<?php
require_once 'export_product.php';

$categoria = 'MLB31447';

echo "=== TESTE CATEGORYSUPORTSVARIATIONS ===\n\n";

// Testar se categoria suporta variações
$supportsVariations = categorySupportsVariations($categoria);
echo "categorySupportsVariations($categoria): " . ($supportsVariations ? 'TRUE' : 'FALSE') . "\n";

if ($supportsVariations) {
    echo "❌ PROBLEMA ENCONTRADO: Categoria suporta variações\n";
    echo "   Isso significa que o código vai tentar buscar variações\n"; 
    echo "   Se não encontrar variações, vai exportar como produto simples\n";
    echo "   MAS não vai adicionar SIZE_GRID_ID porque não entra no ELSE\n";
} else {
    echo "✅ OK: Categoria não suporta variações\n";
    echo "   Vai entrar no ELSE e adicionar SIZE_GRID_ID\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se categorySupportsVariations retorna TRUE, precisamos mover\n";
echo "a lógica de SIZE_GRID_ID para DENTRO do bloco de variações\n";
echo "mesmo quando não há variações encontradas.\n";
?>