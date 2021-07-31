<?php

    include '../cors.php';
    require '../../models/siigo.php';
    require '../autenticated.php';
    $datos = json_decode(file_get_contents("php://input"), TRUE);




    if (isset($_GET['tercero_registrar_modificar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => $data));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->terceroRegistrarModificar($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }
    if (isset($_GET['tercero_buscar'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->terceroBuscar($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }


    if (isset($_GET['crear_actualizar_cliente'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->crear_actualizar_cliente($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['get_usuario_datos_pasarela'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->getUsuarioDatosPasarela($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['map_cliente'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->map_cliente_siigo($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['consultar_cliente'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->consultar_cliente($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['consultar_producto'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->consultar_producto($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['sincronizar_productos_siigo_nube'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->sincronizar_productos_siigo_nube($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['crear_factura'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->crear_factura($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['crear_factura_new'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->crear_factura_new($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['verificacion_data_cliente_con_siigo'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->verificacion_data_cliente_con_siigo($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }
    
    if (isset($_GET['map_cliente_siigo'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - controller', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->map_cliente_siigo($data);
            unset($siigo);

            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['pdf_factura'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->pdf_factura($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['consultar_factura'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->consultar_factura($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    if (isset($_GET['consultar_datos_de_siigo'])) {
        $data = validarPostData('data');
        if (!isset($data)) {
            echo json_encode(array('status' => 'fail', 'message' => 'no data - webservice', 'data' => null));
        } else {
            $siigo = new  Siigo();
            $response = $siigo->consultar_datos_de_siigo($data);
            unset($siigo);
            echo json_encode($response);
            return 0;
        }
    }

    function validarPostData($res)
    {
        $data = null;
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);

        if (isset($_POST[$res])) $data = $_POST[$res];

        else if (isset($postdata) && isset($request->$res)) {
            $data = $request->$res;
            $data = json_encode($data);
            $data = json_decode($data, true);
        }

        return $data;
    }
?>