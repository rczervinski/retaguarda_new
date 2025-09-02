<?php
include "conexao.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $nome = $_POST['nome'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}

if ($request == 'remover_promocao') {
    $nome=$_POST['nome'];
    $query = "delete from promocoes where nome=upper('" . $nome ."')";
    $result = pg_query($conexao, $query);
    echo json_encode("OK");
    die();
}

if ($request == 'remover_item_promocao') {
    $codigo_gtin=$_POST['codigo_gtin'];
    $query = "delete from promocoes where codigo_gtin='" . $codigo_gtin ."'";
    $result = pg_query($conexao, $query);
    echo json_encode("OK");
    die();
}


if ($request == 'carregarItensPromocao') {
    $nome=$_POST['nome'];
    $query = "SELECT pr.codigo_gtin, p.descricao from promocoes pr inner join produtos p on pr.codigo_gtin  = p.codigo_gtin where nome=upper('" . $nome ."')";
    $result = pg_query($conexao, $query);

    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao']
        );
    }
    echo json_encode($response);
    die();
}


// Fetch all records
if ($request == 'fetchall') {
    $query = "SELECT distinct nome FROM promocoes where nome like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
    $query_quantos = "SELECT distinct nome FROM promocoes where nome like upper('%" . $desc_pesquisa . "%')";
    
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_num_rows($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {
        $nome = $row['nome'];
        $quantos = $row_quantos[0];
        $response[] = array(
            "nome" => $nome,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}

if ($request == 'consultarProduto') {
    $response = "0";
    $codigo_gtin= $_POST['codigo_gtin'];
    $query="select descricao from produtos where codigo_gtin='".$codigo_gtin."'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $descricao = $row['descricao'];
        $response[] = array(
            "descricao" => $descricao
        );
    }
    echo json_encode($response);
    die();
}

// PARA CONSULTAR CODIGO DO PRODUTO EXISTENTE
if ($request == 'consultarCodigoVendedor') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select codigo from vendedores where codigo='" . $codigo . "'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $response[] = array(
            "codigo" => $codigo
        );
    }
    echo json_encode($response);
    die();
}

// PARA ALTERAR PRODUTOS
if ($request == 'atribuirPromocoes') {
    $response = "0";
    $nome= $_POST['nome'];
    $query = "select pr.nome,pr.qtde,pr.preco from promocoes pr where pr.nome='" . $nome."' limit 1";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "nome" => $row['nome'],
            "qtde" =>number_format($row['qtde'],2,',',''),
            "preco" =>number_format($row['preco'],2,',',''),
        );
    }
    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarPromocao') {
    $response = "0";
    $nome= $_POST['nome'];
    $qtde=str_replace(',','.',$_POST['qtde']);
    $preco=str_replace(',','.',$_POST['preco']);
    $promocao_prod_codigo= $_POST['promocao_prod_codigo'];
    $promocao_prod_descricao= $_POST['promocao_prod_descricao'];

    $query = "select nome from promocoes where codigo_gtin='".$promocao_prod_codigo."'";
    $result = pg_query($conexao, $query);
    if($row = pg_fetch_assoc($result)){
        $response="Produto ja inserido na promocao ".$row['nome'];
        echo json_encode($response);
        die();
    }
    
    $query = "insert into promocoes (nome,qtde,preco,codigo_gtin) values(upper(semacento('" . $nome . "')),'" . $qtde. "','" .$preco. "','" .$promocao_prod_codigo."')";
    $result = pg_query($conexao, $query);
    $response = "OK";

    echo json_encode($response);
    die();
}

?>