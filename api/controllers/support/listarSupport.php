<?php

    include '../cors.php';
    require '../../models/support.php';
    $support = new Support();
    $res = $support->listarSupport();
    echo json_encode($res);