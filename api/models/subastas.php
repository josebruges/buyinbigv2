<?php
// require 'conexion.php';
require 'Nodo.php';

class Subastas extends Conexion
{
    public function consumirEntradas($usuario, $tickets, $tipo)
    {
        parent::conectar();
        $fechaFin = getdate();
        $fecha    = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
        if (!$this->comprobarTickets($usuario, $tickets, $tipo)) {
            return false;
        }
        $consultar1 = "select * from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and fecha >= CURRENT_DATE order by fecha";
        $lista      = parent::consultaTodo($consultar1);
        // print_r($lista);
        for ($i = 0; $i < count($lista); $i++) {
            if ($tickets >= $lista[$i]['cantidad']) {
                $tickets -= $lista[$i]['cantidad'];
                $consultar2 = "update historico_tickets set cantidad = 0 where id_tickets = {$lista[$i]["id_tickets"]}";
                parent::queryRegistro($consultar2);
            } else if ($tickets < $lista[$i]['cantidad']) {
                $consultar2 = "update historico_tickets set cantidad = (cantidad - " . $tickets . ") where id_tickets = " . $lista[$i]['id_tickets'];
                parent::queryRegistro($consultar2);
                $tickets = 0;
            }else{
            }
            if ($tickets == 0) {
                break;
            }
        }
        $this->comprobarTickets($usuario, 0, $tipo);
        return true;
    }

    public function comprobarTickets($usuario, $tickets, $tipo)
    {
        parent::conectar();
        $consultar1 = parent::consultaTodo("select ifnull(sum(cantidad), 0) cantidad from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and fecha >= CURRENT_DATE order by fecha");
        parent::queryRegistro("update usuarios set $tipo = {$consultar1[0]["cantidad"]} where id = $usuario");
        return $tickets <= $consultar1[0]["cantidad"];
    }

    public function comprarEntradas($datos)
    {
        
        /*
        DATA QUE RECIBE
        uid = int => id del usuario
        tipo = string
        cantidad = int
        idSubasta = int
        */
        parent::conectar();
        $consultar1 = "select * from usuarios where id = $datos[uid] and $datos[tipo] > 0";
        $lista      = parent::consultaTodo($consultar1);
        // print_r($lista);
        if (count($lista) > 0) {
            $tipo       = $lista[0][$datos['tipo']];
            $resta      = $tipo - $datos['cantidad'];
            $consultar2 = "update usuarios set $datos[tipo] = '$resta' where id = $datos[uid]";
            if (!$this->consumirEntradas($datos['uid'], $datos['cantidad'], $datos['tipo'])) {
                $respuesta = array('status' => 'noTicketsTotal', 'message' => 'El usuario no le alcanzan los tickets para esta subasta');
            } else {
                parent::query($consultar2);
                
                $this->sumarInscritosSubasta($datos['idSubasta'], $datos['uid'], $datos['cantidad'], $datos['categoria']);
                // $this->cambiarStatusUser($datos['idSubasta'], $datos['uid'],1);
                // $this->cambiarStatusSubasta($datos['idSubasta'], 1, "por postor"); SE CAMBIO TEMPORAL 
                // $this->agregarPujaSugerida($datos['idSubasta']); SE CAMBIO TEMPORAL
                $respuesta = array('status' => 'success', 'message' => "Entradas compradas");
            }
            
        } else {
            $respuesta = array('status' => 'noTickets', 'message' => 'El usuario no tiene tickets para esta subasta');
        }
        //  parent::cerrar();
        return $respuesta;
    }

    public function cambiarStatusUser($idSubasta, $uid, $status)
    {
        parent::conectar();
        if ((isset($idSubasta) && !empty($idSubasta)) && (isset($uid) && !empty($uid))) {
            $consultar1 = "update inscritos set status = '$status' where subastas_id = '$idSubasta' and usuarios_id = '$uid'";
            parent::query($consultar1);
            return true;
        } else {
            return false;
        }
        
    }

