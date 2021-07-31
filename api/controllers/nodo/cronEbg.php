<?php

include '../cors.php';
require '../../models/Nodo.php';
$datos = json_decode(file_get_contents("php://input"), true);
$user = new Nodo();
$res = $user->buscarBloques();
echo json_encode($res);


