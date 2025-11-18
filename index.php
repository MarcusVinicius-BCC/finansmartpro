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
    <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>