    public function cambiarStatusSubasta($idSubasta, $status, $nombreStatus)
    {
        parent::conectar();
        $consultar1 = "select * from subastas where id = $idSubasta";
        $lista      = parent::consultaTodo($consultar1);
        
        
        $consultar1 = "update subastas set status = '$status', estado = '$nombreStatus' where id = '$idSubasta'";
        $datos      = parent::query($consultar1);
        return true;
    }

    public function cronSubasta()
    {
        parent::conectar();
        $consultar1 = "select *, date_add(CURRENT_TIMESTAMP, interval 24 hour) tiempoactual, unix_timestamp(date_add(CURRENT_TIMESTAMP, interval 24 hour)) timeinicio from subastas where status = 0";
        $lista      = parent::consultaTodo($consultar1);
        
        for ($i=0; $i < count($lista); $i++) {
            $porcentaje = 0;
            $monto = 0;
            $monto =  $lista[$i]["inscritos"] * $lista[$i]["costo_tickets"];
            $porcentaje = $this->calcularPorcentaje($lista[$i]["price"], $monto);
            if (($lista[$i]["categoria"] == "Bronce" && $lista[$i]["inscritos"] >= 21) ||
            ($lista[$i]["categoria"] == "Plata" && $lista[$i]["inscritos"] >= 25) ||
            ($lista[$i]["categoria"] == "Gold" && $lista[$i]["inscritos"] >= 25) ||
            ($lista[$i]["categoria"] == "Platino" && $lista[$i]["inscritos"] >= 58) ||
            ($lista[$i]["categoria"] == "Diamante" && $lista[$i]["inscritos"] >= 58) ) {
                $consultar2 = "update subastas set status = 1, estado = 'por postor' where id =".$lista[$i]['id'];
                parent::queryRegistro($consultar2);
                $this->agregarPujaSugeridaCron($lista[$i]['id'],$lista[$i]["price"], $monto,$lista[$i]["inscritos"],$lista[$i]["categoria"]);
                $consultar4 = "INSERT INTO subastas (id,categoria,fecha,price,status,estado,apostadores,inscritos,fecha_fin,fecha_inicio,costo_tickets) VALUES (null,'{$lista[$i]['categoria']}',CURRENT_TIMESTAMP,{$lista[$i]['price']},0,'inscribiendo',{$lista[$i]['apostadores']},0,null,'{$lista[$i]["tiempoactual"]}',{$lista[$i]['costo_tickets']})";
                parent::queryRegistro($consultar4);
                // $respuesta = array('status' => 'success', 'message' => "consulta exitosa", 'data'=> $data, 'hola' => $lista[$i]);
            }else{
                parent::queryRegistro("update subastas set fecha_inicio = '{$lista[$i]["tiempoactual"]}' where id =".$lista[$i]['id']);
            }
        }
        $respuesta = array('status' => 'success', 'message' => "consulta exitosa");
        return $respuesta;
    }

    public function calcularPorcentaje($valor, $montoTicket)
    {
        $porcentajeTotal = ($montoTicket * 100) / $valor;
        return $porcentajeTotal;
    }

