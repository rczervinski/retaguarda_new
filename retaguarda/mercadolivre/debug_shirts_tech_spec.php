<?php
/**
 * Debug específico para obter especificação técnica do domínio SHIRTS
 */

require_once '../conexao.php';
require_once 'token_manager.php';

echo "=== DEBUG SHIRTS TECH SPEC ===\n\n";

try {
    $tokenManager = getMLTokenManager();
    $tokenResult = $tokenManager->getValidToken();
    
    if (!$tokenResult['success']) {
        echo "❌ ERRO: Token inválido\n";
        exit;
    }
    
    echo "✅ Token válido obtido\n\n";
    
    // Buscar especificação técnica do domínio SHIRTS
    echo "1. BUSCANDO ESPECIFICAÇÃO TÉCNICA DO DOMÍNIO SHIRTS:\n";
    
    $techSpecData = [
        'attributes' => [
            [
                'id' => 'BRAND',
                'name' => 'Marca',
                'value_name' => 'Lacoste',
                'value_id' => '23001'
            ],
            [
                'id' => 'GENDER',
                'name' => 'Gênero',
                'value_name' => 'Masculino',
                'value_id' => '339666'
            ]
        ]
    ];
    
    echo "Dados enviados: " . json_encode($techSpecData, JSON_PRETTY_PRINT) . "\n\n";
    
    $response = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/domains/MLB-SHIRTS/technical_specs?section=grids',
        'POST',
        $techSpecData,
        $tokenResult['token']
    );
    
    if ($response['success']) {
        echo "✅ SUCESSO: Especificação técnica obtida\n\n";
        
        $data = $response['data'];
        
        // Procurar por FILTRABLE_SIZE
        echo "2. PROCURANDO ATRIBUTO FILTRABLE_SIZE:\n";
        
        function findFiltrableSize($data, $path = '') {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $currentPath = $path ? "$path.$key" : $key;
                    
                    if ($key === 'id' && $value === 'FILTRABLE_SIZE') {
                        echo "🎯 ENCONTRADO FILTRABLE_SIZE em: $path\n";
                        return $data; // Retorna o objeto completo
                    }
                    
                    if (is_array($value) || is_object($value)) {
                        $result = findFiltrableSize($value, $currentPath);
                        if ($result) return $result;
                    }
                }
            }
            return null;
        }
        
        $filtrableSize = findFiltrableSize($data);
        
        if ($filtrableSize) {
            echo "✅ FILTRABLE_SIZE encontrado!\n";
            echo "Detalhes: " . json_encode($filtrableSize, JSON_PRETTY_PRINT) . "\n\n";
            
            if (isset($filtrableSize['values'])) {
                echo "3. VALORES VÁLIDOS PARA FILTRABLE_SIZE:\n";
                foreach ($filtrableSize['values'] as $value) {
                    echo "- ID: " . ($value['id'] ?? 'N/A') . " | Nome: " . ($value['name'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "❌ FILTRABLE_SIZE não encontrado na especificação técnica\n";
            echo "Estrutura completa:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        
    } else {
        echo "❌ ERRO ao obter especificação técnica:\n";
        echo "HTTP Code: " . $response['http_code'] . "\n";
        echo "Resposta: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG CONCLUÍDO ===\n";
?>
