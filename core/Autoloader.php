<?php
namespace Core;

/**
 * Autoloader PSR-4 pour le chargement automatique des classes
 * Respecte la politique "Zero Chemins Absolus" avec constantes
 */
class Autoloader
{
    private $prefixes = [];

    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
        
        // Enregistrement des namespaces avec constantes absolues
        $this->addNamespace('Core', CORE_PATH);
        $this->addNamespace('Views', VIEWS_PATH);
        $this->addNamespace('Config', CONFIG_PATH);
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
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            
            $prefix = rtrim($prefix, '\\');
        }
        
        // Fallback pour les classes sans namespace (utilise constantes)
        return $this->loadLegacyClass($class);
    }

    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }
        
        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
     * Chargement des classes legacy sans namespace (avec constantes absolues)
     */
    protected function loadLegacyClass($className)
    {
        $possiblePaths = [
            VIEWS_PATH . '/' . $className . '.php',
            CORE_PATH . '/' . $className . '.php',
            BASE_PATH . '/controllers/' . $className . '.php',
            BASE_PATH . '/models/' . $className . '.php'
        ];
        
        foreach ($possiblePaths as $file) {
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        return false;
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