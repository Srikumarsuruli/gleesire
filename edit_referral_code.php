<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$referral_code_data = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ReferralCode.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM referral_codes WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $referral_code_data = mysqli_fetch_assoc($result);
        } else {
            header("Location: ReferralCode.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $referral_code = trim($_POST['referral_code']);
    $name = trim($_POST['name']);
    $status = $_POST['status'];
    
    if (empty($referral_code) || empty($name)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE referral_codes SET referral_code = ?, name = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $referral_code, $name, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Referral code updated successfully!";
                // Refresh data
                $referral_code_data['referral_code'] = $referral_code;
                $referral_code_data['name'] = $name;
                $referral_code_data['status'] = $status;
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
        <h4 class="text-blue h4">Edit Referral Code</h4>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Referral Code <span class="text-danger">*</span></label>
                        <input type="text" name="referral_code" class="form-control" value="<?php echo htmlspecialchars($referral_code_data['referral_code']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($referral_code_data['name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($referral_code_data['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($referral_code_data['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Referral Code</button>
                <a href="ReferralCode.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>