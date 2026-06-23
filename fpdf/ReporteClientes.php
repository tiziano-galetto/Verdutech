<?php

require('./fpdf.php');

class PDF extends FPDF
{

   function Header()
   {
      global $conn; 

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
      $this->Cell(100, 10, utf8_decode("REPORTE DE CLIENTES"), 0, 1, 'C', 0);
      $this->Ln(10);

      $this->SetX(5);
      $this->SetFillColor(76,175,80);
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(163, 163, 163);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(10, 10, utf8_decode('ID'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('Nombre'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('Apellido'), 1, 0, 'C', 1);
      $this->Cell(50, 10, utf8_decode('Correo'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Teléfono'), 1, 0, 'C', 1);
      $this->Cell(50, 10, utf8_decode('Dirección'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Deuda'), 1, 1, 'C', 1);
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

include '../funcion.php';
$conn = conexion();

$pdf = new PDF();
$pdf->AddPage();
$pdf->AliasNbPages();

$i = 0;
$pdf->SetFont('Arial', '', 10);
$pdf->SetDrawColor(163, 163, 163);

$consulta_reporte_clientes = $conn->query(" select * from clientes ");

while ($datos_reporte = $consulta_reporte_clientes->fetch_object()) {      
   $i = $i + 1;
   $pdf->SetX(5);
   $Deuda_formateada = number_format(
      floatval($datos_reporte->deuda),
      2,
      ',',
      '.'
   );
   
   $Deuda_con_simbolo = '$ ' . $Deuda_formateada;

   $pdf->Cell(10, 10, utf8_decode($i), 1, 0, 'C', 0);
   $pdf->Cell(20, 10, utf8_decode($datos_reporte->nombre), 1, 0, 'C', 0);
   $pdf->Cell(20, 10, utf8_decode($datos_reporte->apellido), 1, 0, 'C', 0);
   $pdf->Cell(50, 10, utf8_decode($datos_reporte->email), 1, 0, 'C', 0);
   $pdf->Cell(25, 10, utf8_decode($datos_reporte->telefono), 1, 0, 'C', 0);
   $pdf->Cell(50, 10, utf8_decode($datos_reporte->direccion), 1, 0, 'C', 0);
   $pdf->Cell(25, 10, $Deuda_con_simbolo, 1, 1, 'C', 0);
}

$pdf->Output('ReporteClientes.pdf', 'I');
?>