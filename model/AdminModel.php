<?php

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    private function filtroFecha($campo, $periodo)
    {
        switch ($periodo) {
            case 'dia':
                return "$campo >= CURDATE()";
            case 'semana':
                return "$campo >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'anio':
                return "$campo >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            case 'mes':
            default:
                return "$campo >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    public function getMetricas($periodo)
    {
        $filtroUsuarios = $this->filtroFecha('fecha_creacion', $periodo);
        $filtroPartidas = $this->filtroFecha('fecha_partida', $periodo);
        $filtroPreguntas = $this->filtroFecha('fecha_creacion', $periodo);

        return [
            'totalJugadores' => $this->contar("SELECT COUNT(*) total FROM USUARIO"),
            'totalPartidas' => $this->contar("SELECT COUNT(*) total FROM PARTIDA WHERE $filtroPartidas"),
            'totalPreguntas' => $this->contar("SELECT COUNT(*) total FROM PREGUNTA WHERE estado = 'aprobada'"),
            'preguntasCreadas' => $this->contar("SELECT COUNT(*) total FROM PREGUNTA WHERE $filtroPreguntas"),
            'usuariosNuevos' => $this->contar("SELECT COUNT(*) total FROM USUARIO WHERE $filtroUsuarios")
        ];
    }

    public function getCorrectasPorUsuario($periodo)
    {
        $filtro = $this->filtroFecha('p.fecha_partida', $periodo);

        $sql = "
        SELECT 
            u.nombre_usuario AS usuario,
            SUM(CASE WHEN p.resultado = 'ganada' THEN 1 ELSE 0 END) AS correctas,
            COUNT(p.id) AS total
        FROM USUARIO u
        LEFT JOIN PARTIDA p ON p.usuario_id = u.id AND $filtro
        GROUP BY u.id, u.nombre_usuario
        LIMIT 10
    ";

        $datos = $this->database->query($sql);

        foreach ($datos as &$fila) {
            $total = (int)$fila['total'];
            $correctas = (int)$fila['correctas'];
            $fila['porcentaje'] = $total > 0 ? round(($correctas * 100) / $total) : 0;
        }

        return $datos;
    }

    public function getUsuariosPorPais($periodo)
    {
        $filtro = $this->filtroFecha('fecha_creacion', $periodo);

        $sql = "
        SELECT
            coordenadas_ciudad AS pais,
            COUNT(*) AS cantidad
        FROM USUARIO
        WHERE $filtro
        GROUP BY coordenadas_ciudad
        ORDER BY cantidad DESC
    ";

        return $this->database->query($sql);
    }

    public function getUsuariosPorSexo($periodo)
    {
        $filtro = $this->filtroFecha('u.fecha_creacion', $periodo);

        $sql = "
        SELECT 
            g.descripcion AS sexo,
            COUNT(*) AS cantidad
        FROM USUARIO u
        INNER JOIN GENERO g ON g.id = u.genero
        WHERE $filtro
        GROUP BY g.descripcion
    ";

        return $this->database->query($sql);
    }

    public function getUsuariosPorEdad($periodo)
    {
        $filtro = $this->filtroFecha('fecha_creacion', $periodo);

        $sql = "
        SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nac, CURDATE()) < 18 THEN 'Menores'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nac, CURDATE()) >= 65 THEN 'Jubilados'
                ELSE 'Medio'
            END AS grupo,
            COUNT(*) AS cantidad
        FROM USUARIO
        WHERE $filtro
        GROUP BY grupo
    ";

        return $this->database->query($sql);
    }

    private function contar($sql)
    {
        $resultado = $this->database->query($sql);
        return $resultado[0]['total'] ?? 0;
    }
}
