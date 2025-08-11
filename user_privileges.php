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
    'Enquiries Management' => array(
        'view_enquiries' => 'All Enquiries',
        'job_enquiries' => 'Job Enquiries',
        'ticket_enquiries' => 'Ticket Enquiries',
        'influencer_enquiries' => 'Influencer Enquiries',
        'dmc_agent_enquiries' => 'DMC/Agent Enquiries',
        'cruise_enquiries' => 'Cruise Enquiries',
        'no_response_enquiries' => 'No Response Enquiries',
        'follow_up_enquiries' => 'Follow up Enquiries'
    ),
    'Leads Management' => array(
        'view_leads' => 'All Leads',
        'fixed_package_lead' => 'Fixed Package Lead',
        'custom_package_leads' => 'Custom Package Leads',
        'medical_tourism_leads' => 'Medical Tourism Leads',
        'lost_to_competitors' => 'Lost to Competitors',
        'no_response_leads' => 'No Response/Rejected Leads',
        'follow_up_leads' => 'Follow up Leads'
    ),
    'Sales & Booking' => array(
        'pipeline' => 'Pipeline',
        'booking_confirmed' => 'Booking Confirmed',
        'travel_completed' => 'Travel Completed',
        'view_cost_sheets' => 'View Cost Sheets',
        'view_payment_receipts' => 'View Payment Receipts',
        'feedbacks' => 'Feedbacks'
    ),
    'Reservation/Booking' => array(
        'hotel_resorts' => 'Hotel/Resorts',
        'cruise_reservation' => 'Cruise',
        'visa_air_ticket' => 'Visa & Air Ticket',
        'transportation_reservation' => 'Transportation'
    ),
    'Marketing' => array(
        'upload_marketing_data' => 'Upload Daily Campaign Data',
        'ad_campaign' => 'Ad Campaigns'
    ),
    'Reports' => array(
        'summary_report' => 'Summary',
        'daily_movement_register' => 'Daily Movement Register',
        'user_activity_report' => 'User Activity Report',
        'department_report' => 'Department Wise Report',
        'source_report' => 'Source Wise Report',
        'user_performance_report' => 'User Performance Report',
        'package_performance_report' => 'Package Performance Report',
        'marketing_performance_report' => 'Marketing Performance Report'
    ),
    'Data Module' => array(
        'transportation_details' => 'Transportation Details',
        'accommodation_details' => 'Accommodation Details',
        'cruise_details' => 'Cruise Details',
        'extras_miscellaneous_details' => 'Extras/Miscellaneous Details'
    ),
    'Administration' => array(
        'add_user' => 'Manage Users',
        'user_privileges' => 'User Privileges'
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
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">User Privileges</h2>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Manage User Privileges</h5>
            </div>
            <div class="card-body">
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
                            <button type="submit" name="select_role" class="btn btn-secondary">Select Role</button>
                            <?php if(!empty($role_id)): ?>
                                <a href="edit_user_privileges.php?role_id=<?php echo $role_id; ?>" class="btn btn-primary ms-2">Edit Privileges</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                
                <?php if(!empty($role_id)): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="privileges-form">
                        <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all" onclick="toggleAllPrivileges(this.checked)">
                                    <label class="form-check-label" for="select-all">Select All Privileges</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <?php foreach($menu_categories as $category => $category_menus): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fa fa-folder-open"></i> <?php echo $category; ?>
                                        <div class="float-right">
                                            <small>
                                                <input type="checkbox" class="category-select-all" data-category="<?php echo str_replace(' ', '_', strtolower($category)); ?>" onclick="toggleCategoryPrivileges(this, '<?php echo str_replace(' ', '_', strtolower($category)); ?>')">
                                                <label>Select All</label>
                                            </small>
                                        </div>
                                    </h6>
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%;">Menu</th>
                                                <th class="text-center" style="width: 15%;">View</th>
                                                <th class="text-center" style="width: 15%;">Add</th>
                                                <th class="text-center" style="width: 15%;">Edit</th>
                                                <th class="text-center" style="width: 15%;">Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($category_menus as $menu_key => $menu_label): ?>
                                                <tr class="category-<?php echo str_replace(' ', '_', strtolower($category)); ?>">
                                                    <td><?php echo $menu_label; ?></td>
                                                    <td class="text-center">
                                                        <input class="form-check-input privilege-checkbox view-checkbox category-<?php echo str_replace(' ', '_', strtolower($category)); ?>-checkbox" type="checkbox" id="view_<?php echo $menu_key; ?>" name="view_<?php echo $menu_key; ?>" value="1" 
                                                            <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_view']) ? 'checked' : ''; ?>>
                                                    </td>
                                                    <td class="text-center">
                                                        <input class="form-check-input privilege-checkbox add-checkbox category-<?php echo str_replace(' ', '_', strtolower($category)); ?>-checkbox" type="checkbox" id="add_<?php echo $menu_key; ?>" name="add_<?php echo $menu_key; ?>" value="1" 
                                                            <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_add']) ? 'checked' : ''; ?>>
                                                    </td>
                                                    <td class="text-center">
                                                        <input class="form-check-input privilege-checkbox edit-checkbox category-<?php echo str_replace(' ', '_', strtolower($category)); ?>-checkbox" type="checkbox" id="edit_<?php echo $menu_key; ?>" name="edit_<?php echo $menu_key; ?>" value="1" 
                                                            <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_edit']) ? 'checked' : ''; ?>>
                                                    </td>
                                                    <td class="text-center">
                                                        <input class="form-check-input privilege-checkbox delete-checkbox category-<?php echo str_replace(' ', '_', strtolower($category)); ?>-checkbox" type="checkbox" id="delete_<?php echo $menu_key; ?>" name="delete_<?php echo $menu_key; ?>" value="1" 
                                                            <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_delete']) ? 'checked' : ''; ?>>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" name="save_privileges" class="btn btn-primary">Save Privileges</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- User Roles List -->
                <div class="mt-5">
                    <h5>All User Roles</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Role ID</th>
                                    <th>Role Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php mysqli_data_seek($roles, 0); ?>
                                <?php while($role = mysqli_fetch_assoc($roles)): ?>
                                    <tr>
                                        <td><?php echo $role['id']; ?></td>
                                        <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                        <td>
                                            <a href="edit_user_privileges.php?role_id=<?php echo $role['id']; ?>" class="btn btn-sm btn-primary">Edit Privileges</a>
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
</div>

<script>
    // Function to toggle all privileges
    function toggleAllPrivileges(checked) {
        const checkboxes = document.querySelectorAll('.privilege-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = checked;
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
        const categoryCheckboxes = document.querySelectorAll('.category-' + category + '-checkbox');
        categoryCheckboxes.forEach(function(cb) {
            cb.checked = checked;
        });
    }
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>