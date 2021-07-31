<?php

    include '../cors.php';
    require '../../models/ventas.php';
    require '../autenticated.php';
    
    $datos = json_decode(file_get_contents("php://input"),TRUE);
   
    if(isset($_GET['mis_ventas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->misVentas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['confirmar_venta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->confirmarVenta($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['confirmar_comprobante'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->confirmarComprobante($data);
        unset($ventas);
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
        $ventas = new Ventas();
        $res = $ventas->rutasEnvio($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }
    
    if(isset($_GET['realizar_envio'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->realizarEnvio($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['confirmar_devolucion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->confirmarDevolucion($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['calificar_comprador'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->calificarComprador($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['resumen_mensual'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->resumenMensualVentas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['ingresos_mensuales'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->ingresosMensualVentas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['ingresos_mensuales_paginacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->ingresosMensualVentasPaginado($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['resumen_ventas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->resumenVentas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['facturacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 

        $ventas = new Ventas();
        $res = $ventas->facturacionVentas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }


    if(isset($_GET['referido_venta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $ventas = new Ventas();
        $res = $ventas->traer_referidos_y_ventas($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['datosestadistica'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
      //   if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $ventas = new Ventas();
        $res = $ventas->datos_para_estadistica_cards($data);
        unset($ventas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['actualizar_timelines_transacciones'])) {
        // Creación 27 abril 2021 - probar

        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'No ha llegado información al servicio solicitado.', 'data'=> $data));
            return 0;
        }
        $ventas = new Ventas();
        $res = $ventas->actualizarTimelinesTransacciones($data);
        unset($ventas);
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