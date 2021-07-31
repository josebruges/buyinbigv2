<?php
require 'Nodo.php';
require 'payudata.php';
require 'notificaciones_nasbi.php';

class Nasbifunciones extends Conexion
{

    public function getCuentaBanco( $type = 0 ){
        return array(
            'uid'               => 313,
            'email'             => 'correopote@bylup.com',
            'pass'              => '@aA123456',
            'empresa'           => 1,
            'address_Nasbigold' => 'd2aadd33ff7911faf0520368285de0a4', 
            'address_Nasbiblue' => 'bc445ce20365385b1c485165041548e6'
        );
    }
    public function crearNasbicoin(Array $data){
        
        if(!isset($data) || !isset($data['uid']) || !isset($data['monto']) || !isset($data['moneda']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;

        $tabla = '';
        if($data['moneda'] == 'Nasbiblue'){
            $tabla = 'nasbicoin_blue';
        }else if($data['moneda'] == 'Nasbigold'){
            $tabla = 'nasbicoin_gold';
        }else{
            return array('status' => 'monedaInvalida', 'message'=> 'moneda invalida', 'data' => $data['moneda']);
        }

        $fecha = intval(microtime(true)*1000);

        $nasbicoin = $this->nasbiCoinData($data, $tabla);
        if($nasbicoin['status'] == 'fail'){
            $data['fecha'] = $fecha;
            return $this->insertarNasbiCoin($data, $tabla);
        }
        if($nasbicoin['status'] == 'success'){
            $nasbicoin                              = $nasbicoin['data'];
            
            $nasbicointransaccion                   = $nasbicoin;
            $nasbicointransaccion['monto']          = $data['monto'];
            $nasbicointransaccion['fecha']          = $fecha;
            $nasbicointransaccion['tipo']           = $data['tipo'];
            $nasbicointransaccion['id_transaccion'] = 0;
            $nasbicointransaccion['uid_envio']      = 0;
            $nasbicointransaccion['empresa_envio']  = 0;

            if( !isset($data['plataforma']) || $data['plataforma'] == "" ){
                $data['plataforma'] = 3;
            }else{
                $data['plataforma'] = intval( $data['plataforma'] );
            }
            if( !isset($data['tipo_uso']) || $data['tipo_uso'] == "" ){
                $data['tipo_uso']   = 0;
            }else{
                $data['tipo_uso']   = intval( $data['tipo_uso'] );
            }

            $nasbicointransaccion['plataforma'] = $data['plataforma'];
            $nasbicointransaccion['tipo_uso']   = $data['tipo_uso'];


            if( isset($data['descripcion']) && $data['descripcion'] != "" ){
                $nasbicointransaccion["descripcion"] = $data['descripcion'];
            }else{
                $nasbicointransaccion["descripcion"] = "";
            }

            $this->insertarNasbiCoinTransaccion($nasbicointransaccion);

            $monto = floatval($nasbicoin['monto']) + floatval($data['monto']);
            $monto = floatval($monto);
            $dataupdate = [
                'monto'   => $monto,
                'fecha'   => $fecha,
                'id'      => $nasbicoin['id'],
                'uid'     => $data['uid'],
                'empresa' => $data['empresa'],
                'address' => $nasbicoin['address'],
            ];
            return $this->actualizarNasbiCoin($dataupdate, $tabla);
        }
    }
    public function diferidoBloqueadoUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $moneda = "";
        if(isset($data['moneda'])) $moneda = "AND nbc.moneda = '$data[moneda]'";

        $fecha = "";
        if(isset($data['fecha_inicio'])){
            $fecha_inicio = $data['fecha_inicio'];
            $horas24 = 86400000;
            $fecha_fin = $fecha_inicio + $horas24;
            $fecha = "AND nbc.fecha_creacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }

        $tipo_transaccion = "";
        if(isset($data['tipo_transaccion'])) $tipo_transaccion = "AND nbc.tipo_transaccion = '$data[tipo_transaccion]'";

        
        parent::conectar();
        $selectxbloqxdif = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT nbc.* FROM nasbicoin_bloqueado_diferido nbc 
                JOIN (SELECT @row_number := 0) r
                WHERE nbc.uid = '$data[uid]' AND nbc.empresa = '$data[empresa]' AND nbc.tipo = '$data[tipo]' $moneda $fecha $tipo_transaccion and nbc.accion = 1
                ORDER BY fecha_actualizacion
            )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $bloqueadosdiferidos = parent::consultaTodo($selectxbloqxdif);
        
        if(count($bloqueadosdiferidos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no tiene bloqueados y diferidos', 'pagina'=> $pagina,  'total_paginas' => 0, 'bloqueados_diferidos' => 0, 'total_bloqueados_diferidos' => 0, 'data' => null);
        }

        $selecttodos = "SELECT COUNT(nbc.id) AS contar FROM nasbicoin_bloqueado_diferido nbc WHERE nbc.uid = '$data[uid]' AND nbc.empresa = '$data[empresa]' AND nbc.tipo = '$data[tipo]' $moneda $fecha $tipo_transaccion and nbc.accion = 1;";

        $todoslosbloqdif = parent::consultaTodo($selecttodos);
        $todoslosbloqdif = floatval($todoslosbloqdif[0]['contar']);
        $totalpaginas = $todoslosbloqdif/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $bloqueadosdiferidos = $this->mapBloqueadosDiferidos($bloqueadosdiferidos, false);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'bloqueados y diferidos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'bloqueados_diferidos' => count($bloqueadosdiferidos), 'total_bloqueados_diferidos' => $todoslosbloqdif, 'data' => $bloqueadosdiferidos);

    }
    public function walletNasbiUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;

        if(!isset($data['ver_transacciones'])) $data['ver_transacciones'] = 0;
        
        $nasbigold = null;
        $nasbiblue = null;
        
        if(!isset($data['tipo'])){
            $nasbigold = $this->nasbiCoinData($data, 'nasbicoin_gold', $data['ver_transacciones']);
            $nasbiblue = $this->nasbiCoinData($data, 'nasbicoin_blue', $data['ver_transacciones']);
        }else{
            if($data['tipo'] == 'Nasbigold') $nasbigold = $this->nasbiCoinData($data, 'nasbicoin_gold', $data['ver_transacciones']);
            if ($data['tipo'] == 'Nasbiblue') $nasbiblue = $this->nasbiCoinData($data, 'nasbicoin_blue', $data['ver_transacciones']);
        }

        $nodo = new Nodo();
        $nasbigold['data']['precio_actual'] = floatval($this->truncNumber($nodo->precioUSD(), 2));
        $nasbigold['data']['precio_actual_mask'] = $this->maskNumber($nasbigold['data']['precio_actual'], 2);
        $nasbigold['data']['moneda'] = 'Nasbigold';
        
        $nasbiblue['data']['precio_actual'] = floatval($this->truncNumber($nodo->precioUSDEBG(), 2));
        $nasbiblue['data']['precio_actual_mask'] = $this->maskNumber($nasbiblue['data']['precio_actual'], 2);
        $nasbiblue['data']['moneda'] = 'Nasbiblue';
        unset($nodo);

        if ( !isset($data['iso_code_2'])){
            $data['iso_code_2'] = "US";
        }

        if (isset($data['iso_code_2'])){
            $moneda_local = $this->selectMonedaLocalUser($data);
            $nasbigold['data']['precio_actual_local_user'] = floatval($this->truncNumber(($moneda_local['costo_dolar']*$nasbigold['data']['precio_actual']), 2));
            $nasbigold['data']['precio_actual_local_user_mask'] = $this->maskNumber($nasbigold['data']['precio_actual_local_user'], 2);
            $nasbigold['data']['precio_actual_moneda_local'] = $moneda_local['code'];
            if(isset($nasbigold['data']['monto_usd'])) $nasbigold['data']['monto_local_user'] = $this->truncNumber(($moneda_local['costo_dolar']*$nasbigold['data']['monto_usd']), 2);
            if(isset($nasbigold['data']['monto_usd'])) $nasbigold['data']['monto_local_user_mask'] = $this->maskNumber($nasbigold['data']['monto_local_user'], 2);

            
            $nasbiblue['data']['precio_actual_local_user'] = floatval($this->truncNumber(($moneda_local['costo_dolar']*$nasbiblue['data']['precio_actual']), 2));
            $nasbiblue['data']['precio_actual_local_user_mask'] = $this->maskNumber($nasbiblue['data']['precio_actual_local_user'], 2);
            $nasbiblue['data']['precio_actual_moneda_local'] = $moneda_local['code'];
            if(isset($nasbiblue['data']['monto_usd'])) $nasbiblue['data']['monto_local_user'] = $this->truncNumber(($moneda_local['costo_dolar']*$nasbiblue['data']['monto_usd']), 2);
            if(isset($nasbiblue['data']['monto_usd'])) $nasbiblue['data']['monto_local_user_mask'] = $this->maskNumber($nasbiblue['data']['monto_local_user'], 2);
        }

        return array(
            'status'         => 'success',
            'uid'            => floatval($data['uid']),
            'nasbicoin_gold' => $nasbigold['data'],
            'nasbicoin_blue' => $nasbiblue['data']
        );
    }
    public function generarPayU(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['moneda']) || !isset($data['monto']) || !isset($data['monto_local']) || !isset($data['moneda_local']) || !isset($data['monto_usd'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;

        $payu = new PayU();
        $precio = $data['monto_local'];
        $moneda = $data['moneda_local'];
        if(!isset($payu->currency_test[$moneda])){
            $precio = $data['monto_usd'];
            $moneda = 'USD';
        }
        $payudata['merchantId'] = $payu->merchantId_test;
        $payudata['accountId'] = $payu->accountId_test;
        $payudata['description'] = 'Nasbicoins '.$data['moneda'].' nasbi';
        $payudata['referenceCode'] = $data['uid']; //Mirar esta referencia
        $payudata['extra1'] = json_encode(array( //Mirar esta referencia
            'tipo' => 5,
            'tipo_descripcion' => 'Nasbicoins '.$data['moneda'].' nasbi',
            'uid' => $data['uid'],
            'empresa' => $data['empresa'],
            'monto' => $data['monto'],
            'moneda' => $data['moneda'],
            'tipo_crear' => 5
        )); //Mirar esta referencia
        $payudata['amount'] = $this->truncNumber($precio, 2);
        $payudata['tax'] = 0;
        $payudata['taxReturnBase'] = 0;
        $payudata['currency'] = $moneda;
        $payudata['signature_text'] = $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$payudata['referenceCode'].'~'.$precio.'~'.$moneda;
        $payudata['signature'] = hash('md5', $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$payudata['referenceCode'].'~'.$precio.'~'.$moneda);
        $payudata['test'] = 1;
        $payudata['lng'] = 'es';
        $comprador = $this->datosUser($data)['data'];
        $payudata['buyerFullName'] = $comprador['nombre'];
        $payudata['buyerEmail'] = $comprador['correo'];
        // respuesta del cliente
        $payudata['responseUrl'] = 'https://nasbi.com/content/e-wallet.php';
        // respuesta para la api
        $payudata['confirmationUrl'] = 'https://testnet.foodsdnd.com:8185/crearpublicacion/requestPayU';
        unset($payu);

        return array('status' => 'success', 'message'=> 'data payu', 'data' => $payudata);
    }
    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2'])){
            if($data['iso_code_2'] == 'US'){
                $monedas_local['costo_dolar'] = 1;
                $monedas_local['code'] = 'USD';
            }else{
                $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
                if(count($array_monedas_locales) > 0){
                    $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                    if(count($monedas_local) > 0) {
                        $monedas_local = $monedas_local[0];
                    }else{
                        $monedas_local['costo_dolar'] = 1;
                        $monedas_local['code'] = 'USD';
                    }
                }else{
                    $monedas_local['costo_dolar'] = 1;
                    $monedas_local['code'] = 'USD';
                }
            }
            $select_precio = $monedas_local;
        }
        return $select_precio;
    }
    function nasbiCoinData(Array $data, String $tabla, Int $view_trans = 1)
    {
        parent::conectar();
        $selectxnasbicoin = "SELECT nc.* FROM $tabla nc WHERE nc.uid = '$data[uid]' AND nc.empresa = '$data[empresa]'";
        $nasbicoin = parent::consultaTodo($selectxnasbicoin);
        parent::cerrar();
        
        if(count($nasbicoin) <= 0) return array('status' => 'fail', 'message'=> 'no tiene cuenta', 'data' => null);
        
        $nasbicoin = $this->mapNasbiCoin($nasbicoin, $view_trans);
        return array('status' => 'success', 'message'=> 'nasbicoin', 'data' => $nasbicoin[0]);
    }
    function insertarNasbiCoin(Array $data, $tabla)
    {
        $address = md5(microtime().rand());

        parent::conectar();
        $insterarxnasbicoin = "INSERT INTO $tabla
        (
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$address',
            '$data[monto]',
            '$data[moneda]',
            '1',
            '$data[empresa]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insertar = parent::queryRegistro($insterarxnasbicoin);
        parent::cerrar();
        if($insertar){

            if ( !isset( $data['notificar'] )) {
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid' => $data['uid'],
                    'empresa' => $data['empresa'],
                    'text' => 'Dinero entregado',
                    'es' => 'Dinero entregado',
                    'en' => 'Send cash',
                    'keyjson' => 'trans143',
                    'url' => ''
                ]);
                unset($notificacion);
            }

            
            $data['id'] = $insertar;
            $data['address'] = $address;
            $data['id_transaccion'] = 0;
            $data['uid_envio'] = 0;
            $data['empresa_envio'] = 0;

            if( !isset($data['plataforma']) || $data['plataforma'] == "" ){
                $data['plataforma'] = 3;
            }else{
                $data['plataforma'] = intval( $data['plataforma'] );
            }
            if( !isset($data['tipo_uso']) || $data['tipo_uso'] == "" ){
                $data['tipo_uso'] = 0;
            }else{
                $data['tipo_uso'] = intval( $data['tipo_uso'] );
            }


            if( isset($data['descripcion']) && $data['descripcion'] != "" ){
                $data['descripcion'] = $data['descripcion'];
            }else{
                $data['descripcion'] = "";
            }

            $this->insertarNasbiCoinTransaccion($data);
            $request = array('status' => 'success', 'message'=> 'nasbicoin creado', 'data' => null);
        }else{
            $request = array('status' => 'fail', 'message'=> 'error al guardar nasbicoin', 'data' => null);
        }
        return $request;
    }
    function actualizarNasbiCoin(Array $data, String $tabla)
    {
        parent::conectar();
        $updatexnasbicoin = "UPDATE $tabla 
        SET
            monto = '$data[monto]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]' AND address = '$data[address]'";
        $update = parent::query($updatexnasbicoin);
        parent::cerrar();
        if($update){

            if ( !isset($data['notificar']) ) {
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid' => $data['uid'],
                    'empresa' => $data['empresa'],
                    'text' => 'Dinero entregado',
                    'es' => 'Dinero entregado',
                    'en' => 'Send cash',
                    'keyjson' => 'trans143',
                    'url' => ''
                ]);
                unset($notificacion);
            }
           $this->envio_correo_nuevos_nasbichips($data, $tabla);
            $request = array('status' => 'success', 'message'=> 'nasbicoin actualizado', 'data' => null);
        }else{
            $request = array('status' => 'fail', 'message'=> 'error al guardar nasbicoin', 'data' => null);
        }
        return $request;
    }
    function insertarNasbiCoinTransaccion(Array $data)
    {
        // $unmes = 2629743;
        // $data['fecha_vencer'] = floatval($data['fecha'] + $unmes);

        if( isset( $data['plataforma']) ){
            $data['plataforma'] = intval("" . $data['plataforma'] );
        }else{
            $data['plataforma'] = 3;
        }
        if( isset( $data['tipo_uso']) ){
            $data['tipo_uso'] = intval("" . $data['tipo_uso'] );
        }else{
            $data['tipo_uso'] = 0;
        }

        $descripcion = "";
        if( isset($data['descripcion']) && $data['descripcion'] != "" ){
            $descripcion = $data['descripcion'];
        }else{
            $data['descripcion'] = "";
        }

        parent::conectar();
        $insertarxnasbicoinxvencer = "INSERT INTO nasbicoin_transacciones
        (
            id_nasbicoin,
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion,
            tipo,
            id_transaccion,
            uid_envio,
            empresa_envio,
            plataforma,
            tipo_uso,
            descripcion
        )
        VALUES
        (
            '$data[id]',
            '$data[uid]',
            '$data[address]',
            '$data[monto]',
            '$data[moneda]',
            '1',
            '$data[empresa]',
            '$data[fecha]',
            '$data[fecha]',
            '$data[tipo]',
            '$data[id_transaccion]',
            '$data[uid_envio]',
            '$data[empresa_envio]',
            $data[plataforma],
            $data[tipo_uso],
            '$descripcion'
        );";
        parent::query($insertarxnasbicoinxvencer);
        parent::cerrar();
        
    }
    function mapNasbiCoin(Array $nabicoins, Int $view_trans = 1)
    {

        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );
        foreach ($nabicoins as $x => $nasbicoin) {
            $nasbicoin['id'] = floatval($nasbicoin['id']);
            $nasbicoin['uid'] = floatval($nasbicoin['uid']);
            $nasbicoin['monto']= floatval($this->truncNumber($nasbicoin['monto'], 2));
            $nasbicoin['monto_mask']= $this->maskNumber($nasbicoin['monto'], 2);
            $nasbicoin['estado'] = floatval($nasbicoin['estado']);
            $nasbicoin['empresa'] = floatval($nasbicoin['empresa']);
            $nasbicoin['fecha_creacion'] = floatval($nasbicoin['fecha_creacion']);
            $nasbicoin['fecha_actualizacion'] = floatval($nasbicoin['fecha_actualizacion']);
            $nasbicoin['monto_usd'] = floatval($this->truncNumber(($precios[$nasbicoin['moneda']]*$nasbicoin['monto']), 2));
            $nasbicoin['monto_usd_mask'] = $this->maskNumber($nasbicoin['monto_usd'], 2);
            $nasbicoin['nasbicoin_transacciones'] = null;
            if($view_trans == 1) $nasbicoin['nasbicoin_transacciones'] = $this->nasbiCoinTransaccionesUsuario($nasbicoin)['data']; 

            $nabicoins[$x] = $nasbicoin;
        }
        unset($nodo);

        return $nabicoins;
    }
    public function nasbiCoinTransaccionesUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['moneda']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;
        
        $pagina = 1;
        if(isset($data['pagina'])) $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        
        $address = "";
        if(isset($data['address'])) $address = "AND nct.address = '$data[address]'";

        $fecha = "";
        if(isset($data['fecha_inicio'])){
            if ( $data['fecha_inicio'] != "" || $data['fecha_inicio'] != null ) {
                $fecha_inicio = $data['fecha_inicio'];
                $horas24 = 86400000;
                $fecha_fin = $fecha_inicio + $horas24;
                $fecha = "AND nct.fecha_creacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }
        }
        
        parent::conectar();
        /* echo $data['moneda']; */
        $selectxnasbicoinxtransaccionesOLD = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT nct.*, nctt.descripcion AS tipo_descripcion
                FROM nasbicoin_transacciones nct
                INNER JOIN nasbicoin_transacciones_tipo nctt ON nct.tipo = nctt.id
                JOIN (SELECT @row_number := 0) r
                WHERE moneda = '$data[moneda]' AND ((nct.uid = '$data[uid]' AND empresa = '$data[empresa]') OR (nct.uid_envio = '$data[uid]' AND empresa_envio = '$data[empresa]')) $address $fecha AND nct.estado = 1
                UNION
                SELECT nct2.id, nct2.id_nasbicoin, nct2.uid, nct2.empresa, nct2.address, (sum(nct2.monto) * -1) monto, nct2.moneda, nct2.estado, 
                nct2.fecha_creacion, nct2.fecha_actualizacion, nct2.tipo, nct2.uid_envio, nct2.empresa_envio, nct2.id_transaccion, nctt2.descripcion AS tipo_descripcion
                FROM nasbicoin_transacciones nct2
                INNER JOIN nasbicoin_transacciones_tipo nctt2 ON nct2.tipo = nctt2.id
                JOIN (SELECT @row_number := 0) r
                WHERE (nct2.uid_envio = '$data[uid]') AND nct2.moneda = '$data[moneda]' AND nct2.empresa = '$data[empresa]' AND nct2.estado = 1 group by id_transaccion, moneda
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        /* echo $selectxnasbicoinxtransaccionesOLD; */
        $selectxnasbicoinxtransacciones = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT nct.*, nctt.descripcion AS tipo_descripcion
                FROM nasbicoin_transacciones nct
                INNER JOIN nasbicoin_transacciones_tipo nctt ON nct.tipo = nctt.id
                JOIN (SELECT @row_number := 0) r
                WHERE moneda = '$data[moneda]' AND ((nct.uid = '$data[uid]' AND empresa = '$data[empresa]') OR (nct.uid_envio = '$data[uid]' AND empresa_envio = '$data[empresa]')) $address $fecha AND nct.estado = 1
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $nasbicointrans = parent::consultaTodo($selectxnasbicoinxtransacciones);
        // echo $selectxnasbicoinxtransacciones;
        if(count($nasbicointrans) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message' => 'no tiene transacciones', 'pagina' => $pagina, 'total_paginas' => 0, 'transacciones' => 0, 'total_transacciones' => 0, 'data' => null);
        }
        
        $selecttodos = "SELECT COUNT(nct.id) AS contar 
        FROM nasbicoin_transacciones nct 
        WHERE nct.uid = '$data[uid]' $address $fecha AND moneda = '$data[moneda]' AND empresa = '$data[empresa]' AND nct.estado = 1";
        $todoslastransacciones = parent::consultaTodo($selecttodos);
        parent::cerrar();

        $todoslastransacciones = floatval($todoslastransacciones[0]['contar']);
        $totalpaginas = $todoslastransacciones/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        
        $nasbicointrans = $this->mapNasbiCoinTransacciones($nasbicointrans);
        return array('status' => 'success', 'message'=> 'transacciones', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'transacciones' => count($nasbicointrans), 'total_transacciones' => $todoslastransacciones, 'data' => $nasbicointrans);

    }
    function mapNasbiCoinTransacciones(Array $nabicoins)
    {
        foreach ($nabicoins as $x => $nasbicoin) {
            $nasbicoin['num'] = floatval($nasbicoin['num']);
            $nasbicoin['id'] = floatval($nasbicoin['id']);
            $nasbicoin['id_nasbicoin'] = floatval($nasbicoin['id_nasbicoin']);
            $nasbicoin['uid'] = floatval($nasbicoin['uid']);
            $nasbicoin['monto']= floatval($this->truncNumber($nasbicoin['monto'], 2));
            $nasbicoin['monto_mask']= $this->maskNumber($nasbicoin['monto'], 2);
            $nasbicoin['estado'] = floatval($nasbicoin['estado']);
            $nasbicoin['empresa'] = floatval($nasbicoin['empresa']);
            $nasbicoin['fecha_creacion'] = floatval($nasbicoin['fecha_creacion']);
            $nasbicoin['fecha_actualizacion'] = floatval($nasbicoin['fecha_actualizacion']);
            $nasbicoin['tipo'] = floatval($nasbicoin['tipo']);
            $nasbicoin['uid_envio'] = floatval($nasbicoin['uid_envio']);
            $nasbicoin['empresa_envio'] = floatval($nasbicoin['empresa_envio']);
            $nasbicoin['datos_user_envio'] = null;
            if($nasbicoin['tipo'] != 1) $nasbicoin['datos_user_envio'] = $this->datosUser(['uid' => $nasbicoin['uid_envio'], 'empresa' => $nasbicoin['empresa_envio']])['data'];

            $nabicoins[$x] = $nasbicoin;
        }

        return $nabicoins;
    }
    function insertarBloqueadoDiferido(Array $data)
    {
        $tipo = 0;
        $accion = 0;

        if($data['tipo'] == 'bloqueado') $tipo = 1;
        if($data['accion'] == 'push') $accion = 1;
        parent::conectar();

        if($tipo == 1){
            
            $condicional_saldo = "";
            
            $addressby = '';
            if($data['moneda'] == 'Nasbiblue') $addressby = 'nasbicoin_blue';
            else if($data['moneda'] == 'Nasbigold') $addressby = 'nasbicoin_gold';
            else return array('status' => 'monedaInvalida', 'message'=> 'moneda invalida', 'data' => $data['moneda']);

            if($accion == 1 && $data['all'] != true) $condicional_saldo = "AND monto > '$data[precio]'";
            $selectaddress = "SELECT * FROM $addressby WHERE uid = '$data[uid]' $condicional_saldo AND address = '$data[address]'";
            $address = parent::consultaTodo($selectaddress);
            if(count($address) == 0) return array('status' => 'addressInvalida', 'message'=> 'la direccion que mandaste no es correcta o no cuenta con el dinero suficiente', 'data' => $selectaddress);
            $address = $address[0];
            
            //Sumar saldo o reverse
            if($accion == 0)$monto = floatval($address['monto'] + $data['precio']);
            
            //Restar saldo o bloquear
            if($accion == 1) $monto = floatval($address['monto'] - $data['precio']);
            if($accion == 1 && $data['all'] == true){
                $data['precio'] = floatval($address['monto']);
                $monto = 0;
            }

            // $saldo = $this->truncNumber($saldo, 2);
            $updatetaddress = "UPDATE $addressby SET monto = '$monto' WHERE id = '$address[id]' AND uid = '$data[uid]' AND address = '$data[address]'";
            $update = parent::query($updatetaddress);
            if(!$update) return array('status' => 'fail', 'message'=> 'error al actualizar el monto', 'data' => null);
        }

        $id_producto = 0;
        if ( isset($data['id_producto']) ) {
            $id_producto = $data['id_producto'];
        }
        $precio_en_cripto = 0;
        if ( isset($data['precio_en_cripto']) ) {
            $precio_en_cripto = $data['precio_en_cripto'];
        }
        if($accion == 1){
            $insertxbloqueadoxdiferido = "INSERT INTO nasbicoin_bloqueado_diferido
            (
                uid,
                empresa,
                id_transaccion,
                id_producto,
                tipo_transaccion,
                moneda,
                address,
                precio,
                precio_momento_usd,
                precio_en_cripto,
                tipo,
                accion,
                descripcion,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
                '$data[uid]',
                '$data[empresa]',
                '$data[id_transaccion]',
                '$id_producto',
                '$data[tipo_transaccion]',
                '$data[moneda]',
                '$data[address]',
                '$data[precio]',
                '$data[precio_momento_usd]',
                '$precio_en_cripto',
                '$tipo',
                '$accion',
                '$data[descripcion]',
                '$data[fecha]',
                '$data[fecha]'
            );";
            $insertarbloqueo = parent::queryRegistro($insertxbloqueadoxdiferido);
            parent::cerrar();
    
            if(!$insertarbloqueo) return array('status' => 'fail', 'message'=> 'error al insertar '.$data['tipo'], 'data' => null);
            return array('status' => 'success', 'message'=> 'cobro realizado', 'data' => $insertarbloqueo);
        }else{
            $updatextransaccionOLD = "UPDATE nasbicoin_bloqueado_diferido
            SET
                accion = '$accion',
                fecha_actualizacion = '$data[fecha]'
            WHERE id = '$data[id]'";
            $updatextransaccion = "UPDATE nasbicoin_bloqueado_diferido
            SET
                accion = '$accion',
                fecha_actualizacion = '$data[fecha]'
            WHERE id_transaccion = '$data[id_transaccion]'";
            $update = parent::query($updatextransaccion);
            parent::cerrar();
            if(!$update) return array('status' => 'fail', 'message'=> 'error actualizar detalle transaccion', 'data' => null);
            
            return array('status' => 'success', 'message'=> 'detalle transaccion actualizado', 'data' => null);
        }
    }
    function enviarDinero(Array $data)
    {
        parent::conectar();
        
        $addressbycomprador = '';
        if($data['moneda'] == 'Nasbiblue') $addressbycomprador = 'nasbicoin_blue';
        else if($data['moneda'] == 'Nasbigold') $addressbycomprador = 'nasbicoin_gold';
        else {
            return array('status' => 'monedaInvalida', 'message'=> 'moneda invalida', 'data' => $data['moneda']);
            parent::cerrar();
        }
        
        $selectaddresscomprador = "SELECT * FROM $addressbycomprador WHERE uid = '$data[uid_envia]' AND address = '$data[addres_envia]' AND empresa = '$data[empresa_envia]'";

        parent::addLog("\t\t -######- [ 1 / SELECT ADDRESS ]: " . $selectaddresscomprador);

        $addresscomprador = parent::consultaTodo($selectaddresscomprador);

        if(count($addresscomprador) == 0) {
            return array('status' => 'addressInvalida', 'message'=> 'la direccion que mandaste no es correcta o no cuenta con el dinero suficiente', 'data' => $data);
            parent::cerrar();
        }
        $addresscomprador = $addresscomprador[0];
        parent::addLog("\t\t -######- [ 2 / SELECT ADDRESS ]: " . json_encode($addresscomprador));

        $saldocomprador = 0;
        // if ( isset( $data['cerrarVenta'] ) ) {
        //     $saldocomprador = floatval($addresscomprador['monto'] - $data['monto_en_cripto']);
        // }else{
            $saldocomprador = floatval($addresscomprador['monto']) - floatval($data['monto']);
        // }

        parent::addLog("\t\t -######- [ 3 / SELECT ADDRESS / addresscomprador ]: " . floatval($addresscomprador['monto']));
        parent::addLog("\t\t -######- [ 4 / SELECT ADDRESS / data ]: " . floatval($data['monto']));

        $updatetaddresscomprador = "UPDATE $addressbycomprador SET monto = '$saldocomprador', fecha_actualizacion = '$data[fecha]' WHERE id = '$addresscomprador[id]' AND uid = '$data[uid_envia]' AND address = '$data[addres_envia]'";

        parent::addLog("\t\t -######- [ 5 / SELECT ADDRESS ]: " . $updatetaddresscomprador);

        $updatecomprador = parent::query($updatetaddresscomprador);

        parent::addLog("\t\t -######- [ 6 / SELECT ADDRESS ]: " . $updatecomprador);


        if(!$updatecomprador) {
            return array('status' => 'fail', 'message'=> 'error al actualizar el monto', 'data' => null);
            parent::cerrar();
        }

        $addressbyvendedor = '';
        if($data['moneda'] == 'Nasbiblue') $addressbyvendedor = 'nasbicoin_blue';
        else if($data['moneda'] == 'Nasbigold') $addressbyvendedor = 'nasbicoin_gold';
        else {
            return array('status' => 'monedaInvalida', 'message'=> 'moneda invalida', 'data' => $data);
            parent::cerrar();
        }

        $selectaddressvendedor = "SELECT * FROM $addressbyvendedor WHERE uid = '$data[uid_recibe]' AND address = '$data[addres_recibe]' AND empresa = '$data[empresa_recibe]'";
        $addressvendedor = parent::consultaTodo($selectaddressvendedor);

        parent::addLog("\t\t -######- [ 7 / SELECT ADDRESS ]: " . $selectaddressvendedor);
        parent::addLog("\t\t -######- [ 8 / SELECT ADDRESS ]: " . json_encode($addressvendedor));

        if(count($addressvendedor) == 0) {
            return array(
                'status' => 'addressInvalida',
                'message'=> 'no cuenta con el dinero suficiente',
                'data' => $data,

                'query' => $selectaddressvendedor,

                'uid_recibe' => $data['uid_recibe'],
                'addres_recibe' => $data['addres_recibe'],
                'empresa_recibe' => $data['empresa_recibe']
            );
            parent::cerrar();
        }
        $addressvendedor = $addressvendedor[0];
        $saldo = 0;
        // if ( isset( $data['cerrarVenta'] ) ) {
        //     $saldo = floatval($addressvendedor['monto'] + $data['monto_en_cripto']);
        // }else{
            $saldo = floatval($addressvendedor['monto']) + floatval($data['monto']);
        // }

        parent::addLog("\t\t -######- [ 9 / SELECT ADDRESS ]: " . floatval($addressvendedor['monto']));
        parent::addLog("\t\t -######- [ 10 / SELECT ADDRESS ]: " . floatval($data['monto']));
        parent::addLog("\t\t -######- [ 11 / SELECT ADDRESS ]: " . $saldo);
        
        // $saldo = floatval($addressvendedor['monto'] + $data['monto']);
        $updatetaddressvendedor = "UPDATE $addressbyvendedor SET monto = '$saldo', fecha_actualizacion = '$data[fecha]' WHERE id = '$addressvendedor[id]' AND uid = '$data[uid_recibe]' AND address = '$data[addres_recibe]' AND empresa = '$data[empresa_recibe]'";
        $updatevendedor = parent::query($updatetaddressvendedor);
        parent::cerrar();

        parent::addLog("\t\t -######- [ 12 / SELECT ADDRESS ]: " . $updatetaddressvendedor);
        parent::addLog("\t\t -######- [ 13 / SELECT ADDRESS ]: " . $updatevendedor);

        if(!$updatevendedor) {
            return array('status' => 'fail', 'message'=> 'error al actualizar el monto', 'data' => null);
            parent::cerrar();
        }

        if( !isset($data['plataforma']) || $data['plataforma'] == "" ){
            $data['plataforma'] = 3;
        }else{
            $data['plataforma'] = intval( $data['plataforma'] );
        }
        if( !isset($data['tipo_uso']) || $data['tipo_uso'] == "" ){
            $data['tipo_uso'] = 0;
        }else{
            $data['tipo_uso'] = intval( $data['tipo_uso'] );
        }

        $descripcion = "";
        if( isset($data['descripcion']) && $data['descripcion'] != "" ){
            $descripcion = $data['descripcion'];
        }

        $this->insertarNasbiCoinTransaccion([
            'id'             => $addressvendedor['id'],
            'uid'            => $addressvendedor['uid'],
            'address'        => $data['addres_recibe'],
            'monto'          => $data['monto'],
            'moneda'         => $data['moneda'],
            'empresa'        => $data['empresa_recibe'],
            'fecha'          => $data['fecha'],
            'tipo'           => $data['tipo'],
            'id_transaccion' => $data['id_transaccion'],
            'uid_envio'      => $data['uid_envia'],
            'empresa_envio'  => $data['empresa_envia'],
            'plataforma'     => $data['plataforma'],
            'tipo_uso'       => $data['tipo_uso'],
            'descripcion'    => $descripcion
        ]);

        return array('status' => 'success', 'message'=> 'Dinero enviado', 'data' => null);
    }

    function mapBloqueadosDiferidos(Array $bloqueadosxdiferidos)
    {
        foreach ($bloqueadosxdiferidos as $x => $bloqdiferidos) {

            $bloqdiferidos['id'] = floatval($bloqdiferidos['id']);
            $bloqdiferidos['uid'] = floatval($bloqdiferidos['uid']);
            $bloqdiferidos['empresa'] = floatval($bloqdiferidos['empresa']);
            $bloqdiferidos['id_transaccion'] = floatval($bloqdiferidos['id_transaccion']);
            $bloqdiferidos['tipo_transaccion'] = floatval($bloqdiferidos['tipo_transaccion']);
            $bloqdiferidos['tipo_transaccion_descripcion'] = 'Compra de articulo';
            if($bloqdiferidos['tipo_transaccion'] == 2) $bloqdiferidos['tipo_transaccion_descripcion'] = 'Subasta de articulo';
            $bloqdiferidos['precio'] = floatval($bloqdiferidos['precio']);
            $bloqdiferidos['precio_mask'] = $this->maskNumber($bloqdiferidos['precio'], 2);
            $bloqdiferidos['precio_momento_usd'] = floatval($bloqdiferidos['precio_momento_usd']);
            $bloqdiferidos['precio_momento_usd_mask'] = $this->maskNumber($bloqdiferidos['precio_momento_usd'], 2);
            $bloqdiferidos['tipo'] = floatval($bloqdiferidos['tipo']);
            $bloqdiferidos['tipo_descripcion'] = 'Diferido';
            if($bloqdiferidos['tipo'] == 1) $bloqdiferidos['tipo_descripcion'] = 'Bloqueo';
            $bloqdiferidos['accion'] = floatval($bloqdiferidos['accion']);
            $bloqdiferidos['descripcion'] = "Ref: #". floatval($bloqdiferidos['id_transaccion']) . ", ". stripslashes($bloqdiferidos['descripcion']);
            $bloqdiferidos['accion_descripcion'] = 'Push';
            if($bloqdiferidos['accion'] == 1) $bloqdiferidos['accion_descripcion'] = 'Reverse';
            $bloqdiferidos['fecha_creacion'] = floatval($bloqdiferidos['fecha_creacion']);
            $bloqdiferidos['fecha_actualizacion'] = floatval($bloqdiferidos['fecha_actualizacion']);

            $bloqueadosxdiferidos[$x] = $bloqdiferidos;
        }

        return $bloqueadosxdiferidos;
    }
    function filter_by_value (Array $array, String $index, $value){
        $newarray = null;
        if(is_array($array) && count($array)>0) 
        {
            foreach(array_keys($array) as $key){
                if(isset($array[$key][$index])){
                    $temp[$key] = $array[$key][$index];
                    if ($temp[$key] == $value){
                        $newarray[] = $array[$key];
                    }
                }
            }
        }
        return $newarray;
    }
    function truncNumber(Float $number, Int $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }
    function maskNumber(Float $numero, Int $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }

    function datosUser(Array $data)
    {
        // No deben de existir conexiones abiertas antes de llamar a esta función
        // de llegar a existirlo se debe cerrar antes de llamar a esta funcion.

        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = '1'";
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => $data);

        $usuario = $this->mapUsuarios($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }
    function mapUsuarios(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;
        $dataPaso = null;
        $dataIdioma= null; 
        $data_uid=null; 
        foreach ($usuarios as $x => $user) {

            if (!isset( $user['idioma'] )) {
                $user['idioma'] = "ES";
            }
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto'];//"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
                $dataIdioma= strtoupper($user['idioma']);
                $data_uid=$user["id"];
            }else if($empresa == 1){
                $datanombre = $user['razon_social'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $dataIdioma= strtoupper($user['idioma']);
                $data_uid=$user["id"];
            }
            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $user['empresa'] = $empresa;
            $user['idioma'] = "ES";
            $user['uid'] = $data_uid;
            $usuarios[$x] = $user;
        }
        return $usuarios;
    }

    function envio_correo_nuevos_nasbichips(Array $data, String $moneda){
        $data_user = $this->datosUser([
            'uid' => $data["uid"],
            'empresa' => $data['empresa']
        ]);
        $this->html_correo_de_recarga_nasbichips($data,$moneda,$data_user["data"]);
    }

    function html_correo_de_recarga_nasbichips(Array $data_recarga, String $moneda, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo12nasbichips.html");
        
        $moneda_u="vacio"; 
        if($moneda == 'nasbicoin_blue'){
            $moneda_u= $json->trans164_;
        }else if($moneda == 'nasbicoin_gold'){
            $moneda_u= "Nasbichips";
        }
 

        $html = str_replace("{{nombre_usuario}}",ucfirst($data_user['nombre']), $html);

        $html = str_replace("{{trans82_}}",$json->trans82_, $html);
        $html = str_replace("{{trans83_}}",$json->trans83_, $html);

        $html = str_replace("{{trans84_}}",$json->trans84_, $html);
        $html = str_replace("{{trans28_}}",$json->trans28_, $html);
        $html = str_replace("{{trans29_}}",$json->trans29_, $html);

        $html = str_replace("{{trans30_}}",$json->trans30_, $html);

        $html = str_replace("{{trans31_}}",$json->trans31_, $html);

        $html = str_replace("{{trans82_}}",$json->trans82_, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{trans34_valor}}", $this->maskNumber($data_recarga["monto"], 2), $html);
        $html = str_replace("{{trans35_unidad}}",$moneda_u, $html);
        $html = str_replace("{{trans31_}}",$json->trans31_, $html);
        $html = str_replace("{{trans37_}}",$json->trans37_, $html);
        $html = str_replace("{{trans38_}}",$json->trans38_, $html);
        $html = str_replace("{{trans39_}}",$json->trans39_, $html);
        $html = str_replace("{{signo_amdiracion_open}}",$json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        $html = str_replace("{{trans85_}}",$json->trans85_, $html);
        $html = str_replace("{{trans40_}}",$json->trans40_, $html);
        $html = str_replace("{{trans86_}}",$json->trans86_, $html);
        $html = str_replace("{{trans165_}}",$json->trans165_, $html);
        
        
        $html = str_replace("{{link_to_promociones}}", $json->link_to_promociones, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'];
        $mensaje1   = $html;
        $titulo    = $json->trans163_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function getTimestamp(){
        $fechaActual = new DateTime();
        $timezone = new DateTimeZone('America/Bogota');
        $fechaActual->format("U");
        $fechaActual->setTimezone($timezone);
        return $fechaActual->getTimestamp()*1000;
        // var_dump($fechaActual);
        // echo '(1)' . $fechaActual->getTimestamp()*1000;
        // echo '(2)' . date_timestamp_get($fechaActual);
        // $pedro = new DateTime('2021-05-05 16:02:43');
        // echo '(3)' . $pedro->getTimestamp()*1000;
        // echo '(4)' . intval(microtime(true)*1000);
    }

    function getFechaRecogidaTcc( Int $recogida = 0 ){
        // 0 == recogida: Yo la llevo a TCC.
        // 1 == recogida: TCC viene por el articulo.

        $fechaActual = new DateTime();
        $timezone    = new DateTimeZone('America/Bogota');

        $fechaActual->format("U");
        $fechaActual->setTimezone($timezone);

        if ( $recogida == 0 ) {
            if( $fechaActual->format('H') >= 16 ) {
                date_add($fechaActual, date_interval_create_from_date_string("+1 days"));
            }
        }else{
            if( $fechaActual->format('H') >= 13 ) {
                date_add($fechaActual, date_interval_create_from_date_string("+1 days"));
            }
            
        }
        $fechaActual_normal = new DateTime( $fechaActual->format('Y-m-d') );
        $fechaActual_inicio = new DateTime( $fechaActual->format('Y-m-d') . " 14:00:00");
        $fechaActual_fin    = new DateTime( $fechaActual->format('Y-m-d') . " 16:00:00");
        
        $resuls = array(
            'fecha'             => $fechaActual->format('Y-m-d'),
            'fecha_inicio'      => $fechaActual->format('Y-m-d') . "T14:00:00",
            'fecha_fin'         => $fechaActual->format('Y-m-d') . "T16:00:00",
            'timestamp_normal'  => $fechaActual_normal->getTimestamp()*1000,
            'timestamp_inicio'  => $fechaActual_inicio->getTimestamp()*1000,
            'timestamp_fin'     => $fechaActual_fin->getTimestamp()*1000
        );
        return $resuls;
    }



    // Validación generar tope de ventas gratuitas.
    function ventasGratuitasRealizadas(Array $data) 
    {
        if( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos: uid=' . $data['uid'] . ', empresa=' . $data['empresa'],
                'cantidad'=> null,
                'data'    => null
            );
        }
        
        parent::conectar();

        $selectxresumenxventas =
            "SELECT
                COUNT(*) AS ventas
            FROM productos_transaccion pt
            INNER JOIN  buyinbig.productos p ON( id_producto = p.id)
            WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado >= 13 AND pt.exposicion_pt = 1;";

        $selectresumenventas = parent::consultaTodo($selectxresumenxventas);
        parent::cerrar();

        if(count($selectresumenventas) == 0) {
            return array(
                'status' => 'noTieneVentasGratuitas',
                'message'=> 'El usuario no cuenta con registros de ventas gratuitas',
                'data' => null
            );
        }else{
            if ( floatval( $selectresumenventas[0]['ventas'] ) >= 3 ) {
                
                parent::conectar();
                $selectxventasxtopexgratuitas = "SELECT * FROM vendedores_tope_ventas_gratuitas  WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
                $selectventastopegratuitas = parent::consultaTodo($selectxventasxtopexgratuitas);

                if(count($selectventastopegratuitas) == 0) {
                    // No ha sido notificado
                    $insertxnotificacionxtopexventasxgratuitas = 
                    "INSERT INTO vendedores_tope_ventas_gratuitas(uid, empresa ) 
                    VALUES ( '$data[uid]', '$data[empresa]' );";
                    parent::queryRegistro($insertxnotificacionxtopexventasxgratuitas);
                    parent::cerrar();

                    $schemaNotificacion = Array(
                        'uid'     => $data['uid'],
                        'empresa' => $data['empresa'],
                        
                        'text' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        'es' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        'en' => 'You have 3 or more sales made with free exposure. Go to my accounts / publications and proceed to modify your articles to a different type of exposure.',
                        'keyjson' => '',

                        'url' => 'mis-cuentas.php?tab=sidenav_publicaciones'
                    );
                    $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";
                    parent::remoteRequest($URL, $schemaNotificacion);

                    return array(
                        'status' => 'success',
                        'message'=> 'resumen',
                        'data' => $selectresumenventas
                    );

                }else{
                    $selectxproductosxgratuitosa= 
                        "SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND exposicion = 1;";
                    $productos_gratuitos = parent::consultaTodo($selectxproductosxgratuitosa);
                    parent::cerrar();

                    if( COUNT( $productos_gratuitos ) > 0 ){
                        
                        // $schemaNotificacion = Array(
                        //     'uid'     => $data['uid'],
                        //     'empresa' => $data['empresa'],
                            
                        //     'text' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        //     'es' => 'Tienes 3 o más ventas realizadas con exposición de tipo gratuita. Dirígete a mis cuentas / publicaciones y procede a modificar tus artículos a un tipo de exposición diferente.',

                        //     'en' => 'You have 3 or more sales made with free exposure. Go to my accounts / publications and proceed to modify your articles to a different type of exposure.',
                        //     'keyjson' => '',

                        //     'url' => 'mis-cuentas.php?tab=sidenav_publicaciones'
                        // );
                        // $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";
                        // parent::remoteRequest($URL, $schemaNotificacion);
                        
                        return array(
                            'status' => 'success',
                            'message'=> 'resumen',
                            'data' => $selectresumenventas
                        );
                    }

                }
            }

            return array(
                'status' => 'noTieneTopeVentasGratuitas',
                'message'=> 'No ha completado las 3 ventas en categoria gratuita. Lleva: ' . $selectresumenventas[0]['ventas'],
                'JSON'=> $selectresumenventas[0],
                'data' => floatval( $selectresumenventas[0]['ventas'] )
            );

        }
    }

}
?>