<?php

namespace SGC\Controllers\Courses;

use SGC\Core\Controller;

/**
 * Gère le téléchargement sécurisé des fichiers.
 */
class DownloadController extends Controller
{
    /**
     * Gère la requête de téléchargement d'un document de cours.
     */
    public function document(int $documentId): void
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $document = $this->db->fetch("SELECT * FROM course_documents WHERE id = ?", [$documentId]);

        if (!$document) {
            http_response_code(404);
            echo "Document non trouvé.";
            return;
        }

        // Vérifier si l'utilisateur est inscrit au cours
        $isEnrolled = $this->db->fetch(
            "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?",
            [$user['id'], $document['course_id']]
        );

        if (!$isEnrolled) {
            http_response_code(403);
            echo "Accès interdit.";
            return;
        }

        $filePath = $document['file_path'];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo "Fichier non trouvé sur le serveur.";
            return;
        }

        // Servir le fichier pour le téléchargement
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        flush(); // Vider les tampons de sortie du système
        readfile($filePath);
        exit;
    }
}