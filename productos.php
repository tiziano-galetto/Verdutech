<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();

$Sql_tipo_productos = "SELECT id_tipo_productos, nombre_tipo_productos FROM tipo_productos ORDER BY id_tipo_productos ASC";
$Resultado_tipo_productos = $conn->query($Sql_tipo_productos);

$Tipo_productos_disponibles = [];
if ($Resultado_tipo_productos && $Resultado_tipo_productos->num_rows > 0) {
    while ($Fila_tipo_productos = $Resultado_tipo_productos->fetch_assoc()) {
        $Tipo_productos_disponibles[] = $Fila_tipo_productos;
    }
}

$Sql_proveedores = "SELECT id_proveedores, nombre_fantasia FROM proveedores ORDER BY id_proveedores ASC";
$Resultado_proveedores = $conn->query($Sql_proveedores);

$Proveedores_disponibles = [];
if ($Resultado_proveedores && $Resultado_proveedores->num_rows > 0) {
    while ($Fila_proveedores = $Resultado_proveedores->fetch_assoc()) {
        $Proveedores_disponibles[] = $Fila_proveedores;
    }
}

$Sql_tipo_unidades = "SELECT id_tipo_unidades, nombre_tipo_unidades FROM tipo_unidades ORDER BY id_tipo_unidades ASC";
$Resultado_tipo_unidades = $conn->query($Sql_tipo_unidades);

$Tipo_unidades_disponibles = [];
if ($Resultado_tipo_unidades && $Resultado_tipo_unidades->num_rows > 0) {
    while ($Fila_tipo_unidades = $Resultado_tipo_unidades->fetch_assoc()) {
        $Tipo_unidades_disponibles[] = $Fila_tipo_unidades;
    }
}

