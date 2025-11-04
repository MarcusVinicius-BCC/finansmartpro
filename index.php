<?php
require 'includes/db.php';
session_start();
require 'includes/header.php';
?>
<div class="container mt-4">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Dashboard para usuários logados -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Saldo Total</h5>
                    <h2 class="card-text" id="total-balance">R$ 0,00</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Receitas do Mês</h5>
                    <h2 class="card-text" id="monthly-income">R$ 0,00</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Despesas do Mês</h5>
                    <h2 class="card-text" id="monthly-expenses">R$ 0,00</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Saúde Financeira -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="health-score-container">
                <div id="health-score">
                    <div class="loading-spinner"></div>
                </div>
                <div id="health-metrics" class="health-metrics"></div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Análises -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="chart-container">
                <h5 class="chart-title">Fluxo de Caixa</h5>
                <canvas id="cashflow-chart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5 class="chart-title">Distribuição de Despesas</h5>
                <canvas id="expense-distribution-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Orçamentos e Previsões -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Orçamentos</h5>
                </div>
                <div class="card-body">
                    <div id="budget-overview"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Previsões</h5>
                </div>
                <div class="card-body">
                    <div id="expense-predictions"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progresso das Metas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="chart-container">
                <h5 class="chart-title">Progresso da Poupança</h5>
                <canvas id="savings-progress-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Lançamento Rápido</h5>
                </div>
                <div class="card-body">
                    <form id="quick-transaction-form">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="descricao" placeholder="Descrição" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <input type="number" class="form-control" name="valor" placeholder="Valor" step="0.01" required>
                            </div>
                            <div class="col">
                                <select class="form-control" name="tipo" required>
                                    <option value="receita">Receita</option>
                                    <option value="despesa">Despesa</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <select class="form-control" name="categoria" required>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, nome, tipo FROM categorias ORDER BY tipo, nome");
                                    while ($categoria = $stmt->fetch()) {
                                        echo "<option value='{$categoria['id']}'>{$categoria['nome']} ({$categoria['tipo']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col">
                                <input type="date" class="form-control" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cotações</h5>
                </div>
                <div class="card-body">
                    <div id="exchange-rates">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Hero Section para visitantes -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <img src="assets/img/mockup.png" alt="FinanSmart Pro Logo" class="hero-logo">
                    <h1 class="display-4 fw-bold">Transforme Sua Vida Financeira</h1>
                    <p class="lead">O FinanSmart Pro é a solução completa para você organizar suas finanças, alcançar seus objetivos e construir um futuro próspero. Comece sua jornada para a liberdade financeira hoje mesmo.</p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-outline-light btn-lg">Começar Agora</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-chart-pie fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title">Dashboard Inteligente</h5>
                            <p class="card-text">Visualize suas finanças de forma clara e objetiva com gráficos interativos e análises em tempo real. Tome decisões informadas com dados precisos.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-exchange-alt fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title">Múltiplas Moedas</h5>
                            <p class="card-text">Gerencie investimentos internacionais e acompanhe cotações em tempo real. Conversão automática para sua moeda principal.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-piggy-bank fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title">Metas Inteligentes</h5>
                            <p class="card-text">Estabeleça objetivos financeiros, acompanhe seu progresso e receba insights personalizados para alcançar suas metas mais rapidamente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>