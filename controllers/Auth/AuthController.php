<?php

namespace SGC\Controllers\Auth;

use SGC\Core\Controller;

/**
 * Contrôleur d'authentification pour SGC E-Learning
 * Gère les pages de connexion, inscription et déconnexion
 */
class AuthController extends Controller
{
    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
    }

    /**
     * Affiche la page de connexion
     */
    public function login()
    {
        // Redirection si déjà connecté
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }

        $data = [
            'title' => 'Connexion - SGC E-Learning',
            'csrf_token' => $this->auth->generateCsrfToken(),
            'error' => null,
            'old_input' => []
        ];

        // Traitement du formulaire POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($data, $this->processLogin());
        }

        $this->renderView('Auth/login.html', $data, 'theme/templates/auth');
    }

    /**
     * Traite la connexion
     */
    private function processLogin()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Vérification CSRF
        if (!$this->auth->verifyCsrfToken($csrfToken)) {
            return [
                'error' => 'Token de sécurité invalide.',
                'old_input' => ['username' => htmlspecialchars($username)]
            ];
        }

        // Tentative de connexion
        $result = $this->auth->login($username, $password, $rememberMe);

        if ($result['success']) {
            $this->redirectToDashboard();
            return [];
        } else {
            return [
                'error' => $result['message'],
                'old_input' => ['username' => htmlspecialchars($username)]
            ];
        }
    }

    /**
     * Affiche la page d'inscription
     */
    public function register()
    {
        // Redirection si déjà connecté
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }

        $data = [
            'title' => 'Inscription - SGC E-Learning',
            'csrf_token' => $this->auth->generateCsrfToken(),
            'errors' => [],
            'success' => null,
            'old_input' => []
        ];

        // Traitement du formulaire POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($data, $this->processRegister());
        }

        $this->renderView('Auth/register.html', $data, 'theme/templates/auth');
    }

    /**
     * Traite l'inscription
     */
    private function processRegister()
    {
        $formData = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? ''
        ];
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Sauvegarde pour réaffichage
        $oldInput = array_map('htmlspecialchars', $formData);
        unset($oldInput['password'], $oldInput['password_confirm']);

        // Vérification CSRF
        if (!$this->auth->verifyCsrfToken($csrfToken)) {
            return [
                'errors' => ['csrf' => 'Token de sécurité invalide.'],
                'old_input' => $oldInput
            ];
        }
        
        // Tentative d'inscription
        $result = $this->auth->register($formData);

        if ($result['success']) {
            return [
                'success' => 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.',
                'old_input' => []
            ];
        } else {
            return [
                'errors' => $result['errors'] ?? ['general' => $result['message']],
                'old_input' => $oldInput
            ];
        }
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        $this->auth->logout();
        header('Location: ' . WEB_ROOT . '/login');
        exit;
    }

    /**
     * Redirection vers le tableau de bord approprié selon le rôle
     */
    private function redirectToDashboard()
    {
        $user = $this->auth->getUser();

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
        exit;
    }

    /**
     * Page de profil utilisateur
     */
    public function profile()
    {
        $this->auth->requireLogin();

        $data = [
            'title' => 'Mon Profil - SGC E-Learning',
            'user' => $this->auth->getUser(),
            'csrf_token' => $this->auth->generateCsrfToken(),
            'success' => null,
            'errors' => []
        ];

        // Traitement de la mise à jour du profil
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($data, $this->processProfileUpdate());
        }

        $this->renderView('Auth/profile.html', $data, 'theme/templates/auth');
    }

    /**
     * Traite la mise à jour du profil
     */
    private function processProfileUpdate()
    {
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Vérification CSRF
        if (!$this->auth->verifyCsrfToken($csrfToken)) {
            return ['errors' => ['csrf' => 'Token de sécurité invalide.']];
        }

        // À implémenter : mise à jour du profil
        return ['success' => 'Profil mis à jour avec succès.'];
    }

    /**
     * Réinitialisation de mot de passe (formulaire)
     */
    public function forgotPassword()
    {
        $data = [
            'title' => 'Mot de passe oublié - SGC E-Learning',
            'csrf_token' => $this->auth->generateCsrfToken(),
            'message' => null,
            'error' => null
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($data, $this->processForgotPassword());
        }

        $this->renderView('Auth/forgot-password.html', $data, 'theme/templates/auth');
    }

    /**
     * Traite la demande de réinitialisation
     */
    private function processForgotPassword()
    {
        // À implémenter : système de réinitialisation par email
        return ['message' => 'Si votre email existe, vous recevrez un lien de réinitialisation.'];
    }

}