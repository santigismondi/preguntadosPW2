<?php

Class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaAleatoria($categoriaId, $preguntasJugadasIds = [])
    {
        if (!empty($preguntasJugadasIds)) {
            // Pasamos el array [1,2,3] a un string "1,2,3" seguro para SQL
            $idsString = implode(',', array_map('intval', $preguntasJugadasIds));
            $sql = "SELECT * FROM PREGUNTA 
                    WHERE categoria_id = ? AND estado = 'aprobada' AND id NOT IN ($idsString)
                    ORDER BY RAND() LIMIT 1";
        } else {
            $sql = "SELECT * FROM PREGUNTA 
                    WHERE categoria_id = ? AND estado = 'aprobada'
                    ORDER BY RAND() LIMIT 1";
        }

        $filas = $this->database->query($sql, [$categoriaId]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function getOpcionesPorPregunta($preguntaId)
    {
        $sql = "SELECT id, texto, es_correcta FROM OPCION WHERE pregunta_id = ?";
        return $this->database->query($sql, [$preguntaId]);
    }

    public function getOpcionPorId($opcionId)
    {
        $sql = "SELECT * FROM OPCION WHERE id = ?";
        $filas = $this->database->query($sql, [$opcionId]);
        return !empty($filas) ? $filas[0] : null;
    }
}
 ?>
