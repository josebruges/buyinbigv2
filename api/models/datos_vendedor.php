<?php
require 'conexion.php';
class DatosVendedor extends Conexion
{
    private $semaforoNumeroVentas = 10;
    private $array_calificacion = array(
        5 => array(
            'nombre' => 'Excelente'
        ),
        4 => array(
            'nombre' => 'Muy bueno'
        ),
        3 => array(
            'nombre' => 'Bueno'
        ),
        2 => array(
            'nombre' => 'Regular'
        ),
        1 => array(
            'nombre' => 'Malo'
        ),
    );

    public function calificacionGeneralVendedor(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $fecha = intval(microtime(true)*1000);
        $data['fecha_actual'] = $fecha;
        $data['hace_meses'] = 6;//Este es el que valida que sean cada 6 meses
        $usuario = $this->datosUsuario($data);
        if($usuario['status'] == 'fail') return $usuario;
        $usuario = $usuario['data'];

        $direccion = $this->direccionUsuario($data)['data'];
        $promedio = $this->promediosUsuario($data)['data'];
        $escalaUsuario = $this->escalaUsuario($data)['data'];

        $resultExtra = $this->calificacionGeneralVendedorExtra( $data );

        $array = array(
            'usuario'     => $usuario,
            'direccion'   => $direccion,
            'promedio'    => $promedio,
            'escala'      => $escalaUsuario,
            'escalaExtra' => $resultExtra
        );
        
        return array('status' => 'success', 'message'=> 'usuario', 'data' => $array);
    }
    public function calificacionGeneralVendedorTotal(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $fecha = intval(microtime(true)*1000);
        $data['fecha_actual'] = $fecha;
        $data['hace_meses'] = 6;//Este es el que valida que sean cada 6 meses
        $usuario = $this->datosUsuario($data);
        if($usuario['status'] == 'fail') return $usuario;
        $usuario = $usuario['data'];

        $direccion = $this->direccionUsuario($data)['data'];
        $promedio = $this->promediosUsuarioTotal($data)['data'];
        $escalaUsuario = $this->escalaUsuario($data)['data'];

        $array = array(
            'usuario' => $usuario,
            'direccion' => $direccion,
            'promedio' => $promedio,
            'escala' => $escalaUsuario
        );
        
        return array('status' => 'success', 'message'=> 'usuario', 'data' => $array);
    }

