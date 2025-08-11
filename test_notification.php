<?php
session_start();
require_once "config/database.php";

function createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn) {
    $enquiry_sql = "SELECT e.*, cl.enquiry_number FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id WHERE e.id = ?";
    if($stmt = mysqli_prepare($conn, $enquiry_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($enquiry = mysqli_fetch_assoc($result)) {
            $enquiry_number = $enquiry['enquiry_number'] ?: $enquiry['lead_number'];
            $lead_number = $enquiry['lead_number'];
            $message = "New lead is assigned to you with enquiry number {$enquiry_number} and lead number {$lead_number}";
            $notification_sql = "INSERT INTO notifications (user_id, enquiry_id, enquiry_number, lead_number, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
            if($notification_stmt = mysqli_prepare($conn, $notification_sql)) {
                mysqli_stmt_bind_param($notification_stmt, "iisss", $file_manager_id, $enquiry_id, $enquiry_number, $lead_number, $message);
                if(mysqli_stmt_execute($notification_stmt)) {
                    mysqli_stmt_close($notification_stmt);
                    mysqli_stmt_close($stmt);
                    return true;
                }
                mysqli_stmt_close($notification_stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$message = "";

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['test_notification'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $file_manager_id = intval($_POST['file_manager_id']);
    
    if($enquiry_id > 0 && $file_manager_id > 0) {
        if(createLeadAssignmentNotification($enquiry_id, $file_manager_id, $conn)) {
            $message = "Test notification created successfully!";
        } else {
            $message = "Failed to create notification.";
        }
    } else {
        $message = "Please select valid enquiry and file manager.";
    }
}

// Get enquiries for testing
$enquiries_sql = "SELECT e.id, e.lead_number, e.customer_name FROM enquiries e ORDER BY e.id DESC LIMIT 10";
$enquiries = mysqli_query($conn, $enquiries_sql);

// Get users for file manager selection
$users_sql = "SELECT id, full_name FROM users ORDER BY full_name";
$users = mysqli_query($conn, $users_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Notification System</title>
    <link rel="stylesheet" href="assets/deskapp/vendors/styles/core.css">
    <link rel="stylesheet" href="assets/deskapp/vendors/styles/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Notification System</h5>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($message)): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="form-group">
                                <label>Select Enquiry:</label>
                                <select name="enquiry_id" class="form-control" required>
                                    <option value="">Choose an enquiry...</option>
                                    <?php while($enquiry = mysqli_fetch_assoc($enquiries)): ?>
                                        <option value="<?php echo $enquiry['id']; ?>">
                                            <?php echo $enquiry['lead_number'] . ' - ' . $enquiry['customer_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Select File Manager:</label>
                                <select name="file_manager_id" class="form-control" required>
                                    <option value="">Choose a file manager...</option>
                                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo $user['full_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="test_notification" class="btn btn-primary">
                                Create Test Notification
                            </button>
                        </form>
                        
                        <hr>
                        <p class="text-muted">
                            This will create a test notification for the selected file manager. 
                            The notification will appear in the header notification dropdown.
                        </p>
                        
                        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                        <a href="simple_debug.php" class="btn btn-info" target="_blank">Debug Notifications</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>