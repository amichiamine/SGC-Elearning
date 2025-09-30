<?php

/**
 * Fichier de bootstrapping pour l'application SGC E-Learning
 * Initialise l'environnement, l'autoloader, et le conteneur de dépendances.
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
// Lie le conteneur à lui-même pour qu'il puisse s'auto-injecter.
$container->singleton(SGC\Core\Container::class, function () use ($container) {
    return $container;
});
$container->singleton(SGC\Core\Config::class, function () {
    return SGC\Core\Config::getInstance();
});

$container->singleton(SGC\Core\Database::class, function ($c) {
    return SGC\Core\Database::getInstance();
});

$container->singleton(SGC\Core\Auth::class, function ($c) {
    return new SGC\Core\Auth();
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

// Le conteneur est maintenant configuré et prêt à être utilisé.
return $container;