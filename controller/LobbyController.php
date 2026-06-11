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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

         var_dump($_SESSION);
         exit();

        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            Redirect::to('index.php?controller=usuario&method=login');
            exit();
        }

        $categorias = $this->model->getCategorias();

        if (!$categorias || $categorias === null) {
            $categorias = [];
        }

        $data = [
            'nombre_usuario' => $_SESSION['nombre_usuario'],
            'categorias'     => $categorias
        ];

        echo $this->renderer->render("lobby", $data);
    }
}