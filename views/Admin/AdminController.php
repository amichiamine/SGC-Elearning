<?php

namespace SGC\Controllers\Admin;

use SGC\Core\Auth;
use SGC\Core\View;
use SGC\Core\Theme;
use SGC\Core\Database;
use SGC\Core\Config;

/**
 * Contrôleur d'administration pour SGC E-Learning
 * Gère le tableau de bord admin et les fonctionnalités d'administration
 */
class AdminController
{
    private $auth;
    private $view;
    private $theme;
    private $db;
    private $config;
    
    public function __construct()
    {
        $this->auth = new Auth();
        $this->view = new View();
        $this->theme = new Theme();
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
        
        // Protection : seuls les admins peuvent accéder
        $this->auth->requireRole('admin');
    }
    
    /**
     * Tableau de bord principal d'administration
     */
    public function dashboard()
    {
        $data = [
            'title' => 'Tableau de bord Admin - SGC E-Learning',
            'user' => $this->auth->getUser(),
            'stats' => $this->getDashboardStats(),
            'recent_users' => $this->getRecentUsers(),
            'recent_activity' => $this->getRecentActivity(),
            'system_info' => $this->getSystemInfo()
        ];
        
        $this->renderView('dashboard', $data);
    }
    
    /**
     * Gestion des utilisateurs
     */
    public function users()
    {
        $page = max(1, $_GET['page'] ?? 1);
        $perPage = 20;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        if ($search) {
            $whereConditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($role) {
            $whereConditions[] = "role = ?";
            $params[] = $role;
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Comptage total
        $stmt = $this->db->query("SELECT COUNT(*) FROM users $whereClause", $params);
        $totalUsers = $stmt->fetchColumn();
        
        // Récupération des utilisateurs
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->query(
            "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $users = $stmt->fetchAll();
        
        $data = [
            'title' => 'Gestion des utilisateurs - Admin',
            'users' => $users,
            'current_page' => $page,
            'total_pages' => ceil($totalUsers / $perPage),
            'total_users' => $totalUsers,
            'search' => $search,
            'role' => $role,
            'per_page' => $perPage
        ];
        
        $this->renderView('users', $data);
    }
    
    /**
     * Gestion des cours
     */
    public function courses()
    {
        $page = max(1, $_GET['page'] ?? 1);
        $perPage = 20;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        if ($search) {
            $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Comptage total
        $stmt = $this->db->query("SELECT COUNT(*) FROM courses $whereClause", $params);
        $totalCourses = $stmt->fetchColumn();
        
        // Récupération des cours
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->query(
            "SELECT c.*, u.username as instructor_name FROM courses c 
             LEFT JOIN users u ON c.instructor_id = u.id 
             $whereClause ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $courses = $stmt->fetchAll();
        
        $data = [
            'title' => 'Gestion des cours - Admin',
            'courses' => $courses,
            'current_page' => $page,
            'total_pages' => ceil($totalCourses / $perPage),
            'total_courses' => $totalCourses,
            'search' => $search,
            'status' => $status,
            'per_page' => $perPage
        ];
        
        $this->renderView('courses', $data);
    }
    
    /**
     * Paramètres système
     */
    public function settings()
    {
        $tab = $_GET['tab'] ?? 'general';
        
        $data = [
            'title' => 'Paramètres système - Admin',
            'user' => $this->auth->getUser(),
            'current_tab' => $tab,
            'settings' => $this->getSettings(),
            'success' => $_GET['saved'] ?? null
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->saveSettings($tab);
            if ($result['success']) {
                header('Location: ' . WEB_ROOT . '/admin/settings?tab=' . $tab . '&saved=1');
                exit;
            }
            $data['errors'] = $result['errors'] ?? [];
        }
        
        $this->renderView('settings', $data);
    }
    
    /**
     * Rapports et statistiques
     */
    public function reports()
    {
        $type = $_GET['type'] ?? 'overview';
        
        $data = [
            'title' => 'Rapports - Admin',
            'user' => $this->auth->getUser(),
            'report_type' => $type,
            'stats' => $this->getDetailedStats($type)
        ];
        
        $this->renderView('reports', $data);
    }
    
    /**
     * Obtient les statistiques du tableau de bord
     */
    private function getDashboardStats()
    {
        return $this->db->getDashboardStats();
    }
    
    /**
     * Obtient les utilisateurs récents
     */
    private function getRecentUsers($limit = 5)
    {
        $stmt = $this->db->query(
            "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Obtient l'activité récente
     */
    private function getRecentActivity($limit = 10)
    {
        $stmt = $this->db->query(
            "SELECT u.username, c.title as course_title, e.enrolled_at 
             FROM enrollments e
             JOIN users u ON e.user_id = u.id
             JOIN courses c ON e.course_id = c.id
             ORDER BY e.enrolled_at DESC LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Obtient les informations système
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'app_version' => $this->config->get('app', 'version') ?? '1.0.0',
            'database_size' => $this->getDatabaseSize()
        ];
    }
    
    /**
     * Obtient la taille de la base de données
     */
    private function getDatabaseSize()
    {
        $dbPath = DATABASE_PATH . '/elearning.db';
        return file_exists($dbPath) ? filesize($dbPath) : 0;
    }
    
    /**
     * Obtient les paramètres système
     */
    private function getSettings()
    {
        $stmt = $this->db->query("SELECT * FROM settings ORDER BY key");
        $settings = $stmt->fetchAll();
        
        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['key']] = $setting;
        }
        
        return $grouped;
    }
    
    /**
     * Sauvegarde les paramètres
     */
    private function saveSettings($tab)
    {
        $errors = [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = substr($key, 8);
                    $this->db->setSetting($settingKey, $value);
                }
            }
            
            $this->db->commit();
            
            // Log de l'action
            $this->db->logAudit(
                $this->auth->getUser()['id'],
                'settings_updated',
                'admin',
                json_encode(['tab' => $tab])
            );
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'errors' => ['general' => $e->getMessage()]];
        }
    }
    
    /**
     * Obtient des statistiques détaillées
     */
    private function getDetailedStats($type)
    {
        $stats = [];
        
        switch ($type) {
            case 'users':
                $stats['by_role'] = $this->db->fetchAll("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                $stats['by_month'] = $this->db->fetchAll("
                    SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as count 
                    FROM users 
                    GROUP BY strftime('%Y-%m', created_at) 
                    ORDER BY month DESC LIMIT 12
                ");
                break;
                
            case 'courses':
                $stats['by_status'] = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM courses GROUP BY status");
                $stats['by_category'] = $this->db->fetchAll("SELECT category, COUNT(*) as count FROM courses GROUP BY category");
                break;
        }
        
        return $stats;
    }
    
    /**
     * Rend une vue admin
     */
    private function renderView($template, $data)
    {
        // Ajout des variables globales pour les templates admin
        $data['nav_items'] = [
            ['label' => 'Tableau de bord', 'url' => WEB_ROOT . '/admin', 'icon' => '📊'],
            ['label' => 'Utilisateurs', 'url' => WEB_ROOT . '/admin/users', 'icon' => '👥'],
            ['label' => 'Cours', 'url' => WEB_ROOT . '/admin/courses', 'icon' => '📚'],
            ['label' => 'Paramètres', 'url' => WEB_ROOT . '/admin/settings', 'icon' => '⚙️'],
            ['label' => 'Rapports', 'url' => WEB_ROOT . '/admin/reports', 'icon' => '📈']
        ];
        
        // Extraction des variables pour le template
        extract($data);
        
        // Inclusion du template
        $templatePath = VIEWS_PATH . '/Admin/' . $template . '.html';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo "<h1>Template admin non trouvé: $template</h1>";
        }
    }
}