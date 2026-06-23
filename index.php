<?php

session_start();
include 'funcion.php';

$mensaje_login = "";
$Correo_error = '';
$Contraseña_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn = conexion();
    
    $Correo = $_POST['correo'];
    $Contraseña = $_POST['contraseña'];

    $sql = "SELECT nombre, apellido, contraseña FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $Correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        if (password_verify($Contraseña, $fila['contraseña'])) {
            $_SESSION['nombre'] = $fila['nombre'];
            $_SESSION['apellido'] = $fila['apellido'];
            $_SESSION['email'] = $Correo;
            $stmt->close();
            $conn->close();
            header("Location: inicio.php");
            exit();
        }
    }

    $mensaje_login = "<div class='mensaje-error'>Datos incorrectos</div>";
    $Correo_error = $Correo;
    $stmt->close();
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
                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($Correo_error); ?>" placeholder="Tu@correo.com" required>
                </div>
                <div class="formulario-grupo">
                    <label for="contraseña">Contraseña</label>
                    <div class="contraseña-input-contenedor">

                        <input type="password" name="contraseña" id="contraseñaInput" value="<?php echo htmlspecialchars($Contraseña_error); ?>" placeholder="••••••••" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility()">
                            <img id="ojoIcono" src="img/ojo2.png">
                        </span>
                        
                    </div>
                    <div style="text-align: right;">
                        <a href="restablecer.php" class="Olvidaste-tu-contraseña">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
                <button type="submit" class="btn-login"><img src="img/Entrar.png" class="icono-entrar">Iniciar sesión</button>
                <p class="link">
                    ¿No tienes una cuenta? <a href="registro.php">Crear cuenta aquí</a>
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