<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Publicacion_prueba extends Conexion
{
    private $montoxminimoxpublicarxmonedaxlocal = 30000;
    private $montoxminimoxpublicar              = 7.927050001189057; // 5 USD
    private $porcentajexmaximoxpublicar         = 50; // 5 USD
    private $tiempomaxsubasta                   = 86400000; // 24 horas
    private $porcetajexminxrecaudar             = 0.4;

    private $entradaxxubastasxnormales          = 0.029; // porcentaje entrada subastas normales
    private $porcentajexrestarxnormal           = 0.1; // porcentaje entrada subastas normales
    private $addressxrecibextickets             = [
        'uid' => 1333,
        'empresa' => 0,
        'address_Nasbigold' => 'aa97273806290a2ef074a95ef71d1a7b', 
        'address_Nasbiblue' => 'c5551f7d44f2d72f98f5976efe96bfdf',
    ];

    private $json_categorias                     = null;
    private $regex_colores                       = '/^(\s)*\((\s)*(\s*\w+\s*)+(\s)*(-(\s)*(\s*\w+\s*)+(\s)*)*\)((\s)*\|(\s)*\((\s)*(\s*\w+\s*)+(\s)*(-(\s)*(\s*\w+\s*)+(\s)*)*\))*(\s)*$/';
    private $regex_cantidades                    = '/^(\s)*\((\s)*(\d+)+(\s)*(-(\s)*(\d+)+(\s)*)*\)((\s)*\|(\s)*\((\s)*(\d+)+(\s)*(-(\s)*(\d+)+(\s)*)*\))*(\s)*$/';
    private $regex_tallas                        = '/^(\.|\,|\'|\"|\ñ|\á|\é|\í|\ó|\ú|\Á|\É|\Í|\Ó|\Ú|\w|\s|\-|\+)+((\.|\,|\'|\"|\ñ|\á|\é|\í|\ó|\ú|\Á|\É|\Í|\Ó|\Ú|\w|\s|\-|\+)+)*(\|(\.|\,|\'|\"|\ñ|\á|\é|\í|\ó|\ú|\Á|\É|\Í|\Ó|\Ú|\w|\s|\-|\+)+((\.|\,|\'|\"|\ñ|\á|\é|\í|\ó|\ú|\Á|\É|\Í|\Ó|\Ú|\w|\s|\-|\+)+)*)*$/';
    private $regex_sku                           = '/^(\s)*\((\s)*(\s*\w*\s*)+(\s)*(-(\s)*(\s*\w*\s*)+(\s)*)*\)((\s)*\|(\s)*\((\s)*(\s*\w*\s*)+(\s)*(-(\s)*(\s*\w*\s*)+(\s)*)*\))*(\s)*$/';
    private $regex_url_youtube                   = '/^(?:https?:)?(?:\/\/)?(?:youtu\.be\/|(?:www\.|m\.)?youtube\.com\/(?:watch|v|embed)(?:\?.*v=|\/))([a-zA-Z0-9\_-]{11})(?:[\?&][a-zA-Z0-9\_-]+=[a-zA-Z0-9\_-]+)*(?:[&\/\#].*)?$/';
    private $regex_url_vimeo                     = '/(http|https)?:\/\/(www\.|player\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|video\/|)(\d+)(?:|\/\?)/';
    private $PASO_VALIDACION                     = -1;
    private $ERROR_VALIDACION_FORMATO            =  0;
    private $ERROR_VALIDACION_SQL                =  1;
    private $ERROR_VALIDACION_NUM_GARANTIA       =  2;
    private $ERROR_VALIDACION_ID_GARANTIA        =  3;
    private $ERROR_VALIDACION_URL_VIDEO          =  4;
    private $ERROR_VALIDACION_URL_PORTADA_VIDEO  =  5;
    private $ERROR_GRUPOS_TALLAS_COLORES         =  6;
    private $ERROR_GRUPOS_COLORES_CANTIDADES     =  7;
    private $ERROR_CANTIDADES_COLORES_TALLAS     =  8;
    private $ERROR_CANTIDADES_TOTALES            =  9;
    private $ERROR_TALLAS_EXISTENCIA             = 10;
    private $ERROR_COLORES_EXISTENCIA            = 11;
    private $ERROR_GRUPOS_SKU                    = 12;
    private $ERROR_CANTIDADES_COLORES_SKU        = 13;
    private $ERROR_3_MESES_GARANTIA              = 14;
    private $ERROR_12_MESES_GARANTIA_NUEVO       = 15;
    private $ERROR_12_MESES_GARANTIA_REAC        = 16;

    public function subirFotosProductoMasivo(Array &$fotos, Int $fecha, Int $id) {
        parent::conectar();        
        
        foreach ($fotos as &$foto) {
            
            $url = $foto['img'];
            $insertarfoto = "INSERT INTO productos_fotos(id_publicacion,foto, estado, fecha_creacion, fecha_actualizacion) VALUES ('$id', '$url', '1', '$fecha', '$fecha')";
            
            parent::query($insertarfoto);
        }
        parent::cerrar();
    }
    
    public function verificarImagenes( Array &$imagenes ) {

        foreach($imagenes as $index => &$data_img) {
            $url = $data_img['img'];

            if( $this->verificarFormato('url_img', $url) != $this->PASO_VALIDACION ) {
                unset( $imagenes[$index] );
            }
        }

    }

    public function escaparDatos( &$producto ){

        $producto['producto']     = addslashes($producto['producto']);
        $producto['marca']        = addslashes($producto['marca']);
        $producto['modelo']       = addslashes($producto['modelo']);
        $producto['titulo']       = addslashes($producto['titulo']);
        $producto['descripcion']  = addslashes($producto['descripcion']);
        $producto['keywords']     = addslashes($producto['keywords']);
    }

    public function insertarPublicarMasivo(Array &$productos, $direccion, $uid, $id_empresa ){

        $tiene_permisos = $this->verificar_usuario_permisos_publicar( array( "uid_usuario" => $uid, "empresa" => $id_empresa ) );
        
        foreach( $productos as $data ) {

            $this->escaparDatos( $data );

            $fecha = intval(microtime(true) * 1000);

            parent::conectar();

            $insertarSQL =
            "INSERT INTO productos_revision(
                uid,
                empresa,
                tipo,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                foto_portada,
                url_video,
                portada_video,
                fecha_creacion,
                genero,
                tiene_colores_tallas,
                id_productos_revision_estados,
                tipo_envio_gratuito,
                id_tiempo_garantia,
                num_garantia
            )
            VALUES(
                '$uid',
                '$id_empresa',
                '$data[tipo]',
                '$data[producto]',
                '$data[marca]',
                '$data[modelo]',
                '$data[titulo]',
                '$data[descripcion]',
                '$data[id_categoria]',
                '$data[id_subcategoria]',
                '$data[condicion_producto]',
                '$data[garantia]',
                '$data[estado]',
                '$fecha',
                '$fecha',
                '$data[cantidad]',
                '$data[moneda_local]',
                '$data[precio]',
                '$data[precio_usd]',
                '$data[precio_publicacion]',
                '$data[precio_usd_publicacion]',
                '$data[oferta]',
                '$data[porcentaje_oferta]',
                '$data[porcentaje_tax]',
                '$data[exposicion]',
                0,
                '$data[envio]',
                '$direccion[id]',
                '$direccion[pais]',
                '$direccion[departamento]',
                '$direccion[ciudad]',
                '$direccion[latitud]',
                '$direccion[longitud]',
                '$direccion[codigo_postal]',
                '$direccion[direccion]',
                '$data[keywords]',
                0,
                '$data[foto_portada]',
                '$data[url_video]',
                '$data[portada_video]',
                '$fecha',
                '$data[genero]',
                '$data[tiene_colores_tallas]',
                0,
                '$data[tipo_envio_gratuito]',
                '$data[id_tiempo_garantia]',
                '$data[num_garantia]'
            )";
    
            $id_insertado = parent::queryRegistro($insertarSQL);
            parent::cerrar();            
            
            if( $tiene_permisos ){
                parent::conectar(); 

                $insertar_permisos_SQL = 
                "INSERT INTO productos(
                    id,
                    uid,
                    empresa,
                    tipo,
                    producto,
                    marca,
                    modelo,
                    titulo,
                    descripcion,
                    categoria,
                    subcategoria,
                    condicion_producto,
                    garantia,
                    estado,
                    fecha_actualizacion,
                    ultima_venta,
                    cantidad,
                    moneda_local,
                    precio,
                    precio_usd,
                    precio_publicacion,
                    precio_usd_publicacion,
                    oferta,
                    porcentaje_oferta,
                    porcentaje_tax,
                    exposicion,
                    cantidad_exposicion,
                    envio,
                    id_direccion,
                    pais,
                    departamento,
                    ciudad,
                    latitud,
                    longitud,
                    codigo_postal,
                    direccion,
                    keywords,
                    cantidad_vendidas,
                    foto_portada,
                    url_video,
                    portada_video,
                    fecha_creacion,
                    genero,
                    tiene_colores_tallas,
                    tipo_envio_gratuito,
                    id_tiempo_garantia,
                    num_garantia
                )
                VALUES(
                    '$id_insertado',
                    '$uid',
                    '$id_empresa',
                    '$data[tipo]',
                    '$data[producto]',
                    '$data[marca]',
                    '$data[modelo]',
                    '$data[titulo]',
                    '$data[descripcion]',
                    '$data[id_categoria]',
                    '$data[id_subcategoria]',
                    '$data[condicion_producto]',
                    '$data[garantia]',
                    '$data[estado]',
                    '$fecha',
                    '$fecha',
                    '$data[cantidad]',
                    '$data[moneda_local]',
                    '$data[precio]',
                    '$data[precio_usd]',
                    '$data[precio_publicacion]',
                    '$data[precio_usd_publicacion]',
                    '$data[oferta]',
                    '$data[porcentaje_oferta]',
                    '$data[porcentaje_tax]',
                    '$data[exposicion]',
                    0,
                    '$data[envio]',
                    '$direccion[id]',
                    '$direccion[pais]',
                    '$direccion[departamento]',
                    '$direccion[ciudad]',
                    '$direccion[latitud]',
                    '$direccion[longitud]',
                    '$direccion[codigo_postal]',
                    '$direccion[direccion]',
                    '$data[keywords]',
                    0,
                    '$data[foto_portada]',
                    '$data[url_video]',
                    '$data[portada_video]',
                    '$fecha',
                    '$data[genero]',
                    '$data[tiene_colores_tallas]',                    
                    '$data[tipo_envio_gratuito]',
                    '$data[id_tiempo_garantia]',
                    '$data[num_garantia]'
                )";

                parent::queryRegistro($insertar_permisos_SQL);

                parent::query("DELETE FROM productos_revision WHERE id = '$id_insertado'");

                parent::cerrar();
                
            }

            $this->subirFotosProductoMasivo( $data['imagenes'], $fecha , $id_insertado);


            $detalle_envio = array( 
                'alto'             => $data['alto'],
                'largo'            => $data['largo'],
                'ancho'            => $data['ancho'],
                'peso'             => $data['peso'],
                'unidad_masa'      => 'kg',
                'unidad_distancia' => 'cm'
            );

            $envio = $this->insertarEnvio($detalle_envio, $fecha, $id_insertado);
            
            if( $data['tiene_colores_tallas'] ){
                foreach($data['data_tallas_SQL'] as $talla){
                    $talla['id_producto'] = $id_insertado;
                    $this->guardarProductoColoresTallas($talla);
                }
            }
        }

        return $tiene_permisos;
            
    }

    public function validarIsoCode2( $data, $iso_code_2){

        $precio_usd = null;
        $array_monedas_local = null;
        //if(isset($iso_code_2)){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);
                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);
                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        //}
        if(/* isset($iso_code_2) &&  */$iso_code_2 == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
                // return "paso #3: " . $precio_usd;
            }else{
                $precio_usd = $data['precio'];
                // return "paso #4: " . $precio_usd;
            }
        }
        
        return $precio_usd;
    }

    public function verificarValoresPermitidos( $nombre_campo, $valor_campo ){
        $PERMITIDO = $this->PASO_VALIDACION;
        
        switch( $nombre_campo ){
            case 'empresa':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 || $valor_campo > 1 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'tipo':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 || $valor_campo > 1 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'genero':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 1 || $valor_campo > 3 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'id_categoria':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'id_subcategoria':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'condicion_producto':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 1 || $valor_campo > 3 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'garantia':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 || $valor_campo > 1 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'num_garantia':
                $valor_campo = intval($valor_campo);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'id_tiempo_garantia':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 2 || $valor_campo > 3 ){//Meses = 2, años = 3
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'estado':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 1 || $valor_campo > 2 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'cantidad':
                $valor_campo = intval($valor_campo);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'precio':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'porcentaje_oferta':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'porcentaje_tax':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'exposicion':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 1 || $valor_campo > 3 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            /* case 'cantidad_exposicion':
                $valor_campo = intval($valor_campo);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break; */
            case 'envio':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 1 || $valor_campo > 3 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'tipo_envio_gratuito':
                $valor_campo = intval($valor_campo);

                if( ($valor_campo < 1 || $valor_campo > 2) /* && $valor_campo != 0  */){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'alto':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'largo':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'ancho':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'peso':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            /* case 'url_video':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'url_portada':
                $valor_campo = $this->truncNumber( floatval($valor_campo) , 2);

                if( $valor_campo <= 0 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break; */
            case 'tiene_colores_tallas':
                $valor_campo = intval($valor_campo);

                if( $valor_campo < 0 || $valor_campo > 1 ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'tallas':
                $esValido = preg_match($this->regex_tallas, $valor_campo);

                if( !$esValido ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'colores':
                $esValido = preg_match($this->regex_colores, $valor_campo);

                if( !$esValido ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'cantidades':
                $esValido = preg_match($this->regex_cantidades, $valor_campo);

                if( !$esValido ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
            case 'sku':
                $esValido = preg_match($this->regex_sku, $valor_campo);

                if( !$esValido ){
                    $PERMITIDO = $this->ERROR_VALIDACION_SQL;
                }
            break;
        }

        return $PERMITIDO;
    }

    public function verificarFormato( $nombre_campo, $valor_campo ){

        $numeros = ['uid','empresa','tipo','genero','id_categoria','id_subcategoria','condicion_producto',
        'garantia','tipo_envio_gratuito','num_garantia','id_tiempo_garantia','estado','cantidad',
        'precio','porcentaje_oferta','porcentaje_tax','exposicion','envio',
        'alto','largo','ancho','peso','tiene_colores_tallas'];

        $cadenas = ['producto','marca','modelo','titulo','descripcion'];

        $url = ['foto_portada','url_video','portada_video','url_img'];

        $imagenes = ['imagenes'];

        if( in_array( $nombre_campo, $numeros) ){
            if( !is_numeric( $valor_campo ) ){
                return $this->ERROR_VALIDACION_FORMATO;
            }

            return $this->PASO_VALIDACION;
        }

        if( in_array( $nombre_campo, $url) ){

            $valor_campo = strval($valor_campo);

            if( $nombre_campo == 'url_video' ) {
                $esValido = preg_match( $this->regex_url_youtube, $valor_campo) || preg_match( $this->regex_url_vimeo, $valor_campo);

                if( !$esValido ){
                    return $this->ERROR_VALIDACION_FORMATO;
                }

            }else{
                if( !filter_var( $valor_campo, FILTER_VALIDATE_URL ) || count( $valor_campo ) <= 0 ){
                    return $this->ERROR_VALIDACION_FORMATO;
                }
            }
            
            return $this->PASO_VALIDACION;
        }
        
    }

    public function verificarValoresGarantia($id_tiempo, $num_garantia, $condicion ){

        $res_id_tiempo     = $this->verificarValoresPermitidos('id_tiempo_garantia', $id_tiempo);
        $resp_num_garantia = $this->verificarValoresPermitidos('num_garantia'      , $num_garantia);

        if( $res_id_tiempo === $this->ERROR_VALIDACION_SQL){
            return $this->ERROR_VALIDACION_ID_GARANTIA;
        }
        
        if( $resp_num_garantia === $this->ERROR_VALIDACION_SQL){
            return $this->ERROR_VALIDACION_NUM_GARANTIA;
        }

        /* if( $condicion != 2 ) {//Nuevo o reacondicionado
            if( $id_tiempo == 2 && $num_garantia < 12 ) { //Menor a 12 meses
                if( $condicion ==  1){//Nuevo
                    return $this->ERROR_12_MESES_GARANTIA_NUEVO;
                }else{//Reacondicionado
                    return $this->ERROR_12_MESES_GARANTIA_REAC;
                }
            }
        }else{              //Usado
            if( $id_tiempo == 2 && $num_garantia < 3 ) { //Menor a 3 meses
                return $this->ERROR_3_MESES_GARANTIA;
            }
        } */

        return $this->PASO_VALIDACION;
    }

    public function verificarPortada($url_video, $url_portada){

        $res_url_video     = $this->verificarFormato('url_video'     , $url_video);
        $resp_url_portada  = $this->verificarFormato('portada_video' , $url_portada);        

        if( $res_url_video === $this->ERROR_VALIDACION_FORMATO){
            return $this->ERROR_VALIDACION_URL_VIDEO;
        }
        
        if( $resp_url_portada === $this->ERROR_VALIDACION_FORMATO){
            return $this->ERROR_VALIDACION_URL_PORTADA_VIDEO;
        }

        return $this->PASO_VALIDACION;
    }

    public function obtenerGrupos(&$grupos) {
        $grupos = str_replace("(", "", $grupos);
        $grupos = str_replace(")", "", $grupos);
        $grupos = explode('|' , $grupos);
        
        $grupos = array_map(function( $g ) {//Eliminando espacios al comienzo y final de la cadena
            return trim( $g );
        }, $grupos );
    }

    public function obtenerDatosGrupo($grupo){
        $grupo = explode('-' , $grupo);

        $grupo = array_map(function( $e ) {//Eliminando espacios al comienzo y final de la cadena
            return trim( $e );
        }, $grupo );
    
        return $grupo;
    }

    public function verificarItemsCantidadesPorColor($grupos_colores, $grupos_cantidad_x_talla){

        $respuesta = true;
        for( $i = 0; $i < count($grupos_colores); $i++ ){
            $grupo_color = $this->obtenerDatosGrupo($grupos_colores[$i]);
            $grupo_cantidad_x_talla = $this->obtenerDatosGrupo($grupos_cantidad_x_talla[$i]);
    
            if( count($grupo_color) != count($grupo_cantidad_x_talla) ){
                $respuesta = false;
                break;
            }
        }
    
        return $respuesta;
    }

    public function verificarCantidades($grupos_cantidad_x_talla, $total){
        $cantidad = 0;
    
        for( $i = 0; $i < count($grupos_cantidad_x_talla); $i++ ){
            $grupo_color = $this->obtenerDatosGrupo($grupos_cantidad_x_talla[$i]);
            for( $k = 0; $k < count($grupo_color); $k++ ){
                $cantidad += intval($grupo_color[$k]);
            }
        }
    
        return $cantidad == $total;
    }

    public function prepararDatosTallasSQL($grupos_tallas, $grupos_colores, $grupos_cantidad_x_talla){
        $dataSQL = [];
    
        for( $i = 0; $i < count($grupos_tallas); $i++ ){
            $colores 	= $this->obtenerDatosGrupo($grupos_colores[$i]);
            $cantidades = $this->obtenerDatosGrupo($grupos_cantidad_x_talla[$i]);
            for( $k = 0; $k < count($colores); $k++ ){
                $elemento = array(
                    "id_tallas"  => $grupos_tallas[$i],
                    "id_colores" => $colores[$k],
                    "cantidad"	 => $cantidades[$k],
                    "sku"        => ''
                );
                array_push($dataSQL, $elemento);
            }
        }
    
        return $dataSQL;
    }

    public function prepararDatosTallasSkuSQL($grupos_tallas, $grupos_colores, $grupos_cantidad_x_talla, $grupos_sku){
        $dataSQL = [];
    
        for( $i = 0; $i < count($grupos_tallas); $i++ ){
            $colores 	= $this->obtenerDatosGrupo($grupos_colores[$i]);
            $cantidades = $this->obtenerDatosGrupo($grupos_cantidad_x_talla[$i]);
            $sku   		= $this->obtenerDatosGrupo($grupos_sku[$i]);
    
            for( $k = 0; $k < count($colores); $k++ ){
                $elemento = array(
                    "id_tallas"  => $grupos_tallas[$i],
                    "id_colores" => $colores[$k],
                    "cantidad"	 => $cantidades[$k],
                    "sku"		 => $sku[$k]
                );
                array_push($dataSQL, $elemento);
            }
        }
    
        return $dataSQL;
    }

    public function existenTallas(&$grupo_tallas, $categoria_path, $genero) {
        
        $resp = true;

        parent::conectar();

        foreach($grupo_tallas as &$talla) {

            $consulta = "SELECT id FROM productos_tallas
                         WHERE  nombre_es      = '$talla'
                         AND    CategoryIDPath = '$categoria_path'
                         AND    tipo           = '$genero'
                         AND    estado         =  1;";

            $id = parent::consultaTodo($consulta);

            if( count($id) <= 0){
                $resp = false;
                break;
            }else{
                $talla = intval( $id[0]['id'] );
            }
        }
        parent::cerrar();

        return $resp;
    }

    public function existenColores(&$grupos_colores){
        
        $resp = true;
        
        parent::conectar();
        
        foreach($grupos_colores as &$grupo_color){

            $colores_id = [];
            $colores    = $this->obtenerDatosGrupo($grupo_color);

            foreach($colores as $color){
                $consulta = "SELECT id FROM productos_colores WHERE nombre_es = '$color' AND estado = 1;";
                $id = parent::consultaTodo($consulta);

                if( count($id) <= 0){
                    $resp = false;
                    break;
                }else{
                    array_push( $colores_id, intval( $id[0]['id'] ));
                }

            }
            
            if($resp === false){
                break;
            }else{
                $str_ids = implode('-',  $colores_id);
                $grupo_color = $str_ids;
            }            
        }

        parent::cerrar();

        return $resp;
    }

    public function verificarDataColores(&$grupos_tallas, &$grupos_colores, &$grupos_cantidad_x_talla, $cantidad_total, $categoria_path, $genero, &$grupos_sku ){
        $this->obtenerGrupos($grupos_tallas);
        $this->obtenerGrupos($grupos_colores);

        $resp = $this->existenTallas( $grupos_tallas, $categoria_path, $genero );

        if( !$resp ){
            return $this->ERROR_TALLAS_EXISTENCIA;
        }

        $resp = $this->existenColores($grupos_colores);

        if( !$resp ){
            return $this->ERROR_COLORES_EXISTENCIA;
        }

        if(count($grupos_colores) !== count($grupos_tallas)){
            return $this->ERROR_GRUPOS_TALLAS_COLORES;
        }

        $this->obtenerGrupos($grupos_cantidad_x_talla);
    
        if(count($grupos_colores) !== count($grupos_cantidad_x_talla)){
            return $this->ERROR_GRUPOS_COLORES_CANTIDADES;
        }
    
        $resp = $this->verificarItemsCantidadesPorColor($grupos_colores, $grupos_cantidad_x_talla);
    
        if(!$resp){
            return $this->ERROR_CANTIDADES_COLORES_TALLAS;
        }
    
        if( strlen(trim($grupos_sku)) > 0 ){//Tiene SKU
		
            $this->obtenerGrupos($grupos_sku);
            if(count($grupos_sku) !== count($grupos_colores)){
                return $this->ERROR_GRUPOS_SKU;
            }
    
            $resp = $this->verificarItemsCantidadesPorColor($grupos_sku, $grupos_colores);
            if(!$resp){
                return $this->ERROR_CANTIDADES_COLORES_SKU;
            }    
        }

        $resp = $this->verificarCantidades($grupos_cantidad_x_talla, $cantidad_total);
    
        if(!$resp){
            return $this->ERROR_CANTIDADES_TOTALES;
        }

        return $this->PASO_VALIDACION;
    }

    public function insertarGarantiaPorDefecto( &$producto ) {

        $producto['garantia'] = 0;

        if( $producto['condicion_producto'] == 2 ){//Usado
            $producto['id_tiempo_garantia'] =  2;
            $producto['num_garantia']       =  3;
        }else if( $producto['condicion_producto'] == 3 ) {//Reacondicionado
            $producto['id_tiempo_garantia'] =  2;
            $producto['num_garantia']       =  0;
        }else{                                 //Nuevo
            $producto['id_tiempo_garantia'] =  3;
            $producto['num_garantia']       =  1;
        }
    }

    public function validarInformacionMasiva( Array &$productos, Array $campos_validar , $iso_code_2 ) {

        $campos_garantia  = ['id_tiempo_garantia', 'num_garantia'];
        $campos_url_video = ['url_video', 'portada_video'];

        $productos_rechazados = [];

        foreach($productos as $key => &$item) {
            $campos_rechazados = [];

            foreach ($campos_validar as $campo) {
                $existe_campo  = array_key_exists ($campo, $item);
    
                if( $existe_campo ){

                    if( (!is_array( $item[$campo] ) && strlen( trim($item[$campo]) ) === 0) || in_array( $campo, $campos_url_video ) ){

                        if( in_array( $campo, $campos_url_video ) ){

                            if( $campo === 'url_video' ) {

                                if( strlen( trim($item[$campo]) ) > 0 ) {//Tiene url_video

                                    if( !isset( $item['portada_video'] ) ){
                                        $campos_rechazados['portada_video'] = 'errCampoFaltante';
                                    }else if( strlen(trim($item['portada_video'])) <= 0 ){
                                        $campos_rechazados['portada_video'] = 'errCampoVacio';
                                    }else{

                                        $respuesta = $this->verificarPortada( $item['url_video'], $item['portada_video'] );
            
                                        if( $respuesta === $this->ERROR_VALIDACION_URL_VIDEO ) {
    
                                            //$campos_rechazados['url_video'] = 'formato invalido';
                                            $campos_rechazados['url_video'] = 'errFormatoInvalido';
                                        }
    
                                        if( $respuesta === $this->ERROR_VALIDACION_URL_PORTADA_VIDEO ){
                                            
                                            //$campos_rechazados['portada_video'] = 'formato invalido';
                                            $campos_rechazados['portada_video'] = 'errFormatoInvalido';
                                        }

                                    }

                                }else{
                                    $item['portada_video'] = '';
                                }
                            }
                            
                        }else{
                            //$campos_rechazados[$campo] = 'campo vacio';
                            $campos_rechazados[$campo] = 'errCampoVacio';
                        }
                    }else{//Validar formatos y existencias
                        if( $this->verificarFormato( $campo, $item[$campo] ) === $this->ERROR_VALIDACION_FORMATO ) {
                            //$campos_rechazados[$campo] = 'formato invalido';
                            $campos_rechazados[$campo] = 'errFormatoInvalido';
                        }

                        if( !array_key_exists( $campo, $campos_rechazados ) ){

                            if( !in_array( $campo, $campos_garantia ) && !in_array( $campo, $campos_url_video ) ){

                                if( $this->verificarValoresPermitidos( $campo, $item[$campo] ) === $this->ERROR_VALIDACION_SQL ){
                                    //$campos_rechazados[$campo] = 'valor fuera de rango';
                                    $campos_rechazados[$campo] = 'errFueraDeRango';
                                }
                            }
                        }
                        
                        if( $campo === 'garantia' ){

                            if( intval( $item[$campo] ) === 1 ){//1 = tiene garantia

                                $puede_verificar_garantia =  !array_key_exists( 'id_tiempo_garantia', $campos_rechazados )
                                                          && !array_key_exists( 'num_garantia'      , $campos_rechazados )
                                                          && !array_key_exists( 'condicion_producto', $campos_rechazados );

                                if( $puede_verificar_garantia ) {

                                    $respuesta = $this->verificarValoresGarantia( $item['id_tiempo_garantia'], $item['num_garantia'], $item['condicion_producto']);
    
                                    if( $respuesta === $this->ERROR_VALIDACION_NUM_GARANTIA ){
                                        //$campos_rechazados['num_garantia']       = 'valor fuera de rango';
                                        $campos_rechazados['num_garantia']       = 'errFueraDeRango';
                                    }

                                    if( $respuesta === $this->ERROR_VALIDACION_ID_GARANTIA ){
                                        //$campos_rechazados['id_tiempo_garantia'] = 'valor fuera de rango';
                                        $campos_rechazados['id_tiempo_garantia'] = 'errFueraDeRango';
                                    }

                                    if( $respuesta === $this->ERROR_3_MESES_GARANTIA ){
                                        //$campos_rechazados['num_garantia']       = 'El tiempo mínimo de garantia para un producto usado es de 3 meses';
                                        $campos_rechazados['num_garantia']       = 'errGarantiaUsado';
                                    }

                                    if( $respuesta === $this->ERROR_12_MESES_GARANTIA_NUEVO ){
                                        //$campos_rechazados['num_garantia']       = 'El tiempo mínimo de garantia para un producto nuevo es de 12 meses';
                                        $campos_rechazados['num_garantia']       = 'errGarantiaNuevo';
                                    }

                                    if( $respuesta === $this->ERROR_12_MESES_GARANTIA_REAC ){
                                        //$campos_rechazados['num_garantia']       = 'El tiempo mínimo de garantia para un producto reacondicionado es de 12 meses';
                                        $campos_rechazados['num_garantia']       = 'errGarantiaReacondicionado';
                                    }

                                }

                            }else{//Garantia por defecto

                                if( !array_key_exists( 'condicion_producto', $campos_rechazados ) ){
                                    $this->insertarGarantiaPorDefecto( $item );
                                }
                            }

                        }

                    }

                }else{
                    //$campos_rechazados[$campo] = 'campo faltante';
                    if($campo != 'imagenes') {
                        $campos_rechazados[$campo] = 'errCampoFaltante';
                    }else{
                        $item['imagenes']          = array();
                    }
                }
            }

            $precio_usd = 0;

            $array_monedas_locales = null;
            $array_monedas_local   = null;

            if( !array_key_exists( 'precio', $campos_rechazados ) && !array_key_exists( 'porcentaje_oferta', $campos_rechazados )) {

                $precio_usd = $this->validarIsoCode2( $item , $iso_code_2);
    
                if( !$precio_usd ){
                    //$campos_rechazados['iso_code_2'] = 'la moneda en la que desea publicar no es valida';
                    $campos_rechazados['iso_code_2'] = 'errMonedaInvalida';
                }

                if( !array_key_exists( 'iso_code_2', $campos_rechazados ) ){
                    $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
                    $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2)[0];    
    
                    if( !array_key_exists( 'porcentaje_oferta', $campos_rechazados ) ){
                        $item['oferta'] = 1;
        
                        if( intval($item['porcentaje_oferta']) <= 0){
                            $item['oferta'] = 0;
                        }
        
                        if ( floatval($item['porcentaje_oferta']) < 0 || floatval($item['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
                           
                            //$campos_rechazados['porcentaje_oferta'] = 'el porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%';
                            $campos_rechazados['porcentaje_oferta'] = 'errPorcentajeDescuento';
                        }else{
                            $precio_usd_descuento = $precio_usd;
                            if ( $precio_usd_descuento < $this->montoxminimoxpublicar && floatval($item['porcentaje_oferta']) != 0) {
                                
                                //$campos_rechazados['precio'] = 'El monto ingresado con descuento es inferior con a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $item['precio'];
                                $campos_rechazados['precio'] = 'errPrecioConDescuento;'.$this->truncNumber($item['precio']).' '.$array_monedas_local['code'].';'.$this->truncNumber($this->montoxminimoxpublicar).' USD';
                            }else if( $precio_usd < $this->montoxminimoxpublicar ){
                                
                                //$campos_rechazados['precio'] = 'El monto ingresado es inferior con a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $item['precio'];
                                $campos_rechazados['precio'] = 'errPrecio;'.$this->truncNumber($item['precio']).' '.$array_monedas_local['code'].';'.$this->truncNumber($this->montoxminimoxpublicar).' USD';
                            }else{
                                $item['precio_usd']             = $precio_usd;
                                $item['precio_usd_publicacion'] = $precio_usd;
                                $item['precio_publicacion']     = $item['precio'];
                                $iso_code_2 == 'US' ?  $item['moneda_local'] = 'USD' :  $item['moneda_local'] = $array_monedas_local['code'];
                            }
                        }
    
                    }
                }
            }            

            $categoria       = null;
            $subcategoria    = null;
            $id_subcategoria = 0;

            if( !array_key_exists( 'id_categoria', $campos_rechazados ) ) {

                $id_categoria = $item['id_categoria'];

                $categoria    = array_filter( $this->json_categorias, function( $item_ct ) use( $id_categoria ) {
                                    return $item_ct['CategoryID'] == $id_categoria; 
                                });
        
                $categoria = array_values( $categoria );
        
                if( count( $categoria ) <= 0){
                    
                    //$campos_rechazados['id_categoria'] = 'La categoria no existe en la base de datos';
                    $campos_rechazados['id_categoria'] = 'errCategoria';
                }else{
                    $categoria = $categoria[0];
                }


                if( !array_key_exists( 'id_categoria', $campos_rechazados ) && !array_key_exists( 'id_subcategoria', $campos_rechazados ) ) {

                    $id_subcategoria = $item['id_subcategoria'];
    
                    $subcategoria    = array_filter( $categoria['subCategoria'], function( $item_sct) use( $id_subcategoria ) {
                                           return $item_sct['CategoryID'] == $id_subcategoria; 
                                       });
    
                    $subcategoria = array_values( $subcategoria );
    
                    if( count($subcategoria) <= 0){
                        
                        //$campos_rechazados['id_subcategoria'] = 'La subcategoria no existe en la base de datos';
                        $campos_rechazados['id_subcategoria'] = 'errSubcategoria';
                    }else{

                        $subcategoria = $subcategoria[0];

                        //Generación de las palabras clave
                        $puede_insertar_palabras_clave    =  !array_key_exists( 'producto', $campos_rechazados )
                                                          && !array_key_exists( 'marca'   , $campos_rechazados )
                                                          && !array_key_exists( 'modelo'  , $campos_rechazados )
                                                          && !array_key_exists( 'titulo'  , $campos_rechazados );

                        if( $puede_insertar_palabras_clave ){
                            $item['nombre_categoria']     = $categoria['CategoryName'];
                            $item['nombre_subcategoria']  = $subcategoria['CategoryName'];        
                            $origen_keywords              = $item['producto'].' '.$item['marca'].' '.$item['modelo'].' '.$item['titulo'].' '.$item['nombre_categoria'].' '.$item['nombre_subcategoria'];
                            $item['keywords']             = implode(',', $this->extractKeyWords($origen_keywords));
                        }

                    }
    
                }

            }

            $tallas_colores_cantidades_sku_copia = [];

            if( !array_key_exists( 'tiene_colores_tallas', $campos_rechazados ) ) {

                if( intval($item['tiene_colores_tallas']) ){

                    if( !isset($item['tallas']) ){
                        
                        //$campos_rechazados['tallas']  = 'campo faltante';
                        $campos_rechazados['tallas']  = 'errCampoFaltante';
                    }else{
                        $tallas_colores_cantidades_sku_copia['tallas'] = $item['tallas'];
                    }

                    if( !isset($item['colores']) ){
                        
                        //$campos_rechazados['colores'] = 'campo faltante';
                        $campos_rechazados['colores'] = 'errCampoFaltante';
                    }else{
                        $tallas_colores_cantidades_sku_copia['colores'] = $item['colores'];
                    }

                    if( !isset($item['cantidad_x_talla_x_color']) ){
                        
                        //$campos_rechazados['cantidad_x_talla_x_color'] = 'campo faltante';
                        $campos_rechazados['cantidad_x_talla_x_color'] = 'errCampoFaltante';
                    }else{
                        $tallas_colores_cantidades_sku_copia['cantidad_x_talla_x_color'] = $item['cantidad_x_talla_x_color'];
                    }

                    if( !array_key_exists( 'tallas', $campos_rechazados ) && $this->verificarValoresPermitidos( 'tallas', $item['tallas'] ) === $this->ERROR_VALIDACION_SQL ){
                        
                        //$campos_rechazados['tallas']  = 'formato invalido';
                        $campos_rechazados['tallas']  = 'errFormatoInvalido';
                    }

                    if( !array_key_exists( 'colores', $campos_rechazados ) && $this->verificarValoresPermitidos( 'colores', $item['colores'] ) === $this->ERROR_VALIDACION_SQL ){
                        
                        //$campos_rechazados['colores'] = 'formato invalido';
                        $campos_rechazados['colores'] = 'errFormatoInvalido';
                    }

                    if( !array_key_exists( 'cantidad_x_talla_x_color', $campos_rechazados ) && $this->verificarValoresPermitidos( 'cantidades', $item['cantidad_x_talla_x_color'] ) === $this->ERROR_VALIDACION_SQL ){
                        
                        //$campos_rechazados['cantidad_x_talla_x_color'] = 'formato invalido';
                        $campos_rechazados['cantidad_x_talla_x_color'] = 'errFormatoInvalido';
                    }

                    if( !array_key_exists( 'id_categoria', $campos_rechazados ) && !array_key_exists( 'id_subcategoria', $campos_rechazados ) ) {

                        $tiene_sku      = false;
                        $categoria_path = $subcategoria['CategoryIDPath'];
                        $respuesta      = $this->PASO_VALIDACION;   
                        $puede_verificar_colores_tallas = false;
                        
                        if( isset($item['SKU']) && strlen(trim($item['SKU'])) > 0 ) {
                            $tallas_colores_cantidades_sku_copia['SKU'] = $item['SKU'];
                            $tiene_sku = true;
        
                            if( $this->verificarValoresPermitidos( 'sku', $item['SKU'] ) === $this->ERROR_VALIDACION_SQL ){
                                
                                //$campos_rechazados['SKU'] = 'formato invalido';
                                $campos_rechazados['SKU'] = 'errFormatoInvalido';
                            }
    
                            $puede_verificar_colores_tallas   =  !array_key_exists( 'tallas'                  , $campos_rechazados )
                                                              && !array_key_exists( 'colores'                 , $campos_rechazados )
                                                              && !array_key_exists( 'cantidad_x_talla_x_color', $campos_rechazados )
                                                              && !array_key_exists( 'cantidad'                , $campos_rechazados )
                                                              && !array_key_exists( 'genero'                  , $campos_rechazados )
                                                              && !array_key_exists( 'SKU'                     , $campos_rechazados );
    
                            if( $puede_verificar_colores_tallas ) {

                                $respuesta = $this->verificarDataColores( $item['tallas'], $item['colores'], $item['cantidad_x_talla_x_color'], intval( $item['cantidad'] ), $categoria_path, $item['genero'], $item['SKU'] );
                            }
        
                        }else{
    
                            $puede_verificar_colores_tallas   =  !array_key_exists( 'tallas'                  , $campos_rechazados )
                                                              && !array_key_exists( 'colores'                 , $campos_rechazados )
                                                              && !array_key_exists( 'cantidad_x_talla_x_color', $campos_rechazados )
                                                              && !array_key_exists( 'cantidad'                , $campos_rechazados )
                                                              && !array_key_exists( 'genero'                  , $campos_rechazados );
    
                            if( $puede_verificar_colores_tallas ) {

                                $grupos_sku = '';
                                $respuesta  = $this->verificarDataColores( $item['tallas'], $item['colores'], $item['cantidad_x_talla_x_color'], intval( $item['cantidad'] ), $categoria_path, $item['genero'], $grupos_sku );
                            }
                        }
    
                        if( $puede_verificar_colores_tallas ){                        
                            switch( $respuesta ){
                                case $this->ERROR_TALLAS_EXISTENCIA:
                                    
                                    //$campos_rechazados['tallas']  = 'alguna de las tallas no existe en base de datos';
                                    $campos_rechazados['tallas']  = 'errTallasExistencias';
                                break;
                                case $this->ERROR_COLORES_EXISTENCIA:
                                   
                                    //$campos_rechazados['colores'] = 'alguno de los colores no existe en base de datos o está inactivo';
                                    $campos_rechazados['colores'] = 'errColoresExistencia';
                                break;
                                case $this->ERROR_GRUPOS_TALLAS_COLORES:
                                   
                                    //$campos_rechazados['tallas']  = 'la cantidad de grupos de tallas no coincide con la cantidad de grupos de colores';
                                    $campos_rechazados['tallas']  = 'errGruposTallasColores';
                                break;
                                case $this->ERROR_GRUPOS_COLORES_CANTIDADES:
                                   
                                    //$campos_rechazados['colores'] = 'la cantidad de grupos de colores no coincide con la cantidad de grupos de cantidades';
                                    $campos_rechazados['colores'] = 'errGruposColoresCantidades';
                                break;
                                case $this->ERROR_CANTIDADES_COLORES_TALLAS:
                                   
                                    //$campos_rechazados['colores'] = 'la cantidad de colores de cada grupo no coincide con las cantidades de tallas';
                                    $campos_rechazados['colores'] = 'errCantidadesColoresTallas';
                                break;
                                case $this->ERROR_CANTIDADES_TOTALES:
                                   
                                    //$campos_rechazados['cantidad_x_talla_x_color'] = 'la suma de cantidades por color por talla no coinciden con las cantidades del producto';
                                    $campos_rechazados['cantidad_x_talla_x_color'] = 'errCantidadesTotales';
                                break;
                                case $this->ERROR_GRUPOS_SKU:
                                   
                                    //$campos_rechazados['SKU']  = 'La cantidad de grupos de SKU no coincide con la cantidad de grupos de colores';
                                    $campos_rechazados['SKU']  = 'errGruposColoresSKU';
                                break;
                                case $this->ERROR_CANTIDADES_COLORES_SKU:
                                   
                                    //$campos_rechazados['SKU']  = 'la cantidad de SKU de cada grupo no coincide con las cantidades de colores';
                                    $campos_rechazados['SKU']  = 'errCantidadesColoresSKU';
                                break;
                                default:
                                    if( $tiene_sku ){
                                        $item['data_tallas_SQL'] = $this->prepararDatosTallasSkuSQL( $item['tallas'], $item['colores'], $item['cantidad_x_talla_x_color'], $item['SKU']  );
                                    }else{
                                        $item['data_tallas_SQL'] = $this->prepararDatosTallasSQL( $item['tallas'], $item['colores'], $item['cantidad_x_talla_x_color']);
                                    }
                                break;
                            }
                        }
                    }                
        
                }

            }

            if( count( $campos_rechazados ) > 0 ) {

                foreach( $tallas_colores_cantidades_sku_copia as $k => $valor ){//Restauramos los colores, tallas, cantidades, SKU como las envió el usuario
                    $item[$k] = $valor;
                }

                $producto = array( 'errores' => $campos_rechazados, 'producto' => $item ); 
                array_push( $productos_rechazados, $producto );
                unset( $productos[$key] );
            }else{

                $foto_portada = array('id' => 0, 'img' => $item['foto_portada']);

                array_unshift( $item['imagenes'], $foto_portada);

                if( count( $item['imagenes'] ) > 1 ) {
                    $index = 0;

                    $item['imagenes']  = array_map(function( $img ) use( &$index ) {
                                            $img['id'] = $index++;
                                            return $img;
                                        }, $item['imagenes'] );

                }

                $this->verificarImagenes( $item['imagenes'] );
                
            }

        }

        return $productos_rechazados;
    }        

    public function getUserDireccionActiva($uid, $empresa) {

        parent::conectar();

        $SQL = "SELECT * FROM direcciones WHERE uid = '$uid' AND empresa = '$empresa' AND activa = 1;";

        $direccion = parent::consultaTodo($SQL);

        parent::cerrar();

        if( count($direccion) > 0 ){
            return $direccion[0];
        }

        return null;
    }

    public function validarURL($path){//De momento no se usa

        $esURLvalida = filter_var( $path, FILTER_VALIDATE_URL );

        if( $esURLvalida ){
            $imagedata = @file_get_contents( $path );
            if( $imagedata ){
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $base64 = "data:image/" . $type . ";base64," . base64_encode($imagedata);
                return $base64;
            }else{
                return "la url no contiene una imagen";
            }
        }else{
            return "la url no es valida";
        }
    }

    public function enviarCorreoPublicacionMasiva( $data) {

        if( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['cantidad_rechazados']) || !isset($data['correo']) || !isset($data['nombre_usuario']) || !isset($data['idioma']) ) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);        
        }

        $tiene_permisos      = $this->verificar_usuario_permisos_publicar( array( "uid_usuario" => $data['uid'], "empresa" => $data['empresa'] ) );
        $cantidad_rechazados = intval($data['cantidad_rechazados']);
        $nombre_usuario      = $data['nombre_usuario'];
        $correo              = $data['correo'];
        $idioma              = $data['idioma'];
        
        $correo    .= ",dev.nasbi@gmail.com,qa.nasbi@gmail.com,nayivernasbi@hotmail.com,gissotk09@gmail.com";
        $json       = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$idioma.".json"));
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";

        $html   = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_carga_masiva/carga_masiva.html"); 
        $html   = str_replace("{{trans72_brand}}"     , $json->trans72_brand     , $html);
        $html   = str_replace("{{trans_04_brand}}"    , $json->trans_04_brand    , $html);
        $html   = str_replace("{{logo_footer_brand}}" , $json->logo_footer_brand , $html);
        $html   = str_replace("{{trans06_}}"          , $json->trans06_          , $html);
        $html   = str_replace("{{trans07_}}"          , $json->trans07_          , $html);
        $html   = str_replace("{{trans245}}"          , $json->trans245          , $html);
        $html   = str_replace("{{nombre_usuario}}"    , $nombre_usuario          , $html);
        $titulo = $json->trans249;

        if( $tiene_permisos ) {

            if( $cantidad_rechazados <= 0 ){        
                $html = str_replace("{{respuesta}}" , $json->trans246 , $html);

                $dataArray = array("email" => $correo, "titulo" => $titulo, "mensaje" => $html, "cabeceras" => $cabeceras);
                parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

            }else{
                $texto = str_replace("#"            , $cantidad_rechazados , $json->trans247);
                $html  = str_replace("{{respuesta}}", $texto               , $html);

                $dataArray = array("email" => $correo, "titulo" => $titulo, "mensaje" => $html, "cabeceras" => $cabeceras);
                parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            }

        }else{

            if( $cantidad_rechazados <= 0 ){
                $html = str_replace("{{respuesta}}" , $json->trans250 , $html);

                $dataArray = array("email" => $correo, "titulo" => $titulo, "mensaje" => $html, "cabeceras" => $cabeceras);
                parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

            }else{
                $texto = str_replace("#"            , $cantidad_rechazados , $json->trans248);
                $html  = str_replace("{{respuesta}}", $texto               , $html);

                $dataArray = array("email" => $correo, "titulo" => $titulo, "mensaje" => $html, "cabeceras" => $cabeceras);
                parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            }
        }

        return array( 
            "status"   => "success",
            "message"  => "correo de carga masiva enviado"
        );
    }
    
    public function publicarMasivo( Array $data ) {

        /* parent::conectar();

        $ids = '101:10111';
            
        $actualizarSQL = "UPDATE  productos_tallas
                          SET     nombre_es = 'RN'
                          WHERE   CategoryIDPath = '$ids' and nombre_es = 2 ";

        parent::query($actualizarSQL);

        return "estado cambiado"; */

        /* parent::conectar();

        $id = 6;
            
        $actualizarSQL = "UPDATE productos_subastas_tipo
                            SET descripcion = 'Standard'
                            WHERE id = '$id';";

        parent::query($actualizarSQL);
        parent::cerrar();

        return "estado cambiado"; */


        /* parent::conectar();
        parent::query('DELETE FROM productos_prueba_masiva');
        parent::cerrar();

        return "eliminados"; */ 

        if( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['json_categorias']) ) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);        
        } 

        if( !isset($data['iso_code_2']) ) {
            return array('status' => 'fail', 'message'=> 'falta el iso_code_2', 'data' => null);            
        }
        
        $direccion_activa = $this->getUserDireccionActiva( $data['uid'], $data['empresa'] );

        if( !$direccion_activa ){
            return array('status' => 'errorDireccion', 'message' => 'El usuario no tiene una dirección activa');
        }

        $data['direccion'] = $direccion_activa;

        $this->json_categorias = json_decode( $data['json_categorias'], 1 );
        $data['productos']     = json_decode( $data['productos'], 1 );

        $productos_rechazados = [];

        $campos_validar = ['tipo','producto','id_categoria','id_subcategoria','marca','modelo','titulo',
        'descripcion','condicion_producto','tipo_envio_gratuito','num_garantia','id_tiempo_garantia','garantia','estado',
        'cantidad','precio','porcentaje_oferta','porcentaje_tax','exposicion','envio','foto_portada',
        'url_video','portada_video','genero','alto','largo','ancho','peso','imagenes','tiene_colores_tallas'];

        $productos_rechazados  = $this->validarInformacionMasiva( $data['productos'], $campos_validar , $data['iso_code_2']);

        //return $productos_rechazados;

        if( count($data['productos']) <= 0 ){
            return array( "status"               => "success",
                          "message"              => "todos los productos han sido rechazados",
                          "rechazados"           => count($productos_rechazados),
                          "productos rechazados" => $productos_rechazados
                        );
        }

        $tiene_permisos = $this->insertarPublicarMasivo( $data['productos'], $direccion_activa, $data['uid'], $data['empresa'] );
        
        if( $tiene_permisos ) {

            return array( 
                "status"               => "success",
                "message"              => "los productos han sido publicados",
                "rechazados"           => count($productos_rechazados),
                "productos rechazados" => $productos_rechazados
            );
        }else{

            return array( 
                "status"               => "success",
                "message"              => "los productos están en revisión",
                "rechazados"           => count($productos_rechazados),
                "productos rechazados" => $productos_rechazados
            );
        }

    }

    public function publicarVersion2Original( Array $data ){
        if(!isset($data) || !isset($data['garantia_tiempo_num']) || !isset($data['garantia_tiempo_unidad_medida']) || !isset($data['tipo_de_envio_alcance']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);


        $request = null;
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);
        $data = $this->mapeoVender($data);
        $producto = $data;

        $envio = $data['direccion_envio'];
        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});
        $textfullcompleto = $producto['producto'].' '.$producto['marca'].' '.$producto['modelo'].' '.$producto['titulo'].' '.$producto['categoria']['CategoryName'].' '.$producto['subcategoria']['CategoryName'];
        
        $producto['keywords'] = implode(',', $this->extractKeyWords($textfullcompleto));
        $producto['categoria'] = $producto['categoria']['CategoryID'];
        $producto['subcategoria'] = $producto['subcategoria']['CategoryID'];
        
        
        $empresa = 0;
        if($producto['empresa'] == true) $empresa = 1;


        $precio_usd = null;
        $array_monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);
                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);
                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
            }else{
                $precio_usd = $data['precio'];
            }
        }

        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        if ( floatval($data['porcentaje_oferta']) < 0 || floatval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd;// - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],2),
                    'symbolMonedaLocal' => $array_monedas_local['code'],
                    'data' => null
                );
                
            }
        }


        $producto['precio_usd'] = $precio_usd;
        $producto['moneda_local'] = $monedas_local;

        // return $precio_usd;

        $cobrar = null;

        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1) {
            $producto['subasta']['precio_usd'] = $precio_usd;
            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            $producto['subasta']['precio'] = $this->truncNumber(($producto['subasta']['precio_usd'] / $precios[$producto['subasta']['moneda']]), 6);
            unset($nodo);

            $validarsubasta = $this->validarTipoSubasta(
                $producto['subasta'],
                $data['iso_code_2']
            );

            if($validarsubasta['status'] == 'fail') return $validarsubasta;
            $producto['subasta']['datos_subasta'] = $validarsubasta['data'];

            // return $producto['subasta'];

            if ($producto['subasta']['datos_subasta']['id'] != 6){
                $tickets = $this->verTicketsUsuario([
                    'uid' => $producto['uid'],
                    'empresa' => $empresa,
                    'plan' => $producto['subasta']['datos_subasta']['id'],
                    'cantidad' => $producto['subasta']['cantidad']
                ]);
                if($tickets['status'] == 'fail') return $tickets;
                $tickets = $tickets['data'];
    
                $cobrar = $this->cobrarTicekts([
                    'tickets' => $tickets, 
                    'cantidad' => $producto['subasta']['cantidad'],
                    'fecha' => $fecha
                ]);
                if($cobrar['status'] != 'success') return $cobrar;
            }
        }

        $tipoSubasta = 0;
        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
            $tipoSubasta = intval($producto['subasta']['tipo']);
        }

        $genero = 3;
        if(isset($data['genero'])){
            $genero = $data['genero'];
        }

            
        parent::conectar();
        $insertarproducto = "INSERT INTO productos_revision(
            uid,
            tipo_envio_gratuito,
            id_tiempo_garantia,
            num_garantia,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            empresa,
            url_video,
            genero,
            tiene_colores_tallas,
            id_productos_revision_estados
        )
        VALUES(
            '$producto[uid]',
            '$producto[tipo_de_envio_alcance]',
            '$producto[garantia_tiempo_unidad_medida]',
            '$producto[garantia_tiempo_num]',
            '$producto[tipo]',
            '$tipoSubasta',
            '$producto[producto]',
            '$producto[marca]',
            '$producto[modelo]',
            '$producto[titulo]',
            '$producto[descripcion]',
            '$producto[categoria]',
            '$producto[subcategoria]',
            '$producto[condicion_producto]',
            '$producto[garantia]',
            '1',
            '$fecha',
            '$fecha',
            '$fecha',
            '$producto[cantidad]',
            '$producto[moneda_local]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[oferta]',
            '$producto[porcentaje_oferta]',
            '$producto[porcentaje_tax]',
            '$producto[exposicion]',
            '$producto[cantidad_exposicion]',
            '$producto[envio]',
            '$envio[id]',
            '$envio[pais]',
            '$envio[departamento]',
            '$envio[ciudad]',
            '$envio[latitud]',
            '$envio[longitud]',
            '$envio[codigo_postal]',
            '$envio[direccion]',
            '$producto[keywords]',
            0,
            '$empresa',
            '$producto[url_video]',
            '$genero',
            '$data[tiene_colores_tallas]',
            0
        )";
        $productoqueryRevision = parent::queryRegistro($insertarproducto);


        parent::cerrar();
            
        $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));
        if($permisosRespuesta){
            parent::conectar();
            $insertarproducto2 = "INSERT INTO productos(
                id,
                uid,
                tipo_envio_gratuito,
                id_tiempo_garantia,
                num_garantia,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas
            )
            VALUES(
                '$productoqueryRevision',
                '$producto[uid]',
                '$producto[tipo_de_envio_alcance]',
                '$producto[garantia_tiempo_unidad_medida]',
                '$producto[garantia_tiempo_num]',
                '$producto[tipo]',
                '$tipoSubasta',
                '$producto[producto]',
                '$producto[marca]',
                '$producto[modelo]',
                '$producto[titulo]',
                '$producto[descripcion]',
                '$producto[categoria]',
                '$producto[subcategoria]',
                '$producto[condicion_producto]',
                '$producto[garantia]',
                '1',
                '$fecha',
                '$fecha',
                '$fecha',
                '$producto[cantidad]',
                '$producto[moneda_local]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[oferta]',
                '$producto[porcentaje_oferta]',
                '$producto[porcentaje_tax]',
                '$producto[exposicion]',
                '$producto[cantidad_exposicion]',
                '$producto[envio]',
                '$envio[id]',
                '$envio[pais]',
                '$envio[departamento]',
                '$envio[ciudad]',
                '$envio[latitud]',
                '$envio[longitud]',
                '$envio[codigo_postal]',
                '$envio[direccion]',
                '$producto[keywords]',
                0,
                '$empresa',
                '$producto[url_video]',
                '$genero',
                '$data[tiene_colores_tallas]'
            )";
            parent::queryRegistro($insertarproducto2);

            parent::query("DELETE FROM productos_revision WHERE id = '$productoqueryRevision'");

            parent::cerrar();
        }
        $productoquery = $productoqueryRevision;
        
        if ($productoquery) {
            $fotos = $this->subirFotosProducto($fotos, $fecha, $productoquery);
            if(isset($data['url_video']) && !empty($data['url_video'])) {
                $imgconcat = $fecha.'_portada_video';

                $url = $this->uploadImagen([
                    'img' => $data['portada_video'],
                    'ruta' => '/imagenes/publicaciones/'.$productoquery.'/'.$imgconcat.'.png'
                ]);

                if($permisosRespuesta){
                    parent::conectar();
                    parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }else{
                    parent::conectar();
                    parent::query("UPDATE productos_revision SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }
            }
            if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
                if(isset($cobrar)) $this->actualizarHistoricoTickets($cobrar['data'], $productoquery);
                $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
            }

            $envio = $this->insertarEnvio($producto['detalle_envio'], $fecha, $productoquery);

            if ( intval( $empresa ) == 1 ) {
                // Evaluamos si es la primera publicación.
                $this->validarPrimerArticulo( $producto, $permisosRespuesta );
            }else{
                $this->enviodecorreodepublicacion_product( $producto );
               
            }

            if(isset($data['tiene_colores_tallas']) && $data['tiene_colores_tallas'] == true){
                
                $array_llenar_detalles = NULL;
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['tallas']) && isset($pair['color_id'])){
                        foreach($pair['tallas'] as $talla){
                            $array_llenar_detalles = array(
                                'id_producto' => $productoquery,
                                'id_tallas' => $talla['id_talla'],
                                'id_colores' => $pair['color_id'],
                                'cantidad' => $talla['cantidad']
                            );
                            if(isset($talla['sku'])){
                                $array_llenar_detalles['sku'] = $talla['sku'];
                            }else{
                                $array_llenar_detalles['sku'] = '';
                            }
                            $this->guardarProductoColoresTallas($array_llenar_detalles);
                        }
                    }
                }
            }
            if($permisosRespuesta){  
                $request = array(
                    'status' => 'success',
                    'message'=> 'producto creado',
                    'revision' => 0,
                    'data' => $productoquery
                );
            }else{
                $request = array(
                    'status' => 'success',
                    'message'=> 'tu producto ha sido enviado a revisión',
                    'revision' => 1,
                    'data' => $productoquery
                ); 
            }
            
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no se pudo crear el producto',
                'data' => null
            );
        }
        return $request;
    }

    public function publicar( Array $data ){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        // Data E-mail:
        // Nombre del producto
        // codigo subasta
        // tipo de publicacion
        // categoria
        // fecha inicio
        // fecha finalizacion




        $request = null;
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);
        $data = $this->mapeoVender($data);
        $producto = $data;
        // print_r($data);
        
        // if( intval( $producto['exposicion'] ) == 1 ){
        //     // Exposición GRATUITA: Solo se permiten 3 articulos con este tipo de exposición
        //     // Los demás deberán migrar a otro tipo de exposición.
        //     if ( !$this->getCountVentasGratuitas( $producto ) ) {
        //         return array(
        //             'status' => 'errorUsoMaximoExposicion',
        //             'message'=> 'Ya has cumplido el tope de articulos publicados con este tipo de exposición',
        //             'data' => null
        //         );
        //     }
        // }

        $envio = $data['direccion_envio'];
        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});
        $textfullcompleto = $producto['producto'].' '.$producto['marca'].' '.$producto['modelo'].' '.$producto['titulo'].' '.$producto['categoria']['CategoryName'].' '.$producto['subcategoria']['CategoryName'];
        // print implode(',', extractKeyWords($textfullcompleto));
        $producto['keywords'] = implode(',', $this->extractKeyWords($textfullcompleto));
        $producto['categoria'] = $producto['categoria']['CategoryID'];
        $producto['subcategoria'] = $producto['subcategoria']['CategoryID'];
        // if($producto['exposicion'] == 3) $producto['cantidad'] = $producto['cantidad'] - $producto['cantidad_exposicion'];
        
        $empresa = 0;
        if($producto['empresa'] == true) $empresa = 1;


        $precio_usd = null;
        $array_monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('https://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);
                        // return "paso #1: " . $precio_usd . " --- oferta: " . floatval($data['porcentaje_oferta']);
                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);
                        // return "paso #2: " . $precio_usd . " --- oferta: " . floatval($data['porcentaje_oferta']);
                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
                // return "paso #3: " . $precio_usd;
            }else{
                $precio_usd = $data['precio'];
                // return "paso #4: " . $precio_usd;
            }
        }

        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        // return "paso #5: " . $precio_usd;
        if( $precio_usd < $this->montoxminimoxpublicar ) {
            return array(
                'status' => 'errorMontoMinimoPublicar',
                'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                
                'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                'costo_dolar' => $array_monedas_local['costo_dolar'],
                'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'], 2),
                'symbolMonedaLocal' => $array_monedas_local['code'],
                'data' => null
            );
        }
        if ( floatval($data['porcentaje_oferta']) < 0 || floatval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd;// - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],2),
                    'symbolMonedaLocal' => $array_monedas_local['code'],
                    'data' => null
                );
                
            }
        }


        $producto['precio_usd'] = $precio_usd;
        $producto['moneda_local'] = $monedas_local;

        // return $precio_usd;

        $cobrar = null;

        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1) {
            $producto['subasta']['precio_usd'] = $precio_usd;
            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            $producto['subasta']['precio'] = $this->truncNumber(($producto['subasta']['precio_usd'] / $precios[$producto['subasta']['moneda']]), 6);
            unset($nodo);

            $validarsubasta = $this->validarTipoSubasta(
                $producto['subasta'],
                $data['iso_code_2']
            );

            if($validarsubasta['status'] == 'fail') return $validarsubasta;
            $producto['subasta']['datos_subasta'] = $validarsubasta['data'];

            // return $producto['subasta'];

            if ($producto['subasta']['datos_subasta']['id'] != 6){
                $tickets = $this->verTicketsUsuario([
                    'uid' => $producto['uid'],
                    'empresa' => $empresa,
                    'plan' => $producto['subasta']['datos_subasta']['id'],
                    'cantidad' => $producto['subasta']['cantidad']
                ]);
                if($tickets['status'] == 'fail') return $tickets;
                $tickets = $tickets['data'];
    
                $cobrar = $this->cobrarTicekts([
                    'tickets' => $tickets, 
                    'cantidad' => $producto['subasta']['cantidad'],
                    'fecha' => $fecha
                ]);
                if($cobrar['status'] != 'success') return $cobrar;
            }
        }

        $tipoSubasta = 0;
        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
            $tipoSubasta = intval($producto['subasta']['tipo']);
        }
        $genero = 3;
        if(isset($data['genero'])){
            $genero = $data['genero'];
        }

        parent::conectar();
        $insertarproducto = "INSERT INTO productos(
            uid,
            tipo,
            tipoSubasta,
            producto,
            marca,
            modelo,
            titulo,
            descripcion,
            categoria,
            subcategoria,
            condicion_producto,
            garantia,
            estado,
            fecha_creacion,
            fecha_actualizacion,
            ultima_venta,
            cantidad,
            moneda_local,
            precio,
            precio_usd,
            precio_publicacion,
            precio_usd_publicacion,
            oferta,
            porcentaje_oferta,
            porcentaje_tax,
            exposicion,
            cantidad_exposicion,
            envio,
            id_direccion,
            pais,
            departamento,
            ciudad,
            latitud,
            longitud,
            codigo_postal,
            direccion,
            keywords,
            cantidad_vendidas,
            empresa,
            url_video,
            genero
        )
        VALUES(
            '$producto[uid]',
            '$producto[tipo]',
            '$tipoSubasta',
            '$producto[producto]',
            '$producto[marca]',
            '$producto[modelo]',
            '$producto[titulo]',
            '$producto[descripcion]',
            '$producto[categoria]',
            '$producto[subcategoria]',
            '$producto[condicion_producto]',
            '$producto[garantia]',
            '1',
            '$fecha',
            '$fecha',
            '$fecha',
            '$producto[cantidad]',
            '$producto[moneda_local]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[precio]',
            '$producto[precio_usd]',
            '$producto[oferta]',
            '$producto[porcentaje_oferta]',
            '$producto[porcentaje_tax]',
            '$producto[exposicion]',
            '$producto[cantidad_exposicion]',
            '$producto[envio]',
            '$envio[id]',
            '$envio[pais]',
            '$envio[departamento]',
            '$envio[ciudad]',
            '$envio[latitud]',
            '$envio[longitud]',
            '$envio[codigo_postal]',
            '$envio[direccion]',
            '$producto[keywords]',
            0,
            $empresa,
            '$producto[url_video]',
            '$genero'
        )";

        $productoquery = parent::queryRegistro($insertarproducto);
        parent::cerrar();
        if ($productoquery) {
            $fotos = $this->subirFotosProducto($fotos, $fecha, $productoquery);
            if(isset($data['url_video']) && !empty($data['url_video'])) {
                $imgconcat = $fecha.'_portada_video';
                $url = $this->uploadImagen([
                    'img' => $data['portada_video'],
                    'ruta' => '/imagenes/publicaciones/'.$productoquery.'/'.$imgconcat.'.png',
                ]);
                parent::conectar();
                parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$productoquery'");
                parent::cerrar();
            }
            if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
                if(isset($cobrar)) $this->actualizarHistoricoTickets($cobrar['data'], $productoquery);
                $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
            }

            $envio = $this->insertarEnvio($producto['detalle_envio'], $fecha, $productoquery);

            if ( intval( $empresa ) == 1 ) {
                // Evaluamos si es la primera publicación.
                $this->validarPrimerArticulo( $producto );
            }else{
                $this->enviodecorreodepublicacion_product( $producto );
               
            }

            $request = array(
                'status' => 'success',
                'message'=> 'producto creado',
                'data' => $productoquery
            );
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no se pudo crear el producto',
                'data' => null
            );
        }
        return $request;
    }

    public function misPublicaciones(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        if(!isset($data['pagina'])) $data['pagina'] = 1;
        
        $pagina = floatval($data['pagina']);
        $numpagina = 9;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        // Discriminar productos segun la data del filtro
        // tipoPublicacion == 0, TODO
        // tipoPublicacion == 1, PRODUCTOS NORMALES
        // tipoPublicacion == 2, SUBASTAS (NORMALES Y (BRONZE,...))
        
        $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
        $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";
        if( isset($data['tipoPublicacion']) ){
            if ( intval($data['tipoPublicacion']) == 0 ) {
                $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";

            }else if ( intval($data['tipoPublicacion']) == 1 ) {
                $tipoPublicacion = " AND p.tipoSubasta = 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta = 0 ";

            }else if ( intval($data['tipoPublicacion']) == 2 ) {
                $tipoPublicacion = " AND p.tipoSubasta > 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta > 0 ";

            }else {
                $tipoPublicacion = " AND p.tipoSubasta >= 0 ";
                $tipoPublicacion2 = " AND pr.tipoSubasta >= 0 ";

            }
        }

        $estado = "AND p.estado >= 0";
        $estado2 = "AND pr.estado >= 0";
        if(isset($data['estado'])){ 
            $estado = "AND p.estado = '$data[estado]'"; 
            $estado2 = "AND pr.estado = '$data[estado]'";
        }

        $exposicion = "";
        $exposicion2 = "";
        if(isset($data['exposicion'])){
            $exposicion = "AND p.exposicion = '$data[exposicion]'";
            $exposicion2 = "AND pr.exposicion = '$data[exposicion]'";
        }

        parent::conectar();

        // $selectxpublicaciones =
        // "SELECT * FROM (
        //     SELECT *,(@row_number:=@row_number+1) AS num FROM(
        //         SELECT p.estado estado2, p.*, p.precio AS precio_local
        //         ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas, ps.inscritos
        //         FROM productos p 
        //         LEFT JOIN productos_subastas ps on ps.id_producto = p.id
        //         JOIN (SELECT @row_number := 0) r 
        //         WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $tipoPublicacion $estado $exposicion
        //         ORDER BY p.fecha_creacion DESC
        //     ) AS datos  ORDER BY datos.fecha_creacion DESC
        // ) as datos2 WHERE datos2.num BETWEEN '$desde' AND '$hasta';";
        $selectxpublicaciones = "";
        $revisionConsulta = "";
        if(!isset($data['estado'])){

            $revisionConsulta = " union(
                SELECT pr.estado estado2, pr.*, pr.precio AS precio_local
                ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas, ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' $tipoPublicacion2 $estado2 $exposicion2 AND pr.id NOT IN (Select pro.id from productos pro)
              ) ";

            $selectxpublicaciones =  "SELECT * FROM (
                SELECT *,(@row_number:=@row_number+1) AS num FROM(
                  SELECT p.estado estado2, p.*, pr.id_productos_revision_estados as revision_status, pr.motivo , p.precio AS precio_local
                  ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas, ps.inscritos
                  FROM productos p 
                  left join productos_revision pr on pr.id = p.id
                  LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                  JOIN (SELECT @row_number := 0) r 
                  WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $tipoPublicacion $estado $exposicion".
                  $revisionConsulta."ORDER BY fecha_creacion DESC
                ) AS datos ORDER BY datos.fecha_creacion DESC 
              ) as datos2  WHERE datos2.num BETWEEN '$desde' AND '$hasta'";
        }else if(isset($data['estado']) && ($data['estado'] != 3 || $data['estado'] != "3") ){
            $selectxpublicaciones = "SELECT * FROM (
            SELECT *,(@row_number:=@row_number+1) AS num FROM(
                SELECT p.estado estado2, p.*, p.precio AS precio_local
                ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas, ps.inscritos
                FROM productos p 
                LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                JOIN (SELECT @row_number := 0) r 
                WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $tipoPublicacion $estado $exposicion
                ORDER BY p.fecha_creacion DESC
            ) AS datos  ORDER BY datos.fecha_creacion DESC
        ) as datos2 WHERE datos2.num BETWEEN '$desde' AND '$hasta';";
        }else if(isset($data['estado']) && ($data['estado'] == 3 || $data['estado'] == "3")){
            $selectxpublicaciones = " SELECT * FROM (
            SELECT *,(@row_number:=@row_number+1) AS num FROM(
            SELECT pr.estado estado2, pr.*, pr.precio AS precio_local, pr.id_productos_revision_estados as revision_status
            ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas, ps.inscritos
            FROM productos_revision pr 
            LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
            JOIN (SELECT @row_number := 0) r 
            WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' $tipoPublicacion2 AND pr.id_productos_revision_estados = 0 $exposicion2
            ORDER BY pr.fecha_creacion DESC
            ) AS  datos  ORDER BY datos.fecha_creacion DESC
            ) AS  datos2 WHERE datos2.num BETWEEN '$desde' AND '$hasta';";
        }

        
        
        $publicaciones = parent::consultaTodo($selectxpublicaciones);

        if(count($publicaciones) < 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron publicaciones', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = "";
        $revisionConsulta2 = "";
        if(!isset($data['estado'])){
            $revisionConsulta2 = " union(
                SELECT pr.estado estado2, pr.*, pr.precio AS precio_local
                ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas, ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' $tipoPublicacion2 $estado2 $exposicion2 AND pr.id NOT IN (Select pro.id from productos pro)
              ) ";
              $selecttodos = "SELECT count( * ) as contar FROM(
                SELECT p.estado estado2, p.*, pr. id_productos_revision_estados as revision_status, pr.motivo, p.precio AS precio_local
                    ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas, ps.inscritos
                    FROM productos p 
                    left join productos_revision pr on pr.id = p.id
                    LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                    JOIN (SELECT @row_number := 0) r 
                    WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $tipoPublicacion $estado $exposicion ".$revisionConsulta2." ORDER BY fecha_creacion DESC)as contar";
        }else if(isset($data['estado']) && ($data['estado'] != 3 || $data['estado'] != "3") ){
            $selecttodos = "SELECT count(*) as contar FROM (
                SELECT p.estado estado2, p.*, p.precio AS precio_local
                ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = p.id) AS visitas, ps.inscritos
                FROM productos p 
                LEFT JOIN productos_subastas ps on ps.id_producto = p.id
                WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $tipoPublicacion $estado $exposicion
                ORDER BY p.fecha_creacion DESC) as contador";
        }else if(isset($data['estado']) && ($data['estado'] == 3 || $data['estado'] == "3")){
            $selecttodos = "SELECT count( * ) as contar FROM(
                SELECT pr.estado estado2, pr.*, pr.precio AS precio_local
                ,(SELECT SUM(hp.visitas) as contador FROM hitstorial_productos hp WHERE hp.id_producto = pr.id) AS visitas, ps.inscritos
                FROM productos_revision pr 
                LEFT JOIN productos_subastas ps on ps.id_producto = pr.id
                WHERE pr.uid = '$data[uid]' AND pr.empresa = '$data[empresa]' $tipoPublicacion2 AND pr.id_productos_revision_estados = 0  $exposicion2
            ) as contador
            ";
        }


        // $selecttodos = "SELECT COUNT(p.id) AS contar FROM productos p WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' $estado $exposicion;";
        
        // echo $selecttodos;
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $publicaciones = $this->mapPublicaciones($publicaciones);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'mis publicaciones', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($publicaciones), 'total_productos' => $todoslosproductos, 'data' => $publicaciones);
    }

    public function misSubastas(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        $pagina = floatval($data['pagina']);
        $numpagina = 5;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        // $select_precio = $this->selectMonedaLocalUser($data);

        $where = "";
        parent::conectar();
        $selecxmisxsubastas = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT 
                    ps.*, 
                    p.producto,
                    p.marca,
                    p.modelo,
                    p.titulo,
                    p.descripcion,
                    p.foto_portada,
                    p.exposicion,
                    pse.descripcion AS estado_descripcion,
                    pst.descripcion AS tipo_descripcion,
                    p.tipoSubasta,
                    p.oferta,
                    p.porcentaje_oferta,
                    p.precio AS precioProducto, 
                    p.precio AS precio_local_user, 
                    p.moneda_local AS moneda_local_user, 
                    p.moneda_local,
                    p.pais
                FROM productos_subastas ps
                INNER JOIN productos p ON ps.id_producto = p.id
                INNER JOIN productos_subastas_tipo pst ON ps.tipo = pst.id
                INNER JOIN productos_subastas_estado pse ON ps.estado = pse.id
                JOIN (SELECT @row_number := 0) r 
                WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.estado != '0'
                ORDER BY ps.fecha_actualizacion DESC
                )as datos 
            ORDER BY fecha_actualizacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        

        $productos = parent::consultaTodo($selecxmisxsubastas);
        if(count($productos) <= 0){
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron subastas productos', 'pagina'=> $pagina, 'total_paginas'=> 0, 'productos' => 0, 'total_productos' => 0, 'data' => null);
        }

        $productos = $this->mapProductosSubastas($productos);
        $selecttodos = "SELECT COUNT(ps.id) AS contar 
        FROM productos_subastas ps
        INNER JOIN productos p ON ps.id_producto = p.id 
        WHERE p.uid = '$data[uid]' AND p.empresa = '$data[empresa]' AND p.estado != '0'
        ORDER BY ps.fecha_actualizacion DESC;";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'subastas productos', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'productos' => count($productos), 'total_productos' => $todoslosproductos, 'data' => $productos);

    }

    public function pausarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $data['actualizar'] = "estado = '2',";

        //para enviar correo de pausa
        $data['tipo_edicion_correo']= 20;  
        //para enviar correo de pausa
      

        return $this->actualizarPublicacion($data);
    }

    public function activarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $data['actualizar'] = "estado = '1',";
        return $this->actualizarPublicacion($data);
    }

    public function eliminarPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);


        $getType = $this->getPublicationType($data['id']);
        if($getType == "subasta"){

            $inscritos = $this->getAuctionUsers($data['id']);
            $total = $inscritos->fetch_assoc();

            if($total["inscritos"] > 0){
                return array(
                    'status' => 'errorDeleteAuction',
                    'message'=> 'No puedes eliminar una publicacion con inscritos',
                    'data' => null
                );
            }
            else{
                //Editando por aquí.
                $fecha = intval(microtime(true)*1000);
                $data['fecha'] = $fecha;
                $data['actualizar'] = " estado = '0', ";
                    //para enviar correo de elimino
                    $data['tipo_edicion_correo']= 21;  
                     //para enviar correo de elimino
      
                return $this->actualizarPublicacion($data);
            }
        }
        else{
            $fecha = intval(microtime(true)*1000);
            $data['fecha'] = $fecha;
            $data['actualizar'] = " estado = '0', ";
                //para enviar correo de elimino
                $data['tipo_edicion_correo']= 21;  
                //para enviar correo de elimino
            return $this->actualizarPublicacion($data);
        }
    }

    public function productoId(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxproducto = "SELECT p.*, p.precio AS precio_local
        , (SELECT AVG(cv.promedio) as general_prom FROM calificacion_vendedor cv WHERE cv.id_producto = p.id) AS calificacion
        FROM productos p
        WHERE p.id = '$data[id]' AND p.uid = '$data[uid]' AND p.empresa = '$data[empresa]'";
        $producto = parent::consultaTodo($selectxproducto);
        parent::cerrar();
        if(count($producto) <= 0) return array('status' => 'fail', 'message'=> 'no producto', 'data' => null);
        
        $producto = $this->mapProductos($producto)[0];
        return array('status' => 'success', 'message'=> 'producto', 'data' => $producto);
    }

    public function fotosproductoId(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa'])) return  array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        parent::conectar();
        $selectxfotosproducto = "SELECT pf.*
        FROM productos_fotos pf
        WHERE pf.id_publicacion = '$data[id]' AND pf.estado = '1'";
        $fotos = parent::consultaTodo($selectxfotosproducto);
        parent::cerrar();
        if(count($fotos) <= 0) return array('status' => 'fail', 'message'=> 'no fotos prodcuto', 'data' => null);
        $fotos = $this->mapFotosProductos($fotos);
        return array('status' => 'success', 'message'=> 'fotos prodcuto', 'data' => $fotos);
    }

    public function editarPublicacion(Array $data){
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos 1', 'data' => null);
        $publicacion = $this->publicacionId($data);
        if($publicacion['status'] == 'fail') return $publicacion;
        $data['publicacion'] = $publicacion['data'];

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;

        if($data['tipo'] == 1) return $this->editarTituloPublicacion($data);
        if($data['tipo'] == 2) return $this->editarCategoriaPublicacion($data);
        if($data['tipo'] == 3) return $this->editarDescripcionPublicacion($data);
        if($data['tipo'] == 4) return $this->editarCondicionPublicacion($data);
        if($data['tipo'] == 5) return $this->editarFotosPublicacion($data);
        if($data['tipo'] == 6) return $this->editarMarcaPublicacion($data);
        if($data['tipo'] == 7) return $this->editarPrecioPublicacion($data);
        if($data['tipo'] == 8) return $this->editarEnvioPublicacion($data);
        if($data['tipo'] == 9) return $this->editarModeloPublicacion($data);
        if($data['tipo'] == 10) return $this->editarDireccionPublicacion($data);
        if($data['tipo'] == 11) return $this->editarCantidadPublicacion($data);
        if($data['tipo'] == 12) return $this->editarTipoPublicacion($data);
        if($data['tipo'] == 13) return $this->editarExposicionPublicacion($data);
        if($data['tipo'] == 14) return $this->editarVideoPublicacion($data);
        if($data['tipo'] == 15) return $this->editarNombreProducto($data);


        return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);
    }

    function editarNombreProducto(Array $data){
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['producto'])) return array('status' => 'fail', 'message'=> 'faltan datos 2', 'data' => null);

        $nombre =addslashes($data['producto']);
        $publicacion = $data['publicacion'];
    
        $data['actualizar'] = "producto = '$nombre',";
        return $this->actualizarPublicacion($data);

    }

    public function validarSubastaMonedaLocal(Array $data)
    {
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['iso_code_2']) || !isset($data['precio']) || !isset($data['cantidad'])) {

            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => null,
                'datos'   => 'uid: ' . $data['uid'] . ' - ' . 'empresa: ' . $data['empresa'] . ' - ' . 'iso_code_2: ' . $data['iso_code_2'] . ' - ' . 'precio: ' . $data['precio'] . ' - ' . 'cantidad: ' . $data['cantidad']
            );
        }

        $precio_usd = null;
        if(isset($data['iso_code_2'])){
            if($data['iso_code_2'] == 'US'){
                return $this->validarTipoSubasta(
                    [
                        'precio_usd' => $data['precio']
                    ],
                    $data['iso_code_2']
                ); 
            }
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    $precio_usd = $this->truncNumber(($data['precio'] / $monedas_local['costo_dolar']), 2);
                }
            }
        }

        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'isocode2 invalido', 'data' => null);

        return $this->validarTipoSubasta(
            [
                'precio_usd' => $precio_usd
            ],
            $data['iso_code_2']
        );
    }

    function publicacionId(Array $data)
    {
        parent::conectar();
        $selectxpublicacion = "SELECT p.*, p.precio AS precio_local, (@row_number:=@row_number+1) AS num 
        FROM productos p 
        JOIN (SELECT @row_number := 0) r 
        WHERE p.id = '$data[id]' AND p.uid = '$data[uid]' AND p.empresa = '$data[empresa]'";
        $publicacion = parent::consultaTodo($selectxpublicacion);
        parent::cerrar();
        if(!$publicacion){
            // parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no se encontraron la publicacion', 'data' => null);
        } 
        // parent::conectar();
        $publicacion = $this->mapPublicaciones($publicacion, true);
        // parent::cerrar();
        return array('status' => 'success', 'message'=> 'mi publicacion', 'data' => $publicacion[0]);
    }

    function subirFotosProducto(Array $fotos, Int $fecha, Int $id)
    {

        parent::conectar();
        $imgconcat = null;
        $validar = true;
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/$id";
        if (!file_exists($nombre_fichero)) {
            mkdir($nombre_fichero, 0777, true);
        }
        
        foreach ($fotos as $x => $foto) {
            $pos = strrpos($foto['img'], ';base64,');
            if ($pos === false) { // nota: tres signos de igual
                $url = $foto['img'];
            }else{
                $imgconcat = $fecha.'_'.$x;
                $url = $this->uploadImagen([
                    'img' => $foto['img'],
                    'ruta' => '/imagenes/publicaciones/'.$id.'/'.$imgconcat.'.png',
                ]);

            }
            $insertarfoto = "INSERT INTO productos_fotos(id_publicacion,foto, estado, fecha_creacion, fecha_actualizacion) VALUES ('$id', '$url', '1', '$fecha', '$fecha')";
            if(!$insertarfoto) $validar = false;
            if($x == 0) {
                
                $p_revision = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$id'");
                if($p_revision && $p_revision[0]['id_productos_revision_estados'] == 0){
                    parent::query("UPDATE productos_revision SET foto_portada = '$url' WHERE id = '$id'");  
                }else{
                    parent::query("UPDATE productos SET foto_portada = '$url' WHERE id = '$id'");
                }
                // parent::query("UPDATE productos SET foto_portada = '$url' WHERE id = '$id'");
            }
            parent::query($insertarfoto);
        }
        parent::cerrar();
        if(!$validar) return array('status' => 'fail', 'message'=> 'fotos no actualizadas', 'data' => null);
        // $notificado = NULL;
        parent::conectar();
        // parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
        // parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
        $notificado = parent::consultaTodo("SELECT * FROM buyinbig.pedir_fotos WHERE id_producto = '$id'; ");
        
        $producto = parent::consultaTodo(" select * from productos where id = '$id';");
        parent::cerrar();
        // echo $notificado[0]['uid_cliente'];
        if(count($notificado) > 0){
                $notificacion = new Notificaciones();
                foreach($notificado as $enviar_notif){
                    if($enviar_notif["notificado"] == 0){
                        $notificacion->insertarNotificacion([
                            'uid' => $enviar_notif['uid_cliente'],
                            'empresa' => $enviar_notif['empresa_cliente'],
                            'text' => 'El vendedor ha publicado nuevas fotos del producto ('.$producto[0]['titulo'].') que deseas',
                            'es' => 'El vendedor ha publicado nuevas fotos del producto ('.$producto[0]['titulo'].') que deseas',
                            'en' => 'The seller has published new photos of the product ('.$producto[0]['titulo'].') you want',
                            'keyjson' => '',
                            'url' => ''
                        ]);
                        parent::conectar();
                        parent::query("UPDATE buyinbig.pedir_fotos SET notificado = 1 where id_producto = '$id';");
                        parent::cerrar();
                    }
                    
                }
        }
        
        
        return array('status' => 'success', 'message'=> 'fotos actualizadas', 'data' => null);
    }

    function uploadImagen(Array $data)
    {
        $base64 = base64_decode(explode(';base64,', $data['img'])[1]);
        $filepath1 = $_SERVER['DOCUMENT_ROOT'] . $data['ruta'];
        file_put_contents($filepath1, $base64);
        chmod($filepath1, 0777);
        $url = $_SERVER['SERVER_NAME'] . $data['ruta'];
        return 'https://'.$url;
    }

    function validarTipoSubasta(Array $subasta, String $iso_code_2 = "US")
    {

        $tipo = '';
        if(isset($subasta['tipo']) && !empty($subasta['tipo'])) $tipo = "pst.id = '$subasta[tipo]' AND ";

        parent::conectar();
        $selectxtipoxsubasta = "SELECT pst.* 
        FROM productos_subastas_tipo pst 
        WHERE $tipo '$subasta[precio_usd]' >= pst.rango_inferior_usd  AND  '$subasta[precio_usd]' <= pst.rango_superior_usd;";
        $selecttipo = parent::consultaTodo($selectxtipoxsubasta);

        $selectxtiposx = "SELECT * FROM productos_subastas_tipo";
        $selecttipos = parent::consultaTodo($selectxtiposx);
        parent::cerrar();

        if ( count($selecttipo) <= 0 )
        {


            if ( $iso_code_2 != "US") {
                $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            }
            
            $precio_local_code = "US";
            $precio_local_rango_inferior = 0;
            $precio_local_rango_superior = 0;
            $precio_local_rango_inferior_us = 0;
            $precio_local_rango_superior_us = 0;


            foreach ( $selecttipos as $x => $tipoSelect )
            {
                if ( $tipoSelect['id'] == 5 ) {
                    // ID: 1, Subastas premium
                    if ( $iso_code_2 != "US") {
                        if(count($array_monedas_locales) > 0){
                            $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                            if(count($monedas_local) > 0) {
                                $monedas_local = $monedas_local[0];
                                $precio_local_rango_superior = $this->truncNumber(($tipoSelect['rango_superior_usd'] * $monedas_local['costo_dolar']), 2);
                                $precio_local_code = $monedas_local['code'];
                                $precio_local_rango_superior_us = $tipoSelect;
                            }
                        }
                    }else{
                        $precio_local_rango_superior = $tipoSelect['rango_superior_usd'];
                    }
                }else if ( $tipoSelect['id'] == 6 ) {
                    // ID: 6, Subastas normales nasbi
                    if ( $iso_code_2 != "US") {
                        if(count($array_monedas_locales) > 0){
                            $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                            if(count($monedas_local) > 0) {
                                $monedas_local = $monedas_local[0];
                                $precio_local_rango_inferior = $this->truncNumber(($tipoSelect['rango_inferior_usd'] * $monedas_local['costo_dolar']), 2);
                                $precio_local_code = $monedas_local['code'];
                                $precio_local_rango_inferior_us = $tipoSelect;
                            }
                        }
                    }else{
                        $precio_local_rango_inferior = $tipoSelect['rango_inferior_usd'];
                    }
                }else{

                }
            }
            return array(
                'status'                           => 'errorTipoSubasta',
                'message'                          => 'tipo de subasta no valida',
                'data'                             => null,
                'valorUSD'                         => $subasta['precio_usd'],
                'iso_code_2'                       => $iso_code_2,
                
                'code'                             => $precio_local_code,
                
                'precio_local_rango_inferior'      => floatval($precio_local_rango_inferior),
                'precio_local_rango_inferior_mask' => $this->maskNumber(floatval($precio_local_rango_inferior), 2),
                
                'precio_local_rango_superior'      => floatval($precio_local_rango_superior),
                'precio_local_rango_superior_mask' => $this->maskNumber(floatval($precio_local_rango_superior), 2),
                'precio_local_rango_inferior_us'   => $precio_local_rango_inferior_us,
                'precio_local_rango_superior_us'   => $precio_local_rango_superior_us

            );
        };

        return array(
            'status' => 'success',
            'message'=> 'tipo de subasta valida',
            'data' => $selecttipo[0],
            'valorUSD' => $subasta['precio_usd'],
        );
    }
    
    function getRangoSubastas(Array $subasta)
    {
        if( !isset($subasta['iso_code_2']) ) {

            return array(
                'status'  => 'fail',
                'message' => 'faltan datos',
                'data'    => $subasta
            );
        }
        parent::conectar();

        $selectxtiposx = "SELECT * FROM productos_subastas_tipo";
        $selecttipos = parent::consultaTodo($selectxtiposx);
        parent::cerrar();

        // echo("##### yarin: " . $selectxtipoxsubasta);


        $iso_code_2 = $subasta['iso_code_2'];
        if ( $iso_code_2 != "US") {
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
        }
        
        $precio_local_code = "US";
        $precio_local_rango_inferior = 0;
        $precio_local_rango_superior = 0;
        $precio_local_rango_inferior_us = 0;
        $precio_local_rango_superior_us = 0;


        foreach ( $selecttipos as $x => $tipoSelect )
        {
            if ( $tipoSelect['id'] == 5 ) {
                // ID: 1, Subastas premium
                if ( $iso_code_2 != "US") {
                    if(count($array_monedas_locales) > 0){
                        $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                        if(count($monedas_local) > 0) {
                            $monedas_local = $monedas_local[0];
                            $precio_local_rango_superior = $this->truncNumber(($tipoSelect['rango_superior_usd'] * $monedas_local['costo_dolar']), 2);
                            $precio_local_code = $monedas_local['code'];
                            $precio_local_rango_superior_us = $tipoSelect;
                        }
                    }
                }else{
                    $precio_local_rango_superior = $tipoSelect['rango_superior_usd'];
                }
            }else if ( $tipoSelect['id'] == 6 ) {
                // ID: 6, Subastas normales nasbi
                if ( $iso_code_2 != "US") {
                    if(count($array_monedas_locales) > 0){
                        $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $iso_code_2);
                        if(count($monedas_local) > 0) {
                            $monedas_local = $monedas_local[0];
                            $precio_local_rango_inferior = $this->truncNumber(($tipoSelect['rango_inferior_usd'] * $monedas_local['costo_dolar']), 2);
                            $precio_local_code = $monedas_local['code'];
                            $precio_local_rango_inferior_us = $tipoSelect;
                        }
                    }
                }else{
                    $precio_local_rango_inferior = $tipoSelect['rango_inferior_usd'];
                }
            }else{

            }
        }
        return array(
            'status'                           => 'success',
            'message'                          => '....',
            'data'                             => $subasta['iso_code_2'],
            'iso_code_2'                       => $iso_code_2,
            
            'code'                             => $precio_local_code,
            
            'precio_local_rango_inferior'      => floatval($precio_local_rango_inferior),
            'precio_local_rango_inferior_mask' => $this->maskNumber(floatval($precio_local_rango_inferior), 2),
            
            'precio_local_rango_superior'      => floatval($precio_local_rango_superior),
            'precio_local_rango_superior_mask' => $this->maskNumber(floatval($precio_local_rango_superior), 2),
            'precio_local_rango_inferior_us'   => $precio_local_rango_inferior_us,
            'precio_local_rango_superior_us'   => $precio_local_rango_superior_us

        );
    }
    

    function verTicketsUsuario(Array $data)
    {
        $selectxticketsxuser = "SELECT nt.*, ntp.nombre AS nombre_plan 
        FROM nasbitickets nt 
        INNER JOIN nasbitickets_plan ntp ON nt.plan = ntp.id
        WHERE nt.uid = '$data[uid]' AND nt.empresa = '$data[empresa]' AND nt.uso = '1' AND nt.plan = '$data[plan]' AND nt.estado = 1
        ORDER BY fecha_creacion ASC";
        parent::conectar();
        $tickets = parent::consultaTodo($selectxticketsxuser);
        parent::cerrar();
        if(!$tickets) return array('status' => 'fail', 'message'=> 'no tickets', 'cantidad_tickets' => 0, 'data' => $data);

        $tickets = $this->mapTickets($tickets);
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
            'array_id' => [],
        ]);
        parent::cerrar();
        
        if(!$cobro) return array('status' => 'fail', 'message'=> 'no cobro tickets', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'cobro tickets', 'data' => $cobro);        
    }

    function actualizarTickets(Array $data)
    {

        $actual = $data['actual'];
        $ticket = $data['tickets'];
        $cantidad = $data['cantidad'];

        if($cantidad <= 0) return $data['array_id']; //true

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

        array_push($data['array_id'], $insertar);
        $actual++;
        return $this->actualizarTickets([
            'tickets' => $data['tickets'], 
            'actual' => $actual, 
            'cantidad' => $cantidad,
            'fecha' => $data['fecha'],
            'array_id' => $data['array_id'],
        ]);

    }

    function actualizarHistoricoTickets(Array $historico, Int $id_producto)
    {
        parent::conectar();
        foreach ($historico as $x => $hist) {
            $ubdatexticket = "UPDATE nasbitickets_historico 
            SET 
                id_producto = '$id_producto'
            WHERE id = '$hist'";
            parent::query($ubdatexticket);
        }
        parent::cerrar();
        
    }

    function insertarSubasta(Array $subasta, Int $fecha, Int $id, Int $precioDolar)
    {
        // $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
        // (264.2350000396352 * 0.4) / 5

        $fecha_inicio = $fecha + $this->tiempomaxsubasta;
        parent::conectar();
        $apostadores = 15;
        //if($subasta['datos_subasta']['id'] == 6) {}
        $selectednumber = parent::consultaTodo("select * from productos_subastas_tipo pst where '$precioDolar' BETWEEN rango_inferior_usd and rango_superior_usd");
        if (count($selectednumber) > 0) {
            $apostadores = $selectednumber[0]["num_apostadores"];
        }
        // if($subasta['datos_subasta']['id'] != 6) $apostadores = ($precioDolar * $this->porcetajexminxrecaudar) / $subasta['datos_subasta']['costo_ticket_usd'];
        $insertarxsubasta = "INSERT INTO productos_subastas
        (
            id_producto,
            moneda,
            precio,
            precio_usd,
            cantidad,
            tipo,
            estado,
            apostadores,
            inscritos,
            fecha_fin,
            fecha_creacion,
            fecha_actualizacion,
            fecha_inicio
        )
        VALUES
        (
            '$id',
            '$subasta[moneda]',
            '$subasta[precio]',
            '$subasta[precio_usd]',
            '$subasta[cantidad]',
            '$subasta[tipo]',
            '1',
            '$apostadores',
            '0',
            '$fecha',
            '$fecha',
            '$fecha',
            '$fecha_inicio'
        );";
        $insertar = parent::query($insertarxsubasta);
        parent::cerrar();
    }

    function insertarEnvio(Array $envio, Int $fecha, Int $id)
    {
        parent::conectar();
        $insertarxenvio = "INSERT INTO productos_envio
        (
            id_producto,
            largo,
            ancho,
            alto,
            unidad_distancia,
            peso,
            unidad_masa,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$id',
            '$envio[largo]',
            '$envio[ancho]',
            '$envio[alto]',
            '$envio[unidad_distancia]',
            '$envio[peso]',
            '$envio[unidad_masa]',
            '1',
            '$fecha',
            '$fecha'
        );";
        $insertar = parent::queryRegistro($insertarxenvio);
        parent::cerrar();
        if(!$insertar) return array('status' => 'fail', 'message'=> 'no se guardo el envio', 'data' => null);
        
        /* return $this->saveShippo([
            'length' => $envio['largo'],
            'width' => $envio['ancho'],
            'height' => $envio['alto'],
            'distance_unit' => $envio['unidad_distancia'],
            'weight' => $envio['peso'],
            'mass_unit' => $envio['unidad_masa'],
            'id' => $insertar
        ]); */
        
    }

    function saveShippo(Array $data)
    {
        $parcel = json_decode(Shippo_Parcel::create(array(
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'distance_unit' => $data['distance_unit'],
            'weight' => $data['weight'],
            'mass_unit' => $data['mass_unit'],
        )));
        $parcel = (array) $parcel;
        parent::conectar();
        $updatedireccion = "UPDATE productos_envio
        SET
            id_shippo = '$parcel[object_id]'
        WHERE id = '$data[id]'";
        $actualizar = parent::query($updatedireccion);
        parent::cerrar();
        if(!$actualizar) return array('status' => 'fail', 'message'=> 'no se actualizo el envio', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'envio actualizado', 'data' => null);
    }

    function editarTituloPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['titulo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $titulo = addslashes($data['titulo']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$publicacion['modelo'].' '.$titulo.' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "titulo = '$titulo',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarCategoriaPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        
        $categoria = addslashes($data['categoria']['CategoryID']);
        $subcategoria = addslashes($data['subcategoria']['CategoryID']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$publicacion['modelo'].' '.$publicacion['titulo'].' '.addslashes($data['categoria']['CategoryName']).' '.addslashes($data['subcategoria']['CategoryName']);
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "categoria = '$categoria',
            subcategoria = '$subcategoria',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarDescripcionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['descripcion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $descripcion = addslashes($data['descripcion']);
        $data['actualizar'] = "descripcion = '$descripcion',";
        return $this->actualizarPublicacion($data);
    }

    function editarModeloPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['modelo']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $modelo = addslashes($data['modelo']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$publicacion['marca'].' '.$modelo.' '.$publicacion['titulo'].' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "modelo = '$modelo',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarDireccionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['id_direccion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $direccion = $this->direccionId($data, false);
        if($direccion['status'] != 'success') return $direccion;
        $direccion = $direccion['data'];

        $data['actualizar'] = "id_direccion = '$direccion[id]',
            pais = '$direccion[pais]',
            departamento = '$direccion[departamento]',
            ciudad = '$direccion[ciudad]',
            latitud = '$direccion[latitud]',
            longitud = '$direccion[longitud]',
            codigo_postal = '$direccion[codigo_postal]',
            direccion = '$direccion[direccion]',";
        return $this->actualizarPublicacion($data);

    }

    function editarCantidadPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['cantidad'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $cantidad = addslashes($data['cantidad']);
        
        $data['actualizar'] = "cantidad = '$cantidad',";
        return $this->actualizarPublicacion($data);
    }

    function editarTipoPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['tipo_publicacion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $tipo_publicacion = addslashes($data['tipo_publicacion']);
        
        $data['actualizar'] = "tipo = '$tipo_publicacion',";
        return $this->actualizarPublicacion($data);
    }

    function editarExposicionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['exposicion'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $exposicion = addslashes($data['exposicion']);

        $data['actualizar'] = "exposicion = '$exposicion',";
        //para enviar correo de expo
        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        $data['tipo_edicion_correo']= 13; 
        $data['porducto_antes']= $producto[0]; 
        //para enviar correo de expo
        return $this->actualizarPublicacion($data);
    }

    function editarVideoPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['url_video']) || !isset($data['portada_video'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        $url_video = addslashes($data['url_video']);
        $portada_video = addslashes($data['portada_video']);

        $fecha = intval(microtime(true)*1000);
        $pos = strrpos($portada_video, ';base64,');
        if ($pos === false) { // nota: tres signos de igual
            $portada_video = $portada_video;
        }else{
            $imgconcat = $fecha.'_portada_video';
            $url = $this->uploadImagen([
                'img' => $data['portada_video'],
                'ruta' => '/imagenes/publicaciones/'.$data['id'].'/'.$imgconcat.'.png',
            ]);
            $portada_video = $url;
        }
        
        
        $data['actualizar'] = "url_video = '$url_video',
        portada_video = '$portada_video',";
        return $this->actualizarPublicacion($data);
    }

    public function direccionId(Array $data, Bool $estado){
        if(!isset($data) || !isset($data['id_direccion']) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'cantidad'=> null, 'data' => null);

        $condicionestado = "";
        if($estado == true) $condicionestado = "AND d.estado = 1";
        
        parent::conectar();
        $selectdireccion = "SELECT * FROM direcciones d WHERE d.id = '$data[id_direccion]' AND d.uid = '$data[uid]' AND d.empresa = '$data[empresa]' $condicionestado ORDER BY fecha_creacion DESC";
        $direcciones = parent::consultaTodo($selectdireccion);
        parent::cerrar();
        if(count($direcciones) <= 0) return array('status' => 'fail', 'message'=> 'la direccion no existe','data' => null);
            
        $direcciones = $this->mapDirecciones($direcciones);
        return array('status' => 'success', 'message'=> 'direccion', 'data' => $direcciones[0]);
    }

    function editarFotosPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['fotos_producto'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $desactivarfotos = $this->desactivarFotos($data);
        if($desactivarfotos['status'] == 'fail') return $desactivarfotos;

        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});

        return $this->subirFotosProducto($fotos, $data['fecha'], $data['id']);
    }

    function desactivarFotos(Array $data)
    {
        parent::conectar();
        $updatexpublicacion = "UPDATE productos_fotos 
        SET 
            estado = 0,
            fecha_actualizacion = '$data[fecha]'
        WHERE id_publicacion = '$data[id]' AND estado = 1";
        $update = parent::query($updatexpublicacion);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'publicacion no actualizada', 'data' => null);

        return array('status' => 'success', 'message'=> 'publicacion actualizada', 'data' => null);

    }

    function editarMarcaPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['marca']) || !isset($data['categoria']) || !isset($data['subcategoria'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $marca = addslashes($data['marca']);
        $publicacion = $data['publicacion'];
        $textfullcompleto = $publicacion['producto'].' '.$marca.' '.$publicacion['modelo'].' '.$publicacion['titulo'].' '.$data['categoria']['CategoryName'].' '.$data['subcategoria']['CategoryName'];
        $keywords = implode(',', $this->extractKeyWords($textfullcompleto));

        
        $data['actualizar'] = "marca = '$marca',
            keywords = '$keywords',";
        return $this->actualizarPublicacion($data);
    }

    function editarPrecioPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['precio']) || !isset($data['iso_code_2']) || !isset($data['oferta'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $precio_usd = null;
        $monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                    $precio_usd = $this->truncNumber(($data['precio'] / $monedas_local['costo_dolar']), 2);
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US') {
            $precio_usd = $data['precio'];
            $moneda_local = 'USD';
        }
        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        if( $precio_usd < $this->montoxminimoxpublicar ) {
            return array(
                'status' => 'errorMontoMinimoPublicar',
                'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                
                'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                'costo_dolar' => $monedas_local['costo_dolar'],
                'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $monedas_local['costo_dolar'],
                'montoMinimoMonedaLocalMak' => $this->maskNumber($this->montoxminimoxpublicar * $monedas_local['costo_dolar'], 2),
                'symbolMonedaLocal' => $monedas_local['code'],
                'data' => null
            );
        }

        if ( floatval($data['porcentaje_oferta']) < 0 || intval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $monedas_local['costo_dolar'], 2),
                    'symbolMonedaLocal' => $monedas_local['code'],
                    'data' => null
                );
                
            }
        }

        $precio_usd;
        $moneda_local = $monedas_local['code'];

        if($data['oferta'] == 0) $data['porcentaje_oferta'] = 0;
        $data['actualizar'] = "precio = '$data[precio]',
        precio_usd = '$precio_usd',
        moneda_local = '$moneda_local',
        oferta = '$data[oferta]',
        porcentaje_oferta = $data[porcentaje_oferta],";
        return $this->actualizarPublicacion($data);
    }

    function editarEnvioPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['tipo_envio']) || !isset($data['largo']) || !isset($data['ancho']) || !isset($data['alto']) || !isset($data['unidad_distancia']) || !isset($data['peso']) || !isset($data['unidad_masa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        $fecha = intval(microtime(true)*1000);
        parent::conectar();
        $updatexpublicacion = "UPDATE productos_envio 
        SET 
            estado = 0,
            fecha_actualizacion = '$data[fecha]'
        WHERE id_producto = '$data[id]'";
        $update = parent::query($updatexpublicacion);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'error al cambiar el envio', 'data' => null);

        $envio = $this->insertarEnvio($data, $fecha, $data['id']);
        if($envio['status'] != 'success') return $envio;

        $data['actualizar'] = "envio = '$data[tipo_envio]',";
        return $this->actualizarPublicacion($data);
    }

    function editarCondicionPublicacion(Array $data)
    {
        if(!isset($data) || !isset($data['id']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo']) || !isset($data['condicion_producto']) || !isset($data['garantia'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        $condicion_producto = addslashes($data['condicion_producto']);
        $garantia = floatval($data['garantia']);
        $data['actualizar'] = "condicion_producto = '$condicion_producto',
            garantia = '$garantia',";
            
        return $this->actualizarPublicacion($data);
    }

    function getPublicationType(String $id){
        parent::conectar();
        $status = "SELECT id_producto FROM productos_subastas WHERE id_producto = '$id'";
        $update = parent::query($status);
        parent::cerrar();
        if($update) return "subasta";
        if(!$update) return "producto";
    }

    function getAuctionUsers(String $id){
        parent::conectar();
        $status = "SELECT inscritos FROM productos_subastas WHERE id_producto = '$id'";
        $update = parent::query($status);
        parent::cerrar();
        if($update) return $update;
        if(!$update) return "noData";
    }

    function actualizarPublicacion(Array $data)
    {
        $actualizar = $data['actualizar'];
        parent::conectar();
        $updatexpublicacion = "UPDATE productos SET $actualizar fecha_actualizacion = '$data[fecha]' WHERE id = '$data[id]' AND uid = '$data[uid]' AND empresa = '$data[empresa]'";

        $update = parent::query($updatexpublicacion);
        parent::cerrar();
        if(!$update) return array('status' => 'fail', 'message'=> 'publicacion no actualizada', 'data' => $updatexpublicacion);
        //para enviar correo si se edito
        if(isset($data['tipo_edicion_correo'])){
            $this->enviar_correo_edicion($data);
        }
        //para enviar correo si se edito 

        $this->envio_de_correo_general_ediccion($data); 
        return array('status' => 'success', 'message'=> 'publicacion actualizada', 'data' => null);
    }

    function detalleEnvioTransaccion(Array $data)
    {
        parent::conectar();
        $selecxenvio = "SELECT pe.*
        FROM productos_envio pe
        WHERE pe.id_producto = '$data[id]' AND estado = 1
        ORDER BY fecha_creacion DESC;";
        $envio = parent::consultaTodo($selecxenvio);
        parent::cerrar();
        if(count($envio) <= 0) return null;
        
        return $this->mapeoEnvio($envio)[0];
    }

    function mapeoVender(Array $data)
    {
        $data['uid'] = addslashes($data['uid']);
        $data['tipo'] = addslashes($data['tipo']);
        $data['producto'] = addslashes($data['producto']);
        $data['marca'] = addslashes($data['marca']);
        $data['modelo'] = addslashes($data['modelo']);
        $data['titulo'] = addslashes($data['titulo']);
        $data['descripcion'] = addslashes($data['descripcion']);
        // $data['categoria'] = addslashes($data['categoria']);
        // $data['subcategoria'] = addslashes($data['subcategoria']);
        $data['condicion_producto'] = addslashes($data['condicion_producto']);
        $data['garantia'] = addslashes($data['garantia']);
        $data['cantidad'] = addslashes($data['cantidad']);
        $data['moneda_local'] = addslashes($data['moneda_local']);
        $data['precio'] = addslashes($data['precio']);
        $data['precio_usd'] = addslashes($data['precio_usd']);
        $data['oferta'] = addslashes($data['oferta']);
        $data['porcentaje_oferta'] = addslashes($data['porcentaje_oferta']);
        $data['exposicion'] = addslashes($data['exposicion']);
        $data['cantidad_exposicion'] = addslashes($data['cantidad_exposicion']);
        $data['envio'] = addslashes($data['envio']);
        $data['direccion_envio']['pais'] = addslashes($data['direccion_envio']['pais']);
        $data['direccion_envio']['departamento'] = addslashes($data['direccion_envio']['departamento']);
        $data['direccion_envio']['ciudad'] = addslashes($data['direccion_envio']['ciudad']);
        $data['direccion_envio']['latitud'] = addslashes($data['direccion_envio']['latitud']);
        $data['direccion_envio']['longitud'] = addslashes($data['direccion_envio']['longitud']);
        $data['direccion_envio']['codigo_postal'] = addslashes($data['direccion_envio']['codigo_postal']);
        $data['direccion_envio']['direccion'] = addslashes($data['direccion_envio']['direccion']);
        $url_video = '';
        $portada_video = '';
        if(isset($data['url_video']) && !empty($data['url_video'])) {
            $url_video = addslashes($data['url_video']);
            $portada_video = addslashes($data['portada_video']);
        }
        $data['url_video'] = $url_video;
        $data['portada_video'] = $portada_video;
        // $data['keywords'] = addslashes($data['keywords']);
        foreach ($data['fotos_producto'] as $x => $foto) {
            $foto['id'] = addslashes($foto['id']);
            $foto['img'] = addslashes($foto['img']);
            $data['fotos_producto'][$x] = $foto;
        }
        
        return $data;
    }

    function mapTickets(Array $tickets)
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

        return array(
            'cantidad_tickets' => $cantidad_tickets,
            'tickets' => $tickets,
        );
    }

    function mapPublicaciones(Array $productos, Bool $condicionestado = false){
        foreach ($productos as $x => $producto) {
            unset($producto['precio']);
            
            $producto['id'] = floatval($producto['id']);
            $producto['uid'] = floatval($producto['uid']);
            $producto['tipo'] = floatval($producto['tipo']);
            $producto['categoria'] = floatval($producto['categoria']);
            $producto['subcategoria'] = floatval($producto['subcategoria']);
            $producto['condicion_producto'] = floatval($producto['condicion_producto']);
            $producto['garantia'] = floatval($producto['garantia']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['estado_descripcion'] = floatval($producto['estado']) == 1 ? 'Activa' : 'Pausada';

            if(isset($producto['revision_status'])){
                if(floatval($producto['revision_status']) == 0){
                    $producto['estado'] = 3;
                    $producto['estado_descripcion'] = 'Revisión';
                }else if(floatval($producto['revision_status'] == 2)){
                    $producto['estado'] = 5;
                    $producto['estado_descripcion'] = 'Rechazada';
                }
            }
            
            if(intval($producto['tipoSubasta']) > 0){
                $producto['subasta_terminada'] = false;
                $subasta = [];
                if($condicionestado == true){
                    parent::conectar();
                    $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id_producto = '$producto[id]';");
                    parent::cerrar();
                }else{
                    $subasta = parent::consultaTodo("SELECT * FROM productos_subastas WHERE id_producto = '$producto[id]';");
                }
                
                if(intval($subasta[0]['estado']) == 4){
                    $producto['subasta_terminada'] = true;
                }
            }
            
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['ultima_venta'] = floatval($producto['ultima_venta']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask']= $this->maskNumber($producto['precio_usd'], 2);
            $producto['precio_local'] = floatval($producto['precio_local']);
            $producto['precio_local_mask']= $this->maskNumber($producto['precio_local'], 2);
            $producto['oferta'] = floatval($producto['oferta']);
            $producto['porcentaje_oferta'] = floatval($producto['porcentaje_oferta']);
            $producto['porcentaje_tax'] = floatval($producto['porcentaje_tax']);
            $producto['exposicion'] = floatval($producto['exposicion']);
            $producto['cantidad_exposicion'] = floatval($producto['cantidad_exposicion']);
            $producto['envio'] = floatval($producto['envio']);
            $producto['pais'] = floatval($producto['pais']);
            $producto['departamento'] = floatval($producto['departamento']);
            $producto['latitud'] = floatval($producto['latitud']);
            $producto['longitud'] = floatval($producto['longitud']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['cantidad_vendidas'] = floatval($producto['cantidad_vendidas']);
            $producto['id_direccion'] = floatval($producto['id_direccion']);

            $producto['precio_descuento_usd'] = $producto['precio_usd'];
            $producto['precio_descuento_usd_mask'] = $this->maskNumber($producto['precio_descuento_usd'], 2);
            $producto['precio_descuento_local'] = $producto['precio_local'];
            $producto['precio_descuento_local_mask'] = $this->maskNumber($producto['precio_descuento_local'], 2);

            if(!isset($producto['visitas'])) $producto['visitas'] = 0;
            if(isset($producto['visitas'])) $producto['visitas'] = floatval($producto['visitas']);
            if($condicionestado == true) $producto['detalles_envio'] = $this->detalleEnvioTransaccion($producto);
            if($condicionestado == true) $producto['detalles_direccion'] = $this->direccionId($producto, false)['data'];

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);
            }

            $productos[$x] = $producto;
        }
        return $productos;
    }

    function mapProductosSubastas($productos){
        $dataMonedaLocal = $this->selectMonedaLocalUserByCountryID([
            'country_id' => $productos[ 0 ]['pais']
        ]);
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
                $producto['paso_por_aqui_1'] = "XX_SIII";
                $producto['porcentaje_entrada'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio']), 2);
                $producto['porcentaje_entrada_mask']= $this->maskNumber($producto['porcentaje_entrada'], 2);

                $producto['porcentaje_entrada_usd'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_usd_mask']= $this->maskNumber($producto['porcentaje_entrada_usd'], 2);

                $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                
                if(isset($dataRecibida) && isset($dataRecibida['iso_code_2_money'])) {

                    if ( ($dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) && $dataMonedaLocal['code'] != "US" ) {


                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precioProducto']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);
                        $producto['paso_por_aqui_2'] = "XX_SIII";

                        
                    }else{
                        $moneda_local = $this->getMonedaLocalUser($dataRecibida);
                        $producto['porcentaje_entrada_local_user'] = $this->truncNumber(($this->entradaxxubastasxnormales * $producto['precio_usd']), 2);
                        $producto['porcentaje_entrada_local_user_mask']= $this->maskNumber($producto['porcentaje_entrada_local_user'], 2);

                    }
                }

                $inscritos = $producto['inscritos'];
                if ($inscritos < 15) $inscritos = 15;
                if ($inscritos > 35) $inscritos = 35;
                $producto['porcentaje'] = $this->truncNumber(floatval(( (($inscritos * $this->entradaxxubastasxnormales) - $this->porcentajexrestarxnormal )) * 100), 1);
            }

            if(isset($producto['precio_local_user'])){
                // if ( $dataRecibida['iso_code_2_money'] == $dataMonedaLocal['iso_code_2'] && ($dataMonedaLocal['code'] == $producto['moneda_local'])) {
                    $producto['precio_local_user'] = floatval($this->truncNumber($producto['precioProducto'],2));
                    $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
                    
                // }else{
                //     $producto['precio_local_user'] = floatval($this->truncNumber($producto['precio_local_user'],2));
                //     $producto['precio_local_user_mask']= $this->maskNumber($producto['precio_local_user'], 2);
                // }
            }else{
                $producto['precio_local_user'] = $producto['precio_usd'];
                $producto['precio_local_user_mask'] = $producto['precio_usd_mask'];
                $producto['moneda_local_user'] = 'USD';

            }

            if($producto['tipoSubasta'] == 6) {
                $producto['precio_subasta_local_user'] = $producto['precio_local_user'] - floatval($producto['precio_local_user'] * ($producto['porcentaje'] / 100));
                $producto['precio_subasta_local_user_mask'] = $this->maskNumber($producto['precio_subasta_local_user'], 2);
                $producto['paso_por_aqui_3'] = "XX_SIII";
            }

            $productos[$x] = $producto;
        }
        return $productos;
    }
    function selectMonedaLocalUser(Array $data)
    {
        $select_precio = "";
        if(isset($data['iso_code_2_money'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
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
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'country_id', $data['country_id']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                }
            }
        }

        return $monedas_local;
    }

    function mapProductos($productos){
        foreach ($productos as $x => $producto) {
            unset($producto['precio']);
            
            $producto['id'] = floatval($producto['id']);
            $producto['uid'] = floatval($producto['uid']);
            $producto['tipo'] = floatval($producto['tipo']);
            $producto['categoria'] = floatval($producto['categoria']);
            $producto['subcategoria'] = floatval($producto['subcategoria']);
            $producto['condicion_producto'] = floatval($producto['condicion_producto']);
            $producto['garantia'] = floatval($producto['garantia']);
            $producto['estado'] = floatval($producto['estado']);
            $producto['fecha_creacion'] = floatval($producto['fecha_creacion']);
            $producto['fecha_actualizacion'] = floatval($producto['fecha_actualizacion']);
            $producto['ultima_venta'] = floatval($producto['ultima_venta']);
            $producto['cantidad'] = floatval($producto['cantidad']);
            $producto['precio_usd'] = floatval($producto['precio_usd']);
            $producto['precio_usd_mask']= $this->maskNumber($producto['precio_usd'], 2);
            $producto['precio_local'] = floatval($producto['precio_local']);
            $producto['precio_local_mask']= $this->maskNumber($producto['precio_local'], 2);
            $producto['oferta'] = floatval($producto['oferta']);
            $producto['porcentaje_oferta'] = floatval($producto['porcentaje_oferta']);
            $producto['exposicion'] = floatval($producto['exposicion']);
            $producto['cantidad_exposicion'] = floatval($producto['cantidad_exposicion']);
            $producto['envio'] = floatval($producto['envio']);
            $producto['pais'] = floatval($producto['pais']);
            $producto['departamento'] = floatval($producto['departamento']);
            $producto['latitud'] = floatval($producto['latitud']);
            $producto['longitud'] = floatval($producto['longitud']);
            $producto['cantidad_vendidas'] = floatval($producto['cantidad_vendidas']);
            if(isset($producto['calificacion'])) $producto['calificacion'] = $this->truncNumber(floatval($producto['calificacion']), 2);

            $producto['precio_descuento_usd'] = $producto['precio_usd'];
            $producto['precio_descuento_usd_mask'] = $this->maskNumber($producto['precio_descuento_usd'], 2);
            $producto['precio_descuento_local'] = $producto['precio_local'];
            $producto['precio_descuento_local_mask'] = $this->maskNumber($producto['precio_descuento_local'], 2);

            if($producto['oferta'] == 1){
                $producto['precio_descuento_usd'] = $producto['precio_usd'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_usd'] = $producto['precio_usd'] - $producto['precio_descuento_usd'];
                $producto['precio_descuento_usd']= floatval($this->truncNumber($producto['precio_descuento_usd'], 2));
                $producto['precio_descuento_usd_mask']= $this->maskNumber($producto['precio_descuento_usd'], 2);

                $producto['precio_descuento_local'] = $producto['precio_local'] * ($producto['porcentaje_oferta']/100);
                $producto['precio_descuento_local'] = $producto['precio_local'] - $producto['precio_descuento_local'];
                $producto['precio_descuento_local']= floatval($this->truncNumber($producto['precio_descuento_local'], 2));
                $producto['precio_descuento_local_mask']= $this->maskNumber($producto['precio_descuento_local'], 2);

            }

            $productos[$x] = $producto;
        }
        return $productos;
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

    function mapeoEnvio(Array $envios)
    {
        foreach ($envios as $x => $envio) {
            $envio['id'] = floatval($envio['id']);
            $envio['id_producto'] = floatval($envio['id_producto']);
            $envio['largo'] = floatval($envio['largo']);
            $envio['ancho'] = floatval($envio['ancho']);
            $envio['alto'] = floatval($envio['alto']);
            $envio['peso'] = floatval($envio['peso']);
            $envio['estado'] = floatval($envio['estado']);
            $envio['fecha_creacion'] = floatval($envio['fecha_creacion']);
            $envio['fecha_actualizacion'] = floatval($envio['fecha_actualizacion']);
            $envios[$x] = $envio;
        }

        return $envios;
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
    
    function extractKeyWords(String $string) {
        mb_internal_encoding('UTF-8');
        $stopwords = array();
        $string = preg_replace('/[\pP]/u', '', trim(preg_replace('/\s\s+/iu', '', mb_strtolower($string))));
        $matchWords = array_filter(explode(' ',$string) , function ($item) use ($stopwords) { return !($item == '' || in_array($item, $stopwords) || mb_strlen($item) <= 2 || is_numeric($item));});
        $wordCountArr = array_count_values($matchWords);
        arsort($wordCountArr);
        return array_keys(array_slice($wordCountArr, 0, 10));
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

    // function getCountVentasGratuitas( Array $data ){
    //     parent::conectar();
    //     $selectxresumenxventas =
    //     "SELECT COUNT(pt.id) AS ventas FROM productos_transaccion pt INNER JOIN  buyinbig.productos p ON( id_producto = p.id) WHERE pt.uid_vendedor = '$data[uid]' AND pt.empresa = '$data[empresa]' AND pt.estado >= 13 AND p.exposicion = 1;";

    //     $selectresumenventas = parent::consultaTodo($selectxresumenxventas);
    //     parent::cerrar();

    //     if(count($selectresumenventas) <= 0) {
    //         return true; // Si puede crear más articulos con esta categoria (GRATUITA).
    //     }else{
    //         // Si tiene menos de 3 ventas completadas, puede seguir creando articulos en
    //         // esta categoria (GRATUITA)
    //         return intval( $selectresumenventas[0]['ventas'] ) <= 3;
    //     }
    // }

    function getRestriccionesPublicar( Array $data )
    {
        if( !isset($data) || !isset($data['iso_code_2']) ) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        }

        $precio_usd = null;
        $array_monedas_local = null;

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';

            return array(
                'status' => 'status',
                
                'montoMinimoPublicarMonedaLocal' => $this->montoxminimoxpublicar,
                'montoMinimoPublicarMonedaLocalMask' => $this->truncNumber($this->montoxminimoxpublicar, 2),

                'montoMinimoPublicarUSD' => $this->montoxminimoxpublicar,
                'montoMinimoPublicarUSDMas' => $this->maskNumber($this->montoxminimoxpublicar, 2),

                'costo_dolar' => 1,

                'symbolMonedaLocal' => $monedas_local,

                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,

                'montoMinimoPublicarConDescuentoMonedaLocal' => ($this->montoxminimoxpublicar) * ($this->porcentajexmaximoxpublicar/100),

                'montoMinimoPublicarConDescuentoMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar) * ($this->porcentajexmaximoxpublicar/100), 2),

                'montoMinimoPublicarConDescuentoUSD' => $this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100),

                'montoMinimoPublicarConDescuentoUSDMask' => $this->maskNumber($this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100), 2)
            );
        }


        if(isset($data['iso_code_2'])){

            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            
            $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);

            if(count($array_monedas_locales) > 0){

                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    return array(
                        'status' => 'success',
                        
                        'montoMinimoPublicarMonedaLocal' => ($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']),
                        'montoMinimoPublicarMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']), 2),

                        'montoMinimoPublicarUSD' => $this->montoxminimoxpublicar,
                        'montoMinimoPublicarUSDMask' => $this->maskNumber($this->montoxminimoxpublicar, 2),

                        'costo_dolar' => $array_monedas_local['costo_dolar'],

                        'symbolMonedaLocal' => $array_monedas_local['code'],

                        'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,

                        'montoMinimoPublicarConDescuentoMonedaLocal' => ($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']) * ($this->porcentajexmaximoxpublicar/100),

                        'montoMinimoPublicarConDescuentoMonedaLocalMask' => $this->maskNumber(($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar']) * ($this->porcentajexmaximoxpublicar/100), 2),

                        'montoMinimoPublicarConDescuentoUSD' => $this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100),

                        'montoMinimoPublicarConDescuentoUSDMask' => $this->maskNumber($this->montoxminimoxpublicar * ($this->porcentajexmaximoxpublicar/100), 2)
                    );
                }else{
                    return array('status' => 'fail', 'message'=> 'faltan datos', 'iso_code_2' => $data['iso_code_2']);
                }
                
            }else{
                return array('status' => 'failDivisasJSON', 'message'=> 'Verificar los montos en http://peers2win.com/js/fidusuarias.json', 'iso_code_2' => $data['iso_code_2']);
            }
        }

        return array('status' => 'fail', 'message'=> 'faltan datos', 'iso_code_2' => $data['iso_code_2']);
    }
    
    function validarPrimerArticulo( Array $data, Int $permisos )
    {
        // Cumpliendo con el sistema de correos
        // Debemos validar si el usuario crea su primera subasta.
        $producto = [];
        $subasta = [];
        if ( intval( $data['empresa'] ) == 1 ) {
            
            parent::conectar();
            $misProductos = parent::consultaTodo(" SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

           if ( count($misProductos) == 1 && $permisos == 1) {

                $producto = $misProductos[0];
                // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                    // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas) > 0 ) {
                        $subasta = $misSubastas[ 0 ];
                    }
                }
                $this->miPrimeraPublicacion([
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                ]);
           }else{  // en el caso que sea una empresa sin permiso y ya tenga productos activados con anterioridad
                $this->enviodecorreodepublicacion_product( $data );
            }

        //    else{
        //     $this->enviodecorreodepublicacion_product( $data );
        //    }

            // else{
            //     return array(
            //         'status' => 'fail',
            //         'mis_productos'=> $misProductos
            //     );
            // }
        }
        // else{
        //     return array(
        //         'status' => 'fail',
        //         'mis_productos'=> $misProductos
        //     );
        // }
    }

    // public function verEmpresa_dataempresa_con_uid(Array $data)
    // {
    //     if(!isset($data) || !isset($data['uid'])) return array('status' => 'fail', 'message'=> 'no data', 'data' => null);

    //     parent::conectar();
    //     $selectxempresa = "SELECT e.* FROM empresas e WHERE e.id = '$data[uid]';";
    //     $empresa = parent::consultaTodo($selectxempresa);

    //     if(count($empresa) <= 0) return array('status' => 'fail', 'message'=> 'no datos empresa', 'data' => null);
        
    //     $empresa = $this->mapEmpresa($empresa, true);
    //     $empresa = $empresa[0];
    //     parent::cerrar();
    //     return  array('status' => 'success', 'message'=> 'datos empresa', 'data' => $empresa);
    // }


    function miPrimeraPublicacion( Array $data ){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_empresa=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        if ($data_subasta == null){
            $data_subasta=[]; 
        }
        $this->htmlEmailprimer_producto_empresa( $data_producto, $data_subasta, $data_empresa["data"]); 
    }

    public function htmlEmailprimer_producto_empresa(Array $data_producto, Array $data_subasta, Array $data_empresa){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo4tuprimerproducto.html");
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();

 
     
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];


       
    

       
        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$data_empresa['nombre'], $html);
      
        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
      //  $html = str_replace("{{trans11}}", $json->trans11, $html); 
         $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
         $html = str_replace("{{trans14}}",$json->trans14, $html);
         $html = str_replace("{{trans15}}",$json->trans15, $html);
        
        
         
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
        
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_empresa["uid"]."&act=0&em=".$data_empresa["empresa"], $html); 

        $para      = $data_empresa['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans104_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function htmlEmailprimer_producto_empresa_revision(Array $data_producto, Array $data_subasta, Array $data_empresa){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_empresa["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo4revision.html");
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();

 
     
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];


       
    

       
        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$data_empresa['nombre'], $html);
        
        $html = str_replace("{{trans147_}}", $json->trans147_, $html);
        $html = str_replace("{{trans148_}}", $json->trans148_, $html);

        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
      //  $html = str_replace("{{trans11}}", $json->trans11, $html); 
         $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
         $html = str_replace("{{trans14}}",$json->trans14, $html);
         $html = str_replace("{{trans15}}",$json->trans15, $html);
        
        
         
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_empresa["uid"]."&act=0&em=".$data_empresa["empresa"], $html); 

        $para      = $data_empresa['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans104_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function datosUserGeneral2( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }


    function enviodecorreodepublicacion_product(Array $data ){
        // Cumpliendo con el sistema de correos
        $producto = [];
        $subasta = [];

        $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));

        if($permisosRespuesta){
            parent::conectar();
            $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();
            if($misProductos && count($misProductos) > 0){
                $producto = $misProductos[0];
                        // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                            // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                            if ( count($misSubastas) > 0 ) {
                                $subasta = $misSubastas[0];
                            }
                }
                $this->preparar_data_email_publicacion([
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                ]);
            }
        }else{
            $producto_revision = [];
            $subasta_revision = [];
            parent::conectar();
            $misProductos_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_revision WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

            if($misProductos_revision && count($misProductos_revision) > 0){
                $producto_revision = $misProductos_revision[0];

                if($producto_revision['tipoSubasta'] > 0){
                    parent::conectar();
                    $misSubastas_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto_revision[id]'; ");
                    parent::cerrar();

                        if ( count($misSubastas_revision) > 0 ) {
                            $subasta_revision = $misSubastas_revision[0];
                        }
                }
                $this->preparar_data_email_publicacion_revision([
                    'status'   => 'success',
                    'producto' => $producto_revision,
                    'subasta'  => $subasta_revision
                ]);
            }
        }
        // $producto = $misProductos[0];
        //         // validamos si es subasta o no.
        // if ( $producto['tipoSubasta'] > 0) {
        //             // SI es subasta o no.
        //     parent::conectar();
        //     $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
        //     parent::cerrar();

        //             if ( count($misSubastas) > 0 ) {
        //                 $subasta = $misSubastas[0];
        //             }
        // }
        // $this->preparar_data_email_publicacion([
        //     'status'   => 'success',
        //     'producto' => $producto,
        //     'subasta'  => $subasta
        // ]);

    }

    function preparar_data_email_publicacion_revision(Array $data){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);

        if (empty($data_subasta)){
            $data_subasta=[]; 
        }else{
            if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales 
                $this->htmlEmailpublicacion_subasta_revision( $data_producto, $data_subasta, $data_user["data"]);  
            }else if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]!= 6){
                $this->htmlEmailpublicacion_subasta_premium_revision( $data_producto, $data_subasta, $data_user["data"]);
            }  
        }
        $this->htmlEmailpublicacion_product_revision( $data_producto, $data_subasta, $data_user["data"]);
        
    }

    function preparar_data_email_publicacion(Array $data){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        if (empty($data_subasta)){
            $data_subasta=[]; 
        }else{
            if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]== 6 ){// Subasta normales 
                $this->htmlEmailpublicacion_subasta( $data_producto, $data_subasta, $data_user["data"]);  
            }else if(($data_subasta["estado"]==1 || $data_subasta["estado"]==2) && $data_subasta["tipo"]!= 6){
               $this->htmlEmailpublicacion_subasta_premium( $data_producto, $data_subasta, $data_user["data"]);
            }  
        }
        $this->htmlEmailpublicacion_product( $data_producto, $data_subasta, $data_user["data"]); 
    }


    public function htmlEmailpublicacion_product(Array $data_producto, Array $data_subasta, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo1.html");
        
            $categorias=$this->get_categoria_product(); 
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];

        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans87_}}",$json->trans243, $html);
            $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans87_}}",$json->trans87_, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$data_producto["titulo"], $html);
      
        $html = str_replace("{{trans93_}}", $json->trans93_, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);
        
     
         $html = str_replace("{{trans89_}}",$json->trans89_, $html);
         $html = str_replace("{{trans94_}}",$json->trans94_, $html);
        
        
         $html = str_replace("{{trans11}}",$json->trans11, $html);
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans105_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    public function htmlEmailpublicacion_product_revision(Array $data_producto, Array $data_subasta, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/corre1revision.html");
        
            $categorias=$this->get_categoria_product(); 
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];

        if (empty($data_subasta)){
            $html = str_replace("{{trans09}}","", $html);
            $html = str_replace("{{codigo_subasta}}","", $html);
            $html = str_replace("{{trans10}}", "", $html);
            $html = str_replace("{{tipo_publicacion}}","", $html);
            $html = str_replace("{{fecha_fin}}","", $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}","", $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans146_}}",$json->trans146_, $html);
            $html = str_replace("{{trans87_}}","", $html);
            $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
            
        }else{
            
            $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
            $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
            $html = str_replace("{{trans09}}",$json->trans09, $html);
            $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
            $html = str_replace("{{trans10}}", $json->trans10, $html);
            $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
            $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
            $html = str_replace("{{trans13}}","", $html);
            $html = str_replace("{{trans12}}",$json->trans12, $html);
            $html = str_replace("{{fecha_inicio}}","", $html);
            $html = str_replace("{{trans146_}}",$json->trans146_, $html);
            $html = str_replace("{{trans87_}}",$json->trans87_, $html);
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$data_producto["titulo"], $html);
      
        $html = str_replace("{{trans93_}}", $json->trans93_, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}", $json->trans25_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);
        
     
         $html = str_replace("{{trans147_}}",$json->trans147_, $html);
         $html = str_replace("{{trans94_}}",$json->trans94_, $html);
        
        
         $html = str_replace("{{trans11}}",$json->trans11, $html);
         $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
         $html = str_replace("{{trans06_}}",$json->trans06_, $html);
         $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $data_producto['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $data_producto['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 

        $para      = $data_user['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans153_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function get_categoria_product(){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
        return $categorias;
    }

    function enviar_correo_edicion(Array $data){
        switch ($data['tipo_edicion_correo']) {
            case 13:
                $this->enviarcorreo_actualizo_expo($data); 
                break;
            case 20: //pauso 
                $this->enviarcorreo_actualizo_pauso($data); 
                break;
            case 21: //elimino
                $this->enviarcorreo_actualizo_elimino($data); 
                break;
            default:
                # code...
                break;
        }
    }


    function enviarcorreo_actualizo_expo(Array $data){
        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        $producto_anterior= $data["porducto_antes"]; 
        $exposicion_anterior= intval($producto_anterior["exposicion"]);  
        $exposicion_nueva= intval($producto[0]["exposicion"]);          
        if($exposicion_anterior==1 &&  $exposicion_nueva==2){
            $this->htmlEmailedicionexposicion_gra_to_clasica($producto[0], $data_user["data"]); 
        }else if($exposicion_anterior==1 &&  $exposicion_nueva==3){
            $this->htmlEmailedicionexposicion_gra_to_premium($producto[0], $data_user["data"]); 
        }else if($exposicion_anterior==2 &&  $exposicion_nueva==3){
            $this->htmlEmailedicionexposicion_cla_to_premium($producto[0], $data_user["data"]); 
        }

    }



    public function htmlEmailedicionexposicion_gra_to_clasica(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo2.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
      
        $html = str_replace("{{trans_07_icon_subasta_brand}}", $json->trans_07_icon_subasta_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{foto_product}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans_09_brand}}",$json->trans_09_brand, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);
        
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{trans130_brand}}",$json->trans130_brand, $html);
        

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
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function get_product_por_id(Array $data){
        parent::conectar();
        $misProductos = parent::consultaTodo("SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND id = '$data[id]' ORDER BY id DESC; ");
        parent::cerrar();
        return $misProductos;
    }


    public function htmlEmailedicionexposicion_gra_to_premium(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo3.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
        $html = str_replace("{{trans130_brand}}", $json->trans130_brand, $html);
        $html = str_replace("{{trans131_brand}}", $json->trans131_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        $html = str_replace("{{trans95_}}",$json->trans95_, $html);

        
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
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
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    public function htmlEmailedicionexposicion_cla_to_premium(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo4.html");

        if($data_producto["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }
        
        $html = str_replace("{{trans113_brand}}", $json->trans113_brand, $html);
        $html = str_replace("{{trans129_brand}}", $json->trans129_brand, $html);
        $html = str_replace("{{trans95_}}", $json->trans95_, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{trans97_}}", $json->trans97_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        

        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
    
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
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
        $titulo    = $json->trans107_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function envio_de_correo_general_ediccion(Array $data){
        $data_subasta=[];
        $es_subasta=0;


        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        
         if($producto[0]["tipoSubasta"]!= "0"){
            $es_subasta=1;

            $data_subasta=  $this->get_data_subasta_por_id_producto([
                'id' => $producto[0]['id']
            ]);

            $data_subasta= $data_subasta[0]; 
        }
    
        $this->htmlEmailactualizacion_general_product($producto[0], $data_user["data"], $data_subasta, $es_subasta); 
        
    }


    public function htmlEmailactualizacion_general_product(Array $data_producto, Array $data_user, Array $data_subasta, Int $es_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo15publicacionctualizada.html");
        
        if($es_subasta==1){
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        

       
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
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
        $titulo    = $json->trans108_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function htmlEmailactivacion_producto(Array $data_producto, Array $data_user, Array $data_subasta, Int $es_subasta){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo5activo_product.html");
        
        if($es_subasta==1){
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
           
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        }



        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans149_}}",$json->trans149_, $html);
        $html = str_replace("{{trans150_}}",$json->trans150_, $html);

        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
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
        $titulo    = $json->trans156_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }



    function enviarcorreo_actualizo_pauso($data){
        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        
      $this->htmlEmailpausa_publi($producto[0], $data_user["data"]); 

    }


    public function htmlEmailpausa_publi(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo16publicacionpausada.html");
        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans81}}", $json->trans81, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans82}}", $json->trans82, $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{foto_producto_pausada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans76}}",$json->trans76, $html);
        $html = str_replace("{{trans77}}",$json->trans77, $html);
        

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
       
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
        $titulo    = $json->trans110_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    public function enviarcorreo_actualizo_elimino(Array $data)
    {
        $data_user  =  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $producto=$this->get_product_por_id([
            'uid' => $data["uid"],
            'id' => $data["id"],
            'empresa'  => $data["empresa"] ]);
        
      $this->htmlEmailelimino($producto[0], $data_user["data"]); 
    }

    public function htmlEmailelimino(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo17publicacioneliminada.html");
        
        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);


        $html = str_replace("{{trans78}}", $json->trans78, $html);
        $html = str_replace("{{trans66}}", $json->trans66, $html);

        $html = str_replace("{{publicacion_titulo_eliminada}}",$data_producto['titulo'] , $html);
        $html = str_replace("{{trans79}}", $json->trans79, $html);
        
        $html = str_replace("{{trans80}}", $json->trans80, $html);
        
       

        $html = str_replace("{{foto_producto_eliminado_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_vender}}",$json->link_to_vender, $html);
       
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
        $titulo    = $json->trans112_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function htmlEmailpublicacion_subasta(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo13subastanasbi.html");
  
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans89}}",$json->trans89, $html);
        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89_}}",$json->trans89_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans88_}}",$json->trans88_, $html);
        
        
        
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        // $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        

        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

        $html = str_replace("{{trans115_}}",$json->trans115_, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        
        $html = str_replace("{{trans90_}}",$json->trans90_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
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
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function htmlEmailpublicacion_subasta_revision(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo2revision.html");
  
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        // $html = str_replace("{{trans89}}",$json->trans89, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89_}}",$json->trans89_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans88_}}",$json->trans88_, $html);
        
        
        
        $html = str_replace("{{producto_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        

        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans147_}}",$json->trans147_, $html);
        $html = str_replace("{{trans115_}}",$json->trans115_, $html);
        
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
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
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function htmlEmailpublicacion_subasta_premium(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();    
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo14subastanasbipremium.html");
        
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans87}}",$json->trans87, $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        

        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans90}}",$json->trans90, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);

        $html = str_replace("{{foto_producto_subasta_premiun_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$data_subasta['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans92}}",$json->trans92, $html);
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        

        
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

       
        
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
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
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function htmlEmailpublicacion_subasta_premium_revision(Array $data_producto,Array $data_subasta ,Array $data_user){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();    
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $data_producto['categoria']);
            $categoria_product=$categoria_product[0];
        $fecha_inicio_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_inicio"])/1000);
        $fecha_fin_product= date('m/d/Y H:i:s:m', intval($data_subasta["fecha_fin"]/1000));
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo3revision.html");
        
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans87}}",$json->trans87, $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        

        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans147_}}",$json->trans147_, $html);

        $html = str_replace("{{foto_producto_subasta_premiun_brand}}",$data_producto['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans92}}",$json->trans92, $html);
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        

        
        $html = str_replace("{{codigo_subasta}}",$data_subasta["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

       
        
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
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
        $titulo    = $json->trans114_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }







    // INICIO COLORES TALLAS, REVISION  



    function crearColor(Array $data)
    {


        if(!isset($data) || !isset($data['nombre_es']) || !isset($data['nombre_en']) || !isset($data['nombre_corto']) || !isset($data['hexadecimal'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => $data);
        }

        parent::conectar();
        // $categorias = parent::consultaTodo($selectcategoria); // Leer datos.

        $esta_color = parent::consultaTodo("SELECT * FROM productos_colores WHERE hexadecimal = '$data[hexadecimal]'");
        $coloresquery = NULL; 
        if(!$esta_color){
            $coloresquery = parent::queryRegistro("INSERT INTO productos_colores(nombre_es,nombre_en,nombre_corto,hexadecimal) values('$data[nombre_es]','$data[nombre_en]','$data[nombre_corto]','$data[hexadecimal]')"); // Insert
        }else{
            return array("status"=>"yaExiste","mensaje"=>"ya existe el color");
        }
        
        parent::cerrar();
        
        if ($coloresquery) {
            return array('status' => 'success', 'message'=> 'color creado', 'data' => $data);
        }

        return array('status' => 'fail', 'message'=> 'no fue posible crear el color', 'data' => $data);
    }

    // function getColores(Array $data){

    //     parent::conectar();
    //     $colores = parent::consultaTodo("SELECT * FROM productos_colores ORDER BY id DESC");
    //     parent::cerrar();
    //     $respuesta = [];
    //     if($colores){
    //         $numXpagina = 10;
    //         $hasta = $data['pag']*$numXpagina;
    //         $desde = ($hasta-$numXpagina)+1;
    //         $respuesta  = [];
    //         for($i = 0; $i<$hasta; $i++){
    //             if($i < count($colores)){
    //                 if(($i + 1) >= $desde && ($i + 1) <= $hasta){
    //                     array_push($respuesta, $colores[$i]);
    //                 }
    //             }
    //         }
    //         $num_paginas = count($colores)/$numXpagina;
    //         $num_paginas = ceil($num_paginas);
            
    //         return array('status' => 'success', 'message'=> 'colores', 'data' => $respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
    //     }

    //     return array('status' => 'fail', 'message'=> 'error al obtener los colores');
    // }

    function getColores(Array $data){
        $colores = NULL;
            if(isset($data['ya_elegidos']) && count($data['ya_elegidos'])>0){

                $restricciones = '(';
                for ($i = 0; $i<count($data['ya_elegidos']); $i++) {
                    if($i == count($data['ya_elegidos']) -1){
                        $restricciones = $restricciones."'".$data['ya_elegidos'][$i]."')";
                    }else{
                        $restricciones = $restricciones."'".$data['ya_elegidos'][$i]."',";
                    } 
                }
    
                parent::conectar();
                $colores = parent::consultaTodo("SELECT * FROM productos_colores 
                WHERE productos_colores.hexadecimal NOT IN ".$restricciones." ORDER BY id DESC");
                parent::cerrar();
            }else{
                parent::conectar();
                $colores = parent::consultaTodo("SELECT * FROM productos_colores ORDER BY id DESC");
                parent::cerrar(); 
            }
            
            $respuesta = [];
            if($colores){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($colores)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $colores[$i]);
                        }
                    }
                }
                $num_paginas = count($colores)/$numXpagina;
                $num_paginas = ceil($num_paginas);
                
                return array('status' => 'success', 'message'=> 'colores', 'data' => $respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
            }
    
            return array('status' => 'fail', 'message'=> 'error al obtener los colores');
        
    }


    function crearTalla(Array $data){
        if(!isset($data) || !isset($data['nombre_es']) || !isset($data['nombre_en']) || !isset($data['tipo'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => $data);
        }

        parent::conectar();
        $tallasquery = parent::queryRegistro("INSERT INTO productos_tallas(nombre_es,nombre_en,tipo) values('$data[nombre_es]','$data[nombre_en]','$data[tipo]')"); // Insert
        parent::cerrar();

        if ($tallasquery) {
            return array('status' => 'success', 'message'=> 'talla creada', 'data' => $data);
        }

        return array('status' => 'fail', 'message'=> 'no fue posible crear la talla', 'data' => $data);
    }

    function getTallas(){
        parent::conectar();
        $tallas = parent::consultaTodo("SELECT * FROM productos_tallas");
        parent::cerrar();

        if($tallas){
            $tallas_numericas = [];
            $tallas_letras_aux = [];
            foreach($tallas as $talla){
                if(is_numeric($talla['nombre_es'])){
                    array_push($tallas_numericas, $talla);
                }else{
                    array_push($tallas_letras_aux, $talla);
                }
            }

            for($i = 0; $i<count($tallas_numericas); $i++){
                for($j = 0; $j<count($tallas_numericas); $j++){
                    if(intval($tallas_numericas[$i]['nombre_es']) < intval($tallas_numericas[$j]['nombre_es'])){
                        $aux = $tallas_numericas[$j];
                        $tallas_numericas[$j] = $tallas_numericas[$i];
                        $tallas_numericas[$i] = $aux;
                    }
                }
            }

            $orden_tallas_letras = array("XS" => 0,"S" => 1, "M" => 2, "L" => 3, "XL" => 4, "XXL" => 5, "No aplica" => 6); // PARA ORGANIZAR LAS TALLAS 
            $tallas_letras = array_fill(0,count($tallas_letras_aux),0); 
            foreach($tallas_letras_aux as $talla){
                $tallas_letras[$orden_tallas_letras[$talla['nombre_es']]] = $talla;
            }

            return array("status" => "success","mensaje" => "ordenado","data"=>array_merge($tallas_letras,$tallas_numericas));
        }
        return array("status" => "fail", "mensaje" => "error al obtener las tallas");
    }

    function guardarProductoColoresTallas(Array $data){
        if(!isset($data) || !isset($data['id_producto']) || !isset($data['id_tallas']) || !isset($data['id_colores']) || !isset($data['cantidad'])) {
            return array('status' => 'fail', 'message'=> 'faltan datos guardar producto colores tallas', 'data' => $data);
        }   
        parent::conectar();
        $producto_colores_tallas = parent::queryRegistro("INSERT INTO detalle_producto_colores_tallas(id_producto,id_tallas,id_colores,cantidad,sku) values('$data[id_producto]','$data[id_tallas]','$data[id_colores]','$data[cantidad]','$data[sku]')"); // Insert
        parent::cerrar();
        // if($producto_colores_tallas){
        //     parent::conectar();
        //     $productoCantidad = parent::consultaTodo("SELECT * FROM productos WHERE id = '$data[id_producto]'; ");
        //     $nueva_cantidad = $productoCantidad[0]['cantidad'] + $data['cantidad'];
        //     $producto = parent::query("UPDATE productos SET cantidad = '$nueva_cantidad' WHERE id = '$data[id_producto]' ");
        //     parent::cerrar(); 
        // }
    }

    public function publicarVersion2( Array $data ){
        if(!isset($data) || !isset($data['uid']) || !isset($data['empresa'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);

        // Data E-mail:
        // Nombre del producto
        // codigo subasta
        // tipo de publicacion
        // categoria
        // fecha inicio
        // fecha finalizacion

        $request = null;
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT']."imagenes/publicaciones/";
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);
        $fecha = intval(microtime(true)*1000);
        $data = $this->mapeoVender($data);
        $producto = $data;
        // print_r($data);
        
        // if( intval( $producto['exposicion'] ) == 1 ){
        //     // Exposición GRATUITA: Solo se permiten 3 articulos con este tipo de exposición
        //     // Los demás deberán migrar a otro tipo de exposición.
        //     if ( !$this->getCountVentasGratuitas( $producto ) ) {
        //         return array(
        //             'status' => 'errorUsoMaximoExposicion',
        //             'message'=> 'Ya has cumplido el tope de articulos publicados con este tipo de exposición',
        //             'data' => null
        //         );
        //     }
        // }

        $envio = $data['direccion_envio'];
        $fotos = $data['fotos_producto'];
        usort($fotos, function($a, $b) {return strcmp($a['id'], $b['id']);});
        $textfullcompleto = $producto['producto'].' '.$producto['marca'].' '.$producto['modelo'].' '.$producto['titulo'].' '.$producto['categoria']['CategoryName'].' '.$producto['subcategoria']['CategoryName'];
        // print implode(',', extractKeyWords($textfullcompleto));
        $producto['keywords'] = implode(',', $this->extractKeyWords($textfullcompleto));
        $producto['categoria'] = $producto['categoria']['CategoryID'];
        $producto['subcategoria'] = $producto['subcategoria']['CategoryID'];
        // if($producto['exposicion'] == 3) $producto['cantidad'] = $producto['cantidad'] - $producto['cantidad_exposicion'];
        
        $empresa = 0;
        if($producto['empresa'] == true) $empresa = 1;


        $precio_usd = null;
        $array_monedas_local = null;
        if(isset($data['iso_code_2'])){
            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/js/fidusuarias.json'), true));
            if(count($array_monedas_locales) > 0){
                $array_monedas_local = $this->filter_by_value($array_monedas_locales, 'iso_code_2', $data['iso_code_2']);
                if(count($array_monedas_local) > 0) {
                    $array_monedas_local = $array_monedas_local[0];

                    if (floatval($data['porcentaje_oferta']) > 0) {
                        $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                        $precio_usd = $this->truncNumber(($precio_usd_temp / $array_monedas_local['costo_dolar']), 2);
                        // return "paso #1: " . $precio_usd . " --- oferta: " . floatval($data['porcentaje_oferta']);
                    }else{
                        $precio_usd = $this->truncNumber(($data['precio'] / $array_monedas_local['costo_dolar']), 2);
                        // return "paso #2: " . $precio_usd . " --- oferta: " . floatval($data['porcentaje_oferta']);
                    }

                    $monedas_local = $array_monedas_local['code'];
                }
            }
        }

        if(isset($data['iso_code_2']) && $data['iso_code_2'] == 'US'){
            $monedas_local = 'USD';
            if (floatval($data['porcentaje_oferta']) > 0) {
                $precio_usd_temp = floatval($data['precio']) - (floatval($data['precio']) * floatval($data['porcentaje_oferta']) / 100);
                $precio_usd = $precio_usd_temp;
                // return "paso #3: " . $precio_usd;
            }else{
                $precio_usd = $data['precio'];
                // return "paso #4: " . $precio_usd;
            }
        }

        if($precio_usd == null) return array('status' => 'fail', 'message'=> 'la moneda en la que desea publicar no es valida', 'data' => null);

        // return "paso #5: " . $precio_usd;
        if( $precio_usd < $this->montoxminimoxpublicar ) {
            return array(
                'status' => 'errorMontoMinimoPublicar',
                'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                
                'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                'costo_dolar' => $array_monedas_local['costo_dolar'],
                'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'], 2),
                'symbolMonedaLocal' => $array_monedas_local['code'],
                'data' => null
            );
        }
        if ( floatval($data['porcentaje_oferta']) < 0 || floatval($data['porcentaje_oferta']) > $this->porcentajexmaximoxpublicar ) {
            return array(
                'status' => 'errorPorcentajeMaximoPublicar',
                'message'=> 'El porcentaje maximo para que su publicación sea permitida es del: ' . $this->porcentajexmaximoxpublicar . '%',
                'porcentajeMaximoPublicar' => $this->porcentajexmaximoxpublicar,
                'data' => null
            );
        }else{
            $precio_usd_descuento = $precio_usd;// - ($precio_usd * floatval($data['porcentaje_oferta']) / 100);
            if ( $precio_usd_descuento < $this->montoxminimoxpublicar ) {
                return array(
                    'status' => 'errorMontoMinimoConDescuentoPublicar',
                    'message'=> 'El monto ingresado a inferior a '. $this->montoxminimoxpublicar .' USD, ingresaste ' . $data['precio'],
                    
                    'precioMonedaLocal' => $data['precio'], // Valor del articulo ingresado por el usuario en la moneda del usuario.
                    'precioUSD' => $precio_usd, // Valor del articulo en DOLARES
                    'costo_dolar' => $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocal' => $this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],
                    'montoMinimoMonedaLocalMask' => $this->maskNumber($this->montoxminimoxpublicar * $array_monedas_local['costo_dolar'],2),
                    'symbolMonedaLocal' => $array_monedas_local['code'],
                    'data' => null
                );
                
            }
        }


        $producto['precio_usd'] = $precio_usd;
        $producto['moneda_local'] = $monedas_local;

        // return $precio_usd;

        $cobrar = null;

        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1) {
            $producto['subasta']['precio_usd'] = $precio_usd;
            $nodo = new Nodo();
            $precios = array(
                'Nasbigold'=> $nodo->precioUSD(),
                'Nasbiblue'=> $nodo->precioUSDEBG()
            );
            $producto['subasta']['precio'] = $this->truncNumber(($producto['subasta']['precio_usd'] / $precios[$producto['subasta']['moneda']]), 6);
            unset($nodo);

            $validarsubasta = $this->validarTipoSubasta(
                $producto['subasta'],
                $data['iso_code_2']
            );

            if($validarsubasta['status'] == 'fail') return $validarsubasta;
            $producto['subasta']['datos_subasta'] = $validarsubasta['data'];

            // return $producto['subasta'];

            if ($producto['subasta']['datos_subasta']['id'] != 6){
                $tickets = $this->verTicketsUsuario([
                    'uid' => $producto['uid'],
                    'empresa' => $empresa,
                    'plan' => $producto['subasta']['datos_subasta']['id'],
                    'cantidad' => $producto['subasta']['cantidad']
                ]);
                if($tickets['status'] == 'fail') return $tickets;
                $tickets = $tickets['data'];
    
                $cobrar = $this->cobrarTicekts([
                    'tickets' => $tickets, 
                    'cantidad' => $producto['subasta']['cantidad'],
                    'fecha' => $fecha
                ]);
                if($cobrar['status'] != 'success') return $cobrar;
            }
        }

        $tipoSubasta = 0;
        if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
            $tipoSubasta = intval($producto['subasta']['tipo']);
        }

        // if(isset($data['variaciones'])){
        //     if($data['variaciones'] == 1){   // si el producto es por tallaje y color la cantida la dan las variaciones
        //         $producto['cantidad'] = 0;
        //     }
        // }

        $genero = 3;
        if(isset($data['genero'])){
            $genero = $data['genero'];
        }

            
            parent::conectar();
            $insertarproducto = "INSERT INTO productos_revision(
                uid,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas,
                id_productos_revision_estados
            )
            VALUES(
                '$producto[uid]',
                '$producto[tipo]',
                '$tipoSubasta',
                '$producto[producto]',
                '$producto[marca]',
                '$producto[modelo]',
                '$producto[titulo]',
                '$producto[descripcion]',
                '$producto[categoria]',
                '$producto[subcategoria]',
                '$producto[condicion_producto]',
                '$producto[garantia]',
                '1',
                '$fecha',
                '$fecha',
                '$fecha',
                '$producto[cantidad]',
                '$producto[moneda_local]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[oferta]',
                '$producto[porcentaje_oferta]',
                '$producto[porcentaje_tax]',
                '$producto[exposicion]',
                '$producto[cantidad_exposicion]',
                '$producto[envio]',
                '$envio[id]',
                '$envio[pais]',
                '$envio[departamento]',
                '$envio[ciudad]',
                '$envio[latitud]',
                '$envio[longitud]',
                '$envio[codigo_postal]',
                '$envio[direccion]',
                '$producto[keywords]',
                0,
                '$empresa',
                '$producto[url_video]',
                '$genero',
                '$data[tiene_colores_tallas]',
                0
            )";
            $productoqueryRevision = parent::queryRegistro($insertarproducto);


            parent::cerrar();
            
            $permisosRespuesta = $this->verificar_usuario_permisos_publicar(array("uid_usuario" => $data['uid'], "empresa" => $data['empresa']));
        if($permisosRespuesta){
            parent::conectar();
            $insertarproducto2 = "INSERT INTO productos(
                id,
                uid,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas
            )
            VALUES(
                '$productoqueryRevision',
                '$producto[uid]',
                '$producto[tipo]',
                '$tipoSubasta',
                '$producto[producto]',
                '$producto[marca]',
                '$producto[modelo]',
                '$producto[titulo]',
                '$producto[descripcion]',
                '$producto[categoria]',
                '$producto[subcategoria]',
                '$producto[condicion_producto]',
                '$producto[garantia]',
                '1',
                '$fecha',
                '$fecha',
                '$fecha',
                '$producto[cantidad]',
                '$producto[moneda_local]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[precio]',
                '$producto[precio_usd]',
                '$producto[oferta]',
                '$producto[porcentaje_oferta]',
                '$producto[porcentaje_tax]',
                '$producto[exposicion]',
                '$producto[cantidad_exposicion]',
                '$producto[envio]',
                '$envio[id]',
                '$envio[pais]',
                '$envio[departamento]',
                '$envio[ciudad]',
                '$envio[latitud]',
                '$envio[longitud]',
                '$envio[codigo_postal]',
                '$envio[direccion]',
                '$producto[keywords]',
                0,
                '$empresa',
                '$producto[url_video]',
                '$genero',
                '$data[tiene_colores_tallas]'
            )";
            parent::queryRegistro($insertarproducto2);
            
            // $deleteProductoRevision = "DELETE FROM productos_revision WHERE id = '$productoqueryRevision'";

            parent::query("DELETE FROM productos_revision WHERE id = '$productoqueryRevision'");

            parent::cerrar();
        }
        $productoquery = $productoqueryRevision;
        // $productoquery = $id_producto;
        if ($productoquery) {
            $fotos = $this->subirFotosProducto($fotos, $fecha, $productoquery);
            if(isset($data['url_video']) && !empty($data['url_video'])) {
                $imgconcat = $fecha.'_portada_video';

                $url = $this->uploadImagen([
                    'img' => $data['portada_video'],
                    'ruta' => '/imagenes/publicaciones/'.$productoquery.'/'.$imgconcat.'.png'
                ]);

                if($permisosRespuesta){
                    parent::conectar();
                    parent::query("UPDATE productos SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }else{
                    parent::conectar();
                    parent::query("UPDATE productos_revision SET portada_video = '$url' WHERE id = '$productoquery'");
                    parent::cerrar();
                }
                


            }
            if (isset($producto['subasta']) && $producto['subasta']['activo'] == 1){
                if(isset($cobrar)) $this->actualizarHistoricoTickets($cobrar['data'], $productoquery);
                $this->insertarSubasta($producto['subasta'], $fecha, $productoquery, $producto['precio_usd']);
            }

            $envio = $this->insertarEnvio($producto['detalle_envio'], $fecha, $productoquery);

            if ( intval( $empresa ) == 1 ) {
                // Evaluamos si es la primera publicación.
                $this->validarPrimerArticulo( $producto, $permisosRespuesta );
            }else{
                $this->enviodecorreodepublicacion_product( $producto );
               
            }

            if(isset($data['tiene_colores_tallas']) && $data['tiene_colores_tallas'] == true){
                
                $array_llenar_detalles = NULL;
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['tallas']) && isset($pair['color_id'])){
                        foreach($pair['tallas'] as $talla){
                            $array_llenar_detalles = array(
                                'id_producto' => $productoquery,
                                'id_tallas' => $talla['id_talla'],
                                'id_colores' => $pair['color_id'],
                                'cantidad' => $talla['cantidad']
                            );
                            if(isset($talla['sku'])){
                                $array_llenar_detalles['sku'] = $talla['sku'];
                            }else{
                                $array_llenar_detalles['sku'] = '';
                            }
                            $this->guardarProductoColoresTallas($array_llenar_detalles);
                        }
                    }
                }
            }
            if($permisosRespuesta){  
                $request = array(
                    'status' => 'success',
                    'message'=> 'producto creado',
                    'revision' => 0,
                    'data' => $productoquery
                );
            }else{
                $request = array(
                    'status' => 'success',
                    'message'=> 'tu producto ha sido enviado a revisión',
                    'revision' => 1,
                    'data' => $productoquery
                ); 
            }
            
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no se pudo crear el producto',
                'data' => null
            );
        }
        return $request;
    }

    function verificar_usuario_permisos_publicar(Array $data){
        parent::conectar();
        $tiene_permisos = parent::consultaTodo("SELECT * FROM productos_permisos_publicar where uid = '$data[uid_usuario]' AND empresa = '$data[empresa]'");
        parent::cerrar();
        if($tiene_permisos){
            if(count($tiene_permisos) > 0){
                // return array("status" => "success", "uid" => $tiene_permisos);
                return true;
            }else{
                return false;
                // return array("status" => "fail", "mensaje" => "no esta");
            }
        }else{
            return false;
            // return array("status" => "fail", "uid" => "no esta definido");
        }
    }

    function productos_espera_verificacion(Array $data){
        parent::conectar();
        $enEspera = parent::consultaTodo(
            "SELECT pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
                pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
                pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
                pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
                pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
                pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
                pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
                pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
                pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
                FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
                ON pr.id_productos_revision_estados = pre.id where pr.uid = '$data[id_usuario]' 
                AND pr.id_productos_revision_estados = 0 ORDER BY pr.id;"
        );
        parent::cerrar();

        if($enEspera){
            $numXpagina = 3;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($enEspera)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        $enEspera[$i]['precio_local_mask'] = $this->maskNumber($enEspera[$i]['precio'], 2);
                        $enEspera[$i]['precio_local_usd_mask'] = $this->maskNumber($enEspera[$i]['precio_usd'], 2);
                        $enEspera[$i]['estado'] = 3;
                        $enEspera[$i]['estado_descripcion'] = 'Revisión';
                        $enEspera[$i]['visitas'] = 0;
                        array_push($respuesta, $enEspera[$i]);
                    }
                }
            }
            $num_paginas = count($enEspera)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success", "mensaje"=> "productos en espera por validacion", "data" => $respuesta, "pagina" => $data['pag'], "total_paginas" => $num_paginas);
        }else{
            return array("status" => "fail", "mensaje" => "error no se encontraron datos");
        }
    }

    function envio_correo_producto_activado(Array $data, Array $data_subasta, Int $es_subasta){
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]); 
        $this->htmlEmailactivacion_producto($data,$data_user['data'],$data_subasta, $es_subasta);
    }

    function envio_correo_aceptado_product(Array $data_producto){
        $data_subasta=[];
        $es_subasta=0;  
        if($data_producto['tipoSubasta'] != "0"){
            $es_subasta=1; 

            $data_subasta=  $this->get_data_subasta_por_id_producto([
                'id' => $data_producto['id']
            ]);
            $data_subasta= $data_subasta[0]; 
        }

        if($data_producto['empresa'] == "1"){
            $this->validar_primero_revision($data_producto);
        } 
        $this->envio_correo_producto_activado($data_producto, $data_subasta, $es_subasta);
    }

    function validar_primero_revision(Array $data){
        $producto = [];
        $subasta = [];
        if ( intval( $data['empresa'] ) == 1 ) {
            
            parent::conectar();
            $misProductos = parent::consultaTodo(" SELECT * FROM buyinbig.productos WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' ORDER BY id DESC; ");
            parent::cerrar();

           if ( count($misProductos) == 1 ) {

                $producto = $misProductos[0];
                // validamos si es subasta o no.
                if ( $producto['tipoSubasta'] > 0) {
                    // SI es subasta o no.
                    parent::conectar();
                    $misSubastas = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$producto[id]'; ");
                    parent::cerrar();

                    if ( count($misSubastas) > 0 ) {
                        $subasta = $misSubastas[ 0 ];
                    }
                }
                $this->miPrimeraPublicacion([
                    'status'   => 'success',
                    'producto' => $producto,
                    'subasta'  => $subasta
                ]);
           }
        }
    }

    function miPrimeraPublicacionEmpresaRevision( Array $data ){
        $data_producto= $data["producto"];
        $data_subasta= $data["subasta"];
        $data_empresa=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        if ($data_subasta == null){
            $data_subasta=[]; 
        }
        $this->htmlEmailprimer_producto_empresa_revision( $data_producto, $data_subasta, $data_empresa["data"]); 
    }

    function prueba_de_guardado(Array $data){
        if(isset($data['variaciones'])){
            // $id_producto = 46;
            if($data['variaciones'] == 1){
                $array_llenar_detalles = NULL;
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['tallas']) && isset($pair['id_color'])){
                        foreach($pair['tallas'] as $talla){
                            $array_llenar_detalles = array(
                                // 'id_producto' => $id_producto,
                                'id_producto' => $data['id_producto'],
                                'id_tallas' => $talla['id_talla'],
                                'id_colores' => $pair['id_color'],
                                'cantidad' => $talla['cantidad']
                            );
                            if(isset($pair['sku'])){
                                $array_llenar_detalles['sku'] = $pair['sku'];
                            }
                            $this->guardarProductoColoresTallas($array_llenar_detalles);
                        }
                    }
                }
            }
        }
        return array('mensaje' => 'terminado');
    }
    // WBS PARA ACTUALIZAR DATOS DE COLORES Y TALLAS
    function prueba_de_actualizacion(Array $data){
        if(isset($data)){

            if(!isset($data['coloresXtallas']) || !isset($data['id_producto'])){
                return array("status" => "error", "mensaje" => "faltan datos");
            }

            parent::conectar();
            $producto = parent::consultaTodo("SELECT * FROM productos WHERE id = '$data[id_producto]'");
            
            
            parent::cerrar();
            

            $cantidad_post = 0;
            $cantidad_post_aux = 0;
            if($producto[0]['tiene_colores_tallas'] == 1){
                $cantidad_post = 0;
            }else{
                $cantidad_post_aux = $producto[0]['cantidad'];
                $cantidad_post = $producto[0]['cantidad'];
            }
            $realizar_op = false;
            
            if(isset($producto) && count($producto) > 0){
                foreach($data['coloresXtallas'] as $pair){
                    if(isset($pair['eliminar_color']) && ($pair['eliminar_color'] == true || $pair['eliminar_color'] == 1)){ // PARA ELIMINAR UN COLOR Y LAS TALLAS ASOCIADAS A EL
                        foreach($pair['tallas'] as $talla){
                            if(isset($talla['id_pair'])){
                                parent::conectar();
                                parent::query("DELETE FROM detalle_producto_colores_tallas WHERE id = '$talla[id_pair]' ");
                                parent::cerrar();
                            }
                        }
                    }else{
                        foreach($pair['tallas'] as $talla){
                            if(isset($talla['id_pair'])){
                                if(isset($talla['eliminar_pair']) && ($talla['eliminar_pair'] == true || $talla['eliminar_pair'] == 1)){ // PARA ELIMINAR UNA CONFIGURACION COLOR - TALLA
                                    // $cantidad_post = $cantidad_post - $talla['cantidad'];
                                    parent::conectar();
                                    parent::query("DELETE FROM detalle_producto_colores_tallas WHERE id = '$talla[id_pair]' ");
                                    parent::cerrar();
                                }else{  // SI NO VIENE EL dato eliminar_pair ENTONCES SE VA A ACTUALIZAR
                                    $cantidad_post = $cantidad_post + $talla['cantidad'];
                                    parent::conectar();
                                    parent::query("UPDATE detalle_producto_colores_tallas SET id_tallas = '$talla[id_talla]', id_colores = '$pair[id_color]', cantidad = '$talla[cantidad]', sku = '$talla[sku]' WHERE id = '$talla[id_pair]'");
                                    parent::cerrar();
                                }
                                
                            }else{ // SI NO VIENE EL id_pair
                                $realizar_op = true;
                                parent::conectar();
                                $consulta = parent::consultaTodo("SELECT * FROM detalle_producto_colores_tallas WHERE id_producto = '$data[id_producto]' AND id_tallas = '$talla[id_talla]' AND id_colores = '$pair[id_color]'");
                                parent::cerrar();
                                if(!$consulta){ // SI EL REGISTRO NO ESTA SE INSERTA
                                    $cantidad_post = $cantidad_post + $talla['cantidad'];
                                    parent::conectar();
                                    $resultado = parent::queryRegistro("INSERT INTO detalle_producto_colores_tallas(id_producto,id_tallas,id_colores,cantidad,sku) VALUES('$data[id_producto]','$talla[id_talla]','$pair[id_color]','$talla[cantidad]','$talla[sku]');");
                                    parent::cerrar();  
                                }else{ // SI EL PRODUCTO ESTA SE ACTUALIZA
                                    $cantidad_pair = $consulta[0]['cantidad'] + $talla['cantidad'];
                                    $cantidad_post = $cantidad_post - $consulta[0]['cantidad'] + $cantidad_pair;

                                    $pair_aux = $consulta[0]['id'];
                                    parent::conectar();
                                    parent::query("UPDATE detalle_producto_colores_tallas SET cantidad = '$cantidad_pair', sku = '$talla[sku]' WHERE id = '$pair_aux'");
                                    parent::cerrar();
                                }
                            }
                        }
                    }
                }
                $nueva_cantidad = 0;
                if($producto[0]['tiene_colores_tallas'] == 1){
                    $nueva_cantidad = $cantidad_post - $cantidad_post_aux;
                }else{
                    if($realizar_op){
                        $nueva_cantidad = $cantidad_post - $cantidad_post_aux;
                    }else{
                        $nueva_cantidad = $cantidad_post;
                    }
                }
                parent::conectar(); // SE ACTUALIZA LA CANTIDAD DISPONIBLE EN LA TABLA PRODUCTOS
                parent::query("UPDATE productos SET tiene_colores_tallas = 1, cantidad ='$nueva_cantidad' WHERE id = '$data[id_producto]'");
                parent::cerrar();


                $dataActualizada = $this->obtener_productos_tallas_editar(array("id_producto" => $data['id_producto']));
                
                if($dataActualizada['status'] == "success"){
                    $this->envio_de_correo_general_ediccion($producto[0]); 
                    return array("status" => "success", "mensaje" => "data actualizada", "data"=>$dataActualizada['data']);
                }else if( $dataActualizada['status'] == 'vacio'){

                    parent::conectar();
                    parent::query("UPDATE productos SET tiene_colores_tallas = 0 WHERE id = '$data[id_producto]'");
                    parent::cerrar();

                    return array("status" => "success", "mensaje" => "todas las tallas y colores eliminadas");
                }
                
                // return array("status" => "success", "mensaje" => "data actualizada", "data"=>$data);
            }else{
                return array("status" => "error", "mensaje" => "error al obtener producto");
            }
            // return array("status" => "success", "mensaje" => "data actualizada", "data"=>$data);
        }else{
            return array("status" => "fail", "mensaje" => "data error");
        }
    }

    // DEPRECADA
    function obtener_pares_producto_colores_tallas(Array $data){

        if(!isset($data['id_producto'])){
            return array("status" => "error", "mensaje" => "faltan datos");
        }

        parent::conectar();
        // $result = parent::consultaTodo("SELECT id as id_pair, id_producto, id_tallas, id_colores, cantidad, sku FROM detalle_producto_colores_tallas WHERE id_producto = '$data[id_producto]';");
        $result = parent::consultaTodo("SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
        pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
        pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
        FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
        JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE id_producto = '$data[id_producto]';");
        parent::cerrar();
        if($result){
            return array("status" => "success", "mensaje" =>"id de la pareja color - talla", "data" => $result);
        }
        return array("status" => "fail", "mensaje" => "no se encontraron registros");
        
    }

    function obtener_productos_tallas_editar(Array $data){ // REGRESA INFORMACION DE COLORES Y TALLAS DE UN PRODUCTO EN UNA ESTRUCTURA APROPIADA PARA EDITAR
        parent::conectar();
        $result = parent::consultaTodo("SELECT dpct.id_producto,dpct.id AS id_pair,pc.id AS id_color, pc.nombre_es AS color_nombre_es, 
        pc.nombre_en AS color_nombre_en,pc.hexadecimal AS hexadecimal, pt.id AS id_tallas, pt.nombre_es AS talla_nombre_es,
        pt.nombre_en AS talla_nombre_en, dpct.cantidad AS cantidad, dpct.sku AS sku 
        FROM productos_colores AS pc JOIN detalle_producto_colores_tallas AS dpct ON pc.id = dpct.id_colores 
        JOIN productos_tallas AS pt ON pt.id = dpct.id_tallas WHERE id_producto = '$data[id_producto]';");
        parent::cerrar();

        if($result && count($result) > 0){
            $response = [];
            foreach($result as $pair){
                if(count($response) > 0){
                    $sw = 0;
                        for($i = 0; $i<count($response);$i++){
                        if($pair['id_color'] === $response[$i]['id_color']){
                            $sw = 1;
                            array_push(
                                $response[$i]['tallas'],array(
                                    "id_talla" => $pair['id_tallas'], 
                                    "cantidad" => $pair['cantidad'], 
                                    "id_pair" => $pair['id_pair'],
                                    "nombre_es" => $pair['talla_nombre_es'],
                                    "nombre_en" => $pair['talla_nombre_en'],
                                    "sku" => $pair['sku']
                                ));
                        }
                    }
                    if($sw == 0){
                        array_push($response,array(
                            "id_color" => $pair['id_color'],
                            "nombre_es" => $pair['color_nombre_es'],
                            "nombre_en" => $pair['color_nombre_en'],
                            "hexadecimal" => $pair['hexadecimal'],
                            "tallas" => [
                                array(
                                    "id_talla" => $pair['id_tallas'], 
                                    "cantidad" => $pair['cantidad'], 
                                    "id_pair" => $pair['id_pair'],
                                    "nombre_es" => $pair['talla_nombre_es'],
                                    "nombre_en" => $pair['talla_nombre_en'],
                                    "sku" => $pair['sku'] 
                                    )
                            ],
                        ));
                    }
                }else{
                    array_push($response,array(
                        "id_color" => $pair['id_color'],
                        "nombre_es" => $pair['color_nombre_es'],
                        "nombre_en" => $pair['color_nombre_en'],
                        "hexadecimal" => $pair['hexadecimal'],
                        "tallas" => [
                            array(
                                "id_talla" => $pair['id_tallas'], 
                                "cantidad" => $pair['cantidad'], 
                                "id_pair" => $pair['id_pair'],
                                "nombre_es" => $pair['talla_nombre_es'],
                                "nombre_en" => $pair['talla_nombre_en'],
                                "sku" => $pair['sku'] 
                                )
                        ],
                    ));
                }
            }
            return array("status" => "success", "data" => $response);
        }else if(!$result){
            return array("status" => "vacio", "mensaje" => "no hay datos","data" => []);
        }
    }
    
    function agregar_nuevo_editar(Array $data){
        if(!isset($data)){
            return array("status" => "error", "mensaje" => "faltan datos");
        }
        $array_llenar_detalles = NULL;
        foreach($data['coloresXtallas'] as $pair){
            if(isset($pair['tallas']) && isset($pair['color_id'])){
                foreach($pair['tallas'] as $talla){
                    $array_llenar_detalles = array(
                        'id_producto' => $data['id_producto'],
                        'id_tallas' => $talla['id_talla'],
                        'id_colores' => $pair['color_id'],
                        'cantidad' => $talla['cantidad']
                    );
                    if(isset($talla['sku'])){
                        $array_llenar_detalles['sku'] = $talla['sku'];
                    }else{
                        $array_llenar_detalles['sku'] = '';
                    }
                    $this->guardarProductoColoresTallas($array_llenar_detalles);
                }
            }
        }
        return array("status" => "success", "mensaje" => "creada nueva configuracion", "data" => $data);
    }
    // NO UTILIZADA POR EL MOMENTO
    function agregar_colores_tallas_primera_vez_editar($data){
        $cantidad = 0;
        $array_llenar_detalles = NULL;
        foreach($data['coloresXtallas'] as $pair){
            if(isset($pair['tallas']) && isset($pair['color_id'])){
                foreach($pair['tallas'] as $talla){
                    $cantidad = $cantidad + $talla['cantidad'];
                    $array_llenar_detalles = array(
                        'id_producto' => $data['id'],
                        'id_tallas' => $talla['id_talla'],
                        'id_colores' => $pair['color_id'],
                        'cantidad' => $talla['cantidad']
                    );
                    if(isset($talla['sku'])){
                        $array_llenar_detalles['sku'] = $talla['sku'];
                    }else{
                        $array_llenar_detalles['sku'] = '';
                    }
                    $this->guardarProductoColoresTallas($array_llenar_detalles);
                }
            }
        }
        parent::conectar();
        $result = parent::query("UPDATE productos SET tiene_colores_tallas = 1, cantidad ='$cantidad' WHERE id = '$data[id]'");
        parent::cerrar();

        if($result){
            return array("status" => "success", "mensaje" => "se actualizo el producto");
        }
        return array("status" => "fail", "mensaje" => "error al agregar colores y tallas");
    }


    function prueba_de_notificacion($data){

        // parent::conectar();
        // parent::query("UPDATE direcciones SET dane = 47001000, ciudad = 'Santa Marta' WHERE id = 30	AND ciudad = 'Samta Marta';");
        // parent::query("UPDATE direcciones SET dane = 05001000 WHERE ciudad LIKE '%medellin%';");
        // parent::query("UPDATE direcciones SET dane = 47189000 WHERE ciudad LIKE '%cienaga%';");
        // parent::query("UPDATE direcciones SET dane = 13430000 WHERE ciudad LIKE '%Magangue%';");
        // parent::query("UPDATE direcciones SET dane = 47001000 WHERE ciudad LIKE '%Santa marta%';");
        // parent::query("UPDATE direcciones SET dane = 73001000 WHERE ciudad LIKE '%Ibague%';");
        // parent::query("UPDATE direcciones SET dane = 68276000 WHERE ciudad LIKE '%Floridablanca%';");
        // parent::query("UPDATE direcciones SET dane = 05631000 WHERE ciudad LIKE '%Sabaneta%';");
        // parent::query("UPDATE direcciones SET dane = 25175000 WHERE ciudad LIKE '%Chia%';");
        // parent::query("UPDATE direcciones SET dane = 73504000 WHERE ciudad LIKE '%Ortega%';");
        // parent::query("UPDATE direcciones SET dane = 76001000 WHERE ciudad LIKE '%Cali%';");
        // parent::cerrar();
        return array("mensaje" => "terminado");
    }

    // FIN COLORES TALLAS, REVISION

    // BACKEND 

    // ESTA FUNCION PERTENECE EL BACKEND
    function obtener_publicaciones_espera_revison_bakcend($data){

        $titulo = "";
        if(isset($data['titulo']) && $data['titulo'] != "" && $data['titulo'] != NULL){
            $titulo = " AND pr.titulo LIKE '%$data[titulo]%' ";
        }
        $emailP2W = "";
        $emailEMPRESA = "";
        if(isset($data['email']) && $data['email'] != "" && $data['email'] != NULL){
            $emailP2W = " AND u.email = '$data[email]' ";
            $emailEMPRESA = " AND u.correo = '$data[email]' ";
        }

        $fecha_actual = intval(microtime(true));

        $date = date("Y-m-d H:i:s",$fecha_actual);

        parent::conectar();
        
        $enEspera = parent::consultaTodo("SELECT * FROM (SELECT u.nombreCompleto as nombre_usuario, u.email as email, u.telefono as telefono ,pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
        pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
        pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
        pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
        pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
        pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
        pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
        pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
        pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
        FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
        ON pr.id_productos_revision_estados = pre.id 
        JOIN peer2win.usuarios as u
        ON pr.uid = u.id AND pr.empresa = 0
        WHERE pr.id_productos_revision_estados = 0 $titulo $emailP2W

        UNION SELECT u.razon_social as nombre_usuario, u.correo as email, u.telefono as telefono, pr.id, pr.uid, pr.empresa, pr.tipo, pr.tipoSubasta,pr.producto,pr.marca,
        pr.modelo, pr.titulo,pr.descripcion, pr.categoria, pr.subcategoria, pr.condicion_producto,pr.garantia, 
        pr.estado,pr.cantidad,pr.moneda_local,pr.precio,pr.precio_usd,pr.precio_publicacion,pr.precio_usd_publicacion,
        pr.oferta,pr.porcentaje_oferta,pr.porcentaje_tax,pr.exposicion,pr.cantidad_exposicion,pr.envio,pr.id_direccion,
        pr.pais,pr.departamento,pr.ciudad,pr.latitud,pr.longitud,pr.codigo_postal,	
        pr.direccion, pr.keywords,pr.foto_portada,pr.url_video,pr.portada_video,pr.cantidad_vendidas,
        pr.fecha_creacion,pr.fecha_actualizacion,pr.ultima_venta,	
        pr.genero,pr.tiene_colores_tallas,pr.id_productos_revision_estados,
        pre.nombre_es AS revision_estado_es, pre.nombre_en AS revision_estado_en  
        FROM productos_revision AS pr JOIN productos_revision_estados AS pre 
        ON pr.id_productos_revision_estados = pre.id
        JOIN buyinbig.empresas as u
        ON pr.uid = u.id AND pr.empresa = 1 
        WHERE pr.id_productos_revision_estados = 0 $titulo $emailEMPRESA) as un ORDER BY un.id DESC;
        ");
        parent::cerrar();
        if($enEspera){
            $numXpagina = 9;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($enEspera)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        $publicacion_fecha = date("Y-m-d H:i:s",floatval($enEspera[$i]['fecha_actualizacion'])/1000);
                        $fecha_update = strtotime($publicacion_fecha);
                        $fecha_hoy = strtotime($date);
                        $diferencia_dias = abs($fecha_hoy - $fecha_update)/(60*60*24);
                        $enEspera[$i]['dias_espera'] = $diferencia_dias;
                        array_push($respuesta, $enEspera[$i]);
                    }
                }
            }
            $num_paginas = count($enEspera)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success","data"=>$respuesta, "pagina" => $data['pag'], "total_paginas" => $num_paginas);
        }   
        return array("status" => "fail", "mensaje"=>"error al obtener publicaciones");
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function obtener_datos_producto($data){
        $producto = NULL;
        parent::conectar();
        $productoPublicado = parent::consultaTodo("SELECT 
            p.id as producto_id, p.uid	as id_usuario, p.empresa as empresa,
            p.tipo as producto_tipo, p.tipoSubasta as producto_tipoSubasta, p.producto as producto, p.marca as producto_marca,
            p.modelo as producto_modelo, p.titulo as producto_titulo, p.descripcion as producto_descripcion,
            p.categoria as producto_categoria, p.subcategoria as producto_subcategoria, p.condicion_producto as producto_condicion,
            p.garantia as producto_garantia, p.estado as producto_estado, p.cantidad as producto_cantidad, p.moneda_local as producto_moneda_local,
            p.precio as producto_precio, p.precio_usd as producto_precio_usd, p.precio_publicacion as producto_precio_publicacion,
            p.precio_usd_publicacion as producto_precio_usd_publicacion, p.oferta as producto_oferta, 
            p.porcentaje_oferta	as producto_porcentaje_oferta, p.porcentaje_tax as producto_porcentaje_tax,
            p.exposicion as producto_exposicion, p.cantidad_exposicion as producto_cantidad_exposicion, 
            p.envio as producto_envio, p.id_direccion as producto_id_direccion, p.pais as producto_pais, 
            p.departamento as producto_departamento, p.ciudad as producto_ciudad, p.latitud as producto_latitud, 
            p.longitud as producto_longitud, p.codigo_postal as producto_codigo_postal, p.direccion as producto_direccion,
            p.keywords as producto_keywords, p.foto_portada as producto_foto_portada, p.url_video as producto_url_video, 
            p.portada_video as producto_portada_video, p.cantidad_vendidas as producto_cantidades_vendidas,
            p.fecha_creacion as producto_fecha_creacion, p.fecha_actualizacion as producto_fecha_actualizacion, 
            p.ultima_venta as producto_ultima_venta, p.genero as producto_genero, 
            p.tiene_colores_tallas as producto_tiene_colores_tallas
            FROM productos as p WHERE p.id = '$data[id]'");
        parent::cerrar();

        if(!$productoPublicado){
            parent::conectar();
        $productoRevision = parent::consultaTodo("SELECT 
            p.id as producto_id, p.uid	as id_usuario, p.empresa as empresa,
            p.tipo as producto_tipo, p.tipoSubasta as producto_tipoSubasta, p.producto as producto, p.marca as producto_marca,
            p.modelo as producto_modelo, p.titulo as producto_titulo, p.descripcion as producto_descripcion,
            p.categoria as producto_categoria, p.subcategoria as producto_subcategoria, p.condicion_producto as producto_condicion,
            p.garantia as producto_garantia, p.estado as producto_estado, p.cantidad as producto_cantidad, p.moneda_local as producto_moneda_local,
            p.precio as producto_precio, p.precio_usd as producto_precio_usd, p.precio_publicacion as producto_precio_publicacion,
            p.precio_usd_publicacion as producto_precio_usd_publicacion, p.oferta as producto_oferta, 
            p.porcentaje_oferta	as producto_porcentaje_oferta, p.porcentaje_tax as producto_porcentaje_tax,
            p.exposicion as producto_exposicion, p.cantidad_exposicion as producto_cantidad_exposicion, 
            p.envio as producto_envio, p.id_direccion as producto_id_direccion, p.pais as producto_pais, 
            p.departamento as producto_departamento, p.ciudad as producto_ciudad, p.latitud as producto_latitud, 
            p.longitud as producto_longitud, p.codigo_postal as producto_codigo_postal, p.direccion as producto_direccion,
            p.keywords as producto_keywords, p.foto_portada as producto_foto_portada, p.url_video as producto_url_video, 
            p.portada_video as producto_portada_video, p.cantidad_vendidas as producto_cantidades_vendidas,
            p.fecha_creacion as producto_fecha_creacion, p.fecha_actualizacion as producto_fecha_actualizacion, 
            p.ultima_venta as producto_ultima_venta, p.genero as producto_genero, 
            p.tiene_colores_tallas as producto_tiene_colores_tallas, p.id_productos_revision_estados as producto_id_revision_estados
            FROM productos_revision as p WHERE p.id = '$data[id]'");
        parent::cerrar();
            if(count($productoRevision) <= 0){
                return array("status" => "fail", "mensaje" => "producto no encontrado");
            }else{
                $producto = $productoRevision;
            } 
        }else{
            $producto = $productoPublicado;
        }

        if($producto){
            $producto = $producto[0];
            parent::conectar();
            $id_productos_revision_estados = parent::consultaTodo("SELECT id_productos_revision_estados FROM productos_revision WHERE id = '$producto[producto_id]' ");
            parent::cerrar();
            if($id_productos_revision_estados){
                $producto['id_productos_revision_estados'] = $id_productos_revision_estados[0]['id_productos_revision_estados'];
                parent::conectar();
                $producto_revision = parent::consultaTodo("SELECT * FROM productos_revision_estados where id = '$producto[id_productos_revision_estados]'");
                parent::cerrar();
                if($producto_revision){
                    $producto['productos_revision_estado'] = $producto_revision[0];
                }else{
                    $producto['productos_revision_estado'] = NULL;
                }
            }else{
                $producto['id_productos_revision_estados'] = NULL;
            }

            if($producto['producto_tiene_colores_tallas'] != 0){
               $respuesta_colores_tallas = $this->obtener_productos_tallas_editar(array('id_producto'=>$producto['producto_id']))["data"];
                $producto['colores_tallas'] = $respuesta_colores_tallas;
            }else{
                $producto['colores_tallas'] = NULL;
            }

            //fechas

            $producto['producto_fecha_creacion'] = floatval($producto['producto_fecha_creacion']);
            $producto['producto_fecha_actualizacion'] = floatval($producto['producto_fecha_actualizacion']);
            $producto['producto_ultima_venta'] = floatval($producto['producto_ultima_venta']);

            $fecha_actual = intval(microtime(true));

            $date = date("Y-m-d H:i:s",$fecha_actual);

            $publicacion_fecha = date("Y-m-d H:i:s",floatval($producto['producto_fecha_actualizacion'])/1000);
            $fecha_update = strtotime($publicacion_fecha);
            $fecha_hoy = strtotime($date);
            $diferencia_dias = abs($fecha_hoy - $fecha_update)/(60*60*24);
            $producto['dias_espera'] = $diferencia_dias;

            //fin fechas

            parent::conectar();
            $producto_fotos = parent::consultaTodo("SELECT * FROM productos_fotos WHERE id_publicacion = '$producto[producto_id]'");
            parent::cerrar();

            if($producto_fotos){
                $producto['fotos'] = $producto_fotos;
                //fechas fotos
                    for($i = 0; $i<count($producto['fotos']); $i++){
                        $producto['fotos'][$i]['fecha_creacion'] = floatval( $producto['fotos'][$i]['fecha_creacion']);
                        $producto['fotos'][$i]['fecha_actualizacion'] = floatval( $producto['fotos'][$i]['fecha_actualizacion']);
                    }
                //fin fechas fotos
            }else{
                $producto['fotos'] = NULL;
            }

            
            parent::conectar();
            $data_usuario = NULL;
            // $producto['usuario'] = $data_ususario;
            if($producto['empresa'] == 1){
                $data_usuario = parent::consultaTodo("SELECT * FROM empresas WHERE id = '$producto[id_usuario]'");
                $data_usuario = array(
                    "nombre_usuario" => $data_usuario[0]['razon_social'],
                    "email" => $data_usuario[0]['correo'],
                    "telefono" => $data_usuario[0]['telefono']
                ); 
                $producto['usuario'] = $data_usuario;
            }else if($producto['empresa'] == 0){
                $data_usuario = parent::consultaTodo("SELECT * FROM peer2win.usuarios WHERE id = '$producto[id_usuario]'");
                $data_usuario = array(
                    "nombre_usuario" => $data_usuario[0]['nombreCompleto'],
                    "email" => $data_usuario[0]['email'],
                    "telefono" => $data_usuario[0]['telefono']
                ); 
                $producto['usuario'] = $data_usuario;
            }
            parent::cerrar();

            
            if($producto['producto_tipoSubasta'] != 0){

                parent::conectar();
                $subastas = parent::consultaTodo("SELECT ps.id as subasta_id,ps.moneda as subasta_moneda, 
                        ps.precio as subasta_precio, ps.precio_usd as subasta_precio_usd,
                        ps.cantidad as subasta_cantidad, ps.tipo as subasta_tipo,
                        ps.estado as subasta_estado, ps.apostadores as subastas_apostadores,
                        ps.inscritos as subastas_inscritos,
                        ps.fecha_fin as subastas_fecha_fin,
                        ps.fecha_creacion as subastas_fecha_creacion,
                        ps.fecha_actualizacion as subastas_fecha_actualizacion,
                        ps.fecha_inicio as subastas_fecha_inicio
                        FROM productos_subastas as ps WHERE ps.id_producto = '$producto[producto_id]'");
                parent::cerrar();

                $producto['subastas'] = $subastas[0];

                //fecha subasta
                $producto['subastas']['subastas_fecha_fin'] = floatval($producto['subastas']['subastas_fecha_fin']);
                $producto['subastas']['subastas_fecha_creacion'] = floatval($producto['subastas']['subastas_fecha_creacion']);
                $producto['subastas']['subastas_fecha_actualizacion'] = floatval($producto['subastas']['subastas_fecha_actualizacion']);
                $producto['subastas']['subastas_fecha_inicio'] = floatval($producto['subastas']['subastas_fecha_inicio']);
                // fin fecha subasta

                $subasta_tipo = $producto['subastas']['subasta_tipo'];
                parent::conectar();
                $subasta_tipo_data = parent::consultaTodo("SELECT * FROM productos_subastas_tipo WHERE id = '$subasta_tipo';");
                parent::cerrar();

                $subasta_estado = $producto['subastas']['subasta_estado'];
                parent::conectar();
                $subasta_estado_data = parent::consultaTodo("SELECT * FROM productos_subastas_estado WHERE id = '$subasta_estado';");
                parent::cerrar();

                
                $subasta_id = $producto['subastas']['subasta_id'];
                parent::conectar();
                $subasta_inscritos = parent::consultaTodo("SELECT * FROM productos_subastas_inscritos WHERE id_subasta = '$subasta_id'");
                parent::cerrar();

                parent::conectar();
                $subasta_pujas = parent::consultaTodo("SELECT * FROM productos_subastas_pujas WHERE id_subasta = '$subasta_id'");
                parent::cerrar();

                if($subasta_tipo_data){
                    $producto['subastas']['subasta_tipo'] = $subasta_tipo_data[0]; 
                }else{
                    $producto['subastas']['subasta_tipo'] = NULL;
                }
                if($subasta_estado_data){
                    $producto['subastas']['subasta_estado'] = $subasta_estado_data[0];
                }else{
                    $producto['subastas']['subasta_estado'] = NULL;
                }

                $producto['subastas']['subastas_inscritos_detalle'] = [];
                if($subasta_inscritos){
                    foreach($subasta_inscritos as $inscrito){
                        $inscrito['fecha_creacion'] = floatval($inscrito['fecha_creacion']);
                        $inscrito['fecha_actualizacion'] = floatval($inscrito['fecha_actualizacion']);
                        array_push($producto['subastas']['subastas_inscritos_detalle'],$inscrito);
                    }
                }else{
                    $producto['subastas']['subastas_inscritos_detalle'] = NULL;
                }

                $producto['subastas']['subasta_pujas'] = [];
                if($subasta_pujas){
                    foreach($subasta_pujas as $puja){
                        $puja['fecha_creacion'] = floatval($puja['fecha_creacion']);
                        $puja['fecha_actualizacion'] = floatval($puja['fecha_actualizacion']);
                        $puja['fecha_final'] = floatval($puja['fecha_final']);
                        array_push($producto['subastas']['subasta_pujas'],$puja);
                    }
                }else{
                    $producto['subastas']['subasta_pujas'] = NULL;
                }
            }else{
                $producto['subastas'] = NULL;
            }
        }
        return array("status" => "success", "data" => $producto);
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function rechazar_producto(Array $data){
        parent::conectar();
        $result = parent::query("UPDATE productos_revision SET id_productos_revision_estados = 2, motivo = '$data[motivo]'  WHERE id = '$data[id_producto]'");
        $producto = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$data[id_producto]'");
        parent::cerrar();
        if($result){
            if($producto){
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid' => $producto[0]['uid'],
                    'empresa' => $producto[0]['empresa'],
                    'text' => 'Tu producto '.$producto[0]['titulo'].' ha sido rechazado',
                    'es' => 'Tu producto '.$producto[0]['titulo'].' ha sido rechazado',
                    'en' => 'Your product '.$producto[0]['titulo'].' has been rejected',
                    'keyjson' => '',
                    'url' => ''
                ]);
            }
            $this->envio_correo_rechazo_($producto[0]); 
            return array("status" => "success", "mensaje" => "producto rechazado");
        }else{
            return array("status" => "fail", "mensaje error al rechazar producto");
        }
    }

    function cronRevision(){

        $fecha_actual = intval(microtime(true));

        $date = date("Y-m-d H:i:s",$fecha_actual);
        
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_revision WHERE id_productos_revision_estados = 0");
        parent::cerrar();
        if($result){
            foreach($result as $publicacion){
                $publicacion_fecha = date("Y-m-d H:i:s",floatval($publicacion['fecha_actualizacion'])/1000);
                $fecha_update = strtotime($publicacion_fecha);
                $fecha_hoy = strtotime($date);
                $diferencia_horas = abs($fecha_hoy - $fecha_update)/(60*60);
                $publicacion['diferencia'] = $diferencia_horas;
                // $publicacion['fecha_act_normla'] = $date;
                // $publicacion['fecha_publ_normla'] = $publicacion_fecha;
                // $publicacion['strFU'] = $fecha_update;
                // $publicacion['strFH'] = $fecha_hoy;  
                if($diferencia_horas >= 120){
                    $this->aceptar_producto(array("id_producto" => intval($publicacion['id'])));
                }
            }
            return array("status" => "success");
        }else{
            return array("status" => "fail", "mensaje" => "no se encontraron productos");
        }
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function aceptar_producto(Array $data){
        parent::conectar();
        $respuesta = parent::consultaTodo("SELECT * FROM productos_revision WHERE id = '$data[id_producto]'");
        $respuesta = $respuesta[0];
        $queriRegistro = "INSERT INTO productos(
                id,
                uid,
                tipo,
                tipoSubasta,
                producto,
                marca,
                modelo,
                titulo,
                descripcion,
                categoria,
                subcategoria,
                condicion_producto,
                garantia,
                estado,
                fecha_creacion,
                fecha_actualizacion,
                ultima_venta,
                cantidad,
                moneda_local,
                precio,
                precio_usd,
                precio_publicacion,
                precio_usd_publicacion,
                oferta,
                porcentaje_oferta,
                porcentaje_tax,
                exposicion,
                cantidad_exposicion,
                envio,
                id_direccion,
                pais,
                departamento,
                ciudad,
                latitud,
                longitud,
                codigo_postal,
                direccion,
                keywords,
                foto_portada,
                portada_video,
                cantidad_vendidas,
                empresa,
                url_video,
                genero,
                tiene_colores_tallas
                )
                VALUES(
                '$respuesta[id]',
                '$respuesta[uid]',
                '$respuesta[tipo]',
                '$respuesta[tipoSubasta]',
                '$respuesta[producto]',
                '$respuesta[marca]',
                '$respuesta[modelo]',
                '$respuesta[titulo]',
                '$respuesta[descripcion]',
                '$respuesta[categoria]',
                '$respuesta[subcategoria]',
                '$respuesta[condicion_producto]',
                '$respuesta[garantia]',
                '$respuesta[estado]',
                '$respuesta[fecha_creacion]',
                '$respuesta[fecha_actualizacion]',
                '$respuesta[ultima_venta]',
                '$respuesta[cantidad]',
                '$respuesta[moneda_local]',
                '$respuesta[precio]',
                '$respuesta[precio_usd]',
                '$respuesta[precio]',
                '$respuesta[precio_usd]',
                '$respuesta[oferta]',
                '$respuesta[porcentaje_oferta]',
                '$respuesta[porcentaje_tax]',
                '$respuesta[exposicion]',
                '$respuesta[cantidad_exposicion]',
                '$respuesta[envio]',
                '$respuesta[id_direccion]',
                '$respuesta[pais]',
                '$respuesta[departamento]',
                '$respuesta[ciudad]',
                '$respuesta[latitud]',
                '$respuesta[longitud]',
                '$respuesta[codigo_postal]',
                '$respuesta[direccion]',
                '$respuesta[keywords]',
                '$respuesta[foto_portada]',
                '$respuesta[portada_video]',
                '$respuesta[cantidad_vendidas]',
                '$respuesta[empresa]',
                '$respuesta[url_video]',
                '$respuesta[genero]',
                '$respuesta[tiene_colores_tallas]'
                )";
                $productoMovido = parent::queryRegistro($queriRegistro);
        parent::cerrar();
        if($productoMovido){
            parent::conectar();
            $result = parent::query("UPDATE productos_revision SET id_productos_revision_estados = 1 WHERE id = '$data[id_producto]'");
            parent::cerrar();
            if($result){
                $notificacion = new Notificaciones();
                $notificacion->insertarNotificacion([
                    'uid' => $respuesta['uid'],
                    'empresa' => $respuesta['empresa'],
                    'text' => 'Tu producto '.$respuesta['titulo'].' ha sido aceptado',
                    'es' => 'Tu producto '.$respuesta['titulo'].' ha sido aceptado',
                    'en' => 'Your product '.$respuesta['titulo'].' has been accepted',
                    'keyjson' => '',
                    'url' => ''
                ]);
                $this->envio_correo_aceptado_product($respuesta);
                return array("status" => "success", "mensaje"=>"producto aceptado correctamente");
            }else{
                return array("status" => "fail", "mensaje"=>"producto movido pero no actualizado");
            }
        }else{
            return array("status" => "fail", "mensaje" => "error al aceptar producto");
        }    
        // return array("status" => "fail", "mensaje" => "esta en desarrollo");
    }


    // ESTA FUNCION PERTENECE AL BACKEND REGRESA TODAS LAS SUBASTAS 
    function obtener_subastas_backend(Array $data){

        $estado = " ps.estado != 0 ";
        if(isset($data['estado']) && $data['estado'] != "" && $data['estado'] != NULL){
            $estado = " ps.estado = '$data[estado]' ";
        }

        $moneda = "";
        if(isset($data['moneda']) && $data['moneda'] != "" && $data['moneda'] != NULL){
            $moneda = " AND ps.moneda = '$data[moneda]' ";
        }

        $tipo = "";
        if(isset($data['tipo']) && $data['tipo'] != "" && $data['tipo'] != NULL){
            $tipo = " AND ps.tipo = '$data[tipo]' ";
        }

        $titulo = "";
        if(isset($data['titulo']) && $data['titulo'] != "" && $data['titulo'] != NULL){
            $titulo = " AND p.titulo LIKE '%$data[titulo]%' ";
        }

        // $query = "SELECT * FROM productos_subastas WHERE ".$estado."".$moneda."".$tipo."";

        $query = "SELECT ps.*,p.id as producto_id,p.titulo as producto_titulo,p.precio as producto_precio, 
        p.foto_portada, pst.descripcion as tipo_descripcion  from productos_subastas as ps 
        join productos as p on p.id = ps.id_producto
        join productos_subastas_tipo as pst on pst.id = ps.tipo WHERE ".$estado."".$titulo."".$moneda."".$tipo." ORDER BY ps.id DESC";

        parent::conectar();
        $subastas_backend = parent::consultaTodo($query);
        parent::cerrar();

        if($subastas_backend){
            
            for($i = 0; $i<count($subastas_backend); $i++){
                $subastas_backend[$i]['fecha_fin'] = floatval( $subastas_backend[$i]['fecha_fin']);
                $subastas_backend[$i]['fecha_creacion'] = floatval( $subastas_backend[$i]['fecha_creacion']);
                $subastas_backend[$i]['fecha_actualizacion'] = floatval( $subastas_backend[$i]['fecha_actualizacion']);
                $subastas_backend[$i]['fecha_inicio'] = floatval( $subastas_backend[$i]['fecha_inicio']);
                $subastas_backend[$i]['producto_precio_mask'] =  $this->maskNumber($subastas_backend[$i]['producto_precio'], 2);
               
            }

            $respuesta = [];
            $numXpagina = 10;
            $hasta = $data['pag']*$numXpagina;
            $desde = ($hasta-$numXpagina)+1;
            $respuesta  = [];
            for($i = 0; $i<$hasta; $i++){
                if($i < count($subastas_backend)){
                    if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                        array_push($respuesta, $subastas_backend[$i]);
                    }
                }
            }
            $num_paginas = count($subastas_backend)/$numXpagina;
            $num_paginas = ceil($num_paginas);
            return array("status" => "success",'pagina' => $data['pag'],'total_paginas' => $num_paginas,'data' => $respuesta);
        }else{
            return array("status" => "fail", "data" => $subastas_backend);
        }
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function dar_permisos_publicacion(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_permisos_publicar WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';");
        parent::cerrar();  
        if(!$result){
            if(count($result) <= 0){
                parent::conectar();
                $permisoRegistro = parent::queryRegistro("INSERT INTO productos_permisos_publicar(uid,empresa) VALUES('$data[uid]','$data[empresa]');");
                parent::cerrar();  
                if($permisoRegistro){
                    return array("status" => "success","mensaje" => "se registraron permisos al usuario");
                }else{
                    return array("status" => "fail", "mensaje" => "error al registrar permisos");
                }
            }else{
                return array("status" => "fail", "mensaje" => "el usuario ya tiene permisos registrados");
            }   
        }
        return array("status" => "fail", "mensaje" => "hubo un error"); 
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function remover_permisos_publicacion(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT * FROM productos_permisos_publicar WHERE uid = '$data[uid]' empresa = '$data[empresa]';");
        parent::cerrar();
        if($result){
            if(count($result)>0){
                $id = $result[0]["id"];
                parent::conectar();
                $result = parent::consultaTodo("DELETE FROM productos_permisos_publicar WHERE id='$id'");
                parent::cerrar();
                if($result){
                    return array("status" => "success", "mensaje" => "se removieron los permisos");
                }
            }else{
                return array("status" => "fail", "mensaje" => "el no tiene permisos registrados");
            }
        }
        return array("status" => "fail", "mensaje" => "hubo un error");
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function modificar_subasta_backend(Array $data){

        parent::conectar();
        $updated = parent::query("UPDATE productos_subastas 
            SET estado = '$data[estado]',
            id_producto = '$data[id_producto]',	
            moneda = '$data[moneda]',	
            precio = '$data[precio]',	
            precio_usd = '$data[precio_usd]',	
            cantidad = '$data[cantidad]',	
            tipo = '$data[tipo]',	
            apostadores = '$data[apostadores]',	
            inscritos = '$data[inscritos]',	
            fecha_fin = '$data[fecha_fin]',	
            fecha_creacion = '$data[fecha_creacion]',
            fecha_actualizacion = '$data[fecha_actualizacion]',	
            fecha_inicio = '$data[fecha_inicio]'
            WHERE id = '$data[id]';
        ");
        parent::cerrar();
        if($updated){
            return array("status" => "success", "mensaje" => "erro al actualizar subasta", "data" => $data);
        }else{
            return array("status" => "fail", "mensaje" => "error al actualizar subasta");
        }
        
        // if(isset($data['id_subasta']) && $data['id_subasta'] != NULL){
        //     if(isset($data['estado']) && $data['estado'] != NULL){
        //         if(isset($data['fecha_inicio']) && $data['fecha_inicio'] != NULL){
        //             parent::conectar();
        //             parent::query("UPDATE productos_subastas SET estado = '$data[estado]' WHERE id = '$data[id_subasta]' ");
        //             parent::cerrar();
        //         }
        //         parent::conectar();
        //         parent::query("UPDATE productos_subastas SET estado = '$data[estado]' WHERE id = '$data[id_subasta]' ");
        //         parent::cerrar();       
        //     }
        // }
    }

    // ESTA FUNCION PERTENECE AL BACKEND
    function subastas_proximas_iniciar(Array $data){

        $fecha_actual = intval(microtime(true));
        // $date_array = explode(" ",$fecha_actual);
        // $date = date("Y-m-d H:i:s",$date_array[0]);
        $date = date("Y-m-d H:i:s",$fecha_actual);

        parent::conectar();
        $subastas = parent::consultaTodo("SELECT * FROM productos_subastas");
        parent::cerrar();
        $data_response = [];
        if($subastas){
            foreach($subastas as $subasta){
                $subasta_fecha = date("Y-m-d H:i:s",floatval($subasta['fecha_inicio'])/1000);
                $testInicio = strtotime($subasta_fecha);
                $test2Actual = strtotime($date);
                $diferencia_horas = abs($testInicio - $test2Actual)/(60*60);

                if($diferencia_horas <= $data['horas']){
                    $subasta["fecha_fin"] = floatval($subasta["fecha_fin"]);
                    $subasta['fecha_creacion'] = floatval($subasta['fecha_creacion']);
                    $subasta['fecha_actualizacion'] = floatval($subasta['fecha_actualizacion']);
                    $subasta['fecha_inicio'] = floatval($subasta['fecha_inicio']);
                    array_push($data_response,$subasta);
                } 
            }
        }
        return array("status" => "success","data"=>$data_response);        
    }
    // ESTA FUNCION PERTENECE AL BACKEND
    function obtener_subasta_backend(Array $data){
        $subasta = NULL;
        parent::conectar();
        $result = parent::consultaTodo("SELECT * from productos_subastas WHERE id = '$data[id]';");
        parent::cerrar();

        if($result){
            $subasta = $result[0];
            $id_subasta = $result[0]['id'];
            $subasta_tipo = $result[0]['tipo'];
            $subasta_estado = $result[0]['estado'];
            $producto_subasta = $result[0]['id_producto'];

            $subasta["fecha_fin"] = floatval($subasta["fecha_fin"]);
            $subasta["fecha_creacion"] = floatval($subasta["fecha_creacion"]);
            $subasta["fecha_actualizacion"] = floatval($subasta["fecha_actualizacion"]);
            $subasta["fecha_inicio"] = floatval($subasta["fecha_inicio"]);

            parent::conectar();
            $tipo_subasta = parent::consultaTodo("SELECT * from productos_subastas_tipo WHERE id = '$subasta_tipo';");
            $producto = parent::consultaTodo("SELECT * FROM productos WHERE id = '$producto_subasta'");
            $producto_fotos = parent::consultaTodo("SELECT * FROM productos_fotos WHERE id_publicacion = '$producto_subasta'");
            parent::cerrar();

            if($producto_fotos){
                $subasta['fotos'] = $producto_fotos;
                //fechas fotos
                    // for($i = 0; $i<count($subasta['fotos']); $i++){
                        // $subasta['fotos'][$i]['fecha_creacion'] = floatval( $subasta['fotos'][$i]['fecha_creacion']);
                        // $subasta['fotos'][$i]['fecha_actualizacion'] = floatval( $subasta['fotos'][$i]['fecha_actualizacion']);
                    // }
                //fin fechas fotos
            }else{
                $producto['fotos'] = NULL;
            }

            $subasta['tipo_subasta'] = [];
            if($tipo_subasta){
                // array_push($subasta['tipo_subasta'],$tipo_subasta[0]);
                $subasta['tipo_subasta'] = $tipo_subasta[0];
            }else{
                $subasta['tipo_subasta'] = NULL;
            }

            if($producto){
                $subasta['producto'] = $producto[0];
            }else{
                $subasta['producto'] = null;
            }

            $subasta_estado = $result[0]['estado'];

            parent::conectar();
            $estado_subasta = parent::consultaTodo("SELECT * from productos_subastas_estado where id = '$subasta_estado';");
            parent::cerrar();

            $subasta['estado_subasta'] = [];
            if($tipo_subasta){
                // array_push($subasta['estado_subasta'],$estado_subasta[0]);
                $subasta['estado_subasta'] = $estado_subasta[0];
            }else{
                $subasta['estado_subasta'] = NULL;
            }

            parent::conectar();
            $inscritos_subasta = parent::consultaTodo("SELECT * from productos_subastas_inscritos where id_subasta = '$id_subasta';");
            parent::cerrar();

            
            if($inscritos_subasta){
                $subasta['inscritos_subasta'] = $inscritos_subasta;
                for($i = 0; $i<count($subasta['inscritos_subasta']); $i++){
                    $subasta['inscritos_subasta'][$i]['fecha_creacion'] = floatval($subasta['inscritos_subasta'][$i]['fecha_creacion']);
                    $subasta['inscritos_subasta'][$i]['fecha_actualizacion'] = floatval($subasta['inscritos_subasta'][$i]['fecha_actualizacion']);
                }
            }else{
                $subasta['inscritos_subasta'] = NULL;
            }

            parent::conectar();
            $pujas_subasta = parent::consultaTodo("SELECT * FROM productos_subastas_pujas WHERE id_subasta = '$id_subasta' ORDER BY id DESC;");
            parent::cerrar();

            // $subasta['pujas_subasta'] = [];
            parent::conectar();
            if($pujas_subasta){
                $subasta['pujas_subasta'] = $pujas_subasta;
                $subasta['total_pujas'] = count($subasta['pujas_subasta']);
                for($i = 0; $i<count($subasta['pujas_subasta']); $i++){
                    $user_id = $subasta['pujas_subasta'][$i]['uid'];
                    $subasta['pujas_subasta'][$i]['fecha_creacion'] = floatval( $subasta['pujas_subasta'][$i]['fecha_creacion']);
                    $subasta['pujas_subasta'][$i]['fecha_actualizacion'] = floatval($subasta['pujas_subasta'][$i]['fecha_actualizacion']);
                    $subasta['pujas_subasta'][$i]['fecha_final'] = floatval($subasta['pujas_subasta'][$i]['fecha_final']);
                    
                    $usuario = parent::consultaTodo("SELECT id, username, nombreCompleto, email,telefono,idioma FROM peer2win.usuarios WHERE id = '$user_id';");
                    if($usuario){
                        $subasta['pujas_subasta'][$i]['usuario'] =  $usuario[0];
                    }else{
                        $subasta['pujas_subasta'][$i]['usuario'] = NULL;
                    }
                }
                
                if($subasta_estado == 4){
                    $subasta['ultima_puja'] =  $pujas_subasta[0];
                    $subasta['ultima_puja']['fecha_creacion'] = floatval($subasta['pujas_subasta'][0]['fecha_creacion']);
                    $subasta['ultima_puja']['fecha_actualizacion'] = floatval($subasta['pujas_subasta'][0]['fecha_actualizacion']);
                    $subasta['ultima_puja']['fecha_final'] = floatval($subasta['pujas_subasta'][0]['fecha_final']);
                    $puja_ganador = $subasta['ultima_puja']['uid'];
                    $ganador = parent::consultaTodo("SELECT id, username, nombreCompleto, email,telefono,idioma FROM peer2win.usuarios WHERE id = '$user_id';");
                    if($ganador){
                        $subasta['ultima_puja']['usuario'] = $ganador[0];
                    }else{
                        $subasta['ultima_puja']['usuario'] = NULL;
                    }
                }
            }else{
                $subasta['pujas_subasta'] = NULL;
            }
            parent::cerrar();

            return array("status" => "success", "data" => $subasta);

        }else{
            return array("status" => "fail", "mensaje" => "subasta no encontrada");
        }

        

        // if($result){
        //     return array("status" => "success", "data" => $result);
        // }else{
        //     return array("status" => "fail", "mensaje" => "no data");
        // }
    }

    function obtener_usuarios_sin_permiso(Array $data){
        parent::conectar();
        $result = parent::consultaTodo("SELECT id,username,nombreCompleto,telefono,email,paisid,idioma 
        FROM peer2win.usuarios WHERE id NOT IN (SELECT uid FROM productos_permisos_publicar WHERE empresa = 0);
        ");
        parent::cerrar();
        $respuesta = [];
            if($result){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($result)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $result[$i]);
                        }
                    }
                }
                $num_paginas = count($result)/$numXpagina;
                $num_paginas = ceil($num_paginas);
            }
        return array("status" => "success","mensaje" => "usuarios sin permiso para publicar", "data" =>$respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
    }

    function obtener_empresas_sin_permiso(Array $data){
        
        parent::conectar();
        $result = parent::consultaTodo("SELECT id,nombre_empresa,nit,razon_social,correo,idioma,foto_logo_empresa  
        FROM buyinbig.empresas WHERE id NOT IN (SELECT uid FROM productos_permisos_publicar WHERE empresa = 1)");
        parent::cerrar();

        $respuesta = [];
            if($result){
                $numXpagina = 10;
                $hasta = $data['pag']*$numXpagina;
                $desde = ($hasta-$numXpagina)+1;
                $respuesta  = [];
                for($i = 0; $i<$hasta; $i++){
                    if($i < count($result)){
                        if(($i + 1) >= $desde && ($i + 1) <= $hasta){
                            array_push($respuesta, $result[$i]);
                        }
                    }
                }
                $num_paginas = count($result)/$numXpagina;
                $num_paginas = ceil($num_paginas);
            }
        return array("status" => "success","mensaje" => "empresas sin permiso para publicar", "data" =>$respuesta,'pagina' => $data['pag'], 'total_paginas' => $num_paginas);
    }

    // FIN BACKEND



    function envio_correo_rechazo_(Array $data_producto){
        $data_user=  $this->datosUserGeneral2([
            'uid' => $data_producto['uid'],
            'empresa' => $data_producto['empresa']
        ]);
        $this->htmlEmailrechazo_producto($data_producto, $data_user["data"]);
        

    }

    public function htmlEmailrechazo_producto(Array $data_producto, Array $data_user){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo6revision.html");

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$data_user['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $data_producto['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        $html = str_replace("{{trans152_}}", $json->trans152_, $html);
        $html = str_replace("{{trans151_}}", $json->trans151_, $html);
        
        
        $html = str_replace("{{titulo_producto}}",$data_producto['titulo'], $html);
        $html = str_replace("{{trans149_}}",$json->trans149_, $html);
        $html = str_replace("{{trans150_}}",$json->trans150_, $html);

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$data_producto['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
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
        $titulo    = $json->trans152_;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }


    function get_data_subasta_por_id_producto(Array $subasta){
        parent::conectar();
        $misSubastas_revision = parent::consultaTodo("SELECT * FROM buyinbig.productos_subastas WHERE id_producto = '$subasta[id]'; ");
        parent::cerrar();

        return $misSubastas_revision;
    }

}
?>