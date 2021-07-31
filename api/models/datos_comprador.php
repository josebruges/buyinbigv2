<?php
require 'conexion.php';
class DatosComprador extends Conexion
{
    private $factorxdivisorxpuntosxcomprador = 1.3211750001981761;

    public function misPuntos(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha_actual'] = $fecha;
        $data['hace_meses'] = 6;//Este es el que valida que sean cada 6 meses

        $fecha = $data['fecha_actual'];
        $micro_date = intval($fecha / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechainicio = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);
        $fechafinal = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        $selectxfacturacionxcomprador = "
            SELECT 
                (
                    SELECT COUNT( DISTINCT(pt.id_carrito) )
                    FROM productos_transaccion  pt 
                    WHERE pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal'
                ) AS total_comprado_count,
                SUM(case when pt.estado = 13 then pt.precio_usd else 0 end) as facturacion_usd
                FROM productos_transaccion pt 
                WHERE pt.uid_comprador = '$data[uid]' AND pt.empresa_comprador = '$data[empresa]' AND pt.fecha_actualizacion BETWEEN '$fechainicio' AND '$fechafinal';";
        
        parent::conectar();
        $selectfacturacioncomprador = parent::consultaTodo($selectxfacturacionxcomprador);
        parent::cerrar();

        if( !$selectfacturacioncomprador ){
            return array('status' => 'fail', 'message'=> 'faltan datos.', 'data' => null );
        }
        
        $selectfacturacioncomprador[0]['total_comprado_count'] = floatval($selectfacturacioncomprador[0]['total_comprado_count']);
        $selectfacturacioncomprador[0]['facturacion_usd']      = floatval($selectfacturacioncomprador[0]['facturacion_usd']);
        return array(
            'status' => 'success',
            'message'=> 'Cual es el acumulado en compras del cliente.',

            'puntos' => floatval($selectfacturacioncomprador[0]['facturacion_usd']) / $this->factorxdivisorxpuntosxcomprador,
            'puntos_mask' => $this->maskNumber(floatval($selectfacturacioncomprador[0]['facturacion_usd']) / $this->factorxdivisorxpuntosxcomprador, 2),
            
            'data' => [
                'total_comprado_count' => $selectfacturacioncomprador[0]['total_comprado_count'],
                'total_comprado_count_mask' => $this->maskNumber($selectfacturacioncomprador[0]['total_comprado_count'], 0),
                'facturacion_usd' => $selectfacturacioncomprador[0]['facturacion_usd'],
                'facturacion_usd_mask' => $this->maskNumber($selectfacturacioncomprador[0]['facturacion_usd'], 2)
            ]
        );
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
}
?>