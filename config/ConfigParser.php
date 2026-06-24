<?php

class ConfigParser
{
    private $path;
    private $config;

    public function __construct($path = null)
    {
        $this->path = $path ?? __DIR__ . '/config.ini';
    }

    public function all()
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (!is_readable($this->path)) {
            throw new RuntimeException("No se puede leer el archivo de configuración: {$this->path}");
        }

        $config = parse_ini_file($this->path, false, INI_SCANNER_TYPED);

        if ($config === false) {
            throw new RuntimeException("No se pudo parsear el archivo de configuración: {$this->path}");
        }

        $this->config = $config;

        return $this->config;
    }

    public function get($key, $default = null)
    {
        $config = $this->all();

        return array_key_exists($key, $config) ? $config[$key] : $default;
    }
}
