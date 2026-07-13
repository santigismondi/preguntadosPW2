<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function getPreguntaPorId($preguntaId){
        $sql = "SELECT id, texto, categoria_id FROM PREGUNTA WHERE id = ?";

        $resultado = $this->database->query($sql, [$preguntaId]);

        return !empty($resultado) ? $resultado[0] : null;
    }
    public function getPreguntaRandom(
        $categoriaId,
        $nivelJugador,
        array $preguntasExcluidas = []
    ) {
        $parametros = [$categoriaId, $nivelJugador];

        $sql = "
        SELECT id, texto, categoria_id
        FROM PREGUNTA
        WHERE categoria_id = ?
          AND dificultad = ?
          AND estado = 'aprobada'
    ";

        if (!empty($preguntasExcluidas)) {
            $marcadores = implode(
                ',',
                array_fill(0, count($preguntasExcluidas), '?')
            );

            $sql .= " AND id NOT IN ($marcadores)";

            $parametros = array_merge(
                $parametros,
                $preguntasExcluidas
            );
        }

        $sql .= "
        ORDER BY RAND()
        LIMIT 1
    ";

        $resultado = $this->database->query(
            $sql,
            $parametros
        );

        if (!empty($resultado)) {
            return $resultado[0];
        }

        /*
         * Si no quedan preguntas de la dificultad actual,
         * buscamos cualquier dificultad de la misma categoría,
         * pero todavía evitando las preguntas ya vistas.
         */
        $parametros = [$categoriaId];

        $sql = "
        SELECT id, texto, categoria_id
        FROM PREGUNTA
        WHERE categoria_id = ?
          AND estado = 'aprobada'
    ";

        if (!empty($preguntasExcluidas)) {
            $marcadores = implode(
                ',',
                array_fill(0, count($preguntasExcluidas), '?')
            );

            $sql .= " AND id NOT IN ($marcadores)";

            $parametros = array_merge(
                $parametros,
                $preguntasExcluidas
            );
        }

        $sql .= "
        ORDER BY RAND()
        LIMIT 1
    ";

        $resultado = $this->database->query(
            $sql,
            $parametros
        );

        return !empty($resultado)
            ? $resultado[0]
            : null;
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

    public function crearPreguntaSugerida($texto, $categoriaId, $opciones, $correctaIndex, $usuarioId)
    {
        $sql = "
            INSERT INTO PREGUNTA(texto, dificultad, categoria_id, estado, usuario_id)
            VALUES (?, 1, ?, 'pendiente', ?)
        ";

        $this->database->execute($sql, [$texto, $categoriaId, $usuarioId]);

        $resultado = $this->database->query("SELECT LAST_INSERT_ID() AS id");
        $preguntaId = $resultado[0]['id'];

        foreach ($opciones as $index => $opcionTexto) {
            $esCorrecta = ($index == $correctaIndex) ? 1 : 0;

            $sql = "
                INSERT INTO OPCION(pregunta_id, texto, es_correcta)
                VALUES (?, ?, ?)
            ";

            $this->database->execute($sql, [$preguntaId, $opcionTexto, $esCorrecta]);
        }

        return $preguntaId;
    }

    public function getCategorias()
    {
        $sql = "SELECT id, nombre, color FROM CATEGORIA";
        return $this->database->query($sql);
    }

    public function reportarPregunta(
        int $preguntaId,
        int $usuarioId,
        string $motivo
    ): int {
        $sql = "
        INSERT INTO REPORTE_PREGUNTA
            (pregunta_id, usuario_id, motivo)
        VALUES
            (?, ?, ?)
    ";

        return $this->database->execute(
            $sql,
            [
                $preguntaId,
                $usuarioId,
                $motivo
            ]
        );
    }
    public function registrarRespuestaUsuario(
        int $usuarioId,
        int $preguntaId,
        int $opcionId,
        bool $esCorrecta
    ) {
        $sql = "
        INSERT INTO RESPUESTA_USUARIO
            (
                usuario_id,
                pregunta_id,
                opcion_id,
                es_correcta
            )
        VALUES (?, ?, ?, ?)
    ";

        return $this->database->execute(
            $sql,
            [
                $usuarioId,
                $preguntaId,
                $opcionId,
                $esCorrecta ? 1 : 0
            ]
        );
    }

}
