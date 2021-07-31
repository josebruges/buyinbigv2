<?php
require 'nasbifunciones.php';
// require 'payudata.php';

class PlanesNasbi extends Conexion
{   
    //////////////////////////////
    //  uso 1 venta, 2 compra  //
    ////////////////////////////

    public function verPlanes(Array $data){
        $data['consulta'] = 'ntp.costo';
        $select_precio = $this->selectMonedaLocalUser($data);
        parent::conectar();
        $selectxplanesxnasbi = "SELECT ntp.* $select_precio FROM nasbitickets_plan ntp";
        $planes = parent::consultaTodo($selectxplanesxnasbi);
        parent::cerrar();
        if(!$planes) return array('status' => 'fail', 'message'=> 'no planes', 'data' => null);
        $data['uso'] = 1;
        $data['tipo'] = 3;
        $planes = $this->mapPlanes($planes, $data);
        return array('status' => 'success', 'message'=> 'planes', 'data' => $planes);
    }

    public function verPlanesCompra(Array $data){
        $data['consulta'] = 'ntpc.costo';
        $select_precio = $this->selectMonedaLocalUser($data);
        parent::conectar();
        $selectxplanesxnasbi = "SELECT ntpc.* $select_precio FROM nasbitickets_plan_compra ntpc";
        $planes = parent::consultaTodo($selectxplanesxnasbi);
        parent::cerrar();
        if(!$planes) return array('status' => 'fail', 'message'=> 'no planes', 'data' => null);
        $data['uso'] = 2;
        $data['tipo'] = 4;
        $planes = $this->mapPlanes($planes, $data);
        return array('status' => 'success', 'message'=> 'planes', 'data' => $planes);
    }

    public function ticketsParaCompra(Array $data){
        $data['consulta'] = 'pst.costo_ticket_usd';
        $data['adicional'] = ', (%precio% * pst.rango_inferior_usd) AS rango_inferior_local_user, (%precio% * pst.rango_superior_usd) AS rango_superior_local_user';
        $select_precio = $this->selectMonedaLocalUser($data);
        parent::conectar();
        $selectxtickets = "SELECT pst.* $select_precio FROM productos_subastas_tipo pst WHERE pst.id BETWEEN 1 AND 3;";
        $tickets = parent::consultaTodo($selectxtickets);
        parent::cerrar();
        if(!$tickets) return array('status' => 'fail', 'message'=> 'no tickets', 'data' => null);
        $tickets = $this->mapticketsArray($tickets);
        return array('status' => 'success', 'message'=> 'tickets', 'data' => $tickets);
    }

