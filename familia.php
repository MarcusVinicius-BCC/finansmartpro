<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'convidar') {
            // Verificar se usuário existe
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['email']]);
            $usuario_convidado = $stmt->fetch();
            
            if ($usuario_convidado) {
                // Verificar se já existe
                $sql = "SELECT id FROM membros_familia WHERE id_proprietario = ? AND id_membro = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $usuario_convidado['id']]);
                
                if (!$stmt->fetch()) {
                    $sql = "INSERT INTO membros_familia (id_proprietario, id_membro, permissoes, status) 
                            VALUES (?, ?, ?, 'pendente')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $usuario_convidado['id'], $_POST['permissoes']]);
                    
                    // Criar notificação
                    $sql = "INSERT INTO alertas (id_usuario, tipo, titulo, mensagem) 
                            VALUES (?, 'sistema', 'Convite para Família', 'Você foi convidado para compartilhar finanças')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$usuario_convidado['id']]);
                    
                    header('Location: familia.php?success=convidado');
                    exit;
                } else {
                    header('Location: familia.php?error=ja_existe');
                    exit;
                }
            } else {
                header('Location: familia.php?error=usuario_nao_encontrado');
                exit;
            }
        }
        
        if ($_POST['action'] === 'aceitar') {
            $sql = "UPDATE membros_familia SET status = 'ativo' WHERE id = ? AND id_membro = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id'], $user_id]);
            
            header('Location: familia.php?success=aceito');
            exit;
        }
        
        if ($_POST['action'] === 'recusar') {
            $sql = "UPDATE membros_familia SET status = 'recusado' WHERE id = ? AND id_membro = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id'], $user_id]);
            
            header('Location: familia.php?success=recusado');
            exit;
        }
        
        if ($_POST['action'] === 'remover') {
            $sql = "DELETE FROM membros_familia WHERE id = ? AND id_proprietario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id'], $user_id]);
            
            header('Location: familia.php?success=removido');
            exit;
        }
        
        if ($_POST['action'] === 'alterar_permissoes') {
            $sql = "UPDATE membros_familia SET permissoes = ? WHERE id = ? AND id_proprietario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['permissoes'], $_POST['id'], $user_id]);
            
            header('Location: familia.php?success=permissoes_alteradas');
            exit;
        }
    }
}

// Buscar membros da família (onde sou proprietário)
$sql = "SELECT mf.*, u.nome, u.email
        FROM membros_familia mf
        JOIN usuarios u ON mf.id_membro = u.id
        WHERE mf.id_proprietario = ?
        ORDER BY mf.status, u.nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$membros = $stmt->fetchAll();

// Buscar convites recebidos
$sql = "SELECT mf.*, u.nome, u.email 
        FROM membros_familia mf
        JOIN usuarios u ON mf.id_proprietario = u.id
        WHERE mf.id_membro = ? AND mf.status = 'pendente'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$convites = $stmt->fetchAll();

