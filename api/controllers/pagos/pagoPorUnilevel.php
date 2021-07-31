<?php

include '../cors.php';
require '../../models/pagos.php';


ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
//print_r($post);
if ($datos == null) {
    $reques = $post;
} else {
    $reques = $datos;
}

$pagos = new Pagos();
$res = $pagos->pagoUnilevelMonto($reques["id"]);

echo json_encode($res);
