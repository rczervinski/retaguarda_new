<?php
/**
 * Gerenciador de Tokens do Mercado Livre
 * 
 * Responsável por:
 * - Verificar se token está válido
 * - Renovar automaticamente quando necessário
 * - Gerenciar refresh tokens
 */

require_once '../conexao.php';
require_once 'ml_config.php';

class MLTokenManager {
    
    private $conexao;
    
    public function __construct($conexao) {
        $this->conexao = $conexao;
    }
    
    /**
     * Obtém token válido (renova automaticamente se necessário)
     */
    public function getValidToken() {
        $tokenData = $this->getCurrentTokenData();
        
        if (!$tokenData) {
            return ['error' => 'Nenhuma configuração ativa encontrada'];
        }
        
        // Verificar se token está válido
        if ($this->isTokenValid($tokenData)) {
            return ['success' => true, 'token' => $tokenData['access_token']];
        }
        
        // Token expirado, tentar renovar
        $refreshResult = $this->refreshToken($tokenData);
        
        if ($refreshResult['success']) {
            return ['success' => true, 'token' => $refreshResult['new_token']];
        }
        
        return $refreshResult; // Retorna erro
    }
    
    /**
     * Busca dados do token atual no banco
     */
    private function getCurrentTokenData() {
        $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE' AND ativo = 1 LIMIT 1";
        $result = pg_query($this->conexao, $query);
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
    
    /**
     * Verifica se token está válido
     */
    private function isTokenValid($tokenData) {
        if (empty($tokenData['access_token'])) {
            return false;
        }
        
        $tokenAge = time() - intval($tokenData['token_created_at'] ?? 0);
        $expiresIn = intval($tokenData['expires_in'] ?? 21600); // 6 horas padrão
        
        // Token válido se ainda não expirou (com margem de 5 minutos)
        return $tokenAge < ($expiresIn - 300);
    }
    
    /**
     * Renova o access token usando refresh token
     */
    private function refreshToken($tokenData) {
        $refreshToken = $tokenData['code']; // refresh_token está no campo 'code'
        
        if (empty($refreshToken)) {
            return ['error' => 'Refresh token não encontrado'];
        }
        
        // Dados para renovação
        $postData = [
            'grant_type' => 'refresh_token',
            'client_id' => $tokenData['client_id'],
            'client_secret' => $tokenData['client_secret'],
            'refresh_token' => $refreshToken
        ];
        
        // Fazer requisição para renovar token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => ML_TOKEN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->logTokenAction('ERROR', 'Erro cURL na renovação: ' . $curlError);
            return ['error' => 'Erro de conexão: ' . $curlError];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200 || !isset($responseData['access_token'])) {
            $error = $responseData['error'] ?? 'Erro desconhecido';
            $errorDescription = $responseData['error_description'] ?? 'Não foi possível renovar token';
            
            $this->logTokenAction('ERROR', "Erro na renovação: {$error} - {$errorDescription}");
            
            // Se refresh token expirou, usuário precisa re-autenticar
            if ($error === 'invalid_grant') {
                return ['error' => 'Refresh token expirado. É necessário re-autenticar.', 'need_reauth' => true];
            }
            
            return ['error' => "Erro na renovação: {$errorDescription}"];
        }
        
        // Sucesso! Salvar novos tokens
        $newAccessToken = $responseData['access_token'];
        $newRefreshToken = $responseData['refresh_token'] ?? $refreshToken; // Usar o antigo se não vier novo
        $expiresIn = $responseData['expires_in'] ?? 21600;
        $tokenCreatedAt = time();
        
        // Atualizar no banco
        $query = "UPDATE token_integracao SET 
                    access_token = '$newAccessToken',
                    code = '$newRefreshToken',
                    refresh_token = '$newRefreshToken',
                    expires_in = $expiresIn,
                    token_created_at = $tokenCreatedAt
                  WHERE descricao = 'MERCADO_LIVRE' AND ativo = 1";
        
        $updateResult = pg_query($this->conexao, $query);
        
        if (!$updateResult) {
            $this->logTokenAction('ERROR', 'Erro ao salvar token renovado: ' . pg_last_error($this->conexao));
            return ['error' => 'Erro ao salvar token renovado'];
        }
        
        $this->logTokenAction('INFO', 'Token renovado com sucesso');
        
        return [
            'success' => true,
            'new_token' => $newAccessToken,
            'expires_in' => $expiresIn
        ];
    }
    
    /**
     * Faz requisição à API do ML com token válido
     */
    public function makeMLRequest($url, $method = 'GET', $data = null) {
        $tokenResult = $this->getValidToken();
        
        if (!$tokenResult['success']) {
            return $tokenResult;
        }
        
        $accessToken = $tokenResult['token'];
        
        // Fazer requisição
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $accessToken",
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);
        
        // Configurar método
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ['error' => 'Erro de conexão: ' . $curlError];
        }
        
        $responseData = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $responseData ?: $response,
            'raw_response' => $response
        ];
    }
    
    /**
     * Log de ações do token
     */
    private function logTokenAction($level, $message) {
        $logFile = __DIR__ . '/logs/token_manager_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verifica status do token atual
     */
    public function getTokenStatus() {
        $tokenData = $this->getCurrentTokenData();
        
        if (!$tokenData) {
            return ['status' => 'not_configured', 'message' => 'Token não configurado'];
        }
        
        if ($this->isTokenValid($tokenData)) {
            $timeLeft = intval($tokenData['expires_in']) - (time() - intval($tokenData['token_created_at']));
            return [
                'status' => 'valid',
                'message' => 'Token válido',
                'expires_in_seconds' => $timeLeft,
                'expires_in_minutes' => round($timeLeft / 60)
            ];
        }
        
        return ['status' => 'expired', 'message' => 'Token expirado'];
    }
}

// Função helper para uso fácil
function getMLTokenManager() {
    global $conexao;
    return new MLTokenManager($conexao);
}
?>
