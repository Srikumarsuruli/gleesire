<?php
// This file is included by view_cost_sheets.php before including pdf_template.php
// It fetches additional data needed for the PDF template

// Fetch children age details from converted_leads table
if (isset($cost_sheet['enquiry_id'])) {
    $children_details_sql = "SELECT children_age_details FROM converted_leads WHERE enquiry_id = ?";
    $children_details_stmt = mysqli_prepare($conn, $children_details_sql);
    mysqli_stmt_bind_param($children_details_stmt, "i", $cost_sheet['enquiry_id']);
    mysqli_stmt_execute($children_details_stmt);
    $children_details_result = mysqli_stmt_get_result($children_details_stmt);
    
    if ($children_details_row = mysqli_fetch_assoc($children_details_result)) {
        $cost_sheet['children_age_details'] = $children_details_row['children_age_details'];
    }
    
    mysqli_stmt_close($children_details_stmt);
}
?>