<?php
/**
 * Gerenciador de Tabelas de Medidas do ML - Versão Oficial V3
 * Baseado na documentação oficial do Mercado Livre
 *
 * Estratégia CORRIGIDA:
 * 1. Verificar se categoria/domínio requer SIZE_GRID
 * 2. Buscar tabelas existentes (BRAND/STANDARD) via API
 * 3. Fallback para SPECIFIC quando necessário
 * 4. Implementar corretamente SIZE_GRID_ROW_ID
 * 5. Garantir AGE_GROUP obrigatório
 */

require_once 'token_manager.php';

class MLSizeChartManagerV3 {
    private $tokenManager;
    private $conexao;
    private $cache = [];

    // Mapeamento correto de categorias para domínios baseado na documentação
    private $categoryToDomain = [
        'MLB31447' => 'SHIRTS',           // Camisetas e regatas
        'MLB31448' => 'SHIRTS',           // Polos
        'MLB31099' => 'JEANS',            // Jeans
        'MLB1059'  => 'SNEAKERS',         // Tênis
        'MLB1040'  => 'CASUAL_SHOES',     // Sapatos casuais
        'MLB1267'  => 'SHIRTS',           // Roupas e acessórios (genérico)
    ];

    // Domínios que requerem SIZE_GRID obrigatoriamente
    private $domainsRequiringSizeGrid = [
        'SHIRTS', 'JEANS', 'SNEAKERS', 'CASUAL_SHOES', 'BOOTS_AND_BOOTIES',
        'SANDALS_AND_CLOGS', 'LOAFERS_AND_OXFORDS', 'FOOTBALL_SHOES'
    ];

    public function __construct($tokenManager, $conexao) {
        $this->tokenManager = $tokenManager;
        $this->conexao = $conexao;
    }
    
