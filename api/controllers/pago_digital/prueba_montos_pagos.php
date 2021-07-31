<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
include '../cors.php';
require '../../models/conexion.php';

//print_r($_POST);
$pago_digital = new Conexion();
$resp = $pago_digital->calcularMontosPagoDigital($_POST, true);

echo json_encode($resp);