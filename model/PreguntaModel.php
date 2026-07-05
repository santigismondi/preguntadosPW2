<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaRandom($categoriaId, $nivelJugador)
    {
        $sql = "SELECT id, texto, categoria_id FROM PREGUNTA WHERE categoria_id = ? AND dificultad = ? AND estado = 'aprobada'
        ORDER BY RAND()
        LIMIT 1 ";

        $resultado = $this->database->query($sql, [$categoriaId, $nivelJugador]);

        if (!empty($resultado)) {
            return $resultado[0];
        }

        //Esta linea lo que hace es que al empezar a jugar si no hay preguntas con dificultad aun cargadas
        //Llama a cualquier pregunta disponible.

        $sql = "SELECT id,texto,categoria_id
            FROM PREGUNTA
            WHERE categoria_id=?
            AND estado='aprobada'
            ORDER BY RAND()
            LIMIT 1";

        $resultado = $this->database->query($sql, [$categoriaId]);

        return !empty($resultado) ? $resultado[0] : null;
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

    public function actualizarEstadisticasPregunta($preguntaId, $esCorrecta)
    {
        if ($esCorrecta) {

            $sql = "UPDATE PREGUNTA 
            SET respuestas_correctas = respuestas_correctas + 1
            WHERE id = ?";

        } else {

            $sql = "UPDATE PREGUNTA
                    SET respuestas_incorrectas = respuestas_incorrectas + 1
                    WHERE id = ?";

        }
        $this->database->execute($sql, [$preguntaId]);

        $this->recalcularDificultad($preguntaId);
    }

    private function recalcularDificultad($preguntaId)
    {
        $sql = "SELECT respuestas_correctas, respuestas_incorrectas
                FROM PREGUNTA
                WHERE id = ?";

        $pregunta = $this->database->query($sql, [$preguntaId])[0];

        $correctas = $pregunta["respuestas_correctas"];
        $incorrectas = $pregunta["respuestas_incorrectas"];

        $total = $correctas + $incorrectas;

        if($total == 0){
            return;
        }

        $porcentaje = $correctas / $total;

        if($porcentaje >= 0.70){
            $dificultad = 0;
        }elseif($porcentaje >= 0.30){
            $dificultad = 1;
        }else{
            $dificultad = 2;
        }

        $sql = "UPDATE PREGUNTA SET dificultad=? WHERE id=?";

        $this->database->execute($sql,[$dificultad,$preguntaId]);
    }
}
