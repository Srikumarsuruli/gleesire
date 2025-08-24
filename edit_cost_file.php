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

// Add PAX columns to table if they don't exist (one by one)
$columns_to_add = [
    'adults_count' => 'INT DEFAULT 0',
    'children_count' => 'INT DEFAULT 0', 
    'infants_count' => 'INT DEFAULT 0'
];

foreach($columns_to_add as $column => $definition) {
    $check_column = "SHOW COLUMNS FROM tour_costings LIKE '$column'";
    $result_check = mysqli_query($conn, $check_column);
    if(mysqli_num_rows($result_check) == 0) {
        $add_column = "ALTER TABLE tour_costings ADD COLUMN $column $definition";
        mysqli_query($conn, $add_column);
    }
}

// Get cost file data
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number as enquiry_number, cl.enquiry_number as lead_number, e.referral_code, e.created_at as enquiry_date, cl.created_at as lead_date,
        s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name, cl.night_day as night_day, cl.travel_start_date as travel_start_date, cl.travel_end_date as travel_end_date, dp.name as department_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        LEFT JOIN departments dp ON e.department_id = dp.id
        WHERE tc.id = ?";

if ($enquiry_id != 0){
    $sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number as enquiry_number, cl.enquiry_number as lead_number, e.referral_code, e.created_at as enquiry_date, cl.created_at as lead_date,
        s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name, cl.night_day as night_day, cl.travel_start_date as travel_start_date, cl.travel_end_date as travel_end_date, dp.name as department_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        LEFT JOIN departments dp ON e.department_id = dp.id
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

// Set default values for NULL PAX counts
$cost_data['adults_count'] = $cost_data['adults_count'] ?? 0;
$cost_data['children_count'] = $cost_data['children_count'] ?? 0;
$cost_data['infants_count'] = $cost_data['infants_count'] ?? 0;



// Initialize variables
$success_message = "";
$error_message = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
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
        
        // Prepare data for update
        $guest_name = $_POST['guest_name'] ?? '';
        $guest_address = $_POST['guest_address'] ?? '';
        $whatsapp_number = $_POST['whatsapp_number'] ?? '';
        $tour_package = $_POST['tour_package'] ?? '';
        $currency = $_POST['currency'] ?? 'USD';
        $nationality = $_POST['nationality'] ?? '';
        $confirmed = intval($_POST['confirmed'] ?? 0);
        $adults_count = intval($_POST['adults_count'] ?? 0);
        $children_count = intval($_POST['children_count'] ?? 0);
        $infants_count = intval($_POST['infants_count'] ?? 0);
        $children_age_details = $_POST['children_age_details'] ?? '';
        $selected_services = json_encode($_POST['services'] ?? []);

        $arrival_date = $_POST['arrival_date'] ?? '';
        $arrival_city = $_POST['arrival_city'] ?? '';
        $arrival_flight = $_POST['arrival_flight'] ?? '';
        $arrival_nights_days = $_POST['arrival_nights_days'] ?? '';
        $arrival_connection = $_POST['arrival_connection'] ?? '';
        
        $arrival_connecting_date = $_POST['arrival_connecting_date'] ?? '';
        $arrival_connecting_city = $_POST['arrival_connecting_city'] ?? '';
        $arrival_connecting_flight = $_POST['arrival_connecting_flight'] ?? '';
        $arrival_connecting_nights_days = $_POST['arrival_connecting_nights_days'] ?? '';
        $arrival_connecting_type = $_POST['arrival_connecting_type'] ?? '';
        
        $departure_date = $_POST['departure_date'] ?? '';
        $departure_city = $_POST['departure_city'] ?? '';
        $departure_flight = $_POST['departure_flight'] ?? '';
        $departure_nights_days = $_POST['departure_nights_days'] ?? '';
        $departure_connection = $_POST['departure_connection'] ?? '';
        
        // Update children_age_details in converted_leads table
        if (!empty($children_age_details) && isset($cost_data['enquiry_id'])) {
            $update_children_sql = "UPDATE converted_leads SET children_age_details = ? WHERE enquiry_id = ?";
            $update_children_stmt = mysqli_prepare($conn, $update_children_sql);
            mysqli_stmt_bind_param($update_children_stmt, "si", $children_age_details, $cost_data['enquiry_id']);
            mysqli_stmt_execute($update_children_stmt);
            mysqli_stmt_close($update_children_stmt);
        }
        
        // Encode service data as JSON
        $visa_data = json_encode($_POST['visa'] ?? []);
        $accommodation_data = json_encode($_POST['accommodation'] ?? []);
        $transportation_data = json_encode($_POST['transportation'] ?? []);
        $cruise_data = json_encode($_POST['cruise'] ?? []);
        $extras_data = json_encode($_POST['extras'] ?? []);
        $agent_package_data = json_encode($_POST['agent_package'] ?? []);
        $medical_tourism_data = json_encode($_POST['medical_tourisms'] ?? []);
        
        // Get existing payment data to preserve receipt if no new one is uploaded
        $existing_payment_data = json_decode($cost_data['payment_data'] ?? '{}', true);
        $existing_receipt = $existing_payment_data['receipt'] ?? null;
        
        // Use new receipt if uploaded, otherwise keep existing receipt
        $final_receipt = $payment_receipt ?? $existing_receipt;
        
        $payment_data = json_encode([
            'date' => $_POST['payment_date'] ?? '',
            'bank' => $_POST['payment_bank'] ?? '',
            'amount' => $_POST['payment_amount'] ?? 0,
            'total_received' => $_POST['total_received'] ?? 0,
            'balance_amount' => $_POST['balance_amount'] ?? 0,
            'receipt' => $final_receipt
        ]);
        
        // Summary data
        $total_expense = floatval($_POST['total_expense'] ?? 0);
        $markup_percentage = floatval($_POST['markup_percentage'] ?? 0);
        $markup_amount = floatval($_POST['markup_amount'] ?? 0);
        $tax_percentage = floatval($_POST['tax_percentage'] ?? 18);
        $tax_amount = floatval($_POST['tax_amount'] ?? 0);
        $package_cost = floatval($_POST['package_cost'] ?? 0);
        $currency_rate = floatval($_POST['currency_rate'] ?? 1);
        $converted_amount = floatval($_POST['converted_amount'] ?? 0);
        
        // Generate new version number
        $base_number = preg_replace('/-S\d+$/', '', $cost_data['cost_sheet_number']);
        $version_sql = "SELECT cost_sheet_number FROM tour_costings WHERE cost_sheet_number LIKE ? ORDER BY cost_sheet_number DESC LIMIT 1";
        $version_stmt = mysqli_prepare($conn, $version_sql);
        $search_pattern = $base_number . '-S%';
        mysqli_stmt_bind_param($version_stmt, "s", $search_pattern);
        mysqli_stmt_execute($version_stmt);
        $version_result = mysqli_stmt_get_result($version_stmt);
        
        $next_version = 2; // Default to S2
        if($version_row = mysqli_fetch_assoc($version_result)) {
            if(preg_match('/-S(\d+)$/', $version_row['cost_sheet_number'], $matches)) {
                $next_version = intval($matches[1]) + 1;
            }
        }
        $new_cost_sheet_number = $base_number . '-S' . $next_version;
        
        // Insert new version instead of updating
        $insert_sql = "INSERT INTO tour_costings (
            enquiry_id, cost_sheet_number, guest_name, guest_address, whatsapp_number,
            tour_package, currency, nationality, adults_count, children_count, infants_count,
            selected_services, visa_data,
            
            arrival_date, arrival_city, arrival_flight, arrival_nights_days, arrival_connection,
            arrival_connecting_date, arrival_connecting_city, arrival_connecting_flight, arrival_connecting_nights_days, arrival_connecting_type,
            departure_date, departure_city, departure_flight, departure_nights_days, departure_connection,
            
            accommodation_data, transportation_data, cruise_data, extras_data, agent_package_data,
            medical_tourism_data, payment_data, total_expense, markup_percentage, markup_amount,
            tax_percentage, tax_amount, package_cost, currency_rate, converted_amount, confirmed
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        if (!$insert_stmt) {
            die("SQL Prepare failed: " . mysqli_error($conn) . "\nQuery: " . $insert_sql);
        }

        // $cost_data['enquiry_id'], $new_cost_sheet_number,
        // Debug: Count parameters
        $params = [
            $cost_data['enquiry_id'], $new_cost_sheet_number, $guest_name, $guest_address, 
            $whatsapp_number, $tour_package, $currency, $nationality, $adults_count, 
            $children_count, $infants_count, $selected_services, $visa_data,

            $arrival_date, $arrival_city, $arrival_flight, $arrival_nights_days, $arrival_connection,
            $arrival_connecting_date, $arrival_connecting_city, $arrival_connecting_flight, $arrival_connecting_nights_days, $arrival_connecting_type,
            $departure_date, $departure_city, $departure_flight, $departure_nights_days, $departure_connection,

            $accommodation_data, $transportation_data, $cruise_data, $extras_data, 
            $agent_package_data, $medical_tourism_data, $payment_data, $total_expense, 
            $markup_percentage, $markup_amount, $tax_percentage, $tax_amount, 
            $package_cost, $currency_rate, $converted_amount, $confirmed
        ];
        // Build type string to match exact parameter count
        // Build type string to match exact parameter count
        $type_string = str_repeat('s', count($params)); 

        // Adjust numeric fields
        $type_string[0] = 'i';  // enquiry_id
        $type_string[8] = 'i';  // adults_count
        $type_string[9] = 'i';  // children_count
        $type_string[10] = 'i'; // infants_count
            
        // Decimal/float fields
        $type_string[35] = 'd'; // total_expense
        $type_string[36] = 'd'; // markup_percentage
        $type_string[37] = 'd'; // markup_amount
        $type_string[38] = 'd'; // tax_percentage
        $type_string[39] = 'd'; // tax_amount
        $type_string[40] = 'd'; // package_cost
        $type_string[41] = 'd'; // currency_rate
        $type_string[42] = 'd'; // converted_amount
            
        // confirmed is integer
        $type_string[43] = 'i';
        
        mysqli_stmt_bind_param($insert_stmt, $type_string, ...$params);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $new_cost_file_id = mysqli_insert_id($conn);
            $success_message = "New cost file version created successfully! New version: " . $new_cost_sheet_number;
            
            // Check if payment details are provided and save to payments table
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
                mysqli_stmt_bind_param($payment_stmt, "issds", $new_cost_file_id, $payment_date, $payment_bank, $payment_amount, $final_receipt);
                
                if (!mysqli_stmt_execute($payment_stmt)) {
                    throw new Exception("Payment record insertion failed: " . mysqli_error($conn));
                }
                mysqli_stmt_close($payment_stmt);
            }
        } else {
            throw new Exception("Database insert failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($insert_stmt);

        echo "<script>window.location.href='view_cost_sheets.php?success=true';</script>";
        exit;
        
    } catch(Exception $e) {
        $error_message = "Error updating cost file: " . $e->getMessage();
    }
}

// Get destinations for dropdown
$destinations_sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $destinations_sql);

$accommodations_sql = "SELECT * FROM accommodation_details ORDER BY destination";
$accommodations = mysqli_query($conn, $accommodations_sql);

$transport_sql = "SELECT * FROM transport_details WHERE status = 'Active' ORDER BY destination";
$transport_details = mysqli_query($conn, $transport_sql);

$agent_sql = "SELECT * FROM travel_agents WHERE status = 'Active' ORDER BY destination";
$agent_details = mysqli_query($conn, $agent_sql);

$hospital_sql = "SELECT * FROM hospital_details WHERE status = 'Active' ORDER BY destination";
$hospital_details = mysqli_query($conn, $hospital_sql);


// Decode JSON data for form population
$selected_services = json_decode($cost_data['selected_services'] ?? '[]', true);
$visa_data = json_decode($cost_data['visa_data'] ?? '[]', true);
$accommodation_data = json_decode($cost_data['accommodation_data'] ?? '[]', true);
$transportation_data = json_decode($cost_data['transportation_data'] ?? '[]', true);
$cruise_data = json_decode($cost_data['cruise_data'] ?? '[]', true);
$extras_data = json_decode($cost_data['extras_data'] ?? '[]', true);
$agent_package_data = json_decode($cost_data['agent_package_data'] ?? '[]', true);
$medical_tourism_data = json_decode($cost_data['medical_tourism_data'] ?? '[]', true);
$payment_data = json_decode($cost_data['payment_data'] ?? '{}', true);

// Ensure payment_data is an array
if (!is_array($payment_data)) {
    $payment_data = [];
}

// Ensure payment data has all required fields
// Always set total_received to the payment amount
$payment_data['total_received'] = $payment_data['amount'] ?? 0;

// Calculate balance amount based on package cost and total received
$package_cost = floatval($cost_data['package_cost']);
$total_received = floatval($payment_data['total_received']);
$payment_data['balance_amount'] = $package_cost - $total_received;
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="cost_file_styles.css">


