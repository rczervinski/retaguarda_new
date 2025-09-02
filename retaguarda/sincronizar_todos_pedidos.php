<?php
/**
 * Script para sincronizar pedidos de todos os serviços de integração ativos
 * Este script é chamado automaticamente ao acessar a página de Vendas Online
 */

// Iniciar sessão e incluir arquivo de conexão
session_start();
include "conexao.php";

// Função para registrar log
function registrarLog($mensagem, $tipo = 'info') {
    // Usar o diretório de logs do PHP ou um diretório temporário
    $logDir = ini_get('error_log');
    if (!empty($logDir) && is_dir(dirname($logDir)) && is_writable(dirname($logDir))) {
        $logFile = dirname($logDir) . '/sincronizacao_pedidos.log';
    } else {
        // Alternativa: usar o diretório temporário do sistema
        $logFile = sys_get_temp_dir() . '/sincronizacao_pedidos.log';
    }

    // Registrar no log do PHP também
    error_log("Sincronização Pedidos: $mensagem");

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
}

// Registrar início da execução
registrarLog("Iniciando sincronização de pedidos", 'info');

// Verificar se a requisição é AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
registrarLog("Requisição AJAX: " . ($is_ajax ? "Sim" : "Não"), 'info');

// Registrar parâmetros recebidos
registrarLog("Parâmetros recebidos: " . print_r($_POST, true), 'debug');

// Inicializar array de resposta
$response = array(
    'success' => true,
    'message' => 'Sincronização concluída com sucesso',
    'servicos' => array()
);

// Verificar quais serviços estão ativos
$query = "SELECT * FROM token_integracao WHERE ativo = 1";
$result = pg_query($conexao, $query);

if (!$result) {
    $error_message = "Erro ao consultar serviços ativos: " . pg_last_error($conexao);
    registrarLog($error_message, 'error');

    $response['success'] = false;
    $response['message'] = $error_message;

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    exit;
}

$servicos_ativos = array();
while ($row = pg_fetch_assoc($result)) {
    $servicos_ativos[] = $row;
}

// Se não houver serviços ativos, retornar
if (empty($servicos_ativos)) {
    $message = "Nenhum serviço de integração ativo encontrado";
    registrarLog($message, 'info');

    $response['message'] = $message;

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    exit;
}

// Sincronizar pedidos de cada serviço ativo
foreach ($servicos_ativos as $servico) {
    $descricao = $servico['descricao'];
    $servico_info = array(
        'descricao' => $descricao,
        'success' => false,
        'message' => '',
        'pedidos_novos' => 0,
        'pedidos_atualizados' => 0
    );

    registrarLog("Iniciando sincronização de pedidos do serviço: $descricao", 'info');

    // Verificar qual serviço é e chamar a função de sincronização correspondente
    switch (strtoupper($descricao)) {
        case 'NUVEMSHOP':
            // Sincronizar pedidos da Nuvemshop
            $resultado = sincronizarPedidosNuvemshop($servico);
            $servico_info['success'] = $resultado['success'];
            $servico_info['message'] = $resultado['message'];
            $servico_info['pedidos_novos'] = $resultado['pedidos_novos'];
            $servico_info['pedidos_atualizados'] = $resultado['pedidos_atualizados'];
            break;


        default:
            $servico_info['message'] = "Serviço não suportado para sincronização automática";
            registrarLog("Serviço não suportado para sincronização automática: $descricao", 'warning');
            break;
    }

    $response['servicos'][] = $servico_info;
}

