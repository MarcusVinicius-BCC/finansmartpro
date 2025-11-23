<?php
require 'includes/db.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar notificações não lidas
$sql = "SELECT * FROM alertas WHERE id_usuario = ? AND status = 'nao_lido' ORDER BY data_criacao DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$notificacoes = $stmt->fetchAll();

// Contar não lidas
$sql = "SELECT COUNT(*) FROM alertas WHERE id_usuario = ? AND status = 'nao_lido'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$total_nao_lidas = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'notificacoes' => $notificacoes,
    'total_nao_lidas' => $total_nao_lidas
]);
