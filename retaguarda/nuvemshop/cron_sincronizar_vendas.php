<?php
/**
 * Script para sincronização automática de vendas da Nuvemshop
 * Este arquivo deve ser executado periodicamente via cron
 *
 * Exemplo de configuração cron (a cada 15 minutos):
 * "*/15 * * * * php /caminho/para/cron_sincronizar_vendas.php"
 */

// Definir como script CLI
define('CLI_SCRIPT', true);

// Incluir arquivo de conexão
$dir = dirname(__FILE__);
require_once $dir . '/../conexao.php';

// Função para registrar log
function registrarLog($mensagem, $tipo = 'info') {
    global $conexao;

    // Usar o diretório de logs do PHP ou um diretório temporário
    $logDir = ini_get('error_log');
    if (!empty($logDir) && is_dir(dirname($logDir)) && is_writable(dirname($logDir))) {
        $logFile = dirname($logDir) . '/nuvemshop_cron.log';
    } else {
        // Alternativa: usar o diretório temporário do sistema
        $logFile = sys_get_temp_dir() . '/nuvemshop_cron.log';
    }

    // Registrar no log do PHP também
    error_log("NuvemShop Cron: $mensagem");

    // Tentar escrever no arquivo de log
    try {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$tipo] $mensagem\n";

        // Verificar se o arquivo é gravável ou se podemos criá-lo
        if ((file_exists($logFile) && is_writable($logFile)) ||
            (!file_exists($logFile) && is_writable(dirname($logFile)))) {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    } catch (Exception $e) {
        // Em caso de erro, registrar no log do PHP
        error_log("Erro ao escrever no log: " . $e->getMessage());
    }

    // Verificar se a tabela de log existe
    try {
        $query = "SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'log_cron'
        )";
        $result = pg_query($conexao, $query);

        if ($result) {
            $exists = pg_fetch_result($result, 0, 0);

            if ($exists == 'f') {
                $query = "CREATE TABLE log_cron (
                    id SERIAL PRIMARY KEY,
                    mensagem TEXT,
                    tipo VARCHAR(50),
                    data_hora TIMESTAMP
                )";
                pg_query($conexao, $query);
            }

            // Inserir log
            $mensagem = pg_escape_string($conexao, $mensagem);
            $tipo = pg_escape_string($conexao, $tipo);

            $query = "INSERT INTO log_cron (mensagem, tipo, data_hora)
                    VALUES ('$mensagem', '$tipo', NOW())";

            pg_query($conexao, $query);
        }
    } catch (Exception $e) {
        error_log("Erro ao registrar log no banco de dados: " . $e->getMessage());
    }

    // Exibir mensagem no console se for CLI
    if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
        echo date('Y-m-d H:i:s') . " [{$tipo}] {$mensagem}\n";
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

// Função para obter pedidos da Nuvemshop
function obterPedidosNuvemshop($api_url, $headers, $params = []) {
    $url = $api_url . '/orders';

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error, 'http_code' => $http_code];
    }

    curl_close($ch);

    if ($http_code == 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'Erro ao obter pedidos da Nuvemshop', 'http_code' => $http_code, 'response' => $response];
    }
}

