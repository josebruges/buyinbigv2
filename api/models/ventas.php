<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Ventas extends Conexion
{
    // tipo_timeline: 1= VENDEDOR, 2= COMPRADOR
    public function misVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;


        $estado = " ";
        $pasoPor = "";
        if ( isset($data['estado']) ) {
            $pasoPor = "[ " . $data['estado'] . " ] ";
        }else{
            $pasoPor = "[ Nada ] ";
        }
        /* if ( isset($data['estado']) ) {
            if ( $data['estado'] != "") {
                if ( intval("" . $data['estado']) == 1) {
                    // Ordenes activas
                    $estado = " AND pt.estado < 13 ";
                    $pasoPor = "/1 ";
                }else{
                    // Ordenes inactivas
                    $estado = " AND pt.estado = 13 ";
                    $pasoPor .= "/2 ";
                }
            }else{
                if ( isset($data['ordenar']) ) {
                    if ( $data['ordenar'] != "") {
                        $estado = " AND pt.estado = '$data[ordenar]' ";
                        $pasoPor .= "/3 ";
                    }else{
                        $estado = " AND pt.estado <= 13 ";
                        $pasoPor .= "/4 ";
                    }
                }else{
                    $estado = " AND pt.estado <= 13 ";
                    $pasoPor .= "/5 ";
                }
            }
        }else {
        } */
        if ( isset($data['ordenar']) ) {
            if ( $data['ordenar'] != "") {
                $estado = " AND pt.estado = '$data[ordenar]' ";
                $pasoPor .= "/6 ";
            }else{
                $estado = " AND pt.estado <= 13 ";
                $pasoPor .= "/7 ";
            }
        }else{
            $estado = " AND pt.estado <= 13 ";
            $pasoPor .= "/8 ";
        }

        $exposicion = "";
        if(isset($data['exposicion'])) $exposicion = "AND p.exposicion = '$data[exposicion]'";
        
        $pagina        = floatval($data['pagina']);
        $numpagina     = 9;
        $hasta         = $pagina * $numpagina;
        $desde         = ($hasta - $numpagina) + 1;

        $request = null;
        parent::conectar();
        $selecxventas = 
        "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT
                    pt.*,
                    p.titulo,
                    p.foto_portada,
                    pte.descripcion AS descripcion_estado,
                    ptt.descripcion AS descripcion_tipo,
                    p.exposicion,
                    p.fecha_creacion AS fecha_creacion_producto,
                    (SELECT SUM(hp.visitas) FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto) AS visitas,
                    (SELECT SUM(pt.cantidad) FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10) AS cantidad_vendidas

                FROM productos_transaccion pt
                LEFT JOIN productos_transaccion_guia_tcc ptg_tcc ON ptg_tcc.id_productos_transaccion = pt.id
                LEFT JOIN productos p ON pt.id_producto = p.id
                LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                JOIN (SELECT @row_number:=0) r
                WHERE (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]') $estado $exposicion
                ORDER BY id DESC
            )as datos ORDER BY id DESC
        )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";
        
        // var_dump($selecxventas);
        /* echo $selecxventas; */
        $ventas = parent::consultaTodo($selecxventas);


        $selecxventasxtotal = "SELECT * FROM productos_transaccion pt WHERE (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]') $estado $exposicion";
        $selecxventasxtotal = parent::consultaTodo($selecxventasxtotal);

        $selecxventasxtotal = count( $selecxventasxtotal );
        $totalpaginas = $selecxventasxtotal/$numpagina;
        $totalpaginas = ceil($totalpaginas);

        $ventas = $this->mapeoVentas($ventas, true);
        parent::cerrar();

        return array(
            'status'           =>  'success',
            'message'          =>  'mis ventas',
            'pagina'           =>  $pagina,
            'total_paginas'    =>  $totalpaginas,
            'productos'        =>  count($ventas),
            'total_productos'  =>  count($selecxventasxtotal),
            'data'             =>  $ventas
        );
    }

    public function confirmarVenta(Array $data)
    {
        if(!isset($data) || !isset($data['id_carrito']) || !isset($data['id']) || !isset($data['uid']) || !isset($data['confirmar'])) return array('status' => 'fail', 'message'=> 'No data en: id_carrito, id, uid, confirmar', 'data' => $data);


        if($data['confirmar'] == 0 && !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'no data en: confirmar, descripcion', 'data' => $data);
        
        $ventas = $this->transaccionCarrito($data, 1);
        if($ventas['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        
        $ventas = $ventas['data'];
        $estado = 1;
        $fecha = intval(microtime(true)*1000);


        // return $ventas;
        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        foreach ($ventas as $key => $venta) {
            $nasbifunciones = new Nasbifunciones();
            $data_wallet_comprador = $nasbifunciones->walletNasbiUsuario([
                "uid"               => $venta['uid_comprador'],
                "empresa"           => $venta['empresa_comprador'],
                "ver_transacciones" => 1,
                "iso_code_2"        => "CO"
            ]);
            $data_wallet_vendedor = $nasbifunciones->walletNasbiUsuario([
                "uid"               => $venta['uid_vendedor'],
                "empresa"           => $venta['empresa'],
                "ver_transacciones" => 1,
                "iso_code_2"        => "CO"
            ]);
            $precio_divisa_usd = 0;
            // Aquí se envia el dinero a la cuenta "POTE".
            $cuentaPote = $nasbifunciones->getCuentaBanco( 1 );

            unset($nasbifunciones);
            if($data['confirmar'] == 1){
                
                if($venta['id_metodo_pago'] == 1){

                    // Validar
                    $result = $this->deboPagarComisionEnGratuita([
                        'uid'         => $venta['uid_vendedor'],
                        'empresa'     => $venta['empresa'],
                        'id_producto' => $venta['id_producto']
                    ]);
                    
                    $data['adicional']['address_vendedor_sd'] = $data_wallet_vendedor['nasbicoin_gold']['address'];
                    $data['adicional']['address_vendedor_bd'] = $data_wallet_vendedor['nasbicoin_blue']['address'];

                    if ($venta['moneda'] == "Nasbigold") {
                        $data['adicional']['address_vendedor'] = $data['adicional']['address_vendedor_sd'];
                    } else {
                        $data['adicional']['address_vendedor'] = $data['adicional']['address_vendedor_bd'];
                    }

                    if(!isset($data['adicional']) || !isset($data['adicional']['address_vendedor']) || !isset($data['uid'])) return array('status' => 'fail', 'message'=> 'no data en: adicional, adicional/address_vendedor, uid', 'data' => $data['adicional']);
                    
                    $estado = 6;

                    if($venta['tipo'] == 1){
                        $address_vendedor = addslashes($data['adicional']['address_vendedor']);

                        $nasbifunciones = new Nasbifunciones();
                        if ( ($result['result'] && intval( $result['data']['exposicion'] ) == 1) || intval( $result['data']['exposicion'] ) != 1 ) {
                            // Son las publicacion gratuitas

                            $porcentajeExposicion = 0;

                            if ( ($result['result'] && intval( $result['data']['exposicion'] ) == 1) ) {
                                $porcentajeExposicion = floatval($result['data']['porcentaje_premium']);
                                $this->notificarCobroPremium($venta);
                            }else{
                                if ( intval( $result['data']['exposicion'] ) == 1) {
                                    $porcentajeExposicion = floatval($result['data']['porcentaje_gratuita']);
                                    
                                }else if ( intval( $result['data']['exposicion'] ) == 2) {
                                    $porcentajeExposicion = floatval($result['data']['porcentaje_clasica']);

                                }else if ( intval( $result['data']['exposicion'] ) == 3) {
                                    $porcentajeExposicion = floatval($result['data']['porcentaje_premium']);

                                }else{
                                }
                            }

                            $venta['precio']          = floatval($venta['precio']);
                            $venta['precio_usd']      = floatval($venta['precio_usd']);
                            $precio_divisa_usd        = ( $venta['precio'] / $venta['precio_usd'] );
                            $comisionPlataforma       = 0;
                            $comisionPlataformaUSD    = 0;
                            $comisionPlataformaCrypto = 0;

                            if ( $venta['moneda'] == "Nasbigold") {
                                // Aquí se envia el dinero a la cuenta "POTE".
                                $selectxpagoxpendiente = "SELECT * FROM buyinbig.saldo_pendiente_pagar WHERE id_carrito = '$venta[id_carrito]' AND id_producto = '$venta[id_producto]';";

                                parent::conectar();
                                $selectpagopendiente = parent::consultaTodo($selectxpagoxpendiente);
                                parent::cerrar();


                                if ( count($selectpagopendiente) < 0 ) {
                                    return array(
                                        'status' => 'failSaldoPendiente',
                                        'message'=> 'No fue posible obtener información respecto al saldo pendiente a pagar.',
                                        'data' => $selectpagopendiente
                                    );
                                }
                                parent::conectar();
                                $selectxinfoxplan = parent::consultaTodo("SELECT p.id, p.porcentaje_nasbi FROM peer2win.usuarios u INNER JOIN peer2win.paquetes p ON (u.plan = p.id) WHERE u.id = '$venta[refer]';");
                                parent::cerrar();

                                $selectxinfoxplan                     = $selectxinfoxplan[0];
                                $selectxinfoxplan['id']               = intval( $selectxinfoxplan['id'] );
                                $selectxinfoxplan['porcentaje_nasbi'] = floatval( $selectxinfoxplan['porcentaje_nasbi'] );

                                $saldoPendiente        = $selectpagopendiente[0];
                                $comisionPlataforma    = $porcentajeExposicion * floatval( $saldoPendiente['monto'] );
                                $comisionPlataformaUSD = $comisionPlataforma / $precio_divisa_usd;

                                $discriminacionPrecios = 
                                "\n\n\n\nLa cuenta es de: $" . floatval( $saldoPendiente['monto'] ) . 
                                "\ncomision (monto): " . floatval( $saldoPendiente['monto'] ) . 
                                "\ncomision venta(id): " . $venta['id'] . 
                                "\ncomision (id_carrito): " . $venta['id_carrito'] . 
                                "\ncomision (id_producto): " . $venta['id_producto'] . 
                                "\ncomision (porcentajeExposicion): " . $porcentajeExposicion . 
                                "\ncomision: (comisionPlataforma): " . $comisionPlataforma . 
                                "\ncomision: (precio_divisa_usd): " . $precio_divisa_usd . 
                                "\ncomision: (comisionPlataformaUSD): " . $comisionPlataformaUSD .
                                "\ncomision: (precio_en_cripto): " . ($comisionPlataformaUSD / $venta['precio_moneda_actual_usd']) .
                                "\nCOMISION: (precio_en_cripto): " . ($comisionPlataformaUSD / $precios[ $venta['moneda'] ]) .

                                "\n INFORMACIÓN PLAN/JSON: " . json_encode($selectxinfoxplan) .
                                "\n INFORMACIÓN PLAN/ID: " . $selectxinfoxplan['id'] .
                                "\n INFORMACIÓN PLAN/PORCENTAJE NASBI: " . $selectxinfoxplan['porcentaje_nasbi'];


                                // parent::addLog($discriminacionPrecios);

                                parent::conectar();
                                $queryxtransaccion = 
                                "UPDATE buyinbig.productos_transaccion
                                SET
                                    refer_porcentaje_comision        = '$porcentajeExposicion',
                                    refer_porcentaje_comision_idplan = '$selectxinfoxplan[id]',
                                    refer_porcentaje_comision_plan   = '$selectxinfoxplan[porcentaje_nasbi]'
                                WHERE id = '$venta[id]';";
                                $updatetransaccion = parent::query($queryxtransaccion);
                                parent::cerrar();

                                $diferido = $nasbifunciones->insertarBloqueadoDiferido([
                                    'id' => null,
                                    'uid' => $cuentaPote['uid'],
                                    'empresa' => $cuentaPote['empresa'],
                                    'moneda' => $venta['moneda'], // Nasbigold
                                    'all' => false,

                                    'precio' => $comisionPlataforma, // Comision que se le paga a la plataforma cripto.
                                    'precio_momento_usd' => $comisionPlataformaUSD, // Comision que se le paga a la plataforma en USD.

                                    'precio_en_cripto' => ($comisionPlataformaUSD / $precios[ $venta['moneda'] ]), // Cuanto cuesta el articulo en cripto.

                                    'address' => $cuentaPote['address_Nasbigold'],
                                    'id_transaccion' => $venta['id_carrito'],
                                    'tipo_transaccion' => $venta['tipo'],
                                    'tipo' => 'diferido',
                                    'accion' => 'push',
                                    'descripcion' => addslashes('Venta de producto '.$venta['titulo']),
                                    'fecha' => $fecha,
                                    'id_producto' => $venta['id_producto']
                                ]);
                            }
                            $diferido = $nasbifunciones->insertarBloqueadoDiferido([
                                'id' => null,
                                'uid' => $venta['uid_vendedor'],
                                'empresa' => $venta['empresa'],
                                'moneda' => $venta['moneda'], // Nasbigold
                                'all' => false,
                                'precio' => $venta['precio'] - $comisionPlataforma, // costo total del articulo
                                'precio_momento_usd' => $venta['precio_usd'] - $comisionPlataformaUSD, // 30000

                                'precio_en_cripto' => (($venta['precio_usd'] - $comisionPlataformaUSD) / $precios[ $venta['moneda'] ]), // Cuanto cuesta el 

                                'address' => $address_vendedor,
                                'id_transaccion' => $venta['id_carrito'],
                                'tipo_transaccion' => $venta['tipo'],
                                'tipo' => 'diferido',
                                'accion' => 'push',
                                'descripcion' => addslashes('Venta de producto '.$venta['titulo']),
                                'fecha' => $fecha,
                                'id_producto' => $venta['id_producto']
                            ]);
                            unset($nasbifunciones);

                        }else{
                            // No tiene que pagar comisiones.
                            $diferido = $nasbifunciones->insertarBloqueadoDiferido([
                                'id' => null,
                                'uid' => $venta['uid_vendedor'],
                                'empresa' => $venta['empresa'],
                                'moneda' => $venta['moneda'], // Nasbigold
                                'all' => false,
                                'precio' => $venta['precio'], // costo total del articulo
                                'precio_momento_usd' => $venta['precio_usd'], // 30000

                                'precio_en_cripto' => ($venta['precio_usd'] / $precios[ $venta['moneda'] ]),

                                'address' => $address_vendedor,
                                'id_transaccion' => $venta['id_carrito'],
                                'tipo_transaccion' => $venta['tipo'],
                                'tipo' => 'diferido',
                                'accion' => 'push',
                                'descripcion' => addslashes('Venta de producto '.$venta['titulo']),
                                'fecha' => $fecha,
                                'id_producto' => $venta['id_producto']
                            ]);
                            unset($nasbifunciones);
                        }

                        if($diferido['status'] == 'fail') return $diferido;

                        $updatedetalle = $this->actualizarDetallePago([
                            'id' => $venta['detalle_pago']['id'],
                            'address_vendedor' => $address_vendedor,
                            'confirmado' => 1,
                            'fecha' => $fecha
                        ]);
                        if($updatedetalle['status'] != 'success') return array('status' => 'fail', 'message'=> 'error al actualizar el detalle de pago', 'data' => null);

                        $montonoti = $this->maskNumber($venta['precio'], 2);
                        if($venta['boolcripto'] == 1) $montonoti = $this->maskNumber($venta['precio'], 2);

                        $notificacion = new Notificaciones();
                        $notificacion->insertarNotificacion([
                            'uid' => $venta['uid_vendedor'],
                            'empresa' => $venta['empresa'],

                            'text' => 'Se ha agregado a tus diferidos el monto de '.$montonoti.' '.$venta['moneda'].' por la venta del producto '.$venta['titulo'] . '. Recuerda que sobre este valor se descontará la comisión por uso de la plataforma. Para mayor información visita https://nasbi.com/content/costo-publicacion.php',

                            'es' => 'Se ha agregado a tus diferidos el monto de '.$montonoti.' '.$venta['moneda'].' por la venta del producto '.$venta['titulo'] . '. Recuerda que sobre este valor se descontará la comisión por uso de la plataforma. Para mayor información visita https://nasbi.com/content/costo-publicacion.php',

                            'en' => 'The amount of '. $montonoti .' Has been added to your deferred payments. '. $venta['moneda'] .' for the sale of the product '. $venta['titulo'] . '. Remember that the commission for using the platform will be discounted on this value. For more information visit https://nasbi.com/content/costo-publicacion.php',

                            'keyjson' => '',
                            'url' => 'costo-publicacion.php'
                        ]);
                        unset($notificacion);
                    }

                }elseif ($venta['id_metodo_pago'] == 2) {
                    $estado = 3;
                }
            }else{
                if($venta['id_metodo_pago'] == 1){
                   
                    $data['adicional']['address_comprador_sd'] = $data_wallet_comprador['nasbicoin_gold']['address'];
                    $data['adicional']['address_comprador_bd'] = $data_wallet_comprador['nasbicoin_blue']['address'];

                    if ($venta['moneda'] == "Nasbigold") {
                        $data['adicional']['addres_comprador'] = $data['adicional']['address_comprador_sd'];
                    } else {
                        $data['adicional']['addres_comprador'] = $data['adicional']['address_comprador_bd'];
                    }

                    $nasbifunciones = new Nasbifunciones();
                    $diferido = $nasbifunciones->insertarBloqueadoDiferido([
                        'id' => $venta['detalle_pago']['id_bloqueado_diferido_comprador'],
                        'uid' => $venta['uid_comprador'],
                        'empresa' => $venta['empresa_comprador'],
                        'moneda' => $venta['moneda'],
                        'all' => false,
                        'precio' => $venta['precio'],
                        'precio_momento_usd'=> null,
                        'address' => $venta['detalle_pago']['addres_comprador'],
                        'id_transaccion' => $venta['id_carrito'],
                        'tipo_transaccion' => $venta['tipo'],
                        'tipo' => 'bloqueado',
                        'accion' => 'reverse',
                        'descripcion' => null,
                        'fecha' => $fecha,
                        'id_producto' => $venta['id_producto']
                    ]);
                    unset($nasbifunciones);
                    
                    if($diferido['status'] == 'fail') return $diferido;
                    
                    $updatedetalle = $this->actualizarDetallePago([
                        'id' => $venta['detalle_pago']['id'],
                        'address_vendedor' => null,
                        'confirmado' => 0,
                        'fecha' => $fecha
                    ]);
                    if($updatedetalle['status'] == 'fail') return array('status' => 'fail', 'message'=> 'error al actualizar el detalle de pago', 'data' => null);
                    $estado = 2;
                }else if ($venta['id_metodo_pago'] == 2) {
                    $estado = 2;
                }
            }
        }
        
        if($estado == 2){
            $this->insertarTransaccionRechazada([
                'uid' => $data['uid'],
                'id_transaccion' => $data['id_carrito'],
                'tipo' => $estado,
                'descripcion' => $data['descripcion'],
                'fecha' => $fecha
            ]);
        }

        $msjfinal = 'venta confirmada';  //si cambia este mensaje deberia cambiarlo en la funcion envio de correo en switch
        if($data['confirmar'] == 0) $msjfinal = 'venta rechazada';

        if($data['confirmar'] == 1){
            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_comprador'],
                'empresa' => $venta['empresa_comprador'],
                'text' => 'El vendedor del producto '.$venta['titulo'].' confirmó tu compra, te invitamos a que revises tus compras para continuar con el proceso y puedas tener tu producto ya',
                'es' => 'El vendedor del producto '.$venta['titulo'].' confirmó tu compra, te invitamos a que revises tus compras para continuar con el proceso y puedas tener tu producto ya',
                'en' => 'The seller of the '.$venta['titulo'].' product confirmed your purchase, we invite you to review your purchases to continue with the process and you can have your product already',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);
        }else{
            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_comprador'],
                'empresa' => $venta['empresa_comprador'],
                'text' => 'El vendedor del producto '.$venta['titulo'].' rechazo tu compra, te invitamos a que revises la razón del rechazo',
                'es' => 'El vendedor del producto '.$venta['titulo'].' rechazo tu compra, te invitamos a que revises la razón del rechazo',
                'en' => 'The seller of the '.$venta['titulo'].' product rejected your purchase, we invite you to review the reason for the rejection',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);
        }

        $dataAux = $data;
        $dataAux['estado'] = $estado;
        $dataAux['fecha'] = $fecha;

        return $this->actualizarEstadoTransaccion([
            'id' => $venta['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            "id_carrito" => $data["id_carrito"],
            'mensaje_success' => $msjfinal,
            'mensaje_fail' => 'error al confirmar la venta'
        ]);
    }


    public function confirmarComprobante(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['confirmar'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if($data['confirmar'] == 0 && !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        

        $venta = $this->transaccionId($data, 5);
        if($venta['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);

        $venta = $venta['data'];
        $estado = 6;
        $msjfinal = 'comprobante confirmado';
        $msjfinalfail = 'error al confirmar el comprobante';
        $contador = $venta['contador'];
        $fecha = intval(microtime(true)*1000);

        if($data['confirmar'] == 1){
            $updatedetalle = $this->actualizarDetallePago([
                'id' => $venta['detalle_pago']['id'],
                'address_vendedor' => NULL,
                'confirmado' => 1,
                'fecha' => $fecha
            ]);
            if($updatedetalle['status'] != 'success') return array('status' => 'fail', 'message'=> 'error al actualizar el detalle de pago', 'data' => null);
        }

        if($data['confirmar'] == 0){
            $estado = 4;
            $msjfinal = 'comprobante declinado';
            $msjfinalfail = 'error al declinar el comprobante';
            $contador++;
            if($contador == 3){
                $estado = 10;
                $msjfinal = 'transaccion no concretada';
            }
            $subir_comprobante = $this->insertarDetallePagoDeclinado([
                'id_transaccion' => $venta['id_carrito'],
                'id_detalle_pago' => $venta['detalle_pago']['id'],
                'conteo' => $contador,
                'url' => $venta['detalle_pago']['url'],
                'descripcion' => addslashes($data['descripcion']),
                'fecha' => $fecha
            ]);
            if($subir_comprobante['status'] != 'success') return $subir_comprobante;
        }

        if($estado == 10){
            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_vendedor'],
                'empresa' => $venta['empresa'],
                'text' => 'Tu proceso de venta del producto '.$venta['titulo'].' no fue concretado',
                'es' => 'Tu proceso de venta del producto '.$venta['titulo'].' no fue concretado',
                'en' => 'Your process to sell the product '.$venta['titulo'].' was not completed',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_ventas'
            ]);
            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_comprador'],
                'empresa' => $venta['empresa_comprador'],
                'text' => 'Tu proceso de compra del producto '.$venta['titulo'].' no fue concretado',
                'es' => 'Tu proceso de venta del producto '.$venta['titulo'].' no fue concretado',
                'en' => 'Your process to buy the product '.$venta['titulo'].' was not completed',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);
        }
        
        return $this->actualizarEstadoTransaccion([
            'id' => $venta['id'],
            'estado' => $estado,
            'fecha' => $fecha,
            'contador' => $contador,
            "id_carrito" => $venta["id_carrito"],
            'mensaje_success' => $msjfinal,
            'mensaje_fail' => $msjfinalfail
        ]);

    }

    public function rutasEnvio(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $venta = $this->transaccionIdFull($data, 2);
        if($venta['status'] == 'fail') return $venta;
        $venta = $venta['data'];

        $fromAddress = (array) json_decode(Shippo_Address::retrieve($venta['envio']['vendedor_id_shippo']), true); //EEUU
        $toAddress = (array) json_decode(Shippo_Address::retrieve($venta['envio']['comprador_id_shippo']), true); //COLOMBIA
        $parcel = (array) json_decode(Shippo_Parcel::retrieve($venta['envio']['id_prodcuto_envio']), true);
        unset($parcel['extra']);
        $net_weight = $parcel['weight'] * $venta['cantidad'];
        $net_height = $parcel['height'] * $venta['cantidad'];

        $parcel2 = (array) json_decode(Shippo_Parcel::create(array(
            'length' => $parcel['length'],
            'width' => $parcel['width'],
            'height' => $net_height,
            'distance_unit' => $parcel['distance_unit'],
            'weight' => $net_weight,
            'mass_unit' => $parcel['mass_unit'],
        )), true);
        unset($parcel2['extra']);


        // Example CustomsItems object.
        // The complete reference for customs object is here: https://goshippo.com/docs/reference#customsitems
        $customs_item =  (array) json_decode(Shippo_CustomsItem::create(array(
            'description' => $venta['titulo'],
            'quantity' => $venta['cantidad'],
            'net_weight' => $net_weight,
            'mass_unit' => $parcel2['mass_unit'],
            'value_amount' => $venta['precio_usd'],
            'value_currency' => 'USD',
            'origin_country' => $fromAddress['country'],
            'tariff_number' => '',
        )), true);

        // Creating the Customs Declaration
        // The details on creating the CustomsDeclaration is here: https://goshippo.com/docs/reference#customsdeclarations
        $customs_declaration = (array) json_decode(Shippo_CustomsDeclaration::create(
        array(
            'contents_type'=> 'MERCHANDISE',
            'contents_explanation'=> $venta['titulo'].' Compra',
            'non_delivery_option'=> 'RETURN',
            'certify'=> 'true',
            'certify_signer'=> 'Nasbi',
            'items'=> array($customs_item['object_id']),
        )), true);
        
        
        $shipment = (array) json_decode(Shippo_Shipment::create(
            array(
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'parcels' => array($parcel2),
                'customs_declaration' => $customs_declaration['object_id'],
                'async' => false
            )
        ));
      
        if($shipment['status'] != 'SUCCESS') return array('status' => 'fail', 'message'=> 'rutas', 'cantidad'=> 0, 'data' => null);

        return array(
            'status' => 'success',
            'message'=> 'rutas',
            'cantidad'=> count($shipment['rates']),
            'data' => $shipment['rates'],
            'customs_item' => $customs_item,
            'customs_declaration' => $customs_declaration,
            'shipment' => $shipment
        );
    }

    public function realizarEnvio(Array $data)
    {

        if(!isset($data) || !isset($data['id_carrito']) || !isset($data['id']) || !isset($data['uid'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $venta = $this->transaccionId($data, 6);

        if($venta['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $venta = $venta['data'];

        if( !isset($data['adicional']) || !isset($data['adicional']['numero_guia']) || !isset($data['adicional']['empresa_envio']) ) {
            return array('status' => 'fail', 'message'=> 'no data 2', 'data' => null);
        }


        // Paso 1: Indificar si tiene todos los datos.
        // $URL_CONFIRM = "http://nasbi.peers2win.com/api/controllers/siigo/?verificacion_data_cliente_con_siigo";
        // $dataArray = array(
        //     "data" => array(
        //         "uid"     => $venta['uid_vendedor'],
        //         "empresa" => $venta['empresa']
        //     )
        // );

        // parent::addLog("\t-----+> [ Venta / Siigo / verificacion_data_cliente_con_siigo ]: " . json_encode($dataArray));

        // $responseVerificarMapSiigo = parent::remoteRequest($URL_CONFIRM, $dataArray);
        // $responseVerificarMapSiigo= json_decode($responseVerificarMapSiigo, true);

        // parent::addLog("\t-----+> [ Venta / Siigo / responseVerificarMapSiigo ]: " . json_encode($responseVerificarMapSiigo));
        // parent::addLog("\t-----+> [ Venta / Siigo / venta ]: " . json_encode($venta));



        // $posibilidades = array('success' ,'actualizar' ,'sinCambios' ,'crear');
        // if( $responseVerificarMapSiigo != null ){
        //     if( !in_array($responseVerificarMapSiigo['status'], $posibilidades) ){

        //         $schemaNotificacion = Array(
        //             'uid'     => $venta['uid_vendedor'],
        //             'empresa' => $venta['empresa'],
                    
        //             'text' => 'Antes de continuar es necesario completar algunos datos relacionados con tu documento de identificación en el módulo de Configuraciones, Datos personales.',

        //             'es' => 'Antes de continuar es necesario completar algunos datos relacionados con tu documento de identificación en el módulo de Configuraciones, Datos personales.',

        //             'en' => 'Before continuing, it is necessary to complete some information related to your identification document in the Settings, Personal Data module.',

        //             'keyjson' => '',

        //             'url' => 'mis-cuentas.php?tab=sidenav_configuracion'
        //         );
                
        //         $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";

        //         parent::remoteRequest($URL, $schemaNotificacion);
        //         return array(
        //             'status'   => 'errorCompletarDatosfacturacionVendedor',
        //             'message'  => 'Se requieren datos para poder crear la factura en siigo',
        //             'data'     => $responseVerificarMapSiigo,
        //             'extra'    => in_array($responseVerificarMapSiigo['status'], $posibilidades)
        //         );
        //     }

        // }

        // // Paso 2: Creación de factura.
        // $result_generar_factura_vendedor = $this->generar_factura_vendedor( $venta );
        // if( $result_generar_factura_vendedor['status'] != 'success'){
        //     return array(
        //         'status' => $result_generar_factura_vendedor['status'],
        //         'message'=> 'No fue posible crear la factura de la venta',
        //         'data' => $result_generar_factura_vendedor,
        //         'dataExtra' => $venta
        //     );
        // }

        $selectxsiigoxdatosxfacturacion =
            "SELECT * FROM buyinbig.siigo_datos_facturacion WHERE uid = '$venta[uid_vendedor]' AND empresa = '$venta[empresa]';";
        parent::conectar();
        $siigo_datos_facturacion        = parent::consultaTodo( $selectxsiigoxdatosxfacturacion );
        parent::cerrar();
        
        // errorDocumentoyNumeroDuplicado
        // noExisteTerceroEnSiigo
        // errorCompletarDatosfacturacionVendedor

        if( COUNT( $siigo_datos_facturacion ) == 0 ){
            return array(
                'status' => 'noExisteTerceroEnSiigo',
                'message'=> 'No cuenta con información de facturación registrada.',
                'data' => null
            );
        }else{
            $result_factura_new = $this->generar_factura_vendedor_new( $venta );
            
            parent::addLog("\t-----+> [ Venta / Siigo / item / venta /result_factura_new ]: " . json_encode($result_factura_new));

            if($result_factura_new['status'] != "success"){
                return $result_factura_new;
            }
        }

        $fecha = intval(microtime(true)*1000);
        $envio = $this->actualizarEnvioTransaccion([
            'id'             => $venta['envio']['id'],
            'id_transaccion' => $venta['id'],
            'numero_guia'    => addslashes($data['adicional']['numero_guia']),
            'empresa'        => addslashes($data['adicional']['empresa_envio']),
            'fecha'          => $fecha,
            'id_carrito'     => $data['id_carrito']
        ]);

        if($envio['status'] != 'success') return $envio;

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid'     => $venta['uid_comprador'],
            'empresa' => $venta['empresa_comprador'],

            'text' => '"¡BUENAS NOTICIAS! el vendedor del producto '.$venta['titulo'].' ha enviado la guía de envío, nos alegra que  cada vez estás más cerca de tu producto',

            'es' => '"¡BUENAS NOTICIAS! el vendedor del producto '.$venta['titulo'].' ha enviado la guía de envío, nos alegra que  cada vez estás más cerca de tu producto',

            'en' => 'GOOD NEWS! the seller of the product '.$venta['titulo'].' has sent the shipping guide, we are glad you are getting closer to your product',
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_compras'
        ]);
        unset($notificacion);
        
        $estado = 7;
        return $this->actualizarEstadoTransaccion([
            'id'              => $venta['id'],
            'estado'          => $estado,
            'fecha'           => $fecha,
            "id_carrito"      => $venta["id_carrito"],
            'mensaje_success' => 'numero de guia enviado',
            'mensaje_fail'    => 'numero de guia no enviado'
        ]);
    }
    // public function generar_factura_vendedor(Array $venta){

    //     // Paso 2: Validar si completo el TOPE DE 3 VENTAS con EXPOSICIÓN GRATUITA.
    //     parent::addLog("\t-----+> [ Venta / Siigo / item / venta ]: " . json_encode($venta));
    //     $nasbifuncionesTopeVentas = new Nasbifunciones();
    //     $resultTopeVentas = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
    //         'uid'     => $venta['uid_vendedor'],
    //         'empresa' => $venta['empresa']
    //     ]);
    //     unset($nasbifuncionesTopeVentas);

    //     // Paso 3: Creación de factura SIIGO

    //     $pago_digital_calculos_pasarelas_id = $venta['pago_digital_calculos_pasarelas_id'];

    //     $pagoxdigitalxcalculosxpasarelas = 
    //     "SELECT
    //         valor_comision_nasbi,
    //         valor_comision_nasbi_segundo_metodo,
    //         iva,
    //         iva_segundo_metodo,
    //         SUM(valor_comision_nasbi + valor_comision_nasbi_segundo_metodo) AS 'COMISION_SIN_IVA',
    //         SUM(iva + iva_segundo_metodo)                                   AS 'COMISION_CON_IVA'
    //     FROM buyinbig.pago_digital_calculos_pasarelas
    //     WHERE id = $pago_digital_calculos_pasarelas_id;";


    //     $comisionCategoriaSinIva = 0;
    //     $comisionCategoriaConIva = 0;

    //     parent::conectar();
    //     $pago_digital_calculos_pasarelas = parent::consultaTodo( $pagoxdigitalxcalculosxpasarelas );
    //     parent::cerrar();
    //     if( COUNT( $pago_digital_calculos_pasarelas ) == 0 ){
    //         $pago_digital_calculos_pasarelas = $pago_digital_calculos_pasarelas[0];

    //         $pago_digital_calculos_pasarelas['COMISION_SIN_IVA']= floatval($pago_digital_calculos_pasarelas['COMISION_SIN_IVA']);
    //         $pago_digital_calculos_pasarelas['COMISION_CON_IVA']= floatval($pago_digital_calculos_pasarelas['COMISION_CON_IVA']);


    //         $comisionCategoriaSinIva = $pago_digital_calculos_pasarelas['COMISION_SIN_IVA'];
    //         $comisionCategoriaConIva = $pago_digital_calculos_pasarelas['COMISION_CON_IVA'];
    //     }


    //     $observacion = 'Cobro comisión del '. ($venta['comisiones_categoria_pt'] * 100) . '% - Ref: #' . $venta['id'] . ' - Ref Cart: #' . $venta['id_carrito'];

    //     $venta['exposicion_pt']                         = intval( $venta['exposicion_pt'] );
    //     $venta['categoria_pt']                          = intval( $venta['categoria_pt'] );
    //     $venta['subcategoria_pt']                       = intval( $venta['subcategoria_pt'] );
        
    //     $venta['codigo_producto_siigo']                 = "";
    //     $venta['codigo_producto_siigo_descripcion']     = "";
        
    //     parent::addLog("\t-----+> [ Venta / Siigo / item / exposicion_pt ]: " . $venta['exposicion_pt']);
    //     if( $venta['exposicion_pt'] == 1 ){
    //         $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_gratuita'] );
    //         $venta['codigo_producto_siigo']             = "";
    //         $venta['codigo_producto_siigo_descripcion'] = "";

    //     }else if( $venta['exposicion_pt'] == 2 ){
    //         $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_clasica'] );
    //         $venta['codigo_producto_siigo']             = $venta['codigo_producto_siigo_clasica'];
    //         $venta['codigo_producto_siigo_descripcion'] = $venta['codigo_producto_siigo_clasica_descripcion'];

    //     }else if( $venta['exposicion_pt'] == 3 ){
    //         $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_premium'] );
    //         $venta['codigo_producto_siigo']             = $venta['codigo_producto_siigo_premium'];
    //         $venta['codigo_producto_siigo_descripcion'] = $venta['codigo_producto_siigo_premium_descripcion'];

    //     }

    //     parent::addLog("\t-----+> [ Venta / Siigo / item / codigo_producto_siigo ]: " . $venta['codigo_producto_siigo']);
    //     parent::addLog("\t-----+> [ Venta / Siigo / item / codigo_producto_siigo_descripcion ]: " . $venta['codigo_producto_siigo_descripcion']);


    //     // Casi 1: Cuando es 100% nasbichips, sin bonos de descuento

    //     $venta['exposicion_pt']       = intval( $venta['exposicion_pt'] );
    //     $venta['tipo']                = intval( $venta['tipo'] );
    //     $venta['precio']              = floatval( $venta['precio'] );
    //     $venta['precio_segundo_pago'] = floatval( $venta['precio_segundo_pago'] );
    //     $venta['precio_comprador']    = floatval( $venta['precio_comprador'] );

    //     if( $venta['exposicion_pt'] > 1 ){
    //         $totalCompra = 0;
    //         if( $venta['tipo'] == 1){
    //             $totalCompra              = $venta['precio'] + $venta['precio_segundo_pago'];

    //         }else{
    //             $totalCompra              = $venta['precio_comprador'];
    //         }

    //         if( $comisionCategoriaSinIva == 0 || $comisionCategoriaConIva == 0 ){
    //             $comisionCategoriaSinIva      = $totalCompra * $venta['comisiones_categoria_pt'];
    //             $comisionCategoriaSinIva      = round($comisionCategoriaSinIva);

    //             $comisionCategoriaConIva      = $comisionCategoriaSinIva * 1.19;
    //             $comisionCategoriaConIva      = floatval("" . number_format($comisionCategoriaConIva, 2, '.', ''));
    //         }


    //         $requestSiigo = Array(
    //             "data" => Array(
    //                 "uid"          => $venta['uid_vendedor'],
    //                 "empresa"      => $venta['empresa'],
    //                 "observations" => $observacion,
    //                 "items" => Array(
    //                     Array(
    //                         "code"        => $venta['codigo_producto_siigo'],
    //                         "description" => $venta['codigo_producto_siigo_descripcion'],
    //                         "quantity"    => 1,
    //                         "price"       => $comisionCategoriaSinIva,
    //                         "discount"    => 0,
    //                         "taxes"       => 19
    //                     )
    //                 ),
    //                 "payments" => Array(
    //                     Array(
    //                         "value" => $comisionCategoriaConIva
    //                     )
    //                 )
    //             )
    //         );

    //         parent::addLog("\t-----+> [ Venta / Siigo / item / requestSiigo ]: " . json_encode($requestSiigo));

    //         // Recuerda que si 'codigo_producto_siigo' es vacio no se crea factura.
    //         $uid_permitidos      = intval("" . $venta['uid_vendedor']);
    //         $empresa_permitidos  = intval("" . $venta['empresa']);

    //         $puede_crear_factura = false;

    //         if( $uid_permitidos == 3014 && $empresa_permitidos == 0 ){
    //             $puede_crear_factura = true;

    //         }else if( $uid_permitidos == 3016 && $empresa_permitidos == 0 ){
    //             $puede_crear_factura = true;
            
    //         }else if( $uid_permitidos == 411 && $empresa_permitidos == 1 ){
    //             $puede_crear_factura = true;

    //         }else{
    //             $puede_crear_factura = false;
    //         }

    //         parent::addLog("-----+> [ A: Carrito / siigo/?crear_factura ]: " . json_encode([ "SEND" => $requestSiigo]));

    //         $responseSiigo = parent::remoteRequest("http://nasbi.peers2win.com/api/controllers/siigo/?crear_factura", $requestSiigo);
    //         $responseSiigo = json_decode($responseSiigo, true);
           
    //         parent::addLog("-----+> [ B: Carrito / siigo/?crear_factura ]: " . json_encode([ "SEND" => $requestSiigo, "REVICED" => $responseSiigo]));
    //         return $responseSiigo;
    //     }else{
    //         return array(
    //             'status' => 'success',
    //             'message'=> 'No requiere factura por ser de tipo gratuita la publicacion',
    //             'data' => $venta
    //         );
    //     }
    // }
    public function generar_factura_vendedor_new(Array $venta){

        // Paso 2: Validar si completo el TOPE DE 3 VENTAS con EXPOSICIÓN GRATUITA.
        parent::addLog("\t-----+> [ Venta / Siigo / item / venta ]: " . json_encode($venta));
        $nasbifuncionesTopeVentas = new Nasbifunciones();
        $resultTopeVentas = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
            'uid'     => $venta['uid_vendedor'],
            'empresa' => $venta['empresa']
        ]);
        unset($nasbifuncionesTopeVentas);

        // Paso 3: Creación de factura SIIGO

        $pago_digital_calculos_pasarelas_id = $venta['pago_digital_calculos_pasarelas_id'];

        $pagoxdigitalxcalculosxpasarelas = 
            "SELECT
                valor_comision_nasbi,
                valor_comision_nasbi_segundo_metodo,
                iva,
                iva_segundo_metodo,
                SUM(valor_comision_nasbi + valor_comision_nasbi_segundo_metodo) AS 'COMISION_SIN_IVA',
                SUM(iva + iva_segundo_metodo)                                   AS 'COMISION_CON_IVA'
            FROM buyinbig.pago_digital_calculos_pasarelas
            WHERE id = $pago_digital_calculos_pasarelas_id;";


        $comisionCategoriaSinIva = 0;
        $comisionCategoriaConIva = 0;

        parent::conectar();
        $pago_digital_calculos_pasarelas = parent::consultaTodo( $pagoxdigitalxcalculosxpasarelas );
        parent::cerrar();
        if( COUNT( $pago_digital_calculos_pasarelas ) == 0 ){
            $pago_digital_calculos_pasarelas = $pago_digital_calculos_pasarelas[0];

            $pago_digital_calculos_pasarelas['COMISION_SIN_IVA']= floatval($pago_digital_calculos_pasarelas['COMISION_SIN_IVA']);
            $pago_digital_calculos_pasarelas['COMISION_CON_IVA']= floatval($pago_digital_calculos_pasarelas['COMISION_CON_IVA']);


            $comisionCategoriaSinIva = $pago_digital_calculos_pasarelas['COMISION_SIN_IVA'];
            $comisionCategoriaConIva = $pago_digital_calculos_pasarelas['COMISION_CON_IVA'];
        }


        $observacion = 'Cobro comisión del '. ($venta['comisiones_categoria_pt'] * 100) . '% - Ref: #' . $venta['id'] . ' - Ref Cart: #' . $venta['id_carrito'];

        $venta['exposicion_pt']                         = intval( $venta['exposicion_pt'] );
        $venta['categoria_pt']                          = intval( $venta['categoria_pt'] );
        $venta['subcategoria_pt']                       = intval( $venta['subcategoria_pt'] );
        
        $venta['codigo_producto_siigo']                 = "";
        $venta['codigo_producto_siigo_descripcion']     = "";
        
        parent::addLog("\t-----+> [ Venta / Siigo / item / exposicion_pt ]: " . $venta['exposicion_pt']);
        if( $venta['exposicion_pt'] == 1 ){
            $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_gratuita'] );
            $venta['codigo_producto_siigo']             = "";
            $venta['codigo_producto_siigo_descripcion'] = "";

        }else if( $venta['exposicion_pt'] == 2 ){
            $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_clasica'] );
            $venta['codigo_producto_siigo']             = $venta['codigo_producto_siigo_clasica'];
            $venta['codigo_producto_siigo_descripcion'] = $venta['codigo_producto_siigo_clasica_descripcion'];

        }else if( $venta['exposicion_pt'] == 3 ){
            $venta['comisiones_categoria_pt']           = floatval( $venta['porcentaje_premium'] );
            $venta['codigo_producto_siigo']             = $venta['codigo_producto_siigo_premium'];
            $venta['codigo_producto_siigo_descripcion'] = $venta['codigo_producto_siigo_premium_descripcion'];

        }

        parent::addLog("\t-----+> [ Venta / Siigo / item / codigo_producto_siigo ]: " . $venta['codigo_producto_siigo']);
        parent::addLog("\t-----+> [ Venta / Siigo / item / codigo_producto_siigo_descripcion ]: " . $venta['codigo_producto_siigo_descripcion']);


        // Casi 1: Cuando es 100% nasbichips, sin bonos de descuento

        $venta['exposicion_pt']       = intval( $venta['exposicion_pt'] );
        $venta['tipo']                = intval( $venta['tipo'] );
        $venta['precio']              = floatval( $venta['precio'] );
        $venta['precio_segundo_pago'] = floatval( $venta['precio_segundo_pago'] );
        $venta['precio_comprador']    = floatval( $venta['precio_comprador'] );

        if( $venta['exposicion_pt'] > 1 ){
            $totalCompra = 0;
            if( $venta['tipo'] == 1){
                $totalCompra              = $venta['precio'] + $venta['precio_segundo_pago'];

            }else{
                $totalCompra              = $venta['precio_comprador'];
            }

            if( $comisionCategoriaSinIva == 0 || $comisionCategoriaConIva == 0 ){
                $comisionCategoriaSinIva      = $totalCompra * $venta['comisiones_categoria_pt'];
                $comisionCategoriaSinIva      = round($comisionCategoriaSinIva);

                $comisionCategoriaConIva      = $comisionCategoriaSinIva * 1.19;
                $comisionCategoriaConIva      = floatval("" . number_format($comisionCategoriaConIva, 2, '.', ''));
            }


            $requestSiigo = Array(
                "data" => Array(
                    "uid"          => $venta['uid_vendedor'],
                    "empresa"      => $venta['empresa'],
                    "observations" => $observacion,
                    "items" => Array(
                        Array(
                            "code"        => $venta['codigo_producto_siigo'],
                            "description" => $venta['codigo_producto_siigo_descripcion'],
                            "quantity"    => 1,
                            "price"       => $comisionCategoriaSinIva,
                            "discount"    => 0,
                            "taxes"       => 19
                        )
                    ),
                    "payments" => Array(
                        Array(
                            "value" => $comisionCategoriaConIva
                        )
                    )
                )
            );

            parent::addLog("\t-----+> [ Venta / Siigo / item / requestSiigo ]: " . json_encode($requestSiigo));

            // Recuerda que si 'codigo_producto_siigo' es vacio no se crea factura.
            parent::addLog("-----+> [ A: Carrito / siigo/?crear_factura_new ]: " . json_encode([ "SEND" => $requestSiigo]));

            $responseSiigo = parent::remoteRequest("http://nasbi.peers2win.com/api/controllers/siigo/?crear_factura_new", $requestSiigo);
            $responseSiigo = json_decode($responseSiigo, true);
           
            parent::addLog("-----+> [ B: Carrito / siigo/?crear_factura_new ]: " . json_encode([ "SEND" => $requestSiigo, "REVICED" => $responseSiigo]));
            return $responseSiigo;
        }else{
            return array(
                'status' => 'success',
                'message'=> 'No requiere factura por ser de tipo gratuita la publicacion',
                'data' => $venta
            );
        }
    }
    public function calificarComprador(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid'])  || !isset($data['empresa']) || !isset($data['expriencia_venta']) || !isset($data['comunicacion_cliente'])  || !isset($data['puntualidad_pago'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $descripcion = null;
        if(isset($data['descripcion'])) $descripcion = addslashes($data['descripcion']);
        
        $venta = $this->transaccionCarritoUnProducto($data, 12);
        $ventas2 = $venta['data2'];
        if($venta['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $venta = $venta['data'];


        if($data['uid'] != $venta[0]['uid_vendedor'] || $data['empresa'] != $venta[0]['empresa']) return array('status' => 'fail', 'message'=> 'este usuario no puede calificar esta venta', 'data' => null);


        $estado = 13;
        $fecha = intval(microtime(true)*1000);

        $instertarxcal = $this->insertarCalificacionComprador([
            'uid' => $venta[0]['uid_comprador'],
            'empresa' => $venta[0]['empresa_comprador'],
            'id_producto' => $venta[0]['id_producto'],
            'id_transaccion' => $venta[0]['id_carrito'],
            'tipo_transaccion' => $venta[0]['tipo'],
            'expriencia_venta' => $data['expriencia_venta'],
            'comunicacion_cliente' => $data['comunicacion_cliente'],
            'puntualidad_pago' => $data['puntualidad_pago'],
            'promedio' => 0,
            'descripcion' => $descripcion,
            'fecha' => $fecha,
            'empresa' => $venta[0]['empresa']
        ]);
        if($instertarxcal['status'] == 'fail') return $instertarxcal;

        parent::conectar();
        // $saldosPendientePago = array();
        $selectxdiferidosxbloqueados = "SELECT * FROM buyinbig.nasbicoin_bloqueado_diferido WHERE id_transaccion = '$data[id_carrito]' AND (tipo_transaccion = '1' OR tipo_transaccion = '2');";
        $selectdiferidosbloqueados = parent::consultaTodo($selectxdiferidosxbloqueados);
        parent::cerrar();

        $adicional =[
            'estado' => $estado,
            'fecha' => $fecha
        ];

        return $this->cerrarVenta($venta, $adicional, $selectdiferidosbloqueados, $ventas2, $venta[0]['uid_comprador'], $venta[0]['empresa_comprador']);
    }

    public function confirmarDevolucion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $venta = $this->transaccionId($data, 9);
        if($venta['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $venta = $venta['data'];
        
        $estado = 6;
        $fecha = intval(microtime(true)*1000);

        return $this->actualizarEstadoTransaccion([
            'id' => $venta['id'],
            "id_carrito" => $venta["id_carrito"],
            'estado' => $estado,
            'fecha' => $fecha,
            'mensaje_success' => 'confirmacion de devolucion',
            'mensaje_fail' => 'no confirmacion de devolucion'
        ]);
    }

    public function resumenMensualVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        $selectxchartxprodxmensual = 
            "SELECT
                COUNT(pt.id_producto) AS cantidades_vendidas,
                p.titulo,
                pt.fecha_actualizacion
            FROM productos_transaccion pt
            INNER JOIN productos p ON pt.id_producto = p.id
            WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
            GROUP BY pt.id_producto
            ORDER BY cantidades_vendidas DESC
            LIMIT 5;";

        // var_dump( $selectxchartxprodxmensual );

        $chartprodmensual = parent::consultaTodo($selectxchartxprodxmensual);
        parent::cerrar();

        if(count($chartprodmensual) <= 0) return array('status' => 'fail', 'message'=> 'no ventas', 'data' => null);

        $chartprodmensual = $this->mapResumenVentas($chartprodmensual);
        return array('status' => 'success', 'message'=> 'ventas', 'data' => $chartprodmensual);
    }

    public function ingresosMensualVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio']) || !isset($data['hace_meses'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        $selectxmesxactual = 
        "SELECT
            pt.*,
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                pt.precio_comprador
            ) AS 'TOTAL_VENTA',
            p.titulo,
            p.foto_portada,
            pte.descripcion AS descripcion_estado,
            ptt.descripcion AS descripcion_tipo
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
        ORDER BY pt.fecha_actualizacion ASC;";

        // var_dump( $selectxmesxactual );

        $mesactual = parent::consultaTodo($selectxmesxactual);

        if($data['hace_meses'] == 0) $data['hace_meses'] = 1;


        $fecha = (strtotime('first day of -'.$data['hace_meses'].' month', $month_end)*1000);
        $fechaunmesmas = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);

        
        $selectxmesxanterior = 
        "SELECT
            pt.*,
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                pt.precio_comprador
            ) AS 'TOTAL_VENTA',
            p.titulo,
            p.foto_portada,
            pte.descripcion AS descripcion_estado,
            ptt.descripcion AS descripcion_tipo
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
        ORDER BY pt.fecha_actualizacion ASC;";
        $mesanterior = parent::consultaTodo($selectxmesxanterior);

        $request = array(
            'mesactual' => $this->mapHistorial($mesactual),
            'mesanterior' => $this->mapHistorial($mesanterior)
        );
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'ventas', 'data' => $request);
    }

    public function ingresosMensualVentasPaginado(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $order = "ORDER BY fecha_actualizacion DESC";
        if(isset($data['order_precio'])) $order = "ORDER BY precio_usd $data[order_precio]";

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        $pagina = floatval($data['pagina']);
        $numpagina = 3;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);
        
        parent::conectar();
        $selectxmesxactual = "SELECT * FROM (
            SELECT 
                *,
                (@row_number:=@row_number+1) AS num FROM(
                    SELECT
                        pt.*,
                        IF( pt.tipo = 1,
                            (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                            pt.precio_comprador
                        ) AS 'TOTAL_VENTA',
                        p.titulo,
                        p.foto_portada,
                        pte.descripcion AS descripcion_estado,
                        ptt.descripcion AS descripcion_tipo,
                        (
                            SELECT
                                SUM(hp.visitas)
                            FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto
                        ) AS visitas,
                        (
                            SELECT
                                SUM(pt.cantidad)
                            FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10
                        ) AS cantidad_vendidas

                FROM productos_transaccion pt
                INNER JOIN productos p ON pt.id_producto = p.id
                LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                JOIN (SELECT @row_number := 0) r
                WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
                $order
                )as datos 
            $order
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $mesactual = parent::consultaTodo($selectxmesxactual);

        // var_dump( $selectxmesxactual );

        if(count($mesactual) <= 0) {
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron ventas', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = 
        "SELECT
            COUNT(pt.id) AS contar
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas';";

        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas      = $todoslosproductos/$numpagina;
        $totalpaginas      = ceil($totalpaginas);
        $mesactual         = $this->mapHistorial($mesactual, false);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'mis ventas', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($mesactual), 'total_productos' => $todoslosproductos, 'data' => $mesactual);
    }

    public function facturacionVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
                
        parent::conectar();
        $selectxfacturacion = 
            "SELECT * FROM (
                SELECT
                    (@x:=@x+1) AS rownumber,
                    pt.*,
                    IF( pt.tipo = 1,
                        (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                        pt.precio_comprador
                    ) AS 'TOTAL_VENTA',
                    p.titulo,
                    p.foto_portada,
                    pte.descripcion AS descripcion_estado,
                    ptt.descripcion AS descripcion_tipo
                FROM productos_transaccion pt
                LEFT JOIN productos p ON(pt.id_producto = p.id)
                LEFT JOIN productos_transaccion_estado pte ON(pt.estado = pte.id)
                LEFT JOIN productos_transaccion_tipo ptt ON(pt.tipo = ptt.id)
                WHERE pt.uid_vendedor = '5' AND pt.empresa = '1' AND pt.estado = 13
                ORDER BY pt.esta_liquidado ASC
            )AS info WHERE info.rownumber BETWEEN 20 AND 30;";
        $selectxfacturacion_old = 
            "SELECT
                pt.*,
                IF( pt.tipo = 1,
                    (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                    pt.precio_comprador
                ) AS 'TOTAL_VENTA',
                p.titulo,
                p.foto_portada,
                pte.descripcion AS descripcion_estado,
                ptt.descripcion AS descripcion_tipo
            FROM productos_transaccion pt
            LEFT JOIN productos p ON(pt.id_producto = p.id)
            LEFT JOIN productos_transaccion_estado pte ON(pt.estado = pte.id)
            LEFT JOIN productos_transaccion_tipo ptt ON(pt.tipo = ptt.id)
            WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 ORDER BY pt.esta_liquidado ASC;";

        // var_dump($selectxfacturacion);
        parent::queryRegistro("SELECT 0 INTO @x;");
        $facturacion = parent::consultaTodo( $selectxfacturacion );
        //print_r($facturacion);

        if(count($facturacion) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron facturacion','data' => null);
        }

        $facturacion = $this->mapHistorial($facturacion, false);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'facturacion', 'data' => $facturacion);
    }

    public function resumenVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        parent::conectar();

        $selectxpor_preparar = 
            "SELECT
                COUNT( DISTINCT(pt.id_carrito) ) AS por_preparar
            FROM productos_transaccion pt
            WHERE estado = 6 AND (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]');";

        $selectxdespachar    = 
            "SELECT
                COUNT( DISTINCT(pt.id_carrito) ) AS despachar
            FROM productos_transaccion pt
            WHERE estado = 6 AND (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]');";

        $selectxtransito     = 
            "SELECT
                COUNT( DISTINCT(pt.id_carrito) ) AS transito
            FROM productos_transaccion pt
            WHERE estado = 7 AND (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]');";

        $selectxfinalizadas  = 
            "SELECT
                COUNT( DISTINCT(pt.id_carrito) ) AS finalizadas
            FROM productos_transaccion pt
            WHERE estado = 13 AND (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]');";


        $selectpor_preparar = parent::consultaTodo($selectxpor_preparar);
        $selectdespachar    = parent::consultaTodo($selectxdespachar);
        $selecttransito     = parent::consultaTodo($selectxtransito);
        $selectfinalizadas  = parent::consultaTodo($selectxfinalizadas);
        parent::cerrar();

        $por_preparar = 0;
        if ( $selectpor_preparar[ 0 ]['por_preparar'] != null ) {
            $por_preparar = $selectpor_preparar[ 0 ]['por_preparar'];
        }
        $despachar = 0;
        if ( $selectdespachar[ 0 ]['despachar'] != null ) {
            $despachar = $selectdespachar[ 0 ]['despachar'];
        }
        $transito = 0;
        if ( $selecttransito[ 0 ]['transito'] != null ) {
            $transito = $selecttransito[ 0 ]['transito'];
        }
        $finalizadas = 0;
        if ( $selectfinalizadas[ 0 ]['finalizadas'] != null ) {
            $finalizadas = $selectfinalizadas[ 0 ]['finalizadas'];
        }
        $resumenVenta = array(
            'por_preparar' => $por_preparar,
            'despachar'    => $despachar,
            'transito'     => $transito,
            'finalizadas'  => $finalizadas
        );

        return array('status' => 'success', 'message'=> 'resuemn', 'data' => $resumenVenta);
    }

    function actualizarEstadoTransaccion(Array $data)
    {
        $adicional = "";
        if($data['estado'] == 4 || $data['estado'] == 10) $adicional = ", contador = '$data[contador]'";
        parent::conectar();
        $updatextransaccion = "UPDATE productos_transaccion
        SET
            estado              = '$data[estado]',
            fecha_actualizacion = '$data[fecha]'
            $adicional
        WHERE id_carrito = '$data[id_carrito]'";
        $updatetransaccion = parent::query($updatextransaccion);
        parent::cerrar();
        if(!$updatetransaccion) return array('status' => 'fail', 'message'=> $data['mensaje_fail'], 'data' => null);
        
        $this->insertarTimeline($data);
        $this->insertarTimeline2($data);
        $this->envio_correo_change_estado($data); // revisar - @YARIN tu funcion esta dando error cuando es ganado en subasta.
        return array('status' => 'success', 'message'=> $data['mensaje_success'], 'data' => null);
    }

    function actualizarTimelinesTransacciones(Array $data)
    {

        $timelinesTransaccionesList = [];
        // Creación 27 abril 2021 - probar
        if ( !isset($data) ) {
            return array(
                'status' => 'fail',
                'message'=> 'faltan datos',
                'data' => null
            );
        }
        foreach ($data as $key => $value) {
            
            $timeline1 = array(
                'id_carrito' => $value['id_carrito'],
                'tipo_timeline' => 1,
                'estado' => $value['estado'],
                'fecha' => $value['fecha_actualizacion'],
                'fecha' => $value['fecha_actualizacion'],
            );
            $timeline2 = array(
                'id_carrito' => $value['id_carrito'],
                'tipo_timeline' => 2,
                'estado' => $value['estado'],
                'fecha' => $value['fecha_actualizacion'],
                'fecha' => $value['fecha_actualizacion'],
            );

            $timeline1['result'] = $this->insertarTimeline($timeline1);
            $timeline2['result'] = $this->insertarTimeline2($timeline2);
            
            $timelinesTransaccionesList[] = $timeline1;
            $timelinesTransaccionesList[] = $timeline2;
        }
        return array('status' => 'success', 'message'=> 'Actualizar transacciones', 'data' => $timelinesTransaccionesList);

    }

    function insertarTimeline(Array $data)
    {

        $tipo_timeline = 1;
        if ( !isset($data['tipo_timeline']) ) {
            // Sino existe esta campo entonces es para insertar en la tabla vendedor
            $tipo_timeline = 1;
        }else{
            $tipo_timeline = isset($data['tipo_timeline']);
        }
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
            '$tipo_timeline',
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
            '2',
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
            p.fecha_creacion AS fecha_creacion_producto,


            p.titulo,
            p.categoria,
            p.exposicion,
            p.fecha_creacion AS fecha_creacion_producto,

            c.porcentaje_gratuita,
            c.porcentaje_clasica,
            c.porcentaje_premium,

            c.codigo_producto_siigo_clasica,
            c.codigo_producto_siigo_premium,

            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_clasica
            ) AS codigo_producto_siigo_clasica_descripcion,

            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_premium
            ) AS codigo_producto_siigo_premium_descripcion

        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        INNER JOIN categorias  c ON(c.CategoryID = p.categoria)
        WHERE pt.id = '$data[id]' AND pt.uid_vendedor = '$data[uid]' AND pt.estado = '$estado'";

        $ventas = parent::consultaTodo($selectxtransaccion);
        if(count($ventas) > 0){
            $ventas = $this->mapeoVentas($ventas, true);
            $ventas = $ventas[0];
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $ventas);
        }else{
            $request = array('status' => 'fail', 'message' => 'no data transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function transaccionCarrito(Array $data, Int $estado)
    {
        $request = null;
        parent::conectar();
        $selectxtransaccion = "SELECT pt.*, p.titulo, p.fecha_creacion AS fecha_creacion_producto
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        WHERE pt.id_carrito = '$data[id_carrito]' AND pt.uid_vendedor = '$data[uid]' AND pt.estado = '$estado'";
        $ventas = parent::consultaTodo($selectxtransaccion);
        if(count($ventas) > 0){
            $ventas = $this->mapeoVentas($ventas, true);
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $ventas);
        }else{
            $request = array('status' => 'fail', 'message' => 'no data transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function transaccionCarritoUnProducto(Array $data, Int $estado)
    {
        $request = null;
        parent::conectar();

        $ventaaa2 = 
        "SELECT 
            pt.*,

            p.titulo,
            p.categoria,
            p.exposicion,
            p.fecha_creacion AS fecha_creacion_producto,

            c.porcentaje_gratuita,
            c.porcentaje_clasica,
            c.porcentaje_premium,

            c.codigo_producto_siigo_clasica,
            c.codigo_producto_siigo_premium,

            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_clasica
            ) AS codigo_producto_siigo_clasica_descripcion,

            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_premium
            ) AS codigo_producto_siigo_premium_descripcion

        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        INNER JOIN categorias  c on c.CategoryID = p.categoria
        WHERE pt.id_carrito = '$data[id_carrito]' AND pt.uid_vendedor = '$data[uid]' 
        AND   pt.estado     = '$estado'
        ORDER BY boolcripto DESC, pt.moneda DESC";

        $selectxtransaccion = 
        "SELECT
            *
        FROM (
            SELECT 
                (@row_number:=@row_number+1) AS num,
                pt.*,
                p.titulo,
                p.categoria,
                p.exposicion,


                c.porcentaje_gratuita,
                c.porcentaje_clasica,
                c.porcentaje_premium,

                c.codigo_producto_siigo_clasica,
                c.codigo_producto_siigo_premium,

                (
                    SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_clasica
                ) AS codigo_producto_siigo_clasica_descripcion,

                (
                    SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = c.codigo_producto_siigo_premium
                ) AS codigo_producto_siigo_premium_descripcion


            FROM productos_transaccion pt
            INNER JOIN productos  p ON (pt.id_producto = p.id)
            INNER JOIN categorias c ON (c.CategoryID = p.categoria)
            WHERE pt.id_carrito = '$data[id_carrito]' AND pt.uid_vendedor = '$data[uid]' 
            AND   pt.estado     = '$estado'
            ORDER BY boolcripto DESC, pt.moneda DESC
        ) a GROUP BY a.id_producto";

        $ventas = parent::consultaTodo($selectxtransaccion);
        $ventas2 = parent::consultaTodo($ventaaa2);
        if(count($ventas) > 0){
            $ventas = $this->mapeoVentas($ventas, true);
            $ventas2 = $this->mapeoVentas($ventas2, true);
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $ventas, "data2"=>$ventas2);
        }else{
            $request = array('status' => 'fail', 'message' => 'no data transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function transaccionIdFull(Array $data, Int $estado)
    {
        $request = null;
        parent::conectar();
        
        $selectxtransaccion = "SELECT pt.*, p.titulo, p.foto_portada, p.producto, pte.descripcion AS descripcion_estado, ptt.descripcion AS descripcion_tipo, p.fecha_creacion AS fecha_creacion_producto
        FROM productos_transaccion pt
        LEFT JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.id = '$data[id]' AND pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = '$estado'
        ORDER BY fecha_creacion DESC;";
        $ventas = parent::consultaTodo($selectxtransaccion);
        if(count($ventas) > 0){
            $ventas = $this->mapeoVentas($ventas, true);
            $ventas = $ventas[0];
            $request = array('status' => 'success', 'message' => 'data transaccion', 'data' => $ventas);
        }else{
            $request = array('status' => 'fail', 'message' => 'faltan datos transaccion', 'data' => null);
        }
        parent::cerrar();
        return $request;
    }

    function mapeoVentas(Array $ventas, Bool $confidencial)
    {
        
        foreach ($ventas as $x => $venta) {
            
            $venta['envio'] = null;
            $venta['id'] = floatval($venta['id']);
            $venta['id_carrito'] = floatval($venta['id_carrito']);
            $venta['id_producto'] = floatval($venta['id_producto']);
            $venta['uid_vendedor'] = floatval($venta['uid_vendedor']);
            $venta['uid_comprador'] = floatval($venta['uid_comprador']);

            $venta['cantidad'] = floatval($venta['cantidad']);

            $venta['boolcripto'] = floatval($venta['boolcripto']);


            $venta['tipo']       = floatval($venta['tipo']);

            $venta['precio_og'] = floatval($venta['precio']);
            if( $venta['tipo'] == 2){
                $venta['precio_comprador'] = floatval($venta['precio_comprador']);

                $venta['precio_puja']      = floatval($venta['precio']);
                $venta['precio']           = floatval($venta['precio_comprador']);
            }else{
                $venta['precio'] = floatval($venta['precio']) + floatval($venta['precio_segundo_pago']) + floatval($venta['precio_cupon']); // Precio 

            }

            $venta['precio_cupon'] = floatval($venta['precio_cupon']);
            $venta['precio_cupon_mask'] = $this->maskNumber($venta['precio_cupon'], 2);


            $venta['precio_segundo_pago']      = floatval($venta['precio_segundo_pago']);
            $venta['precio_segundo_pago_mask'] = $this->maskNumber($venta['precio_segundo_pago'], 2);

            $venta['moneda_segundo_pago']      = $venta['moneda_segundo_pago'];


            if($venta['boolcripto'] == 0)$venta['precio_mask'] = $this->maskNumber($venta['precio'], 2);
            if($venta['boolcripto'] == 1)$venta['precio_mask'] = $this->maskNumber($venta['precio'], 2);
            $venta['precio_usd']               = floatval($venta['precio_usd']);
            $venta['precio_usd_mask']          = $this->maskNumber($venta['precio_usd'], 2);
            $venta['precio_moneda_actual_usd'] = floatval($venta['precio_moneda_actual_usd']);
            $venta['fecha_creacion']           = floatval($venta['fecha_creacion']);
            $venta['fecha_actualizacion']      = floatval($venta['fecha_actualizacion']);
            $venta['estado']                   = floatval($venta['estado']);
            $venta['contador']                 = floatval($venta['contador']);
            $venta['id_metodo_pago']           = floatval($venta['id_metodo_pago']);
            
            $venta['empresa']                  = floatval($venta['empresa']);
            $venta['empresa_comprador']        = floatval($venta['empresa_comprador']);

            if(isset($venta['num'])) $venta['num'] = floatval($venta['num']);

            $venta['detalle_pago']            = $this->detallePagoTransaccion($venta['id'], $venta['id_metodo_pago'], $confidencial);
            $venta['envio']                   = $this->detalleEnvioTransaccion($venta);
            $venta['datos_usuario_comprador'] = $this->datosUser(['uid'=> $venta['uid_comprador'], 'empresa' => $venta['empresa_comprador']])['data'];

            $venta['datos_usuario_vendedor']  = $this->datosUser(['uid'=> $venta['uid_vendedor'], 'empresa' => $venta['empresa']])['data'];
            $venta['timeline']                = $this->timelineTransaccion($venta, 1)['data'];
            
            $venta['contador_chat']           = $this->chatContador($venta, 1)['data'];


            $venta['datos_tcc'] = $this->obtenerInfoTCC( intval($venta['id']) );

            $venta['pago_digital_id'] = intval( $venta['pago_digital_id'] );
            if ( $venta['pago_digital_id'] != 0) {
                $venta['datos_pd'] = $this->obtenerInfoPagoDigital( $venta );
            }else{
                $venta['datos_pd'] = null;
            }

            if( !isset($venta['visitas']) ) {
                $venta['visitas'] = 0;
            }else{
                $venta['visitas'] = floatval($venta['visitas']);
            }
            if( !isset($venta['cantidad_vendidas']) ) {
                $venta['cantidad_vendidas'] = 0;
            }else{
                $venta['cantidad_vendidas'] = floatval($venta['cantidad_vendidas']);
            }
            if( isset($venta['fecha_creacion_producto']) ) {
                $venta['fecha_creacion_producto'] = $venta['fecha_creacion_producto'];
            }

            // inicio nuevo codigo 
            $resultPTEP = 
            parent::consultaTodo("SELECT * FROM productos_transaccion_especificacion_producto WHERE id_transaccion = '$venta[id_carrito]' AND cantidad > 0 AND id_producto = '$venta[id_producto]'");
            $venta['variaciones'] = [];
            if( $resultPTEP && count($resultPTEP) > 0 ){
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
                    

                    if($confi_color_talla && count($confi_color_talla) > 0){
                        array_push($venta['variaciones'], array(
                            'color'    => $confi_color_talla[0]['hexadecimal'],
                            'tallaES'  => $confi_color_talla[0]['talla_nombre_es'],
                            'tallaEN'  => $confi_color_talla[0]['talla_nombre_en'],
                            'cantidad' => intval($itemPTEP['cantidad'])
                        ));
                    }
                }
            }
            // fin nuevo codigo 
            
            $ventas[$x] = $venta;
        }

        return $ventas;
    }
    function obtenerInfoTCC( Int $id ) {
        $result_TCC = parent::consultaTodo("SELECT * FROM buyinbig.productos_transaccion_guia_tcc WHERE id_productos_transaccion = $id;");
        if ( count($result_TCC) == 0 ) {
            return null;
        }else{
            return $result_TCC[0];
        }
    }
    function obtenerInfoPagoDigital( Array $venta ) { 

        $pago_digital = parent::consultaTodo("SELECT ID, STATUS, TOTAL_COMPRA, TIPO, UID, EMPRESA, CODIGO_BANCO, NOMBRE_BANCO, ISO_CODE_2, METODO_PAGO_USADO_ID, TRANSACCION_FINALIZADA, FECHA_CREACION, FECHA_ACTUALIZACION FROM buyinbig.pago_digital WHERE id = $venta[pago_digital_id]");
        if ( count($pago_digital) == 0 ) {
            return null;
        }
        $pago_digital             = $pago_digital[0];
        $pago_digital_referencias = parent::consultaTodo("SELECT ID, STATUS, CODIGO_RESPUESTA, REFERENCIA, CUS, ESTADO, RESPUESTA, BANK_URL, PAGO_DIGITAL_ID, JSON_RESPUESTA FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = $venta[pago_digital_id]");

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
            LEFT JOIN nasbicoin_bloqueado_diferido ncb ON ptdp.id_transaccion = ncb.id_transaccion AND ncb.tipo = 1 AND (ncb.tipo_transaccion = 1 OR ncb.tipo_transaccion = 2)
            LEFT JOIN nasbicoin_bloqueado_diferido ncd ON ptdp.id_transaccion = ncd.id_transaccion AND ncd.tipo = 0 AND (ncd.tipo_transaccion = 1 OR ncd.tipo_transaccion = 2)
            WHERE ptdp.id_transaccion = '$id';";
        }
        if($confidencial == false || ($confidencial == true && $id_metodo_pago != 1)){
            $selecxdetallexpago = "SELECT ptdp.*, 
            (SELECT ptdpd.descripcion FROM productos_transaccion_detalle_pago_declinado ptdpd WHERE ptdpd.id_transaccion = '$id' ORDER BY ptdpd.id DESC LIMIT 1) AS descripcion_declinado 
            FROM productos_transaccion_detalle_pago ptdp
            WHERE ptdp.id_transaccion = '$id';";
        }
        //echo $selecxdetallexpago."\n";
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
            if(isset($envio['tipo_envio'])) $envio['tipo_envio'] = intval($envio['tipo_envio']);
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

    function actualizarDetallePago(Array $data)
    {
        parent::conectar();
        $updatextransaccion = "UPDATE productos_transaccion_detalle_pago
        SET
            confirmado = '$data[confirmado]',
            addres_vendedor = '$data[address_vendedor]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]';";
        $update = parent::query($updatextransaccion);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'error actualizar detalle transaccion', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'detalle transaccion actualizado', 'data' => null);
    }

    function insertarTransaccionRechazada(Array $data)
    {
        parent::conectar();
        $insterxtransaccionxrechazada = "INSERT INTO productos_transaccion_rechazada
        (
            uid,
            id_transaccion,
            tipo,
            descripcion,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$data[id_transaccion]',
            '$data[tipo]',
            '$data[descripcion]',
            '$data[fecha]',
            '$data[fecha]'
            
        );";
        $insert = parent::queryRegistro($insterxtransaccionxrechazada);
        parent::cerrar();
        if(!$insert) return array('status' => 'fail', 'message'=> 'error actualizar detalle transaccion', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'detalle transaccion actualizado', 'data' => null);
    }

    function insertarDetallePagoDeclinado(Array $data)
    {
        parent::conectar();
        $inxsterxdetallexpagoxdeclinado = "INSERT INTO productos_transaccion_detalle_pago_declinado
        (
            id_transaccion,
            id_detalle_pago,
            conteo,
            url,
            descripcion,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_transaccion]',
            '$data[id_detalle_pago]',
            '$data[conteo]',
            '$data[url]',
            '$data[descripcion]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insert = parent::queryRegistro($inxsterxdetallexpagoxdeclinado);
        parent::cerrar();
        if(!$insert) return array('status' => 'fail', 'message'=> 'error actualizar detalle transaccion', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'detalle transaccion actualizado', 'data' => null);
    }

    function actualizarEnvioTransaccion(Array $data)
    {
        parent::conectar();
        $updatextransaccionxenvio = "UPDATE productos_transaccion_envio
        SET
            numero_guia = '$data[numero_guia]',
            empresa = '$data[empresa]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id_carrito = '$data[id_carrito]'";
        $updateenvio = parent::query($updatextransaccionxenvio);
        parent::cerrar();

        if(!$updateenvio) return array('status' => 'fail', 'message'=> 'numero de guia no enviado', 'data' => null);

        return array('status' => 'success', 'message'=> 'numero de guia enviado', 'data' => null);
    }

    function saveShippo(Array $data)
    {
        $envio = (array) json_decode(Shippo_Shipment::retrieve($data['id_envio']), true);
        $ruta = $this->filter_by_value($envio['rates'], 'object_id', $data['id_ruta'])[0];
        if($ruta == null) return array('status' => 'fail', 'message'=> 'la ruta no está disponible', 'data' => null);
        $empresa = $ruta['provider'];

        $transaction = (array) json_decode(Shippo_Transaction::create( 
            array( 
                'rate' => $data['id_ruta'], 
                'label_file_type' => 'PDF', 
                'async' => false
            ) 
        ));
        // echo (json_encode($transaction));
        
        // Retrieve label url and tracking number or error message
        if ($transaction['status'] != 'SUCCESS') return array('status' => 'fail', 'message'=> 'envio no actualizado', 'data' => $transaction['messages']);
        
        parent::conectar();
        $updatedireccion = "UPDATE productos_transaccion_envio
        SET
            numero_guia = '$transaction[tracking_number]',
            url_numero_guia = '$transaction[tracking_url_provider]',
            empresa = '$empresa',
            etiqueta_envio = '$transaction[label_url]',
            factura_comercial = '$transaction[commercial_invoice_url]',
            id_envio_shippo = '$data[id_envio]',
            id_ruta_shippo = '$data[id_ruta]',
            id_transaccion_shippo = '$transaction[object_id]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]'";
        $actualizar = parent::query($updatedireccion);
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
    }

    function insertarCalificacionComprador(Array $data)
    {
        $catidadcalificaciones = 3;
        $data['promedio'] = floatval(($data['expriencia_venta'] + $data['comunicacion_cliente'] + $data['puntualidad_pago']) / $catidadcalificaciones);
        $data['promedio'] = $this->truncNumber($data['promedio'], 2);
        parent::conectar();
        $insertarxcalificacionxvendedor = "INSERT INTO calificacion_comprador
        (
            uid,
            empresa,
            id_producto,
            id_transaccion,
            tipo_transaccion,
            experiencia_venta,
            comunicacion_cliente,
            puntualidad_pago,
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
            '$data[expriencia_venta]',
            '$data[comunicacion_cliente]',
            '$data[puntualidad_pago]',
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

    function cerrarVenta(Array $ventas, Array $adicional, Array $selectdiferidosbloqueados, Array $allVentas, $uid_comprador, $empresa_comprador)
    {

        if ( count($selectdiferidosbloqueados) > 0 ) {
            $nasbifunciones = new Nasbifunciones();
            $data_wallet_comprador = $nasbifunciones->walletNasbiUsuario([
                "uid"               => $uid_comprador,
                "empresa"           => $empresa_comprador,
                "ver_transacciones" => 1,
                "iso_code_2"        => "CO"
            ]);
            foreach ($selectdiferidosbloqueados as $key => $diffbloqueo) {
                $diferidocomprador = $nasbifunciones->insertarBloqueadoDiferido([
                    'id' => $diffbloqueo['id'],
                    'uid' => $diffbloqueo['uid'],
                    'empresa' => $diffbloqueo['empresa'],
                    'moneda' => $diffbloqueo['moneda'],
                    'all' => false,
                    'precio' => $diffbloqueo['precio'],
                    'precio_momento_usd'=> $diffbloqueo['precio_momento_usd'],
                    'address' => $diffbloqueo['address'],
                    'id_transaccion' => $diffbloqueo['id_transaccion'],
                    'tipo_transaccion' => $diffbloqueo['tipo_transaccion'],
                    'tipo' => 'bloqueado',
                    'accion' => 'reverse',
                    'descripcion' => null,
                    'fecha' => $adicional['fecha']
                ]);
                if($diferidocomprador['status'] != 'success'){
                    parent::addLog("\t(1). CONTROLLER VENTAS / cerrarVenta / Desbloquear / ERROR ::: problemas al librar los saldos en " . json_encode($diferidocomprador) . "\n\n");

                    unset($nasbifunciones);
                    return $diferidocomprador;
                }else{
                    parent::addLog("\t(2). CONTROLLER VENTAS / cerrarVenta / Desbloquear / SUCCESS ::: Libre diferido de: " . json_encode($diferidocomprador) . " \n\n");
                }
                $address_send_comprador = "";
                if ( $diffbloqueo['moneda'] == "Nasbigold") {
                    $address_send_comprador = $data_wallet_comprador['nasbicoin_gold']['address'];
                }else{
                    $address_send_comprador = $data_wallet_comprador['nasbicoin_blue']['address'];
                }
                $dataEnviarDinero = array(
                    'id' => $diffbloqueo['id'],
                    'moneda' => $diffbloqueo['moneda'],
                    'uid_envia' => $uid_comprador,
                    'empresa_envia' => $empresa_comprador,
                    'addres_envia' => $address_send_comprador,
                    
                    'uid_recibe' => $diffbloqueo['uid'],
                    'empresa_recibe' => $diffbloqueo['empresa'],
                    'addres_recibe' => $diffbloqueo['address'],
                    'monto' => $diffbloqueo['precio'],

                    'monto_en_cripto' => $diffbloqueo['precio_en_cripto'],
                    'cerrarVenta'     => true,

                    'tipo' => 2,
                    'id_transaccion' => $diffbloqueo['id_transaccion'],
                    'fecha' => $adicional['fecha']
                );
                parent::addLog("\t\t(3). CONTROLLER VENTAS / cerrarVenta / PRE-Send / SUCCESS ::: ". json_encode($dataEnviarDinero ) . "\n\n");
                parent::addLog("\t\t(4). CONTROLLER VENTAS / cerrarVenta / VALIDATION::: ". $diffbloqueo['uid'] . " != " . $uid_comprador . " && " . $diffbloqueo['empresa'] . " != " . $empresa_comprador  ."\n\n");
                
                if ($diffbloqueo['uid'] == $uid_comprador && $diffbloqueo['empresa'] == $empresa_comprador) {
                    // No debo enviarme de mi propio dinero a mi wallet.
                    parent::addLog("\t\t(5). CONTROLLER VENTAS / cerrarVenta / POST-Send ::: ". json_encode($dataEnviarDinero ) . "\n\n");
                }else{
                    // Sino soy el comprador realizar el SEND
                    $send = $nasbifunciones->enviarDinero($dataEnviarDinero);

                    if($send['status'] == 'success') {
                        parent::addLog("\t\t(6.1). CONTROLLER VENTAS / cerrarVenta / Send / SUCCESS ::: ". json_encode($dataEnviarDinero) . "\n\n");
                        parent::addLog("\t\t(6.2). CONTROLLER VENTAS / cerrarVenta / Send / SUCCESS ::: ". json_encode($send) . "\n\n");
                    }else{
                        parent::addLog("\t\t(7.1). CONTROLLER VENTAS / cerrarVenta / Send / ERROR ::: ". json_encode($dataEnviarDinero) . "\n\n");
                        parent::addLog("\t\t(7.2). CONTROLLER VENTAS / cerrarVenta / Send / ERROR ::: ". json_encode($send) . "\n\n");
                    }
                }

                parent::addLog("------------------------------------------------------------\n\n\n\n\n");
            }
        }

        $montoTotalPagar = 0;
        $montoAgregado = array();

        parent::addLog("---+> STOCK EDIT CERRAR VENTA / allVentas: " . json_encode($allVentas));


        foreach ($allVentas as $key => $venta) {
            $this->insertarPuntos($venta);


            // Paso 1: Notificar completación de venta
            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_vendedor'],
                'empresa' => $venta['empresa'],
                'text' => 'GENIAL! has terminado tu proceso de venta del producto '.$venta['titulo'].' nos encanta que seas parte de nuestra familia Nasbi',
                'es' => 'GENIAL! has terminado tu proceso de venta del producto '.$venta['titulo'].' nos encanta que seas parte de nuestra familia Nasbi',
                'en' => 'GREAT! you have finished your process of selling the product '.$venta['titulo'].' we love that you are part of our Nasbi family',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_ventas'
            ]);

            $notificacion->insertarNotificacion([
                'uid' => $venta['uid_comprador'],
                'empresa' => $venta['empresa_comprador'],
                'text' => '¡GENIAL! has terminado tu proceso de compra del producto '.$venta['titulo'].' nos encanta que seas parte de nuestra familia Nasbi',
                'es' => '¡GENIAL! has terminado tu proceso de compra del producto '.$venta['titulo'].' nos encanta que seas parte de nuestra familia Nasbi',
                'en' => 'GREAT! you have finished your process of buying the product  '.$venta['titulo'].' we love that you are part of our Nasbi family',
                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);


            // Paso 2: Validar si completo el TOPE DE 3 VENTAS con EXPOSICIÓN GRATUITA.
            parent::addLog("\t-----+> [ Venta / Siigo / item / venta ]: " . json_encode($venta));
            $nasbifuncionesTopeVentas = new Nasbifunciones();
            $resultTopeVentas = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
                'uid'     => $venta['uid_vendedor'],
                'empresa' => $venta['empresa']
            ]);
            unset($nasbifuncionesTopeVentas);
        }
        $actualizarEstado = $this->actualizarEstadoTransaccion([
            'id'              => $ventas[0]['id'],
            'estado'          => $adicional['estado'],
            'fecha'           => $adicional['fecha'],
            "id_carrito"      => $ventas[0]["id_carrito"],
            'mensaje_success' => 'comprador calificado',
            'mensaje_fail'    => 'comprador no calificado'
        ]);
        if($actualizarEstado['status'] == 'fail') return $actualizarEstado;

        return array('status' => 'success', 'message'=> 'comprador calificado', 'data' => null);
    }

    function timelineTransaccion(Array $data, Int $tipo)
    {
        $selectxtimeline = "SELECT ptt.* FROM productos_transaccion_timeline ptt WHERE ptt.id_transaccion = '$data[id_carrito]' AND ptt.tipo = '$tipo' GROUP BY estado";
        $timeline = parent::consultaTodo($selectxtimeline);

        if(count($timeline) <= 0) return array('status' => 'fail', 'message'=> 'no timeline', 'data' => null);

        $timeline = $this->mapTimeLine($timeline);
        return array('status' => 'success', 'message'=> 'timeline', 'data' => $timeline);
    }

    function insertarPuntos(Array $data)
    {
        
    }

    function chatContador(Array $data, Int $tipo)
    {
        $selectxchat = "SELECT COUNT(c.id) AS contador FROM chat c WHERE c.id_transaccion = '$data[id]' AND c.tipo = '$tipo' AND (c.uid <> '$data[uid_vendedor]' OR c.empresa <> '$data[empresa]') AND c.visualizado = '0'";
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
                $datafoto     = $user['avatar'];
                
            }else if($empresa == 1){
                $datanombre   = $user['razon_social'];//$user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa  = $user['razon_social'];
                $datacorreo   = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto     = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                
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

    function mapResumenVentas(Array $resumen_ventas)
    {
        foreach ($resumen_ventas as $x => $resumen) {

            $resumen['cantidades_vendidas'] = floatval($resumen['cantidades_vendidas']);
            $resumen['titulo'] = stripslashes($resumen['titulo']);
            $resumen['fecha_actualizacion'] = floatval($resumen['fecha_actualizacion']);
            $resumen_ventas[$x] = $resumen;
        }
        return $resumen_ventas;
    }

    function mapHistorial(Array $historial)
    {
        
        foreach ($historial as $x => $hist) {
            
            $hist['envio'] = null;
            $hist['id'] = floatval($hist['id']);
            $hist['id_carrito'] = floatval($hist['id_carrito']);
            $hist['id_producto'] = floatval($hist['id_producto']);
            $hist['uid_vendedor'] = floatval($hist['uid_vendedor']);
            $hist['uid_comprador'] = floatval($hist['uid_comprador']);
            $hist['cantidad'] = floatval($hist['cantidad']);
            $hist['boolcripto'] = floatval($hist['boolcripto']);

            

            if( isset( $hist['TOTAL_VENTA'] )){
                $hist['TOTAL_VENTA'] = floatval($hist['TOTAL_VENTA']);
                $hist['precio']      = floatval($hist['TOTAL_VENTA']);
                $hist['precio_usd']  = floatval($hist['precio']);
            }

            if($hist['boolcripto'] == 0)$hist['precio_mask'] = $this->maskNumber($hist['precio'], 2);
            if($hist['boolcripto'] == 1)$hist['precio_mask'] = $this->maskNumber($hist['precio'], 2);
            $hist['precio_usd_mask'] = $this->maskNumber($hist['precio_usd'], 2);
            $hist['precio_moneda_actual_usd'] = floatval($hist['precio_moneda_actual_usd']);
            $hist['fecha_creacion'] = floatval($hist['fecha_creacion']);
            $hist['fecha_actualizacion'] = floatval($hist['fecha_actualizacion']);
            $hist['estado'] = floatval($hist['estado']);
            $hist['contador'] = floatval($hist['contador']);
            $hist['id_metodo_pago'] = floatval($hist['id_metodo_pago']);
            $hist['tipo'] = floatval($hist['tipo']);
            $hist['empresa'] = floatval($hist['empresa']);
            $hist['empresa_comprador'] = floatval($hist['empresa_comprador']);
            if(isset($hist['num'])) $hist['num'] = floatval($hist['num']);

            if( isset($hist['visitas']) ){
                $hist['visitas'] = floatval($hist['visitas']);
            }else{
                $hist['visitas'] = 0;
            }

            $hist['datos_usuario_comprador'] = $this->datosUser(['uid'=> $hist['uid_comprador'], 'empresa' => $hist['empresa_comprador']])['data'];

            $fecha = intval(microtime(true)*1000);
            $array = [
                'uid' => $hist['uid_vendedor'],
                'empresa' => $hist['empresa'],
                'id_producto' => $hist['id_producto'],
                'filename' => $fecha.'.csv'
            ];
            $hist['url_csv'] = bin2hex(json_encode($array));

            $historial[$x] = $hist;
        }

        return $historial;
    }
    
    function filter_by_value(Array $array, String $index, $value){
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
    function deboPagarComisionEnGratuita( Array $data ){
        parent::conectar();
        $selectxresumenxventas =
        "SELECT COUNT( DISTINCT(id_carrito)) AS ventas FROM productos_transaccion pt INNER JOIN  buyinbig.productos p ON( id_producto = p.id) WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado >= 13 AND p.exposicion = 1;";

        $selectresumenventas = parent::consultaTodo($selectxresumenxventas);
        parent::cerrar();

        $result = false;

        if(count($selectresumenventas) <= 0) {
            // No tiene que pagar comision
            $result = false;
        }else{
            // esta categoria (GRATUITA)
            $result = intval( $selectresumenventas[0]['ventas'] ) >= 3;
        }

        parent::conectar();
            $selectxcomisionxpagar = "SELECT c.CategoryID, c.porcentaje_gratuita, c.porcentaje_clasica, c.porcentaje_premium, p.exposicion FROM buyinbig.productos p INNER JOIN categorias c ON ( p.categoria = c.CategoryID) WHERE p.id = $data[id_producto];";
        $selectcomisionpagar = parent::consultaTodo($selectxcomisionxpagar);
        parent::cerrar();
        if ( $result ) {
            return array(
                'result' => $result,
                'data'   => $selectcomisionpagar[ 0 ]
            );
        }else{
            return array(
                'result' => $result,
                'data'   => $selectcomisionpagar[ 0 ]
            );
        }
    }

    public function notificarCobroPremium( Array $data) {
        parent::conectar();
        $insertxnotificacionxtopexventasxgratuitas = "INSERT INTO vendedores_tope_ventas_gratuitas(uid, empresa ) VALUES ( '$data[uid_vendedor]', '$data[empresa]' );";
        parent::queryRegistro($insertxnotificacionxtopexventasxgratuitas);
        parent::cerrar();

    }

    function envio_correo_change_estado(Array $data){
        switch ($data["mensaje_success"]) {
            case "venta confirmada":
                $this->envio_correo_confirmar_compra($data);
                break;
            case "numero de guia enviado":
                $this->envio_producto_correo($data); 
                break; 
            
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

    function get_direccion_by_id(Array $data){
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where id = '$data[id]';");
        parent::cerrar();
        return $direccion;
    }

    function get_data_envio_product_correo(Array $data) {
        parent::conectar();
        $data_envio_carrito = parent::consultaTodo("SELECT * FROM productos_transaccion_envio where id_carrito = '$data[id_carrito]';");
        parent::cerrar();
        return $data_envio_carrito;
        
    }


    public function envio_correo_confirmar_compra(Array $data)
    {
        $productos_transaccion = $this->get_producto_car_por_id([
            'id_carrito'   => $data["id_carrito"]
        ]);
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

            $this->htmlEmail_confirmo_compra_to_despacho($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto); 
        }
    }

    function envio_producto_correo(Array $data){
        $productos_transaccion=$this->get_producto_car_por_id([
            'id_carrito'   => $data["id_carrito"],
            ]);
        $data_envio = $this->get_data_envio_product_correo([
            'id_carrito'   => $data["id_carrito"],   
                ]);
       if($productos_transaccion[0]["tipo"]=="1"){
            foreach ($productos_transaccion as $x => $producto) {
                $data_producto = $this->get_product_por_id([
                    'uid'   => $producto["uid_vendedor"],
                    'id' => $producto["id_producto"],
                    'empresa'  => $producto["empresa"]
                ]); 
                
                $data_vendedor = $this->datosUserGeneral([
                    'uid' => $producto["uid_vendedor"],
                    'empresa' => $producto['empresa']
                ]);
                
                $data_comprador = $this->datosUserGeneral([
                    'uid' => $producto["uid_comprador"],
                    'empresa' => $producto['empresa_comprador']
                ]);

                $direccion_comprado = $this->get_direccion_by_id([
                    'id' => $data_envio[0]["id_direccion_comprador"]
                ]);

           
               
              $this->htmlEmail_corroeo_envio_guia($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto, $data_envio[0], $direccion_comprado[0] ); 
                $this->htmlEmail_corroeo_envio_guia_para_comprador($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto, $data_envio[0], $direccion_comprado[0]); 
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
                $this->htmlEmail_envio_vendedor_entrega_envio_subasta($data_comprador["data"],$data_vendedor["data"],$data_producto[0], $producto); 
                
            }

        }

    }



    function get_product_por_id(Array $data){
        $select = 
            "SELECT
                p.id,
                p.uid,
                p.empresa,
                p.tipo,
                p.tipoSubasta,
                p.producto,
                p.marca,
                p.modelo,
                p.titulo,
                p.descripcion,
                p.categoria,
                p.subcategoria,
                p.condicion_producto,
                p.garantia,
                p.estado,
                p.cantidad,
                p.moneda_local,
                p.oferta,
                p.porcentaje_oferta,
                p.porcentaje_tax,
                p.exposicion,
                p.cantidad_exposicion,
                p.envio,
                p.id_direccion,
                p.pais,
                p.departamento,
                p.ciudad,
                p.latitud,
                p.longitud,
                p.codigo_postal,
                p.direccion,
                p.keywords,
                p.foto_portada,
                p.url_video,
                p.portada_video,
                p.cantidad_vendidas,
                p.fecha_creacion,
                p.fecha_actualizacion,
                p.ultima_venta,
                p.genero,
                p.tiene_colores_tallas,
                p.tipo_envio_gratuito,
                p.id_tiempo_garantia,
                p.num_garantia,

                IF( p.oferta = 1,
                    p.precio - (p.precio * p.porcentaje_oferta),
                    p.precio
                ) AS 'precio',

                IF( p.oferta = 1,
                    p.precio_usd - (p.precio_usd * p.porcentaje_oferta),
                    p.precio_usd
                ) AS 'precio_usd',

                IF( p.oferta = 1,
                    p.precio_publicacion - (p.precio_publicacion * p.porcentaje_oferta),
                    p.precio_publicacion
                ) AS 'precio_publicacion',

                IF( p.oferta = 1,
                    p.precio_usd_publicacion - (p.precio_usd_publicacion * p.porcentaje_oferta),
                    p.precio_usd_publicacion
                ) AS 'precio_usd_publicacion'

            FROM buyinbig.productos p
            WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.id = '$data[id]'
            ORDER BY p.id DESC;";
                
        parent::conectar();
        $misProductos = parent::consultaTodo($select);
        parent::cerrar();
        if($misProductos){
            return $misProductos;
        }else{
            return [];
        }
    }

    function datosUserGeneral( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }

    public function htmlEmail_confirmo_compra_to_despacho(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto)
    {

       $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
       $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo5.html");

       if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
           $producto["moneda"]="Nasbichips"; 
       }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
           $producto["moneda"]="Bono(s) de descuento"; 
       }



       $html = str_replace("{{nombre_comprador}}",$data_comprador['nombre'], $html);
       $html = str_replace("{{nombre_usuario}}",$data_vendedor['nombre'], $html);
       $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
       $html = str_replace("{{trans123}}",$json->trans123, $html);
       $html = str_replace("{{trans124}}",$json->trans124, $html);

       $html = str_replace("{{precio_producto}}", $this->maskNumber($producto["precio"]), $html);
       $html = str_replace("{{moneda}}",$producto["moneda"], $html);


       $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
       $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);

       $html = str_replace("{{trans125}}",$json->trans125, $html);
       $html = str_replace("{{trans126}}",$json->trans126, $html);
       $html = str_replace("{{trans127}}",$json->trans127, $html);
       $html = str_replace("{{trans110}}",$json->trans110, $html);

       $html = str_replace("{{trans128}}",$json->trans128, $html);

       $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
       $html = str_replace("{{trans128}}",$json->trans128, $html);
       $html = str_replace("{{trans43}}",$json->trans43, $html);



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
       $titulo    = $json->trans117_;
       $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
       $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
       $cabeceras .= 'From: info@nasbi.com' . "\r\n";

       $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
       return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }

    function htmlEmail_corroeo_envio_guia(Array $data_comprador,Array $data_vendedor,Array $data_producto,Array $producto,Array $data_envio, Array $direccion_comprador){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo6.html");
        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }

        $array_tipos_envio=[];

        for ($i=0; $i < 3; $i++) { 
            $array_tipos_envio[$i]["id"]=$i+1;
            $mname ="trans118_".strval($i+1);
            $array_tipos_envio[$i]["nombre"]= $json->$mname;

        }


        $tipo_envio = $this->filter_by_value($array_tipos_envio,"id", $data_envio["tipo_envio"])[0];

        $html = str_replace("{{nombre_recibe}}",$data_comprador['nombre'], $html);
        $html = str_replace("{{nombre_usuario}}",$data_vendedor['nombre'], $html);
        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);




        $html = str_replace("{{trans107}}",$json->trans107, $html);
        $html = str_replace("{{trans112}}",$json->trans112, $html);
        $html = str_replace("{{trans113}}",$json->trans113, $html);


        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{tipo_envio}}",$tipo_envio['nombre'], $html);
        $html = str_replace("{{trans114}}",$json->trans114, $html);
        $html = str_replace("{{trans115}}",$json->trans115, $html);
        $html = str_replace("{{numero_guia}}",$data_envio["numero_guia"], $html);
        $html = str_replace("{{trans116}}",$json->trans116, $html);
        $html = str_replace("{{trans117}}",$json->trans117, $html);
        $html = str_replace("{{direccion}}",$direccion_comprador["direccion"], $html);



        $html = str_replace("{{trans260}}",$json->trans260, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{trans128}}",$json->trans128, $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans17}}",$json->trans117, $html);
        $html = str_replace("{{direccion}}",$json->trans117, $html);
        $html = str_replace("{{trans118}}",$json->trans118, $html);

        $html = str_replace("{{ciudad}}",$direccion_comprador["ciudad"], $html);
        $html = str_replace("{{trans119}}",$json->trans119, $html);

        $html = str_replace("{{telefono_contacto}}",$data_comprador["telefono"], $html);
        $html = str_replace("{{trans120}}","", $html);

        $html = str_replace("{{fecha_est_llegada}}","", $html);

        $html = str_replace("{{trans121}}",$json->trans121, $html);
        $html = str_replace("{{trans122}}",$json->trans122, $html);
        $html = str_replace("{{trans111}}",$json->trans111, $html);

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
        $titulo    = $json->trans119_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function htmlEmail_corroeo_envio_guia_para_comprador(Array $data_comprador,Array $data_vendedor,Array $data_producto,Array $producto,Array $data_envio, Array $direccion_comprador){

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_comprador["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo4.html");
        if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
            $producto["moneda"]="Nasbichips"; 
        }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
            $producto["moneda"]="Bono(s) de descuento"; 
        }

        $array_tipos_envio=[];

        for ($i=0; $i < 3; $i++) { 
         $array_tipos_envio[$i]["id"]=$i+1;
         $mname ="trans118_".strval($i+1);
           $array_tipos_envio[$i]["nombre"]= $json->$mname; // Esta funcion esta dando error.
           
       }
       

       $tipo_envio = $this->filter_by_value($array_tipos_envio,"id", $data_envio["tipo_envio"])[0];
       
       $html = str_replace("{{nombre_recibe}}",$data_comprador['nombre'], $html);
       $html = str_replace("{{nombre_usuario}}",$data_vendedor['nombre'], $html);
       $html = str_replace("{{trans151_brand}}",$json->trans151_brand, $html);
       
       $html = str_replace("{{trans111}}",$json->trans111, $html);
       $html = str_replace("{{trans260}}",$json->trans260, $html);
       $html = str_replace("{{trans152}}",$json->trans152, $html);
       $html = str_replace("{{trans153}}",$json->trans153, $html);
       $html = str_replace("{{trans154}}",$json->trans154, $html);
       
       $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
       $html = str_replace("{{signo_admiracion_open}}",$json->signo_admiracion_open, $html);       


       $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
       $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
       $html = str_replace("{{tipo_envio}}",$tipo_envio['nombre'], $html);
       $html = str_replace("{{trans114}}",$json->trans114, $html);
       $html = str_replace("{{trans115}}",$json->trans115, $html);
       $html = str_replace("{{numero_guia}}",$data_envio["numero_guia"], $html);
       
       $html = str_replace("{{empresa_transportadora}}", $data_envio["empresa"], $html);
       $html = str_replace("{{trans116}}",$json->trans116, $html);
       $html = str_replace("{{trans117}}",$json->trans117, $html);
       $html = str_replace("{{direccion}}",$direccion_comprador["direccion"], $html);

       $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
       $html = str_replace("{{trans128}}",$json->trans128, $html);
       $html = str_replace("{{trans43}}",$json->trans43, $html);
       $html = str_replace("{{trans17}}",$json->trans117, $html);
       $html = str_replace("{{direccion}}",$json->trans117, $html);
       $html = str_replace("{{trans118}}",$json->trans118, $html);
       
       $html = str_replace("{{ciudad}}",$direccion_comprador["ciudad"], $html);       
       
       $html = str_replace("{{trans119}}",$json->trans119, $html);

       $html = str_replace("{{telefono_contacto}}",$data_comprador["telefono"], $html);
       $html = str_replace("{{trans120}}","", $html);
       
       $html = str_replace("{{fecha_est_llegada}}","", $html);

       $html = str_replace("{{trans99}}",$json->trans99, $html);
       
       
       $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
       $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
       $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
       $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
       $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
       $html = str_replace("{{trans06_}}",$json->trans06_, $html);
       $html = str_replace("{{trans07_}}",$json->trans07_, $html);
       $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_comprador["uid"]."&act=0&em=".$data_comprador["empresa"], $html); 
       
       //$para      = 'lfospinoayala@gmail.com, luisospinoa@gmail.com';
       $para      = $data_comprador['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
       $mensaje1   = $html;
       $titulo    = $json->trans120_;
       $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
       $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
       $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
       $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
       return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function htmlEmail_envio_vendedor_entrega_envio_subasta(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo25productodespachado.html");


        $html = str_replace("{{nombre_usuario}}",ucfirst($data_vendedor['nombre']), $html);
        $html = str_replace("{{trans36_brand}}",$json->trans36_brand, $html);


        $html = str_replace("{{trans37}}",$json->trans37, $html);
        $html = str_replace("{{trans38}}",$json->trans38, $html);
        $html = str_replace("{{trans39}}",$json->trans39, $html);
        $html = str_replace("{{trans34}}",$json->trans34, $html);
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
        $titulo    = $json->trans119_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
    //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }




    public function traer_referidos_y_ventas(Array $data){
        $total_usd_ventas_mes_actual=0; 
        $total_usd_ventas_mes_anterior=0; 
        if( !isset($data) || !isset($data['referido']) ) {
            return array('status' => 'fail',
                'message'=> 'faltan datos',
                'cantidad'=> null,
                'data' => null
            );
        }
        
        parent::conectar();

        $selectxusuarios = "SELECT * FROM peer2win.usuarios WHERE referido_nasbi = '$data[referido]'";
        $referidoUsuarios = parent::consultaTodo($selectxusuarios);
        parent::cerrar();

        if(count($referidoUsuarios) <= 0) {
            return array(
                'status'  => 'fail',
                'message' => 'no tiene referidos',
                'data'    => null
            );
        }
        
        $referidoUsuarios = $this->mapUsuarios_referido($referidoUsuarios, 0);
        

        foreach ($referidoUsuarios as $x => $ref) {
            $ventas_mes_actual=0;
            $ventas_mes_anterior=0; 
            $cantidad_usd_vendida_mes_actual=0; 
            $cantidad_usd_vendida_mes_anterior=0; 
        //    $data_respuesta= $this->ingresosMensualVentas([
        //        "uid"=> $ref["uid"], 
        //        "empresa" => "0", 
        //        "fecha_inicio" => intval(microtime(true)),
        //        "hace_meses"=> 0

        //     ]);
            $data_respuesta= $this->ingresosMensualVentas([
                "uid"=> 1378, 
                "empresa" => "0", 
                "fecha_inicio" => 1614661200000,
                "hace_meses"=> 0

            ]);
            if($data_respuesta["data"]!=null){
                $ventas_mes_actual= count($data_respuesta["data"]["mesactual"]);
                $ventas_mes_anterior= count($data_respuesta["data"]["mesanterior"]);
                foreach ($data_respuesta["data"]["mesactual"] as $y => $venta) {
                    $cantidad_usd_vendida_mes_actual+=$venta["precio_usd"]; 
                }

                foreach ($data_respuesta["data"]["mesanterior"] as $y => $venta) {
                    $cantidad_usd_vendida_mes_anterior+=$venta["precio_usd"]; 
                }
                
            }
            $referidoUsuarios[$x]["ventas"] = $data_respuesta["data"]; 
            $referidoUsuarios[$x]["cantidad_ventas_mes_actual"] =  $ventas_mes_actual; 
            $referidoUsuarios[$x]["cantidad_ventas_mes_anterior"] =  $ventas_mes_anterior;
            $total_usd_ventas_mes_actual=+$cantidad_usd_vendida_mes_actual; 
            $total_usd_ventas_mes_anterior=+$cantidad_usd_vendida_mes_anterior; 
        }



      


       // var_dump($referidoUsuarios); 

        return array(
            'status'         => 'success',
            'message'        => 'referidos',
            'dataNoEmpresa'  => $referidoUsuarios, 
            'cantidad_vendida_mes_actual_usd'=> $total_usd_ventas_mes_actual,
            'cantidad_vendida_mes_anterior_usd'=> $total_usd_ventas_mes_anterior
        );
        
    }

    function mapUsuarios_referido(Array $usuarios, Int $empresa)
    {
        $datauid = null;
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datarazonsocial = "";
        $datapais = null;
        $datanit = null;
        $datatipo_documento_dueno = null;
        $dataestado = null;
        $datafecha_creacion = null;
        $datafecha_actualizacion = null;
        $dataacumulado_usd = 0;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datauid         = $user['id'];
                $datanombre      = strtoupper($user['nombreCompleto']);
                $datarazonsocial = strtoupper($datanombre);
                $dataempresa     = $user['email'];//"Nasbi";
                $datacorreo      = $user['email'];
                $datatelefono    = $user['telefono'];
                $datapais        = $user['paisid'];

                $datafecha_creacion        = $user['fecha_ingreso'];
                $datafecha_actualizacion   = $user['fecha_ingreso'];

                $dataestado = floatval($user['status']);
            }else if($empresa == 1){

                $datanombre                = strtoupper($user['razon_social']);
                $datauid                   = $user['id'];
                $dataempresa               = strtoupper($user['razon_social']);
                $datarazonsocial           = strtoupper($user['razon_social']);
                $datacorreo                = $user['correo'];
                $datatelefono              = $user['telefono'];
                $datapais                  = $user['pais'];
                $datanit                   = $user['nit'];
                $datatipo_documento_dueno  = floatval($user['tipo_documento_dueno']);
                $dataestado                = floatval($user['estado']);

                $datafecha_creacion        = floatval($user['fecha_creacion']);
                $datafecha_actualizacion   = floatval($user['fecha_actualizacion']);

                if ( !isset( $user['acumulado_usd'] ) ) {
                    $dataacumulado_usd     = 0;
                }else{
                    if ( $user['acumulado_usd'] != null ) {
                        $dataacumulado_usd = floatval($user['acumulado_usd']);
                    }else{
                        $dataacumulado_usd = 0;
                    }
                }
            }

            unset($user);

            if( $datanombre == "..." ){
                $datanombre = "";
            }
            if( $datanombre == "..." ){
                $datanombre = "";
            }
            if( $datacorreo == "..." ){
                $datacorreo = "";
            }
            if( $datatelefono == "..." ){
                $datatelefono = "";
            }
            if( $datarazonsocial == "..." ){
                $datarazonsocial = "";
            }
            if( $datanit == "..." ){
                $datanit = "";
            }
            if( $datapais == "..." ){
                $datapais = "";
            }
            if( $datatipo_documento_dueno == "..." ){
                $datatipo_documento_dueno = "";
            }

            $user['uid'] = $datauid;
            $user['id'] = $datauid;
            $user['nombre'] = $datanombre;
            $user['nombre_empresa'] = $datanombre;
            $user['empresa'] = $empresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['razon_social'] = $datarazonsocial;
            $user['nit'] = $datanit;
            $user['pais'] = $datapais;
            $user['tipo_documento_dueno'] = $datatipo_documento_dueno;
            $user['estado'] = $dataestado;

            $user['fecha_creacion'] = $datafecha_creacion;
            $user['fecha_actualizacion'] = $datafecha_actualizacion;

            $user['acumulado_usd'] = $dataacumulado_usd;

            $usuarios[$x] = $user;
        }

        return $usuarios;
    }


    function datos_para_estadistica_cards(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $data_user= $this->datosUser_estadistica(array(
            "uid"=> $data["uid"],
            "empresa"=> $data["empresa"]
        )); 
        $datos_ventas_compra= $this->extraer_datos_venta_y_compra(array(
            "uid"=> $data["uid"],
            "empresa"=> $data["empresa"]

        )); 
        if(!isset($data_user)){
            return array("status"=>"fail", "datos_compra_venta"=>$datos_ventas_compra, "datos_user"=>$data_user); 
        }
        $data_user=$data_user["data"];
        return array("status"=>"success", "datos_compra_venta"=>$datos_ventas_compra, "datos_user"=>$data_user); 
    }

    function datosUser_estadistica(Array $data)
    {   

        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        parent::conectar(); 
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar(); 
        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios_estadistica($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }

    function mapUsuarios_estadistica(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;
        $fecha_ingreso= null; 
        $activo=null; 

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto'];//"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
                $fecha_ingreso=strtotime($user["fecha_ingreso"])*1000;
                if(intval($user['inactivo'])==2){
                    $activo=0; 
                }else{
                    $activo=1; 
                }
            }else if($empresa == 1){
                if(intval($user['estado'])==2){
                    $activo=0; 
                }else{
                    $activo=1; 
                }
                $datanombre = $user['razon_social'];//$user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $fecha_ingreso=$user['fecha_creacion'];
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $user['fecha_ingreso']=$fecha_ingreso;
            $user['activo']=$activo;
            $usuarios[$x] = $user;
        }

        return $usuarios;
    }


    function extraer_datos_venta_y_compra(Array $data){
        $cantidad_compras_realizadas = $this->traer_compras_realizadas_por_usuario($data); 
        $cantidad_ventas_realizadas  = $this->traer_ventas_realizadas_por_usuario($data);

        $venta_mas_producto_mas_caro = $this->traer_venta_mas_caro($data); 

        
        $venta_producto_mas_vendido  = $this->traer_venta_mas_vendida_producto($data); 


        $compra_mas_valiosa          = $this->traer_compra_mas_caro($data); 
        $cantidad_envios_completados = $this->cantidad_envios_completados($data); 
        $pedidos_pendientes          = $this->cantidad_pedidos_pendientes($data); 


        return array(
            "cantidad_compra"             => $cantidad_compras_realizadas,
            "cantidad_venta"              => $cantidad_ventas_realizadas,
            "venta_mas_cara"              => $venta_mas_producto_mas_caro,
            "producto_mas_vendido"        => $venta_producto_mas_vendido,
            "cantidad_envios_completados" => $cantidad_envios_completados,
            "compra_mas_valiosa"          => $compra_mas_valiosa,
            "ventas_a_preparar"           => $pedidos_pendientes
        );
        
    }


    function cantidad_pedidos_pendientes(Array $data){
        
        parent::conectar();
        $select_mas_vendido = "SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas_a_preparar FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 6 ";
        $product_mas_vendido = parent::consultaTodo($select_mas_vendido);
        parent::cerrar();

        if(count($product_mas_vendido) <= 0){
            
            return 0;
        }else{
        
          return $product_mas_vendido[0]["ventas_a_preparar"]; 
            
            
        }
        return 0; 

    }


    function cantidad_envios_completados(Array $data){
        // tipos de envio 1 y 2
        parent::conectar();
        $select_mas_vendido = "
        SELECT COUNT(p.id) cantidad_completados_envio 
        FROM (SELECT * FROM productos_transaccion  pt  WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.estado = 13) pt_com JOIN productos p ON (pt_com.id_producto = p.id) where p.envio = 1 or p.envio = 2";
        $product_mas_vendido = parent::consultaTodo($select_mas_vendido);
        parent::cerrar();

        if(count($product_mas_vendido) <= 0){
            
            return 0;
        }else{
        
          return $product_mas_vendido[0]["cantidad_completados_envio"]; 
            
            
        }
    }

    function traer_venta_mas_vendida_producto(Array $data){
        parent::conectar();
        $select_mas_vendido = 
            "SELECT
                id_producto,
                cantidad,
                uid_vendedor,
                empresa
            FROM  productos_transaccion AS pt
            WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa]
            GROUP BY id_producto
            ORDER BY cantidad DESC
            LIMIT 1;";

        // "SELECT
        //     id_producto,
        //     COUNT( id_producto ) AS total,
        //     uid_vendedor,
        //     empresa
        // FROM  productos_transaccion AS pt
        // WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa]
        // GROUP BY id_producto
        // ORDER BY total DESC LIMIT 1";
        $product_mas_vendido = parent::consultaTodo($select_mas_vendido);
        parent::cerrar();

        if(count($product_mas_vendido) <= 0){
            return [];
        }else{
            return $this->preparar_data_de_venta_producto_mas_vendido($product_mas_vendido[0]);
        }
    }

    function preparar_data_de_venta_producto_mas_vendido(Array $data){
        
        $data_producto = $this->get_product_por_id([
            'uid'     => $data["uid_vendedor"],
            'id'      => $data["id_producto"],
            'empresa' => $data["empresa"]
        ]); 

        if( COUNT($data_producto) == 0 ){
            return array("data_producto" => $data_producto, "data_transanccion" => $data); 
        }
        return array("data_producto" => $data_producto[0], "data_transanccion" => $data); 
    }





    function traer_venta_mas_caro(Array $data){
        parent::conectar();
        $selec_compras = 
            "SELECT
                pt.*,
                IF( pt.tipo = 1,
                    (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                    pt.precio_comprador
                ) AS 'TOTAL_VENTA'
                
            FROM  productos_transaccion AS pt
            WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa]
            GROUP BY id_producto
            ORDER BY TOTAL_VENTA DESC
            LIMIT 1;";

        $selec_compras_old = 
            "SELECT
                ptm.*
            FROM (
                SELECT
                    pt.*
                FROM productos_transaccion as pt
                JOIN (
                    SELECT
                        MAX(precio) as precios_maximos,
                        id_carrito,
                        id
                    FROM productos_transaccion pt
                    WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.estado = 13
                    GROUP BY id_carrito
                ) AS pm ON (pt.id = pm.id)
            ) AS ptm
            JOIN productos as p ON (ptm.id_producto = p.id )
            ORDER BY precio
            DESC LIMIT 1";

        $cantidad_compras_realizadas = parent::consultaTodo($selec_compras);
        parent::cerrar();

        if(count($cantidad_compras_realizadas) <= 0){
            return [];
        }else{
          return $this->preparar_data_de_venta_superior($cantidad_compras_realizadas[0]);
        }
    }






    function traer_compra_mas_caro(Array $data){
        parent::conectar();
        $selec_compras = 
            "SELECT
                pt.*,
                IF( pt.tipo = 1,
                    (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                    pt.precio_comprador
                ) AS 'TOTAL_VENTA'
                
            FROM  productos_transaccion AS pt
            WHERE pt.uid_comprador = $data[uid] AND pt.empresa = $data[empresa]
            GROUP BY id_producto
            ORDER BY TOTAL_VENTA DESC
            LIMIT 1;";

	    $selec_compras_old = 
            "SELECT
                pt.*
            FROM productos_transaccion as pt
            JOIN (SELECT MAX(precio) as precios_maximos, id_carrito, id FROM productos_transaccion  pt WHERE pt.uid_comprador = $data[uid] AND pt.empresa_comprador = $data[empresa] AND pt.estado = 13 Group by id_carrito) as pm ON (pt.id = pm.id)  ORDER BY precio DESC limit 1";
        $cantidad_compras_realizadas = parent::consultaTodo($selec_compras);
        parent::cerrar();

        if(count($cantidad_compras_realizadas) <= 0){
            
            return [];
        }else{
           return $this->preparar_data_de_compra_superior($cantidad_compras_realizadas[0]); 
            
            
        }

    }

    function preparar_data_de_compra_superior(Array $data){
        
        $data_producto = $this->get_product_por_id([
            'uid'   => $data["uid_vendedor"],
            'id' => $data["id_producto"],
            'empresa'  => $data["empresa"]
        ]);
    
        if( COUNT($data_producto) == 0 ){
            return array("data_producto"=> $data_producto, "data_transanccion"=> $data); 
            
        }else{
            $data_producto[0]['precio'] = $data['TOTAL_VENTA'];
            return array("data_producto"=> $data_producto[0], "data_transanccion"=> $data); 
        }

    }


    function preparar_data_de_venta_superior(Array $data){
         
        $data_producto = $this->get_product_por_id([
            'uid'     => $data["uid_vendedor"],
            'id'      => $data["id_producto"],
            'empresa' => $data["empresa"]
        ]); 
    
        if( COUNT($data_producto) == 0 ){
            return array("data_producto"=> $data_producto, "data_transanccion"=> $data); 
            
        }else{
            $data_producto[0]['precio'] = $data['TOTAL_VENTA'];
            return array("data_producto"=> $data_producto[0], "data_transanccion"=> $data); 
        }
    }


    function traer_compras_realizadas_por_usuario(Array $data){

        parent::conectar();
	    $selec_compras = "
        SELECT 
            COUNT( DISTINCT(pt.id_carrito) ) AS compras FROM productos_transaccion  pt 
            WHERE pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]' AND pt.estado = 13 ";
        $cantidad_compras_realizadas = parent::consultaTodo($selec_compras);
        parent::cerrar();
        if(count($cantidad_compras_realizadas) <= 0){
            
            return 0;
        }else{
            return $cantidad_compras_realizadas[0]["compras"]; 
        }


    }



    function traer_ventas_realizadas_por_usuario(Array $data){

        parent::conectar();
	    $selec_compras = "SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas FROM productos_transaccion  pt WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.estado = 13";
        $cantidad_compras_realizadas = parent::consultaTodo($selec_compras);
        parent::cerrar();
        if(count($cantidad_compras_realizadas) <= 0){
            
            return 0;
        }else{
            return $cantidad_compras_realizadas[0]["ventas"]; 
        }


    }












}

?>