<?php
// require 'conexion.php';
require 'Nodo.php';

class Pagos extends Conexion
{
    public function Date()
    {

        parent::conectar();
        $lista = array('time' => date("Y-m-d"));
        parent::cerrar();

        return $lista;
    }

    public function pagarBonos($datos) {
        $address["BTC"] = "2MsbmodZYK49daDUxzuv7XG2QVCrzkFucqF";
        $address["EBG"] = "0x372b768ca07c9406ff449ef7cf0cb0d354153b1a";
        parent::conectar();

        $data = array("address_from"=>$datos["address"], "address_to"=>$address[$datos["moneda"]], "amount"=>$datos["monto"], "reason"=>"Compra de bonos nasbi", "usuario"=>$datos["usuario"], "coin"=>$datos["moneda"]);
        //print_r($data);
        $dataresp = json_decode(parent::remoteRequest("https://peers2win.com/api/controllers/nodo/nodo.php", $data), true);
        
        if ($dataresp["status"] == "success") {
            $moneda = $datos["moneda"] === 'BTC' ? 'Nasbigold' : 'Nasbiblue';
            $datareq = parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/nasbicoin/?recibir_dinero", array("data"=>array("uid"=>$datos["usuario"], "monto"=>$datos["monto"], "moneda"=>$moneda, "empresa"=>0, "tipo"=>1)));   
            return array("status"=> "success");
        } else {
            return $dataresp;
        }

        
        //print_r(json_decode($datareq, true));
        //return json_decode($datareq, true);
    }





    public function agregarRegistroMensual($usuario, $tipo){
        parent::conectar();
        
        $fechacorte = parent::consultaTodo("select * from fechas_corte_reconsumos fcr where usuario = $usuario and CURRENT_DATE BETWEEN fecha_inicio  and fecha_fin");
        if (count($fechacorte) > 0) {
            $usuario = parent::consultaTodo("select *, CURRENT_DATE, if(CURRENT_DATE > fecha_fin, 1, 0) finaliza from usuarios where id = $usuario");
            if (count($usuario) > 0) {
                if ($usuario[0]["finaliza"] == 1) {
                    echo "update usuarios set fecha_inicio = DATE_ADD('{$fechacorte[0]["fecha_fin"]}', interval -1 month), fecha_fin = '{$fechacorte[0]["fecha_fin"]}'";
                    parent::queryRegistro("update usuarios set fecha_inicio = DATE_ADD('{$fechacorte[0]["fecha_fin"]}', interval -1 month), fecha_fin = '{$fechacorte[0]["fecha_fin"]}'");
                }
            }
        } else {
            if ($tipo == 2) {
                parent::queryRegistro("insert into fechas_corte_reconsumos (usuario, fecha_inicio, fecha_fin, tipo) values ($usuario, CURRENT_DATE, DATE_ADD(CURRENT_DATE, interval 1 month), '$tipo')");
                parent::queryRegistro("update usuarios set fecha_inicio = CURRENT_DATE, fecha_fin = DATE_ADD(CURRENT_DATE, interval 1 month)");
            } else {
                parent::queryRegistro("insert into fechas_corte_reconsumos (usuario, fecha_inicio, fecha_fin, tipo) values ($usuario, CURRENT_DATE, DATE_ADD(CURRENT_DATE, interval 2 month), '$tipo')");
                parent::queryRegistro("update usuarios set fecha_inicio = CURRENT_DATE, fecha_fin = DATE_ADD(CURRENT_DATE, interval 1 month)");
            }    
        }
        
    }

    public function validarCompraMembresia($usuario)
    {
        parent::conectar();
        $lista = parent::consultaTodo("select count(*) totales from pago_membresia where usuario = $usuario and current_date < fecha_limite;");
        // print_r($lista);
        if (count($lista) > 0) {
            // print_r($lista[0]);
            // if ($lista[0]["totales"] == 1) {
            //     echo "mayor a 1";
            // } else {
            //     echo "menor o igual a 1";
            // }
            return $lista[0]["totales"] <= 1;
        } else {
            return false;
        }

    }

    public function reconsumir($usuario, $plan, $precioUSDOrden)
    {

        parent::conectar();
        $respuesta  = "Completado";
        $consultar1 = "select * from usuarios where id = $usuario";
        $lista      = parent::consultaTodo($consultar1);
        if (count($lista) > 0) {
            if ($lista[0]["status"] == "2") {
                parent::queryRegistro("update usuarios set status = 1 where id = {$lista[0]["id"]}");
                $this->agregarRegistroMensual($usuario, 1);
            }
            $lista[0]["status"] = "1";
            $consulta2          = parent::consultaTodo("select $usuario 'id', a.id asociado, a.username nombre_asociado, (c.montoPendiente + (d.puntos * b.pago_binario)) puntaje, d.puntos, d.entradas300, d.entradas500, d.entradas1000  from usuarios a inner join paquetes b on b.id = a.plan inner join pagoBinario c on c.asociado = a.id and c.usuario = $usuario inner join paquetes d on d.id = $plan inner join usuarios e on c.asociado = e.id where a.id in (select asociado from pagoBinario where usuario = $usuario) and a.status = 1");
            // print_r($consulta2);
            for ($i = 0; $i < count($consulta2); $i++) {
                $insercion = "update pagoBinario set montoPendiente = {$consulta2[$i]["puntaje"]}, puntosPendientes = puntosPendientes + {$consulta2[$i]["puntos"]} where usuario = {$consulta2[$i]["id"]} and asociado = {$consulta2[$i]["asociado"]}";
                // echo $insercion . "\n";
                parent::queryRegistro($insercion);
            }
            $this->pagarSuperiores($usuario, $precioUSDOrden);
            $this->agregarPlanAdquirido($usuario, $plan);
            $detallePlan = $this->obtenerDetallePlan($plan);
            parent::conectar();
            $this->insertarNotificacion($usuario, "Se ha realizado con éxito tu reconsumo {$detallePlan[0]["nombre"]}", "reconsumo");

            $this->pagarEBG($usuario, $detallePlan[0]["monto"]  * 0.1);
            $respuesta = array('status' => 'success', 'message' => 'El reconsumo ha sido aplicado correctamente');
        } else {
            $respuesta = array('status' => 'error', 'message' => 'El usuario no existe');
        }
        // parent::cerrar();
        return $respuesta;

    }

    public function pagarEBG($user, $montoUSD) {
        parent::conectar();
        $datosPay = parent::consultaTodo("select * from address_ebg where usuario = $user limit 1");
        $nodo     = new Nodo();
        $monto = $montoUSD / $nodo->precioUSDEBG();
        $res = $nodo->sendTransactionByEBG(array("address_from" => "0x372b768ca07c9406ff449ef7cf0cb0d354153b1a", "amount"=>$monto,"address_to" => $datosPay[0]["address"], "reason" => "Pago Reconsumo EBG", "usuario" => "0"));
        print_r($res);
        return $res;
    }