$Id_a_editar = '';
$Nombre_a_editar = '';
$Tipo_productos_a_editar = '';
$Tipo_unidades_a_editar = '';
$Proveedores_a_editar = '';
$Precio_a_editar = '';
$Imagenes_a_editar = '';
$Action_formulario = 'agregar';
$Submit_btn_texto = 'Agregar';
$Nombre_busqueda = '';
$Tipo_productos_busqueda = '';
$Tipo_unidades_busqueda = '';
$Proveedores_busqueda = '';
$Precio_busqueda = '';
$Error_nombre = '';
$Error_precio = '';
$Error_general = '';
$Error_direccion = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Action = $_POST['action'] ?? '';

    switch ($Action) {
        case 'agregar':
            $Nombre = $_POST['nombre_del_producto'] ?? '';
            $Tipo_productos = $_POST['id_tipo_productos'] ?? '';
            $Tipo_unidades = $_POST['id_tipo_unidades'] ?? '';
            $Proveedores = $_POST['id_proveedores'] ?? '';
            $Precio = $_POST['precio'] ?? ''; 
            $Archivo_imagenes = $_FILES['img_productos'] ?? null;
            $Ruta_imagenes_db = '';

            $Nombre = ucfirst(strtolower($Nombre));

            $Sql_verificar_nombre = "SELECT id_productos FROM productos WHERE nombre_del_producto = ?";
            $Stmt_verificar_nombre = $conn->prepare($Sql_verificar_nombre);
            $Stmt_verificar_nombre->bind_param("s", $Nombre);
            $Stmt_verificar_nombre->execute();
            $Resultado_nombre = $Stmt_verificar_nombre->get_result();

            $Error_nombre_existe = ($Resultado_nombre->num_rows > 0);

            if (empty(trim($Nombre))) {
                $Error_nombre = "El nombre no puede estar vacío";
            } else if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $Nombre)) {
                $Error_nombre = "El nombre solo puede contener letras y espacios";
            } else if ($Error_nombre_existe) {
                $Error_nombre = "Ya existe un producto con este nombre";
            }

            if (empty($Tipo_productos) || !is_numeric($Tipo_productos)) {
                 $Error_general = "Debe seleccionar un tipo de producto válido";
            } else {
                 $Sql_verificar_tipo_productos = "SELECT id_tipo_productos FROM tipo_productos WHERE id_tipo_productos = ?";
                 $Stmt_verificar_tipo_productos = $conn->prepare($Sql_verificar_tipo_productos);
                 $Stmt_verificar_tipo_productos->bind_param("i", $Tipo_productos);
                 $Stmt_verificar_tipo_productos->execute();
                 $Resultado_tipo_productos = $Stmt_verificar_tipo_productos->get_result();
                 if ($Resultado_tipo_productos->num_rows === 0) {
                     $Error_general = "El tipo de producto seleccionado no es válido";
                 }
                 $Stmt_verificar_tipo_productos->close();
            }

            if (empty($Tipo_unidades) || !is_numeric($Tipo_unidades)) {
                 $Error_general = "Debe seleccionar un tipo de unidad válido";
            } else {
                 $Sql_verificar_tipo_unidades = "SELECT id_tipo_unidades FROM tipo_unidades WHERE id_tipo_unidades = ?";
                 $Stmt_verificar_tipo_unidades = $conn->prepare($Sql_verificar_tipo_unidades);
                 $Stmt_verificar_tipo_unidades->bind_param("i", $Tipo_unidades);
                 $Stmt_verificar_tipo_unidades->execute();
                 $Resultado_tipo_unidades = $Stmt_verificar_tipo_unidades->get_result();
                 if ($Resultado_tipo_unidades->num_rows === 0) {
                     $Error_general = "El tipo de unidad seleccionado no es válido";
                 }
                 $Stmt_verificar_tipo_unidades->close();
            }

            if (empty($Proveedores) || !is_numeric($Proveedores)) {
                 $Error_general = "Debe seleccionar un proveedor válido";
            } else {
                 $Sql_verificar_proveedores = "SELECT id_proveedores FROM proveedores WHERE id_proveedores = ?";
                 $Stmt_verificar_proveedores = $conn->prepare($Sql_verificar_proveedores);
                 $Stmt_verificar_proveedores->bind_param("i", $Proveedores);
                 $Stmt_verificar_proveedores->execute();
                 $Resultado_proveedores = $Stmt_verificar_proveedores->get_result();
                 if ($Resultado_proveedores->num_rows === 0) {
                     $Error_general = "El proveedor seleccionado no es válido";
                 }
                 $Stmt_verificar_proveedores->close();
            }

            if (empty($Error_general) && empty($Error_nombre) && empty($Error_precio)) {
    
                if ($Archivo_imagenes && $Archivo_imagenes['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/imagenes/';
        
                    if (!is_dir($Directorio_subida)) {
                        mkdir($Directorio_subida, 0777, true);
                    }

                    $Nombre_archivo_original = basename($Archivo_imagenes['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('productos_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;

                    if (move_uploaded_file($Archivo_imagenes['tmp_name'], $Ruta_destino)) {
                        $Ruta_imagenes_db = $Ruta_destino;
                    } else {
                        $Error_direccion = "Error al subir el archivo de imagenes";
                    }
                } elseif ($Archivo_imagenes && $Archivo_imagenes['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_imagenes['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql = "INSERT INTO productos (nombre_del_producto, id_tipo_productos, id_proveedores, precio, id_tipo_unidades, img_productos) VALUES (?, ?, ?, ?, ?, ?)";
                    $Stmt = $conn->prepare($Sql);

                    if ($Stmt) {
                        $Stmt->bind_param("siidis", $Nombre, $Tipo_productos, $Proveedores, $Precio, $Tipo_unidades, $Ruta_imagenes_db); 
                        if ($Stmt->execute()) {
                            $_SESSION['mensaje_exito'] = true;
                            header("Location: productos.php");
                            exit;
                        }
                        $Stmt->close();
                    }
                }

            } else {
                $Nombre_a_editar = $Nombre;
                $Tipo_productos_a_editar = $Tipo_productos;
                $Tipo_unidades_a_editar = $Tipo_unidades;
                $Proveedores_a_editar = $Proveedores;
                $Precio_a_editar = $Precio;
                if ($Archivo_imagenes && $Archivo_imagenes['error'] == UPLOAD_ERR_OK) {
                    $Imagenes_a_editar = basename($Archivo_imagenes['name']);
                }
            }
            break;

        case 'eliminar':
            
            $Id_a_eliminar = intval($_POST['eliminar_id']);
            $Sql_seleccionar_archivo = "SELECT img_productos FROM productos WHERE id_productos = ?";
            $Stmt_seleccionar_archivo = $conn->prepare($Sql_seleccionar_archivo);
            if ($Stmt_seleccionar_archivo) {
                $Stmt_seleccionar_archivo->bind_param("i", $Id_a_eliminar);
                $Stmt_seleccionar_archivo->execute();
                $Resultado_archivo = $Stmt_seleccionar_archivo->get_result();
                $Fila_archivo = $Resultado_archivo->fetch_assoc();
                $Stmt_seleccionar_archivo->close();
            
                if ($Fila_archivo && !empty($Fila_archivo['img_productos'])) {
                    $Ruta_archivo = 'uploads/imagenes/' . $Fila_archivo['img_productos'];
                    if (file_exists($Ruta_archivo)) {
                        unlink($Ruta_archivo);
                    }
                }
            }

            $Sql_eliminar = "DELETE FROM productos WHERE id_productos = ?";
            $Stmt_eliminar = $conn->prepare($Sql_eliminar);
            if ($Stmt_eliminar) {
                $Stmt_eliminar->bind_param("i", $Id_a_eliminar);
                $Stmt_eliminar->execute();
                $Stmt_eliminar->close();
            }
            header("Location: productos.php");
            exit;
            break;

        case 'editar':
            
            $Id_a_editar = intval($_POST['id_productos_a_editar']);
            $Nombre = $_POST['nombre_del_producto'] ?? '';
            $Tipo_productos = $_POST['id_tipo_productos'] ?? '';
            $Tipo_unidades = $_POST['id_tipo_unidades'] ?? '';
            $Proveedores = $_POST['id_proveedores'] ?? '';
            $Precio = $_POST['precio'] ?? ''; 
            $Archivo_imagenes_nuevo = $_FILES['img_productos'] ?? null;
            $Ruta_imagenes_db = $_POST['img_productos_actual'] ?? ''; 
            $Borrar_archivo = isset($_POST['borrar_img_productos']); 

            $Nombre = ucfirst(strtolower($Nombre));

            $Sql_verificar_nombre = "SELECT id_productos FROM productos WHERE nombre_del_producto = ?";
            $Stmt_verificar_nombre = $conn->prepare($Sql_verificar_nombre);
            $Stmt_verificar_nombre->bind_param("s", $Nombre);
            $Stmt_verificar_nombre->execute();
            $Resultado_nombre = $Stmt_verificar_nombre->get_result();

            $Error_nombre_existe = ($Resultado_nombre->num_rows > 0);

            if (empty(trim($Nombre))) {
                $Error_nombre = "El nombre no puede estar vacío";
            } else if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $Nombre)) {
                $Error_nombre = "El nombre solo puede contener letras y espacios";
            } else if ($Error_nombre_existe) {
                $Error_nombre = "Ya existe un producto con este nombre";
            }
 
            $Sql_productos_actual = "SELECT img_productos FROM productos WHERE id_productos = ?";
            $Stmt_productos_actual = $conn->prepare($Sql_productos_actual);
            $Stmt_productos_actual->bind_param("i", $Id_a_editar);
            $Stmt_productos_actual->execute();
            $Resultado_actual = $Stmt_productos_actual->get_result();
            $Productos_actual = $Resultado_actual->fetch_assoc();
            $Stmt_productos_actual->close();
            
            $Ruta_imagenes_db_actual = $Productos_actual['img_productos'];
            $Ruta_imagenes_db = $Ruta_imagenes_db_actual;

            if (empty($Tipo_productos) || !is_numeric($Tipo_productos)) {
                 $Error_general = "Debe seleccionar un tipo de producto válido";
            } else {
                 $Sql_verificar_tipo_productos = "SELECT id_tipo_productos FROM tipo_productos WHERE id_tipo_productos = ?";
                 $Stmt_verificar_tipo_productos = $conn->prepare($Sql_verificar_tipo_productos);
                 $Stmt_verificar_tipo_productos->bind_param("i", $Tipo_productos);
                 $Stmt_verificar_tipo_productos->execute();
                 $Resultado_tipo_productos = $Stmt_verificar_tipo_productos->get_result();
                 if ($Resultado_tipo_productos->num_rows === 0) {
                     $Error_general = "El tipo de producto seleccionado no es válido";
                 }
                 $Stmt_verificar_tipo_productos->close();
            }

            if (empty($Tipo_unidades) || !is_numeric($Tipo_unidades)) {
                 $Error_general = "Debe seleccionar un tipo de unidad válido";
            } else {
                 $Sql_verificar_tipo_unidades = "SELECT id_tipo_unidades FROM tipo_unidades WHERE id_tipo_unidades = ?";
                 $Stmt_verificar_tipo_unidades = $conn->prepare($Sql_verificar_tipo_unidades);
                 $Stmt_verificar_tipo_unidades->bind_param("i", $Tipo_unidades);
                 $Stmt_verificar_tipo_unidades->execute();
                 $Resultado_tipo_unidades = $Stmt_verificar_tipo_unidades->get_result();
                 if ($Resultado_tipo_unidades->num_rows === 0) {
                     $Error_general = "El tipo de unidad seleccionado no es válido";
                 }
                 $Stmt_verificar_tipo_unidades->close();
            }

            if (empty($Proveedores) || !is_numeric($Proveedores)) {
                 $Error_general = "Debe seleccionar un proveedor válido";
            } else {
                 $Sql_verificar_proveedores = "SELECT id_proveedores FROM proveedores WHERE id_proveedores = ?";
                 $Stmt_verificar_proveedores = $conn->prepare($Sql_verificar_proveedores);
                 $Stmt_verificar_proveedores->bind_param("i", $Proveedores);
                 $Stmt_verificar_proveedores->execute();
                 $Resultado_proveedores = $Stmt_verificar_proveedores->get_result();
                 if ($Resultado_proveedores->num_rows === 0) {
                     $Error_general = "El proveedor seleccionado no es válido";
                 }
                 $Stmt_verificar_proveedores->close();
            }

            if (empty($Error_general) && empty($Error_nombre) && empty($Error_precio)) {
                           
                if ($Borrar_archivo && !empty($Ruta_imagenes_db_actual)) {
                    $Ruta_archivo_a_borrar = 'uploads/imagenes/' . $Ruta_imagenes_db_actual;
                    if (file_exists($Ruta_archivo_a_borrar)) {
                        unlink($Ruta_archivo_a_borrar);
                    }
                    $Ruta_imagenes_db = '';
                }
                
                if ($Archivo_imagenes_nuevo && $Archivo_imagenes_nuevo['error'] == UPLOAD_ERR_OK) {
                    $Directorio_subida = 'uploads/imagenes/';
                    
                    if (!empty($Ruta_imagenes_db_actual) && !$Borrar_archivo) {
                        $Ruta_archivo_a_borrar = 'uploads/imagenes/' . $Ruta_imagenes_db_actual;
                        if (file_exists($Ruta_archivo_a_borrar)) {
                            unlink($Ruta_archivo_a_borrar);
                        }
                    }
    
                    $Nombre_archivo_original = basename($Archivo_imagenes_nuevo['name']);
                    $Extension_archivo = pathinfo($Nombre_archivo_original, PATHINFO_EXTENSION);
                    $Nombre_archivo_unico = uniqid('productos_', true) . '.' . $Extension_archivo;
                    $Ruta_destino = $Directorio_subida . $Nombre_archivo_unico;
    
                    if (move_uploaded_file($Archivo_imagenes_nuevo['tmp_name'], $Ruta_destino)) {
                        $Ruta_imagenes_db = $Ruta_destino;
                    } else {
                        $Error_direccion = "Error al subir el nuevo archivo de imagenes";
                    }
                } elseif ($Archivo_imagenes_nuevo && $Archivo_imagenes_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Error_direccion = "Error de subida: Código " . $Archivo_imagenes_nuevo['error'];
                }

                if (empty($Error_direccion)) {
                    $Sql_editar = "UPDATE productos SET nombre_del_producto = ?, id_tipo_productos = ?, id_proveedores = ?, precio = ?, id_tipo_unidades = ?, img_productos = ? WHERE id_productos = ?";
                    $Stmt_editar = $conn->prepare($Sql_editar);
                    if ($Stmt_editar) {
                        $Stmt_editar->bind_param("siidisi", $Nombre, $Tipo_productos, $Proveedores, $Precio, $Tipo_unidades, $Ruta_imagenes_db, $Id_a_editar);
                        $Stmt_editar->execute();
                        $Stmt_editar->close();
                    }
                    $_SESSION['mensaje_exito_1'] = true;
                    header("Location: productos.php");
                    exit;
                }
            } else {
                $Nombre_a_editar = $Nombre;
                $Tipo_productos_a_editar = $Tipo_productos;
                $Tipo_unidades_a_editar = $Tipo_unidades;
                $Proveedores_a_editar = $Proveedores;
                $Precio_a_editar = $Precio;
                if ($Archivo_imagenes_nuevo && $Archivo_imagenes_nuevo['error'] != UPLOAD_ERR_NO_FILE) {
                    $Imagenes_a_editar = basename($Archivo_imagenes_nuevo['name']);
                }
            }
            break;
            
        case 'buscar':
            
            $Nombre_busqueda = $_POST['nombre_del_producto'] ?? '';
            $Tipo_productos_busqueda = $_POST['id_tipo_productos'] ?? '';
            $Tipo_unidades_busqueda = $_POST['id_tipo_unidades'] ?? '';
            $Proveedores_busqueda = $_POST['id_proveedores'] ?? '';
            $Precio_busqueda = $_POST['precio'] ?? '';
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'modo_de_edicion' && isset($_GET['id_productos'])) {
    $Id_a_editar = intval($_GET['id_productos']);
    $Sql_editar = "SELECT * FROM productos WHERE id_productos = ?";
    $Stmt_editar = $conn->prepare($Sql_editar);

    if ($Stmt_editar) {
        $Stmt_editar->bind_param("i", $Id_a_editar);
        $Stmt_editar->execute();
        $Resultado_editar = $Stmt_editar->get_result();
        $Productos_a_editar = $Resultado_editar->fetch_assoc();
        $Stmt_editar->close();

        if ($Productos_a_editar) {
            $Nombre_a_editar = $Productos_a_editar['nombre_del_producto'];
            $Tipo_productos_a_editar = $Productos_a_editar['id_tipo_productos'];
            $Tipo_unidades_a_editar = $Productos_a_editar['id_tipo_unidades'];
            $Proveedores_a_editar = $Productos_a_editar['id_proveedores'];
            $Precio_a_editar = $Productos_a_editar['precio'];
            $Imagenes_a_editar = $Productos_a_editar['img_productos'];
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

if (!empty($Nombre_busqueda)) {
    $Condiciones[] = "nombre_del_producto LIKE ?";
    $Tipos .= 's';
    $Parametros[] = '%' . $Nombre_busqueda . '%';
    $Es_busqueda = true;
}
if (!empty($Tipo_productos_busqueda)) {
    $Condiciones[] = "id_tipo_productos = ?";
    $Tipos .= 'i';
    $Parametros[] = $Tipo_productos_busqueda;
    $Es_busqueda = true;
}
if (!empty($Tipo_unidades_busqueda)) {
    $Condiciones[] = "id_tipo_unidades = ?";
    $Tipos .= 'i';
    $Parametros[] = $Tipo_unidades_busqueda;
    $Es_busqueda = true;
}
if (!empty($Proveedores_busqueda)) {
    $Condiciones[] = "id_proveedores = ?";
    $Tipos .= 'i';
    $Parametros[] = $Proveedores_busqueda;
    $Es_busqueda = true;
}
if (is_numeric($Precio_busqueda)) {
    $Condiciones[] = "precio = ?";
    $Tipos .= 'd';
    $Parametros[] = floatval($Precio_busqueda);
    $Es_busqueda = true;
}

$Productos_por_pagina = 12;
$Pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$Offset = ($Pagina_actual - 1) * $Productos_por_pagina;

if (!empty($Condiciones)) {

    $Sql_count = "SELECT COUNT(*) as total FROM productos WHERE " . implode(" AND ", $Condiciones);
    $Stmt_count = $conn->prepare($Sql_count);
    if ($Stmt_count) {
        $Stmt_count->bind_param($Tipos, ...$Parametros);
        $Stmt_count->execute();
        $Total_productos = $Stmt_count->get_result()->fetch_assoc()['total'];
        $Stmt_count->close();
    }

    $Sql_buscar = "SELECT * FROM productos WHERE " . implode(" AND ", $Condiciones) . " LIMIT ? OFFSET ?";
    $Stmt_busqueda = $conn->prepare($Sql_buscar);
    if ($Stmt_busqueda) {
        $Tipos_pag = $Tipos . 'ii';
        $Parametros_pag = array_merge($Parametros, [$Productos_por_pagina, $Offset]);
        $Stmt_busqueda->bind_param($Tipos_pag, ...$Parametros_pag);
        $Stmt_busqueda->execute();
        $Resultado = $Stmt_busqueda->get_result();
        $Stmt_busqueda->close();
    }
} else {

    $Resultado_count = $conn->query("SELECT COUNT(*) as total FROM productos");
    $Total_productos = $Resultado_count->fetch_assoc()['total'];

    $Sql = "SELECT * FROM productos ORDER BY id_productos ASC LIMIT ? OFFSET ?";
    $Stmt_pag = $conn->prepare($Sql);
    $Stmt_pag->bind_param("ii", $Productos_por_pagina, $Offset);
    $Stmt_pag->execute();
    $Resultado = $Stmt_pag->get_result();
    $Stmt_pag->close();
}

$Total_paginas = ceil($Total_productos / $Productos_por_pagina);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de productos</title>
    <link rel="stylesheet" href="productos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
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

                <div class="titulo">Productos</div>

                <?php if (!empty($Error_general) || !empty($Error_nombre) || !empty($Error_precio)): ?>

                    <?php if (!empty($Error_nombre)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_nombre; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_general)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_general; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($Error_precio)): ?>
                        <div class="mensaje_de_error">
                            <p><?php echo $Error_precio; ?></p>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>

                <form class="formulario-y-botones" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="action" name="action" value="<?php echo htmlspecialchars($Action_formulario); ?>">
                    <input type="hidden" name="id_productos_a_editar" value="<?php echo htmlspecialchars($Id_a_editar); ?>">
                    
                    <div class="contenedor-de-campos">
                        <div class="formulario-grupo"> 
                            <label>Imagen</label>
                                <input type="file" name="img_productos" id="img_productos" accept=".jpg, .jpeg, .png, .webp, .gif" class="custom-file-input">
                            <label for="img_productos" class="custom-file-label input-estilo">
                                <span class="file-boton"><img src="img/Archivo.png" class="iconos-principales">Seleccionar archivo</span>
                                <span class="file-nombre" name="file-name" id="file-name">
                                    <?php
                                        if (!empty($Imagenes_a_editar)) {
                                            echo htmlspecialchars($Imagenes_a_editar);
                                        } elseif ($Action_formulario == 'editar' && !empty($Imagenes_a_editar)) {
                                            echo htmlspecialchars($Imagenes_a_editar);
                                        } else {
                                            echo 'Ningún archivo seleccionado';
                                        }
                                    ?>
                                </span>
                            </label>
                            <?php if ($Action_formulario == 'editar' && !empty($Imagenes_a_editar)): ?>
                                <label class="borrar-img-productos">
                                    <input type="checkbox" name="borrar_img_productos" id="borrar_img_productos" value="1"> Eliminar archivo actual
                                </label>
                            <?php endif; ?>
                        </div>
                        <div class="formulario-grupo">
                            <label>Nombre <span class="requerido">*</span></label>
                            <input type="text" name="nombre_del_producto" id="nombre_del_producto" value="<?php echo htmlspecialchars($Nombre_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Tipo de producto <span class="requerido">*</span></label>
                            <select 
                                name="id_tipo_productos" 
                                id="id_tipo_productos" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Tipo_productos_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un tipo de producto --</option>
                                
                                <?php foreach ($Tipo_productos_disponibles as $Tipo_productos_datos): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Tipo_productos_datos['id_tipo_productos']) ?>"
                                        <?= (intval($Tipo_productos_datos['id_tipo_productos']) === intval($Tipo_productos_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Tipo_productos_datos['nombre_tipo_productos']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Proveedor <span class="requerido">*</span></label>
                            <select 
                                name="id_proveedores" 
                                id="id_proveedores" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Proveedores_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un proveedor --</option>
                                
                                <?php foreach ($Proveedores_disponibles as $Proveedores_datos): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Proveedores_datos['id_proveedores']) ?>"
                                        <?= (intval($Proveedores_datos['id_proveedores']) === intval($Proveedores_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Proveedores_datos['nombre_fantasia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="formulario-grupo">
                            <label>Precio <span class="requerido">*</span></label>
                            <input type="number" name="precio" id="precio" value="<?php echo htmlspecialchars($Precio_a_editar); ?>" required>
                        </div>
                        <div class="formulario-grupo">
                            <label>Tipo de unidad <span class="requerido">*</span></label>
                            <select 
                                name="id_tipo_unidades" 
                                id="id_tipo_unidades" 
                                required 
                                class="input-estilo" 
                            >
                                <option value="" disabled <?php echo empty($Tipo_unidades_a_editar) ? 'selected' : ''; ?>>-- Seleccionar un tipo de unidad --</option>
                                
                                <?php foreach ($Tipo_unidades_disponibles as $Tipo_unidades_datos): ?>
                                    <option 
                                        value="<?= htmlspecialchars($Tipo_unidades_datos['id_tipo_unidades']) ?>"
                                        <?= (intval($Tipo_unidades_datos['id_tipo_unidades']) === intval($Tipo_unidades_a_editar)) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($Tipo_unidades_datos['nombre_tipo_unidades']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="action-botones-contenedor">
                        <?php if ($Action_formulario == 'editar'): ?>
                            <button type="submit" class="btn-action"><img src="img/Aceptar.png" class="iconos-principales">Guardar cambios</button>
                            <a href="productos.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar edición</a>
                         <?php elseif ($Es_busqueda):
                            $Url_busqueda = "fpdf/ReporteBusquedaProductos.php?";
                            $Parametros = [];
                            if (!empty($Nombre_busqueda)) { $Parametros[] = "nombre_del_producto=" . urlencode($Nombre_busqueda); }
                            if (!empty($Tipo_productos_busqueda)) { $Parametros[] = "id_tipo_productos=" . urlencode($Tipo_productos_busqueda); }
                            if (!empty($Tipo_unidades_busqueda)) { $Parametros[] = "id_tipo_unidades=" . urlencode($Tipo_unidades_busqueda); }
                            if (!empty($Proveedores_busqueda)) { $Parametros[] = "id_proveedores=" . urlencode($Proveedores_busqueda); }
                            if (is_numeric($Precio_busqueda)) { $Parametros[] = "precio=" . urlencode($Precio_busqueda); }
                            $Url_busqueda .= implode('&', $Parametros);
                        ?>
                            <a href="productos.php" class="btn-action" style="text-decoration: none"><img src="img/Cancelar.png" class="iconos-principales">Cancelar búsqueda</a>
                            <a href="<?php echo htmlspecialchars($Url_busqueda); ?>" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php else: ?>
                            <button type="submit" class="btn-action"><img src="img/Agregar.png" class="iconos-principales">Agregar</button>
                            <button type="submit" class="btn-action" style="text-decoration: none" onclick="return BuscarProductos()"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                            <a href="fpdf/ReporteProductos.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="card-contenedor">
                    <?php while ($fila = $Resultado->fetch_assoc()): ?>
                        <?php
                            $nombre_tipo_productos_mostrar = 'N/A';
                            foreach ($Tipo_productos_disponibles as $Tipo_productos_datos) {
                                if (intval($Tipo_productos_datos['id_tipo_productos']) === intval($fila['id_tipo_productos'])) {
                                    $nombre_tipo_productos_mostrar = $Tipo_productos_datos['nombre_tipo_productos'];
                                    break;
                                }
                            }

                            $nombre_proveedores_mostrar = 'N/A';
                            foreach ($Proveedores_disponibles as $Proveedores_datos) {
                                if (intval($Proveedores_datos['id_proveedores']) === intval($fila['id_proveedores'])) {
                                    $nombre_proveedores_mostrar = $Proveedores_datos['nombre_fantasia'];
                                    break;
                                }
                            }

                            $nombre_tipo_unidades_mostrar = 'N/A';
                            foreach ($Tipo_unidades_disponibles as $Tipo_unidades_datos) {
                                if (intval($Tipo_unidades_datos['id_tipo_unidades']) === intval($fila['id_tipo_unidades'])) {
                                    $nombre_tipo_unidades_mostrar = $Tipo_unidades_datos['nombre_tipo_unidades'];
                                    break;
                                }
                            }
                        ?>
                        <div class="card-producto">
                            <div class="card-imagen-contenedor">
                                <?php if (!empty($fila['img_productos'])): ?>
                                    <img src="<?= htmlspecialchars($fila['img_productos']) ?>" alt="Imagen" class="card-imagen">
                                <?php else: ?>
                                    <div class="card-imagen-placeholder">
                                        <span>Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                                <span class="card-badge"><?= htmlspecialchars($nombre_tipo_productos_mostrar) ?></span>
                            </div>
                            <div class="card-info">
                                <h2 class="card-nombre"><?= htmlspecialchars($fila['nombre_del_producto']) ?></h2>
                                <?php $Precio_formateado = number_format(floatval($fila['precio']), 2, ',', '.'); ?>
                                <p class="card-detalle"><i class="ph ph-currency-dollar"></i> <?= htmlspecialchars($Precio_formateado) ?> x <?= htmlspecialchars($nombre_tipo_unidades_mostrar) ?>.</p>
                                <p class="card-detalle"><i class="ph ph-package"></i> <?= htmlspecialchars($fila['stock']) ?> u.</p>
                                <p class="card-detalle"><i class="ph ph-user"></i> <?= htmlspecialchars($nombre_proveedores_mostrar) ?>.</p>
                            </div>
                            <div class="card-acciones">
                                <form method='GET' style='display:inline;'>
                                    <input type='hidden' name='action' value='modo_de_edicion'>
                                    <input type='hidden' name='id_productos' value="<?= htmlspecialchars($fila['id_productos']) ?>">
                                    <button type='submit' class='btn-card-action btn-editar'><img src='img/Editar.png' class='iconos-secundarios'>Editar</button>
                                </form>
                                    
                                <form method='POST' style='display:inline;' onsubmit='return confirmarEliminar(event)'>
                                    <input type='hidden' name='action' value='eliminar'>
                                    <input type='hidden' name='eliminar_id' value="<?= htmlspecialchars($fila['id_productos']) ?>">
                                    <button type='submit' class='btn-card-action btn-eliminar'><img src='img/Eliminar.png' class='iconos-secundarios'>Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php if ($Total_paginas > 1): ?>
                <div class="paginador-contenedor">
                    <?php
                    $Params_paginador = [];
                    if (!empty($Nombre_busqueda))    $Params_paginador[] = "nombre_del_producto=" . urlencode($Nombre_busqueda);
                    if (!empty($Tipo_productos_busqueda))  $Params_paginador[] = "id_tipo_productos=" . urlencode($Tipo_productos_busqueda);
                    if (!empty($Tipo_unidades_busqueda))  $Params_paginador[] = "id_tipo_unidades=" . urlencode($Tipo_unidades_busqueda);
                    if (!empty($Proveedores_busqueda))    $Params_paginador[] = "id_proveedores=" . urlencode($Proveedores_busqueda);
                    if (is_numeric($Precio_busqueda)) $Params_paginador[] = "precio=" . urlencode($Precio_busqueda);
                    $Query_base = count($Params_paginador) ? '&' . implode('&', $Params_paginador) : '';

                    if ($Pagina_actual > 1): ?>
                        <a href="productos.php?pagina=<?php echo $Pagina_actual - 1 . $Query_base; ?>" class="btn-paginador flecha">⟪ Anterior</a>
                    <?php endif;

                    for ($i = 1; $i <= $Total_paginas; $i++): ?>
                        <a href="productos.php?pagina=<?php echo $i . $Query_base; ?>"
                           class="btn-paginador <?php echo ($i === $Pagina_actual) ? 'activo' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;

                    if ($Pagina_actual < $Total_paginas): ?>
                        <a href="productos.php?pagina=<?php echo $Pagina_actual + 1 . $Query_base; ?>" class="btn-paginador flecha">Siguiente ⟫</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>

        function BuscarProductos() {

            document.getElementById('action').value = 'buscar';
            document.getElementById('nombre_del_producto').required = false;
            document.getElementById('id_tipo_productos').required = false;
            document.getElementById('id_tipo_unidades').required = false;
            document.getElementById('id_proveedores').required = false;
            document.getElementById('precio').required = false;
            return true;
            
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('img_productos');
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
                        title: "El producto ha sido eliminado exitosamente",
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
                title: "Producto registrado exitosamente",
                icon: "success",
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito_1'])): ?>
            Swal.fire({
                title: "Producto modificado exitosamente",
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