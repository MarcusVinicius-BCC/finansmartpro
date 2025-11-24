<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/Pagination.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_validation_failed', [
            'module' => 'planejamento',
            'action' => $_POST['action'] ?? 'unknown',
            'user_id' => $user_id
        ]);
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'salvar_cenario') {
        $sql = "INSERT INTO planejamento_cenarios (id_usuario, nome, descricao, tipo, valor_base, percentual_variacao, resultado_calculado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $resultado = $_POST['valor_base'] * (1 + ($_POST['percentual_variacao'] / 100));
        $stmt->execute([
            $user_id, 
            $_POST['nome'], 
            $_POST['descricao'], 
            $_POST['tipo'], 
            $_POST['valor_base'], 
            $_POST['percentual_variacao'],
            $resultado
        ]);
        header('Location: planejamento.php?success=cenario_salvo');
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'excluir_cenario') {
        $sql = "DELETE FROM planejamento_cenarios WHERE id = ? AND id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id'], $user_id]);
        header('Location: planejamento.php?success=cenario_excluido');
        exit;
    }
}

// Buscar cenários salvos
$sql = "SELECT * FROM planejamento_cenarios WHERE id_usuario = ? ORDER BY data_criacao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cenarios = $stmt->fetchAll();

// Dados para simulador de aposentadoria
$sql = "SELECT AVG(valor) as receita_mensal FROM lancamentos WHERE id_usuario = ? AND tipo = 'receita' AND data >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$receita_media = $stmt->fetch()['receita_mensal'] ?? 0;

$sql = "SELECT AVG(valor) as despesa_mensal FROM lancamentos WHERE id_usuario = ? AND tipo = 'despesa' AND data >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$despesa_media = $stmt->fetch()['despesa_mensal'] ?? 0;

