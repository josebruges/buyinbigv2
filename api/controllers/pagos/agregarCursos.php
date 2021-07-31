<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
    include '../cors.php';
    require '../../models/pagos.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $pagos = new Pagos();
    $res = $pagos->agregarCursos($reques['user'], $reques['curso']);
    //$res = $pagos->agregarTodosLosTalentMS();
    
    echo json_encode($res);