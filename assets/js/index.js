document.addEventListener('DOMContentLoaded', function () {
    const loginButton = document.querySelector('a[href="login.php"]');
    const registerBtn = document.getElementById('registerBtn');

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

    // Botão "Começar Grátis" - redireciona para login com indicador
    if (registerBtn) {
        registerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Salvar flag para mostrar formulário de cadastro
            sessionStorage.setItem('showRegister', 'true');
            handleNavigation(e, 'login.php');
        });
    }

    // ===== ANIMAÇÕES =====

    // Animação de contagem de números
    const animateCount = (element) => {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60fps
        let current = 0;

        const updateCount = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current).toLocaleString('pt-BR');
                requestAnimationFrame(updateCount);
            } else {
                element.textContent = target.toLocaleString('pt-BR');
            }
        };

        updateCount();
    };

    // Intersection Observer para iniciar animação quando visível
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const numbers = entry.target.querySelectorAll('.stat-number');
                numbers.forEach(num => {
                    if (!num.classList.contains('animated')) {
                        num.classList.add('animated');
                        animateCount(num);
                    }
                });
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observar seção de estatísticas
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        observer.observe(statsSection);
    }

    // Scroll suave para âncoras
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#cadastro') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Animação de fade-in ao scroll
    const fadeElements = document.querySelectorAll('.feature-card, .step-card, .testimonial-card');

    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);

                fadeObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    fadeElements.forEach(el => fadeObserver.observe(el));
});