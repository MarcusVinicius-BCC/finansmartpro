<?php
require_once '../includes/db.php';
require_once '../includes/Cache.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $currentMonth = date('Y-m');
    
    // Inicializar cache com TTL de 15 minutos (900 segundos)
    $cache = new Cache('../cache/', 900);
    $cacheKey = "dashboard_summary_{$userId}_{$currentMonth}";
    
    // Tentar obter do cache
    $summary = $cache->get($cacheKey);
    
    if ($summary === null) {
        // Cache miss - calcular dados
        
        // Get monthly income
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total 
            FROM lancamentos 
            WHERE id_usuario = ? 
            AND tipo = 'receita' 
            AND DATE_FORMAT(data, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $currentMonth]);
        $income = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get monthly expenses
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total 
            FROM lancamentos 
            WHERE id_usuario = ? 
            AND tipo = 'despesa' 
            AND DATE_FORMAT(data, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $currentMonth]);
        $expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Calculate total balance (all time)
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END), 0) as balance
            FROM lancamentos 
            WHERE id_usuario = ?
        ");
        $stmt->execute([$userId]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC)['balance'];
        
        // Preparar dados para cache
        $summary = [
            'success' => true,
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $balance,
            'cached' => false,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Armazenar no cache (15 minutos)
        $cache->set($cacheKey, $summary);
    } else {
        // Cache hit
        $summary['cached'] = true;
    }

    echo json_encode($summary);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>