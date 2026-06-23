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
      $this->Cell(100, 10, utf8_decode("REPORTE POR BUSQUEDA DE PRODUCTOS"), 0, 1, 'C', 0);
      $this->Ln(10);

      $this->SetX(3);
      $this->SetFillColor(76,175,80);
      $this->SetTextColor(255, 255, 255);
      $this->SetDrawColor(163, 163, 163);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(10, 12, utf8_decode('ID'), 1, 0, 'C', 1);
      $this->Cell(17, 12, utf8_decode('Imagen'), 1, 0, 'C', 1);
      $this->Cell(35, 12, utf8_decode('Nombre'), 1, 0, 'C', 1);
      $this->Cell(35, 12, utf8_decode('Tipo de producto'), 1, 0, 'C', 1);
      $this->Cell(35, 12, utf8_decode('Proveedor'), 1, 0, 'C', 1);
      $this->Cell(35, 12, utf8_decode('Precio'), 1, 0, 'C', 1);
      $this->Cell(35, 12, utf8_decode('Tipo de unidad'), 1, 1, 'C', 1);
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

$Nombre_busqueda = $_GET['nombre_del_producto'] ?? '';
$Tipo_productos_busqueda = $_GET['id_tipo_productos'] ?? '';
$Tipo_unidades_busqueda = $_GET['id_tipo_unidades'] ?? '';
$Proveedores_busqueda = $_GET['id_proveedores'] ?? '';
$Precio_busqueda = $_GET['precio'] ?? ''; 

if (!empty($Nombre_busqueda)) {
    $Condiciones[] = "p.nombre_del_producto LIKE ?"; 
    $Tipos .= 's';
    $Parametros[] = '%' . $Nombre_busqueda . '%';
}
if (!empty($Tipo_productos_busqueda)) {
    $Condiciones[] = "p.id_tipo_productos = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Tipo_productos_busqueda;
}
if (!empty($Tipo_unidades_busqueda)) {
    $Condiciones[] = "p.id_tipo_unidades = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Tipo_unidades_busqueda;
}
if (!empty($Proveedores_busqueda)) {
    $Condiciones[] = "p.id_proveedores = ?"; 
    $Tipos .= 'i';
    $Parametros[] = $Proveedores_busqueda;
}
if (!empty($Precio_busqueda)) {
    $Condiciones[] = "p.precio = ?"; 
    $Tipos .= 'd';
    $Parametros[] = $Precio_busqueda;
}

$Sql_busqueda = "
    SELECT 
        p.img_productos,
        p.nombre_del_producto,
        tp.nombre_tipo_productos,
        tu.nombre_tipo_unidades,
        pr.nombre_fantasia,
        p.precio
    FROM 
        productos p 
    INNER JOIN 
        tipo_productos tp ON p.id_tipo_productos = tp.id_tipo_productos
    INNER JOIN 
        tipo_unidades tu ON p.id_tipo_unidades = tu.id_tipo_unidades
    INNER JOIN 
        proveedores pr ON p.id_proveedores = pr.id_proveedores
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
      $consulta_reporte_productos = $Stmt_busqueda->get_result();
      $Stmt_busqueda->close();
   } else {
      $consulta_reporte_productos = $conn->query($Sql_buscar);
   }
} else {
   $consulta_reporte_productos = $conn->query($Sql_busqueda);
}

if ($consulta_reporte_productos && $consulta_reporte_productos->num_rows > 0) {
   while ($Fila = $consulta_reporte_productos->fetch_assoc()) { 
       
        $i = $i + 1;
        $pdf->SetX(3);
   
        $pdf->Cell(10, 12, utf8_decode($i), 1, 0, 'C');

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell(17, 12, '', 1, 0, 'C');
        $Imagen = '../' . $Fila['img_productos'];

        if (!empty($Fila['img_productos']) && file_exists($Imagen)) {
            $pdf->Image($Imagen, $x + 1, $y + 1, 15, 10);
        }

        $pdf->Cell(35, 12, utf8_decode($Fila['nombre_del_producto']), 1, 0, 'C');
        $pdf->Cell(35, 12, utf8_decode($Fila['nombre_tipo_productos']), 1, 0, 'C');
        $pdf->Cell(35, 12, utf8_decode($Fila['nombre_fantasia']), 1, 0, 'C');
        $pdf->Cell(35, 12, utf8_decode($Fila['precio']), 1, 0, 'C');
        $pdf->Cell(35, 12, utf8_decode($Fila['nombre_tipo_unidades']), 1, 1, 'C');
   }
} else {
   $pdf->Ln(10);
   $pdf->SetFont('Arial', 'B', 12);
   $pdf->SetTextColor(0, 0, 0);
   $pdf->Cell(0, 10, utf8_decode("No se encontraron productos"), 0, 1, 'C');
}
$pdf->Output('ReporteBusquedaProductos.pdf', 'I');
?>