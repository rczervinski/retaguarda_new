<?php
include "conexao.php";
include "clientes_class.php";
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
        $query = "SELECT codigo,fantasia FROM clientes where codigo='" . $desc_pesquisa . "'";
        $query_quantos = "SELECT count(*) FROM clientes where codigo='" . $desc_pesquisa . "'";
    } else {
        $query = "SELECT codigo,fantasia FROM clientes where fantasia like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM clientes where fantasia like upper('%" . $desc_pesquisa . "%')";
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
if ($request == 'consultarCodigoCliente') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select codigo from clientes where codigo='" . $codigo . "'";
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
if ($request == 'atribuirClientes') {
    $response = "0";
    $clientes = new clientes();
    $clientes->codigo = $_POST['codigo'];
    $query = "select * from clientes where codigo=" . $clientes->codigo;
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
            "classificacao" => $row['classificacao'],
            "numero" => $row['numero'],
            "municipio" => $row['municipio'],
            "uf" => $row['uf'],
            "senha" => $row['senha'],
            "limite" => $row['limite'],
            "mae" => $row['mae'],
            "pai" => $row['pai'],
            "conjuge" => $row['conjuge'],
            "ref1" => $row['ref1'],
            "ref2" => $row['ref2'],
            "fone_ref1" => $row['fone_ref1'],
            "fone_ref2" => $row['fone_ref2'],
            "profissao" => $row['profissao'],
            "nascimento" => $row['nascimento'],
            "vencimento" => $row['vencimento'],
            "numeracao" => $row['numeracao'],
            "municipio_desc" => $row['municipio_desc'],
            "uf_desc" => $row['uf_desc']
        );
    }
    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarClientes') {
    $response = "0";
    $clientes = new clientes();
    $clientes->codigo = $_POST['codigo'];
    $clientes->fantasia = $_POST['fantasia'];
    $clientes->razao_social = $_POST['razao_social'];
    $clientes->cpf_cnpj = $_POST['cpf_cnpj'];
    $clientes->inscricao_rg = $_POST['inscricao_rg'];
    $clientes->contato = $_POST['contato'];
    $clientes->cep = $_POST['cep'];
    $clientes->logradouro = $_POST['logradouro'];
    $clientes->complemento = $_POST['complemento'];
    $clientes->bairro = $_POST['bairro'];
    $clientes->fone = $_POST['fone'];
    $clientes->celular = $_POST['celular'];
    $clientes->email = $_POST['email'];
    if ($_POST['inativo'] == 'true') {
        $clientes->inativo = "1";
    } else {
        $clientes->inativo = "0";
    }
    $clientes->classificacao = $_POST['classificacao'];
    $clientes->numero = $_POST['numero'];
    $clientes->municipio = $_POST['municipio'];
    $clientes->uf = $_POST['uf'];
    $clientes->senha = $_POST['senha'];
    $clientes->limite = $_POST['limite'];
    $clientes->mae = $_POST['mae'];
    $clientes->pai = $_POST['pai'];
    $clientes->conjuge = $_POST['conjuge'];
    $clientes->ref1 = $_POST['ref1'];
    $clientes->ref2 = $_POST['ref2'];
    $clientes->fone_ref1 = $_POST['fone_ref1'];
    $clientes->fone_ref2 = $_POST['fone_ref2'];
    $clientes->profissao = $_POST['profissao'];
    $clientes->nascimento = $_POST['nascimento'];
    $clientes->vencimento = $_POST['vencimento'];
    $clientes->numeracao = $_POST['numeracao'];
    $clientes->municipio_desc = $_POST['municipio_desc'];
    $clientes->uf_desc = $_POST['uf_desc'];
    if (is_null($clienntes->nascimento)) {
        $clientes->nascimento = '2099-01-01';
    }
    if (is_null($clientes->codigo) || $clientes->codigo==0) {
        $query = "insert into clientes (codigo,fantasia,razao_social,cpf_cnpj,inscricao_rg,contato,
                    cep,logradouro,complemento,bairro,fone,celular,email,inativo,classificacao,
                    numero,municipio,uf,senha,limite,mae,pai,conjuge,ref1,ref2,fone_ref1,fone_ref2,
                    profissao,nascimento,vencimento,numeracao,municipio_desc,uf_desc)  
              values(nextval('clientes_codigo_seq'),upper(semacento('" . $clientes->fantasia . "')),upper(semacento('" . $clientes->razao_social . "')),'" . $clientes->cpf_cnpj . "','" . $clientes->inscricao_rg . "','" . $clientes->contato . "','" . $clientes->cep . "',upper(semacento('" . $clientes->logradouro . "')),'" . $clientes->complemento . "',upper(semacento('" . $clientes->bairro . "')),'" . $clientes->fone . "','" . $clientes->celular . "','" . $clientes->email . "','" . $clientes->inativo . "','" . $clientes->classificacao . "','" . $clientes->numero . "','" . $clientes->municipio . "','" . $clientes->uf . "','" . $clientes->senha . "','" . $clientes->limite . "','" . $clientes->mae . "','" . $clientes->pai . "','" . $clientes->conjuge . "','" . $clientes->ref1 . "','" . $clientes->ref2 . "','" . $clientes->fone_ref1 . "','" . $clientes->fone_ref2 . "','" . $clientes->profissao . "','" . $clientes->nascimento . "','" . $clientes->vencimento . "','" . $clientes->numeracao . "',upper(semacento('" . $clientes->municipio_desc . "')),upper('" . $clientes->uf_desc . "'))";
    } else {
        $query = "update clientes set ".
            " fantasia=upper(semacento('".$clientes->fantasia."')),".
            " razao_social=upper(semacento('".$clientes->razao_social."')),".
            " cpf_cnpj='".$clientes->cpf_cnpj."',".
            " inscricao_rg='".$clientes->inscricao_rg."',".
            " contato='".$clientes->contato."',".
            " cep='".$clientes->cep."',".
            " logradouro=upper(semacento('".$clientes->logradouro."')),".
            " complemento='".$clientes->complemento."',".
            " bairro=upper(semacento('".$clientes->bairro."')),".
            " fone='".$clientes->fone."',".
            " celular='".$clientes->celular."',".
            " email='".$clientes->email."',".
            " inativo='".$clientes->inativo."',".
            " classificacao='".$clientes->classificacao."',".
            " numero='".$clientes->numero."',".
            " municipio='".$clientes->municipio."',".
            " uf='".$clientes->uf."',".
            " senha='".$clientes->senha."',".
            " limite='".$clientes->limite."',".
            " mae='".$clientes->mae."',".
            " pai='".$clientes->pai."',".
            " conjuge='".$clientes->conjuge."',".
            " ref1='".$clientes->ref1."',".
            " ref2='".$clientes->ref2."',".
            " fone_ref1='".$clientes->fone_ref1."',".
            " fone_ref2='".$clientes->fone_ref2."',".
            " profissao='".$clientes->profissao."',".
            " nascimento='".$clientes->nascimento."',".
            " vencimento='".$clientes->vencimento."',".
            " numeracao='".$clientes->numeracao."',".
            " municipio_desc=upper(semacento('".$clientes->municipio_desc."')),".
            " uf_desc='".$clientes->uf_desc."'".
            " where codigo=".$clientes->codigo;
        
    }
    $result = pg_query($conexao, $query);
    $response = $query;

    echo json_encode($response);
    die();
}

?>