<?php

  class Tcc extends Conexion
  {

    function consultar_liquidacion_wbs_tcc($data)
    {

      // return "hola";

      // 1ro: Cuanto cuesta enviar un articulo
      //$URL_to_wbs= "http://clientes.tcc.com.co/preservicios/liquidacionacuerdos.asmx?wsdl"; //prueba
      $URL_to_wbs = "http://clientes.tcc.net.co/servicios/liquidacionacuerdos.asmx?wsdl"; //produccion

      $client = new SoapClient($URL_to_wbs);
      $params = $data;
      $response = $client->__soapCall("consultarliquidacion2", array(
          $params
      ));
      $response = get_object_vars($response);
      return $response;
    }

    function grabar_despacho($data)
    {
      // 2do: Genera el número de guia más el rotulo que se pega en la caja.
      // $URL_to_wbs= "http://preclientes.tcc.com.co:1080/api/clientes/remesasws?wsdl"; //prueba
      $URL_to_wbs = "http://clientes.tcc.net.co:4080/api/clientes/remesasws?wsdl"; //produccion
      

      $client = new SoapClient($URL_to_wbs);

      $params = $data;
      $response = $client->__soapCall("grabardespacho7", array(
          $params
      ));
      $response = get_object_vars($response);
      return $response;

    }

    function tracking($data)
    {
      //3ro: Visualizar por donde va tu envio.
      // $URL_to_wbs= "http://clientes.tcc.com.co/preservicios/informacionremesas.asmx?wsdl"; //prueba
      $URL_to_wbs = "http://clientes.tcc.net.co/servicios/informacionremesas.asmx?wsdl"; //produccion
      
      $client = new SoapClient($URL_to_wbs);
      $params = $data;

      $response = $client->__soapCall("consultarInformacionRemesasEstadosUEN", array(
          $params
      ));
      $response = get_object_vars($response);
      return $response;
    }
  }
?>
