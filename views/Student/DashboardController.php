<?php

namespace SGC\Controllers\Student;

use SGC\Core\Controller;

/**
 * Gère le tableau de bord de l'étudiant.
 */
class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord avec les cours de l'étudiant et sa progression.
     */
    public function index(): void
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        // 1. Récupérer les cours auxquels l'utilisateur est inscrit
        $enrolledCourses = $this->db->fetchAll(
            "SELECT c.id, c.title, c.description, c.image
             FROM courses c
             JOIN enrollments e ON c.id = e.course_id
             WHERE e.user_id = ?",
            [$user['id']]
        );

        // 2. Pour chaque cours, calculer la progression
        foreach ($enrolledCourses as &$course) {
            // Compter le nombre total de leçons dans le cours
            $totalLessons = $this->db->fetch(
                "SELECT COUNT(id) as count FROM lessons WHERE course_id = ?",
                [$course['id']]
            )['count'];

            if ($totalLessons > 0) {
                // Compter le nombre de leçons complétées par l'utilisateur pour ce cours
                $completedLessons = $this->db->fetch(
                    "SELECT COUNT(id) as count FROM lesson_completions WHERE user_id = ? AND course_id = ?",
                    [$user['id'], $course['id']]
                )['count'];

                // Calculer le pourcentage de progression
                $course['progress'] = round(($completedLessons / $totalLessons) * 100);
            } else {
                $course['progress'] = 0; // Pas de leçons, donc 0% de progression
            }
        }

        // 3. Rendre la vue avec les données
        $content = $this->view->render('Student/dashboard.html', [
            'courses' => $enrolledCourses
        ]);

        $this->render('theme/templates/base.html', [
            'title' => 'Mon Tableau de Bord',
            'content' => $content,
            'theme' => $this->theme,
            'user' => $user
        ]);
    }
}