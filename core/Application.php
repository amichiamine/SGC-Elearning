<?php
namespace Core;

/**
 * Classe principale de l'application SGC E-Learning
 * Gère l'initialisation et l'exécution sécurisée
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
            // Initialisation sécurisée de la base de données
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

    /**
     * Gestion sécurisée des erreurs
     */
    private function handleError($exception)
    {
        // Log sécurisé de l'erreur
        error_log("SGC Application Error: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        $debug = $this->config->get('app.debug', false);
        
        if ($debug) {
            // Mode développement : affichage détaillé
            echo "<h1>Erreur de l'application</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>Fichier:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Ligne:</strong> " . $exception->getLine() . "</p>";
            echo "<h2>Stack Trace:</h2>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            // Mode production : message générique
            http_response_code(500);

            $errorViewPath = VIEWS_PATH . '/errors/500.php';
            if (file_exists($errorViewPath)) {
                include $errorViewPath;
            } else {
                echo "<h1>Erreur du serveur</h1>";
                echo "<p>Une erreur inattendue s'est produite. Veuillez réessayer plus tard.</p>";
            }
        }
    }

    /**
     * Getters sécurisés pour les composants
     */
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

    public function getRouter()
    {
        return $this->router;
    }
}
?>