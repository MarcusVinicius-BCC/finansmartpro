<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';

// Processar o formulário de nova meta e categorias
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('csrf_validation_failed', [
                'module' => 'metas',
                'action' => $_POST['action'],
                'user_id' => $_SESSION['user_id']
            ]);
            die('Token CSRF inválido. Recarregue a página.');
        }
        if ($_POST['action'] === 'add_category') {
            $nome = trim($_POST['nome']);
            $tipo = $_POST['tipo'];
            
            $stmt = $pdo->prepare("INSERT INTO categorias (id_usuario, nome, tipo) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $nome, $tipo]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        elseif ($_POST['action'] === 'edit_category') {
            $id = $_POST['id'];
            $nome = trim($_POST['nome']);
            $tipo = $_POST['tipo'];
            
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$nome, $tipo, $id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        elseif ($_POST['action'] === 'delete_category') {
            $id = $_POST['id'];
            
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        elseif ($_POST['action'] === 'create') {
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
        elseif ($_POST['action'] === 'edit') {
            $meta_id = $_POST['meta_id'];
            $descricao = trim($_POST['descricao']);
            $valor_meta = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_meta']));
            $data_limite = $_POST['data_limite'];
            $categoria = $_POST['categoria'];
            
            $stmt = $pdo->prepare("UPDATE metas SET descricao = ?, valor_meta = ?, data_limite = ?, categoria = ? WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$descricao, $valor_meta, $data_limite, $categoria, $meta_id, $_SESSION['user_id']]);
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

// Atualizar progresso automático das metas baseado nos lançamentos
foreach ($metas as &$meta) {
    // Buscar soma dos lançamentos da categoria da meta
    $stmtLanc = $pdo->prepare("
        SELECT COALESCE(SUM(l.valor), 0) as total 
        FROM lancamentos l
        INNER JOIN categorias c ON l.id_categoria = c.id
        WHERE l.id_usuario = ? 
        AND c.nome = ? 
        AND l.data <= ?
    ");
    $stmtLanc->execute([$_SESSION['user_id'], $meta['categoria'], $meta['data_limite']]);
    $resultado = $stmtLanc->fetch(PDO::FETCH_ASSOC);
    $valor_calculado = abs($resultado['total']); // Usar valor absoluto
    
    // Atualizar valor_atual da meta se mudou
    if ($valor_calculado != $meta['valor_atual']) {
        $stmtUpdate = $pdo->prepare("UPDATE metas SET valor_atual = ? WHERE id = ?");
        $stmtUpdate->execute([$valor_calculado, $meta['id']]);
        $meta['valor_atual'] = $valor_calculado;
    }
    
    // Atualizar status automaticamente
    $novo_status = $meta['status'];
    if ($valor_calculado >= $meta['valor_meta']) {
        $novo_status = 'Concluída';
    } elseif (strtotime($meta['data_limite']) < time() && $valor_calculado < $meta['valor_meta']) {
        $novo_status = 'Atrasada';
    } else {
        $novo_status = 'Em andamento';
    }
    
    if ($novo_status != $meta['status']) {
        $stmtStatus = $pdo->prepare("UPDATE metas SET status = ? WHERE id = ?");
        $stmtStatus->execute([$novo_status, $meta['id']]);
        $meta['status'] = $novo_status;
    }
}
unset($meta); // Quebrar referência

// Buscar categorias (sistema + usuário)
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id_usuario = ? OR id_usuario = 0 OR id_usuario IS NULL ORDER BY tipo, nome");
$stmt->execute([$_SESSION['user_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar por tipo
$categoriasReceita = array_filter($categorias, fn($c) => $c['tipo'] === 'receita');
$categoriasDespesa = array_filter($categorias, fn($c) => $c['tipo'] === 'despesa');

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Minhas Metas Financeiras</h1>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> O progresso é atualizado automaticamente baseado nos lançamentos da categoria
            </small>
        </div>
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
                                        <button class="btn btn-link text-dark p-0" type="button" id="dropdownMeta<?= $meta['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMeta<?= $meta['id'] ?>">
                                            <li>
                                                <button class="dropdown-item" type="button" 
                                                    onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($meta)) ?>)">
                                                    <i class="fas fa-pen"></i> Editar Meta
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" type="button" 
                                                    onclick="abrirModalAtualizar(<?= htmlspecialchars(json_encode($meta)) ?>)">
                                                    <i class="fas fa-edit"></i> Ajuste Manual
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
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
                                        <small class="text-muted">
                                            Valor Atual: 
                                            <i class="fas fa-sync-alt text-success" title="Atualizado automaticamente pelos lançamentos"></i>
                                        </small>
                                        <strong class="d-block"><?= fmt_currency($meta['valor_atual']) ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Meta:</small>
                                        <strong class="d-block"><?= fmt_currency($meta['valor_meta']) ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Categoria:</small>
                                        <span class="d-block"><?= htmlspecialchars($meta['categoria']) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Faltam:</small>
                                        <strong class="d-block text-<?= $meta['valor_atual'] >= $meta['valor_meta'] ? 'success' : 'warning' ?>">
                                            <?= fmt_currency(max(0, $meta['valor_meta'] - $meta['valor_atual'])) ?>
                                        </strong>
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
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
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
                        <div class="input-group">
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <optgroup label="Receitas">
                                    <?php foreach ($categoriasReceita as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nome']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Despesas">
                                    <?php foreach ($categoriasDespesa as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nome']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="openQuickAddCategory()" title="Adicionar nova categoria">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="openManageCategories()" title="Gerenciar categorias">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
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

<!-- Modal Editar Meta -->
<div class="modal fade" id="editarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Meta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="metas.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="meta_id" id="meta_id_edit">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descricao_edit" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="descricao_edit" name="descricao" required>
                    </div>
                    <div class="mb-3">
                        <label for="valor_meta_edit" class="form-label">Valor da Meta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="valor_meta_edit" name="valor_meta" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="categoria_edit" class="form-label">Categoria</label>
                        <div class="input-group">
                            <select class="form-select" id="categoria_edit" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <optgroup label="Receitas">
                                    <?php foreach ($categoriasReceita as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nome']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Despesas">
                                    <?php foreach ($categoriasDespesa as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nome']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="openQuickAddCategoryFromEdit()" title="Adicionar nova categoria">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="openManageCategoriesFromEdit()" title="Gerenciar categorias">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="data_limite_edit" class="form-label">Data Limite</label>
                        <input type="date" class="form-control" id="data_limite_edit" name="data_limite" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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
                <h5 class="modal-title">Ajuste Manual do Progresso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="metas.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="meta_id" id="meta_id_update">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <small>O progresso é atualizado automaticamente pelos lançamentos. Use este ajuste apenas se necessário.</small>
                    </div>
                    <div class="mb-3">
                        <label for="valor_atual" class="form-label">Valor Atual (Ajuste Manual)</label>
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
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
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

<!-- Modal Adicionar Categoria Rápida -->
<div class="modal fade" id="quickAddCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Nova Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="quick_nome" class="form-label">Nome da Categoria</label>
                    <input type="text" class="form-control" id="quick_nome" required>
                </div>
                <div class="mb-3">
                    <label for="quick_tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="quick_tipo" required>
                        <option value="receita">Receita</option>
                        <option value="despesa">Despesa</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveQuickCategory()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Categorias -->
<div class="modal fade" id="manageCategories" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerenciar Categorias</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Receitas</h6>
                        <div class="list-group">
                            <?php foreach ($categoriasReceita as $cat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($cat['nome']) ?></span>
                                    <?php if ($cat['id_usuario'] != 0 && $cat['id_usuario'] != null): ?>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sistema</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">Despesas</h6>
                        <div class="list-group">
                            <?php foreach ($categoriasDespesa as $cat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($cat['nome']) ?></span>
                                    <?php if ($cat['id_usuario'] != 0 && $cat['id_usuario'] != null): ?>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sistema</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_cat_id">
                <div class="mb-3">
                    <label for="edit_nome" class="form-label">Nome da Categoria</label>
                    <input type="text" class="form-control" id="edit_nome" required>
                </div>
                <div class="mb-3">
                    <label for="edit_tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="edit_tipo" required>
                        <option value="receita">Receita</option>
                        <option value="despesa">Despesa</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveEditCategory()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar máscaras de dinheiro
    $('.money').maskMoney({
        prefix: '',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
    
    // Limpar backdrop ao fechar modais
    $('#quickAddCategory').on('hidden.bs.modal', function() {
        // Remover qualquer backdrop residual
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
        
        // Reabrir modal de nova meta se não foi salvamento
        if (!$(this).data('saved')) {
            setTimeout(() => {
                const novaMetaModal = new bootstrap.Modal(document.getElementById('novametaModal'));
                novaMetaModal.show();
            }, 300);
        }
        $(this).data('saved', false); // Reset flag
    });
    
    $('#manageCategories').on('hidden.bs.modal', function() {
        // Remover qualquer backdrop residual
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
        
        setTimeout(() => {
            const novaMetaModal = new bootstrap.Modal(document.getElementById('novametaModal'));
            novaMetaModal.show();
        }, 300);
    });
});

function abrirModalEditar(meta) {
    document.getElementById('meta_id_edit').value = meta.id;
    document.getElementById('descricao_edit').value = meta.descricao;
    document.getElementById('valor_meta_edit').value = parseFloat(meta.valor_meta).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('categoria_edit').value = meta.categoria;
    document.getElementById('data_limite_edit').value = meta.data_limite;
    
    // Reinicializar maskMoney no campo editado
    $('#valor_meta_edit').maskMoney({
        prefix: '',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
    
    new bootstrap.Modal(document.getElementById('editarModal')).show();
}

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

// Funções de gerenciamento de categorias
function openQuickAddCategory() {
    // Fechar o modal de nova meta temporariamente
    const novaMetaModal = bootstrap.Modal.getInstance(document.getElementById('novametaModal'));
    if (novaMetaModal) {
        novaMetaModal.hide();
    }
    
    // Aguardar o fechamento e abrir o modal de categoria
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('quickAddCategory'));
        modal.show();
    }, 300);
}

function openManageCategories() {
    // Fechar o modal de nova meta temporariamente
    const novaMetaModal = bootstrap.Modal.getInstance(document.getElementById('novametaModal'));
    if (novaMetaModal) {
        novaMetaModal.hide();
    }
    
    // Aguardar o fechamento e abrir o modal de gerenciamento
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('manageCategories'));
        modal.show();
    }, 300);
}

function saveQuickCategory() {
    const nome = document.getElementById('quick_nome').value;
    const tipo = document.getElementById('quick_tipo').value;
    
    if (!nome) {
        alert('Por favor, preencha o nome da categoria');
        return;
    }
    
    $.ajax({
        url: 'metas.php',
        method: 'POST',
        data: {
            csrf_token: '<?= Security::generateCSRFToken() ?>',
            action: 'add_category',
            nome: nome,
            tipo: tipo
        },
        success: function(response) {
            // Marcar como salvo para não reabrir automaticamente
            $('#quickAddCategory').data('saved', true);
            
            // Fechar o modal de adicionar categoria
            const quickModalEl = document.getElementById('quickAddCategory');
            const quickModal = bootstrap.Modal.getInstance(quickModalEl);
            if (quickModal) {
                quickModal.hide();
            }
            
            // Limpar backdrop
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
            
            // Adicionar a nova categoria ao select
            const select = document.getElementById('categoria');
            const optgroup = tipo === 'receita' ? select.querySelector('optgroup[label="Receitas"]') : select.querySelector('optgroup[label="Despesas"]');
            const newOption = document.createElement('option');
            newOption.value = nome;
            newOption.textContent = nome;
            newOption.selected = true;
            optgroup.appendChild(newOption);
            
            // Limpar o formulário
            document.getElementById('quick_nome').value = '';
            document.getElementById('quick_tipo').value = 'receita';
            
            // Reabrir modal de nova meta
            setTimeout(() => {
                const novaMetaModal = new bootstrap.Modal(document.getElementById('novametaModal'));
                novaMetaModal.show();
            }, 300);
        },
        error: function() {
            alert('Erro ao adicionar categoria');
        }
    });
}

function editCategory(cat) {
    document.getElementById('edit_cat_id').value = cat.id;
    document.getElementById('edit_nome').value = cat.nome;
    document.getElementById('edit_tipo').value = cat.tipo;
    
    bootstrap.Modal.getInstance(document.getElementById('manageCategories')).hide();
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function saveEditCategory() {
    const id = document.getElementById('edit_cat_id').value;
    const nome = document.getElementById('edit_nome').value;
    const tipo = document.getElementById('edit_tipo').value;
    
    if (!nome) {
        alert('Por favor, preencha o nome da categoria');
        return;
    }
    
    $.ajax({
        url: 'metas.php',
        method: 'POST',
        data: {
            csrf_token: '<?= Security::generateCSRFToken() ?>',
            action: 'edit_category',
            id: id,
            nome: nome,
            tipo: tipo
        },
        success: function(response) {
            location.reload();
        },
        error: function() {
            alert('Erro ao editar categoria');
        }
    });
}

function deleteCategory(id) {
    if (!confirm('Tem certeza que deseja excluir esta categoria?')) {
        return;
    }
    
    $.ajax({
        url: 'metas.php',
        method: 'POST',
        data: {
            csrf_token: '<?= Security::generateCSRFToken() ?>',
            action: 'delete_category',
            id: id
        },
        success: function(response) {
            location.reload();
        },
        error: function() {
            alert('Erro ao excluir categoria');
        }
    });
}

// Funções para abrir modais de categoria a partir do modal de editar meta
function openQuickAddCategoryFromEdit() {
    const editarModal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
    if (editarModal) {
        editarModal.hide();
    }
    
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('quickAddCategory'));
        modal.show();
        
        // Reabrir modal de editar ao fechar
        $('#quickAddCategory').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
            
            if (!$(this).data('saved')) {
                setTimeout(() => {
                    new bootstrap.Modal(document.getElementById('editarModal')).show();
                }, 300);
            }
            $(this).data('saved', false);
        });
    }, 300);
}

function openManageCategoriesFromEdit() {
    const editarModal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
    if (editarModal) {
        editarModal.hide();
    }
    
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('manageCategories'));
        modal.show();
        
        // Reabrir modal de editar ao fechar
        $('#manageCategories').off('hidden.bs.modal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
            
            setTimeout(() => {
                new bootstrap.Modal(document.getElementById('editarModal')).show();
            }, 300);
        });
    }, 300);
}

// Debug e inicialização de dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se Bootstrap está carregado
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap não está carregado!');
    } else {
        console.log('Bootstrap carregado com sucesso');
        
        // Inicializar todos os dropdowns manualmente
        const dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
        console.log('Dropdowns inicializados:', dropdownList.length);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>