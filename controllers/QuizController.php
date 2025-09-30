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
}