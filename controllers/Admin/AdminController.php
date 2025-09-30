<?php

namespace SGC\Controllers\Admin;

use SGC\Core\Controller;
use SGC\Models\User; // Assuming a User model might exist or be created later.

class AdminController extends Controller
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->auth->requireRole('admin');
    }

    /**
     * Display the main admin dashboard, which includes a list of users.
     */
    public function dashboard()
    {
        $users = $this->getAllUsers();

        $data = [
            'title' => 'Gestion des Utilisateurs',
            'users' => $users
        ];

        $this->renderView('Admin/Dashboard/dashboard.html', $data);
    }

    /**
     * Fetches all users from the database.
     *
     * @return array An array of user records.
     */
    private function getAllUsers(): array
    {
        try {
            $pdo = $this->db->getPDO();
            $stmt = $pdo->query("
                SELECT id, username, email, role, status, created_at
                FROM users
                ORDER BY created_at DESC
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Log the error for debugging.
            error_log('Admin user fetch error: ' . $e->getMessage());
            // Return an empty array to prevent view rendering errors.
            return [];
        }
    }
}