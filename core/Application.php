<?php

namespace SGC\Core;

/**
 * Classe principale de l'application SGC E-Learning.
 * Gère l'initialisation et l'exécution de la requête.
 */
class Application
{
    private Router $router;
    private Config $config;

    /**
     * Le constructeur reçoit les dépendances injectées par le conteneur.
     */
    public function __construct(Router $router, Config $config)
    {
        $this->router = $router;
        $this->config = $config;
    }

    /**
     * Lance l'application.
     */
    public function run()
    {
        try {
            // Le service Auth, initialisé par le conteneur, gère le démarrage de la session.
            // La connexion à la base de données est également gérée par le conteneur.
            
            // Le routeur traite la requête et appelle le contrôleur approprié.
            $this->router->dispatch();
            
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Gère les erreurs de manière centralisée.
     */
    private function handleError(\Exception $exception)
    {
        error_log("SGC Application Error: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        $debug = $this->config->get('app.debug', false);
        
        if ($debug) {
            http_response_code(500);
            echo "<h1>Erreur de l'application</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>Fichier:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Ligne:</strong> " . $exception->getLine() . "</p>";
            echo "<h2>Trace:</h2>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            http_response_code(500);
            // Dans un cas réel, on utiliserait un template de vue pour la page d'erreur.
            echo "<h1>Erreur du serveur</h1>";
            echo "<p>Une erreur inattendue s'est produite. Veuillez réessayer plus tard.</p>";
        }
    }
}