<?php

namespace SGC\Controllers\Admin\Quizzes;

use SGC\Core\Controller;

/**
 * Gère les opérations CRUD pour les quiz dans le panneau d'administration.
 */
class QuizController extends Controller
{
    /**
     * Affiche la liste de tous les quiz.
     */
    public function index(): void
    {
        $quizzes = $this->db->fetchAll("
            SELECT q.id, q.title, c.title as course_title
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            ORDER BY q.created_at DESC
        ");
        $content = $this->view->render('Admin/Quizzes/index.html', ['quizzes' => $quizzes]);
        $this->renderAdminLayout('Gestion des Quiz', $content);
    }

    /**
     * Affiche le formulaire de création de quiz.
     */
    public function create(): void
    {
        $courses = $this->db->fetchAll("SELECT id, title FROM courses ORDER BY title ASC");
        $content = $this->view->render('Admin/Quizzes/create.html', [
            'courses' => $courses,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
        $this->renderAdminLayout('Créer un Nouveau Quiz', $content);
    }

    /**
     * Enregistre un nouveau quiz.
     */
    public function store(): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/quizzes');
            return;
        }

        $this->db->execute(
            "INSERT INTO quizzes (title, description, course_id) VALUES (?, ?, ?)",
            [$_POST['title'], $_POST['description'], $_POST['course_id']]
        );

        $this->redirect('/admin/quizzes');
    }

    /**
     * Affiche la page de gestion d'un quiz (questions et réponses).
     */
    public function manage(int $id): void
    {
        $quiz = $this->db->fetch("SELECT * FROM quizzes WHERE id = ?", [$id]);
        if (!$quiz) {
            $this->redirect('/admin/quizzes');
            return;
        }

        $questions = $this->db->fetchAll("
            SELECT q.* FROM questions q WHERE q.quiz_id = ? ORDER BY q.order_index ASC
        ", [$id]);

        foreach ($questions as &$question) {
            $question['choices'] = $this->db->fetchAll("SELECT * FROM choices WHERE question_id = ?", [$question['id']]);
        }

        $content = $this->view->render('Admin/Quizzes/manage.html', [
            'quiz' => $quiz,
            'questions' => $questions,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
        $this->renderAdminLayout('Gérer le Quiz : ' . $quiz['title'], $content);
    }

    /**
     * Ajoute une question à un quiz.
     */
    public function addQuestion(int $quizId): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/quizzes/manage/' . $quizId);
            return;
        }

        $this->db->execute(
            "INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)",
            [$quizId, $_POST['question_text']]
        );
        $questionId = $this->db->lastInsertId();

        // Ajouter les choix
        foreach ($_POST['choices'] as $index => $choiceText) {
            if (!empty($choiceText)) {
                $isCorrect = isset($_POST['is_correct']) && $_POST['is_correct'] == $index;
                $this->db->execute(
                    "INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)",
                    [$questionId, $choiceText, $isCorrect ? 1 : 0]
                );
            }
        }

        $this->redirect('/admin/quizzes/manage/' . $quizId);
    }

    /**
     * Supprime une question.
     */
    public function deleteQuestion(int $quizId, int $questionId): void
    {
        $this->db->execute("DELETE FROM questions WHERE id = ? AND quiz_id = ?", [$questionId, $quizId]);
        $this->redirect('/admin/quizzes/manage/' . $quizId);
    }

    /**
     * Supprime un quiz.
     */
    public function delete(int $id): void
    {
        $this->db->execute("DELETE FROM quizzes WHERE id = ?", [$id]);
        $this->redirect('/admin/quizzes');
    }

}