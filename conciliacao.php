<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/Pagination.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar conciliação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'conciliacao',
            'action' => $_POST['action'] ?? 'unknown',
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'conciliar') {
        $sql = "INSERT INTO conciliacoes (id_usuario, id_conta, saldo_sistema, saldo_real, divergencia, observacoes, data_conciliacao) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $divergencia = $_POST['saldo_real'] - $_POST['saldo_sistema'];
        $stmt->execute([
            $user_id,
            $_POST['id_conta'],
            $_POST['saldo_sistema'],
            $_POST['saldo_real'],
            $divergencia,
            $_POST['observacoes'] ?: null
        ]);
        
        // Atualizar saldo da conta se necessário
        if (isset($_POST['atualizar_saldo']) && $_POST['atualizar_saldo'] == '1') {
            $sql = "UPDATE contas_bancarias SET saldo_atual = ? WHERE id = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['saldo_real'], $_POST['id_conta'], $user_id]);
        }
        
        header('Location: conciliacao.php?success=conciliado');
        exit;
    }
}

// Buscar contas bancárias
$sql = "SELECT * FROM contas_bancarias WHERE id_usuario = ? ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$contas = $stmt->fetchAll();

// Calcular saldos do sistema
$saldos_sistema = [];
foreach ($contas as $conta) {
    // Saldo inicial + lançamentos
    $sql = "SELECT 
            (SELECT saldo_inicial FROM contas_bancarias WHERE id = ?) +
            COALESCE((SELECT SUM(valor) FROM lancamentos WHERE id_usuario = ? AND tipo = 'receita'), 0) -
            COALESCE((SELECT SUM(valor) FROM lancamentos WHERE id_usuario = ? AND tipo = 'despesa'), 0) as saldo_calculado";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conta['id'], $user_id, $user_id]);
    $saldos_sistema[$conta['id']] = $stmt->fetch()['saldo_calculado'];
}

// Buscar histórico de conciliações
$sql = "SELECT c.*, cb.nome as conta_nome, cb.banco 
        FROM conciliacoes c
        JOIN contas_bancarias cb ON c.id_conta = cb.id
        WHERE c.id_usuario = ?
        ORDER BY c.data_conciliacao DESC
        LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$historico = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-balance-scale me-2"></i>Conciliação Bancária</h2>
            <p class="text-white-50">Compare saldo real do banco com saldo do sistema</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Conciliação realizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Painel de Conciliação -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Nova Conciliação</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="formConciliacao">
                        <input type="hidden" name="action" value="conciliar">
                        
                        <div class="mb-3">
                            <label class="form-label">Conta Bancária</label>
                            <select name="id_conta" id="select_conta" class="form-select" required onchange="atualizarSaldoSistema()">
                                <option value="">Selecione uma conta</option>
                                <?php foreach ($contas as $conta): ?>
                                    <option value="<?= $conta['id'] ?>" 
                                            data-saldo="<?= $saldos_sistema[$conta['id']] ?>"
                                            data-moeda="<?= $conta['moeda'] ?>">
                                        <?= htmlspecialchars($conta['nome']) ?> - <?= $conta['banco'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saldo no Sistema</label>
                                <input type="text" name="saldo_sistema" id="saldo_sistema" class="form-control" readonly>
                                <small class="text-muted">Calculado automaticamente</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saldo Real no Banco</label>
                                <input type="text" name="saldo_real" id="saldo_real" class="form-control money-input" required placeholder="0,00">
                                <small class="text-muted">Conforme extrato bancário</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Divergência</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <span id="moeda_simbolo">R$</span>
                                </span>
                                <input type="text" id="divergencia" class="form-control fw-bold" readonly>
                            </div>
                            <small id="status_divergencia" class="text-muted"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Descreva a origem da divergência (opcional)"></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="atualizar_saldo" value="1" id="atualizar_saldo">
                            <label class="form-check-label" for="atualizar_saldo">
                                Atualizar saldo da conta com o valor real
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check me-2"></i>Conciliar Conta
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dicas -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Dicas de Conciliação</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li>Faça conciliações <strong>mensalmente</strong></li>
                        <li>Use o <strong>saldo do extrato</strong> bancário oficial</li>
                        <li>Verifique se todos os <strong>lançamentos</strong> foram registrados</li>
                        <li>Considere <strong>tarifas bancárias</strong> não lançadas</li>
                        <li>Cheque possíveis <strong>lançamentos duplicados</strong></li>
                        <li>Investigue divergências acima de <strong>R$ 10,00</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Resumo de Contas -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-university me-2"></i>Resumo das Contas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($contas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-university fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma conta bancária cadastrada</p>
                            <a href="contas.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Cadastrar Conta
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Conta</th>
                                        <th>Banco</th>
                                        <th>Saldo Sistema</th>
                                        <th>Saldo Real</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contas as $conta): ?>
                                        <?php
                                        // Buscar última conciliação
                                        $sql = "SELECT saldo_real, divergencia FROM conciliacoes 
                                                WHERE id_conta = ? ORDER BY data_conciliacao DESC LIMIT 1";
                                        $stmt = $pdo->prepare($sql);
                                        $stmt->execute([$conta['id']]);
                                        $ultima = $stmt->fetch();
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($conta['nome']) ?></strong>
                                                <br><small class="text-muted"><?= $conta['tipo'] ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($conta['banco']) ?></td>
                                            <td class="fw-bold">
                                                <?= $conta['moeda'] ?> <?= number_format($saldos_sistema[$conta['id']], 2, ',', '.') ?>
                                            </td>
                                            <td>
                                                <?php if ($ultima): ?>
                                                    <?= $conta['moeda'] ?> <?= number_format($ultima['saldo_real'], 2, ',', '.') ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Não conciliado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($ultima): ?>
                                                    <?php if (abs($ultima['divergencia']) < 0.01): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check"></i> Conciliado
                                                        </span>
                                                    <?php elseif (abs($ultima['divergencia']) < 10): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle"></i> Divergência Pequena
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times"></i> Divergência Grande
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico -->
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Conciliações</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($historico)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma conciliação realizada ainda</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Conta</th>
                                        <th>Saldo Sistema</th>
                                        <th>Saldo Real</th>
                                        <th>Divergência</th>
                                        <th>Obs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $h): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($h['data_conciliacao'])) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($h['conta_nome']) ?></strong>
                                                <br><small class="text-muted"><?= $h['banco'] ?></small>
                                            </td>
                                            <td><?= fmt_currency($h['saldo_sistema']) ?></td>
                                            <td><?= fmt_currency($h['saldo_real']) ?></td>
                                            <td>
                                                <?php
                                                $classe = abs($h['divergencia']) < 0.01 ? 'success' : (abs($h['divergencia']) < 10 ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?= $classe ?>">
                                                    <?= fmt_currency($h['divergencia']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($h['observacoes']): ?>
                                                    <i class="fas fa-comment-alt text-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="<?= htmlspecialchars($h['observacoes']) ?>"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Formatação monetária
function formatMoney(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) / 100).toFixed(2);
    value = value.replace('.', ',');
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = value;
}

