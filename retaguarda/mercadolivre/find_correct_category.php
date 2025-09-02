<?php
/**
 * Buscar categoria correta que suporte SIZE_GRID para camisetas
 */

require_once 'conexaoml.php';
require_once 'token_manager.php';

echo "<h2>🔍 Buscando Categoria Correta para Camisetas com SIZE_GRID</h2>";

try {
    $tokenManager = getMLTokenManager();
    
    // Categorias relacionadas a camisetas para testar
    $categoriasParaTestar = [
        'MLB31447', // Camisetas e Regatas (atual)
        'MLB31448', // Polos
        'MLB1267',  // Roupas e acessórios
        'MLB1059',  // Tênis (para comparar)
        'MLB1040',  // Sapatos casuais (para comparar)
    ];
    
    echo "<h3>Testando categorias:</h3>";
    
    foreach ($categoriasParaTestar as $categoria) {
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Categoria: $categoria</h4>";
        
        $response = $tokenManager->makeMLRequest(
            "https://api.mercadolibre.com/categories/$categoria",
            'GET'
        );
        
        if ($response['success']) {
            $cat = $response['data'];
            echo "<strong>Nome:</strong> " . $cat['name'] . "<br>";
            echo "<strong>Domain ID:</strong> " . ($cat['domain_id'] ?? '<span style="color: red;">N/A</span>') . "<br>";
            echo "<strong>Attribute Types:</strong> " . implode(', ', $cat['attribute_types'] ?? []) . "<br>";
            
            // Verificar se suporta variações
            $supportsVariations = in_array('variations', $cat['attribute_types'] ?? []);
            echo "<strong>Suporta Variações:</strong> " . ($supportsVariations ? '✅ Sim' : '❌ Não') . "<br>";
            
            // Se tem domain_id, verificar se é de moda
            if (isset($cat['domain_id'])) {
                $domain = str_replace('MLB-', '', $cat['domain_id']);
                $isFashion = in_array($domain, ['SHIRTS', 'T_SHIRTS', 'JEANS', 'PANTS', 'DRESSES']);
                echo "<strong>É categoria de moda:</strong> " . ($isFashion ? '✅ Sim' : '❌ Não') . "<br>";
                
                if ($isFashion && $supportsVariations) {
                    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
                    echo "🎯 <strong>CATEGORIA IDEAL ENCONTRADA!</strong><br>";
                    echo "Esta categoria suporta SIZE_GRID e variações.";
                    echo "</div>";
                }
            }
            
        } else {
            echo "<span style='color: red;'>Erro ao consultar categoria</span>";
        }
        
        echo "</div>";
    }
    
    // Buscar categorias de camisetas que tenham domain_id
    echo "<h3>Buscando outras categorias de camisetas:</h3>";
    
    $searchResponse = $tokenManager->makeMLRequest(
        "https://api.mercadolibre.com/sites/MLB/search?q=camiseta&category=MLB1267&limit=1",
        'GET'
    );
    
    if ($searchResponse['success'] && !empty($searchResponse['data']['results'])) {
        $item = $searchResponse['data']['results'][0];
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Exemplo de produto real:</h4>";
        echo "<strong>Título:</strong> " . $item['title'] . "<br>";
        echo "<strong>Categoria:</strong> " . $item['category_id'] . "<br>";
        echo "<strong>Domain ID:</strong> " . ($item['domain_id'] ?? 'N/A') . "<br>";
        echo "<strong>Link:</strong> <a href='" . $item['permalink'] . "' target='_blank'>Ver produto</a><br>";
        echo "</div>";
        
        // Verificar a categoria deste produto
        if (isset($item['category_id'])) {
            $catResponse = $tokenManager->makeMLRequest(
                "https://api.mercadolibre.com/categories/" . $item['category_id'],
                'GET'
            );
            
            if ($catResponse['success']) {
                $realCat = $catResponse['data'];
                echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
                echo "<h4>Detalhes da categoria real:</h4>";
                echo "<strong>ID:</strong> " . $realCat['id'] . "<br>";
                echo "<strong>Nome:</strong> " . $realCat['name'] . "<br>";
                echo "<strong>Domain ID:</strong> " . ($realCat['domain_id'] ?? 'N/A') . "<br>";
                echo "<strong>Attribute Types:</strong> " . implode(', ', $realCat['attribute_types'] ?? []) . "<br>";
                echo "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ Erro:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
