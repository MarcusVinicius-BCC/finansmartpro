<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$aba_ativa = $_GET['aba'] ?? 'pagar';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    // Contas a Pagar
    if ($action === 'criar_pagar') {
        $sql = "INSERT INTO contas_pagar (id_usuario, descricao, valor, vencimento, id_categoria, fornecedor, num_documento, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $_POST['descricao'],
            $_POST['valor'],
            $_POST['vencimento'],
            $_POST['id_categoria'] ?: null,
            $_POST['fornecedor'] ?: null,
            $_POST['num_documento'] ?: null,
            $_POST['observacoes'] ?: null
        ]);
        header('Location: contas_pagar_receber.php?aba=pagar&success=criado');
        exit;
    }
    
    if ($action === 'editar_pagar') {
        $sql = "UPDATE contas_pagar SET descricao = ?, valor = ?, vencimento = ?, id_categoria = ?, fornecedor = ?, num_documento = ?, observacoes = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['descricao'],
            $_POST['valor'],
            $_POST['vencimento'],
            $_POST['id_categoria'] ?: null,
            $_POST['fornecedor'] ?: null,
            $_POST['num_documento'] ?: null,
            $_POST['observacoes'] ?: null,
            $_POST['id'],
            $user_id
        ]);
        header('Location: contas_pagar_receber.php?aba=pagar&success=atualizado');
        exit;
    }
    
    if ($action === 'pagar') {
        $pdo->beginTransaction();
        try {
            // Buscar conta
            $sql = "SELECT * FROM contas_pagar WHERE id = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id'], $user_id]);
            $conta = $stmt->fetch();
            
            if ($conta) {
                // Marcar como paga
                $sql = "UPDATE contas_pagar SET status = 'pago', data_pagamento = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['data_pagamento'], $_POST['id']]);
                
                // Criar lançamento
                $sql = "INSERT INTO lancamentos (id_usuario, descricao, valor, tipo, data, id_categoria, moeda) 
                        VALUES (?, ?, ?, 'despesa', ?, ?, 'BRL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $user_id,
                    $conta['descricao'] . ' (Pago)',
                    $conta['valor'],
                    $_POST['data_pagamento'],
                    $conta['id_categoria']
                ]);
                
                $pdo->commit();
                header('Location: contas_pagar_receber.php?aba=pagar&success=pago');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: contas_pagar_receber.php?aba=pagar&error=pagamento');
            exit;
        }
    }
    
    if ($action === 'excluir_pagar') {
        $sql = "DELETE FROM contas_pagar WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id'], $user_id]);
        header('Location: contas_pagar_receber.php?aba=pagar&success=excluido');
        exit;
    }
    
    // Contas a Receber
    if ($action === 'criar_receber') {
        $sql = "INSERT INTO contas_receber (id_usuario, descricao, valor, vencimento, id_categoria, cliente, num_documento, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $_POST['descricao'],
            $_POST['valor'],
            $_POST['vencimento'],
            $_POST['id_categoria'] ?: null,
            $_POST['cliente'] ?: null,
            $_POST['num_documento'] ?: null,
            $_POST['observacoes'] ?: null
        ]);
        header('Location: contas_pagar_receber.php?aba=receber&success=criado');
        exit;
    }
    
    if ($action === 'editar_receber') {
        $sql = "UPDATE contas_receber SET descricao = ?, valor = ?, vencimento = ?, id_categoria = ?, cliente = ?, num_documento = ?, observacoes = ? 
                WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['descricao'],
            $_POST['valor'],
            $_POST['vencimento'],
            $_POST['id_categoria'] ?: null,
            $_POST['cliente'] ?: null,
            $_POST['num_documento'] ?: null,
            $_POST['observacoes'] ?: null,
            $_POST['id'],
            $user_id
        ]);
        header('Location: contas_pagar_receber.php?aba=receber&success=atualizado');
        exit;
    }
    
    if ($action === 'receber') {
        $pdo->beginTransaction();
        try {
            // Buscar conta
            $sql = "SELECT * FROM contas_receber WHERE id = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id'], $user_id]);
            $conta = $stmt->fetch();
            
            if ($conta) {
                // Marcar como recebida
                $sql = "UPDATE contas_receber SET status = 'recebido', data_recebimento = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['data_recebimento'], $_POST['id']]);
                
                // Criar lançamento
                $sql = "INSERT INTO lancamentos (id_usuario, descricao, valor, tipo, data, id_categoria, moeda) 
                        VALUES (?, ?, ?, 'receita', ?, ?, 'BRL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $user_id,
                    $conta['descricao'] . ' (Recebido)',
                    $conta['valor'],
                    $_POST['data_recebimento'],
                    $conta['id_categoria']
                ]);
                
                $pdo->commit();
                header('Location: contas_pagar_receber.php?aba=receber&success=recebido');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: contas_pagar_receber.php?aba=receber&error=recebimento');
            exit;
        }
    }
    
    if ($action === 'excluir_receber') {
        $sql = "DELETE FROM contas_receber WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id'], $user_id]);
        header('Location: contas_pagar_receber.php?aba=receber&success=excluido');
        exit;
    }
}

