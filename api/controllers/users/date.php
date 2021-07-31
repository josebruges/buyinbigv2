<?php

    /* include '../cors.php'; */
    require '../../models/users.php';
    $user = new Users();
    $res = $user->Date();
    $resp = array('status'=>'success','data' => $res);
    echo json_encode($resp);