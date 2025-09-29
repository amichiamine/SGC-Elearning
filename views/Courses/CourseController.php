<?php

namespace SGC\Controllers\Courses;

use SGC\Core\Controller;

/**
 * Gère l'affichage public des cours.
 */
class CourseController extends Controller
{
    /**
     * Affiche la liste de tous les cours publiés.
     */
    public function index(): void
    {
        $courses = $this->db->fetchAll("SELECT * FROM courses WHERE status = 'published' ORDER BY created_at DESC");

        $content = $this->view->render('Courses/index.html', [
            'courses' => $courses
        ]);

        $this->renderPublicLayout('Tous les Cours', $content);
    }

    /**
     * Affiche la page de détail d'un cours spécifique.
     */
    public function show(int $id): void
    {
        $course = $this->db->fetch("SELECT * FROM courses WHERE id = ? AND status = 'published'", [$id]);

        if (!$course) {
            // Gérer le cas où le cours n'est pas trouvé ou n'est pas publié
            $this->redirect('/courses');
            return;
        }

        // TODO: Récupérer les leçons et autres informations relatives au cours
        $lessons = $this->db->fetchAll("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_index ASC", [$id]);

        $content = $this->view->render('Courses/show.html', [
            'course' => $course,
            'lessons' => $lessons
        ]);

        $this->renderPublicLayout($course['title'], $content);
    }

    /**
     * Helper pour rendre le layout public (base.html).
     */
    private function renderPublicLayout(string $title, string $content): void
    {
        $this->view->render('theme/templates/base.html', [
            'title' => $title,
            'content' => $content
        ]);
    }
}