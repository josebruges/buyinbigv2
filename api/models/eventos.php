<?php
require 'conexion.php';
require '../../Shippo.php';

class Eventos extends Conexion
{

    public function verEventos(Array $data)
    {
        if(!isset($data) || !isset($data['idioma']) || !isset($data['iso_code_2'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        parent::conectar();
        $selectxeventos = "SELECT e.* FROM eventos e WHERE (e.iso_code_2 = '$data[iso_code_2]' OR e.iso_code_2 = 'A') AND e.idioma = '$data[idioma]';";
        $eventos = parent::consultaTodo($selectxeventos, false);
        parent::cerrar();
        if(count($eventos) <= 0) return array('status' => 'fail', 'message'=> 'no hay eventos programdos', 'data' => null);

        $eventos = $this->mapEventos($eventos);
        return array('status' => 'success', 'message'=> 'eventos', 'data' => $eventos);
    }

    public function inscribirmeEvento(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        parent::conectar();
        $selectxeventos = "SELECT e.* FROM eventos e WHERE (e.iso_code_2 = '$data[iso_code_2]' OR e.iso_code_2 = 'A') AND e.idioma = '$data[idioma]';";
        $eventos = parent::consultaTodo($selectxeventos, false);
        parent::cerrar();
        if(count($eventos) <= 0) return array('status' => 'fail', 'message'=> 'no hay eventos programdos', 'data' => null);

        $eventos = $this->mapEventos($eventos);
        return array('status' => 'success', 'message'=> 'eventos', 'data' => $eventos);
    }

    function mapEventos(Array $evento)
    {
        foreach ($evento as $x => $event) {
            $event['id'] = floatval($event['id']);
            $event['estado'] = floatval($event['estado']);
            $event['fecha_inicio'] = floatval($event['fecha_inicio']);
            $event['fecha_creacion'] = $event['fecha_creacion'];
            $event['fecha_actualizacion'] = $event['fecha_actualizacion'];

            $evento[$x] = $event;
        }

        return $evento;
    }

}
?>