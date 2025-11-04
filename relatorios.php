<?php
require 'includes/db.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Categorias
$cats = $pdo->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();

require 'includes/header.php';
?>
<div class="container">
  <div class="card p-3">
    <h5>Relatórios</h5>
    <form action="pdf/gerar_relatorio.php" method="post" target="_blank">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Data de Início</label>
                <input type="date" name="start_date" id="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Data de Fim</label>
                <input type="date" name="end_date" id="end_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filter_type" class="form-label">Tipo</label>
                <select name="filter_type" id="filter_type" class="form-select">
                    <option value="">Todos</option>
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_category" class="form-label">Categoria</label>
                <select name="filter_category" id="filter_category" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Gerar PDF</button>
        </div>
    </form>
  </div>
</div>
<?php require 'includes/footer.php'; ?>