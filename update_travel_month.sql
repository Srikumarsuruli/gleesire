-- Add travel_month column if it doesn't exist
ALTER TABLE converted_leads ADD COLUMN IF NOT EXISTS travel_month VARCHAR(20) NULL;

-- Update a specific record for testing
UPDATE converted_leads SET travel_month = 'January' WHERE enquiry_id = 56;