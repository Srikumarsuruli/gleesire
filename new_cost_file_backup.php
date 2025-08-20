<?php
// Include header
require_once "includes/header.php";
require_once "includes/number_generator.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Get enquiry ID from URL
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($enquiry_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid enquiry ID.</div>";
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

// Get enquiry details
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, e.referral_code,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.adults_count, 
        cl.children_count, cl.infants_count, e.created_at as enquiry_date,
        cl.created_at as booking_date, 
        dest.name as destination_name, fm.full_name as file_manager_name 
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        WHERE e.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Enquiry not found.</div>";
    require_once "includes/footer.php";
    exit;
}

$enquiry = mysqli_fetch_assoc($result);

// Initialize variables
$services = [];
$success_message = "";
$error_message = "";

// Generate cost sheet number
$cost_sheet_number = generateNumber('cost_sheet', $conn);

// Get destinations for dropdown
$destinations_sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $destinations_sql);

// Check if editing existing cost file
$existing_cost_data = null;
$is_editing = false;
$check_existing_sql = "SELECT * FROM tour_costings WHERE enquiry_id = ? ORDER BY created_at DESC LIMIT 1";
$check_stmt = mysqli_prepare($conn, $check_existing_sql);
if ($check_stmt) {
    mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    if (mysqli_num_rows($check_result) > 0) {
        $existing_cost_data = mysqli_fetch_assoc($check_result);
        $is_editing = true;
    }
    mysqli_stmt_close($check_stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if tour_costings table exists, if not create it
        $check_table_sql = "SHOW TABLES LIKE 'tour_costings'";
        $table_exists = mysqli_query($conn, $check_table_sql);
        
        // Check if agent_package_data and medical_tourism_data columns exist, if not add them
        $columns_to_check = [
            'agent_package_data' => 'TEXT AFTER extras_data',
            'medical_tourism_data' => 'TEXT AFTER agent_package_data',
            'confirmed' => 'TINYINT(1) DEFAULT 0 AFTER converted_amount'
        ];
        
        foreach($columns_to_check as $column => $definition) {
            $check_column_sql = "SHOW COLUMNS FROM tour_costings LIKE '$column'";
            $column_exists = mysqli_query($conn, $check_column_sql);
            
            if (mysqli_num_rows($column_exists) == 0) {
                $add_column_sql = "ALTER TABLE tour_costings ADD COLUMN $column $definition";
                if (!mysqli_query($conn, $add_column_sql)) {
                    throw new Exception("Failed to add $column column: " . mysqli_error($conn));
                }
            }
        }
        
        if (mysqli_num_rows($table_exists) == 0) {
            // Table doesn't exist, create it
            $create_table_sql = "CREATE TABLE tour_costings (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                enquiry_id INT(11) NOT NULL,
                cost_sheet_number VARCHAR(50),
                guest_name VARCHAR(255),
                guest_address TEXT,
                whatsapp_number VARCHAR(20),
                tour_package VARCHAR(100),
                currency VARCHAR(10),
                nationality VARCHAR(10),
                selected_services TEXT,
                visa_data TEXT,
                accommodation_data TEXT,
                transportation_data TEXT,
                cruise_data TEXT,
                extras_data TEXT,
                agent_package_data TEXT,
                payment_data TEXT,
                total_expense DECIMAL(10,2) DEFAULT 0,
                markup_percentage DECIMAL(5,2) DEFAULT 0,
                markup_amount DECIMAL(10,2) DEFAULT 0,
                tax_percentage DECIMAL(5,2) DEFAULT 18,
                tax_amount DECIMAL(10,2) DEFAULT 0,
                package_cost DECIMAL(10,2) DEFAULT 0,
                currency_rate DECIMAL(10,4) DEFAULT 1,
                converted_amount DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_enquiry_id (enquiry_id)
            )";
            
            if (!mysqli_query($conn, $create_table_sql)) {
                throw new Exception("Table creation failed: " . mysqli_error($conn));
            }
        }
        
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
        
        // Prepare data for insertion
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
        
        // Update children_age_details in converted_leads table
        if (!empty($children_age_details)) {
            $update_children_sql = "UPDATE converted_leads SET children_age_details = ? WHERE enquiry_id = ?";
            $update_children_stmt = mysqli_prepare($conn, $update_children_sql);
            mysqli_stmt_bind_param($update_children_stmt, "si", $children_age_details, $enquiry_id);
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
        $payment_data = json_encode([
            'date' => $_POST['payment_date'] ?? '',
            'bank' => $_POST['payment_bank'] ?? '',
            'amount' => $_POST['payment_amount'] ?? 0,
            'total_received' => $_POST['total_received'] ?? 0,
            'balance_amount' => $_POST['balance_amount'] ?? 0,
            'receipt' => $payment_receipt
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
        
        // Insert into database
        $insert_sql = "INSERT INTO tour_costings (
            enquiry_id, cost_sheet_number, guest_name, guest_address, whatsapp_number,
            tour_package, currency, nationality, adults_count, children_count, infants_count,
            selected_services, visa_data, accommodation_data, transportation_data, 
            cruise_data, extras_data, agent_package_data, medical_tourism_data, payment_data, total_expense, markup_percentage, 
            markup_amount, tax_percentage, tax_amount, package_cost, currency_rate, 
            converted_amount, confirmed
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        // Build parameter array and type string
        $params = [
            $enquiry_id, $cost_sheet_number, $guest_name, $guest_address, 
            $whatsapp_number, $tour_package, $currency, $nationality, $adults_count, 
            $children_count, $infants_count, $selected_services, $visa_data, 
            $accommodation_data, $transportation_data, $cruise_data, $extras_data, 
            $agent_package_data, $medical_tourism_data, $payment_data, $total_expense, $markup_percentage, $markup_amount, 
            $tax_percentage, $tax_amount, $package_cost, $currency_rate, $converted_amount, $confirmed
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
        $type_string[18] = 's'; // medical_tourism_data
        $type_string[19] = 's'; // payment_data
        $type_string[28] = 'i'; // confirmed
        // Rest are 'd' for decimal values
        
        mysqli_stmt_bind_param($stmt, $type_string, ...$params);
        
        if ($is_editing) {
            // Update existing record
            $update_sql = "UPDATE tour_costings SET 
                guest_name = ?, guest_address = ?, whatsapp_number = ?,
                tour_package = ?, currency = ?, nationality = ?, adults_count = ?, children_count = ?, infants_count = ?, selected_services = ?,
                visa_data = ?, accommodation_data = ?, transportation_data = ?,
                cruise_data = ?, extras_data = ?, agent_package_data = ?, medical_tourism_data = ?, payment_data = ?,
                total_expense = ?, markup_percentage = ?, markup_amount = ?,
                tax_percentage = ?, tax_amount = ?, package_cost = ?,
                currency_rate = ?, converted_amount = ?, confirmed = ?, updated_at = CURRENT_TIMESTAMP
                WHERE enquiry_id = ?";
            
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ssssssiiiissssssssddddddddii",
                $guest_name, $guest_address, $whatsapp_number, $tour_package, $currency,
                $nationality, $adults_count, $children_count, $infants_count, $selected_services, $visa_data, $accommodation_data,
                $transportation_data, $cruise_data, $extras_data, $agent_package_data, $medical_tourism_data, $payment_data,
                $total_expense, $markup_percentage, $markup_amount, $tax_percentage,
                $tax_amount, $package_cost, $currency_rate, $converted_amount, $confirmed, $enquiry_id
            );
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success_message = "Cost file updated successfully!";
                $show_success_popup = true;
                
                // Get the cost file ID
                $cost_file_id = $existing_cost_data['id'];
            } else {
                throw new Exception("Database update failed: " . mysqli_error($conn));
            }
            mysqli_stmt_close($update_stmt);
        } else {
            // Insert new record
            if(mysqli_stmt_execute($stmt)) {
                $cost_file_id = mysqli_insert_id($conn);
                $success_message = "Cost file saved successfully to database!";
                $show_success_popup = true;
            } else {
                throw new Exception("Database insertion failed: " . mysqli_error($conn));
            }
        }
        
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
            mysqli_stmt_bind_param($payment_stmt, "issds", $cost_file_id, $payment_date, $payment_bank, $payment_amount, $payment_receipt);
            
            if (!mysqli_stmt_execute($payment_stmt)) {
                throw new Exception("Payment record insertion failed: " . mysqli_error($conn));
            }
            mysqli_stmt_close($payment_stmt);
        }
        
        mysqli_stmt_close($stmt);
        
    } catch(Exception $e) {
        $error_message = "Error saving cost file: " . $e->getMessage();
        $show_error_popup = true;
    }
}

