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

if($cost_file_id == 0) {
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
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number, e.referral_code, e.created_at as lead_date,
        s.name as source_name, dest.name as destination_name, fm.full_name as file_manager_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE tc.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $cost_file_id);
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
        $adults_count = intval($_POST['adults_count'] ?? 0);
        $children_count = intval($_POST['children_count'] ?? 0);
        $infants_count = intval($_POST['infants_count'] ?? 0);
        $children_age_details = $_POST['children_age_details'] ?? '';
        $selected_services = json_encode($_POST['services'] ?? []);
        
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
            selected_services, visa_data, accommodation_data, transportation_data, 
            cruise_data, extras_data, agent_package_data, payment_data, total_expense, markup_percentage, 
            markup_amount, tax_percentage, tax_amount, package_cost, currency_rate, 
            converted_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        
        // Debug: Count parameters
        $params = [
            $cost_data['enquiry_id'], $new_cost_sheet_number, $guest_name, $guest_address, 
            $whatsapp_number, $tour_package, $currency, $nationality, $adults_count, 
            $children_count, $infants_count, $selected_services, $visa_data, 
            $accommodation_data, $transportation_data, $cruise_data, $extras_data, 
            $agent_package_data, $payment_data, $total_expense, $markup_percentage, $markup_amount, 
            $tax_percentage, $tax_amount, $package_cost, $currency_rate, $converted_amount
        ];
        // Build type string to match exact parameter count
        $type_string = str_repeat('d', count($params)); // Start with all 'd'
        $type_string[0] = 'i'; // enquiry_id
        $type_string[1] = 's'; // cost_sheet_number
        $type_string[2] = 's'; // guest_name
        $type_string[3] = 's'; // guest_address
        $type_string[4] = 's'; // whatsapp_number
        $type_string[5] = 's'; // tour_package
        $type_string[6] = 's'; // currency
        $type_string[7] = 's'; // nationality
        $type_string[8] = 'i'; // adults_count
        $type_string[9] = 'i'; // children_count
        $type_string[10] = 'i'; // infants_count
        $type_string[11] = 's'; // selected_services
        $type_string[12] = 's'; // visa_data
        $type_string[13] = 's'; // accommodation_data
        $type_string[14] = 's'; // transportation_data
        $type_string[15] = 's'; // cruise_data
        $type_string[16] = 's'; // extras_data
        $type_string[17] = 's'; // agent_package_data
        $type_string[18] = 's'; // payment_data
        // Rest are 'd' for decimal values
        
        mysqli_stmt_bind_param($insert_stmt, $type_string,
            ...$params
        );
        
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
        
    } catch(Exception $e) {
        $error_message = "Error updating cost file: " . $e->getMessage();
    }
}

// Get destinations for dropdown
$destinations_sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $destinations_sql);

// Decode JSON data for form population
$selected_services = json_decode($cost_data['selected_services'] ?? '[]', true);
$visa_data = json_decode($cost_data['visa_data'] ?? '[]', true);
$accommodation_data = json_decode($cost_data['accommodation_data'] ?? '[]', true);
$transportation_data = json_decode($cost_data['transportation_data'] ?? '[]', true);
$cruise_data = json_decode($cost_data['cruise_data'] ?? '[]', true);
$extras_data = json_decode($cost_data['extras_data'] ?? '[]', true);
$agent_package_data = json_decode($cost_data['agent_package_data'] ?? '[]', true);
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

<link rel="stylesheet" href="cost_file_styles.css">
<style>
/* Include the same styles from new_cost_file.php */
.cost-file-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    margin: 20px auto;
    max-width: 1200px;
}

