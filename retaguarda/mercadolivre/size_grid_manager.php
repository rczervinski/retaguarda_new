<?php
/**
 * Gerenciador de SIZE_GRID_ID para Mercado Livre
 * Busca e mapeia grades de tamanho válidas na API do ML
 */

require_once 'token_manager.php';

class MLSizeGridManager {
    private $tokenManager;
    private $cache = [];
    
    public function __construct($tokenManager) {
        $this->tokenManager = $tokenManager;
    }
    
    /**
     * Busca SIZE_GRID_ID válido para uma categoria e conjunto de tamanhos
     */
    public function getSizeGridId($categoryId, $tamanhos) {
        // Normalizar tamanhos
        $tamanhos = array_map(function($t) {
            return strtoupper(trim($t));
        }, $tamanhos);
        sort($tamanhos);
        
        $cacheKey = $categoryId . '_' . implode('_', $tamanhos);
        
        // Verificar cache
        if (isset($this->cache[$cacheKey])) {
            error_log("SIZE_GRID: Cache hit para $cacheKey");
            return $this->cache[$cacheKey];
        }
        
        error_log("SIZE_GRID: Buscando grade para categoria $categoryId com tamanhos: " . implode(', ', $tamanhos));
        
        // Buscar grades disponíveis para a categoria
        $availableGrids = $this->fetchCategoryGrids($categoryId);
        
        if (empty($availableGrids)) {
            error_log("SIZE_GRID: Nenhuma grade disponível para categoria $categoryId");
            return null;
        }
        
        // Encontrar melhor match
        $bestMatch = $this->findBestSizeGrid($tamanhos, $availableGrids);
        
        if ($bestMatch) {
            error_log("SIZE_GRID: Melhor match encontrado: " . json_encode($bestMatch));
            $this->cache[$cacheKey] = $bestMatch;
            return $bestMatch;
        }
        
        error_log("SIZE_GRID: Nenhuma grade compatível encontrada");
        return null;
    }
    
    /**
     * Busca grades de tamanho disponíveis para uma categoria
     */
    private function fetchCategoryGrids($categoryId) {
        try {
            // Endpoint para buscar atributos da categoria
            $url = "https://api.mercadolibre.com/categories/$categoryId/attributes";
            $response = $this->tokenManager->makeMLRequest($url, 'GET');
            
            if (!$response['success'] || !isset($response['data'])) {
                error_log("SIZE_GRID: Erro ao buscar atributos da categoria $categoryId");
                return [];
            }
            
            // Procurar atributo SIZE_GRID_ID
            foreach ($response['data'] as $attr) {
                if ($attr['id'] === 'SIZE_GRID_ID') {
                    $values = $attr['values'] ?? [];
                    error_log("SIZE_GRID: Encontradas " . count($values) . " grades para categoria $categoryId");
                    return $values;
                }
            }
            
            error_log("SIZE_GRID: Atributo SIZE_GRID_ID não encontrado para categoria $categoryId");
            return [];
            
        } catch (Exception $e) {
            error_log("SIZE_GRID: Erro ao buscar grades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Encontra a melhor grade baseada nos tamanhos disponíveis
     */
    private function findBestSizeGrid($tamanhos, $availableGrids) {
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($availableGrids as $grid) {
            $gridName = strtoupper($grid['name'] ?? '');
            $gridId = $grid['id'] ?? null;
            
            if (!$gridId) continue;
            
            // Calcular score de compatibilidade
            $score = $this->calculateCompatibilityScore($tamanhos, $gridName);
            
            error_log("SIZE_GRID: Grid '$gridName' (ID: $gridId) - Score: $score");
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = [
                    'id' => $gridId,
                    'name' => $grid['name'],
                    'score' => $score
                ];
            }
        }
        
        // Só aceitar matches com score mínimo
        if ($bestScore < 0.3) {
            error_log("SIZE_GRID: Melhor score ($bestScore) muito baixo, rejeitando");
            return null;
        }
        
        return $bestMatch;
    }
    
    /**
     * Calcula score de compatibilidade entre tamanhos e grade
     */
    private function calculateCompatibilityScore($tamanhos, $gridName) {
        $score = 0;
        $totalTamanhos = count($tamanhos);
        
        if ($totalTamanhos === 0) return 0;
        
        // Mapeamento de tamanhos comuns
        $sizeMap = [
            'PP' => ['XS', 'EXTRA SMALL', 'MUITO PEQUENO'],
            'P' => ['S', 'SMALL', 'PEQUENO'],
            'M' => ['M', 'MEDIUM', 'MEDIO'],
            'G' => ['L', 'LARGE', 'GRANDE'],
            'GG' => ['XL', 'EXTRA LARGE', 'MUITO GRANDE'],
            'XGG' => ['XXL', '2XL'],
            'EGG' => ['XXXL', '3XL']
        ];
        
        foreach ($tamanhos as $tamanho) {
            // Match exato
            if (strpos($gridName, $tamanho) !== false) {
                $score += 1.0;
                continue;
            }
            
            // Match por mapeamento
            if (isset($sizeMap[$tamanho])) {
                foreach ($sizeMap[$tamanho] as $equiv) {
                    if (strpos($gridName, $equiv) !== false) {
                        $score += 0.8;
                        break;
                    }
                }
            }
            
            // Match parcial (números)
            if (is_numeric($tamanho) && strpos($gridName, $tamanho) !== false) {
                $score += 0.6;
            }
        }
        
        return $score / $totalTamanhos;
    }
    
    /**
     * Busca SIZE_GRID_ID padrão para categoria (fallback)
     */
    public function getDefaultSizeGridForCategory($categoryId) {
        $grids = $this->fetchCategoryGrids($categoryId);
        
        if (empty($grids)) {
            return null;
        }
        
        // Procurar por grades padrão comuns
        $preferredNames = ['STANDARD', 'BASIC', 'GENERAL', 'DEFAULT', 'COMUM'];
        
        foreach ($preferredNames as $preferred) {
            foreach ($grids as $grid) {
                $gridName = strtoupper($grid['name'] ?? '');
                if (strpos($gridName, $preferred) !== false) {
                    return [
                        'id' => $grid['id'],
                        'name' => $grid['name']
                    ];
                }
            }
        }
        
        // Se não encontrou padrão, usar o primeiro disponível
        if (!empty($grids)) {
            return [
                'id' => $grids[0]['id'],
                'name' => $grids[0]['name']
            ];
        }
        
        return null;
    }
}

/**
 * Factory function
 */
function getMLSizeGridManager() {
    static $instance = null;
    if ($instance === null) {
        $tokenManager = getMLTokenManager();
        $instance = new MLSizeGridManager($tokenManager);
    }
    return $instance;
}
?>