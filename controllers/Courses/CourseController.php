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
        // General protection: user must be logged in to access any course management.
        $this->auth->requireLogin();
        $this->courseModel = new Course($this->db);
    }

    /**
     * Display a list of courses (for an admin).
     */
    public function index()
    {
        $this->auth->requirePermission('admin.courses.manage');
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
        $this->auth->requirePermission('courses.create');
        $this->renderView('Courses/create.html', [
            'title' => 'Créer un nouveau cours',
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Store a newly created course in the database.
     */
    public function store()
    {
        $this->auth->requirePermission('courses.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/dashboard');
                return;
            }

            $user = $this->auth->getUser();
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'level' => $_POST['level'] ?? 'Débutant',
                'status' => $_POST['status'] ?? 'draft',
                'instructor_id' => $user['id']
            ];

            if (!empty($data['title'])) {
                $this->courseModel->create($data);
                $this->redirect('/instructor/dashboard'); // Redirect to instructor's dashboard
                return;
            }
        }
        $this->redirect('/courses/create');
    }

    /**
     * Show the form for editing the specified course, including its structure.
     */
    public function edit($params)
    {
        $id = (int)$params['id'];
        $course = $this->courseModel->findById($id);

        if (!$course) {
            $this->redirect('/dashboard');
            return;
        }

        // Permission check: User must own the course or be an admin.
        $user = $this->auth->getUser();
        if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
            http_response_code(403);
            $this->renderView('errors/403.html', ['title' => 'Accès Interdit']);
            exit;
        }

        // Fetch the full course structure
        $modules = $this->courseModel->findModulesForCourse($id);
        foreach ($modules as &$module) {
            $lessons = $this->courseModel->findLessonsForModule($module['id']);
            foreach ($lessons as &$lesson) {
                // Fetch materials for each lesson
                $lesson['materials'] = $this->courseModel->findMaterialsForLesson($lesson['id']);
            }
            $module['lessons'] = $lessons;
        }

        // Fetch materials for the course itself (not tied to a lesson)
        $course_materials = $this->courseModel->findMaterialsForCourse($id);

        $this->renderView('Courses/edit.html', [
            'title' => 'Modifier le cours : ' . htmlspecialchars($course['title']),
            'course' => $course,
            'modules' => $modules,
            'course_materials' => $course_materials,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Update the specified course's main details.
     */
    public function update($params)
    {
        $id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course = $this->courseModel->findById($id);
            if (!$course) { $this->redirect('/dashboard'); return; }

            // Permission check
            $user = $this->auth->getUser();
            if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
                $this->redirect('/dashboard'); return;
            }
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/courses/edit/' . $id); return;
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'level' => $_POST['level'] ?? 'Débutant',
                'status' => $_POST['status'] ?? 'draft'
            ];

            if (!empty($data['title'])) {
                $this->courseModel->update($id, $data);
            }
        }
        $this->redirect('/courses/edit/' . $id);
    }

    /**
     * Add a new module to a course.
     */
    public function addModule($params)
    {
        $course_id = (int)$params['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course = $this->courseModel->findById($course_id);
            if (!$course) { $this->redirect('/dashboard'); return; }

            // Permission check
            $user = $this->auth->getUser();
            if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
                $this->redirect('/dashboard'); return;
            }
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/courses/edit/' . $course_id); return;
            }

            $title = trim($_POST['title'] ?? '');
            if (!empty($title)) {
                $this->courseModel->createModule($course_id, $title);
            }
        }
        $this->redirect('/courses/edit/' . $course_id);
    }

    /**
     * Add a new lesson to a module.
     */
    public function addLesson($params)
    {
        $course_id = (int)$params['course_id'];
        $module_id = (int)$params['module_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $course = $this->courseModel->findById($course_id);
            if (!$course) { $this->redirect('/dashboard'); return; }

            // Permission check
            $user = $this->auth->getUser();
            if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
                $this->redirect('/dashboard'); return;
            }
             if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/courses/edit/' . $course_id); return;
            }

            $data = [
                'course_id' => $course_id,
                'module_id' => $module_id,
                'title' => trim($_POST['title'] ?? ''),
                'content_type' => $_POST['content_type'] ?? 'text',
                'session_url' => trim($_POST['session_url'] ?? ''),
                'session_datetime' => trim($_POST['session_datetime'] ?? '')
            ];

            if (!empty($data['title'])) {
                $this->courseModel->createLesson($data);
            }
        }
        $this->redirect('/courses/edit/' . $course_id);
    }

    /**
     * Handle the upload of a new course material.
     */
    public function uploadMaterial($params)
    {
        $course_id = (int)$params['course_id'];
        $lesson_id = (int)$params['lesson_id']; // Will be 0 if not specified for a lesson

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course = $this->courseModel->findById($course_id);
            if (!$course) { $this->redirect('/dashboard'); return; }

            // Permission check
            $user = $this->auth->getUser();
            if ($course['instructor_id'] != $user['id'] && !$this->auth->can('admin.courses.manage')) {
                $this->redirect('/dashboard'); return;
            }
            if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirect('/courses/edit/' . $course_id); return;
            }
            if (!isset($_FILES['material']) || $_FILES['material']['error'] !== UPLOAD_ERR_OK) {
                // Handle upload error
                $this->redirect('/courses/edit/' . $course_id); return;
            }

            $file = $_FILES['material'];
            $title = trim($_POST['title'] ?? $file['name']);

            // --- File Validation ---
            $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'zip'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_types)) {
                // Handle invalid file type
                $this->redirect('/courses/edit/' . $course_id); return;
            }

            // --- Secure File Handling ---
            $upload_dir = 'assets/uploads/courses/' . $course_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Create a unique, sanitized filename
            $sanitized_filename = preg_replace("/[^a-zA-Z0-9-_\.]/", "", basename($file['name']));
            $file_path = $upload_dir . time() . '_' . $sanitized_filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // File moved successfully, add to database
                $this->courseModel->addMaterial(
                    $course_id,
                    $lesson_id > 0 ? $lesson_id : null,
                    $title,
                    $file_path,
                    $file['type'],
                    $file['size'],
                    $user['id']
                );
            } else {
                // Handle file move error
            }
        }
        $this->redirect('/courses/edit/' . $course_id);
    }
}