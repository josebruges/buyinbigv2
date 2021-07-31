<?php

require 'nasbifunciones.php';

class Cupones extends Nasbifunciones
{


    protected $cupones = Array(
        "BONO_PRIMERA_COMPRA" => Array(
            "titulo"            => "BONO PRIMERA COMPRA",

            "descripcion"       => "El programa de Sello Nasbi premia al comprador en su primera compra con $10.000 pesos para ser gastado en tiendas certificadas con el Sello Nasbi.\n",

            "titulo_en"         => "FIRST BONUS BUYS",

            "descripcion_en"    => "The Nasbi Seal program rewards the buyer with their first purchase with $ 10,000 pesos to be spent in stores certified with the Nasbi Seal.",

            "codigo"            => "SJDGA8767H",

            "monto_descuento"   => 10000,
            "monto_minimo"      => 10000,

            "estado"            => 1,
            "fecha_creacion"    => "1620223200000",

            "fecha_vencimiento" => "1620223200000",
            "tipo"              => 1
        ),
        "BONO_REFERIDO" => Array(
            "titulo" => "BONO REFERIDO",

            "descripcion" => "Los compradores tendrán la posibilidad de referir nuevos compradores, por lo cual recibirán $5.000 una vez hayan llevado a cabo su primera compra en tiendas certificadas con el sello Nasbi.",

            "titulo_en" => "BOND REFERRED",

            "descripcion_en" => "Buyers will have the possibility to refer new buyers, for which they will receive $ 5,000 once they have made their first purchase in stores certified with the Nasbi seal.",
            
            "codigo" => "SJDGA87671H",

            "monto_descuento"   => 5000,
            "monto_minimo"      => 5000,
            "estado"            => 1,
            "fecha_creacion"    => "1620222821000",
            "fecha_vencimiento" => "1641013199000",
            "tipo"              => 2
        )
    );
    public function __construct(){
        // Ajuste hora actual colombia
        date_default_timezone_set('America/Bogota');
    }

    private $tabla_cupones = "buyinbig.cupones";
    private $tabla_cupones_historial = "buyinbig.cupones_historial";

    public function seleccionarDeBd(string $tabla, bool $con_condicon, string $condicion)
    {
        parent::conectar();
        $sql = "SELECT * FROM $tabla";
        if ($con_condicon == true) {
            $sql = $sql . $condicion;
        }
        $resultado = parent::consultaTodo($sql);
        parent::cerrar();
        return $resultado;
    }

    public function seleccionarDeBdPorPaginas(String $tabla, String $condicion, int $pagina_buscar, int $cantidad_datos)
    {
        $cantidad_datos_por_pagina = $cantidad_datos; //numero de items que quieres en una pagina
        $hasta = $pagina_buscar * $cantidad_datos_por_pagina;
        $desde = ($hasta - $cantidad_datos_por_pagina) + 1;
        $sql_paginado = "SELECT * FROM (
            SELECT *, (@row_number:=@row_number+1) AS num FROM (
                 SELECT * FROM $tabla c
                        JOIN (SELECT @row_number := 0) r
                ) as datos $condicion 
        ) AS info WHERE info.num BETWEEN '$desde' AND '$hasta'";

        parent::conectar();
        $resultado = parent::consultaTodo($sql_paginado);
        parent::cerrar();

        $respuesta_traer_todo_los_datos = array();
        if ($condicion != "") {
            $respuesta_traer_todo_los_datos = $this->seleccionarDeBd($tabla, true, $condicion);
        } else {
            $respuesta_traer_todo_los_datos = $this->seleccionarDeBd($tabla, false, "");
        }
        $totalpaginas = count($respuesta_traer_todo_los_datos) / $cantidad_datos_por_pagina;
        $totalpaginas = ceil($totalpaginas);

