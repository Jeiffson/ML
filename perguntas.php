<?php
require_once 'header.php';
require 'lib/Meli/meli.php';
require 'configApp.php';


$_SESSION['access_token'] = 'APP_USR-2912143404307015-081320-8291adc7b93b35e9b54dfa974b6a7ccf-1157662968';

unset($_SESSION['access_token']);
// Define o fuso horário para Horário de Brasília
date_default_timezone_set('America/Sao_Paulo');

function fetchPlainText($codAnuncio) {
    $descriptionUrl = 'https://api.mercadolibre.com/items/' . $codAnuncio . '/description';
    $response = file_get_contents($descriptionUrl);
    $data = json_decode($response, true);
    return $data['plain_text'] ?? '';
}

function fetchLocalXML($url) {
    $xmlContent = file_get_contents($url);
    $xml = simplexml_load_string($xmlContent);
    return $xml;
}

function fetchQuestionsAndAnswers($codAnuncio, $accessToken) {
    $questionsUrl = 'https://api.mercadolibre.com/questions/search?item_id=' . $codAnuncio;
    $response = file_get_contents($questionsUrl . '&access_token=' . $accessToken);
    $data = json_decode($response, true);
    $qna = array_map(function($q) {
        $question = $q['text'] ?? '';
        $answer = $q['answer']['text'] ?? '';
        return [
            'question' => $question,
            'answer' => $answer
        ];
    }, $data['questions'] ?? []);
    return $qna;
}

