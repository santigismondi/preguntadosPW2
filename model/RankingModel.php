<?php
class RankingModel {
    private $database;
    public function __construct($database)
    {
        $this->database = $database;
    }
    public function getRanking()
    {
        $sql = "SELECT U.id, U.nombre_usuario, COALESCE(MAX(P.puntaje),0) AS mejor_puntaje
                FROM USUARIO U LEFT JOIN PARTIDA P ON U.id = P.usuario_id
                GROUP BY U.id, U.nombre_usuario
                ORDER BY mejor_puntaje DESC, U.nombre_usuario";

        return $this->database->query($sql, []);
    }

}
