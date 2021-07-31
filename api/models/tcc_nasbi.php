<?php
require 'nasbifunciones.php';
require 'tcc.php';

   

class Tccnasbi extends Conexion
{
    // //[1]. variables PRUEBA consultar envio
    // private $clave_consultar = "CLIENTETCC608W3A61CJ";
    // private $unidad_negocio_paqueteria = 1485100;
    // private $unidad_negocio_mensajeria = 5625200;
    // //fin variables PRUEBA consultar envio

    //[1]. variables PRODUCCION consultar envio
    private $clave_consultar           = "NASBY7893DJ90LK2DHJY";
    private $unidad_negocio_paqueteria = 1779500;
    private $unidad_negocio_mensajeria = 5784900;
    //fin variables PRODUCCION consultar envio


    //[2]. variables PRUEBA despacho 
    private $clave_despacho = "CLIENTETCC608W3A61CJ";
    private $unidad_negocio_mensajeria_des = 1485100;
    private $unidad_negocio_paqueteria_des= 5625200;
    //fin variables PRUEBA despacho


    public function __construct(){

        //variables PRODUCCION consultar envio
        $this->clave_consultar           = "NASBY7893DJ90LK2DHJY";
        $this->unidad_negocio_paqueteria = 1779500;
        $this->unidad_negocio_mensajeria = 5784900;
        // fin variables PRODUCCION consultar envio

        //variables PRODUCCION despacho.
        $this->clave_despacho                = "NASBY7893DJ90LK2DHJY";
        $this->unidad_negocio_mensajeria_des = 1779500; 
        $this->unidad_negocio_paqueteria_des = 5784900; 
        // fin variables PRODUCCION despacho.

    }


    function consultar_valor_envio($data)
    {
        if (!isset($data) || !isset($data['id_direccion_destino']) || !isset($data['productos']) || !isset($data["fecha_consulta"]) || !isset($data["cantidad_producto"])) return array(
            'status' => 'fail',
            'message' => 'faltan datos',
            'data' => null
        );
        $accedio_error_de_producto = 0;
        $productos = $data["productos"];
        $data_direccion = $this->get_data_direccion(["id" => $data['id_direccion_destino']]);
        if (empty($data_direccion["dane"]) && !isset($data_direccion["dane"])) return array(
            'status' => 'ciudadDestinoNoDane',
            'message' => 'el id direccion de destino no tiene dane',
            'data' => null
        );
        if (empty($data["cantidad_producto"]))
        {
            $data["cantidad_producto"] = 1;
        }

        $ciudad_destino = $data_direccion["dane"];
        $fecha_remesa = $this->transformar_fecha_U_to_normal(['fecha' => $data["fecha_consulta"], 'tipo' => 2]);
        $array_de_respuesta_consulta = [];
        $array_data_data_to_tcc = [];

        //campos estaticos que no vienen del front
        $clave_to_tcc = $this->clave_consultar;
        //campos estaticos que no vienen del front
        foreach ($productos as $key => $producto)
        {
            $data_to_tcc = [];
            $data_producto = $this->get_product_por_id(['id' => $producto["id_producto"]]);
            $data_envio = $this->get_data_envio(['id_producto' => $producto["id_producto"]]);
            $respuesta = $this->validacion_nacionalidad_producto_data_general($data_producto, $data_envio);
            $objeto_tcc = new Tcc();
            if ($respuesta["acceso_consulta"] == true)
            {
                $data_respuesta_tcc = [];
                $ciudad_origen = $respuesta["codigo_dane"]; // descomentar cuando ya este lo de direcciones el codigo dane
                // $ciudad_origen="08001000";
                $tipo_envio = $this->get_tipo_envio(['data_envio' => $data_envio, 'cantidad_producto' => $data["cantidad_producto"], 'data_producto' => $data_producto]);
                $valores_cm_medidas = $this->get_valores_cm_medidas(['alto' => floatval($data_envio["alto"]) , "ancho" => floatval($data_envio["ancho"]) , "largo" => floatval($data_envio["largo"]) , "unidad" => $data_envio["unidad_distancia"]]);
                $peso_real = $tipo_envio["peso_mayor"];
                $peso_volumetrico = $tipo_envio["valor_peso_volumetrico"];
                $cuenta_unidad = $this->determinar_unidad_negocio(intval($tipo_envio["unidad_negocio"]));
                $data_to_tcc = [];
                $data_to_tcc = [
                    "Clave" => $clave_to_tcc,
                    "Liquidacion" => array(
                        "tipoenvio"                  => $tipo_envio["unidad_negocio"],
                        "idciudadorigen"             => $ciudad_origen,
                        "idciudaddestino"            => $ciudad_destino,
                        "cuenta"                     => $cuenta_unidad,
                        "valormercancia"             => $tipo_envio["valor_mercancia"],
                        "fecharemesa"                => $fecha_remesa,
                        "idunidadestrategicanegocio" => $tipo_envio["unidad_negocio"],
                        "unidades" => array(
                            "unidad" => array(
                                "numerounidades" => $tipo_envio["numero_unidades"],
                                "pesoreal"       => $peso_real,
                                "pesovolumen"    => $peso_volumetrico,
                                "tipoempaque"    => ""
                            )
                        )
                    )
                ];
                $data_respuesta_tcc = $objeto_tcc->consultar_liquidacion_wbs_tcc($data_to_tcc);
                $data_respuesta_tcc["status"] = $this->agregar_status_a_respuesta_tcc_consulta($data_respuesta_tcc);
                //="success";
                $data_to_tcc["status"] = "success";
            }
            else
            {
                $accedio_error_de_producto = 1;
                $data_respuesta_tcc["mensaje"] = $respuesta["mensaje"];
                $data_to_tcc["mensaje"] = $respuesta["mensaje"];
                $data_respuesta_tcc["status"] = $respuesta["status"];
                $data_to_tcc["status"] = $respuesta["status"];
            }

            $data_respuesta_tcc["id_producto"] = $producto["id_producto"];

            $data_to_tcc["id_producto"] = $producto["id_producto"];
            if (isset($data_to_tcc["Clave"]))
            {
                unset($data_to_tcc["Clave"]);
            }

            array_push($array_de_respuesta_consulta, $data_respuesta_tcc);
            array_push($array_data_data_to_tcc, $data_to_tcc);

        }
        if (count($array_de_respuesta_consulta) <= 0)
        {
            return array(
                'status' => 'fail',
                'message' => 'ocurrio algun error',
                'data' => null
            );
        }
        else
        {
            $data_enviar_respuesta = $this->mapeo_y_suma_de_valores_envio($array_de_respuesta_consulta);
            return array(
                'status' => 'success',
                'message' => 'respuesta succes ',
                'data' => $data_enviar_respuesta,
                'data_enviada_a_tcc' => $array_data_data_to_tcc,
                'bandera_ocurrio_error_product' => $accedio_error_de_producto
            );
        }
    }

