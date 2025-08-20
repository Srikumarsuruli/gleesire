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
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<link rel="stylesheet" href="cost_file_styles.css">

<div class="cost-file-container">
    <div class="cost-file-card">
        <div class="cost-file-header">
            <h1 class="cost-file-title">Create Sheet</h1>
            <p class="cost-file-subtitle">Cost Sheet No: <?php echo htmlspecialchars($cost_sheet_number); ?> | Reference: <?php echo htmlspecialchars($enquiry['enquiry_number'] ?? 'N/A'); ?> | Date: <?php echo date('d-m-Y'); ?></p>
            <p  class="cost-file-subtitle">
                Quotation/Booking Date:<?php echo $enquiry['booking_date'] ? date('d-m-Y', strtotime($enquiry['booking_date'])) : 'N/A'; ?> | 
                Enquiry Date: <?php echo $enquiry['enquiry_date'] ? date('d-m-Y', strtotime($enquiry['enquiry_date'])) : 'N/A'; ?> |
                File Manager: <?php echo htmlspecialchars($enquiry['file_manager_name']); ?>
            </p>
            
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

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $enquiry_id); ?>" enctype="multipart/form-data">
            <div class="info-grid">
                <!-- Customer Information -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-user"></i> Customer Information</h5>
                    <div class="info-row">
                        <span class="info-label">Guest Name:</span>
                        <input type="text" class="form-control form-control-sm" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mobile:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['mobile_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['enquiry_number'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ref Code:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['referral_code'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lead Date:</span>
                        <span class="info-value"><?php echo $enquiry['enquiry_date'] ? date('d-m-Y', strtotime($enquiry['enquiry_date'])) : 'N/A'; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Guest Address:</span>
                        <input type="text" class="form-control form-control-sm" name="guest_address">
                    </div>
                    <div class="info-row">
                        <span class="info-label">WhatsApp Number:</span>
                        <input type="text" class="form-control form-control-sm" name="whatsapp_number">
                    </div>
                </div>

                <!-- Travel Information -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-plane"></i> Travel Information</h5>
                    <div class="info-row">
                        <span class="info-label">Travel Destinations:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['destination_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tour Package:</span>
                        <select class="form-control form-control-sm" name="tour_package">
                            <option value="">Select Package</option>
                            <option value="Honeymoon Package">Honeymoon Package</option>
                            <option value="Family Package">Family Package</option>
                            <option value="Adventure Package">Adventure Package</option>
                            <option value="Business Package">Business Package</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Currency:</span>
                        <select class="form-control form-control-sm" id="currency-selector" name="currency" onchange="updateCurrencySymbols()">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="INR">INR</option>
                            <option value="BHD">BHD</option>
                            <option value="KWD">KWD</option>
                            <option value="OMR">OMR</option>
                            <option value="QAR">QAR</option>
                            <option value="SAR">SAR</option>
                            <option value="AED">AED</option>
                            <option value="THB">THB</option>
                            <option value="STD">STD</option>
                            <option value="SGD">SGD</option>
                            <option value="RM">RM</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nationality:</span>
                        <select class="form-control form-control-sm" name="nationality">
                            <option value="">Select Country</option>
                            <option value="AF">Afghanistan</option>
                            <option value="AL">Albania</option>
                            <option value="DZ">Algeria</option>
                            <option value="AS">American Samoa</option>
                            <option value="AD">Andorra</option>
                            <option value="AO">Angola</option>
                            <option value="AI">Anguilla</option>
                            <option value="AQ">Antarctica</option>
                            <option value="AG">Antigua and Barbuda</option>
                            <option value="AR">Argentina</option>
                            <option value="AM">Armenia</option>
                            <option value="AW">Aruba</option>
                            <option value="AU">Australia</option>
                            <option value="AT">Austria</option>
                            <option value="AZ">Azerbaijan</option>
                            <option value="BS">Bahamas</option>
                            <option value="BH">Bahrain</option>
                            <option value="BD">Bangladesh</option>
                            <option value="BB">Barbados</option>
                            <option value="BY">Belarus</option>
                            <option value="BE">Belgium</option>
                            <option value="BZ">Belize</option>
                            <option value="BJ">Benin</option>
                            <option value="BM">Bermuda</option>
                            <option value="BT">Bhutan</option>
                            <option value="BO">Bolivia</option>
                            <option value="BA">Bosnia and Herzegovina</option>
                            <option value="BW">Botswana</option>
                            <option value="BR">Brazil</option>
                            <option value="BN">Brunei</option>
                            <option value="BG">Bulgaria</option>
                            <option value="BF">Burkina Faso</option>
                            <option value="BI">Burundi</option>
                            <option value="KH">Cambodia</option>
                            <option value="CM">Cameroon</option>
                            <option value="CA">Canada</option>
                            <option value="CV">Cape Verde</option>
                            <option value="KY">Cayman Islands</option>
                            <option value="CF">Central African Republic</option>
                            <option value="TD">Chad</option>
                            <option value="CL">Chile</option>
                            <option value="CN">China</option>
                            <option value="CO">Colombia</option>
                            <option value="KM">Comoros</option>
                            <option value="CG">Congo</option>
                            <option value="CD">Congo (Democratic Republic)</option>
                            <option value="CR">Costa Rica</option>
                            <option value="CI">Cote d'Ivoire</option>
                            <option value="HR">Croatia</option>
                            <option value="CU">Cuba</option>
                            <option value="CY">Cyprus</option>
                            <option value="CZ">Czech Republic</option>
                            <option value="DK">Denmark</option>
                            <option value="DJ">Djibouti</option>
                            <option value="DM">Dominica</option>
                            <option value="DO">Dominican Republic</option>
                            <option value="EC">Ecuador</option>
                            <option value="EG">Egypt</option>
                            <option value="SV">El Salvador</option>
                            <option value="GQ">Equatorial Guinea</option>
                            <option value="ER">Eritrea</option>
                            <option value="EE">Estonia</option>
                            <option value="ET">Ethiopia</option>
                            <option value="FJ">Fiji</option>
                            <option value="FI">Finland</option>
                            <option value="FR">France</option>
                            <option value="GA">Gabon</option>
                            <option value="GM">Gambia</option>
                            <option value="GE">Georgia</option>
                            <option value="DE">Germany</option>
                            <option value="GH">Ghana</option>
                            <option value="GR">Greece</option>
                            <option value="GD">Grenada</option>
                            <option value="GT">Guatemala</option>
                            <option value="GN">Guinea</option>
                            <option value="GW">Guinea-Bissau</option>
                            <option value="GY">Guyana</option>
                            <option value="HT">Haiti</option>
                            <option value="HN">Honduras</option>
                            <option value="HK">Hong Kong</option>
                            <option value="HU">Hungary</option>
                            <option value="IS">Iceland</option>
                            <option value="IN">India</option>
                            <option value="ID">Indonesia</option>
                            <option value="IR">Iran</option>
                            <option value="IQ">Iraq</option>
                            <option value="IE">Ireland</option>
                            <option value="IL">Israel</option>
                            <option value="IT">Italy</option>
                            <option value="JM">Jamaica</option>
                            <option value="JP">Japan</option>
                            <option value="JO">Jordan</option>
                            <option value="KZ">Kazakhstan</option>
                            <option value="KE">Kenya</option>
                            <option value="KI">Kiribati</option>
                            <option value="KP">Korea (North)</option>
                            <option value="KR">Korea (South)</option>
                            <option value="KW">Kuwait</option>
                            <option value="KG">Kyrgyzstan</option>
                            <option value="LA">Laos</option>
                            <option value="LV">Latvia</option>
                            <option value="LB">Lebanon</option>
                            <option value="LS">Lesotho</option>
                            <option value="LR">Liberia</option>
                            <option value="LY">Libya</option>
                            <option value="LI">Liechtenstein</option>
                            <option value="LT">Lithuania</option>
                            <option value="LU">Luxembourg</option>
                            <option value="MO">Macao</option>
                            <option value="MK">Macedonia</option>
                            <option value="MG">Madagascar</option>
                            <option value="MW">Malawi</option>
                            <option value="MY">Malaysia</option>
                            <option value="MV">Maldives</option>
                            <option value="ML">Mali</option>
                            <option value="MT">Malta</option>
                            <option value="MH">Marshall Islands</option>
                            <option value="MR">Mauritania</option>
                            <option value="MU">Mauritius</option>
                            <option value="MX">Mexico</option>
                            <option value="FM">Micronesia</option>
                            <option value="MD">Moldova</option>
                            <option value="MC">Monaco</option>
                            <option value="MN">Mongolia</option>
                            <option value="ME">Montenegro</option>
                            <option value="MA">Morocco</option>
                            <option value="MZ">Mozambique</option>
                            <option value="MM">Myanmar</option>
                            <option value="NA">Namibia</option>
                            <option value="NR">Nauru</option>
                            <option value="NP">Nepal</option>
                            <option value="NL">Netherlands</option>
                            <option value="NZ">New Zealand</option>
                            <option value="NI">Nicaragua</option>
                            <option value="NE">Niger</option>
                            <option value="NG">Nigeria</option>
                            <option value="NO">Norway</option>
                            <option value="OM">Oman</option>
                            <option value="PK">Pakistan</option>
                            <option value="PW">Palau</option>
                            <option value="PS">Palestine</option>
                            <option value="PA">Panama</option>
                            <option value="PG">Papua New Guinea</option>
                            <option value="PY">Paraguay</option>
                            <option value="PE">Peru</option>
                            <option value="PH">Philippines</option>
                            <option value="PL">Poland</option>
                            <option value="PT">Portugal</option>
                            <option value="QA">Qatar</option>
                            <option value="RO">Romania</option>
                            <option value="RU">Russia</option>
                            <option value="RW">Rwanda</option>
                            <option value="KN">Saint Kitts and Nevis</option>
                            <option value="LC">Saint Lucia</option>
                            <option value="VC">Saint Vincent and the Grenadines</option>
                            <option value="WS">Samoa</option>
                            <option value="SM">San Marino</option>
                            <option value="ST">Sao Tome and Principe</option>
                            <option value="SA">Saudi Arabia</option>
                            <option value="SN">Senegal</option>
                            <option value="RS">Serbia</option>
                            <option value="SC">Seychelles</option>
                            <option value="SL">Sierra Leone</option>
                            <option value="SG">Singapore</option>
                            <option value="SK">Slovakia</option>
                            <option value="SI">Slovenia</option>
                            <option value="SB">Solomon Islands</option>
                            <option value="SO">Somalia</option>
                            <option value="ZA">South Africa</option>
                            <option value="SS">South Sudan</option>
                            <option value="ES">Spain</option>
                            <option value="LK">Sri Lanka</option>
                            <option value="SD">Sudan</option>
                            <option value="SR">Suriname</option>
                            <option value="SZ">Swaziland</option>
                            <option value="SE">Sweden</option>
                            <option value="CH">Switzerland</option>
                            <option value="SY">Syria</option>
                            <option value="TW">Taiwan</option>
                            <option value="TJ">Tajikistan</option>
                            <option value="TZ">Tanzania</option>
                            <option value="TH">Thailand</option>
                            <option value="TL">Timor-Leste</option>
                            <option value="TG">Togo</option>
                            <option value="TO">Tonga</option>
                            <option value="TT">Trinidad and Tobago</option>
                            <option value="TN">Tunisia</option>
                            <option value="TR">Turkey</option>
                            <option value="TM">Turkmenistan</option>
                            <option value="TV">Tuvalu</option>
                            <option value="UG">Uganda</option>
                            <option value="UA">Ukraine</option>
                            <option value="AE">United Arab Emirates</option>
                            <option value="GB">United Kingdom</option>
                            <option value="US">United States</option>
                            <option value="UY">Uruguay</option>
                            <option value="UZ">Uzbekistan</option>
                            <option value="VU">Vanuatu</option>
                            <option value="VA">Vatican City</option>
                            <option value="VE">Venezuela</option>
                            <option value="VN">Vietnam</option>
                            <option value="YE">Yemen</option>
                            <option value="ZM">Zambia</option>
                            <option value="ZW">Zimbabwe</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['department_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Source:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['source_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">File Manager:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['file_manager_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Passenger Information -->
                <div class="info-card">
                    <h5><i class="icon-copy fa fa-users"></i> Number of PAX</h5>
                    <div class="info-row">
                        <span class="info-label">Adults:</span>
                        <span class="info-value"><?php echo intval($enquiry['adults_count'] ?? 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children:</span>
                        <span class="info-value"><?php echo intval($enquiry['children_count'] ?? 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Infants:</span>
                        <span class="info-value"><?php echo intval($enquiry['infants_count'] ?? 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total PAX:</span>
                        <span class="info-value">
                            <?php 
                            $total_pax = intval($enquiry['adults_count'] ?? 0) + intval($enquiry['children_count'] ?? 0) + intval($enquiry['infants_count'] ?? 0);
                            echo $total_pax;
                            ?>
                        </span>
                    </div>
                    
                </div>
                <div class="info-card" style="">
                    <h5><i class="icon-copy fa fa-cogs"></i> Select Services</h5>
                    <div class="services-list">
                        <div class="service-item" onclick="toggleService(this, 'visa_flight')">
                            <i class="fa fa-plane service-icon-small"></i>
                            <span class="service-text">VISA / FLIGHT BOOKING</span>
                            <input type="checkbox" name="services[]" value="visa_flight" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'accommodation')">
                            <i class="fa fa-bed service-icon-small"></i>
                            <span class="service-text">ACCOMMODATION</span>
                            <input type="checkbox" name="services[]" value="accommodation" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'transportation')">
                            <i class="fa fa-car service-icon-small"></i>
                            <span class="service-text">TRANSPORTATION</span>
                            <input type="checkbox" name="services[]" value="transportation" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'cruise_hire')">
                            <i class="fa fa-ship service-icon-small"></i>
                            <span class="service-text">CRUISE HIRE</span>
                            <input type="checkbox" name="services[]" value="cruise_hire" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'extras')">
                            <i class="fa fa-plus service-icon-small"></i>
                            <span class="service-text">EXTRAS/MISCELLANEOUS</span>
                            <input type="checkbox" name="services[]" value="extras" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'travel_insurance')">
                            <i class="fa fa-shield service-icon-small"></i>
                            <span class="service-text">TRAVEL INSURANCE</span>
                            <input type="checkbox" name="services[]" value="travel_insurance" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'agent_package')">
                            <i class="fa fa-briefcase service-icon-small"></i>
                            <span class="service-text">AGENT PACKAGE SERVICE</span>
                            <input type="checkbox" name="services[]" value="agent_package" style="display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Selection -->
         

            <!-- VISA / FLIGHT BOOKING Section -->
            <div id="visa-flight-section" class="services-section" style="display: none;">
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
                                <td><input type="date" class="form-control form-control-sm" name="arrival_date" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="arrival_city" placeholder="City"></td>
                                <td><input type="text" class="form-control form-control-sm" name="arrival_flight" placeholder="Flight No"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="arrival_nights_days">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="arrival_connection">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('arrival')"><i class="fa fa-plus"></i></button></td>
                            </tr>
                            <tr id="arrival-connecting" style="display: none;">
                                <td>ARRIVAL (Connecting)</td>
                                <td><input type="date" class="form-control form-control-sm" name="arrival_connecting_date"></td>
                                <td><input type="text" class="form-control form-control-sm" name="arrival_connecting_city" placeholder="City"></td>
                                <td><input type="text" class="form-control form-control-sm" name="arrival_connecting_flight" placeholder="Flight No"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="arrival_connecting_nights_days">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="arrival_connecting_type">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('arrival')"><i class="fa fa-minus"></i></button></td>
                            </tr>
                            <tr>
                                <td>DEPARTURE</td>
                                <td><input type="date" class="form-control form-control-sm" name="departure_date" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="departure_city" placeholder="City"></td>
                                <td><input type="text" class="form-control form-control-sm" name="departure_flight" placeholder="Flight No"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="departure_nights_days">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="departure_connection">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-info" onclick="addConnectingFlight('departure')"><i class="fa fa-plus"></i></button></td>
                            </tr>
                            <tr id="departure-connecting" style="display: none;">
                                <td>DEPARTURE (Connecting)</td>
                                <td><input type="date" class="form-control form-control-sm" name="departure_connecting_date"></td>
                                <td><input type="text" class="form-control form-control-sm" name="departure_connecting_city" placeholder="City"></td>
                                <td><input type="text" class="form-control form-control-sm" name="departure_connecting_flight" placeholder="Flight No"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="departure_connecting_nights_days">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="departure_connecting_type">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeConnectingFlight('departure')"><i class="fa fa-minus"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Visa Details -->
                <div class="info-card">
                    <h6> VISA / FLIGHT DETAILS</h6>
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. No</th>
                                <th>SECTOR</th>
                                <th>SUPPLIER</th>
                                <th>DATE of TRAVELS</th>
                                <th>NO OF PASSENGER</th>
                                <th>RATE / PERSON</th>
                                <th>ROE</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="visa-details-tbody">
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
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6"></td>
                                <td><strong>TOTAL</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="visa-grand-total" readonly></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addVisaRow()"><i class="fa fa-plus"></i> Add Row</button>
                </div>
            </div>

            <!-- ACCOMMODATION Section -->
            <div id="accommodation-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-bed"></i>ACCOMMODATION
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAccommodationRow()"><i class="fa fa-plus"></i> Add Hotel</button>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th rowspan="2">DESTINATION</th>
                                <th rowspan="2">HOTEL</th>
                                <th rowspan="2">CHECK IN</th>
                                <th rowspan="2">CHECK OUT</th>
                                <th rowspan="2">ROOM TYPE</th>
                                <th colspan="2">ROOMS</th>
                                <th colspan="2">EXTRA BED ADULT</th>
                                <th colspan="2">EXTRA BED CHILD</th>
                                <th colspan="2">CHILD NO BED</th>
                                <th rowspan="2">Nights</th>
                                <th rowspan="2">Meal Plan</th>
                                <th rowspan="2">Total</th>
                            </tr>
                            <tr>
                                <th>NO</th>
                                <th>RATE</th>
                                <th>NO</th>
                                <th>RATE/bed</th>
                                <th>NO</th>
                                <th>RATE</th>
                                <th>NO</th>
                                <th>RATE</th>
                            </tr>
                        </thead>
                        <tbody id="accommodation-tbody">
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
                                        <option value="ABAD FORT HOTEL CHULIKKAL FORTKOCHI">ABAD FORT HOTEL CHULIKKAL FORTKOCHI</option>
                                        <option value="ABAD PLAZA">ABAD PLAZA</option>
                                        <option value="AIRLINK CASTLE AIRPORT">AIRLINK CASTLE AIRPORT</option>
                                        <option value="ARTISTE">ARTISTE</option>
                                        <option value="BROADBEAN">BROADBEAN</option>
                                        <option value="CLARION HOTEL KHAYAL">CLARION HOTEL KHAYAL</option>
                                        <option value="COURTYARD MARRIOT KOCHI AIRPORT">COURTYARD MARRIOT KOCHI AIRPORT</option>
                                        <option value="CROWNE PLAZA">CROWNE PLAZA</option>
                                        <option value="DIANA HEIGHTS AIRPORT">DIANA HEIGHTS AIRPORT</option>
                                        <option value="DUTCH BUNGALOW FORTKOCHI">DUTCH BUNGALOW FORTKOCHI</option>
                                        <option value="DUTCH MANOR GREEN ROUTES">DUTCH MANOR GREEN ROUTES</option>
                                        <option value="ELITE PALAZO ANGAMALI">ELITE PALAZO ANGAMALI</option>
                                        <option value="FLORA AIRPORT HOTEL">FLORA AIRPORT HOTEL</option>
                                        <option value="FLORIDA (AIRPORT BUDGET)">FLORIDA (AIRPORT BUDGET)</option>
                                        <option value="FRAGRANT NATURE">FRAGRANT NATURE</option>
                                        <option value="HOLIDAY INN">HOLIDAY INN</option>
                                        <option value="HYATT KOCHI">HYATT KOCHI</option>
                                        <option value="IMA HOUSE">IMA HOUSE</option>
                                        <option value="KEYS KOCHI">KEYS KOCHI</option>
                                        <option value="LE MERIDIAN">LE MERIDIAN</option>
                                        <option value="LUXO">LUXO</option>
                                        <option value="MARRIOT KOCHI">MARRIOT KOCHI</option>
                                        <option value="MONSOON EMPRESS">MONSOON EMPRESS</option>
                                        <option value="NOAH SKY SUITES AIRPORT">NOAH SKY SUITES AIRPORT</option>
                                        <option value="NORTH CENTRE">NORTH CENTRE</option>
                                        <option value="NOVOTEL">NOVOTEL</option>
                                        <option value="OLD COURT HOUSE FORTKOCHI BY ABAD">OLD COURT HOUSE FORTKOCHI BY ABAD</option>
                                        <option value="OLIVE DOWN TOWN">OLIVE DOWN TOWN</option>
                                        <option value="PORT MUZIRIS">PORT MUZIRIS</option>
                                        <option value="RADISSON BLU">RADISSON BLU</option>
                                        <option value="RAMADA">RAMADA</option>
                                        <option value="SARA AIRPORT HOTEL">SARA AIRPORT HOTEL</option>
                                        <option value="SEALORD HOTEL COCHIN">SEALORD HOTEL COCHIN</option>
                                        <option value="SHERATON">SHERATON</option>
                                        <option value="SHILTON INTERNATIONAL">SHILTON INTERNATIONAL</option>
                                        <option value="STARLIT SUITES">STARLIT SUITES</option>
                                        <option value="SUGAR">SUGAR</option>
                                        <option value="TAJ AIRPORT">TAJ AIRPORT</option>
                                        <option value="TAJ MALABAR">TAJ MALABAR</option>
                                        <option value="THARAVADU KOCHI BY LEDD">THARAVADU KOCHI BY LEDD</option>
                                        <option value="VIVANTA MARINE DRIVE">VIVANTA MARINE DRIVE</option>
                                    </select>
                                </td>
                                <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_in]" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_out]" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][room_type]">
                                        <option value="">Select Room Type</option>
                                        <option value="Single Room">Single Room</option>
                                        <option value="Double Room">Double Room</option>
                                        <option value="Twin Room">Twin Room</option>
                                        <option value="Triple Room">Triple Room</option>
                                        <option value="Quad Room">Quad Room</option>
                                        <option value="Deluxe Room">Deluxe Room</option>
                                        <option value="Superior Room">Superior Room</option>
                                        <option value="Executive Room">Executive Room</option>
                                        <option value="Junior Suite">Junior Suite</option>
                                        <option value="Suite">Suite</option>
                                        <option value="Executive Suite">Executive Suite</option>
                                        <option value="Presidential Suite">Presidential Suite</option>
                                        <option value="Penthouse Suite">Penthouse Suite</option>
                                        <option value="Villa">Villa</option>
                                        <option value="Bungalow">Bungalow</option>
                                        <option value="Cabana">Cabana</option>
                                        <option value="Swim-up Room">Swim-up Room</option>
                                        <option value="Garden View Room">Garden View Room</option>
                                        <option value="Ocean View / Sea View Room">Ocean View / Sea View Room</option>
                                        <option value="Oceanfront Room">Oceanfront Room</option>
                                        <option value="Mountain View Room">Mountain View Room</option>
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
                                        <option value="Full Board">Full Board</option>
                                        <option value="All-Inclusive">All-Inclusive</option>
                                        <option value="Ultra All-Inclusive">Ultra All-Inclusive</option>
                                        <option value="Breakfast and Dinner">Breakfast and Dinner</option>
                                        <option value="Breakfast and Lunch">Breakfast and Lunch</option>
                                        <option value="European Plan">European Plan</option>
                                        <option value="American Plan">American Plan</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                            </tr>
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
            <div id="transportation-section" class="services-section" style="display: none;">
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
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="transportation-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTransportationRow()"><i class="fa fa-plus"></i> Add Vehicle</button>
                </div>
            </div>

            <!-- CRUISE HIRE Section -->
            <div id="cruise-hire-section" class="services-section" style="display: none;">
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
                            <tr>
                                <td>1</td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise[0][supplier]" placeholder="Supplier Name"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="cruise[0][boat_type]">
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
                                    <select class="form-control form-control-sm" name="cruise[0][cruise_type]">
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
                                <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[0][check_in]"></td>
                                <td><input type="datetime-local" class="form-control form-control-sm" name="cruise[0][check_out]"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise[0][rate]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise[0][extra]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                            </tr>
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
            <div id="agent-package-section" class="services-section" style="display: none;">
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
                            <tr>
                                <td>
                                    <select class="form-control form-control-sm" name="agent_package[0][destination]">
                                        <option value="">Select Destination</option>
                                        <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                                            <option value="<?php echo htmlspecialchars($dest['name']); ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="agent_package[0][agent_supplier]" placeholder="Agent/Supplier"></td>
                                <td><input type="date" class="form-control form-control-sm" name="agent_package[0][start_date]" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="agent_package[0][end_date]" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[0][adult_count]" data-row="0" value="<?php echo intval($enquiry['adults_count'] ?? 0); ?>" style="width: 70px;"></td>
                                <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[0][adult_price]" data-row="0" value="0" onchange="calculateAgentPackageTotal(0)" style="width: 100px;"></td>
                                <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[0][child_count]" data-row="0" value="<?php echo intval($enquiry['children_count'] ?? 0); ?>" style="width: 70px;"></td>
                                <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[0][child_price]" data-row="0" value="0" onchange="calculateAgentPackageTotal(0)" style="width: 100px;"></td>
                                <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[0][infant_count]" data-row="0" value="<?php echo intval($enquiry['infants_count'] ?? 0); ?>" style="width: 70px;"></td>
                                <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[0][infant_price]" data-row="0" value="0" onchange="calculateAgentPackageTotal(0)" style="width: 100px;"></td>
                                <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
                            </tr>
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
            <div id="extras-section" class="services-section" style="display: none;">
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
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" name="extras[0][supplier]" placeholder="Supplier Name"></td>
                                <td><input type="text" class="form-control form-control-sm" name="extras[0][service_type]" placeholder="Type of Service"></td>
                                <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[0][amount]" data-row="0" value="0" onchange="calculateExtrasTotal(0)" placeholder="0"></td>
                                <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[0][extras]" data-row="0" value="0" onchange="calculateExtrasTotal(0)" placeholder="0"></td>
                                <td><input type="text" class="form-control form-control-sm extras-total" name="extras[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                            </tr>
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
                                <input type="date" class="form-control form-control-sm" name="payment_date">
                            </div>
                            <div class="info-row">
                                <span class="info-label">Bank:</span>
                                <select class="form-control form-control-sm" name="payment_bank">
                                    <option value="">Select Bank</option>
                                    <option value="HDFC BANK">HDFC BANK</option>
                                    <option value="ICICI BANK">ICICI BANK</option>
                                </select>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Amount:</span>
                                <input type="number" class="form-control form-control-sm" name="payment_amount" placeholder="0.00">
                            </div>
                            <div class="info-row">
                                <span class="info-label">Receipt:</span>
                                <input type="file" class="form-control form-control-sm" name="payment_receipt" accept="image/*">
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-row">
                                <span class="info-label">Total Received:</span>
                                <input type="text" class="form-control form-control-sm" name="total_received" readonly placeholder="0.00">
                            </div>
                            <div class="info-row">
                                <span class="info-label">Balance Amount:</span>
                                <input type="text" class="form-control form-control-sm" name="balance_amount" readonly placeholder="0.00">
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
                                    <td><strong>Amount in</strong></td>
                                    <td>
                                        <span id="selected-currency" style="font-weight: bold;">USD</span>
                                        <span style="margin-left: 10px; font-size: 0.8rem; color: #666;">AUTO from Currency in top</span>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm" id="currency-rate" name="currency_rate" value="82" onchange="calculateSummary()" placeholder="1.00"></td>
                                </tr>
                                                 
                                <tr>
                                    <td><strong>MARK UP (PROFIT)</strong></td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" id="markup-percentage" name="markup_percentage" value="" onchange="calculateSummary()" placeholder="%" style="max-width: 80px;">  
                                        <span id="markup-percent-display" style="font-size: 0.8rem; color: #666;"></span>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="markup-amount" name="markup_amount" readonly style="background: #f0f8ff; font-weight: bold;" placeholder="0.00"></td>
                                </tr>
                                <tr>
                                    <td><strong>SERVICE TAX</strong></td>
                                    <td>
                                        <select class="form-control form-control-sm" id="tax-percentage" name="tax_percentage" onchange="calculateSummary()" style="max-width: 80px;">
                                            <option value="0">0%</option>
                                            <option value="5">5%</option>
                                            <option value="18" selected>18%</option>
                                            <option value="1.05">1.05%</option>
                                            <option value="1.18">1.18%</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="tax-amount" name="tax_amount" readonly style="background: #f0f8ff; font-weight: bold;" placeholder="0.00"></td>
                                </tr>
                                <tr>
                                    <td><strong>TOTAL EXPENSE</strong></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="summary-total-expense" name="total_expense" readonly style="background: #f0f8ff; font-weight: bold;" placeholder="0.00"></td>
                                </tr>
                                <tr>
                                    <td><strong>PACKAGE COST</strong></td>
                                    <td></td>
                                    <td><input type="number" class="form-control form-control-sm" id="package-cost" name="package_cost" style="background: #e8f5e8; font-weight: bold;" placeholder="0.00" onchange="calculateFromPackageCost()"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-modern" id="save-cost-file-btn" onclick="return confirmSave()">
                    <i class="fa fa-save"></i> Save Cost File
                </button>
                <a href="view_leads.php" class="btn-modern btn-secondary-modern">
                    <i class="fa fa-arrow-left"></i> Back to Leads
                </a>
            </div>
        </form>
    </div>
