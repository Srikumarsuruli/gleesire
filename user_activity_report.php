<?php
require_once "includes/header.php";

if(!hasPrivilege('reports')) {
    header("location: index.php");
    exit;
}

$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$user_filter = '';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $start_date = !empty($_POST["start_date"]) ? $_POST["start_date"] : date('Y-m-d', strtotime('-30 days'));
    $end_date = !empty($_POST["end_date"]) ? $_POST["end_date"] : date('Y-m-d');
    $user_filter = $_POST["user_filter"] ?? '';
}

// Get users for filter
$users_sql = "SELECT id, username FROM users ORDER BY username";
$users_result = mysqli_query($conn, $users_sql);

// Get activity data
$activities = [];

// Get comments
$comments_sql = "SELECT 
    c.id,
    c.enquiry_id,
    c.user_id,
    c.comment,
    c.created_at,
    u.username,
    e.lead_number,
    es.name as current_status,
    tc.cost_sheet_number,
    'comment' as activity_type
FROM comments c
JOIN users u ON c.user_id = u.id
JOIN enquiries e ON c.enquiry_id = e.id
LEFT JOIN enquiry_status es ON e.status_id = es.id
LEFT JOIN tour_costings tc ON c.enquiry_id = tc.enquiry_id
WHERE DATE(c.created_at) BETWEEN ? AND ?";

if($user_filter) {
    $comments_sql .= " AND c.user_id = ?";
}

$stmt = mysqli_prepare($conn, $comments_sql);
if($user_filter) {
    mysqli_stmt_bind_param($stmt, "ssi", $start_date, $end_date, $user_filter);
} else {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
}
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($comments_result)) {
    $activities[] = $row;
}

// Get status changes
$status_sql = "SELECT 
    scl.id,
    scl.enquiry_id,
    scl.changed_by as user_id,
    scl.changed_at as created_at,
    u.username,
    e.lead_number,
    es_new.name as current_status,
    es_old.name as old_status,
    tc.cost_sheet_number,
    'status_change' as activity_type
FROM status_change_log scl
JOIN users u ON scl.changed_by = u.id
JOIN enquiries e ON scl.enquiry_id = e.id
LEFT JOIN enquiry_status es_new ON scl.new_status_id = es_new.id
LEFT JOIN enquiry_status es_old ON scl.old_status_id = es_old.id
LEFT JOIN tour_costings tc ON scl.enquiry_id = tc.enquiry_id
WHERE DATE(scl.changed_at) BETWEEN ? AND ?";

if($user_filter) {
    $status_sql .= " AND scl.changed_by = ?";
}

$stmt = mysqli_prepare($conn, $status_sql);
if($user_filter) {
    mysqli_stmt_bind_param($stmt, "ssi", $start_date, $end_date, $user_filter);
} else {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
}
mysqli_stmt_execute($stmt);
$status_result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($status_result)) {
    $activities[] = $row;
}

// Sort by created_at descending
usort($activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">User Activity Report</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>User</label>
                        <select class="form-control" name="user_filter">
                            <option value="">All Users</option>
                            <?php 
                            mysqli_data_seek($users_result, 0);
                            while($user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group mb-0">
                        <button type="submit" name="filter" class="btn btn-primary">Apply Filter</button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary ml-2">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Activity Report Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Activity Log (<?php echo count($activities); ?> activities)</h4>
    </div>
    <div class="pb-20">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Activity Type</th>
                        <th>Lead Number</th>
                        <th>Current Status</th>
                        <th>Cost Sheet</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No activities found for the selected criteria</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($activities as $activity): ?>
                    <tr>
                        <td><?php echo date('d-M-Y H:i:s', strtotime($activity['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                        <td>
                            <?php if($activity['activity_type'] == 'comment'): ?>
                                <a href="view_enquiries.php?id=<?php echo $activity['enquiry_id']; ?>#comments" class="badge badge-info" style="text-decoration: none;">Comment</a>
                            <?php else: ?>
                                <a href="view_enquiries.php?id=<?php echo $activity['enquiry_id']; ?>#status" class="badge badge-warning" style="text-decoration: none;">Status Change</a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($activity['lead_number']); ?></td>
                        <td><?php echo htmlspecialchars($activity['current_status'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($activity['cost_sheet_number'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if($activity['activity_type'] == 'comment'): ?>
                                <?php echo htmlspecialchars($activity['comment']); ?>
                            <?php else: ?>
                                Changed from "<?php echo htmlspecialchars($activity['old_status'] ?? 'N/A'); ?>" 
                                to "<?php echo htmlspecialchars($activity['current_status'] ?? 'N/A'); ?>"
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>