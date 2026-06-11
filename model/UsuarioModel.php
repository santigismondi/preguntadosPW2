<?php

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

    public function registrar($nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $contrasena, $foto_perfil)
    {
        $sql = "INSERT INTO USUARIO (nombre, nombre_usuario, email, fecha_nac, genero, coordenadas_ciudad, contrasena, foto_perfil)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Log::info("SQL: $sql [$nombre, $nombre_usuario, $email]");
        $this->database->execute($sql, [$nombre, $nombre_usuario, $email, $fecha_nac, $genero, $coordenadas_ciudad, $contrasena, $foto_perfil]);
    }
}