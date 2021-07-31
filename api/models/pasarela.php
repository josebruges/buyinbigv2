<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Pasarela extends Conexion
{

    //FUNCIONALIDADES MOVIDAD AL ARCHIVO pagos_digitales.php

    // inicio prueba para pagos

    function peticionRemota_pasarela_test(String $url, Array $data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, "1");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data', 'User-Agent:'.$_SERVER['HTTP_USER_AGENT']));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // print_r(json_encode($post_data));
        $file_contents = curl_exec($ch);
        // print_r($file_contents);
        curl_close($ch);
        return json_decode($file_contents);
    }


    // function obtener_token_pd(){
    //     $dataArray = array(
    //         "JWT_USER" => "YRAARAVJKIJJNF5ERWFS",
    //         "JWT_PASS" => "8YCNEBLI2FXG9FWG03B1"
    //     );
    //     $url = "https://www.pagodigital.co/TKN/GENERATE_TOKEN/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url, $dataArray);
    //    return $respuesta;

    //     // return $response = parent::remoteRequest("https://www.pagodigital.co/TKN/GENERATE_TOKEN/", $dataArray);
    // }

    // function obtener_id_comision(Array $data){

    //     $exposicion = $data['exposicion'];
    //     $categoria_id = $data['categoria'];

    //     $id = $exposicion . $categoria_id;
    //     $id = intval($id);
    //     $comisiones = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/comisiones-pasarela.json"),true);
    //     $comision = null;
    //     for($i = 0; $i<count($comisiones); $i++){
    //         if($comisiones[$i]['ID'] == $id){
    //             $comision = $comisiones[$i];
    //             break;
    //         }
    //     }

    //     return $comision;
        
    // }

    // function pse_iniciar_transaccion_test(Array $data){
    //     $datos_producto = $this->buscar_producto($data['PRODUCTO_COMPRA']);
    //     if($datos_producto["status"] == "success"){
    //         $comision = $this->obtener_id_comision(array("exposicion" => $datos_producto["data"]["exposicion"] , "categoria" => $datos_producto["data"]["categoria"]));
    //         if($comision){
    //             $dataArray = array(
    //                 "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //                 "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //                 "NOMBRE_COMPRADOR" => $data['NOMBRE_COMPRADOR'],
    //                 "CEDULA_COMPRADOR" =>  $data['CEDULA_COMPRADOR'],
    //                 "TELEFONO_COMPRADOR" =>  $data['TELEFONO_COMPRADOR'],
    //                 "CORREO_COMPRADOR" =>  $data['CORREO_COMPRADOR'],
    //                 "DIRECCION_COMPRADOR" => $data['DIRECCION_COMPRADOR'],
    //                 "TOTAL_COMPRA" => $data['TOTAL_COMPRA'],
    //                 "PRODUCTO_COMPRA" => $data['PRODUCTO_COMPRA'],
    //                 "CIUDAD" => $data['CIUDAD'],
    //                 "DEPARTAMENTO" => $data['DEPARTAMENTO'],
    //                 "CODIGO_BANCO" => $data['CODIGO_BANCO'],
    //                 "NOMBRE_BANCO" => $data['NOMBRE_BANCO'],
    //                 "TIPO_PERSONA" => $data['TIPO_PERSONA'],
    //                 "TIPO_DOCUMENTO" => $data['TIPO_DOCUMENTO'],
    //                 "CODIGO_ENTIDAD" => "9004617586",
    //                 "CODIGO_SERVICIO" => "9004617586",
    //                 // "URL_RESPUESTA" => "https://nasbi.peers2win.com/api/controllers/publicacion/?url_respuesta_callback_test",
    //                 "URL_RESPUESTA" => "https://nasbi.com", 
    //                 "ID_COMISION" => strval($comision['ID']),
    //                 "ID_PROVEEDOR" => $data['ID_PROVEEDOR'],
    //                 "COSTO_FLETE" => $data['COSTO_FLETE'],
    //                 "JWT" => $data['JWT']
    //             );
    //             $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/PSE/TRANSACTION_BEGIN/";
    //             $respuesta = $this->peticionRemota_pasarela_test($url, $dataArray);
    //             if($respuesta){
    //                 return array("status" => "success", "data" => $respuesta);
    //             }else{
    //                 return array("status" => "fail", "mensaje" => "error respuesta api PD");
    //             }
    //         }
    //     }
        
    // }

    // function pse_check_transaccion_test(Array $data){
    //     $dataArray = array(
    //         "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //         "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //         "ID_REFERENCIA" => $data["ID_REFERENCIA"],
    //         "CUS" => $data["CUS"],
    //         "JWT" => $data["JWT"]
    //     );

    //     $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/PSE/CHECK_TRANSACTION/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url, $dataArray);
    //     if($respuesta){
    //         return array("status" => "success", "data" => $respuesta);
    //     }else{
    //         return array("status" => "fail", "mensaje" => "error respuesta api PD");
    //     }
    // }

    // function pse_finalizar_transaction(Array $data){
    //     $dataArray = array(
    //         "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //         "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //         "ID_REFERENCIA" => $data["ID_REFERENCIA"],
    //         "CUS" => $data["CUS"],
    //         "JWT" => $data["JWT"]
    //     );
    //     $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/PSE/FINALIZE_TRANSACTION/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url, $dataArray);
    //     if($respuesta){
    //         return array("status" => "success", "data" => $respuesta);
    //     }else{
    //         return array("status" => "fail", "mensaje" => "error respuesta api PD");
    //     }
    // }

    // function buscar_producto(Int $idProducto){
    //     parent::conectar();
    //     $producto = parent::consultaTodo("SELECT titulo, exposicion, categoria FROM productos WHERE id = '$idProducto'");
    //     parent::cerrar();

    //     if($producto){
    //         $producto = $producto[0];
    //         return array("status" => "success", "data" => $producto);
    //     }else{
    //         return array("status" => "fail");
    //     }
    // }


    // function solicitar_pin_sured(Array $data){

    //     $datos_producto = $this->buscar_producto(intval($data['PRODUCTO']));
    //     if($datos_producto["status"] == "success"){
    //         $comision = $this->obtener_id_comision(array("exposicion" => $datos_producto["data"]["exposicion"] , "categoria" => $datos_producto["data"]["categoria"]));    
    //         if($comision){
    //                 $dataArray = array(
    //                     "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //                     "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //                     "NOMBRE" => $data['NOMBRE'],
    //                     "CEDULA" => $data['CEDULA'], 
    //                     "TELEFONO" => $data['TELEFONO'], 
    //                     "DIRECCION" => $data['DIRECCION'], 
    //                     "EMAIL" => $data['EMAIL'],
    //                     "TOTAL" => $data['TOTAL'], 
    //                     "PRODUCTO" => $datos_producto['data']['titulo'], 
    //                     "COSTO_FLETE" => $data['COSTO_FLETE'], 
    //                     "ID_COMISION" => strval($comision['ID']), 
    //                     "ID_PROVEEDOR" => $data['ID_PROVEEDOR'], 
    //                     "JWT" => $data['JWT'] 
    //                 );
    //                 $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/SURED/SOLICITAR_PIN_SURED/";
                
    //                 $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);
    //                 if($respuesta){
    //                     return array("status" => "success", "data" => $respuesta);
    //                 }else{
    //                     return array("status" => "fail", "mensaje" => "error respuesta api PD");
    //                 }
    //             }else{
    //                 return array("status" => "fail", "mensaje" => "error al obtener comisiones");
    //             }
    //     }
    //     return array("status" => "fail", "mensaje" => "error");
        
    // }

    // function consultar_pin_sured(Array $data){
    //     $dataArray = array(
    //         "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //         "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //         "PIN" => $data['PIN'],
    //         "JWT" => $data['JWT']
    //     );

    //     $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/SURED/CONSULTA_PIN_SURED/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);
    //     if($respuesta){
    //         return array("status" => "success", "data" => $respuesta);
    //     }else{
    //         return array("status" => "fail", "mensaje" => "error");
    //     }
    // }

    // function solicitar_pin_efecty(Array $data){
        
    //     $datos_producto = $this->buscar_producto(intval($data['PRODUCTO']));
    //     if($datos_producto["status"] == "success"){
    //         $comision = $this->obtener_id_comision(array("exposicion" => $datos_producto["data"]["exposicion"] , "categoria" => $datos_producto["data"]["categoria"]));    
    //         if($comision){
    //                 $dataArray = array(
    //                     "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //                     "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //                     "NOMBRE" => $data['NOMBRE'],
    //                     "CEDULA" => $data['CEDULA'], 
    //                     "TELEFONO" => $data['TELEFONO'], 
    //                     "DIRECCION" => $data['DIRECCION'], 
    //                     "EMAIL" => $data['EMAIL'],
    //                     "TOTAL" => $data['TOTAL'], 
    //                     "PRODUCTO" => $datos_producto['data']['titulo'], 
    //                     "COSTO_FLETE" => $data['COSTO_FLETE'], 
    //                     "ID_COMISION" => strval($comision['ID']), 
    //                     "ID_PROVEEDOR" => $data['ID_PROVEEDOR'], 
    //                     "JWT" => $data['JWT'] 
    //                 );
    //                 // return $dataArray;
    //                 $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/EFECTY/SOLICITAR_PIN_EFECTY/";
                
    //                 $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);
    //                 if($respuesta){
    //                     return array("status" => "success", "data" => $respuesta);
    //                 }else{
    //                     return array("status" => "fail", "mensaje" => "error respuesta api PD");
    //                 }
    //             }else{
    //                 return array("status" => "fail", "mensaje" => "error al obtener comisiones");
    //             }
    //     }
    //     return array("status" => "fail", "mensaje" => "error");
    // }

    // function consultar_pin_efecty(Array $data){
    //     $dataArray = array(
    //         "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //         "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //         "PIN" => $data['PIN'],
    //         "JWT" => $data['JWT']
    //     );

    //     $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/EFECTY/CONSULTA_PIN_EFECTY/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);
    //     if($respuesta){
    //         return array("status" => "success", "data" => $respuesta);
    //     }else{
    //         return array("status" => "fail", "mensaje" => "error");
    //     }
    // }

    // function token_card_tc(Array $data){
    //     $dataArray = array(
    //         "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //         "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //         "JWT" => $data['JWT'],
    //         'PAN' => $data['PAN'],
    //         'CVV2' => $data['CVV2'],
    //         'MES_EXP' => $data['MES_EXP'],
    //         'ANO_EXP' => $data['ANO_EXP'],
    //         'NOMBRE' => $data['NOMBRE'],
    //         'CEDULA' => $data['CEDULA'],
    //         'DIRECCION' => $data['DIRECCION'],
    //         'CORREO' => $data['CORREO'],
    //         'TELEFONO' => $data['TELEFONO'],
    //         'CIUDAD' => $data['CIUDAD'],
    //         'DEPARTAMENTO' => $data['DEPARTAMENTO'],
    //         'FRANQUICIA' => $data['FRANQUICIA']
    //     );
    //     $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/TARJETA_CREDITO/TOKEN_CARD/";
    //     $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);

    //     if($respuesta){
    //         return array("status" => "success", "data"=>$respuesta);
    //     }else{
    //         return array("status" => "fail", "mensaje" =>"error");
    //     }
    // }

    // function process_transaction_tc(Array $data){
    //     $datos_producto = $this->buscar_producto(intval($data['PRODUCTO']));
    //     if($datos_producto["status"] == "success"){
    //         // return $datos_producto;
    //         $comision = $this->obtener_id_comision(array("exposicion" => $datos_producto["data"]["exposicion"] , "categoria" => $datos_producto["data"]["categoria"]));    
    //         if($comision){
    //             $dataArray = array(
    //                 "API_KEY" => "1SLCFBVOBI8MDSRVXDS1",
    //                 "API_SECRET" => "N4DTFDN3FEUQYFJWV98X",
    //                 "TOKEN" => $data['TOKEN'],
    //                 "AMOUNT" => $data['AMOUNT'],
    //                 "CUOTAS" => $data['CUOTAS'],
    //                 "REFPAY" => $data['REFPAY'],
    //                 "ID_COMISION" => strval($comision['ID']),
    //                 "ID_PROVEEDOR" => $data['ID_PROVEEDOR'],
    //                 "COSTO_FLETE" => $data['COSTO_FLETE'],
    //                 "JWT" => $data['JWT']
    //             );
    //             return $dataArray;
    //             $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/TARJETA_CREDITO/PROCESS_TRANSACTION/";
    //             $respuesta = $this->peticionRemota_pasarela_test($url,$dataArray);
            
    //             if($respuesta){
    //                 return array("status" => "success", "data"=>$respuesta);
    //             }else{
    //                 return array("status" => "fail", "mensaje" =>"error");
    //             }
    //         }else{
    //             return array("status" => "fail", "mensaje" => "error al obtener comisiones");
    //         }     
    //     }
    //     return array("status" => "fail", "mensaje" => "error");
    // }

    // fin prueba para pagos




}
?>