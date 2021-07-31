<?php

    include '../cors.php';
    require '../../models/Nodo.php';
    $user = new Nodo();
    $tx = $_GET["txid"];

	$tiempoEspera = rand(0, 120);
	sleep($tiempoEspera);
	echo "El TXHASH = $tx espero $tiempoEspera segundos \n";
    $res = $user->notifyWallet($tx);
    // $res = $user->notifyWallet("temporal");
    
    // $res = $user->obtenerCuentas();
    echo json_encode($res);