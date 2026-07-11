<?php

class AdminController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
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