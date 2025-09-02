<?php
/**
 * Arquivo para sincronizar produtos com a Nuvemshop
 * Este arquivo pode ser executado manualmente ou via AJAX
 */

// Iniciar buffer de saída para controlar o que é enviado ao navegador
ob_start();

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para log de erros
function logError($message) {
    // Verificar se estamos em modo silencioso (quando incluído por outro script)
    global $SILENT_MODE;
    $is_silent = isset($SILENT_MODE) && $SILENT_MODE === true;

    // Usar o diretório de logs do PHP ou um diretório temporário
    $logDir = ini_get('error_log');
    if (!empty($logDir) && is_dir(dirname($logDir)) && is_writable(dirname($logDir))) {
        $logFile = dirname($logDir) . '/nuvemshop_produtos.log';
    } else {
        // Alternativa: usar o diretório temporário do sistema
        $logFile = sys_get_temp_dir() . '/nuvemshop_produtos.log';
    }

    // Registrar no log do PHP também
    error_log("NuvemShop Produtos: $message");

    // Tentar escrever no arquivo de log
    try {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";

        // Verificar se o arquivo é gravável ou se podemos criá-lo
        if ((file_exists($logFile) && is_writable($logFile)) ||
            (!file_exists($logFile) && is_writable(dirname($logFile)))) {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        } else {
            // Se não conseguir escrever no arquivo, apenas registrar no log do PHP
            error_log("Não foi possível escrever no arquivo de log: $logFile");
        }
    } catch (Exception $e) {
        // Em caso de erro, registrar no log do PHP
        error_log("Erro ao escrever no log: " . $e->getMessage());
    }

    // Exibir mensagem no console apenas se não estiver em modo silencioso e for CLI
    if (!$is_silent && defined('CLI_SCRIPT') && CLI_SCRIPT) {
        echo date('Y-m-d H:i:s') . " [LOG] $message\n";
    }

    // Não exibir mensagens na saída padrão quando incluído por outro script
    if (!$is_silent && !defined('CLI_SCRIPT') && basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
        echo "NuvemShop Produtos: $message\n";
    }
}

// Conexão já deve estar disponível quando este arquivo é incluído
// Se for chamado diretamente, incluir o arquivo de conexão
if (!isset($conexao)) {
    try {
        if (file_exists('../conexao.php')) {
            require_once '../conexao.php';
        } elseif (file_exists('conexao.php')) {
            require_once 'conexao.php';
        } else {
            logError("Arquivo de conexão não encontrado");
            die("Erro ao conectar ao banco de dados: arquivo de conexão não encontrado");
        }
    } catch (Exception $e) {
        logError("Erro ao incluir arquivo de conexão: " . $e->getMessage());
        die("Erro ao conectar ao banco de dados. Verifique o arquivo de log para mais detalhes.");
    }
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

// Função para obter produtos da Nuvemshop
function obterProdutosNuvemshop($api_url, $headers, $params = []) {
    $url = $api_url . '/products';

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    // Log para depuração
    logError("Iniciando requisição para obter produtos da Nuvemshop: $url");

    // Configurar o cURL com mais opções para depuração
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desativar verificação SSL para testes
    curl_setopt($ch, CURLOPT_VERBOSE, true); // Modo verboso para depuração

    // Capturar informações de depuração
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Executar a requisição
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);

    // Log do tempo de resposta
    logError("Tempo de resposta: " . $info['total_time'] . " segundos");

    // Verificar se houve erro no cURL
    if (curl_errno($ch)) {
        $error = curl_error($ch);

        // Obter informações de depuração
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        // Log detalhado do erro
        logError("Erro cURL ao obter produtos da Nuvemshop: $error");
        logError("Detalhes da requisição: " . json_encode($info));
        logError("Log verboso: $verboseLog");

        curl_close($ch);
        fclose($verbose);

        return [
            'error' => $error,
            'http_code' => $http_code,
            'curl_info' => $info
        ];
    }

    // Fechar o cURL e o arquivo de log
    curl_close($ch);
    fclose($verbose);

    // Verificar o código de resposta HTTP
    if ($http_code == 200) {
        // Tentar decodificar a resposta JSON
        $decoded = json_decode($response, true);

        // Verificar se a decodificação foi bem-sucedida
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            logError("Erro ao decodificar resposta JSON: " . json_last_error_msg());
            logError("Resposta recebida: " . substr($response, 0, 1000) . "...");

            return [
                'error' => 'Erro ao decodificar resposta JSON: ' . json_last_error_msg(),
                'http_code' => $http_code
            ];
        }

        // Log do número de produtos recebidos
        $count = is_array($decoded) ? count($decoded) : 'N/A';
        logError("Produtos recebidos com sucesso. Total: $count");

        return $decoded;
    } else {
        // Log detalhado do erro HTTP
        logError("Erro HTTP ao obter produtos da Nuvemshop. Código: $http_code");
        logError("Resposta: " . substr($response, 0, 1000) . "...");

        // Tentar extrair mensagem de erro da resposta
        $error_message = 'Erro ao obter produtos da Nuvemshop';
        $decoded_response = json_decode($response, true);

        if ($decoded_response && isset($decoded_response['message'])) {
            $error_message .= ': ' . $decoded_response['message'];
        }

        return [
            'error' => $error_message,
            'http_code' => $http_code,
            'response' => substr($response, 0, 1000) // Limitar o tamanho da resposta
        ];
    }
}

