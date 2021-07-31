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
$detallePlan = $pagos->obtenerDetallePlan($reques["plan"]);
if (count($detallePlan) > 0) {

// if ($reques["plan"] == 1 || $reques["plan"] == 2 || $reques["plan"] == 3 || $reques["plan"] == 4 || $reques["plan"] == 5 || $reques["plan"] == 6 || $reques["plan"] == 7 || $reques["plan"] == 8 || $reques["plan"] == 9) {
    if ($pagos->puedoReconsumir($reques["usuario"])) {
        
        $preciousd   = $pagos->getUSDNodo();
        $membresia = 0;
        if ($reques["membresia"] == 1) {
            $membresiaplan = $pagos->obtenerDetallePlan(10);
            $membresia = $membresiaplan[0]["monto"] * 1;
        }
        $monto       = number_format(((($detallePlan[0]["monto"] * 1) +$membresia) / $preciousd), 6);

        $res         = $pagos->marcarPago($reques["usuario"], $monto, $reques["plan"], $reques["membresia"], $preciousd, ($detallePlan[0]["monto"] * 1) + $membresia);
    } else {
        $res = array("status" => "errorMembresia", "message" => "Necesitas tener una membresía activa para poder continuar");
    }

} else {
    $res = array("status" => "error", "message" => "No puedes adquirir este plan");
}

echo json_encode($res);
