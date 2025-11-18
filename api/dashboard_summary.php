<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $currentMonth = date('Y-m');
    
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

    echo json_encode([
        'success' => true,
        'income' => $income,
        'expenses' => $expenses,
        'balance' => $balance
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>