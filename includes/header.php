<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="pt-br" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FinanSmart Pro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <span class="brand-mark">FS</span>
      <span class="ms-2">FinanSmart Pro</span>
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="themeToggle" class="btn btn-sm btn-outline-light me-2"><i class="fa-solid fa-adjust"></i></button>
      <?php if(isset($_SESSION['user_id'])): ?>
        <div class="dropdown">
          <a class="btn btn-sm btn-outline-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="lancamentos.php">Meus Lançamentos</a></li>
            <li><a class="dropdown-item" href="relatorios.php">Relatórios</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Sair</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-light btn-sm">Entrar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container-fluid p-4">
