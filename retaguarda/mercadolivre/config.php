<?php
/**
 * Configurações básicas para Mercado Livre
 */

// Incluir conexão com banco
require_once '../conexao.php';

// URLs da API do Mercado Livre
define('ML_API_BASE_URL', 'https://api.mercadolibre.com');
define('ML_AUTH_URL', 'https://auth.mercadolibre.com.br/authorization');
define('ML_TOKEN_URL', 'https://api.mercadolibre.com/oauth/token');
define('ML_SITE_ID', 'MLB'); // Brasil

// URLs do sistema
define('ML_CALLBACK_URL', 'https://demo.gutty.app.br/retaguarda/mercadolivre/auth/callback.php');
define('ML_WEBHOOK_URL', 'https://demo.gutty.app.br/retaguarda/mercadolivre/webhook/receiver.php');

/**
 * Classe para gerenciar configurações do ML
 */
class MLConfig {
    
    private static $config = null;
    
    /**
     * Carrega configurações do banco
     */
    public static function load() {
        global $conexao;
        
        if (self::$config !== null) {
            return self::$config;
        }
        
        try {
            $query = "SELECT * FROM ml_configuracoes WHERE ativo = true LIMIT 1";
            $result = pg_query($conexao, $query);
            
            if ($result && pg_num_rows($result) > 0) {
                self::$config = pg_fetch_assoc($result);
            } else {
                self::$config = [];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao carregar configurações ML: " . $e->getMessage());
            self::$config = [];
        }
        
        return self::$config;
    }
    
    /**
     * Obtém valor de configuração
     */
    public static function get($key, $default = null) {
        $config = self::load();
        return $config[$key] ?? $default;
    }
    
    /**
     * Verifica se está configurado
     */
    public static function isConfigured() {
        $config = self::load();
        return !empty($config['client_id']) && !empty($config['client_secret']);
    }
    
    /**
     * Verifica se tem token válido
     */
    public static function hasValidToken() {
        $config = self::load();
        
        if (empty($config['access_token'])) {
            return false;
        }
        
        $tokenAge = time() - intval($config['token_created_at'] ?? 0);
        $expiresIn = intval($config['expires_in'] ?? 0);
        
        // Token válido se ainda não expirou (com margem de 5 minutos)
        return $tokenAge < ($expiresIn - 300);
    }
}
?>
