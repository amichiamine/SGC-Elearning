<?php

namespace SGC\Controllers\Home;

use SGC\Core\Controller;

/**
 * Contrôleur de la page d'accueil.
 */
class HomeController extends Controller
{
    /**
     * Affiche la page d'accueil.
     */
    public function index(): void
    {
        // Les données sont actuellement codées en dur, comme dans la version originale.
        // Une évolution future pourrait les charger depuis la base de données.
        $data = [
            'title' => 'Accueil - SGC E-Learning Platform',
            'hero_data' => $this->getHeroData(),
            'statistics' => $this->getStatistics(),
            'announcements' => [], // Placeholder
            'featured_courses' => $this->getExampleCourses()
        ];

        // Rendu du contenu de la page d'accueil
        ob_start();
        extract($data);
        // Le `View` service est maintenant accédé via `$this->view`
        $this->view->render('Home/home.html', $data);
        $content = ob_get_clean();
        
        // Rendu du layout principal avec le contenu de la page
        $this->render('theme/templates/base.html', [
            'title' => $data['title'],
            'content' => $content,
            'theme' => $this->theme, // Fournir l'objet thème au layout
            'user' => $this->auth->getUser()
        ]);
    }

    // Les méthodes suivantes sont des stubs de données comme dans le code original.
    private function getHeroData(): array
    {
        return [
            'title' => 'Bienvenue sur SGC E-Learning',
            'subtitle' => 'La plateforme de formation corporate nouvelle génération',
            'description' => 'Découvrez nos cours interactifs conçus par des experts.',
            'cta_text' => 'Commencer maintenant',
            'cta_url' => '/register',
        ];
    }

    private function getStatistics(): array
    {
        return [
            ['number' => '12,000+', 'label' => 'Étudiants Actifs', 'icon' => 'users'],
            ['number' => '150+', 'label' => 'Cours Disponibles', 'icon' => 'courses'],
        ];
    }

    private function getExampleCourses(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Management d\'équipe agile',
                'description' => 'Apprenez les méthodes modernes de management.',
                'instructor_name' => 'Marie Dubois',
                'level' => 'Intermédiaire',
                'price' => '299€',
                'rating' => 4.8,
                'students_count' => 1250,
                'image' => ASSETS_URL . '/images/courses/management.jpg',
                'category' => 'Management'
            ],
            [
                'id' => 2,
                'title' => 'Marketing Digital Avancé',
                'description' => 'Maîtrisez les dernières techniques de marketing.',
                'instructor_name' => 'Pierre Martin',
                'level' => 'Avancé',
                'price' => '399€',
                'rating' => 4.9,
                'students_count' => 890,
                'image' => ASSETS_URL . '/images/courses/marketing.jpg',
                'category' => 'Marketing'
            ]
        ];
    }
}