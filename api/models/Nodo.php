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

    public function precioUSDEBG()
    {
        return 60000;
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

    public function generarAllAddressEBG($label)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from usuarios");
        for ($i = 0; $i < count($consulta); $i++) {
            $datos = $this->getNewAccountEBG($label, $consulta[$i]["id"]);
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

    public function getNewAccountEBG($label, $user)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from address_ebg where label = '{$label}_{$user}' ");
        if (count($consulta) > 0) {
            return array("status" => "error", "message" => "Ya existe esta cuenta de este usuario");
        }
        $data     = array('jsonrpc' => "1.0", "id" => "getNewAccount", "method" => "personal_newAccount", "params" => array("1234"));
        $conexion = parent::conectarNodoEBG($data);
        $address  = json_decode($conexion, true);
        // print_r($address);
        parent::queryRegistro("insert into address_ebg (usuario, address, label, saldo) values ({$user}, '{$address["result"]}', '{$label}_{$user}', 0)");
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
        // print_r($datos);
        // parent::cerrar();
        return array("status" => "success");
    }

    public function insertartx($tx)
    {
        parent::conectar();
        // parent::queryRegistro("insert into transacciones_recibidas (txid) values ('$tx')");
        $datos = parent::queryRegistro("insert into temporales (txhash) values ('$tx')");
        // print_r($datos);
        // parent::cerrar();
        return array("status" => "success", "Resolve" => $datos);
    }

    public function sendTransactionByOne($data)
    {
        parent::conectar();
        // print_r($data);
        if (!isset($data["address_from"]) || !isset($data["usuario"]) || !isset($data["address_to"]) || !isset($data["amount"]) || !isset($data["reason"])) {
            return array("status" => "error", "message" => "Envia todos los parametros para poder continuar");
        }
        if ($data["amount"] < 0.0006) {
            return array("status" => "errorMonto", "message" => "El monto que tratas de enviar debe ser superior a 0.0006");
        }

        $addressConsulta = parent::consultaTodo("select * from address_btc where address = '{$data["address_from"]}' and  usuario = '{$data["usuario"]}'");
        // print_r($addressConsulta[0]);
        if (count($addressConsulta) > 0) {
            if (($addressConsulta[0]["saldo"] * 1) < ($data["amount"] * 1) + 0.0001) {
                $montoEnvio = ($addressConsulta[0]["saldo"] * 1) -  0.0007;
                if ($montoEnvio > 0) {
                    return array("status" => "errorSaldo", "message" => "El monto maximo a enviar es de $montoEnvio");       
                } else {
                    return array("status" => "errorSaldo2", "message" => "no puedes retirar mas dinero de tu wallet");           
                }
                
            }
        }
        parent::conectar();
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "sendmany",
            "method"  => "sendmany",
            "params"  => array("", array($data["address_to"] => $data["amount"]), 1, $data["reason"], array($data["address_from"])),
        );
        $cobro_extra = $this->cobro_extra($data["usuario"], $data["address_to"], "BTC");
        if ($cobro_extra["status"] != "error") {
            $data2["params"][1][$cobro_extra["data"]["address"]] = ($data["amount"] * 1) * $cobro_extra["data"]["monto"];
        }
         
        $transaccionEntrante = json_decode(parent::conectarNodo($data2), true);

        if (isset($transaccionEntrante["result"])) {
            $transaccion  = $this->getNormalTransaction($transaccionEntrante["result"]);
            $fee          = number_format(($transaccion["result"]["fee"] * -1), 8);
            $saldoarestar = $fee + number_format(($data["amount"] * 1), 8);
            parent::queryRegistro("update address_btc set saldo = (saldo - $saldoarestar) where address = '{$data["address_from"]}'");
            $this->insertarTransaccion($data["address_from"], $data["address_to"], $fee, $data["amount"], $transaccionEntrante["result"], $data["reason"], $data["usuario"], 0);
            parent::cerrar();
            return array("status" => "success", "txhash" => $transaccionEntrante["result"]);
        } else {
            return array("status" => "errorAddress", "message" => "El address a enviar es invalida");
        }
        // return array("status" => "success", "txhash" => $transaccionEntrante["result"]);
    }

    public function cobro_extra($usuario, $addressReceive, $coin) {
        if ($usuario == 0) {
            return array("status"=>"error");
        }
        $tiposCobro["EBG"] = array(
            "externa"=>array("address"=>"0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "monto"=>0.05), 
            "faswet"=>array("address"=>"0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "monto"=>0.02), 
            "interna"=>array("address"=>"0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "monto"=>0.01), 
            "subasta"=>array("address"=>"2N3NxmDVJyh6R7zpvMtBw837D4zkuTZHDXh", "monto"=>0), 
            "reconsumos"=>array("address"=>"0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "monto"=>0));
        $tiposCobro["BTC"] = array(
            "externa"=>array("address"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "monto"=>0.05), 
            "faswet"=>array("address"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "monto"=>0.02), 
            "interna"=>array("address"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "monto"=>0.01), 
            "subasta"=>array("address"=>"2N3NxmDVJyh6R7zpvMtBw837D4zkuTZHDXh", "monto"=>0), 
            "reconsumos"=>array("address"=>"2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF", "monto"=>0));
        if ($tiposCobro[strtoupper($coin)]["externa"]["address"] == $addressReceive ||
            $tiposCobro[strtoupper($coin)]["faswet"]["address"] == $addressReceive ||
            $tiposCobro[strtoupper($coin)]["interna"]["address"] == $addressReceive ||
            $tiposCobro[strtoupper($coin)]["subasta"]["address"] == $addressReceive ||
            $tiposCobro[strtoupper($coin)]["reconsumos"]["address"] == $addressReceive) {
            return array("status"=>"error");
        }
        parent::conectar();
        $addressInterna = parent::consultaTodo("select * from address_".strtolower($coin)." where address = '$addressReceive'");
        if (count($addressInterna) > 0) {
            if (strtoupper($addressInterna[0]["label"]) == strtoupper("recibir_$usuario")) {
                return array("status"=>"error");
            } else {
                return array("status"=>"success", "data"=>$tiposCobro[strtoupper($coin)]["interna"]);
            }
        } else {
            $address_faswet = parent::consultaTodo("select * from wallet_faswet where address = '$addressReceive' and coin = '".strtoupper($coin)."'");
            if (count($address_faswet) > 0) {
                // return array("status"=>"success", "data"=>$tiposCobro[strtoupper($coin)]["faswet"]);
                return array("status"=>"success", "data"=>$tiposCobro[strtoupper($coin)]["faswet"]);
            } else {
                //aca va la consulta para validar si es de faswet........
                $response = array("status"=>"error");
                if ($response["status"] == "success") {
                    parent::queryRegistro("insert into wallet_faswet (address, coin) values ('$addressReceive', '".strtoupper($coin)."')");
                    return array("status"=>"success", "data"=>$tiposCobro[strtoupper($coin)]["faswet"]);
                } else {
                    return array("status"=>"success", "data"=>$tiposCobro[strtoupper($coin)]["externa"]);
                }
            }
        }


    }

    public function buscarAddress($address, $usuario, $moneda)
    {
        if ($usuario == 0) {
            return true;
        }
        parent::conectar();
        $consulta = parent::consultaTodo("select * from address_" . strtolower($moneda) . " where address = '$address' and usuario = $usuario");
        return count($consulta) > 0;
    }

    public function unlockAccount($address, $password)
    {
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "unlockAccount",
            "method"  => "personal_unlockAccount",
            "params"  => array($address, $password),
        );
        return parent::conectarNodoEBG($data2);
    }

    public function towei($amount, $type)
    {
        if ($type == "wei") {
            return $amount * pow(10, 0);
        }
        if ($type == "ether") {
            return $this->dec2hex($amount * pow(10, 18));
        }

    }

    public function fromwei($amount)
    {
        // print_r(str_replace("0x", "", $amount)."\n");
        // print_r("anexo"."\n");
        // print_r(hexdec("fa2767249f83ec6a000")."\n");
        $hex = hexdec(substr($amount, 2));
        // print_r("hex: $hex \n");
        // print_r("pow: ".pow(10, 10)." \n");
        // print_r("result: ". (($hex * 1) / pow(10, 18)));
        return ($hex * 1) / pow(10, 18);
    }

    public function lockAccount($address)
    {
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "unlockAccount",
            "method"  => "personal_unlockAccount",
            "params"  => array($address),
        );
        return parent::conectarNodoEBG($data2);
    }

    public function getLastBlockEBG()
    {
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "blockNumber",
            "method"  => "eth_blockNumber",
            "params"  => array("latest"),
        );
        $data = json_decode(parent::conectarNodoEBG($data2), true);
        print_r($data);
        return hexdec(substr($data["result"], 2));
    }

    public function dec2hex($dec)
    {
        $hex = ($dec == 0 ? '0' : '');

        while ($dec > 0) {
            $hex = dechex($dec - floor($dec / 16) * 16) . $hex;
            $dec = floor($dec / 16);
        }

        return "0x" . $hex;
    }

    public function getBalance($address)
    {
        parent::conectar();
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "getBalance",
            "method"  => "eth_getBalance",
            "params"  => array($address, "latest"),
        );
        // print_r($data2);
        $transaccionEntrante = json_decode(parent::conectarNodoEBG($data2), true);
        // print_r($transaccionEntrante);
        $transaccionEntrante["result"] = $this->fromwei($transaccionEntrante["result"]);
        parent::conectar();
        parent::queryRegistro("update address_ebg set saldo = {$transaccionEntrante["result"]} where address = '$address'");
        return $transaccionEntrante;
    }

    public function buscarBloques($inicial = 0, $final = 0)
    {
        $hashes = array();
        if ($inicial == 0 && $final == 0) {
            parent::conectar();
            $results = parent::consultaTodo("select max(id) inicial from ebg_blocks;");
            $inicial = $results[0]["inicial"] - 5;
            $final   = $this->getLastBlockEBG();
        }
        // print_r(array($inicial, $final));
        for ($i = $inicial; $i <= $final; $i++) {
            print_r("$i \n");
            $res = $this->getTransactionBlock($i);
            if (count($res["hashes"]) > 0) {

                // print_r($res["hashes"]);
                $hashes = array_merge($hashes, $res["hashes"]);
            }
        }
        print_r("TERMINO \n");
        $hashes = array_unique($hashes);
        print_r($hashes);
        $results = parent::consultaTodo("select * from address_ebg where address in ('" . join("','", $hashes) . "')");
        // print_r($results);
        for ($i = 0; $i < count($results); $i++) {
            print_r($this->getBalance($results[$i]["address"]));
        }
        return $res;
    }

    public function getTransactionBlock($blocknumber)
    {
        parent::conectar();
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "getBlockByNumber",
            "method"  => "eth_getBlockByNumber",
            "params"  => array($this->dec2hex($blocknumber), true),
        );
        // $alltransactions
        // print_r($data2);
        $transaccionEntrante = json_decode(parent::conectarNodoEBG($data2), true);
        parent::conectar();
        parent::queryRegistro("INSERT INTO ebg_blocks (id, difficulty, extraData, gasLimit, gasUsed, hash, logsBloom, miner, mixHash, nonce, `number`, parentHash, receiptsRoot, sha3Uncles, `size`, stateRoot, `timestamp`, totalDifficulty, transactions,transactionsRoot, uncles) VALUES ($blocknumber, '{$transaccionEntrante["result"]["difficulty"]}', '{$transaccionEntrante["result"]["extraData"]}', '{$transaccionEntrante["result"]["gasLimit"]}', '{$transaccionEntrante["result"]["gasUsed"]}', '{$transaccionEntrante["result"]["hash"]}', '{$transaccionEntrante["result"]["logsBloom"]}', '{$transaccionEntrante["result"]["miner"]}', '{$transaccionEntrante["result"]["mixHash"]}', '{$transaccionEntrante["result"]["nonce"]}', '{$transaccionEntrante["result"]["number"]}', '{$transaccionEntrante["result"]["parentHash"]}', '{$transaccionEntrante["result"]["receiptsRoot"]}', '{$transaccionEntrante["result"]["sha3Uncles"]}', '{$transaccionEntrante["result"]["size"]}', '{$transaccionEntrante["result"]["stateRoot"]}', '{$transaccionEntrante["result"]["timestamp"]}','{$transaccionEntrante["result"]["totalDifficulty"]}', " . count($transaccionEntrante["result"]["transactions"]) . ", '{$transaccionEntrante["result"]["transactionsRoot"]}', " . count($transaccionEntrante["result"]["uncles"]) . ")");
        // print_r($transaccionEntrante["result"]["transactions"]);
        $txhash = array();
        if (count($transaccionEntrante["result"]["transactions"]) > 0) {
            $precioUSDEBG = $this->precioUSDEBG();
            for ($i = 0; $i < count($transaccionEntrante["result"]["transactions"]); $i++) {
                // print_r("pago: ". $this->fromwei($transaccionEntrante["result"]["transactions"][$i]["value"]) ."\n");
                parent::queryRegistro("INSERT INTO ebg_transactions (block_id, transaction_id, blockHash, blockNumber, `from`, gas, gasPrice, hash, `input`, nonce, `to`, transactionIndex, value, v, r, s, fecha) VALUES ($blocknumber, $i, '{$transaccionEntrante["result"]["transactions"][$i]["blockHash"]}', '{$transaccionEntrante["result"]["transactions"][$i]["blockNumber"]}', '{$transaccionEntrante["result"]["transactions"][$i]["from"]}', " . $this->fromwei($transaccionEntrante["result"]["transactions"][$i]["gas"]) . ", '{$transaccionEntrante["result"]["transactions"][$i]["gasPrice"]}', '{$transaccionEntrante["result"]["transactions"][$i]["hash"]}', '{$transaccionEntrante["result"]["transactions"][$i]["input"]}', '{$transaccionEntrante["result"]["transactions"][$i]["nonce"]}', '{$transaccionEntrante["result"]["transactions"][$i]["to"]}', '{$transaccionEntrante["result"]["transactions"][$i]["transactionIndex"]}', " . $this->fromwei($transaccionEntrante["result"]["transactions"][$i]["value"]) . ", '{$transaccionEntrante["result"]["transactions"][$i]["v"]}', '{$transaccionEntrante["result"]["transactions"][$i]["r"]}', '{$transaccionEntrante["result"]["transactions"][$i]["s"]}', CURRENT_TIMESTAMP)");
                $txhash[] = $transaccionEntrante["result"]["transactions"][$i]["from"];
                $txhash[] = $transaccionEntrante["result"]["transactions"][$i]["to"];
                $datos    = parent::consultaTodo("select * from transactions_executed_ebg where txhash = '{$transaccionEntrante["result"]["transactions"][$i]["hash"]}'");
                if (count($datos) == 0) {
                    $this->insertarTransaccionEBG($precioUSDEBG, $transaccionEntrante["result"]["transactions"][$i]["hash"], "Transaccion Externa");
                }

            }
        }

        /*echo "INSERT INTO ebg_blocks (id, difficulty, extraData, gasLimit, gasUsed, hash, logsBloom, miner, mixHash, nonce, `number`, parentHash, receiptsRoot, sha3Uncles, `size`, stateRoot, `timestamp`, totalDifficulty, transactions,transactionsRoot, uncles) VALUES ($blocknumber, '{$transaccionEntrante["result"]["difficulty"]}', '{$transaccionEntrante["result"]["extraData"]}', '{$transaccionEntrante["result"]["gasLimit"]}', '{$transaccionEntrante["result"]["gasUsed"]}', '{$transaccionEntrante["result"]["hash"]}', '{$transaccionEntrante["result"]["logsBloom"]}', '{$transaccionEntrante["result"]["miner"]}', '{$transaccionEntrante["result"]["mixHash"]}', '{$transaccionEntrante["result"]["nonce"]}', '{$transaccionEntrante["result"]["number"]}', '{$transaccionEntrante["result"]["parentHash"]}', '{$transaccionEntrante["result"]["receiptsRoot"]}', '{$transaccionEntrante["result"]["sha3Uncles"]}', '{$transaccionEntrante["result"]["size"]}', '{$transaccionEntrante["result"]["stateRoot"]}', '{$transaccionEntrante["result"]["timestamp"]}','{$transaccionEntrante["result"]["totalDifficulty"]}', ".count($transaccionEntrante["result"]["transactions"]).", '{$transaccionEntrante["result"]["transactionsRoot"]}', ".count($transaccionEntrante["result"]["uncles"]).")";*/
        return array("Transaccion" => $transaccionEntrante, "hashes" => $txhash);
        // return array("Transaccion"=>$transaccionEntrante);
    }

    public function sendTransactionByEBG($data)
    {
        // print_r($data);
        if (!isset($data["address_from"]) || !isset($data["usuario"]) || !isset($data["address_to"]) || !isset($data["amount"]) || !isset($data["reason"])) {
            return array("status" => "error", "message" => "Envia todos los parametros para poder continuar");
        }
        
        if ($data["amount"] < 0.0006) {
            return array("status" => "errorMonto", "message" => "El monto que tratas de enviar debe ser superior a 0.0006");
        }
        parent::conectar();
        $this->unlockAccount($data["address_from"], "1234");
        $data2 = array(
            "jsonrpc" => "1.0",
            "id"      => "sendTransaction",
            "method"  => "eth_sendTransaction",
            "params"  => array(array("from" => $data["address_from"], "to" => $data["address_to"], "gasPrice" => $this->dec2hex("1000000000"), "value" => $this->towei($data["amount"], "ether"))),
        );
        $cobro_extra = $this->cobro_extra($data["usuario"], $data["address_to"], "EBG");
        if ($cobro_extra["status"] != "error") {
            $this->sendTransactionByEBG(array("address_from" => $data["address_from"], "amount"=>(($data["amount"] * 1) * $cobro_extra["data"]["monto"]),"address_to" => $cobro_extra["data"]["address"], "reason" => "Cobro de fee P2W", "usuario" => $data["usuario"]));
        }

        // print_r($data2);
        // "id" => "getNewAccount", "method" => "personal_newAccount", "params" => array("1234"));

        $transaccionEntrante = json_decode(parent::conectarNodoEBG($data2), true);

        $this->lockAccount($data["address_from"]);
        // return $transaccionEntrante;
        // $transaccion  = $this->getNormalTransaction($transaccionEntrante["result"]);
        // $fee          = number_format(($transaccion["result"]["fee"] * -1), 8);

        // echo $this->precioUSDEBG() . "  --  " . $transaccionEntrante["result"] . "  --  " . $data["reason"];
        // print_r($transaccionEntrante);
        if (isset($transaccionEntrante["result"])) {
            $saldoarestar = $data["amount"];
            parent::conectar();
            parent::queryRegistro("update address_ebg set saldo = (saldo - $saldoarestar) where address = '{$data["address_from"]}'");
            $this->insertarTransaccionEBG($this->precioUSDEBG(), $transaccionEntrante["result"], $data["reason"]);
            parent::cerrar();
            return array("status" => "success", "txhash" => $transaccionEntrante["result"]);
        } else {
            return array("status" => "errorAddress", "message" => "El address a enviar es invalida");
        }

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
            "params"  => array("", $data["address_to"], 1, $data["reason"], array($data["address_from"])),
        );
        $transaccionEntrante = json_decode(parent::conectarNodoPayment($data2), true);
        // print_r($transaccionEntrante);
        $transaccion = $this->getTransaction($transaccionEntrante["result"]);
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
            // echo "insert into enviarTransaccion (address, address_receive, fee, monto, txhash, coment, usuario, recibida, vout, precio) values ('{$from}','{$to}',{$fee},{$monto},'{$tx}', '{$coment}', {$usuario}, {$confirmaciones}, {$vout}, {$this->precioUSD()}) \n";
            parent::queryRegistro("insert into enviarTransaccion (address, address_receive, fee, monto, txhash, coment, usuario, recibida, vout, precio) values ('{$from}','{$to}',{$fee},{$monto},'{$tx}', '{$coment}', {$usuario}, {$confirmaciones}, {$vout}, {$this->precioUSD()})");
            $ok = array("status" => "success", "message" => "Creado con exito");
        }
        return $ok;

    }

    public function insertarTransaccionEBG($preciousd, $tx, $comment)
    {
        parent::conectar();
        parent::queryRegistro("insert into transactions_executed_ebg (txhash, precio_usd, comentario) values ('$tx', $preciousd, '$comment')");
        // echo "insert into transactions_executed_ebg (txhash, precio_usd, comentario) values ('$tx', $preciousd, '$comment')";

    }

    public function probarMarca($tx, $address, $amount)
    {
        $data = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/index.php", array("hash" => $tx, "address" => $address, "amount" => $amount));
        // print_r($data);
        return $data;
    }

    public function errorGuardar($tx)
    {
        parent::conectar();
        $id = parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations) values ('$tx', 0)");
        echo $id;
        return $id;
    }

    public function marcarDisponible($tx)
    {
        echo "entro a marcarDisponible $tx \n";
        parent::conectar();
        $data              = array('jsonrpc' => "1.0", "id" => "getTransaction", "method" => "gettransaction", "params" => array($tx));
        $conexion          = parent::conectarNodo($data);
        $conexion2         = parent::conectarNodoPayment($data);
        $consulta          = parent::consultaTodo("select * from transacciones_recibidas where txid = '$tx'");
        $id                = null;
        $datosTransaccion  = json_decode($conexion, true);
        $datosTransaccion2 = json_decode($conexion2, true);
        if (count($consulta) > 0) {
            echo "entro a echo(consulta) > 0 \n";
            $id = $consulta[0]["id"];
            if ($id == null) {
                if ($datosTransaccion["result"]["confirmations"] == 0) {
                    $id = parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations) values ('$tx', 0)");
                } else {
                    $id = parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations, fecha_aceptacion) values ('$tx', 1, current_timestamp)");
                }
                if ($id == 0) {
                    return array("status" => "success", "message" => "ya existen estos registros pendientes");
                }
            }
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

        $agregado = "";
        echo "llego a datosTransaccion['result']['confirmations'] \n";
        if ($datosTransaccion["result"]["confirmations"] == 0) {
            parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations) values ('$tx', 0)");
        } else {
            parent::queryRegistro("insert into transacciones_recibidas (txid, confirmations, fecha_aceptacion) values ('$tx', 1, current_timestamp)");
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
                    echo "trato de mandar la notificacion de pago al webservice \n";
                    $req = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/index.php", array("hash" => $tx, "address" => $value["address"], "amount" => $value["amount"]));
                    print_r($req);
                    $saldosumar = $value["amount"];
                    // echo "update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'";
                    parent::queryRegistro("update address_btc set saldo = (saldo + $saldosumar) where address = '{$value["address"]}'");
                    // print_r($value);
                }
            }
        }
        // echo "id total $id";
        if ($id != null) {

            // } else {
            // echo "update transacciones_recibidas set confirmations = 1, fecha_aceptacion where txid = '$tx'";
            parent::queryRegistro("update transacciones_recibidas set confirmations = 1, fecha_aceptacion = current_timestamp where txid = '$tx'");

        }
        parent::cerrar();
        return array("status" => "success", "message" => "Se han sumado los pagos pendientes");
    }
}
