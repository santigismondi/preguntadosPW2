<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
class EditorController
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
        $this->verificarAccesoEditor();

        $data = [
            'titulo' => 'Panel editor',
            'cssExtra' => $this->getBaseUrl() . '/public/css/editor.css',
            'baseUrl' => $this->getBaseUrl(),

            'preguntas' => $this->model->getPreguntas(),
            'reportes' => $this->model->getReportesPendientes(),

            // TODO: hardcodeado, luego incorporar logica
            'propuestas' => [
                [
                    'id' => 1,
                    'usuario' => 'joaco',
                    'pregunta' => '¿Quién convirtió el gol de Argentina en la final del Mundial 2014?',
                    'categoria' => 'Historia del Mundial',
                    'fecha' => '10/07/2026 18:20',
                    'opciones' => [
                        ['texto' => 'Lionel Messi'],
                        ['texto' => 'Gonzalo Higuaín'],
                        ['texto' => 'Ningún jugador argentino', 'correcta' => true],
                        ['texto' => 'Ángel Di María']
                    ]
                ],
                [
                    'id' => 2,
                    'usuario' => 'santi',
                    'pregunta' => '¿Qué selección ganó el Mundial de Francia 1998?',
                    'categoria' => 'Selecciones',
                    'fecha' => '10/07/2026 19:45',
                    'opciones' => [
                        ['texto' => 'Brasil'],
                        ['texto' => 'Francia', 'correcta' => true],
                        ['texto' => 'Italia'],
                        ['texto' => 'Alemania']
                    ]
                ]
            ]
        ];

        $this->renderer->render('editor', $data);
    }

    public function editarPregunta()
    {
        $this->verificarAccesoEditor();

        $id = $_GET['id'] ?? null;

        $data = [
            'titulo' => 'Editar pregunta',
            'cssExtra' => $this->getBaseUrl() . '/public/css/editor.css',
            'pregunta' => $this->model->getPreguntaPorId($id),
            'opciones' => $this->model->getOpcionesPorPregunta($id)
        ];

        $this->renderer->render('editarPregunta', $data);
    }

    public function guardarPregunta()
    {
        $this->verificarAccesoEditor();

        $id = $_POST['id'];
        $texto = $_POST['texto'];
        $opciones = $_POST['opciones'];
        $correcta = $_POST['correcta'];

        $this->model->actualizarPregunta($id, $texto, $opciones, $correcta);

        header('Location: ' . $this->getBaseUrl() . '/editor/ver');
    }

    public function rechazarReporte()
    {
        $this->verificarAccesoEditor();

        $id = $_GET['id'];
        $this->model->rechazarReporte($id);

        header('Location: ' . $this->getBaseUrl() . '/editor/ver');
    }

    public function eliminarPregunta()
    {
        $this->verificarAccesoEditor();

        $id = $_GET['id'];
        $this->model->eliminarPregunta($id);

        header('Location: ' . $this->getBaseUrl() . '/editor/ver');
    }

    private function getBaseUrl()
    {
        return $_SERVER['SERVER_NAME'] === 'localhost' ? '/preguntadosPW2' : '';
    }
    public function nuevaPregunta()
    {
        $this->verificarAccesoEditor();

        $data = [
            'titulo' => 'Nueva pregunta',
            'cssExtra' => $this->getBaseUrl() . '/public/css/editor.css',
            'baseUrl' => $this->getBaseUrl(),
            'categorias' => $this->model->getCategorias()
        ];

        $this->renderer->render('nuevaPregunta', $data);
    }


    public function crearPregunta()
    {
        $this->verificarAccesoEditor();

        $texto = trim($_POST['texto'] ?? '');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $opciones = $_POST['opciones'] ?? [];
        $correcta = isset($_POST['correcta'])
            ? (int)$_POST['correcta']
            : -1;

        if (
            $texto === '' ||
            $categoriaId <= 0 ||
            count($opciones) !== 4 ||
            $correcta < 0 ||
            $correcta > 3
        ) {
            header(
                'Location: ' .
                $this->getBaseUrl() .
                '/index.php?controller=editor&method=nuevaPregunta&error=datos'
            );
            exit;
        }

        foreach ($opciones as $opcion) {
            if (trim($opcion) === '') {
                header(
                    'Location: ' .
                    $this->getBaseUrl() .
                    '/index.php?controller=editor&method=nuevaPregunta&error=opciones'
                );
                exit;
            }
        }

        $this->model->crearPregunta(
            $texto,
            $categoriaId,
            $opciones,
            $correcta
        );

        header(
            'Location: ' .
            $this->getBaseUrl() .
            '/index.php?controller=editor&method=ver&creada=1'
        );
        exit;
    }

    private function verificarAccesoEditor()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Editor') {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            exit;
        }
    }
}

