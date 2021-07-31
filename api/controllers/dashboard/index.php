<?php
    include '../cors.php';
    require '../../models/dashboard.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['mapchart'])) {
        $dashboard = new Dashboard();
        $res = $dashboard->mapchart();
        echo json_encode($res);
        return 0;
    }
    echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
    return 0;
    
?>