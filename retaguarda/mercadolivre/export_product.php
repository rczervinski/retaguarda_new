<?php
/**
 * Exportação de produtos para Mercado Livre
 */

require_once '../conexao.php';
require_once 'token_manager.php';
require_once 'category_predictor.php';
require_once 'error_mapper.php';
require_once 'image_manager.php';
require_once 'category_mapper.php';
require_once 'dynamic_category_mapper.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar parâmetros
if (!isset($_POST['codigo_gtin']) || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros obrigatórios: codigo_gtin, action']);
    exit;
}

$codigo_gtin = $_POST['codigo_gtin'] ?? '';
$action = $_POST['action'] ?? '';

// FALLBACK: Se GTIN estiver vazio, tentar extrair do produto
if (empty($codigo_gtin)) {
    error_log("DEBUG: GTIN vazio, tentando fallback...");

    // Tentar outros campos possíveis
    $codigo_gtin = $_POST['gtin'] ?? $_POST['codigo'] ?? '';

    // Se ainda estiver vazio, usar um valor de teste
    if (empty($codigo_gtin)) {
        $codigo_gtin = '204'; // TEMPORÁRIO: forçar GTIN para teste
        error_log("DEBUG: Usando GTIN de fallback: $codigo_gtin");
    }
}

