<?php
// Include header
require_once "includes/header.php";
require_once "includes/number_generator.php";

// Check if user has privilege to access this page
if(!hasPrivilege('upload_enquiries')) {
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$lead_number = $customer_name = $mobile_number = $email = $social_media_link = $referral_code = "";
$department_id = $source_id = $ad_campaign_id = $status_id = $attended_by = $enquiry_type = "";
$customer_location = $secondary_contact = $destination_id = $other_details = $travel_month = "";
$travel_start_date = $travel_end_date = $adults_count = $children_count = $infants_count = "";
$children_age_details = $customer_available_timing = $file_manager_id = $enquiry_number = "";

$error = $success = "";

// Get current user ID
$current_user_id = $_SESSION["id"];

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

// Get users for dropdown (only sales team, sales manager, operation manager)
$sql = "SELECT u.* FROM users u JOIN roles r ON u.role_id = r.id 
WHERE r.role_name IN ('Sales Team', 'Sales Manager', 'Operation Manager') 
ORDER BY u.full_name";
$users = mysqli_query($conn, $sql);

// Get destinations for dropdown
$sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $sql);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if it's a CSV upload or manual entry
    if(isset($_FILES["csv_file"]) && !empty($_FILES["csv_file"]["name"])) {
        // Process CSV file upload
        $allowed_ext = ['csv'];
        $file_name = $_FILES['csv_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(in_array($file_ext, $allowed_ext)) {
            $file_tmp = $_FILES['csv_file']['tmp_name'];
            
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($file_tmp, 'r');
            
            // Skip first line (header)
            fgetcsv($csvFile);
            
            // Parse data from CSV file line by line
            $count = 0;
            $line_number = 1;
            
            while(($line = fgetcsv($csvFile)) !== FALSE) {
                $line_number++;
                
                // Check if CSV has required fields
                if(count($line) < 5) {
                    continue; // Skip this line if it doesn't have enough fields
                }
                
                // Set values from CSV - Basic enquiry information
                $customer_name = $line[0];
                $mobile_number = $line[1];
                
                // Generate sequential lead number
                $lead_number = generateNumber('enquiry', $conn);
                
                $email = $line[2];
                $department_id = $line[3]; // Assuming department ID is provided
                $source_id = $line[4]; // Assuming source ID is provided
                
                // Optional fields
                $ad_campaign_id = (isset($line[5]) && !empty(trim($line[5]))) ? $line[5] : NULL;
                $referral_code = isset($line[6]) ? $line[6] : NULL;
                $social_media_link = isset($line[7]) ? $line[7] : NULL;
                
                // Check for status in CSV (column 8)
                $csv_status = isset($line[8]) ? trim($line[8]) : '';
                $status_id = 1; // Default to New
                if(strtolower($csv_status) == 'converted') {
                    $status_id = 3; // Converted
                }
                
                // Additional fields
                $enquiry_type = isset($line[9]) ? $line[9] : NULL;
                $customer_location = isset($line[10]) ? $line[10] : NULL;
                $secondary_contact = isset($line[11]) ? $line[11] : NULL;
                $destination_id = isset($line[12]) && !empty(trim($line[12])) ? $line[12] : NULL;
                $other_details = isset($line[13]) ? $line[13] : NULL;
                $travel_month = isset($line[14]) ? $line[14] : NULL;
                $night_day = isset($line[15]) ? $line[15] : NULL;
                $travel_start_date = isset($line[16]) && !empty(trim($line[16])) ? $line[16] : NULL;
                $travel_end_date = isset($line[17]) && !empty(trim($line[17])) ? $line[17] : NULL;
                $adults_count = isset($line[18]) && !empty(trim($line[18])) ? $line[18] : 0;
                $children_count = isset($line[19]) && !empty(trim($line[19])) ? $line[19] : 0;
                $infants_count = isset($line[20]) && !empty(trim($line[20])) ? $line[20] : 0;
                $children_age_details = isset($line[21]) ? $line[21] : NULL;
                $customer_available_timing = isset($line[22]) ? $line[22] : NULL;
                $file_manager_id = isset($line[23]) && !empty(trim($line[23])) ? $line[23] : NULL;
                $lead_type = isset($line[24]) ? $line[24] : NULL;
                
                // Set default values
                $received_datetime = date('Y-m-d H:i:s');
                
                // Prepare an insert statement
                $sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, 
                        ad_campaign_id, referral_code, customer_name, mobile_number, social_media_link, email, status_id, enquiry_type) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
               if($stmt = mysqli_prepare($conn, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    if($ad_campaign_id === NULL) {
                        mysqli_stmt_bind_param($stmt, "ssiiiisssssss", $lead_number, $received_datetime, $current_user_id, 
                                            $department_id, $source_id, $null_value, $referral_code, 
                                            $customer_name, $mobile_number, $social_media_link, $email, $status_id, $enquiry_type);
                    } else {
                        mysqli_stmt_bind_param($stmt, "ssiiiisssssss", $lead_number, $received_datetime, $current_user_id, 
                                            $department_id, $source_id, $ad_campaign_id, $referral_code, 
                                            $customer_name, $mobile_number, $social_media_link, $email, $status_id, $enquiry_type);
                    }
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)) {
                        $enquiry_id = mysqli_insert_id($conn);
                        
                        // If status is Converted, create converted_leads record with all details
                        if($status_id == 3) {
                            $enquiry_number = generateNumber('lead', $conn);
                            
                            $lead_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, 
                                        secondary_contact, destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                                        adults_count, children_count, infants_count, children_age_details, 
                                        customer_available_timing, file_manager_id, booking_confirmed) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
                            
                            if($lead_stmt = mysqli_prepare($conn, $lead_sql)) {
                                mysqli_stmt_bind_param($lead_stmt, "issssisssiisssi", $enquiry_id, $enquiry_number, $lead_type, 
                                                    $customer_location, $secondary_contact, $destination_id, $other_details, 
                                                    $travel_month, $travel_start_date, $travel_end_date, $adults_count, $children_count, 
                                                    $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                                mysqli_stmt_execute($lead_stmt);
                                mysqli_stmt_close($lead_stmt);
                            }
                        }
                        
                        $count++;
                    }
                    
                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
            
            // Close opened CSV file
            fclose($csvFile);
            
            $success = "Successfully imported $count records.";
        } else {
            $error = "Please upload a CSV file.";
        }
    } else {
        // Process manual entry form
        
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
            
            // Removed duplicate mobile number check
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
            
            // Validate status_id exists in lead_status table
            $check_status_sql = "SELECT id FROM lead_status WHERE id = ?";
            $check_status_stmt = mysqli_prepare($conn, $check_status_sql);
            mysqli_stmt_bind_param($check_status_stmt, "i", $status_id);
            mysqli_stmt_execute($check_status_stmt);
            mysqli_stmt_store_result($check_status_stmt);
            
            if(mysqli_stmt_num_rows($check_status_stmt) == 0) {
                $error = "Invalid status selected.";
            }
            
            mysqli_stmt_close($check_status_stmt);
        }
        
        if(empty(trim($_POST["attended_by"]))) {
            $attended_by = $current_user_id; // Default to current user
        } else {
            $attended_by = trim($_POST["attended_by"]);
        }
        
        // Optional fields
        $email = !empty($_POST["email"]) ? trim($_POST["email"]) : NULL;
        $social_media_link = !empty($_POST["social_media_link"]) ? trim($_POST["social_media_link"]) : NULL;
        $referral_code = !empty($_POST["referral_code"]) ? trim($_POST["referral_code"]) : NULL;
        $ad_campaign_id = !empty($_POST["ad_campaign_id"]) ? trim($_POST["ad_campaign_id"]) : NULL;
        $enquiry_type = !empty($_POST["enquiry_type"]) ? trim($_POST["enquiry_type"]) : NULL;
        
        // Set received datetime to current time
        $received_datetime = date('Y-m-d H:i:s');
        
        // Get night_day value
        $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
        
        if(empty($error)) {
            // Generate sequential lead number only when saving
            $lead_number = generateNumber('enquiry', $conn);
            // Prepare an insert statement for enquiries
            $sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, 
                    ad_campaign_id, referral_code, customer_name, mobile_number, social_media_link, email, status_id, enquiry_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssiiiisssssis", $lead_number, $received_datetime, $attended_by, 
                                      $department_id, $source_id, $ad_campaign_id, $referral_code, 
                                      $customer_name, $mobile_number, $social_media_link, $email, $status_id, $enquiry_type);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)) {
                    $enquiry_id = mysqli_insert_id($conn);
                    
                    // If status is "Converted", add to converted_leads table
                    if($status_id == 3) {
                        // Generate enquiry number
                        $enquiry_number = generateNumber('lead', $conn);
                        
                        // Get converted lead details
                        $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                        $customer_location = !empty($_POST["customer_location"]) ? trim($_POST["customer_location"]) : NULL;
                        $secondary_contact = !empty($_POST["secondary_contact"]) ? trim($_POST["secondary_contact"]) : NULL;
                        $destination_id = !empty($_POST["destination_id"]) ? trim($_POST["destination_id"]) : NULL;
                        $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                        $travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
                        $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                        $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                        
                        $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
                        $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                        $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                        $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                        $children_age_details = !empty($_POST["children_age_details"]) ? trim($_POST["children_age_details"]) : NULL;
                        $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                        $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                        
                        // Prepare an insert statement for converted_leads
                        $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                                destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                                adults_count, children_count, infants_count, children_age_details, customer_available_timing, file_manager_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        if($stmt2 = mysqli_prepare($conn, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt2, "issssisssiiiissi", $enquiry_id, $enquiry_number, $lead_type, $customer_location, 
                                                  $secondary_contact, $destination_id, $other_details, $travel_month, 
                                                  $travel_start_date, $travel_end_date, $adults_count, $children_count, 
                                                  $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                            
                            // Attempt to execute the prepared statement
                            if(!mysqli_stmt_execute($stmt2)) {
                                $error = "Error saving converted lead details: " . mysqli_error($conn);
                            }
                            
                            // Close statement
                            mysqli_stmt_close($stmt2);
                        }
                    }
                    
                    $success = "Enquiry added successfully.";
                    
                    // Clear form fields
                    $customer_name = $mobile_number = $email = $social_media_link = $referral_code = "";
                    $department_id = $source_id = $ad_campaign_id = $status_id = $attended_by = $enquiry_type = "";
                    $customer_location = $secondary_contact = $destination_id = $other_details = $travel_month = "";
                    $travel_start_date = $travel_end_date = $adults_count = $children_count = $infants_count = "";
                    $children_age_details = $customer_available_timing = $file_manager_id = $enquiry_number = "";
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>