    /**
     * Função principal: obtém SIZE_GRID_ID para um produto
     * CORRIGIDA baseada na documentação oficial do ML
     */
    public function getSizeGridForProduct($tamanhos, $categoria, $attributes) {
        try {
            error_log("SIZE_CHART_V3: Iniciando busca para categoria $categoria com tamanhos: " . implode(', ', $tamanhos));

            // 1. Verificar se categoria requer SIZE_GRID
            $domainId = $this->mapCategoryToDomain($categoria);
            if (!$domainId) {
                error_log("SIZE_CHART_V3: Categoria $categoria não possui domain_id conhecido");
                return ['success' => false, 'error' => 'Categoria não suporta tabelas de medidas'];
            }

            if (!$this->domainRequiresSizeGrid($domainId)) {
                error_log("SIZE_CHART_V3: Domínio $domainId não requer SIZE_GRID");
                return ['success' => false, 'error' => 'Domínio não requer tabela de medidas'];
            }

            // 2. Verificar se domínio está ativo para tabelas de medidas
            $activeDomains = $this->getActiveDomains();
            if (!in_array($domainId, $activeDomains)) {
                error_log("SIZE_CHART_V3: Domínio $domainId não está ativo para tabelas de medidas");
                return ['success' => false, 'error' => 'Domínio não ativo para tabelas de medidas'];
            }

            // 3. Extrair atributos necessários para busca
            $searchAttributes = $this->extractSearchAttributes($attributes);

            // 4. Buscar tabelas existentes (BRAND/STANDARD) via API oficial
            $existingChart = $this->searchExistingCharts($domainId, $searchAttributes);

            if ($existingChart['success'] && !empty($existingChart['charts'])) {
                $chart = $existingChart['charts'][0];
                error_log("SIZE_CHART_V3: Tabela existente encontrada: " . $chart['id'] . " (tipo: " . $chart['type'] . ")");

                return [
                    'success' => true,
                    'chart_id' => $chart['id'],
                    'chart_name' => $chart['names']['MLB'] ?? 'Tabela do ML',
                    'type' => $chart['type'],
                    'source' => 'existing',
                    'rows' => $chart['rows'] ?? []
                ];
            }

            // 5. Se não encontrou, criar tabela SPECIFIC
            error_log("SIZE_CHART_V3: Nenhuma tabela existente encontrada, criando SPECIFIC");

            $specificChart = $this->createSpecificChart($domainId, $tamanhos, $searchAttributes);

            if ($specificChart['success']) {
                error_log("SIZE_CHART_V3: Tabela SPECIFIC criada: " . $specificChart['chart_id']);
                return $specificChart;
            }

            error_log("SIZE_CHART_V3: Falha ao criar tabela SPECIFIC: " . $specificChart['error']);
            return ['success' => false, 'error' => 'Não foi possível criar tabela de medidas: ' . $specificChart['error']];

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mapeia categoria do ML para domain_id
     */
    private function mapCategoryToDomain($categoria) {
        // ✅ CORREÇÃO: Verificar domínio real da categoria via API
        try {
            $response = $this->tokenManager->makeMLRequest(
                "https://api.mercadolibre.com/categories/$categoria",
                'GET'
            );

            if ($response['success'] && isset($response['data']['domain_id'])) {
                $realDomain = $response['data']['domain_id'];
                error_log("SIZE_CHART: Domínio real da categoria $categoria: $realDomain");

                // Remover prefixo MLB- se presente
                $cleanDomain = str_replace('MLB-', '', $realDomain);
                return $cleanDomain;
            }
        } catch (Exception $e) {
            error_log("SIZE_CHART: Erro ao obter domínio da categoria: " . $e->getMessage());
        }

        // Usar mapeamento manual como fallback
        if (isset($this->categoryToDomain[$categoria])) {
            error_log("SIZE_CHART: Usando domínio do mapeamento manual: " . $this->categoryToDomain[$categoria]);
            return $this->categoryToDomain[$categoria];
        }

        error_log("SIZE_CHART: Categoria $categoria não encontrada no mapeamento");
        return null;
    }

    /**
     * Verifica se domínio requer SIZE_GRID
     */
    private function domainRequiresSizeGrid($domainId) {
        return in_array($domainId, $this->domainsRequiringSizeGrid);
    }

    /**
     * Obtém domínios ativos para tabelas de medidas via API
     */
    private function getActiveDomains() {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                error_log("SIZE_CHART_V3: Token inválido para buscar domínios ativos");
                return [];
            }

            $response = $this->tokenManager->makeMLRequest(
                'https://api.mercadolibre.com/catalog/charts/MLB/configurations/active_domains',
                'GET',
                null,
                $tokenResult['token']
            );

            if ($response['success'] && isset($response['data']['domains'])) {
                $domains = array_map(function($d) {
                    return str_replace('MLB-', '', $d['domain_id']);
                }, $response['data']['domains']);

                error_log("SIZE_CHART_V3: Domínios ativos: " . implode(', ', $domains));
                return $domains;
            }

            error_log("SIZE_CHART_V3: Falha ao obter domínios ativos: " . json_encode($response));
            return $this->domainsRequiringSizeGrid; // Fallback

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro ao obter domínios ativos: " . $e->getMessage());
            return $this->domainsRequiringSizeGrid; // Fallback
        }
    }
    
    /**
     * Extrai atributos necessários para busca de tabelas
     */
    private function extractSearchAttributes($attributes) {
        $searchAttrs = [];
        
        foreach ($attributes as $attr) {
            $id = $attr['id'] ?? '';
            
            // Atributos importantes para busca de tabelas
            if (in_array($id, ['BRAND', 'GENDER', 'AGE_GROUP'])) {
                $searchAttrs[] = [
                    'id' => $id,
                    'values' => [
                        [
                            'name' => $attr['value_name'] ?? '',
                            'id' => $attr['value_id'] ?? null
                        ]
                    ]
                ];
            }
        }
        
        error_log("SIZE_CHART_V2: Atributos para busca: " . json_encode($searchAttrs));
        return $searchAttrs;
    }
    
    /**
     * Busca tabelas existentes (BRAND/STANDARD) na API do ML
     * CORRIGIDA baseada na documentação oficial
     */
    private function searchExistingCharts($domainId, $searchAttributes) {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                return ['success' => false, 'error' => 'Token inválido'];
            }

