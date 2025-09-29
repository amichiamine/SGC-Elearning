<?php

namespace SGC\Controllers\Admin\Courses;

use SGC\Core\Controller;

/**
 * Gère les opérations CRUD pour les cours dans le panneau d'administration.
 */
class CourseController extends Controller
{
    /**
     * Affiche la liste de tous les cours.
     */
    public function index(): void
    {
        $courses = $this->db->fetchAll("SELECT * FROM courses ORDER BY created_at DESC");
        $content = $this->view->render('Admin/Courses/index.html', ['courses' => $courses]);
        $this->renderAdminLayout('Gestion des Cours', $content);
    }

    /**
     * Affiche le formulaire de création de cours.
     */
    public function create(): void
    {
        $content = $this->view->render('Admin/Courses/create.html', [
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
        $this->renderAdminLayout('Créer un Nouveau Cours', $content);
    }

    /**
     * Enregistre un nouveau cours dans la base de données.
     */
    public function store(): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/courses');
            return;
        }

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'instructor_id' => $_POST['instructor_id'] ?? null, // Gérer le cas où aucun formateur n'est assigné
            'category' => $_POST['category'],
            'level' => $_POST['level'],
            'price' => $_POST['price'],
            'status' => $_POST['status'],
            'featured' => isset($_POST['featured']) ? 1 : 0
        ];

        $this->db->execute(
            "INSERT INTO courses (title, description, instructor_id, category, level, price, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            array_values($data)
        );

        $this->redirect('/admin/courses');
    }

    /**
     * Affiche le formulaire pour éditer un cours.
     */
    public function edit(int $id): void
    {
        $course = $this->db->fetch("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            $this->redirect('/admin/courses');
            return;
        }

        $content = $this->view->render('Admin/Courses/edit.html', [
            'course' => $course,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
        $this->renderAdminLayout('Modifier le Cours', $content);
    }

    /**
     * Met à jour un cours dans la base de données.
     */
    public function update(int $id): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/courses');
            return;
        }

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'instructor_id' => $_POST['instructor_id'] ?? null,
            'category' => $_POST['category'],
            'level' => $_POST['level'],
            'price' => $_POST['price'],
            'status' => $_POST['status'],
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'id' => $id
        ];

        $this->db->execute(
            "UPDATE courses SET title = ?, description = ?, instructor_id = ?, category = ?, level = ?, price = ?, status = ?, featured = ? WHERE id = ?",
            array_values($data)
        );

        $this->redirect('/admin/courses');
    }

    /**
     * Supprime un cours de la base de données.
     */
    public function delete(int $id): void
    {
        // La suppression des leçons et inscriptions associées est gérée par `ON DELETE CASCADE`
        $this->db->execute("DELETE FROM courses WHERE id = ?", [$id]);
        $this->redirect('/admin/courses');
    }

}