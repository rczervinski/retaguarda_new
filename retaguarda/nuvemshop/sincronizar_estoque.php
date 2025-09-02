<?php
// Arquivo para sincronizar o estoque entre a Nuvemshop e o banco de dados local
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

// Função para obter todos os produtos da Nuvemshop
function obterProdutosNuvemshop($api_url, $headers) {
    $url = $api_url . '/products';

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
        return ['error' => 'Erro ao obter produtos da Nuvemshop', 'http_code' => $http_code, 'response' => $response];
    }
}

// Função para atualizar a quantidade de um produto no banco de dados local
function atualizarQuantidadeProduto($codigo_gtin, $quantidade) {
    global $conexao;

    // Primeiro, obter o código interno do produto
    $query = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
    $result = pg_query($conexao, $query);

    if (!$result || pg_num_rows($result) == 0) {
        return ['success' => false, 'error' => 'Produto não encontrado: ' . $codigo_gtin];
    }

    $row = pg_fetch_assoc($result);
    $codigo_interno = $row['codigo_interno'];

    // Agora, atualizar a quantidade na tabela produtos_ou
    $query = "UPDATE produtos_ou SET qtde = $quantidade WHERE codigo_interno = $codigo_interno";
    $result = pg_query($conexao, $query);

    if (!$result) {
        return ['success' => false, 'error' => pg_last_error($conexao)];
    }

    return ['success' => true];
}

// Função para atualizar a quantidade de um produto na Nuvemshop
function atualizarQuantidadeNuvemshop($api_url, $headers, $product_id, $variant_id, $quantidade) {
    $url = $api_url . '/products/' . $product_id . '/variants/' . $variant_id;

    $data = json_encode([
        'stock_management' => true,
        'stock' => $quantidade
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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

    if ($http_code == 200) {
        return ['success' => true, 'response' => json_decode($response, true)];
    } else {
        return ['success' => false, 'error' => 'Erro ao atualizar quantidade na Nuvemshop', 'http_code' => $http_code, 'response' => $response];
    }
}

// Verificar o tipo de sincronização solicitada
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Obter configurações da Nuvemshop
$config = obterConfiguracoesNuvemshop();

if (!$config) {
    header('Content-Type: application/json');
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

// Processar a sincronização de acordo com o tipo
switch ($tipo) {
    case 'nuvemshop_para_local':
        // Sincronizar estoque da Nuvemshop para o banco de dados local
        $produtos = obterProdutosNuvemshop($api_url, $headers);

        if (isset($produtos['error'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $produtos['error']]);
            exit;
        }

        $atualizados = 0;
        $erros = 0;
        $log = [];

        foreach ($produtos as $produto) {
            if (isset($produto['variants']) && is_array($produto['variants'])) {
                foreach ($produto['variants'] as $variante) {
                    if (isset($variante['sku']) && !empty($variante['sku']) && isset($variante['stock'])) {
                        $codigo_gtin = $variante['sku'];
                        $quantidade = $variante['stock'];

                        $resultado = atualizarQuantidadeProduto($codigo_gtin, $quantidade);

                        if ($resultado['success']) {
                            $atualizados++;
                            $log[] = "Produto com GTIN $codigo_gtin atualizado com quantidade $quantidade";
                        } else {
                            $erros++;
                            $log[] = "Erro ao atualizar produto com GTIN $codigo_gtin: " . $resultado['error'];
                        }
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'atualizados' => $atualizados,
            'erros' => $erros,
            'log' => $log
        ]);
        break;

    case 'local_para_nuvemshop':
        // Sincronizar estoque do banco de dados local para a Nuvemshop
        $produtos = obterProdutosNuvemshop($api_url, $headers);

        if (isset($produtos['error'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $produtos['error']]);
            exit;
        }

        $atualizados = 0;
        $erros = 0;
        $log = [];

        foreach ($produtos as $produto) {
            if (isset($produto['variants']) && is_array($produto['variants'])) {
                foreach ($produto['variants'] as $variante) {
                    if (isset($variante['sku']) && !empty($variante['sku'])) {
                        $codigo_gtin = $variante['sku'];

                        // Obter quantidade do produto no banco de dados local
                        $query = "SELECT po.qtde FROM produtos p
                                  INNER JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                                  WHERE p.codigo_gtin = '$codigo_gtin'";
                        $result = pg_query($conexao, $query);

                        if ($result && pg_num_rows($result) > 0) {
                            $row = pg_fetch_assoc($result);
                            $quantidade = $row['qtde'];

                            // Atualizar quantidade na Nuvemshop
                            $resultado = atualizarQuantidadeNuvemshop($api_url, $headers, $produto['id'], $variante['id'], $quantidade);

                            if ($resultado['success']) {
                                $atualizados++;
                                $log[] = "Produto com GTIN $codigo_gtin atualizado na Nuvemshop com quantidade $quantidade";
                            } else {
                                $erros++;
                                $log[] = "Erro ao atualizar produto com GTIN $codigo_gtin na Nuvemshop: " . json_encode($resultado);
                            }
                        }
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'atualizados' => $atualizados,
            'erros' => $erros,
            'log' => $log
        ]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tipo de sincronização não especificado']);
        break;
}
?>
