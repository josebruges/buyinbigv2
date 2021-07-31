<?php
    include '../cors.php';
    require '../../models/conexion.php';

    $conexion = new conexion();
  

  $url = "https://www.pagodigital.co/WS_NASBI/API_TEST/PSE/TRANSACTION_BEGIN/";
  $prueba = '{
    "ID": 7,
    "STATUS": 0,
    "REF_LOCAL": "",
    "TOTAL_COMPRA": 510000,
    "PRECIO_USD": 0,
    "TIPO": 1,
    "UID": 1378,
    "EMPRESA": 0,
    "NOMBRE_COMPRADOR": "paula andrea manigua bermudez",
    "CEDULA_COMPRADOR": "1082958808",
    "TELEFONO_COMPRADOR": 2147483647,
    "CORREO_COMPRADOR": "paulamanigua2005@gmail.com",
    "DIRECCION_COMPRADOR": "Carrera 18 #26 A 33",
    "CIUDAD": "Santa Marta",
    "COD_DANE": "47001000",
    "DEPARTAMENTO": "Magdalena",
    "CODIGO_BANCO": 1007,
    "NOMBRE_BANCO": "BANCOLOMBIA",
    "TIPO_PERSONA": "C.C",
    "TIPO_DOCUMENTO": "C.C",
    "ISO_CODE_2": "CO",
    "METODO_PAGO_USADO_ID": "2",
    "TRANSACCION_FINALIZADA": "0",
    "JSON_SOLICITUD": {
        "data": {
            "carrito": {
                "id": "23",
                "uid": "1378",
                "empresa": "0",
                "precio_moneda_actual_usd": "3784.53"
            },
            "metodo_pago": {
                "id": "2",
                "address_comprador": "3d3034eec0d5bc045d2059990b6a7b45",
                "address_comprador_sd": "3d3034eec0d5bc045d2059990b6a7b45",
                "address_comprador_bd": "2721dbeb5adf2b9657309c5bdb6b7b31",
                "desc": "1",
                "bd": "90000",
                "bd_porcentaje": "0.15",
                "bd_usd": "23.781",
                "sd": "526350",
                "sd_usd": "139.079215"
            },
            "direccion": {
                "id": "497"
            },
            "coloresXtallas": [
                {
                    "id_producto": "530",
                    "producto_titulo": "Tenis adidas performance",
                    "variaciones": [
                        {
                            "color": "#191515",
                            "tallaES": "40",
                            "tallaEN": "40",
                            "id_pair": "304",
                            "cantidad_disponible": "9930",
                            "cantidad_ingresada_carrito": "1"
                        }
                    ]
                }
            ],
            "descripcion_extra": "  "
        }
    },
    "FECHA_CREACION": 1619729611245,
    "FECHA_ACTUALIZACION": 1619733479593,
    "JSON_PROVEEDORES": "[{\"ID\":11,\"PAGO_DIGITAL_ID\":7,\"ID_PROVEEDOR\":1145,\"UID\":1378,\"EMPRESA\":0,\"VALOR\":510000,\"VALOR_REAL_COMPRA\":\"600000\",\"COSTO_FLETE\":16350,\"PRODUCTO\":\"Compra de Tenis adidas performance valor real\",\"PRODUCTO_UID\":23,\"PORCENTAJE_COMISION\":17.01,\"ISO_CODE_2\":\"CO\",\"FECHA_CREACION\":\"1619729611245\",\"FECHA_ACTUALIZACION\":\"1619729611245\"}]",
    "JWT": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2MTk3MzM0MDEsImV4cCI6MTYxOTczNzAwMSwiSldUX1RPS0VOIjoiV1pESkFSVE43TkJYOTQxWDM4VU9OS1JONVVIMkFUQVFDNDRYREhKWiJ9.gldaFHpiahR36BP0MQNT2GqLtW_vzvrOBoW2HZx0vSg",
    "API_KEY": "1SLCFBVOBI8MDSRVXDS1",
    "API_SECRET": "N4DTFDN3FEUQYFJWV98X",
    "CODIGO_ENTIDAD": 9004617586,
    "CODIGO_SERVICIO": 9004617586,
    "URL_RESPUESTA": "http://localhost/MisProyectos/ProyectoNasbi2020/content/callback-pse.php"
}';

$data = json_decode($prueba, true);

/*$data["JSON_SOLICITUD"] = json_encode($data["JSON_SOLICITUD"]);*/
$data["JSON_PROVEEDORES"] = json_encode($data["JSON_PROVEEDORES"]);
// $respuesta = $conexion->remoteRequestPagoDigitalParams($url, $data);



$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://www.pagodigital.co/WS_NASBI/API_TEST/PSE/TRANSACTION_BEGIN/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => array(
        'API_KEY' => '1SLCFBVOBI8MDSRVXDS1',
        'API_SECRET' => 'N4DTFDN3FEUQYFJWV98X',
        'NOMBRE_COMPRADOR' => 'Usuario Prueba',
        'CEDULA_COMPRADOR' => '123456789',
        'TELEFONO_COMPRADOR' => '3211234567',
        'CORREO_COMPRADOR' => 'Test@gmail.com',
        'DIRECCION_COMPRADOR' => 'Calle 85 # 22 - 73',
        'TOTAL_COMPRA' => '158000',
        'CIUDAD' => 'BOGOTA D.C',
        'DEPARTAMENTO' => 'CUNDINAMARCA',
        'CODIGO_BANCO' => '1051',
        'NOMBRE_BANCO' => 'BANCO DAVIVIENDA',
        'TIPO_PERSONA' => 'N',
        'TIPO_DOCUMENTO' => 'CC',
        'CODIGO_ENTIDAD' => '9004617586',
        'CODIGO_SERVICIO' => '9004617586',
        'URL_RESPUESTA' => 'https://www.pagodigital.co/WS_NASBI/WSPSE/PRUEBAGET.php',
        'JSON_PROVEEDORES' => '[{"ID_PROVEEDOR":1145,"VALOR":60000,"COSTO_FLETE":10000,"PRODUCTO":"Celular X Referencia Y","PORCENTAJE_COMISION":12},{"ID_PROVEEDOR":991,"VALOR":98000,"COSTO_FLETE":0,"PRODUCTO":"Celular X Referencia Y","PORCENTAJE_COMISION":0}]',
        'JWT' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2MTk3MzQ3MDAsImV4cCI6MTYxOTczODMwMCwiSldUX1RPS0VOIjoiV05OMVJVNzNaUjZWQUdaNUVGMzhSRlFUWFgzSEhOTjYzTTZKVTIzTSJ9.lGNWi4TCR1R-0a3bxPWuyrZqIDecZu053DmmWP8TUNI'
    ),
));

$response = curl_exec($curl);
curl_close($curl);
var_dump($response);

return $response;
    
?>