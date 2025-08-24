<?php
// Include header
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Define variables
$role_id = "";
$success = $error = "";

// Add HR Manager role if it doesn't exist
$check_hr_role = "SELECT id FROM roles WHERE role_name = 'HR Manager'";
$hr_role_result = mysqli_query($conn, $check_hr_role);
if(mysqli_num_rows($hr_role_result) == 0) {
    $insert_hr_role = "INSERT INTO roles (role_name) VALUES ('HR Manager')";
    mysqli_query($conn, $insert_hr_role);
}

// Get roles for dropdown
$sql = "SELECT * FROM roles WHERE id > 1 ORDER BY id";  // Exclude admin role
$roles = mysqli_query($conn, $sql);

// Define available menus organized by categories
$menu_categories = array(
    'Core Functions' => array(
        'dashboard' => 'Dashboard',
        'upload_enquiries' => 'Upload Enquiries'
    ),
    'Enquiries' => array(
        'view_enquiries' => 'All Enquiries',
        'job_enquiries' => 'Job Enquiries',
        'accommodation_partnership_enquiries' => 'Accommodation Partnership Enquiries',
        'visa_air_ticket_enquiries' => 'Visa & Air Ticket Enquiries',
        'influencer_enquiries' => 'Influencer Enquiries',
        'dmc_agent_enquiries' => 'DMC/Agent Enquiries',
        'cruise_enquiries' => 'Cruise Enquiries',
        'no_response_enquiries' => 'No Response Enquiries',
        'follow_up_enquiries' => 'Followup Enquiries'
    ),
    'Leads' => array(
        'view_leads' => 'All Leads',
        'group_tour_package_leads' => 'Group Tour Package Leads',
        'honeymoon_package_leads' => 'Honeymoon Package Leads',
        'corporate_package_leads' => 'Corporate Package Leads',
        'medical_tourism_leads' => 'Medical Tourism Leads',
        'custom_package_leads' => 'Custom Package Leads',
        'pipeline' => 'Pipe Line Leads',
        'no_response_leads' => 'No Response / Rejected Leads',
        'follow_up_leads' => 'Followup Leads',
        'lost_to_competitors' => 'Lost to Competitor Leads',
        'junk_duplicate_leads' => 'Junk & Duplicate Leads'
    ),
    'Booking' => array(
        'booking_confirmed' => 'Booking Confirmed',
        'booking_cancelled' => 'Booking Cancelled',
        'travel_completed' => 'Travel Completed'
    ),
    'Our Customers' => array(
        'feedbacks' => 'Our Customers'
    ),
    'Reservation' => array(
        'accommodation_reservation' => 'Accommodation',
        'cruise_reservation' => 'Cruise',
        'visa_ticketing' => 'Visa Ticketing',
        'air_ticketing' => 'Air Ticketing',
        'train_ticketing' => 'Train Ticketing',
        'transportation_reservation' => 'Transportation'
    ),
    'Payments' => array(
        'view_payment_receipts' => 'Payment Received',
        'accommodation_payment_receipts' => 'Accommodation Payment Receipts',
        'transportation_payment_receipts' => 'Transportation Payment Receipts',
        'cruise_payment_receipts' => 'Cruise Payment Receipts',
        'hospital_tac_receipt' => 'Hospital TAC(Travel Agent Commission) Receipt',
        'travel_insurance_receipts' => 'Travel Insurance Receipts',
        'air_ticket_payment_receipts' => 'Air Ticket Payment Receipts',
        'visa_payment_receipts' => 'VISA Payment Receipts',
        'train_ticket_receipts' => 'Train Ticket Receipts',
        'agent_payment_receipt' => 'Agent Payment Receipt',
        'freelance_travel_agent_commission' => 'Freelance Travel Agent Commission Payment Receipts'
    ),
    'Reports' => array(
        'summary_report' => 'Summary',
        'daily_movement_register' => 'Daily Movement Register',
        'department_report' => 'Department Wise Report',
        'source_channel_reports' => 'Source/Channel Wise Reports',
        'campaign_reports' => 'Campaign wise Reports',
        'package_reports' => 'Package wise Reports',
        'destination_reports' => 'Destination Wise Reports',
        'file_manager_reports' => 'File Manager wise Reports',
        'enquiry_type_reports' => 'Enquiry Type wise Reports',
        'enquiry_status_reports' => 'Enquiry Status wise Reports',
        'lead_status_reports' => 'Lead Status Wise Reports',
        'lead_priority_reports' => 'Lead Priority Wise Reports',
        'lead_attended_user_reports' => 'Lead Attended User Wise Report',
        'tele_sales_reports' => 'Tele Sales User wise Reports',
        'reservation_team_reports' => 'Reservation Team Reports',
        'user_logs' => 'User Logs (User Session Report)',
        'all_users_activity_reports' => 'All Users Activity Reports'
    ),
    'Data Module (Business Credentials)' => array(
        'accommodation_details' => 'Accommodation Details',
        'transportation_details' => 'Transportation Details',
        'cruise_details' => 'Cruise Details',
        'travel_agents' => 'Travel Agent Details',
        'hospital_details' => 'Hospital Details',
        'extras_miscellaneous_details' => 'Extras/Miscellaneous Details',
        'freelance_travel_consultant' => 'Freelance Travel Consultant',
        'packages' => 'Packages'
    ),
    'Data Module' => array(
        'destinations' => 'Destinations',
        'departments' => 'Departments',
        'source_channel' => 'Source (Channels)',
        'referral_code' => 'Referral Codes',
        'enquiry_type' => 'Enquiry Types',
        'enquiry_status' => 'Enquiry Statuses',
        'lead_status' => 'Leads Statuses',
        'night_day' => 'Night/Days'
    ),
    'Admin' => array(
        'add_user' => 'Manage Users',
        'user_privileges' => 'User Privileges',
        'api_keys' => 'API Keys',
        'smtp_setup' => 'SMTP Main Setup',
        'sms_integration' => 'SMS Integration',
        'whatsapp_api' => 'Whatsapp API',
        'instagram_api' => 'Instagram Message API',
        'messenger_api' => 'Messenger API'
    )
);

