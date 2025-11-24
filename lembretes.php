<?php
require 'includes/db.php';
require 'includes/currency.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'lembretes',
            'action' => $action,
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($action === 'marcar_lida') {
        $id = $_POST['id'];
        $sql = "UPDATE alertas SET status = 'lido', data_leitura = NOW() WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        header('Location: lembretes.php');
        exit;
    }
    
    if ($action === 'marcar_todas_lidas') {
        $sql = "UPDATE alertas SET status = 'lido', data_leitura = NOW() WHERE id_usuario = ? AND status = 'nao_lido'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        header('Location: lembretes.php?success=marked_all');
        exit;
    }
    
    if ($action === 'excluir') {
        $id = $_POST['id'];
        $sql = "DELETE FROM alertas WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        header('Location: lembretes.php?success=deleted');
        exit;
    }
}

// Buscar todas as notificações
$sql = "SELECT * FROM alertas WHERE id_usuario = ? ORDER BY status ASC, data_criacao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$notificacoes = $stmt->fetchAll();

// Separar por tipo
$nao_lidas = array_filter($notificacoes, fn($n) => $n['status'] === 'nao_lido');
$lidas = array_filter($notificacoes, fn($n) => $n['status'] === 'lido');

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            $msg = match($_GET['success']) {
                'marked_all' => 'Todas as notificações foram marcadas como lidas!',
                'deleted' => 'Notificação excluída com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-0"><i class="fas fa-bell me-2"></i>Lembretes e Alertas</h2>
            <p class="text-white-50">Central de notificações do sistema</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (!empty($nao_lidas)): ?>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="marcar_todas_lidas">
                    <button type="submit" class="btn btn-outline-light">
                        <i class="fas fa-check-double me-2"></i>Marcar Todas como Lidas
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-bell fa-2x text-primary mb-2"></i>
                    <h3 class="mb-0"><?= count($nao_lidas) ?></h3>
                    <small class="text-muted">Não Lidas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?= count($lidas) ?></h3>
                    <small class="text-muted">Lidas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0">
                        <?= count(array_filter($nao_lidas, fn($n) => $n['tipo'] === 'orcamento')) ?>
                    </h3>
                    <small class="text-muted">Orçamentos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-bullseye fa-2x text-info mb-2"></i>
                    <h3 class="mb-0">
                        <?= count(array_filter($nao_lidas, fn($n) => $n['tipo'] === 'meta')) ?>
                    </h3>
                    <small class="text-muted">Metas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Notificações Não Lidas -->
    <?php if (!empty($nao_lidas)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Não Lidas (<?= count($nao_lidas) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($nao_lidas as $notif): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <div>
                                                    <?php
                                                    $icone = match($notif['tipo']) {
                                                        'orcamento' => 'fa-wallet text-warning',
                                                        'meta' => 'fa-bullseye text-info',
                                                        'vencimento' => 'fa-calendar-times text-danger',
                                                        'sistema' => 'fa-cog text-secondary',
                                                        default => 'fa-info-circle text-primary'
                                                    };
                                                    ?>
                                                    <i class="fas <?= $icone ?> fa-2x"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($notif['titulo']) ?></h6>
                                                    <p class="mb-1"><?= htmlspecialchars($notif['mensagem']) ?></p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="marcar_lida">
                                                <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como lida">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="excluir">
                                                <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notificações Lidas -->
    <?php if (!empty($lidas)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Lidas (<?= count($lidas) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($lidas, 0, 20) as $notif): ?>
                                <div class="list-group-item bg-light">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 opacity-75">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <?php
                                                    $icone = match($notif['tipo']) {
                                                        'orcamento' => 'fa-wallet text-muted',
                                                        'meta' => 'fa-bullseye text-muted',
                                                        'vencimento' => 'fa-calendar-times text-muted',
                                                        'sistema' => 'fa-cog text-muted',
                                                        default => 'fa-info-circle text-muted'
                                                    };
                                                    ?>
                                                    <i class="fas <?= $icone ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($notif['titulo']) ?></h6>
                                                    <p class="mb-0 small"><?= htmlspecialchars($notif['mensagem']) ?></p>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="post">
                                            <input type="hidden" name="action" value="excluir">
                                            <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($notificacoes)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                        <h5>Nenhuma notificação</h5>
                        <p class="text-muted">Você está em dia com tudo! 🎉</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
