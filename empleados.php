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

$Sql_puestos = "SELECT id_puesto, nombre_del_puesto FROM puesto ORDER BY id_puesto ASC";
$Resultado_puestos = $conn->query($Sql_puestos);

$Puestos_disponibles = [];
if ($Resultado_puestos && $Resultado_puestos->num_rows > 0) {
    while ($Fila_puesto = $Resultado_puestos->fetch_assoc()) {
        $Puestos_disponibles[] = $Fila_puesto;
    }
}

$Id_a_editar = '';
$Nombre_a_editar = '';
$Apellido_a_editar = '';
$Correo_a_editar = '';
$Telefono_a_editar = '';
$Puesto_a_editar = '';
$Asistencia_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Nombre_busqueda = '';
$Apellido_busqueda = '';
$Correo_busqueda = '';
$Telefono_busqueda = '';
$Puesto_busqueda = '';
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
            $Puesto = $_POST['puesto'] ?? ''; 
            $Telefono = $_POST['telefono'] ?? '';
            $Archivo_asistencia = $_FILES['asistencia'] ?? null;
            $Ruta_asistencia_db = '';

            $Nombre = ucfirst(strtolower($Nombre));
            $Apellido = ucfirst(strtolower($Apellido));

            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
                $Error_nombre_apellido = "El nombre y el apellido solo pueden contener letras";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            }

            $Sql_verificar_correo = "SELECT id_empleados FROM empleados WHERE email = ?";
            $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
            $Stmt_verificar_correo->bind_param("s", $Correo);
            $Stmt_verificar_correo->execute();
            $Resultado_correo = $Stmt_verificar_correo->get_result();

            $Sql_verificar_telefono = "SELECT id_empleados FROM empleados WHERE telefono = ?";
            $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
            $Stmt_verificar_telefono->bind_param("s", $Telefono);
            $Stmt_verificar_telefono->execute();
            $Resultado_telefono = $Stmt_verificar_telefono->get_result();

            $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);

            if ($Error_correo_existe && $Error_telefono_existe) {
                $Error_general = "Ya existe un empleado con este correo y teléfono";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un empleado con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un empleado con este teléfono";
            }
            if (empty($Puesto) || !is_numeric($Puesto)) {
                 $Error_general = "Debe seleccionar un puesto válido";
            } else {
                 $Sql_verificar_puesto = "SELECT id_puesto FROM puesto WHERE id_puesto = ?";
                 $Stmt_verificar_puesto = $conn->prepare($Sql_verificar_puesto);
                 $Stmt_verificar_puesto->bind_param("i", $Puesto);
                 $Stmt_verificar_puesto->execute();
                 $Resultado_puesto = $Stmt_verificar_puesto->get_result();
                 if ($Resultado_puesto->num_rows === 0) {
                     $Error_general = "El puesto seleccionado no es válido";
                 }
                 $Stmt_verificar_puesto->close();
            }

            if (empty($Error_general) && empty($Error_nombre_apellido) && empty($Error_telefono) && empty($Error_direccion)) {
    
                if ($Archivo_asistencia && $Archivo_asistencia['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/asistencia/';
        
                    if (!is_dir($Directorio_subida)) {
                        mkdir($Directorio_subida, 0777, true);
                    }

                    $Nombre_archivo_original = basename($Archivo_asistencia['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('asistencia_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;

                    if (move_uploaded_file($Archivo_asistencia['tmp_name'], $Ruta_destino)) {
                        $Ruta_asistencia_db = $Nombre_archivo_unico;
                    } else {
                        $Error_direccion = "Error al subir el archivo de asistencia";
                    }
                } elseif ($Archivo_asistencia && $Archivo_asistencia['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_asistencia['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql = "INSERT INTO empleados (nombre, apellido, email, telefono, id_puesto, asistencia) VALUES (?, ?, ?, ?, ?, ?)";
                    $Stmt = $conn->prepare($Sql);

                    if ($Stmt) {
                        $Stmt->bind_param("ssssis", $Nombre, $Apellido, $Correo, $Telefono, $Puesto, $Ruta_asistencia_db); 
                        if ($Stmt->execute()) {
                            $_SESSION['mensaje_exito'] = true;
                            header("Location: empleados.php");
                            exit;
                        }
                        $Stmt->close();
                    }
                }

            } else {
                $Nombre_a_editar = $Nombre;
                $Apellido_a_editar = $Apellido;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Puesto_a_editar = $Puesto;
                if ($Archivo_asistencia && $Archivo_asistencia['error'] == UPLOAD_ERR_OK) {
                    $Asistencia_a_editar = basename($Archivo_asistencia['name']);
                }
            }
            break;

        case 'eliminar':
            
            $Id_a_eliminar = intval($_POST['eliminar_id']);
            $Sql_seleccionar_archivo = "SELECT asistencia FROM empleados WHERE id_empleados = ?";
            $Stmt_seleccionar_archivo = $conn->prepare($Sql_seleccionar_archivo);
            if ($Stmt_seleccionar_archivo) {
                $Stmt_seleccionar_archivo->bind_param("i", $Id_a_eliminar);
                $Stmt_seleccionar_archivo->execute();
                $Resultado_archivo = $Stmt_seleccionar_archivo->get_result();
                $Fila_archivo = $Resultado_archivo->fetch_assoc();
                $Stmt_seleccionar_archivo->close();
            
                if ($Fila_archivo && !empty($Fila_archivo['asistencia'])) {
                    $Ruta_archivo = 'uploads/asistencia/' . $Fila_archivo['asistencia'];
                    if (file_exists($Ruta_archivo)) {
                        unlink($Ruta_archivo);
                    }
                }
            }

            $Sql_eliminar = "DELETE FROM empleados WHERE id_empleados = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: empleados.php");
            exit;
            break;

        case 'editar':
            
            $Id_a_editar = intval($_POST['id_empleado_a_editar']);
            $Nombre = $_POST['nombre'] ?? '';
            $Apellido = $_POST['apellido'] ?? '';
            $Correo = $_POST['correo'] ?? '';
            $Telefono = $_POST['telefono'] ?? '';
            $Puesto = $_POST['puesto'] ?? ''; 
            $Archivo_asistencia_nuevo = $_FILES['asistencia'] ?? null;
            $Ruta_asistencia_db = $_POST['asistencia_actual'] ?? ''; 
            $Borrar_archivo = isset($_POST['borrar_asistencia']); 

            $Nombre = ucfirst(strtolower($Nombre));
            $Apellido = ucfirst(strtolower($Apellido));

            if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
                $Error_nombre_apellido = "El nombre y el apellido solo pueden contener letras";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            }
            
            $Sql_empleado_actual = "SELECT email, telefono, asistencia FROM empleados WHERE id_empleados = ?";
            $Stmt_empleado_actual = $conn->prepare($Sql_empleado_actual);
            $Stmt_empleado_actual->bind_param("i", $Id_a_editar);
            $Stmt_empleado_actual->execute();
            $Resultado_actual = $Stmt_empleado_actual->get_result();
            $Empleado_actual = $Resultado_actual->fetch_assoc();
            $Stmt_empleado_actual->close();
            
            $Ruta_asistencia_db_actual = $Empleado_actual['asistencia'];
            $Ruta_asistencia_db = $Ruta_asistencia_db_actual;

            if (empty($Puesto) || !is_numeric($Puesto)) {
                 $Error_general = "Debe seleccionar un puesto válido";
            } else {
                 $Sql_verificar_puesto = "SELECT id_puesto FROM puesto WHERE id_puesto = ?";
                 $Stmt_verificar_puesto = $conn->prepare($Sql_verificar_puesto);
                 $Stmt_verificar_puesto->bind_param("i", $Puesto);
                 $Stmt_verificar_puesto->execute();
                 $Resultado_puesto = $Stmt_verificar_puesto->get_result();
                 if ($Resultado_puesto->num_rows === 0) {
                     $Error_general = "El puesto seleccionado no es válido";
                 }
                 $Stmt_verificar_puesto->close();
            }

            $Error_correo_existe = false;
            $Error_telefono_existe = false;

            if ($Correo !== $Empleado_actual['email']) {
                $Sql_verificar_correo = "SELECT id_empleados FROM empleados WHERE email = ?";
                $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
                $Stmt_verificar_correo->bind_param("s", $Correo);
                $Stmt_verificar_correo->execute();
                $Resultado_correo = $Stmt_verificar_correo->get_result();
                $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            }

            if ($Telefono !== $Empleado_actual['telefono']) {
                $Sql_verificar_telefono = "SELECT id_empleados FROM empleados WHERE telefono = ?";
                $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
                $Stmt_verificar_telefono->bind_param("s", $Telefono);
                $Stmt_verificar_telefono->execute();
                $Resultado_telefono = $Stmt_verificar_telefono->get_result();
                $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);
            }

            if ($Error_correo_existe && $Error_telefono_existe) {
                $Error_general = "Ya existe un empleado con este correo y teléfono";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un empleado con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un empleado con este teléfono";
            }
            
            if (empty($Error_general) && empty($Error_nombre_apellido) && empty($Error_telefono) && empty($Error_direccion)) {
                           
                if ($Borrar_archivo && !empty($Ruta_asistencia_db_actual)) {
                    $Ruta_archivo_a_borrar = 'uploads/asistencia/' . $Ruta_asistencia_db_actual;
                    if (file_exists($Ruta_archivo_a_borrar)) {
                        unlink($Ruta_archivo_a_borrar);
                    }
                    $Ruta_asistencia_db = '';
                }
                
                if ($Archivo_asistencia_nuevo && $Archivo_asistencia_nuevo['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/asistencia/';
                    
                    if (!empty($Ruta_asistencia_db_actual) && !$Borrar_archivo) {
                        $Ruta_archivo_a_borrar = 'uploads/asistencia/' . $Ruta_asistencia_db_actual;
                        if (file_exists($Ruta_archivo_a_borrar)) {
                            unlink($Ruta_archivo_a_borrar);
                        }
                    }
    
                    $Nombre_archivo_original = basename($Archivo_asistencia_nuevo['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('asistencia_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;
    
                    if (move_uploaded_file($Archivo_asistencia_nuevo['tmp_name'], $Ruta_destino)) {
                        $Ruta_asistencia_db = $Nombre_archivo_unico;
                    } else {
                        $Error_direccion = "Error al subir el nuevo archivo de asistencia";
                    }
                } elseif ($Archivo_asistencia_nuevo && $Archivo_asistencia_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_asistencia_nuevo['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql_editar = "UPDATE empleados SET nombre = ?, apellido = ?, email = ?, telefono = ?, id_puesto = ?, asistencia = ? WHERE id_empleados = ?";
                    $Stmt_editar = $conn->prepare($Sql_editar);
                    if ($Stmt_editar) {
                        $Stmt_editar->bind_param("ssssisi", $Nombre, $Apellido, $Correo, $Telefono, $Puesto, $Ruta_asistencia_db, $Id_a_editar);
                        $Stmt_editar->execute();
                        $Stmt_editar->close();
                    }
                    $_SESSION['mensaje_exito_1'] = true;
                    header("Location: empleados.php");
                    exit;
                }
            } else {
                $Nombre_a_editar = $Nombre;
                $Apellido_a_editar = $Apellido;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Puesto_a_editar = $Puesto;
                if ($Archivo_asistencia_nuevo && $Archivo_asistencia_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Asistencia_a_editar = basename($Archivo_asistencia_nuevo['name']);
                }
            }
            break;
            
        case 'buscar':
            
            $Nombre_busqueda = $_POST['nombre'] ?? '';
            $Apellido_busqueda = $_POST['apellido'] ?? '';
            $Correo_busqueda = $_POST['correo'] ?? '';
            $Telefono_busqueda = $_POST['telefono'] ?? '';
            $Puesto_busqueda = $_POST['puesto'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_empleados'])) {
    $Id_a_editar = intval($_GET['id_empleados']);
    $Sql_editar = "SELECT * FROM empleados WHERE id_empleados = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Empleado_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Empleado_a_editar) {
            $Nombre_a_editar = $Empleado_a_editar['nombre'];
            $Apellido_a_editar = $Empleado_a_editar['apellido'];
            $Correo_a_editar = $Empleado_a_editar['email'];
            $Telefono_a_editar = $Empleado_a_editar['telefono'];
            $Puesto_a_editar = $Empleado_a_editar['id_puesto']; 
            $Asistencia_a_editar = $Empleado_a_editar['asistencia'];
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
if (!empty($Puesto_busqueda)) {
    $Condiciones[] = "id_puesto = ?";
    $Tipos .= 'i';
    $Parametros[] = $Puesto_busqueda;
    $Es_busqueda = true;
}

if (!empty($Condiciones)) {
    $Sql_buscar = "SELECT * FROM empleados WHERE " . implode(" AND ", $Condiciones);
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Stmt_busqueda->bind_param($Tipos, ...$Parametros);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {
    $Sql = "SELECT * FROM empleados ORDER BY id_empleados ASC";
    $Resultado = $conn->query($Sql);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de empleados</title>
    <link rel="stylesheet" href="empleados.css">
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

                <div class="titulo">Empleados</div>

                <?php if (!empty($Error_general) || !empty($Error_nombre_apellido) || !empty($Error_telefono) || !empty($Error_direccion)): ?>

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

                    <?php if (!empty($Error_telefono)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_telefono; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($Error_direccion)): ?> 
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_direccion; ?></p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <form class="formulario-y-botones" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="action" name="action" value="<?php echo htmlspecialchars($Action_formulario); ?>">
                    <input type="hidden" name="id_empleado_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
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
                            <label>Puesto</label>
                            <select 
                                name="puesto" 
                                id="puesto" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Puesto_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un puesto --</option>
                                
                                <?php foreach ($Puestos_disponibles as $Puesto_datos): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Puesto_datos['id_puesto']) ?>"
                                        <?= (intval($Puesto_datos['id_puesto']) === intval($Puesto_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Puesto_datos['nombre_del_puesto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo"> 
                            <label>Asist / Inasist</label>
                                <input type="file" name="asistencia" id="asistencia" accept=".pdf, .doc, .docx, .xls, .xlsx" class="custom-file-input">
                            <label for="asistencia" class="custom-file-label input-estilo">
                                <span class="file-boton"><img src="img/Archivo.png" class="iconos-principales">Seleccionar archivo</span>
                                <span class="file-nombre" name="file-name" id="file-name">
                                    <?php
                                        if (!empty($Asistencia_a_editar)) {
                                            echo htmlspecialchars($Asistencia_a_editar);
                                        } elseif ($Action_formulario == 'editar' && !empty($Asistencia_a_editar)) {
                                            echo htmlspecialchars($Asistencia_a_editar);
                                        } else {
                                            echo 'Ningún archivo seleccionado';
                                        }
                                    ?>
                                </span>
                            </label>
                            <?php if ($Action_formulario == 'editar' && !empty($Asistencia_a_editar)): ?>
                                <label class="borrar-asistencia">
                                    <input type="checkbox" name="borrar_asistencia" id="borrar_asistencia" value="1"> Eliminar archivo actual
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="empleados.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                         <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaEmpleados.php?";
                            $Parametros = [];
                            if (!empty($Nombre_busqueda)) { $Parametros[] = "nombre=" . urlencode($Nombre_busqueda); }
                            if (!empty($Apellido_busqueda)) { $Parametros[] = "apellido=" . urlencode($Apellido_busqueda); }
                            if (!empty($Correo_busqueda)) { $Parametros[] = "correo=" . urlencode($Correo_busqueda); }
                            if (!empty($Telefono_busqueda)) { $Parametros[] = "telefono=" . urlencode($Telefono_busqueda); }
                            if (!empty($Puesto_busqueda)) { $Parametros[] = "puesto=" . urlencode($Puesto_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="empleados.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarEmpleados()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteEmpleados.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
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
                                <th>Puesto</th>
                                <th>Asist / Inasist</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($Resultado && $Resultado->num_rows > 0) {
                                while ($Fila = $Resultado->fetch_assoc()) {
                                    $nombre_puesto_mostrar = 'N/A';
                                    foreach ($Puestos_disponibles as $Puesto_datos) {
                                        if (intval($Puesto_datos['id_puesto']) === intval($Fila['id_puesto'])) {
                                            $nombre_puesto_mostrar = $Puesto_datos['nombre_del_puesto'];
                                            break;
                                        }
                                    }
                                    echo "<tr>"; 
                                    echo "<td>" . htmlspecialchars($Fila['id_empleados']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['apellido']) . "</td>";
                                    echo "<td><a href='https://mail.google.com/mail/?view=cm&fs=1&to=" . htmlspecialchars($Fila['email']) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/gmail.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['email']) . "</a></td>";
                                    echo "<td><a href='https://wa.me/549" . htmlspecialchars($Fila['telefono']) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/whatsapp.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['telefono']) . "</a></td>";
                                    echo "<td>" . htmlspecialchars($nombre_puesto_mostrar) . "</td>"; 
                                    echo "<td>";
                                    if (!empty($Fila['asistencia'])) {
                                        echo "<a href='uploads/asistencia/" . htmlspecialchars($Fila['asistencia']) . "' target='_blank' class='btn-tabla-action btn-descargar'><img src='img/Descargar.png' class='iconos-secundarios'>Descargar</a>";
                                    } else {
                                        echo "N/A";
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='action' value='modo_de_edicion'>";
                                    echo "<input type='hidden' name='id_empleados' value='" . htmlspecialchars($Fila['id_empleados']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>";
                                    echo "</form>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>";
                                    echo "<input type='hidden' name='action' value='eliminar'>";
                                    echo "<input type='hidden' name='eliminar_id' value='" . htmlspecialchars($Fila['id_empleados']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center;'>No se encontraron empleados</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>

        function BuscarEmpleados() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('nombre').required = false;
            document.getElementById('apellido').required = false;
            document.getElementById('telefono').required = false;
            document.getElementById('correo').required = false;
            document.getElementById('puesto').required = false;
            return true;
            
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('asistencia');
            const fileName = document.getElementById('file-name');
            if (fileInput && fileName) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        fileName.textContent = e.target.files[0].name;
                    } else {
                        fileName.textContent = 'Ningún archivo seleccionado';
                    }
                });
            }
        });

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
                        title: "El empleado ha sido eliminado exitosamente",
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
                title: "Empleado registrado exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Empleado modificado exitosamente",
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