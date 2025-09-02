<?php
/**
 * Debug Completo de Imagens para Mercado Livre
 */

require_once 'image_converter.php';
require_once 'image_manager.php';

echo "<h1>🔍 Debug Completo de Imagens</h1>";

// GTINs para testar
$testGtins = ['07891108080307', '07891108081366', '07891108080000'];

foreach ($testGtins as $gtin) {
    echo "<div style='border: 2px solid #007bff; margin: 20px 0; padding: 20px; border-radius: 10px;'>";
    echo "<h2>🎯 GTIN: $gtin</h2>";
    
    $uploadDir = '../../upload/';
    
    // ✅ PASSO 1: Verificar arquivos existentes
    echo "<h3>📁 Arquivos Existentes</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    $allFiles = glob($uploadDir . $gtin . '*');
    if (empty($allFiles)) {
        echo "❌ <strong>Nenhum arquivo encontrado para GTIN $gtin</strong><br>";
    } else {
        echo "✅ <strong>Arquivos encontrados:</strong><br>";
        foreach ($allFiles as $file) {
            $filename = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $size = round(filesize($file) / 1024, 2);
            
            $icon = '';
            if ($extension === 'webp') $icon = '🔄'; // Precisa converter
            elseif (in_array($extension, ['jpg', 'jpeg'])) $icon = '✅'; // Pronto para ML
            elseif ($extension === 'png') $icon = '✅'; // Pronto para ML
            else $icon = '❓'; // Desconhecido
            
            echo "$icon $filename ($extension, $size KB)<br>";
        }
    }
    echo "</div>";
    
    // ✅ PASSO 2: Testar conversão
    echo "<h3>🔄 Teste de Conversão</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    $converter = getMLImageConverter();
    $convertedImages = $converter->convertGtinImages($gtin);
    
    if (empty($convertedImages)) {
        echo "⚠️ Nenhuma imagem WEBP convertida (pode não ter WEBP ou já estar convertida)<br>";
    } else {
        echo "✅ <strong>Imagens convertidas:</strong><br>";
        foreach ($convertedImages as $converted) {
            $filename = basename($converted);
            $size = round(filesize($converted) / 1024, 2);
            echo "📄 $filename ($size KB)<br>";
        }
    }
    echo "</div>";
    
    // ✅ PASSO 3: Testar image manager
    echo "<h3>🎯 Teste Image Manager</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    $imageManager = getMLImageManager();
    $mlImages = $imageManager->prepareImagesForML($gtin);
    
    if (empty($mlImages)) {
        echo "❌ <strong>NENHUMA imagem preparada para ML!</strong><br>";
        echo "Isso significa que o ML não receberá imagens para este GTIN.<br>";
    } else {
        echo "✅ <strong>Imagens preparadas para ML:</strong><br>";
        foreach ($mlImages as $i => $img) {
            $url = $img['source'];
            echo "📤 Imagem " . ($i + 1) . ": <a href='$url' target='_blank'>$url</a><br>";
            
            // Testar se URL é acessível
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200') !== false) {
                echo "   ✅ URL acessível<br>";
            } else {
                echo "   ❌ URL NÃO acessível - PROBLEMA!<br>";
            }
        }
    }
    echo "</div>";
    
    // ✅ PASSO 4: Simular JSON do ML
    echo "<h3>📋 JSON que seria enviado para ML</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    if (empty($mlImages)) {
        echo "❌ <strong>Nenhuma imagem seria enviada!</strong><br>";
        echo "O produto seria criado SEM imagens no ML.<br>";
    } else {
        echo "<strong>Seção 'pictures' do JSON:</strong><br>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        echo json_encode(['pictures' => $mlImages], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "</pre>";
        
        echo "<strong>Seção 'variations' (picture_ids):</strong><br>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        $pictureIds = array_map(function($img) { return $img['source']; }, $mlImages);
        echo json_encode(['picture_ids' => $pictureIds], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "</pre>";
    }
    echo "</div>";
    
    // ✅ PASSO 5: Verificar URLs manualmente
    echo "<h3>🌐 Teste Manual de URLs</h3>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    if (!empty($mlImages)) {
        echo "<strong>Clique nas URLs abaixo para verificar se as imagens carregam:</strong><br>";
        foreach ($mlImages as $i => $img) {
            $url = $img['source'];
            echo "🔗 <a href='$url' target='_blank' style='color: #007bff; text-decoration: underline;'>Imagem " . ($i + 1) . "</a><br>";
        }
        echo "<br><strong>Se as imagens não carregarem:</strong><br>";
        echo "- Verifique se o ngrok está funcionando<br>";
        echo "- Verifique se os arquivos existem na pasta /upload/<br>";
        echo "- Verifique permissões dos arquivos<br>";
    } else {
        echo "⚠️ Nenhuma URL para testar<br>";
    }
    echo "</div>";
    
    echo "</div>";
}

// ✅ Informações gerais
echo "<div style='border: 2px solid #28a745; margin: 20px 0; padding: 20px; border-radius: 10px;'>";
echo "<h2>📚 Informações Importantes</h2>";

echo "<h3>🔧 Processo de Conversão</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "1. Sistema busca arquivos WEBP com o GTIN<br>";
echo "2. Converte WEBP para JPEG na mesma pasta (/upload/)<br>";
echo "3. Image Manager busca arquivos JPG, JPEG, PNG<br>";
echo "4. Gera URLs públicas via ngrok<br>";
echo "5. Retorna array com 'source' para o ML<br>";
echo "</div>";

echo "<h3>⚠️ Possíveis Problemas</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "❌ <strong>URLs não acessíveis:</strong> ngrok parado ou configuração errada<br>";
echo "❌ <strong>Imagens não convertidas:</strong> GD sem suporte WEBP<br>";
echo "❌ <strong>Arquivos não encontrados:</strong> nomes não batem com GTIN<br>";
echo "❌ <strong>Formato não suportado:</strong> ML só aceita JPG, JPEG, PNG<br>";
echo "</div>";

echo "<h3>✅ Próximos Passos</h3>";
echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "1. <strong>Verifique se URLs carregam</strong> clicando nos links acima<br>";
echo "2. <strong>Se URLs funcionam:</strong> teste exportação do produto<br>";
echo "3. <strong>Se URLs não funcionam:</strong> verifique ngrok e arquivos<br>";
echo "4. <strong>Monitore logs</strong> durante a exportação<br>";
echo "</div>";

echo "</div>";
?>
