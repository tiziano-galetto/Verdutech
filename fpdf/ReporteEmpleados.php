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
      $this->Cell(100, 10, utf8_decode("REPORTE DE EMPLEADOS "), 0, 1, 'C', 0);
      $this->Ln(10);

      $this->SetFillColor(76,175,80);
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(163, 163, 163);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(10, 10, utf8_decode('ID'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('Nombre'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('Apellido'), 1, 0, 'C', 1);
      $this->Cell(50, 10, utf8_decode('Correo'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Teléfono'), 1, 0, 'C', 1);
      $this->Cell(45, 10, utf8_decode('Puesto'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('A / I'), 1, 1, 'C', 1);
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

$consulta_reporte_empleados = $conn->query("
    SELECT 
        e.nombre,
        e.apellido,
        e.email,
        e.telefono,
        e.asistencia,
        p.nombre_del_puesto
    FROM 
        empleados e 
    INNER JOIN 
        puesto p ON e.id_puesto = p.id_puesto
");

while ($Fila = $consulta_reporte_empleados->fetch_assoc()) { 
   
   $i = $i + 1;
   $nombre_archivo_completo = $Fila['asistencia'];
   $directorio_archivos = '../uploads/asistencia/';
   $url_archivo = $directorio_archivos . $nombre_archivo_completo;

   $pdf->Cell(10, 10, utf8_decode($i), 1, 0, 'C');
   $pdf->Cell(20, 10, utf8_decode($Fila['nombre']), 1, 0, 'C');
   $pdf->Cell(20, 10, utf8_decode($Fila['apellido']), 1, 0, 'C');
   $pdf->Cell(50, 10, utf8_decode($Fila['email']), 1, 0, 'C');
   $pdf->Cell(25, 10, utf8_decode($Fila['telefono']), 1, 0, 'C');
   $pdf->Cell(45, 10, utf8_decode($Fila['nombre_del_puesto']), 1, 0, 'C');

   $ancho_celda = 20;
   $alto_celda = 10; 

   if (!empty($nombre_archivo_completo)) {

      $ancho_icono = 5;
      $alto_icono = 5;
      $margen_x = 7.5;
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
$pdf->Output('ReporteEmpleados.pdf', 'I');
?>