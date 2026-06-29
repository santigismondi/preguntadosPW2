<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaRandom($categoriaId, $dificultad)
    {
        $sql = "SELECT id, texto, categoria_id FROM PREGUNTA WHERE categoria_id = ? AND dificultad = ? AND estado = 'aprobada'
        ORDER BY RAND()
        LIMIT 1 ";

        $resultado = $this->database->query($sql, [$categoriaId, $dificultad]);

        if (!empty($resultado)) {
            return $resultado[0];
        }

        return null;
    }

    public function getOpciones($preguntaId)
    {
        $sql = "SELECT id, texto, es_correcta FROM OPCION WHERE pregunta_id = ?";
        return $this->database->query($sql, [$preguntaId]);
    }

    public function getCategoria($categoriaId)
    {
        $sql = "SELECT id, nombre, color FROM CATEGORIA WHERE id = ?";
        $resultado = $this->database->query($sql, [$categoriaId]);
        if (!empty($resultado)) {
            return $resultado[0];
        }
        return null;
    }

    public function esRespuestaCorrecta($opcionId)
    {
        $sql = "SELECT es_correcta FROM OPCION WHERE id = ?";
        $resultado = $this->database->query($sql, [$opcionId]);
        if (!empty($resultado)) {
            return $resultado[0]['es_correcta'] == 1;
        }
        return false;
    }

    public function getRespuestaCorrecta($preguntaId)
    {
        $sql = "SELECT texto FROM OPCION WHERE pregunta_id = ? AND es_correcta = 1";
        $resultado = $this->database->query($sql, [$preguntaId]);
        if (!empty($resultado)) {
            return $resultado[0]['texto'];
        }
        return '';
    }

    public function getOpcionCorrectaId($preguntaId)
    {
        $sql = "SELECT id FROM OPCION WHERE pregunta_id = ? AND es_correcta = 1";
        $resultado = $this->database->query($sql, [$preguntaId]);
        if (!empty($resultado)) {
            return $resultado[0]['id'];
        }
        return null;
    }

    public function registrarPartida($usuarioId, $puntaje)
    {
        $sql = "
        INSERT INTO PARTIDA(usuario_id, puntaje, resultado)
        VALUES (?, ?, 'perdida')
    ";

        return $this->database->execute($sql, [$usuarioId, $puntaje]);
    }

    public function reportarPregunta($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO REPORTE_PREGUNTA (pregunta_id, usuario_id, motivo)
            VALUES (?, ?, ?)";

        return $this->database->execute($sql, [$preguntaId, $usuarioId, $motivo]);
    }

}
