<?php

include '../cors.php';
include '../error.php';
require '../../models/subastas.php';
require '../autenticated.php';
$datos = json_decode(file_get_contents("php://input"),TRUE);
$post = $_POST;
if ($datos == null) $reques = $post;
else $reques = $datos;
if(!isset($reques["uid"])){
    echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
    return 0;
}
if (ensureAuth($reques["uid"], 0) == false) return 0; 
$user = new Subastas();
$res = $user->validarInscripto($reques);
echo json_encode($res);


