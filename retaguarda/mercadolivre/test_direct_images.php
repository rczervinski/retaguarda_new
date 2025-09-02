<?php
/**
 * Teste direto do sistema de imagens
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DIRETO DE IMAGENS ===\n\n";

$uploadDir = '../../upload/'; // ✅ CORRIGIDO
$codigoGtin = '204';

echo "1. Verificando pasta upload:\n";
echo "   Caminho: $uploadDir\n";
echo "   Caminho absoluto: " . realpath($uploadDir) . "\n";
echo "   Existe: " . (is_dir($uploadDir) ? 'SIM' : 'NÃO') . "\n";
echo "   Legível: " . (is_readable($uploadDir) ? 'SIM' : 'NÃO') . "\n\n";

if (!is_dir($uploadDir)) {
    echo "❌ ERRO: Pasta upload não encontrada!\n";
    echo "Tentando outros caminhos...\n";
    
    $alternatePaths = [
        './upload/',
        '../../upload/',
        '/upload/',
        'upload/'
    ];
    
    foreach ($alternatePaths as $path) {
        echo "Testando: $path -> " . (is_dir($path) ? 'EXISTE' : 'não existe') . "\n";
    }
    exit;
}

echo "2. Listando arquivos na pasta:\n";
$allFiles = scandir($uploadDir);
foreach ($allFiles as $file) {
    if ($file !== '.' && $file !== '..') {
        $filepath = $uploadDir . $file;
        echo "   - $file (tamanho: " . filesize($filepath) . " bytes)\n";
    }
}

echo "\n3. Testando busca específica para GTIN '$codigoGtin':\n";

$supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

foreach ($allFiles as $filename) {
    if ($filename === '.' || $filename === '..') {
        continue;
    }
    
    echo "   Analisando: $filename\n";
    
    // Verificar extensão
    $pathInfo = pathinfo($filename);
    $extension = strtolower($pathInfo['extension'] ?? '');
    $basename = $pathInfo['filename'];
    
    echo "     - Basename: '$basename'\n";
    echo "     - Extensão: '$extension'\n";
    echo "     - Extensão suportada: " . (in_array($extension, $supportedExtensions) ? 'SIM' : 'NÃO') . "\n";
    
    if (!in_array($extension, $supportedExtensions)) {
        echo "     - ❌ Extensão não suportada\n\n";
        continue;
    }
    
    // Testar padrão principal
    if ($basename === $codigoGtin) {
        echo "     - ✅ MATCH! Imagem principal encontrada\n";
        echo "     - Posição: 1\n";
        echo "     - URL seria: http://localhost/upload/$filename\n\n";
        continue;
    }
    
    // Testar padrão secundário
    $pattern = '/^' . preg_quote($codigoGtin, '/') . '_(\d+)$/';
    if (preg_match($pattern, $basename, $matches)) {
        $position = intval($matches[1]);
        echo "     - ✅ MATCH! Imagem secundária encontrada\n";
        echo "     - Posição: $position\n";
        echo "     - URL seria: http://localhost/upload/$filename\n\n";
        continue;
    }
    
    echo "     - ❌ Não corresponde ao GTIN '$codigoGtin'\n\n";
}

echo "4. Teste final - Verificação manual:\n";
$testFiles = [
    $codigoGtin . '.jpg',
    $codigoGtin . '.png', 
    $codigoGtin . '.webp',
    $codigoGtin . '_2.jpg'
];

foreach ($testFiles as $testFile) {
    $testPath = $uploadDir . $testFile;
    echo "   $testFile: " . (file_exists($testPath) ? '✅ EXISTE' : '❌ não existe') . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
