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
$pagos       = new Pagos();

if ($pagos->validarCompraMembresia($reques["usuario"])) {
	$detallePlan = $pagos->obtenerDetallePlan(10);
	$preciousd   = $pagos->getUSDNodo();
	$monto       = number_format(($detallePlan[0]["monto"] / $preciousd), 6);
	$res         = $pagos->marcarPago($reques["usuario"], $monto, 10, 1, $preciousd, $detallePlan[0]["monto"]);
} else {
	$res = array("status"=>"error", "message"=>"No puedes tener mas de una membresia adelantada");
}

echo json_encode($res);
