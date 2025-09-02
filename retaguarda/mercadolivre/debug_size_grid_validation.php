<?php
/**
 * Debug: Verificar se a tabela SIZE_GRID está válida
 */

require_once 'conexaoml.php';
require_once 'token_manager.php';

$chartId = '3508744';

echo "<h2>🔍 Verificação da Tabela SIZE_GRID: $chartId</h2>";

try {
    $tokenManager = getMLTokenManager();
    
    // 1. Verificar se a tabela existe
    echo "<h3>1. Consultando tabela...</h3>";
    $response = $tokenManager->makeMLRequest(
        "https://api.mercadolibre.com/catalog/charts/$chartId",
        'GET'
    );
    
    if ($response['success']) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✅ Tabela encontrada!</strong><br>";
        echo "<pre>" . json_encode($response['data'], JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
        $chart = $response['data'];
        
        // 2. Verificar status da tabela
        echo "<h3>2. Status da tabela:</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $chart['id'] . "</li>";
        echo "<li><strong>Nome:</strong> " . ($chart['names']['MLB'] ?? 'N/A') . "</li>";
        echo "<li><strong>Domínio:</strong> " . $chart['domain_id'] . "</li>";
        echo "<li><strong>Site:</strong> " . $chart['site_id'] . "</li>";
        echo "<li><strong>Tipo:</strong> " . $chart['type'] . "</li>";
        echo "<li><strong>Seller ID:</strong> " . $chart['seller_id'] . "</li>";
        echo "<li><strong>Status:</strong> " . ($chart['chart_status'] ?? 'ACTIVE') . "</li>";
        echo "</ul>";
        
        // 3. Verificar rows
        echo "<h3>3. Rows da tabela:</h3>";
        if (!empty($chart['rows'])) {
            foreach ($chart['rows'] as $row) {
                echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
                echo "<strong>Row ID:</strong> " . $row['id'] . "<br>";
                echo "<strong>Atributos:</strong><br>";
                foreach ($row['attributes'] as $attr) {
                    echo "- " . $attr['id'] . ": " . ($attr['values'][0]['name'] ?? 'N/A') . "<br>";
                }
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ Nenhuma row encontrada na tabela!";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<strong>❌ Erro ao consultar tabela:</strong><br>";
        echo "HTTP Code: " . $response['http_code'] . "<br>";
        echo "Erro: " . json_encode($response['data'], JSON_PRETTY_PRINT);
        echo "</div>";
    }
    
    // 4. Verificar domínio da categoria MLB31447
    echo "<h3>4. Verificando domínio da categoria MLB31447:</h3>";

    $categoryResponse = $tokenManager->makeMLRequest(
        "https://api.mercadolibre.com/categories/MLB31447",
        'GET'
    );

    if ($categoryResponse['success']) {
        $category = $categoryResponse['data'];
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Categoria MLB31447:</strong><br>";
        echo "- Nome: " . $category['name'] . "<br>";
        echo "- Domain ID: " . ($category['domain_id'] ?? 'N/A') . "<br>";
        echo "- Attribute Types: " . implode(', ', $category['attribute_types'] ?? []) . "<br>";
        echo "</div>";

        if (isset($category['domain_id']) && $category['domain_id'] !== 'SHIRTS') {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo "⚠️ <strong>INCOMPATIBILIDADE DETECTADA!</strong><br>";
            echo "Tabela criada para domínio: <strong>SHIRTS</strong><br>";
            echo "Categoria pertence ao domínio: <strong>" . $category['domain_id'] . "</strong>";
            echo "</div>";
        }
    }

    // 5. Testar criação de produto simples com a tabela
    echo "<h3>5. Testando uso da tabela em produto simples:</h3>";
    
    $testProduct = [
        'title' => 'TESTE SIZE_GRID - NÃO OFERTAR',
        'category_id' => 'MLB31447',
        'price' => 10,
        'currency_id' => 'BRL',
        'available_quantity' => 1,
        'condition' => 'new',
        'listing_type_id' => 'bronze',
        'buying_mode' => 'buy_it_now',
        'attributes' => [
            [
                'id' => 'BRAND',
                'value_name' => 'Autoridade'
            ],
            [
                'id' => 'GENDER',
                'value_id' => '339666',
                'value_name' => 'Masculino'
            ],
            [
                'id' => 'SIZE_GRID_ID',
                'value_id' => $chartId,
                'value_name' => 'Tab Masculino Autoridade GG'
            ],
            [
                'id' => 'AGE_GROUP',
                'value_id' => '6725189',
                'value_name' => 'Adultos'
            ],
            [
                'id' => 'SIZE_GRID_ROW_ID',
                'value_id' => $chartId . ':1',
                'value_name' => 'GG'
            ]
        ]
    ];
    
    echo "<strong>JSON de teste:</strong><br>";
    echo "<pre>" . json_encode($testProduct, JSON_PRETTY_PRINT) . "</pre>";
    
    $testResponse = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/items',
        'POST',
        $testProduct
    );
    
    if ($testResponse['success']) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✅ Produto de teste criado com sucesso!</strong><br>";
        echo "Item ID: " . $testResponse['data']['id'];
        echo "</div>";
        
        // Deletar o produto de teste
        $deleteResponse = $tokenManager->makeMLRequest(
            'https://api.mercadolibre.com/items/' . $testResponse['data']['id'],
            'PUT',
            ['deleted' => 'true']
        );
        
        if ($deleteResponse['success']) {
            echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
            echo "🗑️ Produto de teste deletado com sucesso.";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<strong>❌ Erro ao criar produto de teste:</strong><br>";
        echo "HTTP Code: " . $testResponse['http_code'] . "<br>";
        echo "Erro: " . json_encode($testResponse['data'], JSON_PRETTY_PRINT);
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ Erro:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
