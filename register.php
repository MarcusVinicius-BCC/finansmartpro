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
<div class="container auth-page">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm auth-card">
                <div class="card-body p-5">
                    <h3 class="card-title text-center mb-4 auth-title">Criar Conta</h3>
                    <?php if($errors): ?>
                        <div class="alert alert-danger">
                            <?php echo implode('<br>', $errors); ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" name="senha" id="senha" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha2" class="form-label">Confirmar Senha</label>
                            <input type="password" name="senha2" id="senha2" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Criar Conta</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>