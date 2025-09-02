<?php
// Arquivo de proxy para comunicação com a API da Nuvemshop
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

// Obter configurações da Nuvemshop //back
$config = obterConfiguracoesNuvemshop();

if (!$config) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nenhuma configuração ativa da Nuvemshop encontrada']);
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

// Verificar a operação solicitada
$operation = isset($_GET['operation']) ? $_GET['operation'] : '';

/**
 * Função para remover referências circulares de um objeto JSON
 * Implementação melhorada para lidar com dados da API da Nuvemshop
 *
 * @param mixed $data Os dados a serem processados
 * @return string JSON seguro sem referências circulares
 */
function removeCircularReferences($data) {
    // Log para depuração
    error_log("Iniciando remoção de referências circulares");

    try {
        // Se já for uma string JSON, decodificar primeiro
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao decodificar JSON: " . json_last_error_msg());
                return is_string($data) ? $data : '{}';
            }
        }

        // Usar a função cleanObject para processar os dados
        $cleanData = cleanObject($data);

        // Converter para JSON
        $json = json_encode($cleanData);
        if ($json === false) {
            error_log("Erro ao codificar JSON: " . json_last_error_msg());

            // Tentar uma abordagem mais agressiva
            $simpleData = simplifyObject($cleanData);
            $json = json_encode($simpleData);

            if ($json === false) {
                error_log("Falha na segunda tentativa de codificar JSON: " . json_last_error_msg());
                return '{}';
            }
        }

        error_log("Remoção de referências circulares concluída com sucesso");
        return $json;
    } catch (Exception $e) {
        error_log("Exceção ao remover referências circulares: " . $e->getMessage());
        return '{}';
    }
}

/**
 * Função auxiliar para limpar objetos com referências circulares
 * Implementação melhorada para lidar com dados da API da Nuvemshop
 *
 * @param mixed $data Os dados a serem limpos
 * @param array $seen Array para rastrear objetos já processados (evitar recursão infinita)
 * @param int $depth Profundidade atual da recursão
 * @return mixed Dados limpos sem referências circulares
 */
