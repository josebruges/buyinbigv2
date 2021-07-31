<?php
require 'nasbifunciones.php';

class Referidos extends Conexion
{

    private $valorreferidousd = 39;
    private $porcentajegananciarefer = 0.1;
    private $direccionrecibe = [
        'Nasbigold'=> [
            'uid' => 1,
            'empresa' => 1,
            'address' => 'c9304a51a279633ce19b337553d2ff29'
        ],
        'Nasbiblue'=> [
            'uid' => 1,
            'empresa' => 1,
            'address' => '682258b004ff0b0926b05b5dff79dcc7'
        ],
    ];

    public function contactoConLider(Array $data){
        
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $data = $this->mapeoAddReferido($data);

        $referido = $this->referUsuario($data);
        if($referido['status'] == 'success') return $referido;
        
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $insertarxrefer = "INSERT INTO referidos_code
        (
            uid,
            empresa,
            estado,
            tipo,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '1',
            '1',
            '$fecha',
            '$fecha'
        );";

        $referido = parent::query($insertarxrefer);
        parent::cerrar();

        if(!$referido) return array('status' => 'fail', 'message'=> 'no se pudo completar la solicitud', 'data' => null);

        if ( $data['email_refer'] == 1 || $data['email_refer'] == '1' ) {
            // Envio de correos de BIENVENIDA USUARIOS NASBI : REFERIRI NEGOCIO
            /*$htmlEmail = $this->getWelcomeEmailReferEmpresa( $data );
            $this->sendEmail($data, $htmlEmail);*/
            
        }else{
            // Envio de correos de BIENVENIDA USUARIOS NASBI
            /*$htmlEmail = $this->getWelcomeEmailReferEmpresa( $data );
            $this->sendEmail($data, $htmlEmail);*/
        }




        return array('status' => 'success', 'message'=> 'solicitud completa', 'data' => null);
    }

    public function referUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectxrefer = "SELECT rc.*, rce.nombre AS estado_descripcion
        FROM referidos_code rc 
        INNER JOIN referidos_code_estado rce ON rc.estado = rce.id
        WHERE rc.uid = '$data[uid]' AND rc.empresa = '$data[empresa]'";
        $referido = parent::consultaTodo($selectxrefer);
        parent::cerrar();
        if(count($referido) <= 0) return array('status' => 'fail', 'message'=> 'no referido','data' => null);
            
        $referido = $this->mapReferido($referido)[0];

