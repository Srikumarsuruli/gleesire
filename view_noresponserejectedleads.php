<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$attended_by = $date_filter = "";
$start_date = $end_date = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $attended_by = !empty($_POST["attended_by"]) ? $_POST["attended_by"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
}

// Build the SQL query
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name,
        lsm.status_name as lead_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE (lsm.status_name = 'Not Connected - No Response' OR lsm.status_name = 'Not Interested - Cancelled')";

$params = array();
$types = "";

if(!empty($attended_by)) {
    $sql .= " AND e.attended_by = ?";
    $params[] = $attended_by;
    $types .= "i";
}

if(!empty($date_filter)) {
    switch($date_filter) {
        case "today":
            $sql .= " AND DATE(e.received_datetime) = CURDATE()";
            break;
        case "yesterday":
            $sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case "this_week":
            $sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
            break;
        case "this_month":
            $sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "this_year":
            $sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $sql .= " AND DATE(e.received_datetime) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            break;
    }
}

$sql .= " ORDER BY e.received_datetime DESC";

// Execute the query
if(!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

$total_records = mysqli_num_rows($result);

// Get users for filter dropdown - only those with no response/rejected leads
$users_sql = "SELECT DISTINCT u.* FROM users u 
              JOIN enquiries e ON u.id = e.attended_by 
              JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
              WHERE (lsm.status_name = 'Not Connected - No Response' OR lsm.status_name = 'Not Interested - Cancelled')
              ORDER BY u.full_name";
$users = mysqli_query($conn, $users_sql);
?>

<!-- Include filter styles -->
<link rel="stylesheet" href="assets/css/filter-styles.css">

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Filters</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <label>Attended By</label>
                    <select class="custom-select" id="attended-by-filter" name="attended_by">
                        <option value="">All</option>
                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($attended_by == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date Filter</label>
                    <select class="custom-select" id="date-filter" name="date_filter">
                        <option value="">All Time</option>
                        <option value="today" <?php echo ($date_filter == "today") ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo ($date_filter == "yesterday") ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="this_week" <?php echo ($date_filter == "this_week") ? 'selected' : ''; ?>>This Week</option>
                        <option value="this_month" <?php echo ($date_filter == "this_month") ? 'selected' : ''; ?>>This Month</option>
                        <option value="this_year" <?php echo ($date_filter == "this_year") ? 'selected' : ''; ?>>This Year</option>
                        <option value="custom" <?php echo ($date_filter == "custom") ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div id="custom-date-range" class="custom-date-range" <?php echo ($date_filter != "custom") ? 'style="display: none;"' : ''; ?>>
                    <div class="form-group">
                        <label for="start-date">Start Date</label>
                        <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end-date">End Date</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="filter" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- No Response/Rejected Leads Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">No Response/Rejected Leads (<?php echo $total_records; ?> total)</h4>
    </div>
    <div class="pb-20">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="data-table table stripe hover nowrap" style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th style="min-width: 100px;">Lead<br>Date</th>
                        <th style="min-width: 100px;">Lead<br>Number</th>
                        <th style="min-width: 120px;">Customer<br>Name</th>
                        <th style="min-width: 100px;">Mobile<br>Number</th>
                        <th style="min-width: 120px;">Email<br>Address</th>
                        <th style="min-width: 80px;">Source</th>
                        <th style="min-width: 100px;">Campaign</th>
                        <th style="min-width: 100px;">Lead<br>Status</th>
                        <th style="min-width: 100px;">Attended<br>By</th>
                        <th style="min-width: 100px;">Department</th>
                        <th class="datatable-nosort" style="min-width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($row['received_datetime'])); ?></td>
                        <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['lead_status']); ?></td>
                        <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                        <td>
                            <div class="dropdown">
                                <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                    <i class="dw dw-more"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                    <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                    <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=lead"><i class="dw dw-chat"></i> Comments</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.DataTable !== 'undefined') {
        if ($.fn.DataTable.isDataTable('.data-table')) {
            $('.data-table').DataTable().destroy();
        }
        
        $('.data-table').DataTable({
            scrollCollapse: true,
            autoWidth: false,
            responsive: true,
            searching: false,
            columnDefs: [{
                targets: "datatable-nosort",
                orderable: false,
            }],
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "language": {
                "info": "_START_-_END_ of _TOTAL_ entries",
                paginate: {
                    next: '<i class="ion-chevron-right"></i>',
                    previous: '<i class="ion-chevron-left"></i>'
                }
            }
        });
    }
});

// Show/hide custom date range
document.getElementById('date-filter').addEventListener('change', function() {
    var customDateRange = document.getElementById('custom-date-range');
    if (this.value === 'custom') {
        customDateRange.style.display = 'flex';
    } else {
        customDateRange.style.display = 'none';
    }
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>