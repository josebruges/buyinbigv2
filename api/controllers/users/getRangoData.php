<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
    include '../cors.php';
    require '../../models/users.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new Users();
    //print_r($reques);
    //$reques["usuario"] = 297;
    $res = $user->getNextLevel($reques["usuario"]);
    echo json_encode($res);
    //echo json_encode($reques);