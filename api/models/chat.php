<?php
require 'nasbifunciones.php';
require '../../Shippo.php';

class Chat extends Conexion
{
    public function insertarChat(Array $data){
        if(!isset($data) || !isset($data['id_transaccion']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['mensaje']) && !isset($data['imagen'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        
        if($data['tipo'] == 1) $data['folder'] = 'chat';
        
        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;
        $mensaje = null;
        if(isset($data['mensaje'])) $mensaje = addslashes($data['mensaje']);
        
        $imagen = null;
        if(isset($data['imagen'])){
            $imagen = strrpos($data['imagen'], 'base64');
            if ($imagen === false) {
                $imagen = $data['imagen'];
            }else{
                $imagen = $this->subirFotoChat([
                    'id' => $data['id_transaccion'],
                    'img' => addslashes($data['imagen']),
                    'fecha' => $fecha,
                    'tipo' => $data['folder']
                ]);
            }
        }

        

        parent::conectar();
        $insertarxchat = "INSERT INTO chat
        (
            id_transaccion,
            tipo,
            imagen,
            mensaje,
            uid,
            empresa,
            visualizado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            '$data[id_transaccion]',
            '$data[tipo]',
            '$imagen',
            '$mensaje',
            '$data[uid]',
            '$data[empresa]',
            '0',
            '$fecha',
            '$fecha'
        );";
        $chat = parent::query($insertarxchat, false);
        parent::cerrar();
        unset($conexion);
        if(!$chat) return array('status' => 'fail', 'message'=> 'no se pudo insertar el chat', 'data' => null);
        
        $this->actualizarChat($data);
         $this->enviar_correo_mensaje_nuevo($data); 
        return array('status' => 'success', 'message'=> 'chat insertado', 'data' => $imagen );
    }

    public function verChat(Array $data)
    {
        if(!isset($data) || !isset($data['id_transaccion']) || !isset($data['uid']) || !isset($data['empresa']) || !isset($data['tipo'])) return array('status' => 'fail', 'message'=> 'faltan datos', 'data' => null);
        if(!isset($data['pagina'])) $data['pagina'] = 1;

        $pagina = floatval($data['pagina']);
        $numpagina = 10;
        $hasta = $pagina*$numpagina;
        $desde = ($hasta-$numpagina)+1;

        $fecha = intval(microtime(true)*1000);
        $data['fecha'] = $fecha;


        $this->actualizarChat($data);
        
        parent::conectar();
        $selecxchat = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM(
                SELECT c.* 
                FROM chat c
                JOIN (SELECT @row_number := 0) r
                WHERE c.id_transaccion = '$data[id_transaccion]' AND c.tipo = '$data[tipo]'
                ORDER BY fecha_creacion DESC
                )as datos 
            ORDER BY fecha_creacion DESC
        )AS info
        WHERE info.num BETWEEN '$desde' AND '$hasta';";
        $chat = parent::consultaTodo($selecxchat, false);
        if(count($chat) <= 0) {
            parent::cerrar();
            return array('status' => 'fail', 'message'=> 'no tienes mensajes en este chat', 'pagina'=> $pagina, 'total_paginas'=> 0, 'mensajes' => 0, 'total_productos' => 0, 'data' => null);
        }

        $selecttodos = "SELECT COUNT(c.id) AS contar 
        FROM chat c
        WHERE c.id_transaccion = '$data[id_transaccion]' AND c.tipo = '$data[tipo]';";
        $todoslosproductos = parent::consultaTodo($selecttodos);
        $todoslosproductos = floatval($todoslosproductos[0]['contar']);
        $totalpaginas = $todoslosproductos/$numpagina;
        $totalpaginas = ceil($totalpaginas);
        $chat = $this->mapChat($chat);
        parent::cerrar();
        return array('status' => 'success', 'message'=> 'chat', 'pagina'=> $pagina, 'total_paginas'=>$totalpaginas, 'mensajes' => count($chat), 'total_productos' => $todoslosproductos, 'data' => $chat);
    }

    function subirFotoChat(Array $data)
    {
        $nombre_fichero_principal = $_SERVER['DOCUMENT_ROOT'].'/imagenes/'.$data['tipo'].'/';
        if (!file_exists($nombre_fichero_principal)) mkdir($nombre_fichero_principal, 0777, true);
        
        $nombre_fichero = $_SERVER['DOCUMENT_ROOT'].'/imagenes/'.$data['tipo'].'/'.$data['id'];
        if (!file_exists($nombre_fichero)) mkdir($nombre_fichero, 0777, true);

        $imgconcat = $data['tipo'].'_'.$data['fecha'];
        $url = $this->uploadImagen([
            'img' => $data['img'],
            'ruta' => '/imagenes/'.$data['tipo'].'/'.$data['id'].'/'.$imgconcat.'.png',
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

    function actualizarChat(Array $data)
    {
        parent::conectar();
        $updatexchat = "UPDATE chat
        SET
            visualizado = '1',
            fecha_actualizacion = '$data[fecha]'
        WHERE id_transaccion = '$data[id_transaccion]' AND tipo = '$data[tipo]' AND (uid <> '$data[uid]' OR empresa <> '$data[empresa]')";
        $updatechat = parent::query($updatexchat);
        parent::cerrar();
        if(!$updatechat) return array('status' => 'fail', 'message'=> 'chat no actualizado', 'data' => null);
        
        return array('status' => 'success', 'message'=> 'chat actualizado', 'data' => null);
    }

    function mapChat(Array $chat)
    {
        foreach ($chat as $x => $msj) {
            $msj['id'] = floatval($msj['id']);
            $msj['id_transaccion'] = floatval($msj['id_transaccion']);
            $msj['tipo'] = floatval($msj['tipo']);
            $msj['uid'] = floatval($msj['uid']);
            $msj['empresa'] = floatval($msj['empresa']);
            $msj['visualizado'] = floatval($msj['visualizado']);
            $msj['fecha_creacion'] = $msj['fecha_creacion'];
            $msj['fecha_actualizacion'] = $msj['fecha_actualizacion'];

            $chat[$x] = $msj;
        }

        return $chat;
    }


    function enviar_correo_mensaje_nuevo(Array $data){
        $data_chat_id_transaccion = $this->get_data_chat_by_id_transaccion($data); 
        $data_chat_id_transaccion= $data_chat_id_transaccion[0]; 

        $data_emisor = $this->datosUserGeneral([
            'uid' => $data['uid'],
            'empresa' => $data['empresa']
        ]);

        $data_receptor=[]; 
        $receptor_vendedor=0; 
        
        if($data_chat_id_transaccion["uid_comprador"]== $data["uid"]){ //el receptor es el comprador 
            $data_receptor = $this->datosUserGeneral([
                'uid' => $data_chat_id_transaccion['uid_vendedor'],
                'empresa' => $data_chat_id_transaccion['empresa_vendedor']
            ]);
            $receptor_vendedor=1; 
        }else if($data_chat_id_transaccion["uid_vendedor"]== $data["uid"] ) {//el receptor es el vendedor 
            $data_receptor = $this->datosUserGeneral([
                'uid' => $data_chat_id_transaccion['uid_comprador'],
                'empresa' => $data_chat_id_transaccion['empresa_comprador']
            ]);
        }


        if($data_chat_id_transaccion["tipo"]=="1"){//es producto normal
            // if($data_chat_id_transaccion["visualizado"]=="0"){
                $this->htmlEmaienvio_mensaje_chat($data_receptor["data"], $data_emisor["data"], $receptor_vendedor); 
            // }
           
        }else if($data_chat_id_transaccion["tipo"]=="2"){ //es subasta
             // if($data_chat_id_transaccion["visualizado"]=="0"){
                $this->htmlEmaienvio_mensaje_chat_subasta($data_receptor["data"], $data_emisor["data"], $receptor_vendedor); 
            // } 

        }




    }



    function get_data_chat_by_id_transaccion(Array $data){
        parent::conectar();  
        $mensajes_chat = parent::consultaTodo("SELECT pt.id, chat.imagen, chat.uid as 'uid_emisor', chat.empresa as 'empresa_emisor', chat.visualizado, pt.id_carrito, pt.uid_comprador, pt.empresa_comprador, pt.uid_vendedor, pt.empresa as 'empresa_vendedor', chat.mensaje, pt.tipo FROM chat inner join productos_transaccion as pt on chat.id_transaccion = pt.id where pt.id  = '$data[id_transaccion]';");
        parent::cerrar();
        return $mensajes_chat;
    
}

    function datosUserGeneral( Array $data ) {
        $nasbifunciones = new Nasbifunciones();
        $result = $nasbifunciones->datosUser( $data );
        unset($nasbifunciones);
        return $result;
    }





    public function htmlEmaienvio_mensaje_chat(Array $data_receptor, Array $data_emisor, Int $receptor_vendedor ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_receptor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo3.html");
        $html = str_replace("{{trans155_brand}}",$json->trans155_brand, $html);
        $html = str_replace("{{trans156}}",$json->trans156, $html);
        $html = str_replace("{{signo_admiracion_open}}",$json->signo_admiracion_open, $html);
        $html = str_replace("{{trans45}}",$json->trans45, $html);
        $html = str_replace("{{trans46}}",$json->trans46, $html);

        if($receptor_vendedor == 1){
            $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        }else{
            $html = str_replace("{{link_to_ventas}}",$json->link_to_compras, $html);
        }
       
        
        $html = str_replace("{{trans257}}",$json->trans257, $html);
        $html = str_replace("{{trans258}}",$json->trans258, $html);
        $html = str_replace("{{trans259}}",$json->trans259, $html);


        $html = str_replace("{{nombre_comprador}}",$data_emisor['nombre'], $html);
        $html = str_replace("{{nombre_usuario}}",$data_receptor['nombre'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_receptor["uid"]."&act=0&em=".$data_receptor["empresa"], $html); 

        $para      = $data_receptor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $json->trans128_." ".$data_emisor["nombre"];
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
        
    }

    function htmlEmaienvio_mensaje_chat_subasta(Array $data_receptor, Array $data_emisor, Int $receptor_vendedor ){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/".$data_receptor["idioma"].".json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo23nuevapregunta.html");
        $html = str_replace("{{trans26_brand}}",$json->trans26_brand, $html);
        $html = str_replace("{{trans44_brand}}",$json->trans44_brand, $html);
        $html = str_replace("{{trans45}}",$json->trans45, $html);
        $html = str_replace("{{trans46}}",$json->trans46, $html);
        $html = str_replace("{{foto_producto_nueva_pregunta_brand}}",$json->foto_producto_nueva_pregunta_brand, $html);
        $mensaje_asuunto="vacio"; 

        if($receptor_vendedor == 1){
            $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
            $mensaje_asuunto = $json->trans129_." ".$data_emisor["nombre"]; 
        }else{
            $html = str_replace("{{link_to_ventas}}",$json->link_to_compras, $html);
            $mensaje_asuunto = $json->trans130_." ".$data_emisor["nombre"]; 
        }
    
        
        $html = str_replace("{{nombre_comprador}}",$data_emisor['nombre'], $html);
        $html = str_replace("{{nombre_usuario}}",$data_receptor['nombre'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$data_receptor["uid"]."&act=0&em=".$data_receptor["empresa"], $html); 

        $para      = $data_receptor['correo'] . ', felixespitia@gmail.com';
        $mensaje1   = $html;
        $titulo    = $mensaje_asuunto;
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'From: info@nasbi.com' . "\r\n";
        //$dataArray = array("para"=>$para, "titulo"=>$titulo, "mensaje1"=>$mensaje1, "cabeceras"=> $cabeceras);
        $dataArray = array("email"=>$para, "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
        return $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);

    }


}
?>