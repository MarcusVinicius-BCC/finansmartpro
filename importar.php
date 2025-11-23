<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    $tipo = $_POST['tipo_arquivo'];
    
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        
        // Validar extensão
        if (($tipo === 'ofx' && $extensao === 'ofx') || ($tipo === 'csv' && $extensao === 'csv')) {
            $conteudo = file_get_contents($arquivo['tmp_name']);
            $lancamentos_importados = 0;
            $erros = [];
            
            try {
                if ($tipo === 'ofx') {
                    // Processar OFX
                    $resultado = processarOFX($conteudo, $user_id, $pdo);
                    $lancamentos_importados = $resultado['importados'];
                    $erros = $resultado['erros'];
                } else {
                    // Processar CSV
                    $resultado = processarCSV($conteudo, $user_id, $pdo);
                    $lancamentos_importados = $resultado['importados'];
                    $erros = $resultado['erros'];
                }
                
                // Salvar histórico de importação
                $sql = "INSERT INTO importacoes (id_usuario, nome_arquivo, tipo_arquivo, total_lancamentos, data_importacao) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $arquivo['name'], $tipo, $lancamentos_importados]);
                
                if (!empty($erros)) {
                    header('Location: importar.php?success=parcial&importados=' . $lancamentos_importados . '&erros=' . count($erros));
                } else {
                    header('Location: importar.php?success=completo&importados=' . $lancamentos_importados);
                }
                exit;
                
            } catch (Exception $e) {
                header('Location: importar.php?error=processamento&msg=' . urlencode($e->getMessage()));
                exit;
            }
        } else {
            header('Location: importar.php?error=formato');
            exit;
        }
    } else {
        header('Location: importar.php?error=upload');
        exit;
    }
}

// Buscar histórico de importações
$sql = "SELECT * FROM importacoes WHERE id_usuario = ? ORDER BY data_importacao DESC LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$historico = $stmt->fetchAll();

// Buscar categorias para mapeamento
$sql = "SELECT * FROM categorias WHERE id_usuario = ? OR id_usuario IS NULL ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

include 'includes/header.php';

