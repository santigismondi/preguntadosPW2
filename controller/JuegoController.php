<?php
//comentario de prueba
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
            Redirect::to($this->getBaseUrl() . '/usuario/login');
            return;
        }
        $categoriaId = $this->request->get('categoria_id');
        if ($categoriaId) {
            $_SESSION['categoria_actual'] = $categoriaId;
            $_SESSION['preguntas_respondidas_racha'] = [];
            $_SESSION['respuestas_correctas_racha'] = 0;
        }

        $idCategoria = $_SESSION['categoria_actual'] ?? null;
        if (!$idCategoria) {
            Redirect::to($this->getBaseUrl() . '/usuario/login');
            return;
        }

        $pregunta = $this->model->getPreguntaAleatoria($idCategoria, $_SESSION['preguntas_respondidas_racha']);

        if (!$pregunta) {
            $data = [
                'mensaje' => 'No hay suficientes preguntas en esta categoría.',
                'puntaje' => $_SESSION['puntaje_partida'] ?? 0
            ];
            unset($_SESSION['categoria_actual']);
            echo $this->renderer->render("gameOver", $data);
            return;
            }

        $_SESSION['pregunta_cargada_at'] = time();

        $opciones = $this->model->getOpcionesPorPregunta($pregunta['id']);

        $data = [
            'puntaje' => $_SESSION['puntaje_partida'] ?? 0,
            'racha_actual' => $_SESSION['respuestas_correctas_racha'],
            'pregunta' => $pregunta,
            'opciones' => $opciones
        ];

        echo $this->renderer->render("juego", $data);
    }

    public function responder()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $tiempoActual = time();
        $tiempoCargado = $_SESSION['pregunta_cargada_at'] ?? 0;
        $segundosTranscurridos = $tiempoActual - $tiempoCargado;

        $timeoutPost = $this->request->post('timeout');

        if ($timeoutPost === 'true' || ($tiempoCargado > 0 && $segundosTranscurridos > 11)) {
            $this->procesarPerdida("¡Te quedaste sin tiempo! Tenés 10 segundos por pregunta.");
            exit();
        }

        $preguntaId = $this->request->post('pregunta_id');
        $opcionId = $this->request->post('opcion_id');

        $opcion = $this->model->getOpcionPorId($opcionId);

        if ($opcion && $opcion['es_correcta'] == 1) {
            $_SESSION['respuestas_correctas_racha']++;
            $_SESSION['preguntas_respondidas_racha'][] = $preguntaId;

            if ($_SESSION['respuestas_correctas_racha'] >= 5) {
                if (!isset($_SESSION['puntaje_partida'])) {
                    $_SESSION['puntaje_partida'] = 0;
                }
                $_SESSION['puntaje_partida']++;
                unset($_SESSION['categoria_actual']);
                unset($_SESSION['preguntas_respondidas_racha']);
                unset($_SESSION['respuestas_correctas_racha']);
                Redirect::to($this->getBaseUrl() . '/lobby/ver');
                exit();
            }

            Redirect::to($this->getBaseUrl() . '/juego/jugar');
            exit();
        } else {
            $this->procesarPerdida("¡Respuesta incorrecta! Has perdido la racha de la categoría.");
            exit();
        }
    }

    private function procesarPerdida($mensajeError)
    {
        $puntajeFinal = $_SESSION['puntaje_partida'] ?? 0;
        $this->model->registrarPartida($_SESSION['usuario_id'], $puntajeFinal);

        $data = [
            'error' => $mensajeError,
            'puntajeFinal' => $puntajeFinal
        ];

        unset($_SESSION['categoria_actual']);
        unset($_SESSION['preguntas_respondidas_racha']);
        unset($_SESSION['respuestas_correctas_racha']);
        unset($_SESSION['pregunta_cargada_at']);
        $_SESSION['puntaje_partida'] = 0;

        echo $this->renderer->render("gameOver", $data);
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }
}
