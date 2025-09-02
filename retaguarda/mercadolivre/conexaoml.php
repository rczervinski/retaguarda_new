<?php 
    include("../conexao.php");
    $query="select * from token_integracao where descricao='MERCADO_LIVRE' and ativo=1";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $access_token=$row['access_token'];
        $code=$row['code'];
        $client_id=$row['client_id'];
        $client_secret=$row['client_secret'];
    }
?>