<?php
require 'conexion.php';
class Dashboard extends Conexion
{
    public function mapchart(){
        parent::conectar();
        $request = null;
        $consultaruser = '';
        $consultaruser = "select  u.paisid , count(u.paisid) as count from usuarios u group by paisid";
        $lista = parent::consultaTodo($consultaruser);
        if (count($lista) > 0) {
            $request = array(
                'status' => 'success',
                'message'=> 'referidos',
                'data' => $lista
            );
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no referidos',
                'data' => null
            );
        }
        parent::cerrar();
        return $request;
    }
}
?>