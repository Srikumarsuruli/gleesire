<?php
// Include header
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Check if ID parameter exists
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    echo "<script>window.location.href = 'view_enquiries.php';</script>";
    exit;
}

$id = trim($_GET["id"]);
$success = $error = "";

// Process deletion
if($_SERVER["REQUEST_METHOD"] == "GET") {
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete related comments
        $sql = "DELETE FROM comments WHERE enquiry_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        // Delete related converted_leads if any
        $sql = "DELETE FROM converted_leads WHERE enquiry_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        // Delete the enquiry
        $sql = "DELETE FROM enquiries WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect using JavaScript
        echo "<script>window.location.href = 'view_enquiries.php?deleted=1';</script>";
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = "Error deleting enquiry: " . $e->getMessage();
    }
}
?>

<div class="container">
    <h2 class="my-4">Delete Enquiry</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <a href="view_enquiries.php" class="btn btn-primary">Back to Enquiries</a>
</div>

<?php require_once "includes/footer.php"; ?>
