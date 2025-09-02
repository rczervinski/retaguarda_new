<?php
session_start();
include "conexao.php";
include "nfe_class.php";
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $razao_social = $_POST['razao_social'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
    $desc_pesquisa_cliente = $_POST['desc_pesquisa_cliente'];
    $desc_pesquisa_transportadora = $_POST['desc_pesquisa_transportadora'];
    $desc_pesquisa_produto = $_POST['desc_pesquisa_produto'];
}
// Fetch all records
if ($request == 'fetchall') {
    if (is_numeric($desc_pesquisa)) {
        $query = "select codigo_interno,documento,serie,emissao,hora,c.razao_social as cliente,status from nf_base inner join clientes c on nf_base.cliente=c.codigo where documento='" . $desc_pesquisa . "'";
        $query_quantos = "select count(*) from nf_base inner join clientes c on nf_base.cliente=c.codigo where documento='" . $desc_pesquisa . "'";
    } else {
        $query = "select codigo_interno,documento,serie,emissao,hora,c.razao_social as cliente,status from nf_base inner join clientes c on nf_base.cliente=c.codigo where c.razao_social like upper('%" . $desc_pesquisa . "%') order by documento LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "select count(*) from nf_base inner join clientes c on nf_base.cliente=c.codigo where c.razao_social like upper('%" . $desc_pesquisa . "%')"; 
    }
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_interno = $row['codigo_interno'];
        $documento = $row['documento'];
        $serie = $row['serie'];
        $emissao = $row['emissao'];
        $hora = $row['hora'];
        $cliente = $row['cliente'];
        $status = $row['status'];
        $quantos = $row_quantos[0];
        $response[] = array(
            "codigo_interno" => $codigo_interno,
            "documento" => $documento,
            "serie" => $serie,
            "emissao" => $emissao,
            "hora" => $hora,
            "cliente" => $cliente,
            "status" => $status,
            "quantos" => $quantos
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'fetchalltransportadoras') {
    if (is_numeric($desc_pesquisa_transportadora)) {
        $query = "SELECT codigo,razao_social FROM transportadoras where codigo='" . $desc_pesquisa_transportadora . "'";
        $query_quantos = "SELECT count(*) FROM transportadoras where codigo='" . $desc_pesquisa_transportadora . "'";
    } else {
        $query = "SELECT codigo,razao_social FROM transportadoras where razao_social like upper('%" . $desc_pesquisa_transportadora . "%') order by razao_social LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "SELECT count(*) FROM transportadoras where razao_social like upper('%" . $desc_pesquisa_transportadora . "%')";
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
if ($request == 'fetchallnfeprodutos') {
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
// PARA CONSULTAR CODIGO DO PRODUTO EXISTENTE
if ($request == 'consultarCodigoProduto') {
    $response = "0";
    $codigo_gtin = $_POST['codigo_gtin'];
    $query = "select codigo_interno from produtos where codigo_gtin='" . $codigo_gtin . "'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_interno = $row['codigo_interno'];
        $response[] = array(
            "codigo_interno" => $codigo_interno
        );
    }
    echo json_encode($response);
    die();
}
// 
if ($request == 'pegar_numeracao') {
    $query = "select documento,serie,tipo,natureza,emissao,saida from nf_base where cliente=0 or cliente is null";
    $result = pg_query($conexao, $query);
    $response = array();
    $contem = 0;
    while ($row = pg_fetch_assoc($result)) {
        $contem = 1;
        $documento = $row['documento'];
        $serie = $row['serie'];
        $tipo = $row['tipo'];
        $natureza= $row['natureza'];
        $emissao= $row['emissao'];
        $saida= $row['saida'];
        $response[] = array(
            "documento" => $documento,
            "serie" => $serie,
            "tipo" => $tipo,
            "natureza" => $natureza,
            "emissao" => $emissao,
            "saida" => $saida
        );
    }
    if ($contem == 0) {
        $query2 = "SELECT nextval('nf_documento_seq') as documento ,max(serie) as serie,current_date as emissao,1 as tipo, 5102 as natureza  from nf_base";
        $result = pg_query($conexao, $query2);
        while ($row = pg_fetch_assoc($result)) {
            $contem = 1;
            $documento = $row['documento'];
            $serie = $row['serie'];
            $emissao = $row['emissao'];
            $saida = $row['emissao'];
            $tipo = $row['tipo'];
            $natureza= $row['natureza'];
            if($serie<=1){
                $serie=1; 
            }
            $response[] = array(
                "documento" => $documento,
                "serie" => $serie,
                "tipo" => $tipo,
                "natureza" => $natureza,
                "emissao" => $emissao, 
                "saida" => $saida
            );
            $query2 = "insert into nf_base(codigo_interno,documento,serie,tipo,natureza,saida,emissao) values(nextval('nf_codigo_seq')," . $documento . "," . $serie . ",".$tipo.",".$natureza.",'".$saida."','".$emissao."')";
            $result = pg_query($conexao, $query2);
        }
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregar_estabelecimento') {
    $query = "select ie, cnpj, razao_social, modelonf,uf from estabelecimentos";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $ie = $row['ie'];
        $cnpj = $row['cnpj'];
        $razao_social = $row['razao_social'];
        $modelo_nf = $row['modelo_nf'];
        $uf = $row['uf'];
        $response[] = array(
            "ie" => $ie,
            "cnpj" => $cnpj,
            "razao_social" => $razao_social,
            "modelonf" => $modelonf,
            "uf" => $uf
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'atribuirNfe') {
    $codigo = $_POST['codigo'];
    $query="select * from nf_base where codigo_interno=".$codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "documento" => $row['documento'],
            "serie" => $row['serie'],
            "tipo" => $row['tipo'],
            "natureza" => $row['natureza'],
            "cliente" => $row['cliente'],
            "emissao" => $row['emissao'],
            "saida" => $row['saida'],
            "hora" => $row['hora'],
            "dados_adicionais" => $row['dados_adicionais'],
            "dados_adicionais2" => $row['dados_adicionais2']
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'atribuirCliente') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $documento = $_POST['documento'];
    $serie = $_POST['serie'];
    $tipo = $_POST['tipo'];
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
    $query="update nf_base set tipo=".$tipo.",hora=substring(cast(current_time as text),1,8),emissao=current_date,saida=current_date, cliente=".$codigo." where documento=".$documento." and serie=".$serie; 
    $result = pg_query($conexao, $query);
    echo json_encode($response);
    die();
} 
// pegarDadosProdutoSelecionado
if ($request == 'pegarDadosProdutoSelecionado') {
    $response = "0";
    $codigo = $_POST['codigo_gtin'];
    $query = "select p.descricao, pb.preco_venda,pb.codigo_ncm,pt.situacao_tributaria,pb.cfop,pb.unidade, po.val_desc_a,  " . 
    " po.val_desc_b, po.val_desc_c, po.val_desc_d,po.val_desc_e, pt.aliquota_icms, pt.aliquota_ipi, pt.icms_reducao_bc, p.codigo_interno,pt.perc_mva_icms_st,pt.aliquota_icms_st,pt.aliquota_fcp,pt.aliquota_fcp_st,pt.perc_dif,pt.icms_reducao_bc_st " . 
    " from produtos p INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno INNER JOIN produtos_tb pt ON pt.codigo_interno = p.codigo_interno INNER JOIN produtos_ou po ON po.codigo_interno=p.codigo_interno where p.codigo_gtin='" . $codigo . "'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo_gtin" => $codigo,
            "descricao" => $row['descricao'],
            "preco_venda" => $row['preco_venda'],
            "codigo_ncm" => $row['codigo_ncm'],
            "situacao_tributaria" => $row['situacao_tributaria'],
            "cfop" => $row['cfop'],
            "unidade" => $row['unidade'],
            "val_desc_a" => $row['val_desc_a'],
            "val_desc_b" => $row['val_desc_b'],
            "val_desc_c" => $row['val_desc_c'],
            "val_desc_d" => $row['val_desc_d'],
            "val_desc_e" => $row['val_desc_e'], 
            "aliquota_icms" => $row['aliquota_icms'],
            "aliquota_ipi" => $row['aliquota_ipi'],
            "codigo_interno" => $row['codigo_interno'],
            "perc_mva_icms_st" => $row['perc_mva_icms_st'],
            "aliquota_icms_st" => $row['aliquota_icms_st'],
            "aliquota_fcp" => $row['aliquota_fcp'],
            "aliquota_fcp_st" => $row['aliquota_fcp_st'],
            "perc_dif" => $row['perc_dif'],
            "icms_reducao_bc_st" => $row['icms_reducao_bc_st'],
            "icms_reducao_bc" => $row['icms_reducao_bc']
        );
        $_SESSION['produtonfe']=$response;
    }
    echo json_encode($response);
    die();
}
if ($request == 'adicionarProdutoNfe') {
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    $cst_cson=$_POST['cst_cson'];
    $cfop=$_POST['cfop'];
    //number_format($number, 2, '.', '');
    $qtde=str_replace(",",".",$_POST['qtde']);
    $complemento=$_POST['complemento'];
    $desconto=str_replace(",",".",$_POST['desconto']);
    $valor_unit=str_replace(",",".",$_POST['valor_unit']);
    $response="";
    $prodSel=$_SESSION['produtonfe'];
    $codigo_gtin=$prodSel[0]['codigo_gtin'];
    $descricao=$prodSel[0]['descricao'];
    $codigo_ncm=$prodSel[0]['codigo_ncm'];
    $unidade=$prodSel[0]['unidade'];
    $codigo_interno=$prodSel[0]['codigo_interno'];
    $uf=$_SESSION['uf'];
    $tipo=$_POST['tipo'];
    $finalidade=$_POST['finalidade'];
    $percicmsdevol=str_replace(",",".",$_POST['percicmsdevol']);
    $bcicmsdevol=str_replace(",",".",$_POST['bcicmsdevol']);
    $icms_reducao_bc_st=$prodSel[0]['icms_reducao_bc_st'];
    $icms_reducao_bc=$prodSel[0]['icms_reducao_bc'];
    $perc_mva_icms_st=$prodSel[0]['perc_mva_icms_st'];
    $aliquota_icms_st=$prodSel[0]['aliquota_icms_st'];
    $aliquota_icms=$prodSel[0]['aliquota_icms'];
    $aliquota_fcp=$prodSel[0]['aliquota_fcp'];
    $aliquota_fcp_st=$prodSel[0]['aliquota_fcp_st'];
    $perc_dif=$prodSel[0]['perc_dif'];
    $bc_icms=0;
    $bc_icms_st=0;
    $val_icms=0;
    $val_icms_st=0;
    $val_ipi=0;
    $aliquota_ipi=0;
    $desconto=0;
    //Para notas com ST diferente da UF do estabelecimento
//     if($tipo==0){//Nota de entrada
//         if($uf!=41){
//             $queryTBUF="select * from produtos_tb_uf where codigo_interno=".$codigo_interno." and uf=".$uf;
//             $result = pg_query($conexao, $queryTBUF);
//             while ($row = pg_fetch_assoc($result)) {
//                 $icms_reducao_bc_st=$row['icms_reducao_bc_st'];
//                 $perc_mva_icms_st=$row['perc_mva_icms_st'];
//                 $aliquota_icms_st=$row['aliquota_icms_st'];
//                 $aliquota_icms=$row['aliquota_icms'];
//             }
//         }
//     }else{//Nota de saida
//         if($uf!=41){
//             $queryTBUF="select * from produtos_tb_uf where codigo_interno=".$codigo_interno." and uf=".$uf;
//             $result = pg_query($conexao, $queryTBUF);
//             while ($row = pg_fetch_assoc($result)) {
//                 $icms_reducao_bc_st=$row['icms_reducao_bc_st'];
//                 $perc_mva_icms_st=$row['perc_mva_icms_st'];
//                 $aliquota_icms_st=$row['aliquota_icms_st'];
//                 $aliquota_icms=$row['aliquota_icms'];
//             }
//         }
//     }
// CALCULA ICMS PARA REGIME NORMAL
    if($cst_cson<100 && $cst_cson!=60 && $cst_cson!=40 && $cst_cson!=51){
        if($aliquota_icms>0){
            $bc_icms=($valor_unit-$desconto)*$qtde;
            if($icms_reducao_bc>0){
                $bc_icms=$bc_icms-(($icms_reducao_bc/100)*$bc_icms);
            }
            $val_icms=($aliquota_icms/100)*$bc_icms;
        }
    }
    //CALCULA ICMS PARA DIFERIMENTO
    if($cst_cson==51){
        if($aliquota_icms>0){
            $bc_icms=($valor_unit-$desconto)*$qtde;
            if($icms_reducao_bc>0){
                $bc_icms=$bc_icms-(($icms_reducao_bc/100)*$bc_icms);
            }
            $val_icms=($aliquota_icms/100)*$bc_icms;
            $valicmsop=$val_icms;
            $valicmsdif=($perc_dif/100)*$valicmsop;
            $val_icms=$valicmsop-$valicmsdif;
        }
    }
    //PARA DETERMINADAS CST 101 900 70 90
    if($cst_cson==101 || $cst_cson==900 || $cst_cson==70 || $cst_cson==90){
        if($cst_cson==900 || $cst_cson==90 || $cst_cson==20){
            $bc_icms=$bcicmsdevol;
            $val_icms=($percicmsdevol/100)*$bcicmsdevol;
        }
    }
    /*
     * insert into nf_prod(nf_numero,nf_serie,codigo_gtin,descricao,codigo_ncm,cst_cson,cfop,unidade,quantidade,preco_unitario,total,
     * bc_icms,"
     * "val_icms,
     * val_ipi,
     * aliquota_icms,
     * aliquota_ipi,
     * desconto,
     * numero_serie,
     * aliquota_icms_st,
     * bc_icms_st,
     * val_icms_st,
     * aliquota_fcp,
     * aliquota_fcp_st,
     * val_fcp,
     * perc_dif,
     * val_icmsop,
     * val_icmsdif,
     * numero_pedido_compra,
     * item_pedido_compra) "
            " values(%d,%d,'%s','%s','%s','%s',%d,'%s',%f,%f,%f,%f,%f,%f,%f,%f,%f,'%s',%f,%f,%f,%f,%f,%f,%f,%f,%f,'%s',%d)",atoi((char*)nfe_numero->label()), atoi((char*)nfe_serie->value()),snfp.codigo_gtin,snfp.descricao,snfp.ncm_sh,
            snfp.cst_cson,snfp.cfop,snfp.unidade,snfp.quantidade,snfp.preco_unitario,snfp.total,snfp.bc_icms,snfp.val_icms,snfp.val_ipi,snfp.aliquota_icms,snfp.aliquota_ipi,
            snfp.desconto,snfp.nserie,snfp.aliq_icms_st,snfp.bc_icms_st,snfp.val_icms_st,snfp.aliquota_fcp,snfp.aliquota_fcp_st,snfp.val_fcp,snfp.perc_dif,snfp.vicmsop,snfp.vicmsdif,snfp.numero_pedido_compra,snfp.item_pedido_compra);
     * 
     */
    //Faz a inser��o do item em questao na NF_PROD
    $query="insert into nf_prod (nf_numero,nf_serie, codigo_gtin,descricao,codigo_ncm,cst_cson,cfop,unidade,quantidade,preco_unitario,total,".
    "bc_icms,val_icms,val_ipi,aliquota_icms,aliquota_ipi,desconto,aliquota_icms_st,bc_icms_st) values(".$nf_numero.",".$nf_serie.",'".$codigo_gtin."','".$descricao."','".$codigo_ncm."','".$cst_cson."','".$cfop.
    "','".$unidade."','".$qtde."','".$valor_unit."','".$qtde*$valor_unit."','".$bc_icms."','".$val_icms."','".$val_ipi."','".$aliquota_icms."','".$aliquota_ipi."','".$desconto."','".$aliquota_icms_st."','".$bc_icms_st."')"; 
    $result = pg_query($conexao, $query);
    //Seleciona os itens da nota em questao pra mostrar na grid
    $query2="select * from nf_prod where nf_numero=".$nf_numero." and nf_serie=".$nf_serie." order by codigo";
    $result = pg_query($conexao, $query2);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_gtin = $row['codigo_gtin'];
        $descricao = $row['descricao'];
        $codigo_ncm = $row['codigo_ncm'];
        $cst_cson = $row['cst_cson'];
        $cfop = $row['cfop'];
        $unidade = $row['unidade'];
        $quantidade = $row['quantidade'];
        $preco_unitario = $row['preco_unitario'];
        $total= $row['total'];
        $bc_icms= $row['bc_icms'];
        $val_icms= $row['val_icms'];
        $val_ipi= $row['val_ipi'];
        $aliquota_icms= $row['aliquota_icms'];
        $aliquota_ipi= $row['aliquota_ipi'];
        $desconto= $row['desconto'];
        $codigo= $row['codigo'];
        $frete= $row['frete'];
        $seguro= $row['seguro'];
        $outros= $row['outros'];
        $aliquota_icms_st= $row['aliquota_icms_st'];
        $bc_icms_st= $row['bc_icms_st'];
        $val_icms_st= $row['val_icms_st'];
        $aliquota_fcp= $row['aliquota_fcp'];
        $aliquota_fcp_st= $row['aliquota_fcp_st'];
        $val_fcp= $row['val_fcp'];
        $val_fcp_st= $row['val_fcp_st'];
        $perc_dif= $row['perc_dif'];
        $val_icmsop= $row['val_icmsop'];
        $val_icmsdif= $row['val_icmdif'];
        $response[] = array(
            "codigo_gtin" => $codigo_gtin,
            "descricao" => $descricao,
            "codigo_ncm" => $codigo_ncm,
            "cst_cson" => $cst_cson,
            "cfop" => $cfop,
            "unidade" => $unidade,
            "quantidade" => $quantidade,
            "preco_unitario" => $preco_unitario,
            "total" => $total,
            "bc_icms" => $bc_icms,
            "val_icms" => $val_icms,
            "val_ipi" => $val_ipi,
            "aliquota_icms" => $aliquota_icms,
            "aliquota_ipi" => $aliquota_ipi,
            "desconto" => $desconto, 
            "codigo" => $codigo,
            "frete" => $frete,
            "seguro" => $seguro,
            "outros" => $outros,
            "aliquota_icms_st" => $aliquota_icms_st,
            "bc_icms_st" => $bc_icms_st,
            "val_icms_st" => $val_icms_st,
            "aliquota_fcp" => $aliquota_fcp,
            "aliquota_fcp_st" => $aliquota_fcp_st,
            "val_fcp" => $val_fcp,
            "val_fcp_st" => $val_fcp_st,
            "perc_dif" => $perc_dif,
            "val_icmsop" => $val_icmsop,
            "val_icmdif" => $val_icmdif
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'deletaProdutoNfe') {
    $response='';
    $codigo= $_POST['codigo'];
    $query="delete from nf_prod where codigo=".$codigo." returning nf_numero,nf_serie";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $nf_numero = $row['nf_numero'];
        $nf_serie = $row['nf_serie'];
    }
    //Seleciona os itens da nota em questao pra mostrar na grid
    $query2="select * from nf_prod where nf_numero=".$nf_numero." and nf_serie=".$nf_serie." order by codigo";
    $result = pg_query($conexao, $query2);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_gtin = $row['codigo_gtin'];
        $descricao = $row['descricao'];
        $codigo_ncm = $row['codigo_ncm'];
        $cst_cson = $row['cst_cson'];
        $cfop = $row['cfop'];
        $unidade = $row['unidade'];
        $quantidade = $row['quantidade'];
        $preco_unitario = $row['preco_unitario'];
        $total= $row['total'];
        $bc_icms= $row['bc_icms'];
        $val_icms= $row['val_icms'];
        $val_ipi= $row['val_ipi'];
        $aliquota_icms= $row['aliquota_icms'];
        $aliquota_ipi= $row['aliquota_ipi'];
        $desconto= $row['desconto'];
        $codigo= $row['codigo'];
        $frete= $row['frete'];
        $seguro= $row['seguro'];
        $outros= $row['outros'];
        $aliquota_icms_st= $row['aliquota_icms_st'];
        $bc_icms_st= $row['bc_icms_st'];
        $val_icms_st= $row['val_icms_st'];
        $aliquota_fcp= $row['aliquota_fcp'];
        $aliquota_fcp_st= $row['aliquota_fcp_st'];
        $val_fcp= $row['val_fcp'];
        $val_fcp_st= $row['val_fcp_st'];
        $perc_dif= $row['perc_dif'];
        $val_icmsop= $row['val_icmsop'];
        $val_icmsdif= $row['val_icmdif'];
        $response[] = array(
            "codigo_gtin" => $codigo_gtin,
            "descricao" => $descricao,
            "codigo_ncm" => $codigo_ncm,
            "cst_cson" => $cst_cson,
            "cfop" => $cfop,
            "unidade" => $unidade,
            "quantidade" => $quantidade,
            "preco_unitario" => $preco_unitario,
            "total" => $total,
            "bc_icms" => $bc_icms,
            "val_icms" => $val_icms,
            "val_ipi" => $val_ipi,
            "aliquota_icms" => $aliquota_icms,
            "aliquota_ipi" => $aliquota_ipi,
            "desconto" => $desconto,
            "codigo" => $codigo,
            "frete" => $frete,
            "seguro" => $seguro,
            "outros" => $outros,
            "aliquota_icms_st" => $aliquota_icms_st,
            "bc_icms_st" => $bc_icms_st,
            "val_icms_st" => $val_icms_st,
            "aliquota_fcp" => $aliquota_fcp,
            "aliquota_fcp_st" => $aliquota_fcp_st,
            "val_fcp" => $val_fcp,
            "val_fcp_st" => $val_fcp_st,
            "perc_dif" => $perc_dif,
            "val_icmsop" => $val_icmsop,
            "val_icmdif" => $val_icmdif
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregarProdutoNfe') {
    $response='';
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    //Seleciona os itens da nota em questao pra mostrar na grid
    $query2="select * from nf_prod where nf_numero=".$nf_numero." and nf_serie=".$nf_serie." order by codigo";
    $result = pg_query($conexao, $query2);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_gtin = $row['codigo_gtin'];
        $descricao = $row['descricao'];
        $codigo_ncm = $row['codigo_ncm'];
        $cst_cson = $row['cst_cson'];
        $cfop = $row['cfop'];
        $unidade = $row['unidade'];
        $quantidade = $row['quantidade'];
        $preco_unitario = $row['preco_unitario'];
        $total= $row['total'];
        $bc_icms= $row['bc_icms'];
        $val_icms= $row['val_icms'];
        $val_ipi= $row['val_ipi'];
        $aliquota_icms= $row['aliquota_icms'];
        $aliquota_ipi= $row['aliquota_ipi'];
        $desconto= $row['desconto'];
        $codigo= $row['codigo'];
        $frete= $row['frete'];
        $seguro= $row['seguro'];
        $outros= $row['outros'];
        $aliquota_icms_st= $row['aliquota_icms_st'];
        $bc_icms_st= $row['bc_icms_st'];
        $val_icms_st= $row['val_icms_st'];
        $aliquota_fcp= $row['aliquota_fcp'];
        $aliquota_fcp_st= $row['aliquota_fcp_st'];
        $val_fcp= $row['val_fcp'];
        $val_fcp_st= $row['val_fcp_st'];
        $perc_dif= $row['perc_dif'];
        $val_icmsop= $row['val_icmsop'];
        $val_icmsdif= $row['val_icmdif'];
        $response[] = array(
            "codigo_gtin" => $codigo_gtin,
            "descricao" => $descricao,
            "codigo_ncm" => $codigo_ncm,
            "cst_cson" => $cst_cson,
            "cfop" => $cfop,
            "unidade" => $unidade,
            "quantidade" => $quantidade,
            "preco_unitario" => $preco_unitario,
            "total" => $total,
            "bc_icms" => $bc_icms,
            "val_icms" => $val_icms,
            "val_ipi" => $val_ipi,
            "aliquota_icms" => $aliquota_icms,
            "aliquota_ipi" => $aliquota_ipi,
            "desconto" => $desconto,
            "codigo" => $codigo,
            "frete" => $frete,
            "seguro" => $seguro,
            "outros" => $outros,
            "aliquota_icms_st" => $aliquota_icms_st,
            "bc_icms_st" => $bc_icms_st,
            "val_icms_st" => $val_icms_st,
            "aliquota_fcp" => $aliquota_fcp,
            "aliquota_fcp_st" => $aliquota_fcp_st,
            "val_fcp" => $val_fcp,
            "val_fcp_st" => $val_fcp_st,
            "perc_dif" => $perc_dif,
            "val_icmsop" => $val_icmsop,
            "val_icmdif" => $val_icmdif
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregar_imposto') {
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    $response='';
    $query="select sum(bc_icms) as bc_icms,sum(val_icms) as val_icms,sum(bc_icms_st) as bc_icms_st,sum(val_icms_st) as val_icms_st,sum(total) as val_prod,sum(val_ipi) as val_ipi from nf_prod where nf_numero =".$nf_numero." and nf_serie=".$nf_serie;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $bc_icms = $row['bc_icms'];
        $val_icms = $row['val_icms'];
        $bc_icms_st = $row['bc_icms_st'];
        $val_icms_st = $row['val_icms_st'];
        $val_prod = $row['val_prod'];
        $val_ipi = $row['val_ipi'];
        $response[] = array(
            "bc_icms" => $bc_icms,
            "val_icms" => $val_icms,
            "bc_icms_st" => $bc_icms_st,
            "val_icms_st" => $val_icms_st,
            "val_prod" => $val_prod,
            "val_ipi" => $val_ipi
        );
    }
    echo json_encode($response);
    die();
}
if($request=='gravarNFe'){
    $documento=$_POST['documento'];
    $serie=$_POST['serie'];
    $cfop=$_POST['cfop'];
    $tipo=$_POST['tipo'];
    $emissao=$_POST['emissao'];
    $saida=$_POST['saida'];
    $imposto_bc_icms=str_replace(",",".",$_POST['imposto_bc_icms']);
    $imposto_val_icms=str_replace(",",".",$_POST['imposto_val_icms']);
    $imposto_bc_icms_st=str_replace(",",".",$_POST['imposto_bc_icms_st']);
    $imposto_val_icms_st=str_replace(",",".",$_POST['imposto_val_icms_st']);
    $imposto_val_produtos=str_replace(",",".",$_POST['imposto_val_produtos']);
    $imposto_val_frete=str_replace(",",".",$_POST['imposto_val_frete']);
    $imposto_val_seguro=str_replace(",",".",$_POST['imposto_val_seguro']);
    $imposto_val_descontos=str_replace(",",".",$_POST['imposto_val_descontos']);
    $imposto_val_despesas=str_replace(",",".",$_POST['imposto_val_despesas']);
    $imposto_val_ipi=str_replace(",",".",$_POST['imposto_val_ipi']);
    $imposto_val_total=str_replace(",",".",$_POST['imposto_val_total']);
    $query="update nf_base set natureza=".$cfop.
    ", tipo=".$tipo.
    ", emissao='".$emissao.
    "', saida='".$saida.
    "' where documento=".$documento." and serie=".$serie;
    $result = pg_query($conexao, $query);
    $query="select 1 from nf_imposto where documento=".$documento." and serie=".$serie;
    $result = pg_query($conexao, $query);
    $linhas=pg_affected_rows($result);
    if($linhas>0){
        //update
        $query="update nf_imposto set bcicms=".$imposto_bc_icms.", vlicms=".$imposto_val_icms.", bcicmsst=".$imposto_bc_icms_st.", vlicmsst=".$imposto_val_icms_st.", vlprods=".$imposto_val_produtos.", vlfrete=".$imposto_val_frete.", vlseguro=".$imposto_val_seguro.", vldesconto=".$imposto_val_descontos.", vldespesas=".$imposto_val_despesas.", vlipi=".$imposto_val_ipi.", vltotal=".$imposto_val_total." where documento=".$documento." and serie=".$serie;
        $result = pg_query($conexao, $query);
    }else{
        //insert
        $query="insert into nf_imposto (documento,serie,bcicms,vlicms,bcicmsst,vlicmsst,vlprods,vlfrete,vlseguro,vldesconto,vldespesas,vlipi,vltotal) values(".$documento.",".$serie.",".$imposto_bc_icms.",".$imposto_val_icms.", ".$imposto_bc_icms_st.", ".$imposto_val_icms_st.", ".$imposto_val_produtos.", ".$imposto_val_frete.", ".$imposto_val_seguro.", ".$imposto_val_descontos.",".$imposto_val_despesas.", ".$imposto_val_ipi.", ".$imposto_val_total.") ";
        $result = pg_query($conexao, $query);
    }
    $response="OK";    
    echo json_encode($response);
    die();
}
if($request=='consultarFatura'){
    $documento=$_POST['documento'];
    $serie=$_POST['serie'];
    $query="select * from nf_fat where numero_nf='".$documento."' and serie_nf='".$serie."' ";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $codigo_interno = $row['codigo_interno'];
        $numero = $row['numero'];
        $vencimento = $row['vencimento'];
        $valor = $row['valor'];
        $response[] = array(
            "codigo_interno" => $codigo_interno,
            "numero" => $numero,
            "vencimento" => $vencimento,
            "valor" => $valor
        );
    }
    echo json_encode($response);
    die();
}
if($request=='adicionarFatura'){
    $documento=$_POST['documento'];
    $serie=$_POST['serie'];
    $valor=str_replace(",",".",$_POST['nfe_fat_valor']);
    $vencimento=$_POST['nfe_fat_vencimento'];
    $numero=$_POST['nfe_fat_numero'];
    $query="insert into nf_fat (numero_nf,serie_nf,numero,vencimento,valor) values('".$documento."','".$serie."','".$numero."','".$vencimento."','".$valor."')";
    $result = pg_query($conexao, $query);
    $query="select * from nf_fat where numero_nf='".$documento."' and serie_nf='".$serie."' ";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $codigo_interno = $row['codigo_interno'];
        $numero = $row['numero'];
        $vencimento = $row['vencimento'];
        $valor = $row['valor'];
        $response[] = array(
            "codigo_interno" => $codigo_interno,
            "numero" => $numero,
            "vencimento" => $vencimento,
            "valor" => $valor
        );
    }
    echo json_encode($response);
    die();
}
if($request=='deletar_fat'){
    $codigo_interno=$_POST['codigo_interno'];
    $query="delete from nf_fat where codigo_interno=".$codigo_interno;
    $result = pg_query($conexao, $query);
    $response="";
    echo json_encode($response);
    die();
}
if ($request == 'atribuirTransportadora') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "select * from transportadoras where codigo=" . $codigo;
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
if ($request == 'gravarTransportadoraNfe') {
    $response = "0";
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    $cod_transportadora=$_POST['cod_transportadora'];
    $por_conta=$_POST['por_conta'];
    $placa=$_POST['placa'];
    $cod_antt=$_POST['cod_antt'];
    $placa_uf=$_POST['placa_uf'];
    $quantidade=$_POST['quantidade'];
    $especie=$_POST['especie'];
    $marca=$_POST['marca'];
    $numeracao=$_POST['numeracao'];
    $peso_liquido=$_POST['peso_liquido'];
    $peso_bruto=$_POST['peso_bruto'];
    $query = "delete from nf_tra where nf_numero=" . $nf_numero." and nf_serie=".$nf_serie;
    $result = pg_query($conexao, $query);
    $query = "insert into nf_tra (nf_numero,nf_serie,cod_transportadora,por_conta,placa,placa_uf,quantidade,especie,marca,".
    "numeracao,peso_liquido,peso_bruto,cod_antt)".
        " values('".$nf_numero."','".$nf_serie."','".$cod_transportadora."','".$por_conta."','".
    $placa."','".$placa_uf."','".$quantidade."','".$especie."','".$marca."','".$numeracao."','".
    $peso_liquido."','".$peso_bruto."','".$cod_antt."')";
    $result = pg_query($conexao, $query);
    echo json_encode($query);
    die();
}
if ($request == 'limparTransportadoraNfe') {
    $response = "0";
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    $query = "delete from nf_tra where nf_numero=" . $nf_numero." and nf_serie=".$nf_serie;
    $result = pg_query($conexao, $query);
        echo json_encode($query);
        die();
}
if ($request == 'consultarTransportadora') {
    $response = "0";
    $nf_numero=$_POST['documento'];
    $nf_serie=$_POST['serie'];
    $query = "select * from nf_tra where nf_numero=" . $nf_numero." and nf_serie=".$nf_serie;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "cod_transportadora" => $row['cod_transportadora'],
            "por_conta" => $row['por_conta'],
            "placa" => $row['placa'],
            "cod_antt" => $row['cod_antt'],
            "placa_uf" => $row['placa_uf'],
            "quantidade" => $row['quantidade'],
            "especie" => $row['especie'],
            "marca" => $row['marca'],
            "numeracao" => $row['numeracao'],
            "peso_liquido" => $row['peso_liquido'],
            "peso_bruto" => $row['peso_bruto']
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'gravarDocRefNfe') {
    $nf_numero=$_POST['nf_numero'];
    $nf_serie=$_POST['nf_serie'];
    $nfe_referencia_chave=$_POST['nfe_referencia_chave'];
    $query = "delete from nf_docref where numero='".$nf_numero."' and serie='".$nf_serie."'";
    $result = pg_query($conexao, $query);
    $query = "insert into nf_docref (numero,serie,chave) values('".$nf_numero."','".$nf_serie."','".$nfe_referencia_chave."')";
    $result = pg_query($conexao, $query);
    echo json_encode($response);
    die();
}
if ($request == 'limparDocRefNfe') {
    $response = "0";
    $nf_numero= $_POST['nf_numero'];
    $nf_serie= $_POST['nf_serie'];
    $query = "delete from nf_docref where numero=" . $nf_numero." and serie=".$nf_serie;
    $result = pg_query($conexao, $query);
    echo json_encode($query);
    die();
}
if ($request == 'gravarInfAdicional') {
    $response = "0";
    $documento= $_POST['documento'];
    $serie= $_POST['serie'];
    $nfe_inf_adicional= $_POST['nfe_inf_adicional'];
    $nfe_inf_adicional2= $_POST['nfe_inf_adicional2'];
    $query = "update nf_base set dados_adicionais='".$nfe_inf_adicional."' , dados_adicionais2='".$nfe_inf_adicional2."' where documento=" . $documento." and serie=".$serie;
    $result = pg_query($conexao, $query);
    echo json_encode($query);
    die();
}
if ($request == 'gerarXML') {
    $data1=$_POST['data1'];
    $data2=$_POST['data2'];
    $query="select xml,chave from nf_base where protocolo is not null and status=2 and emissao between '".$data1."' and '".$data2."' " ;
    $result = pg_query($conexao, $query);
    deltree("notas");
    while($row = pg_fetch_assoc($result)){
        mkdir("notas");
        $arquivo = "notas/".$row['chave']."nfe.xml";
        $fp = fopen($arquivo, "a+");
        fwrite($fp, $row['xml']);
        fclose($fp);
    }
    ziparNotas();
    echo json_encode("OK");
    die();
}
function ziparNotas(){
    // Get real path for our folder
    $rootPath = realpath('notas');
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open('file.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
        );
    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
    // Zip archive will be created only after closing object
    $zip->close();
}
function delTree($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}
?>