<?php

    include '../cors.php';
    require '../../models/Nodo.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $txid = $_GET["txid"];
    $user = new Nodo();
    // $res = $user->crearCuentaNueva(1, "Wallet");
    $res = $user->getTransaction($txid);
    // $res = $user->marcarDisponible($txid);
    
    // $res = $user->sendTransactionByOne(array("address_from"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to"=>"2N5zz3vBPos31fUfMc3Zqi5DpEh5F3UAECB", "amount"=>"0.0001", "reason"=>"probando envios de btc", "usuario"=>"0"));
    // $res = $user->verificarConexion();
    
    // $res = $user->obtenerCuentas();
    echo json_encode($res);