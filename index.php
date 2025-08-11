<?php
// Include header
require_once "includes/header.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get counts for dashboard
$total_enquiries = $total_leads = $total_confirmed = $total_pipeline = 0;

// Total enquiries
$sql = "SELECT COUNT(*) as count FROM enquiries";
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_enquiries = $row['count'];
}

// Total leads
$sql = "SELECT COUNT(*) as count FROM enquiries WHERE status_id = 3";
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_leads = $row['count'];
}

// Total confirmed bookings
$sql = "SELECT COUNT(*) as count FROM converted_leads WHERE booking_confirmed = 1";
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_confirmed = $row['count'];
}

// Total canceled bookings count
$sql = "SELECT COUNT(*) as count FROM converted_leads WHERE booking_confirmed = 0";
$result = mysqli_query($conn, $sql);
$total_canceled = 0;
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_canceled = $row['count'];
}

// Profit value (set to 0 for now)
$profit_value = 0;

// Canceled booking amount (set to 0 for now)
$canceled_booking_amount = 0;

// Total travel completed (using travel end date in the past)
$sql = "SELECT COUNT(*) as count FROM converted_leads WHERE travel_end_date IS NOT NULL AND travel_end_date < CURDATE()";
$result = mysqli_query($conn, $sql);
$total_travel_completed = 0;
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_travel_completed = $row['count'];
}

// Total pipeline leads
$sql = "SELECT COUNT(*) as count FROM enquiries e 
        JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE e.status_id = 3 AND lsm.status_name IN (
            'Hot Prospect - Quote given',
            'Prospect - Attended',
            'Prospect - Awaiting Rate from Agent',
            'Neutral Prospect - In Discussion',
            'Future Hot Prospect - Quote Given (with delay)',
            'Future Prospect - Postponed'
        )";
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_pipeline = $row['count'];
}

// Get monthly data for the last 6 months
$monthly_data = [];
$months = [];
$current_month = date('m');
$current_year = date('Y');

for ($i = 0; $i < 6; $i++) {
    $month = $current_month - $i;
    $year = $current_year;
    
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    $month_name = date('M', mktime(0, 0, 0, $month, 1, $year));
    $months[] = $month_name;
    
    // Enquiries for this month
    $sql = "SELECT COUNT(*) as count FROM enquiries WHERE MONTH(received_datetime) = $month AND YEAR(received_datetime) = $year";
    $result = mysqli_query($conn, $sql);
    $enquiries = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $enquiries = $row['count'];
    }
    
    // Leads for this month
    $sql = "SELECT COUNT(*) as count FROM enquiries WHERE status_id = 3 AND MONTH(received_datetime) = $month AND YEAR(received_datetime) = $year";
    $result = mysqli_query($conn, $sql);
    $leads = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $leads = $row['count'];
    }
    
    // Confirmed bookings for this month
    $sql = "SELECT COUNT(*) as count FROM converted_leads cl 
            JOIN enquiries e ON cl.enquiry_id = e.id 
            WHERE cl.booking_confirmed = 1 AND MONTH(e.received_datetime) = $month AND YEAR(e.received_datetime) = $year";
    $result = mysqli_query($conn, $sql);
    $confirmed = 0;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $confirmed = $row['count'];
    }
    
    $monthly_data[] = [
        'month' => $month_name,
        'enquiries' => $enquiries,
        'leads' => $leads,
        'confirmed' => $confirmed
    ];
}

// Reverse the arrays to show oldest to newest
$monthly_data = array_reverse($monthly_data);
$months = array_reverse($months);

// Recent enquiries
$recent_enquiries_sql = "SELECT e.*, u.full_name as attended_by_name, s.name as source_name, ls.name as status_name 
                        FROM enquiries e 
                        JOIN users u ON e.attended_by = u.id 
                        JOIN sources s ON e.source_id = s.id 
                        JOIN lead_status ls ON e.status_id = ls.id 
                        ORDER BY e.received_datetime DESC LIMIT 5";
$recent_enquiries = mysqli_query($conn, $recent_enquiries_sql);

// Recent leads with file manager filtering
$recent_leads_sql = "SELECT e.*, u.full_name as attended_by_name, s.name as source_name, 
                    ls.name as status_name, cl.enquiry_number
                    FROM enquiries e 
                    JOIN users u ON e.attended_by = u.id 
                    JOIN sources s ON e.source_id = s.id 
                    JOIN lead_status ls ON e.status_id = ls.id 
                    JOIN converted_leads cl ON e.id = cl.enquiry_id
                    WHERE e.status_id = 3 AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)";