// Função para processar um pedido da Nuvemshop
function processarPedido($pedido) {
    global $conexao;

    // Extrair informações do pedido
    $codigo_externo = $pedido['id'];
    $data = date('Y-m-d', strtotime($pedido['created_at']));
    $hora = date('H:i:s', strtotime($pedido['created_at']));
    $nome = pg_escape_string($conexao, $pedido['contact_name']);
    $email = pg_escape_string($conexao, $pedido['contact_email']);
    $fone = pg_escape_string($conexao, $pedido['contact_phone']);
    $cpf = pg_escape_string($conexao, $pedido['contact_identification']);

    // Endereço de entrega
    $endereco = pg_escape_string($conexao, $pedido['shipping_address']['address']);
    $numero = pg_escape_string($conexao, $pedido['shipping_address']['number']);
    $complemento = pg_escape_string($conexao, $pedido['shipping_address']['floor']);
    $bairro = pg_escape_string($conexao, $pedido['shipping_address']['locality']);
    $cep = pg_escape_string($conexao, $pedido['shipping_address']['zipcode']);
    $municipio = pg_escape_string($conexao, $pedido['shipping_address']['city']);
    $uf = pg_escape_string($conexao, $pedido['shipping_address']['province']);

    // Informações de pagamento
    $forma_pgto = pg_escape_string($conexao, $pedido['gateway_name']);
    $valor_pago = str_replace('.', '', $pedido['total']) * 100; // Converter para centavos

    // Status do pedido
    $status = $pedido['status'];
    $payment_status = $pedido['payment_status'];

    $status_desc = '';
    if ($status == 'open') {
        if ($payment_status == 'pending') {
            $status_desc = 'Aguardando pagamento';
        } elseif ($payment_status == 'paid') {
            $status_desc = 'Pago';
        } elseif ($payment_status == 'abandoned') {
            $status_desc = 'Abandonado';
        } else {
            $status_desc = 'Em processamento';
        }
    } elseif ($status == 'closed') {
        $status_desc = 'Finalizado';
    } elseif ($status == 'cancelled') {
        $status_desc = 'Cancelado';
    }

    // Verificar se o pedido já existe no banco de dados
    $query = "SELECT codigo, status, payment_status FROM ped_online_base WHERE codigo_externo = '{$codigo_externo}'";
    $result = pg_query($conexao, $query);

    if (pg_num_rows($result) > 0) {
        // Pedido já existe, verificar se precisa atualizar status
        $row = pg_fetch_assoc($result);
        $codigo_pedido = $row['codigo'];
        $status_atual = $row['status'];
        $payment_status_atual = $row['payment_status'];

        // Só atualizar se o status mudou
        if ($status != $status_atual || $payment_status != $payment_status_atual) {
            $query = "UPDATE ped_online_base SET status = '$status', payment_status = '$payment_status', status_desc = '$status_desc' WHERE codigo = $codigo_pedido";
            pg_query($conexao, $query);

            registrarLog("Pedido #{$codigo_pedido} (Nuvemshop #{$codigo_externo}) atualizado: status=$status, payment_status=$payment_status");

            // Se o pedido foi pago e antes não estava, atualizar estoque
            if ($payment_status == 'paid' && $payment_status_atual != 'paid') {
                atualizarEstoquePedido($codigo_pedido, $pedido);
            }
        }

        return ['success' => true, 'message' => 'Pedido atualizado', 'codigo' => $codigo_pedido, 'novo' => false];
    } else {
        // Novo pedido, inserir no banco de dados
        $query = "INSERT INTO ped_online_base (
                    codigo_externo, data, hora, nome, email, fone, cpf,
                    endereco, numero, complemento, bairro, cep, municipio, uf,
                    forma_pgto, valor_pago, status, payment_status, status_desc, origem
                  ) VALUES (
                    '$codigo_externo', '$data', '$hora', '$nome', '$email', '$fone', '$cpf',
                    '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$municipio', '$uf',
                    '$forma_pgto', $valor_pago, '$status', '$payment_status', '$status_desc', 'nuvemshop'
                  ) RETURNING codigo";

        $result = pg_query($conexao, $query);

        if ($result) {
            $row = pg_fetch_row($result);
            $codigo_pedido = $row[0];

            registrarLog("Novo pedido #{$codigo_pedido} (Nuvemshop #{$codigo_externo}) inserido");

            // Inserir produtos do pedido
            foreach ($pedido['products'] as $produto) {
                $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
                $descricao = pg_escape_string($conexao, $produto['name']);
                $qtde = $produto['quantity'];
                $preco_venda = str_replace('.', '', $produto['price']) * 100; // Converter para centavos

                // Inserir produto na tabela ped_online_prod
                $query_prod = "INSERT INTO ped_online_prod (
                                pedido, codigo_produto, descricao, qtde, preco_venda
                              ) VALUES (
                                $codigo_pedido, '$codigo_gtin', '$descricao', $qtde, $preco_venda
                              )";
                pg_query($conexao, $query_prod);
            }

            // Se o pedido já está pago, atualizar estoque
            if ($payment_status == 'paid') {
                atualizarEstoquePedido($codigo_pedido, $pedido);
            }

            return ['success' => true, 'message' => 'Pedido inserido', 'codigo' => $codigo_pedido, 'novo' => true];
        } else {
            registrarLog("Erro ao inserir pedido #{$codigo_externo}: " . pg_last_error($conexao), 'error');
            return ['success' => false, 'message' => 'Erro ao inserir pedido: ' . pg_last_error($conexao)];
        }
    }
}

