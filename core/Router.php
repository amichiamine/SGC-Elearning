<?php

namespace SGC\Core;

/**
 * Routeur pour SGC E-Learning.
 * Gère le routage des requêtes vers les contrôleurs appropriés en se basant sur une configuration.
 */
class Router
{
    private array $routes = [];
    private ?array $currentRoute = null;
    private Auth $auth;
    private Config $config;
    private Container $container;

    /**
     * Le constructeur reçoit ses dépendances et charge les routes.
     */
    public function __construct(Auth $auth, Config $config, Container $container)
    {
        $this->auth = $auth;
        $this->config = $config;
        $this->container = $container;
        $this->loadRoutesFromConfig();
    }

    /**
     * Charge les routes depuis le fichier de configuration.
     */
    private function loadRoutesFromConfig(): void
    {
        $routes = $this->config->get('routes.routes', []);
        foreach ($routes as $route) {
            $this->addRoute(
                $route['method'],
                $route['path'],
                $route['controller'],
                $route['action'],
                $route['middlewares'] ?? []
            );
        }
    }

    /**
     * Ajoute une route à la table de routage.
     */
    public function addRoute(string $method, string $path, string $controller, string $action, array $middlewares = []): void
    {
        $this->routes[] = compact('method', 'path', 'controller', 'action', 'middlewares');
    }

    /**
     * Traite la requête HTTP, trouve la route correspondante et exécute son contrôleur.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/')['path'];

        foreach ($this->routes as $route) {
            if ($this->match($route, $method, $path)) {
                $this->currentRoute = $route;
                if ($this->executeMiddlewares($route['middlewares'])) {
                    $this->executeRoute($route);
                }
                return;
            }
        }
        $this->handle404();
    }

    /**
     * Vérifie si une route correspond à la requête et en extrait les paramètres.
     */
    private function match(array $route, string $method, string $path): bool
    {
        if (strtoupper($route['method']) !== $method) {
            return false;
        }

        $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']) . '$#';

        if (preg_match($pattern, $path, $matches)) {
            $this->currentRoute['params'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    /**
     * Exécute les middlewares associés à une route.
     */
    private function executeMiddlewares(array $middlewares): bool
    {
        foreach ($middlewares as $middleware) {
            if (strpos($middleware, 'role:') === 0) {
                $requiredRole = substr($middleware, 5);
                if (!$this->auth->hasRole($requiredRole)) {
                    $this->handleForbidden();
                    return false;
                }
            } elseif ($middleware === 'auth' && !$this->auth->isLoggedIn()) {
                $this->redirect('/login');
                return false;
            } elseif ($middleware === 'guest' && $this->auth->isLoggedIn()) {
                $this->redirect('/'); // Rediriger vers l'accueil ou un dashboard
                return false;
            }
        }
        return true;
    }

    /**
     * Exécute la méthode du contrôleur associée à la route.
     */
    private function executeRoute(array $route): void
    {
        try {
            $controller = $this->container->make($route['controller']);
            $params = $this->currentRoute['params'] ?? [];
            $controller->{$route['action']}(...array_values($params));
        } catch (\Exception $e) {
            $this->handle500($e);
        }
    }

    private function handle404(): void
    {
        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
    }

    private function handleForbidden(): void
    {
        http_response_code(403);
        echo "<h1>403 - Accès interdit</h1>";
    }

    private function handle500(\Exception $e): void
    {
        error_log("Route execution error: " . $e->getMessage());
        http_response_code(500);
        echo "<h1>500 - Erreur du serveur</h1>";
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}