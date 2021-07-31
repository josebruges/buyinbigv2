<?php
// require 'conexion.php';
require 'nasbifunciones.php';
// require 'token.php';

class Empresas extends Conexion
{

    private $tiempotoken = 3600000;//Tiempo del token
    public function registrarEmpresa(Array $data)
    {
        
        if(
               !isset($data)
            || !isset($data['pais']) 
            || !isset($data['razon_social']) 
            || !isset($data['nit']) 
            || !isset($data['correo']) 
            || !isset($data['clave']) 
            || !isset($data['sector']) 
            || !isset($data['ciu_codigo']) 
            || !isset($data['idioma']) 
            || !isset($data['regimen']) 
            || !isset($data['autorretenedor']) 
            || !isset($data['ciudad']) 
            || !isset($data['direccion']) 
            || !isset($data['telefono']) 

        ) {
            

            parent::addLog(" v.1 Request Gisel: " . json_encode( $data) );

            return array(
                'status' => 'fail',
                'message'=> 'no data',
                'data'   => null
            );

        }
        parent::addLog(" v.2 Request Gisel: " . json_encode( $data) );
        $referido = NULL;
        if(isset($data['referido'])) $referido = addslashes($data['referido']);


        // Verificar si el correo existe o no en la base de datos de peers2win.
        $datos = array('email' => $data['correo']);

        $response = parent::remoteRequest("http://peers2win.com/api/controllers/users/emailExistente.php", $datos);
        $resultFilter = json_decode($response, true);

        if (count( $resultFilter ) > 0) {
            return array(
                'status' => 'errorExitenciaCorreoPeers2Win',
                'message' => 'Este correo ya se encuentrá registrado en la base de datos de usuarios. Una empresa no puede compartir el mismo correo.'
            );
        }

        $data['pais']           = addslashes($data['pais']);
        $data['razon_social']   = addslashes($data['razon_social']);
        $data['nombre_empresa'] = addslashes($data['razon_social']);
        $data['nit']            = addslashes($data['nit']);
        $data['pagina_web']     = addslashes($data['pagina_web']);
        $data['correo']         = addslashes($data['correo']);
        $data['clave']          = addslashes($data['clave']);


        $sector                 = ( !isset($data['sector'])         ? "" : $data['sector']                );
        $ciu_codigo             = ( !isset($data['ciu_codigo'])     ? "" : $data['ciu_codigo']            );
        $regimen                = ( !isset($data['regimen'])        ? "" : $data['regimen']               );
        $autorretenedor         = ( !isset($data['autorretenedor']) ? "" : $data['autorretenedor']        );
        $ciudad                 = ( !isset($data['ciudad'])         ? "" : addslashes($data['ciudad'])    );
        $direccion              = ( !isset($data['direccion'])      ? "" : addslashes($data['direccion']) );
        $telefono               = ( !isset($data['telefono'])       ? "" : addslashes($data['telefono']) );


        parent::addLog("(1)-----+> [ data ]: " . json_encode($data));
        $responseValidarNit = $this->validarNit($data);
        if( $responseValidarNit['status'] != 'success' ){
            return $responseValidarNit;
        }



        if ( !isset($data['idioma']) ) {
            $data['idioma'] = "ES";
        }
        $data['idioma'] = $data['idioma'];

        $correoexiste = $this->verEmpresaCorreo($data);
        parent::addLog(" v.3 Request Gisel: " . json_encode( $correoexiste) );
        if($correoexiste['status'] == 'success') {
            return array(
                'status' => 'correoExiste',
                'message'=> 'empresa ya creada',
                'data' => null
            );
        }


        
        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $insertarxempresa = "INSERT INTO empresas
        (
            pais,
            nombre_empresa,
            razon_social,
            nit,
            pagina_web,
            idioma,
            primer_login,
            correo,
            clave,
            referido,
            estado,
            fecha_creacion,
            fecha_actualizacion,

            sector,
            id_ciiu,
            regimen,
            autorretenedor,
            ciudad,
            direccion,
            telefono
        )
        VALUES
        (
            '$data[pais]',
            '$data[razon_social]',
            '$data[razon_social]',
            '$data[nit]',
            '$data[pagina_web]',
            '$data[idioma]',
            0,
            '$data[correo]',
            '$data[clave]',
            '$referido',
            0,
            '$fecha',
            '$fecha',
            $sector,
            '$ciu_codigo',
            $regimen,
            $autorretenedor,
            '$ciudad',
            '$direccion',
            '$telefono'
        );";
        
        $insert = parent::queryRegistro($insertarxempresa);
        parent::cerrar();

        if(!$insert) {
            return array(
                'status'  => 'fail',
                'message' => 'empresa no creada',
                'data'    => $insertarxempresa
            );
        }else{
            $data['uid'] = $insert;

            parent::addLog(" v.6 Request Gisel: " . json_encode( $data) );

            $this->htmlEmailConfirmacion2($data);
            return array(
                'status'  => 'success',
                'message' => 'empresa creada',
                'data'    => $data['uid']
            );
        }
    }

    public function obtener_ciiu(){

        parent::conectar();
        $ciiuResult = parent::consultaTodo("SELECT * FROM buyinbig.Ciiu;");
        parent::cerrar();

        if( COUNT($ciiuResult) > 0 ){
            return array("status" => "success", "data" => $ciiuResult);
        }else{
            return array("status" =>"fail", "mensaje" => "error");
        }
    }

    public function verEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectxempresa = "SELECT e.* FROM empresas e WHERE e.id = '$data[id]';";
        $empresa = parent::consultaTodo($selectxempresa);

