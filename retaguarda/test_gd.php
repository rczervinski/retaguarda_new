<?php
// Teste da extensão GD
echo "<h2>Teste da Extensão GD</h2>";

// Verificar se a extensão está carregada
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✅ Extensão GD está carregada</p>";
    
    // Verificar versão
    $gd_info = gd_info();
    echo "<p><strong>Versão GD:</strong> " . $gd_info['GD Version'] . "</p>";
    
    // Verificar suporte a WebP
    if (isset($gd_info['WebP Support']) && $gd_info['WebP Support']) {
        echo "<p style='color: green;'>✅ Suporte a WebP: Sim</p>";
    } else {
        echo "<p style='color: red;'>❌ Suporte a WebP: Não</p>";
    }
    
    // Verificar suporte a JPEG
    if (isset($gd_info['JPEG Support']) && $gd_info['JPEG Support']) {
        echo "<p style='color: green;'>✅ Suporte a JPEG: Sim</p>";
    } else {
        echo "<p style='color: red;'>❌ Suporte a JPEG: Não</p>";
    }
    
    // Verificar suporte a PNG
    if (isset($gd_info['PNG Support']) && $gd_info['PNG Support']) {
        echo "<p style='color: green;'>✅ Suporte a PNG: Sim</p>";
    } else {
        echo "<p style='color: red;'>❌ Suporte a PNG: Não</p>";
    }
    
    // Verificar suporte a GIF
    if (isset($gd_info['GIF Read Support']) && $gd_info['GIF Read Support']) {
        echo "<p style='color: green;'>✅ Suporte a GIF: Sim</p>";
    } else {
        echo "<p style='color: red;'>❌ Suporte a GIF: Não</p>";
    }
    
    echo "<h3>Informações completas do GD:</h3>";
    echo "<pre>";
    print_r($gd_info);
    echo "</pre>";
    
} else {
    echo "<p style='color: red;'>❌ Extensão GD não está carregada</p>";
    echo "<p>Para habilitar a extensão GD, edite o arquivo php.ini e descomente a linha:</p>";
    echo "<code>extension=gd</code>";
}

// Teste de criação de imagem simples
echo "<h3>Teste de Criação de Imagem</h3>";
try {
    $test_image = imagecreate(100, 100);
    if ($test_image) {
        echo "<p style='color: green;'>✅ Teste de criação de imagem: Sucesso</p>";
        imagedestroy($test_image);
    } else {
        echo "<p style='color: red;'>❌ Teste de criação de imagem: Falha</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro no teste de criação de imagem: " . $e->getMessage() . "</p>";
}
?>
