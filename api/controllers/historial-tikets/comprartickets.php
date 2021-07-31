<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
    include '../cors.php';
    require '../../models/historial-tikets.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $historial = new HistorialTikects();
    $res = $historial->comprarTickets($reques);
    echo json_encode($res);