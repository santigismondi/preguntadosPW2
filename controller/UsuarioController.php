<?php

class UsuarioController
{
    private $renderer;
    private $request;

    public function __construct($renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function login()
    {
        echo $this->renderer->render("login");
    }

    public function registro()
    {
        echo $this->renderer->render("registro");
    }
}
