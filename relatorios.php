<?php
require 'includes/db.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
require 'includes/header.php';
?>
<div class="container">
  <div class="card p-3">
    <h5>Relatórios</h5>
    <p>Gerar relatório completo em PDF</p>
    <a href="pdf/gerar_relatorio.php" class="btn btn-primary" target="_blank">Gerar PDF</a>
  </div>
</div>
<?php require 'includes/footer.php'; ?>