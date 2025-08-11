<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$attended_by = $file_manager = $lead_type = $lead_status = $date_filter = $search = "";
$start_date = $end_date = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $attended_by = !empty($_POST["attended_by"]) ? $_POST["attended_by"] : "";
    $file_manager = !empty($_POST["file_manager"]) ? $_POST["file_manager"] : "";
    $lead_type = !empty($_POST["lead_type"]) ? $_POST["lead_type"] : "";
    $lead_status = !empty($_POST["lead_status"]) ? $_POST["lead_status"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    $search = !empty($_POST["search"]) ? $_POST["search"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
}

// Process quick filter
$quick_filter = "";
if(isset($_GET["quick_filter"])) {
    $quick_filter = $_GET["quick_filter"];
    
    switch($quick_filter) {
        case "fresh":
            // Fresh - no comments updated
            $lead_status = "";
            break;
        case "attended":
            // Attended - Attended Status
            $lead_status = "Prospect - Attended";
            break;
        case "progress1":
            // Progress 1 - Quote Given
            $lead_status = "Prospect - Quote given";
            break;
        case "progress2":
            // Progress 2 - In Discussion
            $lead_status = "Neutral Prospect - In Discussion";
            break;
        case "followup":
            // Followup - Call back Scheduled
            $lead_status = "Call Back - Call Back Scheduled";
            break;
    }
}

// Create lead_status_map table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (enquiry_id)
)";
mysqli_query($conn, $create_table_sql);

// Check for converted enquiries that are not in leads and add them
$check_sql = "SELECT e.* FROM enquiries e 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id 
        WHERE (e.status_id = 3 OR e.status_id = 'Converted') AND cl.enquiry_id IS NULL";

$check_result = mysqli_query($conn, $check_sql);

if(mysqli_num_rows($check_result) > 0) {
    while($enquiry = mysqli_fetch_assoc($check_result)) {
        // Generate enquiry number
        $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
        
        // Insert into converted_leads table
        $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                      VALUES (?, ?, NULL, NULL, 0)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "is", $enquiry['id'], $enquiry_number);
        mysqli_stmt_execute($insert_stmt);
    }
}



// Build the base SQL query with filters
$base_sql = "SELECT DISTINCT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.travel_month, cl.night_day, cl.booking_confirmed,
        cl.adults_count, cl.children_count, cl.infants_count, cl.children_age_details, cl.lead_type,
        lsm.status_name as lead_status, fm.full_name as file_manager_name,
        dest.name as destination_name,
        GREATEST(COALESCE(lsm.updated_at, '1970-01-01'), COALESCE(lsm.last_reason_updated_at, '1970-01-01')) as last_updated_date
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN lead_status ls ON (e.status_id = ls.id OR e.status_id = ls.name) 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        LEFT JOIN comments c ON e.id = c.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)";

// Simple count query to get total number of leads
$count_sql = "SELECT COUNT(DISTINCT e.id) 
        FROM enquiries e 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)";

// Copy the base SQL to the main query
$sql = $base_sql; // Only converted leads that are not yet confirmed

// Filter by logged-in user only for sales manager and sales team - show only their assigned leads
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if($current_user_id) {
        $sql .= " AND cl.file_manager_id = " . $current_user_id;
        $count_sql .= " AND cl.file_manager_id = " . $current_user_id;
    }
}

// Add quick filter conditions
if($quick_filter == "fresh") {
    $sql .= " AND e.id NOT IN (SELECT DISTINCT enquiry_id FROM comments WHERE enquiry_id IS NOT NULL)"; // No comments
}

$params = array();
$types = "";

if(!empty($attended_by)) {
    $sql .= " AND e.attended_by = ?";
    $params[] = $attended_by;
    $types .= "i";
}

if(!empty($file_manager)) {
    $sql .= " AND cl.file_manager_id = ?";
    $params[] = $file_manager;
    $types .= "i";
}

if(!empty($lead_type)) {
    $sql .= " AND cl.lead_type = ?";
    $params[] = $lead_type;
    $types .= "s";
}

