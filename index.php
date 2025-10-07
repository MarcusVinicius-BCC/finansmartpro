<?php
require 'includes/db.php';
session_start();
require 'includes/header.php';
?>
<div class="container mt-4">
  <div class="row align-items-center">
    <div class="col-lg-6">
      <h1 class="display-5">Você no comando das suas finanças</h1>
      <p class="lead">FinanSmart Pro ajuda você a controlar receitas, despesas e a tomar decisões com dados reais — sem complicação.</p>
      <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-primary btn-lg">Testar grátis</a>
      </div>
      <div class="mt-4 d-flex gap-3">
        <div class="feature"><i class="fa-solid fa-chart-line"></i><span>Dashboard inteligente</span></div>
        <div class="feature"><i class="fa-solid fa-file-export"></i><span>Exportar relatórios</span></div>
        <div class="feature"><i class="fa-solid fa-exchange-alt"></i><span>Conversão multi-moeda</span></div>
      </div>
    </div>
    <div class="col-lg-6 text-center">
      <img src="assets/img/mockup.png" alt="mockup" class="img-fluid rounded" style="max-width:650px;">
    </div>
  </div>

  <section id="features" class="mt-5">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5>Resumo em tempo real</h5>
          <p>Saldo consolidado por moeda e visão diária, semanal e mensal.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5>Relatórios e exportação</h5>
          <p>Exporte em PDF ou CSV com um clique.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5>Alertas inteligentes</h5>
          <p>Receba alertas quando gastos excederem orçamentos.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="mt-5">
    <div class="card p-4">
      <h4>Depoimentos</h4>
      <div class="row">
        <div class="col-md-4">"Integração futura" - Administrador</div>
        
      </div>
    </div>
  </section>
</div>
<?php require 'includes/footer.php'; ?>