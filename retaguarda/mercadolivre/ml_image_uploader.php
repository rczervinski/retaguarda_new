<?php
/**
 * Upload Direto de Imagens para o CDN do Mercado Livre
 * Melhora performance e confiabilidade
 */

class MLImageUploader {
    
    private $accessToken;
    private $uploadEndpoint = 'https://api.mercadolibre.com/pictures/items/upload';
    
    public function __construct($accessToken = null) {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Faz upload de uma imagem diretamente para o CDN do ML
     */
    public function uploadImage($imagePath) {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Arquivo não encontrado'];
        }
        
        if (empty($this->accessToken)) {
            return ['success' => false, 'error' => 'Access token não configurado'];
        }
        
        // Validar imagem
        require_once __DIR__ . '/image_converter.php';
        $converter = getMLImageConverter();
        $validation = $converter->validateImageForML($imagePath);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        try {
            // Preparar dados para upload
            $postData = [
                'file' => new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath))
            ];
            
            // Configurar cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->uploadEndpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("ML_UPLOAD: Erro cURL: $error");
                return ['success' => false, 'error' => "Erro de conexão: $error"];
            }
            
            if ($httpCode !== 200) {
                error_log("ML_UPLOAD: HTTP $httpCode - Response: $response");
                return ['success' => false, 'error' => "Erro HTTP $httpCode"];
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("ML_UPLOAD: Erro JSON: " . json_last_error_msg());
                return ['success' => false, 'error' => 'Resposta inválida do ML'];
            }
            
            if (isset($data['id'])) {
                error_log("ML_UPLOAD: Upload bem-sucedido - ID: " . $data['id']);
                return [
                    'success' => true,
                    'picture_id' => $data['id'],
                    'variations' => $data['variations'] ?? []
                ];
            } else {
                error_log("ML_UPLOAD: Resposta sem ID: $response");
                return ['success' => false, 'error' => 'Resposta inesperada do ML'];
            }
            
        } catch (Exception $e) {
            error_log("ML_UPLOAD: Exceção: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Faz upload de múltiplas imagens de um GTIN
     */
    public function uploadGtinImages($codigoGtin) {
        require_once __DIR__ . '/image_converter.php';
        $converter = getMLImageConverter();
        
        // Converter imagens WEBP primeiro
        $convertedImages = $converter->convertGtinImages($codigoGtin);
        
        $uploadedImages = [];
        $errors = [];
        
        foreach ($convertedImages as $imagePath) {
            $result = $this->uploadImage($imagePath);
            
            if ($result['success']) {
                $uploadedImages[] = [
                    'picture_id' => $result['picture_id'],
                    'variations' => $result['variations'],
                    'original_path' => $imagePath
                ];
            } else {
                $errors[] = [
                    'path' => $imagePath,
                    'error' => $result['error']
                ];
            }
        }
        
        return [
            'uploaded' => $uploadedImages,
            'errors' => $errors,
            'total_uploaded' => count($uploadedImages),
            'total_errors' => count($errors)
        ];
    }
    
    /**
     * Diagnóstica uma imagem antes do upload
     */
    public function diagnoseImage($imagePath, $categoryId, $pictureType = 'thumbnail') {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Arquivo não encontrado'];
        }
        
        if (empty($this->accessToken)) {
            return ['success' => false, 'error' => 'Access token não configurado'];
        }
        
        try {
            // Converter imagem para base64
            $imageData = file_get_contents($imagePath);
            $base64 = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);
            $dataUri = "data:$mimeType;base64,$base64";
            
            $postData = json_encode([
                'picture_url' => $dataUri,
                'context' => [
                    'category_id' => $categoryId,
                    'picture_type' => $pictureType
                ]
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.mercadolibre.com/moderations/pictures/diagnostic',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'error' => "Erro de conexão: $error"];
            }
            
            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "Erro HTTP $httpCode"];
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'error' => 'Resposta inválida do ML'];
            }
            
            return ['success' => true, 'diagnostic' => $data];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Função helper para obter instância do uploader
 */
function getMLImageUploader($accessToken = null) {
    return new MLImageUploader($accessToken);
}
?>
