<?php
namespace Views\Home;

use Core\View;

/**
 * Vue de la page d'accueil
 * Vue principale personnalisable via l'admin
 */
class HomeView extends View
{
    public function index()
    {
        $data = [
            'title' => 'SGC E-Learning Platform',
            'message' => 'Bienvenue sur la plateforme d\'e-learning modulaire'
        ];

        $this->render(VIEWS_PATH . '/Home/home.html', $data);
    }
}
?>