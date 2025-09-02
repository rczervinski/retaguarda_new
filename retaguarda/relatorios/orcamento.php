<?php
require('fpdf.php');
include('../conexao.php');
include('../util.php');
date_default_timezone_set('America/Sao_Paulo');
class PDF extends FPDF
{

    function Header()
    {
        global $emissao;        
        $this->Image('../logo.png',2,2,20);
        $this->SetFont('Arial','B',15);
        $this->Cell(0,2,"Documento Auxiliar de Venda - DAV",0,0,'C');
        $this->Ln(5);
        $this->SetFont('Arial','B',10);
        $this->Cell(0,2,"Numero:".$_GET['codigo']." Emissao:".$emissao,0,0,'C');
        $this->Line(5,20,205,20);
        $this->Ln(6);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Times','',8);
        $this->Cell(30,10,date('d/m/Y H:i:s'));
        // Page number
        $this->SetFont('Times','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        $this->SetFont('Times','I',6);
        $this->Cell(0,10,"Sistema gutty.com.br",0,0,'R');
        
    }
}
// Instanciation of inherited class
$pdf = new PDF();

$codigo=$_GET['codigo'];
$pdf->SetTitle("Documento Auxiliar de Venda - DAV");
$pdf->AliasNbPages();

$query="select to_char(data,'DD/MM/YYYY') as data from dav_base where codigo=".$codigo;
$result = pg_query($conexao, $query);
$row= pg_fetch_assoc($result);
$emissao=$row['data'];

$pdf->AddPage();

$query="select UPPER(e.fantasia) as fantasia, UPPER(e.endereco) as endereco, UPPER(e.bairro) as bairro, e.municipio_desc, e.cep, e.telefone, e.numero,e.uf_desc,e.cnpj from estabelecimentos e ";
$result = pg_query($conexao, $query);
$row= pg_fetch_assoc($result);

$pdf->SetFont('Helvetica','',8);
$pdf->Cell(120,4,$row['fantasia']);
$pdf->Cell(40,4,"CNPJ:".$row['cnpj']);
$pdf->Cell(30,4,"Fone:".$row['telefone']);
$pdf->Ln(4);
$pdf->Cell(30,4,$row['endereco'].' '.$row['numero'].' - '.$row['bairro'].'  -  '.$row['municipio_desc'].' / '.$row['uf_desc']);
$pdf->Cell(0,4,'CEP:'.$row['cep'],0,0,'R');
$pdf->Line(5,30,205,30);
$pdf->Ln(5);
$query="select c.razao_social,c.cpf_cnpj,c.fone,c.fantasia,c.inscricao_rg,trim(c.logradouro||' '||c.numero||' '||c.complemento) as endereco,c.bairro,c.municipio_desc,c.uf_desc,c.cep,db.vendedor as codigo_vendedor,v.nome as vendedor from dav_base db inner join clientes c on db.cliente=c.codigo inner join vendedores v on db.vendedor=v.codigo where db.codigo=".$codigo;
$result = pg_query($conexao, $query);
$row= pg_fetch_assoc($result);
$pdf->Cell(100,4,"Cliente:".$row['razao_social']);
$pdf->Cell(30,4,"Telefone:".$row['fone']);
$pdf->Cell(0,4,"CNPJ:".$row['cpf_cnpj'],0,0,'R');
$pdf->Ln(4);
$pdf->Cell(100,4,"Fantasia:".$row['fantasia']);
$pdf->Cell(30,4,"IE:".$row['inscricao_rg']);
$pdf->Ln(4);
$pdf->Cell(110,4,"Endereco:".$row['endereco']);
$pdf->Cell(50,4,"Bairro:".$row['bairro']);
$pdf->Ln(4);
$pdf->Cell(110,4,"Cidade:".$row['municipio_desc'].' / '.$row['uf_desc']);
$pdf->Cell(50,4,"Cep:".$row['cep']);
$pdf->Ln(4);
$pdf->Cell(110,4,"Vendedor:".$row['codigo_vendedor'].' - '.$row['vendedor']);
$pdf->Ln(4);
$pdf->Line(5,50,205,50);
$pdf->SetFont('Helvetica','B',8);
$pdf->Cell(8,4,"ITEM");
$pdf->Cell(30,4,"COD");
$pdf->Cell(20,4,"QTDE");
$pdf->Cell(10,4,"UNID");
$pdf->Cell(80,4,"DESCRICAO");
$pdf->Cell(23,4,"VAL UNIT.",0,0,'R');
$pdf->Cell(23,4,"TOTAL",0,0,'R');
$pdf->Line(5,55,205,55);
$pdf->Ln(5);
$query="select pd.codigo_gtin, CASE WHEN char_length(pd.complemento)>0 THEN rpad(p.descricao||pd.complemento,50,' ') ELSE rpad(p.descricao,50,' ') END as descricao, pb.unidade, pd.qtde, pd.preco_venda, pd.qtde*pd.preco_venda as total from dav_prod pd INNER JOIN produtos p ON p.codigo_gtin=pd.codigo_gtin INNER JOIN produtos_ib pb ON pb.codigo_interno=p.codigo_interno where pd.dav=".$codigo." order by pd.codigo";
$result = pg_query($conexao, $query);
$item=0;
$pdf->SetFont('Helvetica','',8);
while ($row = pg_fetch_assoc($result)) {
    $item++;
    $pdf->Cell(8,4,$item);
    $pdf->Cell(30,4,$row['codigo_gtin']);
    $pdf->Cell(20,4,$row['qtde']);
    $pdf->Cell(10,4,$row['unidade']);
    $pdf->Cell(80,4,$row['descricao']);
    $pdf->Cell(23,4,$row['preco_venda'],0,0,'R');
    $pdf->Cell(23,4,$row['total'],0,0,'R');
    $pdf->Ln(4);
}
$pdf->Line(5,$pdf->getY(),205,$pdf->getY());
$pdf->Ln(4);
$query="select sum(dp.qtde*dp.preco_venda) as subtotal,db1.desconto,sum(dp.qtde*dp.preco_venda) - db1.desconto as total from dav_base db1 inner join dav_prod dp on db1.codigo=dp.dav where db1.codigo=".$codigo." group by db1.desconto";
$result = pg_query($conexao, $query);
$row = pg_fetch_assoc($result);
$pdf->Cell(150);
$pdf->Cell(22,4,"SUBTOTAL");
$pdf->Cell(22,4,$row['subtotal'],0,0,'R');
$pdf->Ln(4);
$pdf->Cell(150);
$pdf->Cell(22,4,"DESCONTO");
$pdf->Cell(22,4,$row['desconto'],0,0,'R');
$pdf->Ln(4);
$pdf->Cell(150);
$pdf->SetFont('Helvetica','B',8);
$pdf->Cell(22,4,"TOTAL");
$pdf->Cell(22,4,$row['total'],0,0,'R');
$pdf->SetFont('Helvetica','',8);
$pdf->Ln(4);
$pdf->Line(5,$pdf->getY(),205,$pdf->getY());
$pdf->Ln(4);
$query="select obs from dav_base where codigo=".$codigo;
$result = pg_query($conexao, $query);
$row = pg_fetch_assoc($result);

$pdf->MultiCell(0,4,$row['obs'],0,'J');
$pdf->Ln(4);
$pdf->Ln(4);
$pdf->Ln(4);
$pdf->Cell(0,0,"__________________________________________________",0,0,'C');
$pdf->Ln(4);
$pdf->Cell(0,0,"Assinatura do Cliente",0,0,'C');
$pdf->Output();
?>