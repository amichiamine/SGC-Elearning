<?php

namespace SGC\Core;

/**
 * Classe de base pour tous les contrôleurs de l'application.
 * Fournit un accès facile au conteneur de dépendances et aux services.
 */
abstract class Controller
{
    protected Container $container;
    protected View $view;
    protected Database $db;
    protected Config $config;
    protected Auth $auth;
    protected Theme $theme;

    /**
     * Le constructeur reçoit le conteneur de dépendances et initialise
     * les services communs pour un accès facile dans les contrôleurs enfants.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        // Raccourcis vers les services les plus courants
        $this->view = $this->container->make(View::class);
        $this->db = $this->container->make(Database::class);
        $this->config = $this->container->make(Config::class);
        $this->auth = $this->container->make(Auth::class);
        $this->theme = $this->container->make(Theme::class);
    }

    /**
     * Méthode de rendu de vue simplifiée.
     * Les contrôleurs enfants peuvent l'utiliser pour rendre un template.
     *
     * @param string $templatePath Le chemin du template relatif au dossier `views` ou `theme`.
     * @param array $data Les données à passer au template.
     */
    protected function render(string $templatePath, array $data = []): void
    {
        try {
            $this->view->render($templatePath, $data);
        } catch (\Exception $e) {
            // Gérer l'erreur de rendu de manière appropriée
            error_log("Erreur de rendu du template: " . $e->getMessage());
            // En mode production, vous pourriez afficher une page d'erreur générique.
            // Pour le développement, il est utile de voir l'erreur.
            echo "<h1>Erreur de rendu</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    /**
     * Redirige vers une autre URL.
     *
     * @param string $url L'URL de destination.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Helper pour rendre le layout de l'admin de manière centralisée.
     */
    protected function renderAdminLayout(string $title, string $content): void
    {
        $this->view->render('theme/templates/admin-layout.html', [
            'title' => $title,
            'content' => $content
        ]);
    }
}