$sql = "SELECT SUM(saldo_atual) as patrimonio_total FROM contas_bancarias WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$patrimonio_atual = $stmt->fetch()['patrimonio_total'] ?? 0;

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-chart-line me-2"></i>Planejamento Financeiro</h2>
            <p class="text-white-50">Simule cenários, planeje sua aposentadoria e trace metas de longo prazo</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $messages = [
                'cenario_salvo' => 'Cenário salvo com sucesso!',
                'cenario_excluido' => 'Cenário excluído com sucesso!'
            ];
            echo $messages[$_GET['success']] ?? 'Operação realizada com sucesso!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Abas de Navegação -->
    <ul class="nav nav-tabs mb-4" id="planejamentoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="cenarios-tab" data-bs-toggle="tab" data-bs-target="#cenarios" type="button">
                <i class="fas fa-question-circle me-2"></i>Cenários "E se?"
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aposentadoria-tab" data-bs-toggle="tab" data-bs-target="#aposentadoria" type="button">
                <i class="fas fa-umbrella-beach me-2"></i>Aposentadoria
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="metas-longo-tab" data-bs-toggle="tab" data-bs-target="#metas-longo" type="button">
                <i class="fas fa-bullseye me-2"></i>Metas Longo Prazo
            </button>
        </li>
    </ul>

    <div class="tab-content" id="planejamentoTabsContent">
        <!-- Tab Cenários "E se?" -->
        <div class="tab-pane fade show active" id="cenarios" role="tabpanel">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Criar Cenário</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="formCenario">
                                <input type="hidden" name="action" value="salvar_cenario">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nome do Cenário</label>
                                    <input type="text" name="nome" class="form-control" required placeholder="Ex: Aumento de salário">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select" required>
                                        <option value="receita">Aumento de Receita</option>
                                        <option value="despesa">Redução de Despesa</option>
                                        <option value="investimento">Retorno de Investimento</option>
                                        <option value="divida">Quitação de Dívida</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Valor Base</label>
                                    <input type="text" name="valor_base" class="form-control money-input" required placeholder="0,00">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Variação (%)</label>
                                    <input type="number" name="percentual_variacao" class="form-control" step="0.1" required placeholder="Ex: 10">
                                    <small class="text-muted">Percentual de mudança (positivo ou negativo)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva o cenário..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="alert alert-info mb-0">
                                        <strong>Resultado: R$ <span id="resultado-cenario">0,00</span></strong>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Salvar Cenário
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Cenários Salvos</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cenarios)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-lightbulb fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">Nenhum cenário criado ainda. Crie seu primeiro cenário!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Tipo</th>
                                                <th>Valor Base</th>
                                                <th>Variação</th>
                                                <th>Resultado</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cenarios as $cen): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($cen['nome']) ?></strong>
                                                        <?php if ($cen['descricao']): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($cen['descricao']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'receita' => 'success',
                                                            'despesa' => 'warning',
                                                            'investimento' => 'info',
                                                            'divida' => 'danger'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $badges[$cen['tipo']] ?>">
                                                            <?= ucfirst($cen['tipo']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= fmt_currency($cen['valor_base']) ?></td>
                                                    <td><?= number_format($cen['percentual_variacao'], 1, ',', '.') ?>%</td>
                                                    <td class="fw-bold text-success"><?= fmt_currency($cen['resultado_calculado']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($cen['data_criacao'])) ?></td>
                                                    <td>
                                                        <form method="post" style="display:inline;" onsubmit="return confirm('Excluir este cenário?')">
                                                            <input type="hidden" name="action" value="excluir_cenario">
                                                            <input type="hidden" name="id" value="<?= $cen['id'] ?>">
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

        <!-- Tab Simulador de Aposentadoria -->
        <div class="tab-pane fade" id="aposentadoria" role="tabpanel">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Simulador de Aposentadoria</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Idade Atual</label>
                                <input type="number" id="idade_atual" class="form-control" value="30" min="18" max="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Idade Desejada para Aposentadoria</label>
                                <input type="number" id="idade_aposenta" class="form-control" value="65" min="18" max="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Renda Mensal Desejada na Aposentadoria</label>
                                <input type="text" id="renda_desejada" class="form-control money-input" placeholder="0,00">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Aporte Mensal Atual</label>
                                <input type="text" id="aporte_mensal" class="form-control money-input" placeholder="0,00">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Taxa de Retorno Anual (%)</label>
                                <input type="number" id="taxa_retorno" class="form-control" value="8" step="0.1" placeholder="Ex: 8">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Patrimônio Atual</label>
                                <input type="text" id="patrimonio_inicial" class="form-control money-input" value="<?= number_format($patrimonio_atual, 2, ',', '.') ?>">
                            </div>
                            
                            <button type="button" class="btn btn-success w-100" onclick="calcularAposentadoria()">
                                <i class="fas fa-chart-line me-2"></i>Calcular Projeção
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-gradient-success text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Projeção</h5>
                        </div>
                        <div class="card-body">
                            <div id="resultado-aposentadoria" class="d-none">
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle me-2"></i>Análise Completa</h5>
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded">
                                            <small class="text-muted">Tempo até Aposentadoria</small>
                                            <h4 class="mb-0 text-primary" id="anos_faltantes">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded">
                                            <small class="text-muted">Patrimônio Necessário</small>
                                            <h4 class="mb-0 text-success" id="patrimonio_necessario">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded">
                                            <small class="text-muted">Patrimônio Projetado</small>
                                            <h4 class="mb-0 text-info" id="patrimonio_projetado">-</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded">
                                            <small class="text-muted">Renda Mensal Possível</small>
                                            <h4 class="mb-0 text-warning" id="renda_possivel">-</h4>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert" id="status-objetivo">
                                    <h6 id="mensagem-objetivo"></h6>
                                    <p class="mb-0" id="recomendacao-objetivo"></p>
                                </div>
                                
                                <canvas id="graficoAposentadoria" height="200"></canvas>
                            </div>
                            
                            <div id="placeholder-aposentadoria">
                                <div class="text-center py-5">
                                    <i class="fas fa-umbrella-beach fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">Preencha os dados e clique em "Calcular Projeção"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Metas de Longo Prazo -->
        <div class="tab-pane fade" id="metas-longo" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Metas de Longo Prazo (10+ anos)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 mb-4">
                                    <div class="card border-primary h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-home text-primary me-2"></i>Casa Própria</h5>
                                            <div class="mb-3">
                                                <label class="form-label">Valor do Imóvel</label>
                                                <input type="text" id="meta_casa_valor" class="form-control money-input" placeholder="R$ 500.000,00">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Entrada (20%)</label>
                                                <input type="text" id="meta_casa_entrada" class="form-control" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Prazo (anos)</label>
                                                <input type="number" id="meta_casa_prazo" class="form-control" value="15">
                                            </div>
                                            <div class="alert alert-primary mb-0">
                                                <small>Aporte mensal necessário:</small><br>
                                                <strong id="meta_casa_mensal">R$ 0,00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-4">
                                    <div class="card border-success h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-graduation-cap text-success me-2"></i>Educação dos Filhos</h5>
                                            <div class="mb-3">
                                                <label class="form-label">Custo Total Estimado</label>
                                                <input type="text" id="meta_educacao_valor" class="form-control money-input" placeholder="R$ 200.000,00">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Anos até Universidade</label>
                                                <input type="number" id="meta_educacao_prazo" class="form-control" value="18">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Rendimento Anual (%)</label>
                                                <input type="number" id="meta_educacao_rendimento" class="form-control" value="6" step="0.1">
                                            </div>
                                            <div class="alert alert-success mb-0">
                                                <small>Aporte mensal necessário:</small><br>
                                                <strong id="meta_educacao_mensal">R$ 0,00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-4">
                                    <div class="card border-info h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-piggy-bank text-info me-2"></i>Independência Financeira</h5>
                                            <div class="mb-3">
                                                <label class="form-label">Renda Passiva Mensal Desejada</label>
                                                <input type="text" id="meta_fi_renda" class="form-control money-input" placeholder="R$ 10.000,00">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Taxa de Retirada Segura (%)</label>
                                                <input type="number" id="meta_fi_taxa" class="form-control" value="4" step="0.1">
                                                <small class="text-muted">Regra dos 4%: retirada anual segura</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Prazo (anos)</label>
                                                <input type="number" id="meta_fi_prazo" class="form-control" value="20">
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                <small>Patrimônio necessário:</small><br>
                                                <strong id="meta_fi_patrimonio">R$ 0,00</strong><br>
                                                <small>Aporte mensal:</small><br>
                                                <strong id="meta_fi_mensal">R$ 0,00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-info-circle me-2"></i>Dicas para Metas de Longo Prazo:</h6>
                                        <ul class="mb-0">
                                            <li>Revise suas metas anualmente e ajuste os aportes conforme necessário</li>
                                            <li>Considere a inflação ao planejar metas de longo prazo (média de 4-5% ao ano)</li>
                                            <li>Diversifique investimentos para reduzir riscos em prazos longos</li>
                                            <li>Comece o quanto antes - o tempo é seu maior aliado nos juros compostos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
}

document.querySelectorAll('.money-input').forEach(input => {
    input.addEventListener('input', function() {
        formatMoney(this);
    });
});

// Calcular resultado do cenário em tempo real
document.querySelector('[name="valor_base"]').addEventListener('input', calcularResultadoCenario);
document.querySelector('[name="percentual_variacao"]').addEventListener('input', calcularResultadoCenario);

function calcularResultadoCenario() {
    const valorBase = unformatMoney(document.querySelector('[name="valor_base"]').value);
    const percentual = parseFloat(document.querySelector('[name="percentual_variacao"]').value) || 0;
    const resultado = valorBase * (1 + (percentual / 100));
    document.getElementById('resultado-cenario').textContent = resultado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Submeter form com valores corretos
document.getElementById('formCenario').addEventListener('submit', function(e) {
    const valorInput = this.querySelector('[name="valor_base"]');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'valor_base';
    hiddenInput.value = unformatMoney(valorInput.value);
    this.appendChild(hiddenInput);
    valorInput.removeAttribute('name');
});

// Simulador de Aposentadoria
let graficoAposentadoria = null;

function calcularAposentadoria() {
    const idadeAtual = parseInt(document.getElementById('idade_atual').value);
    const idadeAposenta = parseInt(document.getElementById('idade_aposenta').value);
    const rendaDesejada = unformatMoney(document.getElementById('renda_desejada').value);
    const aporteMensal = unformatMoney(document.getElementById('aporte_mensal').value);
    const taxaRetorno = parseFloat(document.getElementById('taxa_retorno').value) / 100;
    const patrimonioInicial = unformatMoney(document.getElementById('patrimonio_inicial').value);
    
    const anosFaltantes = idadeAposenta - idadeAtual;
    const mesesFaltantes = anosFaltantes * 12;
    
    // Cálculo do patrimônio necessário (Regra dos 4%)
    const patrimonioNecessario = (rendaDesejada * 12) / 0.04;
    
    // Cálculo do patrimônio projetado com aportes
    const taxaMensal = Math.pow(1 + taxaRetorno, 1/12) - 1;
    let patrimonioProjetado = patrimonioInicial;
    
    // Valor futuro com aportes mensais
    const fvAportes = aporteMensal * ((Math.pow(1 + taxaMensal, mesesFaltantes) - 1) / taxaMensal);
    const fvPatrimonio = patrimonioInicial * Math.pow(1 + taxaMensal, mesesFaltantes);
    patrimonioProjetado = fvPatrimonio + fvAportes;
    
    // Renda mensal possível com patrimônio projetado
    const rendaPossivel = (patrimonioProjetado * 0.04) / 12;
    
    // Atualizar interface
    document.getElementById('anos_faltantes').textContent = anosFaltantes + ' anos';
    document.getElementById('patrimonio_necessario').textContent = 'R$ ' + patrimonioNecessario.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('patrimonio_projetado').textContent = 'R$ ' + patrimonioProjetado.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('renda_possivel').textContent = 'R$ ' + rendaPossivel.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    
    // Avaliar se atingirá o objetivo
    const statusDiv = document.getElementById('status-objetivo');
    const mensagemDiv = document.getElementById('mensagem-objetivo');
    const recomendacaoDiv = document.getElementById('recomendacao-objetivo');
    
    if (patrimonioProjetado >= patrimonioNecessario) {
        statusDiv.className = 'alert alert-success';
        mensagemDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Você atingirá seu objetivo!';
        const excedente = patrimonioProjetado - patrimonioNecessario;
        recomendacaoDiv.textContent = `Você terá um excedente de R$ ${excedente.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} ou poderá se aposentar antes do planejado.`;
    } else {
        statusDiv.className = 'alert alert-warning';
        mensagemDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Você não atingirá o objetivo com o aporte atual';
        const deficit = patrimonioNecessario - patrimonioProjetado;
        const aporteNecessario = (deficit / ((Math.pow(1 + taxaMensal, mesesFaltantes) - 1) / taxaMensal));
        const totalNecessario = aporteMensal + aporteNecessario;
        recomendacaoDiv.textContent = `Faltam R$ ${deficit.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}. Aumente o aporte mensal para R$ ${totalNecessario.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} ou reduza a renda desejada.`;
    }
    
    // Mostrar resultados
    document.getElementById('placeholder-aposentadoria').classList.add('d-none');
    document.getElementById('resultado-aposentadoria').classList.remove('d-none');
    
    // Gerar gráfico de evolução
    const anos = [];
    const valores = [];
    let valorAcumulado = patrimonioInicial;
    
    for (let ano = 0; ano <= anosFaltantes; ano++) {
        anos.push(idadeAtual + ano);
        valores.push(valorAcumulado);
        
        // Acumular próximo ano
        for (let mes = 0; mes < 12; mes++) {
            valorAcumulado = valorAcumulado * (1 + taxaMensal) + aporteMensal;
        }
    }
    
    if (graficoAposentadoria) {
        graficoAposentadoria.destroy();
    }
    
    const ctx = document.getElementById('graficoAposentadoria').getContext('2d');
    graficoAposentadoria = new Chart(ctx, {
        type: 'line',
        data: {
            labels: anos,
            datasets: [{
                label: 'Patrimônio Projetado',
                data: valores,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Meta de Patrimônio',
                data: Array(anos.length).fill(patrimonioNecessario),
                borderColor: '#ffc107',
                borderDash: [5, 5],
                tension: 0,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                        }
                    }
                }
            }
        }
    });
}

