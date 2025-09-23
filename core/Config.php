<?php
namespace Core;

/**
 * Gestionnaire de configuration
 * Charge les configurations depuis les fichiers JSON
 */
class Config
{
    private $config = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    private function loadConfig()
    {
        // Configuration de la base de données
        $dbConfig = $this->loadJsonConfig('database.json');
        if ($dbConfig) {
            $this->config['database'] = $dbConfig;
        }

        // Configuration de l'application
        $appConfig = $this->loadJsonConfig('app.json');
        if ($appConfig) {
            $this->config['app'] = $appConfig;
        }

        // Configuration des rôles et permissions
        $rolesConfig = $this->loadJsonConfig('roles.json');
        if ($rolesConfig) {
            $this->config['roles'] = $rolesConfig;
        }
    }

    private function loadJsonConfig($filename)
    {
        $filepath = CONFIG_PATH . '/' . $filename;
        
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            return json_decode($content, true);
        }
        
        return null;
    }

    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }
}
?>