<?php

session_start();

include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$Nombre = $_SESSION['nombre'];
$Apellido = $_SESSION['apellido'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="inicio.css">
</head>
<body>
    <div class="contenedor-principal">
        <header class="barra-de-navegacion">
            <div class="logo-contenedor">
                <div class="logo"></div>
                <span class="logo-texto">Verdutech</span>
            </div>
            <div class="bienvenido-texto-contenedor">
                <span class="bienvenido-texto">¡Holaa, <?php echo htmlspecialchars($Nombre . ' ' . $Apellido); ?>! 👋</span>
            </div>
            <div class="boton-contenedo">
                <a href="cerrarsesion.php" class="btn-salir"><img src="img/Salir.png" class="icono-salir">Cerrar sesión</a>
            </div>
        </header>

        <main class="contenedor-secundario">
            <div class="botones-contenedor">
                <div class="display-grid">
                    <a href="#" class="botones">
                        <img src="img/Compras.png" alt="Compras">
                        <span>Compras</span>
                    </a>
                    <a href="ventas.php" class="botones">
                        <img src="img/Ventas.png" alt="Ventas">
                        <span>Ventas</span>
                    </a>
                    <a href="estadisticas.php" class="botones">
                        <img src="img/Estadisticas.png" alt="Estadisticas">
                        <span>Estadísticas</span>
                    </a>
                    <a href="clientes.php" class="botones">
                        <img src="img/clientes.png" alt="Clientes">
                        <span>Clientes</span>
                    </a>
                    <a href="empleados.php" class="botones">
                        <img src="img/Empleados.png" alt="Empleados">
                        <span>Empleados</span>
                    </a>
                    <a href="proveedores.php" class="botones">
                        <img src="img/proveedores.png" alt="Proveedores">
                        <span>Proveedores</span>
                    </a>
                    <a href="#" class="botones">
                        <img src="img/Productos.png" alt="Productos">
                        <span>Productos</span>
                    </a>
                    <a href="#" class="botones">
                        <img src="img/Stock.png" alt="Stock">
                        <span>Stock</span>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>