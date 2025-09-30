<?php

namespace SGC\Controllers\Home;

use SGC\Core\Controller;

class HomeController extends Controller
{
    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
    }

    /**
     * Display the main home page.
     */
    public function index()
    {
        $data = [
            'title' => 'Accueil - SGC E-Learning Platform',
            'description' => 'Plateforme e-learning corporate avec système de formation moderne',
            'hero_data' => $this->getHeroData(),
            'featured_courses' => $this->getFeaturedCourses(),
            'featured_instructors' => $this->getFeaturedInstructors(),
            'testimonials' => $this->getTestimonials(),
            'announcements' => $this->getAnnouncements(),
            'statistics' => $this->getStatistics()
        ];

        // Use the standardized renderView method from the base controller
        $this->renderView('Home/home.html', $data);
    }

    /**
     * Display the About page.
     */
    public function about()
    {
        $this->renderView('Home/about.html', ['title' => 'À propos']);
    }

    /**
     * Display the Contact page.
     */
    public function contact()
    {
        $this->renderView('Home/contact.html', ['title' => 'Contact']);
    }

    // --- Private methods to fetch data for the homepage ---

    private function getHeroData(): array
    {
        return [
            'title' => 'Bienvenue sur SGC E-Learning',
            'subtitle' => 'La plateforme de formation corporate nouvelle génération',
            'description' => 'Découvrez nos cours interactifs conçus par des experts pour développer vos compétences professionnelles.',
            'cta_text' => 'Commencer maintenant',
            'cta_url' => '/register',
            'background_image' => 'assets/images/hero-bg.jpg',
            'video_url' => null
        ];
    }

    private function getFeaturedCourses(): array
    {
        try {
            $pdo = $this->db->getPDO();
            $stmt = $pdo->query("
                SELECT * FROM courses
                WHERE featured = 1 AND status = 'published'
                ORDER BY created_at DESC
                LIMIT 6
            ");
            $courses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($courses)) {
                return $this->getExampleCourses();
            }
            return $courses;
        } catch (\Exception $e) {
            error_log('Database error fetching featured courses: ' . $e->getMessage());
            return $this->getExampleCourses();
        }
    }

    private function getExampleCourses(): array
    {
        return [
            ['id' => 1, 'title' => 'Management d\'équipe agile', 'description' => 'Apprenez les méthodes modernes...', 'instructor_name' => 'Marie Dubois', 'duration' => '8h', 'level' => 'Intermédiaire', 'price' => '299€', 'rating' => 4.8, 'students_count' => 1250, 'image' => 'assets/images/courses/management.jpg', 'category' => 'Management'],
            ['id' => 2, 'title' => 'Marketing Digital Avancé', 'description' => 'Maîtrisez les dernières techniques...', 'instructor_name' => 'Pierre Martin', 'duration' => '12h', 'level' => 'Avancé', 'price' => '399€', 'rating' => 4.9, 'students_count' => 890, 'image' => 'assets/images/courses/marketing.jpg', 'category' => 'Marketing']
        ];
    }

    private function getFeaturedInstructors(): array
    {
        return [
            ['id' => 1, 'name' => 'Marie Dubois', 'title' => 'Expert en Management', 'bio' => '15 ans d\'expérience...', 'specialties' => ['Management', 'Leadership'], 'courses_count' => 8, 'students_count' => 3200, 'rating' => 4.9, 'avatar' => 'assets/images/instructors/marie-dubois.jpg'],
            ['id' => 2, 'name' => 'Pierre Martin', 'title' => 'Consultant Marketing', 'bio' => 'Ancien directeur marketing...', 'specialties' => ['Marketing Digital', 'E-commerce'], 'courses_count' => 12, 'students_count' => 5600, 'rating' => 4.8, 'avatar' => 'assets/images/instructors/pierre-martin.jpg']
        ];
    }

    private function getTestimonials(): array
    {
        return [
            ['name' => 'Jean-Michel Durand', 'position' => 'Directeur RH, TechCorp', 'content' => 'SGC E-Learning a transformé notre approche de la formation.', 'avatar' => 'assets/images/testimonials/jean-michel.jpg', 'rating' => 5],
            ['name' => 'Caroline Lefevre', 'position' => 'Manager, InnovateSA', 'content' => 'La flexibilité de la plateforme est un atout majeur.', 'avatar' => 'assets/images/testimonials/caroline.jpg', 'rating' => 5]
        ];
    }

    private function getAnnouncements(): array
    {
        return [
            ['id' => 1, 'title' => 'Nouveau : Parcours Certification Management', 'content' => 'Découvrez notre nouveau parcours certifiant.', 'type' => 'new', 'url' => '/courses/certification-management', 'priority' => 1, 'expires_at' => null],
            ['id' => 2, 'title' => 'Webinaire Gratuit - Marketing Digital', 'content' => 'Participez à notre webinaire le 30 septembre.', 'type' => 'event', 'url' => '/webinars/marketing-digital', 'priority' => 2, 'expires_at' => '2025-09-30']
        ];
    }

    private function getStatistics(): array
    {
        return [
            ['number' => '12,000+', 'label' => 'Étudiants Actifs', 'icon' => 'users'],
            ['number' => '150+', 'label' => 'Cours Disponibles', 'icon' => 'courses'],
            ['number' => '50+', 'label' => 'Experts Formateurs', 'icon' => 'instructors'],
            ['number' => '95%', 'label' => 'Taux de Satisfaction', 'icon' => 'satisfaction']
        ];
    }
}