<?php

/**
 * Point d'entrée principal de la plateforme SGC E-Learning
 * Architecture modulaire avec vues indépendantes
 */

// Configuration d'environnement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définition des constantes de base avec chemins absolus
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('THEME_PATH', BASE_PATH . '/theme');
define('DATABASE_PATH', BASE_PATH . '/database');

/**
 * Génération sécurisée de l'URL de base
 * Protection contre Host Header Injection
 */
function generateSecureBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    
    if (!$host) {
        $host = 'localhost'; // Fallback sécurisé
    }
    
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = ($scriptDir === '/') ? '' : $scriptDir;
    
    return $protocol . '://' . $host . $scriptDir;
}

$baseUrl = generateSecureBaseUrl();

// Constantes URL pour les assets frontend
define('WEB_ROOT', $baseUrl);
define('ASSETS_URL', WEB_ROOT . '/assets');
define('THEME_URL', WEB_ROOT . '/theme');
define('CSS_URL', THEME_URL . '/css');
define('JS_URL', THEME_URL . '/js');
define('IMG_URL', ASSETS_URL . '/images');

// Chargement de l'autoloader et du conteneur
require_once CORE_PATH . '/Autoloader.php';
require_once CORE_PATH . '/Container.php';

$autoloader = new SGC\Core\Autoloader();
$autoloader->register();

$container = new SGC\Core\Container();

// Enregistrement des services dans le conteneur
$container->singleton(SGC\Core\Config::class);
$container->singleton(SGC\Core\Database::class, function ($c) {
    return new SGC\Core\Database($c->make(SGC\Core\Config::class));
});
$container->singleton(SGC\Core\Auth::class, function ($c) {
    return new SGC\Core\Auth($c->make(SGC\Core\Database::class));
});
$container->singleton(SGC\Core\Theme::class);
$container->singleton(SGC\Core\Router::class, function ($c) {
    return new SGC\Core\Router(
        $c->make(SGC\Core\Auth::class),
        $c->make(SGC\Core\Config::class),
        $c
    );
});
$container->singleton(SGC\Core\Application::class, function ($c) {
    return new SGC\Core\Application(
        $c->make(SGC\Core\Router::class),
        $c->make(SGC\Core\Database::class),
        $c->make(SGC\Core\Config::class)
    );
});

// Démarrage sécurisé de l'application
try {
    // Résolution et exécution de l'application depuis le conteneur
    $app = $container->make(SGC\Core\Application::class);
    $app->run();
    
} catch (Exception $e) {
    // Gestion d'erreur globale sécurisée
    error_log("SGC Application Fatal Error: " . $e->getMessage());
    
    http_response_code(500);
    echo "<h1>Erreur du serveur</h1>";
    echo "<p>Une erreur inattendue s'est produite. Veuillez contacter l'administrateur.</p>";
    
    // Affichage des détails seulement en mode développement
    // Note: La config n'est peut-être pas chargée ici, on utilise une constante.
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<details><summary>Détails techniques</summary>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</details>";
    }
}
?>