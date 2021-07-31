<?php

require 'conexion.php';
require_once 'HTTP/Request2.php';

class Siigo extends Conexion
{
    private $USER_ACCESS_SIIGO  = "gerencia@nasbi.com";
    private $KEY_ACCESS_SIIGO   = "NmRmYTEzY2ItNmVlYy00MzBhLWE2OTAtZmE4YmY2NDMyM2JhOiEyNl9nOT1jJVQ=";

    private $BASE_URL_AUTH      = "https://api.siigo.com/auth";
    private $TOKEN_ACCESO       = "";

    private $BASE_URL_CLIENTES  = "https://api.siigo.com/v1/customers";
    private $BASE_URL_FACTURAS  = "https://api.siigo.com/v1/invoices";
    private $BASE_URL_PRODUCTOS = "";

    private $TABLA_CLIENTES     = 'buyinbig.siigo_clientes';
    private $TABLA_PRODUCTOS    = 'buyinbig.siigo_productos';
    private $TABLA_FACTURAS     = 'buyinbig.siigo_facturas';

    public $TIPO_FACTURA_CODE = 26852;  // ID FACTURA
    public $FORMA_DE_PAGO = 7964; //
    public $SELLER = 954;  // ID USUARIO API

    public $TAXES = array(
        19 => array(
            array(
                "id" => 8494, // Codigo impuesto del 19 %
            )
        )
    );

    public $CUSTOMER_CLIENTE = "Customer";

    public function __construct()
    {
        // Ajuste hora actual colombia
        date_default_timezone_set('America/Bogota');
        $this->obtener_token_siigo();
    }


