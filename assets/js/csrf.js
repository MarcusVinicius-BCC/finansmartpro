/**
 * FinanSmart Pro - CSRF Token Helper
 * Adiciona automaticamente tokens CSRF em todos os formulários POST
 */

document.addEventListener('DOMContentLoaded', function () {
    // Obter todos os formulários POST
    const forms = document.querySelectorAll('form[method="post"], form[method="POST"]');

    forms.forEach(form => {
        // Verificar se já tem campo CSRF
        const hasCSRF = form.querySelector('input[name="csrf_token"]');

        if (!hasCSRF) {
            // Criar campo CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = getCSRFToken();

            // Adicionar no início do formulário
            form.insertBefore(csrfInput, form.firstChild);
        }
    });
});

/**
 * Obtém o token CSRF da sessão PHP via AJAX
 * Fallback: extrai de meta tag ou formulário existente
 */
function getCSRFToken() {
    // Tentar pegar de meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }

    // Tentar pegar de formulário existente
    const existingToken = document.querySelector('input[name="csrf_token"]');
    if (existingToken) {
        return existingToken.value;
    }

    // Gerar novo token via PHP (fallback)
    return generateCSRFTokenSync();
}

/**
 * Gera token CSRF síncrono via requisição AJAX
 */
function generateCSRFTokenSync() {
    let token = '';

    // Criar requisição síncrona (depreciada mas funciona para fallback)
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'api/get_csrf_token.php', false); // false = síncrono
    xhr.send();

    if (xhr.status === 200) {
        try {
            const response = JSON.parse(xhr.responseText);
            token = response.token || '';
        } catch (e) {
            console.error('Erro ao obter CSRF token:', e);
        }
    }

    return token;
}

/**
 * Atualiza todos os tokens CSRF da página
 * Útil para SPAs ou páginas de longa duração
 */
function refreshAllCSRFTokens() {
    const newToken = generateCSRFTokenSync();

    if (newToken) {
        // Atualizar meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }

        // Atualizar todos os campos CSRF
        const csrfInputs = document.querySelectorAll('input[name="csrf_token"]');
        csrfInputs.forEach(input => {
            input.value = newToken;
        });
    }
}

// Atualizar tokens a cada 30 minutos (sincronizado com regeneração de sessão)
setInterval(refreshAllCSRFTokens, 30 * 60 * 1000);
