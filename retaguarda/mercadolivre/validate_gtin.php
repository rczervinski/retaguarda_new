<?php
/**
 * Validador de GTIN para verificar se códigos são válidos
 */

echo "<h2>🔍 Validação de GTIN</h2>";

function validateGTIN($gtin) {
    // Remover espaços e verificar se é numérico
    $gtin = preg_replace('/\s/', '', $gtin);
    if (!ctype_digit($gtin)) {
        return ['valid' => false, 'error' => 'Contém caracteres não numéricos'];
    }
    
    $length = strlen($gtin);
    
    // Verificar comprimento válido
    if (!in_array($length, [8, 12, 13, 14])) {
        return ['valid' => false, 'error' => "Comprimento inválido: $length dígitos (deve ter 8, 12, 13 ou 14)"];
    }
    
    // Validar dígito verificador
    if ($length == 13) {
        // EAN-13
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($gtin[$i]) * (($i % 2 == 0) ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        $isValid = ($checkDigit == intval($gtin[12]));
        
        return [
            'valid' => $isValid,
            'type' => 'EAN-13',
            'calculated_check' => $checkDigit,
            'provided_check' => intval($gtin[12]),
            'error' => $isValid ? null : 'Dígito verificador incorreto'
        ];
        
    } elseif ($length == 14) {
        // GTIN-14
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($gtin[$i]) * (($i % 2 == 0) ? 3 : 1);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        $isValid = ($checkDigit == intval($gtin[13]));
        
        return [
            'valid' => $isValid,
            'type' => 'GTIN-14',
            'calculated_check' => $checkDigit,
            'provided_check' => intval($gtin[13]),
            'error' => $isValid ? null : 'Dígito verificador incorreto'
        ];
        
    } elseif ($length == 12) {
        // UPC-A
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += intval($gtin[$i]) * (($i % 2 == 0) ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        $isValid = ($checkDigit == intval($gtin[11]));
        
        return [
            'valid' => $isValid,
            'type' => 'UPC-A',
            'calculated_check' => $checkDigit,
            'provided_check' => intval($gtin[11]),
            'error' => $isValid ? null : 'Dígito verificador incorreto'
        ];
        
    } elseif ($length == 8) {
        // EAN-8
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += intval($gtin[$i]) * (($i % 2 == 0) ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        $isValid = ($checkDigit == intval($gtin[7]));
        
        return [
            'valid' => $isValid,
            'type' => 'EAN-8',
            'calculated_check' => $checkDigit,
            'provided_check' => intval($gtin[7]),
            'error' => $isValid ? null : 'Dígito verificador incorreto'
        ];
    }
    
    return ['valid' => false, 'error' => 'Tipo de GTIN não suportado'];
}

// Testar os GTINs do seu produto
$gtins = [
    '07891108080000', // Produto principal
    '07891108080001', // Variação
];

foreach ($gtins as $gtin) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>GTIN: $gtin</h3>";
    
    $result = validateGTIN($gtin);
    
    if ($result['valid']) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ <strong>GTIN VÁLIDO</strong><br>";
        echo "Tipo: " . $result['type'] . "<br>";
        echo "Dígito verificador calculado: " . $result['calculated_check'] . "<br>";
        echo "Dígito verificador fornecido: " . $result['provided_check'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ <strong>GTIN INVÁLIDO</strong><br>";
        echo "Erro: " . $result['error'] . "<br>";
        
        if (isset($result['calculated_check'])) {
            echo "Dígito verificador correto seria: " . $result['calculated_check'] . "<br>";
            echo "Dígito verificador fornecido: " . $result['provided_check'] . "<br>";
            
            // Sugerir correção
            $correctedGtin = substr($gtin, 0, -1) . $result['calculated_check'];
            echo "<strong>GTIN corrigido:</strong> $correctedGtin<br>";
        }
        echo "</div>";
    }
    
    echo "</div>";
}

// Informações adicionais
echo "<h3>📚 Informações sobre GTIN:</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<strong>Tipos de GTIN aceitos pelo Mercado Livre:</strong><br>";
echo "- EAN-8: 8 dígitos (produtos pequenos)<br>";
echo "- UPC-A: 12 dígitos (padrão americano)<br>";
echo "- EAN-13: 13 dígitos (padrão internacional)<br>";
echo "- GTIN-14: 14 dígitos (caixas/embalagens)<br><br>";

echo "<strong>Dígito Verificador:</strong><br>";
echo "- É calculado usando algoritmo específico<br>";
echo "- Garante integridade do código<br>";
echo "- Se estiver errado, o GTIN é inválido<br><br>";

echo "<strong>Solução:</strong><br>";
echo "1. Verificar se os códigos no banco estão corretos<br>";
echo "2. Corrigir dígitos verificadores se necessário<br>";
echo "3. Usar códigos EAN-13 se possível (mais comum no Brasil)<br>";
echo "</div>";
?>
