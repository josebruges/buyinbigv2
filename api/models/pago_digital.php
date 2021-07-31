<?php
require 'nasbifunciones.php';

class PagoDigital extends Conexion
{

    private $MONTO_MINIMO_PD                       = 30000;
    private $TRANSACCION_COMPRA_Y_VENTA            = 1;
    private $TRANSACCION_RECARGA                   = 3;


    private $TRANSACCION_ADQUIRIR_TIQUETES_COMPRAS = 4;
    private $TRANSACCION_ADQUIRIR_TIQUETES_VENTAS  = 4;

    private $TRANSACCION_ADQUIRIR_PLANES_COMPRAS   = 5;
    private $TRANSACCION_ADQUIRIR_PLANES_VENTAS    = 6;
    

    private $ESTADOS_TRANSACCIONES = array(
        'EN_PROCESO' => 0,
        'FINALIZA'   => 1,
        'RECHAZADA'  => 2,
        'VENCIDA'    => 3,
        'NO_EXISTE'  => 3,
    );

    // ENTORNO DE PRODUCCIÓN
    private $NUMERO_CONVENIO_EFECTY        = 111217;
    private $ID_PROVEEDOR_NASBI_PRODUCCION = 1327; // SIEMPRE ES NASBI - NO SE DETALLA FLETE PORQUE TODO VA PARA NASBI.
    private $API_KEY_PRODUCCION            = "HK40DPG01VAKDF6PTMVF";
    private $API_SECRET_PRODUCCION         = "AOZ3KMVYEEXLJ9OMXPEC";
    private $BASE_URL_PRODUCCION           = "https://www.pagodigital.co/WS_NASBI/API";

    // ENTORNO DE PRUEBAS
    private $BASE_URL_PRUEBAS              = "https://www.pagodigital.co/WS_NASBI/API_TEST";

    private $PagoDigitalVars = array(
        'BASE_URL'           => "https://www.pagodigital.co/WS_NASBI/API_TEST",

        'ID_PROVEEDOR_NASBI' => 1145,

        'JWT_USER'           => "YRAARAVJKIJJNF5ERWFS",
        'JWT_PASS'           => "8YCNEBLI2FXG9FWG03B1",

        'API_KEY'            => "1SLCFBVOBI8MDSRVXDS1",
        'API_SECRET'         => "N4DTFDN3FEUQYFJWV98X",

        'CODIGO_ENTIDAD'     => 9004617586,
        'CODIGO_SERVICIO'    => 9004617586,

        'URL_RESPUESTA'      => "https://nasbi.com/content/callback-pse.php",
        'URL_RESPUESTA_PSE'  => "https://nasbi.com/content/callback-pse.php"
    );

    public function __construct(){
        $this->PagoDigitalVars['ID_PROVEEDOR_NASBI'] = $this->ID_PROVEEDOR_NASBI_PRODUCCION;

        $this->PagoDigitalVars['API_KEY']            = $this->API_KEY_PRODUCCION;
        $this->PagoDigitalVars['API_SECRET']         = $this->API_SECRET_PRODUCCION;

        $this->PagoDigitalVars['BASE_URL']           = $this->BASE_URL_PRODUCCION;
        $this->BASE_URL_PRUEBAS                      = "https://www.pagodigital.co/WS_NASBI/API_TEST";

        // $this->PagoDigitalVars['URL_RESPUESTA']      = "http://localhost/MisProyectos/ProyectoNasbi2020/content/callback-pse.php";
        // $this->PagoDigitalVars['URL_RESPUESTA_PSE']  = "http://localhost/MisProyectos/ProyectoNasbi2020/content/callback-pse.php";
    }


    function insertarBurritaDigital(Array $data)
    {
        if(!isset($data['usuario']) || !isset($data['password']) || !isset($data["medio_pago"]) || !isset($data["id_referencia"]) || !isset($data["estado"]) || !isset($data["ip"]) || !isset($data["token"])) return array('status' => 'fail', 'message'=> 'faltan datos');

        
        return array('status' => 'success', 'message'=> 'Guardado correctamente');
    }

    function crearProveedor( Array $data )
    {
        if( !isset($data) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['PROVEEDOR_ID'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $data
            );
        }

        parent::addLogPD("1. Pago digital / crearProveedor / data: " . json_encode($data));

        $selectxproveedores = "SELECT * FROM buyinbig.pago_digital_proveedores WHERE UID = $data[UID] AND EMPRESA = $data[EMPRESA];";
        parent::conectar();
        $selectxproveedores = parent::consultaTodo($selectxproveedores);
        parent::cerrar();

        $fecha = $this->getTimestamp();

        parent::addLogPD("2. Pago digital / crearProveedor / validando existencia: " . (count( $selectxproveedores ) == 0 ? "SI" : "No"));
        if( count( $selectxproveedores ) == 0 ){
            // Debe agregar el nuevo proveedor.


            $insert =
            "INSERT INTO  buyinbig.pago_digital_proveedores(
                UID,
                EMPRESA,
                PROVEEDOR_ID,
                ESTADO,
                FECHA_CREACION,
                FECHA_ACTUALIZACION
            )VALUES(
                $data[UID],
                $data[EMPRESA],
                $data[PROVEEDOR_ID],
                1,
                $fecha,
                $fecha
            );";
            parent::conectar();
            $rowAfect = parent::queryRegistro($insert);
            parent::cerrar();
            
            if($rowAfect > 0){
                // nuevo dato almacenado.
                $selectxproveedores = "SELECT * FROM buyinbig.pago_digital_proveedores WHERE UID = $data[UID] AND EMPRESA = $data[EMPRESA];";
                parent::conectar();
                parent::consultaTodo($selectxproveedores);
                parent::cerrar();
                return array(
                    'status'  => 'success',
                    'message' => 'Nuevo dato almacenado',
                    'data'    => $selectxproveedores[0]
                );
                parent::addLogPD("3. Pago digital / crearProveedor / INSERTADO: " . json_encode($selectxproveedores[0]));
            }else{
                parent::addLogPD("4. Pago digital / crearProveedor / NO INSERTADO: " . json_encode($data));
            }
        }else{
            $proveedor = $selectxproveedores[0];

            parent::addLogPD("5. Pago digital / crearProveedor / ACTUALIZANDO: " . json_encode($proveedor));

            $updatexproveedor = 
                "UPDATE buyinbig.pago_digital_proveedores
                SET
                    PROVEEDOR_ID = $data[PROVEEDOR_ID]
                WHERE UID = $data[UID] AND EMPRESA = $data[EMPRESA];";

            parent::conectar();
            $rowAfect = parent::query($updatexproveedor);
            parent::cerrar();
            
            if($rowAfect > 0){
                // nuevo dato almacenado.
                $proveedor['PROVEEDOR_ID'] = $data['PROVEEDOR_ID'];

                return array(
                    'status'  => 'update',
                    'message' => "Se actualizo el registro de: $proveedor[PROVEEDOR_ID] a $data[PROVEEDOR_ID]",
                    'data'    => $proveedor
                );
                parent::addLogPD("6. Pago digital / crearProveedor / ACTUALIZADO: " . json_encode($proveedor));
            }else{
                parent::addLogPD("7. Pago digital / crearProveedor / NO ACTUALIZADO: " . $updatexproveedor);
            }
        }

