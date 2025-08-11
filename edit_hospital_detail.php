<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$hospital_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: HospitalDetails.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM hospital_details WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $hospital_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: HospitalDetails.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $hospital_name = trim($_POST['hospital_name']);
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($hospital_name) || empty($name) || empty($contact_number) || empty($department)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE hospital_details SET destination = ?, hospital_name = ?, name = ?, contact_number = ?, department = ?, email = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssi", $destination, $hospital_name, $name, $contact_number, $department, $email, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Hospital detail updated successfully!";
                // Refresh data
                $hospital_detail['destination'] = $destination;
                $hospital_detail['hospital_name'] = $hospital_name;
                $hospital_detail['name'] = $name;
                $hospital_detail['contact_number'] = $contact_number;
                $hospital_detail['department'] = $department;
                $hospital_detail['email'] = $email;
                $hospital_detail['status'] = $status;
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
        <h4 class="text-blue h4">Edit Hospital Detail</h4>
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
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Hospital Name <span class="text-danger">*</span></label>
                        <input type="text" name="hospital_name" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['hospital_name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['contact_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['department']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($hospital_detail['email']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($hospital_detail['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($hospital_detail['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Hospital Detail</button>
                <a href="HospitalDetails.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>