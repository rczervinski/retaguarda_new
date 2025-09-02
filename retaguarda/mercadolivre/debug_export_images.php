<?php
/**
 * Debug específico da exportação de imagens
 */

require_once 'image_manager.php';

header('Content-Type: text/plain; charset=utf-8');

$codigoGtin = '204';

echo "=== DEBUG EXPORTAÇÃO DE IMAGENS ===\n\n";

echo "1. Testando ImageManager:\n";
$imageManager = getMLImageManager();

echo "2. Chamando findProductImages('$codigoGtin'):\n";
$images = $imageManager->findProductImages($codigoGtin);
echo "   Resultado: " . count($images) . " imagens\n";

if (!empty($images)) {
    foreach ($images as $i => $image) {
        echo "   Imagem $i:\n";
        echo "     - Position: {$image['position']}\n";
        echo "     - Filename: {$image['filename']}\n";
        echo "     - URL: {$image['url']}\n";
        echo "     - Extension: {$image['extension']}\n";
        echo "     - Size: {$image['size']} bytes\n";
        echo "     - Is main: " . ($image['is_main'] ? 'SIM' : 'NÃO') . "\n\n";
    }
} else {
    echo "   ❌ Nenhuma imagem encontrada!\n\n";
}

echo "3. Chamando prepareImagesForML('$codigoGtin'):\n";
$mlImages = $imageManager->prepareImagesForML($codigoGtin);
echo "   Resultado: " . count($mlImages) . " imagens para ML\n";

if (!empty($mlImages)) {
    foreach ($mlImages as $i => $mlImage) {
        echo "   ML Imagem $i:\n";
        echo "     - Source: {$mlImage['source']}\n\n";
    }
} else {
    echo "   ❌ Nenhuma imagem preparada para ML!\n\n";
}

echo "4. JSON que seria enviado:\n";
$jsonData = [
    'title' => 'ABOBRINHA 100G',
    'category_id' => 'MLB432663',
    'price' => 8
];

if (!empty($mlImages)) {
    $jsonData['pictures'] = $mlImages;
}

echo json_encode($jsonData, JSON_PRETTY_PRINT);

echo "\n\n5. Verificação manual da pasta:\n";
$uploadDir = '../../upload/';
echo "   Pasta: $uploadDir\n";
echo "   Existe: " . (is_dir($uploadDir) ? 'SIM' : 'NÃO') . "\n";

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "   Arquivos:\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "     - $file\n";
        }
    }
}

echo "\n6. Teste direto de arquivo específico:\n";
$testFile = $uploadDir . '204.webp';
echo "   Arquivo: $testFile\n";
echo "   Existe: " . (file_exists($testFile) ? 'SIM' : 'NÃO') . "\n";
if (file_exists($testFile)) {
    echo "   Tamanho: " . filesize($testFile) . " bytes\n";
    echo "   Modificado: " . date('Y-m-d H:i:s', filemtime($testFile)) . "\n";
}

echo "\n=== FIM DO DEBUG ===\n";
?>
