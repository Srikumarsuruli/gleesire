ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `booking_status` VARCHAR(50) NULL DEFAULT '' AFTER `infants_count`;

ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `medical_tourism_data` TEXT NULL DEFAULT NULL AFTER `visa_data`;

ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `confirmed` INT NULL DEFAULT 0 AFTER `status`;

ALTER TABLE `tour_costings`
  ADD COLUMN `arrival_date` VARCHAR(50) AFTER `visa_data`,
  ADD COLUMN `arrival_city` VARCHAR(50) AFTER `arrival_date`,
  ADD COLUMN `arrival_flight` VARCHAR(50) AFTER `arrival_city`,
  ADD COLUMN `arrival_nights_days` VARCHAR(50) AFTER `arrival_flight`,
  ADD COLUMN `arrival_connection` VARCHAR(50) AFTER `arrival_nights_days`,
  ADD COLUMN `arrival_connecting_date` VARCHAR(50) AFTER `arrival_connection`,
  ADD COLUMN `arrival_connecting_city` VARCHAR(50) AFTER `arrival_connecting_date`,
  ADD COLUMN `arrival_connecting_flight` VARCHAR(50) AFTER `arrival_connecting_city`,
  ADD COLUMN `arrival_connecting_nights_days` VARCHAR(50) AFTER `arrival_connecting_flight`,
  ADD COLUMN `arrival_connecting_type` VARCHAR(50) AFTER `arrival_connecting_nights_days`,
  ADD COLUMN `departure_date` VARCHAR(50) AFTER `arrival_connecting_type`,
  ADD COLUMN `departure_city` VARCHAR(50) AFTER `departure_date`,
  ADD COLUMN `departure_flight` VARCHAR(50) AFTER `departure_city`,
  ADD COLUMN `departure_nights_days` VARCHAR(50) AFTER `departure_flight`,
  ADD COLUMN `departure_connection` VARCHAR(50) AFTER `departure_nights_days`;


-- 24-08-2025---
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 27-08-2025---
ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `booking_number` VARCHAR(100) NULL AFTER `confirmed`;

-- 29-08-2025---
ALTER TABLE `lead_management`.`customers` 
ADD COLUMN `channel` VARCHAR(45) NULL AFTER `created_at`,
ADD COLUMN `social_media_link` VARCHAR(100) NULL AFTER `channel`,
ADD COLUMN `email` VARCHAR(45) NULL AFTER `social_media_link`,
ADD COLUMN `converted` INT NULL COMMENT '0-No, 1-Yes' AFTER `email`,
ADD COLUMN `enquiry_id` INT NULL AFTER `converted`;
