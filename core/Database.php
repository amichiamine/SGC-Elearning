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
    private $pdo;
    private $config;
    
    public function __construct(Config $config)
    {
        $this->config = $config->get('database');
        $this->connect();
        // La création des tables sera gérée par un système de migration.
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