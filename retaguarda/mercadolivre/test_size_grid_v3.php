<?php
/**
 * Teste da implementação SIZE_GRID V3
 * Baseado na documentação oficial do Mercado Livre
 */

require_once '../conexao.php';
require_once 'token_manager.php';
require_once 'ml_size_chart_manager.php';

echo "=== TESTE SIZE_GRID V3 ===\n\n";

try {
    // Inicializar manager
    $sizeChartManager = getMLSizeChartManagerV3();
    
    // Dados de teste baseados no erro reportado
    $categoria = 'MLB31447'; // Camisetas e regatas
    $tamanhos = ['P', 'M', 'G'];
    $attributes = [
        [
            'id' => 'BRAND',
            'value_name' => 'Autoridade'
        ],
        [
            'id' => 'GENDER',
            'value_id' => '339666',
            'value_name' => 'Masculino'
        ]
    ];
    
    echo "1. Testando categoria: $categoria\n";
    echo "2. Tamanhos: " . implode(', ', $tamanhos) . "\n";
    echo "3. Atributos: " . json_encode($attributes, JSON_PRETTY_PRINT) . "\n\n";
    
    // Testar obtenção de SIZE_GRID
    echo "=== INICIANDO BUSCA DE SIZE_GRID ===\n";
    $result = $sizeChartManager->getSizeGridForProduct($tamanhos, $categoria, $attributes);
    
    if ($result['success']) {
        echo "✅ SUCCESS: SIZE_GRID obtido com sucesso!\n";
        echo "Chart ID: " . $result['chart_id'] . "\n";
        echo "Chart Name: " . $result['chart_name'] . "\n";
        echo "Type: " . $result['type'] . "\n";
        echo "Source: " . $result['source'] . "\n";
        
        // Testar obtenção de ROW_ID para cada tamanho
        echo "\n=== TESTANDO ROW_IDs ===\n";
        foreach ($tamanhos as $tamanho) {
            $rowId = $sizeChartManager->getRowIdForSize($result['chart_id'], $tamanho);
            echo "Tamanho $tamanho -> Row ID: $rowId\n";
        }
        
        // Simular estrutura final para ML
        echo "\n=== ESTRUTURA FINAL PARA ML ===\n";
        $finalAttributes = [
            [
                'id' => 'SIZE_GRID_ID',
                'value_id' => $result['chart_id'],
                'value_name' => $result['chart_name']
            ],
            [
                'id' => 'AGE_GROUP',
                'value_id' => '6725189',
                'value_name' => 'Adultos'
            ]
        ];
        
        echo "Atributos principais:\n";
        echo json_encode($finalAttributes, JSON_PRETTY_PRINT) . "\n";
        
        echo "\nExemplo de variação:\n";
        $exampleVariation = [
            'attributes' => [
                [
                    'id' => 'SIZE_GRID_ROW_ID',
                    'value_id' => $sizeChartManager->getRowIdForSize($result['chart_id'], 'M'),
                    'value_name' => 'M'
                ]
            ],
            'attribute_combinations' => [
                [
                    'id' => 'SIZE',
                    'value_name' => 'M'
                ]
            ]
        ];
        echo json_encode($exampleVariation, JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "❌ ERROR: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
