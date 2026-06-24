<?php

require('./fpdf.php');

class PDF extends FPDF
{

   function Header()
   {

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

      $this->SetTextColor(76,175,80);
      $this->SetFont('Arial', 'B', 15);
      $this->SetX(($this->GetPageWidth() - 100) / 2);
      $this->Cell(100, 10, utf8_decode("REPORTE POR BUSQUEDA DE COMPRAS"), 0, 1, 'C', 0);
      $this->Ln(10);

      $Ancho_tabla = 275;
      $Margen_x = ($this->GetPageWidth() - $Ancho_tabla) / 2;
      $this->SetX($Margen_x);
      $this->SetFillColor(76,175,80);
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(163, 163, 163);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(10, 10, utf8_decode('ID'), 1, 0, 'C', 1);
      $this->Cell(45, 10, utf8_decode('Fecha'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Empleado'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Proveedor'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Método de pago'), 1, 0, 'C', 1);
      $this->Cell(45, 10, utf8_decode('Productos'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Estado'), 1, 0, 'C', 1);
      $this->Cell(35, 10, utf8_decode('Total'), 1, 1, 'C', 1);
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

$Condiciones = [];
$Parametros = [];
$Tipos = '';

$Fecha_busqueda = $_GET['fecha_compra'] ?? '';
$Empleado_busqueda = $_GET['id_empleado'] ?? '';
$Proveedor_busqueda = $_GET['id_proveedor'] ?? '';
$Metodo_de_pago_busqueda = $_GET['id_metodo_de_pago'] ?? '';
$Estado_busqueda = $_GET['id_estado'] ?? ''; 

if (!empty($Fecha_busqueda)) {
    $Condiciones[] = "c.fecha_compra LIKE ?"; 
    $Tipos .= 's';
    $Parametros[] = '%' . $Fecha_busqueda . '%';
}
if (!empty($Empleado_busqueda)) {
    $Condiciones[] = "c.id_empleado = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Empleado_busqueda;
}
if (!empty($Proveedor_busqueda)) {
    $Condiciones[] = "c.id_proveedor = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Proveedor_busqueda;
}
if (!empty($Metodo_de_pago_busqueda)) {
    $Condiciones[] = "c.id_metodo_de_pago = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Metodo_de_pago_busqueda;
}
if (!empty($Estado_busqueda)) {
    $Condiciones[] = "c.id_estado = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Estado_busqueda;
}

$Sql_busqueda = "
    SELECT 
        c.fecha_compra,
        e.nombre,
        e.apellido,
        p.nombre_fantasia,
        m.nombre_metodo_de_pago,
        c.listado_de_productos,
        es.nombre_del_estadoo,
        c.total_compra
    FROM 
        compras c 
    INNER JOIN 
        empleados e ON c.id_empleado = e.id_empleados
    INNER JOIN 
        proveedores p ON c.id_proveedor = p.id_proveedores
    INNER JOIN 
        metodo_de_pago m ON c.id_metodo_de_pago = m.id_metodo_de_pago
    INNER JOIN 
        estadoo es ON c.id_estado = es.id_estadoo
";

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
      $consulta_reporte_compras = $Stmt_busqueda->get_result();
      $Stmt_busqueda->close();
   } else {
      $consulta_reporte_compras = $conn->query($Sql_buscar);
   }
} else {
   $consulta_reporte_compras = $conn->query($Sql_busqueda);
}

if ($consulta_reporte_compras && $consulta_reporte_compras->num_rows > 0) {
   while ($Fila = $consulta_reporte_compras->fetch_assoc()) { 
       
      $i = $i + 1;

      $Listado_productos_json = json_decode($Fila['listado_de_productos'], true);
      $Productos_texto = [];

      if (is_array($Listado_productos_json)) {
         foreach ($Listado_productos_json as $Item) {
            $Id_productos = intval($Item['id']);
            $Cantidad = intval($Item['cantidad']);

            $Sql = $conn->query("SELECT nombre_del_producto FROM productos WHERE id_productos = $Id_productos");
            if ($Sql && $Fila_productos = $Sql->fetch_assoc()) {
               $Productos_texto[] = $Fila_productos['nombre_del_producto'] . ' (x' . $Cantidad . ')';
            }
         }
      }

      $Texto_productos = implode("\n", $Productos_texto);

      $Lineas_productos = count($Productos_texto);
      if ($Lineas_productos == 0) $Lineas_productos = 1;
      $Altura_linea = 10; 
      $Altura_celda = $Lineas_productos * $Altura_linea;

      $Ancho_tabla = 275; 
      $Margen_x = ($pdf->GetPageWidth() - $Ancho_tabla) / 2;
      $pdf->SetX($Margen_x);
      $pdf->Cell(10, $Altura_celda, utf8_decode($i), 1, 0, 'C');
      $pdf->Cell(45, $Altura_celda, utf8_decode($Fila['fecha_compra']), 1, 0, 'C');
      $pdf->Cell(35, $Altura_celda, utf8_decode($Fila['nombre'] . ' ' . $Fila['apellido']), 1, 0, 'C');
      $pdf->Cell(35, $Altura_celda, utf8_decode($Fila['nombre_fantasia']), 1, 0, 'C');
      $pdf->Cell(35, $Altura_celda, utf8_decode($Fila['nombre_metodo_de_pago']), 1, 0, 'C');

      $x = $pdf->GetX();
      $y = $pdf->GetY();

      $pdf->MultiCell(45, 10, utf8_decode($Texto_productos), 1, 'C');
      $Posicion_final = $pdf->GetY();

      $pdf->SetXY($x + 45, $y);
      $pdf->Cell(35, $Altura_celda, utf8_decode($Fila['nombre_del_estadoo']), 1, 0, 'C');
      $pdf->Cell(35, $Altura_celda, utf8_decode($Fila['total_compra']), 1, 0, 'C');

      $pdf->SetY($Posicion_final);

   }
} else {
   $pdf->Ln(10);
   $pdf->SetFont('Arial', 'B', 12);
   $pdf->SetTextColor(0, 0, 0);
   $pdf->Cell(0, 10, utf8_decode("No se encontraron compras"), 0, 1, 'C');
}
$pdf->Output('ReporteBusquedaCompras.php', 'I');
?>