<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/currency.php';
$user_id = $_SESSION['user_id'];

// Processar ações (Adicionar, Editar, Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO orcamentos (id_usuario, id_categoria, mes_ano, valor_limite) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $_POST['id_categoria'], $_POST['mes_ano'], $_POST['valor_limite']]);
            $_SESSION['success_message'] = 'Orçamento criado com sucesso!';
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE orcamentos SET id_categoria = ?, valor_limite = ? WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$_POST['id_categoria'], $_POST['valor_limite'], $_POST['id'], $user_id]);
            $_SESSION['success_message'] = 'Orçamento atualizado com sucesso!';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success_message'] = 'Orçamento excluído com sucesso!';
        }
        
        header('Location: orcamento.php?month=' . ($_POST['mes_ano'] ?? date('Y-m')));
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Erro ao processar ação: ' . $e->getMessage();
    }
}

// Lógica para buscar os dados do orçamento
$month = $_GET['month'] ?? date('Y-m');
$orcamentos = [];

try {
    // Criar tabela orcamentos se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS orcamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_categoria INT NOT NULL,
        mes_ano VARCHAR(7) NOT NULL,
        valor_limite DECIMAL(12,2) NOT NULL,
        UNIQUE KEY unique_orcamento (id_usuario, id_categoria, mes_ano),
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Buscar orçamentos com gastos
    $stmt = $pdo->prepare("
        SELECT 
            o.id, 
            o.id_categoria, 
            o.valor_limite, 
            c.nome as categoria_nome,
            COALESCE(SUM(CASE WHEN l.tipo = 'despesa' THEN l.valor ELSE 0 END), 0) as gasto_atual
        FROM orcamentos o
        JOIN categorias c ON o.id_categoria = c.id
        LEFT JOIN lancamentos l ON l.id_categoria = o.id_categoria 
            AND l.id_usuario = o.id_usuario 
            AND DATE_FORMAT(l.data, '%Y-%m') = o.mes_ano
        WHERE o.id_usuario = ? AND o.mes_ano = ?
        GROUP BY o.id, o.id_categoria, o.valor_limite, c.nome
    ");
    $stmt->execute([$user_id, $month]);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular progresso
    foreach ($orcamentos as &$orc) {
        $orc['progresso'] = ($orc['valor_limite'] > 0) ? ($orc['gasto_atual'] / $orc['valor_limite']) * 100 : 0;
        $orc['restante'] = $orc['valor_limite'] - $orc['gasto_atual'];
        $orc['status'] = $orc['progresso'] >= 100 ? 'danger' : ($orc['progresso'] >= 80 ? 'warning' : 'success');
    }

    // Buscar categorias de despesa para formulário
    $stmt = $pdo->prepare("
        SELECT id, nome 
        FROM categorias 
        WHERE tipo = 'despesa' 
        AND (id_usuario = ? OR id_usuario IS NULL OR id_usuario = 0)
        ORDER BY nome
    ");
    $stmt->execute([$user_id]);
    $categorias_despesa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter moeda base
    $stmt = $pdo->prepare('SELECT moeda_base FROM usuarios WHERE id = ?');
    $stmt->execute([$user_id]);
    $userrow = $stmt->fetch();
    $moeda = $userrow['moeda_base'] ?? 'BRL';

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Meus Orçamentos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoOrcamentoModal">
            <i class="fas fa-plus"></i> Novo Orçamento
        </button>
    </div>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filtro de Mês -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label">Selecionar Mês</label>
                    <input type="month" id="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo dos Orçamentos -->
    <?php if (!empty($orcamentos)): 
        $total_limite = array_sum(array_column($orcamentos, 'valor_limite'));
        $total_gasto = array_sum(array_column($orcamentos, 'gasto_atual'));
        $total_restante = $total_limite - $total_gasto;
        $total_progresso = $total_limite > 0 ? ($total_gasto / $total_limite) * 100 : 0;
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Orçado</h6>
                    <h4><?= number_format($total_limite, 2, ',', '.') ?> <?= $moeda ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-<?= $total_progresso >= 100 ? 'danger' : ($total_progresso >= 80 ? 'warning' : 'success') ?> text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Gasto</h6>
                    <h4><?= number_format($total_gasto, 2, ',', '.') ?> <?= $moeda ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Disponível</h6>
                    <h4><?= number_format($total_restante, 2, ',', '.') ?> <?= $moeda ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Progresso Total</h6>
                    <h4><?= number_format($total_progresso, 1) ?>%</h4>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($orcamentos)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nenhum orçamento definido para este mês. Crie um clicando em "Novo Orçamento".
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orcamentos as $orc): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-<?= $orc['status'] ?>">
                        <div class="card-header bg-<?= $orc['status'] ?> bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tag me-2"></i>
                                <?= htmlspecialchars($orc['categoria_nome']) ?>
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editarOrcamento(<?= $orc['id'] ?>, <?= $orc['id_categoria'] ?>, <?= $orc['valor_limite'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="excluirOrcamento(<?= $orc['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small text-muted">Progresso</span>
                                    <span class="small fw-bold text-<?= $orc['status'] ?>">
                                        <?= number_format($orc['progresso'], 1) ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-<?= $orc['status'] ?>" role="progressbar" 
                                         style="width: <?= min($orc['progresso'], 100) ?>%;" 
                                         aria-valuenow="<?= $orc['progresso'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= number_format(min($orc['progresso'], 100), 0) ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <div class="border rounded p-2 text-center">
                                        <div class="text-muted">Gasto</div>
                                        <div class="fw-bold text-danger">
                                            <?= $moeda ?> <?= number_format($orc['gasto_atual'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2 text-center">
                                        <div class="text-muted">Limite</div>
                                        <div class="fw-bold text-primary">
                                            <?= $moeda ?> <?= number_format($orc['valor_limite'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-2 text-center bg-light">
                                        <div class="text-muted">Disponível</div>
                                        <div class="fw-bold text-<?= $orc['restante'] >= 0 ? 'success' : 'danger' ?>">
                                            <?= $moeda ?> <?= number_format($orc['restante'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if($orc['progresso'] >= 100): ?>
                                <div class="alert alert-danger mt-3 mb-0 small">
                                    <i class="fas fa-exclamation-triangle"></i> Orçamento estourado!
                                </div>
                            <?php elseif($orc['progresso'] >= 80): ?>
                                <div class="alert alert-warning mt-3 mb-0 small">
                                    <i class="fas fa-exclamation-circle"></i> Atenção! Próximo do limite.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Orçamento -->
<div class="modal fade" id="novoOrcamentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="mes_ano" value="<?= htmlspecialchars($month) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias_despesa as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="valor_limite" class="form-label">Valor Limite</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= $moeda ?></span>
                            <input type="number" step="0.01" class="form-control" id="valor_limite" name="valor_limite" required min="0.01">
                        </div>
                        <div class="form-text">Defina o valor máximo que deseja gastar nesta categoria durante o mês.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Orçamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Orçamento -->
<div class="modal fade" id="editarOrcamentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="mes_ano" value="<?= htmlspecialchars($month) ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_id_categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="edit_id_categoria" name="id_categoria" required>
                            <?php foreach ($categorias_despesa as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_valor_limite" class="form-label">Valor Limite</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= $moeda ?></span>
                            <input type="number" step="0.01" class="form-control" id="edit_valor_limite" name="valor_limite" required min="0.01">
                        </div>
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

<!-- Modal Excluir Orçamento -->
<div class="modal fade" id="excluirOrcamentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="mes_ano" value="<?= htmlspecialchars($month) ?>">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este orçamento?</p>
                    <p class="text-muted small">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Sim, Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarOrcamento(id, categoriaId, valorLimite) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_id_categoria').value = categoriaId;
    document.getElementById('edit_valor_limite').value = valorLimite;
    new bootstrap.Modal(document.getElementById('editarOrcamentoModal')).show();
}

function excluirOrcamento(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('excluirOrcamentoModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
