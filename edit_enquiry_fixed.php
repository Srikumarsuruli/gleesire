<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_enquiries', 'edit')) {
    header("location: index.php");
    exit;
}

// Check if ID parameter exists
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: view_enquiries.php");
    exit;
}

$id = trim($_GET["id"]);
$error = $success = "";

// Get departments for dropdown
$sql = "SELECT * FROM departments ORDER BY name";
$departments = mysqli_query($conn, $sql);

// Get sources for dropdown
$sql = "SELECT * FROM sources ORDER BY name";
$sources = mysqli_query($conn, $sql);

// Get ad campaigns for dropdown
$sql = "SELECT * FROM ad_campaigns WHERE status = 'active' ORDER BY name";
$ad_campaigns = mysqli_query($conn, $sql);

// Get lead statuses for dropdown
$sql = "SELECT * FROM lead_status ORDER BY id";
$lead_statuses = mysqli_query($conn, $sql);

// Get users for dropdown
$sql = "SELECT * FROM users ORDER BY full_name";
$users = mysqli_query($conn, $sql);

// Get destinations for dropdown
$sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $sql);

// Fetch enquiry data
$sql = "SELECT * FROM enquiries WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $enquiry = mysqli_fetch_assoc($result);
        } else {
            header("location: view_enquiries.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        exit;
    }
    
    mysqli_stmt_close($stmt);
}

// Fetch converted lead data (if exists) or create if status is Converted but no record exists
$converted_lead = null;
$sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) >= 1) {
            $converted_lead = mysqli_fetch_assoc($result);
        } else if($enquiry['status_id'] == 3) {
            // Status is Converted but no converted_lead record exists, create one
            $enquiry_number = 'LGH-' . date('Y/m/d') . '/' . sprintf('%04d', rand(1, 9999));
            $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number) VALUES (?, ?)";
            if($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($insert_stmt, "is", $id, $enquiry_number);
                if(mysqli_stmt_execute($insert_stmt)) {
                    // Re-fetch the newly created record
                    $fetch_sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
                    if($fetch_stmt = mysqli_prepare($conn, $fetch_sql)) {
                        mysqli_stmt_bind_param($fetch_stmt, "i", $id);
                        mysqli_stmt_execute($fetch_stmt);
                        $fetch_result = mysqli_stmt_get_result($fetch_stmt);
                        if(mysqli_num_rows($fetch_result) >= 1) {
                            $converted_lead = mysqli_fetch_assoc($fetch_result);
                        }
                        mysqli_stmt_close($fetch_stmt);
                    }
                }
                mysqli_stmt_close($insert_stmt);
            }
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Ensure $converted_lead is properly set if record exists
if(!$converted_lead && ($enquiry['status_id'] == 3 || $enquiry['status_id'] == 'Converted')) {
    $final_fetch_sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
    if($final_stmt = mysqli_prepare($conn, $final_fetch_sql)) {
        mysqli_stmt_bind_param($final_stmt, "i", $id);
        mysqli_stmt_execute($final_stmt);
        $final_result = mysqli_stmt_get_result($final_stmt);
        if(mysqli_num_rows($final_result) >= 1) {
            $converted_lead = mysqli_fetch_assoc($final_result);
        }
        mysqli_stmt_close($final_stmt);
    }
}