function cleanObject($data, $seen = [], $depth = 0) {
    // Limitar a profundidade da recursão
    if ($depth > 10) {
        return "[Profundidade máxima atingida]";
    }

    // Tratar valores primitivos
    if (!is_array($data) && !is_object($data)) {
        return $data;
    }

    // Converter para array se for um objeto
    $array = is_object($data) ? get_object_vars($data) : $data;

    // Verificar se já processamos este objeto (evitar referências circulares)
    $objId = is_object($data) ? spl_object_hash($data) : null;
    if (is_object($data) && isset($seen[$objId])) {
        return "[Referência Circular]";
    }

    // Marcar este objeto como visto
    if (is_object($data)) {
        $seen[$objId] = true;
    }

    // Inicializar resultado
    $result = [];

    // Lista de propriedades problemáticas conhecidas na API da Nuvemshop
    $problematicProps = [
        'variants', 'images', 'categories', 'product', 'attributes', 'values',
        'children', 'parent', 'options', 'products'
    ];

    // Processar cada propriedade/elemento
    foreach ($array as $key => $value) {
        // Tratar propriedades problemáticas conhecidas
        if (in_array($key, $problematicProps)) {
            // Tratar variantes
            if ($key === 'variants' && is_array($value)) {
                $result[$key] = [];
                foreach ($value as $variant) {
                    // Extrair apenas as propriedades essenciais das variantes
                    $cleanVariant = [];
                    $essentialProps = ['id', 'sku', 'price', 'stock', 'stock_management', 'weight', 'depth', 'width', 'height'];

                    foreach ($essentialProps as $prop) {
                        if (isset($variant[$prop])) {
                            $cleanVariant[$prop] = $variant[$prop];
                        }
                    }

                    // Tratar valores das variantes (importante para atributos)
                    if (isset($variant['values']) && is_array($variant['values'])) {
                        $cleanVariant['values'] = [];
                        foreach ($variant['values'] as $value) {
                            if (is_array($value) && isset($value['pt'])) {
                                $cleanVariant['values'][] = ['pt' => $value['pt']];
                            } else {
                                $cleanVariant['values'][] = $value;
                            }
                        }
                    }

                    $result[$key][] = $cleanVariant;
                }
            }
            // Tratar atributos
            else if ($key === 'attributes' && is_array($value)) {
                $result[$key] = [];
                foreach ($value as $attribute) {
                    if (is_array($attribute) && isset($attribute['pt'])) {
                        $result[$key][] = ['pt' => $attribute['pt']];
                    } else {
                        $result[$key][] = $attribute;
                    }
                }
            }
            // Tratar valores
            else if ($key === 'values' && is_array($value)) {
                $result[$key] = [];
                foreach ($value as $val) {
                    if (is_array($val) && isset($val['pt'])) {
                        $result[$key][] = ['pt' => $val['pt']];
                    } else {
                        $result[$key][] = $val;
                    }
                }
            }
            // Tratar imagens
            else if ($key === 'images' && is_array($value)) {
                $result[$key] = [];
                foreach ($value as $image) {
                    if (is_array($image)) {
                        $cleanImage = [];
                        if (isset($image['id'])) $cleanImage['id'] = $image['id'];
                        if (isset($image['src'])) $cleanImage['src'] = $image['src'];
                        $result[$key][] = $cleanImage;
                    } else {
                        $result[$key][] = $image;
                    }
                }
            }
            // Tratar categorias
            else if ($key === 'categories' && is_array($value)) {
                $result[$key] = [];
                foreach ($value as $category) {
                    if (is_array($category)) {
                        $cleanCategory = [];
                        if (isset($category['id'])) $cleanCategory['id'] = $category['id'];
                        if (isset($category['name'])) $cleanCategory['name'] = $category['name'];
                        $result[$key][] = $cleanCategory;
                    } else {
                        $result[$key][] = $category;
                    }
                }
            }
            // Tratar referência ao produto
            else if ($key === 'product' && (is_object($value) || is_array($value))) {
                if (is_array($value) && isset($value['id'])) {
                    $result[$key] = ['id' => $value['id']];
                } else {
                    $result[$key] = null;
                }
            }
            // Outras propriedades problemáticas
            else {
                // Processar recursivamente, mas com cuidado
                $result[$key] = cleanObject($value, $seen, $depth + 1);
            }
        }
        // Processar recursivamente objetos e arrays normais
        else if (is_array($value) || is_object($value)) {
            $result[$key] = cleanObject($value, $seen, $depth + 1);
        }
        // Valores primitivos são copiados diretamente
        else {
            $result[$key] = $value;
        }
    }

    return $result;
}

/**
 * Função para simplificar objetos complexos quando outras abordagens falham
 * Usado como último recurso
 *
 * @param mixed $data Os dados a serem simplificados
 * @return array Objeto simplificado
 */
function simplifyObject($data) {
    // Se não for um array ou objeto, retornar como está
    if (!is_array($data) && !is_object($data)) {
        return $data;
    }

    // Converter para array se for um objeto
    $array = is_object($data) ? get_object_vars($data) : $data;
    $result = [];

    // Processar apenas o primeiro nível
    foreach ($array as $key => $value) {
        if (is_scalar($value) || $value === null) {
            // Valores primitivos são copiados diretamente
            $result[$key] = $value;
        } else if (is_array($value)) {
            // Arrays são simplificados para arrays vazios ou contagens
            $result[$key] = count($value) > 0 ? "[Array com " . count($value) . " elementos]" : "[]";
        } else if (is_object($value)) {
            // Objetos são simplificados para strings
            $result[$key] = "[Objeto]";
        } else {
            // Outros tipos são convertidos para string
            $result[$key] = "[Tipo desconhecido]";
        }
    }

    return $result;
}

