<?php
require 'conexion.php';
require '../../Shippo.php';

class Direcciones extends Conexion
{
    public function crear(Array $data){
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $dane; 

        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['pais']) || !isset($data['pais_isocode2']) || !isset($data['departamento']) || !isset($data['departamento_isocode2']) || !isset($data['ciudad']) || !isset($data['latitud']) || !isset($data['longitud']) || !isset($data['codigo_postal']) || !isset($data['direccion']) || !isset($data['activa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $data = $this->mapAddDireccion($data);
        $direcciones_usario = $this->direccionUsuario($data);
        if($direcciones_usario['cantidad'] >= 3) return array('status' => 'maxDirecciones', 'message'=> 'el usuario alcanzo el maximo de direcciones', 'data' => null);

        $activa = 0;
        if($direcciones_usario['cantidad'] == 0) $activa = 1;
        if($data['activa'] == 1 && $direcciones_usario['cantidad'] > 0){
            $desactivar = $this->desactivarDirecciones($data);
            if($desactivar['status'] == 'fail') return $desactivar;
            $activa = 1;
        }
        
        //dane ciudad 
        if(isset($data['id_dane']) &&  $data['id_dane']!=""){
            $dane= $data['id_dane']; 
        }else{
            $dane= $data['id_dane']=""; 
        }
        // fin dane ciudad 

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;
        $datos_usuario = $datos_usuario['data'];

        parent::conectar();
        $insertdireccion = "INSERT INTO direcciones(
            uid,
            empresa,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            activa,
            estado,
            fecha_creacion,
            fecha_actualizacion, 
            dane
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[pais]',
            '$data[departamento]',
            '$data[ciudad]',
            '$data[latitud]',
            '$data[longitud]',
            '$data[codigo_postal]',
            '$data[direccion]',
            '$activa',
            '1',
            '$fecha',
            '$fecha',
            '$dane'
        )";
        $direccion = parent::queryRegistro($insertdireccion);
        parent::cerrar();
        if(!$direccion) return array('status' => 'fail', 'message'=> 'no se guardo la direccion', 'data' => null);
        
        return $this->saveShippo([
            'name' => $datos_usuario['nombre'],
            'company' => $datos_usuario['empresa'],
            'street1' => $data['direccion'],
            'city' => $data['ciudad'],
            'state' => $data['departamento_isocode2'],
            'zip' => $data['codigo_postal'],
            'country' => $data['pais_isocode2'],
            'longitude' => $data['latitud'],
            'latitude' => $data['longitud'],
            'phone' => $datos_usuario['telefono'],
            'email' => $datos_usuario['correo'],
            'metadata' => 'ID direccion '.$direccion,
            'id' => $direccion,
            'uid' => $data['uid'],
            'empresa' => $data['empresa'],
        ]);
    }

    public function direccionUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectdireccion = 
        "SELECT
            d.*,
            ct.id AS 'TCC_id',
            ct.CLASIFICACION_GEOGRAFICA AS 'TCC_CLASIFICACION_GEOGRAFICA',
            ct.CODIGO_SION AS 'TCC_CODIGO_SION',
            ct.COP AS 'TCC_COP',
            ct.DANE AS 'TCC_DANE',
            ct.DANE_DEPARTAMENT AS 'TCC_DANE_DEPARTAMENT',
            ct.DEPARTAMENTO AS 'TCC_DEPARTAMENTO',
            ct.ESTADO AS 'TCC_ESTADO',
            ct.FCE AS 'TCC_FCE',
            ct.PAIS AS 'TCC_PAIS',
            ct.POBLACION AS 'TCC_POBLACION',
            ct.REEXPEDIDA AS 'TCC_REEXPEDIDA',
            ct.TIPO_GEOGRAFIA AS 'TCC_TIPO_GEOGRAFIA',

            ct.CITYCODE,
            ct.STATECODE,
            ct.COUNTRYCODE

        FROM direcciones d
        INNER JOIN  ciudades_tcc ct ON (d.dane = ct.dane)
        WHERE d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' AND d.estado = 1
        ORDER BY fecha_creacion DESC";

