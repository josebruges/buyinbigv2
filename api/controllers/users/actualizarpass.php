<?php

    include '../cors.php';
    require '../../models/users.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new Users();
    $res = $user->updateRecoverPassword($reques);
    echo json_encode($res);