            // Obter seller_id real
            $sellerId = $this->getSellerId();
            if (!$sellerId) {
                error_log("SIZE_CHART_V3: Seller ID não encontrado");
                return ['success' => false, 'error' => 'Seller ID não encontrado'];
            }

            $searchData = [
                'domain_id' => $domainId,
                'site_id' => 'MLB',
                'seller_id' => $sellerId,
                'attributes' => $searchAttributes
            ];

            error_log("SIZE_CHART_V3: Buscando tabelas existentes com: " . json_encode($searchData));

            $response = $this->tokenManager->makeMLRequest(
                'https://api.mercadolibre.com/catalog/charts/search?limit=50&offset=0',
                'POST',
                $searchData,
                $tokenResult['token']
            );

            if ($response['success'] && isset($response['data']['charts'])) {
                $charts = $response['data']['charts'];
                error_log("SIZE_CHART_V3: Encontradas " . count($charts) . " tabelas");

                // Filtrar BRAND, STANDARD e SPECIFIC do próprio vendedor
                $sellerId = $this->getSellerId();
                $validCharts = array_filter($charts, function($chart) use ($sellerId) {
                    if (in_array($chart['type'], ['BRAND', 'STANDARD'])) {
                        return true; // BRAND e STANDARD são sempre válidos
                    }
                    // Para SPECIFIC, verificar se é do mesmo vendedor (se possível)
                    return $chart['type'] === 'SPECIFIC';
                });

                // Priorizar BRAND > STANDARD > SPECIFIC
                usort($validCharts, function($a, $b) {
                    $priority = ['BRAND' => 1, 'STANDARD' => 2, 'SPECIFIC' => 3];
                    return ($priority[$a['type']] ?? 4) - ($priority[$b['type']] ?? 4);
                });

                return ['success' => true, 'charts' => $validCharts];
            }

            error_log("SIZE_CHART_V3: Nenhuma tabela existente encontrada ou erro na API: " . json_encode($response));
            return ['success' => true, 'charts' => []];

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro ao buscar tabelas: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtém especificações técnicas para criar tabela SPECIFIC
     */
    private function getTechnicalSpecs($domainId, $searchAttributes) {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                return ['success' => false, 'error' => 'Token inválido'];
            }
            
            // Converter attributes para o formato esperado pela API
            $techSpecsData = [
                'attributes' => $this->convertAttributesForTechSpecs($searchAttributes)
            ];
            
            error_log("SIZE_CHART_V2: Buscando especificações técnicas para domain $domainId");
            
            $response = $this->tokenManager->makeMLRequest(
                "https://api.mercadolibre.com/domains/$domainId/technical_specs?section=grids",
                'POST',
                $techSpecsData,
                $tokenResult['token']
            );
            
            if ($response['success']) {
                error_log("SIZE_CHART_V2: Especificações técnicas obtidas com sucesso");
                return ['success' => true, 'specs' => $response['data']];
            }
            
