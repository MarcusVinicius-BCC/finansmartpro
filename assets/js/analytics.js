// Advanced Analytics Module
class FinancialAnalytics {
    constructor() {
        this.charts = {};
        this.data = {};
    }

    async initialize() {
        await Promise.all([
            this.fetchFinancialHealth(),
            this.fetchTransactionHistory(),
            this.fetchPredictions()
        ]);
        this.renderDashboardCharts();
    }

    async fetchFinancialHealth() {
        try {
            const response = await fetch('/api/ml_service.php?action=health');
            this.data.health = await response.json();
            this.updateHealthScore();
        } catch (error) {
            console.error('Error fetching financial health:', error);
        }
    }

    async fetchTransactionHistory() {
        try {
            const response = await fetch('/api/dashboard_summary.php?action=history');
            this.data.history = await response.json();
        } catch (error) {
            console.error('Error fetching transaction history:', error);
        }
    }

    async fetchPredictions() {
        try {
            const response = await fetch('/api/ml_service.php?action=predict');
            this.data.predictions = await response.json();
            this.updatePredictions();
        } catch (error) {
            console.error('Error fetching predictions:', error);
        }
    }

    updateHealthScore() {
        const healthData = this.data.health?.data;
        if (!healthData) return;

        const scoreElement = document.getElementById('health-score');
        if (scoreElement) {
            scoreElement.innerHTML = `
                <div class="health-score-value">${healthData.score}</div>
                <div class="health-score-label">Pontuação de Saúde Financeira</div>
            `;
        }

        // Update metrics
        const metricsContainer = document.getElementById('health-metrics');
        if (metricsContainer && healthData.metrics) {
            metricsContainer.innerHTML = `
                <div class="metric">
                    <div class="metric-value">${healthData.metrics.savings_rate.toFixed(1)}%</div>
                    <div class="metric-label">Taxa de Poupança</div>
                </div>
                <div class="metric">
                    <div class="metric-value">${healthData.metrics.expense_stability.toFixed(1)}%</div>
                    <div class="metric-label">Estabilidade de Despesas</div>
                </div>
                <div class="metric">
                    <div class="metric-value">${healthData.metrics.income_growth.toFixed(1)}%</div>
                    <div class="metric-label">Crescimento da Renda</div>
                </div>
            `;
        }
    }

