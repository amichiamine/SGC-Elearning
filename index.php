<?php

/**
 * Point d'entrée principal de la plateforme SGC E-Learning
 */

// --- Configuration de l'environnement ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Définition des constantes de chemin ---
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('THEME_PATH', BASE_PATH . '/theme');
define('DATABASE_PATH', BASE_PATH . '/database');

// --- Chargement des fichiers de base ---
require_once CORE_PATH . '/Autoloader.php';
require_once CORE_PATH . '/Container.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Model.php';

// --- Initialisation de l'autoloader ---
$autoloader = new SGC\Core\Autoloader();
$autoloader->register();

// --- Initialisation du conteneur DI ---
$container = new SGC\Core\Container();

// --- Enregistrement des services dans le conteneur ---
$container->singleton(SGC\Core\Config::class);

$container->singleton(SGC\Core\Database::class, function ($c) {
    return new SGC\Core\Database($c->make(SGC\Core\Config::class));
});

$container->singleton(SGC\Core\Auth::class, function ($c) {
    return new SGC\Core\Auth($c->make(SGC\Core\Database::class));
});

$container->singleton(SGC\Core\Theme::class);

$container->singleton(SGC\Core\View::class, function ($c) {
    return new SGC\Core\View($c->make(SGC\Core\Theme::class));
});

$container->singleton(SGC\Core\Router::class, function ($c) {
    return new SGC\Core\Router(
        $c->make(SGC\Core\Auth::class),
        $c->make(SGC\Core\Config::class),
        $c // Le routeur a besoin du conteneur pour instancier les contrôleurs
    );
});

$container->singleton(SGC\Core\Application::class, function ($c) {
    return new SGC\Core\Application(
        $c->make(SGC\Core\Router::class),
        $c->make(SGC\Core\Config::class)
    );
});


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