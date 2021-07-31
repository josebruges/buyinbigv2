<?php
require 'conexion.php';
require '../../Shippo.php';
require 'payudata.php';
require 'notificaciones_nasbi.php';

class Compras extends Conexion
{
    public function misCompras(Array $data)
    {
        if(!isset($data)  || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        // $estado = "AND pt.estado NOT IN (10, 12, 13)";
        $estado = "AND pt.estado <= 13";
        if(isset($data['estado'])) $estado = "AND pt.estado = '$data[estado]'";
        
        $exposicion = "";
        if(isset($data['exposicion'])) $exposicion = "AND p.exposicion = '$data[exposicion]'";
        
        $pagina        = floatval($data['pagina']);
        $numpagina     = 9;
        $hasta         = $pagina * $numpagina;
        $desde         = ($hasta - $numpagina) + 1;

        $request = null;
        parent::conectar();
        $selecxcompras = 
        "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT
                    pt.*,
                    p.titulo,
                    p.foto_portada,
                    pte.descripcion AS descripcion_estado,
                    ptt.descripcion AS descripcion_tipo,
                    p.exposicion, p.fecha_creacion AS fecha_creacion_producto,
                    (SELECT SUM(hp.visitas) FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto) AS visitas,
                    (SELECT SUM(pt.cantidad) FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10) AS cantidad_vendidas

                FROM productos_transaccion pt
                LEFT JOIN productos p ON pt.id_producto = p.id
                LEFT JOIN productos_transaccion_guia_tcc ptg_tcc ON ptg_tcc.id_productos_transaccion = pt.id
                LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                JOIN (SELECT @row_number:=0) r
                WHERE (pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]') $estado $exposicion
                ORDER BY id DESC
            )as datos ORDER BY id DESC
        )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";
        
        // var_dump($selecxcompras);
        $compras = parent::consultaTodo($selecxcompras);

        $selecxcomprasxtotal = "SELECT * FROM productos_transaccion pt WHERE (pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]') $estado";
        $selecxcomprasxtotal = parent::consultaTodo($selecxcomprasxtotal);

        $selecxcomprasxtotal = count( $selecxcomprasxtotal );
        $totalpaginas = $selecxcomprasxtotal/$numpagina;
        $totalpaginas = ceil($totalpaginas);

        $compras = $this->mapeoCompras($compras, true);
        parent::cerrar();

