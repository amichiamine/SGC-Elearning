<?php

/**
 * Script de migration de base de données simple pour SGC E-Learning.
 *
 * Utilisation en ligne de commande : php migrate.php
 */

// --- Bootstrap minimal de l'application ---
// On a besoin des constantes et de l'autoloader pour accéder au service Database.

echo "Démarrage du script de migration...\n";

define('BASE_PATH', __DIR__);
define('CORE_PATH', BASE_PATH . '/core');
define('CONFIG_PATH', BASE_PATH . '/config');
define('VIEWS_PATH', BASE_PATH . '/views'); // Ajout de la constante manquante
define('DATABASE_PATH', BASE_PATH . '/database');
define('MIGRATIONS_PATH', DATABASE_PATH . '/migrations');

require_once CORE_PATH . '/Autoloader.php';

// On n'a pas besoin du conteneur complet, on instancie juste ce qu'il nous faut.
$autoloader = new SGC\Core\Autoloader();
$autoloader->register();

try {
    echo "Chargement de la configuration...\n";
    $config = new SGC\Core\Config();

    echo "Connexion à la base de données...\n";
    $db = new SGC\Core\Database($config);
    $pdo = $db->getPdo();
    echo "Connexion réussie.\n";

    // 1. S'assurer que la table des migrations existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) UNIQUE NOT NULL,
            ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table 'migrations' vérifiée/créée.\n";

    // 2. Obtenir les migrations déjà exécutées
    $ranMigrations = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    // 3. Scanner le dossier des migrations pour trouver les nouveaux fichiers
    if (!is_dir(MIGRATIONS_PATH)) {
        mkdir(MIGRATIONS_PATH, 0755, true);
    }
    $migrationFiles = scandir(MIGRATIONS_PATH);

    $migrationsToRun = array_filter($migrationFiles, function ($file) use ($ranMigrations) {
        // On ne traite que les fichiers .sql qui n'ont pas encore été exécutés
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql' && !in_array($file, $ranMigrations);
    });

    sort($migrationsToRun);

    if (empty($migrationsToRun)) {
        echo "La base de données est à jour. Aucune nouvelle migration à exécuter.\n";
        exit(0);
    }

    echo "Nouvelles migrations trouvées :\n";
    foreach ($migrationsToRun as $migration) {
        echo "- " . $migration . "\n";
    }

    // 4. Exécuter chaque nouvelle migration
    foreach ($migrationsToRun as $migrationFile) {
        echo "\nExécution de la migration : $migrationFile ... ";

        $filePath = MIGRATIONS_PATH . '/' . $migrationFile;
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new Exception("Impossible de lire le fichier de migration : $migrationFile");
        }

        try {
            $pdo->beginTransaction();
            $pdo->exec($sql);

            // Enregistrer la migration comme exécutée
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationFile]);

            $pdo->commit();
            echo "SUCCÈS.\n";

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "ÉCHEC.\n";
            throw new Exception("Erreur lors de l'exécution de $migrationFile: " . $e->getMessage());
        }
    }

    echo "\nToutes les nouvelles migrations ont été exécutées avec succès.\n";

} catch (Exception $e) {
    echo "\nERREUR DE MIGRATION : " . $e->getMessage() . "\n";
    exit(1);
}