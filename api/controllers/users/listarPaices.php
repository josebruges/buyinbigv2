<?php

    include '../error.php';
    include '../cors.php';
    require '../../models/users.php';
    $user = new Users();
    $res = $user->listarPaices();
    echo json_encode($res);