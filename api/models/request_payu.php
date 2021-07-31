<?php
require 'conexion.php';
require '../../Shippo.php';

class RequestPayU extends Conexion
{

    ///////////////////// Extra /////////////////////////////////
    // tipo 1: Compra de un articulo ///////////////////////////
    // tipo 2: Compra de un ticket individual compra //////////
    // tipo 3: Adquirir Plan tickets venta ///////////////////
    // tipo 4: Adquirir Plan tickets compra /////////////////
    // tipo 5: Adquirir Nasbicoin //////////////////////////
    // tipo 6: Pagar orde de compra referido //////////////
    //////////////////////////////////////////////////////

    public function requestPayUData(Array $data)
    {
        if(!isset($data)) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $data['extra1'] = json_decode(stripcslashes($data['extra1']), true);
        $data['json'] = (array) $data['json'];
        $data['json']['extra1'] = json_decode(stripcslashes($data['json']['extra1']), true);
        $data['json'] = json_encode($data['json'], true);
        $insertarreqeust = $this->insertarRequestPayU($data);
        if($insertarreqeust['status'] != 'success') return $insertarreqeust;
        $data['json'] = (array) json_decode($data['json']);
        if($data['extra1']['tipo'] == 1) return $this->insertarEnCompra($data);
        if($data['extra1']['tipo'] == 2) return $this->insertarTicketCompra($data);
        if($data['extra1']['tipo'] == 3) return $this->insertarPlanTickets($data);
        if($data['extra1']['tipo'] == 4) return $this->insertarPlanTickets($data);
        if($data['extra1']['tipo'] == 5) return $this->insertarNasbiCoin($data);
        if($data['extra1']['tipo'] == 6) return $this->insertarCodigoReferido($data);
    }

    function insertarRequestPayU(Array $data)
    {
        $tipo = $data['extra1']['tipo'];
        parent::conectar();
        $insertxrequest = "INSERT INTO request_payu
        (
            json,
            tipo,
            id_transaccion,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[json]',
            '$tipo',
            '$data[reference_sale]',
            1,
            '$data[fecha]',
            '$data[fecha]'
        );";
        $request_payu = parent::queryRegistro($insertxrequest);
        parent::cerrar();

        if(!$request_payu) return array('status' => 'fail', 'message'=> 'request no insertada', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'request insertada', 'data' => null);
    }

    // COMPRA
    function insertarEnCompra(Array $data)
    {
        $estado = 3;
        $json = $data['json'];
        if($json['state_pol'] == 7) $estado = 3; //PENDING
        if($json['state_pol'] == 6) $estado = 3; //DECLINED
        if($json['state_pol'] == 5) $estado = 3; // EXPIRED
        if($json['state_pol'] == 4) $estado = 6; //APPROVED
        
        return $this->actualizarEstadoTransaccion([
            'id' => $json['reference_sale'],
            'estado' => $estado,
            'fecha' => $data['fecha'],
            'mensaje_success' => 'pago actualizado',
            'mensaje_fail' => 'error al actualizar pago'
        ]);
    }

    function actualizarEstadoTransaccion(Array $data)
    {
        parent::conectar();
        $adicional = "";
        $updatextransaccion = "UPDATE productos_transaccion
        SET
            estado = '$data[estado]',
            fecha_actualizacion = '$data[fecha]'
            $adicional
        WHERE id = '$data[id]'";
        $updatetransaccion = parent::query($updatextransaccion);
        parent::cerrar();
        if(!$updatetransaccion) return array('status' => 'fail', 'message'=> $data['mensaje_fail'], 'data' => null);
        
        $this->insertarTimeline($data);
        return array('status' => 'success', 'message'=> $data['mensaje_success'], 'data' => null);
    }

