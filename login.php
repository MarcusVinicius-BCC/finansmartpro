<?php
require 'includes/db.php';
session_start();

$errors = [];
$reg_errors = [];

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        
        if (!$email) $errors[] = 'Email inválido.';
        if (empty($senha)) $errors[] = 'Senha requerida.';
        
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id, senha, nome FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Credenciais inválidas.';
            }
        }
    }
    // Processar registro
    elseif ($_POST['action'] === 'register') {
        $nome = trim($_POST['nome'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // Validações
        if (empty($nome)) {
            $reg_errors[] = 'Nome é obrigatório.';
        }

        if (!$email) {
            $reg_errors[] = 'Email inválido.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $reg_errors[] = 'Este email já está em uso.';
            }
        }

        if (strlen($senha) < 6) {
            $reg_errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        }

        if ($senha !== $confirmar_senha) {
            $reg_errors[] = 'As senhas não coincidem.';
        }

        if (empty($reg_errors)) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
                $stmt->execute([$nome, $email, $senha_hash]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $nome;
                
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $e) {
                $reg_errors[] = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}

// Definir aba ativa
$active_tab = 'login';
if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
    $active_tab = 'register';
}

require 'includes/header.php';
?>
<style>
    .auth-page {
        min-height: 100vh;
        padding: 4rem 0;
    }

    .auth-logo {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-logo img {
        width: 300px;
        height: auto;
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    .auth-card {
        background: #ffffff;
        border: none;
        border-radius: 1.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .auth-title {
        color: #6a0dad;
        font-weight: 700;
        font-size: 2rem;
    }

    .nav-pills .nav-link {
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        color: white;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.6) 0%, rgba(118, 75, 162, 0.6) 100%);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .nav-pills .nav-link:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
        transform: translateY(-2px);
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.4);
        border-color: transparent;
    }

    .form-label {
        color: #2d3748;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 2px solid #cbd5e0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #6a0dad;
        box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(106, 13, 173, 0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border: none;
        padding: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
    }

    .btn-outline-secondary {
        border-color: #cbd5e0;
        color: #6a0dad;
    }

    .btn-outline-secondary:hover {
        background-color: #6a0dad;
        border-color: #6a0dad;
        color: white;
    }

    .text-end a {
        color: #6a0dad;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .text-end a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .alert-danger {
        background-color: #fff5f5;
        border-color: #feb2b2;
        color: #c53030;
        border-radius: 0.5rem;
    }

    .tab-content {
        padding-top: 2rem;
    }
</style>

<div class="container auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="auth-logo">
                <img src="assets/img/mockup.png" alt="FinanSmart Pro Logo">
            </div>
            <div class="card shadow-sm auth-card">
                <div class="card-body p-5">
                    <h3 class="card-title text-center mb-4 auth-title">Entrar</h3>
                    <?php if($errors): ?>
                        <div class="alert alert-danger">
                            <?php echo implode('<br>', $errors); ?>
                        </div>
                    <?php endif; ?>
                    <ul class="nav nav-pills nav-justified mb-4" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'login' ? 'active' : '' ?>" 
                                    id="login-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#login-form" 
                                    type="button" 
                                    role="tab">
                                Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $active_tab === 'register' ? 'active' : '' ?>"
                                    id="register-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#register-form" 
                                    type="button" 
                                    role="tab">
                                Registrar
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="authTabsContent">
                        <!-- Formulário de Login -->
                        <div class="tab-pane fade <?= $active_tab === 'login' ? 'show active' : '' ?>" 
                             id="login-form" 
                             role="tabpanel">
                            <?php if($errors): ?>
                                <div class="alert alert-danger">
                                    <?php echo implode('<br>', $errors); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="login">
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-control" 
                                           required 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="senha" 
                                               id="senha" 
                                               class="form-control" 
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword('senha')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="text-end mt-2">
                                        <a href="forgot_password.php">Esqueceu sua senha?</a>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Formulário de Registro -->
                        <div class="tab-pane fade <?= $active_tab === 'register' ? 'show active' : '' ?>" 
                             id="register-form" 
                             role="tabpanel">
                            <?php if($reg_errors): ?>
                                <div class="alert alert-danger">
                                    <?php echo implode('<br>', $reg_errors); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="registerForm" novalidate>
                                <input type="hidden" name="action" value="register">
                                <div class="mb-3">
                                    <label for="reg_nome" class="form-label">Nome</label>
                                    <input type="text" 
                                           name="nome" 
                                           id="reg_nome" 
                                           class="form-control" 
                                           required 
                                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                                    <div class="invalid-feedback">Por favor, informe seu nome.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="reg_email" class="form-label">E-mail</label>
                                    <input type="email" 
                                           name="email" 
                                           id="reg_email" 
                                           class="form-control" 
                                           required 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                    <div class="invalid-feedback">Por favor, informe um email válido.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="reg_senha" class="form-label">Senha</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="senha" 
                                               id="reg_senha" 
                                               class="form-control" 
                                               required 
                                               minlength="6">
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword('reg_senha')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">A senha deve ter pelo menos 6 caracteres.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="reg_confirmar_senha" class="form-label">Confirmar Senha</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="confirmar_senha" 
                                               id="reg_confirmar_senha" 
                                               class="form-control" 
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword('reg_confirmar_senha')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">As senhas não coincidem.</div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-user-plus me-2"></i>Criar Conta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                    function togglePassword(inputId) {
                        const input = document.getElementById(inputId);
                        const icon = event.currentTarget.querySelector('i');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.className = 'fas fa-eye-slash';
                        } else {
                            input.type = 'password';
                            icon.className = 'fas fa-eye';
                        }
                    }

                    document.getElementById('registerForm').addEventListener('submit', function(e) {
                        const form = e.target;
                        const senha = form.querySelector('#reg_senha');
                        const confirmarSenha = form.querySelector('#reg_confirmar_senha');
                        
                        form.classList.add('was-validated');
                        
                        if (senha.value !== confirmarSenha.value) {
                            confirmarSenha.setCustomValidity('As senhas não coincidem');
                        } else {
                            confirmarSenha.setCustomValidity('');
                        }
                        
                        if (!form.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });

                    // Manter a aba ativa após recarregar a página
                    const urlParams = new URLSearchParams(window.location.search);
                    const tab = urlParams.get('tab');
                    if (tab === 'register') {
                        const registerTab = new bootstrap.Tab(document.getElementById('register-tab'));
                        registerTab.show();
                    }

                    // Limpar validação ao digitar
                    document.getElementById('reg_confirmar_senha').addEventListener('input', function(e) {
                        const senha = document.getElementById('reg_senha');
                        if (this.value === senha.value) {
                            this.setCustomValidity('');
                        } else {
                            this.setCustomValidity('As senhas não coincidem');
                        }
                    });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>