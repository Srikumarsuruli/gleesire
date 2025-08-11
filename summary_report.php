<?php
require_once "includes/header.php";

if(!hasPrivilege('summary_report')) {
    header("location: index.php");
    exit;
}

// Function to get counts based on date range
function getCounts($conn, $date_condition = "") {
    $data = [];
    
    // Amount Spent from ad_campaigns (no date filter for campaigns)
    $amount_sql = "SELECT SUM(budget) as total_amount FROM ad_campaigns";
    $amount_result = mysqli_query($conn, $amount_sql);
    $data['amount_spent'] = mysqli_fetch_assoc($amount_result)['total_amount'] ?? 0;
    
    // Enquiries count
    $enquiry_sql = "SELECT COUNT(*) as count FROM enquiries e WHERE 1=1" . $date_condition;
    $enquiry_result = mysqli_query($conn, $enquiry_sql);
    $data['enquiries'] = mysqli_fetch_assoc($enquiry_result)['count'];
    
    // Leads count
    $leads_sql = "SELECT COUNT(*) as count FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id WHERE ls.name LIKE '%Lead%'" . $date_condition;
    $leads_result = mysqli_query($conn, $leads_sql);
    $data['leads'] = mysqli_fetch_assoc($leads_result)['count'];
    
    // Booking Confirmed count
    $booking_sql = "SELECT COUNT(*) as count FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id WHERE ls.name = 'Booking Confirmed'" . $date_condition;
    $booking_result = mysqli_query($conn, $booking_sql);
    $data['booking_confirmed'] = mysqli_fetch_assoc($booking_result)['count'];
    
    // Pipeline count
    $pipeline_sql = "SELECT COUNT(*) as count FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id WHERE ls.name = 'Pipeline'" . $date_condition;
    $pipeline_result = mysqli_query($conn, $pipeline_sql);
    $data['pipeline'] = mysqli_fetch_assoc($pipeline_result)['count'];
    
    // Travel Completed count
    $travel_sql = "SELECT COUNT(*) as count FROM enquiries e JOIN lead_status ls ON e.status_id = ls.id WHERE ls.name = 'Travel Completed'" . $date_condition;
    $travel_result = mysqli_query($conn, $travel_sql);
    $data['travel_completed'] = mysqli_fetch_assoc($travel_result)['count'];
    
    // Cost Sheet count
    $cost_condition = str_replace('e.received_datetime', 'created_at', $date_condition);
    if(empty($cost_condition)) $cost_condition = "";
    $cost_sql = "SELECT COUNT(*) as count FROM tour_costings" . ($cost_condition ? " WHERE 1=1" . $cost_condition : "");
    $cost_result = mysqli_query($conn, $cost_sql);
    $data['cost_sheet'] = mysqli_fetch_assoc($cost_result)['count'];
    
    return $data;
}

// Get data for different time periods
$summary_data = [
    'Day' => getCounts($conn, " AND DATE(e.received_datetime) = CURDATE()"),
    'Yesterday' => getCounts($conn, " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"),
    'Week' => getCounts($conn, " AND DATE(e.received_datetime) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"),
    'Month' => getCounts($conn, " AND DATE(e.received_datetime) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"),
    'Year' => getCounts($conn, " AND DATE(e.received_datetime) >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)"),
    'All Time' => getCounts($conn, "")
];

// Handle export requests
if(isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if($export_type == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="summary_report.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th>Period</th><th>Amount Spent</th><th>Enquiries</th><th>Leads</th><th>Booking Confirmed</th><th>Pipeline</th><th>Travel Completed</th><th>Cost Sheet</th></tr>";
        foreach($summary_data as $period => $data) {
            echo "<tr>";
            echo "<td>$period</td>";
            echo "<td>" . number_format($data['amount_spent'], 2) . "</td>";
            echo "<td>{$data['enquiries']}</td>";
            echo "<td>{$data['leads']}</td>";
            echo "<td>{$data['booking_confirmed']}</td>";
            echo "<td>{$data['pipeline']}</td>";
            echo "<td>{$data['travel_completed']}</td>";
            echo "<td>{$data['cost_sheet']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }
    
    if($export_type == 'pdf') {
        require_once 'vendor/autoload.php'; // Assuming you have TCPDF or similar
        // Basic PDF export - you may need to install TCPDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="summary_report.pdf"');
        
        $html = "<h1>Summary Report</h1>";
        $html .= "<table border='1'>";
        $html .= "<tr><th>Period</th><th>Amount Spent</th><th>Enquiries</th><th>Leads</th><th>Booking Confirmed</th><th>Pipeline</th><th>Travel Completed</th><th>Cost Sheet</th></tr>";
        foreach($summary_data as $period => $data) {
            $html .= "<tr>";
            $html .= "<td>$period</td>";
            $html .= "<td>" . number_format($data['amount_spent'], 2) . "</td>";
            $html .= "<td>{$data['enquiries']}</td>";
            $html .= "<td>{$data['leads']}</td>";
            $html .= "<td>{$data['booking_confirmed']}</td>";
            $html .= "<td>{$data['pipeline']}</td>";
            $html .= "<td>{$data['travel_completed']}</td>";
            $html .= "<td>{$data['cost_sheet']}</td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
        
        // Simple HTML to PDF conversion (you may want to use a proper PDF library)
        echo $html;
        exit;
    }
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="text-blue h4">Summary Report</h4>
            <div>
                <a href="?export=excel" class="btn btn-success btn-sm mr-2">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
                <a href="?export=pdf" class="btn btn-danger btn-sm">
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Summary</th>
                        <th>Amount Spent</th>
                        <th>Enquiries</th>
                        <th>Leads</th>
                        <th>Booking Confirmed</th>
                        <th>Pipeline</th>
                        <th>Travel Completed</th>
                        <th>Cost Sheet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($summary_data as $period => $data): ?>
                    <tr>
                        <td><strong><?php echo $period; ?></strong></td>
                        <td>₹<?php echo number_format($data['amount_spent'], 2); ?></td>
                        <td><?php echo $data['enquiries']; ?></td>
                        <td><?php echo $data['leads']; ?></td>
                        <td><?php echo $data['booking_confirmed']; ?></td>
                        <td><?php echo $data['pipeline']; ?></td>
                        <td><?php echo $data['travel_completed']; ?></td>
                        <td><?php echo $data['cost_sheet']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.table th {
    background-color: #343a40;
    color: white;
    text-align: center;
    vertical-align: middle;
}
.table td {
    text-align: center;
    vertical-align: middle;
}
.table td:first-child {
    text-align: left;
    font-weight: 600;
}
</style>

<?php require_once "includes/footer.php"; ?>