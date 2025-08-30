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

// User-based filtering
$user_filter = "";
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $current_user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if($current_user_id) {
        $user_filter = " AND cl.file_manager_id = " . $current_user_id;
    }
}

// Total enquiries (user-based)
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $sql = "SELECT COUNT(DISTINCT e.id) as count FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id WHERE 1=1" . $user_filter;
} else {
    $sql = "SELECT COUNT(*) as count FROM enquiries";
}
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_enquiries = $row['count'];
}

// Total leads (user-based)
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $sql = "SELECT COUNT(DISTINCT e.id) as count FROM enquiries e LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id WHERE e.status_id = 3" . $user_filter;
} else {
    $sql = "SELECT COUNT(*) as count FROM enquiries WHERE status_id = 3";
}
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

// Total pipeline leads (user-based)
if($_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12) {
    $sql = "SELECT COUNT(DISTINCT e.id) as count FROM enquiries e 
            JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
            LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
            WHERE lsm.status_name = 'Hot Prospect - Pipeline'" . $user_filter;
} else {
    $sql = "SELECT COUNT(*) as count FROM enquiries e 
            JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
            WHERE lsm.status_name = 'Hot Prospect - Pipeline'";
}
$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $total_pipeline = $row['count'];
}

// Marketing Cost and Revenue data
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

// Recent enquiries
$recent_enquiries_sql = "SELECT e.*, u.full_name as attended_by_name, s.name as source_name, ls.name as status_name 
                        FROM enquiries e 
                        JOIN users u ON e.attended_by = u.id 
                        JOIN sources s ON e.source_id = s.id 
                        JOIN lead_status ls ON e.status_id = ls.id 
                        ORDER BY e.received_datetime DESC LIMIT 10";
$recent_enquiries = mysqli_query($conn, $recent_enquiries_sql);

// Monthly data for charts
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
    
    $monthly_data[] = [
        'month' => $month_name,
        'enquiries' => $enquiries,
        'leads' => $leads
    ];
}

// Reverse the arrays to show oldest to newest
$monthly_data = array_reverse($monthly_data);
$months = array_reverse($months);
?>

<div class="title pb-20">
    <h2 class="h3 mb-0">Lead Management Overview</h2>
</div>

<div class="row pb-10">
    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
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
    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_leads; ?></div>
                    <div class="font-14 text-secondary weight-500">Total Leads</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#ff5b5b">
                        <i class="icon-copy dw dw-user-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark"><?php echo $total_pipeline; ?></div>
                    <div class="font-14 text-secondary weight-500">Pipeline Leads</div>
                </div>
                <div class="widget-icon">
                    <div class="icon" data-color="#09cc06">
                        <i class="icon-copy dw dw-analytics-21"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
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
</div>

<h4 class="h4 text-blue mb-20">Financial Metrics & Cancellations</h4>
<div class="row pb-10">
    <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p widget-style3">
            <div class="d-flex flex-wrap">
                <div class="widget-data">
                    <div class="weight-700 font-24 text-dark">₹<?php echo number_format($total_marketing_cost, 2); ?></div>
                    <div class="font-14 text-secondary weight-500">Ad Spend</div>
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
                    <div class="weight-700 font-24 text-dark">₹0.00</div>
                    <div class="font-14 text-secondary weight-500">Sales Value</div>
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
                    <div class="font-14 text-secondary weight-500">Revenue Value</div>
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
                    <div class="weight-700 font-24 text-dark">₹0.00</div>
                    <div class="font-14 text-secondary weight-500">Pipeline Value</div>
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
                    <div class="weight-700 font-24 text-dark">₹0.00</div>
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
                    <div class="weight-700 font-24 text-dark">₹0.00</div>
                    <div class="font-14 text-secondary weight-500">Cancel Value</div>
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

