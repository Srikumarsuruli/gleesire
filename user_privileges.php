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

// Get roles for dropdown
$sql = "SELECT * FROM roles WHERE id > 1 ORDER BY id";  // Exclude admin role
$roles = mysqli_query($conn, $sql);

// Define available menus
$menus = array(
    'dashboard' => 'Dashboard',
    'upload_enquiries' => 'Upload Enquiries',
    'view_enquiries' => 'View Enquiries',
    'job_enquiries' => 'Job Enquiries',
    'ticket_enquiries' => 'Ticket Enquiries',
    'influencer_enquiries' => 'Influencer Enquiries',
    'dmc_agent_enquiries' => 'DMC/Agent Enquiries',
    'cruise_enquiries' => 'Cruise Enquiries',
    'lost_to_competitors' => 'Lost to Competitors',
    'view_leads' => 'View Leads',
    'fixed_package_lead' => 'Fixed Package Lead',
    'custom_package_leads' => 'Custom Package Leads',
    'medical_tourism_leads' => 'Medical Tourism Leads',
    'pipeline' => 'Pipeline',
    'booking_confirmed' => 'Booking Confirmed',
    'travel_completed' => 'Travel Completed',
    'view_cost_sheets' => 'View Cost Sheets',
    'hotel_resorts' => 'Hotel/Resorts Reservation',
    'cruise_reservation' => 'Cruise Reservation',
    'visa_air_ticket' => 'Visa & Air Ticket',
    'transportation_reservation' => 'Transportation Reservation',
    'feedbacks' => 'Feedbacks',
    'upload_marketing_data' => 'Upload Marketing Data',
    'ad_campaign' => 'Ad Campaigns',
    'upload_daily_campaign_data' => 'Upload Daily Campaign Data',
    'manage_users' => 'Manage Users',
    'user_privileges' => 'User Privileges',
    'transportation_details' => 'Transportation Details',
    'accommodation_details' => 'Accommodation Details',
    'cruise_details' => 'CRUISE Details',
    'extras_miscellaneous_details' => 'Extras/Miscellaneous Details'
);

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
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Menu</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Add</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($menus as $menu_key => $menu_label): ?>
                                        <tr>
                                            <td><?php echo $menu_label; ?></td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input privilege-checkbox view-checkbox" type="checkbox" id="view_<?php echo $menu_key; ?>" name="view_<?php echo $menu_key; ?>" value="1" 
                                                        <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_view']) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input privilege-checkbox add-checkbox" type="checkbox" id="add_<?php echo $menu_key; ?>" name="add_<?php echo $menu_key; ?>" value="1" 
                                                        <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_add']) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input privilege-checkbox edit-checkbox" type="checkbox" id="edit_<?php echo $menu_key; ?>" name="edit_<?php echo $menu_key; ?>" value="1" 
                                                        <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_edit']) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input privilege-checkbox delete-checkbox" type="checkbox" id="delete_<?php echo $menu_key; ?>" name="delete_<?php echo $menu_key; ?>" value="1" 
                                                        <?php echo (isset($privileges[$menu_key]) && $privileges[$menu_key]['can_delete']) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" name="save_privileges" class="btn btn-primary">Save Privileges</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
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
    }
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>