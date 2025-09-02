<?php
/**
 * Teste do sistema otimizado de imagens
 */

require_once 'image_manager.php';

header('Content-Type: application/json; charset=utf-8');

$codigoGtin = $_GET['gtin'] ?? '204'; // GTIN padrão para teste

$imageManager = getMLImageManager();

echo "=== TESTE DO SISTEMA DE IMAGENS OTIMIZADO ===\n\n";

// Debug da pasta upload
echo "📁 Verificando pasta upload:\n";
$uploadDir = '../upload/';
echo "- Caminho: $uploadDir\n";
echo "- Existe: " . (is_dir($uploadDir) ? 'SIM' : 'NÃO') . "\n";
echo "- Legível: " . (is_readable($uploadDir) ? 'SIM' : 'NÃO') . "\n";

if (is_dir($uploadDir)) {
    $allFiles = scandir($uploadDir);
    echo "- Total de arquivos: " . (count($allFiles) - 2) . "\n"; // -2 para . e ..
    echo "- Arquivos encontrados:\n";
    foreach ($allFiles as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  * $file\n";
        }
    }
}

echo "\n";

// Teste 1: Buscar imagens
echo "🔍 Buscando imagens para GTIN: $codigoGtin\n";
$start = microtime(true);
$images = $imageManager->findProductImages($codigoGtin);
$end = microtime(true);
$time = round(($end - $start) * 1000, 2);

echo "⏱️ Tempo de busca: {$time}ms\n";
echo "📸 Imagens encontradas: " . count($images) . "\n\n";

if (!empty($images)) {
    foreach ($images as $image) {
        echo "- Posição {$image['position']}: {$image['filename']} ({$image['extension']}, " . round($image['size']/1024, 1) . "KB)\n";
        echo "  URL: {$image['url']}\n";
    }
} else {
    echo "❌ Nenhuma imagem encontrada\n";
}

echo "\n";

// Teste 2: Formato para ML
echo "🎯 Formato para Mercado Livre:\n";
$mlImages = $imageManager->prepareImagesForML($codigoGtin);
echo json_encode($mlImages, JSON_PRETTY_PRINT);

echo "\n\n";

// Teste 3: Estatísticas
echo "📊 Estatísticas:\n";
$stats = $imageManager->getImageStats($codigoGtin);
echo json_encode($stats, JSON_PRETTY_PRINT);

echo "\n\n";

// Teste 4: Verificação rápida
echo "✅ Tem imagens: " . ($imageManager->hasImages($codigoGtin) ? 'SIM' : 'NÃO') . "\n";

echo "\n=== COMPARAÇÃO DE PERFORMANCE ===\n";

// Simular método antigo (força bruta)
echo "🐌 Método antigo (simulado):\n";
$start = microtime(true);
$oldMethodCount = 0;
$extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
for ($pos = 1; $pos <= 10; $pos++) {
    foreach ($extensions as $ext) {
        $filename = $pos === 1 ? "$codigoGtin.$ext" : "{$codigoGtin}_{$pos}.$ext";
        if (file_exists("../upload/$filename")) {
            $oldMethodCount++;
        }
    }
}
$end = microtime(true);
$oldTime = round(($end - $start) * 1000, 2);

echo "⏱️ Tempo método antigo: {$oldTime}ms\n";
echo "📸 Imagens encontradas: $oldMethodCount\n";

echo "\n🚀 Método novo:\n";
echo "⏱️ Tempo método novo: {$time}ms\n";
echo "📸 Imagens encontradas: " . count($images) . "\n";

$improvement = $oldTime > 0 ? round((($oldTime - $time) / $oldTime) * 100, 1) : 0;
echo "📈 Melhoria de performance: {$improvement}%\n";

echo "\n=== TESTE DE DIFERENTES GTINs ===\n";

$testGtins = ['204', '123', '456', '999'];
foreach ($testGtins as $testGtin) {
    $testImages = $imageManager->findProductImages($testGtin);
    echo "GTIN $testGtin: " . count($testImages) . " imagens\n";
}
?>
