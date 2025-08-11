<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Set page title
$page_title = "Pipeline Leads";

// Initialize filter variables
$attended_by_filter = $file_manager_filter = $lead_type_filter = $search = "";
$date_filter = "all";
$start_date = $end_date = "";
$travel_month = "";  // For travel month filter

// Process quick month filter
if(isset($_GET["month"])) {
    $travel_month = $_GET["month"];
}

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $attended_by_filter = !empty($_POST["attended_by"]) ? trim($_POST["attended_by"]) : "";
    $file_manager_filter = !empty($_POST["file_manager"]) ? trim($_POST["file_manager"]) : "";
    $lead_type_filter = !empty($_POST["lead_type"]) ? trim($_POST["lead_type"]) : "";
    $date_filter = !empty($_POST["date_filter"]) ? trim($_POST["date_filter"]) : "all";
    $search = !empty($_POST["search"]) ? trim($_POST["search"]) : "";
    
    if($date_filter == "custom") {
        $start_date = !empty($_POST["start_date"]) ? trim($_POST["start_date"]) : "";
        $end_date = !empty($_POST["end_date"]) ? trim($_POST["end_date"]) : "";
    }
}

// Build SQL query
$sql = "SELECT e.*, 
        u.full_name AS attended_by_name, 
        d.name AS department_name, 
        s.name AS source_name,
        lsm.status_name AS lead_status,
        cl.lead_type,
        cl.file_manager_id,
        fm.full_name AS file_manager_name
        FROM enquiries e
        LEFT JOIN users u ON e.attended_by = u.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE lsm.status_name = 'Hot Prospect - Pipeline'";

// Add filters if set
if(!empty($attended_by_filter)) {
    $sql .= " AND e.attended_by = '$attended_by_filter'";
}

// Add travel month filter if set
if(!empty($travel_month)) {
    $sql .= " AND cl.travel_month = '$travel_month'";
}

if(!empty($file_manager_filter)) {
    $sql .= " AND cl.file_manager_id = '$file_manager_filter'";
}

if(!empty($lead_type_filter)) {
    $sql .= " AND cl.lead_type = '$lead_type_filter'";
}

if(!empty($search)) {
    $sql .= " AND (e.lead_number LIKE '%$search%' OR e.customer_name LIKE '%$search%' OR e.mobile_number LIKE '%$search%' OR e.email LIKE '%$search%')";
}

if($date_filter == "today") {
    $sql .= " AND DATE(e.received_datetime) = CURDATE()";
} else if($date_filter == "yesterday") {
    $sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} else if($date_filter == "this_week") {
    $sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
} else if($date_filter == "last_week") {
    $sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(DATE_SUB(NOW(), INTERVAL 1 WEEK))";
} else if($date_filter == "this_month") {
    $sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
} else if($date_filter == "this_year") {
    $sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
} else if($date_filter == "custom" && !empty($start_date) && !empty($end_date)) {
    $sql .= " AND DATE(e.received_datetime) BETWEEN '$start_date' AND '$end_date'";
}

// Add order by
$sql .= " ORDER BY e.received_datetime DESC";

// Execute query
$result = mysqli_query($conn, $sql);
$total_records = mysqli_num_rows($result);

// Get users for attended by filter dropdown - only those with pipeline leads
$attended_by_sql = "SELECT DISTINCT u.* FROM users u 
                    JOIN enquiries e ON u.id = e.attended_by 
                    JOIN lead_status_map lsm ON e.id = lsm.enquiry_id 
                    WHERE lsm.status_name = 'Hot Prospect - Pipeline' 
                    ORDER BY u.full_name";
$attended_by_users = mysqli_query($conn, $attended_by_sql);

// Get file managers for filter dropdown - only those with pipeline leads
$file_manager_sql = "SELECT DISTINCT u.* FROM users u 
                     JOIN converted_leads cl ON u.id = cl.file_manager_id 
                     JOIN lead_status_map lsm ON cl.enquiry_id = lsm.enquiry_id 
                     WHERE lsm.status_name = 'Hot Prospect - Pipeline' 
                     ORDER BY u.full_name";
$file_manager_users = mysqli_query($conn, $file_manager_sql);
?>

