<?php
    // require 'conexion.php';
    require 'Nodo.php';

    class Users extends Conexion
    {
        
        public function Date($user)
        {
            
            $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $user);
            $respuesta = json_decode($response, true);
            // print_r($resp);
            return $resp;
        }

        public function getEventos() {
            parent::conectar();   
            $consulta2   = parent::consultaTodo("select * from eventos e ");
            $data = array();
            for ($i=0;$i<count($consulta2);$i++) {
                $data[$consulta2[$i]["fecha_evento"]][] = array("titulo"=>$consulta2[$i]["titulo_evento"], "descripcion"=>$consulta2[$i]["descripcion_evento"]);
            }
            return $data;
        }
        
        public function getTicketsParaArticulos($user) {
            parent::conectar();   
            $data = array(
                "entradas300"=>array("ticket"=>"entradas300", "cantidad"=>0, "nombre"=> "Bronze"),
                "entradas500"=>array("ticket"=>"entradas500", "cantidad"=>0, "nombre"=> "Silver"),
                "entradas1000"=>array("ticket"=>"entradas1000", "cantidad"=>0, "nombre"=> "Gold"),
                "entradas5000"=>array("ticket"=>"entradas5000", "cantidad"=>0, "nombre"=> "Platinum")
            );
            $consulta2   = parent::consultaTodo("select tipo, sum(cantidad) cantidad from historico_tickets where usuario  = $user and fecha > CURRENT_DATE group by tipo");
            for ($i=0;$i<count($consulta2);$i++) {
                $data[$consulta2[$i]["tipo"]]["cantidad"] = $consulta2[$i]["cantidad"] * 1;
            }
            return $data;
        }
        
        public function verMisWallets($user)
        {
            parent::conectar();
            $wallets_btc = [];
            $wallets_ebg = [];
            $consulta2   = parent::consultaTodo("select * from address_btc where usuario = $user");
            $consulta3   = parent::consultaTodo("select * from address_ebg where usuario = $user");
            if ($user == 0) {
                for ($i = 0; $i < count($consulta2); $i++) {
                    $wallets_btc[] = array("label" => $consulta2[$i]["label"], "address" => $consulta2[$i]["address"], "direccion" => $consulta2[$i]["address"], "saldo" => $consulta2[$i]["saldo"], "simbolo" => "BTC");
                }
                for ($i = 0; $i < count($consulta3); $i++) {
                    $wallets_ebg[] = array("label" => $consulta3[$i]["label"], "address" => $consulta3[$i]["address"], "direccion" => $consulta3[$i]["address"], "saldo" => $consulta3[$i]["saldo"], "simbolo" => "EBG");
                }
            } else {
                for ($i = 0; $i < count($consulta2); $i++) {
                    $label = explode("_", $consulta2[$i]["label"])[0];
                    if (strtoupper($label) == "RECONSUMO") {
                        $label = "Wallet 2 BUY";
                    } else {
                        $label = "Wallet 2 WIN";
                    }
                    $wallets_btc[] = array("label" => $label, "address" => $consulta2[$i]["address"], "direccion" => $consulta2[$i]["address"], "saldo" => $consulta2[$i]["saldo"], "simbolo" => "BTC");
                }
                for ($i = 0; $i < count($consulta3); $i++) {
                    $wallets_ebg[] = array("label" => explode("_", $consulta3[$i]["label"])[0], "address" => $consulta3[$i]["address"], "direccion" => $consulta3[$i]["address"], "saldo" => $consulta3[$i]["saldo"], "simbolo" => "EBG");
                }
            }
            
            $nodo = new Nodo();
            parent::cerrar();
            return array("status" => "success", "wallets_btc" => $wallets_btc, "wallets_ebg" => $wallets_ebg, "precioBTC" => $nodo->precioUSD(), "precioEBG" => $nodo->precioUSDEBG());
            
        }
        
        public function agregarCodigoTickets($user) {
            parent::conectar();
            $consulta2 = parent::consultaTodo("select concat('P2W',sha1(concat_ws('$user', 'entradas300',CURRENT_TIMESTAMP))) entradas300, concat('P2W',sha1(concat_ws('$user', 'entradas500',CURRENT_TIMESTAMP))) entradas500, concat('P2W',sha1(concat_ws('$user', 'entradas1000',CURRENT_TIMESTAMP))) entradas1000, concat('P2W',sha1(concat_ws('$user', 'entradas5000',CURRENT_TIMESTAMP))) entradas5000");
            // print_r("select concat('P2W',sha1(concat_ws('$user', 'entradas300',CURRENT_TIMESTAMP))) entradas300, concat('P2W',sha1(concat_ws('$user', 'entradas500',CURRENT_TIMESTAMP))) entradas500, concat('P2W',sha1(concat_ws('$user', 'entradas1000',CURRENT_TIMESTAMP))) entradas1000, concat('P2W',sha1(concat_ws('$user', 'entradas5000',CURRENT_TIMESTAMP))) entradas5000");
            // print_r("insert into wallet_tickets (usuario, tipo_wallet, qr) values ($user, 'entradas300', '{$consulta2[0]["entradas300"]}'), ($user, 'entradas500', '{$consulta2[0]["entradas500"]}'), ($user, 'entradas1000', '{$consulta2[0]["entradas1000"]}'), ($user, 'entradas5000', '{$consulta2[0]["entradas5000"]}')");
            parent::queryRegistro("insert into wallet_tickets (usuario, tipo_wallet, qr) values ($user, 'entradas300', '{$consulta2[0]["entradas300"]}'), ($user, 'entradas500', '{$consulta2[0]["entradas500"]}'), ($user, 'entradas1000', '{$consulta2[0]["entradas1000"]}'), ($user, 'entradas5000', '{$consulta2[0]["entradas5000"]}')");
            
        }
        
        public function verMisTickets($usuario)
        {
            parent::conectar();
            $nodo     = new Nodo();
            $datatodo = parent::consultaTodo("select * from wallet_tickets where usuario = $usuario");
            if (count($datatodo) == 0) {
                return array("status" => "error", "data" => array(), "precioBTC" => $nodo->precioUSD());
            }
            return array("status" => "success", "data" => $datatodo, "precioBTC" => $nodo->precioUSD());
        }
        
        public function verifySecret($secret)
        {
            parent::conectar();
            $consultar1 = "select * from usuarios where id = '$secret[uid]' and clave_trans like '$secret[secret]'";
            $lista      = parent::consultaTodo($consultar1);
            if (count($lista) > 0) {
                $respuesta = array('status' => 'success', 'message' => 'Contraseña correcta');
            } else {
                $respuesta = array('status' => 'error', 'message' => 'La contraseña es incorrecta');
            }
            parent::cerrar();
            return $respuesta;
        }
        
        public function marcarNotificaciones($user)
        {
            parent::conectar();
            parent::queryRegistro("update notificaciones_usuario set visualizada = 1 where usuario = $user and visualizada = 0");
            return array("status" => "success");
        }
        
        public function obtenerPosibleReferido($user, $referido)
        {
            $referidosPermanentes = [""];
            parent::conectar();
            $respuesta2 = parent::consultaTodo("select * from historico_tickets where usuario = $user and transferido is not null group by transferido order by fecha_adquisicion asc limit 2");
            for ($i = 0; $i < count($respuesta2); $i++) {
                if ($respuesta2[$i]["transferido"] == $referido) {
                    return true;
                }
            }
            for ($i = 0; $i < count($referidosPermanentes); $i++) {
                if ($referidosPermanentes[$i] == $referido) {
                    return true;
                }
            }
            return false;
        }
        
        public function CrearUsuarios($user)
        {
            $nodo = new Nodo();
            parent::conectar();
            $respuesta;
            $separado = explode(" ", $user['usuario']);
            if (count($separado) > 1) {
                return array('status' => 'errorUsernameEspacio', 'message' => 'El usuario no puede llevar espacios');
            }
            $existe = $this->usuarioExistente($user['usuario']);
            if (count($existe) > 0) {
                parent::conectar();
                $consultar1 = "select * from usuarios where username like '{$user["usuario"]}' or email = '{$user["email"]}'";
                $respuesta2 = parent::consultaTodo($consultar1);
                if (count($respuesta2) > 0) {
                    if ($respuesta2[0]["plan"] == 0 && $respuesta2[0]["referido"] == null) {
                        if ($user["password"] != $respuesta2[0]["password"]) {
                            $respuesta = array('status' => 'passwordInvalido', 'message' => 'El password no coincide con la contraseña registrada anteriormente');
                        } else if ($this->obtenerPosibleReferido($respuesta2[0]["id"], $user['referido'])) {
                            $user["idusuario"] = $respuesta2[0]["id"];
                            $respuesta         = $this->convertirInvitadoAFullRegistro($user);
                        } else {
                            $respuesta = array('status' => 'errorReferidoInvalido', 'message' => 'El usuario no puede asociarte como su referido');
                        }
                        
                    } else {
                        $respuesta = array('status' => 'errorUserExiste', 'message' => 'El usuario ya existe');
                    }
                } else {
                    $respuesta = array('status' => 'errorUserExiste', 'message' => 'El usuario ya existe');
                }
            } else {
                parent::conectar();
                $consultar1 = "select * from usuarios where username like '{$user["usuario"]}' or email = '{$user["email"]}'";

                $respuesta2 = parent::consultaTodo($consultar1);
                if (count($respuesta2) != 0) {
                    $respuesta = array('status' => 'errorUserExiste', 'message' => 'Ya existe un usuario diferente con estos datos');
                } else{
            $plan = 10;
                if ($user['plan'] == 0) {
                    $user['plan'] = 10;
                }
                $planes    = $this->obtenerDetallePlan($user['plan']);
                $membresia = $this->obtenerDetallePlan(10);
                if ($planes == 0) {
                    $respuesta = array('status' => 'errorPlan', 'message' => 'No existe este plan');
                } else {
                    if ($user['referido'] != '') {
                        $existeRef = $this->usuarioExistente($user['referido']);
                        if (count($existeRef) > 0) {
                            $data = array(
                                'first_name' => $user['nombre'],
                                'last_name'  => $user['nombre'],
                                'email'      => $user['email'],
                                'username'   => $user['usuario'],
                                'password'   => $user['password'],
                            );
                            $resp       = parent::metodoPost('user/saveUser.php', $data);
                            //$resp       = json_decode($resp, true);


                            //print_r($resp);
                            $consultar2 = "INSERT INTO usuarios(idTalentlms,username,password,plan, status, referido, posicion, nombreCompleto, telefono, email, paisid) VALUES ($resp->data,'" . $user['usuario'] . "', '" . $user['password'] . "', 10, 1, " . $user['referido'] . ", '" . $user['posicion'] . "','" . $user['nombre'] . "', '" . $user['telefono'] . "', '" . $user['email'] . "', '" . $user['pais'] . "')";
                            $preId = parent::queryRegistro($consultar2);
                            $this->agregarCodigoTickets($preId);
                            parent::queryRegistro("INSERT INTO nodos(usuario, monto, puntos) VALUES ($preId,0,0)");
                            $preciousd = $nodo->precioUSD();
                            if ($user["plan"] != 10) {
                                $monto_usd = ($membresia[0]["monto"] * 1) + ($planes[0]["monto"] * 1);
                            } else {
                                $monto_usd = ($planes[0]["monto"] * 1);
                            }
                            
                            $monto = number_format(($monto_usd / $preciousd), 6);
                            // print_r(array($membresia[0]["monto"] , $planes[0]["monto"],  $monto_usd, $monto))
                            $response = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/marcar.php", array("usuario" => $preId, "monto" => $monto, "plan" => $user['plan'], "membresia" => 1, "precio_usd" => $preciousd, "monto_usd" => $monto_usd));
                            // print_r($response);
                            parent::queryRegistro($consultar2);
                            $nodo->getNewAccount("wallet", $preId);
                            $nodo->getNewAccount("reconsumo", $preId);
                            $nodo->getNewAccountEBG("EBGWallet", $preId);
                            if ($user['foto'] !== '') {
                                /* $this->imagen($user['foto'], $preId); */
                                $base64         = base64_decode(explode(',', $user['foto'])[1]);
                                $filepath1 = $_SERVER['DOCUMENT_ROOT'] . "/peer2win/imagen/avatar/$preId.png";
                                $foto = "imagen/avatar/$preId.png";
                                file_put_contents($filepath1, $base64);
                                $consultarthree = "UPDATE usuarios SET foto = '$foto' Where id ='$preId'";
                                parent::query($consultarthree);
                            }
                            $respuesta = array('status' => 'success', 'message' => 'El usuario registrado', 'user' => $preId);
                        } else {
                            $respuesta = array('status' => 'errorReferidoNoExiste', 'message' => 'El referido no existe');
                        }
                        
                    } else {
                        $respuesta = array('status' => 'errorReferidoVacio', 'message' => 'Debes tener referido para poder registrarte');
                    }
                    
                }
                }
                
                
            }
            
            parent::cerrar();
            return $respuesta;
            
        }
        
        public function convertirInvitadoAFullRegistro($user)
        {
            
            $nodo = new Nodo();
            parent::conectar();
            $data = array(
                'name'     => $user['nombre'],
                'lasrname' => $user['nombre'],
                'email'    => $user['email'],
                'username' => $user['usuario'],
                'password' => $user['password'],
            );
            $resp = $this->saveTalentLMS($data);
            
            $planes    = $this->obtenerDetallePlan($user['plan']);
            $membresia = $this->obtenerDetallePlan(10);
            // $consulta2 = "update usuarios set idTalentlms = {$resp->id}, plan=10, referido = {$user["referido"]}, posicion = '{$user["posicion"]}', nombreCompleto = '{$user['nombre']}',  telefono = {$user['telefono']}, paisid = {$user['pais']} where id = {$user["idusuario"]}";
            $consulta2 = "update usuarios set idTalentlms = null, plan=10, referido = {$user["referido"]}, posicion = '{$user["posicion"]}', nombreCompleto = '{$user['nombre']}',  telefono = '{$user['telefono']}', paisid = '{$user['pais']}' where id = {$user["idusuario"]}";
            parent::queryRegistro("INSERT INTO nodos(usuario, monto, puntos) VALUES ({$user["idusuario"]},0,0)");
            parent::queryRegistro($consulta2);
            $preciousd = $nodo->precioUSD();
            if ($user["plan"] != 10) {
                $monto_usd = ($membresia[0]["monto"] * 1) + ($planes[0]["monto"] * 1);
            } else {
                $monto_usd = ($planes[0]["monto"] * 1);
            }
            
            $monto = number_format(($monto_usd / $preciousd), 6);
            // print_r(array($membresia[0]["monto"] , $planes[0]["monto"],  $monto_usd, $monto))
            $response  = parent::remoteRequest("https://peers2win.com/api/controllers/pagos/marcar.php", array("usuario" => $user["idusuario"], "monto" => $monto, "plan" => $user['plan'], "membresia" => 1, "precio_usd" => $preciousd, "monto_usd" => $monto_usd));
            $respuesta = array('status' => 'success', 'message' => 'El usuario registrado', 'user' => $user["idusuario"]);
            return $respuesta;
        }
        
        //no se asocia el plan aun.....
        public function crearInvitado($user)
        {
            $nodo = new Nodo();
            parent::conectar();
            $respuesta;
            $separado = explode(" ", $user['usuario']);
            if (count($separado) > 1) {
                return array('status' => 'errorUsernameEspacio', 'message' => 'El usuario no puede llevar espacios');
            }
            $existe = $this->usuarioExistente($user['usuario']);
            if (count($existe) > 0) {
                $respuesta = array('status' => 'errorUserExiste', 'message' => 'El usuario existe', "datosusers" => $user);
            } else {
                $consultar2 = "INSERT INTO usuarios(idTalentlms,username,password,plan, status, referido, posicion, nombreCompleto, telefono, email, paisid, ciudad) VALUES (null,'{$user['usuario']}', '{$user['password']}', 0, 1, null, '','{$user['nombreCompleto']}', '{$user['telefono']}', '{$user['email']}', '{$user['paisid']}', '{$user['ciudad']}')";
                
                $preId = parent::queryRegistro($consultar2);
                $this->agregarCodigoTickets($preId);
                // print_r($response);
                $nodo->getNewAccount("wallet", $preId);
                $nodo->getNewAccount("reconsumo", $preId);
                $nodo->getNewAccountEBG("EBGWallet", $preId);
                /* $this->saveGroupTalentLMS($resp->id); */
                $respuesta = array('status' => 'success', 'message' => 'El usuario registrado', 'user' => $preId);
            }
            
            parent::cerrar();
            return $respuesta;
            
        }
        
        public function getNameUser($id)
        {
            parent::conectar();
            $consultar1 = "select username from usuarios where id = 1";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            return $lista;
        }
        
        public function getGanancias($userId)
        {
            parent::conectar();
            $nodo     = new Nodo();
            $consulta = "select a.*, ifnull(b.username, 'P2W') usuarioPaga from
            (select 'Puntaje binario' tipo, id, usuarioPaga, usuarioRecibe, monto, fecha_pago, puntos, pata FROM peer2win.puntajeBinario where usuarioRecibe like '$userId[user]'
            UNION
            select 'Pago uniLevel' tipo, id, 0 usuarioPaga, usuario usuarioRecibe, montoUSD monto, fecha_pago, 0 puntos, '' as pata from peer2win.pagoUnilevel where usuario = $userId[user]
            UNION
            SELECT 'Venta directa' tipo, id, usuarioPaga, usuarioRecibe, montoPagado, fecha_agregado, puntosPagados, '' as pata FROM peer2win.venta_directa where usuarioRecibe like '$userId[user]') a
            left join usuarios b on b.id = a.usuarioPaga
            order by fecha_pago desc";
            $lista = parent::consultaTodo($consulta);
            parent::cerrar();
            $repuesta = array('status' => 'success', 'data' => $lista, 'valorBTC' => $nodo->precioUSD());
            return $repuesta;
        }
        
        public function getNextLevel($userId) {
            parent::conectar();
                $consulta8 = "
                    select sum(puntos) puntos, sum(total) total from (
                    select sum(puntos) puntos, 0 total from puntajeBinario p
                    inner join usuarios u on u.id = p.usuarioRecibe
                    where usuarioRecibe = $userId and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
                    union
                    select 0 puntos, count(*) total from usuarios where referido = $userId and plan != 10)
                    a
                ";
                $datos = parent::consultaTodo($consulta8);
                $datos2 = parent::consultaTodo("select sum(if(rango>=1, 1, 0)) rango1, sum(if(rango>=2, 1, 0)) rango2, sum(if(rango>=3, 1, 0)) rango3, sum(if(rango>=4, 1, 0)) rango4, sum(if(rango>=5, 1, 0)) rango5, sum(if(rango>=6, 1, 0)) rango6 from usuarios where referido = $userId;");
                return array("datos"=>$datos[0], "rangos"=> $datos2[0]);
        }
            
        public function getGananciasTop($userId)
        {
            parent::conectar();
            $fechaFin  = getdate();
            $fechayear = $fechaFin['year'] + 1;
            $respuesta;
            $consulta1 = "
                select usuarioRecibe, sum(a.puntos) monto, b.nombreCompleto nc, c.nombre nombreCompleto, b.foto, ifnull((sum(a.puntos)*100/c1.puntos), 0) pun from (
                select concat('pb', p.id) id, usuarioRecibe, monto monto, puntos from puntajeBinario p
                inner join usuarios u on u.id = p.usuarioRecibe
                where usuarioRecibe = p.usuarioRecibe and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
                union
                select concat('vd', id) id, usuarioRecibe, montoPagado monto, 0 puntos from venta_directa
                ) a
                inner join usuarios b on b.id = a.usuarioRecibe
                inner join rangos c on c.id = b.rango
                inner join rangos c1 on c1.id = b.rango + 1
                where usuarioRecibe in (select id from usuarios where referido = $userId[user])
                group by usuarioRecibe
                having pun > 80
                order by monto DESC
                limit 10;
            ";
            $consulta2 = "
                select usuarioRecibe, sum(puntos) monto, b.nombreCompleto nc, c.nombre nombreCompleto, b.foto from (
                select concat('pb', id) id, usuarioRecibe, monto monto from puntajeBinario
                union
                select concat('vd', id) id, usuarioRecibe, montoPagado monto from venta_directa
                ) a
                inner join usuarios b on b.id = a.usuarioRecibe
                inner join rangos c on c.id = b.rango
                group by usuarioRecibe
                order by monto DESC
                limit 10;
            ";
            $consulta3 = "
                select nombreCompleto, foto, fecha_ingreso from usuarios
                where referido in (select usuario from pagoBinario where asociado = $userId[user])
                order by fecha_ingreso desc
                limit 3;
            ";
            $consulta4 = "
                select nombreCompleto, foto  from usuarios
                where status != 1 and id in (select usuario from pagoBinario where asociado = $userId[user]);
            ";
            $consulta5 = "
                select sum(monto) as monto from puntajeBinario p
                inner join usuarios u on  u.id = p.usuarioRecibe
                where usuarioRecibe like $userId[user] and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
            ";
            $consulta6 = "
                select sum(v.montoPagado) as monto from venta_directa v
                inner join usuarios u on  u.id = v.usuarioRecibe
                where usuarioRecibe = '$userId[user]' and v.fecha_agregado BETWEEN u.fecha_inicio and u.fecha_fin
            ";
            $consulta7 = "
                select sum(monto) as monto from puntajeBinario p
                inner join usuarios u on  u.id = p.usuarioRecibe
                inner join pago_membresia m on m.usuario = u.id  and CURRENT_TIMESTAMP BETWEEN m.fecha_adquisicion and m.fecha_limite
                where usuarioRecibe like '$userId[user]' and p.fecha_pago BETWEEN m.fecha_adquisicion and m.fecha_limite
            ";
            $consulta8 = "
                select sum(puntos) puntos, sum(total) total from (
                select sum(puntos) puntos, 0 total from puntajeBinario p
                inner join usuarios u on u.id = p.usuarioRecibe
                where usuarioRecibe = $userId[user] and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
                union
                select 0 puntos, count(*) total from usuarios where referido = $userId[user] and plan != 10)
                a
            ";
            $consulta9 = "
                select (ifnull(sum(monto), 0) * 0.1) monto from puntajeBinario p
                inner join usuarios u on u.id = $userId[user]
                where p.usuarioRecibe in (select id from usuarios where referido = $userId[user]) and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin;
            ";
            $consulta10 = "
                select count(*) referidos from usuarios
                where referido = $userId[user];
            ";
            $consulta11 = "
                select ifnull(sum(m.monto), 0) monto from montosPerdidos m
                inner join usuarios u on m.usuario = u.id
                where m.usuario = $userId[user] and m.fecha_perdido BETWEEN u.fecha_inicio and u.fecha_fin;
            ";
            $consulta12 = "
                select ifnull(sum(a.monto), 0) as monto from (
                    select p.monto as monto FROM puntajeBinario p
                    inner join usuarios u on u.id = usuarioRecibe
                    where usuarioRecibe like '$userId[user]' and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
                    UNION
                    SELECT v.montoPagado as monto FROM venta_directa v
                    inner join usuarios u on u.id = usuarioRecibe
                    where usuarioRecibe like '$userId[user]' and v.fecha_agregado BETWEEN u.fecha_inicio and u.fecha_fin
                    ) a
            ";
            $consulta13 = "
                select ifnull(sum((et.monto + et.fee) * et.precio), 0) as monto, if(ab.label = 'recibir_$userId[user]', 'Compra por reconsumo','Envios a otras direcciones') label from enviarTransaccion et
                inner join usuarios u on u.id = et.usuario
                left join address_btc ab on ab.address = et.address_receive and ab.label = 'recibir_$userId[user]'
                where et.usuario = $userId[user] and et.fecha BETWEEN u.fecha_inicio and u.fecha_fin
                group by label order by label
            ";
            $consulta14 = parent::consultaTodo("select sum(if(rango>=1, 1, 0)) rango1, sum(if(rango>=2, 1, 0)) rango2, sum(if(rango>=3, 1, 0)) rango3, sum(if(rango>=4, 1, 0)) rango4, sum(if(rango>=5, 1, 0)) rango5, sum(if(rango>=6, 1, 0)) rango6 from usuarios where referido = $userId[user];");
            $lista15    = parent::consultaTodo("select * from notificaciones_usuario where usuario = $userId[user] order by fecha desc");
            $rangoUsers = array();
            // for ($i=0;$i<count($consulta14);$i++) {
            $rangoUsers[1] = $consulta14[0]["rango1"];
            $rangoUsers[2] = $consulta14[0]["rango2"];
            $rangoUsers[3] = $consulta14[0]["rango3"];
            $rangoUsers[4] = $consulta14[0]["rango4"];
            $rangoUsers[5] = $consulta14[0]["rango5"];
            $rangoUsers[6] = $consulta14[0]["rango6"];
            // }
            
            /* echo $consulta13; */
            $lista1 = parent::consultaTodo($consulta1);
            $lista2 = parent::consultaTodo($consulta2);
            $lista3 = parent::consultaTodo($consulta3);
            $lista4 = parent::consultaTodo($consulta4);
            $lista5 = parent::consultaTodo($consulta5);
            $lista6 = parent::consultaTodo($consulta6);
            // $lista7    = parent::consultaTodo($consulta7);
            $lista8 = parent::consultaTodo($consulta8);
            // $lista9    = parent::consultaTodo($consulta9);
            $response = json_decode(parent::remoteRequest("https://peers2win.com/api/controllers/pagos/pagoPorUnilevel.php", array("id" => $userId["user"])), true);
            // print_r($response);
            $lista10 = parent::consultaTodo($consulta10);
            $lista11 = parent::consultaTodo($consulta11);
            /* $lista12   = parent::consultaTodo($consulta12); */
            $lista13 = parent::consultaTodo($consulta13);
                                                
            $listaPagoEBG = parent::consultaTodo("select ifnull(sum(a.value * b.precio_usd), 0) valor from ebg_transactions a
            inner join transactions_executed_ebg b on b.txhash = a.hash
            inner join address_ebg c on (c.address = a.`from` or c.address = a.`to`) and c.usuario = {$userId["user"]}
            where b.comentario = 'Pago Reconsumo EBG'");
            // $lista15 = parent::consultaTodo($consulta15);
            /* print_r($lista13[0]); */
            $respuesta = array(
                'status'          => 'success',
                'message'         => 'El usuario registrado',
                'top'             => $lista1,
                'topGenreal'      => $lista2,
                'reciente'        => $lista3,
                'reciente'        => $lista3,
                'inantivo'        => $lista4,
                'ultimoBinario'   => count($lista5) > 0 ? $lista5[0]['monto'] : $lista5,
                'ultimoUnilevel'  => count($lista6) > 0 ? $lista6[0]['monto'] : $lista6,
                'unilevel'        => $response["monto"], //$lista9[0]['monto'],
                // 'acumulado' => count($lista7) > 0 ? $lista7[0]['monto'] : $lista7,
                'acumulado'       => 0,
                'totalPunto'      => $lista8[0],
                "montoPerdido"    => $lista11[0]["monto"],
                'referidos'       => count($lista10) > 0 ? $lista10[0]['referidos'] : $lista10,
                "rangoReferidos"  => $rangoUsers,
                'gastosReconsumo' => count($lista13) > 0 ? $lista13[0]['monto'] : 0,
                'pagoWallets'     => count($lista13) > 1 ? $lista13[1]['monto'] : 0,
                "notificaciones"  => count($lista15) > 0 ? array("status" => "success", "data" => $lista15) : array("status" => "error"),
                "bonoEBG"         => $listaPagoEBG[0]["valor"],
            );
            parent::cerrar();
            return $respuesta;
        }
                                            
        public function getTopMundial($rango) {
            parent::conectar();
            $respuesta;
            $consulta1 = "
                select usuarioRecibe, sum(a.puntos) monto, b.nombreCompleto nc, c.nombre nombreCompleto, b.foto, c.puntos from (
                select concat('pb', p.id) id, usuarioRecibe, monto monto, puntos from puntajeBinario p
                inner join usuarios u on u.id = p.usuarioRecibe
                where usuarioRecibe = p.usuarioRecibe and p.fecha_pago BETWEEN u.fecha_inicio and u.fecha_fin
                union
                select concat('vd', id) id, usuarioRecibe, montoPagado monto, 0 puntos from venta_directa
                ) a
                inner join usuarios b on b.id = a.usuarioRecibe
                inner join rangos c on c.id = b.rango
                where b.rango = $rango[id]
                group by usuarioRecibe
                order by monto DESC
                limit 10;
            ";
            $lista1 = parent::consultaTodo($consulta1);
            $respuesta = array(
                'status'          => 'success',
                'message'         => 'Lista',
                'top'             => $lista1,
            );
            parent::cerrar();
            return $respuesta;
        }
                                                
        public function obtenerDetallePlan($paquete)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "select * from paquetes where id = $paquete";
            $respuesta  = parent::consultaTodo($consultar1);
            /* parent::cerrar(); */
            return $respuesta;
        }
        
        public function usuarioExistente($usuario)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "select * from usuarios where id like '$usuario' or username like '$usuario'";
            $respuesta  = parent::consultaTodo($consultar1);
            /* parent::cerrar(); */
            return $respuesta;
        }

        public function emailExistente($usuario)
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "select * from usuarios where email like '$usuario'";
            $respuesta  = parent::consultaTodo($consultar1);
            /* parent::cerrar(); */
            return $respuesta;
        }
        
        public function buscarRango($rango = 1)
        {
            if ($rango == "") {
                $rango = 1;
            }
            $consultar1 = parent::consultaTodo("select * from rangos where id = $rango");
            return $consultar1[0];
            
        }
        
        public function Login($login)
        {
            
            parent::conectar();
            /* $password = "sha1(concat(sha1('$login[email]'), sha1('$login[password]')))"; */
            //echo $password;
            $login["user"] = str_replace(" ", "ñ", $login["user"]);
            $consultar1    = "select *, CURRENT_TIMESTAMP tiempoextra from usuarios where username = '{$login["user"]}' and password = '$login[password]'";
            /* echo $consultar1; */
            $lista         = parent::consultaTodo($consultar1);
            if (count($lista) > 0) {
                unset($lista[0]['password']);
                if ($lista[0]['clave_trans'] == null || $lista[0]['clave_trans'] == "null" || $lista[0]['clave_trans'] == "") {
                    $lista[0]['clave_trans'] = false;
                }else{
                    $lista[0]['clave_trans'] = true;
                }
                // print_r($lista[0]);
                $lista[0]["rango"] = $this->buscarRango($lista[0]["rango"]);
                if ($lista[0]['referido']) {
                    $consultar2 = "select * from usuarios where referido = " . $lista[0]['id'];
                    $lista2     = parent::consultaTodo($consultar2);
                    $consultar3 = "select * from address_btc where usuario = " . $lista[0]['id'];
                    $lista3     = parent::consultaTodo($consultar3);
                    $consultar4 = "select * from address_ebg where usuario = " . $lista[0]['id'];
                    $lista4     = parent::consultaTodo($consultar4);
                    unset($lista2[0]['password']);
                    
                    $lista[0]['referidoData'] = $lista2;
                    $lista[0]['address_btc']  = $lista3;
                    $lista[0]['address_ebg']  = $lista4;
                }
                $consultar2 = parent::consultaTodo("select * from pago_membresia where usuario = {$lista[0]["id"]} and CURRENT_TIME BETWEEN fecha_adquisicion and fecha_limite;");
                // print_r($consultar2);
                if (count($consultar2) > 0) {
                    $lista[0]["fecha_fin_membresia"] = $consultar2[0]["fecha_limite"];
                } else {
                    $lista[0]["fecha_fin_membresia"] = $lista[0]["tiempoextra"];
                }
                
                $respuesta = array('status' => 'success', 'message' => 'Logueado exitosamente', 'data' => $lista);
            } else {
                $respuesta = array('status' => 'errorLogin', 'message' => 'Los datos proporcionados no son correctos', "datos" => $lista);
            }
            parent::cerrar();
            return $respuesta;
        }
        
        public function updateRecoverPassword($login)
        {
            parent::conectar();
            /* $consultar1 = "select * from users where email like '$login[email]'";
            $lista      = parent::consultaTodo($consultar1);
            if (count($lista) > 0) {
                $token    = SHA1($login["email"] . $lista[0]["token"]);
                $password = "sha1(concat(sha1('$login[email]'), sha1('$login[password]')))";
                if ($password == $lista[0]["password"]) {
                    $respuesta = array('status' => 'error', 'message' => 'La contraseña no puede ser igual a la anterior');
                } else if ($token == $login["token"]) {
                    $consultar2 = "UPDATE users SET password = $password WHERE id = " . $lista[0]["id"];
                    $lista1     = parent::query($consultar2);
                    $respuesta  = array('status' => 'success', 'message' => 'Contraseña actualizada correctamente');
                } else {
                    $respuesta = array('status' => 'error', 'message' => 'El token ingresado ha expirado');
                }
            } else {
                $respuesta = array('status' => 'error', 'message' => 'El correo ingresado no se encuentra registrado');
            } */
            $consultar1 = "select * from token where token = '$login[token]'";
            $lista      = parent::consultaTodo($consultar1);
            if (count($lista) > 0) {
                $fecha = getdate();
                if ($fecha[0] <= $lista[0]['fecha_final']) {
                    if ($login['email'] === $lista[0]["email"]) {
                        $consultar2 = "UPDATE usuarios SET password = '" . $login['password'] . "' WHERE email = '" . $lista[0]["email"] . "'";
                        $consultar3 = "delete from token where token = '" . $login['token'] . "'";
                        $lista2     = parent::query($consultar2);
                        $lista3     = parent::query($consultar3);
                        /* print_r($lista2);
                        print_r($lista3); */
                        /* echo $consultar2;
                        echo $consultar3; */
                        $respuesta = array('status' => 'success', 'message' => 'Contraseña actualizada correctamente');
                    } else {
                        $respuesta = array('status' => 'errorEmail', 'message' => 'El correo no coinciden');
                    }
                    
                } else {
                    $respuesta = array('status' => 'errorTokenVensido', 'message' => 'Token vencido');
                }
            } else {
                $respuesta = array('status' => 'errorToken', 'message' => 'El token no existe');
            }
            parent::cerrar();
            return $respuesta;
        }
        
        public function updatePassword($user) {
            parent::conectar();
            
            $consultar1 = "select * from usuarios where id = '$user[id]'";
            $lista      = parent::consultaTodo($consultar1);
            
            if (count($lista) > 0) {
                if ($lista[0]['password'] == $user['password']) {
                    $consultar2 = "UPDATE usuarios SET password = '" . $user['nueva'] . "' WHERE id = '" . $user['id'] . "'";
                    $lista2     = parent::query($consultar2);
                    $respuesta = array('status' => 'success', 'message' => 'Contraseña actualizada correctamente');
                } else {
                    $respuesta = array('status' => 'errorPassword', 'message' => 'El password no coinciden');
                }
            } else {
                $respuesta = array('status' => 'errorUser', 'message' => 'El usuario no existe');
            }
            
            parent::cerrar();
            return $respuesta;
        }
        
        public function SolicitarPassword($login)
        {
            parent::conectar();
            //echo $password;
            $consultar1 = "select * from usuarios where email like '$login[email]'";
            //echo $consultar1;
            $lista = parent::consultaTodo($consultar1);
            if (count($lista) > 0) {
                $fecha       = getdate();
                $fechaInicio = $fecha[0];
                $fechaFin    = $fecha[0] + (1000 * 60 * 15);
                $token       = SHA1($login["email"] . $lista[0]["password"]);
                $consultar2  = "INSERT INTO token (email, token, fecha_inicio, fecha_final) VALUES ('" . $login["email"] . "', '$token', $fechaInicio, $fechaFin);";
                $lista2      = parent::queryRegistro($consultar2);
                $url         = "https://peers2win.com/recuperar_contrasena.php?token=" . $token;
                /* $temp  = $this->recuperarContrasenia(array("token" => $token, "nombre" => $lista[0]["nombre"], "apellido" => $lista[0]["apellido"], "email" => $login["email"])); */
                if ($lista2 > 0) {
                    $data1 = array(
                        'email' => $login['email'],
                        'token' => $token
                    );
                    $temp  = $this->recuperarContrasenia($data1);
                    $respuesta = array('status' => 'success', 'message' => 'Email enviado exitosamente al usuario', 'token' => $token);
                } else {
                    $respuesta = array('status' => 'error', 'message' => 'El correo ingresado no se encuentra registrado');
                }
            } else {
                $respuesta = array('status' => 'error', 'message' => 'El correo ingresado no se encuentra registrado');
            }
            parent::cerrar();
            return $respuesta;
        }
        
        public function recuperarContrasenia($data)
        {
            $url  = "https://peers2win.com/recuperar_contrasena.php?token=" . $data["token"];
            $html = file_get_contents('/var/www/html/peer2win/html/correo2.html');
            $html = str_replace("#@link_recuperar#@", "$url", $html);
            $para      = $data['email'];
            $titulo    = "Solicitud de recuperación de contraseña";
            $mensaje1  = $html;
            $cabeceras = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $cabeceras .= "From: info@p2w.com\r\n";
            $dataArray = array("email"=>$data['email'], "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            return json_decode($response, true);
            /* if (mail($para, $titulo, $mensaje1, $cabeceras)) {
                //  echo "entro al success";
                return true;
            } else {
                //    echo "entro al error";
                return false;
            } */
        }
        
        public function shareEmail($data)
        {
            $html = file_get_contents('/var/www/html/peer2win/html/correo1.html');
            $html = str_replace("#@link_referido#@", $data['url'], $html);
            $para      = $data['email'];
            $titulo    = "Registro";
            $mensaje1  = $html;
            $cabeceras = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $cabeceras .= "From: info@p2w.com\r\n";
            
            $dataArray = array("email"=>$data['email'], "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            /*print_r($dataArray);
            print_r($response);*/
            /*if (mail($para, $titulo, $mensaje1, $cabeceras)) {
                //  echo "entro al success";
                $respuesta = array('status' => 'success', 'message' => 'Se envio el correo');
            } else {
                //    echo "entro al error";
                $respuesta = array('status' => 'error', 'message' => 'No se envio el correo');
            }*/
            return json_decode($response, true);
        }
        
        public function recuperarUsername($data)
        {
            parent::conectar();
            $consultar1 = "select * from usuarios where email like '$data[email]'";
            $lista = parent::consultaTodo($consultar1);
            if (count($lista)) {
                $html = file_get_contents('/var/www/html/peer2win/html/correo4.html');
                $html = str_replace("#@recuperar_username#@", $lista[0]['username'], $html);
                $para      = $data['email'];
                $titulo    = "Solicitud de recuperación del nombre de usuario";
                $mensaje1  = $html;
                $cabeceras = 'MIME-Version: 1.0' . "\r\n";
                $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $cabeceras .= "From: info@p2w.com\r\n";
                $dataArray = array("email"=>$data['email'], "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
                $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
                /* print_r($response); */
                $respuesta = json_decode($response, true);
                /* if (mail($para, $titulo, $mensaje1, $cabeceras)) {
                    //  echo "entro al success";
                    return true;
                } else {
                    //    echo "entro al error";
                    return false;
                } */
            } else {
                $respuesta = array('status' => 'error', 'message' => 'El correo ingresado no se encuentra registrado');
            }
            
            parent::cerrar();
            return $respuesta;
        }
        
        public function AllEmails($email){
            //$data1 = $this->shareContacto(array("name"=>"felix espitia", "email"=>$email, "telefono"=> "1234", "interes"=>"probando emails", "message"=>"Probando los mensajes", "pais"="Colombia", "ciudad"=>"santa marta"));
            $data2 = $this->recuperarUsername(array("email"=>$email));
            $data3 = $this->shareEmail(array("email"=>$email, "url"=> "https://testnet.foodsdnd.co"));
            $data4 = $this->recuperarContrasenia(array("token"=>"felixespitia", "email"=>$email));
            return array("status"=>"success", "data1"=>$data1, "data2"=>$data2, "data3"=>$data3, "data4"=>$data4);
            
        }
        
        public function shareContacto($data)
        {
            $html = file_get_contents('/var/www/html/peer2win/html/correo5.html');
            $para = 'lucianoadb@gmail.com';
            $html = str_replace("@#nombre#@", $data['name'], $html);
            $html = str_replace("@#email#@", $data['email'], $html);
            $html = str_replace("@#telefono#@", $data['telefono'], $html);
            $html = str_replace("@#titulo_interes#@", isset($data['interes']) ? 'Interés' : 'Mensaje', $html);
            $html = str_replace("@#interes#@", isset($data['interes']) ? $data['interes'] : $data['message'], $html);
            $pais = isset($data['pais']) ? "
            <tr>
            <td
            style='padding: 16px 5px;color: #0C0F3D; font-size:  clamp(8px, 1.5vw,16px);border: 1px solid #eaeaea'>
            Pais
            </td>
            <td
            style='padding: 16px 5px;color: #0C0F3D; font-size:  clamp(8px, 1.5vw,16px);border: 1px solid #eaeaea'>
            $data[pais]
            </td>
            </tr>
            " : '' ;
            $ciudad = isset($data['ciudad']) ? "
            <tr>
            <td
            style='padding: 16px 5px;color: #0C0F3D; font-size:  clamp(8px, 1.5vw,16px);border: 1px solid #eaeaea'>
            Ciudad
            </td>
            <td
            style='padding: 16px 5px;color: #0C0F3D; font-size:  clamp(8px, 1.5vw,16px);border: 1px solid #eaeaea'>
            $data[ciudad]
            </td>
            </tr>
            " : '' ;
            $html = str_replace("@#email#@", $pais, $html);
            $html = str_replace("@#telefono#@", $ciudad, $html);
            $titulo    = 'Contacto';
            $mensaje1  = $html;
            $cabeceras = 'MIME-Version: 1.0' . "\r\n";
            $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $cabeceras .= "From: info@p2w.com\r\n";
            $dataArray = array("email"=>$data['email'], "titulo"=>$titulo, "mensaje"=>$mensaje1, "cabeceras"=> $cabeceras);
            $response = parent::remoteRequest("https://criptocomers.com/api/p2w/", $dataArray);
            return json_decode($response, true);
            /* print_r($lista1); */
            /* print_r($cabeceras); */
            //echo "paso por aca 3";
            //echo "\n $para, $titulo, $mensaje1, $cabeceras";
            /* if (mail($para, $titulo, $mensaje1, $cabeceras)) {
                //  echo "entro al success";
                return array('status' => 'success', 'message' => 'El usuario se ha registrado correctamente');
            } else {
                //    echo "entro al error";
                return array('status' => 'errorServidor', 'message' => 'El correo electronico ingresado no es valido');
            } */
        }
        
        public function listarPaices()
        {
            parent::conectar();
            $respuesta;
            $consultar1 = "select * from paises";
            $consultar2 = "select * from rangos";
            $lista      = parent::consultaTodo($consultar1);
            $lista2     = parent::consultaTodo($consultar2);
            parent::cerrar();
            $respuesta = array(
                'status'  => 'success',
                'message' => 'Lista',
                'paices'  => $lista,
                'rango'   => $lista2,
            );
            return $respuesta;
        }
        
        public function updateUsuario($usuario)
        {
            parent::conectar();
            $respuesta;
            $consultar2 = "UPDATE usuarios SET username='$usuario[username]', nombreCompleto='$usuario[nombreCompleto]', email='$usuario[email]', telefono='$usuario[telefono]', paisid='$usuario[pais]', notificaciones=$usuario[notificacion], avatar=$usuario[avatar], fiat = '$usuario[fiat]' WHERE id=$usuario[id]";
            $preId      = parent::query($consultar2);
            if ($preId > 0) {
                $respuesta = array('status' => 'success', 'message' => 'Los datos del usuario han sido actualizados correctamente');
            } else {
                $respuesta = array('status' => 'error', 'message' => 'No se pudieron actualizar los datos del usuario');
            }
            parent::cerrar();
            return $respuesta;
        }
        
        public function planesUser($user)
        {
            parent::conectar();
            $request       = null;
            $consultaruser = '';
            $consultaruser = "select u.plan, p.nombre, p.descripcion, p.monto, p.imagen, u.status from usuarios u inner join paquetes p on u.plan = p.id where u.id = '$user'";
            $lista         = parent::consultaTodo($consultaruser);
            if (count($lista) > 0) {
                $datarequest = $lista[0];
                // $upgrades    = '';
                // if ($datarequest['plan'] == '10') {
                //     $upgrades = '1';
                // } else if ($datarequest['plan'] == '1') {
                //     $upgrades = '3';
                // } else if ($datarequest['plan'] == '2') {
                //     $upgrades = '4';
                // }
                // $consultarplan             = "select * from paquetes p where p.tipo = '$upgrades'";
                $consultarplan             = "select * from paquetes where paquete_inicial in (select plan from usuarios where id = $user) order by monto asc;";
                if ($datarequest["status"] == "2" || $datarequest["status"] == "4") {
                    $consultarplan             = "select * from paquetes where paquete_inicial in (select plan from usuarios where id = $user) order by monto desc limit 1;";
                }
                $lista2                    = parent::consultaTodo($consultarplan);
                $datarequest['dataplanes'] = null;
                if (count($lista2) > 0) {
                    $datarequest['dataplanes'] = $lista2;
                }
                $request = array(
                    'status'    => 'success',
                    'message'   => 'data planes',
                    'data'      => $datarequest,
                    "membresia" => $this->obtenerDetallePlan(10)[0],
                );
            } else {
                $request = array(
                    'status'  => 'fail',
                    'message' => 'no data planes',
                    'data'    => null,
                );
            }
            parent::cerrar();
            return $request;
        }
                                                        
        public function planesreconsumir()
        {
            parent::conectar();
            $request       = null;
            $consultaruser = '';
            $consultaruser = "select * from paquetes p where p.id > 3 and  p.id < 7";
            $lista         = parent::consultaTodo($consultaruser);
            if (count($lista) > 0) {
                $request = array(
                    'status'    => 'success',
                    'message'   => 'data reconsumir',
                    'data'      => $lista,
                    "membresia" => $this->obtenerDetallePlan(10)[0],
                );
            } else {
                $request = array(
                    'status'  => 'fail',
                    'message' => 'no data reconsumir',
                    'data'    => null,
                );
            }
            parent::cerrar();
            return $request;
        }
        
        public function imagen($url, $user)
        {
            parent::conectar();
            $request        = null;
            $consultarthree = '';
            $base64         = base64_decode(explode(',', $url)[1]);
            /* $filepath1 = "/var/www/html/peer2win/imagen/avatar/$user.png"; */
            $filepath1 = $_SERVER['DOCUMENT_ROOT'] . "/peer2win/imagen/avatar/$user.png";
            $filepath2 = "imagen/avatar/$user.png";
            file_put_contents($filepath1, $base64);
            $consultarthree = "UPDATE usuarios SET foto = '$filepath2' Where id ='$user'";
            $lista          = parent::query($consultarthree);
            if ($lista) {
                $request = array(
                    'status'  => 'success',
                    'message' => 'cambio de foto exitoso',
                    
                );
            } else {
                $request = array(
                    'status'  => 'error',
                    'message' => 'error en cambio de foto ',
                    
                );
            }
            parent::cerrar();
            return $request;
        }
        
        public function getUserNombre($user)
        {
            parent::conectar();
            $consultar1 = "select nombreCompleto, telefono, email from usuarios where id = " . $user['id'];
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            $request = array(
                'status'  => 'success',
                'message' => 'Nombre',
                'data'    => $lista[0]['nombreCompleto'],
                'phone'    => $lista[0]['telefono'],
                'email'    => $lista[0]['email'],
            );
            return $request;
        }
        
        public function saveSuscripcion($suscripcion)
        {
            parent::conectar();
            $request;
            $consultar1 = "INSERT INTO suscripcion (email) VALUE ('$suscripcion[email]')";
            $lista      = parent::queryRegistro($consultar1);
            parent::cerrar();
            if ($lista > 0) {
                $request = array(
                    'status'  => 'success',
                    'message' => 'Suscrito',
                );
            } else {
                $request = array(
                    'status'  => 'error',
                    'message' => 'error en el servidor',
                );
            }
            return $request;
        }
        
        public function getUserAdicionales($user)
        {
            parent::conectar();
            $consultar1 = "select * from usuarios where id = " . $user['id'];
            $lista1     = parent::consultarArreglo($consultar1);
            $consultar2 = "select * from rangos where id = " . $lista1['rango'];
            $lista2     = parent::consultarArreglo($consultar2);
            $consultar3 = "select * from fechas_corte_reconsumos where usuario = {$user['id']} and CURRENT_DATE BETWEEN fecha_inicio  and fecha_fin";
            $lista3     = parent::consultarArreglo($consultar3);
            if (count($lista3) == 0) {
                $fecha      = array(
                    'inico' => $lista1['fecha_inicio'],
                    'fin'   => $lista1['fecha_fin'],
                );
            } else {
                $fecha      = array(
                    'inico' => $lista1['fecha_inicio'],
                    'fin'   => $lista3['fecha_fin'],
                );    
            }
            
            parent::cerrar();
            $request = array(
                'status'  => 'success',
                'message' => 'Nombre',
                'rango'   => $lista2,
                'fecha'   => $fecha,
                'estado'  => $lista1['status'],
                'plan'    => $lista1['plan'],
                'distribuidor'    => $lista1['distribuidor'],
            );
            return $request;
        }
        
        public function getUser($user)
        {
            parent::conectar();
            $consultar1 = "select * from usuarios where email = '" . $user['email'] . "'";
            $lista      = parent::consultaTodo($consultar1);
            parent::cerrar();
            if (count($lista) > 0) {
                unset($lista[0]['password']);
                $request = array(
                    'status'  => 'success',
                    'message' => 'Nombre',
                    'data'    => $lista[0],
                );
            } else {
                $request = array(
                    'status'  => 'errorUser',
                    'message' => 'El usuario no existe',
                );
            }
            
            return $request;
        }
        
        public function saveTalentLMS($user)
        {
            $api    = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl   = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => "https://peers2win.talentlms.com/api/v1/usersignup",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_POSTFIELDS     => array(
                    'first_name' => $user['name'],
                    'last_name'  => $user['lasrname'],
                    'email'      => $user['email'],
                    'login'      => $user['username'],
                    'password'   => $user['password'],
                ),
                CURLOPT_HTTPHEADER     => array(
                    "Authorization: Basic " . $apiKey,
                ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }
        
        public function saveGroupTalentLMS($user)
        {
            $api    = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl   = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => "https://peers2win.talentlms.com/api/v1/addusertogroup/user_id:" . $user . ",group_key:aNgjgGPzg",
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
        
        public function updatePasswordTrans($user) {
            parent::conectar();
            
            $consultar1 = "select * from usuarios where id = '$user[id]'";
            $lista      = parent::consultaTodo($consultar1);
            
            if (count($lista) > 0) {
                if ($lista[0]['clave_trans'] == $user['antigua']) {
                    if ($lista[0]['clave_trans'] == $user['nueva']) {
                        $respuesta = array('status' => 'iguales', 'message' => 'La nueva contraseña es igual a la antigua');
                    }else{
                        $consultar2 = "UPDATE usuarios SET clave_trans = '" . $user['nueva'] . "' WHERE id = '" . $user['id'] . "'";
                        $lista2     = parent::query($consultar2);
                        $respuesta = array('status' => 'success', 'message' => 'Contraseña actualizada correctamente');
                    }
                } else {
                    $respuesta = array('status' => 'errorPassword', 'message' => 'El password no coinciden');
                }
            } else {
                $respuesta = array('status' => 'errorUser', 'message' => 'El usuario no existe');
            }
            
            parent::cerrar();
            return $respuesta;
        }

        public function updateUsuarioIdioma($usuario)
        {
            parent::conectar();
            $respuesta;
            $consultar2 = "UPDATE usuarios SET idioma='$usuario[idioma]' WHERE id=$usuario[id]";
            $preId      = parent::query($consultar2);
            if ($preId > 0) {
                $respuesta = array('status' => 'success', 'message' => 'Los datos del usuario han sido actualizados correctamente');
            } else {
                $respuesta = array('status' => 'error', 'message' => 'No se pudieron actualizar los datos del usuario');
            }
            parent::cerrar();
            return $respuesta;
        }


        public function nacionalidadRegistrar( Array $data )
        {
            if ( !isset($data) || !isset($data['email']) || !isset($data['empresa']) ) {
                return array(
                    'status'=>'fail',
                    'message'=>'Faltan datos',
                    'data'=> $data
                );
            }

            $uid             = null;
            $empresa         = intval($data['empresa']);
            $pais_country_id = intval( $data['country_id'] );
            $pais_iso_code_2 = null;
            $fiat_iso_code_2 = null;
            $monedas_local   = null;

            // USUARIOS: usando el atributo "paisid" el cual es el iso_code_2
            // EMPRESAS: usando el atributo "pais" el cual es el country_id

            // Obtener los datos de la tabla users peers2win
            if ( intval($data['empresa']) == 0 ) {
                parent::conectar();
                $selectxusuario = parent::consultaTodo("SELECT * FROM peer2win.usuarios WHERE email = '$data[email]';");
                parent::cerrar();
                if (count($selectxusuario) <= 0) {
                    return array(
                        'status'  => 'noRegistradoUsuario',
                        'message' => 'Este usuario no esta registrado en la base de datos actual -v1 .',
                        'data'    => $data
                    );
                }
                $selectxusuario            = $selectxusuario[0];
                $arrayNacionalidad['pais'] = $selectxusuario['paisid'];
                $uid                       = $selectxusuario['id'];
            }else{
                // Obtener los datos de la tabla empresas nasbi
                parent::conectar();
                $selectxusuario = parent::consultaTodo("SELECT * FROM buyinbig.empresas WHERE correo = '$data[email]';");
                parent::cerrar();
                if (count($selectxusuario) <= 0) {
                    return array(
                        'status'  => 'noRegistradoUsuario',
                        'message' => 'Esta empresa no esta registrado en la base de datos actual.',
                        'data'    => $data
                    );
                }
                $selectxusuario            = $selectxusuario[0];
                $arrayNacionalidad['pais'] = $selectxusuario['pais'];
                $uid                       = $selectxusuario['id'];
            }

            parent::conectar();
            $selectxnacionalidad = parent::consultaTodo("SELECT * FROM buyinbig.nacionalidades WHERE uid = '$uid' AND empresa = '$empresa';");
            parent::cerrar();
            if (count($selectxnacionalidad) > 0) {
                return array(
                    'status'  => 'registroDuplicado',
                    'message' => 'Las credenciales de este usuario ya se encuentrán registradas en la base de datos.',
                    'data'    => $data,
                    'result' => $selectxnacionalidad
                );
            }

            $array_monedas_locales = array_values((array) json_decode(parent::remoteRequest('http://peers2win.com/api/controllers/fiat/'), true));
            if(count($array_monedas_locales) > 0){
                $monedas_local = $this->filter_by_value($array_monedas_locales, 'country_id', $data['country_id']);
                if(count($monedas_local) > 0) {
                    $monedas_local = $monedas_local[0];
                }else{
                    $monedas_local = $this->filter_by_value($array_monedas_locales, 'code', 'USD');
                    $monedas_local = $monedas_local[0];
                    $monedas_local['iso_code_2'] = "US";
                }
            }else{
                return array(
                    'status'  => 'noMonedasLocales',
                    'message' => 'Verificar la ruta de las monedas locales JSON.',
                    'data'    => $array_monedas_locales
                );
            }

            $pais_iso_code_2 = $monedas_local['iso_code_2'];
            $fiat_iso_code_2 = $monedas_local['iso_code_2'];

            $insertxnacionalidad = "INSERT INTO nacionalidades (uid, empresa, pais_country_id, pais_iso_code_2, fiat_iso_code_2) VALUES('$uid', '$empresa', '$pais_country_id', '$pais_iso_code_2', '$fiat_iso_code_2')";
            
            parent::conectar();
            $insertar = parent::queryRegistro($insertxnacionalidad);
            parent::cerrar();

            if(!$insertar) {
                return array(
                    'status'  => 'noInsertarNacionalidad',
                    'message' => 'No fue posible insertar la nacionalidad',
                    'data'    => $insertxnacionalidad
                );
            }
            return array(
                'status'          => 'success',
                'message'         => 'Datos encontrados',
                'data'            => $monedas_local,
                'uid'             => $uid,
                'empresa'         => $empresa,
                'pais_country_id' => $pais_country_id,
                'pais_iso_code_2' => $pais_iso_code_2,
                'fiat_iso_code_2' => $fiat_iso_code_2 
            );
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

        public function nacionalidadID( Array $data )
        {
            if ( !isset($data) || !isset($data['uid']) || !isset($data['empresa']) ) {
                return array(
                    'status'=>'fail',
                    'message'=>'Faltan datos',
                    'data'=> $data
                );
            }
            parent::conectar();
            $selectxnacionalidad = parent::consultaTodo("SELECT * FROM buyinbig.nacionalidades WHERE uid = '$data[uid]' AND empresa = '$data[empresa]';");
            parent::cerrar();

            $result = null;
            if (count($selectxnacionalidad) <= 0) {
                return array(
                    'status'  => 'noRegistradoUsuario',
                    'message' => 'Este usuario no esta registrado en la base de datos actual - v2.',
                    'data'    => $data
                );
            }else{
                $result = $selectxnacionalidad[0];
            }

            return array(
                'status'  => 'success',
                'message' => 'Datos de la nacionalidad del usuario',
                'data'    => $result

            );
        }
    }
                                                        