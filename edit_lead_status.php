<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$lead_status = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: LeadStatus.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM lead_status WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $lead_status = mysqli_fetch_assoc($result);
        } else {
            header("Location: LeadStatus.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $status = $_POST['status'];
    
    if (empty($name)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE lead_status SET name = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $name, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Enquiry status updated successfully!";
                // Refresh data
                $lead_status['name'] = $name;
                $lead_status['status'] = $status;
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
        <h4 class="text-blue h4">Edit Enquiry Status</h4>
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
                        <label>Enquiry Status <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($lead_status['name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($lead_status['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($lead_status['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Enquiry Status</button>
                <a href="LeadStatus.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>