        if($referido['estado'] == 2){

            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            unset($nodo);
            $referido['precio_nasbigold'] = $this->truncNumber(($this->valorreferidousd / $precios['Nasbigold']), 6);
            $referido['precio_nasbigold_mask'] = $this->maskNumber($referido['precio_nasbigold'], 6);
            $referido['precio_nasbiblue'] = $this->truncNumber(($this->valorreferidousd / $precios['Nasbiblue']), 6);
            $referido['precio_nasbiblue_mask'] = $this->maskNumber($referido['precio_nasbiblue'], 6);

            $payu = new PayU();
            $moneda_local = $this->selectMonedaLocalUser($data);
            $precio = floatval($this->truncNumber(($moneda_local['costo_dolar']*$this->valorreferidousd), 2));
            $moneda = $moneda_local['code'];
            if(!isset($payu->currency_test[$moneda])){
                $precio = $data['monto_usd'];
                $moneda = 'USD';
            }
            $payudata['merchantId'] = $payu->merchantId_test;
            $payudata['accountId'] = $payu->accountId_test;
            $payudata['description'] = 'Pagar orden referido nasbi';
            $payudata['referenceCode'] = $data['uid']; //Mirar esta referencia
            $payudata['extra1'] = json_encode(array( //Mirar esta referencia
                'tipo' => 6,
                'tipo_descripcion' => 'Pagar orden referido nasbi',
                'uid' => $data['uid'],
                'empresa' => $data['empresa'],
                'estado' => 3
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
            $referido['payu'] = $payudata;
        }
        return array('status' => 'success', 'message'=> 'referido', 'data' => $referido);
    }

    public function confirmarLider(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $data['fecha'] = intval(microtime(true)*1000);
        $data['estado'] = 2;
        $data['message_success'] = 'confirmado por el lider';
        $data['message_fail'] = 'no confirmado por el lider';

        return $this->actualizarReferido($data);
    }

    public function eliminar(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])) return $request = array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        parent::conectar();
        $fecha = intval(microtime(true)*1000);
        $deletedireccion = "UPDATE referidos_code SET estado = 0, fecha_actualizacion = '$fecha' WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $eliminar = parent::query($deletedireccion);
        parent::cerrar();
        if(!$eliminar) return array('status' => 'fail', 'message'=> 'no se elimino el referido', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'referido eliminado', 'data' => null);
    }

    public function empresasreferUsuario(Array $data){
        if( !isset($data) || !isset($data['referido']) ) {
            return array('status' => 'fail',
                'message'=> 'faltan datos',
                'cantidad'=> null,
                'data' => null
            );
        }
        
        parent::conectar();
        $selectxempresas  = "SELECT e.* FROM empresas e WHERE e.referido = '$data[referido]'";
        $referido         = parent::consultaTodo($selectxempresas);

        $selectxusuarios  = "SELECT * FROM peer2win.usuarios WHERE referido_nasbi = '$data[referido]'";
        $referidoUsuarios = parent::consultaTodo($selectxusuarios);
        parent::cerrar();

        if(count($referido) <= 0 && count($referidoUsuarios) <= 0) {
            return array(
                'status'  => 'fail',
                'message' => 'no empresas',
                'data'    => null
            );
        }
            
        // $referido = $this->mapEmpresa($referido);
        $referido         = $this->mapUsuarios($referido, 1);
        $referidoUsuarios = $this->mapUsuarios($referidoUsuarios, 0);
        return array(
            'status'         => 'success',
            'message'        => 'empresas',
            'data'           => $referido,
            'dataNoEmpresa'  => $referidoUsuarios
        );
        // }
    }

    public function historialVentasReferUsuario(Array $data){

        if(!isset($data) || !isset($data['referido'])  || !isset($data['fecha_inicio'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;
        
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta         = $pagina * $numpagina;
        $desde         = ($hasta - $numpagina) + 1;

        $fecha         = $data['fecha_inicio'];
        $micro_date    = intval($data['fecha_inicio'] / 1000);
        $month_end     = explode(" ",$micro_date)[0];
        $undia         = 86400000;
        $unsegundo     = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end) * 1000) + ($undia - $unsegundo);

        parent::conectar();
        $selectxempresas = 
        "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT 
                    pt.*,
                    IF( pt.tipo = 1,
                        (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                        pt.precio_comprador
                    ) AS 'TOTAL_VENTA',
                    p.titulo,
                    p.foto_portada,
                    pte.descripcion AS descripcion_estado,
                    ptt.descripcion AS descripcion_tipo
                FROM productos_transaccion pt
                INNER JOIN productos p ON pt.id_producto = p.id
                LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                JOIN (SELECT @row_number := 0) r
                WHERE pt.refer = '$data[referido]' AND pt.estado = 13 AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
                ORDER BY pt.fecha_actualizacion DESC
                )as datos 
            ORDER BY fecha_actualizacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";


        // var_dump( $selectxempresas );

        
        $historial = parent::consultaTodo($selectxempresas);

        if(count($historial) <= 0){
            parent::cerrar();
            return array(
                'status'          => 'fail',
                'message'         => 'no se encontraron ventas',
                'pagina'          => $pagina,
                'total_paginas'   => 0,
                'productos'       => 0,
                'total_productos' => 0,
                'data'            => null,
                
                'fecha'           => $fecha,
                'fechaunmesmas'   => $fechaunmesmas,
                'desde'           => $desde,
                'hasta'           => $hasta,
                'referido'        => $data['referido']
            );
        }

        $selecttodos = 
        "SELECT
            COUNT(pt.id) AS contar
        FROM productos_transaccion pt
        WHERE pt.refer = '$data[referido]'   AND pt.estado = 13 
        AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'";

        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);

        $historial = $this->mapHistorialRefer($historial);

        return array(
            'status'           =>  'success',
            'message'          =>  'mis ventas',
            'pagina'           =>  $pagina,
            'total_paginas'    =>  $totalpaginas,
            'productos'        =>  count($historial),
            'total_productos'  =>  $todoslosproductos,
            'data'             =>  $historial
        );
    }

    public function resumenMensualReferVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        $selectxchartxprodxmensual = 
        "SELECT COUNT(pt.id_producto) AS cantidades_vendidas, p.titulo, pt.fecha_actualizacion
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
        GROUP BY pt.id_producto
        ORDER BY cantidades_vendidas DESC
        LIMIT 5;";
        $chartprodmensual = parent::consultaTodo($selectxchartxprodxmensual);
        parent::cerrar();

        if(count($chartprodmensual) <= 0) return array('status' => 'fail', 'message'=> 'no ventas', 'data' => null);

        $chartprodmensual = $this->mapResumenVentas($chartprodmensual);
        return array('status' => 'success', 'message'=> 'ventas', 'data' => $chartprodmensual);
    }

    public function ingresosMensuaReferlVentas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio']) || !isset($data['hace_meses'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);

        $selectxmesxactual = 
        "SELECT
            pt.*,
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                pt.precio_comprador
            ) AS 'TOTAL_VENTA',
            p.titulo,
            p.foto_portada,
            pte.descripcion AS descripcion_estado,
            ptt.descripcion AS descripcion_tipo
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
        ORDER BY pt.fecha_actualizacion ASC;";
        $mesactual = parent::consultaTodo($selectxmesxactual);

        if($data['hace_meses'] == 0) $data['hace_meses'] = 1;

        $fecha = (strtotime('first day of -'.$data['hace_meses'].' month', $month_end)*1000);
        $fechaunmesmas = (strtotime('last day of -'.$data['hace_meses'].' month', $month_end)*1000)+($undia-$unsegundo);

        $selectxmesxanterior = 
        "SELECT
            pt.*,
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                pt.precio_comprador
            ) AS 'TOTAL_VENTA',
            p.titulo,
            p.foto_portada,
            pte.descripcion AS descripcion_estado,
            ptt.descripcion AS descripcion_tipo
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'
        ORDER BY pt.fecha_actualizacion ASC;";
        $mesanterior = parent::consultaTodo($selectxmesxanterior);
        parent::cerrar();


        $request = array(
            'mesactual'   => $this->mapHistorialRefer($mesactual),
            'mesanterior' => $this->mapHistorialRefer($mesanterior)
        );
        return array(
            'status' => 'success',
            'message'=> 'ventas',
            'data' => $request
        );
    }

    public function ingresosMensualVentasPaginado(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['fecha_inicio'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $order = "ORDER BY fecha_actualizacion DESC";
        if(isset($data['order_precio'])) $order = "ORDER BY comision $data[order_precio]";

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        
        $fecha  = $data['fecha_inicio'];
        $micro_date = intval($data['fecha_inicio'] / 1000);
        $month_end = explode(" ",$micro_date)[0];
        $undia = 86400000;
        $unsegundo = 1000;
        $fechaunmesmas = (strtotime('last day of this month', $month_end)*1000)+($undia-$unsegundo);
        
        parent::conectar();
        $selectxmesxactual = 
        "SELECT * FROM (
            SELECT
                *,
                (@row_number:=@row_number+1) AS num FROM(
                    SELECT
                        IF( pt.tipo = 1,
                            ((pt.precio + pt.precio_segundo_pago + pt.precio_cupon) * pt.refer_porcentaje_comision) * pt.refer_porcentaje_comision_plan,
                            (pt.precio_comprador * pt.refer_porcentaje_comision) * pt.refer_porcentaje_comision_plan
                            
                        ) AS 'comision',
                        IF( pt.tipo = 1,
                            (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                            pt.precio_comprador
                        ) AS 'TOTAL_VENTA',
                        pt.*,
                        p.titulo,
                        p.foto_portada,
                        pte.descripcion AS descripcion_estado,
                        ptt.descripcion AS descripcion_tipo,
                        (
                            SELECT
                                SUM(hp.visitas)
                            FROM hitstorial_productos hp WHERE hp.id_producto = pt.id_producto
                        ) AS visitas,
                        (
                            SELECT
                                SUM(pt.cantidad)
                            FROM buyinbig.productos_transaccion pt WHERE pt.id_producto = p.id AND pt.estado > 10
                        ) AS cantidad_vendidas
                FROM productos_transaccion pt
                INNER JOIN productos p ON pt.id_producto = p.id
                LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
                LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
                JOIN (SELECT @row_number := 0) r
                WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas' AND pt.refer_porcentaje_comision_plan > 0
                $order
                )as datos 
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        /* echo $selectxmesxactual; */
        $mesactual = parent::consultaTodo($selectxmesxactual);
        if(count($mesactual) <= 0) {
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron ventas', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = 
        "SELECT COUNT(pt.id) AS contar
        FROM productos_transaccion pt
        INNER JOIN productos p ON pt.id_producto = p.id
        LEFT JOIN productos_transaccion_estado pte ON pt.estado = pte.id
        LEFT JOIN productos_transaccion_tipo ptt ON pt.tipo = ptt.id
        WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado = 13 AND pt.refer_porcentaje_comision > 0 AND pt.fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas';";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        $mesactual = $this->mapHistorialRefer($mesactual, false);
        return array('status' => 'success', 'message'=> 'mis ventas', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($mesactual), 'total_productos' => $todoslosproductos, 'data' => $mesactual, 'sql' => $selectxmesxactual);
    }

    public function pagarReferidoEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['moneda']) || !isset($data['address']) ) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        //Aqui va el send por lo pronto actualizo de forma manual, no se cobra comision ni nada
        
        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );
        unset($nodo);
        $precio = $this->truncNumber(($this->valorreferidousd / $precios[$data['moneda']]), 6);
        $fecha = intval(microtime(true)*1000);
        
        $nasbifunciones = new Nasbifunciones();
        $send = $nasbifunciones->enviarDinero([
            'moneda' => $data['moneda'],
            'uid_envia' => $data['uid'],
            'empresa_envia' => $data['empresa'],
            'addres_envia' => $data['address'],
            'uid_recibe' => $this->direccionrecibe[$data['moneda']]['uid'],
            'empresa_recibe' => $this->direccionrecibe[$data['moneda']]['empresa'],
            'addres_recibe' => $this->direccionrecibe[$data['moneda']]['address'],
            'monto' => $precio,
            'tipo' => 6,
            'id_transaccion' => $data['uid'].'_'.$data['empresa'],
            'fecha' => $fecha
        ]);
        unset($nasbifunciones);
        if($send['status'] != 'success') return $send;
    }

    public function misComisionesTotalesReferido (Array $data) 
    {
        if(!isset($data) || !isset($data['uid']) ) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => $data);

        $selectxmisxcomisionesxempresa = 
        "SELECT e.*, (
            SELECT SUM( pt.precio ) 
            FROM buyinbig.productos_transaccion pt 
            WHERE pt.estado_pago_transaccion = 0 AND pt.estado = 13 AND pt.refer = '$data[uid]' AND pt.uid_vendedor = e.id
        ) AS acumulado_usd FROM empresas e WHERE e.referido = '$data[uid]';";


        parent::conectar();
        $selectmiscomisionesempresa = parent::consultaTodo($selectxmisxcomisionesxempresa);
        parent::cerrar();
        
        return $selectmiscomisionesempresa;

        if(count( $selectmiscomisionesempresa ) <= 0) return array('status' => 'fail', 'message'=> 'No tienes empresas recomendadas por mi en nasbi.com pendientes por pago.', 'data' => null);

        $empresas = $this->mapEmpresa($selectmiscomisionesempresa);

        return array(
            'status' => 'success',
            'message'=> 'Empresas recomendadas por mi en nasbi.com pendientes por pago',
            'data' => $empresas
        );
    }

    function actualizarReferido(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        parent::conectar();
        $updatexrefer = "UPDATE referidos_code SET estado = '$data[estado]', fecha_actualizacion = '$data[fecha]' WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
        $referido = parent::query($updatexrefer);
        parent::cerrar();
        if(!$referido) return array('status' => 'fail', 'message'=> $data['message_fail'], 'data' => null);


        $schemaDataUser = $this->datosUser( $data )['data'];

        // Envio de correos de BIENVENIDA USUARIOS NASBI : REFERIRI NEGOCIO
        $htmlEmail = $this->getWelcomeEmailReferEmpresa( $schemaDataUser );
        $this->sendEmail($schemaDataUser, $htmlEmail);
        
        return array('status' => 'success', 'message'=> $data['message_success'], 'data' => null);
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

    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        $monedas_local['costo_dolar'] = 1;
        $monedas_local['code'] = 'USD';
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                }
            }
        }
        $select_precio = $monedas_local;
        return $select_precio;
    }

    function mapeoAddReferido(Array $data)
    {
        $data['uid'] = floatval(addslashes($data['uid']));
        $data['empresa'] = floatval(addslashes($data['empresa']));
        return $data;
    }

    function mapReferido(Array $referido)
    {
        foreach ($referido as $x => $ref) {
            $ref['id'] = floatval($ref['id']);
            
            $ref['keyRefer'] = md5( floatval($ref['id']) . floatval($ref['empresa']) );

            $ref['uid'] = floatval($ref['uid']);
            $ref['empresa'] = floatval($ref['empresa']);
            $ref['estado'] = floatval($ref['estado']);
            $ref['tipo'] = floatval($ref['tipo']);
            $ref['fecha_creacion'] = $ref['fecha_creacion'];
            $ref['fecha_actualizacion'] = $ref['fecha_actualizacion'];

            $referido[$x] = $ref;
        }

        return $referido;
    }

    function mapEmpresa(Array $empresas)
    {
        foreach ($empresas as $x => $empresa) {
            $empresa['uid'] = floatval($empresa['id']);
            $empresa['id'] = floatval($empresa['id']);
            $empresa['empresa'] = 1;
            $empresa['pais'] = floatval($empresa['pais']);
            $empresa['nit'] = floatval($empresa['nit']);
            $empresa['nombre_empresa'] = $empresa['nombre_empresa'];
            $empresa['razon_social'] = $empresa['razon_social'];
            $empresa['tipo_documento_dueno'] = floatval($empresa['tipo_documento_dueno']);
            $empresa['estado'] = floatval($empresa['estado']);
            $empresa['fecha_creacion'] = floatval($empresa['fecha_creacion']);
            $empresa['fecha_actualizacion'] = floatval($empresa['fecha_actualizacion']);
            unset($empresa['clave']);

            if ( !isset( $empresa['acumulado_usd'] ) ) {
                $empresa['acumulado_usd'] = 0;
            }else{
                if ( $empresa['acumulado_usd'] != null ) {
                    $empresa['acumulado_usd'] = floatval($empresa['acumulado_usd']);
                }else{
                    $empresa['acumulado_usd'] = 0;
                }
            }

            $empresas[$x] = $empresa;
        }

        return $empresas;
    }

    function mapUsuarios(Array $usuarios, Int $empresa)
    {
        $datauid = null;
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datarazonsocial = "";
        $datapais = null;
        $datanit = null;
        $datatipo_documento_dueno = null;
        $dataestado = null;
        $datafecha_creacion = null;
        $datafecha_actualizacion = null;
        $dataacumulado_usd = 0;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datauid         = $user['id'];
                $datanombre      = strtoupper($user['nombreCompleto']);
                $datarazonsocial = strtoupper($datanombre);
                $dataempresa     = $user['email'];//"Nasbi";
                $datacorreo      = $user['email'];
                $datatelefono    = $user['telefono'];
                $datapais        = $user['paisid'];

                $datafecha_creacion        = $user['fecha_ingreso'];
                $datafecha_actualizacion   = $user['fecha_ingreso'];

                $dataestado = floatval($user['status']);
            }else if($empresa == 1){

                $datanombre                = strtoupper($user['nombre_empresa']);
                $datauid                   = $user['id'];
                $dataempresa               = $user['correo'];
                $datarazonsocial           = strtoupper($user['razon_social']);
                $datacorreo                = $user['correo'];
                $datatelefono              = $user['telefono'];
                $datapais                  = $user['pais'];
                $datanit                   = $user['nit'];
                $datatipo_documento_dueno  = floatval($user['tipo_documento_dueno']);
                $dataestado                = floatval($user['estado']);

                $datafecha_creacion        = floatval($user['fecha_creacion']);
                $datafecha_actualizacion   = floatval($user['fecha_actualizacion']);

                if ( !isset( $user['acumulado_usd'] ) ) {
                    $dataacumulado_usd     = 0;
                }else{
                    if ( $user['acumulado_usd'] != null ) {
                        $dataacumulado_usd = floatval($user['acumulado_usd']);
                    }else{
                        $dataacumulado_usd = 0;
                    }
                }
            }

            unset($user);

            if( $datanombre == "..." ){
                $datanombre = "";
            }
            if( $datanombre == "..." ){
                $datanombre = "";
            }
            if( $datacorreo == "..." ){
                $datacorreo = "";
            }
            if( $datatelefono == "..." ){
                $datatelefono = "";
            }
            if( $datarazonsocial == "..." ){
                $datarazonsocial = "";
            }
            if( $datanit == "..." ){
                $datanit = "";
            }
            if( $datapais == "..." ){
                $datapais = "";
            }
            if( $datatipo_documento_dueno == "..." ){
                $datatipo_documento_dueno = "";
            }

            $user['uid'] = $datauid;
            $user['id'] = $datauid;
            $user['nombre'] = $datanombre;
            $user['nombre_empresa'] = $datanombre;
            $user['empresa'] = $empresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['razon_social'] = $datarazonsocial;
            $user['nit'] = $datanit;
            $user['pais'] = $datapais;
            $user['tipo_documento_dueno'] = $datatipo_documento_dueno;
            $user['estado'] = $dataestado;

            $user['fecha_creacion'] = $datafecha_creacion;
            $user['fecha_actualizacion'] = $datafecha_actualizacion;

            $user['acumulado_usd'] = $dataacumulado_usd;

            $usuarios[$x] = $user;
        }

        return $usuarios;
    }

    function mapHistorialRefer(Array $historial)
    {
        
        foreach ($historial as $x => $hist) {
            // return $hist;
            $hist['envio'] = null;
            $hist['id'] = floatval($hist['id']);
            $hist['id_carrito'] = floatval($hist['id_carrito']);
            $hist['id_producto'] = floatval($hist['id_producto']);
            $hist['uid_vendedor'] = floatval($hist['uid_vendedor']);
            $hist['uid_comprador'] = floatval($hist['uid_comprador']);
            $hist['cantidad'] = floatval($hist['cantidad']);
            $hist['boolcripto'] = floatval($hist['boolcripto']);

            if ( !isset( $hist['refer_porcentaje_comision'] )) {
                $hist['refer_porcentaje_comision']      = 0;
            }
            if ( !isset( $hist['refer_porcentaje_comision_plan'] )) {
                $hist['refer_porcentaje_comision_plan'] = 0;
            }

            $hist['refer_porcentaje_comision']      = floatval($hist['refer_porcentaje_comision']);
            $hist['refer_porcentaje_comision_plan'] = floatval($hist['refer_porcentaje_comision_plan']);

            $hist['precio_original'] = floatval($hist['precio']);

            if( isset($hist['TOTAL_VENTA']) ){
                $hist['precio'] = floatval( $hist['TOTAL_VENTA'] );
            }else{
                $hist['precio']          = floatval($hist['precio']) + floatval($hist['precio_segundo_pago']);
            }


            // Quitar IVA
            $hist['porcentaje_tax_pt'] = floatval( $hist['porcentaje_tax_pt'] );
            if( $hist['porcentaje_tax_pt'] > 0 ){
                //Obtenemos el precio SIN IVA.
                $hist['precio'] = $hist['precio'] / (1 + ($hist['porcentaje_tax_pt'] / 100));
            }

            if ( $hist['refer_porcentaje_comision'] > 0) {

                $comision_nasbi_sin_iva = $hist['precio'] * $hist['refer_porcentaje_comision'];

                $hist['precio']           = $comision_nasbi_sin_iva * $hist['refer_porcentaje_comision_plan'];
                
                $hist['precio_usd']       = $hist['precio'];
                $hist['precio_refer_usd'] = $hist['precio'];
            }else{
                $hist['precio']           = 0;
                $hist['precio_usd']       = 0;
                $hist['precio_refer_usd'] = 0;
            }

            if($hist['boolcripto'] == 0)$hist['precio_mask'] = $this->maskNumber($hist['precio'], 2);
            if($hist['boolcripto'] == 1)$hist['precio_mask'] = $this->maskNumber($hist['precio'], 2);

            $hist['precio_usd'] = floatval($hist['precio_usd']);

            $hist['precio_usd_mask'] = $this->maskNumber($hist['precio_usd'], 2);
            if (isset( $hist['comision'] )) {
                $hist['comision_mask'] = $this->maskNumber($hist['comision'], 2);
            }

            $hist['precio_refer'] = floatval($hist['precio']);
            // $hist['precio_refer'] = floatval($hist['precio']*$this->porcentajegananciarefer);
            if($hist['boolcripto'] == 0)$hist['precio_refer_mask'] = $this->maskNumber($hist['precio_refer'], 2);
            if($hist['boolcripto'] == 1)$hist['precio_refer_mask'] = $this->maskNumber($hist['precio_refer'], 2);

            // $hist['precio_refer_usd'] = floatval($hist['precio_usd']*$this->porcentajegananciarefer);
            $hist['precio_refer_usd_mask'] = $this->maskNumber($hist['precio_refer_usd'], 2);

            $hist['precio_moneda_actual_usd'] = floatval($hist['precio_moneda_actual_usd']);
            $hist['fecha_creacion'] = floatval($hist['fecha_creacion']);
            $hist['fecha_actualizacion'] = floatval($hist['fecha_actualizacion']);
            $hist['estado'] = floatval($hist['estado']);
            $hist['contador'] = floatval($hist['contador']);
            $hist['id_metodo_pago'] = floatval($hist['id_metodo_pago']);
            $hist['tipo'] = floatval($hist['tipo']);
            $hist['empresa'] = floatval($hist['empresa']);
            $hist['empresa_comprador'] = floatval($hist['empresa_comprador']);
            if(isset($hist['num'])) $hist['num'] = floatval($hist['num']);

            
            $resultDatosUser = $this->datosUser([
                'uid'=> $hist['uid_vendedor'],
                'empresa' => intval( $hist['empresa'] )
            ]);
            $hist['datos_usuario_vendedor'] = $resultDatosUser['data'];
            if ( intval( $hist['empresa'] ) == 1 ) {
                $hist['razon_social'] = strtoupper($hist['datos_usuario_vendedor']['nombre']);
            }else{
                $hist['razon_social'] = strtoupper($hist['datos_usuario_vendedor']['nombre']);
            }

            $fecha = intval(microtime(true)*1000);
            $array = [
                'uid'         => $hist['uid_vendedor'],
                'empresa'     => $hist['empresa'],
                'id_producto' => $hist['id_producto'],
                'filename'    => $fecha.'.csv'
            ];
            $hist['url_csv'] = bin2hex(json_encode($array));

            $historial[$x] = $hist;
        }

        return $historial;
    }

    function mapResumenVentas(Array $resumen_ventas)
    {
        foreach ($resumen_ventas as $x => $resumen) {

            $resumen['cantidades_vendidas'] = floatval($resumen['cantidades_vendidas']);
            $resumen['titulo'] = stripslashes($resumen['titulo']);
            $resumen['fecha_actualizacion'] = floatval($resumen['fecha_actualizacion']);
            $resumen_ventas[$x] = $resumen;
        }
        return $resumen_ventas;
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

    function getWelcomeEmailReferEmpresa( Array $data ){
        $code_md5 = md5( $data['uid'] . $data['empresa'] );

        $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>¡Gracias por unirte a Nasbi!</title>
            </head>
            <body style="background-color: #e0ddd6;">
                <table cellpadding="0" cellspacing="0" width="570" align="center" bgcolor="#e0ddd6" border="0">
                    <tbody>
                        <tr>
                            <td width="15"></td>
                            <td>
                                <table cellpadding="0" cellspacing="0" width="570" align="center" border="0" bgcolor="#ffffff" style="border-top-left-radius: 4px; border-top-right-radius: 4px;">
                                    <tbody>
                                        <tr>
                                            <td align="center" valign="middle" style="padding: 30px 0px 20px 0px;">
                                                <a
                                                    href="https://nasbi.com/content/"
                                                    target="_blank"
                                                    data-saferedirecturl="https://nasbi.com/content/"
                                                >
                                                    <img src="https://nasbi.com/imagen/Logo.png" width="160" border="0" alt="Nasbi.com" class="CToWUd" style="display: block; font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #333333;"
                                                    />
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table cellpadding="0" cellspacing="0" width="570" align="center" bgcolor="#ffffff" border="0">
                                    <tbody>
                                        <tr>
                                            <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Te damos la bienvenida!</td>
                                        </tr>
                                        <tr>
                                            <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                        </tr>
                                        <tr>
                                            <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                ¡Gracias por suscribirte a Nasbi! Has completado tu registro y ya puedes comenzar
                                                <a
                                                    href="https://nasbi.com/content/referir-negocio.php?key='. $code_md5 .'"
                                                    style="color: #ea4262 !important;"
                                                    target="_blank"
                                                    data-saferedirecturl="https://nasbi.com/content/referir-negocio.php?key='. $code_md5 .'"
                                                >
                                                    a referir negocios.
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                class="m_-6866340598297024620account-info-subheadline"
                                                style="font-family: Helvetica, Arial, sans-serif; color: #3474fc; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                            >
                                                <b>Información de tu cuenta:</b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                class="m_-6866340598297024620account-info-row"
                                                style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                            >
                                                Tu email de inicio de sesión:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                class="m_-6866340598297024620account-info-row"
                                                style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                            >
                                                Proveedor del servicio:&nbsp;&#8234;Nasbi.com.&#8236;
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                class="m_-6866340598297024620account-info-row"
                                                style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                            >
                                                Tu oferta:&nbsp;Quiero referir negocios
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                class="m_-6866340598297024620account-info-row"
                                                style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                            >
                                                Precio:&nbsp;$39 USD
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                <b>
                                                    Referir negocio
                                                    <a
                                                        href="https://nasbi.com/content/referir-negocio.php?key='. $code_md5 .'"
                                                        style="color: #ea4262 !important;"
                                                        target="_blank"
                                                        data-saferedirecturl="https://nasbi.com/content/referir-negocio.php?key='. $code_md5 .'"
                                                    >
                                                        Solo con $39 USD
                                                    </a>
                                                    Puedes aumentar tus ganancias
                                                </b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">¡Que lo disfrutes!</td>
                                        </tr>
                                        <tr>
                                            <td style="font-family: Helvetica, Arial, sans-serif; color: #454545; font-size: 14px; font-weight: lighter; padding: 0px 34px 35px 34px; line-height: 20px;">–Tus amigos de Nasbi</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td width="15"></td>
                        </tr>
                    </tbody>
                </table>

                <table cellpadding="0" cellspacing="0" width="570" align="center" bgcolor="#e0ddd6" border="0">
                    <tbody>
                        <tr>
                            <td width="15"></td>
                            <td>
                                <table cellpadding="0" cellspacing="0" width="570" align="center" border="0">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <img
                                                    style="display: block;"
                                                    src="https://ci5.googleusercontent.com/proxy/_ONvfgZfDCPxiqpFWJQXH6zSrqRldKSvNARz9hlbIWfUjjUocWi0cyyGlC2s-g8cZkxB3DIBRHE4r0K0rEKRtj9g8jR0oRxTWav2Ow=s0-d-e1-ft#http://cdn.nflximg.com/us/email/logo/newDesign/shadow.png"
                                                    width="570"
                                                    height="25"
                                                    border="0"
                                                    alt=""
                                                    class="CToWUd"
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="font-family: Helvetica, Arial, sans-serif; padding: 0px 10px 15px 10px; font-size: 16px; color: #454545; text-decoration: none; font-weight: normal;">
                                                <a
                                                    href="https://wa.me/573163261371?text=Quiero pertenecer al ecosistema de Peers2win, ¿podrían indicarme como me registro?"
                                                    style="color: #454545; font-weight: normal; text-decoration: none;"
                                                    target="_blank"
                                                    data-saferedirecturl="https://wa.me/573163261371?text=Quiero pertenecer al ecosistema de Peers2win, ¿podrían indicarme como me registro?"
                                                >
                                                    ¿Preguntas?<span> Llama al +57 316 326 1371</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 0px 0px 0px 0px;">
                                                <img
                                                    style="display: block;"
                                                    src="https://ci4.googleusercontent.com/proxy/ulfFJYKgYF2697Y5iU8D_9IG7iFFic1YcSKJWPXL94qvbZYh9ivpZbGfHMDwK3REpzfua5dI23SauBb5LAU_kQaxv93Gl6M0-deoE94=s0-d-e1-ft#http://cdn.nflximg.com/us/email/logo/newDesign/divider.png"
                                                    width="570"
                                                    height="15"
                                                    border="0"
                                                    alt=""
                                                    class="CToWUd"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td width="15"></td>
                        </tr>
                    </tbody>
                </table>

                <table cellpadding="0" cellspacing="0" align="center" width="570">
                    <tbody>
                        <tr>
                            <td height="10"></td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 10px 20px; font-weight: lighter;">
                                Te enviamos este email como parte de tu membresía de Nasbi. Para cambiar tus preferencias de email en cualquier momento, visita la página
                                <a
                                    style="color: #666666;"
                                    href="https://nasbi.com/content/"
                                    target="_blank"
                                    data-saferedirecturl="https://nasbi.com/content/"
                                >
                                    Configuración de comunicaciones
                                </a>
                                en tu cuenta. No respondas a este email, ya que no podemos contestarte desde esta dirección. Si necesitas asistencia o deseas contactarnos, visita nuestro Centro de ayuda en
                                <a
                                    href="https://nasbi.com/content/"
                                    style="color: #666666;"
                                    target="_blank"
                                    data-saferedirecturl="https://nasbi.com/content/"
                                >
                                    help.nasbi.com
                                </a>
                                .
                            </td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 2px 20px; font-weight: lighter;"></td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 2px 20px; font-weight: lighter;">
                                Nasbi envió este mensaje a [<a href="#m_-992341955914042300_" style="text-decoration: none !important; color: footerFontColor;">'. $data['correo'] .'</a>].
                            </td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 2px 20px; font-weight: lighter;">
                                SRC:
                                <a
                                    href="https://nasbi.com/content/"
                                    style="color: #666666; text-decoration: none;"
                                    target="_blank"
                                    data-saferedirecturl="https://nasbi.com/content/"
                                >
                                    05014_es_CO
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 2px 20px; font-weight: lighter;">
                                El uso del servicio y del sitio web de Nasbi está sujeto a nuestros
                                <a
                                    style="color: #666666;"
                                    href="https://nasbi.com/content/"
                                    target="_blank"
                                    data-saferedirecturl="https://nasbi.com/content/"
                                >
                                    Términos de uso
                                </a>
                                y a nuestra
                                <a
                                    style="color: #666666;"
                                    href="https://nasbi.com/content/"
                                    target="_blank"
                                    data-saferedirecturl="https://nasbi.com/content/"
                                >
                                    Declaración de privacidad
                                </a>
                                .
                            </td>
                        </tr>
                        <tr>
                            <td style="font-family: Helvetica, Arial, sans-serif; color: #666666; font-size: 11px; padding: 0px 20px 2px 20px; font-weight: lighter;">
                                <span>
                                    <a
                                        href="https://www.nasbi.com"
                                        style="color: #666666; text-decoration: none;"
                                        target="_blank"
                                        data-saferedirecturl="https://nasbi.com/content/"
                                    ></a>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <img
                                    src="data:image/gif;base64,R0lGODlhAQABAPAAAAAAAAAAACH5BAUAAAAALAAAAAABAAEAAAICRAEAOw=="
                                    style="display: block;"
                                    border="0"
                                    class="CToWUd"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </body>
        </html>';

        return $html;
    }

    function sendEmail($dato, $htmlPlantilla) {

        $desde = "mariopalma199318@gmail.com";
        $para  = $dato['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';

        /*$titulo     = "¡Gracias por unirte a Nasbi!";*/
        $titulo     = "Referir negocios - !Solo con $39 USD Puedes aumentar tus ganancias!";

        $mensaje1   = $htmlPlantilla;

        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $cabeceras .= 'From: '. $desde . "\r\n";

        $dataArray = array("email" => $para, "titulo" => $titulo, "mensaje" => $mensaje1, "cabeceras" => $cabeceras);
        
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        $respuesta = json_decode($response, true);
        return $respuesta;
    }

    function obtener_porcentaje_ganacia_productos(Array $data){
        parent::conectar();
        $fechaCom = "";
        if (isset($data['fecha_inicio'])) {
            $fecha         = $data['fecha_inicio'];
            $micro_date    = intval($data['fecha_inicio'] / 1000);
            $month_end     = explode(" ",$micro_date)[0];
            $undia         = 86400000;
            $unsegundo     = 1000;
            $fechaunmesmas = (strtotime('last day of this month', $month_end) * 1000) + ($undia - $unsegundo);
            $fechaCom = "AND fecha_actualizacion BETWEEN '$fecha' AND '$fechaunmesmas'";
        }
        $selectxtransaccion = 
        "SELECT
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon),
                pt.precio_comprador
            ) AS 'TOTAL_VENTA',
            IF( pt.tipo = 1,
                (pt.precio + pt.precio_segundo_pago + pt.precio_cupon) * refer_porcentaje_comision * 0.01,
                pt.precio_comprador * refer_porcentaje_comision * 0.01
            ) AS 'comision',
            (
                ((precio + precio_segundo_pago) / (1 + (porcentaje_tax_pt / 100)))*refer_porcentaje_comision*0.01
            ) AS comision_old,
            pt.*
        FROM productos_transaccion pt WHERE uid_redsocial = '$data[uid_redsocial]' AND empresa_redsocial = '$data[empresa_redsocial]' AND estado = 13 $fechaCom";
        $registro_transacciones = parent::consultaTodo($selectxtransaccion);
        parent::cerrar();

        $respuestaPaginada  = [];
        $num_paginas        = 0;

        if( count($registro_transacciones) > 0 ){
            
            $respuesta = [];
            foreach($registro_transacciones as $rt){
                $id_producto = $rt['id_producto'];

                $pago_digital_calculos_pasarelas_id = $rt['pago_digital_calculos_pasarelas_id'];
                

                parent::conectar();
                $producto = parent::consultaTodo("SELECT * FROM productos WHERE id = '$id_producto'");
                parent::cerrar();

                $precio_original = floatval( $rt['precio'] );

                // Inicio New
                if ( !isset( $rt['refer_porcentaje_comision'] )) {
                    $rt['refer_porcentaje_comision']      = 0;
                }
                if ( !isset( $rt['refer_porcentaje_comision_plan'] )) {
                    $rt['refer_porcentaje_comision_plan'] = 0;
                }

                $rt['refer_porcentaje_comision']      = floatval($rt['refer_porcentaje_comision']);
                $rt['refer_porcentaje_comision_plan'] = floatval($rt['refer_porcentaje_comision_plan']);

                $rt['precio_original'] = floatval($rt['precio']) + floatval($rt['precio_segundo_pago']);
                $rt['precio']          = floatval($rt['precio']) + floatval($rt['precio_segundo_pago']);


                // Quitar IVA
                $rt['porcentaje_tax_pt'] = floatval( $rt['porcentaje_tax_pt'] );
                if( $rt['porcentaje_tax_pt'] > 0 ){
                    //Obtenemos el precio SIN IVA.
                    $rt['precio'] = $rt['precio'] / (1 + ($rt['porcentaje_tax_pt'] / 100));
                }

                if ( $rt['refer_porcentaje_comision'] > 0) {

                    $comision_nasbi_sin_iva = $rt['precio'] * $rt['refer_porcentaje_comision'];

                    $rt['precio']           = $comision_nasbi_sin_iva * 0.01;
                    
                    $rt['precio_usd']       = $rt['precio'];
                    $rt['precio_refer_usd'] = $rt['precio'];
                }else{
                    $rt['precio']           = 0;
                    $rt['precio_usd']       = 0;
                    $rt['precio_refer_usd'] = 0;
                }
                // Fin New
                $rt['comision_mask'] = $this->maskNumber($rt['comision'], 2);
                $p_gratuia = NULL;
                $p_clasica = NULL;
                $p_premium = NULL;

                
                $precio          = $rt['precio'];
                $precio_usd      = $precio;

                $comision_por_categoria_nasbi = $precio;





                if( $comision_por_categoria_nasbi > 0){
                    $ganancia     = ($comision_por_categoria_nasbi * 0.01);
                    $ganancia_usd = ($comision_por_categoria_nasbi * 0.01);

					$dataReferidoItem = array(
						"producto" 						 => $producto[0], 
                        
                        "exposicion"   			         => $rt['exposicion_pt'],
                        "porcentajeGratuita"             => 0, 
                        "porcentajeClasica"    			 => 0, 
                        "porcentajePremium"    			 => 0,

                        "precio_tax"                     => $precio_original,
                        "precio_tax_usd"                 => $precio_original,
                        "comision_por_categoria_nasbi"   => $comision_por_categoria_nasbi,

                        "ganancia_publicacion" 			 => $ganancia,
                        "ganancia_publicacion_maks"   	 => $this->maskNumber($ganancia, 2),

                        "gananacia_publicacion_usd" 	 => $ganancia_usd,
                        "gananacia_publicacion_usd_mask" => $this->maskNumber($ganancia_usd, 2),

                        "p_gratuita"                     => 0,
                        "p_clasica"                      => 0,
                        "p_premium"                      => 0,
                        "ganancia__gratuita"             => 0,
                        "ganancia_clasica"               => 0,
                        "ganancia_premium"               => 0,
                        
                        "precio_transaccion" 			 => $precio,
                        "precio_transaccion_usd" 		 => $precio_usd,

                        "precio_transaccion_mask" 		 => $this->maskNumber($precio, 2),
                        "precio_transaccion_usd_mask" 	 => $this->maskNumber($precio_usd, 2),

                        "precio_transaccion_moneda"  	 => $rt['moneda'],

                        "fecha_creacion"                 => floatval($rt['fecha_creacion']),
                        "fecha_actualizacion"            => floatval($rt['fecha_actualizacion']),
                        "transaccion" 					 => $rt
                    );
                    if($rt['exposicion_pt'] > 1) {
                    	array_push($respuesta, $dataReferidoItem);
                    }
                }
            }
            $numXpagina = 9;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            for($i = 0; $i<$hasta; $i++){
                if($i < count($respuesta)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        array_push($respuestaPaginada, $respuesta[$i]);
                    }
                }
            }
            $num_paginas = count($respuesta)/$numXpagina;
            $num_paginas = ceil($num_paginas);
        }
        return array(
        	"status"        => "success",
        	"data"          => $respuestaPaginada,
        	"pag"           => $data['pag'],
        	"total_paginas" => $num_paginas
        );
    }

    function calcularUtilidad(){
        $transaciones_u= $this->get_transacciones_con_commisiones();
        if(count($transaciones_u)>0){
            $valor_total=0; 
            $valor_comision_refer=0;
            $valor_comision_social=0;
            $valor_mixtas=0;
            $array_transacciones_refer= []; 
            $array_transacciones_social= []; 
            $array_transacciones_mixta= []; 
            $array_transacciones_general= []; 

            foreach ($transaciones_u as $key => $transaccion) {
                $accedio_refer=0; 
                $accedio_red_social=0; 
                $valor_usd_precio= floatval($transaccion["precio_usd"]);

                if(!empty($transaccion["refer"])){//este quiere decir que no esta vacio el campo de refer
                    $refer_porcentaje_comision= floatval($transaccion["refer_porcentaje_comision"]);
                    $porcentaje_de_comision_plan= floatval($transaccion["refer_porcentaje_comision_plan"]); 
                        if($refer_porcentaje_comision>0){//comision a usuario
                            $accedio_refer=1;
                            $valor_usd_precio= $this->resta_de_comision_a_valor_usd($transaccion, $valor_usd_precio, $refer_porcentaje_comision);
                        }

                        if($porcentaje_de_comision_plan>0){//comision plan
                            $accedio_refer=1;
                            $valor_usd_precio= $this->resta_de_comision_a_valor_usd($transaccion, $valor_usd_precio, $porcentaje_de_comision_plan);
                        }
                }

               
                

                if(!empty($transaccion["uid_redsocial"])){//este quiere decir que no esta vacio el campo de red_social
                    if($transaccion["empresa_redsocial"]== "0"){
                        $procentaje_de_comision_red_social= 0.01; //es 1% siempre lo que se le resta en red_social 
                        $accedio_red_social=1; 
                        $valor_usd_precio= $this->resta_de_comision_a_valor_usd($transaccion, $valor_usd_precio,$procentaje_de_comision_red_social);
                    }
                }



                if($accedio_refer==1 || $accedio_red_social==1){ //quiere decir que esta transaccion aplico alguno de estas comisiones 
                   $transaccion= $this->map_transaccion($transaccion); 


                    if($accedio_refer==1 && $accedio_red_social==1){
                        $valor_mixtas= $valor_usd_precio + $valor_mixtas;
                        $transaccion["tipo_comision"]=1;  //quiere decir que accedio a las dos comisiones
                        $transaccion["valor_utilidad_to_nasbi"]=$valor_usd_precio;  
                        array_push($array_transacciones_mixta, $transaccion); 
                    }else if($accedio_refer==1 ){
                        $valor_comision_refer= $valor_usd_precio + $valor_comision_refer;
                        $transaccion["tipo_comision"]=2;  //quiere decir que solo accedio a referido 
                        $transaccion["valor_utilidad_to_nasbi"]=$valor_usd_precio;  
                        array_push($array_transacciones_refer, $transaccion); 
                    }else if($accedio_red_social == 1 ){
                        $valor_comision_social=$valor_usd_precio + $valor_comision_social;
                        $transaccion["tipo_comision"]= 3; //quiere decir que solo accedio a social
                        $transaccion["valor_utilidad_to_nasbi"]=$valor_usd_precio;  
                        array_push($array_transacciones_social, $transaccion); 
                    }

                    $transaccion["valor_utilidad_to_nasbi"]= $valor_usd_precio; 
                    array_push($array_transacciones_general, $transaccion); 
                    
                    $valor_total=$valor_usd_precio + $valor_total; 
                    
                }
                
            }
            return array(
                'transacciones_mixtas'  => array($array_transacciones_mixta),
                'transacciones_solo_referido' => array($array_transacciones_refer), 
                'transacciones_solo_social' => array($array_transacciones_social),
                'tranascciones_generales' => array($array_transacciones_general),
                'total_utilidad_mixta_USD' => $valor_mixtas, 
                'total_utilidad_solo_referido_USD' => $valor_comision_refer, 
                'total_utilidad_solo_social_USD' => $valor_comision_social,
                'total_utilidad_general' => $valor_total,
                'status' => 'success',
                'message'=> 'Información sobre la utilidad generada',
                'data' => null
            );
        }else{
            return array(
                'status' => 'Fail',
                'message'=> 'No hay transacciones en estado 13 y diferentes a nasbiblue',
                'data' => null
            );
        }
     
    }

    public function map_transaccion(Array $transaccion){
        $data_nueva_transaccion=[]; 
        
        $data_nueva_transaccion["uid_referido"]= $transaccion["refer"]; 
        $data_nueva_transaccion["uid_redsocial"]= $transaccion["uid_redsocial"]; 
        $data_nueva_transaccion["empresa_redsocial"]= $transaccion["empresa_redsocial"]; 
        $data_nueva_transaccion["refer_porcentaje_comision"]= floatval($transaccion["refer_porcentaje_comision"]); 
        $data_nueva_transaccion["refer_porcentaje_comision_plan"]= floatval($transaccion["refer_porcentaje_comision_plan"]);
        $data_nueva_transaccion["refer_porcentaje_comision_idplan"]= $transaccion["refer_porcentaje_comision_idplan"];
        $data_nueva_transaccion["precio_usd_sin_restas"]= floatval($transaccion["precio_usd"]);
        $data_nueva_transaccion["id_transaccion"]= $transaccion["id"];
        $data_nueva_transaccion["estado_transaccion"]= $transaccion["estado"];
        $data_nueva_transaccion["moneda_transaccion"]= $transaccion["moneda"];
        

        return $data_nueva_transaccion; 

    }



    public function resta_de_comision_a_valor_usd(Array $transaccion, float $valor, Float $porcentaje){
        $valor_a_restar= $valor * $porcentaje; //no se divide /100 porque el porcentaje ya viene en menores que 1 
        $valor_to_retornar= $valor -  $valor_a_restar; 
        return $valor_to_retornar; 
        
    }


    public function get_transacciones_con_commisiones(){
        parent::conectar();
        $trassaciones = parent::consultaTodo("SELECT * FROM buyinbig.productos_transaccion WHERE estado = '13' AND moneda != 'Nasbiblue';");
        parent::cerrar();
        return $trassaciones;
    }


    public function buscarUsuarioPorEmail( Array $data ){
        if( !isset($data) || !isset($data['email']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'Campos vacios: recuerda enviar: email',
                'data' => $data
            );
        }
        parent::conectar();
        $peer2win_usuarios = parent::consultaTodo("SELECT * FROM peer2win.usuarios WHERE email = '$data[email]';");
        parent::cerrar();

        parent::conectar();
        $buyinbig_empresas = parent::consultaTodo("SELECT * FROM buyinbig.empresas WHERE correo = '$data[email]';");
        parent::cerrar();

        $quien_lo_refirio = 0;

        if ( count( $peer2win_usuarios ) == 0 ) {
            if ( count( $buyinbig_empresas ) == 0 ) {
                return null;
                
            }else{
                $buyinbig_empresas             = $buyinbig_empresas[0];
                $buyinbig_empresas['referido'] = intval( $buyinbig_empresas['referido'] );
                $quien_lo_refirio              = $buyinbig_empresas['referido'];

                return array(
                    'status'              => 'success',
                    'datos'               => $buyinbig_empresas,
                    'referido_por_id'        => $quien_lo_refirio,
                    'referido_por_data'      => ($quien_lo_refirio != 0? $this->buscarUsuarioPorId(['id' => $quien_lo_refirio]) : null),
                    'empresa'             => 1,
                    'empresa_descripcion' => "Usuario PERSONA JURIDICA"
                );
            }
        }else{
            $peer2win_usuarios                   = $peer2win_usuarios[0];
            $peer2win_usuarios['referido_nasbi'] = intval( $peer2win_usuarios['referido_nasbi'] );
            $quien_lo_refirio                    = $peer2win_usuarios['referido_nasbi'];
            return array(
                'status'              => 'success',
                'datos'               => $peer2win_usuarios,
                'referido_por_id'        => $quien_lo_refirio,
                'referido_por_data'      => $quien_lo_refirio != 0? $this->buscarUsuarioPorId(['id' => $quien_lo_refirio]) : null,
                'empresa'             => 0,
                'empresa_descripcion' => "Usuario PERSONA NATURAL"
            );
        }
        return null;
    }
    public function buscarUsuarioPorId( Array $data ){
        if( !isset($data) || !isset($data['id']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'Campos vacios: recuerda enviar: id',
                'data' => $data
            );
        }
        parent::conectar();
        $peer2win_usuarios = parent::consultaTodo("SELECT * FROM peer2win.usuarios WHERE id = '$data[id]';");
        parent::cerrar();

        parent::conectar();
        $buyinbig_empresas = parent::consultaTodo("SELECT * FROM buyinbig.empresas WHERE id = '$data[id]';");
        parent::cerrar();

        $quien_lo_refirio = 0;

        if ( count( $peer2win_usuarios ) == 0 ) {
            if ( count( $buyinbig_empresas ) == 0 ) {
                return null;
                
            }else{
                $buyinbig_empresas             = $buyinbig_empresas[0];
                $buyinbig_empresas['referido'] = intval( $buyinbig_empresas['referido'] );
                $quien_lo_refirio              = $buyinbig_empresas['referido'];

                return array(
                    'status'              => 'success',
                    'datos'               => $buyinbig_empresas,
                    'referido_por'        => $quien_lo_refirio,
                    'empresa'             => 1,
                    'empresa_descripcion' => "Usuario PERSONA JURIDICA"
                );
            }
        }else{
            $peer2win_usuarios                   = $peer2win_usuarios[0];
            $peer2win_usuarios['referido_nasbi'] = intval( $peer2win_usuarios['referido_nasbi'] );
            $quien_lo_refirio                    = $peer2win_usuarios['referido_nasbi'];
            return array(
                'status'              => 'success',
                'datos'               => $peer2win_usuarios,
                'referido_por'        => $quien_lo_refirio,
                'empresa'             => 0,
                'empresa_descripcion' => "Usuario PERSONA NATURAL"
            );
        }
        return null;
    }
    public function actualizarReferidoDev( Array $data ){
        // Algoritmo para que andres le cambie el codigo de referido a un usuario de nasbi.
        if(!isset($data) || !isset($data['empresa']) || !isset($data['referido']) || !isset($data['id'])) {
            return array('status' => 'fail',
                'message'=> 'Campos vacios: recuerda enviar: id,
                referido Y empresa',
                'data' => $data
            );
        }
        $update = null;
        if (intval($data['empresa']) == 0 ) {
            parent::conectar();
            $update = parent::query("UPDATE peer2win.usuarios SET referido_nasbi = $data[referido] WHERE id = $data[id]");
            parent::cerrar();
        }else{
            parent::conectar();
            $update = parent::query("UPDATE buyinbig.empresas SET referido = $data[referido] WHERE id = $data[id]");
            parent::cerrar();

        }
        if(!$update) return array('status' => 'fail', 'no se realizo la modificación', 'data' => $data);

        return array('status' => 'success', 'Información actualizada');

    }
}
?>