    public function generarPayU(Array $data){
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['iso_code_2']) || !isset($data['cantidad'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') return $datos_usuario;

        return $this->ticketId($data);
    }

    public function pagarPlan(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])  || !isset($data['uso'])){
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        }

        $datos_usuario = $this->datosUser($data);
        if($datos_usuario['status'] == 'fail') {
            return $datos_usuario;
        }


        if($data['uso'] == 1) $data['table'] = "nasbitickets_plan";
        if($data['uso'] == 2) $data['table'] = "nasbitickets_plan_compra";
        
        $plan = $this->planId($data);
        if($plan['status'] != 'success') return $plan;
        $plan =  $plan['data'];

        //Aqui tengo que pagar falta el send o tarjeta de credito

        $fecha = intval(microtime(true)*1000);

        $idinsert = $this->insertarPlanUsuario([
            'data'  => $data,
            'plan'  => $plan,
            'uso'   => $data['uso'],
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

    public function insertarTicketP2W(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['cantidad']) || !isset($data['uso'])) {

            return array(
                'status'  => 'fail', 
                'message' => 'faltan datos', 
                'data'    => null
            );
        }

        $transferido = null;
        if(isset($data['transferido'])) $transferido = $data['transferido'];

        $tipo = $data['tipo'];
        
        if($tipo == 'entradas300') $tipo = 1;
        if($tipo == 'entradas500') $tipo = 2;
        if($tipo == 'entradas1000') $tipo = 3;
        if($tipo == 'entradas5000') $tipo = 4;

        $fecha = intval(microtime(true)*1000);
        $fechames = $fecha + 2628000000;

        $datosInsertTiquetes = array(
            'conexion'               => true,
            'plan'                   => intval($tipo),
            'uid'                    => intval($data['uid']),
            'empresa'                => intval($data['empresa']),
            'id_nabitickets_usuario' => null,
            'cantidad'               => intval($data['cantidad']),
            'descripcion'            => 'Envío P2W',
            'transferido'            => $transferido,
            'uso'                    => intval($data['uso']),
            'origen'                 => 2,
            'fecha'                  => $fecha,
            'fechames'               => $fechames
        );

        return $this->insertarTicket($datosInsertTiquetes);
    }

    public function ticketsUsuario(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['uso']) || !isset($data['group'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        if($data['group'] == 0 && !isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        $tipo = $data['tipo'];
        if($tipo == 'entradas300') $tipo = 1;
        if($tipo == 'entradas500') $tipo = 2;
        if($tipo == 'entradas1000') $tipo = 3;
        if($tipo == 'entradas5000') $tipo = 4;

        $data['tipo'] = $tipo;

        return $this->verTicketsUsuario($data, 'nasbitickets');
    }

    public function pagarTicketCompra(Array $data)
    {

        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['cantidad']) || !isset($data['uso'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $transferido = null;
        if(isset($data['transferido'])) $transferido = $data['transferido'];
        
        $fecha = intval(microtime(true)*1000);
        $fechames = $fecha + 2628000000;

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
            'fecha' => $fecha,
            'fechames' => $fechames
        ]);
    }

    public function ticketsUsuarioHistorico(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['uso']) || !isset($data['group'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if($data['group'] == 0 && !isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        $tipo = $data['tipo'];
        if($tipo == 'entradas300') $tipo = 1;
        if($tipo == 'entradas500') $tipo = 2;
        if($tipo == 'entradas1000') $tipo = 3;
        if($tipo == 'entradas5000') $tipo = 4;

        $data['tipo'] = $tipo;

        return $this->verTicketsUsuario($data, 'nasbitickets_historico');
    }

    public function detallePayU(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        parent::conectar();
        $selectxpayu = "SELECT rpu.* 
        FROM request_payu rpu
        WHERE rpu.id_transaccion = '$data[uid]' AND rpu.tipo = '$data[tipo]'
        ORDER BY rpu.fecha_actualizacion DESC
        LIMIT 1;";
        $payu = parent::consultaTodo($selectxpayu);
        parent::cerrar();

        if(count($payu) <= 0) return array('status' => 'fail', 'message'=> 'no payu', 'data' => null);
        $payu = $payu[0];
        $payu['json'] = json_decode($payu['json'], true);
        return array('status' => 'success', 'message'=> 'pay', 'data' => $payu);
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

    function ticketId(Array $data){
        $data['consulta'] = 'pst.costo_ticket_usd';
        $data['adicional'] = ', (%precio% * pst.rango_inferior_usd) AS rango_inferior_local_user, (%precio% * pst.rango_superior_usd) AS rango_superior_local_user';
        $select_precio = $this->selectMonedaLocalUser($data);
        parent::conectar();
        $selectxtickets = "SELECT pst.* $select_precio FROM productos_subastas_tipo pst WHERE pst.id = '$data[id]';";
        $tickets = parent::consultaTodo($selectxtickets);
        parent::cerrar();
        if(!$tickets) return array('status' => 'fail', 'message'=> 'no tickets', 'data' => null);
        $tickets = $this->mapticketsArray($tickets, $data);
        return array('status' => 'success', 'message'=> 'tickets', 'data' => $tickets[0]);
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

    function insertarTicket(Array $data)
    {

        if( $data['plan'] == 5 ) {//1 TICKET DIAMOND = 2 TICKETS PLATINUM
            $data['plan'] = 4;
            $data['cantidad'] = intval($data['cantidad']) * 2;
        }

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

        //cuando recarga tickets
        $returnMail = null;
        if($data["origen"]==2){
            $returnMail = $this->envio_correo_recarga_de_tickets($data);
        }
        return array('status' => 'success', 'message'=> 'ticket registrado', 'data' => $insert, 'dataExtra' => $data, 'returnMail' => $returnMail);
        

    }

    function verTicketsUsuario(Array $data, String $tabla)
    {
        
        $tipo = "";
        $uso = "";
        $estado = "";
        $joinproducto = "";
        $productoselect = "";
        if($tabla == 'nasbitickets') $estado = "AND estado = 1";
        if($tabla == 'nasbitickets_historico'){
            $joinproducto = "LEFT JOIN productos p ON nt.id_producto = p.id ";
            $productoselect = ", p.titulo";
        }
        $innerplan = "INNER JOIN nasbitickets_plan ntp ON nt.plan = ntp.id AND ntp.id <> 5";//DESCARTAMOS LOS DIAMANTES
        if($data['tipo'] != 'all') $tipo = "AND nt.plan = '$data[tipo]'";
        if($data['uso'] != 'all') $uso = "AND nt.uso = '$data[uso]'";

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;


        $fecha = "";
        if(isset($data['fecha_inicio'])){
            $fecha_inicio = $data['fecha_inicio'];
            $horas24 = 86400000;
            $fecha_fin = $fecha_inicio + $horas24;
            $fecha = "AND nt.fecha_creacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }


        $selectxticketsxuser = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT nt.*, ntp.nombre AS nombre_plan $productoselect
                FROM $tabla nt 
                $innerplan
                $joinproducto
                JOIN (SELECT @row_number := 0) r
                WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' $estado $tipo $uso $fecha
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";

        if($data['tipo'] == 'all' && $data['group'] == 1){
            $selectxticketsxuser = "SELECT nt.plan, SUM(nt.cantidad) AS cantidad, ntp.nombre AS nombre_plan 
            FROM $tabla nt 
            $innerplan 
            WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' $estado $tipo $uso $fecha 
            GROUP BY plan;";
        }

        parent::conectar();
        // echo $selectxticketsxuser;
        $tickets = parent::consultaTodo($selectxticketsxuser);
        if(!$tickets){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no tickets', 'data' => null);
        }
        $selecttodos = "SELECT COUNT(nt.id) AS contar, SUM(nt.cantidad) AS todos_los_tickets
        FROM $tabla nt 
        $innerplan
        WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' $estado $tipo $uso $fecha";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosregistrostickets = floatval($todoslosproductos[0]['contar']);
        $alltickets = floatval($todoslosproductos[0]['todos_los_tickets']);
        $totalpaginas = $todoslosregistrostickets/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $tickets = $this->mapTickets($tickets, $data['group']);

        $platinum = array_filter( $tickets, function( $t ) {
            return $t['plan'] == 4; 
        });

        parent::addLog("TIENE PLATINUM ======> ". json_encode($platinum));

        if( count($platinum) > 0 ) {//CREAMOS LA CATEGORÍA DIAMOND EN BASE A LOS TICKETES PLATINUM
            $diamond = current( $platinum );//OBTENEMOS EL ELEMENTO ACTUAL DEL ARREGLO FILTRADO
            $diamond['plan'] = 5;
            $diamond['nombre_plan'] = 'Diamond pro';//TICKETE DIAMOND QUE SE VE COMO PLATINUM
            $diamond['cantidad'] = intval( $diamond['cantidad'] );
            // $diamond['cantidad'] = intval( $diamond['cantidad']/2 );

            array_push($tickets, $diamond);
        }

        if($data['group'] != 1) return array('status' => 'success', 'message'=> 'planes', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'tickets' => count($tickets), 'total_tickets' => $todoslosregistrostickets, 'todos_los_tickets'=> $alltickets, 'data' => $tickets);

        if($data['group'] == 1) return array('status' => 'success', 'message'=> 'planes', 'todos_los_tickets'=> $alltickets, 'data' => $tickets);
    }

    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2'])){
            $consulta = $data['consulta'];

            $adicional = "";
            if($data['iso_code_2'] == 'US'){
                $monedas_local['costo_dolar'] = 1;
                $monedas_local['code'] = 'USD';
                if(isset($data['adicional'])) $adicional = str_replace('%precio%', $monedas_local['costo_dolar'], $data['adicional']);
                $select_precio = ", (".$monedas_local['costo_dolar']."*".$consulta.") AS precio_local_user, IF(1 < 2, '".$monedas_local['code']."', '') AS moneda_local_user $adicional";
            }

            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    if(isset($data['adicional'])) $adicional = str_replace('%precio%', $monedas_local['costo_dolar'], $data['adicional']);
                    $select_precio = ", (".$monedas_local['costo_dolar']."*".$consulta.") AS precio_local_user, IF(1 < 2, '".$monedas_local['code']."', '') AS moneda_local_user $adicional";
                }
            }
        }

        return $select_precio;
    }

    function datosUser(Array $data)
    {
        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM buyinbig.empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";

        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios($usuario, $data['empresa']);
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }

    function mapPlanes(Array $planes, Array $user = null)
    {
        $payu = new PayU();
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

            $plan['payu'] = null;
            if(isset($user) && isset($user['uid']) && isset($user['empresa'])){
                $precio = $plan['precio_local_user'];
                $moneda = $plan['moneda_local_user'];
                if(!isset($payu->currency_test[$moneda])){
                    $precio = $plan['precio_usd'];
                    $moneda = 'USD';
                }
                $plan['payu']['merchantId'] = $payu->merchantId_test;
                $plan['payu']['accountId'] = $payu->accountId_test;
                $tipo_descripcion = 'Venta';
                if($user['tipo'] == 4) $tipo_descripcion = 'Compra';
                $plan['payu']['description'] = 'Plan nasbi '.$tipo_descripcion.' '.$plan['nombre'].' Nasbi';
                $plan['payu']['referenceCode'] = $user['uid']; //Mirar esta referencia
                $plan['payu']['extra1'] = addcslashes(json_encode(array( //Mirar esta referencia
                    'tipo' => $user['tipo'],
                    'tipo_descripcion' => 'Plan tickets '.$tipo_descripcion.' nasbi',
                    'id' => $plan['id'],
                    'uid' => $user['uid'],
                    'empresa' => $user['empresa'],
                    'uso' => $user['uso'],
                )), '"\\/');
                $plan['payu']['amount'] = $this->truncNumber($precio, 2);
                $plan['payu']['tax'] = 0;
                $plan['payu']['taxReturnBase'] = 0;
                $plan['payu']['currency'] = $moneda;
                $plan['payu']['signature_text'] = $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$plan['payu']['referenceCode'].'~'.$precio.'~'.$moneda;
                $plan['payu']['signature'] = hash('md5', $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$plan['payu']['referenceCode'].'~'.$precio.'~'.$moneda);
                $plan['payu']['test'] = 1;
                $plan['payu']['lng'] = 'es';
                $comprador = $this->datosUser($user)['data'];
                $plan['payu']['buyerFullName'] = $comprador['nombre'];
                $plan['payu']['buyerEmail'] = $comprador['correo'];
                // respuesta del cliente
                $plan['payu']['responseUrl'] = 'https://nasbi.com/content/nasbi-tickets-compra.php';
                // respuesta para la api
                $plan['payu']['confirmationUrl'] = 'https://testnet.foodsdnd.com:8185/crearpublicacion/requestPayU';
            }
            $planes[$x] = $plan;
        }
        unset($payu);

        return $planes;
    }


    function mapTickets(Array $tickets, $group)
    {
        foreach ($tickets as $x => $ticket) {
            $ticket['plan'] = floatval($ticket['plan']);
            $ticket['cantidad'] = floatval($ticket['cantidad']);

            if($group != 1){
                $ticket['id'] = floatval($ticket['id']);
                $ticket['uid'] = floatval($ticket['uid']);
                $ticket['empresa'] = floatval($ticket['empresa']);
                if(isset($ticket['id_nabitickets_usuario'])) $ticket['id_nabitickets_usuario'] = floatval($ticket['id_nabitickets_usuario']);
                if(isset($ticket['id_nasbitickets'])) $ticket['id_nasbitickets'] = floatval($ticket['id_nasbitickets']);
                if(isset($ticket['transferido'])) $ticket['transferido'] = floatval($ticket['transferido']);
                $ticket['uso'] = floatval($ticket['uso']);
                $ticket['uso_descripcion'] = 'Venta de artículo para subasta';
                if($ticket['uso'] == 2) $ticket['uso_descripcion'] = 'Compra de entrada a una subasta';

                if(isset($ticket['origen'])) $ticket['origen'] = floatval($ticket['origen']);
                if(isset($ticket['estado'])) $ticket['estado'] = floatval($ticket['estado']);
                $ticket['fecha_creacion'] = floatval($ticket['fecha_creacion']);
                if(isset($ticket['fecha_actualizacion'])) $ticket['fecha_actualizacion'] = floatval($ticket['fecha_actualizacion']);
                if(isset($ticket['fecha_vencimiento'])) $ticket['fecha_vencimiento'] = floatval($ticket['fecha_vencimiento']);
                $ticket['cantidad_mask']= $this->maskNumber($ticket['cantidad'], 0);
            }

            $tickets[$x] = $ticket;
        }

        return $tickets;
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

    function mapticketsArray(Array $planes, Array $user = null)
    {
        $payu = new PayU();
        foreach ($planes as $x => $plan) {
            $plan['id'] = floatval($plan['id']);
            $plan['rango_inferior_usd'] = floatval($plan['rango_inferior_usd']);
            $plan['rango_superior_usd'] = floatval($plan['rango_superior_usd']);
            $plan['num_apostadores'] = json_decode($plan['num_apostadores']);
            $cantidad = 1;
            if(isset($user['cantidad'])) $cantidad = $user['cantidad'];

            $plan['costo_ticket_usd'] = floatval($plan['costo_ticket_usd'] * $cantidad );
            $plan['costo_ticket_usd_mask'] = $this->maskNumber($plan['costo_ticket_usd'], 2);
            if(isset($plan['precio_local_user'])){
                $plan['precio_local_user'] = floatval($this->truncNumber(($plan['precio_local_user'] * $cantidad),2));
                $plan['precio_local_user_mask']= $this->maskNumber($plan['precio_local_user'], 2);

                $plan['rango_inferior_local_user'] = floatval($this->truncNumber(($plan['rango_inferior_local_user'] * $cantidad),2));
                $plan['rango_inferior_local_user_mask']= $this->maskNumber($plan['rango_inferior_local_user'], 2);

                $plan['rango_superior_local_user'] = floatval($this->truncNumber(($plan['rango_superior_local_user'] * $cantidad),2));
                $plan['rango_superior_local_user_mask']= $this->maskNumber($plan['rango_superior_local_user'], 2);

            }else{
                $plan['precio_local_user'] = $plan['costo_ticket_usd'];
                $plan['precio_local_user_mask'] = $plan['costo_ticket_usd_mask'];
                $plan['moneda_local_user'] = 'USD';
            }
            
            if(isset($user) && isset($user['uid']) && isset($user['empresa'])){
                $plan['payu'] = null;
                $precio = $plan['precio_local_user']*$cantidad;
                $moneda = $plan['moneda_local_user'];
                if(!isset($payu->currency_test[$moneda])){
                    $precio = $plan['costo_ticket_usd'];
                    $moneda = 'USD';
                }
                $plan['payu']['merchantId'] = $payu->merchantId_test;
                $plan['payu']['accountId'] = $payu->accountId_test;
                $plan['payu']['description'] = 'Ticket de compra '.$plan['descripcion'].' Nasbi';
                $plan['payu']['referenceCode'] = $user['uid']; //Mirar esta referencia
                $plan['payu']['extra1'] = addcslashes(json_encode(array( //Mirar esta referencia
                    'tipo' => 2,
                    'tipo_descripcion' => 'Compra individual de ticket compra',
                    'uid' => $user['uid'],
                    'empresa' => $user['empresa'],
                    'cantidad' => $cantidad,
                    'uso' => 2
                )), '"\\/');
                $plan['payu']['amount'] = $this->truncNumber($precio, 2);
                $plan['payu']['tax'] = 0;
                $plan['payu']['taxReturnBase'] = 0;
                $plan['payu']['currency'] = $moneda;
                $plan['payu']['signature_text'] = $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$plan['payu']['referenceCode'].'~'.$precio.'~'.$moneda;
                $plan['payu']['signature'] = hash('md5', $payu->apikey_test.'~'.$payu->merchantId_test.'~'.$plan['payu']['referenceCode'].'~'.$precio.'~'.$moneda);
                $plan['payu']['test'] = 1;
                $plan['payu']['lng'] = 'es';
                $comprador = $this->datosUser($user)['data'];
                $plan['payu']['buyerFullName'] = $comprador['nombre'];
                $plan['payu']['buyerEmail'] = $comprador['correo'];
                // respuesta del cliente
                $plan['payu']['responseUrl'] = 'https://nasbi.com/content/nasbi-tickets-compra.php';
                // respuesta para la api
                $plan['payu']['confirmationUrl'] = 'https://testnet.foodsdnd.com:8185/crearpublicacion/requestPayU';
            }
            $planes[$x] = $plan;
        }
        unset($payu);
        return $planes;
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
                        // $newarray[$key] = $array[$key];
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



    //funciones para envio de correo 

    public function envio_correo_recarga_de_tickets(Array $data)
    {
        $data_user =  $this->datosUserGeneral([
            'uid'     => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $data_tipos_subasta = $this->get_data_tipo_subasta_segun_su_tipo([
            'id' => $data["plan"]
        ]);


        if( count( $data_tipos_subasta ) > 0 ){
           $data_tipos_subasta = $data_tipos_subasta[0];
           return $this->distribuir_envio_de_correo_pot_tipo($data, $data_user["data"], $data_tipos_subasta);
        }
        return null;
    }

    function datosUserGeneral( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }

    function get_data_tipo_subasta_segun_su_tipo(Array $data ) {
        parent::conectar();
        $tipos_subasta = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas_tipo WHERE id = '$data[id]'; ");
        parent::cerrar();
        return $tipos_subasta;
    }

    function distribuir_envio_de_correo_pot_tipo(Array $data, Array $data_user, Array $data_tipos_subasta){

        switch ($data["plan"]) {
            case 1:
                return $this->html_cargue_tickets_bronce($data_user,$data,$data_tipos_subasta); 
                break;
            case 2:
                return $this->html_cargue_tickets_silver($data_user,$data,$data_tipos_subasta); 
                break; 

            case 3:
                return $this->html_cargue_tickets_gold($data_user,$data,$data_tipos_subasta); 
                break; 
        
            case 4:
                return $this->html_cargue_tickets_platinum($data_user,$data,$data_tipos_subasta); 
                break; 
        
            case 5:
                return $this->html_cargue_tickets_diamond($data_user,$data,$data_tipos_subasta); 
                break;

            default:
               break;
        }
    }
   
   
    public function html_cargue_tickets_bronce(Array $data_user, Array $data_carga, Array $data_subasta){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo1.html");


        $html = str_replace("{{trans238_brand}}", $json->trans238_brand, $html);
        $html = str_replace("{{trans239}}", $json->trans239, $html);
        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans215}}", $json->trans215, $html);
        $html = str_replace("{{num_tickets}}", $this->maskNumber($data_carga["cantidad"], 2), $html);
        $html = str_replace("{{trans240}}", $json->trans240, $html);
        $html = str_replace("{{trans222}}", $json->trans222, $html);
        $html = str_replace("{{trans217}}", $json->trans217, $html);
        $html = str_replace("{{trans35_unidad}}", "USD", $html);
        $html = str_replace("{{trans59_}}", $json->trans59_, $html);
        $html = str_replace("{{trans241_valor_uno}}", $this->maskNumber($data_subasta["rango_inferior_usd"], 2), $html);
        $html = str_replace("{{trans242_valor_dos}}",$this->maskNumber($data_subasta["rango_superior_usd"], 2), $html);
        $html = str_replace("{{trans220}}", $json->trans220, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans221}}", $json->trans221, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para       = $data_user['correo'];// . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo     = $json->trans161_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        $respuesta = json_decode($response, true);
        return $respuesta;
   
    }


    public function html_cargue_tickets_silver(Array $data_user, Array $data_carga, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo2.html");
        

        $html = str_replace("{{trans233_brand}}", $json->trans233_brand, $html);
        $html = str_replace("{{trans234}}", $json->trans234, $html);

        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans215}}", $json->trans215, $html);
        $html = str_replace("{{num_tickets}}", $this->maskNumber($data_carga["cantidad"], 2), $html);
        $html = str_replace("{{trans235}}", $json->trans235, $html);

        $html = str_replace("{{trans222}}", $json->trans222, $html);
        $html = str_replace("{{trans217}}", $json->trans217, $html);
        $html = str_replace("{{trans35_unidad}}", "USD", $html);
        $html = str_replace("{{trans59_}}", $json->trans59_, $html);
        $html = str_replace("{{trans236_valor_uno}}", $this->maskNumber($data_subasta["rango_inferior_usd"], 2), $html);
        $html = str_replace("{{trans237_valor_dos}}",$this->maskNumber($data_subasta["rango_superior_usd"], 2), $html);
        $html = str_replace("{{trans220}}", $json->trans220, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans221}}", $json->trans221, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans161_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }

    public function html_cargue_tickets_gold(Array $data_user, Array $data_carga, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo3.html");
        

        $html = str_replace("{{trans228_brand}}", $json->trans228_brand, $html);
        $html = str_replace("{{trans229}}", $json->trans229, $html);

        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans215}}", $json->trans215, $html);
        $html = str_replace("{{num_tickets}}", $this->maskNumber($data_carga["cantidad"], 2), $html);
        $html = str_replace("{{trans230}}", $json->trans230, $html);

        $html = str_replace("{{trans222}}", $json->trans222, $html);
        $html = str_replace("{{trans217}}", $json->trans217, $html);
        $html = str_replace("{{trans35_unidad}}", "USD", $html);
        $html = str_replace("{{trans59_}}", $json->trans59_, $html);
        $html = str_replace("{{trans231_valor_uno}}", $this->maskNumber($data_subasta["rango_inferior_usd"], 2), $html);
        $html = str_replace("{{trans232_valor_dos}}",$this->maskNumber($data_subasta["rango_superior_usd"], 2), $html);
        $html = str_replace("{{trans220}}", $json->trans220, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans221}}", $json->trans221, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans161_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }

    public function html_cargue_tickets_platinum(Array $data_user, Array $data_carga, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo4.html");
        

        $html = str_replace("{{trans223_brand}}", $json->trans223_brand, $html);
        $html = str_replace("{{trans224}}", $json->trans224, $html);

        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans215}}", $json->trans215, $html);
        $html = str_replace("{{num_tickets}}", $this->maskNumber($data_carga["cantidad"], 2), $html);
        $html = str_replace("{{trans225}}", $json->trans225, $html);

        $html = str_replace("{{trans222}}", $json->trans222, $html);
        $html = str_replace("{{trans217}}", $json->trans217, $html);
        $html = str_replace("{{trans35_unidad}}", "USD", $html);
        $html = str_replace("{{trans59_}}", $json->trans59_, $html);
        $html = str_replace("{{trans226_valor_uno}}", $this->maskNumber($data_subasta["rango_inferior_usd"], 2), $html);
        $html = str_replace("{{trans227_valor_dos}}",$this->maskNumber($data_subasta["rango_superior_usd"], 2), $html);
        $html = str_replace("{{trans220}}", $json->trans220, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans221}}", $json->trans221, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans161_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }

    public function html_cargue_tickets_diamond(Array $data_user, Array $data_carga, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo5.html");
        

        $html = str_replace("{{trans212_brand}}", $json->trans212_brand, $html);
        $html = str_replace("{{trans213}}", $json->trans213, $html);

        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans215}}", $json->trans215, $html);
        $html = str_replace("{{num_tickets}}", $this->maskNumber($data_carga["cantidad"], 2), $html);
        $html = str_replace("{{trans214}}", $json->trans214, $html);

        $html = str_replace("{{trans222}}", $json->trans222, $html);
        $html = str_replace("{{trans217}}", $json->trans217, $html);
        $html = str_replace("{{trans35_unidad}}", "USD", $html);
        $html = str_replace("{{trans59_}}", $json->trans59_, $html);
        $html = str_replace("{{trans218_valor_1}}", $this->maskNumber($data_subasta["rango_inferior_usd"], 2), $html);
        $html = str_replace("{{trans219_valor_2}}",$this->maskNumber($data_subasta["rango_superior_usd"], 2), $html);
        $html = str_replace("{{trans220}}", $json->trans220, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans221}}", $json->trans221, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans161_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


    //correos de paquete de tickets NO ESTAN INPLEMENTADOS 

    public function html_compra_de_paquete_tickets_solo_bronce(Array $data_user, Array $data_carga){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo6bronzeproo.html");
        

        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans24_}}", $json->trans24_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans26_}}", $json->trans26_, $html);
        $html = str_replace("{{trans27_}}", $json->trans27_, $html);
        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);

        $html = str_replace("{{trans33_cantidad_paquete}}", $data["cantidad_ticket"], $html);
        $html = str_replace("{{trans34_valor}}", $this->maskNumber($data_carga["cantidad_ticket_precio"], 2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans40_}}", $json->trans40_, $html);
        $html = str_replace("{{trans41_}}", $json->trans41_, $html);
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        
        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


    public function html_compra_de_paquete_tickets_bronce_y_silver(Array $data_user, Array $data_carga){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo7silverproo.html");
        

        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans42_}}", $json->trans42_, $html);
        $html = str_replace("{{trans43_}}", $json->trans43_, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans44_}}", $json->trans44_, $html);
        $html = str_replace("{{trans27_}}", $json->trans27_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans45_}}", $json->trans45_, $html);
        $html = str_replace("{{trans46_}}", $json->trans46_, $html);
        $html = str_replace("{{trans47_cantidad_paquete_pro}}", $data["cantidad_ticket_bronce"], $html);

        $html = str_replace("{{trans48_}}", $json->trans48_, $html);
        $html = str_replace("{{trans49_cantidad_paquete_silver}}", $data["cantidad_ticket_silver"], $html);
        $html = str_replace("{{trans50_}}", $json->trans50_, $html);
        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans30_}}", $json->trans30_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);
        $html = str_replace("{{trans33_cantidad_paquete}}",$data_carga["cantidad_silver_comprados"], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($data_carga["precio_silver_comprados"],2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans52_}}", $json->trans52_, $html);

       
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        
        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


    public function html_compra_de_paquete_gold_trae_bronce_silver_gold(Array $data_user, Array $data_carga, Array $data_info){
        //paquete bronce trae  bronce, silver y gold
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo8goldproo.html");
        
        

        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans42_}}", $json->trans42_, $html);
        $html = str_replace("{{trans53_}}", $json->trans53_, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans44_}}", $json->trans44_, $html);
        $html = str_replace("{{trans27_}}", $json->trans27_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans54_}}", $json->trans54_, $html);

        $html = str_replace("{{trans46_}}", $json->trans46_, $html);
        $html = str_replace("{{trans33_cantidad_paquete_bron}}", $data["cantidad_ticket_bronce"], $html);

        $html = str_replace("{{trans48_}}", $json->trans48_, $html);
        $html = str_replace("{{trans49_cantidad_paquete_silver}}", $data["cantidad_ticket_silver"], $html);

        $html = str_replace("{{trans55_}}", $json->trans55_, $html);
        $html = str_replace("{{trans56_cantidad_paquete_gold}}", $data["cantidad_ticket_gold"], $html);

        $html = str_replace("{{trans75_}}", $json->trans75_, $html);
        
        $html = str_replace("{{trans58_valor_uno}}", $data_info["valor_uno_vender"], $html);
        $html = str_replace("{{trans60_valor_dos}}", $data_info["valor_dos_vender"], $html);

        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans30_}}", $json->trans30_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);

        $html = str_replace("{{trans61_}}", $json->trans61_, $html);

        

        $html = str_replace("{{trans33_cantidad_paquete}}",$data_carga["cantidad_gold_comprados"], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($data_carga["precio_gold_comprados"],2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans40_}}", $json->trans40_, $html);
        $html = str_replace("{{trans41_}}", $json->trans41_, $html);
        
       
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        
        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }



    public function html_compra_de_paquete_platinum_trae_bronce_silver_gold_platinum(Array $data_user, Array $data_carga, Array $data_info){
        //paquete bronce trae  bronce, silver y gold
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo9platinumpro.html");
        
    
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans63_}}", $json->trans63_, $html);
        $html = str_replace("{{trans53_}}", $json->trans53_, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans44_}}", $json->trans44_, $html);
        $html = str_replace("{{trans27_}}", $json->trans27_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans64_}}", $json->trans64_, $html);
        $html = str_replace("{{trans46_}}", $json->trans46_, $html);
        $html = str_replace("{{trans33_cantidad_paquete_bron}}", $data["cantidad_ticket_bronce"], $html);
        $html = str_replace("{{trans48_}}", $json->trans48_, $html);
        $html = str_replace("{{trans49_cantidad_paquete_silver}}", $data["cantidad_ticket_silver"], $html);
        $html = str_replace("{{trans55_}}", $json->trans55_, $html);
        $html = str_replace("{{trans56_cantidad_paquete_gold}}", $data["cantidad_ticket_gold"], $html);
        $html = str_replace("{{trans65_}}", $json->trans65_, $html);
        $html = str_replace("{{trans66_cantidad_resumen_platinum}}", $data["cantidad_ticket_platinum"], $html);
        $html = str_replace("{{trans68_}}", $json->trans68_, $html);
        $html = str_replace("{{trans58_valor_uno}}", $data_info["valor_uno_vender"], $html);
        $html = str_replace("{{trans60_valor_dos}}", $data_info["valor_dos_vender"], $html);
        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans30_}}", $json->trans30_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);
        $html = str_replace("{{trans61_}}", $json->trans61_, $html);
        $html = str_replace("{{trans33_cantidad_paquete}}",$data_carga["cantidad_platinum_comprados"], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($data_carga["precio_platinum_comprados"],2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans40_}}", $json->trans40_, $html);
        $html = str_replace("{{trans41_}}", $json->trans41_, $html);
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }



    public function html_compra_de_paquete_diamond_trae_bronce_silver_gold_platinum_diamond(Array $data_user, Array $data_carga, Array $data_info){
        //paquete bronce trae  bronce, silver y gold
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo10diamondpro.html");
        
        

        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans42_}}", $json->trans42_, $html);
        $html = str_replace("{{trans70_}}", $json->trans70_, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans44_}}", $json->trans44_, $html);
        $html = str_replace("{{trans71_}}", $json->trans71_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
 

        $html = str_replace("{{trans46_}}", $json->trans46_, $html);
        $html = str_replace("{{trans33_cantidad_paquete_bron}}", $data["cantidad_ticket_bronce"], $html);

        $html = str_replace("{{trans48_}}", $json->trans48_, $html);
        $html = str_replace("{{trans49_cantidad_paquete_silver}}", $data["cantidad_ticket_silver"], $html);

        $html = str_replace("{{trans55_}}", $json->trans55_, $html);
        $html = str_replace("{{trans56_cantidad_paquete_gold}}", $data["cantidad_ticket_gold"], $html);

        $html = str_replace("{{trans65_}}", $json->trans65_, $html);
        $html = str_replace("{{trans66_cantidad_resumen_platinum}}", $data["cantidad_ticket_platinum"], $html);

        $html = str_replace("{{trans72_}}", $json->trans65_, $html);
        $html = str_replace("{{trans73_cantidad_resumen_diamond}}", $data["cantidad_ticket_diamond"], $html);

        $html = str_replace("{{trans35_unidad}}", $json->trans35_unidad, $html);

        $html = str_replace("{{trans74_}}", $json->trans74_, $html);
        
        
        $html = str_replace("{{trans58_valor_uno}}", $data_info["valor_uno_vender"], $html);
        $html = str_replace("{{trans60_valor_dos}}", $data_info["valor_dos_vender"], $html);

        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans30_}}", $json->trans30_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);

        $html = str_replace("{{trans76_}}", $json->trans76_, $html);
        
          


        $html = str_replace("{{trans33_cantidad_paquete}}",$data_carga["cantidad_diamond_comprados"], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($data_carga["precio_diamond_comprados"],2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans40_}}", $json->trans40_, $html);
        $html = str_replace("{{trans41_}}", $json->trans41_, $html);
        
       
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        
        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }



    public function html_compra_de_paquete_full_access_trae_todos(Array $data_user, Array $data_carga, Array $data_info){
        //FULL ACCES PAQUETE
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo11fullaccess.html");
        

        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans42_}}", $json->trans42_, $html);
        $html = str_replace("{{trans77_}}", $json->trans77_, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
        $html = str_replace("{{trans78_}}", $json->trans78_, $html);
        $html = str_replace("{{trans71_}}", $json->trans71_, $html);
        $html = str_replace("{{signo_amdiracion_open}}", $json->signo_amdiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans79_}}", $json->trans79_, $html);

        $html = str_replace("{{trans46_}}", $json->trans46_, $html);
        $html = str_replace("{{trans33_cantidad_paquete_bron}}", $json->trans80_, $html);

        $html = str_replace("{{trans48_}}", $json->trans48_, $html);
        $html = str_replace("{{trans49_cantidad_paquete_silver}}", $json->trans80_, $html);

        $html = str_replace("{{trans55_}}", $json->trans55_, $html);
        $html = str_replace("{{trans56_cantidad_paquete_gold}}", $json->trans80_, $html);

        $html = str_replace("{{trans65_}}", $json->trans65_, $html);
        $html = str_replace("{{trans66_cantidad_resumen_platinum}}", $json->trans80_, $html);

        $html = str_replace("{{trans72_}}", $json->trans65_, $html);
        $html = str_replace("{{trans73_cantidad_resumen_diamond}}", $json->trans80_, $html);

        $html = str_replace("{{trans35_unidad}}", $json->trans35_unidad, $html);
        $html = str_replace("{{trans81_}}", $json->trans81_, $html);

        $html = str_replace("{{trans74_}}", $json->trans74_, $html);
        
        
        $html = str_replace("{{trans58_valor_uno}}", $data_info["valor_uno_vender"], $html);
        $html = str_replace("{{trans60_valor_dos}}", $data_info["valor_dos_vender"], $html);

        $html = str_replace("{{trans28_}}", $json->trans28_, $html);
        $html = str_replace("{{trans29_}}", $json->trans29_, $html);
        $html = str_replace("{{trans30_}}", $json->trans30_, $html);
        $html = str_replace("{{trans31_}}", $json->trans31_, $html);

        $html = str_replace("{{trans76_}}", $json->trans76_, $html);
    
        $html = str_replace("{{trans33_cantidad_paquete}}",$data_carga["cantidad_diamond_comprados"], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($data_carga["precio_diamond_comprados"],2), $html);
        $html = str_replace("{{trans35_unidad}}", $data_carga["moneda"], $html);
        
        $html = str_replace("{{trans36_}}", $json->trans36_, $html);
        $html = str_replace("{{trans37_}}", $json->trans37_, $html);
        $html = str_replace("{{trans38_}}", $json->trans38_, $html);
        $html = str_replace("{{trans39_}}", $json->trans39_, $html);
        $html = str_replace("{{trans40_}}", $json->trans40_, $html);
        $html = str_replace("{{trans41_}}", $json->trans41_, $html);
    
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com, gissotk09@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans162_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }



    
    
    
    
    //FIN correos de paquete de tickets NO ESTAN INPLEMENTADOS 

   

  // fin funciones para envio de correo 

}
?>