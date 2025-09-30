<?php

namespace SGC\Core;

/**
 * Routeur pour SGC E-Learning
 * Gère le routage des requêtes vers les contrôleurs appropriés
 */
class Router
{
    private $routes = [];
    private $currentRoute = null;
    private $middlewares = [];

    public function __construct()
    {
        $this->loadRoutes();
    }

    /**
     * Charge les routes depuis le fichier de configuration
     */
    private function loadRoutes()
    {
        $routesFile = CONFIG_PATH . '/routes.json';
        if (!file_exists($routesFile)) {
            throw new \Exception("Le fichier de configuration des routes est introuvable.");
        }

        $routesConfig = json_decode(file_get_contents($routesFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Erreur de décodage du fichier routes.json: " . json_last_error_msg());
        }

        if (isset($routesConfig['routes']) && is_array($routesConfig['routes'])) {
            foreach ($routesConfig['routes'] as $route) {
                $this->addRoute(
                    $route['method'],
                    $route['path'],
                    $route['controller'],
                    $route['action'],
                    $route['middlewares'] ?? []
                );
            }
        }
    }

    /**
     * Ajoute une route
     */
    public function addRoute($method, $path, $controller, $action, $middlewares = [])
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Route une requête
     */
    public function route()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getCurrentPath();

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $this->currentRoute = $route;

                // Exécution des middlewares
                if (!empty($route['middlewares'])) {
                    if (!$this->executeMiddlewares($route['middlewares'])) {
                        return; // Middleware a arrêté l'exécution
                    }
                }

                return $this->executeRoute($route);
            }
        }

        // Route non trouvée
        $this->handle404();
    }

    /**
     * Obtient le chemin actuel
     */
    private function getCurrentPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // Supprime les paramètres de requête
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Supprime le chemin de base si l'application n'est pas à la racine
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);

        if ($basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return $path ?: '/';
    }

    /**
     * Vérifie si une route correspond
     */
    private function matchRoute($route, $method, $path)
    {
        if ($route['method'] !== $method) {
            return false;
        }

        // Correspondance exacte pour l'instant
        // TODO: Ajouter support des paramètres dynamiques
        return $route['path'] === $path;
    }

    /**
     * Exécute les middlewares
     */
    private function executeMiddlewares($middlewares)
    {
        foreach ($middlewares as $middleware) {
            if (!$this->executeMiddleware($middleware)) {
                return false; // Middleware a arrêté l'exécution
            }
        }
        return true;
    }

    /**
     * Exécute un middleware individuel
     */
    private function executeMiddleware($middleware)
    {
        // Middleware d'authentification
        if ($middleware === 'auth') {
            $auth = new \SGC\Core\Auth();
            if (!$auth->isLoggedIn()) {
                header('Location: ' . WEB_ROOT . '/login');
                exit;
            }
            return true;
        }

        // Middleware de vérification de rôle
        if (strpos($middleware, 'role:') === 0) {
            $requiredRole = substr($middleware, 5);
            $auth = new \SGC\Core\Auth();

            if (!$auth->hasRole($requiredRole)) {
                // Redirection selon le rôle actuel
                $user = $auth->getUser();
                if (!$user) {
                    header('Location: ' . WEB_ROOT . '/login');
                } else {
                    switch ($user['role']) {
                        case 'admin':
                            header('Location: ' . WEB_ROOT . '/admin');
                            break;
                        case 'instructor':
                            header('Location: ' . WEB_ROOT . '/instructor');
                            break;
                        default:
                            header('Location: ' . WEB_ROOT . '/student');
                            break;
                    }
                }
                exit;
            }
            return true;
        }

        // Middleware guest (non connecté uniquement)
        if ($middleware === 'guest') {
            $auth = new \SGC\Core\Auth();
            if ($auth->isLoggedIn()) {
                $this->redirectToDashboard($auth->getUser());
                exit;
            }
            return true;
        }

        return true; // Middleware inconnu = continue
    }

    /**
     * Redirection vers le tableau de bord approprié
     */
    private function redirectToDashboard($user)
    {
        switch ($user['role']) {
            case 'admin':
                header('Location: ' . WEB_ROOT . '/admin');
                break;
            case 'instructor':
                header('Location: ' . WEB_ROOT . '/instructor');
                break;
            default:
                header('Location: ' . WEB_ROOT . '/student');
                break;
        }
    }

    /**
     * Exécute une route
     */
    private function executeRoute($route)
    {
        $controllerClass = $route['controller'];
        $action = $route['action'];

        // Construction du nom de classe complet
        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = 'SGC\\Controllers\\' . $controllerClass;
        } else {
            $controllerClass = 'SGC\\Controllers\\' . $controllerClass;
        }

        try {
            if (!class_exists($controllerClass)) {
                throw new \Exception("Contrôleur non trouvé: $controllerClass");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $action)) {
                throw new \Exception("Action non trouvée: $action dans $controllerClass");
            }

            return $controller->$action();

        } catch (\Exception $e) {
            error_log("Erreur de routage: " . $e->getMessage());
            $this->handle500($e);
        }
    }

    /**
     * Gère les erreurs 404
     */
    private function handle404()
    {
        http_response_code(404);

        $errorView = VIEWS_PATH . '/errors/404.php';
        if (file_exists($errorView)) {
            include $errorView;
        } else {
            echo "<h1>404 - Page non trouvée</h1>";
            echo "<p>La page demandée n'existe pas.</p>";
            echo '<p><a href="' . WEB_ROOT . '">Retour à l\'accueil</a></p>';
        }
    }

    /**
     * Gère les erreurs 500
     */
    private function handle500(\Exception $e)
    {
        http_response_code(500);

        $errorView = VIEWS_PATH . '/errors/500.php';
        if (file_exists($errorView)) {
            $error = $e;
            include $errorView;
        } else {
            echo "<h1>500 - Erreur du serveur</h1>";
            echo "<p>Une erreur inattendue s'est produite.</p>";

            try {
                $debug = \SGC\Core\Config::getInstance()->get('app', 'debug') ?? false;
                if ($debug) {
                    echo "<details><summary>Détails techniques</summary>";
                    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    echo "</details>";
                }
            } catch (\Exception $configError) {
                // Ignore si on ne peut pas charger la config
            }
        }
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
        return WEB_ROOT . '/' . ltrim($path, '/');
    }

    /**
     * Vérifie si une route existe
     */
    public function hasRoute($method, $path)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $route['path'] === $path) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtient toutes les routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}