<?php
require 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moeda_base'])) {
    $allowed = ['BRL','USD','EUR'];
    $moeda = in_array($_POST['moeda_base'], $allowed) ? $_POST['moeda_base'] : 'BRL';
    $stmt = $pdo->prepare('UPDATE usuarios SET moeda_base = ? WHERE id = ?');
    $stmt->execute([$moeda, $_SESSION['user_id']]);
    // Registrar no log do servidor para debug (opcional)
    error_log("User {$_SESSION['user_id']} set moeda_base = {$moeda}");
}

$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header('Location: ' . $redirect);
exit;
