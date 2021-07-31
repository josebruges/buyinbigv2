<?php
    require 'conexion.php';

    class Block extends Conexion {
        public function listarBlock($historial) {
            parent::conectar();
            $respuesta;
            $consultar1 = " SELECT b.*, t.nombre as nombre_tipo FROM alumnos a
                            INNER JOIN block b on a.id_block = b.id
                            INNER JOIN tipo_block t on b.tipo = t.id
                            WHERE a.usuario = ".$historial['usuario']." AND b.tipo = ".$historial['tipo'];
            $lista      = parent::consultaTodo($consultar1);
            for ($i=0; $i < count($lista); $i++) { 
                $consultar2 = "SELECT id As id_recurso, recurso FROM recursos WHERE id_block =".$lista[$i]['id'];
                $lista2      = parent::consultaTodo($consultar2);
                $lista[$i]['recurso'] = $lista2;
            }
            parent::cerrar();
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'data' => $lista);
            return $respuesta;
        }

        public function listarCursoGrupo($group) {
            $respues = $this->wbsDetalleGroup($group['id']);
            if ($respues->id) {
                unset($respues->users);
                for ($i=0; $i < count($respues->courses) ; $i++) { 
                    $resCourse = $this-> wbsDetalleCourse($respues->courses[$i]->id);
                    $respues->courses[$i]->data = $resCourse;
                }
                $respuesta = array('status' => 'success', 'message' => 'El detalle del curso', 'data' => $respues);
            } else {
                $respuesta = array('status' => 'errorGroup', 'message' => 'El grupo no existe');
            }
            return $respuesta;
        }

        public function wbsDetalleGroup($group) {
            $api = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://peers2win.talentlms.com/api/v1/groups/id:".$group,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$apiKey,
                ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        public function wbsDetalleCourse($course) {
            $api = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://peers2win.talentlms.com/api/v1/courses/id:".$course,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$apiKey,
                ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        public function loginTalen($user) {
            parent::conectar();
            $respuesta;
            $dataUser = parent::consultaTodo("select * from usuarios where id = ".$user['id']);
            if (count($dataUser)) {
                $data = array(
                    'username' => $dataUser[0]['username'],
                    'password' => $dataUser[0]['password']
                );
                $resp = parent::metodoPost('user/login.php', $data);
                $respuesta = array('status' => 'success', 'message' => 'Link', 'link' => $resp);
            } else {
                $respuesta = array('status' => 'errorUser', 'message' => 'EL usuario no existe');
            }
            
            parent::cerrar();
            return $respuesta;
        }

        public function wbsLogin($user) {
            $api = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://peers2win.talentlms.com/api/v1/userlogin",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array(
                    'login' => $user['username'],
                    'password' => $user['password'],
                    'logout_redirect' => 'peers2win.talentlms.com'
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$apiKey,
                ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        public function listaCursos($block) {
            $data = array(
                'id' => $block['id'],
            );
            $alumno = parent::metodoPost('user/getAlumno.php', $data);
            $curso = parent::metodoPost('curso/getAllCursoUser.php', $data);
            $grupo = parent::metodoPost('group/getAllGroupS.php', $data);
            $user = parent::metodoPost('user/duracionPlataforma.php', $data);
            $cursados = parent::metodoPost('user/getCursados.php', $data);
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'curso' => $curso, 'alummno' => $alumno, 'grupos' => $grupo, 'user' => $user, 'cursados' => $cursados);
            return $respuesta;
        }

        public function listaCursosAll($curso) {
            $grupo = parent::metodoPost('group/getAllGroupI.php', $curso);
            $respuesta = array('status' => 'success', 'message' => 'Lista', 'grupos' => $grupo);
            return $respuesta;
        }

        public function getCurso($curso) {
            $grupo = parent::metodoPost('group/getGroup.php', $curso);
            $respuesta = $grupo;
            return $respuesta;
        }

        public function wbsListarCursos() {
            $api = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://peers2win.talentlms.com/api/v1/courses/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$apiKey,
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }
    
        public function wbsDetalleCurso($curso) {
            $api = 'smU4PuUuA7FbB61eSSTd242FA2vTrf:';
            $apiKey = base64_encode($api);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://peers2win.talentlms.com/api/v1/courses/id:".$curso,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".$apiKey,
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }
    }