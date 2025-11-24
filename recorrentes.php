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
            'module' => 'recorrentes',
            'action' => $action,
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($action === 'create') {
        $descricao = $_POST['descricao'];
        $tipo = $_POST['tipo'];
        $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $id_categoria = $_POST['id_categoria'] ?: null;
        $frequencia = $_POST['frequencia'];
        $dia_vencimento = $_POST['dia_vencimento'];
        $data_inicio = $_POST['data_inicio'];
        $data_fim = $_POST['data_fim'] ?: null;
        
        $sql = "INSERT INTO contas_recorrentes (id_usuario, descricao, valor, dia_vencimento, id_categoria, frequencia, data_inicio, data_fim, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativa')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $descricao, $valor, $dia_vencimento, $id_categoria, $frequencia, $data_inicio, $data_fim]);
        
        header('Location: recorrentes.php?success=created');
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $descricao = $_POST['descricao'];
        $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $id_categoria = $_POST['id_categoria'] ?: null;
        $frequencia = $_POST['frequencia'];
        $dia_vencimento = $_POST['dia_vencimento'];
        $data_fim = $_POST['data_fim'] ?: null;
        $status = $_POST['status'];
        
        $sql = "UPDATE contas_recorrentes SET descricao = ?, valor = ?, id_categoria = ?, 
                frequencia = ?, dia_vencimento = ?, data_fim = ?, status = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$descricao, $valor, $id_categoria, $frequencia, $dia_vencimento, $data_fim, $status, $id, $user_id]);
        
        header('Location: recorrentes.php?success=updated');
        exit;
    }
    
    if ($action === 'gerar') {
        $id = $_POST['id'];
        
        // Buscar dados da recorrência
        $sql = "SELECT * FROM contas_recorrentes WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        $recorrente = $stmt->fetch();
        
        if ($recorrente && $recorrente['status'] === 'ativa') {
            // Criar lançamento (assumir despesa por padrão)
            $data_hoje = date('Y-m-d');
            $sql = "INSERT INTO lancamentos (id_usuario, descricao, tipo, valor, moeda, id_categoria, data) 
                    VALUES (?, ?, 'despesa', ?, 'BRL', ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, 
                $recorrente['descricao'] . ' (Recorrente)', 
                $recorrente['valor'], 
                $recorrente['id_categoria'], 
                $data_hoje
            ]);
            
            // Atualizar última geração
            $sql = "UPDATE contas_recorrentes SET ultima_geracao = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data_hoje, $id]);
            
            header('Location: recorrentes.php?success=generated');
            exit;
        }
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $sql = "DELETE FROM contas_recorrentes WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $user_id]);
        
        header('Location: recorrentes.php?success=deleted');
        exit;
    }
}

// Buscar recorrentes
$sql = "SELECT cr.*, c.nome as categoria_nome, c.icone, c.cor
        FROM contas_recorrentes cr
        LEFT JOIN categorias c ON cr.id_categoria = c.id
        WHERE cr.id_usuario = ?
        ORDER BY cr.status DESC, cr.dia_vencimento";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$recorrentes = $stmt->fetchAll();

// Buscar categorias
$sql = "SELECT * FROM categorias WHERE id_usuario = ? ORDER BY tipo, nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