function askChatGPT($question, $plainText, $localXML1, $localXML2, $questionsAndAnswers) {
 // key chatgpt
    $url = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    $qnaString = '';
    foreach ($questionsAndAnswers as $qna) {
        $qnaString .= 'Pergunta: ' . $qna['question'] . "\nResposta: " . $qna['answer'] . "\n";
    }

    $mensagemBase = 
        "Para a seguinte frase que o cliente escrever no campo de perguntas: " . $question . 
        ". Comporte-se como um atendente de pós-venda de uma loja que irá responder as perguntas dos clientes sobre o produto. " . 
        "Informe somente a resposta da pergunta, sem explicações. " .
        "Retorne apenas a palavra ATENDENTE quando a informação perguntada não estiver nas especificações. " . 
        "Se a pergunta sugerir que o cliente já fez a compra, responda apenas 'ATENDENTE' sem nenhuma saudação. " . 
        "Responda de maneira amigável e não técnica, sempre começando a resposta com 'Olá' e finalizando com 'Obrigado' quando não tiver a palavra ATENDENTE na resposta. " . 
        "A partir desse ponto, todas as informações seguintes referem-se ao produto:" . 
        $localXML1->asXML() . $localXML2->asXML() .  $plainText . $qnaString;

        echo($mensagemBase);

    $data = [
        "model" => "gpt-4",
        "messages" => [
            ["role" => "system", "content" => "Você é um assistente útil."],
            ["role" => "user", "content" => $mensagemBase]
        ],
        "max_tokens" => 150
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Erro: ' . curl_error($ch);
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['choices'][0]['message']['content'] ?? 'Nenhuma resposta recebida.';
}

function getLogEntries($file) {
    if (!file_exists($file)) {
        return [];
    }
    $xml = simplexml_load_file($file);
    $entries = [];
    foreach ($xml->log as $log) {
        $entries[(string)$log->question_id] = (string)$log->response;
    }
    return $entries;
}

function addLogEntry($file, $storeId, $questionId, $question, $response) {
    $logData = file_exists($file) ? new SimpleXMLElement(file_get_contents($file)) : new SimpleXMLElement('<logs></logs>');
    $logEntry = $logData->addChild('log');
    $logEntry->addChild('timestamp', date('Y-m-d H:i:s'));
    $logEntry->addChild('store_id', $storeId);
    $logEntry->addChild('question_id', $questionId);
    $logEntry->addChild('question', htmlspecialchars($question));
    $logEntry->addChild('response', htmlspecialchars($response));
    $logData->asXML($file);
}

$logFile = 'C:\\xampp\\htdocs\\TesteGPT\\log.xml';
$loggedQuestions = getLogEntries($logFile);

$allQuestions = [];

foreach ($stores as $store) {
    $meli = new Meli($store['appId'], $store['secretKey']);
    
    $response = $meli->get('/users/me', array('access_token' => $store['access_token']));
    if (!isset($response['body']->id)) {
        continue; // Pular se não for possível obter o ID da conta
    }
    $id_conta = $response['body']->id;

    $url = '/questions/search';
    $params = [
        'seller_id' => $id_conta,
        'status' => 'UNANSWERED',  // Filtra perguntas não respondidas
        'access_token' => $store['access_token'],
    ];
    $response = $meli->get($url, $params);

    $perguntas = $response['body']->questions ?? [];

    if (!empty($perguntas) && is_array($perguntas)) {
        foreach ($perguntas as $pergunta) {
            $pergunta->store_id = $store['store_id']; // Adiciona o ID da loja à pergunta
            $allQuestions[] = $pergunta;
        }
    }
}
?>
<div class="container" style="padding-top: 20px;">
    <div class="row">
        <?php if (!empty($allQuestions) && is_array($allQuestions)): ?>
            <?php foreach ($allQuestions as $pergunta): ?>
                <?php
                    $product_url = "";
                    $url = '/items/' . $pergunta->item_id;
                    $store = array_filter($stores, function($s) use ($pergunta) { return $s['store_id'] == $pergunta->store_id; });
                    $store = array_shift($store); // Obtemos o primeiro item do array filtrado

                    if (!$store) {
                        continue; // Pular se a loja não for encontrada
                    }

                    $meli = new Meli($store['appId'], $store['secretKey']);
                    $anuncio = $meli->get($url, array('access_token' => $store['access_token']));
                    $product_url = $anuncio['body']->permalink ?? '';

                    // Verifica se a pergunta já está no log
                    if (array_key_exists($pergunta->id, $loggedQuestions)) {
                        $resposta = $loggedQuestions[$pergunta->id]; // Carrega a resposta do log
                    } else {
                        // Obtenha a resposta do ChatGPT para a pergunta
                        $plainText = fetchPlainText($pergunta->item_id);
                        $localXML1 = fetchLocalXML('http://localhost/TesteGPT/treinamentoLoja.xml');
                        $localXML2 = fetchLocalXML('http://localhost/TesteGPT/treinamento.xml');
                        $questionsAndAnswers = fetchQuestionsAndAnswers($pergunta->item_id, $store['access_token']);
                        $resposta = askChatGPT($pergunta->text, $plainText, $localXML1, $localXML2, $questionsAndAnswers);

                        // Adiciona a entrada ao log
                        addLogEntry($logFile, $pergunta->store_id, $pergunta->id, $pergunta->text, $resposta);
                    }
                ?>
                <div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">
                    <div class="card" style="margin-top: 10px;">
                        <h5 class="card-header">ID Pergunta: <?php echo htmlspecialchars($pergunta->id) ?> | Loja ID: <span style="color: blue;"><?php echo htmlspecialchars($pergunta->store_id) ?></span></h5>

                        <div class="card-body">
                            <h5 class="card-title">Produto:
                                <a href="<?php echo htmlspecialchars($product_url) ?>" target="_blank"><?php echo htmlspecialchars($pergunta->item_id) ?></a>
                            </h5>
                            <p class="card-text"><?php echo htmlspecialchars($pergunta->text) ?></p>
                            <p class="card-text"><textarea id="question-<?php echo htmlspecialchars($pergunta->id) ?>"
                                                           style="width: 100%"><?php echo htmlspecialchars($resposta); ?></textarea></p>
                            <button class="btn btn-primary" onclick="sendAnswer(<?php echo htmlspecialchars($pergunta->id) ?>)">
                                Responder
                            </button>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">
                <div class="card" style="text-align: center;">
                    <div class="card-body">
                        <h5 class="card-title">Nenhuma pergunta para listar!</h5>
                        <div style="padding: 10px;">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
require_once 'js.php';
?>
<script>
    function sendAnswer(question_id) {
        var answer_text = $("#question-" + question_id).val();
        var url = "functions.php";
        $.ajax({
            method: "POST",
            url: url,
            async: false,
            cache: false,
            data: {
                "action": "sendanswer",
                "question_id": question_id,
                "text": answer_text
            },
            success: function (data) {
                if (data.indexOf("Erro") > -1) {
                    console.log(data);
                    alert("Erro ao enviar resposta: Verifique os detalhes do erro no console do navegador em modo desenvolvedor(F12)");
                } else {
                    console.log(data);
                    alert("Resposta enviada com sucesso");
                    location.reload();
                }
            },
            error: function (data) {
                alert("Erro, verifique os detalhes no console do navegador em modo desenvolvedor(F12)");
                console.log(data);
                return false;
            }
        });
    }
</script>
<?php
//require_once 'footer.php';
?>
