<?php

namespace SGC\Controllers\Courses;

use SGC\Core\Controller;

/**
 * Gère l'inscription des utilisateurs aux cours.
 */
class EnrollmentController extends Controller
{
    /**
     * Inscrit l'utilisateur connecté au cours spécifié.
     */
    public function enroll(int $courseId): void
    {
        // Le middleware 'auth' garantit que l'utilisateur est connecté.
        $user = $this->auth->getUser();
        if (!$user) {
            // Sécurité additionnelle, même si le middleware devrait déjà bloquer.
            $this->redirect('/login');
            return;
        }

        $userId = $user['id'];

        // Vérifier si l'utilisateur n'est pas déjà inscrit
        $existingEnrollment = $this->db->fetch(
            "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?",
            [$userId, $courseId]
        );

        if ($existingEnrollment) {
            // L'utilisateur est déjà inscrit, le rediriger.
            // TODO: Ajouter un message flash pour informer l'utilisateur.
            $this->redirect('/courses/show/' . $courseId);
            return;
        }

        // Inscrire l'utilisateur
        $this->db->execute(
            "INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)",
            [$userId, $courseId]
        );

        // Mettre à jour le compteur d'étudiants sur le cours
        $this->db->execute(
            "UPDATE courses SET students_count = students_count + 1 WHERE id = ?",
            [$courseId]
        );

        // TODO: Ajouter un message flash de succès.
        // Rediriger vers un tableau de bord "Mes Cours" qui sera créé plus tard.
        // Pour l'instant, rediriger vers la page du cours.
        $this->redirect('/courses/show/' . $courseId);
    }
}