if(!empty($lead_status) && $quick_filter != "fresh") {
    $sql .= " AND lsm.status_name = ?";
    $params[] = $lead_status;
    $types .= "s";
}

if(!empty($search)) {
    $search_term = "%" . $search . "%";
    $sql .= " AND (cl.enquiry_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
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

// Add order by clause - most recent leads first (latest date at top)
$sql .= " ORDER BY DATE(e.received_datetime) DESC, TIME(e.received_datetime) DESC, e.id DESC";

// Execute the count query to get total number of leads
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_array($count_result);
$total_records = $count_row ? $count_row[0] : 0;

// Execute the main query
if(!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

// Get users for attended by filter dropdown - only those with leads in view_leads
$attended_by_sql = "SELECT DISTINCT u.* FROM users u 
                    JOIN enquiries e ON u.id = e.attended_by 
                    WHERE e.status_id = 3 AND e.id IN (
                        SELECT cl.enquiry_id FROM converted_leads cl 
                        WHERE cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)
                    ) 
                    ORDER BY u.full_name";
$users = mysqli_query($conn, $attended_by_sql);

// Get file managers for filter dropdown - only those with leads in view_leads
$file_managers_sql = "SELECT DISTINCT u.* FROM users u 
                      JOIN converted_leads cl ON u.id = cl.file_manager_id 
                      WHERE cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) 
                      ORDER BY u.full_name";
$file_managers = mysqli_query($conn, $file_managers_sql);

// Get lead statuses for filter dropdown
$statuses_sql = "SELECT * FROM lead_status ORDER BY id";
$statuses = mysqli_query($conn, $statuses_sql);

// Get lead status options for filter dropdown - only active ones
$lead_status_sql = "SELECT * FROM enquiry_status WHERE status = 'active' ORDER BY id";
$lead_status_result = mysqli_query($conn, $lead_status_sql);
$lead_status_options = [];
while($status_row = mysqli_fetch_assoc($lead_status_result)) {
    $lead_status_options[] = $status_row['name'];
}

// Get counts for quick filters with user-based filtering
$user_filter = "";
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if($current_user_id) {
        $user_filter = " AND cl.file_manager_id = " . $current_user_id;
    }
}

$fresh_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT e.id) FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id LEFT JOIN comments c ON e.id = c.enquiry_id WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) AND c.id IS NULL" . $user_filter))[0];
$attended_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT e.id) FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) AND lsm.status_name = 'Prospect - Attended'" . $user_filter))[0];
$progress1_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT e.id) FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) AND lsm.status_name = 'Prospect - Quote given'" . $user_filter))[0];
$progress2_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT e.id) FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) AND lsm.status_name = 'Neutral Prospect - In Discussion'" . $user_filter))[0];
$followup_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(DISTINCT e.id) FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL) AND lsm.status_name = 'Call Back - Call Back Scheduled'" . $user_filter))[0];

// Check for confirmation message
$confirmation_message = "";
if(isset($_GET["confirmed"]) && $_GET["confirmed"] == 1) {
    $confirmation_message = "<div class='alert alert-success'>Lead successfully moved to Booking Confirmed.</div>";
} else if(isset($_GET["status_updated"]) && $_GET["status_updated"] == 1) {
    $confirmation_message = "<div class='alert alert-success'>Lead status updated successfully.</div>";
} else if(isset($_GET["error"]) && $_GET["error"] == 1) {
    $confirmation_message = "<div class='alert alert-danger'>Error updating lead status.</div>";
} else if(isset($_GET["error"]) && $_GET["error"] == 2) {
    $confirmation_message = "<div class='alert alert-danger'>Invalid request.</div>";
}
?>



<?php if(!empty($confirmation_message)): ?>
<div class="row">
    <div class="col-md-12">
        <?php echo $confirmation_message; ?>
    </div>
</div>
<?php endif; ?>

<!-- Include filter styles -->
<link rel="stylesheet" href="assets/css/filter-styles.css">

