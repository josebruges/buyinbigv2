<?php
require 'conexion.php';

class UsuarioNasbi extends Conexion
{
    public function loginUsuario(Array $data)
    {
        parent::conectar();
        $buscarDatos = parent::consultaTodo("select * from peer2win.usuarios u where username = '$data[user]' or email = '$data[user]'");

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


            // parent::addLog(".......1/ Empresa informacion = " .json_encode($data2["data"]));
            $empresaData = (array) $data2["data"];

            // parent::addLog(".......1-1/ Empresa informacion = " .json_encode( $empresaData ));

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
                // parent::addLog(".......2/ Empresa informacion = " .json_encode($data2["data"]));
            }

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
        // parent::addLog("\n\n\nPDF: " . $data['pdf']);
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

        parent::addLog("Paso por aquí 1");
        if( !isset($data) || !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial']) || !isset($data['correo']) || !isset($data['telefono']) || !isset($data['pagina_web']) || !isset($data['id_ciiu']) || !isset($data['autorretenedor']) || !isset($data['id_cod_responsabilidad_fiscal']) || !isset($data['regimen']) || !isset($data['administras_recuersos_publicos']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['descripcion_productos_a_vender']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['vende_mas_de_un_articulos'])  || !isset($data['referencias']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
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

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);

