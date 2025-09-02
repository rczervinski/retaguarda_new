<?php
/**
 * Webhook Processor - Mercado Livre
 * 
 * Processa cada tipo de notificação recebida do ML
 */

require_once '../../conexao.php';

class WebhookProcessor {
    
    private $conexao;
    
    public function __construct() {
        global $conexao;
        $this->conexao = $conexao;
    }
    
    /**
     * Processa a notificação baseado no tópico
     */
    public function process($notification) {
        try {
            $topic = $notification['topic'];
            $resource = $notification['resource'];
            
            // Log da notificação recebida
            $this->logNotification($notification);
            
            switch ($topic) {
                case 'orders':
                    return $this->processOrder($notification);
                    
                case 'items':
                    return $this->processItem($notification);
                    
                case 'questions':
                    return $this->processQuestion($notification);
                    
                case 'claims':
                    return $this->processClaim($notification);
                    
                case 'messages':
                    return $this->processMessage($notification);
                    
                case 'shipments':
                    return $this->processShipment($notification);
                    
                default:
                    return [
                        'success' => false,
                        'error' => "Tópico não suportado: {$topic}"
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Processa notificações de pedidos
     */
    private function processOrder($notification) {
        $resource = $notification['resource'];
        
        // Extrair ID do pedido da URL
        if (preg_match('/\/orders\/(\d+)/', $resource, $matches)) {
            $orderId = $matches[1];
            
            // Buscar detalhes do pedido na API do ML
            $orderData = $this->fetchOrderFromML($orderId);
            
            if ($orderData) {
                // Salvar/atualizar pedido no banco
                $this->saveOrder($orderData);
                
                return [
                    'success' => true,
                    'message' => "Pedido {$orderId} processado com sucesso"
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => "Não foi possível processar o pedido"
        ];
    }
    
    /**
     * Processa notificações de itens
     */
    private function processItem($notification) {
        $resource = $notification['resource'];
        
        // Extrair ID do item da URL
        if (preg_match('/\/items\/([A-Z0-9]+)/', $resource, $matches)) {
            $itemId = $matches[1];
            
            // Buscar detalhes do item na API do ML
            $itemData = $this->fetchItemFromML($itemId);
            
            if ($itemData) {
                // Atualizar item no banco local
                $this->updateLocalItem($itemData);
                
                return [
                    'success' => true,
                    'message' => "Item {$itemId} atualizado com sucesso"
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => "Não foi possível processar o item"
        ];
    }
    
    /**
     * Processa notificações de perguntas
     */
    private function processQuestion($notification) {
        $resource = $notification['resource'];
        
        // Extrair ID da pergunta da URL
        if (preg_match('/\/questions\/(\d+)/', $resource, $matches)) {
            $questionId = $matches[1];
            
            // Buscar detalhes da pergunta na API do ML
            $questionData = $this->fetchQuestionFromML($questionId);
            
            if ($questionData) {
                // Salvar pergunta no banco
                $this->saveQuestion($questionData);
                
                return [
                    'success' => true,
                    'message' => "Pergunta {$questionId} processada com sucesso"
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => "Não foi possível processar a pergunta"
        ];
    }
    
    /**
     * Processa notificações de reclamações
     */
    private function processClaim($notification) {
        // Implementar processamento de reclamações
        return [
            'success' => true,
            'message' => "Reclamação processada (implementação pendente)"
        ];
    }
    
    /**
     * Processa notificações de mensagens
     */
    private function processMessage($notification) {
        // Implementar processamento de mensagens
        return [
            'success' => true,
            'message' => "Mensagem processada (implementação pendente)"
        ];
    }
    
    /**
     * Processa notificações de envios
     */
    private function processShipment($notification) {
        // Implementar processamento de envios
        return [
            'success' => true,
            'message' => "Envio processado (implementação pendente)"
        ];
    }
    
    /**
     * Busca dados do pedido na API do ML
     */
    private function fetchOrderFromML($orderId) {
        // TODO: Implementar chamada à API do ML
        // Por enquanto, apenas simula
        return [
            'id' => $orderId,
            'status' => 'paid',
            'date_created' => date('c')
        ];
    }
    
    /**
     * Busca dados do item na API do ML
     */
    private function fetchItemFromML($itemId) {
        // TODO: Implementar chamada à API do ML
        return [
            'id' => $itemId,
            'status' => 'active'
        ];
    }
    
    /**
     * Busca dados da pergunta na API do ML
     */
    private function fetchQuestionFromML($questionId) {
        // TODO: Implementar chamada à API do ML
        return [
            'id' => $questionId,
            'status' => 'UNANSWERED'
        ];
    }
    
    /**
     * Salva pedido no banco de dados
     */
    private function saveOrder($orderData) {
        // TODO: Implementar salvamento no banco
        $this->logProcessor('INFO', 'Pedido salvo no banco', $orderData);
    }
    
    /**
     * Atualiza item local baseado nos dados do ML
     */
    private function updateLocalItem($itemData) {
        // TODO: Implementar atualização no banco
        $this->logProcessor('INFO', 'Item atualizado no banco', $itemData);
    }
    
    /**
     * Salva pergunta no banco de dados
     */
    private function saveQuestion($questionData) {
        // TODO: Implementar salvamento no banco
        $this->logProcessor('INFO', 'Pergunta salva no banco', $questionData);
    }
    
    /**
     * Log da notificação no banco
     */
    private function logNotification($notification) {
        try {
            $query = "INSERT INTO ml_notifications_log (
                topic, resource, application_id, attempts, sent, received, 
                raw_data, processed_at
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, NOW())";
            
            $params = [
                $notification['topic'],
                $notification['resource'],
                $notification['application_id'],
                $notification['attempts'],
                $notification['sent'],
                $notification['received'],
                json_encode($notification)
            ];
            
            pg_query_params($this->conexao, $query, $params);
            
        } catch (Exception $e) {
            $this->logProcessor('ERROR', 'Erro ao salvar log da notificação: ' . $e->getMessage());
        }
    }
    
    /**
     * Log específico do processor
     */
    private function logProcessor($level, $message, $data = []) {
        $logFile = __DIR__ . '/../logs/webhook_processor_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $dataStr = !empty($data) ? ' | Data: ' . json_encode($data) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$dataStr}" . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>
