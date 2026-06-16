<?php

class PreguntaController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function ver()
    {
        $data = [
            'titulo' => 'Preguntados Mundial - Pregunta',
            'cssExtra' => '/preguntadosPW2/css/pregunta.css',
            'colorCategoria' => '#ffe400',
            'iconoCategoria' => '/preguntadosPW2/img/iconos/icono-historia.png',
            'nombreCategoria' => 'Historia',
            'textoPregunta' => '¿En qué año se jugó el primer Mundial de fútbol?',
            'opciones' => [
                ['id' => 1, 'texto' => '1924'],
                ['id' => 2, 'texto' => '1928'],
                ['id' => 3, 'texto' => '1930'],
                ['id' => 4, 'texto' => '1934']
            ]
        ];

        echo $this->renderer->render("pregunta", $data);
    }
}
