<?php

namespace SGC\Controllers\Admin\Users;

use SGC\Core\Controller;

/**
 * Gère les opérations CRUD pour les utilisateurs dans le panneau d'administration.
 */
class UserController extends Controller
{
    /**
     * Affiche la liste de tous les utilisateurs.
     */
    public function index(): void
    {
        $users = $this->db->fetchAll("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC");

        $content = $this->view->render('Admin/Users/users.html', [
            'users' => $users
        ]);

        $this->renderAdminLayout('Gestion des Utilisateurs', $content);
    }

    /**
     * Affiche le formulaire pour éditer un utilisateur.
     */
    public function edit(int $id): void
    {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            // Gérer l'erreur utilisateur non trouvé
            $this->redirect('/admin/users');
            return;
        }

        $content = $this->view->render('Admin/Users/edit.html', [
            'user' => $user,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);

        $this->renderAdminLayout('Modifier l\'Utilisateur', $content);
    }

    /**
     * Traite la mise à jour d'un utilisateur.
     */
    public function update(int $id): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/users');
            return;
        }

        $data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'status' => $_POST['status'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name']
        ];

        $this->db->execute(
            "UPDATE users SET username = ?, email = ?, role = ?, status = ?, first_name = ?, last_name = ? WHERE id = ?",
            array_values(array_merge($data, ['id' => $id]))
        );

        // Gérer la mise à jour du mot de passe si fourni
        if (!empty($_POST['password'])) {
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $this->db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $id]);
        }

        // TODO: Ajouter un message flash de succès
        $this->redirect('/admin/users');
    }

    /**
     * Supprime un utilisateur.
     */
    public function delete(int $id): void
    {
        // TODO: Ajouter une vérification CSRF via POST pour plus de sécurité

        // Empêcher la suppression de son propre compte admin
        if ($this->auth->getUser()['id'] == $id) {
            // TODO: Ajouter un message flash d'erreur
            $this->redirect('/admin/users');
            return;
        }

        $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);

        // TODO: Ajouter un message flash de succès
        $this->redirect('/admin/users');
    }

}