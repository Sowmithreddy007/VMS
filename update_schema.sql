-- Add table for Excel imports
CREATE TABLE IF NOT EXISTS tbl_excel_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NULL,
    mobile VARCHAR(30) NULL,
    address TEXT NULL,
    department VARCHAR(100) NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    year_of_graduation YEAR NULL,
    excel_file_name VARCHAR(255) NOT NULL,
    import_status ENUM('pending', 'imported', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES tbl_events(event_id) ON DELETE CASCADE
);

-- Add new columns to tbl_visitors for enhanced functionality
ALTER TABLE tbl_visitors
ADD COLUMN added_by INT NULL COMMENT 'User ID who added this visitor',
ADD COLUMN relation VARCHAR(100) NULL COMMENT 'Relation to existing guest',
ADD COLUMN visitor_type ENUM('regular', 'spot_entry', 'additional_member') DEFAULT 'regular',
ADD CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) REFERENCES tbl_members(id) ON DELETE SET NULL;

-- Add event_id column and foreign key to event_registrations
ALTER TABLE event_registrations
ADD COLUMN event_id INT NOT NULL AFTER user_id,
ADD CONSTRAINT fk_event_registrations_event
FOREIGN KEY (event_id) REFERENCES tbl_events(event_id)
ON DELETE CASCADE;

-- Update existing records with matching event IDs
UPDATE event_registrations er
JOIN tbl_events e ON er.event = e.event_name
SET er.event_id = e.event_id;

-- Add index for better performance
CREATE INDEX idx_event_registrations_event_id ON event_registrations(event_id);
ALTER TABLE event_registrations
ADD COLUMN event_id INT NOT NULL AFTER user_id,
ADD CONSTRAINT fk_event_registrations_event
FOREIGN KEY (event_id) REFERENCES tbl_events(event_id)
ON DELETE CASCADE;

-- Update existing records (assuming 1:1 mapping between event names and IDs)
UPDATE event_registrations er
JOIN tbl_events e ON er.event = e.event_name
SET er.event_id = e.event_id;
-- Note: This is a schema update; actual PHP code will handle event-specific filtering