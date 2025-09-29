<?php

namespace SGC\Controllers\Admin\Dashboard;

use SGC\Core\Controller;

/**
 * Gère l'affichage du tableau de bord principal de l'administration.
 */
class DashboardController extends Controller
{
    /**
     * Affiche la page principale du tableau de bord.
     * Cette route est protégée par le middleware 'role:admin'.
     */
    public function index(): void
    {
        // Récupérer les statistiques via le service Database
        $stats = $this->db->getDashboardStats();

        // 1. Rendre le contenu de la page spécifique (le tableau de bord) dans une variable
        ob_start();
        $this->view->render('Admin/Dashboard/dashboard.html', [
            'stats' => $stats
        ]);
        $content = ob_get_clean();

        // 2. Rendre le layout principal de l'admin en lui passant le contenu
        $this->view->render('theme/templates/admin-layout.html', [
            'title' => 'Tableau de Bord Admin',
            'content' => $content
        ]);
    }
}