<?php
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

// Fetch converted lead data if status is "Converted"
$converted_lead = null;
if($enquiry['status_id'] == 3) {
    $sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $converted_lead = mysqli_fetch_assoc($result);
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
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
        
        // Check if status is being changed to "Converted"
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
    $night_day = !empty($_POST["night_day"]) ? trim($_POST["night_day"]) : NULL;
    
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
                night_day = ?
                WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssiiiissi", $customer_name, $mobile_number, $email, $social_media_link, 
                                  $referral_code, $department_id, $source_id, $ad_campaign_id, $attended_by, $status_id, $night_day, $id);
            
            if(mysqli_stmt_execute($stmt)) {
                // Check if status changed to "Converted"
                if($status_id == 3 && $enquiry['status_id'] != 3) {
                    // Generate enquiry number
                    $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
                    
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
                    $customer_available_timing = !empty($_POST["customer_available_timing"]) ? trim($_POST["customer_available_timing"]) : NULL;
                    $file_manager_id = !empty($_POST["file_manager_id"]) ? trim($_POST["file_manager_id"]) : NULL;
                    
                    // Insert into converted_leads
                    $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, customer_location, secondary_contact, 
                            destination_id, other_details, travel_month, travel_start_date, travel_end_date, 
                            adults_count, children_count, infants_count, customer_available_timing, file_manager_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "issssississiiis", $id, $enquiry_number, $lead_type, $customer_location, 
                                              $secondary_contact, $destination_id, $other_details, $travel_month, 
                                              $travel_start_date, $travel_end_date, $adults_count, $children_count, 
                                              $infants_count, $customer_available_timing, $file_manager_id);
                        
                        if(!mysqli_stmt_execute($stmt2)) {
                            $error = "Error saving converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                } 
                // Check if status is still "Converted" and we need to update converted_lead details
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
                            customer_available_timing = ?, 
                            file_manager_id = ? 
                            WHERE enquiry_id = ?";
                    
                    if($stmt2 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt2, "ssssissiiisii", $lead_type, $customer_location, $secondary_contact, 
                                              $destination_id, $other_details, $travel_month, $travel_start_date, 
                                              $travel_end_date, $adults_count, $children_count, $infants_count, 
                                              $customer_available_timing, $file_manager_id, $id);
                        
                        if(!mysqli_stmt_execute($stmt2)) {
                            $error = "Error updating converted lead details: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                }
                
                $success = "Enquiry updated successfully.";
                
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
                
                // Refresh converted lead data if status is "Converted"
                if($enquiry['status_id'] == 3) {
                    $sql = "SELECT * FROM converted_leads WHERE enquiry_id = ?";
                    if($stmt4 = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt4, "i", $id);
                        
                        if(mysqli_stmt_execute($stmt4)) {
                            $result = mysqli_stmt_get_result($stmt4);
                            
                            if(mysqli_num_rows($result) == 1) {
                                $converted_lead = mysqli_fetch_assoc($result);
                            }
                        }
                        
                        mysqli_stmt_close($stmt4);
                    }
                }
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
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
                            <h5>Lead Information</h5>
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
                                <label for="attended-by" class="form-label required">Lead Attended By</label>
                                <select class="custom-select col-12" id="attended-by" name="attended_by" required>
                                    <?php mysqli_data_seek($users, 0); ?>
                                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $enquiry['attended_by']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($enquiry['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lead-status" class="form-label required">Lead Status</label>
                                <select class="custom-select col-12" id="lead-status" name="status_id" required>
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
                    <div id="converted-lead-details" class="<?php echo ($enquiry['status_id'] != 3) ? 'd-none' : ''; ?>">
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
                                    <select class="custom-select col-12" id="destination-id" name="destination_id">
                                        <option value="">Select Destination</option>
                                        <?php mysqli_data_seek($destinations, 0); ?>
                                        <?php while($destination = mysqli_fetch_assoc($destinations)): ?>
                                            <option value="<?php echo $destination['id']; ?>" <?php echo (isset($converted_lead['destination_id']) && $destination['id'] == $converted_lead['destination_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($destination['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="other-details" class="form-label">Others</label>
                                    <input type="text" class="form-control" id="other-details" name="other_details" value="<?php echo htmlspecialchars($converted_lead['other_details'] ?? ''); ?>">
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
                                        <option value="Custom" <?php echo (isset($converted_lead['travel_month']) && $converted_lead['travel_month'] == 'Custom') ? 'selected' : ''; ?>>Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="night-day" class="form-label">Night/Day</label>
                                    <select class="custom-select col-12" id="night-day" name="night_day">
                                        <option value="">Select Night/Day</option>
                                        <option value="1N/2D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '1N/2D') ? 'selected' : ''; ?>>1N/2D</option>
                                        <option value="2N/3D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '2N/3D') ? 'selected' : ''; ?>>2N/3D</option>
                                        <option value="3N/4D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '3N/4D') ? 'selected' : ''; ?>>3N/4D</option>
                                        <option value="4N/5D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '4N/5D') ? 'selected' : ''; ?>>4N/5D</option>
                                        <option value="5N/6D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '5N/6D') ? 'selected' : ''; ?>>5N/6D</option>
                                        <option value="6N/7D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '6N/7D') ? 'selected' : ''; ?>>6N/7D</option>
                                        <option value="7N/8D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '7N/8D') ? 'selected' : ''; ?>>7N/8D</option>
                                        <option value="8N/9D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '8N/9D') ? 'selected' : ''; ?>>8N/9D</option>
                                        <option value="9N/10D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '9N/10D') ? 'selected' : ''; ?>>9N/10D</option>
                                        <option value="10N/11D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '10N/11D') ? 'selected' : ''; ?>>10N/11D</option>
                                        <option value="11N/12D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '11N/12D') ? 'selected' : ''; ?>>11N/12D</option>
                                        <option value="12N/13D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '12N/13D') ? 'selected' : ''; ?>>12N/13D</option>
                                        <option value="13N/14D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '13N/14D') ? 'selected' : ''; ?>>13N/14D</option>
                                        <option value="14N/15D" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == '14N/15D') ? 'selected' : ''; ?>>14N/15D</option>
                                        <option value="Custom" <?php echo (isset($enquiry['night_day']) && $enquiry['night_day'] == 'Custom') ? 'selected' : ''; ?>>Custom</option>
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
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="adults-count" class="form-label">Adults Count</label>
                                    <input type="number" class="form-control passenger-count" id="adults-count" name="adults_count" value="<?php echo htmlspecialchars($converted_lead['adults_count'] ?? '0'); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="children-count" class="form-label">Children Count</label>
                                    <input type="number" class="form-control passenger-count" id="children-count" name="children_count" value="<?php echo htmlspecialchars($converted_lead['children_count'] ?? '0'); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="infants-count" class="form-label">Infants Count</label>
                                    <input type="number" class="form-control passenger-count" id="infants-count" name="infants_count" value="<?php echo htmlspecialchars($converted_lead['infants_count'] ?? '0'); ?>" min="0">
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
                                    <select class="custom-select col-12" id="file-manager-id" name="file_manager_id">
                                        <option value="">Select File Manager</option>
                                        <?php mysqli_data_seek($users, 0); ?>
                                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo (isset($converted_lead['file_manager_id']) && $user['id'] == $converted_lead['file_manager_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
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
                                    <label for="enquiry-number" class="form-label">Enquiry Number</label>
                                    <input type="text" class="form-control" id="enquiry-number" name="enquiry_number" value="<?php echo htmlspecialchars($converted_lead['enquiry_number'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="view_enquiries.php" class="btn btn-secondary">Cancel</a>
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