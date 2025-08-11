<?php
// Include database connection
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('booking_confirmed')) {
    header("location: index.php");
    exit;
}

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: booking_confirmed.php");
    exit;
}

$booking_id = $_GET['id'];

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
mysqli_stmt_bind_param($booking_stmt, "i", $booking_id);
mysqli_stmt_execute($booking_stmt);
$booking_result = mysqli_stmt_get_result($booking_stmt);

if(mysqli_num_rows($booking_result) == 0) {
    header("location: booking_confirmed.php");
    exit;
}

$booking = mysqli_fetch_assoc($booking_result);

// Format travel period
$travel_period = '-';
if($booking['travel_start_date'] && $booking['travel_end_date']) {
    $travel_period = date('d-m-Y', strtotime($booking['travel_start_date'])) . ' to ' . date('d-m-Y', strtotime($booking['travel_end_date']));
}

// Format travelers
$travelers = array();
if($booking['adults_count'] > 0) $travelers[] = $booking['adults_count'] . ' Adults';
if($booking['children_count'] > 0) $travelers[] = $booking['children_count'] . ' Children';
if($booking['infants_count'] > 0) $travelers[] = $booking['infants_count'] . ' Infants';
$travelers_text = !empty($travelers) ? implode(', ', $travelers) : '-';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Details - <?php echo $booking['enquiry_number']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section h3 { background-color: #f5f5f5; padding: 10px; margin: 0; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        td:first-child { font-weight: bold; width: 30%; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Print PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
    
    <div class="header">
        <h1>Booking Details</h1>
        <h3>File Number: <?php echo htmlspecialchars($booking['enquiry_number']); ?></h3>
    </div>
    
    <div class="section">
        <h3>Customer Information</h3>
        <table>
            <tr><td>Customer Name:</td><td><?php echo htmlspecialchars($booking['customer_name']); ?></td></tr>
            <tr><td>Mobile Number:</td><td><?php echo htmlspecialchars($booking['mobile_number']); ?></td></tr>
            <tr><td>Email:</td><td><?php echo $booking['email'] ? htmlspecialchars($booking['email']) : '-'; ?></td></tr>
            <tr><td>Customer Location:</td><td><?php echo $booking['customer_location'] ? htmlspecialchars($booking['customer_location']) : '-'; ?></td></tr>
            <tr><td>Secondary Contact:</td><td><?php echo $booking['secondary_contact'] ? htmlspecialchars($booking['secondary_contact']) : '-'; ?></td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Booking Details</h3>
        <table>
            <tr><td>Booking Date:</td><td><?php echo date('d-m-Y', strtotime($booking['created_at'])); ?></td></tr>
            <tr><td>Lead Number:</td><td><?php echo htmlspecialchars($booking['lead_number']); ?></td></tr>
            <tr><td>Destination:</td><td><?php echo $booking['destination_name'] ? htmlspecialchars($booking['destination_name']) : '-'; ?></td></tr>
            <tr><td>Travel Month:</td><td><?php echo $booking['travel_month'] ? date('F Y', strtotime($booking['travel_month'])) : '-'; ?></td></tr>
            <tr><td>Travel Period:</td><td><?php echo $travel_period; ?></td></tr>
            <tr><td>Travelers:</td><td><?php echo $travelers_text; ?></td></tr>
            <tr><td>Customer Available Timing:</td><td><?php echo $booking['customer_available_timing'] ? htmlspecialchars($booking['customer_available_timing']) : '-'; ?></td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Source Information</h3>
        <table>
            <tr><td>Department:</td><td><?php echo htmlspecialchars($booking['department_name']); ?></td></tr>
            <tr><td>Source:</td><td><?php echo htmlspecialchars($booking['source_name']); ?></td></tr>
            <tr><td>Ad Campaign:</td><td><?php echo $booking['campaign_name'] ? htmlspecialchars($booking['campaign_name']) : '-'; ?></td></tr>
            <tr><td>Attended By:</td><td><?php echo htmlspecialchars($booking['attended_by_name']); ?></td></tr>
            <tr><td>File Manager:</td><td><?php echo $booking['file_manager_name'] ? htmlspecialchars($booking['file_manager_name']) : '-'; ?></td></tr>
        </table>
    </div>
    
    <?php if(!empty($booking['other_details'])): ?>
    <div class="section">
        <h3>Additional Details</h3>
        <p><?php echo nl2br(htmlspecialchars($booking['other_details'])); ?></p>
    </div>
    <?php endif; ?>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
<?php exit;

?>