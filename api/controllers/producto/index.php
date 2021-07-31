<?php
    include '../cors.php';
    require '../../models/producto.php';
    require '../autenticated.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['home'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->home($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['filtros_productos'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->filtrosProductos($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->productoId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['producto_colores_tallas'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->productoIdColoresTallas($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['similares'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->similaresId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['productos_vendedor'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->productosUserId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['fotos_producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->fotosproductoId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['preguntas_producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->pqrproductoId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['preguntar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
          if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $producto = new Producto();
        $res = $producto->preguntar($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['responder'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token
        $producto = new Producto();
        $res = $producto->responder($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['calificaciones_producto'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->calificacionesproductoId($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['banner_home'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->bannerHome($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['puede_pedir_fotos'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token([
            "uid"=> $data["uid_cliente"],
            "empresa"=>  $data["empresa_cliente"]
        ]) == false) return 0; 
       //fin validacion token 
        $producto = new Producto();
        $res = $producto->puedePedirFoto($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pedir_fotos'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
         if(llamar_validar_token([
            "uid"=> $data["uid_cliente"],
            "empresa"=>  $data["empresa_cliente"]
        ]) == false) return 0; 
       //fin validacion token 
        $producto = new Producto();
        $res = $producto->pedirFoto($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['filtros_productos2'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->filtrosProductos2($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['listado_empresas_oficiales_filtro'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto = new Producto();
        $res = $producto->traer_listado_empresa_oficiales_filtro($data);
        unset($producto);
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
        // return ensureAuth($data["uid"], $data["empresa"]); 
        return true; 
    }
    
?>