<!-- Quick Filter Tabs -->
<div class="card-box mb-20">
    <div class="pd-20">
        <h4 class="text-blue h4 mb-3">Quick Filters</h4>
        <div class="quick-filter-container">
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="all" <?php echo (!isset($_GET['quick_filter']) || $_GET['quick_filter'] == 'all') ? 'checked' : ''; ?> onchange="window.location.href='view_leads.php'">
                <span class="quick-filter-label">All (<?php echo $total_records; ?>)</span>
            </label>
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="fresh" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'fresh') ? 'checked' : ''; ?> onchange="window.location.href='?quick_filter=fresh'">
                <span class="quick-filter-label">Fresh (<?php echo $fresh_count; ?>)</span>
            </label>
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="attended" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'attended') ? 'checked' : ''; ?> onchange="window.location.href='?quick_filter=attended'">
                <span class="quick-filter-label">Attended (<?php echo $attended_count; ?>)</span>
            </label>
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="progress1" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'progress1') ? 'checked' : ''; ?> onchange="window.location.href='?quick_filter=progress1'">
                <span class="quick-filter-label">Progress 1 (<?php echo $progress1_count; ?>)</span>
            </label>
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="progress2" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'progress2') ? 'checked' : ''; ?> onchange="window.location.href='?quick_filter=progress2'">
                <span class="quick-filter-label">Progress 2 (<?php echo $progress2_count; ?>)</span>
            </label>
            <label class="quick-filter-item">
                <input type="radio" name="quick_filter" value="followup" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'followup') ? 'checked' : ''; ?> onchange="window.location.href='?quick_filter=followup'">
                <span class="quick-filter-label">Followup (<?php echo $followup_count; ?>)</span>
            </label>
        </div>
    </div>
</div>

