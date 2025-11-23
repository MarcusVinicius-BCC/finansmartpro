// Update exchange rates
function updateExchangeRates() {
    fetch('/api/conversao.php')
        .then(response => response.json())
        .then(data => {
            const ratesContainer = document.getElementById('exchange-rates');
            if (ratesContainer) {
                let html = '<div class="rate-grid">';
                ['USD', 'EUR', 'GBP'].forEach(currency => {
                    if (data[currency]) {
                        const val = parseFloat(data[currency]);
                        html += `
                                    <div class="rate-item">
                                        <span class="currency">${currency}/BRL</span>
                                        <span class="value">${formatCurrency(val, 'BRL')}</span>
                                    </div>`;
                    }
                });
                html += '</div>';
                ratesContainer.innerHTML = html;
            }
        })
        .catch(error => console.error('Error fetching exchange rates:', error));
}

// Quick transaction form handling
function handleQuickTransaction(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Adicionar action ao FormData
    formData.append('action', 'add');

    fetch('lancamentos.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                form.reset();
                alert('Transação registrada com sucesso!');
                updateDashboardSummary();
            } else {
                alert('Erro ao registrar transação: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update dashboard summary
function updateDashboardSummary() {
    fetch('/api/dashboard_summary.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-balance').textContent = formatCurrency(data.balance);
            document.getElementById('monthly-income').textContent = formatCurrency(data.income);
            document.getElementById('monthly-expenses').textContent = formatCurrency(data.expenses);
        })
        .catch(error => console.error('Error updating summary:', error));
}

// Format currency values (global)
function formatCurrency(value, currency) {
    // Allow passing explicit currency or fallback to user base
    const userBase = (window.FINANSMART && window.FINANSMART.userBase) ? window.FINANSMART.userBase : 'BRL';
    const curr = currency || userBase || 'BRL';
    try {
        return new Intl.NumberFormat(curr === 'BRL' ? 'pt-BR' : 'en-US', {
            style: 'currency',
            currency: curr
        }).format(Number(value));
    } catch (e) {
        // Fallback simple formatting
        return (Number(value) || 0).toFixed(2) + ' ' + curr;
    }
}

// Expose globally for other modules (analytics.js uses formatCurrency)
window.formatCurrency = formatCurrency;

// Initialize dashboard features
document.addEventListener('DOMContentLoaded', function () {
    updateExchangeRates();
    setInterval(updateExchangeRates, 300000); // Update every 5 minutes

    const quickTransactionForm = document.getElementById('quick-transaction-form');
    if (quickTransactionForm) {
        quickTransactionForm.addEventListener('submit', handleQuickTransaction);
    }

    if (document.getElementById('total-balance')) {
        updateDashboardSummary();
    }
});