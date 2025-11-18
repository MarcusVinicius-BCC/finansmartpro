<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Lógica para processar formulários (Adicionar, Editar, Excluir) será adicionada aqui.

// Lógica para buscar os dados do orçamento
$month = $_GET['month'] ?? date('Y-m');
$orcamentos = [];
$gastos = [];

try {
    // Buscar orçamentos definidos pelo usuário para o mês
    $stmt = $pdo->prepare("
        SELECT o.id, o.id_categoria, o.valor_limite, c.nome as categoria_nome, c.cor, c.icone
        FROM orcamentos o
        JOIN categorias c ON o.id_categoria = c.id
        WHERE o.id_usuario = ? AND o.mes_ano = ?
    ");
    $stmt->execute([$user_id, $month]);
    $orcamentos_definidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar gastos de todas as categorias de despesa para o mês
    $stmt = $pdo->prepare("
        SELECT l.id_categoria, c.nome as categoria_nome, c.cor, c.icone, SUM(l.valor) as total_gasto
        FROM lancamentos l
        JOIN categorias c ON l.id_categoria = c.id
        WHERE l.id_usuario = ? AND STRFTIME('%Y-%m', l.data) = ? AND l.tipo = 'despesa'
        GROUP BY l.id_categoria
    ");
    $stmt->execute([$user_id, $month]);
    $gastos_mes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id_categoria => total_gasto

    // Unir orçamentos e gastos
    foreach ($orcamentos_definidos as $orc) {
        $gasto_atual = $gastos_mes[$orc['id_categoria']] ?? 0;
        $progresso = ($orc['valor_limite'] > 0) ? ($gasto_atual / $orc['valor_limite']) * 100 : 0;
        
        $orcamentos[$orc['id_categoria']] = [
            ...$orc,
            'gasto_atual' => $gasto_atual,
            'progresso' => min($progresso, 100)
        ];
    }

    // Buscar categorias de despesa para o formulário
    $stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'despesa' ORDER BY nome");
    $stmt->execute();
    $categorias_despesa = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Lidar com erros de banco de dados
    die("Erro ao buscar dados: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Meus Orçamentos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoOrcamentoModal">
            <i class="fas fa-plus"></i> Novo Orçamento
        </button>
    </div>

    <!-- Filtro de Mês -->
    <div class="card p-3 mb-4">
        <form method="get" class="row g-3 align-items-center">
            <div class="col-md-3">
                <label for="month" class="form-label">Selecionar Mês:</label>
                <input type="month" id="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="row">
        <?php if (empty($orcamentos)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nenhum orçamento definido para este mês. Crie um clicando em "Novo Orçamento".
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orcamentos as $orc): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas <?= htmlspecialchars($orc['icone']) ?> me-2" style="color: <?= htmlspecialchars($orc['cor']) ?>;"></i>
                                <?= htmlspecialchars($orc['categoria_nome']) ?>
                            </h5>
                            <!-- Botão de Ações (Editar/Excluir) será adicionado aqui -->
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small">Progresso</label>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $orc['progresso'] ?>%;" aria-valuenow="<?= $orc['progresso'] ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= number_format($orc['progresso'], 0) ?>%
                                    </div>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted">Gasto:</small>
                                <strong class="d-block">R$ <?= number_format($orc['gasto_atual'], 2, ',', '.') ?></strong>
                            </div>
                            <div>
                                <small class="text-muted">Limite:</small>
                                <strong class="d-block">R$ <?= number_format($orc['valor_limite'], 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Orçamento -->
<div class="modal fade" id="novoOrcamentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="orcamento.php" method="post">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="mes_ano" value="<?= htmlspecialchars($month) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias_despesa as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="valor_limite" class="form-label">Valor Limite</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="valor_limite" name="valor_limite" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Orçamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modais de Edição e Exclusão serão adicionados aqui -->

<?php require_once 'includes/footer.php'; ?>
