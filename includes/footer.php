    </main>
    
<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Mostrar footer apenas na página inicial e páginas de autenticação
if (in_array($current_page, ['index.php', 'login.php', 'forgot_password.php', 'reset_password.php'])):
?>
    <footer class="footer mt-auto py-4">
        <div class="container">
            <div class="row">
                <!-- Sobre -->
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-primary mb-3">FinanSmart Pro</h5>
                    <p class="text-muted">Sua plataforma completa para gestão financeira pessoal. Controle seus gastos, acompanhe investimentos e alcance suas metas financeiras.</p>
                </div>

                <!-- Links Rápidos -->
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-primary mb-3">Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="dashboard.php" class="text-muted text-decoration-none">
                                <i class="fas fa-chart-line me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="lancamentos.php" class="text-muted text-decoration-none">
                                <i class="fas fa-exchange-alt me-2"></i>Lançamentos
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="metas.php" class="text-muted text-decoration-none">
                                <i class="fas fa-bullseye me-2"></i>Metas
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="investimentos.php" class="text-muted text-decoration-none">
                                <i class="fas fa-chart-pie me-2"></i>Investimentos
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Redes Sociais e Contato -->
                <div class="col-md-4">
                    <h5 class="text-primary mb-3">Conecte-se Conosco</h5>
                    <div class="social-links mb-3">
                        <a href="https://facebook.com/finansmartpro" class="text-muted me-3" target="_blank" title="Facebook">
                            <i class="fab fa-facebook fa-2x"></i>
                        </a>
                        <a href="https://instagram.com/finansmartpro" class="text-muted me-3" target="_blank" title="Instagram">
                            <i class="fab fa-instagram fa-2x"></i>
                        </a>
                        <a href="https://twitter.com/finansmartpro" class="text-muted me-3" target="_blank" title="Twitter">
                            <i class="fab fa-twitter fa-2x"></i>
                        </a>
                        <a href="https://linkedin.com/company/finansmartpro" class="text-muted" target="_blank" title="LinkedIn">
                            <i class="fab fa-linkedin fa-2x"></i>
                        </a>
                    </div>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:contato@finansmartpro.com" class="text-muted text-decoration-none">contato@finansmartpro.com</a>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-phone me-2"></i>
                        <a href="tel:+5511999999999" class="text-muted text-decoration-none">(11) 99999-9999</a>
                    </p>
                </div>
            </div>

            <!-- Copyright -->
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="text-muted mb-0">&copy; <?= date('Y') ?> FinanSmart Pro. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="#" class="text-muted text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="list-inline-item">
                            <span class="text-muted">|</span>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted text-decoration-none">Política de Privacidade</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <style>
    .footer {
        background-color: #6a0dad;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
    }

    .footer h5 {
        color: white !important;
    }

    .footer .text-muted,
    .footer a {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .footer .social-links a:hover,
    .footer a:hover {
        color: white !important;
        opacity: 0.9;
    }

    .footer hr {
        border-color: rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 767.98px) {
        .footer {
            text-align: center;
        }
        
        .footer .social-links {
            justify-content: center;
            display: flex;
        }
    }
    </style>
<?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>