<?php

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();

$Sql_empleados = "SELECT id_empleados, nombre, apellido FROM empleados ORDER BY id_empleados ASC";
$Resultado_empleados = $conn->query($Sql_empleados);
$Empleados_disponibles = [];

if ($Resultado_empleados) {
    while ($Fila = $Resultado_empleados->fetch_assoc()) {
        $Empleados_disponibles[] = $Fila;
    }
}

$Sql_proveedores = "SELECT id_proveedores, nombre_fantasia FROM proveedores ORDER BY id_proveedores ASC";
$Resultado_proveedores = $conn->query($Sql_proveedores);
$Proveedores_disponibles = [];

if ($Resultado_proveedores) {
    while ($Fila = $Resultado_proveedores->fetch_assoc()) {
        $Proveedores_disponibles[] = $Fila;
    }
}

$Sql_metodo_de_pago = "SELECT id_metodo_de_pago, nombre_metodo_de_pago FROM metodo_de_pago ORDER BY id_metodo_de_pago ASC";
$Resultado_metodo_de_pago = $conn->query($Sql_metodo_de_pago);
$Metodo_de_pago_disponible = [];

if ($Resultado_metodo_de_pago) {
    while ($Fila = $Resultado_metodo_de_pago->fetch_assoc()) {
        $Metodo_de_pago_disponible[] = $Fila;
    }
}

$Sql_productos = "SELECT p.id_productos, p.img_productos, p.nombre_del_producto, p.precio, p.stock, p.id_tipo_productos, tp.nombre_tipo_productos,  p.id_tipo_unidades, tu.nombre_tipo_unidades
                  FROM productos p
                  JOIN tipo_productos tp ON p.id_tipo_productos = tp.id_tipo_productos
                  JOIN tipo_unidades tu ON p.id_tipo_unidades = tu.id_tipo_unidades
                  ORDER BY p.id_productos ASC";
$Resultado_productos = $conn->query($Sql_productos);
$Productos_disponible = [];
$Productos_map = [];

if ($Resultado_productos) {
    while ($Fila = $Resultado_productos->fetch_assoc()) {
        $Productos_disponible[] = $Fila;
        $Productos_map[$Fila['id_productos']] = $Fila;
    }
}

$Sql_estado = "SELECT id_estadoo, nombre_del_estadoo FROM estadoo ORDER BY id_estadoo ASC";
$Resultado_estado = $conn->query($Sql_estado);
$Estados_disponibles = [];

if ($Resultado_estado) {
    while ($Fila = $Resultado_estado->fetch_assoc()) {
        $Estados_disponibles[] = $Fila;
    }
}

