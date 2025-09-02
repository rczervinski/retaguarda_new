<?php
/**
 * Webhook Validator - Mercado Livre
 * 
 * Valida se as notificações realmente vieram do Mercado Livre
 */

class WebhookValidator {
    
    /**
     * IPs oficiais do Mercado Livre (podem mudar, então também validamos por outros meios)
     */
    private static $mlIpRanges = [
        '200.58.87.0/24',
        '200.58.86.0/24',
        '200.58.85.0/24',
        '200.58.84.0/24'
    ];
    
    /**
     * User-Agents conhecidos do ML
     */
    private static $mlUserAgents = [
        'MercadoLibre Notifications',
        'MercadoLibre',
        'ML-Notifications'
    ];
    
    /**
     * Valida se a notificação veio do Mercado Livre
     */
    public static function validateNotification($notification, $rawInput) {
        // Validação 1: Estrutura básica
        if (!self::validateStructure($notification)) {
            return false;
        }
        
        // Validação 2: IP de origem (se possível)
        if (!self::validateIP()) {
            // Log mas não bloqueia, pois IPs podem mudar
            self::logValidation('WARNING', 'IP não reconhecido: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
        
        // Validação 3: User-Agent
        if (!self::validateUserAgent()) {
            self::logValidation('WARNING', 'User-Agent não reconhecido: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        }
        
        // Validação 4: Headers esperados
        if (!self::validateHeaders()) {
            self::logValidation('WARNING', 'Headers suspeitos');
        }
        
        // Validação 5: Formato do resource
        if (!self::validateResourceFormat($notification['resource'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida estrutura básica da notificação
     */
    private static function validateStructure($notification) {
        $requiredFields = ['resource', 'topic', 'application_id', 'attempts', 'sent', 'received'];
        
        foreach ($requiredFields as $field) {
            if (!isset($notification[$field])) {
                self::logValidation('ERROR', "Campo obrigatório ausente: {$field}");
                return false;
            }
        }
        
        // Validar tópicos conhecidos
        $validTopics = ['orders', 'items', 'questions', 'claims', 'messages', 'shipments'];
        if (!in_array($notification['topic'], $validTopics)) {
            self::logValidation('ERROR', "Tópico inválido: " . $notification['topic']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida IP de origem
     */
    private static function validateIP() {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (empty($clientIP)) {
            return false;
        }
        
        // Verificar se está em algum range conhecido do ML
        foreach (self::$mlIpRanges as $range) {
            if (self::ipInRange($clientIP, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se IP está em um range
     */
    private static function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) === $subnet;
    }
    
    /**
     * Valida User-Agent
     */
    private static function validateUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        foreach (self::$mlUserAgents as $validUA) {
            if (strpos($userAgent, $validUA) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida headers esperados
     */
    private static function validateHeaders() {
        $headers = getallheaders();
        
        // Verificar Content-Type
        if (isset($headers['Content-Type']) && 
            strpos($headers['Content-Type'], 'application/json') === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida formato do resource
     */
    private static function validateResourceFormat($resource) {
        // Resource deve ser uma URL válida da API do ML
        if (!filter_var($resource, FILTER_VALIDATE_URL)) {
            self::logValidation('ERROR', "Resource não é uma URL válida: {$resource}");
            return false;
        }
        
        // Deve ser do domínio da API do ML
        $parsedUrl = parse_url($resource);
        if ($parsedUrl['host'] !== 'api.mercadolibre.com') {
            self::logValidation('ERROR', "Resource não é do domínio ML: {$resource}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Log específico para validações
     */
    private static function logValidation($level, $message) {
        $logFile = __DIR__ . '/../logs/webhook_validation_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>
