<?php
/**
 * Sistema inteligente de mapeamento de erros do Mercado Livre
 * Estratégia híbrida: mapeamento manual + análise automática + aprendizado
 */

class MLErrorMapper {
    
    private $knownErrors = [];
    private $conexao;
    
    public function __construct($conexao) {
        $this->conexao = $conexao;
        $this->loadKnownErrors();
    }
    
    /**
     * Mapeia erros do ML para soluções em português
     */
    public function mapErrors($mlErrors) {
        $mappedErrors = [];
        
        foreach ($mlErrors as $error) {
            $mapped = $this->mapSingleError($error);
            $mappedErrors[] = $mapped;
            
            // Salvar erro para aprendizado se for novo
            $this->saveErrorForLearning($error, $mapped);
        }
        
        return $mappedErrors;
    }
    
    /**
     * Mapeia um erro específico
     */
    private function mapSingleError($error) {
        $code = $error['code'] ?? '';
        $message = $error['message'] ?? '';
        $references = $error['references'] ?? [];
        
        // 1. Tentar mapeamento manual primeiro
        if (isset($this->knownErrors[$code])) {
            $known = $this->knownErrors[$code];
            return [
                'original' => $error,
                'type' => $known['type'],
                'title' => $known['title'],
                'description' => $known['description'],
                'solution' => $known['solution'],
                'field_type' => $known['field_type'],
                'priority' => $known['priority'],
                'auto_fixable' => $known['auto_fixable'],
                'source' => 'manual_mapping'
            ];
        }
        
        // 2. Análise automática baseada em referências
        $autoMapped = $this->autoMapError($error);
        if ($autoMapped) {
            return $autoMapped;
        }
        
        // 3. Fallback genérico
        return [
            'original' => $error,
            'type' => 'unknown',
            'title' => 'Erro não mapeado',
            'description' => $message,
            'solution' => 'Verifique a documentação do Mercado Livre',
            'field_type' => 'generic',
            'priority' => 'medium',
            'auto_fixable' => false,
            'source' => 'fallback'
        ];
    }
    
    /**
     * Análise automática baseada em padrões
     */
    private function autoMapError($error) {
        $code = $error['code'] ?? '';
        $message = $error['message'] ?? '';
        $references = $error['references'] ?? [];
        
        // Padrões de análise automática
        $patterns = [
            // Imagens
            [
                'pattern' => '/picture|image/i',
                'references' => ['item.pictures'],
                'mapping' => [
                    'type' => 'error',
                    'title' => 'Imagens obrigatórias',
                    'description' => 'Esta categoria requer pelo menos uma imagem do produto',
                    'solution' => 'Adicione uma ou mais imagens do produto',
                    'field_type' => 'images',
                    'priority' => 'high',
                    'auto_fixable' => false
                ]
            ],
            
            // Atributos
            [
                'pattern' => '/attribute.*missing|missing.*attribute/i',
                'references' => ['item.attributes'],
                'mapping' => [
                    'type' => 'warning',
                    'title' => 'Atributo obrigatório faltando',
                    'description' => 'Um campo obrigatório não foi preenchido',
                    'solution' => 'Preencha todos os campos obrigatórios da categoria',
                    'field_type' => 'attributes',
                    'priority' => 'high',
                    'auto_fixable' => true
                ]
            ],
            
            // Preço
            [
                'pattern' => '/price.*minimum|minimum.*price/i',
                'references' => ['item.price', 'item.category_id'],
                'mapping' => [
                    'type' => 'error',
                    'title' => 'Preço abaixo do mínimo',
                    'description' => 'O preço está abaixo do mínimo exigido pela categoria',
                    'solution' => 'Ajuste o preço para o valor mínimo da categoria',
                    'field_type' => 'price',
                    'priority' => 'high',
                    'auto_fixable' => true
                ]
            ],
            
            // Frete
            [
                'pattern' => '/shipping|frete/i',
                'references' => ['shipping.modes', 'user.shipping_preferences'],
                'mapping' => [
                    'type' => 'warning',
                    'title' => 'Configuração de frete',
                    'description' => 'Problema na configuração de frete da conta',
                    'solution' => 'Configure as opções de frete na sua conta do Mercado Livre',
                    'field_type' => 'shipping',
                    'priority' => 'medium',
                    'auto_fixable' => false
                ]
            ]
        ];
        
        // Testar padrões
        foreach ($patterns as $pattern) {
            $matchesPattern = preg_match($pattern['pattern'], $code . ' ' . $message);
            $matchesReferences = empty($pattern['references']) || 
                                array_intersect($pattern['references'], $references);
            
            if ($matchesPattern || $matchesReferences) {
                $mapping = $pattern['mapping'];
                $mapping['original'] = $error;
                $mapping['source'] = 'auto_analysis';
                return $mapping;
            }
        }
        
        return null;
    }
    
