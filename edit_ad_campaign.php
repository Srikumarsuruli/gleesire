<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('ad_campaign')) {
    header("location: index.php");
    exit;
}

// Check if ID parameter exists
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: ad_campaign.php");
    exit;
}

$id = trim($_GET["id"]);
$error = $success = "";

// Get departments for dropdown
$sql = "SELECT * FROM departments ORDER BY name";
$departments = mysqli_query($conn, $sql);

// Fetch campaign data
$sql = "SELECT * FROM ad_campaigns WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $campaign = mysqli_fetch_assoc($result);
        } else {
            header("location: ad_campaign.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        exit;
    }
    
    mysqli_stmt_close($stmt);
}

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
    
    // If no errors, update database
    if(empty($error)) {
        $sql = "UPDATE ad_campaigns SET platform = ?, department_id = ?, name = ?, planned_days = ?, budget = ?, start_date = ?, end_date = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisiissi", $platform, $department_id, $ad_name, $planned_days, $budget, $start_date, $end_date, $id);
            
            if(mysqli_stmt_execute($stmt)) {
                $success = "Campaign updated successfully.";
                
                // Refresh campaign data
                $sql = "SELECT * FROM ad_campaigns WHERE id = ?";
                if($stmt2 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt2, "i", $id);
                    
                    if(mysqli_stmt_execute($stmt2)) {
                        $result = mysqli_stmt_get_result($stmt2);
                        
                        if(mysqli_num_rows($result) == 1) {
                            $campaign = mysqli_fetch_assoc($result);
                        }
                    }
                    
                    mysqli_stmt_close($stmt2);
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
        <h2 class="mb-4">Edit Ad Campaign</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Campaign Details</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="platform" class="form-label required">Ad Platform</label>
                                <input type="text" class="form-control" id="platform" name="platform" value="<?php echo htmlspecialchars($campaign['platform']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department-id" class="form-label required">Department</label>
                                <select class="custom-select col-12" id="department-id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php mysqli_data_seek($departments, 0); ?>
                                    <?php while($department = mysqli_fetch_assoc($departments)): ?>
                                        <option value="<?php echo $department['id']; ?>" <?php echo ($department['id'] == $campaign['department_id']) ? 'selected' : ''; ?>>
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
                                <input type="text" class="form-control" id="ad-name" name="ad_name" value="<?php echo htmlspecialchars($campaign['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="planned-days" class="form-label required">Planned Days</label>
                                <input type="number" class="form-control" id="planned-days" name="planned_days" value="<?php echo htmlspecialchars($campaign['planned_days']); ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="budget" class="form-label required">Lifetime Budget</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="budget" name="budget" value="<?php echo htmlspecialchars($campaign['budget']); ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start-date" class="form-label required">Start Date</label>
                                <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo htmlspecialchars($campaign['start_date']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end-date" class="form-label required">End Date</label>
                                <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo htmlspecialchars($campaign['end_date']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update Campaign</button>
                            <a href="ad_campaign.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>