<?php

require('./fpdf.php');

class PDF extends FPDF
{

   function Header()
   {

      $this->Image('Logo.png', 5, 5, 25);
      $this->SetFont('Arial', 'B', 20);
      $this->Cell(50);
      $this->SetTextColor(76,175,80);
      $this->Cell(100, 15, utf8_decode('Verdutech'), 1, 1, 'C', 0);
      $this->Ln(10);
      $this->SetTextColor(100);

      $this->Cell(1);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(50, 10, utf8_decode("Dirección : Olga orozco 3094"), 0, 0, 'L', 0);
      $this->Ln(5);

      $this->Cell(1);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(50, 10, utf8_decode("Teléfono : 3514596496"), 0, 0, 'L', 0);
      $this->Ln(5);

      $this->Cell(1);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(50, 10, utf8_decode("Correo : lamendocina83@gmail.com"), 0, 0, 'L', 0);
      $this->Ln(10);

      $this->SetTextColor(76,175,80);
      $this->Cell(50);
      $this->SetFont('Arial', 'B', 15);
      $this->Cell(100, 10, utf8_decode("REPORTE DE ESTADISTICAS"), 0, 1, 'C', 0);
      $this->Ln(10);

   }

   function Footer()
   {
      $this->SetY(-15);
      $this->SetFont('Arial', 'I', 8);
      $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
      
      $this->SetY(-15);
      $this->SetFont('Arial', 'I', 8);
      $hoy = date('d/m/Y');
      $this->Cell(355, 10, utf8_decode($hoy), 0, 0, 'C');
   }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->AliasNbPages();



$pdf->Output('ReporteEstadisticas.php', 'I');
?>