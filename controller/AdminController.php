<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            exit;
        }

        Access::allow('admin.dashboard');

        echo $this->renderer->render('admin', [
            'titulo' => 'Preguntados Mundial - Panel de administración',
            'showAppHeader' => true,
            'headerVariant' => 'admin',
            'showBackToLobby' => true,
            'backToLobbyUrl' => (new ConfigParser())->get('baseUrl', '') . '/admin/ver',
            'showLogoutButton' => true,
            'logoutUrl' => (new ConfigParser())->get('baseUrl', '') . '/usuario/logout',
            'showPageTitle' => true,
            'headerPageTitle' => 'Panel de administración',
        ]);
    }

    public function ver()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
            header('Location: ' . $this->getBaseUrl() . '/lobby/ver');
            exit;
        }

        $periodo = $_GET['periodo'] ?? 'mes';

        $metricas = $this->model->getMetricas($periodo);

        // Obtenemos los datos crudos del modelo
        $datosRaw = $this->model->getUsuariosPorPais($periodo);

        // Procesamos
        $usuariosPorPais = [];
        foreach ($datosRaw as $fila) {
            $pais = $this->resolverPais($fila['pais']); // Aquí llamamos al resolver

            if (!isset($usuariosPorPais[$pais])) {
                $usuariosPorPais[$pais] = 0;
            }
            $usuariosPorPais[$pais] += $fila['cantidad'];
        }

        // Convertimos a formato para la vista
        $usuariosPorPaisFormateado = [];
        foreach ($usuariosPorPais as $nombrePais => $cantidad) {
            $usuariosPorPaisFormateado[] = [
                'pais' => $nombrePais,
                'cantidad' => $cantidad
            ];
        }

        $data = array_merge($metricas, [
            'titulo' => 'Administrador',
            'cssExtra' => $this->getBaseUrl() . '/public/css/admin.css',
            'baseUrl' => $this->getBaseUrl(),
            'showAppHeader' => true,
            'headerVariant' => 'admin', // Usamos el estilo del admin o podés crear uno nuevo
            'showLogoutButton' => true,
            'logoutUrl' => $this->getBaseUrl() . '/usuario/logout',
            'showPageTitle' => true,
            'headerPageTitle' => 'Panel de Administrador',

            'periodoDia' => $periodo === 'dia',
            'periodoSemana' => $periodo === 'semana',
            'periodoMes' => $periodo === 'mes',
            'periodoAnio' => $periodo === 'anio',

            'correctasPorUsuario' => $this->model->getCorrectasPorUsuario($periodo),
            'usuariosPorPais' => $usuariosPorPaisFormateado,
            'usuariosPorSexo' => $this->model->getUsuariosPorSexo($periodo),
            'usuariosPorEdad' => $this->model->getUsuariosPorEdad($periodo)
        ]);

        $this->renderer->render('admin', $data);
    }

    private function resolverPais($coordenadas)
    {
        if (empty($coordenadas) || $coordenadas === '0,0') {
            return 'Sin ubicación';
        }

        $partes = explode(',', $coordenadas);
        if (count($partes) !== 2) return 'Coordenada inválida';

        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=" . trim($partes[0]) . "&lon=" . trim($partes[1]) . "&accept-language=es";

        // Configuramos una petición más robusta
        $contexto = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => "User-Agent: MiAppPreguntadosMundial2026/1.0 (contacto@tuemail.com)\r\n" .
                    "Accept: application/json\r\n"
            ]
        ]);

        $respuesta = @file_get_contents($url, false, $contexto);

        if ($respuesta === false) {
            Log::error("AdminController::resolverPais - Error de conexión con API Nominatim");
            return 'Error API';
        }

        $data = json_decode($respuesta, true);

        // DEBUG: Descomenta la línea de abajo si sigue fallando para ver el JSON en el log
        // Log::info("AdminController::resolverPais - API Respuesta: " . $respuesta);

        // Verificamos si existe la dirección y el país
        return $data['address']['country'] ?? 'Desconocido';
    }

    private function getBaseUrl()
    {
        return $_SERVER['SERVER_NAME'] === 'localhost' ? '/preguntadosPW2' : '';
    }
}
