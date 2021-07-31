<?php
require 'conexion.php';

class Contacto extends Conexion
{
    public function insertarContacto(Array $data){
        if(!isset($data) || !isset($data['nombre']) || !isset($data['correo']) || !isset($data['telefono']) || !isset($data['motivo']) || !isset($data['iso_code_2']) || !isset($data['ciudad'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $fecha = intval(microtime(true)*1000);

        $data['nombre'] = addslashes($data['nombre']);
        $data['correo'] = addslashes($data['correo']);
        $data['telefono'] = addslashes($data['telefono']);
        $data['motivo'] = addslashes($data['motivo']);

        parent::conectar();
        $insertarxcontacto = "INSERT INTO contacto
        (
            iso_code_2,
            ciudad,
            nombre,
            correo,
            telefono,
            motivo,
            respuesta,
            estado,
            tipo,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[iso_code_2]',
            '$data[ciudad]',
            '$data[nombre]',
            '$data[correo]',
            '$data[telefono]',
            '$data[motivo]',
            0,
            1,
            1,
            '$fecha',
            '$fecha'
        );";
        $contacto = parent::query($insertarxcontacto, false);
        parent::cerrar();
        unset($conexion);
        if(!$contacto) return array('status' => 'fail', 'message'=> 'no se pudo crear el contacto', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'contacto creado', 'data' => null);
    }

}
?>