        parent::addLogPD("8. Pago digital / crearProveedor / NO HIZO NINGUNA MODIFICACION: " . json_encode($data));
        return array(
            'status'  => 'errorNoregistroProveedor',
            'message' => 'No se realizo ningun registro',
            'data'    => null
        );
    }

    // Implementación servicios para 'PAGO DIGITAL': https://carevalo.gitbook.io/api-pse-nasbi/
    function generateToken()
    {
        $URL = "https://www.pagodigital.co/TKN/GENERATE_TOKEN/";
        $dataSend = array(
            'JWT_USER' => $this->PagoDigitalVars['JWT_USER'],
            'JWT_PASS' => $this->PagoDigitalVars['JWT_PASS']
        );

        $response = parent::remoteRequestPagoDigital($URL, $dataSend);
        if ( $response['STATUS'] != "200" ) {
            return $response = array(
                'status'     =>'fail',
                'message'    =>'Información token',
                'data'       => null,
                'dataExtra'  => $response
            );
        }
        return $response = array(
            'status'     =>'success',
            'message'    =>'Información token',
            'data'       => $response['TOKEN'],
            'dataExtra'  => $response
        );
    }

    // IMPLEMENTANDO PSE
    function getBankList()
    {
        // Se encarga de listar todos los bancos permitidos para pagos en PSE

        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/PSE/GET_BANK_LIST/';
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $dataSend = array(
            'JWT'             => $TOKEN,
            'API_KEY'         => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET'      => $this->PagoDigitalVars['API_SECRET'],
            'CODIGO_ENTIDAD'  => $this->PagoDigitalVars['CODIGO_ENTIDAD'],
            'CODIGO_SERVICIO' => $this->PagoDigitalVars['CODIGO_SERVICIO']
        );

        $response = parent::remoteRequestPagoDigital( $URL, $dataSend );
        if ( isset( $response['PD87'] ) ) {
            return array(
                'status'     =>'fail',
                'message'    =>'No fue posible obtener el listado de bancos.',
                'data'       => $response
            );
        }else if ( is_array( $response ) ) {
            return array(
                'status'     =>'success',
                'message'    =>'Listado de bancos permitidos.',
                'data'       => $response
            );
        }
    }
    function obtenerDatosPSE()
    {
        // Obtiene todos los datos para implementar la vista "PAGO PSE".

        $RESULT_BANCOS = $this->getBankList();
        if ( $RESULT_BANCOS['status'] != 'success') {
            return $RESULT_BANCOS;
        }
        $BANCOS = $RESULT_BANCOS['data'];
        
        parent::conectar();
        $selectxTablaxTipoxPersona = "SELECT * FROM buyinbig.documento_identificacion;";
        $TablaTipoPersona = parent::consultaTodo($selectxTablaxTipoxPersona);
        parent::cerrar();

        if ( count( $TablaTipoPersona ) <= 0) {
            return array(
                'status'     => 'failPreCarga',
                'message'    => 'No fue posible obtener el listado de tipos de documento_identificacion.',
                'data'       => $TablaTipoPersona
            );            
        }

        return array(
            'status'     =>'success',
            'message'    =>'Listados para front-end data PSE.',
            'data'       => array(
                'bancos'            => $BANCOS,
                'tipos_documentos'  => $TablaTipoPersona
            )
        );
    }


    // Generador de ordenes de compras
    function generateOrdenPago( Array $data )
    {
        if( !isset($data) || !isset( $data['REF_LOCAL'] ) || !isset( $data['TOTAL_COMPRA'] ) || !isset( $data['PRECIO_USD'] ) || !isset( $data['TIPO'] ) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['ISO_CODE_2'] ) || !isset( $data['JSON_PROVEEDORES'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $data
            );
        }

        $METODO_PAGO_USADO_ID = 1;
        if( isset($data['METODO_PAGO_USADO_ID']) ) {
            $METODO_PAGO_USADO_ID = intval( $data['METODO_PAGO_USADO_ID'] );
            if ( $METODO_PAGO_USADO_ID == 0 ) {
                $METODO_PAGO_USADO_ID = 1;
            }
        }

        $fecha = $this->getTimestamp();

        $TOTAL_COMPRA = floatval($data['TOTAL_COMPRA']);
        if ( $TOTAL_COMPRA <= 0 ) {
            return array(
                'status'  => 'errorMontoTotalCompra',
                'message' => 'El total de la compra es menor o igual a cero: [ ' . $TOTAL_COMPRA . ' ]. Asegurate de que este valor no incluya la mascará. ',
                'data'    => $data
            );
        }else if ( $TOTAL_COMPRA <= $this->MONTO_MINIMO_PD ) {
            return array(
                'status'  => 'errorMontoMinimoTotalCompra',
                'message' => 'El total de la compra es menor o igual a cero: [ ' . $TOTAL_COMPRA . ' ]. Asegurate de que este valor no incluya la mascará. ',
                'data'    => $data,
                'minimo' => $this->MONTO_MINIMO_PD
            );
        }
 
        $data['UID']     = intval( $data['UID'] );
        $data['EMPRESA'] = intval( $data['EMPRESA'] );
        
        $REF_LOCAL = "";
        if ( isset( $data['REF_LOCAL'] ) ) {
            $REF_LOCAL = $data['REF_LOCAL'];
        }    
        $JSON_SOLICITUD = "";
        if ( isset( $data['JSON_SOLICITUD'] ) ) {
            $JSON_SOLICITUD = json_encode($data['JSON_SOLICITUD']);
            $JSON_SOLICITUD = addslashes( $JSON_SOLICITUD );
        }

        $PRECIO_USD = 0;
        if ( isset( $data['PRECIO_USD'] ) ) {
            $PRECIO_USD = $data['PRECIO_USD'];
        }

        $NOMBRE_COMPRADOR    = "";
        $CEDULA_COMPRADOR    = "";
        $TELEFONO_COMPRADOR  = "";
        $CORREO_COMPRADOR    = "";
        $DIRECCION_COMPRADOR = "";

        $TIPO                = intval($data['TIPO']);
        $TOTAL_COMPRA        = $TOTAL_COMPRA;
        $PRECIO_USD          = $PRECIO_USD;

        $CIUDAD              = "";
        $DEPARTAMENTO        = "";

        $TIPO_PERSONA        = "";
        $TIPO_DOCUMENTO      = "";

        $CODIGO_BANCO        = "";
        $NOMBRE_BANCO        = "";


        $resultDatosPersonales = $this->getUsuarioDatosPasarela( $data );

        if ( $resultDatosPersonales['status'] == 'fail' || $resultDatosPersonales['status'] == 'usuarioNoExiste' ) {
            return array(
                'status' => 'errorCredenciales',
                'message'=> 'Las credenciales del usuario no pertenecen a la plataforma'
            );
        }else{

            if ( $resultDatosPersonales['datos_usuario'] != null ) {
                $NOMBRE_COMPRADOR = addslashes($resultDatosPersonales['datos_usuario']['nombre']);
                $CORREO_COMPRADOR = $resultDatosPersonales['datos_usuario']['correo'];

                if ( $data['EMPRESA'] == 0 && $resultDatosPersonales['datos_usuario']['identificacion'] != null ) {
                    $CEDULA_COMPRADOR = $resultDatosPersonales['datos_usuario']['identificacion'];
                }
                if ( $data['EMPRESA'] == 0 && $resultDatosPersonales['datos_usuario']['telefono'] != null ) {
                    $TELEFONO_COMPRADOR = $resultDatosPersonales['datos_usuario']['telefono'];
                }
                if ( $data['EMPRESA'] == 0 ) {
                    $TIPO_DOCUMENTO = $resultDatosPersonales['datos_usuario']['tipo_identificacion'];
                }
            }
            if ( $resultDatosPersonales['form_pago_digital'] != null ) {
                
                if ( $data['EMPRESA'] == 1 ) {
                    // return $resultDatosPersonales['form_pago_digital'];
                    $TIPO_DOCUMENTO     = $resultDatosPersonales['form_pago_digital']['tipo_identificacion'];
                    $CEDULA_COMPRADOR   = $resultDatosPersonales['form_pago_digital']['no_identificacion'];
                    $TELEFONO_COMPRADOR = $resultDatosPersonales['form_pago_digital']['celular'];

                    $NOMBRE_COMPRADOR   = addslashes($resultDatosPersonales['form_pago_digital']['nombres'].' '.$resultDatosPersonales['form_pago_digital']['apellidos']);
                }
            }
            if ( $resultDatosPersonales['datos_residencia'] != null ) {
                $DIRECCION_COMPRADOR = addslashes($resultDatosPersonales['datos_residencia']['direccion']);
                $CIUDAD              = addslashes($resultDatosPersonales['datos_residencia']['TCC_POBLACION']);
                $DEPARTAMENTO        = addslashes($resultDatosPersonales['datos_residencia']['TCC_DEPARTAMENTO']);
                $COD_DANE            = $resultDatosPersonales['datos_residencia']['TCC_DANE'];
            }
        }

        if ( isset( $TELEFONO_COMPRADOR ) ) {
            $TELEFONO_COMPRADOR = str_replace('+57','',('' . $TELEFONO_COMPRADOR));
            $TELEFONO_COMPRADOR = str_replace(' ','',('' . $TELEFONO_COMPRADOR));
            $TELEFONO_COMPRADOR = intval('' . $TELEFONO_COMPRADOR );
        }
        
        if ( $data['EMPRESA'] == 0 ) {
            $TIPO_PERSONA = "N";
        }else{
            $TIPO_PERSONA = "J";
        }



        // Paso 1: Indificar si tiene todos los datos.
        $URL_CONFIRM = "http://nasbi.peers2win.com/api/controllers/siigo/?verificacion_data_cliente_con_siigo";
        $dataArray = array(
            "data" => array(
                "uid"     => $data['UID'],
                "empresa" => $data['EMPRESA']
            )
        );

        parent::addLog("\t-----+> [ Venta / Siigo / verificacion_data_cliente_con_siigo ]: " . json_encode($dataArray));

        $responseVerificarMapSiigo = parent::remoteRequest($URL_CONFIRM, $dataArray);
        $responseVerificarMapSiigo= json_decode($responseVerificarMapSiigo, true);

        parent::addLog("\t-----+> [ Venta / Siigo / responseVerificarMapSiigo ]: " . json_encode($responseVerificarMapSiigo));

        $posibilidades = array('success' ,'actualizar' ,'sinCambios' ,'crear');
        if( !in_array($responseVerificarMapSiigo['status'], $posibilidades) && $METODO_PAGO_USADO_ID != 1){

            $schemaNotificacion = Array(
                'uid'     => $data['UID'],
                'empresa' => $data['EMPRESA'],
                
                'text' => 'Antes de continuar es necesario completar algunos datos relacionados con tu documento de identificación en el módulo de Configuraciones, Datos personales.',

                'es' => 'Antes de continuar es necesario completar algunos datos relacionados con tu documento de identificación en el módulo de Configuraciones, Datos personales.',

                'en' => 'Before continuing, it is necessary to complete some information related to your identification document in the Settings, Personal Data module.',

                'keyjson' => '',

                'url' => 'mis-cuentas.php?tab=sidenav_configuracion'
            );
            
            $URL = "http://nasbi.peers2win.com/api/controllers/notificaciones_nasbi/?insertar_notificacion";

            parent::remoteRequest($URL, $schemaNotificacion);
            return array(
                'status'   => 'errorCompletarDatosfacturacionVendedor',
                'message'  => 'Se requieren datos para poder crear la factura en siigo',
                'data'     => $responseVerificarMapSiigo,
                'extra'    => in_array($responseVerificarMapSiigo['status'], $posibilidades)
            );
        }

        if ( $TIPO_DOCUMENTO != null ) {
            $TIPO_DOCUMENTO = str_replace('.','','' . $TIPO_DOCUMENTO);
        }

        $NOMBRE_COMPRADOR    = addslashes($NOMBRE_COMPRADOR);
        $DIRECCION_COMPRADOR = addslashes($DIRECCION_COMPRADOR);
        $CIUDAD              = addslashes($CIUDAD);
        $DEPARTAMENTO        = addslashes($DEPARTAMENTO);


        $ESTADOS_TRANSACCION_FINALIZADA = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];
        $schemaxInsertxPD = "INSERT INTO pago_digital (
            REF_LOCAL,
            TOTAL_COMPRA,
            PRECIO_USD,
            TIPO,
            UID,
            EMPRESA,
            NOMBRE_COMPRADOR,
            CEDULA_COMPRADOR,
            TELEFONO_COMPRADOR,
            CORREO_COMPRADOR,
            DIRECCION_COMPRADOR,
            CIUDAD,
            COD_DANE,
            DEPARTAMENTO,
            CODIGO_BANCO,
            NOMBRE_BANCO,
            TIPO_PERSONA,
            TIPO_DOCUMENTO,
            ISO_CODE_2,
            FECHA_CREACION,
            FECHA_ACTUALIZACION,
            METODO_PAGO_USADO_ID,
            TRANSACCION_FINALIZADA,
            JSON_SOLICITUD
        )  
        VALUES(
            '$REF_LOCAL',
            $TOTAL_COMPRA,
            $PRECIO_USD,
            $TIPO,
            $data[UID],
            $data[EMPRESA],
            '$NOMBRE_COMPRADOR',
            '$CEDULA_COMPRADOR',
            '$TELEFONO_COMPRADOR',
            '$CORREO_COMPRADOR',
            '$DIRECCION_COMPRADOR',
            '$CIUDAD',
            '$COD_DANE',
            '$DEPARTAMENTO',
            '',
            '',
            '$TIPO_PERSONA',
            '$TIPO_DOCUMENTO',
            '$data[ISO_CODE_2]',
            $fecha,
            $fecha,
            $METODO_PAGO_USADO_ID,
            $ESTADOS_TRANSACCION_FINALIZADA,
            '$JSON_SOLICITUD'
        )";

        parent::conectar();
        $rowAffectPD = parent::queryRegistro( $schemaxInsertxPD );
        parent::cerrar();


        $proveedores_list = array(); // Aqui se guardan los ID de cada proveedor en pago digital.
        if ( $rowAffectPD > 0 ) {

            foreach ($data['JSON_PROVEEDORES'] as $key => $value) {

                if ( $value['ID_PROVEEDOR'] == $this->PagoDigitalVars['ID_PROVEEDOR_NASBI'] || $value['ID_PROVEEDOR'] == 0 ) {
                    $value['ID_PROVEEDOR']        = $this->PagoDigitalVars['ID_PROVEEDOR_NASBI'];
                    $value['COSTO_FLETE']         = 0;
                    $value['PORCENTAJE_COMISION'] = 0;
                }


                if( strlen($value['PRODUCTO']) >= 45 ){
                    $value['PRODUCTO'] = addslashes(substr($value['PRODUCTO'], 0, 20)) . "...";
                }else{
                    $value['PRODUCTO'] = addslashes($value['PRODUCTO']);

                }
                $schemaxInsertxPDxProveedores = "INSERT INTO pago_digital_json_proveedores(
                    PAGO_DIGITAL_ID,
                    ID_PROVEEDOR,
                    UID,
                    EMPRESA,
                    VALOR,
                    VALOR_REAL_COMPRA,
                    COSTO_FLETE,
                    PRODUCTO,
                    PRODUCTO_UID,
                    PORCENTAJE_COMISION,
                    ISO_CODE_2,
                    FECHA_CREACION,
                    FECHA_ACTUALIZACION
                )  
                VALUES(
                    $rowAffectPD,
                    $value[ID_PROVEEDOR],
                    '$data[UID]',
                    '$data[EMPRESA]',
                    $value[VALOR],
                    $value[VALOR_REAL_COMPRA],
                    '$value[COSTO_FLETE]',
                    '$value[PRODUCTO]',
                    '$value[PRODUCTO_UID]',
                    '$value[PORCENTAJE_COMISION]',
                    '$data[ISO_CODE_2]',
                    $fecha,
                    $fecha
                )";
                parent::conectar();
                $rowAffectPDProveedores = parent::queryRegistro( $schemaxInsertxPDxProveedores );
                parent::cerrar();
            }

            $selectxrefxpd = 
            "SELECT * FROM buyinbig.pago_digital rpd INNER JOIN pago_digital_json_proveedores rpdjp ON ( rpd.id = rpdjp.PAGO_DIGITAL_ID) WHERE rpd.id = '$rowAffectPD' AND rpdjp.PAGO_DIGITAL_ID = '$rowAffectPD';";

            parent::conectar();
            $referenciaxDexPagoxPD = "SELECT * FROM buyinbig.pago_digital WHERE id = '$rowAffectPD';";
            $referenciaDePago_PD   = parent::consultaTodo($referenciaxDexPagoxPD);
            $referenciaDePago_PD   = $referenciaDePago_PD[0];

            
            $referenciaxDexPagoxPDxProveedoresxList = 
            "SELECT * FROM buyinbig.pago_digital_json_proveedores WHERE PAGO_DIGITAL_ID = '$rowAffectPD';";

            $referenciaDePago_PDProveedoresList     = parent::consultaTodo( $referenciaxDexPagoxPDxProveedoresxList );
            parent::cerrar();

            $referenciaDePago_PD["JSON_PROVEEDORES"] = $referenciaDePago_PDProveedoresList;

            return array(
                'status'        => 'success',
                'message'       => 'Registro base en MySQL [ ' . $rowAffectPD . ' ].',
                'data'          => $referenciaDePago_PD,
                'transaccionID' => $rowAffectPD
            );
        }else{
            return array(
                'status'        => 'failRegistroTransaccion',
                'message'       => 'No fue posible realizar el registro de esta transacción.',
                'transaccionID' => $rowAffectPD,
                'Insert'        => $schemaxInsertxPD
            );
        }
    }
    function actualizarOrdenPago( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['NOMBRE_COMPRADOR'] ) || !isset( $data['CORREO_COMPRADOR'] ) || !isset( $data['TIPO_DOCUMENTO'] ) || !isset( $data['CEDULA_COMPRADOR'] ) || !isset( $data['TELEFONO_COMPRADOR'] ) || !isset( $data['DIRECCION_COMPRADOR'] ) || !isset( $data['CIUDAD'] ) || !isset( $data['COD_DANE'] ) || !isset( $data['DEPARTAMENTO'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $data
            );
        }

        $update = 
        "UPDATE buyinbig.pago_digital 
        SET
            NOMBRE_COMPRADOR    = '$data[NOMBRE_COMPRADOR]',
            CEDULA_COMPRADOR    = '$data[CEDULA_COMPRADOR]',
            TELEFONO_COMPRADOR  = '$data[TELEFONO_COMPRADOR]',
            CORREO_COMPRADOR    = '$data[CORREO_COMPRADOR]',
            DIRECCION_COMPRADOR = '$data[DIRECCION_COMPRADOR]',

            CIUDAD              = '$data[CIUDAD]',
            COD_DANE            = '$data[COD_DANE]',
            DEPARTAMENTO        = '$data[DEPARTAMENTO]',

            TIPO_DOCUMENTO      = '$data[TIPO_DOCUMENTO]'
        WHERE ID = $data[ID];";


        parent::conectar();
        $resultUpdate = parent::query($update);
        parent::cerrar();
        if(!$resultUpdate){
            return array(
                'status'  => 'updateError',
                'message' => 'No fue posible actualizar esta informacion.',
                'data'    => $data
            );
        }else{
            if(intval( $data['EMPRESA'] ) == 0){
                $updateUsuario=
                "UPDATE peer2win.usuarios
                SET
                    tipo_identificacion   = '$data[TIPO_DOCUMENTO]',
                    numero_identificacion = '$data[CEDULA_COMPRADOR]'
                WHERE ID = $data[UID];";

                parent::conectar();
                $resultUpdate2 = parent::query( $updateUsuario );
                parent::cerrar();
            }
            return array(
                'status'        => 'success',
                'message'       => 'Información actualizada.'
            );
        }
    }
    function obtenerOrdenPago( Array $data )
    {
        if( !isset($data) || !isset( $data['SLUG'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $data
            );
        }

        $selectxrefxpd = "SELECT * FROM buyinbig.pago_digital rpd WHERE rpd.id = '$data[SLUG]';";

        parent::conectar();
        $referenciaxDexPagoxPD = "SELECT * FROM buyinbig.pago_digital WHERE id = '$data[SLUG]';";
        $referenciaDePago_PD   = parent::consultaTodo($referenciaxDexPagoxPD);

        if ( count($referenciaDePago_PD) == 0 ) {
            return array(
                'status'  => 'errorNoData',
                'message' => 'No se hallarón transacciones para esta slug ' . $data['SLUG'],
                'data'    => $data
            );
        }

        $referenciaDePago_PD                   = $this->mapPagoDigital($referenciaDePago_PD[0]);
        $referenciaDePago_PD['JSON_SOLICITUD'] = json_decode($referenciaDePago_PD['JSON_SOLICITUD']);

        $referenciaxDexPagoxPDxProveedoresxList = 
        "SELECT * FROM buyinbig.pago_digital_json_proveedores WHERE PAGO_DIGITAL_ID = '$data[SLUG]';";

        $referenciaDePago_PDProveedoresList     = parent::consultaTodo( $referenciaxDexPagoxPDxProveedoresxList );
        parent::cerrar();
        if ( count($referenciaDePago_PDProveedoresList) <= 0 ) {
            return array(
                'status'  => 'errorNoDataProveedores',
                'message' => 'No se hallarón transacciones para esta slug ' . $data['SLUG'],
                'data'    => $data
            );
        }
        $referenciaDePago_PD["JSON_PROVEEDORES"] = $this->mapPagoDigitalProveedores( $referenciaDePago_PDProveedoresList );

        return array(
            'status'           => 'success',
            'message'          => 'Información transacciones para slug ' . $data['SLUG'],
            'data'             => $referenciaDePago_PD,
            'JSON_PROVEEDORES' => $referenciaDePago_PDProveedoresList
        );
    }
    function sendOrdenPagoPSE( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) || !isset( $data['CODIGO_BANCO'] ) || !isset( $data['NOMBRE_BANCO'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan dato por enviar',
                'data'    => $data
            );
        }

        if ( strlen("" . $data['CODIGO_BANCO']) == 0 || $data['CODIGO_BANCO'] == null || strlen("" . $data['NOMBRE_BANCO']) == 0 || $data['NOMBRE_BANCO'] == null) {
            return array(
                'status'  => 'errorDataBanco',
                'message' => 'Se requiere el codigo bancario.',
                'data'    => $data
            );
        }

        parent::addLogPD("----+> creando orden de pago digital: " . json_encode($data));

        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );
        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $dataSend['CODIGO_BANCO'] = $data['CODIGO_BANCO'];
        $dataSend['NOMBRE_BANCO'] = $data['NOMBRE_BANCO'];

        $fecha = $this->getTimestamp();

        $updatexordenxdexpago = 
        "UPDATE pago_digital 
        SET 
            METODO_PAGO_USADO_ID = 2,
            CODIGO_BANCO         = '$data[CODIGO_BANCO]',
            NOMBRE_BANCO         = '$data[NOMBRE_BANCO]',
            FECHA_ACTUALIZACION  = '$fecha'
        WHERE ID = '$dataSend[ID]';";

        parent::conectar();
        $update = parent::query($updatexordenxdexpago);
        parent::cerrar();

        // Se encarga de listar todos los bancos permitidos para pagos en PSE

        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/PSE/TRANSACTION_BEGIN/';
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $dataSend['JWT']                = $TOKEN;
        $dataSend['API_KEY']            = $this->PagoDigitalVars['API_KEY'];
        $dataSend['API_SECRET']         = $this->PagoDigitalVars['API_SECRET'];
        $dataSend['CODIGO_ENTIDAD']     = $this->PagoDigitalVars['CODIGO_ENTIDAD'];
        $dataSend['CODIGO_SERVICIO']    = $this->PagoDigitalVars['CODIGO_SERVICIO'];
        $dataSend['URL_RESPUESTA']      = $this->PagoDigitalVars['URL_RESPUESTA_PSE'];

        $dataSend['TELEFONO_COMPRADOR'] = intval( "" . $dataSend['TELEFONO_COMPRADOR']);
        $dataSend['TOTAL_COMPRA']       = floatval("" . $dataSend['TOTAL_COMPRA'] );
        $dataSend['CODIGO_BANCO']       = intval("" . $dataSend['CODIGO_BANCO'] );

        $dataSendAux = array(
            'API_KEY'             => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET'          => $this->PagoDigitalVars['API_SECRET'],
            'NOMBRE_COMPRADOR'    => $dataSend['NOMBRE_COMPRADOR'],
            'CEDULA_COMPRADOR'    => $dataSend['CEDULA_COMPRADOR'],
            'TELEFONO_COMPRADOR'  => $dataSend['TELEFONO_COMPRADOR'],
            'CORREO_COMPRADOR'    => $dataSend['CORREO_COMPRADOR'],
            'DIRECCION_COMPRADOR' => $dataSend['DIRECCION_COMPRADOR'],
            'TOTAL_COMPRA'        => $dataSend['TOTAL_COMPRA'],
            'CIUDAD'              => $dataSend['CIUDAD'],
            'DEPARTAMENTO'        => $dataSend['DEPARTAMENTO'],
            'CODIGO_BANCO'        => $dataSend['CODIGO_BANCO'],
            'NOMBRE_BANCO'        => $dataSend['NOMBRE_BANCO'],
            'TIPO_PERSONA'        => $dataSend['TIPO_PERSONA'],
            'TIPO_DOCUMENTO'      => $dataSend['TIPO_DOCUMENTO'],
            'CODIGO_ENTIDAD'      => $dataSend['CODIGO_ENTIDAD'],
            'CODIGO_SERVICIO'     => $dataSend['CODIGO_SERVICIO'],
            'URL_RESPUESTA'       => $dataSend['URL_RESPUESTA'],
            'JSON_PROVEEDORES'    => json_encode($dataSend['JSON_PROVEEDORES']),
            'JWT'                 => $TOKEN
        );
        
        $response = parent::remoteRequestPagoDigitalParams($URL, $dataSendAux );

        parent::addLogPD("----+> creando orden de pago digital / URL: " . json_encode($URL));
        parent::addLogPD("----+> creando orden de pago digital / response: " . json_encode($response));
        parent::addLogPD("----+> creando orden de pago digital / dataSendAux: " . json_encode($dataSendAux));


        $STATUS = "errorGenerarOrdenPago";
        if ( isset($response) ) {
            if ( $response['STATUS'] == "200" && isset($response['CUS']) ) {
                $insertxreferencia = 
                "INSERT INTO buyinbig.pago_digital_referencias 
                (
                    STATUS,
                    CODIGO_RESPUESTA,
                    REFERENCIA,
                    CUS,
                    ESTADO,
                    RESPUESTA,
                    BANK_URL,
                    PAGO_DIGITAL_ID
                )
                VALUES (
                    '$response[STATUS]',
                    '$response[CODIGO_RESPUESTA]',
                    '$response[REFERENCIA]',
                    '$response[CUS]',
                    '$response[ESTADO]',
                    '$response[RESPUESTA]',
                    '$response[BANK_URL]',
                    $data[ID]
                );";

                parent::conectar();
                $insertreferencia = parent::queryRegistro($insertxreferencia);
                parent::cerrar();

                $STATUS = "success";
            }
        }

        
        $respuestaWS = array(
            'status'           => $STATUS,
            'message'          => 'Información petición de pago PSE.',
            'data'             => $response,
            'dataOrdenSend'    => $dataSend,
            'dataOrdenSendAux' => $dataSendAux,
        );
        parent::addLogPD("----+> creando orden de pago digital / respuestaWS: " . json_encode($respuestaWS));
        return $respuestaWS;
    }
    function finalizeTransactionPSE( Array $data )
    {
        if( !isset($data) || !isset( $data['ticketID']) ) {
            return array(
                'status'  => 'fail',
                'message' => 'faltan dato por enviar',
                'data'    => $data
            );
        }

        $selectxreferenciasxpago = "SELECT * FROM buyinbig.pago_digital_referencias WHERE REFERENCIA = '$data[ticketID]';";
        parent::conectar();
        $selectreferenciaspago = parent::consultaTodo( $selectxreferenciasxpago );
        parent::cerrar();
        if ( count( $selectreferenciaspago ) <= 0 ) {
            return array(
                'status'        => 'errorRef',
                'message'       => 'No se hallo información para esta referencia de pago',
                'data'          => null
            );
        }
        $selectreferenciaspago = $selectreferenciaspago[0];
        
        $selectxpagoxdigital = "SELECT * FROM buyinbig.pago_digital WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";
        parent::conectar();
        $selectpagodigital = parent::consultaTodo( $selectxpagoxdigital );
        parent::cerrar();
        if ( count( $selectpagodigital ) <= 0 ) {
            return array(
                'status'        => 'errorRef',
                'message'       => 'No se hallo información para esta referencia de pago',
                'data'          => null
            );
        }
        $selectpagodigital = $selectpagodigital[ 0 ];
        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/PSE/GET_BANK_LIST/';
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];
        $URL   = $this->PagoDigitalVars['BASE_URL'] . '/PSE/FINALIZE_TRANSACTION/';

        $dataSend = array(
            'API_KEY'       => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET'    => $this->PagoDigitalVars['API_SECRET'],
            'ID_REFERENCIA' => $selectreferenciaspago['REFERENCIA'],
            'CUS'           => $selectreferenciaspago['CUS'],
            'JWT'           => $TOKEN
        );
        $response = parent::remoteRequestPagoDigital($URL, $dataSend);


        $ESTADO                 = $selectreferenciaspago['ESTADO'];
        $RESPUESTA              = $selectreferenciaspago['RESPUESTA'];
        $TRANSACCION_FINALIZADA = $selectpagodigital['TRANSACCION_FINALIZADA'];
        $JSON_RESPUESTA         = json_encode($response);


        if ( isset($response['RESPUESTA']) ) {
            $ESTADO         = $response['RESPUESTA'];
            $RESPUESTA      = $response['RESPUESTA'];
        }

        $STATUS  = "success";
        $MESSAGE = "Información petición de pago PSE.";
        if ( $ESTADO == "Aprobada") {
            $TRANSACCION_FINALIZADA = $this->ESTADOS_TRANSACCIONES['FINALIZA'];
        }else{
            $STATUS = $ESTADO;
        }

        $fecha = $this->getTimestamp();
        $updatexordenxdexpago = 
        "UPDATE pago_digital SET FECHA_ACTUALIZACION = '$fecha', TRANSACCION_FINALIZADA = $TRANSACCION_FINALIZADA WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

        parent::conectar();
        $update_pago_digital = parent::query($updatexordenxdexpago);
        parent::cerrar();

        $fecha = $this->getTimestamp();
        $updatexpagoxdigitalxreferencias = 
        "UPDATE pago_digital_referencias
        SET
            ESTADO         = '$ESTADO',
            RESPUESTA      = '$RESPUESTA',
            JSON_RESPUESTA = '$JSON_RESPUESTA'
        WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";


        parent::conectar();
        $update_pago_digital_referencias = parent::query($updatexpagoxdigitalxreferencias);
        parent::cerrar();


        if ( isset( $selectpagodigital['JSON_SOLICITUD'] ) && $selectpagodigital['JSON_SOLICITUD'] != null ) {
            $selectpagodigital['JSON_SOLICITUD'] = json_decode($selectpagodigital['JSON_SOLICITUD']);
        }

        $result = array(
            'status'    => $STATUS,
            'message'   => $MESSAGE,
            'data'      => $response,
            'dataExtra' => $selectpagodigital
        );

        return $result;
    }

    function solicitarPinEfecty( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        
        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );

        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $dataSendEfecty = array(
            'API_KEY'          => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET'       => $this->PagoDigitalVars['API_SECRET'],
            'NOMBRE'           => $dataSend['NOMBRE_COMPRADOR'],
            'CEDULA'           => $dataSend['CEDULA_COMPRADOR'],
            'TELEFONO'         => $dataSend['TELEFONO_COMPRADOR'],
            'DIRECCION'        => $dataSend['DIRECCION_COMPRADOR'],
            'EMAIL'            => $dataSend['CORREO_COMPRADOR'],
            'TOTAL'            => $dataSend['TOTAL_COMPRA'],
            'JSON_PROVEEDORES' => json_encode($dataSend['JSON_PROVEEDORES']),
            'JWT'              => $TOKEN,
            'FECHA_CREACION'   => intval($dataSend['FECHA_CREACION'])
        );


        $URL      = $this->PagoDigitalVars['BASE_URL'] . '/EFECTY/SOLICITAR_PIN_EFECTY/';
        $response = parent::remoteRequestPagoDigitalParams($URL, $dataSendEfecty);
        $STATUS   = "errorSolicitud";
        $MESSAGE  = "No fue posible solicitar el PIN.";
        if ( isset($response) && isset($response['STATUS'])) {
            if ( $response['STATUS'] == "200" && $response['CODIGO_RESPUESTA'] == "PD00") {
                $STATUS = "success";
                $MESSAGE = "PIN generado con exito";


                // Solo para pruebas bogota
                $response['RESPUESTA'] = "PDE951852";

                $insertxreferencia = 
                "INSERT INTO buyinbig.pago_digital_referencias 
                (
                    STATUS,
                    CODIGO_RESPUESTA,
                    REFERENCIA,
                    CUS,
                    ESTADO,
                    RESPUESTA,
                    BANK_URL,
                    PAGO_DIGITAL_ID
                )
                VALUES (
                    '$response[STATUS]',
                    '$response[CODIGO_RESPUESTA]',
                    '$response[RESPUESTA]',
                    '',
                    'En proceso',
                    '',
                    '',
                    $data[ID]
                );";

                parent::conectar();
                $insertreferencia = parent::queryRegistro($insertxreferencia);
                parent::cerrar();
                
                $fecha = $this->getTimestamp();
                $updatexordenxdexpago = 
                "UPDATE pago_digital 
                SET 
                    METODO_PAGO_USADO_ID = 3,
                    FECHA_ACTUALIZACION = '$fecha',
                    TRANSACCION_FINALIZADA = {$this->ESTADOS_TRANSACCIONES['EN_PROCESO']}
                WHERE ID = '$dataSend[ID]';";

                parent::conectar();
                $update = parent::query($updatexordenxdexpago);
                parent::cerrar();
            }
        }

        $dataSend['CONVENIO_PAGO'] = $this->NUMERO_CONVENIO_EFECTY;

        $responseAPI= array(
            'status'         => $STATUS,
            'message'        => $MESSAGE,
            'data'           => $response,
            'dataExtra'      => $dataSend,
            'dataSendEfecty' => $dataSendEfecty
        );
        parent::addLogPD("----+> [ Solicitar PIN ]: " . json_encode($responseAPI));
        return $responseAPI;
    }
    function consultarPinMasivoEfecty( Array $data )
    {
        if( !isset($data) || !isset( $data['password'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        if ( $data['password'] != "contraseña super segura de mario palma") {
            return array(
                'status'  => 'failPassword',
                'message' => '¿No te sabes la clave? Estas jodio...'
            );
        }
        $selectxpines = 
        "SELECT
            pd.*
        FROM buyinbig.pago_digital pd
        WHERE pd.METODO_PAGO_USADO_ID = 3 AND pd.TRANSACCION_FINALIZADA = {$this->ESTADOS_TRANSACCIONES['EN_PROCESO']};";

        parent::conectar();
        $selectxpagoxdigitalxPINES = parent::consultaTodo($selectxpines);
        parent::cerrar();

        $resultsConsultasTransaccionesPIN = array();

        if ( count($selectxpagoxdigitalxPINES) == 0 ) {
            return array(
                'status'  => 'errorBusquedaVacia',
                'message' => 'Se realizo un barrido y no se hallarón PINES pendientes por validar.',
                'data'    => $selectxpagoxdigitalxPINES,
                'query'   => $selectxpines
            );
        }

        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        foreach ($selectxpagoxdigitalxPINES as $key => $referenciaDePago_PD) {
            $referenciaDePago_PD   = $this->mapPagoDigital($referenciaDePago_PD);
            
            $referenciaxDexPagoxPDxProveedoresxList = 
            "SELECT * FROM buyinbig.pago_digital_json_proveedores WHERE PAGO_DIGITAL_ID = $referenciaDePago_PD[ID];";

            parent::conectar();
            $referenciaDePago_PDProveedoresList     = parent::consultaTodo( $referenciaxDexPagoxPDxProveedoresxList );
            parent::cerrar();

            if ( count($referenciaDePago_PDProveedoresList) <= 0 ) {
                $resultsConsultasTransaccionesPIN[] = array(
                    'status'  => 'errorNoDataProveedores',
                    'message' => 'No se hallarón transacciones para esta slug ' . $referenciaDePago_PD[ID],
                    'data'    => $referenciaDePago_PD
                );
            }else{
                $referenciaDePago_PD["JSON_PROVEEDORES"] = $this->mapPagoDigitalProveedores( $referenciaDePago_PDProveedoresList );

                $selectxreferenciasxpago = "SELECT * FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = $referenciaDePago_PD[ID];";
                parent::conectar();
                $selectreferenciaspago = parent::consultaTodo( $selectxreferenciasxpago );
                parent::cerrar();

                if ( count( $selectreferenciaspago ) <= 0 ) {
                    $resultsConsultasTransaccionesPIN[] = array(
                        'status'        => 'errorRef',
                        'message'       => 'No se hallo información para esta referencia de pago',
                        'data'          => null
                    );
                }else{
                    $selectreferenciaspago = $selectreferenciaspago[0];

                    $dataSendAux = array(
                        'API_KEY'    => $this->PagoDigitalVars['API_KEY'],
                        'API_SECRET' => $this->PagoDigitalVars['API_SECRET'],
                        'PIN'        => $selectreferenciaspago['REFERENCIA'],
                        'JWT'        => $TOKEN
                    );

                    $URL = $this->PagoDigitalVars['BASE_URL'] . '/EFECTY/CONSULTA_PIN_EFECTY/';

                    $update2 = null;
                    $update  = null;

                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                    $STATUS    = "errorPIN";
                    $MESSAGE   = "No fue posible obtener información de este PIN.";

                    $response  = parent::remoteRequestPagoDigitalParams($URL, $dataSendAux );

                    if ( isset($response) && isset($response['STATUS'] ) ) {
                        if ( $response['STATUS'] == "200" ) {
                            if ( $response['CODIGO_RESPUESTA'] == "PD39" ) {
                                $STATUS = "En proceso";
                                $MESSAGE = "En proceso - PIN Activo o en proceso (Aun no ha pagado).";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];

                            }else if ($response['CODIGO_RESPUESTA'] == "PD00") {
                                $STATUS = "success";
                                $MESSAGE = "Aprobado - PIN Aprobado";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['FINALIZA'];

                                $dataCallbackEfecty = array(
                                    'pago_digital'                  => $referenciaDePago_PD,
                                    'pago_digital_json_proveedores' => $referenciaDePago_PD["JSON_PROVEEDORES"],
                                    'pago_digital_referencias'      => $selectxreferenciasxpago
                                );
                                $this->callback_Efecty_SuRed( $dataCallbackEfecty );

                            }else if ($response['CODIGO_RESPUESTA'] == "PD59") {
                                $STATUS = "noPagado";
                                $MESSAGE = "Negado o Vencido - PIN Negado o vencido.";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['VENCIDA'];

                            }else if ($response['CODIGO_RESPUESTA'] == "PD29") {
                                $STATUS = "errorNoExiste";
                                $MESSAGE = "No Existe - PIN No encontrado (1)";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                            }else{
                                $STATUS = "errorNoExiste";
                                $MESSAGE = "PIN No encontrado (1)";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                            }
                            $fecha = $this->getTimestamp();
                            $updatexordenxdexpago = 
                            "UPDATE pago_digital 
                            SET 
                                FECHA_ACTUALIZACION = '$fecha',
                                TRANSACCION_FINALIZADA = $STATUS_ID
                            WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                            parent::conectar();
                            $update2 = parent::query($updatexordenxdexpago);
                            parent::cerrar();

                            $STATUS_AUX         = ( $STATUS == 'success'? 'Aprobada' : $STATUS);
                            $JSON_RESPUESTA     = json_encode($response);
                            $updatexpagoxdigitalxreferencias = 
                            "UPDATE pago_digital_referencias 
                            SET 
                                ESTADO         = '$STATUS_AUX',
                                RESPUESTA      = '$STATUS_AUX',
                                JSON_RESPUESTA = '$JSON_RESPUESTA'
                            WHERE PAGO_DIGITAL_ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                            parent::conectar();
                            $update = parent::query($updatexpagoxdigitalxreferencias);
                            parent::cerrar();
                        }
                    }
                    $resultsConsultasTransaccionesPIN[] = array(
                        'status'                => $STATUS,
                        'message'               => $MESSAGE,
                        'data'                  => $response,
                        'dataSendAux'           => $dataSendAux,
                        'selectreferenciaspago' => $selectreferenciaspago,
                        'update2'               => $update2,
                        'update'                => $update
                    );
                }
            }
        }
        return array(
            'status'     => 'success',
            'message'    => 'Proceso culminado',
            'data'       => $resultsConsultasTransaccionesPIN
        );
    }
    function consultarPinEfecty( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );
        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/EFECTY/SOLICITAR_PIN_EFECTY/';
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $selectxreferenciasxpago = "SELECT * FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = '$dataSend[ID]';";
        parent::conectar();
        $selectreferenciaspago = parent::consultaTodo( $selectxreferenciasxpago );
        parent::cerrar();

        if ( count( $selectreferenciaspago ) <= 0 ) {
            return array(
                'status'        => 'errorRef',
                'message'       => 'No se hallo información para esta referencia de pago',
                'data'          => null
            );
        }
        $selectreferenciaspago = $selectreferenciaspago[0];

        $URL = $this->PagoDigitalVars['BASE_URL'] . '/EFECTY/CONSULTA_PIN_EFECTY/';



        $dataSendAux = array(
            'API_KEY'    => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET' => $this->PagoDigitalVars['API_SECRET'],
            'PIN'        => $selectreferenciaspago['REFERENCIA'],
            'JWT'        => $TOKEN
        );

        $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];
        $STATUS    = "errorPIN";
        $MESSAGE   = "No fue posible obtener información de este PIN";
        $response  = parent::remoteRequestPagoDigitalParams($URL, $dataSendAux );
        if ( isset($response) && isset($response['STATUS'] ) ) {
            if ( $response['STATUS'] == "200" ) {
                if ( $response['CODIGO_RESPUESTA'] == "PD39" ) {
                    $STATUS = "En proceso";
                    $MESSAGE = "PIN Activo o en proceso (Aun no ha pagado).";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD00") {
                    $STATUS = "success";
                    $MESSAGE = "PIN Aprobado";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['FINALIZA'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD59") {
                    $STATUS = "noPagado";
                    $MESSAGE = "PIN Negado o vencido.";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['VENCIDA'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD29") {
                    $STATUS = "errorNoExiste";
                    $MESSAGE = "PIN No encontrado (1)";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                }else{
                    $STATUS = "errorNoExiste";
                    $MESSAGE = "PIN No encontrado (1)";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                }
                $fecha = $this->getTimestamp();
                $updatexordenxdexpago = 
                "UPDATE pago_digital 
                SET 
                    FECHA_ACTUALIZACION = '$fecha',
                    TRANSACCION_FINALIZADA = $STATUS_ID
                WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                parent::conectar();
                $update2 = parent::query($updatexordenxdexpago);
                parent::cerrar();

                $STATUS_AUX = ( $STATUS == 'success'? 'Aprobada' : $STATUS);
                $JSON_RESPUESTA     = json_encode($response);
                $updatexpagoxdigitalxreferencias = 
                "UPDATE pago_digital_referencias 
                SET 
                    ESTADO         = '$STATUS_AUX',
                    RESPUESTA      = '$STATUS_AUX',
                    JSON_RESPUESTA = '$JSON_RESPUESTA'
                WHERE PAGO_DIGITAL_ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                parent::conectar();
                $update = parent::query($updatexpagoxdigitalxreferencias);
                parent::cerrar();
            }
        }
        return array(
            'status'     => $STATUS,
            'message'    => $MESSAGE,
            'data'       => $response,
            'dataExtra'  => $dataSendAux
        );
    }

    //IMPLEMENTACION SU RED

    function solicitarPinSuRed( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        
        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );

        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $dataSendSuRed = array(
            'API_KEY'          => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET'       => $this->PagoDigitalVars['API_SECRET'],
            'NOMBRE'           => $dataSend['NOMBRE_COMPRADOR'],
            'CEDULA'           => $dataSend['CEDULA_COMPRADOR'],
            'TELEFONO'         => $dataSend['TELEFONO_COMPRADOR'],
            'DIRECCION'        => $dataSend['DIRECCION_COMPRADOR'],
            'EMAIL'            => $dataSend['CORREO_COMPRADOR'],
            'TOTAL'            => $dataSend['TOTAL_COMPRA'],
            'JSON_PROVEEDORES' => json_encode($dataSend['JSON_PROVEEDORES']),
            'JWT'              => $TOKEN,
            'FECHA_CREACION'   => intval($dataSend['FECHA_CREACION'])
        );


        $URL      = $this->PagoDigitalVars['BASE_URL'] . '/SURED/SOLICITAR_PIN_SURED/';
        $response = parent::remoteRequestPagoDigitalParams($URL, $dataSendSuRed);
        $STATUS   = "errorSolicitud";
        $MESSAGE  = "No fue posible solicitar el PIN.";
        if ( isset($response) && isset($response['STATUS'])) {
            if ( $response['STATUS'] == "200" && $response['CODIGO_RESPUESTA'] == "PD00") {
                $STATUS = "success";
                $MESSAGE = "PIN generado con exito";

                                // Solo para pruebas bogota
                $response['RESPUESTA'] = "984651329";

                $insertxreferencia = 
                "INSERT INTO buyinbig.pago_digital_referencias 
                (
                    STATUS,
                    CODIGO_RESPUESTA,
                    REFERENCIA,
                    CUS,
                    ESTADO,
                    RESPUESTA,
                    BANK_URL,
                    PAGO_DIGITAL_ID
                )
                VALUES (
                    '$response[STATUS]',
                    '$response[CODIGO_RESPUESTA]',
                    '$response[RESPUESTA]',
                    '',
                    'En proceso',
                    '',
                    '',
                    $data[ID]
                );";

                parent::conectar();
                $insertreferencia = parent::queryRegistro($insertxreferencia);
                parent::cerrar();
                
                $fecha = $this->getTimestamp();
                $updatexordenxdexpago = 
                "UPDATE pago_digital 
                SET 
                    METODO_PAGO_USADO_ID = 5,
                    FECHA_ACTUALIZACION = '$fecha',
                    TRANSACCION_FINALIZADA = {$this->ESTADOS_TRANSACCIONES['EN_PROCESO']}
                WHERE ID = '$dataSend[ID]';";

                parent::conectar();
                $update = parent::query($updatexordenxdexpago);
                parent::cerrar();
            }
        }

        $dataSend['CONVENIO_PAGO'] = "PIN PAGO DIGITAL";

        return array(
            'status'        => $STATUS,
            'message'       => $MESSAGE,
            'data'          => $response,
            'dataExtra'     => $dataSend
        );
    }

    function consultarPinMasivoSuRed( Array $data )
    {
        if( !isset($data) || !isset( $data['password'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        if ( $data['password'] != "contraseña super segura de mario palma") {
            return array(
                'status'  => 'failPassword',
                'message' => '¿No te sabes la clave? Estas jodio...'
            );
        }
        $selectxpagoxdigitalxPINES = 
        "SELECT pdfp.nombre AS 'proveedor_de_pago', pd.*
        FROM buyinbig.pago_digital pd
        INNER JOIN buyinbig.pago_digital_formas_pago pdfp ON ( pd.METODO_PAGO_USADO_ID = pdfp.ID)
        WHERE pd.METODO_PAGO_USADO_ID = 5 AND pd.TRANSACCION_FINALIZADA = {$this->ESTADOS_TRANSACCIONES['EN_PROCESO']};";
        // var_dump($selectxpagoxdigitalxPINES);

        parent::conectar();
        $selectxpagoxdigitalxPINES = parent::consultaTodo($selectxpagoxdigitalxPINES);
        parent::cerrar();

        $resultsConsultasTransaccionesPIN = array();

        if ( count($selectxpagoxdigitalxPINES) == 0 ) {
            return array(
                'status'  => 'errorBusquedaVacia',
                'message' => 'Se realizo un barrido y no se hallarón PINES pendientes por validar.',
                'data'    => $selectxpagoxdigitalxPINES
            );
        }

        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        foreach ($selectxpagoxdigitalxPINES as $key => $referenciaDePago_PD) {
            $referenciaDePago_PD   = $this->mapPagoDigital($referenciaDePago_PD);
            
            $referenciaxDexPagoxPDxProveedoresxList = 
            "SELECT * FROM buyinbig.pago_digital_json_proveedores WHERE PAGO_DIGITAL_ID = $referenciaDePago_PD[ID];";

            parent::conectar();
            $referenciaDePago_PDProveedoresList     = parent::consultaTodo( $referenciaxDexPagoxPDxProveedoresxList );
            parent::cerrar();

            if ( count($referenciaDePago_PDProveedoresList) <= 0 ) {
                $resultsConsultasTransaccionesPIN[] = array(
                    'status'  => 'errorNoDataProveedores',
                    'message' => 'No se hallarón transacciones para esta slug ' . $referenciaDePago_PD[ID],
                    'data'    => $referenciaDePago_PD
                );
            }else{
                $referenciaDePago_PD["JSON_PROVEEDORES"] = $this->mapPagoDigitalProveedores( $referenciaDePago_PDProveedoresList );

                $selectxreferenciasxpago = "SELECT * FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = $referenciaDePago_PD[ID];";
                parent::conectar();
                $selectreferenciaspago = parent::consultaTodo( $selectxreferenciasxpago );
                parent::cerrar();

                if ( count( $selectreferenciaspago ) <= 0 ) {
                    $resultsConsultasTransaccionesPIN[] = array(
                        'status'        => 'errorRef',
                        'message'       => 'No se hallo información para esta referencia de pago',
                        'data'          => null
                    );
                }else{
                    $selectreferenciaspago = $selectreferenciaspago[0];

                    $dataSendAux = array(
                        'API_KEY'    => $this->PagoDigitalVars['API_KEY'],
                        'API_SECRET' => $this->PagoDigitalVars['API_SECRET'],
                        'PIN'        => $selectreferenciaspago['REFERENCIA'],
                        'JWT'        => $TOKEN
                    );

                    $URL = $this->PagoDigitalVars['BASE_URL'] . '/SURED/CONSULTA_PIN_SURED/';

                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];
                    $STATUS    = "errorPIN";
                    $MESSAGE   = "No fue posible obtener información de este PIN.";
                    $response  = parent::remoteRequestPagoDigitalParams($URL, $dataSendAux );
                    if ( isset($response) && isset($response['STATUS'] ) ) {
                        if ( $response['STATUS'] == "200" ) {
                            if ( $response['CODIGO_RESPUESTA'] == "PD39" ) {
                                $STATUS = "En proceso";
                                $MESSAGE = "En proceso - PIN Activo o en proceso (Aun no ha pagado).";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];

                            }else if ($response['CODIGO_RESPUESTA'] == "PD00") {
                                $STATUS = "success";
                                $MESSAGE = "Aprobado - PIN Aprobado";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['FINALIZA'];

                                $dataCallbackEfecty = array(
                                    'pago_digital'                  => $referenciaDePago_PD,
                                    'pago_digital_json_proveedores' => $referenciaDePago_PD["JSON_PROVEEDORES"],
                                    'pago_digital_referencias'      => $selectxreferenciasxpago
                                );
                                $this->callback_Efecty_SuRed( $dataCallbackEfecty );

                            }else if ($response['CODIGO_RESPUESTA'] == "PD59") {
                                $STATUS = "noPagado";
                                $MESSAGE = "Negado o Vencido - PIN Negado o vencido.";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['VENCIDA'];

                            }else if ($response['CODIGO_RESPUESTA'] == "PD29") {
                                $STATUS = "errorNoExiste";
                                $MESSAGE = "No Existe - PIN No encontrado (1)";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                            }else{
                                $STATUS = "errorNoExiste";
                                $MESSAGE = "PIN No encontrado (1)";
                                $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                            }
                            $fecha = $this->getTimestamp();
                            $updatexordenxdexpago = 
                            "UPDATE pago_digital 
                            SET 
                                FECHA_ACTUALIZACION = '$fecha',
                                TRANSACCION_FINALIZADA = $STATUS_ID
                            WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                            parent::conectar();
                            $update2 = parent::query($updatexordenxdexpago);
                            parent::cerrar();

                            $STATUS_AUX = ( $STATUS == 'success'? 'Aprobada' : $STATUS);
                            $JSON_RESPUESTA     = json_encode($response);
                            $updatexpagoxdigitalxreferencias = 
                            "UPDATE pago_digital_referencias 
                            SET 
                                ESTADO         = '$STATUS_AUX',
                                RESPUESTA      = '$STATUS_AUX',
                                JSON_RESPUESTA = '$JSON_RESPUESTA'
                            WHERE PAGO_DIGITAL_ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                            parent::conectar();
                            $update = parent::query($updatexpagoxdigitalxreferencias);
                            parent::cerrar();
                        }
                    }
                    $resultsConsultasTransaccionesPIN[] = array(
                        'status'     => $STATUS,
                        'message'    => $MESSAGE,
                        'data'       => $response,
                        'dataSendAux'=> $dataSendAux,
                        'selectreferenciaspago'=> $selectreferenciaspago
                    );
                }
            }
        }
        $respondeGlobal = array(
            'status'     => 'success',
            'message'    => 'Proceso culminado',
            'data'       => $resultsConsultasTransaccionesPIN
        );
        return $respondeGlobal;
    }

    function consultarPinSuRed( Array $data )
    {
        if( !isset($data) || !isset( $data['ID'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );
        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/SURED/SOLICITAR_PIN_SURED/';
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN = $RESULT_TOKEN['data'];

        $selectxreferenciasxpago = "SELECT * FROM buyinbig.pago_digital_referencias WHERE PAGO_DIGITAL_ID = '$dataSend[ID]';";
        parent::conectar();
        $selectreferenciaspago = parent::consultaTodo( $selectxreferenciasxpago );
        parent::cerrar();

        if ( count( $selectreferenciaspago ) <= 0 ) {
            return array(
                'status'        => 'errorRef',
                'message'       => 'No se hallo información para esta referencia de pago',
                'data'          => null
            );
        }
        $selectreferenciaspago = $selectreferenciaspago[0];

        $URL = $this->PagoDigitalVars['BASE_URL'] . '/SURED/CONSULTA_PIN_SURED/';



        $dataSendAux = array(
            'API_KEY'    => $this->PagoDigitalVars['API_KEY'],
            'API_SECRET' => $this->PagoDigitalVars['API_SECRET'],
            'PIN'        => $selectreferenciaspago['REFERENCIA'],
            'JWT'        => $TOKEN
        );

        $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];
        $STATUS    = "errorPIN";
        $MESSAGE   = "No fue posible obtener información de este PIN";
        $response  = parent::remoteRequestPagoDigitalParams($URL, $dataSendAux );
        if ( isset($response) && isset($response['STATUS'] ) ) {
            if ( $response['STATUS'] == "200" ) {
                if ( $response['CODIGO_RESPUESTA'] == "PD39" ) {
                    $STATUS = "En proceso";
                    $MESSAGE = "PIN Activo o en proceso (Aun no ha pagado).";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD00") {
                    $STATUS = "success";
                    $MESSAGE = "PIN Aprobado";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['FINALIZA'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD59") {
                    $STATUS = "noPagado";
                    $MESSAGE = "PIN Negado o vencido.";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['VENCIDA'];

                }else if ($response['CODIGO_RESPUESTA'] == "PD29") {
                    $STATUS = "errorNoExiste";
                    $MESSAGE = "PIN No encontrado (1)";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                }else{
                    $STATUS = "errorNoExiste";
                    $MESSAGE = "PIN No encontrado (1)";
                    $STATUS_ID = $this->ESTADOS_TRANSACCIONES['NO_EXISTE'];

                }
                $fecha = $this->getTimestamp();
                $updatexordenxdexpago = 
                "UPDATE pago_digital 
                SET 
                    FECHA_ACTUALIZACION = '$fecha',
                    TRANSACCION_FINALIZADA = $STATUS_ID
                WHERE ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                parent::conectar();
                $update2 = parent::query($updatexordenxdexpago);
                parent::cerrar();

                $STATUS_AUX = ( $STATUS == 'success'? 'Aprobada' : $STATUS);
                $JSON_RESPUESTA     = json_encode($response);
                $updatexpagoxdigitalxreferencias = 
                "UPDATE pago_digital_referencias 
                SET 
                    ESTADO         = '$STATUS_AUX',
                    RESPUESTA      = '$STATUS_AUX',
                    JSON_RESPUESTA = '$JSON_RESPUESTA'
                WHERE PAGO_DIGITAL_ID = '$selectreferenciaspago[PAGO_DIGITAL_ID]';";

                parent::conectar();
                $update = parent::query($updatexpagoxdigitalxreferencias);
                parent::cerrar();
            }
        }
        return array(
            'status'     => $STATUS,
            'message'    => $MESSAGE,
            'data'       => $response,
            'dataExtra'  => $dataSendAux
        );
    }

    function callback_Efecty_SuRed( Array $data )
    {
        $data['pago_digital']['TIPO'] = intval( $data['pago_digital']['TIPO'] );
        if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_COMPRA_Y_VENTA ) {
            return $this->callback_Efecty_SuRed_comprayventa( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_RECARGA ) {
            return $this->callback_recarga( $data );

            

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_TIQUETES_COMPRAS ) {
            return $this->callback_recargaTiquetes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_TIQUETES_VENTAS ) {
            return $this->callback_recargaTiquetes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_PLANES_COMPRAS ) {
            return $this->callback_recargaPlanes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_PLANES_VENTAS ) {
            return $this->callback_recargaPlanes( $data );

        }
        return array(
            'status' => 'success',
            'message' => 'No se gestiono nada en el callback Efecty'
        );
    }
    function callback_Efecty_SuRed_comprayventa( Array $data )
    {
        // Creación 27 abril 2021 - probar
        $fecha = $this->getTimestamp();
        $referencia = $data['pago_digital']['ID'];
        $selectxtransaccionxconxpagoxdigital = "SELECT * FROM buyinbig.productos_transaccion WHERE pago_digital_id = $referencia;";
        parent::conectar();
        $selectxtransaccionxconxpagoxdigital = parent::consultaTodo( $selectxtransaccionxconxpagoxdigital );
        parent::cerrar();

        if ( count( $selectxtransaccionxconxpagoxdigital ) == 0) {
            return array(
                'status'     => 'errorTransaccionesVacias',
                'message'    => 'No se hallarón transacciones para la referencia de PD: ' . $data['pago_digital']['ID'],
                'data'       => $selectxtransaccionxconxpagoxdigital
            );
        }
        $info = array();
        foreach ($selectxtransaccionxconxpagoxdigital as $key => $producto_transaccion) {
            $update = 
            "UPDATE buyinbig.productos_transaccion SET estado = 6, fecha_actualizacion = $fecha WHERE id = {$producto_transaccion['id']};";
            
            $producto_transaccion['estado']              = 6;
            $producto_transaccion['fecha_actualizacion'] = $fecha;


            parent::conectar();
            $update = parent::query( $update );
            parent::cerrar();
            array_push($info, $producto_transaccion);

            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $producto_transaccion['uid_vendedor'],
                'empresa' => $producto_transaccion['empresa'],
                'text' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'es' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'en' => "Reflected payment. The status of transaction REF: #". $producto_transaccion['id_carrito'] ." has been updated.",

                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_ventas'
            ]);
            $notificacion->insertarNotificacion([
                'uid' => $producto_transaccion['uid_comprador'],

                'empresa' => $producto_transaccion['empresa_comprador'],

                'text' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'es' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'en' => "Reflected payment. The status of transaction REF: #". $producto_transaccion['id_carrito'] ." has been updated.",

                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);

        }
        $URL = "http://nasbi.peers2win.com/api/controllers/ventas/?actualizar_timelines_transacciones";
        $dataSend = array(
            "data" => $info
        );
        $response = parent::remoteRequest($URL, $dataSend );

        parent::addLogPD("-----+> [ response ]: " . json_encode($response));

        return $response;
    }

    function callback_recarga( Array $data )
    {
        // Creación 27 abril 2021 - probar
        $fecha = $this->getTimestamp();

        $URL = "http://nasbi.peers2win.com/api/controllers/nasbicoin/?recibir_dinero";
        $PAGO_DIGITAL_ID = json_decode($data['pago_digital']['JSON_SOLICITUD']);

        $dataSend = json_decode($data['pago_digital']['JSON_SOLICITUD']);
        $response = parent::remoteRequest($URL, $dataSend );

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid'     => $data['pago_digital']['UID'],
            'empresa' => $data['pago_digital']['EMPRESA'],
            'text'    => 'Acabas de recibir una transacción por el monto de ' . $this->maskNumber(floatval( $data['pago_digital']['TOTAL_COMPRA'] ), 2) . ' Nasbichips',

            'es'      => 'Acabas de recibir una transacción por el monto de ' . $this->maskNumber(floatval( $data['pago_digital']['TOTAL_COMPRA'] ), 2) . ' Nasbichips',

            'en'      => 'You just received a transaction in the amount of ' . $this->maskNumber(floatval( $data['pago_digital']['TOTAL_COMPRA'] ), 2) . ' Nasbichips',

            'keyjson' => '',
            'url'     => 'e-wallet.php?tab=bonos_wallet'
        ]);
        unset($notificacion);

        parent::addLogPD("-----+> [ callback_recarga / Data / request  ]: " . json_encode($dataSend));
        parent::addLogPD("-----+> [ callback_recarga / Data / response ]: " . json_encode($response));

        if ( isset($response['status']) != 'success' ) {
            $update = 
            "UPDATE buyinbig.pago_digital SET TRANSACCION_FINALIZADA = 0 WHERE ID = $PAGO_DIGITAL_ID;";
            parent::conectar();
            $update = parent::query( $update );
            parent::cerrar();

        }

        return $response;
    }

    function callback_recargaTiquetes( Array $data )
    {
        // Creación 27 abril 2021 - probar
        $fecha = $this->getTimestamp();

        $URL = "http://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w";
        $PAGO_DIGITAL_ID = json_decode($data['pago_digital']['JSON_SOLICITUD']);

        $dataSend = json_decode($data['pago_digital']['JSON_SOLICITUD']);
        $response = parent::remoteRequest($URL, $dataSend );

        $dataSend['uso'] = intval($dataSend['uso']);
        
        $msg_es = "Ya se encuentrá disponible tus tiquetes.";
        $msg_en = "Your tickets are now available.";

        if ( $dataSend['uso'] == 1 ) {
            $responseUrl = 'tickets.php?tab=ventas-tickets';
        }else{
            $responseUrl = 'tickets.php?tab=compra-tickets';
        }

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid'     => $data['pago_digital']['UID'],
            'empresa' => $data['pago_digital']['EMPRESA'],
            'text'    => $msg_es,

            'es'      => $msg_es,

            'en'      => $msg_en,

            'keyjson' => '',
            'url'     => $responseUrl
        ]);
        unset($notificacion);

        parent::addLogPD("-----+> [ callback_recargaTiquetes / Data / request  ]: " . json_encode($dataSend));
        parent::addLogPD("-----+> [ callback_recargaTiquetes / Data / response ]: " . json_encode($response));

        if ( isset($response['status']) != 'success' ) {
            $update = 
            "UPDATE buyinbig.pago_digital SET TRANSACCION_FINALIZADA = 0 WHERE ID = $PAGO_DIGITAL_ID;";
            parent::conectar();
            $update = parent::query( $update );
            parent::cerrar();

        }

        return $response;
    }

    function callback_recargaPlanes( Array $data )
    {
        // Creación 27 abril 2021 - probar
        $fecha = $this->getTimestamp();

        $URL = "http://nasbi.peers2win.com/api/controllers/planes_nasbi/?pagar_plan_master";
        $PAGO_DIGITAL_ID = json_decode($data['pago_digital']['JSON_SOLICITUD']);

        $dataSend = json_decode($data['pago_digital']['JSON_SOLICITUD']);
        $response = parent::remoteRequest($URL, $dataSend );

        $dataSend['uso'] = intval($dataSend['uso']);
        
        $msg_es = "Ya se encuentrá disponible tu plan.";
        $msg_en = "Your plan is now available.";

        if ( $dataSend['uso'] == 1 ) {
            $responseUrl = 'tickets.php?tab=ventas-tickets';
        }else{
            $responseUrl = 'tickets.php?tab=compra-tickets';
        }

        $notificacion = new Notificaciones();
        $notificacion->insertarNotificacion([
            'uid'     => $data['pago_digital']['UID'],
            'empresa' => $data['pago_digital']['EMPRESA'],
            'text'    => $msg_es,

            'es'      => $msg_es,

            'en'      => $msg_en,

            'keyjson' => '',
            'url'     => $responseUrl
        ]);
        unset($notificacion);

        parent::addLogPD("-----+> [ callback_recargaPlanes / Data / request  ]: " . json_encode($dataSend));
        parent::addLogPD("-----+> [ callback_recargaPlanes / Data / response ]: " . json_encode($response));

        if ( isset($response['status']) != 'success' ) {
            $update = 
            "UPDATE buyinbig.pago_digital SET TRANSACCION_FINALIZADA = 0 WHERE ID = $PAGO_DIGITAL_ID;";
            parent::conectar();
            $update = parent::query( $update );
            parent::cerrar();

        }

        return $response;
    }

    function getUsuarioDatosPasarela( Array $data ){
        $selectxusuario = "";
        if ( $data['EMPRESA'] == 0 ) {
            $selectxusuario = "SELECT * FROM peer2win.usuarios WHERE id = '$data[UID]'";

            $selectxusuarioxformxpd = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[UID]' AND user_empresa = '$data[EMPRESA]'";


        }else{
            $selectxusuario = "SELECT * FROM buyinbig.empresas WHERE id = '$data[UID]'";
            $selectxusuarioxformxpd = 
            "SELECT *, di.codigo, di.codigo AS tipo_identificacion
            FROM buyinbig.datos_persona_juridica dpj
            INNER JOIN buyinbig.documento_identificacion di ON ( di.id = dpj.id_documento_identificacion)
            WHERE dpj.user_uid = '$data[UID]' AND dpj.user_empresa = '$data[EMPRESA]'";

        }
        
        $selectxusuarioxdireccion = 
        "SELECT
            d.*,
            ct.id AS 'TCC_id',
            ct.CLASIFICACION_GEOGRAFICA AS 'TCC_CLASIFICACION_GEOGRAFICA',
            ct.CODIGO_SION AS 'TCC_CODIGO_SION',
            ct.COP AS 'TCC_COP',
            ct.DANE AS 'TCC_DANE',
            ct.DANE_DEPARTAMENT AS 'TCC_DANE_DEPARTAMENT',
            ct.DEPARTAMENTO AS 'TCC_DEPARTAMENTO',
            ct.ESTADO AS 'TCC_ESTADO',
            ct.FCE AS 'TCC_FCE',
            ct.PAIS AS 'TCC_PAIS',
            ct.POBLACION AS 'TCC_POBLACION',
            ct.REEXPEDIDA AS 'TCC_REEXPEDIDA',
            ct.TIPO_GEOGRAFIA AS 'TCC_TIPO_GEOGRAFIA'
        FROM direcciones d
        INNER JOIN  ciudades_tcc ct ON (d.dane = ct.dane)
        WHERE d.uid = '$data[UID]' AND d.empresa = '$data[EMPRESA]' AND d.activa = 1 AND d.estado = 1
        ORDER BY fecha_creacion DESC";

        parent::conectar();
        $selectusuario          = parent::consultaTodo( $selectxusuario );
        $selectusuarioformpd    = parent::consultaTodo( $selectxusuarioxformxpd );
        $selectusuariodireccion = parent::consultaTodo( $selectxusuarioxdireccion );
        parent::cerrar();

        $status = "";
        $message = "";
        if ( count( $selectusuario ) <= 0 ) {
            $selectusuario = null;
            $status  = 'usuarioNoExiste';
            $message = 'Las credenciales suministradas no pertenecen a ningun usuario.';
            
        }else{
            $selectusuario = $this->mapUsuario( $selectusuario, $data['EMPRESA'] )[0];
        }
        if ( count( $selectusuarioformpd ) <= 0 ) {
            $selectusuarioformpd = null;
            $status  = 'errorFormularioPD';
            $message = 'El usuario requiere completar los datos del formulario pago digital.';
            
        }else{
            $selectusuarioformpd = $selectusuarioformpd[0];
        }
        if ( count( $selectusuariodireccion ) <= 0 ) {
            $selectusuariodireccion = null;
            $status  = 'direccionNoExiste';
            $message = 'El usuario requiere una direccion';
            
        }else{
            $selectusuariodireccion = $selectusuariodireccion[0];
        }
        if ( strlen( $status ) == 0 ) {
            $status = "success";
            $message = "Datos completos del usuario";
        }
        return array(
            'status'            => $status,
            'message'           => $message,
            'datos_usuario'     => $selectusuario,
            'form_pago_digital' => $selectusuarioformpd,
            'datos_residencia'  => $selectusuariodireccion
        );
    }
    function mapUsuario( Array $usuarios, Int $empresa = 0 ) 
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $dataidentificacion = null;
        $datatipoidentificacion = null;

        foreach ($usuarios as $x => $user) {
            if($empresa == 0){
                $datanombre = $user['nombreCompleto'];
                $dataempresa = "Nasbi";
                $datacorreo = $user['email'];
                $datatelefono = $user['telefono'];

                $datatipoidentificacion = $user['tipo_identificacion'];
                $dataidentificacion = $user['numero_identificacion'];
            }else if($empresa == 1){
                $datanombre = $user['razon_social'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datatipoidentificacion = "";
                $dataidentificacion = "";
            }

            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['tipo_identificacion'] = $datatipoidentificacion;
            $user['identificacion'] = $dataidentificacion;
            $usuarios[$x] = $user;
        }
        return $usuarios;

    }
    function mapFormPD( Array $formsPD, Int $empresa = 0 ) 
    {
        $datacelular = null;
        $dataidentificacion = null;
        $datatipoidentificacion = null;


        foreach ($formsPD as $x => $formPD) {
            if($empresa == 0){
                $datacelular = $user['celular'];
                $dataidentificacion = $user['no_identificacion'];
                $datatipoidentificacion = $user['id_documento_identificacion'];
            }else if($empresa == 1){
                $datacelular = $user['celular'];
                $dataidentificacion = $user['no_identificacion'];
                $datatipoidentificacion = $user['codigo'];
            }

            unset($user);
            $user['celular'] = $datacelular;
            $user['identificacion'] = $dataidentificacion;
            $user['tipo_identificacion'] = $datatipoidentificacion;
            $usuarios[$x] = $user;
        }

        return $usuarios;

    }

    function mapPagoDigital( Array $data )
    {

        $data['TELEFONO_COMPRADOR']   = intval("" . $data['TELEFONO_COMPRADOR'] );
        $data['TOTAL_COMPRA']         = floatval("" . $data['TOTAL_COMPRA'] );
        $data['PRECIO_USD']           = floatval("" . $data['PRECIO_USD'] );
        $data['CODIGO_BANCO']         = intval("" . $data['CODIGO_BANCO'] );

        
        $data['ID']                   = intval("" . $data['ID'] );
        $data['TIPO']                 = intval("" . $data['TIPO'] );
        $data['STATUS']               = intval("" . $data['STATUS'] );
        $data['UID']                  = intval("" . $data['UID'] );
        $data['EMPRESA']              = intval("" . $data['EMPRESA'] );
        $data['FECHA_CREACION']       = intval("" . $data['FECHA_CREACION'] );
        $data['FECHA_ACTUALIZACION']  = intval("" . $data['FECHA_ACTUALIZACION'] );

        $data['METODO_PAGO_USADO_ID'] = intval( $data['METODO_PAGO_USADO_ID'] );

        $data['CONVENIO_PAGO'] = "";

        if ( $data['METODO_PAGO_USADO_ID'] == 3 ) {
            $data['CONVENIO_PAGO'] = $this->NUMERO_CONVENIO_EFECTY;

        }else if ( $data['METODO_PAGO_USADO_ID'] == 5 ) {
            $data['CONVENIO_PAGO'] = "PIN PAGO DIGITAL";
        }
        return $data;
    }

    function mapPagoDigitalProveedores( Array $data )
    {
        
        foreach ($data as $key => $value) {
            $value['ID']                           = intval("" . $value['ID'] );
            $value['PAGO_DIGITAL_ID']              = intval("" . $value['PAGO_DIGITAL_ID'] );
            $value['UID']                          = intval("" . $value['UID'] );
            $value['EMPRESA']                      = intval("" . $value['EMPRESA'] );
            $value['ID_PROVEEDOR']                 = intval("" . $value['ID_PROVEEDOR'] );
            $value['VALOR']                        = floatval("" . $value['VALOR'] );
            $value['COSTO_FLETE']                  = intval("" . $value['COSTO_FLETE'] );
            $value['PRODUCTO_UID']                 = intval("" . $value['PRODUCTO_UID'] );
            $value['PORCENTAJE_COMISION']          = floatval("" . $value['PORCENTAJE_COMISION'] );
            $data[ $key ] = $value;
        }
        return $data;
    }


    // Inicio de Implementación TARJETA DE CREDITO - LUIS TOKENS
    function mapUsuarioTarjeta( Array $datos_usuario, Array $datos_pago_digital, Int $empresa = 0 )
    {
        if(!$empresa){
            unset($datos_usuario[0]['CIUDAD']);
            $datanombre         = $datos_usuario[0]["NOMBRE"];
            $dataidentificacion = $datos_usuario[0]["IDENTIFICACION"];

            $dataemail          = $datos_usuario[0]["EMAIL"];
            $datatelefono       = $datos_usuario[0]["TELEFONO"];

            return array(
                "NOMBRE" => $datanombre,
                "IDENTIFICACION" => $dataidentificacion,
                "EMAIL"          => $dataemail,
                "TELEFONO"       => $datatelefono
            );
        }else{
            $datanombre         = $datos_pago_digital[0]["NOMBRE"];
            $dataidentificacion = $datos_pago_digital[0]["IDENTIFICACION"];
            $dataemail          = $datos_usuario[0]["EMAIL"];
            $datatelefono       = $datos_usuario[0]["TELEFONO"];
    
            return array(
                "NOMBRE"         => $datanombre,
                "IDENTIFICACION" => $dataidentificacion,
                "EMAIL"          => $dataemail,
                "TELEFONO"       => $datatelefono
            );
        }
    }

    function mapDatosResidenciaTarjeta( Array $datos_usuario, Array $datos_direccion )
    {   
        $dataciudad         = $datos_usuario[0]["CIUDAD"];
        $datadireccion      = $datos_direccion[0]["DIRECCION"];
        $datadepartamento   = $datos_direccion[0]["DEPARTAMENTO"];

        return array("CIUDAD" => $dataciudad, "DIRECCION" => $datadireccion, "DEPARTAMENTO" => $datadepartamento);
    }

    function mapDatosPagoDigitalTarjeta( Array $datos_usuario, Array $datos_pago_digital , Int $empresa)
    {   
        parent::addLogPD("------+> hey :::: [ datos_usuario ]: " .json_encode($datos_usuario));
        parent::addLogPD("------+> hey :::: [ datos_pago_digital ]: " .json_encode($datos_pago_digital));
        parent::addLogPD("------+> hey :::: [ empresa ]: " . $empresa);

        if( $empresa == 0 ){
            // {
            //   "NOMBRE": "mario palma",
            //   "IDENTIFICACION": "8548646846135",
            //   "EMAIL": "taganga8701@gmail.com",
            //   "TELEFONO": "3015486721",
            //   "CIUDAD": "Santa Marta"
            // }
            $datatelefono   = $datos_usuario[0]["TELEFONO"];
            $datacorreo     = $datos_usuario[0]["EMAIL"];
            
            return array("TELEFONO" => $datatelefono, "CORREO" => $datacorreo);
        }else{
            $datatelefono   = $datos_usuario[0]["TELEFONO"];
            $datacorreo     = $datos_usuario[0]["EMAIL"];
            
            return array("TELEFONO" => $datatelefono, "CORREO" => $datacorreo);
        }
    }

    function getUsuarioDatosTarjeta($data){ 

        $selectxusuario = "";
        if ( $data['EMPRESA'] == 0 ) {
            $selectxusuario = 
            "SELECT 
                u.nombreCompleto        AS NOMBRE,
                u.numero_identificacion AS IDENTIFICACION,
                
                u.email                 AS EMAIL,
                u.telefono              AS TELEFONO,

                u.ciudad                AS CIUDAD
            FROM peer2win.usuarios u
            WHERE id = '$data[UID]'";

            $selectxusuarioxformxpd = 
            "SELECT
            dpn.correo                          AS CORREO,
            dpn.telefono                        AS TELEFONO
            FROM buyinbig.datos_persona_natural AS dpn
            WHERE user_uid = '$data[UID]' AND user_empresa = '$data[EMPRESA]'";
        }else{
            $selectxusuario = 
            "SELECT 
                u.correo   AS CORREO,
                u.ciudad   AS CIUDAD,

                u.correo   AS EMAIL,
                u.telefono AS TELEFONO

            FROM buyinbig.empresas AS u WHERE id = '$data[UID]'";

            $selectxusuarioxformxpd = 
            "SELECT 
                CONCAT(CONCAT(dpj.nombres, ' '), dpj.apellidos) AS NOMBRE,
                dpj.no_identificacion                           AS IDENTIFICACION,
                dpj.celular                                     AS TELEFONO
            FROM buyinbig.datos_persona_juridica                AS dpj
            WHERE dpj.user_uid = '$data[UID]' AND dpj.user_empresa = '$data[EMPRESA]'";

        }

        $selectxusuarioxdireccion = "SELECT
            d.direccion AS DIRECCION,
            ct.DEPARTAMENTO AS 'DEPARTAMENTO'
            FROM direcciones d
            INNER JOIN  ciudades_tcc ct ON (d.dane = ct.dane)
            WHERE d.uid = '$data[UID]' AND d.empresa = '$data[EMPRESA]' AND d.estado = 1
            ORDER BY fecha_creacion DESC";

        parent::conectar();
        $selectusuario          = parent::consultaTodo( $selectxusuario );
        $selectusuarioformpd    = parent::consultaTodo( $selectxusuarioxformxpd );
        $selectusuariodireccion = parent::consultaTodo( $selectxusuarioxdireccion );
        parent::cerrar();

        $STATUS                         = '';
        $MESSAGE                        = '';
        $selectusuario_send             = null;
        $selectusuarioformpd_send       = null;
        $selectusuariodireccion_send    = null;

        if ( count( $selectusuario ) == 0 ) {
            $STATUS         = 'usuarioNoExiste';
            $MESSAGE        = 'Las credenciales suministradas no pertenecen a ningun usuario.';
        }

        $schema_datos_usuario = $selectusuario[ 0 ];

        if( $schema_datos_usuario['NOMBRE'] == "" || $schema_datos_usuario['NOMBRE'] == null ){
            $STATUS         = 'errorUsuarioDatosBasicos';
            $MESSAGE        = 'No cuenta con un nombre completo';

        }
        if( $schema_datos_usuario['IDENTIFICACION'] == "" || $schema_datos_usuario['IDENTIFICACION'] == null ){
            $STATUS         = 'errorUsuarioDatosBasicos';
            $MESSAGE        = 'No cuenta con un identificacion';
        }
        if( $schema_datos_usuario['EMAIL'] == "" || $schema_datos_usuario['EMAIL'] == null ){
            $STATUS         = 'errorUsuarioEmail';
            $MESSAGE        = 'No cuenta con un email';
        }
        if( $schema_datos_usuario['TELEFONO'] == "" || $schema_datos_usuario['TELEFONO'] == null ){
            $STATUS         = 'errorUsuarioDatosBasicos';
            $MESSAGE        = 'No cuenta con un telefono';
        }


        if ( count( $selectusuariodireccion ) <= 0 ) {
            if(strlen($STATUS) == 0){
                $STATUS         = 'direccionNoExiste';
                $MESSAGE        = 'El usuario requiere una direccion';
            }
        }


        parent::addLogPD("-----+> [ strlen-0 ]: " . json_encode($schema_datos_usuario));
        parent::addLogPD("-----+> [ strlen-1 ]: " . $STATUS);
        parent::addLogPD("-----+> [ strlen-2 ]: " . ( strlen( $STATUS ) == 0 ));
        parent::addLogPD("-----+> [ strlen-3 ]: " . strlen( $STATUS ));

        if ( strlen( $STATUS ) == 0 ) {
            $selectusuario_send             = $this->mapUsuarioTarjeta($selectusuario, $selectusuarioformpd, $data['EMPRESA']);
            $selectusuarioformpd_send       = $this->mapDatosPagoDigitalTarjeta($selectusuario, $selectusuarioformpd, $data['EMPRESA']);
            $selectusuariodireccion_send    = $this->mapDatosResidenciaTarjeta($selectusuario, $selectusuariodireccion);
            
            $STATUS         = "success";
            $MESSAGE        = "Datos completos del usuario";

            parent::addLogPD("-----+> map usuario: " . json_encode($selectusuario_send));

        }

        return array(
            'status'            => $STATUS,
            'message'           => $MESSAGE,
            'datos_usuario'     => $selectusuario_send,
            'form_pago_digital' => $selectusuarioformpd_send,
            'datos_residencia'  => $selectusuariodireccion_send
        );
    }

    function obtenerMensajeRespuesta($response){

        parent::addLogPD("------+> [ información respuesta TOKE CARD PAGO DIGITAL ]: " . json_encode($response));

        $STATUS     = '';
        $MESSAGE    = '';

        switch($response['STATUS']){
            case '200':
                if(isset($response['RESPUESTA'])){
                    if( $response['RESPUESTA'] == 'Aprobada' ){
                        $STATUS     = 'success';
                        $MESSAGE    = 'Tarjeta de credito registrada exitosamente.';
                    }else{
                        $STATUS     = 'Negada';
                        $MESSAGE    = $response['RESPUESTA'];
                    }    
                }
                if(isset($response['ERROR'])){
                    if($response['CODIGO_ERROR'] == "PD17"){
                        $STATUS     = 'errorNumeroTarjeta';
                        $MESSAGE    = $response['ERROR'];

                    }else if($response['CODIGO_ERROR'] == 'PD09'){
                        $STATUS     = 'errorNumeroFranquicia';
                        $MESSAGE    = $response['ERROR'];

                    }else if($response['CODIGO_ERROR'] == 'PD39'){
                        $STATUS     = 'errorLimiteMaximoEnvios';
                        $MESSAGE    = $response['ERROR'];

                    }else if($response['CODIGO_ERROR'] == 'PD41'){
                        $STATUS     = 'errorMaximoRegistros';
                        $MESSAGE    = $response['ERROR'];

                    }
                }

                break;

            case '400':
                if(isset($response['MESSAGE'])){
                    $parte_json = substr($response['MESSAGE'], 23);
                    $parte_json = json_decode($parte_json, true);
                    
                    unset($parte_json['API_KEY']);
                    unset($parte_json['API_SECRET']);
                    unset($parte_json['API_TOKEN']);
                    
                    //{"STATUS":"400","STATUS_MESSAGE":"PD01","MESSAGE":"Informacion incompleta"}

                    $filtro_campos_vacios = array();
                    if( is_array( $parte_json ) ){
                        $filtro_campos_vacios = array_filter($parte_json, function($valor){
                            return strlen(trim($valor)) == 0;
                        });

                        $filtro_campos_vacios = array_keys($filtro_campos_vacios);

                    }else{

                    }
                    $STATUS     = 'errorDatosBBDD';
                    $MESSAGE    = 'El usuario tiene campos vacíos en la Base de datos';


                    return array('status' => $STATUS, 'message' => $MESSAGE, 'data' => $filtro_campos_vacios);
                    

                }
                break;

            default:
                $STATUS     = 'fail';
                $MESSAGE    = 'ERROR NO MANEJADO';

        }

        if ( $STATUS == "" && $MESSAGE == "" ) {
            $STATUS     = 'fail';
            $MESSAGE    = 'ERROR NO MANEJADO';
        }

        $result = array('status' => $STATUS, 'message' => $MESSAGE);


        return $result;
    }

    function existenCampos( Array $data ){
        $camposverificar = ['PAN', 'CVV2', 'MES_EXP', 'ANO_EXP', 'FRANQUICIA', 'EMPRESA', 'UID', 'NOMBRE_TARJETA'];
        $camposfaltantes = [];

        foreach ($camposverificar as $campo) {
            $existe_campo = array_key_exists ($campo, $data);

            if(!$existe_campo){
                array_push($camposfaltantes, $campo);
            }
        }
        
        return $camposfaltantes;
    }

    function verificarCamposInvalidos( Array $data ){
        $camposinvalidos = [];

        if( strlen(trim($data['UID'])) == 0 ){
            $camposinvalidos['UID'] = 'No puede estar vacío';
        }else if( !is_numeric($data['UID']) ){
            $camposinvalidos['UID'] = 'Debe ser numérico';
        }

        if( strlen(trim($data['EMPRESA'])) == 0 ){
            $camposinvalidos['EMPRESA'] = 'No puede estar vacío';
        }else if( !is_numeric($data['EMPRESA']) ){
            $camposinvalidos['EMPRESA'] = 'Debe ser numérico';
        }else if( intval(trim($data['EMPRESA'])) < 0 || intval(trim($data['EMPRESA'])) > 1 ){
            $camposinvalidos['EMPRESA'] = 'Los valores permitidos son 0 y 1';
        }

        if( strlen(trim($data['NOMBRE_TARJETA'])) == 0 ){
            $camposinvalidos['NOMBRE_TARJETA'] = 'No puede estar vacío';
        }

        if( strlen(trim($data['PAN'])) == 0 ){
            $camposinvalidos['PAN'] = 'No puede estar vacío';
        }else if( !is_numeric($data['PAN']) ){
            $camposinvalidos['PAN'] = 'Debe ser numérico';
        }

        if( strlen(trim($data['CVV2'])) == 0 ){
            $camposinvalidos['CVV2'] = 'No puede estar vacío';
        }else if( !is_numeric($data['CVV2']) ){
            $camposinvalidos['CVV2'] = 'Debe ser numérico';
        }

        if( strlen(trim($data['MES_EXP'])) == 0 ){
            $camposinvalidos['MES_EXP'] = 'No puede estar vacío';
        }else if( !is_numeric($data['MES_EXP']) ){
            $camposinvalidos['MES_EXP'] = 'Debe ser numérico';
        }else if( strlen(trim($data['MES_EXP'])) != 2 ){
            $camposinvalidos['MES_EXP'] = 'Debe contener exactamente 2 digitos';
        }

        if( strlen(trim($data['ANO_EXP'])) == 0 ){
            $camposinvalidos['ANO_EXP'] = 'No puede estar vacío';
        }else if( !is_numeric($data['ANO_EXP']) ){
            $camposinvalidos['ANO_EXP'] = 'Debe ser numérico';
        }else if( strlen(trim($data['ANO_EXP'])) != 2 ){
            $camposinvalidos['ANO_EXP'] = 'Debe contener exactamente 2 digitos';
        }

        if( strlen(trim($data['FRANQUICIA'])) == 0 ){
            $camposinvalidos['FRANQUICIA'] = 'No puede estar vacío';
        }else if( !is_numeric($data['FRANQUICIA']) ){
            $camposinvalidos['FRANQUICIA'] = 'Debe ser numérico';
        }
        
        return $camposinvalidos;
    }

    function crearTokenCard( Array $data ) {

        parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / DATA: " . json_encode($data));

        $camposfaltantes = $this->existenCampos($data);

        if( !empty($camposfaltantes) ) {
            return array(
                'status'            => 'faltanDatos',
                'message'           => 'Faltan datos',
                'campos faltantes'  => $camposfaltantes
            );
        }


        $camposinvalidos = $this->verificarCamposInvalidos($data);

        if( !empty($camposinvalidos) ) {
            return array(
                'status'            => 'camposInvalidos',
                'message'           => 'Campos inválidos',
                'campos invalidos'  => $camposinvalidos
            );
        }

        $data_usuario = $this->getUsuarioDatosTarjeta( $data );

        
        parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / data_usuario: " . json_encode($data_usuario));

        $CORREO   = "";
        $TELEFONO = "";
        if(intval($data['EMPRESA']) == 0 ){
            $CORREO   = $data_usuario['datos_usuario']['EMAIL'];
            $TELEFONO = $data_usuario['datos_usuario']['TELEFONO'];
        }else{
            $CORREO   = $data_usuario['datos_usuario']['EMAIL'];
            $TELEFONO = $data_usuario['datos_usuario']['TELEFONO'];
        }
        
        if($data_usuario['status'] == 'success'){
            $RESULT_TOKEN   = $this->generateToken();
            $TOKEN          = $RESULT_TOKEN['data'];

            $API_KEY        = $this->PagoDigitalVars['API_KEY'];
            $API_SECRET     = $this->PagoDigitalVars['API_SECRET'];
            $JWT            = $TOKEN;
            $PAN            = $data['PAN'];
            $CVV2           = $data['CVV2'];
            $MES_EXP        = $data['MES_EXP'];
            $ANO_EXP        = $data['ANO_EXP'];
            $NOMBRE         = $data_usuario['datos_usuario']['NOMBRE'];
            $CEDULA         = $data_usuario['datos_usuario']['IDENTIFICACION'];
            $DIRECCION      = $data_usuario['datos_residencia']['DIRECCION'];

            // $CORREO         = $data_usuario['form_pago_digital']['CORREO'];
            // $TELEFONO       = $data_usuario['form_pago_digital']['TELEFONO'];

            $CIUDAD         = $data_usuario['datos_residencia']['CIUDAD'];
            $DEPARTAMENTO   = $data_usuario['datos_residencia']['DEPARTAMENTO'];
            $FRANQUICIA     = $data['FRANQUICIA'];

            $tam                 = strlen( $data['PAN'] );
            $inicio              = $tam - 4;
            $fin                 = $tam;
            $ULTIMOS_DIGITOS_PAN = substr($data['PAN'], $inicio, $fin);

            $URL            = $this->PagoDigitalVars['BASE_URL'] . '/TARJETA_CREDITO/TOKEN_CARD/'; // PRODUCCIÓN

            $dataSend = array(
                'API_KEY'       => $API_KEY,
                'API_SECRET'    => $API_SECRET,
                'JWT'           => $TOKEN,
                'PAN'           => $PAN,
                'CVV2'          => $CVV2,
                'MES_EXP'       => $MES_EXP,
                'ANO_EXP'       => $ANO_EXP,
                'NOMBRE'        => $NOMBRE,
                'CEDULA'        => $CEDULA,
                'DIRECCION'     => $DIRECCION,
                'CORREO'        => $CORREO,
                'TELEFONO'      => $TELEFONO,
                'CIUDAD'        => $CIUDAD,
                'DEPARTAMENTO'  => $DEPARTAMENTO,
                'FRANQUICIA'    => $FRANQUICIA
            );

            $response = parent::remoteRequestPagoDigitalParams($URL, $dataSend );

            
            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / URL: " . $URL);
            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / dataSend: " . json_encode($dataSend));
            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / response: " . json_encode($response));


            $mensajerespuesta = $this->obtenerMensajeRespuesta($response);

            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / (0) mensajerespuesta: " . json_encode($mensajerespuesta));


            if ($mensajerespuesta['status'] == 'success') {
                

                $UID                = $data['UID'];
                $EMPRESA            = $data['EMPRESA'];
                $NOMBRE_TARJETA     = $data['NOMBRE_TARJETA'];
                $TOKEN              = $response['TOKEN'];
                $MICRODEBITO        = $response['MICRODEBITO'];
                $JSON_RESPUESTA     = json_encode($response);
                $FECHA_CREACION     = $this->getTimestamp();

                $verificarTokenSQL = "SELECT * FROM buyinbig.pago_digital_token_card WHERE UID = '$data[UID]' AND EMPRESA = '$data[EMPRESA]' AND TOKEN = '$TOKEN';";

                parent::conectar();
                $verificarToken = parent::consultaTodo($verificarTokenSQL);

                if(count($verificarToken) <= 0){
                    $insertartarjetaSQL = 
                    "INSERT INTO buyinbig.pago_digital_token_card
                    (
                        UID,
                        EMPRESA,
                        NOMBRE,
                        TOKEN,
                        CODIGO_PAN,
                        MICRODEBITO,
                        PAGO_DIGITAL_FRANQUICIAS_ID,
                        JSON_RESPUESTA,
                        FECHA_CREACION
                    )
                    VALUES
                    (
                        '$UID',
                        '$EMPRESA',
                        '$NOMBRE_TARJETA',
                        '$TOKEN',
                        $ULTIMOS_DIGITOS_PAN,
                        '$MICRODEBITO',
                        $data[FRANQUICIA],
                        '$JSON_RESPUESTA',
                        '$FECHA_CREACION'
                    )";

                    $mensajerespuesta['data']['NOMBRE_TARJETA'] = $NOMBRE_TARJETA;
                    $mensajerespuesta['data']['TOKEN']          = $TOKEN;
                    $mensajerespuesta['data']['FRANQUICIA']     = $data['FRANQUICIA'];

                    parent::queryRegistro($insertartarjetaSQL);
                }else{
                    $mensajerespuesta['status']   = 'errorTokenDuplicado';
                    $mensajerespuesta['message']  = 'Esta tarjeta ya ha sido registrada!';
                    
                }

                parent::cerrar();
            }

            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / (1) mensajerespuesta: " . json_encode($mensajerespuesta));

            return $mensajerespuesta;
        }else{
            $STATUS    = $data_usuario['status'];
            $MESSAGE   = $data_usuario['message'];

            $mensajerespuesta = array(
                'status' => $STATUS,
                'message' => $MESSAGE
            );
    
            parent::addLogPD("------+> PAGO DIGITAL / CREAR TOKEN CARD / (2) mensajerespuesta: " . json_encode($mensajerespuesta));
            return $mensajerespuesta;
        }
    }

    function confirmarTokenCard( Array $data ) {
        if( !isset($data) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['TOKEN'] ) || !isset( $data['MICRODEBITO'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - Validar Token Card POST CrearTokenCard().',
                'data'    => $data
            );
        }

        $pago_digital_token_card =
            "SELECT * FROM buyinbig.pago_digital_token_card WHERE UID = $data[UID] AND EMPRESA = $data[EMPRESA] AND TOKEN = '$data[TOKEN]' AND MICRODEBITO = $data[MICRODEBITO];";
        
        parent::conectar();
        $pago_digital_token_card = parent::consultaTodo( $pago_digital_token_card );
        parent::cerrar();

        if( count( $pago_digital_token_card ) == 0 ){
            return array(
                'status'  => 'errorDatosIncorrectos',
                'message' => 'Datos incorrectos.',
                'data'    => $data
            );
        }

        $pago_digital_token_card = $pago_digital_token_card[0];
        $pago_digital_token_card['ESTADO'] = floatval( $pago_digital_token_card['ESTADO'] );

        if( $pago_digital_token_card['ESTADO'] == 1 ){
            return array(
                'status'  => 'success',
                'message' => 'Felicidades cuentas con una nueva tj. El tope maximo es de 3 tarjetas.',
                'data'    => null
            );
        }

        $update       = "UPDATE buyinbig.pago_digital_token_card SET ESTADO = 1 WHERE ID = $pago_digital_token_card[ID];";
        parent::conectar();
        $rowAffect = parent::query( $update );
        parent::cerrar();

        if( $rowAffect == 0){
            return array(
                'status'  => 'errorUpdate',
                'message' => 'En estos momentos no fue posible activar este tj. Vuelve mas tarde.',
                'data'    => $data
            );
        }else{
            return array(
                'status'  => 'success',
                'message' => 'Felicidades cuentas con una nueva tj. El tope maximo es de 3 tarjetas.',
                'data'    => null
            );
        }
    }

    function obtenerIdTarjetas() {
        //Obtiene todos los id de las franquicias de tarjetas

        $STATUS     = "fail";
        $MESSAGE    = "No existen registros.";

        $consulta   = "SELECT * FROM buyinbig.pago_digital_franquicias";

        parent::conectar();
        $id_tarjetas = parent::consultaTodo($consulta);
        parent::cerrar();
 
        if(count($id_tarjetas) > 0){
            $STATUS     = "success";
            $MESSAGE    = "Lista de identificadores de las tarjetas";

            return array(
                'status'    => $STATUS,
                'message'   => $MESSAGE,
                'data'      => $id_tarjetas
            );
        } 

        return array(
            'status'    => $STATUS,
            'message'   => $MESSAGE,
            'data'      => []
        );
    }

    function obtenerTokenCardAll( Array $data ) {
        // Todos los tokens de un usuario.
        if(!isset($data) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] )){
            return array(
                'status'   => 'fail',
                'message'  => 'Faltan datos',
                'data'     => $data
            );
        }
        
        $consultaSQL_ACTIVAS =
        "SELECT
            pdtc.ID AS ID_TARJETA,
            pdtc.NOMBRE AS 'NOMBRE_ORIGINAL',
            CONCAT('***********', pdtc.CODIGO_PAN) AS 'NOMBRE',
            pdtc.TOKEN,
            pdtc.CODIGO_PAN AS PAN,
            pdtc.pago_digital_franquicias_id,
            pdf.FRANQUICIA,
            pdtc.FECHA_CREACION,
            pdtc.ESTADO
        FROM buyinbig.pago_digital_token_card pdtc
        LEFT JOIN buyinbig.pago_digital_franquicias pdf ON(pdtc.pago_digital_franquicias_id = pdf.id)
        WHERE pdtc.UID = '$data[UID]' AND pdtc.EMPRESA = '$data[EMPRESA]' AND pdtc.ESTADO = 1
        ORDER BY pdtc.pago_digital_franquicias_id, pdtc.FECHA_CREACION DESC;";

        $consultaSQL_INACTIVAS =
        "SELECT
            pdtc.ID AS ID_TARJETA,
            pdtc.NOMBRE,
            pdtc.TOKEN,
            pdtc.CODIGO_PAN AS PAN,
            pdtc.pago_digital_franquicias_id,
            pdf.FRANQUICIA,
            pdtc.FECHA_CREACION,
            pdtc.ESTADO
        FROM buyinbig.pago_digital_token_card pdtc
        LEFT JOIN buyinbig.pago_digital_franquicias pdf ON(pdtc.pago_digital_franquicias_id = pdf.id)
        WHERE pdtc.UID = '$data[UID]' AND pdtc.EMPRESA = '$data[EMPRESA]' AND pdtc.ESTADO = 0
        ORDER BY pdtc.pago_digital_franquicias_id, pdtc.FECHA_CREACION DESC;";

        parent::conectar();
        $tarjetas_activas = parent::consultaTodo($consultaSQL_ACTIVAS);
        $tarjetas_inactivas = parent::consultaTodo($consultaSQL_INACTIVAS);
        parent::cerrar();

        $STATUS     = 'success';
        $MESSAGE    = 'Tarjetas registradas';

        if( COUNT($tarjetas_activas) == 0 && COUNT($tarjetas_inactivas) == 0 ){
            $STATUS     = 'registrosVacios';
            $MESSAGE    = 'Este usuario no tiene tarjetas registradas';
            $tarjetas_activas   = null;
        }

        $tarjetas = array();

        if( COUNT($tarjetas_activas) > 0 ){
            $tarjetas = array_merge($tarjetas, $tarjetas_activas);
        }
        if( COUNT($tarjetas_inactivas) > 0 ){
            $tarjetas = array_merge($tarjetas, $tarjetas_inactivas);
        }

        return array(
            'status'             => $STATUS,
            'message'            => $MESSAGE,
            'data'               => $tarjetas,
            'tarjetas_activas'   => $tarjetas_activas,
            'tarjetas_inactivas' => $tarjetas_inactivas
        );
    }

    function obtenerTokenCard( Array $data ) {
        // Todos los tokens de un usuario.
        if(!isset($data) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] )){
            return array(
                'status'   => 'fail',
                'message'  => 'Faltan datos',
                'data'     => $data
            );
        }
        
        $consultaSQL_ACTIVAS =
        "SELECT
            pdtc.ID AS ID_TARJETA,
            pdtc.NOMBRE AS 'NOMBRE_ORIGINAL',
            CONCAT('***********', pdtc.CODIGO_PAN) AS 'NOMBRE',
            pdtc.TOKEN,
            pdtc.CODIGO_PAN AS PAN,
            pdtc.pago_digital_franquicias_id,
            pdf.FRANQUICIA,
            pdtc.FECHA_CREACION,
            pdtc.ESTADO
        FROM buyinbig.pago_digital_token_card pdtc
        LEFT JOIN buyinbig.pago_digital_franquicias pdf ON(pdtc.pago_digital_franquicias_id = pdf.id)
        WHERE pdtc.UID = '$data[UID]' AND pdtc.EMPRESA = '$data[EMPRESA]' AND pdtc.ESTADO = 1
        ORDER BY pdtc.pago_digital_franquicias_id, pdtc.FECHA_CREACION DESC;";

        parent::conectar();
        $tarjetas = parent::consultaTodo($consultaSQL_ACTIVAS);
        parent::cerrar();

        $STATUS     = 'success';
        $MESSAGE    = 'Tarjetas registradas';

        if( COUNT($tarjetas) == 0 ){
            $STATUS     = 'registrosVacios';
            $MESSAGE    = 'Este usuario no tiene tarjetas registradas';
            $tarjetas   = null;
        }

        return array(
            'status'             => $STATUS,
            'message'            => $MESSAGE,
            'data'               => $tarjetas
        );
    }

    function eliminarTokenCard( Array $data ) {
        if( !isset( $data ) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['ID_TARJETA']) ){
            return array(
                'status'   => 'fail',
                'message'  => 'Faltan datos',
                'data'     => $data
            );
        }

        $STATUS     = 'fail';
        $MESSAGE    = 'La tarjeta no existe';

        $verificarSQL = "SELECT * FROM buyinbig.pago_digital_token_card
            WHERE UID = '$data[UID]'
            AND EMPRESA = '$data[EMPRESA]'
            AND ID = '$data[ID_TARJETA]'";
        
        parent::conectar();
        $existeTarjeta = parent::consultaTodo($verificarSQL);

        if( count( $existeTarjeta ) ){
            $STATUS     = 'success';
            $MESSAGE    = 'Tarjeta eliminada exitosamente!';

            $consultaSQL = "DELETE FROM buyinbig.pago_digital_token_card
                WHERE UID = '$data[UID]'
                AND EMPRESA = '$data[EMPRESA]'
                AND ID = '$data[ID_TARJETA]'";

            parent::query($consultaSQL);
        }
        
        parent::cerrar();

        return array(
            'status'   => $STATUS,
            'message'  => $MESSAGE
        );
    }

    function editarNombreCard( Array $data ) {
        if( !isset( $data ) || !isset( $data['UID'] ) || !isset( $data['EMPRESA'] ) || !isset( $data['ID_TARJETA']) || !isset( $data['NOMBRE_TARJETA'])){
            return array(
                'status'   => 'fail',
                'message'  => 'Faltan datos',
                'data'     => $data
            );
        }

        $STATUS     = 'fail';
        $MESSAGE    = 'La tarjeta no existe';

        $verificarSQL = "SELECT * FROM buyinbig.pago_digital_token_card
            WHERE UID = '$data[UID]'
            AND EMPRESA = '$data[EMPRESA]'
            AND ID = '$data[ID_TARJETA]'";
        
        parent::conectar();
        $existeTarjeta = parent::consultaTodo($verificarSQL);

        if( count( $existeTarjeta ) ){
            $STATUS     = 'success';
            $MESSAGE    = 'Tarjeta editada exitosamente!';

            $consultaSQL = "UPDATE buyinbig.pago_digital_token_card
                SET NOMBRE = '$data[NOMBRE_TARJETA]'
                WHERE UID = '$data[UID]'
                AND EMPRESA = '$data[EMPRESA]'
                AND ID = '$data[ID_TARJETA]'";
                            
            parent::query($consultaSQL);
        }
        
        parent::cerrar();

        return array(
            'status'   => $STATUS,
            'message'  => $MESSAGE
        );
    }

    function sendOrdenPagoTarjeta( Array $data ) {
        if( !isset( $data ) || !isset( $data['ID'] ) || !isset( $data['TOKEN'] ) || !isset( $data['CUOTAS']) ){
            return array(
                'status'   => 'fail',
                'message'  => 'Faltan datos',
                'data'     => $data
            );
        }
        $data['SLUG'] = $data['ID'];
        $dataSend     = $this->obtenerOrdenPago( $data );
        if ( !isset($dataSend) ) {
            return array(
                'status'  => 'errorIdOrdenPago',
                'message' => 'No se hallo información para esta orden de pago.',
                'data'    => $data
            );
        }
        $dataSend = $dataSend['data'];

        $fecha = $this->getTimestamp();

        $updatexordenxdexpago = 
        "UPDATE pago_digital 
        SET 
            METODO_PAGO_USADO_ID = 4,
            FECHA_ACTUALIZACION  = '$fecha'
        WHERE ID = '$dataSend[ID]';";

        parent::conectar();
        $update = parent::query($updatexordenxdexpago);
        parent::cerrar();

        $fecha = $this->getTimestamp();

        $selectxpagoxdigitalxtokenxcard = "SELECT * FROM buyinbig.pago_digital_token_card WHERE TOKEN = '$data[TOKEN]' AND UID = $dataSend[UID] AND EMPRESA = $dataSend[EMPRESA];";

        parent::conectar();
        $pago_digital_token_card = parent::consultaTodo($selectxpagoxdigitalxtokenxcard);
        parent::cerrar();


        if ( count($pago_digital_token_card) == 0) {
            return array(
                'status'     =>'errorTokenCardPD',
                'message'    =>'No se hallo el token card de tu tarjeta.',
                'data'       => null
            );
        }
        $pago_digital_token_card = $pago_digital_token_card[0];
        parent::addLogPD("----+> [TOKEN CARD]: " . json_encode($pago_digital_token_card));
        parent::addLogPD("----+> [ dataSend ]: " . json_encode($dataSend));

        $respuesta = [];
        parent::conectar();
        foreach ($dataSend['JSON_PROVEEDORES'] as $key => $JSON_PROVEEDORE) {

            $selectxpagoxdigitalxreferenciasxtarjetaxdexcredito = 
            "SELECT * FROM buyinbig.pago_digital_referencias_tarjeta_de_credito WHERE PAGO_DIGITAL_ID = $JSON_PROVEEDORE[PAGO_DIGITAL_ID] AND PAGO_DIGITAL_JSON_PROVEEDORES_ID = $JSON_PROVEEDORE[ID];";


            
            $selectxpagoxdigitalxreferenciasxtarjetaxdexcredito = parent::consultaTodo($selectxpagoxdigitalxreferenciasxtarjetaxdexcredito);

            parent::addLogPD("----+> [ selectxpagoxdigitalxreferenciasxtarjetaxdexcredito ]: " . json_encode($selectxpagoxdigitalxreferenciasxtarjetaxdexcredito));

            if ( count( $selectxpagoxdigitalxreferenciasxtarjetaxdexcredito ) == 0 ) {


                $insertxpagoxdigitalxreferenciasxtarjetaxdexcredito = 
                    "INSERT INTO buyinbig.pago_digital_referencias_tarjeta_de_credito (
                        PAGO_DIGITAL_ID,
                        PAGO_DIGITAL_JSON_PROVEEDORES_ID,
                        PRODUCTO_UID,

                        PAGO_DIGITAL_TOKEN_CARD_ID,
                        PAGO_DIGITAL_TOKEN_CARD_TOKEN,

                        AMOUNT,
                        CUOTAS,

                        REFPAY,
                        PORCENTAJE_COMISION,
                        ID_PROVEEDOR,
                        COSTO_FLETE,

                        FECHA_CREACION,
                        FECHA_ACTUALIZACION
                    ) VALUES (
                        $JSON_PROVEEDORE[PAGO_DIGITAL_ID],
                        $JSON_PROVEEDORE[ID],
                        $JSON_PROVEEDORE[PRODUCTO_UID],

                        $pago_digital_token_card[ID],
                        '$pago_digital_token_card[TOKEN]',

                        $JSON_PROVEEDORE[VALOR],
                        $data[CUOTAS],

                        '$JSON_PROVEEDORE[PRODUCTO]',
                        $JSON_PROVEEDORE[PORCENTAJE_COMISION],
                        $JSON_PROVEEDORE[ID_PROVEEDOR],
                        $JSON_PROVEEDORE[COSTO_FLETE],

                        $fecha,
                        $fecha
                    );";
                
                $result = parent::queryRegistro($insertxpagoxdigitalxreferenciasxtarjetaxdexcredito);

                parent::addLogPD("----+> [ QUERY ]: " . $insertxpagoxdigitalxreferenciasxtarjetaxdexcredito);
                parent::addLogPD("----+> [ INSER ]: " . $result);

                if ( $result > 0 ) {
                    $respuesta[] = array(
                        'result' => $result,
                        'data'   => $insertxpagoxdigitalxreferenciasxtarjetaxdexcredito
                    );
                }
            }
        }
        parent::cerrar();

        $ESTATUS       = 'success';
        $MESSAGE       = 'Proceso almacenado';
        $DATA_RESPONSE = $dataSend;
        if ( count( $respuesta ) == 0 ) {
            $ESTATUS = 'fail';
            $MESSAGE = 'No se registro el mismo numéro de transacciones';

            $DATA_RESPONSE = null;
        }
        return array(
            'status'     => $ESTATUS,
            'message'    => $MESSAGE,
            'dataExtra'  => $DATA_RESPONSE
        );
    }

    function finalizarTransaccionTarjetaMasivo( Array $data ){

        return array(
            'status'   => 'fail',
            'message'  => 'No hay transacciones pendientes',
            'data'     => null
        );

        if( !isset($data) || !isset( $data['password'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'Faltan datos por enviar al servicio - solicitar PIN',
                'data'    => $data
            );
        }
        if ( $data['password'] != "contraseña super segura de mario palma") {
            return array(
                'status'  => 'failPassword',
                'message' => '¿No te sabes la clave? Estas jodio...'
            );
        }

        $EN_PROCESO = $this->ESTADOS_TRANSACCIONES['EN_PROCESO'];
        $APROBADA   = $this->ESTADOS_TRANSACCIONES['FINALIZA'];
        $RECHAZADA   = $this->ESTADOS_TRANSACCIONES['RECHAZADA'];

        $select = 
            "SELECT * FROM 
                (
                    SELECT 
                        *
                    FROM buyinbig.pago_digital_referencias_tarjeta_de_credito  
                    WHERE ESTADO = $EN_PROCESO
                    ORDER BY FECHA_ACTUALIZACION ASC
                    LIMIT 5
                ) AS inf
            GROUP BY inf.PAGO_DIGITAL_TOKEN_CARD_TOKEN;";

        parent::conectar();
        $pago_digital_referencias_tarjeta_de_credito = parent::consultaTodo( $select );
        parent::cerrar();

        if ( count( $pago_digital_referencias_tarjeta_de_credito ) == 0 ) {
            return array(
                'status'   => 'fail',
                'message'  => 'No hay transacciones pendientes',
                'data'     => null
            );
        }

        parent::addLogPD("---+> (1) [TARJETA DE CREDITO MASIVO / Array]: " . json_encode($pago_digital_referencias_tarjeta_de_credito));
        
        $RESULT_TOKEN = $this->generateToken();

        if ( $RESULT_TOKEN['status'] != "success" ) {
            return array(
                'status'     =>'errorTokenPD',
                'message'    =>'No fue posible generar el token de autentificación PD.',
                'data'       => null
            );
        }
        $TOKEN        = $RESULT_TOKEN['data'];
        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/TARJETA_CREDITO/PROCESS_TRANSACTION/';

        $results_list = [];


        $array_pago_digital = array();
        foreach ($pago_digital_referencias_tarjeta_de_credito as $key => $referencias_tarjeta_de_credito) {

            $PAGO_DIGITAL_ID = $referencias_tarjeta_de_credito['PAGO_DIGITAL_ID'];
            if( !isset($array_pago_digital[ $PAGO_DIGITAL_ID ]) ){
                parent::conectar();
                $result_pago_digital = parent::consultaTodo("SELECT * FROM buyinbig.pago_digital WHERE ID = $PAGO_DIGITAL_ID;");
                parent::cerrar();
                $array_pago_digital[ $PAGO_DIGITAL_ID ] = $result_pago_digital[0];

            }
            $referencias_tarjeta_de_credito['pago_digital'] = $array_pago_digital[ $PAGO_DIGITAL_ID ];

            $dataSend = array(
                'API_KEY'             => $this->PagoDigitalVars['API_KEY'],
                'API_SECRET'          => $this->PagoDigitalVars['API_SECRET'],
                'TOKEN'               => $referencias_tarjeta_de_credito['PAGO_DIGITAL_TOKEN_CARD_TOKEN'],
                'AMOUNT'              => floatval($referencias_tarjeta_de_credito['AMOUNT']),
                'CUOTAS'              => intval($referencias_tarjeta_de_credito['CUOTAS']),
                'REFPAY'              => $referencias_tarjeta_de_credito['REFPAY'],
                'PORCENTAJE_COMISION' => floatval($referencias_tarjeta_de_credito['PORCENTAJE_COMISION']),
                'ID_PROVEEDOR'        => intval($referencias_tarjeta_de_credito['ID_PROVEEDOR']),
                'COSTO_FLETE'         => floatval($referencias_tarjeta_de_credito['COSTO_FLETE']),
                'JWT'                 => $TOKEN
            );

            parent::addLogPD("---+> (2) [TARJETA DE CREDITO MASIVO / URL]: " . $URL);
            parent::addLogPD("---+> (3) [TARJETA DE CREDITO MASIVO / dataSend]: " . json_encode($dataSend));
            $response = parent::remoteRequestPagoDigital($URL, $dataSend);

            parent::addLogPD("---+> (4) [TARJETA DE CREDITO MASIVO / response]: " . json_encode($response));

            $ESTADO                 = $referencias_tarjeta_de_credito['ESTADO'];
            $RESPUESTA              = $referencias_tarjeta_de_credito['ESTADO_DESCRIPCION'];
            $JSON_RESPUESTA         = json_encode($response);

            if ( isset($response['ESTADO']) ) {
                $ESTADO         = $response['ESTADO'];
                $RESPUESTA      = $response['ESTADO'];
            
            }

            $fecha = $this->getTimestamp();

            parent::addLogPD("---+> (5) [TARJETA DE CREDITO MASIVO / Condicionales]: " . $ESTADO);

            if ( $ESTADO == "Aprobada") {

                $RESPUESTA_JSON = json_encode($response);

                $update =
                "UPDATE buyinbig.pago_digital_referencias_tarjeta_de_credito 
                SET
                   ESTADO              = $APROBADA,
                   ESTADO_DESCRIPCION  = 'FINALIZADA',
                   RESPUESTA_JSON      = '$RESPUESTA_JSON',
                   FECHA_ACTUALIZACION = $fecha
                WHERE ID = $referencias_tarjeta_de_credito[ID];";

                parent::addLogPD("---+> (5) [TARJETA DE CREDITO MASIVO / APROBADA / QUERY]: " . $update);

                parent::conectar();
                $update = parent::query( $update );
                parent::cerrar();

                
                parent::addLogPD("---+> (6) [TARJETA DE CREDITO MASIVO / APROBADA / QUERY / RESULT]: " . $update);

                $resultCallback = $this->callback_TarjetaDeCredito( $referencias_tarjeta_de_credito );

                parent::addLogPD("---+> (18) [TARJETA DE CREDITO MASIVO / resultCallback]: " . $resultCallback);

                $results_list[] = array(
                    'dataSend' => $dataSend,
                    'response' => $response,
                    'update'   => $update,
                    'callback' => $resultCallback
                );
                
            }else{

                $RESPUESTA_JSON = json_encode($response);

                $update =
                "UPDATE buyinbig.pago_digital_referencias_tarjeta_de_credito 
                SET
                   ESTADO              = $RECHAZADA,
                   ESTADO_DESCRIPCION  = 'Rechazada',
                   RESPUESTA_JSON      = '$RESPUESTA_JSON',
                   FECHA_ACTUALIZACION = $fecha
                WHERE ID = $referencias_tarjeta_de_credito[ID];";

                $updateProductoTransaccion = 
                "UPDATE buyinbig.pago_digital
                SET
                    TRANSACCION_FINALIZADA = $RECHAZADA
                WHERE ID = $referencias_tarjeta_de_credito[PAGO_DIGITAL_ID];";

                parent::conectar();
                $update         = parent::query( $update );
                $resultUpdatePD = parent::query($updateProductoTransaccion);
                parent::cerrar();

                $results_list[] = array(
                    'dataSend' => $dataSend,
                    'response' => $response,
                    'update'   => null,
                    'callback' => null
                );
            }
        }

        return array(
            'status' => 'success',
            'data'   => $results_list
        );
    }

    
    function callback_TarjetaDeCredito( Array $data )
    {
        $data['pago_digital']['TIPO'] = intval( $data['pago_digital']['TIPO'] );
        if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_COMPRA_Y_VENTA ) {
            return $this->callback_TarjetaDeCredito_comprayventa( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_RECARGA ) {
            return $this->callback_recarga( $data );






        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_TIQUETES_COMPRAS ) {
            return $this->callback_recargaTiquetes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_TIQUETES_VENTAS ) {
            return $this->callback_recargaTiquetes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_PLANES_COMPRAS ) {
            return $this->callback_recargaPlanes( $data );

        }else if ( $data['pago_digital']['TIPO'] == $this->TRANSACCION_ADQUIRIR_PLANES_VENTAS ) {
            return $this->callback_recargaPlanes( $data );



        }else{
        }
        return array(
            'status' => 'success',
            'message' => 'No se gestiono nada en el callback Efecty'
        );
    }

    function callback_TarjetaDeCredito_comprayventa( Array $data )
    {
        // Creación 27 abril 2021 - probar
        $fecha = $this->getTimestamp();

        $selectxtransaccionxconxpagoxdigital = 
        "SELECT * FROM buyinbig.productos_transaccion WHERE pago_digital_id = $data[PAGO_DIGITAL_ID] AND id_producto = $data[PRODUCTO_UID];";

        parent::addLogPD("---+> (7) [TARJETA DE CREDITO MASIVO / QUERY / productos_transaccion]: " . $selectxtransaccionxconxpagoxdigital);

        parent::conectar();
        $selectxtransaccionxconxpagoxdigital = parent::consultaTodo( $selectxtransaccionxconxpagoxdigital );
        parent::cerrar();


        if ( count( $selectxtransaccionxconxpagoxdigital ) == 0) {
            return array(
                'status'     => 'errorTransaccionesVacias',
                'message'    => 'No se hallarón transacciones para la referencia de PD: ' . $data['pago_digital']['ID'],
                'data'       => $selectxtransaccionxconxpagoxdigital,
                'query'      => "SELECT * FROM buyinbig.productos_transaccion WHERE pago_digital_id = $data[PAGO_DIGITAL_ID] AND id_producto = $data[PRODUCTO_UID];"
            );
        }

        parent::addLogPD("---+> (8) [TARJETA DE CREDITO MASIVO / ARRAY / productos_transaccion]: " . json_encode($selectxtransaccionxconxpagoxdigital));

        $info = array();
        foreach ($selectxtransaccionxconxpagoxdigital as $key => $producto_transaccion) {
            $update = 
            "UPDATE buyinbig.productos_transaccion SET estado = 6, fecha_actualizacion = $fecha WHERE id = {$producto_transaccion['id']};";

            parent::addLogPD("---+> (9) [TARJETA DE CREDITO MASIVO / UPDATE / productos_transaccion]: " . $update);

            parent::conectar();
            $update = parent::query( $update );

            parent::addLogPD("---+> (10) [TARJETA DE CREDITO MASIVO / UPDATE / productos_transaccion]: " . $update);

            if ($update) {
                $producto_transaccion['estado'] = 6;
                $producto_transaccion['fecha_actualizacion'] = $fecha;
            }

            parent::addLogPD("---+> (11) [TARJETA DE CREDITO MASIVO / UPDATE / productos_transaccion]: " . json_encode($producto_transaccion));

            parent::cerrar();
            array_push($info, $producto_transaccion);

            $notificacion = new Notificaciones();
            $notificacion->insertarNotificacion([
                'uid' => $producto_transaccion['uid_vendedor'],
                'empresa' => $producto_transaccion['empresa'],
                'text' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'es' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'en' => "Reflected payment. The status of transaction REF: #". $producto_transaccion['id_carrito'] ." has been updated.",

                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_ventas'
            ]);
            $notificacion->insertarNotificacion([
                'uid' => $producto_transaccion['uid_comprador'],

                'empresa' => $producto_transaccion['empresa_comprador'],

                'text' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'es' => 'Pago reflejado. El estado de la transacción REF: #'.$producto_transaccion['id_carrito'].' ha sido actualizado.',

                'en' => "Reflected payment. The status of transaction REF: #". $producto_transaccion['id_carrito'] ." has been updated.",

                'keyjson' => '',
                'url' => 'mis-cuentas.php?tab=sidenav_compras'
            ]);
            unset($notificacion);

        }

        $URL = "http://nasbi.peers2win.com/api/controllers/ventas/?actualizar_timelines_transacciones";
        $dataSend = array(
            "data" => $info
        );
        $response = parent::remoteRequest($URL, $dataSend );

        parent::addLogPD("---+> (12) [TARJETA DE CREDITO MASIVO / URL]: " . $URL);
        parent::addLogPD("---+> (13) [TARJETA DE CREDITO MASIVO / dataSend]: " . json_encode($dataSend));
        parent::addLogPD("---+> (14) [TARJETA DE CREDITO MASIVO / response]: " . json_encode($response));
        
        parent::conectar();
        $selectxpendientes = parent::consultaTodo("SELECT * FROM buyinbig.productos_transaccion WHERE pago_digital_id = $data[PAGO_DIGITAL_ID] AND estado < 6;");
        parent::cerrar();

        parent::addLogPD("---+> (15) [TARJETA DE CREDITO MASIVO / SELECT / productos_transaccion]: " . json_encode($selectxpendientes));

        if ( count( $selectxpendientes ) == 0) {
            $TRANSACCION_FINALIZADA = $this->ESTADOS_TRANSACCIONES['FINALIZA'];

            $updateProductoTransaccion = "UPDATE buyinbig.pago_digital SET TRANSACCION_FINALIZADA = $TRANSACCION_FINALIZADA WHERE ID = $data[PAGO_DIGITAL_ID];";
            
            parent::addLogPD("---+> (16) [TARJETA DE CREDITO MASIVO / UPDATE / pago_digital]: " . $updateProductoTransaccion);


            parent::conectar();
            $resultUpdatePD = parent::query($updateProductoTransaccion);
            parent::cerrar();

            parent::addLogPD("---+> (17) [TARJETA DE CREDITO MASIVO / UPDATE / RESULT / pago_digital]: " . $resultUpdatePD);
        }

        return $response;
    }

    function getTimestamp(){
        $fechaActual = new DateTime();
        $timezone = new DateTimeZone('America/Bogota');
        $fechaActual->format("U");
        $fechaActual->setTimezone($timezone);
        // var_dump($fechaActual);
        return $fechaActual->getTimestamp()*1000;
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


    function generarSolicitudDispersion($data2) {
        //print_r($data2);
        if (!isset($data2["uid"])) {
            return array("status"=>"error", "message"=>"Debes enviar todos los parametros para poder continuar....", "datos"=>$data2);
        }
        parent::conectar();
        $elementos = "";
        foreach ($data2["datos"] as $data) {
          //  print_r($data);
            if (!isset($data["uid_cliente"]) || !isset($data["empresa"]) || !isset($data["valor_transferencia"]) || !isset($data["concepto"]) || !isset($data["id_referencia_tx"])) {
                return array("status"=>"error", "message"=>"Debes enviar todos los parametros para poder continuar", "datos"=>$data);       
            }
        }
        $responseEmail = "";
        foreach ($data2["datos"] as $data) {
            $consultar = parent::consultaTodo("select * from buyinbig.productos_transaccion where esta_liquidado = 1 and id = $data[id_referencia_tx]");
            //print_r($consultar);
            if (count($consultar) == 0) {
                $codigo = rand(100000, 999999);
                parent::addLogDP("INSERT INTO buyinbig.solicitudes_dispercion (uid, empresa, valor_transferencia, concepto, ID_REFERENCIA_TX, fecha_creacion, fecha_actualizacion, codigo, uid_encargado) VALUES ($data[uid_cliente], $data[empresa], $data[valor_transferencia], '$data[concepto]', '$data[id_referencia_tx]', current_timestamp, null, '$codigo', $data2[uid]);");
                $registrar = parent::queryRegistro("INSERT INTO buyinbig.solicitudes_dispercion (uid, empresa, valor_transferencia, concepto, ID_REFERENCIA_TX, fecha_creacion, fecha_actualizacion, codigo, uid_encargado) 
                    VALUES ($data[uid_cliente], $data[empresa], $data[valor_transferencia], '$data[concepto]', '$data[id_referencia_tx]', current_timestamp, null, '$codigo', $data2[uid]); ");
                parent::queryRegistro("update buyinbig.productos_transaccion set esta_liquidado = 1 where id = $data[id_referencia_tx]");
                parent::addLogDP("Solicitud de nueva dispersion creada por el usuario $data2[uid], Numero de solicitud: $registrar");    
                $responseEmail = $this->enviarCorreoSolicitudDispercion(Array("monto"=>$data["valor_transferencia"], "id_referencia_tx"=>$data["id_referencia_tx"], "codigo"=>$codigo));
            }
            
        }
        return array("status"=>"succes", "message"=>"Se te ha enviado un correo electronico con el codigo para poder realizar la dispersion", "respuesta"=> $data2["datos"]);
    }

    function enviarCorreoSolicitudDispercion($data) {
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/correo_verificar_codigo_papgo.html");
        $json = json_decode(file_get_contents("/var/www/html/peer2win/js/jsonCorreo_es.json"));
        $json2 = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = str_replace("{{monto}}", $data['monto'], $html);
        $html = str_replace("{{codigo}}", $data['codigo'], $html);
        $html = str_replace("{{trans_06}}", $json2->trans06_, $html);
        $html = str_replace("{{trans_07}}", $json2->trans07_, $html);
        $html = str_replace("{{logo_footer}}", $json->logo_footer, $html);
        $html = str_replace("{{link_a_promociones}}", "https://nasbi.com/content/descubre.php", $html);
        $html = str_replace("{{link_a_escuela}}", "https://nasbi.com/content/escuela-vendedores.php", $html);
        $html = str_replace("{{link_a_marketplace}}", "https://nasbi.com/content/escuela-vendedores.php", $html);
        //$html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data["id"]."&act=0&em=0", $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $para = "mariopalma199318@gmail.com";
        $titulo    = "Solicitud de pago de liquidación orden #$data[id_referencia_tx]";
        $mensaje1  = $html;
        $cabeceras = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $cabeceras .= "From: info@peers2win.com\r\n";
        
        //$dataArray = array("email"=>"he03villa@gmail.com", "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>"varongrowth@gmail.com", "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras));

        //print_r($response);
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        //print_r($response);
        return $response;
    }

    function realizarSolicitudDispersion($data) {
        if (!isset($data["uid"]) || !isset($data["uid_cliente"]) || !isset($data["empresa"]) || !isset($data["valor_transferencia"]) || !isset($data["codigo"]) || !isset($data["id_referencia_tx"])) {
            return array("status"=>"error", "message"=>"Debes enviar todos los parametros para poder continuar");
        }
        parent::conectar();
        $consulta = parent::consultaTodo("select * from buyinbig.solicitudes_dispercion sd where empresa = $data[empresa] and uid = $data[uid_cliente] and ID_REFERENCIA_TX = $data[id_referencia_tx] and valor_transferencia = $data[valor_transferencia]  and uid_encargado = $data[uid]");
        if (count($consulta) > 0) {
            if ($consulta[0]["codigo"] == $data["codigo"]) {
                $consulta2 = parent::consultaTodo("select * from buyinbig.productos_transaccion pt where id = $data[id_referencia_tx]");
                if (count($consulta2) > 0) {
                    if ($consulta2[0]["esta_liquidado"] == 0) {
                        return array("status"=>"error", "message"=>"La solicitud de pago no esta en proceso");
                    } else if ($consulta2[0]["esta_liquidado"] == 1) {
                        $consulta3 = parent::consultaTodo("select * from buyinbig.pago_digital_proveedores pdp where uid = $data[uid_cliente] and empresa = $data[empresa]");
                        if (count($consulta3) == 0) {
                            return array("status"=>"error", "message"=>"No se encontro el id del proveedor");
                        }
                        $URL          = $this->PagoDigitalVars['BASE_URL'] . '/DISPERSION_CUENTA/';
                        $RESULT_TOKEN = $this->generateToken();
                        if ( $RESULT_TOKEN['status'] != "success" ) {
                            return array(
                                'status'     =>'error',
                                'message'    =>'No fue posible generar el token de autentificación PD.'
                            );
                        }
                        $TOKEN = $RESULT_TOKEN['data'];
                        $dataSend = array(
                            'JWT'             => $TOKEN,
                            'API_KEY'         => $this->PagoDigitalVars['API_KEY'],
                            'API_SECRET'      => $this->PagoDigitalVars['API_SECRET'],

                            'ID_PROVEEDOR'  => $consulta3[0]["PROVEEDOR_ID"],
                            'VALOR_TRANSFERENCIA' => $consulta[0]["valor_transferencia"],
                            'CONCEPTO' => $consulta[0]["concepto"],
                            'ID_REFERENCIA_TX' => 0//$consulta[0]["ID_REFERENCIA_TX"]
                        );
                        parent::addLogDP("Pago de dispersion: ".$URL." ===> ".json_encode($dataSend));
                        $response = parent::remoteRequestPagoDigital( $URL, $dataSend );
                        parent::addLogDP("Respuesta===> ".json_encode($response));
                        //print_r($response);
                        if ($response["STATUS"] == 200) {
                            parent::queryRegistro("update buyinbig.productos_transaccion set esta_liquidado = 2  where id = $data[id_referencia_tx]");
                            parent::queryRegistro("update buyinbig.solicitudes_dispercion set fecha_actualizacion = current_timestamp where id = ".$consulta[0]["id"]);
                        } else {
                            return array("status"=>"error", "message"=>"No se obtuvo respuesta positiva de PD y no se realizo la dispersion, error: ".json_encode($response));
                        }
                        


                        return array("status"=>"success", "message"=>"Se ha concretado el pago de la dispersion seleccionada");
                    } else {
                        return array("status"=>"error", "message"=>"La solicitud de pago fue realizada anteriormente");
                    }
                } else {
                    return array("status"=>"error", "message"=>"No se encuentra la solicitud relacionada");    
                }

            } else {
                return array("status"=>"error", "message"=>"el codigo otorgado no coincide con la solicitud entregada");        
            }
        } else {
            return array("status"=>"error", "message"=>"los datos solicitados no coinciden con la solicitud realizada");    
        }
        
        //
    }

}

?>