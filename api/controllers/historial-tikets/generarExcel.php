<?php

    include '../cors.php';
    require '../../models/historial-tikets.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    if ($datos == null) $reques = $post;
    else $reques = $datos;
    $filename = "phpzag_data_export_".date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename="$filename"");
    $historial = new HistorialTikects();
    $res = $historial->generarExcel($reques);
    echo $res;