<?php
/**
 * OAuth2 Callback - Mercado Livre
 * 
 * Este arquivo recebe o callback do processo de autenticação OAuth2
 * URL: https://demo.gutty.app.br/retaguarda/mercadolivre/auth/callback.php
 */

// Incluir dependências
require_once '../../conexao.php';
require_once '../ml_config.php';

// Função para log específico do OAuth
function logOAuth($level, $message, $data = []) {
    $logFile = __DIR__ . '/../logs/oauth_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $dataStr = !empty($data) ? ' | Data: ' . json_encode($data) : '';
    $logLine = "[{$timestamp}] [{$level}] {$message}{$dataStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

try {
    logOAuth('INFO', 'Callback OAuth recebido', $_GET);
    
    // Verificar se há código de autorização
    if (!isset($_GET['code'])) {
        // Verificar se há erro
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
            $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
            
            logOAuth('ERROR', "Erro OAuth: {$error} - {$errorDescription}");
            
            // Redirecionar para página de erro
            header('Location: ../admin/index.php?error=oauth_error&message=' . urlencode($errorDescription));
            exit;
        }
        
        logOAuth('ERROR', 'Código de autorização não recebido');
        header('Location: ../admin/index.php?error=no_code');
        exit;
    }
    
    $authCode = $_GET['code'];
    $state = $_GET['state'] ?? '';
    
    logOAuth('INFO', 'Código de autorização recebido', [
        'code' => substr($authCode, 0, 10) . '...',
        'state' => $state
    ]);
    
    // Buscar configurações da aplicação na tabela token_integracao
    $query = "SELECT client_id, client_secret FROM token_integracao WHERE descricao = 'MERCADO_LIVRE' LIMIT 1";
    $result = pg_query($conexao, $query);

    if (!$result || pg_num_rows($result) === 0) {
        logOAuth('ERROR', 'Configurações da aplicação não encontradas');
        header('Location: ../../integracao_mercadolivre.php?error=no_config');
        exit;
    }

    $config = pg_fetch_assoc($result);

    // URL de redirecionamento (usar a mesma configurada no sistema)
    $redirect_uri = getMLCallbackUrl();
    
    // Trocar código por token
    $tokenData = [
        'grant_type' => 'authorization_code',
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $authCode,
        'redirect_uri' => $redirect_uri
    ];

    logOAuth('DEBUG', 'Solicitando token de acesso', [
        'redirect_uri_used' => $redirect_uri,
        'client_id' => $config['client_id']
    ]);
    
    // Fazer requisição para obter token
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadolibre.com/oauth/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($tokenData),
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
        logOAuth('ERROR', 'Erro cURL ao solicitar token: ' . $curlError);
        header('Location: ../admin/index.php?error=curl_error');
        exit;
    }
    
    $tokenResponse = json_decode($response, true);
    
    logOAuth('DEBUG', 'Resposta da API de token', [
        'http_code' => $httpCode,
        'response' => $tokenResponse
    ]);
    
    if ($httpCode !== 200 || !isset($tokenResponse['access_token'])) {
        $error = $tokenResponse['error'] ?? 'Erro desconhecido';
        $errorDescription = $tokenResponse['error_description'] ?? 'Não foi possível obter token';

        logOAuth('ERROR', "Erro ao obter token: {$error} - {$errorDescription}");
        header('Location: ../../integracao_mercadolivre.php?error=token_error&message=' . urlencode($errorDescription));
        exit;
    }
    
    // Token obtido com sucesso
    $accessToken = $tokenResponse['access_token'];
    $refreshToken = $tokenResponse['refresh_token'] ?? '';
    $expiresIn = $tokenResponse['expires_in'] ?? 21600; // 6 horas padrão
    $userId = $tokenResponse['user_id'] ?? '';
    
    logOAuth('INFO', 'Token obtido com sucesso', [
        'user_id' => $userId,
        'expires_in' => $expiresIn,
        'has_refresh_token' => !empty($refreshToken)
    ]);
    
    // Salvar token no banco de dados (tabela token_integracao)
    $tokenCreatedAt = time();

    $query = "UPDATE token_integracao SET
                access_token = '$accessToken',
                code = '$refreshToken',
                refresh_token = '$refreshToken',
                url_checkout = '$userId',
                user_id = '$userId',
                expires_in = $expiresIn,
                token_created_at = $tokenCreatedAt,
                ativo = 1
              WHERE descricao = 'MERCADO_LIVRE'";

    $updateResult = pg_query($conexao, $query);

    if (!$updateResult) {
        logOAuth('ERROR', 'Erro ao salvar token no banco: ' . pg_last_error($conexao));
        header('Location: ../../integracao_mercadolivre.php?error=db_error');
        exit;
    }
    
    logOAuth('INFO', 'Token salvo no banco com sucesso');
    
    // Testar o token fazendo uma requisição para obter dados do usuário
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.mercadolibre.com/users/{$userId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$accessToken}",
            'Accept: application/json'
        ]
    ]);
    
    $userResponse = curl_exec($ch);
    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($userHttpCode === 200) {
        $userData = json_decode($userResponse, true);
        logOAuth('INFO', 'Token validado com sucesso', [
            'user_nickname' => $userData['nickname'] ?? 'N/A',
            'user_email' => $userData['email'] ?? 'N/A'
        ]);
        
        // Log dos dados do usuário (não salvamos na tabela token_integracao por enquanto)
        logOAuth('INFO', 'Dados do usuário obtidos', [
            'nickname' => $userData['nickname'] ?? '',
            'email' => $userData['email'] ?? ''
        ]);
    } else {
        logOAuth('WARNING', 'Não foi possível validar o token obtido');
    }
    
    // Redirecionar para página de sucesso
    header('Location: ../../integracao_mercadolivre.php?success=oauth_success');
    exit;
    
} catch (Exception $e) {
    logOAuth('ERROR', 'Exceção no callback OAuth: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    header('Location: ../../integracao_mercadolivre.php?error=exception&message=' . urlencode($e->getMessage()));
    exit;
}
?>
