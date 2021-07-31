<?php
    require '../../models/token.php';

    function ensureAuth($id = "success", $empresa = 0) {
        /* echo isset($_SERVER['HTTP_X_API_KEY']); */
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $resul = validarToken($_SERVER['HTTP_X_API_KEY'], $id, $empresa);
            if ($resul == 'error') {
                $res = array('status' => 'errorToken', 'message' => 'El token no es valido');
                echo json_encode($res);
                return false;
            } else if ($resul == 'errorVencido') {
                $res = array("status"=>"errorVencido", "message"=>"Se ha vencido tu token debes volver a ingresar para actualizarlo");
                echo json_encode($res);
                return false;
            } else {
                return $resul;
            }
        } else {
            if ($_SERVER["HTTP_HOST"] == "localhost") {
                return $id;
            } else {
                $res = array('status' => 'errorEncabezado', 'message' => 'Falta el encabezado x-api-key');
                echo json_encode($res);
                return false;    
            }
            
        }
    }