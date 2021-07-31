<?php
require 'conexion.php';
class Tree extends Conexion
{
    public function mytree($user){
        parent::conectar();
        $request = null;
        $datareturn = null;
        $consultarthree = '';
        $consultarthree = "select n.*, u.username, if(u.status = 1, 1, 0) status, r.imagen rimg, avatar, u.plan, p.imagen foto, u.foto imagen, r.nombre rango from nodos n inner join usuarios u on n.usuario = u.id inner join rangos r on r.id = u.rango inner join paquetes p on p.id = u.plan where n.usuario ='$user'";
        $lista = parent::consultaTodo($consultarthree);
        if (count($lista) > 0) {
            $nivel = 0;
            $datareturn = $lista[0];
            $datareturn['nivel'] = $nivel;
            $datareturn['parent'] = $user;
            $datareturn['data_izquierda'] = $this->submytree($lista[0], 'izquierda', $nivel, $user);
            $datareturn['data_derecha'] = $this->submytree($lista[0], 'derecha', $nivel, $user);
            $request = array(
                'status' => 'success',
                'message'=> 'referidos',
                'data' => $datareturn
            );
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no referidos',
                'data' => null
            );
        }
        parent::cerrar();

        return $request;
    }

    public function submytree($array, $lado, $nivel, $padre = 0){
        $userActivo = null;
        $userActivo = $array[$lado];
        if($userActivo == null){
            return null;
        }else{
            $datareturn = null;
            $consultarthree = '';
            $consultarthree = "select n.*, u.username, if(u.status = 1, 1, 0) status, avatar, r.imagen rimg, u.plan, p.imagen foto, u.foto imagen, ifnull(r.nombre, 'Rango 1') rango from nodos n inner join usuarios u on n.usuario = u.id left join rangos r on r.id = u.rango inner join paquetes p on p.id = u.plan where n.usuario = '$userActivo'";
            if ($padre != 0) {
                $consultarthree = "select n.id, if(u.status = 1, 1, 0) status, n.usuario, avatar, r.imagen rimg, n.derecha, n.izquierda, ifnull(c.monto, 0) puntos, u.username, u.plan, p.imagen foto, u.foto imagen, ifnull(r.nombre, 'Rango 1') rango from nodos n inner join usuarios u on n.usuario = u.id left join rangos r on r.id = u.rango inner join paquetes p on p.id = u.plan left join (select usuario, sum(puntosPendientes) monto from pagoBinario where asociado = $padre group by usuario) c on c.usuario = n.usuario where n.usuario = '$userActivo'";
            }
            $lista = parent::consultaTodo($consultarthree);
            if (count($lista) > 0) {
                $nivel = $nivel+1;
                $datareturn = $lista[0];
                $datareturn['nivel'] = $nivel;
                $datareturn['parent'] = $array['usuario'];
                $datareturn['data_izquierda'] = $this->submytree($lista[0], 'izquierda', $nivel, $padre);
                $datareturn['data_derecha'] = $this->submytree($lista[0], 'derecha', $nivel, $padre);
                return $datareturn;
            }
        }
    }
}
?>