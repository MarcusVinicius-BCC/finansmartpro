<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/Pagination.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'backup',
            'action' => $_POST['action'],
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if ($_POST['action'] === 'gerar_backup') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_finansmart_{$timestamp}.sql";
            $filepath = "backups/{$filename}";
            
            // Criar pasta backups se não existir
            if (!is_dir('backups')) {
                mkdir('backups', 0755, true);
            }
            
            // Tabelas para backup (sem usuarios para evitar duplicação)
            $tabelas = [
                'categorias', 'lancamentos', 'orcamentos', 'metas', 
                'investimentos', 'cartoes', 'contas_bancarias', 'transferencias',
                'contas_recorrentes', 'alertas', 'planejamento_cenarios', 
                'importacoes', 'contas_pagar', 'contas_receber', 'conciliacoes'
            ];
            
            $backup_content = "-- Backup FinanSmart Pro\n";
            $backup_content .= "-- Data: " . date('d/m/Y H:i:s') . "\n";
            $backup_content .= "-- Usuário ID: {$user_id}\n";
            $backup_content .= "-- ATENÇÃO: Este backup NÃO inclui dados da tabela usuarios\n\n";
            $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tabelas as $tabela) {
                // Buscar dados do usuário
                $sql = "SELECT * FROM {$tabela} WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($dados)) {
                    $backup_content .= "-- Tabela: {$tabela}\n";
                    
                    foreach ($dados as $row) {
                        $colunas = array_keys($row);
                        $valores = array_map(function($val) use ($pdo) {
                            return $val === null ? 'NULL' : $pdo->quote($val);
                        }, array_values($row));
                        
                        $backup_content .= "INSERT INTO {$tabela} (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ");\n";
                    }
                    
                    $backup_content .= "\n";
                }
            }
            
            $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Salvar arquivo
            file_put_contents($filepath, $backup_content);
            
            // Download automático
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            
            // Registrar backup
            $sql = "INSERT INTO backups (id_usuario, nome_arquivo, tamanho_bytes, data_backup) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $filename, filesize($filepath)]);
            
            exit;
            
        } catch (Exception $e) {
            header('Location: backup.php?error=backup&msg=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    if ($_POST['action'] === 'restaurar' && isset($_FILES['arquivo_backup'])) {
        try {
            $arquivo = $_FILES['arquivo_backup'];
            
            if ($arquivo['error'] === UPLOAD_ERR_OK) {
                $conteudo = file_get_contents($arquivo['tmp_name']);
                
                // Remover dados antigos do usuário (CUIDADO!)
                if (isset($_POST['confirmar_exclusao'])) {
                    $pdo->beginTransaction();
                    
                    // Deletar dados antigos do usuário ANTES de restaurar
                    $tabelas = [
                        'conciliacoes', 'contas_receber', 'contas_pagar', 'importacoes',
                        'planejamento_cenarios', 'alertas', 'contas_recorrentes',
                        'transferencias', 'contas_bancarias', 'cartoes', 'investimentos',
                        'metas', 'orcamentos', 'lancamentos', 'categorias'
                    ];
                    
                    foreach ($tabelas as $tabela) {
                        $sql = "DELETE FROM {$tabela} WHERE id_usuario = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$user_id]);
                    }
                    
                    // Executar SQL do backup
                    $pdo->exec($conteudo);
                    
                    $pdo->commit();
                    
                    header('Location: backup.php?success=restaurado');
                    exit;
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header('Location: backup.php?error=restaurar&msg=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    if ($_POST['action'] === 'excluir_backup') {
        $arquivo = $_POST['arquivo'];
        $filepath = "backups/{$arquivo}";
        
        if (file_exists($filepath)) {
            unlink($filepath);
            
            $sql = "UPDATE backups SET status = 'excluido' WHERE nome_arquivo = ? AND id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$arquivo, $user_id]);
        }
        
        header('Location: backup.php?success=excluido');
        exit;
    }
}

// Buscar backups do usuário
$sql = "SELECT * FROM backups WHERE id_usuario = ? AND status = 'ativo' ORDER BY data_backup DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$backups = $stmt->fetchAll();

// Estatísticas
$sql = "SELECT COUNT(*) FROM lancamentos WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$total_lancamentos = $stmt->fetchColumn();

$sql = "SELECT COUNT(*) FROM categorias WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$total_categorias = $stmt->fetchColumn();

$sql = "SELECT COUNT(*) FROM contas_bancarias WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$total_contas = $stmt->fetchColumn();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-database me-2"></i>Backup & Restore</h2>
            <p class="text-white-50">Exporte e importe seus dados de forma segura</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $messages = [
                'restaurado' => 'Backup restaurado com sucesso!',
                'excluido' => 'Backup excluído com sucesso!'
            ];
            echo '<i class="fas fa-check-circle me-2"></i>' . ($messages[$_GET['success']] ?? 'Operação concluída!');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>Erro: <?= htmlspecialchars($_GET['msg'] ?? 'Erro desconhecido') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Painel Esquerdo -->
        <div class="col-lg-4 mb-4">
            <!-- Estatísticas -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Seus Dados</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-primary"><?= $total_lancamentos ?></h3>
                                <small class="text-muted">Lançamentos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-success"><?= $total_categorias ?></h3>
                                <small class="text-muted">Categorias</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-warning"><?= $total_contas ?></h3>
                                <small class="text-muted">Contas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-danger"><?= count($backups) ?></h3>
                                <small class="text-muted">Backups</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gerar Backup -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-download me-2"></i>Gerar Backup</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Exporte todos os seus dados financeiros em formato SQL.</p>
                    
                    <div class="alert alert-warning small">
                        <i class="fas fa-info-circle me-1"></i>
                        O backup inclui:
                        <ul class="mb-0 mt-2">
                            <li>Todos os lançamentos</li>
                            <li>Orçamentos e metas</li>
                            <li>Categorias personalizadas</li>
                            <li>Contas e cartões</li>
                            <li>Investimentos</li>
                            <li>Configurações</li>
                        </ul>
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="action" value="gerar_backup">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-download me-2"></i>Gerar e Baixar Backup
                        </button>
                    </form>
                </div>
            </div>

            <!-- Restaurar Backup -->
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Restaurar Backup</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Importe dados de um backup anterior.</p>
                    
                    <div class="alert alert-danger small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>ATENÇÃO:</strong> Restaurar um backup irá <strong>substituir</strong> todos os dados atuais!
                    </div>
                    
                    <form method="post" enctype="multipart/form-data" onsubmit="return confirmarRestauracao()">
                        <input type="hidden" name="action" value="restaurar">
                        
                        <div class="mb-3">
                            <label class="form-label">Arquivo de Backup (.sql)</label>
                            <input type="file" name="arquivo_backup" class="form-control" accept=".sql" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirmar_exclusao" value="1" id="confirmar" required>
                            <label class="form-check-label text-danger" for="confirmar">
                                Entendo que meus dados atuais serão substituídos
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-upload me-2"></i>Restaurar Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Histórico de Backups -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Backups</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-database fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum backup criado ainda</p>
                            <p class="small text-muted">Crie seu primeiro backup para garantir a segurança dos seus dados!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Nome do Arquivo</th>
                                        <th>Tamanho</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i:s', strtotime($backup['data_backup'])) ?></td>
                                            <td>
                                                <i class="fas fa-file-code text-primary me-2"></i>
                                                <code><?= htmlspecialchars($backup['nome_arquivo']) ?></code>
                                            </td>
                                            <td>
                                                <?php
                                                $size = $backup['tamanho_bytes'];
                                                if ($size < 1024) {
                                                    echo $size . ' B';
                                                } elseif ($size < 1048576) {
                                                    echo round($size / 1024, 2) . ' KB';
                                                } else {
                                                    echo round($size / 1048576, 2) . ' MB';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Disponível
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (file_exists("backups/{$backup['nome_arquivo']}")): ?>
                                                    <a href="backups/<?= $backup['nome_arquivo'] ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       download>
                                                        <i class="fas fa-download"></i> Baixar
                                                    </a>
                                                <?php endif; ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Excluir este backup?')">
                                                    <input type="hidden" name="action" value="excluir_backup">
                                                    <input type="hidden" name="arquivo" value="<?= $backup['nome_arquivo'] ?>">
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

            <!-- Dicas de Segurança -->
            <div class="card shadow mt-4">
                <div class="card-header bg-gradient-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Dicas de Segurança</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Boas Práticas</h6>
                            <ul class="small">
                                <li>Faça backups <strong>semanalmente</strong></li>
                                <li>Mantenha <strong>3 versões</strong> de backup</li>
                                <li>Armazene em <strong>locais diferentes</strong></li>
                                <li>Use <strong>nuvem + local</strong></li>
                                <li>Teste restaurações periodicamente</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Cuidados</h6>
                            <ul class="small">
                                <li>Não compartilhe arquivos de backup</li>
                                <li>Criptografe backups sensíveis</li>
                                <li>Verifique integridade após download</li>
                                <li>Delete backups antigos com segurança</li>
                                <li>Confirme antes de restaurar</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <strong><i class="fas fa-lightbulb me-2"></i>Recomendação:</strong>
                        Salve seus backups em serviços de nuvem como Google Drive, Dropbox ou OneDrive para maior segurança.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarRestauracao() {
    return confirm('⚠️ ATENÇÃO!\n\nVocê está prestes a RESTAURAR um backup.\n\nISTO IRÁ SUBSTITUIR TODOS OS SEUS DADOS ATUAIS!\n\nDeseja continuar?');
}
</script>

<?php include 'includes/footer.php'; ?>
