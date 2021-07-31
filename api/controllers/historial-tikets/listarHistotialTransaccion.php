<?php

    include '../cors.php';
    require '../../models/historial-tikets.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $historial = new HistorialTikects();
    if ($reques['coin'] == 'BTC') {
        $res = $historial->listarHistotialTransaccion($reques);
    } else if ($reques['coin'] == 'EBG') {
        $res = $historial->listarHistotialTransaccionEBG($reques);
    }
    
    echo json_encode($res);