$Id_a_editar = '';
$Fecha_a_editar = '';
$Empleado_a_editar = '';
$Proveedor_a_editar = '';
$Metodo_de_pago_a_editar = '';
$Listado_de_productos_a_editar = '[]';
$Estado_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Fecha_busqueda = '';
$Empleado_busqueda = '';
$Proveedor_busqueda = '';
$Metodo_de_pago_busqueda = '';
$Estado_busqueda = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Action = $_POST['action'] ?? '';

    switch ($Action) {
        case 'agregar':
            $Fecha = $_POST['fecha_compra'] ?? '';
            $Empleado = $_POST['id_empleado'] ?? '';
            $Proveedor = $_POST['id_proveedor'] ?? '';
            $Metodo_de_pago = $_POST['id_metodo_de_pago'] ?? '';
            
            $Listado_productos_json = $_POST['listado_productos_json'] ?? '[]'; 
            $Listado_productos_array = json_decode($Listado_productos_json, true);
            $Total_venta = 0.00;
            
            foreach ($Listado_productos_array as $Item) {
                $Id_productos = intval($Item['id']);
                $Cantidad = intval($Item['cantidad']);
                if (isset($Productos_map[$Id_productos])) {
                    $Precio = floatval($Productos_map[$Id_productos]['precio']);
                    $Total_venta += ($Precio * $Cantidad);
                }
            }
            $Listado_de_productos = $Listado_productos_json;
            $Total_venta_formateado = number_format($Total_venta, 2, '.', '');
            $Estado = $_POST['id_estado'] ?? '';

            $Sql = "INSERT INTO compras (fecha_compra, id_empleado, id_proveedor, id_metodo_de_pago, listado_de_productos, id_estado, total_compra) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $Stmt = $conn->prepare($Sql);

            if ($Stmt) {
                $Stmt->bind_param("siiisid", $Fecha, $Empleado, $Proveedor, $Metodo_de_pago, $Listado_de_productos, $Estado, $Total_venta_formateado);
                if ($Stmt->execute()) {
                    if ($Estado == 1 || $Estado == 2) {
                        foreach ($Listado_productos_array as $Item) {
                            $Id_productos = intval($Item['id']);
                            $Cantidad    = intval($Item['cantidad']);
                            $Sql_stock = "UPDATE productos SET stock = stock + ? WHERE id_productos = ?";
                            $Stmt_stock = $conn->prepare($Sql_stock);
                            $Stmt_stock->bind_param("ii", $Cantidad, $Id_productos);
                            $Stmt_stock->execute();
                            $Stmt_stock->close();
                        }
                    }
                    $_SESSION['mensaje_exito'] = true;
                    header("Location: compras.php");
                    exit;
                }
                $Stmt->close();
            }
            break;

        case 'eliminar':
            $Id_a_eliminar = intval($_POST['eliminar_id']);

            $Sql_obtener = "SELECT listado_de_productos, id_estado FROM compras WHERE id_compras = ?";
            $Stmt_obtener = $conn->prepare($Sql_obtener);
            if ($Stmt_obtener) {
                $Stmt_obtener->bind_param("i", $Id_a_eliminar);
                $Stmt_obtener->execute();
                $Stmt_obtener->bind_result($Listado_productos_json, $Estado);
                $Stmt_obtener->fetch();
                $Stmt_obtener->close();

                if ($Estado == 1 || $Estado == 2) {
                    $Listado_productos_array = json_decode($Listado_productos_json, true);
                    if (is_array($Listado_productos_array)) {
                        foreach ($Listado_productos_array as $Item) {
                            $Id_productos = intval($Item['id']);
                            $Cantidad    = intval($Item['cantidad']);
                            $Sql_stock = "UPDATE productos SET stock = stock - ? WHERE id_productos = ?";
                            $Stmt_stock = $conn->prepare($Sql_stock);
                            $Stmt_stock->bind_param("ii", $Cantidad, $Id_productos);
                            $Stmt_stock->execute();
                            $Stmt_stock->close();
                        }
                    }
                }
            }

            $Sql_eliminar = "DELETE FROM compras WHERE id_compras = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: compras.php");
            exit;
            break;

        case 'editar':
            $Id_a_editar = intval($_POST['id_compras_a_editar']);
            $Fecha = $_POST['fecha_compra'] ?? '';
            $Empleado = $_POST['id_empleado'] ?? '';
            $Proveedor = $_POST['id_proveedor'] ?? '';
            $Metodo_de_pago = $_POST['id_metodo_de_pago'] ?? '';
            
            $Listado_productos_json = $_POST['listado_productos_json'] ?? '[]'; 
            $Listado_productos_array = json_decode($Listado_productos_json, true);
            $Total_venta = 0.00;
            
            foreach ($Listado_productos_array as $Item) {
                $Id_productos = intval($Item['id']);
                $Cantidad = intval($Item['cantidad']);
                if (isset($Productos_map[$Id_productos])) {
                    $Precio = floatval($Productos_map[$Id_productos]['precio']);
                    $Total_venta += ($Precio * $Cantidad);
                }
            }
            $Listado_de_productos = $Listado_productos_json;
            $Total_venta_formateado = number_format($Total_venta, 2, '.', '');
            $Estado = $_POST['id_estado'] ?? '';
          
            $Sql_editar = "UPDATE compras SET fecha_compra = ?, id_empleado = ?, id_proveedor = ?, id_metodo_de_pago = ?, listado_de_productos = ?, id_estado = ?, total_compra = ? WHERE id_compras = ?";
            $Stmt_editar = $conn->prepare($Sql_editar);

            if ($Stmt_editar) {
                $Stmt_editar->bind_param("siiisidi", $Fecha, $Empleado, $Proveedor, $Metodo_de_pago, $Listado_de_productos, $Estado, $Total_venta_formateado, $Id_a_editar);
                $Stmt_editar->execute();
                $Stmt_editar->close();
            }
            $_SESSION['mensaje_exito_1'] = true;
            header("Location: compras.php");
            exit;
            break;
            
        case 'buscar':
            $Fecha_busqueda = $_POST['fecha_compra'] ?? '';
            $Empleado_busqueda = $_POST['id_empleado'] ?? '';
            $Proveedor_busqueda = $_POST['id_proveedor'] ?? '';
            $Metodo_de_pago_busqueda = $_POST['id_metodo_de_pago'] ?? '';
            $Estado_busqueda = $_POST['id_estado'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_compras'])) {
    $Id_a_editar = intval($_GET['id_compras']);
    $Sql_editar = "SELECT * FROM compras WHERE id_compras = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Compras_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Compras_a_editar) {
            $Fecha_a_editar = $Compras_a_editar['fecha_compra'];
            $Empleado_a_editar = $Compras_a_editar['id_empleado'];
            $Proveedor_a_editar = $Compras_a_editar['id_proveedor'];
            $Metodo_de_pago_a_editar = $Compras_a_editar['id_metodo_de_pago'];
            $Listado_de_productos_a_editar = $Compras_a_editar['listado_de_productos'];
            $Estado_a_editar = $Compras_a_editar['id_estado'];
            $Action_formulario = 'editar';
            $Submit_btn_texto = 'Guardar cambios';
        }
    }
}

