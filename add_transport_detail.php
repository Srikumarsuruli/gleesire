<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $vehicle = trim($_POST['vehicle']);
    $daily_rent = floatval($_POST['daily_rent']);
    $rate_per_km = floatval($_POST['rate_per_km']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($company_name) || empty($contact_person) || empty($mobile) || empty($vehicle)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO transport_details (destination, company_name, contact_person, mobile, email, vehicle, daily_rent, rate_per_km, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssdds", $destination, $company_name, $contact_person, $mobile, $email, $vehicle, $daily_rent, $rate_per_km, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Transport detail added successfully!";
                // Clear form data
                $destination = $company_name = $contact_person = $mobile = $email = $vehicle = '';
                $daily_rent = $rate_per_km = 0;
                $status = 'Active';
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
        <h4 class="text-blue h4">Add Transport Detail</h4>
        <p class="mb-0">Fill in the details below to add a new transport provider</p>
    </div>
    <div class="pd-20">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo isset($destination) ? htmlspecialchars($destination) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo isset($company_name) ? htmlspecialchars($company_name) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo isset($contact_person) ? htmlspecialchars($contact_person) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo isset($mobile) ? htmlspecialchars($mobile) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Vehicle <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle" class="form-control" value="<?php echo isset($vehicle) ? htmlspecialchars($vehicle) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Daily Rent (₹)</label>
                        <input type="number" step="0.01" name="daily_rent" class="form-control" value="<?php echo isset($daily_rent) ? $daily_rent : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rate per KM (₹)</label>
                        <input type="number" step="0.01" name="rate_per_km" class="form-control" value="<?php echo isset($rate_per_km) ? $rate_per_km : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo (isset($status) && $status == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo (isset($status) && $status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Transport Detail</button>
                <a href="transport_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>