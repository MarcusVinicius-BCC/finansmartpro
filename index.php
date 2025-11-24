<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require 'includes/db.php';
require 'includes/header.php';
?>
<div class="container mt-4">
    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <img src="assets/img/mockup.png" alt="FinanSmart Pro Logo" class="hero-logo animate-fade-in">
            <h1 class="display-4 animate-slide-up">Controle suas finanças com inteligência</h1>
            <p class="lead animate-slide-up-delay-1">O FinanSmart Pro ajuda você a organizar suas despesas, criar metas de economia e visualizar seu progresso financeiro de forma simples e intuitiva.</p>
            <div class="hero-buttons animate-slide-up-delay-2">
                <a href="login.php" class="btn btn-outline-light btn-lg me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                </a>
                <a href="login.php" class="btn btn-light btn-lg" id="registerBtn">
                    <i class="fas fa-user-plus me-2"></i>Começar Grátis
                </a>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card animate-count">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <h3 class="stat-number" data-target="10000">0</h3>
                        <p class="stat-label">Usuários Ativos</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card animate-count">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <h3 class="stat-number" data-target="1000000">0</h3>
                        <p class="stat-label">Lançamentos</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card animate-count">
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        <h3 class="stat-number" data-target="95">0</h3>
                        <p class="stat-label">% Satisfação</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card animate-count">
                        <div class="stat-icon"><i class="fas fa-piggy-bank"></i></div>
                        <h3 class="stat-number" data-target="500000">0</h3>
                        <p class="stat-label">Economizados (R$)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section com Carousel -->
    <div class="features-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title" style="font-family: 'Poppins', sans-serif; font-weight: 700;">Recursos Principais</h2>
            
            <!-- Carousel Mobile -->
            <div id="featuresCarousel" class="carousel slide d-md-none mb-4" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="2"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="3"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="4"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="5"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-chart-pie fa-2x"></i></div>
                                <h5 class="card-title">Análise de Despesas</h5>
                                <p class="card-text">Categorize seus gastos e entenda para onde seu dinheiro está indo com gráficos detalhados.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-bullseye fa-2x"></i></div>
                                <h5 class="card-title">Metas de Economia</h5>
                                <p class="card-text">Defina objetivos financeiros e acompanhe seu progresso para alcançá-los mais rápido.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-chart-line fa-2x"></i></div>
                                <h5 class="card-title">Investimentos</h5>
                                <p class="card-text">Monitore o desempenho de seus investimentos e tome decisões mais informadas.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-mobile-alt fa-2x"></i></div>
                                <h5 class="card-title">100% Responsivo</h5>
                                <p class="card-text">Acesse de qualquer dispositivo - computador, tablet ou celular.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-shield-alt fa-2x"></i></div>
                                <h5 class="card-title">Segurança Total</h5>
                                <p class="card-text">Seus dados protegidos com criptografia e proteção CSRF.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="card feature-card mx-3">
                            <div class="card-body">
                                <div class="feature-icon"><i class="fas fa-file-excel fa-2x"></i></div>
                                <h5 class="card-title">Exportação PDF/Excel</h5>
                                <p class="card-text">Exporte relatórios completos em PDF ou Excel para análise offline.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#featuresCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#featuresCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
            
            <!-- Grid Desktop -->
            <div class="row text-center d-none d-md-flex">
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
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-mobile-alt fa-2x"></i></div>
                            <h5 class="card-title">100% Responsivo</h5>
                            <p class="card-text">Acesse de qualquer dispositivo - computador, tablet ou celular.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-shield-alt fa-2x"></i></div>
                            <h5 class="card-title">Segurança Total</h5>
                            <p class="card-text">Seus dados protegidos com criptografia e proteção CSRF.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body">
                            <div class="feature-icon"><i class="fas fa-file-excel fa-2x"></i></div>
                            <h5 class="card-title">Exportação PDF/Excel</h5>
                            <p class="card-text">Exporte relatórios completos em PDF ou Excel para análise offline.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Como Funciona -->
    <div class="how-it-works-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title" style="font-family: 'Montserrat', sans-serif; font-weight: 800;">Como Funciona</h2>
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-icon"><i class="fas fa-user-plus fa-3x"></i></div>
                        <h5 class="mt-3">Cadastre-se Grátis</h5>
                        <p>Crie sua conta em menos de 1 minuto</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-icon"><i class="fas fa-plus-circle fa-3x"></i></div>
                        <h5 class="mt-3">Adicione Despesas</h5>
                        <p>Registre seus gastos e receitas facilmente</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-icon"><i class="fas fa-chart-bar fa-3x"></i></div>
                        <h5 class="mt-3">Analise Dados</h5>
                        <p>Visualize gráficos e relatórios inteligentes</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="step-icon"><i class="fas fa-trophy fa-3x"></i></div>
                        <h5 class="mt-3">Atinja Metas</h5>
                        <p>Alcance seus objetivos financeiros</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Screenshot Section -->
    <div class="screenshot-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title" style="font-family: 'Raleway', sans-serif; font-weight: 600;">Veja o Sistema em Ação</h2>
            <div class="row align-items-center">
                <div class="col-md-6 mb-4">
                    <div class="screenshot-mockup video-container" id="videoContainer">
                        <video autoplay loop muted playsinline class="demo-video" id="demoVideo">
                            <source src="assets/video/demo.mp4" type="video/mp4">
                            <source src="assets/video/demo.webm" type="video/webm">
                            <!-- Fallback para navegadores sem suporte a vídeo -->
                            <div class="video-fallback">
                                <i class="fas fa-desktop fa-10x text-white opacity-50"></i>
                                <p class="mt-3 text-white">Dashboard Intuitivo</p>
                            </div>
                        </video>
                        <div class="video-overlay">
                            <div class="play-indicator">
                                <i class="fas fa-play-circle fa-3x"></i>
                            </div>
                        </div>
                    </div>
                    <script>
                        // Remover loading quando vídeo carregar
                        const video = document.getElementById('demoVideo');
                        const container = document.getElementById('videoContainer');
                        
                        video.addEventListener('loadeddata', function() {
                            container.classList.add('loaded');
                        });
                        
                        // Fallback: se vídeo não existir, mostrar ícone
                        video.addEventListener('error', function() {
                            container.innerHTML = '<div class="video-fallback" style="padding: 3rem;"><i class="fas fa-desktop fa-10x text-white opacity-50"></i><p class="mt-3 text-white">Dashboard Intuitivo</p></div>';
                            container.classList.add('loaded');
                        });
                    </script>
                </div>
                <div class="col-md-6">
                    <h3 class="text-white mb-4">Interface Moderna e Intuitiva</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle text-success me-2"></i> Dashboard com métricas em tempo real</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Gráficos interativos e coloridos</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Navegação simples e rápida</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Tema claro/escuro disponível</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Notificações inteligentes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Depoimentos -->
    <div class="testimonials-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title" style="font-family: 'Nunito', sans-serif; font-weight: 700; font-style: italic;">O que nossos usuários dizem</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Consegui economizar 30% a mais depois que comecei a usar o FinanSmart Pro. Os gráficos me ajudaram a entender meus gastos!"</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Maria+Silva&background=6a0dad&color=fff" alt="Maria Silva" class="testimonial-avatar">
                            <div>
                                <strong>Maria Silva</strong>
                                <p class="text-muted mb-0">Designer</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Interface super intuitiva! Em poucos minutos já estava cadastrando minhas despesas e criando metas. Recomendo!"</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=João+Santos&background=6a0dad&color=fff" alt="João Santos" class="testimonial-avatar">
                            <div>
                                <strong>João Santos</strong>
                                <p class="text-muted mb-0">Desenvolvedor</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Melhor sistema de finanças pessoais que já usei. O recurso de investimentos é perfeito para acompanhar minha carteira!"</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Ana+Costa&background=6a0dad&color=fff" alt="Ana Costa" class="testimonial-avatar">
                            <div>
                                <strong>Ana Costa</strong>
                                <p class="text-muted mb-0">Analista Financeira</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="faq-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title" style="font-family: 'Lato', sans-serif; font-weight: 900;">Perguntas Frequentes</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-question-circle me-2"></i> O FinanSmart Pro é gratuito?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! O FinanSmart Pro é 100% gratuito. Você tem acesso a todos os recursos sem custo algum.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-question-circle me-2"></i> Meus dados estão seguros?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutamente! Utilizamos criptografia de ponta e proteção CSRF. Seus dados são armazenados com segurança e nunca compartilhados com terceiros.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-question-circle me-2"></i> Posso acessar pelo celular?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! O sistema é 100% responsivo e funciona perfeitamente em smartphones, tablets e computadores.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-question-circle me-2"></i> Posso exportar meus relatórios?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sim! Você pode exportar relatórios completos em PDF ou Excel sempre que precisar.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    <i class="fas fa-question-circle me-2"></i> Como recupero minha senha?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Na tela de login, clique em "Esqueceu sua senha?" e siga as instruções. Você receberá um email com o link para redefinir sua senha.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Final -->
    <div class="cta-section text-center">
        <div class="container">
            <h2 class="display-5 mb-4" style="font-family: 'Bebas Neue', sans-serif; letter-spacing: 2px;">Pronto para Transformar suas Finanças?</h2>
            <p class="lead mb-4" style="font-family: 'Open Sans', sans-serif;">Junte-se a milhares de usuários que já estão no controle de seu dinheiro</p>
            <a href="login.php#cadastro" class="btn btn-light btn-lg">
                <i class="fas fa-rocket me-2"></i>Começar Agora - É Grátis!
            </a>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>