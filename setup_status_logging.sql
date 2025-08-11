-- Create status change log table for enquiries
CREATE TABLE IF NOT EXISTS status_change_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    old_status_id INT(11),
    new_status_id INT(11) NOT NULL,
    changed_by INT(11) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_enquiry_id (enquiry_id),
    INDEX idx_changed_at (changed_at)
);

-- Create lead status change log table
CREATE TABLE IF NOT EXISTS lead_status_change_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100) NOT NULL,
    changed_by INT(11) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_enquiry_id (enquiry_id),
    INDEX idx_changed_at (changed_at)
);

-- Add last_reason column to lead_status_map if it doesn't exist
ALTER TABLE lead_status_map ADD COLUMN IF NOT EXISTS last_reason VARCHAR(100) NULL;