<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$Nombre = $_SESSION['nombre'];
$Apellido = $_SESSION['apellido'];
$Es_admin = (isset($_SESSION['email']) && $_SESSION['email'] === 'admin@verdutech.com');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="inicio.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/lottie-web@5.12.2/build/player/lottie.min.js"></script>
</head>
<body>
    <div class="contenedor-principal">
        <header class="barra-de-navegacion">
            <div class="logo-contenedor">
                <div class="logo"></div>
                <span class="logo-texto">Verdutech</span>
            </div>
            <div class="bienvenido-texto-contenedor">
                <span class="bienvenido-texto">¡Holaa, <?php echo htmlspecialchars($Nombre . ' ' . $Apellido); ?>! <div id="lottie-hey" style="width:32px;height:32px;display:inline-block;vertical-align:bottom;"></div></span>
            </div>
            <div class="boton-contenedo">
                <a href="cerrarsesion.php" class="btn-salir"><img src="img/Salir.png" class="icono-salir">Cerrar sesión</a>
            </div>
        </header>

        <main class="contenedor-secundario">
            <div class="botones-contenedor">
                <div class="display-grid">
                    <a href="compras.php" class="botones">
                        <img src="img/Compras.png" alt="Compras">
                        <span>Compras</span>
                    </a>
                    <a href="ventas.php" class="botones">
                        <img src="img/Ventas.png" alt="Ventas">
                        <span>Ventas</span>
                    </a>
                    <a href="<?php echo $Es_admin ? 'estadisticas.php' : '#'; ?>" class="botones" <?php if(!$Es_admin) echo 'onclick="alertaAdmin(event)"'; ?>>
                        <img src="img/Estadisticas.png" alt="Estadisticas">
                        <span>Estadísticas</span>
                    </a>
                    <a href="clientes.php" class="botones">
                        <img src="img/clientes.png" alt="Clientes">
                        <span>Clientes</span>
                    </a>
                    <a href="<?php echo $Es_admin ? 'empleados.php' : '#'; ?>" class="botones" <?php if(!$Es_admin) echo 'onclick="alertaAdmin(event)"'; ?>>
                        <img src="img/Empleados.png" alt="Empleados">
                        <span>Empleados</span>
                    </a>
                    <a href="proveedores.php" class="botones">
                        <img src="img/proveedores.png" alt="Proveedores">
                        <span>Proveedores</span>
                    </a>
                    <a href="productos.php" class="botones">
                        <img src="img/Productos.png" alt="Productos">
                        <span>Productos</span>
                    </a>
                    <a href="stocks.php" class="botones">
                        <img src="img/Stock.png" alt="Stock">
                        <span>Stock</span>
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script>
        function alertaAdmin(event) {
            
            event.preventDefault(); 
        
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: 'Solo puede acceder el admin',
                confirmButtonColor: '#f44336',
                confirmButtonText: 'Cerrar',
                heightAuto: false
            });
        }

        lottie.loadAnimation({
            container: document.getElementById('lottie-hey'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: 'img/Hey.json'
        });
    </script>
</body>
</html>