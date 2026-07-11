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

        $nivelJugador = $this->obtenerNivelJugador($_SESSION['puntaje']);
        $categoria = $this->model->getCategoria($categoriaId);

        if (isset($_SESSION['pregunta_actual']) && $_SESSION['pregunta_actual']['categoria'] == $categoriaId) {
            $pregunta = $this->model->getPreguntaPorId($_SESSION['pregunta_actual']['id']);
        } else {
        $pregunta = $this->model->getPreguntaRandom($categoriaId, $nivelJugador);
            if ($pregunta) {$_SESSION['pregunta_actual'] =
                ['id' => $pregunta['id'],
                'categoria' => $categoriaId,
                'inicio' => microtime(true)];
            }
        }

        if (!$pregunta || !$categoria) {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            return;
        }

        $transcurrido = microtime(true) - $_SESSION['pregunta_actual']['inicio'];
        $tiempoRestante = max(0, ceil(10 - $transcurrido));

        if ($tiempoRestante <= 0) {
            $this->timeout();
            return;
        }

        $opciones = $this->model->getOpciones($pregunta['id']);

        $data = [
            'titulo'          => 'Preguntados Mundial - Pregunta',
            'cssExtra' => $this->getBaseUrl() . '/public/css/pregunta.css',
            'showAppHeader'   => true,
            'headerVariant'   => 'pregunta',
            'showBackToLobby' => true,
            'backToLobbyUrl'  => $this->getBaseUrl() . '/lobby/ver',
            'showCategoryBadge' => false,
            'headerCategoryName' => $categoria['nombre'],
            'headerCategoryColor' => $categoria['color'],
            'headerCategoryContrastColor' => $this->getContrastingColor($categoria['color']),
            'headerCategoryIcon' => $this->obtenerIcono($categoria['nombre']),
            'showQuestionMeta' => true,
            'colorCategoria'  => $categoria['color'],
            'nombreCategoria' => $categoria['nombre'],
            'iconoCategoria'  => $this->obtenerIcono($categoria['nombre']),
            'textoPregunta'   => $pregunta['texto'],
            'preguntaTexto'   => $pregunta['texto'],
            'preguntaId'      => $pregunta['id'],
            'categoriaId'     => $categoriaId,
            'puntaje'         => $_SESSION['puntaje'],
            'tiempoRestante'  => $tiempoRestante,
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
        $this->model->actualizarEstadisticasPregunta($preguntaId, $esCorrecta);
        $correctaId = $this->model->getOpcionCorrectaId($preguntaId);

        if ($esCorrecta) {
        unset($_SESSION['pregunta_actual']);
                  $_SESSION['puntaje']++;

                echo json_encode([
                    'correcta' => true,
                    'correctaId' => $correctaId,
                    'puntaje' => $_SESSION['puntaje'],
                    'redirect' => $this->getBaseUrl() . '/lobby/jugar'
                ]);
            } else {
            unset($_SESSION['pregunta_actual']);

            $puntajeFinal = $_SESSION['puntaje'];

            $this->model->registrarPartida(
                $_SESSION['usuario_id'],
                $puntajeFinal
            );

            $_SESSION['puntaje'] = 0;
            $_SESSION['partida_terminada'] = true;
            $_SESSION['puntaje_final'] = $puntajeFinal;
            $_SESSION['respuesta_correcta'] =
                $this->model->getRespuestaCorrecta($preguntaId);

            echo json_encode([
                'correcta' => false,
                'correctaId' => $correctaId,
                'puntaje' => $puntajeFinal,
                'redirect' => $this->getBaseUrl() . '/lobby/ver'
            ]);

            return;
        }
            }

    public function timeout()
    {
        unset($_SESSION['pregunta_actual']);

        $puntajeFinal = $_SESSION['puntaje'] ?? 0;

        $this->model->registrarPartida(
            $_SESSION['usuario_id'],
            $puntajeFinal
        );

        $_SESSION['puntaje'] = 0;

        $data = [
            'titulo' => 'Partida terminada',
            'baseUrl' => $this->getBaseUrl(),
            'cssExtra' => $this->getBaseUrl() . '/public/css/gameOver.css',
            'showAppHeader' => true,
            'headerVariant' => 'lobby',
            'showBackToLobby' => true,
            'backToLobbyUrl' => $this->getBaseUrl() . '/lobby/ver',
            'error' => 'Se terminó el tiempo.',
            'puntajeFinal' => $puntajeFinal
        ];

        echo $this->renderer->render('gameOver', $data);
    }
    public function proponer()
    {
        Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);

        $data = [
        'titulo' => 'Proponer pregunta',
        'baseUrl' => $this->getBaseUrl(),
        'cssExtra' => $this->getBaseUrl() . '/public/css/lobby.css',
        'showAppHeader' => true,
        'headerVariant' => 'lobby',
        'showBackToLobby' => true,
        'backToLobbyUrl' => $this->getBaseUrl() . '/lobby/ver',
        'categorias' => $this->model->getCategorias(),
        'mensaje' => $_SESSION['mensaje_pregunta'] ?? null
    ];
        unset($_SESSION['mensaje_pregunta']);

        echo $this->renderer->render("proponerPregunta", $data);
    }

    public function guardarPropuesta()
    {
        Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);

        $texto = trim($_POST['texto'] ?? '');
        $categoriaId = $_POST['categoria_id'] ?? null;

        $opciones = [
            trim($_POST['opcion_1'] ?? ''),
            trim($_POST['opcion_2'] ?? ''),
            trim($_POST['opcion_3'] ?? ''),
            trim($_POST['opcion_4'] ?? '')
        ];

        $correctaIndex = isset($_POST['correcta']) ? (int) $_POST['correcta'] : null;

        if ($texto === '' || !$categoriaId || in_array('', $opciones, true) || $correctaIndex === null) {
            $_SESSION['mensaje_pregunta'] = 'Completá todos los campos antes de enviar.';
            header('Location: ' . $this->getBaseUrl() . '/pregunta/proponer');
            return;
        }

        $this->model->crearPreguntaSugerida($texto, $categoriaId, $opciones, $correctaIndex);

        $_SESSION['mensaje_pregunta'] = '¡Pregunta enviada! Quedó pendiente de aprobación.';
        header('Location: ' . $this->getBaseUrl() . '/pregunta/proponer');
    }

    private function obtenerNivelJugador($puntaje)
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

    private function getContrastingColor($hexColor)
    {
        $hexColor = ltrim(trim((string) $hexColor), '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hexColor)) {
            return '#F8E7A0';
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $luminance = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        return $luminance > 160 ? '#1F2937' : '#F8E7A0';
    }
    public function reportar()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: " . $this->getBaseUrl() . "/usuario/login");
            return;
        }

        $preguntaId = $_POST['pregunta_id'] ?? null;
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$preguntaId) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "Pregunta inválida."
            ]);
            return;
        }

        $this->model->reportarPregunta(
            $preguntaId,
            $_SESSION['usuario_id'],
            $motivo
        );

        echo json_encode([
            "ok" => true,
            "mensaje" => "La pregunta fue reportada correctamente."
        ]);
    }

}