<div class="row">
    <div class="col-md-12">

        
        <!-- Quick Month Filter Tabs -->
        <div class="card-box mb-20">
            <div class="pd-20">
                <h4 class="text-blue h4 mb-3">Quick Month Filters</h4>
                <div class="quick-filter-container">
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="" <?php echo empty($travel_month) ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php'">
                        <span class="quick-filter-label">All</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="March" <?php echo ($travel_month == 'March') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=March'">
                        <span class="quick-filter-label">Mar</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="April" <?php echo ($travel_month == 'April') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=April'">
                        <span class="quick-filter-label">Apr</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="May" <?php echo ($travel_month == 'May') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=May'">
                        <span class="quick-filter-label">May</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="June" <?php echo ($travel_month == 'June') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=June'">
                        <span class="quick-filter-label">Jun</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="July" <?php echo ($travel_month == 'July') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=July'">
                        <span class="quick-filter-label">Jul</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="August" <?php echo ($travel_month == 'August') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=August'">
                        <span class="quick-filter-label">Aug</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="September" <?php echo ($travel_month == 'September') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=September'">
                        <span class="quick-filter-label">Sep</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="October" <?php echo ($travel_month == 'October') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=October'">
                        <span class="quick-filter-label">Oct</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="November" <?php echo ($travel_month == 'November') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=November'">
                        <span class="quick-filter-label">Nov</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="December" <?php echo ($travel_month == 'December') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=December'">
                        <span class="quick-filter-label">Dec</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="January" <?php echo ($travel_month == 'January') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=January'">
                        <span class="quick-filter-label">Jan</span>
                    </label>
                    <label class="quick-filter-item">
                        <input type="radio" name="month_filter" value="February" <?php echo ($travel_month == 'February') ? 'checked' : ''; ?> onchange="window.location.href='pipeline.php?month=February'">
                        <span class="quick-filter-label">Feb</span>
                    </label>
                </div>
            </div>
        </div>
        
        <style>
        .quick-filter-container {
            display: flex;
            gap: 3px;
            overflow-x: auto;
        }
        
        .quick-filter-item {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin: 0;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #fff;
            transition: all 0.2s ease;
            font-size: 13px;
            font-weight: 500;
        }
        
        .quick-filter-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }
        
        .quick-filter-item input[type="radio"] {
            margin: 0 6px 0 0;
            transform: scale(0.9);
        }
        
        .quick-filter-item input[type="radio"]:checked + .quick-filter-label {
            color: #007bff;
            font-weight: 600;
        }
        
        .quick-filter-item:has(input[type="radio"]:checked) {
            background: #e3f2fd;
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0,123,255,0.1);
        }
        
        .quick-filter-label {
            color: #495057;
            font-size: 13px;
            white-space: nowrap;
        }
        </style>
        
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
                                <?php while($user = mysqli_fetch_assoc($attended_by_users)): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($attended_by_filter == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>File Manager</label>
                            <select class="custom-select" id="file-manager-filter" name="file_manager">
                                <option value="">All</option>
                                <?php while($user = mysqli_fetch_assoc($file_manager_users)): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($file_manager_filter == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lead Type</label>
                            <select class="custom-select" id="lead-type-filter" name="lead_type">
                                <option value="">All</option>
                                <option value="Hot" <?php echo ($lead_type_filter == "Hot") ? 'selected' : ''; ?>>Hot</option>
                                <option value="Warm" <?php echo ($lead_type_filter == "Warm") ? 'selected' : ''; ?>>Warm</option>
                                <option value="Cold" <?php echo ($lead_type_filter == "Cold") ? 'selected' : ''; ?>>Cold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Filter</label>
                            <select class="custom-select" id="date-filter" name="date_filter">
                                <option value="all" <?php echo ($date_filter == "all") ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo ($date_filter == "today") ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo ($date_filter == "yesterday") ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo ($date_filter == "this_week") ? 'selected' : ''; ?>>This Week</option>
                                <option value="last_week" <?php echo ($date_filter == "last_week") ? 'selected' : ''; ?>>Last Week</option>
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
        
        <!-- Leads Table -->
        <div class="card-box mb-30">
            <div class="pd-20">
                <h4 class="text-blue h4">Pipeline Leads (<?php echo $total_records; ?> total)</h4>
            </div>
            <div class="pb-20">
                <div class="table-responsive" style="max-height: 500px; overflow: auto;">
                    <table class="data-table table stripe hover nowrap">
                    <thead>
                        <tr>
                            <th style="min-width: 120px;">Date &<br>Time</th>
                            <th style="min-width: 100px;">Lead<br>Number</th>
                            <th style="min-width: 120px;">Customer<br>Name</th>
                            <th style="min-width: 100px;">Mobile<br>Number</th>
                            
                            
                            <th style="min-width: 100px;">Department</th>
                            <th style="min-width: 80px;">Source</th>
                            <th style="min-width: 80px;">Lead Type</th>
                            <th style="min-width: 100px;">Attended By</th>
                            <th style="min-width: 100px;">File Manager</th>
                            <th class="datatable-nosort" style="min-width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></td>
                                <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['lead_type'] ?? 'Not Set'); ?></td>
                                <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned'); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                            <i class="dw dw-more"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                            <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                            <a class="dropdown-item" href="new_cost_file.php?id=<?php echo $row['id']; ?>"><i class="dw dw-file"></i> Cost File</a>
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
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('.data-table')) {
            $('.data-table').DataTable().destroy();
        }
        
        $('.data-table').DataTable({
            scrollCollapse: true,
            autoWidth: false,
            responsive: true,
            searching: false,  // Disable built-in search as we have custom filter
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
        
        // Show/hide custom date range based on date filter selection
        document.getElementById('date-filter').addEventListener('change', function() {
            var customDateRange = document.getElementById('custom-date-range');
            if (this.value === 'custom') {
                customDateRange.style.display = 'flex';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>