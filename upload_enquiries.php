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
$department_id = $source_id = $ad_campaign_id = $status_id = $attended_by = "";
$customer_location = $secondary_contact = $destination_id = $other_details = $travel_month = "";
$travel_start_date = $travel_end_date = $adults_count = $children_count = $infants_count = "";
$customer_available_timing = $file_manager_id = $enquiry_number = "";

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

// Get users for dropdown
$sql = "SELECT * FROM users ORDER BY full_name";
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
            $duplicate_numbers = [];
            $line_number = 1;
            
            while(($line = fgetcsv($csvFile)) !== FALSE) {
                $line_number++;
                
                // Check if CSV has required fields
                if(count($line) < 5) {
                    continue; // Skip this line if it doesn't have enough fields
                }
                
                // Set values from CSV
                $customer_name = $line[0];
                $mobile_number = $line[1];
                
                // Check if mobile number already exists in database
                $check_sql = "SELECT id FROM enquiries WHERE mobile_number = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $mobile_number);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if(mysqli_stmt_num_rows($check_stmt) > 0) {
                    // Add to duplicate list
                    $duplicate_numbers[] = "Line $line_number: $mobile_number ($customer_name)";
                    mysqli_stmt_close($check_stmt);
                    continue; // Skip this record
                }
                
                mysqli_stmt_close($check_stmt);
                
                // Generate sequential lead number
                $lead_number = generateNumber('enquiry', $conn);
                
                $email = $line[2];
                $department_id = $line[3]; // Assuming department ID is provided
                $source_id = $line[4]; // Assuming source ID is provided
                
                // Optional fields
                $ad_campaign_id = (isset($line[5]) && !empty(trim($line[5]))) ? $line[5] : NULL;
                $referral_code = isset($line[6]) ? $line[6] : NULL;
                $social_media_link = isset($line[7]) ? $line[7] : NULL;
                
                // Set default values
                $received_datetime = date('Y-m-d H:i:s');
                $status_id = 1; // New
                
                // Set night_day to NULL for CSV uploads
                $night_day = NULL;
                
                // Prepare an insert statement
                $sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, 
                        ad_campaign_id, referral_code, customer_name, mobile_number, social_media_link, email, status_id, night_day) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
               if($stmt = mysqli_prepare($conn, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    if($ad_campaign_id === NULL) {
                        mysqli_stmt_bind_param($stmt, "ssiiiisssssss", $lead_number, $received_datetime, $current_user_id, 
                                            $department_id, $source_id, $null_value, $referral_code, 
                                            $customer_name, $mobile_number, $social_media_link, $email, $status_id, $night_day);
                    } else {
                        mysqli_stmt_bind_param($stmt, "ssiiiisssssss", $lead_number, $received_datetime, $current_user_id, 
                                            $department_id, $source_id, $ad_campaign_id, $referral_code, 
                                            $customer_name, $mobile_number, $social_media_link, $email, $status_id, $night_day);
                    }
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)) {
                        $count++;
                    }
                    
                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
            
            // Close opened CSV file
            fclose($csvFile);
            
            if(count($duplicate_numbers) > 0) {
                $error = "The following mobile numbers already exist in the database:<br>" . implode("<br>", $duplicate_numbers);
                if($count > 0) {
                    $success = "Successfully imported $count records. Some records were skipped due to duplicate mobile numbers.";
                }
            } else {
                $success = "Successfully imported $count records.";
            }
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
            
            // Check if mobile number already exists
            $check_sql = "SELECT id FROM enquiries WHERE mobile_number = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $mobile_number);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if(mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Error: Mobile number $mobile_number already exists in the database.";
            }
            
            mysqli_stmt_close($check_stmt);
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
        
        // Generate sequential lead number
        $lead_number = generateNumber('enquiry', $conn);
        
        // Set received datetime to current time
        $received_datetime = date('Y-m-d H:i:s');
        
        // Get night_day value
        $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
        
        if(empty($error)) {
            // Prepare an insert statement for enquiries
            $sql = "INSERT INTO enquiries (lead_number, received_datetime, attended_by, department_id, source_id, 
                    ad_campaign_id, referral_code, customer_name, mobile_number, social_media_link, email, status_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssiiiisssssi", $lead_number, $received_datetime, $attended_by, 
                                      $department_id, $source_id, $ad_campaign_id, $referral_code, 
                                      $customer_name, $mobile_number, $social_media_link, $email, $status_id);
                
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
                        $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                        $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                        
                        // Prepare an insert statement for converted_leads
                        $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                                destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                                adults_count, children_count, infants_count, customer_available_timing, file_manager_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        if($stmt2 = mysqli_prepare($conn, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt2, "issssississiiis", $enquiry_id, $enquiry_number, $lead_type, $customer_location, 
                                                  $secondary_contact, $destination_id, $other_details, $travel_month, 
                                                  $travel_start_date, $travel_end_date, $adults_count, $children_count, 
                                                  $infants_count, $customer_available_timing, $file_manager_id);
                            
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
                    $department_id = $source_id = $ad_campaign_id = $status_id = $attended_by = "";
                    $customer_location = $secondary_contact = $destination_id = $other_details = $travel_month = "";
                    $travel_start_date = $travel_end_date = $adults_count = $children_count = $infants_count = "";
                    $customer_available_timing = $file_manager_id = $enquiry_number = "";
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
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="csv-tab" data-toggle="tab" href="#csv" role="tab" aria-controls="csv" aria-selected="false">Bulk CSV Upload</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="uploadTabsContent">
                    <!-- Manual Entry Form -->
                    <div class="tab-pane fade show active" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Lead Information</h5>
                                    <hr>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lead-number" class="form-label">Enquiries Number</label>
                                        <input type="text" class="form-control" id="lead-number" name="lead_number" value="<?php echo generateNumber('enquiry', $conn); ?>" readonly>
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
                                        <label class="col-sm-12 col-form-label">Lead Attended By</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="attended-by" name="attended_by">
                                                <?php while($user = mysqli_fetch_assoc($users)): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $current_user_id) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
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
                                        <input type="text" class="form-control" id="mobile-number" name="mobile_number" value="<?php echo $mobile_number; ?>" required>
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-12 col-form-label required">Lead Status</label>
                                        <div class="col-sm-12">
                                            <select class="custom-select col-12" id="lead-status" name="status_id" required>
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
                                        <h5 class="mt-4">Converted Lead Details</h5>
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
                                                <select class="custom-select col-12" id="destination-id" name="destination_id">
                                                    <option value="">Select Destination</option>
                                                    <?php mysqli_data_seek($destinations, 0); ?>
                                                    <?php while($destination = mysqli_fetch_assoc($destinations)): ?>
                                                        <option value="<?php echo $destination['id']; ?>">
                                                            <?php echo htmlspecialchars($destination['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="other-details" class="form-label">Others</label>
                                            <input type="text" class="form-control" id="other-details" name="other_details" value="<?php echo $other_details; ?>">
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
                                                <option value="Custom" <?php echo ($travel_month == "Custom") ? 'selected' : ''; ?>>Custom</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="night-day" class="form-label">Night/Day</label>
                                            <select class="custom-select col-12" id="night-day" name="night_day">
                                                <option value="">Select Night/Day</option>
                                                <option value="1N/2D">1N/2D</option>
                                                <option value="2N/3D">2N/3D</option>
                                                <option value="3N/4D">3N/4D</option>
                                                <option value="4N/5D">4N/5D</option>
                                                <option value="5N/6D">5N/6D</option>
                                                <option value="6N/7D">6N/7D</option>
                                                <option value="7N/8D">7N/8D</option>
                                                <option value="8N/9D">8N/9D</option>
                                                <option value="9N/10D">9N/10D</option>
                                                <option value="10N/11D">10N/11D</option>
                                                <option value="11N/12D">11N/12D</option>
                                                <option value="12N/13D">12N/13D</option>
                                                <option value="13N/14D">13N/14D</option>
                                                <option value="14N/15D">14N/15D</option>
                                                <option value="Custom">Custom</option>
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
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="adults-count" class="form-label">Adults Count</label>
                                            <input type="number" class="form-control passenger-count" id="adults-count" name="adults_count" value="<?php echo $adults_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="children-count" class="form-label">Children Count</label>
                                            <input type="number" class="form-control passenger-count" id="children-count" name="children_count" value="<?php echo $children_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="infants-count" class="form-label">Infants Count</label>
                                            <input type="number" class="form-control passenger-count" id="infants-count" name="infants_count" value="<?php echo $infants_count; ?>" min="0">
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
                                            <label for="customer-available-timing" class="form-label">Customer Available Timing & Children age Details</label>
                                            <input type="text" class="form-control" id="customer-available-timing" name="customer_available_timing" value="<?php echo $customer_available_timing; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-sm-12 col-form-label">File Manager</label>
                                            <div class="col-sm-12">
                                                <select class="custom-select col-12" id="file-manager-id" name="file_manager_id">
                                                    <option value="">Select File Manager</option>
                                                    <?php mysqli_data_seek($users, 0); ?>
                                                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                                                        <option value="<?php echo $user['id']; ?>">
                                                            <?php echo htmlspecialchars($user['full_name']); ?>
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
                                            <label class="col-sm-12 col-form-label">Lead Type</label>
                                            <div class="col-sm-12">
                                                <select class="custom-select col-12" id="lead-type" name="lead_type">
                                                    <option value="">Select Lead Type</option>
                                                    <option value="Hot">Hot</option>
                                                    <option value="Warm">Warm</option>
                                                    <option value="Cold">Cold</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="enquiry-number" class="form-label">Enquiry Number</label>
                                            <input type="text" class="form-control" id="enquiry-number" name="enquiry_number" value="<?php echo generateNumber('lead', $conn); ?>" readonly>
                                            <small class="text-muted">Auto-generated</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save Lead</button>
                                    <button type="reset" class="btn btn-secondary">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- CSV Upload Form -->
                    <div class="tab-pane fade" id="csv" role="tabpanel" aria-labelledby="csv-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="csv-upload" class="form-label">Upload CSV File</label>
                                <input type="file" class="form-control" id="csv-upload" name="csv_file" accept=".csv" required>
                                <small class="text-muted">CSV format: Customer Name, Mobile Number, Email, Department ID, Source ID, Ad Campaign ID (optional), Referral Code (optional), Social Media Link (optional)</small>
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
