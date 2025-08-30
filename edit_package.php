<?php
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('manage_packages')) {
    header("location: index.php");
    exit;
}

$success_message = $error_message = "";
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($package_id <= 0) {
    header("Location: Packages.php");
    exit;
}

// Get package details
$sql = "SELECT * FROM packages WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $package_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$package = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$package) {
    header("Location: Packages.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST["package_name"]);
    $package_price = trim($_POST["package_price"]);
    $department_id = $_POST["department_id"];
    $status = $_POST["status"];
    
    if (empty($package_name) || empty($package_price) || empty($department_id) || empty($status)) {
        $error_message = "All fields are required.";
    } else {
        $sql = "UPDATE packages SET package_name = ?, package_price = ?, department_id = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssisi", $package_name, $package_price, $department_id, $status, $package_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Package updated successfully!";
                header("refresh:2;url=Packages.php");
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get departments
$departments_sql = "SELECT * FROM departments ORDER BY name";
$departments_result = mysqli_query($conn, $departments_sql);
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix">
        <div class="pull-left">
            <h4 class="text-blue h4">Edit Package</h4>
        </div>
        <div class="pull-right">
            <a href="Packages.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Packages
            </a>
        </div>
    </div>
</div>

<div class="pd-20 card-box mb-30">
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $package_id; ?>">
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Package Name</label>
            <div class="col-sm-12 col-md-10">
                <input class="form-control" type="text" name="package_name" value="<?php echo htmlspecialchars($package['package_name']); ?>" required>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Package Price</label>
            <div class="col-sm-12 col-md-10">
                <input class="form-control" type="text" name="package_price" value="<?php echo htmlspecialchars($package['package_price']); ?>" required>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Department</label>
            <div class="col-sm-12 col-md-10">
                <select class="custom-select col-12" name="department_id" required>
                    <option value="">Select Department</option>
                    <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo ($dept['id'] == $package['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Status</label>
            <div class="col-sm-12 col-md-10">
                <select class="custom-select col-12" name="status" required>
                    <option value="Active" <?php echo ($package['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($package['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        
        <div class="form-group row">
            <div class="col-sm-12 col-md-10 offset-md-2">
                <button type="submit" class="btn btn-primary">Update Package</button>
                <a href="Packages.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>