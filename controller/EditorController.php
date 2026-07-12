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
            'titulo' => 'Editor',
            'cssExtra' => $this->getBaseUrl() . '/public/css/editor.css',
            'baseUrl' => $this->getBaseUrl(),
            'showAppHeader' => true,
            'headerVariant' => 'admin', // Usamos el estilo del admin o podés crear uno nuevo
            'showLogoutButton' => true,
            'logoutUrl' => $this->getBaseUrl() . '/usuario/logout',
            'showPageTitle' => true,
            'headerPageTitle' => 'Panel de Editor',
            'preguntas' => $this->model->getPreguntas(),
            'reportes' => $this->model->getReportesPendientes(),
            'propuestas' => $this->model->getPropuestasPendientes()
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
        $urlVolver = $this->getBaseUrl() . '/editor/ver';
        $data = [
            'titulo' => 'Nueva pregunta',
            'cssExtra' => $this->getBaseUrl() . '/public/css/editor.css',
            'showAppHeader' => true,
            'headerVariant' => 'admin', // Usamos el estilo del admin o podés crear uno nuevo
            'showPageTitle' => true,
            'headerPageTitle' => 'Panel de Editor - Nueva pregunta',
            'showBackToLobby' => true,
            'backToLobbyUrl' => $urlVolver,
            'urlVolver' => $urlVolver,
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
                '/editor/nuevaPregunta?error=datos'
            );
            exit;
        }

        foreach ($opciones as $opcion) {
            if (trim($opcion) === '') {
                header(
                    'Location: ' .
                    $this->getBaseUrl() .
                    '/editor/nuevaPregunta?error=opciones'
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
            '/editor/ver?creada=1'
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

    public function procesarPropuesta()
    {
        $this->verificarAccesoEditor();
        header('Content-Type: application/json'); // Respondemos JSON porque es AJAX

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $id = $data['id'] ?? null;
        $accion = $data['accion'] ?? null;

        if (!$id || !in_array($accion, ['aprobar', 'rechazar'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        // Si se aprueba, entra al juego. Si se rechaza, la marcamos como eliminada.
        $nuevoEstado = $accion === 'aprobar' ? 'aprobada' : 'eliminada';

        $this->model->resolverPropuesta($id, $nuevoEstado);

        echo json_encode(['ok' => true]);
    }
}

