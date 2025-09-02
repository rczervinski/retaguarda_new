<?php
/**
 * Mapeador de Categorias e Atributos do Mercado Livre
 * Baseado nas informações obtidas via MCP
 */

class MLCategoryMapper {
    
    private $knownCategories;
    
    public function __construct() {
        $this->initializeKnownCategories();
    }
    
    /**
     * Inicializa categorias conhecidas com seus atributos obrigatórios
     * Baseado em pesquisas via MCP e testes reais
     */
    private function initializeKnownCategories() {
        $this->knownCategories = [
            
            // ✅ BEBIDAS E ACHOCOLATADOS
            'MLB278125' => [
                'name' => 'Achocolatados',
                'domain' => 'MLB-FOOD_AND_BEVERAGES',
                'required_attributes' => [
                    'MANUFACTURER' => [
                        'name' => 'Fabricante',
                        'type' => 'string',
                        'description' => 'Marca ou fabricante do produto',
                        'examples' => ['Nestlé', 'Toddy', 'Nescau']
                    ],
                    'PRODUCT_NAME' => [
                        'name' => 'Nome do Produto',
                        'type' => 'string', 
                        'description' => 'Nome específico do produto',
                        'examples' => ['Achocolatado Nescau 2.0', 'Toddy Original']
                    ],
                    'NET_WEIGHT' => [
                        'name' => 'Peso Líquido',
                        'type' => 'number_unit',
                        'description' => 'Peso do produto',
                        'examples' => ['400 g', '500 g', '1 kg']
                    ]
                ]
            ],
            
            // ✅ VEGETAIS E VERDURAS
            'MLB432663' => [
                'name' => 'Vegetais e Verduras',
                'domain' => 'MLB-FOOD_AND_BEVERAGES',
                'required_attributes' => [
                    'VEGETABLE_TYPE' => [
                        'name' => 'Tipo de Vegetal',
                        'type' => 'list',
                        'description' => 'Categoria do vegetal',
                        'examples' => ['Abóbora', 'Abobrinha', 'Tomate', 'Cebola']
                    ],
                    'VEGETABLE_VARIETY' => [
                        'name' => 'Variedade',
                        'type' => 'string',
                        'description' => 'Variedade específica do vegetal',
                        'examples' => ['Italiana', 'Japonesa', 'Orgânica']
                    ],
                    'NET_WEIGHT' => [
                        'name' => 'Peso',
                        'type' => 'number_unit',
                        'description' => 'Peso do produto',
                        'examples' => ['100 g', '500 g', '1 kg']
                    ]
                ]
            ],
            
            // ✅ ELETRÔNICOS - CELULARES
            'MLB1055' => [
                'name' => 'Celulares e Smartphones',
                'domain' => 'MLB-CELLPHONES',
                'required_attributes' => [
                    'BRAND' => [
                        'name' => 'Marca',
                        'type' => 'string',
                        'description' => 'Marca do celular',
                        'examples' => ['Samsung', 'Apple', 'Xiaomi', 'Motorola']
                    ],
                    'MODEL' => [
                        'name' => 'Modelo',
                        'type' => 'string',
                        'description' => 'Modelo específico',
                        'examples' => ['Galaxy S23', 'iPhone 15', 'Redmi Note 12']
                    ],
                    'OPERATING_SYSTEM' => [
                        'name' => 'Sistema Operacional',
                        'type' => 'list',
                        'description' => 'Sistema operacional do dispositivo',
                        'examples' => ['Android', 'iOS']
                    ]
                ]
            ],
            
            // ✅ ROUPAS - CAMISETAS
            'MLB109291' => [
                'name' => 'Camisetas',
                'domain' => 'MLB-CLOTHING',
                'required_attributes' => [
                    'BRAND' => [
                        'name' => 'Marca',
                        'type' => 'string',
                        'description' => 'Marca da roupa',
                        'examples' => ['Nike', 'Adidas', 'Genérica']
                    ],
                    'GENDER' => [
                        'name' => 'Gênero',
                        'type' => 'list',
                        'description' => 'Público-alvo',
                        'examples' => ['Masculino', 'Feminino', 'Unissex']
                    ],
                    'SIZE' => [
                        'name' => 'Tamanho',
                        'type' => 'list',
                        'description' => 'Tamanho da peça',
                        'examples' => ['P', 'M', 'G', 'GG']
                    ]
                ]
            ],

            // ✅ PRODUTOS DE LIMPEZA - ÁLCOOL
            'MLB270913' => [
                'name' => 'Álcool e Produtos de Limpeza',
                'domain' => 'MLB-CLEANING_PRODUCTS',
                'required_attributes' => [
                    'BRAND' => [
                        'name' => 'Marca',
                        'type' => 'string',
                        'description' => 'Marca do produto de limpeza',
                        'examples' => ['Da Ilha', 'Veja', 'Ypê', 'Genérica']
                    ],
                    'PRODUCT_TYPE' => [
                        'name' => 'Tipo de Produto',
                        'type' => 'string',
                        'description' => 'Tipo específico do produto',
                        'examples' => ['Álcool 70%', 'Álcool Gel', 'Desinfetante']
                    ],
                    'NET_VOLUME' => [
                        'name' => 'Volume',
                        'type' => 'number_unit',
                        'description' => 'Volume do produto',
                        'examples' => ['500 ml', '1 l', '250 ml']
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Busca informações de uma categoria específica
     */
    public function getCategoryInfo($categoryId) {
        return $this->knownCategories[$categoryId] ?? null;
    }
    
    /**
     * Verifica se uma categoria é conhecida
     */
    public function isCategoryKnown($categoryId) {
        return isset($this->knownCategories[$categoryId]);
    }
    
    /**
     * Retorna todas as categorias conhecidas
     */
    public function getAllKnownCategories() {
        return $this->knownCategories;
    }
    
    /**
     * Busca categorias por domínio
     */
    public function getCategoriesByDomain($domain) {
        $result = [];
        foreach ($this->knownCategories as $categoryId => $info) {
            if ($info['domain'] === $domain) {
                $result[$categoryId] = $info;
            }
        }
        return $result;
    }
    
    /**
     * Prepara campos para o modal baseado na categoria
     */
    public function prepareModalFields($categoryId) {
        $categoryInfo = $this->getCategoryInfo($categoryId);
        
        if (!$categoryInfo) {
            return [
                'known' => false,
                'message' => 'Categoria não mapeada. Campos padrão serão usados.',
                'fields' => []
            ];
        }
        
        $fields = [];
        foreach ($categoryInfo['required_attributes'] as $attrId => $attrInfo) {
            $fields[] = [
                'id' => $attrId,
                'name' => $attrInfo['name'],
                'type' => $attrInfo['type'],
                'description' => $attrInfo['description'],
                'examples' => $attrInfo['examples'],
                'required' => true
            ];
        }
        
        return [
            'known' => true,
            'category_name' => $categoryInfo['name'],
            'domain' => $categoryInfo['domain'],
            'fields' => $fields
        ];
    }
    
    /**
     * Adiciona nova categoria ao mapeamento
     * Para uso futuro quando descobrirmos novas categorias
     */
    public function addCategory($categoryId, $categoryInfo) {
        $this->knownCategories[$categoryId] = $categoryInfo;
        // TODO: Salvar em arquivo ou banco para persistência
    }
    
    /**
     * Gera sugestões de valores baseado no tipo de produto
     */
    public function generateSuggestions($productTitle, $categoryId) {
        $categoryInfo = $this->getCategoryInfo($categoryId);
        if (!$categoryInfo) {
            return [];
        }
        
        $suggestions = [];
        $title = strtolower($productTitle);
        
        // Sugestões baseadas no título do produto
        foreach ($categoryInfo['required_attributes'] as $attrId => $attrInfo) {
            $suggestion = '';
            
            switch ($attrId) {
                case 'MANUFACTURER':
                case 'BRAND':
                    // Tentar extrair marca do título
                    $brands = ['nestlé', 'nescau', 'toddy', 'samsung', 'apple', 'nike', 'adidas', 'da ilha', 'veja', 'ypê'];
                    foreach ($brands as $brand) {
                        if (strpos($title, $brand) !== false) {
                            $suggestion = ucwords($brand);
                            break;
                        }
                    }
                    break;
                    
                case 'PRODUCT_NAME':
                    $suggestion = $productTitle;
                    break;
                    
                case 'NET_WEIGHT':
                    // Tentar extrair peso do título
                    if (preg_match('/(\d+)\s*(g|kg|ml|l)/i', $title, $matches)) {
                        $suggestion = $matches[1] . ' ' . strtolower($matches[2]);
                    }
                    break;
            }
            
            if ($suggestion) {
                $suggestions[$attrId] = $suggestion;
            }
        }
        
        return $suggestions;
    }
}

// Função helper
function getMLCategoryMapper() {
    return new MLCategoryMapper();
}
?>
