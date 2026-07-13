<?php
class RankingModel {
    private $database;
    public function __construct($database)
    {
        $this->database = $database;
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

}