// Função para fazer requisições à API da Nuvemshop com retry e logs detalhados
function makeRequest($method, $endpoint, $data = null, $retryCount = 0, $maxRetries = 3) {
    global $api_url, $headers;

    $url = $api_url . $endpoint;

    // Log da requisição
    $requestId = uniqid();
    $logPrefix = "[Request $requestId]";
    error_log("$logPrefix Iniciando requisição $method para $url");

    // Log dos dados enviados (limitando o tamanho para evitar logs muito grandes)
    if ($data) {
        $logData = substr($data, 0, 1000);
        if (strlen($data) > 1000) {
            $logData .= "... [truncado]";
        }
        error_log("$logPrefix Dados enviados: $logData");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Adicionar timeout para evitar requisições pendentes
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    // Habilitar informações de erro detalhadas
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } else if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Log do código de resposta
    error_log("$logPrefix Resposta recebida com código HTTP: $http_code");

    if (curl_errno($ch)) {
        $error = curl_error($ch);

        // Log do erro
        error_log("$logPrefix Erro CURL: $error");

        // Obter informações detalhadas do erro
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        error_log("$logPrefix Detalhes do erro CURL: $verboseLog");

        curl_close($ch);

        // Tentar novamente em caso de erros de rede
        if ($retryCount < $maxRetries) {
            $waitTime = pow(2, $retryCount) * 1000000; // Backoff exponencial (em microssegundos)
            error_log("$logPrefix Tentando novamente em " . ($waitTime / 1000000) . " segundos (tentativa " . ($retryCount + 1) . " de $maxRetries)");
            usleep($waitTime);
            return makeRequest($method, $endpoint, $data, $retryCount + 1, $maxRetries);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'error' => $error,
            'http_code' => $http_code,
            'details' => 'Falha após ' . ($retryCount + 1) . ' tentativas'
        ]);
        exit;
    }

    curl_close($ch);

    // Log da resposta (limitando o tamanho)
    if ($response) {
        $logResponse = substr($response, 0, 1000);
        if (strlen($response) > 1000) {
            $logResponse .= "... [truncado]";
        }
        error_log("$logPrefix Resposta: $logResponse");
    }

    // Verificar se é um erro 500 e tentar novamente
    if ($http_code == 500 && $retryCount < $maxRetries) {
        $waitTime = pow(2, $retryCount) * 1000000; // Backoff exponencial (em microssegundos)
        error_log("$logPrefix Erro 500 recebido. Tentando novamente em " . ($waitTime / 1000000) . " segundos (tentativa " . ($retryCount + 1) . " de $maxRetries)");
        usleep($waitTime);
        return makeRequest($method, $endpoint, $data, $retryCount + 1, $maxRetries);
    }

    // Limpar a resposta para remover referências circulares
    $cleanResponse = $response;
    if ($http_code >= 200 && $http_code < 300) {
        try {
            // Tentar decodificar a resposta
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse !== null) {
                // Se for um JSON válido, limpar e codificar novamente
                $cleanResponse = removeCircularReferences($decodedResponse);
                error_log("$logPrefix Resposta processada com sucesso");
            }
        } catch (Exception $e) {
            error_log("$logPrefix Erro ao processar resposta da API: " . $e->getMessage());
        }
    } else if ($http_code >= 400) {
        // Log detalhado para erros
        error_log("$logPrefix Erro na API (HTTP $http_code): $response");

        // Tentar extrair mensagem de erro mais detalhada
        try {
            $errorData = json_decode($response, true);
            if ($errorData && isset($errorData['message'])) {
                error_log("$logPrefix Mensagem de erro: " . $errorData['message']);
            }
            if ($errorData && isset($errorData['errors'])) {
                error_log("$logPrefix Detalhes do erro: " . json_encode($errorData['errors']));
            }
        } catch (Exception $e) {
            error_log("$logPrefix Erro ao processar detalhes do erro: " . $e->getMessage());
        }
    }

    return [
        'response' => $cleanResponse,
        'http_code' => $http_code
    ];
}

