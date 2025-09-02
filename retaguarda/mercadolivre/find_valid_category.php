<?php
/**
 * Busca categorias folha válidas no Mercado Livre
 */

require_once '../conexao.php';
require_once 'token_manager.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $tokenManager = getMLTokenManager();
    
    echo "=== BUSCANDO CATEGORIAS FOLHA VÁLIDAS ===\n\n";
    
    // Buscar categorias principais
    $categoriesResponse = $tokenManager->makeMLRequest('https://api.mercadolibre.com/sites/MLB/categories');
    
    if (!$categoriesResponse['success']) {
        throw new Exception('Erro ao buscar categorias');
    }
    
    $categories = $categoriesResponse['data'];
    
    // Buscar subcategorias de "Outros" ou similares
    $targetCategories = ['MLB1499']; // Indústria e Comércio
    
    foreach ($targetCategories as $catId) {
        echo "=== Explorando categoria $catId ===\n";
        
        $catResponse = $tokenManager->makeMLRequest("https://api.mercadolibre.com/categories/$catId");
        
        if ($catResponse['success']) {
            $category = $catResponse['data'];
            echo "Nome: " . $category['name'] . "\n";
            echo "É folha: " . ($category['children_categories'] ? 'NÃO' : 'SIM') . "\n";
            
            if (!empty($category['children_categories'])) {
                echo "Subcategorias:\n";
                foreach ($category['children_categories'] as $child) {
                    echo "- " . $child['id'] . ": " . $child['name'] . "\n";
                    
                    // Verificar se a subcategoria é folha
                    $childResponse = $tokenManager->makeMLRequest("https://api.mercadolibre.com/categories/" . $child['id']);
                    if ($childResponse['success']) {
                        $childData = $childResponse['data'];
                        $isLeaf = empty($childData['children_categories']);
                        echo "  └─ É folha: " . ($isLeaf ? 'SIM ✅' : 'NÃO') . "\n";
                        
                        if ($isLeaf) {
                            echo "  └─ CATEGORIA VÁLIDA ENCONTRADA: " . $child['id'] . "\n";
                        }
                    }
                }
            }
            echo "\n";
        }
    }
    
    // Testar algumas categorias conhecidas como folha
    echo "=== TESTANDO CATEGORIAS CONHECIDAS ===\n";
    $testCategories = [
        'MLB1953', // Outros > Outros
        'MLB1648', // Computação > Outros
        'MLB1039', // Câmeras e Acessórios > Outros
        'MLB1144', // Agro > Outros
    ];
    
    foreach ($testCategories as $catId) {
        $catResponse = $tokenManager->makeMLRequest("https://api.mercadolibre.com/categories/$catId");
        
        if ($catResponse['success']) {
            $category = $catResponse['data'];
            $isLeaf = empty($category['children_categories']);
            echo "$catId: " . $category['name'] . " - É folha: " . ($isLeaf ? 'SIM ✅' : 'NÃO ❌') . "\n";
        } else {
            echo "$catId: CATEGORIA NÃO EXISTE ❌\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
