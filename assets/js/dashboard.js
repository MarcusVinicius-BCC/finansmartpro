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
                        html += `
                            <div class="rate-item">
                                <span class="currency">${currency}/BRL</span>
                                <span class="value">${parseFloat(data[currency]).toFixed(2)}</span>
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

// Format currency values
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

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