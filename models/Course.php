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
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO courses (title, description, instructor_id, category, level, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['instructor_id'],
            $data['category'],
            $data['level'],
            $data['status'] ?? 'draft'
        ]);
    }

    /**
     * Updates an existing course.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE courses SET title = ?, description = ?, category = ?, level = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['level'],
            $data['status'],
            $id
        ]);
    }

    /**
     * Deletes a course by its ID.
     */
    public function delete(int $id): bool
    {
        // Note: ON DELETE CASCADE will handle related lessons, modules, etc.
        $stmt = $this->db->getPDO()->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================================================
    // Course Modules Management
    // ==========================================================================

    /**
     * Finds all modules for a given course, ordered by their index.
     */
    public function findModulesForCourse(int $course_id): array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY order_index ASC");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new module for a course.
     */
    public function createModule(int $course_id, string $title, string $description = ''): bool
    {
        $sql = "INSERT INTO course_modules (course_id, title, description) VALUES (?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([$course_id, $title, $description]);
    }

    // ==========================================================================
    // Lessons Management
    // ==========================================================================

    /**
     * Finds all lessons for a given module, ordered by their index.
     */
    public function findLessonsForModule(int $module_id): array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY order_index ASC");
        $stmt->execute([$module_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new lesson within a module.
     */
    public function createLesson(array $data): bool
    {
        $sql = "INSERT INTO lessons (course_id, module_id, title, content, content_type, video_url, duration, session_url, session_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([
            $data['course_id'],
            $data['module_id'],
            $data['title'],
            $data['content'] ?? '',
            $data['content_type'] ?? 'text',
            $data['video_url'] ?? null,
            $data['duration'] ?? 0,
            $data['session_url'] ?? null,
            $data['session_datetime'] ?? null
        ]);
    }

    // ==========================================================================
    // Course Materials (File Sharing) Management
    // ==========================================================================

    /**
     * Adds a material record to the database.
     */
    public function addMaterial(int $course_id, ?int $lesson_id, string $title, string $file_path, string $file_type, int $file_size, int $uploader_id): bool
    {
        $sql = "INSERT INTO course_materials (course_id, lesson_id, title, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        return $stmt->execute([$course_id, $lesson_id, $title, $file_path, $file_type, $file_size, $uploader_id]);
    }

    /**
     * Finds all materials for a given course.
     */
    public function findMaterialsForCourse(int $course_id): array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM course_materials WHERE course_id = ? AND lesson_id IS NULL ORDER BY created_at DESC");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds all materials for a given lesson.
     */
    public function findMaterialsForLesson(int $lesson_id): array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM course_materials WHERE lesson_id = ? ORDER BY created_at DESC");
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}