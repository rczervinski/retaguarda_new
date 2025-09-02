<?php
/**
 * Busca categorias do Mercado Livre para seleção
 */

require_once '../conexao.php';
require_once 'token_manager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tokenManager = getMLTokenManager();
    
    // Buscar todas as categorias principais
    $categoriesResponse = $tokenManager->makeMLRequest('https://api.mercadolibre.com/sites/MLB/categories');
    
    if (!$categoriesResponse['success']) {
        throw new Exception('Erro ao buscar categorias: ' . json_encode($categoriesResponse));
    }
    
    $mainCategories = $categoriesResponse['data'];
    $leafCategories = [];
    
    // Função recursiva para encontrar categorias folha
    function findLeafCategories($categories, $tokenManager, &$leafCategories, $parentName = '') {
        foreach ($categories as $category) {
            // Buscar detalhes da categoria
            $categoryResponse = $tokenManager->makeMLRequest("https://api.mercadolibre.com/categories/" . $category['id']);
            
            if ($categoryResponse['success']) {
                $categoryData = $categoryResponse['data'];
                
                // Se não tem subcategorias, é uma categoria folha
                if (empty($categoryData['children_categories'])) {
                    $fullName = $parentName ? $parentName . ' > ' . $categoryData['name'] : $categoryData['name'];
                    
                    $leafCategories[] = [
                        'id' => $categoryData['id'],
                        'name' => $categoryData['name'],
                        'full_name' => $fullName,
                        'path_from_root' => $categoryData['path_from_root'] ?? []
                    ];
                } else {
                    // Tem subcategorias, explorar recursivamente
                    $fullName = $parentName ? $parentName . ' > ' . $categoryData['name'] : $categoryData['name'];
                    findLeafCategories($categoryData['children_categories'], $tokenManager, $leafCategories, $fullName);
                }
            }
            
            // Limitar para evitar timeout (processar apenas algumas por vez)
            if (count($leafCategories) >= 50) {
                break 2;
            }
        }
    }
    
    // Buscar categorias folha (limitado para não dar timeout)
    findLeafCategories(array_slice($mainCategories, 0, 5), $tokenManager, $leafCategories);
    
    // Adicionar algumas categorias conhecidas que funcionam
    $knownGoodCategories = [
        ['id' => 'MLB1144', 'name' => 'Agro > Outros', 'full_name' => 'Agro > Outros'],
        ['id' => 'MLB1168', 'name' => 'Antiguidades e Coleções > Outros', 'full_name' => 'Antiguidades e Coleções > Outros'],
        ['id' => 'MLB1132', 'name' => 'Brinquedos e Hobbies > Outros', 'full_name' => 'Brinquedos e Hobbies > Outros'],
        ['id' => 'MLB1276', 'name' => 'Música, Filmes e Seriados > Outros', 'full_name' => 'Música, Filmes e Seriados > Outros'],
        ['id' => 'MLB1039', 'name' => 'Câmeras e Acessórios > Outros', 'full_name' => 'Câmeras e Acessórios > Outros']
    ];
    
    // Mesclar categorias encontradas com conhecidas
    $allCategories = array_merge($knownGoodCategories, $leafCategories);
    
    // Remover duplicatas
    $uniqueCategories = [];
    $seenIds = [];
    
    foreach ($allCategories as $category) {
        if (!in_array($category['id'], $seenIds)) {
            $uniqueCategories[] = $category;
            $seenIds[] = $category['id'];
        }
    }
    
    // Ordenar por nome
    usort($uniqueCategories, function($a, $b) {
        return strcmp($a['full_name'], $b['full_name']);
    });
    
    echo json_encode([
        'success' => true,
        'categories' => $uniqueCategories,
        'total' => count($uniqueCategories),
        'note' => 'Lista limitada de categorias folha válidas para evitar timeout'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