    public function calificacionPaginado(Array $data)
    {

        //Paginacion correcta
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $fecha = intval((microtime(true))*1000);
        $unmesmilliseconds = 2592000000;
        $mesesrestar = 1;
        if(isset($data['meses'])) $mesesrestar = $data['meses'];
        $fechalimite = $fecha - ($unmesmilliseconds * $mesesrestar);

        parent::conectar();
	    $selectxcalificaciones = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT cv.*
                FROM calificacion_vendedor cv
                JOIN (SELECT @row_number := 0) r 
                WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]' AND fecha_creacion >= $fechalimite
                ORDER BY fecha_creacion DESC
            ) as datos 
            ORDER BY fecha_creacion DESC
        )AS info 
        WHERE info.num BETWEEN '$desde' and '$hasta'";
        $calificaciones = parent::consultaTodo($selectxcalificaciones);
        if(count($calificaciones) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron calificaciones', 'pagina'=> $pagina, 'total_paginas'=> 0, 'calificaciones' => 0, 'total_calificaciones' => 0, 'data' => null);
        }

        $selecttodos = "SELECT COUNT(cv.id) AS contar 
        FROM calificacion_vendedor cv 
        WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]' AND fecha_creacion >= $fechalimite
        ORDER BY fecha_creacion DESC;";
        $todoslascalificaciones = parent::consultaTodo($selecttodos);
        $todoslascalificaciones = floatval($todoslascalificaciones[0]['contar']);
        $totalpaginas = $todoslascalificaciones/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();

        return array('status' => 'success', 'message'=> 'calificaciones', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'calificaciones' => count($calificaciones), 'total_productos' => $todoslascalificaciones, 'data' => $calificaciones);
    }

    public function resumenUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectxresumen ="
        SELECT

        (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS reclamos FROM productos_transaccion_reportados ptr INNER JOIN productos_transaccion pt ON ptr.id_transaccion = pt.id WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = '10') AS reclamos,

        (SELECT IF(AVG(cv.tiempo_entrega), AVG(cv.tiempo_entrega), 0) AS tiempos_entrega FROM calificacion_vendedor cv WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]') AS tiempos_entrega,

        (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS canceladas FROM productos_transaccion pt WHERE (pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]') AND pt.estado = '2') AS canceladas,

        (SELECT COUNT(p.id) AS publicaciones FROM productos  p WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]') AS publicaciones,

        (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas_a_preparar FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 6 ) AS ventas_preparar,

        (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS compras FROM productos_transaccion  pt WHERE pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]' AND pt.estado = 13 ) AS compras,

        (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13) AS ventas,

        (SELECT COUNT( ps.id ) AS subastas FROM productos_subastas ps INNER JOIN productos  p ON ps.id_producto = p.id WHERE p.uid = '$data[uid]' AND p.empresa= '$data[empresa]' AND p.estado <> 0 AND ps.estado <> 4) AS subastas;";

        // echo($selectxresumen);
        $resumen = parent::consultaTodo($selectxresumen);
        parent::cerrar();
        if(count($resumen) <= 0) return array('status' => 'fail', 'message'=> 'no resumen', 'data' => null);
            
        $fecha = intval(microtime(true)*1000);
        $data['fecha_actual'] = $fecha;
        $data['hace_meses'] = 6;//Este es el que valida que sean cada 6 meses
        
        $escalaUsuario = $this->escalaUsuario($data)['data'];
        $resumen[0]['escalaUsuario'] = $escalaUsuario;
        return array('status' => 'success', 'message'=> 'resumen', 'data' => $resumen[0]);
    }

    function direccionUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectdireccion = "SELECT * FROM direcciones d WHERE d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' AND d.estado = 1 AND activa = 1";
        $direcciones = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($direcciones) <= 0) return array('status' => 'fail', 'message'=> 'no tine direcciones', 'data' => null);
            
        $direcciones = $this->mapDirecciones($direcciones)[0];
        return array('status' => 'success', 'message'=> 'direcciones', 'data' => $direcciones);
    }

    function datosUsuario(Array $data)
    {
        parent::conectar();
        if($data['empresa'] == 0) $selectxusuario = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxusuario = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]'";
        $usuario = parent::consultaTodo($selectxusuario);
        parent::cerrar();
        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'usuario no existe', 'data' => null);
            
        $usuario = $this->mapUsuario($usuario, $data['empresa'])[0];
        return array('status' => 'success', 'message'=> 'usuario', 'data' => $usuario);
    }

    function promediosUsuario(Array $data)
    {
        parent::conectar();
        $fecha = $data['fecha_actual'];
        $micro_date = intval($fecha / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechainicio = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);
        $fechafinal = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        
        // Consulta original de juanito.
        $selectxpromedio = 
        "SELECT
            AVG(cv.promedio) as general_prom, 
            AVG(cv.buena_atencion) as buena_atencion_prom, 
            AVG(cv.tiempo_entrega) as tiempo_entrega_prom, 
            AVG(cv.fidelidad_producto) as fidelidad_producto_prom, 
            AVG(cv.satisfaccion_producto) as satisfaccion_producto_prom, 
            COUNT(cv.id) as cantidad_comentarios, COUNT(IF(cv.promedio >= 4, 1, NULL)) as buenos, 
            COUNT(IF(cv.promedio < 4 && cv.promedio >= 2.5 , 1, NULL)) as regulares, 
            COUNT(IF(cv.promedio < 2.5 ,1, NULL)) as malos, COUNT(IF(cv.fecha_creacion >= $fechainicio, 1, NULL)) as ventas_seis_meses
        FROM calificacion_vendedor cv
        WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]' AND cv.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal';";

        $promedio = parent::consultaTodo($selectxpromedio);
        parent::cerrar();
        if(count($promedio) <= 0) return array('status' => 'fail', 'message'=> 'promedio no existe', 'data' => null);
            
        $promedio = $this->mapPromedios($promedio, $data['empresa'])[0];
        return array('status' => 'fail', 'message'=> 'promedio', 'data' => $promedio);
    }
    function promediosUsuarioTotal(Array $data)
    {
        parent::conectar();
        $fecha = $data['fecha_actual'];
        $micro_date = intval($fecha / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechainicio = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);
        $fechafinal = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        
        // Consulta original de juanito.
        $selectxpromedio = 
        "SELECT
            AVG(cv.promedio) as general_prom, 
            AVG(cv.buena_atencion) as buena_atencion_prom, 
            AVG(cv.tiempo_entrega) as tiempo_entrega_prom, 
            AVG(cv.fidelidad_producto) as fidelidad_producto_prom, 
            AVG(cv.satisfaccion_producto) as satisfaccion_producto_prom, 
            COUNT(cv.id) as cantidad_comentarios, COUNT(IF(cv.promedio >= 4, 1, NULL)) as buenos, 
            COUNT(IF(cv.promedio < 4 && cv.promedio >= 2.5 , 1, NULL)) as regulares, 
            COUNT(IF(cv.promedio < 2.5 ,1, NULL)) as malos, COUNT(cv.uid) as ventas_seis_meses
        FROM calificacion_vendedor cv
        WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]';";


        // echo("--> [ selectxpromedio ]" . $selectxpromedio);

        $promedio = parent::consultaTodo($selectxpromedio);
        parent::cerrar();
        if(count($promedio) <= 0) return array('status' => 'fail', 'message'=> 'promedio no existe', 'data' => null);
            
        $promedio = $this->mapPromedios($promedio, $data['empresa'])[0];
        return array('status' => 'fail', 'message'=> 'promedio', 'data' => $promedio);
    }

    function escalaUsuario(Array $data)
    {

        $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        $array_monedas_local   = $this->filter_by_value_moneda($array_monedas_locales, 'iso_code_2', 'CO');
        $array_monedas_local   = $array_monedas_local[0];

        $array_monedas_local['costo_dolar'] = floatval("" . $array_monedas_local['costo_dolar']);
        $costo_dolar                        = $array_monedas_local['costo_dolar'];
        


        $fecha = $data['fecha_actual'];
        $micro_date = intval($fecha / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechainicio = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);
        $fechafinal = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);
        
        parent::conectar();
        $selectxescala = 
            "SELECT 

            (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND (pt.estado = 13 OR pt.estado = 10)) AS contar,

            (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13) AS ventas_concretadas,

            (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS ventas FROM productos_transaccion  pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 10) AS ventas_noconcretadas,

            (SELECT COUNT( DISTINCT(pt.id_carrito) ) AS reclamos FROM productos_transaccion_reportados ptr INNER JOIN productos_transaccion pt ON ptr.id_transaccion = pt.id WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]') AS reclamos,

            (SELECT AVG((pqr.fecha_actualizacion - pqr.fecha_creacion) / 3600000) as promedio_tiempo_respuesta FROM productos_pqr pqr WHERE pqr.uid_respuesta = pt.uid_vendedor AND pqr.empresa_respuesta = pt.empresa AND pqr.respuesta <> '') tiempo_respuesta,

            (SELECT (100 - (AVG(cv.tiempo_entrega) * (100/5))) as tiempo_entrega FROM calificacion_vendedor cv WHERE cv.uid = pt.uid_vendedor  AND cv.empresa = pt.empresa) retraso_tiempo_entrega,

            (SELECT (AVG(cv.promedio) * (100/5)) as promedio FROM calificacion_vendedor cv WHERE cv.uid = pt.uid_vendedor  AND cv.empresa = pt.empresa) satisfaccion_usuarios,

            SUM(
                CASE
                    WHEN pt.estado = 13
                    THEN pt.precio_usd
                    ELSE 0
                END
            ) as facturacion_usd_OLD,
            SUM(
                IF( pt.tipo = 1 AND pt.estado = 13,
                    (pt.precio + pt.precio_segundo_pago + pt.precio_cupon) / $costo_dolar,
                    pt.precio_comprador / $costo_dolar
                )
            ) as facturacion_usd,


            (
                SELECT COUNT(ptr.id) AS canceladas
                FROM productos_transaccion_rechazada ptr
                INNER JOIN productos_transaccion pt2 ON pt2.id = ptr.id_transaccion
                WHERE pt2.uid_vendedor = pt.uid_vendedor AND pt2.empresa = pt.empresa
            ) as ventas_canceladas
            FROM productos_transaccion pt
            LEFT JOIN productos_transaccion_reportados ptr ON pt.id = ptr.id_transaccion
            WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal';";
        
        
        

        $escala = parent::consultaTodo($selectxescala);
        parent::cerrar();
        if(count($escala) <= 0) return array('status' => 'fail', 'message'=> 'escala no existe', 'data' => null);

        $escala[0]['reclamos']             = floatval( $escala[0]['reclamos'] );
        $escala[0]['reclamos_num']         = floatval( $escala[0]['reclamos'] );
        $escala[0]['contar']               = floatval( $escala[0]['contar'] );
        $escala[0]['ventas_concretadas']   = floatval( $escala[0]['ventas_concretadas'] );
        $escala[0]['ventas_noconcretadas'] = floatval( $escala[0]['ventas_noconcretadas'] );
        $escala[0]['ventas_totales']       = $escala[0]['ventas_concretadas'] + $escala[0]['ventas_noconcretadas'];

        if ( $escala[0]['reclamos'] != 0 ) {
            $escala[0]['reclamos'] = ( $escala[0]['reclamos'] * 100 ) / $escala[0]['ventas_totales'];
        }

        
        $escala = $this->definirEscala($escala);
        return array('status' => 'success', 'message'=> 'escala', 'data' => $escala);
    }

    function definirEscala(Array $escala)
    {
        $escala = $escala[0];
        if($escala['contar'] < 10) return array('escala' => 0, 'escala_descripcion' => 'Sin clasificacion');

        $array_escala = [];

        $reclamos = ($escala['reclamos'] <= 5) ? 5 : (($escala['reclamos'] <= 15) ? 4 : (($escala['reclamos'] <= 25) ? 3 : (($escala['reclamos'] <= 35) ? 2 : 1)));

        array_push($array_escala, $reclamos);

        $tiempo_respuesta = 0;
        if ( $escala['tiempo_respuesta'] < 6 ) {
            $tiempo_respuesta = 5;
        }else if ( $escala['tiempo_respuesta'] >= 6 && $escala['tiempo_respuesta'] < 12) {
            $tiempo_respuesta = 4;
        }else if ( $escala['tiempo_respuesta'] >= 12 && $escala['tiempo_respuesta'] < 24) {
            $tiempo_respuesta = 3;
        }else if ( $escala['tiempo_respuesta'] >= 24 && $escala['tiempo_respuesta'] < 48) {
            $tiempo_respuesta = 2;
        }else if ( $escala['tiempo_respuesta'] >= 48 && $escala['tiempo_respuesta'] < 72) {
            $tiempo_respuesta = 1;
        }else{
            $tiempo_respuesta = 0;
        }
        array_push($array_escala, $tiempo_respuesta);
        $retraso_tiempo_entrega = ($escala['retraso_tiempo_entrega'] <= 10) ? 5 : (($escala['retraso_tiempo_entrega'] <= 15) ? 4 : (($escala['retraso_tiempo_entrega'] <= 20) ? 3 : (($escala['retraso_tiempo_entrega'] <= 25) ? 2 : 1)));

        array_push($array_escala, $retraso_tiempo_entrega);



        $satisfaccion_usuarios = 0;
        if ( $escala['satisfaccion_usuarios'] >= 95 ) {
            $satisfaccion_usuarios = 5;
        }else if ( $escala['satisfaccion_usuarios'] >= 85 && $escala['satisfaccion_usuarios'] < 95) {
            $satisfaccion_usuarios = 4;
        }else if ( $escala['satisfaccion_usuarios'] >= 75 && $escala['satisfaccion_usuarios'] < 85) {
            $satisfaccion_usuarios = 3;
        }else if ( $escala['satisfaccion_usuarios'] >= 65 && $escala['satisfaccion_usuarios'] < 75) {
            $satisfaccion_usuarios = 2;
        }else if ( $escala['satisfaccion_usuarios'] >= 50 && $escala['satisfaccion_usuarios'] < 65) {
            $satisfaccion_usuarios = 1;
        }else{
            $satisfaccion_usuarios = 0;
        }

        array_push($array_escala, $satisfaccion_usuarios);
        $escala['ventas_canceladas'] = $escala['ventas_canceladas'] / $escala['contar'];

        $cal = min($array_escala);
        $reclamos = ($cal > 4) ? 'Excelente' : (($cal > 3) ? 'Muy bueno' : (($cal > 2) ? 'Bueno' : (($cal > 1) ? 'Regular' : 'Malo')));

        $escala['reclamos']               = $this->maskNumber($escala['reclamos'], 2);
        $escala['tiempo_respuesta']       = $this->maskNumber($escala['tiempo_respuesta'], 2);
        $escala['retraso_tiempo_entrega'] = $this->maskNumber($escala['retraso_tiempo_entrega'], 2);
        $escala['satisfaccion_usuarios']  = $this->maskNumber($escala['satisfaccion_usuarios'], 2);
        $escala['ventas_canceladas']      = $this->maskNumber($escala['ventas_canceladas'], 2);
        $escala['ventas_canceladas']      = $array_escala;
        $escala['cal']                    = $cal;

        return array('escala' => $cal, 'escala_descripcion' => $reclamos, 'detalle' => $escala);
    }

    public function clasificacionUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);


        $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        $array_monedas_local   = $this->filter_by_value_moneda($array_monedas_locales, 'iso_code_2', 'CO');
        $array_monedas_local   = $array_monedas_local[0];

        $array_monedas_local['costo_dolar'] = floatval("" . $array_monedas_local['costo_dolar']);
        $costo_dolar                        = $array_monedas_local['costo_dolar'];
        

        $fecha = intval(microtime(true)*1000);
        $data['fecha_actual'] = $fecha;
        $data['hace_meses'] = 6;//Este es el que valida que sean cada 6 meses
        $escala = $this->escalaUsuario($data);
        if($escala['status'] != 'success') return $escala;
        $escala = $escala['data'];
        // if($escala['escala'] < 4) {
        //     return array('status' => 'fail', 'message'=> 'no clasificacion', 'data' => null, 'escala' => $escala, 'paso' => 1);
        // }
        
        $fecha = $data['fecha_actual'];
        $micro_date = intval($fecha / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechainicio = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);
        $fechafinal = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        parent::conectar();
        $selectxclasificacion = "
            SELECT 
                (
                    SELECT COUNT( DISTINCT(pt.id_carrito) )
                    FROM productos_transaccion  pt 
                    WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal'
                ) AS ventas_concretadas,
                (
                    SELECT COUNT( DISTINCT(pt.id_carrito) )
                    FROM productos_transaccion  pt 
                    WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 10 AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal'
                ) AS ventas_noconcretadas,
                
                (SELECT pv.total_puntos FROM puntos_vendedor pv WHERE pv.uid = pt.uid_vendedor AND pv.empresa) as puntos,
                
                (
                    SELECT COUNT( DISTINCT(pt.id_carrito) )
                    FROM productos_transaccion_rechazada ptr INNER JOIN productos_transaccion pt ON ptr.id_transaccion = pt.id 
                    WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal'
                ) AS ventas_canceladas,
                
                SUM(
                    CASE
                        WHEN pt.estado = 13
                        THEN pt.precio_usd
                        ELSE 0
                    END
                ) as facturacion_usd_OLD,
                SUM(
                    IF( pt.tipo = 1 AND pt.estado = 13,
                        (pt.precio + pt.precio_segundo_pago + pt.precio_cupon) / $costo_dolar,
                        pt.precio_comprador / $costo_dolar
                    )
                ) as facturacion_usd
                
                FROM productos_transaccion pt 
                WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal';";
        $clasificacion = parent::consultaTodo($selectxclasificacion);
        parent::cerrar();

        if(count($clasificacion) <= 0) {
            return array(
                'status'        => 'fail',
                'message'       => 'no clasificacion',
                'paso'       => '2',
                'data'          => null,
                'clasificacion' => $clasificacion
            );
        }

        $resumenUsuarioData = $this->resumenUsuario($data);

        if ( intval( $data['empresa'] ) == 0 ) {
            // Aquellos con el valor de cero, no son empresas.
            parent::conectar();
            $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
            $usuario = parent::consultaTodo($selectxuser);
            $usuario = $usuario[0];
            parent::cerrar();
            // Calcular antiguedad
            $diferencia = intval(microtime(true)*1000) - intval($usuario['fecha_ingreso']);
            $meses = intval($diferencia / (1000*60*60*24*30));
            $clasificacion[0]['antiguedad'] = 3.1;//$meses;
            $clasificacion[0]['antiguedad_creada'] = intval($usuario['fecha_ingreso']);
            $clasificacion[0]['antiguedad_hoy'] = intval(microtime(true)*1000);

            $clasificacion = $this->definirClasificacionNoEmpresas($clasificacion, $data, $resumenUsuarioData);
        }else{
            parent::conectar();
            $selectxuser = null;
            if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
            $usuario = parent::consultaTodo($selectxuser);
            $usuario = $usuario[0];
            parent::cerrar();
            // Calcular antiguedad
            $diferencia = intval(microtime(true)*1000) - intval($usuario['fecha_creacion']);
            $meses = intval($diferencia / (1000*60*60*24*30));
            $clasificacion[0]['antiguedad'] = 3.1;//$meses;
            $clasificacion[0]['antiguedad_creada'] = intval($usuario['fecha_creacion']);
            $clasificacion[0]['antiguedad_hoy'] = intval(microtime(true)*1000);
            $clasificacion = $this->definirClasificacionEmpresas($clasificacion, $data, $resumenUsuarioData);
        }

        return array(
            'status'         => 'success',
            'message'        => 'clasificacion',
            'data'           => $clasificacion,
            'resumenUsuario' => $resumenUsuarioData,
            'fechainicio'   => $fechainicio,
            'fechafinal'    => $fechafinal
        );
        // return $clasificacion;
    }

    function insertDataEjemple(Array $data) {
        parent::conectar();
        for ($i = 11; $i <= 149; $i++) {
            $insertxtest = "
            INSERT INTO `productos_transaccion` (`id`,`id_carrito`,`id_producto`,`uid_vendedor`,`uid_comprador`,`cantidad`,`boolcripto`,`moneda`,`precio`,`precio_usd`,`precio_moneda_actual_usd`,`tipo`,`estado`,`contador`,`id_metodo_pago`,`empresa`,`empresa_comprador`,`descripcion_extra`,`refer`,`refer_porcentaje_comision`,`fecha_creacion`,`fecha_actualizacion`,`contador_devolucion_envio`,`estado_pago_transaccion`)
            VALUES ('$i','$i',15,'5','1291',1,1,'Nasbigold',3784.51,1,30000.000000,1,13,0,1,1,0,'  ','1291',0.000000,'1609158356529','1609158400580',0,0);";
            parent::queryRegistro($insertxtest);
            
        }
        parent::cerrar();
        return array(
            'status'         => 'success',
            'message'        => 'Datos insertados'
        );
    }

    function definirClasificacionNoEmpresas(Array $clasificacion, Array $data, Array $resumenUsuarioData)
    {
        // 1 USD = 3784.51 COP
        $clasificacion['antiguedad_creado'] ="Aqui";// $usuario['fecha_creacion'];
        $clasificacion['antiguedad_hoy'] = "Aqui";//microtime(true)*1000;

        $listProximosObjetivos = array(
            'activar_semaforo' => 0,
            'activar_semaforo_descripcion' => 'Activar semaforo',

            'ser_junior' => 1,
            'ser_junior_descripcion' => 'Convertirme en Junior',

            'ser_senior' => 2,
            'ser_senior_descripcion' => 'Convertirme en Senior',

            'ser_master' => 3,
            'ser_master_descripcion' => 'Convertirme en Master'

        );

        $clasificacion                         = $clasificacion[0];
        $clasificacion['puntos']               = floatval( $clasificacion['facturacion_usd'] ) / 1.3;
        $clasificacion['facturacion_usd']      = floatval( $clasificacion['facturacion_usd'] );
        $clasificacion['ventas_canceladas']    = intval( $clasificacion['ventas_canceladas'] );
        $clasificacion['ventas_concretadas']   = intval( $clasificacion['ventas_concretadas'] );
        $clasificacion['ventas_noconcretadas'] = intval( $clasificacion['ventas_noconcretadas'] );
        
        $totalOrdenesDeCompra = $clasificacion['ventas_concretadas'] + $clasificacion['ventas_noconcretadas'];
        $clasificacion['ventas_totales'] = $totalOrdenesDeCompra;

        // Porcentaje de ventas canceladas
        
        $clasificacion['porcentaje_ventas_canceladas'] = 0;
        if ( $totalOrdenesDeCompra > 0) {
            $clasificacion['porcentaje_ventas_canceladas'] = $clasificacion['ventas_noconcretadas'] < ( $totalOrdenesDeCompra * 0.03);
        }

        
        if ( !isset( $clasificacion['retraso_tiempo_entrega'] ) ) {
            $clasificacion['retraso_tiempo_entrega'] = 0;
        }else{
            $clasificacion['retraso_tiempo_entrega'] = intval( $clasificacion['retraso_tiempo_entrega'] );
        }

        if( $clasificacion['ventas_concretadas'] <= 15 ) {
            if ( $clasificacion['ventas_concretadas'] < $this->semaforoNumeroVentas ) {
                // No ha a activado el semaforo
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['activar_semaforo'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['activar_semaforo_descripcion'],

                    'escala'                           => 0,
                    'escala_descripcion'               => 'Sin clasificacion',
                    'puntos'                           => 0,
                    'puntos_mask'                      => 0,
                    'clasificacion'                    => $clasificacion
                );
            }else if ( $clasificacion['ventas_concretadas'] > $this->semaforoNumeroVentas && $clasificacion['ventas_concretadas'] <= 15 ){
                // Ya se activo el semaforo
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],

                    'escala'                           => 0,
                    'escala_descripcion'               => 'Sin clasificacion',
                    'puntos'                           => 0,
                    'puntos_mask'                      => 0,
                    'clasificacion'                    => $clasificacion
                );
            }else{}
        }
        $array_clasificacion = [];

        // Deprecado
        // $ventas_concretadas = ($clasificacion['ventas_concretadas'] > 15) ? 1 : (($clasificacion['ventas_concretadas'] > 50) ? 2 : (($clasificacion['ventas_concretadas'] > 100) ? 3 : 0));
        if ( $clasificacion['ventas_concretadas'] > 15 && $clasificacion['ventas_concretadas'] < 50 ) {
            $ventas_concretadas = 1;
        }else if ( $clasificacion['ventas_concretadas'] > 50 && $clasificacion['ventas_concretadas'] < 100 ) {
            $ventas_concretadas = 2;
        }else if ( $clasificacion['ventas_concretadas'] > 100 ) {
            $ventas_concretadas = 3;
        }else{
            $ventas_concretadas = 0;
        }
        

        array_push($array_clasificacion, $ventas_concretadas);
        // Deprecado
        // $facturacion = ($clasificacion['facturacion_usd'] > 133) ? 1 : (($clasificacion['facturacion_usd'] > 1500) ? 2 : (($clasificacion['facturacion_usd'] > 2500) ? 3 : 0));
        if ( $clasificacion['facturacion_usd'] > 132.1175000198 && $clasificacion['facturacion_usd'] < 1321.1750001982 ) {
            // Junior
            $facturacion = 1;
        }else if ( $clasificacion['facturacion_usd'] > 1321.1750001982 && $clasificacion['facturacion_usd'] < 2642.3500003964) {
            // Senior
            $facturacion = 2;
        }else if ( $clasificacion['facturacion_usd'] > 2642.3500003964 ) {
            // Master
            $facturacion = 3;
        }else{
            $facturacion = 0;
        }

        $reclamos = ($ventas_concretadas == 1) ? 'Junior' : (($ventas_concretadas == 2) ? 'Senior' : (($ventas_concretadas == 3) ? 'Master' : null));
        return $this->definirClasificacionGenera($clasificacion, $data, $resumenUsuarioData, $reclamos, $ventas_concretadas, $listProximosObjetivos, $array_clasificacion, $facturacion);
    }

    function definirClasificacionEmpresas(Array $clasificacion, Array $data, Array $resumenUsuarioData)
    {

        // 1 USD = 3784.51 COP

        

        $listProximosObjetivos = array(
            'activar_semaforo' => 0,
            'activar_semaforo_descripcion' => 'Activar semaforo',

            'ser_junior' => 1,
            'ser_junior_descripcion' => 'Convertirme en Junior',

            'ser_senior' => 2,
            'ser_senior_descripcion' => 'Convertirme en Senior',

            'ser_master' => 3,
            'ser_master_descripcion' => 'Convertirme en Master'

        );

        $clasificacion = $clasificacion[0];
        $clasificacion['puntos'] = floatval( $clasificacion['facturacion_usd'] ) / 1.3;
        $clasificacion['puntos'] = $this->maskNumber( $clasificacion['puntos'] );

        $clasificacion['facturacion_usd'] = floatval( $clasificacion['facturacion_usd'] );
        $clasificacion['facturacion_usd_mask'] = $this->maskNumber( $clasificacion['facturacion_usd'] );

        $clasificacion['facturacion_cop'] = floatval( $clasificacion['facturacion_usd'] ) * 3784.51;
        $clasificacion['facturacion_cop_mask'] = $this->maskNumber( $clasificacion['facturacion_cop'] );

        $clasificacion['ventas_canceladas'] = intval( $clasificacion['ventas_canceladas'] );
        $clasificacion['ventas_concretadas'] = intval( $clasificacion['ventas_concretadas'] );
        $clasificacion['ventas_noconcretadas'] = intval( $clasificacion['ventas_noconcretadas'] );

        $constante_sum = 0; // para pruebas se pone en días, producción dejar en 1.
        $clasificacion['ventas_concretadas'] = $clasificacion['ventas_concretadas'] + $constante_sum;

        
        $totalOrdenesDeCompra = $clasificacion['ventas_concretadas'] + $clasificacion['ventas_noconcretadas'];
        $clasificacion['ventas_totales'] = $totalOrdenesDeCompra;

        // Porcentaje de ventas canceladas

        $clasificacion['porcentaje_ventas_canceladas'] = 0;
        if ( $totalOrdenesDeCompra > 0) {
            $clasificacion['porcentaje_ventas_canceladas'] = $clasificacion['ventas_noconcretadas'] < ( $totalOrdenesDeCompra * 0.03);
        }
        
        if ( !isset( $clasificacion['retraso_tiempo_entrega'] ) ) {
            $clasificacion['retraso_tiempo_entrega'] = 0;
        }else{
            $clasificacion['retraso_tiempo_entrega'] = intval( $clasificacion['retraso_tiempo_entrega'] );
        }

        if( $clasificacion['ventas_concretadas'] <= 150 ) {
            if ( $clasificacion['ventas_concretadas'] < $this->semaforoNumeroVentas ) {
                // No ha a activado el semaforo
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['activar_semaforo'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['activar_semaforo_descripcion'],

                    'escala'                           => 0,
                    'escala_descripcion'               => 'Sin clasificacion',
                    'puntos'                           => 0,
                    'puntos_mask'                      => 0,
                    'clasificacion'                    => $clasificacion,
                    'descripcion_extra_2'              => 'Paso 0.1'
                );
            }else if ( $clasificacion['ventas_concretadas'] >= $this->semaforoNumeroVentas && $clasificacion['ventas_concretadas'] <= 150 ){
                // Ya se activo el semaforo

                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],

                    'escala'                           => 0,
                    'escala_descripcion'               => 'Sin clasificacion',
                    'puntos'                           => 0,
                    'puntos_mask'                      => 0,
                    'clasificacion'                    => $clasificacion,
                    'descripcion_extra_2'              => 'Paso 0.2'
                );
            }else{}
        }

        $array_clasificacion = [];

        if ( $clasificacion['ventas_concretadas'] > 150 && $clasificacion['ventas_concretadas'] < 300 ) {
            $ventas_concretadas = 1;
        }else if ( $clasificacion['ventas_concretadas'] > 300 && $clasificacion['ventas_concretadas'] < 500 ) {
            $ventas_concretadas = 2;
        }else if ( $clasificacion['ventas_concretadas'] > 500) {
            $ventas_concretadas = 3;
        }else{
            $ventas_concretadas = 0;
        }
        

        array_push($array_clasificacion, $ventas_concretadas);

        if ( $clasificacion['facturacion_usd'] > 2642.3500003963522  && $clasificacion['facturacion_usd'] < 3963.5250005945286 ) {
            // Junior
            $facturacion = 1;
        }else if ( $clasificacion['facturacion_usd'] > 3963.5250005945286 && $clasificacion['facturacion_usd'] < 5284.7000007927045) {
            // Senior
            $facturacion = 2;
        }else if ( $clasificacion['facturacion_usd'] > 5284.7000007927045 ) {
            // Master
            $facturacion = 3;
        }else{
            $facturacion = 0;
        }

        $reclamos = ($ventas_concretadas == 1) ? 'Junior' : (($ventas_concretadas == 2) ? 'Senior' : (($ventas_concretadas == 3) ? 'Master' : null));
        return $this->definirClasificacionGenera($clasificacion, $data, $resumenUsuarioData, $reclamos, $ventas_concretadas, $listProximosObjetivos, $array_clasificacion, $facturacion);
    }

    function definirClasificacionGenera(Array $clasificacion, Array $data, Array $resumenUsuarioData, $reclamos = 0, $ventas_concretadas = 0, Array $listProximosObjetivos, Array $array_clasificacion, $facturacion)
    {

        $reclamos = ($ventas_concretadas == 1) ? 'Junior' : (($ventas_concretadas == 2) ? 'Senior' : (($ventas_concretadas == 3) ? 'Master' : null));
        $arrayEscalas = array(
            array(
                'id' => 0,
                'nombre' => 'Semaforo activo'
            ),
            array(
                'id' => 1,
                'nombre' => 'Junior'
            ),
            array(
                'id' => 2,
                'nombre' => 'Senior'
            ),
            array(
                'id' => 3,
                'nombre' => 'Master'
            )
        );
        if ($resumenUsuarioData['status'] != 'success') {
            return array(
                'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],
                'escala'                           => $arrayEscalas[ 0 ][ 'id' ],
                'escala_descripcion'               => $arrayEscalas[ 0 ][ 'nombre' ],
                'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                'clasificacion'                    => $clasificacion,
                'detalle_extra'                    => $array_clasificacion,
                'descripcion_extra_2'              => 'Paso 1'
            );
        }
        if ($clasificacion['antiguedad'] < 3) {
            return array(
                'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],
                'escala'                           => $arrayEscalas[ 0 ][ 'id' ],
                'escala_descripcion'               => $arrayEscalas[ 0 ][ 'nombre' ],
                'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                'clasificacion'                    => $clasificacion,
                'detalle_extra'                    => $array_clasificacion,
                'descripcion_extra_2'              => 'Paso 2'
            );
        }
        if ( $ventas_concretadas == 1 ) {
            if ( $facturacion >= 1  && $clasificacion['porcentaje_ventas_canceladas'] && $resumenUsuarioData['data']['escalaUsuario']['escala'] >= 3) {
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_senior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_senior_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 3'
                );
            }else{
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas - 1 ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas - 1 ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 4'
                );
            }
        }else if ( $ventas_concretadas == 2 ) {
            if ( $facturacion >= 2  && $clasificacion['porcentaje_ventas_canceladas'] && $resumenUsuarioData['data']['escalaUsuario']['escala'] >= 3) {
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_master'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_master_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 5'
                );
            }else{
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_senior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_senior_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas - 1 ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas - 1 ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 6'
                );
            }
        }else if ( $ventas_concretadas == 3 ) {
            if ( $facturacion >= 3  && $clasificacion['porcentaje_ventas_canceladas'] && $resumenUsuarioData['data']['escalaUsuario']['escala'] >= 3) {
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_senior'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_senior_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 7'
                );
            }else{
                return array(
                    'proximo_objetivo'                 => $listProximosObjetivos['ser_master'],
                    'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_master_descripcion'],
                    'escala'                           => $arrayEscalas[ $ventas_concretadas - 1 ][ 'id' ],
                    'escala_descripcion'               => $arrayEscalas[ $ventas_concretadas - 1 ][ 'nombre' ],
                    'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                    'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                    'clasificacion'                    => $clasificacion,
                    'detalle_extra'                    => $array_clasificacion,
                    'descripcion_extra_2'              => 'Paso 8'
                );
            }
        }else{
            return array(
                'proximo_objetivo'                 => $listProximosObjetivos['ser_junior'],
                'proximo_objetivo_descripcion'     => $listProximosObjetivos['ser_junior_descripcion'],
                'escala'                           => $arrayEscalas[ 0 ][ 'id' ],
                'escala_descripcion'               => $arrayEscalas[ 0 ][ 'nombre' ],
                'puntos'                           => floatval( $clasificacion['facturacion_usd'] ) / 1.3,
                'puntos_mask'                      => $this->maskNumber(floatval( $clasificacion['facturacion_usd'] ) / 1.3, 2),
                'clasificacion'                    => $clasificacion,
                'detalle_extra'                    => $array_clasificacion,
                'descripcion_extra_2'              => 'Paso 9'
            );
        }
    }

    function mapUsuario(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto'];//"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
            }else if($empresa == 1){
                $datanombre = $user['razon_social'];//$user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['empresa'] = $empresa;
            $user['foto'] = $datafoto;
            $usuarios[$x] = $user;
        }

        return $usuarios;
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


    function mapPromedios(Array $promedios)
    {
        $arrayClasificacion = [
            array(
                'id'=> 1,
                'nombre'=> 'Bronze',
                'label_es' => "Es una tienda aprendiz",
                'label_en' => "It's an apprentice shop",
            ),
            array(
                'id'=> 2,
                'nombre'=> 'Silver',
                'label_es' => "Es una tienda intermedia",
                'label_en' => "It's an intermediate store",
            ),
            array(
                'id'=> 3,
                'nombre'=> 'Gold',
                'label_es' => "Es una tienda ganadora",
                'label_en' => "It's a winning store",
            ),
            array(
                'id'=> 4,
                'nombre'=> 'Platinum',
                'label_es' => "Es una tienda confiable",
                'label_en' => "It is a reliable store",
            ),
            array(
                'id'=> 5,
                'nombre'=> 'Diamond',
                'label_es' => "Es una tienda excelente",
                'label_en' => "It's an excellent store",
            ),
        ];

        foreach ($promedios as $x => $prom) {
            $prom['general_prom'] = $this->truncNumber($prom['general_prom'], 2);
            $prom['buena_atencion_prom'] = $this->truncNumber($prom['buena_atencion_prom'], 2);
            $prom['tiempo_entrega_prom'] = $this->truncNumber($prom['tiempo_entrega_prom'], 2);
            $prom['fidelidad_producto_prom'] = $this->truncNumber($prom['fidelidad_producto_prom'], 2);
            $prom['satisfaccion_producto_prom'] = $this->truncNumber($prom['satisfaccion_producto_prom'], 2);
            $prom['cantidad_comentarios'] = $this->truncNumber($prom['cantidad_comentarios'], 0);
            $prom['buenos'] = $this->truncNumber($prom['buenos'], 0);
            $prom['regulares'] = $this->truncNumber($prom['regulares'],0);
            $prom['malos'] = $this->truncNumber($prom['malos'], 0);
            $prom['ventas_seis_meses'] = $this->truncNumber($prom['ventas_seis_meses'], 0);
            $prom['vendedor_tipo'] = 'Amateur';
            $prom['vendedor_tipo_label_es'] = '';
            $prom['vendedor_tipo_label_en'] = '';

            if(ceil($prom['general_prom']) > 1) {
                $prom['vendedor_tipo'] = $this->filter_by_value($arrayClasificacion, 'id', intval($prom['general_prom']))[0]['nombre'];
                $prom['vendedor_tipo_label_es'] = $this->filter_by_value($arrayClasificacion, 'id', intval($prom['general_prom']))[0]['label_es'];
                $prom['vendedor_tipo_label_en'] = $this->filter_by_value($arrayClasificacion, 'id', intval($prom['general_prom']))[0]['label_en'];
            }else{
                $prom['vendedor_tipo'] = $this->filter_by_value($arrayClasificacion, 'id', intval(1))[0]['nombre'];
                $prom['vendedor_tipo_label_es'] = $this->filter_by_value($arrayClasificacion, 'id', intval(1))[0]['label_es'];
                $prom['vendedor_tipo_label_en'] = $this->filter_by_value($arrayClasificacion, 'id', intval(1))[0]['label_en'];
            }

            $promedios[$x] = $prom;
        }

        return $promedios;
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
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray[] = $array[$key];
                    // $newarray[$key] = $array[$key];
                }
            }
        }
        return $newarray;
    }

    // Nuevas funciones 24 Nov. 2020
    function ventasGratuitasRealizadas(Array $data) 
    {
        if( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos: uid=' . $data['uid'] . ', empresa=' . $data['empresa'],
                'cantidad'=> null,
                'data'    => null
            );
        }
        
        parent::conectar();

        $selectxresumenxventas =
            "SELECT COUNT(*) AS ventas
            FROM productos_transaccion pt
            INNER JOIN  buyinbig.productos p ON( id_producto = p.id)
            WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado >= 13 AND pt.exposicion_pt = 1;";

        $selectresumenventas = parent::consultaTodo($selectxresumenxventas);
        parent::cerrar();

        if(count($selectresumenventas) == 0) {
            return array(
                'status' => 'noTieneVentasGratuitas',
                'message'=> 'El usuario no cuenta con registros de ventas gratuitas',
                'data' => null
            );
        }else{
            if ( floatval( $selectresumenventas[0]['ventas'] ) >= 3 ) {
                
                parent::conectar();
                $selectxventasxtopexgratuitas = "SELECT * FROM vendedores_tope_ventas_gratuitas  WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
                $selectventastopegratuitas = parent::consultaTodo($selectxventasxtopexgratuitas);

                if(count($selectventastopegratuitas) == 0) {
                    // No ha sido notificado
                    $insertxnotificacionxtopexventasxgratuitas = 
                    "INSERT INTO vendedores_tope_ventas_gratuitas(uid, empresa ) 
                    VALUES ( '$data[uid]', '$data[empresa]' );";
                    parent::queryRegistro($insertxnotificacionxtopexventasxgratuitas);
                    parent::cerrar();

                    $schemaNotificacion = Array(
                        'uid'     => $data['uid'],
                        'empresa' => $data['empresa'],
                        
                        'text' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        'es' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        'en' => 'You have 3 or more sales made with free exposure. Go to my accounts / publications and proceed to modify your articles to a different type of exposure.',
                        'keyjson' => '',

                        'url' => 'mis-cuentas.php?tab=sidenav_publicaciones'
                    );
                    $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";
                    parent::remoteRequest($URL, $schemaNotificacion);

                    return array(
                        'status' => 'success',
                        'message'=> 'resumen',
                        'data' => $selectresumenventas
                    );

                }else{
                    $selectxproductosxgratuitosa= 
                        "SELECT * FROM buyinbig.productos WHERE uid = 5 AND empresa = 1 AND exposicion = 1;";
                    $productos_gratuitos = parent::consultaTodo($selectxproductosxgratuitosa);
                    parent::cerrar();

                    if( COUNT( $productos_gratuitos ) > 0 ){
                        
                        $schemaNotificacion = Array(
                            'uid'     => $data['uid'],
                            'empresa' => $data['empresa'],
                            
                            'text' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                            'es' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                            'en' => 'You have 3 or more sales made with free exposure. Go to my accounts / publications and proceed to modify your articles to a different type of exposure.',
                            'keyjson' => '',

                            'url' => 'mis-cuentas.php?tab=sidenav_publicaciones'
                        );
                        $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";
                        parent::remoteRequest($URL, $schemaNotificacion);
                        
                        return array(
                            'status' => 'success',
                            'message'=> 'resumen',
                            'data' => $selectresumenventas
                        );
                    }

                }
            }

            return array(
                'status' => 'noTieneTopeVentasGratuitas',
                'message'=> 'No ha completado las 3 ventas en categoria gratuita. Lleva: ' . $selectresumenventas[0]['ventas'],
                'JSON'=> $selectresumenventas[0],
                'data' => floatval( $selectresumenventas[0]['ventas'] )
            );

        }
    }


    // Nuevas funciones 18 Mar. 2021
    public function calificacionGeneralVendedorExtra(Array $data)
    {
        if( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) ) 
        {
            return array(
                'status' => 'fail',
                'message'=> 'faltan datos',
                'data' => null
            );
        }

        parent::conectar();
        $selectxdatosxvendedorxextra = "
            SELECT 
                    (
                        SELECT COUNT( DISTINCT(pt.id_carrito) ) AS contar FROM productos_transaccion pt WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]'
                    ) AS contar,                
                    
                    (
                        SELECT COUNT( DISTINCT(pt.id_carrito) ) AS reclamos FROM productos_transaccion_reportados ptr INNER JOIN productos_transaccion pt ON ptr.id_transaccion = pt.id WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]'
                    ) AS reclamos,
                    
                    (
                        SELECT IF(AVG(cv.tiempo_entrega), AVG(cv.tiempo_entrega), 0) AS tiempos_entrega FROM calificacion_vendedor cv WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]'
                    ) AS tiempos_entrega,

                    (
                        SELECT (AVG(cv.promedio) * (100/5)) as promedio FROM calificacion_vendedor cv WHERE cv.uid = '$data[uid]' AND cv.empresa = '$data[empresa]'
                    ) AS satisfaccion_usuarios,

                    (
                        SELECT 
                            AVG((pqr.fecha_actualizacion - pqr.fecha_creacion) / 3600000) as promedio_tiempo_respuesta 
                            FROM productos_pqr pqr 
                            WHERE (pqr.uid_respuesta = '$data[uid]' AND pqr.empresa_respuesta = '$data[empresa]')
                    ) AS tiempo_respuesta;
        ";

        $escala = parent::consultaTodo($selectxdatosxvendedorxextra);
        parent::cerrar();
        if(count($escala) <= 0) return array('status' => 'fail', 'message'=> 'escala no existe', 'data' => null);

        $escala                          = $escala[0];
        $escala['contar']                = intval( $escala['contar'] );
        $escala['reclamos']              = floatval( $escala['reclamos'] );
        $escala['tiempos_entrega']       = floatval( $escala['tiempos_entrega'] );
        $escala['satisfaccion_usuarios'] = floatval( $escala['satisfaccion_usuarios'] );
        $escala['tiempo_respuesta']      = floatval( $escala['tiempo_respuesta'] );

        if ( !isset( $escala['ventas_totales'] ) ) {
            $escala['ventas_totales'] = 0;
        }

        if ( $escala['reclamos'] != 0 ) {
            if ( $escala['ventas_totales'] > 0 ) {
                $escala['reclamos'] = ( $escala['reclamos'] * 100 ) / $escala['ventas_totales'];
            }else{
                $escala['reclamos'] = 0;
            }
        }

        // $escala['query'] = $selectxdatosxvendedorxextra;

        return $escala;

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
}
?>