    public function pagoUnilevelMonto($usuario)
    {
        parent::conectar();
        $results3 = parent::consultaTodo("select * from usuarios where id = $usuario");
        if (count($results3) > 0) {
            $montoaPagar = 0;
            $pagar       = 0.1;
            $plan = $this->obtenerDetallePlan($results3[0]["plan"]);

            $pagar = $plan[0]["unilevel"] * 1;
            if ($results3[0]["rango"] >= 5) {
                $pagar += 0.03;
            }
            parent::conectar();
            $pagoPorPrimerNivel = parent::consultaTodo("select ifnull(sum(p.monto), 0) * $pagar monto from puntajeBinario p inner join usuarios u on u.id = $usuario where usuarioRecibe in (select id from usuarios where referido = $usuario) and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin;");
            if (count($pagoPorPrimerNivel) > 0) {
                $montoaPagar += $pagoPorPrimerNivel[0]["monto"];
            }
            // echo "monto del 13% {$pagoPorPrimerNivel[0]["monto"]} \n";
            if ($results3[0]["rango"] >= 6) {
                $pagoPorSegundoNivel = parent::consultaTodo("select ifnull(sum(p.monto), 0) * 0.03 monto from puntajeBinario p inner join usuarios u on u.id = $usuario where usuarioRecibe in (select id from usuarios where referido in (select id from usuarios where referido = $usuario)) and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin");
                if (count($pagoPorSegundoNivel) > 0) {
                    $montoaPagar += $pagoPorSegundoNivel[0]["monto"];
                    // echo "monto del 3% segundo nivel {$pagoPorSegundoNivel[0]["monto"]} \n";
                }
            }
            if ($results3[0]["rango"] >= 7) {
                $pagoPorTercerNivel = parent::consultaTodo("select ifnull(sum(p.monto), 0) * 0.02 monto from puntajeBinario p inner join usuarios u on u.id = $usuario where usuarioRecibe in (select id from usuarios where referido in (select id from usuarios where referido in (select id from usuarios where referido = $usuario))) and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin;");
                if (count($pagoPorTercerNivel) > 0) {
                    $montoaPagar += $pagoPorTercerNivel[0]["monto"];
                    // echo "monto del 2% tercer nivel {$pagoPorTercerNivel[0]["monto"]} \n";
                }
            }
            if ($results3[0]["rango"] >= 8) {
                $pagoPorCuartoNivel = parent::consultaTodo("select ifnull(sum(p.monto), 0) * 0.01 monto from puntajeBinario p inner join usuarios u on u.id = $usuariowhere usuarioRecibe in (select id from usuarios where referido in (select id from usuarios where referido in (select id from usuarios where referido in (select id from usuarios where referido = $usuario)))) and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin;");
                if (count($pagoPorCuartoNivel) > 0) {
                    $montoaPagar += $pagoPorCuartoNivel[0]["monto"];
                    // echo "monto del 1% cuarto nivel {$pagoPorCuartoNivel[0]["monto"]} \n";
                }
            }
            print_r($pagar);
            return array("status" => "success", "monto" => $montoaPagar);

        } else {
            return array("status" => "error", "message" => "el id no existe");
        }

    }

    public function pendiente($usuario)
    {

        parent::conectar();
        $respuesta = array("status" => "error", "message" => "no tiene planes asociados");
        $consulta  = parent::consultaTodo("select id, monto,plan,monto_usd,precio_usd,address_recibe,membresia,unix_timestamp(fecha_vencimiento) fecha_vencimiento  from asociar_pagos where usuario = $usuario and asociacion = 0");

        if (count($consulta) > 0) {
            $plan          = $this->obtenerDetallePlan($consulta[0]["plan"]);
            $planMembresia = "0";
            if ($consulta[0]["membresia"] == "1") {
                $planMembresia = $this->obtenerDetallePlan(10)[0]["monto"];

            }
            // print_r($consulta[0]);
            $respuesta = array('status' => 'success', "id" => $consulta[0]["id"], "editable" => $this->estaPorConfirmar($consulta[0]["address_recibe"], $consulta[0]["monto"]), 'message' => 'datos pendientes', "montoCripto" => $consulta[0]["monto"], "montoUSD" => $consulta[0]["monto_usd"], "precioUSD" => $consulta[0]["precio_usd"], "address" => $consulta[0]["address_recibe"], "plan" => array("id" => $plan[0]["id"], "nombre" => $plan[0]["nombre"], "montoUSD" => $plan[0]["monto"]), "membresia" => $planMembresia, "tiempo" => $consulta[0]["fecha_vencimiento"] * 1000);
        }

        // parent::cerrar();
        return $respuesta;

    }

