<?php
/**
 * API para verificar suporte a variações de uma categoria ML
 */

require_once '../conexao.php';
require_once 'token_manager.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar parâmetros
if (!isset($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'category_id é obrigatório']);
    exit;
}

$categoryId = $_GET['category_id'];

try {
    $tokenManager = getMLTokenManager();
    
    // Buscar atributos da categoria
    $response = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/categories/' . $categoryId . '/attributes'
    );
    
    if (!$response['success']) {
        throw new Exception('Erro ao consultar atributos da categoria');
    }
    
    $attributes = $response['data'];
    $allowVariationsAttrs = [];
    $variationAttributeAttrs = [];
    
    // Processar atributos
    foreach ($attributes as $attr) {
        $tags = $attr['tags'] ?? [];
        
        // Atributos que permitem variações (para attribute_combinations)
        if (isset($tags['allow_variations']) && $tags['allow_variations'] === true) {
            $allowVariationsAttrs[] = [
                'id' => $attr['id'],
                'name' => $attr['name'],
                'type' => $attr['type'] ?? 'string',
                'values' => $attr['values'] ?? []
            ];
        }
        
        // Atributos específicos de variações (para attributes das variações)
        if (isset($tags['variation_attribute']) && $tags['variation_attribute'] === true) {
            $variationAttributeAttrs[] = [
                'id' => $attr['id'],
                'name' => $attr['name'],
                'type' => $attr['type'] ?? 'string'
            ];
        }
    }
    
    // Montar resposta
    $result = [
        'success' => true,
        'category_id' => $categoryId,
        'supports_variations' => !empty($allowVariationsAttrs),
        'allow_variations_attributes' => $allowVariationsAttrs,
        'variation_attributes' => $variationAttributeAttrs,
        'total_attributes' => count($attributes)
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>