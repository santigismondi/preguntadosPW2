<?php

class EditorModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntas()
    {
        $sql = "
        SELECT 
            p.id,
            p.texto,
            p.dificultad,
            c.nombre AS categoria,
            oc.texto AS respuesta_correcta
        FROM PREGUNTA p
        INNER JOIN CATEGORIA c 
            ON c.id = p.categoria_id
        LEFT JOIN OPCION oc 
            ON oc.pregunta_id = p.id 
            AND oc.es_correcta = 1
        ORDER BY p.id ASC
    ";

        return $this->database->query($sql);
    }

    public function getReportesPendientes()
    {
        $sql = "
        SELECT
            r.id AS reporte_id,
            r.motivo,
            r.estado,
            DATE_FORMAT(r.fecha, '%d/%m/%Y %H:%i') AS fecha,
            p.id AS pregunta_id,
            p.texto AS pregunta,
            u.nombre_usuario AS usuario,
            c.nombre AS categoria
        FROM REPORTE_PREGUNTA r
        INNER JOIN PREGUNTA p ON p.id = r.pregunta_id
        INNER JOIN USUARIO u ON u.id = r.usuario_id
        INNER JOIN CATEGORIA c ON c.id = p.categoria_id
        WHERE r.estado = 'pendiente'
        ORDER BY r.fecha DESC
    ";

        return $this->database->query($sql);
    }

    public function getPreguntaPorId($id)
    {
        $sql = "
            SELECT 
                p.id,
                p.texto,
                p.dificultad,
                p.estado,
                p.categoria_id,
                c.nombre AS categoria
            FROM PREGUNTA p
            INNER JOIN CATEGORIA c ON c.id = p.categoria_id
            WHERE p.id = ?
        ";

        $resultado = $this->database->query($sql, [$id]);

        return $resultado[0] ?? null;
    }

    public function getOpcionesPorPregunta($id)
    {
        $sql = "
            SELECT id, texto, es_correcta
            FROM OPCION
            WHERE pregunta_id = ?
        ";

        return $this->database->query($sql, [$id]);
    }

    public function actualizarPregunta($id, $texto, $opciones, $correcta)
    {
        $this->database->execute(
            "UPDATE PREGUNTA SET texto = ?, estado = 'aprobada' WHERE id = ?",
            [$texto, $id]
        );

        foreach ($opciones as $opcionId => $opcionTexto) {
            $esCorrecta = ((int)$opcionId === (int)$correcta) ? 1 : 0;

            $this->database->execute(
                "UPDATE OPCION SET texto = ?, es_correcta = ? WHERE id = ?",
                [$opcionTexto, $esCorrecta, $opcionId]
            );
        }

        $this->database->execute(
            "UPDATE REPORTE_PREGUNTA SET estado = 'resuelto' WHERE pregunta_id = ?",
            [$id]
        );
    }

    public function rechazarReporte($id)
    {
        return $this->database->execute(
            "UPDATE REPORTE_PREGUNTA SET estado = 'rechazado' WHERE id = ?",
            [$id]
        );
    }

    public function eliminarPregunta($id)
    {
        return $this->database->execute(
            "UPDATE PREGUNTA SET estado = 'eliminada' WHERE id = ?",
            [$id]
        );
    }

    public function getCategorias()
    {
        $sql = "
        SELECT id, nombre
        FROM CATEGORIA
        ORDER BY nombre ASC
    ";

        return $this->database->query($sql);
    }

    public function crearPregunta(
        $texto,
        $categoriaId,
        $opciones,
        $indiceCorrecta
    ) {
        $sqlPregunta = "
        INSERT INTO PREGUNTA (
            texto,
            dificultad,
            categoria_id,
            estado
        )
        VALUES (?, 0, ?, 'aprobada')
    ";

        $this->database->execute(
            $sqlPregunta,
            [$texto, $categoriaId]
        );

        $resultado = $this->database->query(
            "SELECT LAST_INSERT_ID() AS id"
        );

        $preguntaId = $resultado[0]['id'];

        foreach ($opciones as $indice => $textoOpcion) {
            $esCorrecta = $indice === $indiceCorrecta ? 1 : 0;

            $sqlOpcion = "
            INSERT INTO OPCION (
                pregunta_id,
                texto,
                es_correcta
            )
            VALUES (?, ?, ?)
        ";

            $this->database->execute(
                $sqlOpcion,
                [
                    $preguntaId,
                    trim($textoOpcion),
                    $esCorrecta
                ]
            );
        }

        return $preguntaId;
    }
}
