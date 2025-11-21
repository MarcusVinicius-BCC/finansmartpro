<?php
session_start();
require 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $errors[] = 'Email inválido.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(50));
            $expires = new DateTime('now +1 hour');

            $stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$email, $token, $expires->format('Y-m-d H:i:s')]);

            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/finansmartpro/reset_password.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth   = true;
                $mail->Username   = '952bbf04907c48';
                $mail->Password   = '70a5c57a1c4113';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 2525;

                //Recipients
                $mail->setFrom('no-reply@finansmartpro.com', 'FinanSmart Pro');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Redefinicao de Senha - FinanSmart Pro';
                $mail->Body    = "Clique neste link para redefinir sua senha: <a href='{$reset_link}'>{$reset_link}</a>";
                $mail->AltBody = "Copie e cole este link no seu navegador para redefinir sua senha: {$reset_link}";

                $mail->send();
                $success = 'Um link para redefinir sua senha foi enviado para o seu email.';
            } catch (Exception $e) {
                $errors[] = "Nao foi possivel enviar o email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $errors[] = 'Nenhum usuario encontrado com este email.';
        }
    }
}

require 'includes/header.php';
?>

<div class="container auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm auth-card">
                <div class="card-body p-5">
                    <h3 class="card-title text-center mb-4 auth-title">Esqueceu a Senha?</h3>
                    <?php if($errors): ?>
                        <div class="alert alert-danger">
                            <?php echo implode('<br>', $errors); ?>
                        </div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Enviar Link de Redefinicao</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