    updatePredictions() {
        const predictions = this.data.predictions?.data;
        if (!predictions) return;

        const container = document.getElementById('expense-predictions');
        if (container) {
            let html = '<h5>Previsão de Despesas (Próximo Mês)</h5><div class="predictions-grid">';
            Object.entries(predictions).forEach(([categoryId, amount]) => {
                const category = this.getCategoryName(categoryId);
                html += `
                    <div class="prediction-item">
                        <div class="category">${category}</div>
                        <div class="amount">${formatCurrency(amount)}</div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    }

    renderDashboardCharts() {
        this.renderCashFlowChart();
        this.renderExpenseDistributionChart();
        this.renderSavingsProgressChart();
    }

    renderCashFlowChart() {
        const ctx = document.getElementById('cashflow-chart');
        if (!ctx || !this.data.history?.data) return;

        const data = this.data.history.data;
        this.charts.cashFlow = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [
                    {
                        label: 'Receitas',
                        data: data.map(d => d.income),
                        borderColor: 'rgba(40, 167, 69, 0.8)',
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Despesas',
                        data: data.map(d => d.expenses),
                        borderColor: 'rgba(220, 53, 69, 0.8)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Fluxo de Caixa Mensal'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatCurrency(value)
                        }
                    }
                }
            }
        });
    }

    renderExpenseDistributionChart() {
        const ctx = document.getElementById('expense-distribution-chart');
        if (!ctx || !this.data.history?.data) return;

        const expensesByCategory = this.calculateExpensesByCategory();
        this.charts.expenseDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(expensesByCategory),
                datasets: [{
                    data: Object.values(expensesByCategory),
                    backgroundColor: this.generateColors(Object.keys(expensesByCategory).length)
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição de Despesas'
                    },
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    renderSavingsProgressChart() {
        const ctx = document.getElementById('savings-progress-chart');
        if (!ctx || !this.data.history?.data) return;

        const savingsData = this.calculateSavingsProgress();
        this.charts.savingsProgress = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: savingsData.map(d => d.month),
                datasets: [{
                    label: 'Economia Mensal',
                    data: savingsData.map(d => d.savings),
                    backgroundColor: 'rgba(13, 110, 253, 0.5)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Progresso da Poupança'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatCurrency(value)
                        }
                    }
                }
            }
        });
    }

    calculateExpensesByCategory() {
        // Implementation for expense distribution calculation
        return {};
    }

    calculateSavingsProgress() {
        // Implementation for savings progress calculation
        return [];
    }

    generateColors(count) {
        const colors = [
            '#4CAF50', '#2196F3', '#FFC107', '#E91E63', '#9C27B0',
            '#00BCD4', '#FF5722', '#795548', '#607D8B', '#3F51B5'
        ];
        return Array(count).fill().map((_, i) => colors[i % colors.length]);
    }

    getCategoryName(categoryId) {
        // Implementation to fetch category name from cache or DOM
        return `Categoria ${categoryId}`;
    }
}

// Budget Management Module
class BudgetManager {
    constructor() {
        this.budgets = {};
        this.alerts = [];
    }

    async initialize() {
        await this.loadBudgets();
        this.setupEventListeners();
        this.startMonitoring();
    }

    async loadBudgets() {
        try {
            const response = await fetch('/api/budgets.php');
            this.budgets = await response.json();
            this.renderBudgets();
        } catch (error) {
            console.error('Error loading budgets:', error);
        }
    }

    renderBudgets() {
        const container = document.getElementById('budget-overview');
        if (!container) return;

        let html = '<div class="budget-grid">';
        Object.entries(this.budgets).forEach(([category, budget]) => {
            const progress = (budget.spent / budget.limit) * 100;
            const status = this.getBudgetStatus(progress);

            html += `
                <div class="budget-item ${status}">
                    <div class="budget-header">
                        <h6>${budget.category}</h6>
                        <span class="budget-amount">${formatCurrency(budget.spent)} / ${formatCurrency(budget.limit)}</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-${status}" 
                             role="progressbar" 
                             style="width: ${progress}%" 
                             aria-valuenow="${progress}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    getBudgetStatus(progress) {
        if (progress >= 90) return 'danger';
        if (progress >= 75) return 'warning';
        return 'success';
    }

    setupEventListeners() {
        // Event listeners for budget management UI
    }

    startMonitoring() {
        // Start periodic budget monitoring
        setInterval(() => this.checkBudgetAlerts(), 3600000); // Check every hour
    }

    async checkBudgetAlerts() {
        // Implementation for budget monitoring and alerts
    }
}

// Portfolio Tracking Module
class PortfolioTracker {
    constructor() {
        this.portfolio = {};
        this.marketData = {};
    }

    async initialize() {
        await Promise.all([
            this.loadPortfolio(),
            this.fetchMarketData()
        ]);
        this.renderPortfolio();
        this.startTracking();
    }

    async loadPortfolio() {
        // Implementation for loading portfolio data
    }

    async fetchMarketData() {
        // Implementation for fetching market data
    }

    renderPortfolio() {
        // Implementation for rendering portfolio view
    }

    startTracking() {
        // Start real-time portfolio tracking
        setInterval(() => this.updateMarketData(), 300000); // Update every 5 minutes
    }

    calculateReturns() {
        // Implementation for calculating portfolio returns
    }
}

// Initialize all modules when document is ready
document.addEventListener('DOMContentLoaded', function () {
    const analytics = new FinancialAnalytics();
    const budgetManager = new BudgetManager();
    const portfolioTracker = new PortfolioTracker();

    analytics.initialize();
    budgetManager.initialize();
    portfolioTracker.initialize();

    // Initialize existing dashboard features
    updateExchangeRates();
    setInterval(updateExchangeRates, 300000);

    const quickTransactionForm = document.getElementById('quick-transaction-form');
    if (quickTransactionForm) {
        quickTransactionForm.addEventListener('submit', handleQuickTransaction);
    }

    if (document.getElementById('total-balance')) {
        updateDashboardSummary();
    }
});