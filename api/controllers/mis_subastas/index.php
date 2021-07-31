<?php

    include '../cors.php';
    require '../../models/mis_subastas.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['mis_subastas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $missubastas = new MisSubastas();
        $res = $missubastas->vermisSubastas($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ver_puja'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         $data["empresa"]=0; 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $missubastas = new MisSubastas();
        $res = $missubastas->verPuja($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['bloquear_direcciones'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $missubastas = new MisSubastas();
        $res = $missubastas->bloquearDirecciones($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pujar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        // //validacion token 
        // $data["empresa"]=0; 
        // if(llamar_validar_token($data) == false) return 0; 
        // //fin validacion token 
        $missubastas = new MisSubastas();
        $res = $missubastas->pujar($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['finalizar_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $missubastas = new MisSubastas();
        $res = $missubastas->finalizarSubasta($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['comenzar_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $missubastas = new MisSubastas();
        $res = $missubastas->comenzarSubasta($data);
        unset($missubastas);
        echo json_encode($res);
        return 0;
    }
    echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
    return 0;

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