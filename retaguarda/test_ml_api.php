<?php
/**
 * Teste direto da API do ML sem SSL
 */

$categoryId = 'MLB31447';
$url = "https://api.mercadolibre.com/categories/$categoryId/attributes";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
    exit;
}

$data = json_decode($response, true);
if (!$data) {
    echo "Erro ao decodificar JSON\n";
    echo "Response: $response\n";
    exit;
}

echo "Total de atributos: " . count($data) . "\n";

// Procurar SIZE_GRID_ID
$sizeGridAttr = null;
foreach ($data as $attr) {
    if ($attr['id'] === 'SIZE_GRID_ID') {
        $sizeGridAttr = $attr;
        break;
    }
}

if (!$sizeGridAttr) {
    echo "\n❌ SIZE_GRID_ID não encontrado!\n";
    echo "Atributos que contêm 'SIZE' ou 'GRID':\n";
    foreach ($data as $attr) {
        if (stripos($attr['id'], 'SIZE') !== false || stripos($attr['id'], 'GRID') !== false) {
            echo "- {$attr['id']} ({$attr['name']})\n";
        }
    }
} else {
    echo "\n✅ SIZE_GRID_ID encontrado!\n";
    $values = $sizeGridAttr['values'] ?? [];
    echo "Total de grades: " . count($values) . "\n";
    
    if (!empty($values)) {
        echo "Primeiras 3 grades:\n";
        for ($i = 0; $i < min(3, count($values)); $i++) {
            $grid = $values[$i];
            echo "- ID: {$grid['id']}, Nome: {$grid['name']}\n";
        }
    }
}
?>