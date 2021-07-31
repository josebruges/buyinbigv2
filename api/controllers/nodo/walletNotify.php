<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    include '../cors.php';
    require '../../models/Nodo.php';
    $user = new Nodo();
    
    

	// $tiempoEspera = rand(0, 120);
	// sleep($tiempoEspera);
    
    if (isset($_GET["resolve"])) {
        $tx2 = $_GET["resolve"];
        echo "El TXHASH = $tx2 \n";
        $res = $user->notifyWallet($tx2);
    } else {
        $tx = $_GET["txid"];
        echo "El TXHASH = $tx \n";
        // $res = $user->notifyWallet($tx);
        $res = $user->insertartx($tx);
    }
	
    // $res = $user->notifyWallet("temporal");
    
    // $res = $user->obtenerCuentas();
    echo json_encode($res);