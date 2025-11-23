<?php
require 'includes/db.php';
require 'includes/currency.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Obter moeda do usuário
$stmt = $pdo->prepare("SELECT moeda_base FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$moeda = $stmt->fetchColumn() ?: 'BRL';

// Análise de Fluxo de Caixa - Últimos 12 meses
$sql = "SELECT 
    DATE_FORMAT(data, '%Y-%m') as mes,
    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesas,
    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as saldo
    FROM lancamentos 
    WHERE id_usuario = ? AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$fluxo_caixa = $stmt->fetchAll();

// Top Categorias de Despesa
$sql = "SELECT c.nome, c.icone, c.cor, SUM(l.valor) as total
    FROM lancamentos l
    JOIN categorias c ON l.id_categoria = c.id
    WHERE l.id_usuario = ? AND l.tipo = 'despesa' 
    AND l.data >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$top_categorias = $stmt->fetchAll();

// Despesas Recorrentes (detectadas automaticamente)
$sql = "SELECT descricao, AVG(valor) as valor_medio, COUNT(*) as frequencia
    FROM lancamentos
    WHERE id_usuario = ? AND tipo = 'despesa'
    AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY)
    GROUP BY LOWER(descricao)
    HAVING COUNT(*) >= 2
    ORDER BY frequencia DESC, valor_medio DESC
    LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$despesas_recorrentes = $stmt->fetchAll();

// Análise de Sazonalidade
$sql = "SELECT 
    MONTH(data) as mes,
    AVG(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receita_media,
    AVG(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesa_media
    FROM lancamentos
    WHERE id_usuario = ?
    GROUP BY MONTH(data)
    ORDER BY mes";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$sazonalidade = $stmt->fetchAll();

// Taxa de Poupança
$sql = "SELECT 
    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas
    FROM lancamentos
    WHERE id_usuario = ? AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$taxa_dados = $stmt->fetch();
$taxa_poupanca = $taxa_dados['total_receitas'] > 0 
    ? (($taxa_dados['total_receitas'] - $taxa_dados['total_despesas']) / $taxa_dados['total_receitas']) * 100 
    : 0;

// Previsão para próximo mês (média dos últimos 3 meses)
$sql = "SELECT 
    AVG(receitas) as receita_prev,
    AVG(despesas) as despesa_prev
    FROM (
        SELECT 
            SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas,
            SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesas
        FROM lancamentos
        WHERE id_usuario = ? AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY)
        GROUP BY DATE_FORMAT(data, '%Y-%m')
        ORDER BY DATE_FORMAT(data, '%Y-%m') DESC
        LIMIT 3
    ) as ultimos_meses";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$previsao = $stmt->fetch();

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-chart-bar me-2"></i>Analytics Avançado</h2>
            <p class="text-white-50">Análise profunda dos seus dados financeiros</p>
        </div>
    </div>

    <!-- KPIs Principais -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Taxa de Poupança</h6>
                    <h3 class="mb-0 fw-bold" style="color: <?= $taxa_poupanca > 20 ? '#28a745' : ($taxa_poupanca > 10 ? '#ffc107' : '#dc3545') ?>">
                        <?= number_format($taxa_poupanca, 1) ?>%
                    </h3>
                    <small class="text-muted">Meta: 20%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Receita Prevista</h6>
                    <h3 class="mb-0 text-success fw-bold"><?= format_currency($previsao['receita_prev'], $moeda) ?></h3>
                    <small class="text-muted">Próximo mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Despesa Prevista</h6>
                    <h3 class="mb-0 text-danger fw-bold"><?= format_currency($previsao['despesa_prev'], $moeda) ?></h3>
                    <small class="text-muted">Próximo mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Saldo Previsto</h6>
                    <h3 class="mb-0 fw-bold <?= ($previsao['receita_prev'] - $previsao['despesa_prev']) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= format_currency($previsao['receita_prev'] - $previsao['despesa_prev'], $moeda) ?>
                    </h3>
                    <small class="text-muted">Próximo mês</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Fluxo de Caixa -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Fluxo de Caixa - Últimos 12 Meses</h5>
                </div>
                <div class="card-body">
                    <canvas id="fluxoCaixaChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Top Categorias -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 10 Despesas (Último Mês)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_categorias)): ?>
                        <p class="text-muted text-center py-4">Nenhuma despesa registrada</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_categorias as $index => $cat): ?>
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge bg-primary">#<?= $index + 1 ?></span>
                                    </div>
                                    <div class="me-3" style="width: 35px; height: 35px; background: <?= htmlspecialchars($cat['cor']) ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas <?= htmlspecialchars($cat['icone'] ?? 'fa-folder') ?> text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($cat['nome']) ?></h6>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-danger"><?= format_currency($cat['total'], $moeda) ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Despesas Recorrentes -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sync me-2"></i>Despesas Recorrentes Detectadas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($despesas_recorrentes)): ?>
                        <p class="text-muted text-center py-4">Nenhum padrão detectado ainda</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($despesas_recorrentes as $desp): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($desp['descricao']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-redo-alt me-1"></i><?= $desp['frequencia'] ?>x nos últimos 90 dias
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong><?= format_currency($desp['valor_medio'], $moeda) ?></strong>
                                            <br><small class="text-muted">média</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Análise de Sazonalidade -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Análise de Sazonalidade</h5>
                </div>
                <div class="card-body">
                    <canvas id="sazonalidadeChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fluxo de Caixa
const fluxoData = <?= json_encode($fluxo_caixa) ?>;
const meses = fluxoData.map(d => {
    const [ano, mes] = d.mes.split('-');
    return new Date(ano, mes - 1).toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
});

new Chart(document.getElementById('fluxoCaixaChart'), {
    type: 'line',
    data: {
        labels: meses,
        datasets: [
            {
                label: 'Receitas',
                data: fluxoData.map(d => d.receitas),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Despesas',
                data: fluxoData.map(d => d.despesas),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Saldo',
                data: fluxoData.map(d => d.saldo),
                borderColor: '#6a0dad',
                backgroundColor: 'rgba(106, 13, 173, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => formatCurrency(value)
                }
            }
        }
    }
});

// Sazonalidade
const sazonalidadeData = <?= json_encode($sazonalidade) ?>;
const mesesAno = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

new Chart(document.getElementById('sazonalidadeChart'), {
    type: 'bar',
    data: {
        labels: mesesAno,
        datasets: [
            {
                label: 'Receita Média',
                data: mesesAno.map((_, i) => {
                    const found = sazonalidadeData.find(s => parseInt(s.mes) === i + 1);
                    return found ? parseFloat(found.receita_media) : 0;
                }),
                backgroundColor: 'rgba(40, 167, 69, 0.7)'
            },
            {
                label: 'Despesa Média',
                data: mesesAno.map((_, i) => {
                    const found = sazonalidadeData.find(s => parseInt(s.mes) === i + 1);
                    return found ? parseFloat(found.despesa_media) : 0;
                }),
                backgroundColor: 'rgba(220, 53, 69, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => formatCurrency(value)
                }
            }
        }
    }
});
</script>

<?php require 'includes/footer.php'; ?>
