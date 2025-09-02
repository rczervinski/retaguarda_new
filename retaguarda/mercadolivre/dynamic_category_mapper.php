<?php
/**
 * Mapeador Dinâmico de Categorias usando API do Mercado Livre
 * Busca atributos obrigatórios diretamente da API
 */

require_once 'token_manager.php';
require_once '../conexao.php';

class MLDynamicCategoryMapper {

    private $tokenManager;

    public function __construct() {
        global $conexao;
        $this->tokenManager = new MLTokenManager($conexao);
    }
    
    /**
     * Busca atributos obrigatórios da API do ML com debug completo
     * Estratégia:
     * 1) Tenta technical_specs/input (melhor fonte para campos requeridos)
     * 2) Se falhar, usa /categories/{id}/attributes filtrando tags.required
     * 3) Como última opção, retorna fallback vazio
     */
    public function getCategoryAttributes($categoryId) {
        error_log("=== DEBUG CATEGORY ATTRIBUTES ===");
        error_log("Category ID: $categoryId");

        try {
            // 1) technical_specs/input
            $techUrl = "https://api.mercadolibre.com/categories/{$categoryId}/technical_specs/input";
            error_log("URL technical_specs/input: $techUrl");

            $techResp = $this->tokenManager->makeMLRequest($techUrl, 'GET');
            if (($techResp['success'] ?? false) && !empty($techResp['data']['groups'])) {
                error_log("technical_specs/input OK");
                return $this->processTechnicalSpecs($techResp['data'], $categoryId);
            }

            // 2) /attributes
            $attrUrl = "https://api.mercadolibre.com/categories/{$categoryId}/attributes";
            error_log("URL attributes: $attrUrl");
            $attrResp = $this->tokenManager->makeMLRequest($attrUrl, 'GET');
            if (($attrResp['success'] ?? false) && is_array($attrResp['data'])) {
                error_log("attributes OK");
                return $this->processAttributes($attrResp['data'], $categoryId);
            }

            // 3) Como diagnóstico adicional, tenta GET da categoria (não retorna atributos requeridos)
            $catUrl = "https://api.mercadolibre.com/categories/{$categoryId}";
            $catResp = $this->tokenManager->makeMLRequest($catUrl, 'GET');
            if ($catResp['success'] ?? false) {
                error_log("categories GET OK (informativo), sem campos obrigatórios diretos");
            }

            return $this->getFallbackAttributes();
        } catch (Exception $e) {
            error_log("ERRO GERAL: " . $e->getMessage());
            return $this->getFallbackAttributes();
        }
    }

    /**
     * Processa settings da categoria para extrair campos required
     */
    private function processCategorySettings($categoryData, $categoryId) {
        // Mantido apenas por compatibilidade, mas NÃO é confiável para required
        error_log("=== PROCESSANDO SETTINGS DA CATEGORIA (DEPRECATED) ===");
        return $this->getFallbackAttributes();
    }

    /**
     * Mapeia campos do settings para atributos do ML
     */
    private function mapSettingToField($settingKey) {
        $mappings = [
            'price' => [
                'id' => 'PRICE',
                'name' => 'Preço',
                'type' => 'number',
                'required' => true,
                'description' => 'Preço do produto',
                'examples' => ['100.00', '250.50']
            ],
            'stock' => [
                'id' => 'STOCK',
                'name' => 'Estoque',
                'type' => 'number',
                'required' => true,
                'description' => 'Quantidade em estoque',
                'examples' => ['1', '10', '100']
            ],
            'immediate_payment' => [
                'id' => 'IMMEDIATE_PAYMENT',
                'name' => 'Pagamento Imediato',
                'type' => 'boolean',
                'required' => true,
                'description' => 'Requer pagamento imediato',
                'examples' => ['true', 'false']
            ]
        ];

        return $mappings[$settingKey] ?? null;
    }



