<?php
/**
 * Script para limpar status antigo 'E' do banco de dados
 * Converte todos os produtos com status 'E' para status vazio
 */

require_once 'conexao.php';

echo "<h2>🧹 LIMPEZA - Removendo Status Antigo 'E'</h2>";

try {
    // 1. Verificar quantos produtos têm status 'E'
    $query = "SELECT COUNT(*) as total FROM produtos WHERE status = 'E'";
    $result = pg_query($conexao, $query);
    $row = pg_fetch_assoc($result);
    $total_produtos_e = $row['total'];

    echo "<h3>📊 Status Atual:</h3>";
    echo "<p><strong>Produtos com status 'E':</strong> $total_produtos_e</p>";

    if ($total_produtos_e == 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p style='color: #155724; margin: 0;'>✅ Nenhum produto com status 'E' encontrado. Banco já está limpo!</p>";
        echo "</div>";
    } else {
        // 2. Mostrar alguns exemplos dos produtos que serão afetados
        echo "<h3>📋 Produtos que serão afetados (primeiros 10):</h3>";
        $query = "SELECT codigo_gtin, descricao FROM produtos WHERE status = 'E' ORDER BY codigo_gtin LIMIT 10";
        $result = pg_query($conexao, $query);

        if (pg_num_rows($result) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th style='padding: 8px;'>GTIN</th>";
            echo "<th style='padding: 8px;'>Descrição</th>";
            echo "</tr>";
            
            while ($row = pg_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($row['codigo_gtin']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($row['descricao']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($total_produtos_e > 10) {
                echo "<p><em>... e mais " . ($total_produtos_e - 10) . " produtos.</em></p>";
            }
        }

        // 3. Executar a limpeza
        echo "<h3>🔄 Executando Limpeza:</h3>";
        
        // Iniciar transação
        pg_query($conexao, "BEGIN");
        
        // Atualizar todos os produtos com status 'E' para status vazio
        $update_query = "UPDATE produtos SET status = '' WHERE status = 'E'";
        $update_result = pg_query($conexao, $update_query);
        
        if (!$update_result) {
            throw new Exception("Erro ao atualizar produtos: " . pg_last_error($conexao));
        }
        
        $produtos_atualizados = pg_affected_rows($update_result);
        
        // Commit da transação
        pg_query($conexao, "COMMIT");
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>✅ Limpeza Concluída com Sucesso!</h4>";
        echo "<p><strong>Produtos atualizados:</strong> $produtos_atualizados</p>";
        echo "<p>Todos os produtos com status 'E' agora têm status vazio.</p>";
        echo "</div>";
    }

    // 4. Verificar status após limpeza
    echo "<h3>📊 Status Após Limpeza:</h3>";
    $query = "SELECT status, COUNT(*) as total FROM produtos WHERE status IN ('ENS', 'ENSVI', 'ENSV', 'E') GROUP BY status ORDER BY status";
    $result = pg_query($conexao, $query);

    if (pg_num_rows($result) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Total</th>";
        echo "<th style='padding: 8px;'>Descrição</th>";
        echo "</tr>";
        
        while ($row = pg_fetch_assoc($result)) {
            $descricao = [
                'ENS' => 'Produtos Normais na Nuvemshop',
                'ENSVI' => 'Produtos Vitrine na Nuvemshop',
                'ENSV' => 'Variantes na Nuvemshop',
                'E' => 'Status Antigo (deveria estar vazio agora)'
            ];
            
            $cor = [
                'ENS' => '#2196F3',
                'ENSVI' => '#2E7D32',
                'ENSV' => '#4CAF50',
                'E' => '#F44336'
            ];
            
            echo "<tr>";
            echo "<td style='padding: 8px; background: " . $cor[$row['status']] . "; color: white; text-align: center;'>" . $row['status'] . "</td>";
            echo "<td style='padding: 8px; text-align: center;'>" . $row['total'] . "</td>";
            echo "<td style='padding: 8px;'>" . $descricao[$row['status']] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ Nenhum produto com status de e-commerce encontrado.</p>";
    }

    // 5. Verificar se ainda existem produtos com status 'E'
    $query = "SELECT COUNT(*) as total FROM produtos WHERE status = 'E'";
    $result = pg_query($conexao, $query);
    $row = pg_fetch_assoc($result);
    $produtos_e_restantes = $row['total'];

    if ($produtos_e_restantes > 0) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>⚠️ Atenção</h4>";
        echo "<p>Ainda existem $produtos_e_restantes produtos com status 'E'. Pode ser necessário executar o script novamente.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4 style='color: #0c5460; margin: 0 0 10px 0;'>🎉 Perfeito!</h4>";
        echo "<p>Nenhum produto com status 'E' encontrado. A limpeza foi 100% bem-sucedida!</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    // Rollback em caso de erro
    pg_query($conexao, "ROLLBACK");
    
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>❌ Erro na Limpeza</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// 6. Instruções finais
echo "<h3>📝 Próximos Passos:</h3>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Execute a sincronização</strong> na página de produtos para aplicar os novos status</li>";
echo "<li><strong>Verifique se os ícones</strong> aparecem corretamente na tabela</li>";
echo "<li><strong>Teste a criação</strong> de novos produtos com variantes</li>";
echo "<li><strong>Remova este arquivo</strong> após confirmar que tudo está funcionando</li>";
echo "</ol>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
</style>";
?>
