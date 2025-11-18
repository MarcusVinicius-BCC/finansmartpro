document.addEventListener('DOMContentLoaded', function () {
    const loginButton = document.querySelector('a[href="login.php"]');

    function handleNavigation(event, targetUrl) {
        event.preventDefault();

        const heroSection = document.querySelector('.hero-section');
        const featuresSection = document.querySelector('.features-section');

        // Adiciona a transição CSS
        heroSection.style.transition = 'opacity 0.3s ease';
        featuresSection.style.transition = 'opacity 0.3s ease';

        // Inicia o fade out
        heroSection.style.opacity = '0';
        featuresSection.style.opacity = '0';

        // Redireciona após a animação
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 300);
    }

    if (loginButton) {
        loginButton.addEventListener('click', (e) => handleNavigation(e, 'login.php'));
    }
});