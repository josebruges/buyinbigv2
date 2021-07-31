
<?php

    include '../cors.php';
    require '../../models/Nodo.php';
    $user = new Nodo();
    $res = $user->verificarConexion();
    echo json_encode($res);