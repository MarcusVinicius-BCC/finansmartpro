<?php
require 'includes/db.php';
require 'includes/currency.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Obter moeda base do usuário
$stmt = $pdo->prepare('SELECT moeda_base FROM usuarios WHERE id = ?');
$stmt->execute([$user_id]);
$userrow = $stmt->fetch();
$base = $userrow['moeda_base'] ?? 'BRL';
// DEBUG - REMOVER DEPOIS
error_log("DEBUG: Moeda base = " . $base);

// Totais por moeda
$stmt = $pdo->prepare('SELECT 
    moeda, 
    SUM(CASE WHEN tipo="receita" THEN valor ELSE 0 END) as receita,
    SUM(CASE WHEN tipo="despesa" THEN valor ELSE 0 END) as despesa,
    DATE_FORMAT(data, "%Y-%m") as mes
    FROM lancamentos 
    WHERE id_usuario = ? 
    GROUP BY moeda, DATE_FORMAT(data, "%Y-%m")
    ORDER BY data DESC');
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();

// Processamento dos dados financeiros
$consolidated = 0.0;
$breakdown = [];
$monthly_data = [];
$last_6_months = [];

foreach($rows as $r){
    // Saldo por moeda
    if(!isset($breakdown[$r['moeda']])) {
        $breakdown[$r['moeda']] = [
            'moeda' => $r['moeda'],
            'saldo' => 0,
            'receita_total' => 0,
            'despesa_total' => 0
        ];
    }
    $breakdown[$r['moeda']]['receita_total'] += $r['receita'];
    $breakdown[$r['moeda']]['despesa_total'] += $r['despesa'];
    $breakdown[$r['moeda']]['saldo'] = $breakdown[$r['moeda']]['receita_total'] - $breakdown[$r['moeda']]['despesa_total'];
    
    // Dados mensais para gráficos
    if(!isset($monthly_data[$r['mes']])) {
        $monthly_data[$r['mes']] = [
            'receitas' => 0,
            'despesas' => 0
        ];
    }
    $monthly_data[$r['mes']]['receitas'] += convert_amount($r['receita'], $r['moeda'], $base);
    $monthly_data[$r['mes']]['despesas'] += convert_amount($r['despesa'], $r['moeda'], $base);
}

// Converter saldos para moeda base
foreach($breakdown as $moeda => $dados) {
    $converted = convert_amount($dados['saldo'], $moeda, $base);
    $consolidated += $converted;
    $breakdown[$moeda]['convertido'] = $converted;
}

// Últimas transações
$stmt = $pdo->prepare('SELECT l.*, c.nome as categoria_nome 
    FROM lancamentos l 
    LEFT JOIN categorias c ON l.id_categoria = c.id 
    WHERE l.id_usuario = ? 
    ORDER BY data DESC LIMIT 8');
$stmt->execute([$user_id]);
$latest = $stmt->fetchAll();

// Despesas por categoria (últimos 30 dias)
$stmt = $pdo->prepare('SELECT 
    c.nome as categoria, 
    SUM(l.valor) as total,
    COUNT(*) as quantidade
    FROM lancamentos l 
    JOIN categorias c ON l.id_categoria = c.id 
    WHERE l.id_usuario = ? 
    AND l.tipo = \'despesa\' 
    AND l.data >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY c.nome
    ORDER BY total DESC');
$stmt->execute([$user_id]);
$expenses_by_category = $stmt->fetchAll();

// Cálculo de tendências (comparação mês atual vs anterior)
$current_month = date('Y-m');
$previous_month = date('Y-m', strtotime('-1 month'));

$trend_data = [
    'current' => ['receitas' => 0, 'despesas' => 0],
    'previous' => ['receitas' => 0, 'despesas' => 0]
];

if(isset($monthly_data[$current_month])) {
    $trend_data['current'] = $monthly_data[$current_month];
}
if(isset($monthly_data[$previous_month])) {
    $trend_data['previous'] = $monthly_data[$previous_month];
}

$receitas_trend = 0;
$despesas_trend = 0;
if($trend_data['previous']['receitas'] > 0) {
    $receitas_trend = (($trend_data['current']['receitas'] - $trend_data['previous']['receitas']) / $trend_data['previous']['receitas']) * 100;
}
if($trend_data['previous']['despesas'] > 0) {
    $despesas_trend = (($trend_data['current']['despesas'] - $trend_data['previous']['despesas']) / $trend_data['previous']['despesas']) * 100;
}

$saldo_current = $trend_data['current']['receitas'] - $trend_data['current']['despesas'];
$saldo_previous = $trend_data['previous']['receitas'] - $trend_data['previous']['despesas'];
$saldo_trend = 0;
if($saldo_previous != 0) {
    $saldo_trend = (($saldo_current - $saldo_previous) / abs($saldo_previous)) * 100;
}

// Metas financeiras
try {
    $stmt = $pdo->prepare('SELECT * FROM metas WHERE id_usuario = ? ORDER BY data_limite');
    $stmt->execute([$user_id]);
    $metas = $stmt->fetchAll();
} catch (PDOException $e) {
    // Se a tabela não existir, cria um array vazio
    $metas = [];
    
    // Tenta criar a tabela metas se ela não existir
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS metas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            valor_meta DECIMAL(12,2) NOT NULL,
            valor_atual DECIMAL(12,2) DEFAULT 0.00,
            data_inicio DATE NOT NULL DEFAULT CURRENT_DATE,
            data_limite DATE NOT NULL,
            moeda VARCHAR(10) NOT NULL DEFAULT 'BRL',
            status ENUM('em_andamento', 'concluida', 'atrasada') DEFAULT 'em_andamento',
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
        )");
    } catch (PDOException $e) {
        // Se não conseguir criar a tabela, ignora o erro
    }
}

require 'includes/header.php';
?>
<div class="container-fluid py-4">
    <!-- Resumo Financeiro -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Resumo Financeiro</h4>
        </div>
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card text-white h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-75 mb-1">Patrimônio Total</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($consolidated, 2, ',', '.') ?> <?= $base ?></h3>
                        </div>
                        <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold" style="font-size: 1.5rem; color: #ffd700;"><?= $base === 'BRL' ? 'R$' : ($base === 'USD' ? '$' : '€') ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if($saldo_trend != 0): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-<?= $saldo_trend > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= number_format(abs($saldo_trend), 1) ?>% vs mês anterior
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Atualizado em tempo real</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card text-white h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-75 mb-1">Receitas do Mês</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($trend_data['current']['receitas'], 2, ',', '.') ?> <?= $base ?></h3>
                        </div>
                        <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold" style="font-size: 1.5rem; color: #11998e;"><?= $base === 'BRL' ? 'R$' : ($base === 'USD' ? '$' : '€') ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if($receitas_trend != 0): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-<?= $receitas_trend > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= number_format(abs($receitas_trend), 1) ?>% vs mês anterior
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Sem dados do mês anterior</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card text-white h-100" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-75 mb-1">Despesas do Mês</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($trend_data['current']['despesas'], 2, ',', '.') ?> <?= $base ?></h3>
                        </div>
                        <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold" style="font-size: 1.5rem; color: #eb3349;"><?= $base === 'BRL' ? 'R$' : ($base === 'USD' ? '$' : '€') ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if($despesas_trend != 0): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-<?= $despesas_trend > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= number_format(abs($despesas_trend), 1) ?>% vs mês anterior
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Sem dados do mês anterior</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card text-white h-100" style="background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-75 mb-1">Saldo do Mês</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($saldo_current, 2, ',', '.') ?> <?= $base ?></h3>
                        </div>
                        <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold" style="font-size: 1.5rem; color: #2193b0;"><?= $base === 'BRL' ? 'R$' : ($base === 'USD' ? '$' : '€') ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark">
                            Mês atual: <?= date('m/Y') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Breakdown por moeda -->
    <?php if(count($breakdown) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Detalhamento por Moeda</h5>
        </div>
        <?php foreach($breakdown as $moeda => $dados): ?>
        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-0">Saldo em <?= $moeda ?></h6>
                            <h4 class="mb-0 fw-bold"><?= number_format($dados['saldo'], 2, ',', '.') ?> <?= $moeda ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-coins fa-lg text-white"></i>
                        </div>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Receitas:</span>
                            <span class="text-success fw-bold"><?= number_format($dados['receita_total'], 2, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Despesas:</span>
                            <span class="text-danger fw-bold"><?= number_format($dados['despesa_total'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <!-- Gráficos e Análises -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Análise Financeira</h4>
        </div>
        <div class="col-12 col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Evolução Mensal (Últimos 6 Meses)</h5>
                    <canvas id="cashFlowChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Despesas por Categoria</h5>
                    <canvas id="expensesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico de Comparação Mensal -->
    <div class="row mb-4">
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Comparação Receitas vs Despesas (Últimos 6 Meses)</h5>
                    <canvas id="comparisonChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Categorias de Despesa (30 Dias)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_expenses = array_sum(array_column($expenses_by_category, 'total'));
                                $top_categories = array_slice($expenses_by_category, 0, 5);
                                foreach($top_categories as $cat): 
                                    $percentage = $total_expenses > 0 ? ($cat['total'] / $total_expenses) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['categoria']) ?></td>
                                    <td class="text-end"><?= number_format($cat['total'], 2, ',', '.') ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary"><?= number_format($percentage, 1) ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($top_categories)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Nenhuma despesa nos últimos 30 dias</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Transações e Metas -->
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Últimas Transações</h5>
                    <a href="lancamentos.php" class="btn btn-sm btn-primary">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($latest as $l): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-circle me-2 <?= $l['tipo']=='receita' ? 'text-success' : 'text-danger' ?>" style="font-size: 8px;"></i>
                                            <?= htmlspecialchars($l['descricao']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($l['categoria_nome']) ?>
                                        </span>
                                    </td>
                                    <td class="<?= $l['tipo']=='receita' ? 'text-success' : 'text-danger' ?>">
                                        <?= $l['tipo']=='receita' ? '+' : '-' ?> 
                                        <?= $l['moeda'] ?> <?= number_format($l['valor'],2,',','.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4">
            <div class="row">
                <!-- Conversor de Moedas -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Conversor de Moedas</h5>
                            <form id="convForm">
                                <div class="mb-3">
                                    <label class="form-label">Valor</label>
                                    <input id="conv_amount" type="number" class="form-control" value="100">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">De</label>
                                        <select id="conv_from" class="form-select">
                                            <?php foreach(['BRL', 'USD', 'EUR'] as $c): ?>
                                                <option value="<?= $c ?>" <?= ($c == 'BRL') ? 'selected' : '' ?>><?= $c ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Para</label>
                                        <select id="conv_to" class="form-select">
                                            <?php foreach(['BRL', 'USD', 'EUR'] as $c): ?>
                                                <option value="<?= $c ?>" <?= ($c == $base) ? 'selected' : '' ?>><?= $c ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Converter</button>
                                <div id="convResult" class="mt-3 text-center"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Metas Financeiras -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Metas Financeiras</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMetaModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if(empty($metas)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-bullseye fa-2x mb-2"></i>
                                    <p>Nenhuma meta definida</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($metas as $meta): ?>
                                    <div class="mb-3">
                                        <h6><?= htmlspecialchars($meta['descricao']) ?></h6>
                                        <div class="progress" style="height: 10px;">
                                            <?php $progresso = min(($meta['valor_atual'] / $meta['valor_meta']) * 100, 100); ?>
                                            <div class="progress-bar" role="progressbar" 
                                                style="width: <?= $progresso ?>%;" 
                                                aria-valuenow="<?= $progresso ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-muted">
                                                <?= number_format($meta['valor_atual'], 2, ',', '.') ?> / 
                                                <?= number_format($meta['valor_meta'], 2, ',', '.') ?> 
                                                <?= $meta['moeda'] ?? 'BRL' ?>
                                            </small>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($meta['data_limite'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Meta -->
<div class="modal fade" id="addMetaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Meta Financeira</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="metaForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor da Meta</label>
                        <input type="number" class="form-control" name="valor_meta" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Limite</label>
                        <input type="date" class="form-control" name="data_limite" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const convForm = document.getElementById('convForm');
const fromSelect = document.getElementById('conv_from');
const toSelect = document.getElementById('conv_to');
const resultDiv = document.getElementById('convResult');

function validateConversion() {
    if (fromSelect.value === toSelect.value) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Selecione moedas diferentes.</div>';
        return false;
    }
    resultDiv.innerHTML = '';
    return true;
}

fromSelect.addEventListener('change', validateConversion);
toSelect.addEventListener('change', validateConversion);

convForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!validateConversion()) {
        return;
    }

    const amount = document.getElementById('conv_amount').value;
    const from = fromSelect.value;
    const to = toSelect.value;

    try {
        resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Convertendo...</span></div></div>';
        
        const res = await fetch(`api/conversao.php?from=${from}&to=${to}&amount=${amount}`);
        const json = await res.json();
        
        if (json.result) {
            const formattedResult = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: to
            }).format(json.result);
            
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    ${amount} ${from} = <strong>${formattedResult}</strong>
                </div>`;
        } else {
            throw new Error(json.error || 'Erro na conversão');
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                ${error.message || 'Erro ao converter. Verifique a conexão.'}
            </div>`;
    }
});

// Dados mensais para o gráfico de fluxo de caixa
const monthlyData = <?php echo json_encode($monthly_data); ?>;
const months = Object.keys(monthlyData).sort().slice(-6);
const receitas = months.map(m => monthlyData[m].receitas);
const despesas = months.map(m => monthlyData[m].despesas);

// Gráfico de Fluxo de Caixa
const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
new Chart(cashFlowCtx, {
    type: 'line',
    data: {
        labels: months.map(m => {
            const [year, month] = m.split('-');
            return `${month}/${year}`;
        }),
        datasets: [
            {
                label: 'Receitas',
                data: receitas,
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Despesas',
                data: despesas,
                borderColor: 'rgba(220, 53, 69, 1)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: '<?= $base ?>'
                        }).format(context.raw);
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: '<?= $base ?>'
                        }).format(value);
                    }
                }
            }
        }
    }
});

// Gráfico de Despesas por Categoria
const expensesCtx = document.getElementById('expensesChart').getContext('2d');
const expensesData = <?php echo json_encode($expenses_by_category); ?>;
new Chart(expensesCtx, {
    type: 'doughnut',
    data: {
        labels: expensesData.map(item => item.categoria),
        datasets: [{
            data: expensesData.map(item => item.total),
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(106, 13, 173, 0.8)',
                'rgba(255, 215, 0, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 10,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value * 100) / total).toFixed(1);
                        return `${label}: ${new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: '<?= $base ?>'
                        }).format(value)} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '60%'
    }
});

// Gráfico de Comparação (Barras)
const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
new Chart(comparisonCtx, {
    type: 'bar',
    data: {
        labels: months.map(m => {
            const [year, month] = m.split('-');
            return `${month}/${year}`;
        }),
        datasets: [
            {
                label: 'Receitas',
                data: receitas,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Despesas',
                data: despesas,
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: '<?= $base ?>'
                        }).format(context.raw);
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: '<?= $base ?>'
                        }).format(value);
                    }
                }
            }
        }
    }
});
</script>

<?php require 'includes/footer.php'; ?>