// Função para sincronizar produtos
function sincronizarProdutos() {
    global $conexao;

    // Obter configurações da Nuvemshop
    $config = obterConfiguracoesNuvemshop();

    if (!$config) {
        logError("Nenhuma configuração ativa da Nuvemshop encontrada");
        return ['success' => false, 'message' => 'Nenhuma configuração ativa da Nuvemshop encontrada'];
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

    // Obter produtos da Nuvemshop
    $produtos = obterProdutosNuvemshop($api_url, $headers);

    if (isset($produtos['error'])) {
        logError("Erro ao obter produtos da Nuvemshop: " . json_encode($produtos));
        return ['success' => false, 'message' => 'Erro ao obter produtos da Nuvemshop: ' . $produtos['error']];
    }

    // Contador de produtos sincronizados
    $produtos_sincronizados = 0;
    $produtos_atualizados = 0;
    $erros = 0;

    // Processar cada produto
    foreach ($produtos as $produto) {
        // Implementar a lógica de sincronização de produtos aqui
        // Por enquanto, apenas registrar no log
        logError("Produto recebido da Nuvemshop: " . $produto['name']);
        $produtos_sincronizados++;
    }

    return [
        'success' => true,
        'message' => "Sincronização concluída: $produtos_sincronizados produtos sincronizados, $produtos_atualizados produtos atualizados, $erros erros",
        'produtos_sincronizados' => $produtos_sincronizados,
        'produtos_atualizados' => $produtos_atualizados,
        'erros' => $erros
    ];
}

// Se este arquivo for chamado diretamente (não incluído por outro script)
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Verificar se é uma chamada AJAX
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    try {
        $resultado = sincronizarProdutos();

        if ($is_ajax) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode($resultado);
        } else {
            // Exibir página HTML
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Sincronização de Produtos Nuvemshop</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
            </head>
            <body>
                <div class="container">
                    <h3>Sincronização de Produtos Nuvemshop</h3>
                    <div class="card">
                        <div class="card-content">
                            <h5>Resumo da Sincronização</h5>
                            <p>Produtos sincronizados: ' . $resultado['produtos_sincronizados'] . '</p>
                            <p>Produtos atualizados: ' . $resultado['produtos_atualizados'] . '</p>
                            <p>Erros: ' . $resultado['erros'] . '</p>
                        </div>
                    </div>

                    <a href="../index.php" class="btn waves-effect waves-light">Voltar</a>
                </div>

                <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
            </body>
            </html>';
        }
    } catch (Exception $e) {
        logError("Exceção ao sincronizar produtos: " . $e->getMessage());

        if ($is_ajax) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        } else {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Erro na Sincronização</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
            </head>
            <body>
                <div class="container">
                    <h3>Erro na Sincronização de Produtos</h3>
                    <div class="card red lighten-4">
                        <div class="card-content">
                            <span class="card-title">Exceção ao processar produtos</span>
                            <p>' . htmlspecialchars($e->getMessage()) . '</p>
                        </div>
                    </div>

                    <a href="../index.php" class="btn waves-effect waves-light">Voltar</a>
                </div>

                <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
            </body>
            </html>';
        }
        exit;
    }
}