    function depacho_envio($data)
    {
        if (!isset($data) || !isset($data["uid_remitente"]) || !isset($data["empresa_remitente"]) || !isset($data["uid_destinatario"]) || !isset($data["empresa_destinatario"]) || !isset($data["cantidad_producto"]) || !isset($data['productos']) || !isset($data['id_transanccion']) || !isset($data['id_carrito']) || !isset($data['observacion']) || !isset($data["recogida"]) || !isset($data["fecha_inicio"]) || !isset($data["fecha_fin"])) return array(
            'status' => 'fail',
            'message' => 'faltan datos',
            'data' => null
        );

        $fecha_inicio_recogida = "";
        $fecha_fin_recogida = "";
        $fecha_envio_reco = "";
        $cantidad_producto = "";
        $unix_fecha_general = "";
        $unix_fecha_inicio = "";
        $unix_fecha_final = "";
        $data_to_enviar_wbs_of_tcc = [];
        $recogida = intval($data["recogida"]);

        $guias_de_esta_transancion = $this->traer_guias_transanccion(array(
            "id_trasanccion" => $data['id_transanccion']
        ));
        if (count($guias_de_esta_transancion) > 0)
        {
            return array(
                "status"  => "existeGuia",
                "mensage" => "Ya existe guia de esta transanccion",
                "data"    => $guias_de_esta_transancion
            );
        }

        if (empty($data["cantidad_producto"]))
        {
            $data["cantidad_producto"] = 1;
        }

        $cantidad_producto = intval($data["cantidad_producto"]);

        $fechas = $this->obtener_valores_fecha_tcc($recogida);

        if ($recogida == 1)
        {
            // if(empty($data["fecha_inicio"])  ||  empty($data["fecha_fin"]) ) return array('status' => 'fail', 'message'=> 'falta fecha rango', 'data' => null);
            $fecha_inicio_recogida = $fechas["fecha_inicio"];
            $fecha_fin_recogida    = $fechas["fecha_fin"];
            $fecha_envio_reco      = $fechas["fecha"];
            $unix_fecha_general    = $fechas["timestamp_normal"];
            $unix_fecha_inicio     = $fechas["timestamp_inicio"];
            $unix_fecha_final      = $fechas["timestamp_fin"];
        }
        else
        {
            $unix_fecha_general = $fechas["timestamp_normal"];
            $fecha_envio_reco   = $fechas["fecha"];
        }

        $fechas_rangos_recogida["fecha_inicio"] = $fecha_inicio_recogida;
        $fechas_rangos_recogida["fecha_fin"]    = $fecha_fin_recogida;
        $fechas_rangos_recogida["fecha_envio"]  = $fecha_envio_reco;
        $fechas_rangos_recogida["unix_inicio"]  = $unix_fecha_inicio;
        $fechas_rangos_recogida["unix_fin"]     = $unix_fecha_final;
        $fechas_rangos_recogida["unix_envio"]   = $unix_fecha_general;

        //en realidad esta direccion no se usa para enviar a tcc de remitente estaba de prueba se usa la que tenga el producto
        $data_del_que_envia = $this->datosUserGeneral([ 
            'uid'     => $data["uid_remitente"],
            'empresa' => $data["empresa_remitente"]
        ]);

        $data_del_recibe = $this->datosUserGeneral(['uid' => $data["uid_destinatario"], 'empresa' => $data["empresa_destinatario"]]);

        $data_direccion_activa_remitente = $this->get_direccion_activa_por_uid(['uid' => $data["uid_remitente"], 'empresa' => $data["empresa_remitente"]]);

        $data_direccion_activa_destinatario = $this->get_direccion_activa_por_uid(['uid' => $data["uid_destinatario"], 'empresa' => $data["empresa_destinatario"]]);

        if (!isset($data_direccion_activa_remitente["dane"]) && empty($data_direccion_activa_remitente["dane"])) return array(
            'status'  => 'fail',
            'message' => 'el usuario remitente no tiene codigo dane',
            'data'    => null
        );
        if (!isset($data_direccion_activa_destinatario["dane"]) && empty($data_direccion_activa_destinatario["dane"])) return array(
            'status'  => 'fail',
            'message' => 'el usuario destinatario no tiene codigo dane',
            'data'    => null
        );

        $data_optenida_de_wbs_consultar_liquidacion = $this->consultar_valor_envio(array(
            "productos"            => $data["productos"],
            "fecha_consulta"       => $fechas_rangos_recogida["unix_envio"],
            "id_direccion_destino" => $data_direccion_activa_destinatario["id"],
            "cantidad_producto"    => $data["cantidad_producto"]
        ));

        if ($data_optenida_de_wbs_consultar_liquidacion["status"] == "success")
        {
            $data_to_enviar_wbs_of_tcc["clave"]                  = $this->clave_despacho; //produccion "NASBY7893DJ90LK2DHJY";
            $data_to_enviar_wbs_of_tcc["data_user_remitente"]    = $data_del_que_envia["data"];
            $data_to_enviar_wbs_of_tcc["data_user_destinatario"] = $data_del_recibe["data"];
            $data_tratada_valor_envio                            = $this->tratar_data_de_valor_envio($data_optenida_de_wbs_consultar_liquidacion);
            $data_to_enviar_wbs_of_tcc["valores_envio"]          = $data_tratada_valor_envio;
            
            $data_to_enviar_wbs_of_tcc["data_front"]             = $data;
            $data_to_enviar_wbs_of_tcc["direccion_remitente"]    = $data_direccion_activa_remitente;
            $data_to_enviar_wbs_of_tcc["direccion_destinatario"] = $data_direccion_activa_destinatario;
            $data_to_enviar_wbs_of_tcc["fecha_recogida"]         = $fechas_rangos_recogida;

            $respuesta_envio_data       = $this->enviar_data_wbs_tcc($data_to_enviar_wbs_of_tcc, $cantidad_producto);
            $respuesta_inserccion_guias = $this->preparar_insertar_to_tabla_guias($respuesta_envio_data["data"], $data['id_transanccion'], $data['id_carrito']);
            // var_dump($respuesta_inserccion_guias);
            return $respuesta_envio_data;

        }
        else
        {
            return $data_optenida_de_wbs_consultar_liquidacion;
        }

    }

