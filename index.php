<?php
/**
 * Point d'entrée principal de la plateforme e-learning
 * Architecture modulaire avec vues indépendantes
 */

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définition des constantes de base (chemins relatifs uniquement)
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('THEME_PATH', BASE_PATH . '/theme');
define('DATABASE_PATH', BASE_PATH . '/database');

// Constantes URL pour les assets frontend
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . $host . $scriptName;

define('WEB_ROOT', $baseUrl);
define('ASSETS_URL', WEB_ROOT . '/assets');
define('THEME_URL', WEB_ROOT . '/theme');

// Chargement de l'autoloader
require_once CORE_PATH . '/Autoloader.php';
$autoloader = new Core\Autoloader();
$autoloader->register();

// Initialisation de l'application
$app = new Core\Application();

// Rendre l'app accessible globalement pour les vues
$GLOBALS['app'] = $app;

$app->run();
?>