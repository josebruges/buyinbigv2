<?php
require 'nasbifunciones.php';
class Producto extends Conexion
{
    public function home($data){
        if(!isset($data) || !isset($data['pais']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        $select_precio = $this->selectMonedaLocalUser($data);
        
        $where = "p.estado = 1";
        $limithome = "LIMIT 10";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null; 
        if($data['tipo'] == 1){ // destacados
            $where .= " AND exposicion <> 1";
            $order .= ", exposicion DESC";
        }
        if($data['tipo'] == 2){ // nuevos
            $where .= " AND condicion_producto = 1";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !isset($data['departamento'])){
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
        }
        if(isset($data['pais']) && isset($data['departamento'])){
            $pais = addslashes($data['pais']);
            $departamento = addslashes($data['departamento']);
            $where .= " AND pais = '$pais' AND departamento = $departamento";
        }

        $where .= " AND p.id NOT IN (SELECT ps.id_producto FROM productos_subastas ps)";
        parent::conectar();
        $selecthome = "SELECT p.*, p.precio AS precio_local $select_precio FROM productos p WHERE $where $order $limithome";

        // echo($selecthome);

        $productos = parent::consultaTodo($selecthome);
        parent::cerrar();
        if(count($productos) <= 0) return array('status' => 'fail', 'message'=> 'no se encontraron productos', 'cantidad'=> 0, 'data' => null);
         
        $productos = $this->mapProductos($productos);
        return array('status' => 'success', 'message'=> 'productos', 'cantidad'=> count($productos), 'data' => $productos);
    }

    public function filtrosProductos($data){
        if(!isset($data) || !isset($data['pais'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);

        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        // echo("desde ".$desde."hasta".$hasta);
        $where = "p.estado = 1";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null; 
        if(isset($data['exposicion']) && !empty($data['exposicion'])){ // destacado
            $data['exposicion'] = addslashes($data['exposicion']);
            $where .= " AND exposicion = '$data[exposicion]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['garantia']) && $data['garantia'] != ''){ // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }
        // if(isset($data['pais']) && !empty($data['pais']) && isset($data['departamento']) && !empty($data['departamento'])){ // pais y departamento
        //     $pais = addslashes($data['pais']);
        //     $departamento = addslashes($data['departamento']);
        //     $where .= " AND pais = '$pais' AND departamento = $departamento";
        //     $order .= ", exposicion DESC";
        // }
        if( isset($data['pais']) && !empty($data['pais']) ){ // pais y departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){// busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);

            $where .= " AND titulo LIKE '%$data[producto_nombre]%' ";
            // $where .= " AND MATCH(keywords) AGAINST('$data[producto_nombre]' IN NATURAL LANGUAGE MODE)";
            $order .= ", exposicion DESC";
        }
        if(isset($data['empresa']) && !empty($data['empresa'])){ // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND empresa = '1' AND uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['envio']) && !empty($data['envio'])){ // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])){ // ordenar precio
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order = "ORDER BY precio_usd $data[ordenamiento], exposicion DESC, fecha_creacion DESC";
            if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC"; // mas vendidos
        }
        if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){ // mas vendidos
            // $data['envio'] = addslashes($data['envio']);
            // $where .= " AND cantidad_vendidas > '0'";
            $order = "ORDER BY cantidad_vendidas DESC";
            // if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC";// ordenar precio
        }

        if( !isset($data['subastas']) ){ // Código nuevo para el filtro-producto subastas
            $where .= "  AND p.id NOT IN (SELECT ps.id_producto FROM productos_subastas ps) ";
        }
        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT p.*, p.precio AS precio_local $select_precio
                FROM productos p 
                JOIN (SELECT @row_number := 0) r 
                WHERE $where 
                $order
                )as datos 
            $order
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";

        // echo("---> " . $selecthome);

        $productos = parent::consultaTodo($selecthome);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        parent::cerrar();
        $productos = $this->mapProductos($productos);

        parent::conectar();
        $selecttodos = "SELECT COUNT(p.id) AS contar FROM productos p WHERE $where $order;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        parent::cerrar();

        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        
        return array(
            'status' => 'success',
            'message'=> 'productos',
            'pagina'=> $pagina,
            'total_paginas'=> $totalpaginas,
            'productos' => count($productos),
            'total_productos' => $todoslosproductos,
            'data' => $productos
        );
    }

    public function productoId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);

        $subfavorito = "";
        if(isset($data['uid']) && isset($data['empresa'])){
            $fecha = intval(microtime(true)*1000);
            $data['fecha'] = $fecha;
            $this->validarHistorial($data);
            $subfavorito = ", (SELECT COUNT(f.id) as contar_favorito FROM favoritos f WHERE f.id_producto = p.id AND f.uid = '$data[uid]' AND f.empresa = '$data[empresa]' AND f.estado = 1) AS favorito";
        }

        parent::conectar();
        $selectxproducto = 
        "SELECT
            p.*,
            p.precio AS precio_local
            $select_precio,
            (
                SELECT
                    AVG(cv.promedio) as general_prom
                FROM calificacion_vendedor cv
                WHERE cv.id_producto = p.id
            ) AS calificacion
             $subfavorito 
        FROM productos p
        WHERE p.id = '$data[id]' AND p.estado = '1'";

        $producto = parent::consultaTodo($selectxproducto);
        parent::cerrar();
        if(count($producto) <= 0) return array('status' => 'fail', 'message'=> 'no producto', 'data' => null, 'query' => $selectxproducto);
        
        $producto = $this->mapProductos($producto)[0];
        if ($producto['cantidad'] <= 0 ) {
            return array(
                'status'     => 'errorStock',
                'message'    => 'producto - v1',
                'data'       => $producto,
                'disponible' => $producto['cantidad'],
                'vendido'    => $producto['cantidad_vendidas']
            );
        }else{
            return array(
                'status'     => 'success',
                'message'    => 'producto - v2',
                'data'       => $producto,
                'disponible' => $producto['cantidad'],
                'vendido'    => $producto['cantidad_vendidas']
            );
        }
        // OLD
        // if ($producto['cantidad'] <= $producto['cantidad_vendidas']) {
        //     return array(
        //         'status'     => 'errorStock',
        //         'message'    => 'producto - v1',
        //         'data'       => $producto,
        //         'disponible' => $producto['cantidad'],
        //         'vendido'    => $producto['cantidad_vendidas']
        //     );
        // }else{
        //     return array(
        //         'status'     => 'success',
        //         'message'    => 'producto - v2',
        //         'data'       => $producto,
        //         'disponible' => $producto['cantidad'],
        //         'vendido'    => $producto['cantidad_vendidas']
        //     );
        // }
    }

    public function productoIdColoresTallas(Array $data){
        // esta funcion recibira el parametro id_producto, el cual sera el que haga referencia
        // a la tabla detalle_producto_colores_tallas 
        parent::conectar();
        $selectxproducto = "SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
        pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
        pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
        FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
        JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE id_producto = '$data[id_producto]';";
        $producto_colores_tallas = parent::consultaTodo($selectxproducto);
        parent::cerrar();

        $array_respuesta = array();

        if(count($producto_colores_tallas) <= 0){
            return array('status' => 'fail', 'message'=> 'no se encontraron registros');
        }
         
        foreach($producto_colores_tallas as $key => $value){
            if(!isset($array_respuesta[$value['id_tallas']])){
                $array_respuesta[$value['id_tallas']] = array($value);
            }else{
                array_push($array_respuesta[$value['id_tallas']],$value);
            }
        }
        return array('status' => 'success', 'message'=> 'colores categorizados por tallas', 'data' => $array_respuesta);
    }

    public function similaresId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxpsimilares = "SELECT p.*, p.precio AS precio_local
        FROM productos p
        WHERE p.categoria = (SELECT categoria FROM productos p WHERE p.id = '$data[id]') AND p.id <> '$data[id]' AND p.estado = '1' 
        ORDER BY p.ultima_venta DESC 
        LIMIT 5";
        $similares = parent::consultaTodo($selectxpsimilares);
        parent::cerrar();
        if(count($similares) <= 0) return array('status' => 'fail', 'message'=> 'no similares', 'data' => null);
        
        $similares = $this->mapProductos($similares);
        return array('status' => 'success', 'message'=> 'similares', 'data' => $similares);
    }

