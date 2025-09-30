<?php

namespace SGC\Controllers\Instructor;

use SGC\Core\Controller;

class InstructorController extends Controller
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->auth->requireRole('instructor');
    }

    public function dashboard()
    {
        $this->renderView('Instructor/Dashboard/dashboard.html', ['title' => 'Tableau de bord Formateur']);
    }
}