    function insertarTimeline(Array $data)
    {
        parent::conectar();
        $insertxtimeline = "INSERT INTO productos_transaccion_timeline
        (
            id_transaccion,
            tipo,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '2',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
        parent::cerrar();
    }
    // FIN COMPRA

    // TICKET INDIVIDUAL
    function insertarTicketCompra(Array $data){
        $json = $data['json'];
        if($json['state_pol'] != 4) return array('status' => 'fail', 'message'=> 'no tickets agregados', 'data' => null);
        $data['extra1']['fecha'] = $data['fecha'];
        return $this->pagarTicketCompra($data['extra1']);
    }

    public function pagarTicketCompra(Array $data)
    {

        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['cantidad']) || !isset($data['uso'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $transferido = null;
        if(isset($data['transferido'])) $transferido = $data['transferido'];
        $fechames = $data['fecha'] + 2628000000;

        return $this->insertarTicket([
            'conexion' => true,
            'plan' => $data['tipo'],
            'uid' => $data['uid'],
            'empresa' => $data['empresa'],
            'id_nabitickets_usuario' => null,
            'cantidad' => $data['cantidad'],
            'descripcion' => 'Compra individual Nasbi',
            'transferido' => $transferido,
            'uso' => $data['uso'],
            'origen' => 1,
            'fecha' => $data['fecha'],
            'fechames' => $fechames
        ]);
    }

    function insertarTicket(Array $data)
    {
        if($data['conexion'] == true) parent::conectar();
        $address = md5(microtime().rand());
        $insertxticket = "INSERT INTO nasbitickets
        (
            plan,
            uid,
            empresa,
            id_nabitickets_usuario,
            cantidad,
            descripcion,
            transferido,
            codigo,
            uso,
            origen,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            fecha_vencimiento
        )
        VALUES
        (
            '$data[plan]',
            '$data[uid]',
            '$data[empresa]',
            '$data[id_nabitickets_usuario]',
            '$data[cantidad]',
            '$data[descripcion]',
            '$data[transferido]',
            '$address',
            '$data[uso]',
            '$data[origen]',
            '1',
            '$data[fecha]',
            '$data[fecha]',
            '$data[fechames]'
        );";
        $insert = parent::queryRegistro($insertxticket);
        if($data['conexion'] == true) parent::cerrar();
        
        if(!$insert) return array('status' => 'fail', 'message'=> 'ticket no registrado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'ticket registrado', 'data' => $insert);

    }
    // FIN TICKET INDIVIDUAL

    // PLAN TICKETS
    function insertarPlanTickets(Array $data){
        $json = $data['json'];
        if($json['state_pol'] != 4) return array('status' => 'fail', 'message'=> 'no tickets agregados', 'data' => null);
        $data['extra1']['fecha'] = $data['fecha'];
        return $this->pagarPlan($data['extra1']);
    }

    public function pagarPlan(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])  || !isset($data['uso'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;


        if($data['uso'] == 1) $data['table'] = "nasbitickets_plan";
        if($data['uso'] == 2) $data['table'] = "nasbitickets_plan_compra";
        
        $plan = $this->planId($data);
        if($plan['status'] != 'success') return $plan;
        $plan =  $plan['data'];

        //Aqui tengo que pagar falta el send o tarjeta de credito

        $fecha = intval(microtime(true)*1000);

        $idinsert = $this->insertarPlanUsuario([
            'data' => $data,
            'plan' => $plan,
            'uso' => $data['uso'],
            'fecha' => $fecha
        ]);
        if($idinsert['status'] != 'success') return $idinsert;
        $idinsert =  $idinsert['data'];


        return $this->insertarTicketsArray([
            'plan' => $plan,
            'uso' => $data['uso'],
            'id' => $idinsert,
            'fecha' => $fecha,
            'data' => $data,
        ]);
    }

    function planId(Array $data){
        parent::conectar();
        $table = $data['table'];
        $selectxplanesxnasbi = "SELECT ntp.* FROM $table ntp WHERE ntp.id = '$data[id]'";
        $planes = parent::consultaTodo($selectxplanesxnasbi);
        parent::cerrar();
        if(!$planes) return array('status' => 'fail', 'message'=> 'no planes', 'data' => null);
        $planes = $this->mapPlanes($planes);
        $planes = $planes[0];
        return array('status' => 'success', 'message'=> 'planes', 'data' => $planes);
    }

    function insertarPlanUsuario(Array $data)
    {
        $plan = $data['plan'];
        
        $fecha = $data['fecha'];
        $fechames = $fecha + 2628000000;

        $data = $data['data'];

        parent::conectar();
        $insertxplanxusuario = "INSERT INTO nasbitickets_usuario
        (
            uid,
            plan,
            uso,
            total_entradas,
            costo,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            fecha_vencimiento
        )
        VALUES
        (
            '$data[uid]',
            '$plan[id]',
            '$data[uso]',
            '$plan[total_entradas]',
            '$plan[costo]',
            '1',
            '$fecha',
            '$fecha',
            '$fechames'
        );";
        $insert = parent::queryRegistro($insertxplanxusuario);
        parent::cerrar();
        if(!$insert) return array('status' => 'fail', 'message'=> 'no registro plan usuario', 'data' => null);

        return array('status' => 'success', 'message'=> 'registro plan usuario', 'data' => $insert);
    }


    function insertarTicketsArray(Array $data)
    {
        $plan = $data['plan'];
        
        $id = $data['id'];
        
        $fecha = $data['fecha'];
        $fechames = $fecha + 2628000000;

        $data = $data['data'];

        $insertxticket = null;
        $validar = true;

        $tickets = $plan['entradas_planes'];
        parent::conectar();
        foreach ($tickets as $x => $ticket) {
            $insertxticket = $this->insertarTicket([
                'conexion' => false,
                'plan' => $ticket->id,
                'uid' => $data['uid'],
                'empresa' => $data['empresa'],
                'id_nabitickets_usuario' => $id,
                'cantidad' => $ticket->valor,
                'descripcion' => 'Plan nasbitickets',
                'transferido' => null,
                'uso' => $data['uso'],
                'origen' => 1,
                'fecha' => $fecha,
                'fechames' => $fechames
            ]);
            if(!$insertxticket['status'] == 'fail') $validar = false;
        }
        parent::cerrar();

        if($validar == false) return array('status' => 'fail', 'message'=> 'no tickets agregados', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'tickets agregados', 'data' => null);

    }

    function mapPlanes(Array $planes)
    {
        foreach ($planes as $x => $plan) {
            $plan['id'] = floatval($plan['id']);
            $plan['costo'] = floatval($plan['costo']);
            $plan['volumen_ventas'] = floatval($plan['volumen_ventas']);
            $plan['entradas_planes'] = json_decode($plan['entradas_planes']);
            $plan['total_entradas'] = floatval($plan['total_entradas']);
            $plan['costo_entradas'] = floatval($plan['costo_entradas']);
            $plan['comision_usd'] = floatval($plan['comision_usd']);
            $plan['costo_mask'] = $this->maskNumber($plan['costo'], 2);

            if(isset($plan['precio_local_user'])){
                $plan['precio_local_user'] = floatval($this->truncNumber($plan['precio_local_user'],2));
                $plan['precio_local_user_mask']= $this->maskNumber($plan['precio_local_user'], 2);
            }else{
                $plan['precio_local_user'] = $plan['costo'];
                $plan['precio_local_user_mask'] = $plan['costo_mask'];
                $plan['moneda_local_user'] = 'USD';
            }
            $planes[$x] = $plan;
        }
        return $planes;
    }
    // FIN PLAN TICKETS

    // NASBICOINS
    function insertarNasbiCoin(Array $data){
        $json = $data['json'];
        if($json['state_pol'] != 4) return array('status' => 'fail', 'message'=> 'no tickets agregados', 'data' => null);
        $data['extra1']['fecha'] = $data['fecha'];
        return $this->pagarPlan($data['extra1']);
    }

    function crearNasbicoin(Array $data){
        
        if(!isset($data) || !isset($data['uid']) || !isset($data['monto']) || !isset($data['moneda']) || !isset($data['empresa']) || !isset($data['tipo_crear'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;

        $tabla = '';
        // $data['moneda'] = strtoupper($data['moneda']);
        if($data['moneda'] == 'Nasbiblue'){
            $tabla = 'nasbicoin_blue';
        }else if($data['moneda'] == 'Nasbigold'){
            $tabla = 'nasbicoin_gold';
        }else{
            return array('status' => 'monedaInvalida', 'message'=> 'moneda invalida', 'data' => null);
        }

        $fecha = intval(microtime(true)*1000);

        $nasbicoin = $this->nasbiCoinData($data, $tabla);
        if($nasbicoin['status'] == 'fail'){
            $data['fecha'] = $fecha;
            return $this->insertarNasbiCoin($data, $tabla);
        }
        if($nasbicoin['status'] == 'success'){
            $nasbicoin = $nasbicoin['data'];

            $nasbicointransaccion = $nasbicoin;
            $nasbicointransaccion['monto'] = $data['monto'];
            $nasbicointransaccion['fecha'] = $fecha;
            $nasbicointransaccion['tipo'] = $data['tipo_crear'];
            $nasbicointransaccion['id_transaccion'] = 0;
            $nasbicointransaccion['uid_envio'] = 0;
            $nasbicointransaccion['empresa_envio'] = 0;
            $this->insertarNasbiCoinTransaccion($nasbicointransaccion);

            $monto = floatval($nasbicoin['monto']) + floatval($data['monto']);
            $monto = floatval($monto);
            $dataupdate = [
                'monto' => $monto,
                'fecha' => $fecha,
                'id' => $nasbicoin['id'],
                'uid' => $data['uid'],
                'address' => $nasbicoin['address'],
            ];
            return $this->actualizarNasbiCoin($dataupdate, $tabla);
        }
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
            $nasbicoin['monto']= floatval($this->truncNumber($nasbicoin['monto'], 6));
            $nasbicoin['monto_mask']= $this->maskNumber($nasbicoin['monto'], 6);
            $nasbicoin['estado'] = floatval($nasbicoin['estado']);
            $nasbicoin['empresa'] = floatval($nasbicoin['empresa']);
            $nasbicoin['fecha_creacion'] = floatval($nasbicoin['fecha_creacion']);
            $nasbicoin['fecha_actualizacion'] = floatval($nasbicoin['fecha_actualizacion']);
            $nasbicoin['monto_usd'] = floatval($this->truncNumber(($precios[$nasbicoin['moneda']]*$nasbicoin['monto']), 2));
            $nasbicoin['monto_usd_mask'] = $this->maskNumber($nasbicoin['monto_usd'], 2);
            $nasbicoin['nasbicoin_transacciones'] = null;

            $nabicoins[$x] = $nasbicoin;
        }
        unset($nodo);

        return $nabicoins;
    }

    function insertarNasbiCoinTransaccion(Array $data)
    {
        // $unmes = 2629743;
        // $data['fecha_vencer'] = floatval($data['fecha'] + $unmes);

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
            empresa_envio
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
            '$data[empresa_envio]'
        );";
        parent::query($insertarxnasbicoinxvencer);
        parent::cerrar();
        
    }

    function actualizarNasbiCoin(Array $data, String $tabla)
    {
        parent::conectar();
        $updatexnasbicoin = "UPDATE $tabla 
        SET
            monto = '$data[monto]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]' AND uid = '$data[uid]' AND address = '$data[address]'";
        $update = parent::query($updatexnasbicoin);
        parent::cerrar();
        if($update){
            $request = array('status' => 'success', 'message'=> 'nasbicoin actualizado', 'data' => null);
        }else{
            $request = array('status' => 'fail', 'message'=> 'error al guardar nasbicoin', 'data' => null);
        }
        return $request;
    }
    // FIN NASBICOINS

    // REFERIDO
    function insertarCodigoReferido(Array $data){
        $json = $data['json'];
        if($json['state_pol'] != 4) return array('status' => 'fail', 'message'=> 'no tickets agregados', 'data' => null);
        $data['extra1']['fecha'] = $data['fecha'];
        return $this->actualizarReferido($data['extra1']);
    }

    function actualizarReferido(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $codigo = md5($data['uid'].'_'.$data['empresa']);
        parent::conectar();
        $updatexrefer = "UPDATE referidos_code SET codigo = '$codigo', estado = '$data[estado]', fecha_actualizacion = '$data[fecha]' WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $referido = parent::query($updatexrefer);
        parent::cerrar();
        if(!$referido) return array('status' => 'fail', 'message'=> 'error al insertar codigo', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'codigo insertado', 'data' => null);
    }

    function datosUser(Array $data)
    {
        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
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

    function truncNumber(Float $number, Int $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }

    function maskNumber(Float $numero, Int $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }
}

?>