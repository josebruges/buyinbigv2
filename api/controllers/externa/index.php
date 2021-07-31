<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

require '../../models/conexion.php';
//print_r($_SERVER);

$reques = $_GET;
if (!isset($_GET["url"])) {
 $origin = $_SERVER["HTTP_ORIGIN"];
} else {
    $origin = $_GET["url"];    
}
$origin = explode("//", $origin);
$origin = $origin[count($origin) - 1];
$origin = explode("/", $origin);
$origin = $origin[0];
$user = new conexion();
$user->conectar();
if (!isset($reques["token"])) {
    $consulta = "select * from empresas where pagina_web = '$origin'";
    $res = $user->consultaTodo($consulta);
    if (count($res) > 0) {
        echo json_encode(array("status"=>"success"));
    } else {
        echo json_encode(array("status"=>"error","http"=>$origin));
    }
} else {
    $consulta = "select * from empresas where pagina_web = '$origin' and token_externo = '$reques[token]'";
    $res = $user->consultaTodo($consulta);
    if (count($res) > 0) {
        echo json_encode(array("status"=>"success", "id"=>$res[0]["id"], "idioma"=>$res[0]["idioma"]));
    } else {
        echo json_encode(array("status"=>"error", "origin"=>$origin, "server"=>$_SERVER));
    }    
}

    