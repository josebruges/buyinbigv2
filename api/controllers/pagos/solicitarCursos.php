<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    include '../cors.php';
    require '../../models/pagos.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $pagos = new Pagos();
    //$res = $pagos->agregarCursos(857, 1);
    $res = $pagos->agregarTicketsArticulo(882, 11);
    // $res = $user->Login(array("user"=>"mario", "password"=>"1234"));
    echo json_encode($res);