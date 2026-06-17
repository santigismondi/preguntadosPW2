<?php

class LobbyController
{
    private $renderer;
    private $request;
    private $model;

    public function __construct($model, $renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request  = $request;
        $this->model    = $model;
    }

    public function ver()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /usuario/login');
            return;
        }

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }

        $categorias = $this->model->getCategorias();

        $partidaTerminada = false;
        $puntajeFinal = 0;
        $respuestaCorrecta = '';

        if (isset($_SESSION['partida_terminada']) && $_SESSION['partida_terminada']) {
            $partidaTerminada = true;
            $puntajeFinal = $_SESSION['puntaje_final'];
            $respuestaCorrecta = $_SESSION['respuesta_correcta'];

            unset($_SESSION['partida_terminada']);
            unset($_SESSION['puntaje_final']);
            unset($_SESSION['respuesta_correcta']);
        }

        $data = [
            'titulo'             => 'Preguntados Mundial - Lobby',
            'cssExtra'           => '/preguntadosPW2/css/lobby.css',
            'nombre_usuario'     => $_SESSION['nombre_usuario'],
            'categorias'         => $categorias,
            'puntaje'            => $_SESSION['puntaje'],
            'partidaTerminada'   => $partidaTerminada,
            'puntajeFinal'       => $puntajeFinal,
            'respuestaCorrecta'  => $respuestaCorrecta
        ];

        echo $this->renderer->render("lobby", $data);
    }
}
