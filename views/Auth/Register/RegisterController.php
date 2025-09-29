<?php

namespace SGC\Controllers\Auth\Register;

use SGC\Core\Controller;

/**
 * Gère l'inscription des nouveaux utilisateurs.
 */
class RegisterController extends Controller
{
    /**
     * Affiche le formulaire d'inscription.
     */
    public function showRegistrationForm(): void
    {
        // Génère un token CSRF pour le formulaire
        $csrfToken = $this->auth->generateCsrfToken();

        $this->render('Auth/Register/register.html', [
            'title' => 'Inscription',
            'csrf_token' => $csrfToken
        ]);
    }

    /**
     * Traite la soumission du formulaire d'inscription.
     */
    public function register(): void
    {
        // 1. Vérifier le token CSRF
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCsrfToken($_POST['csrf_token'])) {
            $this->redirect('/register');
            return;
        }

        // 2. Récupérer et valider les données
        $data = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        // La validation est gérée dans Auth::register, mais une validation ici serait une bonne pratique
        // Pour l'instant, on délègue directement au service.

        // 3. Tenter l'inscription via le service Auth
        $result = $this->auth->register($data);

        // 4. Gérer le résultat
        if ($result['success']) {
            // Tenter de connecter l'utilisateur automatiquement après l'inscription
            $loginResult = $this->auth->login($data['username'], $data['password']);
            if ($loginResult['success']) {
                $this->redirect('/student'); // Rediriger vers le tableau de bord étudiant par défaut
            } else {
                // Si la connexion auto échoue, rediriger vers la page de connexion
                $this->redirect('/login');
            }
        } else {
            // Rediriger vers la page d'inscription avec un message d'erreur
            // TODO: Implémenter un système de messages flash pour afficher $result['message'] ou $result['errors']
            $this->redirect('/register');
        }
    }
}