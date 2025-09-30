<?php

namespace SGC\Models;

use SGC\Core\Model;
use PDO;

class Quiz extends Model
{
    /**
     * Creates a new quiz associated with a lesson.
     */
    public function createQuiz(int $lesson_id, string $title, string $description = ''): int
    {
        $sql = "INSERT INTO quizzes (lesson_id, title, description) VALUES (?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$lesson_id, $title, $description]);
        return $this->db->getPDO()->lastInsertId();
    }

    /**
     * Finds a quiz by its lesson ID.
     */
    public function findByLessonId(int $lesson_id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM quizzes WHERE lesson_id = ?");
        $stmt->execute([$lesson_id]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        return $quiz ?: null;
    }

    /**
     * Finds a quiz by its ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$id]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        return $quiz ?: null;
    }

    /**
     * Adds a question to a quiz.
     */
    public function addQuestion(int $quiz_id, string $question_text, string $type = 'multiple_choice'): int
    {
        $sql = "INSERT INTO quiz_questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$quiz_id, $question_text, $type]);
        return $this->db->getPDO()->lastInsertId();
    }

    /**
     * Adds a choice to a question.
     */
    public function addChoice(int $question_id, string $choice_text, bool $is_correct): bool
    {
        $sql = "INSERT INTO question_choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([$question_id, $choice_text, $is_correct]);
    }

    /**
     * Gets all questions and their choices for a quiz.
     */
    public function getQuizQuestions(int $quiz_id): array
    {
        $sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY order_index ASC";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($questions as &$question) {
            $question['choices'] = $this->getQuestionChoices($question['id']);
        }

        return $questions;
    }

    /**
     * Gets all choices for a single question.
     */
    public function getQuestionChoices(int $question_id): array
    {
        $sql = "SELECT * FROM question_choices WHERE question_id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$question_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================================
    // Quiz Taking (Student Experience)
    // ==========================================================================

    /**
     * Starts a new quiz attempt for a user.
     * Returns the ID of the new attempt.
     */
    public function startAttempt(int $quiz_id, int $user_id): int
    {
        $sql = "INSERT INTO quiz_attempts (quiz_id, user_id, status) VALUES (?, ?, 'in_progress')";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$quiz_id, $user_id]);
        return $this->db->getPDO()->lastInsertId();
    }

    /**
     * Saves a user's answer for a specific question in an attempt.
     */
    public function saveAnswer(int $attempt_id, int $question_id, int $chosen_choice_id): bool
    {
        // First, determine if the chosen answer is correct.
        $choiceStmt = $this->db->getPDO()->prepare("SELECT is_correct FROM question_choices WHERE id = ?");
        $choiceStmt->execute([$chosen_choice_id]);
        $is_correct = $choiceStmt->fetchColumn();

        $sql = "INSERT INTO quiz_attempt_answers (attempt_id, question_id, chosen_choice_id, is_correct) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([$attempt_id, $question_id, $chosen_choice_id, $is_correct]);
    }

    /**
     * Finalizes a quiz attempt, calculates the score, and updates the record.
     */
    public function completeAttempt(int $attempt_id): float
    {
        // Get all answers for the attempt
        $answersStmt = $this->db->getPDO()->prepare("SELECT is_correct FROM quiz_attempt_answers WHERE attempt_id = ?");
        $answersStmt->execute([$attempt_id]);
        $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

        $total_questions = count($answers);
        $correct_answers = 0;
        foreach ($answers as $answer) {
            if ($answer['is_correct']) {
                $correct_answers++;
            }
        }

        $score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;

        // Update the attempt record with the final score and status
        $sql = "UPDATE quiz_attempts SET end_time = CURRENT_TIMESTAMP, score = ?, status = 'completed' WHERE id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$score, $attempt_id]);

        return $score;
    }

    /**
     * Finds a quiz attempt by its ID.
     */
    public function findAttemptById(int $attempt_id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM quiz_attempts WHERE id = ?");
        $stmt->execute([$attempt_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $attempt ?: null;
    }
}