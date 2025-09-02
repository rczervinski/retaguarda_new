<?php
session_start();
include "conexao.php";

// Função para registrar logs
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, "logs/vendasonline.log");
}

// Verificar se a pasta de logs existe, se não, criar
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 0;

    // Log para depuração
    logError("Requisição: $request, Página: $pagina");
}

// Fetch all records
if ($request == 'fetchall') {
    // Consulta para obter os pedidos com o total dos produtos (sem frete)
    $query = "SELECT
                p.codigo,
                p.data,
                p.hora,
                p.nome,
                p.valor_pago,
                p.status_desc,
                p.codigo_externo,
                COALESCE(p.origem, 'Desconhecida') as origem,
                COALESCE((
                    SELECT SUM(CAST(pp.qtde * pp.preco_venda AS numeric))
                    FROM ped_online_prod pp
                    WHERE pp.pedido = p.codigo
                ), 0) as total_produtos,
                (SELECT COUNT(*) FROM ped_online_prod pp WHERE pp.pedido = p.codigo) as qtd_produtos
              FROM ped_online_base p
              ORDER BY data DESC, hora DESC
              LIMIT 50 OFFSET " . intval($pagina);

    $query_quantos = "SELECT count(codigo) FROM ped_online_base";

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);

    if (!$result || !$result_quantos) {
        logError("Erro na consulta SQL: " . pg_last_error($conexao));
        echo json_encode(['error' => 'Erro na consulta SQL']);
        exit;
    }

    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $data = $row['data'];
        $hora = $row['hora'];
        $nome = $row['nome'];
        $valor_pago = $row['valor_pago'];
        $total_produtos = $row['total_produtos'];
        $qtd_produtos = $row['qtd_produtos'];
        $status = $row['status_desc'];
        $codigo_externo = $row['codigo_externo'];
        $origem = $row['origem'];
        $quantos = $row_quantos[0];

        // Log para depuração
        logError("PEDIDO #" . $codigo . " - Valor pago: " . $valor_pago . " - Total produtos: " . $total_produtos . " - Qtd produtos: " . $qtd_produtos);

        // Verificar se o pedido tem produtos e se é da Nuvemshop
        if ($qtd_produtos == 0 && $origem == 'nuvemshop' && !empty($codigo_externo)) {
            logError("PEDIDO #" . $codigo . " - Pedido da Nuvemshop sem produtos. Tentando buscar produtos...");

            // Obter configurações da Nuvemshop
            $query_config = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
            $result_config = pg_query($conexao, $query_config);

            if ($result_config && pg_num_rows($result_config) > 0) {
                $config = pg_fetch_assoc($result_config);
                $access_token = $config['access_token'];
                $store_id = $config['code'];

                // Configurações da API
                $api_url = "https://api.tiendanube.com/v1/{$store_id}";

                // Definir headers padrão
                $headers = [
                    'Authentication: bearer ' . $access_token,
                    'Content-Type: application/json',
                    'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
                ];

                // Buscar pedido na Nuvemshop
                $url = "{$api_url}/orders/{$codigo_externo}";

                logError("Buscando pedido na Nuvemshop: $url");

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response_api = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($http_code == 200) {
                    $pedido = json_decode($response_api, true);

                    if (isset($pedido['products']) && is_array($pedido['products'])) {
                        logError("Produtos encontrados no pedido: " . count($pedido['products']));

                        foreach ($pedido['products'] as $produto) {
                            $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
                            $descricao = pg_escape_string($conexao, $produto['name']);
                            $qtde = $produto['quantity'];
                            $preco_venda = $produto['price'];

                            // Inserir produto na tabela ped_online_prod
                            $query_insert = "INSERT INTO ped_online_prod (pedido, codigo_produto, qtde, preco_venda, observacao)
                                            VALUES ($codigo, '$codigo_gtin', $qtde, $preco_venda, '')";

                            logError("Inserindo produto: $query_insert");

                            $result_insert = pg_query($conexao, $query_insert);

                            if (!$result_insert) {
                                logError("Erro ao inserir produto: " . pg_last_error($conexao));
                            } else {
                                logError("Produto inserido com sucesso: $codigo_gtin");
                                $qtd_produtos++;

                                // Verificar se o produto existe na tabela produtos
                                $query_produto = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
                                $result_produto = pg_query($conexao, $query_produto);

                                if ($result_produto && pg_num_rows($result_produto) > 0) {
                                    $row_produto = pg_fetch_assoc($result_produto);
                                    $codigo_interno = $row_produto['codigo_interno'];

                                    // Atualizar estoque
                                    $query_estoque = "UPDATE produtos_ou SET qtde = qtde - $qtde WHERE codigo_interno = $codigo_interno";
                                    $result_estoque = pg_query($conexao, $query_estoque);

                                    logError("Estoque do produto $codigo_gtin (código interno: $codigo_interno) atualizado: -$qtde");
                                }
                            }
                        }

                        // Recalcular o total dos produtos
                        $query_total = "SELECT SUM(CAST(qtde * preco_venda AS numeric)) as total_produtos
                                       FROM ped_online_prod
                                       WHERE pedido = $codigo";
                        $result_total = pg_query($conexao, $query_total);

                        if ($result_total && pg_num_rows($result_total) > 0) {
                            $row_total = pg_fetch_assoc($result_total);
                            $total_produtos = $row_total['total_produtos'];
                            logError("Total dos produtos recalculado: $total_produtos");
                        }
                    }
                } else {
                    logError("Erro ao buscar pedido na Nuvemshop: HTTP $http_code");
                }
            }
        }

        $response[] = array(
            "codigo" => $codigo,
            "data" => $data,
            "hora" => $hora,
            "nome" => $nome,
            "total" => $total_produtos, // Usar o total dos produtos em vez do valor pago
            "valor_pago" => $valor_pago, // Manter o valor pago para referência
            "qtd_produtos" => $qtd_produtos, // Adicionar quantidade de produtos
            "status" => $status,
            "codigo_externo" => $codigo_externo,
            "origem" => $origem,
            "quantos" => $quantos,
            "pagina" => intval($pagina) // Adicionar o valor de pagina para a paginação
        );
    }

    echo json_encode($response);
    exit;
}

