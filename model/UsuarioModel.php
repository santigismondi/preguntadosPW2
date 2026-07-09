<?php
//logueo de errores
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuarioPorCredenciales($nombre_usuario, $contrasena)
    {
        $sql = "SELECT u.*, r.descripcion as rol FROM USUARIO u
                LEFT JOIN ROL r ON r.usuario_id = u.id
                WHERE u.nombre_usuario = ?";
        Log::info("SQL: $sql [$nombre_usuario]");
        $filas = $this->database->query($sql, [$nombre_usuario]);
        if (!empty($filas)) {
            $usuario = $filas[0];
            if (password_verify($contrasena, $usuario['contrasena'])) {
                return $usuario;
            }
        }
        return null;
    }

    public function existeEmail($email)
    {
        $sql = "SELECT id FROM USUARIO WHERE email = ?";
        $filas = $this->database->query($sql, [$email]);
        return !empty($filas);
    }

    public function existeNombreUsuario($nombre_usuario)
    {
        $sql = "SELECT id FROM USUARIO WHERE nombre_usuario = ?";
        $filas = $this->database->query($sql, [$nombre_usuario]);
        return !empty($filas);
    }

    public function registrar($nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $contrasena, $foto_perfil, $token_validacion)
    {
        $sql = "INSERT INTO USUARIO (nombre, nombre_usuario, email, fecha_nac, genero, coordenadas_ciudad, contrasena, foto_perfil, token_validacion, cuenta_activa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        Log::info("SQL: $sql [$nombre, $nombre_usuario, $email]");
        $this->database->execute($sql, [$nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $contrasena, $foto_perfil, $token_validacion]);
    }

    public function validarCuenta($token)
    {
        // Buscamos si existe un usuario con ese token
        $sqlSelect = "SELECT id FROM USUARIO WHERE token_validacion = ?";
        $usuario = $this->database->query($sqlSelect, [$token]);

        if (!empty($usuario)) {
            // Si existe, actualizamos el estado y eliminamos el token por seguridad
            $sqlUpdate = "UPDATE USUARIO SET cuenta_activa = 1, token_validacion = NULL WHERE id = ?";
            $this->database->execute($sqlUpdate, [$usuario[0]['id']]);
            return true; // Validación exitosa
        }

        return false; // Token inválido o ya utilizado
    }
}