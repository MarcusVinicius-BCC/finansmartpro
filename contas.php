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
            'module' => 'contas',
            'action' => $action,
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($action === 'create') {
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];
        $banco = $_POST['banco'] ?? null;
        $agencia = $_POST['agencia'] ?? null;
        $numero_conta = $_POST['numero_conta'] ?? null;
        $saldo_inicial = str_replace(['.', ','], ['', '.'], $_POST['saldo_inicial']);
        $moeda = $_POST['moeda'];
        $cor = $_POST['cor'] ?? '#6a0dad';
        
        $sql = "INSERT INTO contas_bancarias (id_usuario, nome, tipo, banco, agencia, numero_conta, saldo_inicial, saldo_atual, moeda, cor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $nome, $tipo, $banco, $agencia, $numero_conta, $saldo_inicial, $saldo_inicial, $moeda, $cor]);
        
        header('Location: contas.php?success=created');
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];
        $banco = $_POST['banco'] ?? null;
        $agencia = $_POST['agencia'] ?? null;
        $numero_conta = $_POST['numero_conta'] ?? null;
        $status = $_POST['status'];
        $cor = $_POST['cor'] ?? '#6a0dad';
        
        $sql = "UPDATE contas_bancarias SET nome = ?, tipo = ?, banco = ?, agencia = ?, numero_conta = ?, status = ?, cor = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $tipo, $banco, $agencia, $numero_conta, $status, $cor, $id, $user_id]);
        
        header('Location: contas.php?success=updated');
        exit;
    }
    
    if ($action === 'transfer') {
        $conta_origem = $_POST['conta_origem'];
        $conta_destino = $_POST['conta_destino'];
        $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $descricao = $_POST['descricao'];
        $data = $_POST['data'];
        
        $pdo->beginTransaction();
        try {
            // Debitar conta origem
            $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual - ? WHERE id = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$valor, $conta_origem, $user_id]);
            
            // Creditar conta destino
            $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual + ? WHERE id = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$valor, $conta_destino, $user_id]);
            
            // Registrar transferência
            $sql = "INSERT INTO transferencias (id_usuario, conta_origem, conta_destino, valor, descricao, data_transferencia) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $conta_origem, $conta_destino, $valor, $descricao, $data]);
            
            $pdo->commit();
            header('Location: contas.php?success=transferred');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: contas.php?error=transfer_failed');
            exit;
        }
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $sql = "DELETE FROM contas_bancarias WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        header('Location: contas.php?success=deleted');
        exit;
    }
}

// Buscar contas
$sql = "SELECT * FROM contas_bancarias WHERE id_usuario = ? ORDER BY status DESC, nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$contas = $stmt->fetchAll();

// Calcular totais
$total_geral = 0;
foreach ($contas as $conta) {
    if ($conta['status'] === 'ativa') {
        $total_geral += $conta['saldo_atual'];
    }
}

