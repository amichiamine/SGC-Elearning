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

        $this->render('theme/templates/base.html', [
            'title' => 'Tous les Cours',
            'content' => $content,
            'theme' => $this->theme,
            'user' => $this->auth->getUser()
        ]);
    }

    /**
     * Affiche la page de détail d'un cours spécifique.
     */
    public function show(int $id): void
    {
        $course = $this->db->fetch("SELECT * FROM courses WHERE id = ? AND status = 'published'", [$id]);

        if (!$course) {
            $this->redirect('/courses');
            return;
        }

        // Récupérer les leçons du cours
        $lessons = $this->db->fetchAll("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_index ASC", [$id]);

        // Récupérer les documents du cours
        $documents = $this->db->fetchAll("SELECT * FROM course_documents WHERE course_id = ? ORDER BY uploaded_at DESC", [$id]);

        // Si l'utilisateur est connecté, vérifier les leçons complétées et s'il est inscrit
        $completedLessonIds = [];
        $isEnrolled = false;
        if ($this->auth->isLoggedIn()) {
            $userId = $this->auth->getUser()['id'];
            $completed = $this->db->fetchAll(
                "SELECT lesson_id FROM lesson_completions WHERE user_id = ? AND course_id = ?",
                [$userId, $id]
            );
            $completedLessonIds = array_column($completed, 'lesson_id');

            $enrollment = $this->db->fetch("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?", [$userId, $id]);
            $isEnrolled = (bool)$enrollment;
        }

        $content = $this->view->render('Courses/show.html', [
            'course' => $course,
            'lessons' => $lessons,
            'documents' => $documents,
            'completedLessonIds' => $completedLessonIds,
            'isEnrolled' => $isEnrolled
        ]);

        $this->render('theme/templates/base.html', [
            'title' => $course['title'],
            'content' => $content,
            'theme' => $this->theme,
            'user' => $this->auth->getUser(),
            'extra_js' => ['theme/js/chat.js'] // Charger le script du chat
        ]);
    }
}