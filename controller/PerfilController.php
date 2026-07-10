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

        $usuarioIdSolicitado = $this->request->get('id');
        $usuarioIdSesion = $_SESSION['usuario_id'] ?? null;

        if ($usuarioIdSesion === null) {
            Redirect::to(
                $this->getBaseUrl() . '/usuario/login'
            );
            return;
        }

        $usuarioId = $usuarioIdSolicitado ?: $usuarioIdSesion;

        $esPerfilPropio =
            (int) $usuarioId === (int) $usuarioIdSesion;

        $usuario = $this->model->getUsuario($usuarioId);

        if ($usuario === null) {
            Redirect::to(
                $this->getBaseUrl() . '/lobby/ver'
            );
            return;
        }

        $usuario['foto_perfil'] =
            !empty($usuario['foto_perfil'])
                ? $usuario['foto_perfil']
                : 'default.png';

        $usuario['localidad'] =
            $this->resolverLocalidad(
                $usuario['coordenadas_ciudad'] ?? null
            );

        $puntajeMaximo =
            $this->model->getPuntajeMaximo($usuarioId);

        $cantidadPartidas =
            $this->model->getCantidadPartidas($usuarioId);

        $partidas =
            $this->model->getPartidas($usuarioId);

        $urlPerfil =
            $this->getUrlPerfil($usuario['id']);

        $data = [
            'titulo' =>
                $esPerfilPropio
                    ? 'Preguntados Mundial - Mi perfil'
                    : 'Preguntados Mundial - Perfil de jugador',

            'tituloPerfil' =>
                $esPerfilPropio
                    ? 'Mi perfil'
                    : 'Perfil de jugador',

            'showAppHeader' => true,
            'headerVariant' => 'perfil',

            'headerSurfaceStyle' =>
                'background: linear-gradient(
                    135deg,
                    rgba(18, 32, 24, 0.94),
                    rgba(15, 24, 38, 0.92)
                );',

            'showPageTitle' => true,

            'headerPageTitle' =>
                $esPerfilPropio
                    ? 'Mi perfil'
                    : 'Perfil de jugador',

            'showBackToLobby' => true,
            'backToLobbyUrl' =>
                $this->getBaseUrl() . '/lobby/ver',

            'showLogoutButton' => $esPerfilPropio,
            'logoutUrl' =>
                $this->getBaseUrl() . '/usuario/logout',

            'showAdminButton' =>
                $esPerfilPropio && $this->isAdmin(),

            'adminUrl' =>
                $this->getBaseUrl() . '/admin/dashboard',

            'esPerfilPropio' => $esPerfilPropio,

            'usuario' => $usuario,
            'puntajeMaximo' => $puntajeMaximo,
            'cantidadPartidas' => $cantidadPartidas,
            'partidas' => $partidas,

            'qrUrl' =>
                'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='
                . urlencode($urlPerfil),

            'cssExtra' =>
                $this->getBaseUrl() . '/public/css/perfil.css',

            'baseUrl' => $this->getBaseUrl()
        ];

        echo $this->renderer->render(
            'perfil',
            $data
        );
    }

    private function getUrlPerfil($usuarioId)
    {
        $protocolo =
            isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
                ? 'https'
                : 'http';

        $host =
            $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocolo
            . '://'
            . $host
            . $this->getBaseUrl()
            . '/perfil/ver?id='
            . urlencode((string) $usuarioId);
    }

    private function resolverLocalidad($coordenadas)
    {
        if (empty($coordenadas)) {
            return 'Ubicación no disponible';
        }

        $partes = explode(',', $coordenadas);

        if (count($partes) !== 2) {
            return 'Ubicación no disponible';
        }

        $latitud = trim($partes[0]);
        $longitud = trim($partes[1]);

        if (
            !is_numeric($latitud)
            || !is_numeric($longitud)
        ) {
            return 'Ubicación no disponible';
        }

        $url =
            'https://nominatim.openstreetmap.org/reverse'
            . '?format=jsonv2'
            . '&lat=' . urlencode($latitud)
            . '&lon=' . urlencode($longitud);

        $contexto = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'header' =>
                    "User-Agent: PreguntadosMundial/1.0\r\n"
                    . "Accept-Language: es\r\n"
            ]
        ]);

        $respuesta = @file_get_contents(
            $url,
            false,
            $contexto
        );

        if ($respuesta === false) {
            return 'Ubicación no disponible';
        }

        $data = json_decode(
            $respuesta,
            true
        );

        if (!is_array($data)) {
            return 'Ubicación no disponible';
        }

        return $data['address']['city']
            ?? $data['address']['town']
            ?? $data['address']['village']
            ?? $data['address']['municipality']
            ?? $data['address']['state']
            ?? 'Ubicación no disponible';
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get(
            'baseUrl',
            ''
        );
    }

    private function isAdmin()
    {
        return isset($_SESSION['rol'])
            && strtolower(
                (string) $_SESSION['rol']
            ) === 'administrador';
    }
}