<?php
/**
 * Gerenciador de Tabelas de Medidas (SIZE_GRID) do Mercado Livre
 * 
 * Responsável por:
 * - Criar tabelas de medidas personalizadas
 * - Gerenciar SIZE_GRID_ID e SIZE_GRID_ROW_ID
 * - Associar tabelas aos produtos com variações
 */

require_once 'token_manager.php';

class MLSizeChartManager {
    private $tokenManager;
    private $conexao;
    private $cache = [];
    
    public function __construct($tokenManager, $conexao) {
        $this->tokenManager = $tokenManager;
        $this->conexao = $conexao;
    }
    
    /**
     * Obtém ou cria SIZE_GRID_ID para um conjunto de tamanhos
     */
    public function getSizeGridForSizes($tamanhos, $categoria, $gender = 'Masculino') {
        // Normalizar tamanhos
        $tamanhos = array_map('strtoupper', array_map('trim', $tamanhos));
        sort($tamanhos);
        
        $cacheKey = $categoria . '_' . $gender . '_' . implode('_', $tamanhos);
        
        // Verificar cache
        if (isset($this->cache[$cacheKey])) {
            error_log("SIZE_CHART: Cache hit para $cacheKey");
            return $this->cache[$cacheKey];
        }
        
        // Buscar tabela existente no banco
        $existingChart = $this->findExistingChart($tamanhos, $categoria, $gender);
        if ($existingChart) {
            $this->cache[$cacheKey] = $existingChart;
            return $existingChart;
        }
        
        // Criar nova tabela de medidas
        error_log("SIZE_CHART: Criando nova tabela para tamanhos: " . implode(', ', $tamanhos));
        $newChart = $this->createSizeChart($tamanhos, $categoria, $gender);
        
        if ($newChart['success']) {
            $this->cache[$cacheKey] = $newChart;
            return $newChart;
        }
        
        return ['success' => false, 'error' => 'Falha ao criar tabela de medidas'];
    }
    
    /**
     * Busca tabela existente no banco local
     */
    private function findExistingChart($tamanhos, $categoria, $gender) {
        $sizesStr = implode(',', $tamanhos);
        
        $query = "SELECT chart_id, chart_name, sizes, created_at 
                  FROM ml_size_charts 
                  WHERE categoria = $1 AND gender = $2 AND sizes = $3 
                  ORDER BY created_at DESC LIMIT 1";
        
        $result = pg_query_params($this->conexao, $query, [$categoria, $gender, $sizesStr]);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            error_log("SIZE_CHART: Tabela existente encontrada: " . $row['chart_id']);
            
            return [
                'success' => true,
                'chart_id' => $row['chart_id'],
                'chart_name' => $row['chart_name'],
                'sizes' => explode(',', $row['sizes'])
            ];
        }
        