// Função para atualizar o estoque de um pedido
function atualizarEstoquePedido($codigo_pedido, $pedido) {
    global $conexao;

    foreach ($pedido['products'] as $produto) {
        $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
        $qtde = $produto['quantity'];

        // Verificar se o produto existe no banco de dados
        $query_produto = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
        $result_produto = pg_query($conexao, $query_produto);

        if (pg_num_rows($result_produto) > 0) {
            $row_produto = pg_fetch_assoc($result_produto);
            $codigo_interno = $row_produto['codigo_interno'];

            // Atualizar estoque do produto
            $query_estoque = "UPDATE produtos_ou SET qtde = qtde - $qtde WHERE codigo_interno = $codigo_interno";
            $result_estoque = pg_query($conexao, $query_estoque);

            if ($result_estoque) {
                registrarLog("Estoque do produto {$codigo_gtin} atualizado: -$qtde unidades");
            } else {
                registrarLog("Erro ao atualizar estoque do produto {$codigo_gtin}: " . pg_last_error($conexao), 'error');
            }
        } else {
            registrarLog("Produto {$codigo_gtin} não encontrado no banco de dados", 'warning');
        }
    }
}

// Iniciar sincronização
registrarLog("Iniciando sincronização de vendas da Nuvemshop");

// Obter configurações da Nuvemshop
$config = obterConfiguracoesNuvemshop();

if (!$config) {
    registrarLog("Nenhuma configuração ativa da Nuvemshop encontrada", 'error');
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

// Obter data da última sincronização
$query = "SELECT MAX(data) as ultima_data FROM ped_online_base WHERE origem = 'nuvemshop'";
$result = pg_query($conexao, $query);
$row = pg_fetch_assoc($result);
$ultima_data = $row['ultima_data'];

// Se não houver data, usar data de 30 dias atrás
if (empty($ultima_data)) {
    $ultima_data = date('Y-m-d', strtotime('-30 days'));
}

// Parâmetros para obter pedidos
$params = [
    'created_at_min' => $ultima_data . 'T00:00:00-03:00',
    'per_page' => 50
];

registrarLog("Buscando pedidos desde {$ultima_data}");

// Obter pedidos da Nuvemshop
$pedidos = obterPedidosNuvemshop($api_url, $headers, $params);

if (isset($pedidos['error'])) {
    registrarLog("Erro ao obter pedidos: " . $pedidos['error'], 'error');
    exit;
}

// Processar pedidos
$novos_pedidos = 0;
$pedidos_atualizados = 0;
$erros = 0;

foreach ($pedidos as $pedido) {
    $resultado = processarPedido($pedido);

    if ($resultado['success']) {
        if ($resultado['novo']) {
            $novos_pedidos++;
        } else {
            $pedidos_atualizados++;
        }
    } else {
        $erros++;
    }
}

registrarLog("Sincronização concluída: {$novos_pedidos} novos pedidos, {$pedidos_atualizados} pedidos atualizados, {$erros} erros");
?>