// Buscar contas a pagar
$sql = "SELECT cp.*, c.nome as categoria_nome, c.cor as categoria_cor,
        CASE 
            WHEN cp.status = 'pago' THEN 'pago'
            WHEN cp.vencimento < CURDATE() THEN 'atrasado'
            ELSE 'pendente'
        END as status_real
        FROM contas_pagar cp
        LEFT JOIN categorias c ON cp.id_categoria = c.id
        WHERE cp.id_usuario = ?
        ORDER BY cp.vencimento ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$contas_pagar = $stmt->fetchAll();

// Buscar contas a receber
$sql = "SELECT cr.*, c.nome as categoria_nome, c.cor as categoria_cor,
        CASE 
            WHEN cr.status = 'recebido' THEN 'recebido'
            WHEN cr.vencimento < CURDATE() THEN 'atrasado'
            ELSE 'pendente'
        END as status_real
        FROM contas_receber cr
        LEFT JOIN categorias c ON cr.id_categoria = c.id
        WHERE cr.id_usuario = ?
        ORDER BY cr.vencimento ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$contas_receber = $stmt->fetchAll();

// Buscar categorias
$sql = "SELECT * FROM categorias WHERE id_usuario = ? OR id_usuario IS NULL ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

// Calcular resumos
$total_pagar_pendente = array_sum(array_column(array_filter($contas_pagar, fn($c) => $c['status_real'] === 'pendente'), 'valor'));
$total_pagar_atrasado = array_sum(array_column(array_filter($contas_pagar, fn($c) => $c['status_real'] === 'atrasado'), 'valor'));
$total_receber_pendente = array_sum(array_column(array_filter($contas_receber, fn($c) => $c['status_real'] === 'pendente'), 'valor'));
$total_receber_atrasado = array_sum(array_column(array_filter($contas_receber, fn($c) => $c['status_real'] === 'atrasado'), 'valor'));

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Contas a Pagar/Receber</h2>
            <p class="text-white-50">Controle títulos futuros, vencimentos e status de pagamento</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $messages = [
                'criado' => 'Conta criada com sucesso!',
                'atualizado' => 'Conta atualizada com sucesso!',
                'pago' => 'Conta marcada como paga e lançamento criado!',
                'recebido' => 'Conta marcada como recebida e lançamento criado!',
                'excluido' => 'Conta excluída com sucesso!'
            ];
            echo '<i class="fas fa-check-circle me-2"></i>' . ($messages[$_GET['success']] ?? 'Operação realizada!');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>Erro ao processar operação. Tente novamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-danger text-white shadow">
                <div class="card-body">
                    <h6 class="text-white-50">A Pagar (Pendente)</h6>
                    <h3 class="mb-0">R$ <?= number_format($total_pagar_pendente, 2, ',', '.') ?></h3>
                    <small><i class="fas fa-clock me-1"></i><?= count(array_filter($contas_pagar, fn($c) => $c['status_real'] === 'pendente')) ?> conta(s)</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body">
                    <h6 class="text-dark-50">A Pagar (Atrasado)</h6>
                    <h3 class="mb-0">R$ <?= number_format($total_pagar_atrasado, 2, ',', '.') ?></h3>
                    <small><i class="fas fa-exclamation-triangle me-1"></i><?= count(array_filter($contas_pagar, fn($c) => $c['status_real'] === 'atrasado')) ?> conta(s)</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h6 class="text-white-50">A Receber (Pendente)</h6>
                    <h3 class="mb-0">R$ <?= number_format($total_receber_pendente, 2, ',', '.') ?></h3>
                    <small><i class="fas fa-clock me-1"></i><?= count(array_filter($contas_receber, fn($c) => $c['status_real'] === 'pendente')) ?> conta(s)</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <h6 class="text-white-50">A Receber (Atrasado)</h6>
                    <h3 class="mb-0">R$ <?= number_format($total_receber_atrasado, 2, ',', '.') ?></h3>
                    <small><i class="fas fa-exclamation-circle me-1"></i><?= count(array_filter($contas_receber, fn($c) => $c['status_real'] === 'atrasado')) ?> conta(s)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-4" id="contasTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $aba_ativa === 'pagar' ? 'active' : '' ?>" id="pagar-tab" data-bs-toggle="tab" data-bs-target="#pagar" type="button">
                <i class="fas fa-arrow-down me-2"></i>Contas a Pagar
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $aba_ativa === 'receber' ? 'active' : '' ?>" id="receber-tab" data-bs-toggle="tab" data-bs-target="#receber" type="button">
                <i class="fas fa-arrow-up me-2"></i>Contas a Receber
            </button>
        </li>
    </ul>

    <div class="tab-content" id="contasTabsContent">
        <!-- Contas a Pagar -->
        <div class="tab-pane fade <?= $aba_ativa === 'pagar' ? 'show active' : '' ?>" id="pagar" role="tabpanel">
            <div class="card shadow">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Contas a Pagar</h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriarPagar">
                        <i class="fas fa-plus me-1"></i>Nova Conta
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($contas_pagar)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma conta a pagar cadastrada</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vencimento</th>
                                        <th>Descrição</th>
                                        <th>Fornecedor</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contas_pagar as $cp): ?>
                                        <tr class="<?= $cp['status_real'] === 'atrasado' ? 'table-warning' : '' ?>">
                                            <td>
                                                <?= date('d/m/Y', strtotime($cp['vencimento'])) ?>
                                                <?php if ($cp['status_real'] === 'atrasado'): ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Atrasado</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($cp['descricao']) ?></strong>
                                                <?php if ($cp['categoria_nome']): ?>
                                                    <br><span class="badge" style="background-color: <?= $cp['categoria_cor'] ?>"><?= $cp['categoria_nome'] ?></span>
                                                <?php endif; ?>
                                                <?php if ($cp['num_documento']): ?>
                                                    <br><small class="text-muted">Doc: <?= htmlspecialchars($cp['num_documento']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($cp['fornecedor'] ?? '-') ?></td>
                                            <td class="fw-bold">R$ <?= number_format($cp['valor'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'pendente' => 'warning',
                                                    'atrasado' => 'danger',
                                                    'pago' => 'success'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $badges[$cp['status_real']] ?>">
                                                    <?= ucfirst($cp['status_real']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($cp['status'] !== 'pago'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="pagarConta(<?= $cp['id'] ?>, '<?= htmlspecialchars($cp['descricao']) ?>')">
                                                        <i class="fas fa-check"></i> Pagar
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" onclick="editarContaPagar(<?= htmlspecialchars(json_encode($cp)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Excluir esta conta?')">
                                                    <input type="hidden" name="action" value="excluir_pagar">
                                                    <input type="hidden" name="id" value="<?= $cp['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

        <!-- Contas a Receber -->
        <div class="tab-pane fade <?= $aba_ativa === 'receber' ? 'show active' : '' ?>" id="receber" role="tabpanel">
            <div class="card shadow">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Contas a Receber</h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriarReceber">
                        <i class="fas fa-plus me-1"></i>Nova Conta
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($contas_receber)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-hand-holding-usd fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma conta a receber cadastrada</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vencimento</th>
                                        <th>Descrição</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contas_receber as $cr): ?>
                                        <tr class="<?= $cr['status_real'] === 'atrasado' ? 'table-warning' : '' ?>">
                                            <td>
                                                <?= date('d/m/Y', strtotime($cr['vencimento'])) ?>
                                                <?php if ($cr['status_real'] === 'atrasado'): ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Atrasado</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($cr['descricao']) ?></strong>
                                                <?php if ($cr['categoria_nome']): ?>
                                                    <br><span class="badge" style="background-color: <?= $cr['categoria_cor'] ?>"><?= $cr['categoria_nome'] ?></span>
                                                <?php endif; ?>
                                                <?php if ($cr['num_documento']): ?>
                                                    <br><small class="text-muted">Doc: <?= htmlspecialchars($cr['num_documento']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($cr['cliente'] ?? '-') ?></td>
                                            <td class="fw-bold text-success">R$ <?= number_format($cr['valor'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'pendente' => 'warning',
                                                    'atrasado' => 'danger',
                                                    'recebido' => 'success'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $badges[$cr['status_real']] ?>">
                                                    <?= ucfirst($cr['status_real']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($cr['status'] !== 'recebido'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="receberConta(<?= $cr['id'] ?>, '<?= htmlspecialchars($cr['descricao']) ?>')">
                                                        <i class="fas fa-check"></i> Receber
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" onclick="editarContaReceber(<?= htmlspecialchars(json_encode($cr)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Excluir esta conta?')">
                                                    <input type="hidden" name="action" value="excluir_receber">
                                                    <input type="hidden" name="id" value="<?= $cr['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

<!-- Modal Criar Conta a Pagar -->
<div class="modal fade" id="modalCriarPagar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nova Conta a Pagar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="criar_pagar">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" required placeholder="Ex: Conta de luz, Aluguel">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vencimento</label>
                            <input type="date" name="vencimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" class="form-control money-input" required placeholder="0,00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fornecedor</label>
                            <input type="text" name="fornecedor" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nº Documento</label>
                            <input type="text" name="num_documento" class="form-control" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="id_categoria" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Conta a Pagar -->
<div class="modal fade" id="modalEditarPagar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Conta a Pagar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="formEditarPagar">
                <input type="hidden" name="action" value="editar_pagar">
                <input type="hidden" name="id" id="edit_pagar_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" id="edit_pagar_descricao" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vencimento</label>
                            <input type="date" name="vencimento" id="edit_pagar_vencimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" id="edit_pagar_valor" class="form-control money-input" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fornecedor</label>
                            <input type="text" name="fornecedor" id="edit_pagar_fornecedor" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nº Documento</label>
                            <input type="text" name="num_documento" id="edit_pagar_doc" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="id_categoria" id="edit_pagar_categoria" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="edit_pagar_obs" class="form-control" rows="2"></textarea>
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

<!-- Modal Criar Conta a Receber -->
<div class="modal fade" id="modalCriarReceber" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nova Conta a Receber</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="criar_receber">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" required placeholder="Ex: Serviço prestado, Venda produto">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vencimento</label>
                            <input type="date" name="vencimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" class="form-control money-input" required placeholder="0,00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nº Documento</label>
                            <input type="text" name="num_documento" class="form-control" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="id_categoria" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Conta a Receber -->
<div class="modal fade" id="modalEditarReceber" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Conta a Receber</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="formEditarReceber">
                <input type="hidden" name="action" value="editar_receber">
                <input type="hidden" name="id" id="edit_receber_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" id="edit_receber_descricao" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vencimento</label>
                            <input type="date" name="vencimento" id="edit_receber_vencimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" id="edit_receber_valor" class="form-control money-input" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente" id="edit_receber_cliente" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nº Documento</label>
                            <input type="text" name="num_documento" id="edit_receber_doc" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="id_categoria" id="edit_receber_categoria" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="edit_receber_obs" class="form-control" rows="2"></textarea>
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

<!-- Modal Pagar -->
<div class="modal fade" id="modalPagar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check me-2"></i>Confirmar Pagamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="pagar">
                <input type="hidden" name="id" id="pagar_id">
                <div class="modal-body">
                    <p>Confirmar pagamento de: <strong id="pagar_descricao"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-1"></i>Um lançamento de despesa será criado automaticamente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Receber -->
<div class="modal fade" id="modalReceber" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check me-2"></i>Confirmar Recebimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="receber">
                <input type="hidden" name="id" id="receber_id">
                <div class="modal-body">
                    <p>Confirmar recebimento de: <strong id="receber_descricao"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Data do Recebimento</label>
                        <input type="date" name="data_recebimento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-1"></i>Um lançamento de receita será criado automaticamente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Recebimento</button>
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
});

// Processar formulários
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

// Funções de modal
function pagarConta(id, descricao) {
    document.getElementById('pagar_id').value = id;
    document.getElementById('pagar_descricao').textContent = descricao;
    new bootstrap.Modal(document.getElementById('modalPagar')).show();
}

function receberConta(id, descricao) {
    document.getElementById('receber_id').value = id;
    document.getElementById('receber_descricao').textContent = descricao;
    new bootstrap.Modal(document.getElementById('modalReceber')).show();
}

function editarContaPagar(conta) {
    document.getElementById('edit_pagar_id').value = conta.id;
    document.getElementById('edit_pagar_descricao').value = conta.descricao;
    document.getElementById('edit_pagar_vencimento').value = conta.vencimento;
    document.getElementById('edit_pagar_valor').value = parseFloat(conta.valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('edit_pagar_fornecedor').value = conta.fornecedor || '';
    document.getElementById('edit_pagar_doc').value = conta.num_documento || '';
    document.getElementById('edit_pagar_categoria').value = conta.id_categoria || '';
    document.getElementById('edit_pagar_obs').value = conta.observacoes || '';
    new bootstrap.Modal(document.getElementById('modalEditarPagar')).show();
}

function editarContaReceber(conta) {
    document.getElementById('edit_receber_id').value = conta.id;
    document.getElementById('edit_receber_descricao').value = conta.descricao;
    document.getElementById('edit_receber_vencimento').value = conta.vencimento;
    document.getElementById('edit_receber_valor').value = parseFloat(conta.valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('edit_receber_cliente').value = conta.cliente || '';
    document.getElementById('edit_receber_doc').value = conta.num_documento || '';
    document.getElementById('edit_receber_categoria').value = conta.id_categoria || '';
    document.getElementById('edit_receber_obs').value = conta.observacoes || '';
    new bootstrap.Modal(document.getElementById('modalEditarReceber')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
