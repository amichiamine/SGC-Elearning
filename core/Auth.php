<?php

namespace SGC\Core;

use SGC\Core\Database;
use SGC\Core\Config;

/**
 * Gestionnaire d'authentification pour SGC E-Learning
 * Gère les connexions, déconnexions, sessions et rôles utilisateurs
 */
class Auth
{
    private $db;
    private $config;
    private $user = null;
    private $loginAttempts = [];
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
        $this->initializeSession();
        $this->loadCurrentUser();
    }

    /**
     * Initialise la session de façon sécurisée
     */
    private function initializeSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée de session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');

            session_start();

            // Régénération périodique de l'ID de session
            if (!isset($_SESSION['last_regeneration'])) {
                $this->regenerateSession();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
                $this->regenerateSession();
            }
        }
    }

    /**
     * Régénère l'ID de session
     */
    private function regenerateSession()
    {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    /**
     * Charge l'utilisateur actuel depuis la session
     */
    private function loadCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            $this->user = $stmt->fetch();

            // Vérification de sécurité - user toujours valide
            if (!$this->user) {
                $this->logout();
            }
        }
    }

    /**
     * Authentifie un utilisateur
     */
    public function login($username, $password, $rememberMe = false)
    {
        // Vérification du rate limiting
        if ($this->isAccountLocked($username)) {
            return [
                'success' => false,
                'message' => 'Compte temporairement verrouillé. Réessayez dans 15 minutes.'
            ];
        }

        // Validation des entrées
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Nom d\'utilisateur et mot de passe requis.'
            ];
        }

        try {
            // Recherche de l'utilisateur
            $stmt = $this->db->query(
                "SELECT * FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'
                ];
            }

            // Connexion réussie
            $this->clearFailedAttempts($username);
            $this->createUserSession($user);

            // Cookie "Remember Me" si demandé
            if ($rememberMe) {
                $this->setRememberToken($user['id']);
            }

            return [
                'success' => true,
                'message' => 'Connexion réussie.',
                'user' => $user
            ];

        } catch (\Exception $e) {
            error_log("Erreur lors de la connexion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur interne. Veuillez réessayer.'
            ];
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout()
    {
        if ($this->user) {
            // Suppression du token "Remember Me" s'il existe
            if (isset($_COOKIE['remember_token'])) {
                $this->clearRememberToken();
            }
        }

        // Nettoyage de la session
        $_SESSION = [];

        // Destruction du cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        $this->user = null;
    }

    /**
     * Crée une session utilisateur
     */
    private function createUserSession($user)
    {
        $this->regenerateSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $this->user = $user;
    }

    /**
     * Vérifie si un compte est verrouillé
     */
    private function isAccountLocked($username)
    {
        if (!isset($this->loginAttempts[$username])) {
            return false;
        }

        $attempts = $this->loginAttempts[$username];
        return $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS &&
               (time() - $attempts['last_attempt']) < self::LOCKOUT_TIME;
    }

    /**
     * Enregistre une tentative de connexion échouée
     */
    private function recordFailedAttempt($username)
    {
        if (!isset($this->loginAttempts[$username])) {
            $this->loginAttempts[$username] = ['count' => 0, 'last_attempt' => 0];
        }

        $this->loginAttempts[$username]['count']++;
        $this->loginAttempts[$username]['last_attempt'] = time();
    }

    /**
     * Efface les tentatives échouées
     */
    private function clearFailedAttempts($username)
    {
        unset($this->loginAttempts[$username]);
    }

    /**
     * Définit un token "Remember Me"
     */
    private function setRememberToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Sauvegarde en base (créer table si nécessaire)
        try {
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS user_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            );

            $this->db->query(
                "INSERT OR REPLACE INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$userId, $hashedToken, date('Y-m-d H:i:s', strtotime('+30 days'))]
            );
        } catch (\Exception $e) {
            error_log("Erreur token remember: " . $e->getMessage());
        }

        // Cookie sécurisé
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '',
                 isset($_SERVER['HTTPS']), true);
    }

    /**
     * Supprime le token "Remember Me"
     */
    private function clearRememberToken()
    {
        if ($this->user) {
            try {
                $this->db->query("DELETE FROM user_tokens WHERE user_id = ?", [$this->user['id']]);
            } catch (\Exception $e) {
                error_log("Erreur suppression token: " . $e->getMessage());
            }
        }
        setcookie('remember_token', '', time() - 3600, '/');
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn()
    {
        return $this->user !== null;
    }

    /**
     * Obtient l'utilisateur actuel
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole($role)
    {
        return $this->user && $this->user['role'] === $role;
    }

    /**
     * Vérifie si l'utilisateur a au moins un rôle parmi plusieurs
     */
    public function hasAnyRole($roles)
    {
        return $this->user && in_array($this->user['role'], $roles);
    }

    /**
     * Génère un token CSRF
     */
    public function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie un token CSRF
     */
    public function verifyCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Inscrit un nouvel utilisateur
     */
    public function register($data)
    {
        // Validation des données
        $errors = $this->validateRegistrationData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Vérification de l'unicité
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?",
                [$data['username'], $data['email']]
            );

            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou email déjà utilisé.'
                ];
            }

            // Création de l'utilisateur
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->query(
                "INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['username'],
                    $data['email'],
                    $hashedPassword,
                    $data['first_name'] ?? '',
                    $data['last_name'] ?? '',
                    'student' // rôle par défaut
                ]
            );

            return [
                'success' => true,
                'message' => 'Compte créé avec succès.',
                'user_id' => $this->db->lastInsertId()
            ];

        } catch (\Exception $e) {
            error_log("Erreur lors de l'inscription: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du compte.'
            ];
        }
    }

    /**
     * Valide les données d'inscription
     */
    private function validateRegistrationData($data)
    {
        $errors = [];

        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email valide requis.';
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }

        if (isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        return $errors;
    }

    /**
     * Middleware d'authentification
     */
    public function requireLogin($redirectUrl = '/login')
    {
        if (!$this->isLoggedIn()) {
            header("Location: " . WEB_ROOT . $redirectUrl);
            exit;
        }
    }

    /**
     * Middleware de vérification de rôle
     */
    public function requireRole($role, $redirectUrl = '/')
    {
        if (!$this->hasRole($role)) {
            header("Location: " . WEB_ROOT . $redirectUrl);
            exit;
        }
    }

    // Alias pour compatibilité
    public function isAuthenticated() { return $this->isLoggedIn(); }
    public function getCurrentUser() { return $this->getUser(); }
}