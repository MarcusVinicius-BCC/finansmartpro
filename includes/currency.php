<?php
function get_rates($base='BRL') {
    $cache_file = __DIR__ . '/../cache/rates.json';
    $now = time();
    if(file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if($data && isset($data['timestamp']) && ($now - $data['timestamp'] < 3600)) {
            return $data['rates'];
        }
    }
    $url = "https://api.frankfurter.app/latest?from=" . urlencode($base);
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch); curl_close($ch);
    $json = json_decode($res, true);
    if(isset($json['rates'])) {
        $out = ['timestamp'=>$now, 'base'=>$base, 'rates'=>$json['rates']];
        file_put_contents($cache_file, json_encode($out));
        return $json['rates'];
    }
    if(file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        return $data['rates'] ?? [];
    }
    return [];
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
?>