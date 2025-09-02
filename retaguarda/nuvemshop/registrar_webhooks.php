<?php
/**
 * Arquivo para registrar webhooks na Nuvemshop
 * Este arquivo deve ser executado uma vez para configurar os webhooks
 */

require_once '../conexao.php';

// Função para obter as configurações da Nuvemshop
function obterConfiguracoesNuvemshop() {
    global $conexao;

    $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
    $result = pg_query($conexao, $query);

    if (pg_num_rows($result) > 0) {
        $config = pg_fetch_assoc($result);
        return [
            'access_token' => $config['access_token'],
            'store_id' => $config['code'] // O ID da loja está armazenado no campo 'code'
        ];
    }

    return null;
}

// Função para obter a URL base do sistema
function obterUrlBase() {
    // Verificar se estamos em ambiente de desenvolvimento (localhost)
    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        // Exibir instruções para usar ngrok ou outro serviço de túnel
        echo '<div class="card red lighten-4">
            <div class="card-content">
                <span class="card-title">Ambiente Local Detectado</span>
                <p>A Nuvemshop não permite registrar webhooks para URLs locais (localhost) ou URLs não seguras (HTTP).</p>
                <p>Para testar webhooks localmente, você precisa usar um serviço de túnel como ngrok:</p>
                <ol>
                    <li>Baixe e instale <a href="https://ngrok.com/download" target="_blank">ngrok</a></li>
                    <li>Execute o comando: <code>ngrok http 80</code> (ou a porta que seu servidor local está usando)</li>
                    <li>Copie a URL HTTPS fornecida pelo ngrok (ex: https://abc123.ngrok.io)</li>
                    <li>Insira essa URL abaixo:</li>
                </ol>
                <form method="post">
                    <div class="input-field">
                        <input type="text" name="tunnel_url" id="tunnel_url" placeholder="https://seu-tunnel.ngrok.io">
                        <label for="tunnel_url">URL do Túnel</label>
                    </div>
                    <button type="submit" class="btn waves-effect waves-light">Usar esta URL</button>
                </form>
                <p>Alternativamente, você pode usar a sincronização periódica em vez de webhooks.</p>
            </div>
        </div>';

        // Se o formulário foi enviado, usar a URL do túnel fornecida
        if (isset($_POST['tunnel_url']) && !empty($_POST['tunnel_url'])) {
            $tunnel_url = trim($_POST['tunnel_url']);
            // Verificar se a URL começa com https://
            if (strpos($tunnel_url, 'https://') !== 0) {
                echo '<div class="card-panel red lighten-4">A URL deve começar com https://</div>';
                exit;
            }
            return rtrim($tunnel_url, '/');
        } else {
            // Se não foi fornecida uma URL de túnel, não prosseguir
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo '<div class="card-panel red lighten-4">Por favor, forneça uma URL de túnel válida.</div>';
            }
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>';
            exit;
        }
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);

    // Remover "nuvemshop" do final do path, se existir
    $path = preg_replace('/\/nuvemshop$/', '', $path);

    return $protocol . $host . $path;
}

// Função para registrar um webhook na Nuvemshop
function registrarWebhook($api_url, $headers, $event, $url) {
    $data = json_encode([
        'event' => $event,
        'url' => $url
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => $error, 'http_code' => $http_code];
    }

    curl_close($ch);

    if ($http_code == 201) {
        return ['success' => true, 'response' => json_decode($response, true)];
    } else {
        return ['success' => false, 'error' => 'Erro ao registrar webhook', 'http_code' => $http_code, 'response' => $response];
    }
}

// Função para listar webhooks existentes
function listarWebhooks($api_url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => $error, 'http_code' => $http_code];
    }

    curl_close($ch);

    if ($http_code == 200) {
        return ['success' => true, 'webhooks' => json_decode($response, true)];
    } else {
        return ['success' => false, 'error' => 'Erro ao listar webhooks', 'http_code' => $http_code, 'response' => $response];
    }
}

