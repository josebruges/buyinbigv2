<?php
require 'nasbifunciones.php';


class Liquidarventas extends Conexion
{   
    // tipo_timeline: 1= VENDEDOR, 2= COMPRADOR
    public function misVentas_finalizadas(Array $data)
    {
        if(!isset($data) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        $estado = "WHERE pt.estado = 13 ";
        $numpagina=9; 
        $exposicion = "";
        if(isset($data['exposicion'])) $exposicion = "AND p.exposicion = '$data[exposicion]'";

        $ventas = $this->get_ventas_segun_estado(array(
            "pagina"=> $data["pagina"],
            "estado"=>$estado,
            "exposicion"=> $exposicion,
            "num_paginas"=> $numpagina
        ));

        $ventasAux = $ventas;

        parent::conectar(); 

        $ventasListItems = array();
        $ventasSchema = array();
        if ( count($ventasAux) > 0 ) {
         for ( $i=0; $i<count($ventasAux); $i++ ) {

             $idFilter  = $ventasAux[$i]['id_carrito'];

             $selecxventasSubConsulta = "
             SELECT 
                 pt.*,
                 DATEDIFF(NOW(),from_unixtime((pt.fecha_actualizacion / 1000),'%Y-%m-%d %H:%i:%s')) as 'dias_transcurridos',
                 p.titulo,
                 p.foto_portada,
                 pte.descripcion AS descripcion_estado,
                 ptt.descripcion AS descripcion_tipo,
                 p.exposicion, p.fecha_creacion AS fecha_creacion_producto,
                 (SELECT SUM(hp.visitas) FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto) AS visitas,
                 (SELECT SUM(pt.cantidad) FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10) AS cantidad_vendidas
             FROM
                 productos_transaccion pt
                 LEFT JOIN productos p ON pt.id_producto = p.id
                 LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                 LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                 WHERE pt.id_carrito = '$idFilter'
                 ORDER BY fecha_creacion DESC";

             $ventasItem = parent::consultaTodo($selecxventasSubConsulta);

             if ( count($ventasItem) > 0 ) {
                 for ( $j=0; $j < count($ventasItem); $j++ ) {
                     array_push( $ventasListItems, $ventasItem[ $j ] );
                 }

             }
         }
        }

        $ventas = $this->mapeoVentas($ventasListItems, false);

        $newVentas = array();
        $fiat = "USD";
        if (count($ventas) > 0) {
         for ($i=0;$i<count($ventas);$i++) {
             if ($ventas[$i]["moneda"] != "Nasbiblue" && $ventas[$i]["moneda"] != "Nasbigold") {
                 $fiat = $ventas[$i]["moneda"];
             }
             if (!isset($newVentas[$ventas[$i]["id_carrito"]])) {
                 $newVentas[$ventas[$i]["id_carrito"]] = array();
                 $newVentas[$ventas[$i]["id_carrito"]]["total_usd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["total_fiat"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["total_sd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["total_bd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["dias_transcurridos"]= $ventas[$i]["dias_transcurridos"]; 
                 $newVentas[$ventas[$i]["id_carrito"]]["esta_liquidado"]= $ventas[$i]["esta_liquidado"]; 
             } 
             if (!isset($newVentas[$ventas[$i]["id_carrito"]]["productos"])) {
               $newVentas[$ventas[$i]["id_carrito"]]["productos"] = array();  
             }
             if (!isset($newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]])) {
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]] = $ventas[$i];
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["bd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["bd_usd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["sd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["sd_usd"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["fiat"] = 0;
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["fiat_usd"] = 0;
             }
             if ($ventas[$i]["moneda"] == "Nasbiblue") {
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["bd"] = $ventas[$i]["precio"];
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["bd_usd"] = $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_usd"] += $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_bd"] += $ventas[$i]["precio"];
             } else if ($ventas[$i]["moneda"] == "Nasbigold") {
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["sd"] = $ventas[$i]["precio"];
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["sd_usd"] = $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_usd"] += $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_sd"] += $ventas[$i]["precio"];

             } else {
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["fiat"] = $ventas[$i]["precio"];
                 $newVentas[$ventas[$i]["id_carrito"]]["productos"][$ventas[$i]["id_producto"]]["fiat_usd"] = $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_usd"] += $ventas[$i]["precio_usd"];
                 $newVentas[$ventas[$i]["id_carrito"]]["total_fiat"] += $ventas[$i]["precio"];
             } 
         }

        }
        if($ventas){
         $todosxlosxproductosxcount = "
         SELECT  COUNT( distinct(id_carrito) ) AS contar FROM buyinbig.productos_transaccion pt INNER JOIN productos p ON (pt.id_producto = p.id) $estado $exposicion;";
         $todoslosproductoscount = parent::consultaTodo($todosxlosxproductosxcount);
         $todoslosproductos = floatval($todoslosproductoscount[0]['contar']);

         foreach ($newVentas as $key =>$value) {
             foreach ($value["productos"] as $key2 => $value2) {
                 $newVentas[$key]["productos"][$key2]["bd_mask"] = $this->maskNumber($value2["bd"], 2);
                 $newVentas[$key]["productos"][$key2]["bd_usd_mask"] = $this->maskNumber($value2["bd_usd"]);
                 $newVentas[$key]["productos"][$key2]["sd_mask"] = $this->maskNumber($value2["sd"], 2);
                 $newVentas[$key]["productos"][$key2]["sd_usd_mask"] = $this->maskNumber($value2["sd_usd"]);
                 $newVentas[$key]["productos"][$key2]["fiat_mask"] = $this->maskNumber($value2["fiat"]);
                 $newVentas[$key]["productos"][$key2]["fiat_usd_mask"] = $this->maskNumber($value2["fiat_usd"]);
                 $newVentas[$key]["productos"][$key2]["moneda_fiat"] = $fiat;
             }
             $newVentas[$key]["total_usd_mask"] = $this->maskNumber($newVentas[$key]["total_usd"]);
             $newVentas[$key]["total_fiat_mask"] = $this->maskNumber($newVentas[$key]["total_fiat"]);
             $newVentas[$key]["total_sd_mask"] = $this->maskNumber($newVentas[$key]["total_sd"]);
             $newVentas[$key]["total_bd_mask"] = $this->maskNumber($newVentas[$key]["total_bd"]);
         }
         $totalpaginas = $todoslosproductos/$numpagina;
         $totalpaginas = ceil($totalpaginas);
         
         $request = array('status' => 'success', 'message'=> 'mis ventas', 'pagina'=> $data["pagina"], 'total_paginas'=>$totalpaginas, 'productos' => $todoslosproductos, 'total_productos' => $todoslosproductos, 'data' => $newVentas, 'ventasListItems' => $ventasListItems);
        }else{
         $request = array('status' => 'fail', 'message'=> 'no se encontraron ventas', 'pagina'=> $data["pagina"], 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null, 'ventasListItems' => $ventasListItems);
        }
        parent::cerrar();
        return $request;
    }

    function get_ventas_segun_estado(Array $data){
        $pagina = floatval($data["pagina"]);
        $numpagina = $data["num_paginas"];
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $request = null;
        parent::conectar();
        $selecxventas = "
            SELECT *
            FROM
                (SELECT 
                    *, (@row_number:=@row_number + 1) AS num
                FROM
                    (SELECT 
                        SUM(pt.precio) p2,
                        pt.*,
                        DATEDIFF(NOW(),from_unixtime((pt.fecha_actualizacion / 1000),'%Y-%m-%d %H:%i:%s')) as 'dias_transcurridos',
                        p.titulo,
                        p.foto_portada,
                        pte.descripcion AS descripcion_estado,
                        ptt.descripcion AS descripcion_tipo,
                        p.exposicion, p.fecha_creacion AS fecha_creacion_producto,
                        (SELECT SUM(hp.visitas) FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto) AS visitas,
                        (SELECT SUM(pt.cantidad) FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10) AS cantidad_vendidas
                    FROM productos_transaccion pt
                    LEFT JOIN productos p ON pt.id_producto = p.id
                    LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                    LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                    JOIN (SELECT @row_number:=0) r
                     $data[estado] $data[exposicion] and DATEDIFF(NOW(),from_unixtime((pt.fecha_actualizacion / 1000),'%Y-%m-%d %H:%i:%s')) >= 15  GROUP BY pt.id_carrito
                    ORDER BY fecha_creacion DESC
                ) AS datos
                ORDER BY fecha_creacion DESC
            ) AS info
            WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $ventas = parent::consultaTodo($selecxventas);
        $ventasAux = $ventas;
        parent::cerrar(); 

       return $ventasAux;

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
          // $venta['dias_transcurridos']= floatval($venta['dias_transcurridos']);

            $venta['cantidad'] = floatval($venta['cantidad']);

            $venta['boolcripto'] = floatval($venta['boolcripto']);


            $venta['precio'] = floatval($venta['precio']); // Precio 


            if($venta['boolcripto'] == 0)$venta['precio_mask'] = $this->maskNumber($venta['precio'], 2);
            if($venta['boolcripto'] == 1)$venta['precio_mask'] = $this->maskNumber($venta['precio'], 2);
            $venta['precio_usd'] = floatval($venta['precio_usd']);
            $venta['precio_usd_mask'] = $this->maskNumber($venta['precio_usd'], 2);
            $venta['precio_moneda_actual_usd'] = floatval($venta['precio_moneda_actual_usd']);
            $venta['fecha_creacion'] = floatval($venta['fecha_creacion']);
            $venta['fecha_actualizacion'] = floatval($venta['fecha_actualizacion']);
            $venta['estado'] = floatval($venta['estado']);
            $venta['contador'] = floatval($venta['contador']);
            $venta['id_metodo_pago'] = floatval($venta['id_metodo_pago']);
            $venta['tipo'] = floatval($venta['tipo']);
            $venta['empresa'] = floatval($venta['empresa']);
            $venta['empresa_comprador'] = floatval($venta['empresa_comprador']);
            if(isset($venta['num'])) $venta['num'] = floatval($venta['num']);
            $venta['detalle_pago'] = $this->detallePagoTransaccion($venta['id'], $venta['id_metodo_pago'], $confidencial);
            $venta['envio'] = $this->detalleEnvioTransaccion($venta);
            $venta['datos_usuario_comprador'] = $this->datosUser(['uid'=> $venta['uid_comprador'], 'empresa' => $venta['empresa_comprador']])['data'];
            $venta['datos_usuario_comprador']["direccion_trans"]= $this->get_direccion_venta('id_direccion_comprador', floatval($venta['id']), 0);
            $venta['datos_usuario_vendedor'] = $this->datosUser(['uid'=> $venta['uid_vendedor'], 'empresa' => $venta['empresa']])['data'];
            $venta['datos_usuario_vendedor']["direccion_trans"]= $this->get_direccion_venta('id_direccion_vendedor', floatval($venta['id']), 0);
            $venta['timeline'] = $this->timelineTransaccion($venta, 1)['data'];
          //  $venta['contador_chat'] = $this->chatContador($venta, 1)['data'];

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
            parent::consultaTodo("SELECT * FROM productos_transaccion_especificacion_producto WHERE id_transaccion = '$venta[id_carrito]' AND id_producto = '$venta[id_producto]'");
            $venta['variaciones'] = [];
            if( $resultPTEP && count($resultPTEP) > 0 ){
                foreach($resultPTEP as $itemPTEP){
                    $id_DPCT = $itemPTEP['id_detalle_producto_colores_tallas']; 
                    
                    $confi_color_talla = parent::consultaTodo("SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
                    pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
                    pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
                    FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
                    JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE dpct.id = '$id_DPCT';");
                    

                    if($confi_color_talla && count($confi_color_talla) > 0){
                        array_push($venta['variaciones'], array(
                            'color' => $confi_color_talla[0]['hexadecimal'],
                            'tallaES' => $confi_color_talla[0]['talla_nombre_es'],
                            'tallaEN' => $confi_color_talla[0]['talla_nombre_en']
                        ));
                    }
                }
            }
            // fin nuevo codigo 
            
            $ventas[$x] = $venta;
        }

        return $ventas;
    }


    function timelineTransaccion(Array $data, Int $tipo)    
    {
      
        $selectxtimeline = "SELECT ptt.* FROM productos_transaccion_timeline ptt WHERE ptt.id_transaccion = '$data[id_carrito]' AND ptt.tipo = '$tipo'";
        $timeline = parent::consultaTodo($selectxtimeline);
     
        if(count($timeline) <= 0) return array('status' => 'fail', 'message'=> 'no timeline', 'data' => null);

        $timeline = $this->mapTimeLine($timeline);
        return array('status' => 'success', 'message'=> 'timeline', 'data' => $timeline);
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
        $selecxenvio = "SELECT pte.id, $extra pte.id_transaccion, pte.id_direccion_vendedor, pte.id_direccion_comprador, pte.id_prodcuto_envio, pte.id_envio_shippo, pte.id_ruta_shippo, pte.id_transaccion_shippo, pte.numero_guia, pte.url_numero_guia, pte.empresa, pte.etiqueta_envio, pte.factura_comercial, pte.fecha_creacion, pte.fecha_actualizacion,
        dc.id AS comprador_id_direccion, dc.id_shippo AS comprador_id_shippo, dc.uid AS comprador_uid, dc.pais AS comprador_pais, dc.departamento AS comprador_departamento, dc.ciudad AS comprador_ciudad, dc.latitud AS comprador_latitud, dc.longitud AS comprador_longitud, dc.codigo_postal AS comprador_codigo_postal, dc.direccion AS comprador_direccion,
        dv.id AS vendedor_id_direccion, dv.id_shippo AS vendedor_id_shippo, dv.uid AS vendedor_uid, dv.pais AS vendedor_pais, dv.departamento AS vendedor_departamento, dv.ciudad AS vendedor_ciudad, dv.latitud AS vendedor_latitud, dv.longitud AS vendedor_longitud, dv.codigo_postal AS vendedor_codigo_postal, dv.direccion AS vendedor_direccion
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
            // if(isset($envio['id_prodcuto_envio'])) $envio['id_prodcuto_envio'] = floatval($envio['id_prodcuto_envio']);
            // $envio['id_envio_shippo'] = floatval($envio['id_envio_shippo']);
            // $envio['id_ruta_shippo'] = floatval($envio['id_ruta_shippo']);
            // $envio['id_transaccion_shippo'] = floatval($envio['id_transaccion_shippo']);
            if(isset($envio['tipo_envio'])) $envio['tipo_envio'] = 3;//floatval($envio['tipo_envio']);
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
        $identificacion= null; 

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto'];//"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
                $identificacion=  $user['numero_identificacion']; 
            }else if($empresa == 1){
                $datanombre = $user['razon_social'];//$user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $identificacion=  $user['nit']; 
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $user['identificacion'] = $identificacion;
            $usuarios[$x] = $user;
        }

        return $usuarios;
    }

    function actualizarTransaccion( Array $data) {
        if ( isset($data) || isset($data['id_carrito']) || isset($data['estado']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'no data',
                'data' => null
            );
        }

        $data['estado'] = intval( $data['estado'] );

        $fecha = intval(microtime(true)*1000);
        $updatextransaccion = 
        "UPDATE productos_transaccion 
        SET 
            esta_liquidado = '$count_login',
            fecha_actualizacion = '$fecha'
        WHERE id = $data[estado];";

        parent::conectar();
        $update = parent::query($updatextransaccion);
        parent::cerrar();
        if(!$update) {
            return array(
                'status' => 'fail',
                'message'=> 'datos no actualizados',
                'data' => null
            );
        }
        return array(
            'status' => 'success',
            'message'=> 'La transaccion ha sido actualizada',
            'data' => null
        );
    }

    function get_direccion_venta(String $tipo, int $id, int $activo=1){
        if($activo==1){
            parent::conectar();
        }
        $direccion = parent::consultaTodo("select * from productos_transaccion_envio where id_transaccion = $id limit 1");
        if($activo==1){
            parent::cerrar();
        }
 
        $direccion_transaccion=[]; 
        if(isset($direccion)){
            if(count($direccion)>0){
                $direccion_transaccion = $this->get_direccion_by_id($direccion[0][$tipo], $activo ); 
            }   
        }
        
        return $direccion_transaccion;

    }

    function get_direccion_by_id(int $id,  int $activo=1){
        if($activo==1){
            parent::conectar();
        }
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where id = '$id';");
        if($activo==1){
            parent::cerrar();
        }
        return $direccion;
    }

}
?>