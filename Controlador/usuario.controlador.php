<?php


class UsuarioControlador
{


    public function obtenerUsuario($idUsuario){
        include 'ConexionBD.php';
        $consultaSQL = "SELECT * FROM USUARIO 
                        WHERE id = ? ";                       
        $preparedStatement = $mysql->prepare($consultaSQL);
        $preparedStatement->bind_param("i", $idUsuario);
        $preparedStatement->execute();
        $result = $preparedStatement->get_result();
        if ($result->num_rows > 0) {
            $fila = $result->fetch_assoc();
            $jsonRespuesta = json_encode(array(
                "nombreUsuario" => $fila['usuario'],
                "imagenUsuario" => $fila['imagen'],
                "correo" => $fila['email'],
                "cuentaIban" => $fila['cuenta_iban'],
            ));
            echo $jsonRespuesta;
        }
        else{
            $jsonRespuesta = json_encode(array(
                "nombreUsuario" => "null",
                "imagenUsuario" => "null",
                "correo" => "null",
                "cuentaIban" => "null",
            ));
            echo $jsonRespuesta;
        }
        $preparedStatement->close();

    }


    public function iniciarSesion($usuario, $contra)
    {
        include 'ConexionBD.php';


        $controlador = new UsuarioControlador();
        $id = $controlador->obtenerIDUsuario($usuario);
        $combinacion = $controlador->obtenerCombinacion($id);
        $contraseniafinal = hash("sha256", $contra . $combinacion);

        if (empty($usuario) || empty($contra)) {
            return json_encode(array(
                "idResultado" => "null",
                "nombre" => "null",
                "mensaje" => "Usuario o contraseña vacios"
            ));
        }

        $consultaSQL = "SELECT id, usuario, nombre, apellidos, email, imagen, cuenta_iban FROM USUARIO 
                        WHERE usuario = ? 
                        AND password = ?
                        AND fecha_baja IS NULL";
        $preparedStatement = $mysql->prepare($consultaSQL);

        if ($preparedStatement === false) {
            die("Error en la preparación de la sentencia: " . $mysql->error);
        }

        $preparedStatement->bind_param("ss", $usuario, $contraseniafinal);

        if (!$preparedStatement->execute()) {
            die("Error en la ejecución de la sentencia: " . $preparedStatement->error);
        }

        $result = $preparedStatement->get_result();

        if ($result->num_rows > 0) {
            $fila = $result->fetch_assoc();

            if ($fila['nombre'] !== null && $fila['apellidos'] !== null) {
                $jsonRespuesta = json_encode(array(
                    "idResultado" => $fila['id'],
                    "nombre" => $fila['usuario'],
                    "email" => $fila['email'],
                    "imagen" => $fila['imagen'],
                    "cuentaIban" => $fila['cuenta_iban'],
                    "registro" => "completo"
                ));
            } else {
                $jsonRespuesta = json_encode(array(
                    "idResultado" => $fila['id'],
                    "nombre" => $fila['usuario'],
                    "email" => $fila['email'],
                    "imagen" => $fila['imagen'],
                    "cuentaIban" => $fila['cuenta_iban'],
                    "registro" => "incompleto"
                ));
            }
        } else {
            $jsonRespuesta = json_encode(array(
                "idResultado" => "-1",
                "nombre" => "null",
                "email" => "null",
                "imagen" => "null",
                "cuentaIban" => "null",
                "registro" => "null"
            ));
        }

        $preparedStatement->close();
        $mysql->close();
        echo $jsonRespuesta;
    }

    public function registro($usuario, $contra, $email)
    {
        include 'ConexionBD.php';

        $controlador = new UsuarioControlador();
        $combinacion = $controlador->generateNumericSalt();
        $contraEncriptada = hash("sha256", $contra . $combinacion); // Corrige la función hash con el salt concatenado

        // Verificar si el usuario ya existe
        $comprobacion = $mysql->prepare("SELECT * FROM USUARIO WHERE usuario = ?");
        if ($comprobacion === false) {
            echo json_encode(array("mensaje" => "Error en la preparación de la consulta", "codigo" => 0));
            return;
        }
        $comprobacion->bind_param("s", $usuario);
        $comprobacion->execute();
        $resultado = $comprobacion->get_result();

        if ($resultado->num_rows > 0) {
            echo json_encode(array("mensaje" => "Este usuario ya existe", "codigo" => 0));
        } else {
            // Insertar el nuevo usuario
            $preparedStatement = $mysql->prepare("INSERT INTO USUARIO (usuario, password, email) VALUES (?,?,?)");
            if ($preparedStatement === false) {
                echo json_encode(array("mensaje" => "Error en la preparación de la consulta de inserción", "codigo" => 0));
                return;
            }
            $preparedStatement->bind_param("sss", $usuario, $contraEncriptada, $email);
            $preparedStatement->execute();

            if ($preparedStatement->affected_rows > 0) {
                // Obtener el ID del usuario insertado
                $idUsuario = $mysql->insert_id;

                // Insertar la combinación
                $insertarCombinacion = $mysql->prepare("INSERT INTO COMBINACIONUSUARIO (IDUSUARIO, COMBINACION) VALUES (?, ?)");
                if ($insertarCombinacion === false) {
                    echo json_encode(array("mensaje" => "Error en la preparación de la consulta de combinación", "codigo" => 0));
                    return;
                }
                $insertarCombinacion->bind_param("is", $idUsuario, $combinacion); // Usa "is" para ID (int) y combinación (string)
                if ($insertarCombinacion->execute()) {

                    # ACTUALIZAR EL LOG PARA REGISTRAR QUE SE HA REGISTRADO UN NUEVO USUARIO
                    $registroLog = "INSERT INTO REGISTROLOG(MENSAJE, ID_USUARIO) VALUES (?,?)";
                    $prepared = $db->prepare($registroLog);
                    $mensaje = "Se ha registrado un nuevo usuario";
                    $prepared->bind_param("si", $mensaje, $idUsuario);
                    $prepared->execute();
                    echo json_encode(array("mensaje" => "Usuario registrado con éxito", "codigo" => 1));
                } else {
                    echo json_encode(array("mensaje" => "Error al insertar la combinación", "codigo" => 0));
                }
                $insertarCombinacion->close();
            } else {
                echo json_encode(array("mensaje" => "Error al insertar el usuario", "codigo" => 0));
            }
            $preparedStatement->close();
        }
        $comprobacion->close();
        $mysql->close();
    }


