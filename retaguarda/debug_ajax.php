<?php
/**
 * Debug AJAX - Arquivo para capturar logs de debug
 * Este arquivo recebe requisições AJAX para logging e debugging
 */

// Configurar cabeçalhos para AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função para escrever logs
function writeDebugLog($message, $data = null) {
    $logFile = 'debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    $logEntry .= "\n";
    
    // Escrever no arquivo de log
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Obter dados da requisição
    $action = $_POST['action'] ?? '';
    $page = $_POST['page'] ?? '';
    $message = $_POST['message'] ?? '';
    $timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Processar diferentes tipos de ações
    switch ($action) {
        case 'load_complete':
            writeDebugLog("Página carregada: $page", [
                'timestamp' => $timestamp,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Log registrado com sucesso'
            ]);
            break;
            
        case 'log_connection_test':
            writeDebugLog("Teste de conexão iniciado", [
                'timestamp' => $timestamp,
                'message' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Teste de conexão logado'
            ]);
            break;
            
        case 'error_log':
            $error = $_POST['error'] ?? '';
            $stack = $_POST['stack'] ?? '';
            
            writeDebugLog("Erro JavaScript: $error", [
                'timestamp' => $timestamp,
                'stack' => $stack,
                'page' => $page,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Erro logado'
            ]);
            break;
            
        case 'general_log':
            writeDebugLog($message, [
                'timestamp' => $timestamp,
                'page' => $page,
                'additional_data' => $_POST['data'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Log geral registrado'
            ]);
            break;
            
        default:
            // Log genérico para ações não reconhecidas
            writeDebugLog("Ação de debug não reconhecida: $action", $_POST);
            
            echo json_encode([
                'success' => true,
                'message' => 'Debug registrado (ação genérica)',
                'action' => $action
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log do erro
    writeDebugLog("Erro no debug_ajax.php: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'post_data' => $_POST
    ]);
    
    // Resposta de erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
?>