if ($request == 'mostrarDetalhesVenda') {
    $codigo = isset($_POST['codigo']) ? intval($_POST['codigo']) : 0;

    if ($codigo <= 0) {
        echo json_encode(['error' => 'Código de pedido inválido']);
        exit;
    }

    // Primeiro, obter as informações básicas do pedido
    $query_base = "SELECT
        codigo,
        COALESCE(nome, '') as nome,
        COALESCE(cpf, '') as cpf,
        trim(COALESCE(endereco, '') || ' ' || COALESCE(numero, '') || ' ' || COALESCE(complemento, '')) as endereco,
        COALESCE(cep, '') as cep,
        COALESCE(bairro, '') as bairro,
        COALESCE(uf, '') as uf,
        COALESCE(municipio, '') as municipio,
        COALESCE(forma_pgto, '') as forma_pgto,
        COALESCE(valor_pago, '0') as valor_pago,
        COALESCE(fone, '') as fone,
        COALESCE(email, '') as email,
        COALESCE(status, '') as status,
        COALESCE(payment_status, '') as payment_status,
        COALESCE(status_desc, '') as status_desc,
        COALESCE(codigo_externo, '') as codigo_externo,
        COALESCE(data, CAST(CURRENT_DATE AS varchar)) as data,
        COALESCE(hora, CAST(CURRENT_TIME AS varchar)) as hora,
        CASE WHEN origem IS NULL THEN 'Desconhecida' ELSE origem END as origem
    FROM ped_online_base
    WHERE codigo = $codigo";

    $result_base = pg_query($conexao, $query_base);

    if (!$result_base || pg_num_rows($result_base) == 0) {
        echo json_encode(['error' => 'Pedido não encontrado']);
        exit;
    }

    $row_base = pg_fetch_assoc($result_base);
    $codigo_externo = $row_base['codigo_externo'];
    $origem = $row_base['origem'];

    // Verificar se há produtos
    $query_produtos = "SELECT COUNT(*) as total FROM ped_online_prod WHERE pedido = $codigo";
    $result_produtos = pg_query($conexao, $query_produtos);
    $row_produtos = pg_fetch_assoc($result_produtos);
    $tem_produtos = ($row_produtos['total'] > 0);

    // Se não tiver produtos e for da Nuvemshop, buscar produtos
    if (!$tem_produtos && $origem == 'nuvemshop' && !empty($codigo_externo)) {
        logError("Pedido #$codigo sem produtos. Tentando buscar da Nuvemshop...");

        // Obter configurações da Nuvemshop
        $query_config = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
        $result_config = pg_query($conexao, $query_config);

        if ($result_config && pg_num_rows($result_config) > 0) {
            $config = pg_fetch_assoc($result_config);
            $access_token = $config['access_token'];
            $store_id = $config['code'];

            // Configurações da API
            $api_url = "https://api.tiendanube.com/v1/{$store_id}";

            // Definir headers padrão
            $headers = [
                'Authentication: bearer ' . $access_token,
                'Content-Type: application/json',
                'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
            ];

            // Buscar pedido na Nuvemshop
            $url = "{$api_url}/orders/{$codigo_externo}";

            logError("Buscando pedido na Nuvemshop: $url");

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response_api = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($http_code == 200) {
                $pedido = json_decode($response_api, true);

                if (isset($pedido['products']) && is_array($pedido['products'])) {
                    logError("Produtos encontrados no pedido: " . count($pedido['products']));

                    foreach ($pedido['products'] as $produto) {
                        $codigo_gtin = pg_escape_string($conexao, $produto['sku']);
                        $descricao = pg_escape_string($conexao, $produto['name']);
                        $qtde = $produto['quantity'];
                        $preco_venda = $produto['price'];

                        // Inserir produto na tabela ped_online_prod
                        $query_insert = "INSERT INTO ped_online_prod (pedido, codigo_produto, qtde, preco_venda, observacao)
                                        VALUES ($codigo, '$codigo_gtin', $qtde, $preco_venda, '')";

                        logError("Inserindo produto: $query_insert");

                        $result_insert = pg_query($conexao, $query_insert);

                        if (!$result_insert) {
                            logError("Erro ao inserir produto: " . pg_last_error($conexao));
                        } else {
                            logError("Produto inserido com sucesso: $codigo_gtin");

                            // Verificar se o produto existe na tabela produtos
                            $query_produto = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
                            $result_produto = pg_query($conexao, $query_produto);

                            if ($result_produto && pg_num_rows($result_produto) > 0) {
                                $row_produto = pg_fetch_assoc($result_produto);
                                $codigo_interno = $row_produto['codigo_interno'];

                                // Atualizar estoque
                                $query_estoque = "UPDATE produtos_ou SET qtde = qtde - $qtde WHERE codigo_interno = $codigo_interno";
                                $result_estoque = pg_query($conexao, $query_estoque);

                                logError("Estoque do produto $codigo_gtin (código interno: $codigo_interno) atualizado: -$qtde");
                            }
                        }
                    }

                    $tem_produtos = true;
                }
            } else {
                logError("Erro ao buscar pedido na Nuvemshop: HTTP $http_code");
            }
        }
    }

    // Agora, buscar os produtos do pedido
    $query = "SELECT
        pp.codigo_produto as codigo_gtin,
        COALESCE(p.descricao, 'Sem descrição') as descricao,
        pp.qtde,
        pp.preco_venda,
        CAST(pp.qtde * pp.preco_venda AS numeric) as total,
        COALESCE(pp.observacao, '') as observacao
    FROM ped_online_prod pp
    LEFT JOIN produtos p ON pp.codigo_produto = p.codigo_gtin
    WHERE pp.pedido = $codigo";

    $result = pg_query($conexao, $query);
    $response = array();

    // Extrair informações do pedido base
    $nome = $row_base['nome'];
    $cpf = $row_base['cpf'];
    $endereco = $row_base['endereco'];
    $cep = $row_base['cep'];
    $bairro = $row_base['bairro'];
    $uf = $row_base['uf'];
    $municipio = $row_base['municipio'];
    $forma_pgto = $row_base['forma_pgto'];
    $valor_pago = $row_base['valor_pago'];
    $fone = $row_base['fone'];
    $email = $row_base['email'];
    $status = $row_base['status'];
    $payment_status = $row_base['payment_status'];
    $status_desc = $row_base['status_desc'];
    $data = $row_base['data'];
    $hora = $row_base['hora'];

    if ($result && pg_num_rows($result) > 0) {
        // Há produtos na tabela ped_online_prod
        while ($row = pg_fetch_assoc($result)) {
            $codigo_gtin = $row['codigo_gtin'];
            $descricao = $row['descricao'];
            $qtde = $row['qtde'];
            $preco_venda = $row['preco_venda'];
            $total = $row['total'];
            $observacao = $row['observacao'];

            $response[] = array(
                "codigo_gtin" => $codigo_gtin,
                "descricao" => $descricao,
                "qtde" => $qtde,
                "preco_venda" => $preco_venda,
                "total" => $total,
                "observacao" => $observacao,
                "nome" => $nome,
                "cpf" => $cpf,
                "endereco" => $endereco,
                "cep" => $cep,
                "bairro" => $bairro,
                "municipio" => $municipio,
                "uf" => $uf,
                "forma_pgto" => $forma_pgto,
                "valor_pago" => $valor_pago,
                "fone" => $fone,
                "email" => $email,
                "status" => $status,
                "payment_status" => $payment_status,
                "status_desc" => $status_desc,
                "codigo_externo" => $codigo_externo,
                "data" => $data,
                "hora" => $hora,
                "origem" => $origem
            );
        }
    } else {
        // Não há produtos, adicionar um item vazio
        $response[] = array(
            "codigo_gtin" => "",
            "descricao" => "Nenhum item encontrado",
            "qtde" => 0,
            "preco_venda" => 0,
            "total" => 0,
            "observacao" => "",
            "nome" => $nome,
            "cpf" => $cpf,
            "endereco" => $endereco,
            "cep" => $cep,
            "bairro" => $bairro,
            "municipio" => $municipio,
            "uf" => $uf,
            "forma_pgto" => $forma_pgto,
            "valor_pago" => $valor_pago,
            "fone" => $fone,
            "email" => $email,
            "status" => $status,
            "payment_status" => $payment_status,
            "status_desc" => $status_desc,
            "codigo_externo" => $codigo_externo,
            "data" => $data,
            "hora" => $hora,
            "origem" => $origem
        );
    }

    echo json_encode($response);
    exit;
}

?>