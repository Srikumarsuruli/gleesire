<?php
// Initialize the session
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

// Include header
require_once "includes/header.php";

// Check if ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    echo "<div class='alert alert-danger'>Invalid request. No ID provided.</div>";
    require_once "includes/footer.php";
    exit;
}

$id = intval($_GET["id"]);
$type = isset($_GET["type"]) ? $_GET["type"] : "enquiry";

// Check for success/error messages
if(isset($_GET["success"]) && $_GET["success"] == 1) {
    echo "<div class='alert alert-success'>Comment added successfully.</div>";
}
if(isset($_GET["error"])) {
    $error_message = "An error occurred.";
    switch($_GET["error"]) {
        case "empty_comment":
            $error_message = "Comment cannot be empty.";
            break;
        case "execution_failed":
            $error_message = "Failed to save comment. Please try again.";
            break;
        case "prepare_failed":
            $error_message = "Database error. Please try again.";
            break;
        case "invalid_request":
            $error_message = "Invalid request.";
            break;
    }
    echo "<div class='alert alert-danger'>{$error_message}</div>";
}

// Get entity details based on type
$entity_data = null;
$redirect_url = "";

switch($type) {
    case "enquiry":
        $sql = "SELECT e.*, u.full_name as attended_by_name 
                FROM enquiries e 
                JOIN users u ON e.attended_by = u.id 
                WHERE e.id = ?";
        $redirect_url = "view_enquiries.php";
        break;
    case "lead":
        $sql = "SELECT e.*, cl.enquiry_number, u.full_name as attended_by_name 
                FROM enquiries e 
                JOIN converted_leads cl ON e.id = cl.enquiry_id 
                JOIN users u ON e.attended_by = u.id 
                WHERE e.id = ? AND cl.booking_confirmed = 0";
        $redirect_url = "view_leads.php";
        break;
    case "booking":
        $sql = "SELECT e.*, cl.enquiry_number, u.full_name as attended_by_name 
                FROM enquiries e 
                JOIN converted_leads cl ON e.id = cl.enquiry_id 
                JOIN users u ON e.attended_by = u.id 
                WHERE e.id = ? AND cl.booking_confirmed = 1";
        $redirect_url = "booking_confirmed.php";
        break;
    default:
        echo "<div class='alert alert-danger'>Invalid entity type.</div>";
        require_once "includes/footer.php";
        exit;
}

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        $entity_data = mysqli_fetch_assoc($result);
    } else {
        echo "<div class='alert alert-danger'>No record found with the provided ID.</div>";
        require_once "includes/footer.php";
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "<div class='alert alert-danger'>Database error.</div>";
    require_once "includes/footer.php";
    exit;
}

// Get comments for this entity
$comments_sql = "SELECT c.*, u.full_name as user_name 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.enquiry_id = ? 
                ORDER BY c.created_at DESC";

$comments_stmt = mysqli_prepare($conn, $comments_sql);
mysqli_stmt_bind_param($comments_stmt, "i", $id);
mysqli_stmt_execute($comments_stmt);
$comments_result = mysqli_stmt_get_result($comments_stmt);
?>


<div class="row">
    <div class="col-md-12">
        <div class="card-box mb-30">
            <div class="pd-20">
                <h4 class="text-blue h4">
                    <?php if($type == "enquiry"): ?>
                        Comments for Enquiry: <?php echo htmlspecialchars($entity_data['lead_number']); ?>
                    <?php else: ?>
                        Comments for <?php echo ucfirst($type); ?>: <?php echo htmlspecialchars($entity_data['enquiry_number']); ?>
                    <?php endif; ?>
                </h4>
                <p>Customer: <?php echo htmlspecialchars($entity_data['customer_name']); ?></p>
            </div>
            
            <div class="pd-20">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add Comment</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="add_comment.php">
                            <input type="hidden" name="enquiry_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="redirect" value="comments.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>">
                            
                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Comment</button>
                            <a href="<?php echo $redirect_url; ?>" class="btn btn-secondary">Back</a>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Comment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($comments_result) > 0): ?>
                            <?php while($comment = mysqli_fetch_assoc($comments_result)): ?>
                                <div class="comment-box mb-3 p-3 border rounded">
                                    <div class="comment-header d-flex justify-content-between mb-2">
                                        <span class="comment-user font-weight-bold"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                        <span class="comment-date text-muted"><?php echo date('d-m-Y H:i', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>