<?php
require 'conexion.php';

class UsuarioNasbi extends Conexion
{
    public function loginUsuario(Array $data)
    {
        parent::conectar();
        $buscarDatos = parent::consultaTodo("SELECT * from peer2win.usuarios u where username = '$data[user]' or email = '$data[user]'");

        if (count($buscarDatos) > 0) {
            if( !isset($data) || !isset($data['user']) || !isset($data['password']) ) {
                return array(
                    'status'  => 'fail',
                    'message' => 'no data',
                    'data'    => null
                );
            }

            $data["plataforma"] = 2;
            $requestlogin =  (array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/users/login.php', $data));


            if(!isset($requestlogin) || !isset($requestlogin['status']) || $requestlogin['status'] != 'success') {
                return $requestlogin;
            }

            $requestlogin['data'][0] = (array) $requestlogin['data'][0];

            $idUsuario = $requestlogin['data'][0]['id'];

            $requestlogin['data'][0]['referido_nasbi'] = $this->referUsuario(['uid' => $requestlogin['data'][0]['id'], 'empresa' => 1])['data'];
            $requestlogin["empresa"] = 0;


            $resutCamposFaltantes = $this->consultar_campos_faltantes([
                "user_uid"     => $idUsuario,
                "user_empresa" => 0
            ]);
            $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital_status"] = $resutCamposFaltantes["status"];
            $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital"] = false;
            $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = [];
            $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = 0;

            if ( $resutCamposFaltantes["status"] == "success" ) {
                $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital"] = true;

            }else if ( $resutCamposFaltantes["status"] == "success_Campo_faltante"){
                $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = $resutCamposFaltantes["data"];
                $requestlogin['data'][0]["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = $resutCamposFaltantes["campos_faltantes"];

            }else{

            }
            $requestlogin['data'][0]["id_proveedor"]=0;
            return $requestlogin;
        } else {
            $dataNew = [
                "data" => [
                    "correo"         => $data["user"],
                    "clave"          => $data["password"],
                    "mostrar_alerta" => true
                ]
            ];
            $data2 = (array) json_decode(parent::remoteRequest("http://nasbi.peers2win.com/api/controllers/empresas/?login", $dataNew));
            $data2["empresa"] = 1;
            $empresaData["empresa"] = 1;


            $empresaData = (array) $data2["data"];


            $empresaData["completar_datos"] = (array) [
                'completo_formulario_pago_digital_status' => '',
                'completo_formulario_pago_digital' => false,
                'completo_formulario_pago_digital_lista_faltantes' => [],
                'completo_formulario_pago_digital_lista_faltantes_num' => 0,
            ];

            if ( $data2["status"] == "success") {
                $empresaData["empresa"] = 1;


                $resutCamposFaltantes = $this->consultar_campos_faltantes([
                    "user_uid" => $empresaData["id"],
                    "user_empresa" => 1
                ]);

                $empresaData["completar_datos"]["completo_formulario_pago_digital_status"] = $resutCamposFaltantes["status"];

                if ( $resutCamposFaltantes["status"] == "success" ) {
                    $empresaData["completar_datos"]["completo_formulario_pago_digital"] = true;

                }else if ( $resutCamposFaltantes["status"] == "success_Campo_faltante"){
                    $empresaData["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = $resutCamposFaltantes["data"];
                    $empresaData["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = $resutCamposFaltantes["campos_faltantes"];

                }

                $data2["data"] = $empresaData;

            }

            $data2["data"]["id_proveedor"]=0; //valor de id_proveedor
            return $data2;
        }
    }

    public function recuperarPassword(Array $data)
    {
        $requestlogin=[];
        $dataReset=[];
        if( !isset($data) || !isset($data['email']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'faltan datos',
                'data'   => $data
            );
        }

        // Averiguamos si el usuario "NO ES EMPRESA"
        parent::conectar();
        $buscarDatos = parent::consultaTodo("SELECT * FROM peer2win.usuarios u WHERE email = '$data[email]'");
        if (count($buscarDatos) > 0) {
            parent::cerrar();

            $dataReset = array(
                'email'      =>  $data['email'],
                'plataforma' =>  3
            );

            $requestlogin =  (array) json_decode(parent::remoteRequest("http://peers2win.com/api/controllers/users/recuperarpassword.php", $dataReset));

            $requestlogin["plataforma_ws"] = "P2W";
            return $requestlogin;

        }else{
            $buscarDatos = parent::consultaTodo("SELECT * FROM buyinbig.empresas u WHERE correo = '$data[email]'");
            parent::cerrar();
            if (count($buscarDatos) > 0) {
                $dataReset = array(
                    'data' => array(
                        'correo' =>  $data['email']
                    )
                );

                $requestlogin =  (array) json_decode(parent::remoteRequest("http://nasbi.peers2win.com/api/controllers/empresas/?generar_token", $dataReset));
                $requestlogin["plataforma_ws"] = "NASBI";
                return $requestlogin;

            }else{
                return array(
                    'status'  => 'errorCorreo',
                    'message' => 'Usuario ' . $data['email'] . ' no existe.',
                    'data'    => $data
                );
            }
        }
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

    public function referUsuario(Array $data)
    {
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
    }

    public function mapReferido(Array $referido)
    {
        foreach ($referido as $x => $ref) {
            $ref['id']                  = floatval($ref['id']);
            $ref['uid']                 = floatval($ref['uid']);
            $ref['empresa']             = floatval($ref['empresa']);
            $ref['estado']              = floatval($ref['estado']);
            $ref['tipo']                = floatval($ref['tipo']);
            $ref['fecha_creacion']      = $ref['fecha_creacion'];
            $ref['fecha_actualizacion'] = $ref['fecha_actualizacion'];

            $referido[$x] = $ref;
        }

        return $referido;
    }


    public function subirPdf(Array $data, Int $fecha, String $id, int $origen=1)
    {

        if($origen==1){

            $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/" . $data['user_uid'] . "_" . $data['user_empresa'];

            if (!file_exists($nombre_fichero)) {
                mkdir($nombre_fichero, 0777, true);
            }

            return $url = $this->uploadPdf([
                'id'   => $id,
                'pdf'  => $data[ $id ],
                'ruta' => "/documentos/datos_persona_natural/" . $data['user_uid'] . "_" . $data['user_empresa'] . '/' . $id . '.pdf',
            ]);
        }else if($origen==2){
            $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/" . $data['user_uid'] . "_" . $data['user_empresa'];

            if (!file_exists($nombre_fichero)) {
                mkdir($nombre_fichero, 0777, true);
            }
            return $url = $this->uploadPdf([
                'id'   => $id,
                'pdf'  => $data[ $id ],
                'ruta' => "/documentos/datos_persona_juridica/" . $data['user_uid'] . "_" . $data['user_empresa'] . '/' . $id . '.pdf',
            ]);
        }

    }
    public function uploadPdf(Array $data)
    {
        // $base64 = base64_decode( explode(';base64,', $data['pdf'])[1] );

        $base64 = base64_decode( $data['pdf'] );
        $filepath1 = $_SERVER['DOCUMENT_ROOT'] . $data['ruta'];
        file_put_contents($filepath1, $base64);
        chmod($filepath1, 0777);
        $url = $_SERVER['SERVER_NAME'] . $data['ruta'];

        return 'https://' . $url;
    }

    public function datos_venta_persona_natural()
    {
        parent::conectar();
        $bancos                     = parent::consultaTodo("SELECT * FROM buyinbig.Bancos;");
        $banco_tipo_de_cuenta       = parent::consultaTodo("SELECT * FROM buyinbig.banco_tipo_de_cuenta;");
        $Cod_Responsabilidad_Fiscal = parent::consultaTodo("SELECT * FROM buyinbig.Cod_Responsabilidad_Fiscal;");
        $Ciiu                       = parent::consultaTodo("SELECT * FROM buyinbig.Ciiu;");
        $documento_identificacion   = parent::consultaTodo("SELECT * FROM buyinbig.documento_identificacion;");
        parent::cerrar();
        $result = array(
            'bancos'                     => $bancos,
            'banco_tipo_de_cuenta'       => $banco_tipo_de_cuenta,
            'Cod_Responsabilidad_Fiscal' => $Cod_Responsabilidad_Fiscal,
            'Ciiu'                       => $Ciiu,
            'documento_identificacion'   => $documento_identificacion
        );
        return $result;
    }

    public function completar_datos_persona_natural(Array $data)
    {

        if( !isset($data) || !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial']) || !isset($data['correo']) || !isset($data['telefono']) || !isset($data['pagina_web']) || !isset($data['id_ciiu']) || !isset($data['autorretenedor']) || !isset($data['id_cod_responsabilidad_fiscal']) || !isset($data['regimen']) || !isset($data['administras_recuersos_publicos']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['descripcion_productos_a_vender']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['vende_mas_de_un_articulos'])  || !isset($data['referencias']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos. Parametros incompletos.',
                    'data' => $data
                );
        }

        if ( count( $data['referencias'] ) <= 0 || count( $data['referencias'] ) > 2 ) {
            return array(
                'status' => 'errorReferenciasVacia',
                'message'=> 'Debe agregar 2 referencias',
                'data' => null
            );
        }

        if( isset($data['tipo_identificacion']) && isset($data['numero_identificacion']) ){
            if( $data['tipo_identificacion'] != "" && $data['numero_identificacion'] != "" ){
                $update_p2w = 
                "UPDATE peer2win.usuarios
                SET
                    tipo_identificacion   = '$data[tipo_identificacion]',
                    numero_identificacion = '$data[numero_identificacion]'
                WHERE id = '$data[user_uid]';";
                
                parent::conectar();
                $update_p2w = parent::query( $update_p2w );
                parent::cerrar();

            }else{
                return array(
                    'status' => 'failDocumentosDeIdentificacion',
                    'message'=> 'Los usuarios naturales requieren un número de identificación y un tipo de documento.',
                    'data' => $data
                );
            }
        }else{
            return array(
                'status' => 'failDocumentosDeIdentificacion',
                'message'=> 'Los usuarios naturales requieren un número de identificación y un tipo de documento.',
                'data' => $data
            );
        }

        parent::conectar();
        $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) > 0) {
            return array(
                'status'  => 'errorDuplicado',
                'message' => 'Los datos de este usuario ya se encuentrán registrados.',
                'data'    => null
            );
        }

        parent::cerrar();

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc");
        $cedula_doc               = $this->subirPdf($data, $fecha, "cedula_doc");
        $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc");
        
        //INICIO SLASHES
        $campos_ignorados_comilla_simple = ['id', 'user_uid', 'user_empresa','rut_doc', 'cedula_doc' , 'certificado_bancario_doc', 'status_revision'];
        $campos_ignorados_comilla_simple_ref = ['id','user_uid', 'user_empresa', 'id_datos_persona_natural', 'id_datos_persona_juridica'];

        $nombres_natural_revision =  $this->extraer_nombre_columna_tabla('datos_persona_natural');
        foreach ($nombres_natural_revision as $x => $campo) {
            $existe_nombre_columna = in_array($campo["COLUMN_NAME"], $campos_ignorados_comilla_simple);

            if(!$existe_nombre_columna){

                $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]);
            }
        }
        //FIN SLASHES

        parent::conectar();

        $insertxdatosxpersonaxnatural =
        "INSERT INTO buyinbig.datos_persona_natural(
            user_uid,
            user_empresa,
            nombre_comercial,
            correo,
            telefono,
            pagina_web,
            id_ciiu,
            autorretenedor,
            id_cod_responsabilidad_fiscal,
            regimen,
            administras_recuersos_publicos,
            tiene_un_cargo_publico,
            goza_de_un_reconocimiento_publico,
            familiar_con_caracteristicas_anteriores,
            descripcion_productos_a_vender,
            id_bancos,
            id_banco_tipo_de_cuenta,
            nCuenta,
            movimientos_moneda_extranjera,
            vende_mas_de_un_articulos,
            rut_doc,
            cedula_doc,
            certificado_bancario_doc,
            status_revision
        )
        VALUES(
            '$data[user_uid]',
            '$data[user_empresa]',
            '$data[nombre_comercial]',
            '$data[correo]',
            '$data[telefono]',
            '$data[pagina_web]',
            '$data[id_ciiu]',
            '$data[autorretenedor]',
            '$data[id_cod_responsabilidad_fiscal]',
            '$data[regimen]',
            '$data[administras_recuersos_publicos]',
            '$data[tiene_un_cargo_publico]',
            '$data[goza_de_un_reconocimiento_publico]',
            '$data[familiar_con_caracteristicas_anteriores]',
            '$data[descripcion_productos_a_vender]',
            '$data[id_bancos]',
            '$data[id_banco_tipo_de_cuenta]',
            '$data[nCuenta]',
            '$data[movimientos_moneda_extranjera]',
            '$data[vende_mas_de_un_articulos]',
            '$rut_doc',
            '$cedula_doc',
            '$certificado_bancario_doc',
            0
        )";

        $id_dpn = parent::queryRegistro($insertxdatosxpersonaxnatural);

        if (intval( $id_dpn ) <= 0) {
            return array(
                'status'    => 'errorDatos',
                'message'   => 'No fue posible realizar el registro de los datos del usuario natural. Verificar la información.'
            );
        }

        $nombres_natural_revision_re =  $this->extraer_nombre_columna_tabla('datos_persona_referencias', 2);

        foreach ($data['referencias'] as $key => $value) {

            //INICIO SLASHES
            foreach ($nombres_natural_revision_re as $x => $campo) {
                $existe_nombre_columna = in_array($campo["COLUMN_NAME"], $campos_ignorados_comilla_simple_ref);
                if(!$existe_nombre_columna){
                    $value[$campo["COLUMN_NAME"]] = addslashes($value[$campo["COLUMN_NAME"]]);
                }
            }
            //FIN SLASHES

            $id_dpn_ref = parent::queryRegistro(
            "INSERT INTO buyinbig.datos_persona_referencias(
                user_uid,
                user_empresa,
                id_datos_persona_natural,
                nombre_completo,
                telefono,
                empresa,
                telefono_empresa,
                cargo,
                correo_electronico
            )
            VALUES(
                '$value[user_uid]',
                '$value[user_empresa]',
                '$id_dpn',
                '$value[nombre_completo]',
                '$value[telefono]',
                '$value[empresa]',
                '$value[telefono_empresa]',
                '$value[cargo]',
                '$value[correo_electronico]'
            )");
            if (intval( $id_dpn_ref ) <= 0) {
                return array(
                    'status'    => 'errorDatosRefer',
                    'message'   => 'No fue posible realizar el registro de las referencias de los datos del usuario natural. Verificar la información.'
                );
            }
        }
        parent::cerrar();
           $repuesta = $this->insertar_en_usuario_revision_natural($data, intval($id_dpn));
           if(!$repuesta) return array("status"=> "errorRevision", "mensagge"=>"Error al insertar en tabla de revision");

        $resutCamposFaltantes = $this->consultar_campos_faltantes([
            "user_uid" => $data["user_uid"],
            "user_empresa" => $data["user_empresa"]
        ]);


