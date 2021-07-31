<?php
require 'nasbifunciones.php';
class HistorialUsuario extends Conexion
{
    public function historialUser(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);
        $ordenar = "fecha_actualizacion_historial DESC";
        if (isset($data['ordenar'])) {
            $ordenar = "precio_local $data[ordenar]";
        }
        $pagina = floatval($data['pagina']);
        $numpagina = 8;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
      
        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT p.*, p.precio AS precio_local $select_precio, hp.fecha_actualizacion AS fecha_actualizacion_historial, hp.id AS id_historial
                FROM productos p 
                INNER JOIN hitstorial_productos hp ON p.id = hp.id_producto
                JOIN (SELECT @row_number := 0) r 
                WHERE p.estado = 1 AND hp.uid = '$data[uid]' AND hp.empresa = '$data[empresa]' AND hp.estado = 1
                ORDER BY $ordenar
                )as datos 
            ORDER BY $ordenar
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $productos = parent::consultaTodo($selecthome);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $productos = $this->mapProductos($productos);
        $selecttodos = "SELECT COUNT(hp.id) AS contar, hp.fecha_actualizacion AS fecha_actualizacion_historial
        FROM productos p 
        INNER JOIN hitstorial_productos hp ON p.id = hp.id_producto
        WHERE p.estado = 1 AND hp.uid = '$data[uid]' AND hp.empresa = '$data[empresa]' AND hp.estado = 1
        ORDER BY fecha_actualizacion_historial DESC;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        
        return array('status' => 'success', 'message'=> 'productos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($productos), 'total_productos' => $todoslosproductos, 'data' => $productos);
    }

    public function eliminarHistorial(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $whereid = "id = '$data[id]' AND";
        if($data['id'] == 'all') $whereid = "";

        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletexhistorial = "UPDATE hitstorial_productos SET estado = 0, fecha_actualizacion = '$fecha' WHERE $whereid uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $eliminar = parent::query($deletexhistorial);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no se elimino el historial', 'data' => null);

        return array('status' => 'success', 'message'=> 'historial eliminado', 'data' => null);
    }


    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2_money'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
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

    function mapProductosOLD($productos){
        $nodo = new Nodo();
        $precios = array(
            'BTC'=> $nodo->precioUSD(),
            'EBG'=> $nodo->precioUSDEBG()
        );
        foreach ($productos as $x => $producto) {
            unset($producto['precio']);
            
            $producto['id_historial'] = floatval($producto['id_historial']);
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
            if(isset($producto['precio_local_user'])){
                $producto['precio_local_user'] = floatval($this->truncNumber($producto['precio_local_user'],2));
                $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
            }else{
                $producto['precio_local_user'] = $producto['precio_usd'];
                $producto['precio_local_user_mask'] = $producto['precio_usd_mask'];
                $producto['moneda_local_user'] = 'USD';
            }
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
            $producto['precio_descuento_local_user'] = $producto['precio_local_user'];
            $producto['precio_descuento_local_user_mask'] = $this->maskNumber($producto['precio_descuento_local_user'], 2);

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);

                $producto['precio_descuento_local_user'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local_user'] = $producto['precio_usd'] - $producto['precio_descuento_local_user'];
                $producto['precio_descuento_local_user']= floatval($this->truncNumber($producto['precio_descuento_local_user'], 2));
                $producto['precio_descuento_local_user_mask']= $this->maskNumber($producto['precio_descuento_local_user'], 2);
            }

            $producto['precio_nasbiblue'] = floatval($this->truncNumber(($producto['precio_descuento_usd'] / $precios['EBG']), 6));
            $producto['precio_nasbiblue_mask'] = $this->maskNumber($producto['precio_nasbiblue'], 6);
            $producto['precio_nasbiblue_moneda'] = 'Nasbiblue';
            $producto['precio_nasbigold'] = floatval($this->truncNumber(($producto['precio_descuento_usd'] / $precios['BTC']), 6));
            $producto['precio_nasbigold_mask'] = $this->maskNumber($producto['precio_nasbigold'], 6);
            $producto['precio_nasbigold_moneda'] = 'Nasbigold';

            $productos[$x] = $producto;
        }
        unset($nodo);
        return $productos;
    }
    function mapProductos($productos){
        $nodo = new Nodo();
        $precios = array(
            'BTC'=> $nodo->precioUSD(),
            'EBG'=> $nodo->precioUSDEBG()
        );
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

            if(isset($producto['precio_local_user'])){
                $producto['precio_local_user'] = floatval($this->truncNumber($producto['precio_local_user'],2));
                $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
                if($producto['moneda_local_user'] == $producto['moneda_local']){
                    $producto['precio_local_user'] = $producto['precio_local'];
                    $producto['precio_local_user_mask'] = $producto['precio_local_mask'];
                }
            }else{
                $producto['precio_local_user'] = $producto['precio_usd'];
                $producto['precio_local_user_mask'] = $producto['precio_usd_mask'];
                $producto['moneda_local_user'] = 'USD';
            }
            $producto['oferta'] = floatval($producto['oferta']);
            $producto['porcentaje_oferta'] = floatval($producto['porcentaje_oferta']);
            $producto['exposicion'] = floatval($producto['exposicion']);
            $producto['cantidad_exposicion'] = floatval($producto['cantidad_exposicion']);
            $producto['envio'] = floatval($producto['envio']);
            $producto['pais'] = floatval($producto['pais']);
            $producto['departamento'] = floatval($producto['departamento']);
            $producto['latitud'] = floatval($producto['latitud']);
            $producto['longitud'] = floatval($producto['longitud']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['cantidad_vendidas'] = floatval($producto['cantidad_vendidas']);
            if(isset($producto['calificacion'])) $producto['calificacion'] = $this->truncNumber(floatval($producto['calificacion']), 2);

            if(isset($producto['favorito']) && $producto['favorito'] > 0) $producto['favorito'] = true;
            if(!isset($producto['favorito']) || $producto['favorito'] == 0) $producto['favorito'] = false;

            $producto['precio_descuento_usd'] = $producto['precio_usd'];
            $producto['precio_descuento_usd_mask'] = $this->maskNumber($producto['precio_descuento_usd'], 2);
            $producto['precio_descuento_local'] = $producto['precio_local'];
            $producto['precio_descuento_local_mask'] = $this->maskNumber($producto['precio_descuento_local'], 2);
            $producto['precio_descuento_local_user'] = $producto['precio_local_user'];
            $producto['precio_descuento_local_user_mask'] = $this->maskNumber($producto['precio_descuento_local_user'], 2);

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);

                $producto['precio_descuento_local_user'] = $producto['precio_local_user'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local_user'] = $producto['precio_local_user'] - $producto['precio_descuento_local_user'];
                $producto['precio_descuento_local_user']= floatval($this->truncNumber($producto['precio_descuento_local_user'], 2));
                $producto['precio_descuento_local_user_mask']= $this->maskNumber($producto['precio_descuento_local_user'], 2);
            }

            $producto['precio_nasbiblue'] = floatval($this->truncNumber(($producto['precio_descuento_usd'] / $precios['EBG']), 6));
            $producto['precio_nasbiblue_mask'] = $this->maskNumber($producto['precio_nasbiblue'], 6);
            $producto['precio_nasbiblue_moneda'] = 'Nasbiblue';
            $producto['precio_nasbigold'] = floatval($this->truncNumber(($producto['precio_descuento_usd'] / $precios['BTC']), 6));
            $producto['precio_nasbigold_mask'] = $this->maskNumber($producto['precio_nasbigold'], 6);
            $producto['precio_nasbigold_moneda'] = 'Nasbigold';

            $productos[$x] = $producto;
        }
        unset($nodo);
        return $productos;
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
}
?>