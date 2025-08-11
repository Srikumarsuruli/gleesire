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
    'Status (New/Converted)',
    'Enquiry Type',
    'Customer Location',
    'Secondary Contact',
    'Destination ID',
    'Other Details',
    'Travel Month',
    'Night/Day',
    'Travel Start Date',
    'Travel End Date',
    'Adults Count',
    'Children Count',
    'Infants Count',
    'Children Age Details',
    'Customer Available Timing',
    'File Manager ID',
    'Lead Priority'
]);

// Add a sample row with all fields (Converted lead)
fputcsv($output, [
    'John Doe',
    '1234567890',
    'john@example.com',
    '1', // Department ID
    '2', // Source ID
    '1', // Ad Campaign ID
    'REF123', // Referral Code
    'https://facebook.com/johndoe', // Social Media Link
    'Converted', // Status
    'Family Tour Package', // Enquiry Type
    'Mumbai', // Customer Location
    '9876543210', // Secondary Contact
    '3', // Destination ID
    'Honeymoon package', // Other Details
    'December', // Travel Month
    '5N/6D', // Night/Day
    '2023-12-15', // Travel Start Date
    '2023-12-20', // Travel End Date
    '2', // Adults Count
    '1', // Children Count
    '0', // Infants Count
    '8 years', // Children Age Details
    'Evening after 6 PM', // Customer Available Timing
    '5', // File Manager ID
    'Hot' // Lead Priority
]);

// Add another sample row with minimal required fields (New lead)
fputcsv($output, [
    'Jane Smith',
    '9876543210',
    'jane@example.com',
    '2', // Department ID
    '1', // Source ID
    '', // Ad Campaign ID (optional)
    '', // Referral Code (optional)
    '', // Social Media Link (optional)
    'New', // Status
    'Budget Travel Request', // Enquiry Type
    '', // Customer Location
    '', // Secondary Contact
    '', // Destination ID
    '', // Other Details
    '', // Travel Month
    '', // Night/Day
    '', // Travel Start Date
    '', // Travel End Date
    '', // Adults Count
    '', // Children Count
    '', // Infants Count
    '', // Children Age Details
    '', // Customer Available Timing
    '', // File Manager ID
    '' // Lead Priority
]);

// Close the file pointer
fclose($output);
exit;
?>
