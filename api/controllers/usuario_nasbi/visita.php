<?php
    if ((!isset($_GET["estadisticas"]))) {
        include '../cors.php';    
    }
    
    require '../../models/conexion.php';
    $user = 0;
    $response = Array("status"=>"success");
    $arr = Array(3067,3068,3069,3070,3071,3072);

    if (isset($_POST['data']) && isset($_POST['data']["user"])) {
        $user = $_POST['data']["user"];    
        $search = -1;

        $search = array_search($user, $arr);

        // print_r($search);

        if ($arr[$search] == $user) {
            // print_r("lo encontro");
            $conectar = new Conexion();
            $conectar->conectar();
            //print_r($_SERVER["HTTP_X_FORWARDED_FOR"]);
            $consulta = "select *, CURRENT_TIMESTAMP, addtime(CURRENT_TIMESTAMP, -50) from buyinbig.contador_nasbi_visitas cnv where ip = '$_SERVER[HTTP_X_FORWARDED_FOR]' and cnv.fecha > addtime(CURRENT_TIMESTAMP, -450) and usuario = $user";
            $res1 = $conectar->consultaTodo($consulta);
            // print_r($consulta);
            // print_r($res1);
            if (count($res1) == 0) {
                $conectar->queryRegistro("INSERT INTO buyinbig.contador_nasbi_visitas (tipo, ip, fecha, usuario) VALUES (0, '$_SERVER[HTTP_X_FORWARDED_FOR]', current_timestamp, $user);"); 
            }
            $conectar->cerrar();
            
        }
        echo json_encode($response);
    } else if (isset($_GET["estadisticas"])) {
        $conectar = new Conexion();
        $conectar->conectar();
        $req = $conectar->consultaTodo("select cnv.usuario, u.username, (sum(if(cnv.tipo=0,1,0))) visitas, (sum(if(cnv.tipo!=0,1,0))) registros, 
if(dpn.status_revision = 0, 'Sin revisar', if(dpn.status_revision = 1, 'Aprobado', if(dpn.status_revision=2,'Rechazado', 'No se han subido los datos'))) estado_comercio, sum(if(p.id, 1, 0)) productos_publicados, sum(if(pr.id, 1, 0)) productos_en_revision
            from buyinbig.contador_nasbi_visitas cnv 
            inner join peer2win.usuarios u on u.id = cnv.usuario
            left join buyinbig.productos p on p.uid = cnv.usuario and p.empresa = 0
            left join buyinbig.productos_revision pr on pr.uid = cnv.usuario and pr.empresa = 0
            left join buyinbig.datos_persona_natural dpn on dpn.user_uid = cnv.usuario
            group by cnv.usuario");
        $response["data"] = $req;
        $conectar->cerrar();
        $css = "<style type='text/css'>@font-face{font-family:monospace;src:url(../fonts/Lato/monospace.ttf)}@font-face{font-family:monospace;src:url(../fonts/Lato/monospace.ttf)}*{margin:0;padding:0;box-sizing:border-box}body,html{height:100%;font-family:sans-serif}a{margin:0;transition:all .4s;-webkit-transition:all .4s;-o-transition:all .4s;-moz-transition:all .4s}a:focus{outline:none!important}a:hover{text-decoration:none}h1,h2,h3,h4,h5,h6{margin:0}p{margin:0}ul,li{margin:0;list-style-type:none}input{display:block;outline:none;border:none!important}textarea{display:block;outline:none}textarea:focus,input:focus{border-color:transparent!important}button{outline:none!important;border:none;background:0 0}button:hover{cursor:pointer}iframe{border:none!important}.js-pscroll{position:relative;overflow:hidden}.table100 .ps__rail-y{width:9px;background-color:transparent;opacity:1!important;right:5px}.table100 .ps__rail-y::before{content:'';display:block;position:absolute;background-color:#ebebeb;border-radius:5px;width:100%;height:calc(100% - 30px);left:0;top:15px}.table100 .ps__rail-y .ps__thumb-y{width:100%;right:0;background-color:transparent;opacity:1!important}.table100 .ps__rail-y .ps__thumb-y::before{content:'';display:block;position:absolute;background-color:#ccc;border-radius:5px;width:100%;height:calc(100% - 30px);left:0;top:15px}.limiter{width:1366px;margin:0 auto}.container-table100{width:100%;min-height:100vh;background:#fff;display:-webkit-box;display:-webkit-flex;display:-moz-box;display:-ms-flexbox;display:flex;justify-content:center;flex-wrap:wrap;padding:33px 30px}.wrap-table100{width:1170px}.table100{background-color:#fff}table{width:100%}th,td{font-weight:unset;padding-right:10px}.column1{width:10%;padding-left:40px}.column2{width:20%}.column3{width:15%}.column4{width:15%}.column5{width:15%}.table100-head th{padding-top:18px;padding-bottom:18px}.table100-body td{padding-top:16px;padding-bottom:16px}.table100{position:relative;padding-top:60px}.table100-head{position:absolute;width:100%;top:0;left:0}.table100-body{max-height:585px;overflow:auto}.table100.ver1 th{font-family:monospace;font-size:18px;color:#fff;line-height:1.4;background-color:#6c7ae0}.table100.ver1 td{font-family:monospace;font-size:15px;color:black;line-height:1.4;text-align:center;}.table100.ver1 .table100-body tr:nth-child(even){background-color:#f8f6ff}.table100.ver1{border-radius:10px;overflow:hidden;box-shadow:0 0 40px 0 rgba(0,0,0,.15);-moz-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-webkit-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-o-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-ms-box-shadow:0 0 40px 0 rgba(0,0,0,.15)}.table100.ver1 .ps__rail-y{right:5px}.table100.ver1 .ps__rail-y::before{background-color:#ebebeb}.table100.ver1 .ps__rail-y .ps__thumb-y::before{background-color:#ccc}.table100.ver2 .table100-head{box-shadow:0 5px 20px 0 rgba(0,0,0,.1);-moz-box-shadow:0 5px 20px 0 rgba(0,0,0,.1);-webkit-box-shadow:0 5px 20px 0 rgba(0,0,0,.1);-o-box-shadow:0 5px 20px 0 rgba(0,0,0,.1);-ms-box-shadow:0 5px 20px 0 rgba(0,0,0,.1)}.table100.ver2 th{font-family:monospace;font-size:18px;color:#fa4251;line-height:1.4;background-color:transparent}.table100.ver2 td{font-family:monospace;font-size:15px;color:gray;line-height:1.4}.table100.ver2 .table100-body tr{border-bottom:1px solid #f2f2f2}.table100.ver2{border-radius:10px;overflow:hidden;box-shadow:0 0 40px 0 rgba(0,0,0,.15);-moz-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-webkit-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-o-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-ms-box-shadow:0 0 40px 0 rgba(0,0,0,.15)}.table100.ver2 .ps__rail-y{right:5px}.table100.ver2 .ps__rail-y::before{background-color:#ebebeb}.table100.ver2 .ps__rail-y .ps__thumb-y::before{background-color:#ccc}.table100.ver3{background-color:#393939}.table100.ver3 th{font-family:monospace;font-size:15px;color:#00ad5f;line-height:1.4;text-transform:uppercase;background-color:#393939}.table100.ver3 td{font-family:monospace;font-size:15px;color:gray;line-height:1.4;background-color:#222}.table100.ver3{border-radius:10px;overflow:hidden;box-shadow:0 0 40px 0 rgba(0,0,0,.15);-moz-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-webkit-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-o-box-shadow:0 0 40px 0 rgba(0,0,0,.15);-ms-box-shadow:0 0 40px 0 rgba(0,0,0,.15)}.table100.ver3 .ps__rail-y{right:5px}.table100.ver3 .ps__rail-y::before{background-color:#4e4e4e}.table100.ver3 .ps__rail-y .ps__thumb-y::before{background-color:#00ad5f}.table100.ver4{margin-right:-20px}.table100.ver4 .table100-head{padding-right:20px}.table100.ver4 th{font-family:monospace;font-size:18px;color:#4272d7;line-height:1.4;background-color:transparent;border-bottom:2px solid #f2f2f2}.table100.ver4 .column1{padding-left:7px}.table100.ver4 td{font-family:monospace;font-size:15px;color:gray;line-height:1.4}.table100.ver4 .table100-body tr{border-bottom:1px solid #f2f2f2}.table100.ver4{overflow:hidden}.table100.ver4 .table100-body{padding-right:20px}.table100.ver4 .ps__rail-y{right:0}.table100.ver4 .ps__rail-y::before{background-color:#ebebeb}.table100.ver4 .ps__rail-y .ps__thumb-y::before{background-color:#ccc}.table100.ver5{margin-right:-30px}.table100.ver5 .table100-head{padding-right:30px}.table100.ver5 th{font-family:monospace;font-size:14px;color:#555;line-height:1.4;text-transform:uppercase;background-color:transparent}.table100.ver5 td{font-family:monospace;font-size:15px;color:gray;line-height:1.4;background-color:#f7f7f7}.table100.ver5 .table100-body tr{overflow:hidden;border-bottom:10px solid #fff;border-radius:10px}.table100.ver5 .table100-body table{border-collapse:separate;border-spacing:0 10px}.table100.ver5 .table100-body td{border:solid 1px transparent;border-style:solid none;padding-top:10px;padding-bottom:10px}.table100.ver5 .table100-body td:first-child{border-left-style:solid;border-top-left-radius:10px;border-bottom-left-radius:10px}.table100.ver5 .table100-body td:last-child{border-right-style:solid;border-bottom-right-radius:10px;border-top-right-radius:10px}.table100.ver5 tr:hover td{background-color:#ebebeb;cursor:pointer}.table100.ver5 .table100-head th{padding-top:25px;padding-bottom:25px}.table100.ver5{overflow:hidden}.table100.ver5 .table100-body{padding-right:30px}.table100.ver5 .ps__rail-y{right:0}.table100.ver5 .ps__rail-y::before{background-color:#ebebeb}.table100.ver5 .ps__rail-y .ps__thumb-y::before{background-color:#ccc}
        </style>";

        $headTable = '<table>
                        <thead>
                        <tr class="row100 head">
                        <th class="cell100 column1">id</th>
                        <th class="cell100 column2">Usuario</th>
                        <th class="cell100 column3">Visitas</th>
                        <th class="cell100 column4">Registros</th>
                        <th class="cell100 column4">Estado comercio</th>
                        <th class="cell100 column4">Publicados</th>
                        <th class="cell100 column4">Revision</th>
                        </tr>
                        </thead>
                        </table>
                        </div>
                        <div class="table100-body js-pscroll">';
        echo("<html><head>$css</head><body><div class='container-table100'><div class='wrap-table100'><div class='table100 ver1 m-b-110'><div class='table100-head'>$headTable<table><tbody>");
        foreach ($req as $key => $value) {
            echo "
                    <tr class='row100 body'>
                    <td class='cell100 column1'>$value[usuario]</td>
                    <td class='cell100 column2'>$value[username]</td>
                    <td class='cell100 column3'>$value[visitas]</td>
                    <td class='cell100 column4'>$value[registros]</td>
                    <td class='cell100 column4'>$value[estado_comercio]</td>
                    <td class='cell100 column4'>$value[productos_publicados]</td>
                    <td class='cell100 column4'>$value[productos_en_revision]</td>
                    </tr>";
        }
        echo("<tbody></table></div></div></body></html>");

    }

    
    
?>