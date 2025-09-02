<?php
session_start();
include "conexao.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
    $desc_pesquisa_cliente = $_POST['desc_pesquisa_cliente'];
    $desc_pesquisa_produto = $_POST['desc_pesquisa_produto'];
    $data1 = $_POST['data1'];
    $data2 = $_POST['data2'];
}
// Fetch all records
if ($request == 'fetchall') {
    if (is_numeric($desc_pesquisa)) {
        $query = "select db.codigo,to_char(db.data,'DD/MM/YYYY') as data,db.hora,c.razao_social,CASE WHEN db.desconto>0 THEN (sum(dp.total)-db.desconto)  ELSE sum(dp.total)  END as total from dav_base db inner join clientes c on db.cliente=c.codigo inner join dav_prod dp on db.codigo=dp.dav where db.codigo=". $desc_pesquisa." group by db.codigo,c.razao_social order by codigo LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "select count(db.codigo)  from dav_base db where db.codigo=". $desc_pesquisa;
    } else {
        if(strlen($data1)>2 and strlen($data2)>2){
            $query = "select db.codigo,to_char(db.data,'DD/MM/YYYY') as data,db.hora,c.razao_social,CASE WHEN db.desconto>0 THEN (sum(dp.total)-db.desconto)  ELSE sum(dp.total)  END as total from dav_base db inner join clientes c on db.cliente=c.codigo inner join dav_prod dp on db.codigo=dp.dav where c.razao_social like upper('%". $desc_pesquisa."%') and db.data between '".$data1."' and '".$data2."' group by db.codigo,c.razao_social order by codigo LIMIT 50 OFFSET " . ($pagina);
            $query_quantos = "select count(db.codigo)  from dav_base db inner join clientes c on db.cliente=c.codigo where c.razao_social like upper('%".$desc_pesquisa."%') and db.data between '".$data1."' and '".$data2."' ";
        }else{
            $query = "select db.codigo,to_char(db.data,'DD/MM/YYYY') as data,db.hora,c.razao_social,CASE WHEN db.desconto>0 THEN (sum(dp.total)-db.desconto)  ELSE sum(dp.total)  END as total from dav_base db inner join clientes c on db.cliente=c.codigo inner join dav_prod dp on db.codigo=dp.dav where c.razao_social like upper('%". $desc_pesquisa."%') group by db.codigo,c.razao_social order by codigo LIMIT 50 OFFSET " . ($pagina);
            $query_quantos = "select count(db.codigo)  from dav_base db inner join clientes c on db.cliente=c.codigo where c.razao_social like upper('%".$desc_pesquisa."%')";
        }
        
    }
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $data = $row['data'];
        $hora = $row['hora'];
        $nome = $row['razao_social'];
        $total = $row['total'];
        $quantos = $row_quantos[0];
        $response[] = array(
            "codigo" => $codigo,
            "data" => $data,
            "hora" => $hora,
            "nome" => $nome,
            "total" => round($total,2),
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}

if ($request == 'fetchallclientes') {
    if (is_numeric($desc_pesquisa_cliente)) {
        $query = "SELECT codigo,razao_social FROM clientes where codigo='" . $desc_pesquisa_cliente . "'";
        $query_quantos = "SELECT count(*) FROM clientes where codigo='" . $desc_pesquisa_cliente . "'";
    } else {
        $query = "SELECT codigo,razao_social FROM clientes where razao_social like upper('%" . $desc_pesquisa_cliente . "%') order by razao_social LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM clientes where razao_social like upper('%" . $desc_pesquisa_cliente . "%')";
    }
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $razao_social = $row['razao_social'];
        $quantos = $row_quantos[0];
        $response[] = array( 
            "codigo" => $codigo,
            "razao_social" => $razao_social,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}

if ($request == 'atribuirCliente') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select * from clientes where codigo=" . $codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "razao_social" => $row['razao_social'],
            "cpf_cnpj" => $row['cpf_cnpj'],
            "logradouro" => $row['logradouro'],
            "numero" => $row['numero'],
            "complemento" => $row['complemento'],
            "bairro" => $row['bairro'],
            "municipio" => $row['municipio'],
            "municipio_desc" => $row['municipio_desc'],
            "cep" => $row['cep'],
            "uf" => $row['uf'], 
            "uf_desc" => $row['uf_desc'],
            "inscricao_rg" => $row['inscricao_rg'],
            "fone" => $row['fone']
        );
    }
    echo json_encode($response);
    die();
} 

