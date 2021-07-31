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
// $res = $pagos->reconsumir(174, 4, 30000);
//$res = $pagos->agregarPlan(285, 1, 30000);
$res = $pagos->agregarTickets(877, 1, 500, 1);


echo json_encode($res);
