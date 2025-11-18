<?php
echo "<h1>Teste de Diagnóstico cURL</h1>";
echo "<p>Tentando conectar a: <strong>https://api.exchangerate.host/latest?base=BRL</strong></p>";

// Initialize cURL
$ch = curl_init();

// Set the URL
curl_setopt($ch, CURLOPT_URL, "https://api.exchangerate.host/latest?base=BRL");

// Set the option to return the response, rather than printing it
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo "<h2>Erro Encontrado!</h2>";
    echo "<p style='color:red; font-family:monospace; background-color:#f0f0f0; padding:10px; border-radius:5px;'>";
    echo "<strong>Código do Erro cURL:</strong> " . curl_errno($ch) . "<br>";
    echo "<strong>Mensagem de Erro:</strong> " . curl_error($ch);
    echo "</p>";
    echo "<h3>Próximos Passos:</h3>";
    echo "<p>O erro acima é a causa do problema. A causa mais comum é um problema de certificado SSL (se o erro mencionar SSL ou 'certificate').</p>";
} else {
    echo "<h2>Sucesso!</h2>";
    echo "<p style='color:green;'>A conexão foi bem-sucedida e uma resposta foi recebida.</p>";
    echo "<h3>Resposta da API (primeiros 200 caracteres):</h3>";
    echo "<pre style='font-family:monospace; background-color:#f0f0f0; padding:10px; border-radius:5px;'>" . htmlspecialchars(substr($response, 0, 200)) . "...</pre>";
    echo "<p>Se você está vendo esta mensagem, a conexão do seu servidor está funcionando. O problema pode ser outro.</p>";
}

// Close cURL resource
curl_close($ch);
?>