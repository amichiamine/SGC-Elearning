<?php

namespace SGC\Controllers\Student;

use SGC\Core\Controller;

class StudentController extends Controller
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->auth->requireRole('student');
    }

    public function dashboard()
    {
        $this->renderView('Student/Dashboard/dashboard.html', ['title' => 'Tableau de bord Étudiant']);
    }
}