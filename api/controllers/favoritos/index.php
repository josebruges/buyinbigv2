<?php
    include '../cors.php';
    require '../../models/favorito.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['favoritos_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $producto = new Favorito();
        $res = $producto->favoritosUser($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['favoritos_cantidad'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $producto = new Favorito();
        $res = $producto->countFavoritos($data);
        unset($producto);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['favoritos_verificar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        /* if(llamar_validar_token($data) == false) return 0;  */
        //fin validacion token 
        $producto = new Favorito();
        $res = $producto->verificarFavoritos($data);
        unset($producto);
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
        $producto = new Favorito();
        $res = $producto->eliminarFavorito($data);
        unset($producto);
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
        $producto = new Favorito();
        $res = $producto->agregarFavorito($data);
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
        return ensureAuth($data["uid"], $data["empresa"]); 
      //return true; 
      }
    
?>