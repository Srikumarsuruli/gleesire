-- Add night_day column to converted_leads table if it doesn't exist
ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL;