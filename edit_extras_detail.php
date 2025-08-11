<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$extras_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: extras_details.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM extras_details WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $extras_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: extras_details.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $extras_details = trim($_POST['extras_details']);
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $adult_price = floatval($_POST['adult_price']);
    $kids_price = floatval($_POST['kids_price']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($extras_details) || empty($name) || empty($contact_number) || empty($department)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE extras_details SET destination = ?, extras_details = ?, name = ?, contact_number = ?, department = ?, email = ?, adult_price = ?, kids_price = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssddsi", $destination, $extras_details, $name, $contact_number, $department, $email, $adult_price, $kids_price, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Extras detail updated successfully!";
                // Refresh data
                $extras_detail['destination'] = $destination;
                $extras_detail['extras_details'] = $extras_details;
                $extras_detail['name'] = $name;
                $extras_detail['contact_number'] = $contact_number;
                $extras_detail['department'] = $department;
                $extras_detail['email'] = $email;
                $extras_detail['adult_price'] = $adult_price;
                $extras_detail['kids_price'] = $kids_price;
                $extras_detail['status'] = $status;
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
        <h4 class="text-blue h4">Edit Extras/Miscellaneous Detail</h4>
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
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($extras_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($extras_detail['name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Extras/Miscellaneous Details <span class="text-danger">*</span></label>
                        <input type="text" name="extras_details" class="form-control" value="<?php echo htmlspecialchars($extras_detail['extras_details']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($extras_detail['contact_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($extras_detail['department']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($extras_detail['email']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adult Price (₹)</label>
                        <input type="number" step="0.01" name="adult_price" class="form-control" value="<?php echo $extras_detail['adult_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price (₹)</label>
                        <input type="number" step="0.01" name="kids_price" class="form-control" value="<?php echo $extras_detail['kids_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($extras_detail['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($extras_detail['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Extras Detail</button>
                <a href="extras_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>