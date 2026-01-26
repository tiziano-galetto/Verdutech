<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();
if (!$conn) {
    die("Error de conexiÃ³n a la base de datos: " . mysqli_connect_error());
}

$Sql_empleados = "SELECT id_empleados, nombre, apellido FROM empleados ORDER BY id_empleados ASC";
$Resultado_empleados = $conn->query($Sql_empleados);
$Empleados_disponibles = [];

if ($Resultado_empleados) {
    while ($Fila = $Resultado_empleados->fetch_assoc()) {
        $Empleados_disponibles[] = $Fila;
    }
}

$Sql_clientes = "SELECT id_clientes, nombre, apellido FROM clientes ORDER BY id_clientes ASC";
$Resultado_clientes = $conn->query($Sql_clientes);
$Clientes_disponibles = [];

if ($Resultado_clientes) {
    while ($Fila = $Resultado_clientes->fetch_assoc()) {
        $Clientes_disponibles[] = $Fila;
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

$Sql_productos = "SELECT p.id_productos, p.nombre_del_producto, p.precio, p.id_tipo_productos, tp.nombre_tipo_productos 
                  FROM productos p
                  JOIN tipo_productos tp ON p.id_tipo_productos = tp.id_tipo_productos
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

$Sql_estado = "SELECT id_estado, nombre_del_estado FROM estado ORDER BY id_estado ASC";
$Resultado_estado = $conn->query($Sql_estado);
$Estados_disponibles = [];

if ($Resultado_estado) {
    while ($Fila = $Resultado_estado->fetch_assoc()) {
        $Estados_disponibles[] = $Fila;
    }
}

$Id_a_editar = '';
$Fecha_a_editar = '';
$Vendedor_a_editar = '';
$Comprador_a_editar = '';
$Metodo_de_pago_a_editar = '';
$Listado_de_productos_a_editar = '[]';
$Estado_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Fecha_busqueda = '';
$Vendedor_busqueda = '';
$Comprador_busqueda = '';
$Metodo_de_pago_busqueda = '';
$Estado_busqueda = '';
$Total_busqueda = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Action = $_POST['action'] ?? '';

    switch ($Action) {
        case 'agregar':
            $Fecha = $_POST['fecha'] ?? '';
            $Vendedor = $_POST['empleado'] ?? '';
            $Comprador = $_POST['cliente'] ?? '';
            $Metodo_de_pago = $_POST['metodo_de_pago'] ?? '';
            
            $Listado_productos_json = $_POST['listado_productos_json'] ?? '[]'; 
            $Listado_productos_array = json_decode($Listado_productos_json, true);
            $Total_venta = 0.00;
            
            foreach ($Listado_productos_array as $item) {
                $producto_id = intval($item['id']);
                $cantidad = intval($item['cantidad']);
                if (isset($Productos_map[$producto_id])) {
                    $precio = floatval($Productos_map[$producto_id]['precio']);
                    $Total_venta += ($precio * $cantidad);
                }
            }
            $Listado_de_productos = $Listado_productos_json;
            $Total_venta_formateado = number_format($Total_venta, 2, '.', '');
            $Estado = $_POST['estado'] ?? '';

            $Sql = "INSERT INTO ventas (fecha_venta, id_empleado, id_cliente, id_metodo_de_pago, listado_de_productos, id_estado, total_venta) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $Stmt = $conn->prepare($Sql);

            if ($Stmt) {
                $Stmt->bind_param("siiisid", $Fecha, $Vendedor, $Comprador, $Metodo_de_pago, $Listado_de_productos, $Estado, $Total_venta_formateado);
                if ($Stmt->execute()) {
                    $_SESSION['mensaje_exito'] = true;
                    header("Location: ventas.php");
                    exit;
                }
                $Stmt->close();
            }
            break;

        case 'eliminar':
            $Id_a_eliminar = intval($_POST['eliminar_id']);
            $Sql_eliminar = "DELETE FROM ventas WHERE id_ventas = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: ventas.php");
            exit;
            break;

        case 'editar':
            $Id_a_editar = intval($_POST['id_ventas_a_editar']);
            $Fecha = $_POST['fecha'] ?? '';
            $Vendedor = $_POST['empleado'] ?? '';
            $Comprador = $_POST['cliente'] ?? '';
            $Metodo_de_pago = $_POST['metodo_de_pago'] ?? '';
            
            $Listado_productos_json = $_POST['listado_productos_json'] ?? '[]';
            $Listado_productos_array = json_decode($Listado_productos_json, true);
            $Total_venta = 0.00;
            
            foreach ($Listado_productos_array as $item) {
                $producto_id = intval($item['id']);
                $cantidad = intval($item['cantidad']);
                if (isset($Productos_map[$producto_id])) {
                    $precio = floatval($Productos_map[$producto_id]['precio']);
                    $Total_venta += ($precio * $cantidad);
                }
            }
            $Listado_de_productos = $Listado_productos_json;
            $Total_venta_formateado = number_format($Total_venta, 2, '.', '');
            $Estado = $_POST['estado'] ?? '';
          
            $Sql_editar = "UPDATE ventas SET fecha_venta = ?, id_empleado = ?, id_cliente = ?, id_metodo_de_pago = ?, listado_de_productos = ?, id_estado = ?, total_venta = ? WHERE id_ventas = ?";
            $Stmt_editar = $conn->prepare($Sql_editar);

            if ($Stmt_editar) {
                $Stmt_editar->bind_param("siiisidi", $Fecha, $Vendedor, $Comprador, $Metodo_de_pago, $Listado_de_productos, $Estado, $Total_venta_formateado, $Id_a_editar);
                $Stmt_editar->execute();
                $Stmt_editar->close();
            }
            $_SESSION['mensaje_exito_1'] = true;
            header("Location: ventas.php");
            exit;
            break;
            
        case 'buscar':
            $Fecha_busqueda = $_POST['fecha'] ?? '';
            $Vendedor_busqueda = $_POST['empleado'] ?? '';
            $Comprador_busqueda = $_POST['cliente'] ?? '';
            $Metodo_de_pago_busqueda = $_POST['metodo_de_pago'] ?? '';
            $Estado_busqueda = $_POST['estado'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_ventas'])) {
    $Id_a_editar = intval($_GET['id_ventas']);
    $Sql_editar = "SELECT * FROM ventas WHERE id_ventas = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Ventas_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Ventas_a_editar) {
            $Fecha_a_editar = $Ventas_a_editar['fecha_venta'];
            $Vendedor_a_editar = $Ventas_a_editar['id_empleado'];
            $Comprador_a_editar = $Ventas_a_editar['id_cliente'];
            $Metodo_de_pago_a_editar = $Ventas_a_editar['id_metodo_de_pago'];
            $Listado_de_productos_a_editar = $Ventas_a_editar['listado_de_productos'];
            $Estado_a_editar = $Ventas_a_editar['id_estado'];
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
    $Condiciones[] = "fecha_venta LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Fecha_busqueda . '%';
    $Es_busqueda = true;
}
if (is_numeric($Vendedor_busqueda)) {
    $Condiciones[] = "id_empleado = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Vendedor_busqueda);
    $Es_busqueda = true;
}
if (is_numeric($Comprador_busqueda)) {
    $Condiciones[] = "id_cliente = ?";
    $Tipos .= 'i';
    $Parametros[] = intval($Comprador_busqueda);
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

if (!empty($Condiciones)) {
    $Sql_buscar = "SELECT * FROM ventas WHERE " . implode(" AND ", $Condiciones);
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Stmt_busqueda->bind_param($Tipos, ...$Parametros);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {
    $Sql = "SELECT * FROM ventas ORDER BY id_ventas ASC";
    $Resultado = $conn->query($Sql);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de ventas</title>
    <link rel="stylesheet" href="ventas.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

                <div class="titulo">Ventas</div>

                <form class="formulario-y-botones" method="POST">
                    <input type="hidden" id="action" name="action" value="<?php echo htmlspecialchars($Action_formulario); ?>">
                    <input type="hidden" name="id_ventas_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
                    <div class="contenedor-de-campos">
                        <div class="formulario-grupo">
                            <label>Fecha</label>
                            <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($Fecha_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Vendedor</label>
                            <select 
                                name="empleado" 
                                id="empleado" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un vendedor --</option>
        
                                <?php foreach ($Empleados_disponibles as $Empleado): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Empleado['id_empleados']) ?>"
                                        <?= (intval($Empleado['id_empleados']) === intval($Vendedor_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Empleado['nombre'] . ' ' . $Empleado['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Comprador</label>
                            <select 
                                name="cliente" 
                                id="cliente" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un comprador --</option>
        
                                <?php foreach ($Clientes_disponibles as $Cliente): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Cliente['id_clientes']) ?>"
                                        <?= (intval($Cliente['id_clientes']) === intval($Comprador_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Cliente['nombre'] . ' ' . $Cliente['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Método de pago</label>
                            <select 
                                name="metodo_de_pago" 
                                id="metodo_de_pago" 
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
                            <label>Producto</label>
                            <select id="select_producto" class="input-estilo">
                                <option value="" disabled selected>-- Seleccionar un producto --</option>
        
                                <?php foreach ($Productos_disponible as $Producto): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Producto['id_productos']) ?>"
                                    >
                                        <?= htmlspecialchars($Producto['nombre_del_producto']) ?> (Precio: $<?= htmlspecialchars(number_format(floatval($Producto['precio']), 2, ',', '.')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Cantidad</label>
                            <input type="number" id="cantidad_producto" value="1" min="1" class="input-estilo">
                        </div>
                        <div class="formulario-grupo">
                            <label>Estado</label>
                            <select 
                                name="estado" 
                                id="estado" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Id_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un estado --</option>
        
                                <?php foreach ($Estados_disponibles as $Estado): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Estado['id_estado']) ?>"
                                        <?= (intval($Estado['id_estado']) === intval($Estado_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Estado['nombre_del_estado']) ?>
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
                                <td colspan="6" style="text-align: center;">
                                    <strong>Sub total:</strong> <span id="total_venta_display">$ 0,00</span>
                                </td>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="ventas.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                        <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaClientes.php?";
                            $Parametros = [];
                            if (!empty($Fecha_busqueda)) { $Parametros[] = "fecha=" . urlencode($Fecha_busqueda); }
                            if (is_numeric($Vendedor_busqueda)) { $Parametros[] = "vendedor=" . urlencode($Vendedor_busqueda); }
                            if (is_numeric($Comprador_busqueda)) { $Parametros[] = "comprador=" . urlencode($Comprador_busqueda); }
                            if (is_numeric($Metodo_de_pago_busqueda)) { $Parametros[] = "metodo_de_pago=" . urlencode($Metodo_de_pago_busqueda); }
                            if (is_numeric($Estado_busqueda)) { $Parametros[] = "estado=" . urlencode($Estado_busqueda); }
                            if (is_numeric($Total_busqueda)) { $Parametros[] = "total=" . urlencode($Total_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="ventas.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarVentas()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteClientes.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="tabla-contenedor">
                    <table class="datos-tabla" border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Comprador</th>
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
                                    $nombre_cliente_mostrar = 'N/A';
                                    foreach ($Clientes_disponibles as $Cliente) {
                                        if (intval($Cliente['id_clientes']) === intval($Fila['id_cliente'])) {
                                            $nombre_cliente_mostrar = $Cliente['nombre'] . ' ' . $Cliente['apellido'];
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

                                    $nombre_productos_mostrar = 'Sin productos registrados';
                                    $listado_productos_json = $Fila['listado_de_productos'];
                                    
                                    if (!empty($listado_productos_json) && $listado_productos_json !== 'null') {
                                        $listado_productos_array = json_decode($listado_productos_json, true);
                                        $nombres_productos = [];
                                        
                                        if (is_array($listado_productos_array)) {
                                            foreach ($listado_productos_array as $item) {
                                                $producto_id = intval($item['id']);
                                                $cantidad = intval($item['cantidad']);
                                                if (isset($Productos_map[$producto_id])) {
                                                    $nombres_productos[] = htmlspecialchars($Productos_map[$producto_id]['nombre_del_producto']) . ' (x' . $cantidad . ')';
                                                }
                                            }
                                            $nombre_productos_mostrar = implode(', ', $nombres_productos);
                                        }
                                    }

                                    $nombre_estado_mostrar = 'N/A';
                                    $clase_estado = '';
                                    foreach ($Estados_disponibles as $Estado) {
                                        if (intval($Estado['id_estado']) === intval($Fila['id_estado'])) {
                                            $nombre_estado_mostrar = $Estado['nombre_del_estado'];
                                            
                                            if (strcasecmp($nombre_estado_mostrar, 'Cancelada') === 0) {
                                                $clase_estado = 'estado-cancelado';
                                            } elseif (strcasecmp($nombre_estado_mostrar, 'Pendiente') === 0) {
                                                $clase_estado = 'estado-pendiente';
                                            } elseif (strcasecmp($nombre_estado_mostrar, 'Cobrada') === 0) {
                                                $clase_estado = 'estado-cobrado';
                                            }
                                            break;
                                        }
                                    }
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($Fila['id_ventas']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['fecha_venta']) . "</td>";
                                    echo "<td>" . htmlspecialchars($nombre_empleado_mostrar) . "</td>"; 
                                    echo "<td>" . htmlspecialchars($nombre_cliente_mostrar) . "</td>";
                                    echo "<td>" . htmlspecialchars($nombre_metodo_de_pago_mostrar) . "</td>";
                                    echo "<td>" . $nombre_productos_mostrar . "</td>";
                                    $Total_formateado = number_format(
                                        floatval($Fila['total_venta']),
                                        2,
                                        ',',
                                        '.'
                                    );
                                    echo "<td>$ " . $Total_formateado . "</td>";
                                    echo "<td><span class='" . $clase_estado . "'>" . htmlspecialchars($nombre_estado_mostrar) . "</span></td>";
                                    echo "<td>";
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='action' value='modo_de_edicion'>";
                                    echo "<input type='hidden' name='id_ventas' value='" . htmlspecialchars($Fila['id_ventas']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>";
                                    echo "</form>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>";
                                    echo "<input type='hidden' name='action' value='eliminar'>";
                                    echo "<input type='hidden' name='eliminar_id' value='" . htmlspecialchars($Fila['id_ventas']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' style='text-align:center;'>No se encontraron ventas</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>

        let productosEnVenta = [];
        const productosMap = <?= json_encode($Productos_map) ?>; 

        function cargarProductosDesdeJSON() {
            const jsonInput = document.getElementById('listado_productos_json').value;
            if (jsonInput && jsonInput !== 'null') {
                try {
                    const parsed = JSON.parse(jsonInput);
                    if (Array.isArray(parsed)) {
                        productosEnVenta = parsed;
                    }
                } catch (e) {
                    console.error("Error al parsear JSON de productos:", e);
                    productosEnVenta = [];
                }
            }
            actualizarTablaYTotal();
        }

        function actualizarTablaYTotal() {
            const tbody = document.getElementById('tabla_productos_body');
            tbody.innerHTML = '';
            let totalVenta = 0.00;

            if (productosEnVenta.length === 0) {
                tbody.innerHTML = `<tr><td colspan='6' style='text-align:center;'>No se han agregado productos a la venta</td></tr>`;
            } else {
                productosEnVenta.forEach((item, index) => {
                    const producto = productosMap[item.id];
                    if (producto) {
                        const precioUnitario = parseFloat(producto.precio);
                        const cantidad = parseInt(item.cantidad);
                        const subtotal = precioUnitario * cantidad;
                        totalVenta += subtotal;

                        const newRow = tbody.insertRow();
                        newRow.innerHTML = `
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
            document.getElementById('listado_productos_json').value = JSON.stringify(productosEnVenta);
            document.getElementById('total_venta_form').value = totalVenta.toFixed(2);
        }

        function agregarProducto() {
            const select = document.getElementById('select_producto');
            const cantidadInput = document.getElementById('cantidad_producto');
            const productoId = select.value;
            const cantidad = parseInt(cantidadInput.value);

            if (!productoId) {
                alert("Por favor, seleccione un producto.");
                return;
            }
            if (isNaN(cantidad) || cantidad < 1) {
                alert("La cantidad debe ser un número mayor o igual a 1");
                return;
            }

            const existingIndex = productosEnVenta.findIndex(item => item.id === productoId);

            if (existingIndex > -1) {
                productosEnVenta[existingIndex].cantidad += cantidad;
            } else {
                productosEnVenta.push({
                    id: productoId,
                    cantidad: cantidad
                });
            }
            
            select.selectedIndex = 0;
            cantidadInput.value = 1;

            actualizarTablaYTotal();
        }

        function eliminarProducto(index) {
            productosEnVenta.splice(index, 1);
            actualizarTablaYTotal();
        }

        document.addEventListener('DOMContentLoaded', () => {
            cargarProductosDesdeJSON(); 
            document.getElementById('btn_agregar_producto').addEventListener('click', agregarProducto);
        });

        function BuscarVentas() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('fecha').required = false;
            document.getElementById('cliente').required = false;
            document.getElementById('empleado').required = false;
            document.getElementById('metodo_de_pago').required = false;
            document.getElementById('estado').required = false;
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
                        title: "La venta ha sido eliminada exitosamente",
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
                title: "Venta registrada exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Venta modificada exitosamente",
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