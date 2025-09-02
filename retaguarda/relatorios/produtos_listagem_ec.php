<?php
require('fpdf.php');
include('../conexao.php');
date_default_timezone_set('America/Sao_Paulo');
class PDF extends FPDF
{
    function Header()
    {
        $this->Image('../logo.png',2,2,20);
        $this->SetFont('Arial','B',15);
        $this->Cell(0,2,"Listagem de Produtos Ecomerce",0,0,'C');
        $this->SetFont('Times','',10);
        $this->Cell(0,12,date('d/m/Y H:i:s'),0,0,'R');
        $this->Line(5,20,205,20);
        $this->Ln(12);
    }
    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Times','',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        $this->SetFont('Times','I',8);
        $this->Cell(0,10,'GUTTY - gutty.com.br',0,0,'R');
    }
}
// Instanciation of inherited class
$pdf = new PDF();
$pdf->SetTitle("Listagem de Produtos");
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Helvetica','U',8);
$pdf->Cell(25,4,"Codigo");
$pdf->Cell(60,4,"Descricao");
$pdf->Cell(8,4,"Unid.");
$pdf->Cell(15,4,"R$ Venda");
$pdf->Cell(20,4,"R$ Compra");
$pdf->Cell(13,4,"NCM");
$pdf->Cell(2);
$pdf->Cell(10,4,"Qtde");
$pdf->Cell(20,4,"%ICMS/CFOP");
$pdf->Cell(15,4,"CST/PIS/COFINS");
$pdf->Ln(5);
$pdf->SetFont('Helvetica','',8);
$query="select p.codigo_gtin, rpad(p.descricao,37,' ') as descricao, pb.unidade, pb.preco_venda, pb.preco_compra, pb.codigo_ncm, po.qtde, pt.aliquota_icms||'/'||pb.cfop as icms_cfop, pt.situacao_tributaria||'/'||pt.cst_pis||'/'||pt.cst_cofins as cst_pis_cofins 
            from produtos p INNER JOIN produtos_ib pb ON p.codigo_interno=pb.codigo_interno 
            INNER JOIN produtos_ou po ON p.codigo_interno=po.codigo_interno 
            INNER JOIN produtos_tb pt ON p.codigo_interno=pt.codigo_interno where p.status='E' order by p.descricao ";
$result = pg_query($conexao, $query);
$total_compras=0;
$total_vendas=0;
$quantos=0;
while($row = pg_fetch_assoc($result)){
    $quantos=$quantos+1;
    $pdf->Cell(25,4,$row['codigo_gtin']);
    $pdf->Cell(60,4,$row['descricao']);
    $pdf->Cell(10,4,$row['unidade']);
    if($row['preco_venda']<=0)$pdf->SetTextColor(255,0,0);else $pdf->SetTextColor(0,0,0);
    $pdf->Cell(12,4,number_format($row['preco_venda'],2,',',''),0,0,'R');
    $pdf->SetTextColor(0,0,0);
    if($row['preco_compra']<=0)$pdf->SetTextColor(255,0,0);else $pdf->SetTextColor(0,0,0);
    $pdf->Cell(18,4,number_format($row['preco_compra'],2,',',''),0,0,'R');
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(2);
    $pdf->Cell(15,4,$row['codigo_ncm']);
    if($row['qtde']<=0)$pdf->SetTextColor(255,0,0);else $pdf->SetTextColor(0,0,0);
    $pdf->Cell(9,4,$row['qtde'],0,0,'R');
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(2);
    $pdf->Cell(20,4,$row['icms_cfop']);
    $pdf->Cell(2);
    $pdf->Cell(25,4,$row['cst_pis_cofins']);
    $pdf->Ln(4);
    if($row['qtde']>0){
        if($row['preco_compra']>0)
            $total_compras=$total_compras+($row['preco_compra']*$row['qtde']);
        if($row['preco_venda']>0)
            $total_vendas=$total_vendas+($row['preco_venda']*$row['qtde']);
    }
    $pdf->SetTextColor(0,0,0);
}
$pdf->Ln(4);
$pdf->Line(100,$pdf->GetY(),200,$pdf->GetY());
$pdf->Ln(4);
$pdf->SetX(100);
$pdf->Cell(45,4,"Total Compra:".number_format($total_compras,2,',',''));
$pdf->Cell(35,4,"Total Venda:".number_format($total_vendas,2,',',''));
$pdf->Cell(35,4,"em ".$quantos." itens");
$pdf->Output();
?>