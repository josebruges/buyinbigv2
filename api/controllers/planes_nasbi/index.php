<?php
    include '../cors.php';
    require '../../models/planes_nasbi.php';
    require '../autenticated.php';
    
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    $datos = json_decode(file_get_contents("php://input"),TRUE);


    if(isset($_GET['ver_tickets'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->ticketsParaCompra($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ver_planes'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $planes = new PlanesNasbi();
        $res = $planes->verPlanes($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['ver_planes_compra'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->verPlanesCompra($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['gernerar_orden_pago'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->generarPayU($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['detalle_payu'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->detallePayU($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pagar_plan'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
         //validacion token 
         if(llamar_validar_token($data) == false) return 0; 
         //fin validacion token 
        $planes = new PlanesNasbi();
        $res = $planes->pagarPlan($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pagar_plan_master'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->pagarPlan($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['pagar_ticket_compra'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->pagarTicketCompra($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['insertar_ticket_p2w'])) {
        $data = validarPostData('data'); 
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $planes = new PlanesNasbi();
        $res = $planes->insertarTicketP2W($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['tickets_usuario'])) {
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $planes = new PlanesNasbi();
        $res = $planes->ticketsUsuario($data);
        unset($planes);
        echo json_encode($res);
        return 0;
    }
    if(isset($_GET['tickets_usuario_historico'])) {
        $data = validarPostData('data');
        //print_r($data);
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        //validacion token 
        if(llamar_validar_token($data) == false) return 0; 
        //fin validacion token 
        $planes = new PlanesNasbi();
        $res = $planes->ticketsUsuarioHistorico($data);
        unset($planes);
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