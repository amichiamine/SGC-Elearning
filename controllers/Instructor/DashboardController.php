<?php

namespace SGC\Controllers\Instructor;

use SGC\Core\Controller;
use SGC\Models\Course;

class DashboardController extends Controller
{
    private $courseModel;

    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
        $this->auth->requireLogin();
        $this->auth->requireRole('Instructor');
        $this->courseModel = new Course($this->db);
    }

    /**
     * Display the main instructor dashboard (e.g., list of their courses).
     */
    public function index()
    {
        // For now, a simple placeholder view.
        // A full implementation would fetch and display the instructor's courses.
        $this->renderView('Instructor/dashboard.html', [
            'title' => 'Tableau de Bord Formateur'
        ]);
    }

    /**
     * Display the progress of all students enrolled in a specific course.
     */
    public function studentProgress($params)
    {
        $course_id = (int)$params['id'];
        $course = $this->courseModel->findById($course_id);

        if (!$course) {
            $this->redirect('/instructor/dashboard');
            return;
        }

        // Security Check: Ensure the logged-in user is the instructor for this course.
        $user = $this->auth->getUser();
        if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
            http_response_code(403);
            $this->renderView('errors/403.html');
            return;
        }

        $students = $this->courseModel->getEnrolledStudentsWithProgress($course_id);

        $this->renderView('Instructor/student_progress.html', [
            'title' => 'Progression des Étudiants : ' . htmlspecialchars($course['title']),
            'course' => $course,
            'students' => $students
        ]);
    }
}