    /**
     * Processa technical_specs da API e extrai campos obrigatórios
     */
    private function processTechnicalSpecs($techSpecs, $categoryId) {
        $requiredFields = [];
        $optionalFields = [];

        // Verificar se tem grupos
        if (!isset($techSpecs['groups']) || empty($techSpecs['groups'])) {
            return $this->getFallbackAttributes();
        }

        foreach ($techSpecs['groups'] as $group) {
            if (!isset($group['components'])) continue;

            foreach ($group['components'] as $component) {
                if (!isset($component['attributes'])) continue;

                foreach ($component['attributes'] as $attr) {
                    $isRequired = isset($attr['tags']) && in_array('required', $attr['tags']);

                    $fieldInfo = [
                        'id' => $attr['id'],
                        'name' => $attr['name'],
                        'type' => $attr['value_type'],
                        'required' => $isRequired,
                        'max_length' => $attr['value_max_length'] ?? 255,
                        'description' => $this->generateDescription($attr),
                        'examples' => $this->generateExamples($attr)
                    ];

                    // Adicionar valores permitidos se existirem
                    if (isset($attr['values']) && !empty($attr['values'])) {
                        $fieldInfo['allowed_values'] = array_slice($attr['values'], 0, 10);
                    }

                    if ($isRequired) {
                        $requiredFields[] = $fieldInfo;
                    } else {
                        $optionalFields[] = $fieldInfo;
                    }
                }
            }
        }

        return [
            'success' => true,
            'category_id' => $categoryId,
            'source' => 'ml_technical_specs',
            'required_fields' => $requiredFields,
            'optional_fields' => array_slice($optionalFields, 0, 5),
            'total_required' => count($requiredFields),
            'message' => count($requiredFields) > 0
                ? "Categoria com " . count($requiredFields) . " campos obrigatórios (technical_specs)"
                : "Categoria sem campos obrigatórios específicos (technical_specs)"
        ];
    }

    /**
     * Consulta atributos obrigatórios por condição (conditional_required)
     * Envie o mesmo corpo do item que será publicado
     */
    public function getConditionalRequired($categoryId, $itemData) {
        try {
            $url = "https://api.mercadolibre.com/categories/{$categoryId}/attributes/conditional";
            $resp = $this->tokenManager->makeMLRequest($url, 'POST', $itemData);
            if (($resp['success'] ?? false) && isset($resp['data']['required_attributes'])) {
                return [
                    'success' => true,
                    'required_attributes' => $resp['data']['required_attributes']
                ];
            }
            return ['success' => false, 'required_attributes' => []];
        } catch (Exception $e) {
            error_log('Erro conditional_required: ' . $e->getMessage());
            return ['success' => false, 'required_attributes' => []];
        }
    }

    /**
     * Processa atributos da API e extrai apenas os obrigatórios (método antigo)
     */
    private function processAttributes($attributes, $categoryId) {
        error_log("=== PROCESSANDO ATRIBUTOS ===");
        error_log("Total de atributos recebidos: " . count($attributes));

        $requiredFields = [];
        $optionalFields = [];

        foreach ($attributes as $attr) {
            $isRequired = isset($attr['tags']) && in_array('required', $attr['tags']);
            $isHidden = isset($attr['tags']) && in_array('hidden', $attr['tags']);

            // DEBUG COMPLETO: Log de cada atributo
            error_log("ATTR: {$attr['id']} | Name: {$attr['name']} | Required: " . ($isRequired ? 'YES' : 'NO') . " | Hidden: " . ($isHidden ? 'YES' : 'NO') . " | Tags: " . json_encode($attr['tags'] ?? []));

            // Pular atributos ocultos
            if ($isHidden) {
                error_log("  -> PULANDO (hidden)");
                continue;
            }
            
            $fieldInfo = [
                'id' => $attr['id'],
                'name' => $attr['name'],
                'type' => $attr['value_type'],
                'required' => $isRequired,
                'max_length' => $attr['value_max_length'] ?? 255,
                'description' => $this->generateDescription($attr),
                'examples' => $this->generateExamples($attr)
            ];
            
            // Adicionar valores permitidos se for lista
            if ($attr['value_type'] === 'list' && isset($attr['values'])) {
                $fieldInfo['allowed_values'] = array_slice($attr['values'], 0, 10); // Limitar a 10 valores
            }
            
            // Adicionar unidades permitidas se for number_unit
            if ($attr['value_type'] === 'number_unit' && isset($attr['allowed_units'])) {
                $fieldInfo['allowed_units'] = $attr['allowed_units'];
                $fieldInfo['default_unit'] = $attr['default_unit'] ?? '';
            }
            
            if ($isRequired) {
                $requiredFields[] = $fieldInfo;
            } else {
                $optionalFields[] = $fieldInfo;
            }
        }
        
        error_log("DEBUG: Total required fields found: " . count($requiredFields));

        return [
            'success' => true,
            'category_id' => $categoryId,
            'source' => 'ml_api',
            'required_fields' => $requiredFields,
            'optional_fields' => array_slice($optionalFields, 0, 5), // Limitar opcionais
            'total_required' => count($requiredFields),
            'message' => count($requiredFields) > 0
                ? "Categoria com " . count($requiredFields) . " campos obrigatórios (API ML)"
                : "Categoria sem campos obrigatórios específicos (API ML)"
        ];
    }
    
