<?php

Class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaCompleta($categoriaId, $idPreguntasJugadas = [])
    {
        $pregunta = $this->getPreguntaAleatoria($categoriaId, $idPreguntasJugadas);
        if (!$pregunta) {
            return null;
        }
        $opciones = $this->getOpcionesByPreguntaId($pregunta['id']);
        $pregunta['opciones'] = $opciones;
        return $pregunta;
    }

    public function getPreguntaAleatoria($categoriaId, $idPreguntasJugadas = [])
    {
        if (!empty($idPreguntasJugadas)) {
            $idsString = implode(',', array_map('intval', $idPreguntasJugadas));
            $sql = "SELECT * FROM PREGUNTA 
                    WHERE categoria_id = " . intval($categoriaId) . " 
                    AND id NOT IN ($idsString) 
                    ORDER BY RAND() LIMIT 1";
        } else {
            $sql = "SELECT * FROM PREGUNTA 
                    WHERE categoria_id = " . intval($categoriaId) . " 
                    ORDER BY RAND() LIMIT 1";
        }
        $resultado = $this->database->query($sql);
        return !empty($resultado) ? $resultado[0] : null;
    }

    public function getOpcionesByPreguntaId($preguntaId)
    {
        $sql = "SELECT * FROM OPCION WHERE pregunta_id = " . intval($preguntaId);
        return $this->database->query($sql);
    }
}
 ?>
