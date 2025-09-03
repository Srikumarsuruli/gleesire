<?php
// This file is included by view_cost_sheets.php before including pdf_template.php
// It fetches additional data needed for the PDF template

// Fetch additional enquiry and converted lead details
if (isset($cost_sheet['enquiry_id'])) {
    // Get enquiry details including lead number, date, and source
    $enquiry_sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number as enquiry_number, cl.adults_count, cl.children_count, cl.infants_count, cl.children_age_details, cl.enquiry_number as lead_number, e.referral_code, e.created_at as enquiry_date, cl.created_at as lead_date,
            s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name, cl.night_day as night_day, cl.travel_start_date as travel_start_date, cl.travel_end_date as travel_end_date, dp.name as department_name
            FROM tour_costings tc 
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
            LEFT JOIN sources s ON e.source_id = s.id
            LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
            LEFT JOIN destinations dest ON cl.destination_id = dest.id
            LEFT JOIN users fm ON cl.file_manager_id = fm.id
            LEFT JOIN departments dp ON e.department_id = dp.id
            WHERE e.id = ?";
    $enquiry_stmt = mysqli_prepare($conn, $enquiry_sql);
    if ($enquiry_stmt) {
        mysqli_stmt_bind_param($enquiry_stmt, "i", $cost_sheet['enquiry_id']);
        mysqli_stmt_execute($enquiry_stmt);
        $enquiry_result = mysqli_stmt_get_result($enquiry_stmt);
        
        if ($enquiry_result && $enquiry_row = mysqli_fetch_assoc($enquiry_result)) {
            $cost_sheet['lead_number'] = $enquiry_row['lead_number'];
            $cost_sheet['lead_date'] = $enquiry_row['lead_date'];
            $cost_sheet['source_name'] = $enquiry_row['source_name'];
            $cost_sheet['referral_code'] = $enquiry_row['referral_code'];
            $cost_sheet['travel_destination'] = $enquiry_row['department_name'];
            $cost_sheet['children_age_details'] = $enquiry_row['children_age_details'];
        }
        
        mysqli_stmt_close($enquiry_stmt);
    }
}

// Calculate total PAX
$cost_sheet['total_pax'] = ($cost_sheet['adults_count'] ?? 0) + ($cost_sheet['children_count'] ?? 0) + ($cost_sheet['infants_count'] ?? 0);
?>