        if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);

        $empresa = $this->mapEmpresa($empresa, true, true);
        $empresa = $empresa[0];
        parent::cerrar();
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa);
    }

    public function verEmpresaCorreo(Array $data)
    {
        if(!isset($data) || !isset($data['correo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        parent::addLog(" v.4 Request Gisel: " . json_encode( $data) );

        parent::conectar();
        $selectxempresa = "SELECT e.* FROM empresas e WHERE e.correo = '$data[correo]';";
        $empresa = parent::consultaTodo($selectxempresa);
        parent::cerrar();

        if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);
        
        parent::conectar();

        parent::addLog(" v.5 Request Gisel: " . json_encode( $empresa) );
        $empresa = $this->mapEmpresa($empresa);
        parent::cerrar();
        $empresa = $empresa[0];
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa);
    }

    public function verEmpresaToken(Array $data)
    {
        if(!isset($data) || !isset($data['token'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $fecha = $data['fecha'];
        $tiempotoken = $this->tiempotoken;
        parent::conectar();
        $selectxempresa = "SELECT e.*, IF((($fecha - e.fecha_token) < $tiempotoken), 1, 0) AS validar_token FROM empresas e WHERE e.token = '$data[token]';";
        $empresa = parent::consultaTodo($selectxempresa);
        parent::cerrar();
        
        if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);
        
        parent::conectar();
        $empresa = $this->mapEmpresa($empresa);
        parent::cerrar();
        $empresa = $empresa[0];
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa);
    }

    public function loginEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['correo']) || !isset($data['clave'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $origen = $_SERVER['HTTP_USER_AGENT'];
        if(isset($origen)){
            if(count(explode('(', $_SERVER['HTTP_USER_AGENT'])) > 1){
                $origen = explode(';' ,explode(')', explode('(', $_SERVER['HTTP_USER_AGENT'])[1])[0])[0];
            }
        }

        parent::conectar();
        $selectxempresa = "SELECT e.* FROM empresas e WHERE e.correo = '$data[correo]' AND e.clave = '$data[clave]';";
        $empresa = parent::consultaTodo($selectxempresa);
        // parent::cerrar();

        if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);
        
        $empresa = $this->mapEmpresaLogin($empresa)[0];

        parent::cerrar();

        // if($empresa['primer_login'] == 1){
        if( intval($empresa['estado']) > 0 ){
            $count_login = 0;
            $count_login = intval($empresa['primer_login'])+1;
            $fecha = intval(microtime(true)*1000);
            // parent::conectar();
            $updatexempresa = "UPDATE empresas 
            SET 
                primer_login = '$count_login',
                fecha_actualizacion = '$fecha'
            WHERE id = '$empresa[id]';";

            parent::conectar();
            $update = parent::query($updatexempresa);
            parent::cerrar();

            $empresa['primer_login'] = $count_login;
        }

        if (isset($data["tokenfb"])) {
            //$plataforma = (isset($data["plataforma"])?$data["plataforma"]:"2");
            $data["plataforma"] = 2;
            $plataforma         = $data["plataforma"];
            $device             = (isset($data["device"])?$data["device"]:"1");
            //parent::remoteRequest("http://localhost:9632/addRegister", array("token"=>$data["tokenfb"], "uid"=>$lista[0]['id'], "plataforma"=>$plataforma, "device"=>$device));
            parent::conectar();
            $buscarToken = parent::consultaTodo("SELECT id, token, usuario, plataforma FROM peer2win.tokens_firebase where token = '$data[tokenfb]'");
            if (count($buscarToken) > 0)  {
                parent::queryRegistro("UPDATE peer2win.tokens_firebase SET usuario='".$buscarToken[0]['id']."' WHERE token = '$data[tokenfb]'");
                // parent::queryRegistro("UPDATE peer2win.tokens_firebase SET usuario='".$lista[0]['id']."' WHERE token = '$data[tokenfb]'");
            } else {
                parent::queryRegistro("INSERT INTO peer2win.tokens_firebase (token, usuario, plataforma, device) VALUES ('$data[tokenfb]', '$empresa[id]', $plataforma, $device)");
            }
            parent::cerrar();
        }

        $empresa["token"] = generarTokenEmpresa($empresa["id"]);


        if(isset($origen)){
            $empresa['dispositivo'] = $origen;
            $empresa['fecha'] = intval(microtime(true)*1000);
            $this->verificarHistorialLoginEmpresa($empresa);
        }
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa);
    }
    
    public function getCodeMD5(Array $data){

        parent::conectar();
        $buscarDatos = array();
        if ( intval( $data['empresa'] ) == 0 ) {
            $buscarDatos = parent::consultaTodo("SELECT * FROM peer2win.usuarios u WHERE u.email = '$data[email]'; ");
            
        }else{
            $buscarDatos = parent::consultaTodo("SELECT * FROM buyinbig.empresas e WHERE e.correo = '$data[email]'; ");

        }
        parent::cerrar();
        if ( count( $buscarDatos ) > 0 ) {
            $buscarDatos = $buscarDatos[0];
            
            $buscarDatos['id'] = intval( $buscarDatos['id'] );
            return array(
                'STATUS'  => 'success',
                'CORREO'  => $data['email'],
                'ID'      => $buscarDatos['id'],
                'CODE'    => md5( $buscarDatos['id'] )
            );
        }else{
            return array(
                'status' => 'fail',
                'code'=> 0
            );

        }
    }

    public function actualizarEmpresa(Array $data)
    {
        if(
            !isset($data) 
            || !isset($data['id']) 
            || !isset($data['direccion_fisica_de_notificaciones']) 
            || !isset($data['nit']) 
            || !isset($data['pagina_web']) 
            || !isset($data['telefono']) 
            || !isset($data['idioma']) 
            || !isset($data['notificaciones']) 
            || !isset($data['nombre_dueno']) 
            || !isset($data['apellido_dueno']) 
            || !isset($data['tipo_documento_dueno']) 
            || !isset($data['numero_documento_dueno']) 
            || !isset($data['foto_docuemento_empresa']) 
            || !isset($data['foto_documento_dueno'])  
            || !isset($data['foto_logo_empresa'])  
            || !isset($data['foto_portada_empresa']) 
            || !isset($data['razon_social']) 
            || !isset($data['tipo_empresa']) 
            || !isset($data['pais'])  
            || !isset($data['ciu_codigo']) 
            || !isset($data['autorretenedor'])  
            || !isset($data['regimen'])  
            || !isset($data['direccion'])
        ) {
            return array('status' => 'fail', 'message'=> 'no data ', 'data' => $data);
        }

        $data['ciu_codigo'] = strval($data['ciu_codigo']);


        $responseValidarNit = $this->validarNit($data);
        if( $responseValidarNit['status'] != 'success' ){
            return $responseValidarNit;
        }

        $fecha = intval(microtime(true)*1000);
        $urlempresa = strrpos($data['foto_docuemento_empresa'], 'base64');
        if ($urlempresa === false) {
            $urlempresa = $data['foto_docuemento_empresa'];
        }else{
            $urlempresa = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_docuemento_empresa']),
                'fecha' => $fecha,
                'tipo' => 'empresa'
            ]);
        }

        $urldueno = strrpos($data['foto_documento_dueno'], 'base64');
        if ($urldueno === false) {
            $urldueno = $data['foto_documento_dueno'];
        }else{
            $urldueno = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_documento_dueno']),
                'fecha' => $fecha,
                'tipo' => 'dueno'
            ]);
        }

        $urllogo = strrpos($data['foto_logo_empresa'], 'base64');
        if ($urllogo === false) {
            $urllogo = $data['foto_logo_empresa'];
        }else{
            $urllogo = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_logo_empresa']),
                'fecha' => $fecha,
                'tipo' => 'logo'
            ]);
        }


        $urlportada = strrpos($data['foto_portada_empresa'], 'base64');
        if ($urlportada === false) {
            $urlportada = $data['foto_portada_empresa'];
        }else{
            $urlportada = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_portada_empresa']),
                'fecha' => $fecha,
                'tipo' => 'portada'
            ]);
        }

        $nombre_empresa = "";
        if( isset($data['nombre_empresa']) && !empty($data['nombre_empresa']) ) {
            $nombre_empresa = $data['nombre_empresa'];
        }

        $empresa = $this->verEmpresa($data)['data'];

        // $nombre_empresa       = preg_replace('/\&(.)[^;]*;/', '\\1', htmlentities($nombre_empresa));

        $nombre_empresa       = addslashes($nombre_empresa);
        $data['pagina_web']   = addslashes($data['pagina_web']);
        $data['nombre_dueno'] = addslashes($data['nombre_dueno']);
        $data['razon_social'] = addslashes($data['razon_social']);
        
        // $data['razon_social'] = preg_replace('/\&(.)[^;]*;/', '\\1', htmlentities($data['razon_social']));

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            nombre_empresa = '$nombre_empresa',
            nit = '$data[nit]',
            pagina_web = '$data[pagina_web]',
            idioma = '$data[idioma]',
            notificaciones = '$data[notificaciones]',
            telefono = '$data[telefono]',
            nombre_dueno = '$data[nombre_dueno]',
            razon_social = '$data[razon_social]',
            direccion_fisica_de_notificaciones = '$data[direccion_fisica_de_notificaciones]',
            apellido_dueno = '$data[apellido_dueno]',
            tipo_documento_dueno = '$data[tipo_documento_dueno]',
            numero_documento_dueno = '$data[numero_documento_dueno]',
            pais = '$data[pais]',
            tipo_empresa = '$data[tipo_empresa]',
            foto_docuemento_empresa = '$urlempresa',
            foto_documento_dueno = '$urldueno',
            foto_logo_empresa = '$urllogo',
            foto_portada_empresa = '$urlportada',
            primer_cambio = '0',
            fecha_actualizacion = '$fecha', 
            sector= '$data[sector]', 
            id_ciiu = '$data[ciu_codigo]', 
            regimen = '$data[regimen]', 
            autorretenedor = '$data[autorretenedor]',
            direccion = '$data[direccion]'
        WHERE id = '$data[id]';";

        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'datos no actualizados', 'primer_cambio' => $empresa['primer_cambio'], 'data' => null, 'query' => $updatexempresa);
    
        return array('status' => 'success', 'message'=> 'datos actualizados', 'primer_cambio' => $empresa['primer_cambio'], 'data' => null);
    }

    public function personalizarEmpresa(Array $data)
    {
        
        if(!isset($data) || !isset($data['id']) || !isset($data['nit']) || !isset($data['pagina_web']) || !isset($data['telefono']) || !isset($data['idioma']) || !isset($data['notificaciones']) || !isset($data['nombre_dueno']) || !isset($data['apellido_dueno']) || !isset($data['tipo_documento_dueno']) || !isset($data['numero_documento_dueno']) || !isset($data['foto_docuemento_empresa']) || !isset($data['foto_documento_dueno'])  || !isset($data['foto_logo_empresa'])  || !isset($data['foto_portada_empresa']) || !isset($data['foto_asesor']) || !isset($data['razon_social']) || !isset($data['caracteristica_principal_1']) || !isset($data['caracteristica_principal_2']) || !isset($data['caracteristica_principal_3']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => $data);

         //ver data antes de actualizar empresa para saber si es primera vez crear 
         $bandera_saber_si_es_primeravez=0; 
         $data_antes_actualizar_empresa = $this->consultar_data_de_empresa_por_id($data['id']);
         if($data_antes_actualizar_empresa["nombre_dueno"]=="" || $data_antes_actualizar_empresa["nombre_dueno"]== null || !isset($data_antes_actualizar_empresa["nombre_dueno"])){
             $bandera_saber_si_es_primeravez=1; 
         }
         //fin ver data antes de actualizar empresa para saber si es primera vez crear 


        $fecha = intval(microtime(true)*1000);
        $urlempresa = strrpos($data['foto_docuemento_empresa'], 'base64');
        if ($urlempresa === false) {
            $urlempresa = $data['foto_docuemento_empresa'];
        }else{
            $urlempresa = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_docuemento_empresa']),
                'fecha' => $fecha,
                'tipo' => 'empresa'
            ]);
        }

        $urldueno = strrpos($data['foto_documento_dueno'], 'base64');
        if ($urldueno === false) {
            $urldueno = $data['foto_documento_dueno'];
        }else{
            $urldueno = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_documento_dueno']),
                'fecha' => $fecha,
                'tipo' => 'dueno'
            ]);
        }

        $urllogo = strrpos($data['foto_logo_empresa'], 'base64');
        if ($urllogo === false) {
            $urllogo = $data['foto_logo_empresa'];
        }else{
            $urllogo = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_logo_empresa']),
                'fecha' => $fecha,
                'tipo' => 'logo'
            ]);
        }


        $urlportada = strrpos($data['foto_portada_empresa'], 'base64');
        if ($urlportada === false) {
            $urlportada = $data['foto_portada_empresa'];
        }else{
            $urlportada = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_portada_empresa']),
                'fecha' => $fecha,
                'tipo' => 'portada'
            ]);
        }

        $urlfoto_asesor = strrpos($data['foto_asesor'], 'base64');
        if ($urlfoto_asesor === false) {
            $urlfoto_asesor = $data['foto_asesor'];
        }else{
            $urlfoto_asesor = $this->subirFotoEmpresa([
                'id' => $data['id'],
                'img' => addslashes($data['foto_asesor']),
                'fecha' => $fecha,
                'tipo' => 'fotoasesor'
            ]);
        }

        $nombre_empresa = "";
        if(isset($data['nombre_empresa']) && !empty($data['nombre_empresa'])) {
            $nombre_empresa = $data['nombre_empresa'];
        }

        $empresa = $this->verEmpresa($data)['data'];

        $nombre_empresa         = addslashes($nombre_empresa);
        $data['pagina_web']     = addslashes($data['pagina_web']);
        $data['nombre_dueno']   = addslashes($data['nombre_dueno']);
        $data['razon_social']   = addslashes($data['razon_social']);


        $data['nombre_dueno']   = addslashes($data['nombre_dueno']);
        $data['razon_social']   = addslashes($data['razon_social']);
        $data['apellido_dueno'] = addslashes($data['apellido_dueno']);
        $data['descripcion']    = addslashes($data['descripcion']);
        $data['cargo']          = addslashes($data['cargo']);

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            nombre_empresa = '$nombre_empresa',
            nit = '$data[nit]',
            pagina_web = '$data[pagina_web]',
            idioma = '$data[idioma]',
            notificaciones = '$data[notificaciones]',
            telefono = '$data[telefono]',
            nombre_dueno = '$data[nombre_dueno]',
            razon_social = '$data[razon_social]',
            apellido_dueno = '$data[apellido_dueno]',
            tipo_documento_dueno = '$data[tipo_documento_dueno]',
            numero_documento_dueno = '$data[numero_documento_dueno]',

            descripcion = '$data[descripcion]',
            cargo = '$data[cargo]',

            caracteristica_principal_1 = '$data[caracteristica_principal_1]',
            caracteristica_principal_2 = '$data[caracteristica_principal_2]',
            caracteristica_principal_3 = '$data[caracteristica_principal_3]',

            foto_docuemento_empresa = '$urlempresa',
            foto_documento_dueno = '$urldueno',
            foto_logo_empresa = '$urllogo',
            foto_portada_empresa = '$urlportada',
            foto_asesor = '$urlfoto_asesor',
            primer_cambio = '0',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'datos no actualizados', 'primer_cambio' => $empresa['primer_cambio'], 'data' => null, 'query3' => $updatexempresa);
        
        //envio de correo de nuevo espacio en tienda
        if( $bandera_saber_si_es_primeravez ==1){
            $data_empresa = $this->verEmpresa($data);
            if($data_empresa['status']=='success'){
                $data_empresa = $data_empresa['data']; 
                $this->htmlEmailnuevoespaciotienda($data_empresa);
            }
        }
          // fin de envio de correo de nuevo espacio en tienda

        return array('status' => 'success', 'message'=> 'datos actualizados', 'primer_cambio' => $empresa['primer_cambio'], 'data' => null);
    }

    public function confirmarEmpresa(Array $data)
    {

        if( !isset($data) || !isset($data['id']) || !isset($data['code']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'no data',
                'data' => null
            );
        }

        $empresa = $this->verEmpresa($data);
        if($empresa['status'] == 'fail') return $empresa;
        $empresa = $empresa['data'];

        $fecha = intval(microtime(true)*1000);

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            estado = '1',
            primer_login = '0',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'datos no actualizados', 'data' => null);

        // $dataNasbiGold = [
        //     'data' => [
        //         'uid'     => $data['id'],
        //         'empresa' => 1,
        //         'monto'   => 0,
        //         'moneda'  => "Nasbigold",
        //         'tipo'    => "1",
        //         'notificar' => 1
        //     ]
        // ];
        // $nasbifunciones = new Nasbifunciones();
        // $responseGold = $nasbifunciones->insertarNasbiCoin( $dataNasbiGold );

        // $dataNasbiBlue = [
        //     'data' => [
        //         'uid'     => $data['id'],
        //         'empresa' => 1,
        //         'monto'   => 0,
        //         'moneda'  => "Nasbiblue",
        //         'tipo'    => "1",
        //         'notificar' => 1
        //     ]
        // ];
        // $responseBlue = $nasbifunciones->crearNasbicoin( $dataNasbiBlue );

        // unset($nasbifunciones);
        $addressGold = md5(microtime().rand());
        parent::conectar();
        $insterarxnasbicoin = "INSERT INTO nasbicoin_gold
        (
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '$addressGold',
            '0',
            'Nasbigold',
            '1',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertarWalletGold = parent::queryRegistro($insterarxnasbicoin);

        $addressBlue = md5(microtime().rand());
        $insterarxnasbicoin = "INSERT INTO nasbicoin_blue
        (
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '$addressBlue',
            '0',
            'Nasbiblue',
            '1',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertarWalletBlue = parent::queryRegistro($insterarxnasbicoin);
        parent::cerrar();


           //para enviar el correo de registro completado
        $data_empresa = $this->verEmpresa($data);
        if($data_empresa['status']=='success'){
            $data_empresa = $data_empresa['data']; 
            $this->htmlEmailResgitrocompletado($data_empresa);
        }
        //fin para enviar el correo de registro completado

        
        return array(
            'status'        => 'success',
            'message'       => '**La empresa ha sido validada, puedes disfrutar de https://nasbi.com',
            'data'          => null,

            'responseGold'  => $insertarWalletGold,

            'responseBlue'  => $insertarWalletBlue
        );
    }
    public function confirmarEmpresaCode(Array $data)
    {
        // Esta función se encarga de validar que el código de confirmación sea correcto.
        // Activa la empresa y le crea las address.

        if( !isset($data) || !isset($data['id']) || !isset($data['code']) ) {
            return array(
                'status' => 'fail',
                'message'=> 'no data',
                'data' => null
            );
        }

        if ( MD5( $data['id'] ) != $data['code'] ) {
            return array(
                'status' => 'errorCode',
                'message'=> 'El código ingresado no es correcto.',
                'data' => null
            );
        }

        $empresa = $this->verEmpresa($data);
        if($empresa['status'] == 'fail') return $empresa;
        $empresa = $empresa['data'];

        $fecha = intval(microtime(true)*1000);

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            estado = '1',
            primer_login = '0',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'datos no actualizados', 'data' => null);

        // $dataNasbiGold = [
        //     'data' => [
        //         'uid'     => $data['id'],
        //         'empresa' => 1,
        //         'monto'   => 0,
        //         'moneda'  => "Nasbigold",
        //         'tipo'    => "1",
        //         'notificar' => 1
        //     ]
        // ];
        // $nasbifunciones = new Nasbifunciones();
        // $responseGold = $nasbifunciones->insertarNasbiCoin( $dataNasbiGold );

        // $dataNasbiBlue = [
        //     'data' => [
        //         'uid'     => $data['id'],
        //         'empresa' => 1,
        //         'monto'   => 0,
        //         'moneda'  => "Nasbiblue",
        //         'tipo'    => "1",
        //         'notificar' => 1
        //     ]
        // ];
        // $responseBlue = $nasbifunciones->crearNasbicoin( $dataNasbiBlue );

        // unset($nasbifunciones);
        $addressGold = md5(microtime().rand());
        parent::conectar();
        $insterarxnasbicoin = "INSERT INTO nasbicoin_gold
        (
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '$addressGold',
            '0',
            'Nasbigold',
            '1',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertarWalletGold = parent::queryRegistro($insterarxnasbicoin);

        $addressBlue = md5(microtime().rand());
        $insterarxnasbicoin = 
        "INSERT INTO nasbicoin_blue
        (
            uid,
            address,
            monto,
            moneda,
            estado,
            empresa,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '$addressBlue',
            '0',
            'Nasbiblue',
            '1',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertarWalletBlue = parent::queryRegistro($insterarxnasbicoin);
        parent::cerrar();


           //para enviar el correo de registro completado
        $data_empresa = $this->verEmpresa($data);
        if($data_empresa['status']=='success'){
            $data_empresa = $data_empresa['data']; 
            $this->htmlEmailResgitrocompletado($data_empresa);
        }
        //fin para enviar el correo de registro completado

        // cambio Aramis //
        parent::conectar();
        $empresa_crf = parent::consultaTodo("SELECT * FROM empresas WHERE id = '$data[id]'");
        parent::cerrar();
        if(count($empresa_crf) > 0){
            if($empresa_crf[0]['referido'] != NULL && $empresa_crf[0]['referido'] != ""){
                $codigo_referido = $empresa_crf[0]['referido'];

                $notificacion = new Notificaciones();

                $notificacion->insertarNotificacion([
                    'uid' => $codigo_referido,
                    'empresa' => 0,
                    'text' => 'La empresa '.$empresa_crf[0]['razon_social'].' se ha registrado con tu código de referido',
                    'es' => 'La empresa '.$empresa_crf[0]['razon_social'].' se ha registrado con tu código de referido',
                    'en' => 'The company '.$empresa_crf[0]['razon_social'].' has registered with your referral code',
                    'keyjson' => '',
                    'url' => ''
                ]);
            }
        }
        
        // fin cambio Aramis //
        
        return array(
            'status'        => 'success',
            'message'       => 'La empresa ha sido validada, puedes disfrutar de https://nasbi.com',
            'data'          => null,

            'responseGold'  => $insertarWalletGold,

            'responseBlue'  => $insertarWalletBlue
        );
    }

    public function generarToken(Array $data)
    {
        if(!isset($data) || !isset($data['correo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $empresa = $this->verEmpresaCorreo($data);
        if($empresa['status'] == 'fail') {
            return array(
                'status' => 'errorCorreo',
                'message'=> 'El correo ingresado no no se encuentrá registrado',
                'data' => null
            );
        }
        $empresa = $empresa['data'];

        $fecha = intval(microtime(true)*1000);
        $token = md5($empresa['id'].','.$fecha);
        parent::conectar();

        $updatexempresa = 
        "UPDATE empresas 
        SET 
            token = '$token',
            fecha_token = '$fecha'
        WHERE id = '$empresa[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update){
            return array(
                'status'  => 'fail',
                'message' => 'token no actulizado',
                'data'    => null
            );
        }

        $empresa['uid']   = $empresa['id'];
        $empresa['token'] = $token;

        $this->htmlEmailToken2($empresa);

        return array(
            'status'  => 'success',
            'message' => 'token actualizado',
            'data'    => null
        );
    }
    public function reenviarCodigo(Array $data)
    {
        if(!isset($data) || !isset($data['correo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $empresa = $this->verEmpresaCorreo($data);
        if($empresa['status'] == 'fail') {
            return array(
                'status' => 'errorCorreo',
                'message'=> 'El correo ingresado no no se encuentrá registrado',
                'data' => null
            );
        }
        $empresa = $empresa['data'];

        $fecha = intval(microtime(true)*1000);
        $token = md5($empresa['id'].','.$fecha);
        parent::conectar();


        $empresa['uid']   = $empresa['id'];
        $empresa['token'] = $token;

        $empresa['correo'] = $data['correo'];
        $this->htmlEmailConfirmacion2($empresa);

        return array(
            'status'  => 'success',
            'message' => 'token actualizado',
            'data'    => null
        );
    }

    public function darmeDeBaja(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $empresa = $this->verEmpresa($data);
        if($empresa['status'] == 'fail') {
            return array(
                'status' => 'errorEmpresaNoExiste',
                'message'=> 'La empresa no esta registrada.',
                'data' => null
            );
        }
        $empresa = $empresa['data'];
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            estado = 2,
            fecha_actualizacion = '$fecha'
        WHERE id = '$empresa[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'empresa no actulizada', 'data' => null);

        
       // $plantillaHTML = $this->htmlEmailDarseDeBaja($empresa);
       // $this->sendEmailByAsunto($empresa, $plantillaHTML, "nasbi.com - Tu empresa se ha dado de baja");
       $this->html_envio_correo_darme_debaja($empresa);

        return array('status' => 'success', 'message'=> 'estado empresa actualizado', 'data' => null);
    }

    public function darmeDeAlta(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        $empresa = $this->verEmpresa($data);

        if($empresa['status'] == 'fail') {
            return array(
                'status' => 'errorEmpresaNoExiste',
                'message'=> 'La empresa no esta registrada.',
                'data' => null
            );
        }
        $empresa = $empresa['data'];
        $estado = 0;

        if ( $empresa['primer_login'] > 0 ) {
            $estado = 1;
        }

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            estado = '$estado',
            fecha_actualizacion = '$fecha'
        WHERE id = '$empresa[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'empresa no actulizada', 'data' => null);

        
       // $plantillaHTML = $this->htmlEmailDarseDeAlta($empresa);
       // $this->sendEmailByAsunto($empresa, $plantillaHTML, "nasbi.com - Tu empresa se ha dado de alta");
       $this->html_envio_correo_darme_de_alta($empresa);

        return array('status' => 'success', 'message'=> 'estado empresa actualizado', 'data' => null);
    }

    public function restablecerPassword(Array $data)
    {
        if(!isset($data) || !isset($data['token']) || !isset($data['clave']) ) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::addLogJB("----+> 1. RECUPERAR CONTRASEÑA EMPRESA: " . json_encode($data));

        $porciones = explode("index.php?lang=ES", $data['token']);

        $data['token'] = $porciones[0];

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        $empresa = $this->verEmpresaToken($data);

        parent::addLogJB("----+> 2. RECUPERAR CONTRASEÑA EMPRESA | DATOS EMPRESA: " . json_encode($empresa));

        if($empresa['status'] == 'fail') return $empresa;
        $empresa = $empresa['data'];

        if($empresa['validar_token'] == 0) return array('status' => 'tokenVencido', 'message'=> 'token vencido', 'data' => null);

        parent::conectar();
        $updatexempresa = 
        "UPDATE empresas 
        SET 
            clave = '$data[clave]',
            fecha_actualizacion = '$fecha'
        WHERE id = '$empresa[id]';";
        $update = parent::query($updatexempresa);

        parent::addLogJB("----+> 3. QUERY UPDATE: " . $updatexempresa);
        parent::addLogJB("----+> 4. QUERY UPDATE: " . ($update == 0? "NO" : "SI"));
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'clave no actualizada', 'data' => null);

        return array('status' => 'success', 'message'=> 'clave actualizada', 'data' => null);
    }

    public function cambiarContraseña(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['clave_anterior']) || !isset($data['clave_nueva'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $empresa = $this->verEmpresa($data);
        if($empresa['status'] == 'fail') return $empresa;
        $empresa = $empresa['data'];

        if ($empresa['clave'] != $data['clave_anterior']) return array('status' => 'fail', 'message'=> 'calve incorrecta', 'data' => null);
    }

    public function cambiarPassEmpresa(Array $data)
    {

        if(!isset($data) || !isset($data['id']) || !isset($data['clave_anterior']) || !isset($data['clave_nueva'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $empresa = $this->verEmpresa($data);
        if($empresa['status'] == 'fail') return $empresa;
        $empresa = $empresa['data'];

        if ($empresa['clave'] != $data['clave_anterior']) return array('status' => 'fail', 'message'=> 'calve incorrecta', 'data' => null);

        $fecha = intval(microtime(true)*1000);

        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            clave = '$data[clave_nueva]',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'datos no actualizados', 'data' => null);

        return array('status' => 'success', 'message'=> 'datos actualizados', 'data' => null);
    }

    public function home(Array $data)
    {
        if(!isset($data) || !isset($data['pais'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        parent::conectar();
        $selecthomeOLD = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT e.* 
                FROM empresas e
                JOIN (SELECT @row_number := 0) r
                WHERE e.pais = '$data[pais]' AND e.estado = 1
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";

        $selecthome = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT e.* 
                FROM empresas e
                JOIN (SELECT @row_number := 0) r
                WHERE (e.pais = '$data[pais]' AND e.estado = 1) AND (3 < (SELECT COUNT(p.uid) FROM productos p WHERE p.uid = e.id AND p.empresa = 1)) AND (e.idioma IS NOT NULL AND e.cargo IS NOT NULL AND e.nit IS NOT NULL AND e.foto_asesor IS NOT NULL AND e.descripcion IS NOT NULL AND e.pais IS NOT NULL AND e.tipo_empresa IS NOT NULL AND e.nombre_empresa IS NOT NULL AND e.telefono IS NOT NULL AND e.razon_social IS NOT NULL AND e.nombre_dueno IS NOT NULL AND e.foto_logo_empresa IS NOT NULL AND e.foto_portada_empresa IS NOT NULL)
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        // echo $selecthome;
        $empresas = parent::consultaTodo($selecthome);
        if(count($empresas) <= 0) {
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron empresas', 'pagina'=> $pagina, 'total_paginas'=> 0, 'empresas' => 0, 'total_empresas' => 0, 'data' => null);
        }
        
        parent::conectar();
        $empresas = $this->mapEmpresa($empresas, true);
       // parent::cerrar();

        $selecttodos = "SELECT COUNT(e.id) AS contar 
        FROM empresas e 
        WHERE (e.pais = '$data[pais]' AND e.estado = 1) AND (3 < (SELECT COUNT(p.uid) FROM productos p WHERE p.uid = e.id AND p.empresa = 1)) AND (e.idioma IS NOT NULL AND e.cargo IS NOT NULL AND e.nit IS NOT NULL AND e.foto_asesor IS NOT NULL AND e.descripcion IS NOT NULL AND e.pais IS NOT NULL AND e.tipo_empresa IS NOT NULL AND e.nombre_empresa IS NOT NULL AND e.telefono IS NOT NULL AND e.razon_social IS NOT NULL AND e.nombre_dueno IS NOT NULL AND e.foto_logo_empresa IS NOT NULL AND e.foto_portada_empresa IS NOT NULL)
        ORDER BY fecha_creacion;";
        $todoslosempresas = parent::consultaTodo($selecttodos);
        $todoslosempresas = floatval($todoslosempresas[0]['contar']);
        $totalpaginas = $todoslosempresas/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        
        return array('status' => 'success', 'message'=> 'empresas', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'empresas' => count($empresas), 'total_empresas' => $todoslosempresas, 'data' => $empresas);
    }

    public function productosDestacadosEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectxproductos = "SELECT epd.*
        FROM empresa_productos_destacados epd 
        WHERE p.uid = '$data[id]' AND p.empresa = '1' AND estado = '1'
        ORDER BY cantidad_vendidas DESC;";
        $productos = parent::consultaTodo($selectxproductos);
        parent::cerrar();

        if(count($productos) <= 0) return array('status' => 'fail', 'message'=> 'no datos productos', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'datos productos', 'data' => $productos);
    }

    public function solicitudActivarSubastas(Array $data)
    {
        // - En el auth de empresas viene una variable llamada [status_solicitud_activar_subastas] la cual indica:
        // [status_solicitud_activar_subastas = 0]: No ha enviado solicitud.
        // [status_solicitud_activar_subastas = 1]: Opcion bloqueada
        // [status_solicitud_activar_subastas = 2]: Solicitud enviada.
        // [status_solicitud_activar_subastas = 2]: Solicitud activada.
        if( !isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $updatexempresa = "UPDATE empresas 
        SET 
            status_solicitud_activar_subastas = '1',
            fecha_actualizacion = '$fecha'
        WHERE id = '$data[id]';";
        
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'solicitud no ejecutada', 'data' => null);
        
        return  array('status' => 'success', 'message'=> 'solicitud enviada con exito', 'data' => null);
    }

    public function insertarProductosDestacadosEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['id_producto']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        
        $fecha = intval(microtime(true)*1000);
        parent::conectar();

        $updatexproductos = "UPDATE empresa_productos_destacados
        SET
            estado = 0,
            fecha_actualizacion = '$fecha'
        WHERE uid = '$data[id]' AND empresa = 1 tipo = '$data[tipo]';";
        $update = parent::query($updatexproductos);

        $insertxproductos = "INSERT INTO empresa_productos_destacados
        (
            uid,
            empresa,
            id_producto,
            estado,
            tipo,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id]',
            '1',
            '$data[id_producto]',
            '1',
            '$data[tipo]',
            '$fecha',
            '$fecha'
        );
        ";
        $insert = parent::queryRegistro($insertxproductos);
        parent::cerrar();

        if(!$insert) return array('status' => 'fail', 'message'=> 'no producto registrado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'producto registrado', 'data' => $insert);

    }

    function productosEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        // parent::conectar();
        $selectxproductos = "SELECT p.id, p.foto_portada, p.producto, p.marca, p.modelo, p.titulo 
        FROM productos p 
        WHERE p.uid = '$data[id]' AND p.empresa = '1' AND pais = '$data[pais]'
        ORDER BY cantidad_vendidas DESC, ultima_venta DESC
        LIMIT 6;";
        $productos = parent::consultaTodo($selectxproductos);
        // parent::cerrar();

        if(count($productos) <= 0) return array('status' => 'fail', 'message'=> 'no datos productos', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'datos productos', 'data' => $productos);
    }

    function subirFotoEmpresa(Array $data)
    {
        $nombre_fichero_principal = $_SERVER['DOCUMENT_ROOT']."/imagenes/empresas/";
        if (!file_exists($nombre_fichero_principal)) mkdir($nombre_fichero_principal, 0777, true);
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'].'/imagenes/empresas/'.$data['id'];
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);

        $imgconcat = $data['tipo'].'_'.$data['fecha'];
        $url = $this->uploadImagen([
            'img' => $data['img'],
            'ruta' => '/imagenes/empresas/'.$data['id'].'/'.$imgconcat.'.png',
        ]);
        return $url;
    }

    function uploadImagen(Array $data)
    {
        $base64 = base64_decode(explode(',', $data['img'])[1]);
        $filepath1 = $_SERVER['DOCUMENT_ROOT'] . $data['ruta'];
        file_put_contents($filepath1, $base64);
        $url = $_SERVER['SERVER_NAME'] . $data['ruta'];
        return 'https://'.$url;
    }

    function verificarHistorialLoginEmpresa(Array $data)
    {
        $historial = $this->historialLoginEmpresa($data);
        if($historial['status'] == 'success') return $this->actualizarHistorialLoginEmpresa($historial['data']);

        return $this->insertarHistorialLoginEmpresa($data);
    }

    function historialLoginEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['dispositivo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        parent::conectar();
        $selectxempresa = "SELECT eld.* FROM empresa_login_dispositivos eld WHERE eld.id_empresa = '$data[id]' AND eld.dispositivo = '$data[dispositivo]';";
        $empresa = parent::consultaTodo($selectxempresa);
        parent::cerrar();

        if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);
        
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa[0]);
    }

    function actualizarHistorialLoginEmpresa(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['dispositivo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

        $cantidad = $data['veces_login'] + 1;
        $fecha = intval(microtime(true)*1000);

        parent::conectar();
        $updatexempresa = "UPDATE empresa_login_dispositivos
        SET veces_login = '$cantidad',
        fecha_actualizacion = '$fecha'
        WHERE id = '$data[id_empresa]' AND dispositivo = '$data[dispositivo]';";
        $update = parent::query($updatexempresa);
        parent::cerrar();

        if(!$update) return array('status' => 'fail', 'message'=> 'historial login no actualizado', 'data' => null);
        
        return  array('status' => 'success', 'message'=> 'historial login actualizado', 'data' => null);
    }

    function insertarHistorialLoginEmpresa($data){

        parent::conectar();
        $insertxempresa = "INSERT INTO empresa_login_dispositivos
        (
            id_empresa,
            dispositivo,
            veces_login,
            fecha_actualizacion,
            fecha_creacion
        )
        VALUES
        (
            '$data[id]',
            '$data[dispositivo]',
            1,
            '$data[fecha]',
            '$data[fecha]'
        );";
        $instert = parent::queryRegistro($insertxempresa);
        parent::cerrar();

        if(!$instert) return array('status' => 'fail', 'message'=> 'historial login no actualizado', 'data' => null);
        
        return  array('status' => 'success', 'message'=> 'historial login actualizado', 'data' => null);
    }

    function mapEmpresa(Array $empresas, Bool $productos = false, Bool $conexion_encendida = true)
    {
        foreach ($empresas as $x => $empresa) {
            if ( $empresa['descripcion'] == "...") {
                $empresa['descripcion'] = "";
            }
            if ( $empresa['nombre_empresa'] == "...") {
                $empresa['nombre_empresa'] = $empresa['razon_social'];
            }
            if ( $empresa['cargo'] == "...") {
                $empresa['cargo'] = "";
            }
            if ( $empresa['razon_social'] == "...") {
                $empresa['razon_social'] = "";
            }
            if ( $empresa['tipo_empresa'] == "...") {
                $empresa['tipo_empresa'] = "0";
            }
            if ( $empresa['foto_docuemento_empresa'] == "...") {
                $empresa['foto_docuemento_empresa'] = "";
            }
            if ( $empresa['foto_documento_dueno'] == "...") {
                $empresa['foto_documento_dueno'] = "";
            }
            if ( $empresa['foto_logo_empresa'] == "...") {
                $empresa['foto_logo_empresa'] = "";
            }
            if ( $empresa['foto_portada_empresa'] == "...") {
                $empresa['foto_portada_empresa'] = "";
            }
            if ( $empresa['numero_documento_dueno'] == "...") {
                $empresa['numero_documento_dueno'] = "";
            }
            if ( $empresa['apellido_dueno'] == "...") {
                $empresa['apellido_dueno'] = "";
            }
            if ( $empresa['nombre_dueno'] == "...") {
                $empresa['nombre_dueno'] = "";
            }

            $empresa['uid'] = floatval($empresa['id']);
            $empresa['id'] = floatval($empresa['id']);
            $empresa['empresa'] = 1;
            $empresa['pais'] = floatval($empresa['pais']);
            $empresa['nit'] = $empresa['nit'];
            $empresa['razon_social'] = $empresa['razon_social'];
            $empresa['tipo_documento_dueno'] = floatval($empresa['tipo_documento_dueno']);
            $empresa['estado'] = floatval($empresa['estado']);
            $empresa['fecha_creacion'] = floatval($empresa['fecha_creacion']);
            $empresa['fecha_actualizacion'] = floatval($empresa['fecha_actualizacion']);


            $empresa['nombreCompleto'] = $empresa['nombre_empresa'];

            $empresa['tipo_empresa'] = floatval($empresa['tipo_empresa']);
            $empresa['descripcion'] = $empresa['descripcion'];
            $empresa['cargo'] = $empresa['cargo'];
            $empresa['caracteristica_principal_1'] = floatval($empresa['caracteristica_principal_1']);
            $empresa['caracteristica_principal_2'] = floatval($empresa['caracteristica_principal_2']);
            $empresa['caracteristica_principal_3'] = floatval($empresa['caracteristica_principal_3']);
            
            $empresa['status_solicitud_activar_subastas'] = floatval($empresa['status_solicitud_activar_subastas']);

            $empresa['actividad_economica']   = null;
            $empresa['Agrupacion_por_Tarifa'] = null;
            $empresa['Tarifa_por_Mil']        = null;

            if ( isset( $empresa['id_ciiu'] ) && $empresa['id_ciiu'] != null) {
                $selectxempresaxciud = "SELECT * FROM Ciiu c WHERE c.codigo = '$empresa[id_ciiu]';";
                $ciiuData = parent::consultaTodo($selectxempresaxciud);
                if ( count( $ciiuData ) > 0 ) {
                    $ciiuData = $ciiuData[0];
                    $empresa['actividad_economica']   = $ciiuData['Descripcion'];
                    $empresa['Agrupacion_por_Tarifa'] = $ciiuData['Agrupacion_por_Tarifa'];
                    $empresa['Tarifa_por_Mil']        = $ciiuData['Tarifa_por_Mil'];
                }
              
            }

            if($productos == true) $empresa['productos'] = $this->productosEmpresa($empresa)['data'];
            $empresas[$x] = $empresa;
        }

        return $empresas;
    }


    function mapEmpresaLogin(Array $empresas, Bool $productos = false)
    {
        foreach ($empresas as $x => $empresa) {

            if ( $empresa['descripcion'] == "...") {
                $empresa['descripcion'] = "";
            }
            if ( $empresa['nombre_empresa'] == "...") {
                $empresa['nombre_empresa'] = $empresa['razon_social'];
            }
            if ( $empresa['cargo'] == "...") {
                $empresa['cargo'] = "";
            }
            if ( $empresa['razon_social'] == "...") {
                $empresa['razon_social'] = "";
            }
            if ( $empresa['tipo_empresa'] == "...") {
                $empresa['tipo_empresa'] = "0";
            }
            if ( $empresa['foto_docuemento_empresa'] == "...") {
                $empresa['foto_docuemento_empresa'] = "";
            }
            if ( $empresa['foto_documento_dueno'] == "...") {
                $empresa['foto_documento_dueno'] = "";
            }
            if ( $empresa['foto_logo_empresa'] == "...") {
                $empresa['foto_logo_empresa'] = "";
            }
            if ( $empresa['foto_portada_empresa'] == "...") {
                $empresa['foto_portada_empresa'] = "";
            }
            if ( $empresa['numero_documento_dueno'] == "...") {
                $empresa['numero_documento_dueno'] = "";
            }
            if ( $empresa['apellido_dueno'] == "...") {
                $empresa['apellido_dueno'] = "";
            }
            if ( $empresa['nombre_dueno'] == "...") {
                $empresa['nombre_dueno'] = "";
            }

            $empresa['uid'] = floatval($empresa['id']);
            $empresa['id'] = floatval($empresa['id']);
            $empresa['empresa'] = 1;
            $empresa['pais'] = floatval($empresa['pais']);
            $empresa['nit'] = floatval($empresa['nit']);
            $empresa['razon_social'] = $empresa['razon_social'];
            $empresa['tipo_documento_dueno'] = floatval($empresa['tipo_documento_dueno']);
            $empresa['estado'] = floatval($empresa['estado']);
            $empresa['primer_login'] = floatval($empresa['primer_login']);
            $empresa['fecha_creacion'] = floatval($empresa['fecha_creacion']);
            $empresa['fecha_actualizacion'] = floatval($empresa['fecha_actualizacion']);
            
            $empresa['tipo_empresa'] = floatval($empresa['tipo_empresa']);
            $empresa['descripcion'] = $empresa['descripcion'];
            $empresa['cargo'] = $empresa['cargo'];
            $empresa['caracteristica_principal_1'] = floatval($empresa['caracteristica_principal_1']);
            $empresa['caracteristica_principal_2'] = floatval($empresa['caracteristica_principal_2']);
            $empresa['caracteristica_principal_3'] = floatval($empresa['caracteristica_principal_3']);
            
            $empresa['status_solicitud_activar_subastas'] = floatval($empresa['status_solicitud_activar_subastas']);

            $empresa['user'] = $empresa['nombre_empresa'];
            $empresa['username'] = $empresa['razon_social'];
            $empresa['nombreCompleto'] = $empresa['nombre_empresa'];
            /* $empresa['staus_subasta'] = $empresa['staus_subasta']; */
            unset($empresa['clave']);
            if($productos == true) $empresa['productos'] = $this->productosEmpresa($empresa)['data'];
            
            $empresas[$x] = $empresa;
        }

        return $empresas;
    }

    function htmlEmailToken( Array $data )
    {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>¡Gracias por enviar tu solicitud a Nasbi!</title>
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
                                                <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Gracias por enviar tu solicitud a Nasbi!</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                    ¡Gracias por enviar tu solicitud a Nasbi! Estás a un paso de completar tu registro. Una vez revisada la información recibirás un correo electrónico el cual determinará si tu empresa fue aprobada.
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
                                                    Empresa:&nbsp; '.$data['nombre_empresa'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Identificación tributaria:&nbsp; '.$data['nit'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Tu usuario de inicio de sesión:&nbsp;'.$data['correo'].'
                                                    <br>
                                                    <a href="https://nasbi.com/content/nueva-pass-em.php?t='.$data['token'].'">Restablcer contraseña</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Correo electrónico:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Proveedor del servicio:&nbsp;&#8234;Nasbi&#8236;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                    <b>
                                                        Haz parte del <span style="color: #ea4262 !important;">marketplace más innovador </span>del mercado.
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

    function htmlEmailWelcome( Array $data )
    {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>¡Gracias por enviar tu solicitud a Nasbi!</title>
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
                                                <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Gracias por enviar tu solicitud a Nasbi!</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                    ¡Gracias por enviar tu solicitud a Nasbi! Estás a un paso de completar tu registro. Una vez revisada la información recibirás un correo electrónico el cual determinará si tu empresa fue aprobada.
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
                                                    Empresa:&nbsp; '.$data['nombre_empresa'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Identificación tributaria:&nbsp; '.$data['nit'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Tu usuario de inicio de sesión:&nbsp;'.$data['correo'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Correo electrónico:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Proveedor del servicio:&nbsp;&#8234;Nasbi&#8236;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                    <b>
                                                        Haz parte del <span style="color: #ea4262 !important;">marketplace más innovador </span>del mercado.
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

    function htmlEmailDarseDeBaja( Array $data )
    {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>¡Gracias por enviar tu solicitud a Nasbi!</title>
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
                                                <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Gracias por enviar tu solicitud a Nasbi!</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                    ¡Gracias por enviar tu solicitud a Nasbi! Estás a un paso de completar tu registro. Una vez revisada la información recibirás un correo electrónico el cual determinará si tu empresa fue aprobada.
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
                                                    Empresa:&nbsp; '.$data['nombre_empresa'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Identificación tributaria:&nbsp; '.$data['nit'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Tu usuario de inicio de sesión:&nbsp;'.$data['correo'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Correo electrónico:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Estado actual en la plataforma:&nbsp;Empresa dada de baja
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Proveedor del servicio:&nbsp;&#8234;Nasbi&#8236;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                    <b>
                                                        Haz parte del <span style="color: #ea4262 !important;">marketplace más innovador </span>del mercado.
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

    function htmlEmailDarseDeAlta( Array $data )
    {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>¡Gracias por enviar tu solicitud a Nasbi!</title>
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
                                                <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Gracias por enviar tu solicitud a Nasbi!</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                    ¡Gracias por enviar tu solicitud a Nasbi! Estás a un paso de completar tu registro. Una vez revisada la información recibirás un correo electrónico el cual determinará si tu empresa fue aprobada.
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
                                                    Empresa:&nbsp; '.$data['nombre_empresa'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Identificación tributaria:&nbsp; '.$data['nit'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Tu usuario de inicio de sesión:&nbsp;'.$data['correo'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Correo electrónico:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Estado actual en la plataforma:&nbsp;Empresa dada de alta
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Proveedor del servicio:&nbsp;&#8234;Nasbi&#8236;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                    <b>
                                                        Haz parte del <span style="color: #ea4262 !important;">marketplace más innovador </span>del mercado.
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

    function htmlEmailConfirmacion( Array $data )
    {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>Verificación de tu empresa</title>
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
                                                <td align="middle" style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 20px; font-weight: normal; line-height: 24px; padding: 0px 34px 25px 34px;">¡Gracias por enviar tu solicitud a Nasbi!</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">Hola:</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #707070; font-size: 14px; padding: 0px 34px 25px 34px; line-height: 20px; font-weight: lighter;">
                                                    ¡Gracias por enviar tu solicitud a Nasbi! Estás a un paso de completar tu registro.
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
                                                    Empresa:&nbsp; '.$data['razon_social'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Identificación tributaria:&nbsp; '.$data['nit'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Tu usuario de inicio de sesión:&nbsp;'.$data['correo'].'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Código para activar tu cuenta:&nbsp;'. md5( $data['uid'] ) .'
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Correo electrónico:&nbsp;<a href="mailto:' . $data['correo'] . '" target="_blank">'. explode( '@', $data['correo'])[0] .'@<wbr/>'. explode( '@', $data['correo'])[1] .'</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    URL confirmación:&nbsp;<a href="https://nasbi.com/content/index.php" target="_blank">
                                                        https://nasbi.com/content/index.php
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="m_-6866340598297024620account-info-row"
                                                    style="font-family: Helvetica, Arial, sans-serif; color: rgb(43, 43, 43); font-size: 14px; line-height: 20px; font-weight: lighter; padding: 0px 34px 0px 34px;"
                                                >
                                                    Proveedor del servicio:&nbsp;&#8234;Nasbi&#8236;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: Helvetica, Arial, sans-serif; color: #232a85; font-size: 14px; line-height: 20px; font-weight: lighter; padding: 20px 34px 20px 34px;">
                                                    <b>
                                                        Haz parte del <span style="color: #ea4262 !important;">marketplace más innovador </span>del mercado.
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

    function sendEmail($dato, $htmlPlantilla)
    {

        $desde = "info@nasbi.com";
        $para  = $dato['correo'];

        $titulo     = "¡Gracias por enviar tu solicitud a Nasbi!";

        $mensaje1   = $htmlPlantilla;

        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $cabeceras .= 'From: '. $desde . "\r\n";

        $dataArray = array("email" => $para, "titulo" => $titulo, "mensaje" => $mensaje1, "cabeceras" => $cabeceras);
        
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        $respuesta = json_decode($response, true);
        return $respuesta;
    }

    function sendEmailByAsunto($dato, $htmlPlantilla, $asunto)
    {

        $desde = "info@nasbi.com";
        $para  = $dato['correo'];

        $titulo     = $asunto;

        $mensaje1   = $htmlPlantilla;

        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $cabeceras .= 'From: '. $desde . "\r\n";

        $dataArray = array("email" => $para, "titulo" => $titulo, "mensaje" => $mensaje1, "cabeceras" => $cabeceras);
        
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        $respuesta = json_decode($response, true);
        return $respuesta;
    }

    function listarEmpresas(){
        parent::conectar();
        $selectxempresa = "SELECT e.* FROM empresas e;";
        $empresas = parent::consultaTodo($selectxempresa);
        return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresas);
        parent::cerrar();
    }

    function htmlEmailToken2(Array $data){ //recuperar pass
        $fecha = getdate();
        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico 
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo5hasolvidadotucontrasena.html");
        $html = str_replace("{{trans01_brand}}", $json->trans01_brand, $html); 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans02}}", $json->trans02, $html);
        $html = str_replace("{{nombre_usuario}}", $data['razon_social'], $html);

        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);
        
        $html = str_replace("{{trans03}}", $json->trans03, $html);
        $html = str_replace("{{trans04}}", $json->trans04, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{link_restablecer_pass_em}}", "https://nasbi.com/content/nueva-pass-em.php?t=".$data['token'], $html);
        
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data["uid"]."&act=0&em=".$data["empresa"], $html); 


        $para      = $data['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = ucfirst($data['razon_social'])." ".$json->trans102_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function htmlEmailConfirmacion2(Array $data){

        parent::addLog(" v.7 Request Gisel: " . json_encode( $data) );
        // Descomentar cuando el idioma vuelva hacer dinamico 
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo1codigoseguridad.html");
        $html = str_replace("{{trans_brand}}", $json->trans_brand, $html);
        $html = str_replace("{{trans01_}}", $json->trans01_, $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{nombre_usuario}}",$data['razon_social'], $html);
        $html = str_replace("{{trans03_}}", $json->trans03_, $html);
        $html = str_replace("{{trans04_}}", md5($data['uid']), $html);
        $html = str_replace("{{trans05_}}", $json->trans05_, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data["uid"]."&act=0&em=1", $html); 

        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
       

        $para      = $data['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';

        $mensaje1   = $html;
        $titulo    = $json->trans99_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);

        // parent::addLog(" v.8 Request Gisel: " . json_encode( $dataArray) );
        $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

        parent::addLog(" v.9 Request Gisel: " . json_encode( $response) . "[codigo]: " . md5($data['uid']) . " [Destino]: " . $para);
        return $response;
    }

    function htmlEmailResgitrocompletado(Array $data){
        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico 
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo2registrocompletado.html");
        $html = str_replace("{{trans_brand}}", $json->trans_brand, $html);
        $html = str_replace("{{trans_01_brand}}", $json->trans_01_brand, $html);
        $html = str_replace("{{nombre_usuario}}", $data['razon_social'], $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{trans08_}}", $json->trans08_, $html);
        $html = str_replace("{{trans09_}}", $json->trans09_, $html);
        $html = str_replace("{{trans10_}}", $json->trans10_, $html);
        $html = str_replace("{{trans11_}}", $json->trans11_, $html);
        $html = str_replace("{{trans12_}}", $json->trans12_, $html);
        $html = str_replace("{{trans13_}}", $json->trans13_, $html);
        $html = str_replace("{{trans14_}}", $json->trans14_, $html); 
        $html = str_replace("{{trans15_}}", $json->trans15_, $html); 
        $html = str_replace("{{trans16_}}", $json->trans16_, $html); 
        $html = str_replace("{{trans17_}}", $json->trans17_, $html); 
        $html = str_replace("{{trans18_}}", $json->trans18_, $html); 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{link_a_promociones}}", "https://nasbi.com/content/descubre.php", $html);
        $html = str_replace("{{link_a_escuela}}", "https://nasbi.com/content/escuela-vendedores.php", $html);
        $html = str_replace("{{link_a_marketplace}}", "https://nasbi.com/content/escuela-vendedores.php", $html);

        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data["uid"]."&act=0&em=".$data["empresa"], $html); 
        
    
        $para      = $data['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = ucfirst($data['razon_social'])." ".$json->trans100_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function htmlEmailnuevoespaciotienda(Array $data){
        //  $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa['idioma'].".json")); Descomentar cuando el idioma vuelva hacer dinamico 
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo3tutiendaoficial.html");
        $html = str_replace("{{trans_03_brand}}", $json->trans_03_brand, $html);
        $html = str_replace("{{trans19_}}", $json->trans19_, $html);
        $html = str_replace("{{nombre_usuario}}", $data['razon_social'], $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{trans20_}}", $json->trans20_, $html);
        $html = str_replace("{{trans21_}}",$json->trans21_, $html);
        $html = str_replace("{{trans22_}}",$json->trans22_, $html);
        $html = str_replace("{{trans23_}}",$json->trans23_, $html);
        $html = str_replace("{{trans67_}}",$json->trans67_, $html);
        $html = str_replace("{{link_a_escuela}}", "https://nasbi.com/content/escuela-vendedores.php", $html);
        $html = str_replace("{{link_a_vender}}", "https://nasbi.com/content/vender.php", $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data["uid"]."&act=0&em=".$data["empresa"], $html); 
       
    
        $para      = $data['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans101_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function consultar_data_de_empresa_por_id(String $id){

        parent::conectar();
        $selectempresa = " SELECT * FROM empresas WHERE  id = '$id'";
        $dataempresa = parent::consultaTodo($selectempresa);
        parent::cerrar();

        return $dataempresa[0]; 
    }

    function crearAddressFaltantes(Array $data){
        parent::conectar();

        $arraysEmpresas = [4,6,7,9,10,12,13,15];

        $arrayResult = array();

        foreach ($arraysEmpresas as $key => $value) {
            $fecha = intval(microtime(true)*1000);

            $tablaWallet = 'nasbicoin_gold';
            $moneda = "Nasbigold";
            $address = md5($value . microtime() . rand());
            $insterarxnasbicoin = 
                "INSERT INTO $tablaWallet
                (
                    uid,
                    address,
                    monto,
                    moneda,
                    estado,
                    empresa,
                    fecha_creacion,
                    fecha_actualizacion
                )
                VALUES
                (
                    '$value',
                    '$address',
                    '0',
                    '$moneda',
                    '1',
                    '1',
                    '$fecha',
                    '$fecha'
                );";
            $insertarWalletGold = parent::queryRegistro($insterarxnasbicoin);
            array_push($arrayResult, array('uid' => $value, 'result' => $insertarWalletGold, 'moneda' => $moneda));


            
            $tablaWallet = 'nasbicoin_blue';
            $moneda = "Nasbiblue";
            $address = md5($value . microtime() . rand());
            $insterarxnasbicoin = 
                "INSERT INTO $tablaWallet
                (
                    uid,
                    address,
                    monto,
                    moneda,
                    estado,
                    empresa,
                    fecha_creacion,
                    fecha_actualizacion
                )
                VALUES
                (
                    '$value',
                    '$address',
                    '0',
                    '$moneda',
                    '1',
                    '1',
                    '$fecha',
                    '$fecha'
                );";
            $insertarWalletBlue = parent::queryRegistro($insterarxnasbicoin);
            array_push($arrayResult, array('uid' => $value, 'result' => $insertarWalletBlue, 'moneda' => $moneda));
        }
        parent::cerrar();

        return  array('status' => 'success', 'data' => $arrayResult);
    }

    function html_envio_correo_darme_debaja(Array $data){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/dar_de_baja/correo1dar_baja.html");
        
        $html = str_replace("{{trans01_brand}}", $json->trans01_brand, $html); 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans02}}", $json->trans02, $html);
        $html = str_replace("{{nombre_usuario}}", $data['razon_social'], $html);

        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{trans03}}", $json->trans03, $html);

        $html = str_replace("{{trans154_}}", $json->trans154_, $html);
        $html = str_replace("{{trans155_}}", $json->trans155_, $html);

        $html = str_replace("{{trans04}}", $json->trans04, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);

        $para      = $data['correo'] . ',dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = ucfirst($data['razon_social'])." ".$json->trans157_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    function html_envio_correo_darme_de_alta(Array $data_empresa){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/dar_de_baja/correo2dar_alta.html");

        $html = str_replace("{{nombre_usuario}}",ucfirst($data_empresa['razon_social']), $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{trans36_brand}}",$json->trans36_brand, $html);


        $html = str_replace("{{trans158_}}",$json->trans158_, $html);
        $html = str_replace("{{trans159_}}",$json->trans159_, $html);
        $html = str_replace("{{trans39}}",$json->trans39, $html);
        $html = str_replace("{{trans34}}",$json->trans34, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_empresa["uid"]."&act=0&em=".$data_empresa["empresa"], $html); 

        $para      = $data_empresa['correo'];
        $mensaje1   = $html;
        $titulo    = $json->trans159_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";

        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
    }

    public function saber_existencia_correo( Array $data )
    {
        if(!isset($data) || !isset($data['correo'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);
        // parent::addLog(" v.4 Request Gisel: " . json_encode( $data) );
        $data["correo"] = addslashes($data["correo"]); 
        parent::conectar();
        $selectxempresa = "SELECT e.* FROM empresas e WHERE e.correo = '$data[correo]';";
        $empresa = parent::consultaTodo($selectxempresa);
        parent::cerrar();

        if(count($empresa) <= 0) return array('status' => 'success', 'message'=> 'no esta el correo');
        
        return  array('status' => 'fail', 'message'=> 'correo si existe');
    }

    public function obtenerInformacionDelNegocio( Array $data )
    {
        if( !isset( $data ) || !isset( $data['uid'] ) || !isset( $data['empresa'] ) ) {
            return array(
                'status'  => 'fail',
                'message' => 'no data',
                'data'    => null
            );
        }

        // (x) 1. Nombre del propietario
        // (x) 2. Nombre comercial
        // (x) 3. Fecha de ingreso
        // (x) 4. Estado: ON/OFF
        // (x) 5. Cuanto tiempo llevamos juntos: Días.
        // 6. Articulo más vendido.
        // (x) 7. Ventas realizadas.
        // (x) 8. Venta más costosa.
        // 9. Compras realizadas.
        // 10. Compra más costosa.

        // 11. Pedidos completados.
        // 12. Pedidos pendientes.

        $resultEstadistico = array(
            'nombre_propietario'            => '',
            'razon_social'                  => '',
            'fecha_ingreso'                 => 0,

            'estado_actual'                 => true,
            'estado_actual_descripcion'     => 'Activo',

            'antiguedad'                    => 0,
            'antiguedad_unidad_medida'      => '',
            'antiguedad_unidad_descripcion' => 'S: segundos, M: Minutos, H: hora, D: Dia, M: Mes, A: Año',

            'articulo_mas_vendido'          => null,

            'total_ventas'                  => 0,
            'total_ventas_mask'             => 0,
            'venta_mas_valiosa'             => null,
            'total_compras'                 => 0,
            'total_compras_mask'            => 0,
            'compra_mas_valiosa'            => null,
            'envios_completados'            => 0,
            'envios_completados_mask'       => 0,
            'envios_pendientes'             => 0,
            'envios_pendientes_mask'        => 0
        );

        $selectxdatosxusuario = "";
        $seelctxformxpagoxdigital = "";


        if ( $data['empresa'] == 0 ) {
            $selectxdatosxusuario = "SELECT * FROM peer2win.usuarios WHERE id = $data[uid];";
            parent::conectar();
            $selectxdatosxusuario = parent::consultaTodo( $selectxdatosxusuario );
            parent::cerrar();
            
            if ( count( $selectxdatosxusuario ) <= 0 ) {
                return array(
                    'status'  => 'errorNoData',
                    'message' => 'No se encontraron datos para la key enviada [' . $data['uid'] . ', ' . $data['empresa'] . ']',
                    'data'    => null
                );
            }
            $selectxdatosxusuario = $selectxdatosxusuario[ 0 ];
            
            $selctxformxpagoxdigital = "SELECT * FROM buyinbig.datos_persona_natural WHERE user_uid = $data[uid] AND user_empresa = $data[empresa];";
            // var_dump($selctxformxpagoxdigital);
            parent::conectar();
            $selctxformxpagoxdigital = parent::consultaTodo( $selctxformxpagoxdigital );
            parent::cerrar();
            if ( count( $selctxformxpagoxdigital ) <= 0 ) {
                $selctxformxpagoxdigital = array(
                    'nombre_comercial' => ''
                );
            }else{
                $selctxformxpagoxdigital           = $selctxformxpagoxdigital[ 0 ];

            }

            $resultEstadistico['nombre_propietario']       = $selectxdatosxusuario['nombreCompleto'];
            $resultEstadistico['razon_social']             = $selctxformxpagoxdigital['nombre_comercial'];

            $resultEstadistico['fecha_ingreso']            = $selectxdatosxusuario['fecha_ingreso'];

            // $dateRegistro                                  = new DateTime($resultEstadistico['fecha_ingreso']);
            // $timestampRegistro                             = $dateRegistro->getTimestamp();
            // $resultEstadistico['antiguedad']               = (intval(microtime(true)*1000) - $timestampRegistro) / (1000*60*60*24);

            
            // $resultEstadistico['antiguedad_registro']      = $timestampRegistro;
            // $resultEstadistico['antiguedad_hoy']           = intval(microtime(true)*1000);
            // $resultEstadistico['antiguedad_resta']         = (intval(microtime(true)*1000) - $timestampRegistro);
            // $resultEstadistico['antiguedad_div']           = (intval(microtime(true)*1000) - $timestampRegistro) / (1000*60*60*24);


            $resultEstadistico['antiguedad_unidad_medida'] = 'd';
        }else{
            $selectxdatosxusuario = "SELECT * FROM buyinbig.empresas WHERE id = $data[uid];";
            parent::conectar();
            $selectxdatosxusuario = parent::consultaTodo( $selectxdatosxusuario );
            parent::cerrar();

            if ( count( $selectxdatosxusuario ) <= 0 ) {
                return array(
                    'status'  => 'errorNoData',
                    'message' => 'No se encontraron datos para la key enviada [' . $data['uid'] . ', ' . $data['empresa'] . ']',
                    'data'    => null
                );
            }
            $selectxdatosxusuario    = $selectxdatosxusuario[ 0 ];
            $selctxformxpagoxdigital = "SELECT * FROM buyinbig.datos_persona_juridica WHERE user_uid = $data[uid] AND user_empresa = $data[empresa];";
            parent::conectar();
            $selctxformxpagoxdigital = parent::consultaTodo( $selctxformxpagoxdigital );
            parent::cerrar();
            if ( count( $selctxformxpagoxdigital ) <= 0 ) {
                $selctxformxpagoxdigital = array(
                    'nombres'          => '',
                    'apellidos'        => '',
                    'nombre_comercial' => ''
                );
            }else{
                $selctxformxpagoxdigital = $selctxformxpagoxdigital[ 0 ];

            }


            $resultEstadistico['nombre_propietario']       = $selctxformxpagoxdigital['nombres'].' '.$selctxformxpagoxdigital['apellidos'];

            $resultEstadistico['razon_social']             = $selctxformxpagoxdigital['nombre_comercial'];

            $resultEstadistico['fecha_ingreso']            = $selectxdatosxusuario['fecha_creacion'];
            $resultEstadistico['fecha_ingreso']            = intval($resultEstadistico['fecha_ingreso']);

            $date                                          = new DateTime(); 
            $date->setTimestamp( $resultEstadistico['fecha_ingreso'] );  
            $resultEstadistico['fecha_ingreso'] = $date->format('U = Y-m-d H:i:s'); 

            // $resultEstadistico['antiguedad']    = (intval(microtime(true)*1000) - $resultEstadistico['fecha_ingreso']) / (1000*60*60*24);
            // $resultEstadistico['antiguedad_unidad_medida'] = 'd';
        }

        $selectxarticuloxmasxvendido = 
        "SELECT 
            DISTINCT(pt.id_carrito) AS 'pt_id_carrito',
            pt.id_producto AS 'pt_id_producto',
            COUNT(pt.id) AS 'pt_unidades_vendidas',

            p.foto_portada,
            pt.precio AS 'pt_precio',
            pt.moneda AS 'pt_moneda',
            p.precio  AS 'p_precio',
            p.titulo,
            p.tipoSubasta

        FROM buyinbig.productos_transaccion pt 
        INNER JOIN buyinbig.productos p ON (pt.id_producto = p.id)
        WHERE pt.uid_vendedor = 1291 AND pt.empresa = 0 AND pt.moneda != 'Nasbiblue'
        GROUP BY pt.id_producto ORDER BY COUNT(pt.id) DESC LIMIT 1;";


        parent::conectar();
        $selectxarticuloxmasxvendido = parent::consultaTodo( $selectxarticuloxmasxvendido );
        parent::cerrar();

        $arrayArticuloMasVendido = array();
        if ( count( $selectxarticuloxmasxvendido ) == 0 ) {
            $arrayArticuloMasVendido = array(
                'titulo'                    => '',
                'foto_portada'              => '',
                'articulo_mas_vendido'      => '',
                'pt_unidades_vendidas'      => 0,
                'pt_unidades_vendidas_mask' => 0,
                'tipoProducto'              => '',
                'producto_precio'           => 0,
                'producto_precio_mask'      => '',
                'transaccion_precio'        => 0,
                'transaccion_precio_mask'   => '',
                'moneda'                    => ''
            );
        }else{
            $selectxarticuloxmasxvendido = $selectxarticuloxmasxvendido[ 0 ];

            $p_precio             = $this->truncNumber( $selectxarticuloxmasxvendido['p_precio'], 2 );
            $pt_precio            = $this->truncNumber( $selectxarticuloxmasxvendido['pt_precio'], 2 );
            $pt_unidades_vendidas = $this->truncNumber( $selectxarticuloxmasxvendido['pt_unidades_vendidas'], 2 );
            
            $p_precio             = floatval($p_precio);
            $p_precio             = floatval($p_precio);
            $pt_unidades_vendidas = floatval($pt_unidades_vendidas);

            $arrayArticuloMasVendido = array(
                'titulo'                    => $selectxarticuloxmasxvendido['titulo'],
                'foto_portada'              => $selectxarticuloxmasxvendido['foto_portada'],
                
                'pt_unidades_vendidas'      => $pt_unidades_vendidas,
                'pt_unidades_vendidas_mask' => $this->maskNumber($pt_unidades_vendidas),

                'tipoProducto'              => floatval($selectxarticuloxmasxvendido['tipoSubasta']),
                'producto_precio'           => floatval(''.$selectxarticuloxmasxvendido['p_precio']),
                'producto_precio_mask'      => $this->maskNumber( $p_precio ),
                'transaccion_precio'        => floatval(''.$selectxarticuloxmasxvendido['pt_precio']),
                'transaccion_precio_mask'   => $this->maskNumber( $pt_precio ),
                'moneda'                    => $selectxarticuloxmasxvendido['pt_moneda']
            );
        }
        $resultEstadistico['articulo_mas_vendido'] = $arrayArticuloMasVendido;


        $selectxtransaccionesxventaxmasxvaliosa = 
            "SELECT 
                p.foto_portada,
                pt.precio AS 'pt_precio',
                pt.moneda AS 'pt_moneda',
                p.precio AS 'p_precio',
                p.titulo,
                p.tipoSubasta
            FROM buyinbig.productos_transaccion pt 
            INNER JOIN buyinbig.productos p ON (pt.id_producto = p.id)
            WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.moneda != 'Nasbiblue'
            ORDER BY pt.precio DESC LIMIT 1;";

        parent::conectar();
        $selectxtransaccionesxventaxmasxvaliosa = parent::consultaTodo( $selectxtransaccionesxventaxmasxvaliosa );
        parent::cerrar();

        $arrayTransaccionVentaMasValiosa = array();
        if ( count( $selectxtransaccionesxventaxmasxvaliosa ) == 0 ) {
            $arrayTransaccionVentaMasValiosa = array(
                'titulo'                  => '',
                'foto_portada'            => '',
                'tipoProducto'            => '',
                'producto_precio'         => 0,
                'producto_precio_mask'    => '',
                'transaccion_precio'      => 0,
                'transaccion_precio_mask' => '',
                'moneda'                  => ''
            );
        }else{
            $selectxtransaccionesxventaxmasxvaliosa = $selectxtransaccionesxventaxmasxvaliosa[ 0 ];

            $p_precio  = $this->truncNumber( $selectxtransaccionesxventaxmasxvaliosa['p_precio'], 2 );
            $pt_precio = $this->truncNumber( $selectxtransaccionesxventaxmasxvaliosa['pt_precio'], 2 );
            
            $p_precio  = floatval($p_precio);
            $pt_precio = floatval($pt_precio);

            $arrayTransaccionVentaMasValiosa = array(
                'titulo'                  => $selectxtransaccionesxventaxmasxvaliosa['titulo'],
                'foto_portada'            => $selectxtransaccionesxventaxmasxvaliosa['foto_portada'],
                'tipoProducto'            => floatval($selectxtransaccionesxventaxmasxvaliosa['tipoSubasta']),
                'producto_precio'         => floatval(''.$selectxtransaccionesxventaxmasxvaliosa['p_precio']),
                'producto_precio_mask'    => $this->maskNumber( $p_precio ),
                'transaccion_precio'      => floatval(''.$selectxtransaccionesxventaxmasxvaliosa['pt_precio']),
                'transaccion_precio_mask' => $this->maskNumber( $pt_precio ),
                'moneda'                  => $selectxtransaccionesxventaxmasxvaliosa['pt_moneda']
            );
        }
        $resultEstadistico['venta_mas_valiosa'] = $arrayTransaccionVentaMasValiosa;

        $selectxtotalxventas = 
            "SELECT 
                SUM( precio ) AS 'total_ventas'
            FROM buyinbig.productos_transaccion pt 
            WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa];";
            
        parent::conectar();
        $selectxtotalxventas = parent::consultaTodo( $selectxtotalxventas );
        parent::cerrar();

        if ( count( $selectxtotalxventas ) > 0 ) {
            $selectxtotalxventas                    = $selectxtotalxventas[ 0 ];
            $resultEstadistico['total_ventas']      = floatval($selectxtotalxventas['total_ventas']);
            $resultEstadistico['total_ventas_mask'] = $this->maskNumber($selectxtotalxventas['total_ventas']);
        }


        $selectxtotalxcompras = 
            "SELECT 
                DISTINCT(id_carrito), COUNT(id) AS 'total_compras'
            FROM buyinbig.productos_transaccion pt 
            WHERE pt.uid_comprador = $data[uid] AND pt.empresa_comprador = $data[empresa];";
            
        parent::conectar();
        $selectxtotalxcompras = parent::consultaTodo( $selectxtotalxcompras );
        parent::cerrar();

        if ( count( $selectxtotalxcompras ) > 0 ) {
            $selectxtotalxcompras                    = $selectxtotalxcompras[ 0 ];
            $resultEstadistico['total_compras']      = floatval($selectxtotalxcompras['total_compras']);
            $resultEstadistico['total_compras_mask'] = $this->maskNumber($selectxtotalxcompras['total_compras']);
        }

        $selectxtransaccionxcompraxmasxvaliosa = 
            "SELECT 
                p.foto_portada,
                pt.precio AS 'pt_precio',
                pt.moneda AS 'pt_moneda',
                p.precio AS 'p_precio',
                p.titulo,
                p.tipoSubasta
            FROM buyinbig.productos_transaccion pt 
            INNER JOIN buyinbig.productos p ON (pt.id_producto = p.id)
            WHERE pt.uid_comprador = $data[uid] AND pt.empresa_comprador = $data[empresa] AND pt.moneda != 'Nasbiblue'
            ORDER BY pt.precio DESC LIMIT 1;";

        parent::conectar();
        $selectxtransaccionxcompraxmasxvaliosa = parent::consultaTodo( $selectxtransaccionxcompraxmasxvaliosa );
        parent::cerrar();

        $arrayTransaccionVentaMasValiosa = array();
        if ( count( $selectxtransaccionxcompraxmasxvaliosa ) == 0 ) {
            $arrayTransaccionVentaMasValiosa = array(
                'titulo'                  => '',
                'foto_portada'            => '',
                'tipoProducto'            => '',
                'producto_precio'         => 0,
                'producto_precio_mask'    => '',
                'transaccion_precio'      => 0,
                'transaccion_precio_mask' => '',
                'moneda'                  => ''
            );
        }else{
            $selectxtransaccionxcompraxmasxvaliosa = $selectxtransaccionxcompraxmasxvaliosa[ 0 ];

            $p_precio  = $this->truncNumber( $selectxtransaccionxcompraxmasxvaliosa['p_precio'], 2 );
            $pt_precio = $this->truncNumber( $selectxtransaccionxcompraxmasxvaliosa['pt_precio'], 2 );
            
            $p_precio  = floatval($p_precio);
            $pt_precio = floatval($pt_precio);

            $arrayTransaccionCompraMasValiosa = array(
                'titulo'                  => $selectxtransaccionxcompraxmasxvaliosa['titulo'],
                'foto_portada'            => $selectxtransaccionxcompraxmasxvaliosa['foto_portada'],
                'tipoProducto'            => floatval($selectxtransaccionxcompraxmasxvaliosa['tipoSubasta']),
                'producto_precio'         => floatval(''.$selectxtransaccionxcompraxmasxvaliosa['p_precio']),
                'producto_precio_mask'    => $this->maskNumber( $p_precio ),
                'transaccion_precio'      => floatval(''.$selectxtransaccionxcompraxmasxvaliosa['pt_precio']),
                'transaccion_precio_mask' => $this->maskNumber( $pt_precio ),
                'moneda'                  => $selectxtransaccionxcompraxmasxvaliosa['pt_moneda']
            );
        }
        $resultEstadistico['compra_mas_valiosa'] = $arrayTransaccionCompraMasValiosa;
        
        $selectxpedidosxcompletados = "SELECT 
            DISTINCT(id_carrito), COUNT(id) AS 'envios_completados'
        FROM buyinbig.productos_transaccion pt 
        WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.estado IN (8, 11, 12, 13);";
        parent::conectar();
        $selectxpedidosxcompletados = parent::consultaTodo( $selectxpedidosxcompletados );
        parent::cerrar();

        if ( count( $selectxpedidosxcompletados ) > 0 ) {
            $selectxpedidosxcompletados                    = $selectxpedidosxcompletados[ 0 ];
            $resultEstadistico['envios_completados']      = floatval($selectxpedidosxcompletados['envios_completados']);
            $resultEstadistico['envios_completados_mask'] = $this->maskNumber($selectxpedidosxcompletados['envios_completados']);
        }


        $selectxpedidosxcompletados = "SELECT 
            DISTINCT(id_carrito), COUNT(id) AS 'envios_pendientes'
        FROM buyinbig.productos_transaccion pt 
        WHERE pt.uid_vendedor = $data[uid] AND pt.empresa = $data[empresa] AND pt.estado NOT IN (8, 11, 12, 13);";
        parent::conectar();
        $selectxpedidosxcompletados = parent::consultaTodo( $selectxpedidosxcompletados );
        parent::cerrar();

        if ( count( $selectxpedidosxcompletados ) > 0 ) {
            $selectxpedidosxcompletados                    = $selectxpedidosxcompletados[ 0 ];
            $resultEstadistico['envios_pendientes']      = floatval($selectxpedidosxcompletados['envios_pendientes']);
            $resultEstadistico['envios_pendientes_mask'] = $this->maskNumber($selectxpedidosxcompletados['envios_pendientes']);
        }

        return $resultEstadistico;
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

    function validarNit(Array $data){
        $selectxempresas = "";

        if( isset( $data['id']) ){
            $selectxempresas = "SELECT * FROM buyinbig.empresas WHERE id != $data[id] AND nit = $data[nit]";

        }else{
            $selectxempresas = "SELECT * FROM buyinbig.empresas WHERE nit = $data[nit]";

        }
        
        parent::conectar();
        $empresas = parent::consultaTodo($selectxempresas);
        parent::conectar();
        
        if( COUNT($empresas) == 0 ){
            return array('status'=>'success', 'message'=>'No hay empresas con ese NIT.', 'data'=> null);
        }else{
            return array('status'=>'errorDocumentoyNumeroDuplicado', 'message'=>'No hay empresas con ese NIT.', 'data'=> null);
        }
    }
}
?>