<?php
    require 'nasbifunciones.php';
    
    if(!isset($_GET['k'])) return;
    if(!isset($_GET['t'])) return;
    $key = $_GET['k'];
    $tipo = $_GET['t'];
    $hex = hex2bin($key);
    $data = (array) json_decode($hex, true);
    if(!$data) return;

    //// output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$data['filename']);

    $f = fopen('php://output', 'w');
    $delimiter = ";";
    if ($tipo == 1 || $tipo == '1') {
        $fields = array('Titulo', 'Comision COP', 'fecha');
    } else if ($tipo == 2 || $tipo == '2') {
        $fields = array('Titulo', 'Precio COP', 'Descripcion extra', 'Tipo', 'Fecha', 'Cantidad vendida');
    }
    
    /* $fields = array('Titulo', 'Precio', 'Moneda', 'Precio USD', 'Descripcion extra', 'Estado', 'Tipo'); */
    fputcsv($f, $fields, $delimiter);

    $conexion = new Conexion();
    $conexion->conectar();
    if ($tipo == 1 || $tipo == '1') {
        $selecttodos = "SELECT p.titulo, pt.boolcripto, IF( pt.tipo = 1,((pt.precio + pt.precio_segundo_pago + pt.precio_cupon) * pt.refer_porcentaje_comision) * pt.refer_porcentaje_comision_plan,(pt.precio_comprador * pt.refer_porcentaje_comision) * pt.refer_porcentaje_comision_plan) AS 'comision', pt.fecha_actualizacion
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        INNER JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion AND pt.id_producto = '$data[id_producto]'
        ORDER BY pt.fecha_actualizacion DESC";
    } else if ($tipo == 2 || $tipo == '2') {
        $selecttodos = "SELECT p.titulo, pt.boolcripto, IF( pt.tipo = 1,(pt.precio + pt.precio_segundo_pago + pt.precio_cupon),pt.precio_comprador) as precio, pt.descripcion_extra, ptt.descripcion AS descripcion_tipo, pt.fecha_actualizacion, pt.cantidad
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        INNER JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion AND pt.id_producto = '$data[id_producto]'
        ORDER BY pt.fecha_actualizacion DESC";
    }
    /* $selecttodos = "SELECT p.titulo, pt.boolcripto, pt.precio, pt.moneda, pt.precio_usd, pt.descripcion_extra ,pte.descripcion AS descripcion_estado, ptt.descripcion AS descripcion_tipo
    FROM productos_transaccion pt
    INNER JOIN productos p ON pt.id_producto = p.id
    INNER JOIN productos_transaccion_estado pte ON pt.estado = pte.id
    INNER JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
    WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion AND pt.id_producto = '$data[id_producto]'
    ORDER BY pt.fecha_actualizacion DESC"; */
    $todoslosproductos = $conexion->consultaTodo($selecttodos);
    $conexion->cerrar();

    foreach ($todoslosproductos as $x => $transaccion) {
        if ($tipo == 1 || $tipo == '1') {
            if($transaccion['boolcripto'] == 1)$transaccion['comision'] = maskNumber($transaccion['comision'], 2);
            if($transaccion['boolcripto'] == 0)$transaccion['comision'] = maskNumber($transaccion['comision'], 2);
        } else {
            if($transaccion['boolcripto'] == 1)$transaccion['precio'] = maskNumber($transaccion['precio'], 2);
            if($transaccion['boolcripto'] == 0)$transaccion['precio'] = maskNumber($transaccion['precio'], 2);
        }
        $fechaT = getdate(floatval($transaccion['fecha_actualizacion'])/1000);
        $transaccion['fecha_actualizacion'] = "$fechaT[year]-".maskHora($fechaT['mon'])."-".maskHora($fechaT['mday'])." ".maskHora($fechaT['hours']).":".maskHora($fechaT['minutes']).":".maskHora($fechaT['seconds']);
        unset($transaccion['boolcripto']);
        if (isset($transaccion['descripcion_tipo'])) {
            if ($transaccion['descripcion_tipo'] == 'Subasta') {
                $transaccion['descripcion_tipo'] = 'Nasbi descuentos';
            }
        }
        /* $transaccion['precio_usd'] = maskNumber($transaccion['precio_usd'], 2);

        if ( $transaccion['moneda'] == "Nasbigold") {
            $transaccion['moneda'] = "Nasbichips";

        }else if ( $transaccion['moneda'] == "Nasbiblue") {
            $transaccion['moneda'] = "BD";

        }else{

        } */
        fputcsv($f, $transaccion, $delimiter);
    }
    fclose($f);

    function truncNumber(Float $number, Int $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }

    function maskNumber(Float $numero, Int $prec = 2)
    {
        $numero = truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }

    function maskHora($data) {
        return (int)$data < 10 ? "0".$data : $data;
    }
?>