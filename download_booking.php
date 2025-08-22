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

// First, add confirmed column if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM tour_costings LIKE 'confirmed'";
$check_result = mysqli_query($conn, $check_column_sql);
if(mysqli_num_rows($check_result) == 0) {
    $add_column_sql = "ALTER TABLE tour_costings ADD COLUMN confirmed TINYINT(1) DEFAULT 0";
    mysqli_query($conn, $add_column_sql);
}

// Get booking details with latest cost sheet data
$booking_sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
                s.name as source_name, ac.name as campaign_name,
                cl.*, dest.name as destination_name, fm.full_name as file_manager_name,
                tc.* 
                FROM enquiries e 
                JOIN users u ON e.attended_by = u.id 
                JOIN departments d ON e.department_id = d.id 
                JOIN sources s ON e.source_id = s.id 
                LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
                JOIN converted_leads cl ON e.id = cl.enquiry_id
                LEFT JOIN destinations dest ON cl.destination_id = dest.id
                LEFT JOIN users fm ON cl.file_manager_id = fm.id
                LEFT JOIN tour_costings tc ON e.id = tc.enquiry_id AND tc.confirmed = 1
                WHERE e.id = ? ORDER BY tc.created_at DESC LIMIT 1";
$booking_stmt = mysqli_prepare($conn, $booking_sql);
mysqli_stmt_bind_param($booking_stmt, "i", $booking_id);
mysqli_stmt_execute($booking_stmt);
$booking_result = mysqli_stmt_get_result($booking_stmt);

