<?php
include "conexao.php";
include "util.php";
session_start();
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $data1 = $_POST['data1'];
    $data2 = $_POST['data2'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}
//Seleciona as contas(clientes) na tela de pesquisa inicial
if ($request == 'fetchall') {
    $_SESSION=array(); 
    session_destroy();
    $codfor=$_POST['codfor'];
    if(strlen($data1)<1){
        $data1="1900-01-01";
    }
    if(strlen($data2)<1){
        $data1="2900-01-01";
    }
    if(strlen($codfor)>0){
        $query="select to_char(p.vencimento,'DD/MM/YYYY') as vencimento,f.fantasia,p.valor,p.tipo,p.operador,p.documento,p.parcela,p.codigo from contas_pagar p INNER JOIN fornecedores f ON p.fornecedor=f.codigo where 1=1 and p.fornecedor=".$codfor." and p.vencimento between '".$data1."' and '".$data2."' and p.data_pgto is null order by p.vencimento LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "select count(p.codigo) from contas_pagar p INNER JOIN fornecedores f ON p.fornecedor=f.codigo where 1=1 and p.fornecedor=".$codfor." and p.vencimento between '".$data1."' and '".$data2."' and p.data_pgto is null ";
    }else{
        $query="select to_char(p.vencimento,'DD/MM/YYYY') as vencimento,f.fantasia,p.valor,p.tipo,p.operador,p.documento,p.parcela,p.codigo from contas_pagar p INNER JOIN fornecedores f ON p.fornecedor=f.codigo where 1=1 and p.vencimento between '".$data1."' and '".$data2."' and p.data_pgto is null order by p.vencimento LIMIT 50 OFFSET " . ($pagina);
        $query_quantos = "select count(p.codigo) from contas_pagar p INNER JOIN fornecedores f ON p.fornecedor=f.codigo where 1=1 and p.vencimento between '".$data1."' and '".$data2."' and p.data_pgto is null ";
    }

     $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $vencimento = $row['vencimento'];
        $fantasia = $row['fantasia'];
        $valor = $row['valor'];
        $tipo = $row['tipo'];
        $operador = $row['operador'];
        $documento = $row['documento'];
        $parcela = $row['parcela'];
        $quantos = $row_quantos[0];
        $response[] = array(
            "codigo" => $codigo,
            "vencimento" => $vencimento,
            "fantasia" => $fantasia,
            "valor" => $valor,
            "tipo" => $tipo,
            "documento" => $documento,
            "parcela" => $parcela,
            "quantos" => $quantos
        );
    }
    
    echo json_encode($response);
    die();  
}

if ($request == 'fetchfor') {
    $_SESSION=array(); 
    session_destroy();

    $query="select codigo, razao_social, fantasia from fornecedores where upper(razao_social) like upper('%".$desc_pesquisa."%') order by razao_social LIMIT 50 OFFSET " . ($pagina);
    $query_quantos = "select count(codigo) from fornecedores where upper(razao_social) like upper('%".$desc_pesquisa."%')";

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo = $row['codigo'];
        $fantasia = $row['fantasia'];
        $razao_social= $row['razao_social'];
        $response[] = array(
            "codigo" => $codigo,
            "fantasia" => $fantasia,
            "razao_social" => $razao_social
        );
    }
    echo json_encode($response);
    die();
}

if ($request == 'selecionarFornecedor') {
    $_SESSION=array(); 
    session_destroy();
    $codigo=$_POST['codigo'];
    $query="select razao_social from fornecedores where codigo=".$codigo;
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $razao_social= $row['razao_social'];
        $response[] = array(
            "codigo" => $codigo,
            "razao_social" => $razao_social
        );
    }
    echo json_encode($response);
    die();
}

