<?php
    include '../cors.php';
    require '../../models/tcc.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);


    if(isset($_GET['tracking'])) { //wbs que llama a tracking 
        //  $data = validarPostData('data');
        var_dump("hiii"); 
        //     if(!isset($data)){
        //         echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
        //         return 0;
        //     }
        //     $tcc = new Tcc();
        //     $res = $tcc->tracking($data);
        //    // $res = $tcc->tracking();
        //     $res= json_encode($res); 
        //     echo($res);
        //     return 0;
    }

    if(isset($_GET['consultar_liquidacion'])) {
        //A consultar liquidaciones 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tcc();
        $res = $tcc->consultar_liquidacion_wbs_tcc($data);
        //$res = $tcc->consultar_liquidacion();
        $res= json_encode($res); 
        echo($res);
        return 0;
    }
    
    if(isset($_GET['grabar_despacho'])) {
        //wbs que llama a tracking 
        $data = validarPostData('data');
        if(!isset($data)){
            echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
            return 0;
        }
        $tcc = new Tcc();
        $res = $tcc->grabar_despacho($data);
        //$res = $tcc->grabar_despacho();
        $res= json_encode($res); 
        echo($res);
        return 0;
    }

    function validarPostData($res){
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