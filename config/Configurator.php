<?php
class Configurator {
    private $config;

    public function __construct()
    {
        $this->config = (new ConfigParser())->all();
    }

    public function getUsuarioController()
    {
        return new UsuarioController($this->getUsuarioModel(), $this->getRenderer(), new Request());
    }

    public function getLobbyController()
    {
        return new LobbyController($this->getLobbyModel(), $this->getRenderer(), new Request());
    }

    public function getPreguntaController()
    {
        return new PreguntaController($this->getPreguntaModel(), $this->getRenderer());
    }

    private function getLobbyModel()
    {
        return new LobbyModel($this->getDatabase());
    }

    private function getPreguntaModel()
    {
        return new PreguntaModel($this->getDatabase());
    }

    private function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    private function getDatabase()
    {
        return new MyDatabase(
            $this->config['hostname'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );
    }

    private function getRenderer()
    {
        // Detectamos si estamos en XAMPP o en producción
        if ($_SERVER['SERVER_NAME'] === 'localhost') {
            $baseUrl = '/preguntadosPW2'; // La carpeta en tu PC
        } else {
            $baseUrl = ''; // La raíz en InfinityFree
        }

        // Le pasamos el baseUrl como segundo parámetro
        return new MustacheRenderer(__DIR__ . '/../view', $baseUrl);
    }

    public function getRouter()
    {
        return new Router($this, 'usuario', 'login');
    }

    public function getOrDefault($controllerName, $defaultControllerName)
    {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }

        public function getPerfilController()
    {
        return new PerfilController($this->getPerfilModel(), $this->getRenderer(), new Request());
    }

        private function getPerfilModel()
    {
        return new PerfilModel($this->getDatabase());
    }

    public function getAdminController()
    {
        return new AdminController($this->getRenderer());
    }
}
