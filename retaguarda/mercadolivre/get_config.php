<?php
/**
 * Endpoint para obter configurações do ambiente
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir configurações
include 'ml_config.php';

// Retornar configuração do ambiente
echo json_encode(getMLEnvironmentConfig());
?>
