<?php
    require 'conexion.php';
    
    class Support extends Conexion {
        public function listarSupport()
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "SELECT * FROM soporte";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
            return $respuesta;
        }

        public function listarSupportId($support)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "SELECT * FROM soporte WHERE usuario = " . $support['user'] . " ORDER BY fecha DESC;";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            $respuesta = array('status' => 'success', 'message' => 'Se registro', 'data' => $lista);
            return $respuesta;
        }

        public function saveSupport($support)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "INSERT INTO soporte (titulo, mensaje, archivo, usuario, respuesta, fecha) VALUE ('". $support["titulo"] ."', '". $support["mensaje"] ."', '". $support["imagen"] ."', ". $support["user"] .", '', current_time)";
            $lista      = parent::queryRegistro($consultar1);
            parent::cerrar();
            if ($lista > 0) {
                $respuesta = array('status' => 'success', 'message' => 'Se registro', 'data' => $lista);
            } else {
                $respuesta = array('sql'=>$consultar1, 'status' => 'errorServidor', 'message' => 'Error en el servidor');
            }
            return $respuesta;
        }

        public function updateSupport($support)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "UPDATE soporte SET respuesta='$support[respuesta]' WHERE id=$support[id];";
            $lista      = parent::queryRegistro($consultar1);
            parent::cerrar();
            if ($lista > 0) {
                $respuesta = array('status' => 'success', 'message' => 'Se registro', 'data' => $lista);
            } else {
                $respuesta = array("hola"=>"si",'status' => 'errorServidor', 'message' => 'Error en el servidor');
            }
            return $respuesta;
        }

        public function html_respuesta_nasbi_cliente(Array $data_user, Array $data_mensaje){
            $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_user["idioma"].".json"));
            $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo7.html");
            

            $html = str_replace("{{trans135_brand}}",$json->trans135_brand, $html);
            $html = str_replace("{{trans136}}", $json->trans136, $html);
            $html = str_replace("{{trans137}}", $json->trans137, $html);
            $html = str_replace("{{nombre_usuario}}", $data_user["nombre"], $html);//nombre de usuario 
            $html = str_replace("{{respuesta_ventaTrad_7}}", $data_user["mensaje"], $html);//mensaje de respuesta 
            $html = str_replace("{{trans99}}", $json->trans99, $html);
        
        
            $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
            $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
            $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
            $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
            $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
            $html = str_replace("{{trans06_}}",$json->trans06_, $html);
            $html = str_replace("{{trans07_}}",$json->trans07_, $html);
            $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_user["uid"]."&act=0&em=".$data_user["empresa"], $html); 
    
            $para      = $data_user['correo'] . ', dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com';
            $mensaje1   = $html;
            $titulo    = $json->trans152_;
            $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
            $cabeceras .= 'From: info@nasbi.com' . "\r\n";
            //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
            $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

        }


    }