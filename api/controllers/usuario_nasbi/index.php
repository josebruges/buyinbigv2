<?php

    include '../cors.php';
    require '../../models/usuario_nasbi.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);


    if(isset($_GET['login'])) {
        $postdata = json_decode(file_get_contents("php://input"),TRUE);
        $data = validarPostData();
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $usuario_nasbi = new UsuarioNasbi();
        $res = $usuario_nasbi->loginUsuario($data);
        unset($usuario_nasbi);
        echo json_encode($res);
        return 0;
    }
    if( isset($_GET['recuperar_password']) ){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data.....', 'data'=> null));
            return 0;
        }

        $usuario = new UsuarioNasbi();
        $res = $usuario->recuperarPassword($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['datos_venta_persona_natural'])){
        // GET - TRAE LA INFORMACIÓN
        $usuario = new UsuarioNasbi();
        $res = $usuario->datos_venta_persona_natural();
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['completar_datos_persona_natural'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->completar_datos_persona_natural($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['completar_datos_persona_natural_rut_doc'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        // if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->completar_datos_persona_natural_rut_doc($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['completar_datos_persona_natural_cedula_doc'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        // if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->completar_datos_persona_natural_cedula_doc($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['completar_datos_persona_natural_certificado_bancario_doc'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        // if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->completar_datos_persona_natural_certificado_bancario_doc($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar_datos_persona_natural'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->actualizar_datos_persona_natural($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['obtener_datos_persona_natural_by_user'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->obtener_datos_persona_natural_by_user($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }

    //juridica
    if(isset($_GET['completar_datos_persona_juridica'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->completar_datos_persona_juridica($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['agregar_pdf_base'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->cargar_pdf_juridica($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }


    if(isset($_GET['obtener_datos_persona_juridica_by_user'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        // if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->obtener_datos_persona_juridica_by_user($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar_datos_persona_juridica'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->actualizar_datos_persona_juridica($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['saber_todos_los_campos_no_ready'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->consultar_campos_faltantes($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['traer_natural_revision'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        // if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->traer_usuarios_natural_en_revision($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['envio_correo_transacional'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }

        if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->correo_de_promocion_pass_transaccional($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['traer_juridica_revision'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        //if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->traer_usuarios_juridica_en_revision($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }


    if(isset($_GET['insertar_revision_natural'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        //if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->insercion_revision_natural_cambio($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }


    if(isset($_GET['insertar_revision_juridica'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        //if(llamar_validar_token($data) == false) return 0;

        $usuario = new UsuarioNasbi();
        $res = $usuario->insercion_revision_juridica_cambio($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }



    if(isset($_GET['activar_ciiu_temporal'])){
        //BORRRAR

        $usuario = new UsuarioNasbi();
        $res = $usuario->eliminar_person_temporal();
        echo json_encode($res);
        return 0;
    }



    if(isset($_GET['get_estadistica'])){
        $data = validarPostData2('data');
        if(!isset($data)){
            echo json_encode(array('status' => 'fail', 'message' => 'No llego información al ws.', 'data' => $data));
            return 0;
        }
        $usuario = new UsuarioNasbi();
        $res = $usuario->getEstadisticas($data);
        unset($direcciones);
        echo json_encode($res);
        return 0;
    }

    // fin juridica
    function validarPostData()
    {
        $data = null;
        $postdata = json_decode(file_get_contents("php://input"),TRUE);
        
        if(isset($_POST)) $data = $_POST;
        if(isset($postdata)) $data = $postdata;
        return $data;
    }
    function validarPostData2($res)
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
        // return ensureAuth($data["uid"], $data["empresa"]);
        return true;
    }

?>