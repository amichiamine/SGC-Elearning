<?php
namespace SGC\Core;

/**
 * Gestionnaire de configuration pour SGC E-Learning
 * Charge les configurations depuis les fichiers JSON avec chemins absolus
 */
class Config
{
    private static $instance = null;
    private $config = [];

    private function __construct()
    {
        $this->loadConfig();
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

    private function loadConfig()
    {
        // Configuration de la base de données avec chemin absolu
        $dbConfig = $this->loadJsonConfig('database.json');
        if ($dbConfig) {
            $this->config['database'] = $dbConfig;
        }

        // Configuration de l'application avec chemin absolu
        $appConfig = $this->loadJsonConfig('app.json');
        if ($appConfig) {
            $this->config['app'] = $appConfig;
        }

        // Configuration des rôles et permissions avec chemin absolu
        $rolesConfig = $this->loadJsonConfig('roles.json');
        if ($rolesConfig) {
            $this->config['roles'] = $rolesConfig;
        }
    }

    private function loadJsonConfig($filename)
    {
        // Utilisation de la constante CONFIG_PATH (chemin absolu)
        $filepath = CONFIG_PATH . '/' . $filename;
        
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);

            if ($content === false) {
                error_log("Erreur lors de la lecture du fichier de configuration: $filepath");
                return null;
            }

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erreur JSON dans le fichier $filepath: " . json_last_error_msg());
                return null;
            }

            return $decoded;
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

    /**
     * Vérifie si une configuration existe
     */
    public function has($key)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * Sauvegarde une configuration dans son fichier
     */
    public function save($configName)
    {
        if (!isset($this->config[$configName])) {
            throw new \Exception("Configuration '$configName' non trouvée");
        }

        $filepath = CONFIG_PATH . '/' . $configName . '.json';
        $jsonContent = json_encode($this->config[$configName], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonContent === false) {
            throw new \Exception("Erreur lors de l'encodage JSON pour '$configName'");
        }

        $result = file_put_contents($filepath, $jsonContent);

        if ($result === false) {
            throw new \Exception("Impossible d'écrire le fichier de configuration: $filepath");
        }

        return true;
    }

    /**
     * Recharge toutes les configurations
     */
    public function reload()
    {
        $this->config = [];
        $this->loadConfig();
    }
}
?>