<div class="cost-file-container">
    <div class="cost-file-card">
        <div class="cost-file-header">
            <h1 class="cost-file-title"> Create New Version</h1>
            <p class="cost-file-subtitle">
                Cost Sheet No: <?php echo htmlspecialchars($cost_data['cost_sheet_number']); ?> | 
                Reference: <?php echo htmlspecialchars($cost_data['customer_name']); ?> | 
                Last Updated: <?php echo date('d-m-Y H:i', strtotime($cost_data['updated_at'])); ?>
            </p>
            <p class="cost-file-subtitle">
                Enquiry No: <?php echo htmlspecialchars($cost_data['enquiry_number']); ?> | 
                Enquiry Date: <?php echo date('d-m-Y H:i', strtotime($cost_data['enquiry_date'])); ?> | 
                File Manager: <?php echo htmlspecialchars($cost_data['file_manager_name']); ?>
            </p>
            
            <div style="margin-top: 20px;">
                <a href="view_payment_receipts.php?id=<?php echo $cost_file_id; ?>" class="btn btn-sm btn-info">
                    <i class="fa fa-credit-card"></i> View Payment Details
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

        <form method="post" id="cost-file-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $cost_file_id); ?>" enctype="multipart/form-data" onsubmit="return confirmNewVersion(event)">
            <div class="info-grid">
                <!-- Customer Information -->
                <div class="info-card">
                    <h5><i class="fas fa-user"></i> Customer Information</h5>
                    <div class="info-row">
                        <span class="info-label">Guest Name:</span>
                        <input type="text" class="form-control form-control-sm" name="guest_name" value="<?php echo htmlspecialchars($cost_data['guest_name'] ?? $cost_data['customer_name']); ?>">
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
                        <input type="text" class="form-control form-control-sm" name="guest_address" value="<?php echo htmlspecialchars($cost_data['guest_address'] ?? ''); ?>">
                    </div>
                    <div class="info-row">
                        <span class="info-label">WhatsApp Number:</span>
                        <input type="text" class="form-control form-control-sm" name="whatsapp_number" value="<?php echo htmlspecialchars($cost_data['whatsapp_number'] ?? ''); ?>">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['lead_number'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Lead Date:</span>
                        <span class="info-value"><?php echo $cost_data['lead_date'] ? date('d-m-Y', strtotime($cost_data['lead_date'])) : 'N/A'; ?></span>
                        
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['department_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Night/Day:</span>
                        <span class="info-value"><?php echo htmlspecialchars($cost_data['night_day'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Travel Period:</span>
                        <span class="info-value"><?php echo date('d-m-Y', strtotime($cost_data['travel_start_date'])) ?? 'N/A'; ?> To <?php echo date('d-m-Y', strtotime($cost_data['travel_end_date'])) ?? 'N/A'; ?></span>
                    </div>
                </div>

                <!-- Travel Information -->
                <div class="info-card">
                    <h5><i class="fas fa-plane"></i> Travel Information</h5>
                    <div class="info-row">
                        <span class="info-label">Tour Package:</span>
                        <select class="form-control form-control-sm" name="tour_package">
                            <option value="">Select Package</option>
                            <option value="Honeymoon Package" <?php echo ($cost_data['tour_package'] == 'Honeymoon Package') ? 'selected' : ''; ?>>Honeymoon Package</option>
                            <option value="Family Package" <?php echo ($cost_data['tour_package'] == 'Family Package') ? 'selected' : ''; ?>>Family Package</option>
                            <option value="Adventure Package" <?php echo ($cost_data['tour_package'] == 'Adventure Package') ? 'selected' : ''; ?>>Adventure Package</option>
                            <option value="Business Package" <?php echo ($cost_data['tour_package'] == 'Business Package') ? 'selected' : ''; ?>>Business Package</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Currency:</span>
                        <select class="form-control form-control-sm" id="currency-selector" name="currency" onchange="updateCurrencySymbols()">
                            <option value="USD" <?php echo ($cost_data['currency'] == 'USD') ? 'selected' : ''; ?>>USD</option>
                            <option value="EUR" <?php echo ($cost_data['currency'] == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                            <option value="GBP" <?php echo ($cost_data['currency'] == 'GBP') ? 'selected' : ''; ?>>GBP</option>
                            <option value="INR" <?php echo ($cost_data['currency'] == 'INR') ? 'selected' : ''; ?>>INR</option>
                            <option value="BHD" <?php echo ($cost_data['currency'] == 'BHD') ? 'selected' : ''; ?>>BHD</option>
                            <option value="KWD" <?php echo ($cost_data['currency'] == 'KWD') ? 'selected' : ''; ?>>KWD</option>
                            <option value="OMR" <?php echo ($cost_data['currency'] == 'OMR') ? 'selected' : ''; ?>>OMR</option>
                            <option value="QAR" <?php echo ($cost_data['currency'] == 'QAR') ? 'selected' : ''; ?>>QAR</option>
                            <option value="SAR" <?php echo ($cost_data['currency'] == 'SAR') ? 'selected' : ''; ?>>SAR</option>
                            <option value="AED" <?php echo ($cost_data['currency'] == 'AED') ? 'selected' : ''; ?>>AED</option>
                            <option value="THB" <?php echo ($cost_data['currency'] == 'THB') ? 'selected' : ''; ?>>THB</option>
                            <option value="STD" <?php echo ($cost_data['currency'] == 'STD') ? 'selected' : ''; ?>>STD</option>
                            <option value="SGD" <?php echo ($cost_data['currency'] == 'SGD') ? 'selected' : ''; ?>>SGD</option>
                            <option value="RM" <?php echo ($cost_data['currency'] == 'RM') ? 'selected' : ''; ?>>RM</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nationality:</span>
                        <select class="form-control form-control-sm" name="nationality">
                            <option value="">Select Country</option>
                            <option value="IN" <?php echo ($cost_data['nationality'] == 'IN') ? 'selected' : ''; ?>>India</option>
                            <option value="US" <?php echo ($cost_data['nationality'] == 'US') ? 'selected' : ''; ?>>United States</option>
                            <option value="GB" <?php echo ($cost_data['nationality'] == 'GB') ? 'selected' : ''; ?>>United Kingdom</option>
                            <option value="AE" <?php echo ($cost_data['nationality'] == 'AE') ? 'selected' : ''; ?>>United Arab Emirates</option>
                            <option value="SA" <?php echo ($cost_data['nationality'] == 'SA') ? 'selected' : ''; ?>>Saudi Arabia</option>
                        </select>
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
                        <input type="number" class="form-control form-control-sm" name="adults_count" value="<?php echo isset($cost_data['adults_count']) && $cost_data['adults_count'] !== null ? $cost_data['adults_count'] : '0'; ?>" min="0">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children:</span>
                        <input type="number" class="form-control form-control-sm" name="children_count" value="<?php echo isset($cost_data['children_count']) && $cost_data['children_count'] !== null ? $cost_data['children_count'] : '0'; ?>" min="0">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children Age Details:</span>
                        <input type="text" class="form-control form-control-sm" name="children_age_details" placeholder="e.g. 5, 7, 10 years" value="<?php 
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
                            echo htmlspecialchars($children_age_details);
                        ?>">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Infants:</span>
                        <input type="number" class="form-control form-control-sm" name="infants_count" value="<?php echo isset($cost_data['infants_count']) && $cost_data['infants_count'] !== null ? $cost_data['infants_count'] : '0'; ?>" min="0">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total PAX:</span>
                        <input type="text" class="form-control form-control-sm" id="total-pax" readonly>
                    </div>
                </div>

                <!-- Services Selection -->
                <div class="info-card">
                    <h5><i class="fas fa-cogs"></i> Select Services</h5>
                    <div class="services-list">
                        <div class="service-item <?php echo in_array('visa_flight', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'visa_flight')">
                            <i class="fas fa-plane service-icon-small"></i>
                            <span class="service-text">VISA / FLIGHT BOOKING</span>
                            <input type="checkbox" name="services[]" value="visa_flight" <?php echo in_array('visa_flight', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('accommodation', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'accommodation')">
                            <i class="fas fa-bed service-icon-small"></i>
                            <span class="service-text">ACCOMMODATION</span>
                            <input type="checkbox" name="services[]" value="accommodation" <?php echo in_array('accommodation', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('transportation', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'transportation')">
                            <i class="fas fa-car service-icon-small"></i>
                            <span class="service-text">TRANSPORTATION</span>
                            <input type="checkbox" name="services[]" value="transportation" <?php echo in_array('transportation', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('cruise_hire', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'cruise_hire')">
                            <i class="fas fa-ship service-icon-small"></i>
                            <span class="service-text">CRUISE HIRE</span>
                            <input type="checkbox" name="services[]" value="cruise_hire" <?php echo in_array('cruise_hire', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('extras', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'extras')">
                            <i class="fas fa-plus service-icon-small"></i>
                            <span class="service-text">EXTRAS/MISCELLANEOUS</span>
                            <input type="checkbox" name="services[]" value="extras" <?php echo in_array('extras', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('travel_insurance', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'travel_insurance')">
                            <i class="fas fa-shield-alt service-icon-small"></i>
                            <span class="service-text">TRAVEL INSURANCE</span>
                            <input type="checkbox" name="services[]" value="travel_insurance" <?php echo in_array('travel_insurance', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('agent_package', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'agent_package')">
                            <i class="fas fa-briefcase service-icon-small"></i>
                            <span class="service-text">AGENT PACKAGE SERVICE</span>
                            <input type="checkbox" name="services[]" value="agent_package" <?php echo in_array('agent_package', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        <div class="service-item <?php echo in_array('medical_tourism', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'medical_tourism')">
                            <i class="fas fa-hospital-o service-icon-small"></i>
                            <span class="service-text">MEDICAL TOURISM</span>
                            <input type="checkbox" name="services[]" value="medical_tourism" <?php echo in_array('medical_tourism', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISA / FLIGHT BOOKING Section -->
            <div id="visa-flight-section" class="services-section" style="display: <?php echo in_array('visa_flight', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="fas fa-plane"></i> VISA / FLIGHT BOOKING
                    <button type="button" class="btn btn-sm btn-primary" onclick="addVisaRow()">
                        <i class="fas fa-plus"></i> Add Visa
                    </button>
                </h5>
                                
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
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="visa[<?php echo $index; ?>][sector]" value="<?php echo htmlspecialchars($visa['sector'] ?? ''); ?>" placeholder="Sector">
                                        <input type="hidden" name="visa[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="visa[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($visa['supplier'] ?? ''); ?>" placeholder="Supplier"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="visa[<?php echo $index; ?>][travel_date]" value="<?php echo $visa['travel_date'] ?? ''; ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm visa-passengers" name="visa[<?php echo $index; ?>][passengers]" data-row="<?php echo $index; ?>" value="<?php echo $visa['passengers'] ?? '0'; ?>" onchange="calculateVisaTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm visa-rate" name="visa[<?php echo $index; ?>][rate_per_person]" data-row="<?php echo $index; ?>" value="<?php echo $visa['rate_per_person'] ?? '0'; ?>" onchange="calculateVisaTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="visa[<?php echo $index; ?>][roe]" value="<?php echo $visa['roe'] ?? '1'; ?>" step="0.01"></td>
                                    <td><input type="text" class="form-control form-control-sm visa-total" name="visa[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $visa['total'] ?? '0.00'; ?>" readonly></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="visa[0][sector]" placeholder="Sector">
                                        <input type="hidden" name="visa[0][idx]" value="0">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="visa[0][supplier]" placeholder="Supplier"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="visa[0][travel_date]"></td>
                                    <td><input type="number" class="form-control form-control-sm visa-passengers" name="visa[0][passengers]" data-row="0" value="0" onchange="calculateVisaTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm visa-rate" name="visa[0][rate_per_person]" data-row="0" value="0" onchange="calculateVisaTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="visa[0][roe]" value="1" step="0.01"></td>
                                    <td><input type="text" class="form-control form-control-sm visa-total" name="visa[0][total]" data-row="0" readonly></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-right"><strong>TOTAL VISA COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="visa-grand-total" readonly></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Travel Details -->
                <div class="info-card" style="border: none; padding:0;">
                    <h6><i class="fas fa-calendar-alt"></i> Travel Details</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>TRAVEL PERIOD</th>
                                    <th>DATE</th>
                                    <th>CITY</th>
                                    <th>FLIGHT</th>
                                    <th>NIGHTS/DAYS</th>
                                    <th>Flight Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ARRIVAL</strong></td>
                                    <td><input type="date" class="form-control form-control-sm" name="arrival_date" value="<?php echo $cost_data['arrival_date'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="arrival_city" placeholder="City" value="<?php echo $cost_data['arrival_city'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="arrival_flight" placeholder="Flight No" value="<?php echo $cost_data['arrival_flight'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="arrival_nights_days">
                                            <option value="">Select</option>
                                            <option value="Day" <?php echo (isset($cost_data['arrival_nights_days']) && $cost_data['arrival_nights_days'] == 'Day') ? 'selected' : ''; ?>>Day Flight</option>
                                            <option value="Night" <?php echo (isset($cost_data['arrival_nights_days']) && $cost_data['arrival_nights_days'] == 'Night') ? 'selected' : ''; ?>>Night Flight</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="arrival_connection">
                                            <option value="">Select</option>
                                            <option value="Direct" <?php echo (isset($cost_data['arrival_connection']) && $cost_data['arrival_connection'] == 'Direct') ? 'selected' : ''; ?>>Direct</option>
                                            <option value="Connection" <?php echo (isset($cost_data['arrival_connection']) && $cost_data['arrival_connection'] == 'Connection') ? 'selected' : ''; ?>>Connection</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('arrival')"><i class="fas fa-plus"></i></button></td>
                                </tr>
                                <tr id="arrival-connecting" style="display: <?php echo isset($cost_data['arrival_connecting_date']) ? 'table-row' : 'none'; ?>">
                                    <td><strong>ARRIVAL (Connecting)</strong></td>
                                    <td><input type="date" class="form-control form-control-sm" name="arrival_connecting_date" value="<?php echo $cost_data['arrival_connecting_date'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="arrival_connecting_city" placeholder="City" value="<?php echo $cost_data['arrival_connecting_city'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="arrival_connecting_flight" placeholder="Flight No" value="<?php echo $cost_data['arrival_connecting_flight'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="arrival_connecting_nights_days">
                                            <option value="">Select</option>
                                            <option value="Day" <?php echo (isset($cost_data['arrival_connecting_nights_days']) && $cost_data['arrival_connecting_nights_days'] == 'Day') ? 'selected' : ''; ?>>Day Flight</option>
                                            <option value="Night" <?php echo (isset($cost_data['arrival_connecting_nights_days']) && $cost_data['arrival_connecting_nights_days'] == 'Night') ? 'selected' : ''; ?>>Night Flight</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="arrival_connecting_type">
                                            <option value="">Select</option>
                                            <option value="Direct" <?php echo (isset($cost_data['arrival_connecting_type']) && $cost_data['arrival_connecting_type'] == 'Direct') ? 'selected' : ''; ?>>Direct</option>
                                            <option value="Connection" <?php echo (isset($cost_data['arrival_connecting_type']) && $cost_data['arrival_connecting_type'] == 'Connection') ? 'selected' : ''; ?>>Connection</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('arrival')"><i class="fas fa-minus"></i></button></td>
                                </tr>
                                <tr>
                                    <td><strong>DEPARTURE</strong></td>
                                    <td><input type="date" class="form-control form-control-sm" name="departure_date" value="<?php echo $cost_data['departure_date'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="departure_city" placeholder="City" value="<?php echo $cost_data['departure_city'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="departure_flight" placeholder="Flight No" value="<?php echo $cost_data['departure_flight'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="departure_nights_days">
                                            <option value="">Select</option>
                                            <option value="Day" <?php echo (isset($cost_data['departure_nights_days']) && $cost_data['departure_nights_days'] == 'Day') ? 'selected' : ''; ?>>Day Flight</option>
                                            <option value="Night" <?php echo (isset($cost_data['departure_nights_days']) && $cost_data['departure_nights_days'] == 'Night') ? 'selected' : ''; ?>>Night Flight</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="departure_connection">
                                            <option value="">Select</option>
                                            <option value="Direct" <?php echo (isset($cost_data['departure_connection']) && $cost_data['departure_connection'] == 'Direct') ? 'selected' : ''; ?>>Direct</option>
                                            <option value="Connection" <?php echo (isset($cost_data['departure_connection']) && $cost_data['departure_connection'] == 'Connection') ? 'selected' : ''; ?>>Connection</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('departure')"><i class="fas fa-plus"></i></button></td>
                                </tr>
                                <tr id="departure-connecting" style="display: <?php echo !empty($cost_data['departure_connecting_date']) ? 'table-row' : 'none'; ?>">
                                    <td><strong>DEPARTURE (Connecting)</strong></td>
                                    <td><input type="date" class="form-control form-control-sm" name="departure_connecting_date" value="<?php echo $cost_data['departure_connecting_date'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="departure_connecting_city" placeholder="City" value="<?php echo $cost_data['departure_connecting_city'] ?? ''; ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="departure_connecting_flight" placeholder="Flight No" value="<?php echo $cost_data['departure_connecting_flight'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="departure_connecting_nights_days">
                                            <option value="">Select</option>
                                            <option value="Day" <?php echo (isset($cost_data['departure_connecting_nights_days']) && $cost_data['departure_connecting_nights_days'] == 'Day') ? 'selected' : ''; ?>>Day Flight</option>
                                            <option value="Night" <?php echo (isset($cost_data['departure_connecting_nights_days']) && $cost_data['departure_connecting_nights_days'] == 'Night') ? 'selected' : ''; ?>>Night Flight</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="departure_connecting_type">
                                            <option value="">Select</option>
                                            <option value="Direct" <?php echo (isset($cost_data['departure_connecting_type']) && $cost_data['departure_connecting_type'] == 'Direct') ? 'selected' : ''; ?>>Direct</option>
                                            <option value="Connection" <?php echo (isset($cost_data['departure_connecting_type']) && $cost_data['departure_connecting_type'] == 'Connection') ? 'selected' : ''; ?>>Connection</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('departure')"><i class="fas fa-minus"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- ACCOMMODATION Section -->
            <div id="accommodation-section" class="services-section" style="display: <?php echo in_array('accommodation', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-bed"></i> ACCOMMODATION
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAccommodationRow()"><i class="fa fa-plus"></i> Add Accommodation</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>DESTINATION</th>
                                <th>HOTEL</th>
                                <th>CHECK-IN</th>
                                <th>CHECK-OUT</th>
                                <th>ROOM TYPE</th>
                                <th>ROOMS NO</th>
                                <th>MEAL PLAN</th>
                                <th>ROOMS RATE</th>
                                <th>EXTRA ADULT NO</th>
                                <th>EXTRA ADULT RATE</th>
                                <th>EXTRA CHILD NO</th>
                                <th>EXTRA CHILD RATE</th>
                                <th>CHILD NO BED NO</th>
                                <th>CHILD NO BED RATE</th>
                                <th>NIGHTS</th>
                                
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="accommodation-tbody">
                            <?php if (!empty($accommodation_data) && is_array($accommodation_data)): ?>
                                <?php foreach ($accommodation_data as $index => $accom): ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][destination]" onchange="updateHotels(this, <?php echo $index; ?>, <?php echo $accom['hotel']; ?> )">
                                            <option value="">Select Destination</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($accommodations, 0);
                                                while($dest = mysqli_fetch_assoc($accommodations)):
                                                    if (in_array($dest['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $dest['destination'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($dest['destination']); ?>" 
                                                    <?php echo ($accom['destination'] == $dest['destination']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($dest['destination']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="accommodation[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][hotel]" onchange="updateRoomTypes(this, <?php echo $index; ?>, '<?php echo $accom['room_type']; ?>')">
                                            <option value="">Select Hotel</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($accommodations, 0);
                                                while($dest = mysqli_fetch_assoc($accommodations)):
                                                    if (in_array($dest['hotel_name'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $dest['hotel_name'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($dest['hotel_name']); ?>" 
                                                    <?php echo ($accom['hotel'] == $dest['hotel_name']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($dest['hotel_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][check_in]" value="<?php echo $accom['check_in'] ?? ''; ?>"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][check_out]" value="<?php echo $accom['check_out'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][room_type]" onchange="updateRates(this, <?php echo $index; ?>)">
                                            <option value="">Select Room Type</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($accommodations, 0);
                                                while($dest = mysqli_fetch_assoc($accommodations)):
                                                    if (in_array($dest['room_category'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $dest['room_category'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($dest['room_category']); ?>" 
                                                    <?php echo ($accom['room_type'] == $dest['room_category']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($dest['room_category']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[<?php echo $index; ?>][rooms_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['rooms_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][meal_plan]" onchange="updateRates(this, <?php echo $index; ?>)">
                                            <option value="">Select Meal Plan</option>
                                            <option value="cp" <?php echo ($accom['meal_plan'] == 'cp') ? 'selected' : ''; ?>>CP</option>
                                            <option value="map" <?php echo ($accom['meal_plan'] == 'map') ? 'selected' : ''; ?>>MAP</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[<?php echo $index; ?>][rooms_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['rooms_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[<?php echo $index; ?>][extra_adult_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_adult_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[<?php echo $index; ?>][extra_adult_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_adult_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[<?php echo $index; ?>][extra_child_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_child_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[<?php echo $index; ?>][extra_child_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_child_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[<?php echo $index; ?>][child_no_bed_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['child_no_bed_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[<?php echo $index; ?>][child_no_bed_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['child_no_bed_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[<?php echo $index; ?>][nights]" data-row="<?php echo $index; ?>" value="<?php echo $accom['nights'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    
                                    <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $accom['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][destination]" onchange="updateHotels(this, 0)">
                                            <option value="">Select Destination</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($accommodations, 0);
                                                while($dest = mysqli_fetch_assoc($accommodations)):
                                                    if (in_array($dest['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $dest['destination'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($dest['destination']); ?>" 
                                                >
                                                    <?php echo htmlspecialchars($dest['destination']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="accommodation[0][idx]" value="0">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0]" onchange="updateRoomTypes(this, 0)">
                                            <option value="">Select Hotel</option>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_in]"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_out]"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][room_type]" onchange="updateRates(this, 0)">
                                            <option value="">Select Room Type</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[0][rooms_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][meal_plan]" onchange="updateRates(this, 0)">
                                            <option value="">Select Meal Plan</option>
                                            <option value="cp">CP</option>
                                            <option value="map">MAP</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[0][rooms_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[0][extra_adult_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[0][extra_adult_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[0][extra_child_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[0][extra_child_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[0][child_no_bed_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[0][child_no_bed_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[0][nights]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    
                                    <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="15" class="text-right"><strong>TOTAL ACCOMMODATION COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="accommodation-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TRANSPORTATION Section -->
            <div id="transportation-section" class="services-section" style="display: <?php echo in_array('transportation', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-car"></i> INTERNAL TRANSPORTATION
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTransportationRow()"><i class="fa fa-plus"></i> Add Transportation</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>SUPPLIER</th>
                                <th>CAR TYPE</th>
                                <th>DAILY RENT</th>
                                <th>DAYS</th>
                                <th>KM</th>
                                <th>EXTRA KM</th>
                                <th>PRICE/KM</th>
                                <th>TOLL/PARKING</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="transportation-tbody">
                            <?php if (!empty($transportation_data) && is_array($transportation_data)): ?>
                                <?php foreach ($transportation_data as $index => $trans): ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[<?php echo $index; ?>][supplier]" onchange="updateTransportVehicles(this, <?php echo $index; ?>, '<?php echo $trans['car_type'] ?? ''; ?>')">
                                            <option value="">Select Supplier</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($transport_details, 0);
                                                while($transport = mysqli_fetch_assoc($transport_details)):
                                                    if (in_array($transport['company_name'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $transport['company_name'];
                                            ?>
                                                <option value="<?php echo htmlspecialchars($transport['company_name']); ?>" <?php echo ((isset($trans['supplier']) ? $trans['supplier'] : '') == $transport['company_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($transport['company_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="transportation[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[<?php echo $index; ?>][car_type]" onchange="updateTransportRates(this, <?php echo $index; ?>)" data-selected="<?php echo htmlspecialchars($trans['car_type'] ?? ''); ?>">
                                            <option value="">Select Car Type</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[<?php echo $index; ?>][daily_rent]" data-row="<?php echo $index; ?>" value="<?php echo $trans['daily_rent'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[<?php echo $index; ?>][days]" data-row="<?php echo $index; ?>" value="<?php echo $trans['days'] ?? '2'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="2"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[<?php echo $index; ?>][km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[<?php echo $index; ?>][extra_km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['extra_km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[<?php echo $index; ?>][price_per_km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['price_per_km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[<?php echo $index; ?>][toll]" data-row="<?php echo $index; ?>" value="<?php echo $trans['toll'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $trans['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[0][supplier]" onchange="updateTransportVehicles(this, 0)">
                                            <option value="">Select Supplier</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($transport_details, 0);
                                                while($transport = mysqli_fetch_assoc($transport_details)):
                                                    if (in_array($transport['company_name'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $transport['company_name'];
                                            ?>
                                            
                                                <option value="<?php echo htmlspecialchars($transport['company_name']); ?>"><?php echo htmlspecialchars($transport['company_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="transportation[0][idx]" value="0">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[0][car_type]" onchange="updateTransportRates(this, 0)" disabled>
                                            <option value="">Select Car Type</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[0][daily_rent]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[0][days]" data-row="0" value="2" onchange="calculateTransportationTotal(0)" placeholder="2"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[0][km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[0][extra_km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[0][price_per_km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[0][toll]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-right"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="transportation-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- CRUISE HIRE Section -->
            <div id="cruise-hire-section" class="services-section" style="display: <?php echo in_array('cruise_hire', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-ship"></i> CRUISE HIRE
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCruiseRow()"><i class="fa fa-plus"></i> Add Cruise</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. NO</th>
                                <th>SUPPLIER</th>
                                <th>TYPE OF BOAT</th>
                                <th>CRUISE TYPE</th>
                                <th>CHECK-IN</th>
                                <th>CHECK-OUT</th>
                                <th>RATE</th>
                                <th>EXTRA</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="cruise-tbody">
                            <?php if (!empty($cruise_data) && is_array($cruise_data)): ?>
                                <?php foreach ($cruise_data as $index => $cruise): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($cruise['supplier'] ?? ''); ?>" placeholder="Supplier Name">
                                        <input type="hidden" name="cruise[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][boat_type]">
                                            <option value="">Select Boat Type</option>
                                           
                                            <option value="Sailboat / Sailing Yacht" <?php echo ($cruise['boat_type'] == 'Sailboat / Sailing Yacht') ? 'selected' : ''; ?> >Sailboat / Sailing Yacht</option>
                                            <option value="Pontoon Boat" <?php echo ($cruise['boat_type'] == 'Pontoon Boat') ? 'selected' : ''; ?> >Pontoon Boat</option>
                                            <option value="Bowrider" <?php echo ($cruise['boat_type'] == 'Bowrider') ? 'selected' : ''; ?> >Bowrider</option>
                                            <option value="Cabin Cruiser" <?php echo ($cruise['boat_type'] == 'Cabin Cruiser') ? 'selected' : ''; ?> >Cabin Cruiser</option>
                                            <option value="Deck Boat" <?php echo ($cruise['boat_type'] == 'Deck Boat') ? 'selected' : ''; ?> >Deck Boat</option>
                                            <option value="Jet Boat" <?php echo ($cruise['boat_type'] == 'Jet Boat') ? 'selected' : ''; ?> >Jet Boat</option>
                                            <option value="Personal Watercraft (PWC)" <?php echo ($cruise['boat_type'] == 'Personal Watercraft (PWC)') ? 'selected' : ''; ?> >Personal Watercraft (PWC)</option>
                                            <option value="Houseboat" <?php echo ($cruise['boat_type'] == 'Houseboat') ? 'selected' : ''; ?> >Houseboat</option>
                                            <option value="Bass Boat" <?php echo ($cruise['boat_type'] == 'Bass Boat') ? 'selected' : ''; ?> >Bass Boat</option>
                                            <option value="Dinghy" <?php echo ($cruise['boat_type'] == 'Dinghy') ? 'selected' : ''; ?> >Dinghy</option>
                                            <option value="Canoe" <?php echo ($cruise['boat_type'] == 'Canoe') ? 'selected' : ''; ?> >Canoe</option>
                                            <option value="Kayak" <?php echo ($cruise['boat_type'] == 'Kayak') ? 'selected' : ''; ?> >Kayak</option>
                                            <option value="Rowboat" <?php echo ($cruise['boat_type'] == 'Rowboat') ? 'selected' : ''; ?> >Rowboat</option>
                                            <option value="Inflatable Boat" <?php echo ($cruise['boat_type'] == 'Inflatable Boat') ? 'selected' : ''; ?> >Inflatable Boat</option>
                                            <option value="Sloop" <?php echo ($cruise['boat_type'] == 'Sloop') ? 'selected' : ''; ?> >Sloop</option>
                                            <option value="Cutter" <?php echo ($cruise['boat_type'] == 'Cutter') ? 'selected' : ''; ?> >Cutter</option>
                                            <option value="Ketch" <?php echo ($cruise['boat_type'] == 'Ketch') ? 'selected' : ''; ?> >Ketch</option>
                                            <option value="Yawl" <?php echo ($cruise['boat_type'] == 'Yawl') ? 'selected' : ''; ?> >Yawl</option>
                                            <option value="Catamaran" <?php echo ($cruise['boat_type'] == 'Catamaran') ? 'selected' : ''; ?> >Catamaran</option>
                                            <option value="Trimaran" <?php echo ($cruise['boat_type'] == 'Trimaran') ? 'selected' : ''; ?> >Trimaran</option>
                                            <option value="Fishing Trawler" <?php echo ($cruise['boat_type'] == 'Fishing Trawler') ? 'selected' : ''; ?> >Fishing Trawler</option>
                                            <option value="Cargo Ship / Freighter" <?php echo ($cruise['boat_type'] == 'Cargo Ship / Freighter') ? 'selected' : ''; ?> >Cargo Ship / Freighter</option>
                                            <option value="Tugboat" <?php echo ($cruise['boat_type'] == 'Tugboat') ? 'selected' : ''; ?> >Tugboat</option>
                                            <option value="Ferry" <?php echo ($cruise['boat_type'] == 'Ferry') ? 'selected' : ''; ?> >Ferry</option>
                                            <option value="Pilot Boat" <?php echo ($cruise['boat_type'] == 'Pilot Boat') ? 'selected' : ''; ?> >Pilot Boat</option>
                                            <option value="Barge" <?php echo ($cruise['boat_type'] == 'Barge') ? 'selected' : ''; ?> >Barge</option>
                                            <option value="Oil Tanker / Gas Carrier" <?php echo ($cruise['boat_type'] == 'Oil Tanker / Gas Carrier') ? 'selected' : ''; ?> >Oil Tanker / Gas Carrier</option>
                                            <option value="Dredger" <?php echo ($cruise['boat_type'] == 'Dredger') ? 'selected' : ''; ?> >Dredger</option>
                                            <option value="Fireboat" <?php echo ($cruise['boat_type'] == 'Fireboat') ? 'selected' : ''; ?> >Fireboat</option>
                                            <option value="Research Vessel" <?php echo ($cruise['boat_type'] == 'Research Vessel') ? 'selected' : ''; ?> >Research Vessel</option>
                                            <option value="Yacht" <?php echo ($cruise['boat_type'] == 'Yacht') ? 'selected' : ''; ?> >Yacht</option>
                                            <option value="Superyacht / Megayacht" <?php echo ($cruise['boat_type'] == 'Superyacht / Megayacht') ? 'selected' : ''; ?> >Superyacht / Megayacht</option>
                                            <option value="Cruise Ship" <?php echo ($cruise['boat_type'] == 'Cruise Ship') ? 'selected' : ''; ?> >Cruise Ship</option>
                                            <option value="Expedition Yacht" <?php echo ($cruise['boat_type'] == 'Expedition Yacht') ? 'selected' : ''; ?> >Expedition Yacht</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][cruise_type]">
                                            <option value="">Select Cruise Type</option>
                                            <option value="Ocean Cruise" <?php echo ($cruise['cruise_type'] == 'Ocean Cruise') ? 'selected' : ''; ?>>Ocean Cruise</option>
                                            <option value="River Cruise" <?php echo ($cruise['cruise_type'] == 'River Cruise') ? 'selected' : ''; ?>>River Cruise</option>
                                            <option value="Expedition Cruise" <?php echo ($cruise['cruise_type'] == 'Expedition Cruise') ? 'selected' : ''; ?>>Expedition Cruise</option>
                                            <option value="Coastal Cruise" <?php echo ($cruise['cruise_type'] == 'Coastal Cruise') ? 'selected' : ''; ?>>Coastal Cruise</option>
                                            <option value="Mini / Weekend Cruise" <?php echo ($cruise['cruise_type'] == 'Mini / Weekend Cruise') ? 'selected' : ''; ?>>Mini / Weekend Cruise</option>
                                            <option value="Short Cruise" <?php echo ($cruise['cruise_type'] == 'Short Cruise') ? 'selected' : ''; ?>>Short Cruise</option>
                                            <option value="Weeklong Cruise" <?php echo ($cruise['cruise_type'] == 'Weeklong Cruise') ? 'selected' : ''; ?>>Weeklong Cruise</option>
                                            <option value="Extended Cruise" <?php echo ($cruise['cruise_type'] == 'Extended Cruise') ? 'selected' : ''; ?>>Extended Cruise</option>
                                            <option value="World Cruise" <?php echo ($cruise['cruise_type'] == 'World Cruise') ? 'selected' : ''; ?>>World Cruise</option>
                                            <option value="Luxury Cruise" <?php echo ($cruise['cruise_type'] == 'Luxury Cruise') ? 'selected' : ''; ?>>Luxury Cruise</option>
                                            <option value="Family Cruise" <?php echo ($cruise['cruise_type'] == 'Family Cruise') ? 'selected' : ''; ?>>Family Cruise</option>
                                            <option value="Adventure Cruise" <?php echo ($cruise['cruise_type'] == 'Adventure Cruise') ? 'selected' : ''; ?>>Adventure Cruise</option>
                                            <option value="Wellness Cruise" <?php echo ($cruise['cruise_type'] == 'Wellness Cruise') ? 'selected' : ''; ?>>Wellness Cruise</option>
                                            <option value="Romantic / Honeymoon Cruise" <?php echo ($cruise['cruise_type'] == 'Romantic / Honeymoon Cruise') ? 'selected' : ''; ?>>Romantic / Honeymoon Cruise</option>
                                            <option value="Singles Cruise" <?php echo ($cruise['cruise_type'] == 'Singles Cruise') ? 'selected' : ''; ?>>Singles Cruise</option>
                                            <option value="Themed Cruise" <?php echo ($cruise['cruise_type'] == 'Themed Cruise') ? 'selected' : ''; ?>>Themed Cruise</option>
                                            <option value="Repositioning Cruise" <?php echo ($cruise['cruise_type'] == 'Repositioning Cruise') ? 'selected' : ''; ?>>Repositioning Cruise</option>
                                            <option value="Mega Cruise" <?php echo ($cruise['cruise_type'] == 'Mega Cruise') ? 'selected' : ''; ?>>Mega Cruise</option>
                                            <option value="Small Ship Cruise" <?php echo ($cruise['cruise_type'] == 'Small Shiplf') ? 'selected' : ''; ?>>Small Ship Cruise</option>
                                            <option value="Yacht Cruise" <?php echo ($cruise['cruise_type'] == 'Yacht Cruise') ? 'selected' : ''; ?>>Yacht Cruise</option>
                                            <option value="Sailing Cruise" <?php echo ($cruise['cruise_type'] == 'Sailing Cruise') ? 'selected' : ''; ?>>Sailing Cruise</option>
                                            <option value="Barge Cruise" <?php echo ($cruise['cruise_type'] == 'Barge Cruise') ? 'selected' : ''; ?>>Barge Cruise</option>
                                        </select>
                                    </td>
                                    <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][check_in]" value="<?php echo $cruise['check_in'] ?? ''; ?>"></td>
                                    <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][check_out]" value="<?php echo $cruise['check_out'] ?? ''; ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise[<?php echo $index; ?>][rate]" data-row="<?php echo $index; ?>" value="<?php echo $cruise['rate'] ?? '0'; ?>" onchange="calculateCruiseTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise[<?php echo $index; ?>][extra]" data-row="<?php echo $index; ?>" value="<?php echo $cruise['extra'] ?? '0'; ?>" onchange="calculateCruiseTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $cruise['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="cruise[0][supplier]" placeholder="Supplier Name">
                                        <input type="hidden" name="cruise[0][idx]" value="0">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[0][boat_type]">
                                            <option value="">Select Boat Type</option>
                                            <option value="Yacht">Yacht</option>
                                            <option value="Catamaran">Catamaran</option>
                                            <option value="Speedboat">Speedboat</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[0][cruise_type]">
                                            <option value="">Select Cruise Type</option>
                                            <option value="Half Day">Half Day</option>
                                            <option value="Full Day">Full Day</option>
                                            <option value="Sunset Cruise">Sunset Cruise</option>
                                        </select>
                                    </td>
                                    <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[0][check_in]"></td>
                                    <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[0][check_out]"></td>
                                    <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise[0][rate]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise[0][extra]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-right"><strong>TOTAL CRUISE COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="cruise-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- AGENT PACKAGE SERVICE Section -->
            <div id="agent-package-section" class="services-section" style="display: <?php echo in_array('agent_package', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-briefcase"></i> AGENT PACKAGE SERVICE
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAgentPackageRow()"><i class="fa fa-plus"></i> Add Package</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>DESTINATION</th>
                                <th>AGENT/SUPPLIER</th>
                                <th>START DATE</th>
                                <th>END DATE</th>
                                <th>ADULTS</th>
                                <th>PRICE/ADULT</th>
                                <th>CHILDREN</th>
                                <th>PRICE/CHILD</th>
                                <th>INFANTS</th>
                                <th>PRICE/INFANT</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="agent-package-tbody">
                            <?php 
                            $agent_package_data = json_decode($cost_data['agent_package_data'] ?? '[]', true);
                            if (!empty($agent_package_data) && is_array($agent_package_data)): 
                            ?>
                                <?php foreach ($agent_package_data as $index => $package): ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][destination]" onchange="updateAgentSupplier(this, <?php echo $index; ?>, '<?php echo $package['agent_supplier']; ?>' )">
                                            <option value="">Select Destination</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($agent_details, 0);
                                                while($agent = mysqli_fetch_assoc($agent_details)):
                                                    if (in_array($agent['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $agent['destination'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($agent['destination']); ?>" 
                                                    <?php echo ($package['destination'] == $agent['destination']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($agent['destination']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="agent_package[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][agent_supplier]" style="width: 120px;">
                                            <option value="">Select Agent/Supplier</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($agent_details, 0);
                                                while($agent = mysqli_fetch_assoc($agent_details)):
                                                    if (in_array($agent['supplier'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $agent['supplier'];
                                            ?>

                                            <option 
                                                value="<?php echo htmlspecialchars($agent['supplier']); ?>" 
                                                <?php echo ($package['agent_supplier'] == $agent['supplier']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($agent['supplier']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][start_date]" value="<?php echo $package['start_date'] ?? ''; ?>"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][end_date]" value="<?php echo $package['end_date'] ?? ''; ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[<?php echo $index; ?>][adult_count]" data-row="<?php echo $index; ?>" value="<?php echo $package['adult_count'] ?? $cost_data['adults_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[<?php echo $index; ?>][adult_price]" data-row="<?php echo $index; ?>" value="<?php echo $package['adult_price'] ?? 0; ?>" style="width: 100px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[<?php echo $index; ?>][child_count]" data-row="<?php echo $index; ?>" value="<?php echo $package['child_count'] ?? $cost_data['children_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[<?php echo $index; ?>][child_price]" data-row="<?php echo $index; ?>" value="<?php echo $package['child_price'] ?? 0; ?>" style="width: 100px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[<?php echo $index; ?>][infant_count]" data-row="<?php echo $index; ?>" value="<?php echo $package['infant_count'] ?? $cost_data['infants_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[<?php echo $index; ?>][infant_price]" data-row="<?php echo $index; ?>" value="<?php echo $package['infant_price'] ?? 0; ?>" style="width: 100px;" onchange="calculateAgentPackageTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $package['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="agent_package[0][destination]" onchange="updateAgentSupplier(this, 0)">
                                            <option value="">Select Destination</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($agent_details, 0);
                                                while($agent = mysqli_fetch_assoc($agent_details)):
                                                    if (in_array($agent['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $agent['destination'];
                                            ?>
                                                <option value="<?php echo htmlspecialchars($agent['destination']); ?>" ><?php echo htmlspecialchars($agent['destination']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="agent_package[0][idx]" value="0">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="agent_package[0][agent_supplier]" style="width: 120px;" disabled>
                                            <option value="">Select Agent/Supplier</option>
                                           
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="agent_package[0][start_date]" value="<?php echo $cost_data['travel_start_date'] ?? ''; ?>"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="agent_package[0][end_date]" value="<?php echo $cost_data['travel_end_date'] ?? ''; ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[0][adult_count]" data-row="0" value="<?php echo $cost_data['adults_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[0][adult_price]" data-row="0" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[0][child_count]" data-row="0" value="<?php echo $cost_data['children_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[0][child_price]" data-row="0" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[0][infant_count]" data-row="0" value="<?php echo $cost_data['infants_count'] ?? 0; ?>" style="width: 70px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[0][infant_price]" data-row="0" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(0)"></td>
                                    <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="10" class="text-right"><strong>TOTAL AGENT PACKAGE COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="agent-package-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div id="medical-tourism-section" class="services-section" style="display: <?php echo in_array('medical_tourism', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-hospital-o"></i> MEDICAL TOURISM
                    <button type="button" class="btn btn-sm btn-primary" onclick="addMedicalTourismRow()"><i class="fa fa-plus"></i> Add Package</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>PLACE</th>
                                <th>TREATMENT DATE</th>
                                <th>HOSPITAL NAME</th>
                                <th>TREATMENT TYPE</th>
                                <th>OP/IP</th>
                                <th>NET</th>
                                <th>TDS</th>
                                <th>OTHER EXPENSES</th>
                                <th>GST</th>                                
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                                              <tbody id="medical-tourism-tbody">
                            <?php 
                            $medical_tourism_data = json_decode($cost_data['medical_tourism_data'] ?? '[]', true);
                            
                            
                            if (!empty($medical_tourism_data) && is_array($medical_tourism_data)): 
                            ?>
                                <?php foreach ($medical_tourism_data as $index => $package): ?>
                                <tr>
                                    <td>
                                        
                                        <select class="form-control form-control-sm" name="medical_tourisms[<?php echo $index; ?>][place]" onchange="updateHospitals(this, <?php echo $index; ?>, '<?php echo $package['hospital'] ?? ''; ?>')" style="width: 120px;">
                                            <option value="">Select Place</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($hospital_details, 0);
                                                while($hospital = mysqli_fetch_assoc($hospital_details)):
                                                    if (in_array($hospital['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $hospital['destination'];
                                            ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($hospital['destination']); ?>" 
                                                    <?php echo ($package['place'] == $hospital['destination']) ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($hospital['destination']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="medical_tourisms[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="medical_tourisms[<?php echo $index; ?>][treatment_date]" value="<?php echo $package['treatment_date'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[<?php echo $index; ?>][hospital]" style="width: 120px;" data-selected="<?php echo htmlspecialchars($package['hospital'] ?? ''); ?>">
                                            <option value="">Select Hospital</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[<?php echo $index; ?>][treatment_type]">
                                           <option value="">Select type</option>
                                           <option 
                                                value="Type 1"
                                                <?php echo ($package['treatment_type'] == "Type 1") ? 'selected' : ''; ?>
                                           >Type 1</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[<?php echo $index; ?>][op_ip]">
                                           <option value="">Select type</option>
                                           <option value="IP" <?php echo $package['op_ip'] == 'IP' ? 'selected':'' ?>  >IP</option>
                                           <option value="OP" <?php echo $package['op_ip'] == 'OP' ? 'selected':'' ?>   >OP</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-net" name="medical_tourisms[<?php echo $index; ?>][net]" data-row="<?php echo $index; ?>" value="<?php echo $package['net'] ?? 0; ?>" style="width: 100px;" onchange="calculateMedicalTourismTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-tds" name="medical_tourisms[<?php echo $index; ?>][tds]" data-row="<?php echo $index; ?>" value="<?php echo $package['tds'] ?? 0; ?>" style="width: 70px;" onchange="calculateMedicalTourismTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-other_expenses" name="medical_tourisms[<?php echo $index; ?>][other_expenses]" data-row="<?php echo $index; ?>" value="<?php echo $package['other_expenses'] ?? 0; ?>" style="width: 100px;" onchange="calculateMedicalTourismTotal(<?php echo $index; ?>)"></td>
                                    <td>18%</td>
                                    <td><input type="text" class="form-control form-control-sm medical-tourism-total" name="medical_tourisms[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $package['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[0][place]" onchange="updateHospitals(this, 0)" style="width: 120px;">
                                            <option value="">Select Place</option>
                                            <?php
                                                $uniqueValues = [];
                                                mysqli_data_seek($hospital_details, 0);
                                                while($hospital = mysqli_fetch_assoc($hospital_details)):
                                                    if (in_array($hospital['destination'], $uniqueValues)) {
                                                        continue; // skip duplicate
                                                    }
                                                    $uniqueValues[] = $hospital['destination'];
                                            ?>
                                                <option value="<?php echo htmlspecialchars($hospital['destination']); ?>"><?php echo htmlspecialchars($hospital['destination']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="hidden" name="medical_tourisms[0][idx]" value="0">
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="medical_tourisms[0][treatment_date]" value="<?php echo $cost_data['treatment_date'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[0][hospital]" style="width: 120px;" disabled>
                                            <option value="">Select Hospital</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[0][treatment_type]">
                                           <option value="">Select type</option>
                                           <option value="Type 1" >Type 1</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="medical_tourisms[0][op_ip]">
                                           <option value="">Select type</option>
                                           <option value="IP" >IP</option>
                                           <option value="OP" >OP</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-net" name="medical_tourisms[0][net]" data-row="0" value="0" style="width: 100px;" onchange="calculateMedicalTourismTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-tds" name="medical_tourisms[0][tds]" data-row="0" value="0" style="width: 70px;" onchange="calculateMedicalTourismTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm medical-tourism-other_expenses" name="medical_tourisms[0][other_expenses]" data-row="0" value="0" style="width: 100px;" onchange="calculateMedicalTourismTotal(0)"></td>
                                    <td>18%</td>
                                    <td><input type="text" class="form-control form-control-sm medical-tourism-total" name="medical_tourisms[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right"><strong>TOTAL AGENT PACKAGE COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="medical-tourism-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- EXTRAS/MISCELLANEOUS Section -->
            <div id="extras-section" class="services-section" style="display: <?php echo in_array('extras', $selected_services) ? 'block' : 'none'; ?>;">
                <h5>
                    <i class="icon-copy fa fa-plus"></i> EXTRAS/MISCELLANEOUS
                    <button type="button" class="btn btn-sm btn-primary" onclick="addExtrasRow()"><i class="fa fa-plus"></i> Add Service</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>SUPPLIER</th>
                                <th>TYPE OF SERVICE</th>
                                <th>AMOUNT</th>
                                <th>EXTRAS</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="extras-tbody">
                            <?php if (!empty($extras_data) && is_array($extras_data)): ?>
                                <?php foreach ($extras_data as $index => $extra): ?>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="extras[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($extra['supplier'] ?? ''); ?>" placeholder="Supplier Name">
                                        <input type="hidden" name="extras[<?php echo $index; ?>][idx]" value="<?php echo $index; ?>">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="extras[<?php echo $index; ?>][service_type]" value="<?php echo htmlspecialchars($extra['service_type'] ?? ''); ?>" placeholder="Type of Service"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[<?php echo $index; ?>][amount]" data-row="<?php echo $index; ?>" value="<?php echo $extra['amount'] ?? '0'; ?>" onchange="calculateExtrasTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[<?php echo $index; ?>][extras]" data-row="<?php echo $index; ?>" value="<?php echo $extra['extras'] ?? '0'; ?>" onchange="calculateExtrasTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm extras-total" name="extras[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $extra['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="extras[0][supplier]" placeholder="Supplier Name">
                                        <input type="hidden" name="extras[0][idx]" value="0">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="extras[0][service_type]" placeholder="Type of Service"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[0][amount]" data-row="0" value="0" onchange="calculateExtrasTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[0][extras]" data-row="0" value="0" onchange="calculateExtrasTotal(0)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm extras-total" name="extras[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>TOTAL EXTRAS COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="extras-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Continue with all other sections following the same pattern... -->
            <!-- For brevity, I'm showing the structure. You would continue with accommodation, transportation, etc. -->

            <!-- Payment and Summary Section -->
            <div class="payment-summary-container">
                <!-- Payment Details -->
                <div class="info-card" style="flex: 1; min-width: 400px;">
                    <h5><i class="fas fa-credit-card"></i> Payment Details</h5>
                   <div class="info-row">
                            <span class="info-label">Date:</span>
                            <input type="date" class="form-control form-control-sm" name="payment_date" value="<?php echo $payment_data['date'] ?? ''; ?>">
                        </div>
                        <div class="info-row">
                            <span class="info-label">Bank:</span>
                            <select class="form-control form-control-sm" name="payment_bank">
                                <option value="">Select Bank</option>
                                <option value="HDFC BANK" <?php echo ($payment_data['bank'] == 'HDFC BANK') ? 'selected' : ''; ?>>HDFC BANK</option>
                                <option value="ICICI BANK" <?php echo ($payment_data['bank'] == 'ICICI BANK') ? 'selected' : ''; ?>>ICICI BANK</option>
                            </select>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Amount:</span>
                            <input type="number" class="form-control form-control-sm" name="payment_amount" value="<?php echo $payment_data['amount'] ?? '0'; ?>" placeholder="0.00" onchange="updatePaymentTotals()" oninput="updatePaymentTotals()">
                        </div>
                        <div class="info-row">
                            <span class="info-label">Receipt:</span>
                            <input type="file" class="form-control form-control-sm" name="payment_receipt" accept="image/*">
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Received:</span>
                            <input type="text" class="form-control form-control-sm" name="total_received" value="<?php echo number_format((float)($payment_data['total_received'] ?? 0), 2, '.', ''); ?>" readonly>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Balance Amount:</span>
                            <input type="text" class="form-control form-control-sm" name="balance_amount" value="<?php echo number_format((float)($payment_data['balance_amount'] ?? 0), 2, '.', ''); ?>" readonly>
                        </div>
                </div>

                <!-- Summary -->
                <div class="services-section" style="flex: 1; min-width: 400px;">
                    <h5><i class="fas fa-calculator"></i> Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>TOTAL EXPENSE</strong></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="summary-total-expense" name="total_expense" value="<?php echo $cost_data['total_expense']; ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td><strong>MARK UP (PROFIT)</strong></td>
                                    <td>
                                        <input type="hidden" class="form-control form-control-sm" id="markup-percentage" name="markup_percentage" value="<?php echo $cost_data['markup_percentage']; ?>"placeholder="%" style="max-width: 80px;" readonly>
                                        <span id="markup-percent-display" style="font-size: 0.8rem; color: var(--gray-600);"><?php echo $cost_data['markup_percentage']; ?>%</span>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="markup-amount" name="markup_amount" value="<?php echo $cost_data['markup_amount']; ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td><strong>SERVICE TAX</strong></td>
                                    <td>
                                        <select class="form-control form-control-sm" id="tax-percentage" name="tax_percentage" onchange="calculateSummary()" style="max-width: 80px;">
                                            <option value="0" <?php echo ($cost_data['tax_percentage'] == 0) ? 'selected' : ''; ?>>0%</option>
                                            <option value="5" <?php echo ($cost_data['tax_percentage'] == 5) ? 'selected' : ''; ?>>5%</option>
                                            <option value="18" <?php echo ($cost_data['tax_percentage'] == 18) ? 'selected' : ''; ?>>18%</option>
                                            <option value="1.05" <?php echo ($cost_data['tax_percentage'] == 1.05) ? 'selected' : ''; ?>>1.05%</option>
                                            <option value="1.18" <?php echo ($cost_data['tax_percentage'] == 1.18) ? 'selected' : ''; ?>>1.18%</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="tax-amount" name="tax_amount" value="<?php echo $cost_data['tax_amount']; ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td><strong>PACKAGE COST</strong></td>
                                    <td></td>
                                    <td><input type="number" class="form-control form-control-sm" id="package-cost" name="package_cost" value="<?php echo $cost_data['package_cost']; ?>" onchange="calculateSummary()"></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount in</strong></td>
                                    <td><span id="selected-currency" style="font-weight: bold;"><?php echo $cost_data['currency']; ?></span></td>
                                    <td><input type="number" class="form-control form-control-sm" id="currency-rate" name="currency_rate" value="<?php echo $cost_data['currency_rate']; ?>" onchange="calculateSummary()"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="converted-amount" name="converted_amount" value="<?php echo $cost_data['converted_amount']; ?>" readonly></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-modern">
                    <i class="fas fa-save"></i> Update Cost File
                </button>
                <div class="btn-modern"
                    onclick="return confirmCostSheet(event)"
                >
                    <i class="fas fa-save"></i> Confirm Cost File
                </div>
                <a href="view_cost_sheets.php" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-arrow-left"></i> Back to Cost Files
                </a>
            </div>
        </form>
    </div>
</div>

<script src="edit_cost_file_agent_package.js?v=<?php echo time(); ?>"></script>
<script>

    let accommodationRowCount = 1;
    function addAccommodationRow() {
        const tbody = document.getElementById('accommodation-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][destination]" onchange="updateHotels(this, ${accommodationRowCount})">
                    <option value="">Select Destination</option>
                    <?php
                        $uniqueValues = [];
                        mysqli_data_seek($accommodations, 0);
                        while($dest = mysqli_fetch_assoc($accommodations)):
                            if (in_array($dest['destination'], $uniqueValues)) {
                                continue; // skip duplicate
                            }
                            $uniqueValues[] = $dest['destination'];
                    ?>
                        <option 
                            value="<?php echo htmlspecialchars($dest['destination']); ?>" 
                        >
                            <?php echo htmlspecialchars($dest['destination']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="accommodation[${accommodationRowCount}][idx]" value="${accommodationRowCount}">
            </td>
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][hotel]" onchange="updateRoomTypes(this, ${accommodationRowCount})" disabled>
                    <option value="">Select Hotel</option>
                </select>
            </td>
            <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_in]"></td>
            <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_out]"></td>
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][room_type]" onchange="updateRates(this, ${accommodationRowCount})" disabled>
                    <option value="">Select Room Type</option>
                </select>
            </td>
                    
            <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[${accommodationRowCount}][rooms_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][meal_plan]" onchange="updateRates(this, ${accommodationRowCount})">
                    <option value="">Select Meal Plan</option>
                    <option value="cp" selected>CP</option>
                    <option value="map">MAP</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[${accommodationRowCount}][rooms_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[${accommodationRowCount}][extra_adult_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[${accommodationRowCount}][extra_adult_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[${accommodationRowCount}][extra_child_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[${accommodationRowCount}][extra_child_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[${accommodationRowCount}][child_no_bed_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[${accommodationRowCount}][child_no_bed_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[${accommodationRowCount}][nights]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
                    
            <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[${accommodationRowCount}][total]" data-row="${accommodationRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        `;
        tbody.appendChild(newRow);
        accommodationRowCount++;
    }


    // Update hotels based on selected destination
    function updateHotels(destinationSelect, rowIndex, value) {
    const hotelSelect = destinationSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[hotel]"]');
    const roomTypeSelect = destinationSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');
    const mealPlanSelect = destinationSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[meal_plan]"]');
    
    hotelSelect.disabled = !destinationSelect.value;
    // roomTypeSelect.disabled = true;
    // mealPlanSelect.disabled = true;
    
    if(destinationSelect.value) {
        // AJAX call to get hotels for selected destination
        fetch(`get_data_model.php?data_model=accommodation&destination=${destinationSelect.value}`)
            .then(response => response.json())
            .then(res => {
                hotelSelect.innerHTML = '<option value="">Select Hotel</option>';

                let rows = []
                let key_name = "hotel_name"
                
                res.data.forEach(row=>{
                    if(row[key_name] && !rows.includes(row[key_name])){
                        rows.push(row[key_name])
                    }
                })

                rows.forEach(item => {
                    hotelSelect.innerHTML += `<option value="${item}">${item}</option>`;
                });
            });

            updateRates(destinationSelect, rowIndex)
    }
    }

    // Update room types based on selected hotel
    function updateRoomTypes(hotelSelect, rowIndex, value) {
        const destinationSelect = hotelSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[destination]"]');

        const roomTypeSelect = hotelSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');
        const mealPlanSelect = hotelSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[meal_plan]"]');

        roomTypeSelect.disabled = !hotelSelect.value;
        // mealPlanSelect.disabled = true;

        if(hotelSelect.value) {
            // AJAX call to get room types for selected hotel
            fetch(`get_data_model.php?data_model=accommodation&destination=${destinationSelect.value}&hotel_name=${hotelSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    roomTypeSelect.innerHTML = '<option value="">Select Room Type</option>';

                    let rows = []
                    let key_name = "room_category"
                    
                    res.data.forEach(row=>{
                        if(row[key_name] && !rows.includes(row[key_name])){
                            rows.push(row[key_name])
                        }
                    })
                
                    rows.forEach(item => {
                        roomTypeSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });

                    updateRates(hotelSelect, rowIndex)
                });
        }


    }

    // Update rates based on selected room type
    function updateRates(parentSelect, rowIndex) {
        const desctinationSelect = parentSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[destination]"]');
        const hotelSelect = parentSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[hotel]"]');
        const mealTypeSelect = parentSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[meal_plan]"]');
        const _roomTypeSelect = parentSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');

        const roomRateSelect = parentSelect.closest('tr').querySelector('input[name^="accommodation"][name$="[rooms_rate]"]');
        const extraAdultRateSelect = parentSelect.closest('tr').querySelector('input[name^="accommodation"][name$="[extra_adult_rate]"]');
        const extraChildRateSelect = parentSelect.closest('tr').querySelector('input[name^="accommodation"][name$="[extra_child_rate]"]');
        const childNoBedRateSelect = parentSelect.closest('tr').querySelector('input[name^="accommodation"][name$="[child_no_bed_rate]"]');


        if(desctinationSelect.value && hotelSelect.value && _roomTypeSelect.value) {
            console.log(roomRateSelect , extraAdultRateSelect , extraChildRateSelect, childNoBedRateSelect);

            fetch(`get_data_model.php?data_model=accommodation&destination=${desctinationSelect.value}&hotel_name=${hotelSelect.value}&room_category=${_roomTypeSelect.value}`)
                .then(response => response.json())
                .then(res => {

                    if(res.data && res.data.length){
                        let {
                            cp, 
                            map_rate,

                            eb_adult_cp,
                            eb_adult_map,

                            child_with_bed_cp,
                            child_with_bed_map,

                            child_without_bed_cp,
                            child_without_bed_map
                        } = res.data[0]

                        if(mealTypeSelect.value == 'cp'){
                            roomRateSelect.value = cp
                            extraAdultRateSelect.value = eb_adult_cp
                            extraChildRateSelect.value = child_with_bed_cp
                            childNoBedRateSelect.value = child_without_bed_cp
                        }
                        else if(mealTypeSelect.value == 'map'){
                            roomRateSelect.value = map_rate
                            extraAdultRateSelect.value = eb_adult_map
                            extraChildRateSelect.value = child_with_bed_map
                            childNoBedRateSelect.value = child_without_bed_map
                        }

                    }

                });
        }
    }

    // Update rates when meal plan changes
    function updateMealPlanRates(mealPlanSelect, rowIndex) {
        if(mealPlanSelect.value) {
            // AJAX call to get updated rates for selected meal plan
            fetch(`get_meal_plan_rates.php?meal_plan_id=${mealPlanSelect.value}`)
                .then(response => response.json())
                .then(rates => {
                    // Update rate inputs with meal plan adjusted values
                    calculateAccommodationTotal(rowIndex);
                });
        }
    }

    let transportationRowCount = 1;
    function addTransportationRow() {
        const tbody = document.getElementById('transportation-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="transportation[${transportationRowCount}][supplier]" onchange="updateTransportVehicles(this, ${transportationRowCount})">
                    <option value="">Select Supplier</option>
                    <?php
                        $uniqueValues = [];
                        mysqli_data_seek($transport_details, 0);
                        while($transport = mysqli_fetch_assoc($transport_details)):
                            if (in_array($transport['company_name'], $uniqueValues)) {
                                continue; // skip duplicate
                            }
                            $uniqueValues[] = $transport['company_name'];
                    ?>
                        <option value="<?php echo htmlspecialchars($transport['company_name']); ?>"><?php echo htmlspecialchars($transport['company_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="transportation[${transportationRowCount}][idx]" value="${transportationRowCount}">
            </td>
            <td>
                <select class="form-control form-control-sm" name="transportation[${transportationRowCount}][car_type]" onchange="updateTransportRates(this, ${transportationRowCount})" disabled>
                    <option value="">Select Car Type</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[${transportationRowCount}][daily_rent]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[${transportationRowCount}][days]" data-row="${transportationRowCount}" value="2" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="2"></td>
            <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[${transportationRowCount}][km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[${transportationRowCount}][extra_km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[${transportationRowCount}][price_per_km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[${transportationRowCount}][toll]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[${transportationRowCount}][total]" data-row="${transportationRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        `;
        tbody.appendChild(newRow);
        transportationRowCount++;
    }
    // Update transport vehicles based on selected supplier
    function updateTransportVehicles(supplierSelect, rowIndex, selectedVehicle) {
        const vehicleSelect = supplierSelect.closest('tr').querySelector('select[name^="transportation"][name$="[car_type]"]');
        const dailyRentInput = supplierSelect.closest('tr').querySelector('input[name^="transportation"][name$="[daily_rent]"]');
        const pricePerKmInput = supplierSelect.closest('tr').querySelector('input[name^="transportation"][name$="[price_per_km]"]');

        vehicleSelect.disabled = !supplierSelect.value;

        if(supplierSelect.value) {
            // AJAX call to get vehicles for selected supplier
            fetch(`get_data_model.php?data_model=transportation&company_name=${supplierSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    vehicleSelect.innerHTML = '<option value="">Select Car Type</option>';

                    let rows = []
                    let key_name = "vehicle"
                    
                    res.data.forEach(row=>{
                        if(row[key_name] && !rows.includes(row[key_name])){
                            rows.push(row[key_name])
                        }
                    })
                
                    rows.forEach(item => {
                        vehicleSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });

                    // If there's a selected vehicle, update rates
                    if(selectedVehicle) {
                        updateTransportRates(vehicleSelect, rowIndex);
                    }
                });
        }
    }

    // Update transport rates based on selected vehicle
    function updateTransportRates(vehicleSelect, rowIndex) {
        const supplierSelect = vehicleSelect.closest('tr').querySelector('select[name^="transportation"][name$="[supplier]"]');
        const dailyRentInput = vehicleSelect.closest('tr').querySelector('input[name^="transportation"][name$="[daily_rent]"]');
        const pricePerKmInput = vehicleSelect.closest('tr').querySelector('input[name^="transportation"][name$="[price_per_km]"]');

        if(supplierSelect.value && vehicleSelect.value) {
            // AJAX call to get rates for selected supplier and vehicle
            fetch(`get_data_model.php?data_model=transportation&company_name=${supplierSelect.value}&vehicle=${vehicleSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    if(res.data && res.data.length > 0) {
                        const transport = res.data[0];
                        dailyRentInput.value = transport.daily_rent || 0;
                        pricePerKmInput.value = transport.rate_per_km || 0;

                        // Recalculate total
                        calculateTransportationTotal(rowIndex);
                    }
                });
        }
    }

    let cruiseRowCount = 1;
    function addCruiseRow() {
        const tbody = document.getElementById('cruise-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${cruiseRowCount + 1}</td>
            <td><input type="text" class="form-control form-control-sm" name="cruise[${cruiseRowCount}][supplier]" placeholder="Supplier Name"></td>
            <td>
                <select class="form-control form-control-sm" name="cruise[${cruiseRowCount}][boat_type]">
                    <option value="">Select Boat Type</option>
                    <option value="Speedboat / Powerboat">Speedboat / Powerboat</option>
                    <option value="Sailboat / Sailing Yacht">Sailboat / Sailing Yacht</option>
                    <option value="Pontoon Boat">Pontoon Boat</option>
                    <option value="Bowrider">Bowrider</option>
                    <option value="Cabin Cruiser">Cabin Cruiser</option>
                    <option value="Deck Boat">Deck Boat</option>
                    <option value="Jet Boat">Jet Boat</option>
                    <option value="Personal Watercraft (PWC)">Personal Watercraft (PWC)</option>
                    <option value="Houseboat">Houseboat</option>
                    <option value="Bass Boat">Bass Boat</option>
                    <option value="Dinghy">Dinghy</option>
                    <option value="Canoe">Canoe</option>
                    <option value="Kayak">Kayak</option>
                    <option value="Rowboat">Rowboat</option>
                    <option value="Inflatable Boat">Inflatable Boat</option>
                    <option value="Sloop">Sloop</option>
                    <option value="Cutter">Cutter</option>
                    <option value="Ketch">Ketch</option>
                    <option value="Yawl">Yawl</option>
                    <option value="Catamaran">Catamaran</option>
                    <option value="Trimaran">Trimaran</option>
                    <option value="Fishing Trawler">Fishing Trawler</option>
                    <option value="Cargo Ship / Freighter">Cargo Ship / Freighter</option>
                    <option value="Tugboat">Tugboat</option>
                    <option value="Ferry">Ferry</option>
                    <option value="Pilot Boat">Pilot Boat</option>
                    <option value="Barge">Barge</option>
                    <option value="Oil Tanker / Gas Carrier">Oil Tanker / Gas Carrier</option>
                    <option value="Dredger">Dredger</option>
                    <option value="Fireboat">Fireboat</option>
                    <option value="Research Vessel">Research Vessel</option>
                    <option value="Yacht">Yacht</option>
                    <option value="Superyacht / Megayacht">Superyacht / Megayacht</option>
                    <option value="Cruise Ship">Cruise Ship</option>
                    <option value="Expedition Yacht">Expedition Yacht</option>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="cruise[${cruiseRowCount}][cruise_type]">
                    <option value="">Select Cruise Type</option>
                    <option value="Ocean Cruise">Ocean Cruise</option>
                    <option value="River Cruise">River Cruise</option>
                    <option value="Expedition Cruise">Expedition Cruise</option>
                    <option value="Coastal Cruise">Coastal Cruise</option>
                    <option value="Mini / Weekend Cruise">Mini / Weekend Cruise</option>
                    <option value="Short Cruise">Short Cruise</option>
                    <option value="Weeklong Cruise">Weeklong Cruise</option>
                    <option value="Extended Cruise">Extended Cruise</option>
                    <option value="World Cruise">World Cruise</option>
                    <option value="Luxury Cruise">Luxury Cruise</option>
                    <option value="Family Cruise">Family Cruise</option>
                    <option value="Adventure Cruise">Adventure Cruise</option>
                    <option value="Wellness Cruise">Wellness Cruise</option>
                    <option value="Romantic / Honeymoon Cruise">Romantic / Honeymoon Cruise</option>
                    <option value="Singles Cruise">Singles Cruise</option>
                    <option value="Themed Cruise">Themed Cruise</option>
                    <option value="Repositioning Cruise">Repositioning Cruise</option>
                    <option value="Mega Cruise">Mega Cruise</option>
                    <option value="Small Ship Cruise">Small Ship Cruise</option>
                    <option value="Yacht Cruise">Yacht Cruise</option>
                    <option value="Sailing Cruise">Sailing Cruise</option>
                    <option value="Barge Cruise">Barge Cruise</option>
                </select>
            </td>
            <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[${cruiseRowCount}][check_in]"></td>
            <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[${cruiseRowCount}][check_out]"></td>
            <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise[${cruiseRowCount}][rate]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise[${cruiseRowCount}][extra]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[${cruiseRowCount}][total]" data-row="${cruiseRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        `;
        tbody.appendChild(newRow);
        cruiseRowCount++;
    }
    // Confirmation popup for new version creation
    function confirmNewVersion(event) {
        event.preventDefault();
        if (confirm("Do you want to create the new version?")) {
            document.getElementById('cost-file-form').submit();
            return true;
        } else {
            return false;
        }
    }
    function confirmCostSheet(event) {
        event.preventDefault();
        if (confirm("Do you want to confirm this version?")) {

            // add a text input and set name as confirm and value as 1, at last append to the below form
            const confirmInput = document.createElement('input');
            confirmInput.type = 'number';
            confirmInput.name = 'confirmed';
            confirmInput.value = '1';
            document.getElementById('cost-file-form').appendChild(confirmInput);

            document.getElementById('cost-file-form').submit();        
            return true;
        } else {
            return false;
        }
    }

    // Include necessary JavaScript functions
    function toggleService(item, serviceName) {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const isSelected = item.classList.contains('selected');

        if (isSelected) {
            item.classList.remove('selected');
            checkbox.checked = false;
            const sectionId = serviceName.replace('_', '-') + '-section';
            const section = document.getElementById(sectionId);
            if (section) section.style.display = 'none';
        } else {
            item.classList.add('selected');
            checkbox.checked = true;
            const sectionId = serviceName.replace('_', '-') + '-section';
            const section = document.getElementById(sectionId);
            if (section) section.style.display = 'block';
        }
    }

    function addConnectingFlight(type) {
        document.getElementById(type + '-connecting').style.display = 'table-row';
    }

    function removeConnectingFlight(type) {
        document.getElementById(type + '-connecting').style.display = 'none';
    }

    function calculateVisaTotal(row) {
        const passengers = parseFloat(document.querySelector(`.visa-passengers[data-row="${row}"]`).value) || 0;
        const rate = parseFloat(document.querySelector(`.visa-rate[data-row="${row}"]`).value) || 0;
        const total = passengers * rate;
        document.querySelector(`.visa-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateVisaGrandTotal();
    }

    function calculateVisaGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.visa-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('visa-grand-total').value = grandTotal.toFixed(2);
    }

    function calculateSummary() {
        let totalExpense = 0;

        // Add visa/flight total if section is visible
        if (document.getElementById('visa-flight-section').style.display !== 'none') {
            const visaTotal = parseFloat(document.getElementById('visa-grand-total')?.value) || 0;
            totalExpense += visaTotal;
        }

        // Add accommodation total if section is visible
        if (document.getElementById('accommodation-section').style.display !== 'none') {
            const accomTotal = parseFloat(document.getElementById('accommodation-grand-total')?.value) || 0;
            totalExpense += accomTotal;
        }

        // Add transportation total if section is visible
        if (document.getElementById('transportation-section').style.display !== 'none') {
            const transTotal = parseFloat(document.getElementById('transportation-grand-total')?.value) || 0;
            totalExpense += transTotal;
        }

        // Add cruise total if section is visible
        if (document.getElementById('cruise-hire-section').style.display !== 'none') {
            const cruiseTotal = parseFloat(document.getElementById('cruise-grand-total')?.value) || 0;
            totalExpense += cruiseTotal;
        }

        // Add agent package total if section is visible
        if (document.getElementById('agent-package-section').style.display !== 'none') {
            const agentPackageTotal = parseFloat(document.getElementById('agent-package-grand-total')?.value) || 0;
            totalExpense += agentPackageTotal;
        }

        // Add medical tourism total if section is visible
        if (document.getElementById('medical-tourism-section').style.display !== 'none') {
            const medicalTourismTotal = parseFloat(document.getElementById('medical-tourism-grand-total')?.value) || 0;
            totalExpense += medicalTourismTotal;
        }

        // Add extras total if section is visible
        if (document.getElementById('extras-section').style.display !== 'none') {
            const extrasTotal = parseFloat(document.getElementById('extras-grand-total')?.value) || 0;
            totalExpense += extrasTotal;
        }

        // Update total expense
        document.getElementById('summary-total-expense').value = totalExpense.toFixed(2);

      
        

        // Check if package cost has been manually entered
        const packageCost = parseFloat(document.getElementById('package-cost').value) || 0;
        const taxPercentage =  100 + parseFloat(document.getElementById('tax-percentage').value) || 0;
        
        if(packageCost > 0){
            const markupPercentInput = document.getElementById('markup-percentage');
            const markupPercentSpan = document.getElementById('markup-percent-display');
            const markupAmountInput = document.getElementById('markup-amount');
            const taxAmountInput = document.getElementById('tax-amount');

            let markup_with_tax = packageCost - totalExpense

            let taxAmount = markup_with_tax - (markup_with_tax / taxPercentage) * 100
            let markupAmount = markup_with_tax - taxAmount
            let markupPercentage = totalExpense ?  markupAmount / totalExpense * 100 : 0


            taxAmountInput.value = taxAmount.toFixed(2);
            markupAmountInput.value = markupAmount.toFixed(2);
            markupPercentInput.value = markupPercentage.toFixed(2);
            markupPercentSpan.textContent = markupPercentage.toFixed(2) + '%';

             updatePaymentTotals();
        }
    }

    function updateSummaryTotals() {
        calculateSummary();
    }

    const originalCalculateVisaGrandTotal = calculateVisaGrandTotal;
    if (typeof calculateVisaGrandTotal === 'function') {
        calculateVisaGrandTotal = function() {
            originalCalculateVisaGrandTotal();
            updateSummaryTotals();
        };
    }

    const originalCalculateAccommodationGrandTotal = calculateAccommodationGrandTotal;
    if (typeof calculateAccommodationGrandTotal === 'function') {
        calculateAccommodationGrandTotal = function() {
            originalCalculateAccommodationGrandTotal();
            updateSummaryTotals();
        };
    }

    const originalCalculateTransportationGrandTotal = calculateTransportationGrandTotal;
    if (typeof calculateTransportationGrandTotal === 'function') {
        calculateTransportationGrandTotal = function() {
            originalCalculateTransportationGrandTotal();
            updateSummaryTotals();
        };
    }

    const originalCalculateCruiseGrandTotal = calculateCruiseGrandTotal;
    if (typeof calculateCruiseGrandTotal === 'function') {
        calculateCruiseGrandTotal = function() {
            originalCalculateCruiseGrandTotal();
            updateSummaryTotals();
        };
    }

    const originalCalculateExtrasGrandTotal = calculateExtrasGrandTotal;
    if (typeof calculateExtrasGrandTotal === 'function') {
        calculateExtrasGrandTotal = function() {
            originalCalculateExtrasGrandTotal();
            updateSummaryTotals();
        };
    }

    // Function to update payment totals
    function updatePaymentTotals() {
        const paymentAmount = parseFloat(document.querySelector('input[name="payment_amount"]').value) || 0;
        const packageCost = parseFloat(document.getElementById('package-cost').value) || 0;

        // Always update total received to match payment amount
        const totalReceivedInput = document.querySelector('input[name="total_received"]');
        if (totalReceivedInput) {
            totalReceivedInput.value = paymentAmount.toFixed(2);
        }

        // Update balance amount (package cost - total received)
        const balanceAmountInput = document.querySelector('input[name="balance_amount"]');
        if (balanceAmountInput) {
            const balanceAmount = packageCost - paymentAmount;
            balanceAmountInput.value = balanceAmount.toFixed(2);
        }
    }

    // Function to calculate values when package cost is manually entered
    function calculateFromPackageCost() {
        const packageCost = parseFloat(document.getElementById('package-cost').value) || 0;
        const totalExpense = parseFloat(document.getElementById('summary-total-expense').value) || 0;
        const taxPercentage = parseFloat(document.getElementById('tax-percentage').value) || 0;

        // Calculate markup amount based on package cost and total expense
        const markupAmount = packageCost - totalExpense;
        document.getElementById('markup-amount').value = markupAmount.toFixed(2);

        // Calculate and update markup percentage
        let markupPercentage = 0;
        if (totalExpense > 0) {
            markupPercentage = (markupAmount / totalExpense) * 100;
            document.getElementById('markup-percentage').value = markupPercentage.toFixed(2);
            // document.getElementById('markup-percent-display').textContent = markupPercentage.toFixed(2) + '%';
        }

        // Calculate tax amount based on tax percentage
        let taxAmount = 0;
        if (taxPercentage === 5) {
            // For 5% tax, apply to the full package cost
            taxAmount = (packageCost * taxPercentage) / 100;
        } else {
            // For other tax rates, apply only to the markup amount
            taxAmount = (markupAmount * taxPercentage) / 100;
        }
        document.getElementById('tax-amount').value = taxAmount.toFixed(2);

        // Update payment calculations
        updatePaymentTotals();
    }

    // Currency symbol mapping
    const currencySymbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'INR': '₹', 'BHD': 'BD',
        'KWD': 'KD', 'OMR': 'OMR', 'QAR': 'QR', 'SAR': 'SR', 'AED': 'AED', 'GCC': 'GCC',
        'THB': '฿', 'STD': 'Db', 'SGD': 'S$', 'RM': 'RM'
    };

    function updateCurrencySymbols() {
        const selectedCurrency = document.getElementById('currency-selector').value;
        document.getElementById('selected-currency').textContent = selectedCurrency;
    }

    // Add calculation functions for all services
    function calculateAccommodationTotal(row) {
        const roomsNo = parseFloat(document.querySelector(`.accom-rooms-no[data-row="${row}"]`).value) || 0;
        const roomsRate = parseFloat(document.querySelector(`.accom-rooms-rate[data-row="${row}"]`).value) || 0;
        const extraAdultNo = parseFloat(document.querySelector(`.accom-extra-adult-no[data-row="${row}"]`).value) || 0;
        const extraAdultRate = parseFloat(document.querySelector(`.accom-extra-adult-rate[data-row="${row}"]`).value) || 0;
        const extraChildNo = parseFloat(document.querySelector(`.accom-extra-child-no[data-row="${row}"]`).value) || 0;
        const extraChildRate = parseFloat(document.querySelector(`.accom-extra-child-rate[data-row="${row}"]`).value) || 0;
        const childNoBedNo = parseFloat(document.querySelector(`.accom-child-no-bed-no[data-row="${row}"]`).value) || 0;
        const childNoBedRate = parseFloat(document.querySelector(`.accom-child-no-bed-rate[data-row="${row}"]`).value) || 0;
        const nights = parseFloat(document.querySelector(`.accom-nights[data-row="${row}"]`).value) || 0;

        const rowTotal = ((roomsNo * roomsRate) + 
                         (extraAdultNo * extraAdultRate) + 
                         (extraChildNo * extraChildRate) + 
                         (childNoBedNo * childNoBedRate)) * nights;

        document.querySelector(`.accom-total[data-row="${row}"]`).value = rowTotal.toFixed(2);
        calculateAccommodationGrandTotal();
    }

    function calculateAccommodationGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.accom-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('accommodation-grand-total').value = grandTotal.toFixed(2);
    }

    function calculateTransportationTotal(row) {
        const dailyRent = parseFloat(document.querySelector(`.trans-daily-rent[data-row="${row}"]`).value) || 0;
        const days = parseFloat(document.querySelector(`.trans-days[data-row="${row}"]`).value) || 0;
        const extraKm = parseFloat(document.querySelector(`.trans-extra-km[data-row="${row}"]`).value) || 0;
        const pricePerKm = parseFloat(document.querySelector(`.trans-price-per-km[data-row="${row}"]`).value) || 0;
        const toll = parseFloat(document.querySelector(`.trans-toll[data-row="${row}"]`).value) || 0;

        const total = (dailyRent * days) + (extraKm * pricePerKm) + toll;

        document.querySelector(`.trans-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateTransportationGrandTotal();
    }

    function calculateTransportationGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.trans-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('transportation-grand-total').value = grandTotal.toFixed(2);
    }

    function calculateCruiseTotal(row) {
        const rate = parseFloat(document.querySelector(`.cruise-rate[data-row="${row}"]`).value) || 0;
        const extra = parseFloat(document.querySelector(`.cruise-extra[data-row="${row}"]`).value) || 0;
        const total = rate + extra;
        document.querySelector(`.cruise-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateCruiseGrandTotal();
    }

    function calculateCruiseGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.cruise-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('cruise-grand-total').value = grandTotal.toFixed(2);
    }

    function calculateExtrasTotal(row) {
        const amount = parseFloat(document.querySelector(`.extras-amount[data-row="${row}"]`).value) || 0;
        const extra = parseFloat(document.querySelector(`.extras-extra[data-row="${row}"]`).value) || 0;
        const total = amount + extra;
        document.querySelector(`.extras-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateExtrasGrandTotal();
    }

    function calculateExtrasGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.extras-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('extras-grand-total').value = grandTotal.toFixed(2);
    }

    let visaRowCount = <?php echo count($visa_data); ?>;
    function addVisaRow() {
        const tbody = document.getElementById('visa-details-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${visaRowCount + 1}</td>
            <td><input type="text" class="form-control form-control-sm" name="visa[${visaRowCount}][sector]" placeholder="Sector"></td>
            <td><input type="text" class="form-control form-control-sm" name="visa[${visaRowCount}][supplier]" placeholder="Supplier"></td>
            <td><input type="date" class="form-control form-control-sm" name="visa[${visaRowCount}][travel_date]"></td>
            <td><input type="number" class="form-control form-control-sm visa-passengers" name="visa[${visaRowCount}][passengers]" data-row="${visaRowCount}" value="0" onchange="calculateVisaTotal(${visaRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm visa-rate" name="visa[${visaRowCount}][rate_per_person]" data-row="${visaRowCount}" value="0" onchange="calculateVisaTotal(${visaRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm" name="visa[${visaRowCount}][roe]" value="1" step="0.01"></td>
            <td><input type="text" class="form-control form-control-sm visa-total" name="visa[${visaRowCount}][total]" data-row="${visaRowCount}" readonly></td>
        `;
        tbody.appendChild(newRow);
        visaRowCount++;
    }

    // Calculate total PAX
    function calculateTotalPax() {
        const adults = parseInt(document.querySelector('input[name="adults_count"]').value) || 0;
        const children = parseInt(document.querySelector('input[name="children_count"]').value) || 0;
        const infants = parseInt(document.querySelector('input[name="infants_count"]').value) || 0;
        const total = adults + children + infants;
        document.getElementById('total-pax').value = total;
    }

    // Agent Package calculations
    function calculateAgentPackageTotal(row) {
        const adultCount = parseFloat(document.querySelector(`.agent-adult-count[data-row="${row}"]`).value) || 0;
        const adultPrice = parseFloat(document.querySelector(`.agent-adult-price[data-row="${row}"]`).value) || 0;
        const childCount = parseFloat(document.querySelector(`.agent-child-count[data-row="${row}"]`).value) || 0;
        const childPrice = parseFloat(document.querySelector(`.agent-child-price[data-row="${row}"]`).value) || 0;
        const infantCount = parseFloat(document.querySelector(`.agent-infant-count[data-row="${row}"]`).value) || 0;
        const infantPrice = parseFloat(document.querySelector(`.agent-infant-price[data-row="${row}"]`).value) || 0;

        const total = (adultCount * adultPrice) + (childCount * childPrice) + (infantCount * infantPrice);

        document.querySelector(`.agent-package-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateAgentPackageGrandTotal();
    }

    function calculateMedicalTourismTotal(row) {

        const netValue = parseFloat(document.querySelector(`.medical-tourism-net[data-row="${row}"]`).value) || 0;
        const tdsValue = parseFloat(document.querySelector(`.medical-tourism-tds[data-row="${row}"]`).value) || 0;
        const otherExpenses = parseFloat(document.querySelector(`.medical-tourism-other_expenses[data-row="${row}"]`).value) || 0;
        const GST = 18

    
        let total_expenses = netValue + tdsValue + otherExpenses
        let total_tax = total_expenses * GST / 100
        let total = total_expenses + total_tax

        document.querySelector(`.medical-tourism-total[data-row="${row}"]`).value = total.toFixed(2);
        calculateMedicalTourismGrandTotal();
    }

    function calculateMedicalTourismGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.medical-tourism-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('medical-tourism-grand-total').value = grandTotal.toFixed(2);
    }

    function calculateAgentPackageGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.agent-package-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        document.getElementById('agent-package-grand-total').value = grandTotal.toFixed(2);
    }

    function addAgentPackageRow() {
        const tbody = document.getElementById('agent-package-tbody');
        const rows = tbody.querySelectorAll('tr');
        const newIndex = rows.length;

        // Get current PAX counts
        const adultsCount = document.querySelector('input[name="adults_count"]').value || 0;
        const childrenCount = document.querySelector('input[name="children_count"]').value || 0;
        const infantsCount = document.querySelector('input[name="infants_count"]').value || 0;

        const newRow = document.createElement('tr');

        // Get destinations for dropdown
        const destinationsSelect = document.querySelector('select[name="agent_package[0][destination]"]');
        let destinationsOptions = '';
        if (destinationsSelect) {
            destinationsOptions = destinationsSelect.innerHTML;
        }

        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="agent_package[${newIndex}][destination]" onchange="updateAgentSupplier(this, ${newIndex})">
                    ${destinationsOptions}
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="agent_package[${newIndex}][agent_supplier]" style="width: 120px;" disabled>
                    <option value="">Select Agent/Supplier</option>
                </select>
            </td>
            <td><input type="date" class="form-control form-control-sm" name="agent_package[${newIndex}][start_date]"></td>
            <td><input type="date" class="form-control form-control-sm" name="agent_package[${newIndex}][end_date]"></td>
            <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[${newIndex}][adult_count]" data-row="${newIndex}" value="${adultsCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[${newIndex}][adult_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[${newIndex}][child_count]" data-row="${newIndex}" value="${childrenCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[${newIndex}][child_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[${newIndex}][infant_count]" data-row="${newIndex}" value="${infantsCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[${newIndex}][infant_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
            <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[${newIndex}][total]" data-row="${newIndex}" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
        `;

        tbody.appendChild(newRow);
    }
    function updateAgentSupplier(destinationSelect, rowIndex, value) {
        const supplierSelect = destinationSelect.closest('tr').querySelector('select[name^="agent_package"][name$="[agent_supplier]"]');

        supplierSelect.disabled = !destinationSelect.value;


        if(destinationSelect.value) {
            // AJAX call to get hotels for selected destination
            fetch(`get_data_model.php?data_model=agent_package&destination=${destinationSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    supplierSelect.innerHTML = '<option value="">Select Agent/Supplier</option>';
                 

                    let rows = []
                    let key_name = "supplier"
                    
                    res.data.forEach(row=>{
                        if(row[key_name] && !rows.includes(row[key_name])){
                            rows.push(row[key_name])
                        }
                    })
                
                    rows.forEach(item => {
                        supplierSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });
                });
        }
    }

    function addMedicalTourismRow() {
        const tbody = document.getElementById('medical-tourism-tbody');
        const rows = tbody.querySelectorAll('tr');
        const newIndex = rows.length;

        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="medical_tourisms[${newIndex}][place]" onchange="updateHospitals(this, ${newIndex})" style="width: 120px;">
                    <option value="">Select Place</option>
                    <?php
                        $uniqueValues = [];
                        mysqli_data_seek($hospital_details, 0);
                        while($hospital = mysqli_fetch_assoc($hospital_details)):
                            if (in_array($hospital['destination'], $uniqueValues)) {
                                continue; // skip duplicate
                            }
                            $uniqueValues[] = $hospital['destination'];
                    ?>
                        <option value="<?php echo htmlspecialchars($hospital['destination']); ?>"><?php echo htmlspecialchars($hospital['destination']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="medical_tourisms[${newIndex}][idx]" value="${newIndex}">
            </td>
            <td><input type="date" class="form-control form-control-sm" name="medical_tourisms[${newIndex}][treatment_date]" value=""></td>
            <td>
                <select class="form-control form-control-sm" name="medical_tourisms[${newIndex}][hospital]" style="width: 120px;" disabled>
                    <option value="">Select Hospital</option>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="medical_tourisms[${newIndex}][treatment_type]">
                   <option value="">Select type</option>
                   <option value="Type 1" >Type 1</option>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="medical_tourisms[${newIndex}][op_ip]">
                   <option value="">Select type</option>
                   <option value="IP" >IP</option>
                   <option value="OP" >OP</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm medical-tourism-net" name="medical_tourisms[${newIndex}][net]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateMedicalTourismTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm medical-tourism-tds" name="medical_tourisms[${newIndex}][tds]" data-row="${newIndex}" value="0" style="width: 70px;" onchange="calculateMedicalTourismTotal(${newIndex})"></td>
            <td><input type="number" class="form-control form-control-sm medical-tourism-other_expenses" name="medical_tourisms[${newIndex}][other_expenses]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateMedicalTourismTotal(${newIndex})"></td>
            <td>18%</td>
            <td><input type="text" class="form-control form-control-sm medical-tourism-total" name="medical_tourisms[${newIndex}][total]" data-row="${newIndex}" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
        `;
                    
        tbody.appendChild(newRow);
    }

    // Add event listeners for PAX calculation
    document.addEventListener('DOMContentLoaded', function() {
        // Add change listeners to PAX inputs
        document.querySelectorAll('input[name="adults_count"], input[name="children_count"], input[name="infants_count"]').forEach(input => {
            input.addEventListener('change', calculateTotalPax);
            input.addEventListener('input', calculateTotalPax);
        });

        // Add payment amount change listener
        const paymentAmountInput = document.querySelector('input[name="payment_amount"]');
        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('change', updatePaymentTotals);
            paymentAmountInput.addEventListener('input', updatePaymentTotals);
        }

        // Initialize calculations
        calculateTotalPax();
        calculateVisaGrandTotal();
        calculateAccommodationGrandTotal();
        calculateTransportationGrandTotal();
        calculateCruiseGrandTotal();
        calculateExtrasGrandTotal();
        calculateSummary();
        updateCurrencySymbols();

        // Initialize transportation dropdowns for existing rows
        document.querySelectorAll('select[name^="transportation"][name$="[supplier]"]').forEach((supplierSelect, index) => {
            if(supplierSelect.value) {
                const vehicleSelect = supplierSelect.closest('tr').querySelector('select[name^="transportation"][name$="[car_type]"]');
                const selectedVehicle = vehicleSelect.getAttribute('data-selected') || '';
                updateTransportVehicles(supplierSelect, index, selectedVehicle);
            }
        });

        // Initialize medical tourism dropdowns for existing rows
        document.querySelectorAll('select[name^="medical_tourisms"][name$="[place]"]').forEach((placeSelect, index) => {
            if(placeSelect.value) {
                const hospitalSelect = placeSelect.closest('tr').querySelector('select[name^="medical_tourisms"][name$="[hospital]"]');
                const selectedHospital = hospitalSelect.getAttribute('data-selected') || '';
                updateHospitals(placeSelect, index, selectedHospital);
            }
        });

        // Update payment totals on page load
        updatePaymentTotals();
    });

    // Update hospitals based on selected place for medical tourism
    function updateHospitals(placeSelect, rowIndex, selectedHospital) {
        const hospitalSelect = placeSelect.closest('tr').querySelector('select[name^="medical_tourisms"][name$="[hospital]"]');

        hospitalSelect.disabled = !placeSelect.value;

        if(placeSelect.value) {
            // AJAX call to get hospitals for selected place
            fetch(`get_data_model.php?data_model=medical_tourism&destination=${placeSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    hospitalSelect.innerHTML = '<option value="">Select Hospital</option>';

                    let rows = []
                    let key_name = "hospital_name"
                    
                    res.data.forEach(row=>{
                        if(row[key_name] && !rows.includes(row[key_name])){
                            rows.push(row[key_name])
                        }
                    })
                
                    rows.forEach(item => {
                        hospitalSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });
                });
        }
    }
</script>

<?php
require_once "includes/footer.php";
?>