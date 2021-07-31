<?php

    include '../cors.php';
    require '../../models/datos_vendedor.php';
    require '../autenticated.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['calificacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->calificacionGeneralVendedor($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['calificacion__total'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->calificacionGeneralVendedorTotal($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['calificacion_paginado'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->calificacionPaginado($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['resumen_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->resumenUsuario($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['clasificacion_data_temp'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->insertDataEjemple($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['clasificacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->clasificacionUsuario($data);
        unset($datos_vendedor);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ventas_gratuitas_realizadas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        
        // if(llamar_validar_token($data) == false) return 0;

        $datos_vendedor = new DatosVendedor();
        $res = $datos_vendedor->ventasGratuitasRealizadas($data);
        unset($datos_vendedor);
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
        // if(isset($postdata) || isset($request->$res)) return null;

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