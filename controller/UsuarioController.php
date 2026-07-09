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

    public function registrar()
    {
        $nombre = $this->request->post('nombre');
        $nombre_usuario = $this->request->post('nombre_usuario');
        $email = $this->request->post('email');
        $fecha_nac = $this->request->post('fecha_nac');
        $genero = $this->request->post('genero');
        $contrasena = $this->request->post('contrasena');
        $confirmar_contrasena = $this->request->post('confirmar_contrasena');
        $coordenadas_ciudad = $this->request->post('coordenadas_ciudad') ?: '0,0';

        $datosIngresados = [
            'nombre' => $nombre,
            'nombre_usuario' => $nombre_usuario,
            'email' => $email,
            'fecha_nac' => $fecha_nac,
            'genero' => $genero,
        ];

        if (empty($nombre) || empty($nombre_usuario) || empty($email) || empty($fecha_nac) || empty($genero) || empty($contrasena)) {
            Log::warning("UsuarioController::registrar - campos vacios");
            $datosIngresados['error'] = 'Todos los campos son obligatorios.';
            echo $this->renderer->render("registro", $datosIngresados);
            return;
        }
        if ($contrasena !== $confirmar_contrasena) {
            Log::warning("UsuarioController::registrar - contrasenas no coinciden");
            $datosIngresados['error'] = 'Las contraseñas no coinciden.';
            echo $this->renderer->render("registro", $datosIngresados);
            return;
        }

        if ($this->model->existeEmail($email)) {
            Log::warning("UsuarioController::registrar - email ya existe: $email");
            $datosIngresados['error'] = 'El email ya está registrado.';
            echo $this->renderer->render("registro", $datosIngresados);
            return;
        }

        if ($this->model->existeNombreUsuario($nombre_usuario)) {
            Log::warning("UsuarioController::registrar - nombre_usuario ya existe: $nombre_usuario");
            $datosIngresados['error'] = 'El nombre de usuario ya está en uso.';
            echo $this->renderer->render("registro", $datosIngresados);
            return;
        }

        $foto_perfil = 'default.png';
        if (!empty($_FILES['foto_perfil']['name'])) {
            $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
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
        $token = bin2hex(random_bytes(16));
        $this->model->registrar($nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $hash, $foto_perfil, $token);
        $this->enviarCorreoValidacion($email, $nombre, $token);
        Log::info("UsuarioController::registrar - registrado exitosamente: $nombre_usuario");
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
            Log::warning("UsuarioController::procesarLogin - credenciales invalidas: $nombre_usuario");
            echo $this->renderer->render("login", ['error' => 'Usuario o contraseña incorrectos.']);
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
