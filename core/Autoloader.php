<?php
namespace SGC\Core;

/**
 * Autoloader PSR-4 pour SGC E-Learning
 * Chargement automatique des classes selon les namespaces
 */
class Autoloader
{
    private $prefixes = [];

    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
        
        // Enregistrement des namespaces SGC avec constantes
        $this->addNamespace('SGC\\Core', CORE_PATH);
        $this->addNamespace('SGC\\Controllers', BASE_PATH . '/controllers');
        $this->addNamespace('SGC\\Models', BASE_PATH . '/models');
    }

    public function addNamespace($prefix, $baseDir)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }
        
        array_push($this->prefixes[$prefix], $baseDir);
    }

    public function loadClass($class)
    {
        // Parcourir les préfixes de namespace enregistrés
        foreach ($this->prefixes as $prefix => $baseDirs) {
            // Vérifier si le nom de la classe commence par le préfixe
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // Non, passer au préfixe suivant
                continue;
            }

            // Obtenir le nom de la classe relative (ex: Application pour SGC\Core\Application)
            $relativeClass = substr($class, $len);

            // Remplacer les séparateurs de namespace par des séparateurs de répertoire
            // dans le nom de la classe relative, et ajouter .php
            $file = str_replace('\\', '/', $relativeClass) . '.php';

            // Essayer de charger le fichier depuis les répertoires de base associés au préfixe
            foreach ($baseDirs as $baseDir) {
                $filePath = $baseDir . $file;
                if ($this->requireFile($filePath)) {
                    // Le fichier a été trouvé et inclus, on arrête
                    return;
                }
            }
        }
    }

    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
?>