// Processar a operação solicitada
switch ($operation) {
    case 'list_categories':
        // Listar todas as categorias
        $result = makeRequest('GET', '/categories?fields=id,name,parent,handle');

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'create_category':
        // Criar nova categoria
        $data = file_get_contents('php://input');
        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Dados inválidos: ' . json_last_error_msg()]);
            exit;
        }

        $result = makeRequest('POST', '/categories', json_encode($decoded));

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'search':
        // Buscar produto por SKU
        $sku = isset($_GET['sku']) ? $_GET['sku'] : '';
        if (empty($sku)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'SKU não fornecido']);
            exit;
        }

        $result = makeRequest('GET', '/products?sku=' . urlencode($sku));

        if ($result['http_code'] == 200) {
            $products = json_decode($result['response'], true);

            // Verificar se encontrou algum produto com o SKU
            if (!empty($products)) {
                // Se encontrou produtos, retorna o primeiro que corresponde ao SKU exato
                $exactMatch = null;
                foreach ($products as $product) {
                    foreach ($product['variants'] as $variant) {
                        if ($variant['sku'] === $sku) {
                            $exactMatch = $product;
                            break 2;
                        }
                    }
                }

                if ($exactMatch) {
                    header('Content-Type: application/json');
                    echo json_encode($exactMatch);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([]);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode([]);
            }
        } else {
            header('Content-Type: application/json');
            echo $result['response'];
        }
        break;

    case 'update':
        // Atualizar produto existente
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');

        // Log dos dados recebidos
        error_log("Atualizando produto ID: $product_id");

        // Verificar se os dados são JSON válido
        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro nos dados recebidos para atualização: " . json_last_error_msg());
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Dados inválidos: ' . json_last_error_msg(),
                'http_code' => 400
            ]);
            exit;
        }

        // Verificar se há campos obrigatórios
        if (!isset($decoded['name']) || empty($decoded['name'])) {
            error_log("Campo obrigatório 'name' não fornecido na atualização do produto");
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Campo obrigatório não fornecido: name',
                'http_code' => 400
            ]);
            exit;
        }

        // Verificar se há atributos e valores
        if (isset($decoded['attributes']) && is_array($decoded['attributes'])) {
            error_log("Produto contém " . count($decoded['attributes']) . " atributos");

            // Verificar se as variantes têm valores correspondentes
            if (isset($decoded['variants']) && is_array($decoded['variants'])) {
                foreach ($decoded['variants'] as $index => $variant) {
                    if (!isset($variant['values']) || !is_array($variant['values'])) {
                        error_log("Variante $index não tem valores definidos");
                    } else if (count($variant['values']) != count($decoded['attributes'])) {
                        error_log("Variante $index tem " . count($variant['values']) . " valores, mas há " . count($decoded['attributes']) . " atributos");
                    }
                }
            }
        }

        // Fazer a requisição à API
        $result = makeRequest('PUT', '/products/' . $product_id, $data);

        // Verificar se houve erro 500
        if ($result['http_code'] == 500) {
            error_log("Erro 500 ao atualizar produto $product_id. Tentando abordagem alternativa.");

            // Tentar uma abordagem alternativa: dividir a atualização em partes
            // Primeiro, atualizar apenas os dados básicos do produto
            $basicData = [
                'name' => $decoded['name'],
                'description' => isset($decoded['description']) ? $decoded['description'] : '',
                'handle' => isset($decoded['handle']) ? $decoded['handle'] : '',
                'published' => isset($decoded['published']) ? $decoded['published'] : true
            ];

            $basicResult = makeRequest('PUT', '/products/' . $product_id, json_encode($basicData));

            if ($basicResult['http_code'] >= 200 && $basicResult['http_code'] < 300) {
                error_log("Atualização básica do produto $product_id bem-sucedida. Tentando atualizar variantes individualmente.");

                // Se a atualização básica foi bem-sucedida, tentar atualizar as variantes individualmente
                $variantsUpdated = true;

                if (isset($decoded['variants']) && is_array($decoded['variants'])) {
                    foreach ($decoded['variants'] as $variant) {
                        if (isset($variant['id'])) {
                            $variantId = $variant['id'];
                            $variantData = json_encode($variant);

                            $variantResult = makeRequest('PUT', '/products/' . $product_id . '/variants/' . $variantId, $variantData);

                            if ($variantResult['http_code'] >= 400) {
                                error_log("Erro ao atualizar variante $variantId: " . $variantResult['response']);
                                $variantsUpdated = false;
                            }
                        }
                    }
                }

                // Retornar o resultado da atualização básica com informações adicionais
                $responseData = json_decode($basicResult['response'], true);
                if (is_array($responseData)) {
                    $responseData['variants_updated'] = $variantsUpdated;
                    $responseData['message'] = 'Produto atualizado parcialmente devido a erro 500 inicial';

                    header('Content-Type: application/json');
                    echo json_encode($responseData);
                } else {
                    header('Content-Type: application/json');
                    echo $basicResult['response'];
                }
            } else {
                // Se a atualização básica também falhou, retornar o erro original
                header('Content-Type: application/json');
                echo $result['response'];
            }
        } else {
            // Retornar a resposta normal
            header('Content-Type: application/json');
            echo $result['response'];
        }
        break;

    case 'update_variant':
        // Atualizar variante de produto
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
        $variant_id = isset($_GET['variant_id']) ? $_GET['variant_id'] : '';

        if (empty($product_id) || empty($variant_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto ou da variante não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');

        // Log dos dados recebidos
        error_log("Atualizando variante ID: $variant_id do produto ID: $product_id");

        // Verificar se os dados são JSON válido
        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro nos dados recebidos para atualização de variante: " . json_last_error_msg());
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Dados inválidos: ' . json_last_error_msg(),
                'http_code' => 400
            ]);
            exit;
        }

        // Verificar se há valores para os atributos
        if (isset($decoded['values'])) {
            error_log("Variante contém " . (is_array($decoded['values']) ? count($decoded['values']) : 'valores não-array'));

            // Verificar se os valores são válidos
            if (is_array($decoded['values'])) {
                foreach ($decoded['values'] as $index => $value) {
                    if (empty($value)) {
                        error_log("Valor $index está vazio");
                        // Corrigir valores vazios
                        $decoded['values'][$index] = ['pt' => 'Padrão'];
                    } else if (is_array($value) && !isset($value['pt']) && !isset($value['en'])) {
                        error_log("Valor $index não tem chave de idioma");
                        // Adicionar chave de idioma se estiver faltando
                        if (is_string(reset($value))) {
                            $decoded['values'][$index] = ['pt' => reset($value)];
                        } else {
                            $decoded['values'][$index] = ['pt' => 'Padrão'];
                        }
                    }
                }

                // Atualizar os dados com os valores corrigidos
                $data = json_encode($decoded);
            }
        }

        // Fazer a requisição à API
        $result = makeRequest('PUT', '/products/' . $product_id . '/variants/' . $variant_id, $data);

        // Verificar se houve erro 422 (Unprocessable Entity)
        if ($result['http_code'] == 422) {
            error_log("Erro 422 ao atualizar variante $variant_id. Tentando abordagem alternativa.");

            // Tentar uma abordagem alternativa: remover os valores e atualizar apenas os dados básicos
            $basicData = [
                'price' => isset($decoded['price']) ? $decoded['price'] : 0,
                'stock' => isset($decoded['stock']) ? $decoded['stock'] : 0,
                'weight' => isset($decoded['weight']) ? $decoded['weight'] : 0,
                'width' => isset($decoded['width']) ? $decoded['width'] : 0,
                'height' => isset($decoded['height']) ? $decoded['height'] : 0,
                'depth' => isset($decoded['depth']) ? $decoded['depth'] : 0
            ];

            $basicResult = makeRequest('PUT', '/products/' . $product_id . '/variants/' . $variant_id, json_encode($basicData));

            if ($basicResult['http_code'] >= 200 && $basicResult['http_code'] < 300) {
                error_log("Atualização básica da variante $variant_id bem-sucedida.");

                // Retornar o resultado da atualização básica com informações adicionais
                $responseData = json_decode($basicResult['response'], true);
                if (is_array($responseData)) {
                    $responseData['message'] = 'Variante atualizada parcialmente devido a erro 422 inicial';

                    header('Content-Type: application/json');
                    echo json_encode($responseData);
                } else {
                    header('Content-Type: application/json');
                    echo $basicResult['response'];
                }
            } else {
                // Se a atualização básica também falhou, retornar o erro original
                header('Content-Type: application/json');
                echo $result['response'];
            }
        } else if ($result['http_code'] == 500) {
            error_log("Erro 500 ao atualizar variante $variant_id. Tentando abordagem alternativa.");

            // Tentar uma abordagem alternativa: atualizar apenas os dados básicos
            $basicData = [
                'price' => isset($decoded['price']) ? $decoded['price'] : 0,
                'stock' => isset($decoded['stock']) ? $decoded['stock'] : 0
            ];

            $basicResult = makeRequest('PUT', '/products/' . $product_id . '/variants/' . $variant_id, json_encode($basicData));

            if ($basicResult['http_code'] >= 200 && $basicResult['http_code'] < 300) {
                error_log("Atualização básica da variante $variant_id bem-sucedida.");

                // Retornar o resultado da atualização básica com informações adicionais
                $responseData = json_decode($basicResult['response'], true);
                if (is_array($responseData)) {
                    $responseData['message'] = 'Variante atualizada parcialmente devido a erro 500 inicial';

                    header('Content-Type: application/json');
                    echo json_encode($responseData);
                } else {
                    header('Content-Type: application/json');
                    echo $basicResult['response'];
                }
            } else {
                // Se a atualização básica também falhou, retornar o erro original
                header('Content-Type: application/json');
                echo $result['response'];
            }
        } else {
            // Retornar a resposta normal
            header('Content-Type: application/json');
            echo $result['response'];
        }
        break;

    case 'create_variant':
        // Criar nova variante para um produto existente
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');

        $result = makeRequest('POST', '/products/' . $product_id . '/variants', $data);

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'get_product':
        // Obter dados completos de um produto
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        $result = makeRequest('GET', '/products/' . $product_id, null);

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'image_add':
        // Adicionar nova imagem ao produto
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');
        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Dados inválidos: ' . json_last_error_msg()]);
            exit;
        }

        // Validar dados obrigatórios
        if (!isset($decoded['src']) || !isset($decoded['position'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Campos obrigatórios: src, position']);
            exit;
        }

        // Preparar dados para a API
        $imageData = [
            'src' => $decoded['src'],
            'position' => (int)$decoded['position'],
            'product_id' => (int)$product_id
        ];

        error_log("Adicionando imagem ao produto $product_id: " . json_encode($imageData));

        $result = makeRequest('POST', '/products/' . $product_id . '/images', json_encode($imageData));

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'image_update':
        // Atualizar imagem existente do produto
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');
        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Dados inválidos: ' . json_last_error_msg()]);
            exit;
        }

        // Validar dados obrigatórios
        if (!isset($decoded['id']) || !isset($decoded['src']) || !isset($decoded['position'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Campos obrigatórios: id, src, position']);
            exit;
        }

        // Preparar dados para a API
        $imageData = [
            'id' => (int)$decoded['id'],
            'src' => $decoded['src'],
            'position' => (int)$decoded['position'],
            'product_id' => (int)$product_id
        ];

        error_log("Atualizando imagem {$decoded['id']} do produto $product_id: " . json_encode($imageData));

        $result = makeRequest('PUT', '/products/' . $product_id . '/images/' . $decoded['id'], json_encode($imageData));

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    case 'image_remove':
        // Remover imagem do produto
        $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

        if (empty($product_id)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID do produto não fornecido']);
            exit;
        }

        // Obter dados do corpo da requisição
        $data = file_get_contents('php://input');
        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Dados inválidos: ' . json_last_error_msg()]);
            exit;
        }

        // Validar dados obrigatórios
        if (!isset($decoded['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Campo obrigatório: id']);
            exit;
        }

        error_log("Removendo imagem {$decoded['id']} do produto $product_id");

        $result = makeRequest('DELETE', '/products/' . $product_id . '/images/' . $decoded['id'], null);

        header('Content-Type: application/json');
        echo $result['response'];
        break;

    default:
        // Criar novo produto (POST para /products)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obter dados do corpo da requisição
            $data = file_get_contents('php://input');

            $result = makeRequest('POST', '/products', $data);

            header('Content-Type: application/json');
            echo $result['response'];
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Operação não suportada']);
        }
        break;
}
?>