// Update old format enquiry numbers to new format for any converted lead
if($converted_lead && $converted_lead['enquiry_number'] && strpos($converted_lead['enquiry_number'], 'LGH-') === false) {
    $new_enquiry_number = 'LGH-' . date('Y/m/d') . '/' . sprintf('%04d', rand(1, 9999));
    $update_sql = "UPDATE converted_leads SET enquiry_number = ? WHERE enquiry_id = ?";
    if($update_stmt = mysqli_prepare($conn, $update_sql)) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_enquiry_number, $id);
        if(mysqli_stmt_execute($update_stmt)) {
            $converted_lead['enquiry_number'] = $new_enquiry_number;
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Form submitted - starting processing");
    // Validate input
    if(empty(trim($_POST["customer_name"]))) {
        $error = "Please enter customer name.";
    } else {
        $customer_name = trim($_POST["customer_name"]);
    }
    
    if(empty(trim($_POST["mobile_number"]))) {
        $error = "Please enter mobile number.";
    } else {
        $mobile_number = trim($_POST["mobile_number"]);
    }
    
    if(empty(trim($_POST["department_id"]))) {
        $error = "Please select a department.";
    } else {
        $department_id = trim($_POST["department_id"]);
    }
    
    if(empty(trim($_POST["source_id"]))) {
        $error = "Please select a source.";
    } else {
        $source_id = trim($_POST["source_id"]);
    }
    
    if(empty(trim($_POST["status_id"]))) {
        $error = "Please select a status.";
    } else {
        $status_id = trim($_POST["status_id"]);
        
        // Check if status is being changed to "Converted" (ID 3)
        $old_status_id = $enquiry['status_id'];
        if($status_id == 3 && $old_status_id != 3) {
            // Will need to move to leads automatically
            $auto_move_to_leads = true;
        }
    }
    
    if(empty(trim($_POST["attended_by"]))) {
        $error = "Please select attended by.";
    } else {
        $attended_by = trim($_POST["attended_by"]);
    }
    
    // Optional fields
    $email = !empty($_POST["email"]) ? trim($_POST["email"]) : NULL;
    $social_media_link = !empty($_POST["social_media_link"]) ? trim($_POST["social_media_link"]) : NULL;
    $referral_code = !empty($_POST["referral_code"]) ? trim($_POST["referral_code"]) : NULL;
    $ad_campaign_id = !empty($_POST["ad_campaign_id"]) ? trim($_POST["ad_campaign_id"]) : NULL;
    $enquiry_type = !empty($_POST["enquiry_type"]) ? trim($_POST["enquiry_type"]) : NULL;
    
    if(empty($error)) {
        // Update enquiry
        $sql = "UPDATE enquiries SET 
                customer_name = ?, 
                mobile_number = ?, 
                email = ?, 
                social_media_link = ?, 
                referral_code = ?, 
                department_id = ?, 
                source_id = ?, 
                ad_campaign_id = ?, 
                attended_by = ?, 
                status_id = ?,
                enquiry_type = ?
                WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssiiiissi", $customer_name, $mobile_number, $email, $social_media_link, 
                                  $referral_code, $department_id, $source_id, $ad_campaign_id, $attended_by, $status_id, $enquiry_type, $id);
            
            if(mysqli_stmt_execute($stmt)) {
                // Check if status changed to "Converted" (ID 3)
                if($status_id == 3 && $enquiry['status_id'] != 3) {
                    // Generate enquiry number in format LGH-2025/06/16/0688
                    $enquiry_number = 'LGH-' . date('Y/m/d') . '/' . sprintf('%04d', rand(1, 9999));
                    
                    // Get converted lead details
                    $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                    $customer_location = !empty($_POST["customer_location"]) ? trim($_POST["customer_location"]) : NULL;
                    $secondary_contact = !empty($_POST["secondary_contact"]) ? trim($_POST["secondary_contact"]) : NULL;
                    $destination_id = !empty($_POST["destination_id"]) ? trim($_POST["destination_id"]) : NULL;
                    $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                    $travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
                    $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                    $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                    
                    $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                    $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                    $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                    $children_age_details = !empty($_POST["children_age_details"]) ? trim($_POST["children_age_details"]) : NULL;
                    $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                    $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                    // Insert into converted_leads
                    $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                            destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                            adults_count, children_count, infants_count, children_age_details, customer_available_timing, file_manager_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "issssisssiiiissi", $enquiry_id, $enquiry_number, $lead_type, $customer_location, 
                                              $secondary_contact, $destination_id, $other_details, $travel_month, 
                                              $travel_start_date, $travel_end_date, $adults_count, $children_count, 
                                              $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                        
                        if(!mysqli_stmt_execute($stmt2)) {
                            $error = "Error saving converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                } 
                // Check if status is still "Converted" (ID 3) and we need to update converted_lead details
                else if($status_id == 3 && $enquiry['status_id'] == 3 && $converted_lead) {
                    // Get converted lead details
                    $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                    $customer_location = !empty($_POST["customer_location"]) ? trim($_POST["customer_location"]) : NULL;
                    $secondary_contact = !empty($_POST["secondary_contact"]) ? trim($_POST["secondary_contact"]) : NULL;
                    $destination_id = !empty($_POST["destination_id"]) ? trim($_POST["destination_id"]) : NULL;
                    $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                    $travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
                    $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                    $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                    
                    $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                    $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                    $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                    $children_age_details = !empty($_POST["children_age_details"]) ? trim($_POST["children_age_details"]) : NULL;
                    $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                    $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                    
                    // Update converted_leads
                    $sql = "UPDATE converted_leads SET 
                            lead_type = ?,
                            customer_location = ?, 
                            secondary_contact = ?, 
                            destination_id = ?, 
                            other_details = ?, 
                            travel_month = ?, 
                            travel_start_date = ?, 
                            travel_end_date = ?, 
                            adults_count = ?, 
                            children_count = ?, 
                            infants_count = ?, 
                            children_age_details = ?, 
                            customer_available_timing = ?, 
                            file_manager_id = ? 
                            WHERE enquiry_id = ?";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "ssssisssiiisssi", $lead_type, $customer_location, $secondary_contact, 
                                              $destination_id, $other_details, $travel_month, $travel_start_date, 
                                              $travel_end_date, $adults_count, $children_count, $infants_count, 
                                              $children_age_details, $customer_available_timing, $file_manager_id, $id);
                        
                        if(!mysqli_stmt_execute($stmt2)) {
                            $error = "Error updating converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                }
                
                $success = "Enquiry updated successfully.";
                error_log("Enquiry updated successfully for ID: $id");
                
                // Refresh enquiry data
                $sql = "SELECT * FROM enquiries WHERE id = ?";
                if($stmt3 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt3, "i", $id);
                    
                    if(mysqli_stmt_execute($stmt3)) {
                        $result = mysqli_stmt_get_result($stmt3);
                        
                        if(mysqli_num_rows($result) == 1) {
                            $enquiry = mysqli_fetch_assoc($result);
                        }
                    }
                    
                    mysqli_stmt_close($stmt3);
                }
                
                // Refresh converted lead data (if exists)
                $sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
                if($stmt4 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt4, "i", $id);
                    
                    if(mysqli_stmt_execute($stmt4)) {
                        $result = mysqli_stmt_get_result($stmt4);
                        
                        if(mysqli_num_rows($result) >= 1) {
                            $converted_lead = mysqli_fetch_assoc($result);
                            error_log("Refreshed converted lead data for enquiry ID: $id");
                        } else {
                            error_log("No converted lead data to refresh for enquiry ID: $id");
                        }
                    }
                    
                    mysqli_stmt_close($stmt4);
                }
            } else {
                $error = "Database error: " . mysqli_error($conn) . " - Statement error: " . mysqli_stmt_error($stmt);
                error_log("Edit enquiry error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>