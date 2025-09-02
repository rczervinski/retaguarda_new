<?php
/**
 * Configuração dinâmica de URLs para Mercado Livre
 * Detecta automaticamente se está em localhost ou produção
 */

// Detectar ambiente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$isLocalhost = in_array($host, ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8080']);
$isNgrok = strpos($host, 'ngrok') !== false || strpos($host, 'ngrok-free.app') !== false;

// URLs baseadas no ambiente
if ($isNgrok) {
    // Configuração para ngrok (desenvolvimento com túnel)
    define('ML_CALLBACK_URL', 'https://37297414505f.ngrok-free.app/retaguarda/mercadolivre/auth/callback.php');
    define('ML_WEBHOOK_URL', 'https://37297414505f.ngrok-free.app/retaguarda/mercadolivre/webhook/receiver.php');
    define('ML_BASE_URL', 'https://37297414505f.ngrok-free.app/retaguarda/');
} elseif ($isLocalhost) {
    // Configuração para desenvolvimento local (sem túnel)
    define('ML_CALLBACK_URL', 'http://localhost/retaguarda/mercadolivre/auth/callback.php');
    define('ML_WEBHOOK_URL', 'http://localhost/retaguarda/mercadolivre/webhook/receiver.php');
    define('ML_BASE_URL', 'http://localhost/retaguarda/');
} else {
    // Configuração para produção
    define('ML_CALLBACK_URL', 'https://demo.gutty.app.br/retaguarda/mercadolivre/auth/callback.php');
    define('ML_WEBHOOK_URL', 'https://demo.gutty.app.br/retaguarda/mercadolivre/webhook/receiver.php');
    define('ML_BASE_URL', 'https://demo.gutty.app.br/retaguarda/');
}

// URLs da API do Mercado Livre (sempre as mesmas)
define('ML_API_BASE_URL', 'https://api.mercadolibre.com');
define('ML_AUTH_URL', 'https://auth.mercadolibre.com.br/authorization');
define('ML_TOKEN_URL', 'https://api.mercadolibre.com/oauth/token');
define('ML_SITE_ID', 'MLB'); // Brasil

/**
 * Função para obter URL de callback dinâmica
 */
function getCallbackUrl() {
    return ML_CALLBACK_URL;
}

/**
 * Função para obter URL de webhook dinâmica
 */
function getWebhookUrl() {
    return ML_WEBHOOK_URL;
}

/**
 * Função para verificar se está em localhost
 */
function isLocalEnvironment() {
    $host = $_SERVER['HTTP_HOST'];
    return in_array($host, ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8080']);
}

/**
 * Função para verificar se está usando ngrok
 */
function isNgrokEnvironment() {
    $host = $_SERVER['HTTP_HOST'];
    return strpos($host, 'ngrok') !== false || strpos($host, 'ngrok-free.app') !== false;
}

/**
 * Função para obter configuração do ambiente
 */
function getEnvironmentConfig() {
    $environment = 'production';
    if (isNgrokEnvironment()) {
        $environment = 'ngrok';
    } elseif (isLocalEnvironment()) {
        $environment = 'localhost';
    }

    return [
        'is_local' => isLocalEnvironment(),
        'is_ngrok' => isNgrokEnvironment(),
        'environment' => $environment,
        'callback_url' => getCallbackUrl(),
        'webhook_url' => getWebhookUrl(),
        'base_url' => ML_BASE_URL,
        'host' => $_SERVER['HTTP_HOST'],
        'protocol' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http'
    ];
}
?>
