<?php
require 'conexion.php';
class Prueba extends Conexion
{

    
  public function traerUsuariosPorNivel($nivel,$usuario){
        $lider =$this->lider($usuario);
        $request = null;
        $nivelBuscar = $nivel;
        $usuarioBuscar = $usuario;
        $datosArbol = array();
        

        array_push($datosArbol,$this->consultar($usuarioBuscar));
       // print_r($datosArbol);

        if(count($datosArbol[0]) > 0 ){ 
         
            for( $i = 0; $i < count($datosArbol[0]); $i++){
                $datosArbol[0][$i]["nivel"] = 1;
                $datosArbol[0][$i]["padre"] = $usuarioBuscar; 
            }

            //print_r($datosArbol);
            $data = $this->completaArbol(1,$datosArbol,4,0);
           // print_r($data);
            $request = array(
                'status' => 'success',
                'message'=> 'referidos',
                'data' =>  $data,
                'datauser' => $lider["data"][0]
           
               
            );
          
          //  print_r($datosArbol);
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no referidos',
                'data' => null,
                'datauser' => $lider["data"][0]
            );
           
        }
        return $request;



    }
    
    public function completaArbol($nivel,$arbol,$nivelMax,$posicion){
        $usuariosNivel= null;
        $usuariosNivel= array();
        if($nivel < $nivelMax){
            if (isset($arbol[$posicion])) {
          // print_r($arbol[$posicion]);

                for( $j=0; $j< count($arbol[$posicion]); $j++){ 
                $usuariosNivel= $this->consultar($arbol[$posicion][$j]["id"]);
                //   print_r(count($usuariosNivel));
                  if(count($usuariosNivel) > 0){ 
                       
    
                       for($k =0;$k<count($usuariosNivel);$k++){
                           $usuariosNivel[$k]["nivel"]=$nivel+1;
                           $usuariosNivel[$k]["padre"]=$arbol[$posicion][$j]["id"];   
                           $arbol[$posicion+1][]= $usuariosNivel[$k];   
                       }
                    
                     
                  }
            }
            }
            
           // print_r($arbol);
            
            return $this->completaArbol($nivel+1,$arbol,$nivelMax,$posicion+1);
            
        }
        else{
             $newArbol = array();
            for($i = 0;$i<count($arbol);$i++){
                for( $j = 0;$j<count($arbol[$i]);$j++){
                    array_push($newArbol,$arbol[$i][$j]);
      
                }
            }
 
            //print_r($newArbol);
            return $newArbol;
         }
    }




    public function consultar($user){ 
        //print_r($user);
        parent::conectar();
        $datareturn = null;
        $consultarthree = '';
        $consultarthree = "SELECT *,r.imagen rimg from rangos r join (SELECT u.username, u.plan, u.id, u.status, u.referido, u.rango, u.nombreCompleto, u.avatar, p.imagen 'foto', u.foto 'imagen' from usuarios u inner join paquetes p on p.id = u.plan where referido = '$user' ) n where n.rango = r.id";
       
        $lista = parent::consultaTodo($consultarthree);
        $datareturn = $lista;
        parent::cerrar();


     // print_r($datareturn);
        return $datareturn;

    }



    public function lider($user){
        parent::conectar();
        $request = null;
        $datareturn = null;
        $consultarthree = '';
        $consultarthree = "select u.*, p.imagen foto, u.foto 'imagen',u.avatar, ifnull(r.nombre, 'Sin rango') nombreRango, r.imagen rimg from usuarios u inner join paquetes p on p.id = u.plan inner join rangos r on r.id = u.rango where u.id = $user";
       
        $lista = parent::consultaTodo($consultarthree);
        if (count($lista) > 0) {
            $datareturn = $lista;
            $request = array(
                'status' => 'success',
                'message'=> 'referidos',
                'data' => $datareturn
            );
        
        }else{
            $request = array(
                'status' => 'fail',
                'message'=> 'no data',
                'data' => null
            );
        }
        parent::cerrar();

       
        return $request;

    }


    
    
    


  

    
}
?>