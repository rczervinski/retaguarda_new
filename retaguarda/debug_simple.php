<?php
/**
 * Debug simples para testar o export_product.php
 */

// Habilitar exibição de todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Headers para debug
header('Content-Type: application/json; charset=utf-8');

error_log("=== DEBUG SIMPLES INICIADO ===");

try {
    // Simular um POST request para o export_product.php
    $_POST = [
        'codigo_gtin' => '2002002002',
        'action' => 'export',
        'preco_ajustado' => '',
        'ml_attr_BRAND' => ['id' => 'BRAND', 'value_name' => 'Autoridade'],
        'ml_attr_GENDER' => ['id' => 'GENDER', 'value_id' => '339666', 'value_name' => 'Masculino'],
        'ml_attr_GARMENT_TYPE' => ['id' => 'GARMENT_TYPE', 'value_id' => '12038970', 'value_name' => 'Camiseta'],
        'ml_attr_COLOR' => ['id' => 'COLOR', 'value_id' => '52049', 'value_name' => 'Preto'],
        'ml_attr_SIZE' => ['id' => 'SIZE', 'value_id' => '10490141', 'value_name' => 'G'],
        'ml_attr_SLEEVE_TYPE' => ['id' => 'SLEEVE_TYPE', 'value_id' => '466804', 'value_name' => 'Curta'],
        'ml_attr_MODEL' => ['id' => 'MODEL', 'value_name' => 'Boxy']
    ];
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    error_log("DEBUG: Incluindo export_product.php");
    
    // Capturar output buffer para evitar que interfira no JSON
    ob_start();
    
    // Incluir o arquivo de exportação
    include 'mercadolivre/export_product.php';
    
    $output = ob_get_clean();
    
    error_log("DEBUG: Output capturado: " . $output);
    
    echo $output;
    
} catch (Exception $e) {
    error_log("FATAL ERROR: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    error_log("FATAL PHP ERROR: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    echo json_encode([
        'error' => 'PHP Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

error_log("=== DEBUG SIMPLES FINALIZADO ===");
?>