<?php
    include '../cors.php';
    require '../../models/datos_comprador.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    print_r(json_decode(file_get_contents("php://input"), true));
    if(isset($_GET['mis_puntos'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $datos_comprador = new DatosComprador();
        $res = $datos_comprador->misPuntos($data);
        unset($datos_comprador);
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
        //return true; 
    }
?>