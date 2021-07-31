<?php

    require '../cors.php';
    require '../../models/cupones.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"), TRUE);

    if (isset($_GET['buscar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->buscar($data));
        return 0;
    }


    if (isset($_GET['listar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->listar($data));
        return 0;
    }

    if (isset($_GET['filtrar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->filtrar($data));
        return 0;
    }

    if (isset($_GET['crear'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->crear($data));
        return 0;
    }
    if (isset($_GET['crear_tipo'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        $result  = $cupones->crearCuponPorTipo($data);
        unset($cupones);
        echo json_encode($result);
        return 0;
    }

    if (isset($_GET['actualizar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            // return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->actualizar($data));
        return 0;
    }

    if (isset($_GET['puede_usar_cupon'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->puede_usar_cupon($data));
        return 0;
    }

    if (isset($_GET['guardar_cupon_en_historial'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->guardar_cupon_en_historial($data));
        return 0;
    }

    // if (isset($_GET['filtrar_cupones_historial'])) {
    //     $data = validarPostData('data');
    //     if (!isset($data)) {
    //         echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
    //         return 0;
    //     }
    //     //validacion token 
    //     if (llamar_validar_token($data) == false) return 0;
    //     //fin validacion token
    //     $cupones = new Cupones();
    //     echo json_encode($cupones->filtrar_cupones_historial($data));
    //     return 0;
    // }

    if (isset($_GET['filtrar_cupones_historial'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->filtrar_cupones_historial($data));
        return 0;
    }

    if (isset($_GET['filtrar_cupones_historial_bonos'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->filtrar_cupones_historial_bonos($data));
        return 0;
    }

    if (isset($_GET['verificiar_estados_cron'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
            return 0;
        }
        //validacion token 
        if (llamar_validar_token($data) == false) return 0;
        //fin validacion token
        $cupones = new Cupones();
        echo json_encode($cupones->verificiar_estados_cron($data));
        return 0;
    }

    function validarPostData($res)
    {
        $data = null;
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);

        if (isset($_POST[$res])) $data = $_POST[$res];

        else if ($postdata && $request->$res) {
            $data = $request->$res;
            $data = json_encode($data);
            $data = json_decode($data, true);
        }
        return $data;
    }
    function llamar_validar_token(array $data)
    {
        // return ensureAuth($data["uid"], $data["empresa"]);
        return true; 
    }
?>