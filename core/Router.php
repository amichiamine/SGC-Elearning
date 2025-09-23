<?php
namespace Core;

/**
 * Système de routage modulaire
 * Gère les routes vers les vues indépendantes
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
        echo "Page non trouvée";
    }

    private function handleUnauthorized()
    {
        http_response_code(403);
        echo "Accès non autorisé";
    }

    private function redirect($route)
    {
        header("Location: /$route");
        exit;
    }
}
?>