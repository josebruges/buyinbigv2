<?php

include '../cors.php';
require '../../models/Nodo.php';
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
//print_r($post);
if ($datos == null) {
    $reques = $post;
} else {
    $reques = $datos;
}

$user = new Nodo();
// $res = $user->crearCuentaNueva(1, "Wallet");
// $res = $user->getTransaction("c8cc4a98d39b72c20c03f2ed59c731bc156c72383916f7bf822921a60b81bd71");
$sendTo = array(
"2N7PTwxKQEFx1JAmNfxgHeYuXtWdQwkavTD"=>"0.00000540"//,
// "2MujwJys1H72x9aTbhtnF2wwLZJHPnaWnFJ"=>"0.0000001"//,
// "2N7YWFGEqxVzywe1TnGkFQnYrRPYrhiiDm1"=>"0.0001",
// "2Mzzvm4NHjYo1QSDh2uBGyZ2CLHncspwpPk"=>"0.0001",
//     "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.0001",
//     "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.00011",
//     "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.00012",
//     "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.00013",
//     "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.00014",
// "2NFh9cEbczdUfFeJfeYfaD9PxcVmAfgtt2w"=>"0.0001"
);
$res = $user->sendMultiTransaction(array("address_from" => "2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to" => $sendTo, "reason" => "probando envios de btc", "usuario" => "0"));
// $res = $user->sendTransactionByOne(array("address_from"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to"=>"2N75EpZcPBWDAtSMyy9zmMD9LrQep3xiiwu", "usuario"=>"0", "amount"=>"0.00001", "reason"=>"pago por reconsumo"));

// $res = $user->probarMarca("2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", "2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", "0.0001");
// $res = $user->verificarConexion();

// $res = $user->obtenerCuentas();
echo json_encode($res);


