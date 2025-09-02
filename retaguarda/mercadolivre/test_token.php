<?php
/**
 * Teste do sistema de tokens
 */

include 'token_manager.php';

header('Content-Type: application/json; charset=utf-8');

$tokenManager = getMLTokenManager();

// Verificar status do token
$status = $tokenManager->getTokenStatus();

// Testar requisição à API
$testResult = $tokenManager->makeMLRequest('https://api.mercadolibre.com/users/me');

$result = [
    'token_status' => $status,
    'api_test' => $testResult,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>