    // INICIO: CRUD SIIGO TERCEROS
    public function crearTerceroWS( String $OPTION, String $URL, Array $body )
    {
        $resultToken = $this->getToken();
        if( $resultToken == "" ){
            return array(
                "status"         => "failToken",
                "mensaje-error"  => $resultToken
            );   
        }

        $request = new HTTP_Request2();
        $request->setUrl($URL);

        if ( $OPTION == "ADD" ) {
            $request->setMethod(HTTP_Request2::METHOD_POST);

        } else if ( $OPTION == "SET" ) {
            $request->setMethod(HTTP_Request2::METHOD_PUT);

        }

        $request->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();
            if ( $OPTION == "ADD" ) {
                if ( $response->getStatus() == 201 ) {
                    $body = $response->getBody();
                    $json = json_decode($body, true);
                    return array(
                        "status"         => "success",
                        "data"           => $json
                    );
                } else {
                    $error_ws = "errorCrearTercero";
                    $errores = (Array) json_decode($response->getBody(), true);
                    
                    parent::addLogJB("----+> ADD | WS RESPONSE | URL: " . $URL);
                    parent::addLogJB("----+> ADD | WS RESPONSE | getBody: " . $response->getBody());
                    parent::addLogJB("----+> ADD | WS RESPONSE | TOKEN: " . $this->TOKEN);

                    if( isset($errores) ){
                        if( isset($errores['Errors']) ){
                            if( isset($errores['Errors'][0]) ){
                                if( isset($errores['Errors'][0]['Params']) ){
                                    if( isset($errores['Errors'][0]['Params'][0]) ){
                                        $error_ws = "error-" . $errores['Errors'][0]['Params'][0];
                                    }
                                }
                            }
                        }
                    }
                    return array(
                        "status"         => $error_ws,
                        "status-request" => $response->getStatus(),
                        "mensaje-error"  => json_decode($response->getBody()),
                        "dataSend"       => $body
                    );
                }

            } else if ( $OPTION == "SET" ) {
                if ( $response->getStatus() == 200 ) {
                    $body = $response->getBody();
                    $json = json_decode($body, true);
                    return array(
                        "status"         => "success",
                        "data"           => $json
                    );
                } else {
                    $error_ws = "errorCrearTercero";
                    $errores = (Array) json_decode($response->getBody(), true);
                    
                    parent::addLogJB("----+> PUT | WS RESPONSE | URL: " . $URL);
                    parent::addLogJB("----+> PUT | WS RESPONSE | getBody: " . $response->getBody());
                    parent::addLogJB("----+> PUT | WS RESPONSE | TOKEN: " . $this->TOKEN);

                    if( isset($errores) ){
                        if( isset($errores['Errors']) ){
                            if( isset($errores['Errors'][0]) ){
                                if( isset($errores['Errors'][0]['Params']) ){
                                    if( isset($errores['Errors'][0]['Params'][0]) ){
                                        $error_ws = "error-" . $errores['Errors'][0]['Params'][0];
                                    }
                                }
                            }
                        }
                    }
                    return array(
                        "status"         => $error_ws,
                        "status-request" => $response->getStatus(),
                        "mensaje-error"  => json_decode($response->getBody()),
                        "dataSend"       => $body
                    );
                }

            }
        } catch (HTTP_Request2_Exception $e) {
            return array(
                "status"         => "errorCrearTerceroExepcion",
                "mensaje-error"  => $e
            );
        }
    }
    function terceroRegistrarModificar( Array $data )
    {
        if(!isset( $data['uid'] ) || !isset( $data['empresa'] ) || !isset( $data['type'] ) || !isset( $data['person_type'] ) || !isset( $data['id_type'] ) || !isset( $data['identification'] ) || !isset( $data['first_name'] ) || !isset( $data['last_name'] ) || !isset( $data['email'] ) || !isset( $data['commercial_name'] ) || !isset( $data['active'] ) || !isset( $data['vat_responsible'] ) || !isset( $data['fiscal_responsibilities'] ) || !isset( $data['address'] ) || !isset( $data['country_code'] ) || !isset( $data['state_code'] ) || !isset( $data['city_code'] ) || !isset( $data['postal_code'] ) || !isset( $data['indicative'] ) || !isset( $data['number'] ) || !isset( $data['number_contact'] ) ) {

            return array(
                'status'    => 'fail',
                'message'   => 'El request tiene campos vacios.',
                'data'      => $data
            );
        }
        parent::addLogJB("----+> (1). [ data ]: " . json_encode($data));
        parent::addLogJB("----+> (1). [ data ]: " . json_encode($data));
        parent::addLogJB("----+> (1). [ data ]: " . json_encode($data));

        $data['uid']             = intval( $data['uid'] );
        $data['empresa']         = intval( $data['empresa'] );

        $data['type']            = $this->CUSTOMER_CLIENTE;
        $data['person_type']     = ($data['empresa'] == 0? "Person" : "Company");

        $insert = "";
        $update = "";



        $select = "SELECT * FROM buyinbig.siigo_datos_facturacion WHERE uid = $data[uid] AND empresa = $data[empresa];";

        parent::conectar();
        $siigo_datos_facturacion = parent::consultaTodo( $select );
        parent::cerrar();

        $resul = "";

        $URL_SIIGO_CUSTOMER = "";
        $result_siigo_request = null;

        if( COUNT( $siigo_datos_facturacion ) == 0 ){

            $URL_SIIGO_CUSTOMER = "https://api.siigo.com/v1/customers";
            $schema_siigo_customer = $this->mapDatosParaFacturacionRequest( $data );
            
            parent::addLogJB("----+> [ mapDatosParaFacturacionRequest / schema_siigo_customer ]: " . json_encode($schema_siigo_customer));

            $result_siigo_request = $this->crearTerceroWS( "ADD", $URL_SIIGO_CUSTOMER, $schema_siigo_customer );
            parent::addLogJB("----+> [ crearTerceroWS / result_siigo_request ]: " . json_encode($result_siigo_request));

            if( $result_siigo_request['status'] != 'success' ){
                return $result_siigo_request;
            }

            $json_respuesta = json_encode($result_siigo_request);

            $id_siigo = $result_siigo_request['data']['id'];

            $insert =
                "INSERT INTO siigo_datos_facturacion (
                    uid,
                    empresa,
                    type,
                    person_type,
                    id_type,
                    identification,
                    first_name,
                    last_name,
                    email,
                    commercial_name,
                    active,
                    vat_responsible,
                    fiscal_responsibilities,
                    address,
                    country_code,
                    state_code,
                    city_code,
                    postal_code,
                    indicative,
                    number,
                    extension,
                    number_contact,
                    extension_contact,
                    json_respuesta,
                    id_siigo
                ) VALUES (
                    $data[uid],
                    $data[empresa],
                    '$data[type]',
                    '$data[person_type]',
                    '$data[id_type]',
                    '$data[identification]',
                    '$data[first_name]',
                    '$data[last_name]',
                    '$data[email]',
                    '$data[commercial_name]',
                    $data[active],
                    $data[vat_responsible],
                    '$data[fiscal_responsibilities]',
                    '$data[address]',
                    '$data[country_code]',
                    '$data[state_code]',
                    '$data[city_code]',
                    '$data[postal_code]',
                    '$data[indicative]',
                    '$data[number]',
                    '$data[extension]',
                    '$data[number_contact]',
                    '$data[extension_contact]',
                    '$json_respuesta',
                    '$id_siigo'
                );";
            parent::addLogJB("----+> [ insert ]: " . $insert);

            parent::conectar();
            $resul = parent::queryRegistro( $insert );
            parent::cerrar();
            parent::addLogJB("----+> [ insert / resul ]: " . $resul);
        }else{
            $id_siigo_datos_facturacion = $siigo_datos_facturacion[ 0 ]['id'];

            $update = 
            "UPDATE siigo_datos_facturacion
            SET
                uid                     = $data[uid],
                empresa                 = $data[empresa],
                type                    = '$data[type]',
                person_type             = '$data[person_type]',
                id_type                 = '$data[id_type]',
                identification          = '$data[identification]',
                first_name              = '$data[first_name]',
                last_name               = '$data[last_name]',
                email                   = '$data[email]',
                commercial_name         = '$data[commercial_name]',
                active                  = $data[active],
                vat_responsible         = $data[vat_responsible],
                fiscal_responsibilities = '$data[fiscal_responsibilities]',
                address                 = '$data[address]',
                country_code            = '$data[country_code]',
                state_code              = '$data[state_code]',
                city_code               = '$data[city_code]',
                postal_code             = '$data[postal_code]',
                indicative              = '$data[indicative]',
                number                  = '$data[number]',
                extension               = '$data[extension]',
                number_contact          = '$data[number_contact]',
                extension_contact       = '$data[extension_contact]'
            WHERE id = '$id_siigo_datos_facturacion';";

            parent::addLogJB("----+> (1). [ update ]: " . $update);


            // INICIO: API SIIGO
            $URL_SIIGO_CUSTOMER = "https://api.siigo.com/v1/customers/$data[id_siigo]";
            $schema_siigo_customer = $this->mapDatosParaFacturacionRequest( $data );

            parent::addLogJB("----+> (2.1). [ mapDatosParaFacturacionRequest / URL_SIIGO_CUSTOMER ]: " . $URL_SIIGO_CUSTOMER);
            parent::addLogJB("----+> (2.2). [ mapDatosParaFacturacionRequest / schema_siigo_customer ]: " . json_encode($schema_siigo_customer));

            $result_siigo_request = $this->crearTerceroWS( "SET", $URL_SIIGO_CUSTOMER, $schema_siigo_customer );
            parent::addLogJB("----+> (3). [ crearTerceroWS / result_siigo_request ]: " . json_encode($result_siigo_request));

            if( $result_siigo_request['status'] != 'success' ){
                return $result_siigo_request;
            }

            $json_respuesta = json_encode($result_siigo_request);

            // FIN: API SIIGO


            parent::conectar();
            $resul = parent::query( $update );
            parent::cerrar();
            parent::addLogJB("----+> (4). [ update / resul ]: " . $resul);
        }
        

        if( $resul > 0 ){
            return array(
                'status'    => 'success',
                'message'   => ( COUNT( $siigo_datos_facturacion ) == 0? 'Informacion insertada con exito.' : 'Informacion actualizada con exito' ),
                'data'      => $resul,
                'dataExtra' => $data,
                'result_siigo_request' => $result_siigo_request
            );
        }else{
            return array(
                'status'    => ( COUNT( $siigo_datos_facturacion ) == 0? 'errorInsert' : 'errorUpdate' ),
                'message'   => ( COUNT( $siigo_datos_facturacion ) == 0? 'No fue posible insertar los datos para siigo' : 'No fue posible actualizar los datos para siigo' ),
                'data'      => $resul,
                'dataExtra' => $data,
                'insert'    => $insert,
                'update'    => $update,
                'result_siigo_request' => $result_siigo_request
            );
        }
    }
    function terceroBuscar( Array $data )
    {
        if( !isset($data['uid']) || !isset($data['empresa']) ) {
            return array(
                'status'    => 'fail',
                'message'   => 'El request tiene campos vacios.',
                'data'      => $data
            );
        }

        $select =
            "SELECT * FROM buyinbig.siigo_datos_facturacion WHERE uid = $data[uid] AND empresa = $data[empresa];";

        parent::conectar();
        $siigo_datos_facturacion = parent::consultaTodo( $select );
        parent::cerrar();

        if( COUNT( $siigo_datos_facturacion ) > 0 ){
            $siigo_datos_facturacion = $siigo_datos_facturacion[0];
            $data['empresa']         = intval($data['empresa']);

            $siigo_datos_facturacion['json_respuesta'] = json_decode( $siigo_datos_facturacion['json_respuesta'] );

            $datos_facturacion_format = $this->mapDatosParaFacturacion($siigo_datos_facturacion);

            return array(
                'status'     => 'success',
                'message'    => 'Datos para la facturación',
                'data'       => $siigo_datos_facturacion,
                'dataFormat' => $datos_facturacion_format,
            );
        }

        return array(
            'status'  => 'errorFormularioVacio',
            'message' => 'No tiene datos registrados',
            'data'    => $data
        );
    }
    function mapDatosParaFacturacion(Array $datos)
    {

        $name = Array();
        if( intval("" . $datos['empresa']) == 0 ){
            $name = Array(
                addslashes($datos['first_name']),
                addslashes($datos['last_name'])
            );
        }else{
            $name = Array(
                addslashes($datos['commercial_name'])
            );
        }
        $siigo_datos_facturacion = Array(
            'type'                    => $this->CUSTOMER_CLIENTE,
            'person_type'             => $datos['person_type'],

            'id_type'                 => $datos['id_type'],
            'identification'          => $datos['identification'],
            'name'                    => $name,

            'commercial_name'         => addslashes($datos['commercial_name']),
            'active'                  => $datos['active'],
            'vat_responsible'         => $datos['vat_responsible'],
            'fiscal_responsibilities' => Array(
                Array(
                    "code" => $datos['fiscal_responsibilities']
                )
            ),
            'address' => Array(
                'address'             => $datos['address'],
                'city' => Array(
                    'country_code'    => $datos['country_code'],
                    'state_code'      => $datos['state_code'],
                    'city_code'       => $datos['city_code']
                ),
                'postal_code'         => $datos['postal_code']
            ),
            'phones' => Array(
                Array(
                    'indicative'      => $datos['indicative'],
                    'number'          => $datos['number'],
                    'extension'       => $datos['extension']
                )
            ),
            'contacts' => Array(
                Array(
                    'first_name'      => addslashes($datos['first_name']),
                    'last_name'       => addslashes($datos['last_name']),
                    'email'           => $datos['email'],
                    'phone' => Array(
                        'indicative'  => $datos['indicative'],
                        'number'      => $datos['number_contact'],
                        'extension'   => $datos['extension_contact'],
                    )
                )
            ),
            'related_users'           => Array(
                'seller_id'           => $this->SELLER,
                'collector_id'        => $this->SELLER
            )
        );
        return $siigo_datos_facturacion;
    }
    function mapDatosParaFacturacionRequest(Array $datos)
    {
        $name = Array();
        if( intval("" . $datos['empresa']) == 0 ){
            $name = Array(
                addslashes($datos['first_name']),
                addslashes($datos['last_name'])
            );
        }else{
            $name = Array(
                addslashes($datos['commercial_name'])
            );
        }
        $siigo_datos_facturacion = Array(
            'type'                    => $this->CUSTOMER_CLIENTE,
            'person_type'             => $datos['person_type'],

            'id_type'                 => $datos['id_type'],
            'identification'          => $datos['identification'],
            'name'                    => $name,

            'commercial_name'         => addslashes($datos['commercial_name']),
            'active'                  => $datos['active'],
            'vat_responsible'         => $datos['vat_responsible'],
            'fiscal_responsibilities' => Array(
                Array(
                    "code" => $datos['fiscal_responsibilities']
                )
            ),
            'address' => Array(
                'address'             => $datos['address'],
                'city' => Array(
                    'country_code'    => $datos['country_code'],
                    'state_code'      => $datos['state_code'],
                    'city_code'       => $datos['city_code']
                ),
                'postal_code'         => $datos['postal_code']
            ),
            'phones' => Array(
                Array(
                    'indicative'      => $datos['indicative'],
                    'number'          => $datos['number'],
                    'extension'       => $datos['extension']
                )
            ),
            'contacts' => Array(
                Array(
                    'first_name'      => addslashes($datos['first_name']),
                    'last_name'       => addslashes($datos['last_name']),
                    'email'           => $datos['email'],
                    'phone' => Array(
                        'indicative'  => $datos['indicative'],
                        'number'      => $datos['number_contact'],
                        'extension'   => $datos['extension_contact'],
                    )
                )
            ),
            'related_users'           => Array(
                'seller_id'           => $this->SELLER,
                'collector_id'        => $this->SELLER
            )
        );
        return $siigo_datos_facturacion;
    }
    // FIN: CRUD SIIGO TERCEROS


    // INICIO: CREAR FACTURAS
    public function crearFacturaNewWS(Array $body)
    {
        $resultToken = $this->getToken();
        if( $resultToken == "" ){
            return array(
                "status"         => "failToken",
                "mensaje-error"  => $resultToken
            );   
        }

        $request = new HTTP_Request2();
        $request->setUrl("https://api.siigo.com/v1/invoices");
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();

            if ($response->getStatus() == 201) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                return array(
                    "status"         => "success",
                    "data"           => $json
                );
            } else {
                return array(
                    "status"         => "errorFacturaSend",
                    "status-request" => $response->getStatus(),
                    "mensaje-error"  => json_decode($response->getBody()),
                    "dataSend"       => $body
                );
            }
        } catch (HTTP_Request2_Exception $e) {
            return array(
                "status"         => "errorFacturaSendException",
                "mensaje-error"  => $e
            );
        }
    }

    public function crear_factura_new(array $data)
    {
        if ( !isset($data) && !isset($data["uid"])  && !isset($data["empresa"]) && !isset($data["observations"]) && !isset($data["items"]) && COUNT($data["items"]) == 0 && !isset($data["payments"]) && COUNT($data["payments"]) == 0 ) {
            return array(
                "status"  => "fail",
                "message" => "Datos incompletos.",
                "data"    => $data
            );
        }

        $selectxsiigoxdatosxfacturacion = "SELECT identification AS 'numero_documento' FROM buyinbig.siigo_datos_facturacion WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";
        parent::conectar();
        $siigo_datos_facturacion = parent::consultaTodo( $selectxsiigoxdatosxfacturacion );
        parent::cerrar();


        if( COUNT( $siigo_datos_facturacion ) == 0 ){
            // $selectxsiigoxdatosxfacturacion = 
            //     "SELECT identificacion AS 'numero_documento' FROM buyinbig.siigo_clientes WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';";

            // parent::conectar();
            // $siigo_datos_facturacion = parent::consultaTodo( $selectxsiigoxdatosxfacturacion );
            // parent::cerrar();

            if( COUNT( $siigo_datos_facturacion ) == 0 ){
                return array(
                    "status"  => "noExisteTerceroEnSiigo",
                    "message" => "No se encuentrán registros para este usuario en la plataforma siigo.",
                    "data"    => $data
                );
            }
        }
        $siigo_datos_facturacion = $siigo_datos_facturacion[ 0 ];

        $items = Array();
        foreach ($data["items"] as $key => $item) {
            $items[] =  Array(
                "code"        => $item['code'],
                "description" => $item['description'],
                "quantity"    => $item['quantity'],
                "price"       => $item['price'],
                "discount"    => $item['discount'],
                "taxes"       => $this->TAXES[ $item['taxes'] ]
            );
        }

        $schema_send = Array(
            "document" => Array(
                "id"       => $this->TIPO_FACTURA_CODE
            ),
            "date"         => date("Y-m-d"),
            "customer"     => Array(
                "identification" => $siigo_datos_facturacion['numero_documento'],
                "branch_office" => 0
            ),
            "seller"       => $this->SELLER,
            "observations" => addslashes($data["observations"]),
            "items"        => $items,
            "payments"     => Array(
                Array(
                    "value"    => $data["payments"][0]['value'],
                    "id"       => $this->FORMA_DE_PAGO,
                    "due_date" => date("Y-m-d")
                )
            )
        );

        parent::addLogJB("----=> [ 1). JSON FACTURA SIIGO | schema_send ]: " . json_encode($schema_send));
        parent::addLogJB("----=> [ 2). JSON FACTURA SIIGO | data ]: " . json_encode($data));

        $resultFacturacion = $this->crearFacturaNewWS( $schema_send );
        
        parent::addLogJB("----=> [ 2). JSON FACTURA SIIGO | resultFacturacion ]: " . json_encode($resultFacturacion));

        if( $resultFacturacion['status'] == "success" ){

            $id_siigo_factura = $resultFacturacion['data']['id'];
            $codigo_factura   = $resultFacturacion['data']['document']['id'];
            $numero_factura   = $resultFacturacion['data']['number'];
            $fecha            = intval(microtime(true)*1000);
            $json             = json_encode($resultFacturacion, true);

            $tipo = 0;
            if( isset( $data["uid"] )){
                $tipo           = $data["uid"];
            }
            $transaccion_id = 0;
            if( isset( $data["transaccion_id"] )){
                $transaccion_id = $data["transaccion_id"];
            }

            $insertFactura =
                "INSERT INTO siigo_facturas (
                    uid,
                    empresa,
                    id_siigo_factura,
                    codigo_factura,
                    numero_factura,
                    identificacion_cliente,
                    fecha,
                    tipo,
                    transaccion_id,
                    json_retorno
                ) VALUES (
                    $data[uid],
                    $data[empresa],
                    $id_siigo_factura,
                    $codigo_factura,
                    $numero_factura,
                    $siigo_datos_facturacion[numero_documento],
                    $fecha,
                    $tipo,
                    $transaccion_id,
                    $json
                );";
            parent::conectar();
            $rowAffect = parent::queryRegistro( $insertFactura );
            parent::cerrar();

            $resultFacturacion['data']['siigo_facturas_id'] = $rowAffect;

        }

        return $resultFacturacion;
    }
    // FIN: CREAR FACTURAS




    function getUsuarioDatosPasarela(array $data)
    {
        $selectxusuario = "";
        if ($data['empresa'] == 0) {

            $selectxusuario = "SELECT * FROM peer2win.usuarios WHERE id = '$data[uid]'";

            parent::conectar();
            $respuesta_buscar_cliente = parent::consultaTodo($selectxusuario);
            parent::cerrar();

            $tipo_identificacion_usuario = "";

            if (count($respuesta_buscar_cliente) > 0) {
                $tipo_identificacion_usuario = $respuesta_buscar_cliente[0]["tipo_identificacion"];
            }

            if ($tipo_identificacion_usuario == "" && count($respuesta_buscar_cliente) > 0) {
                $tipo_identificacion_usuario = "CC"; // Cedula
            }

            $selectxusuarioxformxpd =
                "SELECT
                dpn.*,
                
                (
                    SELECT di.siigo_IdTypeCode
                    FROM buyinbig.documento_identificacion di
                    WHERE di.codigo = '$tipo_identificacion_usuario'
                ) AS siigo_IdTypeCode,
                
                crf.codigo_siigo

            FROM buyinbig.datos_persona_natural dpn
            INNER JOIN buyinbig.Cod_Responsabilidad_Fiscal crf ON( dpn.id_cod_responsabilidad_fiscal = crf.id )
            WHERE dpn.user_uid = '$data[uid]' AND dpn.user_empresa = '$data[empresa]'";
        } else {
            $selectxusuario = "SELECT * FROM buyinbig.empresas WHERE id = '$data[uid]'";
            $selectxusuarioxformxpd_OLD =
                "SELECT 
                dpj.*,

                di.codigo,
                di.codigo AS tipo_identificacion,

                di.siigo_IdTypeCode,

                crf.codigo_siigo

            FROM buyinbig.datos_persona_juridica dpj
            INNER JOIN buyinbig.documento_identificacion di ON ( di.id = dpj.id_documento_identificacion)
            INNER JOIN buyinbig.Cod_Responsabilidad_Fiscal crf ON( dpj.id_cod_responsabilidad_fiscal = crf.id )
            WHERE dpj.user_uid = '$data[uid]' AND dpj.user_empresa = '$data[empresa]'";
            $selectxusuarioxformxpd =
                "SELECT 
                dpj.*,

                di.codigo,
                di.codigo AS tipo_identificacion,

                di.siigo_IdTypeCode,

                crf.codigo_siigo

            FROM buyinbig.datos_persona_juridica dpj
            LEFT JOIN buyinbig.documento_identificacion di ON ( di.id = 5)
            INNER JOIN buyinbig.Cod_Responsabilidad_Fiscal crf ON( dpj.id_cod_responsabilidad_fiscal = crf.id )
            WHERE dpj.user_uid = '$data[uid]' AND dpj.user_empresa = '$data[empresa]'";
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
            ct.TIPO_GEOGRAFIA AS 'TCC_TIPO_GEOGRAFIA',

            ct.CITYCODE,
            ct.STATECODE,
            ct.COUNTRYCODE
            
        FROM direcciones d
        INNER JOIN  ciudades_tcc ct ON (d.dane = ct.dane)
        WHERE d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' AND d.activa = 1 AND d.estado = 1
        ORDER BY fecha_creacion DESC";

        // parent::addLogSIIGO("----+> [ selectxusuario ]: " . $selectxusuario);
        // parent::addLogSIIGO("----+> [ selectxusuarioxformxpd ]: " . $selectxusuarioxformxpd);
        // parent::addLogSIIGO("----+> [ selectxusuarioxdireccion ]: " . $selectxusuarioxdireccion);

        parent::conectar();
        $selectusuario          = parent::consultaTodo($selectxusuario);
        $selectusuarioformpd    = parent::consultaTodo($selectxusuarioxformxpd);
        $selectusuariodireccion = parent::consultaTodo($selectxusuarioxdireccion);
        parent::cerrar();

        $status = "";
        $message = "";
        if (count($selectusuario) <= 0) {
            $selectusuario = null;
            $status  = 'usuarioNoExiste';
            $message = 'Las credenciales suministradas no pertenecen a ningun usuario.';
        } else {
            $selectusuario = $this->mapUsuario($selectusuario, $data['empresa'])[0];
        }
        if (count($selectusuarioformpd) <= 0) {
            $selectusuarioformpd = null;
            $status  = 'errorFormularioPD';
            $message = 'El usuario requiere completar los datos del formulario pago digital.';
        } else {
            $selectusuarioformpd = $selectusuarioformpd[0];
            if (intval("" . $data['empresa']) == 1) {
                $selectusuarioformpd['id_documento_identificacion_original'] = $selectusuarioformpd['id_documento_identificacion'];
                $selectusuarioformpd['no_identificacion_original'] = $selectusuarioformpd['no_identificacion'];

                $selectusuarioformpd['id_documento_identificacion'] = $selectusuario['tipo_identificacion'];
                $selectusuarioformpd['no_identificacion'] = $selectusuario['identificacion'];
            }
        }
        if (count($selectusuariodireccion) <= 0) {
            $selectusuariodireccion = null;
            $status  = 'direccionNoExiste';
            $message = 'El usuario requiere una direccion';
        } else {
            $selectusuariodireccion = $selectusuariodireccion[0];
        }
        if (strlen($status) == 0) {
            $status = "success";
            $message = "Datos completos del usuario";
        }
        $responseData = array(
            'status'            => $status,
            'message'           => $message,
            'datos_usuario'     => $selectusuario,
            'form_pago_digital' => $selectusuarioformpd,
            'datos_residencia'  => $selectusuariodireccion
        );
        // parent::addLogSIIGO("----+> [ responseData ]: " . json_encode($responseData));
        return $responseData;
    }

    function mapUsuario(array $usuarios, Int $empresa = 0)
    {
        $datanombre = null;
        $dataempresa = null;
        $datacorreo = null;
        $datatelefono = null;
        $dataidentificacion = null;
        $datatipoidentificacion = null;
        $datapais = null;

        foreach ($usuarios as $x => $user) {
            if ($empresa == 0) {
                $datanombre             = $user['nombreCompleto'];
                $dataempresa            = "Nasbi";
                $datacorreo             = $user['email'];
                $datatelefono           = $user['telefono'];
                $datatipoidentificacion = $user['tipo_identificacion'];
                $dataidentificacion     = $user['numero_identificacion'];
                $datapais               = $user['pais_identificacion'];
            } else if ($empresa == 1) {
                $datanombre             = $user['razon_social'];
                $dataempresa            = $user['razon_social'];
                $datacorreo             = $user['correo'];
                $datatelefono           = $user['telefono'];
                $datatipoidentificacion = 5;
                $dataidentificacion     = $user['nit'];
                $datapais               = "";
            }
            unset($user);
            $user['nombre'] = $datanombre;
            $user['empresa'] = $dataempresa;
            $user['correo'] = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['tipo_identificacion'] = $datatipoidentificacion;
            $user['identificacion'] = $dataidentificacion;
            $user['pais'] = $datapais;
            $usuarios[$x] = $user;
        }
        return $usuarios;
    }

    function eliminar_acentos($cadena)
    {

        //Reemplazamos la A y a
        $cadena = str_replace(
            array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
            array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
            $cadena
        );

        //Reemplazamos la E y e
        $cadena = str_replace(
            array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
            array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
            $cadena
        );

        //Reemplazamos la I y i
        $cadena = str_replace(
            array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
            array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
            $cadena
        );

        //Reemplazamos la O y o
        $cadena = str_replace(
            array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
            array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
            $cadena
        );

        //Reemplazamos la U y u
        $cadena = str_replace(
            array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
            array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
            $cadena
        );

        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
            array('Ñ', 'ñ', 'Ç', 'ç'),
            array('N', 'n', 'C', 'c'),
            $cadena
        );

        return $cadena;
    }

    function obtener_data_cliente(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"])) {

            $NOMBRE_COMPRADOR     = "";
            $CEDULA_COMPRADOR     = "";
            $TELEFONO_COMPRADOR   = "";
            $CORREO_COMPRADOR     = "";
            $DIRECCION_COMPRADOR  = "";

            $TIPO_DOCUMENTO_SIIGO = "";
            $TIPO_DOCUMENTO       = "";

            $CODIGO_POSTAL        = "";

            $resultDatosPersonales = $this->getUsuarioDatosPasarela($data);

            if ($resultDatosPersonales['status'] == 'fail' || $resultDatosPersonales['status'] == 'usuarioNoExiste') {
                return array(
                    'status' => 'errorCredenciales',
                    'message' => 'Las credenciales del usuario no pertenecen a la plataforma'
                );
            } else {

                if ($resultDatosPersonales['datos_usuario'] != null) {

                    $NOMBRE_COMPRADOR = addslashes($resultDatosPersonales['datos_usuario']['nombre']);
                    $CORREO_COMPRADOR = $resultDatosPersonales['datos_usuario']['correo'];

                    if ($data['empresa'] == 0 && $resultDatosPersonales['datos_usuario']['identificacion'] != null) {
                        $CEDULA_COMPRADOR = $resultDatosPersonales['datos_usuario']['identificacion'];
                    }
                    if ($data['empresa'] == 0 && $resultDatosPersonales['datos_usuario']['telefono'] != null) {
                        $TELEFONO_COMPRADOR = $resultDatosPersonales['datos_usuario']['telefono'];
                    }
                    if ($data['empresa'] == 0) {
                        $TIPO_DOCUMENTO_SIIGO = $resultDatosPersonales['form_pago_digital']['siigo_IdTypeCode'];
                    }
                    if ($data['empresa'] == 0) {
                        $TIPO_DOCUMENTO = $resultDatosPersonales['datos_usuario']['tipo_identificacion'];
                    }
                }
                if ($resultDatosPersonales['form_pago_digital'] != null) {

                    if ($data['empresa'] == 1) {

                        $TIPO_DOCUMENTO_SIIGO     = $resultDatosPersonales['form_pago_digital']['siigo_IdTypeCode'];
                        $CEDULA_COMPRADOR   = $resultDatosPersonales['form_pago_digital']['no_identificacion'];
                        $TELEFONO_COMPRADOR = $resultDatosPersonales['datos_usuario']['telefono'];

                        $NOMBRE_COMPRADOR = addslashes($resultDatosPersonales['datos_usuario']['nombre']);
                        $TIPO_DOCUMENTO = $resultDatosPersonales['form_pago_digital']['tipo_identificacion'];
                        // $NOMBRE_COMPRADOR   = addslashes($resultDatosPersonales['form_pago_digital']['nombres'] . ' ' . $resultDatosPersonales['form_pago_digital']['apellidos']);
                    }
                }
                if ($resultDatosPersonales['datos_residencia'] != null) {
                    $DIRECCION_COMPRADOR = addslashes($resultDatosPersonales['datos_residencia']['direccion']);
                    $CODIGO_POSTAL       = $resultDatosPersonales['datos_residencia']['codigo_postal'];
                }
            }

            $data_usuario_siigo = array(
                "uid"                     => $data["uid"],
                "empresa"                 => $data["empresa"],
                "IsCustomer"              => true,
                "IsSocialReason"          => intval($data["empresa"]) == 1 ? true : false,
                "FullName"                => strtoupper($this->eliminar_acentos($NOMBRE_COMPRADOR)),
                "FirstName"               => strtoupper($this->eliminar_acentos($NOMBRE_COMPRADOR)),
                "LastName"                => ".",
                "IdTypeCode"              => $TIPO_DOCUMENTO_SIIGO,
                "Identification"          => $CEDULA_COMPRADOR,
                "IsVATCompanyType"        => $resultDatosPersonales['form_pago_digital']["codigo_siigo"] == "O-23" ? true : false,
                "Address"                 => $DIRECCION_COMPRADOR,
                "Phone_Number"            => $TELEFONO_COMPRADOR,
                "City_CountryCode"        => $resultDatosPersonales['datos_residencia']["COUNTRYCODE"],
                "City_StateCode"          => $resultDatosPersonales['datos_residencia']["STATECODE"],
                "City_CityCode"           => $resultDatosPersonales['datos_residencia']["CITYCODE"],
                "Codigo_postal"           => $CODIGO_POSTAL,
                "EMail"                   => $CORREO_COMPRADOR,
                "IsActive"                => true,
                "FiscalResponsibilities"  => [["code" => $resultDatosPersonales['form_pago_digital']["codigo_siigo"]]],
                "tipo_documento"          => $TIPO_DOCUMENTO
            );

            return $data_usuario_siigo;
        } else {
            return array();
        }
    }

    public function seleccionarDeBd(string $tabla, bool $con_condicon, string $condicion)
    {
        parent::conectar();
        $sql = "SELECT * FROM $tabla";
        if ($con_condicon == true) {
            $sql = $sql . ' WHERE ' . $condicion;
        }
        $resultado = parent::consultaTodo($sql);
        parent::cerrar();
        return $resultado;
    }

    public function retornar_columnas_para_insertar_bd(string $tabla)
    {
        if ($tabla == $this->TABLA_CLIENTES) {
            return "
                        uid, 
                        empresa, 
                        id_siigo_cliente, 
                        identificacion, 
                        nombre_completo, 
                        tipo_de_tercero, 
                        direccion, 
                        id_siigo_contacto, 
                        numero_celular, 
                        correo, 
                        json_retorno
                    ";
        } else if ($tabla == $this->TABLA_PRODUCTOS) {
            return "
                        uid, 
                        empresa, 
                        uid_producto, 
                        id_siigo_producto, 
                        codigo_producto_siigo, 
                        nombre_producto, 
                        precio, 
                        json_retorno
                    ";
        } else if ($tabla == $this->TABLA_FACTURAS) {
            return "
                        uid, 
                        empresa, 
                        id_siigo_factura, 
                        codigo_factura, 
                        numero_factura, 
                        identificacion_cliente, 
                        fecha, 
                        json_retorno
                    ";
        }
    }

    public function retornar_datos_para_insertar_bd(string $tabla, array $datos)
    {

        if ($tabla == $this->TABLA_CLIENTES) {

            $id_siigo_cliente = addslashes($datos["id_siigo_cliente"]);

            $nombre_completo  = addslashes($this->eliminar_acentos($datos["nombre_completo"]));
            $direccion        = addslashes($this->eliminar_acentos($datos["direccion"]));
            $correo           = $this->eliminar_acentos($datos["correo"]);

            $json_siigo                      = $datos["json_retorno"];
            $json_siigo["id"]                = $id_siigo_cliente;
            $json_siigo["id_type"]["name"]   = addslashes($this->eliminar_acentos($datos["json_retorno"]["id_type"]["name"]));
            $json_siigo["name"][0]           = addslashes($this->eliminar_acentos($datos["json_retorno"]["name"][0]));

            if (isset($datos["json_retorno"]["commercial_name"]) != "") {
                $json_siigo["commercial_name"] = addslashes($this->eliminar_acentos($datos["json_retorno"]["commercial_name"]));
            }

            $array_responsabilidades = array();
            foreach ($json_siigo["fiscal_responsibilities"] as $responsabilidad) {
                $responsabilidad_bd = array();
                $responsabilidad_bd["code"] = addslashes($this->eliminar_acentos($responsabilidad["code"]));
                $responsabilidad_bd["name"] = addslashes($this->eliminar_acentos($responsabilidad["name"]));
                array_push($array_responsabilidades, $responsabilidad_bd);
            }
            $json_siigo["fiscal_responsibilities"]         = $array_responsabilidades;

            $json_siigo["address"]["address"]              = $direccion;
            $json_siigo["address"]["city"]["country_name"] = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["country_name"]));
            $json_siigo["address"]["city"]["state_name"]   = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["state_name"]));
            $json_siigo["address"]["city"]["city_name"]    = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["city_name"]));

            $json_siigo["contacts"][0]["first_name"]       = addslashes($this->eliminar_acentos($datos["json_retorno"]["contacts"][0]["first_name"]));
            $json_siigo["contacts"][0]["email"]            = $correo;

            $json_siigo["metadata"]["created"]             = addslashes($datos["json_retorno"]["metadata"]["created"]);

            if (isset($datos["json_retorno"]["metadata"]["last_updated"])) {
                $json_siigo["metadata"]["last_updated"]    = addslashes($datos["json_retorno"]["metadata"]["last_updated"]);
            }

            $json_retorno              = json_encode($json_siigo);

            return "
                        '$datos[uid]', 
                        '$datos[empresa]', 
                        '$id_siigo_cliente', 
                        '$datos[identificacion]', 
                        '$nombre_completo', 
                        '$datos[tipo_de_tercero]', 
                        '$direccion', 
                        '$datos[id_siigo_contacto]', 
                        '$datos[numero_celular]', 
                        '$correo', 
                        '$json_retorno'
                    ";
        } else if ($tabla == $this->TABLA_PRODUCTOS) {

            $nombre_producto           = addslashes($this->eliminar_acentos($datos["nombre_producto"]));

            $json_siigo                = $datos["json_retorno"];
            $json_siigo["name"]        = $nombre_producto;

            $precios_producto          = [];

            if (isset($datos["json_retorno"]["prices"]) && count($datos["json_retorno"]["prices"][0]["price_list"]) > 0) {
                foreach ($datos["json_retorno"]["prices"][0]["price_list"] as $precio) {
                    $precio_bd = $precio;
                    $precio_bd["name"] = addslashes($this->eliminar_acentos($precio["name"]));
                    array_push($precios_producto, $precio_bd);
                }
            }

            $datos["json_retorno"]["prices"][0]["price_list"] = $precios_producto;
            $json_retorno                                     = json_encode($json_siigo);

            return "
                        '$datos[uid]', 
                        '$datos[empresa]', 
                        '$datos[uid_producto]', 
                        '$datos[id_siigo_producto]', 
                        '$datos[codigo_producto_siigo]', 
                        '$nombre_producto', 
                        '$datos[precio]', 
                        '$json_retorno'
                    ";
        } else if ($tabla == $this->TABLA_FACTURAS) {

            $id_siigo_factura              = addslashes($datos["id_siigo_factura"]);

            $json_siigo                    = $datos["json_retorno"];
            $json_siigo["id"]              = $id_siigo_factura;
            $json_siigo["customer"]["id"]  = addslashes($json_siigo["customer"]["id"]);
            $json_siigo["observations"]    = addslashes($this->eliminar_acentos($json_siigo["observations"]));

            $array_productos = array();

            foreach ($json_siigo["items"] as $producto) {
                $producto_bd = array();

                $producto_bd["id"] = addslashes($this->eliminar_acentos($producto["id"]));
                $producto_bd["code"] = addslashes($this->eliminar_acentos($producto["code"]));
                $producto_bd["description"] = addslashes($this->eliminar_acentos($producto["description"]));

                if (isset($producto["taxes"])) {
                    $array_impuestos = array();
                    foreach ($producto["taxes"] as $impuestos) {
                        $impuesto_bd = array();
                        $impuesto_bd["name"] = addslashes($this->eliminar_acentos($impuestos["name"]));
                        $impuesto_bd["type"] = addslashes($this->eliminar_acentos($impuestos["type"]));
                        array_push($array_impuestos, $impuesto_bd);
                    }
                    $producto_bd["taxes"] = $array_impuestos;
                }

                array_push($array_productos, $producto_bd);
            }

            $json_siigo["items"] = $array_productos;

            $json_siigo["payments"][0]["name"] = addslashes($this->eliminar_acentos($json_siigo["payments"][0]["name"]));

            if (isset($json_siigo["payments"][0]["due_date"])) {
                $json_siigo["payments"][0]["due_date"] = addslashes($this->eliminar_acentos($json_siigo["payments"][0]["due_date"]));
            }

            $json_siigo["metadata"]["created"]     = addslashes($this->eliminar_acentos($json_siigo["metadata"]["created"]));

            $json_retorno                          = json_encode($json_siigo);

            return "
                        '$datos[uid]', 
                        '$datos[empresa]', 
                        '$id_siigo_factura', 
                        '$datos[codigo_factura]', 
                        '$datos[numero_factura]', 
                        '$datos[identificacion_cliente]', 
                        '$datos[fecha]', 
                        '$json_retorno'
                    ";
        }
    }

    public function insertarEnBd(string $tabla, array $datos)
    {
        parent::conectar();
        $columnas = $this->retornar_columnas_para_insertar_bd($tabla);
        $datos_insertar = $this->retornar_datos_para_insertar_bd($tabla, $datos);
        $sql = "INSERT INTO $tabla ($columnas) VALUES ($datos_insertar)";
        $resultado = parent::queryRegistro($sql);
        parent::cerrar();
        return $resultado;
    }

    public function retornar_datos_para_actualizar_bd(string $tabla, array $datos)
    {

        if ($tabla == $this->TABLA_CLIENTES) {

            $id_siigo_cliente = addslashes($datos["id_siigo_cliente"]);

            $nombre_completo  = addslashes($this->eliminar_acentos($datos["nombre_completo"]));
            $direccion        = addslashes($this->eliminar_acentos($datos["direccion"]));
            $correo           = $this->eliminar_acentos($datos["correo"]);

            $json_siigo                      = $datos["json_retorno"];
            $json_siigo["id"]                = $id_siigo_cliente;
            $json_siigo["id_type"]["name"]   = addslashes($this->eliminar_acentos($datos["json_retorno"]["id_type"]["name"]));
            $json_siigo["name"][0]           = addslashes($this->eliminar_acentos($datos["json_retorno"]["name"][0]));

            if (isset($datos["json_retorno"]["commercial_name"]) && $datos["json_retorno"]["commercial_name"] != "") {
                $json_siigo["commercial_name"] = addslashes($this->eliminar_acentos($datos["json_retorno"]["name"][0]));
            }

            $array_responsabilidades = array();
            foreach ($json_siigo["fiscal_responsibilities"] as $responsabilidad) {
                $responsabilidad_bd = array();
                $responsabilidad_bd["code"] = addslashes($this->eliminar_acentos($responsabilidad["code"]));
                $responsabilidad_bd["name"] = addslashes($this->eliminar_acentos($responsabilidad["name"]));
                array_push($array_responsabilidades, $responsabilidad_bd);
            }
            $json_siigo["fiscal_responsibilities"]         = $array_responsabilidades;

            $json_siigo["address"]["address"]              = $direccion;
            $json_siigo["address"]["city"]["country_name"] = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["country_name"]));
            $json_siigo["address"]["city"]["state_name"]   = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["state_name"]));
            $json_siigo["address"]["city"]["city_name"]    = addslashes($this->eliminar_acentos($datos["json_retorno"]["address"]["city"]["city_name"]));

            $json_siigo["contacts"][0]["first_name"]       = addslashes($this->eliminar_acentos($datos["json_retorno"]["contacts"][0]["first_name"]));
            $json_siigo["contacts"][0]["email"]            = $correo;

            $json_siigo["metadata"]["created"]             = addslashes($datos["json_retorno"]["metadata"]["created"]);

            if (isset($datos["json_retorno"]["metadata"]["last_updated"])) {
                $json_siigo["metadata"]["last_updated"]    = addslashes($datos["json_retorno"]["metadata"]["last_updated"]);
            }

            $json_retorno              = json_encode($json_siigo);

            return  "
                        uid                = '$datos[uid]', 
                        empresa            = '$datos[empresa]', 
                        id_siigo_cliente   = '$id_siigo_cliente', 
                        identificacion     = '$datos[identificacion]', 
                        nombre_completo    = '$nombre_completo', 
                        tipo_de_tercero    = '$datos[tipo_de_tercero]', 
                        direccion          = '$direccion', 
                        id_siigo_contacto  = '$datos[id_siigo_contacto]', 
                        numero_celular     = '$datos[numero_celular]', 
                        correo             = '$correo', 
                        json_retorno       = '$json_retorno'
                    ";
        } else if ($tabla == $this->TABLA_PRODUCTOS) {

            $nombre_producto           = addslashes($this->eliminar_acentos($datos["nombre_producto"]));

            $json_siigo                = $datos["json_retorno"];
            $json_siigo["name"]        = $nombre_producto;

            $precios_producto          = [];

            if (isset($datos["json_retorno"]["prices"]) && count($datos["json_retorno"]["prices"][0]["price_list"]) > 0) {
                foreach ($datos["json_retorno"]["prices"][0]["price_list"] as $precio) {
                    $precio_bd = $precio;
                    $precio_bd["name"] = addslashes($this->eliminar_acentos($precio["name"]));
                    array_push($precios_producto, $precio_bd);
                }
            }

            $datos["json_retorno"]["prices"][0]["price_list"] = $precios_producto;
            $json_retorno                                     = json_encode($json_siigo);

            return  "
                        uid                    = '$datos[uid]', 
                        empresa                = '$datos[empresa]', 
                        uid_producto           = '$datos[uid_producto]', 
                        id_siigo_producto      = '$datos[id_siigo_producto]', 
                        codigo_producto_siigo  = '$datos[codigo_producto_siigo]', 
                        nombre_producto        = '$nombre_producto', 
                        precio                 = '$datos[precio]', 
                        json_retorno           = '$json_retorno'
                    ";
        } else if ($tabla == $this->TABLA_FACTURAS) {

            $id_siigo_factura              = addslashes($datos["id_siigo_factura"]);

            $json_siigo                    = $datos["json_retorno"];
            $json_siigo["id"]              = $id_siigo_factura;
            $json_siigo["customer"]["id"]  = addslashes($json_siigo["customer"]["id"]);
            $json_siigo["observations"]    = addslashes($this->eliminar_acentos($json_siigo["observations"]));

            $array_productos = array();

            foreach ($json_siigo["items"] as $producto) {
                $producto_bd = array();

                $producto_bd["id"] = addslashes($this->eliminar_acentos($producto["id"]));
                $producto_bd["code"] = addslashes($this->eliminar_acentos($producto["code"]));
                $producto_bd["description"] = addslashes($this->eliminar_acentos($producto["description"]));

                if (isset($producto["taxes"])) {
                    $array_impuestos = array();
                    foreach ($producto["taxes"] as $impuestos) {
                        $impuesto_bd = array();
                        $impuesto_bd["name"] = addslashes($this->eliminar_acentos($impuestos["name"]));
                        $impuesto_bd["type"] = addslashes($this->eliminar_acentos($impuestos["type"]));
                        array_push($array_impuestos, $impuesto_bd);
                    }
                    $producto_bd["taxes"] = $array_impuestos;
                }

                array_push($array_productos, $producto_bd);
            }

            $json_siigo["items"] = $array_productos;

            $json_siigo["payments"][0]["name"] = addslashes($this->eliminar_acentos($json_siigo["payments"][0]["name"]));

            if (isset($json_siigo["payments"][0]["due_date"])) {
                $json_siigo["payments"][0]["due_date"] = addslashes($this->eliminar_acentos($json_siigo["payments"][0]["due_date"]));
            }

            $json_siigo["metadata"]["created"]     = addslashes($this->eliminar_acentos($json_siigo["metadata"]["created"]));

            $json_retorno                          = json_encode($json_siigo);

            return  "
                        uid                    = '$datos[uid]', 
                        empresa                = '$datos[empresa]',
                        id_siigo_factura       = '$datos[id_siigo_factura]', 
                        codigo_factura         = '$datos[codigo_factura]', 
                        numero_factura         = '$datos[numero_factura]', 
                        identificacion_cliente = '$datos[identificacion_cliente]', 
                        fecha                  = '$datos[fecha]', 
                        json_retorno           = '$json_retorno'
                    ";
        }
    }

    public function actualizarEnBd(string $tabla, array $datos, string $condicion)
    {
        parent::conectar();
        $nuevos_datos = $this->retornar_datos_para_actualizar_bd($tabla, $datos);
        $sql = "UPDATE $tabla SET $nuevos_datos WHERE $condicion";
        $resultado = parent::query($sql);
        parent::cerrar();
        return $resultado;
    }

    public function getToken()
    {
        $request = new HTTP_Request2();
        $request->setUrl($this->BASE_URL_AUTH);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setHeader(array(
            'Content-Type' => 'application/json'
        ));
        $request->setBody(json_encode(array(
            'username'   => $this->USER_ACCESS_SIIGO,
            'access_key' => $this->KEY_ACCESS_SIIGO
        )));
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                $this->TOKEN = $json["access_token"];
                return $this->TOKEN;
            } else {
                // echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
                //     $response->getReasonPhrase();
                $this->TOKEN = "";
                return $this->TOKEN;
            }
        } catch (HTTP_Request2_Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            $this->TOKEN = "";
            return $this->TOKEN;
        }
    }
    public function obtener_token_siigo()
    {
        $request = new HTTP_Request2();
        $request->setUrl($this->BASE_URL_AUTH);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setHeader(array(
            'Content-Type' => 'application/json'
        ));
        $request->setBody(json_encode(array(
            'username'   => $this->USER_ACCESS_SIIGO,
            'access_key' => $this->KEY_ACCESS_SIIGO
        )));
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                $this->TOKEN = $json["access_token"];
            } else {
                // echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
                //     $response->getReasonPhrase();
                $this->TOKEN = "";
            }
        } catch (HTTP_Request2_Exception $e) {
            // echo 'Error: ' . $e->getMessage();
            $this->TOKEN = "";
        }
    }

    public function verificar_unico_documento_identificacion(array $data)
    {
        if (isset($data["uid"]) && isset($data["empresa"]) && isset($data["tipo_documento"]) && isset($data["numero_documento"])) {
            if (($data["uid"] != "1291" && $data["empresa"] == "0") || ($data["uid"] != "5" && $data["empresa"] == "1")) { // USUARIOS DE PRUEBA NASBI FELIX Y TIENDA 1    
                $buscar_tipo_identificacion = $this->seleccionarDeBd("buyinbig.documento_identificacion", true, "nombre_es = '$data[tipo_documento]' OR nombre_en = '$data[tipo_documento]'");
                if (count($buscar_tipo_identificacion) > 0) {
                    if (intval($data["empresa"]) == 0) { // NATURAL
                        $buscar_usuario_mismo_tipo_numero = $this->seleccionarDeBd("peer2win.usuarios", true, "tipo_identificacion = '$data[tipo_documento]' AND numero_identificacion = '$data[numero_documento]'");
                        if (count($buscar_usuario_mismo_tipo_numero) == 0) {
                            return array(
                                "status" => "errorUsuarioNoExiste",
                                "data"   => $data
                            );
                        } else if (count($buscar_usuario_mismo_tipo_numero) == 1 && $buscar_usuario_mismo_tipo_numero[0]["id"] == $data["uid"]) {
                            return array("status" => "success");
                        } else {
                            return array(
                                "status" => "errorDocumentoyNumeroDuplicado",
                                "data"   => $data
                            );
                        }
                    } else { // JURDICA
                        $buscar_usuario_mismo_tipo_numero = $this->seleccionarDeBd("buyinbig.empresas", true, "nit = '$data[numero_documento]'");
                        if (count($buscar_usuario_mismo_tipo_numero) == 0) {
                            return array(
                                "status" => "errorEmpresaNoExiste",
                                "data"   => $data
                            );
                        } else if (count($buscar_usuario_mismo_tipo_numero) == 1 && $buscar_usuario_mismo_tipo_numero[0]["id"] == $data["uid"]) {
                            return array("status" => "success");
                        } else {
                            return array(
                                "status" => "errorDocumentoyNumeroDuplicado",
                                "data"   => $data
                            );
                        }
                    }
                } else {
                    return array(
                        "status" => "errTipoDocumentoNoExiste",
                        "data"   => $data
                    );
                }
            } else {
                return array("status" => "success");
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "no data",
                "data"    => $data
            );
        }
    }

    public function map_cliente_siigo(array $data)
    {
        $respuesta_buscar_info_cliente = $this->obtener_data_cliente($data);
        if (count($respuesta_buscar_info_cliente) > 0) {

            if (!isset($respuesta_buscar_info_cliente['Identification']) || $respuesta_buscar_info_cliente['Identification'] == "") {
                return array(
                    'status'    => 'errorIdentificacionCliente',
                    'message'   => 'No se pudo obtener la identificacion del cliente, verificar informacion en la base de datos',
                    'data'      => $data,
                    'dataExtra' => $respuesta_buscar_info_cliente
                );
            }

            $numero_telefono = $respuesta_buscar_info_cliente["Phone_Number"];
            if (strlen($numero_telefono) > 10) {
                $numero_telefono = substr($numero_telefono, 0, 10);
            }

            $nameSiigo = Array("", "");
            if( intval($data["empresa"]) == 0 ){
                $nameSiigo = Array(
                    $respuesta_buscar_info_cliente["FullName"],
                    "."
                );
            }else{
                $nameSiigo = Array(
                    $respuesta_buscar_info_cliente["FullName"]
                );
            }

            return array(
                'status' => 'success',
                'message' => 'Mapeado con exito.',
                'data' => array(
                    'type'                    => $this->CUSTOMER_CLIENTE,
                    'person_type'             => intval($data["empresa"]) == 0 ? 'Person' : 'Company',
                    'id_type'                 => $respuesta_buscar_info_cliente["IdTypeCode"],
                    'identification'          => $respuesta_buscar_info_cliente["Identification"],
                    'name'                    => $nameSiigo,

                    'commercial_name'         => intval($data["empresa"]) == 0 ? '' : $respuesta_buscar_info_cliente["FullName"],
                    'active'                  => true,
                    'vat_responsible'         => $respuesta_buscar_info_cliente["IsVATCompanyType"],
                    'fiscal_responsibilities' => $respuesta_buscar_info_cliente["FiscalResponsibilities"],
                    'address' => array(
                        'address'             => $respuesta_buscar_info_cliente["Address"],
                        'city' => array(
                            'country_code'    => $respuesta_buscar_info_cliente["City_CountryCode"],
                            'state_code'      => $respuesta_buscar_info_cliente["City_StateCode"],
                            'city_code'       => $respuesta_buscar_info_cliente["City_CityCode"]
                        ),
                        'postal_code'         => $respuesta_buscar_info_cliente["Codigo_postal"]
                    ),
                    'phones' => [
                        array(
                            'indicative'      => '57',
                            'number'          => strval($numero_telefono),
                        )
                    ],
                    'contacts' => [
                        array(
                            'first_name'      => $respuesta_buscar_info_cliente["FullName"],
                            'last_name'       => ".",
                            'email'           => $respuesta_buscar_info_cliente["EMail"],
                            'phone' => array(
                                'indicative'  => '57',
                                'number'      => strval($numero_telefono),
                            )
                        )
                    ],
                    'related_users'           => array(
                        'seller_id'           => $this->SELLER,
                        'collector_id'        => $this->SELLER
                    )
                )
            );
        } else {
            return array(
                'status'    => 'errorRequiereCrearCliente',
                'message'   => 'Debe crear el cliente en siigo',
                'data'      => $data,
                'dataExtra' => $respuesta_buscar_info_cliente
            );
        }
    }

    public function verificacion_data_cliente_con_siigo(array $data)
    {
        if (!isset($data) && !isset($data["uid"]) && !isset($data["empresa"])) {
            return array(
                'status'   => 'fail',
                'message'  => 'Campos vacios',
                'data'     => $data
            );
        }
        $selectxdatosxfacturacion =
            "SELECT * FROM buyinbig.siigo_datos_facturacion WHERE uid = $data[uid] AND empresa = $data[empresa]";
        parent::conectar();
        $siigo_datos_facturacion = parent::consultaTodo( $selectxdatosxfacturacion );
        parent::cerrar();

        if( COUNT( $siigo_datos_facturacion ) == 0 ){
            return array(
                'status'   => 'noExisteTerceroEnSiigo',
                'message'  => 'Te invitamos a diligenciar tus datos de facturación mediante el módulo de facturación, opción datos de facturación',
                'data'     => $data
            );
        }
        return array(
            'status'   => 'success',
            'message'  => 'Usuario tipo tercero hallado con exito.',
            'data'     => $data
        );
    }

    public function lanzar_peticion_crear_actualizar_cliente(array $body, String $id_usuario)
    {
        $request = new HTTP_Request2();
        if ($id_usuario == "") {
            $request->setUrl($this->BASE_URL_CLIENTES);
            $request->setMethod(HTTP_Request2::METHOD_POST);
        } else {
            $request->setUrl($this->BASE_URL_CLIENTES . "/$id_usuario");
            $request->setMethod(HTTP_Request2::METHOD_PUT);
        }
        $request->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();
            if ($response->getStatus() == 200 || $response->getStatus() == 201) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                return array(
                    "status"         => "success",
                    "data"           => $json
                );
            } else {
                return array(
                    "status"         => "failPeticionRespuestaNo200CrearActualizarCliente",
                    "status-request" => $response->getStatus(),
                    "mensaje-error"  => json_decode($response->getBody()),
                    "dataSend"       => $body
                );
            }
        } catch (HTTP_Request2_Exception $e) {
            return array(
                "status"         => "failPeticionEntroCatchCrearActualizarCliente",
                "mensaje-error"  => $e
            );
        }
    }

    public function crear_actualizar_cliente(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"])) {
            
            $map_cliente = $this->map_cliente_siigo($data);

            if ($map_cliente["status"] == "success") {

                $buscar_cliente = $this->seleccionarDeBd($this->TABLA_CLIENTES, true, "uid = '$data[uid]' AND empresa = '$data[empresa]'");


                $repuesta_peticion = Array("status" => "fail");
                if( COUNT($buscar_cliente) == 0 ){
                    $repuesta_peticion = $this->lanzar_peticion_crear_actualizar_cliente(
                        $map_cliente["data"],
                        ""
                    );
                }else{
                    $repuesta_peticion = $this->lanzar_peticion_crear_actualizar_cliente(
                        $map_cliente["data"],
                        $buscar_cliente[0]["id_siigo_cliente"]
                    );
                }

                if ($repuesta_peticion["status"] != "success") {
                    return $repuesta_peticion;
                }

                $data_bd = array(
                    "uid"               => $data["uid"],
                    "empresa"           => $data["empresa"],
                    "id_siigo_cliente"  => $repuesta_peticion["data"]["id"],
                    "identificacion"    => $repuesta_peticion["data"]["identification"],
                    "nombre_completo"   => $repuesta_peticion["data"]["name"][0],
                    "tipo_de_tercero"   => "CLIENTE",
                    "direccion"         => $repuesta_peticion["data"]["address"]["address"],
                    "id_siigo_contacto" => 0,
                    "numero_celular"    => $repuesta_peticion["data"]["phones"][0]["number"],
                    "correo"            => $repuesta_peticion["data"]["contacts"][0]["email"],
                    "json_retorno"      => $repuesta_peticion["data"]
                );

                if (count($buscar_cliente) == 0) {

                    $resultado_insertar_bd = $this->insertarEnBd($this->TABLA_CLIENTES, $data_bd);

                    if ($resultado_insertar_bd > 0) {
                        return array(
                            "status"  => "success",
                            "message" => "Se ha creado el cliente en siigo, y se guardo en la base de datos",
                            "data"    => $map_cliente["data"]
                        );
                    } else {
                        return array(
                            "status"  => "failBdInsertarCliente",
                            "message" => "Se ha creado el cliente en siigo, pero no se guardo en la base de datos",
                            "data"    => $map_cliente["data"]
                        );
                    }
                } else {
                    $id_cliente = $repuesta_peticion["data"]["id"];
                    $resultado_actualizar_bd  = $this->actualizarEnBd($this->TABLA_CLIENTES, $data_bd, "id_siigo_cliente = '$id_cliente'");
                    if ($resultado_actualizar_bd == true) {
                        return array(
                            "status"  => "success",
                            "message" => "Se ha creado el cliente en siigo, y se actualizo en la base de datos",
                            "data"    => $map_cliente["data"]
                        );
                    } else {
                        return array(
                            "status"  => "failBdActualizarCliente",
                            "message" => "Se ha creado el cliente en siigo, pero no se actualizo en la base de datos",
                            "data"    => $map_cliente["data"]
                        );
                    }
                }
            } else {
                return $map_cliente;
            }
        } else {
            return array(
                "status"         => "noData",
                "message"        => "Data incompleta",
                "data"           => $data,
            );
        }
    }

    public function consultar_cliente(array $data){
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"])) {
            $buscar_cliente = $this->seleccionarDeBd($this->TABLA_CLIENTES, true, "uid = '$data[uid]' AND empresa = '$data[empresa]'");
            if (count($buscar_cliente) > 0) {
                return array(
                    "status" => "success",
                    "data"   => (array) json_decode($buscar_cliente[0]["json_retorno"], true)
                );
            } else {
                return array(
                    "status"  => "errClienteNoExiste",
                    "message" => "El cliente no existe",
                    "data"    => $data,
                );
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "Data incompleta",
                "data"    => $data,
            );
        }
    }

    public function sincronizar_productos_siigo_nube()
    {
        $FECHA_INICIO = "2010-01-01";
        $request_cantidad_datos = new HTTP_Request2();
        $request_cantidad_datos->setUrl("https://api.siigo.com/v1/products?created_start=$FECHA_INICIO");
        $request_cantidad_datos->setMethod(HTTP_Request2::METHOD_GET);
        $request_cantidad_datos->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        $cantidad_datos_en_siigo = 0;
        try {
            $response = $request_cantidad_datos->send();
            if ($response->getStatus() == 200) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                $cantidad_datos_en_siigo = intval($json["pagination"]["total_results"]);
            } else {
                $cantidad_datos_en_siigo = 0;
            }
        } catch (HTTP_Request2_Exception $e) {
            $cantidad_datos_en_siigo = 0;
        }


        $status  = "success";
        $message = "Se han sincronizado todos los productos";
        $errores = array();

        $request = new HTTP_Request2();
        $request->setUrl("https://api.siigo.com/v1/products?created_start=$FECHA_INICIO&page_size=$cantidad_datos_en_siigo");
        $request->setMethod(HTTP_Request2::METHOD_GET);
        $request->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                foreach ($json["results"] as $producto) {

                    $precio = 0;

                    if (isset($producto["prices"]) && count($producto["prices"][0]["price_list"]) > 0) {
                        $precio = $producto["prices"][0]["price_list"][0]["value"];
                    }

                    $data_producto = array(
                        "uid"                   => 100001,
                        "empresa"               => 0,
                        "uid_producto"          => 0,
                        "id_siigo_producto"     => $producto["id"],
                        "codigo_producto_siigo" => $producto["code"],
                        "nombre_producto"       => strtoupper($this->eliminar_acentos($producto["name"])),
                        "precio"                => $precio,
                        "json_retorno"          => $producto
                    );

                    $condicion_buscar = "codigo_producto_siigo = '$producto[code]' ";
                    $respuesta = $this->seleccionarDeBd($this->TABLA_PRODUCTOS, true, $condicion_buscar);
                    if (count($respuesta) > 0) {
                        $respuesta = $this->actualizarEnBd($this->TABLA_PRODUCTOS, $data_producto, $condicion_buscar);
                        if ($respuesta == false) {
                            $status = "failActualizarProductos";
                            $message = "Ha fallado alguna consulta sql";
                            array_push($errores, "actualizar;$producto[code]");
                        }
                    } else {
                        $insertar_en_bd = $this->insertarEnBd($this->TABLA_PRODUCTOS, $data_producto);
                        if ($insertar_en_bd < 0) {
                            $status = "failInsertarProductos";
                            $message = "Ha fallado alguna consulta sql";
                            array_push($errores, "insertar;$producto[code]");
                        }
                    }
                }

                return array(
                    "status"  => $status,
                    "message" => $message,
                    "errores" => $errores
                );
            } else {
                return array(
                    "status"         => "failPeticionRespuestaNo200ListarProductos",
                    "status-request" => $response->getStatus(),
                    "mensaje-error"  => json_decode($response->getBody())
                );
            }
        } catch (HTTP_Request2_Exception $e) {
            return array(
                "status"         => "failPeticionEntroCatchCrearFactura",
                "mensaje-error"  => $e
            );
        }
    }

    public function consultar_producto(array $data){
        if (isset($data) && isset($data["codigo"])) {
            $buscar_producto = $this->seleccionarDeBd($this->TABLA_PRODUCTOS, true, "codigo_producto_siigo = '$data[codigo]'");
            if (count($buscar_producto) > 0) {
                return array(
                    "status" => "success",
                    "data"   => (array) json_decode($buscar_producto[0]["json_retorno"], true)
                );
            } else {
                return array(
                    "status"  => "errProductoNoExiste",
                    "message" => "El producto no existe",
                    "data"    => $data,
                );
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "Data incompleta",
                "data"    => $data,
            );
        }
    }

    public function map_factura_siigo(array $data)
    {
        $respuesta_buscar_info_cliente = $this->obtener_data_cliente($data);
        if (count($respuesta_buscar_info_cliente) > 0) {
            if (!isset($respuesta_buscar_info_cliente['Identification']) || $respuesta_buscar_info_cliente['Identification'] == "") {
                return array(
                    'status'    => 'errorIdentificacionCliente',
                    'message'   => 'No se pudo obtener la identificacion del cliente, verificar informacion en la base de datos',
                    'data'      => $data,
                    'dataExtra' => $respuesta_buscar_info_cliente
                );
            }

            $data_verificar_identificacion = array(
                "uid"              => $data["uid"],
                "empresa"          => $data["empresa"],
                "tipo_documento"   => $respuesta_buscar_info_cliente["tipo_documento"],
                "numero_documento" => $respuesta_buscar_info_cliente["Identification"]
            );

            $respuesta_verificar_identificacion = $this->verificar_unico_documento_identificacion($data_verificar_identificacion);
            if ($respuesta_verificar_identificacion["status"] != "success") {
                return $respuesta_verificar_identificacion;
            }

            return array(
                'status' => 'success',
                'message' => 'Mapeado con exito.',
                'data' => array(
                    'document' => array(
                        'id' => $this->TIPO_FACTURA_CODE // Id de la factura ( CONSTANTE ) 
                    ),
                    'date'     => $data["payments"][0]["due_date"],
                    'customer' => array(
                        'identification' => "" . $respuesta_buscar_info_cliente["Identification"],
                        'branch_office'  => 0
                    ),
                    'seller'       => $this->SELLER, // Id de la persona que administra siigo para la api ( CONSTANTE )
                    'observations' => addslashes($data["observations"]),
                    'items'        => $data["items"],
                    'payments'     => $data["payments"]
                )
            );
        } else {
            return array(
                'status'    => 'errorRequiereCrearCliente',
                'message'   => 'Debe crear el cliente en siigo',
                'data'      => $data,
                'dataExtra' => $respuesta_buscar_info_cliente
            );
        }
    }

    public function lanzar_peticion_crear_factura(array $body)
    {
        $request = new HTTP_Request2();
        $request->setUrl($this->BASE_URL_FACTURAS);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setHeader(array(
            'Content-Type' => 'application/json',
            "Authorization" => $this->TOKEN
        ));
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();
            if ($response->getStatus() == 201) {
                $body = $response->getBody();
                $json = json_decode($body, true);
                return array(
                    "status"         => "success",
                    "data"           => $json
                );
            } else {
                return array(
                    "status"         => "failPeticionRespuestaNo201CrearFactura",
                    "status-request" => $response->getStatus(),
                    "mensaje-error"  => json_decode($response->getBody()),
                    "dataSend"       => $body
                );
            }
        } catch (HTTP_Request2_Exception $e) {
            return array(
                "status"         => "failPeticionEntroCatchCrearFactura",
                "mensaje-error"  => $e
            );
        }
    }

    public function crear_factura(array $data)
    {
        if (isset($data) && isset($data["uid"])  && isset($data["empresa"]) && isset($data["observations"]) && isset($data["items"]) && count($data["items"]) > 0 && isset($data["payments"]) && count($data["payments"]) > 0) {

            $puede_seguir = false;
            $respuesta_comparar_data_cliente_con_data_siigo = $this->verificacion_data_cliente_con_siigo($data);

            if ($respuesta_comparar_data_cliente_con_data_siigo["status"] != "sinCambios") {
                $repuesta_crear_cliente = $this->crear_actualizar_cliente($data);
                if ($repuesta_crear_cliente["status"] == "success") {
                    $puede_seguir = true;
                } else {
                    return $repuesta_crear_cliente;
                }
            } else {
                $puede_seguir = true;
            }

            if ($puede_seguir) {

                $verificacion_correcta_items = true;
                $array_productos             = array();

                foreach ($data["items"] as $producto) {
                    if (
                        isset($producto["code"])        && $producto["code"]        != "" &&
                        isset($producto["description"]) && $producto["description"] != "" &&
                        isset($producto["quantity"])    && $producto["quantity"]    != "" && intval($producto["quantity"]) > 0 &&
                        isset($producto["price"])       && $producto["price"]       != "" && intval($producto["price"]) >= 0 &&
                        isset($producto["discount"])    && $producto["price"]       != ""
                    ) {

                        if (isset($producto["taxes"])) {
                            $producto["taxes"] = intval($producto["taxes"]);
                            if ($this->TAXES[$producto["taxes"]] != null) {
                                $producto["taxes"] = $this->TAXES[$producto["taxes"]];
                            }
                        }
                        array_push($array_productos, $producto);
                    } else {
                        $verificacion_correcta_items = false;
                        break;
                    }
                }

                if ($verificacion_correcta_items) {

                    $data["payments"][0]["id"] = $this->FORMA_DE_PAGO;

                    if (floatval($data["payments"][0]["value"]) >= 0) {

                        $data["payments"][0]["due_date"] = date("Y-m-d");
                        $data["items"]                   = $array_productos;


                        $map_factura_siigo = $this->map_factura_siigo($data);

                        if ($map_factura_siigo['status'] != 'success') {
                            return $map_factura_siigo;
                        }

                        $response = $this->lanzar_peticion_crear_factura($map_factura_siigo['data']);

                        if ($response['status'] != 'success') {
                            return $response;
                        }

                        $fecha_creacion_factura = new DateTime($response["data"]["date"]);

                        $array_insert_bd = array(
                            "uid"                    => $data["uid"],
                            "empresa"                => $data["empresa"],
                            "id_siigo_factura"       => $response["data"]["id"],
                            "codigo_factura"         => $response["data"]["document"]["id"],
                            "numero_factura"         => $response["data"]["number"],
                            "identificacion_cliente" => $response["data"]["customer"]["identification"],
                            "fecha"                  => strval(intval($fecha_creacion_factura->getTimestamp()) * 1000),
                            "json_retorno"           => $response["data"]
                        );

                        $resultado_insertar_bd = $this->insertarEnBd($this->TABLA_FACTURAS, $array_insert_bd);

                        if ($resultado_insertar_bd > 0) {
                            $data_retornada                     = $map_factura_siigo["data"];
                            $data_retornada["id"]               = $resultado_insertar_bd;
                            $data_retornada["id_siigo_factura"] = $response["data"]["id"];
                            $data_retornada["numero_factura"]   = $response["data"]["number"];
                            return array(
                                "status"         => "success",
                                "message"        => "Se ha creado la factura en siigo, y se guardo en la base de datos",
                                "data"           => $data_retornada
                            );
                        } else {
                            return array(
                                "status"         => "failBdInsertarFactura",
                                "message"        => "Se ha creado la factura en siigo, pero no se guardo en la base de datos",
                                "numero-factura" => $response["data"]["number"]
                            );
                        }
                    } else {
                        return array(
                            "status"  => "failPayments",
                            "message" => "La data payments esta mal",
                            "data"    => null
                        );
                    }
                } else {
                    return array(
                        "status"  => "failItems",
                        "message" => "La data de los items esta mal",
                        "data"    => null,
                        'items'   => $data["items"]
                    );
                }
            }else{
                return array(
                    "status"  => "errorFacturaNoCreada",
                    "message" => "Error en el momento de intentar crear la factura.",
                    "data"    => $respuesta_comparar_data_cliente_con_data_siigo
                );
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "Data incompleta",
                "data"    => $data
            );
        }
    }

    public function consultar_factura(array $data){
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["numero"]) && intval($data["numero"]) > 0) {
            $buscar_factura = $this->seleccionarDeBd($this->TABLA_FACTURAS, true, "uid = '$data[uid]' AND empresa = '$data[empresa]' AND numero_factura = '$data[numero]'");
            if (count($buscar_factura) > 0) {
                return array(
                    "status" => "success",
                    "data"   => (array) json_decode($buscar_factura[0]["json_retorno"], true)
                );
            } else {
                return array(
                    "status"  => "errFacturaNoExiste",
                    "message" => "La factura no existe",
                    "data"    => $data,
                );
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "Data incompleta",
                "data"    => $data,
            );
        }
    }

    public function pdf_factura(array $data)
    {
        if (isset($data) && isset($data["uid"]) && $data["uid"] != "" && isset($data["empresa"]) && $data["empresa"] != "" && isset($data["numero"]) && intval($data["numero"]) > 0) {
            $buscar_factura = $this->seleccionarDeBd($this->TABLA_FACTURAS, true, "uid = '$data[uid]' AND empresa = '$data[empresa]' AND numero_factura = '$data[numero]'");
            if (count($buscar_factura) > 0) {
                $id_factura = $buscar_factura[0]["id_siigo_factura"];
                $tipadoFile = "data:application/pdf;base64,";
                $request = new HTTP_Request2();
                $request->setUrl($this->BASE_URL_FACTURAS . "/$id_factura/pdf");
                $request->setMethod(HTTP_Request2::METHOD_GET);
                $request->setHeader(array(
                    "Authorization" => $this->TOKEN
                ));
                try {
                    $response = $request->send();
                    $body = $response->getBody();
                    $json = json_decode($body, true);
                    return array(
                        "status" => "success",
                        "url"    => $tipadoFile . $json["base64"]
                    );
                } catch (HTTP_Request2_Exception $e) {
                    // echo 'Error: ' . $e->getMessage();
                    return array(
                        "status"         => "failPeticionPdf",
                        "status_request" => $response->getStatus(),
                        "error"          => $e->getMessage()
                    );
                }
            } else {
                return array(
                    "status"  => "errFacturaNoExiste",
                    "message" => "La factura no existe",
                    "data"    => $data,
                );
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "Data incompleta",
                "data"    => $data,
            );
        }
    }

    public function consultar_datos_de_siigo(array $data)
    {
        if (isset($data) && isset($data["usuarios"]) && isset($data["grupos_de_inventario"]) && isset($data["tipos_de_comprobantes"]) && isset($data["metodos_de_pago"]) && isset($data["impuestos_registrados"])) {
            $url_consulta = "";
            if (intval($data["usuarios"]) == 1) {
                $url_consulta = "https://api.siigo.com/v1/users";
            } else if (intval($data["grupos_de_inventario"]) == 1) {
                $url_consulta = "https://api.siigo.com/v1/account-groups";
            } else if (intval($data["tipos_de_comprobantes"]) == 1) {
                $url_consulta = "https://api.siigo.com/v1/document-types?type=FV"; // FACTURA DE VENTA
            } else if (intval($data["metodos_de_pago"]) == 1) {
                $url_consulta = "https://api.siigo.com/v1/payment-types?document_type=FV"; // FACTURA DE VENTA
            } else if (intval($data["impuestos_registrados"]) == 1) {
                $url_consulta = "https://api.siigo.com/v1/taxes";
            }
            $request = new HTTP_Request2();
            $request->setUrl($url_consulta);
            $request->setMethod(HTTP_Request2::METHOD_GET);
            $request->setHeader(array(
                'Content-Type' => 'application/json',
                "Authorization" => $this->TOKEN
            ));
            try {
                $response = $request->send();
                if ($response->getStatus() == 200) {
                    $body = $response->getBody();
                    $json = json_decode($body, true);
                    return $json;
                } else {
                    return [];
                }
            } catch (HTTP_Request2_Exception $e) {
                return [];
            }
        } else {
            return array(
                "status"  => "noData",
                "message" => "no data"
            );
        }
    }

}
?>