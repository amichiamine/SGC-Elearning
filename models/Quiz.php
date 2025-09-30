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
}