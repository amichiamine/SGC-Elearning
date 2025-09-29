<?php

namespace SGC\Core;

/**
 * Gestionnaire d'authentification pour SGC E-Learning.
 * Gère les sessions, connexions, et la sécurité des utilisateurs.
 */
class Auth
{
    private Database $db;
    private ?array $user = null;

    /**
     * Le constructeur reçoit la dépendance à la base de données et initialise la session.
     */
    public function __construct(Database $database)
    {
        $this->db = $database;
        $this->initializeSession();
        $this->loadUserFromSession();
    }

    /**
     * Démarre et configure la session de manière sécurisée.
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Des configurations plus avancées (httponly, etc.) peuvent être ajoutées ici.
    }

    /**
     * Charge les informations de l'utilisateur connecté depuis la session.
     */
    private function loadUserFromSession(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            if (!$this->user) {
                // Si l'utilisateur n'existe plus en base, on détruit la session.
                $this->logout();
            }
        }
    }

    /**
     * Tente de connecter un utilisateur.
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->db->fetch("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);

        if ($user && password_verify($password, $user['password'])) {
            $this->createUserSession($user);
            return true;
        }

        return false;
    }

    /**
     * Crée la session pour un utilisateur authentifié.
     */
    private function createUserSession(array $user): void
    {
        session_regenerate_id(true); // Prévention de fixation de session
        $_SESSION['user_id'] = $user['id'];
        $this->user = $user;
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->user = null;
    }

    /**
     * Vérifie si un utilisateur est connecté.
     */
    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    /**
     * Retourne l'utilisateur actuellement connecté.
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     */
    public function hasRole(string $role): bool
    {
        return $this->isLoggedIn() && $this->user['role'] === $role;
    }

    /**
     * Génère un jeton CSRF et le stocke en session.
     */
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Vérifie si le jeton CSRF fourni correspond à celui en session.
     */
    public function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}