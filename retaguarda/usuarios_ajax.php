<?php
include "conexao.php";
include "usuarios_class.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $usuario = $_POST['usuario'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}

// Fetch all records
if ($request == 'fetchall') {

    if (is_numeric($desc_pesquisa)) {
        $query = "SELECT codigo,usuario FROM usuarios where codigo='" . $desc_pesquisa . "'";
        $query_quantos = "SELECT count(*) FROM usuarios where codigo='" . $desc_pesquisa . "'";
    } else {
        $query = "SELECT codigo,usuario FROM usuarios where usuario like upper('%" . $desc_pesquisa . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM usuarios where usuario like upper('%" . $desc_pesquisa . "%')";
    }

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {

        $codigo = $row['codigo'];
        $usuario = $row['usuario'];

        $quantos = $row_quantos[0];

        $response[] = array(
            "codigo" => $codigo,
            "usuario" => $usuario,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}
// PARA CONSULTAR CODIGO DO PRODUTO EXISTENTE
if ($request == 'consultarCodigoUsuario') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select codigo from usuarios where codigo='" . $codigo . "'";
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
if ($request == 'atribuirUsuarios') {
    $response = "0";
    $usuarios = new usuarios();
    $usuarios->codigo = $_POST['codigo'];
    $query = "select * from usuarios where codigo=" . $usuarios->codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "usuario" => $row['usuario'],
            "senha" => $row['senha'],
            "operador" => $row['operador'],
            "gerente" => $row['gerente'],
            "supervisor" => $row['supervisor'],
            "per_produtos" => $row['per_produtos'],
            "per_clientes" => $row['per_clientes'],
            "per_fornecedores" => $row['per_fornecedores'],
            "per_transportadoras" => $row['per_transportadoras'],
            "per_vendedores" => $row['per_vendedores'],
            "per_usuarios" => $row['per_usuarios'],
            "per_entregas" => $row['per_entregas'],
            "per_compradores" => $row['per_compradores'],
            "per_nfe" => $row['per_nfe'],
            "per_orcamentos" => $row['per_orcamentos'],
            "per_pedidos" => $row['per_pedidos'],
            "per_compras" => $row['per_compras'],
            "per_contas_pagar" => $row['per_contas_pagar'],
            "per_contas_receber" => $row['per_contas_receber'],
            "per_relatorios" => $row['per_relatorios'],
            "per_configuracao" => $row['per_configuracao'],
            "per_produtos_con" => $row['per_produtos_con'],
            "inativo" => $row['inativo']
        );
    }

    echo json_encode($response);
    die();
}

// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarUsuarios') {
    $response = "0";
    $usuarios = new usuarios();
    $usuarios->codigo = $_POST['codigo'];
    $usuarios->usuario= $_POST['usuario'];
    $usuarios->senha=$_POST['senha'];
    if ($_POST['operador'] == 'true') {
        $usuarios->operador = "1";
    } else {
        $usuarios->operador = "0";
    }
    if ($_POST['gerente'] == 'true') {
        $usuarios->gerente= "1";
    } else {
        $usuarios->gerente= "0";
    }
    if ($_POST['supervisor'] == 'true') {
        $usuarios->supervisor = "1";
    } else {
        $usuarios->supervisor= "0";
    }
    if ($_POST['per_produtos'] == 'true') {
        $usuarios->per_produtos= "1";
    } else {
        $usuarios->per_produtos= "0";
    }
    if ($_POST['per_clientes'] == 'true') {
        $usuarios->per_clientes = "1";
    } else {
        $usuarios->per_clientes= "0";
    }
    if ($_POST['per_fornecedores'] == 'true') {
        $usuarios->per_fornecedores = "1";
    } else {
        $usuarios->per_fornecedores= "0";
    }
    if ($_POST['per_transportadoras'] == 'true') {
        $usuarios->per_transportadoras = "1";
    } else {
        $usuarios->per_transportadoras= "0";
    }
    if ($_POST['per_vendedores'] == 'true') {
        $usuarios->per_vendedores = "1";
    } else {
        $usuarios->per_vendedores= "0";
    }
    if ($_POST['per_usuarios'] == 'true') {
        $usuarios->per_usuarios= "1";
    } else {
        $usuarios->per_usuarios= "0";
    }
    if ($_POST['per_entregas'] == 'true') {
        $usuarios->per_entregas= "1";
    } else {
        $usuarios->per_entregas= "0";
    }
    if ($_POST['per_compradores'] == 'true') {
        $usuarios->per_compradores= "1";
    } else {
        $usuarios->per_compradores= "0";
    }
    if ($_POST['per_nfe'] == 'true') {
        $usuarios->per_nfe= "1";
    } else {
        $usuarios->per_nfe= "0";
    }
    if ($_POST['per_orcamentos'] == 'true') {
        $usuarios->per_orcamentos= "1";
    } else {
        $usuarios->per_orcamentos= "0";
    }
    if ($_POST['per_pedidos'] == 'true') {
        $usuarios->per_pedidos= "1";
    } else {
        $usuarios->per_pedidos= "0";
    }
    if ($_POST['per_compras'] == 'true') {
        $usuarios->per_compras= "1";
    } else {
        $usuarios->per_compras= "0";
    }
    if ($_POST['per_contas_pagar'] == 'true') {
        $usuarios->per_contas_pagar= "1";
    } else {
        $usuarios->per_contas_pagar= "0";
    }
    if ($_POST['per_contas_receber'] == 'true') {
        $usuarios->per_contas_receber= "1";
    } else {
        $usuarios->per_contas_receber= "0";
    }
    if ($_POST['per_relatorios'] == 'true') {
        $usuarios->per_relatorios= "1";
    } else {
        $usuarios->per_relatorios= "0";
    }
    if ($_POST['per_configuracao'] == 'true') {
        $usuarios->per_configuracao= "1";
    } else {
        $usuarios->per_configuracao= "0";
    }
    if ($_POST['per_produtos_con'] == 'true') {
        $usuarios->per_produtos_con= "1";
    } else {
        $usuarios->per_produtos_con= "0";
    }
    if ($_POST['inativo'] == 'true') {
        $usuarios->inativo = "1";
    } else {
        $usuarios->inativo = "0";
    }
    
    if (is_null($usuarios->codigo) || $usuarios->codigo==0) {
        $query = "insert into usuarios (codigo,usuario,senha,operador,supervisor,per_produtos,per_clientes,per_fornecedores,
per_transportadoras,per_vendedores,per_usuarios,per_entregas,per_compradores,per_nfe,per_orcamentos,per_pedidos,per_compras,per_contas_pagar,per_contas_receber,
per_relatorios,per_configuracao,per_produtos_con,inativo)  
              values(nextval('usuarios_codigo_seq'),'" . $usuarios->usuario. "','" . 
              $usuarios->senha. "','" . $usuarios->operador. "','" . $usuarios->supervisor. "','" . $usuarios->per_produtos. "','" .
              $usuarios->per_clientes. "','" . $usuarios->per_fornecedores. "','" . $usuarios->per_transportadoras. "','" . $usuarios->per_vendedores. "','" . 
              $usuarios->per_usuarios. "','" . $usuarios->per_entregas. "','" . $usuarios->per_compradores. "','" . $usuarios->per_nfe. "','" . 
              $usuarios->per_orcamentos. "','" . $usuarios->per_pedidos. "','" . $usuarios->per_compras. "','" . $usuarios->per_contas_pagar. "','" . 
              $usuarios->per_contas_receber. "','" . $usuarios->per_relatorios. "','" . $usuarios->per_configuracao. "','" . $usuarios->per_produtos_con. "','" .
              $usuarios->inativo ."')";
    } else {
        $query = "update usuarios set ".
            " usuario=upper(semacento('".$usuarios->usuario."')),".
            " senha='".$usuarios->senha."',".
            " operador='".$usuarios->operador."',".
            " gerente='".$usuarios->gerente."',".
            " supervisor='".$usuarios->supervisor."',".
            " per_produtos='".$usuarios->per_produtos."',".
            " per_clientes='".$usuarios->per_clientes."',".
            " per_fornecedores='".$usuarios->per_fornecedores."',".
            " per_transportadoras='".$usuarios->per_transportadoras."',".
            " per_vendedores='".$usuarios->per_vendedores."',".
            " per_usuarios='".$usuarios->per_usuarios."',".
            " per_entregas='".$usuarios->per_entregas."',".
            " per_compradores='".$usuarios->per_compradores."',".
            " per_nfe='".$usuarios->per_nfe."',".
            " per_orcamentos='".$usuarios->per_orcamentos."',".
            " per_pedidos='".$usuarios->per_pedidos."',".
            " per_compras='".$usuarios->per_compras."',".
            " per_contas_pagar='".$usuarios->per_contas_pagar."',".
            " per_contas_receber='".$usuarios->per_contas_receber."',".
            " per_relatorios='".$usuarios->per_relatorios."',".
            " per_configuracao='".$usuarios->per_configuracao."',".
            " per_produtos_con='".$usuarios->per_produtos_con."',".
            " inativo='".$usuarios->inativo."' ".
            " where codigo=".$usuarios->codigo;
            
            
    }
    $result = pg_query($conexao, $query);
    $response = $query;

    echo json_encode($response);
    die();
}

?>