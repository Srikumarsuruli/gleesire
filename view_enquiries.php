<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_enquiries')) {
    header("location: index.php");
    exit;
}

// Debug: Check session data (remove this after testing)
// echo '<pre>Session data: '; print_r($_SESSION); echo '</pre>';

// Define variables for filtering and pagination
$attended_by = $status_id = $search = $date_filter = $lead_type = $enquiry_type = "";
$start_date = $end_date = "";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $attended_by = !empty($_POST["attended_by"]) ? $_POST["attended_by"] : "";
    $status_id = !empty($_POST["status_id"]) ? $_POST["status_id"] : "";
    $search = !empty($_POST["search"]) ? $_POST["search"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    $lead_type = !empty($_POST["lead_type"]) ? $_POST["lead_type"] : "";
    $enquiry_type = !empty($_POST["enquiry_type"]) ? $_POST["enquiry_type"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
} else {
    // Get from URL parameters for pagination
    $attended_by = isset($_GET['attended_by']) ? $_GET['attended_by'] : "";
    $status_id = isset($_GET['status_id']) ? $_GET['status_id'] : "";
    $search = isset($_GET['search']) ? $_GET['search'] : "";
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : "";
    $lead_type = isset($_GET['lead_type']) ? $_GET['lead_type'] : "";
    $enquiry_type = isset($_GET['enquiry_type']) ? $_GET['enquiry_type'] : "";
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : "";
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : "";
}



// Build the base SQL query with filters
$base_sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ls.name as status_name,
        c.comment as recent_comment, c.created_at as comment_date
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN lead_status ls ON e.status_id = ls.id 
        LEFT JOIN (
            SELECT c1.enquiry_id, c1.comment, c1.created_at
            FROM comments c1
            WHERE c1.created_at = (
                SELECT MAX(c2.created_at)
                FROM comments c2
                WHERE c2.enquiry_id = c1.enquiry_id
            )
        ) c ON e.id = c.enquiry_id
        WHERE e.branch_id = " . $_SESSION['branch_id'] . "";

$params = array();
$types = "";

// Filter by logged-in user if not admin
if(!isAdmin()) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['role_id'] ?? null;
    
    if($current_user_id) {
        // Check if user is Lead Manager (role_id = 8) or Lead Team (role_id = 9)
        if($current_user_role == 8 || $current_user_role == 9) {
            // Lead Managers and Lead Team can see their own enquiries + chatbot enquiries
            $base_sql .= " AND (e.attended_by = ? OR u.username = 'chatbot')";
            $params[] = $current_user_id;
            $types .= "i";
        } else {
            // Other users can only see their own enquiries
            $base_sql .= " AND e.attended_by = ?";
            $params[] = $current_user_id;
            $types .= "i";
        }
    }
}

if(!empty($attended_by)) {
    $base_sql .= " AND e.attended_by = ?";
    $params[] = $attended_by;
    $types .= "i";
}

if(!empty($status_id)) {
    $base_sql .= " AND e.status_id = ?";
    $params[] = $status_id;
    $types .= "i";
}

if(!empty($search)) {
    $search_term = "%" . $search . "%";
    $base_sql .= " AND (e.lead_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ? OR e.enquiry_type LIKE ? OR s.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssssss";
}

if(!empty($enquiry_type)) {
    $base_sql .= " AND e.enquiry_type = ?";
    $params[] = $enquiry_type;
    $types .= "s";
}

if(!empty($date_filter)) {
    switch($date_filter) {
        case "today":
            $base_sql .= " AND DATE(e.received_datetime) = CURDATE()";
            break;
        case "yesterday":
            $base_sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case "this_week":
            $base_sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
            break;
        case "this_month":
            $base_sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "this_year":
            $base_sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $base_sql .= " AND DATE(e.received_datetime) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            break;
    }
}

// Get total count for pagination - use simple COUNT(*) on enquiries table
$count_sql = "SELECT COUNT(*) FROM enquiries e WHERE 1=1 AND e.branch_id = " . $_SESSION['branch_id'] . " ";

