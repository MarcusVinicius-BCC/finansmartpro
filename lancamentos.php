<?php
require 'includes/db.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];
// Inserir/Deletar/Editar
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    if($action === 'add'){
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];
        $data = $_POST['data'];
        $moeda = $_POST['moeda'];
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'] ?: null;
        $stmt = $pdo->prepare('INSERT INTO lancamentos (descricao, valor, data, moeda, tipo, id_usuario, id_categoria) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$descricao, $valor, $data, $moeda, $tipo, $user_id, $categoria]);
        header('Location: lancamentos.php'); exit;
    }
    if($action === 'delete'){
        $id = $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM lancamentos WHERE id = ? AND id_usuario = ?');
        $stmt->execute([$id, $user_id]);
        header('Location: lancamentos.php'); exit;
    }
    if($action === 'edit'){
        $id = $_POST['id'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];
        $data = $_POST['data'];
        $moeda = $_POST['moeda'];
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'] ?: null;
        $stmt = $pdo->prepare('UPDATE lancamentos SET descricao=?, valor=?, data=?, moeda=?, tipo=?, id_categoria=? WHERE id=? AND id_usuario=?');
        $stmt->execute([$descricao,$valor,$data,$moeda,$tipo,$categoria,$id,$user_id]);
        header('Location: lancamentos.php'); exit;
    }
}
// Listagem
$stmt = $pdo->prepare('SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ? ORDER BY data DESC');
$stmt->execute([$user_id]);
$lancamentos = $stmt->fetchAll();
// Categorias
$cats = $pdo->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();
require 'includes/header.php';
?>
<div class="container-fluid">
  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Lançamentos</h5>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">Novo</button>
    </div>
  </div>
  <div class="card p-3">
    <table class="table table-hover">
      <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th></th></tr></thead>
      <tbody>
        <?php foreach($lancamentos as $l): ?>
          <tr>
            <td><?= $l['data'] ?></td>
            <td><?= htmlspecialchars($l['descricao']) ?></td>
            <td><?= htmlspecialchars($l['categoria_nome']) ?></td>
            <td class="<?= $l['tipo']=='receita'?'text-success':'text-danger' ?>"><?= $l['moeda'] ?> <?= number_format($l['valor'],2,',','.') ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" onclick='openEdit(<?= json_encode(json_encode($l)) ?>)'><i class="fa-solid fa-pen"></i></button>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('Excluir?')"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="modal-header"><h5 class="modal-title">Novo Lançamento</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><input name="descricao" class="form-control" placeholder="Descrição" required></div>
          <div class="mb-2"><input name="valor" type="number" step="0.01" class="form-control" placeholder="Valor" required></div>
          <div class="mb-2"><input name="data" type="date" class="form-control" required></div>
          <div class="mb-2"><select name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-2"><select name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-2"><select name="categoria" class="form-select"><option value="">-- Selecionar --</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editForm" method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header"><h5 class="modal-title">Editar Lançamento</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><input id="edit_descricao" name="descricao" class="form-control" placeholder="Descrição" required></div>
          <div class="mb-2"><input id="edit_valor" name="valor" type="number" step="0.01" class="form-control" placeholder="Valor" required></div>
          <div class="mb-2"><input id="edit_data" name="data" type="date" class="form-control" required></div>
          <div class="mb-2"><select id="edit_moeda" name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-2"><select id="edit_tipo" name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-2"><select id="edit_categoria" name="categoria" class="form-select"><option value="">-- Selecionar --</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar Alterações</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function openEdit(jsonEncoded){
  const obj = JSON.parse(JSON.parse(jsonEncoded));
  document.getElementById('edit_id').value = obj.id;
  document.getElementById('edit_descricao').value = obj.descricao;
  document.getElementById('edit_valor').value = obj.valor;
  document.getElementById('edit_data').value = obj.data;
  document.getElementById('edit_moeda').value = obj.moeda;
  document.getElementById('edit_tipo').value = obj.tipo;
  document.getElementById('edit_categoria').value = obj.id_categoria || '';
  var editModal = new bootstrap.Modal(document.getElementById('editModal'));
  editModal.show();
}
</script>

<?php require 'includes/footer.php'; ?>