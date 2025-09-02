<?php
/**
 * Script de debug para identificar erro 500 na exportação
 */

// Habilitar exibição de todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Headers para debug
header('Content-Type: application/json; charset=utf-8');

// Log de início
error_log("=== DEBUG EXPORT INICIADO ===");

try {
    // Incluir arquivos necessários com debug
    error_log("DEBUG: Incluindo conexao.php");
    require_once '../conexao.php';
    
    error_log("DEBUG: Incluindo token_manager.php");
    require_once 'token_manager.php';
    
    error_log("DEBUG: Incluindo category_predictor.php");
    require_once 'category_predictor.php';
    
    error_log("DEBUG: Incluindo error_mapper.php");
    require_once 'error_mapper.php';
    
    error_log("DEBUG: Incluindo image_manager.php");
    require_once 'image_manager.php';
    
    error_log("DEBUG: Incluindo category_mapper.php");
    require_once 'category_mapper.php';
    
    error_log("DEBUG: Incluindo dynamic_category_mapper.php");
    require_once 'dynamic_category_mapper.php';
    
    error_log("DEBUG: Todos os includes carregados com sucesso");
    
    // Testar conexão com banco
    if (!$conexao) {
        throw new Exception("Conexão com banco falhou");
    }
    error_log("DEBUG: Conexão com banco OK");
    
    // Simular dados de POST para teste
    $_POST = [
        'codigo_gtin' => '2002002002',
        'action' => 'export',
        'preco_ajustado' => '',
        'ml_attr_BRAND' => ['id' => 'BRAND', 'value_name' => 'Autoridade'],
        'ml_attr_GENDER' => ['id' => 'GENDER', 'value_id' => '339666', 'value_name' => 'Masculino'],
        'ml_attr_GARMENT_TYPE' => ['id' => 'GARMENT_TYPE', 'value_id' => '12038970', 'value_name' => 'Camiseta'],
        'ml_attr_COLOR' => ['id' => 'COLOR', 'value_id' => '52049', 'value_name' => 'Preto'],
        'ml_attr_SIZE' => ['id' => 'SIZE', 'value_id' => '10490141', 'value_name' => 'G'],
        'ml_attr_SLEEVE_TYPE' => ['id' => 'SLEEVE_TYPE', 'value_id' => '466804', 'value_name' => 'Curta'],
        'ml_attr_MODEL' => ['id' => 'MODEL', 'value_name' => 'Boxy']
    ];
    
    error_log("DEBUG: POST data simulado");
    
    // Testar token manager
    error_log("DEBUG: Testando token manager");
    $tokenManager = getMLTokenManager();
    error_log("DEBUG: Token manager OK");
    
    // Testar busca do produto
    error_log("DEBUG: Testando busca do produto");
    $codigo_gtin = '2002002002';
    
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
    
    error_log("DEBUG: Query produto: $query");
    $result = pg_query($conexao, $query);
    
    if (!$result) {
        throw new Exception("Erro na query do produto: " . pg_last_error($conexao));
    }
    
    if (pg_num_rows($result) === 0) {
        throw new Exception("Produto não encontrado: $codigo_gtin");
    }
    
    $produto = pg_fetch_assoc($result);
    error_log("DEBUG: Produto encontrado: " . json_encode($produto));
    
    // Testar predição de categoria
    error_log("DEBUG: Testando predição de categoria");
    $predictor = getMLCategoryPredictor();
    $prediction = $predictor->predictCategory($produto['descricao']);
    error_log("DEBUG: Predição OK: " . json_encode($prediction));
    
    // Testar se categoria suporta variações
    error_log("DEBUG: Testando suporte a variações");
    $categoria = $prediction['best_prediction']['category_id'];
    
    function categorySupportsVariations($categoryId) {
        try {
            $tokenManager = getMLTokenManager();
            $resp = $tokenManager->makeMLRequest('https://api.mercadolibre.com/categories/' . $categoryId . '/attributes', 'GET');
            if (($resp['success'] ?? false) && is_array($resp['data'])) {
                foreach ($resp['data'] as $attr) {
                    if (in_array('allow_variations', $attr['tags'] ?? [])) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao verificar suporte a variações: ' . $e->getMessage());
        }
        return false;
    }
    
    $supportsVariations = categorySupportsVariations($categoria);
    error_log("DEBUG: Categoria $categoria suporta variações: " . ($supportsVariations ? 'SIM' : 'NÃO'));
    
    // Testar busca da grade
    error_log("DEBUG: Testando busca da grade");
    $codigo_interno = $produto['codigo_interno'];
    $queryGd = "SELECT codigo_gtin, caracteristica, variacao FROM produtos_gd WHERE codigo_interno = '" . pg_escape_string($conexao, $codigo_interno) . "' ORDER BY codigo";
    error_log("DEBUG: Query grade: $queryGd");
    
    $resultGd = pg_query($conexao, $queryGd);
    if (!$resultGd) {
        error_log("ERROR: Falha na query da grade: " . pg_last_error($conexao));
    } else {
        $numRows = pg_num_rows($resultGd);
        error_log("DEBUG: Grade encontrada: $numRows registros");
        
        if ($numRows > 0) {
            while ($row = pg_fetch_assoc($resultGd)) {
                error_log("DEBUG: Grade row: " . json_encode($row));
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug concluído com sucesso',
        'produto' => $produto,
        'categoria' => $categoria,
        'suporta_variacoes' => $supportsVariations,
        'prediction' => $prediction
    ]);
    
} catch (Exception $e) {
    error_log("FATAL ERROR: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    error_log("FATAL PHP ERROR: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

error_log("=== DEBUG EXPORT FINALIZADO ===");
?>