</div>

<script src="agent_package_section.js?v=<?php echo time(); ?>"></script>
<!-- Include in edit_cost_file.php as well -->
<script>
// Confirmation popup for saving cost sheet
function confirmSave() {
    return confirm("Do you want to save the cost sheet?");
}
function toggleService(item, serviceName) {
    const checkbox = item.querySelector('input[type="checkbox"]');
    const isSelected = item.classList.contains('selected');
    
    if (isSelected) {
        item.classList.remove('selected');
        checkbox.checked = false;
        // Hide service section
        const sectionId = serviceName.replace('_', '-') + '-section';
        const section = document.getElementById(sectionId);
        if (section) section.style.display = 'none';
    } else {
        item.classList.add('selected');
        checkbox.checked = true;
        // Show service section
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

// Currency symbol mapping
const currencySymbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'INR': '₹',
    'BHD': 'BD',
    'KWD': 'KD',
    'OMR': 'OMR',
    'QAR': 'QR',
    'SAR': 'SR',
    'AED': 'AED',
    'GCC': 'GCC',
    'THB': '฿',
    'STD': 'Db',
    'SGD': 'S$',
    'RM': 'RM'
};

function updateCurrencySymbols() {
    const selectedCurrency = document.getElementById('currency-selector').value;
    const symbol = currencySymbols[selectedCurrency] || selectedCurrency;
    
    // Update summary section currency display
    document.getElementById('selected-currency').textContent = selectedCurrency;
    
    // Update ALL amount input placeholders throughout the form
    const allAmountInputs = document.querySelectorAll(
        'input[type="number"], input[type="text"][readonly], ' +
        'input[placeholder="0"], input[placeholder="0.00"], ' +
        '.visa-rate, .visa-total, .accom-total, .trans-total, ' +
        '.cruise-rate, .cruise-total, .extras-amount, .extras-total, ' +
        '#visa-grand-total, #accommodation-grand-total, #transportation-grand-total, ' +
        '#cruise-grand-total, #extras-grand-total, #summary-total-expense, ' +
        '#markup-amount, #tax-amount, #package-cost, #converted-amount'
    );
    
    allAmountInputs.forEach(input => {
        // Update placeholders
        if (input.placeholder === '0' || input.placeholder === '0.00' || input.placeholder.includes('0.00')) {
            input.placeholder = symbol + ' 0.00';
        }
        
        // Update existing values with currency symbol if they have values
        if (input.value && input.value !== '0' && input.value !== '0.00') {
            const numValue = parseFloat(input.value.replace(/[^0-9.-]/g, ''));
            if (!isNaN(numValue)) {
                input.value = symbol + ' ' + numValue.toFixed(2);
            }
        }
    });
    
    // Update table headers that show "RATE", "TOTAL", "AMOUNT" etc.
    const tableHeaders = document.querySelectorAll('th');
    tableHeaders.forEach(header => {
        if (header.textContent.includes('RATE') || 
            header.textContent.includes('TOTAL') || 
            header.textContent.includes('AMOUNT') ||
            header.textContent.includes('EXTRA')) {
            if (!header.textContent.includes('(' + symbol + ')')) {
                header.innerHTML = header.textContent.replace(/\([^)]*\)/g, '') + ' (' + symbol + ')';
            }
        }
    });
    
    // Update grand total labels
    const totalLabels = document.querySelectorAll('strong');
    totalLabels.forEach(label => {
        if (label.textContent.includes('TOTAL') && label.textContent.includes('COST')) {
            if (!label.textContent.includes('(' + symbol + ')')) {
                label.innerHTML = label.textContent.replace(/\([^)]*\)/g, '') + ' (' + symbol + ')';
            }
        }
    });
    
    // Store current currency for future calculations
    window.currentCurrency = selectedCurrency;
    window.currentCurrencySymbol = symbol;
    
    // Recalculate summary to update currency display
    if (typeof calculateSummary === 'function') {
        calculateSummary();
    }
}

