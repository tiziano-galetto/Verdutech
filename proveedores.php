<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();

$Id_a_editar = '';
$Razon_social_a_editar = '';
$Cuit_a_editar = '';
$Nombre_fantasia_a_editar = '';
$Correo_a_editar = '';
$Telefono_a_editar = '';
$Direccion_a_editar = '';
$Precios_a_editar = '';
$Deuda_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Razon_social_busqueda = '';
$Cuit_busqueda = '';
$Nombre_fantasia_busqueda = '';
$Correo_busqueda = '';
$Telefono_busqueda = '';
$Direccion_busqueda = '';
$Deuda_busqueda = '';
$Error_correo = '';
$Error_telefono = '';
$Error_razon_social = '';
$Error_cuit = '';
$Error_nombre_fantasia = '';
$Error_direccion = '';
$Error_general = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Action = $_POST['action'] ?? '';

    switch ($Action) {
        case 'agregar':
            $Razon_social = $_POST['razon_social'] ?? '';
            $Cuit = $_POST['cuit'] ?? '';
            $Nombre_fantasia = $_POST['nombre_fantasia'] ?? '';
            $Correo = $_POST['correo'] ?? '';
            $Telefono = $_POST['telefono'] ?? '';
            $Direccion = $_POST['direccion'] ?? ''; 
            $Deuda = $_POST['deuda'] ?? '';
            $Archivo_precios = $_FILES['precios'] ?? null;
            $Ruta_precios_db = '';

            $Direccion = ucfirst(strtolower($Direccion));

            if (empty(trim($Razon_social))) {
                $Error_razon_social = "La razón social no puede estar vacía";
            } else if (!preg_match("/^[\p{L}\p{P}\p{S}\s]+$/u", $Razon_social)) {
                $Error_razon_social = "La razón social solo puede contener letras, símbolos y espacios";
            }

            if (empty(trim($Nombre_fantasia))) {
                $Error_nombre_fantasia = "El nombre fantasia no puede estar vacía";
            } else if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $Nombre_fantasia)) {
                $Error_nombre_fantasia = "El nombre fantasia solo puede contener letras y espacios";
            }

            if (empty(trim($Direccion))) {
                $Error_direccion = "La dirección no puede estar vacía";
            } else if (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/", $Direccion)) {
                $Error_direccion = "La dirección solo puede contener letras, números y espacios";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            } elseif (strlen($Telefono) !== 10) {
                 $Error_telefono = "El teléfono debe tener exactamente 10 números";
            }

            if (!preg_match("/^\d+$/", $Cuit)) {
                 $Error_cuit = "El cuit solo pueden contener números";
            } elseif (strlen($Cuit) !== 11) {
                 $Error_cuit = "El cuit debe tener exactamente 11 números";
            }

            $Sql_verificar_correo = "SELECT id_proveedores FROM proveedores WHERE email = ?";
            $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
            $Stmt_verificar_correo->bind_param("s", $Correo);
            $Stmt_verificar_correo->execute();
            $Resultado_correo = $Stmt_verificar_correo->get_result();

            $Sql_verificar_telefono = "SELECT id_proveedores FROM proveedores WHERE telefono = ?";
            $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
            $Stmt_verificar_telefono->bind_param("s", $Telefono);
            $Stmt_verificar_telefono->execute();
            $Resultado_telefono = $Stmt_verificar_telefono->get_result();

            $Sql_verificar_cuit = "SELECT id_proveedores FROM proveedores WHERE cuit = ?";
            $Stmt_verificar_cuit = $conn->prepare($Sql_verificar_cuit);
            $Stmt_verificar_cuit->bind_param("s", $Cuit);
            $Stmt_verificar_cuit->execute();
            $Resultado_cuit = $Stmt_verificar_cuit->get_result();

            $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);
            $Error_cuit_existe = ($Resultado_cuit->num_rows > 0);

            if ($Error_correo_existe && $Error_telefono_existe && $Error_cuit_existe) {
                $Error_general = "Ya existe un proveedor con este cuit, correo y teléfono";
            } elseif ($Error_cuit_existe) {
                $Error_general = "Ya existe un proveedor con este cuit";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un proveedor con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un proveedor con este teléfono";
            }

            if (empty($Error_general) && empty($Error_razon_social) && empty($Error_cuit) && empty($Error_nombre_fantasia) && empty($Error_telefono) && empty($Error_direccion)) {
    
                if ($Archivo_precios && $Archivo_precios['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/precios/';
        
                    if (!is_dir($Directorio_subida)) {
                        mkdir($Directorio_subida, 0777, true);
                    }

                    $Nombre_archivo_original = basename($Archivo_precios['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('precios_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;

                    if (move_uploaded_file($Archivo_precios['tmp_name'], $Ruta_destino)) {
                        $Ruta_precios_db = $Nombre_archivo_unico;
                    } else {
                        $Error_direccion = "Error al subir el archivo de precios";
                    }
                } elseif ($Archivo_precios && $Archivo_precios['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_precios['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql = "INSERT INTO proveedores (razon_social, cuit, nombre_fantasia, email, telefono, direccion, deuda, precios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $Stmt = $conn->prepare($Sql);

                    if ($Stmt) {
                        $Stmt->bind_param("ssssssds", $Razon_social, $Cuit, $Nombre_fantasia, $Correo, $Telefono, $Direccion, $Deuda, $Ruta_precios_db); 
                        if ($Stmt->execute()) {
                            $_SESSION['mensaje_exito'] = true;
                            header("Location: proveedores.php");
                            exit;
                        }
                        $Stmt->close();
                    }
                }

            } else {
                $Razon_social_a_editar = $Razon_social;
                $Cuit_a_editar = $Cuit;
                $Nombre_fantasia_a_editar = $Nombre_fantasia;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Direccion_a_editar = $Direccion;
                $Deuda_a_editar = $Deuda;
                if ($Archivo_precios && $Archivo_precios['error'] == UPLOAD_ERR_OK) {
                    $Precios_a_editar = basename($Archivo_precios['name']);
                }
            }
            break;

        case 'eliminar':
            
            $Id_a_eliminar = intval($_POST['eliminar_id']);
            $Sql_seleccionar_archivo = "SELECT precios FROM proveedores WHERE id_proveedores = ?";
            $Stmt_seleccionar_archivo = $conn->prepare($Sql_seleccionar_archivo);
            if ($Stmt_seleccionar_archivo) {
                $Stmt_seleccionar_archivo->bind_param("i", $Id_a_eliminar);
                $Stmt_seleccionar_archivo->execute();
                $Resultado_archivo = $Stmt_seleccionar_archivo->get_result();
                $Fila_archivo = $Resultado_archivo->fetch_assoc();
                $Stmt_seleccionar_archivo->close();
            
                if ($Fila_archivo && !empty($Fila_archivo['precios'])) {
                    $Ruta_archivo = 'uploads/precios/' . $Fila_archivo['precios'];
                    if (file_exists($Ruta_archivo)) {
                        unlink($Ruta_archivo);
                    }
                }
            }

            $Sql_eliminar = "DELETE FROM proveedores WHERE id_proveedores = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: proveedores.php");
            exit;
            break;

        case 'editar':
            
            $Id_a_editar = intval($_POST['id_proveedores_a_editar']);
            $Razon_social = $_POST['razon_social'] ?? '';
            $Cuit = $_POST['cuit'] ?? '';
            $Nombre_fantasia = $_POST['nombre_fantasia'] ?? '';
            $Correo = $_POST['correo'] ?? '';
            $Telefono = $_POST['telefono'] ?? '';
            $Direccion = $_POST['direccion'] ?? ''; 
            $Deuda = $_POST['deuda'] ?? '';
            $Archivo_precios_nuevo = $_FILES['precios'] ?? null;
            $Ruta_precios_db = $_POST['precios_actuales'] ?? ''; 
            $Borrar_archivo = isset($_POST['borrar_precios']); 

            $Direccion = ucfirst(strtolower($Direccion));

            if (empty(trim($Razon_social))) {
                $Error_razon_social = "La razón social no puede estar vacía";
            } else if (!preg_match("/^[\p{L}\p{P}\p{S}\s]+$/u", $Razon_social)) {
                $Error_razon_social = "La razón social solo puede contener letras, símbolos y espacios";
            }

            if (empty(trim($Nombre_fantasia))) {
                $Error_nombre_fantasia = "El nombre fantasia no puede estar vacía";
            } else if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $Nombre_fantasia)) {
                $Error_nombre_fantasia = "El nombre fantasia solo puede contener letras y espacios";
            }

            if (empty(trim($Direccion))) {
                $Error_direccion = "La dirección no puede estar vacía";
            } else if (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/", $Direccion)) {
                $Error_direccion = "La dirección solo puede contener letras, números y espacios";
            }

            if (!preg_match("/^\d+$/", $Telefono)) {
                 $Error_telefono = "El teléfono solo pueden contener números";
            } elseif (strlen($Telefono) !== 10) {
                 $Error_telefono = "El teléfono debe tener exactamente 10 números";
            }

            if (!preg_match("/^\d+$/", $Cuit)) {
                 $Error_cuit = "El cuit solo pueden contener números";
            } elseif (strlen($Cuit) !== 11) {
                 $Error_cuit = "El cuit debe tener exactamente 11 números";
            }
            
            $Sql_proveedor_actual = "SELECT cuit, email, telefono, precios FROM proveedores WHERE id_proveedores = ?";
            $Stmt_proveedor_actual = $conn->prepare($Sql_proveedor_actual);
            $Stmt_proveedor_actual->bind_param("i", $Id_a_editar);
            $Stmt_proveedor_actual->execute();
            $Resultado_actual = $Stmt_proveedor_actual->get_result();
            $Proveedor_actual = $Resultado_actual->fetch_assoc();
            $Stmt_proveedor_actual->close();
            
            $Ruta_precios_db_actual = $Proveedor_actual['precios'];
            $Ruta_precios_db = $Ruta_precios_db_actual;

            $Error_correo_existe = false;
            $Error_telefono_existe = false;
            $Error_cuit_existe = false;

            if ($Correo !== $Proveedor_actual['email']) {
                $Sql_verificar_correo = "SELECT id_proveedores FROM proveedores WHERE email = ?";
                $Stmt_verificar_correo = $conn->prepare($Sql_verificar_correo);
                $Stmt_verificar_correo->bind_param("s", $Correo);
                $Stmt_verificar_correo->execute();
                $Resultado_correo = $Stmt_verificar_correo->get_result();
                $Error_correo_existe = ($Resultado_correo->num_rows > 0);
            }

            if ($Telefono !== $Proveedor_actual['telefono']) {
                $Sql_verificar_telefono = "SELECT id_proveedores FROM proveedores WHERE telefono = ?";
                $Stmt_verificar_telefono = $conn->prepare($Sql_verificar_telefono);
                $Stmt_verificar_telefono->bind_param("s", $Telefono);
                $Stmt_verificar_telefono->execute();
                $Resultado_telefono = $Stmt_verificar_telefono->get_result();
                $Error_telefono_existe = ($Resultado_telefono->num_rows > 0);
            }

            if ($Cuit !== $Proveedor_actual['cuit']) {
                $Sql_verificar_cuit = "SELECT id_proveedores FROM proveedores WHERE cuit = ?";
                $Stmt_verificar_cuit = $conn->prepare($Sql_verificar_cuit);
                $Stmt_verificar_cuit->bind_param("s", $Cuit);
                $Stmt_verificar_cuit->execute();
                $Resultado_cuit = $Stmt_verificar_cuit->get_result();
                $Error_cuit_existe = ($Resultado_cuit->num_rows > 0);
            }

            if ($Error_correo_existe && $Error_telefono_existe && $Error_cuit_existe) {
                $Error_general = "Ya existe un proveedor con este cuit, correo y teléfono";
            } elseif ($Error_cuit_existe) {
                $Error_general = "Ya existe un proveedor con este cuit";
            } elseif ($Error_correo_existe) {
                $Error_general = "Ya existe un proveedor con este correo";
            } elseif ($Error_telefono_existe) {
                $Error_general = "Ya existe un proveedor con este teléfono";
            }
            
            if (empty($Error_general) && empty($Error_razon_social) && empty($Error_cuit) && empty($Error_nombre_fantasia) && empty($Error_telefono) && empty($Error_direccion)) {
                           
                if ($Borrar_archivo && !empty($Ruta_precios_db_actual)) {
                    $Ruta_archivo_a_borrar = 'uploads/precios/' . $Ruta_precios_db_actual;
                    if (file_exists($Ruta_archivo_a_borrar)) {
                        unlink($Ruta_archivo_a_borrar);
                    }
                    $Ruta_precios_db = '';
                }
                
                if ($Archivo_precios_nuevo && $Archivo_precios_nuevo['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/precios/';
                    
                    if (!empty($Ruta_precios_db_actual) && !$Borrar_archivo) {
                        $Ruta_archivo_a_borrar = 'uploads/precios/' . $Ruta_precios_db_actual;
                        if (file_exists($Ruta_archivo_a_borrar)) {
                            unlink($Ruta_archivo_a_borrar);
                        }
                    }
    
                    $Nombre_archivo_original = basename($Archivo_precios_nuevo['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('precios_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;
    
                    if (move_uploaded_file($Archivo_precios_nuevo['tmp_name'], $Ruta_destino)) {
                        $Ruta_precios_db = $Nombre_archivo_unico;
                    } else {
                        $Error_direccion = "Error al subir el nuevo archivo de precios";
                    }
                } elseif ($Archivo_precios_nuevo && $Archivo_precios_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_precios_nuevo['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql_editar = "UPDATE proveedores SET razon_social = ?, cuit = ?, nombre_fantasia = ?, email = ?, telefono = ?, direccion = ?, deuda = ?, precios = ? WHERE id_proveedores = ?";
                    $Stmt_editar = $conn->prepare($Sql_editar);
                    if ($Stmt_editar) {
                        $Stmt_editar->bind_param("ssssssdsi", $Razon_social, $Cuit, $Nombre_fantasia, $Correo, $Telefono, $Direccion, $Deuda, $Ruta_precios_db, $Id_a_editar);
                        $Stmt_editar->execute();
                        $Stmt_editar->close();
                    }
                    $_SESSION['mensaje_exito_1'] = true;
                    header("Location: proveedores.php");
                    exit;
                }
            } else {
                $Razon_social_a_editar = $Razon_social;
                $Cuit_a_editar = $Cuit;
                $Nombre_fantasia_a_editar = $Nombre_fantasia;
                $Correo_a_editar = $Correo;
                $Telefono_a_editar = $Telefono;
                $Direccion_a_editar = $Direccion;
                $Deuda_a_editar = $Deuda;
                if ($Archivo_precios_nuevo && $Archivo_precios_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Precios_a_editar = basename($Archivo_precios_nuevo['name']);
                }
            }
            break;
            
        case 'buscar':
            
            $Razon_social_busqueda = $_POST['razon_social'] ?? '';
            $Cuit_busqueda = $_POST['cuit'] ?? '';
            $Nombre_fantasia_busqueda = $_POST['nombre_fantasia'] ?? '';
            $Correo_busqueda = $_POST['correo'] ?? '';
            $Telefono_busqueda = $_POST['telefono'] ?? '';
            $Direccion_busqueda = $_POST['direccion'] ?? '';
            $Deuda_busqueda = $_POST['deuda'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_proveedores'])) {
    $Id_a_editar = intval($_GET['id_proveedores']);
    $Sql_editar = "SELECT * FROM proveedores WHERE id_proveedores = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Proveedor_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Proveedor_a_editar) {
            $Razon_social_a_editar = $Proveedor_a_editar['razon_social'];
            $Cuit_a_editar = $Proveedor_a_editar['cuit'];
            $Nombre_fantasia_a_editar = $Proveedor_a_editar['nombre_fantasia'];
            $Correo_a_editar = $Proveedor_a_editar['email'];
            $Telefono_a_editar = $Proveedor_a_editar['telefono'];
            $Direccion_a_editar = $Proveedor_a_editar['direccion']; 
            $Precios_a_editar = $Proveedor_a_editar['precios'];
            $Deuda_a_editar = $Proveedor_a_editar['deuda'];
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

if (!empty($Razon_social_busqueda)) {
    $Condiciones[] = "razon_social LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Razon_social_busqueda . '%';
    $Es_busqueda = true;
}
if (!empty($Cuit_busqueda)) {
    $Condiciones[] = "cuit LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Cuit_busqueda . '%';
    $Es_busqueda = true;
}
if (!empty($Nombre_fantasia_busqueda)) {
    $Condiciones[] = "nombre_fantasia LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Nombre_fantasia_busqueda . '%';
    $Es_busqueda = true;
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

$Proveedores_por_pagina = 5;
$Pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$Offset = ($Pagina_actual - 1) * $Proveedores_por_pagina;

if (!empty($Condiciones)) {

    $Sql_count = "SELECT COUNT(*) as total FROM proveedores WHERE " . implode(" AND ", $Condiciones);
    $Stmt_count = $conn->prepare($Sql_count);
    if ($Stmt_count) {
        $Stmt_count->bind_param($Tipos, ...$Parametros);
        $Stmt_count->execute();
        $Total_proveedores = $Stmt_count->get_result()->fetch_assoc()['total'];
        $Stmt_count->close();
    }

    $Sql_buscar = "SELECT * FROM proveedores WHERE " . implode(" AND ", $Condiciones) . " LIMIT ? OFFSET ?";
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Tipos_pag = $Tipos . 'ii';
        $Parametros_pag = array_merge($Parametros, [$Proveedores_por_pagina, $Offset]);
        $Stmt_busqueda->bind_param($Tipos_pag, ...$Parametros_pag);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {

    $Resultado_count = $conn->query("SELECT COUNT(*) as total FROM proveedores");
    $Total_proveedores = $Resultado_count->fetch_assoc()['total'];

    $Sql = "SELECT * FROM proveedores ORDER BY id_proveedores ASC LIMIT ? OFFSET ?";
    $Stmt_pag = $conn->prepare($Sql);
    $Stmt_pag->bind_param("ii", $Proveedores_por_pagina, $Offset);
    $Stmt_pag->execute();
    $Resultado = $Stmt_pag->get_result();
    $Stmt_pag->close();
}

$Total_paginas = ceil($Total_proveedores / $Proveedores_por_pagina);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de proveedores</title>
    <link rel="stylesheet" href="proveedores.css">
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

                <div class="titulo">Proveedores</div>

                <?php if (!empty($Error_general) || !empty($Error_razon_social) || !empty($Error_cuit) || !empty($Error_nombre_fantasia) || !empty($Error_telefono) || !empty($Error_direccion)): ?>

                    <?php if (!empty($Error_razon_social)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_razon_social; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_cuit)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_cuit; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_nombre_fantasia)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_nombre_fantasia; ?></p>
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
                    <input type="hidden" name="id_proveedores_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
                    <div class="contenedor-de-campos">
                        <div class="formulario-grupo">
                            <label>Razón social <span class="requerido">*</span></label>
                            <input type="text" name="razon_social" id="razon_social" value="<?php echo htmlspecialchars($Razon_social_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Cuit <span class="requerido">*</span></label>
                            <input type="number" name="cuit" id="cuit" value="<?php echo htmlspecialchars($Cuit_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Nombre fantasia <span class="requerido">*</span></label>
                            <input type="text" name="nombre_fantasia" id="nombre_fantasia" value="<?php echo htmlspecialchars($Nombre_fantasia_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Correo <span class="requerido">*</span></label>
                            <input type="email" name="correo" id="correo" value="<?php echo htmlspecialchars($Correo_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Teléfono <span class="requerido">*</span></label>
                            <input type="number" name="telefono" id="telefono" value="<?php echo htmlspecialchars($Telefono_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Dirección <span class="requerido">*</span></label>
                            <input type="text" name="direccion" id="direccion" value="<?php echo htmlspecialchars($Direccion_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Deuda a pagar <span class="requerido">*</span></label>
                            <input type="number" name="deuda" id="deuda" step="0.01" value="<?php echo htmlspecialchars($Deuda_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Listado de precios</label>
                                <input type="file" name="precios" id="precios" accept=".pdf, .doc, .docx, .xls, .xlsx" class="custom-file-input">
                            <label for="precios" class="custom-file-label input-estilo">
                                <span class="file-boton"><img src="img/Archivo.png" class="iconos-principales">Seleccionar archivo</span>
                                <span class="file-nombre" name="file-name" id="file-name">
                                    <?php
                                        if (!empty($Precios_a_editar)) {
                                            echo htmlspecialchars($Precios_a_editar);
                                        } elseif ($Action_formulario == 'editar' && !empty($Precios_a_editar)) {
                                            echo htmlspecialchars($Precios_a_editar);
                                        } else {
                                            echo 'Ningún archivo seleccionado';
                                        }
                                    ?>
                                </span>
                            </label>
                            <?php if ($Action_formulario == 'editar' && !empty($Precios_a_editar)): ?>
                                <label class="borrar-precios">
                                    <input type="checkbox" name="borrar_precios" id="borrar_precios" value="1"> Eliminar archivo actual
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="proveedores.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                         <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaProveedores.php?";
                            $Parametros = [];
                            if (!empty($Razon_social_busqueda)) { $Parametros[] = "razon_social=" . urlencode($Razon_social_busqueda); }
                            if (!empty($Cuit_busqueda)) { $Parametros[] = "cuit=" . urlencode($Cuit_busqueda); }
                            if (!empty($Nombre_fantasia_busqueda)) { $Parametros[] = "nombre_fantasia=" . urlencode($Nombre_fantasia_busqueda); }
                            if (!empty($Correo_busqueda)) { $Parametros[] = "correo=" . urlencode($Correo_busqueda); }
                            if (!empty($Telefono_busqueda)) { $Parametros[] = "telefono=" . urlencode($Telefono_busqueda); }
                            if (!empty($Direccion_busqueda)) { $Parametros[] = "direccion=" . urlencode($Direccion_busqueda); }
                            if (is_numeric($Deuda_busqueda)) { $Parametros[] = "deuda=" . urlencode($Deuda_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="proveedores.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarProveedores()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteProveedores.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="tabla-contenedor">
                    <table class="datos-tabla" border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Razón social</th>
                                <th>Cuit</th>
                                <th>Nombre fantasia</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Deuda a pagar</th>
                                <th>Listado de precios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($Resultado && $Resultado->num_rows > 0) {
                                while ($Fila = $Resultado->fetch_assoc()) {
                                    $Deuda = floatval($Fila['deuda']);
                                    $Clase_deuda = '';
                                    if ($Deuda > 500000) {
                                        $Clase_deuda = 'deuda-alta';
                                    } elseif ($Deuda > 250000) {
                                        $Clase_deuda = 'deuda-media';
                                    } else {
                                        $Clase_deuda = 'deuda-baja';
                                    }
                                    echo "<tr>"; 
                                    echo "<td>" . htmlspecialchars($Fila['id_proveedores']) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['razon_social']) . "</td>";
                                    $Cuit_original = $Fila['cuit'];
                                    if (strlen($Cuit_original) === 11) {
                                        $Cuit_formateado = substr($Cuit_original, 0, 2) . '-' . substr($Cuit_original, 2, 8) . '-' . substr($Cuit_original, 10, 1);
                                    } else {
                                        $Cuit_formateado = $Cuit_original;
                                    }
                                    echo "<td>" . htmlspecialchars($Cuit_formateado) . "</td>";
                                    echo "<td>" . htmlspecialchars($Fila['nombre_fantasia']) . "</td>";
                                    echo "<td><a href='https://mail.google.com/mail/?view=cm&fs=1&to=" . htmlspecialchars($Fila['email']) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/gmail.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['email']) . "</a></td>";
                                    $Telefono_original = $Fila['telefono'];
                                    if (strlen($Telefono_original) === 10) {
                                        $Cod_area = substr($Telefono_original, 0, 3);
                                        $Primera_parte = substr($Telefono_original, 3, 3);
                                        $Segunda_parte = substr($Telefono_original, 6, 4);
                                        $Telefono_formateado = "+54 9 " . $Cod_area . " " . $Primera_parte . "-" . $Segunda_parte;
                                    } else {
                                        $Telefono_formateado = $Telefono_original; 
                                    }
                                    echo "<td><a href='https://wa.me/549" . htmlspecialchars($Telefono_original) . "'target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/whatsapp.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Telefono_formateado) . "</a></td>";
                                    echo "<td><a href='https://www.google.com/maps/search/?api=1&query=" . urlencode($Fila['direccion']) . "' target='_blank' style='color: #000000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;'><img src='img/marcador.png' style='width: 15px; height: 15px;'>" . htmlspecialchars($Fila['direccion']) . "</a></td>";
                                    $Deuda_formateada = number_format(floatval($Fila['deuda']), 2, ',', '.');
                                    echo "<td><span class='" . $Clase_deuda . "'>$ " . $Deuda_formateada . "</span></td>";
                                    echo "<td>";
                                    if (!empty($Fila['precios'])) {
                                        echo "<a href='uploads/precios/" . htmlspecialchars($Fila['precios']) . "' target='_blank' class='btn-tabla-action btn-descargar'><img src='img/Descargar.png' class='iconos-secundarios'>Descargar</a>";
                                    } else {
                                        echo "N/A";
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='action' value='modo_de_edicion'>";
                                    echo "<input type='hidden' name='id_proveedores' value='" . htmlspecialchars($Fila['id_proveedores']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>";
                                    echo "</form>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>";
                                    echo "<input type='hidden' name='action' value='eliminar'>";
                                    echo "<input type='hidden' name='eliminar_id' value='" . htmlspecialchars($Fila['id_proveedores']) . "'>";
                                    echo "<button type='submit' class='btn-tabla-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' style='text-align:center;'>No se encontraron proveedores</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($Total_paginas > 1): ?>
                <div class="paginador-contenedor">
                    <?php
                    $Params_paginador = [];
                    if (!empty($Razon_social_busqueda)) $Params_paginador[] = "razon_social="    . urlencode($Razon_social_busqueda);
                    if (!empty($Cuit_busqueda)) $Params_paginador[] = "cuit="  . urlencode($Cuit_busqueda);
                    if (!empty($Nombre_fantasia_busqueda)) $Params_paginador[] = "nombre_fantasia="    . urlencode($Nombre_fantasia_busqueda);
                    if (!empty($Correo_busqueda))    $Params_paginador[] = "correo="    . urlencode($Correo_busqueda);
                    if (!empty($Telefono_busqueda))  $Params_paginador[] = "telefono="  . urlencode($Telefono_busqueda);
                    if (!empty($Direccion_busqueda)) $Params_paginador[] = "direccion=" . urlencode($Direccion_busqueda);
                    if (is_numeric($Deuda_busqueda)) $Params_paginador[] = "deuda="     . urlencode($Deuda_busqueda);
                    $Query_base = count($Params_paginador) ? '&' . implode('&', $Params_paginador) : '';

                    if ($Pagina_actual > 1): ?>
                        <a href="proveedores.php?pagina=<?php echo $Pagina_actual - 1 . $Query_base; ?>" class="btn-paginador flecha">⟪ Anterior</a>
                    <?php endif;

                    for ($i = 1; $i <= $Total_paginas; $i++): ?>
                        <a href="proveedores.php?pagina=<?php echo $i . $Query_base; ?>"
                           class="btn-paginador <?php echo ($i === $Pagina_actual) ? 'activo' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;

                    if ($Pagina_actual < $Total_paginas): ?>
                        <a href="proveedores.php?pagina=<?php echo $Pagina_actual + 1 . $Query_base; ?>" class="btn-paginador flecha">Siguiente ⟫</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>

        function BuscarProveedores() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('razon_social').required = false;
            document.getElementById('cuit').required = false;
            document.getElementById('nombre_fantasia').required = false;
            document.getElementById('telefono').required = false;
            document.getElementById('correo').required = false;
            document.getElementById('direccion').required = false;
            document.getElementById('deuda').required = false;
            return true;
            
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('precios');
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
                        title: "El proveedor ha sido eliminado exitosamente",
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
                title: "Proveedor registrado exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Proveedor modificado exitosamente",
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