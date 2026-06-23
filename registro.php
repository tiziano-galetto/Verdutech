<?php

session_start();
include 'funcion.php';

$Mensaje_registro = "";
$Nombre_error = '';
$Apellido_error = '';
$Correo_error = '';
$Contraseña_error = '';
$Confirmar_contraseña_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $conn = conexion();
    $errores = []; 

    $Nombre = $_POST['nombre'];
    $Apellido = $_POST['apellido'];
    $Correo = $_POST['email'];
    $Contraseña = $_POST['contraseña'];
    $Confirmar_contraseña = $_POST['confirmar_contraseña'];

    if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/", $Apellido)) {
        $errores[] = "El nombre y el apellido solo pueden contener letras"; 
    }
        
    if (!preg_match("/^\S+$/", $Contraseña)) {
        $errores[] = "La contraseña no puede contener espacios vacíos"; 
    }

    if (strlen($Contraseña) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if (!preg_match("/[A-ZÁÉÍÓÚÑ]/", $Contraseña)) {
        $errores[] = "La contraseña debe contener al menos una letra mayúscula";
    }

    if (!preg_match("/[0-9]/", $Contraseña)) {
        $errores[] = "La contraseña debe contener al menos un número";
    }

    if ($Contraseña !== $Confirmar_contraseña) {
        $errores[] = "Las contraseñas no coinciden";
    }

    $Sql_check = "SELECT email FROM usuarios WHERE email = ?";
    $stmt_check = $conn->prepare($Sql_check);
    $stmt_check->bind_param("s", $Correo);
    $stmt_check->execute();
    $Resultado_check = $stmt_check->get_result();

    if ($Resultado_check->num_rows > 0) {
        $errores[] = "Ya existe un usuario con este correo"; 
    }
    $stmt_check->close();

    if (empty($errores)) {
            
        $Nombre = ucfirst(strtolower($Nombre));
        $Apellido = ucfirst(strtolower($Apellido));
        $Contraseña_hashed = password_hash($Contraseña, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO usuarios (nombre, apellido, email, contraseña) VALUES (?, ?, ?, ?)"; 
        $stmt_insert = $conn->prepare($sql_insert); 
        $stmt_insert->bind_param("ssss", $Nombre, $Apellido, $Correo, $Contraseña_hashed);

        if ($stmt_insert->execute()) {
            $_SESSION['mensaje_exito'] = true;
            header("Location: registro.php");
            exit;
        } else {
            $Mensaje_registro = "<div class='mensaje-error'>Registro fallido</div>";
        }
        $stmt_insert->close();
    } else {
        $Nombre_error = $Nombre;
        $Apellido_error = $Apellido;
        $Correo_error = $Correo;

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
                <h2>Creá tu cuenta</h2>
                <?php echo $Mensaje_registro; ?>
                <div class="formulario-grupo">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($Nombre_error); ?>" placeholder="Tu nombre" required>
                </div>
                <div class="formulario-grupo">
                    <label for="apellido">Apellido</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($Apellido_error); ?>" placeholder="Tu apellido" required>
                </div>
                <div class="formulario-grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($Correo_error); ?>" placeholder="Tu@correo.com" required>
                </div>
                <div class="formulario-grupo">
                    <label for="contrasena">Contraseña</label>
                    <div class="contraseña-input-contenedor">
                        <input type="password" name="contraseña" id="contraseñaInput" value="<?php echo htmlspecialchars($Contraseña_error); ?>" placeholder="••••••••" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility('contraseñaInput', 'ojoIcono1')">
                            <img id="ojoIcono1" src="img/ojo2.png">
                        </span>
                    </div>
                    <ul class="requisitos-contraseña">
                        <li id="req-longitud">Mínimo 8 caracteres</li>
                        <li id="req-mayuscula">Al menos una letra mayúscula</li>
                        <li id="req-numero">Al menos un número</li>
                    </ul>
                </div>
                <div class="formulario-grupo">
                    <label for="confirmar_contraseña">Confirmar contraseña</label>
                    <div class="contraseña-input-contenedor">
                        <input type="password" name="confirmar_contraseña" id="confirmarContraseñaInput" value="<?php echo htmlspecialchars($Confirmar_contraseña_error); ?>" placeholder="••••••••" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility('confirmarContraseñaInput', 'ojoIcono2')">
                            <img id="ojoIcono2" src="img/ojo2.png">
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn-registro">Crear cuenta</button>
                <p class="link">
                    ¿Ya tienes una cuenta? <a href="index.php">Iniciar sesión aquí</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
<script>

    function togglePasswordVisibility(inputId, iconoId) {
        var campo_de_la_contraseña = document.getElementById(inputId);
        var ojo_icono = document.getElementById(iconoId);

        if (campo_de_la_contraseña.type === "password") {
            campo_de_la_contraseña.type = "text";
            ojo_icono.src = "img/ojo1.png";
        } else {
            campo_de_la_contraseña.type = "password";
            ojo_icono.src = "img/ojo2.png";
        }
    }

    document.getElementById("contraseñaInput").addEventListener("input", function () {

        const valor = this.value;
        const reqLongitud  = document.getElementById("req-longitud");
        const reqMayuscula = document.getElementById("req-mayuscula");
        const reqNumero    = document.getElementById("req-numero");

        reqLongitud.classList.toggle("cumplido", valor.length >= 8);
        reqMayuscula.classList.toggle("cumplido", /[A-ZÁÉÍÓÚÑ]/.test(valor));
        reqNumero.classList.toggle("cumplido", /[0-9]/.test(valor));

    });

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        Swal.fire({
            title: "Usuario registrado exitosamente",
            icon: "success",
            timer: 2500,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "index.php";
        });
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

</script>
