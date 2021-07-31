<?php
    include '../cors.php';
    require '../../models/producto_subastas.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['home'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->home($data);
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['verificar_tiempo_subastas'])) {
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->verificarTiempoSubastas();
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['filtro_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->filtrosSubasta2($data);
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['listar_empresa_oficial_filtro'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->listar_empresa_oficial_filtro($data);
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->subastaId($data, 1);
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['cron_subastas'])) {
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->cronSubastas();
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['inscribir_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->inscribirseSubasta($data);
        unset($producto_subastas);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['ingresar_vista'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
       // if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $producto_subastas = new ProductoSubastas();
        $res = $producto_subastas->insertar_historial_vista_subasta($data);
        unset($producto_subastas);
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