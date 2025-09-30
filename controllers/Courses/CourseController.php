<?php

namespace SGC\Controllers\Courses;

use SGC\Core\Controller;
use SGC\Models\Course;

class CourseController extends Controller
{
    private $courseModel;

    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
        // Protect all course management actions
        $this->auth->requireRole('admin');
        $this->courseModel = new Course($this->db);
    }

    /**
     * Display a list of all courses.
     */
    public function index()
    {
        $courses = $this->courseModel->findAll();
        $this->renderView('Courses/index.html', [
            'title' => 'Gestion des Cours',
            'courses' => $courses
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $this->renderView('Courses/create.html', [
            'title' => 'Créer un nouveau cours'
        ]);
    }

    /**
     * Store a newly created course in the database.
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                // CSRF token is invalid, abort
                // Optionally, add an error message
                $this->redirect('/admin/courses');
                exit;
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'draft'
            ];

            // Basic validation
            if (!empty($data['title']) && !empty($data['description'])) {
                $this->courseModel->create($data);
            }
        }
        // Redirect to the course list
        $this->redirect('/admin/courses');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit($params)
    {
        $id = (int)$params['id'];
        $course = $this->courseModel->findById($id);

        if (!$course) {
            // Handle course not found, maybe redirect or show an error
            $this->redirect('/admin/courses');
            return;
        }

        $this->renderView('Courses/edit.html', [
            'title' => 'Modifier le cours',
            'course' => $course
        ]);
    }

    /**
     * Update the specified course in the database.
     */
    public function update($params)
    {
        $id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                // CSRF token is invalid, abort
                $this->redirect('/admin/courses');
                exit;
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'draft'
            ];

            if (!empty($data['title']) && !empty($data['description'])) {
                $this->courseModel->update($id, $data);
            }
        }

        $this->redirect('/admin/courses');
    }

    /**
     * Remove the specified course from the database.
     */
    public function destroy($params)
    {
        $id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                // CSRF token is invalid, abort
                $this->redirect('/admin/courses');
                exit;
            }
             $this->courseModel->delete($id);
        }

        $this->redirect('/admin/courses');
    }
}