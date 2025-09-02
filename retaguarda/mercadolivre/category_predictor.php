<?php
/**
 * Preditor inteligente de categorias do Mercado Livre
 * Usa a API de domain_discovery para sugerir categoria baseada no título
 */

require_once '../conexao.php';
require_once 'token_manager.php';

class MLCategoryPredictor {
    
    private $tokenManager;
    
    public function __construct() {
        $this->tokenManager = getMLTokenManager();
    }
    
    /**
     * Prediz categoria e atributos baseado no título do produto
     */
    public function predictCategory($titulo, $limit = 3) {
        try {
            // Limpar e preparar título
            $titulo = trim($titulo);
            if (empty($titulo)) {
                throw new Exception('Título não pode estar vazio');
            }
            
            // Chamar API de predição
            $url = 'https://api.mercadolibre.com/sites/MLB/domain_discovery/search';
            $params = '?limit=' . $limit . '&q=' . urlencode($titulo);
            
            $response = $this->tokenManager->makeMLRequest($url . $params);
            
            if (!$response['success']) {
                throw new Exception('Erro ao consultar preditor: ' . json_encode($response));
            }
            
            $predictions = $response['data'];
            
            if (empty($predictions)) {
                // Se não encontrou predição, usar categoria padrão
                return $this->getDefaultCategory();
            }
            
            // Processar predições
            $processedPredictions = [];
            foreach ($predictions as $prediction) {
                $processedPredictions[] = [
                    'domain_id' => $prediction['domain_id'],
                    'domain_name' => $prediction['domain_name'],
                    'category_id' => $prediction['category_id'],
                    'category_name' => $prediction['category_name'],
                    'attributes' => $prediction['attributes'] ?? [],
                    'confidence' => $this->calculateConfidence($prediction, $titulo)
                ];
            }
            
            return [
                'success' => true,
                'predictions' => $processedPredictions,
                'best_prediction' => $processedPredictions[0], // Primeira é a melhor
                'original_title' => $titulo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback' => $this->getDefaultCategory()
            ];
        }
    }
    
    /**
     * Calcula confiança da predição (simples)
     */
    private function calculateConfidence($prediction, $titulo) {
        // Lógica simples: se o nome da categoria contém palavras do título
        $tituloWords = explode(' ', strtolower($titulo));
        $categoryWords = explode(' ', strtolower($prediction['category_name']));
        
        $matches = 0;
        foreach ($tituloWords as $word) {
            if (strlen($word) > 3) { // Palavras com mais de 3 caracteres
                foreach ($categoryWords as $catWord) {
                    if (strpos($catWord, $word) !== false) {
                        $matches++;
                        break;
                    }
                }
            }
        }
        
        return min(100, ($matches / max(1, count($tituloWords))) * 100);
    }
    
    /**
     * Categoria padrão para casos onde a predição falha
     */
    private function getDefaultCategory() {
        return [
            'domain_id' => 'MLB-OTHERS',
            'domain_name' => 'Outros',
            'category_id' => 'MLB1144', // Agro > Outros (categoria que funcionou)
            'category_name' => 'Agro > Outros',
            'attributes' => [
                [
                    'id' => 'BRAND',
                    'value_name' => 'Sem marca'
                ],
                [
                    'id' => 'MODEL', 
                    'value_name' => 'Modelo padrão'
                ]
            ],
            'confidence' => 50,
            'is_fallback' => true
        ];
    }
    
    /**
     * Prepara atributos para publicação no ML
     */
    public function prepareAttributes($prediction, $produto = null) {
        $attributes = [];
        
        // Adicionar atributos sugeridos pela predição
        if (!empty($prediction['attributes'])) {
            foreach ($prediction['attributes'] as $attr) {
                $attributes[] = [
                    'id' => $attr['id'],
                    'value_name' => $attr['value_name']
                ];
            }
        }
        
        // Não enviar SELLER_SKU/GTIN no nível do item para evitar duplicação com variações
        
        // Garantir atributos mínimos se não foram sugeridos
        $hasBrand = false;
        $hasModel = false;
        
        foreach ($attributes as $attr) {
            if ($attr['id'] === 'BRAND') $hasBrand = true;
            if ($attr['id'] === 'MODEL') $hasModel = true;
        }
        
        if (!$hasModel) {
            $attributes[] = [
                'id' => 'MODEL',
                'value_name' => 'Modelo padrão'
            ];
        }
        
        return $attributes;
    }
    
    /**
     * Salva predição no banco para cache/histórico
     */
    public function savePrediction($codigoGtin, $titulo, $prediction) {
        global $conexao;
        
        try {
            $predictionJson = pg_escape_string($conexao, json_encode($prediction));
            $titulo = pg_escape_string($conexao, $titulo);
            
            // Salvar na tabela produtos_ib
            $query = "UPDATE produtos_ib SET 
                        categoria_ml = '{$prediction['category_id']}',
                        categoria_ml_data = '$predictionJson'
                      WHERE codigo_interno = (
                          SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigoGtin'
                      )";
            
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                error_log("Erro ao salvar predição: " . pg_last_error($conexao));
            }
            
        } catch (Exception $e) {
            error_log("Erro ao salvar predição: " . $e->getMessage());
        }
    }
}

// Função helper para uso fácil
function getMLCategoryPredictor() {
    return new MLCategoryPredictor();
}

// Se chamado diretamente, testar com um título
if (basename($_SERVER['PHP_SELF']) === 'category_predictor.php') {
    header('Content-Type: application/json; charset=utf-8');
    
    $titulo = $_GET['titulo'] ?? 'Produto teste';
    
    $predictor = new MLCategoryPredictor();
    $result = $predictor->predictCategory($titulo);
    
    echo json_encode($result, JSON_PRETTY_PRINT);
}
?>
