<?php

namespace SGC\Core;

/**
 * Gestionnaire de configuration pour SGC E-Learning.
 * Charge les configurations depuis les fichiers JSON.
 */
class Config
{
    private array $config = [];

    /**
     * Le constructeur charge toutes les configurations nécessaires.
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Charge les fichiers de configuration JSON depuis le répertoire /config.
     */
    private function loadConfig(): void
    {
        $configFiles = ['app', 'database', 'roles', 'routes'];

        foreach ($configFiles as $file) {
            $configData = $this->loadJsonConfig("{$file}.json");
            if ($configData) {
                $this->config[$file] = $configData;
            }
        }
    }

    /**
     * Charge et décode un fichier de configuration JSON.
     *
     * @param string $filename
     * @return array|null
     */
    private function loadJsonConfig(string $filename): ?array
    {
        $filepath = CONFIG_PATH . '/' . $filename;

        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            error_log("Erreur de lecture du fichier de configuration: $filepath");
            return null;
        }
        
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur JSON dans le fichier $filepath: " . json_last_error_msg());
            return null;
        }
        
        return $decoded;
    }

    /**
     * Récupère une valeur de configuration en utilisant la notation par points.
     *
     * @param string $key La clé (ex: 'database.host').
     * @param mixed $default La valeur par défaut si la clé n'est pas trouvée.
     * @return mixed
     */
    public function get(string $key, $default = null)
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
}