<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar upload de anexo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $id_lancamento = $_POST['id_lancamento'];
    $descricao = $_POST['descricao'] ?? '';
    
    // Verificar se o lançamento pertence ao usuário
    $sql = "SELECT id FROM lancamentos WHERE id = ? AND id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_lancamento, $user_id]);
    
    if ($stmt->fetch()) {
        $arquivo = $_FILES['arquivo'];
        $nome_original = $arquivo['name'];
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        $tamanho = $arquivo['size'];
        
        // Validar tipo de arquivo
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
        if (!in_array($extensao, $tipos_permitidos)) {
            header('Location: anexos.php?error=tipo_invalido');
            exit;
        }
        
        // Validar tamanho (max 5MB)
        if ($tamanho > 5 * 1024 * 1024) {
            header('Location: anexos.php?error=tamanho_excedido');
            exit;
        }
        
        // Criar diretório se não existir
        $diretorio_upload = 'uploads/anexos/';
        if (!file_exists($diretorio_upload)) {
            mkdir($diretorio_upload, 0755, true);
        }
        
        // Gerar nome único
        $nome_arquivo = uniqid() . '_' . time() . '.' . $extensao;
        $caminho_completo = $diretorio_upload . $nome_arquivo;
        
        if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
            // Salvar no banco
            $sql = "INSERT INTO anexos_lancamentos (id_lancamento, nome_arquivo, nome_original, caminho_arquivo, tipo_arquivo, tamanho, descricao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_lancamento,
                $nome_arquivo,
                $nome_original,
                $caminho_completo,
                $extensao,
                $tamanho,
                $descricao
            ]);
            
            header('Location: anexos.php?success=enviado&id=' . $id_lancamento);
            exit;
        } else {
            header('Location: anexos.php?error=upload_falhou');
            exit;
        }
    } else {
        header('Location: anexos.php?error=lancamento_invalido');
        exit;
    }
}

// Deletar anexo
if (isset($_GET['deletar'])) {
    $id_anexo = $_GET['deletar'];
    
    // Buscar anexo e verificar permissão
    $sql = "SELECT a.*, l.id_usuario 
            FROM anexos_lancamentos a
            JOIN lancamentos l ON a.id_lancamento = l.id
            WHERE a.id = ? AND l.id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_anexo, $user_id]);
    $anexo = $stmt->fetch();
    
    if ($anexo) {
        // Deletar arquivo físico
        if (file_exists($anexo['caminho_arquivo'])) {
            unlink($anexo['caminho_arquivo']);
        }
        
        // Deletar do banco
        $sql = "DELETE FROM anexos_lancamentos WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_anexo]);
        
        header('Location: anexos.php?success=deletado');
        exit;
    }
}

// Buscar lançamentos do usuário com anexos
$filtro_id = $_GET['id'] ?? '';
$where_clause = "WHERE l.id_usuario = ?";
$params = [$user_id];

if ($filtro_id) {
    $where_clause .= " AND l.id = ?";
    $params[] = $filtro_id;
}

$sql = "SELECT l.*, c.nome as categoria, 
        (SELECT COUNT(*) FROM anexos_lancamentos WHERE id_lancamento = l.id) as total_anexos
        FROM lancamentos l
        LEFT JOIN categorias c ON l.id_categoria = c.id
        $where_clause
        ORDER BY l.data DESC
        LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

// Buscar todos os anexos
$sql = "SELECT a.*, l.descricao as lancamento_desc, l.valor, l.tipo, l.data
        FROM anexos_lancamentos a
        JOIN lancamentos l ON a.id_lancamento = l.id
        WHERE l.id_usuario = ?
        ORDER BY a.data_upload DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$todos_anexos = $stmt->fetchAll();

