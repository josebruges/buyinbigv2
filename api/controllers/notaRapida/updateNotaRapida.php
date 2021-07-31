<?php

    include '../cors.php';
    require '../../models/notaRapida.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $notaR = new NotaRapida();
    $res = $notaR->updateNotaRapida($reques);
    echo json_encode($res);