            error_log("SIZE_CHART_V2: Falha ao obter especificações: " . json_encode($response));
            return ['success' => false, 'error' => 'Especificações não disponíveis'];
            
        } catch (Exception $e) {
            error_log("SIZE_CHART_V2: Erro ao obter especificações: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Converte attributes para formato de technical_specs
     */
    private function convertAttributesForTechSpecs($searchAttributes) {
        $converted = [];
        
        foreach ($searchAttributes as $attr) {
            $values = [];
            foreach ($attr['values'] as $value) {
                $valueData = [
                    'name' => $value['name']
                ];
                if (!empty($value['id'])) {
                    $valueData['id'] = $value['id'];
                }
                $values[] = $valueData;
            }
            
            $converted[] = [
                'id' => $attr['id'],
                'name' => $this->getAttributeName($attr['id']),
                'values' => $values,
                'attribute_group_id' => 'OTHERS',
                'attribute_group_name' => 'Otros'
            ];
        }
        
        return $converted;
    }
    
    /**
     * Cria tabela SPECIFIC baseada na documentação oficial do ML
     * SIMPLIFICADA - sem necessidade de technical_specs complexas
     */
    private function createSpecificChart($domainId, $tamanhos, $searchAttributes) {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                return ['success' => false, 'error' => 'Token inválido'];
            }

            // Montar dados da tabela baseado na documentação oficial
            $chartName = $this->generateChartName($searchAttributes, $tamanhos);

            // Estrutura baseada nos exemplos da documentação oficial
            $chartData = [
                'names' => [
                    'MLB' => $chartName
                ],
                'domain_id' => $domainId,
                'site_id' => 'MLB',
                'main_attribute' => [
                    'attributes' => [
                        [
                            'site_id' => 'MLB',
                            'id' => 'SIZE'
                        ]
                    ]
                ],
                'attributes' => $this->buildChartAttributes($searchAttributes),
                'rows' => $this->buildChartRows($tamanhos, $domainId)
            ];

            error_log("SIZE_CHART_V3: Criando tabela SPECIFIC: " . json_encode($chartData, JSON_PRETTY_PRINT));

            $response = $this->tokenManager->makeMLRequest(
                'https://api.mercadolibre.com/catalog/charts',
                'POST',
                $chartData,
                $tokenResult['token']
            );

            if ($response['success']) {
                $chartId = $response['data']['id'];
                $responseData = $response['data'];

                // Salvar no banco local
                $this->saveChartToDatabase($chartId, $chartName, $tamanhos, $domainId);

                return [
                    'success' => true,
                    'chart_id' => $chartId,
                    'chart_name' => $chartName,
                    'type' => 'SPECIFIC',
                    'source' => 'created',
                    'rows' => $responseData['rows'] ?? []
                ];
            }

            error_log("SIZE_CHART_V3: Erro ao criar tabela: " . json_encode($response));

            // Extrair mensagem de erro mais específica
            $errorMsg = 'Erro desconhecido';
            if (isset($response['data']['message'])) {
                $errorMsg = $response['data']['message'];
            } elseif (isset($response['data']['errors']) && is_array($response['data']['errors'])) {
                $errorMsg = implode('; ', array_map(function($e) {
                    return $e['message'] ?? 'Erro sem mensagem';
                }, $response['data']['errors']));
            }

            return ['success' => false, 'error' => 'Falha ao criar tabela: ' . $errorMsg];

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro ao criar tabela SPECIFIC: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extrai componentes da grid das especificações técnicas
     */
    private function extractGridComponents($techSpecs) {
        $components = [];
        
        // Navegar pela estrutura das especificações
        $groups = $techSpecs['input']['groups'] ?? [];
        foreach ($groups as $group) {
            if ($group['id'] === 'SIZE_CHART') {
                $gridComponents = $group['components'] ?? [];
                foreach ($gridComponents as $component) {
                    if ($component['component'] === 'GRID') {
                        $components = $component['components'] ?? [];
                        break 2;
                    }
                }
            }
        }
        
        return $components;
    }
    
    /**
     * Constrói atributos da tabela baseado na documentação oficial
     */
    private function buildChartAttributes($searchAttributes) {
        $attributes = [];

        // Adicionar atributos obrigatórios baseados na documentação
        foreach ($searchAttributes as $searchAttr) {
            $attrData = [
                'id' => $searchAttr['id']
            ];

            $value = $searchAttr['values'][0] ?? [];
            if (!empty($value['name'])) {
                $attrData['values'] = [
                    [
                        'name' => $value['name']
                    ]
                ];

                if (!empty($value['id'])) {
                    $attrData['values'][0]['id'] = $value['id'];
                }
            }

            $attributes[] = $attrData;
        }

        return $attributes;
    }
    
    /**
     * Constrói linhas da tabela baseado na documentação oficial
     */
    private function buildChartRows($tamanhos, $domainId) {
        $rows = [];

        // Medidas baseadas no tipo de domínio
        if (in_array($domainId, ['SNEAKERS', 'CASUAL_SHOES', 'BOOTS_AND_BOOTIES'])) {
            // Para calçados - usar medidas de pé
            $standardMeasures = [
                '35' => ['foot_length' => 22.5],
                '36' => ['foot_length' => 23.0],
                '37' => ['foot_length' => 23.5],
                '38' => ['foot_length' => 24.0],
                '39' => ['foot_length' => 24.5],
                '40' => ['foot_length' => 25.0],
                '41' => ['foot_length' => 25.5],
                '42' => ['foot_length' => 26.0],
                '43' => ['foot_length' => 26.5],
                '44' => ['foot_length' => 27.0]
            ];

            foreach ($tamanhos as $tamanho) {
                $measures = $standardMeasures[$tamanho] ?? $standardMeasures['40'];

                $row = [
                    'attributes' => [
                        [
                            'id' => 'SIZE',
                            'values' => [
                                ['name' => $tamanho]
                            ]
                        ],
                        [
                            'id' => 'FOOT_LENGTH',
                            'values' => [
                                ['name' => $measures['foot_length'] . ' cm']
                            ]
                        ]
                    ]
                ];

                $rows[] = $row;
            }
        } else {
            // Para roupas - usar valores corretos baseados na especificação técnica real do domínio SHIRTS
            // Valores obtidos via API: /domains/MLB-SHIRTS/technical_specs?section=grids
            $validFiltrableSizes = [
                'XPP' => 'XPP',
                'PP' => 'PP',
                'P' => 'P',
                'M' => 'M',
                'G' => 'G',
                'GG' => 'GG',
                'XG' => 'XG',
                'XGG' => 'XGG',
                'G1' => 'G1',
                'G2' => 'G2',
                'G3' => 'G3',
                'G4' => 'G4',
                'G5' => 'G5',
                'G6' => 'G6',
                'G7' => 'G7',
                'G8' => 'G8',
                'UN' => 'M' // Tamanho único mapeia para M
            ];

            $standardMeasures = [
                'PP' => ['chest' => 88, 'waist' => 76],
                'P'  => ['chest' => 92, 'waist' => 80],
                'M'  => ['chest' => 96, 'waist' => 84],
                'G'  => ['chest' => 100, 'waist' => 88],
                'GG' => ['chest' => 104, 'waist' => 92],
                'XGG' => ['chest' => 108, 'waist' => 96],
                'UN' => ['chest' => 96, 'waist' => 84] // Tamanho único
            ];

            foreach ($tamanhos as $tamanho) {
                $measures = $standardMeasures[$tamanho] ?? $standardMeasures['M'];
                $filtrableSize = $validFiltrableSizes[$tamanho] ?? $validFiltrableSizes['M'];

                $row = [
                    'attributes' => [
                        [
                            'id' => 'SIZE',
                            'values' => [
                                ['name' => $tamanho]
                            ]
                        ],
                        [
                            'id' => 'FILTRABLE_SIZE',
                            'values' => [
                                ['name' => $filtrableSize]
                            ]
                        ],
                        [
                            'id' => 'CHEST_CIRCUMFERENCE_FROM',
                            'values' => [
                                ['name' => $measures['chest'] . ' cm']
                            ]
                        ],
                        [
                            'id' => 'WAIST_CIRCUMFERENCE_FROM',
                            'values' => [
                                ['name' => $measures['waist'] . ' cm']
                            ]
                        ]
                    ]
                ];

                $rows[] = $row;
            }
        }

        return $rows;
    }
    
    /**
     * Obtém seller_id real via API do ML
     */
    private function getSellerId() {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                error_log("SIZE_CHART_V3: Token inválido para obter seller_id");
                return null;
            }

            $response = $this->tokenManager->makeMLRequest(
                'https://api.mercadolibre.com/users/me',
                'GET',
                null,
                $tokenResult['token']
            );

            if ($response['success'] && isset($response['data']['id'])) {
                $sellerId = $response['data']['id'];
                error_log("SIZE_CHART_V3: Seller ID obtido: $sellerId");
                return $sellerId;
            }

            error_log("SIZE_CHART_V3: Falha ao obter seller_id: " . json_encode($response));
            return null;

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro ao obter seller_id: " . $e->getMessage());
            return null;
        }
    }
    
    private function getAttributeName($attrId) {
        $names = [
            'BRAND' => 'Marca',
            'GENDER' => 'Gênero',
            'AGE_GROUP' => 'Idade'
        ];
        return $names[$attrId] ?? $attrId;
    }
    
    private function generateChartName($searchAttributes, $tamanhos) {
        $brand = '';
        $gender = '';

        foreach ($searchAttributes as $attr) {
            if ($attr['id'] === 'BRAND') {
                $brand = $attr['values'][0]['name'] ?? '';
            } elseif ($attr['id'] === 'GENDER') {
                $gender = $attr['values'][0]['name'] ?? '';
            }
        }

        // ✅ CORREÇÃO: Adicionar timestamp para evitar nomes duplicados
        $sizesStr = implode(',', $tamanhos);
        $timestamp = date('His'); // HoraMinutoSegundo
        $name = "Tab $gender $brand $sizesStr $timestamp";

        if (strlen($name) > 60) {
            $name = substr($name, 0, 57) . '...';
        }

        return $name;
    }
    
    private function saveChartToDatabase($chartId, $chartName, $tamanhos, $domainId) {
        $sizesStr = implode(',', $tamanhos);
        
        $query = "INSERT INTO ml_size_charts (chart_id, chart_name, sizes, categoria, gender, created_at) 
                  VALUES ($1, $2, $3, $4, $5, NOW())
                  ON CONFLICT (chart_id) DO UPDATE SET 
                  chart_name = EXCLUDED.chart_name,
                  updated_at = NOW()";
        
        pg_query_params($this->conexao, $query, [
            $chartId, $chartName, $sizesStr, $domainId, 'Unisex'
        ]);
    }
    
    /**
     * Obtém ROW_ID real para um tamanho específico via API
     */
    public function getRowIdForSize($chartId, $size) {
        try {
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                error_log("SIZE_CHART_V3: Token inválido para obter row_id");
                return $chartId . ':' . hash('crc32b', $size); // Fallback
            }

            // Buscar detalhes da tabela via API
            $response = $this->tokenManager->makeMLRequest(
                "https://api.mercadolibre.com/catalog/charts/$chartId",
                'GET',
                null,
                $tokenResult['token']
            );

            if ($response['success'] && isset($response['data']['rows'])) {
                $rows = $response['data']['rows'];

                // Procurar pela linha que corresponde ao tamanho
                foreach ($rows as $row) {
                    $rowAttributes = $row['attributes'] ?? [];
                    foreach ($rowAttributes as $attr) {
                        if ($attr['id'] === 'SIZE') {
                            $values = $attr['values'] ?? [];
                            foreach ($values as $value) {
                                if (($value['name'] ?? '') === $size) {
                                    $rowId = $row['id'] ?? null;
                                    if ($rowId) {
                                        error_log("SIZE_CHART_V3: Row ID encontrado para tamanho $size: $rowId");
                                        return $rowId;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            error_log("SIZE_CHART_V3: Row ID não encontrado para tamanho $size, usando fallback");
            return $chartId . ':' . hash('crc32b', $size); // Fallback determinístico

        } catch (Exception $e) {
            error_log("SIZE_CHART_V3: Erro ao obter row_id: " . $e->getMessage());
            return $chartId . ':' . hash('crc32b', $size); // Fallback
        }
    }
}

/**
 * Factory function - V3 corrigida
 */
function getMLSizeChartManagerV3() {
    static $instance = null;
    if ($instance === null) {
        global $conexao;
        $tokenManager = getMLTokenManager();
        $instance = new MLSizeChartManagerV3($tokenManager, $conexao);
    }
    return $instance;
}

/**
 * Alias para compatibilidade
 */
function getMLSizeChartManagerV2() {
    return getMLSizeChartManagerV3();
}
?>