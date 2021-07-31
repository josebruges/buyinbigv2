<?php
require 'nasbifunciones.php';

class EmailsTesters extends Conexion
{

    protected $SCHEMA_DATA = array(
        "id_correo"    => 0,
        "titulo_email" => "LOTE DE CORREOS - NASBI.COM",
        "path_email"    => "index.html",

        "uid"          => 1000,
        "empresa"      => 1,
        "md5"          => "de6d3fafcbc094a81d4deed609319a1b",
        "nombre"       => "TIENDA DEPORTIVA MAYORISTA SANTA MARTA",
        "razon_social" => "TIENDA STORE SANTA MARTA",
        "correo"       => "dev.nasbi@gmail.com, qa.nasbi@gmail.com, auxiliar.nasbi@hotmail.com, auxiliar.nasbi@gmail.com, nayivernasbi@hotmail.com",
        
        "CategoryID" => 120,
        "categoria" => 120,

        "id" => 1,
        "id_producto" => 1,
        "id_subasta"  => 1,
        "tipoSubasta" => 0,
        "moneda" => "Nasbigold",
        "precio" => "1000000",
        "direccion" => "742 Evergreen Terrace - Av. siempre viva springfield",
        "ciudad" => "Santa Marta",
        "telefono" => "3105323111",
        
        "tipo_envio" => 2,
        "tipo_envio_nombre" => "Aliado logistico TCC",
        "tipo_envio_numero_guia" => "827384656737",

        "titulo"       => "Sneaker Dior-ID",
        "foto_portada" => "https://media.dior.com/img/es_sam/sku/couture/KCK309RTN_S59K_T425X?imwidth=460",

        "foto" => "https://whatsos.com.co/Archivos/Avatar.png",
        "declino_respuesta_comprador_descripcion" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.",
        "descripcion_generar" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.",

        "producto" => array(
            "id"           => 1,
            "titulo"       => "Sneaker Dior-ID",
            "foto_portada" => "https://media.dior.com/img/es_sam/sku/couture/KCK309RTN_S59K_T425X?imwidth=460"
        ),
        "fecha_inicio" => 0,
        "fecha_fin"    => 0,

        "data_subasta" => array(
            "id"           => 1,
            "fecha_inicio" => 0,
            "fecha_fin"    => 0
        )
    );

	function __construct()
	{
    	$fecha_inicio               = new DateTime("2021-06-01 09:00:00");
		$this->SCHEMA_DATA['data_subasta']['fecha_inicio'] = $fecha_inicio->getTimestamp()*1000;

		$fecha_fin               = new DateTime("2021-06-02 09:00:00");
		$this->SCHEMA_DATA['data_subasta']['fecha_fin'] = $fecha_fin->getTimestamp()*1000;

		$this->SCHEMA_DATA['fecha_inicio'] = $fecha_inicio->getTimestamp()*1000;
		$this->SCHEMA_DATA['fecha_fin'] = $fecha_fin->getTimestamp()*1000;
	}

	function campania_master_send( Array $data ){
		return array(
			"plantilla_registro" => $this->plantilla_registro($data),
			"promo_correo" => $this->promo_correo($data),
			"dar_de_alta" => $this->dar_de_alta($data),
			"plantilla_venta_tradicional" => $this->plantilla_venta_tradicional($data),
			"correos_registro_vender_revision" => $this->correos_registro_vender_revision($data),
			"plantillas_product_revision" => $this->plantillas_product_revision($data),
			"compra_tradiccional" => $this->compra_tradiccional($data),
            "plantilla_venta_por_subasta" => $this->plantilla_venta_por_subasta($data)
		);

	}

