<?php
include "conexao.php";
include "vendedores_class.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $nome = $_POST['nome'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}

// Fetch all records
if ($request == 'fetchall') {

    if (is_numeric($desc_pesquisa)) {
        $query = "SELECT codigo,nome FROM vendedores where codigo='" . $desc_pesquisa . "'";
        $query_quantos = "SELECT count(*) FROM vendedores where codigo='" . $desc_pesquisa . "'";
    } else {
        $query = "SELECT codigo,nome FROM vendedores where nome like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM vendedores where nome like upper('%" . $desc_pesquisa . "%')";
    }

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {

        $codigo = $row['codigo'];
        $nome = $row['nome'];

        $quantos = $row_quantos[0];

        $response[] = array(
            "codigo" => $codigo,
            "nome" => $nome,
            "quantos" => $quantos
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
if ($request == 'atribuirVendedores') {
    $response = "0";
    $vendedores = new vendedores();
    $vendedores->codigo = $_POST['codigo'];
    $query = "select * from vendedores where codigo=" . $vendedores->codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "nome" => $row['nome'],
            "comissao" =>number_format($row['comissao'],2,',',''),
            "inativo" => $row['inativo'],
            "desc_max" => number_format($row['desc_max'],2,',',''),
            "senha" => $row['senha'],
        );
    }
    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarVendedores') {
    $response = "0";
    $vendedores = new vendedores();
    $vendedores->codigo = $_POST['codigo'];
    $vendedores->nome= $_POST['nome'];
    $vendedores->senha= $_POST['senha'];
    $vendedores->comissao= str_replace(',','.',$_POST['comissao']);
    if ($_POST['inativo'] == 'true') {
        $vendedores->inativo = "1";
    } else {
        $vendedores->inativo = "0";
    }
    $vendedores->desc_max= str_replace(',','.',$_POST['desc_max']);
    if (is_null($vendedores->codigo) || $vendedores->codigo==0) {
        $query = "insert into vendedores (codigo,nome,comissao,inativo,desc_max,senha)  
              values(nextval('vendedores_codigo_seq'),upper(semacento('" . $vendedores->nome . "')),'" . $vendedores->comissao. "','" . 
              $vendedores->inativo. "','" .$vendedores->desc_max ."','".$vendedores->senha."')";
    } else {
        $query = "update vendedores set ".
            " nome=upper(semacento('".$vendedores->nome."')),".
            " comissao='".$vendedores->comissao."',".
            " inativo='".$vendedores->inativo."',".
            " desc_max='".$vendedores->desc_max."',".
            " senha='".$vendedores->senha."'".
            " where codigo=".$vendedores->codigo;
        
    }
    $result = pg_query($conexao, $query);
    $response = $query;

    echo json_encode($response);
    die();
}

?>