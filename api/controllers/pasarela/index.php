<?php
    include '../cors.php';
    require '../../models/pasarela.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    
    // inicio prueba api pasarela

    if(isset($_GET['obtener_token_pd'])){
        
        $info = new Pasarela();
        $res = $info->obtener_token_pd();
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['url_test'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Pasarela();
        $res = $info->obtener_id_comision($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['iniciar_transaccion_pse'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->pse_iniciar_transaccion_test($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['check_transaccion_pse'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->pse_check_transaccion_test($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['finalizar_transaccion_pse'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->pse_finalizar_transaction($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['solicitar_pin_sured'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->solicitar_pin_sured($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['solicitar_pin_efecty'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->solicitar_pin_efecty($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['consultar_pin_efecty'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->consultar_pin_efecty($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['consultar_pin_sured'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->consultar_pin_sured($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['token_card_tc'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->token_card_tc($data);
        echo json_encode($res);
        return $res;
    }

    if(isset($_GET['process_transaction_tc'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = New Pasarela();
        $res = $info->process_transaction_tc($data);
        echo json_encode($res);
        return $res;
    }

    // fin prueba api pasarela



    // echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
    // return 0;

    function validarPostData($res)
    {
        $data = null;
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);
        
        if(isset($_POST[$res])) $data = $_POST[$res];

        else if($postdata && $request->$res){
            $data = $request->$res;
            $data = json_encode($data);
            $data = json_decode($data, true);
        }
        
        return $data;
    }

    function llamar_validar_token(Array $data){
        return ensureAuth($data["uid"], $data["empresa"]); 
       // return true; 
        }
    
?>