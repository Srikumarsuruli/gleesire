ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `booking_status` VARCHAR(50) NULL DEFAULT '' AFTER `infants_count`;

ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `medical_tourism_data` TEXT NULL DEFAULT NULL AFTER `visa_data`;

ALTER TABLE `lead_management`.`tour_costings` 
ADD COLUMN `confirmed` INT NULL DEFAULT 0 AFTER `status`;
