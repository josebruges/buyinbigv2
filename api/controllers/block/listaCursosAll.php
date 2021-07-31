<?php

    /* include '../cors.php'; */
    require '../../models/block.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $block = new Block();
    $res = $block->listaCursosAll($reques);
    echo json_encode($res);