    function preparar_insertar_to_tabla_guias($data, $id_transanccion, $id_carrito)
    {
        $data_respuesta = [];
        foreach ($data as $key => $respuesta)
        {
            if ($respuesta["status"] == "success")
            {
                $reponse_insercion = $this->insertar_guia_generada_to_nasbi(array(

                    "remesa" => $respuesta["remesa"],
                    "numerorecogida" => $respuesta["numerorecogida"],
                    "urlrelacionenvio" => $respuesta["urlrelacionenvio"],
                    "urlremesa" => $respuesta["urlremesa"],
                    "urlrotulos" => $respuesta["urlrotulos"],
                    "respuesta" => $respuesta["respuesta"],
                    "mensaje" => $respuesta["mensaje"],
                    "id_producto" => $respuesta["id_producto"],
                    "id_carrito" => $id_carrito,
                    "unidad_negocio" => $respuesta["unidad_negocio"],
                    "id_productos_transaccion" => $id_transanccion,
                    "recogida" => $respuesta["recogida"],
                    "fecha_inicio" => $respuesta["fecha_inicio"],
                    "fecha_fin" => $respuesta["fecha_fin"]
                ));
                array_push($data_respuesta, $reponse_insercion);
            }

        }
        return $data_respuesta;
    }

    function insertar_guia_generada_to_nasbi(Array $data)
    {
        parent::conectar();
        $id_dpn_ref = parent::queryRegistro("INSERT INTO productos_transaccion_guia_tcc(
      remesa,
      numerorecogida, 
      urlrelacionenvio,
      urlremesa, 
      urlrotulos, 
      respuesta,
      mensaje, 
      id_producto, 
      id_productos_transaccion, 
      id_carrito,
      unidad_de_negocio,
      tag_recogida,
      rango_fecha_recogida_inicio,
      rango_fecha_recogida_fin
      )
      VALUES(
        '$data[remesa]',
        '$data[numerorecogida]', 
        '$data[urlrelacionenvio]',
        '$data[urlremesa]', 
        '$data[urlrotulos]', 
        '$data[respuesta]',
        '$data[mensaje]', 
        '$data[id_producto]', 
        '$data[id_productos_transaccion]', 
        '$data[id_carrito]',
        '$data[unidad_negocio]',
        '$data[recogida]', 
        '$data[fecha_inicio]', 
        '$data[fecha_fin]'
    
        )");
        parent::cerrar();
        if (intval($id_dpn_ref) <= 0)
        {
            return array(
                'status' => 'errorDatosguia',
                'message' => 'erro insertar guia de producto' . $data["id_producto"]
            );
        }

        return array(
            'status' => 'success',
            'message' => "bien insercion guia  $data[id_producto]"
        );

    }

