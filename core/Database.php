<?php

namespace SGC\Core;

use PDO;
use PDOException;

/**
 * Gestionnaire de base de données pour SGC E-Learning
 * Support SQLite avec extension possible vers MySQL/PostgreSQL
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = Config::getInstance()->get('database');
        $this->connect();
        $this->initializeDatabase();
    }

    /**
     * Obtient l'instance singleton
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Établit la connexion à la base de données
     */
    private function connect()
    {
        try {
            $dbPath = str_replace('{DATABASE_PATH}', DATABASE_PATH, $this->config['path']);
            
            // Création du dossier database s'il n'existe pas
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $dsn = "sqlite:$dbPath";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, null, null, $options);
            
            // Configuration SQLite
            if (isset($this->config['options']['foreign_keys']) && $this->config['options']['foreign_keys']) {
                $this->pdo->exec('PRAGMA foreign_keys=ON');
            }
            
            if (isset($this->config['options']['journal_mode'])) {
                $journalMode = $this->config['options']['journal_mode'];
                $this->pdo->exec("PRAGMA journal_mode=$journalMode");
            }

        } catch (PDOException $e) {
            throw new \Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }


    /**
     * Initialise la base de données si elle n'existe pas
     */
    private function initializeDatabase()
    {
        try {
            // Vérifie si la table users existe déjà
            $this->pdo->query("SELECT 1 FROM users LIMIT 1");
        } catch (PDOException $e) {
            // La table n'existe pas, on lance la migration
            $this->runInitialMigration();
            $this->seedInitialData();
        }
    }

    /**
     * Exécute le script de migration initial
     */
    private function runInitialMigration()
    {
        $migrationFile = DATABASE_PATH . '/migrations/001_initial_schema.sql';
        if (!file_exists($migrationFile)) {
            throw new \Exception("Le fichier de migration initial est introuvable.");
        }
        $sql = file_get_contents($migrationFile);
        $this->pdo->exec($sql);
    }

    /**
     * Insère les données initiales
     */
    private function seedInitialData()
    {
        $this->createDefaultAdmin();
        $this->insertDefaultSettings();
        $this->insertDefaultStatistics();
    }

    /**
     * Crée l'utilisateur admin par défaut
     */
    private function createDefaultAdmin()
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role, first_name, last_name)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(['admin', 'admin@sgc-elearning.com', $adminPassword, 'admin', 'Admin', 'SGC']);
        }
    }

    /**
     * Insère les paramètres par défaut
     */
    private function insertDefaultSettings()
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM settings");
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            $defaultSettings = [
                ['site_name', 'SGC E-Learning', 'string', 'Nom du site'],
                ['site_description', 'Plateforme d\'apprentissage en ligne', 'string', 'Description du site'],
                ['maintenance_mode', '0', 'boolean', 'Mode maintenance'],
                ['allow_registration', '1', 'boolean', 'Autoriser les inscriptions'],
                ['max_file_size', '10485760', 'integer', 'Taille max des fichiers (bytes)'],
                ['pagination_limit', '20', 'integer', 'Nombre d\'items par page'],
                ['email_notifications', '1', 'boolean', 'Notifications par email'],
                ['theme_color', '#4A90E2', 'string', 'Couleur principale du thème']
            ];

            $stmt = $this->pdo->prepare("INSERT INTO settings (key, value, type, description) VALUES (?, ?, ?, ?)");
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
        }
    }

    /**
     * Insère les statistiques par défaut
     */
    private function insertDefaultStatistics()
    {
        $today = date('Y-m-d');

        $defaultStats = [
            ['total_users', 1],
            ['total_courses', 0],
            ['total_enrollments', 0],
            ['active_sessions', 0]
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO statistics (metric_name, metric_value, date) VALUES (?, ?, ?)");
        foreach ($defaultStats as $stat) {
            $stmt->execute([$stat[0], $stat[1], $today]);
        }
    }

    /**
     * Obtient la connexion PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Exécute une requête préparée
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Erreur de requête: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les résultats
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un résultat
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Exécute une requête et retourne le nombre de lignes affectées
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Obtient le dernier ID inséré
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Démarre une transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Annule une transaction
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }

    /**
     * Obtient les statistiques du tableau de bord
     */
    public function getDashboardStats()
    {
        $stats = [];

        // Nombre total d'utilisateurs
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];

        // Nombre total de cours
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM courses WHERE status = 'published'");
        $stats['total_courses'] = $stmt->fetch()['count'];

        // Nombre total d'inscriptions
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM enrollments");
        $stats['total_enrollments'] = $stmt->fetch()['count'];

        // Nouveaux utilisateurs ce mois
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) >= DATE('now', 'start of month')");
        $stats['new_users_month'] = $stmt->fetch()['count'];

        return $stats;
    }

    /**
     * Enregistre un log d'audit
     */
    public function logAudit($userId, $action, $module, $details = null)
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, module, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$userId, $action, $module, $details, $ipAddress, $userAgent]);
    }

    /**
     * Obtient un paramètre
     */
    public function getSetting($key, $default = null)
    {
        $stmt = $this->pdo->prepare("SELECT value, type FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();

        if (!$result) {
            return $default;
        }

        // Conversion du type
        switch ($result['type']) {
            case 'boolean':
                return (bool) $result['value'];
            case 'integer':
                return (int) $result['value'];
            case 'float':
                return (float) $result['value'];
            default:
                return $result['value'];
        }
    }

    /**
     * Définit un paramètre
     */
    public function setSetting($key, $value, $type = 'string', $description = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO settings (key, value, type, description, updated_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        return $stmt->execute([$key, $value, $type, $description]);
    }
}