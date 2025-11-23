<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FinanSmart Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">

    <?php 
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page === 'index.php'): ?>
        <link href="assets/css/hero.css" rel="stylesheet">
    <?php endif; ?>
    <?php if ($current_page === 'dashboard.php'): ?>
        <link href="assets/css/dashboard.css" rel="stylesheet">
        <link href="assets/css/analytics.css" rel="stylesheet">
    <?php endif; ?>
    <?php if (in_array($current_page, ['login.php', 'register.php'])): ?>
        <link href="assets/css/auth.css" rel="stylesheet">
    <?php endif; ?>
    <?php if (in_array($current_page, ['dashboard.php', 'index.php', 'investimentos.php'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <?php endif; ?>
    <?php if ($current_page === 'index.php'): ?>
        <script src="assets/js/index.js" defer></script>
    <?php endif; ?>
    <?php if ($current_page === 'dashboard.php'): ?>
        <script src="assets/js/dashboard.js" defer></script>
        <script src="assets/js/analytics.js" defer></script>
    <?php endif; ?>
    <?php if(isset($_SESSION['user_id']) && $current_page !== 'index.php'): ?>
        <script src="assets/js/sidebar.js" defer></script>
        <script src="assets/js/notifications.js" defer></script>
    <?php endif; ?>
    <script src="assets/js/main.js" defer></script>


</head>
<body class="<?php echo $current_page === 'index.php' ? 'index-page' : ''; ?>">
<?php if(isset($_SESSION['user_id']) && $current_page !== 'index.php'): ?>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/mockup.png" alt="FinanSmart Pro Logo">
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'lancamentos.php' ? 'active' : '' ?>" href="lancamentos.php">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Lançamentos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'orcamento.php' ? 'active' : '' ?>" href="orcamento.php">
                            <i class="fas fa-wallet"></i>
                            <span>Orçamentos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'metas.php' ? 'active' : '' ?>" href="metas.php">
                            <i class="fas fa-bullseye"></i>
                            <span>Metas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Relatórios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'investimentos.php' ? 'active' : '' ?>" href="investimentos.php">
                            <i class="fas fa-chart-pie"></i>
                            <span>Investimentos</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="user-section mt-auto">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle fa-2x"></i>
                    </div>
                    <div class="user-details">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="user-name mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                            <div class="d-flex gap-2">
                                <!-- Sino de Notificações -->
                                <div class="dropdown">
                                    <button class="btn btn-notification p-1" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell"></i>
                                        <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" id="notificationList">
                                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                                            <span>Notificações</span>
                                            <button class="btn btn-sm btn-link p-0" onclick="markAllRead()" style="font-size: 0.75rem;">Marcar todas como lidas</button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="text-center py-3 text-muted" id="emptyNotifications">
                                            <i class="fas fa-bell-slash"></i><br>
                                            Sem notificações
                                        </li>
                                    </ul>
                                </div>
                                <a href="logout.php" class="btn-sair">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Backdrop para mobile -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Conteúdo Principal -->
        <div class="main-content">
<?php else: ?>
    <?php if($current_page === 'index.php'): ?>

    <?php elseif(!isset($_SESSION['user_id'])): ?>
        <div class="position-absolute start-0 top-0 p-4">
            <a href="index.php" class="btn btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <style>
        .btn-voltar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: #ffd700;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
            transition: all 0.3s ease;
        }
        .btn-voltar:hover {
            color: #ffd700;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
        }
        </style>

    <?php endif; ?>
<?php endif; ?>
<main<?= isset($_SESSION['user_id']) && $current_page !== 'index.php' ? '' : ' class="container py-4"' ?>>