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
        
        $this->Cell(0,2,"RECIBO",0,0,'C');
        
        
        $this->Ln(20);
    }
}

$pdf = new PDF();
$pdf->SetTitle("Recibo");
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Helvetica','',10);
$pdf->Ln(20);
$pdf->Cell(10);
$pdf->MultiCell(170,4,"Recebemos de ".$_GET['nome']." a quantia  de R$".$_GET['valor']."(".valorPorExtenso($_GET['valor'],true,false).")");
$pdf->Ln(20);
$query="select semacento(municipio_desc) as municipio,to_char(current_date,'dd/mm/yyyy') as data,razao_social from estabelecimentos";
$result=pg_query($conexao, $query);
$row=pg_fetch_assoc($result);
$pdf->Cell(200,24,$row['razao_social'],0,0,'C');
$pdf->Ln(20);
$pdf->Cell(170,24,$row['municipio'].",".date('d/m/Y'),0,0,'R'); 
$pdf->Output();

?>