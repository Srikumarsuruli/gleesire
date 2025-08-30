<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Get cost file ID
$cost_file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$enquiry_id = isset($_GET['enquiry_id']) ? intval($_GET['enquiry_id']) : 0;

if($cost_file_id == 0 and $enquiry_id == 0) {
    echo "<div class='alert alert-danger'>Invalid cost file ID.</div>";
    require_once "includes/footer.php";
    exit;
}

// Initialize variables
$success_message = "";
$error_message = "";

// Handle payment form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment'])) {
    try {
        // Handle file upload for payment receipt
        $payment_receipt = null;
        if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] == 0) {
            $upload_dir = 'uploads/receipts/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['payment_receipt']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $target_file)) {
                $payment_receipt = $target_file;
            }
        }
        
        $payment_date = $_POST['payment_date'] ?? null;
        $payment_bank = $_POST['payment_bank'] ?? '';
        $payment_amount = floatval($_POST['payment_amount'] ?? 0);
        
        if (!empty($payment_date) && !empty($payment_bank) && $payment_amount > 0) {
            // Check if payments table exists, if not create it
            $check_table_sql = "SHOW TABLES LIKE 'payments'";
            $table_exists = mysqli_query($conn, $check_table_sql);
            
            if (mysqli_num_rows($table_exists) == 0) {
                // Table doesn't exist, create it
                $create_table_sql = "CREATE TABLE payments (
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    cost_file_id INT(11) NOT NULL,
                    payment_date DATE NOT NULL,
                    payment_bank VARCHAR(100) NOT NULL,
                    payment_amount DECIMAL(10,2) NOT NULL,
                    payment_receipt VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (cost_file_id) REFERENCES tour_costings(id) ON DELETE CASCADE
                )";
                
                if (!mysqli_query($conn, $create_table_sql)) {
                    throw new Exception("Payments table creation failed: " . mysqli_error($conn));
                }
            }
            
            // Insert payment record
            $payment_sql = "INSERT INTO payments (cost_file_id, payment_date, payment_bank, payment_amount, payment_receipt) 
                           VALUES (?, ?, ?, ?, ?)";
            $payment_stmt = mysqli_prepare($conn, $payment_sql);
            mysqli_stmt_bind_param($payment_stmt, "issds", $cost_file_id, $payment_date, $payment_bank, $payment_amount, $payment_receipt);
            
            if (mysqli_stmt_execute($payment_stmt)) {
                $success_message = "Payment record added successfully!";
            } else {
                throw new Exception("Payment record insertion failed: " . mysqli_error($conn));
            }
            mysqli_stmt_close($payment_stmt);
        } else {
            $error_message = "Please fill all required payment fields.";
        }
        
    } catch(Exception $e) {
        $error_message = "Error adding payment: " . $e->getMessage();
    }
}

// Get cost file data
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number, e.referral_code, e.created_at as lead_date,
        s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE tc.id = ?";

if ($enquiry_id != 0){
    $sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number, e.referral_code, e.created_at as lead_date,
        s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE tc.enquiry_id = ?";
}

$_id = $cost_file_id != 0 ? $cost_file_id : $enquiry_id;

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Cost file not found.</div>";
    require_once "includes/footer.php";
    exit;
}

$cost_data = mysqli_fetch_assoc($result);
$enquiry_id = $cost_data['enquiry_id'];
$cost_file_id = $cost_data['id'];

// Set default values for NULL PAX counts
$cost_data['adults_count'] = $cost_data['adults_count'] ?? 0;
$cost_data['children_count'] = $cost_data['children_count'] ?? 0;
$cost_data['infants_count'] = $cost_data['infants_count'] ?? 0;

// Get payment history
$payment_history = [];
$check_payments_table = "SHOW TABLES LIKE 'payments'";
$payments_table_exists = mysqli_query($conn, $check_payments_table);
if(mysqli_num_rows($payments_table_exists) > 0) {
    $payments_sql = "SELECT * FROM payments WHERE cost_file_id = ? ORDER BY payment_date DESC";
    $payments_stmt = mysqli_prepare($conn, $payments_sql);
    mysqli_stmt_bind_param($payments_stmt, "i", $cost_file_id);
    mysqli_stmt_execute($payments_stmt);
    $payments_result = mysqli_stmt_get_result($payments_stmt);
    while($payment = mysqli_fetch_assoc($payments_result)) {
        $payment_history[] = $payment;
    }
    mysqli_stmt_close($payments_stmt);
}