// Decode JSON data for form population (for editing mode)
$selected_services = [];
$visa_data = [];
$accommodation_data = [];
$transportation_data = [];
$cruise_data = [];
$extras_data = [];
$agent_package_data = [];
$medical_tourism_data = [];
$payment_data = [];

if ($is_editing && $existing_cost_data) {
    $selected_services = json_decode($existing_cost_data['selected_services'] ?? '[]', true);
    $visa_data = json_decode($existing_cost_data['visa_data'] ?? '[]', true);
    $accommodation_data = json_decode($existing_cost_data['accommodation_data'] ?? '[]', true);
    $transportation_data = json_decode($existing_cost_data['transportation_data'] ?? '[]', true);
    $cruise_data = json_decode($existing_cost_data['cruise_data'] ?? '[]', true);
    $extras_data = json_decode($existing_cost_data['extras_data'] ?? '[]', true);
    $agent_package_data = json_decode($existing_cost_data['agent_package_data'] ?? '[]', true);
    $medical_tourism_data = json_decode($existing_cost_data['medical_tourism_data'] ?? '[]', true);
    $payment_data = json_decode($existing_cost_data['payment_data'] ?? '{}', true);
    
    // Ensure payment_data is an array
    if (!is_array($payment_data)) {
        $payment_data = [];
    }
    
    // Set default values for NULL PAX counts
    $existing_cost_data['adults_count'] = $existing_cost_data['adults_count'] ?? 0;
    $existing_cost_data['children_count'] = $existing_cost_data['children_count'] ?? 0;
    $existing_cost_data['infants_count'] = $existing_cost_data['infants_count'] ?? 0;
    
    // Always set total_received to the payment amount
    $payment_data['total_received'] = $payment_data['amount'] ?? 0;
    
    // Calculate balance amount based on package cost and total received
    $package_cost = floatval($existing_cost_data['package_cost']);
    $total_received = floatval($payment_data['total_received']);
    $payment_data['balance_amount'] = $package_cost - $total_received;
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="cost_file_styles.css">