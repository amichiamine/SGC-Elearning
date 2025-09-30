<?php

/**
 * Point d'entrée principal de la plateforme SGC E-Learning
 */

// --- Initialisation de l'application via le bootstrap ---
$container = require_once __DIR__ . '/bootstrap.php';

// --- Démarrage de l'application ---
try {
    /** @var SGC\Core\Application $app */
    $app = $container->make(SGC\Core\Application::class);
    $app->run();
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo "<h1>Erreur du serveur</h1><p>Une erreur inattendue s'est produite.</p>";
}