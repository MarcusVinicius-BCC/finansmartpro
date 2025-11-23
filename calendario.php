<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Obter mês e ano (padrão: mês atual)
$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

// Validar mês e ano
$mes = max(1, min(12, (int)$mes));
$ano = max(2000, min(2100, (int)$ano));

// Calcular primeiro e último dia do mês
$primeiro_dia = "$ano-$mes-01";
$ultimo_dia = date('Y-m-t', strtotime($primeiro_dia));

// Buscar lançamentos do mês
$sql = "SELECT l.*, c.nome as categoria, c.cor as cor_categoria
        FROM lancamentos l
        LEFT JOIN categorias c ON l.id_categoria = c.id
        WHERE l.id_usuario = ? 
        AND l.data BETWEEN ? AND ?
        ORDER BY l.data, l.id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $primeiro_dia, $ultimo_dia]);
$lancamentos = $stmt->fetchAll();

// Buscar contas a pagar/receber do mês
$sql = "SELECT id, descricao, valor, vencimento, 'pagar' as origem, status
        FROM contas_pagar
        WHERE id_usuario = ?
        AND vencimento BETWEEN ? AND ?
        UNION ALL
        SELECT id, descricao, valor, vencimento, 'receber' as origem, status
        FROM contas_receber
        WHERE id_usuario = ?
        AND vencimento BETWEEN ? AND ?
        ORDER BY vencimento";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $primeiro_dia, $ultimo_dia, $user_id, $primeiro_dia, $ultimo_dia]);
$contas = $stmt->fetchAll();

// Buscar lembretes do mês (se existir tabela)
$lembretes = [];
try {
    $sql = "SELECT * FROM lembretes 
            WHERE id_usuario = ? 
            AND data_lembrete BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $primeiro_dia, $ultimo_dia]);
    $lembretes = $stmt->fetchAll();
} catch (Exception $e) {
    // Tabela não existe, ignorar
}

// Organizar eventos por dia
$eventos_por_dia = [];

foreach ($lancamentos as $lanc) {
    $dia = (int)date('d', strtotime($lanc['data']));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = [
        'tipo' => 'lancamento',
        'subtipo' => $lanc['tipo'],
        'descricao' => $lanc['descricao'],
        'valor' => $lanc['valor'],
        'categoria' => $lanc['categoria'],
        'cor' => $lanc['cor_categoria'] ?? '#007bff',
        'data' => $lanc['data']
    ];
}

foreach ($contas as $conta) {
    $dia = (int)date('d', strtotime($conta['vencimento']));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = [
        'tipo' => 'conta',
        'subtipo' => $conta['origem'],
        'descricao' => $conta['descricao'],
        'valor' => $conta['valor'],
        'status' => $conta['status'],
        'data' => $conta['vencimento']
    ];
}

foreach ($lembretes as $lembrete) {
    $dia = (int)date('d', strtotime($lembrete['data_lembrete']));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = [
        'tipo' => 'lembrete',
        'descricao' => $lembrete['titulo'],
        'data' => $lembrete['data_lembrete']
    ];
}

// Calcular estatísticas do mês
$sql = "SELECT 
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
        COUNT(*) as total_lancamentos
        FROM lancamentos
        WHERE id_usuario = ? 
        AND data BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $primeiro_dia, $ultimo_dia]);
$stats = $stmt->fetch();

// Obter informações do calendário
$primeiro_dia_semana = (int)date('w', strtotime($primeiro_dia)); // 0 = domingo
$total_dias = (int)date('t', strtotime($primeiro_dia));

// Meses em português
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

include 'includes/header.php';
?>

<style>
.calendario-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
}
.calendario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.calendario-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}
.dia-semana {
    text-align: center;
    font-weight: bold;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}
.dia-celula {
    min-height: 100px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 5px;
    position: relative;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}