// Flatten the menu structure for processing
$menus = array();
foreach($menu_categories as $category => $category_menus) {
    $menus = array_merge($menus, $category_menus);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_privileges"])) {
    $role_id = $_POST["role_id"];
    
    if(empty($role_id)) {
        $error = "Please select a role.";
    } else {
        // Delete existing privileges for this role
        $delete_sql = "DELETE FROM user_privileges WHERE role_id = ?";
        if($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
            mysqli_stmt_bind_param($delete_stmt, "i", $role_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // Insert new privileges
            $insert_sql = "INSERT INTO user_privileges (role_id, menu_name, can_view, can_add, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)";
            if($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($insert_stmt, "isiiii", $role_id, $menu_name, $can_view, $can_add, $can_edit, $can_delete);
                
                foreach($menus as $menu_key => $menu_label) {
                    $menu_name = $menu_key;
                    $can_view = isset($_POST["view_" . $menu_key]) ? 1 : 0;
                    $can_add = isset($_POST["add_" . $menu_key]) ? 1 : 0;
                    $can_edit = isset($_POST["edit_" . $menu_key]) ? 1 : 0;
                    $can_delete = isset($_POST["delete_" . $menu_key]) ? 1 : 0;
                    
                    mysqli_stmt_execute($insert_stmt);
                }
                
                mysqli_stmt_close($insert_stmt);
                $success = "Privileges updated successfully.";
            }
        }
    }
}

// Get privileges for selected role
$privileges = array();
if(!empty($role_id)) {
    $priv_sql = "SELECT * FROM user_privileges WHERE role_id = ?";
    if($priv_stmt = mysqli_prepare($conn, $priv_sql)) {
        mysqli_stmt_bind_param($priv_stmt, "i", $role_id);
        mysqli_stmt_execute($priv_stmt);
        $priv_result = mysqli_stmt_get_result($priv_stmt);
        
        while($row = mysqli_fetch_assoc($priv_result)) {
            $privileges[$row['menu_name']] = $row;
        }
        
        mysqli_stmt_close($priv_stmt);
    }
}

// Handle role selection
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["select_role"]) && !isset($_POST["save_privileges"])) {
    $role_id = $_POST["role_id"];
}

