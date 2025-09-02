<?php
// Debug das configurações
include 'conexao.php';
include 'mercadolivre/config_urls.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar configurações no banco
$query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
$result = pg_query($conexao, $query);

$configs = [];
while ($row = pg_fetch_assoc($result)) {
    // Não mostrar client_secret por segurança
    $row['client_secret'] = $row['client_secret'] ? '***CONFIGURADO***' : 'NÃO CONFIGURADO';
    $configs[] = $row;
}

$debug = [
    'environment' => getEnvironmentConfig(),
    'database_configs' => $configs,
    'urls' => [
        'callback' => getCallbackUrl(),
        'webhook' => getWebhookUrl()
    ]
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