// Add the same WHERE conditions as the main query
// Filter by logged-in user if not admin
if(!isAdmin()) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['role_id'] ?? null;
    
    if($current_user_id) {
        // Check if user is Lead Manager (role_id = 8) or Lead Team (role_id = 9)
        if($current_user_role == 8 || $current_user_role == 9) {
            // Need to join users table for chatbot check in count query
            $count_sql = str_replace("FROM enquiries e WHERE", "FROM enquiries e JOIN users u ON e.attended_by = u.id WHERE", $count_sql);
            $count_sql .= " AND (e.attended_by = ? OR u.username = 'chatbot')";
        } else {
            $count_sql .= " AND e.attended_by = ?";
        }
    }
}

if(!empty($attended_by)) {
    $count_sql .= " AND e.attended_by = ?";
}
if(!empty($status_id)) {
    $count_sql .= " AND e.status_id = ?";
}
if(!empty($search)) {
    // For count query, we need to join sources table to search in channel
    $count_sql = "SELECT COUNT(*) FROM enquiries e 
                  JOIN sources s ON e.source_id = s.id
                  WHERE 1=1";
    
    // Re-add all the WHERE conditions for count query with joins
    if(!isAdmin()) {
        $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
        $current_user_role = $_SESSION['role_id'] ?? null;
        
        if($current_user_id) {
            // Check if user is Lead Manager (role_id = 8) or Lead Team (role_id = 9)
            if($current_user_role == 8 || $current_user_role == 9) {
                // Add users join if not already present for chatbot check
                if(strpos($count_sql, 'JOIN users u ON') === false) {
                    $count_sql = str_replace("FROM enquiries e", "FROM enquiries e JOIN users u ON e.attended_by = u.id", $count_sql);
                }
                $count_sql .= " AND (e.attended_by = ? OR u.username = 'chatbot')";
            } else {
                $count_sql .= " AND e.attended_by = ?";
            }
        }
    }
    if(!empty($attended_by)) {
        $count_sql .= " AND e.attended_by = ?";
    }
    if(!empty($status_id)) {
        $count_sql .= " AND e.status_id = ?";
    }
    $count_sql .= " AND (e.lead_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ? OR e.enquiry_type LIKE ? OR s.name LIKE ?)";
    if(!empty($enquiry_type)) {
        $count_sql .= " AND e.enquiry_type = ?";
    }
    if(!empty($date_filter)) {
        switch($date_filter) {
            case "today":
                $count_sql .= " AND DATE(e.received_datetime) = CURDATE()";
                break;
            case "yesterday":
                $count_sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case "this_week":
                $count_sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
                break;
            case "this_month":
                $count_sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
                break;
            case "this_year":
                $count_sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
                break;
            case "custom":
                if(!empty($start_date) && !empty($end_date)) {
                    $count_sql .= " AND DATE(e.received_datetime) BETWEEN ? AND ?";
                }
                break;
        }
    }
}
if(!empty($enquiry_type)) {
    $count_sql .= " AND e.enquiry_type = ?";
}
if(!empty($date_filter)) {
    switch($date_filter) {
        case "today":
            $count_sql .= " AND DATE(e.received_datetime) = CURDATE()";
            break;
        case "yesterday":
            $count_sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case "this_week":
            $count_sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
            break;
        case "this_month":
            $count_sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "this_year":
            $count_sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $count_sql .= " AND DATE(e.received_datetime) BETWEEN ? AND ?";
            }
            break;
    }
}

