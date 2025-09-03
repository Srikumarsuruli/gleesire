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

// Get accommodation details for dropdown
$accommodation_sql = "SELECT * FROM accommodation_details ORDER BY destination";
$accommodation_details = mysqli_query($conn, $accommodation_sql);

// Get transport details for dropdown
$transport_sql = "SELECT * FROM transport_details WHERE status = 'Active' ORDER BY destination";
$transport_details = mysqli_query($conn, $transport_sql);

// Get travel agents for dropdown
$travel_agents_sql = "SELECT * FROM travel_agents WHERE status = 'Active' ORDER BY destination";
$travel_agents = mysqli_query($conn, $travel_agents_sql);

// Get hospital details for dropdown
$hospital_sql = "SELECT * FROM hospital_details WHERE status = 'Active' ORDER BY destination";
$hospital_details = mysqli_query($conn, $hospital_sql);

$cruise_sql = "SELECT * FROM cruise_details ORDER BY destination";
$cruise_details = mysqli_query($conn, $cruise_sql);

// Get packages for dropdown
$packages_sql = "SELECT * FROM packages WHERE status = 'Active' ORDER BY package_name";
$packages_result = mysqli_query($conn, $packages_sql);

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
        
        $stmt = mysqli_prepare($conn, $insert_sql);

        if (!$stmt) {
            die("SQL Prepare failed: " . mysqli_error($conn) . "\nQuery: " . $insert_sql);
        }
        
        // Build parameter array and type string
        $params = [
            $enquiry_id, $cost_sheet_number, $guest_name, $guest_address, 
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
        $type_string = str_repeat('s', count($params)); 

        // Adjust numeric fields
        $type_string[0] = 'i';  // enquiry_id
        $type_string[8] = 'i';  // adults_count
        $type_string[9] = 'i';  // children_count
        $type_string[10] = 'i'; // infants_count
        $type_string[11] = 's';

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
                
                
                echo "<script>window.location.href='view_leads.php?success=true';</script>";
                exit;

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
<style>
.info-label, .service-text, th, td, label, .form-control, select, input, span, 
placeholder, option, .table th, .table td, h5, h6, strong, 
.services-section h5, .services-section h6, .table thead th {
    text-transform: capitalize !important;
}

input::placeholder, textarea::placeholder {
    text-transform: capitalize !important;
}

select option {
    text-transform: capitalize !important;
}
</style>


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
                            <?php while($package = mysqli_fetch_assoc($packages_result)): ?>
                                <option value="<?php echo htmlspecialchars($package['package_name']); ?>">
                                    <?php echo htmlspecialchars($package['package_name']); ?>
                                </option>
                            <?php endwhile; ?>
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
                    <!-- <div class="info-row">
                        <span class="info-label">Lead Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['department_name']); ?></span>
                    </div> --> 
                    <div class="info-row">
                        <span class="info-label">Night/Day:</span>
                        <span class="info-value"><?php 
                            $night_day_sql = "SELECT night_day FROM converted_leads WHERE enquiry_id = ?";
                            $night_day_stmt = mysqli_prepare($conn, $night_day_sql);
                            mysqli_stmt_bind_param($night_day_stmt, "i", $enquiry_id);
                            mysqli_stmt_execute($night_day_stmt);
                            $night_day_result = mysqli_stmt_get_result($night_day_stmt);
                            $night_day = 'N/A';
                            if ($night_day_row = mysqli_fetch_assoc($night_day_result)) {
                                $night_day = $night_day_row['night_day'];
                            }
                            mysqli_stmt_close($night_day_stmt);
                            echo htmlspecialchars($night_day ?: 'N/A');
                        ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Travel Period:</span>
                        <span class="info-value"><?php 
                            $start_date = $enquiry['travel_start_date'] ? date('d-m-Y', strtotime($enquiry['travel_start_date'])) : 'N/A';
                            $end_date = $enquiry['travel_end_date'] ? date('d-m-Y', strtotime($enquiry['travel_end_date'])) : 'N/A';
                            echo $start_date . ' To ' . $end_date;
                        ?></span>
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
                        <span class="info-label">Children Age Details:</span>
                        <span class="info-value"><?php 
                            // Get children age details from converted_leads table
                            $children_age_details = '';
                            $children_details_sql = "SELECT children_age_details FROM converted_leads WHERE enquiry_id = ?";
                            $children_details_stmt = mysqli_prepare($conn, $children_details_sql);
                            mysqli_stmt_bind_param($children_details_stmt, "i", $enquiry_id);
                            mysqli_stmt_execute($children_details_stmt);
                            $children_details_result = mysqli_stmt_get_result($children_details_stmt);
                            
                            if ($children_details_row = mysqli_fetch_assoc($children_details_result)) {
                                $children_age_details = $children_details_row['children_age_details'];
                            }
                            
                            mysqli_stmt_close($children_details_stmt);
                            echo htmlspecialchars($children_age_details ?: 'N/A');
                        ?></span>
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
                     <div class="service-item" onclick="toggleService(this, 'agent_package')">
                            <i class="fa fa-briefcase service-icon-small"></i>
                            <span class="service-text">AGENT PACKAGE SERVICE</span>
                            <input type="checkbox" name="services[]" value="agent_package" style="display: none;">
                        </div>
                    
                        <div class="service-item" onclick="toggleService(this, 'medical_tourism')">
                            <i class="fa fa-hospital service-icon-small"></i>
                            <span class="service-text">MEDICAL TOURISM</span>
                            <input type="checkbox" name="services[]" value="medical_tourism" style="display: none;">
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
                                <th>Travel Period</th>
                                <th>Date</th>
                                <th>City</th>
                                <th>Flight</th>
                                <th>Nights/Days</th>
                                <th>Flight Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ARRIVAL</td>
                                <td><input type="datetime-local" class="form-control form-control-sm" name="arrival_date" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>"></td>
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
                                <td><input type="datetime-local" class="form-control form-control-sm" name="arrival_connecting_date"></td>
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
                                <td><input type="datetime-local" class="form-control form-control-sm" name="departure_date" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>"></td>
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
                                <td><input type="datetime-local" class="form-control form-control-sm" name="departure_connecting_date"></td>
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
                    <h5>
                        VISA / FLIGHT DETAILS
                        
                    </h5>
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. No</th>
                                <th>Sector</th>
                                <th>Supplier</th>
                                <th>Date Of Travels</th>
                                <th>No Of Passenger</th>
                                <th>Rate / Person</th>
                                <th>ROE</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="visa-details-tbody">
                            <tr>
                                <td>1</td>
                                <td>
                                    <select class="form-control form-control-sm" name="visa[0][sector]">
                                        <option value="">Select</option>
                                        <option value="visa">Visa</option>
                                        <option value="flight">Flight</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="visa[0][supplier]" placeholder="Supplier"></td>
                                <td><input type="date" class="form-control form-control-sm" name="visa[0][travel_date]"></td>
                                <td><input type="number" class="form-control form-control-sm visa-passengers" name="visa[0][passengers]" data-row="0" value="0" onchange="calculateVisaTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm visa-rate" name="visa[0][rate_per_person]" data-row="0" value="0" onchange="calculateVisaTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm" name="visa[0][roe]" value="1" step="0.01" onchange="calculateVisaTotal(0)"></td>
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
                        <button type="button" class="btn btn-sm btn-primary" onclick="addVisaRow()"><i class="fa fa-plus"></i> Add Row</button>
                    </table>
                    
                </div>
            </div>

            <!-- ACCOMMODATION Section -->
            <div id="accommodation-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-bed"></i>ACCOMMODATION
                    
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th rowspan="2">Destination</th>
                                <th rowspan="2">Hotel</th>
                                <th rowspan="2">Check In</th>
                                <th rowspan="2">Check Out</th>
                                <th rowspan="2">Room Type</th>
                                <th rowspan="2">Meal Plan</th>
                                <th colspan="2">Rooms</th>
                                <th colspan="2">Extra Bed Adult</th>
                                <th colspan="2">Extra Bed Child</th>
                                <th colspan="2">Child No Bed</th>
                                <th rowspan="2">Nights</th>
                                
                                <th rowspan="2">Total</th>
                            </tr>
                            <tr>
                                <th>No</th>
                                <th>Rate</th>
                                <th>No</th>
                                <th>Rate/Bed</th>
                                <th>No</th>
                                <th>Rate</th>
                                <th>No</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody id="accommodation-tbody">
                            <tr>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][destination]" onchange="updateHotels(this, 0)" >
                                        <option value="">Select Destination</option>
                                        <?php
                                            $uniqueValues = [];
                                            mysqli_data_seek($accommodation_details, 0);
                                            while($accom = mysqli_fetch_assoc($accommodation_details)):
                                                if (in_array($accom['destination'], $uniqueValues)) {
                                                    continue; // skip duplicate
                                                }
                                                $uniqueValues[] = $accom['destination'];
                                        ?>
                                            <option 
                                                value="<?php echo htmlspecialchars($accom['destination']); ?>" 
                                            >
                                                <?php echo htmlspecialchars($accom['destination']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][hotel]" onchange="updateRoomTypes(this, 0)" disabled>
                                        <option value="">Select Hotel</option>
                                    </select>
                                </td>
                                <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_in]" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>" onchange="calculateNights(0)"></td>
                                <td><input type="date" class="form-control form-control-sm" name="accommodation[0][check_out]" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>" onchange="calculateNights(0)"></td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][room_type]" onchange="updateRoomRate(this, 0)" disabled>
                                        <option value="">Select Room Type</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][meal_plan]" onchange="updateRoomRate(this, 0)">
                                        <option value="">Select Meal Plan</option>
                                        <option value="cp" selected>CP</option>
                                        <option value="map">MAP</option>
                                    </select>
                                </td>
                                <td style="min-width: 120px"><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[0][rooms_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                
                                <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[0][rooms_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)" readonly></td>
                                <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[0][extra_adult_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[0][extra_adult_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[0][extra_child_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[0][extra_child_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[0][child_no_bed_no]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[0][child_no_bed_rate]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)"></td>
                                <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[0][nights]" data-row="0" value="0" onchange="calculateAccommodationTotal(0)" readonly></td>
                              
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addAccommodationRow()"><i class="fa fa-plus"></i> Add Hotel</button>
                </div>
            </div>

            <!-- TRANSPORTATION Section -->
            <div id="transportation-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-car"></i> INTERNAL TRANSPORTATION
                    
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. No</th>
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
                            <tr>
                                <td>1</td>
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
                                            <option value="<?php echo htmlspecialchars($transport['company_name']); ?>" ><?php echo htmlspecialchars($transport['company_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <input type="hidden" name="transportation[0][idx]" value="0">
                                    <input type="hidden" name="transportation[0][phone]" value="">
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
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="transportation-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTransportationRow()"><i class="fa fa-plus"></i> Add Vehicle</button>
                    
                </div>
            </div>

            <!-- CRUISE HIRE Section -->
            <div id="cruise-hire-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-ship"></i> CRUISE HIRE
                   
                </h5>
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
                                <th>Days</th>
                                <th>Adult Count</th>
                                <th>Adult Price</th>
                                <th>Kids Count</th>
                                <th>Kids Price</th>
                                <th>Extra</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="cruise-tbody">
                            <tr>
                                <td>1</td>
                                <td>
                                    <select class="form-control form-control-sm" name="cruise[0][supplier]" data-row="0" onchange="updateCruiseBoat(this, 0)">
                                        <option value="">Select supplier</option>
                                        <?php mysqli_data_seek($cruise_details, 0); 
                                        $cruise_supplier = array();
                                        while($supplier = mysqli_fetch_assoc($cruise_details)): 
                                            if(!in_array($supplier['name'], $cruise_supplier)):
                                                $cruise_supplier[] = $supplier['name'];
                                        ?>
                                            <option value="<?php echo htmlspecialchars($supplier['name']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                                        <?php endif; endwhile; ?>
                                    </select>
                                    <input type="hidden" name="cruise[0][idx]" value="0">
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="cruise[0][boat_type]" disabled onchange="updateCruiseType(this, 0)">
                                        <option value="">Select Boat Type</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="cruise[0][cruise_type]"  disabled onchange="updateCruisePricing(this, 0)">
                                        <option value="">Select Cruise Type</option>
                                    </select>
                                </td>
                                <td><input type="datetime-local" class="form-control form-control-sm cruise-start-date" name="cruise[0][check_in]" data-row="0" onchange="calculateCruiseDays(0)"></td>
                                <td><input type="datetime-local" class="form-control form-control-sm cruise-end-date" name="cruise[0][check_out]" data-row="0" onchange="calculateCruiseDays(0)"></td>
                                <td><input type="number" readonly class="form-control form-control-sm cruise-days" name="cruise[0][days]" data-row="0"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-adult-count" name="cruise[0][adult_count]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="number" readonly class="form-control form-control-sm cruise-adult-price" name="cruise[0][adult_price]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-kids-count" name="cruise[0][kids_count]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="number" readonly class="form-control form-control-sm cruise-kids-price" name="cruise[0][kids_price]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-extra-price" name="cruise[0][extra]" data-row="0" value="0" onchange="calculateCruiseTotal(0)" placeholder="0"></td>
                                <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[0][total]" data-row="0" readonly style="background: #f0f8ff; font-weight: bold;"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="12" class="text-right"><strong>TOTAL CRUISE COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="cruise-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCruiseRow()"><i class="fa fa-plus"></i> Add Cruise</button>
                </div>
            </div>

            <!-- AGENT PACKAGE SERVICE Section -->
            <div id="agent-package-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-briefcase"></i> AGENT PACKAGE SERVICE
                    
                </h5>
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
                            <tr>
                                <td>
                                    <select class="form-control form-control-sm" name="agent_package[0][destination]" onchange="updateAgentSupplier(this, 0)">
                                        <option value="">Select Destination</option>
                                        <?php mysqli_data_seek($travel_agents, 0); 
                                        $agent_destinations_added = array();
                                        while($agent = mysqli_fetch_assoc($travel_agents)): 
                                            if(!in_array($agent['destination'], $agent_destinations_added)):
                                                $agent_destinations_added[] = $agent['destination'];
                                        ?>
                                            <option value="<?php echo htmlspecialchars($agent['destination']); ?>"><?php echo htmlspecialchars($agent['destination']); ?></option>
                                        <?php endif; endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="agent_package[0][agent_supplier]" disabled>
                                        <option value="">Select Agent/Supplier</option>
                                    </select>
                                </td>
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

            <!-- MEDICAL TOURISM Section -->
            <div id="medical-tourism-section" class="services-section" style="display: none;">
                <h5>
                    <i class="icon-copy fa fa-hospital"></i> MEDICAL TOURISM
                    
                </h5>
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
                            <tr>
                                <td>
                                    <select class="form-control form-control-sm" name="medical_tourisms[0][place]" onchange="updateHospitals(this, 0)" style="width: 120px;">
                                        <option value="">Select Place</option>
                                        <?php mysqli_data_seek($hospital_details, 0); 
                                        $hospital_destinations_added = array();
                                        while($hospital = mysqli_fetch_assoc($hospital_details)): 
                                            if(!in_array($hospital['destination'], $hospital_destinations_added)):
                                                $hospital_destinations_added[] = $hospital['destination'];
                                        ?>
                                            <option value="<?php echo htmlspecialchars($hospital['destination']); ?>"><?php echo htmlspecialchars($hospital['destination']); ?></option>
                                        <?php endif; endwhile; ?>
                                    </select>
                                    <input type="hidden" name="medical_tourisms[0][idx]" value="0">
                                </td>
                                <td><input type="date" class="form-control form-control-sm" name="medical_tourisms[0][treatment_date]" value=""></td>
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
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right"><strong>TOTAL MEDICAL TOURISM COST:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="medical-tourism-grand-total" readonly style="background: #e8f5e8; font-weight: bold;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addMedicalTourismRow()"><i class="fa fa-plus"></i> Add Treatment</button>
                </div>
            </div>

            <!-- EXTRAS/MISCELLANEOUS Section -->
            <div id="extras-section" class="services-section" style="display: none;">
                <h5><i class="icon-copy fa fa-plus"></i> EXTRAS/MISCELLANEOUS</h5>
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
                                    <td><strong>Amount In</strong></td>
                                    <td>
                                        <span id="selected-currency" style="font-weight: bold;">USD</span>
                                        <span style="margin-left: 10px; font-size: 0.8rem; color: #666;">Auto From Currency In Top</span>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm" id="currency-rate" name="currency_rate" value="82" onchange="calculateSummary()" placeholder="1.00"></td>
                                </tr>
                                                 
                                <tr>
                                    <td><strong>Mark Up (Profit)</strong></td>
                                    <td>
                                        <input type="hidden" class="form-control form-control-sm" id="markup-percentage" name="markup_percentage" value="" onchange="calculateSummary()" placeholder="%" style="max-width: 80px;">  
                                        <span id="markup-percent-display" style="font-size: 0.8rem; color: #666;"></span>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" id="markup-amount" name="markup_amount" readonly style="background: #f0f8ff; font-weight: bold;" placeholder="0.00"></td>
                                </tr>
                                <tr>
                                    <td><strong>Service Tax</strong></td>
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
                                    <td><strong>Total Expense</strong></td>
                                    <td></td>
                                    <td><input type="text" class="form-control form-control-sm" id="summary-total-expense" name="total_expense" readonly style="background: #f0f8ff; font-weight: bold;" placeholder="0.00"></td>
                                </tr>
                                <tr>
                                    <td><strong>Package Cost</strong></td>
                                    <td></td>
                                    <td><input type="number" class="form-control form-control-sm" id="package-cost" name="package_cost" style="background: #e8f5e8; font-weight: bold;" placeholder="0.00" onchange="calculateSummary()"></td>
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
<script src="prevent_negative_values.js?v=<?php echo time(); ?>"></script>
<script src="auto_date_selector.js?v=<?php echo time(); ?>"></script>
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
            // Check for mutual exclusion between accommodation and agent_package
            if (serviceName === 'accommodation') {
                // If selecting accommodation, deselect agent_package
                const agentPackageItem = document.querySelector('.service-item input[value="agent_package"]').closest('.service-item');
                if (agentPackageItem && agentPackageItem.classList.contains('selected')) {
                    agentPackageItem.classList.remove('selected');
                    agentPackageItem.querySelector('input[type="checkbox"]').checked = false;
                    const agentSection = document.getElementById('agent-package-section');
                    if (agentSection) agentSection.style.display = 'none';
                }
            } else if (serviceName === 'agent_package') {
                // If selecting agent_package, deselect accommodation
                const accommodationItem = document.querySelector('.service-item input[value="accommodation"]').closest('.service-item');
                if (accommodationItem && accommodationItem.classList.contains('selected')) {
                    accommodationItem.classList.remove('selected');
                    accommodationItem.querySelector('input[type="checkbox"]').checked = false;
                    const accomSection = document.getElementById('accommodation-section');
                    if (accomSection) accomSection.style.display = 'none';
                }
            }
            
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
        const roe = parseFloat(document.querySelector(`input[name="visa[${row}][roe]"]`).value) || 1;
        const total = passengers * rate * roe;
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
            <td>
                <select class="form-control form-control-sm" name="visa[${visaRowCount}][sector]">
                    <option value="">Select</option>
                    <option value="visa">Visa</option>
                    <option value="flight">Flight</option>
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="visa[${visaRowCount}][supplier]" placeholder="Supplier"></td>
            <td><input type="date" class="form-control form-control-sm" name="visa[${visaRowCount}][travel_date]"></td>
            <td><input type="number" class="form-control form-control-sm visa-passengers" name="visa[${visaRowCount}][passengers]" data-row="${visaRowCount}" value="0" onchange="calculateVisaTotal(${visaRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm visa-rate" name="visa[${visaRowCount}][rate_per_person]" data-row="${visaRowCount}" value="0" onchange="calculateVisaTotal(${visaRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm" name="visa[${visaRowCount}][roe]" value="1" step="0.01" onchange="calculateVisaTotal(${visaRowCount})"></td>
            <td><input type="text" class="form-control form-control-sm visa-total" name="visa[${visaRowCount}][total]" data-row="${visaRowCount}" readonly></td>
        `;
        tbody.appendChild(newRow);
        visaRowCount++;
    }

    // Accommodation calculations
    function calculateNights(row) {
        const checkInInput = document.querySelector(`input[name="accommodation[${row}][check_in]"]`);
        const checkOutInput = document.querySelector(`input[name="accommodation[${row}][check_out]"]`);
        const nightsInput = document.querySelector(`.accom-nights[data-row="${row}"]`);
        
        if (checkInInput && checkOutInput && nightsInput && checkInInput.value && checkOutInput.value) {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            const timeDiff = checkOut.getTime() - checkIn.getTime();
            const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            if (nights > 0) {
                nightsInput.value = nights;
                calculateAccommodationTotal(row);
            }
        }
    }

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
                    <input type="hidden" name="transportation[${transportationRowCount}][phone]" value="">
                </td>

                <td>
                    <select class="form-control form-control-sm" name="transportation[${transportationRowCount}][car_type]" onchange="updateTransportRates(this, ${transportationRowCount})" disabled>
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

    // Accommodation cascading dropdowns
    function updateHotels(destinationSelect, rowIndex, selectedHotel) {
        const hotelSelect = destinationSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[hotel]"]');
        const roomTypeSelect = destinationSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');

        hotelSelect.disabled = !destinationSelect.value;
        roomTypeSelect.disabled = true;

        if(destinationSelect.value) {
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

                    updateRoomRate(destinationSelect, rowIndex)
                });
        }
    }

    function updateRoomTypes(hotelSelect, rowIndex, selectedRoomType) {
        const destinationSelect = hotelSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[destination]"]');
        const roomTypeSelect = hotelSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');

        roomTypeSelect.disabled = !hotelSelect.value;

        if(hotelSelect.value && destinationSelect.value) {
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

                    updateRoomRate(hotelSelect, rowIndex)
                });
        }
    }

    function updateRoomRate(parentSelect, rowIndex) {
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

    // Transportation cascading dropdowns
    function updateTransportVehicles(supplierSelect, rowIndex, selectedVehicle) {
        const vehicleSelect = supplierSelect.closest('tr').querySelector('select[name^="transportation"][name$="[car_type]"]');
        const supplierPhoneSelect = supplierSelect.closest('tr').querySelector('input[name^="transportation"][name$="[phone]"]');
        
        vehicleSelect.disabled = !supplierSelect.value;

        if(supplierSelect.value) {
            fetch(`get_data_model.php?data_model=transportation&company_name=${supplierSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    vehicleSelect.innerHTML = '<option value="">Select Car Type</option>';
                    let rows = []
                    let key_name = "vehicle"
                    
                    console.log(res.data);
                    
                    res.data.forEach((row, idx)=>{

                        if(idx == 0) {
                             supplierPhoneSelect.value = res.data[0].mobile
                        }

                        if(row[key_name] && !rows.includes(row[key_name])){
                            rows.push(row[key_name])
                        }
                    })
                
                    rows.forEach(item => {
                        vehicleSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });
                });
        }
    }

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

        const adultsCount = document.querySelector('input[name="adults_count"]')?.value || 0;
        const childrenCount = document.querySelector('input[name="children_count"]')?.value || 0;
        const infantsCount = document.querySelector('input[name="infants_count"]')?.value || 0;

        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="agent_package[${newIndex}][destination]" onchange="updateAgentSupplier(this, ${newIndex})">
                    <option value="">Select Destination</option>
                    <?php mysqli_data_seek($travel_agents, 0); 
                    $agent_destinations_added = array();
                    while($agent = mysqli_fetch_assoc($travel_agents)): 
                        if(!in_array($agent['destination'], $agent_destinations_added)):
                            $agent_destinations_added[] = $agent['destination'];
                    ?>
                        <option value="<?php echo htmlspecialchars($agent['destination']); ?>"><?php echo htmlspecialchars($agent['destination']); ?></option>
                    <?php endif; endwhile; ?>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="agent_package[${newIndex}][agent_supplier]" disabled>
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

    let cruiseRowCount = 1;
    function addCruiseRow() {
        const tbody = document.getElementById('cruise-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${cruiseRowCount + 1}</td>
            <td>
                <select class="form-control form-control-sm" name="cruise[${cruiseRowCount}][supplier]" data-row="${cruiseRowCount}" onchange="updateCruiseBoat(this, ${cruiseRowCount})">
                    <option value="">Select supplier</option>
                    <?php mysqli_data_seek($cruise_details, 0); 
                    $cruise_supplier = array();
                    while($supplier = mysqli_fetch_assoc($cruise_details)): 
                        if(!in_array($supplier['name'], $cruise_supplier)):
                            $cruise_supplier[] = $supplier['name'];
                    ?>
                        <option value="<?php echo htmlspecialchars($supplier['name']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                    <?php endif; endwhile; ?>
                </select>
                <input type="hidden" name="cruise[${cruiseRowCount}][idx]" value="${cruiseRowCount}">
            </td>
            <td>
                <select class="form-control form-control-sm" name="cruise[${cruiseRowCount}][boat_type]" data-row="${cruiseRowCount}" disabled onchange="updateCruiseType(this, ${cruiseRowCount})">
                    <option value="">Select Boat Type</option>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="cruise[${cruiseRowCount}][cruise_type]" data-row="${cruiseRowCount}" disabled onchange="updateCruisePricing(this, ${cruiseRowCount})">
                    <option value="">Select Cruise Type</option>
                </select>
            </td>
            <td><input type="datetime-local" class="form-control form-control-sm cruise-start-date" name="cruise[${cruiseRowCount}][check_in]" data-row="${cruiseRowCount}" onchange="calculateCruiseDays(${cruiseRowCount})"></td>
            <td><input type="datetime-local" class="form-control form-control-sm cruise-end-date" name="cruise[${cruiseRowCount}][check_out]" data-row="${cruiseRowCount}" onchange="calculateCruiseDays(${cruiseRowCount})"></td>
            <td><input type="number" readonly class="form-control form-control-sm cruise-days" name="cruise[${cruiseRowCount}][days]" data-row="${cruiseRowCount}"></td>
            <td><input type="number" class="form-control form-control-sm cruise-adult-count" name="cruise[${cruiseRowCount}][adult_count]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="number" readonly class="form-control form-control-sm cruise-adult-price" name="cruise[${cruiseRowCount}][adult_price]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm cruise-kids-count" name="cruise[${cruiseRowCount}][kids_count]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="number" readonly class="form-control form-control-sm cruise-kids-price" name="cruise[${cruiseRowCount}][kids_price]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="number" class="form-control form-control-sm cruise-extra-price" name="cruise[${cruiseRowCount}][extra]" data-row="${cruiseRowCount}" value="0" onchange="calculateCruiseTotal(${cruiseRowCount})" placeholder="0"></td>
            <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise[${cruiseRowCount}][total]" data-row="${cruiseRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        `;
        tbody.appendChild(newRow);
        cruiseRowCount++;
    }

    function updateCruiseBoat(destinationSelect, rowIndex, value) {
        
        const boatTypeSelect = destinationSelect.closest('tr').querySelector('select[name^="cruise"][name$="[boat_type]"]');

        boatTypeSelect.disabled = !destinationSelect.value;

        if(destinationSelect.value) {
            fetch(`get_data_model.php?data_model=cruise_hire&supplier=${destinationSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    boatTypeSelect.innerHTML = '<option value="">Select Boat Type</option>';
                    let boatRows = []
                    let boatKeyName = "boat_type"

                    res.data.forEach(row=>{
                        if(row[boatKeyName] && !boatRows.includes(row[boatKeyName])){
                            boatRows.push(row[boatKeyName])
                        }
                       
                    })

                    boatRows.forEach(item => {
                        boatTypeSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });
                 
                });
        }
    }
    function updateCruiseType(boatTypeSelect, rowIndex, value) {

        const supplierSelect = boatTypeSelect.closest('tr').querySelector('select[name^="cruise"][name$="[supplier]"]');
        const cruiseTypeSelect = boatTypeSelect.closest('tr').querySelector('select[name^="cruise"][name$="[cruise_type]"]');

        cruiseTypeSelect.disabled = !boatTypeSelect.value;

        if(boatTypeSelect.value) {
            fetch(`get_data_model.php?data_model=cruise_hire&supplier=${supplierSelect.value}&boat_type=${boatTypeSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    cruiseTypeSelect.innerHTML = '<option value="">Select Cruise Type</option>';
                    let cruiseRows = []
                    let cruiseKeyName = "cruise_type"

                    res.data.forEach(row=>{
                       
                        if(row[cruiseKeyName] && !cruiseRows.includes(row[cruiseKeyName])){
                            cruiseRows.push(row[cruiseKeyName])
                        }
                    })

                    cruiseRows.forEach(item => {
                        cruiseTypeSelect.innerHTML += `<option value="${item}">${item}</option>`;
                    });
                });
        }
    }
    function updateCruisePricing(cruiseTypeSelect, rowIndex, value) {

        const supplierSelect = cruiseTypeSelect.closest('tr').querySelector('select[name^="cruise"][name$="[supplier]"]');
        const boatTypeSelect = cruiseTypeSelect.closest('tr').querySelector('select[name^="cruise"][name$="[boat_type]"]');
        const adultPriceInput = cruiseTypeSelect.closest('tr').querySelector('input[name^="cruise"][name$="[adult_price]"]');
        const kidsPriceInput = cruiseTypeSelect.closest('tr').querySelector('input[name^="cruise"][name$="[kids_price]"]');

        if(cruiseTypeSelect.value) {
            fetch(`get_data_model.php?data_model=cruise_hire&supplier=${supplierSelect.value}&boat_type=${boatTypeSelect.value}&cruise_type=${cruiseTypeSelect.value}`)
                .then(response => response.json())
                .then(res => {
                    let cruiseRows = []
                    let cruiseKeyName = "cruise_type"

                    let pricing_data = res.data[0]

                    if(pricing_data){
                        adultPriceInput.value = pricing_data.adult_price || 0;
                        kidsPriceInput.value = pricing_data.kids_price || 0;
                    }
                });
        }
    }

    function calculateCruiseDays(row) {
        const startDate = new Date(document.querySelector(`.cruise-start-date[data-row="${row}"]`).value);
        const endDate = new Date(document.querySelector(`.cruise-end-date[data-row="${row}"]`).value);

        if(startDate && endDate) {
            const timeDiff = Math.abs(endDate.getTime() - startDate.getTime());
            const days = Math.ceil(timeDiff / (1000 * 3600 * 24));

            document.querySelector(`.cruise-days[data-row="${row}"]`).value = days;
            calculateCruiseTotal(row);
        }
    }

    // Cruise calculations
    function calculateCruiseTotal(row) {
        const adultCount = parseFloat(document.querySelector(`.cruise-adult-count[data-row="${row}"]`).value) || 0;
        const adultPrice = parseFloat(document.querySelector(`.cruise-adult-price[data-row="${row}"]`).value) || 0;
        const kidsCount = parseFloat(document.querySelector(`.cruise-kids-count[data-row="${row}"]`).value) || 0;
        const kidsPrice = parseFloat(document.querySelector(`.cruise-kids-price[data-row="${row}"]`).value) || 0;
        const extraPrice = parseFloat(document.querySelector(`.cruise-extra-price[data-row="${row}"]`).value) || 0;
        const days = parseFloat(document.querySelector(`.cruise-days[data-row="${row}"]`).value) || 0;

        const total = (adultCount * adultPrice * days) + (kidsCount * kidsPrice * days) + extraPrice;

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

    // Medical Tourism functions
    function calculateMedicalTourismTotal(row) {
        const net = parseFloat(document.querySelector(`.medical-tourism-net[data-row="${row}"]`).value) || 0;
        const tds = parseFloat(document.querySelector(`.medical-tourism-tds[data-row="${row}"]`).value) || 0;
        const otherExpenses = parseFloat(document.querySelector(`.medical-tourism-other_expenses[data-row="${row}"]`).value) || 0;

        const subtotal = net + otherExpenses + tds;
        const gst = subtotal * 0.18; // 18% GST
        const total = subtotal + gst;

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

    function addMedicalTourismRow() {
        const tbody = document.getElementById('medical-tourism-tbody');
        const rows = tbody.querySelectorAll('tr');
        const newIndex = rows.length;

        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="medical_tourisms[${newIndex}][place]" onchange="updateHospitals(this, ${newIndex})" style="width: 120px;">
                    <option value="">Select Place</option>
                    <?php mysqli_data_seek($hospital_details, 0); 
                    $hospital_destinations_added = array();
                    while($hospital = mysqli_fetch_assoc($hospital_details)): 
                        if(!in_array($hospital['destination'], $hospital_destinations_added)):
                            $hospital_destinations_added[] = $hospital['destination'];
                    ?>
                        <option value="<?php echo htmlspecialchars($hospital['destination']); ?>"><?php echo htmlspecialchars($hospital['destination']); ?></option>
                    <?php endif; endwhile; ?>
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

    function updateHospitals(placeSelect, rowIndex, selectedHospital) {
        const hospitalSelect = placeSelect.closest('tr').querySelector('select[name^="medical_tourisms"][name$="[hospital]"]');

        hospitalSelect.disabled = !placeSelect.value;

        if(placeSelect.value) {
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

    let accommodationRowCount = 1;
    function addAccommodationRow() {
        const tbody = document.getElementById('accommodation-tbody');
        
        // Get check-in and check-out dates from the first row
        const firstCheckIn = document.querySelector('input[name="accommodation[0][check_in]"]');
        const firstCheckOut = document.querySelector('input[name="accommodation[0][check_out]"]');
        const checkInValue = firstCheckIn ? firstCheckIn.value : '';
        const checkOutValue = firstCheckOut ? firstCheckOut.value : '';
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][destination]" onchange="updateHotels(this, ${accommodationRowCount})">
                    <option value="">Select Destination</option>
                    <?php
                        $uniqueDestinations = [];
                        mysqli_data_seek($accommodation_details, 0);
                        while($dest = mysqli_fetch_assoc($accommodation_details)):
                            if (in_array($dest['destination'], $uniqueDestinations)) {
                                continue; // skip duplicate
                            }
                            $uniqueDestinations[] = $dest['destination'];
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
            <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_in]" value="${checkInValue}" onchange="calculateNights(${accommodationRowCount})"></td>
            <td><input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_out]" value="${checkOutValue}" onchange="calculateNights(${accommodationRowCount})"></td>
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][room_type]" onchange="updateRoomRate(this, ${accommodationRowCount})" disabled>
                    <option value="">Select Room Type</option>
                </select>
            </td>
            <td>
                <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][meal_plan]" onchange="updateRoomRate(this, ${accommodationRowCount})">
                    <option value="">Select Meal Plan</option>
                    <option value="cp" selected>CP</option>
                    <option value="map">MAP</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[${accommodationRowCount}][rooms_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            
            <td><input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[${accommodationRowCount}][rooms_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[${accommodationRowCount}][extra_adult_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[${accommodationRowCount}][extra_adult_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[${accommodationRowCount}][extra_child_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[${accommodationRowCount}][extra_child_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[${accommodationRowCount}][child_no_bed_no]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})"></td>
            <td><input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[${accommodationRowCount}][child_no_bed_rate]" data-row="${accommodationRowCount}" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm accom-nights" name="accommodation[${accommodationRowCount}][nights]" data-row="${accommodationRowCount}" value="0" onchange="calculateAccommodationTotal(${accommodationRowCount})" readonly></td>
                    
            <td><input type="text" class="form-control form-control-sm accom-total" name="accommodation[${accommodationRowCount}][total]" data-row="${accommodationRowCount}" readonly style="background: #f0f8ff; font-weight: bold;"></td>
        `;
        tbody.appendChild(newRow);
        
        // Calculate nights for the new row if both dates are populated
        if (checkInValue && checkOutValue) {
            setTimeout(() => {
                calculateNights(accommodationRowCount);
            }, 100);
        }
        
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

        // Initialize calculations
        calculateVisaGrandTotal();
        calculateAccommodationGrandTotal();
        calculateTransportationGrandTotal();
        calculateCruiseGrandTotal();
        calculateExtrasGrandTotal();
        calculateAgentPackageGrandTotal();
        calculateMedicalTourismGrandTotal();
        calculateSummary();
        updateCurrencySymbols();

        // Initialize accommodation dropdowns for existing rows
        document.querySelectorAll('select[name^="accommodation"][name$="[destination]"]').forEach((destSelect, index) => {
            if(destSelect.value) {
                const hotelSelect = destSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[hotel]"]');
                const roomTypeSelect = destSelect.closest('tr').querySelector('select[name^="accommodation"][name$="[room_type]"]');
                const selectedHotel = hotelSelect.getAttribute('data-selected') || '';
                const selectedRoomType = roomTypeSelect.getAttribute('data-selected') || '';
                updateHotels(destSelect, index, selectedHotel);
                if(selectedHotel) {
                    setTimeout(() => updateRoomTypes(hotelSelect, index, selectedRoomType), 500);
                }
            }
        });

        // Initialize transportation dropdowns for existing rows
        document.querySelectorAll('select[name^="transportation"][name$="[supplier]"]').forEach((supplierSelect, index) => {
            if(supplierSelect.value) {
                const vehicleSelect = supplierSelect.closest('tr').querySelector('select[name^="transportation"][name$="[car_type]"]');
                const selectedVehicle = vehicleSelect.getAttribute('data-selected') || '';
                updateTransportVehicles(supplierSelect, index, selectedVehicle);
            }
        });

        // Initialize agent package dropdowns for existing rows
        document.querySelectorAll('select[name^="agent_package"][name$="[destination]"]').forEach((destSelect, index) => {
            if(destSelect.value) {
                const supplierSelect = destSelect.closest('tr').querySelector('select[name^="agent_package"][name$="[agent_supplier]"]');
                const selectedSupplier = supplierSelect.getAttribute('data-selected') || '';
                updateAgentSupplier(destSelect, index, selectedSupplier);
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

        // Update payment amount calculations
        const paymentAmountInput = document.querySelector('input[name="payment_amount"]');
        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('change', updatePaymentTotals);
        }

        // Update payment totals on page load
        updatePaymentTotals();
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