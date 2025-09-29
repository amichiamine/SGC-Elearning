<?php
namespace SGC\Core;

/**
 * Classe principale de l'application SGC E-Learning
 * Gère l'initialisation et l'exécution sécurisée
 */
class Application
{
    private Router $router;
    private Database $database;
    private Config $config;

    public function __construct(Router $router, Database $database, Config $config)
    {
        $this->router = $router;
        $this->database = $database;
        $this->config = $config;
    }

    public function run()
    {
        try {
            // La base de données est maintenant initialisée lors de sa création via le conteneur.
            
            // Démarrage de la session, géré maintenant par la classe Auth.
            // Le service Auth est démarré par le conteneur, qui initialise la session.
            
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