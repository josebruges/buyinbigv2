<?php

include '../cors.php';
require '../../models/pagos.php';
$datos = json_decode(file_get_contents("php://input"),TRUE);
$post = $_POST;
//print_r($post);
if ($datos == null) $reques = $post;
else $reques = $datos;
$user = new Pagos();
$res = $user->obtenerReconsumos();
//  $res = $user->comprarEntradas(array("uid"=>"172", "tipo"=>"entradas300", "cantidad"=>20));
echo json_encode($res);