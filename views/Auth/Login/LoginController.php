<?php

namespace SGC\Controllers\Auth\Login;

use SGC\Core\Controller;

/**
 * Gère la connexion des utilisateurs.
 */
class LoginController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     */
    public function showLoginForm(): void
    {
        // Génère un token CSRF pour le formulaire
        $csrfToken = $this->auth->generateCsrfToken();

        $this->render('Auth/Login/login.html', [
            'title' => 'Connexion',
            'csrf_token' => $csrfToken
        ]);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     */
    public function login(): void
    {
        // 1. Vérifier le token CSRF
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCsrfToken($_POST['csrf_token'])) {
            // Gérer l'erreur CSRF : rediriger avec un message d'erreur
            // Pour l'instant, simple redirection. Une gestion de messages flash sera ajoutée.
            $this->redirect('/login');
            return;
        }

        // 2. Récupérer les données du formulaire
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        // 3. Tenter la connexion via le service Auth
        $result = $this->auth->login($username, $password, $rememberMe);

        // 4. Gérer le résultat
        if ($result['success']) {
            // Redirection vers le tableau de bord approprié
            $this->redirectBasedOnRole($this->auth->getUser()['role']);
        } else {
            // Rediriger vers la page de connexion avec un message d'erreur
            // TODO: Implémenter un système de messages flash pour afficher $result['message']
            $this->redirect('/login');
        }
    }

    /**
     * Redirige l'utilisateur en fonction de son rôle.
     *
     * @param string $role
     */
    private function redirectBasedOnRole(string $role): void
    {
        switch ($role) {
            case 'admin':
                $this->redirect('/admin');
                break;
            case 'instructor':
                $this->redirect('/instructor');
                break;
            case 'student':
            default:
                $this->redirect('/student'); // Ou un tableau de bord étudiant
                break;
        }
    }
}