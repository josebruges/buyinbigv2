<?php

    include '../cors.php';
    require '../autenticated.php';
    require '../../models/empresas.php';

    
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['registrar_empresa'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->registrarEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ver'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->verEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['login'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->loginEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> $data));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token([
            "uid"=> $data["id"],
            "empresa"=>  1
        ]) == false) return 0; 
       //fin validacion token 
        $empresas = new Empresas();
        $res = $empresas->actualizarEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['personalizar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> $data));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token([
            "uid"=> $data["id"],
            "empresa"=>  1
        ]) == false) return 0; 
       //fin validacion token 
        $empresas = new Empresas();
        $res = $empresas->personalizarEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['actualizar_clave'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token([
            "uid"=> $data["id"],
            "empresa"=>  1
        ]) == false) return 0; 
       //fin validacion token 
        $empresas = new Empresas();
        $res = $empresas->cambiarPassEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['confirmar_empresa'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->confirmarEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['confirmar_empresa_code'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->confirmarEmpresaCode($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['home'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->home($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['generar_token'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->generarToken($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['restablecer_clave'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->restablecerPassword($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['insertar_producto_destacado'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->insertarProductosDestacadosEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['productos_destacados'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->productosDestacadosEmpresa($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['solicitud_activar_subastas'])) {
        // A través de este servicio a la empresa informa que desea activar subastas.
        // Debe pasar por un proceso de revisión para que dicha opcion
        // le sea permitida o habilitada.
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->solicitudActivarSubastas($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['darse_de_baja'])) {
        // A través de este servicio a la empresa informa que desea activar subastas.
        // Debe pasar por un proceso de revisión para que dicha opcion
        // le sea permitida o habilitada.
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            //   if(llamar_validar_token([
            //     "uid"=> $data["id"],
            //     "empresa"=>  1
            // ]) == false) return 0; 
           //fin validacion token 
        $empresas = new Empresas();
        $res = $empresas->darmeDeBaja($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['darse_de_alta'])) {
        // A través de este servicio a la empresa informa que desea activar subastas.
        // Debe pasar por un proceso de revisión para que dicha opcion
        // le sea permitida o habilitada.
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->darmeDeAlta($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['generarWalletsFaltantes'])) {
        // A través de este servicio a la empresa informa que desea activar subastas.
        // Debe pasar por un proceso de revisión para que dicha opcion
        // le sea permitida o habilitada.
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->crearAddressFaltantes($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_ciiu'])){

        $empresas = new Empresas();
        $res = $empresas->obtener_ciiu();
        unset($empresas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['existencia_correo'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->saber_existencia_correo($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['get_code_md5'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->getCodeMD5($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['obtener_informacion_del_negocio'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->obtenerInformacionDelNegocio($data);
        unset($empresas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['reenviar_codigo'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $empresas = new Empresas();
        $res = $empresas->reenviarCodigo($data);
        unset($empresas);
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