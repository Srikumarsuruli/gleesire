<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$source = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: source.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM sources WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $source = mysqli_fetch_assoc($result);
        } else {
            header("Location: source.php");
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
        $sql = "UPDATE sources SET name = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $name, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Source updated successfully!";
                // Refresh data
                $source['name'] = $name;
                $source['status'] = $status;
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
        <h4 class="text-blue h4">Edit Source (Channel)</h4>
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
                        <label>Source (Channel) <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($source['name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($source['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($source['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Source</button>
                <a href="source.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>