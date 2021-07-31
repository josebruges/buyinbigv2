<?php

    include '../cors.php';
    require '../../models/carrito.php';
    require '../autenticated.php';
    
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    
    if(isset($_GET['agregar_masivo_developer'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $carrito = new Carrito();
        $res = $carrito->agregarMasivoDeveloper($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['agregar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $carrito = new Carrito();
        $res = $carrito->agregar($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['agregar_de_no_logueado'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $carrito = new Carrito();
        $res = $carrito->agregarNoLogueado($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['carrito_usuario_add_no_logeado'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $carrito = new Carrito();
        $res = $carrito->agregarDataDeNoLogueado($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['eliminar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $carrito = new Carrito();
        $res = $carrito->eliminar($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['eliminar2'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $carrito = new Carrito();
        $res = $carrito->deleteCarrito($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $carrito = new Carrito();
        $res = $carrito->actualizar($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['carrito_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token
        $carrito = new Carrito();
        $res = $carrito->carritoUsuario($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['contar_carrito_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
           if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        
        $carrito = new Carrito();
        $res = $carrito->contarCarritoUsuario($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['carrito_usuario_no_logeado'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        
        $carrito = new Carrito();
        $res = $carrito->carritoUsuarioNoLogeado($data);
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

        $carrito = new Carrito();
        $res = $carrito->rutasEnvio($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pagar_carrito'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        //  if(llamar_validar_token([
        //     "uid"=> $data["carrito"]["uid"],
        //     "empresa"=>  $data["carrito"]["empresa"]
        // ]) == false) return 0;
       //fin validacion token 
        
        $carrito = new Carrito();
        $res = $carrito->pagarCarrito($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['direccion_vendedor'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        
        $carrito = new Carrito();
        $res = $carrito->direccionVendedorCarrito($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['get_reporte_subastas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        
        $carrito = new Carrito();
        $res = $carrito->getReporteSubastas($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['envio_correo_confirmar_compra'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        
        $carrito = new Carrito();
        $res = $carrito->envio_correo_confirmar_compra($data);
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
          return ensureAuth($data["uid"], $data["empresa"]); 
         //return true; 
        }
    
?>