// Função para excluir um webhook
function excluirWebhook($api_url, $headers, $webhook_id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/webhooks/' . $webhook_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => $error, 'http_code' => $http_code];
    }

    curl_close($ch);

    if ($http_code == 200) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Erro ao excluir webhook', 'http_code' => $http_code, 'response' => $response];
    }
}

// Adicionar HTML básico para a página
echo '<!DOCTYPE html>
<html>
<head>
    <title>Registrar Webhooks Nuvemshop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <div class="container">
        <h3>Registrar Webhooks Nuvemshop</h3>';

// Obter configurações da Nuvemshop
$config = obterConfiguracoesNuvemshop();

if (!$config) {
    echo '<div class="card-panel red lighten-4">Nenhuma configuração ativa da Nuvemshop encontrada</div>';
    echo '</div></body></html>';
    exit;
}

// Configurações da API
$access_token = $config['access_token'];
$store_id = $config['store_id'];
$api_url = "https://api.tiendanube.com/v1/{$store_id}";

// Definir headers padrão
$headers = [
    'Authentication: bearer ' . $access_token,
    'Content-Type: application/json',
    'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
];

// URL base do sistema
$url_base = obterUrlBase();
$webhook_url = $url_base . '/nuvemshop/webhook_receiver.php';

// Eventos que queremos registrar
$eventos = [
    'order/created',
    'order/paid',
    'order/fulfilled',
    'order/cancelled'
];

// Verificar se já existem webhooks registrados
$resultado_listar = listarWebhooks($api_url, $headers);

if ($resultado_listar['success']) {
    $webhooks_existentes = $resultado_listar['webhooks'];

    // Excluir webhooks existentes para os eventos que queremos registrar
    foreach ($webhooks_existentes as $webhook) {
        if (in_array($webhook['event'], $eventos)) {
            $resultado_excluir = excluirWebhook($api_url, $headers, $webhook['id']);

            if ($resultado_excluir['success']) {
                echo "Webhook para o evento {$webhook['event']} excluído com sucesso.<br>";
            } else {
                echo "Erro ao excluir webhook para o evento {$webhook['event']}: " . json_encode($resultado_excluir) . "<br>";
            }
        }
    }
}

// Registrar novos webhooks
$resultados = [];

foreach ($eventos as $evento) {
    $resultado = registrarWebhook($api_url, $headers, $evento, $webhook_url);

    if ($resultado['success']) {
        echo "Webhook para o evento {$evento} registrado com sucesso.<br>";
    } else {
        echo "Erro ao registrar webhook para o evento {$evento}: " . json_encode($resultado) . "<br>";
    }

    $resultados[$evento] = $resultado;
}

// Exibir resumo
echo '<div class="card">
    <div class="card-content">
        <span class="card-title">Resumo do registro de webhooks</span>
        <ul class="collection">';

foreach ($resultados as $evento => $resultado) {
    $status_class = $resultado['success'] ? 'green-text' : 'red-text';
    $status_icon = $resultado['success'] ? 'check_circle' : 'error';
    $status_text = $resultado['success'] ? 'Sucesso' : 'Falha';

    echo '<li class="collection-item">
        <div>
            <i class="material-icons left ' . $status_class . '">' . $status_icon . '</i>
            <strong>' . $evento . ':</strong> <span class="' . $status_class . '">' . $status_text . '</span>
        </div>';

    if (!$resultado['success'] && isset($resultado['response'])) {
        $response = json_decode($resultado['response'], true);
        if (isset($response['url'])) {
            echo '<div class="red-text text-lighten-2 small">' . implode('<br>', $response['url']) . '</div>';
        }
    }

    echo '</li>';
}

echo '</ul>
    </div>
    <div class="card-action">
        <p>URL do webhook: <code>' . $webhook_url . '</code></p>
    </div>
</div>';

// Adicionar botão para voltar
echo '<a href="../index.php" class="btn waves-effect waves-light">Voltar</a>';

// Fechar tags HTML
echo '</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>';
?>
