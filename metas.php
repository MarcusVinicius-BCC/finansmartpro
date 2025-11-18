<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

// Processar o formulário de nova meta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $descricao = trim($_POST['descricao']);
            $valor_meta = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_meta']));
            $data_limite = $_POST['data_limite'];
            $categoria = $_POST['categoria'];
            
            $stmt = $pdo->prepare("INSERT INTO metas (id_usuario, descricao, valor_meta, valor_atual, data_limite, categoria, status) VALUES (?, ?, ?, 0, ?, ?, 'Em andamento')");
            $stmt->execute([$_SESSION['user_id'], $descricao, $valor_meta, $data_limite, $categoria]);
        }
        elseif ($_POST['action'] === 'update') {
            $meta_id = $_POST['meta_id'];
            $valor_atual = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_atual']));
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE metas SET valor_atual = ?, status = ? WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$valor_atual, $status, $meta_id, $_SESSION['user_id']]);
        }
        elseif ($_POST['action'] === 'delete') {
            $meta_id = $_POST['meta_id'];
            
            $stmt = $pdo->prepare("DELETE FROM metas WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$meta_id, $_SESSION['user_id']]);
        }
        
        header('Location: metas.php');
        exit;
    }
}

// Buscar todas as metas do usuário
$stmt = $pdo->prepare("SELECT * FROM metas WHERE id_usuario = ? ORDER BY data_limite ASC");
$stmt->execute([$_SESSION['user_id']]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Minhas Metas Financeiras</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novametaModal">
            <i class="fas fa-plus"></i> Nova Meta
        </button>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if (empty($metas)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Você ainda não tem metas cadastradas.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($metas as $meta): ?>
                        <?php 
                        $progresso = ($meta['valor_atual'] / $meta['valor_meta']) * 100;
                        $progresso = min($progresso, 100);
                        
                        $status_class = match($meta['status']) {
                            'Concluída' => 'bg-success',
                            'Em andamento' => 'bg-primary',
                            'Atrasada' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($meta['descricao']) ?></h5>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" type="button" 
                                                    onclick="abrirModalAtualizar(<?= htmlspecialchars(json_encode($meta)) ?>)">
                                                    <i class="fas fa-edit"></i> Atualizar Progresso
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger" type="button"
                                                    onclick="confirmarExclusao(<?= $meta['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Excluir Meta
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label small">Progresso</label>
                                        <div class="progress">
                                            <div class="progress-bar <?= $status_class ?>" 
                                                role="progressbar" 
                                                style="width: <?= $progresso ?>%" 
                                                aria-valuenow="<?= $progresso ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                <?= number_format($progresso, 0) ?>%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Valor Atual:</small>
                                        <strong class="d-block">R$ <?= number_format($meta['valor_atual'], 2, ',', '.') ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Meta:</small>
                                        <strong class="d-block">R$ <?= number_format($meta['valor_meta'], 2, ',', '.') ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Categoria:</small>
                                        <span class="d-block"><?= htmlspecialchars($meta['categoria']) ?></span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Data Limite:</small>
                                        <span class="d-block"><?= date('d/m/Y', strtotime($meta['data_limite'])) ?></span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <span class="badge <?= $status_class ?>"><?= $meta['status'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nova Meta -->
<div class="modal fade" id="novametaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Meta Financeira</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="metas.php" method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="descricao" name="descricao" required>
                    </div>
                    <div class="mb-3">
                        <label for="valor_meta" class="form-label">Valor da Meta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="valor_meta" name="valor_meta" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria" name="categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <option value="Economia">Economia</option>
                            <option value="Investimento">Investimento</option>
                            <option value="Compra">Compra</option>
                            <option value="Viagem">Viagem</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="data_limite" class="form-label">Data Limite</label>
                        <input type="date" class="form-control" id="data_limite" name="data_limite" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Meta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Atualizar Meta -->
<div class="modal fade" id="atualizarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Atualizar Progresso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="metas.php" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="meta_id" id="meta_id_update">
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
                            <option value="Em andamento">Em andamento</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Atrasada">Atrasada</option>
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
                <p>Tem certeza que deseja excluir esta meta?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <form action="metas.php" method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="meta_id" id="meta_id_delete">
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
    $('.money').maskMoney({
        prefix: '',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
});

function abrirModalAtualizar(meta) {
    document.getElementById('meta_id_update').value = meta.id;
    document.getElementById('valor_atual').value = meta.valor_atual.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('status').value = meta.status;
    
    new bootstrap.Modal(document.getElementById('atualizarModal')).show();
}

function confirmarExclusao(metaId) {
    document.getElementById('meta_id_delete').value = metaId;
    new bootstrap.Modal(document.getElementById('excluirModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>