.cost-file-header {
    background: linear-gradient(135deg, #4facfe 0%, #3b3b3b 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.cost-file-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cost-file-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-top: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    padding: 40px;
}

.info-card {
    background: #f8f9ff;
    border-radius: 15px;
    padding: 5px;
    border-left: 5px solid #4facfe;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
}

.info-value {
    color: #2c3e50;
    font-weight: 500;
}

.services-section {
    background: #fff;
    margin: 0 40px 40px;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    max-height: 600px;
    overflow-y: auto;
}

.services-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0px;
    margin-top: 25px;
    padding: 10px;
    background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.service-item {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 8px 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 210px;
    position: relative;
    overflow: hidden;
}
.service-item.selected {
    background: linear-gradient(135deg, #03a9b8 0%, #242424 100%);
    color: white;
    border-color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
}

.service-icon-small {
    font-size: 1.2rem;
    width: 20px;
    text-align: center;
}

.service-text {
    font-weight: 600;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.table-responsive {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-height: 450px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.payment-summary-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .payment-summary-container {
        flex-direction: column;
    }
}

.table {
    font-size: 0.85rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    position: sticky;
    top: 0;
    z-index: 5;
}

.table .form-control {
    max-width: none;
    width: 100%;
    height: 35px;
    font-size: 0.9rem;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.btn-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 15px 40px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    margin: 0 10px;
    text-decoration: none;
    display: inline-block;
}

.btn-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.action-buttons {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
}

.alert {
    border-radius: 15px;
    padding: 20px;
    margin: 20px 40px;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: none;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: none;
}
</style>

<div class="cost-file-container">
    <div class="cost-file-card">
        <div class="cost-file-header">
            <h1 class="cost-file-title">Create New Version</h1>
            <p class="cost-file-subtitle">Cost Sheet No: <?php echo htmlspecialchars($cost_data['cost_sheet_number']); ?> | Reference: <?php echo htmlspecialchars($cost_data['customer_name']); ?> | Last Updated: <?php echo date('d-m-Y H:i', strtotime($cost_data['updated_at'])); ?></p>
            <div class="text-right" style="margin-top: 10px;">
                <a href="view_payment_receipts.php?id=<?php echo $cost_file_id; ?>" class="btn btn-sm btn-info"><i class="fa fa-credit-card"></i> View Payment Details</a>
            </div>
        </div>

        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" id="cost-file-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $cost_file_id); ?>" enctype="multipart/form-data" onsubmit="return confirmNewVersion(event)">
            <div class="info-grid">
                <!-- Customer Information -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-user"></i> Customer Information</h5>
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
                </div>

                <!-- Travel Information -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-plane"></i> Travel Information</h5>
                    <div class="info-row">
                        <span class="info-label">Package Type:</span>
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
                            <!-- Add more countries as needed -->
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
                    <h5><i class="icon-copy fa fa-users"></i> Number of PAX</h5>
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
                        <input type="text" class="form-control form-control-sm" id="total-pax" readonly style="background: #f0f8ff; font-weight: bold;">
                    </div>
                </div>

                <!-- Services Selection -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-cogs"></i> Select Services</h5>
                    <div class="services-list">
                        <div class="service-item <?php echo in_array('visa_flight', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'visa_flight')">
                            <i class="fa fa-plane service-icon-small"></i>
                            <span class="service-text">VISA / FLIGHT BOOKING</span>
                            <input type="checkbox" name="services[]" value="visa_flight" <?php echo in_array('visa_flight', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('accommodation', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'accommodation')">
                            <i class="fa fa-bed service-icon-small"></i>
                            <span class="service-text">ACCOMMODATION</span>
                            <input type="checkbox" name="services[]" value="accommodation" <?php echo in_array('accommodation', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('transportation', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'transportation')">
                            <i class="fa fa-car service-icon-small"></i>
                            <span class="service-text">TRANSPORTATION</span>
                            <input type="checkbox" name="services[]" value="transportation" <?php echo in_array('transportation', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('cruise_hire', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'cruise_hire')">
                            <i class="fa fa-ship service-icon-small"></i>
                            <span class="service-text">CRUISE HIRE</span>
                            <input type="checkbox" name="services[]" value="cruise_hire" <?php echo in_array('cruise_hire', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('extras', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'extras')">
                            <i class="fa fa-plus service-icon-small"></i>
                            <span class="service-text">EXTRAS/MISCELLANEOUS</span>
                            <input type="checkbox" name="services[]" value="extras" <?php echo in_array('extras', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('travel_insurance', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'travel_insurance')">
                            <i class="fa fa-shield service-icon-small"></i>
                            <span class="service-text">TRAVEL INSURANCE</span>
                            <input type="checkbox" name="services[]" value="travel_insurance" <?php echo in_array('travel_insurance', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                        
                        <div class="service-item <?php echo in_array('agent_package', $selected_services) ? 'selected' : ''; ?>" onclick="toggleService(this, 'agent_package')">
                            <i class="fa fa-briefcase service-icon-small"></i>
                            <span class="service-text">AGENT PACKAGE SERVICE</span>
                            <input type="checkbox" name="services[]" value="agent_package" <?php echo in_array('agent_package', $selected_services) ? 'checked' : ''; ?> style="display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISA / FLIGHT BOOKING Section -->
            <div id="visa-flight-section" class="services-section" style="display: <?php echo in_array('visa_flight', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-plane"></i> VISA / FLIGHT BOOKING</h5>
                
                <!-- Travel Details -->
                <div class="info-card" style="margin-bottom: 30px;"><br>
                    <h6>Travel Details</h6>
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
                                <td>ARRIVAL</td>
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
                                <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('arrival')"><i class="fa fa-plus"></i></button></td>
                            </tr>
                            <tr id="arrival-connecting" style="display: <?php echo !empty($cost_data['arrival_connecting_date']) ? 'table-row' : 'none'; ?>">
                                <td>ARRIVAL (Connecting)</td>
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
                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('arrival')"><i class="fa fa-minus"></i></button></td>
                            </tr>
                            <tr>
                                <td>DEPARTURE</td>
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
                                <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('departure')"><i class="fa fa-plus"></i></button></td>
                            </tr>
                            <tr id="departure-connecting" style="display: <?php echo !empty($cost_data['departure_connecting_date']) ? 'table-row' : 'none'; ?>">
                                <td>DEPARTURE (Connecting)</td>
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
                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('departure')"><i class="fa fa-minus"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-responsive">
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
                                    <td><input type="text" class="form-control form-control-sm" name="visa[<?php echo $index; ?>][sector]" value="<?php echo htmlspecialchars($visa['sector'] ?? ''); ?>" placeholder="Sector"></td>
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
                                    <td><input type="text" class="form-control form-control-sm" name="visa[0][sector]" placeholder="Sector"></td>
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
                                <td><input type="text" class="form-control form-control-sm" id="visa-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addVisaRow()"><i class="fa fa-plus"></i> Add Visa</button>
                </div>
            </div>

            <!-- ACCOMMODATION Section -->
            <div id="accommodation-section" class="services-section" style="display: <?php echo in_array('accommodation', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-bed"></i> ACCOMMODATION</h5>
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
                                <th>ROOMS RATE</th>
                                <th>EXTRA ADULT NO</th>
                                <th>EXTRA ADULT RATE</th>
                                <th>EXTRA CHILD NO</th>
                                <th>EXTRA CHILD RATE</th>
                                <th>CHILD NO BED NO</th>
                                <th>CHILD NO BED RATE</th>
                                <th>NIGHTS</th>
                                <th>MEAL PLAN</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="accommodation-tbody">
                            <?php if (!empty($accommodation_data) && is_array($accommodation_data)): ?>
                                <?php foreach ($accommodation_data as $index => $accom): ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][destination]">
                                            <option value="">Select Destination</option>
                                            <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                                                <option value="<?php echo htmlspecialchars($dest['name']); ?>" <?php echo ($accom['destination'] == $dest['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dest['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][hotel]">
                                            <option value="">Select Hotel</option>
                                            <option value="ABAAM CHELSEA" <?php echo ($accom['hotel'] == 'ABAAM CHELSEA') ? 'selected' : ''; ?>>ABAAM CHELSEA</option>
                                            <option value="ABAD ATRIUM KOCHI" <?php echo ($accom['hotel'] == 'ABAD ATRIUM KOCHI') ? 'selected' : ''; ?>>ABAD ATRIUM KOCHI</option>
                                            <option value="TAJ MALABAR" <?php echo ($accom['hotel'] == 'TAJ MALABAR') ? 'selected' : ''; ?>>TAJ MALABAR</option>
                                            <option value="VIVANTA MARINE DRIVE" <?php echo ($accom['hotel'] == 'VIVANTA MARINE DRIVE') ? 'selected' : ''; ?>>VIVANTA MARINE DRIVE</option>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][check_in]" value="<?php echo $accom['check_in'] ?? ''; ?>"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][check_out]" value="<?php echo $accom['check_out'] ?? ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][room_type]">
                                            <option value="">Select Room Type</option>
                                            <option value="Single Room" <?php echo ($accom['room_type'] == 'Single Room') ? 'selected' : ''; ?>>Single Room</option>
                                            <option value="Double Room" <?php echo ($accom['room_type'] == 'Double Room') ? 'selected' : ''; ?>>Double Room</option>
                                            <option value="Suite" <?php echo ($accom['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                            <option value="Villa" <?php echo ($accom['room_type'] == 'Villa') ? 'selected' : ''; ?>>Villa</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[<?php echo $index; ?>][rooms_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['rooms_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[<?php echo $index; ?>][rooms_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['rooms_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[<?php echo $index; ?>][extra_adult_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_adult_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[<?php echo $index; ?>][extra_adult_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_adult_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[<?php echo $index; ?>][extra_child_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_child_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[<?php echo $index; ?>][extra_child_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['extra_child_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[<?php echo $index; ?>][child_no_bed_no]" data-row="<?php echo $index; ?>" value="<?php echo $accom['child_no_bed_no'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[<?php echo $index; ?>][child_no_bed_rate]" data-row="<?php echo $index; ?>" value="<?php echo $accom['child_no_bed_rate'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[<?php echo $index; ?>][nights]" data-row="<?php echo $index; ?>" value="<?php echo $accom['nights'] ?? '0'; ?>" onchange="calculateAccommodationTotal(<?php echo $index; ?>)"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[<?php echo $index; ?>][meal_plan]">
                                            <option value="">Select Meal Plan</option>
                                            <option value="Room Only" <?php echo ($accom['meal_plan'] == 'Room Only') ? 'selected' : ''; ?>>Room Only</option>
                                            <option value="Bed and Breakfast" <?php echo ($accom['meal_plan'] == 'Bed and Breakfast') ? 'selected' : ''; ?>>Bed and Breakfast</option>
                                            <option value="Half Board" <?php echo ($accom['meal_plan'] == 'Half Board') ? 'selected' : ''; ?>>Half Board</option>
                                            <option value="All-Inclusive" <?php echo ($accom['meal_plan'] == 'All-Inclusive') ? 'selected' : ''; ?>>All-Inclusive</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $accom['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][destination]">
                                            <option value="">Select Destination</option>
                                            <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                                                <option value="<?php echo htmlspecialchars($dest['name']); ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][hotel]">
                                            <option value="">Select Hotel</option>
                                            <option value="ABAAM CHELSEA">ABAAM CHELSEA</option>
                                            <option value="ABAD ATRIUM KOCHI">ABAD ATRIUM KOCHI</option>
                                            <option value="TAJ MALABAR">TAJ MALABAR</option>
                                            <option value="VIVANTA MARINE DRIVE">VIVANTA MARINE DRIVE</option>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_in]"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_out]"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][room_type]">
                                            <option value="">Select Room Type</option>
                                            <option value="Single Room">Single Room</option>
                                            <option value="Double Room">Double Room</option>
                                            <option value="Suite">Suite</option>
                                            <option value="Villa">Villa</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[0][rooms_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[0][rooms_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[0][extra_adult_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[0][extra_adult_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[0][extra_child_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[0][extra_child_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[0][child_no_bed_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[0][child_no_bed_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[0][nights]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="accommodation[0][meal_plan]">
                                            <option value="">Select Meal Plan</option>
                                            <option value="Room Only">Room Only</option>
                                            <option value="Bed and Breakfast">Bed and Breakfast</option>
                                            <option value="Half Board">Half Board</option>
                                            <option value="All-Inclusive">All-Inclusive</option>
                                        </select>
                                    </td>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAccommodationRow()"><i class="fa fa-plus"></i> Add Accommodation</button>
                </div>
            </div>

            <!-- TRANSPORTATION Section -->
            <div id="transportation-section" class="services-section" style="display: <?php echo in_array('transportation', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-car"></i> INTERNAL TRANSPORTATION</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. NO</th>
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
                                    <td><?php echo $index + 1; ?></td>
                                    <td><input type="text" class="form-control form-control-sm" name="transportation[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($trans['supplier'] ?? ''); ?>" placeholder="Supplier Name"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[<?php echo $index; ?>][car_type]">
                                            <option value="">Select Car Type</option>
                                            <option value="Sedan" <?php echo ($trans['car_type'] == 'Sedan') ? 'selected' : ''; ?>>Sedan</option>
                                            <option value="SUV" <?php echo ($trans['car_type'] == 'SUV') ? 'selected' : ''; ?>>SUV</option>
                                            <option value="Hatchback" <?php echo ($trans['car_type'] == 'Hatchback') ? 'selected' : ''; ?>>Hatchback</option>
                                            <option value="Luxury Car" <?php echo ($trans['car_type'] == 'Luxury Car') ? 'selected' : ''; ?>>Luxury Car</option>
                                            <option value="Mini Bus" <?php echo ($trans['car_type'] == 'Mini Bus') ? 'selected' : ''; ?>>Mini Bus</option>
                                            <option value="Bus" <?php echo ($trans['car_type'] == 'Bus') ? 'selected' : ''; ?>>Bus</option>
                                            <option value="Tempo Traveller" <?php echo ($trans['car_type'] == 'Tempo Traveller') ? 'selected' : ''; ?>>Tempo Traveller</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[<?php echo $index; ?>][daily_rent]" data-row="<?php echo $index; ?>" value="<?php echo $trans['daily_rent'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[<?php echo $index; ?>][days]" data-row="<?php echo $index; ?>" value="<?php echo $trans['days'] ?? '2'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="2"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[<?php echo $index; ?>][km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[<?php echo $index; ?>][extra_km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['extra_km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[<?php echo $index; ?>][price_per_km]" data-row="<?php echo $index; ?>" value="<?php echo $trans['price_per_km'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[<?php echo $index; ?>][toll]" data-row="<?php echo $index; ?>" value="<?php echo $trans['toll'] ?? '0'; ?>" onchange="calculateTransportationTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $trans['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>1</td>
                                    <td><input type="text" class="form-control form-control-sm" name="transportation[0][supplier]" placeholder="Supplier Name"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="transportation[0][car_type]">
                                            <option value="">Select Car Type</option>
                                            <option value="Sedan">Sedan</option>
                                            <option value="SUV">SUV</option>
                                            <option value="Hatchback">Hatchback</option>
                                            <option value="Luxury Car">Luxury Car</option>
                                            <option value="Mini Bus">Mini Bus</option>
                                            <option value="Bus">Bus</option>
                                            <option value="Tempo Traveller">Tempo Traveller</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[0][daily_rent]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[0][days]" data-row="0" value="2" onchange="calculateTransportationTotal(0)" placeholder="2"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[0][km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[0][extra_km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[0][price_per_km]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[0][toll]" data-row="0" value="0" onchange="calculateTransportationTotal(0)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="transportation-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTransportationRow()"><i class="fa fa-plus"></i> Add Transportation</button>
                </div>
            </div>

            <!-- CRUISE HIRE Section -->
            <div id="cruise-hire-section" class="services-section" style="display: <?php echo in_array('cruise_hire', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-ship"></i> CRUISE HIRE</h5>
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
                                    <td><input type="text" class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($cruise['supplier'] ?? ''); ?>" placeholder="Supplier Name"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][boat_type]">
                                            <option value="">Select Boat Type</option>
                                            <option value="Yacht" <?php echo ($cruise['boat_type'] == 'Yacht') ? 'selected' : ''; ?>>Yacht</option>
                                            <option value="Catamaran" <?php echo ($cruise['boat_type'] == 'Catamaran') ? 'selected' : ''; ?>>Catamaran</option>
                                            <option value="Speedboat" <?php echo ($cruise['boat_type'] == 'Speedboat') ? 'selected' : ''; ?>>Speedboat</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="cruise[<?php echo $index; ?>][cruise_type]">
                                            <option value="">Select Cruise Type</option>
                                            <option value="Half Day" <?php echo ($cruise['cruise_type'] == 'Half Day') ? 'selected' : ''; ?>>Half Day</option>
                                            <option value="Full Day" <?php echo ($cruise['cruise_type'] == 'Full Day') ? 'selected' : ''; ?>>Full Day</option>
                                            <option value="Sunset Cruise" <?php echo ($cruise['cruise_type'] == 'Sunset Cruise') ? 'selected' : ''; ?>>Sunset Cruise</option>
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
                                    <td><input type="text" class="form-control form-control-sm" name="cruise[0][supplier]" placeholder="Supplier Name"></td>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCruiseRow()"><i class="fa fa-plus"></i> Add Cruise</button>
                </div>
            </div>

            <!-- AGENT PACKAGE SERVICE Section -->
            <div id="agent-package-section" class="services-section" style="display: <?php echo in_array('agent_package', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-briefcase"></i> AGENT PACKAGE SERVICE</h5>
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
                                        <select class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][destination]">
                                            <option value="">Select Destination</option>
                                            <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                                                <option value="<?php echo htmlspecialchars($dest['name']); ?>" <?php echo ($package['destination'] == $dest['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dest['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="agent_package[<?php echo $index; ?>][agent_supplier]" value="<?php echo htmlspecialchars($package['agent_supplier'] ?? ''); ?>" placeholder="Agent/Supplier" style="width: 120px;"></td>
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
                                        <select class="form-control form-control-sm" name="agent_package[0][destination]">
                                            <option value="">Select Destination</option>
                                            <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                                                <option value="<?php echo htmlspecialchars($dest['name']); ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="agent_package[0][agent_supplier]" placeholder="Agent/Supplier" style="width: 120px;"></td>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAgentPackageRow()"><i class="fa fa-plus"></i> Add Package</button>
                </div>
            </div>

            <!-- EXTRAS/MISCELLANEOUS Section -->
            <div id="extras-section" class="services-section" style="display: <?php echo in_array('extras', $selected_services) ? 'block' : 'none'; ?>;">
                <h5><i class="icon-copy fa fa-plus"></i> EXTRAS/MISCELLANEOUS</h5>
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
                                    <td><input type="text" class="form-control form-control-sm" name="extras[<?php echo $index; ?>][supplier]" value="<?php echo htmlspecialchars($extra['supplier'] ?? ''); ?>" placeholder="Supplier Name"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="extras[<?php echo $index; ?>][service_type]" value="<?php echo htmlspecialchars($extra['service_type'] ?? ''); ?>" placeholder="Type of Service"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[<?php echo $index; ?>][amount]" data-row="<?php echo $index; ?>" value="<?php echo $extra['amount'] ?? '0'; ?>" onchange="calculateExtrasTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[<?php echo $index; ?>][extras]" data-row="<?php echo $index; ?>" value="<?php echo $extra['extras'] ?? '0'; ?>" onchange="calculateExtrasTotal(<?php echo $index; ?>)" placeholder="0"></td>
                                    <td><input type="text" class="form-control form-control-sm extras-total" name="extras[<?php echo $index; ?>][total]" data-row="<?php echo $index; ?>" value="<?php echo $extra['total'] ?? '0.00'; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" name="extras[0][supplier]" placeholder="Supplier Name"></td>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addExtrasRow()"><i class="fa fa-plus"></i> Add Service</button>
                </div>
            </div>

            <!-- Payment and Summary Section -->
            <div class="payment-summary-container" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
                <!-- Payment Details -->
                <div class="services-section" style="flex: 1; min-width: 300px;">
                    <h5><i class="icon-copy fa fa-credit-card"></i> Payment Details</h5>
                    <div class="info-grid" style="padding: 0; margin-top: 20px;">
                        <div class="info-card">
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
                        </div>
                        <div class="info-card">
                            <div class="info-row">
                                <span class="info-label">Total Received:</span>
                                <input type="text" class="form-control form-control-sm" name="total_received" value="<?php echo number_format((float)($payment_data['total_received'] ?? 0), 2, '.', ''); ?>" readonly placeholder="0.00">
                            </div>
                            <div class="info-row">
                                <span class="info-label">Balance Amount:</span>
                                <input type="text" class="form-control form-control-sm" name="balance_amount" value="<?php echo number_format((float)($payment_data['balance_amount'] ?? 0), 2, '.', ''); ?>" readonly placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="services-section" style="flex: 1; min-width: 300px;">
                    <h5><i class="icon-copy fa fa-calculator"></i> Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>TOTAL EXPENSE</strong></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="summary-total-expense" name="total_expense" value="<?php echo $cost_data['total_expense']; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <tr>
                                    <td><strong>MARK UP (PROFIT)</strong></td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" id="markup-percentage" name="markup_percentage" value="<?php echo $cost_data['markup_percentage']; ?>" onchange="calculateSummary()" placeholder="%" style="max-width: 80px;">
                                        <span id="markup-percent-display" style="font-size: 0.8rem; color: #666;"><?php echo $cost_data['markup_percentage']; ?>%</span>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="markup-amount" name="markup_amount" value="<?php echo $cost_data['markup_amount']; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
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
                                    <td><input type="text" class="form-control form-control-sm" id="tax-amount" name="tax_amount" value="<?php echo $cost_data['tax_amount']; ?>" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                                </tr>
                                <tr>
                                    <td><strong>PACKAGE COST</strong></td>
                                    <td></td>
                                    <td><input type="number" class="form-control form-control-sm" id="package-cost" name="package_cost" value="<?php echo $cost_data['package_cost']; ?>" style="background: #e8f5e8; font-weight: bold;" onchange="calculateFromPackageCost()"></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount in</strong></td>
                                    <td><span id="selected-currency" style="font-weight: bold;"><?php echo $cost_data['currency']; ?></span></td>
                                    <td><input type="number" class="form-control form-control-sm" id="currency-rate" name="currency_rate" value="<?php echo $cost_data['currency_rate']; ?>" onchange="calculateSummary()"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="converted-amount" name="converted_amount" value="<?php echo $cost_data['converted_amount']; ?>" readonly style="background: #fff3cd; font-weight: bold;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-modern">
                    <i class="fa fa-save"></i> Update Cost File
                </button>
                <a href="view_cost_sheets.php" class="btn-modern btn-secondary-modern">
                    <i class="fa fa-arrow-left"></i> Back to Cost Files
                </a>
            </div>
        </form>
    </div>
</div>

<script src="edit_cost_file_agent_package.js?v=<?php echo time(); ?>"></script>
<script>
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
    const totalExpense = parseFloat(document.getElementById('summary-total-expense').value) || 0;
    
    // Check if package cost has been manually entered
    const packageCostInput = document.getElementById('package-cost');
    const packageCost = parseFloat(packageCostInput.value) || 0;
    
    if (packageCost > 0) {
        // If package cost is already set, calculate markup from it
        calculateFromPackageCost();
    } else {
        // Otherwise calculate markup from percentage
        const markupPercentage = parseFloat(document.getElementById('markup-percentage').value) || 0;
        const markupAmount = (totalExpense * markupPercentage) / 100;
        document.getElementById('markup-amount').value = markupAmount.toFixed(2);
        document.getElementById('markup-percent-display').textContent = markupPercentage + '%';
        
        // Calculate tax based on tax percentage
        const taxPercentage = parseFloat(document.getElementById('tax-percentage').value) || 0;
        let taxAmount = 0;
        
        // Calculate subtotal (expense + markup)
        const subtotal = totalExpense + markupAmount;
        
        if (taxPercentage === 5) {
            // For 5% tax, apply to the full subtotal
            taxAmount = (subtotal * taxPercentage) / 100;
        } else {
            // For other tax rates, apply only to the markup amount
            taxAmount = (markupAmount * taxPercentage) / 100;
        }
        
        document.getElementById('tax-amount').value = taxAmount.toFixed(2);
        
        // Calculate package cost (expense + markup + tax)
        const newPackageCost = subtotal + taxAmount;
        packageCostInput.value = newPackageCost.toFixed(2);
        
        // Calculate converted amount
        const currencyRate = parseFloat(document.getElementById('currency-rate').value) || 1;
        const convertedAmount = newPackageCost / currencyRate;
        document.getElementById('converted-amount').value = convertedAmount.toFixed(2);
    }
    
    // Update payment calculations
    updatePaymentTotals();
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
        document.getElementById('markup-percent-display').textContent = markupPercentage.toFixed(2) + '%';
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
            <select class="form-control form-control-sm" name="agent_package[${newIndex}][destination]">
                ${destinationsOptions}
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="agent_package[${newIndex}][agent_supplier]" placeholder="Agent/Supplier" style="width: 120px;"></td>
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
    
    // Update payment totals on page load
    updatePaymentTotals();
});
</script>

<?php
require_once "includes/footer.php";
?>