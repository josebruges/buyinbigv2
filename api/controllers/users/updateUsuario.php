<?php

    //include '../cors.php';
    require '../../models/users.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new Users();
    $res = $user->updateUsuario($reques);
    echo json_encode($res);