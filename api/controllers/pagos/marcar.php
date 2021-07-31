<?php

include '../cors.php';
require '../../models/pagos.php';
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
//print_r($post);
if ($datos == null) {
    $reques = $post;
} else {
    $reques = $datos;
}

$pagos = new Pagos();
// if ($reques["plan"] == 1 || $reques["plan"] == 2 || $reques["plan"] == 3) {
//     $res = $pagos->agregarPlan($reques["usuario"], $reques["plan"]);
// } else if ($reques["plan"] == 4 || $reques["plan"] == 5 || $reques["plan"] == 6) {
//     $res = $pagos->reconsumir($reques["usuario"], $reques["plan"]);
// } else {
//     $res = $pagos->upgradePlan($reques["usuario"], $reques["plan"]);
// }

// $res = $pagos->marcarPago(223, 0.01293333, 1, 1, 300000, 388);
$res = $pagos->marcarPago($reques["usuario"], $reques["monto"], $reques["plan"], $reques["membresia"], $reques["precio_usd"], $reques["monto_usd"]);
 //$res = $pagos->ejecutarPago("asdsaduasduasgdygasd", "2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", 0.0001);
// $pagos->terminarCursos();
//$res = $pagos->agregarRegistroMensual(367, 2);
//print_r($reques);
/*
$pagos->agregarTickets(940, 20, 300, 1);
$pagos->agregarTickets(940, 20, 500, 1);
$pagos->agregarTickets(940, 20, 1000, 1);
$pagos->agregarTickets(940, 20, 5000, 1);
*/
// $res = $pagos->ejecutarPago(, $reques["address"], $reques["amount"]);


// $res = $pagos->reconsumir("80", "1");
// $res = $pagos->pagarSuperiores("44");
// $res = $pagos->upgradePlan("51", 9);

echo json_encode($res);