    public function solicitarPlan($usuario)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select id, monto,plan,monto_usd,precio_usd,address_recibe,membresia,unix_timestamp(fecha_vencimiento) fecha_vencimiento  from asociar_pagos where usuario = " . $usuario['id'] . " and asociacion = 0");
        parent::cerrar();
        return $respuesta = array("status" => "success", "message" => "Plan", "data" => $consulta[0]);
    }

    public function editarPendiente($usuario, $status)
    {
        parent::conectar();
        $consulta = $this->pendiente($usuario);
        // print_r($status);
        if ($consulta["editable"]) {
            parent::queryRegistro("delete from asociar_pagos where id = {$consulta["id"]}");
            if ($status == "true") {
                $detallePlan      = $this->obtenerDetallePlan($consulta["plan"]["id"]);
                $detalleMembresia = $this->obtenerDetallePlan("10");
                $preciousd        = $this->getUSDNodo();
                $monto            = number_format(($detallePlan[0]["monto"] / $preciousd), 6);
                if ($consulta["membresia"] > 0) {
                    if ($consulta["plan"]["id"] != 10) {
                        $monto += number_format(($detalleMembresia[0]["monto"] / $preciousd), 6);
                        $res = $this->marcarPago($usuario, $monto, $consulta["plan"]["id"], ($consulta["membresia"] > 0 ? 1 : 0), $preciousd, $detallePlan[0]["monto"] + $detalleMembresia[0]["monto"]);
                    } else {
                        $res = $this->marcarPago($usuario, $monto, $consulta["plan"]["id"], ($consulta["membresia"] > 0 ? 1 : 0), $preciousd, $detallePlan[0]["monto"]);
                    }
                    
                } else {
                    $res = $this->marcarPago($usuario, $monto, $consulta["plan"]["id"], ($consulta["membresia"] > 0 ? 1 : 0), $preciousd, $detallePlan[0]["monto"]);    
                }
                // echo ($monto);
                
                return $res;
            }
        } else {
            return array("status" => "error", "message" => "no se puede editar ya se encuentra asociada a algun proceso");
        }

    }

    public function estaPorConfirmar($address, $monto)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from enviarTransaccion where address_receive = '$address' and monto = $monto and recibida = 0");
        if (count($consulta) == 0) {
            return array("status" => true);
        } else {
            return array("status" => false, "txhash" => $consulta[0]["txhash"]);
        }
        return (count($consulta) == 0);
    }

    public function puedoReconsumir($usuario)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from usuarios where id = '$usuario' ");
        if (count($consulta) > 0) {
            return $consulta[0]["status"] != 3;
        } else {
            return true;
        }

    }

    public function marcarPago($usuario, $monto, $plan, $membresia, $precio_usd, $monto_usd)
    {
        parent::conectar();
        $consulta = parent::consultaTodo("select * from address_btc where label = 'recibir_{$usuario}' ");
        $address  = "";
        if (count($consulta) > 0) {
            $address = $consulta[0]["address"];
        } else {
            $nodo     = new Nodo();
            $temporal = $nodo->newAccountPayments($usuario);
            $address  = $temporal["address"];
        }
        if ($address != "") {
            $consulta2 = parent::consultaTodo("select * from asociar_pagos where address_recibe = '$address' and asociacion = 0");
            if (count($consulta2) > 0) {
                $return = array("status" => "error", "message" => "Solo puedes tener una solicitud abierta a la vez");
            } else {
                parent::queryRegistro("insert into asociar_pagos (usuario, monto, address_recibe, asociacion, plan, membresia, precio_usd, monto_usd, fecha_vencimiento) values ($usuario, $monto, '$address', 0, $plan, $membresia, $precio_usd, $monto_usd, date_add(CURRENT_TIMESTAMP, interval 30 minute))");
                $return = array('status' => "success", "valor" => "orden generada con éxito", "address" => $address, "monto" => $monto);
            }
        } else {
            $return = array("status" => "error", "message" => "no pudo asignarsele una cuenta para pagos, intente mas tarde");
        }
        parent::cerrar();
        return $return;
    }

    public function ejecutarPago($txhash, $address, $monto)
    {
        echo "entro a ejecutar pago \n";
        parent::conectar();
        // echo "select * from asociar_pagos where address_recibe = '$address' and asociacion = 0 and monto = $monto";
        $consulta2 = parent::consultaTodo("select * from asociar_pagos where address_recibe = '$address' and asociacion = 0 and monto = $monto");
        if (count($consulta2) > 0) {
            $planAObtener = $this->obtenerDetallePlan($consulta2[0]["plan"]);
            parent::conectar();
            parent::queryRegistro("update asociar_pagos set txhash = '$txhash', asociacion = 1, fecha_pago = current_timestamp where id= {$consulta2[0]["id"]}");
            $usuario = parent::consultaTodo("select * from usuarios where id = {$consulta2[0]["usuario"]}");
            // echo "{$consulta2[0]["membresia"]} == 1 && {$consulta2[0]["plan"]} == 10 && {$usuario[0]["plan"]} != 10";
            if ($consulta2[0]["membresia"] == 1 && $consulta2[0]["plan"] == 10 && $usuario[0]["plan"] != 10) {
                if ($usuario[0]["status"] == 4) {
                    parent::queryRegistro("update usuarios set status = 2 where id = {$consulta2[0]["usuario"]}");
                } else {
                    parent::queryRegistro("update usuarios set status = 1 where id = {$consulta2[0]["usuario"]}");
                }

                $datatodo = parent::consultaTodo("select fecha_limite, if(fecha_limite < CURRENT_DATE, 1, 0) esfinal, date_add(fecha_limite, interval 1 day) manana, date_add(fecha_limite, interval 1 year) proximo from pago_membresia where usuario = {$consulta2[0]["usuario"]} order by fecha_limite desc;");
                if (count($datatodo) > 0) {
                    if ($datatodo[0]["esfinal"] == 1) {
                        parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                        // echo "insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)";
                    } else {
                        parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, '{$datatodo[0]["proximo"]}', '{$datatodo[0]["manana"]}')");
                    }
                } else {
                    parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                }
            } else if ($planAObtener[0]["tipo"]== 1 || $consulta2[0]["plan"] == 10) {
                if ($consulta2[0]["membresia"] == 1) {
                    parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 year) where id = {$consulta2[0]["usuario"]}");
                }
                $res = $this->agregarPlan($consulta2[0]["usuario"], $consulta2[0]["plan"], $consulta2[0]["precio_usd"]);
            } else if ($planAObtener[0]["tipo"]==2) {
                if ($consulta2[0]["membresia"] == 1) {
                    parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 month) where id = {$consulta2[0]["usuario"]}");
                    $datatodo = parent::consultaTodo("select fecha_limite, if(fecha_limite < CURRENT_DATE, 1, 0) esfinal, date_add(fecha_limite, interval 1 day) manana, date_add(fecha_limite, interval 1 year) proximo from pago_membresia where usuario = {$consulta2[0]["usuario"]} order by fecha_limite desc;");
                    if (count($datatodo) > 0) {
                        if ($datatodo[0]["esfinal"] == 1) {
                            parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                            // echo "insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)";
                        } else {
                            parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, '{$datatodo[0]["proximo"]}', '{$datatodo[0]["manana"]}')");
                        }
                    } else {
                        parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                    }
                }
                // if ($consulta2[0]["status"] == 2) {
                //     parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 year) where id = {$consulta2[0]["usuario"]}");
                // }
                $res = $this->reconsumir($consulta2[0]["usuario"], $consulta2[0]["plan"], $consulta2[0]["precio_usd"]);
            } else {
                if ($consulta2[0]["membresia"] == 1) {
                    parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 month) where id = {$consulta2[0]["usuario"]}");
                    $datatodo = parent::consultaTodo("select fecha_limite, if(fecha_limite < CURRENT_DATE, 1, 0) esfinal, date_add(fecha_limite, interval 1 day) manana, date_add(fecha_limite, interval 1 year) proximo from pago_membresia where usuario = {$consulta2[0]["usuario"]} order by fecha_limite desc;");
                    if (count($datatodo) > 0) {
                        if ($datatodo[0]["esfinal"] == 1) {
                            parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                            // echo "insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)";
                        } else {
                            parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, '{$datatodo[0]["proximo"]}', '{$datatodo[0]["manana"]}')");
                        }
                    } else {
                        parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ({$consulta2[0]["usuario"]}, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                    }
                }
                // if ($consulta2[0]["status"] == 2) {
                //     parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 year) where id = {$consulta2[0]["usuario"]}");
                // }
                $res = $this->upgradePlan($consulta2[0]["usuario"], $consulta2[0]["plan"], $consulta2[0]["precio_usd"]);
            }} else {
            return array("status" => "error", "message" => "No encontramos pagos pendientes asociados");
        }
        parent::cerrar();
        return $res;
    }

    public function pagarSuperiores($usuario, $precioUSDOrden = 0)
    {
        parent::conectar();
        // echo "pagarSuperiores ($usuario) \n";
        $lista = parent::consultaTodo("select * from pagoBinario where usuario = $usuario and (montoPendiente > 0 or puntosPendientes > 0)");
        // print_r($lista);
        if (count($lista) > 0) {
            for ($i = 0; $i < count($lista); $i++) {
                $this->obtenerPataMasCorta($lista[$i]["asociado"], $precioUSDOrden);
            }
        } else {
            // echo "No enontre a nadie por sobre el ID #$usuarios";
            $respuesta = array('status' => 'error', 'message' => 'El usuario no existe');
        }
        return "termino";

    }

    public function obtenerPataMasCorta($usuario, $precioUSDOrden = 0)
    {
        parent::conectar();
        echo "obtenerPataMasCorta ($usuario) \n";
        echo "select * from nodos where usuario = $usuario";
        $datosUsuario = parent::consultaTodo("select * from nodos where usuario = $usuario");
        if (count($datosUsuario) == 0) {
            // echo "no encontre datos para este usuario";
            return array('status' => 'error', 'message' => 'usuario no encontrado');
        } else {
            if ($datosUsuario[0]["derecha"] != null && $datosUsuario[0]["izquierda"] != null) {
                $derecha   = $this->obtenerPuntajePorPata($datosUsuario[0]["derecha"], $usuario, $precioUSDOrden);
                $izquierda = $this->obtenerPuntajePorPata($datosUsuario[0]["izquierda"], $usuario, $precioUSDOrden);
                echo "Derecha ==> " . $datosUsuario[0]["derecha"] . ", " . $derecha["puntaje"] . "  \n";
                // print_r($derecha["usuarios"]);
                echo "izquierda ==> " . $datosUsuario[0]["izquierda"] . ", " . $izquierda["puntaje"] . "  \n";
                // print_r($izquierda["usuarios"]);
                if ($derecha["puntaje"] == 0 || $izquierda["puntaje"] == 0) {
                    // echo "No hay pagos pendientes \n";
                    return true;
                } else if ($derecha["puntaje"] < $izquierda["puntaje"]) {
                    // echo ("pagando el monto por la derecha de:" . $derecha["puntaje"] . ", puntaje pendiente por la izquierda: " . ($izquierda["puntaje"] - $derecha["puntaje"]) . " \n");
                    // console.log(derecha.usuarios);
                    $this->pagarPorPataCorta($usuario, $derecha["usuarios"], $izquierda["usuarios"], $precioUSDOrden, "derecha");

                    return true;
                } else {
                    // echo ("pagando el monto por la izquierda de:" . $izquierda["puntaje"] . ", puntaje pendiente por la derecha: " . ($derecha["puntaje"] - $izquierda["puntaje"]) . " \n");
                    // console.log(izquierda.usuarios);
                    $this->pagarPorPataCorta($usuario, $izquierda["usuarios"], $derecha["usuarios"], $precioUSDOrden, "izquierda");

                    return true;
                }

            } else {
                // echo "no hay pagos pendientes \n";
                return "no hay pagos pendientes";
            }
        }
    }

    public function apagarReconsumo($usuario)
    {
        parent::conectar();
        $pendientes = parent::consultaTodo("select ifnull(sum(puntosPendientes), 0) puntos, ifnull(sum(montoPendiente), 0) monto from pagoBinario where asociado = $usuario");
        parent::queryRegistro("insert into monto_perdidos (usuario, monto_perdido, puntos_perdido) values ($usuario, {$pendientes[0]["monto"]}, {$pendientes[0]["puntos"]})");
        parent::queryRegistro("update pagoBinario set montoPendiente = 0, puntosPendientes=0 where asociado = $usuario");
        return array("status" => "success", "puntos" => $pendientes[0]["monto"], "monto" => $pendientes[0]["puntos"]);

    }

    public function terminarMes()
    {
        parent::conectar();
        $usuarios = parent::consultaTodo("select count(*) reconsumos, usuario from planes_adquiridos pa
                    inner join (select * from usuarios where fecha_fin < CURRENT_DATE and status = 1) us on us.id = pa.usuario
                    where pa.fecha_adquisicion BETWEEN us.fecha_inicio and fecha_fin
                    group by usuario");
        $preciousd = $this->getUSDNodo();
        print_r($usuarios);
        for ($i = 0; $i < count($usuarios); $i++) {

            $this->apagarReconsumo($usuarios[$i]["usuario"]);
            $montoAPagar = $this->pagoUnilevelMonto($usuarios[$i]["usuario"]);
            echo "-----------------{$usuarios[$i]["usuario"]}-----------------{$montoAPagar["monto"]}---------------------------";
            if ($montoAPagar["monto"] > 0) {
                $montoPagado = $this->pagarPendientePorUsuario($montoAPagar["monto"], $usuarios[$i]["usuario"], $preciousd, "Pago por Unilevel");
                print_r($montoPagado);
            }

            parent::queryRegistro("insert into pagoUnilevel (usuario, montoUSD, precioUSD) values ({$usuarios[$i]["usuario"]}, {$montoAPagar["monto"]}, $preciousd)");

            if ($usuarios[$i]["reconsumos"] > 1) {
                parent::queryRegistro("update usuarios set status = 1, fecha_inicio = current_date, fecha_fin = date_add(CURRENT_DATE, interval 1 month) where id = {$usuarios[$i]["usuario"]}");
            } else {
                parent::queryRegistro("update usuarios set status = 2 where id = {$usuarios[$i]["usuario"]}");
            }
        }

        echo "-----------------MEMBRESIA----------------- \n";
        $membresias = parent::consultaTodo("select id, status, plan, if(fecha_fin < CURRENT_TIMESTAMP, 1, 0) termino, username from usuarios where status != 4 and id in (select usuario from (select max(fecha_limite) fecha_limite, usuario from pago_membresia group by usuario) a where fecha_limite < CURRENT_DATE)");
        for ($i = 0; $i < count($membresias); $i++) {
            if ($membresias[$i]["termino"] == 1) {
                parent::queryRegistro("update usuarios set status = 4 where id = {$membresias[$i]["id"]}");
                echo "Se acaba de activar el modo sin reconsumo ni membresia para el usuario {$membresias[$i]["username"]}\n";
            } else if ($membresias[$i]["status"] != 3) {
                parent::queryRegistro("update usuarios set status = 3 where id = {$membresias[$i]["id"]}");
                echo "Se acaba de activar el modo sin membresia para el usuario {$membresias[$i]["username"]}\n";
            }

        }
        return array("status" => "success");

    }

    public function terminarCursos()
    {
        parent::conectar();
        $usuarios = parent::consultaTodo("select r.cursos, u.id from usuarios u inner join paquetes r on r.id = u.plan;");
        // $preciousd = $this->getUSDNodo();
        // print_r($usuarios);
        for ($i = 0; $i < count($usuarios); $i++) {
            $this->agregarCursos($usuarios[$i]["id"], $usuarios[$i]["cursos"]);
        }
        return array("status" => "success");

    }

    public function obtenerPuntajePorPata($usuario, $principal)
    {
        // echo "obtenerPuntajePorPata ($usuario, $principal) \n";
        parent::conectar();
        $datos    = parent::consultaTodo("select * from pagoBinario where asociado = $usuario");
        $temporal = [];
        for ($i = 0; $i < count($datos); $i++) {
            $temporal[] = $datos[$i]["usuario"];
        }
        $usuarios = implode(",", $temporal);
        if ($usuarios == "") {
            $usuarios = 0;
        }
        // echo "select * from pagoBinario where asociado = $principal and usuario in ({$usuario},$usuarios) \n";
        $datos2 = parent::consultaTodo("select * from pagoBinario where asociado = $principal and usuario in ({$usuario},$usuarios)");
        $result = array("puntaje" => 0, "usuarios" => []);
        for ($i = 0; $i < count($datos2); $i++) {
            $result["puntaje"] += $datos2[$i]["puntosPendientes"];
            $result["usuarios"][] = $datos2[$i]["usuario"];
        }
        return $result;
    }

    public function agregarTodosLosTalentMS() {
    	parent::conectar();
        $dataUser = parent::consultaTodo("select u.id, u.idTalentlms, p2.cursos from usuarios u inner join paquetes p2 on p2.id = u.plan where idTalentlms is not null and cursos  != ''");
        for ($y=0;$y<count($dataUser); $y++) {
        	$anexo = explode(",", $dataUser[$y]["cursos"]);
	    	for ($i=0;$i<count($anexo);$i++) {
	    		parent::remoteRequest("https://testnet.foodsdnd.com/plataformaEducativa/api/controllers/curso/addCursoAlumno.php", array("id"=>$anexo[$i], "user_id"=>$dataUser[$y]["idTalentlms"]));
	    	}
        }
        
    }

    public function agregarCursos($usuario, $cursos)
    {

        parent::conectar();
        $dataUser = parent::consultaTodo("select * from usuarios where id = $usuario");
        $anexo = explode(",", $cursos);
        if ($cursos != "") {
        	for ($i=0;$i<count($anexo);$i++) {
	    		$PARAM1 = parent::remoteRequest("https://testnet.foodsdnd.com/plataformaEducativa/api/controllers/curso/addCursoAlumno.php", array("id"=>$anexo[$i], "user_id"=>$dataUser[0]["idTalentlms"]));
	    	}
        }
    	
        $grupoKey = '';
        $cursoTraing = '';

        if ($dataUser[0]['plan'] === '1' || $dataUser[0]['plan'] === '2' || $dataUser[0]['plan'] === '3') {
            for ($i = 1; $i <= (int) $dataUser[0]['plan']; $i++) { 
                if ($i === 1) {
                    $grupoKey = '6';
                    $cursoTraing = '4';
                } else if ($i === 2) {
                    $grupoKey = '8';
                    $cursoTraing = '5';
                } else if ($i === 3) {
                    $grupoKey = '10';
                    $cursoTraing = '6';
                }
                $data1 = array(
                    'group_id' => $grupoKey,
                    'user_id' => $dataUser[0]["idTalentlms"]
                );
                $data2 = array(
                    'id' => $cursoTraing,
                    'user_id' => $dataUser[0]["idTalentlms"]
                );
                /* print_r($data1); */
                $resul = parent::metodoPost('group/addGrupoAlumno.php', $data1);
                print_r($resul);
                parent::queryRegistro("insert into alumnos (id_block, usuario, grupo) values (1, $usuario, $grupoKey)");
            }
        }
        return true;
    }

    public function saveGroupTalentLMS($user, $key)
    {
        $api    = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
        $apiKey = base64_encode($api);
        $curl   = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://peers2win.talentlms.com/api/v1/addusertogroup/user_id:" . $user . ",group_key:" . $key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Basic " . $apiKey,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public function validarRango($usuario)
    {
        // echo "validarRango ($usuario) \n";
        parent::conectar();
        $sums = parent::consultaTodo("select sum(derecha) derecha, sum(izquierda) izquierda from (select count(derecha) derecha, 0 izquierda from nodos where derecha in (select a.id from usuarios a  inner join venta_directa b on b.usuarioPaga = a.id and b.puntosPagados > 0 where a.referido = $usuario group by a.id) union all select 0 derecha, count(izquierda) izquierda from nodos where izquierda in (select a.id from usuarios a  inner join venta_directa b on b.usuarioPaga = a.id  and b.puntosPagados > 0 where a.referido = $usuario group by a.id))b");
        // echo "imprimiendo paso 1";
        if (count($sums) == 0) {
            return false;
        } else {
            if ($sums[0]["derecha"] > 0 && $sums[0]["izquierda"] > 0) {
                $results = parent::consultaTodo("select ifnull(sum(puntos), 0) puntos, ifnull(sum(total), 0) total from (select sum(puntos) puntos, 0 total from puntajeBinario p inner join usuarios u on u.id = p.usuarioRecibe where usuarioRecibe = $usuario and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin union  select 0 puntos, count(*) total from (select * from venta_directa where usuarioRecibe = $usuario and puntosPagados > 0 group by usuarioPaga) c) b");
                // print_r($results);
                if (count($results) == 0) {
                    return false;
                } else {
                    $results2 = parent::consultaTodo("select * from rangos where puntos <= " . $results[0]["puntos"] . " and directos <= " . $results[0]["total"] . " order by id desc limit 1");
                    // print_r($results2[0]);
                    if (count($results2) == 0) {
                        // echo ("no tiene rango");
                        return false;
                    } else {
                        // echo "probando rangos";
                        $results3 = parent::consultaTodo("select * from usuarios where id = $usuario");
                        // print_r($results3[0]);
                        if ($results3[0]["rango"] < $results2[0]["id"]) {
                            // print_r(array("data"=>$results2[0]["rangoscalificables"]));
                            // echo "imprimir datos";
                            if ($results2[0]["rangoscalificables"] != "") {
                                $result4 = parent::consultaTodo("select sum(if(rango>=1, 1, 0)) rango1, sum(if(rango>=2, 1, 0)) rango2, sum(if(rango>=3, 1, 0)) rango3, sum(if(rango>=4, 1, 0)) rango4, sum(if(rango>=5, 1, 0)) rango5, sum(if(rango>=6, 1, 0)) rango6 from usuarios where referido = $usuario;");
                                // print_r($result4);
                                $rc = explode(",", $results2[0]["rangoscalificables"]);
                                $nr = explode(",", $results2[0]["necesariosrango"]);
                                // print_r($rc);
                                // print_r($nr);
                                // echo count($rc);
                                for ($i = 0; $i < count($rc); $i++) {
                                    // echo $result4[0]["rango" . $rc[$i]]."\n";
                                    // echo "rango" . $rc[$i];
                                    if (($nr[$i] * 1) <= ($result4[0]["rango" . $rc[$i]] * 1)) {
                                        $j = $i + 0;
                                        for ($j; $j < count($nr); $j++) {
                                            $nr[$j] = ($nr[$j] * 1) - $result4[0]["rango" . $rc[$i]] * 1;
                                        }
                                        // print_r($nr);
                                    } else {
                                        return false;
                                    }
                                }
                                // return false;

                            }

                            // echo "subio de rango {$results3[0][rango]} ===> "+$results2[0]["id"];
                            parent::queryRegistro("update usuarios set rango = " . $results2[0]["id"] . " where id = $usuario");
                            $this->insertarNotificacion($usuario, "Has subido con éxito al rango {$results2[0]["nombre"]}", "rango");
                            return true;
                        } else {
                            // echo "No puede subir mas...";
                            return false;
                        }
                    }
                }
            } else {
                return true;
            }
        }
    }

    public function maximoDisponibleCobro($usuario)
    {
        // echo "maximoDisponibleCobro ($usuario) \n";
        $results = parent::consultaTodo("select ifnull(sum(b.monto), 0) monto, c.gananciaMaxima, if(ifnull(sum(b.monto), 0)<c.gananciaMaxima, c.gananciaMaxima - ifnull(sum(b.monto), 0), 0) maximoCobro from usuarios a
            inner join puntajeBinario b on b.usuarioRecibe = a.id
            inner join rangos c on c.id = a.rango
            where a.id = $usuario");
        if (count($results) == 0) {
            return 0;
        } else {
            return $results[0]["maximoCobro"];
        }
    }

    public function getUSDNodo()
    {
        $nodo      = new Nodo();
        $preciousd = $nodo->precioUSD();
        return $preciousd;
    }

    public function verificarPagosPendientes($usuario, $address, $monto)
    {
        parent::conectar();
        $montoEnviar = $monto;
        $results     = parent::consultaTodo("select * from pagosPendientes where address = '$address'");
        for ($i = 0; $i < count($results); $i++) {
            $montoEnviar += $results[$i]["monto"];
        }
        if ($montoEnviar < 0.000006) {
            parent::queryRegistro("insert into pagosPendientes (address, monto, usuario) values ('$address', $monto, $usuario)");
            return 0;
        } else {
            parent::queryRegistro("delete pagosPendientes where address = '$address'");
            return $montoEnviar;
        }

    }

    public function notificacion($notifica)
    {
        $this->insertarNotificacion($notifica['user'], $notifica['mensaje'], $notifica['tipo']);
        parent::cerrar();
        return $respues = array('status' => 'success', 'message' => 'La notificacion se ingreso');
    }

    public function insertarNotificacion($usuario, $mensaje, $tipo)
    {
        parent::conectar();
        parent::queryRegistro("insert into notificaciones_usuario (usuario, mensaje, tipo) values ($usuario, '$mensaje', '$tipo')");
        return true;
    }

    public function pagarPendientePorUsuario($montoUSD, $usuario, $precioUSDOrden = 0, $mensaje = "Pago peer2win")
    {
        $addressPagoFinal = "2N3uFJwiUMAPLpzPHxzT5mYzKWUNLXjX6w3";
        $nodo             = new Nodo();
        $preciousd        = $precioUSDOrden;
        if ($precioUSDOrden == 0) {
            $preciousd = $nodo->precioUSD();
        }
        if ($preciousd < $precioUSDOrden) {
            $preciousd = $precioUSDOrden;
        }

        $montosEnvio = number_format(($montoUSD / $preciousd), 6);
        parent::conectar();
        $results   = parent::consultaTodo("select * from address_btc where usuario = $usuario");
        $wallet    = "";
        $reconsumo = "";
        for ($i = 0; $i < count($results); $i++) {
            if (strtoupper($results[$i]["label"]) == "WALLET_$usuario") {
                $wallet = $results[$i]["address"];
            } else if (strtoupper($results[$i]["label"]) == "RECONSUMO_$usuario") {
                $reconsumo = $results[$i]["address"];
            }
        }

        $rango          = parent::consultaTodo("select u.username, r.* from rangos r inner join usuarios u on u.rango = r.id where u.id = $usuario");
        $montoPagoEmet  = number_format($montosEnvio * 0.01, 6);
        $montosEnvio    = number_format($montosEnvio - $montoPagoEmet, 6);
        $montoreconsumo = number_format($montosEnvio * $rango[0]["descuento"], 6);
        $montowallet    = number_format($montosEnvio - $montoreconsumo, 6);

        $montoPagoEmet  = $this->verificarPagosPendientes("0", $addressPagoFinal, $montoPagoEmet);
        $montoreconsumo = $this->verificarPagosPendientes($usuario, $reconsumo, $montoreconsumo);
        $montowallet    = $this->verificarPagosPendientes($usuario, $wallet, $montowallet);

        // echo $reconsumo;
        if ($montoPagoEmet > 0) {
            $send["$addressPagoFinal"] = $montoPagoEmet;
        }
        if ($montowallet > 0) {
            $send["$wallet"] = $montowallet;
        }

        if ($montoreconsumo > 0) {
            $send["$reconsumo"] = $montoreconsumo;
        }
        print_r($send);
        $txhash = $nodo->sendMultiTransaction(array("address_from" => "2N7PTwxKQEFx1JAmNfxgHeYuXtWdQwkavTD", "address_to" => $send, "reason" => $mensaje, "usuario" => "0"));
        $this->insertarNotificacion($usuario, "Se ha realizado con éxito un $mensaje a tu cuenta, recuerda verificar el saldo de tu billetera", "pago");

        return array("montoWallet" => $montowallet, "montoreconsumo" => $montoreconsumo, "montoEmet" => $montoPagoEmet, "txhash" => $txhash, "precio" => $preciousd);

    }

    public function pagarPorPataCorta($principal, $usuarios, $usuarios2, $precioUSDOrden = 0, $pata)
    {
        parent::conectar();
        $usuario1 = implode(",", $usuarios);
        $usuario2 = implode(",", $usuarios2);
        echo "pagarPorPataCorta ($principal, $usuario1, $usuario2) \n";
        $usuarioPadre = parent::consultaTodo("select a.id, b.pago_binario from usuarios a inner join paquetes b on b.id = a.plan where a.id = $principal");
        if (count($usuarioPadre) == 0) {
            return "no existe el usuario";
        } else {
            $this->validarRango($principal);
            $tempUser   = implode(",", $usuarios);
            $results    = parent::consultaTodo("select b.username, a.* from pagoBinario a inner join usuarios b on b.id = a.usuario where a.asociado = $principal and a.usuario in ($tempUser)");
            $monto      = 0;
            $actualizar = [];
            // $agregar    = "insert into puntajeBinario (usuarioPaga, usuarioRecibe, monto, puntos, montowallet, montoreconsumo, txhash, preciomomento) values ";
            $agregar = "insert into puntajeBinario (usuarioPaga, usuarioRecibe, monto, puntos, pata) values ";
            echo "pagando pata corta";
            print_r($results);
            for ($i = 0; $i < count($results); $i++) {
                if ($results[$i]["puntosPendientes"] > 0) {
                    $pagoPorUsuario = $this->maximoDisponibleCobro($principal);
                    $pagar          = $results[$i]["montoPendiente"];
                    if ($pagar > $pagoPorUsuario) {
                        $montoNoPagado = number_format($pagar - $pagoPorUsuario, 6);
                        parent::queryRegistro("insert into montosPerdidos (usuario, monto) values ($principal, $montoNoPagado)");
                        $pagar = $pagoPorUsuario;
                        // echo "Ya excedio el pago maximo que puede tener por su rango";
                    }
                    if ($pagar > 0) {
                        $pendientes = $this->pagarPendientePorUsuario($pagar, $results[$i]["asociado"], $precioUSDOrden, "Pago binario: {$pata}");
                    }

                    // $agregar .= "(" . $results[$i]["usuario"] . ", $principal, $pagar, " . $results[$i]["puntosPendientes"] . ", {$pendientes["montoWallet"]}, {$pendientes["montoreconsumo"]}, '{$pendientes["txhash"]}', {$pendientes["precio"]})";
                    $agregar .= "(" . $results[$i]["usuario"] . ", $principal, $pagar, " . $results[$i]["puntosPendientes"] . ", '$pata')";
                    $monto += $results[$i]["puntosPendientes"];
                    $actualizar[] = $results[$i]["id"];
                }
            }
            // echo "MONTO: ".$monto;
            if ($monto > 0) {
                $montoTemp = $monto;
                $results2  = parent::consultaTodo("select * from pagoBinario where asociado = $principal and usuario in (" . implode(",", $usuarios2) . ")");
                for ($i = 0; $i < count($results2); $i++) {
                    if ($results2[$i]["puntosPendientes"] >= $montoTemp) {
                        $string    = "update pagoBinario set puntosPendientes =" . ($results2[$i]["puntosPendientes"] - $montoTemp) . ", montoPendiente = " . (($results2[$i]["puntosPendientes"] - $montoTemp) * $usuarioPadre[0]["pago_binario"]) . " where id =" . $results2[$i]["id"];
                        $montoTemp = 0;
                        parent::queryRegistro($string);
                    }
                    if ($results2[$i]["puntosPendientes"] < $montoTemp) {
                        $string = "update pagoBinario set puntosPendientes = 0, montoPendiente = 0 where id =" . $results2[$i]["id"];
                        $montoTemp -= $results2[$i]["puntosPendientes"];
                        parent::queryRegistro($string);
                    }
                    if ($montoTemp == 0) {
                        break;
                    }
                }

                $agregar = implode("), (", explode(")(", $agregar));
                // echo $agregar;
                parent::queryRegistro($agregar);
                $results  = parent::queryRegistro("update pagoBinario set montoPendiente = 0, puntosPendientes = 0 where id in (" . implode(",", $actualizar) . ")");
                $string   = "select usuarioRecibe, sum(monto) monto, sum(puntos) puntos from puntajeBinario where usuarioRecibe = $principal group by usuarioRecibe";
                $results4 = parent::consultaTodo($string);
                $results5 = parent::consultaTodo("select * from rangos where puntos <= " . $results4[0]["puntos"] . " order by puntos desc limit 1");
                // echo "No hay pagos pendientes, el acumulado en puntos es de:" . $results4[0]["puntos"] . " perteneciente al " . $results5[0]["nombre"] . "\n";
                return "No hay pagos pendientes, el acumulado en puntos es de:" . $results4[0]["puntos"] . " perteneciente al " . $results5[0]["nombre"];
            } else {
                // console.log("no existen pagos pendientes");
                $string   = "select usuarioRecibe, sum(monto) monto, sum(puntos) puntos from puntajeBinario where usuarioRecibe = $principal group by usuarioRecibe";
                $results4 = parent::consultaTodo($string);
                if (count($results4) == 0) {
                    // echo "no se pago nada \n";
                    return "no se pago nada";
                } else {
                    $results5 = parent::consultaTodo("select * from rangos where puntos <= " . $results4[0]["puntos"] . " order by puntos desc limit 1");
                    // echo "No hay pagos pendientes, el acumulado en puntos es de:" . $results4[0]["puntos"] . " perteneciente al " . $results5[0]["nombre"] . "\n";
                    return ("No hay pagos pendientes, el acumulado en puntos es de:" . $results4[0]["puntos"] . " perteneciente al " . $results5[0]["nombre"]);
                }
            }
        }
    }

    public function upgradePlan($usuario, $plan, $precioUSDOrden = 0)
    {
        parent::conectar();
        $results = parent::consultaTodo("select * from usuarios where id = $usuario");
        if (count($results) == 0) {
            // echo "El usuario no existe";
            // parent::cerrar();
            return array("status" => "error", "message" => "El usuario no existe");
            // return ("El usuario no existe");
        } else {
            $planad = $this->obtenerDetallePlan($plan);
            parent::conectar();
            if ($planad[0]["paquete_inicial"] == null) {
                // echo "Ya estas en el mejor plan que podemos ofrecer";
                // parent::cerrar();
                return array("status" => "error", "message" => "Ya estas en el mejor plan que podemos ofrecer");
            } else {
                if ($planad[0]["paquete_inicial"] != $results[0]["plan"]) {
                    // echo "No puedes adquirir este plan";
                    // parent::cerrar();
                    return array("status" => "error", "message" => "No puedes adquirir este plan");
                } else {
                    if ($results[0]["status"] == "2") {
                        parent::queryRegistro("update usuarios set status = 1 where id = {$results[0]["id"]}");
                        $this->agregarRegistroMensual($usuario, 2);
                    }
                    $results[0]["status"] = "1";
                    $results3             = parent::consultaTodo("select $usuario 'id', a.id asociado, a.username nombre_asociado, (c.montoPendiente + (d.puntos * b.pago_binario)) puntaje, d.puntos, d.entradas300, d.entradas500, d.entradas1000  from usuarios a inner join paquetes b on b.id = a.plan inner join pagoBinario c on c.asociado = a.id and c.usuario = $usuario inner join paquetes d on d.id = $plan where a.id in (select asociado from pagoBinario where usuario = $usuario) and status = 1");
                    for ($i = 0; $i < count($results3); $i++) {
                        $string = "update pagoBinario set montoPendiente = {$results3[$i]["puntaje"]}, puntosPendientes = puntosPendientes + {$results3[$i]["puntos"]} where usuario = {$results3[$i]["id"]} and asociado = {$results3[$i]["asociado"]}";
                        parent::queryRegistro($string);
                    }
                    $str2 = "update usuarios set plan = {$planad[0]["paquete_final"]} where id = $usuario";
                    parent::queryRegistro($str2);
                    $this->agregarPlanAdquirido($usuario, $plan);
                    $this->insertarNotificacion($usuario, "Se ha realizado con éxito el upgrade a plan {$planad[0]["nombre"]}", "adquirir plan");

                    // echo ("---------- Monto del usuario " . $results3[0]["nombre_asociado"] . "  ---------");
                    $this->pagarSuperiores($usuario, $precioUSDOrden);
                    // parent::cerrar();
                    return array("status" => "success", "message" => "Se han actualizado correctamente los beneficios de tu plan");
                }
            }
        }
    }

    public function agregarPlanAdquirido($usuario, $plan)
    {
        parent::conectar();
        $results = parent::consultaTodo("select * from paquetes where id= $plan");
        $status  = 1;
        if ($results[0]["tipo"] == 1) {
            $status = 1;
            $this->agregarCursos($usuario, $results[0]["cursos"]);
        } else if ($results[0]["tipo"] == 2) {
            $status = 3;
        } else {
            $status = 2;
            $this->agregarCursos($usuario, $results[0]["cursos"]);
        }
        if ($results[0]["entradas300"] != 0) {
            $this->agregarTickets($usuario, $results[0]["entradas300"], 300, $status);
        }
        if ($results[0]["entradas500"] != 0) {
            $this->agregarTickets($usuario, $results[0]["entradas500"], 500, $status);
        }
        if ($results[0]["entradas1000"] != 0) {
            $this->agregarTickets($usuario, $results[0]["entradas1000"], 1000, $status);
        }
        if ($results[0]["entradas5000"] != 0) {
            $this->agregarTickets($usuario, $results[0]["entradas5000"], 5000, $status);
        }
        parent::queryRegistro("insert into planes_adquiridos (usuario, plan_adquirido) values ($usuario, $plan)");
        return (true);
    }

    public function agregarTickets($usuario, $tickets, $tipo, $status, $usuarioEnvia = null)
    {
        // echo ".".$tipo."--";
        if ($tipo != 300 && $tipo != 500 && $tipo != 1000 && $tipo != 5000) {
            /* resolve("No se puede agregar este tipo de tickets"); */
            return false;
        } else {
            parent::conectar();
            $consultar1 = "select * from usuarios where id= '$usuario'";
            // echo "select * from usuarios where id= '$usuario'";
            $lista = parent::consultaTodo($consultar1);
            if (count($lista) == 0) {
                /* console.log("el usuario al que tratas de agregar entradas no existe"); */
                return false;
            } else {
                $consultar2 = "update usuarios set entradas$tipo = {$lista[0]["entradas" . $tipo]} + $tickets where id= " . $lista[0]['id'];
                // echo "update usuarios set entradas$tipo = {$lista[0]["entradas" . $tipo]} + $tickets where id= " . $lista[0]['id'];
                parent::query($consultar2);
                // echo "ticketes .$tickets";
                for ($i = 0; $i < ($tickets * 1); $i++) {
                    $consultar3 = "insert into historico_tickets (tipo, usuario, cantidad, descripcion, transferido, codigo, fecha) values ('entradas" . $tipo . "', " . $lista[0]['id'] . ", 1, '" . ($status == 1 ? "Compra de plan" : ($status == 2 ? "Upgrade" : ($status == 3 ? "reconsumo" : "transferidos"))) . "', " . ($status != 4 ? "null" : "'" . $usuarioEnvia . "'") . ", sha1(concat('entradas" . $tipo . "', {$lista[0]['id']}, CURRENT_TIMESTAMP, $i)), CURRENT_TIMESTAMP + INTERVAL 2 month)";
                    // echo $consultar3."\n";
                    parent::query($consultar3);

                }
                // $consultar3 = "insert into historico_tickets (tipo, usuario, cantidad, descripcion, transferido, codigo, fecha) values ('entradas" . $tipo . "', " . $lista[0]['id'] . ", " . $tickets . ", '" . ($status == 1 ? "Compra de plan" : ($status == 2 ? "Upgrade" : ($status == 3 ? "reconsumo" : "transferidos"))) . "', " . ($status != 4 ? "null" : "'" . $usuarioEnvia . "'") . ", sha1(concat('entradas" . $tipo . "', {$lista[0]['id']}, CURRENT_TIMESTAMP)), CURRENT_TIMESTAMP + INTERVAL 1 month)";
                // print_r($consultar3);
                // parent::query($consultar3);
                return true;
            }
        }
    }
    public function obtenerDetallePlan($paquete)
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "select * from paquetes where id = $paquete";
        $respuesta  = parent::consultaTodo($consultar1);
        parent::cerrar();
        return $respuesta;
    }

    public function obtenerPlanes()
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "select * from paquetes where tipo in (1,5) order by puntos asc";
        $respuesta  = parent::consultaTodo($consultar1);
        parent::cerrar();
        return $respuesta;
    }

    public function obtenerReconsumos()
    {
        parent::conectar();
        $respuesta;
        $consultar1 = "select * from paquetes where tipo = 2 order by puntos asc";
        $respuesta  = parent::consultaTodo($consultar1);
        parent::cerrar();
        return array("status" => "success", "data" => $respuesta, "membresia" => $this->obtenerDetallePlan(10)[0]);
    }

    public function agregarPlan($usuario, $plan, $precioUSDOrden = 0)
    {
        echo "Entro a agregarPlan \n";
        $detallePlan = $this->obtenerDetallePlan($plan);
        if ($detallePlan[0]["tipo"] == 1 || $plan == 10) {
            // echo "no puedes aplicar a este plan \n";
        } else {
            return ("No puedes aplicar a este plan");
        }
        parent::conectar();
        $results = parent::consultaTodo("select * from usuarios where id = $usuario");
        if (count($results) == 0) {
            // echo "El usuario no existe \n";
            // parent::cerrar();
            return array("status" => "error", "message" => "El usuario no existe");
        } else {
            $this->agregarNuevoUsuario($usuario);
            if ($results[0]["plan"] != 10) {
                // echo "No puedes adquirir un nuevo plan, debes hacer updrage \n";
                // parent::cerrar();
                return array("status" => "error", "message" => "No puedes adquirir un nuevo plan, debes hacer updrage");
                // return ("No puedes adquirir un nuevo plan, debes hacer updrage");
            } else {

                $results3    = parent::consultaTodo("select $usuario 'id', a.id asociado, a.username nombre_asociado, (c.montoPendiente + (d.puntos * b.pago_binario)) puntaje, if(b.puntos = 0, 0, d.puntos) puntos, d.entradas300, d.entradas500, d.entradas1000, (d.monto * b.venta_directa) montoVentaDirecta  from usuarios a inner join paquetes b on b.id = a.plan inner join pagoBinario c on c.asociado = a.id and c.usuario = $usuario inner join paquetes d on d.id = $plan where a.id in (select asociado from pagoBinario where usuario = $usuario) and status = 1");
                
                parent::conectar();
                if ($plan != 10) {
                    $pagoDirecto = parent::consultaTodo("select u.username, p.* from paquetes p inner join usuarios u on u.plan = p.id where u.id = {$results[0]["referido"]}");
                    $str3        = "insert into venta_directa (usuarioPaga, usuarioRecibe, puntosPagados, montoPagado) values ($usuario, {$results[0]["referido"]}, {$detallePlan[0]["puntos"]}, " . number_format($pagoDirecto[0]["venta_directa"] * $detallePlan[0]["monto"], 6) . ")";

                    $this->pagarPendientePorUsuario(number_format($pagoDirecto[0]["venta_directa"] * $detallePlan[0]["monto"], 6), $results[0]["referido"], $precioUSDOrden, "Pago venta directa: {$results[0]["username"]}");
                    parent::queryRegistro($str3);
                }

                for ($i = 0; $i < count($results3); $i++) {
                    parent::conectar();
                    $string = "update pagoBinario set montoPendiente =  {$results3[$i]["puntaje"]}, puntosPendientes = puntosPendientes + {$results3[$i]["puntos"]} where usuario = {$results3[$i]["id"]}  and asociado = {$results3[$i]["asociado"]}";
                    parent::queryRegistro($string);
                    // echo ("---------- Monto del usuario {$results3[$i]["nombre_asociado"]}  ---------");
                    if ($plan != 10) {
                        $this->obtenerPataMasCorta($results3[$i]["asociado"], $precioUSDOrden);
                    }
                }
                // echo "update usuarios set plan = $plan, fecha_inicio = CURRENT_DATE, fecha_fin = CURRENT_DATE + interval 1 month,  where id = $usuario";
                $str2 = "update usuarios set plan = $plan where id = $usuario";
                $this->agregarRegistroMensual($usuario, 1);
                parent::queryRegistro($str2);
                if ($plan != 10) {
                    $this->agregarPlanAdquirido($usuario, $plan);
                    //$this->agregarTicketsArticulo($usuario, $plan);
                    $this->insertarNotificacion($usuario, "Se ha realizado con éxito tu adquisición del plan {$detallePlan[0]["nombre"]}", "adquirir plan");
                } else {
                    $this->insertarNotificacion($usuario, "Se ha realizado con éxito tu adquisición del plan {$detallePlan[0]["nombre"]}", "adquirir plan");
                }
                // echo "Termino el registro \n";
                // parent::cerrar();
                
                // echo ("pagado el monto de {$results3[$i]["montoVentaDirecta"]} al usuario {$results3[$i]["asociado"]} por concepto de venta directa \n");

                return array("status" => "success", "message" => "Se pudo agregar el plan al usuario");
                // return ("concretado");
            }
        }

    }


    function agregarTicketsArticulo($usuario, $plan) {
    	/*1
				"uid": user.id,
		        "tipo": $("#my-tickets").val(),
		        "cantidad": $("#total-tick").val(),
		        "uso": 1,
		        "transferido": "null",
		        "empresa": 0
    	*/
        if ($plan == "11") {
        	$PARAM1 = parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas300", "cantidad"=>"5", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	$param2 = parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas500", "cantidad"=>"3", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        } else if ($plan == "1") {
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas300", "cantidad"=>"5", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas500", "cantidad"=>"3", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas1000", "cantidad"=>"2", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        } else if ($plan == "2") {
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas300", "cantidad"=>"5", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas500", "cantidad"=>"3", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas1000", "cantidad"=>"2", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas5000", "cantidad"=>"1", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        } else if ($plan == "3") {
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas300", "cantidad"=>"5", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas500", "cantidad"=>"3", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas1000", "cantidad"=>"2", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        	parent::remoteRequest("https://nasbi.peers2win.com/api/controllers/planes_nasbi/?insertar_ticket_p2w", array("data"=>array("uid"=>$usuario, "tipo"=>"entradas5000", "cantidad"=>"2", "uso"=>"1", "transferido"=>"null", "empresa"=>0)));	
        }
    	

    }




    public function buscarUltimoPosicion($usuario, $posicion)
    {
        // console.log(usuario, posicion, "select id, " + posicion + " from nodos where usuario = " + usuario);
        $consultarr = "select id, " . $posicion . " from nodos where usuario = " . $usuario;
        $results    = parent::consultaTodo($consultarr);
        if ($results[0][$posicion] == null) {
            return $usuario;
        } else {
            // console.log(results[0][posicion] );
            return $this->buscarUltimoPosicion($results[0][$posicion], $posicion);
        }
    }

    public function agregarNuevoUsuario($usuario)
    {
        parent::conectar();
        $existeEnNodo = parent::consultaTodo("select * from nodos where derecha= $usuario or izquierda = $usuario");
        if (count($existeEnNodo) > 0) {
            return ("Existe en nodos");
        } else {
            $usuariosExisten = parent::consultaTodo("select a.id, a.username, a.status, a.plan, (b.puntos * b.pago_binario) monto,referido, posicion from usuarios a inner join paquetes b on b.id = a.plan where a.id= $usuario");
            if (count($usuariosExisten) == 0) {
                return ("No existen usuarios");
            } else {
                parent::queryRegistro("insert into pago_membresia (usuario, fecha_limite, fecha_adquisicion) values ($usuario, date_add(CURRENT_TIMESTAMP, interval 1 year), current_timestamp)");
                $posicion = $usuariosExisten[0]["posicion"];
                $padre    = $this->buscarUltimoPosicion($usuariosExisten[0]["referido"], $posicion);
                for ($i = 0; $i < count($usuariosExisten); $i++) {
                    $results       = parent::consultaTodo("SELECT * from pagoBinario where usuario = $padre");
                    $insert_values = "insert into pagoBinario (usuario, asociado, montoPendiente) values ";
                    if (count($results) > 0) {
                        for ($j = 0; $j < count($results); $j++) {
                            $insert_values .= "({$usuariosExisten[$i]["id"]}, {$results[$j]["asociado"]}, {$usuariosExisten[$i]["monto"]})";
                        }
                    }
                    // print_r($usuariosExisten[$i]);
                    // print_r($padre);
                    $insert_values .= "({$usuariosExisten[$i]["id"]}, $padre, {$usuariosExisten[$i]["monto"]})";
                    $insert_values = implode("),(", explode(")(", $insert_values));
                    // print_r($insert_values);
                    parent::queryRegistro($insert_values);
                    $str = "update nodos set $posicion = $usuario where usuario = $padre";

                    $this->insertarNotificacion($usuariosExisten[0]["referido"], "Tu referido {$usuariosExisten[0]["username"]} ha sido agregado correctamente a tu arbol binario", "referido");
                    // print_r($insert_values);
                    // print_r($str);

                    parent::queryRegistro($str);
                }
                return ("agregado correctamente el usuario");
                // console.log(usuariosExisten[0]);
            }
        }
    }
}
