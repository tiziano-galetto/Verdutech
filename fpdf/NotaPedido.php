<?php

require('./fpdf.php');
require('../funcion.php');

$Id_compras = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
 
if (!$Id_compras || $Id_compras <= 0) {
    http_response_code(400);
    exit('ID de compra no válido');
}
 
$conn = conexion();
 
$Sql = "SELECT c.id_compras, c.fecha_compra, c.listado_de_productos, mp.nombre_metodo_de_pago, p.nombre_fantasia, p.direccion, p.telefono, p.email, p.cuit
        FROM compras c
        JOIN metodo_de_pago mp ON c.id_metodo_de_pago = mp.id_metodo_de_pago
        JOIN proveedores p ON c.id_proveedor = p.id_proveedores
        WHERE c.id_compras = ?";
 
$Stmt = $conn->prepare($Sql);
$Stmt->bind_param("i", $Id_compras);
$Stmt->execute();
$Resultado = $Stmt->get_result();
$Compra = $Resultado->fetch_assoc();
$Stmt->close();

$Listado_productos = json_decode($Compra['listado_de_productos'], true);
$Productos_listado = [];
if (is_array($Listado_productos)) {
    foreach ($Listado_productos as $Item) {
        $Id_producto = intval($Item['id']);
        $Cantidad = intval($Item['cantidad']);
        $Sql_prod = "SELECT nombre_del_producto, img_productos FROM productos WHERE id_productos = ?";
        $Stmt_prod = $conn->prepare($Sql_prod);
        $Stmt_prod->bind_param("i", $Id_producto);
        $Stmt_prod->execute();
        $Res_prod = $Stmt_prod->get_result();
        $Producto = $Res_prod->fetch_assoc();
        $Stmt_prod->close();
        if ($Producto) {
            $Productos_listado[] = [
                'cantidad' => $Cantidad,
                'nombre'   => $Producto['nombre_del_producto'],
                'imagen'   => $Producto['img_productos'],
            ];
        }
    }
}

$conn->close();
 
if (!$Compra) {
    http_response_code(404);
    exit('No se encontró una compra con ese ID');
}
 
$Pedido_numero   = $Compra['id_compras'];
$Fecha_compra    = $Compra['fecha_compra'];
$Metodo_de_pago  = $Compra['nombre_metodo_de_pago'];
$Nombre_fantasia   = $Compra['nombre_fantasia'];
$Direccion    = $Compra['direccion'];
$Telefono  = $Compra['telefono'];
$Email  = $Compra['email'];
$Cuit_original  = $Compra['cuit'];
if (strlen($Cuit_original) === 11) {
    $Cuit_formateado = substr($Cuit_original, 0, 2) . '-' . substr($Cuit_original, 2, 8) . '-' . substr($Cuit_original, 10, 1);
} else {
    $Cuit_formateado = $Cuit_original;
}

class PDF extends FPDF
{

   function Header()
   {
      $this->Image('Logo.png', 5, 5, 25);
      $this->SetFont('Arial', 'B', 20);
      $this->Cell(50);
      $this->SetTextColor(76, 175, 80);
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
      $this->Ln(5);

      $this->Cell(1);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(50, 10, utf8_decode("Cuit : 20-26018784-9"), 0, 0, 'L', 0);
      $this->Ln(15);
   }

   function Footer()
   {
      $this->SetY(-15);
      $this->SetFont('Arial', 'I', 8);
      $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
   }

