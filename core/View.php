<?php
namespace SGC\Core;

/**
 * Gestionnaire de vue
 * Gère le rendu des templates avec chemins absolus
 */
class View
{
    protected $theme;
    protected $data = [];

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Rend une vue avec des données
     */
    public function render($template, $data = [])
    {
        $this->data = array_merge($this->data, $data);
        
        // Détermination du chemin du template avec constantes absolues
        $templatePath = $this->resolveTemplatePath($template);
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template non trouvé: $template (chemin: $templatePath)");
        }
        
        // Extraction des variables pour le template
        extract($this->data);
        $theme = $this->theme;
        
        // Rendu du template
        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        echo ob_get_clean();
    }
    
    /**
     * Résout le chemin d'un template avec constantes absolues
     */
    private function resolveTemplatePath($template)
    {
        // Chemins possibles avec constantes absolues
        $possiblePaths = [
            THEME_PATH . '/templates/' . $template . '.html',
            THEME_PATH . '/templates/' . $template . '.php', 
            VIEWS_PATH . '/' . $template . '.php',
            VIEWS_PATH . '/' . $template . '.html'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Retourne le premier chemin par défaut pour les messages d'erreur
        return $possiblePaths[0];
    }
    
    /**
     * Charge une feuille de style CSS avec chemin absolu
     */
    public function loadCSS($style)
    {
        $cssPath = THEME_PATH . '/css/' . $style . '.css';
        
        if (file_exists($cssPath)) {
            return $cssPath;
        }
        
        return null;
    }
    
    /**
     * Charge un script JavaScript avec chemin absolu
     */
    public function loadJS($script)
    {
        $jsPath = THEME_PATH . '/js/' . $script . '.js';
        
        if (file_exists($jsPath)) {
            return $jsPath;
        }
        
        return null;
    }
    
    /**
     * Inclut un partial avec chemin absolu
     */
    public function includePartial($partial, $data = [])
    {
        $partialPath = THEME_PATH . '/templates/partials/' . $partial . '.html';
        
        if (!file_exists($partialPath)) {
            $partialPath = VIEWS_PATH . '/partials/' . $partial . '.php';
        }
        
        if (file_exists($partialPath)) {
            $oldData = $this->data;
            $this->data = array_merge($this->data, $data);
            
            extract($this->data);
            include $partialPath;
            
            $this->data = $oldData;
        }
    }
    
    /**
     * Définit des données pour la vue
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }
    
    /**
     * Obtient les données de la vue
     */
    public function getData($key = null)
    {
        if ($key === null) {
            return $this->data;
        }
        
        return $this->data[$key] ?? null;
    }
    
    /**
     * Échappe les données pour l'affichage HTML
     */
    public function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
?>