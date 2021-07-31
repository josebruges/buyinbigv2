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
$res = $pagos->editarPendiente($reques["user"], $reques["status"]);

echo json_encode($res);
