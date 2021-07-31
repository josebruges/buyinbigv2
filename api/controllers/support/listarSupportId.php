<?php

    include '../cors.php';
    require '../../models/support.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $support = new Support();
    $res = $support->listarSupportId($reques);
    echo json_encode($res);