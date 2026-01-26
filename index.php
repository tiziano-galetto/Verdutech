<?php

session_start();
include 'funcion.php';

$mensaje_login = "";
$Correo_error = '';
$Contraseña_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn = conexion();
    
    $Correo = $conn->real_escape_string($_POST['correo']);
    $Contraseña = $conn->real_escape_string($_POST['contraseña']);

    $sql = "SELECT nombre, apellido, contraseña FROM usuarios WHERE email = '$Correo'";
    $resultado = $conn->query($sql);

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        if (password_verify($Contraseña, $fila['contraseña'])) {
            $_SESSION['nombre'] = $fila['nombre'];
            $_SESSION['apellido'] = $fila['apellido'];
            header("Location: inicio.php");
            exit();
        }
    }

    $mensaje_login = "<div class='mensaje-error'>Datos incorrectos</div>";
    $Correo_error = $Correo;
    $Contraseña_error = $Contraseña;
    $conn->close();

}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="barra-de-navegacion">
        <div class="logo-contenedor">
            <div class="logo"></div>
            <span class="logo-texto">Verdutech</span>
        </div>
    </div>
    
    <div class="contenedor-principal">
        <div class="login-contenedor">
            <form class="login-formulario" method="post">
                <h2>Iniciar sesión</h2>
                <?php echo $mensaje_login; ?>
                <div class="formulario-grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($Correo_error); ?>" required>
                </div>
                <div class="formulario-grupo">
                    <label for="contraseña">Contraseña</label>
                    <div class="contraseña-input-contenedor">

                        <input type="password" name="contraseña" id="contraseñaInput" value="<?php echo htmlspecialchars($Contraseña_error); ?>" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility()">
                            <img id="ojoIcono" src="img/ojo2.png">
                        </span>
                        
                    </div>
                </div>
                <button type="submit" class="btn-login">Entrar</button>
                <p class="link">
                    ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
<script>

    function togglePasswordVisibility() {

        var campo_de_la_contraseña = document.getElementById("contraseñaInput");
        var ojo_icono = document.getElementById("ojoIcono");

        if (campo_de_la_contraseña.type === "password") {
            campo_de_la_contraseña.type = "text";
            ojo_icono.src = "img/ojo1.png";
        } else {
            campo_de_la_contraseña.type = "password";
            ojo_icono.src = "img/ojo2.png";
        }
    }

</script>