// Funções de processamento
function processarOFX($conteudo, $user_id, $pdo) {
    $importados = 0;
    $erros = [];
    
    // Parser simplificado OFX
    // Procurar por tags STMTTRN (Statement Transaction)
    preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $transacoes);
    
    foreach ($transacoes[1] as $index => $transacao) {
        try {
            // Extrair dados da transação
            preg_match('/<TRNTYPE>(.*?)</', $transacao, $tipo);
            preg_match('/<DTPOSTED>(.*?)</', $transacao, $data);
            preg_match('/<TRNAMT>(.*?)</', $transacao, $valor);
            preg_match('/<MEMO>(.*?)</', $transacao, $descricao);
            
            if (!empty($tipo[1]) && !empty($data[1]) && !empty($valor[1])) {
                // Converter data OFX (YYYYMMDD) para MySQL (YYYY-MM-DD)
                $dataFormatada = substr($data[1], 0, 4) . '-' . substr($data[1], 4, 2) . '-' . substr($data[1], 6, 2);
                
                // Determinar tipo (receita/despesa)
                $valorFloat = floatval($valor[1]);
                $tipoLancamento = $valorFloat >= 0 ? 'receita' : 'despesa';
                $valorAbsoluto = abs($valorFloat);
                
                $descricaoTexto = !empty($descricao[1]) ? trim($descricao[1]) : 'Importado OFX';
                
                // Verificar duplicatas
                $sql = "SELECT COUNT(*) FROM lancamentos WHERE id_usuario = ? AND descricao = ? AND valor = ? AND data = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $descricaoTexto, $valorAbsoluto, $dataFormatada]);
                
                if ($stmt->fetchColumn() == 0) {
                    // Inserir lançamento
                    $sql = "INSERT INTO lancamentos (id_usuario, descricao, valor, tipo, data, moeda) VALUES (?, ?, ?, ?, ?, 'BRL')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $descricaoTexto, $valorAbsoluto, $tipoLancamento, $dataFormatada]);
                    $importados++;
                }
            }
        } catch (Exception $e) {
            $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    return ['importados' => $importados, 'erros' => $erros];
}

function processarCSV($conteudo, $user_id, $pdo) {
    $importados = 0;
    $erros = [];
    
    $linhas = explode("\n", $conteudo);
    $header = null;
    
    foreach ($linhas as $index => $linha) {
        $linha = trim($linha);
        if (empty($linha)) continue;
        
        $campos = str_getcsv($linha, ';');
        
        // Primeira linha é o cabeçalho
        if ($index === 0) {
            $header = $campos;
            continue;
        }
        
        try {
            // Formato esperado: Data;Descrição;Valor;Tipo
            // Ou auto-detectar colunas
            if (count($campos) >= 3) {
                $data = $campos[0];
                $descricao = $campos[1];
                $valor = $campos[2];
                $tipo = isset($campos[3]) ? strtolower($campos[3]) : null;
                
                // Converter data (aceita dd/mm/yyyy ou yyyy-mm-dd)
                if (strpos($data, '/') !== false) {
                    $partes = explode('/', $data);
                    if (count($partes) === 3) {
                        $dataFormatada = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
                    }
                } else {
                    $dataFormatada = $data;
                }
                
                // Limpar valor (remover R$, espaços, converter vírgula)
                $valor = str_replace(['R$', ' '], '', $valor);
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
                $valorFloat = floatval($valor);
                
                // Determinar tipo
                if (!$tipo) {
                    $tipo = $valorFloat >= 0 ? 'receita' : 'despesa';
                }
                $valorAbsoluto = abs($valorFloat);
                
                // Validar data
                if (!strtotime($dataFormatada)) {
                    throw new Exception("Data inválida: $data");
                }
                
                // Verificar duplicatas
                $sql = "SELECT COUNT(*) FROM lancamentos WHERE id_usuario = ? AND descricao = ? AND valor = ? AND data = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $descricao, $valorAbsoluto, $dataFormatada]);
                
                if ($stmt->fetchColumn() == 0) {
                    // Inserir lançamento
                    $sql = "INSERT INTO lancamentos (id_usuario, descricao, valor, tipo, data, moeda) VALUES (?, ?, ?, ?, ?, 'BRL')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $descricao, $valorAbsoluto, $tipo, $dataFormatada]);
                    $importados++;
                }
            }
        } catch (Exception $e) {
            $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    return ['importados' => $importados, 'erros' => $erros];
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-file-import me-2"></i>Importação de Extratos</h2>
            <p class="text-white-50">Importe lançamentos de arquivos OFX ou CSV do seu banco</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php if ($_GET['success'] === 'completo'): ?>
                <i class="fas fa-check-circle me-2"></i><strong>Importação concluída!</strong> 
                <?= $_GET['importados'] ?> lançamento(s) importado(s) com sucesso.
            <?php elseif ($_GET['success'] === 'parcial'): ?>
                <i class="fas fa-exclamation-triangle me-2"></i><strong>Importação parcial.</strong> 
                <?= $_GET['importados'] ?> lançamento(s) importado(s), <?= $_GET['erros'] ?> erro(s) encontrado(s).
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            $errors = [
                'formato' => 'Formato de arquivo inválido. Use apenas arquivos OFX ou CSV.',
                'upload' => 'Erro ao fazer upload do arquivo. Tente novamente.',
                'processamento' => 'Erro ao processar arquivo: ' . ($_GET['msg'] ?? 'desconhecido')
            ];
            echo '<i class="fas fa-times-circle me-2"></i>' . ($errors[$_GET['error']] ?? 'Erro desconhecido.');
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Upload de Arquivo -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Fazer Upload</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Arquivo</label>
                            <select name="tipo_arquivo" id="tipo_arquivo" class="form-select" required>
                                <option value="ofx">OFX (Open Financial Exchange)</option>
                                <option value="csv">CSV (Comma Separated Values)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Selecionar Arquivo</label>
                            <input type="file" name="arquivo" id="arquivo" class="form-control" accept=".ofx,.csv" required>
                            <small class="text-muted">Tamanho máximo: 5 MB</small>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informações</h6>
                            <ul class="mb-0 small">
                                <li><strong>OFX:</strong> Exportado diretamente do internet banking</li>
                                <li><strong>CSV:</strong> Formato: Data;Descrição;Valor;Tipo</li>
                                <li>Data: dd/mm/yyyy ou yyyy-mm-dd</li>
                                <li>Tipo: receita ou despesa (opcional)</li>
                                <li>Duplicatas são automaticamente ignoradas</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Importar Arquivo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Exemplo CSV -->
            <div class="card shadow mt-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-file-csv me-2"></i>Exemplo de CSV</h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded mb-0" style="font-size: 0.85rem;">Data;Descrição;Valor;Tipo
01/01/2025;Salário;5000,00;receita
05/01/2025;Supermercado;-350,50;despesa
10/01/2025;Aluguel;-1500,00;despesa
15/01/2025;Freelance;800,00;receita</pre>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Valores negativos são automaticamente tratados como despesas
                    </small>
                </div>
            </div>
        </div>

        <!-- Histórico de Importações -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Importações</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($historico)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-import fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma importação realizada ainda</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Arquivo</th>
                                        <th>Tipo</th>
                                        <th>Lançamentos</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $imp): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($imp['data_importacao'])) ?></td>
                                            <td>
                                                <i class="fas fa-file-<?= $imp['tipo_arquivo'] ?> me-2"></i>
                                                <?= htmlspecialchars($imp['nome_arquivo']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $imp['tipo_arquivo'] === 'ofx' ? 'info' : 'success' ?>">
                                                    <?= strtoupper($imp['tipo_arquivo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= $imp['total_lancamentos'] ?></strong> 
                                                <small class="text-muted">registro(s)</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Concluído
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instruções por Banco -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-university me-2"></i>Como Exportar do seu Banco</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="bancoAccordion">
                        <!-- Banco do Brasil -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bb">
                                    <i class="fas fa-university me-2"></i>Banco do Brasil
                                </button>
                            </h2>
                            <div id="bb" class="accordion-collapse collapse" data-bs-parent="#bancoAccordion">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>Acesse o Internet Banking</li>
                                        <li>Extrato → Conta Corrente</li>
                                        <li>Selecione o período</li>
                                        <li>Clique em "Exportar" → Formato OFX</li>
                                        <li>Salve o arquivo e importe aqui</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Itaú -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#itau">
                                    <i class="fas fa-university me-2"></i>Itaú
                                </button>
                            </h2>
                            <div id="itau" class="accordion-collapse collapse" data-bs-parent="#bancoAccordion">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>Entre no app Itaú ou site</li>
                                        <li>Extrato → Conta</li>
                                        <li>Período desejado</li>
                                        <li>Exportar → OFX ou XLS (converta para CSV)</li>
                                        <li>Faça upload do arquivo</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Nubank -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nubank">
                                    <i class="fas fa-university me-2"></i>Nubank
                                </button>
                            </h2>
                            <div id="nubank" class="accordion-collapse collapse" data-bs-parent="#bancoAccordion">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>Abra o app Nubank</li>
                                        <li>Toque no ícone de lupa (buscar)</li>
                                        <li>Digite "enviar por email"</li>
                                        <li>Selecione "Enviar fatura ou extrato por email"</li>
                                        <li>Escolha período e formato (CSV ou Excel)</li>
                                        <li>Converta para CSV se necessário e importe</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Caixa -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#caixa">
                                    <i class="fas fa-university me-2"></i>Caixa Econômica
                                </button>
                            </h2>
                            <div id="caixa" class="accordion-collapse collapse" data-bs-parent="#bancoAccordion">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>Internet Banking da Caixa</li>
                                        <li>Extrato de Conta Corrente</li>
                                        <li>Defina o período</li>
                                        <li>Salvar como → OFX</li>
                                        <li>Importe o arquivo baixado</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validar tamanho do arquivo (5MB)
document.getElementById('arquivo').addEventListener('change', function() {
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (this.files[0] && this.files[0].size > maxSize) {
        alert('Arquivo muito grande! Tamanho máximo: 5 MB');
        this.value = '';
    }
});

// Validar extensão baseado no tipo selecionado
document.getElementById('tipo_arquivo').addEventListener('change', function() {
    const fileInput = document.getElementById('arquivo');
    if (this.value === 'ofx') {
        fileInput.accept = '.ofx';
    } else {
        fileInput.accept = '.csv';
    }
    fileInput.value = ''; // Limpar seleção anterior
});
</script>

<?php include 'includes/footer.php'; ?>
