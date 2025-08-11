-- Add children_age_details column to converted_leads table
ALTER TABLE `converted_leads` ADD COLUMN `children_age_details` VARCHAR(255) NULL AFTER `infants_count`;