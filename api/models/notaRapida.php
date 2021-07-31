<?php
    require 'conexion.php';

    class NotaRapida extends Conexion {
        public function listarNotaRapida($nota) {
            parent::conectar();

            $consulta = "SELECT * FROM nota_rapida WHERE usuario = $nota[user] order by fecha desc;";

            $lista = parent::consultaTodo($consulta);

            parent::cerrar();

            $respuesta = array(
                'status' => 'success', 
                'data' => $lista
            );

            return $respuesta;
        }

        public function saveNotaRapida($nota) {
            parent::conectar();

            $consulta = "INSERT INTO nota_rapida (titulo, descripcion, fecha, usuario) VALUE ('$nota[titulo]', '$nota[descripcion]', current_time, $nota[user])";

            $sopoId = parent::queryRegistro($consulta);

            parent::cerrar();

            if ($sopoId > 0) {
                $respuesta = array(
                    'status' => 'success',
                    'mensaje' => 'La nota se guardo',
                    'data' => $sopoId
                );
            } else {
                $respuesta = array(
                    'status' => 'error',
                    'mensaje' => 'No se puede guardar la nota',
                );
            }

            return $respuesta;
        }
    
        public function updateNotaRapida($nota) {
            parent::conectar();

            $consulta = "UPDATE nota_rapida SET titulo = '$nota[titulo]', descripcion = '$nota[descripcion]', fecha = current_time WHERE id = $nota[id]";

            $sopoId = parent::query($consulta);

            parent::cerrar();

            if ($sopoId > 0) {
                $respuesta = array(
                    'status' => 'success',
                    'mensaje' => 'La nota se guardo',
                    'data' => $sopoId
                );
            } else {
                $respuesta = array(
                    'status' => 'error',
                    'mensaje' => 'No se puede guardar la nota',
                );
            }

            return $respuesta;
        }

        public function deleteNotaRapida($nota) {
            parent::conectar();

            $consulta = "DELETE FROM nota_rapida WHERE id = $nota[id]";

            $sopoId = parent::query($consulta);

            parent::cerrar();

            if ($sopoId > 0) {
                $respuesta = array(
                    'status' => 'success',
                    'mensaje' => 'La nota se elimino',
                    'data' => $sopoId
                );
            } else {
                $respuesta = array(
                    'status' => 'error',
                    'mensaje' => 'No se puede eliminar la nota',
                );
            }

            return $respuesta;
        }
    }