    function enviar_data_wbs_tcc(Array $data, int $cantidad_producto = 1)
    {
        $data_productos              = $data["valores_envio"]["data_envio_productos"];
        $data_enviada_front          = $data["data_front"];
        $fechas_rangos_recogida      = $data["fecha_recogida"];
        $objeto_tcc                  = new Tcc();
        $array_guias_tcc_por_product = [];
        $array_data_enviada_to_tcc   = [];

        foreach ($data_productos as $key => $producto)
        {


            // <rem:identificaciondestinatario>VA LA CEDULA DEL PROVEEDOR O NIT</rem:identificaciondestinatario> // REVISAR DEBE IR EL DEL VENDEDOR

            // <rem:codigobarras></rem:codigobarras>

            $tipoidentificacionremitente = "NIT";
            $identificacionremitente = 901405511;
            $naturalezaremitente     = "J";
            
            if( isset( $data["data_user_remitente"]["identificacion"] ) && $data["data_user_remitente"]["identificacion"] != "" && $data["data_user_remitente"]["identificacion"] != null ){
                $identificacionremitente = $data["data_user_remitente"]["identificacion"];

            }else{
                $tipoidentificacionremitente = "NIT";
                $identificacionremitente = 901405511;
            }

            if( isset( $data["data_user_remitente"]["tipo_identificacion"] ) && $data["data_user_remitente"]["tipo_identificacion"] != "" && $data["data_user_remitente"]["tipo_identificacion"] != null ){
                $tipoidentificacionremitente = $data["data_user_remitente"]["tipo_identificacion"];

            }else{
                $tipoidentificacionremitente = "NIT";
                $identificacionremitente = 901405511;
            }

            $naturalezaremitente     = ($data["data_user_remitente"]["empresa"] == 0? 'N' : 'J');
            
            $cantidad_unidades_caja = $this->obtener_numero_unidades($producto["Liquidacion"]["unidades"]["unidad"]["pesoreal"]);
            $observacion     = "";
            $data_producto   = $this->get_product_por_id(['id' => $producto["id_producto"]]);
            $data_direccion  = $this->get_data_direccion(["id" => $data_producto["id_direccion"]]);
            $observacion     = $this->ajustar_text_observacion($data_producto, intval($data["data_front"]["id_carrito"]));
            $data_enviar_wbs = [];
            $data_enviar_wbs = array(
                "clave" => $data["clave"],
                "numerorelacion"    => "",
                "fechahorarelacion" => "",
                "solicitudrecogida" => array(
                    "numero"        => "",
                    "fecha"         => $fechas_rangos_recogida["fecha_envio"],
                    "ventanainicio" => $fechas_rangos_recogida["fecha_inicio"],
                    "ventanafin"    => $fechas_rangos_recogida["fecha_fin"]
                ) ,
                "unidadnegocio"                  => $data["valores_envio"]["tipo_envio"], // la unidad de negocio se toma en consultar valor envio
                "numeroremesa"                   => "",
                "fechadespacho"                  => $fechas_rangos_recogida["fecha_envio"],
                "cuentaremitente"                => $data["valores_envio"]["cuentaremitente"],
                
                "tipoidentificacionremitente"    => $tipoidentificacionremitente, //"NIT", //siempre nit nasbi
                "identificacionremitente"        => $identificacionremitente, //901405511,

                "sederemitente"                  => "",
                "primernombreremitente"          => $data["data_user_remitente"]["nombre"],
                "segundonombreremitente"         => "",
                "primerapellidoremitente"        => "",
                "segundoapellidoremitente"       => "",
                "razonsocialremitente"           => $data["data_user_remitente"]["nombre"], //se puede poner el del usuario remitente real
                "naturalezaremitente"            => $naturalezaremitente,//"J",
                "contactoremitente"              => $data["data_user_remitente"]["nombre"],
                "direccionremitente"             => $data_direccion["direccion"],
                "emailremitente"                 => "Info@nasbi.com", //correo nasbi
                "telefonoremitente"              => 3142982745, //telefono nasbi
                "ciudadorigen"                   => $data_direccion["dane"],
                "tipoidentificaciondestinatario" => $data["data_user_destinatario"]["tipo_identificacion"],
                "identificaciondestinatario"     => $data["data_user_destinatario"]["identificacion"],
                "sededestinatario"               => "",
                "primernombredestinatario"       => $data["data_user_destinatario"]["nombre"],
                "segundonombredestinatario"      => "",
                "primerapellidodestinatario"     => "",
                "segundoapellidodestinatario"    => "",
                "razonsocialdestinatario"        => "",
                "naturalezadestinatario"         => $data["data_user_destinatario"]["naturaleza"],
                "direcciondestinatario"          => $data["direccion_destinatario"]["direccion"],
                "contactodestinatario"           => $data["data_user_destinatario"]["nombre"],
                "emaildestinatario"              => $data["data_user_destinatario"]["correo"],
                "telefonodestinatario"           => $data["data_user_destinatario"]["telefono"],
                "ciudaddestinatario"             => $data["direccion_destinatario"]["dane"],
                "barriodestinatario"             => "",
                "totalpeso"                      => "",
                "totalpesovolumen"               => "",
                "totalvalormercancia"            => "",
                "formapago"                      => "",
                "observaciones"                  => $observacion,
                "llevabodega"                    => "",
                "recogebodega"                   => "",
                "centrocostos"                   => "",
                "totalvalorproducto"             => "",
                "unidad" => array(
                    "tipounidad"       => "TIPO_UND_PAQ",
                    "tipoempaque"      => "",
                    "claseempaque"     => "CLEM_CAJA",
                    "dicecontener"     => "",
                    "kilosreales"      => $producto["Liquidacion"]["unidades"]["unidad"]["pesoreal"],
                    
                    // "largo"        => $producto["Liquidacion"]["unidades"]["unidad"]["largo"],
                    // "alto"         =>  $producto["Liquidacion"]["unidades"]["unidad"]["alto"],
                    // "ancho"        => $producto["Liquidacion"]["unidades"]["unidad"]["ancho"],

                    "pesovolumen"      =>  $producto["Liquidacion"]["unidades"]["unidad"]["pesovolumen"],
                    "valormercancia"   => $producto["Liquidacion"]["valormercancia"],
                    "codigobarras"     => "", // Antes iba en 1
                    "numerobolsa"      => "1",
                    "referencias"      => "1",
                    "unidadesinternas" => $cantidad_unidades_caja
                ) ,
                "documentoreferencia" => array(
                    "tipodocumento"   => "",
                    "numerodocumento" => "",
                    "fechadocumento"  => ""
                ) ,
                "generardocumentos"       => "TRUE",
                "tiposervicio"            => "",
                "numeroreferenciacliente" => "",
                "fuente"                  => "?"
            );
            
            // var_dump($data_enviar_wbs);


            parent::addLogTCC("----+> [ SEND / data_enviar_wbs ]: " . json_encode($data_enviar_wbs));
            

            $respuesta_despacho7_tcc = $objeto_tcc->grabar_despacho(array(
                "despacho" => $data_enviar_wbs
            ));

            parent::addLogTCC("----+> [ RECIBE / respuesta_despacho7_tcc ]: " . json_encode($respuesta_despacho7_tcc));
            
            $respuesta_despacho7_tcc["id_producto"]    = $producto["id_producto"];
            $respuesta_despacho7_tcc["unidad_negocio"] = $data["valores_envio"]["tipo_envio"];
            $respuesta_despacho7_tcc["recogida"]       = $data["data_front"]["recogida"];
            
            if (intval($respuesta_despacho7_tcc["recogida"]) == 1)
            {
                $respuesta_despacho7_tcc["fecha_inicio"] = $fechas_rangos_recogida["unix_inicio"];
            }
            else
            {
                $respuesta_despacho7_tcc["fecha_inicio"] = $fechas_rangos_recogida["unix_envio"];
            }
            $respuesta_despacho7_tcc["fecha_fin"] = $fechas_rangos_recogida["unix_fin"];
            //guardar datas
            array_push($array_guias_tcc_por_product, $respuesta_despacho7_tcc);

            if (isset($data_enviar_wbs["clave"]))
            {
                unset($data_enviar_wbs["clave"]);
            }
            array_push($array_data_enviada_to_tcc, $data_enviar_wbs);
        }
        //fin guardar datas
        if (count($array_guias_tcc_por_product) > 0)
        {
            $array_guias_tcc_por_product = $this->insertar_status_product($array_guias_tcc_por_product);
            return array(
                "status"              => "success",
                "mensage"             => "tcc respondio",
                "data"                => $array_guias_tcc_por_product,
                "data_enviada_to_tcc" => $array_data_enviada_to_tcc
            );
        }
        else
        {
            return array(
                "status"  => "fail",
                "mensage" => "tcc no respondio"
            );
        }
    }

    function tratar_data_de_valor_envio(Array $data)
    {
        $data_de_return_para_despacho = [];
        $data_se_envio_to_tcc = $data["data_enviada_a_tcc"]; // eso es un array de que cada posicion es la data que se envio de un producto a tcc
        $data_de_return_para_despacho["data_envio_productos"] = $data["data_enviada_a_tcc"];

        // if(count($data_se_envio_to_tcc)>1){
        //   $data_de_return_para_despacho["tipo_envio"]= 1;
        // }else{
        $data_de_return_para_despacho["tipo_envio"] = $data_se_envio_to_tcc[0]["Liquidacion"]["tipoenvio"];
        //  }
        $data_de_return_para_despacho["unidad_de_negocio"] = $data_de_return_para_despacho["tipo_envio"];

        if (intval($data_de_return_para_despacho["unidad_de_negocio"]) == 2)
        {
            $data_de_return_para_despacho["cuentaremitente"] = $this->unidad_negocio_mensajeria_des; //produccion 5784900;
            
        }
        else
        {
            $data_de_return_para_despacho["cuentaremitente"] = $this->unidad_negocio_paqueteria_des; //produccion 1779500;
            
        }

        return $data_de_return_para_despacho;
    }

