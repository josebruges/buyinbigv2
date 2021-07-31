<?php

    include '../cors.php';
    require '../../models/historial-tikets.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $historial = new HistorialTikects();
    $res = $historial->transferirEntradas($reques);
    echo json_encode($res);