<style>
.quick-filter-container {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
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
                    <label>File Manager</label>
                    <select class="custom-select" id="file-manager-filter" name="file_manager">
                        <option value="">All</option>
                        <?php mysqli_data_seek($file_managers, 0); while($manager = mysqli_fetch_assoc($file_managers)): ?>
                            <option value="<?php echo $manager['id']; ?>" <?php echo ($file_manager == $manager['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Type</label>
                    <select class="custom-select" id="lead-type-filter" name="lead_type">
                        <option value="">All</option>
                        <option value="Hot" <?php echo ($lead_type == "Hot") ? 'selected' : ''; ?>>Hot</option>
                        <option value="Warm" <?php echo ($lead_type == "Warm") ? 'selected' : ''; ?>>Warm</option>
                        <option value="Cold" <?php echo ($lead_type == "Cold") ? 'selected' : ''; ?>>Cold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Status</label>
                    <select class="custom-select" id="lead-status-filter" name="lead_status">
                        <option value="">All</option>
                        <?php foreach($lead_status_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($lead_status == $option) ? 'selected' : ''; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="search-filter">Search</label>
                    <input type="text" class="form-control" id="search-filter" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Lead #, Name, Mobile, Email">
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

<!-- Leads Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="text-blue h4">Leads (<?php echo $total_records; ?> total)</h4>
            <?php if(isAdmin()): ?>
                <!-- <a href="export_leads.php" class="btn btn-success">
                    <i class="dw dw-download"></i> Export to CSV
                </a> -->
            <?php endif; ?>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1200px;">
                <thead>
                    <tr>
                         <th style="min-width: 100px;">Lead Date</th>
                        <th style="min-width: 120px;">Lead #</th>
                       
                        <th style="min-width: 120px;">Enquiry #</th>
                        <th style="min-width: 150px;">Customer Name</th>
                        <th style="min-width: 120px;">Mobile</th>
                        <?php if(isAdmin()): ?>
                        <th style="min-width: 100px;">Source</th>
                        <?php endif; ?>
                        <th style="min-width: 120px;">Campaign</th>
                        <th style="min-width: 300px;">Lead Status</th>
                        <th style="min-width: 150px;">Received Date</th>
                        <th style="min-width: 150px;">Last Updated</th>
                        <th style="min-width: 120px;">Attended By</th>
                        <th style="min-width: 120px;">File Manager</th>
                        <th style="min-width: 100px;">Department</th>
                        <th style="min-width: 120px;">Enquiries Status</th>
                        <th class="datatable-nosort" style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr data-id="<?php echo $row['id']; ?>" class="<?php echo (isset($_GET['highlight']) && $_GET['highlight'] == $row['id']) ? 'highlight-row' : ''; ?>">
                 <td><?php echo isset($row['created_at']) ? date('d-m-Y H:i', strtotime($row['created_at'])) : date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></td>   
                <td><?php echo htmlspecialchars($row['enquiry_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                    <?php if(isAdmin()): ?>
                    <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></td>
                    <td>
                        <div>
                            <?php 
                            // Get the current status directly from the database for this row
                            $status_check_sql = "SELECT status_name FROM lead_status_map WHERE enquiry_id = " . $row['id'];
                            $status_check_result = mysqli_query($conn, $status_check_sql);
                            $db_status = '';
                            if ($status_check_result && mysqli_num_rows($status_check_result) > 0) {
                                $status_row = mysqli_fetch_assoc($status_check_result);
                                $db_status = $status_row['status_name'];
                            }
                            
                            // Get the current last reason from the database for this row
                            $db_last_reason = '';
                            $check_column_sql = "SHOW COLUMNS FROM lead_status_map LIKE 'last_reason'";
                            $column_result = mysqli_query($conn, $check_column_sql);
                            
                            if (mysqli_num_rows($column_result) > 0) {
                                $last_reason_check_sql = "SELECT last_reason FROM lead_status_map WHERE enquiry_id = " . $row['id'];
                                $last_reason_check_result = mysqli_query($conn, $last_reason_check_sql);
                                
                                if ($last_reason_check_result && mysqli_num_rows($last_reason_check_result) > 0) {
                                    $last_reason_row = mysqli_fetch_assoc($last_reason_check_result);
                                    $db_last_reason = $last_reason_row['last_reason'];
                                }
                            } else {
                                $alter_table_sql = "ALTER TABLE lead_status_map ADD COLUMN last_reason VARCHAR(100) NULL";
                                mysqli_query($conn, $alter_table_sql);
                            }
                            ?>
                            
                            <!-- Lead Status Row -->
                            <div class="d-flex align-items-center mb-2">
                                <select class="custom-select lead-status-select" name="status" style="min-width: 200px;" data-id="<?php echo $row['id']; ?>" onchange="toggleLastReasonDropdown(this, <?php echo $row['id']; ?>)">
                                    <option value="">Select Status</option>
                                    <?php 
                                    mysqli_data_seek($lead_status_result, 0);
                                    while($status_option = mysqli_fetch_assoc($lead_status_result)): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($status_option['name']); ?>" <?php echo ($db_status == $status_option['name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status_option['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="button" onclick="updateLeadStatus(<?php echo $row['id']; ?>, this)" style="background: none; border: none; color: green; font-size: 18px; cursor: pointer; margin-left: 8px;">✓</button>
                            </div>
                            
                            <!-- Last Reason Row -->
                            <div id="last-reason-container-<?php echo $row['id']; ?>" class="d-flex align-items-center" style="<?php echo ($db_status == "Not Interested - Cancelled") ? '' : 'display: none;'; ?>">
                                <label for="last-reason-<?php echo $row['id']; ?>" class="mr-2" style="min-width: 80px; margin-bottom: 0;">Last Reason:</label>
                                <select class="custom-select" name="last_reason" id="last-reason-<?php echo $row['id']; ?>" style="min-width: 200px;" onchange="this.setAttribute('data-selected', this.value)">
                                    <option value="">Select Reason</option>
                                    <option value="Amendments" <?php echo ($db_last_reason == "Amendments") ? 'selected' : ''; ?>>Amendments</option>
                                    <option value="FD(Fixed Departure)" <?php echo ($db_last_reason == "FD(Fixed Departure)") ? 'selected' : ''; ?>>FD(Fixed Departure)</option>
                                    <option value="High Flight Fare" <?php echo ($db_last_reason == "High Flight Fare") ? 'selected' : ''; ?>>High Flight Fare</option>
                                    <option value="Lack of Engagement" <?php echo ($db_last_reason == "Lack of Engagement") ? 'selected' : ''; ?>>Lack of Engagement</option>
                                    <option value="Lack of Resources" <?php echo ($db_last_reason == "Lack of Resources") ? 'selected' : ''; ?>>Lack of Resources</option>
                                    <option value="Lost to Competitor" <?php echo ($db_last_reason == "Lost to Competitor") ? 'selected' : ''; ?>>Lost to Competitor</option>
                                    <option value="No Decision" <?php echo ($db_last_reason == "No Decision") ? 'selected' : ''; ?>>No Decision</option>
                                    <option value="Not Interested" <?php echo ($db_last_reason == "Not Interested") ? 'selected' : ''; ?>>Not Interested</option>
                                    <option value="On Hold" <?php echo ($db_last_reason == "On Hold") ? 'selected' : ''; ?>>On Hold</option>
                                    <option value="Other Priorities" <?php echo ($db_last_reason == "Other Priorities") ? 'selected' : ''; ?>>Other Priorities</option>
                                    <option value="Own Travel Plans" <?php echo ($db_last_reason == "Own Travel Plans") ? 'selected' : ''; ?>>Own Travel Plans</option>
                                    <option value="Permit / Visa Issue" <?php echo ($db_last_reason == "Permit / Visa Issue") ? 'selected' : ''; ?>>Permit / Visa Issue</option>
                                    <option value="Plan Dropped" <?php echo ($db_last_reason == "Plan Dropped") ? 'selected' : ''; ?>>Plan Dropped</option>
                                    <option value="Postponed" <?php echo ($db_last_reason == "Postponed") ? 'selected' : ''; ?>>Postponed</option>
                                    <option value="Price Objection" <?php echo ($db_last_reason == "Price Objection") ? 'selected' : ''; ?>>Price Objection</option>
                                    <option value="Product /Service mismatch" <?php echo ($db_last_reason == "Product /Service mismatch") ? 'selected' : ''; ?>>Product /Service mismatch</option>
                                    <option value="Unresponsive" <?php echo ($db_last_reason == "Unresponsive") ? 'selected' : ''; ?>>Unresponsive</option>
                                </select>
                                <button type="button" onclick="updateLastReason(<?php echo $row['id']; ?>, this)" style="background: none; border: none; color: green; font-size: 18px; cursor: pointer; margin-left: 8px;">✓</button>
                            </div>
                        </div>
                    </td>
                    <td><?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></td>
                    <td><?php echo ($row['last_updated_date'] && $row['last_updated_date'] != '1970-01-01 00:00:00') ? date('d-m-Y H:i', strtotime($row['last_updated_date'])) : 'Not Updated'; ?></td>
                    <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned'); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['status_name']); ?></td>
                    <td>
                        <div class="dropdown">
                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                <i class="dw dw-more"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>"><i class="dw dw-eye"></i> View</a>
                                <a class="dropdown-item" href="new_cost_file.php?id=<?php echo $row['id']; ?>"><i class="dw dw-file"></i> Cost Sheet</a>
                                <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=lead"><i class="dw dw-chat"></i> Comments</a>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- View Modal for Lead ID: <?php echo $row['id']; ?> -->
                <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewModalLabel<?php echo $row['id']; ?>">Lead Details - <?php echo htmlspecialchars($row['customer_name']); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Lead Number:</strong> <?php echo htmlspecialchars($row['enquiry_number']); ?></p>
                                        <p><strong>Enquiry Number:</strong> <?php echo htmlspecialchars($row['lead_number']); ?></p>
                                        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($row['customer_name']); ?></p>
                                        <p><strong>Mobile Number:</strong> <?php echo htmlspecialchars($row['mobile_number']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></p>
                                        <p><strong>Customer Location:</strong> <?php echo htmlspecialchars($row['customer_location'] ?? 'N/A'); ?></p>
                                        <p><strong>Secondary Contact:</strong> <?php echo htmlspecialchars($row['secondary_contact'] ?? 'N/A'); ?></p>
                                        <p><strong>Referral Code:</strong> <?php echo htmlspecialchars($row['referral_code'] ?? 'N/A'); ?></p>
                                        <p><strong>Social Media Link:</strong> <?php echo htmlspecialchars($row['social_media_link'] ?? 'N/A'); ?></p>
                                        <p><strong>Enquiry Type:</strong> <?php echo htmlspecialchars($row['enquiry_type'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($row['department_name']); ?></p>
                                        <p><strong>Source:</strong> <?php echo htmlspecialchars($row['source_name']); ?></p>
                                        <p><strong>Campaign:</strong> <?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Attended By:</strong> <?php echo htmlspecialchars($row['attended_by_name']); ?></p>
                                        <p><strong>File Manager:</strong> <?php echo htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned'); ?></p>
                                        <p><strong>Lead Status:</strong> <?php echo htmlspecialchars($row['lead_status'] ?? 'N/A'); ?></p>
                                        <p><strong>Enquiry Status:</strong> <?php echo htmlspecialchars($row['status_name']); ?></p>
                                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($row['destination_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Lead Type:</strong> <?php echo htmlspecialchars($row['lead_type'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Travel Start Date:</strong> <?php echo $row['travel_start_date'] ? date('d-m-Y', strtotime($row['travel_start_date'])) : 'N/A'; ?></p>
                                        <p><strong>Travel End Date:</strong> <?php echo $row['travel_end_date'] ? date('d-m-Y', strtotime($row['travel_end_date'])) : 'N/A'; ?></p>
                                        <p><strong>Travel Month:</strong> <?php echo htmlspecialchars($row['travel_month'] ?? 'N/A'); ?></p>
                                        <p><strong>Night/Day:</strong> <?php echo htmlspecialchars($row['night_day'] ?? 'N/A'); ?></p>
                                        <p><strong>Received Date:</strong> <?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Lead Date:</strong> <?php echo isset($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : date('d-m-Y', strtotime($row['received_datetime'])); ?></p>
                                        <p><strong>Last Updated:</strong> <?php echo ($row['last_updated_date'] && $row['last_updated_date'] != '1970-01-01 00:00:00') ? date('d-m-Y H:i', strtotime($row['last_updated_date'])) : 'Not Updated'; ?></p>
                                        <p><strong>Booking Confirmed:</strong> <?php echo $row['booking_confirmed'] ? 'Yes' : 'No'; ?></p>
                                        <p><strong>Other Details:</strong> <?php echo htmlspecialchars($row['other_details'] ?? 'N/A'); ?></p>
                                        <p><strong>Adults Count:</strong> <?php echo htmlspecialchars($row['adults_count'] ?? 'N/A'); ?></p>
                                        <p><strong>Children Count:</strong> <?php echo htmlspecialchars($row['children_count'] ?? 'N/A'); ?></p>
                                        <p><strong>Infants Count:</strong> <?php echo htmlspecialchars($row['infants_count'] ?? 'N/A'); ?></p>
                                        <p><strong>Children Age Details:</strong> <?php echo htmlspecialchars($row['children_age_details'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Comment Modal -->
<div class="modal fade" id="add-comment-modal" tabindex="-1" role="dialog" aria-labelledby="add-comment-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-comment-modal-title">Comments</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="comments-container">
                    <!-- Comments will be loaded here -->
                </div>
                <hr>
                <form id="comment-form">
                    <input type="hidden" name="enquiry_id" id="enquiry-id">
                    <input type="hidden" name="table_id" id="table-id">
                    <div class="form-group">
                        <label for="comment">Add Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Comment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to toggle Last Reason dropdown visibility
function toggleLastReasonDropdown(selectElement, rowId) {
    var lastReasonContainer = document.getElementById('last-reason-container-' + rowId);
    if (selectElement.value === 'Not Interested - Cancelled') {
        lastReasonContainer.style.display = 'block';
    } else {
        lastReasonContainer.style.display = 'none';
    }
}

// Initialize all dropdowns on page load
document.addEventListener('DOMContentLoaded', function() {
    var statusSelects = document.querySelectorAll('.lead-status-select');
    statusSelects.forEach(function(select) {
        var rowId = select.getAttribute('data-id');
        toggleLastReasonDropdown(select, rowId);
    });
});

// Initialize DataTable with custom options
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('load', function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            // Destroy any existing DataTable instance
            if ($.fn.DataTable.isDataTable('.data-table')) {
                $('.data-table').DataTable().destroy();
            }
            
            // Initialize with custom options
            $('.data-table').DataTable({
                scrollCollapse: true,
                autoWidth: false,
                responsive: true,
                searching: false,  // Disable built-in search as we have custom filter
                ordering: true,
                paging: true,
                info: true,
                stateSave: false,
                order: [[0, 'desc']],  // Sort by Lead Date descending (most recent first)
                columnDefs: [{
                    targets: "datatable-nosort",
                    orderable: false,
                }, {
                    targets: 0,  // Lead Date column
                    orderable: true,
                    type: 'date'
                }, {
                    targets: <?php echo isAdmin() ? '9' : '8'; ?>,  // Last Updated column (adjust index based on admin view)
                    orderable: true,
                    type: 'date'
                }],
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "language": {
                    "info": "_START_-_END_ of _TOTAL_ entries",
                    searchPlaceholder: "Search",
                    paginate: {
                        next: '<i class="ion-chevron-right"></i>',
                        previous: '<i class="ion-chevron-left"></i>'
                    }
                }
            });
        }
    });
});

function saveStatus(link, id) {
    var status = $('.lead-status-select[data-id="' + id + '"]').val();
    if (!status) {
        alert('Please select a status first');
        return false;
    }
    link.href = 'direct_save_status.php?id=' + id + '&status=' + encodeURIComponent(status);
    return true;
}



// Function to save lead status with redirect logic
function saveLeadStatus(enquiryId) {
    var status = $('.lead-status-select[data-id="' + enquiryId + '"]').val();
    if (!status) {
        alert('Please select a status first');
        return;
    }
    
    if (status === 'Closed – Booked') {
        window.location.href = 'new_cost_file.php?id=' + enquiryId;
        return;
    }
    
    $.post('save_status.php', {
        enquiry_id: enquiryId,
        status: status
    }, function(response) {
        if (response.indexOf('success') !== -1) {
            alert('Status saved successfully');
        } else {
            alert('Error saving status');
        }
    });
}

function saveLastReasonDirect(enquiryId, lastReason) {
    fetch('test_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'enquiry_id=' + enquiryId + '&last_reason=' + encodeURIComponent(lastReason)
    }).then(response => response.text()).then(data => {
        if(data.includes('saved')) {
            alert('Saved: ' + lastReason);
        } else {
            alert('Error: ' + data);
        }
    });
}

function updateLeadStatus(id, button) {
    console.log('updateLeadStatus called with ID:', id);
    
    // Find the select element in the same row as the button
    var row = button.closest('tr');
    var statusSelect = row.querySelector('.lead-status-select');
    
    if (!statusSelect) {
        console.error('Status select not found for ID:', id);
        return;
    }
    
    var selectedStatus = statusSelect.value;
    console.log('Selected status:', selectedStatus);
    
    if(selectedStatus) {
        // Create and submit form immediately
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_lead_status.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'enquiry_id';
        idInput.value = id;
        
        var statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status_name';
        statusInput.value = selectedStatus;
        
        form.appendChild(idInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        
        console.log('Submitting form with data:', {enquiry_id: id, status_name: selectedStatus});
        form.submit();
    } else {
        console.error('No status selected');
    }
}

function updateLastReason(id, button) {
    console.log('updateLastReason called with ID:', id);
    
    // Find the select element in the same row as the button
    var row = button.closest('tr');
    var reasonSelect = row.querySelector('#last-reason-' + id);
    
    if (!reasonSelect) {
        console.error('Last reason select not found for ID:', id);
        return;
    }
    
    var selectedReason = reasonSelect.value;
    console.log('Selected reason:', selectedReason);
    
    if(selectedReason) {
        // Create and submit form immediately
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_last_reason.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'enquiry_id';
        idInput.value = id;
        
        var reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'last_reason';
        reasonInput.value = selectedReason;
        
        form.appendChild(idInput);
        form.appendChild(reasonInput);
        document.body.appendChild(form);
        
        console.log('Submitting form with data:', {enquiry_id: id, last_reason: selectedReason});
        form.submit();
    } else {
        console.error('No reason selected');
    }
}
</script>
<script>
    // Show/hide custom date range based on date filter selection
    document.getElementById('date-filter').addEventListener('change', function() {
        var customDateRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
        }
    });
    
    // Function to move lead to confirmed
    function moveToConfirmed(id) {
        if (confirm('Are you sure you want to move this lead to Booking Confirmed?')) {
            // Create form data
            var formData = new FormData();
            formData.append('enquiry_id', id);
            
            // Send AJAX request
            fetch('move_to_confirmed.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                alert('Lead successfully moved to Booking Confirmed');
                // Reload the page to refresh the table
                window.location.href = 'view_leads.php?confirmed=1';
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    }
    
    // Set enquiry ID in comment modal and load comments
    $(document).ready(function() {
        
        $('.comment-link').on('click', function(e) {
            var enquiryId = $(this).data('id');
            var customerName = $(this).data('customer');
            console.log("Comment link clicked for enquiry ID:", enquiryId, "Customer:", customerName);
            
            // Set the modal title with customer name
            $('#add-comment-modal-title').text('Comments for ' + customerName);
            
            // Set form values
            $('#enquiry-id').val(enquiryId);
            $('#table-id').val('enquiries');
            
            // Load existing comments
            loadComments(enquiryId);
        });
        
        $('#add-comment-modal').on('show.bs.modal', function (event) {
            // Modal is being shown - nothing additional needed here
            // The click handler above will have already set everything up
        });
        
        // Function to load comments
        function loadComments(enquiryId) {
            console.log("Loading comments for enquiry ID:", enquiryId);
            $('#comments-container').html('<p>Loading comments...</p>');
            
            $.ajax({
                url: 'get_comments.php',
                type: 'GET',
                data: {
                    enquiry_id: enquiryId
                },
                success: function(response) {
                    console.log("Response received:", response);
                    try {
                        var data = JSON.parse(response);
                        console.log("Parsed data:", data);
                        if (data.success) {
                            var commentsHtml = '';
                            if (data.comments && data.comments.length > 0) {
                                data.comments.forEach(function(comment) {
                                    commentsHtml += '<div class="comment-box">' +
                                        '<div class="comment-header">' +
                                        '<span class="comment-user">' + comment.user_name + '</span>' +
                                        '<span class="comment-date">' + comment.created_at + '</span>' +
                                        '</div>' +
                                        '<div class="comment-body">' + comment.comment.replace(/\n/g, '<br>') + '</div>' +
                                        '</div>';
                                });
                            } else {
                                commentsHtml = '<p class="text-muted">No comments yet.</p>';
                            }
                            $('#comments-container').html(commentsHtml);
                        } else {
                            $('#comments-container').html('<p class="text-danger">Error loading comments: ' + data.message + '</p>');
                        }
                    } catch (e) {
                        console.error("Error parsing JSON:", e, response);
                        $('#comments-container').html('<p class="text-danger">Error processing response</p>');
                    }
                },
                error: function() {
                    $('#comments-container').html('<p class="text-danger">Error loading comments</p>');
                }
            });
        }
        
        // Initialize dropdown functionality
        $('.dropdown-toggle').dropdown();
        
        // Handle form submission via AJAX
        $('#comment-form').on('submit', function(e) {
            e.preventDefault();
            var enquiryId = $('#enquiry-id').val();
            var comment = $('#comment').val();
            
            $.ajax({
                url: 'add_comment.php',
                type: 'POST',
                data: {
                    enquiry_id: enquiryId,
                    comment: comment,
                    table_id: $('#table-id').val()
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            // Clear the textarea
                            $('#comment').val('');
                            
                            // Reload comments
                            loadComments(enquiryId);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('Error processing response');
                    }
                },
                error: function() {
                    alert('Error submitting comment');
                }
            });
        });
    });
</script>



<script src="view_leads_update.js"></script>

<?php
// Include footer
require_once "includes/footer.php";
?>