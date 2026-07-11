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
        Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }

        $categorias = $this->model->getCategorias();

        $partidaTerminada = false;
        $puntajeFinal = 0;
        $respuestaCorrecta = '';
        $puntajeMaximo = $this->model->getPuntajeMaximo(
            $_SESSION['usuario_id']
        );

        if (isset($_SESSION['partida_terminada']) && $_SESSION['partida_terminada']) {
            $partidaTerminada = true;
            $puntajeFinal = $_SESSION['puntaje_final'];
            $respuestaCorrecta = $_SESSION['respuesta_correcta'];

            unset($_SESSION['partida_terminada']);
            unset($_SESSION['puntaje_final']);
            unset($_SESSION['respuesta_correcta']);
        }

        $posicionRanking = $this->model->getPosicionRanking($_SESSION["usuario_id"]);

        $data = [
            'titulo'             => 'Preguntados Mundial - Lobby',
            'cssExtra'           => $this->getBaseUrl() . '/public/css/lobby.css',
            'nombre_usuario'     => $_SESSION['nombre_usuario'],
            'showAppHeader'      => true,
            'headerVariant'      => 'lobby',
            'showAppBrand'       => true,
            'headerLogo'         => $this->getBaseUrl() . '/public/img/logo.png',
            'headerTitle'        => 'Preguntados Mundial',
            'headerBrandUrl'     => $this->getBaseUrl() . '/lobby/ver',
            'showProfileButton'  => true,
            'profileButtonUrl'   => $this->getBaseUrl() . '/perfil/ver',
            'profileButtonLabel' => $_SESSION['nombre_usuario'],
            'categorias'         => $categorias,
            'puntaje'            => $_SESSION['puntaje'],
            'partidaTerminada'   => $partidaTerminada,
            'puntajeFinal'       => $puntajeFinal,
            'respuestaCorrecta'  => $respuestaCorrecta,
            'puntajeMaximo' => $puntajeMaximo,
            'posicionRanking' => $posicionRanking
        ];

        echo $this->renderer->render("lobby", $data);
    }
    public function jugar()
    {
        Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }

        $data = [
            'titulo'          => 'Preguntados Mundial - Ruleta',
            'cssExtra'        => $this->getBaseUrl() . '/public/css/lobby.css',
            'showAppHeader'   => true,
            'headerVariant'   => 'lobby',
            'showBackToLobby' => true,
            'backToLobbyUrl'  => $this->getBaseUrl() . '/lobby/ver',
            'showPageTitle'   => true,
            'headerPageTitle' => 'Ruleta',
            'categorias'      => $this->model->getCategorias(),
            'puntaje'         => $_SESSION['puntaje']
        ];

        echo $this->renderer->render("ruleta", $data);
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }

    public function reiniciar()
    {
        $_SESSION['puntaje'] = 0;
        $_SESSION['preguntas_respondidas'] = [];

        header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
        exit;
    }
}
