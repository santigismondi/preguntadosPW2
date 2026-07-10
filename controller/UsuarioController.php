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
        $this->renderLogin();
    }

    public function registro()
    {
        $this->renderRegistro();
    }

    public function registrar()
    {
        $nombre = trim((string) $this->request->post('nombre'));
        $nombreUsuario = trim((string) $this->request->post('nombre_usuario'));
        $email = trim((string) $this->request->post('email'));
        $fechaNacimiento = $this->request->post('fecha_nac');
        $genero = $this->request->post('genero');
        $contrasena = (string) $this->request->post('contrasena');
        $confirmarContrasena = (string) $this->request->post('confirmar_contrasena');

        $coordenadasCiudad =
            $this->request->post('coordenadas_ciudad') ?: '0,0';

        if (
            empty($nombre) ||
            empty($nombreUsuario) ||
            empty($email) ||
            empty($fechaNacimiento) ||
            empty($genero) ||
            empty($contrasena) ||
            empty($confirmarContrasena)
        ) {
            Log::warning(
                'UsuarioController::registrar - campos vacíos'
            );

            $this->renderRegistro(
                'Todos los campos son obligatorios.'
            );

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning(
                "UsuarioController::registrar - email inválido: $email"
            );

            $this->renderRegistro(
                'Ingresá un correo electrónico válido.'
            );

            return;
        }

        if ($contrasena !== $confirmarContrasena) {
            Log::warning(
                'UsuarioController::registrar - contraseñas no coinciden'
            );

            $this->renderRegistro(
                'Las contraseñas no coinciden.'
            );

            return;
        }

        if ($this->model->existeEmail($email)) {
            Log::warning(
                "UsuarioController::registrar - email ya existe: $email"
            );

            $this->renderRegistro(
                'El correo electrónico ya está registrado.'
            );

            return;
        }

        if ($this->model->existeNombreUsuario($nombreUsuario)) {
            Log::warning(
                "UsuarioController::registrar - usuario ya existe: $nombreUsuario"
            );

            $this->renderRegistro(
                'El nombre de usuario ya está en uso.'
            );

            return;
        }

        $fotoPerfil = 'default.png';

        if (
            isset($_FILES['foto_perfil']) &&
            !empty($_FILES['foto_perfil']['name'])
        ) {
            if ($_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
                Log::error(
                    'UsuarioController::registrar - error al recibir la imagen'
                );

                $this->renderRegistro(
                    'No se pudo procesar la foto de perfil.'
                );

                return;
            }

            $extension = strtolower(
                pathinfo(
                    $_FILES['foto_perfil']['name'],
                    PATHINFO_EXTENSION
                )
            );

            $extensionesPermitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (!in_array($extension, $extensionesPermitidas, true)) {
                $this->renderRegistro(
                    'La foto debe ser JPG, PNG o WEBP.'
                );

                return;
            }

            $fotoPerfil =
                uniqid('usuario_', true) . '.' . $extension;

            $directorioDestino =
                __DIR__ . '/../public/img/usuarios/';

            if (!is_dir($directorioDestino)) {
                $directorioCreado = mkdir(
                    $directorioDestino,
                    0777,
                    true
                );

                if (!$directorioCreado) {
                    Log::error(
                        'UsuarioController::registrar - no se pudo crear: ' .
                        $directorioDestino
                    );

                    $this->renderRegistro(
                        'Error interno al preparar la foto de perfil.'
                    );

                    return;
                }
            }

            $rutaDestino =
                $directorioDestino . $fotoPerfil;

            if (
                !move_uploaded_file(
                    $_FILES['foto_perfil']['tmp_name'],
                    $rutaDestino
                )
            ) {
                Log::error(
                    'UsuarioController::registrar - falló move_uploaded_file: ' .
                    $rutaDestino
                );

                $this->renderRegistro(
                    'No se pudo guardar la foto de perfil.'
                );

                return;
            }
        }

        $hash = password_hash(
            $contrasena,
            PASSWORD_DEFAULT
        );

        $this->model->registrar(
            $nombre,
            $nombreUsuario,
            $email,
            $fechaNacimiento,
            $genero,
            $coordenadasCiudad,
            $hash,
            $fotoPerfil
        );

        Log::info(
            "UsuarioController::registrar - registrado correctamente: $nombreUsuario"
        );

        Redirect::to(
            $this->getBaseUrl() . '/usuario/login'
        );
    }

    public function procesarLogin()
    {
        $nombreUsuario = trim(
            (string) $this->request->post('nombre_usuario')
        );

        $contrasena = (string) $this->request->post('contrasena');

        if (
            empty($nombreUsuario) ||
            empty($contrasena)
        ) {
            Log::warning(
                'UsuarioController::procesarLogin - campos vacíos'
            );

            $this->renderLogin(
                'Completá todos los campos.'
            );

            return;
        }

        $usuario =
            $this->model->getUsuarioPorCredenciales(
                $nombreUsuario,
                $contrasena
            );

        if ($usuario === null) {
            Log::warning(
                "UsuarioController::procesarLogin - credenciales inválidas: $nombreUsuario"
            );

            $this->renderLogin(
                'Usuario o contraseña incorrectos.'
            );

            return;
        }

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] =
            $usuario['nombre_usuario'];

        $_SESSION['rol'] =
            $usuario['rol'] ?? 'jugador';

        $_SESSION['puntaje'] = 0;

        unset(
            $_SESSION['partida_terminada'],
            $_SESSION['puntaje_final'],
            $_SESSION['respuesta_correcta'],
            $_SESSION['pregunta_actual']
        );

        Log::info(
            "UsuarioController::procesarLogin - login exitoso id={$usuario['id']}"
        );

        Redirect::to(
            $this->getBaseUrl() . '/lobby/ver'
        );
    }

    public function logout()
    {
        session_unset();
        session_destroy();

        Log::info(
            'UsuarioController::logout'
        );

        Redirect::to(
            $this->getBaseUrl() . '/usuario/login'
        );
    }

    private function renderLogin($error = null)
    {
        $data = [
            'titulo' => 'Preguntados Mundial - Iniciar sesión',
            'baseUrl' => $this->getBaseUrl()
        ];

        if ($error !== null) {
            $data['error'] = $error;
        }

        echo $this->renderer->render(
            'login',
            $data
        );
    }

    private function renderRegistro($error = null)
    {
        $data = [
            'titulo' => 'Preguntados Mundial - Crear cuenta',
            'baseUrl' => $this->getBaseUrl()
        ];

        if ($error !== null) {
            $data['error'] = $error;
        }

        echo $this->renderer->render(
            'registro',
            $data
        );
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get(
            'baseUrl',
            ''
        );
    }
}