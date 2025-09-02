<?php
/**
 * Arquivo para receber webhooks da Nuvemshop
 * Este arquivo processa eventos como vendas, atualizações de pedidos, etc.
 */

require_once '../conexao.php';

// Função para verificar a autenticidade do webhook
function verificarWebhook($data, $hmac_header) {
    // Obter o secret da aplicação
    global $conexao;
    $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
    $result = pg_query($conexao, $query);
    
    if (pg_num_rows($result) > 0) {
        $config = pg_fetch_assoc($result);
        $app_secret = $config['client_secret']; // O secret da aplicação
        
        // Verificar a assinatura
        $calculated_hmac = hash_hmac('sha256', $data, $app_secret);
        return $hmac_header == $calculated_hmac;
    }
    
    return false;
}

// Função para registrar log
function registrarLog($evento, $dados, $status = 'info') {
    global $conexao;
    
    $evento = pg_escape_string($conexao, $evento);
    $dados = pg_escape_string($conexao, json_encode($dados));
    $status = pg_escape_string($conexao, $status);
    
    $query = "INSERT INTO log_webhook (evento, dados, status, data_hora) 
              VALUES ('$evento', '$dados', '$status', NOW())";
    
    pg_query($conexao, $query);
}

// Função para processar um pedido da Nuvemshop
function processarPedido($order_id) {
    global $conexao;
    
    // Obter configurações da Nuvemshop
    $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
    $result = pg_query($conexao, $query);
    
    if (pg_num_rows($result) > 0) {
        $config = pg_fetch_assoc($result);
        $access_token = $config['access_token'];
        $store_id = $config['code']; // O ID da loja está armazenado no campo 'code'
        
        // Obter detalhes do pedido da API da Nuvemshop
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.tiendanube.com/v1/{$store_id}/orders/{$order_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            registrarLog('erro_api', ['erro' => curl_error($ch)], 'error');
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($http_code == 200) {
            $pedido = json_decode($response, true);
            
            // Verificar se o pedido já existe no banco de dados
            $query = "SELECT codigo FROM ped_online_base WHERE codigo_externo = '{$order_id}'";
            $result = pg_query($conexao, $query);
            
            if (pg_num_rows($result) > 0) {
                // Pedido já existe, atualizar status
                $row = pg_fetch_assoc($result);
                $codigo_pedido = $row['codigo'];
                
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
                
                $query = "UPDATE ped_online_base SET status = '$status', payment_status = '$payment_status', status_desc = '$status_desc' WHERE codigo = $codigo_pedido";
                pg_query($conexao, $query);
                
                return $codigo_pedido;
            } else {
                // Novo pedido, inserir no banco de dados
                return inserirNovoPedido($pedido);
            }
        } else {
            registrarLog('erro_api', ['http_code' => $http_code, 'response' => $response], 'error');
            return false;
        }
    }
    
    return false;
}

// Função para inserir um novo pedido no banco de dados
function inserirNovoPedido($pedido) {
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
    
    // Inserir pedido na tabela ped_online_base
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
        
        // Inserir produtos do pedido
        foreach ($pedido['products'] as $produto) {
            $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
            $descricao = pg_escape_string($conexao, $produto['name']);
            $qtde = $produto['quantity'];
            $preco_venda = str_replace('.', '', $produto['price']) * 100; // Converter para centavos
            
            // Verificar se o produto existe no banco de dados
            $query_produto = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
            $result_produto = pg_query($conexao, $query_produto);
            
            if (pg_num_rows($result_produto) > 0) {
                $row_produto = pg_fetch_assoc($result_produto);
                $codigo_interno = $row_produto['codigo_interno'];
                
                // Inserir produto na tabela ped_online_prod
                $query_prod = "INSERT INTO ped_online_prod (
                                pedido, codigo_produto, descricao, qtde, preco_venda
                              ) VALUES (
                                $codigo_pedido, '$codigo_gtin', '$descricao', $qtde, $preco_venda
                              )";
                pg_query($conexao, $query_prod);
                
                // Atualizar estoque do produto
                $query_estoque = "UPDATE produtos_ou SET qtde = qtde - $qtde WHERE codigo_interno = $codigo_interno";
                pg_query($conexao, $query_estoque);
            } else {
                // Produto não encontrado, inserir apenas na tabela de pedidos
                $query_prod = "INSERT INTO ped_online_prod (
                                pedido, codigo_produto, descricao, qtde, preco_venda
                              ) VALUES (
                                $codigo_pedido, '$codigo_gtin', '$descricao', $qtde, $preco_venda
                              )";
                pg_query($conexao, $query_prod);
            }
        }
        
        return $codigo_pedido;
    }
    
    return false;
}

// Verificar se a tabela de log existe, se não, criar
$query = "SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_name = 'log_webhook'
)";
$result = pg_query($conexao, $query);
$exists = pg_fetch_result($result, 0, 0);

if ($exists == 'f') {
    $query = "CREATE TABLE log_webhook (
        id SERIAL PRIMARY KEY,
        evento VARCHAR(255),
        dados TEXT,
        status VARCHAR(50),
        data_hora TIMESTAMP
    )";
    pg_query($conexao, $query);
}

// Receber o webhook
$data = file_get_contents('php://input');
$hmac_header = isset($_SERVER['HTTP_X_LINKEDSTORE_HMAC_SHA256']) ? $_SERVER['HTTP_X_LINKEDSTORE_HMAC_SHA256'] : '';

// Verificar se é um webhook válido
if (!empty($data)) {
    $webhook_data = json_decode($data, true);
    
    // Registrar o webhook recebido
    registrarLog('webhook_recebido', $webhook_data);
    
    // Verificar a autenticidade do webhook (comentado para testes iniciais)
    // if (verificarWebhook($data, $hmac_header)) {
        
    // Processar o webhook de acordo com o evento
    if (isset($webhook_data['event'])) {
        $event = $webhook_data['event'];
        
        switch ($event) {
            case 'order/created':
            case 'order/paid':
            case 'order/fulfilled':
            case 'order/cancelled':
                // Processar o pedido
                $order_id = $webhook_data['id'];
                $codigo_pedido = processarPedido($order_id);
                
                if ($codigo_pedido) {
                    registrarLog('pedido_processado', ['order_id' => $order_id, 'codigo_pedido' => $codigo_pedido]);
                    echo json_encode(['success' => true, 'message' => 'Pedido processado com sucesso']);
                } else {
                    registrarLog('erro_processar_pedido', ['order_id' => $order_id], 'error');
                    echo json_encode(['success' => false, 'message' => 'Erro ao processar pedido']);
                }
                break;
                
            default:
                // Evento não tratado
                registrarLog('evento_nao_tratado', ['event' => $event]);
                echo json_encode(['success' => true, 'message' => 'Evento não tratado']);
                break;
        }
    // } else {
    //     registrarLog('webhook_invalido', ['hmac' => $hmac_header], 'error');
    //     http_response_code(401);
    //     echo json_encode(['success' => false, 'message' => 'Webhook inválido']);
    // }
    } else {
        registrarLog('webhook_sem_evento', $webhook_data, 'error');
        echo json_encode(['success' => false, 'message' => 'Webhook sem evento']);
    }
} else {
    registrarLog('webhook_vazio', [], 'error');
    echo json_encode(['success' => false, 'message' => 'Webhook vazio']);
}
?>
