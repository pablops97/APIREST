<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$bd = 'practica';

    $mysql = mysqli_connect($host, $user, $pass, $bd);
    
    if($mysql -> connect_error){
        die("Error de conexión a la base de datos");
    }
    
?>