<?php

class LobbyModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getCategorias()
    {
        $sql = "SELECT id, nombre, color FROM CATEGORIA";
        $filas = $this->database->query($sql, []);
        return $filas;
    }

    public function getPuntajeMaximo($usuarioId)
    {
        $sql = "SELECT MAX(puntaje) as max_puntaje FROM PARTIDA WHERE usuario_id = ?";
        $resultado = $this->database->query($sql, [$usuarioId]);
        return (!empty($resultado) && $resultado[0]['max_puntaje'] !== null) ? $resultado[0]['max_puntaje'] : 0;
    }
}