if ($request == 'fetchalldavprodutos') {
    if (is_numeric($desc_pesquisa_produto)) {
        $query = "SELECT codigo_gtin,descricao FROM produtos where codigo_gtin='" . $desc_pesquisa_produto . "'";
        $query_quantos = "SELECT count(*) FROM produtos where codigo_gtin='" . $desc_pesquisa_produto . "'";
    } else {
        $query = "SELECT codigo_gtin,descricao FROM produtos where descricao like upper('%" . $desc_pesquisa_produto . "%') LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM produtos where descricao like upper('%" . $desc_pesquisa_produto . "%')";
    }
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_gtin = $row['codigo_gtin'];
        $descricao = $row['descricao'];
        $quantos = $row_quantos[0];
        $response[] = array(
            "codigo_gtin" => $codigo_gtin,
            "descricao" => $descricao,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
}

if ($request == 'pegarDadosProdutoSelecionado') {
    $response = "0";
    $codigo = $_POST['codigo_gtin'];
    $query = "select p.descricao, pb.preco_venda,pb.unidade, po.val_desc_a,  " . 
    " po.val_desc_b, po.val_desc_c, po.val_desc_d,po.val_desc_e,p.codigo_interno " . 
    " from produtos p INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno INNER JOIN produtos_tb pt ON pt.codigo_interno = p.codigo_interno INNER JOIN produtos_ou po ON po.codigo_interno=p.codigo_interno where p.codigo_gtin='" . $codigo . "'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo_gtin" => $codigo,
            "descricao" => $row['descricao'],
            "preco_venda" => $row['preco_venda'],
            "unidade" => $row['unidade'],
            "val_desc_a" => $row['val_desc_a'],
            "val_desc_b" => $row['val_desc_b'],
            "val_desc_c" => $row['val_desc_c'],
            "val_desc_d" => $row['val_desc_d'],
            "val_desc_e" => $row['val_desc_e'],
            "codigo_interno" => $row['codigo_interno']
        );
    } 
    echo json_encode($response);
     die();
}

if ($request == 'adicionarProdutoOrcamento') {
    $response="";
    $codigo=$_POST['codigo'];
    $qtde=str_replace(",",".",$_POST['qtde']);
    $complemento=$_POST['complemento'];
    $codigo_gtin=$_POST['codigo_gtin'];
    $valor_unit=str_replace(",",".",$_POST['valor_unit']);
    $descricao=$_POST['descricao'];
    $cliente=$_POST['cliente'];
    $vendedor=$_POST['vendedor'];
    
    //Faz a inserção do item em questao na dav prod
    $data='';
    $hora='';

    if($codigo==0 || $codigo=='' || $codigo==NULL){
        $query="insert into dav_base (codigo, cliente, vendedor, data, hora) values (nextval('dav_base_codigo_seq'),".$cliente.",".$vendedor.",current_date,substring(cast(current_time as text),1,8)) returning codigo,data,hora ";
        $result = pg_query($conexao, $query);
        $row=pg_fetch_assoc($result);
        $codigo=$row['codigo'];
        $data=$row['data'];
        $hora=$row['hora'];
    } 
    
    $query="insert into dav_prod (dav,codigo_gtin,preco_venda,qtde,total,tabela_utilizada,complemento) values(".$codigo.",'".$codigo_gtin."',".$valor_unit.",".$qtde.",".$valor_unit*$qtde.",'0','".$complemento."')";
    $result = pg_query($conexao, $query);
    $query="select dp.codigo as codigo_interno, dp.codigo_gtin,p.descricao,dp.qtde,pb.unidade,dp.preco_venda,dp.total from dav_prod dp inner join produtos p on dp.codigo_gtin=p.codigo_gtin inner join produtos_ib pb on p.codigo_interno=pb.codigo_interno where dav=".$codigo." order by codigo";
    $result = pg_query($conexao, $query);
    $response = array();
    while($row=pg_fetch_assoc($result)){
        $response[] = array(
            "codigo" => $codigo,
            "codigo_interno" => $row['codigo_interno'],
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao'],
            "quantidade" => $row['qtde'],
            "unidade" => $row['unidade'],
            "preco_venda" => $row['preco_venda'],
            "total" => $row['total'],
            "data" => $data,
            "hora" => $hora,
       );
    }
    echo json_encode($response);
    die();
}
if ($request == 'deletaProdutoDav') {
    $response='';
    $codigo= $_POST['codigo'];
    $query="delete from dav_prod where codigo=".$codigo." returning dav";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $dav = $row['dav'];
    }
    //Seleciona os itens da nota em questao pra mostrar na grid
    $query="select dp.codigo as codigo_interno, dp.codigo_gtin,p.descricao,dp.qtde,pb.unidade,dp.preco_venda,dp.total from dav_prod dp inner join produtos p on dp.codigo_gtin=p.codigo_gtin inner join produtos_ib pb on p.codigo_interno=pb.codigo_interno where dav=".$dav." order by codigo";
    $result = pg_query($conexao, $query);
    $response = array();
    while($row=pg_fetch_assoc($result)){
        $response[] = array(
            "codigo_interno" => $row['codigo_interno'],
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao'],
            "quantidade" => $row['qtde'],
            "unidade" => $row['unidade'],
            "preco_venda" => $row['preco_venda'],
            "total" => $row['total'],
       );
    }
    echo json_encode($response);
    die();
}

