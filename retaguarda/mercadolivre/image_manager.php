<?php
/**
 * Gerenciador de Imagens Otimizado para Mercado Livre
 * Sistema eficiente que lista arquivos da pasta de uma vez
 */

class MLImageManager {

    private $uploadDir;
    private $baseUrl;
    private $supportedExtensions;

    public function __construct() {
        $this->uploadDir = '../../upload/'; // ✅ CORRIGIDO: Dois níveis acima

        // URL base dinâmica - usar ngrok para ML acessar
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        // Se estiver usando ngrok, usar URL do ngrok
        if (strpos($host, 'ngrok') !== false) {
            $this->baseUrl = $protocol . '://' . $host . '/upload/';
        } else {
            // Localhost - usar ngrok configurado
            require_once 'ml_config.php';
            $this->baseUrl = ML_DOMAIN . '/upload/';
        }

        $this->supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }
    
    /**
     * Busca imagens de forma SUPER otimizada
     * Lista todos os arquivos da pasta de uma vez e filtra por GTIN
     */
    public function findProductImages($codigoGtin) {
        if (empty($codigoGtin)) {
            return [];
        }

        // Verificar se pasta existe
        if (!is_dir($this->uploadDir)) {
            return [];
        }

        $images = [];

        // ✅ OTIMIZAÇÃO: Listar todos os arquivos de uma vez
        $allFiles = scandir($this->uploadDir);

        foreach ($allFiles as $filename) {
            // Pular diretórios
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            // ✅ OTIMIZAÇÃO: Verificar se arquivo corresponde ao GTIN
            $imageData = $this->parseImageFilename($filename, $codigoGtin);

            if ($imageData) {
                $filepath = $this->uploadDir . $filename;

                $images[] = [
                    'position' => $imageData['position'],
                    'filename' => $filename,
                    'url' => $this->baseUrl . $filename,
                    'size' => filesize($filepath),
                    'modified' => filemtime($filepath),
                    'extension' => $imageData['extension'],
                    'is_main' => $imageData['position'] === 1
                ];
            }
        }

        // Ordenar por posição
        usort($images, function($a, $b) {
            return $a['position'] - $b['position'];
        });

        return $images;
    }
    
    /**
     * ✅ NOVA FUNÇÃO: Analisa nome do arquivo e extrai informações
     * Verifica se arquivo corresponde ao GTIN e extrai posição/extensão
     */
    private function parseImageFilename($filename, $codigoGtin) {
        // Verificar extensão suportada
        $pathInfo = pathinfo($filename);
        $extension = strtolower($pathInfo['extension'] ?? '');

        if (!in_array($extension, $this->supportedExtensions)) {
            return null;
        }

        $basename = $pathInfo['filename']; // Nome sem extensão

        // Padrão 1: Imagem principal - codigo.ext (ex: 123.jpg)
        if ($basename === $codigoGtin) {
            return [
                'position' => 1,
                'extension' => $extension
            ];
        }

        // Padrão 2: Imagens secundárias - codigo_N.ext (ex: 123_2.jpg)
        $pattern = '/^' . preg_quote($codigoGtin, '/') . '_(\d+)$/';
        if (preg_match($pattern, $basename, $matches)) {
            $position = intval($matches[1]);

            // Validar posição (2-10)
            if ($position >= 2 && $position <= 10) {
                return [
                    'position' => $position,
                    'extension' => $extension
                ];
            }
        }

        return null;
    }
    
    // ✅ Função removida - não precisamos mais gerar nomes, só analisar existentes
    
    /**
     * Prepara imagens para o formato do Mercado Livre
     */
    public function prepareImagesForML($codigoGtin) {
        error_log("DEBUG prepareImagesForML: Iniciando para GTIN '$codigoGtin'");

        $images = $this->findProductImages($codigoGtin);
        error_log("DEBUG prepareImagesForML: Encontradas " . count($images) . " imagens");

        if (empty($images)) {
            error_log("DEBUG prepareImagesForML: Nenhuma imagem encontrada, retornando array vazio");
            return [];
        }

        // Formato do ML: array de objetos com 'source'
        $mlImages = [];
        foreach ($images as $image) {
            $mlImages[] = [
                'source' => $image['url']
            ];
            error_log("DEBUG prepareImagesForML: Adicionada imagem: " . $image['url']);
        }

        error_log("DEBUG prepareImagesForML: Retornando " . count($mlImages) . " imagens para ML");
        return $mlImages;
    }
    
    /**
     * Verifica se produto tem pelo menos uma imagem
     */
    public function hasImages($codigoGtin) {
        $images = $this->findProductImages($codigoGtin);
        return count($images) > 0;
    }
    
    // ✅ Funções de cache removidas - não vamos usar banco para imagens
    
    /**
     * Estatísticas de imagens
     */
    public function getImageStats($codigoGtin) {
        $images = $this->findProductImages($codigoGtin);

        return [
            'total' => count($images),
            'has_main' => count(array_filter($images, function($img) { return $img['is_main']; })) > 0,
            'extensions' => array_unique(array_column($images, 'extension')),
            'total_size' => array_sum(array_column($images, 'size')),
            'urls' => array_column($images, 'url')
        ];
    }
}

// Função helper
function getMLImageManager() {
    return new MLImageManager();
}
?>
