<?php

    include '../cors.php';
    require '../../models/Nodo.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $user = new Nodo();
    // $res = $user->getNewAccount("Wallet", 3);
    // $res = $user->newAccountPayments(2);
    // $res = $user->generarAllAddressEBG("EBGWallet");
    $res = $user->getNewAccountEBG("EBGWallet", 0);
    
    // $res = $user->getTransaction("c8cc4a98d39b72c20c03f2ed59c731bc156c72383916f7bf822921a60b81bd71");
    // $res = $user->sendTransactionByOne(array("address_from"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to"=>"2N5zz3vBPos31fUfMc3Zqi5DpEh5F3UAECB", "amount"=>"0.0001", "reason"=>"probando envios de btc", "usuario"=>"0"));
    // $res = $user->verificarConexion();
    
    // $res = $user->obtenerCuentas();
    echo json_encode($res);