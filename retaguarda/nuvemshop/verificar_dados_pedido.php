<?php
/**
 * Script para verificar os dados de um pedido específico
 */

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivo de conexão
require_once '../conexao.php';

// Obter o código do pedido da URL
$codigo = isset($_GET['codigo']) ? intval($_GET['codigo']) : 0;

// Iniciar HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Verificar Dados do Pedido</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .null-value { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verificar Dados do Pedido</h1>';

if ($codigo <= 0) {
    // Se não foi fornecido um código, mostrar formulário para buscar
    echo '<div class="card-panel blue lighten-4">
        <h4>Buscar Pedido</h4>
        <p>Digite o código do pedido que deseja verificar:</p>
        <form method="get">
            <div class="input-field">
                <input type="number" name="codigo" id="codigo" required>
                <label for="codigo">Código do Pedido</label>
            </div>
            <button type="submit" class="btn blue waves-effect waves-light">Buscar</button>
            <a href="javascript:history.back()" class="btn grey waves-effect waves-light">Voltar</a>
        </form>
    </div>';
} else {
    // Buscar dados do pedido
    $query = "SELECT * FROM ped_online_base WHERE codigo = $codigo";
    $result = pg_query($conexao, $query);
    
    if (pg_num_rows($result) > 0) {
        $pedido = pg_fetch_assoc($result);
        
        echo '<div class="card-panel green lighten-4">
            <h4>Pedido #' . $codigo . ' encontrado</h4>
        </div>';
        
        echo '<div class="card">
            <div class="card-content">
                <span class="card-title">Dados do Pedido</span>
                <table>
                    <tr>
                        <th>Campo</th>
                        <th>Valor</th>
                    </tr>';
        
        foreach ($pedido as $campo => $valor) {
            echo '<tr>
                <td>' . $campo . '</td>
                <td>' . ($valor !== null ? htmlspecialchars($valor) : '<span class="null-value">NULL</span>') . '</td>
            </tr>';
        }
        
        echo '</table>
            </div>
        </div>';
        
        // Buscar itens do pedido
        $query = "SELECT * FROM ped_online_prod WHERE pedido = $codigo";
        $result = pg_query($conexao, $query);
        
        if (pg_num_rows($result) > 0) {
            echo '<div class="card">
                <div class="card-content">
                    <span class="card-title">Itens do Pedido</span>
                    <table>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço</th>
                            <th>Total</th>
                        </tr>';
            
            $total_pedido = 0;
            while ($item = pg_fetch_assoc($result)) {
                $total_item = $item['qtde'] * $item['preco_venda'];
                $total_pedido += $total_item;
                
                echo '<tr>
                    <td>' . $item['codigo_produto'] . '</td>
                    <td>' . $item['descricao'] . '</td>
                    <td>' . $item['qtde'] . '</td>
                    <td>R$ ' . number_format($item['preco_venda']/100, 2, ',', '.') . '</td>
                    <td>R$ ' . number_format($total_item/100, 2, ',', '.') . '</td>
                </tr>';
            }
            
            echo '<tr>
                <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                <td><strong>R$ ' . number_format($total_pedido/100, 2, ',', '.') . '</strong></td>
            </tr>';
            
            echo '</table>
                </div>
            </div>';
        } else {
            echo '<div class="card-panel orange lighten-4">
                <p class="warning">Nenhum item encontrado para este pedido.</p>
            </div>';
        }
        
        // Mostrar a consulta SQL usada no vendasonline_ajax.php
        $query_ajax = "select p.codigo_gtin, p.descricao, pp.qtde, pp.preco_venda, pp.qtde*pp.preco_venda as total, pp.observacao, 
        po.nome, po.cpf, trim(po.endereco||' '||po.numero||' '||po.complemento) as endereco, po.cep, po.bairro, po.uf, po.municipio, 
        po.forma_pgto, po.valor_pago, po.fone, po.email, po.status, po.payment_status, po.status_desc, po.codigo_externo, po.data, po.hora,
        CASE WHEN po.origem IS NULL THEN 'Desconhecida' ELSE po.origem END as origem
        from ped_online_base po 
        inner join ped_online_prod pp on po.codigo=pp.pedido 
        inner join produtos p on pp.codigo_produto=p.codigo_gtin 
        where po.codigo=$codigo";
        
        echo '<div class="card">
            <div class="card-content">
                <span class="card-title">Consulta SQL usada no vendasonline_ajax.php</span>
                <pre>' . htmlspecialchars($query_ajax) . '</pre>
                
                <h5>Resultado da consulta:</h5>';
        
        $result_ajax = pg_query($conexao, $query_ajax);
        
        if (pg_num_rows($result_ajax) > 0) {
            echo '<table>
                <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                </tr>';
            
            $row_ajax = pg_fetch_assoc($result_ajax);
            
            foreach ($row_ajax as $campo => $valor) {
                echo '<tr>
                    <td>' . $campo . '</td>
                    <td>' . ($valor !== null ? htmlspecialchars($valor) : '<span class="null-value">NULL</span>') . '</td>
                </tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="error">A consulta não retornou resultados. Verifique se os JOINs estão corretos.</p>';
            
            // Verificar se o produto existe na tabela produtos
            $query_prod = "SELECT pp.codigo_produto, p.codigo_gtin, p.descricao 
                          FROM ped_online_prod pp 
                          LEFT JOIN produtos p ON pp.codigo_produto = p.codigo_gtin 
                          WHERE pp.pedido = $codigo";
            $result_prod = pg_query($conexao, $query_prod);
            
            if (pg_num_rows($result_prod) > 0) {
                echo '<h5>Verificação de produtos:</h5>';
                echo '<table>
                    <tr>
                        <th>Código no Pedido</th>
                        <th>Código na Tabela Produtos</th>
                        <th>Descrição</th>
                        <th>Status</th>
                    </tr>';
                
                while ($row_prod = pg_fetch_assoc($result_prod)) {
                    echo '<tr>
                        <td>' . $row_prod['codigo_produto'] . '</td>
                        <td>' . ($row_prod['codigo_gtin'] !== null ? $row_prod['codigo_gtin'] : '<span class="null-value">NULL</span>') . '</td>
                        <td>' . ($row_prod['descricao'] !== null ? $row_prod['descricao'] : '<span class="null-value">NULL</span>') . '</td>
                        <td>' . ($row_prod['codigo_gtin'] !== null ? '<span class="success">Encontrado</span>' : '<span class="error">Não encontrado</span>') . '</td>
                    </tr>';
                }
                
                echo '</table>';
            }
        }
        
        echo '</div>
        </div>';
        
    } else {
        echo '<div class="card-panel red lighten-4">
            <h4>Pedido não encontrado</h4>
            <p>Não foi encontrado nenhum pedido com o código ' . $codigo . '.</p>
            <a href="verificar_dados_pedido.php" class="btn blue waves-effect waves-light">Voltar</a>
        </div>';
    }
}

// Fechar HTML
echo '<a href="javascript:history.back()" class="btn grey waves-effect waves-light">Voltar</a>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>';
?>
