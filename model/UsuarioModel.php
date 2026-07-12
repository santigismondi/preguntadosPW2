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

    public function getUsuarioPorNombreUsuario($nombre_usuario)
    {
        $sql = "SELECT u.*, r.descripcion as rol FROM USUARIO u
                LEFT JOIN ROL r ON r.usuario_id = u.id
                WHERE u.nombre_usuario = ?";
        Log::info("SQL: $sql [$nombre_usuario]");
        $filas = $this->database->query($sql, [$nombre_usuario]);

        return !empty($filas) ? $filas[0] : null;
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
        // 1. Insertamos el nuevo usuario en la tabla USUARIO
        $sqlUsuario = "INSERT INTO USUARIO (nombre, nombre_usuario, email, fecha_nac, genero, coordenadas_ciudad, contrasena, foto_perfil, token_validacion, cuenta_activa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        Log::info("SQL Usuario: $sqlUsuario [$nombre, $nombre_usuario, $email]");
        $this->database->execute($sqlUsuario, [$nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $contrasena, $foto_perfil, $token_validacion]);

        // 2. Recuperamos el ID del usuario que se acaba de crear
        $resultadoId = $this->database->query("SELECT LAST_INSERT_ID() AS id");
        $usuarioId = $resultadoId[0]['id'];

        // 3. Le asignamos automáticamente el rol básico de 'Usuario' en la tabla ROL
        $sqlRol = "INSERT INTO ROL (usuario_id, descripcion) VALUES (?, 'Usuario')";
        Log::info("SQL Rol Automático: $sqlRol [Usuario ID: $usuarioId]");
        $this->database->execute($sqlRol, [$usuarioId]);
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