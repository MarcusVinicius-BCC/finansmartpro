<?php
/**
 * Página de Recuperação de Senha
 * Permite ao usuário solicitar link de redefinição por email
 */

require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/EmailService.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token de segurança inválido. Tente novamente.';
        $messageType = 'danger';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Por favor, insira um email válido.';
            $messageType = 'warning';
        } else {
            // Buscar usuário
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Gerar token seguro
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Salvar token no banco
                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (email, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$email, $token, $expires]);
                
                // Enviar email
                $emailService = new EmailService();
                $emailEnviado = $emailService->enviarRecuperacaoSenha($email, $token, $user['nome']);
                
                if ($emailEnviado) {
                    $message = 'Instruções de recuperação foram enviadas para seu email.';
                    $messageType = 'success';
                    
                    // Log de segurança
                    Security::logSecurityEvent('password_reset_requested', [
                        'email' => $email,
                        'user_id' => $user['id']
                    ]);
                } else {
                    $message = 'Erro ao enviar email. Configure o SMTP no EmailService.php';
                    $messageType = 'warning';
                }
            } else {
                // Por segurança, mostra mensagem genérica mesmo se email não existe
                $message = 'Instruções de recuperação foram enviadas para seu email (se cadastrado).';
                $messageType = 'info';
                
                // Log de tentativa com email não cadastrado
                Security::logSecurityEvent('password_reset_unknown_email', [
                    'email' => $email
                ]);
            }
        }
    }
}

// CSRF Token para o formulário
$csrf_token = Security::generateCSRFToken();

require 'includes/header.php';
?>

<div class="container auth-page" style="margin-top: 50px;">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg auth-card border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <i class="fas fa-key fa-3x mb-2"></i>
                    <h3 class="mb-0">Recuperar Senha</h3>
                    <p class="mb-0 mt-2">Digite seu email para receber instruções</p>
                </div>
                
                <div class="card-body p-5">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?php if ($messageType === 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($messageType === 'danger' || $messageType === 'warning'): ?>
                                <i class="fas fa-exclamation-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope text-primary"></i> Email
                            </label>
                            <input 
                                type="email" 
                                class="form-control form-control-lg" 
                                id="email" 
                                name="email" 
                                placeholder="seu-email@exemplo.com"
                                required
                                autofocus
                            >
                            <small class="form-text text-muted">
                                Enviaremos um link de recuperação para este email
                            </small>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Enviar Link de Recuperação
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Voltar para Login
                            </a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info mb-0" role="alert">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle"></i> Informações Importantes
                        </h6>
                        <ul class="mb-0 small">
                            <li>O link de recuperação expira em <strong>1 hora</strong></li>
                            <li>Verifique sua caixa de spam se não receber o email</li>
                            <li>Configure o SMTP em <code>includes/EmailService.php</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