// Handle role selection from URL (GET request)
if(isset($_GET["role_id"]) && isset($_GET["select_role"])) {
    $role_id = $_GET["role_id"];
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="page-header">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="title">
                        <h4>User Privileges Management</h4>
                    </div>
                    <nav aria-label="breadcrumb" role="navigation">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">User Privileges</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card-box mb-30">
            <div class="pd-20">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-blue h4">Manage User Privileges</h4>
                    <div class="badge badge-info">Total Categories: <?php echo count($menu_categories); ?></div>
                <div class="badge badge-success ml-2">Total Menus: <?php echo array_sum(array_map('count', $menu_categories)); ?></div>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="role-form">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="role-id" class="form-label">Select User Role</label>
                            <select class="custom-select col-12 " id="role-id" name="role_id">
                                <option value="">Select Role</option>
                                <?php mysqli_data_seek($roles, 0); ?>
                                <?php while($role = mysqli_fetch_assoc($roles)): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($role_id == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="select_role" class="btn btn-outline-primary mr-2">
                                <i class="fa fa-search"></i> Load Role
                            </button>
                            <?php if(!empty($role_id)): ?>
                                <span class="badge badge-success">Role Selected</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                
                <?php if(!empty($role_id)): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="privileges-form">
                        <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                        
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Quick Actions:</strong> Use the checkboxes below to manage privileges efficiently
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all" onclick="toggleAllPrivileges(this.checked)">
                                    <label class="form-check-label font-weight-bold" for="select-all">
                                        <i class="fa fa-check-square"></i> Select All Privileges
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <?php foreach($menu_categories as $category => $category_menus): ?>
                                <div class="card mb-4">
                                    <div class="card-header category-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 text-white">
                                                <i class="fa fa-folder-open mr-2"></i><?php echo $category; ?>
                                                <span class="badge badge-light ml-2"><?php echo count($category_menus); ?> items</span>
                                            </h6>
                                            <div class="form-check">
                                                <?php $cat_id = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($category)); ?>
                                                <input type="checkbox" class="form-check-input category-select-all" id="cat_<?php echo $cat_id; ?>" data-category="<?php echo $cat_id; ?>" onclick="toggleCategoryPrivileges(this, '<?php echo $cat_id; ?>')">
                                                <label class="form-check-label text-white font-weight-bold" for="cat_<?php echo $cat_id; ?>">
                                                    <i class="fa fa-check-square mr-1"></i>Select All
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover mb-0 privilege-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 70%;"><i class="fa fa-list mr-2"></i>Menu Item</th>
                                                    <th class="text-center" style="width: 30%;"><i class="fa fa-check mr-1"></i>Access</th>
                                                </tr>
                                            </thead>
                                        <tbody>
                                            <?php foreach($category_menus as $menu_key => $menu_label): ?>
                                                <tr class="category-<?php echo $cat_id; ?>">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fa fa-cog mr-2 text-muted"></i>
                                                            <span><?php echo $menu_label; ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input privilege-checkbox category-<?php echo $cat_id; ?>-checkbox" id="menu_<?php echo $menu_key; ?>" name="menu_<?php echo $menu_key; ?>" value="1" 
                                                                <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_view']) ? 'checked' : ''; ?>
                                                                onchange="handleMenuAccess('<?php echo $menu_key; ?>', this.checked)">
                                                            <label class="custom-control-label" for="menu_<?php echo $menu_key; ?>"></label>
                                                        </div>
                                                        <!-- Hidden inputs for CRUD operations -->
                                                        <input type="hidden" name="view_<?php echo $menu_key; ?>" id="view_<?php echo $menu_key; ?>" value="<?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_view']) ? '1' : '0'; ?>">
                                                        <input type="hidden" name="add_<?php echo $menu_key; ?>" id="add_<?php echo $menu_key; ?>" value="<?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_add']) ? '1' : '0'; ?>">
                                                        <input type="hidden" name="edit_<?php echo $menu_key; ?>" id="edit_<?php echo $menu_key; ?>" value="<?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_edit']) ? '1' : '0'; ?>">
                                                        <input type="hidden" name="delete_<?php echo $menu_key; ?>" id="delete_<?php echo $menu_key; ?>" value="<?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_delete']) ? '1' : '0'; ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="save_privileges" class="btn btn-success btn-lg">
                                        <i class="fa fa-save"></i> Save Privileges
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                        <i class="fa fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- User Roles Summary -->
        <div class="card-box mb-30">
            <div class="pd-20">
                <h4 class="text-blue h4 mb-4">User Roles Summary</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table-export nowrap">
                        <thead class="table-dark">
                            <tr>
                                <th class="table-plus">Role ID</th>
                                <th>Role Name</th>
                                <th>Total Users</th>
                                <th class="datatable-nosort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($roles, 0); ?>
                            <?php while($role = mysqli_fetch_assoc($roles)): ?>
                                <?php
                                // Count users for this role
                                $count_sql = "SELECT COUNT(*) as user_count FROM users WHERE role_id = ?";
                                $count_stmt = mysqli_prepare($conn, $count_sql);
                                mysqli_stmt_bind_param($count_stmt, "i", $role['id']);
                                mysqli_stmt_execute($count_stmt);
                                $count_result = mysqli_stmt_get_result($count_stmt);
                                $count_row = mysqli_fetch_assoc($count_result);
                                $user_count = $count_row['user_count'];
                                mysqli_stmt_close($count_stmt);
                                ?>
                                <tr>
                                    <td class="table-plus"><?php echo $role['id']; ?></td>
                                    <td>
                                        <div class="name-avatar d-flex align-items-center">
                                            <div class="avatar mr-2">
                                                <span class="badge badge-primary"><?php echo strtoupper(substr($role['role_name'], 0, 2)); ?></span>
                                            </div>
                                            <div class="txt">
                                                <div class="weight-600"><?php echo htmlspecialchars($role['role_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user_count > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $user_count; ?> user<?php echo $user_count != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                <i class="dw dw-more"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                <a class="dropdown-item" href="?role_id=<?php echo $role['id']; ?>&select_role=1">
                                                    <i class="dw dw-eye"></i> View Privileges
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #28a745;
    border-color: #28a745;
}
.custom-switch .custom-control-label::before {
    border-radius: 0.75rem;
}
.privilege-checkbox {
    transform: scale(1.1);
}
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}
.category-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}
.privilege-table {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 5px;
    overflow: hidden;
}
</style>