// Obter moeda do usuário
$stmt = $pdo->prepare("SELECT moeda_base FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$moeda_usuario = $stmt->fetchColumn() ?: 'BRL';

// Calcular totais (todas são despesas nesta tabela)
$total_receitas = 0;
$total_despesas = 0;
foreach ($recorrentes as $rec) {
    if ($rec['status'] === 'ativa') {
        $total_despesas += $rec['valor'];
    }
}

require 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            $msg = match($_GET['success']) {
                'created' => 'Lançamento recorrente criado com sucesso!',
                'updated' => 'Lançamento recorrente atualizado com sucesso!',
                'generated' => 'Lançamento gerado com sucesso!',
                'deleted' => 'Lançamento recorrente excluído com sucesso!',
                default => 'Operação realizada com sucesso!'
            };
            echo $msg;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-0"><i class="fas fa-sync-alt me-2"></i>Lançamentos Recorrentes</h2>
            <p class="text-white-50">Automatize receitas e despesas que se repetem</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Nova Recorrência
            </button>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Receitas Mensais</h6>
                    <h3 class="mb-0 text-success fw-bold"><?= format_currency($total_receitas, $moeda_usuario) ?></h3>
                    <small class="text-muted">Automáticas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Despesas Mensais</h6>
                    <h3 class="mb-0 text-danger fw-bold"><?= format_currency($total_despesas, $moeda_usuario) ?></h3>
                    <small class="text-muted">Automáticas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Saldo Mensal</h6>
                    <h3 class="mb-0 fw-bold <?= ($total_receitas - $total_despesas) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= format_currency($total_receitas - $total_despesas, $moeda_usuario) ?>
                    </h3>
                    <small class="text-muted">Recorrente</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Recorrentes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lançamentos Configurados</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recorrentes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-sync-alt fa-4x text-muted mb-3"></i>
                            <h5>Nenhum lançamento recorrente</h5>
                            <p class="text-muted">Configure receitas e despesas que se repetem automaticamente</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="fas fa-plus me-2"></i>Criar Primeiro Lançamento
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Categoria</th>
                                        <th>Valor</th>
                                        <th>Frequência</th>
                                        <th>Dia</th>
                                        <th>Última Geração</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recorrentes as $rec): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $rec['status'] === 'ativa' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ucfirst($rec['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($rec['descricao']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge <?= $rec['tipo'] === 'receita' ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= ucfirst($rec['tipo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($rec['categoria_nome']): ?>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div style="width: 25px; height: 25px; background: <?= htmlspecialchars($rec['cor']) ?>; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas <?= htmlspecialchars($rec['icone']) ?> text-white" style="font-size: 12px;"></i>
                                                        </div>
                                                        <span><?= htmlspecialchars($rec['categoria_nome']) ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="<?= $rec['tipo'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                                                    <?= format_currency($rec['valor'], $rec['moeda']) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php
                                                $freq = match($rec['frequencia']) {
                                                    'mensal' => 'Mensal',
                                                    'semanal' => 'Semanal',
                                                    'quinzenal' => 'Quinzenal',
                                                    'anual' => 'Anual',
                                                    default => $rec['frequencia']
                                                };
                                                echo $freq;
                                                ?>
                                            </td>
                                            <td>Dia <?= $rec['dia_vencimento'] ?></td>
                                            <td>
                                                <?= $rec['ultima_geracao'] ? date('d/m/Y', strtotime($rec['ultima_geracao'])) : '<span class="text-muted">Nunca</span>' ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-success" onclick="gerarLancamento(<?= $rec['id'] ?>)" title="Gerar Lançamento Agora">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" onclick='editRecorrente(<?= json_encode($rec) ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteRecorrente(<?= $rec['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Recorrência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" required placeholder="Ex: Aluguel, Condomínio, Internet">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" class="form-control money-input" required placeholder="0,00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="id_categoria" class="form-select">
                                <option value="">Sem categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['tipo'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Frequência</label>
                            <select name="frequencia" class="form-select" required>
                                <option value="mensal">Mensal</option>
                                <option value="semanal">Semanal</option>
                                <option value="quinzenal">Quinzenal</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" class="form-control" min="1" max="31" required value="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Fim (opcional)</label>
                        <input type="date" name="data_fim" class="form-control">
                        <small class="text-muted">Deixe em branco para recorrência indefinida</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Recorrência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" id="edit_descricao" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" id="edit_valor" class="form-control money-input" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="id_categoria" id="edit_categoria" class="form-select">
                                <option value="">Sem categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['tipo'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Frequência</label>
                            <select name="frequencia" id="edit_frequencia" class="form-select" required>
                                <option value="mensal">Mensal</option>
                                <option value="semanal">Semanal</option>
                                <option value="quinzenal">Quinzenal</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dia</label>
                            <input type="number" name="dia_vencimento" id="edit_dia" class="form-control" min="1" max="31" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="ativa">Ativa</option>
                                <option value="pausada">Pausada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" id="edit_data_fim" class="form-control">
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

<!-- Modal Gerar -->
<div class="modal fade" id="gerarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="gerar">
                <input type="hidden" name="id" id="gerar_id">
                <div class="modal-body">
                    <p>Deseja gerar o lançamento recorrente agora?</p>
                    <p class="text-muted small">Isso criará um novo lançamento com a data de hoje.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Gerar</button>
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
                <h5 class="modal-title">Excluir Recorrência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir esta recorrência?</p>
                    <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Os lançamentos já gerados não serão afetados.</p>
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

function editRecorrente(rec) {
    document.getElementById('edit_id').value = rec.id;
    document.getElementById('edit_descricao').value = rec.descricao;
    
    const valorFormatado = parseFloat(rec.valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('edit_valor').value = valorFormatado;
    
    document.getElementById('edit_categoria').value = rec.id_categoria || '';
    document.getElementById('edit_frequencia').value = rec.frequencia;
    document.getElementById('edit_dia').value = rec.dia_vencimento;
    document.getElementById('edit_status').value = rec.status;
    document.getElementById('edit_data_fim').value = rec.data_fim || '';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function gerarLancamento(id) {
    document.getElementById('gerar_id').value = id;
    new bootstrap.Modal(document.getElementById('gerarModal')).show();
}

function deleteRecorrente(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
