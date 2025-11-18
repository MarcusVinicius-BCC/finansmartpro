<?php
require 'includes/db.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Retorno para requisições AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($action === 'add' || $action === 'edit') {
        $data = [
            'descricao' => $_POST['descricao'],
            'valor' => $_POST['valor'],
            'data' => $_POST['data'],
            'moeda' => $_POST['moeda'],
            'tipo' => $_POST['tipo'],
            'id_categoria' => $_POST['categoria'] ?: null,
            'id_usuario' => $user_id
        ];
        
        try {
            if ($action === 'add') {
                $sql = "INSERT INTO lancamentos (descricao, valor, data, moeda, tipo, id_categoria, id_usuario) 
                        VALUES (:descricao, :valor, :data, :moeda, :tipo, :id_categoria, :id_usuario)";
            } else {
                $sql = "UPDATE lancamentos 
                        SET descricao = :descricao, valor = :valor, data = :data, 
                            moeda = :moeda, tipo = :tipo, id_categoria = :id_categoria 
                        WHERE id = :id AND id_usuario = :id_usuario";
                $data['id'] = $_POST['id'];
            }
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($data);
            
            if ($isAjax) {
                echo json_encode(['success' => $success]);
                exit;
            }
            
        } catch (PDOException $e) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
    
    elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM lancamentos WHERE id = ? AND id_usuario = ?");
            $success = $stmt->execute([$_POST['id'], $user_id]);
            
            if ($isAjax) {
                echo json_encode(['success' => $success]);
                exit;
            }
        } catch (PDOException $e) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
    
    // Redirecionar após operações não-AJAX
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Search and filter
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_currency = $_GET['filter_currency'] ?? '';

$sql = 'SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ?';
$params = [$user_id];

if ($search) {
    $sql .= ' AND l.descricao LIKE ?';
    $params[] = "%$search%";
}

if ($filter_type) {
    $sql .= ' AND l.tipo = ?';
    $params[] = $filter_type;
}

if ($filter_category) {
    $sql .= ' AND l.id_categoria = ?';
    $params[] = $filter_category;
}

if ($filter_currency) {
    $sql .= ' AND l.moeda = ?';
    $params[] = $filter_currency;
}

$sql .= ' ORDER BY data DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

// Categorias
$cats = $pdo->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();
require 'includes/header.php';
?>
<div class="container-fluid">
  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Lançamentos</h5>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Novo Lançamento</button>
    </div>
  </div>
  <div class="card p-3 mb-3">
    <form method="get">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Pesquisar por descrição..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="filter_type" class="form-select">
                    <option value="">Todos os tipos</option>
                    <option value="receita" <?= $filter_type == 'receita' ? 'selected' : '' ?>>Receita</option>
                    <option value="despesa" <?= $filter_type == 'despesa' ? 'selected' : '' ?>>Despesa</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="filter_category" class="form-select">
                    <option value="">Todas as categorias</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filter_category == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="filter_currency" class="form-select">
                    <option value="">Todas as moedas</option>
                    <option value="BRL" <?= $filter_currency == 'BRL' ? 'selected' : '' ?>>BRL</option>
                    <option value="USD" <?= $filter_currency == 'USD' ? 'selected' : '' ?>>USD</option>
                    <option value="EUR" <?= $filter_currency == 'EUR' ? 'selected' : '' ?>>EUR</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>
  </div>
  <div class="card p-3">
    <table class="table table-hover">
      <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach($lancamentos as $l): ?>
          <tr>
            <td><?= $l['data'] ?></td>
            <td><?= htmlspecialchars($l['descricao']) ?></td>
            <td><?= htmlspecialchars($l['categoria_nome']) ?></td>
            <td class="<?= $l['tipo']=='receita'?'text-success':'text-danger' ?>"><?= $l['moeda'] ?> <?= number_format($l['valor'],2,',','.') ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-info" onclick='openEdit(<?= json_encode(json_encode($l)) ?>)'><i class="fas fa-edit"></i></button>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('Excluir?')"><i class="fas fa-trash"></i></button>
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="modal-header"><h5 class="modal-title">Novo Lançamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label for="add_descricao" class="form-label">Descrição</label><input id="add_descricao" name="descricao" class="form-control" required></div>
          <div class="mb-3"><label for="add_valor" class="form-label">Valor</label><input id="add_valor" name="valor" type="number" step="0.01" class="form-control" required></div>
          <div class="mb-3"><label for="add_data" class="form-label">Data</label><input id="add_data" name="data" type="date" class="form-control" required></div>
          <div class="mb-3"><label for="add_moeda" class="form-label">Moeda</label><select id="add_moeda" name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-3"><label for="add_tipo" class="form-label">Tipo</label><select id="add_tipo" name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-3"><label for="add_categoria" class="form-label">Categoria</label><select id="add_categoria" name="categoria" class="form-select"><option value="">-- Selecionar --</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editForm" method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header"><h5 class="modal-title">Editar Lançamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label for="edit_descricao" class="form-label">Descrição</label><input id="edit_descricao" name="descricao" class="form-control" required></div>
          <div class="mb-3"><label for="edit_valor" class="form-label">Valor</label><input id="edit_valor" name="valor" type="number" step="0.01" class="form-control" required></div>
          <div class="mb-3"><label for="edit_data" class="form-label">Data</label><input id="edit_data" name="data" type="date" class="form-control" required></div>
          <div class="mb-3"><label for="edit_moeda" class="form-label">Moeda</label><select id="edit_moeda" name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-3"><label for="edit_tipo" class="form-label">Tipo</label><select id="edit_tipo" name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-3"><label for="edit_categoria" class="form-label">Categoria</label><select id="edit_categoria" name="categoria" class="form-select"><option value="">-- Selecionar --</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option><?php endforeach; ?></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Alterações</button></div>
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