// Calculate total received and balance
$total_received = 0;
foreach($payment_history as $payment) {
    $total_received += $payment['payment_amount'];
}
$package_cost = floatval($cost_data['package_cost']);
$balance_amount = $package_cost - $total_received;

// Decode JSON data for display
$selected_services = json_decode($cost_data['selected_services'] ?? '[]', true);
$visa_data = json_decode($cost_data['visa_data'] ?? '[]', true);
$accommodation_data = json_decode($cost_data['accommodation_data'] ?? '[]', true);
$transportation_data = json_decode($cost_data['transportation_data'] ?? '[]', true);
$cruise_data = json_decode($cost_data['cruise_data'] ?? '[]', true);
$extras_data = json_decode($cost_data['extras_data'] ?? '[]', true);
$agent_package_data = json_decode($cost_data['agent_package_data'] ?? '[]', true);
$medical_tourism_data = json_decode($cost_data['medical_tourism_data'] ?? '[]', true);


$accommodation_grand_total = 0;
$transportation_grand_total = 0;
$cruise_grand_total = 0;
$extras_grand_total = 0;
$agent_package_grand_total = 0;
$medical_tourism_grand_total = 0;

if (!empty($accommodation_data) && is_array($accommodation_data)) {
    foreach ($accommodation_data as $accom) {
        $accommodation_grand_total += floatval($accom['total'] ?? 0);
    }
}

if (!empty($transportation_data) && is_array($transportation_data)) {
    foreach ($transportation_data as $trans) {
        $transportation_grand_total += floatval($trans['total'] ?? 0); 
    }
}

if (!empty($cruise_data) && is_array($cruise_data)) {
    foreach ($cruise_data as $cruise) {
        $cruise_grand_total += floatval($cruise['total'] ?? 0);
    }
}

if (!empty($extras_data) && is_array($extras_data)) {
    foreach ($extras_data as $extra) {
        $extras_grand_total += floatval($extra['total'] ?? 0);
    }
}

if (!empty($agent_package_data) && is_array($agent_package_data)) {
    foreach ($agent_package_data as $package) {
        $agent_package_grand_total += floatval($package['total'] ?? 0);
    }
}

if (!empty($medical_tourism_data) && is_array($medical_tourism_data)) {
    foreach ($medical_tourism_data as $medical) {
        $medical_tourism_grand_total += floatval($medical['total'] ?? 0);
    }
}

