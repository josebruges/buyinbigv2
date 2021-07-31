<?php
// require 'conexion.php';
require 'Nodo.php';

class HistorialTikects extends Conexion
{

    public function listarHistotialTego($historial)
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "SELECT * FROM historico_tickets WHERE usuario = " . $historial['usuario'] . " and cantidad > 0 where fecha > CURRENT_DATE";
        $lista      = parent::consultaTodo($consultar1);
        $consultar2 = "SELECT * FROM historico_tickets WHERE transferido = " . $historial['usuario'] . " and cantidad > 0  and fecha > CURRENT_DATE";
        $lista2     = parent::consultaTodo($consultar2);
        parent::cerrar();
        $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista, "transferidos" => $lista2);
        return $respuesta;
    }

    public function listarHistotial($historial)
    {
        $nodo = new Nodo();
        parent::conectar();
        $respuesta;
        $consultar1 = "SELECT * FROM historico_tickets WHERE usuario = " . $historial['usuario'] . " and cantidad > 0  and fecha > CURRENT_DATE order by fecha desc";
        $lista      = parent::consultaTodo($consultar1);
        $consultar2 = "SELECT * FROM historico_tickets WHERE transferido = " . $historial['usuario'] . " and cantidad > 0 and fecha > CURRENT_DATE order by fecha desc";
        $lista2     = parent::consultaTodo($consultar2);
        $consultar3 = "SELECT * FROM compra_tickets WHERE usuario = " . $historial['usuario']." order by fecha_compra desc";
        $lista3     = parent::consultaTodo($consultar3);
        $consultar5 = "select p.nombre, descuento_tickets, compra_ailewux from paquetes p where id in (select plan from usuarios u2 where id = $historial[usuario])";
        $lista5     = parent::consultaTodo($consultar5);

        $consultar4 = "select * from address_btc where label = 'wallet_$historial[usuario]'";
        $lista4     = parent::consultaTodo($consultar4);
        $preciousd  = $nodo->precioUSD();
        if (count($lista) > 0) {
            for ($i = 0; $i < count($lista); $i++) {
                $lista[$i]["precioBTC"] = $preciousd;
            }
        }
        parent::cerrar();
        $respuesta = array(
            'status' => 'success',
            'message' => 'Lista',
            'data' => $lista,
            "transferidos" => $lista2,
            "compras"=>$lista3,
            "saldoBTC"=>$lista4[0]["saldo"],
            "precioactual"=>$preciousd,
            "planuser"=> count($lista5) > 0 ? $lista5[0] : 0
        );
        return $respuesta;
    }


    public function cambiarTickets($historial) {
        
        $this->consumirEntradasLibres($historial["data"]["uid"], $historial["data"]["cantidad"], $historial["data"]["tipo"]);
        if ($historial["data"]["tipo"] == "entradas300") {
            $historial["data"]["cantidad"]  = $historial["data"]["cantidad"] * 5;
        } else if ($historial["data"]["tipo"] == "entradas500") {
            $historial["data"]["cantidad"] = $historial["data"]["cantidad"] * 2;
        } elseif ($historial["data"]["tipo"] == "entradas1000") {
            $historial["data"]["cantidad"] = $historial["data"]["cantidad"] * 3;
        } else if ($historial["data"]["tipo"] == "entradas5000") {
            $historial["data"]["cantidad"] = $historial["data"]["cantidad"] * 3;
        }

        
        $data = parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", $historial);
        return json_decode($data, true);
    }

    public function transferirEntradas($historial)
    {
        parent::conectar();
        $respuesta;
        $fechaFin       = getdate();
        $fecha          = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
        $datosRecepcion = parent::consultaTodo("select * from wallet_tickets where qr = '{$historial['usuario']}' and tipo_wallet = 'entradas$historial[tipo]'");

        if (!$this->comprobarTickets($historial['usuarioEnvia'], $historial['tickets'], "entradas$historial[tipo]")) {
            return array("status" => "error", "message" => "No tiene tickets suficientes...".$historial['usuarioEnvia']."----". $historial['tickets']."----". "entradas$historial[tipo]");
        }
        if (count($datosRecepcion) == 0) {
            return array('status' => 'errorTicket', 'message' => 'El address a recibir no existe');
        }

        if ($datosRecepcion[0]['usuario'] == $historial["usuarioEnvia"]) {
            return array('status' => 'errorMisTickets', 'message' => 'No puedes enviarte tickets a ti mismo');
        }
        if ($datosRecepcion[0]["tipo_wallet"] != "entradas$historial[tipo]") {
            return array('status' => 'errorGrupo', 'message' => 'Los ticketes que tratas de enviar no coinciden con los que reciben', "extra" => $datosRecepcion[0]["tipo_wallet"], "Extra2" => "entradas$historial[tipo]");
        }
        // entradas$historial[tipo]
        
        $consultar1 = "select tipo tipo2, sum(cantidad) entradas$historial[tipo] from historico_tickets where tipo ='entradas$historial[tipo]' and usuario = $historial[usuarioEnvia] and cantidad > 0 and transferido is null and (fecha - interval 20 day) >= CURRENT_DATE order by fecha";

        $lista      = parent::consultaTodo($consultar1);
        /* print_r($lista);
        echo $lista[0]["entradas".$historial['tipo']].'\n';
        echo $historial['tickets'].'\n'; */
        if (($lista[0]["entradas" . $historial['tipo']] * 1) < ($historial['tickets'] * 1)) {
            return array("status" => "errorTicketsReconsumo", "message" => "No tiene tickets suficientes", "maximo"=> $lista[0]["entradas" . $historial['tipo']] * 1);
        }
        $res = $this->getUser($datosRecepcion[0]['usuario']);
        if (!$res) {
            return array("status" => "errorUsuario", "message" => "El usuario no existe");
        }
        $this->consumirEntradas($historial['usuarioEnvia'], $historial['tickets'], "entradas$historial[tipo]", 1);
        parent::conectar();
        $consultar2 = "update usuarios set entradas" . $historial['tipo'] . " = (entradas" . $historial['tipo'] . " - " . $historial['tickets'] . ") where id= " . $historial['usuarioEnvia'];
        //parent::queryRegistro($consultar2);
        $this->agregarTickets($datosRecepcion[0]['usuario'], $historial['tickets'], $historial['tipo'], $historial['status'], $historial['usuarioEnvia']);
        parent::cerrar();
        $respuesta = array('status' => 'success', 'message' => 'Se registro');
        return $respuesta;
    }

    public function verMisTickets($user)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select entradas300, entradas500, entradas1000, entradas5000 from usuarios where id = $user");
        $wallets  = array("status" => "error", "message" => "no se pudieron encontrar los ticketes");
        if (count($consulta) > 0) {
            $wallets = array("status" => "success", "data" => array("entradas300" => $consulta[0]["entradas300"], "entradas500" => $consulta[0]["entradas500"], "entradas1000" => $consulta[0]["entradas1000"], "entradas5000" => $consulta[0]["entradas5000"]));
        }
        return $wallets;
    }

    public function consumirEntradas($usuario, $tickets, $tipo, $interno = 0)
    {
        parent::conectar();
        $fechaFin   = getdate();
        $fecha      = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
        //echo "select * from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and (fecha - interval 20 days) >= CURRENT_DATE order by fecha";
        $consultar1 = "select * from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and fecha >= CURRENT_DATE order by fecha";
        if ($interno == 1) {
            $consultar1 = "select * from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and (fecha - interval 20 day) >= CURRENT_DATE order by fecha";
        }
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
            }
            if ($tickets == 0) {
                break;
            }
        }
        $this->comprobarTickets($usuario, 0, $tipo);
        /* parent::cerrar(); */
        return true;
    }

    public function consumirEntradasLibres($usuario, $tickets, $tipo)
    {
        parent::conectar();
        $fechaFin   = getdate();
        $fecha      = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
        $consultar1 = "select * from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and fecha >= CURRENT_DATE and transferido is null order by fecha";
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
            }
            if ($tickets == 0) {
                break;
            }
        }
        $this->comprobarTickets($usuario, 0, $tipo);
        /* parent::cerrar(); */
        return true;
    }


    public function comprobarTickets($usuario, $tickets, $tipo)
    {
        parent::conectar();
        $consultar1 = parent::consultaTodo("select ifnull(sum(cantidad), 0) cantidad from historico_tickets where tipo = '$tipo' and usuario = $usuario and cantidad > 0 and fecha >= CURRENT_DATE order by fecha");
        parent::queryRegistro("update usuarios set $tipo = {$consultar1[0]["cantidad"]} where id = $usuario");
        return $tickets <= $consultar1[0]["cantidad"];
    }


    public function getUser($usuario)
    {
        parent::conectar();
        $consultar1 = "select * from usuarios where id= '$usuario'";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            return $lista[0];
        } else {
            return false;
        }
    }


    public function comprarTickets($historial)
    {
        $address["BTC"] = "2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF";
        $tickets[300] = 5;
        $tickets[500] = 7;
        $tickets[1000] = 14;
        $tickets[5000] = 30;
        $nodo = new Nodo();
        parent::conectar();
        $respuesta;
        $fechaFin       = getdate();
        $fecha          = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
        

        $consultar1 = "select * from usuarios where id= '{$historial['usuarioEnvia']}'";
        $lista      = parent::consultaTodo($consultar1);
        $consultaPlan = parent::consultaTodo("select p.descuento_tickets from paquetes p where id = {$lista[0]["plan"]}");
        $this->comprobarTickets($historial['usuarioEnvia'], 0, "entradas$historial[tipo]");
        
        //print_r($data);
        
        $precioUSD = $nodo->precioUSD();
        $descuento = $consultaPlan[0]["descuento_tickets"];
        $montoPago = number_format($tickets[$historial["tipo"]] *(1 /  $precioUSD) * (1 - $descuento), 6) * $historial["tickets"];
        $consultarAddress = parent::consultaTodo("select * from address_btc where label = 'wallet_$historial[usuarioEnvia]'");
        $data = array("address_from"=>$consultarAddress[0]["address"], "address_to"=>$address["BTC"], "amount"=>$montoPago, "reason"=>"Compra de tickets", "usuario"=>$historial["usuarioEnvia"], "coin"=>"BTC");
        //echo $consultarAddress[0]["saldo"] . "----->" . $montoPago;
        if (($consultarAddress[0]["saldo"]) < ($montoPago * 1)) {
            $respuesta = array("status"=>"errorMonto", "message"=>"no dispones de saldo suficiente para realizar esta compra");
        } else if ($montoPago < 0.0006) {
            $respuesta = array("status"=>"errorMontoMin", "message"=>"El monto mínimo a comprar es de 0.0006 BTC");
        } else {

            $dataresp = json_decode(parent::remoteRequest("https://peers2win.com/api/controllers/nodo/nodo.php", $data), true);

            if ($dataresp["status"] == "success") {
                parent::queryRegistro("insert into compra_tickets (usuario, cantidad, tipo, costo_unitario, txhashcompra, precio_usd, monto_cripto, descuento) values ({$historial['usuarioEnvia']}, {$historial['tickets']}, 'entradas$historial[tipo]', {$tickets[$historial["tipo"]]}, '$dataresp[txhash]', $precioUSD, $montoPago, $descuento)");
                $consultar2 = "update usuarios set entradas" . $historial['tipo'] . " = (entradas" . $historial['tipo'] . " - " . $historial['tickets'] . ") where id= " . $historial['usuarioEnvia'];
                parent::queryRegistro($consultar2);
                $this->agregarTickets($historial['usuarioEnvia'], $historial['tickets'], $historial['tipo'], 5, null);
                $respuesta = array('status' => 'success', 'message' => 'Se agregaron correctamente');
            } else {
                //print_r($dataresp);
                $respuesta = array("status"=>"errorMonto", "message"=>"no dispones de saldo suficiente para realizar esta compra");
            }
            parent::cerrar();

        }
        return $respuesta;
    }

    public function agregarTickets($usuario, $tickets, $tipo, $status, $usuarioEnvia)
    {
        if ($tipo != 300 && $tipo != 500 && $tipo != 1000 && $tipo != 5000) {
            /* resolve("No se puede agregar este tipo de tickets"); */
            return false;
        } else {
            parent::conectar();
            $consultar1 = "select * from usuarios where id= '$usuario'";
            $lista      = parent::consultaTodo($consultar1);
            if (count($lista) == 0) {
                /* console.log("el usuario al que tratas de agregar entradas no existe"); */
                return false;
            } else {
                $consultar2 = "update usuarios set entradas$tipo = {$lista[0]["entradas" . $tipo]} + $tickets where id= " . $lista[0]['id'];
                // echo "update usuarios set entradas$tipo = {$lista[0]["entradas" . $tipo]} + $tickets where id= " . $lista[0]['id'];
                //parent::queryRegistro($consultar2);
                for ($i = 0; $i < ($tickets * 1); $i++) {
                    $consultar3 = "insert into historico_tickets (tipo, usuario, cantidad, descripcion, transferido, codigo, fecha) values ('entradas" . $tipo . "', " . $lista[0]['id'] . ", 1, '" . ($status == 1 ? "Compra de plan" : ($status == 2 ? "Upgrade" : ($status == 3 ? "reconsumo" : $status== 5 ? "compra" :"transferidos"))) . "', " . ($status != 4 ? "null" : "'" . $usuarioEnvia . "'") . ", sha1(concat('entradas" . $tipo . "', {$lista[0]['id']}, CURRENT_TIMESTAMP, $i)), CURRENT_TIMESTAMP + INTERVAL 1 month)";
                    // echo $consultar3."\n";
                    parent::queryRegistro($consultar3);

                }
        $this->comprobarTickets($usuario, 0, "entradas$tipo");
                return true;
            }
        }
    }

    public function listarHistotialTransaccion($historial)
    {
        if (isset($historial["iduser"]) && $historial["iduser"] != null) {
            parent::conectar();
            $respuesta;
            $consultar1 = "SELECT * FROM enviarTransaccion where address in (select address from address_btc where usuario = {$historial["iduser"]}) or address_receive in (select address from address_btc where usuario = {$historial["iduser"]}) order by fecha desc";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
            return $respuesta;
        } else {
            parent::conectar();
            $respuesta;
            $consultar1 = "SELECT * FROM enviarTransaccion where address = '{$historial['address']}' or address_receive = '{$historial['address']}' order by fecha desc";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
            return $respuesta;
        }

    }

    public function listarHistotialTransaccionNoAddress($userId)
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "SELECT * FROM enviarTransaccion where address in (select address from address_btc where usuario = 297) or address_receive in (select address from address_btc where usuario = 297) order by fecha desc";
        $lista      = parent::consultaTodo($consultar1);
        parent::cerrar();
        $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
        return $respuesta;
    }

    public function listarHistotialTransaccionEBG($historial)
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "
            SELECT
                concat_ws('_', eb.block_id, eb.transaction_id) id,
                eb.from as address,
                eb.to as address_receive,
                eb.hash as txhash,
                eb.gas as fee,
                eb.value as monto,
                ebt.comentario as coment,
                ebt.precio_usd as precio,
                ebt.fecha_creacion as fecha,
                ae.usuario usuario
            FROM ebg_transactions eb
            INNER JOIN transactions_executed_ebg ebt ON ebt.txhash = eb.hash
            LEFT JOIN address_ebg ae on ae.address  = eb.from 
            WHERE eb.from = '{$historial['address']}' or eb.to = '{$historial['address']}'
            order by ebt.fecha_creacion desc;
        ";
        $lista = parent::consultaTodo($consultar1);
        parent::cerrar();
        $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
        return $respuesta;
    }

    public function generarExcel($historial)
    {
        $hearder = '';
        $body = '';
        for ($i=0; $i < count($historial['hearder']); $i++) { 
            $hearder += "<th>". $historial['hearder'][$i] ."</th>";
        }
        for ($i=0; $i < count($historial['body']); $i++) { 
            $body += "
                <tr>
                    <td>". $historial['body'][$i]["concepto"] ."</td>
                    <td>". $historial['body'][$i]["txHash"] ."</td>
                    <td>". $historial['body'][$i]["criptomoneda"] ."</td>
                    <td>". $historial['body'][$i]["usd"] ."</td>
                    <td>". $historial['body'][$i]["fecha"] ."</td>
                </tr>
            ";
        }
        return $html = "
            <table>
                <tr>$hearder</tr>
                <tbody>
                    $body
                </tbody>
            </table>
        ";
    }
}
