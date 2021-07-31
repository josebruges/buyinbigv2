<?php

    include '../cors.php';
    require '../../models/nasbicoin.php';
    require '../autenticated.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['recibir_dinero'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $nasbicoin = new Nasbicoin();
        $res = $nasbicoin->recibirDinero($data);
        unset($nasbicoin);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['wallet_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $nasbicoin = new Nasbicoin();
        $res = $nasbicoin->walletUsuario($data);
        unset($nasbicoin);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['transacciones'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $nasbicoin = new Nasbicoin();
        $res = $nasbicoin->transaccionesUsuario($data);
        unset($nasbicoin);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['diferidos_bloqueados'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $nasbicoin = new Nasbicoin();
        $res = $nasbicoin->diferidoBloqueadosUsuario($data);
        unset($nasbicoin);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['generar_orden_compra'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
          //validacion token 
          if(llamar_validar_token($data) == false) return 0; 
          //fin validacion token 
        $nasbicoin = new Nasbicoin();
        $res = $nasbicoin->generarPayUUsuario($data);
        unset($nasbicoin);
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
       // return ensureAuth($data["uid"], $data["empresa"]); 
       return true; 
      }
    
?>