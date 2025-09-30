<?php

namespace SGC\Controllers;

use SGC\Core\Controller;
use SGC\Models\Quiz;
use SGC\Models\Course;

class QuizController extends Controller
{
    private $quizModel;
    private $courseModel;

    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
        $this->auth->requireLogin();
        $this->quizModel = new Quiz($this->db);
        $this->courseModel = new Course($this->db);
    }

    /**
     * Show the form for creating a new quiz for a lesson.
     * Instructors will access this via the course edit page.
     */
    public function create($params)
    {
        $lesson_id = (int)$params['lesson_id'];
        // Additional logic will be needed here to show a form
        // For now, this is a placeholder.
        echo "Form to create quiz for lesson " . $lesson_id;
    }

    /**
     * Show the interface for managing a quiz (adding questions).
     */
    public function edit($params)
    {
        $quiz_id = (int)$params['id'];
        $quiz = $this->quizModel->findById($quiz_id);

        if (!$quiz) {
            $this->redirect('/dashboard');
            return;
        }

        // You would also fetch the lesson and course to check permissions
        // $lesson = $this->courseModel->findLessonById($quiz['lesson_id']);
        // $course = $this->courseModel->findById($lesson['course_id']);
        //
        // Permission check here...

        $questions = $this->quizModel->getQuizQuestions($quiz_id);

        $this->renderView('Quiz/edit.html', [
            'title' => 'Modifier le Quiz : ' . htmlspecialchars($quiz['title']),
            'quiz' => $quiz,
            'questions' => $questions,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Add a question to a quiz.
     */
    public function addQuestion($params)
    {
        $quiz_id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Permission checks needed here
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/quiz/edit/' . $quiz_id); return;
            }

            $question_text = trim($_POST['question_text'] ?? '');
            if (!empty($question_text)) {
                $question_id = $this->quizModel->addQuestion($quiz_id, $question_text);

                // Add choices
                if (isset($_POST['choices']) && is_array($_POST['choices'])) {
                    foreach ($_POST['choices'] as $index => $choice_text) {
                        if (!empty($choice_text)) {
                            $is_correct = isset($_POST['is_correct']) && $_POST['is_correct'] == $index;
                            $this->quizModel->addChoice($question_id, $choice_text, $is_correct);
                        }
                    }
                }
            }
        }
        $this->redirect('/quiz/edit/' . $quiz_id);
    }

    // ==========================================================================
    // Student-Facing Quiz Methods
    // ==========================================================================

    /**
     * Display the quiz for a student to take.
     */
    public function take($params)
    {
        $quiz_id = (int)$params['id'];
        $quiz = $this->quizModel->findById($quiz_id); // Note: findById doesn't exist yet in Quiz model, assuming it should. Let's add it.

        if (!$quiz) {
            $this->redirect('/dashboard'); return;
        }

        // Permission check: ensure user is enrolled in the course.
        // This logic will need to be fully implemented. For now, we assume access.

        $user = $this->auth->getUser();
        $attempt_id = $this->quizModel->startAttempt($quiz_id, $user['id']);
        $questions = $this->quizModel->getQuizQuestions($quiz_id);

        $this->renderView('Quiz/take.html', [
            'title' => 'Passer le Quiz : ' . htmlspecialchars($quiz['title']),
            'quiz' => $quiz,
            'questions' => $questions,
            'attempt_id' => $attempt_id,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Process the student's quiz submission.
     */
    public function submit($params)
    {
        $attempt_id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/dashboard'); return;
            }

            $answers = $_POST['answers'] ?? [];
            foreach ($answers as $question_id => $choice_id) {
                $this->quizModel->saveAnswer($attempt_id, $question_id, $choice_id);
            }

            $this->quizModel->completeAttempt($attempt_id);

            $this->redirect('/quiz/results/' . $attempt_id);
        }
    }

    /**
     * Display the results of a quiz attempt.
     */
    public function results($params)
    {
        $attempt_id = (int)$params['id'];
        $attempt = $this->quizModel->findAttemptById($attempt_id);

        if (!$attempt) {
            $this->redirect('/dashboard'); return;
        }

        // Ensure the user viewing the results is the one who took the quiz.
        $user = $this->auth->getUser();
        if ($attempt['user_id'] != $user['id']) {
            $this->redirect('/dashboard'); return;
        }

        $this->renderView('Quiz/results.html', [
            'title' => 'Résultats du Quiz',
            'attempt' => $attempt
        ]);
    }
}