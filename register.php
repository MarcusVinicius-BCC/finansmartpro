<?php
require 'includes/db.php';
session_start();
$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';
    if(empty($nome)) $errors[] = 'Nome requerido.';
    if(!$email) $errors[] = 'Email inválido.';
    if(strlen($senha) < 6) $errors[] = 'Senha deve ter ao menos 6 caracteres.';
    if($senha !== $senha2) $errors[] = 'Senhas não conferem.';
    if(empty($errors)){
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        try{
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $email, $hash]);
            header('Location: login.php'); exit;
        } catch (PDOException $e){
            if($e->getCode() == 23000) $errors[] = 'Email já cadastrado.';
            else $errors[] = 'Erro no cadastro.';
        }
    }
}
require 'includes/header.php';
?>
<div class="container d-flex justify-content-center align-items-center" style="min-height:70vh;">
  <div class="card shadow-sm p-4" style="max-width:640px;width:100%;border-radius:12px;">
    <div class="row g-0">
      <div class="col-md-6 p-3">
        <h4>Criar Conta</h4>
        <?php if($errors): ?><div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-2"><input type="text" name="nome" class="form-control" placeholder="Nome completo" required></div>
          <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
          <div class="row g-2"><div class="col-md-6"><input type="password" name="senha" class="form-control" placeholder="Senha" required></div><div class="col-md-6"><input type="password" name="senha2" class="form-control" placeholder="Confirmar senha" required></div></div>
          <div class="d-grid mt-3"><button class="btn btn-success">Criar Conta</button></div>
        </form>
      </div>
      <div class="col-md-6 d-none d-md-block bg-hero-2 p-3 rounded-end">
        <h5 class="text-white">Comece grátis</h5>
        <p class="text-white-50">Teste por 30 dias sem custo.</p>
      </div>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>