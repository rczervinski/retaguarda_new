<?php
/**
 * Debug específico para rastrear o fluxo SIZE_GRID
 */

require_once '../conexao.php';
require_once 'token_manager.php';

echo "=== DEBUG SIZE_GRID FLOW ===\n\n";

// Simular dados exatos do seu produto
$categoria = 'MLB31447';
$dados = [
    'title' => 'CAMISETA PRETA MASCULINA',
    'category_id' => 'MLB31447',
    'attributes' => [
        ['id' => 'BRAND', 'value_id' => '23001', 'value_name' => 'Lacoste'],
        ['id' => 'GENDER', 'value_id' => '339666', 'value_name' => 'Masculino'],
        ['id' => 'GARMENT_TYPE', 'value_id' => '12038970', 'value_name' => 'Camiseta'],
        ['id' => 'COLOR', 'value_id' => '52049', 'value_name' => 'Preto'],
        ['id' => 'SIZE', 'value_id' => '3259489', 'value_name' => 'UN'],
        ['id' => 'SLEEVE_TYPE', 'value_id' => '466804', 'value_name' => 'Curta'],
        ['id' => 'MODEL', 'value_name' => 'Boxy']
    ]
];

echo "1. DADOS ORIGINAIS:\n";
echo "Categoria: $categoria\n";
echo "Atributos: " . json_encode($dados['attributes'], JSON_PRETTY_PRINT) . "\n\n";

// Teste 1: Verificar se categoria requer SIZE_GRID
echo "2. TESTE: categoryRequiresSizeGrid()\n";
function categoryRequiresSizeGrid($categoria) {
    $categoriesRequiringSizeGrid = [
        'MLB31447', // Camisetas e regatas
        'MLB31448', // Polos
        'MLB31099', // Jeans
        'MLB1267',  // Roupas e acessórios
    ];
    
    return in_array($categoria, $categoriesRequiringSizeGrid);
}

$requiresSizeGrid = categoryRequiresSizeGrid($categoria);
echo "Categoria $categoria requer SIZE_GRID: " . ($requiresSizeGrid ? 'SIM' : 'NÃO') . "\n\n";

if (!$requiresSizeGrid) {
    echo "❌ PROBLEMA: Categoria não está na lista que requer SIZE_GRID!\n";
    exit;
}

// Teste 2: Verificar se categoria suporta variações
echo "3. TESTE: categorySupportsVariations()\n";
function categorySupportsVariations($categoria) {
    // Categorias que suportam variações no ML
    $variationCategories = [
        'MLB31447', // Camisetas e regatas
        'MLB31448', // Polos
        'MLB31099', // Jeans
        'MLB1059',  // Tênis
        'MLB1040',  // Sapatos casuais
        'MLB1267'   // Roupas e acessórios
    ];
    
    return in_array($categoria, $variationCategories);
}

$supportsVariations = categorySupportsVariations($categoria);
echo "Categoria $categoria suporta variações: " . ($supportsVariations ? 'SIM' : 'NÃO') . "\n\n";

// Teste 3: Simular o fluxo do código
echo "4. SIMULANDO FLUXO DO CÓDIGO:\n";

if ($supportsVariations) {
    echo "→ Categoria suporta variações\n";
    echo "→ Tentaria buildMLVariationsFromGrade()\n";
    echo "→ Se não conseguir criar variações, deveria chamar implementSizeChartForSimpleProduct()\n";
} else {
    echo "→ Categoria NÃO suporta variações\n";
    echo "→ Deveria verificar categoryRequiresSizeGrid()\n";
    if ($requiresSizeGrid) {
        echo "→ Deveria chamar implementSizeChartForSimpleProduct()\n";
    }
}

echo "\n5. TESTE: implementSizeChartForSimpleProduct()\n";

// Simular a função
function implementSizeChartForSimpleProductDebug($dados, $categoria) {
    echo "→ Iniciando implementSizeChartForSimpleProduct\n";
    
    // Extrair tamanho atual
    $currentSize = 'UN';
    foreach ($dados['attributes'] as $attr) {
        if ($attr['id'] === 'SIZE') {
            $currentSize = $attr['value_name'] ?? 'UN';
            break;
        }
    }
    
    echo "→ Tamanho encontrado: $currentSize\n";
    
    // Tentar carregar o manager
    if (!file_exists('ml_size_chart_manager.php')) {
        echo "❌ ERRO: Arquivo ml_size_chart_manager.php não encontrado!\n";
        return false;
    }
    
    require_once 'ml_size_chart_manager.php';
    
    try {
        $sizeChartManager = getMLSizeChartManagerV3();
        echo "→ Manager V3 carregado com sucesso\n";
        
        $chartResult = $sizeChartManager->getSizeGridForProduct([$currentSize], $categoria, $dados['attributes']);
        
        if ($chartResult['success']) {
            echo "→ ✅ SIZE_GRID obtido: " . $chartResult['chart_id'] . "\n";
            return true;
        } else {
            echo "→ ❌ Falha ao obter SIZE_GRID: " . $chartResult['error'] . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "→ ❌ EXCEÇÃO: " . $e->getMessage() . "\n";
        return false;
    }
}

$result = implementSizeChartForSimpleProductDebug($dados, $categoria);

echo "\n6. RESULTADO FINAL:\n";
if ($result) {
    echo "✅ SIZE_GRID deveria ter sido adicionado\n";
} else {
    echo "❌ SIZE_GRID NÃO foi adicionado - há um problema no fluxo\n";
}

echo "\n=== VERIFICAÇÕES ADICIONAIS ===\n";

// Verificar se as funções existem no export_product.php
if (function_exists('implementSizeChartForSimpleProduct')) {
    echo "✅ Função implementSizeChartForSimpleProduct existe\n";
} else {
    echo "❌ Função implementSizeChartForSimpleProduct NÃO existe\n";
}

if (function_exists('ensureAgeGroupAttribute')) {
    echo "✅ Função ensureAgeGroupAttribute existe\n";
} else {
    echo "❌ Função ensureAgeGroupAttribute NÃO existe\n";
}

echo "\n=== DEBUG CONCLUÍDO ===\n";
?>
