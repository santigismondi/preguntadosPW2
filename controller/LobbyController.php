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
            Redirect::to('/preguntadosPW2-main/index.php?controller=usuario&method=login');
            return;
        }

        $categorias = $this->model->getCategorias();

        $data = [
            'nombre_usuario' => $_SESSION['nombre_usuario'],
            'categorias'     => $categorias
        ];

        echo $this->renderer->render("lobby", $data);
    }
}