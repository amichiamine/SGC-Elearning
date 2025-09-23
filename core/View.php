<?php
namespace Core;

/**
 * Classe de base pour toutes les vues
 * Fournit les fonctionnalités communes
 */
abstract class View
{
    protected $database;
    protected $auth;
    protected $config;
    protected $theme;

    public function __construct()
    {
        global $app;
        $this->database = $app->getDatabase();
        $this->auth = $app->getAuth();
        $this->config = $app->getConfig();
        $this->theme = $app->getTheme();
    }

    protected function render($template, $data = [])
    {
        extract($data);
        
        ob_start();
        include $template;
        $content = ob_get_clean();
        
        echo $content;
    }

    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>