$Resultado = null;
$Es_busqueda = false;
$Condiciones = [];
$Parametros = [];
$Tipos = '';

if (!empty($Fecha_busqueda)) {
    $Condiciones[] = "fecha_compra LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Fecha_busqueda . '%';
    $Es_busqueda = true;
}
if (is_numeric($Empleado_busqueda)) {
    $Condiciones[] = "id_empleado = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Empleado_busqueda);
    $Es_busqueda = true;
}
if (is_numeric($Proveedor_busqueda)) {
    $Condiciones[] = "id_proveedor = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Proveedor_busqueda);
    $Es_busqueda = true;
}
if (is_numeric($Metodo_de_pago_busqueda)) {
    $Condiciones[] = "id_metodo_de_pago = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Metodo_de_pago_busqueda);
    $Es_busqueda = true;
}
if (is_numeric($Estado_busqueda)) {
    $Condiciones[] = "id_estado = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Estado_busqueda);
    $Es_busqueda = true;
}

$Compras_por_pagina = 5;
$Pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$Offset = ($Pagina_actual - 1) * $Compras_por_pagina;

if (!empty($Condiciones)) {

    $Sql_count = "SELECT COUNT(*) as total FROM compras WHERE " . implode(" AND ", $Condiciones);
    $Stmt_count = $conn->prepare($Sql_count);
    if ($Stmt_count) {
        $Stmt_count->bind_param($Tipos, ...$Parametros);
        $Stmt_count->execute();
        $Total_compras = $Stmt_count->get_result()->fetch_assoc()['total'];
        $Stmt_count->close();
    }

    $Sql_buscar = "SELECT * FROM compras WHERE " . implode(" AND ", $Condiciones) . " LIMIT ? OFFSET ?";
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Tipos_pag = $Tipos . 'ii';
        $Parametros_pag = array_merge($Parametros, [$Compras_por_pagina, $Offset]);
        $Stmt_busqueda->bind_param($Tipos_pag, ...$Parametros_pag);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {
    
    $Resultado_count = $conn->query("SELECT COUNT(*) as total FROM compras");
    $Total_compras = $Resultado_count->fetch_assoc()['total'];

    $Sql = "SELECT * FROM compras ORDER BY id_compras ASC LIMIT ? OFFSET ?";
    $Stmt_pag = $conn->prepare($Sql);
    $Stmt_pag->bind_param("ii", $Compras_por_pagina, $Offset);
    $Stmt_pag->execute();
    $Resultado = $Stmt_pag->get_result();
    $Stmt_pag->close();
}

$Total_paginas = ceil($Total_compras / $Compras_por_pagina);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de compras</title>
    <link rel="stylesheet" href="compras.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="app-contenedor">
        <header class="barra-de-navegacion">
            <div class="logo-contenedor">
                <div class="logo"></div>
                <span class="logo-texto">Verdutech</span>
            </div>
            <a href="inicio.php" class="btn-salir"><img src="img/Volver.png" class="icono-volver">Volver al inicio</a>
        </header>

        <main class="contenedor-principal">
            <div class="contenedor-principal-grupo">

                <div class="titulo">Compras</div>

                <form class="formulario-y-botones" method="POST">
                    <input type="hidden" id="action" name="action" value="<?php echo htmlspecialchars($Action_formulario); ?>">
                    <input type="hidden" name="id_compras_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
                    <div class="contenedor-de-campos">
                        <div class="formulario-grupo">
                            <label>Fecha <span class="requerido">*</span></label>
                            <input type="datetime-local" name="fecha_compra" id="fecha_compra" value="<?php echo htmlspecialchars($Fecha_a_editar); ?>" min="<?php echo date('Y-m-d') ?>T00:00" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Empleado <span class="requerido">*</span></label>
                            <select 
                                name="id_empleado" 
                                id="id_empleado" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un empleado --</option>
        
                                <?php foreach ($Empleados_disponibles as $Empleado): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Empleado['id_empleados']) ?>"
                                        <?= (intval($Empleado['id_empleados']) === intval($Empleado_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Empleado['nombre'] . ' ' . $Empleado['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Proveedor <span class="requerido">*</span></label>
                            <select 
                                name="id_proveedor" 
                                id="id_proveedor" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un proveedor --</option>
        
                                <?php foreach ($Proveedores_disponibles as $Proveedor): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Proveedor['id_proveedores']) ?>"
                                        <?= (intval($Proveedor['id_proveedores']) === intval($Proveedor_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Proveedor['nombre_fantasia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Método de pago <span class="requerido">*</span></label>
                            <select 
                                name="id_metodo_de_pago" 
                                id="id_metodo_de_pago" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un método de pago --</option>
        
                                <?php foreach ($Metodo_de_pago_disponible as $Metodo_de_pago): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Metodo_de_pago['id_metodo_de_pago']) ?>"
                                        <?= (intval($Metodo_de_pago['id_metodo_de_pago']) === intval($Metodo_de_pago_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Metodo_de_pago['nombre_metodo_de_pago']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="formulario-grupo">
                            <label>Producto <span class="requerido">*</span></label>
                            <select id="select_producto" class="input-estilo">
                                <option value="" disabled selected>-- Seleccionar un producto --</option>
        
                                <?php foreach ($Productos_disponible as $Producto): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Producto['id_productos']) ?>"
                                    >
                                        <?= htmlspecialchars($Producto['nombre_del_producto']) ?> (Stock: <?= htmlspecialchars(intval($Producto['stock'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Cantidad <span class="requerido">*</span></label>
                            <input type="number" id="cantidad_producto" value="1" min="1" class="input-estilo">
                        </div>
                        <div class="formulario-grupo">
                            <label>Estado <span class="requerido">*</span></label>
                            <select 
                                name="id_estado" 
                                id="id_estado" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un estado --</option>
        
                                <?php foreach ($Estados_disponibles as $Estado): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Estado['id_estadoo']) ?>"
                                        <?= (intval($Estado['id_estadoo']) === intval($Estado_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Estado['nombre_del_estadoo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>&nbsp;</label>
                            <button type="button" class="btn-action" id="btn_agregar_producto"><img src="img/Agregar.png" class="iconos-principales">Agregar producto</button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="listado_productos_json" id="listado_productos_json" value='<?php echo htmlspecialchars($Listado_de_productos_a_editar); ?>' required>
                    <input type="hidden" name="total_venta_form" id="total_venta_form" value='0.00'>
                    <div class="tabla-contenedor">
                        <table class="datos-tabla" border="1">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Precio unitario</th>
                                    <th>Cantidad</th>
                                    <th>Precio total</th>
                                    <th>Tipo de producto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_productos_body">
                                </tbody>
                            <tfoot>
                                <td colspan="7" style="text-align: center;">
                                    <strong>Sub total:</strong> <span id="total_venta_display">$ 0,00</span>
                                </td>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="compras.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                        <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaCompras.php?";
                            $Parametros = [];
                            if (!empty($Fecha_busqueda)) { $Parametros[] = "fecha_compra=" . urlencode($Fecha_busqueda); }
                            if (is_numeric($Empleado_busqueda)) { $Parametros[] = "id_empleado=" . urlencode($Empleado_busqueda); }
                            if (is_numeric($Proveedor_busqueda)) { $Parametros[] = "id_proveedor=" . urlencode($Proveedor_busqueda); }
                            if (is_numeric($Metodo_de_pago_busqueda)) { $Parametros[] = "id_metodo_de_pago=" . urlencode($Metodo_de_pago_busqueda); }
                            if (is_numeric($Estado_busqueda)) { $Parametros[] = "id_estado=" . urlencode($Estado_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="compras.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarCompras()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteCompras.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="tabla-contenedor">
                    <table class="datos-tabla" border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th>Proveedor</th>
                                <th>Método de pago</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($Resultado && $Resultado->num_rows > 0) {
                                while ($Fila = $Resultado->fetch_assoc()) {
                                    $nombre_empleado_mostrar = 'N/A';
                                    foreach ($Empleados_disponibles as $Empleado) {
                                        if (intval($Empleado['id_empleados']) === intval($Fila['id_empleado'])) {
                                            $nombre_empleado_mostrar = $Empleado['nombre'] . ' ' . $Empleado['apellido'];
                                            break;
                                        }
                                    }
                                    $nombre_proveedor_mostrar = 'N/A';
                                    foreach ($Proveedores_disponibles as $Proveedor) {
                                        if (intval($Proveedor['id_proveedores']) === intval($Fila['id_proveedor'])) {
                                            $nombre_proveedor_mostrar = $Proveedor['nombre_fantasia'];
                                            break;
                                        }
                                    }
                                    $nombre_metodo_de_pago_mostrar = 'N/A';
                                    foreach ($Metodo_de_pago_disponible as $Metodo_de_pago) {
                                        if (intval($Metodo_de_pago['id_metodo_de_pago']) === intval($Fila['id_metodo_de_pago'])) {
                                            $nombre_metodo_de_pago_mostrar = $Metodo_de_pago['nombre_metodo_de_pago'];
                                            break;
                                        }
                                    }

                                    $Productos_mostrar = 'N/A';
                                    $listado_productos_json = $Fila['listado_de_productos'];
                                    
                                    if (!empty($listado_productos_json) && $listado_productos_json !== 'null') {
                                        $listado_productos_array = json_decode($listado_productos_json, true);
                                        $Productos = [];
                                        
                                        if (is_array($listado_productos_array)) {
                                            foreach ($listado_productos_array as $Item) {
                                                $Id_productos = intval($Item['id']);
                                                $Cantidad = intval($Item['cantidad']);
                                                if (isset($Productos_map[$Id_productos])) {
                                                    $Productos[] = htmlspecialchars($Productos_map[$Id_productos]['nombre_del_producto']) . ' (x' . $Cantidad . ')';
                                                }
                                            }
                                            $Productos_mostrar = implode(', ', $Productos);
                                        }
                                    }

                                    $nombre_estado_mostrar = 'N/A';
                                    $Clase_estado = '';
                                    foreach ($Estados_disponibles as $Estado) {
                                        if (intval($Estado['id_estadoo']) === intval($Fila['id_estado'])) {
                                            $nombre_estado_mostrar = $Estado['nombre_del_estadoo'];
                                            
                                            if (strcasecmp($nombre_estado_mostrar, 'Cancelada') === 0) {
                                                $Clase_estado = 'estado-cancelado';
                                                $Icono_estado = '<i class="fa-regular fa-circle-xmark"></i>';
                                            } elseif (strcasecmp($nombre_estado_mostrar, 'Pendiente') === 0) {
                                                $Clase_estado = 'estado-pendiente';
                                                $Icono_estado = '<i class="fa-regular fa-clock"></i>';
                                            } elseif (strcasecmp($nombre_estado_mostrar, 'Pagada') === 0) {
                                                $Clase_estado = 'estado-pagado';
                                                $Icono_estado = '<i class="fa-regular fa-circle-check"></i>';
                                            }
                                            break;
                                        }
                                    }
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($Fila['id_compras']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['fecha_compra']) . "</td>";
                                    echo "<td>" . htmlspecialchars($nombre_empleado_mostrar) . "</td>"; 
                                    echo "<td>" . htmlspecialchars($nombre_proveedor_mostrar) . "</td>";
                                    echo "<td>" . htmlspecialchars($nombre_metodo_de_pago_mostrar) . "</td>";
                                    echo "<td>" . $Productos_mostrar . "</td>";
                                    $Total_formateado = number_format(
                                        floatval($Fila['total_compra']),
                                        2,
                                        ',',
                                        '.'
                                    );
                                    echo "<td>$ " . $Total_formateado . "</td>";
                                    echo "<td><span class='" . $Clase_estado . "'>" . $Icono_estado . " " . htmlspecialchars($nombre_estado_mostrar) . "</span></td>";
                                    echo "<td>";
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='action' value='modo_de_edicion'>";
                                    echo "<input type='hidden' name='id_compras' value='" . htmlspecialchars($Fila['id_compras']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>";
                                    echo "</form>";

                                    echo "<a href='fpdf/NotaPedido.php?id=" . intval($Fila['id_compras']) . "'target='_blank' class='btn-tabla-action btn-descargar'><img src='img/Descargar.png' class='iconos-secundarios'>Nota de pedido</a>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>";
                                    echo "<input type='hidden' name='action' value='eliminar'>";
                                    echo "<input type='hidden' name='eliminar_id' value='" . htmlspecialchars($Fila['id_compras']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' style='text-align:center;'>No se encontraron compras</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($Total_paginas > 1): ?>
                <div class="paginador-contenedor">
                    <?php
                    $Params_paginador = [];
                    if (!empty($Fecha_busqueda)) { $Params_paginador[] = "fecha_compra=" . urlencode($Fecha_busqueda); }
                    if (is_numeric($Empleado_busqueda)) { $Params_paginador[] = "id_empleado=" . urlencode($Empleado_busqueda); }
                    if (is_numeric($Proveedor_busqueda)) { $Params_paginador[] = "id_proveedor=" . urlencode($Proveedor_busqueda); }
                    if (is_numeric($Metodo_de_pago_busqueda)) { $Params_paginador[] = "id_metodo_de_pago=" . urlencode($Metodo_de_pago_busqueda); }
                    if (is_numeric($Estado_busqueda)) { $Params_paginador[] = "id_estado=" . urlencode($Estado_busqueda); }
                    $Query_base = count($Params_paginador) ? '&' . implode('&', $Params_paginador) : '';

                    if ($Pagina_actual > 1): ?>
                        <a href="compras.php?pagina=<?php echo $Pagina_actual - 1 . $Query_base; ?>" class="btn-paginador flecha">⟪ Anterior</a>
                    <?php endif;

                    for ($i = 1; $i <= $Total_paginas; $i++): ?>
                        <a href="compras.php?pagina=<?php echo $i . $Query_base; ?>"
                           class="btn-paginador <?php echo ($i === $Pagina_actual) ? 'activo' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;

                    if ($Pagina_actual < $Total_paginas): ?>
                        <a href="compras.php?pagina=<?php echo $Pagina_actual + 1 . $Query_base; ?>" class="btn-paginador flecha">Siguiente ⟫</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>

        let productosEnCompra = [];
        const productosMap = <?= json_encode($Productos_map) ?>; 

        function cargarProductosDesdeJSON() {
            const jsonInput = document.getElementById('listado_productos_json').value;
            if (jsonInput && jsonInput !== 'null') {
                try {
                    const parsed = JSON.parse(jsonInput);
                    if (Array.isArray(parsed)) {
                        productosEnCompra = parsed;
                    }
                } catch (e) {
                    console.error("Error al parsear JSON de productos:", e);
                    productosEnCompra = [];
                }
            }
            actualizarTablaYTotal();
        }

        function actualizarTablaYTotal() {
            const tbody = document.getElementById('tabla_productos_body');
            tbody.innerHTML = '';
            let totalVenta = 0.00;

            if (productosEnCompra.length === 0) {
                tbody.innerHTML = `<tr><td colspan='7' style='text-align:center;'>No se han agregado productos a la compra</td></tr>`;
            } else {
                productosEnCompra.forEach((item, index) => {
                    const producto = productosMap[item.id];
                    if (producto) {
                        const precioUnitario = parseFloat(producto.precio);
                        const cantidad = parseInt(item.cantidad);
                        const subtotal = precioUnitario * cantidad;
                        totalVenta += subtotal;

                        const newRow = tbody.insertRow();
                        newRow.innerHTML = `
                            <td><img src="${producto.img_productos}" alt="Imagen" style="width:50px;height:50px;object-fit:cover;border-radius:5px;"></td>
                            <td>${producto.nombre_del_producto}</td>
                            <td>$ ${precioUnitario.toFixed(2).replace('.', ',')}</td>
                            <td>${cantidad}</td>
                            <td>$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                            <td>${producto.nombre_tipo_productos}</td>
                            <td><button type="button" class="btn-tabla-action btn-eliminar" onclick="eliminarProducto(${index})"><img src='img/X.png' class='iconos-secundarios'>Quitar</button></td>
                        `;
                    }
                });
            }
            
            document.getElementById('total_venta_display').innerHTML = `<strong>$ ${totalVenta.toFixed(2).replace('.', ',')}</strong>`;
            document.getElementById('listado_productos_json').value = JSON.stringify(productosEnCompra);
            document.getElementById('total_venta_form').value = totalVenta.toFixed(2);
        }

        function agregarProducto() {
            const select = document.getElementById('select_producto');
            const cantidadInput = document.getElementById('cantidad_producto');
            const productoId = select.value;
            const cantidad = parseInt(cantidadInput.value);

            if (!productoId) {
                alert("Por favor, seleccione un producto");
                return;
            }
            if (isNaN(cantidad) || cantidad < 1) {
                alert("La cantidad debe ser un número mayor o igual a 1");
                return;
            }

            const existingIndex = productosEnCompra.findIndex(item => item.id === parseInt(productoId));

            if (existingIndex > -1) {
                productosEnCompra[existingIndex].cantidad += cantidad;
            } else {
                productosEnCompra.push({
                    id: parseInt(productoId),
                    cantidad: cantidad
                });
            }
            
            select.selectedIndex = 0;
            cantidadInput.value = 1;

            actualizarTablaYTotal();
        }

        function eliminarProducto(index) {
            productosEnCompra.splice(index, 1);
            actualizarTablaYTotal();
        }

        document.addEventListener('DOMContentLoaded', () => {
            cargarProductosDesdeJSON(); 
            document.getElementById('btn_agregar_producto').addEventListener('click', agregarProducto);
        });

        function BuscarCompras() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('fecha_compra').required = false;
            document.getElementById('id_empleado').required = false;
            document.getElementById('id_proveedor').required = false;
            document.getElementById('id_metodo_de_pago').required = false;
            document.getElementById('id_estado').required = false;
            document.getElementById('listado_productos_json').required = false; 
            return true;
            
        }

        function confirmarEliminar(event) {
            event.preventDefault();
    
            Swal.fire({
                title: "¿Estás seguro?",
                text: "¡No podrás revertir esto!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4CAF50",
                cancelButtonColor: "#f44336",
                confirmButtonText: "Si, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "La compra ha sido eliminada exitosamente",
                        icon: "success",
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        event.target.submit();
                    });
                }
            });
        }

        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            Swal.fire({
                title: "Compra registrada exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Compra modificada exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito_1']); ?>
        <?php endif; ?>

    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>