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
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        fm.full_name as file_manager_name, dest.name as destination_name
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN lead_status ls ON e.status_id = ls.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        WHERE 1=1";

$params = array();
$types = "";

// Filter by logged-in user if not admin
if(!isAdmin()) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    // Debug: Check current user ID (remove this after testing)
    // echo '<div>Current User ID: ' . $current_user_id . '</div>';
    if($current_user_id) {
        $base_sql .= " AND e.attended_by = ?";
        $params[] = $current_user_id;
        $types .= "i";
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
    $base_sql .= " AND (e.lead_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
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
$count_sql = "SELECT COUNT(*) FROM enquiries e WHERE 1=1";

// Add the same WHERE conditions as the main query
// Filter by logged-in user if not admin
if(!isAdmin()) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if($current_user_id) {
        $count_sql .= " AND e.attended_by = ?";
    }
}

if(!empty($attended_by)) {
    $count_sql .= " AND e.attended_by = ?";
}
if(!empty($status_id)) {
    $count_sql .= " AND e.status_id = ?";
}
if(!empty($search)) {
    $count_sql .= " AND (e.lead_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ?)";
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

// Add order by for main query - most recent enquiries first (latest date at top)
$sql = $base_sql . " ORDER BY DATE(e.received_datetime) DESC, TIME(e.received_datetime) DESC, e.id DESC";

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
$users = mysqli_query($conn, $users_sql);

// Get lead statuses for filter dropdown - only active ones
$statuses_sql = "SELECT * FROM lead_status WHERE status = 'active' ORDER BY id";
$statuses = mysqli_query($conn, $statuses_sql);

// Get enquiry types for filter dropdown - only active ones
$enquiry_types_sql = "SELECT * FROM enquiry_types WHERE status = 'active' ORDER BY name";
$enquiry_types = mysqli_query($conn, $enquiry_types_sql);



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
                        <?php mysqli_data_seek($users, 0); while($user = mysqli_fetch_assoc($users)): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($attended_by == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
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
        <h4 class="text-blue h4">Enquiries (<?php echo $total_records; ?> total)</h4>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <style>
        .dataTables_paginate {
            overflow-x: visible !important;
        }
        .dataTables_wrapper .row {
            overflow-x: visible !important;
        }
        </style>
        <table class="data-table table stripe hover nowrap" style="min-width: 1200px;">
            <thead>
                <tr>
                    <th>Enquiry Date</th>
                    <th>Enquiry Number</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>File Manager</th>
                    <th>Destinations</th>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Attended By</th>
                    <th>Last Updated</th>
                    <th class="datatable-nosort">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr data-id="<?php echo $row['id']; ?>">
                            <td><?php echo date('d-m-Y', strtotime($row['received_datetime'])); ?></td>
                            <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($row['destination_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <select class="custom-select status-select" data-id="<?php echo $row['id']; ?>" data-original="<?php echo $row['status_id']; ?>" style="min-width: 120px;">
                                        <?php mysqli_data_seek($statuses, 0); ?>
                                        <?php while($status = mysqli_fetch_assoc($statuses)): ?>
                                            <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $row['status_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="button" onclick="updateStatus(<?php echo $row['id']; ?>, this)" style="background: none; border: none; color: green; font-size: 18px; cursor: pointer;">✓</button>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['last_updated'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                        <i class="dw dw-more"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                        <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                        <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=enquiry"><i class="dw dw-chat"></i> Comments</a>
                                        <?php if(isAdmin()): ?>
                                            <a class="dropdown-item" href="delete_enquiry.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this enquiry?');"><i class="dw dw-delete-3"></i> Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">No enquiries found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="row mt-3">
            <div class="col-sm-12 col-md-5">
                <!-- <div class="dataTables_info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                </div> -->
            </div>
            <div class="col-sm-12 col-md-7">
                <!-- <div class="dataTables_paginate paging_simple_numbers">
                    <ul class="pagination">
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
                </div> -->
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>



<script>
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
});

// Initialize DataTable with custom options
window.addEventListener('load', function() {
    if (typeof $.fn.DataTable !== 'undefined') {
        // Destroy any existing DataTable instance
        if ($.fn.DataTable.isDataTable('.data-table')) {
            $('.data-table').DataTable().destroy();
        }
        
        // Initialize with custom options
        $('.data-table').DataTable({
            autoWidth: false,
            responsive: false,
            searching: false,  // Disable built-in search as we have custom filter
            ordering: true,
            stateSave: false,
            paging: true,
            info: true,
            order: [[0, 'desc']],  // Sort by Enquiry Date descending (most recent first)
            columnDefs: [{
                targets: "datatable-nosort",
                orderable: false,
            }, {
                targets: 0,  // Enquiry Date column
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
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>