<?php
/**
 * Teste de Conversão de Imagens WEBP para JPEG
 */

require_once 'image_converter.php';
require_once 'image_manager.php';

echo "<h1>🖼️ Teste de Conversão de Imagens para Mercado Livre</h1>";

// Verificar se GD está instalado
if (!extension_loaded('gd')) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Extensão GD não está instalada!</strong><br>";
    echo "Instale a extensão GD do PHP para converter imagens.";
    echo "</div>";
    exit;
}

// Verificar suporte a WEBP
$gdInfo = gd_info();
echo "<h2>📋 Informações do GD</h2>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Versão GD:</strong> " . $gdInfo['GD Version'] . "<br>";
echo "<strong>Suporte WEBP:</strong> " . ($gdInfo['WebP Support'] ? '✅ Sim' : '❌ Não') . "<br>";
echo "<strong>Suporte JPEG:</strong> " . ($gdInfo['JPEG Support'] ? '✅ Sim' : '❌ Não') . "<br>";
echo "<strong>Suporte PNG:</strong> " . ($gdInfo['PNG Support'] ? '✅ Sim' : '❌ Não') . "<br>";
echo "</div>";

if (!$gdInfo['WebP Support']) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Suporte WEBP não disponível!</strong><br>";
    echo "Atualize o PHP ou recompile com suporte WEBP.";
    echo "</div>";
    exit;
}

// Testar conversão
$converter = getMLImageConverter();
$imageManager = getMLImageManager();

echo "<h2>🔍 Buscando Imagens WEBP</h2>";

$uploadDir = '../../upload/';
$webpFiles = glob($uploadDir . '*.webp');

if (empty($webpFiles)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "⚠️ <strong>Nenhuma imagem WEBP encontrada em:</strong> $uploadDir<br>";
    echo "Faça upload de algumas imagens WEBP para testar a conversão.";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>Encontradas " . count($webpFiles) . " imagens WEBP:</strong><br>";
    foreach ($webpFiles as $file) {
        echo "- " . basename($file) . " (" . round(filesize($file) / 1024, 2) . " KB)<br>";
    }
    echo "</div>";
}

// Testar conversão para GTINs específicos
$testGtins = ['07891108080307', '07891108081366', '07891108080000'];

echo "<h2>🔄 Testando Conversão por GTIN</h2>";

foreach ($testGtins as $gtin) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>GTIN: $gtin</h3>";
    
    // Buscar imagens WEBP do GTIN
    $gtinWebpFiles = glob($uploadDir . $gtin . '*.webp');
    
    if (empty($gtinWebpFiles)) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ Nenhuma imagem WEBP encontrada para este GTIN";
        echo "</div>";
    } else {
        echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
        echo "📁 <strong>Imagens WEBP encontradas:</strong><br>";
        foreach ($gtinWebpFiles as $file) {
            echo "- " . basename($file) . "<br>";
        }
        echo "</div>";
        
        // Tentar conversão
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
        echo "🔄 <strong>Convertendo...</strong><br>";
        
        $convertedImages = $converter->convertGtinImages($gtin);
        
        if (empty($convertedImages)) {
            echo "❌ Nenhuma imagem foi convertida<br>";
        } else {
            echo "✅ Convertidas " . count($convertedImages) . " imagens:<br>";
            foreach ($convertedImages as $converted) {
                $filename = basename($converted);
                $filesize = round(filesize($converted) / 1024, 2);
                echo "- $filename ($filesize KB)<br>";
                
                // Validar para ML
                $validation = $converter->validateImageForML($converted);
                if ($validation['valid']) {
                    echo "  ✅ Válida para ML<br>";
                } else {
                    echo "  ❌ Inválida: " . $validation['error'] . "<br>";
                }
            }
        }
        echo "</div>";
        
        // Testar prepareImagesForML
        echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
        echo "🎯 <strong>Teste prepareImagesForML:</strong><br>";
        
        $mlImages = $imageManager->prepareImagesForML($gtin);
        
        if (empty($mlImages)) {
            echo "❌ Nenhuma imagem preparada para ML<br>";
        } else {
            echo "✅ Preparadas " . count($mlImages) . " imagens para ML:<br>";
            foreach ($mlImages as $img) {
                echo "- " . $img['source'] . "<br>";
            }
        }
        echo "</div>";
    }
    
    echo "</div>";
}

// Informações sobre formatos suportados
echo "<h2>📚 Informações sobre Formatos</h2>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<strong>Formatos aceitos pelo Mercado Livre:</strong><br>";
echo "✅ JPG/JPEG - Recomendado (menor tamanho, boa qualidade)<br>";
echo "✅ PNG - Aceito (maior tamanho, melhor para transparência)<br>";
echo "❌ WEBP - NÃO aceito (formato moderno, mas ML não suporta)<br>";
echo "❌ GIF - NÃO aceito<br><br>";

echo "<strong>Especificações técnicas:</strong><br>";
echo "- Tamanho máximo: 10 MB<br>";
echo "- Resolução recomendada: 1200 x 1200 px<br>";
echo "- Resolução mínima: 500 x 500 px<br>";
echo "- Qualidade JPEG: 90% (boa qualidade vs tamanho)<br>";
echo "- Fundo: Branco recomendado<br>";
echo "</div>";

// Limpeza de arquivos antigos
echo "<h2>🧹 Limpeza de Arquivos</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>Limpeza automática:</strong><br>";
echo "- Arquivos convertidos são mantidos por 7 dias<br>";
echo "- Limpeza automática remove arquivos antigos<br>";
echo "- Para limpar manualmente, execute: <code>\$converter->cleanOldConvertedFiles();</code>";
echo "</div>";

echo "<h2>🚀 Próximos Passos</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "1. ✅ <strong>Conversão automática implementada</strong><br>";
echo "2. ✅ <strong>Validação de imagens implementada</strong><br>";
echo "3. ✅ <strong>Integração com image_manager implementada</strong><br>";
echo "4. 🔄 <strong>Teste a exportação do produto agora!</strong><br>";
echo "5. 📈 <strong>Opcional:</strong> Implementar upload direto para CDN do ML<br>";
echo "</div>";
?>
