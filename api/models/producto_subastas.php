<?php
require 'nasbifunciones.php';
class ProductoSubastas extends Conexion
{

    private $tiempomaxsubasta = 86400000; // 24 horas
    private $entradaxxubastasxnormales = 0.029; // porcentaje entrada subastas normales
    private $porcentajexrestarxnormal = 0.1; // porcentaje entrada subastas normales
    private $addressxrecibextickets = [
        'uid' => 1333,
        'empresa' => 0,
        'address_Nasbigold' => 'aa97273806290a2ef074a95ef71d1a7b', 
        'address_Nasbiblue' => 'c5551f7d44f2d72f98f5976efe96bfdf',
    ];

    public function eliminarInscripcionesSubasta($id_subasta) {
        
        parent::conectar();

        $eliminarSQL = "DELETE FROM buyinbig.productos_subastas_inscritos WHERE id_subasta = '$id_subasta'";
        parent::query($eliminarSQL);
        
        $actualizarSQL = "UPDATE  productos_subastas
                          SET     inscritos = 0
                          WHERE   id = '$id_subasta'";
        parent::query($actualizarSQL);
        parent::cerrar();
    }

    public function regresarTickets($usuarios) {
        foreach($usuarios as $u){
            $dataArray = array( 
                "data" => 
                array(  "uid"         => intval($u['uid']), 
                        "empresa"     => intval($u['empresa']),
                        "tipo"        => intval($u['tipo_ticket']),
                        "cantidad"    => intval($u['cantidad_ticket']),
                        "uso"         => 2,
                        "transferido" => null
                    )
            );
            
            parent::remoteRequest(
                "http://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w",
                $dataArray
            );
        }
    }

    public function regresarSaldo( $usuarios, $precio_producto_usd, $moneda ) {
        $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        $array_monedas_local   = $this->filter_by_value($array_monedas_locales, 'iso_code_2', 'CO');
        $array_monedas_local   = $array_monedas_local[0];
        $costo_dolar           = floatval($array_monedas_local['costo_dolar']);

        parent::addLogSubastas("PRECIO PRODUCTO USD ======>". $precio_producto_usd);
        parent::addLogSubastas("COSTO DOLAR ======>". $costo_dolar);

        $costo_producto        = ceil($costo_dolar * $precio_producto_usd);
        parent::addLogSubastas("PRECIO PRODUCTO ======>". $costo_producto);
        $costo_entrada         = ceil($costo_producto * 0.029);
        parent::addLogSubastas("COSTO ENTRADA ======>". $costo_entrada);

        foreach($usuarios as $u){
            $monto = intval($u['cantidad_ticket']) * $costo_entrada;
            parent::addLogSubastas("CANTIDAD TICKETES ======>". $u['cantidad_ticket']);
            parent::addLogSubastas("MONTO ======>". $monto);
            $dataArray = array( 
                "data" => 
                array(  "uid"         => intval($u['uid']), 
                        "empresa"     => intval($u['empresa']),
                        "monto"       => round($monto),
                        "moneda"      => $moneda,
                        "tipo"        => 2
                    )
            );
            
            parent::remoteRequest(
                "http://nasbi.peers2win.com/api/controllers/nasbicoin/?recibir_dinero",
                $dataArray
            );
        }
    }

