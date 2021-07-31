<?php

require 'nasbifunciones.php';
require '../../Shippo.php';
require 'tcc.php';

class Carrito extends Conexion
{
    private $clave_to_tcc = "CLIENTETCC608W3A61CJ"; //Prueba.
    private $unidad_negocio_paqueteria = 1485100; //Prueba.
    private $unidad_negocio_mensajeria = 5625200; //Prueba.

    public function __construct(){
        $this->clave_to_tcc = "NASBY7893DJ90LK2DHJY"; //Producción.
        $this->unidad_negocio_paqueteria = 1779500; //Producción.
        $this->unidad_negocio_mensajeria = 5784900; //Producción.
    }

    public function agregarMasivoDeveloper( Array $data ){
        $response = array();
        foreach ($data['items'] as $key => $value) {
            $response[] = array(
                'params'  => $value,
                'response' => $this->agregar( $value )
            );
        }
        return ["response" =>$response, "request" => $data];
    }

    public function agregar(Array $data){
        if(!isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto'])|| !isset($data['cantidad'])|| !isset($data['moneda'])) {

            return array(
                'status'  => 'fail',
                'message' => 'no data',
                'data'    => null
            );
        }

        $precio_envio = 0;
        if ( isset( $data['precio_envio'] ) ) {
            $precio_envio = $data['precio_envio'];
        }

        $producto = $this->productoId($data);
        if($producto['status'] != 'success') return $producto;


        
        $producto = $producto['data'];

        if($producto['uid'] == $data['uid'] && $producto['empresa'] == $data['empresa']) return array('status' => 'productoPertenece', 'message'=> 'este producto te pertenece', 'cantidad'=> 0, 'data' => null);


        $nasbifuncionesTopeVentas = new Nasbifunciones();
        $resultTopeVentas         = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
            'uid'     => $producto['uid'],
            'empresa' => $producto['empresa']
        ]);
        unset($nasbifuncionesTopeVentas);

        $producto['exposicion']           = intval( $producto['exposicion'] );
        $producto['categoria']            = intval( $producto['categoria'] );
        $producto['subcategoria']         = intval( $producto['subcategoria'] );

        if( $resultTopeVentas['status'] == 'success' && $producto['exposicion'] == 1 ){
            $producto['exposicion'] = 3;
        }
        
        if( $producto['exposicion'] == 1 ){
            $producto['comisiones_categoria'] = floatval( $producto['porcentaje_gratuita'] );

        }else if( $producto['exposicion'] == 2 ){
            $producto['comisiones_categoria'] = floatval( $producto['porcentaje_clasica'] );

        }else if( $producto['exposicion'] == 3 ){
            $producto['comisiones_categoria'] = floatval( $producto['porcentaje_premium'] );

        }


        $fecha = intval(microtime(true)*1000);
        $carrito = null;


        $selectusuario = null;
        if( intval($producto['empresa']) == 1 ) {
            parent::conectar();
            $selectxempresa = "SELECT e.* FROM empresas e WHERE e.id = '$producto[uid]';";
            $selectempresa = parent::consultaTodo($selectxempresa);

            if( count($selectempresa) > 0 ) {
                $empresa = $this->mapEmpresa($selectempresa)[0];
                $data['refer'] = $empresa['referido'];
            }
        }else{
            parent::conectar();
            $selectxusuario = "SELECT * FROM peer2win.usuarios WHERE id = '$producto[uid]';";
            $selectusuario = parent::consultaTodo($selectxusuario);
            
            if( count($selectusuario) > 0 ) {
                $selectusuario = $selectusuario[0];
                $data['refer'] = $selectusuario['referido_nasbi'];
            }
        }


        $refer = '';
        if(isset($data['refer'])) $refer = $data['refer'];
        $data          = $this->mapAddCarrito($data, true);
        $existecarrito = $this->productoCarrito($data);
        parent::conectar();


        $cantidad = 0;
        $pasos = "";
        $updatecarrito = "";
        $resultRestarEnStockDetalleProducto = "";
        
        if($existecarrito['status'] == 'fail'){

            $pasos = " paso 1 ";

            // $existecarritoMismaDivisa = $this->validarMonedasCarrito( $data );
            // if($existecarritoMismaDivisa['status'] == 'success'){
                //Validamos si los articulos que hay en el carrito son diferentes a la moneda nueva que ingresa.
                $uid_redsocial = "";
                if(isset($data['uid_redsocial'])){
                    $uid_redsocial = $data['uid_redsocial'];
                }
                $empresa_redsocial = "";
                if(isset($data['empresa_redsocial'])){
                    $empresa_redsocial = $data['empresa_redsocial'];
                }


                $data['moneda'] = "COP"; // Temporal 2021-04-11 06:15PM

                $insertcarrito = 
                "INSERT INTO carrito(
                    uid,
                    empresa,
                    id_producto,
                    cantidad,
                    moneda,
                    estado,
                    tipo,
                    exposicion_pt,
                    categoria_pt,
                    subcategoria_pt,
                    comisiones_categoria_pt,
                    refer,
                    fecha_creacion,
                    fecha_actualizacion,
                    uid_redsocial,
                    empresa_redsocial,
                    precio_envio,
                    porcentaje_tax_pt
                )
                VALUES(
                    '$data[uid]',
                    '$data[empresa]',
                    '$data[id_producto]',
                    '$data[cantidad]',
                    '$data[moneda]',
                    1,
                    1,
                    $producto[exposicion],
                    $producto[categoria],
                    $producto[subcategoria],
                    $producto[comisiones_categoria],
                    '$refer',
                    '$fecha',
                    '$fecha',
                    '$uid_redsocial',
                    '$empresa_redsocial',
                    $precio_envio,
                    $producto[porcentaje_tax]
                );";

                $carrito = parent::queryRegistro($insertcarrito);

                $resultRestarEnStockDetalleProducto = $this->restarEnStockDetalleProducto( $carrito, $data );
                $existecarrito = $this->productoCarrito2($data);
                /* print_r($resultRestarEnStockDetalleProducto); */
                if (  $resultRestarEnStockDetalleProducto['status'] != "success" ) {
                    if( $resultRestarEnStockDetalleProducto['status'] == 'sinEspecificaciones' ){


                        $existecarrito['data']['cantidad']         = intval( $existecarrito['data']['cantidad'] );
                        $existecarrito['data']['cantidadProducto'] = intval( $existecarrito['data']['cantidadProducto'] );
                        /* print_r($existecarrito['data']);
                        print_r($data); */
                        if ( $data['cantidad'] > $existecarrito['data']['cantidadProducto'] ) {
                            // Solicitas más que lo que tienen disponible en stock.
                            return array(
                                "status"     => "stockError",
                                "mensaje"    => "estas intentando agregar mas de la cantidad disponible",
                                "data"       => $data,
                                "stock"      => $existecarrito['data']['cantidadProducto'],
                                "agregar"    => ($existecarrito['data']['cantidad'] + $data['cantidad']),
                                "operacion"  => ($existecarrito['data']['cantidadProducto'] - ($existecarrito['data']['cantidad'] + $data['cantidad']))
                            );

                        }else{
                            $cantidad = $existecarrito['data']['cantidad'] + $data['cantidad'];
                            
                        }

                    }else{
                        return $resultRestarEnStockDetalleProducto;
                    }
                }
            // }else{
            //     return $existecarritoMismaDivisa;
            // }
        }else if($existecarrito['status'] == 'success'){

            $pasos .= " paso 2 ";

            
            $data['cantidad']                  = intval( $data['cantidad'] );
            $existecarrito['data']['cantidad'] = intval( $existecarrito['data']['cantidad'] );

            $cantidad = 0;
            if ( isset( $data['accion_reemplazar_cantidad'] ) ) {
                // A través de esta variable sabemos si vamos a reemplazar por el nuevo valor o sumamos.
                $cantidad                          = $data['cantidad'];

                $pasos .= " paso 3 => " . $cantidad;
                
            }else{
                $cantidad                          = $existecarrito['data']['cantidad'] + $data['cantidad'];
                $pasos .= " paso 4 => " . $cantidad;
            }

            $idcarrito = $existecarrito['data']['id'];

            $resultRestarEnStockDetalleProducto = $this->restarEnStockDetalleProducto( $idcarrito, $data );


            if (  $resultRestarEnStockDetalleProducto['status'] != 'success') {

                if ( $resultRestarEnStockDetalleProducto['status'] == 'eliminado') {
                    $cantidad = $resultRestarEnStockDetalleProducto['variaciones_totales'];
                    $pasos .= " paso 5 => " . $cantidad;

                }else if( $resultRestarEnStockDetalleProducto['status'] == 'sinEspecificaciones' ){

                    $existecarrito['data']['cantidad']         = intval( $existecarrito['data']['cantidad'] );
                    $existecarrito['data']['cantidadProducto'] = intval( $existecarrito['data']['cantidadProducto'] );

                    if ( ($existecarrito['data']['cantidad'] + $data['cantidad']) > $existecarrito['data']['cantidadProducto'] ) {
                        // Solicitas más que lo que tienen disponible en stock.
                        return array(
                            "status"     => "stockError",
                            "mensaje"    => "estas intentando agregar mas de la cantidad disponible",
                            "data"       => $data,
                            "stock"      => $existecarrito['data']['cantidadProducto'],
                            "agregar"    => ($existecarrito['data']['cantidad'] + $data['cantidad']),
                            "operacion"  => ($existecarrito['data']['cantidadProducto'] - ($existecarrito['data']['cantidad'] + $data['cantidad']))
                        );

                    }else{
                        $cantidad = $existecarrito['data']['cantidad'] + $data['cantidad'];
                        
                    }
                }else{
                    return $resultRestarEnStockDetalleProducto;
                }
            }else{
                $selectxvariacionesxtotal = "SELECT SUM(cantidad) AS cantidades_agregadas FROM productos_transaccion_especificacion_producto WHERE id_carrito = '$idcarrito';";

                $selectxvariacionesxtotal = parent::consultaTodo( $selectxvariacionesxtotal );
                $selectxvariacionesxtotal = $selectxvariacionesxtotal[0];
                $cantidad = $selectxvariacionesxtotal['cantidades_agregadas'];

                $pasos .= " paso 6 => " . $cantidad .  ' --> Carrito: ' . $idcarrito;
            }

            $updatecarrito = 
            "UPDATE carrito
            SET
                cantidad                = $cantidad,

                exposicion_pt           = $producto[exposicion],
                categoria_pt            = $producto[categoria],
                subcategoria_pt         = $producto[subcategoria],
                comisiones_categoria_pt = $producto[comisiones_categoria],
                porcentaje_tax_pt       = $producto[porcentaje_tax],

                estado               = 1,
                refer                = '$refer',
                fecha_actualizacion  = $fecha
            WHERE id = '$idcarrito' AND uid = '$data[uid]' AND empresa = '$data[empresa]' AND id_producto = '$data[id_producto]';";

            $carrito = parent::query($updatecarrito);

        }
        parent::cerrar();
        if(!$carrito) {
            return array(
                'status'        => 'error',
                'message'       => 'no se pudo agregar al carrito - agregar()',
                'cantidad'      => 0,
                'data'          => null,
                'carrito'       => $carrito,
                'existecarrito' => $existecarrito,
                'pasos'         => $pasos,
                'updatecarrito' => $updatecarrito,
                'resultRestarEnStockDetalleProducto' => $resultRestarEnStockDetalleProducto
            );
        }
        
        return array(
            'status'             => 'success',
            'message'            => 'agregado al carrito',
            'cantidad'           => count($carrito),
            'data'               => $carrito,
            'cantidad_articulos' => $cantidad
        );
    }

    public function validarMonedasCarrito(Array $data)
    {
        // Esta función de encarga de validar que el articulo tenga la misma
        // moneda que los demás que estan dentro del carrito actual

        if(!isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        parent::conectar();

        $selectxcarritoxcompra = "
        SELECT *
        FROM buyinbig.carrito
        WHERE buyinbig.carrito.uid = '$data[uid]' AND estado = 1";

        $selectcarritocompra = parent::consultaTodo($selectxcarritoxcompra);
        if(count($selectcarritocompra) <= 0) {
            return array(
                'status' => 'success',
                'message'=> 'No tiene carrito, puedes agregar este articulo.',
                'data' => null
            );
        } else if ($selectcarritocompra[0]["moneda"] != $data["moneda"]) {
            return array(
                'status' => 'errorMonedaCarrito',
                'message'=> 'Actualmente el carrito cuenta con otros articulos agregados con una divisa diferente a ' . $data['moneda'],
                'data' => null,
                'selectcarritocompra' => $selectcarritocompra[0]["moneda"],
                'data_moneda' => $data["moneda"]
            );
        }

        
        
       /* $selectxvalidaxmonedaxcarrito = "
        SELECT *
        FROM buyinbig.carrito
        WHERE buyinbig.carrito.uid = '$data[uid]' AND buyinbig.carrito.moneda = ''; ";

        parent::conectar();
        $selectvalidamonedacarrito = parent::consultaTodo($selectxvalidaxmonedaxcarrito);

        //print_r($selectxvalidaxmonedaxcarrito);

        parent::cerrar();
        if(count($selectvalidamonedacarrito) <= 0) {
            return array(
                'status' => 'errorMonedaCarrito',
                'message'=> 'Actualmente el carrito cuenta con otros articulos agregados con una divisa diferente a ' . $data['moneda'],
                'data' => null
            );
        }*/
        
        //$carrito = $carrito[0];
        //, 'cantidad'=> count($carrito), 'data' => $carrito
        return array('status' => 'success', 'message'=> 'Puedes agregar este articulo.');
    }

    public function agregarNoLogueado(Array $data)
    {
        if(!isset($data)) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        foreach ($data as $x => $carrito) {
            $this->agregar($carrito);
        }

        return array('status' => 'success', 'message'=> 'agregado al carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }

    public function eliminarNew(Array $data)
    {
        if(!isset($data['uid']));
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $deletexcarrito = "delete from carrito WHERE uid = '$data[uid]'";
        $delete = parent::query($deletexcarrito);
        //parent::cerrar();
        if(!$delete) return array('status' => 'fail', 'message'=> 'no eliminado del carrito', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'eliminado del carrito', 'data' => null);
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


        $productos_transaccion_especificacion_producto = parent::consultaTodo("SELECT * FROM productos_transaccion_especificacion_producto WHERE id_carrito = $data[id];");

        if ( count($productos_transaccion_especificacion_producto) > 0 ) {
            foreach ($productos_transaccion_especificacion_producto as $key => $value) {
                //inicio nuevo codigo
                $deletexproductosxtransaccionxespecificacionxproducto =  "DELETE FROM productos_transaccion_especificacion_producto WHERE id = $value[id];";
                parent::query($deletexproductosxtransaccionxespecificacionxproducto);
                //fin nuevo codigo
            }
        }

        parent::cerrar();
        if(!$delete) return array('status' => 'fail', 'message'=> 'no eliminado del carrito', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'eliminado del carrito', 'data' => null);
    }

    public function actualizar(Array $data)
    {
        if(!isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto']) || !isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        


        $producto = $this->productoId($data);
        if($producto['status'] != 'success') return $producto;
        
        

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
        $selectcarritoxuserxproductoxmoneda = 
        "SELECT 
            c.*,
            p.cantidad AS cantidadProducto,
            p.estado AS estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND c.id_producto = '$data[id_producto]' AND c.tipo = 1 AND p.estado = 1;";
        /* echo $selectcarritoxuserxproductoxmoneda; */

        $carrito = parent::consultaTodo($selectcarritoxuserxproductoxmoneda);

        parent::cerrar();
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no existe en el carrito - v1', 'cantidad'=> 0, 'cantidadProducto' => 0, 'data' => null);
        
        $carrito = $carrito[0];
        return array('status' => 'success', 'message'=> 'existe en el carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }

    public function productoCarrito2(Array $data){
        if(!isset($data['uid']) || !isset($data['empresa']) || !isset($data['id_producto']) || !isset($data['cantidad']) || !isset($data['moneda'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectcarritoxuserxproductoxmoneda = 
        "SELECT 
            c.*,
            p.cantidad AS cantidadProducto,
            p.estado AS estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto
        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND c.id_producto = '$data[id_producto]' AND c.tipo = 1 AND p.estado = 1;";
        /* echo $selectcarritoxuserxproductoxmoneda; */

        $carrito = parent::consultaTodo($selectcarritoxuserxproductoxmoneda);

        /* parent::cerrar(); */
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no existe en el carrito - v1', 'cantidad'=> 0, 'cantidadProducto' => 0, 'data' => null);
        
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
        if(count($carrito) <= 0) return array('status' => 'fail', 'message'=> 'no existe en el carrito - v2', 'cantidad'=> 0, 'data' => null);
        
        $carrito = $carrito[0];
        return array('status' => 'success', 'message'=> 'existe en el carrito', 'cantidad'=> count($carrito), 'data' => $carrito);
    }

    public function carritoUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $respuesta_envio=[]; 

        $selectcarritoxuser = 
            "SELECT 
                c.*,
                p.cantidad AS cantidadProducto,
                p.precio_usd,
                p.precio AS precio_local,
                p.moneda_local,
                p.oferta,
                p.porcentaje_oferta,
                p.titulo,
                p.foto_portada,
                p.estado AS estado_producto,
                p.uid AS uid_vendedor,
                p.empresa AS empresa_producto,
                p.envio AS tipo_envio,
                p.producto,
                pe.id AS id_prodcuto_envio,
                pe.id_shippo AS id_prodcuto_envio_shippo,
                d.id AS id_direccion_vendedor,
                d.id_shippo AS id_direccion_vendedor_shippo,

                p.exposicion,
                p.porcentaje_tax,
                ctg.porcentaje_gratuita,
                ctg.porcentaje_clasica,
                ctg.porcentaje_premium,

                IF( p.empresa = 0,
                    u.usuarios_estados_teindas_oficiales_id,
                    e.usuarios_estados_teindas_oficiales_id
                ) AS tienda_oficial

            FROM carrito c 
            INNER JOIN productos p ON c.id_producto = p.id
            INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
            INNER JOIN direcciones d ON p.id_direccion = d.id

            INNER JOIN categorias ctg ON ctg.CategoryID = p.categoria

            LEFT JOIN buyinbig.empresas e ON(p.uid = e.id)
            LEFT JOIN peer2win.usuarios u ON(p.uid = u.id)

            WHERE c.uid = '$data[uid]' AND c.empresa = '$data[empresa]' AND p.estado = 1 AND c.estado = 1;";

        // var_dump( $selectcarritoxuser );
        parent::conectar();
        $carrito = parent::consultaTodo($selectcarritoxuser);
        parent::cerrar();

        if(count($carrito) == 0) {
            return array(
                'status'      => 'fail',
                'message'     => 'no tiene productos en el carrito',
                'cantidad'    => 0,
                'data'        => null,
                'dataTotales' => null
            );
        }
        // return $carrito;
        $arraycarito = $this->carritoUsuarioMap($carrito, false);
        // return $arraycarito;

        // //agregar lo de envio
        $respuesta_envio =  $this->agregar_valor_envio_producto([
            "array_carrito" => $arraycarito['carrito'],
            "uid"           => $data['uid'],
            "empresa"       => $data['empresa']
        ]);

        $arraycarito['carrito']                                 = $respuesta_envio["data_carrito"]; 
        $arraycarito['carritoTotales']["totales_envio_general"] = $respuesta_envio["data_general_envio"]; 
        // // // //fin agregar lo de envio

        $status = "success";
        if( $respuesta_envio['status'] != "success" ){
            $status = "errorAliadosPendientes";
        }

        $response = array(
            'status'           => $status,
            'message'          => 'carrito usuario',
            'cantidad'         => count($arraycarito['carrito']),
            'data'             => $arraycarito['carrito'],
            'dataTotales'      => $arraycarito['carritoTotales'],
            'direccion_activa' => $respuesta_envio["direccion_activa"],
            'respuesta_envio'  => $respuesta_envio
        );
        return $response;
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
        $selectproductosxcarritoxuserxnoxlog = 
        "SELECT 
            p.id AS id_producto,
            p.cantidad AS cantidadProducto,
            p.precio_usd,
            p.precio AS precio_local,
            p.moneda_local,
            p.oferta,
            p.porcentaje_oferta,
            p.titulo,
            p.foto_portada,
            p.estado AS  estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto,
            p.envio AS tipo_envio,
            pe.id AS id_prodcuto_envio,
            pe.id_shippo AS id_prodcuto_envio_shippo,
            d.id AS id_direccion_vendedor,
            d.id_shippo AS id_direccion_vendedor_shippo,
            
            p.exposicion,
            p.porcentaje_tax,
            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium

        FROM productos p
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id
        INNER JOIN categorias ctg ON ctg.CategoryID = p.categoria
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
            $result= $this->agregar($carrito);
            parent::addLog("----+> [ validando carrito no logeado ]: " . json_encode($result) );
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
        $empresa                               = "empresas s.a.s.";
        $transaction["tracking_number"]        = round(microtime(true) * 1000);
        $transaction["tracking_url_provider"]  = "";
        $transaction["label_url"]              = "";
        $transaction["commercial_invoice_url"] = "";
        $transaction["object_id"]              = round(microtime(true) * 1000);
        $updatedireccion                       = 
        "UPDATE productos_transaccion_envio
        
        SET
            numero_guia           = '$transaction[tracking_number]',
            url_numero_guia       = '$transaction[tracking_url_provider]',
            empresa               = '$empresa',
            etiqueta_envio        = '$transaction[label_url]',
            factura_comercial     = '$transaction[commercial_invoice_url]',
            id_envio_shippo       = '$data[id_envio]',
            id_ruta_shippo        = '$data[id_ruta]',
            id_transaccion_shippo = '$transaction[object_id]'

        WHERE id_carrito          = '$data[id_carrito]'";
        $actualizar = parent::query($updatedireccion);
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
    }
    public function pagarCarrito(Array $data)
    {
        if(!isset($data['carrito']) || !isset($data['metodo_pago']) || !isset($data['direccion'])) {
            return array('status' => 'fail', 'message'=> 'no data', 'msj' => 1, 'params' => $data['direccion']);
        }

        if(!isset($data['carrito']) || !isset($data['carrito']['id']) || !isset($data['carrito']['uid']) || !isset($data['carrito']['empresa']) || !isset($data['carrito']['precio_moneda_actual_usd']) ) {

            return array('status' => 'fail', 'message'=> 'no data', 'msj' => 2, 'params' => $data['direccion']);
        }

        
        $pago_digital_calculos_pasarelas_id = 0;

        $pago_digital = array();
        $pago_digital_id = 0;

        if(!isset($data['metodo_pago']) || !isset($data['metodo_pago']['id'])) {
            return array('status' => 'fail', 'message'=> 'no data', 'msj' => 3, 'params' => $data['direccion']);
        }else{
            if( isset( $data['metodo_pago']['pago_digital_id'] ) && $data['metodo_pago']['pago_digital_id'] != null ) {
                $pago_digital_id = intval( $data['metodo_pago']['pago_digital_id'] );
                
                $selectxpagoxdigital=
                    "SELECT * FROM buyinbig.pago_digital WHERE ID = $pago_digital_id;";
                parent::conectar();
                $pago_digital = parent::consultaTodo($selectxpagoxdigital);
                parent::cerrar();
                if ( count($pago_digital) == 0) {
                    $pago_digital = null;
                }else{
                    $pago_digital = $pago_digital[0];
                    $pago_digital['METODO_PAGO_USADO_ID'] = intval( $pago_digital['METODO_PAGO_USADO_ID'] );
                    parent::conectar();
                    $pago_digital_json_proveedores = parent::consultaTodo("SELECT * FROM buyinbig.pago_digital_json_proveedores WHERE PAGO_DIGITAL_ID = $pago_digital_id;");
                    parent::cerrar();

                    $pago_digital['JSON_PROVEEDORES'] = array();
                    if ( count($pago_digital_json_proveedores) == 0) {
                        $pago_digital_json_proveedores = null;
                    }else{
                        $pago_digital['JSON_PROVEEDORES'] = $pago_digital_json_proveedores;
                    }
                }
            }
        }

        if(!isset($data['direccion']) || !isset($data['direccion']['id']) ) {
            return array('status' => 'fail', 'message'=> 'no data', 'msj' => 4, 'params' => $data['direccion']);
        }


        $carrito = $this->carritoPagarNew($data['carrito']); // Obtiene todos los carritos del cliente agrupado por id_producto.
        if($carrito['status'] == 'fail') {
            return array('status' => 'fail', 'message'=> 'no data', 'msj' => 5, 'params' => $data['direccion'], 'result' => $carrito);
        }
        $carrito = $carrito['data'];

        $pago = [];
        $request = null;
        parent::conectar();
        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        $montoTotalBD = 0;
        $montoTotalSD = 0;
        $misBD = 0;
        $miSD = 0;


        // return $carrito;
        parent::conectar();
        if ($data["metodo_pago"]["desc"] == "1") {
            $montoTotalBD = parent::number_format($data["metodo_pago"]["bd"], 2);
            $bonos = parent::consultaTodo("SELECT monto FROM nasbicoin_blue nb WHERE uid = $carrito[uid]");
            if (count($bonos) > 0) {
                $misBD = $bonos[0]["monto"];
            }
            if ($montoTotalBD > $misBD) {
                return array('status' => 'errorBD', 'message'=> 'No tienes suficientes bonos de descuento para realizar esta compra');
            }
        }
        if ($data["metodo_pago"]["id"] == "1" || $data["metodo_pago"]["id"] == "3") {
            $bonos = parent::consultaTodo("SELECT monto FROM nasbicoin_gold nb WHERE uid = $carrito[uid]");
            if (count($bonos) > 0) {
                $miSD = $bonos[0]["monto"];

            }
        }

        $montosAPagar = array();
        $productos = array();
        $pagarConCriptosArr = array();


        $pagarConCriptosArr_diferido = array();
        $nasbifunciones = new Nasbifunciones();
        $cuentaPote     = $nasbifunciones->getCuentaBanco( 1 );
        unset($nasbifunciones);

        $fecha = intval(microtime(true)*1000);

        $id_carrito = array();
        $montoPendienteCategoriaVendedor = array();

        //PARA EL CORREO
        $array_de_precios_unidades_correo = array();
        //FIN PARA EL CORREO

        $content_tallas_colores = array();

        $id_carrito_lote_transaccion = 0;
        // return [
        //     "carrito" => $carrito["productos_array"],
        //     "request" => $data
        // ];
        foreach ($carrito["productos_array"] as $key => $value) {
            // return $value;

            $nasbifuncionesTopeVentas = new Nasbifunciones();
            $resultTopeVentas = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
                'uid'     => $value['uid_vendedor'],
                'empresa' => $value['empresa_producto']
            ]);
            unset($nasbifuncionesTopeVentas);

            $value['exposicion_pt']           = intval( $value['exposicion_pt'] );
            $value['categoria_pt']            = intval( $value['categoria_pt'] );
            $value['subcategoria_pt']         = intval( $value['subcategoria_pt'] );

            if( $resultTopeVentas['status'] == 'success' && $value['exposicion_pt'] == 1 ){
                $value['exposicion_pt'] = 3;
            }

            
            $value['codigo_producto_siigo']                 = "";
            $value['codigo_producto_siigo_descripcion']     = "";
            
            if( $value['exposicion_pt'] == 1 ){
                $value['comisiones_categoria_pt']           = floatval( $value['porcentaje_gratuita'] );
                $value['codigo_producto_siigo']             = "";
                $value['codigo_producto_siigo_descripcion'] = "";

            }else if( $value['exposicion_pt'] == 2 ){
                $value['comisiones_categoria_pt']           = floatval( $value['porcentaje_clasica'] );
                $value['codigo_producto_siigo']             = $value['codigo_producto_siigo_clasica'];
                $value['codigo_producto_siigo_descripcion'] = $value['codigo_producto_siigo_clasica_descripcion'];

            }else if( $value['exposicion_pt'] == 3 ){
                $value['comisiones_categoria_pt']           = floatval( $value['porcentaje_premium'] );
                $value['codigo_producto_siigo']             = $value['codigo_producto_siigo_premium'];
                $value['codigo_producto_siigo_descripcion'] = $value['codigo_producto_siigo_premium_descripcion'];

            }


            parent::cerrar();
            //aca calculas el flete!!!!
            $flete = 0;
            $array_carrito_temp[] = $value;
            $respuesta_envio      =  $this->agregar_valor_envio_producto([
                "array_carrito" => $array_carrito_temp,
                "uid"           => $value['uid'],
                "empresa"       => $value['empresa']
            ]);

            $status = "success";
            if( $respuesta_envio['status'] != "success" ){
                return array('status' => 'errorAliadosPendientes', 'message'=> 'Hay articulos con aliados logisticos pendientes por cotizar (No tienen valor de envio).', 'data' => $respuesta_envio);
            }

            $array_carrito_temp = array();
            
            if ( isset($respuesta_envio) ) {
                if ( isset($respuesta_envio['data_general_envio']) ) {
                    if ( isset( $respuesta_envio['data_general_envio']['total_envio_COP'] )) {
                        $flete = $respuesta_envio['data_general_envio']['total_envio_COP'];
                    }
                }
            }
            if ($id_carrito_lote_transaccion == 0 ) {
                $id_carrito_lote_transaccion = $value["id"];
            }
            
            array_push($id_carrito, $value["id"]);

            if ( !isset($value['precio_envio'])) {
                $value['precio_envio'] = 0;
            }

            $tempData = array(
                'id_carrito'      => $value["id"],
                'id_transaccion'  => null,
                'id_producto'     => $value['id_producto']
            );

            parent::conectar();
            $selectxconsultarxtallasxcolores = 
            "SELECT * FROM productos_transaccion_especificacion_producto WHERE id_carrito = '$tempData[id_carrito]' AND id_producto = '$tempData[id_producto]'";
            
            $consultarxtallasxcolores = parent::consultaTodo($selectxconsultarxtallasxcolores);


            if ( !isset($content_tallas_colores[ $value["uid_vendedor"] . '_' . $value["empresa_producto"] ]) ) {
                $content_tallas_colores[ $value["uid_vendedor"] . '_' . $value["empresa_producto"] ] = array(
                    'id_transaccion' => $value["id"],
                    'datos'          => array()
                );

                $tempData['id_transaccion'] = $value["id"];
                if ( count( $consultarxtallasxcolores ) > 0) {
                    array_push( $content_tallas_colores[ $value["uid_vendedor"] . '_' . $value["empresa_producto"] ]['datos'], $tempData);
                }
            }else{
                $tempData['id_transaccion'] = $content_tallas_colores[ $value["uid_vendedor"] . '_' . $value["empresa_producto"] ]['id_transaccion'];
                if ( count( $consultarxtallasxcolores ) > 0) {
                    array_push( $content_tallas_colores[ $value["uid_vendedor"] . '_' . $value["empresa_producto"] ]['datos'], $tempData);
                }
            }

            
            if (!isset($montoPendienteCategoriaVendedor[$value["uid_vendedor"]])) {
                $montoPendienteCategoriaVendedor[$value["uid_vendedor"]] = array();
            }
            if (!isset($montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]])) {
                $montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]] = array();
            }
            if (!isset($montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]][$value["id_producto"]])) {
                $montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]][$value["id_producto"]] = array(
                    "id_carrito" => $value["id"],
                    "monto"      => 0,
                    "moneda"     => 0
                );
            }

            $monto_pendiente_aux = ($value["precio"] * $value["cantidad"] * (1 -($value["porcentaje_oferta"] / 100)));
            $montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]][$value["id_producto"]]["monto"] = $monto_pendiente_aux;

            $montoPendienteCategoriaVendedor[$value["uid_vendedor"]][$value["categoria"]][$value["id_producto"]]["moneda"] = $value["moneda_local"];

            $dbpreciousd = 0;
            if($value['cantidad'] > $value['cantidadProducto'] && ($value['tipo'] == 1)) {
                return array(
                    'status'   => 'superaStock',
                    'message'  => 'la cantidad que deseas comprar supera el stock',
                    'producto' => $value["id_producto"]
                );
            }

            if($value['precio'] <= 0.000000) {
                return array(
                    'status'   => 'fail',
                    'message'  => 'monto invalido',
                    'producto' => $value["id_producto"]
                );
            }


            $totalPago        = $value["cantidad"] * $value["precio_usd"] * (1 -($value["porcentaje_oferta"] / 100));
            $totalRestanteUSD = $value["cantidad"] * $value["precio"]     * (1 -($value["porcentaje_oferta"] / 100));

            $arrayMontosPrueba = array(
                'cantidad'         => $value["cantidad"],
                'precio_usd'       => $value["precio_usd"],
                'totalPago'        => $totalPago,
                'totalRestanteUSD' => $totalRestanteUSD,
                'productos'        => '',
                'totalPagarFiat'   => 0,
                'envio'            => $flete
            );

            $estado_transaccion = 6;
            
            if ( $pago_digital != null ) {
                if ( $pago_digital['METODO_PAGO_USADO_ID'] == 3 || $pago_digital['METODO_PAGO_USADO_ID'] == 4 || $pago_digital['METODO_PAGO_USADO_ID'] == 5 ) {
                    $estado_transaccion = 3;
                }
            }

            $precio_segundo_pago = 0;
            $moneda_segundo_pago = ($data["metodo_pago"]["id"] == "3") ? "Nasbigold":"Nasbiblue";

            if ($montoTotalBD > 0) {
                $montoPagoNasbiBlue = 0;
                $montoNBA = $data["metodo_pago"]["bd"];
                $max50NBA = $totalRestanteUSD / 2;
                $max50NBA = parent::number_format($max50NBA, 2);
                if ($moneda_segundo_pago == "Nasbiblue") {
                    $max50NBA = $totalRestanteUSD * $data["metodo_pago"]["bd_porcentaje"];
                    if (($montoNBA * 1) > ($max50NBA * 1)) {
                        $montoPagoNasbiBlue =  $max50NBA;
                        $montoPagoNasbiBlue = parent::number_format($montoPagoNasbiBlue, 2);
                        $dbpreciousd = $max50NBA / $carrito["precio_moneda_actual_usd"];
                    } else{
                        $montoPagoNasbiBlue =  $montoTotalBD;
                        $dbpreciousd = parent::number_format(($montoTotalBD / $carrito["precio_moneda_actual_usd"]), 2);
                    }
                } else {
                    $montoPagoNasbiBlue =  $montoTotalBD;
                    $dbpreciousd = parent::number_format(($montoTotalBD / $carrito["precio_moneda_actual_usd"]), 2);
                }
                $precio_segundo_pago = $montoPagoNasbiBlue;

                $montoTotalBD       -= $montoPagoNasbiBlue;
                $totalRestanteUSD   -= $montoPagoNasbiBlue;

                // Aquí le creamos los diferidos a la cuenta POTE NASBI QUE RECAUDA TODO EL DINERO
                // DESPUES LOS ADMINISTRATIVOS LIQUIDAN LAS VENTAS.
                $schemaPagarConCriptosArr = array(
                    "carrito" => array(
                        "uid"                      => $value['uid'],
                        "empresa"                  => $value['empresa'],
                        "moneda"                   => $moneda_segundo_pago,
                        "precio"                   => $montoPagoNasbiBlue,
                        "precio_moneda_actual_usd" => $dbpreciousd,
                        "paso"                     => "PASO POR AQUI (1).",
                        "tipo"                     => $value['tipo'],
                        "titulo"                   => $value['titulo'],
                        "id_producto"              => $value['id_producto'],
                        "precio_envio"             => $value['precio_envio']
                    ),
                    "metodo_pago" => array(
                        "address_comprador" => $data["metodo_pago"]["address_comprador_bd"]
                    ),
                    "fecha" => $fecha,
                    "id_carrito" => $value['id'],

                    "data_diferido" => array(
                        'id'                 => null,
                        'uid'                => $cuentaPote['uid'],
                        'empresa'            => $cuentaPote['empresa'],
                        'moneda'             => $moneda_segundo_pago,
                        'all'                => false,

                        'precio'             => $montoPagoNasbiBlue,
                        'precio_momento_usd' => $dbpreciousd,

                        'precio_en_cripto'   => $montoPagoNasbiBlue,

                        'address'            => $cuentaPote['address_Nasbiblue'],
                        'id_transaccion'     => $value['id'],
                        'tipo_transaccion'   => $value['tipo'],
                        'tipo'               => 'diferido',
                        'accion'             => 'push',
                        'descripcion'        => addslashes('Venta de producto '.$value['titulo']),
                        'fecha'              => $fecha,
                        'id_producto'        => $value['id_producto']
                    )
                );

                $pagarConCriptosArr[] = $schemaPagarConCriptosArr;
                
                $array_de_precios_unidades_correo[$value["id_producto"]]["moneda"]       = $moneda_segundo_pago;  //correo
                $array_de_precios_unidades_correo[$value["id_producto"]]["precio_local"] = $montoPagoNasbiBlue;
            }


            parent::addLog("-----+> [ data.metodo_pago ]: " . json_encode($data['metodo_pago']));
            $precio_cupon         = 0;
            $serial_cupon         = "";
            $cupones_historial_id = 0;
            if( isset( $data['metodo_pago']['cupon_carrito_usado'][ $value["id"] ] ) ){
                $schemaCupon          = $data['metodo_pago']['cupon_carrito_usado'][ $value["id"] ];

                parent::addLog("-----+> [ --- schemaCupon --- ]: " . json_encode($schemaCupon));


                $precio_cupon         = floatval( $schemaCupon['monto'] );
                $cupones_historial_id = $schemaCupon['cupon']['id'];
                $totalRestanteUSD     = $totalRestanteUSD - $precio_cupon;

                $serial_cupon         = $schemaCupon['cupon']['codigo'];
            }

            // return $data;

            $value['refer'] = intval( $value['refer'] );
            if( $value['refer'] > 0 ){
                
                $selectxplanxactivo = 
                    "SELECT * FROM peer2win.usuarios WHERE CURRENT_TIMESTAMP BETWEEN fecha_inicio AND fecha_fin AND id = '$value[refer]'";

                $tiene_plan_activo = parent::consultaTodo( $selectxplanxactivo );
                if( COUNT( $tiene_plan_activo ) == 0 ){
                    $value['refer'] = "";
                }
            }else{
                $value['refer'] = "";
            }
            
            $referido_compras        = "";
            $selectxreferidoxcompras = "";
            $value['empresa']        = intval("" . $value['empresa']);

            if( $value['empresa'] == 0 ){
                $selectxreferidoxcompras =
                "SELECT
                    referido_nasbi AS 'CODIGO_REFERIDO'
                FROM peer2win.usuarios
                WHERE id = '$value[uid]';";

            }else{
                $selectxreferidoxcompras =
                "SELECT
                    referido AS 'CODIGO_REFERIDO'
                FROM buyinbig.empresas
                WHERE id = '$value[uid]';";
            }
            $select_referido_compras = parent::consultaTodo( $selectxreferidoxcompras );
            if( COUNT( $select_referido_compras ) > 0 ){
                $select_referido_compras = $select_referido_compras[0];
                $referido_compras = $select_referido_compras['CODIGO_REFERIDO'];
            }




            // metodo 1 == Saldo dorado
            if ($data["metodo_pago"]["id"] == "1") {
                $totalPagarSD  = $totalRestanteUSD;
                $totalUSDPagar =  parent::number_format(($totalPagarSD / $carrito["precio_moneda_actual_usd"]),2);

                $totalPagarSD  = floatval( $totalPagarSD );
                $miSD          = floatval( $miSD );
                if ($totalPagarSD > $miSD) {
                    return array(
                        'status'       => 'errorSD',
                        'message'      => 'No tienes suficiente SD para realizar esta compra (02) / ' . $miSD .  " / " . $montoTotalSD . " // " . $totalPagarSD,
                        'miSD'         => $miSD,
                        'montoTotalSD' => $montoTotalSD,
                        'totalPagarSD' => $totalPagarSD
                    );
                }

                $miSD -= $totalPagarSD;

                $fleteAux = 0;
                if ( $pago_digital == null ) {
                    $fleteAux = $flete;
                }


                $schemaReporteFacturacion = Array(
                    "costo_producto"           => ($totalPagarSD + $precio_segundo_pago + $precio_cupon),
                    "flete"                    => $flete,

                    "recaudo_pasarela"         => $totalPagarSD,
                    
                    "segundo_metodo_pago"      => 'Nasbiblue',
                    "monto_segundo_metodo_pago"=> $precio_segundo_pago,

                    "serial_bono"              => $serial_cupon,
                    
                    "metodoPago"               => 6,
                    "porcentaje_comision_nasbi"=> $value['comisiones_categoria_pt'],

                    "tipo_usuario_comprador"   => ($value['empresa']          == 0? "NATURAL": "JURIDICA"),
                    "tipo_usuario_vendedor"    => ($value['empresa_producto'] == 0? "NATURAL": "JURIDICA"),

                    "descripcion_pago"         => "Compra " . $value['titulo']
                );

                parent::cerrar();
                $result_pago_digital_calculos_pasarelas = parent::calcularMontosPagoDigital($schemaReporteFacturacion);
                parent::conectar();
                
                $pago_digital_calculos_pasarelas = array();
                $pago_digital_calculos_pasarelas_id = 0;

                if( $result_pago_digital_calculos_pasarelas['status'] == 'success' && $value['codigo_producto_siigo'] != '' ){
                    $pago_digital_calculos_pasarelas_id = $result_pago_digital_calculos_pasarelas['data']['id_insercion'];
                }


                parent::addLog("---+> logs palma: " . json_encode(Array("ENVIA" => $schemaReporteFacturacion, "RECIBE" => $result_pago_digital_calculos_pasarelas)));

                $selectxplan = 
                    "SELECT
                        p.id,
                        p.porcentaje_nasbi
                    FROM peer2win.usuarios u
                    INNER JOIN peer2win.paquetes p ON (u.plan = p.id)
                    WHERE u.id = '$value[refer]';";
                

                parent::cerrar();
                parent::conectar();
                $selectxinfoxplan = parent::consultaTodo($selectxplan);

                if( COUNT( $selectxinfoxplan ) == 0){
                    $selectxinfoxplan = Array(
                        "id" => 0,
                        "porcentaje_nasbi" => 0
                    );

                }else{
                    $selectxinfoxplan                     = $selectxinfoxplan[0];
                    $selectxinfoxplan['id']               = intval( $selectxinfoxplan['id'] );
                    $selectxinfoxplan['porcentaje_nasbi'] = floatval( $selectxinfoxplan['porcentaje_nasbi'] );
                }

                $comisionPlan = $value['refer'] != '' ? $selectxinfoxplan['porcentaje_nasbi'] : 0;
                $idPlan = $value['refer'] != '' ? $selectxinfoxplan['id'] : 0;

                $productos[] = "(
                    '$value[id]',
                    '$value[id_producto]',
                    '$value[uid_vendedor]',
                    '$value[uid]',
                    '$value[cantidad]',
                    '1',
                    'Nasbigold',
                    '$totalPagarSD',
                    '$totalUSDPagar',
                    '$precios[Nasbigold]',
                    '$value[tipo]',
                    '$estado_transaccion',
                    '0',
                    '1',
                    '$value[empresa_producto]',
                    '$value[empresa]',
                    '$value[titulo]',
                    '$value[refer]',
                    '$value[exposicion_pt]',
                    '$value[categoria_pt]',
                    '$value[subcategoria_pt]',
                    '$value[comisiones_categoria_pt]',
                    '$fecha',
                    '$fecha',
                    '$value[uid_redsocial]',
                    '$value[empresa_redsocial]',
                    $flete,
                    $precio_segundo_pago,
                    '$moneda_segundo_pago',
                    $pago_digital_id,
                    $pago_digital_calculos_pasarelas_id,
                    $id_carrito_lote_transaccion,
                    '$value[comisiones_categoria_pt]',
                    '$idPlan',
                    '$comisionPlan',
                    $value[porcentaje_tax_pt],
                    $precio_cupon,
                    $cupones_historial_id,
                    '$referido_compras'
                )";

                // return ['insert' =>$productos, 'select' => $selectxinfoxplan];

                // Aquí le creamos los diferidos a la cuenta POTE NASBI QUE RECAUDA TODO EL DINERO
                // DESPUES LOS ADMINISTRATIVOS LIQUIDAN LAS VENTAS.

                $schemaPagarConCriptosArr = array(
                    "carrito" => array(
                        "uid"                      => $value['uid'],
                        "empresa"                  => $value['empresa'],
                        "moneda"                   => "Nasbigold",
                        "precio"                   => $totalPagarSD + $fleteAux,
                        "precio_moneda_actual_usd" => $totalUSDPagar,
                        "paso"                     => "PASO POR AQUI (2). " . $totalUSDPagar . " <==> " . $carrito["precio_moneda_actual_usd"],
                        "tipo"                     => $value['tipo'],
                        "titulo"                   => $value['titulo'],
                        "id_producto"              => $value['id_producto'],
                        "precio_envio"             => $value['precio_envio']
                    ),
                    "metodo_pago" => array(
                        "address_comprador" => $data["metodo_pago"]["address_comprador_sd"]
                    ),
                    "fecha" => $fecha,
                    "id_carrito" => $value['id'],

                    "data_diferido" => array(
                        'id'                 => null,
                        'uid'                => $cuentaPote['uid'],
                        'empresa'            => $cuentaPote['empresa'],
                        'moneda'             => "Nasbigold",
                        'all'                => false,

                        'precio'             => $totalPagarSD + $fleteAux,
                        'precio_momento_usd' => $totalUSDPagar,

                        'precio_en_cripto'   => $totalPagarSD + $fleteAux,

                        'address'            => $cuentaPote['address_Nasbigold'],
                        'id_transaccion'     => $value['id'],
                        'tipo_transaccion'   => $value['tipo'],
                        'tipo'               => 'diferido',
                        'accion'             => 'push',
                        'descripcion'        => addslashes('Venta de producto '.$value['titulo']),
                        'fecha'              => $fecha,
                        'id_producto'        => $value['id_producto']
                    )
                );
                $pagarConCriptosArr[] = $schemaPagarConCriptosArr;
                $array_de_precios_unidades_correo[$value["id_producto"]]["moneda"]= 'Nasbigold';  //correo
                $array_de_precios_unidades_correo[$value["id_producto"]]["precio_local"] = $totalPagarSD;

            } else if ($data["metodo_pago"]["id"] == 2) {

                $totalPagarFiat = $totalRestanteUSD;

                $arrayMontosPrueba['totalPagarFiat'] = $totalPagarFiat;

                $precioUSDRestante = parent::number_format(($totalRestanteUSD / $carrito["precio_moneda_actual_usd"]),2);


                $schemaReporteFacturacion = Array(
                    "costo_producto"           => ($totalPagarFiat + $precio_segundo_pago + $precio_cupon),
                    "flete"                    => $flete,

                    "recaudo_pasarela"         => $totalPagarFiat,
                    
                    "segundo_metodo_pago"      => $moneda_segundo_pago,
                    "monto_segundo_metodo_pago"=> $precio_segundo_pago,

                    "serial_bono"              => $serial_cupon,
                    
                    "metodoPago"               => $pago_digital['METODO_PAGO_USADO_ID'], // Solo cuando se usa la pasarela de P.D
                    "porcentaje_comision_nasbi"=> $value['comisiones_categoria_pt'],

                    "tipo_usuario_comprador"   => ($value['empresa']          == 0? "NATURAL": "JURIDICA"),
                    "tipo_usuario_vendedor"    => ($value['empresa_producto'] == 0? "NATURAL": "JURIDICA"),

                    "descripcion_pago"         => "Compra " . $value['titulo']
                );
                parent::cerrar();
                $pago_digital_calculos_pasarelas = parent::calcularMontosPagoDigital($schemaReporteFacturacion);
                parent::conectar();
                $pago_digital_calculos_pasarelas_id = 0;
                if( $pago_digital_calculos_pasarelas['status'] == 'success'){
                    $pago_digital_calculos_pasarelas_id = $pago_digital_calculos_pasarelas['data']['id_insercion'];
                }

                parent::addLog("-----+> [ schemaReporteFacturacion / Fiat ]: " . json_encode([ "SEND" => $schemaReporteFacturacion, "REVICED" => $pago_digital_calculos_pasarelas]));

                $productos[] = "(
                    '$value[id]',
                    '$value[id_producto]',
                    '$value[uid_vendedor]',
                    '$value[uid]',
                    '$value[cantidad]',
                    '0',
                    '$value[moneda]',
                    '$totalPagarFiat',
                    '$precioUSDRestante',
                    '$carrito[precio_moneda_actual_usd]',
                    '$value[tipo]',
                    '$estado_transaccion',
                    '0',
                    '2',
                    '$value[empresa_producto]',
                    '$value[empresa]',
                    '$value[titulo]',
                    '$value[refer]',
                    '$value[exposicion_pt]',
                    '$value[categoria_pt]',
                    '$value[subcategoria_pt]',
                    '$value[comisiones_categoria_pt]',
                    '$fecha',
                    '$fecha',
                    '$value[uid_redsocial]',
                    '$value[empresa_redsocial]',
                    $flete,
                    $precio_segundo_pago,
                    '$moneda_segundo_pago',
                    $pago_digital_id,
                    $pago_digital_calculos_pasarelas_id,
                    $id_carrito_lote_transaccion,
                    '$value[comisiones_categoria_pt]',
                    0,
                    0,
                    $value[porcentaje_tax_pt],
                    $precio_cupon,
                    $cupones_historial_id,
                    '$referido_compras'
                )";

                $array_de_precios_unidades_correo[$value["id_producto"]]["moneda"]       = $value["moneda"];  //correo
                $array_de_precios_unidades_correo[$value["id_producto"]]["precio_local"] = $totalPagarFiat; 
            
            } else if ($data["metodo_pago"]["id"] == 3) {
                // Probar más adelante.
                if ( !isset($data["metodo_pago"]["sd"]) && !isset($data["metodo_pago"]["fiat"]) ) {
                    return array('status' => 'camposVacios', 'message' => 'Faltan los campos SD y FIAT.', 'params' => $data["metodo_pago"]);
                }

                if ( $pago_digital == null ) {
                    return array('status' => 'camposVacios', 'message' => 'Faltan los campos SD y FIAT.', 'params' => $data["metodo_pago"], 'pd' => $pago_digital);
                }


                $json_proveedor = null;
                foreach ($pago_digital['JSON_PROVEEDORES'] as $key => $json_proveedor_aux) {
                    if ( $json_proveedor_aux['PRODUCTO_UID'] == $value['id_producto'] ) {
                        $json_proveedor = $json_proveedor_aux;
                    }
                }
                if ( $json_proveedor != null ) {
                    $flete                               = floatval( $json_proveedor['COSTO_FLETE'] );
                    $combinado_fiat                      = floatval( $json_proveedor['VALOR'] ) - floatval( $json_proveedor['COSTO_FLETE'] );
                    $json_proveedor['VALOR_REAL_COMPRA'] = floatval( $json_proveedor['VALOR_REAL_COMPRA'] );
                    $combinado_sd                        = abs($combinado_fiat - $json_proveedor['VALOR_REAL_COMPRA']);

                    // $data["metodo_pago"]["sd"]   = floatval( $data["metodo_pago"]["sd"]);
                    // $data["metodo_pago"]["fiat"] = floatval( $data["metodo_pago"]["fiat"]) - $flete;
                    
                    $data["metodo_pago"]["sd"]   = $combinado_sd;
                    $data["metodo_pago"]["fiat"] = $combinado_fiat;

                    $totalPagarFiat                      = $data["metodo_pago"]["fiat"];
                    $arrayMontosPrueba['totalPagarFiat'] = $totalPagarFiat;
                    
                    $precioUSDRestante                   = $totalPagarFiat / ($value["precio"] / $value["precio_usd"]);
                    $totalRestanteUSD                    = $precioUSDRestante;

                    $precio_segundo_pago                 = $data["metodo_pago"]["sd"];
                    $moneda_segundo_pago                 = "Nasbigold";

                    $miSD          = floatval( $miSD );

                    if ($data["metodo_pago"]["sd"] > $miSD) {
                        return array(
                            'status'       => 'errorSD',
                            'message'      => 'No tienes suficiente SD para realizar esta compra (02) / ' . $miSD .  " / " . $data["metodo_pago"]["sd"] . " // " . $data["metodo_pago"]["sd"],
                            'miSD'         => $miSD,
                            'montoTotalSD' => $data["metodo_pago"]["sd"],
                            'totalPagarSD' => $data["metodo_pago"]["sd"]
                        );
                    }

                    $miSD -= $data["metodo_pago"]["sd"];

                    $fleteAux = 0;

                    // Aquí le creamos los diferidos a la cuenta POTE NASBI QUE RECAUDA TODO EL DINERO
                    // DESPUES LOS ADMINISTRATIVOS LIQUIDAN LAS VENTAS.
                    $schemaPagarConCriptosArr = array(
                        "carrito" => array(
                            "uid"                      => $value['uid'],
                            "empresa"                  => $value['empresa'],
                            "moneda"                   => "Nasbigold",
                            "precio"                   => $data["metodo_pago"]["sd"],
                            "precio_moneda_actual_usd" => ($value["precio"] / $value["precio_usd"]),
                            "paso"                     => "PASO POR AQUI (2). " . $data["metodo_pago"]["sd"] . " <==> " . ($value["precio"] / $value["precio_usd"]),
                            "tipo"                     => $value['tipo'],
                            "titulo"                   => $value['titulo'],
                            "id_producto"              => $value['id_producto'],
                            "precio_envio"             => $value['precio_envio']
                        ),
                        "metodo_pago" => array(
                            "address_comprador" => $data["metodo_pago"]["address_comprador_sd"]
                        ),
                        "fecha" => $fecha,
                        "id_carrito" => $value['id'],

                        "data_diferido" => array(
                            'id'                 => null,
                            'uid'                => $cuentaPote['uid'],
                            'empresa'            => $cuentaPote['empresa'],
                            'moneda'             => "Nasbigold",
                            'all'                => false,

                            'precio'             => $data["metodo_pago"]["sd"],
                            'precio_momento_usd' => ($value["precio"] / $value["precio_usd"]),

                            'precio_en_cripto'   => $data["metodo_pago"]["sd"] + $fleteAux,

                            'address'            => $cuentaPote['address_Nasbigold'],
                            'id_transaccion'     => $value['id'],
                            'tipo_transaccion'   => $value['tipo'],
                            'tipo'               => 'diferido',
                            'accion'             => 'push',
                            'descripcion'        => addslashes('Venta de producto '.$value['titulo']),
                            'fecha'              => $fecha,
                            'id_producto'        => $value['id_producto']
                        )
                    );
                    $pagarConCriptosArr[] = $schemaPagarConCriptosArr;


                    $schemaReporteFacturacion = Array(
                        "costo_producto"           => ($totalPagarFiat + $precio_segundo_pago + $precio_cupon),
                        "flete"                    => $flete,

                        "recaudo_pasarela"         => $totalPagarFiat,
                        
                        "segundo_metodo_pago"      => $moneda_segundo_pago,
                        "monto_segundo_metodo_pago"=> $precio_segundo_pago,

                        "serial_bono"              => $serial_cupon,
                        
                        "metodoPago"               => $pago_digital['METODO_PAGO_USADO_ID'],
                        "porcentaje_comision_nasbi"=> $value['comisiones_categoria_pt'],

                        "tipo_usuario_comprador"   => ($value['empresa']          == 0? "NATURAL": "JURIDICA"),
                        "tipo_usuario_vendedor"    => ($value['empresa_producto'] == 0? "NATURAL": "JURIDICA"),

                        "descripcion_pago"         => "Compra " . $value['titulo']
                    );
                    parent::cerrar();
                    $pago_digital_calculos_pasarelas = parent::calcularMontosPagoDigital($schemaReporteFacturacion);
                    parent::conectar();
                    $pago_digital_calculos_pasarelas_id = 0;
                    if( $pago_digital_calculos_pasarelas['status'] == 'success'){
                        $pago_digital_calculos_pasarelas_id = $pago_digital_calculos_pasarelas['data']['id_insercion'];
                    }

                    parent::addLog("-----+> [ schemaReporteFacturacion / Combinado ]: " . json_encode([ "SEND" => $schemaReporteFacturacion, "REVICED" => $pago_digital_calculos_pasarelas]));

                    $productos[] = "(
                        '$value[id]',
                        '$value[id_producto]',
                        '$value[uid_vendedor]',
                        '$value[uid]',
                        '$value[cantidad]',
                        '0',
                        '$value[moneda]',
                        '$totalPagarFiat',
                        '$precioUSDRestante',
                        '$carrito[precio_moneda_actual_usd]',
                        '$value[tipo]',
                        '$estado_transaccion',
                        '0',
                        '2',
                        '$value[empresa_producto]',
                        '$value[empresa]',
                        '$value[titulo]',
                        '$value[refer]',
                        '$value[exposicion_pt]',
                        '$value[categoria_pt]',
                        '$value[subcategoria_pt]',
                        '$value[comisiones_categoria_pt]',
                        '$fecha',
                        '$fecha',
                        '$value[uid_redsocial]',
                        '$value[empresa_redsocial]',
                        $flete,
                        $precio_segundo_pago,
                        '$moneda_segundo_pago',
                        $pago_digital_id,
                        $pago_digital_calculos_pasarelas_id,
                        $id_carrito_lote_transaccion,
                        '$value[comisiones_categoria_pt]',
                        0,
                        0,
                        $value[porcentaje_tax_pt],
                        $precio_cupon,
                        $cupones_historial_id,
                        '$referido_compras'
                    )";

                    $array_de_precios_unidades_correo[$value["id_producto"]]["moneda"]       = $value["moneda"];  //correo
                    $array_de_precios_unidades_correo[$value["id_producto"]]["precio_local"] = $totalPagarFiat; 

                }
            }          
        }

        $arrayMontosPrueba['productos'] = $productos;
        


        $metodo_pago_transaccion = $data['metodo_pago']['id'];
        $data['descripcion_extra'] = addslashes($data['descripcion_extra']);        
        
        $insertarxtransaccion =
        "INSERT INTO productos_transaccion (
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
            exposicion_pt,
            categoria_pt,
            subcategoria_pt,
            comisiones_categoria_pt,
            fecha_creacion,
            fecha_actualizacion,
            uid_redsocial,
            empresa_redsocial,
            precio_envio,
            precio_segundo_pago,
            moneda_segundo_pago,
            pago_digital_id,
            pago_digital_calculos_pasarelas_id,
            id_carrito_lote_transaccion,
            refer_porcentaje_comision,
            refer_porcentaje_comision_idplan,
            refer_porcentaje_comision_plan,
            porcentaje_tax_pt,
            precio_cupon,
            cupones_historial_id,
            refer_comprador
        )
        VALUES " . implode(",", $productos);


        $sw_cantidad_pasada = false;
        $productos_rechazados_cantidad = array();

        
        if(isset($data['coloresXtallas']) && count($data['coloresXtallas']) > 0){
            foreach($data['coloresXtallas'] as $producto){
                if(isset($producto['variaciones']) && count($producto['variaciones']) > 0){
                    foreach($producto['variaciones'] as $pair){

                        $selectxpar = parent::consultaTodo("SELECT * FROM detalle_producto_colores_tallas WHERE id = '$pair[id_pair]'");


                        if( count($selectxpar) > 0 ){
                            $selectxpar = $selectxpar[0];

                            $selectxpar['cantidad']             = intval( $selectxpar['cantidad'] );
                            $pair['cantidad_ingresada_carrito'] = intval( $pair['cantidad_ingresada_carrito'] );

                            $new_cantidad = $selectxpar['cantidad'] - $pair['cantidad_ingresada_carrito'];

                            if( $new_cantidad >= 0 ){

                                parent::query("UPDATE detalle_producto_colores_tallas SET cantidad = $new_cantidad WHERE id = '$pair[id_pair]';");

                            }else{
                                return array(
                                    'status'  => 'stockError',
                                    'message' => 'Alguno de sus productos supera la cantidad disponible, la compra fue cancelada',
                                    'data'    => array(
                                        'selectxpar'   => $selectxpar,
                                        'pair'         => $pair,
                                        'new_cantidad' => $new_cantidad,
                                        'condicional'  => ($new_cantidad < 0)
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }


        $transaccionquery = parent::queryRegistro($insertarxtransaccion);
        // var_dump($insertarxtransaccion);

        if($transaccionquery){
            $textoSend = "";
            foreach ($pagarConCriptosArr as $key => $value) {
                $this->pagarConCriptoNew(
                    $value["carrito"],
                    $value["metodo_pago"],
                    $value["fecha"],
                    $value["id_carrito"],
                    $precios,
                    $value["data_diferido"]
                );
            }
            $insertPendientes = 
                "INSERT INTO saldo_pendiente_pagar(uid, id_carrito, monto, moneda_local, saldo_pendiente, fecha_creado, fecha_pagado, categoria, porcentaje_pagar, id_producto) VALUES ";
            
            $pendientes = array();

            foreach ($montoPendienteCategoriaVendedor as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    foreach ($value2 as $key3 => $value3) {
                        $pendientes[] = "($key, '$value3[id_carrito]', $value3[monto], '$value3[moneda]', 0, current_timestamp, current_timestamp, $key2, 0, $key3)";
                    }
                }
            }

            parent::queryRegistro($insertPendientes." ".implode(",", $pendientes));

            foreach ($id_carrito as $key => $value) {
                
                $insertarTimeline1 = array(
                    'id'     => $value,
                    'estado' => 1,
                    'fecha'  => $fecha,
                    'tipo'   => 1
                );
                
                $insertarTimeline2 = array(
                    'id'     => $value,
                    'estado' => 1,
                    'fecha'  => $fecha,
                    'tipo'   => 2
                );

                $this->insertarTimeline($insertarTimeline1);
                $this->insertarTimeline($insertarTimeline2);
                $ESTADO_TIMELINE_AUX = 6;
                if ( $pago_digital != null ) {
                    
                    if ( $pago_digital['METODO_PAGO_USADO_ID'] == 3 || $pago_digital['METODO_PAGO_USADO_ID'] == 4 || $pago_digital['METODO_PAGO_USADO_ID'] == 5 ) {
                        $ESTADO_TIMELINE_AUX = 3;

                    }
                }
                $insertarTimeline1 = array(
                    'id'     => $value,
                    'estado' => $ESTADO_TIMELINE_AUX,
                    'fecha'  => $fecha,
                    'tipo'   => 1
                );
                
                $insertarTimeline2 = array(
                    'id'     => $value,
                    'estado' => $ESTADO_TIMELINE_AUX,
                    'fecha'  => $fecha,
                    'tipo'   => 2
                );

                $this->insertarTimeline($insertarTimeline1);
                $this->insertarTimeline($insertarTimeline2);
                
                $consultar = parent::consultaTodo("SELECT * FROM productos_transaccion WHERE id_carrito = $value;");

                for ($i=0;$i<count($consultar);$i++) {

                    if(!isset($data["metodo_pago"]["address_comprador_bd"])){
                        $data["metodo_pago"]["address_comprador_bd"] = "";
                    }
                    if(!isset($data["metodo_pago"]["address_comprador_sd"])){
                        $data["metodo_pago"]["address_comprador_sd"] = "";
                    }
                    $this->insertarMetodoPago(
                        array(
                            "moneda"=>$consultar[$i]["moneda"],
                            "precio"=>$consultar[$i]["precio"],
                            "cantidad"=>$consultar[$i]["cantidad"],
                            "tipo"=>$consultar[$i]["tipo"]
                        ),
                        array(
                            "id"=>$consultar[$i]["id_metodo_pago"],
                            "address_comprador"=>(
                                $consultar[$i]["moneda"]=="Nasbiblue" ? 
                                    $data["metodo_pago"]["address_comprador_bd"] :
                                    $data["metodo_pago"]["address_comprador_sd"]
                            )
                        ),
                        array(
                            "data"=>$consultar[$i]["id"]
                        ),
                        array(
                            "id_transaccion"=>$consultar[$i]["id"],
                            "fecha"=>$consultar[$i]["fecha_creacion"]
                        )
                    );

                    $model_productos_transaccion                         = $consultar[$i];
                    $model_productos_transaccion['cupones_historial_id'] = intval($model_productos_transaccion['cupones_historial_id']);
                    if( $model_productos_transaccion['cupones_historial_id'] > 0 ){

                        $selectxhistorialxcupon= "SELECT * FROM buyinbig.cupones_historial WHERE id = '$model_productos_transaccion[cupones_historial_id]';";
                        $schema_cupones_historial = parent::consultaTodo($selectxhistorialxcupon);
                        
                        $selectxcupon= "SELECT * FROM buyinbig.cupones WHERE codigo = '$model_productos_transaccion[cupones_historial_id]';";
                        $schema_cupones = parent::consultaTodo($selectxhistorialxcupon);

                        if( COUNT($schema_cupones_historial) > 0 ){
                            $schema_cupones_historial = $schema_cupones_historial[0];

                            parent::addLog("---+> 1. [ CUPON ]: " . json_encode($model_productos_transaccion));
                            parent::addLog("---+> 2. [ CUPON ]: " . json_encode($schema_cupones_historial));

                            $updateCupon = "UPDATE buyinbig.cupones SET estado = 0 WHERE codigo = '$schema_cupones_historial[cupon_tipo_id]';";

                            parent::addLog("---+> 2.1 [ CUPON ]: " . $updateCupon);

                            $rowAffectUpdateCupon = parent::query($updateCupon);

                            parent::addLog("---+> 3. [ CUPON ]: " . $rowAffectUpdateCupon);

                            if( $rowAffectUpdateCupon > 0 ){
                                $updateCupon = "UPDATE buyinbig.cupones_historial SET estado = 2 WHERE id = $schema_cupones_historial[id];";
                                parent::addLog("---+> 3.1 [ CUPON ]: " . $updateCupon);

                                $rowAffectUpdateCuponHistorial= parent::query($updateCupon);
                                parent::addLog("---+> 4. [ CUPON ]: " . $rowAffectUpdateCuponHistorial);
                            }
                        }


                    }
                }
            }

            $adicional = [
                'id_transaccion' => $transaccionquery,
                'fecha'          => $fecha
            ];
            
            $id_envio = null;
            $id_ruta = null;

            if(isset($data['shippo'])){
                $id_envio = $data['shippo']['id_envio'];
                $id_ruta  = $data['shippo']['id_ruta'];
            }

            $notificacion = new Notificaciones();
            $insertarEnvio = array();

            parent::addLog("##################### INICIO #####################");
            parent::addLog("-----+> [1. Pagar carrito restar unidades stock]: " . json_encode($carrito));

            foreach ($carrito["productos_array"] as $key => $value) {

                parent::addLog("-----+> [2. Pagar carrito restar unidades stock]: " . json_encode($value));
                $updatexstock =
                    "UPDATE productos SET cantidad_vendidas = cantidad_vendidas + $value[cantidad]  WHERE id = $value[id_producto]";

                parent::addLog("-----+> [3. Pagar carrito restar unidades stock]: " . $updatexstock);

                parent::query($updatexstock);  

                $this->insertarEnvio([
                    'id_carrito'             => $value["id"],
                    'tipo_envio'             => $value['tipo_envio'],
                    'id_prodcuto_envio'      => $value['id_prodcuto_envio_shippo'],
                    'id_envio'               => $id_envio,
                    'id_ruta'                => $id_ruta,
                    'id_transaccion'         => $value["id"],
                    'id_direccion_vendedor'  => $value['id_direccion_vendedor'], 
                    'id_direccion_comprador' => $data['direccion']['id'],
                    'fecha'                  => $fecha
                ]);

                //vendedor
                $notificacion->insertarNotificacion([
                    'uid' => $value['uid_vendedor'],
                    'empresa' => $value['empresa_producto'],
                    'text' => 'Inició proceso de venta de tu producto '.$value['titulo'].', revisa tu listado de ventas',
                    'es' => 'Inició proceso de venta de tu producto '.$value['titulo'].', revisa tu listado de ventas',
                    'en' => 'Start the process of buying and selling your product, check your sales list',
                    'keyjson' => '',
                    'url' => 'mis-cuentas.php?tab=sidenav_ventas'
                ]);
                //comprador
                $notificacion->insertarNotificacion([
                    'uid' => $value['uid'],
                    'empresa' => $value['empresa'],
                    'text' => 'Has comenzado el proceso de compra del producto '.$value['titulo'],
                    'es' => 'Has comenzado el proceso de compra del producto '.$value['titulo'],
                    'en' => 'You have started the process of purchasing the product '.$value['titulo'],
                    'keyjson' => '',
                    'url' => 'mis-cuentas.php?tab=sidenav_compras'
                ]);
            }
            parent::addLog( "----+> previa email: " . json_encode(Array(
                "id_carrito_lote_transaccion" => $id_carrito_lote_transaccion,
                "productos_array"             => json_encode($carrito["productos_array"]),
                "id_carrito"                  => json_encode($id_carrito)
            )));
            $this->envio_correo_confirmar_compra(Array(
                "id_carrito_lote_transaccion" => $id_carrito_lote_transaccion,
                "uid_comprador"               => $carrito['uid'],
                "empresa_comprador"           => $carrito['empresa']
            ));
            if(isset($array_de_precios_unidades_correo)){
                $this->envio_correo_deproducts_comprado($id_carrito_lote_transaccion, $carrito["productos_array"], $data["carrito"], $array_de_precios_unidades_correo);

            }
            parent::addLog("##################### FIN #####################");
            
            $this->eliminarNew($carrito);

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

        //$selectxproducto = "SELECT p.* FROM productos p WHERE p.id = '$data[id_producto]' AND p.estado = '1'";
        $selectxproducto =
        "SELECT
            p.*,
            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium
        FROM productos p
        INNER JOIN categorias ctg ON(p.categoria = ctg.CategoryID)
        WHERE p.id = $data[id_producto] AND p.estado = 1;";

        $producto = parent::consultaTodo($selectxproducto);
        parent::cerrar();
        if(count($producto) <= 0) return array('status' => 'fail', 'message'=> 'no producto', 'data' => null);


        parent::conectar();
        $selectxproductoxtransaccion = "
            SELECT SUM(pt.cantidad) AS cantidad
            FROM buyinbig.productos_transaccion pt 
            WHERE pt.id_producto = '$data[id_producto]'";

        $productoTransaccion = parent::consultaTodo($selectxproductoxtransaccion);
        parent::cerrar();

        $producto[0]['cantidad']            = intval( $producto[0]['cantidad'] );
        $productoTransaccion[0]['cantidad'] = intval( $productoTransaccion[0]['cantidad'] );
        $producto[0]['cantidad']           += $productoTransaccion[0]['cantidad'];

        parent::addLog("----+> CARRITO / AGREGAR AL CARRITO PRODUCTO.PHP: " . json_encode($producto[0]));

        if ( $productoTransaccion[0]['cantidad'] == null ) {
            if ( $data['cantidad'] <= $producto[0]['cantidad'] ) {
                return array(
                    'status' => 'success',
                    'message'=> 'producto',
                    'data' => $producto[0],
                    'dataRecibe' => $data,
                    'cantidad_creadas' => intval( $producto[0]['cantidad'] ),
                    'cantidad_vendida' => null,
                    'cantidad_solicitada' => intval( $data['cantidad'] )
                );
            }else{
                return array(
                    'status' => 'errorStock',
                    'message'=> 'producto - v1',
                    'data' => $producto[0],
                    'dataRecibe' => $data,
                    'cantidad_creadas' => intval( $producto[0]['cantidad'] ),
                    'cantidad_vendida' => null,
                    'cantidad_solicitada' => intval( $data['cantidad'] )
                );
            }
        }else{
            if ( $data['cantidad'] <= ($producto[0]['cantidad'] - $productoTransaccion[0]['cantidad']) ) {
                return array(
                    'status' => 'success',
                    'message'=> 'producto',
                    'data' => $producto[0],
                    'cantidad_solicitada' => intval( $data['cantidad'] ),
                    'cantidad_creadas' => intval( $producto[0]['cantidad'] ),
                    'cantidad_vendida' => intval( $producto[0]['cantidad'] ),
                    'cantidad_disponible' => intval( ($producto[0]['cantidad'] - $productoTransaccion[0]['cantidad']) ),
                    'cantidad_solicitada' => intval( $data['cantidad'] )
                );
            }else{
                return array(
                    'status' => 'errorStock',
                    'message'=> 'producto - v2',
                    'data' => $producto[0],
                    'cantidad_solicitada' => intval($data['cantidad']),
                    'cantidad_creadas' => intval( $producto[0]['cantidad'] ),
                    'cantidad_vendida' => intval( $productoTransaccion[0]['cantidad'] ),
                    'cantidad_disponible' => intval( ($producto[0]['cantidad'] - $productoTransaccion[0]['cantidad']) ),
                    'cantidad_solicitada' => intval( $data['cantidad'] )
                );
            }
        }
    }


    /*
        tomar en cuenta----

        productos_transacciones

        id,                                 se queda 
        estado,                             se queda
        id_carrito,                         se queda
        id_producto,                        se queda
        uid_vendedor,                       se queda
        uid_comprador,                      se queda
        cantidad,                           se queda
        boolcripto,                         se queda
        moneda,                             moneda => moneda_pago_principal
        precio,                             precio //es el total de la compra (producto * cantidad + flete)
        precio_envio,                       se queda //es el flete
        precio_usd,                         el costo en dolares del producto, creo que ya no va...
        precio_moneda_actual_usd,           USD a ML
        tipo,                               //1. compra --- 2. subasta
        contador,                           //proceso de devolucion, ira en 0 por ahora
        id_metodo_pago,                     // 
        empresa,                            // este va en 1 si es empresa 0 si no lo es // es del vendedor
        empresa_comprador,                  // este va en 1 si es empresa 0 si no lo es // es del comprador
        descripcion_extra,                  //anexo... normal
        refer,                              //quien refirio el producto
        refer_porcentaje_comision,          //quien refirio el producto
        refer_porcentaje_comision_idplan,   //quien refirio el producto
        refer_porcentaje_comision_plan,     //quien refirio el producto
        uid_redsocial,                      //para saber quien compartio el producto por la red social
        empresa_redsocial,                  //para saber quien compartio el producto por la red social
        fecha_creacion,                     //cuando se creo el producto
        fecha_actualizacion,                //cuando se actualizo el producto
        contador_devolucion_envio,          //proceso de devolucion, ira en 0 por ahora
        estado_pago_transaccion,            //pendiente, completado, cancelado
        esta_liquidado                      // booleano, si ya se los pagamos o no

        // campos adicionales a agregar
        precio_segundo_pago                 //es el total de la compra precio - flete - (producto * cantidad) en BD o SD si se agrega
        moneda_segundo_pago                 // SD o BD

    */


    function carritoPagar(Array $carrito, Bool $bcompra = true)
    {
        $request = null;
        parent::conectar();
        $selectxcarritoxcompra = 
        "SELECT c.*,
            p.cantidad AS cantidadProducto,
            p.precio_usd,
            p.precio AS precio_local,
            p.moneda_local,
            p.oferta,
            p.porcentaje_oferta,
            p.titulo,
            p.foto_portada,
            p.estado AS estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto,
            p.envio AS tipo_envio,
            p.producto,
            pe.id AS id_prodcuto_envio,
            pe.id_shippo AS id_prodcuto_envio_shippo,
            d.id AS id_direccion_vendedor,
            d.id_shippo AS id_direccion_vendedor_shippo,

            p.exposicion,
            p.porcentaje_tax,
            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium,

            IF( p.empresa = 0,
                u.usuarios_estados_teindas_oficiales_id,
                e.usuarios_estados_teindas_oficiales_id
            ) AS tienda_oficial

        from carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id

        INNER JOIN categorias ctg ON ctg.CategoryID = p.categoria

        LEFT JOIN buyinbig.empresas e ON(p.uid = e.id)
        LEFT JOIN peer2win.usuarios u ON(p.uid = u.id)

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

    function carritoPagarNew(Array $carrito, Bool $bcompra = true)
    {
        $request = null;
        parent::conectar();
        $selectxcarritoxcompra = "
        SELECT
            p.precio,
            c.*,
            p.cantidad AS cantidadProducto,
            p.precio_usd,
            p.precio AS precio_local,
            p.moneda_local,
            p.oferta,
            p.porcentaje_oferta,
            p.titulo,
            p.foto_portada,
            p.estado AS estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto,
            p.envio AS tipo_envio,
            p.producto,
            p.categoria,
            pe.id AS id_prodcuto_envio,
            pe.id_shippo AS id_prodcuto_envio_shippo,
            d.id AS id_direccion_vendedor,
            d.id_shippo AS id_direccion_vendedor_shippo,

            p.exposicion,
            p.porcentaje_tax,

            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium,

            ctg.codigo_producto_siigo_clasica,
            ctg.codigo_producto_siigo_premium,
            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = ctg.codigo_producto_siigo_clasica
            ) AS codigo_producto_siigo_clasica_descripcion,

            (
                SELECT nombre_producto FROM buyinbig.siigo_productos WHERE codigo_producto_siigo = ctg.codigo_producto_siigo_premium
            ) AS codigo_producto_siigo_premium_descripcion,

            IF( p.empresa = 0,
                u.usuarios_estados_teindas_oficiales_id,
                e.usuarios_estados_teindas_oficiales_id
            ) AS tienda_oficial


        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id
        
        INNER JOIN categorias ctg ON(p.categoria = ctg.CategoryID)

        LEFT JOIN buyinbig.empresas e ON(p.uid = e.id)
        LEFT JOIN peer2win.usuarios u ON(p.uid = u.id)

        WHERE c.uid = '$carrito[uid]' AND c.empresa = '$carrito[empresa]' 
        AND p.estado = 1 AND c.estado = 1 
        AND c.cantidad > 0 AND p.tipoSubasta = 0 
        GROUP BY id_producto;";

        // var_dump($selectxcarritoxcompra);
        $carritocompra = parent::consultaTodo($selectxcarritoxcompra);

        // Aqui debemos buscar los carritos que fuerón ganados en subastas.

        $selectxcarritoxcompraxsubasta = "
        SELECT
            (
                SELECT pspTemp.monto
                FROM buyinbig.productos pTemp
                INNER JOIN productos_subastas psTemp ON pTemp.id = psTemp.id_producto
                INNER JOIN productos_subastas_pujas pspTemp ON psTemp.id = pspTemp.id_subasta
                WHERE pTemp.id = p.id AND pspTemp.id_subasta = psTemp.id  ORDER BY pspTemp.id DESC LIMIT 1
            ) AS precio,
            c.*,
            p.cantidad AS cantidadProducto,
            (
                SELECT pspTemp.monto_usd
                FROM buyinbig.productos pTemp
                INNER JOIN productos_subastas psTemp ON pTemp.id = psTemp.id_producto
                INNER JOIN productos_subastas_pujas pspTemp ON psTemp.id = pspTemp.id_subasta
                WHERE pTemp.id = p.id AND pspTemp.id_subasta = psTemp.id  ORDER BY pspTemp.id DESC LIMIT 1
            ) AS precio_usd,
            (
                SELECT pspTemp.monto
                FROM buyinbig.productos pTemp
                INNER JOIN productos_subastas psTemp ON pTemp.id = psTemp.id_producto
                INNER JOIN productos_subastas_pujas pspTemp ON psTemp.id = pspTemp.id_subasta
                WHERE pTemp.id = p.id AND pspTemp.id_subasta = psTemp.id  ORDER BY pspTemp.id DESC LIMIT 1
            ) AS precio_local,
            (SELECT moneda FROM buyinbig.productos_subastas WHERE id_producto = p.id) AS monedav2,
            (SELECT id FROM buyinbig.productos_subastas WHERE id_producto = p.id) AS id_subasta,
            p.moneda_local,
            p.oferta,
            p.porcentaje_oferta,
            p.titulo,
            p.foto_portada,
            p.estado AS estado_producto,
            p.uid AS uid_vendedor,
            p.empresa AS empresa_producto,
            p.envio AS tipo_envio,
            p.producto,
            p.categoria,
            pe.id AS id_prodcuto_envio,
            pe.id_shippo AS id_prodcuto_envio_shippo,
            d.id AS id_direccion_vendedor,
            d.id_shippo AS id_direccion_vendedor_shippo,

            p.exposicion,
            p.porcentaje_tax,
            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium,

            IF( p.empresa = 0,
                u.usuarios_estados_teindas_oficiales_id,
                e.usuarios_estados_teindas_oficiales_id
            ) AS tienda_oficial

        FROM carrito c 
        INNER JOIN productos p ON c.id_producto = p.id
        INNER JOIN productos_envio pe ON p.id = pe.id_producto AND pe.estado = 1
        INNER JOIN direcciones d ON p.id_direccion = d.id

        INNER JOIN categorias ctg ON(p.categoria = ctg.CategoryID)

        LEFT JOIN buyinbig.empresas e ON(p.uid = e.id)
        LEFT JOIN peer2win.usuarios u ON(p.uid = u.id)

        WHERE c.uid = '$carrito[uid]' AND c.empresa = '$carrito[empresa]' 
        AND p.estado = 1 AND c.estado = 1
        AND c.cantidad > 0 AND p.tipoSubasta > 0 
        GROUP BY id_producto;";

        $carritocomprasubasta = parent::consultaTodo($selectxcarritoxcompraxsubasta);

        parent::cerrar();

        if(count($carritocompra) > 0){

            
            
            if(count($carritocomprasubasta) > 0){
                $carrito["productos_array"] = array_merge( $carritocompra , $carritocomprasubasta);

            }else{
                $carrito["productos_array"] = $carritocompra;

            }
            $request = array('status' => 'success', 'message'=> 'carrito usuario no logeado', 'data'=> $carrito);
        }else{
            if(count($carritocomprasubasta) > 0){
                $carrito["productos_array"] = $carritocomprasubasta;
                $request = array('status' => 'success', 'message'=> 'carrito usuario no logeado', 'data'=> $carrito);

            }else{
                $request = array('status' => 'fail', 'message'=> 'no tiene productos en el carrito no logeado', 'cantidad'=> 0, 'data' => null, 'subresult' => $carritocomprasubasta, "mensaje2" => "test");

            }
        }

        return $request;
    }

    function realizarCompra(Array $carrito, Array $metodo_pago, Int $fecha)
    {
        $request = array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if ($metodo_pago['desc']==1) {
             $this->pagarConCripto(array("uid"=>$carrito['uid'] , "empresa"=>$carrito['empresa'], "moneda"=>"Nasbiblue", "precio"=>$metodo_pago['bd'], "precio_moneda_actual_usd"=>$metodo_pago['bd_precio_usd'], "tipo"=>$carrito['tipo'], "titulo"=>$carrito['tipo']), array("address_comprador"=>$metodo_pago["address_comprador_bd"]), $fecha);
        }
        if ($metodo_pago['id'] == 1 || $metodo_pago['id'] == 3) {
            $request = $this->pagarConCripto(array("uid"=>$carrito['uid'] , "empresa"=>$carrito['empresa'], "moneda"=>"Nasbigold", "precio"=>$metodo_pago['sd_monto_usd'], "precio_moneda_actual_usd"=>$metodo_pago['sd_precio_usd'], "tipo"=>$carrito['tipo'], "titulo"=>$carrito['tipo']), array("address_comprador"=>$metodo_pago["address_comprador_sd"]), $fecha);
        }

        if ($metodo_pago['id'] == 2 || $metodo_pago['id'] == 3) {
            $request = array('status' => 'success', 'message'=> 'data no foto', 'data' => null);
        }

        /*if($metodo_pago['id'] == 1) $request = $this->pagarConCripto($carrito, $metodo_pago, $fecha);
        if($metodo_pago['id'] == 2) */
        // completar los otros metodos de pagos
        return $request;
    }

    function pagarConCripto(Array $carrito, Array $metodo_pago, Int $fecha)
    {
        if(!isset($metodo_pago['address_comprador'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null, "msj"=>2);
        if($carrito['tipo'] == 2) return array('status' => 'success', 'message'=> 'producto subasta no aplica bloqueo', 'data' => null);
        $nasbifunciones = new Nasbifunciones();
        
        $bloqueo = $nasbifunciones->insertarBloqueadoDiferido([
            'id' => null,
            'uid'=> $carrito['uid'],
            'empresa' => $carrito['empresa'],
            'moneda'=> $carrito['moneda'],
            'all' => false,

            'precio' => $carrito['precio'],
            'precio_momento_usd'=>$carrito['precio_moneda_actual_usd'],
            'precio_en_cripto' => ($carrito['precio'] / $carrito['precio_moneda_actual_usd']), // Cuanto cuesta el articulo en cripto.

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
            'text' => 'Se ha bloqueado el monto '.$this->maskNumber($carrito['precio'], 2).' '.$carrito['moneda'].' por la compra del producto '.$carrito['titulo'],
            'es' => 'Se ha bloqueado el monto '.$this->maskNumber($carrito['precio'], 2).' '.$carrito['moneda'].' por la compra del producto '.$carrito['titulo'],
            'en' => 'You have started the process of purchasing the product '.$carrito['titulo'],
            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_compras'
        ]);
        unset($notificacion);
        return $bloqueo;
    }

    function pagarConCriptoNew(Array $carrito, Array $metodo_pago, Int $fecha, $id_transaccion, Array $preciosCripto, Array $data_diferido)
    {
        if(!isset($metodo_pago['address_comprador'])) return array('status' => 'fail', 'message'=> 'no data array pagar cripto new', 'data' => $metodo_pago);
        if($carrito['tipo'] == 2) return array('status' => 'success', 'message'=> 'producto subasta no aplica bloqueo', 'data' => null);
        $nasbifunciones = new Nasbifunciones();

        if ( !isset($carrito['precio_envio']) ) {
            $carrito['precio_envio'] = 0;
        }

        $dataBloqueo = array(
            'id'                 => null,
            'uid'                => $carrito['uid'],
            'empresa'            => $carrito['empresa'],
            'moneda'             => $carrito['moneda'],
            'all'                => false,
            
            'precio'             => $carrito['precio'],
            'precio_momento_usd' => $carrito['precio_moneda_actual_usd'],
            'precio_en_cripto'   => $carrito['precio_moneda_actual_usd'] / $preciosCripto[ $carrito['moneda'] ],

            'address'            => $metodo_pago['address_comprador'],
            'id_transaccion'     => $id_transaccion,
            'tipo_transaccion'   => $carrito['tipo'], //antes decia 1
            'tipo'               => 'bloqueado',
            'accion'             => 'push',
            'descripcion'        => addslashes($carrito['titulo']),
            'fecha'              => $fecha,
            "id_producto"        => $carrito['id_producto']
        );
        $bloqueo = $nasbifunciones->insertarBloqueadoDiferido($dataBloqueo);

        if ( isset( $data_diferido ) && isset( $data_diferido['uid'] ) ) {
            $diferido = $nasbifunciones->insertarBloqueadoDiferido($data_diferido);
        }

        unset($nasbifunciones);

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $carrito['uid'],

            'empresa' => $carrito['empresa'],

            'text' => 'Se ha bloqueado el monto ' . $this->maskNumber($carrito['precio'], 2) .' '. $carrito['moneda'].' por la compra del producto ' . $carrito['titulo'],

            'es' => 'Se ha bloqueado el monto ' . $this->maskNumber($carrito['precio'], 2) .' ' . $carrito['moneda'].' por la compra del producto ' . $carrito['titulo'],

            'en' => 'You have started the process of purchasing the product ' . $carrito['titulo'],

            'keyjson' => '',
            'url' => 'mis-cuentas.php?tab=sidenav_compras'
        ]);

        if ( isset( $data_diferido ) && isset( $data_diferido['uid'] ) ) {
            $notificacion->insertarNotificacion([
                'uid' => $data_diferido['uid'],

                'empresa' => $data_diferido['empresa'],

                'text' => 'Se ha agregado a tus diferidos el monto de '. $this->maskNumber($data_diferido['precio'], 2) .' '. $carrito['moneda'].' por la transaction REF: #' . $id_transaccion,

                'es' => 'Se ha agregado a tus diferidos el monto de '. $this->maskNumber($data_diferido['precio'], 2) .' '. $carrito['moneda'].' por la transaction REF: #' . $id_transaccion,

                'en' => 'Se ha agregado a tus diferidos el monto de '. $this->maskNumber($data_diferido['precio'], 2) .' '. $carrito['moneda'].' por la transaction REF: #' . $id_transaccion,

                'keyjson' => '',

                'url' => 'e-wallet.php'
            ]);
        }


        unset($notificacion);
        return $bloqueo;
    }

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
        $result = parent::queryRegistro($insertxtimeline);
        
        
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
            //$updatexbloqueadoxdiferido = "UPDATE nasbicoin_bloqueado_diferido SET id_transaccion = '$adicional[id_transaccion]' WHERE id = '$pago[data]'";
            //$updatebloqdif = parent::query($updatexbloqueadoxdiferido);
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

    function agregarCarritoPago($tipo, $id, $id_transaccion, $moneda, $id_metodo_pago, $monto, $cantidad, $addres_vendedor, $addres_comprador, $descripcion, $fecha) {
            if ($tipo == 1) {
                $updatexbloqueadoxdiferido = "UPDATE nasbicoin_bloqueado_diferido SET id_transaccion = '$id_transaccion' WHERE id = '$id'";
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
                '$id_transaccion',
                '$id_metodo_pago',
                '$moneda',
                '$monto',
                '$cantidad',
                '$addres_vendedor',
                '$addres_comprador',
                'null',
                '$descripcion',
                'null',
                '0',
                '$fecha',
                '$fecha'
            );";
            $insertquery = parent::query($insertxmetodoxpago);
            $this->pagarConCripto(array("uid"=>$carrito['uid'] , "empresa"=>$carrito['empresa'], "moneda"=>"Nasbiblue", "precio"=>$metodo_pago['bd'], "precio_moneda_actual_usd"=>$metodo_pago['bd_precio_usd'], "tipo"=>$carrito['tipo'], "titulo"=>$carrito['tipo']), array("address_comprador"=>$metodo_pago["address_comprador_bd"]), $fecha);
    }

    function insertarMetodoPagoDeprecado(Array $carrito, Array $metodo_pago, Array $pago, Array $adicional)
    {
        $addres_vendedor = null;
        $addres_comprador = null;
        $url = null;
        $descripcion = null;
        $tx_transaccion = null;
        if($metodo_pago['desc'] == 1 && $carrito['tipo'] == 1){
            $addres_comprador = $metodo_pago['address_comprador_bd'];
            $updatexbloqueadoxdiferido = "UPDATE nasbicoin_bloqueado_diferido SET id_transaccion = '$adicional[id_transaccion]' WHERE id = '$pago[data]'";
            $updatebloqdif = parent::query($updatexbloqueadoxdiferido);
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
                '1',
                'Nasbiblue',
                '$metodo_pago[bd]',
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
        }

        if(($metodo_pago['id'] == 1 || $metodo_pago['id'] == 3) && $carrito['tipo'] == 1) {
            $addres_comprador = $metodo_pago['address_comprador_sd'];
            $updatexbloqueadoxdiferido = "UPDATE nasbicoin_bloqueado_diferido SET id_transaccion = '$adicional[id_transaccion]' WHERE id = '$pago[data]'";
            $updatebloqdif = parent::query($updatexbloqueadoxdiferido);
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
                '1',
                'Nasbigold',
                '$metodo_pago[sd_monto_usd]',
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
        }

        if(($metodo_pago['id'] == 2 || $metodo_pago['id'] == 3) && $carrito['tipo'] == 1) {
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
                '1',
                'Nasbigold',
                '$metodo_pago[monto_fiat]',
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
        }
        
        
        // completar los otros metodos de pagos
    }

    function insertarEnvio(Array $data)
    {
        $insertxenvio = "INSERT INTO productos_transaccion_envio
        (
            id_carrito,
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
            '$data[id_carrito]',
            '$data[id_transaccion]',
            '$data[tipo_envio]',
            '$data[id_direccion_vendedor]',
            '$data[id_direccion_comprador]',
            '$data[id_prodcuto_envio]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        
        $insertenvio = parent::queryRegistro($insertxenvio);
        
        $this->saveShippo([
            'id_carrito' => $data['id_carrito'],
            'id_envio'   => $data['id_envio'],
            'id_ruta'    => $data['id_ruta'],
            'id'         => $insertenvio
        ]);
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
        // return $carrito;


        $proveedores_list = array(); // Aqui se guardan los ID de cada proveedor en pago digital.


        $nasbifuncionesTopeVentas = new Nasbifunciones();
        
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
            
            $car['precio_local_mask'] = $this->maskNumber(floatval($car['precio_local']), 2);




            $car['id_proveedor'] = null;
            $compuesto_id            = $car['uid_vendedor'] . '_' . $car['empresa_producto'];
            if ( isset( $proveedores_list[ $compuesto_id ] ) ) {
                $car['id_proveedor'] = $proveedores_list[ $compuesto_id ];
                
            }else{
                $selectxproveedorexpd = 
                "SELECT * FROM buyinbig.pago_digital_proveedores WHERE UID = $car[uid_vendedor] AND EMPRESA = $car[empresa_producto];";

                // var_dump($selectxproveedorexpd);

                parent::conectar();
                $selectproveedorepd = parent::consultaTodo( $selectxproveedorexpd );
                parent::cerrar();

                if ( count( $selectproveedorepd ) > 0 ) {
                    $selectproveedorepd                = $selectproveedorepd[0];
                    $proveedores_list[ $compuesto_id ] = $selectproveedorepd['PROVEEDOR_ID'];
                    $car['id_proveedor']               = $proveedores_list[ $compuesto_id ];
                }
            }

            // inicio nuevo codigo 
            parent::conectar();

            $selectxproductosxtransaccionxespecificacionxproducto = 
                "SELECT * FROM productos_transaccion_especificacion_producto WHERE id_carrito = '$car[id]' AND cantidad > 0 AND id_producto = '$car[id_producto]'";
            $resultPTEP = parent::consultaTodo($selectxproductosxtransaccionxespecificacionxproducto);
            parent::cerrar();

            $car['variaciones'] = [];
            if($resultPTEP && count($resultPTEP) > 0){

                foreach($resultPTEP as $itemPTEP){
                    $id_DPCT = $itemPTEP['id_detalle_producto_colores_tallas']; 
                    
                    $selectxvariaciones =
                    "SELECT 
                        dpct.id_producto,
                        dpct.id        AS id_pair,
                        pc.id          AS id_color,
                        pc.nombre_es   AS color_nombre_es,
                        pc.nombre_en   AS color_nombre_en,
                        pc.hexadecimal AS hexadecimal,
                        pt.id          AS id_tallas,
                        pt.nombre_es   AS talla_nombre_es,
                        pt.nombre_en   AS talla_nombre_en,
                        dpct.cantidad  AS cantidad,
                        dpct.sku       AS sku
                    FROM productos_colores pc
                    JOIN detalle_producto_colores_tallas dpct ON(pc.id = dpct.id_colores)
                    JOIN productos_tallas pt ON pt.id = dpct.id_tallas
                    WHERE dpct.id = '$id_DPCT';";


                    parent::conectar();
                    $confi_color_talla = parent::consultaTodo($selectxvariaciones);
                    parent::cerrar();

                    if($confi_color_talla && count($confi_color_talla) > 0){
                        array_push($car['variaciones'],array(
                            'color'                      => $confi_color_talla[0]['hexadecimal'],
                            'tallaES'                    => $confi_color_talla[0]['talla_nombre_es'],
                            'tallaEN'                    => $confi_color_talla[0]['talla_nombre_en'],
                            'id_pair'                    => $confi_color_talla[0]['id_pair'],
                            'cantidad_disponible'        => $confi_color_talla[0]['cantidad'],
                            'cantidad_ingresada_carrito' => $itemPTEP['cantidad'],
                            'especificaciones'           => $resultPTEP,
                            'detalle_producto'           => $confi_color_talla
                        ));
                    }
                }
            }
            // fin nuevo codigo 


            $car['oferta'] = floatval($car['oferta']);
            $car['porcentaje_oferta'] = floatval($car['porcentaje_oferta']);
            $car['empresa'] = floatval($car['empresa']);
            $car['empresa_producto'] = floatval($car['empresa_producto']);
            $car['tipo_envio'] = intval($car['tipo_envio']);
            $car['fecha_creacion'] = intval($car['fecha_creacion']);
            $car['fecha_actualizacion'] = intval($car['fecha_actualizacion']);
            $car['id_prodcuto_envio'] = intval($car['id_prodcuto_envio']);
            $car['id_direccion_vendedor'] = intval($car['id_direccion_vendedor']);

            $car['precio_descuento_usd'] = $car['precio_usd'];
            $car['precio_descuento_local'] = $car['precio_local'];

            if($car['oferta'] == 1){
                $car['precio_descuento_usd'] = $car['precio_usd'] * ($car['porcentaje_oferta']/100);
                $car['precio_descuento_usd'] = $car['precio_usd'] - $car['precio_descuento_usd'];
                $car['precio_descuento_local'] = $car['precio_local'] * ($car['porcentaje_oferta']/100);
                $car['precio_descuento_local'] = $car['precio_local'] - $car['precio_descuento_local'];

                $car['precio_descuento_local_mask'] = $this->maskNumber(floatval($car['precio_descuento_local']), 2);
            }else{
                $car['precio_descuento_local_mask'] = $this->maskNumber(floatval($car['precio_local']), 2);
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
                    $car['precio'] = floatval($this->truncNumber($car['precio'], 2));
                    $car['precio_mask'] = $this->maskNumber($car['precio'], 2);
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
                'empresa' => $car['empresa']
            );
            $nasbifunciones = new Nasbifunciones();
            $car['nodoWallet']= $nasbifunciones->walletNasbiUsuario( $dataPreWallet );
            
            // if($car['tipo'] == 2){

            //     $subasta = $this->subastaIdProducto($car);
            //     $subasta = $subasta['data'];

            //     $car['oferta'] = 0;
            //     $car['porcentaje_oferta'] = 0;

            //     $car['precio_descuento_usd'] = $subasta['puja_monto_usd'];
            //     $car['precio_descuento_local'] = $subasta['puja_monto'];
            //     $car['precio_descuento_local_mask'] = $this->maskNumber(floatval($car['precio_descuento_local']), 2);

            //     $car['cantidad'] = $subasta['cantidad'];

            //     $car['precio'] = $subasta['puja_monto'];
            //     $car['precio_mask'] = $this->maskNumber($subasta['puja_monto'], 2);

            //     $car['precio_usd'] = $subasta['puja_monto_usd'];
            //     $car['precio_usd_mask'] = $this->maskNumber($subasta['puja_monto_usd'], 2);
            // }

            if ( isset($car['porcentaje_tax']) ) {
                $car['porcentaje_tax'] = floatval( $car['porcentaje_tax'] );
            }else{
                $car['porcentaje_tax'] = 0;
            }

            $resultTopeVentas = $nasbifuncionesTopeVentas->ventasGratuitasRealizadas([
                'uid'     => $car['uid_vendedor'],
                'empresa' => $car['empresa']
            ]);

            $car['exposicion'] = intval( $car['exposicion'] );

            if( $resultTopeVentas['status'] == 'success' && $car['exposicion'] == 1 ){
                $car['exposicion'] = 3;
                $car['resultTopeVentas'] = $resultTopeVentas;
            }

            if ( isset($car['exposicion']) && isset($car['porcentaje_gratuita']) && isset($car['porcentaje_clasica']) && isset($car['porcentaje_premium']) ) {

                

                $car['porcentaje_gratuita'] = floatval($car['porcentaje_gratuita']);
                $car['porcentaje_clasica']  = floatval($car['porcentaje_clasica']);
                $car['porcentaje_premium']  = floatval($car['porcentaje_premium']);

                if ( $car['exposicion'] == 0 ) {
                    $car['exposicion_porcentaje_pago'] = floatval( $car['porcentaje_gratuita'] );

                }else if ( $car['exposicion'] == 1 ) {
                    $car['exposicion_porcentaje_pago'] = floatval( $car['porcentaje_gratuita'] );

                }else if ( $car['exposicion'] == 2 ) {
                    $car['exposicion_porcentaje_pago'] = floatval( $car['porcentaje_clasica'] );

                }else if ( $car['exposicion'] == 3 ) {
                    $car['exposicion_porcentaje_pago'] = floatval( $car['porcentaje_premium'] );
                    
                }else{
                    $car['exposicion_porcentaje_pago'] = floatval( $car['porcentaje_gratuita'] );

                }
            }else{
                $car['exposicion_porcentaje_pago'] = 0;
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
                $carritoTotales[$car['moneda']]['total_usd'] += ($car['precio_usd_total']);
                $carritoTotales[$car['moneda']]['total_mask_usd'] = $this->maskNumber($carritoTotales[$car['moneda']]['total_usd'], 2);
                
                $carritoTotales['cantidad'] = $carritoTotales['cantidad'] + $car['cantidad'];
                $carritoTotales['total_usd'] = $carritoTotales[$car['moneda']]['total_usd'];
                $carritoTotales['total_mask_usd'] = $this->maskNumber($carritoTotales['total_usd'], 2);
                $carritoTotales['nodoWallet'] = $nasbifunciones->walletNasbiUsuario( $dataPreWallet );


                
                $carritoTotales[$car['moneda']]['total'] = floatval($carritoTotales[$car['moneda']]['total'] + $car['precio']);
                $carritoTotales[$car['moneda']]['total_mask'] = $this->maskNumber($carritoTotales[$car['moneda']]['total'], 2);
                if( $car['moneda'] != $car['moneda_local']) $carritoTotales[$car['moneda']]['total_mask'] = $this->maskNumber($carritoTotales[$car['moneda']]['total'], 2);
                array_push($carritoTotales[$car['moneda']]['productos'], $car);
            }
        }

        unset($nasbifuncionesTopeVentas);

        unset($nodo);
        return array(
            'carrito'=> $carrito,
            'carritoTotales'=> $carritoTotales,
        );
    }

    function subastaIdProducto(Array $data)
    {
        parent::conectar();
        $selectxsubasta = 
        "SELECT

            ps.*,
            p.uid AS uid_producto,
            p.producto,
            p.marca,
            p.modelo,
            p.titulo,
            p.descripcion,
            p.empresa AS empresa_producto,
            psi.uid,
            psi.empresa,
            psi.estado AS estado_inscrito,
            psi.id AS id_inscrito,

            psp.monto AS puja_monto,
            psp.moneda_local_simbol AS puja_moneda_local_simbol,
            psp.monto_usd AS puja_monto_usd,
            psp.monto_cripto AS puja_monto_cripto

        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id
        INNER JOIN productos_subastas_pujas psp ON psp.id_subasta = ps.id
        INNER JOIN productos_subastas_inscritos psi ON  ps.id = psi.id_subasta
        WHERE ps.id_producto = '$data[id_producto]' AND psi.uid = '$data[uid]' AND ps.estado = '4' AND psp.id_subasta = ps.id
        ORDER BY psp.id DESC LIMIT 1;";
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
            $producto['precio_mask'] = $this->maskNumber($producto['precio'], 2);
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

    function mapEmpresa(Array $empresas, Bool $productos = false)
    {
        foreach ($empresas as $x => $empresa) {
            if ( $empresa['descripcion'] == "...") {
                $empresa['descripcion'] = "";
            }
            if ( $empresa['nombre_empresa'] == "...") {
                $empresa['nombre_empresa'] = $empresa['razon_social'];
            }
            if ( $empresa['cargo'] == "...") {
                $empresa['cargo'] = "";
            }
            if ( $empresa['razon_social'] == "...") {
                $empresa['razon_social'] = "";
            }
            if ( $empresa['tipo_empresa'] == "...") {
                $empresa['tipo_empresa'] = "0";
            }
            if ( $empresa['foto_docuemento_empresa'] == "...") {
                $empresa['foto_docuemento_empresa'] = "";
            }
            if ( $empresa['foto_documento_dueno'] == "...") {
                $empresa['foto_documento_dueno'] = "";
            }
            if ( $empresa['foto_logo_empresa'] == "...") {
                $empresa['foto_logo_empresa'] = "";
            }
            if ( $empresa['foto_portada_empresa'] == "...") {
                $empresa['foto_portada_empresa'] = "";
            }
            if ( $empresa['numero_documento_dueno'] == "...") {
                $empresa['numero_documento_dueno'] = "";
            }
            if ( $empresa['apellido_dueno'] == "...") {
                $empresa['apellido_dueno'] = "";
            }
            if ( $empresa['nombre_dueno'] == "...") {
                $empresa['nombre_dueno'] = "";
            }

            $empresa['uid'] = floatval($empresa['id']);
            $empresa['id'] = floatval($empresa['id']);
            $empresa['empresa'] = 1;
            $empresa['pais'] = floatval($empresa['pais']);
            $empresa['nit'] = floatval($empresa['nit']);
            $empresa['razon_social'] = $empresa['razon_social'];
            $empresa['tipo_documento_dueno'] = floatval($empresa['tipo_documento_dueno']);
            $empresa['estado'] = floatval($empresa['estado']);
            $empresa['fecha_creacion'] = floatval($empresa['fecha_creacion']);
            $empresa['fecha_actualizacion'] = floatval($empresa['fecha_actualizacion']);


            $empresa['nombreCompleto'] = $empresa['nombre_empresa'];

            $empresa['tipo_empresa'] = floatval($empresa['tipo_empresa']);
            $empresa['descripcion'] = $empresa['descripcion'];
            $empresa['cargo'] = $empresa['cargo'];
            $empresa['referido'] = $empresa['referido'];
            $empresa['caracteristica_principal_1'] = floatval($empresa['caracteristica_principal_1']);
            $empresa['caracteristica_principal_2'] = floatval($empresa['caracteristica_principal_2']);
            $empresa['caracteristica_principal_3'] = floatval($empresa['caracteristica_principal_3']);
            
            $empresa['status_solicitud_activar_subastas'] = floatval($empresa['status_solicitud_activar_subastas']);

            unset($empresa['clave']);
            
            $empresas[$x] = $empresa;
        }

        return $empresas;
    }

    function envio_correo_deproducts_comprado(Int $id_carrito_lote_transaccion = 0, Array $carrito, Array $data_wbs, Array $array_precios_unidades){

        parent::addLogJB(" [ envio_correo_deproducts_comprado ]: " . json_encode(Array("carrito" => $carrito,"data_wbs" => $data_wbs,"array_precios_unidades" => $array_precios_unidades, "id_carrito_lote_transaccion" => $id_carrito_lote_transaccion)));

        $id_comprador  = $data_wbs["uid"];
        $b_emp         = $data_wbs["empresa"];
        $precio_unidad = array(); 
 
        $data_comprador=  $this->datosUserGeneral3([
            'uid'     => $id_comprador,
            'empresa' => $b_emp
        ]);
        foreach ($carrito as $key => $value) {
            // if($value['tipo'] == 1 ){
                //no es un producto de subasta 
                $data_vendedor = $this->datosUserGeneral3([
                    'uid'     => $value["uid_vendedor"],
                    'empresa' => $value["empresa_producto"]
                ]);

                $data_producto = $this->get_product_por_id2([
                    'uid'     => $value["uid_vendedor"],
                    'id'      => $value["id_producto"],
                    'empresa' => $value["empresa_producto"
                ]]);

                $direccion_vendedor = $this->get_direccion_by_id([
                    'id'      => $value["id_direccion_vendedor"]
                ]);

                $precio_unidad["moneda"] = $array_precios_unidades[$value["id_producto"]]["moneda"];
                $precio_unidad["precio"] = $array_precios_unidades[$value["id_producto"]]["precio_local"];

                $selectxproductosxtransaccion =
                    "SELECT
                        *
                    FROM buyinbig.productos_transaccion
                    WHERE uid_comprador = '$value[uid]' AND empresa_comprador = '$value[empresa]' AND id_producto = '$value[id_producto]'
                    AND id_carrito_lote_transaccion = '$id_carrito_lote_transaccion';";

                parent::conectar();
                $productos_transaccion = parent::consultaTodo( $selectxproductosxtransaccion );
                parent::cerrar();
                
                if( COUNT( $productos_transaccion ) > 0 ){
                    $productos_transaccion = $productos_transaccion[ 0 ];

                    $htmlEmail_comienza_compra_datos = Array(
                        "data_comprador"        => $data_comprador["data"],
                        "data_vendedor"         => $data_vendedor["data"],
                        "data_producto"         => $data_producto[0],
                        "value"                 => $value,
                        "precio_unidad"         => $precio_unidad,
                        "direccion_vendedor"    => $direccion_vendedor[0],
                        "productos_transaccion" => $productos_transaccion
                    );
                    parent::addLogJB("---+> [ htmlEmail_comienza_compra ]: " . json_encode($htmlEmail_comienza_compra_datos));

                    $this->htmlEmail_comienza_compra(
                        $data_comprador["data"],
                        $data_vendedor["data"],
                        $data_producto[0],
                        $value,
                        $precio_unidad,
                        $direccion_vendedor[0],
                        $productos_transaccion
                    );
                }
            // }
        }
    }




    // INICIO: Correo confirmación de despacho.
    public function envio_correo_confirmar_compra(Array $data)
    {
        $productos_transaccion_item = 
            "SELECT
                *
            FROM buyinbig.productos_transaccion
            WHERE uid_comprador = '$data[uid_comprador]' AND empresa_comprador =  '$data[empresa_comprador]' AND id_carrito_lote_transaccion = '$data[id_carrito_lote_transaccion]';";

        $productos_transaccion_item = parent::consultaTodo($productos_transaccion_item);

        if( COUNT( $productos_transaccion_item ) > 0 ){

            foreach ($productos_transaccion_item as $key => $producto) {
                $data_producto = $this->get_product_por_id([
                    'uid'      => $producto["uid_vendedor"],
                    'id'       => $producto["id_producto"],
                    'empresa'  => $producto["empresa"]
                ]); 

                parent::addLog("----+> [ data_producto ]: " . json_encode($data_producto));

                $data_vendedor = $this->datosUserGeneral3([
                    'uid'      => $producto["uid_vendedor"],
                    'empresa'  => $producto['empresa']
                ]);

                parent::addLog("----+> [ data_vendedor ]: " . json_encode($data_vendedor));

                $data_comprador = $this->datosUserGeneral3([
                    'uid'      => $producto["uid_comprador"],
                    'empresa'  => $producto['empresa_comprador']
                ]);


                parent::addLog("----+> [ data_comprador ]: " . json_encode($data_comprador));

                parent::addLog("----+> envio_correo_confirmar_compra: " . json_encode(Array( $data_comprador["data"], $data_vendedor["data"], $data_producto, $producto)));

                $this->htmlEmail_confirmo_compra_to_despacho(
                    $data_comprador["data"],
                    $data_vendedor["data"],
                    $data_producto,
                    $producto
                );
            }
        }
    }

    public function htmlEmail_confirmo_compra_to_despacho(Array $data_comprador, Array $data_vendedor,Array $data_producto, Array $producto)
    {

        $htmlEmail_confirmo_compra_to_despacho = Array(
            "data_comprador" => $data_comprador,
            "data_vendedor" => $data_vendedor,
            "data_producto" => $data_producto,
            "producto" => $producto
        );
        parent::addLog("----+> [ htmlEmail_confirmo_compra_to_despacho ]: " . json_encode($htmlEmail_confirmo_compra_to_despacho));


       $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
       $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo5.html");

       if($producto["moneda"]=="Nasbigold" || $producto["moneda"]=="nasbigold"){
           $producto["moneda"]="Nasbichips"; 
       }else if($producto["moneda"]=="Nasbiblue" || $producto["moneda"]=="nasbiblue"){
           $producto["moneda"]="Bono(s) de descuento"; 
       }



       $html = str_replace("{{id_carrito}}", $producto['id_carrito'], $html);

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

    // FIN: Correo confirmación de despacho.


    function datosUserGeneral3( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }
    
    function get_product_por_id2( Array $data ){
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
                p.tiempo_estimado_envio_num,
                p.tiempo_estimado_envio_unidad,

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
            WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]'
            ORDER BY p.id DESC;";



        parent::conectar();
        $misProductos = parent::consultaTodo($select);
        parent::cerrar();
        return $misProductos;
    }

    public function htmlEmail_comienza_compra(Array $data_comprador, Array $data_vendedor,Array $data_producto,Array $value, Array $precio_unidad, Array $direccion_vendedor, Array $productos_transaccion )
    {

        $recibe_dts = Array(
            "data_comprador"        => $data_comprador,
            "data_vendedor"         => $data_vendedor,
            "data_producto"         => $data_producto,
            "value"                 => $value,
            "precio_unidad"         => $precio_unidad,
            "direccion_vendedor"    => $direccion_vendedor,
            "productos_transaccion" => $productos_transaccion
        );

        parent::addLogJB("----+> [ recibe_dts]: " . json_encode($recibe_dts));
           
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_comprador["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo2.html");

        if($precio_unidad["moneda"]=="Nasbigold" || $precio_unidad["moneda"]=="nasbigold"){
            $precio_unidad["moneda"]="Nasbichips"; 
        }else if($precio_unidad["moneda"]=="Nasbiblue" || $precio_unidad["moneda"]=="nasbiblue"){
            $precio_unidad["moneda"]="Bono(s) de descuento"; 
        }

        if($data_vendedor["foto"] == "" || intval( $data_vendedor["empresa"] )== 0){
            $data_vendedor["foto"] = $json->foto_por_defecto_user;    
        }
        

        $productos_transaccion['tipo']                = intval( $productos_transaccion['tipo'] );
        $productos_transaccion['id_metodo_pago']      = intval( $productos_transaccion['id_metodo_pago'] );
        $productos_transaccion['precio']              = floatval( $productos_transaccion['precio'] );
        $productos_transaccion['precio_comprador']    = floatval( $productos_transaccion['precio_comprador'] );
        $productos_transaccion['precio_segundo_pago'] = floatval( $productos_transaccion['precio_segundo_pago'] );
        $productos_transaccion['precio_cupon']        = floatval( $productos_transaccion['precio_cupon'] );

        $data_producto['tipo_envio_gratuito']         = intval( $data_producto['tipo_envio_gratuito'] );

        $metodo_pago_usado = "Nasbichips";
        if( $productos_transaccion['id_metodo_pago'] == 1 ){
            $metodo_pago_usado = "Nasbichips";

        }else if( $productos_transaccion['id_metodo_pago'] == 2 ){
            $metodo_pago_usado = "Moneda local";
        
        }else if( $productos_transaccion['id_metodo_pago'] == 3 ){
            $metodo_pago_usado = "Pago combinado";

        }else{

        }

        $precio_total = 0;
        if( $productos_transaccion['tipo'] == 1 ){
            $precio_total = $productos_transaccion['precio'] + $productos_transaccion['precio_segundo_pago'] + $productos_transaccion['precio_cupon'];
        }else{
            $precio_total = $productos_transaccion['precio_comprador'];
        }

        $tipo_envio = "";
        if( $data_producto['envio'] == 1 ){
            $tipo_envio = $json->trans118_1;

        }else if( $data_producto['envio'] == 2 ){
            $tipo_envio = $json->trans261;

        }else if( $data_producto['envio'] == 3 ){
            $tipo_envio = $json->trans118_3;

        }else{

        }
        if(!isset( $data_producto['tiempo_estimado_envio_num'] )){
            $data_producto['tiempo_estimado_envio_num'] = 0;

        }
        if(!isset( $data_producto['tiempo_estimado_envio_unidad'] )){
            $data_producto['tiempo_estimado_envio_unidad'] = 0;

        }

        $data_producto['tiempo_estimado_envio_num']    = intval( $data_producto['tiempo_estimado_envio_num'] );
        $data_producto['tiempo_estimado_envio_unidad'] = intval( $data_producto['tiempo_estimado_envio_unidad'] );
        $tiempo_estimado_envio = "";

        if( $data_producto['tiempo_estimado_envio_unidad'] == 0 ){
            $tiempo_estimado_envio = $json->trans265;

        }else if( $data_producto['tiempo_estimado_envio_unidad'] == 1 ){
            $tiempo_estimado_envio = $data_producto['tiempo_estimado_envio_num'] . " " . $json->trans262;

        }else if( $data_producto['tiempo_estimado_envio_unidad'] == 2 ){
            $tiempo_estimado_envio = $data_producto['tiempo_estimado_envio_num'] . " " . $json->trans263;

        }else if( $data_producto['tiempo_estimado_envio_unidad'] == 3 ){
            $tiempo_estimado_envio = $data_producto['tiempo_estimado_envio_num'] . " " . $json->trans264;

        }
        $datos_array_temp= Array(
            "param1" => date('Y-m-d', ($productos_transaccion['fecha_creacion'] / 1000)),
            "param2" => $productos_transaccion["id_carrito"],
            "param3" => $productos_transaccion["cantidad"],
            "param4" => $metodo_pago_usado,
            "param5" => $this->maskNumber($precio_total, 2),
            "param6" => $tipo_envio,
            "param7" => $tiempo_estimado_envio
        );
        parent::addLogJB("datos correo enviado: " . json_encode($datos_array_temp));


        $html = str_replace("{{fecha}}",         date('Y-m-d', ($productos_transaccion['fecha_creacion'] / 1000)), $html);
        $html = str_replace("{{id_carrito}}",    $productos_transaccion["id_carrito"], $html);
        $html = str_replace("{{cant_unidades}}", $productos_transaccion["cantidad"], $html);
        $html = str_replace("{{metodo_pago}}",   $metodo_pago_usado, $html);
        $html = str_replace("{{total}}",         $this->maskNumber($precio_total, 2), $html);
        $html = str_replace("{{tipo_envio}}",    $tipo_envio, $html);
        $html = str_replace("{{fecha_entrega}}", $tiempo_estimado_envio, $html);

        $html = str_replace("{{trans157_brand}}",$json->trans157_brand, $html);
        $html = str_replace("{{trans06}}",       $json->trans06, $html);
        $html = str_replace("{{nombre_usuario}}",$data_comprador['nombre'], $html);
        $html = str_replace("{{nombre_vendedor}}",$data_vendedor['nombre'], $html);
        

        $html = str_replace("{{trans159}}", $json->trans159, $html);
        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans158}}",$json->trans158, $html);
        
        // $html = str_replace("{{trans34_valor}}",$this->maskNumber($precio_unidad["precio"], 2) , $html);
        // $html = str_replace("{{trans35_unidad}}",$precio_unidad["moneda"], $html);
       
        $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
        $html = str_replace("{{trans160}}",$json->trans160, $html);
        $html = str_replace("{{trans161_brand}}",$json->trans161_brand, $html);
        $html = str_replace("{{trans162}}",$json->trans162, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        
        $html = str_replace("{{trans253}}", $json->trans253, $html);
        $html = str_replace("{{trans254}}", $json->trans254, $html);
        $html = str_replace("{{trans255}}", $json->trans255, $html);
        $html = str_replace("{{transe256}}", $json->trans256, $html);

        $html = str_replace("{{direccion_vendedor}}",$direccion_vendedor["direccion"], $html);
        $html = str_replace("{{telefono_vendedor}}",$data_vendedor["telefono"], $html);
        $html = str_replace("{{ciudad_vendedor}}",$direccion_vendedor["ciudad"], $html);
        $html = str_replace("{{foto_user}}",$data_vendedor["foto"], $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}","", $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_comprador["uid"]."&act=0&em=".$data_comprador["empresa"], $html); 

        $para      = $data_comprador['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans116_." ".$data_producto['titulo'];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

        parent::addLogJB(" --- htmlEmail_comienza_compra ---" . json_encode($response));

        return $response;
    
    }

    function get_direccion_by_id(Array $data){
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where id = '$data[id]';");
        parent::cerrar();
        return $direccion;
    }

    //lo de envio 
    function consultar_valor_envio($data){
        if(!isset($data) || !isset($data['dane_destino']) || !isset($data['productos'])  || !isset($data["fecha_consulta"]) || !isset($data["cantidad_producto"])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
         //var_dump($data); 
        $accedio_error_de_producto   = 0; 
        $productos                   = $data["productos"]; 
        $ciudad_destino              = $data["dane_destino"];

        // $fecha_remesa                = $this->transformar_fecha_U_to_normal(['fecha' => $data["fecha_consulta"], 'tipo' => 2  ]);

        $fecha_inicio_recogida = "";
        $fecha_fin_recogida    = "";
        $fecha_envio_reco      = "";
        $cantidad_producto     = "";
        $unix_fecha_general    = "";
        $unix_fecha_inicio     = "";
        $unix_fecha_final      = "";

        $fecha_remesa          = $this->obtener_valores_fecha_tcc( 2 );

        $array_de_respuesta_consulta = []; 
        $array_data_data_to_tcc      = []; 
    
        //campos estaticos que no vienen del front 
        foreach ( $productos as $key => $producto ) {
            $data_to_tcc   = []; 
            $data_producto = $this->get_product_por_id(['id' => $producto["id_producto"] ]); 
            $tipo_tcc      = $this->saber_si_tiene_tipo_envio_tcc($data_producto); 
            $data_envio    = $this->get_data_envio(['id_producto' => $producto["id_producto"]]); 
            $respuesta     = $this->validacion_nacionalidad_producto_data_general($data_producto, $data_envio);
            $objeto_tcc    = new Tcc();
            //var_dump($tipo_tcc); 

            $data_respuesta_tcc = [];
            if($respuesta["acceso_consulta"] == true && $tipo_tcc == true){
                $ciudad_origen      = $respuesta["codigo_dane"];


                parent::addLog("xxxxxxxxx----+> [ producto ]: " . json_encode($producto));
                $tipo_envio         = $this->get_tipo_envio([
                    'data_envio'        => $data_envio,
                    'cantidad_producto' => $producto["cantidad"], 
                    'data_producto'     => $data_producto
                ]);
                $valores_cm_medidas = $this->get_valores_cm_medidas([
                    'alto'   => floatval($data_envio["alto"]),
                    "ancho"  => floatval($data_envio["ancho"]),
                    "largo"  => floatval($data_envio["largo"]),
                    "unidad" => $data_envio["unidad_distancia"]
                ]);


                $producto['DATOS_EXTRA_ENVIO'] = Array(
                    "tipo_envio"         => $tipo_envio,
                    "valores_cm_medidas" => $valores_cm_medidas,
                    "ciudad_origen"      => $ciudad_origen
                );

                
                $peso_real= $tipo_envio["peso_mayor"];
                $peso_volumetrico = $tipo_envio["valor_peso_volumetrico"];
                $cuenta_unidad=  $this->determinar_unidad_negocio(intval($tipo_envio["unidad_negocio"])); 
                $data_to_tcc=[]; 
                $data_to_tcc = [
                    "Clave" => $this->clave_to_tcc,
                    "Liquidacion" => array(
                        "tipoenvio"                  => $tipo_envio["unidad_negocio"],
                        "idciudadorigen"             => $ciudad_origen,
                        "idciudaddestino"            => $ciudad_destino,
                        "cuenta"                     => $cuenta_unidad,
                        "valormercancia"             => $tipo_envio["valor_mercancia"],
                        "fecharemesa"                => $fecha_remesa['fecha'],
                        "idunidadestrategicanegocio" => $tipo_envio["unidad_negocio"],
                        "unidades" => array(
                            "unidad" => array(
                                "numerounidades" => $tipo_envio["numero_unidades"],
                                "pesoreal"       => $peso_real,
                                "pesovolumen"    => $peso_volumetrico,
                                "tipoempaque"    => ""
                            )
                        )
                    )
                ];

                parent::addLog("----+> [ SEND | DATOS CONSULTA TCC ]: ". json_encode(Array('DATA_CONSULTA_TCC' => $data_to_tcc, 'producto' => $producto)));

                $data_respuesta_tcc           = $objeto_tcc->consultar_liquidacion_wbs_tcc($data_to_tcc);

                $data_respuesta_tcc["status"] = "success";
                $data_to_tcc["status"]        = "success"; 

                parent::addLog("----+> [ RECIBE | DATOS CONSULTA TCC ]: ". json_encode(Array('DATA_CONSULTA_TCC' => $data_to_tcc, 'producto' => $producto)));

            }else if($respuesta["acceso_consulta"] == false){
                $accedio_error_de_producto     = 1; 
                $data_respuesta_tcc["mensaje"] = $respuesta["mensaje"]; 
                $data_to_tcc["mensaje"]        = $respuesta["mensaje"]; 
                $data_respuesta_tcc["status"]  = $respuesta["status"]; 
                $data_to_tcc["status"]         = $respuesta["status"];

            }else if($tipo_tcc == false){
                $accedio_error_de_producto     = 1;
                $data_respuesta_tcc["mensaje"] = "no es tipo tcc"; 
                $data_to_tcc["mensaje"]        = "no es tipo tcc"; 
                $data_respuesta_tcc["status"]  = "NOtcc"; 
                $data_to_tcc["status"]         = "NOtcc";

            }

            $data_respuesta_tcc["id_producto"] = $producto["id_producto"];
            $data_to_tcc["id_producto"]        = $producto["id_producto"];
            if(isset($data_to_tcc["Clave"])){
                unset($data_to_tcc["Clave"]);
            }
            array_push($array_de_respuesta_consulta,$data_respuesta_tcc); 
            array_push($array_data_data_to_tcc,$data_to_tcc);
            
        }
        if(count($array_de_respuesta_consulta) == 0){
            return array('status' => 'fail', 'message'=> 'ocurrio algun error', 'data' => null);
        }else{
            $data_enviar_respuesta= $this->mapeo_y_suma_de_valores_envio($array_de_respuesta_consulta); 
            return array(
                'status'                        => 'success',
                'message'                       => 'respuesta succes',
                'data'                          => $data_enviar_respuesta,
                'data_enviada_a_tcc'            => $array_data_data_to_tcc,
                'bandera_ocurrio_error_product' => $accedio_error_de_producto
            );
        }
    }

    function get_data_direccion(Array $data){
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        if(count($direccion)>0){
            return $direccion[0];
        }
        return $direccion; 
    }

    function transformar_fecha_U_to_normal(Array $data){
        if($data["tipo"]==1){
            return date("Y-m-d", intval($data["fecha"])); 
        }else if($data["tipo"]==2){//en la bd se guardan multiplicada por mil 
            return date("Y-m-d", intval($data["fecha"])/1000); 
        }
    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT *,(precio-(precio*(porcentaje_oferta/100))) as precio_con_descuento FROM buyinbig.productos WHERE  id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos[0];
    }

    function get_data_envio(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * from productos_envio  WHERE  id_producto = '$data[id_producto]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos[0];
    }

    function validacion_nacionalidad_producto_data_general(Array $data_producto, Array $data_envio){
        if($data_producto["moneda_local"]!= "COP"){
            return array("acceso_consulta"=> false, "mensaje"=> "el precio del producto no es COP", "status"=>"NoPaisProducto"); 
        }else{
            $data_direccion= $this->get_data_direccion(["id"=>$data_producto["id_direccion"]]); 
            if(isset($data_direccion["dane"]) && $data_direccion["dane"]!=""){
                return array("acceso_consulta"=> true, "mensaje"=> "apto para consultar", "codigo_dane" =>$data_direccion["dane"]); 
            }else{
                return array("acceso_consulta"=> false, "mensaje"=> "la direccion de este producto no tiene codigo dane", "codigo_dane" =>"", "status"=>"NoDane"); 
            }
        }
    }

    function get_tipo_envio( Array $data ){
        parent::addLog("----+> [ get_tipo_envio ]: ". json_encode($data));
        $cantidad_producto           = intval($data["cantidad_producto"]); 
        $peso                        = floatval($data["data_envio"]["peso"]); 
        $alto                        = floatval($data["data_envio"]["alto"]); 
        $ancho                       = floatval($data["data_envio"]["ancho"]); 
        $largo                       = floatval($data["data_envio"]["largo"]); 
        $unidad_distancia_longitudes = $data["data_envio"]["unidad_distancia"]; //cm: centimetro in: pulgada 
        $unidad_masa                 = $data["data_envio"]["unidad_masa"]; 
        $peso_mayor                  = 0; 
        $data_producto               = $data["data_producto"]; 
        $valor_mercancia_max         = 3511208; //para mensajeria


        parent::addLog("----+> [ alto ]: ". $alto);
        parent::addLog("----+> [ unidad_distancia_longitudes ]: ". $unidad_distancia_longitudes);
        $alto_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros([
            'valor'  => $alto,
            'unidad' => $unidad_distancia_longitudes
        ]);
        parent::addLog("----+> [ alto_convertido_metros ]: ". $alto_convertido_metros);


        parent::addLog("----+> [ ancho ]: ". $ancho);
        parent::addLog("----+> [ unidad_distancia_longitudes ]: ". $unidad_distancia_longitudes);
        $ancho_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros([
            'valor'  => $ancho,
            'unidad' => $unidad_distancia_longitudes
        ]);
        parent::addLog("----+> [ ancho_convertido_metros ]: ". $ancho_convertido_metros);


        parent::addLog("----+> [ largo ]: ". $largo);
        parent::addLog("----+> [ unidad_distancia_longitudes ]: ". $unidad_distancia_longitudes);
        $largo_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros([
            'valor'  => $largo,
            'unidad' => $unidad_distancia_longitudes
        ]);
        parent::addLog("----+> [ largo_convertido_metros ]: ". $largo_convertido_metros);

        parent::addLog("----+> [ alto_convertido_metros ]: ". $alto_convertido_metros);
        parent::addLog("----+> [ ancho_convertido_metros ]: ". $ancho_convertido_metros);
        parent::addLog("----+> [ largo_convertido_metros ]: ". $largo_convertido_metros);
        $peso_volumetrico        = $this->calculo_peso_volumetrico_tcc([
            'alto'  => $alto_convertido_metros,
            'ancho' => $ancho_convertido_metros,
            'largo' => $largo_convertido_metros
        ]);
        parent::addLog("----+> [ peso_volumetrico ]: ". $peso_volumetrico);

        parent::addLog("----+> [ peso ]: ". $peso);
        parent::addLog("----+> [ unidad_masa ]: ". $unidad_masa);
        $peso_real_producto_kilogramo = $this->convertir_medidas_masa_nasbi_kilo([
            'valor'  => $peso,
            'unidad' => $unidad_masa
        ]);
        parent::addLog("----+> [ peso_real_producto_kilogramo ]: ". $peso_real_producto_kilogramo);
        parent::addLog("----+> [ peso_volumetrico ]: ". $peso_volumetrico);
        parent::addLog("----+> [ peso_real_producto_kilogramo ]: ". $peso_real_producto_kilogramo);

        if($peso_volumetrico > $peso_real_producto_kilogramo){
            // esto se hace porque tcc cobra el valor a cual peso es mayor 
            $peso_mayor = $peso_volumetrico * $cantidad_producto;
            parent::addLog("----+x1> [ peso_mayor ]: ". $peso_mayor);
            parent::addLog("----+x1> [ peso_volumetrico ]: ". $peso_volumetrico);
            parent::addLog("----+x1> [ cantidad_producto ]: ". $cantidad_producto);
        }else{
            $peso_mayor = $peso_real_producto_kilogramo * $cantidad_producto;
            parent::addLog("----+x2> [ peso_mayor ]: ". $peso_mayor);
            parent::addLog("----+x2> [ peso_real_producto_kilogramo ]: ". $peso_real_producto_kilogramo);
            parent::addLog("----+x2> [ cantidad_producto ]: ". $cantidad_producto);
        }

        $peso_mayor             = ceil($peso_mayor); 

        $numero_unidades        = $this->obtener_numero_unidades($peso_mayor);
  
        $valor_mercancia        = $cantidad_producto *  floatval($data_producto["precio_con_descuento"]);

        $peso_volumetrico_total = ceil($peso_volumetrico * $cantidad_producto);

        parent::addLog("----+> [ peso_mayor ]: ". $peso_mayor);

        
        if( $peso_mayor <= 5 && floatval($data_producto["precio_con_descuento"]) < $valor_mercancia_max && $numero_unidades == 1 ){
            //falta por precio de mercancia 
            return array(
                "unidad_negocio"         => 2,
                "peso_mayor"             => $peso_mayor,
                "numero_unidades"        => $numero_unidades,
                "valor_mercancia"        => $valor_mercancia,
                "valor_peso_volumetrico" => $peso_volumetrico_total
            ); //mensajeria
        }else{
            return array(
                "unidad_negocio"         => 1,
                "peso_mayor"             => $peso_mayor,
                "numero_unidades"        => $numero_unidades,
                "valor_mercancia"        => $valor_mercancia,
                "valor_peso_volumetrico" => $peso_volumetrico_total
            ); //paqueteria 
        }
    }

    function get_valores_cm_medidas(Array $data){
        $alto = $data["alto"]; 
        $ancho= $data["ancho"]; 
        $largo= $data["largo"]; 
        $unidad = $data["unidad"]; 
        if($unidad == "in"){
            $alto= $alto/0.39370;
            $ancho= $ancho/0.39370;
            $largo= $largo/0.39370;
        }
        return array("alto"=>$alto, "ancho"=> $ancho, "largo"=> $largo);
    }
    
    function convertir_medidas_masa_nasbi_kilo(Array $data){
        $peso   = $data["valor"]; 
        $unidad = $data["unidad"]; 
        $valor  = $peso; 
        if($unidad == "lb"){
            //por si no es kilogramo directamente 
            $valor =  $peso/2.2046; 
        }
        return $valor;
    }

    function convertir_medidas_longitud_nasbi_to_metros(Array $data){
        $valor=0; 
        if($data["unidad"]== "in"){ //pulgadas a metro 
            $valor= $data["valor"]/39.370; 
        }else if($data["unidad"]== "cm"){ //centimetros a metros
            $valor= $data["valor"]/100; 
        }
        return $valor; 
    }

    function calculo_peso_volumetrico_tcc(Array $data){
        $valor;
        $ancho= $data["ancho"]; 
        $largo= $data["largo"]; 
        $alto= $data["alto"]; 

        $valor= $ancho * $largo * $alto * 400; 
        return $valor;
    }

    function mapeo_y_suma_de_valores_envio(Array $data){
        $total_de_valores_envio   = 0;
        $total_de_valores_volumen = 0; 

        foreach ($data as $key =>$producto) {

            if(isset($producto["consultarliquidacion2Result"]->total->totaldespacho)){
                
                $data[$key]["consultarliquidacion2Result"]->envio_tcc = 1;
                $id_producto= $producto["id_producto"];
                if(isset($producto['consultarliquidacion2Result']->total->totaldespacho)){
                    $total_de_valores_envio= $total_de_valores_envio + floatval($producto["consultarliquidacion2Result"]->total->totaldespacho);
                    $total_de_valores_volumen= $total_de_valores_volumen + floatval($producto["consultarliquidacion2Result"]->total->totalpesovolumen);  
                }
                $data[$key]["consultarliquidacion2Result"]->total->totaldespacho_mask = $this->maskNumber(floatval($producto["consultarliquidacion2Result"]->total->totaldespacho),2);
            }else{
                $contenedor = new stdClass();
                $envio_tcc = new stdClass();
                $contenedor->envio_tcc=0; 
                $data[$key]["consultarliquidacion2Result"]= $contenedor;
            }
        }

        return array(
            "total_de_valor_envio_mask" => $this->maskNumber($total_de_valores_envio, 2),   
            "data_tcc_de_cada_producto" => $data,  
            "total_de_valor_envio"      =>$total_de_valores_envio,
            "total_de_peso_volumen"     =>$total_de_valores_volumen
        );
    }

    function get_direccion_activa_por_uid(Array $data){
        $select = "SELECT * from direcciones where uid= '$data[uid]' and empresa = '$data[empresa]' and activa = 1 ORDER BY id DESC;";
        
        parent::conectar();
        $direccion = parent::consultaTodo( $select );
        parent::cerrar();

        if(count($direccion)>0){
            return $direccion[0];
        }
        return $direccion;
    }

    function agregar_valor_envio_producto(Array $data ){
        $array_carrito                 = $data["array_carrito"]; 
        $uid_carrito                   = $data["uid"];
        $empresa_carrito               = $data["empresa"]; 
        $data_direccion_activa_destino = $this->get_direccion_activa_por_uid([
            'uid'     => $data["uid"],
            'empresa' => $data["empresa"]
        ]);
        $fecha_consulta        = microtime(true);
        $cantidad_producto     = count($array_carrito); 
        $data_general          = null; 
        $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        $array_monedas_local   = $this->filter_by_value_moneda($array_monedas_locales, 'iso_code_2', 'CO');
        $array_monedas_local   = $array_monedas_local[0];


        $count_aliados_logisticos = Array(
            "count" => 0,
            "lista" => []
        );
        $count_aliados_logisticos_cotizados = Array(
            "count" => 0,
            "lista" => []
        );

        if (!empty($data_direccion_activa_destino) && $data_direccion_activa_destino["dane"]!=null && $data_direccion_activa_destino["dane"]!=""){

            $direccion_activa          = 1; 
            $consulta_de_valores_envio = $this->consultar_valor_envio([
                "dane_destino"      => $data_direccion_activa_destino["dane"],
                "productos"         => $array_carrito,
                "fecha_consulta"    => $fecha_consulta,
                "cantidad_producto" => $cantidad_producto
            ]);
            foreach ($array_carrito as $key2 =>$producto2){
                $producto2["tipo_envio"] = intval($producto2["tipo_envio"]);
                if( $producto2["tipo_envio"] == 2 ){
                    if( !isset( $count_aliados_logisticos['lista'][ $producto2["id_producto"] ] ) ){
                        $count_aliados_logisticos['count']++;
                        $count_aliados_logisticos['lista'][ $producto2["id_producto"] ] = true;
                    }
                }
            }


            
            parent::addLog("------+> [ PRUEBA / consulta_de_valores_envio]: " . json_encode( $consulta_de_valores_envio) );
            foreach ($consulta_de_valores_envio["data"]["data_tcc_de_cada_producto"] as $key => $producto){

                foreach ($array_carrito as $key2 =>$producto2){

                    $producto2["tipo_envio"] = intval($producto2["tipo_envio"]);
                    if($producto2["id_producto"] == $producto["id_producto"]){
                        $array_carrito[$key2]["valor_envio_COP"]      = 0;
                        $array_carrito[$key2]["valor_envio_mask_COP"] = 0;
                        $array_carrito[$key2]["envio_tcc"]            = 0; 
                        $array_carrito[$key2]["valor_envio_USD"]      = 0;
                        $array_carrito[$key2]["valor_envio_USD_mask"] = 0;
                    }

                    if($producto["consultarliquidacion2Result"]->envio_tcc == 1){
                        if( $producto2["id_producto"] == $producto["id_producto"] && $producto2["tipo_envio"] == 2 ){
                            $array_carrito[$key2]["valor_envio_COP"]      = floatval($producto["consultarliquidacion2Result"]->total->totaldespacho);
                            $array_carrito[$key2]["valor_envio_mask_COP"] = $producto["consultarliquidacion2Result"]->total->totaldespacho_mask;
                            $array_carrito[$key2]["envio_tcc"]            = 1; 

                            if(count($array_monedas_local) > 0) {
                                $array_carrito[$key2]["valor_envio_USD"] = $this->truncNumber(($array_carrito[$key2]["valor_envio_COP"]/ $array_monedas_local['costo_dolar']), 2);
                                $array_carrito[$key2]["valor_envio_USD_mask"] = $this->maskNumber($array_carrito[$key2]["valor_envio_USD"], 2);
                            }else{
                                $array_carrito[$key2]["valor_envio_USD"] = 0;
                                $array_carrito[$key2]["valor_envio_USD_mask"] = 0;
                            }


                            if( $producto2["tipo_envio"] == 2 ){
                                if( !isset( $count_aliados_logisticos_cotizados['lista'][ $producto2["id_producto"] ] ) ){
                                    $count_aliados_logisticos_cotizados['count']++;
                                    $count_aliados_logisticos_cotizados['lista'][ $producto2["id_producto"] ] = true;
                                }
                            }
                            break;
                        }
                    }
                }
            }

            $data_general["total_envio_COP_mask"] = $consulta_de_valores_envio["data"]["total_de_valor_envio_mask"]; 
            $data_general["total_envio_COP"]      = $consulta_de_valores_envio["data"]["total_de_valor_envio"]; 
            $data_general["total_envio_USD"]      =  $this->truncNumber(($data_general["total_envio_COP"]/ $array_monedas_local['costo_dolar']), 2);
            $data_general["total_envio_USD_mask"] =  $this->maskNumber($data_general["total_envio_USD"], 2);
        }else{
            $direccion_activa=0; 
        }

        parent::addLog("------+> [ PRUEBA / count_aliados_logisticos]: " . json_encode( $count_aliados_logisticos) );
        parent::addLog("------+> [ PRUEBA / count_aliados_logisticos_cotizados]: " . json_encode( $count_aliados_logisticos_cotizados) );

        parent::addLog("------+> [ PRUEBA / CONDICIONAL]: " . ($count_aliados_logisticos['count'] == $count_aliados_logisticos_cotizados['count']) );

        $status = "success";
        if( ($count_aliados_logisticos['count'] != $count_aliados_logisticos_cotizados['count']) ){
            $status = "errorAliadosPendientes";
        }
        return array(
            "status"                       => $status,
            "data_carrito"                 => $array_carrito,
            "data_general_envio"           => $data_general,
            "direccion_activa"             => $direccion_activa,
            "aliados_logisticos"           => $count_aliados_logisticos,
            "aliados_logisticos_cotizados" => $count_aliados_logisticos_cotizados
        );
    }

    function filter_by_value_moneda (Array $array, String $index, $value){
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


    function saber_si_tiene_tipo_envio_tcc(Array $data_producto){
        if(intval($data_producto["envio"]) == 2){
            return true; 
        }
        return false;
    }

    function restarEnStockDetalleProducto( Int $carrito_id, Array $data ){

        $carrito = $carrito_id;

        // 1: Validar si el cliente solicita agregar unidades dato las especificaciones del articulo (Colores/Tallas).
        if( isset($data['id_pair']) && ($data['id_pair'] != NULL && $data['id_pair'] != "") ){

            // 2: Validamos cuantas unidades hay en STOCK.
            $selectxdetallexproductoxcoloresxtallas = "SELECT cantidad FROM detalle_producto_colores_tallas WHERE id = '$data[id_pair]';";
            $stock_articulo                         = parent::consultaTodo($selectxdetallexproductoxcoloresxtallas);

            if ( count($stock_articulo) == 0) {
                return array(
                    "status" => "fail",
                    "mensaje" => "Este articulo no tiene detalles o especificacion (Colores/Tallas) - v1",
                    "data"    => $data
                );
            }

            $stock_articulo             = $stock_articulo[0];
            $stock_articulo['cantidad'] = intval( $stock_articulo['cantidad'] );
            $data['cantidad']           = intval( $data['cantidad'] );



            // Validamos si quieren eliminar un Color/talla

            if ( $data['cantidad'] == 0 ) {

                $selectxproductosxtransaccionxespecificacionxproducto = 
                    "SELECT * 
                    FROM productos_transaccion_especificacion_producto
                    WHERE id_carrito = '$carrito' AND id_producto = '$data[id_producto]' AND id_detalle_producto_colores_tallas = '$data[id_pair]';";

                $productos_transaccion_especificacion_producto = parent::consultaTodo($selectxproductosxtransaccionxespecificacionxproducto);

                if ( count($productos_transaccion_especificacion_producto) > 0) {
                    $productos_transaccion_especificacion_producto = $productos_transaccion_especificacion_producto[0];


                    $deletexvariacion = "DELETE FROM buyinbig.productos_transaccion_especificacion_producto WHERE id = $productos_transaccion_especificacion_producto[id];";

                    $responseItem = parent::query($deletexvariacion);

                    $selectxvariacionesxtotal = "SELECT SUM(cantidad) AS cantidades_agregadas FROM productos_transaccion_especificacion_producto WHERE id_carrito = '$carrito';";


                    $selectxvariacionesxtotal = parent::consultaTodo( $selectxvariacionesxtotal );


                    if ($responseItem) {
                        return array(
                            "status"                                        => "eliminado",
                            "responseItem"                                  => $responseItem,
                            "productos_transaccion_especificacion_producto" => $productos_transaccion_especificacion_producto,
                            'variaciones_totales'                           => $selectxvariacionesxtotal[0]['cantidades_agregadas']
                        );
                        
                    }
                }
            }

            // 3: Saber si puede agregar más o no.
            if( $stock_articulo['cantidad'] - $data['cantidad'] >= 0 ){

                // 4: Determinar si es un INSERT || UPDATE
                $selectxproductosxtransaccionxespecificacionxproducto = 
                    "SELECT * 
                    FROM productos_transaccion_especificacion_producto
                    WHERE id_carrito = '$carrito' AND id_producto = '$data[id_producto]' AND id_detalle_producto_colores_tallas = '$data[id_pair]';";

                $productos_transaccion_especificacion_producto = parent::consultaTodo($selectxproductosxtransaccionxespecificacionxproducto);


                $update = "";
                $response             = -1;
                $response_descripcion = "";
                if ( count($productos_transaccion_especificacion_producto) == 0) {
                    // INSERT
                    $insertarColoresTallas = 
                        "INSERT INTO productos_transaccion_especificacion_producto(
                            id_carrito,
                            id_transaccion,
                            id_producto,
                            id_detalle_producto_colores_tallas,
                            cantidad
                        ) 
                        VALUES(
                            $carrito,
                            $carrito,
                            $data[id_producto],
                            $data[id_pair],
                            $data[cantidad]
                        )";

                    $response = parent::queryRegistro($insertarColoresTallas);
                    $response_descripcion = "El proceso paso por INSERT REGISTRO";
                }else{

                    $productos_transaccion_especificacion_producto = $productos_transaccion_especificacion_producto[0];
                    $new_cantidad = 0;
                    if ( isset( $data['accion_reemplazar_cantidad'] ) ) {
                        $new_cantidad = $data['cantidad'];
                    }else{
                        $new_cantidad = $productos_transaccion_especificacion_producto['cantidad'] + $data['cantidad'];
                    }

                    $update =
                    "UPDATE productos_transaccion_especificacion_producto SET
                        cantidad = $new_cantidad
                    WHERE id = $productos_transaccion_especificacion_producto[id];";

                    $response = parent::query($update);
                    $response_descripcion = "El proceso paso por UPDATE REGISTRO";
                }

                if ( $response > 0 ) {
                    return array(
                        "status"   => "success",
                        "mensaje"  => $response_descripcion,
                        "response" => $response,
                        "data"     => $data
                    );
                }else{
                    return array(
                        "status"  => "errorAlmacenar",
                        "mensaje" => "No fue posible realizar el almacenamiento. Nota: " . $response_descripcion,
                        "data"    => $data
                    );
                }
            }else{
                return array(
                    "status"     => "stockError",
                    "mensaje"    => "estas intentando agregar mas de la cantidad disponible",
                    "data"       => $data,
                    "stock"      => $stock_articulo['cantidad'],
                    "agregar"    => $data['cantidad'],
                    "operacion"  => ($stock_articulo['cantidad'] - $data['cantidad'])
                );
            }
        }else{
            return array(
                "status"  => "sinEspecificaciones",
                "mensaje" => "Este articulo no tiene detalles o especificacion (Colores/Tallas)",
                "data"    => $data
            );
        }
    }


    function obtener_numero_unidades($peso_mayor){
        //cada 30 kilos es una caja 
        return ceil($peso_mayor/30); 
    }

    function determinar_unidad_negocio(int $tipo_envio){
        if($tipo_envio == 1){
          return $this->unidad_negocio_paqueteria; //paqueteria 
        }else{
          return $this->unidad_negocio_mensajeria;  //mensajeria
        }
    }

    function obtener_valores_fecha_tcc($opcion_recogida)
    {
        $nasbifunciones = new Nasbifunciones();
        $fechas = $nasbifunciones->getFechaRecogidaTcc($opcion_recogida);
        unset($nasbifunciones);
        return $fechas;
    }



    function getReporteSubastas( Array $data ){
        $selectxreport =
            "SELECT 
                ps.id                                             AS 'ID_NASBI_DESCUENTO',
                ps.tipo                                           AS 'TIPO_SUBASTA_ID',
                FROM_UNIXTIME(ps.fecha_creacion/1000, '%d/%m/%Y') AS 'FECHA',
                FROM_UNIXTIME(ps.fecha_inicio/1000,'%d/%m/%Y')    AS 'HORA_INICIO',
                IF(ps.tipo < 6, 'PREMIUM', 'ESTANDAR')            AS 'TIPO',
                
                CASE
                    WHEN ps.tipo = 1 THEN 'Bronze'
                    WHEN ps.tipo = 2 THEN 'Silver'
                    WHEN ps.tipo = 3 THEN 'Gold'
                    WHEN ps.tipo = 4 THEN 'Platinum'
                    WHEN ps.tipo = 5 THEN 'Diamond'
                    WHEN ps.tipo = 6 THEN 'Standard'
                    ELSE 'No Definido'
                END AS 'CATEGORIA',
                
                
                CASE
                    WHEN ps.estado = 0 THEN 'INSCRIBIENDO'
                    WHEN ps.estado = 1 THEN 'INSCRIBIENDO'
                    WHEN ps.estado = 2 THEN 'POR POSTOR'
                    WHEN ps.estado = 3 THEN 'ACTIVA'
                    WHEN ps.estado = 4 THEN 'FINALIZADO'
                    ELSE 'No Definido'
                END AS 'ESTADO',
                
                p.precio                                          AS 'VALOR_COMERCIAL',
                ps.precio_usd                                     AS 'VALOR_COMERCIAL_USD',

                0                                                 AS 'VALOR_INICIO_ND',
                0                                                 AS 'DESCUENTO_OFERTADO',
                0                                                 AS 'VALOR_OFERTADO',

                ps.inscritos                                      AS 'NUMERO_INSCRITOS_FINALIZADO',
                ps.inscritos_activacion_subasta                   AS 'NUMERO_INSCRITOS',
                p.titulo                                          AS 'PRODUCTO',
                p.cantidad                                        AS 'UNIDADES',
                
                ''                                                AS 'GANADOR',
                ''                                                AS 'FUENTE',
                ''                                                AS 'TIPO_IDENTIF',
                ''                                                AS 'NO_IDENTIFICACION',
                ''                                                AS 'CORREO',

                FROM_UNIXTIME(ps.fecha_fin/1000,'%d/%m/%Y')       AS 'HORA_FIN'

            FROM buyinbig.productos p
            INNER JOIN buyinbig.productos_subastas ps ON(p.id = ps.id_producto)
            WHERE ps.estado = 4;";

        parent::conectar();
        $reporteSubasta = parent::consultaTodo( $selectxreport );
        parent::cerrar();

        if( COUNT( $reporteSubasta ) == 0 ){
            return Array("status" => "fail", "data" => null);
        }else{
            return $this->getReporteSubastasMap( $reporteSubasta );
        }
    }
    function getReporteSubastasMap(Array $productos){
        foreach ($productos as $x => $producto) {
            
            $producto['VALOR_COMERCIAL']                = intval(   $producto['VALOR_COMERCIAL'] );
            $producto['NUMERO_INSCRITOS']               = intval(   $producto['NUMERO_INSCRITOS'] );
            $producto['NUMERO_INSCRITOS_FINALIZADO']    = intval(   $producto['NUMERO_INSCRITOS_FINALIZADO'] );

            if( $producto['NUMERO_INSCRITOS'] == 0 ){
                $producto['NUMERO_INSCRITOS']           = $producto['NUMERO_INSCRITOS_FINALIZADO'];
            }

            $producto['TIPO_SUBASTA_ID']                = intval(   $producto['TIPO_SUBASTA_ID'] );
            $producto['VALOR_COMERCIAL_USD']            = floatval( $producto['VALOR_COMERCIAL_USD'] );

            $producto['VALOR_INICIO_ND']                = floatval( $producto['VALOR_INICIO_ND'] );
            $producto['DESCUENTO_OFERTADO']             = floatval( $producto['DESCUENTO_OFERTADO'] );
            $producto['VALOR_OFERTADO']                 = floatval( $producto['VALOR_OFERTADO'] );

            if( $producto['TIPO_SUBASTA_ID'] < 6){
                $porcentaje  = 0;
                $monto_usd   = 0;

                parent::conectar();
                $selectednumber = parent::consultaTodo("SELECT * FROM productos_subastas_tipo pst WHERE $producto[VALOR_COMERCIAL_USD] BETWEEN rango_inferior_usd AND rango_superior_usd");
                parent::cerrar();

                for ($i = 1; $i <= 6; $i++) {

                    $index = $i * 10;
                    $index = $index / 100;

                    # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
                    # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
                    $recaudar_entradas = ($producto['VALOR_COMERCIAL_USD'] / 0.77) - ($producto['VALOR_COMERCIAL_USD'] - ($producto['VALOR_COMERCIAL_USD'] * $index));
                    #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
                    $productos_subastas_tipo = $selectednumber[0];
                    $apostadores             = $recaudar_entradas / $productos_subastas_tipo['costo_ticket_usd'];
                    $apostadores             = round($apostadores);

                    if ($producto['NUMERO_INSCRITOS'] >= $apostadores) {
                        $producto['DESCUENTO_OFERTADO'] = $index;
                        $producto['VALOR_INICIO_ND']    = $producto['VALOR_COMERCIAL_USD']   - ($producto['VALOR_COMERCIAL_USD']    * $producto['DESCUENTO_OFERTADO']);
                    } 
                }
            }else{
                $inscritos  = $producto['NUMERO_INSCRITOS'];
                if ($inscritos > 35) $inscritos = 35;
                $porcentaje = ($inscritos * 0.029) - 0.1;
                $producto['DESCUENTO_OFERTADO'] = $porcentaje;

                $producto['VALOR_INICIO_ND']    = $producto['VALOR_COMERCIAL_USD'] - ($producto['VALOR_COMERCIAL_USD'] * $producto['DESCUENTO_OFERTADO']);
            }
            
            $producto['DESCUENTO_OFERTADO'] = $producto['DESCUENTO_OFERTADO'] * 100;

            $selectxproductosxsubastasxpujas =
                "SELECT * FROM buyinbig.productos_subastas_pujas WHERE id_subasta = 149 ORDER BY id DESC LIMIT 1;";

            parent::conectar();
            $productos_subastas_pujas = parent::consultaTodo( $selectxproductosxsubastasxpujas );
            parent::cerrar();
            if( COUNT( $productos_subastas_pujas ) > 0 ){
                $productos_subastas_pujas            = $productos_subastas_pujas[0];

                $producto['VALOR_OFERTADO']          = floatval( $productos_subastas_pujas['monto'] );
                $producto['GANADOR_ID']              = intval( $productos_subastas_pujas['uid'] );

                $productos_subastas_pujas['empresa'] = 0;


                $schemaUsuario = $this->getDatosUsuario( 
                    Array(
                        "uid"     => intval($productos_subastas_pujas['uid']),
                        "empresa" => intval($productos_subastas_pujas['empresa'])
                    )
                );

                $producto['GANADOR']           = $schemaUsuario['GANADOR'];
                $producto['FUENTE']            = $schemaUsuario['FUENTE'];
                $producto['TIPO_IDENTIF']      = $schemaUsuario['TIPO_IDENTIF'];
                $producto['NO_IDENTIFICACION'] = $schemaUsuario['NO_IDENTIFICACION'];
                $producto['CORREO']            = $schemaUsuario['CORREO'];
            }
            $productos[$x] = $producto;
        }
        return $productos;
    }

    function getDatosUsuario(Array $datos){
        $selectxusuario = "";
        if( $datos['empresa'] == 0 ){
            $selectxusuario = 
                "SELECT
                    u.nombreCompleto        AS 'GANADOR',
                    p.nombre                AS 'FUENTE',
                    u.tipo_identificacion   AS 'TIPO_IDENTIF',
                    u.numero_identificacion AS 'NO_IDENTIFICACION',
                    u.email                 AS 'CORREO'

                FROM      peer2win.usuarios u
                LEFT JOIN peer2win.plataformas p ON(u.plataforma_registro = p.id)
                WHERE     u.id = $datos[uid];";
            
            parent::conectar();
            $usuario = parent::consultaTodo( $selectxusuario );
            parent::cerrar();

            return $usuario[0];

        }else{
            $selectxusuario =
            "SELECT
                razon_social AS 'GANADOR',
                'NASBI'      AS 'FUENTE',
                'NIT'        AS 'TIPO_IDENTIF',
                nit          AS 'NO_IDENTIFICACION',
                correo       AS 'CORREO'
            FROM buyinbig.empresas WHERE id = $datos[uid];";

            parent::conectar();
            $empresa = parent::consultaTodo( $selectxusuario );
            parent::cerrar();

            return $empresa[0];
        }

    }
}
?>