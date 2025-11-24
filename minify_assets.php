<?php
/**
 * FinanSmart Pro - Minificador de Assets
 * Execute este script ANTES de publicar para produção
 */

// Configurações
$baseDir = __DIR__;
$cssDir = $baseDir . '/assets/css/';
$jsDir = $baseDir . '/assets/js/';

// Arrays para armazenar arquivos
$cssFiles = [];
$jsFiles = [];

echo "==============================================\n";
echo "FinanSmart Pro - Minificador de Assets\n";
echo "==============================================\n\n";

// ==================================================
// FUNÇÃO: Minificar CSS
// ==================================================
function minifyCSS($css) {
    // Remover comentários
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    // Remover espaços em branco
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    // Remover espaços ao redor de caracteres especiais
    $css = preg_replace('/\s*([{}|:;,>])\s*/', '$1', $css);
    return trim($css);
}

// ==================================================
// FUNÇÃO: Minificar JavaScript
// ==================================================
function minifyJS($js) {
    // Remover comentários de linha única
    $js = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', "\n", $js);
    // Remover comentários de múltiplas linhas
    $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
    // Remover espaços em branco desnecessários
    $js = preg_replace('/\s+/', ' ', $js);
    // Remover espaços ao redor de operadores
    $js = preg_replace('/\s*([=+\-*\/%<>!&|,;:?{}()\[\]])\s*/', '$1', $js);
    return trim($js);
}

// ==================================================
// PROCESSAR ARQUIVOS CSS
// ==================================================
echo "📁 Processando arquivos CSS...\n";
echo "----------------------------------------------\n";

$cssFiles = glob($cssDir . '*.css');
$totalCssSavings = 0;

foreach ($cssFiles as $file) {
    $filename = basename($file);
    
    // Pular arquivos já minificados
    if (strpos($filename, '.min.css') !== false) {
        echo "⏭️  Pulando (já minificado): {$filename}\n";
        continue;
    }
    
    $originalContent = file_get_contents($file);
    $originalSize = strlen($originalContent);
    
    $minifiedContent = minifyCSS($originalContent);
    $minifiedSize = strlen($minifiedContent);
    
    $savings = $originalSize - $minifiedSize;
    $savingsPercent = round(($savings / $originalSize) * 100, 2);
    
    // Salvar arquivo minificado
    $minFile = str_replace('.css', '.min.css', $file);
    file_put_contents($minFile, $minifiedContent);
    
    echo "✅ {$filename}\n";
    echo "   Original: " . number_format($originalSize) . " bytes\n";
    echo "   Minificado: " . number_format($minifiedSize) . " bytes\n";
    echo "   Economia: " . number_format($savings) . " bytes ({$savingsPercent}%)\n";
    echo "   Salvo em: " . basename($minFile) . "\n\n";
    
    $totalCssSavings += $savings;
}

// ==================================================
// PROCESSAR ARQUIVOS JAVASCRIPT
// ==================================================
echo "\n📁 Processando arquivos JavaScript...\n";
echo "----------------------------------------------\n";

$jsFiles = glob($jsDir . '*.js');
$totalJsSavings = 0;

foreach ($jsFiles as $file) {
    $filename = basename($file);
    
    // Pular arquivos já minificados
    if (strpos($filename, '.min.js') !== false) {
        echo "⏭️  Pulando (já minificado): {$filename}\n";
        continue;
    }
    
    $originalContent = file_get_contents($file);
    $originalSize = strlen($originalContent);
    
    $minifiedContent = minifyJS($originalContent);
    $minifiedSize = strlen($minifiedContent);
    
    $savings = $originalSize - $minifiedSize;
    $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100, 2) : 0;
    
    // Salvar arquivo minificado
    $minFile = str_replace('.js', '.min.js', $file);
    file_put_contents($minFile, $minifiedContent);
    
    echo "✅ {$filename}\n";
    echo "   Original: " . number_format($originalSize) . " bytes\n";
    echo "   Minificado: " . number_format($minifiedSize) . " bytes\n";
    echo "   Economia: " . number_format($savings) . " bytes ({$savingsPercent}%)\n";
    echo "   Salvo em: " . basename($minFile) . "\n\n";
    
    $totalJsSavings += $savings;
}

// ==================================================
// RESUMO FINAL
// ==================================================
echo "\n==============================================\n";
echo "📊 RESUMO DA MINIFICAÇÃO\n";
echo "==============================================\n";
echo "CSS:\n";
echo "  Arquivos processados: " . count($cssFiles) . "\n";
echo "  Economia total: " . number_format($totalCssSavings) . " bytes\n\n";
echo "JavaScript:\n";
echo "  Arquivos processados: " . count($jsFiles) . "\n";
echo "  Economia total: " . number_format($totalJsSavings) . " bytes\n\n";
echo "TOTAL ECONOMIZADO: " . number_format($totalCssSavings + $totalJsSavings) . " bytes\n";
echo "==============================================\n\n";

echo "✅ Minificação concluída!\n\n";
echo "⚠️  PRÓXIMOS PASSOS:\n";
echo "1. Atualize os links nos arquivos PHP para usar .min.css e .min.js\n";
echo "2. Teste todas as páginas para garantir que funcionam corretamente\n";
echo "3. Configure o .htaccess para cache de arquivos minificados\n";
echo "4. Execute o database_indexes.sql no MySQL\n";
echo "5. Configure SSL/HTTPS no servidor\n\n";
?>
