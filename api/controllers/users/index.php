<?php
    include '../cors.php';
    require '../../models/users.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    
    if(isset($_GET['nacionalidad_registrar'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $usermodel = new Users();
        $res = $usermodel->nacionalidadRegistrar($data);
        unset($usermodel);
        echo json_encode($res);
        return 0;
    }
    
    if(isset($_GET['nacionalidad_id'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
            if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 

        $usermodel = new Users();
        $res = $usermodel->nacionalidadID($data);
        unset($usermodel);
        echo json_encode($res);
        return 0;
    }
    
    if(isset($_GET['notificar_referido'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $usermodel = new Users();
        $res = $usermodel->nacionalidadID($data);
        unset($usermodel);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['getmyplan'])) {
        // if(!isset($_POST['user'])){
        //     echo json_encode(array('status'=>'fail', 'message'=>'no user', 'data'=> null));
        //     return 0;
        // }
        $usermodel = new Users();
        $user = $_POST['user'];
        $res = $usermodel->planesUser($user);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['getreconsumir'])) {
        // if(!isset($_POST['user'])){
        //     echo json_encode(array('status'=>'fail', 'message'=>'no user', 'data'=> null));
        //     return 0;
        // }
        $usermodel = new Users();
        $res = $usermodel->planesreconsumir();
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