// DEBUG: Log dos dados recebidos
error_log("=== DEBUG POST DATA ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
error_log("POST codigo_gtin: '" . $codigo_gtin . "'");
error_log("POST action: '" . $action . "'");
error_log("POST completo: " . print_r($_POST, true));
error_log("RAW INPUT: " . file_get_contents('php://input'));

try {
    $tokenManager = getMLTokenManager();
    
    switch ($action) {
        case 'export':
            $result = exportarProdutoML($codigo_gtin, $tokenManager);
            break;
            
        case 'update':
            $result = atualizarProdutoML($codigo_gtin, $tokenManager);
            break;
            
        case 'delete':
            $result = removerProdutoML($codigo_gtin, $tokenManager);
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Exporta produto para Mercado Livre
 */
function exportarProdutoML($codigo_gtin, $tokenManager) {
    global $conexao;

    // Buscar produto no banco
    $produto = buscarProduto($codigo_gtin);
    if (!$produto) {
        throw new Exception('Produto não encontrado: ' . $codigo_gtin);
    }

    // DEBUG: Log dos dados do produto
    error_log("=== DEBUG EXPORTAÇÃO ML ===");
    error_log("GTIN: " . $codigo_gtin);
    error_log("Dados do produto: " . print_r($produto, true));

    // Verificar se já existe no ML
    $existeML = verificarProdutoExisteML($codigo_gtin, $tokenManager);
    if ($existeML['exists']) {
        return ['error' => 'Produto já existe no Mercado Livre', 'ml_id' => $existeML['ml_id']];
    }

    // NOVO: Usar preditor inteligente de categoria
    $predictor = getMLCategoryPredictor();
    $prediction = $predictor->predictCategory($produto['descricao']);

    // DEBUG: Log da predição
    error_log("Predição de categoria: " . json_encode($prediction, JSON_PRETTY_PRINT));

    if (!$prediction['success']) {
        // Se predição falhou, usar fallback
        $prediction['best_prediction'] = $prediction['fallback'];
        error_log("Usando categoria fallback");
    }

    // Salvar predição no banco
    $predictor->savePrediction($codigo_gtin, $produto['descricao'], $prediction['best_prediction']);

    // Processar dados customizados do usuário
    $atributosCustomizados = [];
    if (isset($_POST['atributos_customizados'])) {
        $atributosCustomizados = $_POST['atributos_customizados'];
    }

    $precoAjustado = null;
    if (isset($_POST['preco_ajustado']) && !empty($_POST['preco_ajustado'])) {
        $precoAjustado = floatval($_POST['preco_ajustado']);
    }

    // DEBUG: Log dos dados customizados
    error_log("Atributos customizados: " . json_encode($atributosCustomizados));
    error_log("Preço ajustado: " . ($precoAjustado ?? 'não informado'));

    // Preparar dados para ML usando predição + customizações
    $dadosML = prepararDadosMLComPredicao($produto, $prediction['best_prediction'], $predictor, $atributosCustomizados, $precoAjustado);

    // Validação final dos atributos obrigatórios antes de enviar
    $dynamicMapper = getMLDynamicCategoryMapper();
    $categoriaId = $prediction['best_prediction']['category_id'];
    $attrInfo = $dynamicMapper->getCategoryAttributes($categoriaId);

    $missing = [];
    if ($attrInfo['success']) {
        $presentIds = array_map(function($a){ return $a['id']; }, $dadosML['attributes'] ?? []);
        foreach ($attrInfo['required_fields'] as $field) {
            if (!in_array($field['id'], $presentIds)) {
                $missing[] = $field;
            }
        }
    }

    // Checa obrigatórios condicionais (se aplicável) incluindo variations
    $conditional = $dynamicMapper->getConditionalRequired($categoriaId, $dadosML);
    if ($conditional['success'] && !empty($conditional['required_attributes'])) {
        $presentIds = array_map(function($a){ return $a['id']; }, $dadosML['attributes'] ?? []);
        foreach ($conditional['required_attributes'] as $cAttr) {
            if (!in_array($cAttr['id'], $presentIds)) {
                $missing[] = [
                    'id' => $cAttr['id'],
                    'name' => $cAttr['name'] ?? $cAttr['id'],
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Atributo obrigatório por condição'
                ];
            }
        }
    }

    if (!empty($missing)) {
        // Devolver para o front pedindo preenchimento dos campos antes do POST real
        return [
            'success' => false,
            'need_attributes' => true,
            'message' => 'Preencha os atributos obrigatórios da categoria antes de exportar.',
            'category_id' => $categoriaId,
            'required_fields' => $missing,
            'preview_payload' => $dadosML
        ];
    }

    // DEBUG: Log do JSON que será enviado
    error_log("JSON para ML: " . json_encode($dadosML, JSON_PRETTY_PRINT));
    
    // Enviar para ML
    $response = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/items',
        'POST',
        $dadosML
    );
    
    if (!$response['success']) {
        $httpCode = $response['http_code'] ?? 'N/A';

        // DEBUG: Log da resposta completa
        error_log("Resposta completa do ML: " . json_encode($response, JSON_PRETTY_PRINT));

        // NOVO: Mapear erros usando sistema inteligente
        $errorMapper = getMLErrorMapper($conexao);
        $mlErrors = $response['data']['cause'] ?? [];
        $mappedErrors = $errorMapper->mapErrors($mlErrors);

        // Retornar erro estruturado para o JavaScript
        $errorData = [
            'error' => 'Erro ao exportar para ML',
            'http_code' => $httpCode,
            'ml_response' => $response['data'],
            'mapped_errors' => $mappedErrors, // NOVO: Erros traduzidos e com soluções
            'debug_info' => [
                'url' => 'https://api.mercadolibre.com/items',
                'method' => 'POST',
                'sent_data' => $dadosML
            ]
        ];

        // Se houver campo cause, destacar (manter compatibilidade)
        if (isset($response['data']['cause'])) {
            $errorData['causes'] = $response['data']['cause'];
        }

        echo json_encode($errorData);
        exit;
    }
    
    $mlItem = $response['data'];
    
    // Marcar produto com tag ML
    marcarProdutoML($codigo_gtin, 'ML', $mlItem['id']);
    
    return [
        'success' => true,
        'message' => 'Produto exportado com sucesso',
        'ml_id' => $mlItem['id'],
        'ml_permalink' => $mlItem['permalink']
    ];
}

/**
 * Busca produto no banco de dados com dados completos
 */
function buscarProduto($codigo_gtin) {
    global $conexao;

    // Buscar dados completos do produto incluindo preço e estoque
    $query = "SELECT p.*,
                     pb.preco_venda,
                     pb.descricao_detalhada,
                     po.qtde as estoque,
                     po.peso,
                     po.altura,
                     po.largura,
                     po.comprimento
              FROM produtos p
              LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
              LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
              WHERE p.codigo_gtin = '$codigo_gtin'";

    $result = pg_query($conexao, $query);

    if ($result && pg_num_rows($result) > 0) {
        return pg_fetch_assoc($result);
    }

    return null;
}

/**
 * Verifica se produto já existe no ML
 */
function verificarProdutoExisteML($codigo_gtin, $tokenManager) {
    // Buscar por SKU (código GTIN)
    $response = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/users/me/items/search?q=' . urlencode($codigo_gtin)
    );
    
    if ($response['success'] && !empty($response['data']['results'])) {
        return ['exists' => true, 'ml_id' => $response['data']['results'][0]];
    }
    
    return ['exists' => false];
}

/**
 * Prepara dados do produto para formato do ML usando predição inteligente + customizações
 */
function prepararDadosMLComPredicao($produto, $prediction, $predictor, $atributosCustomizados = [], $precoAjustado = null) {
    // Usar categoria sugerida pela predição
    $categoria = $prediction['category_id'];

    // DEBUG: Mostrar campos específicos
    error_log("Campo descricao: '" . ($produto['descricao'] ?? 'NULL') . "'");
    error_log("Campo preco_venda: '" . ($produto['preco_venda'] ?? 'NULL') . "'");
    error_log("Campo estoque: '" . ($produto['estoque'] ?? 'NULL') . "'");

    // Validar campos obrigatórios
    $titulo = trim($produto['descricao'] ?? '');
    if (empty($titulo)) {
        throw new Exception('Título do produto é obrigatório');
    }

    // Usar preço ajustado se fornecido, senão usar preço original
    $preco = $precoAjustado ?? floatval($produto['preco_venda'] ?? 0);
    error_log("Preço convertido: " . $preco . " (ajustado: " . ($precoAjustado ? 'sim' : 'não') . ")");

    if ($preco <= 0) {
        throw new Exception('Preço deve ser maior que zero. Valor recebido: ' . $preco . ' (original: ' . ($produto['preco_venda'] ?? 'NULL') . ')');
    }

    $quantidade = intval($produto['estoque'] ?? 1);
    if ($quantidade < 1) {
        $quantidade = 1; // Mínimo 1
    }

    error_log("Valores finais - Título: '$titulo', Preço: $preco, Quantidade: $quantidade");

    // Dados básicos obrigatórios apenas
    $dados = [
        'title' => limitarTexto($titulo, 60),
        'category_id' => $categoria,
        'price' => $preco,
        'currency_id' => 'BRL',
        'available_quantity' => $quantidade,
        'condition' => 'new',
        'listing_type_id' => 'bronze',
        'buying_mode' => 'buy_it_now',
        // ✅ NOVO: Desabilitar frete grátis para evitar erros
        'shipping' => [
            'mode' => 'not_specified',
            'free_shipping' => false
        ]
    ];

    // Adicionar descrição se disponível
    $descricao = trim($produto['descricao_detalhada'] ?? $produto['descricao'] ?? '');
    if (!empty($descricao)) {
        $dados['description'] = [
            'plain_text' => limitarTexto($descricao, 50000)
        ];
    }

    // ✅ SISTEMA ROBUSTO DE IMAGENS (IGUAL NUVEMSHOP)
    error_log("DEBUG EXPORT: Iniciando sistema robusto de imagens");

    $productImages = [];
    $uploadDir = '../../upload/';
    $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Tentar diferentes GTINs possíveis
    $possibleGtins = [];

    // 1. GTIN do parâmetro (se não estiver vazio)
    if (!empty($codigo_gtin)) {
        $possibleGtins[] = $codigo_gtin;
    }

    // 2. GTIN do produto do banco
    if (!empty($produto['codigo_gtin'])) {
        $possibleGtins[] = $produto['codigo_gtin'];
    }

    // 3. Código interno como fallback
    if (!empty($produto['codigo_interno'])) {
        $possibleGtins[] = $produto['codigo_interno'];
    }

    error_log("DEBUG EXPORT: GTINs para testar: " . implode(', ', $possibleGtins));

    // Verificar cada GTIN possível
    foreach ($possibleGtins as $gtin) {
        if (empty($gtin)) continue;

        // Verificar imagem principal: gtin.ext
        foreach ($supportedExtensions as $ext) {
            $filename = $gtin . '.' . $ext;
            $filepath = $uploadDir . $filename;

            if (file_exists($filepath)) {
                $imageUrl = ML_DOMAIN . '/upload/' . $filename;
                $productImages[] = ['source' => $imageUrl];
                error_log("DEBUG EXPORT: ✅ Imagem principal encontrada: $imageUrl");
                break 2; // Sair dos dois loops - encontrou imagem
            }
        }

        // Se não encontrou principal, verificar secundárias: gtin_2.ext, gtin_3.ext, etc
        for ($pos = 2; $pos <= 4; $pos++) {
            foreach ($supportedExtensions as $ext) {
                $filename = $gtin . '_' . $pos . '.' . $ext;
                $filepath = $uploadDir . $filename;

                if (file_exists($filepath)) {
                    $imageUrl = ML_DOMAIN . '/upload/' . $filename;
                    $productImages[] = ['source' => $imageUrl];
                    error_log("DEBUG EXPORT: ✅ Imagem secundária encontrada: $imageUrl");
                    break 3; // Sair dos três loops - encontrou imagem
                }
            }
        }
    }

    // Adicionar imagens ao produto se encontrou alguma
    if (!empty($productImages)) {
        $dados['pictures'] = $productImages;
        error_log("DEBUG EXPORT: ✅ Total de imagens adicionadas: " . count($productImages));
    } else {
        error_log("DEBUG EXPORT: ❌ Nenhuma imagem encontrada para nenhum GTIN testado");
    }

    // Usar atributos sugeridos pela predição + customizações do usuário
    $dados['attributes'] = $predictor->prepareAttributes($prediction, $produto);
    // Remover possíveis atributos de variação/identificadores do nível do item para evitar duplicação
    $dados['attributes'] = array_values(array_filter($dados['attributes'], function($attr) {
        $id = $attr['id'] ?? '';
        if (in_array($id, ['SELLER_SKU','GTIN','EAN'])) return false;
        return true;
    }));

    // ✅ NOVO: Sistema dinâmico baseado na API do ML
    $dynamicMapper = getMLDynamicCategoryMapper();
    $categoryAttributes = $dynamicMapper->getCategoryAttributes($categoria);

    $requiredFields = [];

    if ($categoryAttributes['success']) {
        error_log("DEBUG EXPORT: Atributos obtidos da API do ML para categoria $categoria");

        // Processar campos obrigatórios da API
        foreach ($categoryAttributes['required_fields'] as $field) {
            $attrId = $field['id'];
            $value = '';

            // Buscar valor fornecido pelo usuário
            $userField = 'ml_field_' . strtolower($attrId);
            if (isset($_POST[$userField]) && !empty($_POST[$userField])) {
                $value = $_POST[$userField];
            }
            // Fallback para campos padrão
            elseif ($attrId === 'MANUFACTURER' || $attrId === 'BRAND') {
                $value = $_POST['brand'] ?? $_POST['manufacturer'] ?? $produto['fabricante'] ?? $produto['marca'] ?? '';
            }
            elseif ($attrId === 'PRODUCT_NAME' || $attrId === 'MODEL') {
                $value = $_POST['product_name'] ?? $produto['descricao'] ?? $dados['title'] ?? '';
            }

            if (!empty($value)) {
                $requiredFields[$attrId] = $value;
                error_log("DEBUG EXPORT: Campo $attrId = $value");
            }
        }
    } else {
        // Sem fallback manual: manter automático apenas
        error_log("DEBUG EXPORT: Atributos de categoria não disponíveis (sem fallback manual)");
    }

    foreach ($requiredFields as $fieldId => $value) {
        // Verificar se já existe nos atributos
        $exists = false;
        foreach ($dados['attributes'] as $attr) {
            if ($attr['id'] === $fieldId) {
                $exists = true;
                break;
            }
        }

        // Adicionar se não existir e valor não estiver vazio
        if (!$exists && !empty($value)) {
            $dados['attributes'][] = [
                'id' => $fieldId,
                'value_name' => $value
            ];
            error_log("DEBUG EXPORT: ✅ Campo obrigatório adicionado: $fieldId = $value");
        } elseif (!$exists) {
            error_log("DEBUG EXPORT: ⚠️ Campo $fieldId não preenchido pelo usuário");
        }
    }

    // Aplicar atributos customizados pelo usuário
    foreach ($atributosCustomizados as $attrId => $attrValue) {
        if (!empty($attrValue)) {
            // Remover atributo existente se houver
            $dados['attributes'] = array_filter($dados['attributes'], function($attr) use ($attrId) {
                return $attr['id'] !== $attrId;
            });

            // Adicionar novo valor
            $dados['attributes'][] = [
                'id' => $attrId,
                'value_name' => $attrValue
            ];
        }
    }

    // DEBUG: Log da categoria e atributos usados
    error_log("Categoria usada: $categoria (" . $prediction['category_name'] . ")");
    error_log("Atributos finais: " . json_encode($dados['attributes'], JSON_PRETTY_PRINT));
    
    // ✅ VARIAÇÕES: se o produto tiver grade, montar variations a partir de produtos_gd
    $variations = buildMLVariationsFromGrade($produto, $categoria);
    if (!empty($variations)) {
        // Validar se categoria suporta variations (tem ao menos um atributo allow_variations)
        $supportsVariations = categorySupportsVariations($categoria);
        if (!$supportsVariations) {
            throw new Exception('A categoria selecionada não suporta variações. Remova a grade ou selecione outra categoria.');
        }

        // Validar preços iguais entre variações
        $firstPrice = $variations[0]['price'];
        foreach ($variations as $v) {
            if (floatval($v['price']) !== floatval($firstPrice)) {
                throw new Exception('Todas as variações devem ter o mesmo preço. Ajuste os preços no seu cadastro.');
            }
        }
        // Alinhar preço do item
        $dados['price'] = $firstPrice;

        $dados['variations'] = $variations;
        error_log("DEBUG EXPORT: Variations montadas: " . json_encode($variations));
    }

    // Reindexar arrays para garantir formato de lista no JSON
    if (isset($dados['attributes']) && is_array($dados['attributes'])) {
        $dados['attributes'] = array_values($dados['attributes']);
    }
    if (isset($dados['variations']) && is_array($dados['variations'])) {
        foreach ($dados['variations'] as &$v) {
            if (isset($v['attributes']) && is_array($v['attributes'])) {
                $v['attributes'] = array_values($v['attributes']);
            }
            if (isset($v['attribute_combinations']) && is_array($v['attribute_combinations'])) {
                $v['attribute_combinations'] = array_values($v['attribute_combinations']);
            }
        }
        unset($v);
    }

    return $dados;
}

// Funções de imagens e atributos removidas para simplificar
// Serão implementadas futuramente quando necessário

/**
 * Marca produto com tag ML
 */
function marcarProdutoML($codigo_gtin, $tag, $ml_id = null) {
    global $conexao;
    
    $query = "UPDATE produtos SET ml = '$tag' WHERE codigo_gtin = '$codigo_gtin'";
    $result = pg_query($conexao, $query);
    
    if (!$result) {
        throw new Exception('Erro ao marcar produto: ' . pg_last_error($conexao));
    }
    
    // TODO: Salvar ML ID em tabela de relacionamento (implementar futuramente)
}

/**
 * Limita texto a um número de caracteres
 */
function limitarTexto($texto, $limite) {
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    
    return substr($texto, 0, $limite - 3) . '...';
}

/**
 * Atualiza produto no ML
 */
function atualizarProdutoML($codigo_gtin, $tokenManager) {
    // TODO: Implementar atualização
    return ['error' => 'Atualização ainda não implementada'];
}

/**
 * Remove produto do ML
 */
function removerProdutoML($codigo_gtin, $tokenManager) {
    // TODO: Implementar remoção
    return ['error' => 'Remoção ainda não implementada'];
}

/**
 * Monta as variações (variations) a partir da tabela produtos_gd para o produto pai
 * Regras solicitadas:
 * - SELLER_SKU das variações = GTIN do pai
 * - GTIN da variação = codigo_gtin da linha em produtos_gd (se válido 8-14 dígitos)
 * - attribute_combinations: usar produtos_gd.caracteristica/variacao mapeando para atributo allow_variations
 * - price = preço da variação (produtos_ib.preco_venda)
 * - available_quantity = estoque da variação (produtos_ou.qtde)
 */
function buildMLVariationsFromGrade($produtoPai, $categoryId) {
    global $conexao;
    $codigoInternoPai = $produtoPai['codigo_interno'] ?? null;
    if (empty($codigoInternoPai)) {
        return [];
    }

    // Buscar linhas de grade
    $queryGd = "SELECT codigo_gtin, caracteristica, variacao FROM produtos_gd WHERE codigo_interno = '" . pg_escape_string($conexao, $codigoInternoPai) . "' ORDER BY codigo";
    $resultGd = pg_query($conexao, $queryGd);
    if (!$resultGd || pg_num_rows($resultGd) < 2) { // precisa >1 para ser variação
        return [];
    }

    // Buscar atributos da categoria para mapear allow_variations
    $attrMeta = fetchCategoryAttributesRaw($categoryId);
    $mappedAttr = mapCaracteristicaToMlAttribute($attrMeta, $produtoPai);

    $variations = [];
    while ($row = pg_fetch_assoc($resultGd)) {
        $codigoGtinVar = $row['codigo_gtin'];
        $caracteristica = trim(strtolower($row['caracteristica'] ?? ''));
        $variacao = trim($row['variacao'] ?? '');

        // Dados da variação a partir do cadastro do produto com esse GTIN
        $qVar = "SELECT p.codigo_gtin, pb.preco_venda, po.qtde as estoque
                 FROM produtos p
                 LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                 LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                 WHERE p.codigo_gtin = '" . pg_escape_string($conexao, $codigoGtinVar) . "'";
        $rVar = pg_query($conexao, $qVar);
        $precoVar = null; $estoqueVar = 1;
        if ($rVar && pg_num_rows($rVar) > 0) {
            $varData = pg_fetch_assoc($rVar);
            $precoVar = floatval($varData['preco_venda'] ?? 0);
            $estoqueVar = intval($varData['estoque'] ?? 1);
            if ($estoqueVar < 0) { $estoqueVar = 0; }
        }

        // attribute_combinations
        $comb = buildAttributeCombinations($attrMeta, $mappedAttr, $caracteristica, $variacao);

        // attributes da variação
        $varAttributes = [];
        // SELLER_SKU da variação = GTIN do pai
        if (!empty($produtoPai['codigo_gtin'])) {
            $varAttributes[] = [ 'id' => 'SELLER_SKU', 'value_name' => (string)$produtoPai['codigo_gtin'] ];
        }
        // GTIN da variação quando válido
        $digitsOnly = preg_replace('/\D+/', '', (string)$codigoGtinVar);
        if (strlen($digitsOnly) >= 8 && strlen($digitsOnly) <= 14) {
            $varAttributes[] = [ 'id' => 'GTIN', 'value_name' => $digitsOnly ];
        }

        $variation = [
            'attribute_combinations' => $comb,
            'price' => $precoVar !== null && $precoVar > 0 ? $precoVar : floatval($produtoPai['preco_venda'] ?? 0),
            'available_quantity' => max(0, $estoqueVar),
            'attributes' => $varAttributes
        ];

        // Adicionar imagens específicas da variação se existirem no upload (GTIN da variação)
        try {
            require_once __DIR__ . '/image_manager.php';
            $imgManager = getMLImageManager();
            $varPics = $imgManager->prepareImagesForML($codigoGtinVar);
            if (!empty($varPics)) {
                // ML espera picture_ids (ids/URLs já carregadas) – podemos enviar sources no nível do item e urls aqui
                $variation['picture_ids'] = array_map(function($p){ return $p['source']; }, $varPics);
            }
        } catch (Exception $e) {
            error_log('WARN: Falha ao preparar imagens da variação: ' . $e->getMessage());
        }

        $variations[] = $variation;
    }

    return $variations;
}

/**
 * Busca atributos crus da categoria (para mapear allow_variations / values)
 */
function fetchCategoryAttributesRaw($categoryId) {
    try {
        $tokenManager = getMLTokenManager();
        $resp = $tokenManager->makeMLRequest('https://api.mercadolibre.com/categories/' . $categoryId . '/attributes', 'GET');
        if (($resp['success'] ?? false) && is_array($resp['data'])) {
            return $resp['data'];
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar attributes raw: ' . $e->getMessage());
    }
    return [];
}

/**
 * Verifica se a categoria suporta variações (há ao menos um atributo com tag allow_variations)
 */
function categorySupportsVariations($categoryId) {
    $attrs = fetchCategoryAttributesRaw($categoryId);
    foreach ($attrs as $attr) {
        if (in_array('allow_variations', $attr['tags'] ?? [])) {
            return true;
        }
    }
    return false;
}

/**
 * Mapeia nome da característica (ex.: cor, tamanho, voltagem) -> atributo ML allow_variations
 */
function mapCaracteristicaToMlAttribute($attributesRaw, $produtoPai) {
    $map = [];
    
    // Primeiro, mapear atributos reais da categoria que suportam variações
    foreach ($attributesRaw as $attr) {
        $tags = $attr['tags'] ?? [];
        if (in_array('allow_variations', $tags)) {
            $key = strtolower($attr['name']); // ex.: Cor, Tamanho
            $map[$key] = [ 'id' => $attr['id'], 'name' => $attr['name'], 'values' => $attr['values'] ?? [] ];
            
            // Também mapear por ID para facilitar busca
            $map[strtolower($attr['id'])] = [ 'id' => $attr['id'], 'name' => $attr['name'], 'values' => $attr['values'] ?? [] ];
        }
    }
    
    // Adicionar mapeamentos padrão apenas se não existirem na categoria
    $defaults = [
        'cor' => [ 'id' => 'COLOR', 'name' => 'Cor' ],
        'color' => [ 'id' => 'COLOR', 'name' => 'Cor' ],
        'tamanho' => [ 'id' => 'SIZE', 'name' => 'Tamanho' ],
        'size' => [ 'id' => 'SIZE', 'name' => 'Tamanho' ],
        'voltagem' => [ 'id' => 'VOLTAGE', 'name' => 'Voltagem' ],
        'voltage' => [ 'id' => 'VOLTAGE', 'name' => 'Voltagem' ]
    ];
    
    foreach ($defaults as $k => $v) {
        if (!isset($map[$k])) {
            $map[$k] = $v;
        }
    }
    
    return $map;
}

/**
 * Constrói attribute_combinations para uma variação
 */
function buildAttributeCombinations($attributesRaw, $mappedAttr, $caracteristica, $variacao) {
    $combinations = [];
    $carKey = strtolower(trim($caracteristica));
    $variacao = trim($variacao);

    // Log para debug
    error_log("DEBUG buildAttributeCombinations: caracteristica='$carKey', variacao='$variacao'");
    error_log("DEBUG buildAttributeCombinations: mappedAttr keys: " . implode(', ', array_keys($mappedAttr)));

    // Normalizar nomes comuns (ex.: cor)
    $aliases = [ 'cor' => 'cor', 'color' => 'cor', 'cores' => 'cor', 'tamanho' => 'tamanho', 'size' => 'tamanho' ];
    if (isset($aliases[$carKey])) { 
        $carKey = $aliases[$carKey]; 
        error_log("DEBUG buildAttributeCombinations: aliased to '$carKey'");
    }

    // Tentar casar pelo nome do atributo
    $targetAttr = null;
    
    // 1. Busca exata
    if (isset($mappedAttr[$carKey])) {
        $targetAttr = $mappedAttr[$carKey];
        error_log("DEBUG buildAttributeCombinations: found exact match: " . $targetAttr['id']);
    }
    // 2. Busca por similaridade
    else {
        foreach ($mappedAttr as $name => $info) {
            $lname = strtolower($name);
            if ($lname === $carKey || strpos($lname, $carKey) !== false || strpos($carKey, $lname) !== false) {
                $targetAttr = $info; 
                error_log("DEBUG buildAttributeCombinations: found similar match: $name -> " . $info['id']);
                break;
            }
        }
    }
    
    // 3. Fallback: primeiro allow_variations disponível
    if (!$targetAttr) {
        foreach ($attributesRaw as $attr) {
            if (in_array('allow_variations', $attr['tags'] ?? [])) { 
                $targetAttr = [ 'id' => $attr['id'], 'name' => $attr['name'] ]; 
                error_log("DEBUG buildAttributeCombinations: using fallback: " . $attr['id']);
                break; 
            }
        }
    }

    if ($targetAttr) {
        $valueId = null;
        // Se a categoria fornece lista de valores, tentar casar por nome para usar value_id
        if (isset($targetAttr['values']) && is_array($targetAttr['values'])) {
            foreach ($targetAttr['values'] as $v) {
                if (isset($v['name']) && strcasecmp(trim($v['name']), $variacao) === 0) {
                    $valueId = $v['id'];
                    error_log("DEBUG buildAttributeCombinations: found value_id: " . $valueId);
                    break;
                }
            }
        }
        
        $comb = [ 'id' => $targetAttr['id'], 'name' => $targetAttr['name'] ];
        if ($valueId) {
            $comb['value_id'] = $valueId;
            $comb['value_name'] = $variacao;
        } else {
            $comb['value_name'] = $variacao;
        }
        $combinations[] = $comb;
        error_log("DEBUG buildAttributeCombinations: created combination: " . json_encode($comb));
    } else {
        error_log("ERROR buildAttributeCombinations: no target attribute found for '$carKey'");
    }

    return $combinations;
}
?>