// Estatísticas
$sql = "SELECT 
        COUNT(*) as total_anexos,
        SUM(tamanho) as tamanho_total,
        COUNT(CASE WHEN tipo_arquivo IN ('jpg', 'jpeg', 'png', 'gif') THEN 1 END) as total_imagens,
        COUNT(CASE WHEN tipo_arquivo = 'pdf' THEN 1 END) as total_pdfs
        FROM anexos_lancamentos a
        JOIN lancamentos l ON a.id_lancamento = l.id
        WHERE l.id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-paperclip me-2"></i>Anexos de Comprovantes</h2>
            <p class="text-white-50">Adicione fotos de notas fiscais e recibos aos seus lançamentos</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $messages = [
                'enviado' => 'Anexo enviado com sucesso!',
                'deletado' => 'Anexo removido.'
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
                'tipo_invalido' => 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou PDF.',
                'tamanho_excedido' => 'Arquivo muito grande. Tamanho máximo: 5MB.',
                'upload_falhou' => 'Falha no upload do arquivo.',
                'lancamento_invalido' => 'Lançamento não encontrado.'
            ];
            echo '<i class="fas fa-times-circle me-2"></i>' . ($errors[$_GET['error']] ?? 'Erro desconhecido.');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-file fa-3x text-primary mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_anexos'] ?></h3>
                    <small class="text-muted">Total de Anexos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-image fa-3x text-success mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_imagens'] ?></h3>
                    <small class="text-muted">Imagens</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_pdfs'] ?></h3>
                    <small class="text-muted">PDFs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-hdd fa-3x text-warning mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['tamanho_total'] / 1024 / 1024, 2) ?> MB</h3>
                    <small class="text-muted">Espaço Usado</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Adicionar Anexo -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Enviar Anexo</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Lançamento</label>
                            <select name="id_lancamento" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($lancamentos as $lanc): ?>
                                    <option value="<?= $lanc['id'] ?>" <?= ($filtro_id == $lanc['id']) ? 'selected' : '' ?>>
                                        <?= date('d/m/Y', strtotime($lanc['data'])) ?> - 
                                        <?= htmlspecialchars($lanc['descricao']) ?> - 
                                        R$ <?= number_format($lanc['valor'], 2, ',', '.') ?>
                                        <?php if ($lanc['total_anexos'] > 0): ?>
                                            (<?= $lanc['total_anexos'] ?> anexo<?= $lanc['total_anexos'] > 1 ? 's' : '' ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Arquivo</label>
                            <input type="file" name="arquivo" class="form-control" required accept=".jpg,.jpeg,.png,.gif,.pdf">
                            <small class="text-muted">Formatos: JPG, PNG, GIF, PDF (máx. 5MB)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição (opcional)</label>
                            <textarea name="descricao" class="form-control" rows="2" placeholder="Ex: Nota fiscal da compra"></textarea>
                        </div>

                        <div class="alert alert-info small">
                            <i class="fas fa-lightbulb me-1"></i>
                            <strong>Dica:</strong> Tire foto da nota fiscal com seu celular e envie aqui para manter tudo organizado!
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Enviar Anexo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Anexos -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Anexos Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($todos_anexos)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum anexo enviado ainda</p>
                            <p class="small text-muted">Comece adicionando comprovantes aos seus lançamentos!</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($todos_anexos as $anexo): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border">
                                        <div class="card-body p-2">
                                            <!-- Preview -->
                                            <div class="text-center mb-2" style="height: 150px; overflow: hidden; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <?php if (in_array($anexo['tipo_arquivo'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                    <img src="<?= htmlspecialchars($anexo['caminho_arquivo']) ?>" 
                                                         class="img-fluid" 
                                                         style="max-height: 150px; cursor: pointer;"
                                                         onclick="visualizarAnexo('<?= htmlspecialchars($anexo['caminho_arquivo']) ?>', 'imagem', '<?= htmlspecialchars($anexo['nome_original']) ?>')">
                                                <?php else: ?>
                                                    <div class="text-center">
                                                        <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                                        <p class="small mb-0 mt-2">PDF</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Info -->
                                            <h6 class="mb-1 small text-truncate" title="<?= htmlspecialchars($anexo['nome_original']) ?>">
                                                <?= htmlspecialchars($anexo['nome_original']) ?>
                                            </h6>
                                            <p class="small text-muted mb-1">
                                                <strong><?= htmlspecialchars($anexo['lancamento_desc']) ?></strong>
                                            </p>
                                            <p class="small text-muted mb-1">
                                                <span class="badge bg-<?= $anexo['tipo'] === 'receita' ? 'success' : 'danger' ?>">
                                                    R$ <?= number_format($anexo['valor'], 2, ',', '.') ?>
                                                </span>
                                                <span class="ms-1"><?= date('d/m/Y', strtotime($anexo['data'])) ?></span>
                                            </p>
                                            <?php if ($anexo['descricao']): ?>
                                                <p class="small mb-1"><?= htmlspecialchars($anexo['descricao']) ?></p>
                                            <?php endif; ?>
                                            <p class="small text-muted mb-2">
                                                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($anexo['data_upload'])) ?> • 
                                                <?= number_format($anexo['tamanho'] / 1024, 0) ?> KB
                                            </p>

                                            <!-- Ações -->
                                            <div class="btn-group btn-group-sm w-100">
                                                <a href="<?= htmlspecialchars($anexo['caminho_arquivo']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   target="_blank"
                                                   title="Abrir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= htmlspecialchars($anexo['caminho_arquivo']) ?>" 
                                                   class="btn btn-outline-success" 
                                                   download="<?= htmlspecialchars($anexo['nome_original']) ?>"
                                                   title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="?deletar=<?= $anexo['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Deletar este anexo?')"
                                                   title="Deletar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualização -->
<div class="modal fade" id="modalVisualizacao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Visualização</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="modalConteudo">
                <!-- Conteúdo será inserido via JS -->
            </div>
        </div>
    </div>
</div>

<script>
function visualizarAnexo(caminho, tipo, nome) {
    document.getElementById('modalTitulo').textContent = nome;
    
    if (tipo === 'imagem') {
        document.getElementById('modalConteudo').innerHTML = 
            '<img src="' + caminho + '" class="img-fluid" alt="' + nome + '">';
    }
    
    new bootstrap.Modal(document.getElementById('modalVisualizacao')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
