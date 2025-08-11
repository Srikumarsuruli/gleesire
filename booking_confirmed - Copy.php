<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('booking_confirmed')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$file_manager = $department = $source = $ad_campaign = $date_filter = "";
$start_date = $end_date = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $file_manager = !empty($_POST["file_manager"]) ? $_POST["file_manager"] : "";
    $department = !empty($_POST["department"]) ? $_POST["department"] : "";
    $source = !empty($_POST["source"]) ? $_POST["source"] : "";
    $ad_campaign = !empty($_POST["ad_campaign"]) ? $_POST["ad_campaign"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
}

// Build the SQL query with filters
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name,
        s.name as source_name, ac.name as campaign_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.created_at as booking_date,
        cl.file_manager_id, fm.full_name as file_manager_name
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id
        JOIN sources s ON e.source_id = s.id
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id
        JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE cl.booking_confirmed = 1"; // Only confirmed bookings

$params = array();
$types = "";

if(!empty($file_manager)) {
    $sql .= " AND cl.file_manager_id = ?";
    $params[] = $file_manager;
    $types .= "i";
}

if(!empty($department)) {
    $sql .= " AND e.department_id = ?";
    $params[] = $department;
    $types .= "i";
}

if(!empty($source)) {
    $sql .= " AND e.source_id = ?";
    $params[] = $source;
    $types .= "i";
}

if(!empty($ad_campaign)) {
    $sql .= " AND e.ad_campaign_id = ?";
    $params[] = $ad_campaign;
    $types .= "i";
}

if(!empty($date_filter)) {
    switch($date_filter) {
        case "today":
            $sql .= " AND DATE(cl.created_at) = CURDATE()";
            break;
        case "yesterday":
            $sql .= " AND DATE(cl.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case "this_week":
            $sql .= " AND YEARWEEK(cl.created_at) = YEARWEEK(NOW())";
            break;
        case "this_month":
            $sql .= " AND MONTH(cl.created_at) = MONTH(NOW()) AND YEAR(cl.created_at) = YEAR(NOW())";
            break;
        case "this_year":
            $sql .= " AND YEAR(cl.created_at) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $sql .= " AND DATE(cl.created_at) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            break;
    }
}

// Add order by clause
$sql .= " ORDER BY cl.created_at DESC";

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

// Get departments for filter dropdown
$departments_sql = "SELECT * FROM departments ORDER BY name";
$departments = mysqli_query($conn, $departments_sql);

// Get sources for filter dropdown
$sources_sql = "SELECT * FROM sources ORDER BY name";
$sources = mysqli_query($conn, $sources_sql);

