<?php
/**
 * Teste das APIs de categoria do ML
 */

require_once 'token_manager.php';
require_once '../conexao.php';

header('Content-Type: application/json; charset=utf-8');

$categoryId = $_GET['category_id'] ?? 'MLB1676';

try {
    $tokenManager = new MLTokenManager($conexao);
    $tokenResult = $tokenManager->getValidToken();
    
    if (isset($tokenResult['error'])) {
        throw new Exception('Token error: ' . $tokenResult['error']);
    }
    
    $accessToken = $tokenResult['access_token'];
    
    $results = [];
    
    // 1. Testar /categories/{id}
    $url1 = "https://api.mercadolibre.com/categories/{$categoryId}";
    $response1 = makeRequest($url1, $accessToken);
    $results['category_info'] = [
        'url' => $url1,
        'status' => $response1['status'],
        'data' => $response1['data']
    ];
    
    // 2. Testar /categories/{id}/attributes
    $url2 = "https://api.mercadolibre.com/categories/{$categoryId}/attributes";
    $response2 = makeRequest($url2, $accessToken);
    $results['attributes'] = [
        'url' => $url2,
        'status' => $response2['status'],
        'data' => $response2['data']
    ];
    
    // 3. Testar /categories/{id}/technical_specs/input
    $url3 = "https://api.mercadolibre.com/categories/{$categoryId}/technical_specs/input";
    $response3 = makeRequest($url3, $accessToken);
    $results['technical_specs'] = [
        'url' => $url3,
        'status' => $response3['status'],
        'data' => $response3['data']
    ];
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

function makeRequest($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => $httpCode === 200 ? json_decode($response, true) : $response
    ];
}
?>
