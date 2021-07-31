<?php
require 'nasbifunciones.php';
class Nasbicoin extends Conexion
{
    public function recibirDinero(Array $data){
        $nasbifunciones = new Nasbifunciones();
        $req = $nasbifunciones->crearNasbicoin($data);
        unset($nasbifunciones);
        return $req;
    }
    public function emitir_nasbiChips(Array $data) {
        parent::conectar();
        parent::queryRegistro("INSERT INTO buyinbig.emision_nasbichips (monto, motivo, fecha_emision) VALUES ($data[monto], '$data[motivo]', current_timestamp);");
        parent::queryRegistro("update peer2win.generales set valor = valor + $data[monto] where id = 48");

        return array("status"=>"success", "message"=>"Emitidos correctamente los nasbichips");
    }
    public function walletUsuario(Array $data)
    {   
        $nasbifunciones = new Nasbifunciones();
        $req = $nasbifunciones->walletNasbiUsuario($data);
        unset($nasbifunciones);
        return $req;
    }
    public function transaccionesUsuario(Array $data)
    {
        $nasbifunciones = new Nasbifunciones();
        $req = $nasbifunciones->nasbiCoinTransaccionesUsuario($data);
        unset($nasbifunciones);
        return $req;
    }
    public function diferidoBloqueadosUsuario(Array $data)
    {
        $nasbifunciones = new Nasbifunciones();
        $req = $nasbifunciones->diferidoBloqueadoUsuario($data);
        unset($nasbifunciones);
        return $req;
    }
    public function generarPayUUsuario(Array $data)
    {
        $nasbifunciones = new Nasbifunciones();
        $req = $nasbifunciones->generarPayU($data);
        unset($nasbifunciones);
        return $req;
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