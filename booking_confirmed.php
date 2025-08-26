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
$sql = "SELECT e.*,  
        e.created_at as booking_date,
        tc.booking_number as booking_number,
        cl.created_at as lead_date,
        cl.enquiry_number as lead_number,
        cl.file_manager_id, 
        fm.full_name as file_manager_name,
        s.name as source_name, 
        cl.travel_start_date as checkin_date, 
        cl.travel_end_date as checkout_date, 
        e.customer_name as customer_name, 
        e.mobile_number as customer_contact_number, 
        cl.adults_count, 
        cl.children_count, 
        cl.infants_count,
        ds.name as destination_name, 
        
        tc.visa_data as visa_data,
        tc.arrival_flight as arrival_flight_no, 
        tc.arrival_date as arrival_flight_date, 
        tc.arrival_city as arrival_flight_clity, 
        
        tc.departure_flight as departure_flight_no, 
        tc.departure_date as departure_flight_date, 
        tc.departure_city as departure_flight_clity, 

        tc.currency as currency, 
        tc.package_cost as package_cost, 
        
        SUM(ps.payment_amount) as payment_received,

        tc.transportation_data as transportation_data,
        
        u.full_name as attended_by_name, 
        d.name as department_name,
        
        ac.name as campaign_name,
        cl.enquiry_number, 
      
        
        tc.id as cost_sheet_id, 
        tc.cost_sheet_number as cost_sheet_number,
        
        'Closed – Booked' as lead_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id
        JOIN sources s ON e.source_id = s.id
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id
        JOIN tour_costings tc ON e.id = tc.enquiry_id
        JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        LEFT JOIN destinations ds ON cl.destination_id = ds.id
        LEFT JOIN payments ps ON tc.id = ps.cost_file_id
        WHERE tc.confirmed = '1'";

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

// Get total count of records
$count_sql = "SELECT COUNT(*) FROM enquiries e 
        JOIN tour_costings tc ON e.id = tc.enquiry_id
        WHERE tc.confirmed = '1'";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_array($count_result);
$total_records = $count_row ? $count_row[0] : 0;

// Add order by clause
$sql .= "GROUP BY 
        e.id, e.created_at, tc.booking_number,
        cl.created_at, cl.enquiry_number, cl.file_manager_id,
        fm.full_name, s.name, cl.travel_start_date, cl.travel_end_date,
        e.customer_name, e.mobile_number, cl.adults_count, cl.children_count, cl.infants_count,
        ds.name, tc.visa_data, tc.arrival_flight, tc.arrival_date, tc.arrival_city,
        tc.departure_flight, tc.departure_date, tc.departure_city,
        tc.currency, tc.package_cost,
        u.full_name, d.name, ac.name,
        tc.id, tc.cost_sheet_number";

$sql .= " ORDER BY cl.created_at DESC";


// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get file managers for filter dropdown - only those with confirmed bookings
$file_managers_sql = "SELECT DISTINCT u.* FROM users u 
                      JOIN converted_leads cl ON u.id = cl.file_manager_id 
                      JOIN lead_status_map lsm ON cl.enquiry_id = lsm.enquiry_id 
                      WHERE lsm.status_name = 'Closed – Booked' 
                      ORDER BY u.full_name";
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

<!-- Check for confirmation message -->
<?php
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

