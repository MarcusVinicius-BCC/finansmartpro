<?php
require_once __DIR__ . '/Cache.php';

function get_rates($base='BRL') {
    $cache = new Cache(__DIR__ . '/../cache/', 3600); // 1 hora TTL
    $cacheKey = "currency_rates_{$base}";
    
    // Tentar obter do cache
    $cachedRates = $cache->get($cacheKey);
    if ($cachedRates !== null) {
        return $cachedRates;
    }
    
    // Buscar taxas atualizadas de API externa
    $url = "https://api.frankfurter.app/latest?from=" . urlencode($base);
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $res = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($res && $httpCode === 200) {
        $json = json_decode($res, true);
        if(isset($json['rates'])) {
            // Salvar no cache
            $cache->set($cacheKey, $json['rates'], 3600);
            return $json['rates'];
        }
    }
    
    // Fallback: tentar cache expirado (ainda pode estar no arquivo)
    $cache_file = __DIR__ . '/../cache/rates.json';
    if(file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if($data && isset($data['rates'])) {
            error_log("AVISO: Usando taxas de câmbio em cache expirado (última atualização: " . date('Y-m-d H:i:s', $data['timestamp']) . ")");
            return $data['rates'];
        }
    }
    
    // Último recurso: taxas fixas aproximadas
    error_log("ERRO: Não foi possível obter taxas de câmbio. Usando taxas fixas de fallback.");
    $fallbackRates = [
        'USD' => 0.20,
        'EUR' => 0.18,
        'GBP' => 0.16,
        'BRL' => 1.0
    ];
    
    // Salvar fallback no cache por 5 minutos
    $cache->set($cacheKey, $fallbackRates, 300);
    
    return $fallbackRates;
}

function convert_amount($amount, $from, $to='BRL') {
    $from = strtoupper($from); $to = strtoupper($to);
    if($from === $to) return round($amount,2);
    $rates = get_rates($from);
    if(isset($rates[$to])) {
        return round($amount * $rates[$to],2);
    }
    $rates2 = get_rates($to);
    if(isset($rates2[$from])) {
        return round($amount / $rates2[$from],2);
    }
    return null;
}

/**
 * Formata o valor pelo locale e adiciona símbolo/sigla da moeda.
 * Retorna string pronta para exibir.
 */
function format_currency($amount, $moeda = 'BRL', $locale = null) {
    $moeda = strtoupper($moeda ?? 'BRL');
    // Determinar locale por moeda se não for passado
    if ($locale === null) {
        if ($moeda === 'USD') $locale = 'en_US';
        else $locale = 'pt_BR';
    }

    // Configurações básicas por locale
    if ($locale === 'pt_BR') {
        $dec = ','; $th = '.'; $space = true;
    } elseif ($locale === 'en_US') {
        $dec = '.'; $th = ','; $space = false;
    } else {
        // fallback
        $dec = ','; $th = '.'; $space = true;
    }

    $formatted = number_format((float)$amount, 2, $dec, $th);

    // Símbolo por moeda
    $symbol = $moeda === 'BRL' ? 'R$' : ($moeda === 'USD' ? '$' : '€');

    // Posição: por padrão mostramos símbolo antes. Ajuste se necessário.
    if ($moeda === 'EUR' && $locale === 'de_DE') {
        // europa continental (ex.: 1.234,56 €)
        return $formatted . ' ' . $symbol;
    }

    if ($space) return $symbol . ' ' . $formatted;
    return $symbol . $formatted;
}
?>