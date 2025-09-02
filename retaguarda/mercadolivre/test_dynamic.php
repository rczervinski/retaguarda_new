<?php
/**
 * Teste do sistema dinâmico
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DO SISTEMA DINÂMICO ===\n\n";

try {
    echo "1. Testando require do dynamic_category_mapper...\n";
    require_once 'dynamic_category_mapper.php';
    echo "✅ dynamic_category_mapper.php carregado com sucesso\n\n";
    
    echo "2. Testando criação da classe...\n";
    $mapper = new MLDynamicCategoryMapper();
    echo "✅ MLDynamicCategoryMapper criado com sucesso\n\n";
    
    echo "3. Testando função helper...\n";
    $mapper2 = getMLDynamicCategoryMapper();
    echo "✅ getMLDynamicCategoryMapper() funcionando\n\n";
    
    echo "4. Testando busca de atributos (sem token)...\n";
    $result = $mapper->getCategoryAttributes('MLB432663');
    echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
