<?php
/**
 * Configuração Global do Mercado Livre
 * 
 * ALTERE APENAS AS URLs AQUI QUANDO MUDAR DE AMBIENTE
 */

// ==================== CONFIGURAÇÃO PRINCIPAL ====================

// ALTERE AQUI QUANDO MUDAR O AMBIENTE:
// Para ngrok: https://SEU_TUNEL.ngrok-free.app
// Para servidor: https://demo.gutty.app.br
// Para localhost: http://localhost (só para testes sem OAuth)

define('ML_DOMAIN', 'https://313ff4f054ae.ngrok-free.app');

// ==================== NÃO ALTERE DAQUI PARA BAIXO ====================

// URLs automáticas baseadas no domínio
define('ML_CALLBACK_URL_GLOBAL', ML_DOMAIN . '/retaguarda/mercadolivre/auth/callback.php');
define('ML_WEBHOOK_URL_GLOBAL', ML_DOMAIN . '/retaguarda/mercadolivre/webhook/receiver.php');
define('ML_BASE_URL_GLOBAL', ML_DOMAIN . '/retaguarda/');

// URLs da API do Mercado Livre (sempre as mesmas)
define('ML_API_BASE_URL', 'https://api.mercadolibre.com');
define('ML_AUTH_URL', 'https://auth.mercadolibre.com.br/authorization');
define('ML_TOKEN_URL', 'https://api.mercadolibre.com/oauth/token');
define('ML_SITE_ID', 'MLB'); // Brasil

/**
 * Função para obter URL de callback
 */
function getMLCallbackUrl() {
    return ML_CALLBACK_URL_GLOBAL;
}

/**
 * Função para obter URL de webhook
 */
function getMLWebhookUrl() {
    return ML_WEBHOOK_URL_GLOBAL;
}

/**
 * Função para obter URL base
 */
function getMLBaseUrl() {
    return ML_BASE_URL_GLOBAL;
}

/**
 * Função para obter domínio atual
 */
function getMLDomain() {
    return ML_DOMAIN;
}

/**
 * Função para verificar se token está válido
 */
function isMLTokenValid($tokenData) {
    if (empty($tokenData['access_token'])) {
        return false;
    }
    
    $tokenAge = time() - intval($tokenData['token_created_at'] ?? 0);
    $expiresIn = intval($tokenData['expires_in'] ?? 21600); // 6 horas padrão
    
    // Token válido se ainda não expirou (com margem de 5 minutos)
    return $tokenAge < ($expiresIn - 300);
}

/**
 * Função para obter configuração completa do ambiente
 */
function getMLEnvironmentConfig() {
    $domain = ML_DOMAIN;
    $environment = 'production';
    
    if (strpos($domain, 'ngrok') !== false) {
        $environment = 'ngrok';
    } elseif (strpos($domain, 'localhost') !== false) {
        $environment = 'localhost';
    }
    
    return [
        'environment' => $environment,
        'domain' => $domain,
        'callback_url' => getMLCallbackUrl(),
        'webhook_url' => getMLWebhookUrl(),
        'base_url' => getMLBaseUrl(),
        'is_ngrok' => $environment === 'ngrok',
        'is_local' => $environment === 'localhost',
        'is_production' => $environment === 'production'
    ];
}

/**
 * Função para exibir URLs para configurar no ML
 */
function showMLUrls() {
    echo "=== URLs PARA CONFIGURAR NO MERCADO LIVRE ===\n";
    echo "Redirect URI: " . getMLCallbackUrl() . "\n";
    echo "Notification URL: " . getMLWebhookUrl() . "\n";
    echo "Ambiente: " . getMLEnvironmentConfig()['environment'] . "\n";
    echo "===============================================\n";
}

// Auto-detectar se está sendo chamado diretamente para mostrar URLs
if (basename($_SERVER['PHP_SELF']) === 'ml_config.php') {
    header('Content-Type: text/plain; charset=utf-8');
    showMLUrls();
}
?>
