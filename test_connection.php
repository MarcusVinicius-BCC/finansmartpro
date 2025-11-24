<?php
/**
 * Teste de Conexão com Banco de Dados
 */

require_once 'includes/db.php';

echo "✅ Conexão estabelecida com sucesso!\n";
echo "📊 Banco de dados: " . $db . "\n";
echo "🖥️  Host: " . $host . "\n";
echo "👤 Usuário: " . $user . "\n";

// Testar uma query simples
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "👥 Total de usuários: " . $result['total'] . "\n";
    echo "\n✨ Sistema funcionando perfeitamente!\n";
} catch (PDOException $e) {
    echo "❌ Erro ao consultar banco: " . $e->getMessage() . "\n";
}
?>
