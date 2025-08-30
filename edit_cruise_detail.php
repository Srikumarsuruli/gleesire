<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$cruise_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cruise_id <= 0) {
    header("Location: cruise_details.php");
    exit;
}

// Get cruise details
$sql = "SELECT * FROM cruise_details WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $cruise_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cruise = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$cruise) {
    header("Location: cruise_details.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $cruise_details = trim($_POST['cruise_details']);
    $boat_type = trim($_POST['boat_type']);
    $cruise_type = trim($_POST['cruise_type']);
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $adult_price = floatval($_POST['adult_price']);
    $kids_price = floatval($_POST['kids_price']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($cruise_details) || empty($name) || empty($contact_number) || empty($department)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE cruise_details SET destination = ?, cruise_details = ?, boat_type = ?, cruise_type = ?, name = ?, contact_number = ?, department = ?, email = ?, adult_price = ?, kids_price = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssssddsi", $destination, $cruise_details, $boat_type, $cruise_type, $name, $contact_number, $department, $email, $adult_price, $kids_price, $status, $cruise_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Cruise detail updated successfully!";
                header("refresh:2;url=cruise_details.php");
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="clearfix">
            <div class="pull-left">
                <h4 class="text-blue h4">Edit Cruise Detail</h4>
            </div>
            <div class="pull-right">
                <a href="cruise_details.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Cruise Details
                </a>
            </div>
        </div>
    </div>
    <div class="pd-20">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $cruise_id; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($cruise['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cruise['name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Cruise Details <span class="text-danger">*</span></label>
                        <input type="text" name="cruise_details" class="form-control" value="<?php echo htmlspecialchars($cruise['cruise_details']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Type of Boat</label>
                        <input type="text" name="boat_type" class="form-control" value="<?php echo htmlspecialchars($cruise['boat_type'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Cruise Type</label>
                        <input type="text" name="cruise_type" class="form-control" value="<?php echo htmlspecialchars($cruise['cruise_type'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($cruise['contact_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($cruise['department']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($cruise['email']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adult Price (₹)</label>
                        <input type="number" step="0.01" name="adult_price" class="form-control" value="<?php echo $cruise['adult_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price (₹)</label>
                        <input type="number" step="0.01" name="kids_price" class="form-control" value="<?php echo $cruise['kids_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($cruise['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($cruise['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Cruise Detail</button>
                <a href="cruise_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>