<script>
    // Function to handle menu access - when menu is checked, enable all CRUD operations
    function handleMenuAccess(menuKey, checked) {
        const value = checked ? '1' : '0';
        document.getElementById('view_' + menuKey).value = value;
        document.getElementById('add_' + menuKey).value = value;
        document.getElementById('edit_' + menuKey).value = value;
        document.getElementById('delete_' + menuKey).value = value;
    }
    
    // Function to toggle all privileges
    function toggleAllPrivileges(checked) {
        const checkboxes = document.querySelectorAll('.privilege-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = checked;
            // Trigger the change event to update hidden fields
            checkbox.dispatchEvent(new Event('change'));
        });
        // Update category checkboxes
        const categoryCheckboxes = document.querySelectorAll('.category-select-all');
        categoryCheckboxes.forEach(function(checkbox) {
            checkbox.checked = checked;
        });
    }
    
    // Function to toggle category privileges
    function toggleCategoryPrivileges(checkbox, category) {
        const checked = checkbox.checked;
        const categoryCheckboxes = document.querySelectorAll('.category-' + CSS.escape(category) + '-checkbox');
        categoryCheckboxes.forEach(function(cb) {
            cb.checked = checked;
            // Trigger the change event to update hidden fields
            cb.dispatchEvent(new Event('change'));
        });
    }
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>