<?php
session_start();
require 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()');
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    $errors[] = 'Token invalido ou expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset_request) {
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (strlen($senha) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }

    if ($senha !== $confirmar_senha) {
        $errors[] = 'As senhas nao coincidem.';
    }

    if (empty($errors)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
        $stmt->execute([$senha_hash, $reset_request['email']]);

        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->execute([$reset_request['email']]);

        $success = 'Sua senha foi redefinida com sucesso! Voce ja pode fazer login.';
    }
}

require 'includes/header.php';
?>

<div class="container auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm auth-card">
                <div class="card-body p-5">
                    <h3 class="card-title text-center mb-4 auth-title">Redefinir Senha</h3>
                    <?php if($errors): ?>
                        <div class="alert alert-danger">
                            <?php echo implode('<br>', $errors); ?>
                        </div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php else: ?>
                        <form method="post" novalidate>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label for="senha" class="form-label">Nova Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
