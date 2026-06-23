<?php

session_start();
include 'funcion.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    <link rel="stylesheet" href="cambiar.css">
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
                <h2>Cambiar contraseña</h2>
                <div class="formulario-grupo">
                    <label for="contrasena">Contraseña</label>
                    <div class="contraseña-input-contenedor">
                        <input type="password" name="contraseña" id="contraseñaInput" placeholder="••••••••" required>
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
                        <input type="password" name="confirmar_contraseña" id="confirmarContraseñaInput" placeholder="••••••••" required>
                        <span class="alternar-contraseña" onclick="togglePasswordVisibility('confirmarContraseñaInput', 'ojoIcono2')">
                            <img id="ojoIcono2" src="img/ojo2.png">
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn-registro">Cambiar contraseña</button>
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
            title: "Se cambio la contraseña exitosamente",
            icon: "success",
            timer: 2500,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "index.php";
        });
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

</script>