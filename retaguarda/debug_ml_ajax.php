<?php
// Debug do ML AJAX
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar se arquivos existem
    $files = [
        'conexao.php' => file_exists('conexao.php'),
        'mercadolivre/token_manager.php' => file_exists('mercadolivre/token_manager.php'),
        'mercadolivre/ml_config.php' => file_exists('mercadolivre/ml_config.php')
    ];
    
    // Incluir conexão
    include 'conexao.php';
    
    // Verificar conexão
    $conexao_ok = $conexao ? true : false;
    
    // Verificar configuração ML
    $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE' AND ativo = 1";
    $result = pg_query($conexao, $query);
    $config_exists = $result && pg_num_rows($result) > 0;
    
    $config_data = null;
    if ($config_exists) {
        $config_data = pg_fetch_assoc($result);
        // Não mostrar client_secret
        $config_data['client_secret'] = $config_data['client_secret'] ? '***CONFIGURADO***' : 'NÃO CONFIGURADO';
    }
    
    // Tentar incluir token manager
    $token_manager_ok = false;
    try {
        include 'mercadolivre/token_manager.php';
        $token_manager_ok = true;
    } catch (Exception $e) {
        $token_manager_error = $e->getMessage();
    }
    
    echo json_encode([
        'files_exist' => $files,
        'conexao_ok' => $conexao_ok,
        'config_exists' => $config_exists,
        'config_data' => $config_data,
        'token_manager_ok' => $token_manager_ok,
        'token_manager_error' => $token_manager_error ?? null,
        'php_errors' => error_get_last()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
