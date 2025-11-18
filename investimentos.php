<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $nome = trim($_POST['nome']);
            $tipo = $_POST['tipo'];
            $valor_investido = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_investido']));
            $data_inicio = $_POST['data_inicio'];
            $data_vencimento = !empty($_POST['data_vencimento']) ? $_POST['data_vencimento'] : null;
            $risco = $_POST['risco'];
            $notas = trim($_POST['notas']);
            
            $stmt = $pdo->prepare("INSERT INTO investimentos (id_usuario, nome, tipo, valor_investido, valor_atual, data_inicio, data_vencimento, risco, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $nome, $tipo, $valor_investido, $valor_investido, $data_inicio, $data_vencimento, $risco, $notas]);
        }
        elseif ($_POST['action'] === 'update') {
            $id = $_POST['investimento_id'];
            $valor_atual = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_atual']));
            $status = $_POST['status'];
            
            // Calcular rendimento percentual
            $stmt = $pdo->prepare("SELECT valor_investido FROM investimentos WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $investimento = $stmt->fetch();
            
            if ($investimento) {
                $rendimento_percentual = (($valor_atual - $investimento['valor_investido']) / $investimento['valor_investido']) * 100;
                
                $stmt = $pdo->prepare("UPDATE investimentos SET valor_atual = ?, rendimento_percentual = ?, status = ? WHERE id = ? AND id_usuario = ?");
                $stmt->execute([$valor_atual, $rendimento_percentual, $status, $id, $_SESSION['user_id']]);
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['investimento_id'];
            
            $stmt = $pdo->prepare("DELETE FROM investimentos WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
        }
        
        header('Location: investimentos.php');
        exit;
    }
}

// Buscar todos os investimentos do usuário
$stmt = $pdo->prepare("SELECT * FROM investimentos WHERE id_usuario = ? ORDER BY status ASC, data_inicio DESC");
$stmt->execute([$_SESSION['user_id']]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_investido = 0;
$total_atual = 0;
$rendimento_total = 0;

foreach ($investimentos as $inv) {
    if ($inv['status'] === 'Ativo') {
        $total_investido += $inv['valor_investido'];
        $total_atual += $inv['valor_atual'];
    }
}

if ($total_investido > 0) {
    $rendimento_total = (($total_atual - $total_investido) / $total_investido) * 100;
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Meus Investimentos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoInvestimentoModal">
            <i class="fas fa-plus"></i> Novo Investimento
        </button>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Total Investido</h6>
                    <h4 class="mb-0">R$ <?= number_format($total_investido, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Valor Atual</h6>
                    <h4 class="mb-0">R$ <?= number_format($total_atual, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Rendimento Total</h6>
                    <h4 class="mb-0 <?= $rendimento_total >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($rendimento_total >= 0 ? '+' : '') . number_format($rendimento_total, 2, ',', '.') ?>%
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if (empty($investimentos)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Você ainda não tem investimentos cadastrados.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Valor Investido</th>
                                <th>Valor Atual</th>
                                <th>Rendimento</th>
                                <th>Data Início</th>
                                <th>Vencimento</th>
                                <th>Risco</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investimentos as $inv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inv['nome']) ?></td>
                                    <td><?= htmlspecialchars($inv['tipo']) ?></td>
                                    <td>R$ <?= number_format($inv['valor_investido'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($inv['valor_atual'], 2, ',', '.') ?></td>
                                    <td class="<?= $inv['rendimento_percentual'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($inv['rendimento_percentual'] >= 0 ? '+' : '') . number_format($inv['rendimento_percentual'], 2, ',', '.') ?>%
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($inv['data_inicio'])) ?></td>
                                    <td><?= $inv['data_vencimento'] ? date('d/m/Y', strtotime($inv['data_vencimento'])) : '-' ?></td>
                                    <td>
                                        <span class="badge <?php
                                            echo match($inv['risco']) {
                                                'Baixo' => 'bg-success',
                                                'Médio' => 'bg-warning',
                                                'Alto' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>"><?= $inv['risco'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php
                                            echo match($inv['status']) {
                                                'Ativo' => 'bg-success',
                                                'Resgatado' => 'bg-info',
                                                'Vencido' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        ?>"><?= $inv['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick='abrirModalAtualizar(<?= json_encode($inv) ?>)' 
                                                title="Atualizar">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="popover" 
                                                data-bs-trigger="focus"
                                                title="Notas"
                                                data-bs-content="<?= htmlspecialchars($inv['notas'] ?: 'Nenhuma nota disponível.') ?>"
                                                title="Ver notas">
                                                <i class="fas fa-sticky-note"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmarExclusao(<?= $inv['id'] ?>)"
                                                title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<!-- Modal Novo Investimento -->
<div class="modal fade" id="novoInvestimentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Investimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="investimentos.php" method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Investimento</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Selecione o tipo</option>
                            <option value="Poupança">Poupança</option>
                            <option value="CDB">CDB</option>
                            <option value="LCI">LCI</option>
                            <option value="LCA">LCA</option>
                            <option value="Tesouro Direto">Tesouro Direto</option>
                            <option value="Ações">Ações</option>
                            <option value="FII">FII</option>
                            <option value="Criptomoedas">Criptomoedas</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="valor_investido" class="form-label">Valor Investido</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="valor_investido" name="valor_investido" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="data_vencimento" class="form-label">Data de Vencimento (opcional)</label>
                        <input type="date" class="form-control" id="data_vencimento" name="data_vencimento">
                    </div>
                    <div class="mb-3">
                        <label for="risco" class="form-label">Nível de Risco</label>
                        <select class="form-select" id="risco" name="risco" required>
                            <option value="">Selecione o risco</option>
                            <option value="Baixo">Baixo</option>
                            <option value="Médio">Médio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Atualizar Investimento -->
<div class="modal fade" id="atualizarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Atualizar Investimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="investimentos.php" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="investimento_id" id="investimento_id_update">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valor_atual" class="form-label">Valor Atual</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="valor_atual" name="valor_atual" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Ativo">Ativo</option>
                            <option value="Resgatado">Resgatado</option>
                            <option value="Vencido">Vencido</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmação de Exclusão -->
<div class="modal fade" id="excluirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este investimento?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <form action="investimentos.php" method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="investimento_id" id="investimento_id_delete">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar máscaras monetárias
    $('.money').maskMoney({
        prefix: '',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
    
    // Inicializar popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

function abrirModalAtualizar(investimento) {
    document.getElementById('investimento_id_update').value = investimento.id;
    document.getElementById('valor_atual').value = investimento.valor_atual.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('status').value = investimento.status;
    
    new bootstrap.Modal(document.getElementById('atualizarModal')).show();
}

function confirmarExclusao(id) {
    document.getElementById('investimento_id_delete').value = id;
    new bootstrap.Modal(document.getElementById('excluirModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>