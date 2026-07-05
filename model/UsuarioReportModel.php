<?php

class UsuarioReportModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Recomendación técnica de índice:
     * - USUARIO(created_at)
     * - USUARIO(created_at, pais, genero)
     */
    public function getUserMetrics(string $period, string $from, string $to, ?string $country = null, ?string $sex = null): array
    {
        $bucketExpression = $this->bucketExpression($period);
        $sql = "
            SELECT
                {$bucketExpression} AS time_bucket,
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) < 18 THEN '<18'
                    WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    ELSE '55+'
                END AS age_group,
                COALESCE(NULLIF(u.pais, ''), 'Sin país') AS country,
                COALESCE(NULLIF(u.genero, ''), 'Sin sexo') AS sex,
                COUNT(*) AS total
            FROM USUARIO u
            WHERE u.created_at >= ?
              AND u.created_at < ?
        ";

        $params = [$from, $to];

        if ($country !== null && $country !== '') {
            $sql .= " AND u.pais = ?";
            $params[] = $country;
        }

        if ($sex !== null && $sex !== '') {
            $sql .= " AND u.genero = ?";
            $params[] = $sex;
        }

        $sql .= "
            GROUP BY time_bucket, age_group, country, sex
            ORDER BY time_bucket, age_group, country, sex
        ";

        return $this->database->query($sql, $params);
    }

    private function bucketExpression(string $period): string
    {
        switch ($period) {
            case 'day':
                return 'DATE(u.created_at)';
            case 'week':
                return 'YEARWEEK(u.created_at, 3)';
            case 'month':
                return "DATE_FORMAT(u.created_at, '%Y-%m')";
        }

        throw new InvalidArgumentException('Periodo no soportado.');
    }
}