// Buscar famílias das quais faço parte
$sql = "SELECT mf.*, u.nome, u.email 
        FROM membros_familia mf
        JOIN usuarios u ON mf.id_proprietario = u.id
        WHERE mf.id_membro = ? AND mf.status = 'ativo'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$familias = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-users me-2"></i>Gestão Familiar</h2>
            <p class="text-white-50">Compartilhe suas finanças com a família</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $messages = [
                'convidado' => 'Convite enviado com sucesso!',
                'aceito' => 'Convite aceito! Agora você tem acesso às finanças.',
                'recusado' => 'Convite recusado.',
                'removido' => 'Membro removido da família.',
                'permissoes_alteradas' => 'Permissões atualizadas!'
            ];
            echo '<i class="fas fa-check-circle me-2"></i>' . ($messages[$_GET['success']] ?? 'Operação concluída!');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            $errors = [
                'usuario_nao_encontrado' => 'Usuário não encontrado. Verifique o email.',
                'ja_existe' => 'Este usuário já está na sua família.'
            ];
            echo '<i class="fas fa-times-circle me-2"></i>' . ($errors[$_GET['error']] ?? 'Erro desconhecido.');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($convites)): ?>
        <!-- Convites Pendentes -->
        <div class="alert alert-warning alert-dismissible">
            <h5><i class="fas fa-envelope me-2"></i>Você tem <?= count($convites) ?> convite(s) pendente(s)!</h5>
            <?php foreach ($convites as $convite): ?>
                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                    <div>
                        <strong><?= htmlspecialchars($convite['nome']) ?></strong> 
                        (<?= htmlspecialchars($convite['email']) ?>) quer compartilhar finanças com você.
                        <br><small class="text-muted">Permissões: <?= ucfirst($convite['permissoes']) ?></small>
                    </div>
                    <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="aceitar">
                            <input type="hidden" name="id" value="<?= $convite['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Aceitar
                            </button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="recusar">
                            <input type="hidden" name="id" value="<?= $convite['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-times"></i> Recusar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Convidar Membro -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Convidar Membro</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="convidar">
                        
                        <div class="mb-3">
                            <label class="form-label">Email do Membro</label>
                            <input type="email" name="email" class="form-control" required placeholder="exemplo@email.com">
                            <small class="text-muted">O usuário deve estar cadastrado no sistema</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nível de Permissão</label>
                            <select name="permissoes" class="form-select" required>
                                <option value="visualizacao">Visualização - Apenas ver dados</option>
                                <option value="edicao">Edição - Ver e editar lançamentos</option>
                                <option value="total">Total - Controle completo</option>
                            </select>
                        </div>

                        <div class="alert alert-info small">
                            <strong><i class="fas fa-info-circle me-1"></i>Como funciona:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Visualização:</strong> Ver dashboard, relatórios e lançamentos</li>
                                <li><strong>Edição:</strong> Criar/editar lançamentos e categorias</li>
                                <li><strong>Total:</strong> Acesso completo (contas, metas, etc.)</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Convite
                        </button>
                    </form>
                </div>
            </div>

            <!-- Famílias das quais faço parte -->
            <?php if (!empty($familias)): ?>
                <div class="card shadow mt-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-home me-2"></i>Famílias que Participo</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($familias as $fam): ?>
                            <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                <div>
                                    <strong><?= htmlspecialchars($fam['nome']) ?></strong>
                                    <br><small class="text-muted">
                                        <?= htmlspecialchars($fam['email']) ?> • 
                                        <span class="badge bg-info"><?= ucfirst($fam['permissoes']) ?></span>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Meus Membros -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Membros da Minha Família</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($membros)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum membro cadastrado ainda</p>
                            <p class="small text-muted">Convide familiares para compartilhar finanças!</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($membros as $membro): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 <?= $membro['status'] === 'ativo' ? 'border-success' : 'border-warning' ?>">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                         style="width: 60px; height: 60px; font-size: 24px;">
                                                        <?= strtoupper(substr($membro['nome'], 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($membro['nome']) ?></h6>
                                                    <p class="small text-muted mb-2">
                                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($membro['email']) ?>
                                                    </p>
                                                    
                                                    <div class="mb-2">
                                                        <span class="badge bg-<?= $membro['status'] === 'ativo' ? 'success' : ($membro['status'] === 'pendente' ? 'warning' : 'secondary') ?>">
                                                            <?= ucfirst($membro['status']) ?>
                                                        </span>
                                                        <span class="badge bg-info ms-1">
                                                            <i class="fas fa-key me-1"></i><?= ucfirst($membro['permissoes']) ?>
                                                        </span>
                                                    </div>

                                                    <?php if ($membro['status'] === 'ativo'): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-primary btn-sm" 
                                                                    onclick="alterarPermissoes(<?= $membro['id'] ?>, '<?= $membro['permissoes'] ?>')">
                                                                <i class="fas fa-edit"></i> Permissões
                                                            </button>
                                                            <form method="post" style="display:inline;" 
                                                                  onsubmit="return confirm('Remover este membro?')">
                                                                <input type="hidden" name="action" value="remover">
                                                                <input type="hidden" name="id" value="<?= $membro['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                    <i class="fas fa-trash"></i> Remover
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Explicação de Permissões -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Entenda as Permissões</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-eye fa-3x text-primary mb-2"></i>
                                <h6>Visualização</h6>
                                <small class="text-muted">
                                    Ver dashboard, gráficos, relatórios e lançamentos. 
                                    <strong>Não pode editar</strong> nada.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-edit fa-3x text-warning mb-2"></i>
                                <h6>Edição</h6>
                                <small class="text-muted">
                                    Criar e editar <strong>lançamentos</strong>, 
                                    <strong>categorias</strong> e <strong>orçamentos</strong>.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-crown fa-3x text-danger mb-2"></i>
                                <h6>Total</h6>
                                <small class="text-muted">
                                    Controle completo: contas, cartões, 
                                    metas, investimentos e <strong>tudo mais</strong>.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Alterar Permissões -->
<div class="modal fade" id="modalPermissoes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Alterar Permissões</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="alterar_permissoes">
                <input type="hidden" name="id" id="permissoes_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Novo Nível de Permissão</label>
                        <select name="permissoes" id="permissoes_select" class="form-select" required>
                            <option value="visualizacao">Visualização</option>
                            <option value="edicao">Edição</option>
                            <option value="total">Total</option>
                        </select>
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

<script>
function alterarPermissoes(id, permissaoAtual) {
    document.getElementById('permissoes_id').value = id;
    document.getElementById('permissoes_select').value = permissaoAtual;
    new bootstrap.Modal(document.getElementById('modalPermissoes')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