// Metas de Longo Prazo - Calculadoras em tempo real
document.getElementById('meta_casa_valor').addEventListener('input', function() {
    const valor = unformatMoney(this.value);
    const entrada = valor * 0.2;
    const prazo = parseInt(document.getElementById('meta_casa_prazo').value) || 15;
    const mensal = entrada / (prazo * 12);
    
    document.getElementById('meta_casa_entrada').value = 'R$ ' + entrada.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('meta_casa_mensal').textContent = 'R$ ' + mensal.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
});

document.getElementById('meta_casa_prazo').addEventListener('input', function() {
    document.getElementById('meta_casa_valor').dispatchEvent(new Event('input'));
});

document.getElementById('meta_educacao_valor').addEventListener('input', calcularMetaEducacao);
document.getElementById('meta_educacao_prazo').addEventListener('input', calcularMetaEducacao);
document.getElementById('meta_educacao_rendimento').addEventListener('input', calcularMetaEducacao);

function calcularMetaEducacao() {
    const valor = unformatMoney(document.getElementById('meta_educacao_valor').value);
    const anos = parseInt(document.getElementById('meta_educacao_prazo').value) || 18;
    const rendimento = parseFloat(document.getElementById('meta_educacao_rendimento').value) / 100 || 0.06;
    
    const meses = anos * 12;
    const taxaMensal = Math.pow(1 + rendimento, 1/12) - 1;
    const mensal = valor / ((Math.pow(1 + taxaMensal, meses) - 1) / taxaMensal);
    
    document.getElementById('meta_educacao_mensal').textContent = 'R$ ' + mensal.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

document.getElementById('meta_fi_renda').addEventListener('input', calcularMetaFI);
document.getElementById('meta_fi_taxa').addEventListener('input', calcularMetaFI);
document.getElementById('meta_fi_prazo').addEventListener('input', calcularMetaFI);

function calcularMetaFI() {
    const rendaMensal = unformatMoney(document.getElementById('meta_fi_renda').value);
    const taxaRetirada = parseFloat(document.getElementById('meta_fi_taxa').value) / 100 || 0.04;
    const anos = parseInt(document.getElementById('meta_fi_prazo').value) || 20;
    
    const patrimonioNecessario = (rendaMensal * 12) / taxaRetirada;
    
    // Assumindo 8% de retorno anual
    const meses = anos * 12;
    const taxaMensal = Math.pow(1.08, 1/12) - 1;
    const mensal = patrimonioNecessario / ((Math.pow(1 + taxaMensal, meses) - 1) / taxaMensal);
    
    document.getElementById('meta_fi_patrimonio').textContent = 'R$ ' + patrimonioNecessario.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('meta_fi_mensal').textContent = 'R$ ' + mensal.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}
</script>

<?php include 'includes/footer.php'; ?>
