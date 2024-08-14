<?php
// Adiciona as credenciais de cada loja

$appId = '2912143404307015';
$secretKey = '57r0XStJzR1BQc7v4CQyJE0F6yc6VfuO';
$redirectURI = 'https://perguntassinapse-38007fcc4155.herokuapp.com';
$siteId = 'MLB';

//$_SESSION['access_token'] = 'APP_USR-7174773457857060-081316-3bb07a96026951a2210300533b8e86e1-48650523';
//$_SESSION['access_token'] = '';
//unset($_SESSION['access_token']);

$stores = [
    [
        'store_id' => 'SINAPSE_IMPORTS', // Substitua pelo ID real da loja 1
        'appId' => '2912143404307015', // Substitua pelo appId real da loja 1
        'secretKey' => '57r0XStJzR1BQc7v4CQyJE0F6yc6VfuO', // Substitua pela secretKey real da loja 1
        'redirectURIq' => 'https://uol.com.br', // Substitua pelo redirectURI real da loja 1
        'access_token' => 'APP_USR-2912143404307015-081312-74179fd3c043cad299c42180ad039612-1157662968' // Substitua pelo token real da loja 1
    ],
    [
        'store_id' => 'SINAPSE_MUSIC', // Substitua pelo ID real da loja 2
        'appId' => '7174055562722161', // Substitua pelo appId real da loja 2
        'secretKey' => 'fs7umcmxJpKV2d37N5thSfp8k8k4VwVT', // Substitua pela secretKey real da loja 2
        'redirectURIq' => 'https://www.google.com/', // Substitua pelo redirectURI real da loja 2
        'access_token' => 'APP_USR-7174055562722161-081315-64c8927c74bc174e1b3c73976ce4d6ec-1089224964' // Substitua pelo token real da loja 2
    ],
    [
        'store_id' => 'SINAPSE_CELULARES', // Substitua pelo ID real da loja 3
        'appId' => '7174773457857060', // Substitua pelo appId real da loja 3
        'secretKey' => 'cgY5Vty6OcRMNjBxzj5cuINJ4vXQUee9', // Substitua pela secretKey real da loja 3
        'redirectURIq' => 'https://www.google.com', // Substitua pelo redirectURI real da loja 3
        'access_token' => 'APP_USR-7174773457857060-081316-3bb07a96026951a2210300533b8e86e1-48650523' // Substitua pelo token real da loja 3
    ]
];
?>