$count_stmt = mysqli_prepare($conn, $count_sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_array($count_result);
$total_records = $count_row ? $count_row[0] : 0;
$total_pages = ceil($total_records / $records_per_page);

// Add order by and pagination LIMIT for main query
$sql = $base_sql . " ORDER BY e.id DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute the main query
$stmt = mysqli_prepare($conn, $sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get users for filter dropdown - only those who have enquiries
$users_sql = "SELECT DISTINCT u.* FROM users u 
              INNER JOIN enquiries e ON u.id = e.attended_by 
              ORDER BY u.full_name";
$users = db_query($conn, $users_sql);

// Get lead statuses for filter dropdown - only active ones
$statuses_sql = "SELECT * FROM lead_status WHERE status = 'active' ORDER BY id";
$statuses = db_query($conn, $statuses_sql);

// Get enquiry types for filter dropdown - only active ones
$enquiry_types_sql = "SELECT * FROM enquiry_types WHERE status = 'active' ORDER BY name";
$enquiry_types = db_query($conn, $enquiry_types_sql);



// Build URL parameters for pagination
$url_params = array();
if(!empty($attended_by)) $url_params[] = "attended_by=" . urlencode($attended_by);
if(!empty($status_id)) $url_params[] = "status_id=" . urlencode($status_id);
if(!empty($search)) $url_params[] = "search=" . urlencode($search);
if(!empty($date_filter)) $url_params[] = "date_filter=" . urlencode($date_filter);
if(!empty($lead_type)) $url_params[] = "lead_type=" . urlencode($lead_type);
if(!empty($enquiry_type)) $url_params[] = "enquiry_type=" . urlencode($enquiry_type);
if(!empty($start_date)) $url_params[] = "start_date=" . urlencode($start_date);
if(!empty($end_date)) $url_params[] = "end_date=" . urlencode($end_date);
$url_string = !empty($url_params) ? "&" . implode("&", $url_params) : "";
?>

<!-- Include filter styles -->
<link rel="stylesheet" href="assets/css/filter-styles.css">

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20" style="font-size: 12px;">
        <h4 class="text-blue h4">Filters</h4>
    </div>
    <div class="pb-20 pd-20" style="font-size: 12px;">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <label>Attended By</label>
                    <select class="custom-select" id="attended-by-filter" name="attended_by">
                        <option value="">All</option>
                        <?php mysqli_data_seek($users, 0); while($user = mysqli_fetch_assoc($users)): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($attended_by == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Enquiry Type</label>
                    <select class="custom-select" id="enquiry-type-filter" name="enquiry_type">
                        <option value="">All</option>
                        <?php mysqli_data_seek($enquiry_types, 0); while($type = mysqli_fetch_assoc($enquiry_types)): ?>
                            <option value="<?php echo htmlspecialchars($type['name']); ?>" <?php echo ($enquiry_type == $type['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Enquiries Status</label>
                    <select class="custom-select" id="status-filter" name="status_id">
                        <option value="">All</option>
                        <?php mysqli_data_seek($statuses, 0); while($status = mysqli_fetch_assoc($statuses)): ?>
                            <option value="<?php echo $status['id']; ?>" <?php echo ($status_id == $status['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search-filter">Search</label>
                    <input type="text" class="form-control" id="search-filter" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enquiry #, Name, Mobile, Email, Enquiry Type, Channel">
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
                        <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end-date">End Date</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
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

<!-- Enquiries Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="text-blue h4">Enquiries (<?php echo $total_records; ?> total)</h4>
            <?php if(isAdmin()): ?>
                <a href="export_enquiries_csv.php" class="btn btn-success btn-sm">
                    <i class="fa fa-download"></i> Export to CSV
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .enquiry-table-container {
        position: relative;
        height: 600px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    
    .enquiry-table-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .enquiry-table-body {
        height: calc(100% - 70px);
        overflow-x: scroll !important;
        overflow-y: scroll !important;
        scrollbar-width: thin;
    }
    
    .enquiry-table-body::-webkit-scrollbar {
        width: 12px !important;
        height: 12px !important;
        display: block !important;
    }
    
    .enquiry-table-body::-webkit-scrollbar-track {
        background: #f1f1f1 !important;
        display: block !important;
    }
    
    .enquiry-table-body::-webkit-scrollbar-thumb {
        background: #888 !important;
        border-radius: 6px !important;
        display: block !important;
    }
    
    .enquiry-table-body::-webkit-scrollbar-thumb:hover {
        background: #555 !important;
    }
    
    .enquiry-table-body::-webkit-scrollbar-corner {
        background: #f1f1f1 !important;
    }
    
    .enquiry-table {
        width: 1800px;
        min-width: 1800px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .enquiry-table th,
    .enquiry-table td {
        padding: 12px 8px;
        text-align: left;
        border-right: 1px solid #e0e0e0;
        white-space: nowrap;
        min-width: 120px;
        font-size: 12px;
    }
    
    .enquiry-table th:nth-child(4),
    .enquiry-table td:nth-child(4) {
        min-width: 80px;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .enquiry-table th:nth-child(1),
    .enquiry-table td:nth-child(1) {
        position: sticky;
        left: 0;
        background: white;
        z-index: 2;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    
    .enquiry-table th:nth-child(1) {
        background: #f8f9fa;
    }
    
    .enquiry-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    
    .enquiry-table tbody tr {
        border-bottom: 1px solid #e0e0e0;
    }
    
    .enquiry-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .enquiry-table tbody tr:nth-child(even) {
        background-color: #fafafa;
    }
    
    .enquiry-table tbody tr:nth-child(even):hover {
        background-color: #f0f0f0;
    }
    
    .pagination-container {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
        z-index: 10;
    }
    </style>
    
    <div class="enquiry-table-container">
        <div class="enquiry-table-body">
            <table class="enquiry-table">
                <thead class="enquiry-table-header">
                    <tr>
                        <th style="min-width: 100px;">Actions</th>
                        <th>Enquiry Date</th>
                        <th>Enquiry Number</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Enquiry Type</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Recent Comments</th>
                        <th>Attended By</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr data-id="<?php echo $row['id']; ?>">
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>" title="View" style="color: #17a2b8; font-size: 16px;">
                                            <i class="dw dw-eye"></i>
                                        </a>
                                        <a href="edit_enquiry.php?id=<?php echo $row['id']; ?>" title="Edit" style="color: #007bff; font-size: 16px;">
                                            <i class="dw dw-edit2"></i>
                                        </a>
                                        <a href="#" onclick="openCommentsModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['customer_name']); ?>')" title="Comments" style="color: #28a745; font-size: 16px;">
                                            <i class="dw dw-chat"></i>
                                        </a>
                                        <?php if(isAdmin()): ?>
                                            <a href="delete_enquiry.php?id=<?php echo $row['id']; ?>" title="Delete" style="color: #dc3545; font-size: 16px;" onclick="return confirm('Are you sure you want to delete this enquiry?');">
                                                <i class="dw dw-delete-3"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($row['received_datetime'])); ?></td>
                                <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['enquiry_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['source_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <select class="custom-select status-select" data-id="<?php echo $row['id']; ?>" data-original="<?php echo $row['status_id']; ?>" style="min-width: 100px; font-size: 12px;">
                                            <?php mysqli_data_seek($statuses, 0); ?>
                                            <?php while($status = mysqli_fetch_assoc($statuses)): ?>
                                                <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $row['status_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="button" onclick="updateStatus(<?php echo $row['id']; ?>, this)" style="background: none; border: none; color: green; font-size: 16px; cursor: pointer;">✓</button>
                                    </div>
                                </td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['recent_comment'] ?? ''); ?>">
                                    <?php echo $row['recent_comment'] ? htmlspecialchars(substr($row['recent_comment'], 0, 50)) . (strlen($row['recent_comment']) > 50 ? '...' : '') : 'No comments'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['last_updated'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No enquiries found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        // Reset result pointer to add modals
        mysqli_data_seek($result, 0);
        while($row = mysqli_fetch_assoc($result)): 
        ?>
        <!-- View Modal for Enquiry ID: <?php echo $row['id']; ?> -->
        <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewModalLabel<?php echo $row['id']; ?>">Enquiry Details - <?php echo htmlspecialchars($row['customer_name']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
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
                                <p><strong>Attended By:</strong> <?php echo htmlspecialchars($row['attended_by_name']); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($row['status_name']); ?></p>
                                <p><strong>Received Date:</strong> <?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('d-m-Y H:i', strtotime($row['last_updated'])); ?></p>
                                <p><strong>Other Details:</strong> <?php echo htmlspecialchars($row['other_details'] ?? 'N/A'); ?></p>
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
        
        <!-- Static Pagination -->
        <div class="pagination-container">
            <div class="row">
                <div class="col-sm-12 col-md-5">
                    <div class="dataTables_info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                    </div>
                </div>
                <div class="col-sm-12 col-md-7">
                    <?php if($total_pages > 1): ?>
                    <div class="dataTables_paginate paging_simple_numbers">
                        <ul class="pagination justify-content-end">
                            <?php if($page > 1): ?>
                                <li class="paginate_button page-item previous">
                                    <a href="?page=<?php echo $page-1; ?><?php echo $url_string; ?>" class="page-link">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="paginate_button page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a href="?page=<?php echo $i; ?><?php echo $url_string; ?>" class="page-link"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <li class="paginate_button page-item next">
                                    <a href="?page=<?php echo $page+1; ?><?php echo $url_string; ?>" class="page-link">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Comments Modal -->
<div class="modal fade" id="commentsModal" tabindex="-1" role="dialog" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentsModalLabel">Comments</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="commentsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="comments-tab" data-toggle="tab" href="#comments" role="tab">Comments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="attachments-tab" data-toggle="tab" href="#attachments" role="tab">Attachments</a>
                    </li>
                </ul>
                
                <div class="tab-content" id="commentsTabContent">
                    <!-- Comments Tab -->
                    <div class="tab-pane fade show active" id="comments" role="tabpanel">
                        <div class="mt-3">
                            <div id="commentsContainer">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Loading comments...</p>
                                </div>
                            </div>
                            <hr>
                            <form id="addCommentForm">
                                <input type="hidden" id="enquiryId" name="enquiry_id">
                                <input type="hidden" name="type" value="enquiry">
                                <div class="form-group">
                                    <label for="commentText">Add Comment</label>
                                    <textarea class="form-control" id="commentText" name="comment" rows="3" placeholder="Enter your comment here..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Comment</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Attachments Tab -->
                    <div class="tab-pane fade" id="attachments" role="tabpanel">
                        <div class="mt-3">
                            <div id="attachmentsContainer">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Loading attachments...</p>
                                </div>
                            </div>
                            <hr>
                            <form id="addAttachmentForm" enctype="multipart/form-data">
                                <input type="hidden" id="attachmentEnquiryId" name="enquiry_id">
                                <input type="hidden" name="type" value="enquiry">
                                <div class="form-group">
                                    <label for="attachmentFile">Upload Attachment</label>
                                    <input type="file" class="form-control-file" id="attachmentFile" name="attachment" required>
                                    <small class="form-text text-muted">Max file size: 10MB. Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG</small>
                                </div>
                                <div class="form-group">
                                    <label for="attachmentDescription">Description (Optional)</label>
                                    <input type="text" class="form-control" id="attachmentDescription" name="description" placeholder="Brief description of the file">
                                </div>
                                <button type="submit" class="btn btn-success">Upload Attachment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fix sidebar menu on this page
$(document).ready(function() {
    $('.sidebar-menu a').off('click').on('click', function(e) {
        var href = $(this).attr('href');
        if (href && href !== 'javascript:;' && href !== '#') {
            window.location.href = href;
        } else {
            var $submenu = $(this).next('.submenu');
            if ($submenu.length > 0) {
                e.preventDefault();
                $('.sidebar-menu .submenu').not($submenu).slideUp();
                $submenu.slideToggle();
            }
        }
    });
});

function updateStatus(id, button) {
    console.log('updateStatus called with ID:', id);
    
    // Find the select element in the same row as the button
    var row = button.closest('tr');
    var statusSelect = row.querySelector('.status-select');
    
    if (!statusSelect) {
        console.error('Status select not found for ID:', id);
        return;
    }
    
    var selectedStatus = statusSelect.value;
    var originalStatus = statusSelect.getAttribute('data-original');
    
    console.log('Selected status:', selectedStatus);
    console.log('Original status:', originalStatus);
    
    if(selectedStatus && selectedStatus !== originalStatus) {
        // Create and submit form immediately
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_status.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        var statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status_id';
        statusInput.value = selectedStatus;
        
        form.appendChild(idInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        
        console.log('Submitting form with data:', {id: id, status_id: selectedStatus});
        form.submit();
    } else if(selectedStatus === originalStatus) {
        console.log('Status unchanged, no update needed');
    } else {
        console.error('No status selected');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Date filter functionality
    const dateFilter = document.getElementById('date-filter');
    const customDateRange = document.getElementById('custom-date-range');
    
    if (dateFilter && customDateRange) {
        dateFilter.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'flex';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    }
    
    // Initialize scrollable table
    const tableContainer = document.querySelector('.enquiry-table-container');
    if (tableContainer) {
        // Add keyboard navigation for better accessibility
        tableContainer.addEventListener('keydown', function(e) {
            const scrollAmount = 50;
            switch(e.key) {
                case 'ArrowUp':
                    if (e.ctrlKey) {
                        this.querySelector('.enquiry-table-body').scrollTop -= scrollAmount;
                        e.preventDefault();
                    }
                    break;
                case 'ArrowDown':
                    if (e.ctrlKey) {
                        this.querySelector('.enquiry-table-body').scrollTop += scrollAmount;
                        e.preventDefault();
                    }
                    break;
                case 'ArrowLeft':
                    if (e.ctrlKey) {
                        this.querySelector('.enquiry-table-body').scrollLeft -= scrollAmount;
                        e.preventDefault();
                    }
                    break;
                case 'ArrowRight':
                    if (e.ctrlKey) {
                        this.querySelector('.enquiry-table-body').scrollLeft += scrollAmount;
                        e.preventDefault();
                    }
                    break;
            }
        });
    }
});

// Comments modal functionality
function openCommentsModal(enquiryId, customerName) {
    document.getElementById('enquiryId').value = enquiryId;
    document.getElementById('attachmentEnquiryId').value = enquiryId;
    document.getElementById('commentsModalLabel').textContent = 'Comments & Attachments for ' + customerName;
    document.getElementById('commentText').value = '';
    document.getElementById('attachmentFile').value = '';
    document.getElementById('attachmentDescription').value = '';
    
    // Show modal
    $('#commentsModal').modal('show');
    
    // Load comments and attachments
    loadComments(enquiryId);
    loadAttachments(enquiryId);
}

function loadComments(enquiryId) {
    const container = document.getElementById('commentsContainer');
    container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div><p>Loading comments...</p></div>';
    
    fetch('get_comments.php?enquiry_id=' + enquiryId + '&type=enquiry')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.comments && data.comments.length > 0) {
                    data.comments.forEach(comment => {
                        html += `
                            <div class="comment-item mb-3 p-3" style="border-left: 3px solid #007bff; background-color: #f8f9fa;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong style="color: #007bff;">${comment.user_name}</strong>
                                    <small class="text-muted">${comment.created_at}</small>
                                </div>
                                <div style="white-space: pre-wrap;">${comment.comment}</div>
                            </div>
                        `;
                    });
                } else {
                    html = '<p class="text-muted text-center">No comments yet.</p>';
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger">Error loading comments: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            container.innerHTML = '<p class="text-danger">Error loading comments. Please try again.</p>';
        });
}

// Handle comment form submission
document.getElementById('addCommentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    fetch('add_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('commentText').value = '';
            loadComments(document.getElementById('enquiryId').value);
        } else {
            alert('Error adding comment: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding comment. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

// Load attachments function
function loadAttachments(enquiryId) {
    const container = document.getElementById('attachmentsContainer');
    container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div><p>Loading attachments...</p></div>';
    
    fetch('get_attachments.php?enquiry_id=' + enquiryId + '&type=enquiry')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(attachment => {
                        html += `
                            <div class="attachment-item mb-3 p-3" style="border: 1px solid #dee2e6; border-radius: 5px; background-color: #f8f9fa;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="${getFileIcon(attachment.original_name)} mr-2" style="font-size: 20px; color: #007bff;"></i>
                                        <div>
                                            <strong>${attachment.original_name}</strong>
                                            <br><small class="text-muted">${attachment.file_size} bytes</small>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-muted d-block">${attachment.created_at}</small>
                                        <a href="download_attachment.php?id=${attachment.id}" class="btn btn-sm btn-outline-primary mt-1">Download</a>
                                    </div>
                                </div>
                                ${attachment.description ? `<div class="text-muted">${attachment.description}</div>` : ''}
                            </div>
                        `;
                    });
                } else {
                    html = '<p class="text-muted text-center">No attachments yet.</p>';
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger">Error loading attachments: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            container.innerHTML = '<p class="text-danger">Error loading attachments. Please try again.</p>';
        });
}

// Get file icon based on file extension
function getFileIcon(fileName) {
    const ext = fileName.split('.').pop().toLowerCase();
    switch(ext) {
        case 'pdf': return 'fa fa-file-pdf';
        case 'doc': case 'docx': return 'fa fa-file-word';
        case 'xls': case 'xlsx': return 'fa fa-file-excel';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fa fa-file-image';
        default: return 'fa fa-file';
    }
}

// Handle attachment form submission
document.getElementById('addAttachmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    
    fetch('upload_attachment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('attachmentFile').value = '';
            document.getElementById('attachmentDescription').value = '';
            loadAttachments(document.getElementById('attachmentEnquiryId').value);
        } else {
            alert('Error uploading attachment: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error uploading attachment. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

// Custom table functionality for scrollable design
window.addEventListener('load', function() {
    // Ensure dropdown menus work properly in scrollable container
    document.querySelectorAll('.dropdown-toggle').forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    // Add smooth scrolling behavior
    const tableBody = document.querySelector('.enquiry-table-body');
    if (tableBody) {
        tableBody.style.scrollBehavior = 'smooth';
    }
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>
