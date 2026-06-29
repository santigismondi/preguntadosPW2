<?php

class PreguntaController
{
    private $renderer;
    private $model;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function ver()
    {
        $categoriaId = isset($_GET['categoria_id']) ? $_GET['categoria_id'] : null;

        if (!$categoriaId) {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            return;
        }

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }

        $puntaje = $_SESSION['puntaje'];
        $dificultad = $this->obtenerDificultad($puntaje);

        $categoria = $this->model->getCategoria($categoriaId);
        $pregunta = $this->model->getPreguntaRandom($categoriaId, $dificultad);

        if (!$pregunta || !$categoria) {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            return;
        }

        $opciones = $this->model->getOpciones($pregunta['id']);

        $data = [
            'titulo'          => 'Preguntados Mundial - Pregunta',
            'cssExtra'        => $this->getBaseUrl() . '/public/css/pregunta.css',
            'colorCategoria'  => $categoria['color'],
            'nombreCategoria' => $categoria['nombre'],
            'iconoCategoria'  => $this->obtenerIcono($categoria['nombre']),
            'textoPregunta'   => $pregunta['texto'],
            'preguntaId'      => $pregunta['id'],
            'categoriaId'     => $categoriaId,
            'puntaje'         => $_SESSION['puntaje'],
            'opciones'        => $opciones
        ];

        echo $this->renderer->render("pregunta", $data);
    }

    public function verificar()
    {
        header('Content-Type: application/json');

        $preguntaId = isset($_POST['pregunta_id']) ? $_POST['pregunta_id'] : null;
        $opcionId = isset($_POST['opcion_id']) ? $_POST['opcion_id'] : null;
        $categoriaId = isset($_POST['categoria_id']) ? $_POST['categoria_id'] : null;

        if (!$preguntaId || !$opcionId || !$categoriaId) {
            echo json_encode(['error' => true]);
            return;
        }

        $esCorrecta = $this->model->esRespuestaCorrecta($opcionId);
        $correctaId = $this->model->getOpcionCorrectaId($preguntaId);

        if ($esCorrecta) {
            $_SESSION['puntaje']++;
            echo json_encode([
                'correcta' => true,
                'correctaId' => $correctaId,
                'puntaje' => $_SESSION['puntaje'],
                'redirect' => $this->getBaseUrl() . '/lobby/ver'
            ]);
        } else {
            $puntajeFinal = $_SESSION['puntaje'];
            $this->model->registrarPartida(
                $_SESSION['usuario_id'],
                $puntajeFinal
            );
            $_SESSION['puntaje'] = 0;
            $_SESSION['partida_terminada'] = true;
            $_SESSION['puntaje_final'] = $puntajeFinal;
            $_SESSION['respuesta_correcta'] = $this->model->getRespuestaCorrecta($preguntaId);

            echo json_encode([
                'correcta' => false,
                'correctaId' => $correctaId,
                'puntaje' => $puntajeFinal,
                'redirect' => $this->getBaseUrl() . '/lobby/ver'
            ]);
        }
    }

    public function timeout()
    {
        $puntajeFinal = $_SESSION['puntaje'] ?? 0;

        $this->model->registrarPartida(
            $_SESSION['usuario_id'],
            $puntajeFinal
        );

        $_SESSION['puntaje'] = 0;

        $data = [
            'error' => '¡Se terminó el tiempo!',
            'puntajeFinal' => $puntajeFinal
        ];

        echo $this->renderer->render('gameOver', $data);
    }

    private function obtenerDificultad($puntaje)
    {
        if ($puntaje < 5) {
            return 0;
        }

        if ($puntaje < 10) {
            return 1;
        }

        return 2;
    }

    private function obtenerIcono($nombre)
    {

        $nombre = strtolower($nombre);
        $baseUrl = $this->getBaseUrl();

        if (strpos($nombre, 'grupo') !== false || strpos($nombre, 'fase') !== false) {
            return $baseUrl . "/public/img/iconos/fase.png";
        }
        if (strpos($nombre, 'estadio') !== false) {
            return $baseUrl . "/public/img/iconos/estadios.png";
        }
        if (strpos($nombre, 'jugador') !== false) {
            return $baseUrl . "/public/img/iconos/jugadores.png";
        }
        if (strpos($nombre, 'seleccion') !== false || strpos($nombre, 'selección') !== false) {
            return $baseUrl . "/public/img/iconos/selecciones.png";
        }
        if (strpos($nombre, 'historia') !== false) {
            return $baseUrl . "/public/img/iconos/historia.png";
        }
        if (strpos($nombre, 'record') !== false || strpos($nombre, 'estad') !== false) {
            return $baseUrl . "/public/img/iconos/estadisticas.png";
        }

        return $baseUrl . "/public/img/iconos/fase.png";
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }
}
