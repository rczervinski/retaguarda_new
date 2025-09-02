<?php
include "conexao.php";
include "fornecedores_class.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $razao_social = $_POST['razao_social'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}

// Fetch all records
if ($request == 'fetchall') {

    if (is_numeric($desc_pesquisa)) {
        $query = "SELECT codigo,fantasia FROM fornecedores where codigo='" . $desc_pesquisa . "'";
        $query_quantos = "SELECT count(*) FROM fornecedores where codigo='" . $desc_pesquisa . "'";
    } else {
        $query = "SELECT codigo,fantasia FROM fornecedores where fantasia like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM fornecedores where fantasia like upper('%" . $desc_pesquisa . "%')";
    }

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {

        $codigo = $row['codigo'];
        $fantasia = $row['fantasia'];

        $quantos = $row_quantos[0];

        $response[] = array(
            "codigo" => $codigo,
            "fantasia" => $fantasia,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}
// PARA CONSULTAR CODIGO DO PRODUTO EXISTENTE
if ($request == 'consultarCodigoFornecedor') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select codigo from fornecedores where codigo='" . $codigo . "'";
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
if ($request == 'atribuirFornecedores') {
    $response = "0";
    $fornecedores = new fornecedores();
    $fornecedores->codigo = $_POST['codigo'];
    $query = "select * from fornecedores where codigo=" . $fornecedores->codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "fantasia" => $row['fantasia'],
            "razao_social" => $row['razao_social'],
            "cpf_cnpj" => $row['cpf_cnpj'],
            "inscricao_rg" => $row['inscricao_rg'],
            "comprador" => $row['comprador'],
            "cep" => $row['cep'],
            "logradouro" => $row['logradouro'],
            "complemento" => $row['complemento'],
            "bairro" => $row['bairro'],
            "fone" => $row['fone'],
            "celular" => $row['celular'],
            "email" => $row['email'],
            "inativo" => $row['inativo'],
            "numero" => $row['numero'],
            "municipio" => $row['municipio'],
            "uf" => $row['uf'],
            "dt_inicio" => $row['dt_inicio'],
            "municipio_desc" => $row['municipio_desc'],
            "uf_desc" => $row['uf_desc']
        );
    }
    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarFornecedores') {
    $response = "0";
    $fornecedores = new fornecedores();
    $fornecedores->codigo = $_POST['codigo'];
    $fornecedores->fantasia = $_POST['fantasia'];
    $fornecedores->razao_social = $_POST['razao_social'];
    $fornecedores->cpf_cnpj = $_POST['cpf_cnpj'];
    $fornecedores->inscricao_rg = $_POST['inscricao_rg'];
    $fornecedores->comprador = $_POST['comprador'];
    $fornecedores->cep = $_POST['cep'];
    $fornecedores->logradouro = $_POST['logradouro'];
    $fornecedores->complemento = $_POST['complemento'];
    $fornecedores->bairro = $_POST['bairro'];
    $fornecedores->fone = $_POST['fone'];
    $fornecedores->celular = $_POST['celular'];
    $fornecedores->email = $_POST['email'];
    if ($_POST['inativo'] == 'true') {
        $fornecedores->inativo = "1";
    } else {
        $fornecedores->inativo = "0";
    }
    $fornecedores->numero = $_POST['numero'];
    $fornecedores->municipio = $_POST['municipio'];
    $fornecedores->uf = $_POST['uf'];
    $fornecedores->dt_inicio = $_POST['dt_inicio'];
    $fornecedores->municipio_desc = $_POST['municipio_desc'];
    $fornecedores->uf_desc = $_POST['uf_desc'];
    if (is_null($fornecedores->dt_inicio) || $fornecedores->dt_inicio=='') {
        $fornecedores->dt_inicio = '2099-01-01';
    }
    if (is_null($fornecedores->codigo) || $fornecedores->codigo==0) {
        $query = "insert into fornecedores (codigo,fantasia,razao_social,cpf_cnpj,inscricao_rg,comprador,
                    cep,logradouro,complemento,bairro,fone,celular,email,inativo,
                    numero,municipio,uf,dt_inicio,municipio_desc,uf_desc)  
              values(nextval('clientes_codigo_seq'),upper(semacento('" . $fornecedores->fantasia . "')),upper(semacento('" . 
              $fornecedores->razao_social . "')),'" . $fornecedores->cpf_cnpj . "','" . $fornecedores->inscricao_rg . "','" . 
              $fornecedores->comprador . "','" . $fornecedores->cep . "',upper(semacento('" . $fornecedores->logradouro . "')),'" . 
              $fornecedores->complemento . "',upper(semacento('" . $fornecedores->bairro . "')),'" . $fornecedores->fone . "','" . 
              $fornecedores->celular . "','" . $fornecedores->email . "','" . $fornecedores->inativo . "','" . 
              $fornecedores->numero . "','" . $fornecedores->municipio . "','" . $fornecedores->uf . "','" . 
              $fornecedores->dt_inicio . "',upper(semacento('" . $fornecedores->municipio_desc . "')),upper('" . $fornecedores->uf_desc . "'))";
    } else {
        $query = "update fornecedores set ".
            " fantasia=upper(semacento('".$fornecedores->fantasia."')),".
            " razao_social=upper(semacento('".$fornecedores->razao_social."')),".
            " cpf_cnpj='".$fornecedores->cpf_cnpj."',".
            " inscricao_rg='".$fornecedores->inscricao_rg."',".
            " comprador='".$fornecedores->comprador."',".
            " cep='".$fornecedores->cep."',".
            " logradouro=upper(semacento('".$fornecedores->logradouro."')),".
            " complemento='".$fornecedores->complemento."',".
            " bairro=upper(semacento('".$fornecedores->bairro."')),".
            " fone='".$fornecedores->fone."',".
            " celular='".$fornecedores->celular."',".
            " email='".$fornecedores->email."',".
            " inativo='".$fornecedores->inativo."',".
            " numero='".$fornecedores->numero."',".
            " municipio='".$fornecedores->municipio."',".
            " uf='".$fornecedores->uf."',".
            " dt_inicio='".$fornecedores->dt_inicio."',".
            " municipio_desc='".$fornecedores->municipio_desc."',".
            " uf_desc='".$fornecedores->uf_desc."'".
            " where codigo=".$fornecedores->codigo;
        
    }
    $result = pg_query($conexao, $query);
    $response = $query;

    echo json_encode($response);
    die();
}

?>