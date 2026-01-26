<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();
if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

$Id_a_editar = '';
$Nombre_a_editar = '';
$Apellido_a_editar = '';
$Correo_a_editar = '';
$Telefono_a_editar = '';
$Direccion_a_editar = '';
$Deuda_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Nombre_busqueda = '';
$Apellido_busqueda = '';
$Correo_busqueda = '';
$Telefono_busqueda = '';
$Deuda_busqueda = '';
$Error_correo = '';
$Error_telefono = '';
$Error_nombre_apellido = '';
$Error_direccion = '';
$Error_general = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Action = $_POST['action'] ?? '';

    switch ($Action) {
        case 'agregar':
            $Nombre = $_POST['nombre'] ?? '';
            $Apellido = $_POST['apellido'] ?? '';
            $Correo = $_POST['correo'] ?? '';
            $Telefono = $_POST['telefono'] ?? '';
            $Direccion = $_POST['direccion'] ?? '';
            $Deuda = $_POST['deuda'] ?? '';

            $Nombre = ucfirst(strtolower($Nombre));
            $Apellido = ucfirst(strtolower($Apellido));

            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
                $Error_nombre_apellido = "El nombre y el apellido solo pueden contener letras";
            }

            if (empty(trim($Direccion))) {
                $Error_direccion = "La dirección no puede estar vacía";
            } else if (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/", $Direccion)) {
                $Error_direccion = "La dirección solo puede contener letras, números y espacios";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            }

            $Sql_verificar_correo = "SELECT id_clientes FROM clientes WHERE email = ?";
            $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
            $Stmt_verificar_correo->bind_param("s", $Correo);
            $Stmt_verificar_correo->execute();
            $Resultado_correo = $Stmt_verificar_correo->get_result();

            $Sql_verificar_telefono = "SELECT id_clientes FROM clientes WHERE telefono = ?";
            $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
            $Stmt_verificar_telefono->bind_param("s", $Telefono);
            $Stmt_verificar_telefono->execute();
            $Resultado_telefono = $Stmt_verificar_telefono->get_result();

            $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);

            if ($Error_correo_existe && $Error_telefono_existe) {
                $Error_general = "Ya existe un cliente con este correo y teléfono";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un cliente con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un cliente con este teléfono";
            }

            if (empty($Error_general) && empty($Error_nombre_apellido) && empty($Error_direccion) && empty($Error_telefono)) {
                $Sql = "INSERT INTO clientes (nombre, apellido, email, telefono, direccion, deuda) VALUES (?, ?, ?, ?, ?, ?)";
                $Stmt = $conn->prepare($Sql);

                if ($Stmt) {
                    $Stmt->bind_param("sssssd", $Nombre, $Apellido, $Correo, $Telefono, $Direccion, $Deuda);
                    if ($Stmt->execute()) {
                        $_SESSION['mensaje_exito'] = true;
                        header("Location: clientes.php");
                        exit;
                    }
                    $Stmt->close();
                }
            } else {
                $Nombre_a_editar = $Nombre;
                $Apellido_a_editar = $Apellido;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Direccion_a_editar = $Direccion;
                $Deuda_a_editar = $Deuda;
            }
            break;

        case 'eliminar':
            $Id_a_eliminar = intval($_POST['eliminar_id']);
            $Sql_eliminar = "DELETE FROM clientes WHERE id_clientes = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: clientes.php");
            exit;
            break;

        case 'editar':
            $Id_a_editar = intval($_POST['id_cliente_a_editar']);
            $Nombre = $_POST['nombre'] ?? '';
            $Apellido = $_POST['apellido'] ?? '';
            $Correo = $_POST['correo'] ?? '';
            $Telefono = $_POST['telefono'] ?? '';
            $Direccion = $_POST['direccion'] ?? '';
            $Deuda = $_POST['deuda'] ?? '';

            $Nombre = ucfirst(strtolower($Nombre));
            $Apellido = ucfirst(strtolower($Apellido));

            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
                $Error_nombre_apellido = "El nombre y el apellido solo pueden contener letras";
            }

            if (empty(trim($Direccion))) {
                $Error_direccion = "La dirección no puede estar vacía";
            } else if (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/", $Direccion)) {
                $Error_direccion = "La dirección solo puede contener letras, números y espacios";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            }
            
            $Sql_cliente_actual = "SELECT email, telefono FROM clientes WHERE id_clientes = ?";
            $Sql_cliente_actual = $conn->prepare($Sql_cliente_actual);
            $Sql_cliente_actual->bind_param("i", $Id_a_editar);
            $Sql_cliente_actual->execute();
            $Resultado_actual = $Sql_cliente_actual->get_result();
            $Cliente_actual = $Resultado_actual->fetch_assoc();
            $Sql_cliente_actual->close();

            $Error_correo_existe = false;
            $Error_telefono_existe = false;

            if ($Correo !== $Cliente_actual['email']) {
                $Sql_verificar_correo = "SELECT id_clientes FROM clientes WHERE email = ?";
                $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
                $Stmt_verificar_correo->bind_param("s", $Correo);
                $Stmt_verificar_correo->execute();
                $Resultado_correo = $Stmt_verificar_correo->get_result();
                $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            }

            if ($Telefono !== $Cliente_actual['telefono']) {
                $Sql_verificar_telefono = "SELECT id_clientes FROM clientes WHERE telefono = ?";
                $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
                $Stmt_verificar_telefono->bind_param("s", $Telefono);
                $Stmt_verificar_telefono->execute();
                $Resultado_telefono = $Stmt_verificar_telefono->get_result();
                $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);
            }

            if ($Error_correo_existe && $Error_telefono_existe) {
                $Error_general = "Ya existe un cliente con este correo y teléfono";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un cliente con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un cliente con este teléfono";
            }
            
            if (empty($Error_general) && empty($Error_nombre_apellido) && empty($Error_direccion) && empty($Error_telefono)) {
                $Sql_editar = "UPDATE clientes SET nombre = ?, apellido = ?, email = ?, telefono = ?, direccion = ?, deuda = ? WHERE id_clientes = ?";
                $Stmt_editar = $conn->prepare($Sql_editar);
                if ($Stmt_editar) {
                    $Stmt_editar->bind_param("sssssdi", $Nombre, $Apellido, $Correo, $Telefono, $Direccion, $Deuda, $Id_a_editar);
                    $Stmt_editar->execute();
                    $Stmt_editar->close();
                }
                $_SESSION['mensaje_exito_1'] = true;
                header("Location: clientes.php");
                exit;
            } else {
                $Nombre_a_editar = $Nombre;
                $Apellido_a_editar = $Apellido;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Direccion_a_editar = $Direccion;
                $Deuda_a_editar = $Deuda;
            }
            break;
            
        case 'buscar':
            $Nombre_busqueda = $_POST['nombre'] ?? '';
            $Apellido_busqueda = $_POST['apellido'] ?? '';
            $Correo_busqueda = $_POST['correo'] ?? '';
            $Telefono_busqueda = $_POST['telefono'] ?? '';
            $Direccion_busqueda = $_POST['direccion'] ?? '';
            $Deuda_busqueda = $_POST['deuda'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_cliente'])) {
    $Id_a_editar = intval($_GET['id_cliente']);
    $Sql_editar = "SELECT * FROM clientes WHERE id_clientes = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Cliente_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Cliente_a_editar) {
            $Nombre_a_editar = $Cliente_a_editar['nombre'];
            $Apellido_a_editar = $Cliente_a_editar['apellido'];
            $Correo_a_editar = $Cliente_a_editar['email'];
            $Telefono_a_editar = $Cliente_a_editar['telefono'];
            $Direccion_a_editar = $Cliente_a_editar['direccion'];
            $Deuda_a_editar = $Cliente_a_editar['deuda'];
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


if (!empty($Nombre_busqueda) && !empty($Apellido_busqueda)) {
    $Condiciones[] = "nombre LIKE ?";
    $Condiciones[] = "apellido LIKE ?";
    $Tipos .= 'ss';
    $Parametros[] = '%' . $Nombre_busqueda . '%';
    $Parametros[] = '%' . $Apellido_busqueda . '%';
    $Es_busqueda = true;
    $Operador_logico = "AND";
} elseif (!empty($Nombre_busqueda)) {
    $Condiciones[] = "nombre LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Nombre_busqueda . '%';
    $Es_busqueda = true;
    $Operador_logico = "OR";
} elseif (!empty($Apellido_busqueda)) {
    $Condiciones[] = "apellido LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Apellido_busqueda . '%';
    $Es_busqueda = true;
    $Operador_logico = "OR";
}
if (!empty($Correo_busqueda)) {
    $Condiciones[] = "email LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Correo_busqueda . '%';
    $Es_busqueda = true;
}
if (!empty($Telefono_busqueda)) {
    $Condiciones[] = "telefono LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Telefono_busqueda . '%';
    $Es_busqueda = true;
}

if (!empty($Direccion_busqueda)) {
    $Condiciones[] = "direccion LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Direccion_busqueda . '%';
    $Es_busqueda = true;
}

if (is_numeric($Deuda_busqueda)) {
    $Condiciones[] = "deuda = ?";
    $Tipos .= 'd';
    $Parametros[] = floatval($Deuda_busqueda);
    $Es_busqueda = true;
}

if (!empty($Condiciones)) {
    $Sql_buscar = "SELECT * FROM clientes WHERE " . implode(" AND ", $Condiciones);
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Stmt_busqueda->bind_param($Tipos, ...$Parametros);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {
    $Sql = "SELECT * FROM clientes ORDER BY id_clientes ASC";
    $Resultado = $conn->query($Sql);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de clientes</title>
    <link rel="stylesheet" href="clientes.css">
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

                <div class="titulo">Clientes</div>

                <?php if (!empty($Error_general) || !empty($Error_nombre_apellido) || !empty($Error_direccion) || !empty($Error_telefono)): ?>

                    <?php if (!empty($Error_nombre_apellido)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_nombre_apellido; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_general)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_general; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_direccion)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_direccion; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_telefono)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_telefono; ?></p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <form class="formulario-y-botones" method="POST">
                    <input type="hidden" id="action" name="action" value="<?php echo htmlspecialchars($Action_formulario); ?>">
                    <input type="hidden" name="id_cliente_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
                    <div class="contenedor-de-campos">
                        <div class="formulario-grupo">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($Nombre_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Apellido</label>
                            <input type="text" name="apellido" id="apellido" value="<?php echo htmlspecialchars($Apellido_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Correo</label>
                            <input type="email" name="correo" id="correo" value="<?php echo htmlspecialchars($Correo_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Teléfono</label>
                            <input type="number" name="telefono" id="telefono" value="<?php echo htmlspecialchars($Telefono_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Dirección</label>
                            <input type="text" name="direccion" id="direccion" value="<?php echo htmlspecialchars($Direccion_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Deuda</label>
                            <input type="number" name="deuda" id="deuda" step="0.01" value="<?php echo htmlspecialchars($Deuda_a_editar); ?>" required>
                        </div>
                    </div>
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="clientes.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                        <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaClientes.php?";
                            $Parametros = [];
                            if (!empty($Nombre_busqueda)) { $Parametros[] = "nombre=" . urlencode($Nombre_busqueda); }
                            if (!empty($Apellido_busqueda)) { $Parametros[] = "apellido=" . urlencode($Apellido_busqueda); }
                            if (!empty($Correo_busqueda)) { $Parametros[] = "correo=" . urlencode($Correo_busqueda); }
                            if (!empty($Telefono_busqueda)) { $Parametros[] = "telefono=" . urlencode($Telefono_busqueda); }
                            if (!empty($Direccion_busqueda)) { $Parametros[] = "direccion=" . urlencode($Direccion_busqueda); }
                            if (is_numeric($Deuda_busqueda)) { $Parametros[] = "deuda=" . urlencode($Deuda_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="clientes.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarClientes()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteClientes.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="tabla-contenedor">
                    <table class="datos-tabla" border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Deuda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($Resultado && $Resultado->num_rows > 0) {
                                while ($Fila = $Resultado->fetch_assoc()) {
                                    $Deuda = floatval($Fila['deuda']);
                                    $Clase_deuda = '';
                                    if ($Deuda > 25000) {
                                        $Clase_deuda = 'deuda-alta';
                                    } elseif ($Deuda > 15000) {
                                        $Clase_deuda = 'deuda-media';
                                    } else {
                                        $Clase_deuda = 'deuda-baja';
                                    }
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($Fila['id_clientes']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['apellido']) . "</td>";
                                    echo "<td><a href='https://mail.google.com/mail/?view=cm&fs=1&to=" . htmlspecialchars($Fila['email']) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/gmail.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['email']) . "</a></td>";
                                    echo "<td><a href='https://wa.me/549" . htmlspecialchars($Fila['telefono']) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/whatsapp.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['telefono']) . "</a></td>";
                                    echo "<td><a href='https://www.google.com/maps/search/?api=1&query=" . urlencode($Fila['direccion']) . "' target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/marcador.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['direccion']) . "</a></td>";
                                    $Deuda_formateada = number_format(
                                        floatval($Fila['deuda']),
                                        2,
                                        ',',
                                        '.'
                                    );
                                    echo "<td><span class='" . $Clase_deuda . "'>$ " . $Deuda_formateada . "</span></td>";
                                    echo "<td>";
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='action' value='modo_de_edicion'>";
                                    echo "<input type='hidden' name='id_cliente' value='" . htmlspecialchars($Fila['id_clientes']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>";
                                    echo "</form>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>";
                                    echo "<input type='hidden' name='action' value='eliminar'>";
                                    echo "<input type='hidden' name='eliminar_id' value='" . htmlspecialchars($Fila['id_clientes']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center;'>No se encontraron clientes</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>

        function BuscarClientes() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('nombre').required = false;
            document.getElementById('apellido').required = false;
            document.getElementById('telefono').required = false;
            document.getElementById('direccion').required = false;
            document.getElementById('correo').required = false;
            document.getElementById('deuda').required = false;
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
                        title: "El cliente ha sido eliminado exitosamente",
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
                title: "Cliente registrado exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Cliente modificado exitosamente",
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