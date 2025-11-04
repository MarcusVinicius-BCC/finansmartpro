<?php
require 'includes/db.php';

try {
    // Tenta adicionar a coluna moeda se ela não existir
    $pdo->exec("ALTER TABLE metas ADD COLUMN IF NOT EXISTS moeda VARCHAR(10) NOT NULL DEFAULT 'BRL' AFTER status;");
    echo "Coluna moeda adicionada com sucesso na tabela metas!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}