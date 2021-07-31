<?php
require 'nasbifunciones.php';
class MisSubastas extends Conexion
{

    // private $tiempomaxsubasta = 9500000 * 10; // tiempo durante la subasta 1 hora * 10
    private $tiempomaxsubasta = 45000; // tiempo durante la subasta 45 segundos -  Es este.
    private $entradaxxubastasxnormales = 0.029; // porcentaje entrada subastas normales
    private $porcentajexrestarxnormal = 0.1; // porcentaje entrada subastas normales
    private $porcentajexsugeridoxpujar = 0.1; //porcentaje sugerido segunda puja en adelante
    private $porcentajebloqueo = [
        'Nasbigold' => 0.0002,
        'Nasbiblue' => 0.02
    ];
    public function vermisSubastas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['historial']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;


        $tipo = "";
        if(isset($data['tipo'])) $tipo = "AND ps.tipo = '$data[tipo]'";

        $historial = "AND ps.estado < 4";
        $subselect = "";
        if($data['historial'] == 1){
            $subselect = ", (SELECT psp.uid FROM productos_subastas_pujas psp WHERE psp.id_subasta = ps.id ORDER BY fecha_actualizacion DESC LIMIT 1) AS uid_ganador";
            $historial = "AND ps.estado = 4";
        }


        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        parent::conectar();
        $selecxsubastas = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT 
                psi.*, 
                ps.id_producto,
                ps.estado AS estado_subasta,
                ps.inscritos,
                ps.apostadores,
                ps.tipo,
                ps.moneda,
                ps.precio,
                ps.fecha_inicio,
                pst.costo_ticket_usd,
                pst.descripcion AS tipo_descripcion,
                p.producto,
                p.descripcion,
                p.foto_portada,
                pse.descripcion AS estado_descripcion,
                p.precio_usd,
                p.envio,
            

                p.titulo,
                p.oferta,
                p.precio AS precio_local,
                p.porcentaje_oferta,
                p.moneda_local

