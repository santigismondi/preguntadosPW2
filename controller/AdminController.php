<?php

class AdminController
{
    private $renderer;
    private $request;

    public function __construct($renderer, $request)
    {
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function dashboard()
    {
        Access::allow('admin.dashboard');

        echo $this->renderer->render('admin', [
            'titulo' => 'Preguntados Mundial - Panel de administración',
            'showAppHeader' => true,
            'headerVariant' => 'admin',
            'showBackToLobby' => true,
            'backToLobbyUrl' => (new ConfigParser())->get('baseUrl', '') . '/lobby/ver',
            'showLogoutButton' => true,
            'logoutUrl' => (new ConfigParser())->get('baseUrl', '') . '/usuario/logout',
            'showPageTitle' => true,
            'headerPageTitle' => 'Panel de administración',
        ]);
    }
}