// Initialize currency symbols on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCurrencySymbols();
    
    <?php if ($is_editing && $existing_cost_data): ?>
    // Populate form with existing data
    populateExistingData();
    <?php endif; ?>
});

<?php if ($is_editing && $existing_cost_data): ?>
function populateExistingData() {
    console.log('Populating existing data...');
    console.log('Cost data:', <?php echo json_encode($existing_cost_data); ?>);
    
    // Populate basic fields
    const guestName = document.querySelector('input[name="guest_name"]');
    if (guestName) {
        guestName.value = '<?php echo addslashes($existing_cost_data['guest_name'] ?? ''); ?>';
        console.log('Guest name set to:', guestName.value);
    }
    
    const guestAddress = document.querySelector('input[name="guest_address"]');
    if (guestAddress) {
        guestAddress.value = '<?php echo addslashes($existing_cost_data['guest_address'] ?? ''); ?>';
        console.log('Guest address set to:', guestAddress.value);
    }
    
    const whatsappNumber = document.querySelector('input[name="whatsapp_number"]');
    if (whatsappNumber) {
        whatsappNumber.value = '<?php echo addslashes($existing_cost_data['whatsapp_number'] ?? ''); ?>';
        console.log('WhatsApp set to:', whatsappNumber.value);
    }
    
    const tourPackage = document.querySelector('select[name="tour_package"]');
    if (tourPackage) {
        tourPackage.value = '<?php echo addslashes($existing_cost_data['tour_package'] ?? ''); ?>';
        console.log('Tour package set to:', tourPackage.value);
    }
    
    const currency = document.querySelector('select[name="currency"]');
    if (currency) {
        currency.value = '<?php echo addslashes($existing_cost_data['currency'] ?? 'USD'); ?>';
        console.log('Currency set to:', currency.value);
        updateCurrencySymbols();
    }
    
    const nationality = document.querySelector('select[name="nationality"]');
    if (nationality) {
        nationality.value = '<?php echo addslashes($existing_cost_data['nationality'] ?? ''); ?>';
        console.log('Nationality set to:', nationality.value);
    }
    
    // Populate selected services
    console.log('Populating services...');
    <?php 
    $selected_services = json_decode($existing_cost_data['selected_services'] ?? '[]', true);
    if (is_array($selected_services)) {
        foreach ($selected_services as $service) {
            echo "console.log('Setting service: {$service}');\n";
            echo "const service_{$service} = document.querySelector('input[value=\"{$service}\"]');\n";
            echo "if (service_{$service}) {\n";
            echo "    service_{$service}.checked = true;\n";
            echo "    service_{$service}.closest('.service-item').classList.add('selected');\n";
            echo "    const sectionId = '{$service}'.replace('_', '-') + '-section';\n";
            echo "    const section = document.getElementById(sectionId);\n";
            echo "    if (section) {\n";
            echo "        section.style.display = 'block';\n";
            echo "        console.log('Showing section:', sectionId);\n";
            echo "    }\n";
            echo "} else {\n";
            echo "    console.log('Service checkbox not found for: {$service}');\n";
            echo "}\n";
        }
    }
    ?>
    
    // Populate service data with delay to ensure DOM is ready
    setTimeout(() => {
        console.log('Populating service data...');
        
        // Populate VISA data if visa_flight service is selected
        <?php 
        $visa_data = json_decode($existing_cost_data['visa_data'] ?? '[]', true);
        if (is_array($visa_data) && !empty($visa_data) && isset($visa_data[0])) {
            $visa = $visa_data[0]; // Get first visa entry
            echo "const visaSector = document.querySelector('input[name=\"visa[0][sector]\"]');\n";
            echo "if (visaSector) visaSector.value = '" . addslashes($visa['sector'] ?? '') . "';\n";
            echo "const visaSupplier = document.querySelector('input[name=\"visa[0][supplier]\"]');\n";
            echo "if (visaSupplier) visaSupplier.value = '" . addslashes($visa['supplier'] ?? '') . "';\n";
            echo "const visaDate = document.querySelector('input[name=\"visa[0][travel_date]\"]');\n";
            echo "if (visaDate) visaDate.value = '" . ($visa['travel_date'] ?? '') . "';\n";
            echo "const visaPassengers = document.querySelector('input[name=\"visa[0][passengers]\"]');\n";
            echo "if (visaPassengers) visaPassengers.value = '" . ($visa['passengers'] ?? '0') . "';\n";
            echo "const visaRate = document.querySelector('input[name=\"visa[0][rate_per_person]\"]');\n";
            echo "if (visaRate) visaRate.value = '" . ($visa['rate_per_person'] ?? '0') . "';\n";
            echo "const visaRoe = document.querySelector('input[name=\"visa[0][roe]\"]');\n";
            echo "if (visaRoe) visaRoe.value = '" . ($visa['roe'] ?? '1') . "';\n";
            echo "console.log('Visa data populated');\n";
        }
        
        // Populate ACCOMMODATION data if accommodation service is selected
        $accommodation_data = json_decode($existing_cost_data['accommodation_data'] ?? '[]', true);
        if (is_array($accommodation_data) && !empty($accommodation_data) && isset($accommodation_data[0])) {
            $accom = $accommodation_data[0]; // Get first accommodation entry
            echo "const accomDest = document.querySelector('select[name=\"accommodation[0][destination]\"]');\n";
            echo "if (accomDest) accomDest.value = '" . addslashes($accom['destination'] ?? '') . "';\n";
            echo "const accomHotel = document.querySelector('select[name=\"accommodation[0][hotel]\"]');\n";
            echo "if (accomHotel) accomHotel.value = '" . addslashes($accom['hotel'] ?? '') . "';\n";
            echo "const accomCheckin = document.querySelector('input[name=\"accommodation[0][check_in]\"]');\n";
            echo "if (accomCheckin) accomCheckin.value = '" . ($accom['check_in'] ?? '') . "';\n";
            echo "const accomCheckout = document.querySelector('input[name=\"accommodation[0][check_out]\"]');\n";
            echo "if (accomCheckout) accomCheckout.value = '" . ($accom['check_out'] ?? '') . "';\n";
            echo "const accomRoomType = document.querySelector('select[name=\"accommodation[0][room_type]\"]');\n";
            echo "if (accomRoomType) accomRoomType.value = '" . addslashes($accom['room_type'] ?? '') . "';\n";
            echo "const accomRoomsNo = document.querySelector('input[name=\"accommodation[0][rooms_no]\"]');\n";
            echo "if (accomRoomsNo) accomRoomsNo.value = '" . ($accom['rooms_no'] ?? '0') . "';\n";
            echo "const accomRoomsRate = document.querySelector('input[name=\"accommodation[0][rooms_rate]\"]');\n";
            echo "if (accomRoomsRate) accomRoomsRate.value = '" . ($accom['rooms_rate'] ?? '0') . "';\n";
            echo "const accomNights = document.querySelector('input[name=\"accommodation[0][nights]\"]');\n";
            echo "if (accomNights) accomNights.value = '" . ($accom['nights'] ?? '0') . "';\n";
            echo "const accomMealPlan = document.querySelector('select[name=\"accommodation[0][meal_plan]\"]');\n";
            echo "if (accomMealPlan) accomMealPlan.value = '" . addslashes($accom['meal_plan'] ?? '') . "';\n";
            echo "console.log('Accommodation data populated');\n";
        }
        
        // Populate PAYMENT data
        $payment_data = json_decode($existing_cost_data['payment_data'] ?? '{}', true);
        if (is_array($payment_data)) {
            echo "const paymentDate = document.querySelector('input[name=\"payment_date\"]');\n";
            echo "if (paymentDate) paymentDate.value = '" . ($payment_data['date'] ?? '') . "';\n";
            echo "const paymentBank = document.querySelector('select[name=\"payment_bank\"]');\n";
            echo "if (paymentBank) paymentBank.value = '" . addslashes($payment_data['bank'] ?? '') . "';\n";
            echo "const paymentAmount = document.querySelector('input[name=\"payment_amount\"]');\n";
            echo "if (paymentAmount) paymentAmount.value = '" . ($payment_data['amount'] ?? '0') . "';\n";
            echo "const totalReceived = document.querySelector('input[name=\"total_received\"]');\n";
            echo "if (totalReceived) totalReceived.value = '" . ($payment_data['total_received'] ?? '0') . "';\n";
            echo "const balanceAmount = document.querySelector('input[name=\"balance_amount\"]');\n";
            echo "if (balanceAmount) balanceAmount.value = '" . ($payment_data['balance_amount'] ?? '0') . "';\n";
            echo "console.log('Payment data populated');\n";
        }
        ?>
    }, 1000);
    
    // Populate summary fields
    const totalExpense = document.getElementById('summary-total-expense');
    if (totalExpense) totalExpense.value = '<?php echo $existing_cost_data['total_expense'] ?? '0.00'; ?>';
    
    const markupPercentage = document.getElementById('markup-percentage');
    if (markupPercentage) markupPercentage.value = '<?php echo $existing_cost_data['markup_percentage'] ?? '0'; ?>';
    
    const markupAmount = document.getElementById('markup-amount');
    if (markupAmount) markupAmount.value = '<?php echo $existing_cost_data['markup_amount'] ?? '0.00'; ?>';
    
    const taxPercentage = document.getElementById('tax-percentage');
    if (taxPercentage) taxPercentage.value = '<?php echo $existing_cost_data['tax_percentage'] ?? '18'; ?>';
    
    const taxAmount = document.getElementById('tax-amount');
    if (taxAmount) taxAmount.value = '<?php echo $existing_cost_data['tax_amount'] ?? '0.00'; ?>';
    
    const packageCost = document.getElementById('package-cost');
    if (packageCost) packageCost.value = '<?php echo $existing_cost_data['package_cost'] ?? '0.00'; ?>';
    
    const currencyRate = document.getElementById('currency-rate');
    if (currencyRate) currencyRate.value = '<?php echo $existing_cost_data['currency_rate'] ?? '1'; ?>';
    
    const convertedAmount = document.getElementById('converted-amount');
    if (convertedAmount) convertedAmount.value = '<?php echo $existing_cost_data['converted_amount'] ?? '0.00'; ?>';
    
    // Show editing indicator
    const title = document.querySelector('.cost-file-title');
    if (title) title.innerHTML += ' <small style="color: #ffd700;">(EDITING)</small>';
    
    // Recalculate totals after data population
    setTimeout(() => {
        console.log('Recalculating totals...');
        if (typeof calculateSummary === 'function') {
            calculateSummary();
        }
    }, 2000);
}
<?php endif; ?>