        // $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc");
        // $cedula_doc               = $this->subirPdf($data, $fecha, "cedula_doc");
        // $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc");

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
            vende_mas_de_un_articulos
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
            '$data[vende_mas_de_un_articulos]'
        )";
        
        $id_dpn = parent::queryRegistro($insertxdatosxpersonaxnatural);

        if (intval( $id_dpn ) <= 0) {
            return array(
                'status'    => 'errorDatos', 
                'message'   => 'No fue posible realizar el registro de los datos del usuario natural. Verificar la información.'
            );
        }

        foreach ($data['referencias'] as $key => $value) {
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
        // if( !isset($data) || || !isset($data['rut_doc']) ){

        //         return array(
        //             'status' => 'fail',
        //             'message'=> 'faltan datos',
        //             'data' => $data
        //         );
        // }
        // parent::conectar();
        // $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        // $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        // if ( count($datos_persona_natural) <= 0) {
        //     return array(
        //         'status'  => 'errorNoExisteUsuario',
        //         'message' => 'Las credenciales de este usuario no estan registradas',
        //         'data'    => null
        //     );
        // }
        // parent::cerrar();

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);

        // $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc");

        
        // $updatexdatosxpersonaxnatural =
        // "UPDATE buyinbig.datos_persona_natural
        // SET
        //     rut_doc = '$rut_doc'
        // WHERE (id = '$datos_persona_natural[id]');";

        // parent::conectar();
        // parent::query( $updatexdatosxpersonaxnatural );
        // parent::cerrar();
        return array(
            'status'      => 'success', 
            'message'     => 'Información completada'
        );

    }
    public function completar_datos_persona_natural_cedula_doc(Array $data)
    {
        // if( !isset($data) || || !isset($data['cedula_doc']) ){

        //         return array(
        //             'status' => 'fail',
        //             'message'=> 'faltan datos',
        //             'data' => $data
        //         );
        // }
        // parent::conectar();
        // $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        // $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        // if ( count($datos_persona_natural) <= 0) {
        //     return array(
        //         'status'  => 'errorNoExisteUsuario',
        //         'message' => 'Las credenciales de este usuario no estan registradas',
        //         'data'    => null
        //     );
        // }
        // parent::cerrar();

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);

        // $cedula_doc               = $this->subirPdf($data, $fecha, "cedula_doc");

        // $updatexdatosxpersonaxnatural =
        // "UPDATE buyinbig.datos_persona_natural
        // SET
        //     cedula_doc = '$cedula_doc'
        // WHERE (id = '$datos_persona_natural[id]');";

        // parent::conectar();
        // parent::query( $updatexdatosxpersonaxnatural );
        // parent::cerrar();
        return array(
            'status'      => 'success', 
            'message'     => 'Información completada'
        );
        
    }
    public function completar_datos_persona_natural_certificado_bancario_doc(Array $data)
    {
        // if( !isset($data) || || !isset($data['certificado_bancario_doc']) ){

        //         return array(
        //             'status' => 'fail',
        //             'message'=> 'faltan datos',
        //             'data' => $data
        //         );
        // }

        // parent::conectar();
        // $selectxdatosxpersonaxnatural = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";
        // $datos_persona_natural = parent::consultaTodo($selectxdatosxpersonaxnatural);
        // if ( count($datos_persona_natural) <= 0) {
        //     return array(
        //         'status'  => 'errorNoExisteUsuario',
        //         'message' => 'Las credenciales de este usuario no estan registradas',
        //         'data'    => null
        //     );
        // }
        // parent::cerrar();

        // $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_natural/";
        // if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        // $fecha = intval(microtime(true)*1000);

        // $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc");

        // $updatexdatosxpersonaxnatural =
        // "UPDATE buyinbig.datos_persona_natural
        // SET
        //     certificado_bancario_doc = '$certificado_bancario_doc'
        // WHERE (id = '$datos_persona_natural[id]');";

        // parent::conectar();
        // parent::query( $updatexdatosxpersonaxnatural );
        // parent::cerrar();

        return array(
            'status'      => 'success', 
            'message'     => 'Información completada'
        );
        
    }

    public function actualizar_datos_persona_natural(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial']) || !isset($data['correo']) || !isset($data['telefono']) || !isset($data['pagina_web']) || !isset($data['id_ciiu']) || !isset($data['autorretenedor']) || !isset($data['id_cod_responsabilidad_fiscal']) || !isset($data['regimen']) || !isset($data['administras_recuersos_publicos']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['descripcion_productos_a_vender']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['vende_mas_de_un_articulos']) || !isset($data['rut_doc']) || !isset($data['cedula_doc']) || !isset($data['certificado_bancario_doc']) 
            || !isset($data['referencias']) ){

                return array(
                    'status' => 'fail',
                    'message'=> 'faltan datos',
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
            certificado_bancario_doc                = '$certificado_bancario_doc'
        WHERE (id = '$datos_persona_natural[id]');";
        parent::query( $updatexdatosxpersonaxnatural );

        

        $selectxdatosxpersonaxreferencias = "SELECT * FROM buyinbig.datos_persona_referencias WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        $selectxdatosxpersonaxreferencias = parent::consultaTodo($selectxdatosxpersonaxreferencias);
        if ( count($selectxdatosxpersonaxreferencias) <= 0) {
            return array(
                'status'  => 'errorUsuarioReferenciasVacio',
                'message' => 'No fue posible encontrá información de este usuario.',
                'data'    => null
            );
        }

        $index = 0;
        foreach ($data['referencias'] as $key => $value) {
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

        return array(
            'status'  => 'success',
            'message' => 'Datos personales del usuaro',
            'data'    => $selectdatospersonanatural
        );
    }


    public function completar_datos_persona_juridica(Array $data)
    {
        if( !isset($data['user_uid']) || !isset($data['user_empresa']) || !isset($data['nombre_comercial'])  || !isset($data['id_cod_responsabilidad_fiscal']) ||  !isset($data['nombres']) || !isset($data['apellidos']) || !isset($data['id_documento_identificacion']) || !isset($data['no_identificacion']) || !isset($data['tel_fijo']) || !isset($data['celular']) || !isset($data['tiene_un_cargo_publico']) || !isset($data['goza_de_un_reconocimiento_publico']) || !isset($data['familiar_con_caracteristicas_anteriores']) || !isset($data['correo_pagos']) || !isset($data['describe_correo_pagos']) || !isset($data['id_bancos']) || !isset($data['id_banco_tipo_de_cuenta']) || !isset($data['nCuenta']) || !isset($data['activos_corrientes']) || !isset($data['activos_no_corrientes']) || !isset($data['ingresos_ventas']) || !isset($data['pasivos_corrientes']) 
            || !isset($data['pasivos_no_corrientes']) || !isset($data['costos_gastos'])  || !isset($data['patrimonio'])  || !isset($data['ingresos_netos']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['camara_comercio_doc']) || !isset($data['rut_doc']) || !isset($data['contador_doc']) || !isset($data['cedula_representante_doc'])  || !isset($data['certificado_bancario_doc']) || !isset($data['certificado_composicon_accionaria_doc'])  || !isset($data['estados_financieros_doc']) || !isset($data['referencias']) ){

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

        parent::conectar();
        $selectxdatosxpersonax_juridica = "SELECT * FROM buyinbig.datos_persona_juridica WHERE user_uid = '$data[user_uid]' AND user_empresa = '$data[user_empresa]';";

        $datos_persona_juridica = parent::consultaTodo($selectxdatosxpersonax_juridica);
        if ( count($datos_persona_juridica) > 0) {
            return array(
                'status'  => 'errorDuplicado',
                'message' => 'Los datos de este usuario ya se encuentrán registrados.',
                'data'    => null
            );
        }
        parent::cerrar();

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

       
       
        $camara_comercio_doc      = $this->subirPdf($data, $fecha, "camara_comercio_doc", 2);
        $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc", 2);
        $contador_doc             = $this->subirPdf($data, $fecha, "contador_doc", 2);
        $cedula_representante_doc = $this->subirPdf($data, $fecha, "cedula_representante_doc", 2);
        $certificado_bancario_doc = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        $certificado_composicon_accionaria_doc = $this->subirPdf($data, $fecha, "certificado_composicon_accionaria_doc", 2);
        $estados_financieros_doc = $this->subirPdf($data, $fecha, "estados_financieros_doc", 2);
         parent::conectar();

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
            camara_comercio_doc, 
            rut_doc, 
            contador_doc, 
            cedula_representante_doc, 
            certificado_bancario_doc, 
            certificado_composicon_accionaria_doc, 
            estados_financieros_doc
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
            '$camara_comercio_doc',
            '$rut_doc', 
            '$contador_doc', 
            '$cedula_representante_doc', 
            '$certificado_bancario_doc', 
            '$certificado_composicon_accionaria_doc',
            '$estados_financieros_doc' 

        )";

        $id_dpn = parent::queryRegistro($insertxdatosxpersonax_juridica);
        
        

        if (intval( $id_dpn ) <= 0) {
            return array(
                'status'    => 'errorDatos', 
                'message'   => 'No fue posible realizar el registro de los datos del usuario juridico. Verificar la información.'
            );
        }

        foreach ($data['referencias'] as $key => $value) {
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
            if (intval( $id_dpn_ref ) <= 0) {
                return array(
                    'status'    => 'errorDatosRefer', 
                    'message'   => 'No fue posible realizar el registro de las referencias de los datos del usuario juridico. Verificar la información.'
                );
            }
        }
        parent::cerrar();

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

        $this->envio_correo_registro_exitoso($data); 

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


        
        return array(
            'status'  => 'success',
            'message' => 'Datos personales del usuaro',
            'data'    => $selectdatospersona_juridica
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
        || !isset($data['pasivos_no_corrientes']) || !isset($data['costos_gastos'])  || !isset($data['patrimonio'])  || !isset($data['ingresos_netos']) || !isset($data['movimientos_moneda_extranjera']) || !isset($data['camara_comercio_doc']) || !isset($data['rut_doc']) || !isset($data['contador_doc']) || !isset($data['cedula_representante_doc'])  || !isset($data['certificado_bancario_doc']) || !isset($data['certificado_composicon_accionaria_doc'])  || !isset($data['estados_financieros_doc']) || !isset($data['referencias']) || !isset($data['id_ciiu']) ){

            return array(
                'status' => 'fail',
                'message'=> 'faltan datos',
                'data' => null
            );
        }

        if(intval($data['user_empresa'])!=1)  return array('status' => 'fail','message'=> 'el usuario no es empresa','data' => null);

        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'] . "documentos/datos_persona_juridica/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);

        $camara_comercio_doc                      = $data["camara_comercio_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $camara_comercio_doc) ) == 1) {
            $camara_comercio_doc                  = $this->subirPdf($data, $fecha, "camara_comercio_doc", 2);
        }

        $rut_doc                      = $data["rut_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $rut_doc) ) == 1) {
            $rut_doc                  = $this->subirPdf($data, $fecha, "rut_doc", 2);
        }

        $contador_doc                      = $data["contador_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $contador_doc) ) == 1) {
            $contador_doc                  = $this->subirPdf($data, $fecha, "contador_doc", 2);
        }

        $cedula_representante_doc                      = $data["cedula_representante_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $cedula_representante_doc) ) == 1) {
            $cedula_representante_doc                  = $this->subirPdf($data, $fecha, "cedula_representante_doc", 2);
        }

        $certificado_bancario_doc                      = $data["certificado_bancario_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_bancario_doc) ) == 1) {
            $certificado_bancario_doc                  = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        }

        $certificado_bancario_doc                      = $data["certificado_bancario_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_bancario_doc) ) == 1) {
            $certificado_bancario_doc                  = $this->subirPdf($data, $fecha, "certificado_bancario_doc", 2);
        }

        $certificado_composicon_accionaria_doc                      = $data["certificado_composicon_accionaria_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $certificado_composicon_accionaria_doc) ) == 1) {
            $certificado_composicon_accionaria_doc                  = $this->subirPdf($data, $fecha, "certificado_composicon_accionaria_doc", 2);
        }

        $estados_financieros_doc                      = $data["estados_financieros_doc"];
        if ( count( explode('https://nasbi.peers2win.com/documentos', $estados_financieros_doc) ) == 1) {
            $estados_financieros_doc                  = $this->subirPdf($data, $fecha, "estados_financieros_doc", 2);
        }


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
            camara_comercio_doc                     = '$camara_comercio_doc', 
            rut_doc                                 = '$rut_doc', 
            contador_doc                            = '$contador_doc', 
            cedula_representante_doc                = '$cedula_representante_doc', 
            certificado_bancario_doc                = '$certificado_bancario_doc', 
            certificado_composicon_accionaria_doc   = '$certificado_composicon_accionaria_doc', 
            estados_financieros_doc                 = '$estados_financieros_doc'
         
        WHERE (id = '$datos_persona_juridica[id]');";
        parent::query($updatexdatosxpersonax_juridica);



        // Actualizar id_ciiu empresa 

        $updatempresa_juridica =
        "UPDATE buyinbig.empresas
        SET
            id_ciiu                       = '$data[id_ciiu]'
        WHERE (id = '$data[user_uid]');"; 

        parent::query($updatempresa_juridica);

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
        foreach ($data['referencias'] as $key => $value) {
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

        return array(
            'status'    => 'success', 
            'message'   => 'Información actualizada'
        );
    }

    function actualizar_ciiu_tempo(){
        $updatempresa_juridica =
        "UPDATE buyinbig.empresas
        SET
            id_ciiu                       = '1522'
        WHERE (id = '63');"; 
        parent::conectar();
        parent::query($updatempresa_juridica);
        parent::cerrar();

        return "bien";
    }

    function eliminar_person_temporal(){
        $updatempresa_juridica =
        "DELETE FROM datos_persona_juridica where  user_uid= 163"; 
        parent::conectar();
        parent::query($updatempresa_juridica);
        parent::cerrar();
       // ;
        return "bien";
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

        if($data_empresa["data"]["id_ciiu"]=="" || !isset($data_empresa["data"]["id_ciiu"])){
            $bandera=1; 
            array_push($array_mensaje,array("mensaje"=> "falta el campo"." "."id_ciiu", "campo"=>"id_ciiu")); 
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
      $data_personal= $this->get_dato_personal([
          "uid"=> $data['user_uid'], 
          "empresa"=> $data['user_empresa'], 
          "juridico"=> 0
      ]);
    
      if (empty($data_personal))  return array("status"=> "fail_no_registro", "mensage"=> "el usuario no presenta registro no tiene ningun dato en la tabla", "data"=>null); 
    
      $array_propiedades=  array_keys($data_personal); 

  
      
      foreach ($array_propiedades as $x => $propiedad) {
        if($data_personal[$propiedad]==""  || !isset($data_personal[$propiedad])){
          $bandera=1; 
          array_push($array_mensaje,array("mensaje"=> "falta el campo"." ".$propiedad, "campo"=>$propiedad)); 
        }
      }
    
      if($bandera==0){
        return array("status"=> "success", "mensage"=> "no le falta ningun campo","data"=> null); 
      }else if($bandera==1){
        return array("status"=> "success_Campo_faltante","mensage"=> "faltan campos" ,"data"=> $array_mensaje, "campos_faltantes"=> count($array_mensaje)); 
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
            $this->html_registro_exitoso_bienvenida($data_user["data"]); 
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
       

        $para      = $data_user['correo'];
        $mensaje1   = $html;
        $titulo    = $json->trans171_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
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
        return array('status' => 'success', 'message'=> 'user', 'data' => $usuario[0]);
    }

    function mapUsuarios2(Array $usuarios, Int $empresa)
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



        // function listar_usuarios($data){
    //     if(!isset($data) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);
    //        if(intval($data['tipo'])==0){// todos

   
    //        }else if(intval($data['tipo'])==1){//natural

   
    //        }else if(intval($data['tipo'])==1){//juridica 
   
    //        }
   
    //    }
   
    function traer_usuarios_natural_en_revision(){
       $campos_revision_numerico=["nombre_comercial_status","correo_status","telefono_status","pagina_web_status","id_ciiu_status", "autorretenedor_status", "id_cod_responsabilidad_fiscal_status", "regimen_status", "administras_recuersos_publicos_status", "tiene_un_cargo_publico_status", "goza_de_un_reconocimiento_publico_status", "familiar_con_caracteristicas_anteriores_status", "descripcion_productos_a_vender_status", "id_bancos_status", "id_banco_tipo_de_cuenta_status", "nCuenta_status", "movimientos_moneda_extranjera_status", "vende_mas_de_un_articulos_status", "rut_doc_status", "cedula_doc_status", "certificado_bancario_doc_status", "datos_persona_referencias_1", "datos_persona_referencias_2"];  //que pueden ser 0, 1 o 2 
       $data_campos= $this->extraer_nombre_columna_tabla('datos_persona_natural_revision'); 
       $data_listado= $this->traer_listado_personas_natural_o_juridica('datos_persona_natural'); 

       if($data_campos== false || $data_listado==false) return array("status"=>"fail", "mensagge"=>"campos_tabla", "data"=>"fail"); 
        foreach ($data_campos as $x => $propiedad["COLUMN_NAME"]) {
            
        }
   
    }

    function traer_listado_personas_natural_o_juridica(String $tabla){
        $consulta_select="SELECT * FROM '$tabla'"; 

        //var_dump($consulta_name_colm); 

        parent::conectar();
        $listado_user = parent::consultaTodo($consulta_select);
        parent::cerrar();

        if(count($listado_user)<=0){
            $listado_user=false; 
        }

        return $listado_user; 
    }

    function extraer_nombre_columna_tabla(String $nombre_tabla){

        $consulta_name_colm=" SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_name = '$nombre_tabla'"; 

        //var_dump($consulta_name_colm); 

        parent::conectar();
        $nombres_columnas = parent::consultaTodo($consulta_name_colm);
        parent::cerrar();

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






}

?>