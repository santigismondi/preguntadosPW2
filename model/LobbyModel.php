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
}