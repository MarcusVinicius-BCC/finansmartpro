<?php
require 'includes/db.php';
require 'includes/currency.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';
require_once 'includes/Cache.php';
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Inicializar cache
$cache = new Cache('cache/', 1800); // 30 minutos

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'categorias',
            'action' => $action,
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($action === 'create') {
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];
        $icone = $_POST['icone'] ?? 'fa-folder';
        $cor = $_POST['cor'] ?? '#6a0dad';
        $descricao = $_POST['descricao'] ?? null;
        
        $sql = "INSERT INTO categorias (id_usuario, nome, tipo, icone, cor, descricao) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $nome, $tipo, $icone, $cor, $descricao]);
        
        // Invalidar cache
        $cache->delete("categorias_{$user_id}");
        
        header('Location: categorias.php?success=created');
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];
        $icone = $_POST['icone'] ?? 'fa-folder';
        $cor = $_POST['cor'] ?? '#6a0dad';
        $descricao = $_POST['descricao'] ?? null;
        
        $sql = "UPDATE categorias SET nome = ?, tipo = ?, icone = ?, cor = ?, descricao = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $tipo, $icone, $cor, $descricao, $id, $user_id]);
        
        // Invalidar cache
        $cache->delete("categorias_{$user_id}");
        
        header('Location: categorias.php?success=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        
        // Verificar se há lançamentos usando esta categoria
        $check = $pdo->prepare("SELECT COUNT(*) FROM lancamentos WHERE id_categoria = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            header('Location: categorias.php?error=in_use');
            exit;
        }
        
        $sql = "DELETE FROM categorias WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        // Invalidar cache
        $cache->delete("categorias_{$user_id}");
        
        header('Location: categorias.php?success=deleted');
        exit;
    }
}

// Buscar categorias com cache
$cache_key = "categorias_{$user_id}";
$categorias = $cache->remember($cache_key, function() use ($pdo, $user_id) {
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM lancamentos WHERE id_categoria = c.id) as total_uso,
            (SELECT SUM(valor) FROM lancamentos WHERE id_categoria = c.id) as total_valor
            FROM categorias c 
            WHERE c.id_usuario = ? 
            ORDER BY c.tipo, c.nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}, 1800); // 30 minutos

$categorias_receita = array_filter($categorias, fn($c) => $c['tipo'] === 'receita');
$categorias_despesa = array_filter($categorias, fn($c) => $c['tipo'] === 'despesa');

// Obter moeda do usuário
$stmt = $pdo->prepare("SELECT moeda_base FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$moeda = $stmt->fetchColumn() ?: 'BRL';

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            $msg = match($_GET['success']) {
                'created' => 'Categoria criada com sucesso!',
                'updated' => 'Categoria atualizada com sucesso!',
                'deleted' => 'Categoria excluída com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php
            $msg = match($_GET['error']) {
                'in_use' => 'Esta categoria não pode ser excluída pois possui lançamentos associados!',
                default => 'Erro ao processar operação!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="text-white mb-0"><i class="fas fa-tags me-2"></i>Categorias</h2>
            <p class="text-white-50">Organize seus lançamentos com categorias personalizadas</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Nova Categoria
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Categorias de Receita -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Receitas (<?= count($categorias_receita) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categorias_receita)): ?>
                        <p class="text-muted text-center py-4">Nenhuma categoria de receita cadastrada</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($categorias_receita as $cat): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="category-icon" style="background: <?= htmlspecialchars($cat['cor']) ?>; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas <?= htmlspecialchars($cat['icone'] ?? 'fa-folder') ?> text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($cat['nome']) ?></h6>
                                            <small class="text-muted">
                                                <?= $cat['total_uso'] ?> lançamento(s) • 
                                                <?= format_currency($cat['total_valor'] ?? 0, $moeda) ?>
                                            </small>
                                            <?php if ($cat['descricao']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($cat['descricao']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)" <?= $cat['total_uso'] > 0 ? 'disabled title="Não é possível excluir categoria em uso"' : '' ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Categorias de Despesa -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-down me-2"></i>Despesas (<?= count($categorias_despesa) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categorias_despesa)): ?>
                        <p class="text-muted text-center py-4">Nenhuma categoria de despesa cadastrada</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($categorias_despesa as $cat): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="category-icon" style="background: <?= htmlspecialchars($cat['cor']) ?>; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas <?= htmlspecialchars($cat['icone'] ?? 'fa-folder') ?> text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($cat['nome']) ?></h6>
                                            <small class="text-muted">
                                                <?= $cat['total_uso'] ?> lançamento(s) • 
                                                <?= format_currency($cat['total_valor'] ?? 0, $moeda) ?>
                                            </small>
                                            <?php if ($cat['descricao']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($cat['descricao']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)" <?= $cat['total_uso'] > 0 ? 'disabled title="Não é possível excluir categoria em uso"' : '' ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Categoria -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ícone</label>
                            <select name="icone" class="form-select">
                                <option value="fa-folder">📁 Pasta</option>
                                <option value="fa-home">🏠 Casa</option>
                                <option value="fa-car">🚗 Carro</option>
                                <option value="fa-shopping-cart">🛒 Compras</option>
                                <option value="fa-utensils">🍽️ Alimentação</option>
                                <option value="fa-heartbeat">❤️ Saúde</option>
                                <option value="fa-graduation-cap">🎓 Educação</option>
                                <option value="fa-plane">✈️ Viagem</option>
                                <option value="fa-gamepad">🎮 Lazer</option>
                                <option value="fa-mobile-alt">📱 Telefone</option>
                                <option value="fa-bolt">⚡ Energia</option>
                                <option value="fa-tshirt">👕 Vestuário</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" class="form-control form-control-color" value="#6a0dad">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="descricao" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" id="edit_tipo" class="form-select" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ícone</label>
                            <select name="icone" id="edit_icone" class="form-select">
                                <option value="fa-folder">📁 Pasta</option>
                                <option value="fa-home">🏠 Casa</option>
                                <option value="fa-car">🚗 Carro</option>
                                <option value="fa-shopping-cart">🛒 Compras</option>
                                <option value="fa-utensils">🍽️ Alimentação</option>
                                <option value="fa-heartbeat">❤️ Saúde</option>
                                <option value="fa-graduation-cap">🎓 Educação</option>
                                <option value="fa-plane">✈️ Viagem</option>
                                <option value="fa-gamepad">🎮 Lazer</option>
                                <option value="fa-mobile-alt">📱 Telefone</option>
                                <option value="fa-bolt">⚡ Energia</option>
                                <option value="fa-tshirt">👕 Vestuário</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" id="edit_cor" class="form-control form-control-color">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="descricao" id="edit_descricao" class="form-control" rows="2"></textarea>
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

<!-- Modal Excluir -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir esta categoria?</p>
                    <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_nome').value = cat.nome;
    document.getElementById('edit_tipo').value = cat.tipo;
    document.getElementById('edit_icone').value = cat.icone || 'fa-folder';
    document.getElementById('edit_cor').value = cat.cor || '#6a0dad';
    document.getElementById('edit_descricao').value = cat.descricao || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteCategory(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
