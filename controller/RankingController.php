<?php
class RankingController {
    private $renderer;
    private $model;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->renderer = $renderer;
        $this->model = $model;
        $this->request = $request;
    }

    public function ver()
    {
        Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);

        $ranking = $this->model->getRanking();
        $posicion = 1;

        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $posicion++;
        }

        echo $this->renderer->render('ranking', [
            'titulo' => 'Ranking global',
            'ranking' => $ranking,
            'baseUrl' => $this->getBaseUrl(),
            'cssExtra' => $this->getBaseUrl() . '/public/css/lobby.css',
            'showAppHeader' => true,
            'headerVariant' => 'lobby',
            'showBackToLobby' => true,
            'backToLobbyUrl' => $this->getBaseUrl() . '/lobby/ver'
        ]);
    }

    private function getBaseUrl()
    {
        return (new ConfigParser())->get('baseUrl','');
    }

}
