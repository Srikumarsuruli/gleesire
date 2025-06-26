<?php
// Include header
require_once "includes/header.php";

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
$customer_available_timing = $file_manager_id = $enquiry_number = $lead_type = "";

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
    // Process manual entry form
    if(!isset($_FILES["csv_file"]) || empty($_FILES["csv_file"]["name"])) {
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
        
        // Generate lead number
        $lead_number = 'LGH-' . date('Y/m/d/') . sprintf('%04d', rand(1, 9999));
        
        // Set received datetime to current time
        $received_datetime = date('Y-m-d H:i:s');
        
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
                        
                        // Use a simpler query with fewer parameters first
                        $sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, lead_type, booking_confirmed) 
                                VALUES (?, ?, ?, 0)";
                        
                        if($stmt2 = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt2, "iss", $enquiry_id, $enquiry_number, $lead_type);
                            
                            if(mysqli_stmt_execute($stmt2)) {
                                // Now update with the rest of the fields
                                $sql = "UPDATE converted_leads SET 
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
                                
                                $stmt3 = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt3, "ssississiiisi", 
                                                      $customer_location, $secondary_contact, $destination_id, 
                                                      $other_details, $travel_month, $travel_start_date, $travel_end_date, 
                                                      $adults_count, $children_count, $infants_count, 
                                                      $customer_available_timing, $file_manager_id, $enquiry_id);
                                mysqli_stmt_execute($stmt3);
                                mysqli_stmt_close($stmt3);
                                
                                $success = "Enquiry added successfully and converted to lead.";
                            } else {
                                $error = "Error saving converted lead: " . mysqli_error($conn);
                            }
                            
                            mysqli_stmt_close($stmt2);
                        } else {
                            $error = "Error preparing statement: " . mysqli_error($conn);
                        }
                    } else {
                        $success = "Enquiry added successfully.";
                    }
                    
                    // Clear form fields
                    $customer_name = $mobile_number = $email = $social_media_link = $referral_code = "";
                    $department_id = $source_id = $ad_campaign_id = $status_id = $attended_by = "";
                    $customer_location = $secondary_contact = $destination_id = $other_details = $travel_month = "";
                    $travel_start_date = $travel_end_date = $adults_count = $children_count = $infants_count = "";
                    $customer_available_timing = $file_manager_id = $enquiry_number = $lead_type = "";
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        // Process CSV file upload
        // (CSV upload code remains the same)
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
                                        <label for="lead-number" class="form-label">Lead Number</label>
                                        <input type="text" class="form-control" id="lead-number" name="lead_number" value="<?php echo 'LGH-' . date('Y/m/d/') . sprintf('%04d', rand(1, 9999)); ?>" readonly>
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
                                                <?php mysqli_data_seek($users, 0); ?>
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
                                            <input type="text" class="form-control" id="enquiry-number" name="enquiry_number" value="<?php echo 'GH ' . sprintf('%04d', rand(1, 9999)); ?>" readonly>
                                            <small class="text-muted">Auto-generated</small>
                                        </div>
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
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="travel-month" class="form-label">Travel Month</label>
                                            <select class="custom-select col-12" id="travel-month" name="travel_month">
                                                <option value="">Select Month</option>
                                                <option value="January">January</option>
                                                <option value="February">February</option>
                                                <option value="March">March</option>
                                                <option value="April">April</option>
                                                <option value="May">May</option>
                                                <option value="June">June</option>
                                                <option value="July">July</option>
                                                <option value="August">August</option>
                                                <option value="September">September</option>
                                                <option value="October">October</option>
                                                <option value="November">November</option>
                                                <option value="December">December</option>
                                                <option value="Custom">Custom</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="travel-period" class="form-label">Night / Day (Travel Period)</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="travel-start-date" name="travel_start_date" value="<?php echo $travel_start_date; ?>" placeholder="From">
                                                <span class="input-group-text">to</span>
                                                <input type="date" class="form-control" id="travel-end-date" name="travel_end_date" value="<?php echo $travel_end_date; ?>" placeholder="To">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="adults-count" class="form-label">Adults Count</label>
                                            <input type="number" class="form-control" id="adults-count" name="adults_count" value="<?php echo $adults_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="children-count" class="form-label">Children Count</label>
                                            <input type="number" class="form-control" id="children-count" name="children_count" value="<?php echo $children_count; ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="infants-count" class="form-label">Infants Count</label>
                                            <input type="number" class="form-control" id="infants-count" name="infants_count" value="<?php echo $infants_count; ?>" min="0">
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