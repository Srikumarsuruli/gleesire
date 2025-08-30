<?php
// This file is included by view_cost_sheets.php before including pdf_template.php
// It fetches additional data needed for the PDF template

// Fetch additional enquiry and converted lead details
if (isset($cost_sheet['enquiry_id'])) {
    // Get enquiry details including lead number, date, and source
    $enquiry_sql = "SELECT e.lead_number, e.received_datetime, s.name as source_name, 
                           cl.ref_code, cl.travel_destination, cl.children_age_details
                    FROM enquiries e 
                    LEFT JOIN sources s ON e.source_id = s.id
                    LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
                    WHERE e.id = ?";
    $enquiry_stmt = mysqli_prepare($conn, $enquiry_sql);
    if ($enquiry_stmt) {
        mysqli_stmt_bind_param($enquiry_stmt, "i", $cost_sheet['enquiry_id']);
        mysqli_stmt_execute($enquiry_stmt);
        $enquiry_result = mysqli_stmt_get_result($enquiry_stmt);
        
        if ($enquiry_result && $enquiry_row = mysqli_fetch_assoc($enquiry_result)) {
            $cost_sheet['lead_number'] = $enquiry_row['lead_number'];
            $cost_sheet['lead_date'] = $enquiry_row['received_datetime'];
            $cost_sheet['source_name'] = $enquiry_row['source_name'];
            $cost_sheet['ref_code'] = $enquiry_row['ref_code'];
            $cost_sheet['travel_destination'] = $enquiry_row['travel_destination'];
            $cost_sheet['children_age_details'] = $enquiry_row['children_age_details'];
        }
        
        mysqli_stmt_close($enquiry_stmt);
    }
}

// Calculate total PAX
$cost_sheet['total_pax'] = ($cost_sheet['adults_count'] ?? 0) + ($cost_sheet['children_count'] ?? 0) + ($cost_sheet['infants_count'] ?? 0);
?>