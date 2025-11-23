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
    
    // Exportar para CSV
    if ($action === 'export_csv') {
        $search = $_POST['search'] ?? '';
        $filter_type = $_POST['filter_type'] ?? '';
        $filter_category = $_POST['filter_category'] ?? '';
        $filter_currency = $_POST['filter_currency'] ?? '';
        $filter_date_start = $_POST['filter_date_start'] ?? '';
        $filter_date_end = $_POST['filter_date_end'] ?? '';
        $filter_min_value = $_POST['filter_min_value'] ?? '';
        $filter_max_value = $_POST['filter_max_value'] ?? '';
        
        $sql = 'SELECT l.data, l.tipo, l.descricao, c.nome as categoria, l.valor, l.moeda 
                FROM lancamentos l 
                LEFT JOIN categorias c ON l.id_categoria = c.id 
                WHERE l.id_usuario = ?';
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
        if ($filter_date_start) {
            $sql .= ' AND l.data >= ?';
            $params[] = $filter_date_start;
        }
        if ($filter_date_end) {
            $sql .= ' AND l.data <= ?';
            $params[] = $filter_date_end;
        }
        if ($filter_min_value) {
            $sql .= ' AND l.valor >= ?';
            $params[] = $filter_min_value;
        }
        if ($filter_max_value) {
            $sql .= ' AND l.valor <= ?';
            $params[] = $filter_max_value;
        }
        
        $sql .= ' ORDER BY l.data DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // Gerar CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=lancamentos_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
        
        fputcsv($output, ['Data', 'Tipo', 'Descrição', 'Categoria', 'Valor', 'Moeda']);
        
        foreach ($results as $row) {
            fputcsv($output, [
                date('d/m/Y', strtotime($row['data'])),
                ucfirst($row['tipo']),
                $row['descricao'],
                $row['categoria'],
                number_format($row['valor'], 2, ',', '.'),
                $row['moeda']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // Ações de CATEGORIA
    if ($action === 'add_category') {
        $nome = trim($_POST['nome']);
        $tipo = $_POST['tipo'];
        
        $stmt = $pdo->prepare("INSERT INTO categorias (id_usuario, nome, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $nome, $tipo]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'nome' => $nome, 'tipo' => $tipo]);
        exit;
    }
    
    elseif ($action === 'edit_category') {
        try {
            $stmt = $pdo->prepare('UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND id_usuario = ?');
            $success = $stmt->execute([$_POST['nome'], $_POST['tipo'], $_POST['id'], $user_id]);
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
    
    elseif ($action === 'delete_category') {
        try {
            $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = ? AND id_usuario = ?');
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
$filter_date_start = $_GET['filter_date_start'] ?? '';
$filter_date_end = $_GET['filter_date_end'] ?? '';
$filter_min_value = $_GET['filter_min_value'] ?? '';
$filter_max_value = $_GET['filter_max_value'] ?? '';

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

if ($filter_date_start) {
    $sql .= ' AND l.data >= ?';
    $params[] = $filter_date_start;
}

if ($filter_date_end) {
    $sql .= ' AND l.data <= ?';
    $params[] = $filter_date_end;
}

if ($filter_min_value) {
    $sql .= ' AND l.valor >= ?';
    $params[] = $filter_min_value;
}

if ($filter_max_value) {
    $sql .= ' AND l.valor <= ?';
    $params[] = $filter_max_value;
}

$sql .= ' ORDER BY data DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

// Estatísticas dos lançamentos filtrados
$total_receitas = 0;
$total_despesas = 0;
foreach ($lancamentos as $l) {
    if ($l['tipo'] === 'receita') {
        $total_receitas += $l['valor'];
    } else {
        $total_despesas += $l['valor'];
    }
}

// Categorias do usuário + categorias padrão do sistema
$stmt = $pdo->prepare('SELECT * FROM categorias WHERE id_usuario = ? OR id_usuario = 0 OR id_usuario IS NULL ORDER BY nome');
$stmt->execute([$user_id]);
$cats = $stmt->fetchAll();
require 'includes/header.php';
// Disponibilizar utilitários de moeda para formatação
require_once 'includes/currency.php';
?>
<div class="container-fluid">
  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Lançamentos</h5>
      <div>
        <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal"><i class="fas fa-tags"></i> Gerenciar Categorias</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Novo Lançamento</button>
      </div>
    </div>
  </div>
  <div class="card p-3 mb-3">
    <form method="get" id="filterForm">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Pesquisar</label>
                <input type="text" name="search" class="form-control" placeholder="Descrição..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tipo</label>
                <select name="filter_type" class="form-select">
                    <option value="">Todos</option>
                    <option value="receita" <?= $filter_type == 'receita' ? 'selected' : '' ?>>Receita</option>
                    <option value="despesa" <?= $filter_type == 'despesa' ? 'selected' : '' ?>>Despesa</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Categoria</label>
                <select name="filter_category" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filter_category == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Moeda</label>
                <select name="filter_currency" class="form-select">
                    <option value="">Todas</option>
                    <option value="BRL" <?= $filter_currency == 'BRL' ? 'selected' : '' ?>>BRL</option>
                    <option value="USD" <?= $filter_currency == 'USD' ? 'selected' : '' ?>>USD</option>
                    <option value="EUR" <?= $filter_currency == 'EUR' ? 'selected' : '' ?>>EUR</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Período</label>
                <div class="input-group">
                    <input type="date" name="filter_date_start" class="form-control" value="<?= htmlspecialchars($filter_date_start) ?>">
                    <span class="input-group-text">até</span>
                    <input type="date" name="filter_date_end" class="form-control" value="<?= htmlspecialchars($filter_date_end) ?>">
                </div>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-2">
                <label class="form-label small">Valor Mínimo</label>
                <input type="text" name="filter_min_value" class="form-control money-input" placeholder="0,00" value="<?= htmlspecialchars($filter_min_value ? number_format($filter_min_value, 2, ',', '.') : '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Valor Máximo</label>
                <input type="text" name="filter_max_value" class="form-control money-input" placeholder="0,00" value="<?= htmlspecialchars($filter_max_value ? number_format($filter_max_value, 2, ',', '.') : '') ?>">
            </div>
            <div class="col-md-8 d-flex align-items-end justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="lancamentos.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpar
                </a>
                <button type="button" class="btn btn-success" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>
        </div>
    </form>
  </div>
  
  <!-- Estatísticas dos Filtros -->
  <?php if (!empty($lancamentos)): ?>
  <div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body py-2">
            <small>Total Receitas (filtrado)</small>
            <h5 class="mb-0"><?= format_currency($total_receitas, $filter_currency ?: ($user_base ?? 'BRL')) ?></h5>
          </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body py-2">
            <small>Total Despesas (filtrado)</small>
            <h5 class="mb-0"><?= format_currency($total_despesas, $filter_currency ?: ($user_base ?? 'BRL')) ?></h5>
          </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body py-2">
            <small>Saldo (filtrado)</small>
            <h5 class="mb-0"><?= format_currency($total_receitas - $total_despesas, $filter_currency ?: ($user_base ?? 'BRL')) ?></h5>
          </div>
        </div>
    </div>
  </div>
  <?php endif; ?>
  
  <div class="card p-3">
    <table class="table table-hover">
      <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach($lancamentos as $l): ?>
            <tr>
            <td><?= $l['data'] ?></td>
            <td><?= htmlspecialchars($l['descricao']) ?></td>
            <td><?= htmlspecialchars($l['categoria_nome']) ?></td>
            <td class="<?= $l['tipo']=='receita'?'text-success':'text-danger' ?>"><?= $l['tipo']=='receita' ? '+' : '-' ?> <?= format_currency($l['valor'], $l['moeda']) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-info" onclick="openEdit('<?= htmlspecialchars(json_encode($l), ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
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
          <div class="mb-3">
            <label for="add_valor" class="form-label">Valor</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input id="add_valor" name="valor" type="text" class="form-control money-input" required placeholder="0,00">
            </div>
          </div>
          <div class="mb-3"><label for="add_data" class="form-label">Data</label><input id="add_data" name="data" type="date" class="form-control" required></div>
          <div class="mb-3"><label for="add_moeda" class="form-label">Moeda</label><select id="add_moeda" name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-3"><label for="add_tipo" class="form-label">Tipo</label><select id="add_tipo" name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-3">
            <label for="add_categoria" class="form-label">Categoria</label>
            <div class="input-group">
              <select id="add_categoria" name="categoria" class="form-select">
                <option value="">-- Selecionar --</option>
                <?php foreach($cats as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-outline-secondary" onclick="openQuickAddFromAdd()" title="Nova Categoria">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
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
          <div class="mb-3">
            <label for="edit_valor" class="form-label">Valor</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input id="edit_valor" name="valor" type="text" class="form-control money-input" required placeholder="0,00">
            </div>
          </div>
          <div class="mb-3"><label for="edit_data" class="form-label">Data</label><input id="edit_data" name="data" type="date" class="form-control" required></div>
          <div class="mb-3"><label for="edit_moeda" class="form-label">Moeda</label><select id="edit_moeda" name="moeda" class="form-select"><option>BRL</option><option>USD</option><option>EUR</option></select></div>
          <div class="mb-3"><label for="edit_tipo" class="form-label">Tipo</label><select id="edit_tipo" name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
          <div class="mb-3">
            <label for="edit_categoria" class="form-label">Categoria</label>
            <div class="input-group">
              <select id="edit_categoria" name="categoria" class="form-select">
                <option value="">-- Selecionar --</option>
                <?php foreach($cats as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['tipo'] ?>)</option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-outline-secondary" onclick="openQuickAddFromEdit()" title="Nova Categoria">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Alterações</button></div>
      </form>
    </div>
  </div>
</div>

<script>
// Variável global para rastrear de qual modal veio
window.categorySourceModal = null;

// Funções para abrir modal de categoria
function openQuickAddFromAdd() {
  window.categorySourceModal = 'add';
  const addModal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
  if (addModal) addModal.hide();
  setTimeout(() => {
    const quickModal = new bootstrap.Modal(document.getElementById('quickAddCategoryModal'));
    quickModal.show();
  }, 300);
}

function openQuickAddFromEdit() {
  window.categorySourceModal = 'edit';
  const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
  if (editModal) editModal.hide();
  setTimeout(() => {
    const quickModal = new bootstrap.Modal(document.getElementById('quickAddCategoryModal'));
    quickModal.show();
  }, 300);
}

// Função para cancelar e voltar ao modal de origem
function cancelQuickAdd() {
  const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickAddCategoryModal'));
  if (quickModal) quickModal.hide();
  
  const sourceModal = window.categorySourceModal;
  setTimeout(() => {
    if (sourceModal === 'add') {
      const addModal = new bootstrap.Modal(document.getElementById('addModal'));
      addModal.show();
    } else if (sourceModal === 'edit') {
      const editModal = new bootstrap.Modal(document.getElementById('editModal'));
      editModal.show();
    }
  }, 300);
}

// Formatação de valor monetário
function formatMoney(input) {
  let value = input.value.replace(/\D/g, '');
  value = (parseInt(value) / 100).toFixed(2);
  value = value.replace('.', ',');
  value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
  input.value = value;
}

function unformatMoney(value) {
  return value.replace(/\./g, '').replace(',', '.');
}

document.querySelectorAll('.money-input').forEach(input => {
  input.addEventListener('input', function() {
    formatMoney(this);
  });
  
  input.addEventListener('blur', function() {
    if (this.value === '' || this.value === '0,00') {
      this.value = '0,00';
    }
  });
});

// Interceptar submissão dos formulários para converter valor
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', function(e) {
    const valorInputs = this.querySelectorAll('.money-input');
    valorInputs.forEach(input => {
      if (input.value && input.name) {
        const originalName = input.name;
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = originalName;
        hiddenInput.value = unformatMoney(input.value);
        this.appendChild(hiddenInput);
        input.removeAttribute('name');
      }
    });
  });
});

function openEdit(jsonEncoded){
  try {
    const obj = JSON.parse(jsonEncoded);
    document.getElementById('edit_id').value = obj.id;
    document.getElementById('edit_descricao').value = obj.descricao;
    
    // Formatar valor para exibição
    const valorFormatado = parseFloat(obj.valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('edit_valor').value = valorFormatado;
    
    document.getElementById('edit_data').value = obj.data;
    document.getElementById('edit_moeda').value = obj.moeda;
    document.getElementById('edit_tipo').value = obj.tipo;
    document.getElementById('edit_categoria').value = obj.id_categoria || '';
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
  } catch(error) {
    console.error('Erro ao abrir modal de edição:', error);
    alert('Erro ao abrir formulário de edição. Verifique o console.');
  }
}

// Quick Add Category from lancamentos modal
document.addEventListener('DOMContentLoaded', function() {
  const quickForm = document.getElementById('quickCategoryForm');
  if (quickForm) {
    quickForm.addEventListener('submit', function(e) {
      e.preventDefault();
      console.log('Formulário submetido!'); // Debug
      
      const formData = new FormData(this);
      const nome = formData.get('nome');
      const tipo = formData.get('tipo');
      
      console.log('Nome:', nome, 'Tipo:', tipo); // Debug
      
      // Criar dados para enviar
      const data = new URLSearchParams();
      data.append('action', 'add_category');
      data.append('nome', nome);
      data.append('tipo', tipo);
      
      // Enviar requisição
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data
      })
      .then(response => response.json())
      .then(result => {
        console.log('Resultado:', result);
        
        if (result.success) {
          // Add to both selects
          const newOption = document.createElement('option');
          newOption.value = result.id;
          newOption.textContent = `${result.nome} (${result.tipo})`;
          document.getElementById('add_categoria').appendChild(newOption.cloneNode(true));
          document.getElementById('edit_categoria').appendChild(newOption);
          
          // Select the new category in the appropriate select
          const sourceModal = window.categorySourceModal;
          if (sourceModal === 'add') {
            document.getElementById('add_categoria').value = result.id;
          } else if (sourceModal === 'edit') {
            document.getElementById('edit_categoria').value = result.id;
          }
          
          // Reset form and close modal
          quickForm.reset();
          const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickAddCategoryModal'));
          if (quickModal) {
            quickModal.hide();
          }
          
          // Reabrir o modal de origem
          setTimeout(() => {
            if (sourceModal === 'add') {
              const addModal = new bootstrap.Modal(document.getElementById('addModal'));
              addModal.show();
            } else if (sourceModal === 'edit') {
              const editModal = new bootstrap.Modal(document.getElementById('editModal'));
              editModal.show();
            }
          }, 300);
        } else {
          alert('Erro ao criar categoria: ' + (result.message || 'Erro desconhecido'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ocorreu um erro ao criar a categoria.');
      });
    });
  } else {
    console.error('Formulário quickCategoryForm não encontrado!');
  }
});

// Manage Categories Modal Scripts
let currentEditCategoryId = null;

function openEditCategoryModal(id, nome, tipo) {
  currentEditCategoryId = id;
  document.getElementById('editCategoryName').value = nome;
  document.getElementById('editCategoryType').value = tipo;
  const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
  editModal.show();
}

document.getElementById('editCategoryForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'edit_category');
  formData.append('id', currentEditCategoryId);
  
  try {
    const response = await fetch('', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: formData
    });
    const result = await response.json();
    
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
      location.reload();
    } else {
      alert('Erro ao editar categoria: ' + result.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Ocorreu um erro ao editar a categoria.');
  }
});

async function deleteCategory(id, nome) {
  if (confirm(`Tem certeza que deseja excluir a categoria "${nome}"?`)) {
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('id', id);
    
    try {
      const response = await fetch('', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: formData
      });
      const result = await response.json();
      
      if (result.success) {
        location.reload();
      } else {
        alert('Erro ao excluir categoria: ' + result.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Ocorreu um erro ao excluir a categoria.');
    }
  }
}

// Exportar CSV
function exportCSV() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    formData.append('action', 'export_csv');
    
    // Criar form temporário para submit
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.style.display = 'none';
    
    formData.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
    });
    
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
}

</script>

<!-- Quick Add Category Modal (small, from lancamentos form) -->
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="quickCategoryForm">
        <div class="modal-header">
          <h5 class="modal-title">Nova Categoria</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select" required>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd()">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Criar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Manage Categories Modal (full management) -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gerenciar Categorias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Tipo</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id_usuario = ? OR id_usuario = 0 OR id_usuario IS NULL ORDER BY tipo DESC, nome ASC');
            $stmt->execute([$user_id]);
            $categorias_list = $stmt->fetchAll();
            
            if (empty($categorias_list)): ?>
              <tr><td colspan="3" class="text-center text-muted">Nenhuma categoria cadastrada.</td></tr>
            <?php else: ?>
              <?php foreach($categorias_list as $cat): ?>
                <tr>
                  <td>
                    <?= htmlspecialchars($cat['nome']) ?>
                    <?php if ($cat['id_usuario'] == 0 || $cat['id_usuario'] == NULL): ?>
                      <small class="text-muted">(Sistema)</small>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-<?= $cat['tipo'] == 'receita' ? 'success' : 'danger' ?>"><?= ucfirst($cat['tipo']) ?></span></td>
                  <td class="text-end">
                    <?php if ($cat['id_usuario'] == $user_id): ?>
                      <button class="btn btn-sm btn-info" onclick='openEditCategoryModal(<?= $cat['id'] ?>, "<?= htmlspecialchars($cat['nome']) ?>", "<?= $cat['tipo'] ?>")'>
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" onclick='deleteCategory(<?= $cat['id'] ?>, "<?= htmlspecialchars($cat['nome']) ?>")'>
                        <i class="fas fa-trash"></i>
                      </button>
                    <?php else: ?>
                      <span class="text-muted small">Categoria padrão</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="editCategoryForm">
        <div class="modal-header">
          <h5 class="modal-title">Editar Categoria</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" id="editCategoryName" name="nome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select id="editCategoryType" name="tipo" class="form-select" required>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>