    function get_product_por_id(Array $data)
    {
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT *, (precio-(precio*(porcentaje_oferta/100))) as precio_con_descuento FROM buyinbig.productos WHERE  id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos[0];
    }

    function transformar_fecha_U_to_normal(Array $data)
    {
        if ($data["tipo"] == 1)
        {
            return date("Y-m-d", intval($data["fecha"]));
        }
        else if ($data["tipo"] == 2)
        { //en la bd se guardan multiplicada por mil
            return date("Y-m-d", intval($data["fecha"]) / 1000);
        }
        else if ($data["tipo"] == 3)
        { //en la bd se guardan las de milisegundo
            $fecha_milisegundo = date("Y-m-d\TH:i:s", intval($data["fecha"]) / 1000);
            $fecha_milisegundo = strtotime('-1 hour', strtotime($fecha_milisegundo));
            $fecha_milisegundo = date("Y-m-d\TH:i:s", $fecha_milisegundo);
            return $fecha_milisegundo;
        }

    }

    function get_data_envio(Array $data)
    {
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * from productos_envio  WHERE  id_producto = '$data[id_producto]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos[0];

    }

    function get_tipo_envio(Array $data)
    {
        $cantidad_producto = intval($data["cantidad_producto"]);
        $data_producto = $data["data_producto"];
        $peso = floatval($data["data_envio"]["peso"]);
        $alto = floatval($data["data_envio"]["alto"]);
        $ancho = floatval($data["data_envio"]["ancho"]);
        $largo = floatval($data["data_envio"]["largo"]);
        $unidad_distancia_longitudes = $data["data_envio"]["unidad_distancia"]; //cm: centimetro in: pulgada
        $unidad_masa = $data["data_envio"]["unidad_masa"];
        $peso_mayor;
        $valor_mercancia_max = 3511208; //para mensajeria
        $alto_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros(['valor' => $alto, 'unidad' => $unidad_distancia_longitudes]);
        $ancho_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros(['valor' => $ancho, 'unidad' => $unidad_distancia_longitudes]);
        $largo_convertido_metros = $this->convertir_medidas_longitud_nasbi_to_metros(['valor' => $largo, 'unidad' => $unidad_distancia_longitudes]);

        $peso_volumetrico = $this->calculo_peso_volumetrico_tcc(['alto' => $alto_convertido_metros, 'ancho' => $ancho_convertido_metros, 'largo' => $largo_convertido_metros]);
        $peso_real_producto_kilogramo = $this->convertir_medidas_masa_nasbi_kilo(['valor' => $peso, 'unidad' => $unidad_masa]);

        if ($peso_volumetrico > $peso_real_producto_kilogramo)
        { // esto se hace porque tcc cobra el valor a cual peso es mayor
            $peso_mayor = $peso_volumetrico * $cantidad_producto;
        }
        else
        {
            $peso_mayor = $peso_real_producto_kilogramo * $cantidad_producto;
        }

        $peso_mayor = ceil($peso_mayor);

        $numero_unidades = $this->obtener_numero_unidades($peso_mayor);

        $valor_mercancia = $cantidad_producto * floatval($data_producto["precio_con_descuento"]);

        $peso_volumetrico_total = ceil($peso_volumetrico * $cantidad_producto); 

        //&& $cantidad_producto==1
        if ($peso_mayor <= 5 && floatval($data_producto["precio_con_descuento"]) < $valor_mercancia_max && $numero_unidades == 1)
        { //falta por precio de mercancia
            return array(
                "unidad_negocio" => 2,
                "peso_mayor" => $peso_mayor,
                "numero_unidades" => $numero_unidades,
                "valor_mercancia" => $valor_mercancia,
                "valor_peso_volumetrico" => $peso_volumetrico_total
            ); //mensajeria
            
        }
        else
        {
            return array(
                "unidad_negocio" => 1,
                "peso_mayor" => $peso_mayor,
                "numero_unidades" => $numero_unidades,
                "valor_mercancia" => $valor_mercancia,
                "valor_peso_volumetrico" => $peso_volumetrico_total
            ); //mensajeria //paqueteria
            
        }

    }

    function convertir_medidas_longitud_nasbi_to_metros(Array $data)
    {
        $valor = 0;
        if ($data["unidad"] == "in")
        { //pulgadas a metro
            $valor = $data["valor"] / 39.370;
        }
        else if ($data["unidad"] == "cm")
        { //centimetros a metros
            $valor = $data["valor"] / 100;
        }
        return $valor;
    }

    function calculo_peso_volumetrico_tcc(Array $data)
    {
        $valor;
        $ancho = $data["ancho"];
        $largo = $data["largo"];
        $alto = $data["alto"];

        $valor = $ancho * $largo * $alto * 400;
        return $valor;

    }

    function convertir_medidas_masa_nasbi_kilo(Array $data)
    {
        $peso = $data["valor"];
        $unidad = $data["unidad"];
        $valor = $peso;
        if ($unidad == "lb")
        { //por si no es kilogramo directamente
            $valor = $peso / 2.246;
        }

        return $valor;
    }

    function get_valores_cm_medidas(Array $data)
    {
        $alto = $data["alto"];
        $ancho = $data["ancho"];
        $largo = $data["largo"];
        $unidad = $data["unidad"];
        if ($unidad == "in")
        {
            $alto = $alto / 0.39370;
            $ancho = $ancho / 0.39370;
            $largo = $largo / 0.39370;
        }

        return array(
            "alto" => $alto,
            "ancho" => $ancho,
            "largo" => $largo
        );
    }

