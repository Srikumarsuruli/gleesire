ALTER TABLE `cruise_details` 
ADD COLUMN `boat_type` VARCHAR(255) NULL AFTER `cruise_details`,
ADD COLUMN `cruise_type` VARCHAR(255) NULL AFTER `boat_type`;