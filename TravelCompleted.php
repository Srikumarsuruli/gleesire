<?php
require_once "includes/header.php";

if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Filter variables
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
} else {
    $file_manager = isset($_GET['file_manager']) ? $_GET['file_manager'] : "";
    $search = isset($_GET['search']) ? $_GET['search'] : "";
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : "";
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : "";
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : "";
}

// Build SQL query with filters
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date,
        dest.name as destination_name, fm.full_name as file_manager_name
        FROM tour_costings tc 
        JOIN enquiries e ON tc.enquiry_id = e.id 
        JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE tc.confirmed = 1 AND cl.travel_end_date < CURDATE()";

$params = array();
$types = "";

if(!empty($file_manager)) {
    $sql .= " AND cl.file_manager_id = ?";
    $params[] = $file_manager;
    $types .= "i";
}

if(!empty($search)) {
    $search_term = "%" . $search . "%";
    $sql .= " AND (tc.cost_sheet_number LIKE ? OR e.customer_name LIKE ? OR e.mobile_number LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if(!empty($date_filter)) {
    switch($date_filter) {
        case "this_month":
            $sql .= " AND MONTH(cl.travel_end_date) = MONTH(NOW()) AND YEAR(cl.travel_end_date) = YEAR(NOW())";
            break;
        case "last_month":
            $sql .= " AND MONTH(cl.travel_end_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(cl.travel_end_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))";
            break;
        case "this_year":
            $sql .= " AND YEAR(cl.travel_end_date) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $sql .= " AND cl.travel_end_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            break;
    }
}

$sql .= " ORDER BY cl.travel_end_date DESC";

$stmt = mysqli_prepare($conn, $sql);
if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get file managers for filter dropdown
$managers_sql = "SELECT * FROM users WHERE role_id IN (11, 12) ORDER BY full_name";
$managers = mysqli_query($conn, $managers_sql);
?>

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Travel Completed Filters</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>File Manager</label>
                        <select class="custom-select" name="file_manager">
                            <option value="">All</option>
                            <?php while($manager = mysqli_fetch_assoc($managers)): ?>
                                <option value="<?php echo $manager['id']; ?>" <?php echo ($file_manager == $manager['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($manager['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cost Sheet, Name, Mobile">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Filter</label>
                        <select class="custom-select" name="date_filter" onchange="toggleCustomDates(this.value)">
                            <option value="">All Time</option>
                            <option value="this_month" <?php echo ($date_filter == "this_month") ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_month" <?php echo ($date_filter == "last_month") ? 'selected' : ''; ?>>Last Month</option>
                            <option value="this_year" <?php echo ($date_filter == "this_year") ? 'selected' : ''; ?>>This Year</option>
                            <option value="custom" <?php echo ($date_filter == "custom") ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" name="filter" class="btn btn-primary">Filter</button>
                            <a href="TravelCompleted.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="custom-date-range" style="display: <?php echo ($date_filter == 'custom') ? 'block' : 'none'; ?>;">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Travel Completed</h4>
    </div>
    <div class="pb-20">
        <div class="table-responsive">
            <table class="data-table table stripe hover">
                <thead>
                    <tr>
                        <th>Cost Sheet No</th>
                        <th>Customer Name</th>
                        <th>Mobile</th>
                        <th>Destination</th>
                        <th>Travel Period</th>
                        <th>Package Cost</th>
                        <th>File Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['cost_sheet_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['destination_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $start = $row['travel_start_date'] ? date('d-m-Y', strtotime($row['travel_start_date'])) : 'N/A';
                                $end = $row['travel_end_date'] ? date('d-m-Y', strtotime($row['travel_end_date'])) : 'N/A';
                                echo $start . ' to ' . $end;
                                ?>
                            </td>
                            <td><?php echo $row['currency'] . ' ' . number_format($row['package_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="generate_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fa fa-file-pdf-o"></i> Invoice
                                </a>
                                <a href="download_receipt.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fa fa-download"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No completed travels found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script>
    $('.data-table').DataTable({
        scrollCollapse: true,
        autoWidth: false,
        responsive: true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "info": "_START_-_END_ of _TOTAL_ entries",
            searchPlaceholder: "Search",
            paginate: {
                next: '<i class="ion-chevron-right"></i>',
                previous: '<i class="ion-chevron-left"></i>'
            }
        },
    });
    
    function toggleCustomDates(value) {
        const customRange = document.getElementById('custom-date-range');
        customRange.style.display = value === 'custom' ? 'block' : 'none';
    }
</script>

<?php require_once "includes/footer.php"; ?>