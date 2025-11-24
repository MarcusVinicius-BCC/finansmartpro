<?php
require 'includes/db.php';
require 'includes/currency.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';
if(!isset($_SESSION['user_id'])) header('Location: login.php');
$user_id = $_SESSION['user_id'];

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'cartoes',
            'action' => $action,
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($action === 'create') {
        $nome = $_POST['nome'];
        $bandeira = $_POST['bandeira'];
        $numero_final = $_POST['numero_final'];
        $limite = str_replace(['.', ','], ['', '.'], $_POST['limite']);
        $dia_fechamento = $_POST['dia_fechamento'];
        $dia_vencimento = $_POST['dia_vencimento'];
        $cor = $_POST['cor'] ?? '#6a0dad';
        
        $sql = "INSERT INTO cartoes (id_usuario, nome, bandeira, numero_final, limite, dia_fechamento, dia_vencimento, cor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $nome, $bandeira, $numero_final, $limite, $dia_fechamento, $dia_vencimento, $cor]);
        
        header('Location: cartoes.php?success=created');
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $bandeira = $_POST['bandeira'];
        $numero_final = $_POST['numero_final'];
        $limite = str_replace(['.', ','], ['', '.'], $_POST['limite']);
        $dia_fechamento = $_POST['dia_fechamento'];
        $dia_vencimento = $_POST['dia_vencimento'];
        $status = $_POST['status'];
        $cor = $_POST['cor'] ?? '#6a0dad';
        
        $sql = "UPDATE cartoes SET nome = ?, bandeira = ?, numero_final = ?, limite = ?, 
                dia_fechamento = ?, dia_vencimento = ?, status = ?, cor = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $bandeira, $numero_final, $limite, $dia_fechamento, $dia_vencimento, $status, $cor, $id, $user_id]);
        
        header('Location: cartoes.php?success=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $sql = "DELETE FROM cartoes WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        header('Location: cartoes.php?success=deleted');
        exit;
    }
}

// Buscar cartões
$sql = "SELECT * FROM cartoes WHERE id_usuario = ? ORDER BY status, nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cartoes = $stmt->fetchAll();

// Obter moeda do usuário
$stmt = $pdo->prepare("SELECT moeda_base FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$moeda = $stmt->fetchColumn() ?: 'BRL';

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            $msg = match($_GET['success']) {
                'created' => 'Cartão cadastrado com sucesso!',
                'updated' => 'Cartão atualizado com sucesso!',
                'deleted' => 'Cartão excluído com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="text-white mb-0"><i class="fas fa-credit-card me-2"></i>Cartões de Crédito</h2>
            <p class="text-white-50">Gerencie seus cartões e acompanhe os limites</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Novo Cartão
            </button>
        </div>
    </div>

    <div class="row">
        <?php if (empty($cartoes)): ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                        <h5>Nenhum cartão cadastrado</h5>
                        <p class="text-muted">Adicione seus cartões de crédito para começar o controle</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus me-2"></i>Cadastrar Primeiro Cartão
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($cartoes as $cartao): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100" style="border-left: 4px solid <?= htmlspecialchars($cartao['cor']) ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($cartao['nome']) ?></h5>
                                    <span class="badge <?= $cartao['status'] === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($cartao['status']) ?>
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick='editCard(<?= json_encode($cartao) ?>)'><i class="fas fa-edit me-2"></i>Editar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteCard(<?= $cartao['id'] ?>)"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fab fa-cc-<?= strtolower($cartao['bandeira']) ?> fa-2x"></i>
                                    <span class="text-muted">•••• <?= htmlspecialchars($cartao['numero_final']) ?></span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Limite</small>
                                    <strong><?= format_currency($cartao['limite'], $moeda) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Disponível</small>
                                    <strong class="text-success"><?= format_currency($cartao['limite'], $moeda) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Fechamento</small>
                                    <strong>Dia <?= $cartao['dia_fechamento'] ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Vencimento</small>
                                    <strong>Dia <?= $cartao['dia_vencimento'] ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Criar Cartão -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cartão</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Nubank Roxo">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bandeira</label>
                            <select name="bandeira" class="form-select" required>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="elo">Elo</option>
                                <option value="amex">American Express</option>
                                <option value="diners">Diners</option>
                                <option value="discover">Discover</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Últimos 4 Dígitos</label>
                            <input type="text" name="numero_final" class="form-control" maxlength="4" pattern="[0-9]{4}" placeholder="1234">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Limite</label>
                        <input type="text" name="limite" class="form-control money-input" required placeholder="0,00">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dia Fechamento</label>
                            <input type="number" name="dia_fechamento" class="form-control" min="1" max="31" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dia Vencimento</label>
                            <input type="number" name="dia_vencimento" class="form-control" min="1" max="31" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor Identificação</label>
                        <input type="color" name="cor" class="form-control form-control-color" value="#6a0dad">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cartão -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cartão</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bandeira</label>
                            <select name="bandeira" id="edit_bandeira" class="form-select" required>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="elo">Elo</option>
                                <option value="amex">American Express</option>
                                <option value="diners">Diners</option>
                                <option value="discover">Discover</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Últimos 4 Dígitos</label>
                            <input type="text" name="numero_final" id="edit_numero_final" class="form-control" maxlength="4" pattern="[0-9]{4}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Limite</label>
                        <input type="text" name="limite" id="edit_limite" class="form-control money-input" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dia Fechamento</label>
                            <input type="number" name="dia_fechamento" id="edit_dia_fechamento" class="form-control" min="1" max="31" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dia Vencimento</label>
                            <input type="number" name="dia_vencimento" id="edit_dia_vencimento" class="form-control" min="1" max="31" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="ativo">Ativo</option>
                                <option value="bloqueado">Bloqueado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" id="edit_cor" class="form-control form-control-color">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Cartão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este cartão?</p>
                    <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Formatação monetária
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

function editCard(card) {
    document.getElementById('edit_id').value = card.id;
    document.getElementById('edit_nome').value = card.nome;
    document.getElementById('edit_bandeira').value = card.bandeira;
    document.getElementById('edit_numero_final').value = card.numero_final || '';
    
    const limiteFormatado = parseFloat(card.limite).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('edit_limite').value = limiteFormatado;
    
    document.getElementById('edit_dia_fechamento').value = card.dia_fechamento;
    document.getElementById('edit_dia_vencimento').value = card.dia_vencimento;
    document.getElementById('edit_status').value = card.status;
    document.getElementById('edit_cor').value = card.cor || '#6a0dad';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteCard(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
