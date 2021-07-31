<?php

    include '../cors.php';
    require '../../models/Nodo.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new Nodo();
    // $res = $user->crearCuentaNueva(1, "Wallet");
    // $res = $user->getTransaction("c8cc4a98d39b72c20c03f2ed59c731bc156c72383916f7bf822921a60b81bd71");
    // $res = $user->sendTransactionByOne(array("address_from"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to"=>"2N5zz3vBPos31fUfMc3Zqi5DpEh5F3UAECB", "amount"=>"0.0001", "reason"=>"probando envios de btc", "usuario"=>"0"));
    // print_r($reques);
    if ($user->buscarAddress($reques["address_from"], $reques["usuario"], $reques["coin"])) {
        if (strtoupper($reques["coin"]) == "BTC") {
            $res = $user->sendTransactionByOne($reques);
        } else {
            $res = $user->sendTransactionByEBG($reques);
        }
    } else {
        $res = array("status"=>"error", "message"=>"la wallet de la que estas tratando de enviar no coincide con los datos proporcionados");
    }
    
    // $res = $user->verificarConexion();
    
    // $res = $user->obtenerCuentas();
    echo json_encode($res);