<?php
// Variáveis necessárias para a chamada
$app_id = '2912143404307015';
$secret_key = 'dBvO42FTZmw25z3dLKj13r3zx2mkgK7x';
$authorization_code = 'TG-66bb876092c0e300016c0ba9-1157662968';
$redirect_uri = 'https://uol.com.br';
$code_verifier = 'SEU_CODE_VERIFIER';

// Configurações do cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.mercadolibre.com/oauth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: application/json',
    'content-type: application/x-www-form-urlencoded'
));

// Dados a serem enviados na solicitação POST
$post_fields = http_build_query(array(
    'grant_type' => 'authorization_code',
    'client_id' => $app_id,
    'client_secret' => $secret_key,
    'code' => $authorization_code,
    'redirect_uri' => $redirect_uri,
    'code_verifier' => $code_verifier
));

curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

// Executa a chamada e obtém a resposta
$response = curl_exec($ch);

// Verifica se ocorreu algum erro
if (curl_errno($ch)) {
    echo 'Erro: ' . curl_error($ch);
} else {
    // Decodifica a resposta JSON
    $result = json_decode($response, true);

    // Exibe a resposta ou trata conforme necessário
    echo '<pre>';
    print_r($result);
    echo '</pre>';
}

// Fecha a sessão cURL
curl_close($ch);
?>
