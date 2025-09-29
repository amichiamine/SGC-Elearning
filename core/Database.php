<?php

namespace SGC\Core;

use PDO;
use PDOException;

/**
 * Gestionnaire de base de données pour SGC E-Learning.
 * Gère la connexion et les requêtes à la base de données.
 */
class Database
{
    private ?PDO $pdo = null;
    private array $config;

    /**
     * Le constructeur reçoit la configuration et établit la connexion.
     */
    public function __construct(Config $config)
    {
        $this->config = $config->get('database');
        $this->connect();
    }

    /**
     * Établit la connexion à la base de données en utilisant la configuration.
     */
    private function connect(): void
    {
        try {
            // Le chemin est défini dans config/database.json, ex: "{DATABASE_PATH}/elearning.db"
            $dbPath = str_replace('{DATABASE_PATH}', DATABASE_PATH, $this->config['path']);
            
            // Création du dossier de la base de données s'il n'existe pas
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
            
            if (isset($this->config['options']['foreign_keys']) && $this->config['options']['foreign_keys']) {
                $this->pdo->exec('PRAGMA foreign_keys=ON');
            }
            
        } catch (PDOException $e) {
            // Transformer l'erreur PDO en une exception plus générale
            throw new \Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    /**
     * Retourne l'instance PDO pour les opérations avancées.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Exécute une requête préparée et retourne l'objet PDOStatement.
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Erreur de requête SQL: " . $e->getMessage());
        }
    }

    /**
     * Exécute une requête et récupère tous les résultats.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Exécute une requête et récupère un seul résultat.
     */
    public function fetch(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Exécute une requête (INSERT, UPDATE, DELETE) et retourne le nombre de lignes affectées.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Retourne l'ID de la dernière ligne insérée.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // Les méthodes métier comme getDashboardStats() et logAudit() seront déplacées
    // dans des classes dédiées (ex: repositories ou services) dans une architecture plus avancée.
    // Pour l'instant, on les laisse ici pour ne pas casser le code existant.

    public function getDashboardStats(): array
    {
        $stats = [];
        $stats['total_users'] = $this->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
        $stats['total_courses'] = $this->fetch("SELECT COUNT(*) as count FROM courses WHERE status = 'published'")['count'] ?? 0;
        $stats['total_enrollments'] = $this->fetch("SELECT COUNT(*) as count FROM enrollments")['count'] ?? 0;
        $stats['new_users_month'] = $this->fetch("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) >= DATE('now', 'start of month')")['count'] ?? 0;
        return $stats;
    }
}