// Popup functions
function closePopup(popupId) {
    const popup = document.getElementById(popupId);
    if (popup) {
        popup.style.animation = 'popupSlideOut 0.3s ease';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 300);
    }
}

// Add slide out animation
const style = document.createElement('style');
style.textContent = `
    @keyframes popupSlideOut {
        from {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        to {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
    }
`;
document.head.appendChild(style);

let visaRowCount = 1;
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

// Accommodation calculations
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

// Transportation calculations
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

let transportationRowCount = 1;
function addTransportationRow() {
    const tbody = document.getElementById('transportation-tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <tr>
            <td>${transportationRowCount + 1}</td>
            <td><input type="text" class="form-control form-control-sm" name="transportation[${transportationRowCount}][supplier]" placeholder="Supplier Name"></td>
            <td>
                <select class="form-control form-control-sm" name="transportation[${transportationRowCount}][car_type]">
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
            <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[${transportationRowCount}][daily_rent]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[${transportationRowCount}][days]" data-row="${transportationRowCount}" value="2" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="2"></td>
            <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[${transportationRowCount}][km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[${transportationRowCount}][extra_km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[${transportationRowCount}][price_per_km]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[${transportationRowCount}][toll]" data-row="${transportationRowCount}" value="0" onchange="calculateTransportationTotal(${transportationRowCount})" placeholder="0"></td>
            <td><input type="text" class="form-control form-control-sm trans-total" name="transportation[${transportationRowCount}][total]" data-row="${transportationRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        </tr>
    `;
    tbody.appendChild(newRow);
    transportationRowCount++;
}

// Cruise calculations
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

// Extras calculations
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

// Summary calculations
function calculateSummary() {
    // Get total expense from all selected services
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
    
    // Add extras total if section is visible
    if (document.getElementById('extras-section').style.display !== 'none') {
        const extrasTotal = parseFloat(document.getElementById('extras-grand-total')?.value) || 0;
        totalExpense += extrasTotal;
    }
    
    // Update total expense
    document.getElementById('summary-total-expense').value = totalExpense.toFixed(2);
    
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
        const convertedAmountInput = document.getElementById('converted-amount');
        if (convertedAmountInput) {
            convertedAmountInput.value = convertedAmount.toFixed(2);
        }
    }
    
    // Update payment balance calculations
    updatePaymentTotals();
}

// Update summary when any service total changes
function updateSummaryTotals() {
    calculateSummary();
}

// Override existing calculation functions to also update summary
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

let extrasRowCount = 1;
function addExtrasRow() {
    const tbody = document.getElementById('extras-tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" class="form-control form-control-sm" name="extras[${extrasRowCount}][supplier]" placeholder="Supplier Name"></td>
        <td><input type="text" class="form-control form-control-sm" name="extras[${extrasRowCount}][service_type]" placeholder="Type of Service"></td>
        <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[${extrasRowCount}][amount]" data-row="${extrasRowCount}" value="0" onchange="calculateExtrasTotal(${extrasRowCount})" placeholder="0"></td>
        <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[${extrasRowCount}][extras]" data-row="${extrasRowCount}" value="0" onchange="calculateExtrasTotal(${extrasRowCount})" placeholder="0"></td>
        <td><input type="text" class="form-control form-control-sm extras-total" name="extras[${extrasRowCount}][total]" data-row="${extrasRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
    `;
    tbody.appendChild(newRow);
    extrasRowCount++;
}

let accommodationRowCount = 1;
function addAccommodationRow() {
    const tbody = document.getElementById('accommodation-tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][destination]">
                <option value="">Select Destination</option>
                <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                    <option value="<?php echo htmlspecialchars($dest['name']); ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][hotel]">
                <option value="">Select Hotel</option>
                <option value="ABAAM CHELSEA">ABAAM CHELSEA</option>
                <option value="ABAD ATRIUM KOCHI">ABAD ATRIUM KOCHI</option>
                <option value="TAJ MALABAR">TAJ MALABAR</option>
                <option value="VIVANTA MARINE DRIVE">VIVANTA MARINE DRIVE</option>
            </select>
        </td>
        <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_in]"></td>
        <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_out]"></td>
        <td>
            <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][room_type]">
                <option value="">Select Room Type</option>
                <option value="Single Room">Single Room</option>
                <option value="Double Room">Double Room</option>
                <option value="Suite">Suite</option>
                <option value="Villa">Villa</option>
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[${accommodationRowCount}][rooms_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[${accommodationRowCount}][rooms_rate]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[${accommodationRowCount}][extra_adult_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[${accommodationRowCount}][extra_adult_rate]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[${accommodationRowCount}][extra_child_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[${accommodationRowCount}][extra_child_rate]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[${accommodationRowCount}][child_no_bed_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[${accommodationRowCount}][child_no_bed_rate]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[${accommodationRowCount}][nights]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
        <td>
            <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][meal_plan]">
                <option value="">Select Meal Plan</option>
                <option value="Room Only">Room Only</option>
                <option value="Bed and Breakfast">Bed and Breakfast</option>
                <option value="Half Board">Half Board</option>
                <option value="All-Inclusive">All-Inclusive</option>
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[${accommodationRowCount}][total]" data-row="${accommodationRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
    `;
    tbody.appendChild(newRow);
    accommodationRowCount++;
}

// Add some interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all info cards
    document.querySelectorAll('.info-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
    
    // Update payment amount calculations
    const paymentAmountInput = document.querySelector('input[name="payment_amount"]');
    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('change', updatePaymentTotals);
    }
});

// Function to update payment totals
function updatePaymentTotals() {
    const paymentAmount = parseFloat(document.querySelector('input[name="payment_amount"]').value) || 0;
    const packageCost = parseFloat(document.getElementById('package-cost').value) || 0;
    
    // Update total received
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
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>