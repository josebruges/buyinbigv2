<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include '../cors.php';
require '../../models/subastas.php';
$datos = json_decode(file_get_contents("php://input"),TRUE);
$post = $_POST;
//print_r($post);
if ($datos == null) $reques = $post;
else $reques = $datos;
$user = new Subastas();
$res = $user->getSubastas($reques);
//  $res = $user->comprarEntradas(array("uid"=>"172", "tipo"=>"entradas300", "cantidad"=>20));
echo json_encode($res);