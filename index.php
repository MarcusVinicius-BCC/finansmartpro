<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require 'includes/db.php';
require 'includes/header.php';
?>
<div class="container mt-4">
    <!-- Hero Section para não logados -->
    <div class="hero-section text-center">
        <div class="container">
            <img src="assets/img/mockup.png" alt="FinanSmart Pro Logo" class="hero-logo">
            <h1 class="display-4">Controle suas finanças com inteligência</h1>
            <p class="lead">O FinanSmart Pro ajuda você a organizar suas despesas, criar metas de economia e visualizar seu progresso financeiro de forma simples e intuitiva.</p>
            <a href="login.php" class="btn btn-outline-light btn-lg">Fazer Login</a>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-chart-pie fa-2x"></i></div>
                            <h5 class="card-title">Análise de Despesas</h5>
                            <p class="card-text">Categorize seus gastos e entenda para onde seu dinheiro está indo com gráficos detalhados.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-bullseye fa-2x"></i></div>
                            <h5 class="card-title">Metas de Economia</h5>
                            <p class="card-text">Defina objetivos financeiros e acompanhe seu progresso para alcançá-los mais rápido.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-chart-line fa-2x"></i></div>
                            <h5 class="card-title">Investimentos</h5>
                            <p class="card-text">Monitore o desempenho de seus investimentos e tome decisões mais informadas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>