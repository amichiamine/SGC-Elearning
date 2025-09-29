<?php

namespace Views\Home;

use Core\View;
use Core\Database;

/**
 * Contrôleur de la vue principale Home
 * Gère l'affichage de la page d'accueil avec thème intégré
 */
class HomeController extends View
{
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
        parent::__construct();
    }

    /**
     * Affichage principal de la page d'accueil
     */
    public function index(): void
    {
        // Récupération des données pour la page d'accueil
        $data = [
            'title' => 'Accueil - SGC E-Learning Platform',
            'description' => 'Plateforme e-learning corporate avec système de formation moderne',
            'show_header' => true,
            'show_footer' => true,
            'extra_css' => [
                'theme/css/views/home.css'
            ],
            'extra_js' => [
                'theme/js/home.js'
            ],
            
            // Données des composants
            'hero_data' => $this->getHeroData(),
            'featured_courses' => $this->getFeaturedCourses(),
            'featured_instructors' => $this->getFeaturedInstructors(),
            'testimonials' => $this->getTestimonials(),
            'announcements' => $this->getAnnouncements(),
            'statistics' => $this->getStatistics()
        ];

        // Rendu du contenu home
        ob_start();
        extract($data);
        include VIEWS_PATH . '/Home/home.html';
        $content = ob_get_clean();
        
        // Ajout du contenu au data pour le template de base
        $data['content'] = $content;
        $data['theme'] = $this->theme;
        
        // Rendu avec le template de base qui contient tous les styles
        $this->render('theme/templates/base.html', $data);
    }

    /**
     * Données de la section hero/bannière principale
     */
    private function getHeroData(): array
    {
        return [
            'title' => 'Bienvenue sur SGC E-Learning',
            'subtitle' => 'La plateforme de formation corporate nouvelle génération',
            'description' => 'Découvrez nos cours interactifs conçus par des experts pour développer vos compétences professionnelles.',
            'cta_text' => 'Commencer maintenant',
            'cta_url' => '/register',
            'background_image' => 'assets/images/hero-bg.jpg',
            'video_url' => null // URL de vidéo si disponible
        ];
    }

    /**
     * Récupération des cours mis en avant
     */
    private function getFeaturedCourses(): array
    {
        try {
            $pdo = $this->database->getPDO();
            $stmt = $pdo->query("
                SELECT * FROM courses 
                WHERE featured = 1 AND status = 'published' 
                ORDER BY created_at DESC 
                LIMIT 6
            ");
            $courses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Si pas de cours en base, retourner des données d'exemple
            if (empty($courses)) {
                return $this->getExampleCourses();
            }
            
            return $courses;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des données d'exemple
            return $this->getExampleCourses();
        }
    }

    /**
     * Cours d'exemple pour démonstration
     */
    private function getExampleCourses(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Management d\'\u00e9quipe agile',
                'description' => 'Apprenez les méthodes modernes de management pour diriger efficacement vos équipes.',
                'instructor_name' => 'Marie Dubois',
                'duration' => '8h',
                'level' => 'Intermédiaire',
                'price' => '299€',
                'rating' => 4.8,
                'students_count' => 1250,
                'image' => 'assets/images/courses/management.jpg',
                'category' => 'Management'
            ],
            [
                'id' => 2,
                'title' => 'Marketing Digital Avancé',
                'description' => 'Maîtrisez les dernières techniques de marketing digital et social media.',
                'instructor_name' => 'Pierre Martin',
                'duration' => '12h',
                'level' => 'Avancé',
                'price' => '399€',
                'rating' => 4.9,
                'students_count' => 890,
                'image' => 'assets/images/courses/marketing.jpg',
                'category' => 'Marketing'
            ],
            [
                'id' => 3,
                'title' => 'Développement Personnel',
                'description' => 'Développez votre confiance en vous et améliorez vos compétences relationnelles.',
                'instructor_name' => 'Sophie Laurent',
                'duration' => '6h',
                'level' => 'Débutant',
                'price' => '199€',
                'rating' => 4.7,
                'students_count' => 2100,
                'image' => 'assets/images/courses/personal.jpg',
                'category' => 'Développement Personnel'
            ],
            [
                'id' => 4,
                'title' => 'Finance d\'Entreprise',
                'description' => 'Comprenez les enjeux financiers et la gestion budgétaire en entreprise.',
                'instructor_name' => 'Thomas Moreau',
                'duration' => '10h',
                'level' => 'Intermédiaire',
                'price' => '349€',
                'rating' => 4.6,
                'students_count' => 670,
                'image' => 'assets/images/courses/finance.jpg',
                'category' => 'Finance'
            ],
            [
                'id' => 5,
                'title' => 'Communication Efficace',
                'description' => 'Améliorez vos présentations et votre communication interpersonnelle.',
                'instructor_name' => 'Anne Petit',
                'duration' => '5h',
                'level' => 'Débutant',
                'price' => '149€',
                'rating' => 4.8,
                'students_count' => 1850,
                'image' => 'assets/images/courses/communication.jpg',
                'category' => 'Communication'
            ],
            [
                'id' => 6,
                'title' => 'Innovation & Créativité',
                'description' => 'Stimulez votre créativité et apprenez les méthodes d\'innovation.',
                'instructor_name' => 'Luc Bernard',
                'duration' => '7h',
                'level' => 'Intermédiaire',
                'price' => '249€',
                'rating' => 4.9,
                'students_count' => 450,
                'image' => 'assets/images/courses/innovation.jpg',
                'category' => 'Innovation'
            ]
        ];
    }

    /**
     * Formateurs mis en avant
     */
    private function getFeaturedInstructors(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Marie Dubois',
                'title' => 'Expert en Management',
                'bio' => '15 ans d\'expérience en management d\'\u00e9quipes internationales.',
                'specialties' => ['Management', 'Leadership', 'Gestion d\'\u00e9quipe'],
                'courses_count' => 8,
                'students_count' => 3200,
                'rating' => 4.9,
                'avatar' => 'assets/images/instructors/marie-dubois.jpg'
            ],
            [
                'id' => 2,
                'name' => 'Pierre Martin',
                'title' => 'Consultant Marketing Digital',
                'bio' => 'Ancien directeur marketing dans le CAC 40, expert en transformation digitale.',
                'specialties' => ['Marketing Digital', 'Social Media', 'E-commerce'],
                'courses_count' => 12,
                'students_count' => 5600,
                'rating' => 4.8,
                'avatar' => 'assets/images/instructors/pierre-martin.jpg'
            ],
            [
                'id' => 3,
                'name' => 'Sophie Laurent',
                'title' => 'Coach Certifiée',
                'bio' => 'Psychologue du travail et coach certifiée ICF, spécialisée en développement personnel.',
                'specialties' => ['Développement Personnel', 'Coaching', 'Bien-être'],
                'courses_count' => 6,
                'students_count' => 4100,
                'rating' => 4.9,
                'avatar' => 'assets/images/instructors/sophie-laurent.jpg'
            ],
            [
                'id' => 4,
                'name' => 'Thomas Moreau',
                'title' => 'Expert Financier',
                'bio' => 'Directeur financier avec 20 ans d\'expérience en entreprise.',
                'specialties' => ['Finance', 'Comptabilité', 'Gestion'],
                'courses_count' => 10,
                'students_count' => 2800,
                'rating' => 4.7,
                'avatar' => 'assets/images/instructors/thomas-moreau.jpg'
            ]
        ];
    }

    /**
     * Témoignages clients
     */
    private function getTestimonials(): array
    {
        return [
            [
                'name' => 'Jean-Michel Durand',
                'position' => 'Directeur RH, TechCorp',
                'content' => 'SGC E-Learning a transformé notre approche de la formation. Les cours sont de qualité exceptionnelle.',
                'avatar' => 'assets/images/testimonials/jean-michel.jpg',
                'rating' => 5
            ],
            [
                'name' => 'Caroline Lefevre',
                'position' => 'Manager, InnovateSA',
                'content' => 'La flexibilité de la plateforme permet à nos équipes de se former selon leurs disponibilités.',
                'avatar' => 'assets/images/testimonials/caroline.jpg',
                'rating' => 5
            ],
            [
                'name' => 'Alain Rousseau',
                'position' => 'CEO, StartupTech',
                'content' => 'Un investissement rentable pour le développement des compétences de nos collaborateurs.',
                'avatar' => 'assets/images/testimonials/alain.jpg',
                'rating' => 5
            ]
        ];
    }

    /**
     * Annonces importantes
     */
    private function getAnnouncements(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Nouveau : Parcours Certification Management',
                'content' => 'Découvrez notre nouveau parcours certifiant en management d\'\u00e9quipe.',
                'type' => 'new',
                'url' => '/courses/certification-management',
                'priority' => 1,
                'expires_at' => null
            ],
            [
                'id' => 2,
                'title' => 'Webinaire Gratuit - Marketing Digital',
                'content' => 'Participez à notre webinaire gratuit le 30 septembre à 14h.',
                'type' => 'event',
                'url' => '/webinars/marketing-digital',
                'priority' => 2,
                'expires_at' => '2025-09-30'
            ],
            [
                'id' => 3,
                'title' => 'Offre Spéciale Rentrée',
                'content' => '20% de réduction sur tous les cours jusqu\'au 15 octobre.',
                'type' => 'promo',
                'url' => '/promotions/rentree',
                'priority' => 3,
                'expires_at' => '2025-10-15'
            ]
        ];
    }

    /**
     * Statistiques de la plateforme
     */
    private function getStatistics(): array
    {
        return [
            [
                'number' => '12,000+',
                'label' => 'Étudiants Actifs',
                'icon' => 'users'
            ],
            [
                'number' => '150+',
                'label' => 'Cours Disponibles',
                'icon' => 'courses'
            ],
            [
                'number' => '50+',
                'label' => 'Experts Formateurs',
                'icon' => 'instructors'
            ],
            [
                'number' => '95%',
                'label' => 'Taux de Satisfaction',
                'icon' => 'satisfaction'
            ]
        ];
    }
}