-- Migration to add tables for granular student progress tracking.

-- 1. Create a table to track individual lesson completions.
CREATE TABLE IF NOT EXISTS lesson_completions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE(user_id, lesson_id)
);

-- 2. Add a column to the enrollments table to cache the overall progress percentage.
-- This will be updated by the application logic whenever a lesson is completed.
ALTER TABLE enrollments ADD COLUMN progress_percentage REAL DEFAULT 0.0;