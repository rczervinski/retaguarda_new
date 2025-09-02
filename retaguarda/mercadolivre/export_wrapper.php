<?php
/**
 * Wrapper para capturar erros fatais no export_product.php
 */

// Capturar TODOS os erros possíveis
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Função para capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("FATAL SHUTDOWN ERROR: " . json_encode($error));
        
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        
        echo json_encode([
            'fatal_error' => true,
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'type' => $error['type']
        ]);
    }
});

// Headers
header('Content-Type: application/json; charset=utf-8');

try {
    error_log("=== EXPORT WRAPPER INICIADO ===");
    error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNDEFINED'));
    error_log("POST data: " . print_r($_POST, true));
    
    // Verificar se o arquivo existe
    $exportFile = __DIR__ . '/export_product.php';
    if (!file_exists($exportFile)) {
        throw new Exception("Arquivo export_product.php não encontrado: $exportFile");
    }
    
    error_log("DEBUG: Arquivo export_product.php encontrado");
    
    // Incluir o arquivo original
    include $exportFile;
    
    error_log("=== EXPORT WRAPPER FINALIZADO ===");
    
} catch (ParseError $e) {
    error_log("PARSE ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'parse_error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    error_log("PHP ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'php_error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'exception' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>