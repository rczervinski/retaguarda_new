<?php
/**
 * Teste do wrapper
 */

// Dados do POST
$postData = [
    'codigo_gtin' => '2002002002',
    'action' => 'export',
    'preco_ajustado' => '',
    'ml_attr_BRAND[id]' => 'BRAND',
    'ml_attr_BRAND[value_name]' => 'Autoridade',
    'ml_attr_GENDER[id]' => 'GENDER',
    'ml_attr_GENDER[value_id]' => '339666',
    'ml_attr_GENDER[value_name]' => 'Masculino',
    'ml_attr_GARMENT_TYPE[id]' => 'GARMENT_TYPE',
    'ml_attr_GARMENT_TYPE[value_id]' => '12038970',
    'ml_attr_GARMENT_TYPE[value_name]' => 'Camiseta',
    'ml_attr_COLOR[id]' => 'COLOR',
    'ml_attr_COLOR[value_id]' => '52049',
    'ml_attr_COLOR[value_name]' => 'Preto',
    'ml_attr_SIZE[id]' => 'SIZE',
    'ml_attr_SIZE[value_id]' => '10490141',
    'ml_attr_SIZE[value_name]' => 'G',
    'ml_attr_SLEEVE_TYPE[id]' => 'SLEEVE_TYPE',
    'ml_attr_SLEEVE_TYPE[value_id]' => '466804',
    'ml_attr_SLEEVE_TYPE[value_name]' => 'Curta',
    'ml_attr_MODEL[id]' => 'MODEL',
    'ml_attr_MODEL[value_name]' => 'Boxy'
];

// Converter para formato de query string
$postString = http_build_query($postData);

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://249fafeab50f.ngrok-free.app/retaguarda/mercadolivre/export_wrapper.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'ngrok-skip-browser-warning: true'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Executar
echo "Testando via wrapper...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";
?>