        return null;
    }
    
    /**
     * Cria nova tabela de medidas via API do ML
     */
    private function createSizeChart($tamanhos, $categoria, $gender) {
        try {
            // Obter token válido
            $tokenResult = $this->tokenManager->getValidToken();
            if (!$tokenResult['success']) {
                return ['success' => false, 'error' => 'Token inválido: ' . $tokenResult['error']];
            }
            
            $token = $tokenResult['token'];
            
            // Preparar dados da tabela
            $chartName = "Tabela " . $gender . " - " . implode(", ", $tamanhos);
            $domainId = $this->getDomainIdFromCategory($categoria);
            
            $chartData = [
                'names' => [
                    'MLB' => $chartName
                ],
                'domain_id' => $domainId,
                'site_id' => 'MLB',
                'measure_type' => 'CLOTHING_MEASURE',
                'main_attribute' => [
                    'id' => 'SIZE',
                    'site_id' => 'MLB'
                ],
                'attributes' => [
                    [
                        'id' => 'GENDER',
                        'name' => 'Gênero',
                        'value_id' => $this->getGenderValueId($gender),
                        'value_name' => $gender
                    ],
                    [
                        'id' => 'CHEST_CIRCUMFERENCE',
                        'name' => 'Circunferência do peito'
                    ],
                    [
                        'id' => 'WAIST_CIRCUMFERENCE',
                        'name' => 'Circunferência da cintura'
                    ],
                    [
                        'id' => 'LENGTH',
                        'name' => 'Comprimento'
                    ]
                ],
                'rows' => $this->buildChartRows($tamanhos, $gender)
            ];
            
            error_log("SIZE_CHART: Dados da tabela: " . json_encode($chartData, JSON_PRETTY_PRINT));
            
            // Fazer requisição para criar tabela
            $response = $this->tokenManager->makeMLRequest(
                'https://api.mercadolibre.com/catalog/charts',
                'POST',
                $chartData,
                $token
            );
            
            if (!$response['success']) {
                error_log("SIZE_CHART: Erro ao criar tabela: " . json_encode($response));
                return ['success' => false, 'error' => 'Falha na API: ' . ($response['data']['message'] ?? 'Erro desconhecido')];
            }
            
            $chartId = $response['data']['id'];
            error_log("SIZE_CHART: Tabela criada com sucesso: $chartId");
            
            // Salvar no banco local
            $this->saveChartToDatabase($chartId, $chartName, $tamanhos, $categoria, $gender);
            
            return [
                'success' => true,
                'chart_id' => $chartId,
                'chart_name' => $chartName,
                'sizes' => $tamanhos
            ];
            
        } catch (Exception $e) {
            error_log("SIZE_CHART: Exceção ao criar tabela: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Constrói as linhas da tabela de medidas
     */
    private function buildChartRows($tamanhos, $gender) {
        $rows = [];
        
        // Mapeamento básico de medidas por tamanho (em cm)
        $measurements = [
            'PP' => ['chest' => 88, 'waist' => 76, 'length' => 66],
            'P'  => ['chest' => 92, 'waist' => 80, 'length' => 68],
            'M'  => ['chest' => 96, 'waist' => 84, 'length' => 70],
            'G'  => ['chest' => 100, 'waist' => 88, 'length' => 72],
            'GG' => ['chest' => 104, 'waist' => 92, 'length' => 74],
            'XGG' => ['chest' => 108, 'waist' => 96, 'length' => 76],
            'EGG' => ['chest' => 112, 'waist' => 100, 'length' => 78]
        ];
        
        foreach ($tamanhos as $tamanho) {
            $measures = $measurements[$tamanho] ?? $measurements['M']; // Fallback para M
            
            $rows[] = [
                'size' => [
                    'id' => 'SIZE',
                    'value_name' => $tamanho
                ],
                'measurements' => [
                    [
                        'id' => 'CHEST_CIRCUMFERENCE',
                        'value' => $measures['chest']
                    ],
                    [
                        'id' => 'WAIST_CIRCUMFERENCE', 
                        'value' => $measures['waist']
                    ],
                    [
                        'id' => 'LENGTH',
                        'value' => $measures['length']
                    ]
                ]
            ];
        }
        
        return $rows;
    }
    
    /**
     * Mapeia categoria para domain_id
     */
    private function getDomainIdFromCategory($categoria) {
        // Domain IDs corretos baseados na documentação do ML
        $mapping = [
            'MLB31447' => 'SHIRTS', // Camisetas e regatas
            'MLB31099' => 'JEANS',   // Jeans
            'MLB31448' => 'SHIRTS'   // Polos (também usa SHIRTS)
        ];
        
        return $mapping[$categoria] ?? 'SHIRTS';
    }
    
    /**
     * Salva tabela no banco local para cache
     */
    private function saveChartToDatabase($chartId, $chartName, $tamanhos, $categoria, $gender) {
        $sizesStr = implode(',', $tamanhos);
        
        $query = "INSERT INTO ml_size_charts (chart_id, chart_name, sizes, categoria, gender, created_at) 
                  VALUES ($1, $2, $3, $4, $5, NOW())
                  ON CONFLICT (chart_id) DO UPDATE SET 
                  chart_name = EXCLUDED.chart_name,
                  sizes = EXCLUDED.sizes,
                  updated_at = NOW()";
        
        $result = pg_query_params($this->conexao, $query, [
            $chartId, $chartName, $sizesStr, $categoria, $gender
        ]);
        
        if (!$result) {
            error_log("SIZE_CHART: Erro ao salvar no banco: " . pg_last_error($this->conexao));
        } else {
            error_log("SIZE_CHART: Tabela salva no banco: $chartId");
        }
    }
    
    /**
     * Obtém SIZE_GRID_ROW_ID para um tamanho específico
     */
    public function getRowIdForSize($chartId, $size) {
        // Para implementação completa, seria necessário buscar via API
        // Por enquanto, usar hash determinístico
        return hash('crc32', $chartId . '_' . $size);
    }
    
    /**
     * Mapeia nome do gênero para value_id do ML
     */
    private function getGenderValueId($gender) {
        $mapping = [
            'Masculino' => '339666',
            'Feminino' => '339665',
            'Meninas' => '339668',
            'Meninos' => '339667',
            'Bebês' => '371795',
            'Sem gênero' => '110461'
        ];
        
        return $mapping[$gender] ?? '339666'; // Default: Masculino
    }
}

/**
 * Factory function
 */
function getMLSizeChartManager() {
    static $instance = null;
    if ($instance === null) {
        global $conexao;
        $tokenManager = getMLTokenManager();
        $instance = new MLSizeChartManager($tokenManager, $conexao);
    }
    return $instance;
}

/**
 * Criar tabela necessária (executar uma vez)
 */
function createSizeChartTable() {
    global $conexao;
    
    $sql = "CREATE TABLE IF NOT EXISTS ml_size_charts (
        id SERIAL PRIMARY KEY,
        chart_id VARCHAR(50) UNIQUE NOT NULL,
        chart_name VARCHAR(255) NOT NULL,
        sizes TEXT NOT NULL,
        categoria VARCHAR(20) NOT NULL,
        gender VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
    )";
    
    $result = pg_query($conexao, $sql);
    if (!$result) {
        error_log("SIZE_CHART: Erro ao criar tabela: " . pg_last_error($conexao));
        return false;
    }
    
    error_log("SIZE_CHART: Tabela ml_size_charts criada/verificada");
    return true;
}
?>