                $subselect
                FROM productos_subastas_inscritos psi
                INNER JOIN productos_subastas ps ON psi.id_subasta = ps.id
                INNER JOIN productos p ON ps.id_producto = p.id
                INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                INNER JOIN productos_subastas_estado pse ON ps.estado = pse.id
                JOIN (SELECT @row_number := 0) r
                WHERE psi.uid = '$data[uid]' AND psi.empresa = '$data[empresa]' $tipo $historial
                ORDER BY fecha_creacion DESC
            )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        /* echo $selecxsubastas; */
        $subastas = parent::consultaTodo($selecxsubastas);
        if(!$subastas){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron ventas', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = "SELECT COUNT(psi.id) AS contar 
        FROM productos_subastas_inscritos psi
        INNER JOIN productos_subastas ps ON psi.id_subasta = ps.id
        WHERE psi.uid = '$data[uid]' AND psi.empresa = '$data[empresa]' $tipo $historial;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $subastas = $this->mapSubastas($subastas, false);        
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'mis subastas', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'subastas' => count($subastas), 'total_subastas' => $todoslosproductos, 'data' => $subastas);
    }

    public function verPuja(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $data['empresa'] = 0;

        $subasta = $this->subastaId($data);
        if($subasta['status'] != 'success') return $subasta;
        $subasta = $subasta['data'];

        if($subasta['estado'] == '1') return array(
            'status' => 'inscribiendo',
            'message' => 'faltan usuarios por inscribirse a la subasta',
            'sugerido' => 0,
            'sugerido_mask' => 0,
            'sugerido_usd' => 0,
            'sugerido_usd_mask' => 0,

            'sugerido_local' => 0,
            'sugerido_local_mask' => 0,

            'sugerido_cripto' => 0,
            'sugerido_cripto_mask' => 0,

            'precio_moneda' => 0,
            'precio_moneda_mask' => 0,
            'data' => null,
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );
        
        if($subasta['estado'] == '2'){
            $dineropuja = $this->dineroBloqueadoSubasta($data);
            if($dineropuja['status'] == 'fail') return $dineropuja;
            $dineropuja = $dineropuja['data'];
            $dineropuja['precio'] = $dineropuja['precio'] - $this->porcentajebloqueo[$dineropuja['moneda']];
            unset($dineropuja['id']);
            unset($dineropuja['id_transaccion']);
            unset($dineropuja['tipo_transaccion']);

            $suegerido = $this->calcularSugerido($subasta);
            $resultSugeridoActivoNasbi = array(
                'status' => 'porPostor',
                'message' => 'usuarios inscritos falta apostador',
                'sugerido' => $suegerido['monto'],
                'sugerido_mask' => $suegerido['monto_mask'],
                'sugerido_usd' => $suegerido['monto_usd'],
                'sugerido_usd_mask' => $suegerido['monto_usd_mask'],


                'sugerido_local' => $suegerido['monto_local'],
                'sugerido_local_mask' => $suegerido['monto_local_mask'],

                'sugerido_cripto' => $suegerido['monto_cripto'],
                'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],


                'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
                'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
                'dinero_puja' => $dineropuja,
                'data' => null,
                'estado_subasta' => $subasta['estado'],

                'extra_data' => $suegerido
            );
            parent::addLogSubastas("----+> #2) RESPONDIENDO AL FRON-END: " . json_encode($resultSugeridoActivoNasbi));
            return $resultSugeridoActivoNasbi;
        }

        if($subasta['estado'] == '3'){
            if($subasta['estado_inscrito'] == 1)  return array(
                'status' => 'bloquearAddress',
                'message' => 'usuario debe bloquear las direcciones para empezar a pujar',
                'sugerido' => 0,
                'sugerido_mask' => 0,
                'sugerido_usd' => 0,
                'sugerido_usd_mask' => 0,


                'sugerido_local' => 0,
                'sugerido_local_mask' => 0,

                'sugerido_cripto' => 0,
                'sugerido_cripto_mask' => 0,


                'precio_moneda' => 0,
                'precio_moneda_mask' => 0,
                'data' => null,
                'estado_subasta' => $subasta['estado'],
                'extra_data' => $suegerido
            );
            
            $allpujas = $this->pujaId($data, 10);
            if($allpujas['status'] == 'fail') return $subasta;

            $dineropuja = $this->dineroBloqueadoSubasta($data);
            if($dineropuja['status'] == 'fail') return $dineropuja;
            $dineropuja = $dineropuja['data'];
            $dineropuja['precio'] = $dineropuja['precio'] - $this->porcentajebloqueo[$dineropuja['moneda']];
            unset($dineropuja['id']);
            unset($dineropuja['id_transaccion']);
            unset($dineropuja['tipo_transaccion']);

            $allpujas = $allpujas['data'];
            $puja = $allpujas[0];

            $suegerido = $this->calcularSugeridoAvtivo($subasta, $puja);

            $puja['sugerido'] = $suegerido['monto'];
            $puja['sugerido_usd_mask'] = $suegerido['monto_mask'];
            
            $resultSugeridoActivoNasbi = array(
                'status' => 'success',
                'message' => 'subasta en curso - mensaje J.B',
                'sugerido' => $suegerido['monto'],
                'sugerido_mask' => $suegerido['monto_mask'],
                'sugerido_usd' => $suegerido['monto_usd'],
                'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

                
                'sugerido_local' => $suegerido['monto_local'],
                'sugerido_local_mask' => $suegerido['monto_local_mask'],

                'sugerido_cripto' => $suegerido['monto_cripto'],
                'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],


                'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
                'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
                'puja_actual' => $puja,
                'data' => $allpujas,
                'dinero_puja' => $dineropuja,
                'estado_subasta' => $subasta['estado'],

                'extra_data' => $suegerido
            );
            parent::addLogSubastas("----+> #3) RESPONDIENDO AL FRON-END: " . json_encode($resultSugeridoActivoNasbi));
            return $resultSugeridoActivoNasbi;
        }

        if($subasta['estado'] == '4') return array(
            'status' => 'pujaFinalizada',
            'message' => 'la puja ya tiene un ganador',
            'sugerido' => 0,
            'sugerido_mask' => 0,
            'sugerido_usd' => 0,
            'sugerido_usd_mask' => 0,

            'sugerido_local' => 0,
            'sugerido_local_mask' => 0,

            'sugerido_cripto' => 0,
            'sugerido_cripto_mask' => 0,

            'precio_moneda' => 0,
            'precio_moneda_mask' => 0,
            'data' => null,
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );

    }

    public function bloquearDirecciones(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['id']) || !isset($data['direcciones'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        //INICIO DE VERIFICAR SI YA SE HA BLOQUEADO LA BILLETERA PARA LA SUBASTA EN OTRO PROCEDIMIENTO
        $SQL = "SELECT * FROM nasbicoin_bloqueado_diferido WHERE id_transaccion = '$data[id]' AND uid = '$data[uid]'";
        parent::conectar();
        $res_SQL = parent::consultaTodo($SQL);
        parent::cerrar();

        if( count($res_SQL) > 0 ){
            return array('status' => 'errYaEstaBloqueada', 'message' => 'No se ha podido bloquear el saldo en este Nasbi descuento porque ya ha sido bloqueado.', 'data' => null);
        }
        //FIN DE VERIFICAR SI YA SE HA BLOQUEADO LA BILLETERA PARA LA SUBASTA EN OTRO PROCEDIMIENTO

        //INICIO DE VERIFICAR NUEVAMENTE SI AÚN EL SALDO NO SE HA BLOQUEADO EN OTRA SUBASTA
        $URL = "http://nasbi.peers2win.com/api/controllers/nasbicoin/?wallet_usuario";

        $dataSend = array( "data" => array("uid" => $data['uid'], "empresa" => $data['empresa'], "tipo" => $data['moneda_bloquear']));

        $res = array(json_decode( parent::remoteRequest($URL, $dataSend), true ));
        $res = current($res);

        if ($res["status"] == 'success') {
            if ( $data['moneda_bloquear'] == "Nasbigold" ) {//DE MOMENTO SOLO VALIDADO CON NASBIGOLD
                if( $res["nasbicoin_gold"]["monto"] <= 0 ) {
                    return array('status' => 'errSaldoCambio', 'message' => 'El saldo Nasbichips ha sido modificado en otra transacción y es cero', 'data' => null);
                }
            }else{
                if( $res["nasbicoin_blue"]["monto"] <= 0 ) {
                    return array('status' => 'errSaldoCambio', 'message' => 'El saldo Nasbiblue ha sido modificado en otra transacción y es cero', 'data' => null);
                }
            }
        } else {
            return array('status' => 'fail', 'message' => 'no se pudieron bloquear las direcciones', 'data' => null);
        }
        //FIN DE VERIFICAR NUEVAMENTE SI AÚN EL SALDO NO SE HA BLOQUEADO EN OTRA SUBASTA

        $data['empresa'] = 0;

        $subasta = $this->subastaId($data);
        if($subasta['status'] == 'fail') return $subasta;
        $subasta = $subasta['data'];

        if($subasta['estado_inscrito'] == 2) return array('status' => 'direccionesBloqueadas', 'message' => 'usuario ya tiene sus direcciones ya bloqueadas', 'data' => null);;

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        $bloqueadas = $this->bloquearDireccionesSubasta($data, $subasta);
        $validar = true;
        foreach ($bloqueadas as $x => $bloq) {
            if($bloq['status'] != 'success') $validar = false;
        }

        if($validar == false) return array('status' => 'fail', 'message' => 'no se pudieron bloquear las direcciones', 'data' => null);
        
        $inscrito = $this->actualizarInscrito([
            'id' => $subasta['id_inscrito'],
            'estado' => 2,
            'fecha' => $fecha
        ]);
        if($inscrito['status'] == 'fail') return $inscrito;

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $data['uid'],
            'empresa' => 0,
            'text' => 'Has bloqueado la cartera para poder participar en la subasta del producto '.$subasta['titulo'],
            'es' => 'Has bloqueado la cartera para poder participar en la subasta del producto '.$subasta['titulo'],
            'en' => 'At your ready, the auction for your '.$subasta['titulo'].' product has started!',
            'keyjson' => '',
            'url' => ''
        ]);
        unset($notificacion);
        
        $this->envio_correo_cuenta_bloqueada($subasta, $data );

        return array('status' => 'success', 'message' => 'direcciones bloqueadas', 'data' => null, 'comision'=> $this->porcentajebloqueo[$subasta['moneda']]);

    }
    
    public function pujar(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['id']) || !isset($data['monto'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $data['empresa'] = 0;

        $subasta = $this->subastaId($data);
        if($subasta['status'] == 'fail') return $subasta;
        $subasta = $subasta['data'];

        $data['monto'] = floatval($data['monto']);

        $suegerido = array();

        if($subasta['estado'] == '2') {
            $suegerido = $this->calcularSugerido($subasta);

            parent::conectar();
            parent::query("UPDATE buyinbig.productos_subastas SET inscritos = $subasta[inscritos] WHERE id = $subasta[id]);");
            parent::cerrar();
        }
        else{
            $allpujas = $this->pujaId($data, 10);
            if($allpujas['status'] == 'fail') return $allpujas;
            $allpujas = $allpujas['data'];
            
            $puja = $allpujas[0];

            $suegerido = $this->calcularSugeridoAvtivo($subasta, $puja);
        }
                                                    
        if($subasta['estado'] == '2' && $data['monto'] < $suegerido['monto']) {
            return array(
                'status' => 'errorMontoSugerido',
                'message' => 'el monto que desea apostar es menor al sugerido incial',

                'sugerido' => $suegerido['monto'],
                'sugerido_mask' => $suegerido['monto_mask'],
                'sugerido_usd' => $suegerido['monto_usd'],
                'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

                'sugerido_local' => $suegerido['monto_local'],
                'sugerido_local_mask' => $suegerido['monto_local_mask'],

                'sugerido_cripto' => $suegerido['monto_cripto'],
                'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],

                'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
                'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
                'puja_actual' => null,
                'uid_pujo' => $data['uid'],
                'estado_subasta' => $subasta['estado'],

                'extra_data' => $suegerido
            );
        }

        $puja['sugerido'] = $suegerido['monto'];
        $puja['sugerido_usd_mask'] = $suegerido['monto_mask'];

        if($subasta['estado'] == '3' && $data['monto'] <= $puja['monto']) return array(
            'status' => 'errorMontoPujar',
            'message' => 'el monto que desea apostar debe ser mayor al que va ganando',
            'sugerido' => $suegerido['monto'],
            'sugerido_mask' => $suegerido['monto_mask'],
            'sugerido_usd' => $suegerido['monto_usd'],
            'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

            'sugerido_local' => $suegerido['monto_local'],
            'sugerido_local_mask' => $suegerido['monto_local_mask'],

            'sugerido_cripto' => $suegerido['monto_cripto'],
            'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],

            'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
            'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
            'puja_actual' => $puja,
            'uid_pujo' => $data['uid'],
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );
        if($subasta['estado'] == '4') return array(
            'status' => 'pujaFinalizada',
            'message' => 'la puja ya tiene un ganador',
            'sugerido' => $suegerido['monto'],
            'sugerido_mask' => $suegerido['monto_mask'],
            'sugerido_usd' => $suegerido['monto_usd'],
            'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

            'sugerido_local' => $suegerido['monto_local'],
            'sugerido_local_mask' => $suegerido['monto_local_mask'],

            'sugerido_cripto' => $suegerido['monto_cripto'],
            'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],

            'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
            'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
            'puja_actual' => $puja,
            'uid_pujo' => $data['uid'],
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );

        $dineropuja = $this->dineroBloqueadoSubasta($data);
        if($dineropuja['status'] == 'fail') return $dineropuja;
        $dineropuja = $dineropuja['data'];
        $dineropuja['precio'] = $dineropuja['precio'] - $this->porcentajebloqueo[$dineropuja['moneda']];

        if($data['monto'] > $dineropuja['precio']) return array(
            'status' => 'montoErroneo',
            'message' => 'no puede pujar mas de lo que tienes bloqueados',
            'sugerido' => $suegerido['monto'],
            'sugerido_mask' => $suegerido['monto_mask'],
            'sugerido_usd' => $suegerido['monto_usd'],
            'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

            'sugerido_local' => $suegerido['monto_local'],
            'sugerido_local_mask' => $suegerido['monto_local_mask'],

            'sugerido_cripto' => $suegerido['monto_cripto'],
            'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],

            'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
            'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
            'puja_actual' => $puja,
            'uid_pujo' => $data['uid'],
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        return $this->insertarPuja($data, $subasta, $suegerido);

    }

    public function finalizarSubasta(Array $data)
    {   
        parent::addLogSubastas("=========================SUBASTA FINALIZADA=========================");
        if(!isset($data) || !isset($data['uid']) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $data['empresa'] = 0;
        
        $subasta = $this->subastaId($data);
        if($subasta['status'] == 'fail') return $subasta;
        $subasta = $subasta['data'];

        $allpujas = $this->pujaId($data, 1);
        if($allpujas['status'] == 'fail') return $subasta;
        $puja = $allpujas['data'][0];

        if ($puja['uid'] != $data['uid']) return array('status' => 'usuarioNoGanador', 'message' => 'el usuario enviado no es el ganador', 'data' => null);

        $dineropuja = $this->dineroBloqueadoSubasta($data);
        if($dineropuja['status'] == 'fail') return $dineropuja;
        $dineropuja = $dineropuja['data'];

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;


        // Finalizar subasta
        parent::addLogSubastas("==========SUBASTA ACTUALIZAR========= " . json_encode($subasta) );
        parent::addLogSubastas("==========FINALIZAR SUBASTA========== ");
        $estado = 4;
        $update = $this->actualizarEstadoSubasta([
            'id'              => $subasta['id'],
            'estado'          => $estado,
            'fecha'           => $data['fecha'],
            'mensaje_success' => 'subasta finalizada',
            'mensaje_fail'    => 'no finalizo la subasta'
        ]);
        
        if($update['status'] == 'fail') return $update;

        // quitar bloqueados
        $desbloquear = $this->quitarBloqueados($data);
        if($desbloquear['status'] != 'success') return $desbloquear;

        $subasta['fecha'] = $fecha;
        $carrito = $this->insertarCarrito($subasta, $puja, $dineropuja, $fecha);
        if($carrito['status'] != 'success') return $carrito;

        //para el correo
         $this->enviarcorreo_subasta_finalizada($data, $subasta, $dineropuja, $puja );
        //fin para el correo

        return array('status' => 'success', 'message' => 'subasta finalizada', 'data' => null, 'carritoInfo' => $carrito);
    }

    function subastaId(Array $data)
    {
        parent::conectar();
        $selectxsubasta = "
        SELECT 
            ps.*, 
            p.uid AS uid_producto,
            p.cantidad AS 'cantidad_producto',
            p.producto,
            p.marca,
            p.modelo,
            p.titulo,
            p.descripcion,
            p.empresa AS empresa_producto,
            p.precio_usd,

            p.envio AS tipo_envio,

            p.exposicion,
            p.categoria,
            p.subcategoria,

            p.porcentaje_tax,

            p.oferta,
            p.precio AS precio_local,
            p.porcentaje_oferta,
            p.moneda_local,
            
            p.id_direccion,

            psi.uid,
            psi.empresa,
            psi.estado AS estado_inscrito,
            psi.id AS id_inscrito,
            pst.costo_ticket_usd,


            ctg.porcentaje_gratuita,
            ctg.porcentaje_clasica,
            ctg.porcentaje_premium

        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id
        INNER JOIN categorias ctg ON(p.categoria = ctg.CategoryID)
        INNER JOIN productos_subastas_inscritos psi ON  ps.id = psi.id_subasta
        INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
        WHERE ps.id = '$data[id]' AND psi.uid = '$data[uid]'";
        $subasta = parent::consultaTodo($selectxsubasta);
        parent::cerrar();
        
        if(count($subasta) <= 0) return array('status' => 'fail', 'message' => 'faltan datos subasta', 'data' => null);
        
        $subasta = $this->mapProductosSubastas($subasta, true);
        $subasta = $subasta[0];

        parent::addLogSubastas("SUBASTA =======> ".json_encode($subasta));

        return array('status' => 'success', 'message' => 'data subasta', 'data' => $subasta);

    }

    public function calcularSugerido($subasta)
    {   
        // $montoTicket = $precioxentrada * $numxinscritos;
        // $valor = $precio_usd
        // $porcentajeTotal = ($montoTicket * 100) / $valor;
        // $porcentaje = (($subasta['costo_ticket_usd'] * $subasta['inscritos']) * 100) / $subasta['precio_usd'];

        if($subasta['tipo'] != 6){
            $porcentaje = 0;
            $monto_usd = 0;
            
            // $subasta['porcentaje_oferta'] = floatval( $subasta['porcentaje_oferta'] );
            // if ( $subasta['porcentaje_oferta'] >= 1 ) {
            //     $subasta['porcentaje_oferta'] = $subasta['porcentaje_oferta'] / 100;
            // }

            // if ( intval( $subasta['oferta'] ) == 1) {
            //     $monto_usd = $subasta['precio_usd'] - ( $subasta['precio_usd']  * $subasta['porcentaje_oferta']);
            // }else{
                // $monto_usd = $subasta['precio_usd'] - ($subasta['precio_usd']   * $porcentaje);
            // }
            // $porcentaje = ($subasta['inscritos'] * $subasta['costo_ticket_usd']) / $monto_usd;

            // if ($porcentaje >= 0.4 &&  $porcentaje < 0.5) return $this->porcentajeProducto($subasta, 0.1);
            // if ($porcentaje >= 0.5 &&  $porcentaje < 0.6) return $this->porcentajeProducto($subasta, 0.2);
            // if ($porcentaje >= 0.6 &&  $porcentaje < 0.7) return $this->porcentajeProducto($subasta, 0.3);
            // if ($porcentaje >= 0.7 &&  $porcentaje < 0.8) return $this->porcentajeProducto($subasta, 0.4);
            // if ($porcentaje >= 0.8 &&  $porcentaje < 9) return $this->porcentajeProducto($subasta, 0.5);
            // if ($porcentaje >= 0.9) return $this->porcentajeProducto($subasta, 0.2);

            parent::conectar();
            $selectednumber = parent::consultaTodo("SELECT * FROM productos_subastas_tipo pst WHERE $subasta[precio_usd] BETWEEN rango_inferior_usd AND rango_superior_usd");
            parent::cerrar();

            $porcentaje  = 0;
            $tope_maximo = true;

            for ($i = 1; $i <= 6; $i++) {

                $index = $i * 10;
                $index = $index / 100;

                # SOLO PARA SUBASTAS CON TIQUETES. Hallar minimo de apostadores.
                # Paso 1: Hallar cuanto se debe "RECAUDAR ENENTRDAS"
                $recaudar_entradas = ($subasta['precio_usd'] / 0.77) - ($subasta['precio_usd'] - ($subasta['precio_usd'] * $index));
                #Paso 2: Hallar cuanto cuesta en dolares 1 tiquete de la subasta.
                $productos_subastas_tipo = $selectednumber[0];
                $apostadores             = $recaudar_entradas / floatval($productos_subastas_tipo['costo_ticket_usd']);
                $apostadores             = round($apostadores);

                if ($subasta['inscritos'] >= $apostadores) {
                    $porcentaje          = $index;
                }else{
                    $tope_maximo = false;
                    parent::addLogSubastas("-----+> 0. SUGERIDO | id: " . $subasta['id']);
                    parent::addLogSubastas("-----+> 1. SUGERIDO | precio_usd: " . $subasta['precio_usd']);
                    parent::addLogSubastas("-----+> 2. SUGERIDO | inscritos: " . $subasta['inscritos']);
                    parent::addLogSubastas("-----+> 3. SUGERIDO | recaudar_entradas: " . $recaudar_entradas);
                    parent::addLogSubastas("-----+> 4. SUGERIDO | apostadores: " . $apostadores);
                    parent::addLogSubastas("-----+> 5. SUGERIDO | porcentaje: " . $porcentaje);

                    return $this->porcentajeProducto($subasta, $porcentaje);
                } 
            }

            if( $tope_maximo) {
                return $this->porcentajeProducto($subasta, $porcentaje);
            }
            
        }else{
            $inscritos = $subasta['inscritos'];
            if ($inscritos > 35) $inscritos = 35;
            $porcentaje = ($inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal;
            return $this->porcentajeProducto($subasta, $porcentaje);
        }
        
    }

    function porcentajeProducto(Array $subasta, Float $porcentaje)
    {
        $monto_usd        = 0;
        $monto_localmoney = 0;

        if ( intval( $subasta['oferta'] ) == 1) {
            $monto_usd        = floatval($subasta['precio_usd'])   - ( floatval($subasta['precio_usd'])   * floatval($subasta['porcentaje_oferta']/100));
            $monto_usd        = floatval($monto_usd)               - ( floatval($monto_usd)               * floatval($porcentaje));

            $monto_localmoney = floatval($subasta['precio_local']) - ( floatval($subasta['precio_local']) * floatval($subasta['porcentaje_oferta']/100));
            $monto_localmoney = $monto_localmoney       - ( $monto_localmoney        * floatval($porcentaje));
            
        }else{
            $monto_usd        = floatval($subasta['precio_usd'])   - (floatval($subasta['precio_usd'])    * floatval($porcentaje));
            $monto_localmoney = floatval($subasta['precio_local']) - (floatval($subasta['precio_local'])  * floatval($porcentaje));
        }

        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        $monto = floatval($monto_usd / $precios[ $subasta['moneda'] ]);

        unset($nodo);
        $monto = floatval($this->truncNumber($monto, 6));
        $monto_mask = $this->maskNumber($monto, 2);

        $monto_usd = floatval($this->truncNumber($monto_usd, 2));
        $monto_usd_mask = $this->maskNumber($monto_usd, 2);

        $monto_local = floatval($this->truncNumber($monto_localmoney, 2));
        $monto_local_mask = $this->maskNumber($monto_localmoney, 2);

        parent::addLogSubastas("-----+> 6. SUGERIDO | porcentaje: " . $porcentaje);
        
        $resultSugeridoNasbi = array(
            'monto' => $monto_local, // Este sugerido debe ser en moneda local. Agregar variables para equivalencia en COIN.
            'monto_mask' => $monto_local_mask, // Este sugerido debe ser en moneda local. Agregar variables para equivalencia en COIN.

            'monto_usd' => $monto_usd,
            'monto_usd_mask' => $monto_usd_mask,

            'monto_local' => $monto_local,
            'monto_local_mask' => $monto_local_mask,
            'monto_local_symbol' => $subasta['moneda_local'],

            'monto_cripto' => $monto,
            'monto_cripto_mask' => $monto_mask,

            'precio_moneda_actual_usd' => $this->truncNumber($precios[$subasta['moneda']], 2),
            'precio_moneda_actual_usd_mask' => $this->maskNumber($precios[$subasta['moneda']], 2)

        );
        
        parent::addLogSubastas("-----+> 7. SUGERIDO | resultSugeridoNasbi: " . json_encode($resultSugeridoNasbi));
        return $resultSugeridoNasbi;
    }

    function calcularSugeridoAvtivo(Array $subasta, Array $puja)
    {
        $puja['monto'] = floatval( $puja['monto'] );

        $result        = $puja['monto'] + ( $puja['monto'] * $this->porcentajexsugeridoxpujar); // Debe estar en la moneda local del producto.
        $suegerido     = $this->truncNumber( $result, 2 );
        $suegeridomask = $this->maskNumber( $suegerido, 2 );

        // $suegerido = floatval($this->truncNumber(floatval($puja['monto']) + (floatval($puja['monto'] * $this->porcentajexsugeridoxpujar)), 6));
        // $suegeridomask = $this->maskNumber($suegerido, 2);

        // Calculamos el sugerido de LOCAL MONEY -> USD
        $precio_divisa_en_usd = $subasta['precio_local'] / $subasta['precio_usd'];

        $suegerido_usd_1    = ($suegerido / $precio_divisa_en_usd);
        $suegerido_usd_1    = floatval($this->truncNumber($suegerido_usd_1, 2));
        $suegerido_usd_1_mask = $this->maskNumber($suegerido_usd_1, 2);

        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        // Calculamos el sugerido de USD -> CRIPTO
        $sugerido_coin      = $suegerido_usd_1 / $precios[ $subasta['moneda'] ];
        $sugerido_coin      = $this->truncNumber($sugerido_coin, 6);
        $sugerido_coin_mask = $this->maskNumber($sugerido_coin, 6);



        // $suegerido_usd = floatval($suegerido * $precios[ $subasta['moneda'] ]);
        unset($nodo);
        // $suegerido_usd = floatval($this->truncNumber($suegerido_usd, 2));
        // $suegeridomask_mask = $this->maskNumber($suegerido_usd, 2);

        return [
            'monto' => $suegerido, // Este sugerido debe ser en moneda local. Agregar variables para equivalencia en COIN.
            'monto_mask' => $suegeridomask, // Este sugerido debe ser en moneda local. Agregar variables para equivalencia en COIN.

            'monto_usd' => $suegerido_usd_1, //$suegerido_usd,
            'monto_usd_mask' => $suegerido_usd_1_mask, //$suegeridomask_mask,

            'monto_local' => $suegerido,
            'monto_local_mask' => $suegeridomask,
            'monto_local_symbol' => $subasta['moneda_local'],

            'monto_cripto' => $sugerido_coin,
            'monto_cripto_mask' => $sugerido_coin_mask,


            'precio_moneda_actual_usd' => $this->truncNumber($precios[$subasta['moneda']], 2),
            'precio_moneda_actual_usd_mask' => $this->maskNumber($precios[$subasta['moneda']], 2)
        ];
    }

    function mapProductosSubastas($productos){
        foreach ($productos as $x => $producto) {
            $producto['id'] = floatval($producto['id']);
            $producto['uid'] = floatval($producto['uid']);
            $producto['empresa'] = floatval($producto['empresa']);
            $producto['empresa_producto'] = floatval($producto['empresa_producto']);
            $producto['uid_producto'] = floatval($producto['uid_producto']);
            $producto['id_producto'] = floatval($producto['id_producto']);
            $producto['precio'] = floatval($producto['precio']);
            $producto['precio_mask'] = $this->maskNumber($producto['precio'], 2);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask'] = $this->maskNumber($producto['precio_usd'], 2);

            $producto['cantidad'] = floatval($producto['cantidad']);

            $producto['tipo'] = floatval($producto['tipo']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['apostadores'] = floatval($producto['apostadores']);
            $producto['inscritos'] = floatval($producto['inscritos']);
            $producto['estado_inscrito'] = floatval($producto['estado_inscrito']);
            $producto['id_inscrito'] = floatval($producto['id_inscrito']);
            $producto['fecha_fin'] = floatval($producto['fecha_fin']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);

            $producto['exposicion']           = intval( $producto['exposicion']   );
            $producto['categoria']            = intval( $producto['categoria']    );
            $producto['subcategoria']         = intval( $producto['subcategoria'] );
            $producto['comisiones_categoria'] = 0;

            if( $producto['exposicion'] == 1 ){
                $producto['comisiones_categoria'] = floatval( $producto['porcentaje_gratuita'] );

            }else if( $producto['exposicion'] == 2 ){
                $producto['comisiones_categoria'] = floatval( $producto['porcentaje_clasica'] );

            }else if( $producto['exposicion'] == 3 ){
                $producto['comisiones_categoria'] = floatval( $producto['porcentaje_premium'] );

            }


            // inicio nuevo codigo 
            parent::conectar();
            
            $selectxcoloresxtallas = 
            "SELECT * FROM detalle_producto_colores_tallas WHERE id_producto = $producto[id];";
            
            $detalle_producto_colores_tallas = parent::consultaTodo($selectxcoloresxtallas);
            $producto['detalle_producto_colores_tallas'] = $detalle_producto_colores_tallas;

            parent::cerrar();


            $productos[$x] = $producto;
        }
        return $productos;
    }

    function pujaId(Array $data, Int $limit)
    {
        parent::conectar();
        $selectxpuja = 
        "SELECT psp.*, u.username
        FROM productos_subastas_pujas psp
        LEFT JOIN peer2win.usuarios u ON psp.uid = u.id
        WHERE psp.id_subasta = '$data[id]'
        ORDER BY fecha_creacion DESC
        LIMIT $limit";
        $puja = parent::consultaTodo($selectxpuja);
        parent::cerrar();
        
        if(count($puja) <= 0) return array('status' => 'fail', 'message' => 'puja no data', 'data' => null);
        
        $puja = $this->mapPuja($puja, true);
        return array('status' => 'success', 'message' => 'puja', 'data' => $puja);
    }

    function mapPuja(Array $pujas)
    {
        foreach ($pujas as $x => $puja) {
            $puja['id'] = floatval($puja['id']);
            $puja['uid'] = floatval($puja['uid']);
            $puja['id_subasta'] = floatval($puja['id_subasta']);
            $puja['monto'] = floatval($puja['monto']);
            $puja['monto_mask'] = $this->maskNumber($puja['monto'], 2);
            $puja['fecha_creacion'] = floatval($puja['fecha_creacion']);
            $puja['fecha_actualizacion'] = floatval($puja['fecha_actualizacion']);
            $pujas[$x] = $puja;
        }
        return $pujas;
    }

    function insertarPuja(Array $data, Array $subasta, Array $sugerido )
    {
        $fecha_fin = $data['fecha'] + $this->tiempomaxsubasta;;
        parent::conectar();
        $insertarxpuja = "INSERT INTO productos_subastas_pujas
        (
            uid,
            id_subasta,
            
            monto,
            
            moneda_local_simbol,
            monto_usd,
            monto_cripto,

            fecha_creacion,
            fecha_actualizacion,
            fecha_final
        )
        VALUES
        (
            '$data[uid]',
            '$data[id]',
            '$data[monto]',

            '$subasta[moneda_local]',
            '$sugerido[monto_usd]',
            '$sugerido[monto_cripto]',

            '$data[fecha]',
            '$data[fecha]',
            '$fecha_fin'
        );";
        $insertar = parent::queryRegistro($insertarxpuja);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message' => 'error insertar subasta', 'data' => null);

        if($subasta['estado'] == 2){
            $estado = 3;
            $this->actualizarEstadoSubasta([
                'id' => $subasta['id'],
                'estado' => $estado,
                'fecha' => $data['fecha'],
                'mensaje_success' => 'subasta activa',
                'mensaje_fail' => 'no se activo la subasta'
            ]);
        }

        $allpujas = $this->pujaId($data, 10);
        if($allpujas['status'] == 'fail') return $allpujas;
        $allpujas = $allpujas['data'];
        
        $puja = $allpujas[0];
        $suegerido = $this->calcularSugeridoAvtivo($subasta, $puja);

        if($subasta['estado'] == 2){
            $subasta['estado'] = 3;
            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $subasta['uid_producto'],
                'empresa' => $subasta['empresa_producto'],
                'text' => 'En sus marcas listos, ¡Ya ha comenzado la subasta de tu producto '.$subasta['titulo'],
                'es' => 'En sus marcas listos, ¡Ya ha comenzado la subasta de tu producto '.$subasta['titulo'],
                'en' => 'At your ready, the auction for your '.$subasta['titulo'].' product has started!',
                'keyjson' => '',
                'url' => ''
            ]);
            unset($notificacion);
        }

        $puja['sugerido'] = $suegerido['monto'];
        $puja['sugerido_usd_mask'] = $suegerido['monto_mask'];
        
        return array(
            'status' => 'success',
            'message' => 'subasta en curso',
            'sugerido' => $suegerido['monto'],
            'sugerido_mask' => $suegerido['monto_mask'],
            'sugerido_usd' => $suegerido['monto_usd'],
            'sugerido_usd_mask' => $suegerido['monto_usd_mask'],

            'sugerido_local' => $suegerido['monto_local'],
            'sugerido_local_mask' => $suegerido['monto_local_mask'],

            'sugerido_cripto' => $suegerido['monto_cripto'],
            'sugerido_cripto_mask' => $suegerido['monto_cripto_mask'],


            'precio_moneda' => $suegerido['precio_moneda_actual_usd'],
            'precio_moneda_mask' => $suegerido['precio_moneda_actual_usd_mask'],
            'puja_actual' => $puja,
            'uid_pujo' => $data['uid'],
            'estado_subasta' => $subasta['estado'],

            'extra_data' => $suegerido
        );
    }

    function dineroBloqueadoSubasta(Array $data)
    {
        parent::conectar();
        $selectxbloqueado = "SELECT ncb.*
        FROM nasbicoin_bloqueado_diferido ncb
        WHERE ncb.id_transaccion = '$data[id]' AND ncb.uid = '$data[uid]' AND ncb.empresa = '$data[empresa]' AND ncb.tipo_transaccion = '2' AND accion = '1'";
        // echo $selectxbloqueado;
        $dinerobloqueado = parent::consultaTodo($selectxbloqueado);
        parent::cerrar();
        
        if(count($dinerobloqueado) <= 0) return array('status' => 'fail', 'message' => 'no tiene dinero bloqueado', 'data' => null);
        
        $dinerobloqueado = $this->mapBloquadoSubastas($dinerobloqueado, true);
        $dinerobloqueado = $dinerobloqueado[0];
        parent::addLogSubastas("DINERO BLOQUEADO =====>> ".json_encode($dinerobloqueado));
        return array('status' => 'success', 'message' => 'data dinero bloqueado', 'data' => $dinerobloqueado);
    }

    function mapBloquadoSubastas(Array $bloqueados)
    {
        foreach ($bloqueados as $x => $bloq) {
            $bloq['id'] = floatval($bloq['id']);
            $bloq['uid'] = floatval($bloq['uid']);
            $bloq['empresa'] = floatval($bloq['empresa']);
            $bloq['id_transaccion'] = floatval($bloq['id_transaccion']);
            $bloq['tipo_transaccion'] = floatval($bloq['tipo_transaccion']);
            $bloq['precio'] = floatval($bloq['precio']);
            $bloq['precio_mask'] = $this->maskNumber($bloq['precio'], 2);
            $bloq['precio_momento_usd'] = floatval($bloq['precio_momento_usd']);
            $bloq['precio_momento_usd_mask'] = $this->maskNumber($bloq['precio_momento_usd'], 2);
            $bloq['precio_usd'] = $this->truncNumber(floatval($bloq['precio']*$bloq['precio_momento_usd']), 2);
            $bloq['precio_usd_mask'] =  $this->maskNumber($bloq['precio_usd'], 2);
            $bloq['tipo'] = floatval($bloq['tipo']);
            $bloq['accion'] = floatval($bloq['accion']);
            $bloq['fecha_creacion'] = floatval($bloq['fecha_creacion']);
            $bloq['fecha_actualizacion'] = floatval($bloq['fecha_actualizacion']);
            $bloqueados[$x] = $bloq;
        }
        return $bloqueados;
    }

    function actualizarEstadoSubasta(Array $data)
    {
        // $adicional = "";
        parent::conectar();
        $updatexsubastas = "UPDATE productos_subastas
        SET
            estado = '$data[estado]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]'";
        parent::addLogSubastas("SQL actualizar =>>". $updatexsubastas );
        $updatesubastas = parent::query($updatexsubastas);
        parent::cerrar();
        if(!$updatesubastas) return array('status' => 'fail', 'message'=> $data['mensaje_fail'], 'data' => null);
        
        return array('status' => 'success', 'message'=> $data['mensaje_success'], 'data' => null);
    }

    function bloquearDireccionesSubasta(Array $data, Array $subasta)
    {
        $nasbifunciones = new Nasbifunciones();
        $direccionesUsuario = $data['direcciones'];
        $arrayreq = array();
        $retorno = null;

        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );
        foreach ($direccionesUsuario as $x => $direcciones) {
            $retorno = $nasbifunciones->insertarBloqueadoDiferido([
                'id' => null,
                'uid'=> $data['uid'],
                'empresa' => $subasta['empresa'],
                'moneda'=> $subasta['moneda'],
                'all' => true,
                'precio'=> 0,
                'precio_momento_usd'=> $precios[$subasta['moneda']],
                'address' => $direcciones['address'],
                'id_transaccion' => $subasta['id'],
                'tipo_transaccion' => 2,
                'tipo' => 'bloqueado',
                'accion' => 'push',
                'descripcion' => addslashes('Subasta '.$subasta['titulo']),
                'fecha' => $data['fecha']
            ]);
            array_push($arrayreq, $retorno);
        }
        unset($nasbifunciones);
        unset($precios);

        return $arrayreq;
    }

    function actualizarInscrito($data)
    {
        // $adicional = "";
        parent::conectar();
        $updatexsubastas = "UPDATE productos_subastas_inscritos
        SET
            estado = '$data[estado]',
            fecha_actualizacion = '$data[fecha]'
        WHERE id = '$data[id]'";
        $updatesubastas = parent::query($updatexsubastas);
        parent::cerrar();
        if(!$updatesubastas) return array('status' => 'fail', 'message'=> 'inscrito no actualizado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'inscrito actualizado', 'data' => null);
    }

    function quitarBloqueados(Array $data)
    {
        parent::conectar();
        $selectxbloqueado = 
        "SELECT ncb.* FROM nasbicoin_bloqueado_diferido ncb WHERE ncb.id_transaccion = '$data[id]' AND ncb.tipo_transaccion = '2'";

        $dinerobloqueado = parent::consultaTodo($selectxbloqueado);
        parent::cerrar();
        
        if(count($dinerobloqueado) <= 0) return array('status' => 'fail', 'message' => 'faltan datos subasta', 'data' => null);
        $dinerobloqueado = $this->mapBloquadoSubastas($dinerobloqueado, true);

        $desbloqueadas = $this->quitarBloqueadosTodos($dinerobloqueado, $data['fecha']);
        $validar = true;

        return array('status' => 'success', 'message' => 'data subasta', 'data' => null);
    }

    function quitarBloqueadosTodos(Array $addressBloqueadas, Int $fecha)
    {

        parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos (Todos los bloqueos): " . json_encode( $addressBloqueadas ) );
        // $nasbifunciones = new Nasbifunciones();
        $arrayreq = array();
        $retorno = null;

        parent::conectar();
        foreach ($addressBloqueadas as $x => $bloq) {
            $dataReverse = [
                'id'                 => $bloq['id'],
                'uid'                => $bloq['uid'],
                'empresa'            => $bloq['empresa'],
                'moneda'             => $bloq['moneda'],
                'all'                => false,
                'precio'             => $bloq['precio'],
                'precio_momento_usd' => null,
                'address'            => $bloq['address'],
                'id_transaccion'     => $bloq['id_transaccion'],
                'tipo_transaccion'   => 2,
                'tipo'               => 'bloqueado',
                'accion'             => 'reverse',
                'descripcion'        => 'Intentando quitas los bloqueos',
                'fecha'              => $fecha
            ];
            // $retorno = $nasbifunciones->insertarBloqueadoDiferido($dataReverse);
            parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos (devolviendo el dinero): " . json_encode( $dataReverse ) );

            $walletFrom = "buyinbig.nasbicoin_gold";
            if ( $bloq['moneda'] == "Nasbiblue" ) {
                $walletFrom = "buyinbig.nasbicoin_blue";
            }

            $selectxwalletxuser = "SELECT * FROM $walletFrom WHERE uid = '$bloq[uid]' AND empresa = '$bloq[empresa]' AND address = '$bloq[address]';";
            parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos ([1]. devolviendo el dinero SQL): " . $selectxwalletxuser );

            $selectwalletuser   = parent::consultaTodo($selectxwalletxuser);
            $selectwalletuser   = $selectwalletuser[0];
            parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos ([2]. devolviendo el dinero SQL): " . json_encode($selectwalletuser) );

            $selectxwalletxuserxnew = "UPDATE $walletFrom SET monto = monto + '$bloq[precio]' WHERE id = '$selectwalletuser[id]';";
            parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos ([3]. devolviendo el dinero SQL): " . $selectxwalletxuserxnew );
            $selectwalletusernew    = parent::query($selectxwalletxuserxnew);

            $deletexbloqueo = "DELETE FROM buyinbig.nasbicoin_bloqueado_diferido WHERE id = '$bloq[id]';";
            parent::addLogSubastas(" #SUBASTAS# quitarBloqueadosTodos ([3]. devolviendo el dinero SQL): " . $deletexbloqueo );

            $deletebloqueo  = parent::query($deletexbloqueo);
            array_push($arrayreq, $dataReverse);
        }
        parent::cerrar();
        

        // unset($nasbifunciones);
        return $arrayreq;
    }

    function insertarCarrito(Array $subasta, Array $puja, Array $dineropuja, Int $fecha)
    {

        parent::addLogSubastas(" #SUBASTAS# -----+> [ subasta ]: " . json_encode($subasta));
        parent::addLogSubastas(" #SUBASTAS# -----+> [ puja ]: " . json_encode($puja));
        parent::addLogSubastas(" #SUBASTAS# -----+> [ dineropuja ]: " . json_encode($dineropuja));

        parent::addLogSubastas("ENTRO A SUBASTA A INSERTAR: ========>" . json_encode( $subasta ) );

        $refer = "";
        parent::conectar();
        if( intval($subasta['empresa_producto']) == 1 ) {//ESTA ES LA CUENTA QUE PUBLICÓ EL NASBIDESCUENTO
            $selectempresa = parent::consultaTodo("SELECT e.* FROM empresas e WHERE e.id = '$subasta[uid_producto]';");
            if( count($selectempresa) > 0 ) {
                $refer = $selectempresa[0]['referido'];
                parent::addLogSubastas("========OBTENEMOS REFERIDO=========". $selectempresa[0]['referido'] );
            }
        }


        $insertcarrito = 
        "INSERT INTO carrito(
            uid,
            empresa,
            id_producto,
            cantidad,
            moneda,
            estado,
            tipo,
            exposicion_pt,
            categoria_pt,
            subcategoria_pt,
            comisiones_categoria_pt,
            refer,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES(
            '$subasta[uid]',
            '$subasta[empresa]',
            '$subasta[id_producto]',
            '$subasta[cantidad_producto]',
            '$subasta[moneda]',
            0,
            2,
            $subasta[exposicion],
            $subasta[categoria],
            $subasta[subcategoria],
            $subasta[comisiones_categoria],
            '$refer',
            '$subasta[fecha]',
            '$subasta[fecha]'
        );";
        $carrito = parent::queryRegistro($insertcarrito);//INSERCION TEMPORAL, MAS ADELANTE SE ELIMIINA DE ESTA TABLA

        parent::addLogSubastas(" #SUBASTAS# -----+> [ insertcarrito ]: " . $insertcarrito);


       parent::addLogSubastas(" #SUBASTAS# Insertar Carrito [uid_producto] ---->: " . $carrito);
        if(!$carrito) {
            parent::addLogSubastas("========ERROR AL INSERTAR EN EL CARRITO=========");
            parent::cerrar();
            return array(
                'status'        => 'fail',
                'message'       => 'error insertar carrito',
                'data'          => null,
                'carritoResult' => $carrito
            );
        }else{
            parent::addLogSubastas("========SE INSERTA EN EL CARRITO=========");

            $puja['monto']           = floatval( $puja['monto'] );
            $subasta['precio_local'] = floatval( $subasta['precio_local'] );
            $subasta['precio_usd']   = floatval( $subasta['precio_usd'] );

            $precio_usd_temp               = $puja['monto'] / ($subasta['precio_local'] / $subasta['precio_usd']);
            $precio_moneda_actual_usd_temp = $subasta['precio_local'] / $subasta['precio_usd'];

            $estado_producto_transaccion = 6;

            $subasta['flete'] = 0;

            $tipo_envio_por_direccion = $subasta['tipo_envio'];
            $flete = 0;
            if( $subasta['tipo_envio'] == 2 ){

                $tipo_envio_por_direccion = 3;

                $selectxdireccionxresidencia = 
                    "SELECT *
                    FROM buyinbig.direcciones
                    WHERE uid = $subasta[uid] AND empresa = $subasta[empresa] AND activa = 1";

                $direcciones = parent::consultaTodo($selectxdireccionxresidencia);

                parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / direcciones / 1 ]: " . json_encode($direcciones));

                if( count($direcciones) > 0 ){

                    $direcciones = $direcciones[0];
                    parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / direcciones / 2 ]: " . json_encode($direcciones));
                    $URL = "http://nasbi.peers2win.com/api/controllers/tcc_nasbi/?consultar_valor_envio";
                    $dataSend = array(
                        "data" => array(
                            "id_direccion_destino" => $direcciones['id'],
                            "fecha_consulta"       => intval(microtime(true)*1000),
                            "cantidad_producto"    => $subasta['cantidad'],
                            "productos"            => array(
                                [
                                    "id_producto" => $subasta['id_producto']
                                ]
                            )
                        )
                    );

                    parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / dataSend ]: " . json_encode($dataSend));

                    $response = parent::remoteRequest($URL, $dataSend);
                    $response = json_decode($response, true);

                    // var_dump( $response );

                    parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / response ]: " . json_encode($response));

                    if( $response['status'] == 'success' ){
                        parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / success ]: " . json_encode($response));

                        if(  isset($response['bandera_ocurrio_error_product']) ){
                            if( $response['bandera_ocurrio_error_product'] != 1 ){

                                if(  isset( $response['data']['total_de_valor_envio'] ) ){
                                    if(  isset( $response['data']['total_de_valor_envio'] ) ){

                                        $response['data']['total_de_valor_envio'] = floatval( $response['data']['total_de_valor_envio'] );
                                        $flete                                    = $response['data']['total_de_valor_envio'];
                                        $subasta['flete']                         = $response['data']['total_de_valor_envio'];
                                    
                                        parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / success / flete ]: " . $flete);
                                        parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / success / subasta ]: " . json_encode($subasta));
                                    }
                                }

                            }
                        }
                    }else{
                        parent::addLogSubastas(" #SUBASTAS# -----+> [ gestor envio / mal]: " . json_encode($response));

                    }
                }
            }


            parent::cerrar();
            $result = $this->deboPagarComisionEnGratuita([
                'uid'         => $subasta['uid_producto'],
                'empresa'     => $subasta['empresa_producto'],
                'id_producto' => $subasta['id_producto']
            ]);
            
            parent::conectar();
            $porcentajeExposicion = 0;
            $porcentajeExposicion = floatval($result['data']['porcentaje_premium']);

            $queryxtransaccion = 
                "SELECT
                    p.id,
                    p.porcentaje_nasbi
                FROM peer2win.usuarios u
                INNER JOIN peer2win.paquetes p ON(u.plan = p.id)
                WHERE u.id = '$refer';";

            $selectxinfoxplan = parent::consultaTodo($queryxtransaccion);

            $selectxinfoxplan                     = $selectxinfoxplan[0];
            $selectxinfoxplan['id']               = intval( $selectxinfoxplan['id'] );
            $selectxinfoxplan['porcentaje_nasbi'] = floatval( $selectxinfoxplan['porcentaje_nasbi'] );

            $precio_segundo_pago = 0;
            $moneda_segundo_pago = "";

            $insertarxtransaccion =
            "INSERT INTO productos_transaccion
            (
                id_carrito,
                id_producto,
                uid_vendedor,
                uid_comprador,
                cantidad,
                boolcripto,
                moneda,
                precio,
                precio_comprador,
                precio_usd,
                precio_moneda_actual_usd,
                tipo,
                estado,
                contador,
                id_metodo_pago,
                empresa,
                empresa_comprador,
                descripcion_extra,
                refer,
                exposicion_pt,
                categoria_pt,
                subcategoria_pt,
                comisiones_categoria_pt,
                refer_porcentaje_comision,
                refer_porcentaje_comision_idplan,
                refer_porcentaje_comision_plan,
                fecha_creacion,
                fecha_actualizacion,
                precio_envio,
                precio_segundo_pago,
                moneda_segundo_pago,
                pago_digital_id,
                pago_digital_calculos_pasarelas_id,
                id_carrito_lote_transaccion
            )
            VALUES
            (
                $carrito,
                $subasta[id_producto],
                $subasta[uid_producto],
                $subasta[uid],
                $subasta[cantidad_producto],
                1,
                '$subasta[moneda]',
                $puja[monto],
                $subasta[precio_local],
                $precio_usd_temp,
                $precio_moneda_actual_usd_temp,
                2,
                $estado_producto_transaccion,
                0,
                1,
                $subasta[empresa_producto],
                $subasta[empresa],
                '',
                '$refer',
                $subasta[exposicion],
                $subasta[categoria],
                $subasta[subcategoria],
                $subasta[comisiones_categoria],
                $porcentajeExposicion,
                $selectxinfoxplan[id],
                $selectxinfoxplan[porcentaje_nasbi],
                $fecha,
                $fecha,
                $flete,
                $precio_segundo_pago,
                '$moneda_segundo_pago',
                0,
                0,
                $carrito
            )";

            parent::addLogSubastas(" #SUBASTAS# -----+> [ insertarxtransaccion ]: " . $insertarxtransaccion);

            $productoTransaccion = parent::queryRegistro($insertarxtransaccion);
            parent::addLogSubastas(" #SUBASTAS# -----+> [ productoTransaccion ]: " . $productoTransaccion);

            $consulta = "DELETE FROM buyinbig.carrito WHERE id = $carrito;";//SE ELIMINA DEL CARRITO
            parent::query($consulta);
            parent::cerrar();

            $envioDinero = $this->bloquearDineroSubastaFinalizada($subasta, $puja, $dineropuja, $fecha, $carrito);

            $dataTimeLine = array(
                'id_carrito' => $carrito,
                'estado'     => 1,
                'fecha'      => $subasta['fecha'],
                'fecha'      => $subasta['fecha']
            );
            $this->insertarTimeline($dataTimeLine);
            $this->insertarTimeline2($dataTimeLine);
            
            $dataTimeLine = array(
                'id_carrito' => $carrito,
                'estado'     => $estado_producto_transaccion,
                'fecha'      => $subasta['fecha'],
                'fecha'      => $subasta['fecha']
            );
            $this->insertarTimeline($dataTimeLine);
            $this->insertarTimeline2($dataTimeLine);



            $id_envio = null;
            $id_ruta = null;

            $tipo_envio = null;
            $id_prodcuto_envio_shippo = null;
            $id_direccion_vendedor = null;
            $id_direccion_comprador = null;
            $fecha = intval(microtime(true)*1000);


            parent::conectar();
            $selectxproductoxenvio = 
            parent::consultaTodo("SELECT * FROM productos_envio pe WHERE pe.id_producto = '$subasta[id_producto]' AND pe.estado = 1;");

            $selectxdireccionesxcomprador = 
            parent::consultaTodo("SELECT * FROM direcciones d WHERE d.uid = '$subasta[uid]' AND d.empresa = '$subasta[empresa]' AND activa = 1;");

            $selectxdireccionesxvendedor = 
            parent::consultaTodo("SELECT * FROM direcciones d WHERE d.id = $subasta[id_direccion] AND (d.uid = '$subasta[uid_producto]' AND d.empresa = '$subasta[empresa_producto]');");

            $selectxproductos = 
            parent::consultaTodo("SELECT * FROM productos p WHERE p.uid = '$subasta[uid_producto]' AND p.empresa = '$subasta[empresa_producto]';");


            $selectxproductoxenvio         = $selectxproductoxenvio[0];
            $selectxdireccionesxcomprador  = $selectxdireccionesxcomprador[0];
            $selectxdireccionesxvendedor   = $selectxdireccionesxvendedor[0];
            $selectxproductos               = $selectxproductos[0];

            $this->insertarEnvio([
                'id_carrito'             => $carrito,

                'tipo_envio'             => $tipo_envio_por_direccion,
                'id_prodcuto_envio'      => $selectxproductoxenvio['id_shippo'],
                
                'id_envio'               => $id_envio,
                'id_ruta'                => $id_ruta,

                'id_transaccion'         => $carrito,

                'id_direccion_vendedor'  => $selectxdireccionesxvendedor['id'], 
                'id_direccion_comprador' => $selectxdireccionesxcomprador['id'],

                'fecha'                  => $fecha
            ]);

            if( $subasta['detalle_producto_colores_tallas'] != null || isset($subasta['detalle_producto_colores_tallas']) ){
                foreach( $subasta['detalle_producto_colores_tallas']  as $itemDetalle ) {

                    $insertarColoresTallas = 
                        "INSERT INTO productos_transaccion_especificacion_producto(
                            id_carrito,
                            id_transaccion,
                            id_producto,
                            id_detalle_producto_colores_tallas,
                            cantidad
                        ) 
                        VALUES(
                            $carrito,
                            $carrito,
                            $itemDetalle[id_producto],
                            $itemDetalle[id],
                            $itemDetalle[cantidad]
                        )";
                    $response = parent::queryRegistro($insertarColoresTallas);
                    parent::query("UPDATE detalle_producto_colores_tallas SET cantidad = 0 WHERE id = $itemDetalle[id];");
                }
            }

            $updateCantidad =
            "UPDATE buyinbig.productos
            SET
                cantidad          = 0,
                cantidad_vendidas = $subasta[cantidad]
            WHERE id = $subasta[id_producto];";

            parent::query($updateCantidad);

            parent::cerrar();

            return array(
                'status'                   => 'success',
                'message'                  => 'carrito creado',
                'data'                     => null,
                'carritoResult'            => $carrito,
                'puja'                     => $puja,
                'dineropuja'               => $dineropuja,
                'subasta'                  => $subasta,
                'carritoTransaccionResult' => $productoTransaccion,
                'envioDinero'              => $envioDinero,
            );
        }
    }

    function bloquearDineroSubastaFinalizada(Array $subasta, Array $puja, Array $dineropuja, Int $fecha, Int $id_transaccion)
    {
        // No es posible enviar el dinero si el articulo no se ha pagado.

        
        $nasbifunciones = new Nasbifunciones();
        $subasta['precio_local'] = floatval( $subasta['precio_local'] );
        $subasta['precio_usd']   = floatval( $subasta['precio_usd'] );
        $puja['monto']           = floatval( $puja['monto'] );

        $precio_divisa_en_usd    = $subasta['precio_local'] / $subasta['precio_usd'];
        $puja_monto_en_usd       = $puja['monto'] / $precio_divisa_en_usd;

        $nodo = new Nodo();
        $precios = array(
            'Nasbigold'=> $nodo->precioUSD(),
            'Nasbiblue'=> $nodo->precioUSDEBG()
        );

        $cuentaPote             = $nasbifunciones->getCuentaBanco( 1 );
        
        unset($nodo);

        $puja_monto_en_cripto = $puja_monto_en_usd / $precios[ $subasta['moneda'] ];



        // INICIO - BLOQUEO. Crearle el bloqueo al CLIENTE.
        $dataBloqueo = array(
            'id'                 => null,
            'uid'                => $puja['uid'],
            'empresa'            => $dineropuja['empresa'],
            'moneda'             => $subasta['moneda'],
            'all'                => false,
            
            'precio'             => $puja['monto'] + $subasta['flete'],
            'precio_momento_usd' => $puja_monto_en_usd,
            'precio_en_cripto'   => $puja_monto_en_cripto, // Cuanto cuesta el articulo en cripto.

            'address'            => $dineropuja['address'],
            'id_transaccion'     => $id_transaccion,
            'tipo_transaccion'   => 2,
            'tipo'               => 'bloqueado',
            'accion'             => 'push',
            'descripcion'        => addslashes('Artículo ' . $subasta['titulo'] . ' adquirido en subasta.'),
            'fecha'              => $fecha,
            "id_producto"        => $subasta['id_producto']
        );
        $bloqueo = $nasbifunciones->insertarBloqueadoDiferido($dataBloqueo);

        $address_diff = "";
        if( $subasta['moneda'] == 'Nasbigold'){
            $address_diff = $cuentaPote['address_Nasbigold'];

        }else{
            $address_diff = $cuentaPote['address_Nasbiblue'];

        }

        $data_diferido = array(
            'id'                 => null,
            'uid'                => $cuentaPote['uid'],
            'empresa'            => $cuentaPote['empresa'],
            'moneda'             => $subasta['moneda'],
            'all'                => false,

            'precio'             => $puja['monto'] + $subasta['flete'],
            'precio_momento_usd' => $puja_monto_en_usd,
            'precio_en_cripto'   => $puja_monto_en_cripto,

            'address'            => $address_diff,
            'id_transaccion'     => $id_transaccion,
            'tipo_transaccion'   => 2,
            'tipo'               => 'diferido',
            'accion'             => 'push',
            'descripcion'        => addslashes('Artículo ' . $subasta['titulo'] . ' adquirido en subasta.'),
            'fecha'              => $fecha,
            'id_producto'        => $subasta['id_producto']
        );
        $diferido = $nasbifunciones->insertarBloqueadoDiferido($data_diferido);

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid' => $subasta['uid'],

            'empresa' => $subasta['empresa'],

            
            'text' => 'Se ha bloqueado el monto '.$this->maskNumber($puja['monto']+$subasta['flete'], 2) . ' COP por '.addslashes('Artículo ' . $subasta['titulo'] . ' adquirido en subasta.'),
            
            'es' => 'Se ha bloqueado el monto '.$this->maskNumber($puja['monto']+$subasta['flete'], 2).' COP por '.addslashes('Artículo ' . $subasta['titulo'] . ' adquirido en subasta.'),
            
            'en' => 'You have started the process of purchasing the product ' . addslashes('Artículo ' . $subasta['titulo'] . ' adquirido en subasta.'),
            
            'keyjson' => '',
            
            'url' => 'mis-cuentas.php?tab=sidenav_compras'
        ]);


        $notificacion->insertarNotificacion([
            'uid' => $subasta['uid_producto'],

            'empresa' => $subasta['empresa_producto'],

            'text' => "Se ha enviado una orden de compra por el monto de " . $this->maskNumber($puja['monto']+$subasta['flete'], 2) . " COP por concepto de el artículo " . addslashes('Artículo ' . $subasta['titulo'] . " ganado en subasta."),

            'es' => "Se ha enviado una orden de compra por el monto de " . $this->maskNumber($puja['monto']+$subasta['flete'], 2) . " COP por concepto de el artículo " .addslashes('Artículo ' . $subasta['titulo'] . " ganado en subasta."),


            'en' => "A purchase order for the amount of " . $this->maskNumber($puja['monto'], 2) . " COP has been sent for the item " .addslashes('Artículo ' . $subasta['titulo'] . " won at auction"),

            'keyjson' => '',
            
            'url' => 'mis-cuentas.php?tab=sidenav_ventas'
        ]);

        unset($notificacion);

        
        parent::addLogSubastas(" #SUBASTAS# MIS_SUBASTAS | BLOQUEO: " . 
        json_encode(array(
            'subasta'                             => $subasta,
            'puja'                                => $puja,
            'dineropuja'                          => $dineropuja,
            
            'precios'                             => $precios,
            'puja[monto]'                         => $puja['monto'],
            'subasta[flete]'                      => $subasta['flete'],
            'precio_divisa_en_usd'                => $precio_divisa_en_usd,
            'puja_monto_en_cripto'                => $puja_monto_en_cripto,
            
            'dataBloqueo'                         => $dataBloqueo,
            'bloqueo'                             => $bloqueo,

            'data_diferido'                       => $data_diferido,
            'diferido'                            => $diferido
        )));

        // FIN - BLOQUEO.
        unset($nasbifunciones);
        return $bloqueo;
    }

    function mapSubastas(Array $subastas)
    {
        foreach ($subastas as $x => $subasta) {
            $subasta['id'] = floatval($subasta['id']);
            $subasta['uid'] = floatval($subasta['uid']);
            $subasta['empresa'] = floatval($subasta['empresa']);
            $subasta['id_subasta'] = floatval($subasta['id_subasta']);
            $subasta['id_producto'] = floatval($subasta['id_producto']);
            $subasta['cantidad'] = floatval($subasta['cantidad']);
            $subasta['estado'] = floatval($subasta['estado']);
            $subasta['ticket'] = floatval($subasta['ticket']);
            $subasta['costo_ticket_usd'] = floatval($subasta['costo_ticket_usd']);
            $subasta['costo_ticket_usd_mask'] = $this->maskNumber($subasta['costo_ticket_usd'], 2);
            $subasta['inscritos'] = floatval($subasta['inscritos']);
            $subasta['apostadores'] = floatval($subasta['apostadores']);
            $subasta['tipo'] = floatval($subasta['tipo']);
            $subasta['estado_subasta'] = floatval($subasta['estado_subasta']);
            if(isset($subasta['uid_ganador'])) $subasta['uid_ganador'] = floatval($subasta['uid_ganador']);
            $subasta['precio'] = floatval($subasta['precio']);
            $subasta['precio_mask'] = $this->maskNumber($subasta['precio'], 2);
            $subasta['precio_usd'] = floatval($subasta['precio_usd']);
            $subasta['precio_usd_mask'] = $this->maskNumber($subasta['precio_usd'], 2);

            $subasta['precio_local'] = floatval($subasta['precio_local']);
            $subasta['precio_local_mask']= $this->maskNumber($subasta['precio_local'], 2);

            if($subasta['oferta'] == 1){
                $subasta['porcentaje_oferta'] = floatval( $subasta['porcentaje_oferta'] );
                if ( $subasta['porcentaje_oferta'] >= 1 ) {
                    $subasta['porcentaje_oferta'] = $subasta['porcentaje_oferta'] / 100;
                }
                $subasta['precio_descuento_local'] = $subasta['precio_local'] * ($subasta['porcentaje_oferta']);
                $subasta['precio_descuento_local'] = $subasta['precio_local'] - $subasta['precio_descuento_local'];
                $subasta['precio_descuento_local']= floatval($this->truncNumber($subasta['precio_descuento_local'], 2));
                $subasta['precio_descuento_local_mask']= $this->maskNumber($subasta['precio_descuento_local'], 2);
            }else{
                $subasta['precio_descuento_local'] = $subasta['precio_local'];
                $subasta['precio_descuento_local_mask']= $this->maskNumber($subasta['precio_descuento_local'], 2);
            }

            $subasta['fecha_creacion'] = floatval($subasta['fecha_creacion']);
            $subasta['fecha_actualizacion'] = floatval($subasta['fecha_actualizacion']);
            $subasta['fecha_inicio'] = floatval($subasta['fecha_inicio']);
            $subastas[$x] = $subasta;
        }
        return $subastas;
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

    function deboPagarComisionEnGratuita( Array $data )
    {
        parent::conectar();
            $selectxcomisionxpagar = "SELECT c.CategoryID, c.porcentaje_gratuita, c.porcentaje_clasica, c.porcentaje_premium, p.exposicion FROM buyinbig.productos p INNER JOIN categorias c ON ( p.categoria = c.CategoryID) WHERE p.id = $data[id_producto];";
        $selectcomisionpagar = parent::consultaTodo($selectxcomisionxpagar);
        parent::cerrar();
        return array(
            'result' => true,
            'data'   => $selectcomisionpagar[ 0 ]
        );
    }

    function insertarTimeline(Array $data)
    {

        $tipo_timeline = 1;
        if ( !isset($data['tipo_timeline']) ) {
            // Sino existe esta campo entonces es para insertar en la tabla vendedor
            $tipo_timeline = 1;
        }else{
            $tipo_timeline = isset($data['tipo_timeline']);
        }
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
            '$data[id_carrito]',
            '$tipo_timeline',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
        parent::cerrar();
    }

    function insertarTimeline2(Array $data)
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
            '$data[id_carrito]',
            '2',
            '$data[estado]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        parent::queryRegistro($insertxtimeline);
        parent::cerrar();
    }

    function enviarcorreo_subasta_finalizada(Array $data_wbs, Array $subasta, Array $data_dinero_ultimo, Array $puja){
        $traer_data_subasta_por_id_e_inscritos= $this->optener_infomacion_subasta_inscritos([
            'id' => $data_wbs["id"]
        ]);

        $data_vendedor=  $this->datosUserGeneral([
            'uid' => $subasta["uid_producto"],
            'empresa' => $subasta["empresa_producto"]
        ]);


        $producto=$this->get_product_por_id([
            'uid' => $subasta["uid_producto"],
            'id' => $subasta["id_producto"],
            'empresa'  => $subasta["empresa_producto"] ]);
        
        foreach ($traer_data_subasta_por_id_e_inscritos as $x => $inscrito) {
            $data_cliente=  $this->datosUserGeneral([
                'uid' => $inscrito['uid'],
                'empresa' => $inscrito['empresa']
            ]);

        
            if($inscrito['uid'] == $data_wbs["uid"]){ //es el ganador de la subasta 
                $direcciones_cliente= $this->direccionUsuario([
                    'uid' => $inscrito['uid'],
                    'empresa' => $inscrito['empresa']
                ]);
                $this->html_correo_envio_ganador($data_cliente["data"], $producto[0], $puja, $subasta , $data_wbs); 
                $this->html_correo_vendedor_producto_ganado($data_vendedor["data"], $data_cliente["data"], $producto[0], $puja, $subasta , $data_wbs); 
                $this->html_correo_vendedor_producto_envio_datos_comprador($data_vendedor["data"], $data_cliente["data"], $producto[0], $puja, $subasta , $data_wbs, $direcciones_cliente["data"]); 
            }else{
                $this->html_correo_envio_no_ganadores($data_cliente["data"], $producto[0], $puja, $subasta , $data_wbs); 
            }
        }
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
    
    function datosUserGeneral( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }

    function html_correo_envio_ganador(Array $data_cliente, Array $data_producto, Array $data_dinero_ultimo, Array $data_subasta, Array $data_wbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_cliente["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo12.html");
        
        //$bloq['precio_mask'] = $this->maskNumber($bloq['precio'], 2);

        if($data_subasta["moneda"]=="Nasbigold" || $data_subasta["moneda"]=="nasbigold"){
            $data_subasta["moneda"]="Nasbichips"; 
        }else if($data_subasta["moneda"]=="Nasbiblue" || $data_subasta["moneda"]=="nasbiblue"){
            $data_subasta["moneda"]="Bono(s) de descuento"; 
        }

        $html = str_replace("{{trans26_brand}}",$json->trans26_brand, $html);
        $html = str_replace("{{trans175}}", $json->trans175, $html);
        $html = str_replace("{{trans176}}", $json->trans176, $html);
        $html = str_replace("{{nombre_usuario}}",$data_cliente['nombre'], $html);
       $html = str_replace("{{trans177}}", $json->trans177, $html);
        $html = str_replace("{{producto_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans35_unidad}}",$data_subasta["moneda"], $html);
        $html = str_replace("{{trans34_valor}} ",  $this->maskNumber($data_dinero_ultimo["monto"], 2), $html);
        $html = str_replace("{{trans178}}", $json->trans178, $html);
        $html = str_replace("{{trans136_}}", $json->trans136_, $html);
        $html = str_replace("{{link_to_contacto}}", $json->link_to_contacto, $html);
        $html = str_replace("{{trans137_}}", $json->trans137_, $html);
        $html = str_replace("{{trans138_}}", $json->trans138_, $html);

    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_cliente["uid"]."&act=0&em=".$data_cliente["empresa"], $html); 

        $para      = $data_cliente['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans133_." ".$data_wbs["id"];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function html_correo_vendedor_producto_ganado(Array $data_vendedor,Array $data_cliente, Array $data_producto, Array $data_dinero_ultimo, Array $data_subasta ,Array $data_wbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo24enviopendiente.html");
    
        if($data_subasta["moneda"]=="Nasbigold" || $data_subasta["moneda"]=="nasbigold"){
            $data_subasta["moneda"]="Nasbichips"; 
        }else if($data_subasta["moneda"]=="Nasbiblue" || $data_subasta["moneda"]=="nasbiblue"){
            $data_subasta["moneda"]="Bono(s) de descuento"; 
        }
    
        $html = str_replace("{{trans_brand}}",$json->trans_brand, $html);
        $html = str_replace("{{trans40}}", $json->trans40, $html);
        $html = str_replace("{{nombre_usuario}}",$data_vendedor['nombre'], $html);
        $html = str_replace("{{trans41}}", $json->trans41, $html);
        $html = str_replace("{{foto_producto_despachar}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans35_unidad}}",$data_subasta["moneda"], $html);
        $html = str_replace("{{trans34_valor}} ", $this->maskNumber($data_dinero_ultimo["monto"], 2), $html);
        $html = str_replace("{{trans42}}", $json->trans42, $html);
        $html = str_replace("{{trans43}}", $json->trans43, $html);
        $html = str_replace("{{link_to_ventas}}", $json->link_to_ventas, $html);
        
       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans135_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    
    }

    function html_correo_envio_no_ganadores(Array $data_cliente, Array $data_producto, Array $data_dinero_ultimo, Array $data_subasta, Array $data_wbs){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_cliente["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo13.html");


        $html = str_replace("{{trans26_brand}}",$json->trans26_brand, $html);
        $html = str_replace("{{trans168}}", $json->trans168, $html);
        $html = str_replace("{{trans169_brand}}", $json->trans169_brand, $html);
        $html = str_replace("{{nombre_usuario}}",$data_cliente['nombre'], $html);
        $html = str_replace("{{trans170}}", $json->trans170, $html);
        $html = str_replace("{{nasbichips_compra_subasta}}","", $html);
        $html = str_replace("{{trans171}}", $json->trans171, $html);
        $html = str_replace("{{trans173}}", $json->trans173, $html);
        $html = str_replace("{{trans172}}", $json->trans172, $html);
        $html = str_replace("{{trans174}}", $json->trans174, $html);
        
        $html = str_replace("{{link_to_nasbidescuento}}", $json->link_to_nasbidescuento, $html);
        
       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_cliente["uid"]."&act=0&em=".$data_cliente["empresa"], $html); 

        $para      = $data_cliente['correo']  . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans134_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    public function html_correo_vendedor_producto_envio_datos_comprador(Array $data_vendedor,Array $data_cliente, Array $data_producto, Array $data_dinero_ultimo, Array $data_subasta ,Array $data_wbs, Array $direcciones_cliente)
    {
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_vendedor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo22productovendido.html");
    
        if($data_subasta["moneda"]=="Nasbigold" || $data_subasta["moneda"]=="nasbigold"){
            $data_subasta["moneda"]="Nasbichips"; 
        }else if($data_subasta["moneda"]=="Nasbiblue" || $data_subasta["moneda"]=="nasbiblue"){
            $data_subasta["moneda"]="Bono(s) de descuento"; 
        }

        $direccion_activacliente = $this->filter_by_value($direcciones_cliente, 'activa', 1); 
        $direccion_activacliente= $direccion_activacliente[0]; 

        $html = str_replace("{{trans47_brand}}",$json->trans47_brand, $html);
        $html = str_replace("{{trans48}}", $json->trans48, $html);
        $html = str_replace("{{nombre_usuario}}",$data_vendedor['nombre'], $html);
        $html = str_replace("{{trans49}}", $json->trans49, $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans35_unidad}}",$data_subasta["moneda"], $html);
        $html = str_replace("{{trans34_valor}} ",$this->maskNumber($data_dinero_ultimo["monto"], 2), $html);
        $html = str_replace("{{foto_producto_vendido_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans50}}", $json->trans50, $html);
        $html = str_replace("{{trans51}}", $json->trans51, $html);
        $html = str_replace("{{nombre_cliente}}", $data_cliente["nombre"], $html);
        $html = str_replace("{{trans52}}", $json->trans52, $html);
        $html = str_replace("{{telefono_cliente}}", $data_cliente["telefono"], $html);
        $html = str_replace("{{trans53}}", $json->trans53, $html);
        $html = str_replace("{{direccion_cliente}}", $direccion_activacliente["direccion"], $html);
        $html = str_replace("{{trans54}}", $json->trans54, $html);
        $html = str_replace("{{ciudad_cliente}}", $direccion_activacliente["ciudad"], $html);
        $html = str_replace("{{trans55}}", $json->trans55, $html);
        $html = str_replace("{{link_to_contacto}}", $json->link_to_contacto, $html);
        $html = str_replace("{{trans24}}", $json->trans24, $html);
        $html = str_replace("{{trans39}}", $json->trans39, $html);
        
        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_vendedor["uid"]."&act=0&em=".$data_vendedor["empresa"], $html); 
    
        $para      = $data_vendedor['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans139_." ".$data_producto['titulo'];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function direccionUsuario(Array $data){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
        
        parent::conectar();
        $selectdireccion = "SELECT * FROM direcciones d WHERE d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' AND d.estado = 1 ORDER BY fecha_creacion DESC";
        $direcciones = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($direcciones) <= 0) return array('status' => 'fail', 'message'=> 'no tine direcciones', 'cantidad'=> 0,'data' => null);
            
        $direcciones = $this->mapDirecciones($direcciones);
        return array('status' => 'success', 'message'=> 'direcciones', 'cantidad'=> count($direcciones),'data' => $direcciones);
    }

    function mapDirecciones(Array $direcciones)
    {
        foreach ($direcciones as $x => $dir) {
            $dir['uid'] = floatval($dir['uid']);
            $dir['pais'] = floatval($dir['pais']);
            $dir['departamento'] = floatval($dir['departamento']);
            $dir['ciudad'] = $dir['ciudad'];
            $dir['latitud'] = floatval($dir['latitud']);
            $dir['longitud'] = floatval($dir['longitud']);
            $dir['codigo_postal'] = $dir['codigo_postal'];
            $dir['direccion'] = $dir['direccion'];
            $dir['id'] = floatval($dir['id']);
            $dir['estado'] = floatval($dir['estado']);
            $dir['activa'] = floatval($dir['activa']);
            $dir['fecha_creacion'] = addslashes($dir['fecha_creacion']);
            $dir['fecha_actualizacion'] = addslashes($dir['fecha_actualizacion']);

            $direcciones[$x] = $dir;
        }

        return $direcciones;
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

    function envio_correo_cuenta_bloqueada(Array $data_subasta, Array $data_wbs){
        $data_user=  $this->datosUserGeneral([
            'uid' => $data_wbs["uid"],
            'empresa' => $data_wbs["empresa"]
        ]);

        $this->html_envio_correo_cuenta_bloqueada($data_user["data"], $data_wbs, $data_subasta);

    }

    function html_envio_correo_cuenta_bloqueada(Array $data_user, Array $data_wbs, Array $data_subasta ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_por_subasta/Comprasubastacorreo10.html");
    
        if($data_subasta["moneda"]=="Nasbigold" || $data_subasta["moneda"]=="nasbigold"){
            $data_subasta["moneda"]="Nasbichips"; 
        }else if($data_subasta["moneda"]=="Nasbiblue" || $data_subasta["moneda"]=="nasbiblue"){
            $data_subasta["moneda"]="Bono(s) de descuento"; 
        }
 

        $html = str_replace("{{trans185_brand}}",$json->trans185_brand, $html);
        $html = str_replace("{{trans186}}", $json->trans186, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans187}}", $json->trans187, $html);
        $html = str_replace("{{nasbichips_compra_subasta}}", "", $html);
        $html = str_replace("{{trans188}}", $json->trans188, $html);
        $html = str_replace("{{subasta}}", $data_wbs["id"], $html);
        $html = str_replace("{{trans189}}", $json->trans189, $html);
        $html = str_replace("{{trans174}}", $json->trans174, $html);
        $html = str_replace("{{link_to_nasbidescuento}}", $json->link_to_nasbidescuento, $html);
        $html = str_replace("{{moneda_subasta}}", $data_subasta["moneda"], $html);

        

        
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 
    
        $para      = $data_user['correo']  . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans140_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function comenzarSubasta(Array $data) {

        if(isset($data['id'])){
            parent::conectar();
            $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id = '$data[id]'");
            parent::cerrar();
            
            if($subasta){
                if($subasta[0]['inscritos'] >= $subasta[0]['apostadores']){
                    $inscritos_activacion = $subasta[0]['inscritos'];
                    parent::conectar();
                    $updated =  parent::query("UPDATE productos_subastas SET
                                               estado = 2,
                                               inscritos_activacion_subasta = '$inscritos_activacion'
                                               WHERE id = '$data[id]'");
                    parent::cerrar();
                    if($updated){
                        return array("status" => "success", "mensaje" => "subasta activada");
                    }else{
                        return array("status" => "fail", "mensaje" => "error al activar subasta");
                    }
                }else{
                    return array("status" => "fail", "mensaje" => "la subasta no cumple el numero de inscritos");
                }
            }else{
                return array("status" => "fail", "mensaje" => "no se encontro subasta");
            }
        }
        return array("status" => "fail", "mensaje" => "no id en data");
    }

    function get_product_solo_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos;
    }

    function get_direccion_activa_por_uid(Array $data){
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where uid = '$data[uid]' and empresa = '$data[empresa]' and activa = 1;");
        parent::cerrar();
        return $direccion;
    }

    function get_producto_envio_por_producto(Array $data){
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM productos_envio where id_producto = '$data[id_producto]'");
        parent::cerrar();
        return $direccion;
    }

    function insertarEnvio(Array $data)
    {
        $insertxenvio = "INSERT INTO productos_transaccion_envio
        (
            id_carrito,
            id_transaccion,
            tipo_envio,
            id_direccion_vendedor,
            id_direccion_comprador,
            id_prodcuto_envio,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_carrito]',
            '$data[id_transaccion]',
            '$data[tipo_envio]',
            '$data[id_direccion_vendedor]',
            '$data[id_direccion_comprador]',
            '$data[id_prodcuto_envio]',
            '$data[fecha]',
            '$data[fecha]'
        );";
        $insertenvio = parent::queryRegistro($insertxenvio);
        //if($data['tipo_envio'] == 2) 
            $this->saveShippo(['id_carrito' => $data['id_carrito'],'id_envio' => $data['id_envio'], 'id_ruta' => $data['id_ruta'], 'id' => $insertenvio]);
    }

    function saveShippo(Array $data)
    {
        $empresa                               = "empresas s.a.s.";
        $transaction["tracking_number"]        = round(microtime(true) * 1000);
        $transaction["tracking_url_provider"]  = "";
        $transaction["label_url"]              = "";
        $transaction["commercial_invoice_url"] = "";
        $transaction["object_id"]              = round(microtime(true) * 1000);

        $updatedireccion = 
        "UPDATE productos_transaccion_envio
        SET
            numero_guia = '$transaction[tracking_number]',
            url_numero_guia = '$transaction[tracking_url_provider]',
            empresa = '$empresa',
            etiqueta_envio = '$transaction[label_url]',
            factura_comercial = '$transaction[commercial_invoice_url]',
            id_envio_shippo = '$data[id_envio]',
            id_ruta_shippo = '$data[id_ruta]',
            id_transaccion_shippo = '$transaction[object_id]'
        WHERE id_carrito = '$data[id_carrito]'";

        $actualizar = parent::query($updatedireccion);
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
    }



}
?>
