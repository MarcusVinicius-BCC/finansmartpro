<?php
/**
 * Página de Redefinição de Senha
 * Permite ao usuário criar nova senha usando token válido
 */

require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/EmailService.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Validar token
$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()');
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    $errors[] = 'Token inválido ou expirado. Solicite uma nova recuperação de senha.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset_request) {
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        
        // Validar senha forte
        if (strlen($senha) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula.';
        }
        if (!preg_match('/[a-z]/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula.';
        }
        if (!preg_match('/[0-9]/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos um número.';
        }
        
        if ($senha !== $confirmar_senha) {
            $errors[] = 'As senhas não coincidem.';
        }
        
        if (empty($errors)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Buscar usuário
            $stmt = $pdo->prepare('SELECT id, nome FROM usuarios WHERE email = ?');
            $stmt->execute([$reset_request['email']]);
            $user = $stmt->fetch();
            
            // Atualizar senha
            $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
            $stmt->execute([$senha_hash, $reset_request['email']]);
            
            // Deletar todos os tokens de reset deste email
            $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
            $stmt->execute([$reset_request['email']]);
            
            // Enviar email de confirmação
            $emailService = new EmailService();
            $emailService->enviarConfirmacaoAlteracao($reset_request['email'], $user['nome'] ?? '');
            
            // Log de segurança
            Security::logSecurityEvent('password_reset_completed', [
                'user_id' => $user['id'],
                'email' => $reset_request['email']
            ]);
            
            $success = 'Sua senha foi redefinida com sucesso! Você já pode fazer login.';
        }
    }
}

// CSRF Token
$csrf_token = Security::generateCSRFToken();

require 'includes/header.php';
?>

<div class="container auth-page" style="margin-top: 50px;">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg auth-card border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <i class="fas fa-lock fa-3x mb-2"></i>
                    <h3 class="mb-0">Redefinir Senha</h3>
                    <p class="mb-0 mt-2">Crie uma senha forte e segura</p>
                </div>
                
                <div class="card-body p-5">
                    <?php if($errors): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                            <h5 class="mb-3"><?= htmlspecialchars($success) ?></h5>
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Ir para Login
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="resetForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            
                            <div class="mb-4">
                                <label for="senha" class="form-label fw-bold">
                                    <i class="fas fa-key text-primary"></i> Nova Senha
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control form-control-lg" 
                                        id="senha" 
                                        name="senha" 
                                        minlength="8"
                                        required
                                        autofocus
                                    >
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha')">
                                        <i class="fas fa-eye" id="senha-icon"></i>
                                    </button>
                                </div>
                                <div id="passwordStrength" class="mt-2"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmar_senha" class="form-label fw-bold">
                                    <i class="fas fa-check-double text-primary"></i> Confirmar Senha
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control form-control-lg" 
                                        id="confirmar_senha" 
                                        name="confirmar_senha" 
                                        minlength="8"
                                        required
                                    >
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_senha')">
                                        <i class="fas fa-eye" id="confirmar_senha-icon"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="mt-2"></div>
                            </div>
                            
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <i class="fas fa-shield-alt"></i> Requisitos de Senha Forte
                                </h6>
                                <ul class="mb-0 small" id="requirements">
                                    <li id="req-length">Mínimo de 8 caracteres</li>
                                    <li id="req-upper">Pelo menos uma letra maiúscula</li>
                                    <li id="req-lower">Pelo menos uma letra minúscula</li>
                                    <li id="req-number">Pelo menos um número</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                    <i class="fas fa-save"></i> Redefinir Senha
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left"></i> Voltar para Login
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle de visualização de senha
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validação de força de senha em tempo real
document.getElementById('senha')?.addEventListener('input', function(e) {
    const senha = e.target.value;
    const strengthDiv = document.getElementById('passwordStrength');
    
    // Verificar requisitos
    const hasLength = senha.length >= 8;
    const hasUpper = /[A-Z]/.test(senha);
    const hasLower = /[a-z]/.test(senha);
    const hasNumber = /[0-9]/.test(senha);
    
    // Atualizar lista de requisitos
    document.getElementById('req-length').style.color = hasLength ? 'green' : '';
    document.getElementById('req-upper').style.color = hasUpper ? 'green' : '';
    document.getElementById('req-lower').style.color = hasLower ? 'green' : '';
    document.getElementById('req-number').style.color = hasNumber ? 'green' : '';
    
    // Calcular força
    let strength = 0;
    if (hasLength) strength++;
    if (hasUpper) strength++;
    if (hasLower) strength++;
    if (hasNumber) strength++;
    if (/[^A-Za-z0-9]/.test(senha)) strength++; // Caracteres especiais
    
    // Exibir indicador
    let text = '';
    let color = '';
    
    if (senha.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    if (strength <= 2) {
        text = 'Fraca';
        color = 'danger';
    } else if (strength === 3 || strength === 4) {
        text = 'Média';
        color = 'warning';
    } else {
        text = 'Forte';
        color = 'success';
    }
    
    strengthDiv.innerHTML = `<small class="text-${color}"><strong>Força: ${text}</strong></small>`;
});

// Validar confirmação de senha
document.getElementById('confirmar_senha')?.addEventListener('input', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = e.target.value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmar.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (senha === confirmar) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> Senhas coincidem</small>';
    } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> Senhas não coincidem</small>';
    }
});
</script>

<?php require 'includes/footer.php'; ?>
