<?php

class PerfilModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuario($usuarioId)
    {
        $sql = "SELECT id, nombre, nombre_usuario, foto_perfil, coordenadas_ciudad
                FROM USUARIO
                WHERE id = ?";
        $filas = $this->database->query($sql, [$usuarioId]);

        return !empty($filas) ? $filas[0] : null;
    }

    public function getPuntajeMaximo($usuarioId)
    {
        $sql = "SELECT MAX(puntaje) AS max_puntaje
                FROM PARTIDA
                WHERE usuario_id = ?";
        $resultado = $this->database->query($sql, [$usuarioId]);

        return (!empty($resultado) && $resultado[0]['max_puntaje'] !== null)
            ? $resultado[0]['max_puntaje']
            : 0;
    }

    public function getPartidas($usuarioId)
    {
        $sql = "SELECT id, puntaje, resultado
                FROM PARTIDA
                WHERE usuario_id = ?
                ORDER BY id DESC";
        return $this->database->query($sql, [$usuarioId]);
    }

    public function getCantidadPartidas($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM PARTIDA
                WHERE usuario_id = ?";
        $resultado = $this->database->query($sql, [$usuarioId]);

        return !empty($resultado) ? $resultado[0]['total'] : 0;
    }
}