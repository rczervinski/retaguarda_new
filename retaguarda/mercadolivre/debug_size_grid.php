<?php
/**
 * Debug direto do SIZE_GRID_ID
 */

// Simular dados do produto exportado
$dados = [
    "title" => "CAMISETA PRETA MASCULINA",
    "category_id" => "MLB31447",
    "attributes" => [
        [
            "id" => "BRAND",
            "value_name" => "Autoridade"
        ],
        [
            "id" => "GENDER", 
            "value_id" => "339666",
            "value_name" => "Masculino"
        ],
        [
            "id" => "SIZE",
            "value_id" => "3259489", 
            "value_name" => "UN"
        ]
    ]
];

$categoria = "MLB31447";

echo "=== DEBUG SIZE_GRID_ID ===\n\n";

// Teste 1: categoryRequiresSizeGrid
function categoryRequiresSizeGrid($categoria) {
    $categoriesRequiringSizeGrid = [
        "MLB31447", // Camisetas e regatas
        "MLB31448", // Polos
        "MLB31099", // Jeans
        "MLB1267",  // Roupas e acessórios
    ];
    
    return in_array($categoria, $categoriesRequiringSizeGrid);
}

$requiresSizeGrid = categoryRequiresSizeGrid($categoria);
echo "1. categoryRequiresSizeGrid($categoria): " . ($requiresSizeGrid ? "TRUE" : "FALSE") . "\n";

echo "2. Resultado esperado: TRUE\n";
echo "3. Se TRUE, então o código DEVE adicionar SIZE_GRID_ID\n";

if ($requiresSizeGrid) {
    echo "✅ TESTE PASSOU - Categoria exige SIZE_GRID_ID\n";
} else {
    echo "❌ TESTE FALHOU - Categoria não detectada como exigindo SIZE_GRID_ID\n";
}
?>
