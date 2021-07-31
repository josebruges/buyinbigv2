<?php
    include '../cors.php';
    require '../../models/tree.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['mytree'])) {
        // if(!isset($_POST['user'])){
        //     echo json_encode(array('status'=>'fail', 'message'=>'no user', 'data'=> null));
        //     return 0;
        // }
        $tree = new Tree();
        $user = $_POST['user'];
        $res = $tree->mytree($user);
        echo json_encode($res);
        return 0;
    }
    echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
    return 0;
    
?>