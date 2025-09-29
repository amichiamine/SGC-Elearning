<?php

namespace SGC\Api\V1;

use SGC\Core\Controller;

/**
 * Gère les requêtes de l'API pour le système de chat.
 */
class ChatController extends Controller
{
    /**
     * Récupère les messages d'un cours spécifique.
     *
     * @param int $courseId
     */
    public function getMessages(int $courseId): void
    {
        // Seuls les utilisateurs connectés peuvent voir le chat.
        if (!$this->auth->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Accès non autorisé'], 403);
            return;
        }

        $messages = $this->db->fetchAll(
            "SELECT cm.message_text, cm.created_at, u.username
             FROM chat_messages cm
             JOIN users u ON cm.user_id = u.id
             WHERE cm.course_id = ?
             ORDER BY cm.created_at ASC",
            [$courseId]
        );

        $this->jsonResponse($messages);
    }

    /**
     * Enregistre un nouveau message dans le chat d'un cours.
     *
     * @param int $courseId
     */
    public function postMessage(int $courseId): void
    {
        if (!$this->auth->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Accès non autorisé'], 403);
            return;
        }

        // Récupérer les données envoyées en JSON
        $data = json_decode(file_get_contents('php://input'), true);
        $messageText = $data['message'] ?? null;

        if (empty($messageText)) {
            $this->jsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
            return;
        }

        $user = $this->auth->getUser();

        $this->db->execute(
            "INSERT INTO chat_messages (course_id, user_id, message_text) VALUES (?, ?, ?)",
            [$courseId, $user['id'], $messageText]
        );

        $this->jsonResponse(['success' => true, 'message' => 'Message envoyé']);
    }

    /**
     * Helper pour envoyer une réponse JSON.
     *
     * @param mixed $data Les données à encoder en JSON.
     * @param int $statusCode Le code de statut HTTP.
     */
    private function jsonResponse($data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}