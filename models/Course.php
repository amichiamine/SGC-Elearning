<?php

namespace SGC\Models;

use SGC\Core\Model;
use PDO;

class Course extends Model
{
    /**
     * Fetches all courses from the database.
     */
    public function findAll(): array
    {
        $stmt = $this->db->getPDO()->query("SELECT * FROM courses ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a single course by its ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        return $course ?: null;
    }

    /**
     * Creates a new course in the database.
     * created_at and updated_at have default values in the schema.
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO courses (title, description, status) VALUES (?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status']
        ]);
    }

    /**
     * Updates an existing course.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE courses SET title = ?, description = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'],
            $id
        ]);
    }

    /**
     * Deletes a course by its ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->getPDO()->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }
}