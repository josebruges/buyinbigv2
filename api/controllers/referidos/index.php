<?php

    include '../error.php';
    include '../cors.php';
    require '../../models/referidos.php';
    require '../autenticated.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['contactar_con_lider'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->contactoConLider($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['referido_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->referUsuario($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['confirmar_lider'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->confirmarLider($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['mis_comisiones_totales_referido'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->misComisionesTotalesReferido($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['empresas_referido'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token([
            "uid"=> $data["referido"],
            "empresa"=>  0
        ]) == false) return 0; 
       //fin validacion token 
        
        $referidos = new Referidos();
        $res = $referidos->empresasreferUsuario($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['historial_ventas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token([
            "uid"=> $data["referido"],
            "empresa"=>  0
        ]) == false) return 0; 
       //fin validacion token 
        $referidos = new Referidos();
        $res = $referidos->historialVentasReferUsuario($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['resumen_mensual'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->resumenMensualReferVentas($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ingresos_mensuales'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->ingresosMensuaReferlVentas($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['obtener_porcentaje_ganacia'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token([
            "uid"=> $data["uid_redsocial"],
            "empresa"=>  $data["empresa_redsocial"]
        ]) == false) return 0; 
       //fin validacion token 
        $referido = new Referidos();
        $res = $referido->obtener_porcentaje_ganacia_productos($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ingresos_mensuales_paginacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->ingresosMensualVentasPaginado($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['calcular_utilidad'])) {
        // $data = validarPostData('data');
        // if(!isset($data)){
        //     echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
        //     return 0;
        // }
        $referidos = new Referidos();
        $res = $referidos->calcularUtilidad();
        unset($referidos);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['buscar_usuario_por_email'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->buscarUsuarioPorEmail($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['buscar_usuario_por_id'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->buscarUsuarioPorId($data);
        unset($referidos);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['actualizar_referido_dev'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $referidos = new Referidos();
        $res = $referidos->actualizarReferidoDev($data);
        unset($referidos);
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
        if(!isset($postdata) || !isset($request->$res)) return $data;

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