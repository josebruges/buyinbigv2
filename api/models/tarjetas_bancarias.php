<?php
require 'conexion.php';
class TarjetasBancarias extends Conexion
{
    public function crear(Array $data){
        
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['numero']) || !isset($data['ccv']) || !isset($data['mes_expiriacion']) || !isset($data['ano_expiriacion']) || !isset($data['nombre']) || !isset($data['apellido']) || !isset($data['direccion']) || !isset($data['telefono']) || !isset($data['tipo'])) return $request = array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $fecha = intval(microtime(true)*1000);
        $data = $this->mapeoAddTarjeta($data);
        $tarjetas_usario = $this->tarjetasUsuario($data);
        if($tarjetas_usario['cantidad'] >= 1) return array('status' => 'maxTarjetas', 'message'=> 'el usuario alcanzo el maximo de tarjetas', 'data' => null);

        parent::conectar();
        $insertdireccion = "INSERT INTO tarjetas_bancarias
        (
            uid,
            empresa,
            numero,
            ccv,
            mes_expiriacion,
            ano_expiriacion,
            nombre,
            apellido,
            direccion,
            telefono,
            tipo,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[numero]',
            '$data[ccv]',
            '$data[mes_expiriacion]',
            '$data[ano_expiriacion]',
            '$data[nombre]',
            '$data[apellido]',
            '$data[direccion]',
            '$data[telefono]',
            '$data[tipo]',
            '1',
            '$fecha',
            '$fecha'
        );";
        $direccion = parent::query($insertdireccion);
        parent::cerrar();
        if(!$direccion) return array('status' => 'fail', 'message'=> 'no se guardo la tarjeta', 'data' => null);

        return array('status' => 'success', 'message'=> 'tarjeta guardada', 'data' => null);
    }

    public function tarjetasUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return $request = array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectdireccion = "SELECT tb.* FROM tarjetas_bancarias tb WHERE tb.uid = '$data[uid]' AND tb.empresa = '$data[empresa]' AND tb.estado = 1";
        $tarjetasbancarias = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($tarjetasbancarias) <= 0) return array('status' => 'fail', 'message'=> 'no tine tarjetas bancarias', 'cantidad'=> 0,'data' => null);
            
        $tarjetasbancarias = $this->mapeoTarjetas($tarjetasbancarias);
        return array('status' => 'success', 'message'=> 'tarjetas bancarias', 'cantidad'=> count($tarjetasbancarias),'data' => $tarjetasbancarias);
    }

    public function eliminar(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])) return $request = array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletedireccion = "UPDATE tarjetas_bancarias SET estado = 0, fecha_actualizacion = '$fecha' WHERE id = '$data[id]' AND uid = '$data[uid]';";
        $eliminar = parent::query($deletedireccion);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no se elimino la tarjeta bancaria', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'tarjeta bancaria eliminada', 'data' => null);
    }

    function mapeoAddTarjeta(Array $data)
    {
        $data['uid'] = floatval(addslashes($data['uid']));
        $data['empresa'] = floatval(addslashes($data['empresa']));
        $data['numero'] = addslashes($data['numero']);
        $data['ccv'] = floatval(addslashes($data['ccv']));
        $data['mes_expiriacion'] = floatval(addslashes($data['mes_expiriacion']));
        $data['ano_expiriacion'] = floatval(addslashes($data['ano_expiriacion']));
        $data['nombre'] = addslashes($data['nombre']);
        $data['apellido'] = addslashes($data['apellido']);
        $data['direccion'] = addslashes($data['direccion']);
        $data['telefono'] = addslashes($data['telefono']);
        $data['tipo'] = floatval(addslashes($data['tipo']));

        if(isset($data['id'])) $data['id'] = floatval(addslashes($data['id']));

        return $data;
    }

    function mapeoTarjetas(Array $tarjetas)
    {
        foreach ($tarjetas as $x => $card) {
            $card['id'] = floatval($card['id']);
            $card['uid'] = floatval($card['uid']);
            $card['empresa'] = floatval($card['empresa']);
            $card['ccv'] = floatval($card['ccv']);
            $card['mes_expiriacion'] = floatval($card['mes_expiriacion']);
            $card['ano_expiriacion'] = floatval($card['ano_expiriacion']);
            $card['tipo'] = floatval($card['tipo']);
            $card['fecha_creacion'] = $card['fecha_creacion'];
            $card['fecha_actualizacion'] = $card['fecha_actualizacion'];

            $tarjetas[$x] = $card;
        }

        return $tarjetas;
    }
}
?>