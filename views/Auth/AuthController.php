<?php

namespace SGC\Controllers\Auth;

use SGC\Core\Controller;

/**
 * Gère le profil utilisateur et la déconnexion.
 */
class AuthController extends Controller
{
    /**
     * Affiche la page de profil de l'utilisateur connecté.
     */
    public function profile(): void
    {
        // Le middleware 'auth' garantit que seul un utilisateur connecté peut accéder ici.
        $user = $this->auth->getUser();

        $this->render('Auth/Profile/profile.html', [
            'title' => 'Mon Profil',
            'user' => $user,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Traite la mise à jour du profil utilisateur.
     */
    public function updateProfile(): void
    {
        $user = $this->auth->getUser();

        // Vérifier le token CSRF
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCsrfToken($_POST['csrf_token'])) {
            $this->redirect('/profile');
            return;
        }

        // Récupérer les données du formulaire
        $data = [
            'first_name' => $_POST['first_name'] ?? $user['first_name'],
            'last_name' => $_POST['last_name'] ?? $user['last_name'],
            'email' => $_POST['email'] ?? $user['email'],
        ];

        // Mettre à jour le mot de passe s'il est fourni
        if (!empty($_POST['password'])) {
            // TODO: Ajouter une validation plus robuste (longueur, confirmation)
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        // Mettre à jour les informations de l'utilisateur dans la base de données
        $updateStmt = $this->db->query(
            "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?",
            [$data['first_name'], $data['last_name'], $data['email'], $user['id']]
        );

        // Mettre à jour le mot de passe si changé
        if (isset($data['password'])) {
            $this->db->query("UPDATE users SET password = ? WHERE id = ?", [$data['password'], $user['id']]);
        }
        
        // TODO: Ajouter une gestion des erreurs et des messages de succès
        $this->redirect('/profile');
    }

    /**
     * Gère la déconnexion de l'utilisateur.
     */
    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/');
    }
}