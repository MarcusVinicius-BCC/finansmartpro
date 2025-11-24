<?php
require 'includes/db.php';
require_once 'includes/security.php';
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// CSRF Token
$csrf_token = Security::generateCSRFToken();

// Categorias
$cats = $pdo->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();

// Mês atual
$mes_atual = date('Y-m');

require 'includes/header.php';
?>
<div class="container">
  <!-- Relatórios Mensais -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Relatórios Mensais</h5>
    </div>
    <div class="card-body">
      <p class="text-muted">Gere relatórios completos com resumo financeiro, gráficos e lançamentos detalhados.</p>
      
      <div class="row g-3">
        <div class="col-md-6">
          <label for="mes_relatorio" class="form-label fw-bold">Selecione o Mês/Ano</label>
          <input type="month" id="mes_relatorio" class="form-control" value="<?= $mes_atual ?>">
        </div>
      </div>
      
      <div class="mt-3 d-flex gap-2 flex-wrap">
        <button onclick="gerarRelatorioPDF()" class="btn btn-danger">
          <i class="fas fa-file-pdf"></i> Gerar PDF
        </button>
        <button onclick="gerarRelatorioExcel()" class="btn btn-success">
          <i class="fas fa-file-excel"></i> Gerar Excel
        </button>
      </div>
    </div>
  </div>

  <!-- Relatórios Personalizados -->
  <div class="card">
    <div class="card-header bg-secondary text-white">
      <h5 class="mb-0"><i class="fas fa-filter"></i> Relatórios Personalizados</h5>
    </div>
    <div class="card-body">
      <p class="text-muted">Filtre por período, tipo e categoria para relatórios customizados.</p>
      
      <form action="pdf/gerar_relatorio.php" method="post" target="_blank">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="row g-3">
          <div class="col-md-3">
            <label for="start_date" class="form-label">Data de Início</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
          </div>
          <div class="col-md-3">
            <label for="end_date" class="form-label">Data de Fim</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= date('Y-m-t') ?>">
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
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> Gerar PDF Personalizado
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Informações sobre Relatórios -->
  <div class="alert alert-info mt-4">
    <h6><i class="fas fa-info-circle"></i> Sobre os Relatórios</h6>
    <ul class="mb-0">
      <li><strong>PDF:</strong> Relatório visual com gráficos, ideal para impressão e apresentações</li>
      <li><strong>Excel:</strong> Planilha completa com múltiplas abas (Resumo, Lançamentos, Por Categoria)</li>
      <li><strong>Personalizado:</strong> Filtre dados específicos por período, tipo e categoria</li>
    </ul>
  </div>
</div>

<script>
const csrfToken = '<?= $csrf_token ?>';

function gerarRelatorioPDF() {
  const mesAno = document.getElementById('mes_relatorio').value;
  if (!mesAno) {
    alert('Selecione um mês/ano');
    return;
  }
  
  window.open(
    `pdf/relatorio_mensal.php?mes_ano=${mesAno}&csrf_token=${csrfToken}`,
    '_blank'
  );
}

function gerarRelatorioExcel() {
  const mesAno = document.getElementById('mes_relatorio').value;
  if (!mesAno) {
    alert('Selecione um mês/ano');
    return;
  }
  
  window.open(
    `pdf/relatorio_excel.php?mes_ano=${mesAno}&csrf_token=${csrfToken}`,
    '_blank'
  );
}
</script>

<?php require 'includes/footer.php'; ?>