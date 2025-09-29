<?php

namespace SGC\Controllers\Quiz;

use SGC\Core\Controller;

/**
 * Gère la participation des étudiants aux quiz.
 */
class QuizController extends Controller
{
    /**
     * Affiche la page pour passer un quiz.
     */
    public function take(int $quizId): void
    {
        $quiz = $this->db->fetch("SELECT * FROM quizzes WHERE id = ?", [$quizId]);
        if (!$quiz) {
            $this->redirect('/courses'); // Ou une page d'erreur
            return;
        }

        $questions = $this->db->fetchAll("
            SELECT q.id, q.question_text
            FROM questions q
            WHERE q.quiz_id = ? ORDER BY q.order_index ASC
        ", [$quizId]);

        foreach ($questions as &$question) {
            $question['choices'] = $this->db->fetchAll(
                "SELECT id, choice_text FROM choices WHERE question_id = ?",
                [$question['id']]
            );
        }

        $content = $this->view->render('Quiz/take.html', [
            'quiz' => $quiz,
            'questions' => $questions,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);

        $this->renderPublicLayout('Passer le Quiz : ' . $quiz['title'], $content);
    }

    /**
     * Traite la soumission du quiz et calcule le score.
     */
    public function submit(int $quizId): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/quiz/take/' . $quizId);
            return;
        }

        $user = $this->auth->getUser();
        $answers = $_POST['questions'] ?? [];
        $score = 0;

        // Commencer une transaction
        $this->db->beginTransaction();

        try {
            // Créer une nouvelle tentative de quiz
            $this->db->execute(
                "INSERT INTO quiz_attempts (user_id, quiz_id, started_at) VALUES (?, ?, ?)",
                [$user['id'], $quizId, date('Y-m-d H:i:s')]
            );
            $attemptId = $this->db->lastInsertId();

            // Vérifier chaque réponse
            foreach ($answers as $questionId => $choiceId) {
                $correctChoice = $this->db->fetch(
                    "SELECT id FROM choices WHERE question_id = ? AND is_correct = 1",
                    [$questionId]
                );

                $isCorrect = ($correctChoice && $correctChoice['id'] == $choiceId);
                if ($isCorrect) {
                    $score++;
                }

                $this->db->execute(
                    "INSERT INTO attempt_answers (attempt_id, question_id, choice_id, is_correct) VALUES (?, ?, ?, ?)",
                    [$attemptId, $questionId, $choiceId, $isCorrect ? 1 : 0]
                );
            }

            // Mettre à jour le score final de la tentative
            $finalScore = round(($score / count($answers)) * 100);
            $this->db->execute(
                "UPDATE quiz_attempts SET score = ?, completed_at = ? WHERE id = ?",
                [$finalScore, date('Y-m-d H:i:s'), $attemptId]
            );

            $this->db->commit();

            // Rediriger vers la page des résultats
            $this->redirect('/quiz/results/' . $attemptId);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la soumission du quiz: " . $e->getMessage());
            // TODO: Ajouter un message d'erreur flash
            $this->redirect('/quiz/take/' . $quizId);
        }
    }

    /**
     * Affiche les résultats d'une tentative de quiz.
     */
    public function results(int $attemptId): void
    {
        $attempt = $this->db->fetch(
            "SELECT a.*, q.title as quiz_title
             FROM quiz_attempts a
             JOIN quizzes q ON a.quiz_id = q.id
             WHERE a.id = ? AND a.user_id = ?",
            [$attemptId, $this->auth->getUser()['id']]
        );

        if (!$attempt) {
            $this->redirect('/courses');
            return;
        }

        $content = $this->view->render('Quiz/results.html', [
            'attempt' => $attempt
        ]);

        $this->renderPublicLayout('Résultats du Quiz', $content);
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