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

// Check if comment_attachments table exists and get attachments
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'comment_attachments'");
if(mysqli_num_rows($table_check) > 0) {
    $attachments_sql = "SELECT ca.*, u.full_name as user_name 
                       FROM comment_attachments ca 
                       JOIN users u ON ca.user_id = u.id 
                       WHERE ca.enquiry_id = ? 
                       ORDER BY ca.created_at DESC";
    
    $attachments_stmt = mysqli_prepare($conn, $attachments_sql);
    mysqli_stmt_bind_param($attachments_stmt, "i", $id);
    mysqli_stmt_execute($attachments_stmt);
    $attachments_result = mysqli_stmt_get_result($attachments_stmt);
} else {
    $attachments_result = false;
}
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
                <div class="row">
                    <!-- Comments Section -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Add Comment</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="add_comment.php" onsubmit="return submitComment(this);">
                                    <input type="hidden" name="enquiry_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="redirect" value="comments.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>">
                                    
                                    <div class="form-group">
                                        <label for="comment">Comment</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Submit Comment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attachments Section -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Add Attachment</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="add_attachment.php" enctype="multipart/form-data">
                                    <input type="hidden" name="enquiry_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="redirect" value="comments.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>">
                                    
                                    <div class="form-group">
                                        <label for="attachment">Upload File (Image/PDF)</label>
                                        <input type="file" class="form-control" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf" required>
                                        <small class="text-muted">Allowed formats: JPG, JPEG, PNG, PDF</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-3">
                    <a href="<?php echo $redirect_url; ?>" class="btn btn-secondary">Back</a>
                </div>
                
                <div class="row">
                    <!-- Comments History -->
                    <div class="col-md-6">
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
                    
                    <!-- Attachments History -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Attachment History</h5>
                            </div>
                            <div class="card-body">
                                <?php if($attachments_result && mysqli_num_rows($attachments_result) > 0): ?>
                                    <?php while($attachment = mysqli_fetch_assoc($attachments_result)): ?>
                                        <div class="attachment-box mb-3 p-3 border rounded">
                                            <div class="attachment-header d-flex justify-content-between mb-2">
                                                <span class="attachment-user font-weight-bold"><?php echo htmlspecialchars($attachment['user_name']); ?></span>
                                                <span class="attachment-date text-muted"><?php echo date('d-m-Y H:i', strtotime($attachment['created_at'])); ?></span>
                                            </div>
                                            <div class="attachment-body d-flex justify-content-between align-items-center">
                                                <span><?php echo htmlspecialchars($attachment['original_name']); ?></span>
                                                <button class="btn btn-sm btn-primary" onclick="viewAttachment('<?php echo $attachment['file_path']; ?>', '<?php echo $attachment['file_type']; ?>')">View</button>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted">No attachments yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attachment View Modal -->
<div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Attachment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="attachmentContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
let isSubmitting = false;

function submitComment(form) {
    if (isSubmitting) {
        return false;
    }
    
    const comment = form.comment.value.trim();
    if (comment === '') {
        alert('Please enter a comment');
        return false;
    }
    
    isSubmitting = true;
    form.querySelector('button[type="submit"]').disabled = true;
    form.querySelector('button[type="submit"]').textContent = 'Submitting...';
    
    return true;
}

function viewAttachment(filePath, fileType) {
    const content = document.getElementById('attachmentContent');
    
    if (fileType === 'pdf') {
        content.innerHTML = `<embed src="${filePath}" type="application/pdf" width="100%" height="600px" />`;
    } else {
        content.innerHTML = `<img src="${filePath}" class="img-fluid" alt="Attachment" />`;
    }
    
    $('#attachmentModal').modal('show');
}
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>