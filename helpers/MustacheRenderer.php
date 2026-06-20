<?php

require_once(__DIR__ . '/../vendor/mustache/src/Mustache/Autoloader.php');

class MustacheRenderer {
    private $mustache;
    private $baseUrl; // Nueva propiedad para almacenar la URL base

    // Recibimos $baseUrl como segundo parámetro
    public function __construct($viewsFolder, $baseUrl = "") {
        Mustache_Autoloader::register();

        $this->baseUrl = $baseUrl; // Guardamos el valor

        $this->mustache = new Mustache_Engine([
            'loader'          => new Mustache_Loader_FilesystemLoader($viewsFolder),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($viewsFolder),
        ]);
    }

    public function render($viewName, $data = []) {
        // Inyectamos automáticamente la baseUrl en el array de datos
        // Así estará disponible en todos tus archivos .mustache sin tener que pasarlo a mano
        $data['baseUrl'] = $this->baseUrl;

        $template = $this->mustache->loadTemplate($viewName);
        echo $template->render($data);
    }
}