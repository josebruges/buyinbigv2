<?php

include '../cors.php';
require '../../models/Nodo.php';
$datos = json_decode(file_get_contents("php://input"), true);
$post  = $_POST;
if ($datos == null) {
    $reques = $post;
} else {
    $reques = $datos;
}

$user = new Nodo();
$res = array("BTC"=>$user->precioUSD());

echo json_encode($res);


