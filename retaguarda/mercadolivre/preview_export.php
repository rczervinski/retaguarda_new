<?php
/**
 * Preview da exportação - mostra predição e requisitos antes de exportar
 */

require_once '../conexao.php';
require_once 'token_manager.php';
require_once 'category_predictor.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

if (!isset($_POST['codigo_gtin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'GTIN não informado']);
    exit;
}

$codigo_gtin = $_POST['codigo_gtin'];

try {
    // Buscar produto
    $query = "SELECT p.*, 
                     pb.preco_venda, 
                     pb.descricao_detalhada, 
                     po.qtde as estoque
              FROM produtos p
              LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
              LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
              WHERE p.codigo_gtin = '$codigo_gtin'";
    
    $result = pg_query($conexao, $query);
    
    if (!$result || pg_num_rows($result) == 0) {
        throw new Exception('Produto não encontrado');
    }
    
    $produto = pg_fetch_assoc($result);
    
    // Fazer predição
    $predictor = getMLCategoryPredictor();
    $prediction = $predictor->predictCategory($produto['descricao']);
    
    if (!$prediction['success']) {
        throw new Exception('Erro na predição: ' . $prediction['error']);
    }
    
    // Analisar requisitos da categoria
    $requirements = analyzeRequirements($produto, $prediction['best_prediction']);
    
    // Preparar resposta
    $response = [
        'success' => true,
        'produto' => [
            'gtin' => $produto['codigo_gtin'],
            'titulo' => $produto['descricao'],
            'preco' => floatval($produto['preco_venda'] ?? 0),
            'estoque' => intval($produto['estoque'] ?? 1)
        ],
        'prediction' => $prediction['best_prediction'],
        'all_predictions' => $prediction['predictions'],
        'requirements' => $requirements,
        'can_export' => $requirements['can_export'],
        'issues' => $requirements['issues']
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Analisa requisitos da categoria e produto
 */
function analyzeRequirements($produto, $prediction) {
    $issues = [];
    $canExport = true;
    
    $preco = floatval($produto['preco_venda'] ?? 0);
    $titulo = trim($produto['descricao'] ?? '');
    $estoque = intval($produto['estoque'] ?? 1);
    
    // Verificações básicas
    if (empty($titulo)) {
        $issues[] = [
            'type' => 'error',
            'field' => 'titulo',
            'message' => 'Título é obrigatório'
        ];
        $canExport = false;
    }
    
    if ($preco <= 0) {
        $issues[] = [
            'type' => 'error', 
            'field' => 'preco',
            'message' => 'Preço deve ser maior que zero'
        ];
        $canExport = false;
    }
    
    if ($estoque < 1) {
        $issues[] = [
            'type' => 'warning',
            'field' => 'estoque', 
            'message' => 'Estoque baixo (será definido como 1)'
        ];
    }
    
    // Verificações específicas da categoria
    $categoryId = $prediction['category_id'];
    
    // Requisitos conhecidos por categoria
    $categoryRequirements = getCategoryRequirements($categoryId);
    
    foreach ($categoryRequirements as $req) {
        if ($req['type'] === 'min_price' && $preco < $req['value']) {
            $issues[] = [
                'type' => 'error',
                'field' => 'preco',
                'message' => "Categoria {$prediction['category_name']} requer preço mínimo de R$ {$req['value']}"
            ];
            $canExport = false;
        }
    }
    
    // Verificar atributos obrigatórios
    $requiredAttributes = getRequiredAttributes($categoryId);
    foreach ($requiredAttributes as $attr) {
        $issues[] = [
            'type' => 'warning',
            'field' => 'atributos',
            'message' => "Campo \"{$attr['name']}\" é obrigatório para esta categoria"
        ];
    }
    
    return [
        'can_export' => $canExport,
        'issues' => $issues,
        'category_requirements' => $categoryRequirements,
        'required_attributes' => $requiredAttributes
    ];
}

/**
 * Requisitos conhecidos por categoria
 */
function getCategoryRequirements($categoryId) {
    $requirements = [
        'MLB432663' => [ // Categoria de vegetais
            ['type' => 'min_price', 'value' => 8, 'message' => 'Preço mínimo R$ 8,00']
        ]
    ];
    
    return $requirements[$categoryId] ?? [];
}

/**
 * Atributos obrigatórios conhecidos por categoria
 */
function getRequiredAttributes($categoryId) {
    $attributes = [
        'MLB432663' => [ // Categoria de vegetais
            ['id' => 'VEGETABLE_TYPE', 'name' => 'Tipo de vegetal'],
            ['id' => 'VEGETABLE_VARIETY', 'name' => 'Variedade do vegetal']
        ]
    ];
    
    return $attributes[$categoryId] ?? [];
}
?>