<div class="row pb-10">
    <div class="col-md-8 mb-20">
        <div class="card-box height-100-p pd-20">
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-0 pb-md-3">
                <div class="h5 mb-md-0">Lead Activities</div>
                <div class="form-group mb-md-0">
                    <select class="form-control form-control-sm selectpicker">
                        <option value="">Last Week</option>
                        <option value="">Last Month</option>
                        <option value="">Last 6 Month</option>
                        <option value="">Last 1 year</option>
                    </select>
                </div>
            </div>
            <div id="activities-chart"></div>
        </div>
    </div>
    <div class="col-md-4 mb-20">
        <div class="card-box min-height-200px pd-20 mb-20" data-bgcolor="#455a64">
            <div class="d-flex justify-content-between pb-20 text-white">
                <div class="icon h1 text-white">
                    <i class="icon-copy dw dw-money-1"></i>
                </div>
                <div class="font-14 text-right">
                    <div><i class="icon-copy ion-arrow-up-c"></i> 2.69%</div>
                    <div class="font-12">Since last month</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="text-white">
                    <div class="font-14">Marketing Cost</div>
                    <div class="font-24 weight-500">₹<?php echo number_format($total_marketing_cost, 0); ?></div>
                </div>
                <div class="max-width-150">
                    <div id="marketing-chart"></div>
                </div>
            </div>
        </div>
        <div class="card-box min-height-200px pd-20" data-bgcolor="#265ed7">
            <div class="d-flex justify-content-between pb-20 text-white">
                <div class="icon h1 text-white">
                    <i class="icon-copy dw dw-analytics-5"></i>
                </div>
                <div class="font-14 text-right">
                    <div><i class="icon-copy ion-arrow-up-c"></i> 3.69%</div>
                    <div class="font-12">Since last month</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="text-white">
                    <div class="font-14">Revenue</div>
                    <div class="font-24 weight-500">₹<?php echo number_format($total_marketing_revenue, 0); ?></div>
                </div>
                <div class="max-width-150">
                    <div id="revenue-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p pd-20 min-height-200px">
            <div class="d-flex justify-content-between pb-10">
                <div class="h5 mb-0">Quick Actions</div>
            </div>
            <div class="user-list">
                <ul>
                    <?php if(hasPrivilege('upload_enquiries')): ?>
                    <li class="d-flex align-items-center justify-content-between">
                        <div class="name-avatar d-flex align-items-center pr-2">
                            <div class="avatar mr-2 flex-shrink-0">
                                <div class="icon" style="background: #00eccf; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="icon-copy dw dw-upload1" style="color: white; font-size: 20px;"></i>
                                </div>
                            </div>
                            <div class="txt">
                                <div class="font-14 weight-600">Upload Enquiries</div>
                                <div class="font-12 weight-500" data-color="#b2b1b6">Add new enquiries</div>
                            </div>
                        </div>
                        <div class="cta flex-shrink-0">
                            <a href="upload_enquiries.php" class="btn btn-sm btn-outline-primary">Go</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_enquiries')): ?>
                    <li class="d-flex align-items-center justify-content-between">
                        <div class="name-avatar d-flex align-items-center pr-2">
                            <div class="avatar mr-2 flex-shrink-0">
                                <div class="icon" style="background: #ff5b5b; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="icon-copy dw dw-list" style="color: white; font-size: 20px;"></i>
                                </div>
                            </div>
                            <div class="txt">
                                <div class="font-14 weight-600">View Enquiries</div>
                                <div class="font-12 weight-500" data-color="#b2b1b6">Manage enquiries</div>
                            </div>
                        </div>
                        <div class="cta flex-shrink-0">
                            <a href="view_enquiries.php" class="btn btn-sm btn-outline-primary">Go</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_leads')): ?>
                    <li class="d-flex align-items-center justify-content-between">
                        <div class="name-avatar d-flex align-items-center pr-2">
                            <div class="avatar mr-2 flex-shrink-0">
                                <div class="icon" style="background: #09cc06; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="icon-copy dw dw-filter" style="color: white; font-size: 20px;"></i>
                                </div>
                            </div>
                            <div class="txt">
                                <div class="font-14 weight-600">View Leads</div>
                                <div class="font-12 weight-500" data-color="#b2b1b6">Manage leads</div>
                            </div>
                        </div>
                        <div class="cta flex-shrink-0">
                            <a href="view_leads.php" class="btn btn-sm btn-outline-primary">Go</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('booking_confirmed')): ?>
                    <li class="d-flex align-items-center justify-content-between">
                        <div class="name-avatar d-flex align-items-center pr-2">
                            <div class="avatar mr-2 flex-shrink-0">
                                <div class="icon" style="background: #1b00ff; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="icon-copy dw dw-check" style="color: white; font-size: 20px;"></i>
                                </div>
                            </div>
                            <div class="txt">
                                <div class="font-14 weight-600">Bookings</div>
                                <div class="font-12 weight-500" data-color="#b2b1b6">Confirmed bookings</div>
                            </div>
                        </div>
                        <div class="cta flex-shrink-0">
                            <a href="booking_confirmed.php" class="btn btn-sm btn-outline-primary">Go</a>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-20">
        <div class="card-box height-100-p pd-20 min-height-200px">
            <div class="d-flex justify-content-between">
                <div class="h5 mb-0">Lead Status Distribution</div>
            </div>
            <div id="lead-status-chart"></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-20">
        <div class="card-box height-100-p pd-20 min-height-200px">
            <div class="max-width-300 mx-auto">
                <img src="assets/deskapp/vendors/images/upgrade.svg" alt="" />
            </div>
            <div class="text-center">
                <div class="h5 mb-1">Lead Management System</div>
                <div class="font-14 weight-500 max-width-200 mx-auto pb-20" data-color="#a6a6a7">
                    Manage your leads and enquiries efficiently with our comprehensive system.
                </div>
                <a href="view_enquiries.php" class="btn btn-primary btn-lg">Get Started</a>
            </div>
        </div>
    </div>