// Função para sincronizar pedidos da Nuvemshop
function sincronizarPedidosNuvemshop($config) {
    global $conexao;

    $resultado = array(
        'success' => false,
        'message' => '',
        'pedidos_novos' => 0,
        'pedidos_atualizados' => 0
    );

    try {
        // Configurações da API
        $access_token = $config['access_token'];
        $store_id = $config['code']; // O ID da loja está armazenado no campo 'code'
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        // Definir headers padrão
        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // Verificar se a coluna 'origem' existe na tabela ped_online_base
        $query = "SELECT EXISTS (
            SELECT FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'ped_online_base'
            AND column_name = 'origem'
        )";
        $result = pg_query($conexao, $query);
        $coluna_origem_existe = pg_fetch_result($result, 0, 0) === 't';

        // Obter data da última sincronização
        if ($coluna_origem_existe) {
            $query = "SELECT MAX(data) as ultima_data FROM ped_online_base WHERE origem = 'nuvemshop'";
        } else {
            // Se a coluna 'origem' não existir, buscar a data mais recente sem filtro
            $query = "SELECT MAX(data) as ultima_data FROM ped_online_base";

            // Tentar adicionar a coluna 'origem' para uso futuro
            try {
                $alter_query = "ALTER TABLE ped_online_base ADD COLUMN origem VARCHAR(50)";
                pg_query($conexao, $alter_query);
                registrarLog("Coluna 'origem' adicionada à tabela ped_online_base", 'info');
            } catch (Exception $e) {
                registrarLog("Erro ao adicionar coluna 'origem': " . $e->getMessage(), 'error');
            }
        }

        $result = pg_query($conexao, $query);
        if ($result) {
            $row = pg_fetch_assoc($result);
            $ultima_data = $row['ultima_data'];
        } else {
            registrarLog("Erro na consulta: " . pg_last_error($conexao), 'error');
            $ultima_data = null;
        }

        // Se não houver data, usar data de 30 dias atrás
        if (empty($ultima_data)) {
            $ultima_data = date('Y-m-d', strtotime('-30 days'));
        }

        // Parâmetros para obter pedidos
        $params = [
            'created_at_min' => $ultima_data . 'T00:00:00-03:00',
            'per_page' => 50
        ];

        registrarLog("Buscando pedidos da Nuvemshop desde {$ultima_data}", 'info');

        // Obter pedidos da Nuvemshop
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
            $resultado['message'] = "Erro ao obter pedidos da Nuvemshop: $error";
            registrarLog($resultado['message'], 'error');
            return $resultado;
        }

        curl_close($ch);

        if ($http_code != 200) {
            $resultado['message'] = "Erro ao obter pedidos da Nuvemshop. Código HTTP: $http_code, Resposta: $response";
            registrarLog($resultado['message'], 'error');
            return $resultado;
        }

        $pedidos = json_decode($response, true);

        if (!is_array($pedidos)) {
            $resultado['message'] = "Resposta inválida da API da Nuvemshop";
            registrarLog($resultado['message'], 'error');
            return $resultado;
        }

        // Processar pedidos
        $pedidos_novos = 0;
        $pedidos_atualizados = 0;

        foreach ($pedidos as $pedido) {
            $resultado_pedido = processarPedidoNuvemshop($pedido);

            if ($resultado_pedido['success']) {
                if ($resultado_pedido['novo']) {
                    $pedidos_novos++;
                } else {
                    $pedidos_atualizados++;
                }
            }
        }

        $resultado['success'] = true;
        $resultado['message'] = "Sincronização concluída: $pedidos_novos novos pedidos, $pedidos_atualizados pedidos atualizados";
        $resultado['pedidos_novos'] = $pedidos_novos;
        $resultado['pedidos_atualizados'] = $pedidos_atualizados;

        registrarLog($resultado['message'], 'info');

    } catch (Exception $e) {
        $resultado['message'] = "Erro ao sincronizar pedidos da Nuvemshop: " . $e->getMessage();
        registrarLog($resultado['message'], 'error');
    }

    return $resultado;
}

// Função para processar um pedido da Nuvemshop
function processarPedidoNuvemshop($pedido) {
    global $conexao;

    try {
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
        $query = "SELECT codigo FROM ped_online_base WHERE codigo_externo = '$codigo_externo'";
        $result = pg_query($conexao, $query);

        if (pg_num_rows($result) > 0) {
            // Pedido já existe, atualizar status
            $row = pg_fetch_assoc($result);
            $codigo_pedido = $row['codigo'];

            $query = "UPDATE ped_online_base SET status = '$status', payment_status = '$payment_status', status_desc = '$status_desc' WHERE codigo = $codigo_pedido";
            pg_query($conexao, $query);

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

                return ['success' => true, 'message' => 'Pedido inserido', 'codigo' => $codigo_pedido, 'novo' => true];
            } else {
                registrarLog("Erro ao inserir pedido #$codigo_externo: " . pg_last_error($conexao), 'error');
                return ['success' => false, 'message' => 'Erro ao inserir pedido: ' . pg_last_error($conexao)];
            }
        }
    } catch (Exception $e) {
        registrarLog("Erro ao processar pedido: " . $e->getMessage(), 'error');
        return ['success' => false, 'message' => 'Erro ao processar pedido: ' . $e->getMessage()];
    }
}

// Retornar resposta em formato JSON se for uma requisição AJAX
if ($is_ajax) {
    // Registrar a resposta para depuração
    registrarLog("Resposta antes da serialização: " . print_r($response, true), 'debug');

    // Simplificar a resposta para evitar problemas de serialização
    $simplified_response = array(
        'success' => $response['success'],
        'message' => $response['message'],
        'servicos' => array()
    );

    // Simplificar os serviços
    foreach ($response['servicos'] as $servico) {
        $simplified_response['servicos'][] = array(
            'descricao' => $servico['descricao'],
            'success' => $servico['success'],
            'message' => $servico['message'],
            'pedidos_novos' => $servico['pedidos_novos'],
            'pedidos_atualizados' => $servico['pedidos_atualizados']
        );
    }

    // Registrar a resposta simplificada para depuração
    registrarLog("Resposta simplificada: " . print_r($simplified_response, true), 'debug');

    // Definir cabeçalhos e enviar resposta
    header('Content-Type: application/json');

    // Tentar serializar a resposta
    $json_response = json_encode($simplified_response);

    // Verificar se a serialização foi bem-sucedida
    if ($json_response === false) {
        // Se falhar, enviar uma resposta de erro
        registrarLog("Erro ao serializar resposta: " . json_last_error_msg(), 'error');

        $error_response = array(
            'success' => false,
            'message' => 'Erro ao serializar resposta: ' . json_last_error_msg()
        );

        echo json_encode($error_response);
    } else {
        // Se for bem-sucedida, enviar a resposta
        echo $json_response;
    }
}
?>
