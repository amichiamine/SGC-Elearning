-- Migration to enhance the course structure with modules, content types, and materials.

-- 1. Create a table for Course Modules to group lessons
CREATE TABLE IF NOT EXISTS course_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- 2. Add module_id to the lessons table to associate them with a module
-- Note: It's not possible to add a foreign key with ALTER TABLE in SQLite before version 3.25.
-- We will handle the relation in the application logic.
ALTER TABLE lessons ADD COLUMN module_id INTEGER;

-- 3. Add a content type to the lessons table
ALTER TABLE lessons ADD COLUMN content_type VARCHAR(50) DEFAULT 'video' NOT NULL CHECK(content_type IN ('video', 'text', 'live_session', 'quiz', 'file'));

-- 4. Create a table for course materials (for file sharing)
CREATE TABLE IF NOT EXISTS course_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    lesson_id INTEGER, -- Can be null if the material is for the whole course
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    file_type VARCHAR(50),
    file_size INTEGER,
    uploaded_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Note: The application logic will need to be updated to manage these new tables and fields.