    public function obtenerTiempoTranscurrido( $fecha_creacion, $fecha_actual ) {

        $diferencia_fechas = abs( ($fecha_actual / 1000) - ($fecha_creacion / 1000) );

        $anios = floor($diferencia_fechas / (365 * 60 * 60 * 24));

        $meses = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));

        $dias  = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        $horas = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24) / (60 * 60));

        $minutos  = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24 - $horas * 60 * 60) / (60) );

        $segundos = floor(($diferencia_fechas - $anios * 365 * 60 * 60 * 24 - $meses * 30 * 60 * 60 * 24 - $dias * 60 * 60 * 24 - $horas * 60 * 60 - $minutos * 60) );

        parent::addLogSubastas("TIEMPO TRANSCURRIDO ENTRE FECHA DE CREACION Y FECHA ACTUAL ====> ".$anios." year,  ".$meses." months ".$dias." days ".$horas." hours ".$minutos." minutes ".$segundos." seconds");

        return array( "anios"    => $anios,
                      "meses"    => $meses,
                      "dias"     => $dias,
                      "horas"    => $horas,
                      "minutos"  => $minutos,
                      "segundos" => $segundos );
    }

    public function regresarEntradas( $id_subasta ) {

        $SQL = "SELECT psi.uid, psi.empresa, psi.cantidad_ticket, psi.ticket AS tipo_ticket
                FROM buyinbig.productos_subastas_inscritos AS psi WHERE id_subasta = '$id_subasta'";

        parent::conectar();
        $usuarios = parent::consultaTodo($SQL);
        parent::cerrar();

        if(count($usuarios) > 0) {
            parent::addLog("TIENE USUARIOS");

            $es_estandar = intval($usuarios[0]['tipo_ticket']) == 6 ? true: false;

            if(!$es_estandar) {
                $this->regresarTickets($usuarios);
            }else{
                $SQL = "SELECT precio_usd, moneda FROM buyinbig.productos_subastas WHERE id = '$id_subasta'";

                parent::conectar();
                $subasta    = parent::consultaTodo($SQL);
                $precio_usd = floatval($subasta[0]['precio_usd']);
                $moneda     = $subasta[0]['moneda'];
                parent::cerrar();

                $this->regresarSaldo($usuarios, $precio_usd, $moneda);
            }

            $this->eliminarInscripcionesSubasta($id_subasta);

        }else{
            parent::addLog("NO TIENE USUARIOS");
        }

    }

    public function activarSubasta( $id_subasta ) {

        parent::conectar();
        $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id = '$id_subasta'");
        parent::cerrar();
        
        if( count($subasta) > 0 ) {

            $subasta = current( $subasta );

            if( $subasta['inscritos'] >= $subasta['apostadores'] ) {

                $inscritos_activacion = $subasta['inscritos'];
                parent::conectar();
                $updated = parent::query("UPDATE productos_subastas SET
                                          estado   = 2,
                                          inscritos_activacion_subasta = '$inscritos_activacion'
                                          WHERE id = '$id_subasta'");
                parent::cerrar();

            }

        }

    }

    public function calcularNuevoTiempoInicio( $id_subasta, $dias , $fecha_actual) {

        $nueva_fecha_inicio = $fecha_actual + ($this->tiempomaxsubasta * $dias );

        $SQL = "UPDATE buyinbig.productos_subastas SET
                    fecha_creacion      = '$fecha_actual',
                    fecha_fin           = '$fecha_actual',
                    fecha_actualizacion = '$fecha_actual',
                    fecha_inicio        = '$nueva_fecha_inicio'
                    WHERE id            = '$id_subasta'";

        parent::conectar();
        parent::query($SQL);
        parent::cerrar();
    }

    public function verificarTiempoSubastas() {

        /* parent::conectar();
        // $usuarios = parent::query("DELETE FROM nasbicoin_bloqueado_diferido WHERE id_transaccion = 339 AND uid= 1378");
        
        parent::query("UPDATE productos SET estado = 0 WHERE id = 8026");

        parent::cerrar();

        return "estado cambiado"; */

        // return "verificar las subastas";

        parent::conectar();

        $SQL_subastas = "SELECT p.titulo, ps.id AS id_subasta, ps.tipo, ps.estado, ps.inscritos, ps.apostadores, ps.fecha_creacion
                         FROM productos AS p
                         JOIN productos_subastas AS ps
                         ON p.id = ps.id_producto                         
                         WHERE p.estado = 1 AND ps.estado = 1 AND ps.id = 389;";

        $subastas = parent::consultaTodo( $SQL_subastas );
        parent::cerrar();

        if( count($subastas) > 0 ) {//Ejecutar acciones
            $fecha_actual = intval(microtime(true) * 1000);

            foreach ($subastas as $sbta) {

                $tiempo = $this->obtenerTiempoTranscurrido( intval( $sbta['fecha_creacion'] ), $fecha_actual );

                $dias = 1;
                if( $sbta['tipo'] != 6 ) {//24Hrs
                    $dias = 2;//48Hrs
                }

                if( $tiempo['dias'] >= $dias ) {//Ya ha pasado el tiempo límite

                    if( $sbta['inscritos'] >= $sbta['apostadores'] ) {//Activar Subasta
                        parent::addLogSubastas("ACTIVADA");

                        $this->activarSubasta( intval($sbta['id_subasta']) );

                    }else{
                        parent::addLogSubastas("ENTRADAS REGRESADAS");
                        $this->regresarEntradas( intval($sbta['id_subasta']) );//Regresar entradas
                        $this->calcularNuevoTiempoInicio( intval($sbta['id_subasta']), $dias , $fecha_actual);
                    }
                }else{
                    parent::addLogSubastas("NO APLICA EL TIEMPO LIMITE");
                }

                /* if( $tiempo['minutos'] >= 3 ) {//Ya ha pasado el tiempo límite

                    if( $sbta['inscritos'] >= $sbta['apostadores'] ) {//Activar Subasta
                        parent::addLogSubastas("ACTIVADA");

                        $this->activarSubasta( intval($sbta['id_subasta']) );

                    }else{
                        parent::addLogSubastas("ENTRADAS REGRESADAS");
                        $this->regresarEntradas( intval($sbta['id_subasta']) );//Regresar entradas
                        $this->calcularNuevoTiempoInicio( intval($sbta['id_subasta']), $dias , $fecha_actual);
                    }
                }else{
                    parent::addLogSubastas("NO APLICA EL TIEMPO LIMITE");
                } */

            }
        }

        return "exito";
    }

    public function home(Array $data)
    {
        if(!isset($data['pais']) && !isset($data['departamento'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $select_precio = $this->selectMonedaLocalUser($data);

        $pagina = floatval($data['pagina']);
        $numpagina = 10;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $where = " p.estado != 0 AND p.estado != 2 AND p.estado < 4 ";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null;
        $subselect = "";
        if($data['tipo'] == 1){ // Nasbi normales
            $where .= " AND (ps.estado = 1 OR ps.estado = 2) AND ps.tipo = 6";
            $order .= ", exposicion DESC";
        }
        if($data['tipo'] == 2){ // Nasbi premium
            $where .= " AND (ps.estado = 1 OR ps.estado = 2 OR ps.estado = 3) AND ps.tipo <> 6";
            $order .= ", exposicion DESC";
        }
        if(isset($data['exposicion']) && !empty($data['exposicion'])){ // destacado
            $data['exposicion'] = addslashes($data['exposicion']);
            $where .= " AND exposicion = '$data[exposicion]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['garantia']) && $data['garantia'] != ''){ // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && isset($data['departamento']) && !empty($data['departamento'])){ // pais y departamento
            $pais = addslashes($data['pais']);
            $departamento = addslashes($data['departamento']);
            $where .= " AND pais = '$pais' AND departamento = $departamento";
            $order .= ", exposicion DESC";
        }
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){// busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);
            $where .= " AND MATCH(keywords) AGAINST('$data[producto_nombre]' IN NATURAL LANGUAGE MODE)";
            $order .= ", exposicion DESC";
        }
        if(isset($data['empresa']) && !empty($data['empresa'])){ // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND empresa = '1' AND uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['envio']) && !empty($data['envio'])){ // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])){ // ordenar precio
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order = "ORDER BY precio_usd $data[ordenamiento]";
            if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC"; // mas vendidos
        }
        if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){ // mas vendidos
            // $data['envio'] = addslashes($data['envio']);
            // $where .= " AND cantidad_vendidas > '0'";
            $order = "ORDER BY cantidad_vendidas DESC";
            // if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC";// ordenar precio
        }

        if( isset($data['uid']) && isset($data['empresa']) ) {
            $where .= " AND uid = '$data[uid]' AND empresa = '$data[empresa]'";
        }

        if( isset($data['id_producto']) ) {
            $where .= " AND p.id = '$data[id_producto]'";
        }

        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT ps.*, 
                    p.uid,
                    p.empresa,
                    p.producto,
                    p.marca,
                    p.modelo,
                    p.titulo,
                    p.descripcion,
                    p.foto_portada,
                    p.tipoSubasta, 
                    p.oferta, 
                    p.porcentaje_oferta, 
                    p.exposicion, 
                    p.precio AS precioProducto, 
                    p.moneda_local, 
                    p.pais, 
                    pst.descripcion AS tipo_descripcion ,
                    p.condicion_producto,
                    p.tiempo_estimado_envio_num,
                    p.tiempo_estimado_envio_unidad,
                    p.garantia
                    $select_precio
                    $subselect
                FROM productos_subastas ps
                INNER JOIN productos p ON ps.id_producto = p.id
                INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                JOIN (SELECT @row_number := 0) r 
                WHERE $where
                $order
                )as datos 
            $order
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $productos = parent::consultaTodo($selecthome);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron subastas productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $productos = $this->mapProductosSubastas($productos, $data);
        $selecttodos = "SELECT COUNT(ps.id) AS contar 
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id 
        WHERE $where;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'subastas productos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($productos), 'total_productos' => $todoslosproductos, 'data' => $productos);

    }

    public function inscribirseSubasta(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['id']) || !isset($data['ticket']) || !isset($data['cantidad']) || !isset($data['cantidad_ticket'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if($data['ticket'] == 6 && !isset($data['address'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $subasta = $this->subastaId($data, 1);
        if($subasta['status'] == 'fail') return array('status' => 'fail', 'message'=> 'venta no existe', 'data' => null);
        $subasta = $subasta['data'];

        if ($subasta['uid'] == $data['uid'] && $subasta['empresa'] == $data['empresa']) return array('status' => 'tuSubasta', 'message'=> 'no puedes inscribirte a tus propias subastas', 'data' => null);
        if ($subasta['tipo'] == 6 && !isset($data['address'])) return array('status' => 'fail', 'message'=> 'faltan datos 22', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        parent::addLogSubastas("1). [Producto_subasta/inscribirseSubasta/inscribirseSubasta]: " . json_encode($data));

        if($subasta['tipo'] == 6){

            parent::addLogSubastas("2). [Producto_subasta/inscribirseSubasta/entradaxxubastasxnormales]: " . $this->entradaxxubastasxnormales);
            parent::addLogSubastas("3). [Producto_subasta/inscribirseSubasta/subasta]: " . json_encode($subasta) );


            $porcentajexentrada = $this->truncNumber(($this->entradaxxubastasxnormales * $subasta['precioProducto'] * $data['cantidad']), 2);

            parent::addLogSubastas("3). porcentajexentrada: ". $porcentajexentrada);
            parent::addLogSubastas("4). moneda de iscripcion". $subasta['moneda'] );

            $dataArray = array( 
                "data" => 
                array(  "uid"                 => intval($data['uid']), 
                        "empresa"             => intval($data['empresa']),
                        "iso_code_2"          => "CO",
                        "iso_code_2_money"    => "CO"
                    )
            );            

            $URL = "http://nasbi.peers2win.com/api/controllers/nasbicoin/?wallet_usuario";
            $res = array(json_decode( parent::remoteRequest($URL, $dataArray), true ));
            $res = current($res);

            parent::addLogSubastas(json_encode($res));

            if ($res["status"] == 'success') {
                if ( $subasta['moneda'] == "Nasbigold" ) {
                    if( $res["nasbicoin_gold"]["monto"] < $porcentajexentrada ) {
                        return array('status' => 'errSaldoInsuficiente', 'message' => 'El saldo Nasbichips es insuficiente', 'data' => null);
                    }
                }else{
                    if( $res["nasbicoin_blue"]["monto"] < $porcentajexentrada ) {
                        return array('status' => 'errSaldoInsuficiente', 'message' => 'El saldo Nasbiblue es insuficiente', 'data' => null);
                    }
                }
            }

            $nasbifunciones = new Nasbifunciones();
            $dataEnviarDinero = array(
                'moneda' => $subasta['moneda'],
                'uid_envia' => $data['uid'],
                'empresa_envia' => $data['empresa'],
                'addres_envia' => $data['address'],
                'uid_recibe' => $this->addressxrecibextickets['uid'],
                'empresa_recibe' => $this->addressxrecibextickets['empresa'],
                'addres_recibe' => $this->addressxrecibextickets['address_'.$subasta['moneda']],
                'monto' => $porcentajexentrada,
                'tipo' => 4,
                'id_transaccion' => $subasta['id'].'_'.$data['uid'],
                'fecha' => $data['fecha']
            );
            $cobrar = $nasbifunciones->enviarDinero($dataEnviarDinero);

            parent::addLogSubastas("4). [Producto_subasta/inscribirseSubasta/dataEnviarDinero]: ". json_encode($dataEnviarDinero));
            unset($nasbifunciones);
            if($cobrar['status'] != 'success') return $cobrar;
        }else{

            $tickets = $this->verTicketsUsuario([
                'uid'      => $data['uid'],
                'empresa'  => $data['empresa'],
                'plan'     => $data['ticket'],
                'cantidad' => $data['cantidad_ticket']
            ]);

            if($tickets['status'] == 'fail') return $tickets;
            $tickets = $tickets['data'];

            $cantidad = intval($data['cantidad_ticket']);

            if( $data['ticket'] == 5 ) {//1 TICKET DIAMOND = 2 TICKETS PLATINUM
                $cantidad = $cantidad * 2;
            }
    
            $cobrar = $this->cobrarTicekts([
                'tickets'  => $tickets, 
                'cantidad' => $cantidad,
                'fecha'    => $fecha,
                'id_producto' => $subasta['id_producto']
            ]);
            if($cobrar['status'] != 'success') return $cobrar;
        }

        $inscrito = $this->isertarInscrito($data);
        if($inscrito['status'] == 'fail') return $inscrito;

        $estado = 1;
        $cantidadinscritos = floatval($subasta['inscritos'] + $data['cantidad']);

          //para el correo
          $data_for_correo=[]; 
          $data_for_correo["data_subasta"]= $subasta;
          $data_for_correo["cantidad_inscritos"]= $cantidadinscritos; 
          $data_for_correo["data_wbs"]=$data; 
          $this->correo_inscripcion_subasta($data_for_correo);
  
          //fin para el correo 


        return $this->actualizarEstadoSubasta([
            'id' => $subasta['id'],
            'estado' => $estado,
            'inscritos' => $cantidadinscritos,
            'fecha' => $data['fecha'],
            'mensaje_success' => 'inscrito registrado',
            'mensaje_fail' => 'inscrito no registrado'
        ]);

    }

    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2_money'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2_money']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];

                    $select_precio = ", (".$monedas_local['costo_dolar']."*p.precio_usd) AS precio_local_user, IF(1 < 2, '".$monedas_local['code']."', '') AS moneda_local_user";
                }
            }
        }

        return $select_precio;
    }
    function selectMonedaLocalUserByCountryID(Array $data)
    {
        $monedas_local = null;
        if(isset($data['country_id'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'country_id', $data['country_id']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                }
            }
        }

        return $monedas_local;
    }

    function getMonedaLocalUser(Array $data)
    {
        $select_precio = 1;
        $monedas_local = 'USD';
        if(isset($data['iso_code_2_money'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2_money']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    $select_precio = $monedas_local['costo_dolar'];
                    $monedas_local = $monedas_local['code'];
                }
            }
        }

        return [
            'precio' => $select_precio,
            'moneda' => $monedas_local,
        ];
    }


    public function cronSubastas()
    {
        $fecha = intval(microtime(true)*1000);
        $activarsubastas = $this->activarSubsta($fecha);
        $posponersubastas = $this->posponerSubsta($fecha);
        return [
            'status'=> 'success',
            'activar_subastas'=> $activarsubastas,
            'posponer_subastas'=> $posponersubastas
        ];
        
    }

    function activarSubsta(Int $fecha)
    {
        $tiempomaxxxx = $this->tiempomaxsubasta;

    

        $this->envio_de_correos_a_subasta_con_novedades([ //esta funcion consulta subastas actas de tiempo e inscritos 
            //igual las que ya cumplieron el tiempo pero no los inscritos y las que ya tienen el minimo de inscritos pero no el tiempo 
            'fecha_actual' => $fecha,
            'tiempo_max' => $tiempomaxxxx

        ]); 

        
        
        parent::conectar();
        $selectxsubasta = "UPDATE productos_subastas
        INNER JOIN productos ON productos_subastas.id_producto = productos .id
        SET productos_subastas.estado = '2',
        productos_subastas.fecha_actualizacion = '$fecha'
        WHERE ('$fecha' - productos_subastas.fecha_inicio) >= '$tiempomaxxxx' AND productos_subastas.estado = 1 AND  productos_subastas.inscritos >= productos_subastas.apostadores AND productos.estado = 1";
        // echo "SELECT ps.*, p.producto, p.marca, p.modelo, p.titulo, p.descripcion, p.uid, p.empresa
        // FROM productos_subastas ps
        // INNER JOIN productos p ON ps.id_producto = p.id
        // WHERE ('$fecha' - ps.fecha_inicio) >= '$tiempomaxxxx' AND ps.estado = 1 AND  ps.inscritos >= ps.apostadores AND p.estado = 1";
        $subasta = parent::query($selectxsubasta);
        parent::cerrar();
        if(!$subasta) return array('status' => 'fail', 'message' => 'no hay subastas que actualizar', 'data' => null);

        return array('status' => 'success', 'message' => 'subastas actualizadas', 'data' => null);
    }

    function posponerSubsta(Int $fecha)
    {
        $tiempomaxxxx = $this->tiempomaxsubasta;
        $fecha_inicio = $fecha + $tiempomaxxxx;
        
        parent::conectar();
        $selectxsubasta = "UPDATE productos_subastas
        INNER JOIN productos ON productos_subastas.id_producto = productos .id
        SET productos_subastas.estado = '1',
        productos_subastas.fecha_inicio = '$fecha_inicio',
        productos_subastas.fecha_actualizacion = '$fecha'
        WHERE ('$fecha' - productos_subastas.fecha_inicio) >= '$tiempomaxxxx' AND productos_subastas.estado = 1 AND  productos_subastas.inscritos < productos_subastas.apostadores AND productos.estado = 1";
        // echo "SELECT ps.*, p.producto, p.marca, p.modelo, p.titulo, p.descripcion, p.uid, p.empresa
        // FROM productos_subastas ps
        // INNER JOIN productos p ON ps.id_producto = p.id
        // WHERE ('$fecha' - ps.fecha_inicio) >= '$tiempomaxxxx' AND ps.estado = 1 AND  ps.inscritos < ps.apostadores AND p.estado = 1";
        $subasta = parent::query($selectxsubasta);
        parent::cerrar();
        if(!$subasta) return array('status' => 'fail', 'message' => 'no hay subastas que actualizar', 'data' => null);

        return array('status' => 'success', 'message' => 'subastas actualizadas', 'data' => null);
    }

    public function subastaId(Array $data, Int $estado)
    {
        $select_precio = $this->selectMonedaLocalUser($data);
        $request = null;
        parent::conectar();
        $selectxsubasta = "SELECT 
            ps.*,
            p.producto,
            p.marca,
            p.modelo,
            p.titulo,
            p.descripcion,
            p.uid,
            p.empresa,
            p.precio AS precioProducto,
            p.moneda_local,
            p.oferta, 
            p.tipoSubasta, 
            p.porcentaje_oferta, 
            p.exposicion, 
            p.precio AS precioProducto, 
            p.moneda_local, 
            p.pais
            $select_precio
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id
        WHERE ps.id = '$data[id]' AND (ps.estado = '$estado' OR p.estado < 4)";

        $subasta = parent::consultaTodo($selectxsubasta);
        if(count($subasta) > 0){
            $subasta = $this->mapProductosSubastas($subasta, $data);
            $subasta = $subasta[0];
            $request = array('status' => 'success', 'message' => 'data subasta', 'data' => $subasta);
        }else{
            $request = array('status' => 'fail', 'message' => 'faltan datos subasta', 'data' => null);
        }

        // echo( "Consulta: " . $selectxsubasta );
        parent::cerrar();
        return $request;
    }

    function actualizarEstadoSubasta(Array $data)
    {
        parent::conectar();

        $SQL = "SELECT productos_subastas.estado FROM productos_subastas WHERE id = '$data[id]'";
        $resp = parent::consultaTodo( $SQL );

        parent::cerrar();

        $estado_subasta = $resp[0]['estado'];

        $setEstadoSQL = "estado = '$data[estado]',";

        if( $estado_subasta != 1 ){
            $setEstadoSQL = "";
        }

        parent::conectar();
        $updatexsubastas = "UPDATE productos_subastas
        SET
            $setEstadoSQL
            inscritos = '$data[inscritos]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]'";
        $updatesubastas = parent::query($updatexsubastas);
        parent::cerrar();
        if(!$updatesubastas) return array('status' => 'fail', 'message'=> $data['mensaje_fail'], 'data' => null);
        
        return array('status' => 'success', 'message'=> $data['mensaje_success'], 'data' => null);
    }

    function isertarInscrito(Array $data)
    {
        parent::conectar();
        $insertarxinscrito = "INSERT INTO productos_subastas_inscritos
        (
            uid,
            empresa,
            id_subasta,
            cantidad,
            estado,
            ticket,
            cantidad_ticket,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[id]',
            '$data[cantidad]',
            '1',
            '$data[ticket]',
            '$data[cantidad_ticket]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insertar = parent::queryRegistro($insertarxinscrito);
        parent::cerrar();
        
        if(!$insertar) return array('status' => 'fail', 'message'=> 'inscrito no registrado', 'data' => null);

        return array('status' => 'success', 'message'=> 'inscrito registrado', 'data' => null);
    }

    function validarTipoSubasta(Array $subasta)
    {
        parent::conectar();
        $selectxtipoxsubasta = "SELECT pst.* FROM productos_subastas_tipo pst WHERE pst.id = '$subasta[tipo]' AND '$subasta[precio_usd]' >= pst.rango_inferior_usd  AND  '$subasta[precio_usd]' <= pst.rango_superior_usd;";
        $selecttipo = parent::consultaTodo($selectxtipoxsubasta);
        parent::cerrar();
        if (count($selecttipo) <= 0) return array('status' => 'fail', 'message'=> 'tipo de subasta no valida', 'data' => null);
        return array('status' => 'success', 'message'=> 'tipo de subasta valida', 'data' => $selecttipo[0]);
    }

    function verTicketsUsuario(Array $data)
    {   
        $es_diamond = false;
        if( $data['plan'] == 5 ) {//1 TICKET DIAMOND = 2 TICKETS PLATINUM
            $es_diamond   = true;
            $data['plan'] = 4;
        }

        parent::conectar();
        $selectxticketsxuser = "SELECT nt.*, ntp.nombre AS nombre_plan 
        FROM nasbitickets nt 
        INNER JOIN nasbitickets_plan ntp ON nt.plan = ntp.id
        WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' AND nt.uso = '2' AND nt.plan = '$data[plan]' AND nt.estado = 1
        ORDER BY fecha_creacion ASC";
        $tickets = parent::consultaTodo($selectxticketsxuser);
        parent::cerrar();

        if(!$tickets) return array('status' => 'fail', 'message'=> 'no tickets', 'cantidad_tickets' => 0, 'data' => null);

        $tickets = $this->mapTickets($tickets, $es_diamond);

        if( $data['cantidad'] > $tickets['cantidad_tickets']) return array('status' => 'fail', 'message'=> 'no cantidad tickets', 'cantidad_tickets' => $tickets['cantidad_tickets'], 'data' => null);

        return array('status' => 'success', 'message'=> 'planes', 'cantidad_tickets' => $tickets['cantidad_tickets'], 'data' => $tickets['tickets']);
    }

    function cobrarTicekts(Array $data)
    {
        parent::conectar();
        $cobro = $this->actualizarTickets([
            'tickets' => $data['tickets'], 
            'actual' => 0, 
            'cantidad' => $data['cantidad'],
            'fecha' => $data['fecha'],
            'id_producto' => $data['id_producto']
        ]);
        parent::cerrar();
        
        if(!$cobro) return array('status' => 'fail', 'message'=> 'no cobro tickets', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'cobro tickets', 'data' => $cobro);
        
    }

    function actualizarTickets(Array $data)
    {//ESTA FUNCIÓN ES RECURSIVA

        $actual = $data['actual'];
        $ticket = $data['tickets'];
        $cantidad = $data['cantidad'];

        if($cantidad <= 0) return true;

        $cantidad_insertar = 0;
        $cantidad_restante = 0;
        $ticket = $ticket[$actual];
        $resta = $ticket['cantidad'] - $cantidad;

        $estado = 1;
        if($resta < 0){
            $cantidad_insertar = $ticket['cantidad'];

            $estado = 0;
            $cantidad_restante = 0;

            $cantidad = $resta * (-1);
        }else{
            $cantidad_insertar = $cantidad;
            
            if($resta == 0) $estado = 0;
            $cantidad_restante = $resta;
            
            $cantidad = 0;

        }

        $insertarxhistorico = "INSERT INTO nasbitickets_historico
        (
            id_nasbitickets,
            plan,
            uid,
            empresa,
            cantidad,
            codigo,
            uso,
            id_producto,
            fecha_creacion
        )
        VALUES
        (
            '$ticket[id]',
            '$ticket[plan]',
            '$ticket[uid]',
            '$ticket[empresa]',
            '$cantidad_insertar',
            '$ticket[codigo]',
            '$ticket[uso]',
            '$data[id_producto]',
            '$data[fecha]'
        );";
        $insertar = parent::queryRegistro($insertarxhistorico);
        if(!$insertar) return false;
        
        $ubdatexticket = "UPDATE nasbitickets 
        SET 
            cantidad = '$cantidad_restante',
            estado = '$estado',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$ticket[id]'";
        $update = parent::query($ubdatexticket);
        if(!$update) return false;

        $actual++;
        return $this->actualizarTickets([
            'tickets' => $data['tickets'], 
            'actual' => $actual, 
            'cantidad' => $cantidad,
            'fecha' => $data['fecha'],
            'id_producto' => $data['id_producto']
        ]);

    }

    function mapTickets(Array $tickets, $es_diamond)
    {   
        $cantidad_tickets = 0;

        foreach ($tickets as $x => $ticket) {
            $ticket['plan'] = floatval($ticket['plan']);
            $ticket['cantidad'] = floatval($ticket['cantidad']);
            $cantidad_tickets += $ticket['cantidad'];
            $ticket['id'] = floatval($ticket['id']);
            $ticket['uid'] = floatval($ticket['uid']);
            $ticket['empresa'] = floatval($ticket['empresa']);
            $ticket['id_nabitickets_usuario'] = floatval($ticket['id_nabitickets_usuario']);
            $ticket['transferido'] = floatval($ticket['transferido']);
            $ticket['uso'] = floatval($ticket['uso']);
            $ticket['origen'] = floatval($ticket['origen']);
            $ticket['estado'] = floatval($ticket['estado']);
            $ticket['fecha_creacion'] = floatval($ticket['fecha_creacion']);
            $ticket['fecha_actualizacion'] = floatval($ticket['fecha_actualizacion']);
            $ticket['fecha_vencimiento'] = floatval($ticket['fecha_vencimiento']);
            $tickets[$x] = $ticket;
        }

        if( $es_diamond ) {
            $cantidad_tickets = intval($cantidad_tickets / 2);//AQUÍ HAY TRUNCAMIENTO DE DECIMALES
        }

        return array(
            'cantidad_tickets' => $cantidad_tickets,
            'tickets' => $tickets,
        );

    }

    function obtenerCategoria( $precio ){
        $SQL = "SELECT * FROM productos_subastas_tipo
                pst WHERE '$precio'
                BETWEEN rango_inferior_usd
                AND rango_superior_usd";

        $categoria = parent::consultaTodo($SQL);
        
        return $categoria[0];
    }

    function calcularPrimeraPuja( $descuento, $precio ){
        return ($precio - (($descuento / 100) * $precio) );
    }
    
    function calcularRecaudoMinimo( $precio, $puja ){
        return ( ($precio / 0.77 ) - $puja );
    }
    
    function calcularMinimoInscritos( $recaudo, $costo_ticket ){
        return ( $recaudo / $costo_ticket );
    }
    
    function obtenerTablaReferencia( $precio, $categoria ){
    
        $tabla = null;
    
        for($i = 1; $i <= 6; $i++) {
            $puja      = $this->calcularPrimeraPuja( $i * 10 , $precio );
            $recaudo   = $this->calcularRecaudoMinimo( $precio, $puja );
            $inscritos = $this->calcularMinimoInscritos( $recaudo, floatval($categoria['costo_ticket_usd']) );
    
            $tabla['descuentos'][ strval($i * 10) ] =
            array(
                "descuento"		=> ($i * 10),
                "primera_puja"  => intval($puja),
                "recaudo"       => round($recaudo, 2),
                "min_inscritos" => round($inscritos)
            );
        }
    
        return $tabla;
    }
    
    function calcularRangoSuperior( &$tabla ){
        foreach( $tabla['descuentos'] as $k => &$oferta ){
            if( $k != 60 ){
                $sig = $tabla['descuentos'][$k + 10];
                $oferta['max_inscritos'] = $sig['min_inscritos'] - 1;
            }else{
                $oferta['max_inscritos'] = 9999999;
            }
        }
    
    }
    
    function obtenerProductoDescuento( $tabla, $inscritos ){
        $producto = array_filter( $tabla['descuentos'], function( $descuento ) use ($inscritos){
            return $inscritos >= $descuento['min_inscritos'] && $inscritos <= $descuento['max_inscritos'];
        });
    
        return current($producto);
    }

    function mapProductosSubastas(Array $productos, Array $dataRecibida = null){
        

        $dataMonedaLocal = $this->selectMonedaLocalUserByCountryID([
            'country_id' => $productos[ 0 ]['pais']
        ]);
        // $dataRecibida['iso_code_2_money'] = $dataMonedaLocal['iso_code_2'];
        foreach ($productos as $x => $producto) {
            $producto['id'] = floatval($producto['id']);
            $producto['id_producto'] = floatval($producto['id_producto']);
            $producto['precio'] = floatval($producto['precio']);
            $producto['precio_mask'] = $this->maskNumber($producto['precio'], 6);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask'] = $this->maskNumber($producto['precio_usd'], 2);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['tipo'] = floatval($producto['tipo']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['apostadores'] = floatval($producto['apostadores']);
            $producto['inscritos'] = floatval($producto['inscritos']);
            $producto['inscritos_activacion_subasta'] = intval($producto['inscritos_activacion_subasta']);
            $producto['fecha_fin'] = floatval($producto['fecha_fin']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['porcentaje_entrada'] = null;
            $producto['porcentaje_entrada_mask'] = null;

            $producto['oferta'] = floatval( $producto['oferta'] );
            $producto['porcentaje_oferta'] = floatval( $producto['porcentaje_oferta'] );
            $producto['precio'] = floatval( $producto['precio'] );
            $producto['precioProducto'] = floatval( $producto['precioProducto'] );
            
            if ( floatval( $producto['oferta'] ) > 0 ) {
                $producto['precioProducto'] = ( $producto['precioProducto'] - ( $producto['precioProducto'] * ($producto['porcentaje_oferta']/100)));
                $producto['precio'] = ( $producto['precio'] - ( $producto['precio'] * ($producto['porcentaje_oferta']/100)));
            }
            if( !isset($producto['tipoSubasta']) ) {
                $producto['tipoSubasta'] = 0;
            }
            if($producto['tipoSubasta'] == 6) {
                
                $producto['porcentaje_entrada'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio']), 2);
                $producto['porcentaje_entrada_mask']= $this->maskNumber($producto['porcentaje_entrada'], 2);
                
                $producto['porcentaje_entrada_usd'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_usd_mask']= $this->maskNumber($producto['porcentaje_entrada_usd'], 2);

                $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);

                $producto['_dataRecibida'] = $dataRecibida;
                $producto['_dataMonedaLocal'] = $dataMonedaLocal;

                
                if(isset($dataRecibida) && isset($dataRecibida['iso_code_2_money'])) {

                    if ( ($dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) && $dataMonedaLocal['code'] != "US" ) {

                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precioProducto']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                        
                    }else{

                        $moneda_local = $this->getMonedaLocalUser($dataRecibida);
                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                    }
                }

                // $inscritos = $producto['inscritos'];
                // if ($inscritos < 15) $inscritos = 15;
                // if ($inscritos > 35) $inscritos = 35;
                // $producto['porcentaje'] = $this->truncNumber(floatval(( (($inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal )) * 100), 1);
            }


            if(isset($producto['precio_local_user'])){
                if ( $dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) {
                    $producto['precio_local_user'] = floatval($this->truncNumber($producto['precioProducto'],2));
                    $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);

                    
                }else{
                    $producto['precio_local_user'] = floatval($this->truncNumber($producto['precio_local_user'],2));
                    $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);

                }
            }else{
                $producto['precio_local_user'] = $producto['precio_usd'];
                $producto['precio_local_user_mask'] = $producto['precio_usd_mask'];
                $producto['moneda_local_user'] = 'USD';


            }


            if( $producto['estado'] > 2 ){
                $producto['inscritos_activacion_subasta'] = intval( $producto['inscritos_activacion_subasta'] );
                if( $producto['inscritos_activacion_subasta'] == 0 ){
                    $producto['inscritos_activacion_subasta'] = $producto['inscritos'];
                }
                $producto['inscritos'] = $producto['inscritos_activacion_subasta'];
            }

            $inscritos = 0;
            intval($producto['inscritos_activacion_subasta']) > 0 ? $inscritos = intval($producto['inscritos_activacion_subasta']) : $inscritos = $producto['inscritos'];

            $producto['porcentaje'] = 0;
            if( $producto['tipoSubasta'] == 6 ){
                if( $inscritos >= 15 ){

                    if($inscritos > 35){
                        $inscritos = 35;
                    }

                    $producto['entradaxxubastasxnormales'] = $this->entradaxxubastasxnormales;

                    $producto['porcentajexrestarxnormal']  = $this->porcentajexrestarxnormal;

                    $producto['porcentaje'] = ( ($inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal ) * 100;

                    $producto['porcentaje'] = $this->truncNumber($producto['porcentaje'], 1);
                
                }

            }else{

                $selectednumber = parent::consultaTodo("SELECT * FROM productos_subastas_tipo pst WHERE $producto[precio_usd] BETWEEN rango_inferior_usd AND rango_superior_usd");

                $productos_subastas_tipo = array(
                    "costo_ticket_usd" => 0
                );

                for ($i = 1; $i <= 6; $i++) {

                    $index = $i * 10;
                    $index = $index / 100;

                    # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
                    # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
                    $recaudar_entradas = ($producto['precio_usd'] / 0.77) - ($producto['precio_usd'] - ($producto['precio_usd'] * $index));
                    #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
                    $productos_subastas_tipo = $selectednumber[0];
                    $apostadores             = $recaudar_entradas / $productos_subastas_tipo['costo_ticket_usd'];
                    $apostadores             = round($apostadores);

                    if ($inscritos >= $apostadores) {
                        $producto['porcentaje']             = $index * 100;
                        $producto['extra_subasta_tiquetes'] = array(
                            "productos_subastas_tipo" => $productos_subastas_tipo,
                            "recaudar_entradas"       => $recaudar_entradas,
                            "porcentaje"              => $index * 100,
                            "porcentaje_descuento"    => $index,
                            "costo_usd"               => $producto['precio_usd'],
                            "costo_cop"               => $producto['precio_local_user'],
                            "apostadores_subastas"    => $apostadores
                        );
                    }else{
                        break;
                    } 
                }
            }

            //if($producto['tipoSubasta'] == 6) { //si cambia este calculo por favor hacer el cambio en consulta de maximo y minimos de subasta normales
                $producto['precio_subasta_local_user'] = $producto['precio_local_user'] - floatval($producto['precio_local_user'] * ($producto['porcentaje'] / 100));
                $producto['precio_subasta_local_user_mask'] = $this->maskNumber($producto['precio_subasta_local_user'], 2);
            //}

            $producto['array_fotos'] = $this->fotosproductoId($producto)['data'];

            $productos[$x] = $producto;
        }
        return $productos;
    }

    public function fotosproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        // parent::conectar();
        $selectxfotosproducto = "SELECT pf.*
        FROM productos_fotos pf
        WHERE pf.id_publicacion = '$data[id_producto]' AND pf.estado = '1'";
        $fotos = parent::consultaTodo($selectxfotosproducto);
        // parent::cerrar();
        if(count($fotos) <= 0) return array('status' => 'fail', 'message'=> 'no fotos prodcuto', 'data' => null);
        $fotos = $this->mapFotosProductos($fotos);
        return array('status' => 'success', 'message'=> 'fotos prodcuto', 'data' => $fotos);
    }

    function datosUser(Array $data)
    {
        $selectxuser = null;
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        $usuario = parent::consultaTodo($selectxuser);
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

    function mapFotosProductos(Array $fotos)
    {
        foreach ($fotos as $x => $foto) {
            $foto['id'] = floatval($foto['id']);
            $foto['id_publicacion'] = floatval($foto['id_publicacion']);
            $foto['estado'] = floatval($foto['estado']);
            $foto['fecha_creacion'] = floatval($foto['fecha_creacion']);
            $foto['fecha_actualizacion'] = floatval($foto['fecha_actualizacion']);
            $fotos[$x] = $foto;
        }

        return $fotos;
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

    function truncNumber( $number, $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }

    function maskNumber($numero, $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }



    //funciones para los correos
    public function correo_inscripcion_subasta(Array $data_to_correo){
        $data_subasta= $data_to_correo["data_subasta"]; 
        $cantidad_inscritos= $data_to_correo["cantidad_inscritos"];
        $data_wbs= $data_to_correo["data_wbs"];

        $data_user_incrito  =  $this->datosUserGeneral2([
            'uid' => $data_wbs['uid'],
            'empresa' => $data_wbs['empresa']
        ]);

        $data_producto_vendedor=$this->get_product_por_id_sin_uid([
            'id' => $data_subasta["id_producto"] ]
        );

        $data_producto_vendedor= $data_producto_vendedor[0]; 

        $data_vendedor= $this->datosUserGeneral2([
            'uid' => $data_producto_vendedor["uid"] , 
            'empresa' => $data_producto_vendedor["empresa"]
            ]);

         if($cantidad_inscritos >= $data_subasta["apostadores"]){ 
            $porcentaje_normal_descuento=0; 
            $es_normal=0; 
            if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales  
                $porcentaje_normal_descuento = $this->truncNumber(floatval(( (($cantidad_inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal )) * 100), 1);//porcentaje de descuento_subasta normal 
                $es_normal=1; 
            }
             $this->html_envio_correos_minimo_inscrito_superado($data_vendedor["data"],$data_subasta, $data_wbs, $porcentaje_normal_descuento, $es_normal ); 
             $this->html_envio_correos_minimo_inscrito_superado_vendedor($data_vendedor["data"],$data_subasta, $data_wbs,$data_producto_vendedor, $porcentaje_normal_descuento, $es_normal); 
         }

        if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales 
            $this->html_envio_correo_inscripcion_subasta_normal($data_user_incrito["data"],$data_subasta, $data_wbs); 
        }else if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]!= 6){ // Subasta premium 
            $this->html_envio_correo_inscripcion_subasta_premium($data_user_incrito["data"],$data_subasta, $data_wbs); 
        } 


    }


    function datosUserGeneral2( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }
    
    

    function html_envio_correo_inscripcion_subasta_normal(Array $data_user, Array $data_subasta, Array $data_wbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo6.html");
        
        
      
        $html = str_replace("{{trans206_brand}}", $json->trans206_brand, $html);
        $html = str_replace("{{trans207}}", $json->trans207, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);
        $html = str_replace("{{trans208}}", $json->trans208, $html);
        $html = str_replace("{{trans209}}", $json->trans209, $html);
        $html = str_replace("{{trans210}}", $json->trans210, $html);
        $html = str_replace("{{trans211}}", $json->trans211, $html);
        $html = str_replace("{{trans204}}", $json->trans204, $html);
        $html = str_replace("{{trans205}}", $json->trans205, $html);
        $html = str_replace("{{id_subasta}}", $data_wbs["id"], $html);
        
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_misubasta, $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{trans245}}",$json->trans245, $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans131_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function html_envio_correo_inscripcion_subasta_premium(Array $data_user, Array $data_subasta, Array $data_wbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo7.html");
        
        
      
        $html = str_replace("{{trans198_brand}}", $json->trans198_brand, $html);
        $html = str_replace("{{trans199}}", $json->trans199, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);
        $html = str_replace("{{trans200}}", $json->trans200, $html);
        $html = str_replace("{{trans201}}", $json->trans201, $html);
        $html = str_replace("{{subasta}}", $data_wbs["id"], $html);
        $html = str_replace("{{trans202}}", $json->trans202, $html);
        $html = str_replace("{{continuacion}}","", $html);
        $html = str_replace("{{trans203}}", $json->trans203, $html);
        $html = str_replace("{{trans204}}", $json->trans204, $html);
        $html = str_replace("{{trans205}}", $json->trans205, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_misubasta, $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans132_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    public function html_envio_correos_minimo_inscrito_superado(Array $data_user, Array $data_subasta, Array $data_wbs, Int $porcentaje_descuento, Int $es_normal)
    {
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo8.html");
        
        if($es_normal == 1){
            $html = str_replace("{{trans69}}",$porcentaje_descuento."%", $html);
            $html = str_replace("{{trans68}}", $json->trans68, $html);
            $html = str_replace("{{trans70}}", $json->trans70, $html);
        }else{
            $html = str_replace("{{trans68}}", "", $html);
            $html = str_replace("{{trans70}}", "", $html);
            $html = str_replace("{{trans69}}","", $html);
        }
      
        $html = str_replace("{{trans194_brand}}", $json->trans194_brand, $html);
        $html = str_replace("{{trans195}}", $json->trans195, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);
        $html = str_replace("{{trans182}}", $json->trans182, $html);
        $html = str_replace("{{subasta}}", $data_wbs["id"], $html);
        $html = str_replace("{{trans196}}", $json->trans196, $html);
      
        $html = str_replace("{{trans197}}", $json->trans197, $html);
        $html = str_replace("{{conteo_tiempo}}", $json->conteo_tiempo, $html);
        $html = str_replace("{{trans58}}", $json->trans58, $html);
        $html = str_replace("{{link_to_misubasta}}", $json->link_to_misubasta, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans141_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    } 


    function get_product_por_id_sin_uid(Array $data){
        parent::conectar();
        $producto_vendedor = parent::consultaTodo("SELECT  p.uid, p.empresa, ps.id as 'id_subasta', ps.id_producto, p.foto_portada, p.titulo  FROM productos_subastas as ps inner join productos as p on ps.id_producto = p.id WHERE p.id = '$data[id]';");
        parent::cerrar();
        return $producto_vendedor;
    }

    function get_subasta_actas_para_activar(Array $data){
        parent::conectar();
        $subastas_actas = parent::consultaTodo("SELECT ps.*, p.titulo, p.foto_portada, p.uid as 'uid_vendedor', p.empresa as 'empresa_vendedor'  FROM productos_subastas as ps inner join productos as p on ps.id_producto = p.id  WHERE ('$data[fecha_actual]' - ps.fecha_inicio ) >= '$data[tiempo_max]' AND ps.estado = 1 AND  ps.inscritos >= ps.apostadores AND p.estado = 1;");
        parent::cerrar();
        return $subastas_actas;
    }

    function get_subasta_tiempo_cumplido_pero_no_inscritos(Array $data){
        parent::conectar();
        $subastas_actas = parent::consultaTodo("SELECT ps.*, p.titulo, p.foto_portada, p.uid as 'uid_vendedor', p.empresa as 'empresa_vendedor'  FROM productos_subastas as ps inner join productos as p on ps.id_producto = p.id  WHERE ('$data[fecha_actual]' - ps.fecha_inicio ) >= '$data[tiempo_max]' AND ps.estado = 1 AND  ps.inscritos < ps.apostadores AND p.estado = 1;");
        parent::cerrar();
        return $subastas_actas;
    }

    function get_subasta_incritos_minimos_completos_pero_no_tiempo_de_comienzo(Array $data){
        parent::conectar();
        $subastas_actas = parent::consultaTodo("SELECT ps.*, p.titulo, p.foto_portada, p.uid as 'uid_vendedor', p.empresa as 'empresa_vendedor'  FROM productos_subastas as ps inner join productos as p on ps.id_producto = p.id  WHERE ('$data[fecha_actual]' - ps.fecha_inicio ) < '$data[tiempo_max]' AND ps.estado = 1 AND  ps.inscritos >= ps.apostadores AND p.estado = 1;");
        parent::cerrar();
        return $subastas_actas;
    }

    function optener_infomacion_subasta_inscritos(Array $data){
        parent::conectar();
        $info_incritos_de_subasta = parent::consultaTodo("SELECT * FROM productos_subastas_inscritos WHERE id_subasta = '$data[id]'; ");
        parent::cerrar();
        return $info_incritos_de_subasta;
    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos;
    }



    function html_envio_correos_minimo_inscrito_superado_vendedor(Array $data_user, Array $data_subasta, Array $data_wbs, Array $data_producto_vendedor,Int $porcentaje_descuento, Int $es_normal ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo19yacomienzatusubasta.html");

        if($es_normal == 1){
            $html = str_replace("{{trans69}}",$porcentaje_descuento."%", $html);
            $html = str_replace("{{trans68}}", $json->trans68, $html);
            $html = str_replace("{{trans70}}", $json->trans70, $html);
        }else{
            $html = str_replace("{{trans68}}", "", $html);
            $html = str_replace("{{trans70}}", "", $html);
            $html = str_replace("{{trans69}}","", $html);
        }

        $html = str_replace("{{subasta}}", $data_wbs["id"], $html);
      
        $html = str_replace("{{trans_01_brand}}", $json->trans_01_brand, $html);
        $html = str_replace("{{trans63}}", $json->trans63, $html);
        $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);
        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans64}}", $json->trans64, $html);
        $html = str_replace("{{trans65}}", $json->trans65, $html);
        $html = str_replace("{{trans66}}", $json->trans66, $html);
        $html = str_replace("{{publicacion_titulo_subasta}}",$data_producto_vendedor["titulo"] , $html);
        $html = str_replace("{{trans67}}",$json->trans67 , $html);
        $html = str_replace("{{foto_producto_comezo_subasta_brand}}", $data_producto_vendedor["foto_portada"], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        $html = str_replace("{{trans58}}", $json->trans58, $html);
        $html = str_replace("{{trans71}}", $json->trans71, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans141_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }



    function envio_de_correos_a_subasta_con_novedades(Array $data) {
        $subastas_actas_para_activar  =  $this->get_subasta_actas_para_activar([
            'fecha_actual' => $data['fecha_actual'],
            'tiempo_max' => $data['tiempo_max']
        ]);

        $subastas_que_ya_cumplieron_no_inscritos= $this->get_subasta_tiempo_cumplido_pero_no_inscritos([
            'fecha_actual' => $data['fecha_actual'],
            'tiempo_max' => $data['tiempo_max']
        ]);

        // $inscritos_minimos_completos_no_tiempo= $this->get_subasta_incritos_minimos_completos_pero_no_tiempo_de_comienzo([
        //     'fecha_actual' => $data['fecha_actual'],
        //     'tiempo_max' => $data['tiempo_max']
        // ]);

        //envio de correos de subasta com novedades 

        if ( count($subastas_actas_para_activar) > 0 ) {
            $this->envio_correo_de_subasta_con_novedades_flujo($subastas_actas_para_activar, 1); //las que ya estan listas 
            
        }

        if ( count($subastas_que_ya_cumplieron_no_inscritos) > 0 ) {
            $this->envio_correo_de_subasta_con_novedades_flujo($subastas_que_ya_cumplieron_no_inscritos, 2); //las que les faltan incritos
        }

        // if ( count($inscritos_minimos_completos_no_tiempo) > 0 ) {
        //   //  $this->envio_correo_de_subasta_que_les_falta_tiempo(); //las que les falta tiempo 
        // }
       


    }




    function envio_correo_de_subasta_con_novedades_flujo(Array $data_subastas_para_activar, Int $tipo_envio){
        foreach ($data_subastas_para_activar as $j => $subasta_lista) {

            $traer_data_subasta_por_id_e_inscritos= $this->optener_infomacion_subasta_inscritos([
                'id' => $subasta_lista["id"]
            ]);
            $data_vendedor =  $this->datosUserGeneral2([
                'uid' => $subasta_lista['uid_vendedor'],
                'empresa' => $subasta_lista['empresa_vendedor']
            ]);

            $data_producto=$this->get_product_por_id([
                'uid' => $subasta_lista["uid_vendedor"],
                'id' => $subasta_lista["id_producto"],
                'empresa'  => $subasta_lista["empresa_vendedor"] ]);
                

            foreach ($traer_data_subasta_por_id_e_inscritos as $key => $inscrito) {
                $data_cliente=  $this->datosUserGeneral2([
                    'uid' => $inscrito['uid'],
                    'empresa' => $inscrito['empresa']
                ]);

                $this->enviocorreo_html_subasta($data_vendedor["data"], $data_producto[0],  $data_cliente["data"], $tipo_envio, $subasta_lista, $key); 
            }

        }


    }


    function enviocorreo_html_subasta(Array $data_vendedor, Array $data_producto, Array $data_cliente, Int $tipo, Array $data_subasta, Int $key){
        switch ($tipo) {
            case 1:
                $this->envio_correo_html_subasta_comenzo_comprador($data_vendedor, $data_producto, $data_cliente, $data_subasta); 
                if($key==0){//para que solo lo llame una vez 
                    $this->envio_correo_html_subasta_comenzo_vendedor($data_vendedor, $data_producto, $data_cliente, $data_subasta); 
                }
                break;

            case 2: //cuando ya esta en el tiempo pero no tiene suficientes inscritos 
                $this->envio_correo_html_no_suficientes_inscritos_comprador($data_vendedor, $data_producto, $data_cliente, $data_subasta); 
                if($key==0){//para que solo lo llame una vez 
                     $this->envio_correo_html_no_suficientes_inscritos_vendedor($data_vendedor, $data_producto, $data_cliente, $data_subasta); 
                }
                break;
            
            default:
                # code...
                break;
        }
    }


    public function envio_correo_html_subasta_comenzo_comprador(Array $data_vendedor, Array $data_producto, Array $data_cliente, Array $data_subasta)
    {
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_cliente["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo11.html");
        


        $html = str_replace("{{trans179_brand}}",$json->trans179_brand, $html);
        $html = str_replace("{{trans180}}", $json->trans180, $html);
        $html = str_replace("{{trans181}}",$json->trans181, $html);
        $html = str_replace("{{trans182}}", $json->trans182, $html);
        $html = str_replace("{{nombre_usuario}}", $data_cliente["nombre"], $html);
        $html = str_replace("{{subasta}}", $data_subasta["id"], $html);
        $html = str_replace("{{trans183}}", $json->trans183, $html);
        $html = str_replace("{{trans184}}", $json->trans184, $html);
        $html = str_replace("{{link_to_nasbidescuento}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans174}}", $json->trans174, $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_cliente["uid"]."&act=0&em=".$data_cliente["empresa"], $html); 

        $para      = $data_cliente['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans143_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
       
    }



    public function envio_correo_html_subasta_comenzo_vendedor(Array $data_vendedor, Array $data_producto, Array $data_cliente, Array $data_subasta)
    {
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo21comenzolacarrera.html");


        $html = str_replace("{{trans56_brand}}",$json->trans56_brand, $html);
        $html = str_replace("{{trans57}}", $json->trans57, $html);
        $html = str_replace("{{foto_producto_carrera_brand}}", $data_producto["foto_portada"], $html);
        $html = str_replace("{{titulo_producto}}", $data_producto["titulo"], $html);
        $html = str_replace("{{link_to_nasbidescuento}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{trans58}}", $json->trans58, $html);
        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 

        $para      = $data_vendedor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans143_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
       
    }


    public function envio_correo_html_no_suficientes_inscritos_comprador(Array $data_vendedor, Array $data_producto, Array $data_cliente, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_cliente["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo9.html");
        
        

        $html = str_replace("{{trans190_brand}}",$json->trans190_brand, $html);
        $html = str_replace("{{trans191}}", $json->trans191, $html);
        $html = str_replace("{{nombre_usuario}}", $data_cliente["nombre"], $html);
        $html = str_replace("{{trans182}}", $json->trans182, $html);
        $html = str_replace("{{trans192}}",$json->trans192, $html);
        $html = str_replace("{{subasta}}", $data_subasta["id"], $html);
        $html = str_replace("{{trans193}}", $json->trans193, $html);
        $html = str_replace("{{trans58}}", $json->trans58, $html);
        $html = str_replace("{{link_to_subasta_id}}", "https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta["id"], $html);

        

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_cliente["uid"]."&act=0&em=".$data_cliente["empresa"], $html); 

        $para      = $data_cliente['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans144_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    public function envio_correo_html_no_suficientes_inscritos_vendedor(Array $data_vendedor, Array $data_producto, Array $data_cliente, Array $data_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo20tusubastanocomenzo.html");

        $html = str_replace("{{trans_brand}}",$json->trans_brand, $html);
        $html = str_replace("{{trans59}}", $json->trans59, $html);
        $html = str_replace("{{trans60}}",$json->trans60, $html);
        $html = str_replace("{{nombre_usuario}}", $data_vendedor["nombre"], $html);
        $html = str_replace("{{trans61}}", $json->trans61, $html);
        $html = str_replace("{{foto_producto_subasta_no_comenzo_brand}}", $data_producto["foto_portada"], $html);
        $html = str_replace("{{link_to_resumen}}", $json->link_to_resumen, $html);
        $html = str_replace("{{trans62}}", $json->trans62, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto["titulo"], $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 

        $para      = $data_vendedor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans144_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);


    }
    //fin funciones para los correos
    public function filtrosSubasta2(Array $data)
    {

        if(!isset($data['pais']) && !isset($data['departamento'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $select_precio = $this->selectMonedaLocalUser($data);
        $campo_porcentaje_subasta=""; 
        $pagina = floatval($data['pagina']);
        $numpagina = 10;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        $order_relevante=""; 
        $where = " p.estado != 0 AND p.estado != 2 AND p.estado < 4 ";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null;
        $subselect = "";
        $select_masrelevantes=""; 
        $filtro_ordenamiento=0;
        $join=""; 
        if($data['tipo'] == 1){ // Nasbi normales
            $where .= " AND (ps.estado = 1 OR ps.estado = 2) AND ps.tipo = 6";
            $order .= ", exposicion DESC";
        }
        if($data['tipo'] == 2){ // Nasbi premium
            $where .= " AND (ps.estado = 1 OR ps.estado = 2) AND ps.tipo <> 6";
            $order .= ", exposicion DESC";
        }
        if(isset($data['exposicion']) && !empty($data['exposicion'])){ // destacado
            $data['exposicion'] = addslashes($data['exposicion']);
            $where .= " AND exposicion = '$data[exposicion]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['garantia']) && $data['garantia'] != ''){ // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
          //  $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND pais = '$pais'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && isset($data['departamento']) && !empty($data['departamento'])){ // pais y departamento
            $pais = addslashes($data['pais']);
            $departamento = addslashes($data['departamento']);
            $where .= " AND pais = '$pais' AND departamento = $departamento";
            $order .= ", exposicion DESC";
        }
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){// busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);
            $where .= " AND MATCH(keywords) AGAINST('$data[producto_nombre]' IN NATURAL LANGUAGE MODE)";
            $order .= ", exposicion DESC";
        }
        if(isset($data['empresa']) && !empty($data['empresa'])){ // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND empresa = '1' AND uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['envio']) && !empty($data['envio'])){ // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])){ // ordenar precit
            $filtro_ordenamiento= 1; 
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            // $order = "ORDER BY precio_usd $data[ordenamiento]";
            $order = "ORDER BY precioProducto $data[ordenamiento]";
         //   if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC"; // mas vendidos
        }
        // if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){ // mas vendidos
        //     // $data['envio'] = addslashes($data['envio']);
        //     // $where .= " AND cantidad_vendidas > '0'";
        //     $order = "ORDER BY cantidad_vendidas DESC";
        //     // if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC";// ordenar precio
        // }

        //nuevos campos 


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor'])  && $data['tipo'] == 2 ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) >=  '$data[minimo_valor]' AND  (p.precio-(p.precio*(p.porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor'])  && $data['tipo'] == 1){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta >=  '$data[minimo_valor]' AND  $valor_subasta_sin_porcentaje_subasta <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }

        //solo minimo 


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) && $data['tipo'] == 2  ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) >=  '$data[minimo_valor]'";
            $order .= ", precioProducto DESC";
        }

        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) && $data['tipo'] == 1  ){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta  >=  '$data[minimo_valor]'";
            $order .= ", precioProducto DESC";
        }
        // fin solo minimo 
        //solo maximo

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor']) && $data['tipo'] == 2 ){ // minimo valor & valor mayor 
             $data['maximo_valor'] = intval($data['maximo_valor']);
             $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
             $order .= ", precioProducto DESC";
         }
 

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor']) && $data['tipo'] == 1 ){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 

            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }

          // fin solo maximo


        if(isset($data['genero']) && !empty($data['genero'])){ // genero
            $data['genero'] = intval($data['genero']);
            $data['genero'] = addslashes($data['genero']);
            $where .= " AND genero = '$data[genero]'";
           // $order .= ", genero DESC";
        }

        if(isset($data['mas_relevante']) && !empty($data['mas_relevante']) && $data['mas_relevante']== 1){ // para que muestre del que tiene mas visitas a menos los que no muestra es porque no tiene 
            //  $selecthome= "SELECT p.*, ht.visitas FROM ($selecthome) AS p INNER JOIN  hitstorial_productos ht ON (ht.id_producto = p.id) ORDER BY ht.visitas DESC"; 
            $join= "JOIN  (select *, sum(visitas) as total_visitas from hitstorial_productos group by id_producto) ht ON (ht.id_producto = p.id)"; 
            if($filtro_ordenamiento==1){
                $order.= ",total_visitas DESC";
            }else{
                $order = str_replace("fecha_creacion","total_visitas",$order);
            }
            $select_masrelevantes = ", ht.total_visitas";
        }

        //fin nuevos campos 
        parent::conectar();
        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT ps.*, 
                    p.producto,
                    p.marca,
                    p.modelo,
                    p.titulo,
                    p.descripcion,
                    p.foto_portada,
                    p.tipoSubasta, 
                    p.oferta, 
                    p.porcentaje_oferta, 
                    p.exposicion, 
                    p.precio AS precioProducto, 
                    p.moneda_local, 
                    p.pais, 
                    p.genero,
                    p.tiempo_estimado_envio_num,
                    p.tiempo_estimado_envio_unidad,
                    p.garantia,
                    $campo_porcentaje_subasta
                    pst.descripcion AS tipo_descripcion 
                    $select_precio
                    $subselect
                    $select_masrelevantes
                FROM productos_subastas ps
                INNER JOIN productos p ON ps.id_producto = p.id
                INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                $join
                JOIN (SELECT @row_number := 0) r 
                WHERE $where 
                $order
                $order_relevante
                )as datos 
            $order
            $order_relevante
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";

        // var_dump($selecthome);
        $selectxhomexvaluexmax =
        "SELECT *, (@row_number:=@row_number+1) AS num FROM(
            SELECT 
                MAX(p.precio) AS MAX_PRICE_PRODUCTO,
                AVG(p.precio) AS MAX_PRICE_PRODUCTO_PROM,
                p.moneda_local AS MAX_PRICE_PRODUCTO_MONEDA
            FROM productos_subastas ps
            INNER JOIN productos p ON ps.id_producto = p.id
            INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
            JOIN (SELECT @row_number := 0) r 
            WHERE $where 
            )as datos";

      //   var_dump($selecthome); 
        $productos = parent::consultaTodo($selecthome);


        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron subastas productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }
        
        $select_home_value_max        = parent::consultaTodo($selectxhomexvaluexmax);
        $productos_precio_max         = 0;
        $productos_precio_max_mask    = "";

        $productos_precio_prom         = 0;
        $productos_precio_prom_mask    = "";
        
        $productos_precio_max_symbol  = "";

        if( count( $select_home_value_max ) > 0 ){
            $productos_precio_prom        = floatval($this->truncNumber($select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO_PROM"], 2));
            $productos_precio_prom_mask   = $this->maskNumber($productos_precio_prom, 2);

            $productos_precio_max        = floatval($this->truncNumber($select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO"], 2));
            $productos_precio_max_mask   = $this->maskNumber($productos_precio_max, 2);

            $productos_precio_max_symbol = $select_home_value_max[ 0 ]["MAX_PRICE_PRODUCTO_MONEDA"];
        }

        $productos = $this->mapProductosSubastas($productos, $data);
        if(isset($productos[0]["tipoSubasta"]) && isset($data["ordenamiento"]) && $data["ordenamiento"] != ""){
            $tipo_ordenamiento = SORT_ASC;
            if(isset($data["ordenamiento"]) && $data["ordenamiento"] == "DESC"){
                $tipo_ordenamiento = SORT_DESC;
            }
            $key_ordenar = array_column($productos, "precio_local_user"); // SUBASTA PREMIUM
            if(intval($productos[0]["tipoSubasta"]) == 6){
                $key_ordenar = array_column($productos, "precio_subasta_local_user"); // SUBASTA NORMAL
            } 
            array_multisort($key_ordenar, $tipo_ordenamiento, $productos);
        }

        $selecttodos = "SELECT COUNT(ps.id) AS contar 
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id 
        $join
        WHERE $where;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        
        return array(
            'status'                 => 'success',
            'message'                => 'subastas productos',
            'pagina'                 => $pagina,
            'total_paginas'          => $totalpaginas,
            'productos'              => count($productos),
            'total_productos'        => $todoslosproductos,
            'data'                   => $productos,
            
            'price_max_value'             => $productos_precio_max,
            'price_max_value_mask'        => $productos_precio_max_mask,

            'price_max_value_prom'        => $productos_precio_prom,
            'price_max_value_prom_mask'   => $productos_precio_prom_mask,

            'price_max_value_symbol'      => $productos_precio_max_symbol
        );
    }


    public function listar_empresa_oficial_filtro(Array $data)
    {
        if(!isset($data['pais']) && !isset($data['departamento'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $select_precio = $this->selectMonedaLocalUser($data);

        $pagina = floatval($data['pagina']);
        $numpagina = 10;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $where = " p.estado != 0 AND p.estado != 2 AND p.estado < 4 ";
        $order = "ORDER BY fecha_creacion DESC";
        $pais = null;
        $subselect = "";
        if($data['tipo'] == 1){ // Nasbi normales
            $where .= " AND (ps.estado = 1 OR ps.estado = 2) AND ps.tipo = 6";
            $order .= ", exposicion DESC";
        }
        if($data['tipo'] == 2){ // Nasbi premium
            $where .= " AND (ps.estado = 1 OR ps.estado = 2) AND ps.tipo <> 6";
            $order .= ", exposicion DESC";
        }
        if(isset($data['exposicion']) && !empty($data['exposicion'])){ // destacado
            $data['exposicion'] = addslashes($data['exposicion']);
            $where .= " AND exposicion = '$data[exposicion]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['condicion_producto']) && !empty($data['condicion_producto'])){ // nuevo, usado, remanofacturado
            $data['condicion_producto'] = addslashes($data['condicion_producto']);
            $where .= " AND condicion_producto = '$data[condicion_producto]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['garantia']) && $data['garantia'] != ''){ // si tiene garantia o no
            $data['garantia'] = addslashes($data['garantia']);
            $where .= " AND garantia = '$data[garantia]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['oferta'])  && $data['oferta'] != ''){ // si tiene oferta o no
            $data['oferta'] = addslashes($data['oferta']);
            $where .= " AND oferta = '$data[oferta]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && (!isset($data['departamento']) || empty($data['departamento']))){ // pais y no departamento
            $pais = addslashes($data['pais']);
            $where .= " AND p.pais = '$pais'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['pais']) && !empty($data['pais']) && isset($data['departamento']) && !empty($data['departamento'])){ // pais y departamento
            $pais = addslashes($data['pais']);
            $departamento = addslashes($data['departamento']);
            $where .= " AND p.pais = '$pais' AND departamento = $departamento";
            $order .= ", exposicion DESC";
        }
        if(isset($data['producto_nombre']) && !empty($data['producto_nombre'])){// busqueda producto nombre
            $data['producto_nombre'] = addslashes($data['producto_nombre']);
            $where .= " AND MATCH(keywords) AGAINST('$data[producto_nombre]' IN NATURAL LANGUAGE MODE)";
            $order .= ", exposicion DESC";
        }
        if(isset($data['empresa']) && !empty($data['empresa'])){ // productos de una empresa
            $data['empresa'] = addslashes($data['empresa']);
            $where .= " AND empresa = '1' AND uid = '$data[empresa]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['categoria']) && !empty($data['categoria'])){ // si tiene categoria o no
            $data['categoria'] = addslashes($data['categoria']);
            $where .= " AND categoria = '$data[categoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['subcategoria']) && !empty($data['subcategoria'])){ // si tiene categoria o no
            $data['subcategoria'] = addslashes($data['subcategoria']);
            $where .= " AND subcategoria = '$data[subcategoria]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['envio']) && !empty($data['envio'])){ // tipos de envio
            $data['envio'] = addslashes($data['envio']);
            $where .= " AND envio = '$data[envio]'";
            $order .= ", exposicion DESC";
        }
        if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])){ // ordenar precio
            $data['ordenamiento'] = addslashes($data['ordenamiento']);
            $order = "ORDER BY precio_usd $data[ordenamiento]";
            //if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC"; // mas vendidos
        }
        // if(isset($data['mas_vendidos']) && !empty($data['mas_vendidos'])){ // mas vendidos
        //     // $data['envio'] = addslashes($data['envio']);
        //     // $where .= " AND cantidad_vendidas > '0'";
        //     $order = "ORDER BY cantidad_vendidas DESC";
        //     // if(isset($data['ordenamiento']) && !empty($data['ordenamiento'])) $order = "ORDER BY cantidad_vendidas DESC, precio_usd $data[ordenamiento], ultima_venta DESC, exposicion DESC";// ordenar precio
        // }

        //nuevos campos 

        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor'])  && $data['tipo'] == 2 ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) >=  '$data[minimo_valor]' AND  (p.precio-(p.precio*(p.porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && isset($data['maximo_valor']) && !empty($data['maximo_valor'])  && $data['tipo'] == 1){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta >=  '$data[minimo_valor]' AND  $valor_subasta_sin_porcentaje_subasta <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }

        //solo minimo 


        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) && $data['tipo'] == 2  ){ // minimo valor & valor mayor 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) >=  '$data[minimo_valor]'";
            $order .= ", precioProducto DESC";
        }

        if(isset($data['minimo_valor']) && !empty($data['minimo_valor']) && empty($data['maximo_valor']) && $data['tipo'] == 1  ){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 
            $data['minimo_valor'] = intval($data['minimo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta  >=  '$data[minimo_valor]'";
            $order .= ", precioProducto DESC";
        }
        // fin solo minimo 
        //solo maximo

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor']) && $data['tipo'] == 2 ){ // minimo valor & valor mayor 
             $data['maximo_valor'] = intval($data['maximo_valor']);
             $where .= " AND (p.precio-(p.precio*(p.porcentaje_oferta/100))) <=  '$data[maximo_valor]'";
             $order .= ", precioProducto DESC";
         }
 

        if(isset($data['maximo_valor']) && !empty($data['maximo_valor']) && empty($data['minimo_valor']) && $data['tipo'] == 1 ){ // minimo valor & valor mayor 
            $condicion_inscritos= "IF(ps.inscritos < 15, 15, IF(ps.inscritos>35, 35, ps.inscritos))"; 
            $porcentaje_subasta="((($condicion_inscritos * $this->entradaxxubastasxnormales)-$this->porcentajexrestarxnormal)*100)"; 
            $campo_porcentaje_subasta="$porcentaje_subasta AS porcentaje_subasta_campo,"; 
            $valor_subasta_sin_porcentaje_subasta= "(p.precio-(p.precio*$porcentaje_subasta/100))"; 

            $data['maximo_valor'] = intval($data['maximo_valor']);
            $where .= " AND $valor_subasta_sin_porcentaje_subasta <=  '$data[maximo_valor]'";
            $order .= ", precioProducto DESC";
        }

          // fin solo maximo

        if(isset($data['genero']) && !empty($data['genero'])){ // genero
            $data['genero'] = intval($data['genero']);
            $data['genero'] = addslashes($data['genero']);
            $where .= " AND genero = '$data[genero]'";
            $order .= ", genero DESC";
        }

        if(isset($data['mas_relevante']) && !empty($data['mas_relevante']) && $data['mas_relevante']== 1){ // para que muestre del que tiene mas visitas a menos los que no muestra es porque no tiene 
            $join= "JOIN  (select *, sum(visitas) as total_visitas from hitstorial_productos group by id_producto) ht ON (ht.id_producto = p.id)"; 
            // if($filtro_ordenamiento==1){
            //     $order.= ",total_visitas DESC";
            // }else{
            //     $order = str_replace("fecha_creacion","total_visitas",$order);
            // }
            // $select_masrelevantes = ", ht.total_visitas";
           }

        //fin nuevos campos 

        parent::conectar();
        $selecthome = "
                SELECT DISTINCT e.*
                FROM productos_subastas ps
                INNER JOIN productos p ON ps.id_producto = p.id
                INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                JOIN empresas e ON (p.uid= e.id)
                WHERE $where AND p.empresa=1 AND (e.pais = '$pais' AND e.estado = 1) AND (3 < (SELECT COUNT(p.uid) FROM productos p WHERE p.uid = e.id AND p.empresa = 1)) AND (e.idioma IS NOT NULL AND e.cargo IS NOT NULL AND e.nit IS NOT NULL AND e.foto_asesor IS NOT NULL AND e.descripcion IS NOT NULL AND e.pais IS NOT NULL AND e.tipo_empresa IS NOT NULL AND e.nombre_empresa IS NOT NULL AND e.telefono IS NOT NULL AND e.razon_social IS NOT NULL AND e.nombre_dueno IS NOT NULL AND e.foto_logo_empresa IS NOT NULL AND e.foto_portada_empresa IS NOT NULL)
        ";
        //    var_dump($selecthome); 
    
   
      $empresas = parent::consultaTodo($selecthome);
      if(count($empresas) <= 0){
          parent::cerrar();
          return array('status' => 'fail', 'message'=> 'no se encontraron empresas', 'data' => null);
      }

       parent::cerrar();
       $empresas = $this->mapEmpresaOficial($empresas);
      return array(
          'status' => 'success',
          'message'=> 'empresas oficiales',
          'data' => $empresas
      );

    }


    function mapEmpresaOficial(Array $empresas)
    {
        $empresas_oficiales=[]; 

        foreach ($empresas as $x => $empresa) {
            $empresa_aux=[]; 

            if ( $empresa['descripcion'] == "...") {
                $empresa_aux['descripcion'] = "";
            }
            if ( $empresa['nombre_empresa'] == "...") {
                $empresa_aux['nombre_empresa'] = $empresa['razon_social'];
            }
        
           
            if ( $empresa['foto_logo_empresa'] == "...") {
                $empresa_aux['foto_logo_empresa'] = "";
            }
            if ( $empresa['foto_portada_empresa'] == "...") {
                $empresa_aux['foto_portada_empresa'] = "";
            }
        
            $empresa_aux['id'] = floatval($empresa['id']);
            $empresa_aux['empresa'] = 1;
            $empresa_aux['pais'] = floatval($empresa['pais']);
            $empresa_aux['nombre'] = $empresa['nombre_empresa'];
            $empresa_aux['nombreCompleto'] = $empresa['nombre_empresa'];
            //$empresas[$x] = $empresa;
            array_push($empresas_oficiales, $empresa_aux );
        }

        return $empresas_oficiales;
    }
  

    //fin filtro nuevo subasta 


    function insertar_historial_vista_subasta(Array $data)
    {
        if(!isset($data['uid']) || !isset($data['empresa']) || !isset($data['id'])  ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $insertarxpqr = "INSERT INTO hitstorial_productos
        (
            uid,
            empresa,
            id_producto,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            visitas
        )
        VALUES
        (
            '$data[uid]',
            '$data[empresa]',
            '$data[id]',
            '1',
            '$fecha',
            '$fecha',
            '1'
        );";
        $insertar = parent::query($insertarxpqr, false);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'guardado en el historial', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'pregunta realizada', 'data' => $insertar);
    }

}