    public function editarUsuario($idUsuario, $usuario, $correo, $contra, $iban)
{
    include 'ConexionBD.php';
    $updates = [];
    
    // Depuración: imprimir las variables recibidas
    error_log("Usuario: $usuario, Correo: $correo, Pass: $contra, IBAN: $iban");

    if ($usuario != '') $updates[] = "usuario = '$usuario'";
    if ($correo != '') $updates[] = "email = '$correo'";

    // Comprobar específicamente la variable $pass
    if (isset($contra) && !empty($contra)) {
        $controlador = new UsuarioControlador();
        $combinacion = $controlador->obtenerCombinacion($idUsuario);
        $passHasheada = hash("sha256", $contra . $combinacion); // Nota: concatenación corregida
        $updates[] = "password = '$passHasheada'";
    } else {
        // Depuración: mensaje si $pass está vacío
        error_log("La contraseña está vacía.");
    }

    if ($iban != '') {
        $iban = 'ES' . $iban;
        $updates[] = "cuenta_iban = '$iban'";
    }

    if (!empty($updates)) {
        $sql = "UPDATE Usuario SET " . implode(', ', $updates) . " WHERE id = $idUsuario";
        
        // Depuración: imprimir la consulta SQL
        error_log("SQL Query: $sql");

        if ($mysql->query($sql) === TRUE) {

            // ACTUALIZAR EL LOG PARA REGISTRAR QUE TECNICO HA ACTUALIZADO AL USUARIO
            $registroLog = "INSERT INTO REGISTROLOG(MENSAJE, ID_USUARIO) VALUES (?,?)";
            $prepared = $mysql->prepare($registroLog);
            $mensaje = "El usuario ha actualizado sus datos";
            $prepared->bind_param("si", $mensaje, $idUsuario);
            $prepared->execute();

            echo json_encode(array("mensaje" => "Datos actualizados con éxito", "codigo" => 1));
        } else {
            echo json_encode(array("mensaje" => "Error", "codigo" => 0));
        }
    } else {
        echo json_encode(array("mensaje" => "No hay datos que modificar", "codigo" => 1));
    }
}


    public function completarRegistro($id, $nombre, $apellidos, $direccion, $provincia, $codigoPostal, $fechaNacimiento, $iban, $fileTmpPath, $dest_path){
    $targetDir = "localhost/login/Images/";
    $response = array();
    
        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            // Guardar los datos en la base de datos
            include 'ConexionBD.php';
            $sql = "UPDATE USUARIO SET NOMBRE = ?, APELLIDOS = ?, DIRECCION = ?, PROVINCIA = ? CP = ?, FECHA_NACIMIENTO = ?, CUENTA_IBAN = ?, IMAGEN = ?
                                    WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisssi", $nombre, $apellidos, $direccion, $provincia, $codigoPostal, $fechaNacimiento, $iban, $newFileName, $id);
            
            if ($stmt->execute()) {
                $response['message'] = "Datos guardados exitosamente";
                $response['code'] = 1;
            } else {
                $response['message'] = "Error al guardar los datos";
                $response['code'] = 0;
            }

            $stmt->close();
        } else {
            $response['message'] = "Error al mover el archivo";
            $response['code'] = 0;
        }
    

    echo json_encode($response);
}
    

    public function obtenerIDUsuario($usuario)
    {
        include 'ConexionBD.php';

        $extraerID = "SELECT id FROM usuario WHERE usuario = ?";
        $stmt = $mysql->prepare($extraerID);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->get_result();
        $fila = $result->fetch_assoc();

        if ($fila) {
            return $fila['id'];
        } else {
            return 0;
        }
    }

    public function obtenerCombinacion($id)
    {
        include 'ConexionBD.php';

        #extraemos la combinacion para añadirsela a la contraseña

        $extraerID = "SELECT COMBINACION FROM COMBINACIONUSUARIO WHERE IDUSUARIO = ?";
        $stmt = $mysql->prepare($extraerID);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->get_result();
        $fila = $result->fetch_assoc();

        if ($fila) {
            return $fila['COMBINACION'];
        } else {
            return 0;
        }
    }



    private function generateNumericSalt($minLength = 10, $maxLength = 16)
    {
        $length = rand($minLength, $maxLength);
        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= rand(0, 9);
        }
        return $salt;
    }
}
