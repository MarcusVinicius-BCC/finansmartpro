<?php
require 'includes/db.php';
session_start();
$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    if(!$email) $errors[] = 'Email inválido.';
    if(empty($senha)) $errors[] = 'Senha requerida.';
    if(empty($errors)){
        $stmt = $pdo->prepare('SELECT id, senha, nome FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if($user && password_verify($senha, $user['senha'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            header('Location: dashboard.php'); exit;
        } else {
            $errors[] = 'Credenciais inválidas.';
        }
    }
}
require 'includes/header.php';
?>
<div class="container d-flex justify-content-center align-items-center" style="min-height:70vh;">
  <div class="card shadow-sm p-4" style="max-width:520px;width:100%;border-radius:12px;">
    <div class="row g-0">
      <div class="col-md-6 d-none d-md-block p-3 bg-hero rounded-start">
        <h4 class="text-white">Bem-vindo</h4>
        <p class="text-white-50">Gerencie suas finanças com facilidade.</p>
      </div>
      <div class="col-md-6 p-3">
        <h4 class="login-title">Entrar</h4>
        <?php if($errors): ?><div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="E-mail" required></div>
          <div class="mb-2"><input type="password" name="senha" class="form-control" placeholder="Senha" required></div>
          <div class="d-grid"><button class="btn btn-primary">Entrar</button></div>
        </form>
        <div class="mt-3 text-center"><a href="register.php">Criar conta</a></div>
      </div>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>