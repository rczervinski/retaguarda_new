<?php
/**
 * Arquivo para sincronizar vendas da Nuvemshop
 * Este arquivo pode ser executado manualmente ou via cron para sincronizar vendas
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
        $logFile = dirname($logDir) . '/nuvemshop_error.log';
    } else {
        // Alternativa: usar o diretório temporário do sistema
        $logFile = sys_get_temp_dir() . '/nuvemshop_error.log';
    }

    // Registrar no log do PHP também
    error_log("NuvemShop: $message");

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
        echo "NuvemShop: $message\n";
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

    // Log para depuração - pedido completo da Nuvemshop
    logError("PEDIDO COMPLETO DA NUVEMSHOP (JSON): " . json_encode($pedido));

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

    // Usar o valor original sem modificação
    $valor_pago = $pedido['total'];

    // Log para depuração
    logError("VALOR ORIGINAL DO PEDIDO (JSON): " . json_encode($pedido['total']));
    logError("VALOR ORIGINAL DO PEDIDO (TIPO): " . gettype($pedido['total']));

    // Verificar o tipo da coluna valor_pago no banco de dados
    $query_column_type = "SELECT data_type FROM information_schema.columns
                         WHERE table_schema = 'public'
                         AND table_name = 'ped_online_base'
                         AND column_name = 'valor_pago'";
    $result_column_type = pg_query($conexao, $query_column_type);
    if ($result_column_type && pg_num_rows($result_column_type) > 0) {
        $row_column_type = pg_fetch_assoc($result_column_type);
        $column_type = $row_column_type['data_type'];
        logError("TIPO DA COLUNA valor_pago NO BANCO: " . $column_type);
    }

    // Status do pedido
    $status = $pedido['status'];
    $payment_status = $pedido['payment_status'];

    // Verificar o tipo de dados da coluna status
    $query_check = "SELECT data_type FROM information_schema.columns
                    WHERE table_schema = 'public'
                    AND table_name = 'ped_online_base'
                    AND column_name = 'status'";
    $result_check = pg_query($conexao, $query_check);

    if ($result_check && pg_num_rows($result_check) > 0) {
        $row_check = pg_fetch_assoc($result_check);
        $status_data_type = $row_check['data_type'];

        // Se o tipo for integer, converter o status para um código numérico
        if ($status_data_type == 'integer') {
            logError("Coluna 'status' é do tipo integer. Convertendo valor de string para número.");

            // Converter status de string para número
            if ($status == 'open') {
                $status = 1; // Aberto
            } elseif ($status == 'closed') {
                $status = 2; // Fechado
            } elseif ($status == 'cancelled') {
                $status = 3; // Cancelado
            } else {
                $status = 0; // Desconhecido
            }
        }
    }

    $status_desc = '';
    if ($status == 'open' || $status === 1) {
        if ($payment_status == 'pending') {
            $status_desc = 'Aguardando pagamento';
        } elseif ($payment_status == 'paid') {
            $status_desc = 'Pago';
        } elseif ($payment_status == 'abandoned') {
            $status_desc = 'Abandonado';
        } else {
            $status_desc = 'Em processamento';
        }
    } elseif ($status == 'closed' || $status === 2) {
        $status_desc = 'Finalizado';
    } elseif ($status == 'cancelled' || $status === 3) {
        $status_desc = 'Cancelado';
    }

    // Verificar se a coluna 'codigo_externo' existe na tabela ped_online_base
    $query_check = "SELECT EXISTS (
        SELECT FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'ped_online_base'
        AND column_name = 'codigo_externo'
    )";
    $result_check = pg_query($conexao, $query_check);
    $coluna_codigo_externo_existe = pg_fetch_result($result_check, 0, 0) === 't';

    if (!$coluna_codigo_externo_existe) {
        // Adicionar a coluna 'codigo_externo'
        try {
            $alter_query = "ALTER TABLE ped_online_base ADD COLUMN codigo_externo VARCHAR(50)";
            pg_query($conexao, $alter_query);
            logError("Coluna 'codigo_externo' adicionada à tabela ped_online_base");
        } catch (Exception $e) {
            logError("Erro ao adicionar coluna 'codigo_externo': " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao adicionar coluna necessária: ' . $e->getMessage()];
        }
    }

    // Verificar se as outras colunas necessárias existem
    $colunas_necessarias = ['payment_status', 'status_desc'];
    foreach ($colunas_necessarias as $coluna) {
        $query_check = "SELECT EXISTS (
            SELECT FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'ped_online_base'
            AND column_name = '$coluna'
        )";
        $result_check = pg_query($conexao, $query_check);
        $coluna_existe = pg_fetch_result($result_check, 0, 0) === 't';

        if (!$coluna_existe) {
            // Adicionar a coluna
            try {
                $alter_query = "ALTER TABLE ped_online_base ADD COLUMN $coluna VARCHAR(50)";
                pg_query($conexao, $alter_query);
                logError("Coluna '$coluna' adicionada à tabela ped_online_base");
            } catch (Exception $e) {
                logError("Erro ao adicionar coluna '$coluna': " . $e->getMessage());
                // Continuar mesmo com erro
            }
        }
    }

    // Verificar se o pedido já existe no banco de dados
    $query = "SELECT codigo FROM ped_online_base WHERE codigo_externo = '$codigo_externo'";
    $result = pg_query($conexao, $query);

    if ($result && pg_num_rows($result) > 0) {
        // Pedido já existe, atualizar status
        $row = pg_fetch_assoc($result);
        $codigo_pedido = $row['codigo'];

        // Construir a consulta de atualização com base nas colunas existentes
        // Tratar o status de acordo com seu tipo
        $status_value = is_numeric($status) ? $status : "'$status'";
        $update_fields = ["status = $status_value"];

        // Verificar cada coluna antes de incluí-la na consulta
        $query_check = "SELECT column_name FROM information_schema.columns
                        WHERE table_schema = 'public'
                        AND table_name = 'ped_online_base'";
        $result_check = pg_query($conexao, $query_check);
        $colunas_existentes = [];

        while ($row_check = pg_fetch_assoc($result_check)) {
            $colunas_existentes[] = $row_check['column_name'];
        }

        if (in_array('payment_status', $colunas_existentes)) {
            $update_fields[] = "payment_status = '$payment_status'";
        }

        if (in_array('status_desc', $colunas_existentes)) {
            $update_fields[] = "status_desc = '$status_desc'";
        }

        $query = "UPDATE ped_online_base SET " . implode(", ", $update_fields) . " WHERE codigo = $codigo_pedido";
        pg_query($conexao, $query);

        return ['success' => true, 'message' => 'Pedido atualizado', 'codigo' => $codigo_pedido, 'novo' => false];
    } else {
        // Novo pedido, inserir no banco de dados
        // Construir a consulta de inserção com base nas colunas existentes
        $query_check = "SELECT column_name FROM information_schema.columns
                        WHERE table_schema = 'public'
                        AND table_name = 'ped_online_base'";
        $result_check = pg_query($conexao, $query_check);
        $colunas_existentes = [];

        while ($row_check = pg_fetch_assoc($result_check)) {
            $colunas_existentes[] = $row_check['column_name'];
        }

        // Verificar se a coluna codigo é uma sequência
        $query_seq = "SELECT pg_get_serial_sequence('ped_online_base', 'codigo') as sequence_name";
        $result_seq = pg_query($conexao, $query_seq);
        $row_seq = pg_fetch_assoc($result_seq);
        $is_sequence = !empty($row_seq['sequence_name']);

        logError("Verificando se a coluna 'codigo' é uma sequência: " . ($is_sequence ? "Sim" : "Não"));

        // Iniciar com as colunas básicas
        $insert_columns = ['data', 'hora', 'nome', 'email', 'fone', 'cpf',
                          'endereco', 'numero', 'complemento', 'bairro', 'cep', 'municipio', 'uf',
                          'forma_pgto', 'valor_pago', 'status'];

        // Preparar os valores para inserção, tratando o status de acordo com seu tipo
        $status_value = is_numeric($status) ? $status : "'$status'";

        // Log para depuração - valor_pago antes de inserir
        logError("VALOR_PAGO ANTES DE INSERIR: " . $valor_pago . " (TIPO: " . gettype($valor_pago) . ")");

        $insert_values = ["'$data'", "'$hora'", "'$nome'", "'$email'", "'$fone'", "'$cpf'",
                         "'$endereco'", "'$numero'", "'$complemento'", "'$bairro'", "'$cep'", "'$municipio'", "'$uf'",
                         "'$forma_pgto'", "$valor_pago", $status_value];

        // Se a coluna codigo não for uma sequência, precisamos incluí-la explicitamente
        if (!$is_sequence && in_array('codigo', $colunas_existentes)) {
            // Obter o próximo valor da sequência ou gerar um ID único
            $query_max = "SELECT COALESCE(MAX(codigo), 0) + 1 as next_id FROM ped_online_base";
            $result_max = pg_query($conexao, $query_max);
            $row_max = pg_fetch_assoc($result_max);
            $next_id = $row_max['next_id'];

            logError("Próximo ID para a coluna 'codigo': $next_id");

            $insert_columns[] = 'codigo';
            $insert_values[] = $next_id;
        }

        // Adicionar colunas opcionais se existirem
        if (in_array('codigo_externo', $colunas_existentes)) {
            $insert_columns[] = 'codigo_externo';
            $insert_values[] = "'$codigo_externo'";
        }

        if (in_array('payment_status', $colunas_existentes)) {
            $insert_columns[] = 'payment_status';
            $insert_values[] = "'$payment_status'";
        }

        if (in_array('status_desc', $colunas_existentes)) {
            $insert_columns[] = 'status_desc';
            $insert_values[] = "'$status_desc'";
        }

        if (in_array('origem', $colunas_existentes)) {
            $insert_columns[] = 'origem';
            $insert_values[] = "'nuvemshop'";
        }

        $query = "INSERT INTO ped_online_base (" . implode(", ", $insert_columns) . ")
                  VALUES (" . implode(", ", $insert_values) . ") RETURNING codigo";

        logError("Consulta de inserção: $query");

        $result = pg_query($conexao, $query);

        if ($result) {
            $row = pg_fetch_row($result);
            $codigo_pedido = $row[0];

            // Inserir produtos do pedido
            foreach ($pedido['products'] as $produto) {
                $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
                $descricao = pg_escape_string($conexao, $produto['name']);
                $qtde = $produto['quantity'];

                // Usar o valor original sem modificação
                $preco_venda = $produto['price'];

                // Log para depuração
                logError("PRODUTO: " . $produto['name'] . " (SKU: " . $produto['sku'] . ")");
                logError("PREÇO ORIGINAL DO PRODUTO (JSON): " . json_encode($produto['price']));
                logError("PREÇO ORIGINAL DO PRODUTO (TIPO): " . gettype($produto['price']));

                // Verificar o tipo da coluna preco_venda no banco de dados
                $query_column_type = "SELECT data_type FROM information_schema.columns
                                     WHERE table_schema = 'public'
                                     AND table_name = 'ped_online_prod'
                                     AND column_name = 'preco_venda'";
                $result_column_type = pg_query($conexao, $query_column_type);
                if ($result_column_type && pg_num_rows($result_column_type) > 0) {
                    $row_column_type = pg_fetch_assoc($result_column_type);
                    $column_type = $row_column_type['data_type'];
                    logError("TIPO DA COLUNA preco_venda NO BANCO: " . $column_type);
                }

                // Verificar as colunas existentes na tabela ped_online_prod
                $query_check_prod = "SELECT column_name FROM information_schema.columns
                                    WHERE table_schema = 'public'
                                    AND table_name = 'ped_online_prod'";
                $result_check_prod = pg_query($conexao, $query_check_prod);

                if (!$result_check_prod) {
                    logError("Erro ao verificar colunas da tabela ped_online_prod: " . pg_last_error($conexao));
                    continue; // Pular este produto e continuar com o próximo
                }

                $colunas_prod = [];
                while ($row_check_prod = pg_fetch_assoc($result_check_prod)) {
                    $colunas_prod[] = $row_check_prod['column_name'];
                }

                // Verificar se a coluna codigo é uma sequência
                $query_seq_prod = "SELECT pg_get_serial_sequence('ped_online_prod', 'codigo') as sequence_name";
                $result_seq_prod = pg_query($conexao, $query_seq_prod);

                if (!$result_seq_prod) {
                    logError("Erro ao verificar sequência da coluna 'codigo': " . pg_last_error($conexao));
                    continue; // Pular este produto e continuar com o próximo
                }

                $row_seq_prod = pg_fetch_assoc($result_seq_prod);
                $is_sequence_prod = !empty($row_seq_prod['sequence_name']);

                logError("Verificando se a coluna 'codigo' em ped_online_prod é uma sequência: " . ($is_sequence_prod ? "Sim" : "Não"));

                // Log para depuração - preco_venda antes de inserir
                logError("PRECO_VENDA ANTES DE INSERIR (PRODUTO): " . $preco_venda . " (TIPO: " . gettype($preco_venda) . ")");

                // Construir a consulta de inserção com base nas colunas existentes
                $insert_columns_prod = ['pedido', 'codigo_produto', 'qtde', 'preco_venda'];
                $insert_values_prod = [$codigo_pedido, "'$codigo_gtin'", $qtde, $preco_venda];

                // Adicionar coluna descricao se existir
                if (in_array('descricao', $colunas_prod)) {
                    $insert_columns_prod[] = 'descricao';
                    $insert_values_prod[] = "'$descricao'";
                }

                // Adicionar coluna observacao se existir
                if (in_array('observacao', $colunas_prod)) {
                    $insert_columns_prod[] = 'observacao';
                    $insert_values_prod[] = "''"; // Valor vazio para observação
                }

                // Se a coluna codigo não for uma sequência, precisamos incluí-la explicitamente
                if (!$is_sequence_prod && in_array('codigo', $colunas_prod)) {
                    // Obter o próximo valor da sequência ou gerar um ID único
                    $query_max_prod = "SELECT COALESCE(MAX(codigo), 0) + 1 as next_id FROM ped_online_prod";
                    $result_max_prod = pg_query($conexao, $query_max_prod);

                    if (!$result_max_prod) {
                        logError("Erro ao obter próximo ID para a coluna 'codigo': " . pg_last_error($conexao));
                        continue; // Pular este produto e continuar com o próximo
                    }

                    $row_max_prod = pg_fetch_assoc($result_max_prod);
                    $next_id_prod = $row_max_prod['next_id'];

                    logError("Próximo ID para a coluna 'codigo' em ped_online_prod: $next_id_prod");

                    $insert_columns_prod[] = 'codigo';
                    $insert_values_prod[] = $next_id_prod;
                }

                $query_prod = "INSERT INTO ped_online_prod (" . implode(", ", $insert_columns_prod) . ")
                              VALUES (" . implode(", ", $insert_values_prod) . ")";
                logError("Consulta de inserção de produto: $query_prod");

                $result_insert_prod = pg_query($conexao, $query_prod);

                if (!$result_insert_prod) {
                    logError("ERRO ao inserir produto na tabela ped_online_prod: " . pg_last_error($conexao));
                } else {
                    logError("Produto inserido com sucesso na tabela ped_online_prod");
                }

                // Verificar se o produto existe no banco de dados para atualizar o estoque
                $query_produto = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
                $result_produto = pg_query($conexao, $query_produto);

                if ($result_produto && pg_num_rows($result_produto) > 0) {
                    $row_produto = pg_fetch_assoc($result_produto);
                    $codigo_interno = $row_produto['codigo_interno'];

                    // Atualizar estoque do produto
                    $query_estoque = "UPDATE produtos_ou SET qtde = qtde - $qtde WHERE codigo_interno = $codigo_interno";
                    pg_query($conexao, $query_estoque);
                }
            }

            return ['success' => true, 'message' => 'Pedido inserido', 'codigo' => $codigo_pedido, 'novo' => true];
        } else {
            return ['success' => false, 'message' => 'Erro ao inserir pedido: ' . pg_last_error($conexao)];
        }
    }
}

// Verificar se é uma chamada AJAX com auto_sync
$is_auto_sync = isset($_POST['auto_sync']) && ($_POST['auto_sync'] === 'true' || $_POST['auto_sync'] === true);

// Log para depuração
logError("Verificando auto_sync: " . ($is_auto_sync ? "Sim" : "Não"));
logError("POST data: " . json_encode($_POST));

// Se este arquivo for chamado diretamente (não incluído), executar a sincronização
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Obter configurações da Nuvemshop
    $config = obterConfiguracoesNuvemshop();

    if (!$config) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma configuração ativa da Nuvemshop encontrada']);
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
            logError("Coluna 'origem' adicionada à tabela ped_online_base");
        } catch (Exception $e) {
            logError("Erro ao adicionar coluna 'origem': " . $e->getMessage());
        }
    }

    $result = pg_query($conexao, $query);
    if ($result) {
        $row = pg_fetch_assoc($result);
        $ultima_data = $row['ultima_data'];
    } else {
        logError("Erro na consulta: " . pg_last_error($conexao));
        $ultima_data = null;
    }

    // Se não houver data, usar data de 30 dias atrás
    if (empty($ultima_data)) {
        $ultima_data = date('Y-m-d', strtotime('-30 days'));
    } else {
        // Se houver data, subtrair 1 dia para garantir que não perca pedidos
        $ultima_data = date('Y-m-d', strtotime($ultima_data . ' -1 day'));
    }

    // Verificar se foi solicitada uma sincronização completa
    $forcar_sincronizacao_completa = isset($_POST['force_full_sync']) && $_POST['force_full_sync'] == 'true';

    // Se for uma sincronização completa, usar data de 30 dias atrás
    if ($forcar_sincronizacao_completa) {
        $ultima_data = date('Y-m-d', strtotime('-30 days'));
        logError("Sincronização completa solicitada. Buscando pedidos desde $ultima_data");
    }

    // Parâmetros para obter pedidos
    $params = [
        'created_at_min' => $ultima_data . 'T00:00:00-03:00',
        'per_page' => 50
    ];

    logError("Buscando pedidos da Nuvemshop desde $ultima_data");

    // Obter pedidos da Nuvemshop
    try {
        $pedidos = obterPedidosNuvemshop($api_url, $headers, $params);

        if (isset($pedidos['error'])) {
            logError("Erro ao obter pedidos da Nuvemshop: " . json_encode($pedidos));

            // Limpar qualquer saída anterior
            ob_clean();

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $pedidos['error']]);
            } else {
                header('Content-Type: text/html; charset=utf-8');
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
                        <h3>Erro na Sincronização de Vendas</h3>
                        <div class="card red lighten-4">
                            <div class="card-content">
                                <span class="card-title">Erro ao obter pedidos da Nuvemshop</span>
                                <p>' . (isset($pedidos['error']) ? htmlspecialchars($pedidos['error']) : 'Erro desconhecido') . '</p>';

                if (isset($pedidos['http_code'])) {
                    echo '<p>Código HTTP: ' . $pedidos['http_code'] . '</p>';
                }

                if (isset($pedidos['response'])) {
                    echo '<p>Resposta: ' . htmlspecialchars(substr($pedidos['response'], 0, 500)) . (strlen($pedidos['response']) > 500 ? '...' : '') . '</p>';
                }

                echo '</div>
                        </div>

                        <a href="../index.php" class="btn waves-effect waves-light">Voltar</a>
                    </div>

                    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
                </body>
                </html>';
            }
            exit;
        }
    } catch (Exception $e) {
        logError("Exceção ao obter pedidos da Nuvemshop: " . $e->getMessage());

        // Limpar qualquer saída anterior
        ob_clean();

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
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
                    <h3>Erro na Sincronização de Vendas</h3>
                    <div class="card red lighten-4">
                        <div class="card-content">
                            <span class="card-title">Exceção ao processar pedidos</span>
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

    // Processar pedidos
    $resultados = [];
    $novos_pedidos = 0;
    $pedidos_atualizados = 0;
    $erros = 0;

    try {
        // Verificar se $pedidos é um array
        if (!is_array($pedidos)) {
            logError("Erro: \$pedidos não é um array. Tipo recebido: " . gettype($pedidos));
            throw new Exception("Formato de resposta inválido da API da Nuvemshop");
        }

        foreach ($pedidos as $pedido) {
            try {
                $resultado = processarPedido($pedido);

                if ($resultado['success']) {
                    if ($resultado['novo']) {
                        $novos_pedidos++;
                    } else {
                        $pedidos_atualizados++;
                    }
                } else {
                    $erros++;
                    logError("Erro ao processar pedido: " . json_encode($resultado));
                }

                $resultados[] = $resultado;
            } catch (Exception $e) {
                $erros++;
                logError("Exceção ao processar pedido individual: " . $e->getMessage() . "\nPedido: " . json_encode($pedido));
                $resultados[] = [
                    'success' => false,
                    'message' => 'Exceção: ' . $e->getMessage(),
                    'pedido_id' => isset($pedido['id']) ? $pedido['id'] : 'desconhecido'
                ];
            }
        }
    } catch (Exception $e) {
        logError("Exceção ao processar lista de pedidos: " . $e->getMessage());

        // Limpar qualquer saída anterior
        ob_clean();

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao processar pedidos: ' . $e->getMessage()]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
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
                    <h3>Erro na Sincronização de Vendas</h3>
                    <div class="card red lighten-4">
                        <div class="card-content">
                            <span class="card-title">Erro ao processar pedidos</span>
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

    // Exibir resultados
    $resposta = [
        'success' => true,
        'message' => "Sincronização concluída: $novos_pedidos novos pedidos, $pedidos_atualizados pedidos atualizados" . ($erros > 0 ? ", $erros erros" : ""),
        'pedidos_novos' => $novos_pedidos,
        'pedidos_atualizados' => $pedidos_atualizados,
        'erros' => $erros,
        'resultados' => $resultados
    ];

    // Garantir que não haja saída antes do cabeçalho
    ob_clean();

    // Verificar se é uma chamada AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Registrar a resposta para depuração
        error_log("Resposta antes da serialização: " . print_r($resposta, true));

        // Simplificar a resposta para evitar problemas de serialização
        $simplified_response = array(
            'success' => $resposta['success'],
            'message' => $resposta['message']
        );

        // Adicionar campos para compatibilidade com o formato esperado pelo JS
        if (isset($resposta['pedidos_novos'])) {
            $simplified_response['pedidos_novos'] = $resposta['pedidos_novos'];
        }

        if (isset($resposta['pedidos_atualizados'])) {
            $simplified_response['pedidos_atualizados'] = $resposta['pedidos_atualizados'];
        }

        // Adicionar campos alternativos para compatibilidade
        if (isset($resposta['novos_pedidos'])) {
            $simplified_response['pedidos_novos'] = $resposta['novos_pedidos'];
        }

        if (isset($resposta['pedidos_atualizados'])) {
            $simplified_response['pedidos_atualizados'] = $resposta['pedidos_atualizados'];
        }

        // Registrar a resposta simplificada para depuração
        error_log("Resposta simplificada: " . print_r($simplified_response, true));

        // Definir cabeçalhos e enviar resposta
        header('Content-Type: application/json');

        // Tentar serializar a resposta
        $json_response = json_encode($simplified_response);

        // Verificar se a serialização foi bem-sucedida
        if ($json_response === false) {
            // Se falhar, enviar uma resposta de erro
            error_log("Erro ao serializar resposta: " . json_last_error_msg());

            $error_response = array(
                'success' => false,
                'message' => 'Erro ao serializar resposta: ' . json_last_error_msg()
            );

            echo json_encode($error_response);
        } else {
            // Se for bem-sucedida, enviar a resposta
            echo $json_response;
        }
    } else {
        // Exibir página HTML
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Sincronização de Vendas Nuvemshop</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        </head>
        <body>
            <div class="container">
                <h3>Sincronização de Vendas Nuvemshop</h3>
                <div class="card">
                    <div class="card-content">
                        <h5>Resumo da Sincronização</h5>
                        <p>Novos pedidos: ' . $novos_pedidos . '</p>
                        <p>Pedidos atualizados: ' . $pedidos_atualizados . '</p>
                        <p>Erros: ' . $erros . '</p>
                    </div>
                </div>

                <a href="../index.php" class="btn waves-effect waves-light">Voltar</a>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        </body>
        </html>';
    }
}
?>
