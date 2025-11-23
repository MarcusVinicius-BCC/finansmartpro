<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/currency.php';

// Se não tiver parâmetros, retorna taxas atuais
if(!isset($_GET['from']) && !isset($_GET['to']) && !isset($_GET['amount'])){
    $base = $_GET['base'] ?? 'BRL';
    $rates = get_rates($base);
    echo json_encode($rates);
    exit;
}

if(!isset($_GET['from'], $_GET['to'], $_GET['amount'])){
    http_response_code(400); 
    echo json_encode(['error'=>'params missing', 'message' => 'Parâmetros obrigatórios: from, to, amount']); 
    exit;
}

$from = strtoupper($_GET['from']); 
$to = strtoupper($_GET['to']); 
$amount = floatval($_GET['amount']);

$result = convert_amount($amount, $from, $to);

if ($result === null) {
    http_response_code(500);
    echo json_encode([
        'error' => 'conversion_failed',
        'message' => 'Não foi possível converter as moedas. Verifique a conexão ou códigos de moeda.',
        'from' => $from,
        'to' => $to
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'result' => round($result, 2),
    'from' => $from,
    'to' => $to,
    'amount' => $amount,
    'rate' => $result / $amount
]);
?>