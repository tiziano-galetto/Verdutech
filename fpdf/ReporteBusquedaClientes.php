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
      $this->Cell(100, 10, utf8_decode("REPORTE POR BUSQUEDA DE CLIENTES "), 0, 1, 'C', 0);
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
      $this->Cell(40, 10, utf8_decode('Dirección'), 1, 0, 'C', 1);
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

$Condiciones = [];
$Parametros = [];
$Tipos = '';

$Nombre_busqueda = $_GET['nombre'] ?? '';
$Apellido_busqueda = $_GET['apellido'] ?? '';
$Correo_busqueda = $_GET['correo'] ?? '';
$Telefono_busqueda = $_GET['telefono'] ?? '';
$Direccion_busqueda = $_GET['direccion'] ?? '';
$Deuda_busqueda = $_GET['deuda'] ?? '';

if (!empty($Nombre_busqueda)) {
    $Condiciones[] = "nombre LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Nombre_busqueda . '%';
}
if (!empty($Apellido_busqueda)) {
    $Condiciones[] = "apellido LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Apellido_busqueda . '%';
}
if (!empty($Correo_busqueda)) {
    $Condiciones[] = "email LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Correo_busqueda . '%';
}
if (!empty($Telefono_busqueda)) {
    $Condiciones[] = "telefono LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Telefono_busqueda . '%';
}

if (!empty($Direccion_busqueda)) {
    $Condiciones[] = "direccion LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Direccion_busqueda . '%';
}

if (is_numeric($Deuda_busqueda)) {
    $Condiciones[] = "deuda = ?";
    $Tipos .= 'd';
    $Parametros[] = floatval($Deuda_busqueda);
}

$Sql_busqueda = "SELECT * FROM clientes";

if (!empty($Condiciones)) {
   $Sql_buscar = $Sql_busqueda . " WHERE " . implode(" AND ", $Condiciones);
   $Stmt_busqueda = $conn->prepare($Sql_buscar);
    
   if ($Stmt_busqueda && !empty($Tipos)) {
      $binding_params = array_merge([$Tipos], $Parametros);
      $Referencias = [];
      foreach ($binding_params as $key => $value) {
         $Referencias[$key] = &$binding_params[$key];
      }
      call_user_func_array(array($Stmt_busqueda, 'bind_param'), $Referencias);
        
      $Stmt_busqueda->execute();
      $consulta_reporte_clientes = $Stmt_busqueda->get_result();
      $Stmt_busqueda->close();
   } else {
      $consulta_reporte_clientes = $conn->query($Sql_buscar);
   }
} else {
   $consulta_reporte_clientes = $conn->query($Sql_busqueda);
}

if ($consulta_reporte_clientes && $consulta_reporte_clientes->num_rows > 0) {
    while ($datos_reporte = $consulta_reporte_clientes->fetch_object()) {      
        $i = $i + 1;

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
        $pdf->Cell(40, 10, utf8_decode($datos_reporte->direccion), 1, 0, 'C', 0);
        $pdf->Cell(25, 10, $Deuda_con_simbolo, 1, 1, 'C', 0);
    }
} else {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, utf8_decode("No se encontraron clientes"), 0, 1, 'C');
}
$pdf->Output('ReporteBusquedaClientes.pdf', 'I');
?>