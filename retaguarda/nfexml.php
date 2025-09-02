<?php
session_start();
include "conexao.php";
require_once "vendor/autoload.php";
require_once "config.php";
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\CertificateException;
use NFePHP\Common\Soap\SoapCurl;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Common\Keys;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\DA\NFe\Danfe;
$request = "";
if (isset($_POST['request'])) {
    $request = $_POST['request'];
}
function gravar($texto){
    $arquivo = "logNFE.txt";
    $fp = fopen($arquivo, "a+");
    fwrite($fp, $texto);
    fclose($fp);
}
if ($request == 'validade') {
    $senha = "1234";
    $configJson = json_encode($arr);
    $content = file_get_contents('certificado.pfx');
    $cert = Certificate::readPfx($content, $senha);
    $validFrom = $cert->getValidTo();
    echo json_encode($validFrom->format('d/m/Y'));
    die();
}
if ($request == 'gerarXML') {
    try{
        $nfe = new Make();
        unlink('danfe.pdf');
        $std = new stdClass();
        $std->versao = '4.00';
        $nfe->taginfNFe($std);
        $finalidade = $_POST['finalidade'];
        $documento = $_POST['documento'];
        $serie = $_POST['serie'];
        $query = "select c.descricao as natureza,nb.codigo_interno,nb.documento,nb.serie,e.uf,nb.emissao,nb.saida,nb.hora,nb.tipo, cast(substring(cast(nb.natureza as text),1,1) as numeric)-4 as iddest, e.municipio  from estabelecimentos e,cfops c ,nf_base nb where nb.documento=" . $documento . " and serie=" . $serie . " and CAST(c.codigo as integer)=nb.natureza";
        $result = pg_query($conexao, $query);
        while ($row = pg_fetch_assoc($result)) {
            $std = new stdClass();
            $std->cUF = $row['uf'];
            $std->cNF = $row['codigo_interno'];
            $natureza = explode(" ", $row['natureza']);
            $std->natOp = $natureza[0];
            // $std->indPag = 0;
            $std->mod = 55;
            $std->serie = $row['serie'];
            $std->nNF = $row['documento'];
            $emissao = $row['emissao'] . 'T' . $row['hora'] . '-03:00';
            $saida = $row['saida'] . 'T' . $row['hora'] . '-03:00';
            $std->dhEmi = $emissao;
            $std->dhSaiEnt = $saida;
            $std->tpNF = $row['tipo'];
            $std->idDest = $row['iddest'];
            $std->cMunFG = $row['municipio'];
            $std->tpImp = 1; // Impressao em retrato
            $std->tpEmis = 1; // Normal , sem contingencia
            $std->tpAmb = 1; // Se deixar o tpAmb como 2 voc� emitir� a nota em ambiente de homologa��o(teste) e as notas fiscais aqui n�o tem valor fiscal
            $std->finNFe = $finalidade;
            $std->indFinal = 0;
            $std->indPres = 0;
            $std->procEmi = '0';
            $std->verProc = 1;
            $nfe->tagide($std);
        }
        $query = "select e.razao_social,e.ie,c.regime_tributario,e.cnpj,e.endereco,e.numero,e.bairro,e.municipio,e.municipio_desc,e.uf_desc,e.cep from estabelecimentos e , configuracao c";
        $result = pg_query($conexao, $query);
        while ($row = pg_fetch_assoc($result)) {
            $std = new stdClass();
            $std->xNome = $row['razao_social'];
            $std->IE = $row['ie'];
            $std->CRT = $row['regime_tributario'];
            $std->CNPJ = $row['cnpj'];
            $nfe->tagemit($std);
            $std = new stdClass();
            $std->xLgr = $row['endereco'];
            $std->nro = $row['numero'];
            $std->xBairro = $row['bairro'];
            $std->cMun = $row['municipio'];
            $std->xMun = $row['municipio_desc'];
            $std->UF = $row['uf_desc'];
            $std->CEP = $row['cep'];
            $std->cPais = '1058';
            $std->xPais = 'BRASIL';
            $nfe->tagenderEmit($std);
        }
        // Dest
        $query = "select c.razao_social,c.inscricao_rg,c.cpf_cnpj,c.logradouro,c.numero,c.bairro,c.municipio,c.municipio_desc,c.uf_desc,c.cep from nf_base nb inner join clientes c on nb.cliente=c.codigo where nb.documento=" . $documento . " and nb.serie=" . $serie;
        $result = pg_query($conexao, $query);
        while ($row2 = pg_fetch_assoc($result)) {
            $std = new stdClass();
            $std->xNome = $row2['razao_social'];
            if ($row2['inscricao_rg'] == 'ISENTO' || strlen($row2['inscricao_rg']) < 2) {
                $std->indIEDest = 2;
            } else {
                $std->indIEDest = 1;
            }
            $std->IE = $row2['inscricao_rg'];
            if (strlen($row2['cpf_cnpj']) == 11) {
                $std->CPF = $row2['cpf_cnpj'];
            } else {
                $std->CNPJ = $row2['cpf_cnpj'];
            }
            $nfe->tagdest($std);
            $std = new stdClass();
            $std->xLgr = $row2['logradouro'];
            $std->nro = $row2['numero'];
            $std->xBairro = $row2['bairro'];
            $std->cMun = $row2['municipio'];
            $std->xMun = $row2['municipio_desc'];
            $std->UF = $row2['uf_desc'];
            $std->CEP = $row2['cep'];
            $std->cPais = '1058';
            $std->xPais = 'BRASIL';
            $nfe->tagenderDest($std);
        }
        $item = 0;
        $query = "select np.codigo_gtin, np.descricao,np.codigo_ncm,np.cfop,np.unidade,np.quantidade,np.preco_unitario,np.total,np.cst_cson, " . "np.bc_icms,np.aliquota_icms,np.val_icms,np.val_ipi,np.aliquota_ipi,pt.cst_ipi,pt.aliquita_pis,pt.cst_pis, pb.preco_venda-pb.preco_compra as lucro, " . " pt.aliquota_cofins, pt.cst_cofins" . " from nf_prod np" . " inner join produtos p on np.codigo_gtin=p.codigo_gtin inner join produtos_tb pt on p.codigo_interno=pt.codigo_interno inner join produtos_ib pb on p.codigo_interno=pb.codigo_interno " . " where np.nf_numero=" . $documento . " and np.nf_serie=" . $serie;
        $result = pg_query($conexao, $query);
        gravar($query);
        $row_quantos = pg_num_rows($result);
        while ($row = pg_fetch_assoc($result)) {
            $item ++;
            // det item
            $std = new stdClass();
            $std->item = $item;
            $std->cProd = $row['codigo_gtin'];
            $std->xProd = $row['descricao'];
            $std->cEAN = 'SEM GTIN';
            $std->cEANTrib = 'SEM GTIN';
            $std->NCM = $row['codigo_ncm'];
            $std->CFOP = $row['cfop'];
            $std->uCom = $row['unidade'];
            $std->qCom = $row['quantidade'];
            $std->vUnCom = $row['preco_unitario'];
            $queryImposto = "select vlfrete/" . $row_quantos . " as vlfrete,vlseguro/" . $row_quantos . " as vlseguro,vldespesas/" . $row_quantos . " as vldespesas,vldesconto/" . $row_quantos . " as vldesconto from nf_imposto where documento=" . $documento . " and serie =" . $serie;
            $result_imposto = pg_query($conexao, $queryImposto);
            while ($row_imposto = pg_fetch_assoc($result_imposto)) {
                if ($row_imposto['vlfrete'] > 0)
                    $std->vFrete = $row_imposto['vlfrete'];
                    if ($row_imposto['vlseguro'] > 0)
                        $std->vSeg = $row_imposto['vlseguro'];
                        if ($row_imposto['vldesconto'] > 0)
                            $std->vDesc = $row_imposto['vldesconto'];
                            if ($row_imposto['vldespesas'] > 0)
                                $std->vOutro = $row_imposto['vldespesas'];
            }
            $std->vProd = $row['total'];
            $std->uTrib = $row['unidade'];
            $std->qTrib = $row['quantidade'];
            $std->vUnTrib = $row['preco_unitario'];
            $std->indTot = 1;
            $nfe->tagprod($std);
            $std = new stdClass();
            $std->item = $item;
            $nfe->tagimposto($std);
            // icms
            $std = new stdClass();
            $std->item = $item;
            $std->orig = 0;
            $std->CSOSN = $row['cst_cson'];
            $std->modBC = 0;
            $std->vBC = $row['bc_icms'];
            $std->pICMS = $row['aliquota_icms'];
            $std->vICMS = $row['val_icms'];
            $nfe->tagICMSSN($std);
            if ($row['cst_ipi'] > 0) {
                $std = new stdClass();
                $std->item = $item;
                $std->cEnq = '999';
                $std->CST = $row['cst_ipi'];
                $std->vIPI = $row['val_ipi'];
                $std->vBC = $row['bc_icms'];
                $std->pIPI = $row['aliquota_ipi'];
                $nfe->tagIPI($std);
            }
            if ($row['cst_pis'] ==1 || $row['cst_pis'] ==2) {
                $std = new stdClass();
                $std->item = $item;
                $std->CST = str_pad($row['cst_pis'] , 2 , '0' , STR_PAD_LEFT);
                $std->vBC = $row['lucro'];
                $std->pPIS = $row['aliquita_pis'];
                $std->vPIS = ($row['aliquita_pis'] / 100) * $row['lucro'];
                $nfe->tagPIS($std);
            }else{
                $std = new stdClass();
                $std->item = $item;
                $std->CST = str_pad($row['cst_pis'] , 2 , '0' , STR_PAD_LEFT);
                $std->vBC = "0";
                $std->pPIS = $row['aliquita_pis'];
                $std->vPIS = "0";
                $nfe->tagPIS($std);
            }
            if ($row['cst_cofins'] ==1 || $row['cst_cofins'] ==2) {
                $std = new stdClass();
                $std->item = $item;
                $std->CST = str_pad($row['cst_cofins'] , 2 , '0' , STR_PAD_LEFT);
                $std->vCOFINS = ($row['aliquota_cofins'] / 100) * $row['lucro'];
                $std->vBC = $row['lucro'];
                $std->pCOFINS = $row['aliquota_cofins'];
                $nfe->tagCOFINS($std);
            }else{
                $std = new stdClass();
                $std->item = $item;
                $std->CST = str_pad($row['cst_cofins'] , 2 , '0' , STR_PAD_LEFT);
                $std->vCOFINS = "0";
                $std->vBC = "0";
                $std->pCOFINS = $row['aliquota_cofins'];
                $nfe->tagCOFINS($std);
            }
        }
        $queryInf = "select dados_adicionais, dados_adicionais2 from nf_base where documento=" . $documento . " and serie=" . $serie;
        $result = pg_query($conexao, $queryInf);
        if ($row = pg_fetch_assoc($result)) {
            if(strlen($row['dados_adicionais2'])>1){
                $std = new stdClass();
                $std->infAdFisco = $row['dados_adicionais'];
                $std->infCpl = $row['dados_adicionais2'];
                $nfe->taginfAdic($std);
            }
        }
       // TRANSPORTADORA
        $queryTrans = "select * from nf_tra where nf_numero=" . $documento . " and nf_serie=" . $serie;
        $result = pg_query($conexao, $queryTrans);
        if ($row = pg_fetch_assoc($result)) {
            $std = new stdClass();
            $std->modFrete = $row['por_conta'];
            $nfe->tagtransp($std);
            $queryTrans2 = "select * from transportadoras where codigo=" . $row['cod_transportadora'];
            $resulttra = pg_query($conexao, $queryTrans2);
            while ($row2 = pg_fetch_assoc($resulttra)) {
                $std = new stdClass();
                $std->xNome = $row2['razao_social'];
                $std->IE = $row2['inscricao_rg'];
                $std->xEnder = $row2['logradouro'];
                $std->xMun = $row2['municipio_desc'];
                $std->UF = $row2['uf_desc'];
                if (strlen($row2['cpf_cnpj']) > 11) {
                    $std->CNPJ = $row2['cpf_cnpj'];
                    $std->CPF = null;
                } else {
                    $std->CNPJ = null;
                    $std->CPF = $row2['cpf_cnpj'];
                }
                $nfe->tagtransporta($std);
            }
            $std = new stdClass();
            $std->placa = $row['placa'];
            $std->UF = $row['placa_uf'];
            $std->RNTC = $row['cod_antt'];
            $nfe->tagveicTransp($std);
            $std = new stdClass();
            $std->item = 1; // indicativo do numero do volume
            $std->qVol = $row['quantidade'];
            $std->esp = $row['especie'];
            $std->marca = $row['marca'];
            $std->nVol = $row['numeracao'];
            $std->pesoL = $row['peso_liquido'];
            $std->pesoB = $row['peso_bruto'];
            $nfe->tagvol($std);
        } else {
            $std = new stdClass();
            $std->modFrete = 9; // 9-sem ocorrencia de transporte
            $nfe->tagtransp($std);
        }
        // Doc Referencia
        $queryDocRef = "select * from nf_docref where numero=" . $documento . " and serie=" . $serie;
        $result = pg_query($conexao, $queryDocRef);
        if ($row = pg_fetch_assoc($result)) {
            $std = new stdClass();
            $std->refNFe = $row['chave'];
            $nfe->tagrefNFe($std);
        }
        $query = "select vltotal from nf_imposto where documento=" . $documento . " and serie=" . $serie;
        $result = pg_query($conexao, $query);
        $vltotal = 0;
        while ($row = pg_fetch_assoc($result)) {
            if ($finalidade == 4) {
                $std = new stdClass();
                $std->vTroco = $row['vltotal'];
                $nfe->tagpag($std);
                $std = new stdClass();
                $std->indPag = "0";
                $std->tPag = "90";
                $std->vPag = 0;
                $nfe->tagdetPag($std);
                $vltotal = 0;
            } else {
                $std = new stdClass();
                $std->vTroco = "0";
                $nfe->tagpag($std);
                $std = new stdClass();
                $std->indPag = "0";
                $std->tPag = "01";
                $std->vPag = $row['vltotal'];
                $nfe->tagdetPag($std);
                $vltotal = $row['vltotal'];
            }
        }
        // DUPLICATA
        $query = "select * from nf_fat where numero_nf=" . $documento . " and serie_nf=" . $serie;
        $result = pg_query($conexao, $query);
		$y=0;
        while ($row = pg_fetch_assoc($result)) {
           // FATURA
	    	if($y==0){
            	$std = new stdClass();
            	$std->nFat = $documento;
	            $std->vOrig = $vltotal;
    	        $std->vLiq = $vltotal;
        	    $nfe->tagfat($std);
			}
            $std = new stdClass();
            $std->nDup = $row['numero'];
            $std->dVenc = $row['vencimento'];
            $std->vDup = $row['valor'];
            $nfe->tagdup($std);
			$y=$y+1;
        }
        gravar("teste3 \n");
        // INF RESP TECNICO
        $std = new stdClass();
        $std->CNPJ = '01414955000158';
        $std->xContato = 'JANO CREMA';
        $std->email = 'jano@gutty.com.br';
        $std->fone = '41995075567';
        $nfe->taginfRespTec($std);
        // GERA O XML
        $xml = $nfe->monta();
        gravar($xml);
        // ASSINA O XML
        $configJson = json_encode($arr);
        $content = file_get_contents('certificado.pfx');
        $tools = new Tools($configJson, Certificate::readPfx($content, '1234'));
        $tools->model('55');
        $response = $tools->signNFe($xml);
        header('Content-type: text/xml; charset=UTF-8');
        $chave = $nfe->getChave();
        $query = "update nf_base set chave='" . $chave . "',xml='" . $response . "' where documento=" . $documento . " and serie=" . $serie;
        $result = pg_query($conexao, $query);
        // ENVIA O XML RECEITA
        try {
            $tools->model('55');
            $idLote = str_pad(100, 15, '0', STR_PAD_LEFT);
            $resp = $tools->sefazEnviaLote([
                $response
            ], $idLote);
            $st = new NFePHP\NFe\Common\Standardize();
            $std = $st->toStd($resp);
            if ($std->cStat != 103) {
                $erro = "Nota nao autorizada pelo motivo:" . $std->xMotivo;
                $query = "update nf_base set motivo='" . $erro . "' where documento=" . $documento . " and serie=" . $serie;
                $result = pg_query($conexao, $query);
                echo json_encode($erro);
                die();
            }
            $recibo = $std->infRec->nRec;
            $protocolo = $tools->sefazConsultaRecibo($recibo);
            $std = $st->toStd($protocolo);
            if ($std->protNFe->infProt->cStat != 100 && $std->protNFe->infProt->cStat != 150) {
                $motivo = $std->protNFe->infProt->cStat . " - " . $std->protNFe->infProt->xMotivo;
                $query = "update nf_base set motivo='" . $motivo . "' where documento=" . $documento . " and serie=" . $serie;
                $result = pg_query($conexao, $query);
                echo json_encode($motivo);
                die();
            } else {
                $recebimento = $std->protNFe->infProt->dhRecbto;
                $data_rec = substr($recebimento, 0, 10);
                $hora_rec = substr($recebimento, 11, 8);
                $query = "update nf_base set status=2, protocolo='" . $std->protNFe->infProt->nProt . "', data_protocolo='" . $data_rec . "', hora_protocolo='" . $hora_rec . "', recibo='" . $recibo . "' where documento=" . $documento . " and serie=" . $serie;
                $result = pg_query($conexao, $query);
            }
            $xmlautorizado = Complements::toAuthorize($response, $protocolo);
            $query = "update nf_base set xml='" . $xmlautorizado . "' where documento=" . $documento . " and serie=" . $serie;
            $result = pg_query($conexao, $query);
            $danfe = new Danfe($xmlautorizado);
            $danfe->exibirTextoFatura = false;
            $danfe->exibirPIS = false;
            $danfe->exibirIcmsInterestadual = false;
            $danfe->exibirValorTributos = false;
            $danfe->descProdInfoComplemento = false;
            $danfe->exibirNumeroItemPedido = false;
            $danfe->setOcultarUnidadeTributavel(true);
            $danfe->obsContShow(false);
            $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents('logo.jpg'));
            $danfe->logoParameters($logo, $logoAlign = 'C', $mode_bw = false);
            $danfe->setDefaultFont($font = 'times');
            $danfe->setDefaultDecimalPlaces(4);
            $danfe->debugMode(false);
            $danfe->creditsIntegratorFooter('GUTTY - http://www.gutty.com.br');
            $pdf = $danfe->render();
            header('Content-Type: application/pdf');
            $file_exists = file_put_contents('danfe.pdf', $pdf);
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
            die();
        }
        // echo json_encode($xmlautorizado);
        echo json_encode("OK");
        die();
    }catch (Exception $e) {
        echo json_encode($e->getMessage());
        die();
    }
}
if($request=='arrumarDuplicidade'){
    $documento=$_POST['documento'];
    $configJson = json_encode($arr);
    $content = file_get_contents('certificado.pfx');
    $tools = new Tools($configJson, Certificate::readPfx($content, '1234'));
    $tools->model('55');
    $query="select recibo,xml from nf_base where documento=".$documento;
    $result = pg_query($conexao, $query);
    $recibo=0;
    $xml="";
    while ($row = pg_fetch_assoc($result)) {
        $recibo=$row['recibo'];
        $xml=$row['xml'];
    }
    $protocolo = $tools->sefazConsultaRecibo($recibo);
    $xmlautorizado = Complements::toAuthorize($xml, $protocolo);
    $query="update nf_base set xml='".$xmlautorizado."' where documento=".$documento;
    $result = pg_query($conexao, $query);
    echo json_encode("OK");
    die();
}
if($request=='pegarProtocoloViaRecibo'){
    $recibo=$_POST['recibo'];
    $configJson = json_encode($arr);
    $content = file_get_contents('certificado.pfx');
    $tools = new Tools($configJson, Certificate::readPfx($content, '1234'));
    $tools->model('55');
    $protocolo = $tools->sefazConsultaRecibo($recibo);
    echo json_encode($protocolo);
    die();
}
if ($request == 'cancelarNfe') {
    try {
        $codigo_interno = $_POST['codigo_interno'];
        $senha = "1234";
        $configJson = json_encode($arr);
        $content = file_get_contents('certificado.pfx');
        $certificate = Certificate::readPfx($content, $senha);
        $tools = new Tools($configJson, $certificate);
        $tools->model('55');
        $query = "select  documento,serie,chave,protocolo from nf_base where codigo_interno=" . $codigo_interno;
        $result = pg_query($conexao, $query);
        while ($row = pg_fetch_assoc($result)) {
            $chave = $row['chave'];
            $xJust = 'Erro de digitacao nos dados da nfe';
            $nProt = $row['protocolo'];
            $resp = $tools->sefazCancela($chave, $xJust, $nProt);
            $st = new NFePHP\NFe\Common\Standardize();
            $std = $st->toStd($resp);
            if ($std->cStat != 128) {
                echo json_encode("Evento nao foi processado");
                die();
            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                    $xml = Complements::toAuthorize($tools->lastRequest, $resp);
                    $query2 = "insert into eventos_nfe(numero,serie,data_hora,evento,xml,chave) values(" . $row['documento'] . "," . $row['serie'] . ",current_date,'cancelamento','" . $xml . "','".$row['chave']."')";
                    $result = pg_query($conexao, $query2);
                    $query3 = "update nf_base set status=3 where documento=" . $row['documento'] . " and serie=" . $row['serie'];
                    $result = pg_query($conexao, $query3);
                } else {
                    echo json_encode("Evento nao foi processado");
                    die();
                }
            }
        }
    } catch (Exception $e) {
        echo json_encode($e->getMessage());
        die();
    }
    echo json_encode("OK");
    die();
}
if ($request == 'gerarDanfe') {
    unlink('danfe.pdf');
    $codigo_interno = $_POST['codigo_interno'];
    $query = "select xml from nf_base where codigo_interno=" . $codigo_interno;
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        $danfe = new Danfe($row['xml']);
        $danfe->exibirTextoFatura = false;
        $danfe->exibirPIS = false;
        $danfe->exibirIcmsInterestadual = false;
        $danfe->exibirValorTributos = false;
        $danfe->descProdInfoComplemento = false;
        $danfe->exibirNumeroItemPedido = false;
        $danfe->setOcultarUnidadeTributavel(true);
        $danfe->obsContShow(false);
        $danfe->printParameters($orientacao = 'P', $papel = 'A4', $margSup = 2, $margEsq = 2);
        $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents('logo.jpg'));
        $danfe->logoParameters($logo, $logoAlign = 'C', $mode_bw = false);
        $danfe->setDefaultFont($font = 'times');
        $danfe->setDefaultDecimalPlaces(4);
        $danfe->debugMode(false);
        $danfe->creditsIntegratorFooter('GUTTY - http://www.gutty.com.br');
        $pdf = $danfe->render();
        header('Content-Type: application/pdf');
        $file_exists = file_put_contents('danfe.pdf', $pdf);
    }
    echo json_encode("OK");
    die();
}
if ($request == 'efetuarCorrecao') {
    $codigo_interno= $_POST['codigo_interno'];
    $correcao= $_POST['correcao'];
    //Fazer a correcao da NFE
    try {
        $senha = "1234";
        $configJson = json_encode($arr);
        $content = file_get_contents('certificado.pfx');
        $cert = Certificate::readPfx($content, $senha);
        $tools = new Tools($configJson, $cert);
        $tools->model('55');
        $query="select documento,serie,chave from nf_base where codigo_interno=".$codigo_interno;
        $result = pg_query($conexao, $query);
        if($row = pg_fetch_assoc($result)) {
            $chave = $row['chave'];
            $documento=$row['documento'];
            $serie=$row['serie'];
            $xCorrecao =$correcao;
            $nrEvento=1;
            $queryNrEvento="select count(*) as contagem from eventos_nfe where numero=".$documento." and serie=".$serie;
            $resultNrEvento = pg_query($conexao, $queryNrEvento);
            if($row2 = pg_fetch_assoc($resultNrEvento)) {
                $nrEvento=$nrEvento+$row2['contagem'];
            }
            $nSeqEvento = $nrEvento;
            $response = $tools->sefazCCe($chave, $xCorrecao, $nSeqEvento);
            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();
            //verifique se o evento foi processado
            if ($std->cStat != 128) {
                echo json_encode("Evento nao processado");
                die();
            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '135' || $cStat == '136') {
                    $xml = Complements::toAuthorize($tools->lastRequest, $response);
                    $query="insert into eventos_nfe (numero,serie,data_hora,evento,xml,chave) values(".$documento.",".$serie.",current_date,'CARTA CORRECAO','".$xml."','".$chave."')";
                    $result2 = pg_query($conexao, $query);
                } else {
                    echo json_encode("Evento nao processado");
                    die();
                }
            }
        }
    } catch (\Exception $e) {
        echo json_encode($e->getMessage());
        die();
    }
    echo json_encode("OK");
    die();
}
function impostoIbpt($valor, $ncm, $local)
{
    if($local==1){
        $query="select imposto_federal as imposto from ibpt where ncm='".$ncm."'";
    }else{
        $query="select imposto_pr as imposto from ibpt where ncm='".$ncm."'";
    }
    $result = pg_query($conexao, $query);
    while ($row = pg_fetch_assoc($result)) {
        return ($row['imposto']/100)*$valor;
    }   
    if($local==1){
        return (4.2/100)*$valor;
    }else{
        return (7.2/100)*$valor;
    }
}
?>
