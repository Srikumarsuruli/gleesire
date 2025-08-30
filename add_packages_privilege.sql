-- Add manage_packages privilege
INSERT INTO privileges (name, description) VALUES ('manage_packages', 'Manage Packages');

-- Grant privilege to admin role (assuming role_id 1 is admin)
INSERT INTO role_privileges (role_id, privilege_id) 
SELECT 1, id FROM privileges WHERE name = 'manage_packages';