<?php

class AdminController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function ver()
    {
        $data = [
            'titulo' => 'Panel administrador',
            'cssExtra' => $this->getBaseUrl() . '/public/css/admin.css',

            'totalJugadores' => 128,
            'totalPartidas' => 540,
            'totalPreguntas' => 320,
            'preguntasCreadas' => 45,
            'usuariosNuevos' => 18,

            'correctasPorUsuario' => [
                ['usuario' => 'joaco', 'correctas' => 18, 'total' => 25, 'porcentaje' => 72],
                ['usuario' => 'nacho', 'correctas' => 15, 'total' => 30, 'porcentaje' => 50],
                ['usuario' => 'mora', 'correctas' => 21, 'total' => 28, 'porcentaje' => 75],
            ],

            'usuariosPorPais' => [
                ['pais' => 'Argentina', 'cantidad' => 90],
                ['pais' => 'Uruguay', 'cantidad' => 20],
                ['pais' => 'Brasil', 'cantidad' => 18],
            ],

            'usuariosPorSexo' => [
                ['sexo' => 'Masculino', 'cantidad' => 70],
                ['sexo' => 'Femenino', 'cantidad' => 48],
                ['sexo' => 'No especifica', 'cantidad' => 10],
            ],

            'usuariosPorEdad' => [
                ['grupo' => 'Menores', 'cantidad' => 35],
                ['grupo' => 'Medio', 'cantidad' => 80],
                ['grupo' => 'Jubilados', 'cantidad' => 13],
            ],

            'reportesPreguntas' => [
                [
                    'id' => 1,
                    'pregunta' => '¿Quién ganó el Mundial 1986?',
                    'categoria' => 'Historia',
                    'cantidadReportes' => 3,
                    'motivo' => 'Respuesta incorrecta',
                    'estado' => 'Pendiente',
                    'estadoClase' => 'estado-pendiente'
                ],
                [
                    'id' => 1,
                    'pregunta' => '¿En qué país se jugó el Mundial 2014?',
                    'categoria' => 'Sedes',
                    'cantidadReportes' => 1,
                    'motivo' => 'Pregunta mal redactada',
                    'estado' => 'Revisado',
                    'estadoClase' => 'estado-revisado'
                ],
            ]
        ];

        $this->renderer->render('admin', $data);
    }

    private function getBaseUrl()
    {
        return $_SERVER['SERVER_NAME'] === 'localhost' ? '/preguntadosPW2' : '';
    }

    public function editarPregunta()
    {
        $data = [
            'titulo' => 'Editar pregunta',
            'cssExtra' => $this->getBaseUrl() . '/public/css/admin.css',

            'preguntaId' => $_GET['id'] ?? 0,
            'pregunta' => '¿Quién ganó el Mundial 1986?',
            'categoria' => 'Historia',

            'opciones' => [
                ['id' => 1, 'texto' => 'Argentina', 'correcta' => true],
                ['id' => 2, 'texto' => 'Brasil', 'correcta' => false],
                ['id' => 3, 'texto' => 'Alemania', 'correcta' => false],
                ['id' => 4, 'texto' => 'Francia', 'correcta' => false],
            ]
        ];

        $this->renderer->render('editarPreguntaAdmin', $data);
    }


}
