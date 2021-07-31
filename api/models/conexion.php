<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


    class Conexion{
        public $isEnable = false;
        private $conexion;
        private $usuario='root';
        private $clave='Adminabc123.,.';
        private $server='localhost';
        private $db='buyinbig';
        
        public function conectar(){
            $this->conexion = new mysqli($this->server, $this->usuario, $this->clave, $this->db);
            if($this->conexion->connect_errno) {
                echo'Falla al conectar con MySQL: '.$this->conexion->connect_error;
                $this->isEnable = false;
            }else{
                $this->isEnable = true;
            }
        }
        
        public function query($consulta, $activarutf8 = true){
            if($activarutf8 == true) $this->conexion->set_charset("utf8");
            if($activarutf8 == false) $this->conexion->set_charset("utf8mb4");
            return $this->conexion->query($consulta);
        }

        public function queryRegistro($consulta){
            $this->conexion->set_charset("utf8");
            $this->conexion->query($consulta);
            return $this->conexion->insert_id;
        }
        
        public function verificarRegistros($consulta){
            return $verificarRegistros = mysqli_num_rows($this->conexion->query($consulta));
        }

        function number_format($numero, $decimales) {
            return intval($numero * (10 ** $decimales)) / (10 ** $decimales);
        }

        
        public function addLog($text) {
            $fp = fopen(__DIR__.'/logs.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }
        public function addLogRevision($text) {
            $fp = fopen(__DIR__.'/logs_revision.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }
        public function addLogTCC($text) {
            $fp = fopen(__DIR__.'/logs_TCC.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }
        public function addLogSIIGO($text) {
            $fp = fopen(__DIR__.'/logs_siigo.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }
        public function addLogPD($text) {
            $fp = fopen(__DIR__.'/logs_PD.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }

        public function addLogDP($text) {
            $fp = fopen(__DIR__.'/logs_dispersion.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."===>".$text."\n");  
            fclose($fp);  
        }

        public function addLogSubastas($text) {
            $fp = fopen(__DIR__.'/logs_subastas.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."--->".$text."\n");  
            fclose($fp);  
        }

        public function addLogJB($text) {
            $fp = fopen(__DIR__.'/logs_jb.txt', 'a');
            fwrite($fp, "\n" . date("Y-m-d H:i:s")."--->".$text."\n");  
            fclose($fp);  
        }

        function conectarNodo($post_data = "", $timeout = 10)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://192.168.0.200:18332/wallet/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, "1");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_USERPWD, "MainBitcoinProd:MainBitcoinInEmetNumero1");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return ($file_contents) ? $file_contents : false;
        }

        function conectarNodoEBG($post_data = "", $timeout = 10)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://192.168.0.200:8003/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, "1");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return ($file_contents) ? $file_contents : false;
        }

        function conectarNodoPayment($post_data = "", $timeout = 10)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://192.168.0.200:18332/wallet/p2w");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, "1");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_USERPWD, "MainBitcoinProd:MainBitcoinInEmetNumero1");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return ($file_contents) ? $file_contents : false;
        }

         function remoteRequest($url, $post_data = "", $timeout = 10)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, "1");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'User-Agent:' . $_SERVER['HTTP_USER_AGENT']));
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return ($file_contents) ? $file_contents : false;
        }
        function remoteRequestPagoDigital($url, Array $post_data)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, "1");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return json_decode($file_contents,true);
        }


        function remoteRequestPagoDigitalParams($url, Array $post_data)
        {

            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $post_data,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return (Array) json_decode($response);
        }


        public function metodoPost($aURL, $data)
        {
            $reques = $this->remoteRequest('https://testnet.foodsdnd.com/plataformaEducativa/api/controllers/'.$aURL, $data);
            return json_decode($reques);
        }

        public function metodoGet($aURL) 
        {
            return json_decode($this->remoteRequest('https://testnet.foodsdnd.com/plataformaEducativa/api/controllers/'.$aURL));
        }
        
        public function consultarArreglo($consulta, $activarutf8 = true){
            if($activarutf8 == true) $this->conexion->set_charset("utf8");
            if($activarutf8 == false) $this->conexion->set_charset("utf8mb4");
            return mysqli_fetch_array($this->conexion->query($consulta),MYSQLI_ASSOC);
        }
        
        public function consultaTodo($consulta){
            $this->conexion->set_charset("utf8");
            $result = $this->conexion->query($consulta);
            $results_array = [];
            while ($row = $result->fetch_assoc()) {
              $results_array[] = $row;
            }
            return $results_array;
        }
         
        public function cerrar(){
            $this->isEnable = false;
            $this->conexion->close();
        }
        public function isEnableMySQL(){

            if( $this->isEnable ){
                return $this->isEnable;
            }else{
                return false;
            }
        }
        
        public function salvar($des){
            $string = $this->conexion->real_escape_string($des);
            return $string;
        }
        
        public function filtra($string){
            $res = $this->salvar($string);
            $buscar = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
            $reemplazar = array('&aacute','&eacute', '&iacute', '&oacute', '&uacute', '&Aacute', '&Eacute', '&Iacute', '&Oacute', '&Uacute', '&ntilde', '&Ntilde');
            $res = str_replace($buscar,$reemplazar,$string);
            $res = strtolower($res);
            $res = trim($res);
            return $res;
        }
        
        public function recartar($string){
            $buscar = array('&aacute','&eacute', '&iacute', '&oacute', '&uacute', '&Aacute', '&Eacute', '&Iacute', '&Oacute', '&Uacute', '&ntilde', '&Ntilde');
            $reemplazar = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
            $res = str_replace($buscar,$reemplazar,$string);
            return $res;
        }



        /*
            cuando el tipo de persona comprador es juridico
            se agrega valor de reteica y retefuente

            cuando el tipo de persona vendedor es juridico
            se agrega e valor del iva del producto

        */

        public function calcularMontosPagoDigital(Array $data)
	    {
	        if (   !isset($data["costo_producto"]) 
	            || !isset($data["flete"]) 
	            || !isset($data["recaudo_pasarela"]) 
	            || !isset($data["segundo_metodo_pago"]) 
	            || !isset($data["monto_segundo_metodo_pago"]) 
	            // || !isset($data["iva_producto"]) 
	            || !isset($data["metodoPago"])  
                || !isset($data["porcentaje_comision_nasbi"])
                || !isset($data["tipo_usuario_comprador"])
                || !isset($data["tipo_usuario_vendedor"])
                || !isset($data["serial_bono"])
	        	|| !isset($data["descripcion_pago"])) {

	            return array("status" => "error", "message" => "no has enviado los parametros correctamente", "datos"=>$data);

	        }

            $costo_producto                                = $data["costo_producto"];
	        $flete                                         = $data["flete"];
	        $recaudo_pasarela_inicial                      = $data["recaudo_pasarela"];
	        $segundo_metodo_pago                           = $data["segundo_metodo_pago"];
	        $monto_segundo_metodo_pago                     = $data["monto_segundo_metodo_pago"];
	        // $iva_producto                                  = $data["iva_producto"];
	        $metodoPago                                    = $data["metodoPago"];
	        $porcentaje_comision_nasbi                     = $data["porcentaje_comision_nasbi"];
	        $descripcion_pago                              = $data["descripcion_pago"];
            $tipo_usuario_comprador                        = $data["tipo_usuario_comprador"];
            $tipo_usuario_vendedor                         = $data["tipo_usuario_vendedor"];
            $serial_bono                                   = $data["serial_bono"] || "";
            $recaudo_pasarela                              = $recaudo_pasarela_inicial + $flete;

            $iva_producto = 0;




	        $porcentaje_iva                                = 0.19;
            $costo_producto_sin_iva                        = $costo_producto / (1 + $porcentaje_iva); 
            $costo_producto_con_iva                        = $costo_producto;

            if ($tipo_usuario_vendedor == "JURIDICA") {
                if ($iva_producto <= 0) {
                    // return array("status" => "error", "message" => "no has enviado los parametros correctamente");
                    $iva_producto = $costo_producto_con_iva  - $costo_producto_sin_iva;
                }
            }

            $total                                         = $costo_producto + $flete;
	        $tarifa_fija_pago_digital                      = 700;
	        $porcentaje_tarifa_variable_pago_digital       = 0.03;
	        $porcentaje_retefuente_bancario                = 0.015;
	        $porcentaje_reteica_bancario                   = 0.00414;
	        $porcentaje_rete_iva_bancario                  = 0.15;
	        $porcentaje_retefuente_comisiones_nasbi        = 0.11;
	        $porcentaje_retefuente_comisiones_pago_digital = 0.11;
	        $porcentaje_reteica_comision_nasbi             = 0.00966;
	        $porcentaje_reteica_comision_pago_digital      = 0.00966;


            $porcentaje_retefuente_tcpj = 0.025;
            $porcentaje_reteica_tcpj      = 0.00966;

            $retefuente = 0;
            $reteica = 0;

            if ($tipo_usuario_comprador == "JURIDICA" && $metodoPago != 6) {
                $retefuente = $costo_producto * $porcentaje_retefuente_tcpj;
                $reteica = $costo_producto * $porcentaje_reteica_tcpj;

                $total -= $retefuente;
                $total -= $reteica;

                $recaudo_pasarela -= $retefuente;
                $recaudo_pasarela -= $reteica;
            }




	        /* porcentajes_segundo metodo pago*/
	        $porcentaje_iva_segundo_metodo                                = 0.19;
	        $tarifa_fija_pago_digital_segundo_metodo                      = 700;
	        $porcentaje_tarifa_variable_pago_digital_segundo_metodo       = 0.03;
	        $porcentaje_retefuente_bancario_segundo_metodo                = 0.015;
	        $porcentaje_reteica_bancario_segundo_metodo                   = 0.00414;
	        $porcentaje_rete_iva_bancario_segundo_metodo                  = 0.15;
	        $porcentaje_retefuente_comisiones_nasbi_segundo_metodo        = 0.11;
	        $porcentaje_retefuente_comisiones_pago_digital_segundo_metodo = 0.11;
	        $porcentaje_reteica_comision_nasbi_segundo_metodo             = 0.00966;
	        $porcentaje_reteica_comision_pago_digital_segundo_metodo      = 0.00966;

            /* nasbichips */
            $porcentaje_recuperacion_tarifa_variable_pago_digital = 0.03;
            $porcentaje_recuperacion_iva_comision_pd = 0.19;
            $recuperacion_tarifa_variable_pago_digital = 0;
            $recuperacion_iva_comision_pd = 0;

	        /* monto de pago inicial*/
	        $valor_comision_nasbi           = $recaudo_pasarela * $porcentaje_comision_nasbi;
	        $iva                            = $valor_comision_nasbi * $porcentaje_iva;
	        $tarifa_variable_pago_digital   = $recaudo_pasarela * $porcentaje_tarifa_variable_pago_digital;
	        $iva_por_tarifa_fija_y_variable = ($tarifa_fija_pago_digital + $tarifa_variable_pago_digital) * $porcentaje_iva;

	        $retefuente_bancaria = ($metodoPago == 4)?($recaudo_pasarela * $porcentaje_retefuente_bancario) : 0;
	        $reteica_bancario    = ($metodoPago==4)?($recaudo_pasarela * $porcentaje_reteica_bancario):0;
	        $reteiva_bancario    = $iva_producto * $porcentaje_rete_iva_bancario;
	        $retefuente_comisiones_nasbi        = $metodoPago != 6?$valor_comision_nasbi * $porcentaje_retefuente_comisiones_nasbi:0;
	        $retefuente_comisiones_pago_digital = $metodoPago != 6?($tarifa_variable_pago_digital + $tarifa_fija_pago_digital) * $porcentaje_retefuente_comisiones_pago_digital : 0;
	        
            $reteica_comisiones_nasbi        = $metodoPago != 6?$valor_comision_nasbi * $porcentaje_reteica_comision_nasbi:0;
	        $reteica_comisiones_pago_digital = $metodoPago != 6?($tarifa_variable_pago_digital + $tarifa_fija_pago_digital) * $porcentaje_reteica_comision_pago_digital : 0;

            //+ $reteiva_bancario
            $reteiva_bancario = 0;

            if ($metodoPago == 6) {
                //$iva = 0;
                $tarifa_variable_pago_digital = 0;
                $iva_por_tarifa_fija_y_variable = 0;
                $recuperacion_tarifa_variable_pago_digital = $total * $porcentaje_recuperacion_tarifa_variable_pago_digital;
                $recuperacion_iva_comision_pd = ($recuperacion_tarifa_variable_pago_digital + $tarifa_fija_pago_digital) * $porcentaje_recuperacion_iva_comision_pd;
            }
                $total_comisiones = $valor_comision_nasbi + $iva + $tarifa_variable_pago_digital + $tarifa_fija_pago_digital + $iva_por_tarifa_fija_y_variable + $retefuente_bancaria + $reteica_bancario  + $retefuente_comisiones_nasbi + $retefuente_comisiones_pago_digital + $reteica_comisiones_nasbi + $reteica_comisiones_pago_digital + $recuperacion_iva_comision_pd + $recuperacion_tarifa_variable_pago_digital;    
                

	        

	        $otros_cargos_abonos = 0;
            if ($metodoPago == 6) {
                $otros_cargos_abonos = 6500;
            } else if ($recaudo_pasarela != 0 && $monto_segundo_metodo_pago != 0) {
                $otros_cargos_abonos = 1500;
                $otros_cargos_abonos += $otros_cargos_abonos * $porcentaje_iva;

            }

	        $total_valor_recibir_vendedor = $recaudo_pasarela - $otros_cargos_abonos - $total_comisiones;


            if ($total_valor_recibir_vendedor < 0) {
                $total_valor_recibir_vendedor = 0;
            }

	        /* monto de pago inicial*/
	        $valor_comision_nasbi_segundo_metodo = $monto_segundo_metodo_pago * $porcentaje_comision_nasbi;
	        $iva_segundo_metodo = $valor_comision_nasbi_segundo_metodo * $porcentaje_iva_segundo_metodo;
	        
            $tarifa_variable_pago_digital_segundo_metodo = (($metodoPago != 6)?$monto_segundo_metodo_pago:$flete + $monto_segundo_metodo_pago) * $porcentaje_tarifa_variable_pago_digital_segundo_metodo;

	        $iva_por_tarifa_fija_y_variable_segundo_metodo = ($tarifa_fija_pago_digital_segundo_metodo + $tarifa_variable_pago_digital_segundo_metodo) * $porcentaje_iva_segundo_metodo;

            if ($metodoPago == 6 || $monto_segundo_metodo_pago == 0) {
                $tarifa_fija_pago_digital_segundo_metodo = 0;
                $tarifa_variable_pago_digital_segundo_metodo = 0;
                $iva_por_tarifa_fija_y_variable_segundo_metodo = 0;
            }


            $total_comisiones_segundo_metodo =  $valor_comision_nasbi_segundo_metodo + $iva_segundo_metodo + $tarifa_variable_pago_digital_segundo_metodo + $tarifa_fija_pago_digital_segundo_metodo + $iva_por_tarifa_fija_y_variable_segundo_metodo;

	        $otros_cargos_abonos_segundo_metodo = 0;

	        $total_valor_recibir_vendedor_segundo_metodo = $monto_segundo_metodo_pago - $otros_cargos_abonos_segundo_metodo - $total_comisiones_segundo_metodo;

            
	        $total_a_recibir_vendedor_consolidado = (($costo_producto!=0)?$costo_producto_con_iva:$monto_segundo_metodo_pago)  - ((($costo_producto!=0)?$total_comisiones:0) + $total_comisiones_segundo_metodo) - $retefuente -$reteica - $otros_cargos_abonos;




            $total_todas_comisiones = $total_comisiones + $total_comisiones_segundo_metodo;




	        $insertar = 
	        "INSERT INTO buyinbig.pago_digital_calculos_pasarelas (
	            costo_producto,
	            flete,
	            metodo_pago,
	            recaudo_pasarela,
	            segundo_metodo_pago,
	            monto_segundo_metodo_pago,
	            iva_producto,
	            porcentaje_comision_nasbi,
	            porcentaje_iva,
	            tarifa_fija_pago_digital,
	            porcentaje_tarifa_variable_pago_digital,
	            porcentaje_retefuente_bancario,
	            porcentaje_reteica_bancario,
	            porcentaje_rete_iva_bancario,
	            porcentaje_retefuente_comisiones_nasbi,
	            porcentaje_retefuente_comisiones_pago_digital,
	            porcentaje_reteica_comision_nasbi,
	            porcentaje_reteica_comision_pago_digital,
	            valor_comision_nasbi,
	            iva,
	            tarifa_variable_pago_digital,
	            iva_por_tarifa_fija_y_variable,
	            retefuente_bancaria,
	            reteica_bancario,
	            retefuente_comisiones_nasbi,
	            retefuente_comisiones_pago_digital,
	            reteica_comisiones_nasbi,
	            reteica_comisiones_pago_digital,
	            total_comisiones,
	            otros_cargos_abonos,
	            total_valor_recibir_vendedor,
	            valor_comision_nasbi_segundo_metodo,
	            iva_segundo_metodo,
	            tarifa_fija_pago_digital_segundo_metodo,
	            tarifa_variable_pago_digital_segundo_metodo,
	            iva_por_tarifa_fija_y_variable_segundo_metodo,
	            total_comisiones_segundo_metodo,
	            otros_cargos_abonos_segundo_metodo,
	            total_valor_recibir_vendedor_segundo_metodo,
	            total_a_recibir_vendedor_consolidado,
	            fecha_calculo,
	            descripcion_pago,
                tipo_comprador,
                tipo_vendedor,
                retefuente,
                reteica,
                total_todas_comisiones,
                porcentaje_recuperacion_tarifa_variable_pago_digital,
                recuperacion_tarifa_variable_pago_digital,
                porcentaje_recuperacion_iva_comision_pd,
                recuperacion_iva_comision_pd,
                serial_bono
	        )VALUES(
	            $costo_producto,
	            $flete,
	            '$metodoPago',
	            $recaudo_pasarela,
	            '$segundo_metodo_pago',
	            $monto_segundo_metodo_pago,
	            $iva_producto,
	            $porcentaje_comision_nasbi,
	            $porcentaje_iva,
	            $tarifa_fija_pago_digital,
	            $porcentaje_tarifa_variable_pago_digital,
	            $porcentaje_retefuente_bancario,
	            $porcentaje_reteica_bancario,
	            $porcentaje_rete_iva_bancario,
	            $porcentaje_retefuente_comisiones_nasbi,
	            $porcentaje_retefuente_comisiones_pago_digital,
	            $porcentaje_reteica_comision_nasbi,
	            $porcentaje_reteica_comision_pago_digital,
	            $valor_comision_nasbi,
	            $iva,
	            $tarifa_variable_pago_digital,
	            $iva_por_tarifa_fija_y_variable,
	            $retefuente_bancaria,
	            $reteica_bancario,
	            $retefuente_comisiones_nasbi,
	            $retefuente_comisiones_pago_digital,
	            $reteica_comisiones_nasbi,
	            $reteica_comisiones_pago_digital,
	            $total_comisiones,
	            $otros_cargos_abonos,
	            $total_valor_recibir_vendedor,
	            $valor_comision_nasbi_segundo_metodo,
	            $iva_segundo_metodo,
	            $tarifa_fija_pago_digital_segundo_metodo,
	            $tarifa_variable_pago_digital_segundo_metodo,
	            $iva_por_tarifa_fija_y_variable_segundo_metodo,
	            $total_comisiones_segundo_metodo,
	            $otros_cargos_abonos_segundo_metodo,
	            $total_valor_recibir_vendedor_segundo_metodo,
	            $total_a_recibir_vendedor_consolidado,
	            CURRENT_TIMESTAMP,
	            '$descripcion_pago',
                '$tipo_usuario_comprador',
                '$tipo_usuario_vendedor',
                $retefuente,
                $reteica,
                $total_todas_comisiones,
                $porcentaje_recuperacion_tarifa_variable_pago_digital,
                $recuperacion_tarifa_variable_pago_digital,
                $porcentaje_recuperacion_iva_comision_pd,
                $recuperacion_iva_comision_pd,
                '$serial_bono'
	        );";

	        $this->conectar();
	        $insertarTabla = $this->queryRegistro( $insertar );
	        $this->cerrar();

	        return array(
	            "status"=>"success",
	            "data"=>array(
	                "id_insercion"=>$insertarTabla,
	                "costo_producto"=>$costo_producto,
	                "flete"=>$flete,
	                "metodoPago"=>$metodoPago,
                    "recaudo_pasarela_inicial"=>$recaudo_pasarela_inicial,
	                "recaudo_pasarela"=>$recaudo_pasarela,
	                "segundo_metodo_pago"=>$segundo_metodo_pago,
	                "monto_segundo_metodo_pago"=>$monto_segundo_metodo_pago,
	                "iva_producto"=>$iva_producto,
	                "porcentaje_comision_nasbi"=>$porcentaje_comision_nasbi,
                    "porcentaje_retefuente_tcpj"=>$porcentaje_retefuente_tcpj,
                    "porcentaje_reteica_tcpj"=>$porcentaje_reteica_tcpj,
                    "tipo_usuario_comprador"=>$tipo_usuario_comprador,
                    "tipo_usuario_vendedor"=>$tipo_usuario_vendedor,
                    "retefuente"=>$retefuente,
                    "reteica"=>$reteica,
                    "costo_producto_sin_iva" => $costo_producto_sin_iva,
                    "costo_producto_con_iva" => $costo_producto_con_iva,
                    "recaudo_total_neto"=> $total,

	                "porcentaje_iva"=>$porcentaje_iva,
	                "tarifa_fija_pago_digital"=>$tarifa_fija_pago_digital,
	                "porcentaje_tarifa_variable_pago_digital"=>$porcentaje_tarifa_variable_pago_digital,
	                "porcentaje_retefuente_bancario_segundo_metodo"=>$porcentaje_retefuente_bancario,
	                "porcentaje_reteica_bancario"=>$porcentaje_reteica_bancario,
	                "porcentaje_rete_iva_bancario"=>$porcentaje_rete_iva_bancario,
	                "porcentaje_retefuente_comisiones_nasbi"=>$porcentaje_retefuente_comisiones_nasbi,
	                "porcentaje_retefuente_comisiones_pago_digital"=>$porcentaje_retefuente_comisiones_pago_digital,
	                "porcentaje_reteica_comision_nasbi"=>$porcentaje_reteica_comision_nasbi,
	                "porcentaje_reteica_comision_pago_digital"=>$porcentaje_reteica_comision_pago_digital,
                    "serial_bono" => $serial_bono,

                    // "reteiva_bancario"=>$reteiva_bancario,


	                "valor_comision_nasbi"=>$valor_comision_nasbi,
	                "iva"=>$iva,
	                "tarifa_fija_pago_digital"=> $tarifa_fija_pago_digital,
	                "tarifa_variable_pago_digital"=>$tarifa_variable_pago_digital,
	                "iva_por_tarifa_fija_y_variable"=>$iva_por_tarifa_fija_y_variable,
	                "retefuente_bancaria"=>$retefuente_bancaria,
	                "reteica_bancario"=>$reteica_bancario,
	                "retefuente_comisiones_nasbi"=>$retefuente_comisiones_nasbi,
	                "retefuente_comisiones_pago_digital"=>$retefuente_comisiones_pago_digital,
	                "reteica_comisiones_nasbi"=>$reteica_comisiones_nasbi,
	                "reteica_comisiones_pago_digital"=>$reteica_comisiones_pago_digital,
	                "total_comisiones"=>$total_comisiones,
	                "otros_cargos_abonos"=>$otros_cargos_abonos,
	                "total_valor_recibir_vendedor"=> $total_valor_recibir_vendedor,

	                "valor_comision_nasbi_segundo_metodo"=>$valor_comision_nasbi_segundo_metodo,
	                "iva_segundo_metodo"=>$iva_segundo_metodo,
	                "tarifa_fija_pago_digital_segundo_metodo"=> $tarifa_fija_pago_digital_segundo_metodo,
	                "tarifa_variable_pago_digital_segundo_metodo"=>$tarifa_variable_pago_digital_segundo_metodo,
	                "iva_por_tarifa_fija_y_variable_segundo_metodo"=>$iva_por_tarifa_fija_y_variable_segundo_metodo,

	                "total_comisiones_segundo_metodo"=>$total_comisiones_segundo_metodo,
	                "otros_cargos_abonos_segundo_metodo"=>$otros_cargos_abonos_segundo_metodo,
	                "total_valor_recibir_vendedor_segundo_metodo"=> $total_valor_recibir_vendedor_segundo_metodo,
	                "total_a_recibir_vendedor_consolidado"=> $total_a_recibir_vendedor_consolidado,
	                "descripcion_pago"=> $descripcion_pago,
                    "total_todas_comisiones"=> $total_todas_comisiones,


                    "porcentaje_recuperacion_tarifa_variable_pago_digital"=>$porcentaje_recuperacion_tarifa_variable_pago_digital,
                    "recuperacion_tarifa_variable_pago_digital"=>$recuperacion_tarifa_variable_pago_digital,
                    "porcentaje_recuperacion_iva_comision_pd"=>$porcentaje_recuperacion_iva_comision_pd,
                    "recuperacion_iva_comision_pd"=>$recuperacion_iva_comision_pd
	            )
	        );
	    }
    }