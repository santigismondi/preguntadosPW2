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

    public function getRanking()
    {
        $sql = "
        SELECT
            u.id,
            u.nombre_usuario,
            COALESCE(MAX(p.puntaje), 0) AS mejor_puntaje
        FROM USUARIO u
        INNER JOIN ROL r
            ON r.usuario_id = u.id
        LEFT JOIN PARTIDA p
            ON p.usuario_id = u.id
        WHERE r.descripcion = 'Usuario'
        GROUP BY
            u.id,
            u.nombre_usuario
        ORDER BY
            mejor_puntaje DESC,
            u.nombre_usuario ASC
    ";

        return $this->database->query($sql, []);
    }

    public function getPosicionRanking($usuarioId)
    {
        $ranking = $this->getRanking();

        $posicion = 1;

        foreach ($ranking as $jugador) {
            if ($jugador["id"] == $usuarioId) {
                return $posicion;
            }
            $posicion++;
        }
        return null;
    }
}