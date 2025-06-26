<?php
// Include database connection
require_once "config/database.php";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="enquiries_template.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, [
    'Customer Name', 
    'Mobile Number', 
    'Email', 
    'Department ID', 
    'Source ID', 
    'Ad Campaign ID (optional)', 
    'Referral Code (optional)', 
    'Social Media Link (optional)',
    'Status ID (1=New, 3=Converted)',
    'Lead Type (Hot/Warm/Cold)',
    'Customer Location',
    'Secondary Contact',
    'Destination ID',
    'Travel Month',
    'Travel Start Date (YYYY-MM-DD)',
    'Travel End Date (YYYY-MM-DD)',
    'Adults Count',
    'Children Count',
    'Infants Count',
    'Customer Available Timing',
    'File Manager ID',
    'Other Details'
]);

// Add a sample row
fputcsv($output, [
    'John Doe',
    '1234567890',
    'john@example.com',
    '1', // Department ID
    '2', // Source ID
    '1', // Ad Campaign ID
    'REF123', // Referral Code
    'https://facebook.com/johndoe', // Social Media Link
    '3', // Status ID (3 = Converted)
    'Hot', // Lead Type
    'New York', // Customer Location
    '9876543210', // Secondary Contact
    '2', // Destination ID
    'January', // Travel Month
    '2023-01-15', // Travel Start Date
    '2023-01-25', // Travel End Date
    '2', // Adults Count
    '1', // Children Count
    '0', // Infants Count
    'Evening', // Customer Available Timing
    '3', // File Manager ID
    'VIP Customer' // Other Details
]);

// Add another sample row with minimal required fields
fputcsv($output, [
    'Jane Smith',
    '9876543210',
    'jane@example.com',
    '2', // Department ID
    '1', // Source ID
    '', // Ad Campaign ID (optional)
    '', // Referral Code (optional)
    '', // Social Media Link (optional)
    '1' // Status ID (1 = New)
]);

// Close the file pointer
fclose($output);
exit;
?>
