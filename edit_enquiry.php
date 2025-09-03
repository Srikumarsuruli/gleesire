<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once "config/database.php";
require_once "includes/functions.php";

// Define dummy notification function
function createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn) {
    return true;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Fetch enquiry data first
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
        header("location: view_enquiries.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug travel_month
    if(isset($_POST['travel_month'])) {
        error_log('POST travel_month: ' . $_POST['travel_month']);
    }

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
                    // Check if converted_leads record already exists
                    $check_sql = "SELECT id FROM converted_leads WHERE enquiry_id = ?";
                    $existing_lead = false;
                    if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                        mysqli_stmt_bind_param($check_stmt, "i", $id);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        if(mysqli_num_rows($check_result) > 0) {
                            $existing_lead = true;
                        }
                        mysqli_stmt_close($check_stmt);
                    }
                    
                    if(!$existing_lead) {
                        // Include number generator
                        require_once "includes/number_generator.php";
                        // Generate enquiry number using the same system as upload_enquiries.php
                        $enquiry_number = generateNumber('lead', $conn);
                    
                    // Get converted lead details
                    $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                    $customer_location = !empty($_POST["customer_location"]) ? trim($_POST["customer_location"]) : NULL;
                    $secondary_contact = !empty($_POST["secondary_contact"]) ? trim($_POST["secondary_contact"]) : NULL;
                    $destination_ids = !empty($_POST["destination_id"]) ? $_POST["destination_id"] : [];
                    $destination_id = !empty($destination_ids) ? implode(',', $destination_ids) : NULL;
                    $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                    $travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
                    $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                    $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                    
                    $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                    $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                    $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                    $children_age_details = !empty($_POST["children_age_details"]) ? $_POST["children_age_details"] : NULL;
                    $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                    $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                    $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;

                    echo $children_age_details;

                    // Insert into converted_leads
                    $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                            destination_id, other_details, travel_month, travel_start_date, travel_end_date, night_day, 
                            adults_count, children_count, infants_count, children_age_details, customer_available_timing, file_manager_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "issssissssiiiissi", $id, $enquiry_number, $lead_type, $customer_location, 
                                              $secondary_contact, $destination_id, $other_details, $travel_month, 
                                              $travel_start_date, $travel_end_date, $night_day, $adults_count, $children_count, 
                                              $infants_count, $children_age_details, $customer_available_timing, $file_manager_id);
                        
                        if(mysqli_stmt_execute($stmt2)) {
                            // Create notification if file manager is assigned
                            if(!empty($file_manager_id)) {
                                // createLeadAssignmentNotification($id, $file_manager_id, $conn);
                            }
                        } else {
                            $error = "Error saving converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                    }
                }
                // Status changed but not to converted, remove from converted_leads if exists
                if($enquiry['status_id'] == 3 && $status_id != 3) {
                    $delete_sql = "DELETE FROM converted_leads WHERE enquiry_id = ?";
                    if($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($delete_stmt, "i", $id);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }
                }
                
                // Check if status is still "Converted" (ID 3) and we need to update converted_lead details
                else if($status_id == 3 && $enquiry['status_id'] == 3) {
                    // Get converted lead details
                    $lead_type = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : NULL;
                    $customer_location = !empty($_POST["customer_location"]) ? trim($_POST["customer_location"]) : NULL;
                    $secondary_contact = !empty($_POST["secondary_contact"]) ? trim($_POST["secondary_contact"]) : NULL;
                    $destination_ids = !empty($_POST["destination_id"]) ? $_POST["destination_id"] : [];
                    $destination_id = !empty($destination_ids) ? implode(',', $destination_ids) : NULL;
                    $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                    $travel_month = !empty($_POST["travel_month"]) ? trim($_POST["travel_month"]) : NULL;
                    $travel_start_date = !empty($_POST["travel_start_date"]) ? trim($_POST["travel_start_date"]) : NULL;
                    $travel_end_date = !empty($_POST["travel_end_date"]) ? trim($_POST["travel_end_date"]) : NULL;
                    
                    $adults_count = !empty($_POST["adults_count"]) ? trim($_POST["adults_count"]) : 0;
                    $children_count = !empty($_POST["children_count"]) ? trim($_POST["children_count"]) : 0;
                    $infants_count = !empty($_POST["infants_count"]) ? trim($_POST["infants_count"]) : 0;
                    $children_age_details = !empty($_POST["children_age_details"]) ? $_POST["children_age_details"] : NULL;
                    $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                    $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                    $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
                    
                    echo $children_age_details;
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
                            night_day = ?,
                            adults_count = ?, 
                            children_count = ?, 
                            infants_count = ?, 
                            children_age_details = ?, 
                            customer_available_timing = ?, 
                            file_manager_id = ? 
                            WHERE enquiry_id = ?";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "ssssissssiiisssi", $lead_type, $customer_location, $secondary_contact, 
                                              $destination_id, $other_details, $travel_month, $travel_start_date, 
                                              $travel_end_date, $night_day, $adults_count, $children_count, $infants_count, 
                                              $children_age_details, $customer_available_timing, $file_manager_id, $id);
                        
                        if(mysqli_stmt_execute($stmt2)) {
                            // Check if file manager was changed and create notification
                            $old_file_manager = $converted_lead['file_manager_id'] ?? null;
                            if(!empty($file_manager_id) && $file_manager_id != $old_file_manager) {
                                // createLeadAssignmentNotification($id, $file_manager_id, $conn);
                            }
                        } else {
                            $error = "Error updating converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                }
                
                // Always update converted_leads if record exists
                $check_converted_sql = "SELECT id FROM converted_leads WHERE enquiry_id = ?";
                if($check_stmt = mysqli_prepare($conn, $check_converted_sql)) {
                    mysqli_stmt_bind_param($check_stmt, "i", $id);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    if(mysqli_num_rows($check_result) > 0) {
                        // Update converted_leads with form data
                        $other_details = !empty($_POST["other_details"]) ? trim($_POST["other_details"]) : NULL;
                        $update_converted_sql = "UPDATE converted_leads SET other_details = ? WHERE enquiry_id = ?";
                        if($update_stmt = mysqli_prepare($conn, $update_converted_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "si", $other_details, $id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);

                        }
                    }
                    mysqli_stmt_close($check_stmt);
                }
                
                if(empty($error)) {
                    error_log("Enquiry updated successfully for ID: $id");
                    
                    // Determine the correct return page based on the referrer
                    $return_page = "view_enquiries.php";
                    
                    if(isset($_SERVER['HTTP_REFERER'])) {
                        $pages = [
                            'view_leads.php',
                            'view_job_enquiries.php',
                            'view_ticket_enquiry.php',
                            'view_influencer_enquiries.php',
                            'view_dmc.php',
                            'view_cruise.php',
                            'view_noresponserejectedenquiries.php',
                            'view_flowup.php'
                        ];
                        
                        foreach($pages as $page) {
                            if(strpos($_SERVER['HTTP_REFERER'], $page) !== false) {
                                $return_page = $page;
                                break;
                            }
                        }
                    }
                    
                    // Redirect back to the appropriate page
                    // header("location: $return_page");
                    // exit;
                }
            } else {
                $error = "Database error: " . mysqli_error($conn) . " - Statement error: " . mysqli_stmt_error($stmt);
                error_log("Edit enquiry error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Include header after POST processing
require_once "includes/header.php";

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

// Get users for dropdown - allow all users to assign file managers
$can_assign_file_manager = true; // Allow all users to assign file managers
$sql = "SELECT u.* FROM users u JOIN roles r ON u.role_id = r.id 
WHERE r.role_name IN ('Sales Team', 'Sales Manager') 
ORDER BY u.full_name";
$users = mysqli_query($conn, $sql);

// Get the attended by user name for display
$attended_by_name = '';
if (!empty($enquiry['attended_by'])) {
    $sql = "SELECT full_name FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $enquiry['attended_by']);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $attended_by_name = $row['full_name'];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get destinations for dropdown
$sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $sql);

// Get packages for dropdown (only active ones)
$sql = "SELECT * FROM packages WHERE status = 'Active' ORDER BY package_name";
$packages = mysqli_query($conn, $sql);

// Store packages in array for reuse
$packages_array = [];
if($packages) {
    while($pkg = mysqli_fetch_assoc($packages)) {
        $packages_array[] = $pkg;
    }
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
        } else {
            // No converted_lead record exists, create one regardless of status
            require_once "includes/number_generator.php";
            $enquiry_number = generateNumber('lead', $conn);
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

// Debug: Log converted_lead data
if($converted_lead) {
    error_log('Converted lead data: ' . print_r($converted_lead, true));
    echo "<!-- DEBUG: other_details = '" . htmlspecialchars($converted_lead['other_details'] ?? 'NULL') . "' -->";
    echo "<!-- DEBUG: packages_array count = " . count($packages_array) . " -->";
    foreach($packages_array as $idx => $pkg) {
        echo "<!-- DEBUG: Package $idx: '" . htmlspecialchars($pkg['package_name']) . "' -->";
    }
} else {
    error_log('No converted lead data found for enquiry ID: ' . $id);
    echo "<!-- DEBUG: No converted_lead record found for ID $id, status_id = {$enquiry['status_id']} -->";
}

// Update old format enquiry numbers to new format for any converted lead
if($converted_lead && $converted_lead['enquiry_number'] && (strpos($converted_lead['enquiry_number'], 'LGH-') !== false || strpos($converted_lead['enquiry_number'], 'GHL/') === false)) {
    require_once "includes/number_generator.php";
    $new_enquiry_number = generateNumber('lead', $conn);
    $update_sql = "UPDATE converted_leads SET enquiry_number = ? WHERE enquiry_id = ?";
    if($update_stmt = mysqli_prepare($conn, $update_sql)) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_enquiry_number, $id);
        if(mysqli_stmt_execute($update_stmt)) {
            $converted_lead['enquiry_number'] = $new_enquiry_number;
        }
        mysqli_stmt_close($update_stmt);
    }
}

?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Edit Enquiry</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        

        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Enquiry Details</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post">
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Enquiry Information</h5>
                            <hr>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lead-number" class="form-label">Lead Number</label>
                                <input type="text" class="form-control" id="lead-number" name="lead_number" value="<?php echo htmlspecialchars($enquiry['lead_number']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="received-datetime" class="form-label">Received Date & Time</label>
                                <input type="text" class="form-control" id="received-datetime" name="received_datetime" value="<?php echo htmlspecialchars($enquiry['received_datetime']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="attended-by" class="form-label">Lead Attended By</label>
                                <input type="text" class="form-control" id="attended-by" name="attended_by_display" value="<?php echo htmlspecialchars($attended_by_name); ?>" readonly>
                                <input type="hidden" name="attended_by" value="<?php echo htmlspecialchars($enquiry['attended_by']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department-id" class="form-label required">Market Category (Department)</label>
                                <select class="custom-select col-12" id="department-id" name="department_id" required>
                                    <?php mysqli_data_seek($departments, 0); ?>
                                    <?php while($department = mysqli_fetch_assoc($departments)): ?>
                                        <option value="<?php echo $department['id']; ?>" <?php echo ($department['id'] == $enquiry['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="source-id" class="form-label required">Source (Channel)</label>
                                <select class="custom-select col-12" id="source-id" name="source_id" required>
                                    <?php mysqli_data_seek($sources, 0); ?>
                                    <?php while($source = mysqli_fetch_assoc($sources)): ?>
                                        <option value="<?php echo $source['id']; ?>" <?php echo ($source['id'] == $enquiry['source_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($source['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ad-campaign-id" class="form-label">Ad Campaign</label>
                                <select class="custom-select col-12" id="ad-campaign-id" name="ad_campaign_id">
                                    <option value="">Select Campaign</option>
                                    <?php mysqli_data_seek($ad_campaigns, 0); ?>
                                    <?php while($campaign = mysqli_fetch_assoc($ad_campaigns)): ?>
                                        <option value="<?php echo $campaign['id']; ?>" <?php echo ($campaign['id'] == $enquiry['ad_campaign_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($campaign['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="referral-code" class="form-label">Referral Code</label>
                                <input type="text" class="form-control" id="referral-code" name="referral_code" value="<?php echo htmlspecialchars($enquiry['referral_code'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer-name" class="form-label required">Customer Name</label>
                                <input type="text" class="form-control" id="customer-name" name="customer_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mobile-number" class="form-label required">Mobile Number</label>
                                <input type="text" class="form-control" id="mobile-number" name="mobile_number" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="social-media-link" class="form-label">Social Media Link</label>
                                <input type="text" class="form-control" id="social-media-link" name="social_media_link" value="<?php echo htmlspecialchars($enquiry['social_media_link'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($enquiry['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="enquiry-type" class="form-label">Enquiry Type</label>
                                <select class="custom-select col-12" id="enquiry-type" name="enquiry_type">
                                    <option value="">Select Enquiry Type</option>
                                    <option value="Advertisement Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Advertisement Enquiry') ? 'selected' : ''; ?>>Advertisement Enquiry</option>
                                    <option value="Budget Travel Request" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Budget Travel Request') ? 'selected' : ''; ?>>Budget Travel Request</option>
                                    <option value="Collaboration" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Collaboration') ? 'selected' : ''; ?>>Collaboration</option>
                                    <option value="Corporate Tour Request" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Corporate Tour Request') ? 'selected' : ''; ?>>Corporate Tour Request</option>
                                    <option value="Cruise Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Cruise Enquiry') ? 'selected' : ''; ?>>Cruise Enquiry</option>
                                    <option value="Cruise Plan (Lakshadweep)" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Cruise Plan (Lakshadweep)') ? 'selected' : ''; ?>>Cruise Plan (Lakshadweep)</option>
                                    <option value="DMCs" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'DMCs') ? 'selected' : ''; ?>>DMCs</option>
                                    <option value="Early Bird Offer Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Early Bird Offer Enquiry') ? 'selected' : ''; ?>>Early Bird Offer Enquiry</option>
                                    <option value="Family Tour Package" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Family Tour Package') ? 'selected' : ''; ?>>Family Tour Package</option>
                                    <option value="Flight + Hotel Combo Request" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Flight + Hotel Combo Request') ? 'selected' : ''; ?>>Flight + Hotel Combo Request</option>
                                    <option value="Group Tour Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Group Tour Enquiry') ? 'selected' : ''; ?>>Group Tour Enquiry</option>
                                    <option value="Honeymoon Package Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Honeymoon Package Enquiry') ? 'selected' : ''; ?>>Honeymoon Package Enquiry</option>
                                    <option value="Job Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Job Enquiry') ? 'selected' : ''; ?>>Job Enquiry</option>
                                    <option value="Just Hotel Booking Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Just Hotel Booking Enquiry') ? 'selected' : ''; ?>>Just Hotel Booking Enquiry</option>
                                    <option value="Luxury Travel Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Luxury Travel Enquiry') ? 'selected' : ''; ?>>Luxury Travel Enquiry</option>
                                    <option value="Medical Tourism Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Medical Tourism Enquiry') ? 'selected' : ''; ?>>Medical Tourism Enquiry</option>
                                    <option value="Need Train + Bus Tickets" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Need Train + Bus Tickets') ? 'selected' : ''; ?>>Need Train + Bus Tickets</option>
                                    <option value="Only Tickets" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Only Tickets') ? 'selected' : ''; ?>>Only Tickets</option>
                                    <option value="Religious Tour Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Religious Tour Enquiry') ? 'selected' : ''; ?>>Religious Tour Enquiry</option>
                                    <option value="School / College Tour Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'School / College Tour Enquiry') ? 'selected' : ''; ?>>School / College Tour Enquiry</option>
                                    <option value="Sightseeing Only Request" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Sightseeing Only Request') ? 'selected' : ''; ?>>Sightseeing Only Request</option>
                                    <option value="Solo Travel Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Solo Travel Enquiry') ? 'selected' : ''; ?>>Solo Travel Enquiry</option>
                                    <option value="Sponsorship" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Sponsorship') ? 'selected' : ''; ?>>Sponsorship</option>
                                    <option value="Ticket Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Ticket Enquiry') ? 'selected' : ''; ?>>Ticket Enquiry</option>
                                    <option value="Travel Insurance Required" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Travel Insurance Required') ? 'selected' : ''; ?>>Travel Insurance Required</option>
                                    <option value="Visa Assistance Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Visa Assistance Enquiry') ? 'selected' : ''; ?>>Visa Assistance Enquiry</option>
                                    <option value="Vloggers / Influencers" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Vloggers / Influencers') ? 'selected' : ''; ?>>Vloggers / Influencers</option>
                                    <option value="Weekend Getaway Enquiry" <?php echo (isset($enquiry['enquiry_type']) && $enquiry['enquiry_type'] == 'Weekend Getaway Enquiry') ? 'selected' : ''; ?>>Weekend Getaway Enquiry</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lead-status" class="form-label required">Enquiry Status</label>
                                <select class="custom-select col-12" id="lead-status" name="status_id" required>
                                    <option value="">Select Status</option>
                                    <?php mysqli_data_seek($lead_statuses, 0); ?>
                                    <?php while($status = mysqli_fetch_assoc($lead_statuses)): ?>
                                        <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $enquiry['status_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Converted Lead Details (initially hidden if not converted) -->
                    <div id="converted-lead-details" class="<?php echo ($enquiry['status_id'] != 3 && $enquiry['status_id'] != 'Converted') ? 'd-none' : ''; ?>">
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
                                    <input type="text" class="form-control" id="customer-location" name="customer_location" value="<?php echo htmlspecialchars($converted_lead['customer_location'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="secondary-contact" class="form-label">Secondary Contact</label>
                                    <input type="text" class="form-control" id="secondary-contact" name="secondary_contact" value="<?php echo htmlspecialchars($converted_lead['secondary_contact'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="destination-id" class="form-label">Destination</label>
                                    <select class="custom-select col-12" id="destination-id" name="destination_id[]" multiple size="5">
                                        <?php 
                                        mysqli_data_seek($destinations, 0);
                                        $selected_destinations = isset($converted_lead['destination_id']) ? explode(',', $converted_lead['destination_id']) : [];
                                        ?>
                                        <?php while($destination = mysqli_fetch_assoc($destinations)): ?>
                                            <option value="<?php echo $destination['id']; ?>" <?php echo in_array($destination['id'], $selected_destinations) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($destination['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple destinations</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="other-details" class="form-label">Package Type</label>
                                    <?php 
                                    $current_package = isset($converted_lead['other_details']) ? trim($converted_lead['other_details']) : '';
                                    ?>

                                    <select class="custom-select col-12" id="other-details" name="other_details">
                                        <option value="">Select Package Type</option>
                                        <?php 
                                        foreach($packages_array as $index => $package): 
                                            $package_name = trim($package['package_name']);
                                            // Handle legacy values: '0' selects first package, otherwise match exact name
                                            $is_selected = ($current_package === '0' && $index === 0) || 
                                                          ($current_package !== '' && $current_package !== '0' && $current_package === $package_name);
                                        ?>
                                            <option value="<?php echo htmlspecialchars($package_name); ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($package_name); ?>
                                            </option>
                                        <?php endforeach; ?>
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
                                        <option value="January" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'January') ? 'selected' : ''; ?>>January</option>
                                        <option value="February" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'February') ? 'selected' : ''; ?>>February</option>
                                        <option value="March" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'March') ? 'selected' : ''; ?>>March</option>
                                        <option value="April" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'April') ? 'selected' : ''; ?>>April</option>
                                        <option value="May" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'May') ? 'selected' : ''; ?>>May</option>
                                        <option value="June" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'June') ? 'selected' : ''; ?>>June</option>
                                        <option value="July" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'July') ? 'selected' : ''; ?>>July</option>
                                        <option value="August" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'August') ? 'selected' : ''; ?>>August</option>
                                        <option value="September" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'September') ? 'selected' : ''; ?>>September</option>
                                        <option value="October" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'October') ? 'selected' : ''; ?>>October</option>
                                        <option value="November" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'November') ? 'selected' : ''; ?>>November</option>
                                        <option value="December" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'December') ? 'selected' : ''; ?>>December</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="night-day" class="form-label">Night/Day</label>
                                    <select class="custom-select col-12" id="night-day" name="night_day">
                                        <option value="">Select Night/Day</option>
                                        <option value="1N/2D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '1N/2D') ? 'selected' : ''; ?>>1N/2D</option>
                                        <option value="2N/3D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '2N/3D') ? 'selected' : ''; ?>>2N/3D</option>
                                        <option value="3N/4D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '3N/4D') ? 'selected' : ''; ?>>3N/4D</option>
                                        <option value="4N/5D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '4N/5D') ? 'selected' : ''; ?>>4N/5D</option>
                                        <option value="5N/6D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '5N/6D') ? 'selected' : ''; ?>>5N/6D</option>
                                        <option value="6N/7D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '6N/7D') ? 'selected' : ''; ?>>6N/7D</option>
                                        <option value="7N/8D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '7N/8D') ? 'selected' : ''; ?>>7N/8D</option>
                                        <option value="8N/9D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '8N/9D') ? 'selected' : ''; ?>>8N/9D</option>
                                        <option value="9N/10D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '9N/10D') ? 'selected' : ''; ?>>9N/10D</option>
                                        <option value="10N/11D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '10N/11D') ? 'selected' : ''; ?>>10N/11D</option>
                                        <option value="11N/12D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '11N/12D') ? 'selected' : ''; ?>>11N/12D</option>
                                        <option value="12N/13D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '12N/13D') ? 'selected' : ''; ?>>12N/13D</option>
                                        <option value="13N/14D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '13N/14D') ? 'selected' : ''; ?>>13N/14D</option>
                                        <option value="14N/15D" <?php echo (isset($converted_lead['night_day']) && $converted_lead['night_day'] == '14N/15D') ? 'selected' : ''; ?>>14N/15D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="travel-period" class="form-label">Travel Period</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="travel-start-date" name="travel_start_date" value="<?php echo htmlspecialchars($converted_lead['travel_start_date'] ?? ''); ?>" placeholder="From">
                                        <span class="input-group-text">to</span>
                                        <input type="date" class="form-control" id="travel-end-date" name="travel_end_date" value="<?php echo htmlspecialchars($converted_lead['travel_end_date'] ?? ''); ?>" placeholder="To">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="adults-count" class="form-label">Adults Count</label>
                                    <input type="number" class="form-control passenger-count" id="adults-count" name="adults_count" value="<?php echo htmlspecialchars($converted_lead['adults_count'] ?? '0'); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="children-count" class="form-label">Children Count</label>
                                    <input type="number" class="form-control passenger-count" id="children-count" name="children_count" value="<?php echo htmlspecialchars($converted_lead['children_count'] ?? '0'); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="infants-count" class="form-label">Infants Count</label>
                                    <input type="number" class="form-control passenger-count" id="infants-count" name="infants_count" value="<?php echo htmlspecialchars($converted_lead['infants_count'] ?? '0'); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="children-age-details" class="form-label">Children Age Details</label>
                                    <input type="text" class="form-control" id="children-age-details" name="children_age_details" value="<?php echo htmlspecialchars($converted_lead['children_age_details'] ?? ''); ?>" placeholder="e.g., 5, 8, 12 years">
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
                                    <input type="text" class="form-control" id="customer-available-timing" name="customer_available_timing" value="<?php echo htmlspecialchars($converted_lead['customer_available_timing'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="file-manager-id" class="form-label">File Manager</label>
                                    <?php if($can_assign_file_manager): ?>
                                    <select class="custom-select col-12" id="file-manager-id" name="file_manager_id">
                                        <option value="">Select File Manager</option>
                                        <?php mysqli_data_seek($users, 0); ?>
                                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo (isset($converted_lead['file_manager_id']) && $user['id'] == $converted_lead['file_manager_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <?php else: ?>
                                    <input type="text" class="form-control" value="Not Assigned" readonly>
                                    <small class="text-muted">Only Admins/Managers can assign file managers</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lead-type" class="form-label">Lead Type</label>
                                    <select class="custom-select col-12" id="lead-type" name="lead_type">
                                        <option value="">Select Lead Type</option>
                                        <option value="Hot" <?php echo (isset($converted_lead['lead_type']) && $converted_lead['lead_type'] == 'Hot') ? 'selected' : ''; ?>>Hot</option>
                                        <option value="Warm" <?php echo (isset($converted_lead['lead_type']) && $converted_lead['lead_type'] == 'Warm') ? 'selected' : ''; ?>>Warm</option>
                                        <option value="Cold" <?php echo (isset($converted_lead['lead_type']) && $converted_lead['lead_type'] == 'Cold') ? 'selected' : ''; ?>>Cold</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="enquiry-number" class="form-label">Lead Number</label>
                                    <input type="text" class="form-control" id="enquiry-number" name="enquiry_number" value="<?php echo htmlspecialchars($converted_lead['enquiry_number'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <?php
                            // Determine the correct return page based on the referrer or enquiry type
                            $return_page = "view_enquiries.php";
                            
                            // Define pages to check in the referrer URL
                            $pages = [
                                'view_job_enquiries.php',
                                'view_ticket_enquiry.php',
                                'view_influencer_enquiries.php',
                                'view_dmc.php',
                                'view_cruise.php',
                                'view_noresponserejectedenquiries.php',
                                'view_flowup.php',
                                'view_leads.php'
                            ];
                            
                            // Check if referrer contains any of the pages
                            if(isset($_SERVER['HTTP_REFERER'])) {
                                foreach($pages as $page) {
                                    if(strpos($_SERVER['HTTP_REFERER'], $page) !== false) {
                                        $return_page = $page;
                                        break;
                                    }
                                }
                            } 
                            // If no referrer, try to determine from enquiry type
                            else if(isset($enquiry['enquiry_type'])) {
                                switch($enquiry['enquiry_type']) {
                                    case 'Job Enquiry':
                                        $return_page = "view_job_enquiries.php";
                                        break;
                                    case 'Ticket Enquiry':
                                        $return_page = "view_ticket_enquiry.php";
                                        break;
                                    case 'Influencer Enquiry':
                                        $return_page = "view_influencer_enquiries.php";
                                        break;
                                    case 'DMCs':
                                        $return_page = "view_dmc.php";
                                        break;
                                    case 'Cruise Enquiry':
                                        $return_page = "view_cruise.php";
                                        break;
                                }
                            }
                            ?>
                            <a href="<?php echo $return_page; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
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
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>