<?php

    include '../cors.php';
    require '../../models/pagos.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $pagos = new Pagos();
    $res = $pagos->solicitarPlan($reques);
    // $res = $user->Login(array("user"=>"mario", "password"=>"1234"));
    echo json_encode($res);