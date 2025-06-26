<?php
// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="marketing_data_template.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV header
fputcsv($output, [
    'Date', 
    'Campaign name', 
    'Amount spent (INR)', 
    'Impressions', 
    'CPM (cost per 1,000 impressions) (INR)', 
    'Reach', 
    'Link clicks', 
    'CPC (all) (INR)', 
    'Results', 
    'Cost per results'
]);

// Add sample data
fputcsv($output, [
    '25/04/2025',
    'test1',
    'Rs: 15000',
    '10000',
    '',
    '10k',
    '5k',
    'Rs: 15',
    '50',
    'Rs: 13'
]);

// Close the output stream
fclose($output);
exit;
?>