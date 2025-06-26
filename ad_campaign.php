<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('ad_campaign')) {
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$platform = $department_id = $ad_name = $planned_days = $budget = $start_date = $end_date = "";
$error = $success = "";

// Get departments for dropdown
$sql = "SELECT * FROM departments ORDER BY name";
$departments = mysqli_query($conn, $sql);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if(empty(trim($_POST["platform"]))) {
        $error = "Please enter ad platform.";
    } else {
        $platform = trim($_POST["platform"]);
    }
    
    if(empty(trim($_POST["department_id"]))) {
        $error = "Please select a department.";
    } else {
        $department_id = trim($_POST["department_id"]);
    }
    
    if(empty(trim($_POST["ad_name"]))) {
        $error = "Please enter ad name.";
    } else {
        $ad_name = trim($_POST["ad_name"]);
    }
    
    if(empty(trim($_POST["planned_days"]))) {
        $error = "Please enter planned days.";
    } else {
        $planned_days = trim($_POST["planned_days"]);
    }
    
    if(empty(trim($_POST["budget"]))) {
        $error = "Please enter budget.";
    } else {
        $budget = trim($_POST["budget"]);
    }
    
    if(empty(trim($_POST["start_date"]))) {
        $error = "Please enter start date.";
    } else {
        $start_date = trim($_POST["start_date"]);
    }
    
    if(empty(trim($_POST["end_date"]))) {
        $error = "Please enter end date.";
    } else {
        $end_date = trim($_POST["end_date"]);
    }
    
    // Check if end date is after start date
    if(!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
        $error = "End date must be after start date.";
    }
    
    // If no errors, insert into database
    if(empty($error)) {
        // Prepare an insert statement
        $sql = "INSERT INTO ad_campaigns (platform, department_id, name, planned_days, budget, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sisiiss", $platform, $department_id, $ad_name, $planned_days, $budget, $start_date, $end_date);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success = "Ad campaign added successfully.";
                
                // Clear form fields
                $platform = $department_id = $ad_name = $planned_days = $budget = $start_date = $end_date = "";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all ad campaigns
$sql = "SELECT ac.*, d.name as department_name 
        FROM ad_campaigns ac 
        JOIN departments d ON ac.department_id = d.id 
        ORDER BY ac.created_at DESC";
$campaigns = mysqli_query($conn, $sql);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Ad Campaign Management</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Campaign</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="platform" class="form-label required">Ad Platform</label>
                                <input type="text" class="form-control" id="platform" name="platform" value="<?php echo $platform; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department-id" class="form-label required">Department</label>
                                <select class="custom-select col-12 " id="department-id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php mysqli_data_seek($departments, 0); ?>
                                    <?php while($department = mysqli_fetch_assoc($departments)): ?>
                                        <option value="<?php echo $department['id']; ?>" <?php echo ($department_id == $department['id']) ? 'selected' : ''; ?>>
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
                                <label for="ad-name" class="form-label required">Ad Name</label>
                                <input type="text" class="form-control" id="ad-name" name="ad_name" value="<?php echo $ad_name; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="planned-days" class="form-label required">Planned Days</label>
                                <input type="number" class="form-control" id="planned-days" name="planned_days" value="<?php echo $planned_days; ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="budget" class="form-label required">Lifetime Budget</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="budget" name="budget" value="<?php echo $budget; ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start-date" class="form-label required">Start Date</label>
                                <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo $start_date; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end-date" class="form-label required">End Date</label>
                                <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo $end_date; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Save Campaign</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Active Campaigns</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad Name</th>
                                <th>Platform</th>
                                <th>Department</th>
                                <th>Budget</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days Left</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($campaigns) > 0): ?>
                                <?php while($campaign = mysqli_fetch_assoc($campaigns)): ?>
                                    <?php 
                                    // Calculate days left
                                    $end_date = new DateTime($campaign['end_date']);
                                    $today = new DateTime();
                                    $days_left = $today->diff($end_date)->days;
                                    $is_expired = $today > $end_date;
                                    
                                    // Update status if expired
                                    if($is_expired && $campaign['status'] == 'active') {
                                        $update_sql = "UPDATE ad_campaigns SET status = 'inactive' WHERE id = " . $campaign['id'];
                                        mysqli_query($conn, $update_sql);
                                        $campaign['status'] = 'inactive';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['platform']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['department_name']); ?></td>
                                        <td>$<?php echo number_format($campaign['budget'], 2); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($campaign['start_date'])); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($campaign['end_date'])); ?></td>
                                        <td>
                                            <?php if($is_expired): ?>
                                                <span class="text-danger">Expired</span>
                                            <?php else: ?>
                                                <?php echo $days_left; ?> days
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($campaign['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No campaigns found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>