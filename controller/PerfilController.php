<?php

class PerfilController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function ver()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to($this->getBaseUrl() . '/usuario/login');
            return;
        }

        $usuarioId = $this->request->get('id') ?: $_SESSION['usuario_id'];

        $usuario = $this->model->getUsuario($usuarioId);

        if ($usuario === null) {
            Redirect::to($this->getBaseUrl() . '/lobby/ver');
            return;
        }

        $urlPerfil = $this->getUrlPerfil($usuario['id']);

        echo $this->renderer->render('perfil', [
            'titulo' => 'Preguntados Mundial - Perfil',
            'usuario' => $usuario,
            'puntajeMaximo' => $this->model->getPuntajeMaximo($usuarioId),
            'cantidadPartidas' => $this->model->getCantidadPartidas($usuarioId),
            'partidas' => $this->model->getPartidas($usuarioId),
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($urlPerfil),
            'cssExtra' => $this->getBaseUrl() . '/public/css/perfil.css',
            'baseUrl' => $this->getBaseUrl(),
        ]);
    }

    private function getUrlPerfil($usuarioId)
    {
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocolo . '://' . $host . $this->getBaseUrl() . '/perfil/ver?id=' . $usuarioId;
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }
}
