<?php
    include '../cors.php';
    require '../../models/tarjetas_bancarias.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['crear'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tarjetas_bancarias = new TarjetasBancarias();
        $res = $tarjetas_bancarias->crear($data);
        unset($tarjetas_bancarias);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['tarjetas_bancarias_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tarjetas_bancarias = new TarjetasBancarias();
        $res = $tarjetas_bancarias->tarjetasUsuario($data);
        unset($tarjetas_bancarias);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['eliminar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tarjetas_bancarias = new TarjetasBancarias();
        $res = $tarjetas_bancarias->eliminar($data);
        unset($tarjetas_bancarias);
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
    
?>