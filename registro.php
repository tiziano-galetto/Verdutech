<?php

session_start();
include 'funcion.php';

$Mensaje_registro = "";
$Nombre_error = '';
$Apellido_error = '';
$Correo_error = '';
$Contraseña_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $conn = conexion();
    $errores = []; 

    $Nombre = $conn->real_escape_string($_POST['nombre']);
    $Apellido = $conn->real_escape_string($_POST['apellido']);
    $Correo = $conn->real_escape_string($_POST['email']);
    $Contraseña = $conn->real_escape_string($_POST['contraseña']);

    if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
        $errores[] = "El nombre y el apellido solo pueden contener letras"; 
    }
        
    if (!preg_match("/^\S+$/", $Contraseña)) {
        $errores[] = "La contraseña no puede contener espacios vacíos"; 
    }
            
    $Sql_check = "SELECT email FROM usuarios WHERE email = '$Correo'";
    $Resultado_check = $conn->query($Sql_check);

    if ($Resultado_check->num_rows > 0) {
        $errores[] = "Ya existe un usuario con este correo"; 
    }

    if (empty($errores)) {
            
        $Nombre = ucfirst(strtolower($Nombre));
        $Apellido = ucfirst(strtolower($Apellido));

        $Contraseña_hashed = password_hash($Contraseña, PASSWORD_DEFAULT);
        $Sql_insert = "INSERT INTO usuarios (nombre, apellido, email, contraseña) VALUES ('$Nombre', '$Apellido', '$Correo', '$Contraseña_hashed')";

        if ($conn->query($Sql_insert) === TRUE) {
            $_SESSION['mensaje_exito'] = true;
            header("Location: registro.php");
            exit;
        } else {
            $Mensaje_registro = "<div class='mensaje-error'>Registro fallido</div>";
        }
    } else {
        $Nombre_error = $Nombre;
        $Apellido_error = $Apellido;
        $Correo_error = $Correo;
        $Contraseña_error = $Contraseña;

        foreach ($errores as $error) {
            $Mensaje_registro .= "<div class='mensaje-error'>$error</div>";
        }
    }

    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="registro.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="barra-de-navegacion">
        <div class="logo-contenedor">
            <div class="logo"></div>
            <span class="logo-texto">Verdutech</span>
        </div>
    </div>
    
    <div class="contenedor-principal">
        <div class="registro-contenedor">
            <form class="registro-formulario" method="post">
                <h2>Crear una cuenta</h2>
                <?php echo $Mensaje_registro; ?>
                <div class="formulario-grupo">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($Nombre_error); ?>" required>
                </div>
                <div class="formulario-grupo">
                    <label for="apellido">Apellido</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($Apellido_error); ?>" required>
                </div>
                <div class="formulario-grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($Correo_error); ?>" required>
                </div>
                <div class="formulario-grupo">
                    <label for="contrasena">Contraseña</label>
                    <div class="contraseña-input-contenedor">

                        <input type="password" name="contraseña" id="contraseñaInput" value="<?php echo htmlspecialchars($Contraseña_error); ?>" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility()">
                            <img id="ojoIcono" src="img/ojo2.png">
                        </span>
                        
                    </div>
                </div>
                <button type="submit" class="btn-registro">Registrarse</button>
                <p class="link">
                    ¿Ya tienes una cuenta? <a href="index.php">Inicia sesión aquí</a>
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

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        Swal.fire({
            title: "Usuario registrado exitosamente",
            icon: "success",
            timer: 2500,
            showConfirmButton: false
        });
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

</script>
