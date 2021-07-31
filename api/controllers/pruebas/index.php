<?php
    include '../cors.php';
    require '../../models/nasbifunciones.php';
    $datos = json_decode(file_get_contents("php://input"),TRUE);
    if(isset($_GET['eliminarbloqueado'])) {
        $data = validarPostData('data');
        
        $nasbifunciones = new Nasbifunciones();
        $diferidocomprador = $nasbifunciones->insertarBloqueadoDiferido([
                    'id' => 2323,
                    'uid' => "1179",
                    'empresa' => 0,
                    'moneda' => "Nasbiblue",
                    'all' => false,
                    'precio' => 273020.000000,
                    'precio_momento_usd'=> null,
                    'address' => "756597a004efa73a2cb9d0983f86a7206",
                    'id_transaccion' => "1",
                    'tipo_transaccion' => "1",
                    'tipo' => 'bloqueado',
                    'accion' => 'reverse',
                    'descripcion' => null,
                    'fecha' => $adicional['fecha']
                ]);
        $res = $diferidocomprador;
        unset($nasbifunciones);
        echo json_encode($res);
        return 0;
    }

    function validarPostData($res)
    {
        $data = null;
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);
        
        if(isset($_POST[$res])) $data = $_POST[$res];

        else if($postdata && $request->$res){
            $data = $request->$res;
            $data = json_encode($data);
            $data = json_decode($data, true);
        }
        
        return $data;
    }
    
?>