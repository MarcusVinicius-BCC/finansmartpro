<?php
require 'includes/db.php';
require 'includes/currency.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];
// get user base currency
$stmt = $pdo->prepare('SELECT moeda_base FROM usuarios WHERE id = ?');
$stmt->execute([$user_id]);
$userrow = $stmt->fetch();
$base = $userrow['moeda_base'] ?? 'BRL';
// totals per currency
$stmt = $pdo->prepare('SELECT moeda, SUM(CASE WHEN tipo="receita" THEN valor ELSE 0 END) as receita, SUM(CASE WHEN tipo="despesa" THEN valor ELSE 0 END) as despesa FROM lancamentos WHERE id_usuario = ? GROUP BY moeda');
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();
$consolidated = 0.0;
$breakdown = [];
foreach($rows as $r){
    $saldo = $r['receita'] - $r['despesa'];
    $converted = convert_amount($saldo, $r['moeda'], $base);
    $consolidated += $converted;
    $breakdown[] = ['moeda'=>$r['moeda'],'saldo'=>$saldo,'convertido'=>$converted];
}
// latest transactions
$stmt = $pdo->prepare('SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ? ORDER BY data DESC LIMIT 8');
$stmt->execute([$user_id]);
$latest = $stmt->fetchAll();
require 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card p-3 shadow-sm">
        <small class="text-muted">Saldo consolidado (<?= htmlspecialchars($base) ?>)</small>
        <h3 class="mt-2"><?= number_format($consolidated,2,',','.') ?> <?= $base ?></h3>
      </div>
    </div>
    <?php foreach($breakdown as $b): ?>
    <div class="col-6 col-md-3">
      <div class="card p-3">
        <small class="text-muted">Saldo (<?= $b['moeda'] ?>)</small>
        <h5 class="mt-2"><?= number_format($b['saldo'],2,',','.') ?> <?= $b['moeda'] ?></h5>
        <small class="text-muted">Convertido: <?= number_format($b['convertido'],2,',','.') ?> <?= $base ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="row mt-4">
    <div class="col-12 col-lg-7">
      <div class="card p-3">
        <h5>Últimas transações</h5>
        <table class="table">
          <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th></tr></thead>
          <tbody>
            <?php foreach($latest as $l): ?>
              <tr>
                <td><?= $l['data'] ?></td>
                <td><?= htmlspecialchars($l['descricao']) ?></td>
                <td><?= htmlspecialchars($l['categoria_nome']) ?></td>
                <td class="<?= $l['tipo']=='receita'?'text-success':'text-danger' ?>"><?= $l['moeda'] ?> <?= number_format($l['valor'],2,',','.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-12 col-lg-5">
      <div class="card p-3">
        <h5>Converter</h5>
        <form id="convForm">
          <div class="row g-2">
            <div class="col-5"><input id="conv_amount" class="form-control" type="number" value="100"></div>
            <div class="col-3"><select id="conv_from" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
            <div class="col-3"><select id="conv_to" class="form-select"><option><?= htmlspecialchars($base) ?></option><option>BRL</option><option>USD</option></select></div>
            <div class="col-1"><button class="btn btn-primary">OK</button></div>
          </div>
        </form>
        <div id="convResult" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('convForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const amount = document.getElementById('conv_amount').value;
  const from = document.getElementById('conv_from').value;
  const to = document.getElementById('conv_to').value;
  const res = await fetch(`/finansmart_pro/api/conversao.php?from=${from}&to=${to}&amount=${amount}`);
  const json = await res.json();
  if(json && json.result) {
    document.getElementById('convResult').innerHTML = `<strong>${json.result}</strong>`;
  } else {
    document.getElementById('convResult').innerText = 'Erro ao converter.';
  }
});
</script>

<?php require 'includes/footer.php'; ?>