// Check if current user is assigned as file manager to any leads
$file_manager_check_sql = "SELECT COUNT(*) as count FROM converted_leads WHERE file_manager_id = " . $_SESSION["id"];
$fm_check_result = mysqli_query($conn, $file_manager_check_sql);
$fm_check = mysqli_fetch_assoc($fm_check_result);
$is_file_manager = $fm_check['count'] > 0;

// If user is not admin and is assigned as file manager to leads, show only their assigned leads
if($_SESSION["role_id"] != 1 && $is_file_manager) {
    $recent_leads_sql .= " AND cl.file_manager_id = " . $_SESSION["id"];
}

$recent_leads_sql .= " ORDER BY e.received_datetime DESC LIMIT 5";
$recent_leads = mysqli_query($conn, $recent_leads_sql);

// Recent confirmed bookings
$recent_confirmed_sql = "SELECT e.*, u.full_name as attended_by_name, s.name as source_name, 
                        ls.name as status_name, cl.enquiry_number, cl.travel_start_date, cl.travel_end_date
                        FROM enquiries e 
                        JOIN users u ON e.attended_by = u.id 
                        JOIN sources s ON e.source_id = s.id 
                        JOIN lead_status ls ON e.status_id = ls.id 
                        JOIN converted_leads cl ON e.id = cl.enquiry_id
                        WHERE cl.booking_confirmed = 1
                        ORDER BY e.received_datetime DESC LIMIT 5";
$recent_confirmed = mysqli_query($conn, $recent_confirmed_sql);

// Source distribution for all enquiries
$source_sql = "SELECT s.name, COUNT(*) as count 
              FROM enquiries e 
              JOIN sources s ON e.source_id = s.id 
              GROUP BY e.source_id 
              ORDER BY count DESC";
$source_result = mysqli_query($conn, $source_sql);
$source_data = [];
if($source_result) {
    while($row = mysqli_fetch_assoc($source_result)) {
        $source_data[] = $row;
    }
}

// Source distribution for leads
$leads_source_sql = "SELECT s.name, COUNT(*) as count 
                    FROM enquiries e 
                    JOIN sources s ON e.source_id = s.id 
                    WHERE e.status_id = 3
                    GROUP BY e.source_id 
                    ORDER BY count DESC";
$leads_source_result = mysqli_query($conn, $leads_source_sql);
$leads_source_data = [];
if($leads_source_result) {
    while($row = mysqli_fetch_assoc($leads_source_result)) {
        $leads_source_data[] = $row;
    }
}

// Source distribution for confirmed bookings
$confirmed_source_sql = "SELECT s.name, COUNT(*) as count 
                        FROM enquiries e 
                        JOIN sources s ON e.source_id = s.id 
                        JOIN converted_leads cl ON e.id = cl.enquiry_id
                        WHERE cl.booking_confirmed = 1
                        GROUP BY e.source_id 
                        ORDER BY count DESC";
$confirmed_source_result = mysqli_query($conn, $confirmed_source_sql);
$confirmed_source_data = [];
if($confirmed_source_result) {
    while($row = mysqli_fetch_assoc($confirmed_source_result)) {
        $confirmed_source_data[] = $row;
    }
}

// Marketing Cost and Revenue data from cost_sheets
$marketing_data = [];
for ($i = 0; $i < 6; $i++) {
    $month = $current_month - $i;
    $year = $current_year;
    
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    $month_name = date('M', mktime(0, 0, 0, $month, 1, $year));
    
    // Total expense (Marketing Cost) for this month
    $cost_sql = "SELECT SUM(total_expense) as total_cost FROM cost_sheets WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year";
    $cost_result = mysqli_query($conn, $cost_sql);
    $total_cost = 0;
    if ($cost_result) {
        $row = mysqli_fetch_assoc($cost_result);
        $total_cost = $row['total_cost'] ? floatval($row['total_cost']) : 0;
    }
    
    // Final amount (Revenue) for this month
    $revenue_sql = "SELECT SUM(final_amount) as total_revenue FROM cost_sheets WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year";
    $revenue_result = mysqli_query($conn, $revenue_sql);
    $total_revenue = 0;
    if ($revenue_result) {
        $row = mysqli_fetch_assoc($revenue_result);
        $total_revenue = $row['total_revenue'] ? floatval($row['total_revenue']) : 0;
    }
    
    $marketing_data[] = [
        'month' => $month_name,
        'cost' => $total_cost,
        'revenue' => $total_revenue
    ];
}

// Reverse to show oldest to newest
$marketing_data = array_reverse($marketing_data);

