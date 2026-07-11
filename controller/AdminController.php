<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class AdminController
{
    private $renderer;
    private $request;
    private $model;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
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

    public function ver()
    {
        $periodo = $_GET['periodo'] ?? 'mes';

        $metricas = $this->model->getMetricas($periodo);

        $data = array_merge($metricas, [
            'titulo' => 'Panel administrador',
            'cssExtra' => $this->getBaseUrl() . '/public/css/admin.css',
            'baseUrl' => $this->getBaseUrl(),

            'periodoDia' => $periodo === 'dia',
            'periodoSemana' => $periodo === 'semana',
            'periodoMes' => $periodo === 'mes',
            'periodoAnio' => $periodo === 'anio',

            'correctasPorUsuario' => $this->model->getCorrectasPorUsuario($periodo),
            'usuariosPorPais' => $this->model->getUsuariosPorPais($periodo),
            'usuariosPorSexo' => $this->model->getUsuariosPorSexo($periodo),
            'usuariosPorEdad' => $this->model->getUsuariosPorEdad($periodo)
        ]);

        $this->renderer->render('admin', $data);
    }

    private function getBaseUrl()
    {
        return $_SERVER['SERVER_NAME'] === 'localhost' ? '/preguntadosPW2' : '';
    }
}
