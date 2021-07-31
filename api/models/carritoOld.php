<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Carrito extends Conexion
{
    public function agregar(Array $data){
        if(!isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto'])|| !isset($data['cantidad'])|| !isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $producto = $this->productoId($data);
        if($producto['status'] != 'success') return $producto;
        $producto = $producto['data'];

        if($producto['uid'] == $data['uid'] && $producto['empresa'] == $data['empresa']) return array('status' => 'productoPertenece', 'message'=> 'este producto te pertenece', 'cantidad'=> 0, 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $carrito = null;


        $refer = '';
        if(isset($data['refer'])) $refer = $data['refer'];
        $data = $this->mapAddCarrito($data, true);
        $existecarrito = $this->productoCarrito($data);
        parent::conectar();
        if($existecarrito['status'] == 'fail'){
            $insertcarrito = "INSERT INTO carrito(
                uid,
                empresa,
                id_producto,
                cantidad,
                moneda,
                estado,
                tipo,
                refer,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES(
                '$data[uid]',
                '$data[empresa]',
                '$data[id_producto]',
                '$data[cantidad]',
                '$data[moneda]',
                1,
                1,
                '$refer',
                '$fecha',
                '$fecha'
            );";
            $carrito = parent::query($insertcarrito);
        }else if($existecarrito['status'] == 'success'){
            // if($data['cantidad'] >  $existecarrito['data']['cantidadProducto']) return array('status' => 'superaCantidad', 'message'=> 'no data', 'data' => null);
            $cantidad = $existecarrito['data']['cantidad'] + $data['cantidad'];
            // if($cantidad >  $existecarrito['data']['cantidadProducto']) return array('status' => 'superaCantidad', 'message'=> 'no data', 'data' => null);
            $idcarrito = $existecarrito['data']['id'];
            $updatecarrito = "UPDATE carrito
            SET
                cantidad = '$cantidad',
                estado = 1,
                refer = '$refer',
                fecha_actualizacion = '$fecha'
            WHERE id = '$idcarrito' AND uid = '$data[uid]' AND empresa = '$data[empresa]' AND id_producto = '$data[id_producto]' AND moneda = '$data[moneda]';";
            $carrito = parent::query($updatecarrito);
        }
        parent::cerrar();
        if(!$carrito) return array('status' => 'fail', 'message'=> 'no se pudo agregar al carrito', 'cantidad'=> 0, 'data' => null);
        
        return array('status' => 'success', 'message'=> 'agregado al carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }


    public function agregarNoLogueado(Array $data)
    {
        if(!isset($data)) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        foreach ($data as $x => $carrito) {
            $this->agregar($carrito);
        }

        return array('status' => 'success', 'message'=> 'agregado al carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }

    public function eliminar(Array $data, Int $tipo = 1)
    {
        if(!isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto']) || !isset($data['moneda'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $deletexcarrito = "UPDATE carrito
        SET 
            estado = 0,
            cantidad = 0,
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND id_producto = '$data[id_producto]' AND moneda = '$data[moneda]' AND tipo = '$tipo';";
        $delete = parent::query($deletexcarrito);
        parent::cerrar();
        if(!$delete) return array('status' => 'fail', 'message'=> 'no eliminado del carrito', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'eliminado del carrito', 'data' => null);
    }

    public function actualizar(Array $data)
    {
        if(!isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto']) || !isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $data = $this->mapAddCarrito($data, true);
        $carritoid = $this->productoCarritoId($data);
        if($carritoid['status'] == 'fail') return $carritoid;
        
        $carritoid = $carritoid['data'];
        if($carritoid['moneda'] != $data['moneda']){
            $delete = $this->eliminar($carritoid);
            if($delete['status'] == 'fail') return $delete;
            return $this->agregar($data);
        }

        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $updatexcarrito = "UPDATE carrito
        SET 
            estado = 1,
            cantidad = '$data[cantidad]',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND id_producto = '$data[id_producto]' AND moneda = '$data[moneda]' AND tipo = 1;";
        $update = parent::query($updatexcarrito);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'no carrito actualizado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'carrito actualizado', 'data' => null);
    }

    public function productoCarrito(Array $data){
        if(!isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto']) || !isset($data['cantidad']) || !isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectcarritoxuserxproductoxmoneda = "SELECT c.*, p.cantidad AS cantidadProducto, p.estado AS estado_producto, p.uid AS uid_vendedor, p.empresa AS empresa_producto
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND c.id_producto = '$data[id_producto]' AND c.moneda = '$data[moneda]' AND c.tipo = 1 AND p.estado = 1;";
        $carrito = parent::consultaTodo($selectcarritoxuserxproductoxmoneda);
        parent::cerrar();
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no existe en el carrito', 'cantidad'=> 0, 'data' => null);
        
        $carrito = $carrito[0];
        return array('status' => 'success', 'message'=> 'existe en el carrito', 'cantidad'=> count($carrito), 'data' => $carrito);

    }

    public function productoCarritoId(Array $data){
        if(!isset($data['id'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectcarritoxuserxproductoxmoneda = "SELECT c.*, p.cantidad AS cantidadProducto, p.estado AS estado_producto, p.uid AS uid_vendedor, p.empresa AS empresa_producto
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        WHERE c.id = '$data[id]'";
        $carrito = parent::consultaTodo($selectcarritoxuserxproductoxmoneda);
        parent::cerrar();
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no existe en el carrito', 'cantidad'=> 0, 'data' => null);
        
        $carrito = $carrito[0];
        return array('status' => 'success', 'message'=> 'existe en el carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }

    public function carritoUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectcarritoxuser = "SELECT c.*, p.cantidad AS cantidadProducto, 
        p.precio_usd, p.precio AS precio_local, p.moneda_local, p.oferta, p.porcentaje_oferta, p.titulo, p.foto_portada, p.estado AS estado_producto, p.uid AS uid_vendedor, p.empresa AS empresa_producto, p.envio AS tipo_envio, p.producto,
        pe.id AS id_prodcuto_envio, pe.id_shippo AS id_prodcuto_envio_shippo,
        d.id AS id_direccion_vendedor, d.id_shippo AS id_direccion_vendedor_shippo
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id
        WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND p.estado = 1 AND c.estado = 1;";
        $carrito = parent::consultaTodo($selectcarritoxuser);
        parent::cerrar();
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no tiene productos en el carrito', 'cantidad'=> 0, 'data' => null, 'dataTotales'=> null);
        
        $arraycarito = $this->carritoUsuarioMap($carrito, false);
        return array('status' => 'success', 'message'=> 'carrito usuario', 'cantidad'=> count($arraycarito['carrito']), 'data' => $arraycarito['carrito'], 'dataTotales'=> $arraycarito['carritoTotales']);

    }

    public function contarCarritoUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectcarritoxuser = "SELECT SUM(c.cantidad) AS contar
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND p.estado = 1 AND c.estado = 1;";
        $carrito = parent::consultaTodo($selectcarritoxuser);
        parent::cerrar();
        if(count($carrito) <= 0) return array('status' => 'fail', 'message' => 'no tiene productos en el carrito', 'data' => null);
        
        return array('status' => 'success', 'message' => 'carrito usuario', 'data'=> $carrito[0]['contar']);
    }

    public function carritoUsuarioNoLogeado(Array $data){
        if(!isset($data) || count($data) <= 0) return $request = array('status' => 'fail', 'message'=> 'no tiene productos en el carrito no logeado', 'data' => null);
        $data = $this->mapAddCarrito($data, false);
        usort($data, function($a, $b) {return strcmp($a['id_producto'], $b['id_producto']);});
        $array_id_productos = $this->unique_multidim_array($data, 'id_producto');
        $array_id_productos = implode(',',array_map(function($a) {return $a['id_producto'];}, $array_id_productos));
        parent::conectar();
        $selectproductosxcarritoxuserxnoxlog = "SELECT p.id AS id_producto, p.cantidad AS cantidadProducto, p.precio_usd, p.precio AS precio_local, p.moneda_local, p.oferta, p.porcentaje_oferta, p.titulo, p.foto_portada, p.estado AS  estado_producto, p.uid AS uid_vendedor, p.empresa AS empresa_producto, p.envio AS tipo_envio,
        pe.id AS id_prodcuto_envio, pe.id_shippo AS id_prodcuto_envio_shippo,
        d.id AS id_direccion_vendedor, d.id_shippo AS id_direccion_vendedor_shippo
        FROM productos p
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id
        WHERE p.id IN ($array_id_productos) AND p.estado = 1
        ORDER BY p.id ASC;";
        $productos = parent::consultaTodo($selectproductosxcarritoxuserxnoxlog);
        parent::cerrar();
        if(count($productos) <= 0) return array('status' => 'fail', 'message'=> 'no tiene productos en el carrito no logeado', 'cantidad'=> 0, 'data' => null, 'dataTotales'=> null);
        
        $arraycarito = $this->carritoUsuarioMapNoLog($productos, $data);
        return array('status' => 'success', 'message'=> 'carrito usuario no logeado', 'cantidad'=> count($arraycarito['carrito']), 'data' => $arraycarito['carrito'], 'dataTotales'=> $arraycarito['carritoTotales']);
        
    }

    public function agregarDataDeNoLogueado(Array $data){
        if(!isset($data) || !isset($data['iso_code_2']) || !isset($data['data_carrito']) || count($data['data_carrito']) <= 0) return array('status' => 'fail', 'message'=> 'no tiene productos en el carrito no logeado', 'data' => null);
        $data['data_carrito'] = $this->mapAddCarrito($data['data_carrito'], false);
        usort($data['data_carrito'], function($a, $b) {return strcmp($a['id_producto'], $b['id_producto']);});
        foreach ($data['data_carrito'] as $x => $carrito) {
            $this->agregar($carrito);
        }
        return $this->carritoUsuario($data);
    }

    public function rutasEnvio(Array $data)
    {
        if(!isset($data) || !isset($data['destino']) || !isset($data['carrito'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['carrito']['id']) || !isset($data['carrito']['uid']) || !isset($data['carrito']['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $carrito = $this->carritoPagar($data['carrito'], false);
        if($carrito['status'] == 'fail') return $carrito;
        $carrito = $carrito['data'][0];

        $data['destino'] = addslashes($data['destino']);

        $fromAddress = (array) json_decode(Shippo_Address::retrieve($carrito['id_direccion_vendedor_shippo']), true); //EEUU
        $toAddress = (array) json_decode(Shippo_Address::retrieve($data['destino']), true); //COLOMBIA
        $parcel = (array) json_decode(Shippo_Parcel::retrieve($carrito['id_prodcuto_envio_shippo']), true);
        unset($parcel['extra']);
        $net_weight = $parcel['weight'] * $carrito['cantidad'];
        $net_height = $parcel['height'] * $carrito['cantidad'];

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
            'description' => $carrito['producto'],
            'quantity' => $carrito['cantidad'],
            'net_weight' => $net_weight,
            'mass_unit' => $parcel2['mass_unit'],
            'value_amount' => $carrito['precio_usd'],
            'value_currency' => 'USD',
            'origin_country' => $fromAddress['country'],
            'tariff_number' => '',
        )), true);

        // Creating the Customs Declaration
        // The details on creating the CustomsDeclaration is here: https://goshippo.com/docs/reference#customsdeclarations
        $customs_declaration = (array) json_decode(Shippo_CustomsDeclaration::create(
        array(
            'contents_type'=> 'MERCHANDISE',
            'contents_explanation'=> $carrito['producto'].' Compra',
            'non_delivery_option'=> 'RETURN',
            'certify'=> 'true',
            'certify_signer'=> 'Nasbi',
            'items'=> array($customs_item['object_id']),
        )), true);
        
        // echo (json_encode($parcel2));
        // echo (json_encode($toAddress));
        
        $shipment = (array) json_decode(Shippo_Shipment::create(
            array(
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'parcels' => array($parcel2),
                'customs_declaration' => $customs_declaration['object_id'],
                'async' => false
            )
        ));

        // echo (json_encode($shipment));
        if($shipment['status'] != 'SUCCESS') return array('status' => 'fail', 'message'=> 'rutas', 'cantidad'=> 0, 'data' => null);

        return array('status' => 'success', 'message'=> 'rutas', 'cantidad'=> count($shipment['rates']), 'data' => $shipment['rates']);
    }

    public function direccionVendedorCarrito(Array $data)
    {
        if(!isset($data) || !isset($data['id_direccion_vendedor'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectxdirxvendedor = "SELECT d.*
        FROM direcciones d
        WHERE d.id = '$data[id_direccion_vendedor]';";
        $direccion_vendedor = parent::consultaTodo($selectxdirxvendedor);
        parent::cerrar();

        if(count($direccion_vendedor ) <= 0) return array('status' => 'fail', 'message'=> 'direccion', 'data' => null);

        return array('status' => 'success', 'message'=> 'direccion', 'data' => $direccion_vendedor[0]);
    }

    function saveShippo(Array $data)
    {
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
        // echo (json_encode($transaction));
        
        // Retrieve label url and tracking number or error message
        if ($transaction['status'] == 'SUCCESS'){
            
            $updatedireccion = "UPDATE productos_transaccion_envio
            SET
                numero_guia = '$transaction[tracking_number]',
                url_numero_guia = '$transaction[tracking_url_provider]',
                empresa = '$empresa',
                etiqueta_envio = '$transaction[label_url]',
                factura_comercial = '$transaction[commercial_invoice_url]',
                id_envio_shippo = '$data[id_envio]',
                id_ruta_shippo = '$data[id_ruta]',
                id_transaccion_shippo = '$transaction[object_id]'
            WHERE id = '$data[id]'";
            $actualizar = parent::query($updatedireccion);
            if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
            
            return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
        }else {
            return array('status' => 'fail', 'message'=> 'envio no actualizado', 'data' => $transaction['messages']);
        }
    }

    public function pagarCarrito(Array $data)
    {
        if(!isset($data['carrito']) || !isset($data['metodo_pago']) || !isset($data['direccion'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['carrito']) || !isset($data['carrito']['id']) || !isset($data['carrito']['uid']) || !isset($data['carrito']['empresa']) || !isset($data['carrito']['precio_moneda_actual_usd']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['metodo_pago']) || !isset($data['metodo_pago']['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['direccion']) || !isset($data['direccion']['id']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        // if(!isset($data['ruta'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        
        // get carrito y validaciones de compras
        $carrito = $this->carritoPagar($data['carrito']);
        if($carrito['status'] == 'fail') return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $carrito = $carrito['data'][0];

        if($carrito['precio'] <= 0.000000) return array('status' => 'fail', 'message'=> 'monto invalido', 'data' => null);
        if($carrito['cantidad'] > $carrito['cantidadProducto'] && $carrito['tipo'] == 1) return array('status' => 'superaStock', 'message'=> 'la cantidad que deseas comprar supera el stock', 'data' => null);
        if($carrito['tipo_envio'] == 2 && !isset($data['shippo'])) return array('status' => 'noRuta', 'message'=> 'Por favor, selecciona una ruta de envío', 'data' => null);
        
        //fecha principal
        $fecha = intval(microtime(true)*1000);


        //realizar pago del prodcuto
        $pago = [];
        $request = null;
        parent::conectar();

        if($carrito['tipo'] == 1){
            $pago = $this->realizarCompra($carrito, $data['metodo_pago'], $fecha);
            if($pago['status'] != 'success') return $pago;

            $cantidad = floatval($carrito['cantidadProducto'] - $carrito['cantidad']);
            $updatexcantidadxproducto = "UPDATE productos
            SET cantidad = '$cantidad',
            fecha_actualizacion = '$fecha'
            WHERE uid = '$carrito[uid]' AND id = '$carrito[id_producto]';";
            $updatequery = parent::query($updatexcantidadxproducto);
            if(!$updatequery) return array('status' => 'fail', 'message'=> 'no se pudo actualizar la cantidad', 'data' => null);
        }
        
        $estado_transaccion = 1;
        $metodo_pago_transaccion = $data['metodo_pago']['id'];
        $data['descripcion_extra'] = addslashes($data['descripcion_extra']);

        if($carrito['tipo'] == 2) $estado_transaccion = 6;

        $insertarxtransaccion = "INSERT INTO productos_transaccion
        (
            id_carrito,
            id_producto,
            uid_vendedor,
            uid_comprador,
            cantidad,
            boolcripto,
            moneda,
            precio,
            precio_usd,
            precio_moneda_actual_usd,
            tipo,
            estado,
            contador,
            id_metodo_pago,
            empresa,
            empresa_comprador,
            descripcion_extra,
            refer,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$carrito[id]',
            '$carrito[id_producto]',
            '$carrito[uid_vendedor]',
            '$carrito[uid]',
            '$carrito[cantidad]',
            '$carrito[boolcripto]',
            '$carrito[moneda]',
            '$carrito[precio]',
            '$carrito[precio_usd]',
            '$carrito[precio_moneda_actual_usd]',
            '$carrito[tipo]',
            '$estado_transaccion',
            '0',
            '$metodo_pago_transaccion',
            '$carrito[empresa_producto]',
            '$carrito[empresa]',
            '$data[descripcion_extra]',
            '$carrito[refer]',
            '$fecha',
            '$fecha'
        );";
        $transaccionquery = parent::queryRegistro($insertarxtransaccion);
        if($transaccionquery){
            $adicional = [
                'id_transaccion'=> $transaccionquery,
                'fecha' => $fecha
            ];
            $this->insertarMetodoPago($carrito, $data['metodo_pago'], $pago, $adicional);
            
            $this->insertarTimeline([
                'id' => $transaccionquery,
                'estado' => 1,
                'fecha' => $fecha,
                'tipo' => 1
            ]);
            $this->insertarTimeline([
                'id' => $transaccionquery,
                'estado' => 1,
                'fecha' => $fecha,
                'tipo' => 2
            ]);

            $id_envio = null;
            $id_ruta = null;
            if(isset($data['shippo'])){
                $id_envio = $data['shippo']['id_envio'];
                $id_ruta = $data['shippo']['id_ruta'];
            }

            $this->insertarEnvio([
                'tipo_envio' => $carrito['tipo_envio'],
                'id_prodcuto_envio'=> $carrito['id_prodcuto_envio_shippo'],
                'id_envio' => $id_envio,
                'id_ruta' => $id_ruta,
                'id_transaccion' => $transaccionquery,
                'id_direccion_vendedor'=> $carrito['id_direccion_vendedor'], 
                'id_direccion_comprador'=> $data['direccion']['id'],
                'fecha' => $fecha,
            ]);
            parent::cerrar();
            $this->eliminar($carrito, $carrito['tipo']);
            $notificacion = new Notificaciones();
            //vendedor
            $notificacion->insertarNotificacion([
                'uid' => $carrito['uid_vendedor'],
                'empresa' => $carrito['empresa_producto'],
                'text' => 'Inició proceso de venta de tu producto '.$carrito['titulo'].', revisa tu listado de ventas',
                'es' => 'Inició proceso de venta de tu producto '.$carrito['titulo'].', revisa tu listado de ventas',
                'en' => 'Start the process of buying and selling your product, check your sales list',
                'keyjson' => '',
                'url' => ''
            ]);
            //comprador
            $notificacion->insertarNotificacion([
                'uid' => $carrito['uid'],
                'empresa' => $carrito['empresa'],
                'text' => 'Has comenzado el proceso de compra del producto '.$carrito['titulo'],
                'es' => 'Has comenzado el proceso de compra del producto '.$carrito['titulo'],
                'en' => 'You have started the process of purchasing the product '.$carrito['titulo'],
                'keyjson' => '',
                'url' => ''
            ]);
            unset($notificacion);
            $request = array('status' => 'success', 'message'=> 'compra realizada', 'data' => null);
        }else{
            parent::cerrar();
            $request = array('status' => 'fail', 'message'=> 'no se pudo realizar la compra', 'data' => null);
        }

        return $request;
    }

    function productoId(Array $data)
    {
        if(!isset($data) || !isset($data['id_producto'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxproducto = "SELECT p.*
        FROM productos p
        WHERE p.id = '$data[id_producto]' AND p.estado = '1'";
        $producto = parent::consultaTodo($selectxproducto);
        parent::cerrar();
        if(count($producto) <= 0) return array('status' => 'fail', 'message'=> 'no producto', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'producto', 'data' => $producto[0]);
    }

    function carritoPagar(Array $carrito, Bool $bcompra = true)
    {
        $request = null;
        parent::conectar();
        $selectxcarritoxcompra = "SELECT c.*, p.cantidad AS cantidadProducto, p.precio_usd, p.precio AS precio_local, p.moneda_local, p.oferta, p.porcentaje_oferta, p.titulo, p.foto_portada, p.estado AS estado_producto, p.uid AS uid_vendedor, p.empresa AS empresa_producto, p.envio AS tipo_envio, p.producto,
        pe.id AS id_prodcuto_envio, pe.id_shippo AS id_prodcuto_envio_shippo,
        d.id AS id_direccion_vendedor, d.id_shippo AS id_direccion_vendedor_shippo
        from carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id
        WHERE c.uid = '$carrito[uid]' AND c.empresa = '$carrito[empresa]' AND p.estado = 1 AND c.id = '$carrito[id]' AND c.estado = 1;";
        $carritocompra = parent::consultaTodo($selectxcarritoxcompra);
        parent::cerrar();

        if(count($carritocompra) > 0){
            $carritocompra[0] = array_merge($carritocompra[0], $carrito);
            $arraycarito = $this->carritoUsuarioMap($carritocompra, $bcompra);
            $request = array('status' => 'success', 'message'=> 'carrito usuario no logeado', 'cantidad'=> count($arraycarito['carrito']), 'data' => $arraycarito['carrito']);
        }else{
            $request = array('status' => 'fail', 'message'=> 'no tiene productos en el carrito no logeado', 'cantidad'=> 0, 'data' => null);
        }

        return $request;
    }

    function realizarCompra(Array $carrito, Array $metodo_pago, Int $fecha)
    {
        $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if($metodo_pago['id'] == 1) $request = $this->pagarConCripto($carrito, $metodo_pago, $fecha);
        if($metodo_pago['id'] == 2) $request = array('status' => 'success', 'message'=> 'data no foto', 'data' => null);
        // completar los otros metodos de pagos
        return $request;
    }

    function pagarConCripto(Array $carrito, Array $metodo_pago, Int $fecha)
    {
        if(!isset($metodo_pago['address_comprador'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if($carrito['tipo'] == 2) return array('status' => 'success', 'message'=> 'producto subasta no aplica bloqueo', 'data' => null);
        $nasbifunciones = new Nasbifunciones();
        
        $bloqueo = $nasbifunciones->insertarBloqueadoDiferido([
            'id' => null,
            'uid'=> $carrito['uid'],
            'empresa' => $carrito['empresa'],
            'moneda'=> $carrito['moneda'],
            'all' => false,
            'precio'=>$carrito['precio'],
            'precio_momento_usd'=>$carrito['precio_moneda_actual_usd'],
            'address' => $metodo_pago['address_comprador'],
            'id_transaccion' => null,
            'tipo_transaccion' => $carrito['tipo'], //antes decia 1
            'tipo' => 'bloqueado',
            'accion' => 'push',
            'descripcion' => addslashes('Compra producto '.$carrito['titulo']),
            'fecha' => $fecha
        ]);
        unset($nasbifunciones);

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $carrito['uid'],
            'empresa' => $carrito['empresa'],
            'text' => 'Se ha bloqueado el monto '.$this->maskNumber($carrito['precio'], 6).' '.$carrito['moneda'].' de por la compra del producto '.$carrito['titulo'],
            'es' => 'Se ha bloqueado el monto '.$this->maskNumber($carrito['precio'], 6).' '.$carrito['moneda'].' de por la compra del producto '.$carrito['titulo'],
            'en' => 'You have started the process of purchasing the product '.$carrito['titulo'],
            'keyjson' => '',
            'url' => ''
        ]);
        unset($notificacion);
        return $bloqueo;
    }

    // function retirarDineroVencer(Array $data)
    // {
    //     $nasbicoinvencer = $this->nasbiCoinVencerUsuario($data);
    // }

    // function nasbiCoinVencerUsuario(Array $data)
    // {
    //     parent::conectar();
    //     $selectxnasbicoinxvencer = "SELECT ncv.* FROM nasbicoin_vencer ncv WHERE ncv.id_nasbicoin = '$data[moneda]' AND ncv.address = '$data[address]' AND estado = 1 ORDER BY fecha_vencer ASC";
    //     $nasbicoinvencer = parent::consultaTodo($selectxnasbicoinxvencer);
    //     parent::cerrar();
        
    //     if(count($nasbicoinvencer) <= 0) return array('status' => 'fail', 'message'=> 'no tiene cuenta', 'data' => null);
        
    //     $nasbicoinvencer = $this->mapeoNasbiCoinVencer($nasbicoinvencer);
    //     return array('status' => 'success', 'message'=> 'nasbicoin', 'data' => $nasbicoinvencer);
    // }

    // function mapeoNasbiCoinVencer(Array $nabicoins)
    // {
    //     foreach ($nabicoins as $x => $nasbicoin) {
    //         $nasbicoin['id'] = floatval($nasbicoin['id']);
    //         $nasbicoin['uid'] = floatval($nasbicoin['uid']);
    //         $nasbicoin['monto']= floatval($this->truncNumber($nasbicoin['monto'], 6));
    //         $nasbicoin['monto_mask']= $this->maskNumber($nasbicoin['monto'], 6);
    //         $nasbicoin['estado'] = floatval($nasbicoin['estado']);
    //         $nasbicoin['empresa'] = floatval($nasbicoin['empresa']);
    //         $nasbicoin['fecha_creacion'] = floatval($nasbicoin['fecha_creacion']*1000);
    //         $nasbicoin['fecha_actualizacion'] = floatval($nasbicoin['fecha_actualizacion']*1000);
    //         $nasbicoin['fecha_vencer'] = floatval($nasbicoin['fecha_vencer']*1000);

    //         $nabicoins[$x] = $nasbicoin;
    //     }

    //     return $nabicoins;
    // }

    function insertarTimeline(Array $data)
    {
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
            '$data[id]',
            '$data[tipo]',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
    }

    function insertarMetodoPago(Array $carrito, Array $metodo_pago, Array $pago, Array $adicional)
    {
        $addres_vendedor = null;
        $addres_comprador = null;
        $url = null;
        $descripcion = null;
        $tx_transaccion = null;

        if($metodo_pago['id'] == 1 && $carrito['tipo'] == 1){
            $addres_comprador = $metodo_pago['address_comprador'];
            $updatexbloqueadoxdiferido = "UPDATE nasbicoin_bloqueado_diferido SET id_transaccion = '$adicional[id_transaccion]' WHERE id = '$pago[data]'";
            $updatebloqdif = parent::query($updatexbloqueadoxdiferido);
        }
        $insertxmetodoxpago = "INSERT INTO productos_transaccion_detalle_pago
        (
            id_transaccion,
            id_metodo_pago,
            moneda,
            monto,
            cantidad,
            addres_vendedor,
            addres_comprador,
            url,
            descripcion,
            tx_transaccion,
            confirmado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$adicional[id_transaccion]',
            '$metodo_pago[id]',
            '$carrito[moneda]',
            '$carrito[precio]',
            '$carrito[cantidad]',
            '$addres_vendedor',
            '$addres_comprador',
            '$url',
            '$descripcion',
            '$tx_transaccion',
            '0',
            '$adicional[fecha]',
            '$adicional[fecha]'
        );";
        $insertquery = parent::query($insertxmetodoxpago);
        // completar los otros metodos de pagos
    }

    function insertarEnvio(Array $data)
    {
        $insertxenvio = "INSERT INTO productos_transaccion_envio
        (
            id_transaccion,
            tipo_envio,
            id_direccion_vendedor,
            id_direccion_comprador,
            id_prodcuto_envio,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_transaccion]',
            '$data[tipo_envio]',
            '$data[id_direccion_vendedor]',
            '$data[id_direccion_comprador]',
            '$data[id_prodcuto_envio]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insertenvio = parent::queryRegistro($insertxenvio);
        if($data['tipo_envio'] == 2) $this->saveShippo(['id_envio' => $data['id_envio'], 'id_ruta' => $data['id_ruta'], 'id' => $insertenvio]);
    }

    function mapAddCarrito(Array $data, Bool $logueado)
    {
        if($logueado == true){
            $data['uid'] = floatval(addslashes($data['uid']));
            $data['id_producto'] = floatval(addslashes($data['id_producto']));
            $data['cantidad'] = floatval(addslashes($data['cantidad']));
            $data['moneda'] = addslashes($data['moneda']);
        }else{
            foreach ($data as $x => $carrito) {
                $carrito['id_producto'] = floatval(addslashes($carrito['id_producto']));
                $carrito['cantidad'] = floatval(addslashes($carrito['cantidad']));
                $carrito['moneda'] = addslashes($carrito['moneda']);
                $data[$x] = $carrito;
            }
        }
        
        return $data;
    }

    function carritoUsuarioMapNoLog(Array $productos, Array $carrito)
    {
        $producto_carrito = null;
        $fecha = intval(microtime(true)*1000);
        foreach ($carrito as $x => $car) {
            $producto_carrito = $this->filter_by_value($productos, 'id_producto', $car['id_producto']);
            $producto_carrito = $producto_carrito[0];
            $car['id'] = $x;
            $car['uid'] = null;
            $car['estado'] = 1;
            $car['tipo'] = 1;
            $car['fecha_creacion'] = $fecha;
            $car['fecha_actualizacion'] = $fecha;
            $carrito[$x] = array_merge($car, $producto_carrito);
        }
        return $this->carritoUsuarioMap($carrito, false);
    }

    function carritoUsuarioMap(Array $carrito, Bool $compra)
    {
        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        
        
        $carritoTotales = new ArrayObject();
        
        $carritoTotales['cantidad'] = 0;
        $carritoTotales['total_usd'] = 0;
        $carritoTotales['total_mask_usd'] = 0;
        
        foreach ($carrito as $x => $car) {
            $car['precio'] = null;
            $car['precio_mask'] = null;
            $car['precio_usd_mask'] = null;
            $car['boolcripto'] = null;

            if($compra == false) $car['precio_moneda_actual_usd'] = null;
            if(isset($car['id'])) $car['id']  = floatval($car['id']);
            if(isset($car['uid'])) $car['uid'] = floatval($car['uid']);
            $car['uid_vendedor'] = floatval($car['uid_vendedor']);
            $car['id_producto'] = floatval($car['id_producto']);
            $car['cantidad'] = floatval($car['cantidad']);
            if(isset($car['estado'])) $car['estado'] = floatval($car['estado']);
            $car['tipo'] = floatval($car['tipo']);
            $car['cantidadProducto'] = floatval($car['cantidadProducto']);
            $car['estado_producto'] = floatval($car['estado_producto']);
            $car['precio_usd'] = floatval($car['precio_usd']);
            $car['precio_local'] = floatval($car['precio_local']);
            $car['oferta'] = floatval($car['oferta']);
            $car['porcentaje_oferta'] = floatval($car['porcentaje_oferta']);
            $car['empresa'] = floatval($car['empresa']);
            $car['empresa_producto'] = floatval($car['empresa_producto']);
            $car['tipo_envio'] = floatval($car['tipo_envio']);
            $car['fecha_creacion'] = floatval($car['fecha_creacion']);
            $car['fecha_actualizacion'] = floatval($car['fecha_actualizacion']);
            $car['id_prodcuto_envio'] = floatval($car['id_prodcuto_envio']);
            $car['id_direccion_vendedor'] = floatval($car['id_direccion_vendedor']);

            $car['precio_descuento_usd'] = $car['precio_usd'];
            $car['precio_descuento_local'] = $car['precio_local'];

            if($car['oferta'] == 1){
                $car['precio_descuento_usd'] = $car['precio_usd'] * ($car['porcentaje_oferta']/100);
                $car['precio_descuento_usd'] = $car['precio_usd'] - $car['precio_descuento_usd'];
                $car['precio_descuento_local'] = $car['precio_local'] * ($car['porcentaje_oferta']/100);
                $car['precio_descuento_local'] = $car['precio_local'] - $car['precio_descuento_local'];
            }

            if( $car['moneda'] == $car['moneda_local']){
                $car['precio'] = $car['precio_descuento_local'] * $car['cantidad'];
                $car['precio'] = floatval($this->truncNumber($car['precio'], 2));
                $car['precio_mask'] = $this->maskNumber($car['precio'], 2);

                if($compra == false) {
                    if ( floatval($car['precio_descuento_usd']) > 0) {
                        $car['precio_moneda_actual_usd'] = 
                        floatval($this->truncNumber(($car['precio_descuento_local'] / $car['precio_descuento_usd']), 2));
                    }else{
                        $car['precio_moneda_actual_usd'] = 0;
                    }
                }
                $car['boolcripto'] = 0;
            }else{
                if( isset($precios[$car['moneda']]) ){
                    if($compra == false) $car['precio'] = ($car['precio_descuento_usd'] / $precios[$car['moneda']]) * $car['cantidad'];
                    else $car['precio'] = ($car['precio_descuento_usd'] / $car['precio_moneda_actual_usd']) * $car['cantidad'];
                    $car['precio'] = floatval($this->truncNumber($car['precio'], 6));
                    $car['precio_mask'] = $this->maskNumber($car['precio'], 6);
                    if($compra == false) $car['precio_moneda_actual_usd'] = $precios[$car['moneda']];
                    $car['boolcripto'] = 1;
                }
                
            }

            // $car['precio_usd'] = floatval($this->truncNumber(($car['precio_descuento_usd'] * $car['cantidad']), 2));
            $car['precio_usd_mask']= $this->maskNumber($car['precio_usd'], 2);
            $car['precio_usd_total'] = floatval($this->truncNumber(($car['precio_descuento_usd'] * $car['cantidad']), 2));
            $car['precio_usd_total_mask']= $this->maskNumber($car['precio_usd_total'], 2);

            $dataPreWallet = array(
                'uid' => $car['uid'],
                'empresa' => 0
            );
            $nasbifunciones = new Nasbifunciones();
            $car['nodoWallet']= $nasbifunciones->walletNasbiUsuario( $dataPreWallet );
            
            if($car['tipo'] == 2){

                $subasta = $this->subastaIdProducto($car);
                $subasta = $subasta['data'];

                $car['oferta'] = 0;
                $car['porcentaje_oferta'] = 0;

                $car['precio_descuento_usd'] = $car['precio_usd'];
                $car['precio_descuento_local'] = $car['precio_local'];

                $car['cantidad'] = $subasta['cantidad'];

                $car['precio'] = $subasta['precio'];
                $car['precio_mask'] = $subasta['precio_mask'];

                $car['precio_usd'] = $subasta['precio_usd'];
                $car['precio_usd_mask'] = $subasta['precio_usd_mask'];

            }

            $carrito[$x] = $car;
            if($compra == false){
                if(!isset($carritoTotales[$car['moneda']])){
                    $carritoTotales[$car['moneda']] = array(
                        'cantidad'=> 0,
                        'total_usd'=> 0, 
                        'total_mask_usd'=> '0.00',
                        'total'=> 0, 
                        'total_mask'=> '0.000000',
                        'boolcripto'=> $car['boolcripto'],
                        'moneda'=> $car['moneda'], 
                        'nombremoneda'=> '', 
                        'imgmoneda'=> '',
                        'productos'=> []
                    );
                }
                $carritoTotales[$car['moneda']]['cantidad'] = floatval($carritoTotales[$car['moneda']]['cantidad'] + ($car['cantidad']));
                $carritoTotales[$car['moneda']]['total_usd'] = floatval($carritoTotales[$car['moneda']]['total_usd'] + ($car['precio_usd_total']));
                $carritoTotales[$car['moneda']]['total_mask_usd'] = $this->maskNumber($carritoTotales[$car['moneda']]['total_usd'], 2);
                
                $carritoTotales['cantidad'] = $carritoTotales['cantidad'] + $car['cantidad'];
                $carritoTotales['total_usd'] = $carritoTotales['total_usd'] + $carritoTotales[$car['moneda']]['total_usd'];
                $carritoTotales['total_mask_usd'] = $this->maskNumber($carritoTotales['total_usd'], 2);;
                
                $carritoTotales[$car['moneda']]['total'] = floatval($carritoTotales[$car['moneda']]['total'] + $car['precio']);
                $carritoTotales[$car['moneda']]['total_mask'] = $this->maskNumber($carritoTotales[$car['moneda']]['total'], 2);
                if( $car['moneda'] != $car['moneda_local']) $carritoTotales[$car['moneda']]['total_mask'] = $this->maskNumber($carritoTotales[$car['moneda']]['total'], 6);
                // array_push($carritoTotales[$car['moneda']]['productos'], $car);
            }
        }
        // $carritoTotales = (array_values(get_object_vars($carritoTotales)));
        unset($nodo);
        return array(
            'carrito'=> $carrito,
            'carritoTotales'=> $carritoTotales,
        );
    }

    function subastaIdProducto(Array $data)
    {
        parent::conectar();
        $selectxsubasta = "SELECT ps.*, p.uid AS uid_producto, p.producto, p.marca, p.modelo, p.titulo, p.descripcion, p.empresa AS empresa_producto, psi.uid, psi.empresa, psi.estado AS estado_inscrito, psi.id AS id_inscrito
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id
        INNER JOIN productos_subastas_inscritos psi ON  ps.id = psi.id_subasta
        WHERE ps.id_producto = '$data[id_producto]' AND psi.uid = '$data[uid]' AND ps.estado = '4'";
        $subasta = parent::consultaTodo($selectxsubasta);
        parent::cerrar();
        
        if(count($subasta) <= 0) return array('status' => 'fail', 'message' => 'faltan datos subasta', 'data' => null);
        
        $subasta = $this->mapProductosSubastas($subasta, true);
        $subasta = $subasta[0];
        return array('status' => 'success', 'message' => 'data subasta', 'data' => $subasta);

    }

    function mapProductosSubastas($productos){
        foreach ($productos as $x => $producto) {
            $producto['id'] = floatval($producto['id']);
            $producto['uid'] = floatval($producto['uid']);
            $producto['empresa'] = floatval($producto['empresa']);
            $producto['empresa_producto'] = floatval($producto['empresa_producto']);
            $producto['uid_producto'] = floatval($producto['uid_producto']);
            $producto['id_producto'] = floatval($producto['id_producto']);
            $producto['precio'] = floatval($producto['precio']);
            $producto['precio_mask'] = $this->maskNumber($producto['precio'], 6);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask'] = $this->maskNumber($producto['precio_usd'], 2);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['tipo'] = floatval($producto['tipo']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['apostadores'] = floatval($producto['apostadores']);
            $producto['inscritos'] = floatval($producto['inscritos']);
            $producto['estado_inscrito'] = floatval($producto['estado_inscrito']);
            $producto['id_inscrito'] = floatval($producto['id_inscrito']);
            $producto['fecha_fin'] = floatval($producto['fecha_fin']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $productos[$x] = $producto;
        }
        return $productos;
    }

    function deleteCarrito($carrito) {
        parent::conectar();

        $consulta = "DELETE FROM buyinbig.carrito WHERE uid = $carrito[uid];";
        parent::query($consulta);

        parent::cerrar();

        return array('status' => 'success', 'message' => 'Se elimino');
    }

    function unique_multidim_array(Array $array, String $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();
       
        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
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
}
?>