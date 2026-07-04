<?php

class UsuarioController
{
    private $renderer;
    private $request;
    private $model;

    public function __construct($model, $renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->model = $model;
    }

    public function login()
    {
        $this->renderer->render("login", [
            "titulo" => "Preguntados Mundial - Login"
        ]);
    }

    public function registro()
    {
        $this->renderer->render("registro", [
            "titulo" => "Preguntados Mundial - Registro"
        ]);
    }
    public function registrar(){
        $nombre = $this->request->post('nombre');
        $nombre_usuario = $this->request->post('nombre_usuario');
        $email = $this->request->post('email');
        $fecha_nac = $this->request->post('fecha_nac');
        $genero = $this->request->post('genero');
        $contrasena = $this->request->post('contrasena');
        $confirmar_contrasena = $this->request->post('confirmar_contrasena');
        $coordenadas_ciudad  = $this->request->post('coordenadas_ciudad') ?: '0,0';

        if (empty($nombre) || empty($nombre_usuario) || empty($email) || empty($fecha_nac) || empty($genero) || empty($contrasena)) {
            Log::warning("UsuarioController::registrar - campos vacios");
                echo $this->renderer->render("registro", ['error' => 'Todos los campos son obligatorios.']);
            return;
        }
 
        if ($contrasena !== $confirmar_contrasena) {
            Log::warning("UsuarioController::registrar - contrasenas no coinciden");
            echo $this->renderer->render("registro", ['error' => 'Las contraseñas no coinciden.']);
            return;
        }
 
        if ($this->model->existeEmail($email)) {
            Log::warning("UsuarioController::registrar - email ya existe: $email");
            echo $this->renderer->render("registro", ['error' => 'El email ya está registrado.']);
            return;
        }
 
        if ($this->model->existeNombreUsuario($nombre_usuario)) {
            Log::warning("UsuarioController::registrar - nombre_usuario ya existe: $nombre_usuario");
            echo $this->renderer->render("registro", ['error' => 'El nombre de usuario ya está en uso.']);
            return;
        }

        $foto_perfil = 'default.png';
        if (!empty($_FILES['foto_perfil']['name'])) {
            $extension   = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $foto_perfil = uniqid() . '.' . $extension;

            // 1. Definimos la ruta absoluta apuntando correctamente al directorio público
            $dir_destino = __DIR__ . '/../public/img/usuarios/';

            // 2. Programación defensiva: si el directorio no existe, lo creamos dinámicamente
            if (!is_dir($dir_destino)) {
                if (!mkdir($dir_destino, 0777, true)) {
                    Log::error("UsuarioController::registrar - No se pudo crear el directorio: $dir_destino");
                    echo $this->renderer->render("registro", ['error' => 'Error interno del servidor al procesar la imagen.']);
                    return;
                }
            }

            // 3. Control estricto del resultado de la subida física
            if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $dir_destino . $foto_perfil)) {
                Log::error("UsuarioController::registrar - Falló move_uploaded_file a: " . $dir_destino . $foto_perfil);
                echo $this->renderer->render("registro", ['error' => 'No se pudo guardar la foto de perfil de manera segura.']);
                return;
            }
        }

        // Si la foto se subió con éxito (o quedó la default), recién ahí procedemos a la persistencia
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $this->model->registrar($nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $hash, $foto_perfil);

        Log::info("UsuarioController::registrar - registrado exitosamente: $nombre_usuario");
        Redirect::to($this->getBaseUrl() . '/usuario/login');
    }
 
    public function procesarLogin()
    {
        $nombre_usuario = $this->request->post('nombre_usuario');
        $contrasena     = $this->request->post('contrasena');
 
        if (empty($nombre_usuario) || empty($contrasena)) {
            Log::warning("UsuarioController::procesarLogin - campos vacios");
            echo $this->renderer->render("login", ['error' => 'Completá todos los campos.']);
            return;
        }
 
        $usuario = $this->model->getUsuarioPorCredenciales($nombre_usuario, $contrasena);
 
        if ($usuario === null) {
            Log::warning("UsuarioController::procesarLogin - credenciales invalidas: $nombre_usuario");
            echo $this->renderer->render("login", ['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }
 
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['rol'] = $usuario['rol'] ?? 'jugador';

        Log::info("UsuarioController::procesarLogin - login exitoso id={$usuario['id']}");
        Redirect::to($this->getBaseUrl() . "/lobby/ver");
    }
 
    public function logout()
    {
        session_destroy();
        Log::info("UsuarioController::logout");
        Redirect::to($this->getBaseUrl() . '/usuario/login');
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }
}
