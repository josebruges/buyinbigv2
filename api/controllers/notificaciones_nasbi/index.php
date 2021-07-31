<?php
    include '../cors.php';
    require '../../models/notificaciones_nasbi.php';
    require '../../models/conexion.php';
    // require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['insertar_notificacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $notificaciones = new Notificaciones();
        $res = $notificaciones->insertarNotificacion($data);
        unset($notificaciones);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['notificaciones_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
           if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $notificaciones = new Notificaciones();
        $res = $notificaciones->verNotificacionesUsuario($data);
        unset($notificaciones);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['marcar_como_leida'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $notificaciones = new Notificaciones();
        $res = $notificaciones->notificacionLeida($data);
        unset($notificaciones);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['notificar_desde_p2w'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $notificaciones = new Notificaciones();
        $res = $notificaciones->insertarNotificacion($data);
        unset($notificaciones);
        echo json_encode($res);
        return 0;
    }

    function validarPostData($res)
    {
        $data = null;
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);
        
        if(isset($_POST[$res])) $data = $_POST[$res];
        
        else if(isset($postdata) && isset($request->$res)){
            $data = $request->$res;
            $data = json_encode($data);
            $data = json_decode($data, true);
        }
        return $data;
    }

    function llamar_validar_token(Array $data){
        //  return ensureAuth($data["uid"], $data["empresa"]); 
         return true; 
        }
?>