<div class="row">
    <div class="col-md-12">
        
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
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings Table -->
        <div class="card-box mb-30">
            <div class="pd-20">
                <h4 class="text-blue h4">Confirmed Bookings (<?php echo $total_records; ?> total)</h4>
            </div>
            <div class="pb-20">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="data-table table stripe hover nowrap" style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">Booking Date</th>
                            <th style="min-width: 100px;">Booking Number</th>
                            <th style="min-width: 80px;">Lead Date</th>
                            <th style="min-width: 80px;">Lead Number</th>
                            <th style="min-width: 100px;">File Manager</th>
                            <th style="min-width: 80px;">Source</th>
                            <th style="min-width: 80px;">Check In</th>
                            <th style="min-width: 120px;">Check Out</th>
                            <th style="min-width: 100px;">Guest Name</th>
                            <th style="min-width: 100px;">No. of PAX</th>
                            <th style="min-width: 100px;">Destinations</th>
                            <th style="min-width: 100px;">Suppliers</th>
                            <th style="min-width: 100px;">Arr FLT Details</th>
                            <th style="min-width: 100px;">Dep FLT Details</th>
                            <th style="min-width: 100px;">Contact No.</th>
                            <th style="min-width: 100px;">Total Amount</th>
                            <th style="min-width: 100px;">Payment Received</th>
                            <th style="min-width: 100px;">Payment Status</th>
                            <th style="min-width: 100px;">Vechicle Details</th>

                            <th class="datatable-nosort" style="min-width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr data-id="<?php echo $row['id']; ?>" class="<?php echo (isset($_GET['highlight']) && $_GET['highlight'] == $row['id']) ? 'highlight-row' : ''; ?>">
                                    <td><?php echo date('d-m-Y', strtotime($row['booking_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['booking_number']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['lead_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                                    <td><?php echo $row['checkin_date'] ? date('d-m-Y', strtotime($row['checkin_date'])) : '-'; ?></td>
                                    <td><?php echo $row['checkout_date'] ? date('d-m-Y', strtotime($row['checkout_date'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo intval($row['adults_count'] ?? 0) + intval($row['children_count'] ?? 0) + intval($row['infants_count'] ?? 0) ; ?></td>
                                    <td><?php echo htmlspecialchars($row['destination_name']); ?></td>
                                    <td>
                                        <?php
                                            $visa_data = json_decode($row['visa_data'], true);
                                            $suppliers = array();
                                
                                            if(is_array($visa_data)) {
                                                foreach($visa_data as $item) {
                                                    if(isset($item['sector']) && $item['sector'] == 'flight' && isset($item['supplier'])) {
                                                        $suppliers[] = $item['supplier'];
                                                    }
                                                }
                                            }
                                        
                                            echo implode(', ', array_unique($suppliers));
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['arrival_flight_no']); ?>
                                        &nbsp;|&nbsp;
                                        <?php echo date('d-m-Y H:i', strtotime($row['arrival_flight_date'])); ?>
                                        <br />
                                        <?php echo htmlspecialchars($row['arrival_flight_clity']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['departure_flight_no']); ?>
                                        &nbsp;|&nbsp;
                                        <?php echo date('d-m-Y H:i', strtotime($row['departure_flight_date'])); ?>
                                        <br />
                                        <?php echo htmlspecialchars($row['departure_flight_clity']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['customer_contact_number'] ?? 'N/A'); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($row['currency']) . ' ' . number_format($row['package_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['currency']) . ' ' . number_format($row['payment_received'], 2); ?></td>
                                    <td>
                                        <?php if($row['payment_received'] >= $row['package_cost']): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php
                                            $visa_data = json_decode($row['transportation_data'], true);
                                            $suppliers = array();
                                
                                            if(is_array($visa_data)) {
                                                foreach($visa_data as $item) {
                                                    
                                                    $suppliers[] = 'Supplier: ' . $item['supplier'] . ' <br> Phone: ' . (isset($item['phone']) ? $item['phone'] : '-');                                                }
                                            }
                                        
                                            echo implode(', ', array_unique($suppliers));
                                        ?>
                                    </td>
                                    
                                    
                                    <td>
                                        <div class="dropdown">
                                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                <i class="dw dw-more"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                <!-- <a class="dropdown-item view-booking" href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">
                                                    <i class="dw dw-eye"></i> View
                                                </a> -->
                                                <a class="dropdown-item" href="view_cost_file.php?id=<?php echo $row['cost_sheet_id']; ?>">
                                                    <i class="dw dw-eye"></i> View Cost Sheet
                                                </a>
                                                <!-- <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>">
                                                    <i class="dw dw-edit2"></i> Edit
                                                </a> -->
                                                <!-- <a class="dropdown-item" href="download_booking.php?id=<?php echo $row['id']; ?>">
                                                    <i class="dw dw-download"></i> Download PDF
                                                </a> -->
                                                <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=booking">
                                                    <i class="dw dw-chat"></i> Comments
                                                </a>
                                                <!-- <?php if(hasPrivilege('new_cost_file')): ?>
                                                <a class="dropdown-item" href="new_cost_file.php?id=<?php echo $row['id']; ?>">
                                                    <i class="dw dw-file"></i> Cost File
                                                </a>
                                                <?php endif; ?> -->
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
                                                                        cl.*, dest.name as destination_name, fm.full_name as file_manager_name,
                                                                        lsm.status_name as lead_status
                                                                        FROM enquiries e 
                                                                        JOIN users u ON e.attended_by = u.id 
                                                                        JOIN departments d ON e.department_id = d.id 
                                                                        JOIN sources s ON e.source_id = s.id 
                                                                        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
                                                                        JOIN converted_leads cl ON e.id = cl.enquiry_id
                                                                        LEFT JOIN destinations dest ON cl.destination_id = dest.id
                                                                        LEFT JOIN users fm ON cl.file_manager_id = fm.id
                                                                        JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
                                                                        WHERE e.id = ? AND lsm.status_name = 'Closed – Booked'";
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
                                                                    <p><strong>Lead Status:</strong> <?php echo $booking['lead_status'] ? htmlspecialchars($booking['lead_status']) : '-'; ?></p>
                                                                    <p><strong>Other Details:</strong> <?php echo $booking['other_details'] ? htmlspecialchars($booking['other_details']) : '-'; ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            <a href="download_booking.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                                                <i class="dw dw-download"></i> Download PDF
                                                            </a>
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
</div>

<script>
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
                    columnDefs: [{
                        targets: "datatable-nosort",
                        orderable: false,
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
    
    // Show/hide custom date range based on date filter selection
    document.getElementById('date-filter').addEventListener('change', function() {
        var customDateRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
        }
    });
    
    // Initialize modals and dropdowns
    $(document).ready(function() {
        // Fix dropdown toggle functionality
        // $('.dropdown-toggle').dropdown();
        
        // Make sure modals work properly
        $('.view-booking').on('click', function(e) {
        e.preventDefault();
        var targetModal = $(this).data('target');
        $(targetModal).modal('show');
        });
        
        // Set enquiry ID in comment modal and load comments
        $('.comment-link').on('click', function(e) {
            var enquiryId = $(this).data('id');
            var customerName = $(this).data('customer');
            
            // Set the modal title with customer name
            $('#add-comment-modal-title').text('Comments for ' + customerName);
            
            // Set form values
            $('#enquiry-id').val(enquiryId);
            $('#table-id').val('enquiries');
            
            // Load existing comments
            loadComments(enquiryId);
        });
        
        // Function to load comments
        function loadComments(enquiryId) {
            $('#comments-container').html('<p>Loading comments...</p>');
            
            $.ajax({
                url: 'get_comments.php',
                type: 'GET',
                data: {
                enquiry_id: enquiryId
                },
                success: function(response) {
                try {
                    var data = JSON.parse(response);
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
                    $('#comments-container').html('<p class="text-danger">Error processing response</p>');
                }
                },
                error: function() {
                $('#comments-container').html('<p class="text-danger">Error loading comments</p>');
                }
            });
        }
        
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

<?php
// Include footer
require_once "includes/footer.php";
?>