    public function agregarPujaSugeridaCron($idSubasta, $valor, $montoTicket, $inscritos, $categoria)
    {
        $nodo = new Nodo();
        parent::conectar();
        $consultar1 = "select * from subastas where id = $idSubasta";
        $consultar2 = "select * from pujas where subastas_id=$idSubasta";
        
        $lista      = parent::consultaTodo($consultar1);
        $lista2     = parent::consultaTodo($consultar2);
        
        $preciousd  = $nodo->precioUSD();
        
        if (count($lista2) == 0) {
            if (($categoria == "Bronce" && $inscritos >= 21 && $inscritos < 27) ||
            ($categoria == "Plata" && $inscritos >= 25 && $inscritos < 32) ||
            ($categoria == "Gold" && $inscritos >= 25 && $inscritos < 32) ||
            ($categoria == "Platino" && $inscritos >= 58 && $inscritos < 75) ||
            ($categoria == "Diamante" && $inscritos >= 58 && $inscritos < 75)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (90 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3);
                
            }else if (($categoria == "Bronce" && $inscritos >= 27 && $inscritos < 33) ||
            ($categoria == "Plata" && $inscritos >= 32 && $inscritos < 39) ||
            ($categoria == "Gold" && $inscritos >= 32 && $inscritos < 39) ||
            ($categoria == "Platino" && $inscritos >= 75 && $inscritos < 92) ||
            ($categoria == "Diamante" && $inscritos >= 75 && $inscritos < 92)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (80 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3);
                
            }else if (($categoria == "Bronce" && $inscritos >= 33 && $inscritos < 39) ||
            ($categoria == "Plata" && $inscritos >= 39 && $inscritos < 46) ||
            ($categoria == "Gold" && $inscritos >= 39 && $inscritos < 46) ||
            ($categoria == "Platino" && $inscritos >= 92 && $inscritos < 108) ||
            ($categoria == "Diamante" && $inscritos >= 92 && $inscritos < 108)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (70 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3);
                
            }else if (($categoria == "Bronce" && $inscritos >= 39 && $inscritos < 45) ||
            ($categoria == "Plata" && $inscritos >= 46 && $inscritos < 54) ||
            ($categoria == "Gold" && $inscritos >= 46 && $inscritos < 54) ||
            ($categoria == "Platino" && $inscritos >= 108 && $inscritos < 125) ||
            ($categoria == "Diamante" && $inscritos >= 108 && $inscritos < 125)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (60 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3); 
                
            }else if (($categoria == "Bronce" && $inscritos >= 45 && $inscritos < 51) ||
            ($categoria == "Plata" && $inscritos >= 54 && $inscritos < 61) ||
            ($categoria == "Gold" && $inscritos >= 54 && $inscritos < 61) ||
            ($categoria == "Platino" && $inscritos >= 125 && $inscritos < 142) ||
            ($categoria == "Diamante" && $inscritos >= 125 && $inscritos < 142)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (50 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3); 
                
            }else if (($categoria == "Bronce" && $inscritos >= 51) ||
            ($categoria == "Plata" && $inscritos >= 61) ||
            ($categoria == "Gold" && $inscritos >= 61) ||
            ($categoria == "Platino" && $inscritos >= 142) ||
            ($categoria == "Diamante" && $inscritos >= 142)) {
                
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (40 / 100);
                $consultar3 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar3);  
                
            }
            return true;
            
        }else{
            return false;
        }
    }

    public function agregarPujaSugerida($idSubasta)
    {
        $nodo = new Nodo();
        parent::conectar();
        $consultar1 = "select * from subastas where id = $idSubasta";
        $consultar2 = "select * from pujas where subastas_id=$idSubasta";
        $lista      = parent::consultaTodo($consultar1);
        $lista2     = parent::consultaTodo($consultar2);
        
        if ($lista[0]["inscritos"] >= $lista[0]["apostadores"]) {
            if (count($lista2) == 0) {
                $preciousd  = $nodo->precioUSD();
                $lista      = parent::consultaTodo($consultar1);
                $operacion  = $lista[0]["price"] / $preciousd;
                $total      = $operacion * (30 / 100);
                $consultar2 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES(null,0," . $idSubasta . "," . $total . ",'sugerido')";
                $datos      = parent::query($consultar2);
                return true;
            } else {
                return false;
            }
            
        } else {
            return false;
        }
        
    }

    public function sumarInscritosSubasta($idSubasta, $uid, $cantidad, $categoria)
    {
        parent::conectar();
        $consultar1 = "select * from subastas where categoria = '$categoria' and id = '$idSubasta'";
        $lista      = parent::consultaTodo($consultar1);
        // print_r($lista);
        if (count($lista) > 0) {
            if ($categoria == "Diamante") {
                $cantidad = $cantidad / 2;
            }
            $consultar2 = "update subastas set inscritos = (inscritos + $cantidad) where categoria = '$categoria' and id = $idSubasta";
            $consultar3 = "INSERT INTO inscritos (usuarios_id, subastas_id, cantidad, status) VALUES ('$uid', '$idSubasta', '$cantidad', 1)";
            parent::query($consultar2);
            parent::query($consultar3);
            return true;
        } else {
            return false;
        }
    }
    /* parent::cerrar(); */

    public function getSubastas($datos)
    {
        $nodo = new Nodo();
        parent::conectar();
        $consultar1 = "select *, ifnull(unix_timestamp(fecha_fin)*1000, 0) termina, ifnull(unix_timestamp(fecha_inicio)*1000, 0) inicio from subastas where status != 3";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $preciousd = $nodo->precioUSD();
            if (isset($datos['uid']) && !empty($datos['uid'])) {
                for ($i = 0; $i < count($lista); $i++) {
                    $consulta2 = "select inscritos.status from usuarios
                    left join inscritos
                    on inscritos.usuarios_id = usuarios.id
                    left join subastas
                    on subastas.id = inscritos.subastas_id
                    where subastas.id = " . $lista[$i]['id'] . " and usuarios.id = $datos[uid]";
                    $data = parent::consultaTodo($consulta2);
                    if (isset($data) && !empty($data)) {
                        $lista[$i]["status_user"] = $data[0]["status"];
                    } else {
                        $lista[$i]["status_user"] = 0;
                    }
                    $lista[$i]["precioBTC"] = $lista[$i]["price"] / $preciousd;
                    $lista[$i]["precioUSD"] = $preciousd;
                    
                }
                $respuesta = array('status' => 'success', 'message' => "consulta exitosa", 'data' => $lista);
            } else {
                for ($i = 0; $i < count($lista); $i++) {
                    $lista[$i]["precioBTC"] = $lista[$i]["price"] / $preciousd;
                    $lista[$i]["precioUSD"] = $preciousd;
                }
                $respuesta = array('status' => 'success', 'message' => "consulta exitosa", 'data' => $lista);
                
            }
            
        } else {
            $respuesta = array('status' => 'error', 'message' => 'No existen subastas disponibles');
        }
        parent::cerrar();
        return $respuesta;
    }

    public function pujar($datos)
    {
        parent::conectar();
        $consultar1 = "select *, date_add(CURRENT_TIMESTAMP, interval 45 second) tiempoactual, unix_timestamp(date_add(CURRENT_TIMESTAMP, interval 45 second)) timefinal from pujas where subastas_id = $datos[idSubasta]";
        // echo $consultar1;
        $lista     = parent::consultaTodo($consultar1);
        $respuesta = array();
        if (count($lista) > 0) {
            if (count($lista) == 1) {
                $consultar3 = "select * from subastas where id = $datos[idSubasta]";
                $lista2     = parent::consultaTodo($consultar3);
                $this->cambiarStatusSubasta($datos['idSubasta'], 2, "activo");
                // $consultar4 = "INSERT INTO subastas (id,categoria,fecha,price,status,estado,apostadores,inscritos,fecha_fin) VALUES (null,'{$lista2[0]['categoria']}',CURRENT_TIMESTAMP,{$lista2[0]['price']},0,'inscribiendo',{$lista2[0]['apostadores']},0,null)";
                // parent::query($consultar4); SE QUITO TEMPORAL
                
            }
            $consultar2 = "INSERT INTO pujas (idpujas,usuarios_id,subastas_id,monto,nombre) VALUES (null,$datos[uid],$datos[idSubasta],$datos[monto],'$datos[nombre]')";
            // parent::query($consultar2);
            parent::queryRegistro("update subastas set fecha_fin = '{$lista[0]["tiempoactual"]}' where id = $datos[idSubasta]");
            // echo "update subastas set fecha_fin = {$lista[0]["tiempoactual"]} where id = $datos[idSubasta]";
            $insertid  = parent::queryRegistro($consultar2);
            $data      = $this->dataPujas($datos['idSubasta']);
            $respuesta = array('status' => 'success', 'data' => $data, 'message' => 'puja exitosa', "monto" => $datos["monto"], "subasta" => $datos["idSubasta"], "timefinal" => $lista[0]["timefinal"] * 1000, "lastInsert" => $insertid);
            // print_r($respuesta);
        } else {
            $respuesta = array('status' => 'error', 'message' => 'noexiste');
        }
        return $respuesta;
    }

    public function dataPujas($idSubasta)
    {
        parent::conectar();
        $consultar1 = "select pujas.nombre as nombre, pujas.monto from subastas inner join pujas on subastas.id = pujas.subastas_id where subastas_id = $idSubasta";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $respuesta = $lista;
        } else {
            $respuesta = false;
        }
        return $respuesta;
        
    }

    public function getSaldoDisponible($datos)
    {
        parent::conectar();
        $nodo       = new Nodo();
        $preciousd  = $nodo->precioUSD();
        $consultar1 = "select saldo from address_bloqueadas where id_usuario = $datos[uid] and id_subasta = $datos[idSubasta]";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            for ($i = 0; $i < count($lista); $i++) {
                if ($lista[$i]["saldo"] >= 0.0002) {
                    $lista[$i]["saldo"] = $lista[$i]["saldo"] - 0.0002;
                } else {
                    $lista[$i]["saldo"] = 0.000000;
                }
                $lista[$i]["saldoUsd"] = $lista[$i]["saldo"] * $preciousd;
                // $lista["saldoTotal"] = $lista["saldoTotal"] + $lista[$i]["saldo"];
            }
            $respuesta = array('status' => 'success', 'message' => 'consulta exitosa', 'data' => $lista);
        } else {
            $respuesta = array('status' => 'error', 'message' => 'errorParametros');
        }
        return $respuesta;
        
    }

    public function getPujas($datos)
    {
        parent::conectar();
        // $consultar1 = "select usuarios.nombreCompleto as nombre, pujas.monto from usuarios
        // inner join pujas
        // on pujas.usuarios_id = usuarios.id
        // inner join subastas
        // on subastas.id = pujas.subastas_id
        // where subastas.id = $datos[idSubasta]";
        $consultar1 = "select pujas.nombre as nombre, pujas.monto from subastas inner join pujas on subastas.id = pujas.subastas_id where subastas_id = $datos[idSubasta]";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $respuesta = array('status' => 'success', 'message' => "consulta exitosa", 'data' => $lista);
        } else {
            $respuesta = array('status' => 'noPujas', 'message' => "no existe pujas en esta subasta");
        }
        return $respuesta;
        
    }

    public function bloquearAddress($datos)
    {
        parent::conectar();
        parent::query("SET SQL_SAFE_UPDATES=0");
        $condition  = implode("','", $datos['address']);
        $consultar1 = "select * from address_btc where address IN ('$condition')";
        $lista      = parent::consultaTodo($consultar1);
        if (!empty($lista)) {
            
            $consultar2 = "insert into address_bloqueadas (id, id_address_btc, id_usuario, address, label, saldo, id_subasta)(select null id, id id_address_btc, usuario id_usuario, address, label, saldo, {$datos["id_subasta"]} id_subasta from address_btc where address in ('$condition'))";
            $data       = parent::query($consultar2);
            if ($data == true || $data == 'true') {
                $consultar3 = "update address_btc set saldo = 0 where address IN ('$condition')";
                parent::query($consultar3);
                $this->cambiarStatusUser($datos['id_subasta'], $datos['uid'], 2);
                $respuesta = array('status' => 'success', 'message' => "consulta exitosa", "data" => $data);
            } else {
                $respuesta = array('status' => 'errorConsulta', 'message' => "error en la consulta");
            }
            
        } else {
            $respuesta = array('status' => 'noExiste', 'message' => "invalidAddress");
        }
        return $respuesta;
        
    }

    public function devolverSaldo($idSubasta)
    {
        // $idSubasta = 1
        parent::conectar();
        parent::query("SET SQL_SAFE_UPDATES=0");
        $consultar1 = "select * from address_bloqueadas where id_subasta = $idSubasta";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            for ($i = 0; $i < count($lista); $i++) {
                $consultar2 = "update address_btc set saldo = (saldo + " . $lista[$i]['saldo'] . ") where address = '{$lista[$i]['address']}'";
                $data       = parent::query($consultar2);
            }
            if ($data == true) {
                $consultar3 = "DELETE FROM address_bloqueadas WHERE id_subasta = $idSubasta";
                $data2      = parent::query($consultar3);
                return $data2;
                
            }
        } else {
            return false;
        }
    }

    public function getHistorico($datos)
    {
        // $idSubasta = 1
        parent::conectar();
        $nodo = new Nodo();
        parent::query("SET SQL_SAFE_UPDATES=0");
        // $consultar1 = "select if(ganadores.id_usuario = $datos[uid], 1, 0) soyganador, subastas.* from subastas
        // inner join ganadores on ganadores.id_subasta = subastas.id where ganadores.id_usuario = $datos[uid]";
        $consultar1 = "SELECT s.*, hp.nombre,
        (SELECT nombre FROM peer2win.historico_pujas WHERE subastas_id = s.id ORDER BY idpujas DESC LIMIT 1) AS nombre_ganador
        FROM peer2win.subastas s
        INNER JOIN peer2win.historico_pujas hp ON s.id = hp.subastas_id
        WHERE hp.usuarios_id = $datos[uid]
        GROUP BY s.id
        ORDER BY s.fecha_fin , hp.idpujas";
        $lista = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $preciousd = $nodo->precioUSD();
            
            for ($i = 0; $i < count($lista); $i++) {
                if ($lista[$i]["nombre_ganador"] == $lista[$i]["nombre"]) {
                    $lista[$i]["soyganador"] = 1;
                }else{
                    $lista[$i]["soyganador"] = 0;
                }
                $lista[$i]["precioBTC"]  = $preciousd;
                $lista[$i]["totalPrice"] = $lista[$i]["price"] / $preciousd;
            }
            $respuesta = array('status' => 'success', 'data' => $lista);
            
        } else {
            $respuesta = array('status' => 'noData', 'message' => 'el usuario no ha participado en subastas');
        }
        return $respuesta;
    }

    public function getGanadores()
    {
        parent::conectar();
        $nodo       = new Nodo();
        $consultar1 = "select ganadores.*, usuarios.foto from ganadores
        inner join usuarios
        on usuarios.id = ganadores.id_usuario";
        $lista = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $preciousd = $nodo->precioUSD();
            for ($i = 0; $i < count($lista); $i++) {
                $lista[$i]["precioBTC"] = $preciousd;
                $lista[$i]["premioBTC"] = $lista[$i]["premio"] / $preciousd;
            }
            $respuesta = array('status' => 'success', 'data' => $lista);
            
        } else {
            $respuesta = array('status' => 'noData', 'message' => 'Aun no existen ganadores');
        }
        return $respuesta;
        
    }

    public function agregarGanadores($idSubasta)
    {
        // $idSubasta = 1
        parent::conectar();
        parent::query("SET SQL_SAFE_UPDATES=0");
        $consultar1 = "select  historico_pujas.*, subastas.price as premio, subastas.categoria from subastas
        inner join historico_pujas
        on historico_pujas.subastas_id = subastas.id
        where subastas.id = $idSubasta";
        $consultarGanadores = "select * from ganadores where id_subasta = $idSubasta";
        $lista      = parent::consultaTodo($consultar1);
        $lista2     = parent::consultaTodo($consultarGanadores);
        if (count($lista2) <= 0) {
            $ultimo     = end($lista);
            $consultar2 = "INSERT INTO ganadores (id_subasta,nombre,montoPuja,premio,categoria,id_usuario,txhash) VALUES ($ultimo[subastas_id],'$ultimo[nombre]',$ultimo[monto],$ultimo[premio],'$ultimo[categoria]',$ultimo[usuarios_id],'esperando')";
            $data       = parent::query($consultar2);
            $pago = $this->pagarSubasta($ultimo['usuarios_id'], $ultimo["monto"], $ultimo["premio"], $idSubasta);
            
            
        }
        return true;
    }

    public function pagarSubasta($user,$amount,$premio,$idSubasta)
    {   
        $addressAPagar = "2N3NxmDVJyh6R7zpvMtBw837D4zkuTZHDXh";
        $addressParaPagar= "2N3NxmDVJyh6R7zpvMtBw837D4zkuTZHDXh";
        parent::conectar();
        parent::query("SET SQL_SAFE_UPDATES=0");
        $consultar = parent::consultaTodo("select * from address_btc ab  where usuario  = $user order by id asc"); // AQUI COGE LAS DOS ADDRESS PERO NO SE COMO SERIA PARA SACARLE EL SALDO :V
        //$consultar[0]["address"];
        $nodo = new Nodo();
        $preciousd  = $nodo->precioUSD();
        $operacion  = $premio / $preciousd;
        $operacion = number_format(($operacion), 6) * 1;
        $cobroPuja = $nodo->sendTransactionByOne(array("address_from"=> $consultar[0]["address"], "address_to"=>$addressAPagar, "amount"=>$amount, "reason"=>"Cobro por ganar subasta #".$idSubasta, "usuario"=>$user));
        if ($cobroPuja["status"] != "success") {
            $cobroPuja = $nodo->sendTransactionByOne(array("address_from"=> $consultar[1]["address"], "address_to"=>$addressAPagar, "amount"=>$amount, "reason"=>"Cobro por ganar subasta #".$idSubasta, "usuario"=>$user));
        }
        $cobrouser =  1 * (number_format(($operacion * 0.03), 6));
        $pagoUsuario = $operacion - $cobrouser;
        $dataTo[$consultar[0]["address"]] = $pagoUsuario;
        $dataTo[$addressAPagar] = $cobrouser;
        $response = $nodo->sendMultiTransaction(array("address_from"=> $addressParaPagar, "address_to"=>$dataTo, "reason"=>"Pago de subasta #".$idSubasta, "usuario"=>0));
        // print_r(array("address_from"=> $addressParaPagar, "address_to"=>$dataTo, "reason"=>"Pago de subasta #".$idSubasta, "usuario"=>0));
        // print_r($response);
        $update = "update ganadores set txhash = '{$response['txhash']}' where id_subasta =".$idSubasta;
        // print_r($update);
        parent::query($update);
        
        return true; // PROVICIONAL
    }

    public function terminarSubasta($datos)
    {
        parent::conectar();
        parent::query("SET SQL_SAFE_UPDATES=0");
        $consultar1 = "select * from subastas where id = $datos[idSubasta]";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            $dato1 = $this->cambiarStatusSubasta($datos['idSubasta'], 3, "terminado");
            if ($dato1 == true) {
                $consultar2 = "INSERT INTO historico_subasta SELECT * FROM subastas WHERE id = $datos[idSubasta]";
                $consultar3 = "INSERT INTO historico_pujas SELECT * FROM pujas WHERE subastas_id = $datos[idSubasta]";
                // print_r($ganadores);
                $data1 = parent::query($consultar2);
                $data2 = parent::query($consultar3);
                if ($data1 == true && $data2 == true) {
                    $this->devolverSaldo($datos['idSubasta']);
                    $this->agregarGanadores($datos['idSubasta']);
                    // $consultar4 = "DELETE FROM subastas WHERE id = $datos[idSubasta]";
                    //$consultar5 = "DELETE FROM pujas WHERE subastas_id = $datos[idSubasta]";
                    // parent::query($consultar4);
                    //parent::query($consultar5);
                     $respuesta = array('status' => 'success', 'message' => 'consulta exitosa');
                } else {
                    $respuesta = array('status' => 'errorBack', 'message' => 'error al generar los historicos', 'data1' => $data1, 'data2' => $data2);
                }
            }
        } else {
            $respuesta = array('status' => 'error', 'message' => 'errorParametros');
        }
        return $respuesta;
        
    }

    public function validarInscripto($datos) {
        parent::conectar();

        $consulta = "select * from productos_subastas_inscritos where uid = $datos[uid] and id_subasta = $datos[subasta]";
        $lista      = parent::consultaTodo($consulta);
        $count = count($lista);

        if ($count > 0) {
            $respuesta = array('status' => 'errorInscrito', 'message' => 'El usuario ya esta inscrito en esta subasta');
        } else {
            $respuesta = array('status' => 'success', 'message' => 'El usuario no esta inscrito en esta subasta');
        }

        parent::cerrar();
        return $respuesta;
    }

}
