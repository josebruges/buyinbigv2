<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
    include '../cors.php';
    require '../../models/users.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null)  {
        $reques = $post;
    } else {
        $reques = $datos;
    }
    // else $reques = $datos;
    $user = new Users();
    // $res = $user->verMisWallets(172);
    // print_r($post);
    // print_r($datos);
    // print_r($reques);
    $res = $user->verMisTickets($reques["user"]);
    // $res = $user->agregarWalletTickets(367);
    // $res = $user->agregarTodos();

    // $res = array("status"=>"success");
    echo json_encode($res);