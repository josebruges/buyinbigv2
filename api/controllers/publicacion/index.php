<?php

    include '../cors.php';
    include '../error.php';
    require '../../models/publicacion.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['clonar_publicacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->clonarPublicacion($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['publicar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->publicar($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['mis_publicaciones'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            // if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->misPublicaciones($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['publicacion_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->publicacionId($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['publicacion_revision'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->publicacionRevisionId($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['fotos_producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->fotosproductoId($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['mis_subastas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        // if(llamar_validar_token($data) == false) return 0;
        //fin validacion token 

        $vender = new Publicacion();
        $res = $vender->misSubastas($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['editar_publicacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
       //  if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->editarPublicacion($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['validar_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
           //validacion token 
           if(llamar_validar_token($data) == false) return 0; 
           //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->validarSubastaMonedaLocal($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pausar_publicacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        
        $vender = new Publicacion();
        $res = $vender->pausarPublicacion($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['activar_publicacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->activarPublicacion($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['eliminar_publicacion'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->eliminarPublicacion($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['rango_subastas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data - controller', 'data'=> $data));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->getRangoSubastas($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['restricciones_publicar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->getRestriccionesPublicar($data);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['validar_primer_articulo'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->validarPrimerArticulo($data);
        echo json_encode($res);
        return 0;
    }


    // INICIO - Aramis
    if(isset($_GET['crear_color'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->crearColor($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['crear_talla'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->crearTalla($data);
        echo json_encode($res);
        return 0;
    }    

    if(isset($_GET['obtener_tallas'])){
        $vender = new Publicacion();
        $res = $vender->getTallas();
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_colores'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        // $res = $vender->getColores($data);
        $res = $vender->getColores($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['get_colores_tallas'])){
        // $data = validarPostData('data');
        // if(!isset($data)){
        //     echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
        //     return 0;
        // }
        $publicacion = new Publicacion();
        $res = $publicacion->getColoresTallas();
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['guardar_producto_colores_tallas'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->guardarProductoColoresTallas($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['prueba_guardado'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->prueba_de_guardado($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['prueba_notificacion'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->prueba_de_notificacion($data);
        // $res = $vender->getColores2Prueba($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['prueba_nueva_paginacion_colores'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->getColores2Prueba($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['editar_colores_tallas'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->prueba_de_actualizacion($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['publicar_version_nueva'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->publicarVersion2($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_pares_producto_colores_tallas'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->obtener_pares_producto_colores_tallas($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['agregar_nuevo_editar'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->agregar_nuevo_editar($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_productos_tallas_editar'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->obtener_productos_tallas_editar($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['prueba_obtener_permisos'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->verificar_usuario_permisos_publicar($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['verificar_permiso_usuario'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->verificarPermisoPublicarUsuario($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['productos_espera_validacion'])){ //no se usa 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        // //validacion token 
        //  if(llamar_validar_token($data) == false) return 0; 
        // //fin validacion token 
        $vender = new Publicacion();
        $res = $vender->productos_espera_verificacion($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['rechazar_producto'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->rechazar_producto($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['aceptar_producto'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->aceptar_producto($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['activar_permisos_publicacion'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->dar_permisos_publicacion($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['remover_permisos_publicacion'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->remover_permisos_publicacion($data);
        echo json_encode($res);
        return 0;
    }

    // FIN - Aramis

    // BACKEND

    if(isset($_GET['obtener_todos_espera_revision'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->obtener_publicaciones_espera_revison_bakcend($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_producto_backend'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->obtener_datos_producto($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_subastas_backend'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->obtener_subastas_backend($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['obtener_subasta_backend'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->obtener_subasta_backend($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['subastas_prox_iniciar'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->subastas_proximas_iniciar($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['usuarios_sin_permisos'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $info = new Publicacion();
        $res = $info->obtener_usuarios_sin_permiso($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['empresas_sin_permisos'])){
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $info = new Publicacion();
        $res = $info->obtener_empresas_sin_permiso($data);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['cron_revision'])){
        $info = New Publicacion();
        $res = $info->cronRevision();
        echo json_encode($res);
        return $res;
    }


    if(isset($_GET['tratar_banner'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $vender = new Publicacion();
        $res = $vender->tratar_banner($data);
        echo json_encode($res);
        return 0;
    }

    // FIN BACKEND

    // echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
    // return 0;

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