</div>

<div class="card-box pb-10">
    <div class="h5 pd-20 mb-0">Recent Enquiries</div>
    <table class="data-table table nowrap">
        <thead>
            <tr>
                <th class="table-plus">Customer</th>
                <th>Lead #</th>
                <th>Source</th>
                <th>Status</th>
                <th>Date</th>
                <th class="datatable-nosort">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($enquiry = mysqli_fetch_assoc($recent_enquiries)): ?>
            <tr>
                <td class="table-plus">
                    <div class="name-avatar d-flex align-items-center">
                        <div class="avatar mr-2 flex-shrink-0">
                            <div style="background: #265ed7; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo strtoupper(substr($enquiry['customer_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="txt">
                            <div class="weight-600"><?php echo htmlspecialchars($enquiry['customer_name']); ?></div>
                        </div>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($enquiry['lead_number']); ?></td>
                <td><?php echo htmlspecialchars($enquiry['source_name']); ?></td>
                <td>
                    <?php 
                    $status_class = '';
                    switch($enquiry['status_id']) {
                        case 1: $status_class = 'badge-warning'; break;
                        case 2: $status_class = 'badge-primary'; break;
                        case 3: $status_class = 'badge-success'; break;
                        case 4: $status_class = 'badge-danger'; break;
                        default: $status_class = 'badge-secondary';
                    }
                    ?>
                    <span class="badge badge-pill <?php echo $status_class; ?>" data-bgcolor="#e7ebf5" data-color="#265ed7">
                        <?php echo htmlspecialchars($enquiry['status_name']); ?>
                    </span>
                </td>
                <td><?php echo date('d M Y', strtotime($enquiry['received_datetime'])); ?></td>
                <td>
                    <div class="table-actions">
                        <a href="view_enquiry_details.php?id=<?php echo $enquiry['id']; ?>" data-color="#265ed7">
                            <i class="icon-copy dw dw-edit2"></i>
                        </a>
                        <a href="#" data-color="#e95959">
                            <i class="icon-copy dw dw-delete-3"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="title pb-20 pt-20">
    <h2 class="h3 mb-0">Quick Start</h2>
