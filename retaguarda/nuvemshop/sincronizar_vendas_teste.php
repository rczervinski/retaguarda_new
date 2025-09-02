<?php
/**
 * Versão simplificada para teste da sincronização de vendas
 */

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para log de erros
function logError($message) {
    $logFile = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    require_once '../conexao.php';
} catch (Exception $e) {
    logError("Erro ao incluir arquivo de conexão: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

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

// Função para testar a conexão com a API da Nuvemshop
function testarConexaoNuvemshop($api_url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/store');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_errno($ch) ? curl_error($ch) : null;
    
    curl_close($ch);

    return [
        'success' => ($http_code == 200),
        'http_code' => $http_code,
        'response' => $response,
        'curl_error' => $curl_error
    ];
}

// Obter configurações da Nuvemshop
$config = obterConfiguracoesNuvemshop();

// Iniciar HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Teste de Sincronização Nuvemshop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <div class="container">
        <h3>Teste de Sincronização Nuvemshop</h3>';

if (!$config) {
    echo '<div class="card red lighten-4">
            <div class="card-content">
                <span class="card-title">Erro de Configuração</span>
                <p>Nenhuma configuração ativa da Nuvemshop encontrada.</p>
                <p>Por favor, configure a integração com a Nuvemshop primeiro.</p>
            </div>
        </div>';
} else {
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

    // Testar conexão
    $teste = testarConexaoNuvemshop($api_url, $headers);

    echo '<div class="card ' . ($teste['success'] ? 'green lighten-4' : 'red lighten-4') . '">
            <div class="card-content">
                <span class="card-title">Teste de Conexão</span>
                <p><strong>Status:</strong> ' . ($teste['success'] ? 'Sucesso' : 'Falha') . '</p>
                <p><strong>Código HTTP:</strong> ' . $teste['http_code'] . '</p>';
    
    if ($teste['curl_error']) {
        echo '<p><strong>Erro cURL:</strong> ' . $teste['curl_error'] . '</p>';
    }
    
    if ($teste['success']) {
        $store_data = json_decode($teste['response'], true);
        echo '<p><strong>Nome da Loja:</strong> ' . htmlspecialchars($store_data['name']) . '</p>';
        echo '<p><strong>URL da Loja:</strong> ' . htmlspecialchars($store_data['url']) . '</p>';
    } else {
        echo '<p><strong>Resposta:</strong> ' . htmlspecialchars(substr($teste['response'], 0, 500)) . '</p>';
    }
    
    echo '</div>
        </div>';

    // Exibir informações de configuração
    echo '<div class="card">
            <div class="card-content">
                <span class="card-title">Configuração Atual</span>
                <p><strong>ID da Loja:</strong> ' . $store_id . '</p>
                <p><strong>Token de Acesso:</strong> ' . substr($access_token, 0, 10) . '...' . substr($access_token, -5) . '</p>
                <p><strong>URL da API:</strong> ' . $api_url . '</p>
            </div>
        </div>';

    // Exibir informações do banco de dados
    echo '<div class="card">
            <div class="card-content">
                <span class="card-title">Informações do Banco de Dados</span>';
    
    // Verificar tabela ped_online_base
    $query = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'ped_online_base'
    )";
    $result = pg_query($conexao, $query);
    $exists = pg_fetch_result($result, 0, 0);
    
    echo '<p><strong>Tabela ped_online_base:</strong> ' . ($exists == 't' ? 'Existe' : 'Não existe') . '</p>';
    
    if ($exists == 't') {
        $query = "SELECT COUNT(*) FROM ped_online_base";
        $result = pg_query($conexao, $query);
        $count = pg_fetch_result($result, 0, 0);
        
        echo '<p><strong>Número de pedidos:</strong> ' . $count . '</p>';
        
        $query = "SELECT MAX(data) as ultima_data FROM ped_online_base WHERE origem = 'nuvemshop'";
        $result = pg_query($conexao, $query);
        $row = pg_fetch_assoc($result);
        $ultima_data = $row['ultima_data'];
        
        echo '<p><strong>Data do último pedido:</strong> ' . ($ultima_data ? $ultima_data : 'Nenhum pedido encontrado') . '</p>';
    }
    
    echo '</div>
        </div>';
}

echo '<a href="../index.php" class="btn waves-effect waves-light">Voltar</a>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>';
?>