function unformatMoney(value) {
    return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
}

document.querySelectorAll('.money-input').forEach(input => {
    input.addEventListener('input', function() {
        formatMoney(this);
        calcularDivergencia();
    });
});

// Atualizar saldo do sistema ao selecionar conta
function atualizarSaldoSistema() {
    const select = document.getElementById('select_conta');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const saldo = parseFloat(option.dataset.saldo);
        const moeda = option.dataset.moeda;
        
        document.getElementById('saldo_sistema').value = saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('moeda_simbolo').textContent = moeda;
        
        calcularDivergencia();
    } else {
        document.getElementById('saldo_sistema').value = '';
        document.getElementById('divergencia').value = '';
        document.getElementById('status_divergencia').textContent = '';
    }
}

// Calcular divergência em tempo real
function calcularDivergencia() {
    const saldoSistema = unformatMoney(document.getElementById('saldo_sistema').value);
    const saldoReal = unformatMoney(document.getElementById('saldo_real').value);
    
    if (saldoSistema && saldoReal) {
        const divergencia = saldoReal - saldoSistema;
        const divergenciaFormatada = divergencia.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        document.getElementById('divergencia').value = divergenciaFormatada;
        
        const statusDiv = document.getElementById('status_divergencia');
        
        if (Math.abs(divergencia) < 0.01) {
            statusDiv.innerHTML = '<i class="fas fa-check text-success"></i> Contas conciliadas perfeitamente!';
            statusDiv.className = 'text-success';
        } else if (Math.abs(divergencia) < 10) {
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Pequena divergência detectada';
            statusDiv.className = 'text-warning';
        } else {
            statusDiv.innerHTML = '<i class="fas fa-times text-danger"></i> Divergência significativa - verifique lançamentos!';
            statusDiv.className = 'text-danger';
        }
    }
}

// Processar formulário
document.getElementById('formConciliacao').addEventListener('submit', function(e) {
    const saldoRealInput = document.querySelector('[name="saldo_real"]');
    const saldoSistemaInput = document.querySelector('[name="saldo_sistema"]');
    
    // Criar inputs hidden com valores numéricos
    const hiddenReal = document.createElement('input');
    hiddenReal.type = 'hidden';
    hiddenReal.name = 'saldo_real';
    hiddenReal.value = unformatMoney(saldoRealInput.value);
    
    const hiddenSistema = document.createElement('input');
    hiddenSistema.type = 'hidden';
    hiddenSistema.name = 'saldo_sistema';
    hiddenSistema.value = unformatMoney(saldoSistemaInput.value);
    
    this.appendChild(hiddenReal);
    this.appendChild(hiddenSistema);
    
    saldoRealInput.removeAttribute('name');
    saldoSistemaInput.removeAttribute('name');
});

// Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<?php include 'includes/footer.php'; ?>
