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
        $this->addNamespace('SGC\\Controllers', VIEWS_PATH);
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
        
        // Fallback pour compatibilité ascendante
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
     * Chargement des classes legacy (avec constantes)
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