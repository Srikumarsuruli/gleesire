<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$transport_detail = null;

// Get transport detail ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: transport_details.php");
    exit;
}

$id = $_GET['id'];

// Fetch existing transport detail
$sql = "SELECT * FROM transport_details WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $transport_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: transport_details.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

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
        $sql = "UPDATE transport_details SET destination = ?, company_name = ?, contact_person = ?, mobile = ?, email = ?, vehicle = ?, daily_rent = ?, rate_per_km = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssddsi", $destination, $company_name, $contact_person, $mobile, $email, $vehicle, $daily_rent, $rate_per_km, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Transport detail updated successfully!";
                // Refresh the transport detail data
                $transport_detail['destination'] = $destination;
                $transport_detail['company_name'] = $company_name;
                $transport_detail['contact_person'] = $contact_person;
                $transport_detail['mobile'] = $mobile;
                $transport_detail['email'] = $email;
                $transport_detail['vehicle'] = $vehicle;
                $transport_detail['daily_rent'] = $daily_rent;
                $transport_detail['rate_per_km'] = $rate_per_km;
                $transport_detail['status'] = $status;
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
        <h4 class="text-blue h4">Edit Transport Detail</h4>
        <p class="mb-0">Update the transport provider details below</p>
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
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($transport_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($transport_detail['company_name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($transport_detail['contact_person']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($transport_detail['mobile']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($transport_detail['email']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Vehicle <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle" class="form-control" value="<?php echo htmlspecialchars($transport_detail['vehicle']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Daily Rent (₹)</label>
                        <input type="number" step="0.01" name="daily_rent" class="form-control" value="<?php echo $transport_detail['daily_rent']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rate per KM (₹)</label>
                        <input type="number" step="0.01" name="rate_per_km" class="form-control" value="<?php echo $transport_detail['rate_per_km']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($transport_detail['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($transport_detail['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Transport Detail</button>
                <a href="transport_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>