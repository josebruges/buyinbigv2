<?php
    include '../cors.php';
    require '../../models/users.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_POST['url'])) {
  
        $tree = new Users();
        $user = $_POST['user'];  
        $url= $_POST['url'];
        $res = $tree->imagen($url,$user);
        echo json_encode($res);
        return 0;
    }
    echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null))."arrrayyyy";
    return 0;
    
?>