-- Migration to add a complete Quiz System.

-- 1. Quizzes Table
-- A quiz is associated with a lesson.
CREATE TABLE IF NOT EXISTS quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_id INTEGER UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit INTEGER, -- Time limit in minutes
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- 2. Quiz Questions Table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'multiple_choice' NOT NULL, -- e.g., multiple_choice, single_choice
    order_index INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- 3. Question Choices Table (for multiple-choice questions)
CREATE TABLE IF NOT EXISTS question_choices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT 0 NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- 4. Quiz Attempts Table
-- Records a user's attempt to take a quiz.
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    score DECIMAL(5, 2), -- Score as a percentage
    status VARCHAR(50) DEFAULT 'in_progress' NOT NULL, -- e.g., in_progress, completed
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 5. Quiz Attempt Answers Table
-- Records the specific answers a user gave for each question in an attempt.
CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    chosen_choice_id INTEGER,
    is_correct BOOLEAN,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id),
    FOREIGN KEY (chosen_choice_id) REFERENCES question_choices(id)
);