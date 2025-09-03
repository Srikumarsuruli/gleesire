<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include timezone configuration
require_once "config/timezone.php";

// Include header
require_once "includes/header.php";
require_once "includes/number_generator.php";

// Include notification function if file exists
if (file_exists("create_lead_assignment_notification.php")) {
    require_once "create_lead_assignment_notification.php";
} else {
    // Define dummy function if file doesn't exist
    function createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn) {
        return true;
    }
}

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

// Get current user role to check if bulk upload should be hidden
$sql = "SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
$current_user_role = '';
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $current_user_role = $row['role_name'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Get departments for dropdown
$sql = "SELECT * FROM departments ORDER BY name";
$departments = mysqli_query($conn, $sql);

// Get sources for dropdown (only active ones)
$sql = "SELECT * FROM sources WHERE status = 'active' ORDER BY name";
$sources = mysqli_query($conn, $sql);

// Get ad campaigns for dropdown
$sql = "SELECT * FROM ad_campaigns WHERE status = 'active' ORDER BY name";
$ad_campaigns = mysqli_query($conn, $sql);

// Get lead statuses for dropdown (only active ones)
$sql = "SELECT * FROM lead_status WHERE status = 'active' ORDER BY id";
$lead_statuses = mysqli_query($conn, $sql);

// Get users for dropdown (only sales team and sales manager)
$can_assign_file_manager = true; // Allow everyone to assign file managers
$sql = "SELECT u.* FROM users u JOIN roles r ON u.role_id = r.id 
WHERE r.role_name IN ('Sales Team', 'Sales Manager') 
ORDER BY u.full_name";
$users = mysqli_query($conn, $sql);

// Get destinations for dropdown (only active ones)
$sql = "SELECT * FROM destinations WHERE status = 'active' ORDER BY name";
$destinations = mysqli_query($conn, $sql);

// Get night_day options for dropdown (only active ones)
$sql = "SELECT * FROM night_day WHERE status = 'active' ORDER BY CAST(SUBSTRING_INDEX(name, 'N', 1) AS UNSIGNED)";
$night_day_options = mysqli_query($conn, $sql);

// Get enquiry types for dropdown (only active ones)
$sql = "SELECT * FROM enquiry_types WHERE status = 'active' ORDER BY name";
$enquiry_types = mysqli_query($conn, $sql);

// Get packages for dropdown (only active ones)
$sql = "SELECT * FROM packages WHERE status = 'Active' ORDER BY package_name";
$packages = mysqli_query($conn, $sql);

// Debug: Check if form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Form submitted - POST data: " . print_r($_POST, true));
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
                $destination_ids = isset($line[12]) && !empty(trim($line[12])) ? explode(',', trim($line[12])) : [];
                $destination_id = !empty($destination_ids) ? implode(',', array_map('trim', $destination_ids)) : NULL;
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
                    mysqli_stmt_bind_param($stmt, "ssiiiisssssis", $lead_number, $received_datetime, $current_user_id, 
                                        $department_id, $source_id, $ad_campaign_id, $referral_code, 
                                        $customer_name, $mobile_number, $social_media_link, $email, $status_id, $enquiry_type);
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)) {
                        $enquiry_id = mysqli_insert_id($conn);
                        
                        // If status is Converted, create converted_leads record with all details
                        if($status_id == 3) {
                            $enquiry_number = generateNumber('lead', $conn);
                            
                            // Check if night_day column exists
                            $check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'night_day'";
                            $column_result = mysqli_query($conn, $check_column_sql);
                            
                            if (mysqli_num_rows($column_result) == 0) {
                                // Column doesn't exist, add it
                                $alter_table_sql = "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL";
                                mysqli_query($conn, $alter_table_sql);
                            }
                            
                            $lead_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, 
                                        secondary_contact, destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                                        night_day, adults_count, children_count, infants_count, children_age_details, 
                                        customer_available_timing, file_manager_id, booking_confirmed) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
                            
                            if($lead_stmt = mysqli_prepare($conn, $lead_sql)) {
                                mysqli_stmt_bind_param($lead_stmt, "issssisssssiisssi", $enquiry_id, $enquiry_number, $lead_type, 
                                                    $customer_location, $secondary_contact, $destination_id, $other_details, $travel_month, 
                                                    $travel_start_date, $travel_end_date, $night_day, $adults_count, $children_count, 
                                                    $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                                
                                if(mysqli_stmt_execute($lead_stmt)) {
                                    // Create notification if file manager is assigned
                                    if($file_manager_id) {
                                        createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn);
                                    }
                                }
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
                        $destination_ids = !empty($_POST["destination_id"]) ? $_POST["destination_id"] : [];
                        $destination_id = !empty($destination_ids) ? implode(',', $destination_ids) : NULL;
                        $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                        // Convert month name to date format (first day of month)
$travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
if ($travel_month) {
    $month_names = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
    if (isset($month_names[$travel_month])) {
        $year = date('Y');
        $travel_month = $year . '-' . $month_names[$travel_month] . '-01';
    }
}
                        $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                        $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                        
                        $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
                        $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                        $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                        $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                        $children_age_details = !empty($_POST["children_age_details"]) ? trim($_POST["children_age_details"]) : NULL;
                        $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                        $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                        
                        // Check if night_day column exists
                        $check_column_sql = "SHOW COLUMNS FROM converted_leads LIKE 'night_day'";
                        $column_result = mysqli_query($conn, $check_column_sql);
                        
                        if (mysqli_num_rows($column_result) == 0) {
                            // Column doesn't exist, add it
                            $alter_table_sql = "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL";
                            mysqli_query($conn, $alter_table_sql);
                        }
                        
                        // Prepare an insert statement for converted_leads
                        $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                                destination_id, other_details, travel_month, travel_start_date, travel_end_date, night_day, 
                                adults_count, children_count, infants_count, children_age_details, customer_available_timing, file_manager_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        if($stmt2 = mysqli_prepare($conn, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt2, "issssisssssiisssi", $enquiry_id, $enquiry_number, $lead_type, $customer_location, 
                                                  $secondary_contact, $destination_id, $other_details, $travel_month, 
                                                  $travel_start_date, $travel_end_date, $night_day, $adults_count, $children_count, 
                                                  $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                            
                            // Attempt to execute the prepared statement
                            if(mysqli_stmt_execute($stmt2)) {
                                // Create notification if file manager is assigned
                                if($file_manager_id) {
                                    createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn);
                                }
                            } else {
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

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Upload Enquiries</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="uploadTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual" role="tab" aria-controls="manual" aria-selected="true">Manual Entry</a>
                    </li>
                   <?php
$allowed_roles = ['Sales Team', 'Admin', 'Lead Manager', 'Lead Team'];
if (in_array($current_user_role, $allowed_roles)):
?>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="csv-tab" data-toggle="tab" href="#csv" role="tab" aria-controls="csv" aria-selected="false">Bulk CSV Upload</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="uploadTabsContent">
                    <!-- Manual Entry Form -->
                    <div class="tab-pane fade show active" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="console.log('Form submitting...'); return true;">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Enquiries Information</h5>
                                    <hr>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lead-number" class="form-label">Enquiries Number</label>
                                        <input type="text" class="form-control" id="lead-number" name="lead_number" value="<?php echo generateNumber('enquiry', $conn, true); ?>" readonly>
                                        <small class="text-muted">Auto-generated</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="received-datetime" class="form-label">Received Date & Time</label>
                                        <input type="text" class="form-control" id="received-datetime" name="received_datetime" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                        <small class="text-muted">Current server time</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Attended By</label>
                                        <div class="col-sm-12">
                                            <input type="hidden" name="attended_by" value="<?php echo $current_user_id; ?>">
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly style="background-color: #f0f0f0;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label required">Market Category (Department)</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="department-id" name="department_id" required>
                                                <option value="">Select Department</option>
                                                <?php mysqli_data_seek($departments, 0); ?>
                                                <?php while($department = mysqli_fetch_assoc($departments)): ?>
                                                    <option value="<?php echo $department['id']; ?>">
                                                        <?php echo htmlspecialchars($department['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label required">Source (Channel)</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="source-id" name="source_id" required>
                                                <option value="">Select Source</option>
                                                <?php mysqli_data_seek($sources, 0); ?>
                                                <?php while($source = mysqli_fetch_assoc($sources)): ?>
                                                    <option value="<?php echo $source['id']; ?>">
                                                        <?php echo htmlspecialchars($source['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Ad Campaign</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="ad-campaign-id" name="ad_campaign_id">
                                                <option value="">Select Campaign</option>
                                                <?php mysqli_data_seek($ad_campaigns, 0); ?>
                                                <?php while($campaign = mysqli_fetch_assoc($ad_campaigns)): ?>
                                                    <option value="<?php echo $campaign['id']; ?>">
                                                        <?php echo htmlspecialchars($campaign['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="referral-code" class="form-label">Referral Code</label>
                                        <input type="text" class="form-control" id="referral-code" name="referral_code" value="<?php echo $referral_code; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer-name" class="form-label required">Customer Name</label>
                                        <input type="text" class="form-control" id="customer-name" name="customer_name" value="<?php echo $customer_name; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mobile-number" class="form-label required">Mobile Number</label>
                                        <input type="text" class="form-control" id="mobile-number" name="mobile_number" value="<?php echo !empty($mobile_number) ? $mobile_number : '+'; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="social-media-link" class="form-label">Social Media Link</label>
                                        <input type="text" class="form-control" id="social-media-link" name="social_media_link" value="<?php echo $social_media_link; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label">Enquiry Type</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="enquiry-type" name="enquiry_type">
                                                <option value="">Select Enquiry Type</option>
                                                <?php mysqli_data_seek($enquiry_types, 0); ?>
                                                <?php while($enquiry_type = mysqli_fetch_assoc($enquiry_types)): ?>
                                                    <option value="<?php echo htmlspecialchars($enquiry_type['name']); ?>">
                                                        <?php echo htmlspecialchars($enquiry_type['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-md-4">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label required">Enquiry Status</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="lead-status" name="status_id" required>
                                                <option value="">Select Status</option>
                                                <?php mysqli_data_seek($lead_statuses, 0); ?>
                                                <?php while($status = mysqli_fetch_assoc($lead_statuses)): ?>
                                                    <option value="<?php echo $status['id']; ?>">
                                                        <?php echo htmlspecialchars($status['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        
                            
                            <!-- Converted Lead Details (initially hidden) -->
                            <div id="converted-lead-details" class="d-none">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mt-4">Converted Enquiries Details</h5>
                                        <hr>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer-location" class="form-label">Customer Location</label>
                                            <input type="text" class="form-control" id="customer-location" name="customer_location" value="<?php echo $customer_location; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="secondary-contact" class="form-label">Secondary Contact</label>
                                            <input type="text" class="form-control" id="secondary-contact" name="secondary_contact" value="<?php echo $secondary_contact; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-sm-12 col-form-label">Destination</label>
                                            <div class="col-sm-12">
                                                <select class="custom-select col-12" id="destination-id" name="destination_id[]" multiple size="5">
                                                    <?php mysqli_data_seek($destinations, 0); ?>
                                                    <?php while($destination = mysqli_fetch_assoc($destinations)): ?>
                                                        <option value="<?php echo $destination['id']; ?>">
                                                            <?php echo htmlspecialchars($destination['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <small class="text-muted">Hold Ctrl/Cmd to select multiple destinations</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="other-details" class="form-label">Package Type</label>
                                            <select class="custom-select col-12" id="other-details" name="other_details">
                                                <option value="">Select Package Type</option>
                                                <?php mysqli_data_seek($packages, 0); ?>
                                                <?php while($package = mysqli_fetch_assoc($packages)): ?>
                                                    <option value="<?php echo htmlspecialchars($package['package_name']); ?>">
                                                        <?php echo htmlspecialchars($package['package_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="travel-month" class="form-label">Travel Month</label>
                                            <select class="custom-select col-12" id="travel-month" name="travel_month">
                                                <option value="">Select Month</option>
                                                <option value="January" <?php echo ($travel_month == "January") ? 'selected' : ''; ?>>January</option>
                                                <option value="February" <?php echo ($travel_month == "February") ? 'selected' : ''; ?>>February</option>
                                                <option value="March" <?php echo ($travel_month == "March") ? 'selected' : ''; ?>>March</option>
                                                <option value="April" <?php echo ($travel_month == "April") ? 'selected' : ''; ?>>April</option>
                                                <option value="May" <?php echo ($travel_month == "May") ? 'selected' : ''; ?>>May</option>
                                                <option value="June" <?php echo ($travel_month == "June") ? 'selected' : ''; ?>>June</option>
                                                <option value="July" <?php echo ($travel_month == "July") ? 'selected' : ''; ?>>July</option>
                                                <option value="August" <?php echo ($travel_month == "August") ? 'selected' : ''; ?>>August</option>
                                                <option value="September" <?php echo ($travel_month == "September") ? 'selected' : ''; ?>>September</option>
                                                <option value="October" <?php echo ($travel_month == "October") ? 'selected' : ''; ?>>October</option>
                                                <option value="November" <?php echo ($travel_month == "November") ? 'selected' : ''; ?>>November</option>
                                                <option value="December" <?php echo ($travel_month == "December") ? 'selected' : ''; ?>>December</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="night-day" class="form-label">Night/Day</label>
                                            <select class="custom-select col-12" id="night-day" name="night_day">
                                                <option value="">Select Night/Day</option>
                                                <?php mysqli_data_seek($night_day_options, 0); ?>
                                                <?php while($night_day = mysqli_fetch_assoc($night_day_options)): ?>
                                                    <option value="<?php echo htmlspecialchars($night_day['name']); ?>">
                                                        <?php echo htmlspecialchars($night_day['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="travel-period" class="form-label">Travel Period</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="travel-start-date" name="travel_start_date" value="<?php echo $travel_start_date; ?>" placeholder="From">
                                                <span class="input-group-text">to</span>
                                                <input type="date" class="form-control" id="travel-end-date" name="travel_end_date" value="<?php echo $travel_end_date; ?>" placeholder="To">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="adults-count" class="form-label">Adults Count</label>
                                            <input type="number" class="form-control passenger-count" id="adults-count" name="adults_count" value="<?php echo $adults_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="children-count" class="form-label">Children Count</label>
                                            <input type="number" class="form-control passenger-count" id="children-count" name="children_count" value="<?php echo $children_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="infants-count" class="form-label">Infants Count</label>
                                            <input type="number" class="form-control passenger-count" id="infants-count" name="infants_count" value="<?php echo $infants_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="children-age-details" class="form-label">Children Age Details</label>
                                            <input type="text" class="form-control" id="children-age-details" name="children_age_details" value="<?php echo $children_age_details; ?>" placeholder="e.g., 5, 8, 12 years">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="total-passengers" class="form-label">Total Passengers</label>
                                            <input type="text" class="form-control" id="total-passengers" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer-available-timing" class="form-label">Customer Available Timing</label>
                                            <input type="text" class="form-control" id="customer-available-timing" name="customer_available_timing" value="<?php echo $customer_available_timing; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-sm-12 col-form-label">File Manager</label>
                                            <div class="col-sm-12">
                                                <?php if($can_assign_file_manager): ?>
                                                <select class="custom-select col-12" id="file-manager-id" name="file_manager_id">
                                                    <option value="">Select File Manager</option>
                                                    <?php mysqli_data_seek($users, 0); ?>
                                                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                                                        <option value="<?php echo $user['id']; ?>">
                                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <?php else: ?>
                                                <input type="text" class="form-control" value="Not Available" readonly>
                                                <small class="text-muted">Only Admins/Managers can assign file managers</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-sm-12 col-form-label">Lead Priority</label>
                                            <div class="col-sm-12">
                                                <select class="custom-select col-12" id="lead-type" name="lead_type">
                                                    <option value="">Select Lead Priority</option>
                                                    <option value="Hot">Hot</option>
                                                    <option value="Warm">Warm</option>
                                                    <option value="Cold">Cold</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="enquiry-number" class="form-label">Lead Number</label>
                                            <input type="text" class="form-control" id="enquiry-number" name="enquiry_number" value="<?php echo generateNumber('lead', $conn, true); ?>" readonly>
                                            <small class="text-muted">Preview only</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save Enquiry</button>
                                    <button type="reset" class="btn btn-secondary">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- CSV Upload Form -->
                    <?php if($current_user_role !== 'Sales Team'): ?>
                    <div class="tab-pane fade" id="csv" role="tabpanel" aria-labelledby="csv-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="csv-upload" class="form-label">Upload CSV File</label>
                                <input type="file" class="form-control" id="csv-upload" name="csv_file" accept=".csv" required>
                                <small class="text-muted">CSV format includes all Enquiries Information and Converted Lead Details fields. Download the template for the complete format.</small>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <h6>CSV Template</h6>
                            <p>Download the <a href="download_template.php" class="text-primary">CSV template</a> to ensure your data is formatted correctly.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show/hide converted lead details based on status selection
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('lead-status');
        const convertedDetails = document.getElementById('converted-lead-details');
        
        if (statusSelect && convertedDetails) {
            statusSelect.addEventListener('change', function() {
                if (this.options[this.selectedIndex].text === 'Converted') {
                    convertedDetails.classList.remove('d-none');
                } else {
                    convertedDetails.classList.add('d-none');
                }
            });
            
            // Check initial value
            if (statusSelect.options[statusSelect.selectedIndex].text === 'Converted') {
                convertedDetails.classList.remove('d-none');
            }
        }
        
        // Calculate total passengers
        function calculateTotalPassengers() {
            const adults = parseInt(document.getElementById('adults-count').value) || 0;
            const children = parseInt(document.getElementById('children-count').value) || 0;
            const infants = parseInt(document.getElementById('infants-count').value) || 0;
            const total = adults + children + infants;
            document.getElementById('total-passengers').value = total;
        }
        
        // Add event listeners to passenger count inputs
        const passengerInputs = document.querySelectorAll('.passenger-count');
        passengerInputs.forEach(input => {
            input.addEventListener('input', calculateTotalPassengers);
        });
        
        // Calculate initial total
        calculateTotalPassengers();
        
        // Handle travel month selection to set default view in date picker and restrict date selection
        const travelMonthSelect = document.getElementById('travel-month');
        const travelStartDate = document.getElementById('travel-start-date');
        const travelEndDate = document.getElementById('travel-end-date');
        
        if (travelMonthSelect && travelStartDate && travelEndDate) {
            travelMonthSelect.addEventListener('change', function() {
                const selectedMonth = this.value;
                
                if (selectedMonth && selectedMonth !== 'Custom') {
                    // Get month number (0-11) from month name
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    const monthIndex = monthNames.indexOf(selectedMonth);
                    
                    if (monthIndex !== -1) {
                        // Store month data as attributes on date inputs
                        const currentDate = new Date();
                        const currentMonthIndex = currentDate.getMonth();
                        const yearToUse = (monthIndex < currentMonthIndex) ? currentDate.getFullYear() + 1 : currentDate.getFullYear();
                        
                        // Calculate first and last day of the selected month
                        const firstDay = new Date(yearToUse, monthIndex, 1);
                        const lastDay = new Date(yearToUse, monthIndex + 1, 0);
                        
                        // Format dates as YYYY-MM-DD
                        const firstDayStr = firstDay.toISOString().split('T')[0];
                        const lastDayStr = lastDay.toISOString().split('T')[0];
                        
                        // Automatically set the date values to first and last day of month
                        travelStartDate.value = firstDayStr;
                        travelEndDate.value = lastDayStr;
                        
                        // Remove restrictions - allow any date selection
                        travelStartDate.removeAttribute('min');
                        travelStartDate.removeAttribute('max');
                        travelEndDate.removeAttribute('min');
                        travelEndDate.removeAttribute('max');
                        
                        // Store month and year as data attributes
                        travelStartDate.setAttribute('data-month', monthIndex);
                        travelStartDate.setAttribute('data-year', yearToUse);
                        travelEndDate.setAttribute('data-month', monthIndex);
                        travelEndDate.setAttribute('data-year', yearToUse);
                    }
                } else {
                    // Clear values and restrictions for custom selection
                    travelStartDate.value = '';
                    travelEndDate.value = '';
                    travelStartDate.removeAttribute('min');
                    travelStartDate.removeAttribute('max');
                    travelEndDate.removeAttribute('min');
                    travelEndDate.removeAttribute('max');
                    
                    // Clear data attributes
                    travelStartDate.removeAttribute('data-month');
                    travelStartDate.removeAttribute('data-year');
                    travelEndDate.removeAttribute('data-month');
                    travelEndDate.removeAttribute('data-year');
                }
            });
            
            // Set calendar view to selected month when clicking on date inputs
            travelStartDate.addEventListener('focus', function() {
                const month = this.getAttribute('data-month');
                const year = this.getAttribute('data-year');
                if (month !== null && year !== null && !this.value) {
                    const defaultDate = new Date(year, month, 1);
                    this.value = defaultDate.toISOString().split('T')[0];
                }
            });
            
            travelEndDate.addEventListener('focus', function() {
                const month = this.getAttribute('data-month');
                const year = this.getAttribute('data-year');
                if (month !== null && year !== null && !this.value) {
                    const defaultDate = new Date(year, month + 1, 0);
                    this.value = defaultDate.toISOString().split('T')[0];
                }
            });
            
            // Allow any date selection - no validation restrictions
        }
        
        // Initialize tabs
        $('#uploadTabs a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>