// Total marketing cost and revenue
$total_marketing_cost_sql = "SELECT SUM(total_expense) as total_cost FROM cost_sheets";
$total_marketing_cost_result = mysqli_query($conn, $total_marketing_cost_sql);
$total_marketing_cost = 0;
if($total_marketing_cost_result) {
    $row = mysqli_fetch_assoc($total_marketing_cost_result);
    $total_marketing_cost = $row['total_cost'] ? floatval($row['total_cost']) : 0;
}

$total_marketing_revenue_sql = "SELECT SUM(final_amount) as total_revenue FROM cost_sheets";
$total_marketing_revenue_result = mysqli_query($conn, $total_marketing_revenue_sql);
$total_marketing_revenue = 0;
if($total_marketing_revenue_result) {
    $row = mysqli_fetch_assoc($total_marketing_revenue_result);
    $total_marketing_revenue = $row['total_revenue'] ? floatval($row['total_revenue']) : 0;
}

// Total sale amount - set to 0 as sale_amount column doesn't exist yet
$total_sale_amount = 0;
?>



<div class="row pb-10">
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_enquiries; ?></div>
                    <div class="font-14 text-secondary weight-500">Total Enquiries</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#00eccf">
                        <i class="icon-copy dw dw-calendar1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_leads; ?></div>
                    <div class="font-14 text-secondary weight-500">Total Leads &nbsp;&nbsp;&nbsp;</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#ff5b5b">
                        <i class="icon-copy dw dw-user-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_pipeline; ?></div>
                    <div class="font-14 text-secondary weight-500">Total Pipeline &nbsp;&nbsp;&nbsp;</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#09cc06">
                        <i class="icon-copy dw dw-analytics-21"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_confirmed; ?></div>
                    <div class="font-14 text-secondary weight-500">Confirmed Bookings</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#1b00ff">
                        <i class="icon-copy dw dw-checked"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">0</div>
                    <div class="font-14 text-secondary weight-500">Travel Completed</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#28a745">
                        <i class="icon-copy dw dw-car"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_canceled; ?></div>
                    <div class="font-14 text-secondary weight-500">Booking Canceled</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#dc3545">
                        <i class="icon-copy dw dw-cancel"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row pb-10">
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($total_marketing_cost, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Marketing Cost</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#ff9800">
                        <i class="icon-copy dw dw-money-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($total_sale_amount, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Total Sale Amount</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#17a2b8">
                        <i class="icon-copy dw dw-invoice"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($total_marketing_revenue, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Total Revenue</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#4caf50">
                        <i class="icon-copy dw dw-analytics-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_pipeline; ?></div>
                    <div class="font-14 text-secondary weight-500">Pipeline Amount</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#09cc06">
                        <i class="icon-copy dw dw-analytics-21"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($profit_value, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Profit Value</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#28a745">
                        <i class="icon-copy dw dw-money-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($canceled_booking_amount, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Canceled Booking</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#6c757d">
                        <i class="icon-copy dw dw-money-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-30">
    <div class="col-12">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Overview Statistics</h4>
            <div class="row">
                <div class="col-md-6">
                    <div id="overview-chart" style="height: 300px;"></div>
                </div>
                <div class="col-md-6">
                    <div id="monthly-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-30" style="display: none;">
    <div class="col-12">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Marketing Cost vs Revenue</h4>
            <div class="row">
                <div class="col-md-6">
                    <div id="marketing-chart" style="height: 300px;"></div>
                </div>
                <div class="col-md-6">
                    <div id="marketing-comparison-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-xl-4 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Enquiry Sources</h4>
            <div id="chart"></div>
        </div>
    </div>
    <div class="col-xl-4 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Lead Sources</h4>
            <div id="leads-chart"></div>
        </div>
    </div>
    <div class="col-xl-4 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Confirmed Booking Sources</h4>
            <div id="confirmed-chart"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Recent Enquiries</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Lead #</th>
                            <th>Customer</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($enquiry = mysqli_fetch_assoc($recent_enquiries)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enquiry['lead_number']); ?></td>
                            <td><?php echo htmlspecialchars($enquiry['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($enquiry['source_name']); ?></td>
                            <td>
                                <?php 
                                $status_class = '';
                                switch($enquiry['status_id']) {
                                    case 1: $status_class = 'badge-warning'; break; // New
                                    case 2: $status_class = 'badge-primary'; break; // In Progress
                                    case 3: $status_class = 'badge-success'; break; // Converted to Lead
                                    case 4: $status_class = 'badge-danger'; break; // Closed
                                    default: $status_class = 'badge-secondary';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($enquiry['status_name']); ?></span>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($enquiry['received_datetime'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<div class="row">
    <div class="col-md-6 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Recent Leads</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Enquiry #</th>
                            <th>Customer</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_leads && mysqli_num_rows($recent_leads) > 0): ?>
                            <?php while($lead = mysqli_fetch_assoc($recent_leads)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['enquiry_number']); ?></td>
                                <td><?php echo htmlspecialchars($lead['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($lead['source_name']); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($lead['received_datetime'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No leads found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-30">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Recent Confirmed Bookings</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Enquiry #</th>
                            <th>Customer</th>
                            <th>Travel Date</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_confirmed && mysqli_num_rows($recent_confirmed) > 0): ?>
                            <?php while($booking = mysqli_fetch_assoc($recent_confirmed)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['enquiry_number']); ?></td>
                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                <td>
                                    <?php 
                                    if(!empty($booking['travel_start_date'])) {
                                        echo date('d-m-Y', strtotime($booking['travel_start_date']));
                                        if(!empty($booking['travel_end_date'])) {
                                            echo ' to ' . date('d-m-Y', strtotime($booking['travel_end_date']));
                                        }
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['source_name']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No confirmed bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p pd-20 min-height-200px">
            <div class="d-flex justify-content-between pb-10">
                <div class="h5 mb-0">Quick Links</div>
            </div>
            <div class="list-group">
                <?php if(hasPrivilege('upload_enquiries')): ?>
                <a href="upload_enquiries.php" class="list-group-item list-group-item-action">
                    <i class="icon-copy dw dw-upload1"></i> Upload Enquiries
                </a>
                <?php endif; ?>
                <?php if(hasPrivilege('view_enquiries')): ?>
                <a href="view_enquiries.php" class="list-group-item list-group-item-action">
                    <i class="icon-copy dw dw-list"></i> View Enquiries
                </a>
                <?php endif; ?>
                <?php if(hasPrivilege('view_leads')): ?>
                <a href="view_leads.php" class="list-group-item list-group-item-action">
                    <i class="icon-copy dw dw-filter"></i> View Leads
                </a>
                <?php endif; ?>
                <?php if(hasPrivilege('booking_confirmed')): ?>
                <a href="booking_confirmed.php" class="list-group-item list-group-item-action">
                    <i class="icon-copy dw dw-check"></i> Booking Confirmed
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8 col-md-6 mb-20">
        <div class="card-box height-100-p pd-20">
            <h4 class="h4 text-blue mb-20">Welcome to Lead Management System</h4>
            <p>This dashboard provides an overview of your leads and enquiries. Use the quick links to navigate to different sections of the application.</p>
            <p>For any assistance, please contact the system administrator.</p>
        </div>
    </div>
</div>

<!-- ApexCharts JS -->
<script src="assets/deskapp/src/plugins/apexcharts/apexcharts.min.js"></script>
<script>
    // Source distribution chart
    var options = {
        series: [
            <?php foreach($source_data as $source): ?>
            <?php echo $source['count']; ?>,
            <?php endforeach; ?>
        ],
        chart: {
            width: '100%',
            height: 350,
            type: 'pie',
        },
        labels: [
            <?php foreach($source_data as $source): ?>
            "<?php echo $source['name']; ?>",
            <?php endforeach; ?>
        ],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
    
    // Overview statistics chart
    var overviewOptions = {
        series: [{
            name: 'Count/Amount',
            data: [<?php echo $total_enquiries; ?>, <?php echo $total_leads; ?>, <?php echo $total_pipeline; ?>, <?php echo $total_confirmed; ?>, <?php echo $total_travel_completed; ?>, <?php echo $total_sale_amount; ?>, <?php echo $total_marketing_revenue; ?>, <?php echo $total_marketing_cost; ?>]
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: ['#00eccf', '#ff5b5b', '#09cc06', '#1b00ff', '#28a745', '#17a2b8', '#4caf50', '#ff9800'],
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                var index = opts.dataPointIndex;
                if (index >= 5) {
                    return '₹' + val.toFixed(0);
                }
                return val;
            },
            offsetY: -20,
            style: {
                fontSize: '10px',
                colors: ["#304758"]
            }
        },
        xaxis: {
            categories: ['Enquiries', 'Leads', 'Pipeline', 'Confirmed', 'Travel Done', 'Sale Amount', 'Revenue', 'Marketing Cost'],
            position: 'bottom',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            labels: {
                style: {
                    fontSize: '10px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Count/Amount'
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function(val, opts) {
                    var index = opts.dataPointIndex;
                    if (index >= 5) {
                        return '₹' + val.toFixed(2);
                    }
                    return val;
                }
            }
        }
    };

    var overviewChart = new ApexCharts(document.querySelector("#overview-chart"), overviewOptions);
    overviewChart.render();
    
    // Monthly statistics chart
    var monthlyOptions = {
        series: [{
            name: 'Enquiries',
            data: [<?php foreach($monthly_data as $data): echo $data['enquiries'] . ','; endforeach; ?>]
        }, {
            name: 'Leads',
            data: [<?php foreach($monthly_data as $data): echo $data['leads'] . ','; endforeach; ?>]
        }, {
            name: 'Confirmed',
            data: [<?php foreach($monthly_data as $data): echo $data['confirmed'] . ','; endforeach; ?>]
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: false
            }
        },
        colors: ['#00eccf', '#ff5b5b', '#1b00ff'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: [<?php foreach($months as $month): echo "'" . $month . "',"; endforeach; ?>],
        },
        yaxis: {
            title: {
                text: 'Count'
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val;
                }
            }
        },
        legend: {
            position: 'top'
        }
    };

    var monthlyChart = new ApexCharts(document.querySelector("#monthly-chart"), monthlyOptions);
    monthlyChart.render();
    
    // Leads source distribution chart
    var leadsOptions = {
        series: [
            <?php foreach($leads_source_data as $source): ?>
            <?php echo $source['count']; ?>,
            <?php endforeach; ?>
        ],
        chart: {
            width: '100%',
            height: 350,
            type: 'pie',
        },
        labels: [
            <?php foreach($leads_source_data as $source): ?>
            "<?php echo $source['name']; ?>",
            <?php endforeach; ?>
        ],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        colors: ['#ff5b5b', '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de', '#6f42c1']
    };

    var leadsChart = new ApexCharts(document.querySelector("#leads-chart"), leadsOptions);
    leadsChart.render();
    
    // Confirmed bookings source distribution chart
    var confirmedOptions = {
        series: [
            <?php foreach($confirmed_source_data as $source): ?>
            <?php echo $source['count']; ?>,
            <?php endforeach; ?>
        ],
        chart: {
            width: '100%',
            height: 350,
            type: 'pie',
        },
        labels: [
            <?php foreach($confirmed_source_data as $source): ?>
            "<?php echo $source['name']; ?>",
            <?php endforeach; ?>
        ],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        colors: ['#1b00ff', '#00eccf', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#e83e8c', '#17a2b8']
    };

    var confirmedChart = new ApexCharts(document.querySelector("#confirmed-chart"), confirmedOptions);
    confirmedChart.render();
    
    // Marketing Cost vs Revenue line chart
    var marketingOptions = {
        series: [{
            name: 'Marketing Cost',
            data: [<?php foreach($marketing_data as $data): echo $data['cost'] . ','; endforeach; ?>]
        }, {
            name: 'Revenue',
            data: [<?php foreach($marketing_data as $data): echo $data['revenue'] . ','; endforeach; ?>]
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: false
            }
        },
        colors: ['#ff9800', '#4caf50'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: [<?php foreach($marketing_data as $data): echo "'" . $data['month'] . "',"; endforeach; ?>],
        },
        yaxis: {
            title: {
                text: 'Amount (₹)'
            },
            labels: {
                formatter: function(val) {
                    return '₹' + val.toFixed(0);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return '₹' + val.toFixed(2);
                }
            }
        },
        legend: {
            position: 'top'
        }
    };

    var marketingChart = new ApexCharts(document.querySelector("#marketing-chart"), marketingOptions);
    marketingChart.render();
    
    // Marketing Cost vs Revenue comparison bar chart
    var marketingComparisonOptions = {
        series: [{
            name: 'Marketing Cost',
            data: [<?php echo $total_marketing_cost; ?>]
        }, {
            name: 'Revenue',
            data: [<?php echo $total_marketing_revenue; ?>]
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: ['#ff9800', '#4caf50'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return '₹' + val.toFixed(0);
            },
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ["#304758"]
            }
        },
        xaxis: {
            categories: ['Total'],
            position: 'bottom',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            title: {
                text: 'Amount (₹)'
            },
            labels: {
                formatter: function(val) {
                    return '₹' + val.toFixed(0);
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return '₹' + val.toFixed(2);
                }
            }
        },
        legend: {
            position: 'top'
        }
    };

    var marketingComparisonChart = new ApexCharts(document.querySelector("#marketing-comparison-chart"), marketingComparisonOptions);
    marketingComparisonChart.render();
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>