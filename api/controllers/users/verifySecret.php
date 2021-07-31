<?php

    include '../cors.php';
    require '../../models/users.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    $post = $_POST;
    //print_r($post);
    if ($datos == null) $reques = $post;
    else $reques = $datos;

    //validacion token 
    if (ensureAuth($reques["uid"], 0) == false) return 0; 
    //fin validacion token 

    $user = new Users();
   $res = $user->verifySecret($reques);
    //  $res = $user->verifySecret(array("uid"=>"172", "password"=>"1234"));
    echo json_encode($res);