    function validacion_nacionalidad_producto_data_general(Array $data_producto, Array $data_envio)
    {

        if ($data_producto["moneda_local"] != "COP")
        {
            return array(
                "acceso_consulta" => false,
                "mensaje" => "el precio del producto no es COP",
                "status" => "NoPaisProducto"
            );
        }
        else
        {
            $data_direccion = $this->get_data_direccion(["id" => $data_producto["id_direccion"]]);
            if (isset($data_direccion["dane"]) && !empty($data_direccion["dane"]))
            {

                return array(
                    "acceso_consulta" => true,
                    "mensaje" => "producto apto para consultar",
                    "codigo_dane" => $data_direccion["dane"]
                );

            }
            else
            {
                //hay que cambiar el valor true por false en acceso a consulta  mas adelante
                return array(
                    "acceso_consulta" => false,
                    "mensaje" => "la direccion de este producto no tiene codigo dane",
                    "codigo_dane" => "",
                    "status" => "NoDane"
                );
            }

        }
    }

    function mapeo_y_suma_de_valores_envio(Array $data)
    {
        $total_de_valores_envio = 0;
        $total_de_valores_volumen = 0;

        foreach ($data as $key => $producto)
        {
            if (isset($producto["consultarliquidacion2Result"]
                ->total
                ->totaldespacho) && $producto["status"] == "success")
            {
                $id_producto = $producto["id_producto"];
                if (isset($producto['consultarliquidacion2Result']
                    ->total
                    ->totaldespacho))
                {
                    $total_de_valores_envio = $total_de_valores_envio + floatval($producto["consultarliquidacion2Result"]
                        ->total
                        ->totaldespacho);
                    $total_de_valores_volumen = $total_de_valores_volumen + floatval($producto["consultarliquidacion2Result"]
                        ->total
                        ->totalpesovolumen);
                }
                $data[$key]["consultarliquidacion2Result"]
                    ->total->totaldespacho_mask = $this->maskNumber(floatval($producto["consultarliquidacion2Result"]
                    ->total
                    ->totaldespacho) , 2);
                $data[$key]["consultarliquidacion2Result"]
                    ->total->valor_envio_moneda_local = $producto["consultarliquidacion2Result"]
                    ->total->totaldespacho;
                $data[$key]["consultarliquidacion2Result"]
                    ->total->valor_envio_moneda_local_mask = $this->maskNumber(floatval($producto["consultarliquidacion2Result"]
                    ->total
                    ->totaldespacho) , 2);
            }
            else if ($producto["status"] == "fail")
            {
                $respuesta = new stdClass();
                $total = new stdClass();
                $contenedor = new stdClass();

                $respuesta = $data[$key]["consultarliquidacion2Result"]->respuesta;

                $total->valor_envio_moneda_local = 0;
                $total->valor_envio_moneda_local_mask = 0;
                $total->valortarifa = 0;
                $total->totalpesofacturado = 0;
                $total->totalpesoreal = 0;
                $total->totalpesovolumen = 0;
                $total->totalunidades = 0;
                $total->totaldespacho = 0;
                $total->totaldespacho_mask = 0;
                $total->totaldespacho_mask = 0;

                $contenedor->respuesta = $respuesta;
                $contenedor->total = $total;
                $total_de_valores_envio = 0;
                $total_de_valores_volumen = 0;
                $data[$key]["consultarliquidacion2Result"] = $contenedor;
            }

        }

        return array(
            "total_de_valor_envio_mask" => $this->maskNumber($total_de_valores_envio, 2) ,
            "data_tcc_de_cada_producto" => $data,
            "total_de_valor_envio" => $total_de_valores_envio,
            "valor_envio_total_moneda_local" => $total_de_valores_envio,
            "valor_envio_total_moneda_local_mask" => $this->maskNumber($total_de_valores_envio, 2) ,
            "total_de_peso_volumen" => $total_de_valores_volumen
        );

    }

    function maskNumber(Float $numero, Int $prec = 2)
    {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }

    function truncNumber($number, $prec = 2)
    {
        return sprintf("%." . $prec . "f", floor($number * pow(10, $prec)) / pow(10, $prec));
    }

    function get_data_direccion(Array $data)
    {
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * FROM direcciones where id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        if (count($direccion) > 0)
        {
            return $direccion[0];
        }
        return $direccion;

    }

    function datosUserGeneral(Array $data)
    {
        $result = $this->datosUser_to_tcc($data);
        // unset($nasbifunciones);
        return $result;
    }

    function get_direccion_activa_por_uid(Array $data)
    {
        parent::conectar();
        $direccion = parent::consultaTodo("SELECT * from direcciones where uid= '$data[uid]' and empresa = '$data[empresa]' and activa = 1 ORDER BY id DESC; ");
        parent::cerrar();
        if (count($direccion) > 0)
        {
            return $direccion[0];
        }
        return $direccion;

    }

