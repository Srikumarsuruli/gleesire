<?php
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

$filter = $_GET['filter'] ?? 'today';
$user_filter = $_GET['user'] ?? '';

// Get date range based on filter
$where_date = "";
switch($filter) {
    case 'today':
        $where_date = "l.date = CURDATE()";
        break;
    case 'week':
        $where_date = "l.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_date = "l.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'year':
        $where_date = "l.date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $where_date = "1=1";
}

// Get users for filter dropdown
$users_sql = "SELECT id, username FROM users ORDER BY username";
$users_result = mysqli_query($conn, $users_sql);

// Build query
$sql = "SELECT l.*, u.username 
        FROM user_login_logs l 
        JOIN users u ON l.user_id = u.id 
        WHERE $where_date";

if($user_filter) {
    $sql .= " AND l.user_id = " . intval($user_filter);
}

$sql .= " ORDER BY l.login_time DESC";
$result = mysqli_query($conn, $sql);

// Get summary stats
$stats_sql = "SELECT 
    COUNT(*) as total_sessions,
    SUM(session_duration) as total_duration,
    AVG(session_duration) as avg_duration,
    COUNT(DISTINCT user_id) as active_users
    FROM user_login_logs l 
    WHERE $where_date";

if($user_filter) {
    $stats_sql .= " AND user_id = " . intval($user_filter);
}

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

function formatDuration($seconds) {
    if($seconds <= 0) return "Active";
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">User Login Logs</h4>
    </div>
    
    <!-- Filters -->
    <div class="pd-20">
        <div class="row">
            <div class="col-md-4">
                <select class="form-control" onchange="filterLogs()" id="filter">
                    <option value="today" <?php echo $filter == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="year" <?php echo $filter == 'year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-control" onchange="filterLogs()" id="user_filter">
                    <option value="">All Users</option>
                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="pd-20">
        <div class="row">
            <div class="col-md-3">
                <div class="widget-style3">
                    <div class="widget-data">
                        <div class="weight-700 font-24 text-dark"><?php echo $stats['total_sessions']; ?></div>
                        <div class="font-14 text-secondary weight-500">Total Sessions</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget-style3">
                    <div class="widget-data">
                        <div class="weight-700 font-24 text-dark"><?php echo formatDuration($stats['total_duration']); ?></div>
                        <div class="font-14 text-secondary weight-500">Total Time</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget-style3">
                    <div class="widget-data">
                        <div class="weight-700 font-24 text-dark"><?php echo formatDuration($stats['avg_duration']); ?></div>
                        <div class="font-14 text-secondary weight-500">Average Session</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget-style3">
                    <div class="widget-data">
                        <div class="weight-700 font-24 text-dark"><?php echo $stats['active_users']; ?></div>
                        <div class="font-14 text-secondary weight-500">Active Users</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="pb-20">
        <table class="table hover data-table nowrap">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Duration</th>
                    <th>Location</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo date('H:i:s', strtotime($row['login_time'])); ?></td>
                    <td><?php echo $row['logout_time'] ? date('H:i:s', strtotime($row['logout_time'])) : 'Active'; ?></td>
                    <td><?php echo formatDuration($row['session_duration']); ?></td>
                    <td>
                        <?php 
                        $city = $row['city'] ?? 'Not Available';
                        $country = $row['country'] ?? 'Not Available';
                        if($city == 'Unknown' || $city == '' || $city == null) $city = 'Not Available';
                        if($country == 'Unknown' || $country == '' || $country == null) $country = 'Not Available';
                        echo $city . ', ' . $country;
                        ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterLogs() {
    const filter = document.getElementById('filter').value;
    const user = document.getElementById('user_filter').value;
    window.location.href = `user_logs.php?filter=${filter}&user=${user}`;
}
</script>

<?php require_once "includes/footer.php"; ?>