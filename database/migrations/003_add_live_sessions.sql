-- Migration to add fields for Live Sessions to the lessons table.

ALTER TABLE lessons ADD COLUMN session_url VARCHAR(512);
ALTER TABLE lessons ADD COLUMN session_datetime DATETIME;

-- Note: The application logic will need to be updated to manage these new fields.