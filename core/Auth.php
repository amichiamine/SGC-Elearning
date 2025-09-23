<?php
namespace Core;

/**
 * Système d'authentification
 * Gère l'authentification et les sessions utilisateur
 */
class Auth
{
    private $database;
    private $currentUser = null;

    public function __construct($database)
    {
        $this->database = $database;
        $this->loadCurrentUser();
    }

    public function isAuthenticated()
    {
        return $this->currentUser !== null;
    }

    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    public function hasRole($roleName)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        return $this->currentUser['role_name'] === $roleName || 
               $this->currentUser['role_name'] === 'super_admin';
    }

    private function loadCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->database->fetch("
                SELECT u.*, r.name as role_name, r.display_name as role_display_name
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?
            ", [$_SESSION['user_id']]);
        }
    }

    public function login($username, $password)
    {
        $user = $this->database->fetch("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? AND u.status = 'active'
        ", [$username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $this->currentUser = $user;
            
            // Mise à jour de la dernière connexion
            $this->database->execute("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$user['id']]);
            
            return true;
        }

        return false;
    }

    public function logout()
    {
        unset($_SESSION['user_id']);
        $this->currentUser = null;
        session_destroy();
    }
}
?>