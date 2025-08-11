<?php
// Include necessary files
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. Cost sheet ID is required.");
}

$cost_file_id = intval($_GET['id']);

// Get the specific cost sheet
$view_sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email 
            FROM tour_costings tc 
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
            WHERE tc.id = ?";
$view_stmt = mysqli_prepare($conn, $view_sql);
mysqli_stmt_bind_param($view_stmt, "i", $cost_file_id);
mysqli_stmt_execute($view_stmt);
$view_result = mysqli_stmt_get_result($view_stmt);

if($view_row = mysqli_fetch_assoc($view_result)) {
    $cost_sheet = $view_row;
} else {
    die("Cost sheet not found.");
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Cost_Sheet_' . $cost_sheet['cost_sheet_number'] . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel to properly display UTF-8 characters
fputs($output, "\xEF\xBB\xBF");

// Add header rows
fputcsv($output, ['Gleesire - Cost Sheet']);
fputcsv($output, ['Cost Sheet: ' . $cost_sheet['cost_sheet_number']]);
fputcsv($output, ['Generated on: ' . date('d-m-Y H:i')]);
fputcsv($output, []); // Empty row for spacing

// Customer Information
fputcsv($output, ['Customer Information']);
fputcsv($output, ['Guest Name:', $cost_sheet['guest_name'] ?? $cost_sheet['customer_name']]);
fputcsv($output, ['Mobile:', $cost_sheet['mobile_number'] ?? 'N/A']);
fputcsv($output, ['Email:', $cost_sheet['email'] ?? 'N/A']);
fputcsv($output, ['Address:', $cost_sheet['guest_address'] ?? 'N/A']);
fputcsv($output, ['WhatsApp:', $cost_sheet['whatsapp_number'] ?? 'N/A']);
fputcsv($output, []); // Empty row for spacing

// Travel Information
fputcsv($output, ['Travel Information']);
fputcsv($output, ['Tour Package:', $cost_sheet['tour_package'] ?? 'N/A']);
fputcsv($output, ['Currency:', $cost_sheet['currency'] ?? 'USD']);
fputcsv($output, ['Nationality:', $cost_sheet['nationality'] ?? 'N/A']);
fputcsv($output, ['Adults:', $cost_sheet['adults_count'] ?? '0']);
fputcsv($output, ['Children:', $cost_sheet['children_count'] ?? '0']);
fputcsv($output, ['Infants:', $cost_sheet['infants_count'] ?? '0']);
fputcsv($output, []); // Empty row for spacing

// Selected Services
fputcsv($output, ['Selected Services']);
$selected_services = json_decode($cost_sheet['selected_services'] ?? '[]', true);
if(!empty($selected_services)) {
    $services_text = '';
    foreach($selected_services as $service) {
        $services_text .= strtoupper(str_replace('_', ' ', $service)) . ', ';
    }
    $services_text = rtrim($services_text, ', ');
    fputcsv($output, [$services_text]);
} else {
    fputcsv($output, ['No services selected']);
}
fputcsv($output, []); // Empty row for spacing

// Cost Summary
fputcsv($output, ['Cost Summary']);
fputcsv($output, ['Total Expense:', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['total_expense'], 2)]);
fputcsv($output, ['Mark Up (' . number_format($cost_sheet['markup_percentage'], 2) . '%):', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['markup_amount'], 2)]);
fputcsv($output, ['Service Tax (' . number_format($cost_sheet['tax_percentage'], 2) . '%):', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['tax_amount'], 2)]);
fputcsv($output, ['Package Cost:', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['package_cost'], 2)]);
fputcsv($output, []); // Empty row for spacing

// Footer
fputcsv($output, ['This is a computer-generated document. No signature is required.']);

// Close the file pointer
fclose($output);
exit;
?>