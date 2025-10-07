<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/currency.php';
if(!isset($_GET['from'], $_GET['to'], $_GET['amount'])){
    http_response_code(400); echo json_encode(['error'=>'params missing']); exit;
}
$from = strtoupper($_GET['from']); $to = strtoupper($_GET['to']); $amount = floatval($_GET['amount']);
$rates = get_rates($from);
$result = convert_amount($amount, $from, $to);
if ($result === null) {
    http_response_code(500);
    echo json_encode(['error'=>'Could not convert currencies. Check internet connection or currency codes.']);
    exit;
}

echo json_encode(['result'=>round($result,2),'from'=>$from,'to'=>$to]);
?>