    /**
     * Carrega erros conhecidos (mapeamento manual)
     */
    private function loadKnownErrors() {
        $this->knownErrors = [
            'item.listing_type_id.requiresPictures' => [
                'type' => 'error',
                'title' => 'Imagens obrigatórias',
                'description' => 'Esta categoria ou tipo de listagem requer pelo menos uma imagem',
                'solution' => 'Adicione uma imagem do produto antes de exportar',
                'field_type' => 'images',
                'priority' => 'high',
                'auto_fixable' => false
            ],
            
            'item.price.invalid' => [
                'type' => 'error', 
                'title' => 'Preço inválido',
                'description' => 'O preço não atende aos requisitos da categoria',
                'solution' => 'Ajuste o preço conforme os requisitos da categoria',
                'field_type' => 'price',
                'priority' => 'high',
                'auto_fixable' => true
            ],
            
            'item.category_id.invalid' => [
                'type' => 'error',
                'title' => 'Categoria inválida',
                'description' => 'A categoria selecionada não é válida ou não é uma categoria folha',
                'solution' => 'Selecione uma categoria folha válida',
                'field_type' => 'category',
                'priority' => 'high',
                'auto_fixable' => true
            ],
            
            'item.attribute.missing_catalog_required' => [
                'type' => 'warning',
                'title' => 'Campo obrigatório faltando',
                'description' => 'Um campo obrigatório da categoria não foi preenchido',
                'solution' => 'Preencha o campo obrigatório indicado',
                'field_type' => 'attributes',
                'priority' => 'high',
                'auto_fixable' => true
            ],
            
            'shipping.me2_adoption_mandatory' => [
                'type' => 'warning',
                'title' => 'Mercado Envios obrigatório',
                'description' => 'Sua conta deve ter o Mercado Envios 2.0 configurado',
                'solution' => 'Configure o Mercado Envios 2.0 na sua conta do ML',
                'field_type' => 'shipping',
                'priority' => 'medium',
                'auto_fixable' => false
            ],
            
            'shipping.lost_me1_by_user' => [
                'type' => 'warning',
                'title' => 'Mercado Envios 1.0 descontinuado',
                'description' => 'O Mercado Envios 1.0 não está mais disponível',
                'solution' => 'Migre para o Mercado Envios 2.0',
                'field_type' => 'shipping',
                'priority' => 'low',
                'auto_fixable' => false
            ]
        ];
    }
    
    /**
     * Salva erro para aprendizado futuro
     */
    private function saveErrorForLearning($originalError, $mappedError) {
        try {
            $code = pg_escape_string($this->conexao, $originalError['code'] ?? '');
            $originalJson = pg_escape_string($this->conexao, json_encode($originalError));
            $mappedJson = pg_escape_string($this->conexao, json_encode($mappedError));
            $source = pg_escape_string($this->conexao, $mappedError['source']);
            
            // Verificar se já existe
            $checkQuery = "SELECT id FROM ml_error_learning WHERE error_code = '$code'";
            $result = pg_query($this->conexao, $checkQuery);
            
            if (!$result || pg_num_rows($result) == 0) {
                // Inserir novo erro
                $insertQuery = "INSERT INTO ml_error_learning 
                               (error_code, original_error, mapped_error, source, created_at, count) 
                               VALUES ('$code', '$originalJson', '$mappedJson', '$source', NOW(), 1)";
                pg_query($this->conexao, $insertQuery);
            } else {
                // Incrementar contador
                $updateQuery = "UPDATE ml_error_learning 
                               SET count = count + 1, last_seen = NOW() 
                               WHERE error_code = '$code'";
                pg_query($this->conexao, $updateQuery);
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar para aprendizado: " . $e->getMessage());
        }
    }
}

// Função helper
function getMLErrorMapper($conexao) {
    return new MLErrorMapper($conexao);
}
?>
