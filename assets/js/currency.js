
document.addEventListener('DOMContentLoaded', function () {
    const convertButton = document.getElementById('convertCurrency');
    if (convertButton) {
        convertButton.addEventListener('click', function () {
            const amount = document.getElementById('converter_amount').value;
            const from = document.getElementById('converter_from').value;
            const to = document.getElementById('converter_to').value;
            const resultDiv = document.getElementById('converter_result');

            if (!amount || !from || !to) {
                resultDiv.innerHTML = '<span class="text-danger">Preencha todos os campos.</span>';
                return;
            }

            resultDiv.innerHTML = '<span class="text-info">Convertendo...</span>';

            fetch(`/api/conversao.php?amount=${amount}&from=${from}&to=${to}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultDiv.innerHTML = `<span class="text-danger">Erro: ${data.error}</span>`;
                    } else {
                        resultDiv.innerHTML = `<span class="text-success">${amount} ${from} = ${data.result} ${to}</span>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<span class="text-danger">Ocorreu um erro na conversão.</span>';
                });
        });
    }
});