    public function productosUserId(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['pais'])) {
            return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        }
        $select_precio = $this->selectMonedaLocalUser($data);

        $extraCondicion = "";
        if ( isset( $data['id_producto'] ) ) {
            $extraCondicion = " AND p.id <> " . $data['id_producto'];
        }

        parent::conectar();
        $selectxpsimilares = "SELECT p.*, p.precio AS precio_local $select_precio
        FROM productos p
        WHERE p.uid = '$data[id]' AND p.tipoSubasta = 0 AND p.id <> '$data[id]' $extraCondicion AND p.estado = '1' AND p.pais = '$data[pais]'
        ORDER BY p.ultima_venta DESC 
        LIMIT 10";

        // echo($selectxpsimilares);

        $similares = parent::consultaTodo($selectxpsimilares);
        parent::cerrar();
        if(count($similares) <= 0) return array('status' => 'fail', 'message'=> 'no mas productos vendedor', 'data' => null);
        
        $similares = $this->mapProductos($similares);
        return array('status' => 'success', 'message'=> 'mas productos vendedor', 'data' => $similares);
    }

    public function fotosproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

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

    public function pqrproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxpqr = "SELECT ppqr.*
        FROM productos_pqr ppqr
        WHERE ppqr.id_producto = '$data[id]' AND ppqr.estado = '1'
        ORDER BY fecha_actualizacion DESC
        LIMIT 10";
        $pqr = parent::consultaTodo($selectxpqr, false);
        parent::cerrar();
        if(count($pqr) <= 0) return array('status' => 'fail', 'message'=> 'no preguntas y respuestas prodcuto', 'data' => null);
        $pqr = $this->mapProductosPQR($pqr);
        return array('status' => 'success', 'message'=> 'preguntas y respuestas prodcuto', 'data' => $pqr);
    }

    public function preguntar(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['pregunta'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        // $data['id'] = $data['id_producto'];
        $producto = $this->productoId($data);
        if($producto['status'] != 'success') return $producto;
        $producto = $producto['data'];

        if($producto['uid'] == $data['uid'] && $producto['empresa'] == $data['empresa']) return array('status' => 'fail', 'message'=> 'no puedes realizar preguntas a tu mismo producto', 'data' => null);

        $data['pregunta'] = addslashes($data['pregunta']);
        $fecha = intval(microtime(true)*1000);

        parent::conectar();
        $insertarxpqr = "INSERT INTO productos_pqr
        (
            id_producto,
            pregunta,
            uid_pregunta,
            empresa_pregunta,
            uid_respuesta,
            empresa_respuesta,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '$data[pregunta]',
            '$data[uid]',
            '$data[empresa]',
            '$producto[uid]',
            '$producto[empresa]',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertar = parent::query($insertarxpqr, false);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'no se pudo realizar la prepgunta', 'data' => null);

        $tipo_subasta = $producto["tipoSubasta"];
        $url = "producto.php?name=$producto[titulo]&uid=$producto[id]";
        // 0 tradiconal, del 1 al 5 subasta premium y el 6 subasta normal 
        if(intval($tipo_subasta) != 0){ 
            $url = "producto-nasbi-descuento.php?name=$producto[titulo]&uid=$producto[id]&tipo=$tipo_subasta";
        }
        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $producto['uid'],
            'empresa' => $producto['empresa'],
            'text' => 'Han realizado una pregunta al producto '.$producto['titulo'],
            'es' => 'Han realizado una pregunta al producto '.$producto['titulo'],
            'en' => 'They have made a question to the product '.$producto['titulo'],
            'keyjson' => '',
            'url' => $url
        ]);
        unset($notificacion);
        $this->envio_correo_pregunta_($data,  $producto);
      
        return array('status' => 'success', 'message'=> 'pregunta realizada', 'data' => $insertar);
    }

    public function responder(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['id_pregunta']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['respuesta'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        // $data['id'] = $data['id_producto'];
        $producto = $this->productoId($data);
        if($producto['status'] != 'success') return $producto;
        $producto = $producto['data'];

        if($producto['uid'] != $data['uid'] || $producto['empresa'] != $data['empresa']) return array('status' => 'fail', 'message'=> 'esta pregunta no te pertenece', 'data' => null);
        
        $data['respuesta'] = addslashes($data['respuesta']);
        $fecha = intval(microtime(true)*1000);
        
        parent::conectar();
        $updatexpqr = "UPDATE productos_pqr
        SET 
            respuesta = '$data[respuesta]',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id_pregunta]' AND id_producto = '$data[id]' AND estado = '1'";
        $update = parent::query($updatexpqr, false);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'no se pudo realizar la respuesta', 'data' => null);

        $pregunta = $this->preguntaId($data); // Esta es una función que trae toda la data de la pregunta.
        $pregunta = $pregunta['data'];

        
        // Datos del usuario que realiza la pregunta.
        $usuario_pregunta = $this->datosUserGeneral([
            'uid' => $pregunta['uid_pregunta'],
            'empresa' => $pregunta['empresa_pregunta']
        ]);
        
        // Datos del usuario que realiza la respuesta.
        $usuario_respuesta = $this->datosUserGeneral([
            'uid' => $pregunta['uid_respuesta'],
            'empresa' => $pregunta['empresa_respuesta']
        ]);

        // $producto: Datos del producto.
        

       $this->htmlEmailrespuesta_pregunta($usuario_pregunta["data"],$usuario_respuesta["data"],$data); 


        $tipo_subasta = $producto["tipoSubasta"];
        $url = "producto.php?name=$producto[titulo]&uid=$producto[id]";
        // 0 tradiconal, del 1 al 5 subasta premium y el 6 subasta normal 
        if(intval($tipo_subasta) != 0){ 
            $url = "producto-nasbi-descuento.php?name=$producto[titulo]&uid=$producto[id]&tipo=$tipo_subasta";
        }
        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $pregunta['uid_pregunta'],
            'empresa' => $pregunta['empresa_pregunta'],
            'text' => 'Han respondido a tu pregunta del producto '.$producto['titulo'],
            'es' => 'Han respondido a tu pregunta del producto '.$producto['titulo'],
            'en' => 'They have answered your question about the product '.$producto['titulo'],
            'keyjson' => '',
            'url' => $url
        ]);
        unset($notificacion);
        
        return array('status' => 'success', 'message'=> 'respuesta realizada', 'data' => $update);
    }

    public function calificacionesproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxcalificaciones = "SELECT cv.id, cv.uid, cv.empresa, cv.promedio, cv.fecha_actualizacion, cv.descripcion
        FROM calificacion_vendedor cv
        WHERE cv.id_producto = '$data[id]'
        ORDER BY fecha_actualizacion DESC
        LIMIT 10";
        $calificaciones = parent::consultaTodo($selectxcalificaciones, false);
        parent::cerrar();
        if(count($calificaciones) <= 0) return array('status' => 'fail', 'message'=> 'no calificaciones prodcuto', 'data' => null);
        $calificaciones = $this->mapProductosCalificacion($calificaciones);
        
        return array('status' => 'success', 'message'=> 'calificaciones prodcuto', 'data' => $calificaciones);
    }

    public function bannerHome(Array $data)
    {
        if(!isset($data) || !isset($data['idioma']) || !isset($data['tipo']) || !isset($data['iso_code_2'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $idioma = strtoupper($data['idioma']);
        parent::conectar();
        $selectxbanner = "SELECT b.* 
        FROM banner b
        WHERE b.idioma = '$idioma' AND tipo = '$data[tipo]' AND iso_code_2 = '$data[iso_code_2]' AND estado = 1 AND estado = 1
        ORDER BY fecha_actualizacion DESC";
        $banner = parent::consultaTodo($selectxbanner, false);
        parent::cerrar();


        $selectxbanner_aux = "SELECT b.* 
        FROM banner b
        WHERE b.idioma = '$idioma' AND b.tipo = '$data[tipo]' AND b.estado = 1 AND b.estado = 1
        ORDER BY b.fecha_actualizacion DESC";

        if ( count($banner) <= 0 ) {
            parent::conectar();
            $selectxbanner = "SELECT b.* 
            FROM banner b
            WHERE b.idioma = '$idioma' AND tipo = '$data[tipo]' AND estado = 1 AND estado = 1
            ORDER BY fecha_actualizacion DESC";
            $banner = parent::consultaTodo($selectxbanner, false);
            parent::cerrar();

            if ( count($banner) <= 0 ) {
                return array('status' => 'fail', 'message'=> 'no banner home', 'data' => null);
            }else{
                return array('status' => 'success', 'message'=> 'banner home defaults', 'data' => $banner);
            }
        }else{
            return array('status' => 'success', 'message'=> 'banner home', 'data' => $banner);
        }
    }

    public function favoritosUser(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);

        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
      
        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT p.*, p.precio AS precio_local $select_precio, f.fecha_actualizacion AS fecha_actualizacion_favoritos
                FROM productos p 
                INNER JOIN favoritos f ON p.id = f.id_producto
                JOIN (SELECT @row_number := 0) r 
                WHERE p.estado = 1 AND f.uid = '$data[uid]' AND f.empresa = '$data[empresa]' AND f.estado = 1
                ORDER BY fecha_actualizacion_favoritos DESC
                )as datos 
            ORDER BY fecha_actualizacion_favoritos DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $productos = parent::consultaTodo($selecthome);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        parent::cerrar();
        $productos = $this->mapProductos($productos);
        parent::conectar();

        $selecttodos = "SELECT COUNT(f.id) AS contar 
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

    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2_money'])){
            if($data['iso_code_2_money'] == 'US')  $select_precio = ", (1*p.precio_usd) AS precio_local_user, IF(1 < 2, 'USD', '') AS moneda_local_user";

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

    function validarHistorial(Array $data)
    {
        $historial = $this->verHistorial($data);
        if($historial['status'] == 'success') {
            $historial = $historial['data'];
            $historial['fecha'] = $data['fecha'];
            return $this->actualizaHistorial($historial);
        }

        return $this->insertarHistorial($data);
    }

    function verHistorial(Array $data)
    {
        parent::conectar();
        $selectxhistorial = "SELECT hp.* FROM hitstorial_productos hp WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id_producto = '$data[id]'";
        $historial = parent::consultaTodo($selectxhistorial);
        parent::cerrar();
        if(count($historial) <= 0) return array('status' => 'fail', 'message'=> 'no se encontraron historial', 'data' => null);
         
        return array('status' => 'success', 'message'=> 'historial', 'data' => $historial[0]);
    }

    function actualizaHistorial(Array $data)
    {
        $data['visitas'] = floatval($data['visitas'] + 1);
        parent::conectar();
        $updatexhistorial = "UPDATE hitstorial_productos
        SET
            estado = 1,
            visitas = '$data[visitas]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]' AND id_producto = '$data[id_producto]'";
        $historial = parent::query($updatexhistorial);
        parent::cerrar();
        if(!$historial) return array('status' => 'fail', 'message'=> 'no se encontraron historial', 'data' => null);
         
        return array('status' => 'success', 'message'=> 'historial', 'data' => $historial);
    }

    function insertarHistorial(Array $data)
    {
        parent::conectar();
        $insertarxpqr = "INSERT INTO hitstorial_productos
        (
            uid,
            empresa,
            id_producto,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            visitas
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[id]',
            '1',
            '$data[fecha]',
            '$data[fecha]',
            '1'
        );";
        $insertar = parent::query($insertarxpqr, false);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'guardado en el historial', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'pregunta realizada', 'data' => $insertar);
    }

    function preguntaId(Array $data)
    {
        // Vemos que consulta la tabla de preguntas y retorna la que aparece en la posición número cero
        parent::conectar();
        $selectxpqr = "SELECT ppqr.*
        FROM productos_pqr ppqr
        WHERE ppqr.id = '$data[id_pregunta]' AND ppqr.estado = '1'
        ORDER BY fecha_actualizacion DESC
        LIMIT 10";
        $pqr = parent::consultaTodo($selectxpqr, false);
        parent::cerrar();
        if(count($pqr) <= 0) return array('status' => 'fail', 'message'=> 'no preguntas y respuestas prodcuto', 'data' => null);
        $pqr = $this->mapProductosPQR($pqr)[0];
        return array('status' => 'success', 'message'=> 'preguntas y respuestas prodcuto', 'data' => $pqr);
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

            $resultTallasColores = $this->productoIdColoresTallas(['id_producto' => $producto['id']]);
            $producto['detalle_colores_tallas'] = null;
            if( $resultTallasColores['status'] == "success"){
            	$producto['detalle_colores_tallas'] = $resultTallasColores['data'];
            }
            $productos[$x] = $producto;
        }
        unset($nodo);
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

    function mapProductosPQR(Array $pregutas)
    {
        // y de alli veo que me da el id del producto la empresa del que hizo la pregun
        foreach ($pregutas as $x => $pqr) {
            $pqr['id'] = floatval($pqr['id']);
            $pqr['id_producto'] = floatval($pqr['id_producto']);
            
            $pqr['uid_pregunta'] = floatval($pqr['uid_pregunta']); // UID del que pregunto
            $pqr['empresa_pregunta'] = floatval($pqr['empresa_pregunta']); // EMPRESA del que pregunto

            $pqr['uid_respuesta'] = floatval($pqr['uid_respuesta']); // UID del dueño del articulo
            $pqr['empresa_respuesta'] = floatval($pqr['empresa_respuesta']); // EMPRESA del dueño del articulo

            $pqr['estado'] = floatval($pqr['estado']);
            $pqr['fecha_creacion'] = floatval($pqr['fecha_creacion']);
            $pqr['fecha_actualizacion'] = floatval($pqr['fecha_actualizacion']);
            $pregutas[$x] = $pqr;
        }

        return $pregutas;
    }

    function mapProductosCalificacion(Array $calificaciones)
    {
        foreach ($calificaciones as $x => $cal) {
            $cal['id'] = floatval($cal['id']);
            $cal['uid'] = floatval($cal['uid']);
            $cal['empresa'] = floatval($cal['empresa']);
            $cal['promedio'] = floatval($cal['promedio']);
            $calificaciones[$x] = $cal;
        }

        return $calificaciones;
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


    function htmlEmailrespuesta_pregunta(Array $usuario_pregunta,Array $usuario_respuesta,Array $data ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$usuario_pregunta['idioma'].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo1.html");
         $producto=$this->get_product_por_id([
         'uid'   => $usuario_respuesta["uid"],
         'id' => $data["id"],
         'empresa'  => $usuario_respuesta["empresa"] ]); 
        
         
         
        $html = str_replace("{{nombre_producto}}", $producto[0]["titulo"], $html);
        $html = str_replace("{{trans164_brand}}", $json->trans164_brand, $html);
        $html = str_replace("{{nombre_usuario}}",$usuario_pregunta['nombre'], $html);
        $html = str_replace("{{trans163}}", $json->trans163, $html);
        $html = str_replace("{{nombre_vendedor}}", $usuario_respuesta['nombre'], $html);
        $html = str_replace("{{trans165}}", $json->trans165, $html);
        $html = str_replace("{{trans167}}", $json->trans167, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$usuario_pregunta["uid"]."&act=0&em=".$usuario_pregunta["empresa"], $html); 
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}","", $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans166}}",$json->trans166, $html);
        $html = str_replace("{{trans147}}",$json->trans147, $html);
        $html = str_replace("{{lin_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data['id'], $html);
        
       

        $para      = $usuario_pregunta['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans103_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function datosUserGeneral( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos;
    }

    function envio_correo_pregunta_(Array $data, Array $data_producto){
    
            // Datos del usuario que realiza la pregunta.
            $usuario_cliente = $this->datosUserGeneral([
                'uid' => $data['uid'],
                'empresa' => $data['empresa']
            ]);
            
            // Datos del usuario que realiza la respuesta.
            $usuario_vendedor = $this->datosUserGeneral([
                'uid' => $data_producto['uid'],
                'empresa' => $data_producto['empresa']
            ]);
    
            // $producto: Datos del producto.
            $this->htmlEmailpregunta($usuario_cliente["data"],$usuario_vendedor["data"],$data_producto);
    }

    function htmlEmailpregunta(Array $usuario_cliente,Array $usuario_vendedor,Array $data ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$usuario_vendedor['idioma'].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo18nuevapregunta.html");
        
         
        
        $html = str_replace("{{trans72_brand}}", $json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        
        $html = str_replace("{{titulo_producto}}", $data["titulo"], $html);
       
        $html = str_replace("{{trans73}}", $json->trans73, $html);
        $html = str_replace("{{trans75}}", $json->trans75, $html);
        $html = str_replace("{{trans76}}", $json->trans76, $html);
        $html = str_replace("{{trans77}}", $json->trans77, $html);
      
        $html = str_replace("{{nombre_usuario}}",ucfirst($usuario_vendedor['nombre']), $html);
        $html = str_replace("{{trans74}}", $json->trans74, $html);
        $html = str_replace("{{foto_producto_nueva_pregunta_brand}}", $data["foto_portada"], $html);
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data['id'], $html);
        



        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$usuario_vendedor["uid"]."&act=0&em=".$usuario_vendedor["empresa"], $html); 
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}","", $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans166}}",$json->trans166, $html);
        $html = str_replace("{{trans147}}",$json->trans147, $html);
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data['id'], $html);
        
       

        $para      = $usuario_vendedor['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans109_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    public function puedePedirFoto(Array $data)
    {
        if( !isset($data) || !isset($data['uid_cliente']) || !isset($data['empresa_cliente']) || !isset($data['uid_vendedor']) || !isset($data['empresa_vendedor']) || !isset($data['id_producto']) ) {
            return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        }
        if ( ($data['uid_cliente'] == $data['uid_vendedor']) && ($data['empresa_cliente'] == $data['empresa_vendedor'])) {
            return array(
                'status'  => 'failSoyPropietario',
                'message' => 'No puedes pedirte más fotos del articulo cuando tu eres el propietario.',
                'data'    => $data
            );
        }

        parent::conectar();

        $pedir_fotos = parent::consultaTodo("SELECT * FROM buyinbig.pedir_fotos WHERE id_producto = '$data[id_producto]' AND uid_cliente = '$data[uid_cliente]' AND empresa_cliente = '$data[empresa_cliente]';");
        
        parent::cerrar();

        if ( count($pedir_fotos) <= 0 ) {
            return array(
                'status'  => 'success',
                'message' => 'Si puedes solicitar más fotos de este articulo.',
                'data'    => $pedir_fotos
            );
        }else{
            return array(
                'status'  => 'fail',
                'message' => 'Usted ya ha solicitado fotos de este articulo.',
                'data'    => $data
            );
        }
    }
    public function pedirFoto(Array $data)
    {
        if( !isset($data) || !isset($data['uid_cliente']) || !isset($data['empresa_cliente']) || !isset($data['uid_vendedor']) || !isset($data['empresa_vendedor']) || !isset($data['id_producto']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'no data',
                'data' => null
            );
        }

        $result = $this->puedePedirFoto( $data );
        if ( $result['status'] != 'success') {
            return $result;
        }


        $fecha = intval(microtime(true)*1000);

        parent::conectar();

        $producto = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE id = '$data[id_producto]';");
        if ( count($producto) <= 0 ) {
            // Pailas no existe el producto.
            return array(
                'status'  => 'errorNoExiste',
                'message' => 'No se encontró información del producto solicitado.',
                'data'    => $data
            );
        }
        $producto = $producto[0];

        $insertarxpedirxfotos = 
        "INSERT INTO pedir_fotos
        (
            uid_cliente,
            empresa_cliente,

            uid_vendedor,
            empresa_vendedor,

            id_producto,

            fecha_creacion,
            fecha_actualizacion,

            notificado
        )
        VALUES
        (
            '$data[uid_cliente]',
            '$data[empresa_cliente]',

            '$data[uid_vendedor]',
            '$data[empresa_vendedor]',

            '$data[id_producto]',
            
            '$fecha',
            '$fecha',
            
            0
        );";

        $insertar = parent::query($insertarxpedirxfotos, false);
        parent::cerrar();
        if(!$insertar) {
            return array(
                'status' => 'errorInsertarSolicitud',
                'message'=> 'no se pudo insertar la solicitud',
                'data' => $data
            );
        }


        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $data['uid_vendedor'],
            'empresa' => $data['empresa_vendedor'],

            'text' => 'Un nuevo cliente está interesado en ver más imágenes del producto ' . $producto['titulo'],
            'es' => 'Un nuevo cliente está interesado en ver más imágenes del producto ' . $producto['titulo'],
            'en' => 'A new customer is interested in seeing more images of the product ' . $producto['titulo'],
            'keyjson' => '',
            'url' => 'https://nasbi.com/content/producto.php?uid=' . $data['id_producto']
        ]);
        unset($notificacion);
        return array(
            'status' => 'success',
            'message'=> 'solicitud creada.',
            'data' => null
        );
    }



    public function filtrosProductos2($data){
        $join                  = "";
        $bandera_mas_relevante = 0;
        $bandera_mas_vendidos  = 0;
        $ordenamiento_filter   = 0;

        if(!isset($data) || !isset($data['pais'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        $select_precio     = $this->selectMonedaLocalUser($data);
        $campo_adicional   = ""; 
        $group             = ""; 
        $pagina            = floatval($data['pagina']);
        $numpagina         = 9;
        $hasta             = $pagina*$numpagina;
        $desde             = ($hasta-$numpagina)+1;
        $where             = "p.tipoSubasta = 0 AND p.estado = 1 ";
        $order             = "ORDER BY fecha_creacion DESC";
        $order_mas_vendido = ""; 
        $pais              = null; 

        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ 
            // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['garantia']) && $data['garantia'] != ''){ 
            // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ 
            // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){
            // busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);

            $where .= " AND titulo LIKE '%$data[producto_nombre]%' ";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ 
            // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }

        if( isset($data['pais']) && !empty($data['pais']) ){ 
            // pais y departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['empresa']) && !empty($data['empresa'])){ 
            // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND p.empresa = '1' AND p.uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ 
            // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ 
            // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
      
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento']) ){ 
            // ordenar precio
            $ordenamiento_filter=1; 
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order = "ORDER BY precio_usd $data[ordenamiento], exposicion DESC, fecha_creacion DESC";
            if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC"; // mas vendidos
        }
        if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){
            // mas vendidos
            $bandera_mas_vendidos=1; 
            $where .= " AND cantidad_vendidas > 0";
            $order = "ORDER BY cantidad_vendidas DESC";
        }

        if(isset($data['genero']) && !empty($data['genero'])){
            // genero
            $data['genero'] = intval($data['genero']);
            $data['genero'] = addslashes($data['genero']);
            $where .= " AND genero = '$data[genero]'";
            $order .= ", genero DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor']) ){
            // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND (precio-(precio*(porcentaje_oferta/100))) >=  '$data[minimo_valor]' AND  (precio-(precio*(porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precio DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) ){
            // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND (precio-(precio*(porcentaje_oferta/100))) >=  '$data[minimo_valor]'";
            $order .= ", precio DESC";;
        }

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor'])  ){
            // minimo valor & valor mayor 
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND  (precio-(precio*(porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precio DESC";
        }

        if(isset($data['mas_relevante']) && !empty($data['mas_relevante']) && $data['mas_relevante']== 1){
            // para que muestre del que tiene mas visitas a menos los que no muestra es porque no tiene 
            $campo_adicional.= ", max(ht.visitas) as visitas"; 
            $join.= "JOIN  hitstorial_productos ht ON (ht.id_producto = p.id)"; 
            $group.= "GROUP BY p.id"; 
            if($bandera_mas_vendidos==0){
                $order = str_replace("fecha_creacion","visitas",$order);
            }else{
                $order .= ", visitas DESC";
            }
            $bandera_mas_relevante=1; 
           }

        if(isset($data['envio']) && !empty($data['envio'])){
            // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }

        if($ordenamiento_filter==1 && $bandera_mas_vendidos==1 ){
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order_mas_vendido= "ORDER BY precio_oferta_pag $data[ordenamiento]"; 
        }

        parent::conectar();

        $selecthome = 
        "SELECT *, (precio-(precio*(porcentaje_oferta/100))) as 'precio_oferta_pag'FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT 
                    p.*,
                    p.precio AS precio_local
                    $select_precio
                    $campo_adicional
                FROM productos p 
                JOIN (SELECT @row_number := 0) r 
                $join
                WHERE $where 
                $group
                $order
                )as datos 
            $order
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta' $order_mas_vendido";
        /* echo $selecthome; */

        $selectxhomexvaluexmax = 
        "SELECT 
            *,
            (@row_number:=@row_number+1) AS num 
        FROM(
            SELECT 
                MAX(p.precio) AS MAX_PRICE_PRODUCTO,
                AVG(p.precio) AS MAX_PRICE_PRODUCTO_PROM,
                p.moneda_local AS MAX_PRICE_PRODUCTO_MONEDA
            FROM productos p 
            JOIN (
                SELECT @row_number := 0) r 
                $join
                WHERE $where 
        )as datos";

       

     //var_dump($selecthome); 
        $productos = parent::consultaTodo($selecthome);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        
        $select_home_value_max        = parent::consultaTodo($selectxhomexvaluexmax);
        
        $productos_precio_max         = 0;
        $productos_precio_max_mask    = "";

        $productos_precio_prom         = 0;
        $productos_precio_prom_mask    = "";
        
        $productos_precio_max_symbol  = "";

        if( count( $select_home_value_max ) > 0 ){
            $productos_precio_prom        = floatval($this->truncNumber($select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO_PROM"], 2));
            $productos_precio_prom_mask   = $this->maskNumber($productos_precio_prom, 2);

            $productos_precio_max        = floatval($this->truncNumber($select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO"], 2));
            $productos_precio_max_mask   = $this->maskNumber($productos_precio_max, 2);

            $productos_precio_max_symbol = $select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO_MONEDA"];
        }

        parent::cerrar();
        $productos = $this->mapProductos($productos);

        parent::conectar();
        if($bandera_mas_relevante==0){
            $selecttodos       = "SELECT COUNT(p.id) AS contar $campo_adicional FROM productos p $join WHERE $where  $group $order;";
        }else{
           $selecttodos       = "SELECT count(pro.identificador_producto) as contar from (SELECT COUNT(p.id) AS contar $campo_adicional, p.id as identificador_producto FROM productos p $join WHERE $where  $group $order) as pro";
        }
       
        $todoslosproductos = parent::consultaTodo($selecttodos);
        parent::cerrar();
       // var_dump($selecttodos); 

        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas      = $todoslosproductos / $numpagina;
        $totalpaginas      = ceil($totalpaginas);
        
        return array(
            
            'status'                      => 'success',
            'message'                     => 'productos',
            'pagina'                      => $pagina,
            'total_paginas'               => $totalpaginas,
            'productos'                   => count($productos),
            'total_productos'             => $todoslosproductos,
            'data'                        => $productos,
            
            'price_max_value'             => $productos_precio_max,
            'price_max_value_mask'        => $productos_precio_max_mask,

            'price_max_value_prom'        => $productos_precio_prom,
            'price_max_value_prom_mask'   => $productos_precio_prom_mask,

            'price_max_value_symbol'      => $productos_precio_max_symbol
        );
    }


    public function traer_listado_empresa_oficiales_filtro($data){
        $join="";
        $campo_adicional=""; 
        $group=""; 
        $bandera_mas_vendidos= 0;
        if(!isset($data) || !isset($data['pais'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $select_precio = $this->selectMonedaLocalUser($data);

        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        // echo("desde ".$desde."hasta".$hasta);
        $where = "p.estado = 1";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null; 

        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){// busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);

            $where .= " AND titulo LIKE '%$data[producto_nombre]%' ";
            // $where .= " AND MATCH(keywords) AGAINST('$data[producto_nombre]' IN NATURAL LANGUAGE MODE)";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND p.pais = '$pais'";
            $order .= ", exposicion DESC";
        }

        if( isset($data['pais']) && !empty($data['pais']) ){ // pais y departamento
            $pais = addslashes($data['pais']);
            $where .= " AND p.pais = '$pais'";
            $order .= ", exposicion DESC";
        }
    
        if(isset($data['empresa']) && !empty($data['empresa'])){ // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND p.empresa = '1' AND p.uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
      
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento']) ){ // ordenar precio
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order = "ORDER BY precio_usd $data[ordenamiento], exposicion DESC, fecha_creacion DESC";
            if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC"; // mas vendidos
        }
        if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){ // mas vendidos
            // $data['envio'] = addslashes($data['envio']);
            // $where .= " AND cantidad_vendidas > '0'";
            $order = "ORDER BY cantidad_vendidas DESC";
            $bandera_mas_vendidos= 1;
            // if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC";// ordenar precio
        }


        if(isset($data['genero']) && !empty($data['genero'])){ // genero
            $data['genero'] = intval($data['genero']);
            $data['genero'] = addslashes($data['genero']);
            $where .= " AND genero = '$data[genero]'";
            $order .= ", genero DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor']) ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND (precio-(precio*(porcentaje_oferta/100))) >=  '$data[minimo_valor]' AND  (precio-(precio*(porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precio DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND (precio-(precio*(porcentaje_oferta/100))) >=  '$data[minimo_valor]'";
            $order .= ", precio DESC";
        }

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor'])  ){ // minimo valor & valor mayor 
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND  (precio-(precio*(porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precio DESC";
        }
        
        if(isset($data['garantia']) && $data['garantia'] != ''){ // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }

      
        if(isset($data['mas_relevante']) && !empty($data['mas_relevante']) && $data['mas_relevante']== 1){
            // para que muestre del que tiene mas visitas a menos los que no muestra es porque no tiene 
            $campo_adicional.= ", max(ht.visitas) as visitas"; 
            $join.= "JOIN  hitstorial_productos ht ON (ht.id_producto = p.id)"; 
            $group.= "GROUP BY p.id"; 
            if($bandera_mas_vendidos==0){
                $order = str_replace("fecha_creacion","visitas",$order);
            }else{
                $order .= ", visitas DESC";
            }
            $bandera_mas_relevante=1; 
        }
        

         if(isset($data['envio']) && !empty($data['envio'])){ // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }
  

        parent::conectar();

        $selecthome = "
                SELECT DISTINCT e.* $campo_adicional
                FROM productos p 
                $join 
                JOIN empresas e ON (p.uid= e.id)
                WHERE $where AND p.empresa=1 AND (e.pais = '$pais' AND e.estado = 1) AND (3 < (SELECT COUNT(p.uid) FROM productos p WHERE p.uid = e.id AND p.empresa = 1)) AND (e.idioma IS NOT NULL AND e.cargo IS NOT NULL AND e.nit IS NOT NULL AND e.foto_asesor IS NOT NULL AND e.descripcion IS NOT NULL AND e.pais IS NOT NULL AND e.tipo_empresa IS NOT NULL AND e.nombre_empresa IS NOT NULL AND e.telefono IS NOT NULL AND e.razon_social IS NOT NULL AND e.nombre_dueno IS NOT NULL AND e.foto_logo_empresa IS NOT NULL AND e.foto_portada_empresa IS NOT NULL)
                $group
                $order
                ";


      //var_dump($selecthome); 

        $empresas = parent::consultaTodo($selecthome);
        if(count($empresas) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron empresas', 'data' => null);
        }

         parent::cerrar();
         $empresas = $this->mapEmpresaOficial($empresas);
        return array(
            'status' => 'success',
            'message'=> 'empresas oficiales',
            'data' => $empresas
        );
    }



    function mapEmpresaOficial(Array $empresas)
    {
        $empresas_oficiales=[]; 

        foreach ($empresas as $x => $empresa) {
            $empresa_aux=[]; 

            if ( $empresa['descripcion'] == "...") {
                $empresa_aux['descripcion'] = "";
            }
            if ( $empresa['nombre_empresa'] == "...") {
                $empresa_aux['nombre_empresa'] = $empresa['razon_social'];
            }
        
           
            if ( $empresa['foto_logo_empresa'] == "...") {
                $empresa_aux['foto_logo_empresa'] = "";
            }
            if ( $empresa['foto_portada_empresa'] == "...") {
                $empresa_aux['foto_portada_empresa'] = "";
            }
        
            $empresa_aux['id'] = floatval($empresa['id']);
            $empresa_aux['empresa'] = 1;
            $empresa_aux['pais'] = floatval($empresa['pais']);
            $empresa_aux['nombre'] = $empresa['nombre_empresa'];
            $empresa_aux['nombreCompleto'] = $empresa['nombre_empresa'];
            //$empresas[$x] = $empresa;
            array_push($empresas_oficiales, $empresa_aux );
        }

        return $empresas_oficiales;
    }

}
