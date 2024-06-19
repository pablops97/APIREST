<?php
require_once 'Controlador/usuario.controlador.php';
require_once 'Controlador/ConexionBD.php';
require_once 'Controlador/eventos.controlador.php';
$arrayRutas = explode("/", $_SERVER["REQUEST_URI"]);

$eventoControlador = new ControladorEventos();
$usuarioControlador = new UsuarioControlador();


if (count(array_filter($arrayRutas)) == 2) {
    
    switch (array_filter($arrayRutas)[2]) {
        case 'inicio':
            if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
                
                $usuario = $_POST['nombreUsuario'];
                $pass = $_POST['pass'];
                $usuarioControlador->iniciarSesion($usuario, $pass);
                
            }else{
                header("HTTP/1.0 404 Not Found");
            }
            break;
        case "registro":
            if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
                $usuario = $_POST["nombreusuario"];
                $contra = $_POST["contrasenia"];
                $email = $_POST["email"];
                $usuarioControlador->registro($usuario, $contra, $email);
            }else{
                header("HTTP/1.0 404 Not Found");
            }
            break;

        case "eventos":
            if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "GET") {
                $eventoControlador->obtenerEventos();
                
            }else{
                header("HTTP/1.0 404 Not Found");
            }
            break;
        case "editar":
            if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
            $usuarioControlador->editarUsuario($_POST['idusuario'], $_POST['usuario'], $_POST['correo'], $_POST['pass'], $_POST['iban']);
            }else{
                header("HTTP/1.0 404 Not Found");
            }
            break;

        case "completar_edicion":
            if($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST"){
                 // Recibir los datos
                $nombre = $_POST['nombre'];
                $apellidos = $_POST['apellidos'];
                $direccion = $_POST['direccion'];
                $provincia = $_POST['provincia'];
                $codigoPostal = $_POST['codigoPostal'];
                $fechaNacimiento = $_POST['fechaNacimiento'];
                $iban = $_POST['iban'];
                $id = $_POST['idusuario'];
                $targetDir = "localhost/login/Images/";
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['imagen']['tmp_name'];
                    $fileName = $_FILES['imagen']['name'];
                    $fileSize = $_FILES['imagen']['size'];
                    $fileType = $_FILES['imagen']['type'];
                    $fileNameCmps = explode(".", $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));

                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $dest_path = $targetDir . $newFileName;
                }
                $usuarioControlador ->completarRegistro($nombre, $apellidos, $direccion, $provincia, $codigoPostal, $fechaNacimiento, $iban, $fileTmpPath, $dest_path, $id);
            }
            break;
            case 'usuario':
                if($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST"){
                    $usuarioControlador->obtenerUsuario($_POST['id']);
                }
                break;
    }
}

if(count(array_filter($arrayRutas)) == 3){
    if(array_filter($arrayRutas)[2] == 'eventos'){
        switch(array_filter($arrayRutas)[3]){
            case 'inscripcion':
                if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
                $eventoControlador->inscripcion($_POST['idusuario'], $_POST['idevento']);
                }else{
                    header("HTTP/1.0 404 Not Found");
                }
                break;
            case 'miseventos':
                if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
                    $eventoControlador->obtenerEventoPorUsuario($_POST['idusuario']);
                }else{
                    header("HTTP/1.0 404 Not Found");
                }
                break;
            case 'baja':
                if ($_SERVER["REQUEST_METHOD"] && $_SERVER["REQUEST_METHOD"] == "POST") {
                    $eventoControlador->abandonarEvento($_POST['idusuario'],$_POST['idmatriculacion']);
                }else{
                    header("HTTP/1.0 404 Not Found");
                }
                break;
            case 'recuperar':
                $eventoControlador->recuperarEvento($_POST['idmatriculacion']);
                break;
        }
        
    }
}
