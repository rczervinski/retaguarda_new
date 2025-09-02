<?php
require('fpdf.php');
include('../conexao.php');
include('../util.php');
date_default_timezone_set('America/Sao_Paulo');
class PDF extends FPDF
{
    function Header()
    {
        $this->Image('../logo.png',2,2,20);
        $this->SetFont('Arial','B',15);
        $this->Cell(0,2,"Contas Recebidas",0,0,'C');
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
$pdf->SetTitle("Contas Recebidas");
$pdf->AliasNbPages();
$pdf->AddPage();
$query="SELECT to_char(rp.data_pagamento,'DD/MM/YYYY') as pgto,c.fantasia,cr.debito,cr.codigo AS cr_codigo,to_char(cr.vencimento,'DD/MM/YYYY') as vencimento, rp.valor_pagamento,rp.historico FROM contas_receber2 cr INNER JOIN clientes c ON cr.cliente = c.codigo
     INNER JOIN receber_pagamentos rp ON cr.codigo = rp.codigo_conta
     WHERE c.codigo=".$_GET['cliente'];
$result = pg_query($conexao, $query);
$total=0;
while($row = pg_fetch_assoc($result)){
    if($total==0){
        $pdf->SetFont('Helvetica','U',8);
        $pdf->Cell(25,4,"Cliente: ");
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(150,4,$row['fantasia']);
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica','U',8);
        $pdf->Cell(25,4,"Data Pgto");
        $pdf->Cell(60,4,"Vencimento");
        $pdf->Cell(8,4,"Valor");
        $pdf->Cell(20);
        $pdf->Cell(20,4,"Valor Informado");
        $pdf->Cell(5);
        $pdf->Cell(5,4,"Historico");
        $pdf->SetFont('Helvetica','',8);
    }
    $pdf->Ln(4);
    $pdf->Cell(25,4,$row['pgto']);
    $pdf->Cell(60,4,$row['vencimento']);
    $pdf->Cell(8,4,padraoBrasileiro($row['debito']));
    $pdf->Cell(20);
    $pdf->Cell(20,4,padraoBrasileiro($row['valor_pagamento']));
    $pdf->Cell(5);
    $pdf->Cell(5,4,$row['historico']);
    $total=$total+$row['debito'];
}
$pdf->Ln(10);
$pdf->Line(90,$pdf->GetY(),180,$pdf->GetY());
$pdf->Ln(4);
$pdf->SetX(100);
$pdf->Cell(40,4,"Total Pago:  ".number_format($total,2,',',''));
$pdf->Output();
?>