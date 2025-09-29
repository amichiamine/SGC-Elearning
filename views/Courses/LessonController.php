<?php

namespace SGC\Controllers\Courses;

use SGC\Core\Controller;

/**
 * Gère les actions liées aux leçons, comme leur complétion.
 */
class LessonController extends Controller
{
    /**
     * Marque une leçon comme terminée pour l'utilisateur connecté.
     */
    public function complete(int $lessonId): void
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        // Récupérer l'ID du cours pour la redirection
        $lesson = $this->db->fetch("SELECT course_id FROM lessons WHERE id = ?", [$lessonId]);
        if (!$lesson) {
            $this->redirect('/'); // Leçon non trouvée
            return;
        }
        $courseId = $lesson['course_id'];

        // Vérifier que l'utilisateur est bien inscrit au cours
        $isEnrolled = $this->db->fetch(
            "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?",
            [$user['id'], $courseId]
        );
        if (!$isEnrolled) {
            $this->redirect('/courses/show/' . $courseId); // Non inscrit
            return;
        }

        // Insérer l'enregistrement de complétion (ignore si déjà présent grâce à la contrainte UNIQUE)
        $this->db->execute(
            "INSERT OR IGNORE INTO lesson_completions (user_id, lesson_id, course_id) VALUES (?, ?, ?)",
            [$user['id'], $lessonId, $courseId]
        );

        // Rediriger vers la page du cours
        // TODO: Ajouter un message flash de succès
        $this->redirect('/courses/show/' . $courseId);
    }
}