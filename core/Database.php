<?php
namespace Core;

/**
 * Gestionnaire de base de données SQLite avec support multi-SGBD
 * Portable et embarqué
 */
class Database
{
    private $pdo;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function initialize()
    {
        $dbConfig = $this->config->get('database', [
            'type' => 'sqlite',
            'path' => DATABASE_PATH . '/elearning.db'
        ]);

        try {
            switch ($dbConfig['type']) {
                case 'sqlite':
                    $this->pdo = new \PDO('sqlite:' . $dbConfig['path']);
                    break;
                case 'mysql':
                    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
                    $this->pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                    break;
                case 'postgresql':
                    $dsn = "pgsql:host={$dbConfig['host']};dbname={$dbConfig['database']}";
                    $this->pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                    break;
                default:
                    throw new \Exception("Type de base de données non supporté: " . $dbConfig['type']);
            }

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // Création des tables si nécessaire
            $this->createTables();

        } catch (\PDOException $e) {
            throw new \Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    private function createTables()
    {
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    role_id INTEGER NOT NULL,
                    avatar VARCHAR(255),
                    bio TEXT,
                    status VARCHAR(20) DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'suspended')),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME,
                    FOREIGN KEY (role_id) REFERENCES roles(id)
                )
            ",
            'roles' => "
                CREATE TABLE IF NOT EXISTS roles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(50) UNIQUE NOT NULL,
                    display_name VARCHAR(100) NOT NULL,
                    description TEXT,
                    is_system BOOLEAN DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'permissions' => "
                CREATE TABLE IF NOT EXISTS permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) UNIQUE NOT NULL,
                    display_name VARCHAR(150) NOT NULL,
                    description TEXT,
                    module VARCHAR(50) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'role_permissions' => "
                CREATE TABLE IF NOT EXISTS role_permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    role_id INTEGER NOT NULL,
                    permission_id INTEGER NOT NULL,
                    granted BOOLEAN DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (role_id) REFERENCES roles(id),
                    FOREIGN KEY (permission_id) REFERENCES permissions(id),
                    UNIQUE(role_id, permission_id)
                )
            ",
            'audit_logs' => "
                CREATE TABLE IF NOT EXISTS audit_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    action VARCHAR(100) NOT NULL,
                    module VARCHAR(50) NOT NULL,
                    details TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            "
        ];

        foreach ($tables as $tableName => $sql) {
            $this->pdo->exec($sql);
        }

        // Insertion des données de base
        $this->insertDefaultData();
    }

    private function insertDefaultData()
    {
        // Vérifier si les données existent déjà
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM roles");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Insertion des rôles par défaut
            $roles = [
                ['super_admin', 'Super Administrateur', 'Accès complet à toutes les fonctionnalités', 1],
                ['admin', 'Administrateur', 'Gestion de la plateforme et des utilisateurs', 1],
                ['instructor', 'Formateur', 'Création et gestion de cours', 1],
                ['student', 'Étudiant', 'Accès aux cours et formations', 1],
                ['guest', 'Invité', 'Accès limité aux contenus publics', 1]
            ];

            $stmt = $this->pdo->prepare("INSERT INTO roles (name, display_name, description, is_system) VALUES (?, ?, ?, ?)");
            foreach ($roles as $role) {
                $stmt->execute($role);
            }

            // Création du super administrateur par défaut
            $this->createDefaultAdmin();
        }
    }

    private function createDefaultAdmin()
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, role_id) 
            VALUES (?, ?, ?, ?, ?, (SELECT id FROM roles WHERE name = 'super_admin'))
        ");
        
        $stmt->execute([
            'admin',
            'admin@elearning.local',
            password_hash('admin123', PASSWORD_DEFAULT),
            'Super',
            'Admin'
        ]);
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}
?>