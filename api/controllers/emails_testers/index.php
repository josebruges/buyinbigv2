<?php
    include '../cors.php';
    require '../../models/emails_testers.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if(isset($_GET['campania_master_send'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->campania_master_send($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['plantilla_registro'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->plantilla_registro($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['promo_correo'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->promo_correo($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['dar_de_alta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->dar_de_alta($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['plantilla_venta_tradicional'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->plantilla_venta_tradicional($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['correos_registro_vender_revision'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->correos_registro_vender_revision($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['plantillas_product_revision'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->plantillas_product_revision($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['compra_tradiccional'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->compra_tradiccional($data);
        unset($emailTesters);
        echo json_encode($res);
        return 0;
    }

    if(isset($_GET['plantilla_venta_por_subasta'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }

        $emailTesters = new EmailsTesters();
        $res = $emailTesters->plantilla_venta_por_subasta($data);
        unset($emailTesters);
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
?>