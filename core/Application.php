<?php
namespace Core;

/**
 * Classe principale de l'application
 * Gère le routage et l'initialisation
 */
class Application
{
    private $router;
    private $database;
    private $auth;
    private $config;
    private $theme;

    public function __construct()
    {
        $this->config = new Config();
        $this->database = new Database($this->config);
        $this->auth = new Auth($this->database);
        $this->theme = new Theme();
        $this->router = new Router($this->auth);
    }

    public function run()
    {
        try {
            // Initialisation de la base de données
            $this->database->initialize();
            
            // Démarrage de la session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Traitement de la requête
            $this->router->dispatch();
            
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleError($exception)
    {
        http_response_code(500);
        echo "Erreur: " . $exception->getMessage();
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getTheme()
    {
        return $this->theme;
    }
}
?>