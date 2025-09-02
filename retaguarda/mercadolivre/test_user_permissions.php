<?php
/**
 * Testa permissões do usuário no Mercado Livre
 */

require_once '../conexao.php';
require_once 'token_manager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tokenManager = getMLTokenManager();
    
    // 1. Testar informações do usuário
    echo "=== TESTE DE PERMISSÕES MERCADO LIVRE ===\n\n";
    
    $userResponse = $tokenManager->makeMLRequest('https://api.mercadolibre.com/users/me');
    
    if ($userResponse['success']) {
        $userData = $userResponse['data'];
        echo "✅ Usuário conectado:\n";
        echo "- ID: " . $userData['id'] . "\n";
        echo "- Nickname: " . $userData['nickname'] . "\n";
        echo "- Email: " . ($userData['email'] ?? 'N/A') . "\n";
        echo "- Site: " . $userData['site_id'] . "\n";
        echo "- Status: " . $userData['status'] . "\n";
        echo "- Seller reputation: " . json_encode($userData['seller_reputation'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ Erro ao buscar dados do usuário: " . json_encode($userResponse) . "\n\n";
    }
    
    // 2. Testar categorias disponíveis
    echo "=== TESTANDO CATEGORIAS ===\n";
    $categoriesResponse = $tokenManager->makeMLRequest('https://api.mercadolibre.com/sites/MLB/categories');
    
    if ($categoriesResponse['success']) {
        echo "✅ Categorias acessíveis: " . count($categoriesResponse['data']) . " categorias\n";
        
        // Mostrar algumas categorias relevantes
        $relevantCategories = ['MLB1574', 'MLB1499', 'MLB1000'];
        foreach ($relevantCategories as $catId) {
            $catResponse = $tokenManager->makeMLRequest("https://api.mercadolibre.com/categories/$catId");
            if ($catResponse['success']) {
                echo "- $catId: " . $catResponse['data']['name'] . "\n";
            }
        }
    } else {
        echo "❌ Erro ao buscar categorias\n";
    }
    
    echo "\n=== TESTANDO LIMITES DE LISTAGEM ===\n";
    
    // 3. Testar limites de listagem
    $limitsResponse = $tokenManager->makeMLRequest('https://api.mercadolibre.com/users/' . $userData['id'] . '/classifieds_promotion_packs');
    
    if ($limitsResponse['success']) {
        echo "✅ Limites de listagem acessíveis\n";
        echo "Dados: " . json_encode($limitsResponse['data']) . "\n";
    } else {
        echo "⚠️ Não foi possível acessar limites de listagem\n";
    }
    
    echo "\n=== TESTANDO CRIAÇÃO DE ITEM SIMPLES ===\n";
    
    // 4. Testar criação de item muito simples
    $testItem = [
        'title' => 'TESTE - Produto de Teste',
        'category_id' => 'MLB1144', // Agro > Outros (categoria folha)
        'price' => 10.00,
        'currency_id' => 'BRL',
        'available_quantity' => 1,
        'condition' => 'new',
        'listing_type_id' => 'bronze',
        'buying_mode' => 'buy_it_now'
    ];
    
    echo "JSON de teste: " . json_encode($testItem, JSON_PRETTY_PRINT) . "\n\n";
    
    $testResponse = $tokenManager->makeMLRequest(
        'https://api.mercadolibre.com/items',
        'POST',
        $testItem
    );
    
    if ($testResponse['success']) {
        echo "✅ SUCESSO! Item de teste criado: " . $testResponse['data']['id'] . "\n";
        echo "Link: " . $testResponse['data']['permalink'] . "\n";
        
        // Deletar o item de teste
        $deleteResponse = $tokenManager->makeMLRequest(
            'https://api.mercadolibre.com/items/' . $testResponse['data']['id'],
            'PUT',
            ['status' => 'closed']
        );
        
        if ($deleteResponse['success']) {
            echo "✅ Item de teste removido com sucesso\n";
        }
        
    } else {
        echo "❌ ERRO ao criar item de teste:\n";
        echo "HTTP Code: " . $testResponse['http_code'] . "\n";
        echo "Resposta: " . json_encode($testResponse['data'], JSON_PRETTY_PRINT) . "\n";
        
        // Analisar erro específico
        if (isset($testResponse['data']['message']) && $testResponse['data']['message'] === 'seller.unable_to_list') {
            echo "\n🔍 ANÁLISE DO ERRO seller.unable_to_list:\n";
            if (isset($testResponse['data']['cause'])) {
                echo "Causa: " . json_encode($testResponse['data']['cause'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}
?>
