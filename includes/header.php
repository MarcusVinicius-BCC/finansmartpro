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
        // Carregar moeda do usuário para o seletor (quando logado)
        $user_base = 'BRL';
        if (isset($_SESSION['user_id'])) {
            // include db apenas se disponível
            if (!isset($pdo)) {
                require_once __DIR__ . '/db.php';
            }
            $stmtBase = $pdo->prepare('SELECT moeda_base FROM usuarios WHERE id = ?');
            $stmtBase->execute([$_SESSION['user_id']]);
            $rowBase = $stmtBase->fetch();
            $user_base = $rowBase['moeda_base'] ?? 'BRL';
        }
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

    <script>
        // Expor configuração global do front-end (moeda do usuário)
        window.FINANSMART = window.FINANSMART || {};
        window.FINANSMART.userBase = '<?= isset($user_base) ? $user_base : 'BRL' ?>';
    </script>


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
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'categorias.php' ? 'active' : '' ?>" href="categorias.php">
                            <i class="fas fa-tags"></i>
                            <span>Categorias</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'cartoes.php' ? 'active' : '' ?>" href="cartoes.php">
                            <i class="fas fa-credit-card"></i>
                            <span>Cartões</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'contas.php' ? 'active' : '' ?>" href="contas.php">
                            <i class="fas fa-university"></i>
                            <span>Contas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'recorrentes.php' ? 'active' : '' ?>" href="recorrentes.php">
                            <i class="fas fa-sync-alt"></i>
                            <span>Recorrentes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'lembretes.php' ? 'active' : '' ?>" href="lembretes.php">
                            <i class="fas fa-bell"></i>
                            <span>Lembretes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'planejamento.php' ? 'active' : '' ?>" href="planejamento.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Planejamento</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'importar.php' ? 'active' : '' ?>" href="importar.php">
                            <i class="fas fa-file-import"></i>
                            <span>Importar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'contas_pagar_receber.php' ? 'active' : '' ?>" href="contas_pagar_receber.php">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Pagar/Receber</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'conciliacao.php' ? 'active' : '' ?>" href="conciliacao.php">
                            <i class="fas fa-balance-scale"></i>
                            <span>Conciliação</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'backup.php' ? 'active' : '' ?>" href="backup.php">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'familia.php' ? 'active' : '' ?>" href="familia.php">
                            <i class="fas fa-users"></i>
                            <span>Família</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'anexos.php' ? 'active' : '' ?>" href="anexos.php">
                            <i class="fas fa-paperclip"></i>
                            <span>Anexos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'calendario.php' ? 'active' : '' ?>" href="calendario.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendário</span>
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
                <div class="mt-3 px-3">
                    <div class="currency-selector">
                        <button type="button" class="currency-current" id="currencyToggle" aria-label="Alternar moeda" aria-expanded="false">
                            <i class="fas fa-exchange-alt"></i>
                            <span class="currency-symbol"><?= $user_base === 'BRL' ? 'R$' : ($user_base === 'USD' ? '$' : '€') ?></span>
                            <span class="currency-code"><?= $user_base ?></span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </button>
                        <form method="post" action="set_currency.php" class="currency-dropdown" id="currencyDropdown">
                            <button type="submit" name="moeda_base" value="BRL" class="currency-option <?= $user_base === 'BRL' ? 'active' : '' ?>">
                                <span class="currency-symbol">R$</span>
                                <span class="currency-code">BRL</span>
                                <span class="currency-name">Real</span>
                            </button>
                            <button type="submit" name="moeda_base" value="USD" class="currency-option <?= $user_base === 'USD' ? 'active' : '' ?>">
                                <span class="currency-symbol">$</span>
                                <span class="currency-code">USD</span>
                                <span class="currency-name">Dólar</span>
                            </button>
                            <button type="submit" name="moeda_base" value="EUR" class="currency-option <?= $user_base === 'EUR' ? 'active' : '' ?>">
                                <span class="currency-symbol">€</span>
                                <span class="currency-code">EUR</span>
                                <span class="currency-name">Euro</span>
                            </button>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        </form>
                    </div>
                    <script>
                    document.getElementById('currencyToggle').addEventListener('click', function() {
                        const dropdown = document.getElementById('currencyDropdown');
                        const isOpen = dropdown.classList.toggle('show');
                        this.setAttribute('aria-expanded', isOpen);
                        this.querySelector('.fa-chevron-down').style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                    });
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.currency-selector')) {
                            const dropdown = document.getElementById('currencyDropdown');
                            const toggle = document.getElementById('currencyToggle');
                            dropdown.classList.remove('show');
                            toggle.setAttribute('aria-expanded', 'false');
                            toggle.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
                        }
                    });
                    </script>
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