if(mysqli_num_rows($booking_result) == 0) {
    // Try without confirmed filter if no confirmed cost sheet found
    $booking_sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
                    s.name as source_name, ac.name as campaign_name,
                    cl.*, dest.name as destination_name, fm.full_name as file_manager_name,
                    tc.* 
                    FROM enquiries e 
                    JOIN users u ON e.attended_by = u.id 
                    JOIN departments d ON e.department_id = d.id 
                    JOIN sources s ON e.source_id = s.id 
                    LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
                    JOIN converted_leads cl ON e.id = cl.enquiry_id
                    LEFT JOIN destinations dest ON cl.destination_id = dest.id
                    LEFT JOIN users fm ON cl.file_manager_id = fm.id
                    LEFT JOIN tour_costings tc ON e.id = tc.enquiry_id
                    WHERE e.id = ? ORDER BY tc.created_at DESC LIMIT 1";
    $booking_stmt = mysqli_prepare($conn, $booking_sql);
    mysqli_stmt_bind_param($booking_stmt, "i", $booking_id);
    mysqli_stmt_execute($booking_stmt);
    $booking_result = mysqli_stmt_get_result($booking_stmt);
    
    if(mysqli_num_rows($booking_result) == 0) {
        header("location: booking_confirmed.php");
        exit;
    }
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
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
        .company-logo { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 10px; }
        .receipt-title { font-size: 20px; margin: 10px 0; }
        .section { margin-bottom: 25px; }
        .section h3 { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 12px; margin: 0; border-radius: 5px; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f8f9fa; }
        .cost-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .cost-table th, .cost-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .cost-table th { background-color: #007bff; color: white; }
        .cost-table tr:nth-child(even) { background-color: #f8f9fa; }
        .total-row { background-color: #e3f2fd !important; font-weight: bold; }
        .service-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .service-title { font-weight: bold; color: #007bff; margin-bottom: 10px; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Print PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
    
    <div class="header">
        <div class="company-logo">GLEESIRE</div>
        <div class="receipt-title">BOOKING CONFIRMATION RECEIPT</div>
        <h3>File Number: <?php echo htmlspecialchars($booking['enquiry_number']); ?></h3>
        <?php if(!empty($booking['cost_sheet_number'])): ?>
        <p>Cost Sheet: <?php echo htmlspecialchars($booking['cost_sheet_number']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h3>Customer Information</h3>
        <table class="info-table">
            <tr><td>Customer Name:</td><td><?php echo htmlspecialchars($booking['customer_name']); ?></td></tr>
            <tr><td>Mobile Number:</td><td><?php echo htmlspecialchars($booking['mobile_number']); ?></td></tr>
            <tr><td>Email:</td><td><?php echo $booking['email'] ? htmlspecialchars($booking['email']) : '-'; ?></td></tr>
            <tr><td>Address:</td><td><?php echo htmlspecialchars($booking['guest_address'] ?? $booking['customer_location'] ?? '-'); ?></td></tr>
            <tr><td>WhatsApp:</td><td><?php echo htmlspecialchars($booking['whatsapp_number'] ?? $booking['secondary_contact'] ?? '-'); ?></td></tr>
            <tr><td>Nationality:</td><td><?php echo htmlspecialchars($booking['nationality'] ?? '-'); ?></td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Booking Details</h3>
        <table class="info-table">
            <tr><td>Booking Date:</td><td><?php echo date('d-m-Y', strtotime($booking['created_at'])); ?></td></tr>
            <tr><td>Lead Number:</td><td><?php echo htmlspecialchars($booking['lead_number']); ?></td></tr>
            <tr><td>Tour Package:</td><td><?php echo htmlspecialchars($booking['tour_package'] ?? '-'); ?></td></tr>
            <tr><td>Destination:</td><td><?php echo $booking['destination_name'] ? htmlspecialchars($booking['destination_name']) : '-'; ?></td></tr>
            <tr><td>Travel Month:</td><td><?php echo $booking['travel_month'] ? date('F Y', strtotime($booking['travel_month'])) : '-'; ?></td></tr>
            <tr><td>Travel Period:</td><td><?php echo $travel_period; ?></td></tr>
            <tr><td>Travelers:</td><td><?php echo $travelers_text; ?></td></tr>
            <tr><td>Currency:</td><td><?php echo htmlspecialchars($booking['currency'] ?? 'USD'); ?></td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Source Information</h3>
        <table class="info-table">
            <tr><td>Department:</td><td><?php echo htmlspecialchars($booking['department_name']); ?></td></tr>
            <tr><td>Source:</td><td><?php echo htmlspecialchars($booking['source_name']); ?></td></tr>
            <tr><td>Ad Campaign:</td><td><?php echo $booking['campaign_name'] ? htmlspecialchars($booking['campaign_name']) : '-'; ?></td></tr>
            <tr><td>Attended By:</td><td><?php echo htmlspecialchars($booking['attended_by_name']); ?></td></tr>
            <tr><td>File Manager:</td><td><?php echo $booking['file_manager_name'] ? htmlspecialchars($booking['file_manager_name']) : '-'; ?></td></tr>
        </table>
    </div>
    
    <?php if(!empty($booking['selected_services'])): ?>
    <div class="section">
        <h3>Selected Services</h3>
        <?php 
        $selected_services = json_decode($booking['selected_services'], true);
        if(!empty($selected_services)): 
        ?>
            <?php foreach($selected_services as $service): ?>
            <div class="service-section">
                <div class="service-title"><?php echo strtoupper(str_replace('_', ' ', $service)); ?></div>
                <?php 
                // Display service details using correct column names from tour_costings table
                $service_details = null;
                $service_lower = strtolower(str_replace(' ', '_', $service));
                
                if(strpos($service_lower, 'visa') !== false || strpos($service_lower, 'flight') !== false) {
                    $service_details = json_decode($booking['visa_data'] ?? '{}', true);
                } elseif(strpos($service_lower, 'accommodation') !== false || strpos($service_lower, 'hotel') !== false) {
                    $service_details = json_decode($booking['accommodation_data'] ?? '{}', true);
                } elseif(strpos($service_lower, 'transportation') !== false || strpos($service_lower, 'transport') !== false) {
                    $service_details = json_decode($booking['transportation_data'] ?? '{}', true);
                } elseif(strpos($service_lower, 'cruise') !== false) {
                    $service_details = json_decode($booking['cruise_data'] ?? '{}', true);
                } elseif(strpos($service_lower, 'extras') !== false || strpos($service_lower, 'miscellaneous') !== false) {
                    $service_details = json_decode($booking['extras_data'] ?? '{}', true);
                } elseif(strpos($service_lower, 'travel_insurance') !== false || strpos($service_lower, 'insurance') !== false) {
                    // Check if there's insurance data in extras or payment data
                    $insurance_data = json_decode($booking['extras_data'] ?? '{}', true);
                    if(isset($insurance_data['travel_insurance'])) {
                        $service_details = $insurance_data['travel_insurance'];
                    }
                } elseif(strpos($service_lower, 'agent_package') !== false || strpos($service_lower, 'agent') !== false) {
                    // Check if there's agent package data in extras
                    $agent_data = json_decode($booking['extras_data'] ?? '{}', true);
                    if(isset($agent_data['agent_package'])) {
                        $service_details = $agent_data['agent_package'];
                    }
                } elseif(strpos($service_lower, 'medical_tourism') !== false || strpos($service_lower, 'medical') !== false) {
                    // Check if there's medical tourism data in extras
                    $medical_data = json_decode($booking['extras_data'] ?? '{}', true);
                    if(isset($medical_data['medical_tourism'])) {
                        $service_details = $medical_data['medical_tourism'];
                    }
                }
                if(!empty($service_details) && is_array($service_details)):
                    // Display the data in a more structured format like the cost sheet
                    foreach($service_details as $key => $value):
                        if(!empty($value) && $key !== '0'):
                ?>
                <div style="margin-bottom: 10px;">
                    <strong><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</strong>
                    <?php if(is_array($value)): ?>
                        <table class="cost-table" style="margin-top: 5px;">
                            <?php 
                            $first_row = true;
                            foreach($value as $row):
                                if(is_array($row)):
                                    if($first_row):
                                        // Display headers
                                        echo '<tr>';
                                        foreach(array_keys($row) as $header):
                                            echo '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
                                        endforeach;
                                        echo '</tr>';
                                        $first_row = false;
                                    endif;
                                    // Display data
                                    echo '<tr>';
                                    foreach($row as $cell):
                                        echo '<td>' . htmlspecialchars($cell) . '</td>';
                                    endforeach;
                                    echo '</tr>';
                                endif;
                            endforeach;
                            ?>
                        </table>
                    <?php else: ?>
                        <?php echo htmlspecialchars($value); ?>
                    <?php endif; ?>
                </div>
                <?php 
                        endif;
                    endforeach;
                else: 
                ?>
                <p>No details available</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($booking['package_cost'])): ?>
    <div class="section">
        <h3>Cost Summary</h3>
        <table class="cost-table">
            <tr><th>Description</th><th>Amount</th></tr>
            <tr><td>Total Expense</td><td><?php echo htmlspecialchars($booking['currency'] ?? 'USD') . ' ' . number_format($booking['total_expense'] ?? 0, 2); ?></td></tr>
            <tr><td>Mark Up (<?php echo number_format($booking['markup_percentage'] ?? 0, 2); ?>%)</td><td><?php echo htmlspecialchars($booking['currency'] ?? 'USD') . ' ' . number_format($booking['markup_amount'] ?? 0, 2); ?></td></tr>
            <tr><td>Service Tax (<?php echo number_format($booking['tax_percentage'] ?? 0, 2); ?>%)</td><td><?php echo htmlspecialchars($booking['currency'] ?? 'USD') . ' ' . number_format($booking['tax_amount'] ?? 0, 2); ?></td></tr>
            <tr class="total-row"><td><strong>Total Package Cost</strong></td><td><strong><?php echo htmlspecialchars($booking['currency'] ?? 'USD') . ' ' . number_format($booking['package_cost'], 2); ?></strong></td></tr>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($booking['other_details'])): ?>
    <div class="section">
        <h3>Additional Details</h3>
        <p><?php echo nl2br(htmlspecialchars($booking['other_details'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p><strong>Thank you for choosing Gleesire!</strong></p>
        <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
<?php exit;

?>