<?php

include '../cors.php';
require '../../models/pagos.php';
// require '../../models/Nodo.php';
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
//print_r($post);
if ($datos == null) {
    $reques = $post;
} else {
    $reques = $datos;
}

$user = new Pagos();
// $user = new Nodo();
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
// $res = $user->sendTransactionByEBG(array("address_from" => "0xbc4be6aaa1c0d3f0e1f0a8aaca46be22810620e1", "amount"=>9999,"address_to" => "0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "reason" => "Mandando BTC", "usuario" => "0"));
// $res = $user->getBalance("0x372b768ca07c9406ff449ef7cf0cb0d354153b1a");
// $res = $user->pagarEBG(297, 10);


// $res = $user->buscarBloques(772893, 772962);
// $res = $user->reconsumir(367, 4, 30000);
$res = $user->obtenerPataMasCorta(297);


// for ($i=758117;$i<=768117;$i++) {
// 	print_r("$i \n");
// 	$res = $user->getTransactionBlock($i);
// 	print_r($res);
// }

// $res = $user->sendTransactionByOne(array("address_from"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "address_to"=>"2N75EpZcPBWDAtSMyy9zmMD9LrQep3xiiwu", "usuario"=>"0", "amount"=>"0.00001", "reason"=>"pago por reconsumo"));

// $res = $user->probarMarca("2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", "2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", "0.0001");
// $res = $user->verificarConexion();

// $res = $user->obtenerCuentas();
echo json_encode($res);


