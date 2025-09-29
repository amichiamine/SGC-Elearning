<?php

/**
 * Script de migration de base de données simple pour SGC E-Learning
 *
 * Utilisation en ligne de commande :
 * php migrate.php
 */

// --- Bootstrap de l'application pour accès aux services ---
require_once __DIR__ . '/core/Autoloader.php';
require_once __DIR__ . '/core/Container.php';

// Définition des constantes de base
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('DATABASE_PATH', BASE_PATH . '/database');
define('MIGRATIONS_PATH', DATABASE_PATH . '/migrations');

$autoloader = new SGC\Core\Autoloader();
$autoloader->register();

$container = new SGC\Core\Container();

// Enregistrement des services nécessaires pour la migration
$container->singleton(SGC\Core\Config::class);
$container->singleton(SGC\Core\Database::class, function ($c) {
    return new SGC\Core\Database($c->make(SGC\Core\Config::class));
});

try {
    /** @var SGC\Core\Database $db */
    $db = $container->make(SGC\Core\Database::class);
    $pdo = $db->getPdo();

    echo "Connexion à la base de données réussie.\n";

    // --- 1. Créer la table des migrations si elle n'existe pas ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) UNIQUE NOT NULL,
            ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "Table 'migrations' vérifiée/créée.\n";

    // --- 2. Obtenir les migrations déjà exécutées ---
    $ranMigrationsStmt = $pdo->query("SELECT migration FROM migrations");
    $ranMigrations = $ranMigrationsStmt->fetchAll(PDO::FETCH_COLUMN);

    // --- 3. Scanner le dossier des migrations ---
    $migrationFiles = scandir(MIGRATIONS_PATH);
    if ($migrationFiles === false) {
        throw new Exception("Impossible de scanner le dossier des migrations.");
    }

    $migrationsToRun = array_filter($migrationFiles, function ($file) use ($ranMigrations) {
        return preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.sql$/', $file) && !in_array($file, $ranMigrations);
    });

    sort($migrationsToRun);

    if (empty($migrationsToRun)) {
        echo "La base de données est à jour. Aucune nouvelle migration à exécuter.\n";
        exit(0);
    }

    echo "Nouvelles migrations à exécuter :\n";
    foreach ($migrationsToRun as $migration) {
        echo "- " . $migration . "\n";
    }

    // --- 4. Exécuter chaque nouvelle migration ---
    foreach ($migrationsToRun as $migrationFile) {
        echo "\nExécution de la migration : $migrationFile ...\n";

        $filePath = MIGRATIONS_PATH . '/' . $migrationFile;
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new Exception("Impossible de lire le fichier de migration : $migrationFile");
        }

        try {
            $pdo->beginTransaction();

            $pdo->exec($sql);

            // Enregistrer la migration dans la table des migrations
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationFile]);

            $pdo->commit();
            echo "Migration $migrationFile exécutée avec succès.\n";

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "ERREUR lors de l'exécution de $migrationFile. Annulation des modifications.\n";
            throw $e;
        }
    }

    echo "\nToutes les nouvelles migrations ont été exécutées avec succès.\n";

} catch (Exception $e) {
    echo "\nERREUR DE MIGRATION : " . $e->getMessage() . "\n";
    exit(1);
}