// Get ad campaigns for filter dropdown
$ad_campaigns_sql = "SELECT * FROM ad_campaigns WHERE status = 'active' ORDER BY name";
$ad_campaigns = mysqli_query($conn, $ad_campaigns_sql);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Booking Confirmed</h2>
        
        <!-- Include filter styles -->
        <link rel="stylesheet" href="assets/css/filter-styles.css">
        
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
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
                            <label>Department</label>
                            <select class="custom-select" id="department-filter" name="department">
                                <option value="">All</option>
                                <?php mysqli_data_seek($departments, 0); while($dept = mysqli_fetch_assoc($departments)): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($department == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Channel</label>
                            <select class="custom-select" id="source-filter" name="source">
                                <option value="">All</option>
                                <?php mysqli_data_seek($sources, 0); while($src = mysqli_fetch_assoc($sources)): ?>
                                    <option value="<?php echo $src['id']; ?>" <?php echo ($source == $src['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($src['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ad Campaign</label>
                            <select class="custom-select" id="ad-campaign-filter" name="ad_campaign">
                                <option value="">All</option>
                                <?php mysqli_data_seek($ad_campaigns, 0); while($campaign = mysqli_fetch_assoc($ad_campaigns)): ?>
                                    <option value="<?php echo $campaign['id']; ?>" <?php echo ($ad_campaign == $campaign['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campaign['name']); ?>
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
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Confirmed Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking Date</th>
                                <th>File Number</th>
                                <th>Customer Name</th>
                                <th>Mobile Number</th>
                                <th>Trip Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($row['booking_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['enquiry_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                                        <td><?php echo $row['travel_start_date'] ? date('d-m-Y', strtotime($row['travel_start_date'])) : '-'; ?></td>
                                        <td><?php echo $row['travel_end_date'] ? date('d-m-Y', strtotime($row['travel_end_date'])) : '-'; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                    <i class="dw dw-more"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                    <a class="dropdown-item view-booking" href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">
                                                        <i class="dw dw-eye"></i> View
                                                    </a>
                                                    <?php 
                                                    // Check if user is Accounts Manager (assuming role_id 3 is Accounts Manager)
                                                    $user_role_sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
                                                    $user_role_stmt = mysqli_prepare($conn, $user_role_sql);
                                                    mysqli_stmt_bind_param($user_role_stmt, "i", $_SESSION['user_id']);
                                                    mysqli_stmt_execute($user_role_stmt);
                                                    $user_role_result = mysqli_stmt_get_result($user_role_stmt);
                                                    $user_role = mysqli_fetch_assoc($user_role_result);
                                                    if($user_role && $user_role['name'] == 'Accounts Manager'): 
                                                    ?>
                                                    <a class="dropdown-item" href="download_booking.php?id=<?php echo $row['id']; ?>">
                                                        <i class="dw dw-download"></i> Download PDF
                                                    </a>
                                                    <?php endif; ?>
                                                    <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=booking">
                                                        <i class="dw dw-chat"></i> Comments
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="viewModalLabel<?php echo $row['id']; ?>">Booking Details</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php
                                                            // Get booking details
                                                            $booking_sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
                                                                        s.name as source_name, ac.name as campaign_name,
                                                                        cl.*, dest.name as destination_name, fm.full_name as file_manager_name 
                                                                        FROM enquiries e 
                                                                        JOIN users u ON e.attended_by = u.id 
                                                                        JOIN departments d ON e.department_id = d.id 
                                                                        JOIN sources s ON e.source_id = s.id 
                                                                        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
                                                                        JOIN converted_leads cl ON e.id = cl.enquiry_id
                                                                        LEFT JOIN destinations dest ON cl.destination_id = dest.id
                                                                        LEFT JOIN users fm ON cl.file_manager_id = fm.id
                                                                        WHERE e.id = ?";
                                                            $booking_stmt = mysqli_prepare($conn, $booking_sql);
                                                            mysqli_stmt_bind_param($booking_stmt, "i", $row['id']);
                                                            mysqli_stmt_execute($booking_stmt);
                                                            $booking_result = mysqli_stmt_get_result($booking_stmt);
                                                            $booking = mysqli_fetch_assoc($booking_result);
                                                            ?>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p><strong>File Number:</strong> <?php echo htmlspecialchars($booking['enquiry_number']); ?></p>
                                                                    <p><strong>Lead Number:</strong> <?php echo htmlspecialchars($booking['lead_number']); ?></p>
                                                                    <p><strong>Booking Date:</strong> <?php echo date('d-m-Y', strtotime($booking['created_at'])); ?></p>
                                                                    <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                                                    <p><strong>Mobile Number:</strong> <?php echo htmlspecialchars($booking['mobile_number']); ?></p>
                                                                    <p><strong>Email:</strong> <?php echo $booking['email'] ? htmlspecialchars($booking['email']) : '-'; ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Customer Location:</strong> <?php echo $booking['customer_location'] ? htmlspecialchars($booking['customer_location']) : '-'; ?></p>
                                                                    <p><strong>Secondary Contact:</strong> <?php echo $booking['secondary_contact'] ? htmlspecialchars($booking['secondary_contact']) : '-'; ?></p>
                                                                    <p><strong>Destination:</strong> <?php echo $booking['destination_name'] ? htmlspecialchars($booking['destination_name']) : '-'; ?></p>
                                                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($booking['department_name']); ?></p>
                                                                    <p><strong>Source:</strong> <?php echo htmlspecialchars($booking['source_name']); ?></p>
                                                                    <p><strong>Ad Campaign:</strong> <?php echo $booking['campaign_name'] ? htmlspecialchars($booking['campaign_name']) : '-'; ?></p>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p><strong>Travel Month:</strong> <?php echo $booking['travel_month'] ? date('F Y', strtotime($booking['travel_month'])) : '-'; ?></p>
                                                                    <p><strong>Travel Period:</strong> 
                                                                        <?php 
                                                                        if($booking['travel_start_date'] && $booking['travel_end_date']) {
                                                                            echo date('d-m-Y', strtotime($booking['travel_start_date'])) . ' to ' . date('d-m-Y', strtotime($booking['travel_end_date']));
                                                                        } else {
                                                                            echo '-';
                                                                        }
                                                                        ?>
                                                                    </p>
                                                                    <p><strong>Travelers:</strong> 
                                                                        <?php 
                                                                        $travelers = array();
                                                                        if($booking['adults_count'] > 0) $travelers[] = $booking['adults_count'] . ' Adults';
                                                                        if($booking['children_count'] > 0) $travelers[] = $booking['children_count'] . ' Children';
                                                                        if($booking['infants_count'] > 0) $travelers[] = $booking['infants_count'] . ' Infants';
                                                                        echo !empty($travelers) ? implode(', ', $travelers) : '-';
                                                                        ?>
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Customer Available Timing:</strong> <?php echo $booking['customer_available_timing'] ? htmlspecialchars($booking['customer_available_timing']) : '-'; ?></p>
                                                                    <p><strong>File Manager:</strong> <?php echo $booking['file_manager_name'] ? htmlspecialchars($booking['file_manager_name']) : '-'; ?></p>
                                                                    <p><strong>Other Details:</strong> <?php echo $booking['other_details'] ? htmlspecialchars($booking['other_details']) : '-'; ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            <?php if($user_role && $user_role['name'] == 'Accounts Manager'): ?>
                                                            <a href="download_booking.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                                                <i class="dw dw-download"></i> Download PDF
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No confirmed bookings found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show/hide custom date range based on date filter selection
    document.addEventListener('DOMContentLoaded', function() {
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
    
    // Function to reset filters
    function resetFilters() {
        document.getElementById('file-manager-filter').selectedIndex = 0;
        document.getElementById('department-filter').selectedIndex = 0;
        document.getElementById('source-filter').selectedIndex = 0;
        document.getElementById('ad-campaign-filter').selectedIndex = 0;
        document.getElementById('date-filter').selectedIndex = 0;
        document.getElementById('custom-date-range').style.display = 'none';
        document.getElementById('start-date').value = '';
        document.getElementById('end-date').value = '';
        
        // Submit the form
        document.getElementById('filter-form').submit();
    }
    
    // Initialize modals and dropdowns
    $(document).ready(function() {
        // Fix dropdown toggle functionality
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).next('.dropdown-menu').toggle();
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown-menu').hide();
            }
        });
        
        // Make sure modals work properly
        $('.view-booking').on('click', function(e) {
            e.preventDefault();
            var targetModal = $(this).data('target');
            $(targetModal).modal('show');
        });
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>