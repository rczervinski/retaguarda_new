<?php
/**
 * Conversor de Imagens para Mercado Livre
 * Converte WEBP para JPEG e otimiza para ML
 */

class MLImageConverter {
    
    private $uploadDir;
    private $convertedDir;
    private $maxFileSize = 10485760; // 10MB
    private $targetWidth = 1200;
    private $targetHeight = 1200;
    private $jpegQuality = 90;
    
    public function __construct() {
        $this->uploadDir = '../../upload/';
        // ✅ SIMPLIFICADO: Não usar pasta separada, converter na mesma pasta
        $this->convertedDir = $this->uploadDir;
    }
    
    /**
     * Converte uma imagem WEBP para JPEG otimizado para ML
     */
    public function convertWebpToJpeg($webpPath, $outputPath = null) {
        if (!file_exists($webpPath)) {
            error_log("CONVERTER: Arquivo não encontrado: $webpPath");
            return false;
        }
        
        // Verificar se é WEBP
        $imageInfo = getimagesize($webpPath);
        if ($imageInfo === false) {
            error_log("CONVERTER: Não é uma imagem válida: $webpPath");
            return false;
        }
        
        // Gerar nome de saída se não fornecido
        if ($outputPath === null) {
            $filename = pathinfo($webpPath, PATHINFO_FILENAME);
            $outputPath = $this->uploadDir . $filename . '.jpg';
        }
        
        try {
            // Carregar imagem WEBP
            $image = imagecreatefromwebp($webpPath);
            if ($image === false) {
                error_log("CONVERTER: Erro ao carregar WEBP: $webpPath");
                return false;
            }
            
            // Obter dimensões originais
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            
            // Calcular novas dimensões mantendo proporção
            $newDimensions = $this->calculateOptimalSize($originalWidth, $originalHeight);
            
            // Criar nova imagem redimensionada
            $resizedImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
            
            // Fundo branco (recomendado pelo ML)
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);
            
            // Redimensionar com alta qualidade
            imagecopyresampled(
                $resizedImage, $image,
                0, 0, 0, 0,
                $newDimensions['width'], $newDimensions['height'],
                $originalWidth, $originalHeight
            );
            
            // Salvar como JPEG
            $success = imagejpeg($resizedImage, $outputPath, $this->jpegQuality);
            
            // Limpar memória
            imagedestroy($image);
            imagedestroy($resizedImage);
            
            if ($success) {
                error_log("CONVERTER: Convertido com sucesso: $webpPath -> $outputPath");
                return $outputPath;
            } else {
                error_log("CONVERTER: Erro ao salvar JPEG: $outputPath");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CONVERTER: Exceção ao converter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula tamanho ótimo mantendo proporção
     */
    private function calculateOptimalSize($originalWidth, $originalHeight) {
        // Se já está no tamanho ideal
        if ($originalWidth <= $this->targetWidth && $originalHeight <= $this->targetHeight) {
            return ['width' => $originalWidth, 'height' => $originalHeight];
        }
        
        // Calcular proporção
        $ratio = min(
            $this->targetWidth / $originalWidth,
            $this->targetHeight / $originalHeight
        );
        
        return [
            'width' => round($originalWidth * $ratio),
            'height' => round($originalHeight * $ratio)
        ];
    }
    
    /**
     * Converte todas as imagens WEBP de um GTIN para JPEG
     */
    public function convertGtinImages($codigoGtin) {
        $convertedImages = [];
        
        // Buscar todas as imagens WEBP do GTIN
        $webpFiles = glob($this->uploadDir . $codigoGtin . '*.webp');
        
        foreach ($webpFiles as $webpFile) {
            $filename = pathinfo($webpFile, PATHINFO_FILENAME);
            $jpegPath = $this->uploadDir . $filename . '.jpg';

            // Verificar se já foi convertido e está atualizado
            if (file_exists($jpegPath) && filemtime($jpegPath) >= filemtime($webpFile)) {
                error_log("CONVERTER: Já convertido: $jpegPath");
                $convertedImages[] = $jpegPath;
                continue;
            }

            // Converter
            error_log("CONVERTER: Convertendo $webpFile para $jpegPath");
            $result = $this->convertWebpToJpeg($webpFile, $jpegPath);
            if ($result) {
                $convertedImages[] = $result;
                error_log("CONVERTER: Conversão bem-sucedida: $result");
            } else {
                error_log("CONVERTER: Falha na conversão de $webpFile");
            }
        }
        
        return $convertedImages;
    }
    
    /**
     * Valida se imagem atende aos requisitos do ML
     */
    public function validateImageForML($imagePath) {
        if (!file_exists($imagePath)) {
            return ['valid' => false, 'error' => 'Arquivo não encontrado'];
        }
        
        // Verificar tamanho do arquivo
        $fileSize = filesize($imagePath);
        if ($fileSize > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'Arquivo muito grande (máx 10MB)'];
        }
        
        // Verificar se é imagem
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Não é uma imagem válida'];
        }
        
        // Verificar formato
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            return ['valid' => false, 'error' => 'Formato não suportado (use JPG ou PNG)'];
        }
        
        // Verificar dimensões mínimas
        if ($imageInfo[0] < 500 || $imageInfo[1] < 500) {
            return ['valid' => false, 'error' => 'Dimensões muito pequenas (mín 500x500px)'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Obtém URL pública da imagem convertida
     */
    public function getConvertedImageUrl($codigoGtin) {
        $jpegPath = $this->uploadDir . $codigoGtin . '.jpg';

        if (file_exists($jpegPath)) {
            // URL base dinâmica
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];

            if (strpos($host, 'ngrok') !== false) {
                return $protocol . '://' . $host . '/upload/' . $codigoGtin . '.jpg';
            } else {
                require_once 'ml_config.php';
                return ML_DOMAIN . '/upload/' . $codigoGtin . '.jpg';
            }
        }

        return null;
    }
    
    /**
     * Limpa arquivos convertidos antigos
     */
    public function cleanOldConvertedFiles($daysOld = 7) {
        $files = glob($this->convertedDir . '*.jpg');
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                error_log("CONVERTER: Removido arquivo antigo: $file");
            }
        }
    }
}

/**
 * Função helper para obter instância do conversor
 */
function getMLImageConverter() {
    return new MLImageConverter();
}
?>
