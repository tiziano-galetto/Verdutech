<?php
function conexion(){
    $Host = 'localhost';
    $User = 'root';
    $Password = '';
    $BD = 'verdutech';

    $conn = mysqli_connect($Host, $User, $Password, $BD);

    if (!$conn) {
        die('No es posible conectarse a la base de datos: ' . mysqli_connect_error());
    }
    return $conn;
}
?>