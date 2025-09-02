<?php
include "conexao.php";
include "transportadoras_class.php";
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
        $query = "SELECT codigo,fantasia FROM transportadoras where codigo='" . $desc_pesquisa . "'";
        $query_quantos = "SELECT count(*) FROM transportadoras where codigo='" . $desc_pesquisa . "'";
    } else {
        $query = "SELECT codigo,fantasia FROM transportadoras where fantasia like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM transportadoras where fantasia like upper('%" . $desc_pesquisa . "%')";
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
if ($request == 'consultarCodigoTransportadora') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select codigo from transportadoras where codigo='" . $codigo . "'";
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
if ($request == 'atribuirTransportadoras') {
    $response = "0";
    $transportadoras = new transportadoras();
    $transportadoras->codigo = $_POST['codigo'];
    $query = "select * from transportadoras where codigo=" . $transportadoras->codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "fantasia" => $row['fantasia'],
            "razao_social" => $row['razao_social'],
            "cpf_cnpj" => $row['cpf_cnpj'],
            "inscricao_rg" => $row['inscricao_rg'],
            "contato" => $row['contato'],
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
            "municipio_desc" => $row['municipio_desc'],
            "uf_desc" => $row['uf_desc']
        );
    }
    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarTransportadoras') {
    $response = "0";
    $transportadoras = new transportadoras();
    $transportadoras->codigo = $_POST['codigo'];
    $transportadoras->fantasia = $_POST['fantasia'];
    $transportadoras->razao_social = $_POST['razao_social'];
    $transportadoras->cpf_cnpj = $_POST['cpf_cnpj'];
    $transportadoras->inscricao_rg = $_POST['inscricao_rg'];
    $transportadoras->contato = $_POST['contato'];
    $transportadoras->cep = $_POST['cep'];
    $transportadoras->logradouro = $_POST['logradouro'];
    $transportadoras->complemento = $_POST['complemento'];
    $transportadoras->bairro = $_POST['bairro'];
    $transportadoras->fone = $_POST['fone'];
    $transportadoras->celular = $_POST['celular'];
    $transportadoras->email = $_POST['email'];
    if ($_POST['inativo'] == 'true') {
        $transportadoras->inativo = "1";
    } else {
        $transportadoras->inativo = "0";
    }
    $transportadoras->numero = $_POST['numero'];
    $transportadoras->municipio = $_POST['municipio'];
    $transportadoras->uf = $_POST['uf'];
    $transportadoras->municipio_desc = $_POST['municipio_desc'];
    $transportadoras->uf_desc = $_POST['uf_desc'];
    
    if (is_null($transportadoras->codigo) || $transportadoras->codigo==0) {
        $query = "insert into transportadoras (codigo,fantasia,razao_social,cpf_cnpj,inscricao_rg,contato,
                    cep,logradouro,complemento,bairro,fone,celular,email,inativo,
                    numero,municipio,uf,municipio_desc,uf_desc)  
              values(nextval('clientes_codigo_seq'),upper(semacento('" . $transportadoras->fantasia . "')),upper(semacento('" . 
              $transportadoras->razao_social . "')),'" . $transportadoras->cpf_cnpj . "','" . $transportadoras->inscricao_rg . "','" . 
              $transportadoras->contato . "','" . $transportadoras->cep . "',upper(semacento('" . $transportadoras->logradouro . "')),'" . 
              $transportadoras->complemento . "',upper(semacento('" . $transportadoras->bairro . "')),'" . $transportadoras->fone . "','" . 
              $transportadoras->celular . "','" . $transportadoras->email . "','" . $transportadoras->inativo . "','" . 
              $transportadoras->numero . "','" . $transportadoras->municipio . "','" . $transportadoras->uf . "',upper(semacento('" . $transportadoras->municipio_desc . "')),upper('" . $transportadoras->uf_desc . "'))";
    } else {
        $query = "update transportadoras set ".
            " fantasia=upper(semacento('".$transportadoras->fantasia."')),".
            " razao_social=upper(semacento('".$transportadoras->razao_social."')),".
            " cpf_cnpj='".$transportadoras->cpf_cnpj."',".
            " inscricao_rg='".$transportadoras->inscricao_rg."',".
            " contato='".$transportadoras->contato."',".
            " cep='".$transportadoras->cep."',".
            " logradouro=upper(semacento('".$transportadoras->logradouro."')),".
            " complemento='".$transportadoras->complemento."',".
            " bairro=upper(semacento('".$transportadoras->bairro."')),".
            " fone='".$transportadoras->fone."',".
            " celular='".$transportadoras->celular."',".
            " email='".$transportadoras->email."',".
            " inativo='".$transportadoras->inativo."',".
            " numero='".$transportadoras->numero."',".
            " municipio='".$transportadoras->municipio."',".
            " uf='".$transportadoras->uf."',".
            " municipio_desc='".$transportadoras->municipio_desc."',".
            " uf_desc='".$transportadoras->uf_desc."'".
            " where codigo=".$transportadoras->codigo;
        
    }
    $result = pg_query($conexao, $query);
    $response = $query;

    echo json_encode($response);
    die();
}

?>