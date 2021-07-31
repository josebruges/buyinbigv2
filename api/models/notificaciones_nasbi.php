<?php

class Notificaciones
{
    public function insertarNotificacion(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['text']) || !isset($data['es']) || !isset($data['en']) || !isset($data['keyjson']) || !isset($data['url'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $fecha = intval(microtime(true)*1000);
        $conexion = new Conexion();
        $conexion->conectar();
        $insertarxnotificaciones = "INSERT INTO notificaciones
        (
            text,
            keyjson,
            es,
            en,
            url,
            uid,
            empresa,
            leida,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[text]',
            '$data[keyjson]',
            '$data[es]',
            '$data[en]',
            '$data[url]',
            '$data[uid]',
            '$data[empresa]',
            '0',
            '1',
            '$fecha',
            '$fecha'
        );";
        $notificaciones = $conexion->query($insertarxnotificaciones);
        $conexion->cerrar();
        unset($conexion);
        if(!$notificaciones) return array('status' => 'fail', 'message'=> 'no se pudo completar la solicitud', 'data' => null);

        return array('status' => 'success', 'message'=> 'solicitud completa', 'data' => null);
    }

    public function verNotificacionesUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        
        $conexion = new Conexion();
        $conexion->conectar();
        $selectxnotificaciones = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT n.* 
                FROM notificaciones n
                JOIN (SELECT @row_number := 0) r
                WHERE n.uid = '$data[uid]' AND n.empresa = '$data[empresa]'
                ORDER BY fecha_creacion DESC
            )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $notificaciones = $conexion->consultaTodo($selectxnotificaciones);
        if(count($notificaciones) <= 0){
            $conexion->cerrar();
            unset($conexion);
            return array('status' => 'success', 'message'=> 'mis notificaciones', 'pagina'=> $pagina, 'total_paginas'=>0, 'notificaciones' => 0, 'total_notificaciones' => 0, 'no_leidas' => 0, 'data' => null);
        }

        $selecttodos = "SELECT COUNT(n.id) AS contar FROM notificaciones n WHERE n.uid = '$data[uid]' AND n.empresa = '$data[empresa]' ORDER BY fecha_creacion DESC;";
        $todaslasnotificaciones = $conexion->consultaTodo($selecttodos);
        $selectxnoleidas = "SELECT COUNT(n.id) AS contar FROM notificaciones n WHERE n.uid = '$data[uid]' AND n.empresa = '$data[empresa]' AND leida = 0 ORDER BY fecha_creacion DESC;";
        $noleidas = $conexion->consultaTodo($selectxnoleidas);
        $noleidas = floatval($noleidas[0]['contar']);
        $todaslasnotificaciones = floatval($todaslasnotificaciones[0]['contar']);
        $totalpaginas = $todaslasnotificaciones/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $conexion->cerrar();
        unset($conexion);
        return array('status' => 'success', 'message'=> 'mis notificaciones', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'notificaciones' => count($notificaciones), 'total_notificaciones' => $todaslasnotificaciones, 'no_leidas' => $noleidas, 'data' => $notificaciones);
    }

    public function notificacionLeida(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $notificacion = $this->notificacionId($data);
        if($notificacion['status'] != 'success') return $notificacion;

        $fecha = intval(microtime(true)*1000);
        $conexion = new Conexion();
        $conexion->conectar();
        $supdatenotificacion = "UPDATE notificaciones
        SET leida = 1,
        fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
        $update = $conexion->query($supdatenotificacion);
        $conexion->cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'notificacion no leida ', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'notificacion leida ', 'data' => $notificacion["data"]);
    }

    function notificacionId(Array $data)
    {
        $conexion = new Conexion();
        $conexion->conectar();
        $selectxnotificaciones = "SELECT n.* 
        FROM notificaciones n
        WHERE n.id = '$data[id]' AND n.uid = '$data[uid]' AND n.empresa = '$data[empresa]'";
        $notificaciones = $conexion->consultaTodo($selectxnotificaciones);
        $conexion->cerrar();
        if(count($notificaciones) <= 0) return array('status' => 'fail', 'message'=> 'notificacion no existe ', 'data' => null);

        return array('status' => 'success', 'message'=> 'notificacion no existe ', 'data' => $notificaciones[0]);
    }
}
?>