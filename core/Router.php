<?php
namespace Core;

/**
 * Système de routage modulaire
 * Gère les routes vers les vues indépendantes avec chemins absolus
 */
class Router
{
    private $routes = [];
    private $auth;

    public function __construct($auth)
    {
        $this->auth = $auth;
        $this->loadRoutes();
    }

    private function loadRoutes()
    {
        // Routes par défaut
        $this->routes = [
            '' => ['view' => 'Home', 'method' => 'index', 'auth' => false],
            'home' => ['view' => 'Home', 'method' => 'index', 'auth' => false],
            'login' => ['view' => 'Auth\\Login', 'method' => 'index', 'auth' => false],
            'logout' => ['view' => 'Auth\\Login', 'method' => 'logout', 'auth' => true],
            'admin' => ['view' => 'Admin\\Dashboard', 'method' => 'index', 'auth' => true, 'role' => 'admin'],
        ];
    }

    public function dispatch()
    {
        $uri = $this->getCurrentUri();
        $route = $this->findRoute($uri);

        if (!$route) {
            $this->handleNotFound();
            return;
        }

        // Vérification d'authentification
        if ($route['auth'] && !$this->auth->isAuthenticated()) {
            $this->redirect('login');
            return;
        }

        // Vérification des rôles
        if (isset($route['role']) && !$this->auth->hasRole($route['role'])) {
            $this->handleUnauthorized();
            return;
        }

        $this->loadView($route);
    }

    private function getCurrentUri()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        return trim($uri, '/');
    }

    private function findRoute($uri)
    {
        return $this->routes[$uri] ?? null;
    }

    private function loadView($route)
    {
        $viewParts = explode('\\', $route['view']);
        $viewName = end($viewParts);
        $viewClass = "Views\\{$route['view']}\\{$viewName}Controller";
        $method = $route['method'];
        
        // Tentative de chargement du fichier contrôleur avec chemins absolus
        $possiblePaths = [
            VIEWS_PATH . '/' . str_replace('\\', '/', $route['view']) . '/' . $viewName . 'Controller.php',
            VIEWS_PATH . '/' . $viewName . '/' . $viewName . 'Controller.php'
        ];
        
        $controllerFile = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $controllerFile = $path;
                break;
            }
        }
        
        if ($controllerFile) {
            require_once $controllerFile;
        }

        if (class_exists($viewClass)) {
            global $app;
            $view = new $viewClass($app->getDatabase());
            if (method_exists($view, $method)) {
                $view->$method();
                return;
            }
        }

        $this->handleNotFound();
    }

    private function handleNotFound()
    {
        http_response_code(404);
        
        // Tentative de charger une page d'erreur 404 personnalisée
        $errorPage = VIEWS_PATH . '/errors/404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<h1>404 - Page non trouvée</h1>";
            echo "<p>La page demandée n'existe pas.</p>";
        }
    }

    private function handleUnauthorized()
    {
        http_response_code(403);
        
        // Tentative de charger une page d'erreur 403 personnalisée
        $errorPage = VIEWS_PATH . '/errors/403.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<h1>403 - Accès non autorisé</h1>";
            echo "<p>Vous n'avez pas l'autorisation d'accéder à cette page.</p>";
        }
    }

    private function redirect($route)
    {
        header("Location: /$route");
        exit;
    }
}
?>