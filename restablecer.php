<?php

session_start();
include 'funcion.php';
$conn = conexion();

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$SweetAlert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Correo = $_POST['correo'] ?? '';

    if (empty($Correo) || !filter_var($Correo, FILTER_VALIDATE_EMAIL)) {

        $SweetAlert = "Swal.fire({
            icon: 'error',
            title: 'Por favor ingresá un correo electrónico válido',
            timer: 2500,
            showConfirmButton: false
        });";

    } else {

        $Stmt = $conn->prepare("SELECT id_usuarios FROM usuarios WHERE email = ?");
        $Stmt->bind_param("s", $Correo);
        $Stmt->execute();
        $Stmt->store_result();
        $Resultado = $Stmt->num_rows > 0;
        $Stmt->close();

        if (!$Resultado) {

            $SweetAlert = "Swal.fire({
                icon: 'error',
                title: 'Este correo no está registrado',
                timer: 2500,
                showConfirmButton: false
            });";

        } else {

            $Token = bin2hex(random_bytes(32));
            $TokenExpiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $Stmt = $conn->prepare("UPDATE usuarios SET token = ?, token_expiracion = ? WHERE email = ?");
            $Stmt->bind_param("sss", $Token, $TokenExpiracion, $Correo);
            $Stmt->execute();
            $Stmt->close();

            $Enlace = "http://localhost/verdutech/cambiar.php?token=$Token";

            $CuerpoHtml = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
            <meta charset="UTF-8">
            </head>
            <body style="margin:0; padding:0; background-color:#f5f5f5;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding: 50px 0;">
                    <tr>
                        <td align="center">
                            <table role="presentation" width="500" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius: 10px; overflow:hidden;">
                                
                                <tr>
                                    <td style="background-color:#4caf50; padding: 25px 25px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td valign="middle" width="50" style="width:50px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" width="50" height="50" style="background-color:#ffffff; border-radius: 50%; width:50px; height:50px;">
                                                        <tr>
                                                            <td align="center" valign="middle">
                                                                <img src="cid:LogoVerdutech" alt="Verdutech" width="50" height="50" style="display:block; border-radius: 50%;">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="center" valign="middle">
                                                    <span style="color:#ffffff; font-size: 25px; font-weight:bold;">Verdutech</span>
                                                </td>
                                                <td valign="middle" width="50" style="width:50px;"> </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding: 25px 50px 25px 50px;">
                                        <h2 style="color:#000000; font-size: 25px; margin: 0 0 10px 0;">Restablecer tu contraseña</h2>
                                        <p style="color:#696969; font-size: 15px; line-height: 1.5; margin: 0 0 10px 0;">
                                            Recibimos una solicitud para restablecer tu contraseña.
                                        </p>
                                        <p style="color:#696969; font-size: 15px; line-height: 1.5; margin: 0;">
                                            Si fuiste vos, hacé clic en el botón.
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding: 25px 50px 25px 50px;">
                                        <a href="' . $Enlace . '" target="_blank" style="background-color:#4caf50; color:#ffffff; text-decoration:none; font-size: 15px; font-weight:bold; padding: 15px 25px; border-radius: 5px; display:inline-block;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding: 25px 50px 25px 50px;">
                                        <p style="color:#696969; font-size: 15px; line-height: 1.5; margin: 0 0 10px 0;">
                                            Este enlace es válido durante <strong>15 minutos</strong>.
                                        </p>
                                        <p style="color:#696969; font-size: 15px; line-height: 1.5; margin: 0;">
                                            Si no solicitaste el restablecimiento, podés ignorar este correo. Tu contraseña no cambiará.
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding: 25px 50px 25px 50px; border-top: 1px solid #f5f5f5;">
                                        <p style="color:#696969; font-size: 15px; line-height: 1.5; margin: 0 0 10px 0;">
                                            ¿El botón no funciona? Copiá este enlace en tu navegador.
                                        </p>
                                        <p style="margin: 0;">
                                            <a href="' . $Enlace . '" style="color:#4caf50; font-size: 15px; word-break: break-all;">' . $Enlace . '</a>
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ';

            $Mail = new PHPMailer(true);

            try {

                $Mail->isSMTP();
                $Mail->Host       = 'smtp.gmail.com';
                $Mail->SMTPAuth   = true;
                $Mail->Username   = 'tizianogaletto1@gmail.com';
                $Mail->Password   = 'spgyhdvmmexjtmdl';
                $Mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $Mail->Port       = 587;
                $Mail->CharSet    = 'UTF-8';

                $Mail->setFrom('tizianogaletto1@gmail.com', 'Verdutech');
                $Mail->addAddress($Correo);

                $Mail->isHTML(true);
                $Mail->Subject = 'Restablecer contraseña';
                $Mail->Body    = $CuerpoHtml;
                $Mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                $Mail->addEmbeddedImage('img/Logo.png', 'LogoVerdutech');

                $Mail->send();

                $SweetAlert = "Swal.fire({
                    icon: 'success',
                    title: 'Correo enviado exitosamente',
                    timer: 2500,
                    showConfirmButton: false
                });";

            } catch (Exception $e) {

                $SweetAlert = "Swal.fire({
                    icon: 'error',
                    title: 'No se pudo enviar el correo',
                    timer: 2500,
                    showConfirmButton: false
                });";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
    <link rel="stylesheet" href="restablecer.css">
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
        <div class="login-contenedor">
            <form class="login-formulario" method="post">
                <h2>Restablecer contraseña</h2>
                <p>Ingresa tu correo electrónico y te enviaremos un mail para restablecer tu contraseña.</p>
                <div class="formulario-grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" placeholder="Tu@correo.com" required>
                </div>
                <button type="submit" class="btn-login"><img src="img/Enviar.png" class="icono-enviar">Enviar mail</button>
                <p class="link">
                    ¿Ya la recordaste? <a href="index.php">Iniciar sesión aquí</a>
                </p>
            </form>
        </div>
    </div>
    <?php if (!empty($SweetAlert)) { ?>
        <script>
            <?php echo $SweetAlert; ?>
        </script>
    <?php } ?>
</body>
</html>