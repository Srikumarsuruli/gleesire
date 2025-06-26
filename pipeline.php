<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$file_manager = $search = $date_filter = "";
$start_date = $end_date = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $file_manager = !empty($_POST["file_manager"]) ? $_POST["file_manager"] : "";
    $search = !empty($_POST["search"]) ? $_POST["search"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
}

// Build the SQL query with filters
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.booking_confirmed,
        lsm.status_name as lead_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        JOIN lead_status ls ON e.status_id = ls.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE e.status_id = 3 AND lsm.status_name IN (
            'Hot Prospect - Quote given',
            'Prospect - Attended',
            'Prospect - Awaiting Rate from Agent',
            'Neutral Prospect - In Discussion',
            'Future Hot Prospect - Quote Given (with delay)',
            'Future Prospect - Postponed'
        )";

$params = array();
$types = "";

if(!empty($file_manager)) {
    $sql .= " AND cl.file_manager_id = ?";
    $params[] = $file_manager;
    $types .= "i";
}

if(!empty($search)) {
    $search_term = "%" . $search . "%";
    $sql .= " AND (e.lead_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ? OR e.email LIKE ? OR cl.enquiry_number LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sssss";
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

// Add order by clause
$sql .= " ORDER BY e.received_datetime DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get file managers for filter dropdown
$file_managers_sql = "SELECT * FROM users ORDER BY full_name";
$file_managers = mysqli_query($conn, $file_managers_sql);
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
                    <label for="search-filter">Search</label>
                    <input type="text" class="form-control" id="search-filter" name="search" value="<?php echo $search; ?>" placeholder="Lead #, Name, Mobile, Email">
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

<!-- Pipeline Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Pipeline</h4>
    </div>
    <div class="pb-20">
        <table class="data-table table stripe hover nowrap">
            <thead>
                <tr>
                    <th>Lead #</th>
                    <th>Enquiry #</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Source</th>
                    <th>Campaign</th>
                    <th>Lead Status</th>
                    <th>Received Date</th>
                    <th>Attended By</th>
                    <th>Department</th>
                    <th class="datatable-nosort">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr data-id="<?php echo $row['id']; ?>">
                    <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['enquiry_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <form method="post" action="save_status.php" style="display: flex; align-items: center;">
                                <input type="hidden" name="enquiry_id" value="<?php echo $row['id']; ?>">
                                <select class="custom-select" name="status" style="min-width: 200px;">
                                    <option value="">Select Status</option>
                                    <option value="Hot Prospect - Quote given" <?php echo ($row['lead_status'] == "Hot Prospect - Quote given") ? 'selected' : ''; ?>>Hot Prospect - Quote given</option>
                                    <option value="Prospect - Attended" <?php echo ($row['lead_status'] == "Prospect - Attended") ? 'selected' : ''; ?>>Prospect - Attended</option>
                                    <option value="Prospect - Awaiting Rate from Agent" <?php echo ($row['lead_status'] == "Prospect - Awaiting Rate from Agent") ? 'selected' : ''; ?>>Prospect - Awaiting Rate from Agent</option>
                                    <option value="Neutral Prospect - In Discussion" <?php echo ($row['lead_status'] == "Neutral Prospect - In Discussion") ? 'selected' : ''; ?>>Neutral Prospect - In Discussion</option>
                                    <option value="Future Hot Prospect - Quote Given (with delay)" <?php echo ($row['lead_status'] == "Future Hot Prospect - Quote Given (with delay)") ? 'selected' : ''; ?>>Future Hot Prospect - Quote Given (with delay)</option>
                                    <option value="Future Prospect - Postponed" <?php echo ($row['lead_status'] == "Future Prospect - Postponed") ? 'selected' : ''; ?>>Future Prospect - Postponed</option>
                                </select>
                                <button type="submit" class="btn btn-link p-0 ml-2">
                                    <i class="icon-copy fa fa-check text-success" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                    <td><?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></td>
                    <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                    <td>
                        <div class="dropdown">
                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                <i class="dw dw-more"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                <a class="dropdown-item" href="#" onclick="moveToConfirmed(<?php echo $row['id']; ?>); return false;"><i class="dw dw-check"></i> Move to Confirmed</a>
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
                window.location.href = 'pipeline.php?confirmed=1';
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    }
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>