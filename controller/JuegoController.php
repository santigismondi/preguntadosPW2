<?php

class JuegoController {

    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request) {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function jugar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/preguntadosPW2-main/index.php?controller=usuario&method=login');
            return;
        }
        $categoriaId = $this->request->get('categoria_id');
        if ($categoriaId) {
            $_SESSION['categoria_actual'] = $categoriaId;
            $_SESSION['preguntas_respondidas'] = [];
            $_SESSION['puntaje_partida'] = 0;
        }

        $idCategoria = $_SESSION['categoria_actual'] ?? null;
        if (!$idCategoria) {
            Redirect::to('/preguntadosPW2-main/index.php?controller=lobby&method=ver');
            return;
        }

        $pregunta = $this->model->getPreguntaAleatoria($idCategoria, $_SESSION['preguntas_respondidas']);

        if (!$pregunta) {
            $data = [
                'mensaje' => '¡Completaste todas las preguntas de esta categoría!',
                'puntaje' => $_SESSION['puntaje_partida']
            ];
            echo $this->renderer->render("finPartida", $data);
            return;
        }
        $opciones = $this->model->getOpcionesPorPregunta($pregunta['id']);

        $data = [
            'puntaje' => $_SESSION['puntaje_partida'],
            'pregunta' => $pregunta,
            'opciones' => $opciones
        ];

        echo $this->renderer->render("juego", $data);
    }

    public function responder()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $preguntaId = $this->request->post('pregunta_id');
        $opcionId = $this->request->post('opcion_id');

        $opcion = $this->model->getOpcionPorId($opcionId);

        if ($opcion && $opcion['es_correcta'] == 1) {
            // Acierto: sumamos punto y agregamos la pregunta al historial para no repetirla
            $_SESSION['puntaje_partida']++;
            $_SESSION['preguntas_respondidas'][] = $preguntaId;

            Redirect::to('/preguntadosPW2-main/index.php?controller=juego&method=jugar');
        } else {
            $data = [
                'puntajeFinal' => $_SESSION['puntaje_partida']
            ];
            unset($_SESSION['categoria_actual']);
            unset($_SESSION['preguntas_respondidas']);

            echo $this->renderer->render("gameOver", $data);
            }
        }
}