//Grava uma conta a receber do cliente em questao
if ($request == 'adicionarReceber') {
    $codigo= $_POST['codigo'];
    $valor= padraoAmericano($_POST['valor']);
    $vencimento= $_POST['vencimento'];
    $discriminacao= $_POST['discriminacao'];
    $query="insert into contas_receber2 (cliente,data,credito,venda,debito,vencimento,historico) values(".$codigo.",current_date,0,0,".$valor.",'".$vencimento."','".$discriminacao."')";
    $result = pg_query($conexao, $query);
    $response="OK";
    echo json_encode($response);
    die();
}
//Monta as contas do cliente em questao
if ($request == 'atribuirReceber') {
    if(!isset($_SESSION['cesta'])){
        $_SESSION['cesta']=array();
    }
    $codigo=$_POST['codigo'];
    $query="select cr.cliente,cr.venda,to_char(cr.data,'DD/MM/YYYY') as data,cr.credito,cr.debito, ". 
        "CASE WHEN (((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100 )>0 ". 
        "THEN ".
        "((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100 ". 
        "ELSE ".
        "'0' ".
        "END as juros,".
        "CASE WHEN (((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100 )>0 ". 
        "THEN ".
        "(((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100)+cr.debito ". 
        "ELSE ".
        "cr.debito ". 
        "END as total ".
        ",cr.historico,to_char(cr.vencimento,'DD/MM/YYYY') as vencimento,cr.codigo from contas_receber2 cr, indices_financeiros if where cr.cliente=".$codigo." and cr.codigo not in (select codigo_conta from receber_pagamentos where cliente=".$codigo.") order by codigo desc  LIMIT 50 OFFSET " . ($pagina);
    $query_quantos = "select count(*) from contas_receber2 cr, indices_financeiros if where cr.cliente=".$codigo." and cr.codigo not in (select codigo_conta from receber_pagamentos where cliente=".$codigo.") ";
    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);
    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $quantos = $row_quantos[0];
        $key=0;
        $key = array_search($row['codigo'], array_column($_SESSION['cesta'], 'codigo'),true);
        $response[] = array(
            "codigo" => $row['codigo'],
            "cliente" => $row['cliente'],
            "venda" => $row['venda'],
            "data" => $row['data'],
            "credito" => $row['credito'],
            "debito" => $row['debito'],
            "juros" => $row['juros'],
            "total" => $row['total'],
            "historico" => $row['historico'],
            "vencimento" => $row['vencimento'],
            "quantos" => $quantos,
            "selecao" => $key
        );
    }
    echo json_encode($response);
    die();
}
//Adiciona / remove a conta selecionada na sessao conforme selecao/des selecao
if ($request == 'selecionarConta') {
    if(!isset($_SESSION['cesta'])){
        $_SESSION['cesta']=array();
    }
    $codigo= $_POST['codigo']; 
    $valor= padraoAmericano($_POST['valor']);
    $selecionado= $_POST['selecionado'];
    if($selecionado=='true'){
        //Adicionar na cesta 
        $novos_dados=array('codigo'=>$codigo,'valor'=>$valor);
        array_push($_SESSION['cesta'],$novos_dados);
    }else {
        //Remover da sessao
        $key = array_search($codigo, array_column($_SESSION['cesta'], 'codigo'));
        if($key>=0){
            unset($_SESSION['cesta'][$key]);
            $_SESSION['cesta']=array_values($_SESSION['cesta']);
        }
    }
    echo json_encode($response);
    die();
}
if ($request == 'pagarSelecionados') {
    $cliente=$_POST['cliente'];
    $quantos=count($_SESSION['cesta']);
    $query="";
    for($i=0;$i<$quantos;$i++){
         $query="insert into receber_pagamentos (data_pagamento,codigo_conta,cliente,valor,valor_pagamento) values(current_date,".$_SESSION['cesta'][$i]['codigo'].",".$cliente.",".$_SESSION['cesta'][$i]['valor'].",".$_SESSION['cesta'][$i]['valor'].")";
         $result = pg_query($conexao, $query);
    } 
    $_SESSION=array();
    session_destroy();
    echo json_encode($query);
    die();
}
if($request == 'pagarAvulso')
{
    $cliente=$_POST['cliente'];
    $valor=padraoAmericano($_POST['valor']);
    $query="select cr.debito as debito,cr.codigo as codigo,(((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100 ) as juros from contas_receber2 cr, indices_financeiros if where cr.cliente=".$cliente." and cr.codigo not in (select codigo_conta from receber_pagamentos) order by codigo asc ";
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $juros=$row['juros'];
        $debito=$row['debito'];
        $credito=$row['credito'];
        if($juros>0){
            $debitoConta=$debito+$juros;
        }else{
            $debitoConta=$debito;
        }
        $debitosConta=$debitosConta+$debitoConta;
        $codConta=$row['codigo'];
        if($valor>$debitosConta){
            $query2="insert into receber_pagamentos (data_pagamento,codigo_conta,cliente,valor,historico,valor_pagamento) values(current_date,".$codConta.",".$cliente.",".$debitoConta.",'pagamento avulso',".$valor.")";
            $result2 = pg_query($conexao, $query2);
            $query3="update contas_receber2 set credito=".$debitoConta." where cliente=".$cliente." and codigo=".$codConta;
            $result3 = pg_query($conexao, $query3);
        }else if($valor==$debitosConta){
            $query4="insert into receber_pagamentos (data_pagamento,codigo_conta,cliente,valor,historico,valor_pagamento) values(current_date,".$codConta.",".$cliente.",".$debitoConta.",'pagamento avulso',".$valor.")";
            $result4 = pg_query($conexao, $query4);
            $query5="update contas_receber2 set credito=".$debitoConta." where cliente=".$cliente." and codigo=".$codConta;
            $result5 = pg_query($conexao, $query5);
            break;
        }else if($valor<$debitosConta){
            $historico="PAGAMENTO parcial R$ ".valor;
            $query6="insert into receber_pagamentos (data_pagamento,codigo_conta,cliente,valor,historico,valor_pagamento) values(current_date,".$codConta.",".$cliente.",".$valor.",'pagamento avulso',".$valor.")";
            $result6=pg_query($conexao, $query6);
            $query7="update contas_receber2 set credito=".$debitoConta." where cliente=".$cliente." and codigo=".$codConta;
            $result7 = pg_query($conexao, $query7);
            $residual=$debitosConta-$valor;
            $query8="insert into contas_receber2 (cliente,data,credito,debito,historico,vencimento) values(".$cliente.",current_date,0,".$residual.",'pagamento avulso',current_date+30)";
            $result8 = pg_query($conexao, $query8);
            break;
        }
    }
    echo json_encode("OK");
    die();
}
if($request == 'montarSaldo')
{
    $cliente=$_POST['cliente'];
    $query= "select ".
    "sum(CASE WHEN (((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100 )>0 ".
       " THEN ".
       " (((((if.taxa_juros_perc_atraso)/30)*((current_date-cr.vencimento)-if.tolerancia_dias_atraso))*cr.debito)/100)+cr.debito ".
       " ELSE ".
           " cr.debito ".
        " END) as total ".
        " from contas_receber2 cr, indices_financeiros if where cr.cliente= ".$cliente."  and cr.codigo not in (select codigo_conta from receber_pagamentos where cliente= ".$cliente.") ";
    $result = pg_query($conexao, $query);
    $row = pg_fetch_assoc($result);
    echo json_encode(number_format($row['total'],2,',',''));
    die();
}
if($request == 'pegarValorSelecionado')
{
    $quantos=count($_SESSION['cesta']);
    $valor=0;
    for($i=0;$i<$quantos;$i++){
        $valor=$valor+$_SESSION['cesta'][$i]['valor'];
    }
    echo json_encode(number_format($valor,2,',',''));
    die();
}
function gravar($texto){
    $arquivo = "logReceber.txt";
    $fp = fopen($arquivo, "a+");
    fwrite($fp, $texto);
    fclose($fp);
}
?>