.dia-celula:hover {
    background: #f8f9fa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.dia-celula.hoje {
    border: 2px solid #007bff;
    background: #e7f3ff;
}
.dia-celula.vazio {
    background: #f8f9fa;
    cursor: default;
}
.dia-numero {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 5px;
}
.evento {
    font-size: 10px;
    padding: 2px 4px;
    margin: 2px 0;
    border-radius: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: white;
}
.evento-receita {
    background: #28a745;
}
.evento-despesa {
    background: #dc3545;
}
.evento-pagar {
    background: #ffc107;
    color: #000;
}
.evento-receber {
    background: #17a2b8;
}
.evento-lembrete {
    background: #6c757d;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendário Financeiro</h2>
            <p class="text-white-50">Visualize suas transações em um calendário mensal</p>
        </div>
    </div>

    <!-- Estatísticas do Mês -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                    <h4 class="mb-0 text-success">R$ <?= number_format($stats['total_receitas'] ?? 0, 2, ',', '.') ?></h4>
                    <small class="text-muted">Receitas do Mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                    <h4 class="mb-0 text-danger">R$ <?= number_format($stats['total_despesas'] ?? 0, 2, ',', '.') ?></h4>
                    <small class="text-muted">Despesas do Mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-balance-scale fa-2x text-primary mb-2"></i>
                    <h4 class="mb-0 text-<?= (($stats['total_receitas'] ?? 0) - ($stats['total_despesas'] ?? 0)) >= 0 ? 'success' : 'danger' ?>">
                        R$ <?= number_format(($stats['total_receitas'] ?? 0) - ($stats['total_despesas'] ?? 0), 2, ',', '.') ?>
                    </h4>
                    <small class="text-muted">Saldo do Mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x text-info mb-2"></i>
                    <h4 class="mb-0"><?= $stats['total_lancamentos'] ?? 0 ?></h4>
                    <small class="text-muted">Lançamentos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendário -->
    <div class="card shadow">
        <div class="card-body">
            <div class="calendario-container">
                <!-- Navegação -->
                <div class="calendario-header">
                    <a href="?mes=<?= $mes == 1 ? 12 : $mes - 1 ?>&ano=<?= $mes == 1 ? $ano - 1 : $ano ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                    
                    <h4 class="mb-0"><?= $meses[$mes] ?> de <?= $ano ?></h4>
                    
                    <a href="?mes=<?= $mes == 12 ? 1 : $mes + 1 ?>&ano=<?= $mes == 12 ? $ano + 1 : $ano ?>" 
                       class="btn btn-outline-primary">
                        Próximo <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <!-- Legenda -->
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <span class="badge evento-receita">Receita</span>
                    <span class="badge evento-despesa">Despesa</span>
                    <span class="badge evento-pagar">A Pagar</span>
                    <span class="badge evento-receber">A Receber</span>
                    <span class="badge evento-lembrete">Lembrete</span>
                </div>

                <!-- Grid do Calendário -->
                <div class="calendario-grid">
                    <!-- Cabeçalho: Dias da semana -->
                    <div class="dia-semana">DOM</div>
                    <div class="dia-semana">SEG</div>
                    <div class="dia-semana">TER</div>
                    <div class="dia-semana">QUA</div>
                    <div class="dia-semana">QUI</div>
                    <div class="dia-semana">SEX</div>
                    <div class="dia-semana">SÁB</div>

                    <!-- Células vazias antes do primeiro dia -->
                    <?php for ($i = 0; $i < $primeiro_dia_semana; $i++): ?>
                        <div class="dia-celula vazio"></div>
                    <?php endfor; ?>

                    <!-- Dias do mês -->
                    <?php for ($dia = 1; $dia <= $total_dias; $dia++): ?>
                        <?php
                        $data_dia = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                        $eh_hoje = ($data_dia === date('Y-m-d'));
                        $eventos = $eventos_por_dia[$dia] ?? [];
                        ?>
                        <div class="dia-celula <?= $eh_hoje ? 'hoje' : '' ?>" 
                             onclick="verDetalhes('<?= $data_dia ?>', '<?= $dia ?>', '<?= $meses[$mes] ?>')">
                            <div class="dia-numero"><?= $dia ?></div>
                            <?php foreach (array_slice($eventos, 0, 3) as $evento): ?>
                                <div class="evento evento-<?= $evento['tipo'] === 'lancamento' ? $evento['subtipo'] : ($evento['tipo'] === 'conta' ? $evento['subtipo'] : $evento['tipo']) ?>" 
                                     title="<?= htmlspecialchars($evento['descricao']) ?>">
                                    <?php if (isset($evento['valor'])): ?>
                                        R$ <?= number_format($evento['valor'], 2, ',', '.') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars(substr($evento['descricao'], 0, 15)) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($eventos) > 3): ?>
                                <div class="evento" style="background: #6c757d;">
                                    +<?= count($eventos) - 3 ?> mais
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes do Dia -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">Detalhes do Dia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalConteudo">
                <!-- Conteúdo via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
const eventosPorDia = <?= json_encode($eventos_por_dia) ?>;

function verDetalhes(data, dia, mes) {
    const eventos = eventosPorDia[parseInt(dia)] || [];
    
    if (eventos.length === 0) {
        return;
    }
    
    document.getElementById('modalTitulo').textContent = `${dia} de ${mes}`;
    
    let html = '<div class="list-group">';
    
    eventos.forEach(evento => {
        let icone = '';
        let cor = '';
        let valor = '';
        
        if (evento.tipo === 'lancamento') {
            icone = evento.subtipo === 'receita' ? 'fa-arrow-up' : 'fa-arrow-down';
            cor = evento.subtipo === 'receita' ? 'success' : 'danger';
            valor = `<strong class="text-${cor}">R$ ${formatarMoeda(evento.valor)}</strong>`;
        } else if (evento.tipo === 'conta') {
            icone = evento.subtipo === 'pagar' ? 'fa-file-invoice-dollar' : 'fa-hand-holding-usd';
            cor = evento.subtipo === 'pagar' ? 'warning' : 'info';
            valor = `<strong class="text-${cor}">R$ ${formatarMoeda(evento.valor)}</strong>`;
        } else {
            icone = 'fa-bell';
            cor = 'secondary';
        }
        
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas ${icone} text-${cor} me-2"></i>
                        <strong>${evento.descricao}</strong>
                        ${evento.categoria ? `<br><small class="text-muted ms-4">${evento.categoria}</small>` : ''}
                        ${evento.status ? `<br><small class="ms-4"><span class="badge bg-${evento.status === 'pendente' ? 'warning' : 'success'}">${evento.status}</span></small>` : ''}
                    </div>
                    <div class="text-end">
                        ${valor}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('modalConteudo').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
}

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

<?php include 'includes/footer.php'; ?>