    function datosUser_to_tcc(Array $data)
    {
        // No deben de existir conexiones abiertas antes de llamar a esta función
        // de llegar a existirlo se debe cerrar antes de llamar a esta funcion.
        parent::conectar();
        $selectxuser = null;
        if ($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if ($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = '1'";
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();

        if (count($usuario) <= 0) return array(
            'status' => 'fail',
            'message' => 'no user',
            'data' => $data
        );

        $usuario = $this->mapUsuarios_to_tcc($usuario, $data['empresa']);
        return array(
            'status' => 'success',
            'message' => 'user',
            'data' => $usuario[0]
        );
    }
    function mapUsuarios_to_tcc(Array $usuarios, Int $empresa)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $datafoto = null;
        $dataPaso = null;
        $dataIdioma = null;
        $data_uid = null;
        $data_identificacion = null;
        $data_tipo_identificacion = null;
        $data_naturaleza = null;
        foreach ($usuarios as $x => $user)
        {

            if (!isset($user['idioma']))
            {
                $user['idioma'] = "ES";
            }
            if ($empresa == 0)
            {
                $datanombre = $user['nombreCompleto'];
                $dataempresa = $user['nombreCompleto']; //"Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto = $user['avatar'];
                $dataIdioma = strtoupper($user['idioma']);
                $data_uid = $user["id"];
                $data_identificacion = $user["numero_identificacion"];
                $data_tipo_identificacion = "CC";
                $data_naturaleza = "N";

            }
            else if ($empresa == 1)
            {
                $datanombre = $user['razon_social'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..." ? "" : $user['foto_logo_empresa']);
                $dataIdioma = strtoupper($user['idioma']);
                $data_uid = $user["id"];
                $data_identificacion = $user["nit"];
                $data_tipo_identificacion = "NIT";
                $data_naturaleza = "J";
            }
            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto'] = $datafoto;
            $user['empresa_bool'] = $empresa;
            $user['idioma'] = "ES";
            $user['uid'] = $data_uid;
            $user['naturaleza'] = $data_naturaleza;
            $user['identificacion'] = $data_identificacion;
            $user['tipo_identificacion'] = $data_tipo_identificacion;
            $usuarios[$x] = $user;
        }
        return $usuarios;
    }
   
/*
    function envio_de_data_to_tcc(Array $array_ciudades_origen, Array $producto_actos_para_consultar_to_tcc, Array $datageneral)
    {
        $clave_to_tcc = "CLIENTETCC608W3A61CJ"; //la clave CLIENTETCC608W3A61CJ es la de prueba
        $objeto_tcc = new Tcc();
        $datas_respuestas_tcc_llamados = [];
        $valor_mercancia = null;
        $unidades = [];
        foreach ($array_ciudades_origen as $key => $ciudad_origen)
        {
            $productos_de_ciudad_actual = [];
            foreach ($producto_actos_para_consultar_to_tcc as $key => $producto_apto)
            {
                if ($producto_apto["ciudad_origen"] == $ciudad_origen)
                {
                    array_push($productos_de_ciudad_actual, $producto_apto);
                }
            }
            if (count($productos_de_ciudad_actual) > 0)
            {
                $valor_mercancia = 0;
                $unidades = [];
                $cantidad_unidades_ciudad_actual;
                foreach ($productos_de_ciudad_actual as $key => $producto)
                {
                    $unidad = [];
                    $unidad = ["numerounidades" => 1, "pesoreal" => $producto["peso_real"], "pesovolumen" => "", "alto" => $producto["valores_longitudes"]["alto"], "largo" => $producto["valores_longitudes"]["largo"], "ancho" => $producto["valores_longitudes"]["ancho"], "tipoempaque" => ""

                    ];
                    $valor_mercancia = $valor_mercancia + $producto["valor_producto"];
                    array_push($unidades, $unidad);

                    // $data_respuesta_llamado_tcc = $objeto_tcc->consultar_liquidacion_wbs_tcc($data_to_tcc);
                    // array_push($datas_respuestas_tcc_llamados, $data_respuesta_llamado_tcc);
                    
                }
                $data_to_llamado_tcc = [];
                $data_to_llamado_tcc = ["Clave" => $clave_to_tcc, "Liquidacion" => array(
                    "tipoenvio" => 1,
                    "idciudadorigen" => $ciudad_origen,
                    "idciudaddestino" => $datageneral["ciudad_destino"],
                    "valormercancia" => $producto["valor_producto"],
                    "fecharemesa" => $datageneral["fecha_remesa"],
                    "idunidadestrategicanegocio" => $datageneral["tipo_envio"],
                    "unidades" => array(
                        "unidad" => $unidades
                    )
                ) ];

            }
            else
            {
                var_dump("esto no deberia pasar");
            }

            var_dump($datas_respuestas_tcc_llamados);

        }
    }
    */

    function seguimiento_product(Array $data)
    {
        $numeros_guias_product = [];
        $numeros_guias_product = $data;
        foreach ($numeros_guias_product as $key => $numero_guia)
        {
        }
    }

    function saber_todos_los_campos_no_ready($data)
    {
        if (!isset($data['user_uid']) || !isset($data['user_empresa'])) return array(
            "status" => "fail_parametro",
            "mensage" => "falta parametro"
        );
        $array_mensaje = [];
        $bandera = 0;
        $data_personal = $this->get_dato_personal_user(["uid" => $data['user_uid'], "empresa" => $data['user_empresa']]);

        if (empty($data_personal)) return array(
            "status" => "fail_no_registro",
            "mensage" => "el usuario no presenta registro no tiene ningun dato en la tabla"
        );

        $array_propiedades = array_keys($data_personal);

        foreach ($array_propiedades as $x => $propiedad)
        {
            if (empty($data_personal[$propiedad]) || !isset($data_personal[$propiedad]))
            {
                $bandera = 1;
                array_push($array_mensaje, "falta el campo" + " " + $propiedad);
            }
        }

        if ($bandera == 0)
        {
            return array(
                "status" => "success",
                "mensage" => "no le falta ningun campo",
                "data" => null
            );
        }
        else if ($bandera == 1)
        {
            return array(
                "status" => "success_Campo_faltante",
                "mensage" => "faltan campos",
                "data" => $array_mensaje,
                "campos_faltantes" => count($array_mensaje)
            );
        }

    }

    function saber_todos_los_campos_no_ready2($data)
    {
        if (!isset($data['user_uid']) || !isset($data['user_empresa'])) return array(
            "status" => "fail_parametro",
            "mensage" => "falta parametro"
        );
        $array_mensaje = [];
        $bandera = 0;
        $data_personal = $this->get_dato_personal_user(["uid" => $data['user_uid'], "empresa" => $data['user_empresa']]);

        if (empty($data_personal)) return array(
            "status" => "fail_no_registro",
            "mensage" => "el usuario no presenta registro no tiene ningun dato en la tabla"
        );

        $array_propiedades = array_keys($data_personal);

        foreach ($array_propiedades as $x => $propiedad)
        {
            if (empty($data_personal[$propiedad]) || !isset($data_personal[$propiedad]))
            {
                $bandera = 1;
                array_push($array_mensaje, "falta el campo" + " " + $propiedad);
            }
        }

        if ($bandera == 0)
        {
            return array(
                "status" => "success",
                "mensage" => "no le falta ningun campo",
                "data" => null
            );
        }
        else if ($bandera == 1)
        {
            return array(
                "status" => "success_Campo_faltante",
                "mensage" => "faltan campos",
                "data" => $array_mensaje,
                "campos_faltantes" => count($array_mensaje)
            );
        }

    }

    function get_dato_personal_user(Array $data)
    {
        parent::conectar();
        $dato_usuario = parent::consultaTodo("SELECT * FROM buyinbig.datos_persona_natural WHERE  user_uid = '$data[uid]' and  user_empresa = '$data[empresa]'  ORDER BY id DESC; ");
        parent::cerrar();
        if (count($dato_usuario) <= 0)
        {
            return $dato_usuario;
        }
        else
        {
            return $dato_usuario[0];
        }

    }

    function insertar_status_product(Array $data)
    {
        foreach ($data as $x => $producto)
        {
            if (isset($producto["respuesta"]) && isset($producto["mensaje"]))
            {
                if (intval($producto["respuesta"]) == 0)
                {
                    $data[$x]["status"] = "success";
                    $data[$x] = $this->cambiar_572_to_575_rotulo($data[$x]); 
                }
                else
                {
                    $data[$x]["status"] = "fail";
                }

            }
            else
            {
                $data[$x]["status"] = "fail";
            }
        }
        return $data;
    }

    function agregar_status_a_respuesta_tcc_consulta(Array $data)
    {
        $data_aux = $data["consultarliquidacion2Result"]->respuesta;
        if (intval($data_aux->codigo) != 0)
        {
            return "fail";
        }
        return "success";

    }

    function truncar_tabla()
    {
        //TRUNCATE TABLE "nombre_tabla";
        $truncar_banner = "TRUNCATE TABLE productos_transaccion_guia_tcc";
        parent::conectar();
        parent::query($truncar_banner);
        parent::cerrar();
    }

    function tracking(Array $data)
    {
        //remesas es igual a  guias
        if (!isset($data['id_transancion'])) return array(
            "status" => "fail_parametro",
            "mensage" => "falta parametro"
        );
        $guias_de_esta_transancion = $this->traer_guias_transanccion(array(
            "id_trasanccion" => $data['id_transancion']
        ));
        if (count($guias_de_esta_transancion) == 0)
        {
            return array(
                "status" => "fail",
                "mensage" => "falta parametro"
            );
        }
        return $array_estados_de_remesa = $this->consultar_remesa_de_producto($guias_de_esta_transancion);

    }

    function consultar_remesa_de_producto($guias_de_esta_transancion)
    {
        $array_respuesta_trackin = [];
        $array_data_to_tcc = [];
        $objeto_tcc = new Tcc();
        $clave_to_tcc = $this->clave_despacho;
        foreach ($guias_de_esta_transancion as $key => $remesa)
        {
            $data_to_tcc = [];
            $data_to_tcc = ["Clave" => $clave_to_tcc, "remesas" => ["RemesaUEN" => ["numeroremesa" => $remesa["remesa"], "unidadnegocio" => 1]], "Respuesta" => 0, "Mensaje" => "ok"

            ];

            $data_respuesta_tcc = $objeto_tcc->tracking($data_to_tcc);
            if (isset($data_to_tcc["Clave"]))
            {
                unset($data_to_tcc["Clave"]);
            }
            $data_respuesta_tcc["id_producto"] = $remesa["id_producto"];
            $data_respuesta_tcc["id_productos_transaccion"] = $remesa["id_productos_transaccion"];
            $data_respuesta_tcc["status"] = $this->agregar_status_resultado_tracking($data_respuesta_tcc);

            array_push($array_respuesta_trackin, $data_respuesta_tcc);
            array_push($array_data_to_tcc, $data_to_tcc);
        }
        return array(
            "array_respuesta_tcc" => $array_respuesta_trackin,
            "array_data_enviada_to_tcc" => $array_data_to_tcc
        );

    }

    function agregar_status_resultado_tracking(Array $data)
    {
        if (isset($data["Respuesta"]))
        {
            if (intval($data["Respuesta"]) == 0)
            {
                return "success";
            }
            else
            {
                return "fail";
            }
        }
        else
        {
            return "fail";
        }
    }

    function traer_guias_transanccion(Array $data)
    {
        $registro_tcc_guias = [];
        parent::conectar();
        $registro_tcc_guias = parent::consultaTodo("SELECT * FROM productos_transaccion_guia_tcc WHERE  id_productos_transaccion = $data[id_trasanccion] ORDER BY id DESC; ");
        parent::cerrar();

        if (!isset($registro_tcc_guias))
        {
            return [];
        }
        else
        {
            if (count($registro_tcc_guias) == 0)
            {
                return [];
            }
            else
            {
                return $registro_tcc_guias;
            }
        }

    }

    function traer_guias_consulta(Array $data)
    {
        //remesas es igual a  guias
        if (!isset($data['id_transancion'])) return array(
            "status" => "fail_parametro",
            "mensage" => "falta parametro"
        );
        $guias_de_esta_transancion = $this->traer_guias_transanccion(array(
            "id_trasanccion" => $data['id_transancion']
        ));
        if (count($guias_de_esta_transancion) == 0)
        {
            return array(
                "status" => "fail",
                "mensage" => "no data guia",
                "data" => []
            );
        }
        return array(
            "status" => "success",
            "mensage" => "data guia",
            "data" => $guias_de_esta_transancion
        );

    }

    function truncar_tabla_guias()
    {
        //productos_transaccion_guia_tcc
        
    }

    function ajustar_text_observacion(Array $data, int $id_carrito)
    {
        //REF: #1 - ZAPATOS BLA BLA BLA
        $texto_observacion = "REF: #" . $id_carrito . " - " . $data["titulo"];
        return $texto_observacion;
    }

    function obtener_numero_unidades($peso_mayor)
    {
        //cada 30 kilos es una caja
        return ceil($peso_mayor / 30);
    }

    function determinar_unidad_negocio(int $tipo_envio)
    {
        if ($tipo_envio == 1)
        {
            return $this->unidad_negocio_paqueteria; //paqueteria
            
        }
        else
        {
            return $this->unidad_negocio_mensajeria; //mensajeria
            
        }
    }

    function obtener_valores_fecha_tcc($opcion_recogida)
    {
        $nasbifunciones = new Nasbifunciones();
        $fechas = $nasbifunciones->getFechaRecogidaTcc($opcion_recogida);
        return $fechas;

    }

    function traer_info_recibida_de_tcc_guia($data){
        if (!isset($data) || !isset($data["id_transanccion"]) ) return array(
            'status' => 'fail',
            'message' => 'faltan datos',
            'data' => null
        );
        $guias_de_esta_transancion = $this->traer_guias_transanccion(array(
            "id_trasanccion" => $data['id_transanccion']
        ));

        if (count($guias_de_esta_transancion) > 0)
        {
            return array(
                "status" => "success",
                "mensaje" => "data success",
                "data" => $guias_de_esta_transancion
            );
        }else{
            return array(
                "status" => "fail",
                "mensaje" => "no data",
                "data" => $guias_de_esta_transancion
            );
        }

    }


    function cambiar_572_to_575_rotulo(Array $data){
        $data["urlrotulos"] =  str_replace(",572", ",575", $data["urlrotulos"]) ; 
        return $data;
    }
}

?>
