<?php

    include '../cors.php';
    require '../../models/pagos.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $historial = new Pagos();
    $res = $historial->agregarTickets(367, 1000, 300, 3);
    echo json_encode($res);