        $faltanDatos["completar_datos"] = (array) [
            'completo_formulario_pago_digital_status' => '',
            'completo_formulario_pago_digital' => false,
            'completo_formulario_pago_digital_lista_faltantes' => [],
            'completo_formulario_pago_digital_lista_faltantes_num' => 0,
        ];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_status"] = $resutCamposFaltantes["status"];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital"] = false;
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = [];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = 0;

        if ( $resutCamposFaltantes["status"] == "success" ) {
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital"] = true;

        }else if ( $resutCamposFaltantes["status"] == "success_Campo_faltante"){
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = $resutCamposFaltantes["data"];
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = $resutCamposFaltantes["campos_faltantes"];

        }else{

        }

        $this->envio_correo_registro_exitoso($data);

        return array( 
            'status'      => 'success',
            'message'     => 'Información completada',
            'rowAffect'   => $id_dpn,
            'faltanDatos' => $faltanDatos
        );
    }
    public function completar_datos_persona_natural_rut_doc(Array $data)
    {
        if( !isset($data) || !isset($data['rut_doc']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => $data
                );
        }
        parent::conectar();
        $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) <= 0) {
            return array(
                'status'  => 'errorNoExisteUsuario',
                'message' => 'Las credenciales de este usuario no estan registradas',
                'data'    => null
            );
        }
        $datos_persona_natural = $datos_persona_natural[0];
        parent::cerrar();

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc");


        $updatexdatosxpersonaxnatural =
        "UPDATE buyinbig.datos_persona_natural
        SET
            rut_doc = '$rut_doc'
        WHERE (id = '$datos_persona_natural[id]');";

        parent::conectar();
        parent::query( $updatexdatosxpersonaxnatural );
        parent::cerrar();
        return array(
            'status'                => 'success',
            'message'               => 'Información completada',
            'rut_doc'               => $rut_doc,
            'datos_persona_natural' => $datos_persona_natural
        );

    }
    public function completar_datos_persona_natural_cedula_doc(Array $data)
    {
        if( !isset($data) || !isset($data['cedula_doc']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => $data
                );
        }
        parent::conectar();
        $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) <= 0) {
            return array(
                'status'  => 'errorNoExisteUsuario',
                'message' => 'Las credenciales de este usuario no estan registradas',
                'data'    => null
            );
        }
        $datos_persona_natural = $datos_persona_natural[0];
        parent::cerrar();

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $cedula_doc               = $this->subirPdf($data, $fecha, "cedula_doc");

        $updatexdatosxpersonaxnatural =
        "UPDATE buyinbig.datos_persona_natural
        SET
            cedula_doc = '$cedula_doc'
        WHERE (id = '$datos_persona_natural[id]');";

        parent::conectar();
        parent::query( $updatexdatosxpersonaxnatural );
        parent::cerrar();
        return array(
            'status'                => 'success',
            'message'               => 'Información completada',
            'cedula_doc'            => $cedula_doc,
            'datos_persona_natural' => $datos_persona_natural
        );

    }
    public function completar_datos_persona_natural_certificado_bancario_doc(Array $data)
    {
        if( !isset($data) || !isset($data['certificado_bancario_doc']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => $data
                );
        }

        parent::conectar();
        $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) <= 0) {
            return array(
                'status'  => 'errorNoExisteUsuario',
                'message' => 'Las credenciales de este usuario no estan registradas',
                'data'    => null
            );
        }
        $datos_persona_natural = $datos_persona_natural[0];
        parent::cerrar();

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc");

        $updatexdatosxpersonaxnatural =
        "UPDATE buyinbig.datos_persona_natural
        SET
            certificado_bancario_doc = '$certificado_bancario_doc'
        WHERE (id = '$datos_persona_natural[id]');";

        parent::conectar();
        parent::query( $updatexdatosxpersonaxnatural );
        parent::cerrar();

        return array(
            'status'                    => 'success',
            'message'                   => 'Información completada',
            'certificado_bancario_doc'  => $certificado_bancario_doc,
            'datos_persona_natural'     => $datos_persona_natural
        );

    }

    public function actualizar_datos_persona_natural(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial']) || !isset($data['correo']) || !isset($data['telefono']) || !isset($data['pagina_web']) || !isset($data['id_ciiu']) || !isset($data['autorretenedor']) || !isset($data['id_cod_responsabilidad_fiscal']) || !isset($data['regimen']) || !isset($data['administras_recuersos_publicos']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['descripcion_productos_a_vender']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['vende_mas_de_un_articulos']) || !isset($data['rut_doc']) || !isset($data['cedula_doc']) || !isset($data['certificado_bancario_doc'])
            || !isset($data['referencias']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos 2',
                    'data' => null
                );
        }

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $rut_doc                      = $data["rut_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $rut_doc) ) == 1) {
            $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc");
        }

        $cedula_doc                   = $data["cedula_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $cedula_doc) ) == 1) {
            $cedula_doc               = $this->subirPdf($data, $fecha, "cedula_doc");
        }

        $certificado_bancario_doc = $data["certificado_bancario_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_bancario_doc) ) == 1) {
            $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc");
        }

        parent::conectar();

        $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) <= 0) {
            return array(
                'status'  => 'errorUsuarioVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }
        $datos_persona_natural = $datos_persona_natural[0];

        if( isset($data['tipo_identificacion']) && isset($data['numero_identificacion']) ){
            if( $data['tipo_identificacion'] != "" && $data['numero_identificacion'] != "" ){
                $update_p2w = 
                "UPDATE peer2win.usuarios
                SET
                    tipo_identificacion   = '$data[tipo_identificacion]',
                    numero_identificacion = '$data[numero_identificacion]'
                WHERE id = '$data[user_uid]';";
                
                parent::cerrar();
                parent::conectar();
                $update_p2w = parent::query( $update_p2w );
                parent::cerrar();
                parent::conectar();

            }else{
                return array(
                    'status' => 'failDocumentosDeIdentificacion',
                    'message'=> 'Los usuarios naturales requieren un número de identificación y un tipo de documento.',
                    'data' => $data
                );
            }
        }else{
            return array(
                'status' => 'failDocumentosDeIdentificacion',
                'message'=> 'Los usuarios naturales requieren un número de identificación y un tipo de documento.',
                'data' => $data
            );
        }

        //INICIO SLASHES
        $campos_ignorados_comilla_simple = ['id','user_uid', 'user_empresa','rut_doc', 'cedula_doc' , 'certificado_bancario_doc', 'status_revision','referencias'];
        $campos_ignorados_comilla_simple_ref = ['id','user_uid', 'user_empresa', 'id_datos_persona_natural', 'id_datos_persona_juridica']; 

        $nombres_natural_revision =  $this->extraer_nombre_columna_tabla('datos_persona_natural', 2);
        foreach ($nombres_natural_revision as $x => $campo) {
            $existe_nombre_columna = in_array($campo["COLUMN_NAME"], $campos_ignorados_comilla_simple);

            if(!$existe_nombre_columna){
                $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]);
            }
        }
        //FIN SLASHES

        $updatexdatosxpersonaxnatural =
        "UPDATE buyinbig.datos_persona_natural
        SET
            nombre_comercial                        = '$data[nombre_comercial]',
            correo                                  = '$data[correo]',
            telefono                                = '$data[telefono]',
            pagina_web                              = '$data[pagina_web]',
            id_ciiu                                 = '$data[id_ciiu]',
            autorretenedor                          = '$data[autorretenedor]',
            id_cod_responsabilidad_fiscal           = '$data[id_cod_responsabilidad_fiscal]',
            regimen                                 = '$data[regimen]',
            administras_recuersos_publicos          = '$data[administras_recuersos_publicos]',
            tiene_un_cargo_publico                  = '$data[tiene_un_cargo_publico]',
            goza_de_un_reconocimiento_publico       = '$data[goza_de_un_reconocimiento_publico]',
            familiar_con_caracteristicas_anteriores = '$data[familiar_con_caracteristicas_anteriores]',
            descripcion_productos_a_vender          = '$data[descripcion_productos_a_vender]',
            id_bancos                               = '$data[id_bancos]',
            id_banco_tipo_de_cuenta                 = '$data[id_banco_tipo_de_cuenta]',
            nCuenta                                 = '$data[nCuenta]',
            movimientos_moneda_extranjera           = '$data[movimientos_moneda_extranjera]',
            vende_mas_de_un_articulos               = '$data[vende_mas_de_un_articulos]',
            rut_doc                                 = '$rut_doc',
            cedula_doc                              = '$cedula_doc',
            certificado_bancario_doc                = '$certificado_bancario_doc',
            status_revision                         =  0

        WHERE (id = '$datos_persona_natural[id]');";
        parent::query( $updatexdatosxpersonaxnatural );



        $selectxdatosxpersonaxreferencias = "SELECT * FROM buyinbig.datos_persona_referencias WHERE  id_datos_persona_natural = '$datos_persona_natural[id]'";

        $selectxdatosxpersonaxreferencias = parent::consultaTodo($selectxdatosxpersonaxreferencias);
        if ( count($selectxdatosxpersonaxreferencias) <= 0) {
            return array(
                'status'  => 'errorUsuarioReferenciasVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }

        $index = 0;    

        $nombres_natural_revision_re =  $this->extraer_nombre_columna_tabla('datos_persona_referencias', 2);

        foreach ($data['referencias'] as $key => $value) { 
            //INICIO SLASHES
            foreach ($nombres_natural_revision_re as $x => $campo) {
                $existe_nombre_columna = in_array($campo["COLUMN_NAME"], $campos_ignorados_comilla_simple_ref);
                if(!$existe_nombre_columna){
                    $value[$campo["COLUMN_NAME"]] = addslashes($value[$campo["COLUMN_NAME"]]);
                }
            }
            //FIN SLASHES

            $referencia = $selectxdatosxpersonaxreferencias[ $index ];

            $updatexdatosxpersonaxreferencias =
            "UPDATE buyinbig.datos_persona_referencias
            SET
                nombre_completo          = '$value[nombre_completo]',
                telefono                 = '$value[telefono]',
                empresa                  = '$value[empresa]',
                telefono_empresa         = '$value[telefono_empresa]',
                cargo                    = '$value[cargo]',
                correo_electronico       = '$value[correo_electronico]'

            WHERE (id = '$referencia[id]');";
            parent::query( $updatexdatosxpersonaxreferencias );
            $index++;
        }
        parent::cerrar();

        $repuesta = $this->insertar_en_usuario_revision_natural($data, intval($datos_persona_natural["id"]));
        if(!$repuesta) return array("status"=> "errorRevision", "mensagge"=>"Error al insertar en tabla de revision");

        $this->envio_correo_registro_exitoso($data);

        return array(
            'status'    => 'success',
            'message'   => 'Información actualizada'
        );
    }


    public function obtener_datos_persona_natural_by_user(Array $data)
    {
        $referencias_persona_natural=null;

        if( !isset($data['user_uid']) || !isset($data['user_empresa']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => null
                );
        }

        $selectxdatosxpersonaxnatural =
        "SELECT
            bpn.*,

            c.Agrupacion_por_Tarifa,
            c.Codigo,
            c.Descripcion AS 'Ciiu_descripcion',
            c.Tarifa_por_Mil,


            b.bankCode,
            b.bankName,


            btdc.nombre_es,
            btdc.nombre_en,

            crf.descripcion_es,
            crf.descripcion_en

        FROM buyinbig.datos_persona_natural bpn
        INNER JOIN Ciiu c ON (c.Codigo = bpn.id_ciiu)
        INNER JOIN Bancos b ON (b.id = bpn.id_bancos)
        INNER JOIN banco_tipo_de_cuenta btdc ON (btdc.id = bpn.id_banco_tipo_de_cuenta)
        INNER JOIN Cod_Responsabilidad_Fiscal crf ON (crf.id = bpn.id_cod_responsabilidad_fiscal)
        WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        parent::conectar();
        $selectdatospersonanatural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        parent::cerrar();
        if ( count($selectdatospersonanatural) <= 0) {
            return array(
                'status'  => 'errorUsuarioVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }
        $selectdatospersonanatural = $selectdatospersonanatural[0];

        $select_referencia_p_natural ="SELECT * FROM buyinbig.datos_persona_referencias WHERE id_datos_persona_natural = '$selectdatospersonanatural[id]';";


        parent::conectar();
        $referencias_persona_natural = parent::consultaTodo($select_referencia_p_natural);
        parent::cerrar();

        if(count($referencias_persona_natural)==0 || !isset($referencias_persona_natural)){
            $referencias_persona_natural=null;
        }

        $selectdatospersonanatural["referencias"] =  $referencias_persona_natural;

        $selectxrevisionxnatural =
            "SELECT *
            FROM buyinbig.datos_persona_natural_revision
            WHERE user_uid = $data[user_uid] AND user_empresa = $data[user_empresa]
            ORDER BY id DESC LIMIT 1;";
        parent::conectar();
        $selectxrevisionxnatural = parent::consultaTodo($selectxrevisionxnatural);
        parent::cerrar();
        if( count($selectxrevisionxnatural) <= 0 ){
            $selectxrevisionxnatural = null;
        }else{
            $selectxrevisionxnatural = $selectxrevisionxnatural[0];
        }

        return array(
            'status'         => 'success',
            'message'        => 'Datos personales del usuaro',
            'data'           => $selectdatospersonanatural,
            'data_aprobado' => $selectxrevisionxnatural
        );
    }


    public function completar_datos_persona_juridica(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial'])  || !isset($data['id_cod_responsabilidad_fiscal']) ||  !isset($data['nombres']) || !isset($data['apellidos']) || !isset($data['id_documento_identificacion']) || !isset($data['no_identificacion']) || !isset($data['tel_fijo']) || !isset($data['celular']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['correo_pagos']) || !isset($data['describe_correo_pagos']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['activos_corrientes']) || !isset($data['activos_no_corrientes']) || !isset($data['ingresos_ventas']) || !isset($data['pasivos_corrientes']) || !isset($data['pasivos_no_corrientes']) || !isset($data['costos_gastos'])  || !isset($data['patrimonio'])  || !isset($data['ingresos_netos']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['referencias']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => null
                );
        }



        if ( count( $data['referencias'] ) <= 0 || count( $data['referencias'] ) > 2 ) {
            return array(
                'status' => 'errorReferenciasVacia',
                'message'=> 'Debe agregar 2 referencias',
                'data' => null
            );
        }


        //preguntar si falta CIIU
         $data_empresa=$this->obtener_data_empresa([
            "uid"=> $data['user_uid'],
            "empresa"=> $data['user_empresa'] 
        ]);

        if($data_empresa["data"]["id_ciiu"]=="" || !isset($data_empresa["data"]["id_ciiu"])){
           return array("status"=>"fail", "data"=>null,"mensaje"=> "falta el campo id_ciiu", "campo"=>"id_ciiu");
        }

        // fin preguntar si falta CIIU
        $campos_no_validar_comilla_simple= ['id','user_uid', 'user_empresa', 'camara_comercio_doc','rut_doc', 'contador_doc',   'cedula_representante_doc', 'certificado_bancario_doc', 'certificado_composicon_accionaria_doc', 'estados_financieros_doc', 'status_revision', 'referencias'];
        $campos_referencia_comilla_simple= ['id','user_uid', 'user_empresa', 'id_datos_persona_natural', 'id_datos_persona_juridica'];

        $selectxdatosxpersonax_juridica = "SELECT * FROM buyinbig.datos_persona_juridica WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        parent::conectar();
        $datos_persona_juridica = parent::consultaTodo($selectxdatosxpersonax_juridica);
        parent::cerrar();

        if ( count($datos_persona_juridica) > 0) {
            /* parent::cerrar(); */
            return array(
                'status'  => 'errorDuplicado',
                'message' => 'Los datos de este usuario ya se encuentrán registrados.',
                'data'    => null
            );
        }

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);



        // $camara_comercio_doc      = $this->subirPdf($data, $fecha, "camara_comercio_doc", 2);
        // $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc", 2);
        // $contador_doc             = $this->subirPdf($data, $fecha, "contador_doc", 2);
        // $cedula_representante_doc = $this->subirPdf($data, $fecha, "cedula_representante_doc", 2);
        // $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        // $certificado_composicon_accionaria_doc = $this->subirPdf($data, $fecha, "certificado_composicon_accionaria_doc", 2);
        // $estados_financieros_doc = $this->subirPdf($data, $fecha, "estados_financieros_doc", 2);

        
        parent::conectar();
        $nombres_juridica_revision =  $this->extraer_nombre_columna_tabla('datos_persona_juridica');
        /* parent::cerrar(); */

        foreach ($nombres_juridica_revision as $x => $campo) {
            $tipo = in_array($campo["COLUMN_NAME"], $campos_no_validar_comilla_simple);
            if(!$tipo){

            $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]);

            }
         }


        /* parent::conectar(); */

        $insertxdatosxpersonax_juridica =
        "INSERT INTO buyinbig.datos_persona_juridica(
            user_uid,
            user_empresa,
            nombre_comercial,
            id_cod_responsabilidad_fiscal,
            nombres,
            apellidos,
            id_documento_identificacion,
            no_identificacion,
            tel_fijo,
            celular,
            administras_recuersos_publicos,
            tiene_un_cargo_publico,
            goza_de_un_reconocimiento_publico,
            familiar_con_caracteristicas_anteriores,
            correo_pagos,
            describe_correo_pagos,
            id_bancos,
            id_banco_tipo_de_cuenta,
            nCuenta,
            activos_corrientes,
            activos_no_corrientes,
            ingresos_ventas,
            pasivos_corrientes,
            pasivos_no_corrientes,
            costos_gastos,
            patrimonio,
            ingresos_netos,
            movimientos_moneda_extranjera,
            status_revision
        )
        VALUES(
            '$data[user_uid]',
            '$data[user_empresa]',
            '$data[nombre_comercial]',
            '$data[id_cod_responsabilidad_fiscal]',
            '$data[nombres]',
            '$data[apellidos]',
            '$data[id_documento_identificacion]',
            '$data[no_identificacion]',
            '$data[tel_fijo]',
            '$data[celular]',
            '$data[administras_recuersos_publicos]',
            '$data[tiene_un_cargo_publico]',
            '$data[goza_de_un_reconocimiento_publico]',
            '$data[familiar_con_caracteristicas_anteriores]',
            '$data[correo_pagos]',
            '$data[describe_correo_pagos]',
            '$data[id_bancos]',
            '$data[id_banco_tipo_de_cuenta]',
            '$data[nCuenta]',
            '$data[activos_corrientes]',
            '$data[activos_no_corrientes]',
            '$data[ingresos_ventas]',
            '$data[pasivos_corrientes]',
            '$data[pasivos_no_corrientes]',
            '$data[costos_gastos]',
            '$data[patrimonio]',
            '$data[ingresos_netos]',
            '$data[movimientos_moneda_extranjera]',
            0

        )";
        parent::conectar();
        $id_dpn = parent::queryRegistro($insertxdatosxpersonax_juridica);
        parent::cerrar();



        if (intval( $id_dpn ) <= 0) {
            return array(
                'status'    => 'errorDatos',
                'message'   => 'No fue posible realizar el registro de los datos del usuario juridico. Verificar la información.'
            );
        }

        foreach ($data['referencias'] as $key => $value) {

            parent::conectar();
            $nombres_juridica_revision_re =  $this->extraer_nombre_columna_tabla('datos_persona_referencias', 2);
            /* parent::cerrar(); */
            foreach ($nombres_juridica_revision_re as $x => $campo) {
                $tipo = in_array($campo["COLUMN_NAME"], $campos_referencia_comilla_simple);
                if(!$tipo){
                    $value[$campo["COLUMN_NAME"]] = addslashes($value[$campo["COLUMN_NAME"]]);
                }
            }


            parent::conectar();
            $id_dpn_ref = parent::queryRegistro(
            "INSERT INTO buyinbig.datos_persona_referencias(
                user_uid,
                user_empresa,
                id_datos_persona_juridica,
                nombre_completo,
                telefono,
                empresa,
                telefono_empresa,
                cargo,
                correo_electronico
            )
            VALUES(
                '$value[user_uid]',
                '$value[user_empresa]',
                '$id_dpn',
                '$value[nombre_completo]',
                '$value[telefono]',
                '$value[empresa]',
                '$value[telefono_empresa]',
                '$value[cargo]',
                '$value[correo_electronico]'
            )");
            parent::cerrar();

            if (intval( $id_dpn_ref ) <= 0) {
                return array(
                    'status'    => 'errorDatosRefer',
                    'message'   => 'No fue posible realizar el registro de las referencias de los datos del usuario juridico. Verificar la información.'
                );
            }
        }
        
        parent::conectar();
        $repuesta = $this->insertar_en_usuario_revision_juridica($data, intval($id_dpn));
        /* parent::cerrar(); */
        
        if(!$repuesta) return array("status"=> "errorRevision", "mensagge"=>"Error al insertar en tabla de revision");
        $resutCamposFaltantes = $this->consultar_campos_faltantes([
            "user_uid"     => $data["user_uid"],
            "user_empresa" => $data["user_empresa"]
        ]);
        $faltanDatos["completar_datos"] = (array) [
            'completo_formulario_pago_digital_status' => '',
            'completo_formulario_pago_digital' => false,
            'completo_formulario_pago_digital_lista_faltantes' => [],
            'completo_formulario_pago_digital_lista_faltantes_num' => 0,
        ];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_status"] = $resutCamposFaltantes["status"];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital"] = false;
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = [];
        $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = 0;

        if ( $resutCamposFaltantes["status"] == "success" ) {
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital"] = true;

        }else if ( $resutCamposFaltantes["status"] == "success_Campo_faltante"){
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes"] = $resutCamposFaltantes["data"];
            $faltanDatos["completar_datos"]["completo_formulario_pago_digital_lista_faltantes_num"] = $resutCamposFaltantes["campos_faltantes"];

        }else{

        }
        /* $this->envio_correo_registro_exitoso($data); */

        return array(
            'status'     => 'success',
            'message'    => 'Información completada',
            'rowAffect'  => $id_dpn,
            'faltanDatos'=> $faltanDatos
        );
    }


    public function obtener_datos_persona_juridica_by_user(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
                    'data' => null
                );
        }
        $id_ciiu= null;

        $data_empresa=$this->obtener_data_empresa([
            "uid"=> $data['user_uid'],
            "empresa"=> $data['user_empresa']
        ]);

      //  var_dump($data_empresa);
        if(!isset($data_empresa["data"]["id_ciiu"])) return array("status"=> "fail", "mensage"=> "no tiene id ciiu esta persona juridica");
        $id_ciiu= $data_empresa["data"]["id_ciiu"];

        $selectxdatosxpersonax_juridica =
        "SELECT
            bpj.*,

            c.Agrupacion_por_Tarifa,
            c.Codigo,
            c.Descripcion AS 'Ciiu_descripcion',
            c.Tarifa_por_Mil,


            b.bankCode,
            b.bankName,


            btdc.nombre_es,
            btdc.nombre_en,

            crf.descripcion_es,
            crf.descripcion_en

        FROM buyinbig.datos_persona_juridica bpj
        INNER JOIN Ciiu c ON (c.Codigo = '$id_ciiu')
        INNER JOIN Bancos b ON (b.id = bpj.id_bancos)
        INNER JOIN banco_tipo_de_cuenta btdc ON (btdc.id = bpj.id_banco_tipo_de_cuenta)
        INNER JOIN Cod_Responsabilidad_Fiscal crf ON (crf.id = bpj.id_cod_responsabilidad_fiscal)
        WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        parent::conectar();
        $selectdatospersona_juridica= parent::consultaTodo($selectxdatosxpersonax_juridica);
        parent::cerrar();
        if ( count($selectdatospersona_juridica) <= 0) {
            return array(
                'status'  => 'errorUsuarioVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }
        $selectdatospersona_juridica = $selectdatospersona_juridica[0];

        $select_referencia_p_juridica ="SELECT * FROM buyinbig.datos_persona_referencias WHERE id_datos_persona_juridica = '$selectdatospersona_juridica[id]';";

        parent::conectar();
        $referencias_persona_juridica = parent::consultaTodo($select_referencia_p_juridica);
        parent::cerrar();

        if(count($referencias_persona_juridica)==0 || !isset($referencias_persona_juridica)){
            $referencias_persona_juridica=null;
        }

        $selectdatospersona_juridica["referencias"] =  $referencias_persona_juridica;


        $selectxrevisionxjuridica =
            "SELECT *
            FROM buyinbig.datos_persona_juridica_revision
            WHERE user_uid = $data[user_uid] AND user_empresa = $data[user_empresa]
            ORDER BY id DESC LIMIT 1;";
        parent::conectar();
        $selectxrevisionxjuridica = parent::consultaTodo($selectxrevisionxjuridica);
        parent::cerrar();
        if( count($selectxrevisionxjuridica) <= 0 ){
            $selectxrevisionxjuridica = null;
        }else{
            $selectxrevisionxjuridica = $selectxrevisionxjuridica[0];
        }


        return array(
            'status'         => 'success',
            'message'        => 'Datos personales del usuaro',
            'data'           => $selectdatospersona_juridica,
            'data_aprobado' => $selectxrevisionxjuridica
        );
    }


    public function obtener_data_empresa(Array $data){
        parent::conectar();
        $selectxuser = null;
        if($data['empresa'] == 1) {
            $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = '1'";
            $usuario = parent::consultaTodo($selectxuser);
            parent::cerrar();

            if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => $data);

            $usuario = $this->mapUsuarios($usuario, $data['empresa']);
            return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
        }else{
            parent::cerrar();
            return null;
        }
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
        $data_ciiu= null;
        foreach ($usuarios as $x => $user) {

            if (!isset( $user['idioma'] )) {
                $user['idioma'] = "ES";
            }
            if($empresa == 1){
                $datanombre = $user['razon_social'];
                $dataempresa = $user['razon_social'];
                $datacorreo = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $dataIdioma= strtoupper($user['idioma']);
                $data_uid=$user["id"];
                $data_ciiu= $user["id_ciiu"];
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
            $user['id_ciiu']= $data_ciiu;
            $usuarios[$x] = $user;
        }
        return $usuarios;
    }


    public function actualizar_datos_persona_juridica(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial'])  || !isset($data['id_cod_responsabilidad_fiscal']) ||  !isset($data['nombres']) || !isset($data['apellidos']) || !isset($data['id_documento_identificacion']) || !isset($data['no_identificacion']) || !isset($data['tel_fijo']) || !isset($data['celular']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['correo_pagos']) || !isset($data['describe_correo_pagos']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['activos_corrientes']) || !isset($data['activos_no_corrientes']) || !isset($data['ingresos_ventas']) || !isset($data['pasivos_corrientes'])
        || !isset($data['pasivos_no_corrientes']) || !isset($data['costos_gastos'])  || !isset($data['patrimonio'])  || !isset($data['ingresos_netos']) || !isset($data['movimientos_moneda_extranjera'])  || !isset($data['referencias']) || !isset($data['id_ciiu']) ){

            return array(
                'status' => 'fail',
                'message'=> 'faltan datos',
                'data' => null
            );
        }

        if(intval($data['user_empresa'])!=1)  return array('status' => 'fail','message'=> 'el usuario no es empresa','data' => null);

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);

        // $camara_comercio_doc                      = $data["camara_comercio_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $camara_comercio_doc) ) == 1) {
        //     $camara_comercio_doc                  = $this->subirPdf($data, $fecha, "camara_comercio_doc", 2);
        // }

        // $rut_doc                      = $data["rut_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $rut_doc) ) == 1) {
        //     $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc", 2);
        // }

        // $contador_doc                      = $data["contador_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $contador_doc) ) == 1) {
        //     $contador_doc                  = $this->subirPdf($data, $fecha, "contador_doc", 2);
        // }

        // $cedula_representante_doc                      = $data["cedula_representante_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $cedula_representante_doc) ) == 1) {
        //     $cedula_representante_doc                  = $this->subirPdf($data, $fecha, "cedula_representante_doc", 2);
        // }

        // $certificado_bancario_doc                      = $data["certificado_bancario_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_bancario_doc) ) == 1) {
        //     $certificado_bancario_doc                  = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        // }

        // $certificado_bancario_doc                      = $data["certificado_bancario_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_bancario_doc) ) == 1) {
        //     $certificado_bancario_doc                  = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        // }

        // $certificado_composicon_accionaria_doc                      = $data["certificado_composicon_accionaria_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_composicon_accionaria_doc) ) == 1) {
        //     $certificado_composicon_accionaria_doc                  = $this->subirPdf($data, $fecha, "certificado_composicon_accionaria_doc", 2);
        // }

        // $estados_financieros_doc                      = $data["estados_financieros_doc"];
        // if ( count( explode('https://nasbi.peers2win.com/documentos', $estados_financieros_doc) ) == 1) {
        //     $estados_financieros_doc                  = $this->subirPdf($data, $fecha, "estados_financieros_doc", 2);
        // }



        parent::conectar();

        $selectxdatosxpersonax_juridica = "SELECT * FROM buyinbig.datos_persona_juridica WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        $datos_persona_juridica = parent::consultaTodo($selectxdatosxpersonax_juridica);
        if ( count($datos_persona_juridica) <= 0) {
            return array(
                'status'  => 'errorUsuarioVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }
        
        //INICIO SLASHES
        $campos_no_validar_comilla_simple = ['id','user_uid', 'user_empresa', 'camara_comercio_doc','rut_doc', 'contador_doc',	'cedula_representante_doc',	'certificado_bancario_doc',	'certificado_composicon_accionaria_doc', 'estados_financieros_doc', 'status_revision'];
        $campos_referencia_comilla_simple = ['id','user_uid', 'user_empresa', 'id_datos_persona_natural', 'id_datos_persona_juridica'];

        $nombres_juridica_revision =  $this->extraer_nombre_columna_tabla('datos_persona_juridica', 2);
        foreach ($nombres_juridica_revision as $x => $campo) {
            $tipo = in_array($campo["COLUMN_NAME"], $campos_no_validar_comilla_simple);

            if(!$tipo){
                $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]);
            }
         }
        
        //FIN SLASHES

        $datos_persona_juridica = $datos_persona_juridica[0];

        $updatexdatosxpersonax_juridica =
        "UPDATE buyinbig.datos_persona_juridica
        SET
            nombre_comercial                        = '$data[nombre_comercial]',
            id_cod_responsabilidad_fiscal           = '$data[id_cod_responsabilidad_fiscal]',
            nombres                                 = '$data[nombres]',
            apellidos                               = '$data[apellidos]',
            id_documento_identificacion             = '$data[id_documento_identificacion]',
            no_identificacion                       = '$data[no_identificacion]',
            tel_fijo                                = '$data[tel_fijo]',
            celular                                 = '$data[celular]',
            administras_recuersos_publicos          = '$data[administras_recuersos_publicos]',
            tiene_un_cargo_publico                  = '$data[tiene_un_cargo_publico]',
            goza_de_un_reconocimiento_publico       = '$data[goza_de_un_reconocimiento_publico]',
            familiar_con_caracteristicas_anteriores = '$data[familiar_con_caracteristicas_anteriores]',
            correo_pagos                            = '$data[correo_pagos]',
            describe_correo_pagos                   = '$data[describe_correo_pagos]',
            id_bancos                               = '$data[id_bancos]',
            id_banco_tipo_de_cuenta                 = '$data[id_banco_tipo_de_cuenta]',
            nCuenta                                 = '$data[nCuenta]',
            activos_corrientes                      = '$data[activos_corrientes]',
            activos_no_corrientes                   = '$data[activos_no_corrientes]',
            ingresos_ventas                         = '$data[ingresos_ventas]',
            pasivos_corrientes                      = '$data[pasivos_corrientes]',
            pasivos_no_corrientes                   = '$data[pasivos_no_corrientes]',
            costos_gastos                           = '$data[costos_gastos]',
            patrimonio                              = '$data[patrimonio]',
            ingresos_netos                          = '$data[ingresos_netos]',
            movimientos_moneda_extranjera           = '$data[movimientos_moneda_extranjera]',
            status_revision                         = 0

        WHERE (id = '$datos_persona_juridica[id]');";
        parent::query($updatexdatosxpersonax_juridica);


        // -- camara_comercio_doc                     = '$camara_comercio_doc',
        // -- rut_doc                                 = '$rut_doc',
        // -- contador_doc                            = '$contador_doc',
        // -- cedula_representante_doc                = '$cedula_representante_doc',
        // -- certificado_bancario_doc                = '$certificado_bancario_doc',
        // -- certificado_composicon_accionaria_doc   = '$certificado_composicon_accionaria_doc',
        // -- estados_financieros_doc                 = '$estados_financieros_doc',

        // Actualizar id_ciiu empresa

        // $updatempresa_juridica =
        // "UPDATE buyinbig.empresas
        // SET
        //     id_ciiu                       = '$data[id_ciiu]'
        // WHERE (id = '$data[user_uid]');";

        // parent::query($updatempresa_juridica);

        // fin Actualizar id_ciiu empresa

        $selectxdatosxpersonaxreferencias = "SELECT * FROM buyinbig.datos_persona_referencias WHERE id_datos_persona_juridica = '$datos_persona_juridica[id]';";

        $selectxdatosxpersonaxreferencias = parent::consultaTodo($selectxdatosxpersonaxreferencias);
        if ( count($selectxdatosxpersonaxreferencias) <= 0) {
            return array(
                'status'  => 'errorUsuarioReferenciasVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }

        $index = 0;

        $nombres_juridica_revision_re =  $this->extraer_nombre_columna_tabla('datos_persona_referencias', 2);

        foreach ($data['referencias'] as $key => $value) { 

            foreach ($nombres_juridica_revision_re as $x => $campo) {
                $tipo = in_array($campo["COLUMN_NAME"], $campos_referencia_comilla_simple);
                if(!$tipo){
                    $value[$campo["COLUMN_NAME"]] = addslashes($value[$campo["COLUMN_NAME"]]);
                }
            }
 
            $referencia = $selectxdatosxpersonaxreferencias[ $index ];

            $updatexdatosxpersonaxreferencias =
            "UPDATE buyinbig.datos_persona_referencias
            SET
                nombre_completo          = '$value[nombre_completo]',
                telefono                 = '$value[telefono]',
                empresa                  = '$value[empresa]',
                telefono_empresa         = '$value[telefono_empresa]',
                cargo                    = '$value[cargo]',
                correo_electronico       = '$value[correo_electronico]'

            WHERE (id = '$referencia[id]');";
            parent::query( $updatexdatosxpersonaxreferencias );
            $index++;
        }
        parent::cerrar();

        $repuesta = $this->insertar_en_usuario_revision_juridica($data, intval($datos_persona_juridica["id"]));
        if(!$repuesta) return array("status"=> "errorRevision", "mensagge"=>"Error al insertar en tabla de revision");

        $this->envio_correo_registro_exitoso($data);

        return array(
            'status'    => 'success',
            'message'   => 'Información actualizada'
        );
    }

    function actualizar_ciiu_tempo(){
        $updatempresa_juridica =
        "UPDATE buyinbig.empresas
        SET
            id_ciiu = '1522'
        WHERE (id = '63');";
        parent::conectar();
        parent::query($updatempresa_juridica);
        parent::cerrar();

        return "bien";
    }

    function eliminar_person_temporal(){ 
        // $updatempresa_juridica =
        // "DELETE FROM  datos_persona_juridica_revision where user_uid = 63 ";
        // parent::conectar();
        // parent::query($updatempresa_juridica);
        // parent::cerrar();

        // $updatempresa_juridica2 =
        // "DELETE FROM  datos_persona_juridica where user_uid = 63 ";
        // parent::conectar();
        // parent::query($updatempresa_juridica2);
        // parent::cerrar();

        $updatempresa_juridica3 =
        "DELETE FROM  datos_persona_referencias where id_datos_persona_juridica = 19 ";
        parent::conectar();
        parent::query($updatempresa_juridica3);
        parent::cerrar();
    }

    function consultar_campos_faltantes($data){
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) ) return array("status"=> "fail_parametro", "mensage"=> "falta parametro");

        if(intval($data['user_empresa'])==1){
          return  $this->saber_todos_los_campos_no_ready_juridico($data);
        }else if(intval($data['user_empresa'])==0){
          return  $this->saber_todos_los_campos_no_ready_natural($data);
        }
    }

    function saber_todos_los_campos_no_ready_juridico($data){
        $array_mensaje=[];
        $bandera=0;
        $data_personal= $this->get_dato_personal([
            "uid"=> $data['user_uid'],
            "empresa"=> $data['user_empresa'],
            "juridico"=> 1
        ]);

        $campos_no_obligatorios=["administras_recuersos_publicos", "tiene_un_cargo_publico", "goza_de_un_reconocimiento_publico", "familiar_con_caracteristicas_anteriores"];

        if (empty($data_personal))  return array("status"=> "fail_no_registro", "mensage"=> "el usuario no presenta registro no tiene ningun dato en la tabla", "data"=>null);

        $array_propiedades=  array_keys($data_personal);


        foreach ($array_propiedades as $x => $propiedad) {
          if($data_personal[$propiedad]==""  || !isset($data_personal[$propiedad])){
                    $tipo = in_array($propiedad, $campos_no_obligatorios);
                    if(!$tipo){
                        $bandera=1;
                        array_push($array_mensaje,array("mensaje"=> "falta el campo"." ".$propiedad, "campo"=>$propiedad));
                    }
          }
        }

        //preguntar si falta CIIU
        $data_empresa=$this->obtener_data_empresa([
            "uid"=> $data['user_uid'],
            "empresa"=> $data['user_empresa']
        ]);

        if( isset( $data_empresa ) ) {
            if( isset( $data_empresa["data"] ) ) {
                if( !isset( $data_empresa["data"]["id_ciiu"] ) ) {
                    $bandera = 1;
                    array_push(
                        $array_mensaje,
                        array(
                            'mensaje' => "falta el campo id_ciiu",
                            "campo"   => "id_ciiu"
                        )
                    );
                }else if( $data_empresa["data"]["id_ciiu"] == null || $data_empresa["data"]["id_ciiu"] == ""){
                    $bandera = 1;
                    array_push(
                        $array_mensaje,
                        array(
                            'mensaje' => "falta el campo id_ciiu",
                            "campo"   => "id_ciiu"
                        )
                    );

                }
            }
        }

        // fin preguntar si falta CIIU

        if($bandera==0){
          return array("status"=> "success", "mensage"=> "no le falta ningun campo","data"=> null);
        }else if($bandera==1){
          return array("status"=> "success_Campo_faltante","mensage"=> "faltan campos" ,"data"=> $array_mensaje, "campos_faltantes"=> count($array_mensaje));
        }
    }

    function saber_todos_los_campos_no_ready_natural($data){
        $array_mensaje=[];
        $bandera=0;
        $campos_no_obligatorios=[];
        $data_personal= $this->get_dato_personal([
          "uid"=> $data['user_uid'],
          "empresa"=> $data['user_empresa'],
          "juridico"=> 0
        ]);
        $campos_no_obligatorios=["administras_recuersos_publicos", "tiene_un_cargo_publico", "goza_de_un_reconocimiento_publico", "familiar_con_caracteristicas_anteriores", "pagina_web"];

        if (empty($data_personal))  return array("status"=> "fail_no_registro", "mensage"=> "el usuario no presenta registro no tiene ningun dato en la tabla", "data"=>null);

        $array_propiedades=  array_keys($data_personal);

        foreach ($array_propiedades as $x => $propiedad) {
            if($data_personal[$propiedad]==""  || !isset($data_personal[$propiedad])){
                $tipo = in_array($propiedad, $campos_no_obligatorios);
                if(!$tipo){
                    $bandera=1;
                    array_push($array_mensaje,array("mensaje"=> "falta el campo"." ".$propiedad, "campo"=>$propiedad));
                }
            }
        }

        if($bandera==0){
            return array(
                "status"=> "success",
                "mensage"=> "no le falta ningun campo",
                "data"=> null
            );
        }else if($bandera==1){
            return array(
                "status"=> "success_Campo_faltante",
                "mensage"=> "faltan campos",
                "data"=> $array_mensaje,
                "campos_faltantes"=> count($array_mensaje)
            );
        }
    }


    function get_dato_personal(Array $data){
        if($data["juridico"]==1){
            parent::conectar();
            $dato_usuario = parent::consultaTodo("SELECT * FROM buyinbig.datos_persona_juridica WHERE  user_uid = '$data[uid]' and  user_empresa = '$data[empresa]'  ORDER BY id DESC; ");
            parent::cerrar();
      }else if($data["juridico"]==0){

        parent::conectar();
        $dato_usuario = parent::consultaTodo("SELECT * FROM buyinbig.datos_persona_natural WHERE  user_uid = '$data[uid]' and  user_empresa = '$data[empresa]'  ORDER BY id DESC; ");
        parent::cerrar();

      }

      if(count($dato_usuario)<=0){
          return $dato_usuario;
      }else{
          return $dato_usuario[0];
      }

    }


    function filter_by_value(Array $array, String $index, $value){
        $newarray = null;
        if(is_array($array) && count($array)>0)
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray[] = $array[$key];
                    // $newarray[$key] = $array[$key];
                }
            }
        }
        return $newarray;
    }


    function envio_correo_registro_exitoso($data){
        $data_user            = "";
        $data["user_empresa"] = intval($data["user_empresa"]);

        if(intval($data["user_empresa"]) == 1){
            $data_user = $this->obtener_data_empresa([
                "uid"     => $data['user_uid'],
                "empresa" => $data["user_empresa"]
            ]);

        }else if(intval($data["user_empresa"])==0){
            $data_user = $this->datosUser([
                'uid'     => $data['user_uid'],
                'empresa' => intval($data["user_empresa"])
            ]);

        }else{
        }

        if( isset($data_user["data"]) ){
            $this->html_registro_exitoso_bienvenida( $data_user["data"] );
        }
    }



    function html_registro_exitoso_bienvenida(Array $data_user){
        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/registro_vender/registroExitoso.html");


        $html = str_replace("{{trans166_}}", $json->trans166_, $html);
        $html = str_replace("{{trans167_}}", $json->trans167_, $html);
        $html = str_replace("{{trans168_}}", $json->trans168_, $html);
        $html = str_replace("{{trans169_}}", $json->trans169_, $html);
        $html = str_replace("{{trans170_}}", $json->trans170_, $html);
        $html = str_replace("{{trans_03_brand}}", $json->trans_03_brand, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);


        $para       = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';

        $mensaje1   = $html;
        $titulo     = $json->trans171_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";

        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

        return $response;
    }


    function datosUser(Array $data)
    {
        $selectxuser = null;
        
        if($data['empresa'] == 0) $selectxuser = "SELECT u.* FROM peer2win.usuarios u WHERE u.id = '$data[uid]'";
        if($data['empresa'] == 1) $selectxuser = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]' AND e.estado = 1";
        
        parent::conectar();
        $usuario = parent::consultaTodo($selectxuser);
        parent::cerrar();
        
        if(count($usuario) <= 0) return array('status' => 'fail', 'message'=> 'no user', 'data' => null);

        $usuario = $this->mapUsuarios2($usuario, $data['empresa']);
        return array(
            'status'  => 'success',
            'message' => 'user',
            'data'    => $usuario[0]
        );
    }

    function mapUsuarios2(Array $usuarios, Int $empresa)
    {
        $datanombre   = null;
        $dataempresa  = null;
        $datacorreo   = null;
        $datatelefono = null;
        $datafoto     = null;
        $dataPaso     = null;
        $dataIdioma   = null;
        $data_uid     = null;

        foreach ($usuarios as $x => $user) {

            if (!isset( $user['idioma'] )) {
                $user['idioma'] = "ES";
            }
            if($empresa == 0){
                $datanombre   = $user['nombreCompleto'];
                $dataempresa  = $user['nombreCompleto'];//"Nasbi";
                $datacorreo   = $user['email'];
                $datatelefono = $user['telefono'];
                $datafoto     = $user['avatar'];
                $dataIdioma   = strtoupper($user['idioma']);
                $data_uid     = $user["id"];
            }else if($empresa == 1){
                $datanombre   = $user['razon_social'];
                $dataempresa  = $user['razon_social'];
                $datacorreo   = $user['correo'];
                $datatelefono = $user['telefono'];
                $datafoto     = ($user['foto_logo_empresa'] == "..."? "" : $user['foto_logo_empresa']);
                $dataIdioma   = strtoupper($user['idioma']);
                $data_uid     = $user["id"];
            }

            unset($user);

            $user['nombre']   = $datanombre;
            $user['empresa']  = $dataempresa;
            $user['correo']   = $datacorreo;
            $user['telefono'] = $datatelefono;
            $user['foto']     = $datafoto;
            $user['empresa']  = $empresa;
            $user['idioma']   = "ES";
            $user['uid']      = $data_uid;
            $usuarios[$x]     = $user;
        }
        return $usuarios;
    }

    function traer_usuarios_natural_en_revision(Array $data){
        if( !isset($data['pagina']) ) return array("status"=> "fail_parametro", "mensage"=> "falta parametro");
        $bandera_filtro=0;
        $tipo_campo="" ;
        $campo_input= "";
        if(isset($data['campo']) ){
            $tipo_campo=$data["campo"]; // 1 nombre comercial, 2 correo
            $campo_input= $data["texto"];


            $tipo_campo= ""; // 1 nombre comercial, 2 correo
            $campo_input=  "";

            $datos_para_el_filtro= $this->detectar_campo_del_filtro(1, $data);
            if( $datos_para_el_filtro != false ){
                $bandera_filtro=1;
                $tipo_campo = $datos_para_el_filtro["campo"]; // 1 nombre comercial, 2 correo
                $campo_input = $datos_para_el_filtro["texto"];
            }
        }

        $campos_de_tabla_natural=
            "
            listado.id as id_user_listado,
            listado.user_uid as uid_user,
            listado.user_empresa as empresa_user ,
            listado.nombre_comercial,
            listado.correo,
            listado.telefono,
            listado.pagina_web,
            listado.id_ciiu,
            listado.autorretenedor,
            listado.id_cod_responsabilidad_fiscal,
            listado.regimen,
            listado.administras_recuersos_publicos,
            listado.tiene_un_cargo_publico,
            listado.goza_de_un_reconocimiento_publico,
            listado.familiar_con_caracteristicas_anteriores,
            listado.descripcion_productos_a_vender,
            listado.id_bancos,
            listado.id_banco_tipo_de_cuenta,
            listado.nCuenta,
            listado.movimientos_moneda_extranjera,
            listado.vende_mas_de_un_articulos,
            listado.rut_doc,
            listado.cedula_doc,
            listado.certificado_bancario_doc,
            listado.status_revision,
            ";

        $data_listado = $this->traer_listado_personas_natural_o_juridica(
            'datos_persona_natural',
            intval($data["tipo"]),
            $data,
            'datos_persona_natural_revision',
            'n',
            $campos_de_tabla_natural,
            array(
                "campo" => $tipo_campo,
                "texto" => $campo_input,
                "bandera"=> $bandera_filtro
            )
        ); // t, ipo 1->aprobados, 2->rechazadps, 0-> no leidos, 3->todos

        if( $data_listado == false ) {
            return array(
                'status'        =>"fail",
                'data'          => null,
                'pagina'        => $data["pagina"],
                'total_paginas' => 0
            );
        }
       return $data_listado;
    }

    function detectar_campo_del_filtro(int $tipo, Array $data){
        if(!isset($data['campo']) && empty($data['campo']) && !isset($data['texto']) && empty($data['texto']) ){
            return false;
        }
        $data['campo']= intval($data['campo']);
        if($tipo == 1){

            if($data['campo']==1){
                $data['campo']="nombre_comercial";
            }else if($data['campo']== 2){
                $data['campo']="correo";
            }else{
                return false;
            }
            
            $data['texto']=  addslashes($data['texto']);
            return (array("campo"=> $data['campo'], "texto"=>$data['texto'] ));

        }else if($tipo == 2){

            if($data['campo']==1){
                $data['campo']="nombres";
            }else if($data['campo']== 2){
                $data['campo']="apellidos";
            }else if($data['campo']== 3){
                $data['campo']="correo_pagos";
            } else if($data['campo']== 4){
                $data['campo']="no_identificacion";
            }else{
                return false;
            }
            $data['texto']=  addslashes($data['texto']);
            return (array("campo"=> $data['campo'], "texto"=>$data['texto'] ));
        }
    }

    function traer_usuarios_juridica_en_revision(Array $data){
        if( !isset($data['pagina']) ) return array("status" => "fail_parametro", "mensage" => "falta parametro");

        $bandera_filtro = 0;
        $tipo_campo     = "";
        $campo_input    = "";

        if(isset($data['campo']) ){
            $tipo_campo           = $data["campo"]; // 1 nombre comercial, 2 correo
            $campo_input          = $data["texto"];
            $tipo_campo           = ""; // 1 nombre comercial, 2 correo
            $campo_input          = "";
            $datos_para_el_filtro = $this->detectar_campo_del_filtro(2, $data);
            if( $datos_para_el_filtro != false ){
                $bandera_filtro = 1;
                $tipo_campo     = $datos_para_el_filtro["campo"]; // 1 nombre comercial, 2 correo
                $campo_input    = $datos_para_el_filtro["texto"];
            } 
            
        }

        $campos_de_tabla_juridica= "e.direccion_fisica_de_notificaciones, e.ciudad, e.sector, e.autorretenedor, e.regimen, e.pagina_web, e.razon_social, e.nit,listado.id as id_user_listado,listado.user_uid as uid_user, listado.user_empresa as empresa_user ,  listado.nombre_comercial,	listado.id_cod_responsabilidad_fiscal,	listado.nombres,	listado.apellidos,	listado.id_documento_identificacion, listado.no_identificacion,	listado.tel_fijo,	listado.celular, listado.administras_recuersos_publicos, listado.tiene_un_cargo_publico,	listado.goza_de_un_reconocimiento_publico,	listado.familiar_con_caracteristicas_anteriores,	listado.correo_pagos,	listado.describe_correo_pagos,	listado.id_bancos, listado.id_banco_tipo_de_cuenta,	listado.nCuenta,	listado.activos_corrientes,	listado.activos_no_corrientes,	listado.ingresos_ventas,	listado.pasivos_corrientes,	listado.pasivos_no_corrientes,	listado.costos_gastos,	listado.patrimonio,	listado.ingresos_netos,	listado.movimientos_moneda_extranjera,	listado.camara_comercio_doc,	listado.rut_doc,	listado.contador_doc,	listado.cedula_representante_doc,	listado.certificado_bancario_doc,	listado.certificado_composicon_accionaria_doc,	listado.estados_financieros_doc,	listado.status_revision, ";

        $data_listado= $this->traer_listado_personas_natural_o_juridica('datos_persona_juridica', intval($data["tipo"]), $data, 'datos_persona_juridica_revision','j', $campos_de_tabla_juridica, array("campo"=>$tipo_campo, "texto"=> $campo_input, "bandera"=> $bandera_filtro)); // tipo 1->aprobados, 2->rechazadps, 0-> no leidos, 3->todos
        if($data_listado==false) return array("status"=>"fail","data" => null,'pagina'=> $data["pagina"],'total_paginas'=> 0);
        return $data_listado;
    }

    function traer_listado_personas_natural_o_juridica(String $tabla, int $tipo=3, Array $data, String $tabla_revision, String $persona, String $campos_encabezado=" listado.*,listado.id as id_user_listado, ", Array $data_filtro ){
        $pagina = floatval($data['pagina']);
        $numpagina = 4; //numero de items
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;
        $todos_los_productos;
        $consulta_select2="";
        $campo_en_referencia="";
        $join_revision= "JOIN $tabla_revision as plr on (listado.id = plr.id_datos_persona) group by plr.id_datos_persona";
        $join_revision2= "JOIN (select * from $tabla_revision dtrt  join (select max(id) as id_max from $tabla_revision dr GROUP BY user_uid) sus on (sus.id_max= dtrt.id)) as plr on (listado.id = plr.id_datos_persona)";
        $join_de_datos_generales= "";
        $encabezado_ciiu= "c.Agrupacion_por_Tarifa, c.Codigo,c.Descripcion AS 'Ciiu_descripcion', c.Tarifa_por_Mil,";
        $campo_filtro="";
        $left_para_data_p2w="";
        $left_para_data_empresa_dire="";
        if($data_filtro["bandera"]==1){
            $campo_filtro= $this->preparar_where_de_filtro($tipo,$persona, $data_filtro);
          //  var_dump($campo_filtro); 
        }
        if($persona == 'n'){
            $campo_en_referencia= "id_datos_persona_natural";
            $join= "INNER JOIN Ciiu c ON (c.Codigo = listado.id_ciiu) INNER JOIN Bancos b ON (b.id = listado.id_bancos) INNER JOIN banco_tipo_de_cuenta btdc ON (btdc.id = listado.id_banco_tipo_de_cuenta) INNER JOIN Cod_Responsabilidad_Fiscal crf ON (crf.id = listado.id_cod_responsabilidad_fiscal)";
            $left_para_data_p2w= " LEFT JOIN peer2win.usuarios datos_usuario_natural ON (datos_usuario_natural.id =listado.user_uid) LEFT JOIN buyinbig.direcciones d on d.uid = listado.user_uid AND d.empresa = listado.user_empresa AND d.activa = 1";
        }else{
            $left_para_data_empresa_dire="  LEFT JOIN buyinbig.direcciones d on d.uid = listado.user_uid AND d.empresa = listado.user_empresa AND d.activa = 1";
            $join_de_datos_generales= "";
            $campo_en_referencia= "id_datos_persona_juridica";
            $join= "INNER JOIN empresas e on (e.id = listado.user_uid) INNER JOIN Ciiu c ON (c.Codigo = e.id_ciiu)  INNER JOIN Bancos b ON (b.id = listado.id_bancos) INNER JOIN banco_tipo_de_cuenta btdc ON (btdc.id = listado.id_banco_tipo_de_cuenta) INNER JOIN Cod_Responsabilidad_Fiscal crf ON (crf.id = listado.id_cod_responsabilidad_fiscal) ";
        }

        if($tipo==3){
            if($persona == 'n'){
                $consulta_select=
                "SELECT *
                FROM (
                    SELECT
                        *,
                        (@row_number:=@row_number+1) AS num
                    FROM (
                        SELECT
                            $campos_encabezado
                            plr.*,
                            plr.id as id_statu_revision,
                            $encabezado_ciiu b.bankCode,
                            b.bankName,
                            btdc.nombre_es,
                            btdc.nombre_en,
                            crf.descripcion_es,
                            crf.descripcion_en,

                            ifnull(d.ciudad, '') AS 'datos_resicendia_ciudad',
                            ifnull(d.direccion, '') AS 'datos_resicendia_direccion',

                            datos_usuario_natural.nombreCompleto AS 'pn_nombreCompleto',
                            datos_usuario_natural.tipo_identificacion AS 'pn_tipo_identificacion',
                            datos_usuario_natural.numero_identificacion AS 'pn_numero_identificacion'



                        FROM $tabla as listado
                        $join
                        $left_para_data_p2w

                        JOIN (SELECT @row_number := 0) r $join_revision2 ORDER BY plr.id ASC )as datos $campo_filtro )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";


                $consultar_todos= "SELECT * FROM (SELECT $campos_encabezado  plr.*, plr.id as id_statu_revision,$encabezado_ciiu b.bankCode, b.bankName, btdc.nombre_es, btdc.nombre_en,crf.descripcion_es, crf.descripcion_en FROM $tabla as listado $join    $join_revision2 ) AS datos $campo_filtro ";
            }else{
                $consulta_select=
                "SELECT *
                FROM (
                    SELECT
                        *,
                        (@row_number:=@row_number+1) AS num FROM(
                            SELECT
                                $campos_encabezado
                                plr.*,
                                plr.id as id_statu_revision,
                                $encabezado_ciiu
                                b.bankCode,
                                b.bankName,
                                btdc.nombre_es,
                                btdc.nombre_en,
                                crf.descripcion_es,
                                crf.descripcion_en,

                                ifnull(d.ciudad, '') AS 'datos_resicendia_ciudad',
                                ifnull(d.direccion, '') AS 'datos_resicendia_direccion'

                            FROM $tabla as listado
                            $join
                            $left_para_data_empresa_dire
                            JOIN (SELECT @row_number := 0) r $join_revision2  $join_de_datos_generales  order by plr.id ASC  )as datos $campo_filtro  )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";

                $consultar_todos= "SELECT * FROM (SELECT $campos_encabezado  plr.*, plr.id as id_statu_revision,$encabezado_ciiu b.bankCode, b.bankName, btdc.nombre_es, btdc.nombre_en,crf.descripcion_es, crf.descripcion_en FROM $tabla as listado $join    $join_revision2   $join_de_datos_generales ) AS datos $campo_filtro";

               // var_dump($consulta_select);
            }
        }else{
            if($persona == 'n'){
                $consulta_select="
                SELECT
                    *
                FROM (
                    SELECT
                        *,
                        (@row_number:=@row_number+1) AS num FROM(
                            SELECT
                                $campos_encabezado
                                plr.*,
                                plr.id as id_statu_revision,
                                $encabezado_ciiu
                                b.bankCode,
                                b.bankName,
                                btdc.nombre_es,
                                btdc.nombre_en,
                                crf.descripcion_es,
                                crf.descripcion_en,

                                ifnull(d.ciudad, '') AS 'datos_resicendia_ciudad',
                                ifnull(d.direccion, '') AS 'datos_resicendia_direccion',

                                datos_usuario_natural.nombreCompleto AS 'pn_nombreCompleto',
                                datos_usuario_natural.tipo_identificacion AS 'pn_tipo_identificacion',
                                datos_usuario_natural.numero_identificacion AS 'pn_numero_identificacion'

                                FROM $tabla as listado
                                $join
                                $left_para_data_p2w
                                JOIN (
                                    SELECT @row_number := 0) r
                                    $join_revision2
                                ) AS datos
                                WHERE datos.status_revision = '$tipo' $campo_filtro
                                order by datos.id ASC )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";
                $consultar_todos= "SELECT * FROM (SELECT $campos_encabezado plr.*, plr.id as id_statu_revision, $encabezado_ciiu b.bankCode, b.bankName, btdc.nombre_es, btdc.nombre_en,crf.descripcion_es, crf.descripcion_en FROM $tabla as listado $join  $join_revision2   )as datos  WHERE datos.status_revision = '$tipo' $campo_filtro";

              //  var_dump($consulta_select);
            }else{
                 // $consulta_select=" SELECT *  FROM (SELECT *, (@row_number:=@row_number+1) AS num FROM(SELECT listado.*, MAX(plr.id) FROM $tabla as listado JOIN (SELECT @row_number := 0) r  )as datos join datos_persona_juridica_revision as plr on (listado.id = plr.id_datos_persona) group by plr.id_datos_persona WHERE status_revision = '$tipo')AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";
                 $consulta_select="
                 SELECT
                    *
                 FROM (
                    SELECT
                        *,
                        (@row_number:=@row_number+1) AS num
                    FROM(
                        SELECT
                            $campos_encabezado
                            plr.*,
                            plr.id as id_statu_revision,
                            $encabezado_ciiu
                            b.bankCode,
                            b.bankName,
                            btdc.nombre_es,
                            btdc.nombre_en,
                            crf.descripcion_es,
                            crf.descripcion_en,

                            ifnull(d.ciudad, '') AS 'datos_resicendia_ciudad',
                            ifnull(d.direccion, '') AS 'datos_resicendia_direccion'

                            FROM $tabla as listado
                            $join
                            $left_para_data_empresa_dire
                            JOIN (SELECT @row_number := 0) r   $join_revision2  $join_de_datos_generales   )as datos  WHERE datos.status_revision = '$tipo' $campo_filtro order by datos.id ASC )AS info WHERE info.num BETWEEN '$desde' AND '$hasta';";
                 $consultar_todos= "SELECT * FROM (SELECT $campos_encabezado plr.*, plr.id as id_statu_revision, $encabezado_ciiu b.bankCode, b.bankName, btdc.nombre_es, btdc.nombre_en,crf.descripcion_es, crf.descripcion_en FROM $tabla as listado $join  $join_revision2   $join_de_datos_generales  )as datos  WHERE datos.status_revision = '$tipo' $campo_filtro";
               //  var_dump($consulta_select);
            }

        }

        if($persona == 'j'){
        //   var_dump($consulta_select); 
        }

        
        parent::conectar();
        $listado_user = parent::consultaTodo($consulta_select);
        $listado_todos= parent::consultaTodo($consultar_todos);
        parent::cerrar();


        if(count($listado_user)<=0){
            return false;
        }
        $todos_los_productos= count($listado_todos);
        if(count($listado_todos)<=0){
            return false;
        }
        $totalpaginas = $todos_los_productos/$numpagina;
        $totalpaginas = ceil($totalpaginas);


        foreach ($listado_user as $x => $usuario) {
            $select_referencia_p_natural ="SELECT * FROM buyinbig.datos_persona_referencias WHERE $campo_en_referencia = '$usuario[id_user_listado]';";
            if($persona == 'j'){
                $listado_user[$x]["activos_corrientes_mask"]=$this->maskNumber($usuario["activos_corrientes"], 2);
                $listado_user[$x]["activos_no_corrientes_mask"]=$this->maskNumber($usuario["activos_no_corrientes"], 2);
                $listado_user[$x]["ingresos_ventas_mask"]=$this->maskNumber($usuario["ingresos_ventas"], 2);
                $listado_user[$x]["pasivos_corrientes_mask"]=$this->maskNumber($usuario["pasivos_corrientes"], 2);
                $listado_user[$x]["pasivos_no_corrientes_mask"]=$this->maskNumber($usuario["pasivos_no_corrientes"], 2);
                $listado_user[$x]["costos_gastos_mask"]=$this->maskNumber($usuario["costos_gastos"], 2);
                $listado_user[$x]["patrimonio_mask"]=$this->maskNumber($usuario["patrimonio"], 2);
                $listado_user[$x]["ingresos_netos_mask"]=$this->maskNumber($usuario["ingresos_netos"], 2);

              //  $listado_user[$x]["referencias"]= $referencias_persona_natural;
            }

            $listado_user[$x]['pago_digital_proveedores'] = $this->getProveedorId( $listado_user[$x] );
            parent::conectar();
            $referencias_persona_natural = parent::consultaTodo($select_referencia_p_natural);
            parent::cerrar();
            if(count($referencias_persona_natural)>0){
                $listado_user[$x]["referencias"]= $referencias_persona_natural;
            }

        }
        //, 'campo_borrar'=> $consulta_select2

        return array("status"=>"success","data" => $listado_user,'pagina'=> $pagina,'total_paginas'=> $totalpaginas, 'campo_borrar'=> $consulta_select);
    }

    function getProveedorId(Array $data){
        $selectxproveedor =
            "SELECT * FROM buyinbig.pago_digital_proveedores WHERE UID = $data[uid_user] AND EMPRESA = $data[empresa_user];";

        parent::conectar();
        $proveedor = parent::consultaTodo($selectxproveedor);
        parent::cerrar();
        if(count($proveedor)>0){
            return $proveedor[0];
        }else{
            return null;
        }
    }


    function preparar_where_de_filtro(int $tipo, String $persona, Array $data_filtro){
        if($tipo==3){
            return "WHERE datos.".$data_filtro["campo"]."  LIKE '%".$data_filtro["texto"]."%'";
        }else{
            return "AND datos.".$data_filtro["campo"]."  LIKE '%".$data_filtro["texto"]."%'";
        }
    }

    function extraer_nombre_columna_tabla(String $nombre_tabla, Int $tipo=1){

        $consulta_name_colm=" SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$nombre_tabla'";

        // Original
        // if($tipo==1){
        //     parent::conectar();
        // }
        
        parent::addLogJB("----> [1. usuarios nasbi | isEnableMySQL]: " . parent::isEnableMySQL());
        if( !parent::isEnableMySQL() ){
            parent::conectar();
        }
        parent::addLogJB("----> [2. usuarios nasbi | isEnableMySQL]: " . parent::isEnableMySQL());

        $nombres_columnas = parent::consultaTodo($consulta_name_colm);

        
        if($tipo==1){
            parent::cerrar();
        }
        if(count($nombres_columnas)<=0){
            $nombres_columnas=false;
        }
        return $nombres_columnas;
    }


    function traer_usuarios_juridica($data){
        $consulta_select ="";
        $campos_status= ["status_nombre_comercial", "status_correo", "status_telefono", "status_pagina_web", "status_id_ciiu", "status_autorretenedor", "status_id_cod_responsabilidad_fiscal",	"status_regimen", "status_administras_recuersos_publicos","status_tiene_un_cargo_publico","status_goza_de_un_reconocimiento_publico", "status_familiar_con_caracteristicas_anteriores",	"status_descripcion_productos_a_vender", "status_id_bancos", "status_id_banco_tipo_de_cuenta",	"status_nCuenta",	"movimientos_moneda_extranjera",	"status_vende_mas_de_un_articulos",	"status_rut_doc","status_cedula_doc","status_certificado_bancario_doc"];
        $usuarios_personas_naturales= [];

        $consulta_select= "SELECT * FROM datos_persona_natural";
        parent::conectar();
        $usuarios_personas_naturales = parent::consultaTodo($consulta_select);
        parent::cerrar();

        if (count($usuarios_personas_naturales) > 0) {
            return  $usuarios_personas_naturales;
        }

    }

    function insertar_en_usuario_revision_natural(Array $data, Int $id_datos_persona){
        // $datosxpersonaxnaturalxrevision =
        // "SELECT * FROM buyinbig.datos_persona_natural_revision WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]' ORDER BY id DESC LIMIT 1;";

        // parent::conectar();
        // $datospersonanaturalrevision = parent::consultaTodo($datosxpersonaxnaturalxrevision);
        // parent::cerrar();

        // if ( count( $datospersonanaturalrevision ) > 0) {

        //     $datospersonanaturalrevision = $datospersonanaturalrevision[ 0 ];
        //     // return $datospersonanaturalrevision;
        //      $VALUES_QUERY_KEY = array();
        //      $VALUES_QUERY_CONTENT = array();
        //      $VALUES_QUERY_CONTENT_ex = array();
        //     foreach ($datospersonanaturalrevision as $key => $value) {
        //         // return $key . " -- " .  $value;

        //         if ($key != "id") {
        //             array_push( $VALUES_QUERY_KEY, $key);
        //             if( intval( "" . $value) == 1) {
        //                 array_push( $VALUES_QUERY_CONTENT, 1);
        //             }else{
        //                 if ( strpos(("" . $key), "_descripcion") ) {
        //                     $value = "''";
        //                 }
        //                 array_push( $VALUES_QUERY_CONTENT, $value);
        //             }


        //             array_push( $VALUES_QUERY_CONTENT_ex, intval( "" . $value) . " // " . strpos(("" . $key), "_descripcion") . " // " . $key);
        //         }
        //     }
        //     $insertxdatosxpersonaxnatural =
        //     "INSERT INTO datos_persona_natural_revision(" . implode(",", $VALUES_QUERY_KEY) . ")
        //     VALUES(" . implode(",", $VALUES_QUERY_CONTENT) . ")";

        //     // return array(
        //     //     'VALUES_QUERY_KEY' => $VALUES_QUERY_KEY,
        //     //     'VALUES_QUERY_CONTENT' => $VALUES_QUERY_CONTENT,
        //     //     'VALUES_QUERY_CONTENT_ex' => $VALUES_QUERY_CONTENT_ex,
        //     //     'query1' => implode(",", $VALUES_QUERY_CONTENT),
        //     //     'query2' => implode(",", $VALUES_QUERY_KEY),
        //     //     'queryFinal' => $insertxdatosxpersonaxnatural
        //     // );

        //     parent::conectar();
        //     $id_dpn = parent::queryRegistro($insertxdatosxpersonaxnatural);
        //     parent::cerrar();
        //     if (intval( $id_dpn ) <= 0) {
        //        return false;
        //     }
        //     return true;

        $status_campos_actual= $this->obtener_data_status($data, 'buyinbig.datos_persona_natural_revision');
        if($status_campos_actual!=null){
          $consulta_de_insercion_cuando_ya_esta= $this->creaccion_consulta_para_inserccion_actualizar_si_existe($data, $id_datos_persona, $status_campos_actual, 'datos_persona_natural_revision');
          parent::conectar();
          $id_dpn = parent::queryRegistro($consulta_de_insercion_cuando_ya_esta);
          parent::cerrar();
          //var_dump($consulta_de_insercion_cuando_ya_esta);
          if (intval( $id_dpn ) <= 0) {
             return false;
          }
          return true;
        }else{

            $insertxdatosxpersonaxnatural ="INSERT INTO datos_persona_natural_revision(
                user_uid,
                user_empresa,
                id_datos_persona,
                nombre_comercial_status,
                correo_status,
                telefono_status,
                pagina_web_status,
                id_ciiu_status,
                autorretenedor_status,
                id_cod_responsabilidad_fiscal_status,
                regimen_status,
                administras_recuersos_publicos_status,
                tiene_un_cargo_publico_status,
                goza_de_un_reconocimiento_publico_status,
                familiar_con_caracteristicas_anteriores_status,
                descripcion_productos_a_vender_status,
                id_bancos_status,
                id_banco_tipo_de_cuenta_status,
                nCuenta_status,
                movimientos_moneda_extranjera_status,
                vende_mas_de_un_articulos_status,
                rut_doc_status,
                cedula_doc_status,
                certificado_bancario_doc_status,
                datos_persona_referencias_1,
                datos_persona_referencias_2

            )
            VALUES(
                '$data[user_uid]',
                '$data[user_empresa]',
                '$id_datos_persona',
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0
            )";

            parent::conectar();
            $id_dpn = parent::queryRegistro($insertxdatosxpersonaxnatural);
            parent::cerrar();
            if (intval( $id_dpn ) <= 0) {
               return false;
            }
            return true;
        }

    }


    function insertar_en_usuario_revision_juridica(Array $data, Int $id_datos_persona){
        $status_campos_actual= $this->obtener_data_status($data, 'buyinbig.datos_persona_juridica_revision');
        if($status_campos_actual!=null){
          $consulta_de_insercion_cuando_ya_esta= $this->creaccion_consulta_para_inserccion_actualizar_si_existe($data, $id_datos_persona, $status_campos_actual, 'datos_persona_juridica_revision');
          parent::conectar();
          $id_dpn = parent::queryRegistro($consulta_de_insercion_cuando_ya_esta);
          parent::cerrar();
         // var_dump($consulta_de_insercion_cuando_ya_esta);

          if (intval( $id_dpn ) <= 0) {
             return false;
          }
          return true;

        }else{
            $insertxdatosxpersonax_juridica =
            "INSERT INTO buyinbig.datos_persona_juridica_revision(
                user_uid,
                user_empresa,
                id_datos_persona,
                nombre_comercial_status,
                id_cod_responsabilidad_fiscal_status,
                nombres_status,
                apellidos_status,
                id_documento_identificacion_status,
                no_identificacion_status,
                tel_fijo_status,
                celular_status,
                administras_recuersos_publicos_status,
                tiene_un_cargo_publico_status,
                goza_de_un_reconocimiento_publico_status,
                familiar_con_caracteristicas_anteriores_status,
                correo_pagos_status,
                describe_correo_pagos_status,
                id_bancos_status,
                id_banco_tipo_de_cuenta_status,
                nCuenta_status,
                activos_corrientes_status,
                activos_no_corrientes_status,
                ingresos_ventas_status,
                pasivos_corrientes_status,
                pasivos_no_corrientes_status,
                costos_gastos_status,
                patrimonio_status,
                ingresos_netos_status,
                movimientos_moneda_extranjera_status,
                camara_comercio_doc_status,
                rut_doc_status,
                contador_doc_status,
                cedula_representante_doc_status,
                certificado_bancario_doc_status,
                certificado_composicon_accionaria_doc_status,
                estados_financieros_doc_status,
                datos_persona_referencias_1,
                datos_persona_referencias_2,
                id_ciiu_status
            )
            VALUES(
                '$data[user_uid]',
                '$data[user_empresa]',
                '$id_datos_persona',
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0
            )";
           // var_dump($insertxdatosxpersonax_juridica);

            parent::conectar();
            $id_dpn = parent::queryRegistro($insertxdatosxpersonax_juridica);
            parent::cerrar();
            if (intval( $id_dpn ) <= 0) {
               return false;
            }
            return true;
        }

    }


    function creaccion_consulta_para_inserccion_actualizar_si_existe(Array $data, Int $datos_persona, Array $datospersonanaturalrevision, String $tabla){

        $VALUES_QUERY_KEY        = array();
        $VALUES_QUERY_CONTENT    = array();
        $VALUES_QUERY_CONTENT_ex = array();

        foreach ($datospersonanaturalrevision as $key => $value) {
            if ($key != "id"  ) {
                array_push( $VALUES_QUERY_KEY, $key);

                if( intval( "" . $value) == 1) {
                    array_push( $VALUES_QUERY_CONTENT, 1);

                }else{
                    if((intval( "" . $value) == 2 || intval( "" . $value) == 0 ) && !strpos(("" . $key), "_descripcion") ){

                        if( !($key == "user_uid" || $key == "user_empresa" || $key == "user_empresa" || $key == "id_datos_persona" ) ){
                            array_push( $VALUES_QUERY_CONTENT, 0);

                        }else{
                            array_push( $VALUES_QUERY_CONTENT, $value);

                        }
                    }else{
                        if ( strpos(("" . $key), "_descripcion") ) {
                            $value = "''";

                        }
                        array_push( $VALUES_QUERY_CONTENT, $value);
                    }
                }
                array_push( $VALUES_QUERY_CONTENT_ex, intval( "" . $value) . " // " . strpos(("" . $key), "_descripcion") . " // " . $key);
            }
        }
        $insertxdatosxpersonaxnatural = "INSERT INTO $tabla (" . implode(",", $VALUES_QUERY_KEY) . ") VALUES(" . implode(",", $VALUES_QUERY_CONTENT) . ")";

        return $insertxdatosxpersonaxnatural;
    }

    function insercion_revision_natural_cambio($data){

        $campos_faltantes         = [];
        $nombres_natural_revision =  $this->extraer_nombre_columna_tabla('datos_persona_natural_revision');

        $campos_no_a_validar      = [
            'id_datos_persona',
            'id',
            'nombre_comercial_descripcion',
            'correo_descripcion',
            'telefono_descripcion',
            'pagina_web_descripcion',
            'id_ciiu_descripcion',
            'autorretenedor_descripcion',
            'id_cod_responsabilidad_fiscal_descripcion',
            'regimen_descripcion',
            'administras_recuersos_publicos_descripcion',
            'tiene_un_cargo_publico_descripcion',
            'goza_de_un_reconocimiento_publico_descripcion',
            'familiar_con_caracteristicas_anteriores_descripcion',
            'descripcion_productos_a_vender_descripcion',
            'id_bancos_descripcion',
            'id_banco_tipo_de_cuenta_descripcion',
            'nCuenta_descripcion',
            'movimientos_moneda_extranjera_descripcion',
            'vende_mas_de_un_articulos_descripcion',
            'rut_doc_descripcion',
            'cedula_doc_descripcion',
            'certificado_bancario_doc_descripcion',
            'datos_persona_referencias_1_descripcion',
            'datos_persona_referencias_2_descripcion'
        ];

        $campos_descripcion       = [
            'nombre_comercial_descripcion',
            'correo_descripcion',
            'telefono_descripcion',
            'pagina_web_descripcion',
            'id_ciiu_descripcion',
            'autorretenedor_descripcion',
            'id_cod_responsabilidad_fiscal_descripcion',
            'regimen_descripcion',
            'administras_recuersos_publicos_descripcion',
            'tiene_un_cargo_publico_descripcion',
            'goza_de_un_reconocimiento_publico_descripcion',
            'familiar_con_caracteristicas_anteriores_descripcion',
            'descripcion_productos_a_vender_descripcion',
            'id_bancos_descripcion',
            'id_banco_tipo_de_cuenta_descripcion',
            'nCuenta_descripcion',
            'movimientos_moneda_extranjera_descripcion',
            'vende_mas_de_un_articulos_descripcion',
            'rut_doc_descripcion',
            'cedula_doc_descripcion',
            'certificado_bancario_doc_descripcion',
            'datos_persona_referencias_1_descripcion',
            'datos_persona_referencias_2_descripcion'
        ];
         
        $campos_status            = [
            'nombre_comercial_status',
            'correo_status',
            'telefono_status',
            'pagina_web_status',
            'id_ciiu_status',
            'autorretenedor_status',
            'id_cod_responsabilidad_fiscal_status',
            'regimen_status',
            'administras_recuersos_publicos_status',
            'tiene_un_cargo_publico_status',
            'goza_de_un_reconocimiento_publico_status',
            'familiar_con_caracteristicas_anteriores_status',
            'descripcion_productos_a_vender_status',
            'id_bancos_status',
            'id_banco_tipo_de_cuenta_status',
            'nCuenta_status',
            'movimientos_moneda_extranjera_status',
            'vende_mas_de_un_articulos_status',
            'rut_doc_status',
            'cedula_doc_status',
            'certificado_bancario_doc_status',
            'datos_persona_referencias_1',
            'datos_persona_referencias_2'
        ];

        $bandera_campo_faltante   = 0;
        $bandera_rechazado        = 0;
        $bandera_en_cero          = 0;
        $campos_rechazados        = [];
        array_push($nombres_natural_revision, array("COLUMN_NAME" => "id_user_listado"));

        foreach ($nombres_natural_revision as $x => $campo) {
            $tipo = in_array($campo["COLUMN_NAME"], $campos_no_a_validar);
            if(!$tipo){
                if(!isset($data[$campo["COLUMN_NAME"]])){
                    $bandera_campo_faltante=1;
                    array_push(
                        $campos_faltantes,
                        array(
                            "mensaje" => "falta el campo"." ".$campo["COLUMN_NAME"],
                            "campo"   => $campo["COLUMN_NAME"]
                        )
                    );
                }
            }
        }
        if($bandera_campo_faltante == 1){
            return array("status" => "fail", "mensagge" => "faltan parametros", "mensaje_campos_faltante" => $campos_faltantes);
        }

        $repuesta = $this->insertar_en_usuario_revision_natural_cambio(
            $data,
            intval($data["id_user_listado"]),
            $campos_descripcion,
            $nombres_natural_revision
        );
        if(!$repuesta) return array("status" => "errorRevision", "mensagge" => "Error al insertar en tabla de revision");

        foreach ( $campos_status as $x => $campo) {
            if(intval($data[$campo])==2 ){
                $bandera_rechazado= 1;
                array_push($campos_rechazados, array("campo" => $campo, "descripcion" => $data[$campos_descripcion[$x]]));
            }

            if(intval($data[$campo])==0 ){
                $bandera_en_cero= 1;
            }
        }

        if($bandera_rechazado == 1 || $bandera_en_cero == 1 ){
            if($bandera_rechazado == 1){
                $this->actualizar_status_general_tabla_datos('datos_persona_natural',2, $data["id_user_listado"]);
                $this->envio_correo_rechazado_natural($data, $campos_rechazados);
            }else{
                //este no deberia pasar ni aqui ni en natural porque el revisor tiene que llenar todos los campos para terminar de revisar
                $this->actualizar_status_general_tabla_datos('datos_persona_natural',0, $data["id_user_listado"]);
            }
        }else {
            //todos estan en uno
            $this->actualizar_status_general_tabla_datos('datos_persona_natural', 1, $data["id_user_listado"]);
            $this->envio_correo_exitoso_aprobado_campos_exitoso($data);
        }

        return array("status"=> "success", "mensagge"=>"agregado nueva revision");
    }


    function actualizar_status_general_tabla_datos(String $tabla, Int $tipo, String $id){
        
        $updatempresa_juridica = "UPDATE $tabla SET status_revision = '$tipo' WHERE (id = $id );";

        parent::conectar();
        $result= parent::query($updatempresa_juridica);
        parent::cerrar();

        // return "bien";
        return $result;
    }



    function insercion_revision_juridica_cambio($data){
        $campos_faltantes=[];
        $nombres_juridica_revision =  $this->extraer_nombre_columna_tabla('datos_persona_juridica_revision');
        $campos_no_a_validar= ['id_datos_persona','id','nombre_comercial_descripcion','id_cod_responsabilidad_fiscal_descripcion', 'nombres_descripcion', 'apellidos_descripcion', 'id_documento_identificacion_descripcion', 'no_identificacion_descripcion', 'tel_fijo_descripcion', 'celular_descripcion', 'administras_recuersos_publicos_descripcion', 'tiene_un_cargo_publico_descripcion', 'goza_de_un_reconocimiento_publico_descripcion', 'familiar_con_caracteristicas_anteriores_descripcion', 'correo_pagos_descripcion', 'describe_correo_pagos_descripcion', 'id_bancos_descripcion', 'id_banco_tipo_de_cuenta_descripcion', 'nCuenta_descripcion', 'activos_corrientes_descripcion', 'activos_corrientes_descripcion', 'activos_no_corrientes_descripcion', 'ingresos_ventas_descripcion', 'pasivos_corrientes_descripcion', 'pasivos_no_corrientes_descripcion', 'costos_gastos_descripcion', 'patrimonio_descripcion', 'ingresos_netos_descripcion', 'movimientos_moneda_extranjera_descripcion', 'camara_comercio_doc_descripcion', 'rut_doc_descripcion', 'contador_doc_descripcion', 'cedula_representante_doc_descripcion', 'certificado_bancario_doc_descripcion', 'certificado_composicon_accionaria_doc_descripcion', 'estados_financieros_doc_descripcion', 'datos_persona_referencias_1_descripcion', 'datos_persona_referencias_2_descripcion'];
        $campos_descripcion= ['nombre_comercial_descripcion','id_cod_responsabilidad_fiscal_descripcion', 'nombres_descripcion', 'apellidos_descripcion', 'id_documento_identificacion_descripcion', 'no_identificacion_descripcion', 'tel_fijo_descripcion', 'celular_descripcion', 'administras_recuersos_publicos_descripcion', 'tiene_un_cargo_publico_descripcion', 'goza_de_un_reconocimiento_publico_descripcion', 'familiar_con_caracteristicas_anteriores_descripcion', 'correo_pagos_descripcion', 'describe_correo_pagos_descripcion', 'id_bancos_descripcion', 'id_banco_tipo_de_cuenta_descripcion', 'nCuenta_descripcion', 'activos_corrientes_descripcion', 'activos_no_corrientes_descripcion', 'ingresos_ventas_descripcion', 'pasivos_corrientes_descripcion', 'pasivos_no_corrientes_descripcion', 'costos_gastos_descripcion', 'patrimonio_descripcion', 'ingresos_netos_descripcion', 'movimientos_moneda_extranjera_descripcion', 'camara_comercio_doc_descripcion', 'rut_doc_descripcion', 'contador_doc_descripcion', 'cedula_representante_doc_descripcion', 'certificado_bancario_doc_descripcion', 'certificado_composicon_accionaria_doc_descripcion', 'estados_financieros_doc_descripcion', 'datos_persona_referencias_1_descripcion', 'datos_persona_referencias_2_descripcion'];
        $campos_status=  ['nombre_comercial_status', 'id_cod_responsabilidad_fiscal_status', 'nombres_status', 'apellidos_status', 'id_documento_identificacion_status', 'no_identificacion_status', 'tel_fijo_status', 'celular_status', 'administras_recuersos_publicos_status', 'tiene_un_cargo_publico_status', 'goza_de_un_reconocimiento_publico_status', 'familiar_con_caracteristicas_anteriores_status', 'correo_pagos_status', 'describe_correo_pagos_status', 'id_bancos_status', 'id_banco_tipo_de_cuenta_status', 'nCuenta_status', 'activos_corrientes_status', 'activos_no_corrientes_status', 'ingresos_ventas_status', 'pasivos_corrientes_status', 'pasivos_no_corrientes_status', 'costos_gastos_status', 'patrimonio_status', 'ingresos_netos_status', 'movimientos_moneda_extranjera_status', 'camara_comercio_doc_status', 'rut_doc_status', 'contador_doc_status', 'cedula_representante_doc_status', 'certificado_bancario_doc_status', 'certificado_composicon_accionaria_doc_status', 'estados_financieros_doc_status', 'datos_persona_referencias_1', 'datos_persona_referencias_2'];
        $bandera_campo_faltante= 0;
        $bandera_rechazado= 0;
        $bandera_en_cero= 0;
        $campos_rechazados= [];
        array_push($nombres_juridica_revision,array("COLUMN_NAME"=> "id_user_listado"));
        foreach ($nombres_juridica_revision as $x => $campo) {
           $tipo = in_array($campo["COLUMN_NAME"], $campos_no_a_validar);
           if(!$tipo){

               if(!isset($data[$campo["COLUMN_NAME"]])){
                   $bandera_campo_faltante=1;
                   array_push($campos_faltantes,array("mensaje"=> "falta el campo"." ".$campo["COLUMN_NAME"], "campo"=>$campo["COLUMN_NAME"]));
               }
           }
        }
        if($bandera_campo_faltante==1){
           return array("status"=>"fail", "mensagge"=>"faltan parametros", "mensaje_campos_faltante"=> $campos_faltantes);
        }

        $repuesta = $this->insertar_en_usuario_revision_juridica_cambio($data, intval($data["id_user_listado"]), $campos_descripcion, $nombres_juridica_revision);
        if(!$repuesta) return array("status"=> "errorRevision", "mensagge"=>"Error al insertar en tabla de revision");

        foreach ( $campos_status as $x => $campo) {
            if(intval($data[$campo])==2 ){
               $bandera_rechazado= 1;
              // array_push($campos_rechazados, $campo);
              array_push($campos_rechazados, array("campo"=>$campo, "descripcion"=>$data[$campos_descripcion[$x]]));
            }

            if(intval($data[$campo])==0 ){
               $bandera_en_cero= 1;
            }
        }

        if($bandera_rechazado == 1 || $bandera_en_cero == 1 ){ //con que alguno sea 0 o 2 el estatus general del usuario no puede ser 1
            if($bandera_rechazado == 1){
               $this->actualizar_status_general_tabla_datos('datos_persona_juridica',2, $data["id_user_listado"]);
               $this->envio_correo_rechazado_natural($data, $campos_rechazados);
            }else{
               $this->actualizar_status_general_tabla_datos('datos_persona_juridica',0, $data["id_user_listado"]);
            }
        }else {
            //todos estan en uno
            $this->actualizar_status_general_tabla_datos('datos_persona_juridica', 1, $data["id_user_listado"]);
            $this->envio_correo_exitoso_aprobado_campos_exitoso($data);
        }

        return array("status"=> "success", "mensagge"=>"agregado nueva revision");
    }

    function insertar_en_usuario_revision_juridica_cambio(Array $data, Int $id_datos_persona, Array $campos_descripcion, Array $nombres_juridica_revision){

        //INICIO SLASHES
        foreach ($nombres_juridica_revision as $x => $campo) {
            $existe_campo = in_array($campo["COLUMN_NAME"], $campos_descripcion);
              
            if($existe_campo){
                $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]); 
            }
        }
        //FIN SLASHES

        $insertxdatosxpersonax_juridica =
        "INSERT INTO buyinbig.datos_persona_juridica_revision(
            user_uid,
            user_empresa,
            id_datos_persona,
            nombre_comercial_status,
            id_cod_responsabilidad_fiscal_status,
            nombres_status, apellidos_status,
            id_documento_identificacion_status,
            no_identificacion_status,
            tel_fijo_status,
            celular_status,
            administras_recuersos_publicos_status,
            tiene_un_cargo_publico_status,
            goza_de_un_reconocimiento_publico_status,
            familiar_con_caracteristicas_anteriores_status,
            correo_pagos_status,
            describe_correo_pagos_status,
            id_bancos_status,
            id_banco_tipo_de_cuenta_status,
            nCuenta_status,
            activos_corrientes_status,
            activos_no_corrientes_status,
            ingresos_ventas_status,
            pasivos_corrientes_status,
            pasivos_no_corrientes_status,
            costos_gastos_status,
            patrimonio_status,
            ingresos_netos_status,
            movimientos_moneda_extranjera_status,
            camara_comercio_doc_status,
            rut_doc_status,
            contador_doc_status,
            cedula_representante_doc_status,
            certificado_bancario_doc_status,
            certificado_composicon_accionaria_doc_status,
            estados_financieros_doc_status,
            datos_persona_referencias_1,
            datos_persona_referencias_2,
            nombre_comercial_descripcion,
            id_cod_responsabilidad_fiscal_descripcion,
            nombres_descripcion,
            apellidos_descripcion,
            id_documento_identificacion_descripcion,
            no_identificacion_descripcion,
            tel_fijo_descripcion,
            celular_descripcion,
            administras_recuersos_publicos_descripcion,
            tiene_un_cargo_publico_descripcion,
            goza_de_un_reconocimiento_publico_descripcion,
            familiar_con_caracteristicas_anteriores_descripcion,
            correo_pagos_descripcion,
            describe_correo_pagos_descripcion,
            id_bancos_descripcion,
            id_banco_tipo_de_cuenta_descripcion,
            nCuenta_descripcion,
            activos_corrientes_descripcion,
            activos_no_corrientes_descripcion,
            ingresos_ventas_descripcion,
            pasivos_corrientes_descripcion,
            pasivos_no_corrientes_descripcion,
            costos_gastos_descripcion,
            patrimonio_descripcion,
            ingresos_netos_descripcion,
            movimientos_moneda_extranjera_descripcion,
            camara_comercio_doc_descripcion,
            rut_doc_descripcion,
            contador_doc_descripcion,
            cedula_representante_doc_descripcion,
            certificado_bancario_doc_descripcion,
            certificado_composicon_accionaria_doc_descripcion,
            estados_financieros_doc_descripcion,
            datos_persona_referencias_1_descripcion,
            datos_persona_referencias_2_descripcion
       )
        VALUES(
            '$data[user_uid]',
            '$data[user_empresa]',
            '$id_datos_persona',
            '$data[nombre_comercial_status]',
            '$data[id_cod_responsabilidad_fiscal_status]',
            '$data[nombres_status]',
            '$data[apellidos_status]',
            '$data[id_documento_identificacion_status]',
            '$data[no_identificacion_status]',
            '$data[tel_fijo_status]',
            '$data[celular_status]',
            '$data[administras_recuersos_publicos_status]',
            '$data[tiene_un_cargo_publico_status]',
            '$data[goza_de_un_reconocimiento_publico_status]',
            '$data[familiar_con_caracteristicas_anteriores_status]',
            '$data[correo_pagos_status]',
            '$data[describe_correo_pagos_status]',
            '$data[id_bancos_status]',
            '$data[id_banco_tipo_de_cuenta_status]',
            '$data[nCuenta_status]',
            '$data[activos_corrientes_status]',
            '$data[activos_no_corrientes_status]',
            '$data[ingresos_ventas_status]',
            '$data[pasivos_corrientes_status]',
            '$data[pasivos_no_corrientes_status]',
            '$data[costos_gastos_status]',
            '$data[patrimonio_status]',
            '$data[ingresos_netos_status]',
            '$data[movimientos_moneda_extranjera_status]',
            '$data[camara_comercio_doc_status]',
            '$data[rut_doc_status]',
            '$data[contador_doc_status]',
            '$data[cedula_representante_doc_status]',
            '$data[certificado_bancario_doc_status]',
            '$data[certificado_composicon_accionaria_doc_status]',
            '$data[estados_financieros_doc_status]',
            '$data[datos_persona_referencias_1]',
            '$data[datos_persona_referencias_2]',
            '$data[nombre_comercial_descripcion]',
            '$data[id_cod_responsabilidad_fiscal_descripcion]',
            '$data[nombres_descripcion]',
            '$data[apellidos_descripcion]',
            '$data[id_documento_identificacion_descripcion]',
            '$data[no_identificacion_descripcion]',
            '$data[tel_fijo_descripcion]',
            '$data[celular_descripcion]',
            '$data[administras_recuersos_publicos_descripcion]',
            '$data[tiene_un_cargo_publico_descripcion]',
            '$data[goza_de_un_reconocimiento_publico_descripcion]',
            '$data[familiar_con_caracteristicas_anteriores_descripcion]',
            '$data[correo_pagos_descripcion]',
            '$data[describe_correo_pagos_descripcion]',
            '$data[id_bancos_descripcion]',
            '$data[id_banco_tipo_de_cuenta_descripcion]',
            '$data[nCuenta_descripcion]',
            '$data[activos_corrientes_descripcion]',
            '$data[activos_no_corrientes_descripcion]',
            '$data[ingresos_ventas_descripcion]',
            '$data[pasivos_corrientes_descripcion]',
            '$data[pasivos_no_corrientes_descripcion]',
            '$data[costos_gastos_descripcion]',
            '$data[patrimonio_descripcion]',
            '$data[ingresos_netos_descripcion]',
            '$data[movimientos_moneda_extranjera_descripcion]',
            '$data[camara_comercio_doc_descripcion]',
            '$data[rut_doc_descripcion]',
            '$data[contador_doc_descripcion]',
            '$data[cedula_representante_doc_descripcion]',
            '$data[certificado_bancario_doc_descripcion]',
            '$data[certificado_composicon_accionaria_doc_descripcion]',
            '$data[estados_financieros_doc_descripcion]',
            '$data[datos_persona_referencias_1_descripcion]',
            '$data[datos_persona_referencias_2_descripcion]'
        )";
       // var_dump($insertxdatosxpersonax_juridica);

        parent::conectar();
        $id_dpn = parent::queryRegistro($insertxdatosxpersonax_juridica);
        parent::cerrar();
        if (intval( $id_dpn ) <= 0) {
           return false;
        }
        return true;
    }


    function insertar_en_usuario_revision_natural_cambio(Array $data, Int $id_datos_persona, Array $campos_descripcion, Array $nombres_natural_revision){
        
        //INICIO SLASHES
        foreach ($nombres_natural_revision as $x => $campo) {
            $existe_campo = in_array($campo["COLUMN_NAME"], $campos_descripcion);
              
            if($existe_campo){
                $data[$campo["COLUMN_NAME"]] = addslashes($data[$campo["COLUMN_NAME"]]); 
            }
        }
        //FIN SLASHES

        $insertxdatosxpersonaxnatural ="INSERT INTO datos_persona_natural_revision(
            user_uid,
            user_empresa,
            id_datos_persona,
            nombre_comercial_status,
            correo_status,
            telefono_status,
            pagina_web_status,
            id_ciiu_status,
            autorretenedor_status,
            id_cod_responsabilidad_fiscal_status,
            regimen_status,
            administras_recuersos_publicos_status,
            tiene_un_cargo_publico_status,
            goza_de_un_reconocimiento_publico_status,
            familiar_con_caracteristicas_anteriores_status,
            descripcion_productos_a_vender_status,
            id_bancos_status,
            id_banco_tipo_de_cuenta_status,
            nCuenta_status,
            movimientos_moneda_extranjera_status,
            vende_mas_de_un_articulos_status,
            rut_doc_status,
            cedula_doc_status,
            certificado_bancario_doc_status,
            datos_persona_referencias_1,
            datos_persona_referencias_2,
            nombre_comercial_descripcion,
            correo_descripcion,
            telefono_descripcion,
            pagina_web_descripcion,
            id_ciiu_descripcion,
            autorretenedor_descripcion,
            id_cod_responsabilidad_fiscal_descripcion,
            regimen_descripcion,
            administras_recuersos_publicos_descripcion,
            tiene_un_cargo_publico_descripcion,
            goza_de_un_reconocimiento_publico_descripcion,
            familiar_con_caracteristicas_anteriores_descripcion,
            descripcion_productos_a_vender_descripcion,
            id_bancos_descripcion,
            id_banco_tipo_de_cuenta_descripcion,
            nCuenta_descripcion,
            movimientos_moneda_extranjera_descripcion,
            vende_mas_de_un_articulos_descripcion,
            rut_doc_descripcion,
            cedula_doc_descripcion,
            certificado_bancario_doc_descripcion,
            datos_persona_referencias_1_descripcion,
            datos_persona_referencias_2_descripcion
        )
        VALUES(
            '$data[user_uid]',
            '$data[user_empresa]',
            '$id_datos_persona',
            '$data[nombre_comercial_status]',
            '$data[correo_status]',
            '$data[telefono_status]',
            '$data[pagina_web_status]',
            '$data[id_ciiu_status]',
            '$data[autorretenedor_status]',
            '$data[id_cod_responsabilidad_fiscal_status]',
            '$data[regimen_status]',
            '$data[administras_recuersos_publicos_status]',
            '$data[tiene_un_cargo_publico_status]',
            '$data[goza_de_un_reconocimiento_publico_status]',
            '$data[familiar_con_caracteristicas_anteriores_status]',
            '$data[descripcion_productos_a_vender_status]',
            '$data[id_bancos_status]',
            '$data[id_banco_tipo_de_cuenta_status]',
            '$data[nCuenta_status]',
            '$data[movimientos_moneda_extranjera_status]',
            '$data[vende_mas_de_un_articulos_status]',
            '$data[rut_doc_status]',
            '$data[cedula_doc_status]',
            '$data[certificado_bancario_doc_status]',
            '$data[datos_persona_referencias_1]',
            '$data[datos_persona_referencias_2]',
            '$data[nombre_comercial_descripcion]',
            '$data[correo_descripcion]',
            '$data[telefono_descripcion]',
            '$data[pagina_web_descripcion]',
            '$data[id_ciiu_descripcion]',
            '$data[autorretenedor_descripcion]',
            '$data[id_cod_responsabilidad_fiscal_descripcion]',
            '$data[regimen_descripcion]',
            '$data[administras_recuersos_publicos_descripcion]',
            '$data[tiene_un_cargo_publico_descripcion]',
            '$data[goza_de_un_reconocimiento_publico_descripcion]',
            '$data[familiar_con_caracteristicas_anteriores_descripcion]',
            '$data[descripcion_productos_a_vender_descripcion]',
            '$data[id_bancos_descripcion]',
            '$data[id_banco_tipo_de_cuenta_descripcion]',
            '$data[nCuenta_descripcion]',
            '$data[movimientos_moneda_extranjera_descripcion]',
            '$data[vende_mas_de_un_articulos_descripcion]',
            '$data[rut_doc_descripcion]',
            '$data[cedula_doc_descripcion]',
            '$data[certificado_bancario_doc_descripcion]',
            '$data[datos_persona_referencias_1_descripcion]',
            '$data[datos_persona_referencias_2_descripcion]'
        )";
        //var_dump($insertxdatosxpersonaxnatural);
        parent::conectar();
        $id_dpn = parent::queryRegistro($insertxdatosxpersonaxnatural);
        parent::cerrar();
        if (intval( $id_dpn ) <= 0) {
           return false;
        }
        return true;
    }

    function envio_correo_exitoso_aprobado_campos_exitoso(Array $data){
        $data_user= "";
        if(intval($data["user_empresa"])==1){
            $data_user=$this->obtener_data_empresa([
                "uid"=> $data['user_uid'],
                "empresa"=> $data["user_empresa"]
            ]);
        }else if(intval($data["user_empresa"])==0){
            $data_user = $this->datosUser([
                'uid' => $data['user_uid'],
                'empresa' => intval($data["user_empresa"])
            ]);
        }
        if(isset($data_user["data"])){
            $this->html_envio_correo_exitoso_revision($data_user["data"]);
        }
    }


    function html_envio_correo_exitoso_revision(Array $data_user){
        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/correos_registro_vender_revision/revision_exitosa.html");


        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans186_}}", $json->trans186_, $html);
        $html = str_replace("{{trans187_}}", $json->trans187_, $html);
        $html = str_replace("{{trans188_}}", $json->trans188_, $html);
        $html = str_replace("{{trans189_}}", $json->trans189_, $html);
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        $html = str_replace("{{link_tuto_pago}}", $json->link_tuto_pago, $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans194_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);


        return $response;


    }


    function envio_correo_rechazado_natural(Array $data, Array $campos_rechazado){
        $data_user= "";
        if(intval($data["user_empresa"])==1){
            $data_user=$this->obtener_data_empresa([
                "uid"=> $data['user_uid'],
                "empresa"=> $data["user_empresa"]
            ]);
        }else if(intval($data["user_empresa"])==0){
            $data_user = $this->datosUser([
                'uid' => $data['user_uid'],
                'empresa' => intval($data["user_empresa"])
            ]);
        }

        if(isset($data_user["data"])){
          $this->html_correo_rechazo_registro_vender($data_user["data"], $campos_rechazado);
        }


    }


    function html_correo_rechazo_registro_vender(Array $data_user, Array $campos_rechazado){
    
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/correos_registro_vender_revision/correoNoexitosoregistro.html");

        $html_campos_rechazados= "";

        foreach ( $campos_rechazado as $x => $campo) {
            if(isset($json->{$campo["campo"]})){
                $html_campos_rechazados.= '<br> '.($x+1).' '.$json->{$campo["campo"]}.', Justificación de rechazo: '.$campo["descripcion"].'<br>';
            }else{
                $html_campos_rechazados.= '<br> '.($x+1).' '.$campo["campo"].', Justificación de rechazo: '.$campo["descripcion"].'<br>';
            }


        }
        $html = str_replace("{{trans172_}}", $json->trans172_ , $html);
        $html = str_replace("{{trans173_}}", $json->trans173_ , $html);
        $html = str_replace("{{trans174_}}", $json->trans174_ , $html);
        $html = str_replace("{{trans175_}}", $json->trans175_ , $html);
        $html = str_replace("{{trans176_}}", $json->trans176_ , $html);
        $html = str_replace("{{trans177_}}", $json->trans177_ , $html);
        $html = str_replace("{{trans178_}}", $json->trans178_ , $html);
        $html = str_replace("{{trans179_}}", $json->trans179_ , $html);
        $html = str_replace("{{trans180_}}", $json->trans180_ , $html);
        $html = str_replace("{{trans181_}}", $json->trans181_ , $html);
        $html = str_replace("{{trans182_}}", $json->trans182_ , $html);
        $html = str_replace("{{trans183_}}", $json->trans183_ , $html);
        $html = str_replace("{{trans184_}}", $json->trans184_ , $html);

        $html = str_replace("{{nombre_user}}", $data_user["nombre"] , $html);
        $html = str_replace("{{link_whatsapp}}", $json->link_whatsapp  , $html);

        $html = str_replace("{{items_rechazados}}", $html_campos_rechazados , $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);


        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans185_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";


        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);



        return $response;

    }


    function cargar_pdf_juridica(Array $data){// en la data debe venir el base64, el tipo de pdf a cargar
        $respuesta_existencia_user= $this->saber_si_el_user_esta_registrado($data);
        if($respuesta_existencia_user!=false){

            $respuesta= $this->actualizar_para_pdf($data, $respuesta_existencia_user["data"]);
            if($respuesta){
                return array("status"=> "success", "mensagge"=> "insertado pdf en juridica");
            }else{
                // $actualizar_para_pdf
                return array("status"=> "fail", "mensagge"=> "error insertar pdf en juridica");
            }

        }else{
            return array("status"=> "fail", "mensagge"=> "usuario no existe");
            // $respuesta= $this->insertar_para_pdf($data);
            // if($respuesta){
            //     return array("status"=> "success", "mensagge"=> "insertado pdf en juridica");
            // }else{
            //     return array("status"=> "fail", "mensagge"=> "error insertar pdf en juridica");
            // }
        }

    }

    function actualizar_para_pdf(Array $data, $data_user_tabla,String $tabla="datos_persona_juridica"){
        //persona juridica
        //Tipos documento: camara_comercio_doc, rut_doc, contgador_doc, cedula_representante_doc, certificado_bancario_doc, certificado_composicon_accionaria_doc, estados_financieros_doc
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/$tabla/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);
        $data[$data["key"]]= $data["documento"];

        $campo_pdf                      = $data["documento"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $campo_pdf) ) == 1) {
            $campo_pdf                  = $this->subirPdf($data, $fecha, $data["key"], 2);
        }

        $updatexdatosxpersonax_juridica =
        "UPDATE $tabla
        SET
            $data[key]                        = '$campo_pdf'


        WHERE (id = '$data_user_tabla[id]');";
        parent::conectar();
        parent::query($updatexdatosxpersonax_juridica);
        parent::cerrar();

        $consulta_campos =$this->saber_si_el_user_esta_registrado($data);

        if($consulta_campos!=false){
           if(!empty($consulta_campos["data"][$data["key"]])){
               return array("status"=>"success", "mensagge"=>"bien");
           }
           return array("status"=>"fail", "mensagge"=>"error 1 ");
        }
        return array("status"=>"fail", "mensagge"=>"error 2 ");

    }

    function insertar_para_pdf(Array $data, String $tabla="datos_persona_juridica"){
        //persona juridica
        //Tipos documento: camara_comercio_doc, rut_doc, contador_doc, cedula_representante_doc, certificado_bancario_doc, certificado_composicon_accionaria_doc, estados_financieros_doc
        $data[$data["key"]]= $data["documento"];

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);


        $documento_pdf      = $this->subirPdf($data, $fecha, $data["key"], 2);


        $insertxdatosxpersonax_juridica =
        "INSERT INTO $tabla(
            user_uid,
            user_empresa,
            $data[key]

        )
        VALUES(
            '$data[user_uid]',
            '$data[user_empresa]',
            '$documento_pdf'

        )";
       // var_dump($insertxdatosxpersonax_juridica);

        parent::conectar();
        $id_dpn = parent::queryRegistro($insertxdatosxpersonax_juridica);
        parent::cerrar();
        if (intval( $id_dpn ) <= 0) {
           return false;
        }
        return true;
    }


    function saber_si_el_user_esta_registrado($data, $tabla="datos_persona_juridica"){
        parent::conectar();
        $selectxdatosxpersonaxnatural = "SELECT * FROM $tabla WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        if ( count($datos_persona_natural) > 0) {
            parent::cerrar();
            return array("status"=>"success", "data"=>$datos_persona_natural[0]);
        }else{
            parent::cerrar();
            return false;
        }

    }

    function correo_de_promocion_pass_transaccional(Array $data){
        if( !isset($data['uid']) || !isset($data['empresa']) ) return array("status"=> "fail", "mensage"=> "falta parametro");
        
        if( intval($data["empresa"]) == 1 ){
            $data_user=$this->obtener_data_empresa([
                "uid"=> $data['uid'],
                "empresa"=> $data["empresa"]
            ]);
        }else if( intval($data["empresa"]) ==0 ){
            $data_user = $this->datosUser([
                'uid' => $data['uid'],
                'empresa' => intval($data["empresa"])
            ]);
        }

        if(isset($data_user["data"])){
            $respuesta = json_decode($this->html_pass_transaccional_correo_promo($data_user["data"]));
            if(isset($respuesta->status) ){
                if($respuesta->status == "success"){
                    return(array("status" => "success", "message" => "se envio correo transaccional"));
                }else{
                    return(array("status" => "fail", "message" => "no se envio correo transaccional"));
                }
            }else{
                return(array("status" => "fail", "message" => "no se envio correo transaccional"));
            }
        }
    }

    function html_pass_transaccional_correo_promo(Array $data_user){

        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico
            $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
            $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/promo_correo/correopromopasstrans.html");

            $valor= "5.000";

            $html = str_replace("{{trans190_}}", $json->trans190_, $html);
            $html = str_replace("{{trans191_}}", $json->trans191_, $html);
            $html = str_replace("{{trans192_}}", $json->trans192_, $html);
            $html = str_replace("{{valor}}", $valor, $html);
            $html = str_replace("{{codigoreferido}}", $data_user["uid"], $html);
            $html = str_replace("{{link_to_wallet}}", $json->link_to_wallet, $html);
            $html = str_replace("{{trans195_}}", $json->trans195_, $html);
            $html = str_replace("{{trans196_}}", $json->trans196_, $html);
            $html = str_replace("{{trans197_}}", $json->trans197_, $html);
            $html = str_replace("{{trans198_}}", $json->trans198_, $html);

            $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
            $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html);
            $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
            $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
            $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
            $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
            $html = str_replace("{{trans06_}}",$json->trans06_, $html);
            $html = str_replace("{{trans07_}}",$json->trans07_, $html);



            $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
            $mensaje1   = $html;
            $titulo    = $json->trans193_;
            $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
            $cabeceras .= 'From: info@nasbi.com' . "\r\n";
            //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
            $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }


    function obtener_data_status(Array $data, String $tabla){
        $selectxrevisionxjuridica =
        "SELECT *
        FROM $tabla
        WHERE user_uid = $data[user_uid] AND user_empresa = $data[user_empresa]
        ORDER BY id DESC LIMIT 1;";
        parent::conectar();
        $selectxrevisionxjuridica = parent::consultaTodo($selectxrevisionxjuridica);
        parent::cerrar();
        if( count($selectxrevisionxjuridica) <= 0 ){
            return   $selectxrevisionxjuridica = null;
        }else{
            return   $selectxrevisionxjuridica = $selectxrevisionxjuridica[0];
        }
    }

    function getEstadisticas(Array $data){
        $selectxestadistica =
            "SELECT 
                'APROBADOS',
                COUNT(p.id) AS 'PUBLICACIONES',
                SUM(( p.cantidad - p.cantidad_vendidas )) AS 'UNIDADES',
                SUM(( p.cantidad - p.cantidad_vendidas ) * (p.precio - ( p.precio * (p.porcentaje_oferta/100) ))) AS 'PRECIO'
                
                
            FROM buyinbig.productos p
            INNER JOIN buyinbig.pago_digital_proveedores pdp ON(p.uid = pdp.UID AND p.empresa = pdp.EMPRESA)
            WHERE (p.uid = pdp.UID AND p.empresa = pdp.EMPRESA) AND p.estado = 1

            UNION

            SELECT 
                'RECHAZADOS',
                COUNT(p.id) AS 'PUBLICACIONES',
                SUM(( p.cantidad - p.cantidad_vendidas )) AS 'UNIDADES',
                SUM(( p.cantidad - p.cantidad_vendidas ) * (p.precio - ( p.precio * (p.porcentaje_oferta/100) ))) AS 'PRECIO'
                
            FROM buyinbig.productos_revision p
            INNER JOIN buyinbig.pago_digital_proveedores pdp ON(p.uid = pdp.UID AND p.empresa = pdp.EMPRESA)
            WHERE (p.uid = pdp.UID AND p.empresa = pdp.EMPRESA) AND p.id_productos_revision_estados = 2

            UNION

            SELECT 
                'INHABILITADOS',
                COUNT(p.id) AS 'PUBLICACIONES',
                SUM(( p.cantidad - p.cantidad_vendidas )) AS 'UNIDADES',
                SUM(( p.cantidad - p.cantidad_vendidas ) * (p.precio - ( p.precio * (p.porcentaje_oferta/100) ))) AS 'PRECIO'
                
            FROM buyinbig.productos p
            INNER JOIN buyinbig.pago_digital_proveedores pdp ON(p.uid = pdp.UID AND p.empresa = pdp.EMPRESA)
            WHERE (p.uid = pdp.UID AND p.empresa = pdp.EMPRESA) AND p.estado != 1";
        
        parent::conectar();
        $estadistica = parent::consultaTodo( $selectxestadistica );
        parent::cerrar();

        if( count($estadistica) == 0 ){
            return Array(
                'status'  => 'fail',
                'message' => 'No hay registros.',
                'data'    => null
            );
        }
        for( $i = 0; $i < count($estadistica); $i++ ){
            $estadistica[ $i ]['PRECIO'] = floatval( $estadistica[ $i ]['PRECIO'] );
            $estadistica[ $i ]['PRECIO_MASK'] = $this->maskNumber($estadistica[ $i ]['PRECIO'], 2);

            $estadistica[ $i ]['PUBLICACIONES'] = floatval( $estadistica[ $i ]['PUBLICACIONES'] );
            $estadistica[ $i ]['PUBLICACIONES_MASK'] = $this->maskNumber($estadistica[ $i ]['PUBLICACIONES'], 0);
            
            $estadistica[ $i ]['UNIDADES'] = floatval( $estadistica[ $i ]['UNIDADES'] );
            $estadistica[ $i ]['UNIDADES_MASK'] = $this->maskNumber($estadistica[ $i ]['UNIDADES'], 0);

        }
        return Array(
            'status'  => 'success',
            'message' => 'Reporte de todas las PUBLICACIONES (APROBADAS, RECHAZADAS E INHABILITADAS).',
            'data'    => $estadistica
        );

    }



}

?>