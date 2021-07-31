<?php
    include '../cors.php';
    require '../../models/tcc_nasbi.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['consultar_valor_envio'])) { 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data controller', 'data'=> null));
            return 0;
        }
        $tcc = new Tccnasbi();
        $res = $tcc->consultar_valor_envio($data);
        $res= json_encode($res); 
        echo($res);
        return 0;
    }

    if(isset($_GET['consultar_valor_envio2'])) { //QUITARR CUANDO FUNCIONE Y SE PASE AL OTRO
        $data = validarPostData('data');
        if(!isset($data)){
           echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
           return 0;
       }
       $tcc = new Tccnasbi();
       $res = $tcc->consultar_valor_envio2($data);
       $res= json_encode($res); 
       echo($res);
       return 0;
    }

    if(isset($_GET['despacho'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tccnasbi();
        $res = $tcc->depacho_envio($data);
        $res= json_encode($res); 
        echo($res);
        return 0;
    }

    if(isset($_GET['seguimiento'])) {
        $data = validarPostData('data');
        if(!isset($data)){
           echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
           return 0;
       }
       $tcc = new Tccnasbi();
       $res = $tcc->seguimiento_product($data);
       $res= json_encode($res); 
       echo($res);
       return 0;
    }

    if(isset($_GET['tracking'])) { 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tccnasbi();
        $res = $tcc->tracking($data);
        $res= json_encode($res); 
        echo($res);
        return 0;
    }

    if(isset($_GET['traer_data_guia'])) { 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tccnasbi();
        $res = $tcc->traer_info_recibida_de_tcc_guia($data);
        $res= json_encode($res); 
        echo($res);
        return 0;
    }
    
    
    if(isset($_GET['truncar'])) { //BORRAR
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tccnasbi();
        $res = $tcc->truncar_tabla($data);
        $res= json_encode($res); 
        echo($res);
        return 0;
    }


    if(isset($_GET['campos_faltantes'])){ //borrar 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
  
        // if(llamar_validar_token($data) == false) return 0;
  
        $usuario = new Tccnasbi();
        $res = $usuario->saber_todos_los_campos_no_ready($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }


    if(isset($_GET['consultar_guia_comprador'])){ //borrar 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
  
        // if(llamar_validar_token($data) == false) return 0;
  
        $usuario = new Tccnasbi();
        $res = $usuario->traer_guias_consulta($data);
        unset($usuario);
        echo json_encode($res);
        return 0;
    }

    function validarPostData($res){
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

?>