// Get children age details from converted_leads table
$children_age_details = '';
if (isset($cost_data['enquiry_id'])) {
    $children_details_sql = "SELECT children_age_details FROM converted_leads WHERE enquiry_id = ?";
    $children_details_stmt = mysqli_prepare($conn, $children_details_sql);
    mysqli_stmt_bind_param($children_details_stmt, "i", $cost_data['enquiry_id']);
    mysqli_stmt_execute($children_details_stmt);
    $children_details_result = mysqli_stmt_get_result($children_details_stmt);
    
    if ($children_details_row = mysqli_fetch_assoc($children_details_result)) {
        $children_age_details = $children_details_row['children_age_details'];
    }
    
    mysqli_stmt_close($children_details_stmt);
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="cost_file_styles.css">

<div class="cost-file-container">
    <div class="cost-file-card">
        <div class="cost-file-header">
            <h1 class="cost-file-title">Cost File Details</h1>
            <p class="cost-file-subtitle">
                Cost Sheet No: <?php echo htmlspecialchars($cost_data['cost_sheet_number']); ?> | 
                Reference: <?php echo htmlspecialchars($cost_data['customer_name']); ?> | 
                Last Updated: <?php echo date('d-m-Y H:i', strtotime($cost_data['updated_at'])); ?>
            </p>
            <div style="margin-top: 20px;">
                <a href="booking_confirmed.php" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Confirmed Bookings
                </a>
            </div>
        </div>

        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div id="cost-file-view">
            <div class="info-grid">
                <!-- Customer Information -->
                <div class="info-card">
                    <h5><i class="fas fa-user"></i> Customer Information</h5>
                    <div class="info-row">
                        <span class="info-label">Guest Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['guest_name'] ?? $cost_data['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mobile:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['mobile_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guest Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['guest_address'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">WhatsApp Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['whatsapp_number'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['lead_number'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Date:</span>
                        <span class="info-value"><?php echo $cost_data['lead_date'] ? date('d-m-Y', strtotime($cost_data['lead_date'])) : 'N/A'; ?></span>
                    </div>
                </div>

                <!-- Travel Information -->
                <div class="info-card">
                    <h5><i class="fas fa-plane"></i> Travel Information</h5>
                    <div class="info-row">
                        <span class="info-label">Tour Package:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['tour_package'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Currency:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['currency'] ?? 'USD'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nationality:</span>
                        <span class="info-value"><?php 
                            $nationalities = ['IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom', 'AE' => 'United Arab Emirates', 'SA' => 'Saudi Arabia'];
                            echo $nationalities[$cost_data['nationality']] ?? $cost_data['nationality'] ?? 'N/A';
                        ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ref Code:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['referral_code'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Source / Agent:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['source_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Travel Destination:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['destination_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Passenger Information -->
                <div class="info-card">
                    <h5><i class="fas fa-users"></i> Number of PAX</h5>
                    <div class="info-row">
                        <span class="info-label">Adults:</span>
                        <span class="info-value"><?php echo $cost_data['adults_count'] ?? 0; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children:</span>
                        <span class="info-value"><?php echo $cost_data['children_count'] ?? 0; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children Age Details:</span>
                        <span class="info-value"><?php echo htmlspecialchars($children_age_details ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Infants:</span>
                        <span class="info-value"><?php echo $cost_data['infants_count'] ?? 0; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total PAX:</span>
                        <span class="info-value"><?php echo ($cost_data['adults_count'] ?? 0) + ($cost_data['children_count'] ?? 0) + ($cost_data['infants_count'] ?? 0); ?></span>
                    </div>
                </div>

                <!-- Selected Services -->
                <div class="info-card">
                    <h5><i class="fas fa-cogs"></i> Selected Services</h5>
                    <div class="services-list">
                        <?php if(empty($selected_services)): ?>
                            <p>No services selected</p>
                        <?php else: ?>
                            <?php 
                            $service_names = [
                                'visa_flight' => 'VISA / FLIGHT BOOKING',
                                'accommodation' => 'ACCOMMODATION', 
                                'transportation' => 'TRANSPORTATION',
                                'cruise_hire' => 'CRUISE HIRE',
                                'extras' => 'EXTRAS/MISCELLANEOUS',
                                'travel_insurance' => 'TRAVEL INSURANCE',
                                'agent_package' => 'AGENT PACKAGE SERVICE',
                                'medical_tourism' => 'MEDICAL TOURISM'
                            ];
                            $service_icons = [
                                'visa_flight' => 'fas fa-plane',
                                'accommodation' => 'fas fa-bed',
                                'transportation' => 'fas fa-car', 
                                'cruise_hire' => 'fas fa-ship',
                                'extras' => 'fas fa-plus',
                                'travel_insurance' => 'fas fa-shield-alt',
                                'agent_package' => 'fas fa-briefcase',
                                'medical_tourism' => 'fas fa-hospital-o'
                            ];
                            foreach($selected_services as $service): ?>
                                <div class="service-item selected">
                                    <i class="<?php echo $service_icons[$service] ?? 'fas fa-check'; ?> service-icon-small"></i>
                                    <span class="service-text"><?php echo $service_names[$service] ?? strtoupper($service); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Service Details Summary -->
            <div class="services-summary" style="margin-top: 30px; padding: 12px;">
                <h5 style="color: #000;><i class="fas fa-list"></i> Service Details Summary</h5>
                
                <?php if(!empty($visa_data)): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-plane"></i> Visa/Flight Details</h6>
                    <div class="table-responsive" style="margin-bottom: 18px;">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>S. NO</th>
                                    <th>SECTOR</th>
                                    <th>SUPPLIER</th>
                                    <th>TRAVEL DATE</th>
                                    <th>PASSENGERS</th>
                                    <th>RATE PER PERSON</th>
                                    <th>ROE</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody id="visa-details-tbody">
                                <?php if (!empty($visa_data) && is_array($visa_data)): ?>
                                    <?php foreach ($visa_data as $index => $visa): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($visa['sector'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($visa['supplier'] ?? ''); ?></td>
                                        <td><?php echo $visa['travel_date'] ?? ''; ?></td>
                                        <td><?php echo $visa['passengers'] ?? '0'; ?></td>
                                        <td><?php echo $visa['rate_per_person'] ?? '0'; ?></td>
                                        <td><?php echo $visa['roe'] ?? '1'; ?></td>
                                        <td><?php echo $visa['total'] ?? '0.00'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-right"><strong>TOTAL VISA COST:</strong></td>
                                    <td>
                                        <?php 
                                            $total_visa_cost = 0;
                                            if (!empty($visa_data) && is_array($visa_data)) {
                                                foreach ($visa_data as $visa) {
                                                    $total_visa_cost += floatval($visa['total'] ?? 0);
                                                }
                                            }
                                            echo number_format($total_visa_cost, 2);
                                        ?>
                                    </td>                           
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h6 style="margin-top: 1rem"><i class="fas fa-calendar-alt"></i> Travel Details</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Travel Period</th>
                                    <th>Date</th>
                                    <th>City</th>
                                    <th>Flight</th>
                                    <th>Nights/Days</th>
                                    <th>Flight Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ARRIVAL</strong></td>
                                    <td> <?php echo $cost_data['arrival_date'] ? date('d-m-Y H:i', strtotime($cost_data['arrival_date'])) : 'N/A'; ?></td>
                                    <td><?php echo $cost_data['arrival_flight'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['arrival_nights_days'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['arrival_connection'] ?? ''; ?></td>
                                </tr>
                                <tr id="arrival-connecting" style="display: <?php echo isset($cost_data['arrival_connecting_date']) ? 'table-row' : 'none'; ?>">
                                    <td><strong>ARRIVAL (Connecting)</strong></td>
                                    <td> <?php echo $cost_data['arrival_connecting_date'] ? date('d-m-Y H:i', strtotime($cost_data['arrival_connecting_date'])) : 'N/A'; ?></td>
                                    <td><?php echo $cost_data['arrival_connecting_city'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['arrival_connecting_flight'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['arrival_connecting_nights_days'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['arrival_connecting_type'] ?? ''; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>DEPARTURE</strong></td>
                                    <td> <?php echo $cost_data['departure_date'] ? date('d-m-Y H:i', strtotime($cost_data['departure_date'])) : 'N/A'; ?></td>
                                    <td><?php echo $cost_data['departure_city'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_flight'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_nights_days'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_connection'] ?? ''; ?></td>
                                </tr>
                                <tr id="departure-connecting" style="display: <?php echo !empty($cost_data['departure_connecting_date']) ? 'table-row' : 'none'; ?>">
                                    <td><strong>DEPARTURE (Connecting)</strong></td>
                                    <td> <?php echo $cost_data['departure_connecting_date'] ? date('d-m-Y H:i', strtotime($cost_data['departure_connecting_date'])) : 'N/A'; ?></td>
                                    <td><?php echo $cost_data['departure_connecting_city'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_connecting_flight'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_connecting_nights_days'] ?? ''; ?></td>
                                    <td><?php echo $cost_data['departure_connecting_type'] ?? ''; ?></td>
                                </tr>                            
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($accommodation_data) && is_array($accommodation_data) && !empty($accommodation_data[0]['destination'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-bed"></i> Accommodation Details</h6>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Hotel</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Room Type</th>
                                <th>Rooms</th>
                                <th>Meal Plan</th>
                                <th>Rate ($)</th>
                                <th>Extra Bed Adult</th>
                                <th>Rate/Bed ($)</th>
                                <th>Extra Bed Child</th>
                                <th>Rate ($)</th>
                                <th>Child No Bed</th>
                                <th>Rate ($)</th>
                                <th>Nights</th>
                                
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="accommodation-tbody">
                            <?php foreach ($accommodation_data as $index => $accom): ?>
                            <tr>
                                    <td><?php echo htmlspecialchars($accom['destination'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($accom['hotel'] ?? ''); ?></td>
                                    <td><?php echo $accom['check_in'] ?? ''; ?></td>
                                    <td><?php echo $accom['check_out'] ?? ''; ?></td>
                                    <td><?php echo htmlspecialchars($accom['room_type'] ?? ''); ?></td>
                                    <td><?php echo $accom['rooms_no'] ?? '0'; ?></td>
                                    <td><?php echo strtoupper($accom['meal_plan'] ?? ''); ?></td>
                                    <td><?php echo $accom['rooms_rate'] ?? '0'; ?></td>
                                    <td><?php echo $accom['extra_adult_no'] ?? '0'; ?></td>
                                    <td><?php echo $accom['extra_adult_rate'] ?? '0'; ?></td>
                                    <td><?php echo $accom['extra_child_no'] ?? '0'; ?></td>
                                    <td><?php echo $accom['extra_child_rate'] ?? '0'; ?></td>
                                    <td><?php echo $accom['child_no_bed_no'] ?? '0'; ?></td>
                                    <td><?php echo $accom['child_no_bed_rate'] ?? '0'; ?></td>
                                    <td><?php echo $accom['nights'] ?? '0'; ?></td>
                                    <td><?php echo $accom['total'] ?? '0.00'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="15" class="text-right"><strong>TOTAL ACCOMMODATION COST:</strong></td>
                                <td><?php echo $accommodation_grand_total ?? '0.00'; ?></td>
                            </tr>
                        </tfoot>                    
                    </table>
                </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($transportation_data) && is_array($transportation_data) && !empty($transportation_data[0]['supplier'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-car"></i> Transportation Details</h6>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Car Type</th>
                                <th>Daily Rent</th>
                                <th>Days</th>
                                <th>Km</th>
                                <th>Extra Km</th>
                                <th>Price/Km</th>
                                <th>Toll/Parking</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="transportation-tbody">
                            <?php foreach ($transportation_data as $index => $trans): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trans['supplier'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($trans['car_type'] ?? ''); ?></td>
                                <td><?php echo $trans['daily_rent'] ?? '0'; ?></td>
                                <td><?php echo $trans['days'] ?? '2'; ?></td>
                                <td><?php echo $trans['km'] ?? '0'; ?></td>
                                <td><?php echo $trans['extra_km'] ?? '0'; ?></td>
                                <td><?php echo $trans['price_per_km'] ?? '0'; ?></td>
                                <td><?php echo $trans['toll'] ?? '0'; ?></td>
                                <td><?php echo $trans['total'] ?? '0.00'; ?></td>
                            </tr>                            
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-right"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><?php echo $transportation_grand_total ?? '0.00'; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($cruise_data) && is_array($cruise_data) && !empty($cruise_data[0]['supplier'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-ship"></i> Cruise Details</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>S. No</th>
                                    <th>Supplier</th>
                                    <th>Type Of Boat</th>
                                    <th>Cruise Type</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Rate</th>
                                    <th>Extra</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="cruise-tbody">
                                <?php foreach ($cruise_data as $index => $cruise): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($cruise['supplier'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($cruise['boat_type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($cruise['cruise_type'] ?? ''); ?></td>
                                    <td><?php echo $cruise['check_in'] ?? ''; ?></td>
                                    <td><?php echo $cruise['check_out'] ?? ''; ?></td>
                                    <td><?php echo $cruise['rate'] ?? '0'; ?></td>
                                    <td><?php echo $cruise['extra'] ?? '0'; ?></td>
                                    <td><?php echo $cruise['total'] ?? '0.00'; ?></td>
                                </tr>                                
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8" class="text-right"><strong>TOTAL CRUISE COST:</strong></td>
                                    <td><?php echo $cruise_grand_total ?? '0.00'; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($agent_package_data) && is_array($agent_package_data) && !empty($agent_package_data[0]['destination'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-briefcase"></i> Agent Package Service</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Destination</th>
                                    <th>Agent/Supplier</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Adults</th>
                                    <th>Price/Adult</th>
                                    <th>Children</th>
                                    <th>Price/Child</th>
                                    <th>Infants</th>
                                    <th>Price/Infant</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="agent-package-tbody">
                               
                                <?php foreach ($agent_package_data as $index => $package): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($package['destination'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($package['agent_supplier'] ?? ''); ?></td>
                                        <td><?php echo $package['start_date'] ?? ''; ?></td>
                                        <td><?php echo $package['end_date'] ?? ''; ?></td>
                                        <td><?php echo $package['adult_count'] ?? $cost_data['adults_count'] ?? 0; ?></td>
                                        <td><?php echo $package['adult_price'] ?? 0; ?></td>
                                        <td><?php echo $package['child_count'] ?? $cost_data['children_count'] ?? 0; ?></td>
                                        <td><?php echo $package['child_price'] ?? 0; ?></td>
                                        <td><?php echo $package['infant_count'] ?? $cost_data['infants_count'] ?? 0; ?></td>
                                        <td><?php echo $package['infant_price'] ?? 0; ?></td>
                                        <td><?php echo $package['total'] ?? '0.00'; ?></td>
                                    </tr>                                    
                                <?php endforeach; ?>
                               
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="10" class="text-right"><strong>TOTAL AGENT PACKAGE COST:</strong></td>
                                    <td><?php echo $agent_package_grand_total ?? '0.00'; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                

                <?php if(!empty($medical_tourism_data) && is_array($medical_tourism_data) && !empty($medical_tourism_data[0]['place'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-hospital-o"></i> Medical Tourism</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Place</th>
                                    <th>Treatment Date</th>
                                    <th>Hospital Name</th>
                                    <th>Treatment Type</th>
                                    <th>Op/Ip</th>
                                    <th>Net</th>
                                    <th>Tds</th>
                                    <th>Other Expenses</th>
                                    <th>Gst</th>                                
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="medical-tourism-tbody">
                                
                                <?php foreach ($medical_tourism_data as $index => $package): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($package['place'] ?? ''); ?></td>
                                    <td><?php echo $package['treatment_date'] ?? ''; ?></td>
                                    <td><?php echo htmlspecialchars($package['hospital'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($package['treatment_type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($package['op_ip'] ?? ''); ?></td>
                                    <td><?php echo $package['net'] ?? 0; ?></td>
                                    <td><?php echo $package['tds'] ?? 0; ?></td>
                                    <td><?php echo $package['other_expenses'] ?? 0; ?></td>
                                    <td>18%</td>
                                    <td><?php echo $package['total'] ?? '0.00'; ?></td>
                                </tr>                                
                                <?php endforeach; ?>
                               
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="9" class="text-right"><strong>TOTAL MEDICAL TOURISM COST:</strong></td>
                                    <td><?php echo $medical_tourism_grand_total ?? '0.00'; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                
                <?php if(!empty($extras_data) && is_array($extras_data) && !empty($extras_data[0]['supplier'])): ?>
                <div class="service-summary-card">
                    <h6><i class="fas fa-plus"></i> Extras/Miscellaneous</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Type Of Service</th>
                                    <th>Amount</th>
                                    <th>Extras</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="extras-tbody">
                                <?php foreach ($extras_data as $index => $extra): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($extra['supplier'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($extra['service_type'] ?? ''); ?></td>
                                    <td><?php echo $extra['amount'] ?? '0'; ?></td>
                                    <td><?php echo $extra['extras'] ?? '0'; ?></td>
                                    <td><?php echo $extra['total'] ?? '0.00'; ?></td>
                                </tr>                                
                                <?php endforeach; ?>
                                
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>TOTAL EXTRAS COST:</strong></td>
                                    <td><?php echo $extras_grand_total ?? '0.00'; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
                <?php endif; ?>
            </div>

            <!-- Cost Summary -->
            <div class="cost-summary" style="margin-top: 30px; padding: 12px;">
                <h5><i class="fas fa-calculator"></i> Cost Summary</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td><strong>TOTAL EXPENSE</strong></td>
                                <td><?php echo $cost_data['currency']; ?> <?php echo number_format($cost_data['total_expense'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>MARK UP (PROFIT)</strong></td>
                                <td><?php echo $cost_data['currency']; ?> <?php echo number_format($cost_data['markup_amount'], 2); ?> (<?php echo $cost_data['markup_percentage']; ?>%)</td>
                            </tr>
                            <tr>
                                <td><strong>SERVICE TAX</strong></td>
                                <td><?php echo $cost_data['currency']; ?> <?php echo number_format($cost_data['tax_amount'], 2); ?> (<?php echo $cost_data['tax_percentage']; ?>%)</td>
                            </tr>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td><strong>PACKAGE COST</strong></td>
                                <td><?php echo $cost_data['currency']; ?> <?php echo number_format($cost_data['package_cost'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount in <?php echo $cost_data['currency']; ?></strong></td>
                                <td><?php echo number_format($cost_data['converted_amount'], 2); ?> (Rate: <?php echo $cost_data['currency_rate']; ?>)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Management Section -->
            <div class="payment-management-section" style="margin-top: 30px; padding: 12px;">
                <div class="info-card">
                    <h5><i class="fas fa-credit-card"></i> Payment Details & History</h5>
                    
                    <!-- Payment Summary -->
                    <div class="payment-summary" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Package Cost:</strong><br>
                                <span style="font-size: 1.2em; color: #007bff;"><?php echo $cost_data['currency']; ?> <?php echo number_format($package_cost, 2); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Received:</strong><br>
                                <span style="font-size: 1.2em; color: #28a745;"><?php echo $cost_data['currency']; ?> <?php echo number_format($total_received, 2); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Balance Amount:</strong><br>
                                <span style="font-size: 1.2em; color: <?php echo $balance_amount > 0 ? '#dc3545' : '#28a745'; ?>;"><?php echo $cost_data['currency']; ?> <?php echo number_format($balance_amount, 2); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Payment Status:</strong><br>
                                <span class="badge <?php echo $balance_amount <= 0 ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $balance_amount <= 0 ? 'Fully Paid' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if($balance_amount > 0): ?>
                    
                    <!-- Add Payment Form -->
                    <div class="add-payment-form" style="border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <h6><i class="fas fa-plus"></i> Add New Payment</h6>
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Payment Date:</label>
                                    <input type="date" class="form-control form-control-sm" name="payment_date" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Bank:</label>
                                    <select class="form-control form-control-sm" name="payment_bank" required>
                                        <option value="">Select Bank</option>
                                        <option value="HDFC BANK">HDFC BANK</option>
                                        <option value="ICICI BANK">ICICI BANK</option>
                                        <option value="SBI">SBI</option>
                                        <option value="AXIS BANK">AXIS BANK</option>
                                        <option value="KOTAK BANK">KOTAK BANK</option>
                                        <option value="CASH">CASH</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Amount:</label>
                                    <input type="number" class="form-control form-control-sm" name="payment_amount" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Receipt:</label>
                                    <input type="file" class="form-control form-control-sm" name="payment_receipt" accept="image/*">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" name="add_payment" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Payment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php endif; ?>
                    
                    <!-- Payment History -->
                    <div class="payment-history">
                        <h6><i class="fas fa-history"></i> Payment History</h6>
                        <?php if(empty($payment_history)): ?>
                            <p class="text-muted">No payment records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Bank</th>
                                            <th>Amount</th>
                                            <th>Receipt</th>
                                            <th>Added On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($payment_history as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_bank']); ?></td>
                                            <td><?php echo $cost_data['currency']; ?> <?php echo number_format($payment['payment_amount'], 2); ?></td>
                                            <td>
                                                <?php if($payment['payment_receipt']): ?>
                                                    <a href="<?php echo htmlspecialchars($payment['payment_receipt']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No receipt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($payment['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                        
                


                <?php 
                if(isAdmin() || getRoleAccess("Accounts Manager") || getRoleAccess("Accounts Team")): ?>
                <a 
                    href="view_cost_sheets.php?action=export_pdf&id=<?php echo $cost_file_id; ?>" 
                    class="btn btn-danger"
                    target="_blank"
                    style="padding: 10px 20px; font-weight: bold;"
                >
                    <i class="fa fa-file-pdf-o"></i> Download Full PDF
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.service-summary-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
}

.service-summary-card h6 {
    margin-bottom: 5px;
    color: #495057;
}

.service-summary-card p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9em;
}

.badge-success {
    background-color: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 0.8em;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 0.8em;
}
</style>

<script>
// Simple view-only page - no complex calculations needed
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cost file view loaded');
});
</script>

<?php
require_once "includes/footer.php";
?>