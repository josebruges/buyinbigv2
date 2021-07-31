<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Publicacion extends Conexion
{
    private $montoxminimoxpublicarxmonedaxlocal = 30000;
    private $montoxminimoxpublicar              = 7.927050001189057; // 5 USD
    private $porcentajexmaximoxpublicar         = 50; // 5 USD
    private $tiempomaxsubasta                   = 86400000; // 24 horas
    //private $tiempomaxsubasta                   = 45000; // 24 horas
    private $porcetajexminxrecaudar             = 0.4;

    private $entradaxxubastasxnormales          = 0.029; // porcentaje entrada subastas normales
    private $porcentajexrestarxnormal           = 0.1; // porcentaje entrada subastas normales
    private $addressxrecibextickets             = [
        'uid'               => 1333,
        'empresa'           => 0,
        'address_Nasbigold' => 'aa97273806290a2ef074a95ef71d1a7b', 
        'address_Nasbiblue' => 'c5551f7d44f2d72f98f5976efe96bfdf',
    ];

    public function __construct(){
        $nasbifunciones = new Nasbifunciones();
        $result         = $nasbifunciones->getCuentaBanco();
        unset($nasbifunciones);
        
        $addressxrecibextickets['uid']               = $result['uid'];
        $addressxrecibextickets['empresa']           = $result['empresa'];
        $addressxrecibextickets['address_Nasbigold'] = $result['address_Nasbigold'];
        $addressxrecibextickets['address_Nasbiblue'] = $result['address_Nasbiblue'];
    }

    public function subirFotosClonacion(Array &$fotos, Int $fecha, Int $id) {
        parent::conectar();
        $imgconcat = null;
        $validar = true;

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/$id";
        if (!file_exists($nombre_fichero)) {
            mkdir($nombre_fichero, 0777, true);
        }
        
        foreach ($fotos as $x => &$foto) {
            $pos = strrpos($foto['img'], ';base64,');
            if ($pos === false) { // nota: tres signos de igual
                $url = $foto['img'];
            }else{
                $imgconcat = $fecha.'_'.$x;
                $url = $this->uploadImagen([
                    'img' => $foto['img'],
                    'ruta' => '/imagenes/publicaciones/'.$id.'/'.$imgconcat.'.png',
                ]);
                $foto['img'] = $url;
            }

            $insertarfoto = "INSERT INTO productos_fotos(id_publicacion,foto, estado, fecha_creacion, fecha_actualizacion) VALUES ('$id', '$url', '1', '$fecha', '$fecha')";
            if(!$insertarfoto) $validar = false;
            parent::query($insertarfoto);
        }
        parent::cerrar();
    }

    public function convertirImgsBase64( Array &$imagenes ) {

        foreach($imagenes as $index => &$data_img) {
            $url = $data_img['img'];
            $imagedata       = file_get_contents( $url );
            $type            = pathinfo($url, PATHINFO_EXTENSION);
            $base64          = "data:image/" . $type . ";base64," . base64_encode($imagedata);
            $data_img['img'] = $base64;
        }

    }

    public function obtenerProducto( $id_producto, $revision = false) {
        $tabla = "buyinbig.productos";

        if($revision){
            $tabla = "buyinbig.productos_revision";
        }

        parent::conectar();

        $SQL = "SELECT * FROM $tabla WHERE id = '$id_producto'";
        $producto = parent::consultaTodo($SQL);

        parent::cerrar();

        return $producto;
    }

    public function insertarClonacion($data, $fecha, $revision = false) {

        parent::conectar();

        $insertarSQL =
        "INSERT INTO productos_revision(
            uid,
            empresa,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            foto_portada,
            url_video,
            portada_video,
            fecha_creacion,
            genero,
            tiene_colores_tallas,
            id_productos_revision_estados,
            tipo_envio_gratuito,
            id_tiempo_garantia,
            num_garantia
        )
        VALUES(
            '$data[uid]',
            '$data[empresa]',
            '$data[tipo]',
            '$data[tipoSubasta]',
            '$data[producto]',
            '$data[marca]',
            '$data[modelo]',
            '$data[titulo]',
            '$data[descripcion]',
            '$data[categoria]',
            '$data[subcategoria]',
            '$data[condicion_producto]',
            '$data[garantia]',
            '$data[estado]',
            '$fecha',
            '$fecha',
            '$data[cantidad]',
            '$data[moneda_local]',
            '$data[precio]',
            '$data[precio_usd]',
            '$data[precio_publicacion]',
            '$data[precio_usd_publicacion]',
            '$data[oferta]',
            '$data[porcentaje_oferta]',
            '$data[porcentaje_tax]',
            '$data[exposicion]',
            0,
            '$data[envio]',
            '$data[id_direccion]',
            '$data[pais]',
            '$data[departamento]',
            '$data[ciudad]',
            '$data[latitud]',
            '$data[longitud]',
            '$data[codigo_postal]',
            '$data[direccion]',
            '$data[keywords]',
            0,
            '$data[foto_portada]',
            '$data[url_video]',
            '$data[portada_video]',
            '$fecha',
            '$data[genero]',
            '$data[tiene_colores_tallas]',
            0,
            '$data[tipo_envio_gratuito]',
            '$data[id_tiempo_garantia]',
            '$data[num_garantia]'
        )";

        $id_insertado = parent::queryRegistro($insertarSQL);
        parent::cerrar();

        $this->subirFotosClonacion( $data['imagenes'], $fecha , $id_insertado);
        $url_portada = $data['imagenes'][0]['img'];
        $data['foto_portada'] = $url_portada;

        parent::conectar();

        $SQL = "UPDATE buyinbig.productos_revision SET foto_portada = '$url_portada' WHERE id = '$id_insertado'";
        parent::query($SQL);

        parent::cerrar();
        
        if( !$revision ){
            parent::conectar(); 

            $insertar_permisos_SQL = 
            "INSERT INTO productos(
                id,
                uid,
                empresa,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                foto_portada,
                url_video,
                portada_video,
                fecha_creacion,
                genero,
                tiene_colores_tallas,
                tipo_envio_gratuito,
                id_tiempo_garantia,
                num_garantia
            )
            VALUES(
                '$id_insertado',
                '$data[uid]',
                '$data[empresa]',
                '$data[tipo]',
                '$data[tipoSubasta]',
                '$data[producto]',
                '$data[marca]',
                '$data[modelo]',
                '$data[titulo]',
                '$data[descripcion]',
                '$data[categoria]',
                '$data[subcategoria]',
                '$data[condicion_producto]',
                '$data[garantia]',
                '$data[estado]',
                '$fecha',
                '$fecha',
                '$data[cantidad]',
                '$data[moneda_local]',
                '$data[precio]',
                '$data[precio_usd]',
                '$data[precio_publicacion]',
                '$data[precio_usd_publicacion]',
                '$data[oferta]',
                '$data[porcentaje_oferta]',
                '$data[porcentaje_tax]',
                '$data[exposicion]',
                0,
                '$data[envio]',
                '$data[id_direccion]',
                '$data[pais]',
                '$data[departamento]',
                '$data[ciudad]',
                '$data[latitud]',
                '$data[longitud]',
                '$data[codigo_postal]',
                '$data[direccion]',
                '$data[keywords]',
                0,
                '$data[foto_portada]',
                '$data[url_video]',
                '$data[portada_video]',
                '$fecha',
                '$data[genero]',
                '$data[tiene_colores_tallas]',                    
                '$data[tipo_envio_gratuito]',
                '$data[id_tiempo_garantia]',
                '$data[num_garantia]'
            )";

            parent::queryRegistro($insertar_permisos_SQL);

            parent::query("DELETE FROM productos_revision WHERE id = '$id_insertado'");

            parent::cerrar();
            
        }

        $detalle_envio = array( 
            'alto'             => $data['detalle_envio']['alto'],
            'largo'            => $data['detalle_envio']['largo'],
            'ancho'            => $data['detalle_envio']['ancho'],
            'peso'             => $data['detalle_envio']['peso'],
            'unidad_masa'      => $data['detalle_envio']['unidad_masa'],
            'unidad_distancia' => $data['detalle_envio']['unidad_distancia']
        );

        $envio = $this->insertarEnvio($detalle_envio, $fecha, $id_insertado);

        if(isset($data['url_video']) && !empty($data['url_video'])) {

            $url             = str_replace('https', 'http', $data['portada_video']);
            $imagedata       = file_get_contents( $url );
            $type            = pathinfo($url, PATHINFO_EXTENSION);
            $base64          = "data:image/" . $type . ";base64," . base64_encode($imagedata);
            $data['portada_video'] = $base64;

            $imgconcat = $fecha.'_portada_video';

            $url = $this->uploadImagen([
                'img' => $data['portada_video'],
                'ruta' => '/imagenes/publicaciones/'.$id_insertado.'/'.$imgconcat.'.png'
            ]);

            if(!$revision){
                parent::conectar();
                parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$id_insertado'");
                parent::cerrar();
            }else{
                parent::conectar();
                parent::query("UPDATE productos_revision SET portada_video = '$url' WHERE id = '$id_insertado'");
                parent::cerrar();
            }
        }
        
        if( $data['tiene_colores_tallas'] ){
            foreach($data['colores_tallas'] as $talla){
                $talla['id_producto'] = $id_insertado;
                $this->guardarProductoColoresTallas($talla);
            }
        }

        $fecha_inicio = $fecha + $this->tiempomaxsubasta;//24Hrs

        if( $data['datos_subasta']['tipo'] != 6 ) {
            $fecha_inicio = $fecha + ($this->tiempomaxsubasta * 2); //48Hrs
        }

        $data['datos_subasta']['id_producto']         = $id_insertado;
        $data['datos_subasta']['fecha_actualizacion'] = $fecha;
        $data['datos_subasta']['fecha_inicio']        = $fecha_inicio;
        $data['datos_subasta']['fecha_fin']           = $fecha;
        $data['datos_subasta']['fecha_creacion']      = $fecha;

        $subasta = $data['datos_subasta'];

        parent::conectar();

        $insertarSubasta = "INSERT INTO productos_subastas
        (
            id_producto,
            moneda,
            precio,
            precio_usd,
            cantidad,
            tipo,
            estado,
            apostadores,
            inscritos,
            fecha_fin,
            fecha_creacion,
            fecha_actualizacion,
            fecha_inicio
        )
        VALUES
        (
            '$subasta[id_producto]',
            '$subasta[moneda]',
            '$subasta[precio]',
            '$subasta[precio_usd]',
            '$subasta[cantidad]',
            '$subasta[tipo]',
            '$subasta[estado]',
            '$subasta[apostadores]',
            '$subasta[inscritos]',
            '$subasta[fecha_fin]',
            '$subasta[fecha_creacion]',
            '$subasta[fecha_actualizacion]',
            '$subasta[fecha_inicio]'
        );";
        parent::query($insertarSubasta);

        parent::cerrar();
    }

    public function obtenerDetalleEnvio($id_producto){
        parent::conectar();

        $SQL = "SELECT * FROM buyinbig.productos_envio  WHERE id_producto = '$id_producto'";
        $detalle_envio = parent::consultaTodo($SQL);

        parent::cerrar();

        return $detalle_envio[0];
    }

    public function obtenerColoresTallas($id_producto) {
        parent::conectar();

        $SQL = "SELECT * FROM buyinbig.detalle_producto_colores_tallas  WHERE id_producto = '$id_producto'";
        $colores_tallas = parent::consultaTodo($SQL);

        parent::cerrar();

        return $colores_tallas;
    }

    public function obtenerDatosSubasta($id_producto) {
        parent::conectar();

        $SQL = "SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$id_producto'";
        $datos_subasta = parent::consultaTodo($SQL);

        parent::cerrar();

        return $datos_subasta[0];
    }

    public function obtenerImagenes($id_producto) {
        parent::conectar();

        $SQL = "SELECT * FROM buyinbig.productos_fotos WHERE id_publicacion = '$id_producto'";
        $imgs = parent::consultaTodo($SQL);

        parent::cerrar();

        return $imgs;
    }

    public function clonarPublicacion( Array $data ) {

        /////////////////////////////

        /* $tickets = $this->verTicketsUsuario([
            'uid'      => 5,
            'empresa'  => 1,
            'plan'     => 3,
            'cantidad' => 99999979
        ]);

        if( $tickets['status'] == 'fail' ) {
            return array( "status" => 'errTickets', "message" => "No tienes ticketes para clonar esta publicacion" );
        }

        $fecha = intval(microtime(true) * 1000);

        $tickets = $tickets['data'];

        $cobrar  = $this->cobrarTicekts([
            'tickets'  => $tickets, 
            'cantidad' => 99999979,
            'fecha'    => $fecha
        ]);

        return "quitados"; */
        
        //////////////////////////////

        if(!isset($data) || !isset($data['id_publicacion']) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => null
            );
        }

        $producto = $this->obtenerProducto($data['id_publicacion']);//Buscamos en tabla productos

        if(count($producto) <= 0) {
            $producto = $this->obtenerProducto($data['id_publicacion'], true);//Buscamos en tabla productos_revision
        }

        if(count($producto) <= 0){
            return array(
                'status'  => 'fail',
                'message' => 'El producto no existe'
            );
        }

        $producto = $producto[0];

        if( $producto['cantidad'] == 0 ) {
            $producto['cantidad'] = 1;
        }

        if( $producto['tipoSubasta'] == 0 ) {
            return array(
                'status'  => 'fail',
                'message' => 'El producto no es una subasta'
            );
        }

        ////////////////////

        $fecha = intval(microtime(true) * 1000);
        
        if( $producto['tipoSubasta'] != 6 ) {//COBRO DE TICKET SOLO PARA SUBASTAS PREMIUM

            $tickets = $this->verTicketsUsuario([
                'uid'      => $producto['uid'],
                'empresa'  => $producto['empresa'],
                'plan'     => $producto['tipoSubasta'],
                'cantidad' => 1
            ]);
    
            if( $tickets['status'] == 'fail' ) {
                return array( "status" => 'errTickets', "message" => "No tienes ticketes para clonar esta publicacion" );
            }
    
            $tickets = $tickets['data'];

            $cantidad = 1;

            if( $producto['tipoSubasta'] == 5 ) {
                $cantidad = 2;
            }

            $cobrar  = $this->cobrarTicekts([
                'tickets'  => $tickets, 
                'cantidad' => $cantidad,
                'fecha'    => $fecha
            ]);

        }

        ////////////////////

        $producto['titulo'] = '(CLONE) - '.$producto['titulo'];
        $producto['estado'] = 2;

        $datos_subasta = $this->obtenerDatosSubasta($data['id_publicacion']);

        $datos_subasta['estado']      = 1;
        $datos_subasta['inscritos']   = 0;
        $datos_subasta['inscritos_activacion_subasta'] = 0;
        
        $producto['datos_subasta']    = $datos_subasta;

        $detalle_envio = $this->obtenerDetalleEnvio($data['id_publicacion']);

        $producto['detalle_envio'] = $detalle_envio;

        $imagenes = $this->obtenerImagenes($data['id_publicacion']);

        $index = 0;

        $imagenes = array_map(function( $img ) use( &$index ) {
                        $img['id'] = $index++;
                        $img['img'] = str_replace( 'https' ,'http', $img['foto'] );

                        unset($img['id_publicacion']);
                        unset($img['estado']);
                        unset($img['fecha_creacion']);
                        unset($img['fecha_actualizacion']);
                        unset($img['foto']);
                        
                        return $img;
                    }, $imagenes );
                    
        $this->convertirImgsBase64( $imagenes );

        $producto['imagenes'] = $imagenes;

        // return $imagenes;

        if( intval($producto['tiene_colores_tallas']) ){

            $colores_tallas = $this->obtenerColoresTallas( $data['id_publicacion'] );
            $producto['colores_tallas'] = $colores_tallas;
        }

        if( isset($producto['id_productos_revision_estados']) ){//revision
            $this->insertarClonacion($producto, $fecha, true);
            // return "revision";
        }else{//publicado
            $this->insertarClonacion($producto, $fecha);
            // return "publicado";
        }

        return array(
            'status'  => 'success',
            'message' => 'clonacion exitosa'
        );
    }

    // DEPRECADA. --- VER publicarVersion2
    public function publicar( Array $data ){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => null
            );
        }

        $request        = null;
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha    = intval(microtime(true)*1000);
        $data     = $this->mapeoVender($data);
        $producto = $data;

        $envio = $data['direccion_envio'];
        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});
        $textfullcompleto = $producto['producto'].' '.$producto['marca'].' '.$producto['modelo'].' '.$producto['titulo'].' '.$producto['categoria']['CategoryName'].' '.$producto['subcategoria']['CategoryName'];
        
        $producto['keywords'] = implode(',', $this->extractKeyWords($textfullcompleto));
        $producto['categoria'] = $producto['categoria']['CategoryID'];
        $producto['subcategoria'] = $producto['subcategoria']['CategoryID'];
        
        $empresa = 0;
        if($producto['empresa'] == true) $empresa = 1;

        $precio_usd = null;
        $array_monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('https://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);

                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);

                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);

                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
            }else{
                $precio_usd = $data['precio'];
            }
        }

        if($precio_usd == null) {
            return array(
                'status' => 'fail',
                'message'=> 'la moneda en la que desea publicar no es valida',
                'data' => null
            );
        }

        // return "paso #5: " . $precio_usd;
        // if( $precio_usd < $this->montoxminimoxpublicar ) {
        //     return array(
        //         'status' => 'errorMontoMinimoPublicar',
        //         'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                
        //         'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
        //         'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
        //         'costo_dolar' => $array_monedas_local['costo_dolar'],
        //         'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
        //         'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'], 2),
        //         'symbolMonedaLocal' => $array_monedas_local['code'],
        //         'data' => null
        //     );
        // }
        if ( floatval($data['porcentaje_oferta']) < 0 || floatval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd;// - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],2),
                    'symbolMonedaLocal' => $array_monedas_local['code'],
                    'data' => null
                );
                
            }
        }


        $producto['precio_usd']   = $precio_usd;
        $producto['moneda_local'] = $monedas_local;

        // return $precio_usd;

        $cobrar = null;

        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1) {
            $producto['subasta']['precio_usd'] = $precio_usd;
            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            $producto['subasta']['precio'] = $this->truncNumber(($producto['subasta']['precio_usd'] / $precios[$producto['subasta']['moneda']]), 6);
            unset($nodo);

            $validarsubasta = $this->validarTipoSubasta(
                $producto['subasta'],
                $data['iso_code_2']
            );

            if($validarsubasta['status'] == 'fail') return $validarsubasta;
            $producto['subasta']['datos_subasta'] = $validarsubasta['data'];

            // return $producto['subasta'];

            if ($producto['subasta']['datos_subasta']['id'] != 6) {
                
                $tickets = $this->verTicketsUsuario([
                    'uid'      => $producto['uid'],
                    'empresa'  => $empresa,
                    'plan'     => $producto['subasta']['datos_subasta']['id'],
                    'cantidad' => 1
                ]);

                if( $tickets['status'] == 'fail' ) return $tickets;
                $tickets = $tickets['data'];

                $cantidad = 1;

                if( $producto['subasta']['datos_subasta']['id'] == 5 ) {
                    $cantidad = 2;
                }
    
                $cobrar = $this->cobrarTicekts([
                    'tickets'  => $tickets, 
                    'cantidad' => $cantidad,
                    'fecha'    => $fecha
                ]);

                if($cobrar['status'] != 'success') return $cobrar;
            }
        }

        $tipoSubasta = 0;
        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
            $tipoSubasta = intval($producto['subasta']['tipo']);
        }
        $genero = 3;
        if(isset($data['genero'])){
            $genero = $data['genero'];
        }

        $producto['precio']            = floatval(''. $producto['precio'] );
        $producto['porcentaje_oferta'] = floatval(''. $producto['porcentaje_oferta'] );
        $producto['porcentaje_tax']    = floatval(''. $producto['porcentaje_tax'] );
        
        $precioSinAranceles = 0;

        if ( $producto['porcentaje_oferta'] > 0 ) {
            $precioSinAranceles = $producto['precio'] - ( $producto['precio'] * ($producto['porcentaje_oferta'] / 100) );
            $precioSinAranceles = $precioSinAranceles - ( $precioSinAranceles * ($producto['porcentaje_tax'] / 100) );
        }else{
            $precioSinAranceles = $producto['precio'];
            $precioSinAranceles = $precioSinAranceles - ( $precioSinAranceles * ($producto['porcentaje_tax'] / 100) );
        }
        if ( $precioSinAranceles <= $this->montoxminimoxpublicarxmonedaxlocal ) {
            return array(
                'status' => 'errorMontoMinimoPublicar',
                
                'message'=> 'El monto minimo a publicar con descuentos y sin IVA debe ser superior a: ' . $this->montoxminimoxpublicarxmonedaxlocal,
                
                // 'descuento' => $producto['porcentaje_oferta'],

                // 'iva' => $producto['porcentaje_tax'],
                
                // 'valorConDescuento' => ($producto['precio'] - ( $producto['precio'] * ($producto['porcentaje_oferta'] / 100) )),
                
                'valorConDescuentoSinIva' => $precioSinAranceles,
                
            );
        }

        parent::conectar();
        $insertarproducto = "INSERT INTO productos(
            uid,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            empresa,
            url_video,
            genero,
            tiempo_estimado_envio_num,
            tiempo_estimado_envio_unidad
        )
        VALUES(
            '$producto[uid]',
            '$producto[tipo]',
            '$tipoSubasta',
            '$producto[producto]',
            '$producto[marca]',
            '$producto[modelo]',
            '$producto[titulo]',
            '$producto[descripcion]',
            '$producto[categoria]',
            '$producto[subcategoria]',
            '$producto[condicion_producto]',
            '$producto[garantia]',
            '1',
            '$fecha',
            '$fecha',
            '$fecha',
            '$producto[cantidad]',
            '$producto[moneda_local]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[oferta]',
            '$producto[porcentaje_oferta]',
            '$producto[porcentaje_tax]',
            '$producto[exposicion]',
            '$producto[cantidad_exposicion]',
            '$producto[envio]',
            '$envio[id]',
            '$envio[pais]',
            '$envio[departamento]',
            '$envio[ciudad]',
            '$envio[latitud]',
            '$envio[longitud]',
            '$envio[codigo_postal]',
            '$envio[direccion]',
            '$producto[keywords]',
            0,
            $empresa,
            '$producto[url_video]',
            '$genero',
            '$producto[tiempo_estimado_envio_num]',
            '$producto[tiempo_estimado_envio_unidad]',
        )";

        $productoquery = parent::queryRegistro($insertarproducto);
        parent::cerrar();
        if ($productoquery) {
            $fotos = $this->subirFotosProducto($fotos, $fecha, $productoquery);
            if(isset($data['url_video']) && !empty($data['url_video'])) {
                $imgconcat = $fecha.'_portada_video';
                $url = $this->uploadImagen([
                    'img' => $data['portada_video'],
                    'ruta' => '/imagenes/publicaciones/'.$productoquery.'/'.$imgconcat.'.png',
                ]);
                parent::conectar();
                parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$productoquery'");
                parent::cerrar();
            }
            if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
                if(isset($cobrar)) $this->actualizarHistoricoTickets($cobrar['data'], $productoquery);
                $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
            }

            $envio = $this->insertarEnvio($producto['detalle_envio'], $fecha, $productoquery);

            if ( intval( $empresa ) == 1 ) {
                // Evaluamos si es la primera publicación.
                $this->validarPrimerArticulo( $producto );
            }else{
                $this->enviodecorreodepublicacion_product( $producto );
               
            }

            $request = array(
                'status' => 'success',
                'message'=> 'producto creado',
                'data' => $productoquery
            );
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no se pudo crear el producto',
                'data' => null
            );
        }
        return $request;
    }

    public function misPublicaciones(Array $data)
    {   
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;


        $palabra = "%";
        if( isset($data['palabra']) && $data['palabra'] != "" && $data['palabra'] != null){
            $palabra = $data['palabra'] . '%';
        }

        // Discriminar productos segun la data del filtro
        // tipoPublicacion == 0, TODO
        // tipoPublicacion == 1, PRODUCTOS NORMALES
        // tipoPublicacion == 2, SUBASTAS (NORMALES Y (BRONZE,...))
        
        $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
        $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";
        if( isset($data['tipoPublicacion']) ){
            if ( intval($data['tipoPublicacion']) == 0 ) {
                $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";

            }else if ( intval($data['tipoPublicacion']) == 1 ) {
                $tipoPublicacion = " AND p.tipoSubasta = 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta = 0 ";

            }else if ( intval($data['tipoPublicacion']) == 2 ) {
                $tipoPublicacion = " AND p.tipoSubasta > 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta > 0 ";

            }else {
                $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";

            }
        }

        $estado = "AND p.estado >= 0";
        $estado2 = "AND pr.estado >= 0";
        if(isset($data['estado'])){ 
            $estado = "AND p.estado = '$data[estado]'"; 
            $estado2 = "AND pr.estado = '$data[estado]'";
        }

        $exposicion = "";
        $exposicion2 = "";
        if(isset($data['exposicion'])){
            $exposicion = "AND p.exposicion = '$data[exposicion]'";
            $exposicion2 = "AND pr.exposicion = '$data[exposicion]'";
        }

        parent::conectar();
        $selectxpublicaciones = "";
        $revisionConsulta = "";
        if(!isset($data['estado']) || $data['estado'] == 5){

            $revisionConsulta = 
            " UNION(
                SELECT
                    pr.estado estado2,
                    pr.*,
                    pr.precio AS precio_local,
                    (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas,
                    ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps ON (ps.id_producto = pr.id)
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' AND pr.titulo LIKE '$palabra'  $tipoPublicacion2 $exposicion2 AND pr.id NOT IN (SELECT pro.id FROM productos pro) AND pr.id_productos_revision_estados != 1
            ) ";

            $selectxpublicaciones =  
            "SELECT * FROM (
                SELECT *,(@row_number:=@row_number+1) AS num FROM(
                    SELECT
                        p.estado estado2,
                        p.*,
                        pr.id_productos_revision_estados as revision_status,
                        pr.motivo,
                        p.precio AS precio_local,
                        (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas,
                        ps.inscritos
                    FROM productos p 
                    LEFT JOIN productos_revision pr ON (pr.id = p.id)
                    LEFT JOIN productos_subastas ps ON (ps.id_producto = p.id)
                    JOIN (SELECT @row_number := 0) r 
                    WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra' $tipoPublicacion $estado $exposicion " 
                    .$revisionConsulta.
                    " ORDER BY fecha_creacion DESC
                ) AS datos ORDER BY datos.fecha_creacion DESC 
              ) AS datos2  WHERE datos2.num BETWEEN '$desde' AND '$hasta'";

        }else if(isset($data['estado']) && ($data['estado'] != 3 || $data['estado'] != "3") ){
            
            $selectxpublicaciones = 
            "SELECT * FROM (
                SELECT *,(@row_number:=@row_number+1) AS num FROM(
                    SELECT 
                        p.estado estado2,
                        p.*,
                        p.precio AS precio_local,
                        (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas,
                        ps.inscritos
                    FROM productos p 
                    LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                    JOIN (SELECT @row_number := 0) r 
                    WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra'  $tipoPublicacion $estado $exposicion
                    ORDER BY p.fecha_creacion DESC
                ) AS datos  ORDER BY datos.fecha_creacion DESC
            ) as datos2 WHERE datos2.num BETWEEN '$desde' AND '$hasta';";

        }else if(isset($data['estado']) && ($data['estado'] == 3 || $data['estado'] == "3")){
            
            $selectxpublicaciones = 
            "SELECT * FROM (
                SELECT *,(@row_number:=@row_number+1) AS num FROM(
                    SELECT 
                        pr.estado estado2,
                        pr.*,
                        pr.precio AS precio_local,
                        pr.id_productos_revision_estados as revision_status,
                        (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas,
                        ps.inscritos
                    FROM productos_revision pr 
                    LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                    JOIN (SELECT @row_number := 0) r 
                    WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' AND pr.titulo LIKE '$palabra' $tipoPublicacion2 AND pr.id_productos_revision_estados = 0 $exposicion2
                    ORDER BY pr.fecha_creacion DESC
                ) AS  datos  ORDER BY datos.fecha_creacion DESC
            ) AS  datos2 WHERE datos2.num BETWEEN '$desde' AND '$hasta';";
        }

        
        // var_dump($selectxpublicaciones);
        $publicaciones = parent::consultaTodo($selectxpublicaciones);

        if(count($publicaciones) < 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron publicaciones', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = "";
        $revisionConsulta2 = "";
        if(!isset($data['estado']) || $data['estado'] == 5){
            $revisionConsulta2 = 
            " UNION(
                SELECT 
                    pr.estado estado2,
                    pr.*,
                    pr.precio AS precio_local,
                    (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas,
                    ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' AND pr.titulo LIKE '$palabra'  $tipoPublicacion2 $estado2 $exposicion2 AND pr.id NOT IN (Select pro.id from productos pro)
              ) ";
              $selecttodos = 
              "SELECT count( * ) as contar FROM(
                    SELECT 
                        p.estado estado2,
                        p.*,
                        pr. id_productos_revision_estados as revision_status,
                        pr.motivo,
                        p.precio AS precio_local,
                        (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas,
                        ps.inscritos
                    FROM productos p 
                    LEFT JOIN productos_revision pr on pr.id = p.id
                    LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                    JOIN (SELECT @row_number := 0) r 
                    WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra'  $tipoPublicacion $estado $exposicion "
                    .$revisionConsulta2.
                    " ORDER BY fecha_creacion DESC)as contar";
        }else if(isset($data['estado']) && ($data['estado'] != 3 || $data['estado'] != "3") ){
            $selecttodos = 
            "SELECT count(*) as contar FROM (
                SELECT 
                    p.estado estado2,
                    p.*,
                    p.precio AS precio_local,
                    (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas,
                    ps.inscritos
                FROM productos p 
                LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra'  $tipoPublicacion $estado $exposicion
                ORDER BY p.fecha_creacion DESC) as contador";
        }else if(isset($data['estado']) && ($data['estado'] == 3 || $data['estado'] == "3")){
            $selecttodos = 
            "SELECT count( * ) as contar FROM(
                SELECT 
                    pr.estado estado2,
                    pr.*,
                    pr.precio AS precio_local,
                    (SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas,
                    ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' AND pr.titulo LIKE '$palabra'  $tipoPublicacion2 AND pr.id_productos_revision_estados = 0  $exposicion2
            ) as contador ";
        }

        // var_dump($selecttodos);
        $todoslosproductos = parent::consultaTodo($selecttodos);

        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $publicaciones = $this->mapPublicaciones($publicaciones);
        
        parent::cerrar();

        return array(
            'status'          => 'success',
            'message'         => 'mis publicaciones',
            'pagina'          => $pagina,
            'total_paginas'   =>$totalpaginas,
            'productos'       => count($publicaciones),
            'total_productos' => $todoslosproductos,
            'data'            => $publicaciones
        );
    }

    public function misSubastas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        $pagina = floatval($data['pagina']);
        $numpagina = 5;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $palabra = "%";
        if( isset($data['palabra']) && $data['palabra'] != "" && $data['palabra'] != null){
            $palabra = $data['palabra'] . '%';
        }

        // $select_precio = $this->selectMonedaLocalUser($data);

        $where = "";
        parent::conectar();
        $selecxmisxsubastas = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT 
                    ps.*, 
                    p.producto,
                    p.marca,
                    p.modelo,
                    p.titulo,
                    p.descripcion,
                    p.foto_portada,
                    p.exposicion,
                    pse.descripcion AS estado_descripcion,
                    pst.descripcion AS tipo_descripcion,
                    p.tipoSubasta,
                    p.oferta,
                    p.porcentaje_oferta,
                    p.precio AS precioProducto, 
                    p.precio AS precio_local_user, 
                    p.moneda_local AS moneda_local_user, 
                    p.moneda_local,
                    p.pais
                FROM productos_subastas ps
                INNER JOIN productos p ON ps.id_producto = p.id
                LEFT JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                LEFT JOIN productos_subastas_estado pse ON ps.estado = pse.id
                JOIN (SELECT @row_number := 0) r 
                WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra' AND p.estado != '0'
                ORDER BY ps.fecha_actualizacion DESC
                )as datos 
            ORDER BY fecha_actualizacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        

        $productos = parent::consultaTodo($selecxmisxsubastas);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron subastas productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $productos = $this->mapProductosSubastas($productos);
        $selecttodos = 
        "SELECT
            COUNT(ps.id) AS contar 
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id 
        WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.titulo LIKE '$palabra' AND p.estado != '0'
        ORDER BY ps.fecha_actualizacion DESC;";

        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'subastas productos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($productos), 'total_productos' => $todoslosproductos, 'data' => $productos);

    }

    public function pausarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $data['actualizar'] = "estado = '2',";

        //para enviar correo de pausa
        $data['tipo_edicion_correo']= 20;  
        //para enviar correo de pausa
      

        return $this->actualizarPublicacion($data);
    }

    public function activarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $data['actualizar'] = "estado = '1',";
        return $this->actualizarPublicacion($data);
    }

    public function eliminarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);


        parent::conectar();
        $producto = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE id = $data[id];");
        parent::cerrar();
        if ( count( $producto ) == 0 ) {
            return array(
                'status'  => 'NoExisteProducto',
                'message' => 'No se hallo el articulo'
            );
        }
        $getType = $this->getPublicationType($data['id']);
        if($getType == "subasta"){

            $inscritos = $this->getAuctionUsers($data['id']);
            $total = $inscritos->fetch_assoc();

            if($total["inscritos"] > 0){
                return array(
                    'status' => 'errorDeleteAuction',
                    'message'=> 'No puedes eliminar una publicacion con inscritos',
                    'data' => null
                );
            }
            else{
                //Editando por aquí.
                $fecha = intval(microtime(true)*1000);
                $data['fecha'] = $fecha;
                $data['actualizar'] = " estado = '0', ";
                    //para enviar correo de elimino
                    $data['tipo_edicion_correo']= 21;  
                     //para enviar correo de elimino
      
                return $this->actualizarPublicacion($data);
            }
        }
        else{
            $fecha = intval(microtime(true)*1000);
            $data['fecha'] = $fecha;
            $data['actualizar'] = " estado = '0', ";
                //para enviar correo de elimino
                $data['tipo_edicion_correo']= 21;  
                //para enviar correo de elimino
            return $this->actualizarPublicacion($data);
        }
    }

    public function productoId(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxproducto = "SELECT p.*, p.precio AS precio_local
        , (SELECT AVG(cv.promedio) as general_prom FROM calificacion_vendedor cv WHERE cv.id_producto = p.id) AS calificacion
        FROM productos p
        WHERE p.id = '$data[id]' AND p.uid = '$data[uid]' AND p.empresa = '$data[empresa]'";
        $producto = parent::consultaTodo($selectxproducto);
        parent::cerrar();
        if(count($producto) <= 0) return array('status' => 'fail', 'message'=> 'no producto', 'data' => null);
        
        $producto = $this->mapProductos($producto)[0];
        return array('status' => 'success', 'message'=> 'producto', 'data' => $producto);
    }

    public function fotosproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxfotosproducto = "SELECT pf.*
        FROM productos_fotos pf
        WHERE pf.id_publicacion = '$data[id]' AND pf.estado = '1'";
        $fotos = parent::consultaTodo($selectxfotosproducto);
        parent::cerrar();
        if(count($fotos) <= 0) return array('status' => 'fail', 'message'=> 'no fotos prodcuto', 'data' => null);
        $fotos = $this->mapFotosProductos($fotos);
        return array('status' => 'success', 'message'=> 'fotos prodcuto', 'data' => $fotos);
    }

    public function editarPublicacion(Array $data){
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos 1', 'data' => null);

        //VERIFICAR SI ES SUBASTA Y TIENE USUARIOS INSCRITOS
        $SQL = 
        "SELECT
            psi.id_subasta,
            ps.estado
        FROM productos AS p
        JOIN productos_subastas AS ps ON(p.id  = ps.id_producto)
        JOIN productos_subastas_inscritos AS psi ON(ps.id = psi.id_subasta AND p.id  = '$data[id]')";

        parent::conectar();
        $res = parent::consultaTodo($SQL);
        parent::cerrar();

        if( count( $res ) > 0 ) {
            //NO PUEDE MODIFICAR, TIENE INSCRITOS
            $res = current( $res );

            if( $res['estado'] == 4 ) {//SUBASTA FINALIZADA Y USUARIOS INSCRITOS
                return array("status" => "errSubastaFinalizada", "message" => "La subasta ha finalizado", "data" => null);
            }else{//SUBASTA CON USUARIOS INSCRITOS
                return array("status" => "errSubastaConInscritos", "message" => "La subasta tiene usuarios inscritos", "data" => null);
            }
        }
        //FIN DE VERIFICAR SI ES SUBASTA Y TIENE USUARIOS INSCRITOS        

        $publicacion = $this->publicacionId($data);
        if($publicacion['status'] == 'fail') return $publicacion;
        $data['publicacion'] = $publicacion['data'];


        $fecha = intval(microtime(true) * 1000);
        $data['fecha'] = $fecha;

        if($data['tipo'] == 1) return $this->editarTituloPublicacion($data);
        if($data['tipo'] == 2) return $this->editarCategoriaPublicacion($data);
        if($data['tipo'] == 3) return $this->editarDescripcionPublicacion($data);
        if($data['tipo'] == 4) return $this->editarCondicionPublicacion($data);
        if($data['tipo'] == 5) return $this->editarFotosPublicacion($data);
        if($data['tipo'] == 6) return $this->editarMarcaPublicacion($data);
        if($data['tipo'] == 7) return $this->editarPrecioPublicacion($data);
        if($data['tipo'] == 8) return $this->editarEnvioPublicacion($data);
        if($data['tipo'] == 9) return $this->editarModeloPublicacion($data);
        if($data['tipo'] == 10) return $this->editarDireccionPublicacion($data);
        if($data['tipo'] == 11) return $this->editarCantidadPublicacion($data);
        if($data['tipo'] == 12) return $this->editarTipoPublicacion($data);
        if($data['tipo'] == 13) return $this->editarExposicionPublicacion($data);
        if($data['tipo'] == 14) return $this->editarVideoPublicacion($data);
        if($data['tipo'] == 15) return $this->editarNombreProducto($data);
        if($data['tipo'] == 16) return $this->editarEstimadosProducto($data);
        if($data['tipo'] == 17) return $this->editarEstadoRevicionProducto($data);


        return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);
    }

    function editarNombreProducto(Array $data){

        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['producto'])) return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);

        $nombre =addslashes($data['producto']);
        $publicacion = $data['publicacion'];
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
    
        $data['actualizar'] = "producto = '$nombre',";
        return $this->actualizarPublicacion($data);

    }

    function editarEstimadosProducto(Array $data){

        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tiempo_estimado_envio_num']) || !isset($data['tiempo_estimado_envio_unidad'])) return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);

        $tiempoEstimadoEnvioNum = $data['tiempo_estimado_envio_num'];
        $tiempoEstimadoEnvioUnidad = $data['tiempo_estimado_envio_unidad'];
        $publicacion = $data['publicacion'];
    
        $data['actualizar'] = "tiempo_estimado_envio_num = '$tiempoEstimadoEnvioNum', tiempo_estimado_envio_unidad = '$tiempoEstimadoEnvioUnidad',";
        return $this->actualizarPublicacion($data);

    }

    function editarEstadoRevicionProducto(Array $data){

        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);

        /* $nombre =addslashes($data['producto']);
        $publicacion = $data['publicacion']; */
        
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $data['actualizar'] = "id_productos_revision_estados = '0',";
        return $this->actualizarPublicacion($data);

    }

    public function validarSubastaMonedaLocal(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['iso_code_2']) || !isset($data['precio']) || !isset($data['cantidad'])) {

            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => null,
                'datos'   => 'uid: ' . $data['uid'] . ' - ' . 'empresa: ' . $data['empresa'] . ' - ' . 'iso_code_2: ' . $data['iso_code_2'] . ' - ' . 'precio: ' . $data['precio'] . ' - ' . 'cantidad: ' . $data['cantidad']
            );
        }

        $precio_usd = null;
        if(isset($data['iso_code_2'])){
            if($data['iso_code_2'] == 'US'){
                return $this->validarTipoSubasta(
                    [
                        'precio_usd' => $data['precio']
                    ],
                    $data['iso_code_2']
                ); 
            }
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    $precio_usd = $data['precio'] / $monedas_local['costo_dolar'];
                }
            }
        }

        
        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'isocode2 invalido', 'data' => null);
        
        return $this->validarTipoSubasta(
            [
                'precio_usd' => ceil( $this->truncNumber($precio_usd, 2) )
            ],
            $data['iso_code_2']
        );
    }

    function publicacionId(Array $data)
    {
        parent::conectar();
        $selectxpublicacion = 
        "SELECT p.*, p.precio AS precio_local, ps.inscritos, ps.estado AS estado_subasta
        FROM productos p
        LEFT JOIN productos_subastas ps ON p.id = ps.id_producto
        WHERE p.id = '$data[id]' AND p.uid = '$data[uid]' AND p.empresa = '$data[empresa]';";

        $publicacion = parent::consultaTodo($selectxpublicacion);
        parent::cerrar();
        if(!$publicacion){
            // parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron la publicacion', 'data' => null);
        } 
        // parent::conectar();
        $publicacion = $this->mapPublicaciones($publicacion, true);
        // parent::cerrar();
        return array('status' => 'success', 'message'=> 'mi publicacion', 'data' => $publicacion[0]);
    }

    function publicacionRevisionId(Array $data)
    {
        parent::conectar();
        $selectxpublicacion = 
        "SELECT p.*, p.precio AS precio_local FROM productos_revision p
        WHERE p.id = '$data[id]' AND p.uid = '$data[uid]' AND p.empresa = '$data[empresa]';";

        $publicacion = parent::consultaTodo($selectxpublicacion);
        parent::cerrar();
        if(!$publicacion){
            // parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron la publicacion', 'data' => null);
        } 
        // parent::conectar();
        $publicacion = $this->mapPublicaciones($publicacion, true);
        // parent::cerrar();
        return array('status' => 'success', 'message'=> 'mi publicacion', 'data' => $publicacion[0]);
    }

    function subirFotosProducto(Array $fotos, Int $fecha, Int $id)
    {

        parent::conectar();
        $imgconcat = null;
        $validar = true;
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/$id";
        if (!file_exists($nombre_fichero)) {
            mkdir($nombre_fichero, 0777, true);
        }
        
        foreach ($fotos as $x => $foto) {
            $pos = strrpos($foto['img'], ';base64,');
            if ($pos === false) { // nota: tres signos de igual
                $url = $foto['img'];
            }else{
                $imgconcat = $fecha.'_'.$x;
                $url = $this->uploadImagen([
                    'img' => $foto['img'],
                    'ruta' => '/imagenes/publicaciones/'.$id.'/'.$imgconcat.'.png',
                ]);

            }
            $insertarfoto = "INSERT INTO productos_fotos(id_publicacion,foto, estado, fecha_creacion, fecha_actualizacion) VALUES ('$id', '$url', '1', '$fecha', '$fecha')";
            if(!$insertarfoto) $validar = false;
            if($x == 0) {
                
                $p_revision = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$id'");
                if($p_revision && $p_revision[0]['id_productos_revision_estados'] == 0){
                    parent::query("UPDATE productos_revision SET foto_portada = '$url' WHERE id = '$id'");  
                }else{
                    parent::query("UPDATE productos SET foto_portada = '$url' WHERE id = '$id'");
                }
                // parent::query("UPDATE productos SET foto_portada = '$url' WHERE id = '$id'");
            }
            parent::query($insertarfoto);
        }
        parent::cerrar();
        if(!$validar) return array('status' => 'fail', 'message'=> 'fotos no actualizadas', 'data' => null);
        // $notificado = NULL;
        parent::conectar();
        // parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
        // parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
        $notificado = parent::consultaTodo("SELECT * FROM buyinbig.pedir_fotos WHERE id_producto = '$id'; ");
        
        $producto = parent::consultaTodo(" select * from productos where id = '$id';");
        parent::cerrar();
        // echo $notificado[0]['uid_cliente'];
        if(count($notificado) > 0){
                $notificacion = new Notificaciones();
                foreach($notificado as $enviar_notif){
                    if($enviar_notif["notificado"] == 0){
                        $notificacion->insertarNotificacion([
                            'uid' => $enviar_notif['uid_cliente'],
                            'empresa' => $enviar_notif['empresa_cliente'],
                            'text' => 'El vendedor ha publicado nuevas fotos del producto ('.$producto[0]['titulo'].') que deseas',
                            'es' => 'El vendedor ha publicado nuevas fotos del producto ('.$producto[0]['titulo'].') que deseas',
                            'en' => 'The seller has published new photos of the product ('.$producto[0]['titulo'].') you want',
                            'keyjson' => '',
                            'url' => ''
                        ]);
                        parent::conectar();
                        parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
                        parent::cerrar();
                    }
                    
                }
        }
        
        
        return array('status' => 'success', 'message'=> 'fotos actualizadas', 'data' => null);
    }

    function uploadImagen(Array $data)
    {
        $base64 = base64_decode(explode(';base64,', $data['img'])[1]);
        $filepath1 = $_SERVER['DOCUMENT_ROOT'] . $data['ruta'];
        file_put_contents($filepath1, $base64);
        chmod($filepath1, 0777);
        $url = $_SERVER['SERVER_NAME'] . $data['ruta'];
        return 'https://'.$url;
    }

    function obtenerCategoria( $precio ){
        $SQL = "SELECT * FROM productos_subastas_tipo
                pst WHERE '$precio'
                BETWEEN rango_inferior_usd
                AND rango_superior_usd";

        $categoria = parent::consultaTodo($SQL);
        
        return $categoria;
    }

    function calcularPrecioDescuento( $precio, $descuento ){
        return ( $precio - ( $precio * $descuento ) );
    }
    
    function calcularRecaudoMinimo( $precio, $precio_descuento ){
        return ( ($precio / 0.77 ) - $precio_descuento );
    }
    
    function calcularMinimoInscritos( $recaudo, $costo_ticket ){
        return round( $recaudo / $costo_ticket );
    }

    function validarTipoSubasta(Array $subasta, String $iso_code_2 = "US") {

        parent::conectar();
        $categoria = $this->obtenerCategoria( $subasta['precio_usd'] );
        parent::addLogSubastas("CATEGORIA ====> ".json_encode($categoria));
        parent::cerrar();

        if( count($categoria) <= 0 ){
            $selectxtiposx = "SELECT * FROM productos_subastas_tipo";
            parent::conectar();
            $selecttipos = parent::consultaTodo($selectxtiposx);
            parent::cerrar();

            if ( $iso_code_2 != "US") {
                $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            }
            
            $precio_local_code = "US";
            $precio_local_rango_inferior = 0;
            $precio_local_rango_superior = 0;
            $precio_local_rango_inferior_us = 0;
            $precio_local_rango_superior_us = 0;


            foreach ( $selecttipos as $x => $tipoSelect )
            {
                if ( $tipoSelect['id'] == 5 ) {
                    // ID: 1, Subastas premium
                    if ( $iso_code_2 != "US") {
                        if(count($array_monedas_locales) > 0){
                            $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                            if(count($monedas_local) > 0) {
                                $monedas_local = $monedas_local[0];
                                $precio_local_rango_superior = $this->truncNumber(($tipoSelect['rango_superior_usd'] * $monedas_local['costo_dolar']), 2);
                                $precio_local_code = $monedas_local['code'];
                                $precio_local_rango_superior_us = $tipoSelect;
                            }
                        }
                    }else{
                        $precio_local_rango_superior = $tipoSelect['rango_superior_usd'];
                    }
                }else if ( $tipoSelect['id'] == 6 ) {
                    // ID: 6, Subastas normales nasbi
                    if ( $iso_code_2 != "US") {
                        if(count($array_monedas_locales) > 0){
                            $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                            if(count($monedas_local) > 0) {
                                $monedas_local = $monedas_local[0];
                                $precio_local_rango_inferior = $this->truncNumber(($tipoSelect['rango_inferior_usd'] * $monedas_local['costo_dolar']), 2);
                                $precio_local_code = $monedas_local['code'];
                                $precio_local_rango_inferior_us = $tipoSelect;
                            }
                        }
                    }else{
                        $precio_local_rango_inferior = $tipoSelect['rango_inferior_usd'];
                    }
                }else{

                }
            }
            return array(
                'status'                           => 'errorTipoSubasta',
                'message'                          => 'tipo de subasta no valida',
                'data'                             => null,
                'valorUSD'                         => $subasta['precio_usd'],
                'iso_code_2'                       => $iso_code_2,
                
                'code'                             => $precio_local_code,
                
                'precio_local_rango_inferior'      => floatval($precio_local_rango_inferior),
                'precio_local_rango_inferior_mask' => $this->maskNumber(floatval($precio_local_rango_inferior), 2),
                
                'precio_local_rango_superior'      => floatval($precio_local_rango_superior),
                'precio_local_rango_superior_mask' => $this->maskNumber(floatval($precio_local_rango_superior), 2),
                'precio_local_rango_inferior_us'   => $precio_local_rango_inferior_us,
                'precio_local_rango_superior_us'   => $precio_local_rango_superior_us

            );
        }

        $categoria = $categoria[0];
        $tipo      = $categoria['id'];

        if($tipo != 6){//NasbiDescuentos con tickets
            
            $descuento = (10 / 100);//El mínimo de inscritos es cuando el descuento es de 10%
            # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRADAS"
            $precio_descuento  = $this->calcularPrecioDescuento( $subasta['precio_usd'], $descuento );
            $recaudar_entradas = $this->calcularRecaudoMinimo( $subasta['precio_usd'], $precio_descuento );                      
            #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
            $apostadores       = $this->calcularMinimoInscritos( $recaudar_entradas, $categoria['costo_ticket_usd'] );
            
            return array(
                'status'   => 'success',
                'message'  => 'tipo de subasta valida',
                'data'     => array(
                                "id"                 => $tipo,
                                "descripcion"        => $categoria['descripcion'],
                                "rango_inferior_usd" => $categoria['rango_inferior_usd'],
                                "rango_superior_usd" => $categoria['rango_superior_usd'],
                                "num_apostadores"    => $apostadores,
                                "costo_ticket_usd"   => $categoria['costo_ticket_usd']
                            ),
                'valorUSD' => $subasta['precio_usd']
            );
        }else{//NasbiDescuentos Estándar
            return array(
                'status'   => 'success',
                'message'  => 'tipo de subasta valida',
                'data'     => array(
                                "id"                 => $tipo,
                                "descripcion"        => $categoria['descripcion'],
                                "rango_inferior_usd" => $categoria['rango_inferior_usd'],
                                "rango_superior_usd" => $categoria['rango_superior_usd'],
                                "num_apostadores"    => $categoria['num_apostadores'],
                                "costo_ticket_usd"   => $categoria['costo_ticket_usd']
                            ),
                'valorUSD' => $subasta['precio_usd']
            );
        }

    }
    
    function getRangoSubastas(Array $subasta)
    {
        if( !isset($subasta['iso_code_2']) ) {

            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $subasta
            );
        }
        parent::conectar();

        $selectxtiposx = "SELECT * FROM productos_subastas_tipo";
        $selecttipos = parent::consultaTodo($selectxtiposx);
        parent::cerrar();

        // echo("##### yarin: " . $selectxtipoxsubasta);


        $iso_code_2 = $subasta['iso_code_2'];
        if ( $iso_code_2 != "US") {
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        }
        
        $precio_local_code = "US";
        $precio_local_rango_inferior = 0;
        $precio_local_rango_superior = 0;
        $precio_local_rango_inferior_us = 0;
        $precio_local_rango_superior_us = 0;


        foreach ( $selecttipos as $x => $tipoSelect )
        {
            if ( $tipoSelect['id'] == 5 ) {
                // ID: 1, Subastas premium
                if ( $iso_code_2 != "US") {
                    if(count($array_monedas_locales) > 0){
                        $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                        if(count($monedas_local) > 0) {
                            $monedas_local = $monedas_local[0];
                            $precio_local_rango_superior = $this->truncNumber(($tipoSelect['rango_superior_usd'] * $monedas_local['costo_dolar']), 2);
                            $precio_local_code = $monedas_local['code'];
                            $precio_local_rango_superior_us = $tipoSelect;
                        }
                    }
                }else{
                    $precio_local_rango_superior = $tipoSelect['rango_superior_usd'];
                }
            }else if ( $tipoSelect['id'] == 6 ) {
                // ID: 6, Subastas normales nasbi
                if ( $iso_code_2 != "US") {
                    if(count($array_monedas_locales) > 0){
                        $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                        if(count($monedas_local) > 0) {
                            $monedas_local = $monedas_local[0];
                            $precio_local_rango_inferior = $this->truncNumber(($tipoSelect['rango_inferior_usd'] * $monedas_local['costo_dolar']), 2);
                            $precio_local_code = $monedas_local['code'];
                            $precio_local_rango_inferior_us = $tipoSelect;
                        }
                    }
                }else{
                    $precio_local_rango_inferior = $tipoSelect['rango_inferior_usd'];
                }
            }else{

            }
        }
        return array(
            'status'                           => 'success',
            'message'                          => '....',
            'data'                             => $subasta['iso_code_2'],
            'iso_code_2'                       => $iso_code_2,
            
            'code'                             => $precio_local_code,
            
            'precio_local_rango_inferior'      => floatval($precio_local_rango_inferior),
            'precio_local_rango_inferior_mask' => $this->maskNumber(floatval($precio_local_rango_inferior), 2),
            
            'precio_local_rango_superior'      => floatval($precio_local_rango_superior),
            'precio_local_rango_superior_mask' => $this->maskNumber(floatval($precio_local_rango_superior), 2),
            'precio_local_rango_inferior_us'   => $precio_local_rango_inferior_us,
            'precio_local_rango_superior_us'   => $precio_local_rango_superior_us

        );
    }
    

    function verTicketsUsuario(Array $data)
    {
        $es_diamond = false;
        if( $data['plan'] == 5 ) {//1 TICKET DIAMOND = 2 TICKETS PLATINUM
            $es_diamond   = true;
            $data['plan'] = 4;
        }

        $selectxticketsxuser = 
        "SELECT nt.*, ntp.nombre AS nombre_plan 
        FROM nasbitickets nt 
        INNER JOIN nasbitickets_plan ntp ON nt.plan = ntp.id
        WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' AND nt.uso = '1' AND nt.plan = '$data[plan]' AND nt.estado = 1
        ORDER BY fecha_creacion ASC";
        parent::conectar();
        $tickets = parent::consultaTodo($selectxticketsxuser);
        parent::cerrar();
        if(!$tickets) return array('status' => 'fail', 'message'=> 'no tickets', 'cantidad_tickets' => 0, 'data' => $data);

        $tickets = $this->mapTickets($tickets, $es_diamond);
        if( $data['cantidad'] > $tickets['cantidad_tickets']) return array('status' => 'fail', 'message'=> 'no cantidad tickets', 'cantidad_tickets' => $tickets['cantidad_tickets'], 'data' => null);

        return array('status' => 'success', 'message'=> 'planes', 'cantidad_tickets' => $tickets['cantidad_tickets'], 'data' => $tickets['tickets']);
    }

    function cobrarTicekts(Array $data)
    {
        parent::conectar();
        $cobro = $this->actualizarTickets([
            'tickets' => $data['tickets'], 
            'actual' => 0, 
            'cantidad' => $data['cantidad'],
            'fecha' => $data['fecha'],
            'array_id' => [],
        ]);
        parent::cerrar();
        
        if(!$cobro) return array('status' => 'fail', 'message'=> 'no cobro tickets', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'cobro tickets', 'data' => $cobro);        
    }

    function actualizarTickets(Array $data)
    {//ESTA FUNCIÓN ES RECURSIVA

        $actual = $data['actual'];
        $ticket = $data['tickets'];
        $cantidad = $data['cantidad'];

        if($cantidad <= 0) return $data['array_id']; //true

        $cantidad_insertar = 0;
        $cantidad_restante = 0;
        $ticket = $ticket[$actual];
        $resta = $ticket['cantidad'] - $cantidad;

        $estado = 1;
        if($resta < 0){
            $cantidad_insertar = $ticket['cantidad'];

            $estado = 0;
            $cantidad_restante = 0;

            $cantidad = $resta * (-1);
        }else{
            $cantidad_insertar = $cantidad;
            
            if($resta == 0) $estado = 0;
            $cantidad_restante = $resta;
            
            $cantidad = 0;

        }

        $insertarxhistorico = "INSERT INTO nasbitickets_historico
        (
            id_nasbitickets,
            plan,
            uid,
            empresa,
            cantidad,
            codigo,
            uso,
            fecha_creacion
        )
        VALUES
        (
            '$ticket[id]',
            '$ticket[plan]',
            '$ticket[uid]',
            '$ticket[empresa]',
            '$cantidad_insertar',
            '$ticket[codigo]',
            '$ticket[uso]',
            '$data[fecha]'
        );";
        $insertar = parent::queryRegistro($insertarxhistorico);
        if(!$insertar) return false;
        
        $ubdatexticket = "UPDATE nasbitickets 
        SET 
            cantidad = '$cantidad_restante',
            estado = '$estado',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$ticket[id]'";
        $update = parent::query($ubdatexticket);
        if(!$update) return false;

        array_push($data['array_id'], $insertar);
        $actual++;
        return $this->actualizarTickets([
            'tickets' => $data['tickets'], 
            'actual' => $actual, 
            'cantidad' => $cantidad,
            'fecha' => $data['fecha'],
            'array_id' => $data['array_id'],
        ]);

    }

    function actualizarHistoricoTickets(Array $historico, Int $id_producto)
    {
        parent::conectar();
        foreach ($historico as $x => $hist) {
            $ubdatexticket = "UPDATE nasbitickets_historico 
            SET 
                id_producto = '$id_producto'
            WHERE id = '$hist'";
            parent::query($ubdatexticket);
        }
        parent::cerrar();
        
    }

    function insertarSubasta(Array $subasta, Int $fecha, Int $id, Int $precioDolar)
    {
        $fecha_inicio = $fecha + $this->tiempomaxsubasta;//24Hrs
        parent::conectar();
        $apostadores = 15;

        $selectednumber = parent::consultaTodo("SELECT * FROM productos_subastas_tipo pst WHERE '$precioDolar' BETWEEN rango_inferior_usd AND rango_superior_usd");
        if (count($selectednumber) > 0) {
            $apostadores = $selectednumber[0]["num_apostadores"];
        }

        $subasta['tipo'] = intval($subasta['tipo']);
        if( $subasta['tipo'] != 6) {

            $fecha_inicio = $fecha + ($this->tiempomaxsubasta * 2); //48Hrs
            # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
            # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
            $recaudar_entradas = ($subasta['precio_usd'] / 0.77) - ($subasta['precio_usd'] - ($subasta['precio_usd'] * 0.1));
            // $recaudar_entradas = ceil( $recaudar_entradas );



            #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
            $productos_subastas_tipo = $selectednumber[0];
            $apostadores             = $recaudar_entradas / $productos_subastas_tipo['costo_ticket_usd'];
            $apostadores             = round( $apostadores );
        }

        $insertarxsubasta = "INSERT INTO productos_subastas
        (
            id_producto,
            moneda,
            precio,
            precio_usd,
            cantidad,
            tipo,
            estado,
            apostadores,
            inscritos,
            fecha_fin,
            fecha_creacion,
            fecha_actualizacion,
            fecha_inicio
        )
        VALUES
        (
            '$id',
            '$subasta[moneda]',
            '$subasta[precio]',
            '$subasta[precio_usd]',
            '$subasta[cantidad]',
            '$subasta[tipo]',
            '1',
            '$apostadores',
            '0',
            '$fecha',
            '$fecha',
            '$fecha',
            '$fecha_inicio'
        );";
        $insertar = parent::query($insertarxsubasta);
        parent::cerrar();
    }

    function insertarEnvio(Array $envio, Int $fecha, Int $id)
    {
        parent::conectar();
        $insertarxenvio = "INSERT INTO productos_envio
        (
            id_producto,
            largo,
            ancho,
            alto,
            unidad_distancia,
            peso,
            unidad_masa,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$id',
            '$envio[largo]',
            '$envio[ancho]',
            '$envio[alto]',
            '$envio[unidad_distancia]',
            '$envio[peso]',
            '$envio[unidad_masa]',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertar = parent::queryRegistro($insertarxenvio);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'no se guardo el envio', 'data' => null);

        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
        
        // return $this->saveShippo([
        //     'length' => $envio['largo'],
        //     'width' => $envio['ancho'],
        //     'height' => $envio['alto'],
        //     'distance_unit' => $envio['unidad_distancia'],
        //     'weight' => $envio['peso'],
        //     'mass_unit' => $envio['unidad_masa'],
        //     'id' => $insertar
        // ]);
    }

    function saveShippo(Array $data)
    {
        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
        // Deprecado 28 - abril - 2021
        
        // $parcel = json_decode(Shippo_Parcel::create(array(
        //     'length' => $data['length'],
        //     'width' => $data['width'],
        //     'height' => $data['height'],
        //     'distance_unit' => $data['distance_unit'],
        //     'weight' => $data['weight'],
        //     'mass_unit' => $data['mass_unit'],
        // )));
        // $parcel = (array) $parcel;
        // parent::conectar();
        // $updatedireccion = "UPDATE productos_envio
        // SET
        //     id_shippo = '$parcel[object_id]'
        // WHERE id = '$data[id]'";
        // $actualizar = parent::query($updatedireccion);
        // parent::cerrar();
        // if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
        
        // return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
    }

    function editarTituloPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['titulo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $titulo = addslashes($data['titulo']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$publicacion['modelo'].' '.$titulo.' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "titulo = '$titulo',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarCategoriaPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        
        $categoria = addslashes($data['categoria']['CategoryID']);
        $subcategoria = addslashes($data['subcategoria']['CategoryID']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$publicacion['modelo'].' '.$publicacion['titulo'].' '.addslashes($data['categoria']['CategoryName']).' '.addslashes($data['subcategoria']['CategoryName']);
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "categoria = '$categoria',
            subcategoria = '$subcategoria',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarDescripcionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $descripcion = addslashes($data['descripcion']);
        $data['actualizar'] = "descripcion = '$descripcion',";
        return $this->actualizarPublicacion($data);
    }

    function editarModeloPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['modelo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $modelo = addslashes($data['modelo']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$modelo.' '.$publicacion['titulo'].' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "modelo = '$modelo',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarDireccionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['id_direccion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $direccion = $this->direccionId($data, false);
        if($direccion['status'] != 'success') return $direccion;
        $direccion = $direccion['data'];

        $data['actualizar'] = "id_direccion = '$direccion[id]',
            pais = '$direccion[pais]',
            departamento = '$direccion[departamento]',
            ciudad = '$direccion[ciudad]',
            latitud = '$direccion[latitud]',
            longitud = '$direccion[longitud]',
            codigo_postal = '$direccion[codigo_postal]',
            direccion = '$direccion[direccion]',";
        return $this->actualizarPublicacion($data);

    }

    function editarCantidadPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['cantidad'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $cantidad = addslashes($data['cantidad']);
        
        $data['actualizar'] = "cantidad = '$cantidad',";
        return $this->actualizarPublicacion($data);
    }

    function editarTipoPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['tipo_publicacion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $tipo_publicacion = addslashes($data['tipo_publicacion']);
        
        $data['actualizar'] = "tipo = '$tipo_publicacion',";
        return $this->actualizarPublicacion($data);
    }

    function editarExposicionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['exposicion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $exposicion = addslashes($data['exposicion']);

        $data['actualizar'] = "exposicion = '$exposicion',";
        //para enviar correo de expo
        $producto = $this->get_product_por_id([
            'uid'     => $data["uid"],
            'id'      => $data["id"],
            'empresa' => $data["empresa"]
        ]);

        $data['tipo_edicion_correo'] = 13; 
        $data['porducto_antes']      = $producto[0]; 
        //para enviar correo de expo

        if ( count( $producto ) == 0) {
            return array(
                'status'  => 'NoExisteProducto',
                'message' => 'No se hallo el articulo'
            );
            
        }
        return $this->actualizarPublicacion($data);
    }

    function editarVideoPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['url_video']) || !isset($data['portada_video'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $url_video = addslashes($data['url_video']);
        $portada_video = addslashes($data['portada_video']);

        $fecha = intval(microtime(true)*1000);
        $pos = strrpos($portada_video, ';base64,');
        if ($pos === false) { // nota: tres signos de igual
            $portada_video = $portada_video;
        }else{
            $imgconcat = $fecha.'_portada_video';
            $url = $this->uploadImagen([
                'img' => $data['portada_video'],
                'ruta' => '/imagenes/publicaciones/'.$data['id'].'/'.$imgconcat.'.png',
            ]);
            $portada_video = $url;
        }
        
        
        $data['actualizar'] = "url_video = '$url_video',
        portada_video = '$portada_video',";
        return $this->actualizarPublicacion($data);
    }

    public function direccionId(Array $data, Bool $estado){
        if(!isset($data) || !isset($data['id_direccion']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);

        $condicionestado = "";
        if($estado == true) $condicionestado = "AND d.estado = 1";
        
        parent::conectar();
        $selectdireccion = "SELECT * FROM direcciones d WHERE d.id = '$data[id_direccion]' AND d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' $condicionestado ORDER BY fecha_creacion DESC";
        $direcciones = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($direcciones) <= 0) return array('status' => 'fail', 'message'=> 'la direccion no existe','data' => null);
            
        $direcciones = $this->mapDirecciones($direcciones);
        return array('status' => 'success', 'message'=> 'direccion', 'data' => $direcciones[0]);
    }

    function editarFotosPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['fotos_producto'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $desactivarfotos = $this->desactivarFotos($data);
        if($desactivarfotos['status'] == 'fail') return $desactivarfotos;

        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});

        return $this->subirFotosProducto($fotos, $data['fecha'], $data['id']);
    }

    function desactivarFotos(Array $data)
    {
        parent::conectar();
        $updatexpublicacion = "UPDATE productos_fotos 
        SET 
            estado = 0,
            fecha_actualizacion = '$data[fecha]'
        WHERE id_publicacion = '$data[id]' AND estado = 1";
        $update = parent::query($updatexpublicacion);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'publicacion no actualizada', 'data' => null);

        return array('status' => 'success', 'message'=> 'publicacion actualizada', 'data' => null);

    }

    function editarMarcaPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['marca']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $marca = addslashes($data['marca']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$marca.' '.$publicacion['modelo'].' '.$publicacion['titulo'].' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "marca = '$marca',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarPrecioPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['precio'])  || !isset($data['porcentaje_oferta']) || !isset($data['porcentaje_tax']) || !isset($data['iso_code_2']) || !isset($data['oferta'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $precio_usd = null;
        $monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    $precio_usd = $this->truncNumber(($data['precio'] / $monedas_local['costo_dolar']), 2);
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US') {
            $precio_usd = $data['precio'];
            $moneda_local = 'USD';
        }
        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        // if( $precio_usd < $this->montoxminimoxpublicar ) {
        //     return array(
        //         'status' => 'errorMontoMinimoPublicar',
        //         'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                
        //         'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
        //         'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
        //         'costo_dolar' => $monedas_local['costo_dolar'],
        //         'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $monedas_local['costo_dolar'],
        //         'montoMinimoMonedaLocalMak' => $this->maskNumber($this->montoxminimoxpublicar * $monedas_local['costo_dolar'], 2),
        //         'symbolMonedaLocal' => $monedas_local['code'],
        //         'data' => null
        //     );
        // }

        if ( floatval($data['porcentaje_oferta']) < 0 || intval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $monedas_local['costo_dolar'], 2),
                    'symbolMonedaLocal' => $monedas_local['code'],
                    'data' => null
                );
                
            }
        }

        $precio_usd;
        $moneda_local = $monedas_local['code'];

        if($data['oferta'] == 0) $data['porcentaje_oferta'] = 0;

        $data['precio']            = floatval(''. $data['precio'] );
        $data['porcentaje_oferta'] = floatval(''. $data['porcentaje_oferta'] );
        $data['porcentaje_tax']    = floatval(''. $data['porcentaje_tax'] );
        
        $precioSinAranceles = 0;
        $id_pasos = "0";

        if ( $data['porcentaje_oferta'] > 0 ) {
            $precioSinAranceles = $data['precio'] - ( $data['precio'] * ($data['porcentaje_oferta'] / 100) );
            $precioSinAranceles = $this->truncNumber( $precioSinAranceles / ( ($data['porcentaje_tax'] + 100) / 100 ), 2);

            $id_pasos = "1";
        }else{
            $precioSinAranceles = $data['precio'];
            $precioSinAranceles = $this->truncNumber( $precioSinAranceles / ( ($data['porcentaje_tax'] + 100) / 100 ), 2);
            $id_pasos = "2";
        }

        if ( $precioSinAranceles <= $this->montoxminimoxpublicarxmonedaxlocal ) {
            return array(
                'status'                  => 'errorMontoMinimoPublicar',
                
                'message'                 => 'El monto minimo a publicar con descuentos y sin IVA debe ser superior a: ' . $this->montoxminimoxpublicarxmonedaxlocal,
                
                'descuento'               => $data['porcentaje_oferta'],

                'iva'                     => $data['porcentaje_tax'],
                
                'valorConDescuento'       => ($data['precio'] - ( $data['precio'] * ($data['porcentaje_oferta'] / 100) )),
                
                'valorConDescuentoSinIva' => $precioSinAranceles,

                'dataSend'                => $data,

                'dataSendResult' => array(
                    'id_pasos'                           => $id_pasos,
                    'precioSinAranceles'                 => $precioSinAranceles,
                    'montoxminimoxpublicarxmonedaxlocal' => $this->montoxminimoxpublicarxmonedaxlocal,
                    'porcentaje_oferta'                  => $data['porcentaje_oferta'],
                    'Condicional'                        => $precioSinAranceles <= $this->montoxminimoxpublicarxmonedaxlocal,
                    'precio'                             => $data['precio'],
                    'paso_1'                             => $data['precio'] - ( $data['precio'] * ($data['porcentaje_oferta'] / 100) ),
                    'paso_1_1'                           => $data['precio'] - ( $data['precio'] * ($data['porcentaje_oferta'] / 100) ) - ( $data['precio'] - ( $data['precio'] * ($data['porcentaje_oferta'] / 100) ) * ($data['porcentaje_oferta'] / 100) ),

                    'data'                               => $data


                )
                
            );
        }

        if( boolval($data['es_subasta']) ) {

            parent::addLogSubastas("PRECIO CON IVA =========> ". $data['precio'] );
            parent::addLogSubastas("PRECIO CON IVA USD =========> ". $precio_usd );
            
            parent::conectar();
            $subasta = parent::consultaTodo("SELECT * FROM productos_subastas_tipo pst WHERE $precio_usd BETWEEN rango_inferior_usd AND rango_superior_usd");
            $tipo_subasta_anterior = parent::consultaTodo("SELECT tipo, fecha_creacion FROM productos_subastas ps WHERE id_producto = '$data[id]'");
            parent::cerrar();

            $productos_subastas_tipo = $subasta[0];
            $tipo_subasta_anterior = $tipo_subasta_anterior[0];
            $fecha_creacion = intval($tipo_subasta_anterior['fecha_creacion']);

            parent::addLogSubastas("CATEGORIA SUBASTA ANTERIOR ===> ".$tipo_subasta_anterior['tipo']);
            parent::addLogSubastas("CATEGORIA SUBASTA NUEVA ===> ".$productos_subastas_tipo['id']);

            //ACTUALIZAR LA PUBLICACIÓN
            $data['actualizar'] = 
            "precio           = '$data[precio]',
            tipoSubasta       = '$productos_subastas_tipo[id]',
            precio_usd        = '$precio_usd',
            moneda_local      = '$moneda_local',
            porcentaje_tax    = '$data[porcentaje_tax]',
            oferta            = '$data[oferta]',
            porcentaje_oferta = '$data[porcentaje_oferta]',";

            $resp = $this->actualizarPublicacion($data);
            //FIN ACTUALIZAR LA PUBLICACIÓN

            if( $tipo_subasta_anterior['tipo'] != $productos_subastas_tipo['id'] ) {//DEVOLUCIÓN DE TICKET

                if( $tipo_subasta_anterior['tipo'] != 6 ) {//VERIFICACIÓN QUE NO SEA SUBASTA STANDARD PARA REGRESAR EL TICKET

                    $datos_ticket = array( "uid" => $data["uid"],
                                    "empresa" => $data["empresa"],
                                    "tipo_ticket" => $tipo_subasta_anterior['tipo'],
                                    "cantidad_ticket" => 1 );
    
                    $this->regresarTicketDeVenta( $datos_ticket );
                }

                if( $productos_subastas_tipo['id'] != 6 ){//VERIFICACIÓN QUE NO SEA SUBASTA STANDARD PARA COBRAR EL NUEVO TICKET

                    $tickets = $this->verTicketsUsuario([
                        'uid'      => $data['uid'],
                        'empresa'  => $data['empresa'],
                        'plan'     => $productos_subastas_tipo['id'],
                        'cantidad' => 1
                    ]);
                    
                    if($tickets['status'] == 'fail') return $tickets;
                    $tickets = $tickets['data'];

                    $cantidad = 1;

                    if( $productos_subastas_tipo['id'] == 5 ) {
                        $cantidad = 2;
                    }
        
                    $cobrar = $this->cobrarTicekts([
                        'tickets'  => $tickets, 
                        'cantidad' => $cantidad,
                        'fecha'    => $data['fecha']
                    ]);
                }

                /* if( $tipo_subasta_anterior['tipo'] != 6 && $productos_subastas_tipo['id'] != 6 ) {//SI EL CAMBIO DE CATEGORÍA ES ENTRE SUBASTAS CON TICKETS, DEJAR IGUAL LA FECHA DE INICIO
                    $this->actualizarSubasta( $data, $productos_subastas_tipo, $fecha_creacion );
                }else{
                    $this->actualizarSubasta( $data, $productos_subastas_tipo, $fecha_creacion, true);//CAMBIAR FECHA DE INICIO
                } */
                
            }/* else{
                $this->actualizarSubasta( $data, $productos_subastas_tipo, $fecha_creacion );
            } */

            $this->actualizarSubasta( $data, $productos_subastas_tipo/* , $fecha_creacion */ );

        }else{

            $data['actualizar'] = 
            "precio           = '$data[precio]',
            precio_usd        = '$precio_usd',
            moneda_local      = '$moneda_local',
            porcentaje_tax    = '$data[porcentaje_tax]',
            oferta            = '$data[oferta]',
            porcentaje_oferta = '$data[porcentaje_oferta]',";

            $resp = $this->actualizarPublicacion($data);
        }

        return $resp;
    }

    public function regresarTicketDeVenta( $datos_ticket ) { 
        $dataArray = array( 
            "data" => 
            array(  "uid"         => intval($datos_ticket['uid']), 
                    "empresa"     => intval($datos_ticket['empresa']),
                    "tipo"        => intval($datos_ticket['tipo_ticket']),
                    "cantidad"    => intval($datos_ticket['cantidad_ticket']),
                    "uso"         => 1,
                    "transferido" => null
                )
        );
        
        parent::remoteRequest(
            "http://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w",
            $dataArray
        );
    }

    public function obtenerFechaMenosTiempoTranscurrido($fecha_creacion, $fecha_actual, $nueva_fecha_inicio) {

        $diferencia_fechas = abs( ($fecha_actual/1000) - ($fecha_creacion/1000) );

        $anios = floor($diferencia_fechas / (365 * 60 * 60 * 24));

        $meses = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));

        $dias  = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        $horas = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24) / (60 * 60));

        $minutos  = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24 - $horas * 60 * 60) / (60) );

        $segundos = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24 - $horas * 60 * 60 - $minutos * 60) );

        parent::addLogSubastas("DIFERENCIA ENTRE FECHA DE CREACION Y FECHA ACTUAL ====> ".$anios." year,  ".$meses." months ".$dias." days ".$horas." hours ".$minutos." minutes ".$segundos." seconds");

        $dias     = $dias * 86400000;
        $horas    = $horas * 3600000;
        $minutos  = $minutos * 60000;
        $segundos = $segundos * 6000;

        $nueva_fecha_inicio = $nueva_fecha_inicio - $dias - $horas - $minutos - $segundos;

        return $nueva_fecha_inicio;
    }

    public function actualizarSubasta( $data, $productos_subastas_tipo/* , $fecha_creacion, $cambio_tipo_subasta = false */ ) {
        $SQL_obtener_producto = "SELECT precio_usd, fecha_actualizacion FROM productos WHERE id = '$data[id]'";
        parent::conectar();
        $producto = parent::consultaTodo($SQL_obtener_producto);
        parent::cerrar();

        $producto = $producto[0];
        $nodo = new Nodo();

        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        $precio_subasta = $this->truncNumber(($producto['precio_usd'] / $precios[$data['moneda_pujas']]), 6);

        $tipo_subasta = intval($productos_subastas_tipo['id']);
        $apostadores = 15;

        $SQL = '';

        // if( $cambio_tipo_subasta ) {//CAMBAMOS LA FECHA DE INICIO
            $fecha_actual = $producto['fecha_actualizacion'];
            $nueva_fecha_inicio = $fecha_actual + $this->tiempomaxsubasta;
            
            if( $tipo_subasta != 6) {
                $nueva_fecha_inicio = $fecha_actual + ($this->tiempomaxsubasta * 2); //48Hrs
                # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
                # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
                $recaudar_entradas  = ($producto['precio_usd'] / 0.77) - ($producto['precio_usd'] - ($producto['precio_usd'] * 0.1));
                // $recaudar_entradas = ceil( $recaudar_entradas );

                #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
                $apostadores = $recaudar_entradas / $productos_subastas_tipo['costo_ticket_usd'];
                $apostadores = round( $apostadores );
            }

            // $nueva_fecha_inicio = $this->obtenerFechaMenosTiempoTranscurrido($fecha_creacion, $fecha_actual, $nueva_fecha_inicio);
            
            $SQL = "UPDATE buyinbig.productos_subastas SET
                    moneda       = '$data[moneda_pujas]',
                    tipo         = '$productos_subastas_tipo[id]',
                    apostadores  = '$apostadores',
                    precio       = '$precio_subasta',
                    precio_usd   = '$producto[precio_usd]',
                    fecha_inicio = '$nueva_fecha_inicio',
                    fecha_creacion      = '$fecha_actual',
                    fecha_fin           = '$fecha_actual',
                    fecha_actualizacion = '$fecha_actual'
                    WHERE id_producto   = '$data[id]'";

        /* }else{//MANTENEMOS LA MISMA LA FECHA DE INICIO DE LA SUBASTA
            // $fecha_actual = $producto['fecha_actualizacion'];
            // $nueva_fecha_inicio = $fecha_actual + $this->tiempomaxsubasta;
            
            if( $tipo_subasta != 6) {
                // $nueva_fecha_inicio = $fecha_actual + ($this->tiempomaxsubasta * 2); //48Hrs
                # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
                # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
                $recaudar_entradas  = ($producto['precio_usd'] / 0.77) - ($producto['precio_usd'] - ($producto['precio_usd'] * 0.1));
                // $recaudar_entradas = ceil( $recaudar_entradas );

                #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
                $apostadores = $recaudar_entradas / $productos_subastas_tipo['costo_ticket_usd'];
                $apostadores = round( $apostadores );
            }

            // $nueva_fecha_inicio = $this->obtenerFechaMenosTiempoTranscurrido($fecha_creacion, $fecha_actual, $nueva_fecha_inicio);
            
            $SQL = "UPDATE buyinbig.productos_subastas SET
                    moneda      = '$data[moneda_pujas]',
                    tipo        = '$productos_subastas_tipo[id]',
                    apostadores = '$apostadores',
                    precio      = '$precio_subasta',
                    precio_usd  = '$producto[precio_usd]',
                    fecha_actualizacion = '$producto[fecha_actualizacion]'
                    WHERE id_producto   = '$data[id]'";

        } */

        parent::conectar();
        parent::query($SQL);
        parent::cerrar();

    }

    function editarEnvioPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['tipo_envio']) || !isset($data['largo']) || !isset($data['ancho']) || !isset($data['alto']) || !isset($data['unidad_distancia']) || !isset($data['peso']) || !isset($data['unidad_masa']) || !isset($data['tipo_envio_gratuito']) ) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $updatexpublicacion = "UPDATE productos_envio 
        SET 
            estado = 0,
            fecha_actualizacion = '$data[fecha]'
        WHERE id_producto = '$data[id]'";
        $update = parent::query($updatexpublicacion);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'error al cambiar el envio', 'data' => null);

        $envio = $this->insertarEnvio($data, $fecha, $data['id']);
        if($envio['status'] != 'success') return $envio;

        $data['actualizar'] = 
                "envio = '$data[tipo_envio]',
                tipo_envio_gratuito = '$data[tipo_envio_gratuito]',";
        
        return $this->actualizarPublicacion($data);
    }

    function editarCondicionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['condicion_producto']) || !isset($data['garantia']) || !isset($data['id_tiempo_garantia']) || !isset($data['num_garantia']) ) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $condicion_producto = addslashes($data['condicion_producto']);
        $garantia = floatval($data['garantia']);
        $id_tiempo_garantia = intval($data['id_tiempo_garantia']);
        $num_garantia  = intval($data['num_garantia']);

        $data['actualizar'] =
               "condicion_producto = '$condicion_producto',
               garantia            = '$garantia',
               id_tiempo_garantia  = '$id_tiempo_garantia',
               num_garantia        = '$num_garantia',";
             
        return $this->actualizarPublicacion($data);
    }

    function getPublicationType(String $id){
        parent::conectar();
        $status = "SELECT id_producto FROM productos_subastas WHERE id_producto = '$id'";
        $update = parent::query($status);
        parent::cerrar();
        if($update) return "subasta";
        if(!$update) return "producto";
    }

    function getAuctionUsers(String $id){
        parent::conectar();
        $status = "SELECT inscritos FROM productos_subastas WHERE id_producto = '$id'";
        $update = parent::query($status);
        parent::cerrar();
        if($update) return $update;
        if(!$update) return "noData";
    }

    public function pausarPublicacionEditar( $id_producto ) {

        $SQL = "UPDATE productos SET estado = 2 WHERE id = '$id_producto';";

        parent::addLogSubastas("SQL ======>> ". $SQL);
        
        parent::conectar();
        parent::query($SQL);
        parent::cerrar();
    }

    public function insertarProductoEnRevision( $data ) {

        parent::conectar();

        $id_revision = isset($data['id_productos_revision_estados']) ? $data['id_productos_revision_estados'] : 0;

        $insertarSQL =
        "INSERT INTO productos_revision(
            id,
            uid,
            empresa,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            foto_portada,
            url_video,
            portada_video,
            fecha_creacion,
            genero,
            tiene_colores_tallas,
            id_productos_revision_estados,
            tipo_envio_gratuito,
            id_tiempo_garantia,
            num_garantia,
            tiempo_estimado_envio_num,
            tiempo_estimado_envio_unidad
        )
        VALUES(
            '$data[id]',
            '$data[uid]',
            '$data[empresa]',
            '$data[tipo]',
            '$data[tipoSubasta]',
            '$data[producto]',
            '$data[marca]',
            '$data[modelo]',
            '$data[titulo]',
            '$data[descripcion]',
            '$data[categoria]',
            '$data[subcategoria]',
            '$data[condicion_producto]',
            '$data[garantia]',
            '$data[estado]',
            '$data[fecha_actualizacion]',
            '$data[ultima_venta]',
            '$data[cantidad]',
            '$data[moneda_local]',
            '$data[precio]',
            '$data[precio_usd]',
            '$data[precio_publicacion]',
            '$data[precio_usd_publicacion]',
            '$data[oferta]',
            '$data[porcentaje_oferta]',
            '$data[porcentaje_tax]',
            '$data[exposicion]',
            0,
            '$data[envio]',
            '$data[id_direccion]',
            '$data[pais]',
            '$data[departamento]',
            '$data[ciudad]',
            '$data[latitud]',
            '$data[longitud]',
            '$data[codigo_postal]',
            '$data[direccion]',
            '$data[keywords]',
            0,
            '$data[foto_portada]',
            '$data[url_video]',
            '$data[portada_video]',
            '$data[fecha_creacion]',
            '$data[genero]',
            '$data[tiene_colores_tallas]',
            '$id_revision',
            '$data[tipo_envio_gratuito]',
            '$data[id_tiempo_garantia]',
            '$data[num_garantia]',
            '$data[tiempo_estimado_envio_num]',
            '$data[tiempo_estimado_envio_unidad]'
        )";

        parent::queryRegistro($insertarSQL);
        parent::cerrar();
    }

    public function eliminarRevisionAnterior( $id_producto ){
        $SQL = "DELETE FROM productos_revision WHERE id = '$id_producto'";
        
        parent::conectar();
        parent::query($SQL);
        parent::cerrar();
    }

    function actualizarPublicacion(Array $data) 
    {        
        parent::conectar();
        $tiene_permisos = parent::consultaTodo("SELECT * FROM productos_permisos_publicar WHERE uid = '$data[uid]' AND empresa = '$data[empresa]'");
        parent::cerrar();


        if( COUNT( $tiene_permisos ) > 0 ) {
            //EJECUTAR REVISION
            
            /* $producto = $this->obtenerProducto( $data['id'] );

            if( count($producto) > 0 ) {

                $producto = $producto[0];

                $this->eliminarRevisionAnterior( $producto['id'] );
                $this->insertarProductoEnRevision( $producto );
                $this->pausarPublicacionEditar( $producto['id'] );

            } */

            $actualizar = $data['actualizar'];
            parent::conectar();
            $updatexpublicacion = "UPDATE productos_revision SET $actualizar fecha_actualizacion = '$data[fecha]' WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
            $update = parent::query($updatexpublicacion);
            $verificar = "SELECT id_productos_revision_estados FROM productos_revision WHERE id = '$data[id]'";
            $existe = parent::consultarArreglo($verificar);
            if (count($existe)) {
                if ($existe['id_productos_revision_estados'] == '1') {
                    $auxupdatexpublicacion = "UPDATE productos_revision SET id_productos_revision_estados = 0, fecha_actualizacion = '$data[fecha]' WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
                    $auxupdate = parent::query($auxupdatexpublicacion);
                }
            }
            if (isset($data['tipo'])) {
                if ($data['tipo'] != 17) {
                    $updatexpublicacion2 = 
                        "UPDATE productos
                            SET
                            $actualizar fecha_actualizacion = '$data[fecha]'
                        WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
        
                    $update2 = parent::query($updatexpublicacion2);
                }
            }
            parent::cerrar();
            if(!$update) return array('status' => 'fail', 'message'=> 'publicacion no actualizada', 'data' => $updatexpublicacion);
            //para enviar correo si se edito
            if(isset($data['tipo_edicion_correo'])){
                $this->enviar_correo_edicion($data);
            }
            //para enviar correo si se edito 

            $this->envio_de_correo_general_ediccion($data); 

        }else{
            $actualizar = $data['actualizar'];
            parent::conectar();
            $updatexpublicacion = 
                "UPDATE productos
                    SET
                    $actualizar fecha_actualizacion = '$data[fecha]'
                WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";

            $update = parent::query($updatexpublicacion);
            parent::cerrar();
            if(!$update) return array('status' => 'fail', 'message'=> 'publicacion no actualizada', 'data' => $updatexpublicacion);
            //para enviar correo si se edito
            

            if(isset($data['tipo_edicion_correo'])){
                $this->enviar_correo_edicion($data);
            }
            //para enviar correo si se edito 

            $this->envio_de_correo_general_ediccion($data); 
        }

        return array('status' => 'success', 'message'=> 'publicacion actualizada', 'data' => null);
    }

    function detalleEnvioTransaccion(Array $data)
    {
        parent::conectar();
        $selecxenvio = "SELECT pe.*
        FROM productos_envio pe
        WHERE pe.id_producto = '$data[id]' AND estado = 1
        ORDER BY fecha_creacion DESC;";
        $envio = parent::consultaTodo($selecxenvio);
        parent::cerrar();
        if(count($envio) <= 0) return null;
        
        return $this->mapeoEnvio($envio)[0];
    }

    function mapeoVender(Array $data)
    {
        $data['uid'] = addslashes($data['uid']);
        $data['tipo'] = addslashes($data['tipo']);
        $data['producto'] = addslashes($data['producto']);
        $data['marca'] = addslashes($data['marca']);
        $data['modelo'] = addslashes($data['modelo']);
        $data['titulo'] = addslashes($data['titulo']);
        $data['descripcion'] = addslashes($data['descripcion']);
        /* $data['num_garantia'] = addslashes($data['num_garantia']);
        $data['num_tiempo_garantia'] = addslashes($data['num_tiempo_garantia']);
        $data['tipo_envio_gratuito'] = addslashes($data['tipo_envio_gratuito']); */
        
        // $data['categoria'] = addslashes($data['categoria']);
        // $data['subcategoria'] = addslashes($data['subcategoria']);
        $data['condicion_producto'] = addslashes($data['condicion_producto']);
        $data['garantia'] = addslashes($data['garantia']);
        $data['cantidad'] = addslashes($data['cantidad']);
        $data['moneda_local'] = addslashes($data['moneda_local']);
        $data['precio'] = addslashes($data['precio']);
        $data['precio_usd'] = addslashes($data['precio_usd']);
        $data['oferta'] = addslashes($data['oferta']);
        $data['porcentaje_oferta'] = addslashes($data['porcentaje_oferta']);
        $data['exposicion'] = addslashes($data['exposicion']);
        $data['cantidad_exposicion'] = addslashes($data['cantidad_exposicion']);
        $data['envio'] = addslashes($data['envio']);
        $data['direccion_envio']['pais'] = addslashes($data['direccion_envio']['pais']);
        $data['direccion_envio']['departamento'] = addslashes($data['direccion_envio']['departamento']);
        $data['direccion_envio']['ciudad'] = addslashes($data['direccion_envio']['ciudad']);
        $data['direccion_envio']['latitud'] = addslashes($data['direccion_envio']['latitud']);
        $data['direccion_envio']['longitud'] = addslashes($data['direccion_envio']['longitud']);
        $data['direccion_envio']['codigo_postal'] = addslashes($data['direccion_envio']['codigo_postal']);
        $data['direccion_envio']['direccion'] = addslashes($data['direccion_envio']['direccion']);
        $url_video = '';
        $portada_video = '';
        if(isset($data['url_video']) && !empty($data['url_video'])) {
            $url_video = addslashes($data['url_video']);
            $portada_video = addslashes($data['portada_video']);
        }
        $data['url_video'] = $url_video;
        $data['portada_video'] = $portada_video;
        // $data['keywords'] = addslashes($data['keywords']);
        foreach ($data['fotos_producto'] as $x => $foto) {
            $foto['id'] = addslashes($foto['id']);
            $foto['img'] = addslashes($foto['img']);
            $data['fotos_producto'][$x] = $foto;
        }
        
        return $data;
    }

    function mapTickets(Array $tickets, $es_diamond)
    {
        $cantidad_tickets = 0;
        foreach ($tickets as $x => $ticket) {
            $ticket['plan'] = floatval($ticket['plan']);
            $ticket['cantidad'] = floatval($ticket['cantidad']);
            $cantidad_tickets += $ticket['cantidad'];
            $ticket['id'] = floatval($ticket['id']);
            $ticket['uid'] = floatval($ticket['uid']);
            $ticket['empresa'] = floatval($ticket['empresa']);
            $ticket['id_nabitickets_usuario'] = floatval($ticket['id_nabitickets_usuario']);
            $ticket['transferido'] = floatval($ticket['transferido']);
            $ticket['uso'] = floatval($ticket['uso']);
            $ticket['origen'] = floatval($ticket['origen']);
            $ticket['estado'] = floatval($ticket['estado']);
            $ticket['fecha_creacion'] = floatval($ticket['fecha_creacion']);
            $ticket['fecha_actualizacion'] = floatval($ticket['fecha_actualizacion']);
            $ticket['fecha_vencimiento'] = floatval($ticket['fecha_vencimiento']);
            $tickets[$x] = $ticket;
        }

        if( $es_diamond ) {
            $cantidad_tickets = intval($cantidad_tickets / 2);//AQUÍ HAY TRUNCAMIENTO DE DECIMALES
        }

        return array(
            'cantidad_tickets' => $cantidad_tickets,
            'tickets' => $tickets,
        );
    }

    function mapPublicaciones(Array $productos, Bool $condicionestado = false){
        foreach ($productos as $x => $producto) {
            unset($producto['precio']);
            
            $producto['id'] = intval($producto['id']);
            $producto['uid'] = intval($producto['uid']);
            $producto['tipo'] = intval($producto['tipo']);
            $producto['categoria'] = intval($producto['categoria']);
            if (isset($producto['id_productos_revision_estados'])) {
                $producto['id_productos_revision_estados'] = intval($producto['id_productos_revision_estados']);
            }
            $producto['subcategoria'] = intval($producto['subcategoria']);
            $producto['condicion_producto'] = intval($producto['condicion_producto']);
            $producto['garantia'] = intval($producto['garantia']);

            $producto['estado'] = intval($producto['estado']);
            $producto['estado_descripcion'] = intval($producto['estado']) == 1 ? 'Activa' : 'Pausada';

            //0 eliminado, 1 activo, 2 pausado, 3 revision y 5 rechazado

            if( isset($producto['revision_status']) ){
                
                $producto['revision_status'] = intval($producto['revision_status']);

                if(intval($producto['revision_status']) == 0){
                    $producto['estado'] = 3;
                    $producto['estado_descripcion'] = 'Revisión';

                }else if(intval($producto['revision_status'] == 2)){
                    $producto['estado'] = 5;
                    $producto['estado_descripcion'] = 'Rechazada';

                }
            }
            
            if(intval($producto['tipoSubasta']) > 0){
                $producto['subasta_terminada'] = false;
                $subasta = [];
                if($condicionestado == true){
                    parent::conectar();
                    $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id_producto = '$producto[id]';");
                    parent::cerrar();
                }else{
                    $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id_producto = '$producto[id]';");
                }

                if( COUNT( $subasta ) > 0 ){
                    if(intval($subasta[0]['estado']) == 4){
                        $producto['subasta_terminada'] = true;
                    }
                }
                
            }
            
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['ultima_venta'] = floatval($producto['ultima_venta']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask']= $this->maskNumber($producto['precio_usd'], 2);
            $producto['precio_local'] = floatval($producto['precio_local']);
            $producto['precio_local_mask']= $this->maskNumber($producto['precio_local'], 2);
            $producto['oferta'] = floatval($producto['oferta']);
            $producto['porcentaje_oferta'] = floatval($producto['porcentaje_oferta']);
            $producto['porcentaje_tax'] = floatval($producto['porcentaje_tax']);
            $producto['exposicion'] = floatval($producto['exposicion']);
            $producto['cantidad_exposicion'] = floatval($producto['cantidad_exposicion']);
            $producto['envio'] = floatval($producto['envio']);
            $producto['pais'] = floatval($producto['pais']);
            $producto['departamento'] = floatval($producto['departamento']);
            $producto['latitud'] = floatval($producto['latitud']);
            $producto['longitud'] = floatval($producto['longitud']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['cantidad_vendidas'] = floatval($producto['cantidad_vendidas']);
            $producto['id_direccion'] = floatval($producto['id_direccion']);

            $producto['precio_descuento_usd'] = $producto['precio_usd'];
            $producto['precio_descuento_usd_mask'] = $this->maskNumber($producto['precio_descuento_usd'], 2);
            $producto['precio_descuento_local'] = $producto['precio_local'];
            $producto['precio_descuento_local_mask'] = $this->maskNumber($producto['precio_descuento_local'], 2);

            if(!isset($producto['visitas'])) $producto['visitas'] = 0;
            if(isset($producto['visitas'])) $producto['visitas'] = floatval($producto['visitas']);
            if($condicionestado == true) $producto['detalles_envio'] = $this->detalleEnvioTransaccion($producto);
            if($condicionestado == true) $producto['detalles_direccion'] = $this->direccionId($producto, false)['data'];

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);
            }

            $productos[$x] = $producto;
        }
        return $productos;
    }

    function mapProductosSubastas($productos){
        $dataMonedaLocal = $this->selectMonedaLocalUserByCountryID([
            'country_id' => $productos[ 0 ]['pais']
        ]);
        foreach ($productos as $x => $producto) {
            $producto['id'] = floatval($producto['id']);
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
            $producto['fecha_fin'] = floatval($producto['fecha_fin']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['porcentaje_entrada'] = null;
            $producto['porcentaje_entrada_mask'] = null;

            $producto['oferta'] = floatval( $producto['oferta'] );
            $producto['porcentaje_oferta'] = floatval( $producto['porcentaje_oferta'] );
            $producto['precio'] = floatval( $producto['precio'] );
            $producto['precioProducto'] = floatval( $producto['precioProducto'] );
            
            if ( floatval( $producto['oferta'] ) > 0 ) {
                $producto['precioProducto'] = ( $producto['precioProducto'] - ( $producto['precioProducto'] * ($producto['porcentaje_oferta']/100)));
                $producto['precio'] = ( $producto['precio'] - ( $producto['precio'] * ($producto['porcentaje_oferta']/100)));
            }
            
            if( !isset($producto['tipoSubasta']) ) {
                $producto['tipoSubasta'] = 0;
            }

            if($producto['tipoSubasta'] == 6) {
                $producto['paso_por_aqui_1'] = "XX_SIII";
                $producto['porcentaje_entrada'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio']), 2);
                $producto['porcentaje_entrada_mask']= $this->maskNumber($producto['porcentaje_entrada'], 2);

                $producto['porcentaje_entrada_usd'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_usd_mask']= $this->maskNumber($producto['porcentaje_entrada_usd'], 2);

                $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                
                if(isset($dataRecibida) && isset($dataRecibida['iso_code_2_money'])) {

                    if ( ($dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) && $dataMonedaLocal['code'] != "US" ) {


                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precioProducto']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                        $producto['paso_por_aqui_2'] = "XX_SIII";

                        
                    }else{
                        $moneda_local = $this->getMonedaLocalUser($dataRecibida);
                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);

                    }
                }

                $inscritos = $producto['inscritos'];
                if ($inscritos < 15) $inscritos = 15;
                if ($inscritos > 35) $inscritos = 35;
                $producto['porcentaje'] = $this->truncNumber(floatval(( (($inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal )) * 100), 1);
            }

            if(isset($producto['precio_local_user'])){
                // if ( $dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) {
                    $producto['precio_local_user'] = floatval($this->truncNumber($producto['precioProducto'],2));
                    $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
                    
                // }else{
                //     $producto['precio_local_user'] = floatval($this->truncNumber($producto['precio_local_user'],2));
                //     $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
                // }
            }else{
                $producto['precio_local_user'] = $producto['precio_usd'];
                $producto['precio_local_user_mask'] = $producto['precio_usd_mask'];
                $producto['moneda_local_user'] = 'USD';

            }

            if($producto['tipoSubasta'] == 6) {
                $producto['precio_subasta_local_user'] = $producto['precio_local_user'] - floatval($producto['precio_local_user'] * ($producto['porcentaje'] / 100));
                $producto['precio_subasta_local_user_mask'] = $this->maskNumber($producto['precio_subasta_local_user'], 2);
                $producto['paso_por_aqui_3'] = "XX_SIII";
            }

            $productos[$x] = $producto;
        }
        return $productos;
    }
    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2_money'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2_money']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];

                    $select_precio = ", (".$monedas_local['costo_dolar']."*p.precio_usd) AS precio_local_user, IF(1 < 2, '".$monedas_local['code']."', '') AS moneda_local_user";
                }
            }
        }

        return $select_precio;
    }
    function selectMonedaLocalUserByCountryID(Array $data)
    {
        $monedas_local = null;
        if(isset($data['country_id'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'country_id', $data['country_id']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                }
            }
        }

        return $monedas_local;
    }

    function mapProductos($productos){
        foreach ($productos as $x => $producto) {
            unset($producto['precio']);
            
            $producto['id'] = floatval($producto['id']);
            $producto['uid'] = floatval($producto['uid']);
            $producto['tipo'] = floatval($producto['tipo']);
            $producto['categoria'] = floatval($producto['categoria']);
            $producto['subcategoria'] = floatval($producto['subcategoria']);
            $producto['condicion_producto'] = floatval($producto['condicion_producto']);
            $producto['garantia'] = floatval($producto['garantia']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['ultima_venta'] = floatval($producto['ultima_venta']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask']= $this->maskNumber($producto['precio_usd'], 2);
            $producto['precio_local'] = floatval($producto['precio_local']);
            $producto['precio_local_mask']= $this->maskNumber($producto['precio_local'], 2);
            $producto['oferta'] = floatval($producto['oferta']);
            $producto['porcentaje_oferta'] = floatval($producto['porcentaje_oferta']);
            $producto['exposicion'] = floatval($producto['exposicion']);
            $producto['cantidad_exposicion'] = floatval($producto['cantidad_exposicion']);
            $producto['envio'] = floatval($producto['envio']);
            $producto['pais'] = floatval($producto['pais']);
            $producto['departamento'] = floatval($producto['departamento']);
            $producto['latitud'] = floatval($producto['latitud']);
            $producto['longitud'] = floatval($producto['longitud']);
            $producto['cantidad_vendidas'] = floatval($producto['cantidad_vendidas']);
            if(isset($producto['calificacion'])) $producto['calificacion'] = $this->truncNumber(floatval($producto['calificacion']), 2);

            $producto['precio_descuento_usd'] = $producto['precio_usd'];
            $producto['precio_descuento_usd_mask'] = $this->maskNumber($producto['precio_descuento_usd'], 2);
            $producto['precio_descuento_local'] = $producto['precio_local'];
            $producto['precio_descuento_local_mask'] = $this->maskNumber($producto['precio_descuento_local'], 2);

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);

            }

            $productos[$x] = $producto;
        }
        return $productos;
    }

    function mapFotosProductos(Array $fotos)
    {
        foreach ($fotos as $x => $foto) {
            $foto['id'] = floatval($foto['id']);
            $foto['id_publicacion'] = floatval($foto['id_publicacion']);
            $foto['estado'] = floatval($foto['estado']);
            $foto['fecha_creacion'] = floatval($foto['fecha_creacion']);
            $foto['fecha_actualizacion'] = floatval($foto['fecha_actualizacion']);
            $fotos[$x] = $foto;
        }

        return $fotos;
    }

    function mapeoEnvio(Array $envios)
    {
        foreach ($envios as $x => $envio) {
            $envio['id'] = floatval($envio['id']);
            $envio['id_producto'] = floatval($envio['id_producto']);
            $envio['largo'] = floatval($envio['largo']);
            $envio['ancho'] = floatval($envio['ancho']);
            $envio['alto'] = floatval($envio['alto']);
            $envio['peso'] = floatval($envio['peso']);
            $envio['estado'] = floatval($envio['estado']);
            $envio['fecha_creacion'] = floatval($envio['fecha_creacion']);
            $envio['fecha_actualizacion'] = floatval($envio['fecha_actualizacion']);
            $envios[$x] = $envio;
        }

        return $envios;
    }

    function mapDirecciones(Array $direcciones)
    {
        foreach ($direcciones as $x => $dir) {
            $dir['uid'] = floatval($dir['uid']);
            $dir['pais'] = floatval($dir['pais']);
            $dir['departamento'] = floatval($dir['departamento']);
            $dir['ciudad'] = $dir['ciudad'];
            $dir['latitud'] = floatval($dir['latitud']);
            $dir['longitud'] = floatval($dir['longitud']);
            $dir['codigo_postal'] = $dir['codigo_postal'];
            $dir['direccion'] = $dir['direccion'];
            $dir['id'] = floatval($dir['id']);
            $dir['estado'] = floatval($dir['estado']);
            $dir['activa'] = floatval($dir['activa']);
            $dir['fecha_creacion'] = addslashes($dir['fecha_creacion']);
            $dir['fecha_actualizacion'] = addslashes($dir['fecha_actualizacion']);

            $direcciones[$x] = $dir;
        }

        return $direcciones;
    }
    
    function extractKeyWords(String $string) {
        mb_internal_encoding('UTF-8');
        $stopwords = array();
        $string = preg_replace('/[\pP]/u', '', trim(preg_replace('/\s\s+/iu', '', mb_strtolower($string))));
        $matchWords = array_filter(explode(' ',$string) , function ($item) use ($stopwords) { return !($item == '' || in_array($item, $stopwords) || mb_strlen($item) <= 2 || is_numeric($item));});
        $wordCountArr = array_count_values($matchWords);
        arsort($wordCountArr);
        return array_keys(array_slice($wordCountArr, 0, 10));
    }

    function truncNumber( $number, $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }

    function maskNumber($numero, $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }

    function filter_by_value (Array $array, String $index, $value){
        $newarray = null;
        if(is_array($array) && count($array)>0) 
        {
            foreach(array_keys($array) as $key){
                if(isset($array[$key][$index])){
                    $temp[$key] = $array[$key][$index];
                    if ($temp[$key] == $value){
                        $newarray[] = $array[$key];
                        // $newarray[$key] = $array[$key];
                    }
                }
            }
        }
        return $newarray;
    }


    function getRestriccionesPublicar( Array $data )
    {
        if( !isset($data) || !isset($data['iso_code_2']) ) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        }

        $precio_usd = null;
        $array_monedas_local = null;

        // if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
        //     $monedas_local = 'USD';

        //     return array(
        //         'status' => 'status',
                
        //         'montoMinimoPublicarMonedaLocal' => $this->montoxminimoxpublicar,
        //         'montoMinimoPublicarMonedaLocalMask' => $this->truncNumber($this->montoxminimoxpublicar, 2),

        //         'montoMinimoPublicarUSD' => $this->montoxminimoxpublicar,
        //         'montoMinimoPublicarUSDMas' => $this->maskNumber($this->montoxminimoxpublicar, 2),

        //         'costo_dolar' => 1,

        //         'symbolMonedaLocal' => $monedas_local,

        //         'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,

        //         'montoMinimoPublicarConDescuentoMonedaLocal' => ($this->montoxminimoxpublicar) * ($this->porcentajexmaximoxpublicar/100),

        //         'montoMinimoPublicarConDescuentoMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar) * ($this->porcentajexmaximoxpublicar/100), 2),

        //         'montoMinimoPublicarConDescuentoUSD' => $this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100),

        //         'montoMinimoPublicarConDescuentoUSDMask' => $this->maskNumber($this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100), 2)
        //     );
        // }


        // if(isset($data['iso_code_2'])){

        //     $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            
        //     $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);

        //     if(count($array_monedas_locales) > 0){

        //         if(count($array_monedas_local) > 0) {
        //             $array_monedas_local = $array_monedas_local[0];

        //             return array(
        //                 'status' => 'success',
                        
        //                 'montoMinimoPublicarMonedaLocal' => ($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']),
        //                 'montoMinimoPublicarMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']), 2),

        //                 'montoMinimoPublicarUSD' => $this->montoxminimoxpublicar,
        //                 'montoMinimoPublicarUSDMask' => $this->maskNumber($this->montoxminimoxpublicar, 2),

        //                 'costo_dolar' => $array_monedas_local['costo_dolar'],

        //                 'symbolMonedaLocal' => $array_monedas_local['code'],

        //                 'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,

        //                 'montoMinimoPublicarConDescuentoMonedaLocal' => ($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']) * ($this->porcentajexmaximoxpublicar/100),

        //                 'montoMinimoPublicarConDescuentoMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']) * ($this->porcentajexmaximoxpublicar/100), 2),

        //                 'montoMinimoPublicarConDescuentoUSD' => $this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100),

        //                 'montoMinimoPublicarConDescuentoUSDMask' => $this->maskNumber($this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100), 2)
        //             );
        //         }else{
        //             return array('status' => 'fail', 'message'=> 'faltan datos', 'iso_code_2' => $data['iso_code_2']);
        //         }
                
        //     }else{
        //         return array('status' => 'failDivisasJSON', 'message'=> 'Verificar los montos en http://peers2win.com/js/fidusuarias.json', 'iso_code_2' => $data['iso_code_2']);
        //     }
        // }


        $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        
        $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', 'CO');

        if(count($array_monedas_locales) > 0){

            if(count($array_monedas_local) > 0) {
                $array_monedas_local = $array_monedas_local[0];

                $montoMinimoPublicarMonedaLocal             = $this->montoxminimoxpublicarxmonedaxlocal;
                $montoMinimoPublicarUSD                     = $this->montoxminimoxpublicarxmonedaxlocal / $array_monedas_local['costo_dolar'];
                $montoMinimoPublicarConDescuentoMonedaLocal = $this->montoxminimoxpublicarxmonedaxlocal;
                $montoMinimoPublicarConDescuentoUSD         = $this->montoxminimoxpublicarxmonedaxlocal / $array_monedas_local['costo_dolar'];


                return array(
                    'status' => 'success',
                    
                    'montoMinimoPublicarMonedaLocal'                 => $montoMinimoPublicarMonedaLocal,
                    'montoMinimoPublicarMonedaLocalMask'             => $this->maskNumber($montoMinimoPublicarMonedaLocal, 2),

                    'montoMinimoPublicarUSD'                         => $montoMinimoPublicarUSD,
                    'montoMinimoPublicarUSDMask'                     => $this->maskNumber($montoMinimoPublicarUSD, 2),

                    'costo_dolar'                                    => $array_monedas_local['costo_dolar'],
                    'symbolMonedaLocal'                              => $array_monedas_local['code'],

                    'porcentajeMaximoPublicar'                       => $this->porcentajexmaximoxpublicar,

                    'montoMinimoPublicarConDescuentoMonedaLocal'     => $montoMinimoPublicarConDescuentoMonedaLocal,
                    'montoMinimoPublicarConDescuentoMonedaLocalMask' => $this->maskNumber($montoMinimoPublicarConDescuentoMonedaLocal, 2),

                    'montoMinimoPublicarConDescuentoUSD'             => $montoMinimoPublicarConDescuentoUSD,
                    'montoMinimoPublicarConDescuentoUSDMask'         => $this->maskNumber($montoMinimoPublicarConDescuentoUSD, 2)
                );
            }else{
                return array('status' => 'fail', 'message'=> 'faltan datos', 'iso_code_2' => $data['iso_code_2']);
            }
            
        }else{
            return array('status' => 'failDivisasJSON', 'message'=> 'Verificar los montos en http://peers2win.com/js/fidusuarias.json', 'iso_code_2' => $data['iso_code_2']);
        }

        return array('status' => 'fail', 'message'=> 'faltan datos', 'iso_code_2' => $data['iso_code_2']);
    }
    
    function validarPrimerArticulo( Array $data, Int $permisos )
    {

        // Cumpliendo con el sistema de correos
        // Debemos validar si el usuario crea su primera subasta.
        $producto = [];
        $subasta  = [];
        // if ( intval( $data['empresa'] ) == 1 ) {
            
            parent::conectar();
            $misProductos = parent::consultaTodo(" SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            $misProductosRevision = parent::consultaTodo(" SELECT * FROM buyinbig.productos_revision WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();


            // parent::addLogJB("----+> 2 CONDICIONAL: " . (COUNT($misProductos) == 1? "SI" : "NO"));
            // parent::addLogJB("----+> 3 CONDICIONAL: " . ($permisos == 1? "SI" : "NO"));

            // if ( COUNT($misProductos) == 1 && $permisos == 1) {

                // parent::addLogJB("----+> 4 CONDICIONAL [ SI ]: " . ( COUNT($misProductos) == 1 && $permisos == 1));

                // $producto = $misProductos[0];
                // validamos si es subasta o no.
                // if ( $producto['tipoSubasta'] > 0) {
                //     // SI es subasta o no.
                //     parent::conectar();
                //     $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                //     parent::cerrar();

                //     if ( count($misSubastas) > 0 ) {
                //         $subasta = $misSubastas[ 0 ];
                //     }
                // }

                if ( COUNT($misProductos) == 0 && COUNT($misProductosRevision) == 1 ){

                    $result_productos = $this->getProductoPrimeraPublicacion($data);

                    // parent::addLogJB("----+> 3.1 CONDICIONAL: " . json_encode($result_productos));

                    if($result_productos['status'] == 'success'){
                        $this->miPrimeraPublicacion([
                            'status'   => 'success',
                            'producto' => $result_productos['producto'],
                            'subasta'  => $result_productos['subasta']
                        ]);
                    }
                }
            // }else{
                // en el caso que sea una empresa sin permiso y ya tenga productos activados con anterioridad
                if ( COUNT($misProductos) != 1 && $permisos != 1) {
                    $this->enviodecorreodepublicacion_product( $data );
                }
            // }
        // }
    }

    function getProductoPrimeraPublicacion($data){

        $producto = [];
        $subasta  = [];

        $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));

        if($permisosRespuesta){
            parent::conectar();
            $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();
            if($misProductos && count($misProductos) > 0){
                $producto = $misProductos[0];
                // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                    // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas) > 0 ) {
                        $subasta = $misSubastas[0];
                    }
                }
                return Array(
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                );
            }
        }else{
            $producto_revision = [];
            $subasta_revision = [];

            parent::conectar();
            $misProductos_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_revision WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

            if($misProductos_revision && count($misProductos_revision) > 0){
                $producto_revision = $misProductos_revision[0];

                if($producto_revision['tipoSubasta'] > 0){
                    parent::conectar();
                    $misSubastas_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto_revision[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas_revision) > 0 ) {
                        $subasta_revision = $misSubastas_revision[0];
                    }
                }

                return Array(
                    'status'   => 'success',
                    'producto' => $producto_revision,
                    'subasta'  => $subasta_revision
                );
            }
        }

        return Array(
            'status'   => 'fail',
            'producto' => [],
            'subasta'  => []
        );
    }



    function miPrimeraPublicacion( Array $data ){
        $data_producto = $data["producto"];
        $data_subasta  = $data["subasta"];

        $data_empresa  =  $this->datosUserGeneral2([
            'uid'     => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        if ($data_subasta == null){
            $data_subasta = []; 
        }

        // parent::addLogJB("----+> 5 CONDICIONAL [ miPrimeraPublicacion / JSON ]: " . json_encode(Array( "data_producto" => $data_producto, "data_subasta" => $data_subasta, "data_empresa" => $data_empresa["data"])));

        $this->htmlEmailprimer_producto_empresa( $data_producto, $data_subasta, $data_empresa["data"]);
    }

    public function htmlEmailprimer_producto_empresa(Array $data_producto, Array $data_subasta, Array $data_empresa){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo4tuprimerproducto.html");
        
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();

        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
        $categoria_product=$categoria_product[0];

        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }else{
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }

        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$data_empresa['nombre'], $html);
      
        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
      
        $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        
        
         
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
        
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_empresa["uid"]."&act=0&em=".$data_empresa["empresa"], $html); 

        $para      = $data_empresa['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans104_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

        // parent::addLogJB("----+> 6. [ htmlEmailprimer_producto_empresa ]: " . json_encode($response));

        return $response;
        
    }

    public function htmlEmailprimer_producto_empresa_revision(Array $data_producto, Array $data_subasta, Array $data_empresa){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo4revision.html");
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();

 
     
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];


       
    

       
        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$data_empresa['nombre'], $html);
        
        $html = str_replace("{{trans147_}}", $json->trans147_, $html);
        $html = str_replace("{{trans148_}}", $json->trans148_, $html);

        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
      //  $html = str_replace("{{trans11}}", $json->trans11, $html); 
         $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
         $html = str_replace("{{trans14}}",$json->trans14, $html);
         $html = str_replace("{{trans15}}",$json->trans15, $html);
        
        
         
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_empresa["uid"]."&act=0&em=".$data_empresa["empresa"], $html); 

        $para      = $data_empresa['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans104_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function datosUserGeneral2( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }


    function enviodecorreodepublicacion_product(Array $data ){
        // Cumpliendo con el sistema de correos
        $producto = [];
        $subasta = [];

        $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));

        if($permisosRespuesta){
            parent::conectar();
            $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();
            if($misProductos && count($misProductos) > 0){
                $producto = $misProductos[0];
                // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                    // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas) > 0 ) {
                        $subasta = $misSubastas[0];
                    }
                }
                $this->preparar_data_email_publicacion([
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                ]);
            }
        }else{
            $producto_revision = [];
            $subasta_revision = [];

            parent::conectar();
            $misProductos_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_revision WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

            if($misProductos_revision && count($misProductos_revision) > 0){
                $producto_revision = $misProductos_revision[0];

                if($producto_revision['tipoSubasta'] > 0){
                    parent::conectar();
                    $misSubastas_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto_revision[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas_revision) > 0 ) {
                        $subasta_revision = $misSubastas_revision[0];
                    }
                }

                $this->preparar_data_email_publicacion_revision([
                    'status'   => 'success',
                    'producto' => $producto_revision,
                    'subasta'  => $subasta_revision
                ]);
            }
        }

    }

    function preparar_data_email_publicacion_revision(Array $data){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);

        if (empty($data_subasta)){
            $data_subasta=[]; 
        }else{
            if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales 
                $this->htmlEmailpublicacion_subasta_revision( $data_producto, $data_subasta, $data_user["data"]);  
            }else if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]!= 6){
                $this->htmlEmailpublicacion_subasta_premium_revision( $data_producto, $data_subasta, $data_user["data"]);
            }  
        }
        $this->htmlEmailpublicacion_product_revision( $data_producto, $data_subasta, $data_user["data"]);
        
    }

    function preparar_data_email_publicacion(Array $data){
        $data_producto = $data["producto"];
        $data_subasta  = $data["subasta"];

        $data_user     = $this->datosUserGeneral2([
            'uid'     => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);

        if (empty($data_subasta)){
            $data_subasta=[]; 
        }else{
            if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales 
                $this->htmlEmailpublicacion_subasta( $data_producto, $data_subasta, $data_user["data"]);  
            }else if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]!= 6){
               $this->htmlEmailpublicacion_subasta_premium( $data_producto, $data_subasta, $data_user["data"]);
            }  
        }

        $this->htmlEmailpublicacion_product( $data_producto, $data_subasta, $data_user["data"]); 
    }


    public function htmlEmailpublicacion_product(Array $data_producto, Array $data_subasta, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo1.html");
        
            $categorias=$this->get_categoria_product(); 
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];

        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans87_}}",$json->trans243, $html);
            $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans87_}}",$json->trans87_, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$data_producto["titulo"], $html);
      
        $html = str_replace("{{trans93_}}", $json->trans93_, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);
        
     
         $html = str_replace("{{trans89_}}",$json->trans89_, $html);
         $html = str_replace("{{trans94_}}",$json->trans94_, $html);
        
        
         $html = str_replace("{{trans11}}",$json->trans11, $html);
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans105_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);


        return $response;
        
    }


    public function htmlEmailpublicacion_product_revision(Array $data_producto, Array $data_subasta, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/corre1revision.html");

        $categorias=$this->get_categoria_product(); 
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
        $categoria_product=$categoria_product[0];

        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans146_}}",$json->trans146_, $html);
            $html = str_replace("{{trans87_}}","", $html);
            $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }else{

            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans146_}}",$json->trans146_, $html);
            $html = str_replace("{{trans87_}}",$json->trans87_, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }

        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);

        $html = str_replace("{{trans244}}", $json->trans244, $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$data_producto["titulo"], $html);

        $html = str_replace("{{trans93_}}", $json->trans93_, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);


        $html = str_replace("{{trans147_}}",$json->trans147_, $html);
        $html = str_replace("{{trans94_}}",$json->trans94_, $html);


        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans153_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";

        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);


        return $response;
    }


    function get_categoria_product(){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
        return $categorias;
    }

    function enviar_correo_edicion(Array $data){
        switch ( intval($data['tipo_edicion_correo'])) {
            case 13:
                $this->enviarcorreo_actualizo_expo($data); 
                break;
            case 20: //pauso 
                $this->enviarcorreo_actualizo_pauso($data); 
                break;
            case 21: //elimino
                $this->enviarcorreo_actualizo_elimino($data); 
                break;
            default:
                # code...
                break;
        }
    }


    function enviarcorreo_actualizo_expo(Array $data){
        $data_user  =  $this->datosUserGeneral2([
            'uid'     => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto = $this->get_product_por_id([
            'uid'     => $data["uid"],
            'id'      => $data["id"],
            'empresa' => $data["empresa"]
        ]);

        $producto_anterior   = $data["porducto_antes"];
        $exposicion_anterior = intval("" . $producto_anterior["exposicion"]);
        $exposicion_nueva    = intval("" . $producto[0]["exposicion"]);



        if(($exposicion_anterior == 0 || $exposicion_anterior == 1) &&  $exposicion_nueva == 2){
            $this->htmlEmailedicionexposicion_gra_to_clasica($producto[0], $data_user["data"]);

        }else if(($exposicion_anterior == 0 || $exposicion_anterior == 1) &&  $exposicion_nueva == 3){
            $this->htmlEmailedicionexposicion_gra_to_premium($producto[0], $data_user["data"]);

        }else if($exposicion_anterior == 2 &&  $exposicion_nueva == 3){
            $this->htmlEmailedicionexposicion_cla_to_premium($producto[0], $data_user["data"]);

        }
    }



    public function htmlEmailedicionexposicion_gra_to_clasica(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo2.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
      
        $html = str_replace("{{trans_07_icon_subasta_brand}}", $json->trans_07_icon_subasta_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{foto_product}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans_09_brand}}",$json->trans_09_brand, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);
        
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{trans130_brand}}",$json->trans130_brand, $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function htmlEmailedicionexposicion_gra_to_premium(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo3.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
        $html = str_replace("{{trans130_brand}}", $json->trans130_brand, $html);
        $html = str_replace("{{trans131_brand}}", $json->trans131_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);

        
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    public function htmlEmailedicionexposicion_cla_to_premium(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo4.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
        $html = str_replace("{{trans113_brand}}", $json->trans113_brand, $html);
        $html = str_replace("{{trans129_brand}}", $json->trans129_brand, $html);
        $html = str_replace("{{trans95_}}", $json->trans95_, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{trans97_}}", $json->trans97_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        

        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
    
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]' ORDER BY id DESC;");
        parent::cerrar();
        return $misProductos;
    }

    function envio_de_correo_general_ediccion(Array $data){
        $data_subasta=[];
        $es_subasta=0;


        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        
         if($producto[0]["tipoSubasta"]!= "0"){
            $es_subasta=1;

            $data_subasta=  $this->get_data_subasta_por_id_producto([
                'id' => $producto[0]['id']
            ]);

            $data_subasta= $data_subasta[0]; 
        }
    
        $this->htmlEmailactualizacion_general_product($producto[0], $data_user["data"], $data_subasta, $es_subasta); 
        
    }


    public function htmlEmailactualizacion_general_product(Array $data_producto, Array $data_user, Array $data_subasta, Int $es_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo15publicacionctualizada.html");
        
        if($es_subasta==1){
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        

       
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans108_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function htmlEmailactivacion_producto(Array $data_producto, Array $data_user, Array $data_subasta, Int $es_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo5activo_product.html");
        
        if($es_subasta==1){
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
           
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }



        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans149_}}",$json->trans149_, $html);
        $html = str_replace("{{trans150_}}",$json->trans150_, $html);

        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans156_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }



    function enviarcorreo_actualizo_pauso($data){
        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"]
        ]);
        if ( count( $producto ) == 0) {
            return array(
                'status'  => 'NoExiteProducto',
                'message' => 'No se hallo el articulo'
            );
            
        }
        
      $this->htmlEmailpausa_publi($producto[0], $data_user["data"]); 

    }


    public function htmlEmailpausa_publi(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo16publicacionpausada.html");
        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans81}}", $json->trans81, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans82}}", $json->trans82, $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{foto_producto_pausada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans76}}",$json->trans76, $html);
        $html = str_replace("{{trans77}}",$json->trans77, $html);
        

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans110_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function enviarcorreo_actualizo_elimino(Array $data)
    {
        $data_user  =  $this->datosUserGeneral2([
            'uid'     => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto = $this->get_product_por_id([
            'uid'      => $data["uid"],
            'id'       => $data["id"],
            'empresa'  => $data["empresa"]
        ]);
        if ( count( $producto ) == 0) {
            return array(
                'status'  => 'NoExiteProducto',
                'message' => 'No se hallo el articulo'
            );
            
        }
        
      $this->htmlEmailelimino($producto[0], $data_user["data"]); 
    }

    public function htmlEmailelimino(Array $data_producto, Array $data_user){

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo17publicacioneliminada.html");
        
        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);

        $html = str_replace("{{trans78}}", $json->trans78, $html);
        $html = str_replace("{{trans66}}", $json->trans66, $html);

        $html = str_replace("{{publicacion_titulo_eliminada}}",$data_producto['titulo'] , $html);
        $html = str_replace("{{trans79}}", $json->trans79, $html);
        
        $html = str_replace("{{trans80}}", $json->trans80, $html);

        $html = str_replace("{{foto_producto_eliminado_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_vender}}",$json->link_to_vender, $html);
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans112_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array('email' => $para, 'titulo' => $titulo, 'mensaje' => $mensaje1, 'cabeceras' => $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function htmlEmailpublicacion_subasta(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo13subastanasbi.html");
  
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$json->trans89, $html);
        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89_}}",$json->trans89_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans88_}}",$json->trans88_, $html);
        
        
        
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        // $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        

        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

        $html = str_replace("{{trans115_}}",$json->trans115_, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        
        $html = str_replace("{{trans90_}}",$json->trans90_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function htmlEmailpublicacion_subasta_revision(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo2revision.html");
  
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        // $html = str_replace("{{trans89}}",$json->trans89, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89_}}",$json->trans89_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans88_}}",$json->trans88_, $html);
        
        
        
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        

        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans147_}}",$json->trans147_, $html);
        $html = str_replace("{{trans115_}}",$json->trans115_, $html);
        
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function htmlEmailpublicacion_subasta_premium(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();    
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo14subastanasbipremium.html");
        
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans87}}",$json->trans87, $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        

        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans90}}",$json->trans90, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);

        $html = str_replace("{{foto_producto_subasta_premiun_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans92}}",$json->trans92, $html);
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        

        
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

       
        
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function htmlEmailpublicacion_subasta_premium_revision(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();    
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo3revision.html");
        
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans87}}",$json->trans87, $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        

        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans147_}}",$json->trans147_, $html);

        $html = str_replace("{{foto_producto_subasta_premiun_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans92}}",$json->trans92, $html);
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        

        
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

       
        
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }







    // INICIO COLORES TALLAS, REVISION  
    function crearColor(Array $data)
    {


        if(!isset($data) || !isset($data['nombre_es']) || !isset($data['nombre_en']) || !isset($data['nombre_corto']) || !isset($data['hexadecimal'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => $data);
        }

        parent::conectar();
        // $categorias = parent::consultaTodo($selectcategoria); // Leer datos.

        $esta_color = parent::consultaTodo("SELECT * FROM productos_colores WHERE hexadecimal = '$data[hexadecimal]'");
        $coloresquery = NULL; 
        if(!$esta_color){
            $coloresquery = parent::queryRegistro("INSERT INTO productos_colores(nombre_es,nombre_en,nombre_corto,hexadecimal) values('$data[nombre_es]','$data[nombre_en]','$data[nombre_corto]','$data[hexadecimal]')"); // Insert
        }else{
            return array("status"=>"yaExiste","mensaje"=>"ya existe el color");
        }
        
        parent::cerrar();
        
        if ($coloresquery) {
            return array('status' => 'success', 'message'=> 'color creado', 'data' => $data);
        }

        return array('status' => 'fail', 'message'=> 'no fue posible crear el color', 'data' => $data);
    }

    function getColores(Array $data){
        $colores = NULL;
            if(isset($data['ya_elegidos']) && count($data['ya_elegidos'])>0){

                $restricciones = '(';
                for ($i = 0; $i<count($data['ya_elegidos']); $i++) {
                    if($i == count($data['ya_elegidos']) -1){
                        $restricciones = $restricciones."'".$data['ya_elegidos'][$i]."')";
                    }else{
                        $restricciones = $restricciones."'".$data['ya_elegidos'][$i]."',";
                    } 
                }
    
                parent::conectar();
                $colores = parent::consultaTodo("SELECT * FROM productos_colores 
                WHERE productos_colores.hexadecimal NOT IN ".$restricciones." ORDER BY id DESC");
                parent::cerrar();
            }else{
                parent::conectar();
                $colores = parent::consultaTodo("SELECT * FROM productos_colores ORDER BY id DESC");
                parent::cerrar(); 
            }
            
            $respuesta = [];
            if($colores){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($colores)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $colores[$i]);
                        }
                    }
                }
                $num_paginas = count($colores)/$numXpagina;
                $num_paginas = ceil($num_paginas);
                
                return array('status' => 'success', 'message'=> 'colores', 'data' => $respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
            }
    
            return array('status' => 'fail', 'message'=> 'error al obtener los colores');
        
    }


    function crearTalla(Array $data){
        if(!isset($data) || !isset($data['nombre_es']) || !isset($data['nombre_en']) || !isset($data['tipo'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => $data);
        }

        parent::conectar();
        $tallasquery = parent::queryRegistro("INSERT INTO productos_tallas(nombre_es,nombre_en,tipo) values('$data[nombre_es]','$data[nombre_en]','$data[tipo]')"); // Insert
        parent::cerrar();

        if ($tallasquery) {
            return array('status' => 'success', 'message'=> 'talla creada', 'data' => $data);
        }

        return array('status' => 'fail', 'message'=> 'no fue posible crear la talla', 'data' => $data);
    }

    function getTallas(){
        parent::conectar();
        $tallas = parent::consultaTodo("SELECT * FROM buyinbig.productos_tallas;");
        parent::cerrar();

        if( count($tallas) > 0 ){
            return array("status" => "success", "mensaje" => "ordenado", "data"=> $tallas);
        }
        // if($tallas){
        //     $tallas_numericas = [];
        //     $tallas_letras_aux = [];
        //     foreach($tallas as $talla){
        //         if(is_numeric($talla['nombre_es'])){
        //             array_push($tallas_numericas, $talla);
        //         }else{
        //             array_push($tallas_letras_aux, $talla);
        //         }
        //     }

        //     for($i = 0; $i<count($tallas_numericas); $i++){
        //         for($j = 0; $j<count($tallas_numericas); $j++){
        //             if(intval($tallas_numericas[$i]['nombre_es']) < intval($tallas_numericas[$j]['nombre_es'])){
        //                 $aux = $tallas_numericas[$j];
        //                 $tallas_numericas[$j] = $tallas_numericas[$i];
        //                 $tallas_numericas[$i] = $aux;
        //             }
        //         }
        //     }

        //     $orden_tallas_letras = array("XS" => 0,"S" => 1, "M" => 2, "L" => 3, "XL" => 4, "XXL" => 5, "No aplica" => 6); // PARA ORGANIZAR LAS TALLAS 
        //     $tallas_letras = array_fill(0,count($tallas_letras_aux),0); 
        //     foreach($tallas_letras_aux as $talla){
        //         $tallas_letras[ $orden_tallas_letras[ $talla['nombre_es'] ] ] = $talla;
        //     }

        //     return array("status" => "success","mensaje" => "ordenado","data"=>array_merge($tallas_letras,$tallas_numericas));
        // }
        return array("status" => "fail", "mensaje" => "error al obtener las tallas");
    }

    function guardarProductoColoresTallas(Array $data){
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['id_tallas']) || !isset($data['id_colores']) || !isset($data['cantidad'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos guardar producto colores tallas', 'data' => $data);
        }   
        $insertxtalla= 
        "INSERT INTO detalle_producto_colores_tallas (
            id_producto,
            id_tallas,
            id_colores,
            cantidad,
            sku
        ) values(
            '$data[id_producto]',
            '$data[id_tallas]',
            '$data[id_colores]',
            '$data[cantidad]',
            '$data[sku]'
        )";
        parent::conectar();
        $producto_colores_tallas = parent::queryRegistro($insertxtalla); // Insert
        parent::cerrar();
    }

    public function publicarVersion2( Array $data ){
        if(!isset($data) || !isset($data['garantia_tiempo_num']) || !isset($data['garantia_tiempo_unidad_medida']) || !isset($data['tipo_de_envio_alcance']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);


        // parent::addLogJB("---+> request PUBLICAR: " . json_encode($data));


        $request = null;
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);
        $data = $this->mapeoVender($data);
        $producto = $data;

        $envio = $data['direccion_envio'];
        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});
        $textfullcompleto = $producto['producto'].' '.$producto['marca'].' '.$producto['modelo'].' '.$producto['titulo'].' '.$producto['categoria']['CategoryName'].' '.$producto['subcategoria']['CategoryName'];
        
        $producto['keywords'] = implode(',', $this->extractKeyWords($textfullcompleto));
        $producto['categoria'] = $producto['categoria']['CategoryID'];
        $producto['subcategoria'] = $producto['subcategoria']['CategoryID'];
        
        
        $empresa = 0;
        if($producto['empresa'] == true) $empresa = 1;


        $precio_usd = null;
        $array_monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);
                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);
                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
            }else{
                $precio_usd = $data['precio'];
            }
        }

        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        if ( floatval($data['porcentaje_oferta']) < 0 || floatval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd;// - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],2),
                    'symbolMonedaLocal' => $array_monedas_local['code'],
                    'data' => null
                );
                
            }
        }


        $producto['precio_usd'] = $precio_usd;
        $producto['moneda_local'] = $monedas_local;

        // return $precio_usd;

        $cobrar = null;

        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1) {
            $producto['subasta']['precio_usd'] = $precio_usd;
            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            $producto['subasta']['precio'] = $this->truncNumber(($producto['subasta']['precio_usd'] / $precios[$producto['subasta']['moneda']]), 6);
            unset($nodo);

            $validarsubasta = $this->validarTipoSubasta(
                $producto['subasta'],
                $data['iso_code_2']
            );

            if($validarsubasta['status'] == 'fail') return $validarsubasta;
            $producto['subasta']['datos_subasta'] = $validarsubasta['data'];

            // return $producto['subasta'];

            parent::addLog( "DATOS SUBASTA ==============>".json_encode($producto['subasta']['datos_subasta']) );

            if ( $producto['subasta']['datos_subasta']['id'] != 6 ) {

                $tickets = $this->verTicketsUsuario([
                    'uid'      => $producto['uid'],
                    'empresa'  => $empresa,
                    'plan'     => $producto['subasta']['datos_subasta']['id'],
                    'cantidad' => 1
                ]);

                if($tickets['status'] == 'fail') return $tickets;
                $tickets = $tickets['data'];

                $cantidad = 1;

                if( $producto['subasta']['datos_subasta']['id'] == 5 ) {
                    $cantidad = 2;
                }
    
                $cobrar = $this->cobrarTicekts([
                    'tickets'  => $tickets, 
                    'cantidad' => $cantidad,
                    'fecha'    => $fecha
                ]);

                if($cobrar['status'] != 'success') return $cobrar;
            }
        }

        $tipoSubasta = 0;
        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
            $tipoSubasta = intval($producto['subasta']['tipo']);
        }

        $genero = 3;
        if(isset($data['genero'])){
            $genero = $data['genero'];
        }

            
        parent::conectar();
        $insertarproducto = "INSERT INTO productos_revision(
            uid,
            tipo_envio_gratuito,
            id_tiempo_garantia,
            num_garantia,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            empresa,
            url_video,
            genero,
            tiene_colores_tallas,
            id_productos_revision_estados,
            tiempo_estimado_envio_num,
            tiempo_estimado_envio_unidad
        )
        VALUES(
            '$producto[uid]',
            '$producto[tipo_de_envio_alcance]',
            '$producto[garantia_tiempo_unidad_medida]',
            '$producto[garantia_tiempo_num]',
            '$producto[tipo]',
            '$tipoSubasta',
            '$producto[producto]',
            '$producto[marca]',
            '$producto[modelo]',
            '$producto[titulo]',
            '$producto[descripcion]',
            '$producto[categoria]',
            '$producto[subcategoria]',
            '$producto[condicion_producto]',
            '$producto[garantia]',
            '1',
            '$fecha',
            '$fecha',
            '$fecha',
            '$producto[cantidad]',
            '$producto[moneda_local]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[oferta]',
            '$producto[porcentaje_oferta]',
            '$producto[porcentaje_tax]',
            '$producto[exposicion]',
            '$producto[cantidad_exposicion]',
            '$producto[envio]',
            '$envio[id]',
            '$envio[pais]',
            '$envio[departamento]',
            '$envio[ciudad]',
            '$envio[latitud]',
            '$envio[longitud]',
            '$envio[codigo_postal]',
            '$envio[direccion]',
            '$producto[keywords]',
            0,
            '$empresa',
            '$producto[url_video]',
            '$genero',
            '$data[tiene_colores_tallas]',
            0,
            '$producto[tiempo_estimado_envio_num]',
            '$producto[tiempo_estimado_envio_unidad]'
        )";

        $productoqueryRevision = parent::queryRegistro($insertarproducto);
        parent::cerrar();
        $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));

        if($permisosRespuesta){
            parent::conectar();
            $insertarproducto2 = "INSERT INTO productos(
                id,
                uid,
                tipo_envio_gratuito,
                id_tiempo_garantia,
                num_garantia,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas,
                tiempo_estimado_envio_num,
                tiempo_estimado_envio_unidad
            )
            VALUES(
                '$productoqueryRevision',
                '$producto[uid]',
                '$producto[tipo_de_envio_alcance]',
                '$producto[garantia_tiempo_unidad_medida]',
                '$producto[garantia_tiempo_num]',
                '$producto[tipo]',
                '$tipoSubasta',
                '$producto[producto]',
                '$producto[marca]',
                '$producto[modelo]',
                '$producto[titulo]',
                '$producto[descripcion]',
                '$producto[categoria]',
                '$producto[subcategoria]',
                '$producto[condicion_producto]',
                '$producto[garantia]',
                '1',
                '$fecha',
                '$fecha',
                '$fecha',
                '$producto[cantidad]',
                '$producto[moneda_local]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[oferta]',
                '$producto[porcentaje_oferta]',
                '$producto[porcentaje_tax]',
                '$producto[exposicion]',
                '$producto[cantidad_exposicion]',
                '$producto[envio]',
                '$envio[id]',
                '$envio[pais]',
                '$envio[departamento]',
                '$envio[ciudad]',
                '$envio[latitud]',
                '$envio[longitud]',
                '$envio[codigo_postal]',
                '$envio[direccion]',
                '$producto[keywords]',
                0,
                '$empresa',
                '$producto[url_video]',
                '$genero',
                '$data[tiene_colores_tallas]',
                '$data[tiempo_estimado_envio_num]',
                '$data[tiempo_estimado_envio_unidad]'
            )";
            parent::queryRegistro($insertarproducto2);

            parent::query("DELETE FROM productos_revision WHERE id = '$productoqueryRevision'");

            parent::cerrar();
        }

        $productoquery = $productoqueryRevision;
        
        if ($productoquery) {
            $fotos = $this->subirFotosProducto($fotos, $fecha, $productoquery);
            if(isset($data['url_video']) && !empty($data['url_video'])) {
                $imgconcat = $fecha.'_portada_video';

                $url = $this->uploadImagen([
                    'img' => $data['portada_video'],
                    'ruta' => '/imagenes/publicaciones/'.$productoquery.'/'.$imgconcat.'.png'
                ]);

                if($permisosRespuesta){
                    parent::conectar();
                    parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }else{
                    parent::conectar();
                    parent::query("UPDATE productos_revision SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }
            }


            if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
                if(isset($cobrar)) $this->actualizarHistoricoTickets($cobrar['data'], $productoquery);

                $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
            }


            $envio = $this->insertarEnvio($producto['detalle_envio'], $fecha, $productoquery);

            
            // if ( intval( $empresa ) == 1 ) {
                // Evaluamos si es la primera publicación.
                // parent::addLogJB("----+> 1 ANTES DE [validarPrimerArticulo].");
                $this->validarPrimerArticulo( $producto, $permisosRespuesta );
            // }else{
                // $this->enviodecorreodepublicacion_product( $producto );
               
            // }

            if(isset($data['tiene_colores_tallas']) && $data['tiene_colores_tallas'] == true){
                
                $array_llenar_detalles = NULL;
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['tallas']) && isset($pair['color_id'])){
                        foreach($pair['tallas'] as $talla){
                            $array_llenar_detalles = array(
                                'id_producto' => $productoquery,
                                'id_tallas' => $talla['id_talla'],
                                'id_colores' => $pair['color_id'],
                                'cantidad' => $talla['cantidad']
                            );
                            if(isset($talla['sku'])){
                                $array_llenar_detalles['sku'] = $talla['sku'];
                            }else{
                                $array_llenar_detalles['sku'] = '';
                            }
                            $this->guardarProductoColoresTallas($array_llenar_detalles);
                        }
                    }
                }
            }
            if($permisosRespuesta){  
                $request = array(
                    'status' => 'success',
                    'message'=> 'producto creado',
                    'revision' => 0,
                    'data' => $productoquery
                );
            }else{
                $request = array(
                    'status' => 'success',
                    'message'=> 'tu producto ha sido enviado a revisión',
                    'revision' => 1,
                    'data' => $productoquery
                ); 
            }
            
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no se pudo crear el producto',
                'data' => null
            );
        }
        return $request;
    }

    function verificar_usuario_permisos_publicar(Array $data){
        parent::conectar();
        $tiene_permisos = parent::consultaTodo("SELECT * FROM productos_permisos_publicar where uid = '$data[uid_usuario]' AND empresa = '$data[empresa]'");
        parent::cerrar();
        if($tiene_permisos){
            if(count($tiene_permisos) > 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function verificarPermisoPublicarUsuario(Array $data){
        parent::conectar();
        $tiene_permisos = parent::consultaTodo("SELECT * FROM productos_permisos_publicar where uid = '$data[uid]' AND empresa = '$data[empresa]'");
        parent::cerrar();
        if($tiene_permisos){
            if(count($tiene_permisos) > 0){
                return array( "status" => "success" );
            }else{
                return array( "status" => "fail" );
            }
        }else{
            return array( "status" => "fail" );
        }
    }

    function productos_espera_verificacion(Array $data){
        parent::conectar();
        $enEspera = parent::consultaTodo(
            "SELECT pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
                pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
                pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
                pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
                pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
                pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
                pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
                pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
                pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
                FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
                ON pr.id_productos_revision_estados = pre.id where pr.uid = '$data[id_usuario]' 
                AND pr.id_productos_revision_estados = 0 ORDER BY pr.id;"
        );
        parent::cerrar();

        if($enEspera){
            $numXpagina = 3;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($enEspera)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        $enEspera[$i]['precio_local_mask'] = $this->maskNumber($enEspera[$i]['precio'], 2);
                        $enEspera[$i]['precio_local_usd_mask'] = $this->maskNumber($enEspera[$i]['precio_usd'], 2);
                        $enEspera[$i]['estado'] = 3;
                        $enEspera[$i]['estado_descripcion'] = 'Revisión';
                        $enEspera[$i]['visitas'] = 0;
                        array_push($respuesta, $enEspera[$i]);
                    }
                }
            }
            $num_paginas = count($enEspera)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success", "mensaje"=> "productos en espera por validacion", "data" => $respuesta, "pagina" => $data['pag'], "total_paginas" => $num_paginas);
        }else{
            return array("status" => "fail", "mensaje" => "error no se encontraron datos");
        }
    }

    function envio_correo_producto_activado(Array $data, Array $data_subasta, Int $es_subasta){
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]); 
        $this->htmlEmailactivacion_producto($data,$data_user['data'],$data_subasta, $es_subasta);
    }

    function envio_correo_aceptado_product(Array $data_producto){
        $data_subasta=[];
        $es_subasta=0;  
        if($data_producto['tipoSubasta'] != "0"){
            $es_subasta=1; 

            $data_subasta=  $this->get_data_subasta_por_id_producto([
                'id' => $data_producto['id']
            ]);
            $data_subasta= $data_subasta[0]; 
        }

        if($data_producto['empresa'] == "1"){
            $this->validar_primero_revision($data_producto);
        } 
        $this->envio_correo_producto_activado($data_producto, $data_subasta, $es_subasta);
    }

    function validar_primero_revision(Array $data){
        $producto = [];
        $subasta = [];
        if ( intval( $data['empresa'] ) == 1 ) {
            
            parent::conectar();
            $misProductos = parent::consultaTodo(" SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

           if ( count($misProductos) == 1 ) {

                $producto = $misProductos[0];
                // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                    // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas) > 0 ) {
                        $subasta = $misSubastas[ 0 ];
                    }
                }
                $this->miPrimeraPublicacion([
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                ]);
           }
        }
    }

    function miPrimeraPublicacionEmpresaRevision( Array $data ){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_empresa=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        if ($data_subasta == null){
            $data_subasta=[]; 
        }
        $this->htmlEmailprimer_producto_empresa_revision( $data_producto, $data_subasta, $data_empresa["data"]); 
    }

    function prueba_de_guardado(Array $data){
        if(isset($data['variaciones'])){
            // $id_producto = 46;
            if($data['variaciones'] == 1){
                $array_llenar_detalles = NULL;
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['tallas']) && isset($pair['id_color'])){
                        foreach($pair['tallas'] as $talla){
                            $array_llenar_detalles = array(
                                // 'id_producto' => $id_producto,
                                'id_producto' => $data['id_producto'],
                                'id_tallas' => $talla['id_talla'],
                                'id_colores' => $pair['id_color'],
                                'cantidad' => $talla['cantidad']
                            );
                            if(isset($pair['sku'])){
                                $array_llenar_detalles['sku'] = $pair['sku'];
                            }
                            $this->guardarProductoColoresTallas($array_llenar_detalles);
                        }
                    }
                }
            }
        }
        return array('mensaje' => 'terminado');
    }
    
    // WBS PARA ACTUALIZAR DATOS DE COLORES Y TALLAS
    function prueba_de_actualizacion(Array $data){
        if(isset($data)){

            if(!isset($data['coloresXtallas']) || !isset($data['id_producto'])){
                return array("status" => "error", "mensaje" => "faltan datos");
            }

            parent::conectar();
            $producto = parent::consultaTodo("SELECT * FROM productos WHERE id = '$data[id_producto]'");
            
            
            parent::cerrar();
            

            $cantidad_post = 0;
            $cantidad_post_aux = 0;
            if($producto[0]['tiene_colores_tallas'] == 1){
                $cantidad_post = 0;
            }else{
                $cantidad_post_aux = $producto[0]['cantidad'];
                $cantidad_post = $producto[0]['cantidad'];
            }
            $realizar_op = false;
            
            if(isset($producto) && count($producto) > 0){
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['eliminar_color']) && ($pair['eliminar_color'] == true || $pair['eliminar_color'] == 1)){ // PARA ELIMINAR UN COLOR Y LAS TALLAS ASOCIADAS A EL
                        foreach($pair['tallas'] as $talla){
                            if(isset($talla['id_pair'])){
                                parent::conectar();
                                parent::query("DELETE FROM detalle_producto_colores_tallas WHERE id = '$talla[id_pair]' ");
                                parent::cerrar();
                            }
                        }
                    }else{
                        foreach($pair['tallas'] as $talla){
                            if(isset($talla['id_pair'])){
                                if(isset($talla['eliminar_pair']) && ($talla['eliminar_pair'] == true || $talla['eliminar_pair'] == 1)){ // PARA ELIMINAR UNA CONFIGURACION COLOR - TALLA
                                    // $cantidad_post = $cantidad_post - $talla['cantidad'];
                                    parent::conectar();
                                    parent::query("DELETE FROM detalle_producto_colores_tallas WHERE id = '$talla[id_pair]' ");
                                    parent::cerrar();
                                }else{  // SI NO VIENE EL dato eliminar_pair ENTONCES SE VA A ACTUALIZAR
                                    $cantidad_post = $cantidad_post + $talla['cantidad'];
                                    parent::conectar();
                                    parent::query("UPDATE detalle_producto_colores_tallas SET id_tallas = '$talla[id_talla]', id_colores = '$pair[id_color]', cantidad = '$talla[cantidad]', sku = '$talla[sku]' WHERE id = '$talla[id_pair]'");
                                    parent::cerrar();
                                }
                                
                            }else{ // SI NO VIENE EL id_pair
                                $realizar_op = true;
                                parent::conectar();
                                $consulta = parent::consultaTodo("SELECT * FROM detalle_producto_colores_tallas WHERE id_producto = '$data[id_producto]' AND id_tallas = '$talla[id_talla]' AND id_colores = '$pair[id_color]'");
                                parent::cerrar();
                                if(!$consulta){ // SI EL REGISTRO NO ESTA SE INSERTA
                                    $cantidad_post = $cantidad_post + $talla['cantidad'];
                                    parent::conectar();
                                    $resultado = parent::queryRegistro("INSERT INTO detalle_producto_colores_tallas(id_producto,id_tallas,id_colores,cantidad,sku) VALUES('$data[id_producto]','$talla[id_talla]','$pair[id_color]','$talla[cantidad]','$talla[sku]');");
                                    parent::cerrar();  
                                }else{ // SI EL PRODUCTO ESTA SE ACTUALIZA
                                    $cantidad_pair = $consulta[0]['cantidad'] + $talla['cantidad'];
                                    $cantidad_post = $cantidad_post - $consulta[0]['cantidad'] + $cantidad_pair;

                                    $pair_aux = $consulta[0]['id'];
                                    parent::conectar();
                                    parent::query("UPDATE detalle_producto_colores_tallas SET cantidad = '$cantidad_pair', sku = '$talla[sku]' WHERE id = '$pair_aux'");
                                    parent::cerrar();
                                }
                            }
                        }
                    }
                }
                $nueva_cantidad = 0;
                if($producto[0]['tiene_colores_tallas'] == 1){
                    $nueva_cantidad = $cantidad_post - $cantidad_post_aux;
                }else{
                    if($realizar_op){
                        $nueva_cantidad = $cantidad_post - $cantidad_post_aux;
                    }else{
                        $nueva_cantidad = $cantidad_post;
                    }
                }
                parent::conectar(); // SE ACTUALIZA LA CANTIDAD DISPONIBLE EN LA TABLA PRODUCTOS
                parent::query("UPDATE productos SET tiene_colores_tallas = 1, cantidad ='$nueva_cantidad' WHERE id = '$data[id_producto]'");
                parent::cerrar();


                $dataActualizada = $this->obtener_productos_tallas_editar(array("id_producto" => $data['id_producto']));
                
                if($dataActualizada['status'] == "success"){
                    $this->envio_de_correo_general_ediccion($producto[0]); 
                    return array("status" => "success", "mensaje" => "data actualizada", "data"=>$dataActualizada['data']);
                }else if( $dataActualizada['status'] == 'vacio'){

                    parent::conectar();
                    parent::query("UPDATE productos SET tiene_colores_tallas = 0 WHERE id = '$data[id_producto]'");
                    parent::cerrar();

                    return array("status" => "success", "mensaje" => "todas las tallas y colores eliminadas");
                }
                
                // return array("status" => "success", "mensaje" => "data actualizada", "data"=>$data);
            }else{
                return array("status" => "error", "mensaje" => "error al obtener producto");
            }
            // return array("status" => "success", "mensaje" => "data actualizada", "data"=>$data);
        }else{
            return array("status" => "fail", "mensaje" => "data error");
        }
    }

    // DEPRECADA
    function obtener_pares_producto_colores_tallas(Array $data){
        if(!isset($data['id_producto'])){
            return array("status" => "error", "mensaje" => "faltan datos");
        }

        parent::conectar();
        // $result = parent::consultaTodo("SELECT id as id_pair, id_producto, id_tallas, id_colores, cantidad, sku FROM detalle_producto_colores_tallas WHERE id_producto = '$data[id_producto]';");
        $result = parent::consultaTodo("SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
        pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
        pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
        FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
        JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE id_producto = '$data[id_producto]';");
        parent::cerrar();
        if($result){
            return array("status" => "success", "mensaje" =>"id de la pareja color - talla", "data" => $result);
        }
        return array("status" => "fail", "mensaje" => "no se encontraron registros");
    }

    function obtener_productos_tallas_editar(Array $data){
        // REGRESA INFORMACION DE COLORES Y TALLAS DE UN PRODUCTO EN UNA ESTRUCTURA APROPIADA PARA EDITAR
        parent::conectar();
        $result = parent::consultaTodo("SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
        pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
        pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
        FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
        JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE id_producto = '$data[id_producto]';");
        parent::cerrar();

        if($result && count($result) > 0){
            $response = [];
            foreach($result as $pair){
                if(count($response) > 0){
                    $sw = 0;
                        for($i = 0; $i<count($response);$i++){
                        if($pair['id_color'] === $response[$i]['id_color']){
                            $sw = 1;
                            array_push(
                                $response[$i]['tallas'],array(
                                    "id_talla" => $pair['id_tallas'], 
                                    "cantidad" => $pair['cantidad'], 
                                    "id_pair" => $pair['id_pair'],
                                    "nombre_es" => $pair['talla_nombre_es'],
                                    "nombre_en" => $pair['talla_nombre_en'],
                                    "sku" => $pair['sku']
                                ));
                        }
                    }
                    if($sw == 0){
                        array_push($response,array(
                            "id_color" => $pair['id_color'],
                            "nombre_es" => $pair['color_nombre_es'],
                            "nombre_en" => $pair['color_nombre_en'],
                            "hexadecimal" => $pair['hexadecimal'],
                            "tallas" => [
                                array(
                                    "id_talla" => $pair['id_tallas'], 
                                    "cantidad" => $pair['cantidad'], 
                                    "id_pair" => $pair['id_pair'],
                                    "nombre_es" => $pair['talla_nombre_es'],
                                    "nombre_en" => $pair['talla_nombre_en'],
                                    "sku" => $pair['sku'] 
                                    )
                            ],
                        ));
                    }
                }else{
                    array_push($response,array(
                        "id_color" => $pair['id_color'],
                        "nombre_es" => $pair['color_nombre_es'],
                        "nombre_en" => $pair['color_nombre_en'],
                        "hexadecimal" => $pair['hexadecimal'],
                        "tallas" => [
                            array(
                                "id_talla" => $pair['id_tallas'], 
                                "cantidad" => $pair['cantidad'], 
                                "id_pair" => $pair['id_pair'],
                                "nombre_es" => $pair['talla_nombre_es'],
                                "nombre_en" => $pair['talla_nombre_en'],
                                "sku" => $pair['sku'] 
                                )
                        ],
                    ));
                }
            }
            return array("status" => "success", "data" => $response);
        }else if(!$result){
            return array("status" => "vacio", "mensaje" => "no hay datos","data" => []);
        }
    }
    
    function agregar_nuevo_editar(Array $data){
        if(!isset($data)){
            return array("status" => "error", "mensaje" => "faltan datos");
        }
        $array_llenar_detalles = NULL;
        foreach($data['coloresXtallas'] as $pair){
            if(isset($pair['tallas']) && isset($pair['color_id'])){
                foreach($pair['tallas'] as $talla){
                    $array_llenar_detalles = array(
                        'id_producto' => $data['id_producto'],
                        'id_tallas' => $talla['id_talla'],
                        'id_colores' => $pair['color_id'],
                        'cantidad' => $talla['cantidad']
                    );
                    if(isset($talla['sku'])){
                        $array_llenar_detalles['sku'] = $talla['sku'];
                    }else{
                        $array_llenar_detalles['sku'] = '';
                    }
                    $this->guardarProductoColoresTallas($array_llenar_detalles);
                }
            }
        }
        return array("status" => "success", "mensaje" => "creada nueva configuracion", "data" => $data);
    }

    // NO UTILIZADA POR EL MOMENTO
    function agregar_colores_tallas_primera_vez_editar($data){
        $cantidad = 0;
        $array_llenar_detalles = NULL;
        foreach($data['coloresXtallas'] as $pair){
            if(isset($pair['tallas']) && isset($pair['color_id'])){
                foreach($pair['tallas'] as $talla){
                    $cantidad = $cantidad + $talla['cantidad'];
                    $array_llenar_detalles = array(
                        'id_producto' => $data['id'],
                        'id_tallas' => $talla['id_talla'],
                        'id_colores' => $pair['color_id'],
                        'cantidad' => $talla['cantidad']
                    );
                    if(isset($talla['sku'])){
                        $array_llenar_detalles['sku'] = $talla['sku'];
                    }else{
                        $array_llenar_detalles['sku'] = '';
                    }
                    $this->guardarProductoColoresTallas($array_llenar_detalles);
                }
            }
        }
        parent::conectar();
        $result = parent::query("UPDATE productos SET tiene_colores_tallas = 1, cantidad ='$cantidad' WHERE id = '$data[id]'");
        parent::cerrar();

        if($result){
            return array("status" => "success", "mensaje" => "se actualizo el producto");
        }
        return array("status" => "fail", "mensaje" => "error al agregar colores y tallas");
    }

    function prueba_de_notificacion($data){

        // parent::conectar();
        // parent::query("UPDATE direcciones SET dane = 47001000, ciudad = 'Santa Marta' WHERE id = 30	AND ciudad = 'Samta Marta';");
        // parent::query("UPDATE direcciones SET dane = 05001000 WHERE ciudad LIKE '%medellin%';");
        // parent::query("UPDATE direcciones SET dane = 47189000 WHERE ciudad LIKE '%cienaga%';");
        // parent::query("UPDATE direcciones SET dane = 13430000 WHERE ciudad LIKE '%Magangue%';");
        // parent::query("UPDATE direcciones SET dane = 47001000 WHERE ciudad LIKE '%Santa marta%';");
        // parent::query("UPDATE direcciones SET dane = 73001000 WHERE ciudad LIKE '%Ibague%';");
        // parent::query("UPDATE direcciones SET dane = 68276000 WHERE ciudad LIKE '%Floridablanca%';");
        // parent::query("UPDATE direcciones SET dane = 05631000 WHERE ciudad LIKE '%Sabaneta%';");
        // parent::query("UPDATE direcciones SET dane = 25175000 WHERE ciudad LIKE '%Chia%';");
        // parent::query("UPDATE direcciones SET dane = 73504000 WHERE ciudad LIKE '%Ortega%';");
        // parent::query("UPDATE direcciones SET dane = 76001000 WHERE ciudad LIKE '%Cali%';");
        // parent::cerrar();
        return array("mensaje" => "terminado");
    }

    // FIN COLORES TALLAS, REVISION

    // BACKEND 

    // ESTA FUNCION PERTENECE EL BACKEND
    function obtener_publicaciones_espera_revison_bakcend($data){

        $filtro = "";
        $emailP2W = "";
        $emailEMPRESA = "";
        $usuarioP2W = "";
        $usuarioEMPRESA = "";

        if(isset($data['pag']) && isset($data['filtro_review']) && isset($data['input_buscar'])){

            if(intval( ($data['filtro_review']) < 1 || intval($data['filtro_review']) > 3) && strlen($data['filtro_review'])){ 
                return array("status" => "fail", "data" => [], "mensaje" => "Los id del filtro son 1,2,3", "pagina" => $data['pag'], "total_paginas" => 1);
            }

            if(!strlen($data['input_buscar']) && strlen($data['filtro_review'])){ 
                return array("status" => "fail", "data" => [], "mensaje" => "Cadena de busqueda vacia", "pagina" => $data['pag'], "total_paginas" => 1);
            }  

            if(!strlen($data['filtro_review']) && strlen($data['input_buscar'])){
                return array("status" => "fail", "data" => [], "mensaje" => "Falta el id del filtro", "pagina" => $data['pag'], "total_paginas" => 1);
            }

            if(intval($data['filtro_review']) == 1){
                $filtro = " AND pr.titulo LIKE '%$data[input_buscar]%' ";
            }else if(intval($data['filtro_review']) == 2){
                $usuarioP2W = " AND u.nombreCompleto LIKE '%$data[input_buscar]%' ";
                $usuarioEMPRESA = "AND u.razon_social LIKE '%$data[input_buscar]%' ";
            }else if(intval($data['filtro_review']) == 3){
                $emailP2W = " AND u.email LIKE '%$data[input_buscar]%' "; 
                $emailEMPRESA = " AND u.correo LIKE '%$data[input_buscar]%' ";
            }
        }else{
            return array("status" => "fail", "data" => [], "mensaje" => "Faltan datos", "pagina" => $data['pag'], "total_paginas" => 1);
        }

        $fecha_actual = intval(microtime(true));

        $date = date("Y-m-d H:i:s",$fecha_actual);

        parent::conectar();
        $consulta = "SELECT * FROM (SELECT u.nombreCompleto as nombre_usuario, u.email as email, u.telefono as telefono ,pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
        pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
        pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
        pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
        pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
        pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
        pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
        pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
        pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
        FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
        ON pr.id_productos_revision_estados = pre.id 
        JOIN peer2win.usuarios as u
        ON pr.uid = u.id AND pr.empresa = 0
        WHERE pr.id_productos_revision_estados IN (0, 2) $filtro $usuarioP2W $emailP2W

        UNION SELECT u.razon_social as nombre_usuario, u.correo as email, u.telefono as telefono, pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
        pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
        pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
        pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
        pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
        pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
        pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
        pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
        pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
        FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
        ON pr.id_productos_revision_estados = pre.id
        JOIN buyinbig.empresas as u
        ON pr.uid = u.id AND pr.empresa = 1 
        WHERE pr.id_productos_revision_estados IN (0, 2) $filtro $usuarioEMPRESA $emailEMPRESA) as un ORDER BY un.id DESC;
        ";

        $enEspera = parent::consultaTodo($consulta); 
        parent::cerrar();
        if(count($enEspera) > 0){
            $numXpagina = 9;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($enEspera)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        $publicacion_fecha = date("Y-m-d H:i:s",floatval($enEspera[$i]['fecha_actualizacion'])/1000);
                        $fecha_update = strtotime($publicacion_fecha);
                        $fecha_hoy = strtotime($date);
                        $diferencia_dias = abs($fecha_hoy - $fecha_update)/(60*60*24);
                        $enEspera[$i]['precio_mask'] = $this->maskNumber($enEspera[$i]['precio'], 2);
                        $enEspera[$i]['dias_espera'] = $diferencia_dias;
                        array_push($respuesta, $enEspera[$i]);
                    }
                }
            }
            $num_paginas = count($enEspera)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success", "data" => $respuesta, "mensaje" => "Datos de publicaciones", "pagina" => $data['pag'], "total_paginas" => $num_paginas);
        }else{
            return array("status" => "fail", "data" => [], "mensaje" => "No hay datos", "pagina" => $data['pag'], "total_paginas" => 1);
        }
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function obtener_datos_producto($data){
        $producto = NULL;
        parent::conectar();

        $productoPublicado = parent::consultaTodo("SELECT 
            p.id as producto_id, p.uid	as id_usuario, p.empresa as empresa,
            p.tipo as producto_tipo, p.tipoSubasta as producto_tipoSubasta, p.producto as producto, p.marca as producto_marca,
            p.modelo as producto_modelo, p.titulo as producto_titulo, p.descripcion as producto_descripcion,
            p.categoria as producto_categoria, p.subcategoria as producto_subcategoria, p.condicion_producto as producto_condicion,
            p.garantia as producto_garantia, p.estado as producto_estado, p.cantidad as producto_cantidad, p.moneda_local as producto_moneda_local,
            p.precio as producto_precio, p.precio_usd as producto_precio_usd, p.precio_publicacion as producto_precio_publicacion,
            p.precio_usd_publicacion as producto_precio_usd_publicacion, p.oferta as producto_oferta, 
            p.porcentaje_oferta	as producto_porcentaje_oferta, p.porcentaje_tax as producto_porcentaje_tax,
            p.exposicion as producto_exposicion, p.cantidad_exposicion as producto_cantidad_exposicion, 
            p.envio as producto_envio, p.id_direccion as producto_id_direccion, p.pais as producto_pais, 
            p.departamento as producto_departamento, p.ciudad as producto_ciudad, p.latitud as producto_latitud, 
            p.longitud as producto_longitud, p.codigo_postal as producto_codigo_postal, p.direccion as producto_direccion,
            p.keywords as producto_keywords, p.foto_portada as producto_foto_portada, p.url_video as producto_url_video, 
            p.portada_video as producto_portada_video, p.cantidad_vendidas as producto_cantidades_vendidas,
            p.fecha_creacion as producto_fecha_creacion, p.fecha_actualizacion as producto_fecha_actualizacion, 
            p.ultima_venta as producto_ultima_venta, p.genero as producto_genero, 
            p.tiene_colores_tallas as producto_tiene_colores_tallas,
            p.tiempo_estimado_envio_num, p.tiempo_estimado_envio_unidad, p.id_tiempo_garantia, p.num_garantia
            FROM productos as p WHERE p.id = '$data[id]' AND p.estado = 1");
        parent::cerrar();

        if(!$productoPublicado){
            parent::conectar();
            $productoRevision = parent::consultaTodo("SELECT 
            p.id as producto_id, p.uid	as id_usuario, p.empresa as empresa,
            p.tipo as producto_tipo, p.tipoSubasta as producto_tipoSubasta, p.producto as producto, p.marca as producto_marca,
            p.modelo as producto_modelo, p.titulo as producto_titulo, p.descripcion as producto_descripcion,
            p.categoria as producto_categoria, p.subcategoria as producto_subcategoria, p.condicion_producto as producto_condicion,
            p.garantia as producto_garantia, p.estado as producto_estado, p.cantidad as producto_cantidad, p.moneda_local as producto_moneda_local,
            p.precio as producto_precio, p.precio_usd as producto_precio_usd, p.precio_publicacion as producto_precio_publicacion,
            p.precio_usd_publicacion as producto_precio_usd_publicacion, p.oferta as producto_oferta, 
            p.porcentaje_oferta	as producto_porcentaje_oferta, p.porcentaje_tax as producto_porcentaje_tax,
            p.exposicion as producto_exposicion, p.cantidad_exposicion as producto_cantidad_exposicion, 
            p.envio as producto_envio, p.id_direccion as producto_id_direccion, p.pais as producto_pais, 
            p.departamento as producto_departamento, p.ciudad as producto_ciudad, p.latitud as producto_latitud, 
            p.longitud as producto_longitud, p.codigo_postal as producto_codigo_postal, p.direccion as producto_direccion,
            p.keywords as producto_keywords, p.foto_portada as producto_foto_portada, p.url_video as producto_url_video, 
            p.portada_video as producto_portada_video, p.cantidad_vendidas as producto_cantidades_vendidas,
            p.fecha_creacion as producto_fecha_creacion, p.fecha_actualizacion as producto_fecha_actualizacion, 
            p.ultima_venta as producto_ultima_venta, p.genero as producto_genero, 
            p.tiene_colores_tallas as producto_tiene_colores_tallas, p.id_productos_revision_estados as producto_id_revision_estados,
            p.tiempo_estimado_envio_num, p.tiempo_estimado_envio_unidad, p.id_tiempo_garantia, p.num_garantia
            FROM productos_revision as p WHERE p.id = '$data[id]'");
        parent::cerrar();
            if(count($productoRevision) <= 0){
                return array("status" => "fail", "mensaje" => "producto no encontrado");
            }else{
                $producto = $productoRevision;
            } 
        }else{
            $producto = $productoPublicado;
        }

        if($producto){
            $producto = $producto[0];
            parent::conectar();
            $id_productos_revision_estados = parent::consultaTodo("SELECT id_productos_revision_estados FROM productos_revision WHERE id = '$producto[producto_id]' ");
            parent::cerrar();
            if($id_productos_revision_estados){
                $producto['id_productos_revision_estados'] = $id_productos_revision_estados[0]['id_productos_revision_estados'];
                parent::conectar();
                $producto_revision = parent::consultaTodo("SELECT * FROM productos_revision_estados where id = '$producto[id_productos_revision_estados]'");
                parent::cerrar();
                if($producto_revision){
                    $producto['productos_revision_estado'] = $producto_revision[0];
                }else{
                    $producto['productos_revision_estado'] = NULL;
                }
            }else{
                $producto['id_productos_revision_estados'] = NULL;
            }

            if($producto['producto_tiene_colores_tallas'] != 0){
               $respuesta_colores_tallas = $this->obtener_productos_tallas_editar(array('id_producto'=>$producto['producto_id']))["data"];
                $producto['colores_tallas'] = $respuesta_colores_tallas;
            }else{
                $producto['colores_tallas'] = NULL;
            }

            //fechas

            $producto['producto_fecha_creacion'] = floatval($producto['producto_fecha_creacion']);
            $producto['producto_fecha_actualizacion'] = floatval($producto['producto_fecha_actualizacion']);
            $producto['producto_ultima_venta'] = floatval($producto['producto_ultima_venta']);

            $fecha_actual = intval(microtime(true));

            $date = date("Y-m-d H:i:s",$fecha_actual);

            $publicacion_fecha = date("Y-m-d H:i:s",floatval($producto['producto_fecha_actualizacion'])/1000);
            $fecha_update = strtotime($publicacion_fecha);
            $fecha_hoy = strtotime($date);
            $diferencia_dias = abs($fecha_hoy - $fecha_update)/(60*60*24);
            $producto['dias_espera'] = $diferencia_dias;
            //fin fechas

            $producto['precio_mask'] = $this->maskNumber($producto['producto_precio'], 2);

            parent::conectar();
            $producto_fotos = parent::consultaTodo("SELECT * FROM productos_fotos WHERE id_publicacion = '$producto[producto_id]'");
            parent::cerrar();

            if($producto_fotos){
                $producto['fotos'] = $producto_fotos;
                //fechas fotos
                    for($i = 0; $i<count($producto['fotos']); $i++){
                        $producto['fotos'][$i]['fecha_creacion'] = floatval( $producto['fotos'][$i]['fecha_creacion']);
                        $producto['fotos'][$i]['fecha_actualizacion'] = floatval( $producto['fotos'][$i]['fecha_actualizacion']);
                    }
                //fin fechas fotos
            }else{
                $producto['fotos'] = NULL;
            }

            
            parent::conectar();
            $data_usuario = NULL;
            // $producto['usuario'] = $data_ususario;
            if($producto['empresa'] == 1){
                $data_usuario = parent::consultaTodo("SELECT * FROM empresas WHERE id = '$producto[id_usuario]'");
                $data_usuario = array(
                    "nombre_usuario" => $data_usuario[0]['razon_social'],
                    "email" => $data_usuario[0]['correo'],
                    "telefono" => $data_usuario[0]['telefono']
                ); 
                $producto['usuario'] = $data_usuario;
            }else if($producto['empresa'] == 0){
                $data_usuario = parent::consultaTodo("SELECT * FROM peer2win.usuarios WHERE id = '$producto[id_usuario]'");
                $data_usuario = array(
                    "nombre_usuario" => $data_usuario[0]['nombreCompleto'],
                    "email" => $data_usuario[0]['email'],
                    "telefono" => $data_usuario[0]['telefono']
                ); 
                $producto['usuario'] = $data_usuario;
            }
            parent::cerrar();

            
            if($producto['producto_tipoSubasta'] != 0){

                parent::conectar();
                $subastas = parent::consultaTodo("SELECT ps.id as subasta_id,ps.moneda as subasta_moneda, 
                        ps.precio as subasta_precio, ps.precio_usd as subasta_precio_usd,
                        ps.cantidad as subasta_cantidad, ps.tipo as subasta_tipo,
                        ps.estado as subasta_estado, ps.apostadores as subastas_apostadores,
                        ps.inscritos as subastas_inscritos,
                        ps.fecha_fin as subastas_fecha_fin,
                        ps.fecha_creacion as subastas_fecha_creacion,
                        ps.fecha_actualizacion as subastas_fecha_actualizacion,
                        ps.fecha_inicio as subastas_fecha_inicio
                        FROM productos_subastas as ps WHERE ps.id_producto = '$producto[producto_id]'");
                parent::cerrar();

                $producto['subastas'] = $subastas[0];

                //fecha subasta
                $producto['subastas']['subastas_fecha_fin'] = floatval($producto['subastas']['subastas_fecha_fin']);
                $producto['subastas']['subastas_fecha_creacion'] = floatval($producto['subastas']['subastas_fecha_creacion']);
                $producto['subastas']['subastas_fecha_actualizacion'] = floatval($producto['subastas']['subastas_fecha_actualizacion']);
                $producto['subastas']['subastas_fecha_inicio'] = floatval($producto['subastas']['subastas_fecha_inicio']);
                // fin fecha subasta

                $subasta_tipo = $producto['subastas']['subasta_tipo'];
                parent::conectar();
                $subasta_tipo_data = parent::consultaTodo("SELECT * FROM productos_subastas_tipo WHERE id = '$subasta_tipo';");
                parent::cerrar();

                $subasta_estado = $producto['subastas']['subasta_estado'];
                parent::conectar();
                $subasta_estado_data = parent::consultaTodo("SELECT * FROM productos_subastas_estado WHERE id = '$subasta_estado';");
                parent::cerrar();

                
                $subasta_id = $producto['subastas']['subasta_id'];
                parent::conectar();
                $subasta_inscritos = parent::consultaTodo("SELECT * FROM productos_subastas_inscritos WHERE id_subasta = '$subasta_id'");
                parent::cerrar();

                parent::conectar();
                $subasta_pujas = parent::consultaTodo("SELECT * FROM productos_subastas_pujas WHERE id_subasta = '$subasta_id'");
                parent::cerrar();

                if($subasta_tipo_data){
                    $producto['subastas']['subasta_tipo'] = $subasta_tipo_data[0]; 
                }else{
                    $producto['subastas']['subasta_tipo'] = NULL;
                }
                if($subasta_estado_data){
                    $producto['subastas']['subasta_estado'] = $subasta_estado_data[0];
                }else{
                    $producto['subastas']['subasta_estado'] = NULL;
                }

                $producto['subastas']['subastas_inscritos_detalle'] = [];
                if($subasta_inscritos){
                    foreach($subasta_inscritos as $inscrito){
                        $inscrito['fecha_creacion'] = floatval($inscrito['fecha_creacion']);
                        $inscrito['fecha_actualizacion'] = floatval($inscrito['fecha_actualizacion']);
                        array_push($producto['subastas']['subastas_inscritos_detalle'],$inscrito);
                    }
                }else{
                    $producto['subastas']['subastas_inscritos_detalle'] = NULL;
                }

                $producto['subastas']['subasta_pujas'] = [];
                if($subasta_pujas){
                    foreach($subasta_pujas as $puja){
                        $puja['fecha_creacion'] = floatval($puja['fecha_creacion']);
                        $puja['fecha_actualizacion'] = floatval($puja['fecha_actualizacion']);
                        $puja['fecha_final'] = floatval($puja['fecha_final']);
                        array_push($producto['subastas']['subasta_pujas'],$puja);
                    }
                }else{
                    $producto['subastas']['subasta_pujas'] = NULL;
                }
            }else{
                $producto['subastas'] = NULL;
            }
        }
        return array("status" => "success", "data" => $producto);
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function rechazar_producto(Array $data){

        // parent::addLogJB("-----+> [ data ]: " . json_encode($data));

        parent::conectar();
        $result = parent::query("UPDATE productos_revision SET id_productos_revision_estados = 2, motivo = '$data[motivo]'  WHERE id = '$data[id_producto]'");
        $producto = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$data[id_producto]'");
        parent::cerrar();
        if($result){
            if($producto){
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid'     => $producto[0]['uid'],
                    'empresa' => $producto[0]['empresa'],
                    'text'    => 'Tu producto '.$producto[0]['titulo'].' ha sido rechazado',
                    'es'      => 'Tu producto '.$producto[0]['titulo'].' ha sido rechazado',
                    'en'      => 'Your product '.$producto[0]['titulo'].' has been rejected',
                    'keyjson' => '',
                    'url'     => ''
                ]);
            }

            $producto[0]['data_request_rechazo'] = $data;
            $this->envio_correo_rechazo_($producto[0]); 
            return array("status" => "success", "mensaje" => "producto rechazado");
        }else{
            return array("status" => "fail", "mensaje error al rechazar producto");
        }
    }

    function cronRevision(){

        $fecha_actual = intval(microtime(true));

        $date = date("Y-m-d H:i:s",$fecha_actual);
        
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_revision WHERE id_productos_revision_estados = 0");
        parent::cerrar();
        if($result){
            foreach($result as $publicacion){
                $publicacion_fecha = date("Y-m-d H:i:s",floatval($publicacion['fecha_actualizacion'])/1000);
                $fecha_update = strtotime($publicacion_fecha);
                $fecha_hoy = strtotime($date);
                $diferencia_horas = abs($fecha_hoy - $fecha_update)/(60*60);
                $publicacion['diferencia'] = $diferencia_horas;
                // $publicacion['fecha_act_normla'] = $date;
                // $publicacion['fecha_publ_normla'] = $publicacion_fecha;
                // $publicacion['strFU'] = $fecha_update;
                // $publicacion['strFH'] = $fecha_hoy;  
                if($diferencia_horas >= 120){
                    $this->aceptar_producto(array("id_producto" => intval($publicacion['id'])));
                }
            }
            return array("status" => "success");
        }else{
            return array("status" => "fail", "mensaje" => "no se encontraron productos");
        }
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function aceptar_producto(Array $data) {

        parent::conectar();
        $respuesta = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$data[id_producto]'");
        parent::cerrar();
        $respuesta = $respuesta[0];

        $respuesta['producto']    = addslashes($respuesta['producto']);
        $respuesta['marca']       = addslashes($respuesta['marca']);
        $respuesta['modelo']      = addslashes($respuesta['modelo']);
        $respuesta['titulo']      = addslashes($respuesta['titulo']);
        $respuesta['descripcion'] = addslashes($respuesta['descripcion']);

        $producto = $this->obtenerProducto( $respuesta['id'] );//Buscamos en tabla productos

        $queriRegistro = '';
        $productoMovido = 0;

        if( count($producto) > 0 ) {//UPDATE

            parent::addLogSubastas("UPDATE");
            $queriRegistro = 
            "UPDATE productos SET
            tipo = '$respuesta[tipo]',
            tipoSubasta = '$respuesta[tipoSubasta]',
            producto = '$respuesta[producto]',
            marca = '$respuesta[marca]',
            modelo = '$respuesta[modelo]',
            titulo = '$respuesta[titulo]',
            descripcion = '$respuesta[descripcion]',
            categoria = '$respuesta[categoria]',
            subcategoria = '$respuesta[subcategoria]',
            condicion_producto = '$respuesta[condicion_producto]',
            garantia = '$respuesta[garantia]',
            estado = '$respuesta[estado]',
            fecha_creacion = '$respuesta[fecha_creacion]',
            fecha_actualizacion = '$respuesta[fecha_actualizacion]',
            ultima_venta = '$respuesta[ultima_venta]',
            cantidad = '$respuesta[cantidad]',
            precio = '$respuesta[precio]',
            precio_usd = '$respuesta[precio_usd]',
            precio_publicacion = '$respuesta[precio]',
            precio_usd_publicacion = '$respuesta[precio_usd]',
            oferta = '$respuesta[oferta]',
            porcentaje_oferta = '$respuesta[porcentaje_oferta]',
            porcentaje_tax = '$respuesta[porcentaje_tax]',
            exposicion = '$respuesta[exposicion]',
            cantidad_exposicion = '$respuesta[cantidad_exposicion]',
            envio = '$respuesta[envio]',
            id_direccion = '$respuesta[id_direccion]',
            pais = '$respuesta[pais]',
            departamento = '$respuesta[departamento]',
            ciudad = '$respuesta[ciudad]',
            latitud = '$respuesta[latitud]',
            longitud = '$respuesta[longitud]',
            codigo_postal = '$respuesta[codigo_postal]',
            direccion = '$respuesta[direccion]',
            keywords = '$respuesta[keywords]',
            foto_portada = '$respuesta[foto_portada]',
            portada_video = '$respuesta[portada_video]',
            cantidad_vendidas = '$respuesta[cantidad_vendidas]',
            url_video = '$respuesta[url_video]',
            genero = '$respuesta[genero]',
            tiene_colores_tallas = '$respuesta[tiene_colores_tallas]',
            tipo_envio_gratuito = '$respuesta[tipo_envio_gratuito]',
            id_tiempo_garantia = '$respuesta[id_tiempo_garantia]',
            num_garantia = '$respuesta[num_garantia]',
            tiempo_estimado_envio_num	 = '$respuesta[tiempo_estimado_envio_num]',
            tiempo_estimado_envio_unidad = '$respuesta[tiempo_estimado_envio_unidad]'
            WHERE id = '$respuesta[id]';";

            parent::conectar();
            $productoMovido = parent::query($queriRegistro);
            parent::cerrar();
        }else{//INSERT
            parent::addLogSubastas("insert");
            $queriRegistro = 
            "INSERT INTO productos(
                id,
                uid,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                foto_portada,
                portada_video,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas,
                tipo_envio_gratuito,
                id_tiempo_garantia,
                num_garantia,
                tiempo_estimado_envio_num,
                tiempo_estimado_envio_unidad
            ) VALUES (
                '$respuesta[id]',
                '$respuesta[uid]',
                '$respuesta[tipo]',
                '$respuesta[tipoSubasta]',
                '$respuesta[producto]',
                '$respuesta[marca]',
                '$respuesta[modelo]',
                '$respuesta[titulo]',
                '$respuesta[descripcion]',
                '$respuesta[categoria]',
                '$respuesta[subcategoria]',
                '$respuesta[condicion_producto]',
                '$respuesta[garantia]',
                '$respuesta[estado]',
                '$respuesta[fecha_creacion]',
                '$respuesta[fecha_actualizacion]',
                '$respuesta[ultima_venta]',
                '$respuesta[cantidad]',
                '$respuesta[moneda_local]',
                '$respuesta[precio]',
                '$respuesta[precio_usd]',
                '$respuesta[precio]',
                '$respuesta[precio_usd]',
                '$respuesta[oferta]',
                '$respuesta[porcentaje_oferta]',
                '$respuesta[porcentaje_tax]',
                '$respuesta[exposicion]',
                '$respuesta[cantidad_exposicion]',
                '$respuesta[envio]',
                '$respuesta[id_direccion]',
                '$respuesta[pais]',
                '$respuesta[departamento]',
                '$respuesta[ciudad]',
                '$respuesta[latitud]',
                '$respuesta[longitud]',
                '$respuesta[codigo_postal]',
                '$respuesta[direccion]',
                '$respuesta[keywords]',
                '$respuesta[foto_portada]',
                '$respuesta[portada_video]',
                '$respuesta[cantidad_vendidas]',
                '$respuesta[empresa]',
                '$respuesta[url_video]',
                '$respuesta[genero]',
                '$respuesta[tiene_colores_tallas]',
                '$respuesta[tipo_envio_gratuito]',
                '$respuesta[id_tiempo_garantia]',
                '$respuesta[num_garantia]',
                '$respuesta[tiempo_estimado_envio_num]',
                '$respuesta[tiempo_estimado_envio_unidad]'
            )";

            parent::conectar();
            $productoMovido = parent::queryRegistro($queriRegistro);
            parent::cerrar();
        }

        parent::addLogSubastas("PRODUCTO REVISION ID ======> " . $productoMovido);

        if($productoMovido){
            parent::conectar();
            $result = parent::query("UPDATE productos_revision SET id_productos_revision_estados = 1 WHERE id = '$data[id_producto]'");
            parent::cerrar();
            if($result){
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid'     => $respuesta['uid'],
                    'empresa' => $respuesta['empresa'],
                    'text'    => 'Tu producto ' . $respuesta['titulo'] . ' ha sido aceptado.',
                    'es'      => 'Tu producto ' . $respuesta['titulo'] . ' ha sido aceptado.',
                    'en'      => 'Your product ' . $respuesta['titulo'] . ' has been accepted.',
                    'keyjson' => '',
                    'url'     => ''
                ]);
                $this->envio_correo_aceptado_product($respuesta);
                return array('status' => 'success', 'mensaje' => 'producto aceptado correctamente');
            }else{
                return array('status' => 'fail', 'mensaje' => 'producto movido pero no actualizado');
            }
        }else{
            
            return array('status' => 'fail', 'mensaje' => 'error al aceptar producto');
        }    
    }

    // ESTA FUNCION PERTENECE AL BACKEND REGRESA TODAS LAS SUBASTAS 
    function obtener_subastas_backend(Array $data){

        $estado = " ps.estado != 0 ";
        if(isset($data['estado']) && $data['estado'] != "" && $data['estado'] != NULL){
            $estado = " ps.estado = '$data[estado]' ";
        }

        $moneda = "";
        if(isset($data['moneda']) && $data['moneda'] != "" && $data['moneda'] != NULL){
            $moneda = " AND ps.moneda = '$data[moneda]' ";
        }

        $tipo = "";
        if(isset($data['tipo']) && $data['tipo'] != "" && $data['tipo'] != NULL){
            $tipo = " AND ps.tipo = '$data[tipo]' ";
        }

        $titulo = "";
        if(isset($data['titulo']) && $data['titulo'] != "" && $data['titulo'] != NULL){
            $titulo = " AND p.titulo LIKE '%$data[titulo]%' ";
        }

        // $query = "SELECT * FROM productos_subastas WHERE ".$estado."".$moneda."".$tipo."";

        $query = "SELECT ps.*,p.id as producto_id,p.titulo as producto_titulo,p.precio as producto_precio, 
        p.foto_portada, pst.descripcion as tipo_descripcion  from productos_subastas as ps 
        join productos as p on p.id = ps.id_producto
        join productos_subastas_tipo as pst on pst.id = ps.tipo WHERE ".$estado."".$titulo."".$moneda."".$tipo." ORDER BY ps.id DESC";

        parent::conectar();
        $subastas_backend = parent::consultaTodo($query);
        parent::cerrar();

        if($subastas_backend){
            
            for($i = 0; $i<count($subastas_backend); $i++){
                $subastas_backend[$i]['fecha_fin'] = floatval( $subastas_backend[$i]['fecha_fin']);
                $subastas_backend[$i]['fecha_creacion'] = floatval( $subastas_backend[$i]['fecha_creacion']);
                $subastas_backend[$i]['fecha_actualizacion'] = floatval( $subastas_backend[$i]['fecha_actualizacion']);
                $subastas_backend[$i]['fecha_inicio'] = floatval( $subastas_backend[$i]['fecha_inicio']);
                $subastas_backend[$i]['producto_precio_mask'] =  $this->maskNumber($subastas_backend[$i]['producto_precio'], 2);
               
            }

            $respuesta = [];
            $numXpagina = 10;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($subastas_backend)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        array_push($respuesta, $subastas_backend[$i]);
                    }
                }
            }
            $num_paginas = count($subastas_backend)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success",'pagina' => $data['pag'],'total_paginas' => $num_paginas,'data' => $respuesta);
        }else{
            return array("status" => "fail", "data" => $subastas_backend);
        }
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function dar_permisos_publicacion(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_permisos_publicar WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';");
        parent::cerrar();  
        if(!$result){
            if(count($result) <= 0){
                parent::conectar();
                $permisoRegistro = parent::queryRegistro("INSERT INTO productos_permisos_publicar(uid,empresa) VALUES('$data[uid]','$data[empresa]');");
                parent::cerrar();  
                if($permisoRegistro){
                    return array("status" => "success","mensaje" => "se registraron permisos al usuario");
                }else{
                    return array("status" => "fail", "mensaje" => "error al registrar permisos");
                }
            }else{
                return array("status" => "fail", "mensaje" => "el usuario ya tiene permisos registrados");
            }   
        }
        return array("status" => "fail", "mensaje" => "hubo un error"); 
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function remover_permisos_publicacion(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_permisos_publicar WHERE uid = '$data[uid]' empresa = '$data[empresa]';");
        parent::cerrar();
        if($result){
            if(count($result)>0){
                $id = $result[0]["id"];
                parent::conectar();
                $result = parent::consultaTodo("DELETE FROM productos_permisos_publicar WHERE id='$id'");
                parent::cerrar();
                if($result){
                    return array("status" => "success", "mensaje" => "se removieron los permisos");
                }
            }else{
                return array("status" => "fail", "mensaje" => "el no tiene permisos registrados");
            }
        }
        return array("status" => "fail", "mensaje" => "hubo un error");
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function modificar_subasta_backend(Array $data){

        parent::conectar();
        $updated = parent::query("UPDATE productos_subastas 
            SET estado = '$data[estado]',
            id_producto = '$data[id_producto]',	
            moneda = '$data[moneda]',	
            precio = '$data[precio]',	
            precio_usd = '$data[precio_usd]',	
            cantidad = '$data[cantidad]',	
            tipo = '$data[tipo]',	
            apostadores = '$data[apostadores]',	
            inscritos = '$data[inscritos]',	
            fecha_fin = '$data[fecha_fin]',	
            fecha_creacion = '$data[fecha_creacion]',
            fecha_actualizacion = '$data[fecha_actualizacion]',	
            fecha_inicio = '$data[fecha_inicio]'
            WHERE id = '$data[id]';
        ");
        parent::cerrar();
        if($updated){
            return array("status" => "success", "mensaje" => "erro al actualizar subasta", "data" => $data);
        }else{
            return array("status" => "fail", "mensaje" => "error al actualizar subasta");
        }
        
        // if(isset($data['id_subasta']) && $data['id_subasta'] != NULL){
        //     if(isset($data['estado']) && $data['estado'] != NULL){
        //         if(isset($data['fecha_inicio']) && $data['fecha_inicio'] != NULL){
        //             parent::conectar();
        //             parent::query("UPDATE productos_subastas SET estado = '$data[estado]' WHERE id = '$data[id_subasta]' ");
        //             parent::cerrar();
        //         }
        //         parent::conectar();
        //         parent::query("UPDATE productos_subastas SET estado = '$data[estado]' WHERE id = '$data[id_subasta]' ");
        //         parent::cerrar();       
        //     }
        // }
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function subastas_proximas_iniciar(Array $data){

        $fecha_actual = intval(microtime(true));
        // $date_array = explode(" ",$fecha_actual);
        // $date = date("Y-m-d H:i:s",$date_array[0]);
        $date = date("Y-m-d H:i:s",$fecha_actual);

        parent::conectar();
        $subastas = parent::consultaTodo("SELECT * FROM productos_subastas");
        parent::cerrar();
        $data_response = [];
        if($subastas){
            foreach($subastas as $subasta){
                $subasta_fecha = date("Y-m-d H:i:s",floatval($subasta['fecha_inicio'])/1000);
                $testInicio = strtotime($subasta_fecha);
                $test2Actual = strtotime($date);
                $diferencia_horas = abs($testInicio - $test2Actual)/(60*60);

                if($diferencia_horas <= $data['horas']){
                    $subasta["fecha_fin"] = floatval($subasta["fecha_fin"]);
                    $subasta['fecha_creacion'] = floatval($subasta['fecha_creacion']);
                    $subasta['fecha_actualizacion'] = floatval($subasta['fecha_actualizacion']);
                    $subasta['fecha_inicio'] = floatval($subasta['fecha_inicio']);
                    array_push($data_response,$subasta);
                } 
            }
        }
        return array("status" => "success","data"=>$data_response);        
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function obtener_subasta_backend(Array $data){
        $subasta = NULL;
        parent::conectar();
        $result = parent::consultaTodo("SELECT * from productos_subastas WHERE id = '$data[id]';");
        parent::cerrar();

        if($result){
            $subasta = $result[0];
            $id_subasta = $result[0]['id'];
            $subasta_tipo = $result[0]['tipo'];
            $subasta_estado = $result[0]['estado'];
            $producto_subasta = $result[0]['id_producto'];

            $subasta["fecha_fin"] = floatval($subasta["fecha_fin"]);
            $subasta["fecha_creacion"] = floatval($subasta["fecha_creacion"]);
            $subasta["fecha_actualizacion"] = floatval($subasta["fecha_actualizacion"]);
            $subasta["fecha_inicio"] = floatval($subasta["fecha_inicio"]);

            parent::conectar();
            $tipo_subasta = parent::consultaTodo("SELECT * from productos_subastas_tipo WHERE id = '$subasta_tipo';");
            $producto = parent::consultaTodo("SELECT * FROM productos WHERE id = '$producto_subasta'");
            $producto_fotos = parent::consultaTodo("SELECT * FROM productos_fotos WHERE id_publicacion = '$producto_subasta'");
            parent::cerrar();

            if($producto_fotos){
                $subasta['fotos'] = $producto_fotos;
                //fechas fotos
                    // for($i = 0; $i<count($subasta['fotos']); $i++){
                        // $subasta['fotos'][$i]['fecha_creacion'] = floatval( $subasta['fotos'][$i]['fecha_creacion']);
                        // $subasta['fotos'][$i]['fecha_actualizacion'] = floatval( $subasta['fotos'][$i]['fecha_actualizacion']);
                    // }
                //fin fechas fotos
            }else{
                $producto['fotos'] = NULL;
            }

            $subasta['tipo_subasta'] = [];
            if($tipo_subasta){
                // array_push($subasta['tipo_subasta'],$tipo_subasta[0]);
                $subasta['tipo_subasta'] = $tipo_subasta[0];
            }else{
                $subasta['tipo_subasta'] = NULL;
            }

            if($producto){
                $subasta['producto'] = $producto[0];
            }else{
                $subasta['producto'] = null;
            }

            $subasta_estado = $result[0]['estado'];

            parent::conectar();
            $estado_subasta = parent::consultaTodo("SELECT * from productos_subastas_estado where id = '$subasta_estado';");
            parent::cerrar();

            $subasta['estado_subasta'] = [];
            if($tipo_subasta){
                // array_push($subasta['estado_subasta'],$estado_subasta[0]);
                $subasta['estado_subasta'] = $estado_subasta[0];
            }else{
                $subasta['estado_subasta'] = NULL;
            }

            parent::conectar();
            $inscritos_subasta = parent::consultaTodo("SELECT * from productos_subastas_inscritos where id_subasta = '$id_subasta';");
            parent::cerrar();

            
            if($inscritos_subasta){
                $subasta['inscritos_subasta'] = $inscritos_subasta;
                for($i = 0; $i<count($subasta['inscritos_subasta']); $i++){
                    $subasta['inscritos_subasta'][$i]['fecha_creacion'] = floatval($subasta['inscritos_subasta'][$i]['fecha_creacion']);
                    $subasta['inscritos_subasta'][$i]['fecha_actualizacion'] = floatval($subasta['inscritos_subasta'][$i]['fecha_actualizacion']);
                }
            }else{
                $subasta['inscritos_subasta'] = NULL;
            }

            parent::conectar();
            $pujas_subasta = parent::consultaTodo("SELECT * FROM productos_subastas_pujas WHERE id_subasta = '$id_subasta' ORDER BY id DESC;");
            parent::cerrar();

            // $subasta['pujas_subasta'] = [];
            parent::conectar();
            if($pujas_subasta){
                $subasta['pujas_subasta'] = $pujas_subasta;
                $subasta['total_pujas'] = count($subasta['pujas_subasta']);
                for($i = 0; $i<count($subasta['pujas_subasta']); $i++){
                    $user_id = $subasta['pujas_subasta'][$i]['uid'];
                    $subasta['pujas_subasta'][$i]['fecha_creacion'] = floatval( $subasta['pujas_subasta'][$i]['fecha_creacion']);
                    $subasta['pujas_subasta'][$i]['fecha_actualizacion'] = floatval($subasta['pujas_subasta'][$i]['fecha_actualizacion']);
                    $subasta['pujas_subasta'][$i]['fecha_final'] = floatval($subasta['pujas_subasta'][$i]['fecha_final']);
                    
                    $usuario = parent::consultaTodo("SELECT id, username, nombreCompleto, email,telefono,idioma FROM peer2win.usuarios WHERE id = '$user_id';");
                    if($usuario){
                        $subasta['pujas_subasta'][$i]['usuario'] =  $usuario[0];
                    }else{
                        $subasta['pujas_subasta'][$i]['usuario'] = NULL;
                    }
                }
                
                if($subasta_estado == 4){
                    $subasta['ultima_puja'] =  $pujas_subasta[0];
                    $subasta['ultima_puja']['fecha_creacion'] = floatval($subasta['pujas_subasta'][0]['fecha_creacion']);
                    $subasta['ultima_puja']['fecha_actualizacion'] = floatval($subasta['pujas_subasta'][0]['fecha_actualizacion']);
                    $subasta['ultima_puja']['fecha_final'] = floatval($subasta['pujas_subasta'][0]['fecha_final']);
                    $puja_ganador = $subasta['ultima_puja']['uid'];
                    $ganador = parent::consultaTodo("SELECT id, username, nombreCompleto, email,telefono,idioma FROM peer2win.usuarios WHERE id = '$user_id';");
                    if($ganador){
                        $subasta['ultima_puja']['usuario'] = $ganador[0];
                    }else{
                        $subasta['ultima_puja']['usuario'] = NULL;
                    }
                }
            }else{
                $subasta['pujas_subasta'] = NULL;
            }
            parent::cerrar();

            return array("status" => "success", "data" => $subasta);

        }else{
            return array("status" => "fail", "mensaje" => "subasta no encontrada");
        }
    }

    function obtener_usuarios_sin_permiso(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT id,username,nombreCompleto,telefono,email,paisid,idioma 
        FROM peer2win.usuarios WHERE id NOT IN (SELECT uid FROM productos_permisos_publicar WHERE empresa = 0);
        ");
        parent::cerrar();
        $respuesta = [];
            if($result){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($result)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $result[$i]);
                        }
                    }
                }
                $num_paginas = count($result)/$numXpagina;
                $num_paginas = ceil($num_paginas);
            }
        return array("status" => "success","mensaje" => "usuarios sin permiso para publicar", "data" =>$respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
    }

    function obtener_empresas_sin_permiso(Array $data){
        
        parent::conectar();
        $result = parent::consultaTodo("SELECT id,nombre_empresa,nit,razon_social,correo,idioma,foto_logo_empresa  
        FROM buyinbig.empresas WHERE id NOT IN (SELECT uid FROM productos_permisos_publicar WHERE empresa = 1)");
        parent::cerrar();

        $respuesta = [];
            if($result){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($result)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $result[$i]);
                        }
                    }
                }
                $num_paginas = count($result)/$numXpagina;
                $num_paginas = ceil($num_paginas);
            }
        return array("status" => "success","mensaje" => "empresas sin permiso para publicar", "data" =>$respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
    }

    // FIN BACKEND

    function envio_correo_rechazo_(Array $data_producto){
        $data_user =  $this->datosUserGeneral2([
            'uid'     => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        $this->htmlEmailrechazo_producto($data_producto, $data_user["data"]);
    }

    public function htmlEmailrechazo_producto(Array $data_producto, Array $data_user) {

        // parent::addLogJB("-------+> # [ htmlEmailrechazo_producto ] #: " . json_encode(Array( "data_producto" => $data_producto, "data_user" => $data_user )));

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo6revision.html");

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        $html = str_replace("{{trans152_}}", $json->trans152_, $html);
        $html = str_replace("{{trans151_}}", $json->trans151_, $html);        
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{motivo_rechazo}}", $data_producto['motivo'], $html);
        
        $html = str_replace("{{trans149_}}",$json->trans149_, $html);
        $html = str_replace("{{trans150_}}",$json->trans150_, $html);

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para = $data_user['correo'] . ',felixespitia@hotmail.com, nayiver_10@hotmail.com, dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        
        // $para      = 'lfospinoayala@gmail.com, luisospinoa@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans152_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        parent::addLogSubastas("EMAIL DE RECHAZO EXITOSO => ". $data_user['correo']);

        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function get_data_subasta_por_id_producto(Array $subasta){
        parent::conectar();
        $misSubastas_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$subasta[id]'; ");
        parent::cerrar();

        return $misSubastas_revision;
    }

    function tratar_banner(Array $data){
        if(count($data)<=0 || count($data)<22) return  array("status"=>"fail", "mensagge"=>"falta un o unos banner"); 
        $array_respuesta=[]; 
        $bandera_error=0; 

        $truncar_banner ="TRUNCATE TABLE banner"; 
        parent::conectar();
        parent::query($truncar_banner);
        parent::cerrar();


        foreach ($data as $x => $banner) {
        $url_button=  $banner["button-url"]; 
        $subtitulo= addslashes($banner["subtitulo"]);
        $insert_banner="INSERT INTO banner
        (   id,
            subtitulo,
            tipo,
            img,
            iso_code_2,
            button,
            `button-url`, 
            nota, 
            titulo,
            idioma,
            estado,
            fecha_actualizacion,
            fecha_creacion,
            img_responsive
        )
        VALUES(
            '$banner[id]',
            '$subtitulo', 
            '$banner[tipo]', 
            '$banner[img]', 
            '$banner[iso_code_2]', 
            '$banner[button]', 
            '$url_button',
            '$banner[nota]',
            '$banner[titulo]',
            '$banner[idioma]',
            '$banner[estado]',
            '$banner[fecha_actualizacion]',
            '$banner[fecha_creacion]',
            '$banner[img_responsive]'
        )";
        parent::conectar();
        $id_dpn = parent::queryRegistro($insert_banner);
        parent::cerrar();
        if (intval( $id_dpn ) > 0) {
           array_push($array_respuesta, array("status"=>"succes", "mensagge"=>"insertado banner"." ".($x+1))); 
        }else{
           $bandera_error=1;
           array_push($array_respuesta, array("status"=>"fail", "mensagge"=>" no insertado banner"." ".($x+1))); 
        }

        }
        return array("status"=>"success", "mensagge"=>"todo los banners actualizados");  
    }

    function getColoresTallas(){
        $selectxproductosxtallas  = "SELECT * FROM buyinbig.productos_tallas;";
        $selectxproductosxcolores = "SELECT * FROM buyinbig.productos_colores;";


        parent::conectar();
        $productos_tallas  = parent::consultaTodo($selectxproductosxtallas);
        $productos_colores = parent::consultaTodo($selectxproductosxcolores);
        parent::cerrar();

        return Array(
            "status"  => "success",
            "colores" => $productos_colores,
            "tallas"  => $productos_tallas
        );
    }

}
?>