<?php
/**
 * Corrigir status das variantes
 */

require_once 'conexao.php';

echo "<h2>🔧 CORRIGIR - Status das Variantes</h2>";

try {
    // Iniciar transação
    pg_query($conexao, "BEGIN");

    echo "<h3>1. Encontrando variantes sem status que deveriam ter ENSV:</h3>";
    
    // Buscar produtos ENSVI (vitrine)
    $query_vitrines = "SELECT codigo_interno, codigo_gtin, descricao FROM produtos WHERE status = 'ENSVI'";
    $result_vitrines = pg_query($conexao, $query_vitrines);
    
    $total_corrigidos = 0;
    
    while ($vitrine = pg_fetch_assoc($result_vitrines)) {
        $codigo_interno_pai = $vitrine['codigo_interno'];
        $sku_pai = $vitrine['codigo_gtin'];
        $desc_pai = $vitrine['descricao'];
        
        echo "<h4>Produto Vitrine: $sku_pai - " . htmlspecialchars($desc_pai) . "</h4>";
        
        // Buscar variantes relacionadas na produtos_gd
        $query_gd = "SELECT codigo_gtin FROM produtos_gd WHERE codigo_interno = $codigo_interno_pai";
        $result_gd = pg_query($conexao, $query_gd);
        
        $gtins_variantes = array();
        while ($row_gd = pg_fetch_assoc($result_gd)) {
            $gtins_variantes[] = $row_gd['codigo_gtin'];
        }
        
        if (empty($gtins_variantes)) {
            echo "<p style='color: orange;'>⚠️ Nenhuma variante encontrada na produtos_gd</p>";
            continue;
        }
        
        echo "<p><strong>GTINs das variantes:</strong> " . implode(', ', $gtins_variantes) . "</p>";
        
        // Verificar status atual das variantes
        $gtins_list = "'" . implode("', '", $gtins_variantes) . "'";
        $query_status = "SELECT codigo_interno, codigo_gtin, descricao, status FROM produtos WHERE codigo_gtin IN ($gtins_list)";
        $result_status = pg_query($conexao, $query_status);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>GTIN</th>";
        echo "<th style='padding: 8px;'>Descrição</th>";
        echo "<th style='padding: 8px;'>Status Atual</th>";
        echo "<th style='padding: 8px;'>Ação</th>";
        echo "</tr>";
        
        while ($variante = pg_fetch_assoc($result_status)) {
            $gtin = $variante['codigo_gtin'];
            $desc = $variante['descricao'];
            $status_atual = $variante['status'];
            $codigo_interno = $variante['codigo_interno'];
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($gtin) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($desc) . "</td>";
            echo "<td style='padding: 8px;'>" . ($status_atual ?: '<em>vazio</em>') . "</td>";
            echo "<td style='padding: 8px;'>";
            
            if ($status_atual !== 'ENSV') {
                // Corrigir status para ENSV
                $update_query = "UPDATE produtos SET status = 'ENSV' WHERE codigo_interno = $codigo_interno";
                $update_result = pg_query($conexao, $update_query);
                
                if ($update_result) {
                    echo "<span style='color: green;'>✅ Corrigido para ENSV</span>";
                    $total_corrigidos++;
                } else {
                    echo "<span style='color: red;'>❌ Erro ao corrigir</span>";
                }
            } else {
                echo "<span style='color: blue;'>✅ Já está correto (ENSV)</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Commit da transação
    pg_query($conexao, "COMMIT");
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>✅ Correção Concluída!</h3>";
    echo "<p><strong>Total de variantes corrigidas:</strong> $total_corrigidos</p>";
    echo "<p>Agora todas as variantes relacionadas a produtos ENSVI têm status ENSV.</p>";
    echo "</div>";
    
    echo "<h3>2. Verificação pós-correção:</h3>";
    echo "<p>Execute a sincronização novamente para verificar se o problema foi resolvido.</p>";
    
} catch (Exception $e) {
    // Rollback em caso de erro
    pg_query($conexao, "ROLLBACK");
    
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px;'>";
    echo "<p style='color: #721c24;'>❌ Erro durante a correção: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
</style>";
?>
