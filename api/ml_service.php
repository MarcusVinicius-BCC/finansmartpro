<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

function calculateFinancialHealth($userId) {
    global $pdo;
    
    // Get last 6 months of transactions
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as income,
            SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as expenses,
            DATE_FORMAT(data, '%Y-%m') as month
        FROM lancamentos 
        WHERE id_usuario = ? 
        AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$userId]);
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate metrics
    $metrics = [
        'savings_rate' => 0,
        'expense_stability' => 0,
        'income_growth' => 0,
        'debt_to_income' => 0
    ];
    
    if (count($monthlyData) > 0) {
        // Calculate savings rate
        $totalIncome = array_sum(array_column($monthlyData, 'income'));
        $totalExpenses = array_sum(array_column($monthlyData, 'expenses'));
        $metrics['savings_rate'] = $totalIncome > 0 ? (($totalIncome - $totalExpenses) / $totalIncome) * 100 : 0;
        
        // Calculate expense stability (coefficient of variation)
        $expenses = array_column($monthlyData, 'expenses');
        $avgExpense = array_sum($expenses) / count($expenses);
        $variance = array_sum(array_map(function($x) use ($avgExpense) {
            return pow($x - $avgExpense, 2);
        }, $expenses)) / count($expenses);
        $metrics['expense_stability'] = 100 - (sqrt($variance) / $avgExpense * 100);
        
        // Calculate income growth
        $firstIncome = $monthlyData[0]['income'];
        $lastIncome = end($monthlyData)['income'];
        $metrics['income_growth'] = $firstIncome > 0 ? (($lastIncome - $firstIncome) / $firstIncome) * 100 : 0;
    }
    
    // Calculate financial health score (0-100)
    $weights = [
        'savings_rate' => 0.4,
        'expense_stability' => 0.3,
        'income_growth' => 0.3
    ];
    
    $score = 0;
    foreach ($metrics as $key => $value) {
        $normalizedValue = min(max($value, 0), 100);
        $score += $normalizedValue * $weights[$key];
    }
    
    return [
        'score' => round($score),
        'metrics' => $metrics,
        'history' => $monthlyData
    ];
}

function predictExpenses($userId) {
    global $pdo;
    
    // Get historical data
    $stmt = $pdo->prepare("
        SELECT 
            SUM(valor) as total,
            DATE_FORMAT(data, '%Y-%m') as month,
            id_categoria
        FROM lancamentos 
        WHERE id_usuario = ? 
        AND tipo = 'despesa'
        AND data >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(data, '%Y-%m'), id_categoria
        ORDER BY month
    ");
    $stmt->execute([$userId]);
    $historical = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Simple moving average prediction
    $predictions = [];
    $categories = array_unique(array_column($historical, 'id_categoria'));
    
    foreach ($categories as $categoryId) {
        $categoryData = array_filter($historical, function($item) use ($categoryId) {
            return $item['id_categoria'] == $categoryId;
        });
        
        if (count($categoryData) >= 3) {
            $recent = array_slice($categoryData, -3);
            $avg = array_sum(array_column($recent, 'total')) / 3;
            $predictions[$categoryId] = round($avg, 2);
        }
    }
    
    return $predictions;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'health':
            echo json_encode(['success' => true, 'data' => calculateFinancialHealth($userId)]);
            break;
            
        case 'predict':
            echo json_encode(['success' => true, 'data' => predictExpenses($userId)]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>