</div>

<div class="row">
    <div class="col-md-4 mb-20">
        <a href="view_enquiries.php" class="card-box d-block mx-auto pd-20 text-secondary">
            <div class="img pb-30">
                <img src="assets/deskapp/vendors/images/medicine-bro.svg" alt="" />
            </div>
            <div class="content">
                <h3 class="h4">Enquiries</h3>
                <p class="max-width-200">
                    Manage and track all customer enquiries efficiently
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-20">
        <a href="view_leads.php" class="card-box d-block mx-auto pd-20 text-secondary">
            <div class="img pb-30">
                <img src="assets/deskapp/vendors/images/remedy-amico.svg" alt="" />
            </div>
            <div class="content">
                <h3 class="h4">Leads</h3>
                <p class="max-width-200">
                    Convert enquiries to leads and track progress
                </p>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-20">
        <a href="booking_confirmed.php" class="card-box d-block mx-auto pd-20 text-secondary">
            <div class="img pb-30">
                <img src="assets/deskapp/vendors/images/paper-map-cuate.svg" alt="" />
            </div>
            <div class="content">
                <h3 class="h4">Bookings</h3>
                <p class="max-width-200">
                    Manage confirmed bookings and travel arrangements
                </p>
            </div>
        </a>
    </div>
</div>

<div class="footer-wrap pd-20 mb-20 card-box">
    Lead Management System - Powered by DeskApp Template
</div>

<!-- ApexCharts JS -->
<script src="assets/deskapp/src/plugins/apexcharts/apexcharts.min.js"></script>
<script>
    // Lead Activities Chart
    var activitiesOptions = {
        series: [{
            name: 'Enquiries',
            data: [<?php foreach($monthly_data as $data): echo $data['enquiries'] . ','; endforeach; ?>]
        }, {
            name: 'Leads',
            data: [<?php foreach($monthly_data as $data): echo $data['leads'] . ','; endforeach; ?>]
        }],
        chart: {
            type: 'area',
            height: 350,
            toolbar: {
                show: false
            }
        },
        colors: ['#00eccf', '#ff5b5b'],
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
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
            }
        },
        legend: {
            position: 'top'
        }
    };

    var activitiesChart = new ApexCharts(document.querySelector("#activities-chart"), activitiesOptions);
    activitiesChart.render();

    // Marketing Cost Chart (Small)
    var marketingOptions = {
        series: [<?php echo $total_marketing_cost; ?>],
        chart: {
            type: 'radialBar',
            height: 150,
            sparkline: {
                enabled: true
            }
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '50%'
                },
                dataLabels: {
                    show: false
                }
            }
        },
        colors: ['#ffffff']
    };

    var marketingChart = new ApexCharts(document.querySelector("#marketing-chart"), marketingOptions);
    marketingChart.render();

    // Revenue Chart (Small)
    var revenueOptions = {
        series: [<?php echo $total_marketing_revenue; ?>],
        chart: {
            type: 'radialBar',
            height: 150,
            sparkline: {
                enabled: true
            }
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '50%'
                },
                dataLabels: {
                    show: false
                }
            }
        },
        colors: ['#ffffff']
    };

    var revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), revenueOptions);
    revenueChart.render();

    // Lead Status Distribution Chart
    var statusOptions = {
        series: [<?php echo $total_enquiries; ?>, <?php echo $total_leads; ?>, <?php echo $total_pipeline; ?>, <?php echo $total_confirmed; ?>],
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['Enquiries', 'Leads', 'Pipeline', 'Confirmed'],
        colors: ['#00eccf', '#ff5b5b', '#09cc06', '#1b00ff'],
        legend: {
            position: 'bottom'
        }
    };

    var statusChart = new ApexCharts(document.querySelector("#lead-status-chart"), statusOptions);
    statusChart.render();
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>