<?php
require 'nasbifunciones.php';
class Favorito extends Conexion
{
    public function favoritosUser(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);
        $ordenar = "fecha_actualizacion_favoritos DESC";
        if (isset($data['ordenar'])) {
            $ordenar = "precio_local $data[ordenar]";
        }

        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
      
        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT p.*, p.precio AS precio_local $select_precio, f.fecha_actualizacion AS fecha_actualizacion_favoritos, f.id AS id_favorito
                FROM productos p 
                INNER JOIN favoritos f ON p.id = f.id_producto
                JOIN (SELECT @row_number := 0) r 
                WHERE p.estado = 1 AND p.tipoSubasta = 0 AND f.uid = '$data[uid]' AND f.empresa = '$data[empresa]' AND f.estado = 1
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
        $selecttodos = "SELECT COUNT(f.id) AS contar, f.fecha_actualizacion AS fecha_actualizacion_favoritos
        FROM productos p 
        INNER JOIN favoritos f ON p.id = f.id_producto
        WHERE p.estado = 1 AND f.uid = '$data[uid]' AND f.empresa = '$data[empresa]' AND f.estado = 1
        ORDER BY fecha_actualizacion_favoritos DESC;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        
        return array('status' => 'success', 'message'=> 'productos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($productos), 'total_productos' => $todoslosproductos, 'data' => $productos);
    }


    public function agregarFavorito(Array $data)
    {
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $favorito = $this->favoritoId($data);
        
        if($favorito['status'] == 'success') return $this->actualizarFavorito($data);

        return $this->crearFavorito($data);
    }

    function favoritoId(Array $data)
    {
        parent::conectar();
        $selectxfavorito = "SELECT f.* FROM favoritos f WHERE f.id_producto = '$data[id_producto]' AND f.uid = '$data[uid]' AND f.empresa = '$data[empresa]'";
        $favorito = parent::consultaTodo($selectxfavorito);
        parent::cerrar();

        if(count($favorito) <= 0) return array('status' => 'fail', 'message'=> 'no favorito', 'data' => null);

        return array('status' => 'success', 'message'=> 'favorito', 'data' => $favorito[0]);
    }

    function actualizarFavorito(Array $data)
    {
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletexfav = "UPDATE favoritos SET estado = 1, fecha_actualizacion = '$fecha' WHERE id_producto = '$data[id_producto]' AND uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $eliminar = parent::query($deletexfav);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no agregado a favorito', 'data' => null);

        return array('status' => 'success', 'message'=> 'agregado a favorito', 'data' => null);
    }

    function crearFavorito(Array $data)
    {
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $insterarxfav = "INSERT INTO favoritos
            (
                uid,
                empresa,
                id_producto,
                estado,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
                '$data[uid]',
                '$data[empresa]',
                '$data[id_producto]',
                '1',
                '$fecha',
                '$fecha'
            );
        ";
        $insertar = parent::query($insterarxfav);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'no agregado a favorito', 'data' => null);

        return array('status' => 'success', 'message'=> 'agregado a favorito', 'data' => null);
    }


    public function eliminarFavorito(Array $data)
    {
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $whereid = "id_producto = '$data[id_producto]' AND";
        if($data['id_producto'] == 'all') $whereid = "";

        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletexhistorial = "UPDATE favoritos SET estado = 0, fecha_actualizacion = '$fecha' WHERE $whereid uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $eliminar = parent::query($deletexhistorial);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no se elimino el favorito', 'data' => null);

        return array('status' => 'success', 'message'=> 'favorito eliminado', 'data' => null);
    }

    function countFavoritos(Array $data) {
        if(!isset($data) || !isset($data['uid'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        parent::conectar();
        $consultar = "select count(f.id) cantidad from favoritos f INNER JOIN productos p ON p.id = f.id_producto where f.uid = $data[uid] AND f.estado = 1 AND p.estado = 1 AND p.tipoSubasta = 0";
        $lista = parent::consultarArreglo($consultar);
        return array('status' => 'success', 'message'=> 'cantidad de favoritos', 'cantidad' => $lista['cantidad']);
    }

    function verificarFavoritos(Array $data) {
        if(!isset($data) || !isset($data['uid']) || !isset($data['id_producto'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        parent::conectar();
        $consultar = "select * from favoritos where uid = $data[uid] AND estado = 1 AND id_producto = $data[id_producto]";
        $lista = parent::consultarArreglo($consultar);
        $resul;
        if (count($lista) > 0) {
            $resul = true;
        } else {
            $resul = false;
        }
        return array('status' => 'success', 'message'=> 'Verificar si el producto en mis favoritos', 'data' => array('verificar' => $resul));

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
            
            $producto['id'] = floatval($producto['id']);
            $producto['id_favorito'] = floatval($producto['id_favorito']);
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