if($request=='gravarDav'){
    $dav_codigo=$_POST['dav_codigo'];
    $dav_observacao=$_POST['dav_observacao'];
    $cliente=$_POST['cliente'];
    $vendedor=$_POST['vendedor'];
    $desconto=$_POST['dav_desconto'];
    if($desconto==NULL or $desconto==''){
        $desconto=0;
    }
    $dav_desconto=str_replace(",",".",$desconto);
    $query="update dav_base set vendedor=".$vendedor.", cliente=".$cliente.", obs='".$dav_observacao."', desconto=".$dav_desconto." where codigo =".$dav_codigo;
    $result = pg_query($conexao, $query);
    $response="OK";
    echo json_encode($response);
    die();
}

if ($request == 'carregar_vendedores') {
    $query = "select codigo,nome from vendedores order by nome";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $nome = $row['nome'];
        $codigo_vendedor = $row['codigo'];
        $response[] = array(
            "codigo_vendedor" => $codigo_vendedor,
            "nome" => $nome,
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'calcularTotalDav') {
    $dav_codigo=$_POST['dav_codigo'];
    $query = "select db.desconto,sum(dp.preco_venda*dp.qtde) as subtotal from dav_base db INNER JOIN dav_prod dp on db.codigo=dp.dav where db.codigo=".$dav_codigo." group by db.desconto";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $subtotal = $row['subtotal'];
        $desconto = $row['desconto'];
        $total=$subtotal-$desconto;
        $response[] = array(
            "subtotal" => $subtotal,
            "desconto" => $desconto,
            "total" => $total
        );
    }
    echo json_encode($response);
    die();
}

if ($request == 'atribuirDav') {
    $codigo = $_POST['codigo'];
    $query="select *,to_char(data,'DD/MM/YYYY') as dataformatada from dav_base where codigo=".$codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "documento" => $row['documento'],
            "cliente" => $row['cliente'],
            "vendedor" => $row['vendedor'],
            "data" => $row['dataformatada'],
            "hora" => $row['hora'],
            "obs" => $row['obs'],
            "desconto" => $row['desconto'],
            "forma_pagamento" => $row['forma_pagamento'],
            "programa" => $row['programa']
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregarProdutoDav') {
    $response='';
    $dav= $_POST['codigo'];
    
    //Seleciona os itens da nota em questao pra mostrar na grid
    $query="select dp.codigo as codigo_interno, dp.codigo_gtin,p.descricao,dp.qtde,pb.unidade,dp.preco_venda,dp.total from dav_prod dp inner join produtos p on dp.codigo_gtin=p.codigo_gtin inner join produtos_ib pb on p.codigo_interno=pb.codigo_interno where dav=".$dav." order by codigo";
    $result = pg_query($conexao, $query);
    $response = array();
    while($row=pg_fetch_assoc($result)){
        $response[] = array(
            "codigo_interno" => $row['codigo_interno'],
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao'],
            "quantidade" => $row['qtde'],
            "unidade" => $row['unidade'],
            "preco_venda" => $row['preco_venda'],
            "total" => $row['total'],
       );
    }
    echo json_encode($response);
    die();
}
?>