// Obter moeda do usuário
$stmt = $pdo->prepare("SELECT moeda_base FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$moeda_usuario = $stmt->fetchColumn() ?: 'BRL';

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            $msg = match($_GET['success']) {
                'created' => 'Conta criada com sucesso!',
                'updated' => 'Conta atualizada com sucesso!',
                'transferred' => 'Transferência realizada com sucesso!',
                'deleted' => 'Conta excluída com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php
            $msg = match($_GET['error']) {
                'transfer_failed' => 'Erro ao realizar transferência!',
                default => 'Erro ao processar operação!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-0"><i class="fas fa-university me-2"></i>Contas Bancárias</h2>
            <p class="text-white-50">Gerencie suas contas e realize transferências</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#transferModal">
                <i class="fas fa-exchange-alt me-2"></i>Transferir
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Nova Conta
            </button>
        </div>
    </div>

    <!-- Resumo Total -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Patrimônio Total</h6>
                    <h3 class="mb-0 text-primary fw-bold"><?= format_currency($total_geral, $moeda_usuario) ?></h3>
                    <small class="text-muted"><?= count($contas) ?> conta(s) ativa(s)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (empty($contas)): ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-university fa-4x text-muted mb-3"></i>
                        <h5>Nenhuma conta cadastrada</h5>
                        <p class="text-muted">Adicione suas contas bancárias para começar o controle</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus me-2"></i>Cadastrar Primeira Conta
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($contas as $conta): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100" style="border-left: 4px solid <?= htmlspecialchars($conta['cor']) ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($conta['nome']) ?></h5>
                                    <span class="badge <?= $conta['status'] === 'ativa' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($conta['status']) ?>
                                    </span>
                                    <span class="badge bg-info ms-1"><?= strtoupper($conta['moeda']) ?></span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick='editAccount(<?= json_encode($conta) ?>)'><i class="fas fa-edit me-2"></i>Editar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteAccount(<?= $conta['id'] ?>)"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-building me-1"></i><?= htmlspecialchars($conta['tipo']) ?>
                                    <?php if ($conta['banco']): ?>
                                        • <?= htmlspecialchars($conta['banco']) ?>
                                    <?php endif; ?>
                                </small>
                                <?php if ($conta['agencia'] || $conta['numero_conta']): ?>
                                    <small class="text-muted d-block">
                                        <?php if ($conta['agencia']): ?>
                                            Ag: <?= htmlspecialchars($conta['agencia']) ?>
                                        <?php endif; ?>
                                        <?php if ($conta['numero_conta']): ?>
                                            • Conta: <?= htmlspecialchars($conta['numero_conta']) ?>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block">Saldo Atual</small>
                                <h4 class="mb-0 <?= $conta['saldo_atual'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= format_currency($conta['saldo_atual'], $conta['moeda']) ?>
                                </h4>
                            </div>
                            
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Saldo Inicial: <?= format_currency($conta['saldo_inicial'], $conta['moeda']) ?></span>
                                <span class="<?= ($conta['saldo_atual'] - $conta['saldo_inicial']) >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($conta['saldo_atual'] - $conta['saldo_inicial']) >= 0 ? '+' : '' ?>
                                    <?= format_currency($conta['saldo_atual'] - $conta['saldo_inicial'], $conta['moeda']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Criar Conta -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Conta Bancária</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome da Conta</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Conta Corrente Principal">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select" required>
                                <option value="Corrente">Corrente</option>
                                <option value="Poupança">Poupança</option>
                                <option value="Investimento">Investimento</option>
                                <option value="Pagamento">Pagamento</option>
                                <option value="Dinheiro">Dinheiro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Moeda</label>
                            <select name="moeda" class="form-select" required>
                                <option value="BRL">BRL - Real</option>
                                <option value="USD">USD - Dólar</option>
                                <option value="EUR">EUR - Euro</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Banco</label>
                        <input type="text" name="banco" class="form-control" placeholder="Ex: Nubank, Itaú, Bradesco">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agência</label>
                            <input type="text" name="agencia" class="form-control" placeholder="0001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número da Conta</label>
                            <input type="text" name="numero_conta" class="form-control" placeholder="12345-6">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Inicial</label>
                        <input type="text" name="saldo_inicial" class="form-control money-input" required placeholder="0,00">
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

<!-- Modal Editar Conta -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Conta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome da Conta</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" id="edit_tipo" class="form-select" required>
                                <option value="Corrente">Corrente</option>
                                <option value="Poupança">Poupança</option>
                                <option value="Investimento">Investimento</option>
                                <option value="Pagamento">Pagamento</option>
                                <option value="Dinheiro">Dinheiro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="ativa">Ativa</option>
                                <option value="inativa">Inativa</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Banco</label>
                        <input type="text" name="banco" id="edit_banco" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agência</label>
                            <input type="text" name="agencia" id="edit_agencia" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número da Conta</label>
                            <input type="text" name="numero_conta" id="edit_numero_conta" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor</label>
                        <input type="color" name="cor" id="edit_cor" class="form-control form-control-color">
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

<!-- Modal Transferência -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transferir Entre Contas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="transfer">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Conta Origem</label>
                        <select name="conta_origem" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($contas as $c): ?>
                                <?php if ($c['status'] === 'ativa'): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= format_currency($c['saldo_atual'], $c['moeda']) ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conta Destino</label>
                        <select name="conta_destino" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($contas as $c): ?>
                                <?php if ($c['status'] === 'ativa'): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= format_currency($c['saldo_atual'], $c['moeda']) ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor</label>
                        <input type="text" name="valor" class="form-control money-input" required placeholder="0,00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control" required placeholder="Ex: Transferência para investimento">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Transferir</button>
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
                <h5 class="modal-title">Excluir Conta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir esta conta?</p>
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

function editAccount(account) {
    document.getElementById('edit_id').value = account.id;
    document.getElementById('edit_nome').value = account.nome;
    document.getElementById('edit_tipo').value = account.tipo;
    document.getElementById('edit_status').value = account.status;
    document.getElementById('edit_banco').value = account.banco || '';
    document.getElementById('edit_agencia').value = account.agencia || '';
    document.getElementById('edit_numero_conta').value = account.numero_conta || '';
    document.getElementById('edit_cor').value = account.cor || '#6a0dad';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteAccount(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
