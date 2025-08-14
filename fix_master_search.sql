-- SQL script to fix master search issues
-- Run this in phpMyAdmin or MySQL command line

-- 1. Add night_day column if it doesn't exist
ALTER TABLE converted_leads ADD COLUMN IF NOT EXISTS night_day VARCHAR(20) NULL;

-- 2. Modify travel_month column to be VARCHAR instead of DATE
ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20);

-- 3. Create night_day reference table if it doesn't exist
CREATE TABLE IF NOT EXISTS night_day (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Insert default night_day values if table is empty
INSERT IGNORE INTO night_day (name) VALUES 
('1 Night 2 Days'),
('2 Nights 3 Days'),
('3 Nights 4 Days'),
('4 Nights 5 Days'),
('5 Nights 6 Days'),
('6 Nights 7 Days'),
('7 Nights 8 Days'),
('8 Nights 9 Days'),
('9 Nights 10 Days'),
('10 Nights 11 Days'),
('11 Nights 12 Days'),
('12 Nights 13 Days'),
('13 Nights 14 Days'),
('14 Nights 15 Days');

-- 5. Show the updated structure
DESCRIBE converted_leads;