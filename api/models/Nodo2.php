<?php
require 'conexion.php';

class Nodo extends Conexion
{
    public function Date()
    {

        parent::conectar();
        $lista = array('time' => date("Y-m-d"));
        parent::cerrar();

        return $lista;
    }

    public function verificarConexion()
    {
        $data     = array('jsonrpc' => "1.0", "id" => "verificarNodo", "method" => "getblockcount", "params" => array());
        $conexion = parent::conectarNodo($data);
        return json_decode($conexion, true);
    }

    public function getTransaction($tx)
    {
        $data     = array('jsonrpc' => "1.0", "id" => "getTransaction", "method" => "gettransaction", "params" => array($tx));
        $conexion = parent::conectarNodoPayment($data);
        return json_decode($conexion, true);
    }

    public function getNormalTransaction($tx)
    {
        $data     = array('jsonrpc' => "1.0", "id" => "getTransaction", "method" => "gettransaction", "params" => array($tx));
        $conexion = parent::conectarNodo($data);
        return json_decode($conexion, true);
    }

    public function precioUSD()
    {
        return 30000;
    }

    public function generarAllAddress($label)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from usuarios");
        for ($i = 0; $i < count($consulta); $i++) {
            $datos = $this->getNewAccount($label, $consulta[$i]["id"]);
            print_r($datos);
        }
        parent::cerrar();

    }

    public function getNewAccount($label, $user)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from address_btc where label = '{$label}_{$user}' ");
        if (count($consulta) > 0) {
            return array("status" => "error", "message" => "Ya existe esta cuenta de este usuario");
        }
        $data     = array('jsonrpc' => "1.0", "id" => "getNewAccount", "method" => "getnewaddress", "params" => array("{$label}_{$user}"));
        $conexion = parent::conectarNodo($data);
        $address  = json_decode($conexion, true);
        // print_r($address);
        parent::queryRegistro("insert into address_btc (usuario, address, label, saldo) values ({$user}, '{$address["result"]}', '{$label}_{$user}', 0)");
        parent::cerrar();
        return array('status' => "success", "message" => "Se ha creado correctamente el address", "address" => $address["result"]);
    }

    public function newAccountPayments($user)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from address_btc where label = 'recibir_{$user}' ");
        if (count($consulta) > 0) {
            return array("status" => "error", "message" => "Ya existe esta cuenta de este usuario");
        }
        $data     = array('jsonrpc' => "1.0", "id" => "getNewAccountPayments", "method" => "getnewaddress", "params" => array("recibir_{$user}"));
        $conexion = parent::conectarNodoPayment($data);
        $address  = json_decode($conexion, true);
        // print_r($address);
        parent::queryRegistro("insert into address_btc (usuario, address, label, saldo) values (0, '{$address["result"]}', 'recibir_{$user}', 0)");
        parent::cerrar();
        return array('status' => "success", "message" => "Se ha creado correctamente el address", "address" => $address["result"]);
    }

    public function notifyWallet($tx)
    {
        // parent::conectar();
        // parent::queryRegistro("insert into transacciones_recibidas (txid) values ('$tx')");
        $datos = $this->marcarDisponible($tx);
        print_r($datos);
        // parent::cerrar();
        return array("status" => "success");
    }

    public function sendTransactionByOne($data)
    {
        // print_r($data);
        if (!isset($data["address_from"]) || !isset($data["usuario"]) || !isset($data["address_to"]) || !isset($data["amount"]) || !isset($data["reason"])) {
            return array("status" => "error", "message" => "Envia todos los parametros para poder continuar");
        }
        parent::conectar();
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "sendmany",
            "method"  => "sendmany",
            "params"  => array("", array($data["address_to"] => $data["amount"]), 1, "Send by Peer2Win", array($data["address_from"])),
        );
        // print_r($data2);
        $transaccionEntrante = json_decode(parent::conectarNodo($data2), true);
        // print_r($transaccionEntrante);
        $transaccion         = $this->getNormalTransaction($transaccionEntrante["result"]);
        $fee                 = number_format(($transaccion["result"]["fee"] * -1), 8);
        $saldoarestar        = $fee + number_format(($data["amount"] * 1), 8);
        parent::queryRegistro("update address_btc set saldo = (saldo - $saldoarestar) where address = '{$data["address_from"]}'");
        $this->insertarTransaccion($data["address_from"], $data["address_to"], $fee, $data["amount"], $transaccionEntrante["result"], $data["reason"], $data["usuario"], 0);
        parent::cerrar();
        return array("status" => "success", "txhash" => $transaccionEntrante["result"]);
    }

    public function sendMultiTransaction($data)
    {

        if (!isset($data["address_from"]) || !isset($data["usuario"]) || !isset($data["address_to"]) || !is_array($data["address_to"]) || !isset($data["reason"])) {
            return array("status" => "error", "message" => "Envia todos los parametros para poder continuar");
        }
        parent::conectar();
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "sendmany",
            "method"  => "sendmany",
            "params"  => array("", $data["address_to"], 1, "Multi-payment Peer2Win", array($data["address_from"])),
        );
        $transaccionEntrante = json_decode(parent::conectarNodoPayment($data2), true);
        print_r($transaccionEntrante);
        $transaccion         = $this->getTransaction($transaccionEntrante["result"]);
        // print_r($transaccion);
        $fee          = number_format(($transaccion["result"]["fee"] * -1), 8);
        $saldoarestar = $fee;
        $pos          = 0;
        foreach ($data["address_to"] as $key => $value) {
            $this->insertarTransaccion($data["address_from"], $key, $fee, $value, $transaccionEntrante["result"], $data["reason"], $data["usuario"], 0, $pos);
            $pos++;
            $saldoarestar += number_format(($value * 1), 8);
        }
        parent::queryRegistro("update address_btc set saldo = (saldo - $saldoarestar) where address = '{$data["address_from"]}'");
        parent::cerrar();
        return array("status" => "success", "txhash" => $transaccionEntrante["result"]);
    }

    public function insertarTransaccion($from, $to, $fee, $monto, $tx, $coment, $usuario, $confirmaciones, $vout = 0)
    {
        // echo "select * from enviarTransaccion where address='$to' and monto = $monto and txhash = '$tx'";
        $consulta = parent::consultaTodo("select * from enviarTransaccion where address_receive='$to' and monto = $monto and txhash = '$tx' " . ($vout != 0 ? "and vout = $vout" : ""));
        $ok       = array("status" => "error", "message" => "no se pudo registrar");
        if (count($consulta) > 0) {
            for ($i = 0; $i < count($consulta); $i++) {
                if ($consulta[$i]["recibida"] == 0 && $confirmaciones == 1) {
                    parent::queryRegistro("update enviarTransaccion set recibida = $confirmaciones where id = {$consulta[$i]["id"]}");
                    $ok = array("status" => "success", "message" => "Actualizado con exito");
                }
            }
        } else {
            // $precioUSD = ;
            parent::queryRegistro("insert into enviarTransaccion (address, address_receive, fee, monto, txhash, coment, usuario, recibida, vout, precio) values ('{$from}','{$to}',{$fee},{$monto},'{$tx}', '{$coment}', {$usuario}, {$confirmaciones}, {$vout}, {$this->precioUSD()})");
            $ok = array("status" => "success", "message" => "Creado con exito");
        }
        return $ok;

    }

    public function probarMarca($tx, $address, $amount) {
        $data = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/index.php", array("hash"=>$tx, "address"=>$address, "amount"=>$amount));
        print_r($data);
        return $data;
    }

    public function marcarDisponible($tx)
    {
        parent::conectar();
        $data              = array('jsonrpc' => "1.0", "id" => "getTransaction", "method" => "gettransaction", "params" => array($tx));
        $conexion          = parent::conectarNodo($data);
        $conexion2         = parent::conectarNodoPayment($data);
        $consulta          = parent::consultaTodo("select * from transacciones_recibidas where txid = '$tx'");
        $id                = null;
        $datosTransaccion  = json_decode($conexion, true);
        $datosTransaccion2 = json_decode($conexion2, true);
        if (count($consulta) > 0) {
            $id = $consulta[0]["id"];
            if ($consulta[0]["confirmations"] > 0) {
                return array("status" => "success", "message" => "ya existen estos registros actualizados");
            }
            if ($consulta[0]["confirmations"] == 0 && $datosTransaccion["result"]["confirmations"] == 0) {
                return array("status" => "success", "message" => "ya existen estos registros pendientes");
            }
        }
        $fee     = 0;
        $comment = "External transaction";
        if (!isset($datosTransaccion["result"]["fee"]) && isset($datosTransaccion2["result"]["fee"])) {
            $fee = $datosTransaccion2["result"]["fee"];
        }
        if (!isset($datosTransaccion["result"]["comment"]) && isset($datosTransaccion2["result"]["comment"])) {
            $comment = $datosTransaccion2["result"]["comment"];
        }
        if (isset($datosTransaccion["result"]["fee"]) && !isset($datosTransaccion2["result"]["fee"])) {
            $fee = $datosTransaccion["result"]["fee"];
        }
        if (isset($datosTransaccion["result"]["comment"]) && !isset($datosTransaccion2["result"]["comment"])) {
            $comment = $datosTransaccion["result"]["comment"];
        }
        foreach ($datosTransaccion["result"]["details"] as $key => $value) {
            if ($value["category"] == "receive") {
                // $fee = (number_format(($datosTransaccion["result"]["fee"] * -1), 8));
                $this->insertarTransaccion("", $value["address"], $fee, number_format($value["amount"] * 1, 8), $datosTransaccion["result"]["txid"], $comment, 0, ($datosTransaccion["result"]["confirmations"] == 0 ? 0 : 1));
                if ($datosTransaccion["result"]["confirmations"] > 0) {
                    $saldosumar = $value["amount"];
                    // echo "update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'";
                    parent::queryRegistro("update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'");
                    // print_r($value);
                }
            }
        }
        foreach ($datosTransaccion2["result"]["details"] as $key => $value) {
            if ($value["category"] == "receive") {
                // $fee = (number_format(($datosTransaccion2["result"]["fee"] * -1), 8));
                $this->insertarTransaccion("", $value["address"], $fee, number_format($value["amount"] * 1, 8), $datosTransaccion2["result"]["txid"], $comment, 0, ($datosTransaccion2["result"]["confirmations"] == 0 ? 0 : 1));
                if ($datosTransaccion2["result"]["confirmations"] > 0) {
                    $req = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/index.php", array("hash"=>$tx, "address"=>$value["address"], "amount"=>$value["amount"]));
                    print_r($req);
                    $saldosumar = $value["amount"];
                    // echo "update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'";
                    parent::queryRegistro("update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'");
                    // print_r($value);
                }
            }
        }
        // echo "id total $id";
        if ($id == null) {
            if ($datosTransaccion["result"]["confirmations"] == 0) {
                parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations) values ('$tx', 0)");
            } else {
                parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations, fecha_aceptacion) values ('$tx', 1, current_timestamp)");
            }

        } else {
            // echo "update transacciones_recibidas set confirmations = 1, fecha_aceptacion where txid = '$tx'";
            parent::queryRegistro("update transacciones_recibidas set confirmations = 1, fecha_aceptacion = current_timestamp where txid = '$tx'");

        }
        parent::cerrar();
        return array("status" => "success", "message" => "Se han sumado los pagos pendientes");
    }
}
