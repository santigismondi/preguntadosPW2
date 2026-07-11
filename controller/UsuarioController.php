<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Asegurate de que la ruta al autoload sea correcta según la ubicación de tu controlador
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

        // Si la foto se subió con éxito (o quedó la default), recién ahí procedemos a la persistencia
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));
        $this->model->registrar($nombre, $nombreUsuario, $email, $fechaNacimiento, $genero, $coordenadasCiudad, $hash, $fotoPerfil, $token);
        $this->enviarCorreoValidacion($email, $nombre, $token);
        Log::info("UsuarioController::registrar - registrado exitosamente: $nombreUsuario");
        echo $this->renderer->render("login", ['mensaje_exito' => 'Registro exitoso. Te enviamos un correo para validar tu cuenta.']);
    }

    private function enviarCorreoValidacion($email, $nombre, $token)
    {
        $mail = new PHPMailer(true);
        try {
            // Configuración de Mailtrap
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = (new ConfigParser())->get('mail_user', '13b6c5f52b7e9e');
            $mail->Password = (new ConfigParser())->get('mail_pass', 'c9041c1603f335');
            $mail->Port = 2525;

            $mail->setFrom('no-reply@preguntadosmundial.com', 'Preguntados Mundial');
            $mail->addAddress($email, $nombre);

            $mail->isHTML(true);
            $mail->Subject = 'Valida tu cuenta para empezar a jugar';

            // Armamos el enlace que el usuario debe clickear
            $enlace = $this->getBaseUrl() . "/usuario/validar?token=" . $token;

            $mail->Body = "
                <div style='background: #F8E7A0; padding: 10px 15px; font-family: Arial, sans-serif;'> 
                    <h2>Hola $nombre! Bienvenido al desafio mundialista.</h2>
                    <p>Para poder iniciar sesion y empezar a jugar, necesitamos que valides tu correo electronico.</p>
                    <p>Hace clic en el siguiente enlace:</p>
                    <a href='$enlace' style='padding: 10px 15px; background: #198754; color: white; text-decoration: none; border-radius: 5px;'>Validar mi cuenta</a>
                    <p>Si no podes acceder copia este enlace: <b>$enlace</b></p>
                    <p>y pegalo luego del dominio (/) en tu navegador</p>
                </div>";

            $mail->send();
            Log::info("Correo de validación enviado a $email");
        } catch (Exception $e) {
            Log::error("No se pudo enviar el correo a $email. Error: {$mail->ErrorInfo}");
        }
    }

    // Endpoint que recibe el clic del correo
    public function validar()
    {
        // Asumiendo que tu request puede capturar GET, sino podés usar $_GET['token']
        $token = $_GET['token'] ?? null;

        if (!$token) {
            Redirect::to($this->getBaseUrl() . '/usuario/login');
        }

        $exito = $this->model->validarCuenta($token);

        if ($exito) {
            Log::info("UsuarioController::validar - Cuenta activada exitosamente con token");
            echo $this->renderer->render("login", ['mensaje_exito' => '¡Cuenta validada! Ya podés iniciar sesión.']);
        } else {
            Log::warning("UsuarioController::validar - Intento de validación fallido (token inválido)");
            echo $this->renderer->render("login", ['error' => 'El enlace de validación es inválido o la cuenta ya fue activada.']);
        }
    }

    public function procesarLogin()
    {
        $nombre_usuario = $this->request->post('nombre_usuario');
        $contrasena = $this->request->post('contrasena');

        if (empty($nombre_usuario) || empty($contrasena)) {
            Log::warning("UsuarioController::procesarLogin - campos vacios");
            echo $this->renderer->render("login", ['error' => 'Completá todos los campos.']);
            return;
        }

        $usuario = $this->model->getUsuarioPorNombreUsuario($nombre_usuario);

        if ($usuario === null) {
            Log::warning(
                "UsuarioController::procesarLogin - credenciales inválidas: $nombre_usuario"
            );

            $this->renderLogin(
                'Usuario o contraseña incorrectos.'
            );

            return;
        }

        if (!password_verify($contrasena, $usuario['contrasena'])) {
            Log::warning("UsuarioController::procesarLogin - contrasena incorrecta para: $nombre_usuario");
            echo $this->renderer->render("login", ['error' => 'La contraseña ingresada es incorrecta.']);
            return;
        }

        if ($usuario['cuenta_activa'] == 0) {
            Log::warning("UsuarioController::procesarLogin - cuenta no validada: $nombre_usuario");
            echo $this->renderer->render("login", ['error' => 'Por favor, revisá tu correo y validá tu cuenta antes de ingresar.']);
            return;
        }

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];

        $_SESSION['rol'] = $usuario['rol'] ?? 'Usuario';

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

        if ($_SESSION['rol'] === 'Administrador') {
            Redirect::to($this->getBaseUrl() . '/admin/ver');
        } elseif ($_SESSION['rol'] === 'Editor') {
            Redirect::to($this->getBaseUrl() . '/editor/ver');
        } else {
            Redirect::to($this->getBaseUrl() . '/lobby/ver');
        }
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