        return array(
            'status'           =>  'success',
            'message'          =>  'mis compras',
            'pagina'           =>  $pagina,
            'total_paginas'    =>  $totalpaginas,
            'productos'        =>  count($compras),
            'total_productos'  =>  count($selecxcomprasxtotal),
            'data'             =>  $compras
        );
    }

    public function detalleRequestPayU(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $compra = $this->transaccionId($data, 3);
        if($compra['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $compra = $compra['data'];

        if($data['uid'] != $compra['uid_comprador'] || $data['empresa'] != $compra['empresa_comprador']) return array('status' => 'fail', 'message'=> 'este usuario no puede repottar esta venta', 'data' => null);

        return $this->detallePayU($data);
    }

    public function subirFotoComprobante(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['estado']) || !isset($data['foto']) || !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        
        $compra = $this->transaccionId($data, $data['estado']);
        if($compra['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);

        $compra = $compra['data'];
        $id_detalle_pago = $compra['detalle_pago']['id'];
        $estado = 5;
        $descripcion = addslashes($data['descripcion']);

        $fecha = intval(microtime(true)*1000);

        $url = $this->subirComprobante([
            'id_transaccion' => $data['id'],
            'img' => $data['foto'],
            'fecha' => $fecha
        ]);

        parent::conectar();
        $updatexdetallexpago = "UPDATE productos_transaccion_detalle_pago
        SET
            url = '$url',
            descripcion = '$descripcion',
            fecha_actualizacion = '$fecha'
        WHERE id = '$id_detalle_pago'";
        $updatedetallepago = parent::query($updatexdetallexpago);
        parent::cerrar();
        if(!$updatedetallepago) return array('status' => 'fail', 'message'=> 'error al actualizar comprobante', 'data' => null);
        
        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'comprobante actualizado',
            'mensaje_fail' => 'error al actualizar comprobante'
        ]);
    }

    public function confirmarEntregado(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $compra = $this->transaccionId($data, 7);
        if($compra['status'] == 'fail') return $compra;
        $compra = $compra['data'];

        $estado = 8;
        $fecha = intval(microtime(true)*1000);

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $compra['uid_vendedor'],
            'empresa' => $compra['empresa'],
            'text' => '¡FELICIDADES! el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha confirmado que le ha llegado tu venta',
            'es' => '¡FELICIDADES! el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha confirmado que le ha llegado tu venta',
            'en' => 'CONGRATULATIONS! the '.$compra['datos_usuario_comprador']['nombre'].' buyer of the '.$compra['titulo'].' product has confirmed that your sale has reached him',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_ventas'
        ]);
        unset($notificacion);

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'entrega confirmada',
            'mensaje_fail' => 'entrega no confirmada'
        ]);
    }

    public function confirmarEntregadoBien(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $compra = $this->transaccionId($data, 8);
        if($compra['status'] == 'fail') return $compra;
        $compra = $compra['data'];

        $estado = 11;
        $fecha = intval(microtime(true)*1000);

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $compra['uid_vendedor'],
            'empresa' => $compra['empresa'],
            'text' => '¡FELICIDADES! el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha confirmado que le ha llegado tu venta',
            'es' => '¡FELICIDADES! el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha confirmado que le ha llegado tu venta',
            'en' => 'CONGRATULATIONS! the '.$compra['datos_usuario_comprador']['nombre'].' buyer of the '.$compra['titulo'].' product has confirmed that your sale has reached him',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_ventas'
        ]);
        unset($notificacion);

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'entrega confirmada',
            'mensaje_fail' => 'entrega no confirmada',
            'envio_correo_confirmar' => 1
        ]);
    }

    public function rutasEnvio(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['estado'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $compra = $this->transaccionIdFull($data, $data['estado']);
        if($compra['status'] == 'fail') return $compra;
        $compra = $compra['data'];

        $envio_shippo = (array) json_decode(Shippo_Shipment::retrieve($compra['envio']['id_envio_shippo']), true); //EEUU
        unset($envio_shippo['parcels'][0]['extra']);

        // echo (json_encode($envio_shippo));
        
        if($envio_shippo['address_from']['object_id'] == $envio_shippo['address_to']['object_id']){
            $shipment = (array) json_decode(Shippo_Shipment::create(
                array(
                    'address_from' => $envio_shippo['address_from']['object_id'],
                    'address_to' => $envio_shippo['address_to']['object_id'],
                    'parcels' => array($envio_shippo['parcels'][0]['object_id']),
                    'customs_declaration' => $envio_shippo['customs_declaration'],
                    'extra' => array('is_return' => true),
                    'async' => false
                )
            ));
        }else{
            $envio_cd = (array) json_decode(Shippo_CustomsDeclaration::retrieve($envio_shippo['customs_declaration']), true);
            $envio_ci = (array) json_decode(Shippo_CustomsItem::retrieve($envio_cd['items'][0]), true);

            // Example CustomsItems object.
            // The complete reference for customs object is here: https://goshippo.com/docs/reference#customsitems
            $customs_item =  (array) json_decode(Shippo_CustomsItem::create(array(
                'description' => $envio_ci['description'],
                'quantity' => $envio_ci['quantity'],
                'net_weight' => $envio_ci['net_weight'],
                'mass_unit' => $envio_ci['mass_unit'],
                'value_amount' => $envio_ci['value_amount'],
                'value_currency' => $envio_ci['value_currency'],
                'origin_country' => $envio_shippo['address_to']['country'],
                'tariff_number' => $envio_ci['tariff_number'],
            )), true);

            // Creating the Customs Declaration
            // The details on creating the CustomsDeclaration is here: https://goshippo.com/docs/reference#customsdeclarations
            $customs_declaration = (array) json_decode(Shippo_CustomsDeclaration::create(
            array(
                'contents_type'=> $envio_cd['contents_type'],
                'contents_explanation'=> $envio_cd['contents_explanation'],
                'non_delivery_option'=> $envio_cd['non_delivery_option'],
                'certify'=> $envio_cd['certify'],
                'certify_signer'=> $envio_cd['certify_signer'],
                'items'=> array($customs_item['object_id']),
            )), true);

            $shipment = (array) json_decode(Shippo_Shipment::create(
                array(
                    'address_from' => $envio_shippo['address_to']['object_id'],
                    'address_to' => $envio_shippo['address_from']['object_id'],
                    'parcels' => array($envio_shippo['parcels'][0]['object_id']),
                    'customs_declaration' => $customs_declaration,
                    'async' => false
                )
            ));
        }
        
        
        if ($shipment['status'] != 'SUCCESS') return array('status' => 'fail', 'message'=> 'rutas', 'cantidad'=> 0, 'data' => null);

        return array('status' => 'success', 'message'=> 'rutas', 'cantidad'=> count($shipment['rates']), 'data' => $shipment['rates']);
    }

    public function devolucionProducto(Array $data)
    {
        if( !isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['estado']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $compra = $this->transaccionIdFull($data, $data['estado']);
        if($compra['status'] == 'fail') return $compra;
        $compra = $compra['data'];
        $compra_envio = $compra['envio'];
        $contador = $compra['contador_devolucion_envio'];
        //fecha principal
        $fecha = intval(microtime(true)*1000);

        if(isset($data['id_envio']) && isset($data['id_ruta'])){

            $envio = (array) json_decode(Shippo_Shipment::retrieve($data['id_envio']), true);
            $ruta = $this->filter_by_value($envio['results'], 'object_id', $data['id_ruta'])[0];
            $empresa = $ruta['provider'];

            $transaction = (array) json_decode(Shippo_Transaction::create( 
                array( 
                    'rate' => $data['id_ruta'], 
                    'label_file_type' => 'PDF', 
                    'async' => false
                ) 
            ));
            
            // Retrieve label url and tracking number or error message
            if ($transaction['status'] != 'SUCCESS') return array('status' => 'fail', 'message'=> 'envio no actualizado', 'data' => $transaction['messages']);

            $envio_shippo = (array) json_decode(Shippo_Shipment::retrieve($data['id_envio']), true); //EEUU
            
            $vendedor_id_shippo = $envio_shippo['address_from']['object_id'];
            $comprador_id_shippo = $envio_shippo['address_to']['object_id'];

            $this->insertarEnvioDevolucion([
                'tracking_number' => $transaction['tracking_number'],
                'tracking_url_provider' => $transaction['tracking_url_provider'],
                'empresa' => $empresa,
                'label_url' => $transaction['label_url'],
                'commercial_invoice_url' => $transaction['commercial_invoice_url'],
                'id_envio' => $data['id_envio'],
                'id_ruta' => $data['id_ruta'],
                'object_id' => $transaction['object_id'],
                'id' => $compra['id'],
                'id_direccion_vendedor' => $compra_envio['id_direccion_vendedor'],
                'id_direccion_comprador' => $compra_envio['id_direccion_comprador'],
                'id_prodcuto_envio' => $compra_envio['id_prodcuto_envio'],
                'fecha' => $fecha,
            ]);
        }else if( isset($data['numero_guia']) && isset($data['empresa_envio']) ){
            $this->insertarEnvioDevolucion([
                'tracking_number' => $data['numero_guia'],
                'tracking_url_provider' => null,
                'empresa' => $data['empresa_envio'],
                'label_url' => null,
                'commercial_invoice_url' => null,
                'id_envio' => null,
                'id_ruta' => null,
                'object_id' => null,
                'id' => $compra['id'],
                'id_direccion_vendedor' => $compra_envio['id_direccion_vendedor'],
                'id_direccion_comprador' => $compra_envio['id_direccion_comprador'],
                'id_prodcuto_envio' => $compra_envio['id_prodcuto_envio'],
                'fecha' => $fecha,
            ]);
        }else{
            return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        }

        $estado = 9;
        $msjfinal = 'devolucion producto';
        $contador++;
        if($contador == 3){
            $estado = 10;
            $msjfinal = 'transaccion no concretada';
        }

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $compra['uid_vendedor'],
            'empresa' => $compra['empresa'],
            'text' => '¿Qué ha pasado? el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha devuelto tu venta por favor revisa las razones del rechazo',
            'es' => '¿Qué ha pasado? el comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' ha devuelto tu venta por favor revisa las razones del rechazo',
            'en' => 'What happened? the buyer '.$compra['datos_usuario_comprador']['nombre'].' of the product '.$compra['titulo'].' has returned your sale, please review the reasons for rejection',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_ventas'
        ]);
        unset($notificacion);

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'adicional' => ", contador_devolucion_envio = '$data[contador]'",
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => $msjfinal,
            'mensaje_fail' => 'no devolucion producto'
        ]);
    }

    public function reportarComrpra(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['estado']) || !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        
        $compra = $this->transaccionId($data, $data['estado']);
        if($compra['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $compra = $compra['data'];

        if($data['uid'] != $compra['uid_comprador'] || $data['empresa'] != $compra['empresa_comprador']) return array('status' => 'fail', 'message'=> 'este usuario no puede repottar esta venta', 'data' => null);

        $estado = 10;
        $descripcion = addslashes($data['descripcion']);

        $fecha = intval(microtime(true)*1000);

        $url = null;
        if(isset($data['foto'])){
            $url = $this->subirComprobanteNoConcretado([
                'id_transaccion' => $data['id'],
                'img' => $data['foto'],
                'fecha' => $fecha
            ]);
        }

        parent::conectar();
        $instertarxreporte = "INSERT INTO productos_transaccion_reportados
        (
            id_transaccion,
            estado_transaccion,
            estado,
            descripcion_comprador,
            soporte_comprador,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$compra[id]',
            '$compra[estado]',
            '1',
            '$descripcion',
            '$url',
            '$fecha',
            '$fecha'
        );";
        $instertar = parent::queryRegistro($instertarxreporte);
        parent::cerrar();
        if(!$instertar) return array('status' => 'fail', 'message'=> 'error al insertar reporte', 'data' => null);
         //para el correo 
         $data_to_correo= []; 
         $data_to_correo["data_wbs"]=$data;
         $data_to_correo["foto_soporte"]=$url;
          //fin para rl correo 

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'compra no concretada',
            'mensaje_fail' => 'error compra no concretada',
            'envio_correo_reportar' => $data_to_correo  //para el correo 
            
        ]);
    }

    public function calificarVendedor(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['buena_atencion']) || !isset($data['tiempo_entrega']) || !isset($data['fidelidad_producto']) || !isset($data['satisfaccion_producto'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $descripcion = null;
        if(isset($data['descripcion'])) $descripcion = addslashes($data['descripcion']);
        
        $compra = $this->transaccionId($data, 11);
        if($compra['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $compra = $compra['data'];

        if($data['uid'] != $compra['uid_comprador'] || $data['empresa'] != $compra['empresa_comprador']) return array('status' => 'fail', 'message'=> 'este usuario no puede calificar esta venta', 'data' => null);

        $estado = 12;
        $fecha = intval(microtime(true)*1000);

        $instertarxcal = $this->insertarCalificacionVendor([
            'uid' => $compra['uid_vendedor'],
            'empresa' => $compra['empresa'],
            'id_producto' => $compra['id_producto'],
            'id_transaccion' => $compra['id'],
            'tipo_transaccion' => $compra['tipo'],
            'buena_atencion' => $data['buena_atencion'],
            'tiempo_entrega' => $data['tiempo_entrega'],
            'fidelidad_producto' => $data['fidelidad_producto'],
            'satisfaccion_producto' => $data['satisfaccion_producto'],
            'promedio' => 0,
            'descripcion' => $descripcion,
            'fecha' => $fecha
        ]);
        if($instertarxcal['status'] == 'fail') return $instertarxcal;

        $notificacion = new Notificaciones();
        //vendedor
        $notificacion->insertarNotificacion([
            'uid' => $compra['uid_vendedor'],
            'empresa' => $compra['empresa'],
            'text' => 'El comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' te ha calificado, ahora ya puedes calificarlo',
            'es' => 'El comprador '.$compra['datos_usuario_comprador']['nombre'].' del producto '.$compra['titulo'].' te ha calificado, ahora ya puedes calificarlo',
            'en' => 'The buyer '.$compra['datos_usuario_comprador']['nombre'].' of the product '.$compra['titulo'].' has qualified you, now you can rate it',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_ventas'
        ]);
        //compra
        $notificacion->insertarNotificacion([
            'uid'     => $compra['uid_comprador'],
            'empresa' => $compra['empresa_comprador'],
            'text' => 'No sabes cuánto nos alegra que ya tengas tu producto contigo, por favor ahora califica el vendedor de esta compra',
            'es' => 'No sabes cuánto nos alegra que ya tengas tu producto contigo, por favor ahora califica el vendedor de esta compra',
            'en' => 'You do not know how happy we are that you already have your product with you, please rate the seller of this purchase now',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_compras'
        ]);
        unset($notificacion);



        // INICIO: [ CUPONES NASBI  - LISTA DE USUARIOS HABILITADOS ]
        $uid_permitidos      = intval("" . $compra['uid_vendedor']);
        $empresa_permitidos  = intval("" . $compra['empresa']);

        $puede_crear_cupon = false;

        if( $uid_permitidos == 3014 && $empresa_permitidos == 0 ){
            $puede_crear_cupon = true; // [NATURAL] - vodis87760

        }else if( $uid_permitidos == 1291 && $empresa_permitidos == 0 ){
            $puede_crear_cupon = true; // [NATURAL] - Felix

        }else if( $uid_permitidos == 1378 && $empresa_permitidos == 0 ){
            $puede_crear_cupon = true; // [NATURAL] - maya

        }else if( $uid_permitidos == 3016 && $empresa_permitidos == 0 ){
            $puede_crear_cupon = true; // [NATURAL] - sidojic873
        
        }else if( $uid_permitidos == 5 && $empresa_permitidos == 1 ){
            $puede_crear_cupon = true; // [juridico] - Tienda 1
        
        }else if( $uid_permitidos == 319 && $empresa_permitidos == 1 ){
            $puede_crear_cupon = true; // [juridico] - La tienda Nay

        }else if( $uid_permitidos == 411 && $empresa_permitidos == 1 ){
            $puede_crear_cupon = true; // [juridico] - tijes43623

        }else{
            $puede_crear_cupon = false;
        }


        $compra['tienda_oficial']  = floatval( $compra['tienda_oficial'] );
        $compra['refer_comprador'] = floatval( $compra['refer_comprador'] );
        if( $compra['refer_comprador'] > 0 && $compra['tienda_oficial'] > 0 /*&& $puede_crear_cupon*/){
            $requestCupon = Array(
                "data" => Array(
                    "uid"     => $compra['refer_comprador'],
                    "empresa" => 0,
                    "tipo"    => 2
                )
            );
            $responseCupon = parent::remoteRequest("http://nasbi.peers2win.com/api/controllers/cupones/?crear_tipo", $requestCupon);

            $responseCupon = json_decode($responseCupon, true);
            $responseCupon['refer_comprador'] = $compra['refer_comprador'];
            parent::addLog("----+> CUPONES DE 5K: " . json_encode($responseCupon));

            // return $responseCupon;
        }
        // FIN: [ CUPONES NASBI  - LISTA DE USUARIOS HABILITADOS ]

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'vendedor calificado',
            'mensaje_fail' => 'vendedor no calificado',
            'envio_correo_calificar_ven' => 1
        ]);
    }

    public function noConcretadoDeclinarVenta(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $compra = $this->transaccionId($data, 2);
        if($compra['status'] == 'fail') return $compra;
        $compra = $compra['data'];

        $estado = 10;
        $fecha = intval(microtime(true)*1000);

        return $this->actualizarEstadoTransaccion([
            'id' => $compra['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'id_carrito' => $compra['id_carrito'],
            'mensaje_success' => 'compra no concretada',
            'mensaje_fail' => 'fallo no concretar compra'
        ]);
    }

    function actualizarEstadoTransaccion(Array $data)
    {
        parent::conectar();
        $adicional = "";
        if(isset($data['adicional'])) $adicional = $data['adicional'];
        $updatextransaccion = "UPDATE productos_transaccion
        SET
            estado = '$data[estado]',
            fecha_actualizacion = '$data[fecha]'
            $adicional
        WHERE id_carrito = '$data[id_carrito]'";
        $updatetransaccion = parent::query($updatextransaccion);
        parent::cerrar();
        if(!$updatetransaccion) return array('status' => 'fail', 'message'=> $data['mensaje_fail'], 'data' => null);
        
        $this->insertarTimeline($data);
        $this->insertarTimeline2($data);

        $this->envio_correo_change_estado_compra($data);
        return array('status' => 'success', 'message'=> $data['mensaje_success'], 'data' => null);
    }

    function insertarTimeline(Array $data)
    {
        parent::conectar();
        $insertxtimeline = "INSERT INTO productos_transaccion_timeline
        (
            id_transaccion,
            tipo,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_carrito]',
            '2',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
        parent::cerrar();
    }
    function insertarTimeline2(Array $data)
    {
        parent::conectar();
        $insertxtimeline = "INSERT INTO productos_transaccion_timeline
        (
            id_transaccion,
            tipo,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_carrito]',
            '1',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
        parent::cerrar();
    }

    function transaccionId(Array $data, Int $estado)
    {
        $request = null;
        parent::conectar();
        $selectxtransaccion = 
        "SELECT 
            pt.*,
            p.titulo,
            IF( p.empresa = 0,
                u.usuarios_estados_teindas_oficiales_id,
                e.usuarios_estados_teindas_oficiales_id
            ) AS tienda_oficial
        FROM
            productos_transaccion pt
        INNER JOIN buyinbig.productos p ON(pt.id_producto = p.id)
        LEFT JOIN buyinbig.empresas e ON(p.uid = e.id AND p.empresa = 1)
        LEFT JOIN peer2win.usuarios u ON(p.uid = u.id AND p.empresa = 0)
        WHERE pt.id = '$data[id]' AND pt.uid_comprador = '$data[uid]' AND pt.estado = '$estado'";
        
        $compras = parent::consultaTodo($selectxtransaccion);
        if(count($compras) > 0){
            $compras = $this->mapeoCompras($compras, true);
            $compras = $compras[0];
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $compras);
        }else{
            $request = array('status' => 'fail', 'message' => 'faltan datos transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function transaccionIdFull(Array $data, Int $estado)
    {
        $request = null;
        parent::conectar();
        
        $selectxtransaccion = "SELECT pt.*, 
        p.titulo, p.foto_portada, p.producto,
        pte.descripcion AS descripcion_estado, 
        ptt.descripcion AS descripcion_tipo
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        INNER JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        INNER JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.id = '$data[id]' AND pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]' AND pt.estado = '$estado'
        ORDER BY fecha_creacion DESC;";
        $compras = parent::consultaTodo($selectxtransaccion);
        if(count($compras) > 0){
            $compras = $this->mapeoCompras($compras, true);
            $compras = $compras[0];
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $compras);
        }else{
            $request = array('status' => 'fail', 'message' => 'faltan datos transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function insertarCalificacionVendor(Array $data)
    {
        $catidadcalificaciones = 4;
        $data['promedio'] = floatval(($data['buena_atencion'] + $data['tiempo_entrega'] + $data['fidelidad_producto'] + $data['satisfaccion_producto']) / $catidadcalificaciones);
        $data['promedio'] = $this->truncNumber($data['promedio'], 2);
        parent::conectar();
        $insertarxcalificacionxvendedor = "INSERT INTO calificacion_vendedor
        (
            uid,
            empresa,
            id_producto,
            id_transaccion,
            tipo_transaccion,
            buena_atencion,
            tiempo_entrega,
            fidelidad_producto,
            satisfaccion_producto,
            promedio,
            descripcion,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[id_producto]',
            '$data[id_transaccion]',
            '$data[tipo_transaccion]',
            '$data[buena_atencion]',
            '$data[tiempo_entrega]',
            '$data[fidelidad_producto]',
            '$data[satisfaccion_producto]',
            '$data[promedio]',
            '$data[descripcion]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insertarcalificacion = parent::query($insertarxcalificacionxvendedor, false);
        parent::cerrar();
        if(!$insertarcalificacion) return array('status' => 'fail', 'message'=> 'vendedor no calificado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'vendedor calificado', 'data' => null);
    }

    function insertarEnvioDevolucion(Array $data)
    {
        parent::conectar();
        $updatedireccion = "INSERT INTO productos_transaccion_envio_devolucion
        (
            numero_guia,
            url_numero_guia,
            empresa,
            etiqueta_envio,
            factura_comercial,
            id_envio_shippo,
            id_ruta_shippo,
            id_transaccion_shippo,
            id_transaccion,
            id_direccion_vendedor,
            id_direccion_comprador,
            id_prodcuto_envio,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[tracking_number]',
            '$data[tracking_url_provider]',
            '$data[empresa]',
            '$data[label_url]',
            '$data[commercial_invoice_url]',
            '$data[id_envio]',
            '$data[id_ruta]',
            '$data[object_id]',
            '$data[id]',
            '$data[id_direccion_vendedor]',
            '$data[id_direccion_comprador]',
            '$data[id_prodcuto_envio]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $actualizar = parent::queryRegistro($updatedireccion);
        parent::cerrar();
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
    }

    function mapeoCompras(Array $compras, Bool $confidencial)
    {
        $hoy = intval(microtime(true)*1000);
        $dia = 86400000;
        $diassiete = $dia * 7;
        $diastres = $dia * 3;
        $payu = new PayU();
        foreach ($compras as $x => $compra) {

            $compra['id'] = floatval($compra['id']);
            $compra['id_carrito'] = floatval($compra['id_carrito']);
            $compra['id_producto'] = floatval($compra['id_producto']);
            $compra['uid_vendedor'] = floatval($compra['uid_vendedor']);
            $compra['uid_comprador'] = floatval($compra['uid_comprador']);
            $compra['cantidad'] = floatval($compra['cantidad']);
            $compra['boolcripto'] = floatval($compra['boolcripto']);
            
            $compra['precio'] = floatval($compra['precio']) + floatval($compra['precio_envio']);

            if($compra['boolcripto'] == 0) $compra['precio_mask'] = $this->maskNumber($compra['precio'], 2);
            if($compra['boolcripto'] == 1) $compra['precio_mask'] = $this->maskNumber($compra['precio'], 2);
            $compra['precio_usd'] = floatval($compra['precio_usd']);
            $compra['precio_usd_mask'] = $this->maskNumber($compra['precio_usd'], 2);

            $compra['precio_segundo_pago']      = floatval($compra['precio_segundo_pago']);
            $compra['precio_segundo_pago_mask'] = $this->maskNumber($compra['precio_segundo_pago'], 2);

            $compra['precio_cupon'] = floatval($compra['precio_cupon']);
            $compra['precio_cupon_mask'] = $this->maskNumber($compra['precio_cupon'], 2);

            $compra['moneda_segundo_pago']      = $compra['moneda_segundo_pago'];
            
            $compra['precio_envio']      = floatval($compra['precio_envio']);
            $compra['precio_envio_mask'] = $this->maskNumber($compra['precio_envio'], 2);

            $compra['precio_moneda_actual_usd'] = floatval($compra['precio_moneda_actual_usd']);
            $compra['fecha_creacion'] = floatval($compra['fecha_creacion']);
            $compra['fecha_actualizacion'] = floatval($compra['fecha_actualizacion']);
            $compra['estado'] = floatval($compra['estado']);
            $compra['contador'] = floatval($compra['contador']);
            $compra['id_metodo_pago'] = floatval($compra['id_metodo_pago']);
            $compra['tipo'] = floatval($compra['tipo']);
            $compra['empresa'] = floatval($compra['empresa']);
            $compra['empresa_comprador'] = floatval($compra['empresa_comprador']);
            
            $compra['datos_tcc'] = $this->obtenerInfoTCC( intval($compra['id']) );
            
            $compra['pago_digital_id'] = intval( $compra['pago_digital_id'] );
            if ( $compra['pago_digital_id'] != 0) {
                $compra['datos_pd'] = $this->obtenerInfoPagoDigital( $compra );
            }else{
                $compra['datos_pd'] = null;
            }

            if(isset($compra['num'])) $compra['num'] = floatval($compra['num']);

            $compra['caso_especial'] = 0;
            if($compra['estado'] == 6 && ($hoy - $compra['fecha_actualizacion']) >= $diastres) $compra['caso_especial'] = 1;
            if($compra['estado'] == 7 && ($hoy - $compra['fecha_actualizacion']) >= $diassiete) $compra['caso_especial'] = 1;

            $compra['payu'] = null;
            if($compra['boolcripto'] == 0){
                $precio = $compra['precio'];
                $moneda = $compra['moneda'];
                if(!isset($payu->currency_test[$moneda])){
                    $precio = $compra['precio_usd'];
                    $moneda = 'USD';
                }
                $compra['payu']['merchantId'] = $payu->merchantId_test;
                $compra['payu']['accountId'] = $payu->accountId_test;
                $compra['payu']['description'] = addslashes('Compra de articulo '.$compra['titulo'].' Nasbi');
                $compra['payu']['referenceCode'] = $compra['id'];
                $compra['payu']['extra1'] = addcslashes(json_encode(array( //Mirar esta referencia
                    'tipo' => 1,
                    'tipo_descripcion' => 'Compra de un articulo'
                )), '"\\/');
                $compra['payu']['amount'] = $this->truncNumber($precio, 2);
                $compra['payu']['tax'] = 0;
                $compra['payu']['taxReturnBase'] = 0;
                $compra['payu']['currency'] = $moneda;
                $compra['payu']['signature_text'] = $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$compra['payu']['referenceCode'].'~'.$precio.'~'.$moneda;
                $compra['payu']['signature'] = hash('md5', $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$compra['payu']['referenceCode'].'~'.$precio.'~'.$moneda);
                $compra['payu']['test'] = 1;
                $compra['payu']['lng'] = 'es';
                $comprador = $this->datosUser(['uid'=> $compra['uid_comprador'], 'empresa' => $compra['empresa_comprador']])['data'];
                $compra['payu']['buyerFullName'] = $comprador['nombre'];
                $compra['payu']['buyerEmail'] = $comprador['correo'];
                // respuesta del cliente
                $compra['payu']['responseUrl'] = 'https://nasbi.com/content/mis-cuentas.php';
                // respuesta para la api
                $compra['payu']['confirmationUrl'] = 'https://testnet.foodsdnd.com:8185/crearpublicacion/requestPayU';
            }

            $compra['detalle_pago'] = $this->detallePagoTransaccion($compra['id'], $compra['id_metodo_pago'], $confidencial);
            $compra['envio'] = $this->detalleEnvioTransaccion($compra);
            if($compra['estado'] == 2) $compra['detalle_rechazada'] = $this->detalleTransaccionRechazada($compra['id']);
            $compra['datos_usuario_vendedor'] = $this->datosUser(['uid'=> $compra['uid_vendedor'], 'empresa' => $compra['empresa']])['data'];
            $compra['datos_usuario_comprador'] = $this->datosUser(['uid'=> $compra['uid_comprador'], 'empresa' => $compra['empresa_comprador']])['data'];
            $compra['timeline'] = $this->timelineTransaccion($compra, 2)['data'];
            $compra['contador_chat'] = $this->chatContador($compra, 1)['data'];

            // inicio nuevo codigo 
            $resultPTEP = 
            parent::consultaTodo("SELECT * FROM productos_transaccion_especificacion_producto WHERE id_transaccion = '$compra[id_carrito]' AND cantidad > 0 AND id_producto = '$compra[id_producto]'");


            $compra['variaciones'] = [];
            

            if( count($resultPTEP) > 0 ){

                foreach($resultPTEP as $itemPTEP){
                    $id_DPCT = $itemPTEP['id_detalle_producto_colores_tallas'];

                    
                    $selectxvariaciones =
                    "SELECT
                        dpct.id_producto,
                        dpct.id AS id_pair,
                        pc.id AS id_color,
                        pc.nombre_es AS color_nombre_es, 
                        pc.nombre_en AS color_nombre_en,
                        pc.hexadecimal AS hexadecimal,
                        pt.id AS id_tallas,
                        pt.nombre_es AS talla_nombre_es,
                        pt.nombre_en AS talla_nombre_en,
                        dpct.cantidad AS cantidad,
                        dpct.sku AS sku
                    FROM productos_colores AS pc
                    LEFT JOIN detalle_producto_colores_tallas AS dpct ON(pc.id = dpct.id_colores)
                    LEFT JOIN productos_tallas AS pt ON(pt.id = dpct.id_tallas)
                    WHERE dpct.id = '$id_DPCT';";


                    $confi_color_talla = parent::consultaTodo($selectxvariaciones);


                    if( count($confi_color_talla) > 0 ){
                        array_push($compra['variaciones'], array(
                            'color'    => $confi_color_talla[0]['hexadecimal'],
                            'tallaES'  => $confi_color_talla[0]['talla_nombre_es'],
                            'tallaEN'  => $confi_color_talla[0]['talla_nombre_en'],
                            'cantidad' => intval($itemPTEP['cantidad'])
                        ));

                    }
                }
            }
            // fin nuevo codigo 
            
            $compras[$x] = $compra;
        }
        unset($payu);
        return $compras;
    }
    function obtenerInfoTCC( Int $id ) {
        $result_TCC = parent::consultaTodo("SELECT * FROM buyinbig.productos_transaccion_guia_tcc WHERE id_productos_transaccion = $id;");
        if ( count($result_TCC) == 0 ) {
            return null;
        }else{
            return $result_TCC[0];
        }
    }
    function obtenerInfoPagoDigital( Array $compra ) { 

        $pago_digital = parent::consultaTodo("SELECT ID, STATUS, TOTAL_COMPRA, TIPO, UID, EMPRESA, CODIGO_BANCO, NOMBRE_BANCO, ISO_CODE_2, METODO_PAGO_USADO_ID, TRANSACCION_FINALIZADA, FECHA_CREACION, FECHA_ACTUALIZACION FROM buyinbig.pago_digital WHERE id = $compra[pago_digital_id]");
        if ( count($pago_digital) == 0 ) {
            return null;
        }
        $pago_digital             = $pago_digital[0];
        $pago_digital_referencias = parent::consultaTodo("SELECT ID, STATUS, CODIGO_RESPUESTA, REFERENCIA, CUS, ESTADO, RESPUESTA, BANK_URL, PAGO_DIGITAL_ID, JSON_RESPUESTA FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = $compra[pago_digital_id]");

        if ( count($pago_digital_referencias) == 0 ) {
            return null;
        }
        $pago_digital_referencias = $pago_digital_referencias[0];

        return array(
            'pago_digital'             => $pago_digital,
            'pago_digital_referencias' => $pago_digital_referencias
        );
    }

    function detallePagoTransaccion(Int $id, Int $id_metodo_pago, Bool $confidencial)
    {
        $detalle_pago = null;
        if($id_metodo_pago == 1 && $confidencial == true){
            $selecxdetallexpago = "SELECT ptdp.*, ncb.id AS id_bloqueado_diferido_comprador, ncd.id AS id_bloqueado_diferido_vendedor
            FROM productos_transaccion_detalle_pago ptdp
            LEFT JOIN nasbicoin_bloqueado_diferido ncb ON ptdp.id_transaccion = ncb.id_transaccion AND ncb.tipo = 1 AND ncb.tipo_transaccion = 1
            LEFT JOIN nasbicoin_bloqueado_diferido ncd ON ptdp.id_transaccion = ncd.id_transaccion AND ncd.tipo = 0 AND ncd.tipo_transaccion = 1
            WHERE ptdp.id_transaccion = '$id';";
        }
        if($confidencial == false || ($confidencial == true && $id_metodo_pago != 1)){
            $selecxdetallexpago = "SELECT ptdp.*, 
            (SELECT ptdpd.descripcion FROM productos_transaccion_detalle_pago_declinado ptdpd WHERE ptdpd.id_transaccion = '$id' ORDER BY ptdpd.id DESC LIMIT 1) AS descripcion_declinado 
            FROM productos_transaccion_detalle_pago ptdp
            WHERE ptdp.id_transaccion = '$id';";
        }
        $detalle_pago = parent::consultaTodo($selecxdetallexpago);
        if(count($detalle_pago) > 0){
            $detalle_pago = $this->mapeoDetallePago($detalle_pago)[0];
        }else{
            $detalle_pago = null;
        }

        return $detalle_pago;
    }

    function mapeoDetallePago(Array $detalle_pago)
    {
        foreach ($detalle_pago as $x => $detalle) {
            $detalle['id'] = floatval($detalle['id']);
            $detalle['id_transaccion'] = floatval($detalle['id_transaccion']);
            $detalle['id_metodo_pago'] = floatval($detalle['id_metodo_pago']);
            $detalle['monto'] = floatval($detalle['monto']);
            $detalle['cantidad'] = floatval($detalle['cantidad']);
            $detalle['confirmado'] = floatval($detalle['confirmado']);
            $detalle['fecha_creacion'] = floatval($detalle['fecha_creacion']);
            $detalle['fecha_actualizacion'] = floatval($detalle['fecha_actualizacion']);

            if(isset($detalle['id_bloqueado_diferido_comprador'])) $detalle['id_bloqueado_diferido_comprador'] = floatval($detalle['id_bloqueado_diferido_comprador']);
            if(isset($detalle['id_bloqueado_diferido_vendedor'])) $detalle['id_bloqueado_diferido_vendedor'] = floatval($detalle['id_bloqueado_diferido_vendedor']);

            $detalle_pago[$x] = $detalle;
        }
        return $detalle_pago;
    }

    function detalleEnvioTransaccion(Array $data)
    {
        $from = "FROM productos_transaccion_envio pte";
        $extra = "pte.tipo_envio, ";
        if($data['estado'] == 9){
            $extra = "";
            $from = "FROM productos_transaccion_envio_devolucion pte";
        }

        $envio = null;
        $selecxenvio = 
        "SELECT 
            pte.id,
            $extra
            pte.id_transaccion,
            pte.id_direccion_vendedor,
            pte.id_direccion_comprador,
            pte.id_prodcuto_envio,
            pte.id_envio_shippo,
            pte.id_ruta_shippo,
            pte.id_transaccion_shippo,
            pte.numero_guia,
            pte.url_numero_guia,
            pte.empresa,
            pte.etiqueta_envio,
            pte.factura_comercial,
            pte.fecha_creacion,
            pte.fecha_actualizacion,

            dc.id AS comprador_id_direccion,
            dc.id_shippo AS comprador_id_shippo,
            dc.uid AS comprador_uid,
            dc.pais AS comprador_pais,
            dc.departamento AS comprador_departamento,
            dc.ciudad AS comprador_ciudad,
            dc.latitud AS comprador_latitud,
            dc.longitud AS comprador_longitud,
            dc.codigo_postal AS comprador_codigo_postal,
            dc.direccion AS comprador_direccion,

            dv.id AS vendedor_id_direccion,
            dv.id_shippo AS vendedor_id_shippo,
            dv.uid AS vendedor_uid,
            dv.pais AS vendedor_pais,
            dv.departamento AS vendedor_departamento,
            dv.ciudad AS vendedor_ciudad,
            dv.latitud AS vendedor_latitud,
            dv.longitud AS vendedor_longitud,
            dv.codigo_postal AS vendedor_codigo_postal,
            dv.direccion AS vendedor_direccion

        $from
        INNER JOIN direcciones dv ON pte.id_direccion_vendedor = dv.id
        INNER JOIN direcciones dc ON pte.id_direccion_comprador = dc.id
        WHERE pte.id_transaccion = '$data[id_carrito]'
        ORDER BY fecha_creacion DESC;";
        $envio = parent::consultaTodo($selecxenvio);
        if(count($envio) <= 0) return null;
        
        return $this->mapeoEnvio($envio)[0];
    }

    function mapeoEnvio(Array $envios)
    {
        foreach ($envios as $x => $envio) {

            $envio['id'] = floatval($envio['id']);
            $envio['id_transaccion'] = floatval($envio['id_transaccion']);
            $envio['id_direccion_vendedor'] = floatval($envio['id_direccion_vendedor']);
            $envio['id_direccion_comprador'] = floatval($envio['id_direccion_comprador']);
            if(isset($envio['tipo_envio'])) $envio['tipo_envio'] = floatval($envio['tipo_envio']);

            $envio['fecha_creacion'] = floatval($envio['fecha_creacion']);
            $envio['fecha_actualizacion'] = floatval($envio['fecha_actualizacion']);

            
            $envio['comprador_id_direccion'] = floatval($envio['comprador_id_direccion']);
            $envio['comprador_uid'] = floatval($envio['comprador_uid']);
            $envio['comprador_pais'] = floatval($envio['comprador_pais']);
            $envio['comprador_departamento'] = floatval($envio['comprador_departamento']);
            $envio['comprador_latitud'] = floatval($envio['comprador_latitud']);
            $envio['comprador_longitud'] = floatval($envio['comprador_longitud']);
            
            $envio['vendedor_id_direccion'] = floatval($envio['vendedor_id_direccion']);
            $envio['vendedor_uid'] = floatval($envio['vendedor_uid']);
            $envio['vendedor_pais'] = floatval($envio['vendedor_pais']);
            $envio['vendedor_departamento'] = floatval($envio['vendedor_departamento']);
            $envio['vendedor_latitud'] = floatval($envio['vendedor_latitud']);
            $envio['vendedor_longitud'] = floatval($envio['vendedor_longitud']);

            $envio['fecha_creacion'] = floatval($envio['fecha_creacion']);
            $envio['fecha_actualizacion'] = floatval($envio['fecha_actualizacion']);
            
            $envios[$x] = $envio;
        }
        return $envios;
    }

    function detalleTransaccionRechazada(Int $id)
    {
        $selectxtransaccionxrechazada = "SELECT ptr.* FROM productos_transaccion_rechazada ptr WHERE ptr.id_transaccion = '$id';";
        $rechazada = parent::consultaTodo($selectxtransaccionxrechazada);
        if(count($rechazada) > 0){
            $rechazada = $this->mapeoTransaccionRechazada($rechazada)[0];
        }else{
            $rechazada = null;
        }
        return $rechazada;
    }

    function detallePayU(Array $data)
    {
        parent::conectar();
        $selectxpayu = "SELECT rpu.* 
        FROM request_payu rpu
        WHERE rpu.id_transaccion = '$data[id]' AND rpu.tipo = 1
        ORDER BY rpu.fecha_actualizacion DESC
        LIMIT 1;";
        $payu = parent::consultaTodo($selectxpayu);
        parent::cerrar();

        if(count($payu) <= 0) return array('status' => 'fail', 'message'=> 'no payu', 'data' => null);
        $payu = $payu[0];
        $payu['json'] = json_decode($payu['json'], true);
        // echo json_last_error_msg();
        // $payu['json'] = json_decode($payu['json'], true);
        return array('status' => 'success', 'message'=> 'pay', 'data' => $payu);
    }

    function mapeoTransaccionRechazada(Array $rechazadas)
    {
        foreach ($rechazadas as $x => $rechazada) {
            $rechazada['id'] = floatval($rechazada['id']);
            $rechazada['uid'] = floatval($rechazada['uid']);
            $rechazada['id_transaccion'] = floatval($rechazada['id_transaccion']);
            $rechazada['tipo'] = floatval($rechazada['tipo']);

            $rechazadas[$x] = $rechazada;
        }
        return $rechazadas;
    }

    function subirComprobante(Array $data)
    {
        $nombre_fichero_principal = $_SERVER['DOCUMENT_ROOT']."/imagenes/transacciones/";
        if (!file_exists($nombre_fichero_principal)) mkdir($nombre_fichero_principal, 0777, true);
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'].'/imagenes/transacciones/'.$data['id_transaccion'];
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);

        $imgconcat = 'pago_'.$data['fecha'];
        $url = $this->uploadImagen([
            'img' => $data['img'],
            'ruta' => '/imagenes/transacciones/'.$data['id_transaccion'].'/'.$imgconcat.'.png',
        ]);
        return $url;
    }

    function subirComprobanteNoConcretado(Array $data)
    {
        $nombre_fichero_principal = $_SERVER['DOCUMENT_ROOT']."/imagenes/no_concretado/";
        if (!file_exists($nombre_fichero_principal)) mkdir($nombre_fichero_principal, 0777, true);
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'].'/imagenes/no_concretado/'.$data['id_transaccion'];
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);

        $imgconcat = 'no_concretado_'.$data['fecha'];
        $url = $this->uploadImagen([
            'img' => $data['img'],
            'ruta' => '/imagenes/no_concretado/'.$data['id_transaccion'].'/'.$imgconcat.'.png',
        ]);
        return $url;
    }

    function uploadImagen(Array $data)
    {
        $posicion1 = strpos($data['img'], 'base64');
        if ($posicion1 !== false) {
            $base64 = base64_decode(explode(',', $data['img'])[1]);
            $filepath1 = $_SERVER['DOCUMENT_ROOT'] . $data['ruta'];
            file_put_contents($filepath1, $base64);
            $url = $_SERVER['SERVER_NAME'] . $data['ruta'];
            return 'https://'.$url;
        } else {
            return $data['img'];
        }
    }

    function timelineTransaccion(Array $data, Int $tipo)
    {
        $selectxtimeline = "SELECT ptt.* FROM productos_transaccion_timeline ptt WHERE ptt.id_transaccion = '$data[id_carrito]' AND ptt.tipo = '$tipo' GROUP BY estado";
        $timeline = parent::consultaTodo($selectxtimeline);

        if(count($timeline) <= 0) return array('status' => 'fail', 'message'=> 'no timeline', 'data' => null);

        $timeline = $this->mapTimeLine($timeline);
        return array('status' => 'success', 'message'=> 'timeline', 'data' => $timeline);
    }

    function chatContador(Array $data, Int $tipo)
    {
        $selectxchat = "SELECT COUNT(c.id) AS contador FROM chat c WHERE c.id_transaccion = '$data[id]' AND c.tipo = '$tipo' AND (c.uid <> '$data[uid_comprador]' OR c.empresa <> '$data[empresa_comprador]') AND c.visualizado = '0'";
        $chat = parent::consultaTodo($selectxchat);

        if(count($chat) <= 0) return array('status' => 'fail', 'message'=> 'no chat', 'data' => 0);
        return array('status' => 'success', 'message'=> 'chat', 'data' => floatval($chat[0]['contador']));
    }

    function mapTimeLine(Array $timelines)
    {
        foreach ($timelines as $x => $timeline) {

            $timeline['id'] = floatval($timeline['id']);
            $timeline['id_transaccion'] = floatval($timeline['id_transaccion']);
            $timeline['estado'] = floatval($timeline['estado']);
            $timeline['tipo'] = floatval($timeline['tipo']);
            $timeline['fecha_creacion'] = floatval($timeline['fecha_creacion']);
            $timeline['fecha_actualizacion'] = floatval($timeline['fecha_actualizacion']);

            $timelines[$x] = $timeline;
        }
        return $timelines;
    }

    function datosUser(Array $data)
    {
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        $usuario = parent::consultaTodo($selectxuser);
        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }

    function mapUsuarios(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre   = $user['nombreCompleto'];
                $dataempresa  = $user['nombreCompleto'];//"Nasbi";
                $datacorreo   = $user['email'];
                $datatelefono = $user['telefono'];
                // $datafoto     = $user['foto'];
                $datafoto     = $user['avatar'];

            }else if($empresa == 1){
                $datanombre   = $user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa  = $user['razon_social'];
                $datacorreo   = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto     = $user['foto_asesor'];
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $usuarios[$x] = $user;
        }

        return $usuarios;
    }

    function filter_by_value (Array $array, String $index, $value){
        $newarray = null;
        if(is_array($array) && count($array)>0) 
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray[] = $array[$key];
                    // $newarray[$key] = $array[$key];
                }
            }
        }
        return $newarray;
    }

    function truncNumber(Float $number, Int $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }

    function maskNumber(Float $numero, Int $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }


    ///comienzo de funciones para envio de correos 

    function envio_correo_change_estado_compra(Array $data){
    
        switch ($data["mensaje_success"]) {
            case "entrega confirmada":
                if(isset($data["envio_correo_confirmar"])){
                 $this->envio_correo_confirmar_entrega($data); 
                }
                break;
            case "compra no concretada":
                if(isset($data["envio_correo_reportar"])){
                    $this->envio_correo_declino_entrega($data["envio_correo_reportar"]); 
                }
                break;

            case "vendedor calificado":
                if(isset($data["envio_correo_calificar_ven"])){
                  $this->envio_correo_califico_vendedor($data); 
                }
           
            
            default:
                # code...
                break;
        }

    }

    function get_producto_car_por_id(Array $data){
        parent::conectar();  
        $productos_carrito = parent::consultaTodo("SELECT * FROM productos_transaccion WHERE id_carrito = '$data[id_carrito]';");
        parent::cerrar();
        return $productos_carrito;

    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos;
    }

    function datosUserGeneral( Array $data ) {
        $result = $this->datosUser2( $data );
        return $result;
    }





    function envio_correo_confirmar_entrega(Array $data ){
        $productos_transaccion=$this->get_producto_car_por_id([
            'id_carrito'   => $data["id_carrito"],
            ]);
            if($productos_transaccion[0]["tipo"]=="1"){
                foreach ($productos_transaccion as $x => $producto) {
                    $data_producto = $this->get_product_por_id([
                        'uid'   => $producto["uid_vendedor"],
                        'id' => $producto["id_producto"],
                        'empresa'  => $producto["empresa"] ]); 
                    
                    $data_vendedor = $this->datosUserGeneral([
                        'uid' => $producto["uid_vendedor"],
                        'empresa' => $producto['empresa']
                    ]);
                    
                    $data_comprador = $this->datosUserGeneral([
                        'uid' => $producto["uid_comprador"],
                        'empresa' => $producto['empresa_comprador']
                    ]);

                
                        $this->htmlEmail_confirmo_comprador_entrega_envio($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto); 
                    $this->htmlEmail_confirmo_vendedor_entrega_envio($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto); 
                }
            }else if($productos_transaccion[0]["tipo"]=="2"){
                foreach ($productos_transaccion as $x => $producto) {
                    $data_producto = $this->get_product_por_id([
                        'uid'   => $producto["uid_vendedor"],
                        'id' => $producto["id_producto"],
                        'empresa'  => $producto["empresa"] ]); 
                    
                    $data_vendedor = $this->datosUserGeneral([
                        'uid' => $producto["uid_vendedor"],
                        'empresa' => $producto['empresa']
                    ]);
                    
                    $data_comprador = $this->datosUserGeneral([
                        'uid' => $producto["uid_comprador"],
                        'empresa' => $producto['empresa_comprador']
                    ]);
                
                    $this->htmlEmail_confirmo_vendedor_entrega_envio_subasta($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto); //se le envia al vendedor
                        
                    
                }

            }
    }




    public function htmlEmail_confirmo_comprador_entrega_envio(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto)
    {
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_comprador["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo5.html");

        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }

        if($data_vendedor["foto"]=="" || $producto["empresa"]=="0"){
            $data_vendedor["foto"]==$json->foto_por_defecto_user;    
        }


        $html = str_replace("{{nombre_usuario}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans144_brand}}",$json->trans144_brand, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans145}}",$json->trans145, $html);
        $html = str_replace("{{trans146}}",$json->trans146, $html);
        $html = str_replace("{{trans147}}",$data_producto["titulo"], $html);
        $html = str_replace("{{trans148}}",$json->trans148, $html);
        $html = str_replace("{{trans149}}",$json->trans149, $html);
        $html = str_replace("{{trans150}}",$json->trans150, $html);
        $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{foto_vendedor}}",$data_vendedor["foto"], $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_comprador["uid"]."&act=0&em=".$data_comprador["empresa"], $html); 

        $para      = $data_comprador['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans121_." ".ucfirst($data_comprador['nombre']);
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }

    function htmlEmail_confirmo_vendedor_entrega_envio (Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo7.html");
    
        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }
    
        if($data_vendedor["foto"]=="" || $producto["empresa"]=="0"){
            $data_vendedor["foto"]==$json->foto_por_defecto_user;    
        }
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{trans107}}",$json->trans107, $html);
        $html = str_replace("{{trans108}}",$json->trans108, $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{trans109}}",$json->trans109, $html);
        $html = str_replace("{{trans110}}",$json->trans110, $html);
        
        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($producto['precio']), $html);
        $html = str_replace("{{moneda}}",$producto['moneda'], $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{foto_vendedor}}",$data_vendedor["foto"], $html);
        
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans111}}",$json->trans111, $html);
        
        $html = str_replace("{{trans266}}",$json->trans266, $html);
        $html = str_replace("{{trans145}}",$json->trans145, $html);
       
        $html = str_replace("{{id_carrito}}", $producto["id_carrito"], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans122_." ".ucfirst($data_comprador['nombre']);
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    
       }


    function datosUser2(Array $data)
    {
        // No deben de existir conexiones abiertas antes de llamar a esta función
        // de llegar a existirlo se debe cerrar antes de llamar a esta funcion.

        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = '1'";
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => $data);

        $usuario = $this->mapUsuarios2($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }
    function mapUsuarios2(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;
        $dataPaso = null;
        $dataIdioma= null; 
        $data_uid=null; 
        foreach ($usuarios as $x => $user) {
            if (!isset( $user['idioma'] )) {
                $user['idioma'] = "ES";
            }
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto'];//"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
                $dataIdioma= strtoupper($user['idioma']);
                $data_uid=$user["id"];
            }else if($empresa == 1){
                $datanombre = $user['razon_social'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $dataIdioma=  strtoupper($user['idioma']);
                $data_uid=$user["id"];
            }
            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $user['empresa'] = $empresa;
            $user['idioma'] = "ES";
            $user['uid'] = $data_uid;
            $usuarios[$x] = $user;
        }
        return $usuarios;
    }



    function envio_correo_declino_entrega($data){
        $url_imagen = $data["foto_soporte"];

        if(isset($url_imagen)){
            $data = $data["data_wbs"];
            $productos_transaccion=$this->get_producto_car_por_id([
                'id_carrito'   => $data["id_carrito"],
            ]);

            foreach ($productos_transaccion as $x => $producto) {
                $data_producto = $this->get_product_por_id([
                    'uid'     => $producto["uid_vendedor"],
                    'id'      => $producto["id_producto"],
                    'empresa' => $producto["empresa"]
                ]); 
                $data_vendedor = $this->datosUserGeneral([
                    'uid' => $producto["uid_vendedor"],
                    'empresa' => $producto['empresa']
                ]);
                $data_comprador = $this->datosUserGeneral([
                    'uid' => $producto["uid_comprador"],
                    'empresa' => $producto['empresa_comprador']
                ]);
                $this->htmlEmail_declino__entrega_comprador_envio($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto, $data);
                $this->htmlEmail_declino__entrega_vendedor_envio($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto, $data, $url_imagen);
                $this->htmlEmail_se_congelo_pago($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto, $data, $url_imagen);
            }
        }
    }



    function envio_correo_califico_vendedor(Array $data){
      
        $productos_transaccion=$this->get_producto_car_por_id([
            'id_carrito'   => $data["id_carrito"],
            ]);
        $data_transaccion_un_producto_carrito= $productos_transaccion[0];

        $data_vendedor = $this->datosUserGeneral([
            'uid' => $data_transaccion_un_producto_carrito["uid_vendedor"],
            'empresa' => $data_transaccion_un_producto_carrito['empresa']
        ]);

        $this->html_calificaron_vendedor($data_vendedor["data"]); 
    }



    function htmlEmail_declino__entrega_comprador_envio(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto, Array $datawbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_comprador["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo6.html");
        
    
        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }
    
        if($data_vendedor["foto"]=="" || $producto["empresa"]=="0"){
            $data_vendedor["foto"]==$json->foto_por_defecto_user;    
        }

    
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_comprador['nombre']), $html);

        $html = str_replace("{{trans138_brand}}",$json->trans138_brand, $html);
        $html = str_replace("{{trans139}}",$json->trans139, $html);
        $html = str_replace("{{trans140}}",$json->trans140, $html);

        $html = str_replace("{{trans141}}",$json->trans141, $html);
        $html = str_replace("{{trans142}}",$data_producto["titulo"], $html);
        $html = str_replace("{{trans143}}",$json->trans143 . ' ' . $datawbs["descripcion"], $html);
        $html = str_replace("{{descripcion_queja}}",$datawbs["descripcion"], $html);

        
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        

        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($producto['precio']), $html);
        $html = str_replace("{{moneda}}",$producto['moneda'], $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{foto_vendedor}}",$data_vendedor["foto"], $html);
        
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans111}}",$json->trans111, $html);
       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_comprador["uid"]."&act=0&em=".$data_comprador["empresa"], $html); 
    
        $para      = $data_comprador['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans123_." ".$data_producto["titulo"];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    
    }


    function htmlEmail_declino__entrega_vendedor_envio(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto, Array $datawbs, String $url_imagen){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo8.html");
        
        
        $html = str_replace("{{trans105_brand}}",$json->trans105_brand, $html);
        $html = str_replace("{{trans24}}",$json->trans24, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans106}}",$json->trans106, $html);
        $html = str_replace("{{trans100}}",$json->trans100, $html);
        $html = str_replace("{{trans101}}",$json->trans101, $html);
        $html = str_replace("{{producto_mal_estado_brand}}",$url_imagen, $html);
        $html = str_replace("{{respuesta_comprador}}",$datawbs["descripcion"], $html);
        

        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{trans98}}",$json->trans98, $html);
        $html = str_replace("{{trans104}}",$json->trans104, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);

       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans124_." ".$data_comprador["nombre"]." ".$json->trans125_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    
    }


    function htmlEmail_se_congelo_pago(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto, Array $datawbs, String $url_imagen){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo10.html");
        
        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }
        
        $html = str_replace("{{id_carrito}}", $producto["id_carrito"], $html);
        $html = str_replace("{{trans124_}}",$json->trans124_, $html);
        $html = str_replace("{{trans249}}",$json->trans249, $html);
        $html = str_replace("{{trans250}}",$json->trans250, $html);
        $html = str_replace("{{trans251}}",$json->trans251, $html);
        $html = str_replace("{{trans252}}",$json->trans251, $html);
        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
        $html = str_replace("{{trans94}}",$json->trans94, $html);
        $html = str_replace("{{trans95}}",$json->trans95, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{trans96}}",$json->trans96, $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($producto['precio']), $html);
        $html = str_replace("{{moneda_unidad}}",$producto['moneda'], $html);
        
        $html = str_replace("{{trans98}}",$json->trans98, $html);
        $html = str_replace("{{trans24}}",$json->trans24, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);

       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 

        $html = str_replace("{{trans97}}",$json->trans97, $html);
    
        $para      = $data_vendedor['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans126_." ".$data_producto['titulo']." ".$json->trans127_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }



    function htmlEmail_confirmo_vendedor_entrega_envio_subasta(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo26tuproductohallegado.html");


        $html = str_replace("{{nombre_usuario}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans32_brand}}",$json->trans32_brand, $html);
        $html = str_replace("{{trans33}}",$json->trans33, $html);
        $html = str_replace("{{trans34}}",$json->trans34, $html);        
        $html = str_replace("{{trans35}}",$json->trans35, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);

        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 

        $para      = $data_vendedor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans122_." ".$data_comprador["nombre"];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


    public function htmlEmail_declino__entrega_vendedor_envio_caso_especial(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto, Array $datawbs)
    {
           $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
           $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo9.html");
       
       
       
           $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
           $html = str_replace("{{nombre_comprador}}",ucfirst($data_comprador['nombre']), $html);
           $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
           $html = str_replace("{{trans100}}",$json->trans100, $html);
           $html = str_replace("{{trans101}}",$json->trans101, $html);
           $html = str_replace("{{trans19}}",$json->trans19, $html);
           $html = str_replace("{{trans102}}",$json->trans102, $html);
           $html = str_replace("{{trans103}}",$json->trans103, $html);
           $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
           $html = str_replace("{{trans98}}",$json->trans98, $html);
           $html = str_replace("{{trans24}}",$json->trans24, $html);
           $html = str_replace("{{trans104}}",$json->trans104, $html);
           
           $html = str_replace("{{descripcion_queja}}",$datawbs["descripcion"], $html);
           
           $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
           $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
   
          
       
           $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
           $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
           $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
           $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
           $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
           $html = str_replace("{{trans06_}}",$json->trans06_, $html);
           $html = str_replace("{{trans07_}}",$json->trans07_, $html);
           $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
       
           $para      = $data_vendedor['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
           $mensaje1   = $html;
           $titulo    = $json->trans124_." ".$data_comprador["nombre"]." ".$json->trans125_;
           $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
           $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
           $cabeceras .= 'From: info@nasbi.com' . "\r\n";
           //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
           $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
           return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function htmlEmail_se_congelo_pago_subasta(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto, Array $datawbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo28pagocongelado.html");
    
    
    
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($data_comprador['nombre']), $html);
        $html = str_replace("{{trans17_brand}}",$json->trans17_brand, $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{trans20}}",$json->trans20, $html);
        $html = str_replace("{{titulo_producto_pago_congelado}}", $data_producto['titulo'], $html);
        $html = str_replace("{{trans21}}",$json->trans21, $html);
        $html = str_replace("{{numero_pago}}",$datawbs["id_carrito"], $html);
        $html = str_replace("{{trans22}}",$json->trans22, $html);
        $html = str_replace("{{trans23}}",$json->trans23, $html);
        $html = str_replace("{{trans24}}",$json->trans24, $html);
        $html = str_replace("{{trans25}}",$json->trans25, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        
       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans124_." ".$data_comprador["nombre"]." ".$json->trans125_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


    function html_calificaron_vendedor(Array $data_vendedor){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo27publicaunanuevaventa.html");
    
        $html = str_replace("{{trans26_brand}}",$json->trans26_brand, $html);
        $html = str_replace("{{trans27_brand}}",$json->trans27_brand, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);  
        
        $html = str_replace("{{trans136_}}",$json->trans136_, $html);
        $html = str_replace("{{trans137_}}",$json->trans137_, $html);
        $html = str_replace("{{trans138_}}",$json->trans138_, $html);
        $html = str_replace("{{link_to_contacto}}",$json->link_to_contacto, $html);
        
        
        $html = str_replace("{{trans28}}",$json->trans28, $html);
        $html = str_replace("{{trans29}}",$json->trans29, $html);
        $html = str_replace("{{trans30}}",$json->trans30, $html);
        $html = str_replace("{{link_to_vender}}",$json->link_to_vender, $html);
        $html = str_replace("{{trans31}}",$json->trans31, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans142_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


 ///fin comienzo de funciones para envio de correos 

}

?>