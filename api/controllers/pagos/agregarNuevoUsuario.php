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

// $res = $pagos->marcarPago("35", 0.0001, 4, 0, 10000, 1);
// $res = $pagos->ejecutarPago("asdsaduasduasgdygasd", "2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", 0.0001);
print_r($reques);
$res = $pagos->agregarNuevoUsuario(168);


// $res = $pagos->reconsumir("80", "1");
// $res = $pagos->pagarSuperiores("44");
// $res = $pagos->upgradePlan("51", 9);

echo json_encode($res);