        $direcciones = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($direcciones) <= 0) return array('status' => 'fail', 'message'=> 'no tine direcciones', 'cantidad'=> 0,'data' => null);
            
        $direcciones = $this->mapDirecciones($direcciones);
        return array('status' => 'success', 'message'=> 'direcciones', 'cantidad'=> count($direcciones),'data' => $direcciones);
    }

    public function eliminar(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id']) || !isset($data['activa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletedireccion = "UPDATE direcciones SET activa = 0, estado = 0, fecha_actualizacion = '$fecha' WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $eliminar = parent::query($deletedireccion);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no se elimino la direccion', 'data' => null);

        if($data['activa'] == 0) return array('status' => 'success', 'message'=> 'direccion eliminada', 'data' => null);

        $direcciones_usario = $this->direccionUsuario($data);
        if($direcciones_usario['cantidad'] <= 0) return array('status' => 'success', 'message'=> 'direccion eliminada', 'data' => null);
        
        $direccion_reciente = $direcciones_usario['data'][0];
        $direccion_reciente['fecha'] = $fecha;
        $this->activarDireccionId($direccion_reciente);

        return array('status' => 'success', 'message'=> 'direccion eliminada', 'data' => null);
    }

    public function actualizar(Array $data){
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['pais'])|| !isset($data['departamento'])|| !isset($data['ciudad'])|| !isset($data['latitud'])|| !isset($data['longitud'])|| !isset($data['codigo_postal'])|| !isset($data['direccion']) || !isset($data['activa'])) return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);
        $data = $this->mapAddDireccion($data);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        if($data['activa'] == 1){
            $desactivar = $this->desactivarDirecciones($data);
            if($desactivar['status'] == 'fail') return $desactivar;
        }
        //dane ciudad 
          if(isset($data['id_dane']) &&  $data['id_dane']!=""){
            $dane= $data['id_dane']; 
        }else{
            $dane= $data['id_dane']=""; 
        }
        // fin dane ciudad 

        parent::conectar();
        $updatedireccion = "UPDATE direcciones
        SET
            pais = '$data[pais]',
            departamento = '$data[departamento]',
            ciudad = '$data[ciudad]',
            latitud = '$data[latitud]',
            longitud = '$data[longitud]',
            codigo_postal = '$data[codigo_postal]',
            direccion = '$data[direccion]',
            activa = '$data[activa]',
            estado = 1,
            fecha_actualizacion = '$fecha',
            dane = '$dane'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
        $actualizar = parent::query($updatedireccion);
        parent::cerrar();
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo la direccion', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;
        $datos_usuario = $datos_usuario['data'];
        
        return $this->saveShippo([
            'name' => $datos_usuario['nombre'],
            'company' => $datos_usuario['empresa'],
            'street1' => $data['direccion'],
            'city' => $data['ciudad'],
            'state' => $data['departamento_isocode2'],
            'zip' => $data['codigo_postal'],
            'country' => $data['pais_isocode2'],
            'longitude' => $data['latitud'],
            'latitude' => $data['longitud'],
            'phone' => $datos_usuario['telefono'],
            'email' => $datos_usuario['correo'],
            'metadata' => 'ID direccion '.$data['id'],
            'id' => $data['id'],
            'uid' => $data['uid'],
            'empresa' => $data['empresa'],
        ]);
    }

    public function activarDireccion(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        $desactivar = $this->desactivarDirecciones($data);
        if($desactivar['status'] == 'fail') return $desactivar;

        return $this->activarDireccionId($data);
    }

    function desactivarDirecciones(Array $data)
    {
        parent::conectar();
        $desactivarxdir = "UPDATE direcciones SET activa = 0, fecha_actualizacion = '$data[fecha]' WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $desacivar = parent::query($desactivarxdir);
        parent::cerrar();
        if(!$desacivar) return array('status' => 'fail', 'message'=> 'no se desactivaron las otras direcciones', 'data' => null);

        return array('status' => 'success', 'message'=> 'direcciones desactivadas', 'data' => null);
    }

    function activarDireccionId(Array $data)
    {
        parent::conectar();
        $activarxdir = "UPDATE direcciones SET activa = 1, estado = 1, fecha_actualizacion = '$data[fecha]' WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $activar = parent::query($activarxdir);
        parent::cerrar();
        if(!$activar) return array('status' => 'fail', 'message'=> 'no se activo la direccion', 'data' => null);

        return array('status' => 'success', 'message'=> 'direccion activada', 'data' => null);
    }

    function datosUser(Array $data)
    {
        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios($usuario, $data['empresa']);
        
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }

    function saveShippo(Array $data)
    {
        // $fromAddress = (array) json_decode(Shippo_Address::create( array(
        //     'name' => $data['name'],
        //     'company' => $data['company'],
        //     'street1' => $data['street1'],
        //     'city' => $data['city'],
        //     'state' => $data['state'],
        //     'zip' => $data['zip'],
        //     'country' => $data['country'],
        //     'longitude' => $data['longitude'],
        //     'latitude' => $data['latitude'],
        //     'phone' => $data['phone'],
        //     'email' => $data['email'],
        //     'metadata' => $data['metadata'],
        //     "validate" => true
        // )));
        // // echo (json_encode($fromAddress));
        // parent::conectar();
        // $updatedireccion = "UPDATE direcciones
        // SET
        //     id_shippo = '$fromAddress[object_id]'
        // WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
        // $actualizar = parent::query($updatedireccion);
        // parent::cerrar();
        // if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo la direccion', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'direccion actualizada', 'data' => null);
    }

    function mapAddDireccion(Array $data)
    {
        $data['uid'] = floatval(addslashes($data['uid']));
        $data['empresa'] = floatval(addslashes($data['empresa']));
        $data['pais'] = floatval(addslashes($data['pais']));
        $data['departamento'] = floatval(addslashes($data['departamento']));
        $data['ciudad'] = addslashes($data['ciudad']);
        $data['latitud'] = floatval(addslashes($data['latitud']));
        $data['longitud'] = floatval(addslashes($data['longitud']));
        $data['codigo_postal'] = addslashes($data['codigo_postal']);
        $data['direccion'] = addslashes($data['direccion']);
        $data['activa'] = addslashes($data['activa']);
        if(isset($data['id'])) $data['id'] = floatval(addslashes($data['id']));

        return $data;
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

    function mapUsuarios(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = "Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
            }else if($empresa == 1){
                $datanombre = $user['nombre_dueno'].' '.$user['apellido_dueno'];
                $dataempresa = $user['nombre_empresa'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $usuarios[$x] = $user;
        }

        return $usuarios;
    }
}
?>