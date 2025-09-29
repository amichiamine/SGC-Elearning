<?php

namespace SGC\Controllers\Admin\Settings;

use SGC\Core\Controller;

/**
 * Gère les paramètres de l'application dans le panneau d'administration.
 */
class SettingsController extends Controller
{
    /**
     * Affiche la page des paramètres.
     */
    public function index(): void
    {
        // Récupérer tous les paramètres de la base de données
        $settings = $this->db->fetchAll("SELECT key, value, description, type FROM settings");

        // Organiser les settings dans un format plus simple pour la vue
        $settingsData = [];
        foreach ($settings as $setting) {
            $settingsData[$setting['key']] = [
                'value' => $setting['value'],
                'description' => $setting['description'],
                'type' => $setting['type']
            ];
        }

        $content = $this->view->render('Admin/Settings/settings.html', [
            'settings' => $settingsData,
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);

        $this->renderAdminLayout('Paramètres du Site', $content);
    }

    /**
     * Met à jour les paramètres de l'application.
     */
    public function update(): void
    {
        if (!$this->auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/admin/settings');
            return;
        }

        // Récupérer tous les paramètres postés, sauf le token CSRF
        $postedSettings = $_POST;
        unset($postedSettings['csrf_token']);

        // Parcourir chaque paramètre et le mettre à jour dans la base de données
        foreach ($postedSettings as $key => $value) {
            $this->db->execute(
                "UPDATE settings SET value = ? WHERE key = ?",
                [$value, $key]
            );
        }

        // TODO: Ajouter un message flash de succès
        $this->redirect('/admin/settings');
    }

}