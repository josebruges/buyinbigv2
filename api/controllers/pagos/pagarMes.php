
<?php

include '../cors.php';
require '../../models/pagos.php';


ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
//print_r($post);
$pagos = new Pagos();
// if ($reques["plan"] == 1 || $reques["plan"] == 2 || $reques["plan"] == 3) {
//     $res = $pagos->agregarPlan($reques["usuario"], $reques["plan"]);
// } else if ($reques["plan"] == 4 || $reques["plan"] == 5 || $reques["plan"] == 6) {
//     $res = $pagos->reconsumir($reques["usuario"], $reques["plan"]);
// } else {
//     $res = $pagos->upgradePlan($reques["usuario"], $reques["plan"]);
// }

// $res = $pagos->marcarPago(223, 0.01293333, 1, 1, 300000, 388);
//$res = $pagos->terminarCursos();
 $res = $pagos->terminarMes();
// $res = $pagos->ejecutarPago("asdsaduasduasgdygasd", "2N8yasjFyPi6XHhRXZGSsohDTANuMMKLsxZ", 0.0001);
// print_r($res);
// $res = $pagos->ejecutarPago(, $reques["address"], $reques["amount"]);


// $res = $pagos->reconsumir("80", "1");
// $res = $pagos->pagarSuperiores("44");
// $res = $pagos->upgradePlan("51", 9);

echo json_encode($res);
