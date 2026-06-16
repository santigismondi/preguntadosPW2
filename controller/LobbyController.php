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
        session_start();
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/preguntadosPW2/index.php?controller=usuario&method=login');
            return;
        }

        $categorias = $this->model->getCategorias();

        $data = [
            'titulo'         => 'Preguntados Mundial - Lobby',
            'cssExtra' => '/preguntadosPW2/css/lobby.css',
            'nombre_usuario' => $_SESSION['nombre_usuario'],
            'categorias'     => $categorias
        ];

        echo $this->renderer->render("lobby", $data);
    }
}