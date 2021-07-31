<?php
    include '../cors.php';
    require '../../models/liquidar_ventas.php';
    require '../autenticated.php';

    if(isset($_GET['listar_transacciones'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        if(llamar_validar_token($data) == false) return 0; 
        $chat = new Liquidarventas();
        $res = $chat->misVentas_finalizadas($data);
        unset($chat);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar_transacciones'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        if(llamar_validar_token($data) == false) return 0; 
        $chat = new Liquidarventas();
        $res = $chat->actualizarTransaccion($data);
        unset($chat);
        echo json_encode($res);
        return 0;
    }



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
      //  return ensureAuth($data["uid"], $data["empresa"]); 
        return true; 
    }


?>