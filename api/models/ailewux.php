<?php
    require 'Nodo.php';

    class Ailewux extends Conexion {

        public function listarAilewuxPersona($ailewux) {
            parent::conectar();

            $consulta = "SELECT * FROM solicitud_persona WHERE usuario = $ailewux[id] order by fecha desc;";
            $consulta2 = "SELECT * FROM solicitud_distribuidor WHERE usuario = $ailewux[id] order by fecha desc;";

            $lista = parent::consultaTodo($consulta);
            $lista2 = parent::consultaTodo($consulta2);

            parent::cerrar();

            $respuesta = array(
                'status' => 'success', 
                'personal' => $lista,
                'distribuidor' => $lista2,
            );

            return $respuesta;
        }

        public function listarAilewuxDistribuidor($ailewux) {
            parent::conectar();

            $consulta = "SELECT * FROM solicitud_distribuidor WHERE usuario = $ailewux[id] order by fecha desc;";

            $lista = parent::consultaTodo($consulta);

            parent::cerrar();

            $respuesta = array(
                'status' => 'success', 
                'data' => $lista
            );

            return $respuesta;
        }

        public function comprarAilewuxPer($ailewux) {
            $address["BTC"] = "2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF";
            $nodo = new Nodo();
            parent::conectar();
            $respuesta;
            $fechaFin       = getdate();
            $fecha          = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
            $consultar1 = "select * from usuarios where id= '{$ailewux['id']}'";
            $lista      = parent::consultaTodo($consultar1);
            $consultaPlan = parent::consultaTodo("select p.compra_ailewux from paquetes p where id = {$lista[0]["plan"]}");
            $precioUSD = $nodo->precioUSD();
            $descuento = $consultaPlan[0]["compra_ailewux"];
            $montoPago = number_format(200 *(1 /  $precioUSD) * (1 - $descuento), 6) * $ailewux["cantidad"];
            $consultarAddress = parent::consultaTodo("select * from address_btc where label = 'wallet_$ailewux[id]'");
            $data = array(
                "address_from"=> $consultarAddress[0]["address"],
                "address_to"=> $address["BTC"],
                "amount"=> $montoPago,
                "reason"=> "Compra de ailewux",
                "usuario"=> $ailewux["id"],
                "coin"=> "BTC"
            );

            if (($consultarAddress[0]["saldo"]) < ($montoPago * 1)) {
                $respuesta = array("status"=>"errorMonto", "message"=>"No dispones de saldo suficiente para realizar esta compra");
            } else if ($montoPago < 0.0006) {
                $respuesta = array("status"=>"errorMontoMin", "message"=>"El monto mínimo a comprar es de 0.0006 BTC");
            } else {
                $dataresp = json_decode(parent::remoteRequest("https://peers2win.com/api/controllers/nodo/nodo.php", $data), true);
                if ($dataresp["status"] == "success") {
                    $consultar1 = "INSERT INTO solicitud_persona (cantidad, pais, ciudad, direccion, txhash, fecha, monto, precio, usuario) VALUE ($ailewux[cantidad], '$ailewux[pais]', '$ailewux[ciudad]', '$ailewux[direccion]', '$dataresp[txhash]', current_time, $montoPago, $precioUSD, $ailewux[id])";
                    $id = parent::queryRegistro($consultar1);
                    $consultar2 = "select * from solicitud_persona where id= $id";
                    $respuesta = array('status' => 'success', 'message' => 'Se agregaron correctamente', 'data' => parent::consultaTodo($consultar2));
                } else {
                    $respuesta = array("status"=>"errorMonto", "message"=>"No dispones de saldo suficiente para realizar esta compra");
                }
            }

            parent::cerrar();
            return $respuesta;
        }

        public function comprarAilewuxDistri($ailewux) {
            $address["BTC"] = "2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF";
            $nodo = new Nodo();
            parent::conectar();
            $respuesta;
            $fechaFin       = getdate();
            $fecha          = $fechaFin['year'] . '-' . $fechaFin['mon'] . '-' . $fechaFin['mday'];
            $consultar1 = "select * from usuarios where id= '{$ailewux['id']}'";
            $lista      = parent::consultaTodo($consultar1);
            $consultaPlan = parent::consultaTodo("select p.compra_ailewux from paquetes p where id = {$lista[0]["plan"]}");
            $precioUSD = $nodo->precioUSD();
            $descuento = $consultaPlan[0]["compra_ailewux"];
            $montoPago = number_format(200 *(1 /  $precioUSD) * (1 - $descuento), 6) * $ailewux["cantidad"];
            $consultarAddress = parent::consultaTodo("select * from address_btc where label = 'wallet_$ailewux[id]'");
            $data = array(
                "address_from"=> $consultarAddress[0]["address"],
                "address_to"=> $address["BTC"],
                "amount"=> $montoPago,
                "reason"=> "Compra de ailewux",
                "usuario"=> $ailewux["id"],
                "coin"=> "BTC"
            );

            if (($consultarAddress[0]["saldo"]) < ($montoPago * 1)) {
                $respuesta = array("status"=>"errorMonto", "message"=>"No dispones de saldo suficiente para realizar esta compra");
            } else if ($montoPago < 0.0006) {
                $respuesta = array("status"=>"errorMontoMin", "message"=>"El monto mínimo a comprar es de 0.0006 BTC");
            } else {
                $dataresp = json_decode(parent::remoteRequest("https://peers2win.com/api/controllers/nodo/nodo.php", $data), true);
                if ($dataresp["status"] == "success") {
                    $consultar1 = "INSERT INTO solicitud_distribuidor (cantidad, pais, ciudad, direccion, txhash, nombre_usuario, fecha, monto, precio, usuario) VALUE ($ailewux[cantidad], '$ailewux[pais]', '$ailewux[ciudad]', '$ailewux[direccion]', '$dataresp[txhash]', '{$lista[0]["nombreCompleto"]}', current_time, $montoPago, $precioUSD, $ailewux[id])";
                    $id = parent::queryRegistro($consultar1);
                    $consultar2 = "select * from solicitud_distribuidor where id= $id";
                    $respuesta = array('status' => 'success', 'message' => 'Se agregaron correctamente', 'data' => parent::consultaTodo($consultar2));
                } else {
                    $respuesta = array("status"=>"errorMonto", "message"=>"No dispones de saldo suficiente para realizar esta compra");
                }
            }

            parent::cerrar();
            return $respuesta;
        }

        public function mail($ailewux) {
            parent::conectar();
            $consultar1 = "select u.*, p.name as pais from usuarios u inner join paises p on u.paisid = p.id where u.id= '{$ailewux['id']}'";
            $lista      = parent::consultaTodo($consultar1);
            $html = '
                <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "ttp://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                        <meta name="viewport" content="width=device-width, initial-scale=1" />
                        <title>Neopolitan Confirm Email</title>
                        <style type="text/css">
                            *{
                                font-family: Arial!important;
                            }
                            body{
                                font-family: Arial!important;
                            }
                            .Contenedor_Correo {
                                background: url("https://shoxen.com/shoxen-correo.png");
                                background-repeat: no-repeat;
                                background-position: center center;
                                height: 630px;
                            }
                            .descriptivos {
                                color: #FFFFFF;
                            }
                            .Img_Emetbusiness{
                                width: 60%;
                                margin-top: 10%;
                            }
                            .Nombre_Usuario{
                                color: #1dcad3;
                                font-size: 22px;
                                font-weight: bold;
                                text-align: center;
                                font-family: "Open Sans";
                            }
                            .Conte_Blanco{
                                background-color: rgba(255, 255, 255, 0.8);
                                padding-top: 1%;
                                width: 43%!important;
                                min-width: 43%!important;
                                max-width: 43%!important;
                                max-height: 420px;
                            }
                            .desc{
                                color: #1dcad3;
                                font-size: 15px;
                                text-align: center;
                                margin-bottom: 5%;
                                margin-top: 10%;
                                padding: 5px;
                                font-family: "Open Sans";
                            }
                            .btn_subir{
                                width: 50%;
                                margin-top: 10%;
                                background-color: #1dcad3;
                                color: #fff;
                                margin-bottom: 8%;
                                padding: 10px;
                                border: none;
                                font-size: 16px;
                                border-radius: 5px;
                                font-family: "Open Sans";
                            }
                            .row{
                                width: 100%;
                                font-family: "Open Sans";
                            }
                            .pBtn{
                                width: 30%;
                                color:#FFFFFF;
                                padding: 10px;
                                background: #1dcad3;
                                border-radius: 5px;
                                font-size:15px;
                                padding-left: 10%;
                                padding-right: 10%;                       	
                            }
                            @media (max-width: 767px){
                                .pBtn{
                                    width: 70%!important;
                                    font-size: 13px!important;
                                }
                            }
                            @media(max-width: 480px){
                                .Conte_Blanco{
                                    background-color: rgba(255, 255, 255, 0.8);
                                    padding-top: 1%;
                                    width: 85%!important;
                                    min-width: 85%!important;
                                    max-width: 85%!important;
                                }
                                .pBtn{
                                    width: 70%!important;
                                    font-size: 12px!important;
                                }
                            }
                            @media(max-width: 1400px){
                            .pBtn{
                                font-size: 12px!important;
                            }
                            }
                        </style>
                    </head>
                    <body>
                        <div align="center">
                            <div class="Contenedor_Correo" id="about">
                                <br><br><br><br><br><br><br><br><br><br>
                                <div align="center">
                                    <div class="row row_content">
                                        <div class="col-md-12">
                                            <div class="Conte_Blanco">
                                                <p class="Nombre_Usuario">Nombre: '.$lista[0]['nombreCompleto'].'!</p>
                                                <p class="Nombre_Usuario">Correo: '.$lista[0]['email'].'!</p>
                                                <p class="Nombre_Usuario">Telefono: '.$lista[0]['telefono'].'!</p>
                                                <p class="Nombre_Usuario">País: '.$lista[0]['pais'].'!</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </body>
                </html>
            ';
            parent::cerrar();
            $para      = 'he03villa@gmail.com';
            $titulo    = 'Correo de contacta de solicitud de distribuidor de ailewux '.$lista[0]['email'];
            $mensaje1   = $html;
            $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $cabeceras .= 'From: '.$lista[0]['email'] . "\r\n";
            $dataArray = array("email"=>$lista[0]['email'], "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            $respuesta = json_decode($response, true);
            /* print_r($cabeceras); */
            /* if(mail($para, $titulo, $mensaje1, $cabeceras)) return array('status' => 'success','mensaje' => 'Se envio el correo');
            else return array('status' => 'error','mensaje' => 'Error el servidor'); */
            return $respuesta;
        }
    }