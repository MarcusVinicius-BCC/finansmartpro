<?php
/**
 * API Endpoint para obter CSRF Token
 * Usado pelo JavaScript csrf.js como fallback
 */

require_once '../includes/security.php';

// Configurar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gerar e retornar token
header('Content-Type: application/json');
echo json_encode([
    'token' => Security::generateCSRFToken(),
    'expires_in' => 1800 // 30 minutos
]);