        return array(
            "data" => $resultado,
            "pagina" => $pagina_buscar,
            "total_paginas" => $totalpaginas
        );
    }

    public function retornar_columnas_para_insertar_bd(string $tabla)
    {
        if ($tabla == $this->tabla_cupones) {
            return "`titulo`, `descripcion`, `titulo_en`, `descripcion_en`, `codigo`, `monto_descuento`, `monto_minimo`, `estado`, `fecha_creacion`, `fecha_vencimiento`";
        } else if ($tabla == $this->tabla_cupones_historial) {
            return "`uid`, `empresa`, `cupon_tipo_id`, `fecha_creacion`, `estado`";
        }
    }

    public function retornar_datos_para_insertar_bd(string $tabla, array $datos)
    {
        if ($tabla == $this->tabla_cupones) {
            return "'$datos[titulo]', '$datos[descripcion]', '$datos[titulo_en]', '$datos[descripcion_en]', '$datos[codigo]', '$datos[monto_descuento]', '$datos[monto_minimo]', '$datos[estado]', '$datos[fecha_creacion]', '$datos[fecha_vencimiento]'";
        } else if ($tabla == $this->tabla_cupones_historial) {
            return "'$datos[uid]', '$datos[empresa]', '$datos[cupon_tipo_id]', '$datos[fecha_creacion]', '$datos[estado]'";
        }
    }

    public function insertarEnBd(string $tabla, array $datos)
    {
        $columnas = $this->retornar_columnas_para_insertar_bd($tabla);
        $datos = $this->retornar_datos_para_insertar_bd($tabla, $datos);
        parent::conectar();
        $sql = "INSERT INTO $tabla ($columnas) VALUES ($datos)";
        $resultado = parent::queryRegistro($sql);
        parent::cerrar();
        return $resultado;
    }

    public function retornar_datos_para_actualizar_bd(string $tabla, array $datos)
    {
        if ($tabla == $this->tabla_cupones) {
            return "titulo = '$datos[titulo]', descripcion = '$datos[descripcion]', titulo_en = '$datos[titulo_en]', descripcion_en = '$datos[descripcion_en]', codigo = '$datos[codigo]', monto_descuento = '$datos[monto_descuento]', monto_minimo = '$datos[monto_minimo]', estado = '$datos[estado]', fecha_creacion = '$datos[fecha_creacion]', fecha_vencimiento = '$datos[fecha_vencimiento]'";
        } else if ($tabla == $this->tabla_cupones_historial) {
            return "uid = '$datos[uid]', empresa = '$datos[empresa]', cupon_tipo_id = '$datos[cupon_tipo_id]', fecha_creacion = '$datos[fecha_creacion]', estado = '$datos[estado]'";
        }
    }

    public function actualizarEnBd(string $tabla, array $datos, string $condicion_actualizar)
    {
        $nuevos_datos = $this->retornar_datos_para_actualizar_bd($tabla, $datos);
        parent::conectar();
        $sql = "UPDATE $tabla SET $nuevos_datos WHERE $condicion_actualizar";
        $resultado = parent::query($sql);
        parent::cerrar();
        return $resultado;
    }

    // public function eliminarEnBd($tabla, $condicion)
    // {
    //     parent::conectar();
    //     $sql = "DELETE FROM $tabla WHERE $condicion";
    //     $resultado = parent::query($sql);
    //     parent::cerrar();
    //     return $resultado;
    // }

    function convertir_dia_str_como_array(String $dia)
    {
        $array_dia = explode('-', $dia);
        if (strlen($array_dia[1]) == 1) {
            $array_dia[1] = "0" . $array_dia[1];
        } else if (strlen($array_dia[1]) == 2) {
            $array_dia[1] = $array_dia[1];
        } else {
            $array_dia[1] = "00"; // error de formato
        }
        if (strlen($array_dia[2]) == 1) {
            $array_dia[2] = "0" . $array_dia[2];
        } else if (strlen($array_dia[2]) == 2) {
            $array_dia[2] = $array_dia[2];
        } else {
            $array_dia[2] = "00"; // error de formato
        }
        return array(
            "y" => $array_dia[0],
            "m" => $array_dia[1],
            "d" => $array_dia[2]
        );
    }

    function convertir_hora_str_como_array(String $hora)
    {
        $array_hora = explode(':', $hora);
        if (strlen($array_hora[0]) == 1) {
            $array_hora[0] = "0" . $array_hora[0];
        } else if (strlen($array_hora[0]) == 2) {
            $array_hora[0] = $array_hora[0];
        } else {
            $array_hora[0] = "00"; // error de formato
        }
        if (strlen($array_hora[1]) == 1) {
            $array_hora[1] = "0" . $array_hora[1];
        } else if (strlen($array_hora[1]) == 2) {
            $array_hora[1] = $array_hora[1];
        } else {
            $array_hora[1] = "00"; // error de formato
        }
        if (strlen($array_hora[2]) == 1) {
            $array_hora[2] = "0" . $array_hora[2];
        } else if (strlen($array_hora[2]) == 2) {
            $array_hora[2] = $array_hora[2];
        } else {
            $array_hora[2] = "00"; // error de formato
        }
        return array(
            "h" => $array_hora[0],
            "i" => $array_hora[1],
            "s" => $array_hora[2]
        );
    }

    function convertir_fecha_str_como_array(String $dia, String $hora)
    {
        $dia = $this->convertir_dia_str_como_array($dia);
        $hora = $this->convertir_hora_str_como_array($hora);
        return array(
            "y" => $dia["y"],
            "m" => $dia["m"],
            "d" => $dia["d"],
            "h" => $hora["h"],
            "i" => $hora["i"],
            "s" => $hora["s"]
        );
    }

    function convertir_dia_array_a_str(array $dia)
    {
        return "$dia[y]-$dia[m]-$dia[d]";
    }

    function convertir_hora_array_a_str(array $hora)
    {
        return "$hora[h]:$hora[i]:$hora[s]";
    }

    function convertir_fecha_array_a_str(array $dia, array $hora)
    {
        $dia = $this->convertir_dia_array_a_str($dia);
        $hora = $this->convertir_hora_array_a_str($hora);
        return "$dia $hora";
    }

    function getFechaActual()
    {
        $fecha_actual = date("Y-m-d H:i:s", intval(microtime(true)));
        $array_fecha = explode(' ', $fecha_actual);
        $fecha = $this->convertir_fecha_str_como_array($array_fecha[0], $array_fecha[1]);
        return $fecha;
    }

    function diferencia_entre_fechas(String $dia_inical, String $dia_final)
    {
        return date_diff(date_create($dia_inical), date_create($dia_final));
    }

    function esta_una_fecha_entre_dos_fechas(String $fecha_inicio, String $fecha_comparar, String $fecha_fin)
    {
        $fecha_inicio = strtotime($fecha_inicio);
        $fecha_fin = strtotime($fecha_fin);
        $fecha_comparar = strtotime($fecha_comparar);
        if (($fecha_comparar >= $fecha_inicio) && ($fecha_comparar <= $fecha_fin)) {
            return true;
        } else {
            return false;
        }
    }

    function transformar_fecha_u_to_normal(array $data)
    {
        if ($data["vienePor"] == "front") {
            return date("Y-m-d H:i:s", intval($data["fecha_unix"]));
        } else if ($data["vienePor"] == "bd") { //en la bd se guardan multiplicada por mil 
            return date("Y-m-d H:i:s", intval($data["fecha_unix"]) / 1000);
        }
    }

    function listar(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["pagina"])) {
            $cupones_traidos = $this->seleccionarDeBdPorPaginas($this->tabla_cupones, "", intval($data["pagina"]), 10);
            $cupones = array();
            foreach ($cupones_traidos["data"] as $datos_cupones) {

                $data_a_mostrar = $datos_cupones;
                $data_a_mostrar["monto_descuento"] = strval(doubleval($data_a_mostrar["monto_descuento"]));
                $data_a_mostrar["monto_minimo"] = strval(doubleval($data_a_mostrar["monto_minimo"]));
                $data_a_mostrar["monto_descuento_mascara"] = parent::maskNumber($data_a_mostrar["monto_descuento"], 2);
                $data_a_mostrar["monto_minimo_mascara"] = parent::maskNumber($data_a_mostrar["monto_minimo"], 2);

                $creacion = $this->transformar_fecha_u_to_normal(array(
                    "vienePor" => "bd",
                    "fecha_unix" => $data_a_mostrar["fecha_creacion"]
                ));
                $data_a_mostrar["fecha_creacion"] = $creacion;
                $actual = $this->convertir_fecha_array_a_str($this->getFechaActual(), $this->getFechaActual());
                $vencimiento = $this->transformar_fecha_u_to_normal(array(
                    "vienePor" => "bd",
                    "fecha_unix" => $data_a_mostrar["fecha_vencimiento"]
                ));
                $data_a_mostrar["fecha_vencimiento"] = $vencimiento;
                if ($this->esta_una_fecha_entre_dos_fechas($creacion, $actual, $vencimiento)) {
                    $data_a_mostrar["estado"] = "1";
                } else {
                    $data_a_mostrar["estado"] = "0";
                }

                $diferencia_entre_fechas = $this->diferencia_entre_fechas($actual, $vencimiento);
                if (intval($diferencia_entre_fechas->invert) == 1) {
                    $data_a_mostrar["esta_vencido"] = "1";
                    $data_a_mostrar["dias_restantes"] = "0";
                } else {
                    $data_a_mostrar["esta_vencido"] = "0";
                    $data_a_mostrar["dias_restantes"] = strval(intval($diferencia_entre_fechas->days));
                }

                $data_a_mostrar["moneda_local"] = "COP";

                array_push($cupones, $data_a_mostrar);
            }
            if (count($cupones) > 0) {
                return array(
                    "status"  => "success",
                    "message" => "listado completo de los cupones",
                    "pagina" => $cupones_traidos["pagina"],
                    "total_paginas" => $cupones_traidos["total_paginas"],
                    "data"    => $cupones
                );
            } else {
                return array(
                    "status"  => "sinDatosEnLaPagina",
                    "message" => "en la pagina $cupones_traidos[pagina] no hay datos",
                    "pagina" => $cupones_traidos["pagina"],
                    "total_paginas" => $cupones_traidos["total_paginas"],
                    "data"    => null
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    function filtrar($data)
    {
        if (
            isset($data) &&
            isset($data["pagina"]) &&
            isset($data["inactivo"]) &&
            isset($data["disponible"]) &&
            isset($data["uid"]) &&
            isset($data["empresa"])
            // isset($data["porVencer"]) &&
            // isset($data["vencido"])
        ) {
            $cupones_traidos = $this->seleccionarDeBdPorPaginas($this->tabla_cupones, "", intval($data["pagina"]), 10);
            $cupones = array();
            foreach ($cupones_traidos["data"] as $datos_cupones) {
                $data_a_mostrar = $datos_cupones;
                $data_a_mostrar["monto_descuento"] = strval(doubleval($data_a_mostrar["monto_descuento"]));
                $data_a_mostrar["monto_minimo"] = strval(doubleval($data_a_mostrar["monto_minimo"]));
                $data_a_mostrar["monto_descuento_mascara"] = parent::maskNumber($data_a_mostrar["monto_descuento"], 2);
                $data_a_mostrar["monto_minimo_mascara"] = parent::maskNumber($data_a_mostrar["monto_minimo"], 2);

                $creacion = $this->transformar_fecha_u_to_normal(array(
                    "vienePor" => "bd",
                    "fecha_unix" => $data_a_mostrar["fecha_creacion"]
                ));
                $data_a_mostrar["fecha_creacion"] = $creacion;
                $actual = $this->convertir_fecha_array_a_str($this->getFechaActual(), $this->getFechaActual());
                $vencimiento = $this->transformar_fecha_u_to_normal(array(
                    "vienePor" => "bd",
                    "fecha_unix" => $data_a_mostrar["fecha_vencimiento"]
                ));
                $data_a_mostrar["fecha_vencimiento"] = $vencimiento;
                if ($this->esta_una_fecha_entre_dos_fechas($creacion, $actual, $vencimiento)) {
                    $data_a_mostrar["estado"] = "1";
                } else {
                    $data_a_mostrar["estado"] = "0";
                }

                $diferencia_entre_fechas = $this->diferencia_entre_fechas($actual, $vencimiento);
                if (intval($diferencia_entre_fechas->invert) == 1) {
                    $data_a_mostrar["esta_vencido"] = "1";
                    $data_a_mostrar["dias_restantes"] = "0";
                } else {
                    $data_a_mostrar["esta_vencido"] = "0";
                    $data_a_mostrar["dias_restantes"] = strval(intval($diferencia_entre_fechas->days));
                }

                $data_a_mostrar["moneda_local"] = "COP";

                if (intval($data["inactivo"]) == 1 && intval($data_a_mostrar["estado"]) == 0) {
                    array_push($cupones, $data_a_mostrar);
                } else if (intval($data["disponible"]) == 1 && intval($data_a_mostrar["estado"]) == 1) {
                    array_push($cupones, $data_a_mostrar);
                }
                // } else if (intval($data["porVencer"]) == 1) {
                //     if (
                //         intval($data_a_mostrar["estado"]) != 2 &&
                //         intval($diferencia_entre_fechas->invert) == 0 &&
                //         doubleval($diferencia_entre_fechas->days) >= 0 &&
                //         doubleval($diferencia_entre_fechas->days) <= 3
                //     ) {
                //         array_push($cupones, $data_a_mostrar);
                //     }
                // } else if (intval($data["vencido"]) == 1 && intval($diferencia_entre_fechas->invert) == 1) {
                //     array_push($cupones, $data_a_mostrar);
                // }
            }
            if (count($cupones) > 0) {
                return array(
                    "status"  => "success",
                    "message" => "listado de cupones por filtrado",
                    "pagina" => $cupones_traidos["pagina"],
                    "total_paginas" => $cupones_traidos["total_paginas"],
                    "data"    => $cupones
                );
            } else {
                return array(
                    "status"  => "sinDatosEnLaPagina",
                    "message" => "en la pagina $cupones_traidos[pagina] no hay datos con el filtro indicado",
                    "pagina" => $cupones_traidos["pagina"],
                    "total_paginas" => $cupones_traidos["total_paginas"],
                    "data"    => null
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    function buscar(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["codigo"])) {
            if ($data["codigo"] != "") {
                $condicion = " WHERE codigo LIKE '$data[codigo]'";
                $respuesta_busqueda = $this->seleccionarDeBd($this->tabla_cupones, true, $condicion);
                if (empty($respuesta_busqueda)) {
                    return array(
                        "status"  => "cuponNoExiste",
                        "message" => "no existe un cupon con el codigo `$data[codigo]`",
                        "data"    => null
                    );
                } else {
                    $data_a_enviar = $respuesta_busqueda[0];
                    $data_a_enviar["monto_descuento"]  = floatval($data_a_enviar["monto_descuento"]); //strval(doubleval($data_a_enviar["monto_descuento"]));
                    $data_a_enviar["monto_minimo"] = floatval($data_a_enviar["monto_minimo"]); //strval(doubleval($data_a_enviar["monto_minimo"]));

                    $data_a_enviar["monto_descuento_mascara"] = parent::maskNumber($data_a_enviar["monto_descuento"], 2);
                    $data_a_enviar["monto_minimo_mascara"] = parent::maskNumber($data_a_enviar["monto_minimo"], 2);

                    $creacion = $this->transformar_fecha_u_to_normal(array(
                        "vienePor" => "bd",
                        "fecha_unix" => $data_a_enviar["fecha_creacion"]
                    ));
                    $data_a_enviar["fecha_creacion"] = $creacion;
                    $actual = $this->convertir_fecha_array_a_str($this->getFechaActual(), $this->getFechaActual());
                    $vencimiento = $this->transformar_fecha_u_to_normal(array(
                        "vienePor" => "bd",
                        "fecha_unix" => $data_a_enviar["fecha_vencimiento"]
                    ));
                    $data_a_enviar["fecha_vencimiento"] = $vencimiento;
                    if ($this->esta_una_fecha_entre_dos_fechas($creacion, $actual, $vencimiento)) {
                        $data_a_enviar["estado"] = "1";
                    } else {
                        $data_a_enviar["estado"] = "0";
                    }

                    $diferencia_entre_fechas = $this->diferencia_entre_fechas($actual, $vencimiento);
                    if (intval($diferencia_entre_fechas->invert) == 1) {
                        $data_a_enviar["esta_vencido"] = "1";
                        $data_a_enviar["dias_restantes"] = "0";
                    } else {
                        $data_a_enviar["esta_vencido"] = "0";
                        $data_a_enviar["dias_restantes"] = strval(intval($diferencia_entre_fechas->days));
                    }

                    $data_a_enviar["moneda_local"] = "COP";

                    $data_a_guardar = $data_a_enviar;
                    $fecha_creacion_unix = new DateTime($data_a_guardar["fecha_creacion"]);
                    $data_a_guardar["fecha_creacion"] = intval($fecha_creacion_unix->getTimestamp()) * 1000;
                    $fecha_vencimiento_unix = new DateTime($data_a_guardar["fecha_vencimiento"]);
                    $data_a_guardar["fecha_vencimiento"] = intval($fecha_vencimiento_unix->getTimestamp()) * 1000;
                    $condicion = "codigo = '$data[codigo]'";
                    $this->actualizarEnBd($this->tabla_cupones, $data_a_guardar, $condicion);

                    return array(
                        "status"  => "success",
                        "message" => "cupon encontrado con exito",
                        "data"    => $data_a_enviar
                    );
                }
            } else {
                return array(
                    "status"  => "envioCodigoVacio",
                    "message" => "el campo CODIGO de la data, lo envio vacio",
                    "data"    => null
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    function verificar_fechas_data(array $data)
    {
        $array_fecha_actual = $this->getFechaActual();
        $array_dia_creacion = $this->convertir_dia_str_como_array($data["fecha_creacion"]);
        $array_dia_vencimiento = $this->convertir_dia_str_como_array($data["fecha_vencimiento"]);

        $date_time_dia_actual = date_create($this->convertir_dia_array_a_str($array_fecha_actual));
        $date_time_dia_creacion = date_create($this->convertir_dia_array_a_str($array_dia_creacion));
        $date_time_dia_vencimiento = date_create($this->convertir_dia_array_a_str($array_dia_vencimiento));

        $intervalo_dia_actual_creacion = date_diff($date_time_dia_actual, $date_time_dia_creacion);
        $intervalo_dia_actual_vencimiento = date_diff($date_time_dia_actual, $date_time_dia_vencimiento);
        $intervalo_dia_vencimiento_creacion = date_diff($date_time_dia_creacion, $date_time_dia_vencimiento);

        if (intval($intervalo_dia_actual_creacion->invert) == 1) {
            return false;
        }
        if (intval($intervalo_dia_actual_vencimiento->invert) == 1) {
            return false;
        }
        if (intval($intervalo_dia_vencimiento_creacion->invert) == 1) {
            return false;
        }

        if ($data["hora_creacion"] != "" && $data["hora_vencimiento"] != "") {

            $array_hora_creacion = $this->convertir_hora_str_como_array($data["hora_creacion"]);
            $array_hora_vencimiento = $this->convertir_hora_str_como_array($data["hora_vencimiento"]);

            $date_time_fecha_actual = date_create($this->convertir_fecha_array_a_str($array_fecha_actual, $array_fecha_actual));
            $date_time_fecha_creacion = date_create($this->convertir_fecha_array_a_str($array_dia_creacion, $array_hora_creacion));
            $date_time_fecha_vencimiento = date_create($this->convertir_fecha_array_a_str($array_dia_vencimiento, $array_hora_vencimiento));

            $intervalo_fecha_actual_creacion = date_diff($date_time_fecha_actual, $date_time_fecha_creacion);
            $intervalo_fecha_actual_vencimiento = date_diff($date_time_fecha_actual, $date_time_fecha_vencimiento);
            $intervalo_fecha_vencimiento_creacion = date_diff($date_time_fecha_creacion, $date_time_fecha_vencimiento);

            if (intval($intervalo_fecha_actual_creacion->invert) == 1) {
                return false;
            }
            if (intval($intervalo_fecha_actual_vencimiento->invert) == 1) {
                return false;
            }
            if (intval($intervalo_fecha_vencimiento_creacion->invert) == 1) {
                return false;
            }
        }

        return true;
    }

    function verificar_data_crear_actualizar_cupones(array $data)
    {
        if (isset($data)) {

            if (!isset($data["uid"]) || $data["uid"] == "") {
                return false;
            }

            if (!isset($data["empresa"]) || $data["empresa"] == "") {
                return false;
            }

            if (!isset($data["titulo"]) || $data["titulo"] == "") {
                // echo "1<br>";
                return false;
            }
            if (!isset($data["descripcion"]) || $data["descripcion"] == "") {
                // echo "2<br>";
                return false;
            }
            if (!isset($data["titulo_en"]) || $data["titulo_en"] == "") {
                // echo "1<br>";
                return false;
            }
            if (!isset($data["descripcion_en"]) || $data["descripcion_en"] == "") {
                // echo "2<br>";
                return false;
            }
            if (!isset($data["codigo"]) || $data["codigo"] == "") {
                // echo "3<br>";
                return false;
            }
            if (!isset($data["monto_descuento"]) || $data["monto_descuento"] == "") {
                // echo "4<br>";
                return false;
            }
            if (!isset($data["monto_minimo"]) || $data["monto_minimo"] == "") {
                // echo "5<br>";
                return false;
            }
            if (!isset($data["fecha_creacion"]) || $data["fecha_creacion"] == "") {
                // echo "7<br>";
                return false;
            }
            if (!isset($data["hora_creacion"])) {
                // echo "8<br>";
                return false;
            }
            if (!isset($data["fecha_vencimiento"]) || $data["fecha_vencimiento"] == "") {
                // echo "9<br>";
                return false;
            }
            if (!isset($data["hora_vencimiento"])) {
                // echo "10<br>";
                return false;
            }
            if (
                ($data["hora_creacion"] != "" && $data["hora_vencimiento"] == "") ||
                ($data["hora_creacion"] == "" && $data["hora_vencimiento"] != "")
            ) {
                // echo "11<br>";
                return false;
            }
        } else {
            // echo "12<br>";
            return false;
        }

        return true;
    }

    function asignar_horas_fecha(array $dia_creacion, array $dia_vencimiento)
    {
        $fecha_a_ingresar_bd = array();
        $array_fecha_actual = $this->getFechaActual();
        // echo json_encode(array("actual" => $array_fecha_actual));

        if ($dia_creacion["y"] > $array_fecha_actual["y"]) {
            $fecha_a_ingresar_bd["fecha_creacion"] = $this->convertir_fecha_array_a_str($dia_creacion, array(
                "h" => "00",
                "i" => "00",
                "s" => "01"
            ));
        } else if ($dia_creacion["y"] == $array_fecha_actual["y"]) {
            if ($dia_creacion["m"] > $array_fecha_actual["m"]) {
                $fecha_a_ingresar_bd["fecha_creacion"] = $this->convertir_fecha_array_a_str($dia_creacion, array(
                    "h" => "00",
                    "i" => "00",
                    "s" => "01"
                ));
            } else if ($dia_creacion["m"] == $array_fecha_actual["m"]) {
                if ($dia_creacion["d"] > $array_fecha_actual["d"]) {
                    $fecha_a_ingresar_bd["fecha_creacion"] = $this->convertir_fecha_array_a_str($dia_creacion, array(
                        "h" => "00",
                        "i" => "00",
                        "s" => "01"
                    ));
                } else {
                    $fecha_a_ingresar_bd["fecha_creacion"] = $this->convertir_fecha_array_a_str($dia_creacion, array(
                        "h" => $array_fecha_actual["h"],
                        "i" => $array_fecha_actual["i"],
                        "s" => $array_fecha_actual["s"]
                    ));
                }
            }
        }

        $fecha_a_ingresar_bd["fecha_vencimiento"] = $this->convertir_fecha_array_a_str($dia_vencimiento, array(
            "h" => "23",
            "i" => "59",
            "s" => "59"
        ));

        return $fecha_a_ingresar_bd;
    }

    function crear(array $data)
    {
        if ($this->verificar_data_crear_actualizar_cupones($data)) {
            $respuesta_buscar_cupon = $this->buscar($data);
            if ($respuesta_buscar_cupon["status"] == "cuponNoExiste") {
                if ($this->verificar_fechas_data($data)) {
                    $data_a_enviar = $data;
                    if ($data_a_enviar["hora_creacion"] == "" && $data_a_enviar["hora_vencimiento"] == "") {
                        $array_dia_creacion = $this->convertir_dia_str_como_array($data["fecha_creacion"]);
                        $array_dia_vencimiento = $this->convertir_dia_str_como_array($data["fecha_vencimiento"]);
                        $data_generada_fecha = $this->asignar_horas_fecha($array_dia_creacion, $array_dia_vencimiento);
                        $data_a_enviar["fecha_creacion"] = $data_generada_fecha["fecha_creacion"];
                        $data_a_enviar["fecha_vencimiento"] = $data_generada_fecha["fecha_vencimiento"];
                    } else {
                        $array_dia_creacion = $this->convertir_dia_str_como_array($data["fecha_creacion"]);
                        $array_hora_creacion = $this->convertir_hora_str_como_array($data["hora_creacion"]);
                        $data_a_enviar["fecha_creacion"] = $this->convertir_fecha_array_a_str($array_dia_creacion, $array_hora_creacion);
                        $array_dia_vencimiento = $this->convertir_dia_str_como_array($data["fecha_vencimiento"]);
                        $array_hora_vencimiento = $this->convertir_hora_str_como_array($data["hora_vencimiento"]);
                        $data_a_enviar["fecha_vencimiento"] = $this->convertir_fecha_array_a_str($array_dia_vencimiento, $array_hora_vencimiento);
                    }
                    $creacion = $data_a_enviar["fecha_creacion"];
                    $actual = $this->convertir_fecha_array_a_str($this->getFechaActual(), $this->getFechaActual());
                    $vencimiento = $data_a_enviar["fecha_vencimiento"];
                    if ($this->esta_una_fecha_entre_dos_fechas($creacion, $actual, $vencimiento)) {
                        $data_a_enviar["estado"] = "1";
                    } else {
                        $data_a_enviar["estado"] = "0";
                    }
                    $data_a_guardar = $data_a_enviar;
                    $fecha_creacion_unix = new DateTime($data_a_guardar["fecha_creacion"]);
                    $data_a_guardar["fecha_creacion"] = intval($fecha_creacion_unix->getTimestamp()) * 1000;
                    $fecha_vencimiento_unix = new DateTime($data_a_guardar["fecha_vencimiento"]);
                    $data_a_guardar["fecha_vencimiento"] = intval($fecha_vencimiento_unix->getTimestamp()) * 1000;
                    $respuesta_crear_cupon = $this->insertarEnBd($this->tabla_cupones, $data_a_guardar);
                    if ($respuesta_crear_cupon > 0) {
                        return array(
                            "status"  => "success",
                            "message" => "cupon creado con exito",
                            "data"    => $data_a_enviar
                        );
                    } else {
                        return array(
                            "status"  => "errorinsertarEnBdCupones",
                            "message" => "ha ocurrido un problema al guardar el cupon en la base de datos",
                            "data"    => null
                        );
                    }
                } else {
                    return array(
                        "status"  => "fechasMalas",
                        "message" => "esta ingresando una fecha no permitida",
                        "data"    => null
                    );
                }
            } else {
                return array(
                    "status"  => "cuponYaExiste",
                    "message" => "ya existe un cupon con el codigo `$data[codigo]`",
                    "data"    => null
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    function crearCuponPorTipo(array $data)
    {
        if ( !isset($data) && !isset($data["uid"]) && !isset($data["empresa"]) && !isset($data["tipo"]) ) {
            return array(
                'status'  => 'fail',
                'message' => 'no data',
                'data'    => null
            );
        }

        $fecha        = $this->getTimestamp();
        $data['tipo'] = intval($data['tipo']);

        $schemaCupon = Array();
        if( $data['tipo'] == 1 ){
            $schemaCupon = $this->cupones['BONO_PRIMERA_COMPRA'];

        }else{
            $schemaCupon = $this->cupones['BONO_REFERIDO'];

        }
        
        $selectxidxmax = "SELECT MAX(id) AS 'MAX' FROM buyinbig.cupones;";
        parent::conectar();
        $MAX_ID = parent::consultaTodo($selectxidxmax);
        parent::cerrar();

        $NEXT_ROW = 1;
        if( COUNT($MAX_ID) > 0 ){
            $NEXT_ROW = intval( $MAX_ID[0]['MAX'] ) + 1;
        }

        $codigo                           = $schemaCupon['codigo'] . $NEXT_ROW . $data["uid"] . $data["empresa"];

        $schemaCupon['fecha_creacion']    = $fecha['timestamp_fechaActual'];
        $schemaCupon['fecha_vencimiento'] = $fecha['timestamp_fechaVencimiento'];



        $insert =
            "INSERT INTO buyinbig.cupones (
                titulo,
                descripcion,
                titulo_en,
                descripcion_en,
                codigo,
                monto_descuento,
                monto_minimo,
                estado,
                fecha_creacion,
                fecha_vencimiento,
                tipo
            ) VALUES (
                '$schemaCupon[titulo]',
                '$schemaCupon[descripcion]',
                '$schemaCupon[titulo_en]',
                '$schemaCupon[descripcion_en]',
                '$codigo',
                $schemaCupon[monto_descuento],
                $schemaCupon[monto_minimo],
                1,
                '$schemaCupon[fecha_creacion]',
                '$schemaCupon[fecha_vencimiento]',
                '$schemaCupon[tipo]'
            )";

        parent::conectar();
        $rowAffect = parent::queryRegistro($insert);
        parent::cerrar();
        
        if( $rowAffect == 0 ){
            return array(
                'status'  => 'errorInsertCupon',
                'message' => 'no data',
                'data'    => $rowAffect
            );
        }

        $insertxHistorial =
            "INSERT INTO buyinbig.cupones_historial (
                uid,
                empresa,
                cupon_tipo_id,
                fecha_creacion,
                estado
            ) VALUES (
                $data[uid],
                $data[empresa],
                '$codigo',
                '$schemaCupon[fecha_creacion]',
                1
            )";
        parent::conectar();
        $rowAffectHistorial = parent::queryRegistro($insertxHistorial);
        parent::cerrar();
        
        if( $rowAffectHistorial == 0 ){
            return array(
                'status'             => 'errorInsertCupon',
                'message'            => 'no data',
                'data'               => null,
                'rowAffect'          => $rowAffect,
                'rowAffectHistorial' => $rowAffectHistorial
            );
        }
        return array(
            'status'             => 'success',
            'message'            => 'Se ha insertado un nuevo cupón en la base de datos.',
            'data'               => $codigo,
            'rowAffect'          => $rowAffect,
            'rowAffectHistorial' => $rowAffectHistorial
        );
    }

    function actualizar(array $data)
    {
        if ($this->verificar_data_crear_actualizar_cupones($data)) {
            $respuesta_buscar_cupon = $this->buscar($data);
            if ($respuesta_buscar_cupon["status"] == "success") {
                if ($this->verificar_fechas_data($data)) {
                    $data_a_enviar = $data;
                    if ($data_a_enviar["hora_creacion"] == "" && $data_a_enviar["hora_vencimiento"] == "") {
                        $array_dia_creacion = $this->convertir_dia_str_como_array($data["fecha_creacion"]);
                        $array_dia_vencimiento = $this->convertir_dia_str_como_array($data["fecha_vencimiento"]);
                        $data_generada_fecha = $this->asignar_horas_fecha($array_dia_creacion, $array_dia_vencimiento);
                        $data_a_enviar["fecha_creacion"] = $data_generada_fecha["fecha_creacion"];
                        $data_a_enviar["fecha_vencimiento"] = $data_generada_fecha["fecha_vencimiento"];
                    } else {
                        $array_dia_creacion = $this->convertir_dia_str_como_array($data["fecha_creacion"]);
                        $array_hora_creacion = $this->convertir_hora_str_como_array($data["hora_creacion"]);
                        $data_a_enviar["fecha_creacion"] = $this->convertir_fecha_array_a_str($array_dia_creacion, $array_hora_creacion);
                        $array_dia_vencimiento = $this->convertir_dia_str_como_array($data["fecha_vencimiento"]);
                        $array_hora_vencimiento = $this->convertir_hora_str_como_array($data["hora_vencimiento"]);
                        $data_a_enviar["fecha_vencimiento"] = $this->convertir_fecha_array_a_str($array_dia_vencimiento, $array_hora_vencimiento);
                    }
                    $creacion = $data_a_enviar["fecha_creacion"];
                    $actual = $this->convertir_fecha_array_a_str($this->getFechaActual(), $this->getFechaActual());
                    $vencimiento = $data_a_enviar["fecha_vencimiento"];
                    if ($this->esta_una_fecha_entre_dos_fechas($creacion, $actual, $vencimiento)) {
                        $data_a_enviar["estado"] = "1";
                    } else {
                        $data_a_enviar["estado"] = "0";
                    }
                    $data_a_guardar = $data_a_enviar;
                    $fecha_creacion_unix = new DateTime($data_a_guardar["fecha_creacion"]);
                    $data_a_guardar["fecha_creacion"] = intval($fecha_creacion_unix->getTimestamp()) * 1000;
                    $fecha_vencimiento_unix = new DateTime($data_a_guardar["fecha_vencimiento"]);
                    $data_a_guardar["fecha_vencimiento"] = intval($fecha_vencimiento_unix->getTimestamp()) * 1000;
                    $condicion = "codigo = '$data[codigo]'";
                    $respuesta_actualizar_cupon = $this->actualizarEnBd($this->tabla_cupones, $data_a_guardar, $condicion);
                    if ($respuesta_actualizar_cupon == true) {
                        return array(
                            "status"  => "success",
                            "message" => "cupon actualizado con exito",
                            "data"    => $data_a_enviar
                        );
                    } else {
                        return array(
                            "status"  => "errorActualizarEnBdCupones",
                            "message" => "ha ocurrido un problema al actualizar el cupon en la base de datos",
                            "data"    => null
                        );
                    }
                } else {
                    return array(
                        "status"  => "fechasMalas",
                        "message" => "esta ingresando una fecha no permitida",
                        "data"    => null
                    );
                }
            } else {
                return $respuesta_buscar_cupon;
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    function puede_usar_cupon(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["codigo"])) {
            
            $respuesta_buscar_cupon = $this->buscar($data);

            if ($respuesta_buscar_cupon["status"] == "success") {
                $data_cupon = $respuesta_buscar_cupon["data"];
                $estado_cupon_en_historial = "";
                if (intval($data_cupon["esta_vencido"]) == 1) {
                    $estado_cupon_en_historial = "3";
                } else {
                    if (intval($data_cupon["estado"]) == 1) {
                        $estado_cupon_en_historial = "1";
                    } else {
                        $estado_cupon_en_historial = "0";
                    }
                }
                $data_cupon['id']   = intval($data_cupon['id']);
                $data['data_cupon'] = $data_cupon;
                return $this->validarRestriccionesDelCupones($data);

            } else {
                return array(
                    "status"  => "cuponNoExiste",
                    "message" => "no existe un cupon con el codigo `$data[codigo]`",
                    "data"    => null
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
        return array(
            "status"  => "fail",
            "message" => "data incompleta",
            "data"    => null
        );
    }

    function guardar_cupon_en_historial(array $data)
    {
        if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["codigo"])) {
            $buscar_cupon = $this->buscar($data);
            if ($buscar_cupon["status"] == 'success') {
                $datos_cupon = $buscar_cupon["data"];
                $condicion_a_buscar = " WHERE uid = '$data[uid]' AND empresa = '$data[empresa]' AND cupon_tipo_id = '$datos_cupon[codigo]'";
                $respuesta_buscar_registro_en_cupones_historial = $this->seleccionarDeBd($this->tabla_cupones_historial, true, $condicion_a_buscar);
                if (empty($respuesta_buscar_registro_en_cupones_historial)) {
                    $array_fecha_actual = $this->getFechaActual();
                    $fecha_actual_str = $this->convertir_fecha_array_a_str($array_fecha_actual, $array_fecha_actual);
                    $fecha_actual_unix = new DateTime($fecha_actual_str);
                    $fecha_a_guardar = intval($fecha_actual_unix->getTimestamp()) * 1000;
                    $estado_guardar_cupon_en_historial = "";
                    if (intval($datos_cupon["esta_vencido"]) == 1) {
                        $estado_guardar_cupon_en_historial = "3";
                    } else {
                        if (intval($datos_cupon["estado"]) == 1) {
                            $estado_guardar_cupon_en_historial = "1";
                        } else {
                            $estado_guardar_cupon_en_historial = "0";
                        }
                    }
                    $datos_a_enviar = array(
                        "uid" => "$data[uid]",
                        "empresa" => "$data[empresa]",
                        "cupon_tipo_id" => "$data[codigo]",
                        "fecha_creacion" => "$fecha_a_guardar",
                        "estado" => $estado_guardar_cupon_en_historial
                    );
                    $resultado_insertar = $this->insertarEnBd($this->tabla_cupones_historial, $datos_a_enviar);
                    if ($resultado_insertar > 0) {
                        return array(
                            "status"  => "success",
                            "message" => "cupon ingresado a la bd",
                            "data"    => $datos_a_enviar
                        );
                    } else {
                        return array(
                            "status"  => "errorInsertarEnBdCuponesHistorial",
                            "message" => "ha ocurrido un problema al guardar el cupon en la base de datos",
                            "data"    => null
                        );
                    }
                } else {
                    return array(
                        "status"  => "cuponYaGuardado",
                        "message" => "este cupon ya ha sido asignado al usuario",
                        "data"    => null
                    );
                }
            } else {
                return $buscar_cupon;
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }

    // function filtrar_cupones_historial_old(array $data)
    // {
    //     if (isset($data) && isset($data["uid"]) && isset($data["empresa"]) && isset($data["pagina"])) {
    //         $condicion_buscar_por_usuario = " WHERE uid = '$data[uid]' AND empresa = '$data[empresa]'";
    //         $cupones_traidos = $this->seleccionarDeBdPorPaginas($this->tabla_cupones_historial, $condicion_buscar_por_usuario, intval($data["pagina"]), 3);
    //         $cupones = array();
    //         foreach ($cupones_traidos["data"] as $datos_cupones) {

    //             $datos_cupones_historial = $datos_cupones;
    //             $datos_cupon = $this->buscar(array(
    //                 "uid"     => $data["uid"],
    //                 "empresa" => $data["empresa"],
    //                 "codigo"  => $datos_cupones_historial["cupon_tipo_id"]
    //             ));

    //             $data_a_mostrar = $datos_cupon["data"];

    //             if (intval($data["inactivo"]) == 1 && intval($data_a_mostrar["estado"]) == 0) {
    //                 array_push($cupones, $data_a_mostrar);

    //             } else if (intval($data["disponible"]) == 1 && intval($data_a_mostrar["estado"]) == 1) {
    //                 array_push($cupones, $data_a_mostrar);

    //             } else if (intval($data["usado"]) == 1 && intval($data_a_mostrar["estado"]) == 2) {
    //                 array_push($cupones, $data_a_mostrar);

    //             } else if (intval($data["vencido"]) == 1 && intval($data_a_mostrar["esta_vencido"]) == 1) {
    //                 array_push($cupones, $data_a_mostrar);

    //             }
    //         }
    //         if (count($cupones) > 0) {
    //             return array(
    //                 "status"  => "success",
    //                 "message" => "listado completo de los cupones por filtro",
    //                 "pagina" => $cupones_traidos["pagina"],
    //                 "total_paginas" => $cupones_traidos["total_paginas"],
    //                 "data"    => $cupones
    //             );
    //         } else {
    //             return array(
    //                 "status"  => "sinDatosEnLaPagina",
    //                 "message" => "en la pagina $cupones_traidos[pagina] no hay datos",
    //                 "pagina" => $cupones_traidos["pagina"],
    //                 "total_paginas" => $cupones_traidos["total_paginas"],
    //                 "data"    => null
    //             );
    //         }
    //     } else {
    //         return array(
    //             "status"  => "dataIncompleta",
    //             "message" => "data incompleta",
    //             "data"    => null
    //         );
    //     }
    // }
    function filtrar_cupones_historial(array $data)
    {
        if ( !isset($data) && !isset($data["uid"]) && !isset($data["empresa"]) && !isset($data["estado"]) && !isset($data["pagina"]) ) {
            return array(
                "status"  => "fail",
                "message" => "datos incompletos",
                "data"    => null
            );
        }


        $fecha        = $this->getTimestamp();

        $estado = "";
        if( intval($data["estado"]) != 3 ){
            $estado = " ch.estado = '$data[estado]' AND c.fecha_vencimiento >= $fecha[timestamp_fechaActual] ";
        }else if( intval($data["estado"]) == 3 ){
            $estado = " c.fecha_vencimiento < $fecha[timestamp_fechaActual] ";
        }

        $selectxcupones =
            "SELECT
                c.*,
                0 AS 'acumulado',
                FROM_UNIXTIME(c.fecha_creacion/1000, '%d/%m/%Y %H:%i') AS 'fecha_creacion_format',
                FROM_UNIXTIME(c.fecha_vencimiento/1000, '%d/%m/%Y %H:%i') AS 'fecha_vencimiento_format'
            FROM buyinbig.cupones c
            INNER JOIN buyinbig.cupones_historial ch ON (c.codigo = ch.cupon_tipo_id)
            WHERE $estado AND ch.uid = $data[uid] AND ch.empresa = $data[empresa]";


        parent::conectar();
        $cuponesList = parent::consultaTodo($selectxcupones);
        parent::cerrar();
        if( COUNT($cuponesList) == 0){
            return array(
                "status"  => "errorSinCupones",
                "message" => "No se hallarón cupones para ti.",
                "data"    => null,
                "query"   => $selectxcupones
            );
        }

        $misCupones = $this->mapCupones( $cuponesList );
        return array(
            "status"  => "success",
            "message" => "Listado de cupones",
            "data"    => $misCupones,
            "query"   => $selectxcupones
        );
    }
    function filtrar_cupones_historial_bonos(array $data)
    {
        if ( !isset($data) && !isset($data["uid"]) && !isset($data["empresa"]) && !isset($data["estado"]) && !isset($data["pagina"]) ) {
            return array(
                "status"  => "fail",
                "message" => "datos incompletos",
                "data"    => null
            );
        }


        $fecha        = $this->getTimestamp();

        $estado = "";
        if( intval($data["estado"]) != 3 ){
            $estado = " ch.estado = '$data[estado]' AND c.fecha_vencimiento >= $fecha[timestamp_fechaActual] ";
        }else if( intval($data["estado"]) == 3 ){
            $estado = " c.fecha_vencimiento < $fecha[timestamp_fechaActual] ";
        }

        $selectxcupones =
            "SELECT 
                c.id,
                c.titulo,
                c.descripcion,
                c.titulo_en,
                c.descripcion_en,
                c.codigo,
                
                ch.uid,
                SUM(c.monto_descuento) AS 'acumulado',
                c.monto_descuento,
                
                c.monto_minimo,
                c.estado,
                c.fecha_creacion,
                c.fecha_vencimiento,
                c.tipo,
                ch.estado AS 'estado_cupon_historial',

                FROM_UNIXTIME(c.fecha_creacion / 1000, '%d/%m/%Y %H:%i') AS 'fecha_creacion_format',
                FROM_UNIXTIME(c.fecha_vencimiento / 1000, '%d/%m/%Y %H:%i') AS 'fecha_vencimiento_format'

            FROM buyinbig.cupones c
            INNER JOIN buyinbig.cupones_historial ch ON(c.codigo = ch.cupon_tipo_id)
            WHERE ch.uid = $data[uid] AND ch.empresa = $data[empresa] AND ch.estado = 1
            GROUP BY c.tipo
            ORDER BY c.tipo
            LIMIT 2;";


        parent::conectar();
        $cuponesList = parent::consultaTodo($selectxcupones);
        parent::cerrar();
        if( COUNT($cuponesList) == 0){
            return array(
                "status"  => "errorSinCupones",
                "message" => "No se hallarón cupones para ti.",
                "data"    => null,
                "query"   => $selectxcupones
            );
        }

        $misCupones = $this->mapCupones( $cuponesList );
        return array(
            "status"  => "success",
            "message" => "Listado de cupones",
            "data"    => $misCupones,
            "query"   => $selectxcupones
        );
    }

    function mapCupones( Array $cupones ){
        foreach ($cupones as $x => $cupon) {
            $cupon['id']          = intval($cupon['id']);
            $cupon['titulo']      = $cupon['titulo'];
            $cupon['descripcion'] = $cupon['descripcion'];


            $cupon['monto_descuento'] = floatval($cupon['monto_descuento']);
            $cupon['monto_minimo']    = floatval($cupon['monto_minimo']);

            $cupon['monto_minimo_mascara']    = parent::maskNumber($cupon['monto_minimo'], 2);
            $cupon['monto_descuento_mascara'] = parent::maskNumber($cupon['monto_descuento'], 2);

            if( isset( $cupon['acumulado'] )){
                $cupon['acumulado']      = floatval($cupon['acumulado']);
                $cupon['acumulado_mask'] = parent::maskNumber($cupon['acumulado'], 2);
            }
          
            $cupon['estado'] = intval($cupon['estado']);
            $cupon['tipo']   = intval($cupon['tipo']);

            $cupon['esta_vencido']   = 0;
            $cupon['dias_restantes'] = 0;
            
            $cupon['moneda_local'] = "COP";

            $cupones[$x] = $cupon;
        }
        return $cupones;
    }

    function verificiar_estados_cron(array $data)
    {
        if (isset($data["uid"]) && isset($data["empresa"])) {
            $todo_marcha_bien = true;
            $cupones_en_bd = $this->seleccionarDeBd($this->tabla_cupones, false, "");
            foreach ($cupones_en_bd as $cupones) {
                $data_actual_cupon = $this->buscar(array(
                    "uid" => $data["uid"],
                    "empresa" => $data["empresa"],
                    "codigo" => $cupones["codigo"]
                ));
                $estado_cupon_en_historial = "";
                if (intval($data_actual_cupon["data"]["esta_vencido"]) == 1) {
                    $estado_cupon_en_historial = "3";
                } else {
                    if (intval($data_actual_cupon["data"]["estado"]) == 1) {
                        $estado_cupon_en_historial = "1";
                    } else {
                        $estado_cupon_en_historial = "0";
                    }
                }
                $condicion_buscar_por_codigo = " WHERE cupon_tipo_id = '$cupones[codigo]'";
                $cupones_por_codigo_historial = $this->seleccionarDeBd($this->tabla_cupones_historial, true, $condicion_buscar_por_codigo);
                foreach ($cupones_por_codigo_historial as $cupones_historial) {
                    $data_cupon_historial_nueva = $cupones_historial;
                    $data_cupon_historial_nueva["estado"] = $estado_cupon_en_historial;
                    $condicion_a_actualizar = "cupon_tipo_id = '$cupones[codigo]'";
                    $todo_marcha_bien = $this->actualizarEnBd($this->tabla_cupones_historial, $data_cupon_historial_nueva, $condicion_a_actualizar);
                    if (!$todo_marcha_bien) {
                        break;
                    }
                }
                if (!$todo_marcha_bien) {
                    break;
                }
            }
            if ($todo_marcha_bien) {
                return array(
                    "status" => "success",
                    "message" => "todo ha marchado bien"
                );
            } else {
                return array(
                    "status" => "fail",
                    "message" => "ha fallado el actualizar estados"
                );
            }
        } else {
            return array(
                "status"  => "dataIncompleta",
                "message" => "data incompleta",
                "data"    => null
            );
        }
    }


    function validarRestriccionesDelCupones(array $data)
    {
        parent::addLog("----> SCHEMA CUPONNXXX. " . json_encode($data['data_cupon']));

        $codigo = $data['data_cupon']['codigo'];


        $cuponesxhistorial = "SELECT * FROM buyinbig.cupones_historial WHERE uid = $data[uid] AND empresa = $data[empresa] AND estado = 1 AND cupon_tipo_id = '$codigo';";
        parent::conectar();
        $cupones_historial = parent::consultaTodo($cuponesxhistorial);
        parent::cerrar();

        if( COUNT( $cupones_historial ) == 0){
            return array(
                "status"  => "fail",
                "message" => "el usuario no puede usar este cupon",
                "data"    => $data
            );
        }
        $cupones_historial = $cupones_historial[0];
        $data['data_cupon']['cupones_historial'] = $cupones_historial;

        $data['data_cupon']['cupones_historial']['estado'] = intval($data['data_cupon']['cupones_historial']['estado']);
        if( $data['data_cupon']['cupones_historial']['estado'] != 1 ){
          return array(
                "status"  => "fail",
                "message" => "el usuario no puede usar este cupon",
                "data"    => $data
            );
        }

        $data['data_cupon']['tipo'] = intval($data['data_cupon']['tipo']);

        if ($data['data_cupon']['tipo'] == 1) {
            // Cupon 1ra compra
            $result = $this->validarPrimeraCompra($data);
            if($result['status'] != 'success'){
                return $result;
            }else{
                return $this->validarTiendasCertificadas($data);
            }
        } else if ($data['data_cupon']['tipo'] == 2) {
            // Cupon para tiendas certificadas.
            return $this->validarTiendasCertificadas($data);
        } else {
            return array(
                "status"  => "fail",
                "message" => "el usuario no puede usar este cupon",
                "data"    => $data
            );
        }
    }
    function validarPrimeraCompra(array $data)
    {
        if( intval($data['uid']) == 1291  && intval($data['empresa']) == 0 || (intval($data['uid']) == 3014  && intval($data['empresa']) == 0) ){
            return array(
                "status"  => "success",
                "message" => 'Disfruta de tu cupon nasbi.',
                "data"    => $data['data_cupon']
            );
        }
        $productos_transaccion = Array();
        $selectxproductosxtransacciones =
            "SELECT * FROM buyinbig.productos_transaccion WHERE uid_comprador = $data[uid] AND empresa_comprador = $data[empresa];";

        parent::conectar();
        $productos_transaccion = parent::consultaTodo($selectxproductosxtransacciones);
        parent::cerrar();

        if (count($productos_transaccion) > 0) {
            return array(
                "status"        => "ErrorNoEsPrimeraCompra",
                "message"       => "Esta no es tu 1ra compra. Actualmente llevas " . count($productos_transaccion) . ' compras.',
                "data"          => null,
                'total_compras' => $productos_transaccion
            );
        } else {
            return array(
                "status"  => "success",
                "message" => 'Si es su primera compra, ya puedes usarlo.',
                "data"    => $data['data_cupon']
            );
        }
    }
    function validarTiendasCertificadas(array $data)
    {

        if( intval($data['uid']) == 1291  && intval($data['empresa']) == 0 || (intval($data['uid']) == 3014  && intval($data['empresa']) == 0) ){
            return array(
                "status"  => "success",
                "message" => 'Disfruta de tu cupon nasbi.',
                "data"    => $data['data_cupon']
            );
        }

        // VALIDAR SI ES UNA TIENDA OFICIAL
        $selectxcountxtiendasxoficiales =
            "SELECT
                COUNT(
                    e.usuarios_estados_teindas_oficiales_id = 1 OR u.usuarios_estados_teindas_oficiales_id = 1
                ) AS 'tiendas_oficiales',

                COUNT(
                    e.usuarios_estados_teindas_oficiales_id != 1 OR u.usuarios_estados_teindas_oficiales_id != 1
                ) AS 'tiendas_no_oficiales'
            FROM buyinbig.carrito c
            LEFT JOIN buyinbig.productos p ON(c.id_producto = p.id)
            LEFT JOIN buyinbig.empresas e ON(p.uid = e.id)
            LEFT JOIN peer2win.usuarios u ON(p.uid = u.id)
            WHERE (c.uid = $data[uid] AND c.empresa = $data[empresa]) AND c.estado = 1;";

        parent::conectar();
        $count_tiendas_oficiales = parent::consultaTodo($selectxcountxtiendasxoficiales);
        parent::cerrar();

        if( COUNT($count_tiendas_oficiales) == 0 ){
            return array(
                "status"     => "noUsoTiendaOficial",
                "message"    => 'No estas comprando el cupon en los tiendas oficiales.',
                "data"       => $data['data_cupon'],
                'dataExtra1' => $count_tiendas_oficiales
            );
        }

        $count_tiendas_oficiales = $count_tiendas_oficiales[0];
        $count_tiendas_oficiales['tiendas_oficiales']    = intval( $count_tiendas_oficiales['tiendas_oficiales'] );
        $count_tiendas_oficiales['tiendas_no_oficiales'] = intval( $count_tiendas_oficiales['tiendas_no_oficiales'] );
        
        if( $count_tiendas_oficiales['tiendas_oficiales'] == 0 ){
            return array(
                "status"     => "cuponNoDisponible",
                "message"    => 'No puedes usar este cupón en tiendas no oficiales - v2',
                "data"       => $data['data_cupon'],
                'dataExtra1' => $count_tiendas_oficiales
            );
        }

        // Buscar si la persona ya uso el CUPÓN.
        $cuponxdisponible = 
            "SELECT
                *
            FROM buyinbig.cupones_historial
            WHERE uid = $data[uid] AND empresa = $data[empresa] AND estado = 1;";
        
        parent::conectar();
        $cupon_disponible = parent::consultaTodo($cuponxdisponible);
        parent::cerrar();

        if( COUNT($cupon_disponible) == 0 ){
            return array(
                "status"     => "cuponNoDisponible",
                "message"    => 'Cupon vencido, usado - v3',
                "data"       => $data['data_cupon'],
                'dataExtra1' => $count_tiendas_oficiales,
                'dataExtra2' => $cupon_disponible,
            );
        }

        return array(
            "status"  => "success",
            "message" => 'Disfruta de tu cupon nasbi.',
            "data"    => $data['data_cupon']
        );
        
    }

    function getTimestamp(){
        $timezone    = new DateTimeZone('America/Bogota');
        
        $fechaActual      = new DateTime();
        $fechaVencimiento = new DateTime();

        $fechaActual->format("U");
        $fechaActual->setTimezone($timezone);

        date_add($fechaVencimiento, date_interval_create_from_date_string("+3 month"));
        
        $resuls = array(
            'fechaActual'               => $fechaActual->format('Y-m-d'),
            'fechaVencimiento'          => $fechaVencimiento->format('Y-m-d'),
            'timestamp_fechaActual'     => $fechaActual->getTimestamp()*1000,
            'timestamp_fechaVencimiento'=> $fechaVencimiento->getTimestamp()*1000
        );
        return $resuls;
    }
}
?>