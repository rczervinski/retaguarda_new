<?php
/**
 * Webhook Receiver - Mercado Livre
 * 
 * Este arquivo recebe todas as notificações do Mercado Livre
 * URL: https://demo.gutty.app.br/retaguarda/mercadolivre/webhook/receiver.php
 */

// Desabilitar exibição de erros para não interferir na resposta
error_reporting(0);
ini_set('display_errors', 0);

// Incluir dependências
require_once '../../conexao.php';
require_once 'processor.php';
require_once 'validator.php';

// Função para log específico do webhook
function logWebhook($level, $message, $data = []) {
    $logFile = __DIR__ . '/../logs/webhook_' . date('Y-m-d') . '.log';
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
    // Log da requisição recebida
    logWebhook('INFO', 'Webhook recebido', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'headers' => getallheaders()
    ]);
    
    // Verificar se é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logWebhook('WARNING', 'Método não permitido: ' . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        echo "Method Not Allowed";
        exit;
    }
    
    // Obter dados da requisição
    $input = file_get_contents('php://input');
    $notification = json_decode($input, true);
    
    // Log dos dados recebidos
    logWebhook('DEBUG', 'Dados recebidos', [
        'raw_input' => $input,
        'parsed_notification' => $notification,
        'json_error' => json_last_error_msg()
    ]);
    
    // Verificar se o JSON é válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWebhook('ERROR', 'JSON inválido: ' . json_last_error_msg());
        http_response_code(400);
        echo "Invalid JSON";
        exit;
    }
    
    // Verificar estrutura básica da notificação
    if (!isset($notification['resource']) || !isset($notification['topic'])) {
        logWebhook('ERROR', 'Estrutura de notificação inválida', $notification);
        http_response_code(400);
        echo "Invalid notification structure";
        exit;
    }
    
    // Validar se a notificação veio realmente do ML
    if (!WebhookValidator::validateNotification($notification, $input)) {
        logWebhook('WARNING', 'Notificação não validada - possível tentativa de fraude', $notification);
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
    
    logWebhook('INFO', 'Notificação validada com sucesso', [
        'topic' => $notification['topic'],
        'resource' => $notification['resource']
    ]);
    
    // Processar a notificação baseado no tópico
    $processor = new WebhookProcessor();
    $result = $processor->process($notification);
    
    if ($result['success']) {
        logWebhook('INFO', 'Notificação processada com sucesso', [
            'topic' => $notification['topic'],
            'message' => $result['message']
        ]);
    } else {
        logWebhook('ERROR', 'Erro ao processar notificação', [
            'topic' => $notification['topic'],
            'error' => $result['error']
        ]);
    }
    
    // Sempre responder 200 OK para o ML (mesmo em caso de erro interno)
    http_response_code(200);
    echo "OK";
    
} catch (Exception $e) {
    // Log do erro
    logWebhook('ERROR', 'Exceção no webhook: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Sempre responder 200 OK para não perder notificações
    http_response_code(200);
    echo "OK";
}
?>
