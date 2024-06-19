<?php

class ControladorEventos
{

    public function obtenerEventos()
    {
        include 'ConexionBD.php';
        $fechaActual = date("Y-m-d");
        // Consulta para extraer eventos disponibles para inscripción
        $consultaEventos = "SELECT *
                            FROM EVENTO
                            WHERE ESTADO != 'Eliminado'
                            ORDER BY TITULO_EVENTO";

        if ($result = $mysql->query($consultaEventos)) {
            $eventos = array();
            while ($row = $result->fetch_assoc()) {
                // Consulta para extraer usuarios matriculados en cada evento
                $consultaUsuariosPorEvento = "SELECT U.USUARIO, U.IMAGEN 
                                              FROM MATRICULACION M 
                                              LEFT JOIN USUARIO U ON M.IDUSUARIO = U.ID
                                              WHERE M.IDEVENTO = ?
                                              AND M.FECHA_ANULADO IS NULL";
                $pr = $mysql->prepare($consultaUsuariosPorEvento);
                $pr->bind_param("i", $row['ID']);
                $pr->execute();
                $resultadoUsuarios = $pr->get_result();
                $usuarios = array();
                while ($usuario = $resultadoUsuarios->fetch_assoc()) {
                    $usuarios[] = array(
                        "nombreUsuario" => $usuario['USUARIO'],
                        "imagenUsuario" => $usuario['IMAGEN']
                    );
                }
                $eventos[] = array(
                    "idevento" => $row['ID'],
                    "nombreEvento" => $row['TITULO_EVENTO'],
                    "estadoEvento" => $row['ESTADO'],
                    "descripcionEvento" => $row['DESCRIPCION_EVENTO'],
                    "numeroparticipantes" => $row['NUMEROMAXPARTICIPANTES'],
                    "precio" => $row['PRECIO'],
                    "fechainicioinscripcion" => $row['FECHA_INICIO_INSCRIPCION'],
                    "fechaInicio" => $row['FECHA_INICIO'],
                    "fechaFin" => $row['FECHA_FIN'],
                    "imagenevento" => $row['IMAGENEVENTO'],
                    "usuarios" => $usuarios
                );
                $resultadoUsuarios->free();
            }
            $result->free();
            echo json_encode($eventos);
        } else {
            echo json_encode(array("error" => "Error en la consulta de eventos"));
        }
    }

    public function obtenerEventoPorUsuario($id)
    {
        include 'ConexionBD.php';
        // Consulta para extraer eventos disponibles para inscripción
        $consultaEventos = "SELECT M.IDMATRICULACION, E.ID, E.TITULO_EVENTO, E.DESCRIPCION_EVENTO, E.ESTADO, E.NUMEROMAXPARTICIPANTES, E.PRECIO, E.FECHA_INICIO_INSCRIPCION,
                            E.FECHA_INICIO, E.FECHA_FIN, E.IMAGENEVENTO
                            FROM EVENTO E INNER JOIN MATRICULACION M ON E.ID = M.IDEVENTO
                            WHERE M.IDUSUARIO = $id
                            AND M.FECHA_ANULADO IS NULL
                            AND E.ESTADO != 'En curso'";

        if ($result = $mysql->query($consultaEventos)) {
            $eventos = array();
            while ($row = $result->fetch_assoc()) {
                $eventos[] = array(
                    "idmatriculacion" => $row['IDMATRICULACION'],
                    "evento" => array(
                        "idevento" => $row['ID'],
                        "nombreEvento" => $row['TITULO_EVENTO'],
                        "estadoEvento" => $row['ESTADO'],
                        "descripcionEvento" => $row['DESCRIPCION_EVENTO'],
                        "numeroparticipantes" => $row['NUMEROMAXPARTICIPANTES'],
                        "precio" => $row['PRECIO'],
                        "fechainicioinscripcion" => $row['FECHA_INICIO_INSCRIPCION'],
                        "fechaInicio" => $row['FECHA_INICIO'],
                        "fechaFin" => $row['FECHA_FIN'],
                        "imagenevento" => $row['IMAGENEVENTO']
                        )
                );
            }
            $result->free();
            echo json_encode($eventos);
        } else {
            echo json_encode(array("error" => "Error en la consulta de eventos"));
        }
    }

    public function inscripcion($idUsuario, $idEvento){
        include 'ConexionBD.php';
        $fechaActual = date("y-m-d");
        $estado = "Confirmada";
        $inscripcionSQL = "INSERT INTO MATRICULACION(IDEVENTO, IDUSUARIO, FECHA_MATRICULACION, ESTADO) VALUES (?,?,?,?)";
        $prepare = $mysql->prepare($inscripcionSQL);
        $prepare -> bind_param("iiss", $idEvento, $idUsuario, $fechaActual, $estado);
        if($prepare->execute()){
            # ACTUALIZAR EL LOG PARA REGISTRAR QUE SE HA INSCRITO EN UN NUEVO EVENTO
            $registroLog = "INSERT INTO REGISTROLOG(MENSAJE, ID_USUARIO, ID_EVENTO) VALUES (?,?,?)";
            $prepared = $mysql->prepare($registroLog);
            $mensaje = "El usuario se ha inscrito en un evento";
            $prepared->bind_param("sii", $mensaje, $idUsuario, $idEvento);
            $prepared->execute();
            echo json_encode(array("mensaje" => "Inscrito correctamente en el evento", "codigo" => 1));
        }else{
            echo json_encode(array("mensaje" => "Error inscribiendote al evento", "codigo" => 0));
        }
    }


    public function abandonarEvento($idUsuario, $idMatriculacion){
        include 'ConexionBD.php';
        $fechaActual = date("y-m-d");
        // Consulta para modificar el eventos que deseas eliminar
        $consultaEventos = "UPDATE MATRICULACION SET FECHA_ANULADO = ?
                            WHERE IDMATRICULACION = ?";
        $prepare = $mysql->prepare($consultaEventos);
        $prepare->bind_param("si", $fechaActual, $idMatriculacion);
        
        if($prepare->execute()){
             # ACTUALIZAR EL LOG PARA REGISTRAR QUE SE DADO DE BAJA DE UN EVENTO
             $registroLog = "INSERT INTO REGISTROLOG(MENSAJE, ID_USUARIO, ID_EVENTO) VALUES (?,?,?)";
             $prepared = $mysql->prepare($registroLog);
             $mensaje = "El usuario se ha dado de baja del evento";
             $prepared->bind_param("sii", $mensaje, $idUsuario, $idEvento);
             $prepared->execute();
            echo json_encode(array("mensaje" => "Dado de baja correctamente en el evento", "codigo" => 1));
        }else{
            echo json_encode(array("mensaje" => "Error dando de baja al evento", "codigo" => 0));
        }
    }

    public function recuperarEvento($idMatriculacion){
        include 'ConexionBD.php';
        $fechaActual = date("y-m-d");
        // Consulta para recuperar el eventos eliminado
        $consultaEventos = "UPDATE MATRICULACION SET FECHA_ANULADO = null
                            WHERE IDMATRICULACION = ?";
        $prepare = $mysql->prepare($consultaEventos);
        $prepare->bind_param("i", $idMatriculacion);
        
        if($prepare->execute()){
            echo json_encode(array("mensaje" => "Evento recuperado", "codigo" => 1));
        }else{
            echo json_encode(array("mensaje" => "Error recuperando evento", "codigo" => 0));
        }
    }
}
