<?php

    include '../cors.php';
    require '../../models/historial-tikets.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new HistorialTikects();
    // $res = $user->verMisWallets(172);
    $res = $user->verMisTickets($reques["user"]);
    echo json_encode($res);