    /**
     * Gera descrição amigável para o atributo
     */
    private function generateDescription($attr) {
        $descriptions = [
            'BRAND' => 'Marca ou fabricante do produto',
            'MODEL' => 'Modelo específico do produto',
            'MANUFACTURER' => 'Fabricante do produto',
            'PRODUCT_NAME' => 'Nome específico do produto',
            'NET_WEIGHT' => 'Peso líquido do produto',
            'NET_VOLUME' => 'Volume do produto',
            'COLOR' => 'Cor principal do produto',
            'SIZE' => 'Tamanho do produto',
            'GENDER' => 'Público-alvo (masculino, feminino, unissex)',
            'VEGETABLE_TYPE' => 'Tipo de vegetal ou verdura',
            'VEGETABLE_VARIETY' => 'Variedade específica do vegetal'
        ];
        
        return $descriptions[$attr['id']] ?? $attr['name'];
    }
    
    /**
     * Gera exemplos para o atributo
     */
    private function generateExamples($attr) {
        $examples = [
            'BRAND' => ['Nike', 'Samsung', 'Nestlé'],
            'MODEL' => ['Galaxy S23', 'iPhone 15', 'Modelo 2024'],
            'MANUFACTURER' => ['Samsung', 'Apple', 'LG'],
            'PRODUCT_NAME' => ['Produto Original', 'Edição Especial'],
            'NET_WEIGHT' => ['500 g', '1 kg', '250 g'],
            'NET_VOLUME' => ['500 ml', '1 l', '250 ml'],
            'COLOR' => ['Preto', 'Branco', 'Azul'],
            'SIZE' => ['P', 'M', 'G', 'GG'],
            'GENDER' => ['Masculino', 'Feminino', 'Unissex']
        ];
        
        // Se tem valores sugeridos na API, usar eles
        if (isset($attr['values']) && !empty($attr['values'])) {
            $apiExamples = array_slice(array_column($attr['values'], 'name'), 0, 3);
            if (!empty($apiExamples)) {
                return $apiExamples;
            }
        }
        
        return $examples[$attr['id']] ?? ['Exemplo 1', 'Exemplo 2'];
    }
    
    /**
     * Atributos de fallback quando API falha - SEM campos padrão inadequados
     */
    private function getFallbackAttributes() {
        return [
            'success' => false,
            'category_id' => 'unknown',
            'source' => 'fallback',
            'required_fields' => [], // SEM campos padrão - deixar vazio
            'optional_fields' => [],
            'total_required' => 0,
            'message' => 'Erro ao buscar atributos da API do ML. Categoria sem campos mapeados.'
        ];
    }
    
    /**
     * Gera sugestões baseadas no título do produto
     */
    public function generateSuggestions($productTitle, $categoryAttributes) {
        $suggestions = [];
        $title = strtolower($productTitle);
        
        foreach ($categoryAttributes['required_fields'] as $field) {
            $suggestion = '';
            
            switch ($field['id']) {
                case 'BRAND':
                case 'MANUFACTURER':
                    // Tentar extrair marca conhecida do título
                    $brands = ['nestlé', 'samsung', 'apple', 'nike', 'adidas', 'da ilha', 'veja'];
                    foreach ($brands as $brand) {
                        if (strpos($title, $brand) !== false) {
                            $suggestion = ucwords($brand);
                            break;
                        }
                    }
                    break;
                    
                case 'MODEL':
                case 'PRODUCT_NAME':
                    $suggestion = $productTitle;
                    break;
                    
                case 'NET_WEIGHT':
                case 'NET_VOLUME':
                    // Tentar extrair peso/volume do título
                    if (preg_match('/(\d+)\s*(g|kg|ml|l)/i', $title, $matches)) {
                        $suggestion = $matches[1] . ' ' . strtolower($matches[2]);
                    }
                    break;
            }
            
            if ($suggestion) {
                $suggestions[$field['id']] = $suggestion;
            }
        }
        
        return $suggestions;
    }
}

// Função helper
function getMLDynamicCategoryMapper() {
    return new MLDynamicCategoryMapper();
}
?>
