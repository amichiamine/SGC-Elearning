<?php

namespace SGC\Controllers\Student;

use SGC\Core\Controller;
use SGC\Models\Course;

class CourseController extends Controller
{
    private $courseModel;

    public function __construct(\SGC\Core\Container $container)
    {
        parent::__construct($container);
        $this->auth->requireLogin();
        // For now, we just require a student role. A proper implementation
        // would check for specific course enrollment.
        $this->auth->requireRole('Student');
        $this->courseModel = new Course($this->db);
    }

    /**
     * Display a single course for a student, including their progress.
     */
    public function view($params)
    {
        $course_id = (int)$params['id'];
        $user_id = $this->auth->getUser()['id'];

        // TODO: Add a check to ensure the student is enrolled in this course.

        $course = $this->courseModel->findById($course_id);
        if (!$course) {
            $this->redirect('/student/dashboard');
            return;
        }

        $modules = $this->courseModel->findModulesForCourse($course_id);
        foreach ($modules as &$module) {
            $lessons = $this->courseModel->findLessonsForModule($module['id']);
            foreach ($lessons as &$lesson) {
                $lesson['materials'] = $this->courseModel->findMaterialsForLesson($lesson['id']);
            }
            $module['lessons'] = $lessons;
        }

        $completed_lessons = $this->courseModel->getCompletedLessons($user_id, $course_id);

        // Calculate progress percentage
        $total_lessons = 0;
        foreach($modules as $module) {
            $total_lessons += count($module['lessons']);
        }
        $progress_percentage = ($total_lessons > 0) ? (count($completed_lessons) / $total_lessons) * 100 : 0;

        $this->renderView('Student/course_view.html', [
            'title' => $course['title'],
            'course' => $course,
            'modules' => $modules,
            'completed_lessons' => $completed_lessons,
            'progress_percentage' => $progress_percentage,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Mark a lesson as complete for the current user.
     */
    public function markAsComplete($params)
    {
        $lesson_id = (int)$params['lesson_id'];
        $user_id = $this->auth->getUser()['id'];

        // TODO: Add a check to ensure the student is enrolled.

        $lesson = $this->courseModel->findLessonById($lesson_id);
        if (!$lesson) {
            $this->redirect('/student/dashboard'); // Or show an error
            return;
        }

        $this->courseModel->markLessonAsComplete($user_id, $lesson_id);

        // Redirect back to the course page to show the updated progress
        $this->redirect('/student/course/' . $lesson['course_id']);
    }
}