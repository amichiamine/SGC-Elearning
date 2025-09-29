<?php

namespace SGC\Controllers\Admin\Courses;

use SGC\Core\Controller;

/**
 * Gère les opérations CRUD pour les cours dans le panneau d'administration.
 */
class CourseController extends Controller
{
    private const UPLOAD_DIR = BASE_PATH . '/assets/uploads/course_documents/';

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
            'category' => $_POST['category'],
            'level' => $_POST['level'],
            'price' => $_POST['price'],
            'status' => $_POST['status']
        ];

        $this->db->execute(
            "INSERT INTO courses (title, description, category, level, price, status) VALUES (?, ?, ?, ?, ?, ?)",
            array_values($data)
        );

        $this->redirect('/admin/courses');
    }

    /**
     * Affiche le formulaire pour éditer un cours et ses documents.
     */
    public function edit(int $id): void
    {
        $course = $this->db->fetch("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            $this->redirect('/admin/courses');
            return;
        }

        $documents = $this->db->fetchAll("SELECT * FROM course_documents WHERE course_id = ? ORDER BY uploaded_at DESC", [$id]);

        $content = $this->view->render('Admin/Courses/edit.html', [
            'course' => $course,
            'documents' => $documents,
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
            'category' => $_POST['category'],
            'level' => $_POST['level'],
            'price' => $_POST['price'],
            'status' => $_POST['status'],
            'id' => $id
        ];

        $this->db->execute(
            "UPDATE courses SET title = ?, description = ?, category = ?, level = ?, price = ?, status = ? WHERE id = ?",
            array_values($data)
        );

        $this->redirect('/admin/courses/edit/' . $id);
    }

    /**
     * Gère le téléversement d'un document pour un cours.
     */
    public function uploadDocument(int $courseId): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/courses/edit/' . $courseId);
            return;
        }

        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['document']['name']);
            $fileTmpName = $_FILES['document']['tmp_name'];
            $fileSize = $_FILES['document']['size'];

            // Créer un nom de fichier unique pour éviter les conflits
            $safeFileName = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $fileName);
            $filePath = self::UPLOAD_DIR . $safeFileName;

            if (move_uploaded_file($fileTmpName, $filePath)) {
                $this->db->execute(
                    "INSERT INTO course_documents (course_id, file_name, file_path, file_size) VALUES (?, ?, ?, ?)",
                    [$courseId, $fileName, $filePath, $fileSize]
                );
            }
        }

        $this->redirect('/admin/courses/edit/' . $courseId);
    }

    /**
     * Supprime un document de cours.
     */
    public function deleteDocument(int $courseId, int $documentId): void
    {
        $document = $this->db->fetch("SELECT * FROM course_documents WHERE id = ? AND course_id = ?", [$documentId, $courseId]);

        if ($document) {
            // Supprimer le fichier physique
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            // Supprimer l'enregistrement en base de données
            $this->db->execute("DELETE FROM course_documents WHERE id = ?", [$documentId]);
        }

        $this->redirect('/admin/courses/edit/' . $courseId);
    }

    /**
     * Supprime un cours de la base de données.
     */
    public function delete(int $id): void
    {
        // La suppression des documents associés est gérée par `ON DELETE CASCADE`
        $this->db->execute("DELETE FROM courses WHERE id = ?", [$id]);
        $this->redirect('/admin/courses');
    }

    /**
     * Helper pour rendre le layout de l'admin.
     */
    private function renderAdminLayout(string $title, string $content): void
    {
        $this->view->render('theme/templates/admin-layout.html', [
            'title' => $title,
            'content' => $content
        ]);
    }
}