<?php

class PerfilController
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

    public function ver()
    {
        Access::allow('profile.view');

        $usuarioId = $this->request->get('id') ?: $_SESSION['usuario_id'];

        $usuario = $this->model->getUsuario($usuarioId);

        if ($usuario === null) {
            Redirect::to($this->getBaseUrl() . '/lobby/ver');
            return;
        }

        $usuario['localidad'] = $this->resolverLocalidad($usuario['coordenadas_ciudad']);

        $urlPerfil = $this->getUrlPerfil($usuario['id']);
        
        $usuario['foto_perfil'] = !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'default.png';

        echo $this->renderer->render('perfil', [
            'titulo' => 'Preguntados Mundial - Perfil',
            'showAppHeader' => true,
            'headerVariant' => 'perfil',
            'headerSurfaceStyle' => 'background: radial-gradient(circle at top left, rgba(245, 197, 24, 0.18), transparent 28%), radial-gradient(circle at bottom right, rgba(192, 57, 43, 0.22), transparent 30%), linear-gradient(135deg, rgba(11, 18, 32, 0.96), rgba(27, 38, 58, 0.94) 55%, rgba(56, 25, 33, 0.95));',
            'showPageTitle' => true,
            'headerPageTitle' => 'Mi Perfil',
            'showBackToLobby' => true,
            'backToLobbyUrl' => $this->getBaseUrl() . '/lobby/ver',
            'showLogoutButton' => true,
            'logoutUrl' => $this->getBaseUrl() . '/usuario/logout',
            'showAdminButton' => $this->isAdmin(),
            'adminUrl' => $this->getBaseUrl() . '/admin/dashboard',
            'usuario' => $usuario,
            'puntajeMaximo' => $this->model->getPuntajeMaximo($usuarioId),
            'cantidadPartidas' => $this->model->getCantidadPartidas($usuarioId),
            'partidas' => $this->model->getPartidas($usuarioId),
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($urlPerfil),
            'cssExtra' => $this->getBaseUrl() . '/public/css/perfil.css',
            'baseUrl' => $this->getBaseUrl(),
        ]);
    }

    private function getUrlPerfil($usuarioId)
    {
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocolo . '://' . $host . $this->getBaseUrl() . '/perfil/ver?id=' . $usuarioId;
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl', '');
    }

    private function resolverLocalidad($coordenadas)
    {
        if (empty($coordenadas)) {
            return 'Ubicación no disponible';
        }

        [$lat, $lng] = explode(',', $coordenadas);

        $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
            . urlencode(trim($lat))
            . '&lon=' . urlencode(trim($lng));

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: PreguntadosMundial/1.0\r\n"
            ]
        ]);

        $respuesta = file_get_contents($url, false, $context);
        $data = json_decode($respuesta, true);

        return $data['address']['city']
            ?? $data['address']['town']
            ?? $data['address']['village']
            ?? $data['address']['state']
            ?? 'Ubicación no disponible';
    }

    private function isAdmin()
    {
        return isset($_SESSION['rol']) && strtolower((string) $_SESSION['rol']) === 'administrador';
    }
}