   function Tabla($Pedido_numero, $Fecha_compra, $Metodo_de_pago, $Nombre_fantasia, $Direccion, $Telefono, $Email, $Cuit_formateado, $Productos_listado)
   {
      $margenIzq  = $this->lMargin;
      $anchoTotal = 190;

      $wCant = 15;
      $wImg  = 30;
      $wNom  = 50;
      $wPu   = 50;
      $wTot  = $anchoTotal - $wCant - $wImg - $wNom - $wPu;

      $anchoIzq = $wCant + $wImg + $wNom;
      $anchoDer = $wPu + $wTot;

      $xBase = $margenIzq;
      $yBase = $this->GetY();
      $hFila = 8;
      $hDer  = $hFila * 4;

      $hTitulo = $hDer - $hFila * 2;

      $this->SetXY($xBase, $yBase);
      $this->SetFont('Arial', 'B', 15);
      $this->SetTextColor(76, 175, 80);
      $this->Cell($anchoIzq, $hTitulo, utf8_decode('NOTA DE PEDIDO'), 1, 0, 'C');

      $xDer = $xBase + $anchoIzq;
      $yDer = $yBase;

      $this->Rect($xDer, $yDer, $anchoDer, $hDer);

      $this->SetTextColor(0);
      $hLinea    = 6;
      $altoTexto = $hLinea * 5;
      $yTexto    = $yDer + ($hDer - $altoTexto) / 2;
      $this->SetXY($xDer + 2, $yTexto);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($this->GetStringWidth(utf8_decode('SEÑOR (ES): ')), $hLinea, utf8_decode('SEÑOR (ES): '), 0, 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($anchoDer - 4 - $this->GetStringWidth(utf8_decode('SEÑOR (ES): ')), $hLinea, utf8_decode($Nombre_fantasia), 0, 2, 'L');
      $this->SetX($xDer + 2);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($this->GetStringWidth(utf8_decode('DIRECCIÓN: ')), $hLinea, utf8_decode('DIRECCIÓN: '), 0, 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($anchoDer - 4 - $this->GetStringWidth(utf8_decode('DIRECCIÓN: ')), $hLinea, utf8_decode($Direccion), 0, 2, 'L');
      $this->SetX($xDer + 2);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($this->GetStringWidth(utf8_decode('TELÉFONO: ')), $hLinea, utf8_decode('TELÉFONO: '), 0, 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($anchoDer - 4 - $this->GetStringWidth(utf8_decode('TELÉFONO: ')), $hLinea, utf8_decode($Telefono), 0, 2, 'L');
      $this->SetX($xDer + 2);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($this->GetStringWidth(utf8_decode('CORREO: ')), $hLinea, utf8_decode('CORREO: '), 0, 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($anchoDer - 4 - $this->GetStringWidth(utf8_decode('CORREO: ')), $hLinea, utf8_decode($Email), 0, 2, 'L');
      $this->SetX($xDer + 2);
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($this->GetStringWidth(utf8_decode('CUIT: ')), $hLinea, utf8_decode('CUIT: '), 0, 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($anchoDer - 4 - $this->GetStringWidth(utf8_decode('CUIT: ')), $hLinea, utf8_decode($Cuit_formateado), 0, 2, 'L');

      $wPed  = $wCant + $wImg;
      $wFech = $anchoIzq - $wPed;

      $this->SetXY($xBase, $yBase + $hTitulo);
      $this->SetFont('Arial', 'B', 10);
      $this->SetTextColor(0);
      $this->Cell($wPed,  $hFila, utf8_decode('PEDIDO N°'), 1, 0, 'C');
      $this->Cell($wFech, $hFila, utf8_decode('FECHA'), 1, 0, 'C');

      $this->SetXY($xBase, $yBase + $hTitulo + $hFila);
      $this->SetFont('Arial', '', 10);
      $this->Cell($wPed,  $hFila, $Pedido_numero, 1, 0, 'C');
      $this->Cell($wFech, $hFila, $Fecha_compra, 1, 0, 'C');

      $this->SetXY($xBase, $yBase + $hDer);
      $this->SetFont('Arial', 'B', 10);
      $this->SetTextColor(0);

      $this->Cell($wCant, $hFila, utf8_decode('CANT.'), 1, 0, 'C');
      $this->Cell($wImg, $hFila, utf8_decode('IMAGEN'), 1, 0, 'C');
      $this->Cell($wNom, $hFila, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C');
      $this->Cell($wPu,  $hFila, utf8_decode('P. UNITARIO'), 1, 0, 'C');
      $this->Cell($wTot, $hFila, utf8_decode('P. TOTAL'), 1, 1, 'C');

      $altaFila = 15;
      $this->SetFont('Arial', '', 10);

      foreach ($Productos_listado as $Prod) {
          $y = $this->GetY();
  
          $this->Rect($xBase, $y, $wCant, $altaFila);
          $this->SetXY($xBase, $y + ($altaFila - $hFila) / 2);
          $this->Cell($wCant, $hFila, $Prod['cantidad'], 0, 0, 'C');

          $this->Rect($xBase + $wCant, $y, $wImg, $altaFila);
          $Ruta_imagen = '../' . $Prod['imagen'];
          if (file_exists($Ruta_imagen)) {
              $this->Image($Ruta_imagen, $xBase + $wCant + 5, $y + 1, 20, $altaFila - 2);
          }

          $this->Rect($xBase + $wCant + $wImg, $y, $wNom, $altaFila);
          $this->SetXY($xBase + $wCant + $wImg, $y + ($altaFila - $hFila) / 2);
          $this->Cell($wNom, $hFila, utf8_decode($Prod['nombre']), 0, 0, 'C');

          $this->Rect($xBase + $wCant + $wImg + $wNom, $y, $wPu, $altaFila);

          $this->Rect($xBase + $wCant + $wImg + $wNom + $wPu, $y, $wTot, $altaFila);

          $this->SetXY($xBase, $y + $altaFila);
      }

      $wMetodo = $wCant + $wImg + $wNom + $wPu;
      $this->SetFont('Arial', 'B', 10);
      $wLabel = $this->GetStringWidth(utf8_decode('MÉTODO DE PAGO: '));
      $this->Cell($wLabel, $hFila, utf8_decode('MÉTODO DE PAGO: '), 'LTB', 0, 'L');
      $this->SetFont('Arial', '', 10);
      $this->Cell($wMetodo - $wLabel, $hFila, utf8_decode($Metodo_de_pago), 'RTB', 0, 'L');
      $this->SetFont('Arial', 'B', 10);
      $this->Cell($wTot, $hFila, utf8_decode('TOTAL:'), 1, 1, 'L');

      $this->SetTextColor(0);
   }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->AliasNbPages();

$pdf->Tabla($Pedido_numero, $Fecha_compra, $Metodo_de_pago, $Nombre_fantasia, $Direccion, $Telefono, $Email, $Cuit_formateado, $Productos_listado);

$pdf->Output('NotaPedido.pdf', 'I');
?>