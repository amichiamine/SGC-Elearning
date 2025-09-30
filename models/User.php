<?php

namespace SGC\Models;

use SGC\Core\Model;
use PDO;

class User extends Model
{
    /**
     * Finds a single user by their ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Gets all courses a user is enrolled in.
     */
    public function getEnrolledCourses(int $user_id): array
    {
        $sql = "SELECT c.* FROM courses c
                JOIN enrollments e ON c.id = e.course_id
                WHERE e.user_id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}