<?php
/**
 * Teste do Sistema Antivírus
 * FinanSmart Pro
 */

require_once 'includes/AntivirusScanner.php';

echo "🛡️ TESTE DO SISTEMA ANTIVÍRUS\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Verificar scanner disponível
echo "1️⃣ VERIFICANDO SCANNER DISPONÍVEL...\n";
$status = AntivirusScanner::getScannerStatus();

echo "   Scanner: " . $status['scanner'] . "\n";
echo "   Disponível: " . ($status['available'] ? '✅ SIM' : '⚠️  NÃO (usando validação manual)') . "\n";
echo "   Descrição: " . $status['description'] . "\n\n";

// 2. Teste com arquivo limpo
echo "2️⃣ TESTANDO ARQUIVO LIMPO...\n";
$testClean = 'test_clean.txt';
file_put_contents($testClean, 'Este é um arquivo de teste limpo.');

$result = AntivirusScanner::scanFile($testClean);

echo "   Resultado: " . ($result['safe'] ? '✅ LIMPO' : '❌ AMEAÇA') . "\n";
echo "   Scanner: " . ($result['scanner'] ?? 'N/A') . "\n";
echo "   Tempo: " . ($result['scan_time'] ?? 'N/A') . "\n";
echo "   Tamanho: " . ($result['file_size'] ?? 0) . " bytes\n\n";

unlink($testClean);

// 3. Teste com imagem válida (PNG)
echo "3️⃣ TESTANDO IMAGEM PNG VÁLIDA...\n";
$testPNG = 'test_image.png';

// PNG 1x1 pixel transparente válido
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
file_put_contents($testPNG, $pngData);

$result = AntivirusScanner::scanFile($testPNG);

echo "   Resultado: " . ($result['safe'] ? '✅ LIMPO' : '❌ AMEAÇA') . "\n";
echo "   Scanner: " . ($result['scanner'] ?? 'N/A') . "\n";

if (!$result['safe']) {
    echo "   ⚠️  Ameaça: " . ($result['threat'] ?? 'Desconhecida') . "\n";
    if (isset($result['checks'])) {
        echo "   Verificações:\n";
        foreach ($result['checks'] as $check) {
            echo "      - " . ($check['check'] ?? 'N/A') . ": " . ($check['valid'] ? 'OK' : 'FALHOU') . "\n";
        }
    }
}

unlink($testPNG);
echo "\n";

// 4. Teste com script PHP embutido (MALICIOSO)
echo "4️⃣ TESTANDO SCRIPT MALICIOSO...\n";
$testMalicious = 'test_malicious.jpg';

// Arquivo que finge ser JPG mas contém PHP
$maliciousData = "\xFF\xD8\xFF\xE0" . "<?php system(\$_GET['cmd']); ?>";
file_put_contents($testMalicious, $maliciousData);

$result = AntivirusScanner::scanFile($testMalicious);

echo "   Resultado: " . ($result['safe'] ? '❌ NÃO DETECTOU (PROBLEMA!)' : '✅ BLOQUEADO') . "\n";
echo "   Scanner: " . ($result['scanner'] ?? 'N/A') . "\n";

if (!$result['safe']) {
    echo "   ✅ Ameaça detectada: " . ($result['threat'] ?? 'Script malicioso') . "\n";
    if (isset($result['checks'])) {
        echo "   Verificações falhadas:\n";
        foreach ($result['checks'] as $check) {
            if (!$check['valid']) {
                echo "      - " . ($check['check'] ?? 'N/A');
                if (isset($check['error'])) {
                    echo " (" . $check['error'] . ")";
                }
                echo "\n";
            }
        }
    }
}

unlink($testMalicious);
echo "\n";

// 5. Teste EICAR (se ClamAV estiver instalado)
if ($status['available']) {
    echo "5️⃣ TESTANDO ARQUIVO EICAR (padrão de teste)...\n";
    $testEicar = 'test_eicar.txt';
    
    // String EICAR oficial (NÃO É VÍRUS REAL - apenas para teste)
    $eicarString = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
    file_put_contents($testEicar, $eicarString);
    
    $result = AntivirusScanner::scanFile($testEicar);
    
    echo "   Resultado: " . ($result['safe'] ? '⚠️  NÃO DETECTOU' : '✅ BLOQUEADO') . "\n";
    echo "   Scanner: " . ($result['scanner'] ?? 'N/A') . "\n";
    
    if (!$result['safe']) {
        echo "   ✅ Ameaça: " . ($result['threat'] ?? 'EICAR detectado') . "\n";
    }
    
    unlink($testEicar);
    echo "\n";
} else {
    echo "5️⃣ TESTE EICAR PULADO (requer ClamAV/Windows Defender)\n\n";
}

// 6. Verificar logs
echo "6️⃣ VERIFICANDO LOGS...\n";
$logFile = 'logs/antivirus_' . date('Y-m-d') . '.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $count = count($lines);
    echo "   ✅ Log encontrado: {$logFile}\n";
    echo "   📊 Total de scans hoje: {$count}\n";
    
    if ($count > 0) {
        echo "   📝 Último scan:\n";
        $lastScan = json_decode(end($lines), true);
        if ($lastScan) {
            echo "      - Arquivo: " . ($lastScan['file'] ?? 'N/A') . "\n";
            echo "      - Resultado: " . ($lastScan['result'] ?? 'N/A') . "\n";
            echo "      - Scanner: " . ($lastScan['scanner'] ?? 'N/A') . "\n";
            echo "      - Tempo: " . ($lastScan['scan_time'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "   ⚠️  Nenhum log encontrado ainda\n";
}

echo "\n";

// Resumo
echo str_repeat("=", 60) . "\n";
echo "✅ TESTE CONCLUÍDO\n\n";

echo "📋 RESUMO:\n";
echo "   - Scanner ativo: " . $status['description'] . "\n";
echo "   - Proteção básica: ✅ ATIVA (validação manual)\n";

if ($status['available']) {
    echo "   - Proteção avançada: ✅ ATIVA (" . $status['scanner'] . ")\n";
    echo "\n🎉 SISTEMA TOTALMENTE PROTEGIDO!\n";
} else {
    echo "   - Proteção avançada: ⚠️  INATIVA\n";
    echo "\n⚠️  RECOMENDAÇÃO:\n";
    echo "   Instale o ClamAV para proteção completa.\n";
    echo "   Veja instruções em: ANTIVIRUS_SETUP.md\n";
}

echo "\n";
