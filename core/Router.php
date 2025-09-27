<?php
namespace SGC\Core;

/**
 * Système de routage modulaire pour SGC E-Learning
 * Gère les routes vers les vues indépendantes avec chemins absolus
 */
class Router
{
    private $routes = [];
    private $auth;
    private $currentRoute = null;

    public function __construct($auth = null)
    {
        $this->auth = $auth;
        $this->loadRoutes();
    }

    private function loadRoutes()
    {
        // Routes par défaut du système
        $this->routes = [
            '' => ['view' => 'Home', 'method' => 'index', 'auth' => false],
            'home' => ['view' => 'Home', 'method' => 'index', 'auth' => false],
            'about' => ['view' => 'Home', 'method' => 'about', 'auth' => false],
            'contact' => ['view' => 'Home', 'method' => 'contact', 'auth' => false],
            'login' => ['view' => 'Auth\\Login', 'method' => 'index', 'auth' => false],
            'register' => ['view' => 'Auth\\Register', 'method' => 'index', 'auth' => false],
            'logout' => ['view' => 'Auth\\Login', 'method' => 'logout', 'auth' => true],
            'admin' => ['view' => 'Admin\\Dashboard', 'method' => 'index', 'auth' => true, 'role' => 'admin'],
            'profile' => ['view' => 'User\\Profile', 'method' => 'index', 'auth' => true],
        ];
    }
    
    /**
     * Ajoute une route dynamiquement
     */
    public function addRoute($path, $controller, $method = 'index', $auth = false, $role = null)
    {
        $this->routes[$path] = [
            'view' => $controller,
            'method' => $method,
            'auth' => $auth,
            'role' => $role
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
        
        $this->currentRoute = $route;

        // Vérification d'authentification
        if ($route['auth'] && $this->auth && !$this->auth->isAuthenticated()) {
            $this->redirect('login');
            return;
        }

        // Vérification des rôles
        if (isset($route['role']) && $this->auth && !$this->auth->hasRole($route['role'])) {
            $this->handleUnauthorized();
            return;
        }

        $this->loadView($route);
    }

    private function getCurrentUri()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Suppression des paramètres de requête
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Suppression du chemin de base
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
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
        $controllerName = $viewName . 'Controller';
        
        // Construction du chemin vers le contrôleur
        $controllerPath = VIEWS_PATH . '/' . str_replace('\\', '/', $route['view']) . '/' . $controllerName . '.php';
        
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            
            $controllerClass = "SGC\\Controllers\\" . str_replace('\\', '\\', $route['view']) . "\\" . $controllerName;
            
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                
                if (method_exists($controller, $route['method'])) {
                    $controller->{$route['method']}();
                    return;
                }
            }
        }
        
        // Fallback : chargement direct du template si pas de contrôleur
        $templatePath = VIEWS_PATH . '/' . str_replace('\\', '/', $route['view']) . '/' . strtolower($viewName) . '.html';
        
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            $this->handleNotFound();
        }
    }

    private function handleNotFound()
    {
        http_response_code(404);
        
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
        $url = defined('WEB_ROOT') ? WEB_ROOT . '/' . $route : '/' . $route;
        header("Location: $url");
        exit;
    }
    
    /**
     * Obtient la route courante
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }
    
    /**
     * Génère une URL
     */
    public function url($path = '')
    {
        $baseUrl = defined('WEB_ROOT') ? WEB_ROOT : '';
        return $baseUrl . '/' . ltrim($path, '/');
    }
}
?>