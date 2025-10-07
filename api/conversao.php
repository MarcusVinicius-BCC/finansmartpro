<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/currency.php';
if(!isset($_GET['from'], $_GET['to'], $_GET['amount'])){
    http_response_code(400); echo json_encode(['error'=>'params missing']); exit;
}
$from = strtoupper($_GET['from']); $to = strtoupper($_GET['to']); $amount = floatval($_GET['amount']);
$rates = get_rates($from);
$result = null;
if(isset($rates[$to])){
    $result = $amount * $rates[$to];
} else {
    $result = convert_amount($amount, $from, $to);
}
echo json_encode(['result'=>round($result,2),'from'=>$from,'to'=>$to]);
?>