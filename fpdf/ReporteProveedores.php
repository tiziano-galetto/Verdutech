<?php

require('./fpdf.php');

class PDF extends FPDF
{

   function Header()
   {
      global $conn; 

      $this->Image('Logo.png', 5, 5, 25);
      $this->SetFont('Arial', 'B', 20);
      $this->SetTextColor(76,175,80);
      $this->SetX(($this->GetPageWidth() - 100) / 2);
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

      $this->SetFont('Arial', 'B', 15);
      $this->SetTextColor(76,175,80);
      $this->SetX(($this->GetPageWidth() - 100) / 2);
      $this->Cell(100, 10, utf8_decode("REPORTE DE PROVEEDORES"), 0, 1, 'C', 0);
      $this->Ln(10);

      $this->SetX(3);
      $this->SetFillColor(76,175,80);
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(163, 163, 163);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(10, 10, utf8_decode('ID'), 1, 0, 'C', 1);
      $this->Cell(45, 10, utf8_decode('Razón social'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Cuit'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Nombre fantasia'), 1, 0, 'C', 1);
      $this->Cell(55, 10, utf8_decode('Correo'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Teléfono'), 1, 0, 'C', 1);
      $this->Cell(45, 10, utf8_decode('Dirección'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Deuda'), 1, 0, 'C', 1);
      $this->Cell(15, 10, utf8_decode('Precios'), 1, 1, 'C', 1);
   }

   function Footer()
   {
      $this->SetY(-15);
      $this->SetFont('Arial', 'I', 8);
      $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
      
      $this->SetY(-15);
      $this->SetFont('Arial', 'I', 8);
      $hoy = date('d/m/Y');
      $this->Cell(0, 10, utf8_decode($hoy), 0, 0, 'R');
   }
}

include '../funcion.php';
$conn = conexion();

$pdf = new PDF('L');
$pdf->AddPage();
$pdf->AliasNbPages();

$i = 0;
$pdf->SetFont('Arial', '', 10);
$pdf->SetDrawColor(163, 163, 163);

$consulta_reporte_proveedores = $conn->query(" select * from proveedores ");

while ($Fila = $consulta_reporte_proveedores->fetch_assoc()) { 
   
   $i = $i + 1;
   $pdf->SetX(3);
   $nombre_archivo_completo = $Fila['precios'];
   $directorio_archivos = '../uploads/precios/';
   $url_archivo = $directorio_archivos . $nombre_archivo_completo;

   $Deuda_formateada = number_format(
      floatval($Fila['deuda']),
      2,
      ',',
      '.'
   );
   $Deuda_con_simbolo = '$ ' . $Deuda_formateada;

   $Cuit_original = $Fila['cuit'];
   if (strlen($Cuit_original) === 11) {
      $Cuit_formateado = substr($Cuit_original, 0, 2) . '-' . substr($Cuit_original, 2, 8) . '-' . substr($Cuit_original, 10, 1);
   } else {
      $Cuit_formateado = $Cuit_original;
   }

   $pdf->Cell(10, 10, utf8_decode($i), 1, 0, 'C');
   $pdf->Cell(45, 10, utf8_decode($Fila['razon_social']), 1, 0, 'C');
   $pdf->Cell(35, 10, utf8_decode($Cuit_formateado), 1, 0, 'C');
   $pdf->Cell(35, 10, utf8_decode($Fila['nombre_fantasia']), 1, 0, 'C');
   $pdf->Cell(55, 10, utf8_decode($Fila['email']), 1, 0, 'C');
   $pdf->Cell(25, 10, utf8_decode($Fila['telefono']), 1, 0, 'C');
   $pdf->Cell(45, 10, utf8_decode($Fila['direccion']), 1, 0, 'C');
   $pdf->Cell(25, 10, utf8_decode($Deuda_con_simbolo), 1, 0, 'C');

   $ancho_celda = 15;
   $alto_celda = 10; 

   if (!empty($nombre_archivo_completo)) {

      $ancho_icono = 5;
      $alto_icono = 5;
      $margen_x = 5;
      $margen_y = 2.5;

      $pdf->Cell($ancho_celda, $alto_celda, '', 1, 0, 'C'); 

      $x_actual = $pdf->GetX();
      $y_actual = $pdf->GetY();
        
      $pdf->SetX($x_actual - $ancho_celda); 
        
      $pdf->Image(
         'Descarga.png',           
         $pdf->GetX() + $margen_x, 
         $pdf->GetY() + $margen_y, 
         $ancho_icono,            
         $alto_icono,             
         'PNG',                       
         $url_archivo
      );

      $pdf->SetXY($x_actual, $y_actual); 
        
   } else {
      $pdf->Cell($ancho_celda, $alto_celda, 'N/A', 1, 0, 'C'); 
   }
   $pdf->Ln();
}
$pdf->Output('ReporteProveedores.pdf', 'I');
?>