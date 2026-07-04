<?php

class ReporteController
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

    public function usuarios()
    {
        Access::allowAnyRole(['Editor', 'Administrador']);

        $period = $this->request->get('period') ?: 'month';
        $from = $this->request->get('from');
        $to = $this->request->get('to');

        if (empty($from) || empty($to)) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requieren los parámetros from y to.']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(
            $this->model->getUserMetrics(
                $period,
                $from,
                $to,
                $this->request->get('country'),
                $this->request->get('sex')
            )
        );
    }
}
