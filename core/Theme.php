<?php

namespace SGC\Core;

/**
 * Gestionnaire de thème centralisé SGC E-Learning
 * Gère la configuration, les assets et l'intégration du thème claymorphism
 */
class Theme
{
    private array $config;
    private string $themePath;
    private array $loadedAssets = [];

    public function __construct()
    {
        $this->themePath = THEME_PATH . '/';
        $this->loadConfig();
    }

    /**
     * Charger la configuration complète du thème
     */
    private function loadConfig(): void
    {
        $configFiles = [
            'theme' => THEME_PATH . '/config/theme.json',
            'colors' => THEME_PATH . '/config/colors.json',
            'typography' => THEME_PATH . '/config/typography.json',
            'spacing' => THEME_PATH . '/config/spacing.json',
            'components' => THEME_PATH . '/config/components.json',
            'layouts' => THEME_PATH . '/config/layouts.json',
            'animations' => THEME_PATH . '/config/animations.json'
        ];

        $this->config = [];
        
        foreach ($configFiles as $key => $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $this->config[$key] = json_decode($content, true);
            }
        }
    }

    /**
     * Obtenir la configuration complète ou une section spécifique
     */
    public function getConfig(?string $section = null): array
    {
        if ($section) {
            return $this->config[$section] ?? [];
        }
        return $this->config;
    }

    /**
     * Obtenir les couleurs du thème
     */
    public function getColors(): array
    {
        return $this->config['colors'] ?? [];
    }

    /**
     * Obtenir une couleur spécifique
     */
    public function getColor(string $key): ?string
    {
        $colors = $this->getColors();
        $keys = explode('.', $key);
        $value = $colors;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return is_string($value) ? $value : null;
    }

    /**
     * Obtenir les assets CSS requis
     */
    public function getCSSAssets(): array
    {
        return [
            THEME_PATH . '/css/variables.css',
            THEME_PATH . '/css/reset.css',
            THEME_PATH . '/css/claymorphism.css',
            THEME_PATH . '/css/components/buttons.css',
            THEME_PATH . '/css/components/forms.css',
            THEME_PATH . '/css/components/cards.css',
            THEME_PATH . '/icons/icons.css'
        ];
    }

    /**
     * Obtenir les assets JavaScript requis
     */
    public function getJSAssets(): array
    {
        return [
            THEME_PATH . '/js/theme.js'
        ];
    }

    /**
     * Générer les balises link pour CSS
     */
    public function renderCSSLinks(array $extraCSS = []): string
    {
        $css = array_merge($this->getCSSAssets(), $extraCSS);
        $links = [];
        
        foreach ($css as $file) {
            if (file_exists($file)) {
                $timestamp = filemtime($file);
                $links[] = '<link rel="stylesheet" href="' . $file . '?v=' . $timestamp . '">';
            }
        }
        
        return implode("\n    ", $links);
    }

    /**
     * Générer les balises script pour JavaScript
     */
    public function renderJSLinks(array $extraJS = []): string
    {
        $js = array_merge($this->getJSAssets(), $extraJS);
        $scripts = [];
        
        foreach ($js as $file) {
            if (file_exists($file)) {
                $timestamp = filemtime($file);
                $scripts[] = '<script src="' . $file . '?v=' . $timestamp . '"></script>';
            }
        }
        
        return implode("\n    ", $scripts);
    }

    /**
     * Rendu d'un template avec le thème
     */
    public function render(string $template, array $data = []): string
    {
        $templatePath = THEME_PATH . '/templates/' . $template . '.html';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template non trouvé : $templatePath");
        }

        // Extraire les données pour les rendre disponibles dans le template
        extract($data);
        
        // Ajouter les données du thème
        $theme = $this;
        $themeConfig = $this->config;
        
        // Cache de sortie pour capturer le contenu
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Générer des variables CSS dynamiques
     */
    public function generateCSSVariables(): string
    {
        $colors = $this->getColors();
        $variables = [];
        
        // Générer les variables CSS à partir de la config
        if (isset($colors['primary'])) {
            foreach ($colors['primary'] as $key => $value) {
                $variables[] = "--color-$key: $value;";
            }
        }
        
        if (isset($colors['gradients'])) {
            foreach ($colors['gradients'] as $key => $value) {
                $variables[] = "--gradient-$key: $value;";
            }
        }
        
        return ":root {\n  " . implode("\n  ", $variables) . "\n}";
    }

    /**
     * Obtenir les métadonnées du thème
     */
    public function getMetadata(): array
    {
        return $this->config['theme'] ?? [];
    }

    /**
     * Vérifier si le thème est en mode responsive
     */
    public function isResponsive(): bool
    {
        return $this->config['theme']['responsive'] ?? true;
    }

    /**
     * Obtenir le nom du thème
     */
    public function getName(): string
    {
        return $this->config['theme']['name'] ?? 'SGC Theme';
    }

    /**
     * Obtenir la version du thème
     */
    public function getVersion(): string
    {
        return $this->config['theme']['version'] ?? '1.0.0';
    }

    /**
     * Générer les en-têtes de cache pour les assets
     */
    public function getCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
    }

    /**
     * Obtenir l'URL d'un asset avec versioning
     */
    public function asset(string $path): string
    {
        $fullPath = THEME_PATH . '/' . $path;
        $timestamp = file_exists($fullPath) ? filemtime($fullPath) : time();
        return $fullPath . '?v=' . $timestamp;
    }

    /**
     * Minifier le CSS en production
     */
    public function minifyCSS(string $css): string
    {
        // Supprimer les commentaires
        $css = preg_replace('/\/\*[^*]*\*+([^/*][^*]*\*+)*\//', '', $css);
        // Supprimer les espaces inutiles
        $css = preg_replace('/\s+/', ' ', $css);
        // Supprimer les espaces autour des caractères spéciaux
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        return trim($css);
    }

    /**
     * Obtenir les breakpoints responsive
     */
    public function getBreakpoints(): array
    {
        return $this->config['spacing']['breakpoints'] ?? [];
    }

    /**
     * Générer les métadonnées viewport pour responsive
     */
    public function getViewportMeta(): string
    {
        if ($this->isResponsive()) {
            return '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        }
        return '';
    }
}