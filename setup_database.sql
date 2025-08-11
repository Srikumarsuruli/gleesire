-- Create tour_costings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `tour_costings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `enquiry_id` INT(11) NOT NULL,
  `cost_sheet_number` VARCHAR(50),
  `guest_name` VARCHAR(255),
  `guest_address` TEXT,
  `whatsapp_number` VARCHAR(20),
  `tour_package` VARCHAR(100),
  `currency` VARCHAR(10),
  `nationality` VARCHAR(10),
  `adults_count` INT DEFAULT 0,
  `children_count` INT DEFAULT 0,
  `infants_count` INT DEFAULT 0,
  `selected_services` TEXT,
  `visa_data` TEXT,
  `accommodation_data` TEXT,
  `transportation_data` TEXT,
  `cruise_data` TEXT,
  `extras_data` TEXT,
  `payment_data` TEXT,
  `total_expense` DECIMAL(10,2) DEFAULT 0,
  `markup_percentage` DECIMAL(5,2) DEFAULT 0,
  `markup_amount` DECIMAL(10,2) DEFAULT 0,
  `tax_percentage` DECIMAL(5,2) DEFAULT 18,
  `tax_amount` DECIMAL(10,2) DEFAULT 0,
  `package_cost` DECIMAL(10,2) DEFAULT 0,
  `currency_rate` DECIMAL(10,4) DEFAULT 1,
  `converted_amount` DECIMAL(10,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_enquiry_id` (`enquiry_id`)
);

-- Create payments table if it doesn't exist
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cost_file_id` INT(11) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_bank` VARCHAR(100) NOT NULL,
  `payment_amount` DECIMAL(10,2) NOT NULL,
  `payment_receipt` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_cost_file` FOREIGN KEY (`cost_file_id`) REFERENCES `tour_costings` (`id`) ON DELETE CASCADE
);

-- Create directory for payment receipts
-- Note: This is a comment for manual action, as SQL cannot create directories
-- You need to manually create the directory: uploads/receipts/
-- with appropriate permissions (e.g., 755 or 777)