	// PACK EMAILS #1 - plantilla_registro
	function plantilla_registro( Array $data ){
		return array(
			"sendRegistroPart1"   => $this->sendRegistroPart1(),
			"sendRegistroPart2"   => $this->sendRegistroPart2(),
			"sendRegistroPart3"   => $this->sendRegistroPart3(),
			"sendRegistroPart4_1" => $this->sendRegistroPart4_1(),
			"sendRegistroPart4_2" => $this->sendRegistroPart4_2(),
			"sendRegistroPart5"   => $this->sendRegistroPart5()
		);
	}
	function sendRegistroPart1(){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo1codigoseguridad.html");

		
		$this->SCHEMA_DATA['id_correo']    = 1;
		$this->SCHEMA_DATA['titulo_email'] = $json->trans99_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_registro/correo1codigoseguridad.html";


        $html = str_replace("{{trans_brand}}", $json->trans_brand, $html);
        $html = str_replace("{{trans01_}}", $json->trans01_, $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html); // Excepción

        $html = str_replace("{{trans03_}}", $json->trans03_, $html);
        $html = str_replace("{{trans04_}}", $this->SCHEMA_DATA['md5'], $html);
        $html = str_replace("{{trans05_}}", $json->trans05_, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=". $this->SCHEMA_DATA['uid'] ."&act=0&em=1", $html); // Excepción

        $html = str_replace("{{link_facebook_nasbi}}", $json->to_facebook_, $html); // Excepción
        $html = str_replace("{{link_instagram_nasbi}}", $json->to_instagram_, $html); // Excepción
        $html = str_replace("{{link_youtube_nasbi}}", $json->to_youtube_, $html); // Excepción
        $html = str_replace("{{link_in_nasbi}}", $json->to_in_, $html); // Excepción

        return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function sendRegistroPart2(){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo2registrocompletado.html");

		$this->SCHEMA_DATA['id_correo'] = 2;
		$this->SCHEMA_DATA['titulo_email'] = ucfirst( $this->SCHEMA_DATA['razon_social'] )." ".$json->trans100_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_registro/correo2registrocompletado.html";


        $html = str_replace("{{trans_brand}}", $json->trans_brand, $html);
        $html = str_replace("{{trans_01_brand}}", $json->trans_01_brand, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html); // Excepción
        
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

        $html = str_replace("{{link_facebook_nasbi}}", $json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}", $json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}", $json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}", $json->to_in_, $html);

        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=" . $this->SCHEMA_DATA['uid'] . "&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
    function sendRegistroPart3(){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo3tutiendaoficial.html");
        
    	$this->SCHEMA_DATA['id_correo'] = 3;
    	$this->SCHEMA_DATA['titulo_email'] = $json->trans101_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_registro/correo3tutiendaoficial.html";

        $html = str_replace("{{trans_03_brand}}", $json->trans_03_brand, $html);
        $html = str_replace("{{trans19_}}", $json->trans19_, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html);
        
        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);

        $html = str_replace("{{trans20_}}", $json->trans20_, $html);
        $html = str_replace("{{trans21_}}", $json->trans21_, $html);
        $html = str_replace("{{trans22_}}", $json->trans22_, $html);
        $html = str_replace("{{trans23_}}", $json->trans23_, $html);
        $html = str_replace("{{trans67_}}", $json->trans67_, $html);
        $html = str_replace("{{link_a_escuela}}", "https://nasbi.com/content/escuela-vendedores.php", $html);
        $html = str_replace("{{link_a_vender}}", "https://nasbi.com/content/vender.php", $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{link_facebook_nasbi}}", $json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}", $json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}", $json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}", $json->to_in_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 
       
    	return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function sendRegistroPart4_1(){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo4tuprimerproducto.html");
        

    	$this->SCHEMA_DATA['id_correo'] = 4;
    	$this->SCHEMA_DATA['titulo_email'] = $json->trans104_ . " (PRODUCTO) ";
    	$this->SCHEMA_DATA['path_email']    = "plantilla_registro/correo4tuprimerproducto.html";

        $CategoryID = $this->SCHEMA_DATA['CategoryID'];
        $selectcategoria = "SELECT * FROM buyinbig.categorias WHERE CategoryID = $CategoryID";
        
        parent::conectar();
        $categorias        = parent::consultaTodo($selectcategoria);
        parent::cerrar();

        $categoria_product = $this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product = $categoria_product[0];

        
        $html = str_replace("{{trans09}}","", $html);
        $html = str_replace("{{codigo_subasta}}","", $html);
        $html = str_replace("{{trans10}}", "", $html);
        $html = str_replace("{{tipo_publicacion}}","", $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}","", $html);
        $html = str_replace("{{fecha_inicio}}","", $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);

        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html);
      
        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);

        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);

        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 
       
    	return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function sendRegistroPart4_2(){


        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo4tuprimerproducto.html");
        

    	$this->SCHEMA_DATA['id_correo'] = 4;
    	$this->SCHEMA_DATA['titulo_email'] = $json->trans104_ . " (SUBASTAS) ";
    	$this->SCHEMA_DATA['path_email']    = "plantilla_registro/correo4tuprimerproducto.html";

        $CategoryID = $this->SCHEMA_DATA['CategoryID'];
        $selectcategoria = "SELECT * FROM buyinbig.categorias WHERE CategoryID = $CategoryID";
        
        parent::conectar();
        $categorias        = parent::consultaTodo($selectcategoria);
        parent::cerrar();

        $categoria_product = $this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product = $categoria_product[0];

        $fecha_inicio_product = date('m/d/Y H:i:s:m', $this->SCHEMA_DATA['data_subasta']['fecha_inicio']);
        $fecha_fin_product    = date('m/d/Y H:i:s:m', $this->SCHEMA_DATA['data_subasta']['fecha_fin']);

        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA['data_subasta']['id'], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$this->SCHEMA_DATA['id_subasta'], $html);

        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html);
      
        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);

        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);

        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 
       
    	return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function sendRegistroPart5(){
    	//recuperar pass
        
        $fecha = getdate();

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_registro/correo5hasolvidadotucontrasena.html");


        $this->SCHEMA_DATA['id_correo']    = 5;
    	$this->SCHEMA_DATA['titulo_email'] = ucfirst($this->SCHEMA_DATA['razon_social'])." ".$json->trans102_;
    	$this->SCHEMA_DATA['path_email']   = "plantilla_registro/correo5hasolvidadotucontrasena.html";


        $html = str_replace("{{trans01_brand}}", $json->trans01_brand, $html); 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans02}}", $json->trans02, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html);

        $html = str_replace("*!",'', $html);
        $html = str_replace("!*",'', $html);
        
        $html = str_replace("{{trans03}}", $json->trans03, $html);
        $html = str_replace("{{trans04}}", $json->trans04, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{link_restablecer_pass_em}}", "https://nasbi.com/content/nueva-pass-em.php?t=".$this->SCHEMA_DATA['md5'], $html);
        
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }


    // PACK EMAILS #2 - registro_vender
	function sendRegistroVender(){
		return array(
			"sendRegistroVenderPart1"   => $this->sendRegistroVenderPart1()
		);
	}
	function sendRegistroVenderPart1(){

		$json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/registro_vender/registroExitoso.html");

		
		$this->SCHEMA_DATA['id_correo']    = 6;
		$this->SCHEMA_DATA['titulo_email'] = $json->trans171_;
		$this->SCHEMA_DATA['path_email']    = "registro_vender/registroExitoso.html";


        $html = str_replace("{{trans166_}}", $json->trans166_, $html);
        $html = str_replace("{{trans167_}}", $json->trans167_, $html);
        $html = str_replace("{{trans168_}}", $json->trans168_, $html);
        $html = str_replace("{{trans169_}}", $json->trans169_, $html);
        $html = str_replace("{{trans170_}}", $json->trans170_, $html);
        $html = str_replace("{{trans_03_brand}}", $json->trans_03_brand, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);

        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=". $this->SCHEMA_DATA["uid"] ."&act=0&em=". $this->SCHEMA_DATA["empresa"], $html);

        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
	}


    // PACK EMAILS #3 - promo_correo
	function promo_correo( Array $data ){
		return array(
			"sendPromoCorreoPart1"   => $this->sendPromoCorreoPart1()
		);
	}
	function sendPromoCorreoPart1(){

		$json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/promo_correo/correopromopasstrans.html");

		
		$this->SCHEMA_DATA['id_correo']    = 7;
		$this->SCHEMA_DATA['titulo_email'] = $json->trans193_;
		$this->SCHEMA_DATA['path_email']    = "promo_correo/correopromopasstrans.html";

        $valor = "5.000";

        $html = str_replace("{{trans190_}}", $json->trans190_, $html);
        $html = str_replace("{{trans191_}}", $json->trans191_, $html);
        $html = str_replace("{{trans192_}}", $json->trans192_, $html);
        $html = str_replace("{{valor}}", $valor, $html);
        $html = str_replace("{{codigoreferido}}", $this->SCHEMA_DATA["uid"], $html);
        $html = str_replace("{{link_to_wallet}}", $json->link_to_wallet, $html);
        $html = str_replace("{{trans195_}}", $json->trans195_, $html);
        $html = str_replace("{{trans196_}}", $json->trans196_, $html);
        $html = str_replace("{{trans197_}}", $json->trans197_, $html);
        $html = str_replace("{{trans198_}}", $json->trans198_, $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
	}

	// PACK EMAILS #4 - dar_de_baja & dar_de_alta
	function dar_de_alta( Array $data ){
		return array(
			"sendDarDeBaja"    => $this->sendDarDeBaja(),
			"sendDarDarDeAlta" => $this->sendDarDarDeAlta()
		);
	}
    function sendDarDeBaja(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/dar_de_baja/correo1dar_baja.html");

		
		$this->SCHEMA_DATA['id_correo']     = 8;
		$this->SCHEMA_DATA['titulo_email']  = ucfirst($this->SCHEMA_DATA['razon_social'])." ".$json->trans157_;
		$this->SCHEMA_DATA['path_email']    = "dar_de_baja/correo1dar_baja.html";
        
        $html = str_replace("{{trans01_brand}}", $json->trans01_brand, $html); 
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{trans02}}", $json->trans02, $html);
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['razon_social'], $html);

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

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function sendDarDarDeAlta(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/dar_de_baja/correo2dar_alta.html");

		
		$this->SCHEMA_DATA['id_correo']     = 9;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans159_;
		$this->SCHEMA_DATA['path_email']    = "dar_de_baja/correo2dar_alta.html";

        $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['razon_social']), $html);
        
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
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }

    // PACK EMAILS #5 - plantilla_venta_tradicional
	function plantilla_venta_tradicional( Array $data ){
		return array(
			"plantillaVentaTradicionalPart1_1" => $this->plantillaVentaTradicionalPart1_1(),
			"plantillaVentaTradicionalPart1_2" => $this->plantillaVentaTradicionalPart1_2(),
			"plantillaVentaTradicionalPart2" => $this->plantillaVentaTradicionalPart2(),
			"plantillaVentaTradicionalPart3" => $this->plantillaVentaTradicionalPart3(),
			"htmlEmailedicionexposicion_cla_to_premium" => $this->htmlEmailedicionexposicion_cla_to_premium(),
			"htmlEmail_confirmo_compra_to_despacho" => $this->htmlEmail_confirmo_compra_to_despacho(),
			"htmlEmail_corroeo_envio_guia" => $this->htmlEmail_corroeo_envio_guia(),
			"htmlEmail_confirmo_vendedor_entrega_envio" => $this->htmlEmail_confirmo_vendedor_entrega_envio(),
			"htmlEmail_declino__entrega_vendedor_envio" => $this->htmlEmail_declino__entrega_vendedor_envio(),
			"htmlEmail_declino__entrega_vendedor_envio_caso_especial" => $this->htmlEmail_declino__entrega_vendedor_envio_caso_especial(),
			"htmlEmail_se_congelo_pago" => $this->htmlEmail_se_congelo_pago()
		);
	}
    function plantillaVentaTradicionalPart1_1(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo1.html");

		
		$this->SCHEMA_DATA['id_correo']     = 10;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans105_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/ventatradiccionalcorreo1.html";

        $categorias        = $this->get_categoria_product(); 
        $categoria_product = $this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product = $categoria_product[0];

        $html = str_replace("{{trans09}}","", $html);
        $html = str_replace("{{codigo_subasta}}","", $html);
        $html = str_replace("{{trans10}}", "", $html);
        $html = str_replace("{{tipo_publicacion}}","", $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}","", $html);
        $html = str_replace("{{fecha_inicio}}","", $html);
        $html = str_replace("{{trans87_}}",$json->trans243, $html);
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_subasta'], $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']["titulo"], $html);
      
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
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function plantillaVentaTradicionalPart1_2(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo1.html");

		
		$this->SCHEMA_DATA['id_correo']     = 10;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans105_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/ventatradiccionalcorreo1.html";

        $categorias        = $this->get_categoria_product(); 
        $categoria_product = $this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product = $categoria_product[0];

        $fecha_inicio_product = date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_inicio"]);
        $fecha_fin_product    = date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_fin"]);

        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}","", $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$this->SCHEMA_DATA['id_producto'], $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']["titulo"], $html);
      
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
		$html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
		$html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);

       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function plantillaVentaTradicionalPart2(){

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/ventatradiccionalcorreo2.html");

		
		$this->SCHEMA_DATA['id_correo']     = 11;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans107_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/ventatradiccionalcorreo2.html";

        if($this->SCHEMA_DATA["tipoSubasta"] != 0){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        }
        
      
        $html = str_replace("{{trans_07_icon_subasta_brand}}", $json->trans_07_icon_subasta_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{foto_product}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
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

        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function plantillaVentaTradicionalPart3(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo3.html");

		
		$this->SCHEMA_DATA['id_correo']     = 12;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans107_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo3.html";

        if($this->SCHEMA_DATA["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        }
        
        $html = str_replace("{{trans130_brand}}", $json->trans130_brand, $html);
        $html = str_replace("{{trans131_brand}}", $json->trans131_brand, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans97_}}",$json->trans97_, $html);
        $html = str_replace("{{producto_brand}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
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
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailedicionexposicion_cla_to_premium(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo4.html");

		
		$this->SCHEMA_DATA['id_correo']     = 13;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans107_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo4.html";

        if($this->SCHEMA_DATA["tipoSubasta"] != "0"){
            $html = str_replace("{{link_to_product}}","", $html);
        }else{
            $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        }
        
        $html = str_replace("{{trans113_brand}}", $json->trans113_brand, $html);
        $html = str_replace("{{trans129_brand}}", $json->trans129_brand, $html);
        $html = str_replace("{{trans95_}}", $json->trans95_, $html);
        $html = str_replace("{{trans96_}}", $json->trans96_, $html);
        $html = str_replace("{{trans97_}}", $json->trans97_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        

        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{producto_brand}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
    
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmail_confirmo_compra_to_despacho(){
        
		$json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
		$html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo5.html");

		
		$this->SCHEMA_DATA['id_correo']     = 14;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans117_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo5.html";

		if($this->SCHEMA_DATA["moneda"] == "Nasbigold" || $this->SCHEMA_DATA["moneda"] == "nasbigold"){
			$this->SCHEMA_DATA["moneda"] = "Nasbichips"; 
		}else if($this->SCHEMA_DATA["moneda"] == "Nasbiblue" || $this->SCHEMA_DATA["moneda"] == "nasbiblue"){
			$this->SCHEMA_DATA["moneda"] = "Bono(s) de descuento"; 
		}

		$html = str_replace("{{nombre_comprador}}",$this->SCHEMA_DATA['nombre'], $html);
		$html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
		$html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
		$html = str_replace("{{trans123}}",$json->trans123, $html);
		$html = str_replace("{{trans124}}",$json->trans124, $html);

		$html = str_replace("{{precio_producto}}", $this->maskNumber($this->SCHEMA_DATA["precio"]), $html);
		$html = str_replace("{{moneda}}",$this->SCHEMA_DATA["moneda"], $html);


		$html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
		$html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);

		$html = str_replace("{{trans125}}",$json->trans125, $html);
		$html = str_replace("{{trans126}}",$json->trans126, $html);
		$html = str_replace("{{trans127}}",$json->trans127, $html);
		$html = str_replace("{{trans110}}",$json->trans110, $html);

		$html = str_replace("{{trans128}}",$json->trans128, $html);

		$html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
		$html = str_replace("{{trans128}}",$json->trans128, $html);
		$html = str_replace("{{trans43}}",$json->trans43, $html);



		$html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
		$html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
		$html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
		$html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
		$html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
		$html = str_replace("{{trans06_}}",$json->trans06_, $html);
		$html = str_replace("{{trans07_}}",$json->trans07_, $html);
		$html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmail_corroeo_envio_guia(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo6.html");

		
		$this->SCHEMA_DATA['id_correo']     = 15;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans119_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo6.html";

        if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
            $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
        }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
            $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
        }

        $array_tipos_envio=[];

        for ($i=0; $i < 3; $i++) { 
            $array_tipos_envio[$i]["id"]     = $i + 1;
            $mname                           = "trans118_".strval($i+1);
            $array_tipos_envio[$i]["nombre"] = $json->$mname;

        }


        $tipo_envio = $this->filter_by_value($array_tipos_envio,"id", $this->SCHEMA_DATA["tipo_envio"])[0];

        $html = str_replace("{{nombre_recibe}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);


        $html = str_replace("{{trans107}}",$json->trans107, $html);
        $html = str_replace("{{trans112}}",$json->trans112, $html);
        $html = str_replace("{{trans113}}",$json->trans113, $html);


        $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{tipo_envio}}",$this->SCHEMA_DATA['tipo_envio_nombre'], $html);
        $html = str_replace("{{trans114}}",$json->trans114, $html);
        $html = str_replace("{{trans115}}",$json->trans115, $html);
        $html = str_replace("{{numero_guia}}",$this->SCHEMA_DATA['tipo_envio_numero_guia'], $html);
        $html = str_replace("{{trans116}}",$json->trans116, $html);
        $html = str_replace("{{trans117}}",$json->trans117, $html);
        $html = str_replace("{{direccion}}",$this->SCHEMA_DATA["direccion"], $html);




        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{trans128}}",$json->trans128, $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans17}}",$json->trans117, $html);
        $html = str_replace("{{direccion}}",$json->trans117, $html);
        $html = str_replace("{{trans118}}",$json->trans118, $html);

        $html = str_replace("{{ciudad}}",$this->SCHEMA_DATA["ciudad"], $html);
        $html = str_replace("{{trans119}}",$json->trans119, $html);

        $html = str_replace("{{telefono_contacto}}",$this->SCHEMA_DATA["telefono"], $html);
        $html = str_replace("{{trans120}}","", $html);

        $html = str_replace("{{fecha_est_llegada}}","", $html);

        $html = str_replace("{{trans121}}",$json->trans121, $html);
        $html = str_replace("{{trans122}}",$json->trans122, $html);
        $html = str_replace("{{trans111}}",$json->trans111, $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
	function htmlEmail_confirmo_vendedor_entrega_envio(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo7.html");

		
		$this->SCHEMA_DATA['id_correo']     = 16;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans122_." ".ucfirst($this->SCHEMA_DATA['nombre']);
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo7.html";
    
        if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
            $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
        }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
            $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
        }
    
        if($this->SCHEMA_DATA["foto"] == "" || $this->SCHEMA_DATA["empresa"] == "0"){
            $this->SCHEMA_DATA["foto"] == $json->foto_por_defecto_user;    
        }
        $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{trans107}}",$json->trans107, $html);
        $html = str_replace("{{trans108}}",$json->trans108, $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{trans109}}",$json->trans109, $html);
        $html = str_replace("{{trans110}}",$json->trans110, $html);
        
        $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($this->SCHEMA_DATA['precio']), $html);
        $html = str_replace("{{moneda}}", $this->SCHEMA_DATA['moneda'], $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
        $html = str_replace("{{foto_vendedor}}", $this->SCHEMA_DATA["foto"], $html);
        
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{trans111}}",$json->trans111, $html);
       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmail_declino__entrega_vendedor_envio(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo8.html");

		
		$this->SCHEMA_DATA['id_correo']     = 17;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans124_." ".$this->SCHEMA_DATA["nombre"]." ".$json->trans125_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo8.html";
        
        
        $html = str_replace("{{trans105_brand}}",$json->trans105_brand, $html);
        $html = str_replace("{{trans24}}",$json->trans24, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{trans106}}",$json->trans106, $html);
        $html = str_replace("{{trans100}}",$json->trans100, $html);
        $html = str_replace("{{trans101}}",$json->trans101, $html);
        $html = str_replace("{{producto_mal_estado_brand}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{respuesta_comprador}}",$this->SCHEMA_DATA["declino_respuesta_comprador_descripcion"], $html);
        

        $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans98}}",$json->trans98, $html);
        $html = str_replace("{{trans104}}",$json->trans104, $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmail_declino__entrega_vendedor_envio_caso_especial(){
       $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
       $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo9.html");

		
		$this->SCHEMA_DATA['id_correo']     = 18;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans124_." ".$this->SCHEMA_DATA["nombre"]." ".$json->trans125_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo9.html";
       
       
       $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
       $html = str_replace("{{nombre_comprador}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
       $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
       $html = str_replace("{{trans100}}",$json->trans100, $html);
       $html = str_replace("{{trans101}}",$json->trans101, $html);
       $html = str_replace("{{trans19}}",$json->trans19, $html);
       $html = str_replace("{{trans102}}",$json->trans102, $html);
       $html = str_replace("{{trans103}}",$json->trans103, $html);
       $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
       $html = str_replace("{{trans98}}",$json->trans98, $html);
       $html = str_replace("{{trans24}}",$json->trans24, $html);
       $html = str_replace("{{trans104}}",$json->trans104, $html);
       
       $html = str_replace("{{descripcion_queja}}",$this->SCHEMA_DATA["declino_respuesta_comprador_descripcion"], $html);
       
       $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
       $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);

      
   
       $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
       $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
       $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
       $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
       $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
       $html = str_replace("{{trans06_}}",$json->trans06_, $html);
       $html = str_replace("{{trans07_}}",$json->trans07_, $html);
       $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmail_se_congelo_pago(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_tradicional/Ventatradiccionalcorreo10.html");


		
		$this->SCHEMA_DATA['id_correo']     = 19;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans126_." ".$this->SCHEMA_DATA['producto']['titulo']." ".$json->trans127_;
		$this->SCHEMA_DATA['path_email']    = "plantilla_venta_tradicional/Ventatradiccionalcorreo10.html";

        
        if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
            $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
        }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
            $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
        }
        

        $html = str_replace("{{trans93_brand}}",$json->trans93_brand, $html);
        $html = str_replace("{{trans94}}",$json->trans94, $html);
        $html = str_replace("{{trans95}}",$json->trans95, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{nombre_comprador}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{trans19}}",$json->trans19, $html);
        $html = str_replace("{{trans96}}",$json->trans96, $html);
        $html = str_replace("{{trans34_valor}}",$this->maskNumber($this->SCHEMA_DATA['precio']), $html);
        $html = str_replace("{{moneda_unidad}}",$this->SCHEMA_DATA['moneda'], $html);
        
        $html = str_replace("{{trans98}}",$json->trans98, $html);
        $html = str_replace("{{trans24}}",$json->trans24, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);

       
    
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }


    // PACK EMAILS #6 - correos_registro_vender_revision
	function correos_registro_vender_revision( Array $data ){
		return array(
			"html_correo_rechazo_registro_vender" => $this->html_correo_rechazo_registro_vender(),
			"html_envio_correo_exitoso_revision" => $this->html_envio_correo_exitoso_revision()
		);
	}
    function html_correo_rechazo_registro_vender(){
    
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/correos_registro_vender_revision/correoNoexitosoregistro.html");


		
		$this->SCHEMA_DATA['id_correo']     = 20;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans185_;
		$this->SCHEMA_DATA['path_email']    = "correos_registro_vender_revision/correoNoexitosoregistro.html";

        $html_campos_rechazados= "";

        $html_campos_rechazados.= '<br> 1. Dirección, Justificación de rechazo: '.$this->SCHEMA_DATA['descripcion_generar'].'<br>';
        $html_campos_rechazados.= '<br> 2. Teléfono, Justificación de rechazo: '.$this->SCHEMA_DATA['descripcion_generar'].'<br>';
        $html_campos_rechazados.= '<br> 3. Actividad económica, Justificación de rechazo: '.$this->SCHEMA_DATA['descripcion_generar'].'<br>';

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

        $html = str_replace("{{nombre_user}}", $this->SCHEMA_DATA["nombre"] , $html);
        $html = str_replace("{{link_whatsapp}}", $json->link_whatsapp  , $html);

        $html = str_replace("{{items_rechazados}}", $html_campos_rechazados , $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function html_envio_correo_exitoso_revision(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/correos_registro_vender_revision/revision_exitosa.html");


		
		$this->SCHEMA_DATA['id_correo']     = 21;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans194_;
		$this->SCHEMA_DATA['path_email']    = "correos_registro_vender_revision/revision_exitosa.html";


        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans186_}}", $json->trans186_, $html);
        $html = str_replace("{{trans187_}}", $json->trans187_, $html);
        $html = str_replace("{{trans188_}}", $json->trans188_, $html);
        $html = str_replace("{{trans189_}}", $json->trans189_, $html);
        $html = str_replace("{{link_to_vender}}", $json->link_to_vender, $html);
        $html = str_replace("{{link_tuto_pago}}", $json->link_tuto_pago, $html);

        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }


    // PACK EMAILS #7 - plantillas_product_revision
	function plantillas_product_revision( Array $data ){
		return array(
			"htmlEmailpublicacion_product_revision_1_1" => $this->htmlEmailpublicacion_product_revision_1_1(),
			"htmlEmailpublicacion_product_revision_1_2" => $this->htmlEmailpublicacion_product_revision_1_2(),
			"htmlEmailpublicacion_subasta_revision" => $this->htmlEmailpublicacion_subasta_revision(),
			"htmlEmailpublicacion_subasta_premium_revision" => $this->htmlEmailpublicacion_subasta_premium_revision(),
			"htmlEmailprimer_producto_empresa_revision_1_1" => $this->htmlEmailprimer_producto_empresa_revision_1_1(),
			"htmlEmailprimer_producto_empresa_revision_1_2" => $this->htmlEmailprimer_producto_empresa_revision_1_2(),
			"htmlEmailactivacion_producto" => $this->htmlEmailactivacion_producto(),
			"htmlEmailrechazo_producto" => $this->htmlEmailrechazo_producto()
		);
	}
    function htmlEmailpublicacion_product_revision_1_1(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/corre1revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 22;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans153_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/corre1revision.html";

		
        $categorias=$this->get_categoria_product(); 
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product=$categoria_product[0];

        
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
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id'], $html);
        
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);

        $html = str_replace("{{trans244}}", $json->trans244, $html);
        
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']["titulo"], $html);

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
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailpublicacion_product_revision_1_2(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/corre1revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 22;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans153_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/corre1revision.html";

		
        $categorias=$this->get_categoria_product(); 
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product=$categoria_product[0];

        $fecha_inicio_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_inicio"]);
        $fecha_fin_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_fin"]);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id_subasta"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}",$fecha_fin_product, $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}","", $html);
        $html = str_replace("{{trans146_}}",$json->trans146_, $html);
        $html = str_replace("{{trans87_}}",$json->trans87_, $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$this->SCHEMA_DATA['id_subasta'], $html);
    

        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans_06_icon_subasta_brand}}", $json->trans_06_icon_subasta_brand, $html);
        $html = str_replace("{{trans92_}}", $json->trans92_, $html);

        $html = str_replace("{{trans244}}", $json->trans244, $html);
        
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']["titulo"], $html);

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
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);


        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailpublicacion_subasta_revision(){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
            $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
            $categoria_product=$categoria_product[0];
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo2revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 23;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans114_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo2revision.html";
  
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        // $html = str_replace("{{trans89}}",$json->trans89, $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89_}}",$json->trans89_, $html);
        $html = str_replace("{{trans91_}}",$json->trans91_, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans88_}}",$json->trans88_, $html);
        
        
        
        $html = str_replace("{{producto_brand}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['producto']['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        

        $fecha_inicio_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_inicio"]);
        $fecha_fin_product   = date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_fin"]);


        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id_subasta"], $html);
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
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['producto']['id'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailpublicacion_subasta_premium_revision(){
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        parent::conectar();
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();    
        
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product=$categoria_product[0];


        $fecha_inicio_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_inicio"]);
        $fecha_fin_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA["fecha_fin"]);
        
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo3revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 24;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans114_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo3revision.html";
        
        $html = str_replace("{{trans_01_brand}}",$json->trans_01_brand, $html);
        $html = str_replace("{{trans86}}",$json->trans86, $html);
        $html = str_replace("{{trans91}}",$json->trans91, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        
        $html = str_replace("{{signo_admiracion_open}}", $json->signo_admiracion_open, $html);
        
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans09_}}",$json->trans09_, $html);
        $html = str_replace("{{trans87}}",$json->trans87, $html);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id_subasta"], $html);
        

        $html = str_replace("{{trans88}}",$json->trans88, $html);
        $html = str_replace("{{trans89}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans145_}}",$json->trans145_, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans147_}}",$json->trans147_, $html);

        $html = str_replace("{{foto_producto_subasta_premiun_brand}}",$this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans92}}",$json->trans92, $html);
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        

        
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id_subasta"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);

       
        
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailprimer_producto_empresa_revision_1_1(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo4revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 24;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans104_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo4revision.html";
        
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        parent::conectar();
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
     
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product=$categoria_product[0];

    
        $html = str_replace("{{trans09}}","", $html);
        $html = str_replace("{{codigo_subasta}}","", $html);
        $html = str_replace("{{trans10}}", "", $html);
        $html = str_replace("{{tipo_publicacion}}","", $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}","", $html);
        $html = str_replace("{{fecha_inicio}}","", $html);
        $html = str_replace("{{link_to_producto}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        
        $html = str_replace("{{trans147_}}", $json->trans147_, $html);
        $html = str_replace("{{trans148_}}", $json->trans148_, $html);

        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        
         
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailprimer_producto_empresa_revision_1_2(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo4revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 24;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans104_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo4revision.html";
        
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        parent::conectar();
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
     
        $categoria_product=$this->filter_by_value($categorias, 'CategoryID', $this->SCHEMA_DATA['CategoryID']);
        $categoria_product=$categoria_product[0];

        
        $fecha_inicio_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA['fecha_inicio']);
        $fecha_fin_product= date('m/d/Y H:i:s:m', $this->SCHEMA_DATA['fecha_fin']);
        $html = str_replace("{{trans09}}",$json->trans09, $html);
        $html = str_replace("{{codigo_subasta}}",$this->SCHEMA_DATA["id_producto"], $html);
        $html = str_replace("{{trans10}}", $json->trans10, $html);
        $html = str_replace("{{tipo_publicacion}}", $json->trans87_, $html);
        $html = str_replace("{{fecha_fin}}","", $html);
        $html = str_replace("{{trans13}}","", $html);
        $html = str_replace("{{trans12}}",$json->trans12, $html);
        $html = str_replace("{{fecha_inicio}}",$fecha_inicio_product, $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$this->SCHEMA_DATA['id_subasta'], $html);
        
        $html = str_replace("{{trans16_brand}}", $json->trans16_brand, $html);
        $html = str_replace("{{trans05}}", $json->trans05, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        
        $html = str_replace("{{trans147_}}", $json->trans147_, $html);
        $html = str_replace("{{trans148_}}", $json->trans148_, $html);

        $html = str_replace("{{trans06}}", $json->trans06, $html);
        $html = str_replace("{{trans07}}", $json->trans07, $html);
        $html = str_replace("{{trans08}}", $json->trans08, $html);
        $html = str_replace("{{trans11}}",$json->trans11, $html);
     
      
        $html = str_replace("{{trans14}}",$json->trans14, $html);
        $html = str_replace("{{trans15}}",$json->trans15, $html);
        
         
        $html = str_replace("{{categoria_publicacion}}",$categoria_product["CategoryName"], $html);
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{titulo_producto}}", $this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{foto_producto}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
       
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailactivacion_producto(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo5activo_product.html");


		
		$this->SCHEMA_DATA['id_correo']     = 25;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans104_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo5activo_product.html";
        
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/nasbi-descuentos.php?sub=".$this->SCHEMA_DATA['id_producto'], $html);

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
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
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailrechazo_producto(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantillas_product_revision/correo6revision.html");


		
		$this->SCHEMA_DATA['id_correo']     = 26;
		$this->SCHEMA_DATA['titulo_email']  = $json->trans152_;
		$this->SCHEMA_DATA['path_email']    = "plantillas_product_revision/correo6revision.html";

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        $html = str_replace("{{trans152_}}", $json->trans152_, $html);
        $html = str_replace("{{trans151_}}", $json->trans151_, $html);
        
        
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans149_}}",$json->trans149_, $html);
        $html = str_replace("{{trans150_}}",$json->trans150_, $html);

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id_producto'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }


	// PACK EMAILS #8 - compra_tradiccional
	function compra_tradiccional( Array $data ){
		return array(
			"htmlEmailrespuesta_pregunta" => $this->htmlEmailrespuesta_pregunta(),
			"htmlEmail_comienza_compra" => $this->htmlEmail_comienza_compra(),
			"htmlEmaienvio_mensaje_chat" => $this->htmlEmaienvio_mensaje_chat(),
			"htmlEmail_corroeo_envio_guia_para_comprador" => $this->htmlEmail_corroeo_envio_guia_para_comprador(),
			"htmlEmail_confirmo_comprador_entrega_envio" => $this->htmlEmail_confirmo_comprador_entrega_envio(),
			"htmlEmail_declino__entrega_comprador_envio" => $this->htmlEmail_declino__entrega_comprador_envio(),
			"html_respuesta_nasbi_cliente" => $this->html_respuesta_nasbi_cliente()
		);
	}
	function htmlEmailrespuesta_pregunta(){
	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo1.html");

	    $this->SCHEMA_DATA['id_correo']     = 27;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans103_;
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo1.html";

	    $html = str_replace("{{nombre_producto}}", $this->SCHEMA_DATA['producto']["titulo"], $html);
	    $html = str_replace("{{trans164_brand}}", $json->trans164_brand, $html);
	    $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{trans163}}", $json->trans163, $html);
	    $html = str_replace("{{nombre_vendedor}}", $this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{trans165}}", $json->trans165, $html);
	    $html = str_replace("{{trans167}}", $json->trans167, $html);
	    $html = str_replace("{{trans06_}}", $json->trans06_, $html);
	    $html = str_replace("{{trans07_}}", $json->trans07_, $html);
	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}","", $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
	    $html = str_replace("{{trans166}}",$json->trans166, $html);
	    $html = str_replace("{{trans147}}",$json->trans147, $html);
	    $html = str_replace("{{lin_to_product}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['id'], $html);

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function htmlEmail_comienza_compra(){

	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo2.html");

	    $this->SCHEMA_DATA['id_correo']     = 28;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans116_." ".$this->SCHEMA_DATA['producto']['titulo'];
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo2.html";

	    if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
	        $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
	    }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
	        $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
	    }

	    if($this->SCHEMA_DATA["foto"] == "" || intval( $this->SCHEMA_DATA["empresa"] )== 0){
	        $this->SCHEMA_DATA["foto"] = $json->foto_por_defecto_user;    
	    }

	    $html = str_replace("{{trans157_brand}}",$json->trans157_brand, $html);
	    $html = str_replace("{{trans06}}", $json->trans06, $html);
	    $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{nombre_vendedor}}",$this->SCHEMA_DATA['nombre'], $html);


	    $html = str_replace("{{trans159}}", $json->trans159, $html);
	    $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
	    $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
	    $html = str_replace("{{trans158}}",$json->trans158, $html);

	    $html = str_replace("{{trans34_valor}}",$this->maskNumber($this->SCHEMA_DATA["precio"], 2) , $html);
	    $html = str_replace("{{trans35_unidad}}",$this->SCHEMA_DATA["moneda"], $html);

	    $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
	    $html = str_replace("{{trans160}}",$json->trans160, $html);
	    $html = str_replace("{{trans161_brand}}",$json->trans161_brand, $html);
	    $html = str_replace("{{trans162}}",$json->trans162, $html);
	    $html = str_replace("{{trans99}}",$json->trans99, $html);

	    $html = str_replace("{{direccion_vendedor}}",$this->SCHEMA_DATA["direccion"], $html);
	    $html = str_replace("{{telefono_vendedor}}",$this->SCHEMA_DATA["telefono"], $html);
	    $html = str_replace("{{ciudad_vendedor}}",$this->SCHEMA_DATA["ciudad"], $html);
	    $html = str_replace("{{foto_user}}",$this->SCHEMA_DATA["foto"], $html);


	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}","", $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function htmlEmaienvio_mensaje_chat(){
	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo3.html");

	    $this->SCHEMA_DATA['id_correo']     = 29;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans128_." ".$this->SCHEMA_DATA["nombre"];
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo3.html";

	    $html = str_replace("{{trans155_brand}}",$json->trans155_brand, $html);
	    $html = str_replace("{{trans156}}",$json->trans156, $html);
	    $html = str_replace("{{signo_admiracion_open}}",$json->signo_admiracion_open, $html);
	    $html = str_replace("{{trans45}}",$json->trans45, $html);
	    $html = str_replace("{{trans46}}",$json->trans46, $html);

	    $html = str_replace("{{link_to_ventas}}",$json->link_to_compras, $html);

	    $html = str_replace("{{nombre_comprador}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function htmlEmail_corroeo_envio_guia_para_comprador(){

	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo4.html");

	    $this->SCHEMA_DATA['id_correo']     = 30;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans120_;
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo4.html";


	    if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
	        $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
	    }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
	        $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
	    }

	    $array_tipos_envio=[];

	    for ($i=0; $i < 3; $i++) { 
	        $array_tipos_envio[$i]["id"]=$i+1;
	        $mname ="trans118_".strval($i+1);
	        $array_tipos_envio[$i]["nombre"]= $json->$mname;
	    }


	    $tipo_envio = $this->filter_by_value($array_tipos_envio,"id", 2)[0];

	    $html = str_replace("{{nombre_recibe}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
	    $html = str_replace("{{trans151_brand}}",$json->trans151_brand, $html);

	    $html = str_replace("{{trans152}}",$json->trans152, $html);
	    $html = str_replace("{{trans153}}",$json->trans153, $html);
	    $html = str_replace("{{trans154}}",$json->trans154, $html);

	    $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
	    $html = str_replace("{{signo_admiracion_open}}",$json->signo_admiracion_open, $html);





	    $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
	    $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
	    $html = str_replace("{{tipo_envio}}",$this->SCHEMA_DATA['tipo_envio_nombre'], $html);
	    $html = str_replace("{{trans114}}",$json->trans114, $html);
	    $html = str_replace("{{trans115}}",$json->trans115, $html);
	    $html = str_replace("{{numero_guia}}",$this->SCHEMA_DATA["tipo_envio_numero_guia"], $html);
	    $html = str_replace("{{trans116}}",$json->trans116, $html);
	    $html = str_replace("{{trans117}}",$json->trans117, $html);
	    $html = str_replace("{{direccion}}",$this->SCHEMA_DATA["direccion"], $html);




	    $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
	    $html = str_replace("{{trans128}}",$json->trans128, $html);
	    $html = str_replace("{{trans43}}",$json->trans43, $html);
	    $html = str_replace("{{trans17}}",$json->trans117, $html);
	    $html = str_replace("{{direccion}}",$json->trans117, $html);
	    $html = str_replace("{{trans118}}",$json->trans118, $html);

	    $html = str_replace("{{ciudad}}",$this->SCHEMA_DATA["ciudad"], $html);




	    $html = str_replace("{{trans119}}",$json->trans119, $html);

	    $html = str_replace("{{telefono_contacto}}",$this->SCHEMA_DATA["telefono"], $html);
	    $html = str_replace("{{trans120}}","", $html);

	    $html = str_replace("{{fecha_est_llegada}}","", $html);

	    $html = str_replace("{{trans99}}",$json->trans99, $html);


	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function htmlEmail_confirmo_comprador_entrega_envio(){

	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo5.html");

	    $this->SCHEMA_DATA['id_correo']     = 31;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans121_." ".ucfirst($this->SCHEMA_DATA['nombre']);
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo5.html";

	    if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
	        $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
	    }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
	        $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
	    }

	    if($this->SCHEMA_DATA["foto"]=="" || $this->SCHEMA_DATA["empresa"]=="0"){
	        $this->SCHEMA_DATA["foto"]==$json->foto_por_defecto_user;    
	    }


	    $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);
	    $html = str_replace("{{trans144_brand}}",$json->trans144_brand, $html);
	    $html = str_replace("{{trans91}}",$json->trans91, $html);
	    $html = str_replace("{{trans145}}",$json->trans145, $html);
	    $html = str_replace("{{trans146}}",$json->trans146, $html);
	    $html = str_replace("{{trans147}}",$this->SCHEMA_DATA['producto']["titulo"], $html);
	    $html = str_replace("{{trans148}}",$json->trans148, $html);
	    $html = str_replace("{{trans149}}",$json->trans149, $html);
	    $html = str_replace("{{trans150}}",$json->trans150, $html);
	    $html = str_replace("{{link_to_compras}}",$json->link_to_compras, $html);
	    $html = str_replace("{{trans99}}",$json->trans99, $html);
	    $html = str_replace("{{foto_vendedor}}",$this->SCHEMA_DATA["foto"], $html);


	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function htmlEmail_declino__entrega_comprador_envio(){
	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo6.html");

	    $this->SCHEMA_DATA['id_correo']     = 32;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans123_." ".$this->SCHEMA_DATA['producto']["titulo"];
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo6.html";


	    if($this->SCHEMA_DATA["moneda"]=="Nasbigold" || $this->SCHEMA_DATA["moneda"]=="nasbigold"){
	        $this->SCHEMA_DATA["moneda"]="Nasbichips"; 
	    }else if($this->SCHEMA_DATA["moneda"]=="Nasbiblue" || $this->SCHEMA_DATA["moneda"]=="nasbiblue"){
	        $this->SCHEMA_DATA["moneda"]="Bono(s) de descuento"; 
	    }

	    if($this->SCHEMA_DATA["foto"]=="" || $this->SCHEMA_DATA["empresa"]=="0"){
	        $this->SCHEMA_DATA["foto"]==$json->foto_por_defecto_user;    
	    }


	    $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);

	    $html = str_replace("{{trans138_brand}}",$json->trans138_brand, $html);
	    $html = str_replace("{{trans139}}",$json->trans139, $html);
	    $html = str_replace("{{trans140}}",$json->trans140, $html);

	    $html = str_replace("{{trans141}}",$json->trans141, $html);
	    $html = str_replace("{{trans142}}",$this->SCHEMA_DATA['producto']["titulo"], $html);
	    $html = str_replace("{{trans143}}",$json->trans143, $html);
	    $html = str_replace("{{descripcion_queja}}",$this->SCHEMA_DATA["descripcion_generar"], $html);


	    $html = str_replace("{{trans99}}",$json->trans99, $html);


	    $html = str_replace("{{producto_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
	    $html = str_replace("{{trans34_valor}}",$this->maskNumber($this->SCHEMA_DATA['precio']), $html);
	    $html = str_replace("{{moneda}}",$this->SCHEMA_DATA['moneda'], $html);
	    $html = str_replace("{{link_to_ventas}}",$json->link_to_ventas, $html);
	    $html = str_replace("{{foto_vendedor}}",$this->SCHEMA_DATA["foto"], $html);

	    $html = str_replace("{{trans43}}",$json->trans43, $html);
	    $html = str_replace("{{trans111}}",$json->trans111, $html);


	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}
	function html_respuesta_nasbi_cliente(){
	    $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
	    $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/compra_tradiccional/Compratradicionalcorreo7.html");

	    $this->SCHEMA_DATA['id_correo']     = 33;
	    $this->SCHEMA_DATA['titulo_email']  = $json->trans152_;
	    $this->SCHEMA_DATA['path_email']    = "compra_tradiccional/Compratradicionalcorreo7.html";


	    $html = str_replace("{{trans135_brand}}",$json->trans135_brand, $html);
	    $html = str_replace("{{trans136}}", $json->trans136, $html);
	    $html = str_replace("{{trans137}}", $json->trans137, $html);
	    $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA["nombre"], $html);//nombre de usuario 
	    $html = str_replace("{{respuesta_ventaTrad_7}}", $this->SCHEMA_DATA["descripcion_generar"], $html);//mensaje de respuesta 
	    $html = str_replace("{{trans99}}", $json->trans99, $html);


	    $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
	    $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
	    $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
	    $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
	    $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
	    $html = str_replace("{{trans06_}}",$json->trans06_, $html);
	    $html = str_replace("{{trans07_}}",$json->trans07_, $html);
	    $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

	    return $this->sendEmail($this->SCHEMA_DATA, $html);
	}

    // PACK EMAILS #9 - plantilla_venta_por_subasta
    function plantilla_venta_por_subasta( Array $data ){
        return Array(
            "htmlEmailpausa_publi"                   => $this->htmlEmailpausa_publi(),
            "htmlEmailactualizacion_general_product" => $this->htmlEmailactualizacion_general_product(),
            "htmlEmailelimino"                       => $this->htmlEmailelimino(),
            "htmlEmailpregunta"                      => $this->htmlEmailpregunta(),
            "html_calificaron_vendedor"              => $this->html_calificaron_vendedor(),
            "html_correo_de_recarga_nasbichips"      => $this->html_correo_de_recarga_nasbichips()
        );
    }

    function htmlEmailpausa_publi(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo16publicacionpausada.html");

        $this->SCHEMA_DATA['id_correo']     = 34;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans110_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo16publicacionpausada.html";


        
        
        $html = str_replace("{{trans72_brand}}", $json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans81}}", $json->trans81, $html);
        
        $html = str_replace("{{nombre_usuario}}", $this->SCHEMA_DATA['nombre'], $html);

        $html = str_replace("{{trans82}}", $json->trans82, $html);
        $html = str_replace("{{trans43}}", $json->trans43, $html);
        $html = str_replace("{{foto_producto_pausada_brand}}", $this->SCHEMA_DATA['producto']['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['producto']['titulo'], $html);
        $html = str_replace("{{trans76}}", $json->trans76, $html);
        $html = str_replace("{{trans77}}", $json->trans77, $html);
        

        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['producto']['id'], $html);
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }

    function html_calificaron_vendedor(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo27publicaunanuevaventa.html");

        $this->SCHEMA_DATA['id_correo']     = 35;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans142_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo27publicaunanuevaventa.html";


        $html = str_replace("{{trans26_brand}}",$json->trans26_brand, $html);
        $html = str_replace("{{trans27_brand}}",$json->trans27_brand, $html);
        $html = str_replace("{{nombre_usuario}}",ucfirst($this->SCHEMA_DATA['nombre']), $html);  
        
        $html = str_replace("{{trans136_}}",$json->trans136_, $html);
        $html = str_replace("{{trans137_}}",$json->trans137_, $html);
        $html = str_replace("{{trans138_}}",$json->trans138_, $html);
        $html = str_replace("{{link_to_contacto}}",$json->link_to_contacto, $html);
        
        
        $html = str_replace("{{trans28}}",$json->trans28, $html);
        $html = str_replace("{{trans29}}",$json->trans29, $html);
        $html = str_replace("{{trans30}}",$json->trans30, $html);
        $html = str_replace("{{link_to_vender}}",$json->link_to_vender, $html);
        $html = str_replace("{{trans31}}",$json->trans31, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailpregunta(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo18nuevapregunta.html");
        $this->SCHEMA_DATA['id_correo']     = 36;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans109_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo18nuevapregunta.html";
         
        
        $html = str_replace("{{trans72_brand}}", $json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        
        $html = str_replace("{{titulo_producto}}",  $this->SCHEMA_DATA['titulo'], $html);
       
        $html = str_replace("{{trans73}}", $json->trans73, $html);
        $html = str_replace("{{trans75}}", $json->trans75, $html);
        $html = str_replace("{{trans76}}", $json->trans76, $html);
        $html = str_replace("{{trans77}}", $json->trans77, $html);
      
        $html = str_replace("{{nombre_usuario}}",ucfirst( $this->SCHEMA_DATA['nombre']), $html);
        $html = str_replace("{{trans74}}", $json->trans74, $html);
        $html = str_replace("{{foto_producto_nueva_pregunta_brand}}",$this->SCHEMA_DATA['foto_portada'], $html);
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['uid'], $html);
        



        $html = str_replace("{{trans06_}}", $json->trans06_, $html);
        $html = str_replace("{{trans07_}}", $json->trans07_, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html); 
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA['uid']."&act=0&em=".$this->SCHEMA_DATA['empresa'], $html); 
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}","", $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html);
        $html = str_replace("{{trans166}}",$json->trans166, $html);
        $html = str_replace("{{trans147}}",$json->trans147, $html);
        $html = str_replace("{{link_to_product}}", "https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['producto']['id'], $html);
        
       

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function htmlEmailelimino(){

        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo17publicacioneliminada.html");

        $this->SCHEMA_DATA['id_correo']     = 37;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans112_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo17publicacioneliminada.html";

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);

        $html = str_replace("{{trans78}}", $json->trans78, $html);
        $html = str_replace("{{trans66}}", $json->trans66, $html);

        $html = str_replace("{{publicacion_titulo_eliminada}}",$this->SCHEMA_DATA['titulo'], $html);
        $html = str_replace("{{trans79}}", $json->trans79, $html);
        
        $html = str_replace("{{trans80}}", $json->trans80, $html);

        $html = str_replace("{{foto_producto_eliminado_brand}}", $this->SCHEMA_DATA['foto_portada'], $html);
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['titulo'], $html);
        
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=".$this->SCHEMA_DATA['producto']['id'], $html);
        $html = str_replace("{{link_to_vender}}",$json->link_to_vender, $html);
       
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }

    function htmlEmailactualizacion_general_product(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo15publicacionctualizada.html");

        $this->SCHEMA_DATA['id_correo']     = 38;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans108_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo15publicacionctualizada.html";
        $html = str_replace("{{link_to_product}}","https://nasbi.com/content/producto.php?uid=". $this->SCHEMA_DATA['producto']['id'], $html);

        $html = str_replace("{{trans72_brand}}",$json->trans72_brand, $html);
        $html = str_replace("{{trans_04_brand}}", $json->trans_04_brand, $html);
        $html = str_replace("{{trans83}}", $json->trans83, $html);
        $html = str_replace("{{nombre_usuario}}",$this->SCHEMA_DATA['nombre'], $html);
        $html = str_replace("{{trans84}}", $json->trans84, $html);
        $html = str_replace("{{foto_publicacion_actualiada_brand}}", $this->SCHEMA_DATA['foto_portada'], $html);
        $html = str_replace("{{trans85}}", $json->trans85, $html);
        
        $html = str_replace("{{titulo_producto}}",$this->SCHEMA_DATA['titulo'], $html);
        

       
        $html = str_replace("{{link_to_ventas}}","https://nasbi.com/content/mis-cuentas.php?tokenPageView=id-ventas", $html);
        $html = str_replace("{{trans43}}",$json->trans43, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }
    function html_correo_de_recarga_nasbichips(){
        $json = json_decode(file_get_contents("/var/www/html/buyinbig/JSON/ES.json"));
        $html = file_get_contents("/var/www/html/buyinbig/plantillas_emails/plantilla_venta_por_subasta/correo12nasbichips.html");

        $this->SCHEMA_DATA['id_correo']     = 39;
        $this->SCHEMA_DATA['titulo_email']  = $json->trans163_;
        $this->SCHEMA_DATA['path_email']    = "plantilla_venta_por_subasta/correo12nasbichips.html";
        
        $moneda_u= $json->trans164_;
       

        $html = str_replace("{{nombre_usuario}}",ucfirst( $this->SCHEMA_DATA['nombre']), $html);

        $html = str_replace("{{trans82_}}",$json->trans82_, $html);
        $html = str_replace("{{trans83_}}",$json->trans83_, $html);

        $html = str_replace("{{trans84_}}",$json->trans84_, $html);
        $html = str_replace("{{trans28_}}",$json->trans28_, $html);
        $html = str_replace("{{trans29_}}",$json->trans29_, $html);

        $html = str_replace("{{trans30_}}",$json->trans30_, $html);

        $html = str_replace("{{trans31_}}",$json->trans31_, $html);

        $html = str_replace("{{trans82_}}",$json->trans82_, $html);
        $html = str_replace("{{trans99}}",$json->trans99, $html);
        $html = str_replace("{{trans34_valor}}", $this->maskNumber($this->SCHEMA_DATA["precio"], 2), $html);
        $html = str_replace("{{trans35_unidad}}",$moneda_u, $html);
        $html = str_replace("{{trans31_}}",$json->trans31_, $html);
        $html = str_replace("{{trans37_}}",$json->trans37_, $html);
        $html = str_replace("{{trans38_}}",$json->trans38_, $html);
        $html = str_replace("{{trans39_}}",$json->trans39_, $html);
        $html = str_replace("{{signo_amdiracion_open}}",$json->signo_admiracion_open, $html);
        $html = str_replace("{{trans25_}}",$json->trans25_, $html);
        $html = str_replace("{{trans85_}}",$json->trans85_, $html);
        $html = str_replace("{{trans40_}}",$json->trans40_, $html);
        $html = str_replace("{{trans86_}}",$json->trans86_, $html);
        $html = str_replace("{{trans165_}}",$json->trans165_, $html);
        
        
        $html = str_replace("{{link_to_promociones}}", $json->link_to_promociones, $html);
        $html = str_replace("{{logo_footer_brand}}", $json->logo_footer_brand, $html);
        $html = str_replace("{{link_facebook_nasbi}}",$json->to_facebook_, $html);
        $html = str_replace("{{link_instagram_nasbi}}",$json->to_instagram_, $html);
        $html = str_replace("{{link_youtube_nasbi}}",$json->to_youtube_, $html);
        $html = str_replace("{{link_in_nasbi}}",$json->to_in_, $html); 
        $html = str_replace("{{trans06_}}",$json->trans06_, $html);
        $html = str_replace("{{trans07_}}",$json->trans07_, $html);
        $html = str_replace("{{link_dar_de_baja}}", "https://nasbi.com/content/index.php?sr=".$this->SCHEMA_DATA["uid"]."&act=0&em=".$this->SCHEMA_DATA["empresa"], $html); 

        return $this->sendEmail($this->SCHEMA_DATA, $html);
    }





    // FUNCIONES GENERALES.
    function sendEmail($dato, $htmlPlantilla)
    {
    	$TimestampEnvio = new DateTime();

        $desde  = "info@nasbi.com";
        $para   = $dato['correo'];
        $titulo = "REF: " . $this->SCHEMA_DATA['id_correo'] . " - " . $dato['titulo_email'] . " - TimestampEnvio: " . $TimestampEnvio->getTimestamp()*1000  . " - PATH: " . $dato['path_email'];

        $mensaje1   = $htmlPlantilla;

        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $cabeceras .= 'From: '. $desde . "\r\n";

        $dataArray = array(
        	'email'     => $para,
        	'titulo'    => $titulo,
        	'mensaje'   => $mensaje1,
        	'cabeceras' => $cabeceras
        );
        
        $response = parent::remoteRequest('https://criptocomers.com/api/p2w/', $dataArray);
        $respuesta = json_decode($response, true);
        return $respuesta;
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
    function get_categoria_product(){
        parent::conectar();
        $selectcategoria = "SELECT * FROM buyinbig.categorias";
        $categorias = parent::consultaTodo($selectcategoria);
        parent::cerrar();
        return $categorias;
    }
    function truncNumber(Float $number, Int $prec = 2 )
    {
        return sprintf( "%.".$prec."f", floor( $number*pow( 10, $prec ) )/pow( 10, $prec ) );
    }
    function maskNumber(Float $numero, Int $prec = 2) {
        $numero = $this->truncNumber($numero, $prec);
        return number_format($numero, $prec, '.', ',');
    }
}
?>
