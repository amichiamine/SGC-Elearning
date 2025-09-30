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
     * Finds all lessons for a given module, including associated quiz IDs.
     */
    public function findLessonsForModule(int $module_id): array
    {
        $sql = "SELECT l.*, q.id as quiz_id FROM lessons l
                LEFT JOIN quizzes q ON l.id = q.lesson_id
                WHERE l.module_id = ?
                ORDER BY l.order_index ASC";
        $stmt = $this->db->getPDO()->prepare($sql);
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

    /**
     * Finds a single lesson by its ID.
     */
    public function findLessonById(int $id): ?array
    {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM lessons WHERE id = ?");
        $stmt->execute([$id]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lesson ?: null;
    }

    // ==========================================================================
    // Progress Tracking
    // ==========================================================================

    /**
     * Marks a lesson as complete for a specific user.
     */
    public function markLessonAsComplete(int $user_id, int $lesson_id): bool
    {
        $sql = "INSERT OR IGNORE INTO lesson_completions (user_id, lesson_id) VALUES (?, ?)";
        $stmt = $this->db->getPDO()->prepare($sql);
        $success = $stmt->execute([$user_id, $lesson_id]);

        if ($success && $stmt->rowCount() > 0) {
            $lesson = $this->findLessonById($lesson_id);
            if ($lesson) {
                $this->updateCourseProgress($user_id, $lesson['course_id']);
            }
        }

        return $success;
    }

    /**
     * Calculates and updates the overall progress for a user in a course.
     */
    public function updateCourseProgress(int $user_id, int $course_id): void
    {
        $totalLessonsStmt = $this->db->getPDO()->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
        $totalLessonsStmt->execute([$course_id]);
        $total_lessons = $totalLessonsStmt->fetchColumn();

        if ($total_lessons == 0) {
            return;
        }

        $completedLessonsStmt = $this->db->getPDO()->prepare(
            "SELECT COUNT(*) FROM lesson_completions lc
             JOIN lessons l ON lc.lesson_id = l.id
             WHERE lc.user_id = ? AND l.course_id = ?"
        );
        $completedLessonsStmt->execute([$user_id, $course_id]);
        $completed_lessons = $completedLessonsStmt->fetchColumn();

        $progress_percentage = ($completed_lessons / $total_lessons) * 100;

        $updateSql = "UPDATE enrollments SET progress_percentage = ? WHERE user_id = ? AND course_id = ?";
        $updateStmt = $this->db->getPDO()->prepare($updateSql);
        $updateStmt->execute([$progress_percentage, $user_id, $course_id]);
    }

    /**
     * Gets a list of completed lesson IDs for a user in a specific course.
     */
    public function getCompletedLessons(int $user_id, int $course_id): array
    {
        $sql = "SELECT lc.lesson_id FROM lesson_completions lc
                JOIN lessons l ON lc.lesson_id = l.id
                WHERE lc.user_id = ? AND l.course_id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Gets a list of enrolled students for a course, including their progress.
     */
    public function getEnrolledStudentsWithProgress(int $course_id): array
    {
        $sql = "SELECT u.id, u.username, u.email, e.enrolled_at, e.progress_percentage
                FROM users u
                JOIN enrollments e ON u.id = e.user_id
                WHERE e.course_id = ?";
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}