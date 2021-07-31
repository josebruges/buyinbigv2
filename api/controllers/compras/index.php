<?php

    include '../cors.php';
    require '../../models/compras.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['mis_compras'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $compras = new Compras();
        $res = $compras->misCompras($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['compra_no_concretada'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $compras = new Compras();
        $res = $compras->noConcretadoDeclinarVenta($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['subir_foto_comprobante'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $compras = new Compras();
        $res = $compras->subirFotoComprobante($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['detalle_payu'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $compras = new Compras();
        $res = $compras->detalleRequestPayU($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['confirmar_entrega'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $compras = new Compras();
        $res = $compras->confirmarEntregado($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['confirmar_entregado_bien'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $compras = new Compras();
        $res = $compras->confirmarEntregadoBien($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['rutas_envio'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $compras = new Compras();
        $res = $compras->rutasEnvio($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['devolucion_producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $compras = new Compras();
        $res = $compras->devolucionProducto($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['reportar_compra'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $compras = new Compras();
        $res = $compras->reportarComrpra($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['calificar_vendedor'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $compras = new Compras();
        $res = $compras->calificarVendedor($data);
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