<?php

    include '../cors.php';
    require '../../models/pago_digital.php';

    require '../autenticated.php';

    $datos = json_decode(file_get_contents("php://input"),TRUE);

    if ($datos == null) {
    	$datos = $_POST;	
    }

    $headers = getallheaders();
    $datos["ip"] = "";
    if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
        $datos["ip"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    if ( isset( $headers["pago_dg_token"] ) ) {
        $datos["token"] = $headers["pago_dg_token"];
        $datos_comprador = new PagoDigital();
        $res = $datos_comprador->insertarBurritaDigital($datos);
        echo json_encode($res);
    }else{
        if(isset($_GET['crear_proveedor'])) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->crearProveedor( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if( isset($_GET['generar_token']) ) {
            $pago_digital = new PagoDigital();
            $res = $pago_digital->generateToken( null );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        // RELEASE V1 - Servicios para la implementación de PSE
        if( isset($_GET['obtener_datos_PSE']) ) {
            $pago_digital = new PagoDigital();
            $res = $pago_digital->obtenerDatosPse( null );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if( isset($_GET['get_bank_list']) ) {
            $pago_digital = new PagoDigital();
            $res = $pago_digital->getBankList( null );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset($_GET['generate_orden_pago'])) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0;
            $pago_digital = new PagoDigital();
            $res = $pago_digital->generateOrdenPago( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset($_GET['actualizar_orden_pago'])) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0;
            
            $pago_digital = new PagoDigital();
            $res = $pago_digital->actualizarOrdenPago( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset($_GET['obtener_orden_pago'])) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->obtenerOrdenPago( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset($_GET['send_orden_pago_pse'])) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->sendOrdenPagoPSE( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['finalize_transaction_pse']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            // if(llamar_validar_token($data) == false) return 0;
            $pago_digital = new PagoDigital();
            $res = $pago_digital->finalizeTransactionPSE( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        // RELEASE V1 - Servicios para la implementación de EFECTY
        if(isset( $_GET['solicitar_pin_efecty']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->solicitarPinEfecty( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['consultar_pin_masivo_efecty']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            $pago_digital = new PagoDigital();
            $res = $pago_digital->consultarPinMasivoEfecty( $data );
            unset($pago_digital);
            echo json_encode( $res );
            return 0;
        }
        if(isset( $_GET['consultar_pin_efecty']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status' => 'fail', 'message' => 'no data', 'data' => null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();

            $res = $pago_digital->consultarPinEfecty( $data );

            unset($pago_digital);
            echo json_encode( $res );
            return 0;
        }

        // RELEASE V1 - Servicios para la implementación de SuRed

        if(isset( $_GET['solicitar_pin_su_red']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->solicitarPinSuRed( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['consultar_pin_masivo_su_red']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            $pago_digital = new PagoDigital();
            $res = $pago_digital->consultarPinMasivoSuRed( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['consultar_pin_su_red']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->consultarPinSuRed( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }        


        // Implementación modulo tarjeta de credito.
        if(isset( $_GET['obtener_token_card_all']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            
            $pago_digital = new PagoDigital();
            $res = $pago_digital->obtenerTokenCardAll( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['obtener_token_card']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            
            $pago_digital = new PagoDigital();
            $res = $pago_digital->obtenerTokenCard( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['obtener_id_tarjetas']) ) {
            $pago_digital = new PagoDigital();
            $res = $pago_digital->obtenerIdTarjetas();
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['crear_token_card']) ) {  
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }

            $pago_digital = new PagoDigital();
            $res = $pago_digital->crearTokenCard($data);
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['confirmar_token_card']) ) {  
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }

            $pago_digital = new PagoDigital();
            $res = $pago_digital->confirmarTokenCard($data);
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['eliminar_token_card']) ) {  
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }

            $pago_digital = new PagoDigital();
            $res = $pago_digital->eliminarTokenCard($data);
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset( $_GET['editar_nombre_card']) ) {  
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0; 
            }

            $pago_digital = new PagoDigital();
            $res = $pago_digital->editarNombreCard($data);
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['send_orden_pago_tarjeta']) ) {  
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0; 
            }

            $pago_digital = new PagoDigital();
            $res = $pago_digital->sendOrdenPagoTarjeta($data);
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }
        if(isset( $_GET['finalizar_transaccion_tarjeta_masivo']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            $pago_digital = new PagoDigital();
            $res = $pago_digital->finalizarTransaccionTarjetaMasivo( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }


        if(isset($_GET['solicitar_dispersion']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->generarSolicitudDispersion( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        if(isset($_GET['realizar_dispersion']) ) {
            $data = validarPostData('data');
            if(!isset($data)){
                echo json_encode(array('status'=>'fail', 'message'=>'no data', 'data'=> null));
                return 0;
            }
            if(llamar_validar_token($data) == false) return 0; 
            $pago_digital = new PagoDigital();
            $res = $pago_digital->realizarSolicitudDispersion( $data );
            unset($pago_digital);
            echo json_encode($res);
            return 0;
        }

        echo json_encode(array('status'=>'fail', 'message'=>'error 404', 'data'=> null));
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
    function llamar_validar_token(Array $data){
        // return ensureAuth($data["uid"], $data["empresa"]); 
        return true; 
    }
?>