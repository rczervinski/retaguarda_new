<?php
// Teste simples para verificar o endpoint da grade avançada
include "conexao.php";

// Testar com um código interno conhecido (substitua pelo código de um produto que tenha grade)
$codigo_interno = 1; // Ajuste para um código que exista

echo "<h2>Teste do Endpoint carregar_grade_completa</h2>";
echo "<p>Código interno testado: $codigo_interno</p>";

try {
    $query = "SELECT 
                pg.codigo_gtin,
                pg.caracteristica,
                pg.variacao,
                pg.nome as descricao,
                COALESCE(pb.preco_venda::numeric, 0) as preco,
                COALESCE(po.qtde::numeric, 0) as estoque,
                COALESCE(po.peso::numeric, 0) as peso,
                COALESCE(po.altura::numeric, 0) as altura,
                COALESCE(po.largura::numeric, 0) as largura,
                COALESCE(po.comprimento::numeric, 0) as comprimento
              FROM produtos_gd pg
              LEFT JOIN produtos p ON pg.codigo_gtin = p.codigo_gtin
              LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
              LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
              WHERE pg.codigo_interno = $codigo_interno
              ORDER BY pg.codigo";
    
    echo "<p><strong>Query:</strong> $query</p>";
    
    $result = pg_query($conexao, $query);
    
    if (!$result) {
        echo "<p style='color: red;'><strong>Erro:</strong> " . pg_last_error($conexao) . "</p>";
    } else {
        echo "<p style='color: green;'><strong>Query executada com sucesso!</strong></p>";
        
        $variants = [];
        while ($row = pg_fetch_assoc($result)) {
            $variants[] = $row;
        }
        
        echo "<p><strong>Variantes encontradas:</strong> " . count($variants) . "</p>";
        
        if (count($variants) > 0) {
            echo "<h3>Dados das variantes:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>GTIN</th><th>Característica</th><th>Variação</th><th>Preço</th><th>Estoque</th><th>Peso</th></tr>";
            
            foreach ($variants as $variant) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($variant['codigo_gtin']) . "</td>";
                echo "<td>" . htmlspecialchars($variant['caracteristica']) . "</td>";
                echo "<td>" . htmlspecialchars($variant['variacao']) . "</td>";
                echo "<td>" . htmlspecialchars($variant['preco']) . "</td>";
                echo "<td>" . htmlspecialchars($variant['estoque']) . "</td>";
                echo "<td>" . htmlspecialchars($variant['peso']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>Nenhuma variante encontrada para este produto.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exceção:</strong> " . $e->getMessage() . "</p>";
}

// Testar também se existem produtos com grade
echo "<h2>Produtos com Grade</h2>";
$query_grade = "SELECT DISTINCT pg.codigo_interno, p.descricao, COUNT(pg.codigo) as total_variantes
                FROM produtos_gd pg
                LEFT JOIN produtos p ON pg.codigo_interno = p.codigo_interno
                GROUP BY pg.codigo_interno, p.descricao
                ORDER BY total_variantes DESC
                LIMIT 10";

$result_grade = pg_query($conexao, $query_grade);

if ($result_grade) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Código Interno</th><th>Descrição</th><th>Total Variantes</th></tr>";
    
    while ($row = pg_fetch_assoc($result_grade)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['codigo_interno']) . "</td>";
        echo "<td>" . htmlspecialchars($row['descricao']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_variantes']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Erro ao buscar produtos com grade: " . pg_last_error($conexao) . "</p>";
}
?>
