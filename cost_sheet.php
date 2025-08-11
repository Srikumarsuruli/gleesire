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

// Get enquiry details
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name,
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

// Check if cost_sheets table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'cost_sheets'");
if(mysqli_num_rows($table_exists) == 0) {
    // Table doesn't exist, redirect to the table creation script
    echo "<script>window.location.href = 'create_cost_sheets_table.php';</script>";
    exit;
}

// Initialize variables
$services = [];
$travel_details = [];
$visa_flight_ticket = [];
$accommodation = [];
$transportation = [];
$cruise_hire = [];
$extras = [];
$payment_data = [];
$package_cost = 0;
$tax_percentage = 18;
$currency_rate = 1;
$success_message = "";
$error_message = "";

// Check if cost sheet already exists for this enquiry
$check_sql = "SELECT * FROM cost_sheets WHERE enquiry_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

// Generate cost sheet number
$cost_sheet_number = '';

// If cost sheet exists, load the data
if(mysqli_num_rows($check_result) > 0) {
    $cost_sheet = mysqli_fetch_assoc($check_result);
    $cost_sheet_number = $cost_sheet['cost_sheet_number'] ?? generateNumber('cost_sheet', $conn);
    $services_data = json_decode($cost_sheet['services_data'], true);
    $payment_data = json_decode($cost_sheet['payment_data'], true);
    
    // Extract data from services_data
    $services = $services_data['services'] ?? [];
    $travel_details = $services_data['travel_details'] ?? [];
    $visa_flight_ticket = $services_data['visa_flight_ticket'] ?? [];
    $accommodation = $services_data['accommodation'] ?? [];
    $transportation = $services_data['transportation'] ?? [];
    $cruise_hire = $services_data['cruise_hire'] ?? [];
    $extras = $services_data['extras'] ?? [];
    
    // Get other fields
    $package_cost = $cost_sheet['package_cost'];
    $tax_percentage = $cost_sheet['tax_percentage'];
    $currency_rate = 1; // Default to 1 if not stored
    $currency = $cost_sheet['currency'];
} else {
    // Generate new cost sheet number for new cost sheet
    $cost_sheet_number = generateNumber('cost_sheet', $conn);
}

// Process form submission
$success_message = "";
$error_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get selected services
    if(isset($_POST['services'])) {
        $services = $_POST['services'];
    }
    
    // Get travel details
    if(isset($_POST['travel_details'])) {
        $travel_details = $_POST['travel_details'];
    }
    
    // Handle receipt uploads
    $payment_data = [];
    if(isset($_POST['payment'])) {
        // Create receipts directory if it doesn't exist
        $receipts_dir = "receipts";
        if(!is_dir($receipts_dir)) {
            mkdir($receipts_dir, 0755, true);
        }
        
        // Process each payment row
        foreach($_POST['payment'] as $index => $payment) {
            $payment_data[$index] = $payment;
            
            // Check if a file was uploaded for this payment
            $file_key = "payment_receipt_" . $index;
            if(isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                $file_name = $_FILES[$file_key]['name'];
                $file_tmp = $_FILES[$file_key]['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Generate a unique filename
                $new_file_name = "receipt_" . $enquiry_id . "_" . $index . "_" . time() . "." . $file_ext;
                $destination = $receipts_dir . "/" . $new_file_name;
                
                // Move the uploaded file
                move_uploaded_file($file_tmp, $destination);
                
                // Store the file path in the payment data
                $payment_data[$index]['receipt'] = $destination;
            }
        }
    }
    
    // Save to database
    try {
        // Clean numeric values
        $total_expense = isset($_POST['total_expense']) ? preg_replace('/[^0-9.]/', '', $_POST['total_expense']) : 0;
        $package_cost = isset($_POST['package_cost']) ? preg_replace('/[^0-9.]/', '', $_POST['package_cost']) : 0;
        $markup_percentage = isset($_POST['markup_percentage']) ? preg_replace('/[^0-9.]/', '', $_POST['markup_percentage']) : 0;
        $tax_percentage = isset($_POST['tax_percentage']) ? $_POST['tax_percentage'] : 0;
        $tax_amount = isset($_POST['tax_amount']) ? preg_replace('/[^0-9.]/', '', $_POST['tax_amount']) : 0;
        $final_amount = isset($_POST['final_amount']) ? preg_replace('/[^0-9.]/', '', $_POST['final_amount']) : 0;
        $currency = isset($_POST['currency']) ? $_POST['currency'] : 'USD';
        
        // Prepare services data for JSON storage
        $services_data = [
            'services' => $services,
            'travel_details' => $travel_details,
            'visa_flight_ticket' => isset($_POST['visa_flight_ticket']) ? $_POST['visa_flight_ticket'] : [],
            'accommodation' => isset($_POST['accommodation']) ? $_POST['accommodation'] : [],
            'transportation' => isset($_POST['transportation']) ? $_POST['transportation'] : [],
            'cruise_hire' => isset($_POST['cruise_hire']) ? $_POST['cruise_hire'] : [],
            'extras' => isset($_POST['extras']) ? $_POST['extras'] : []
        ];
        
        // Check if cost sheet already exists for this enquiry
        $check_sql = "SELECT id FROM cost_sheets WHERE enquiry_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $enquiry_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) > 0) {
            // Update existing cost sheet
            $cost_sheet = mysqli_fetch_assoc($check_result);
            $cost_sheet_id = $cost_sheet['id'];
            
            // Use direct query instead of prepared statement to avoid binding issues
            $services_data_json = mysqli_real_escape_string($conn, json_encode($services_data));
            $payment_data_json = mysqli_real_escape_string($conn, json_encode($payment_data));
            $customer_name = mysqli_real_escape_string($conn, $enquiry['customer_name']);
            $enquiry_number = mysqli_real_escape_string($conn, $enquiry['enquiry_number']);
            
            $update_sql = "UPDATE cost_sheets SET 
                customer_name = '$customer_name',
                enquiry_number = '$enquiry_number',
                currency = '$currency',
                total_expense = $total_expense,
                package_cost = $package_cost,
                markup_percentage = $markup_percentage,
                tax_percentage = $tax_percentage,
                tax_amount = $tax_amount,
                final_amount = $final_amount,
                services_data = '$services_data_json',
                payment_data = '$payment_data_json',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $cost_sheet_id";
            
            if(mysqli_query($conn, $update_sql)) {
                $success_message = "Cost sheet updated successfully!";
            } else {
                $error_message = "Error updating cost sheet: " . mysqli_error($conn);
            }
            
        } else {
            // Insert new cost sheet
            // Use direct query instead of prepared statement to avoid binding issues
            $user_id = $_SESSION["id"]; // Using the correct session variable
            $services_data_json = mysqli_real_escape_string($conn, json_encode($services_data));
            $payment_data_json = mysqli_real_escape_string($conn, json_encode($payment_data));
            $customer_name = mysqli_real_escape_string($conn, $enquiry['customer_name']);
            $enquiry_number = mysqli_real_escape_string($conn, $enquiry['enquiry_number']);
            
            $insert_sql = "INSERT INTO cost_sheets (
                enquiry_id,
                customer_name,
                enquiry_number,
                cost_sheet_number,
                currency,
                total_expense,
                package_cost,
                markup_percentage,
                tax_percentage,
                tax_amount,
                final_amount,
                services_data,
                payment_data,
                created_by
            ) VALUES (
                $enquiry_id,
                '$customer_name',
                '$enquiry_number',
                '$cost_sheet_number',
                '$currency',
                $total_expense,
                $package_cost,
                $markup_percentage,
                $tax_percentage,
                $tax_amount,
                $final_amount,
                '$services_data_json',
                '$payment_data_json',
                $user_id
            )";
            
            if(mysqli_query($conn, $insert_sql)) {
                $success_message = "Cost sheet saved successfully!";
            } else {
                $error_message = "Error saving cost sheet: " . mysqli_error($conn);
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>



<div class="card-box mb-30">
    <div class="receipt-header">
        <h4 class="text-info font-weight-bold">COST SHEET</h4>
        <p class="mb-0">Cost Sheet No: <?php echo htmlspecialchars($cost_sheet_number); ?></p>
        <p class="mb-0">Reference: <?php echo htmlspecialchars($enquiry['enquiry_number']); ?></p>
        <p class="mb-0">Date: <?php echo date('d-m-Y'); ?></p>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    </div>
    <div class="pb-10 pd-10">
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $enquiry_id); ?>" id="cost-sheet-form" enctype="multipart/form-data">
            <!-- Customer Information -->
            <div class="row mb-2">
                <div class="col-md-12 table-responsive">
                    <style>
                        body {
                            font-size: 0.6rem;
                        }
                        .card-box {
                            max-width: 1000px;
                            margin: 0 auto;
                            border: 1px solid #ddd;
                            box-shadow: 0 0 10px rgba(0,0,0,0.1);
                            background-color: #fff;
                            padding: 0.5rem;
                        }
                        .section-divider {
                            border-top: 1px dashed #ddd;
                            margin: 0.5rem 0;
                        }
                        .bg-light-blue {
                            background-color: #f0f8ff;
                        }
                        .table td, .table th {
                            vertical-align: middle;
                            padding: 0.2rem;
                            font-size: 0.6rem;
                            text-align: left;
                        }
                        .custom-control-label {
                            font-weight: normal;
                            font-size: 0.6rem;
                            text-align: left;
                        }
                        .form-control-sm {
                            height: calc(1.2em + 0.4rem + 2px);
                            padding: 0.1rem 0.2rem;
                            font-size: 0.6rem;
                        }
                        .mb-2 {
                            margin-bottom: 0.2rem !important;
                        }
                        .mt-2 {
                            margin-top: 0.2rem !important;
                        }
                        .p-2 {
                            padding: 0.2rem !important;
                        }
                        .empty-cell {
                            background-color: #f9f9f9;
                        }
                        .fa-plus, .fa-minus {
                            font-size: 0.6rem;
                        }
                        .btn-sm {
                            padding: 0.1rem 0.3rem;
                            font-size: 0.6rem;
                        }
                        .sticky-top {
                            position: sticky;
                            top: 0;
                            z-index: 1;
                        }
                        h5 {
                            font-size: 0.8rem;
                            font-weight: bold;
                        }
                        h6 {
                            font-size: 0.7rem;
                            font-weight: bold;
                        }
                        .receipt-header {
                            text-align: center;
                            padding: 10px;
                            border-bottom: 1px solid #ddd;
                        }
                        .receipt-header h4 {
                            margin: 0;
                            font-size: 1rem;
                        }
                        .receipt-footer {
                            text-align: center;
                            padding: 10px;
                            border-top: 1px solid #ddd;
                            font-size: 0.6rem;
                        }
                        .receipt-table {
                            border: 1px solid #dee2e6;
                            border-radius: 4px;
                            padding: 0.2rem;
                            margin-bottom: 0.5rem;
                        }
                        .receipt-table table {
                            margin-bottom: 0;
                        }
                        .service-section h5 {
                            border-radius: 4px;
                            margin-bottom: 0.3rem;
                        }
                        .table-bordered th, .table-bordered td {
                            border: 1px solid #dee2e6;
                        }
                        .table thead th {
                            border-bottom: 2px solid #dee2e6;
                            background-color: #f8f9fa;
                        }
                        @media print {
                            body {
                                font-size: 0.6rem;
                            }
                            .btn, .no-print {
                                display: none !important;
                            }
                            .card-box {
                                border: none;
                                box-shadow: none;
                            }
                            .table {
                                width: 100% !important;
                            }
                            .table td, .table th {
                                padding: 0.1rem;
                            }
                        }
                        .text-right {
                            text-align: right !important;
                        }
                        .text-left {
                            text-align: left !important;
                        }
                        .service-section {
                            margin-bottom: 0.5rem !important;
                        }
                    </style>
                    <table class="table table-bordered table-sm" style="table-layout: fixed; width: 100%;">
                        <tr>
                            <td class="bg-light" width="15%" style="font-size: 0.8rem;"><strong>GUEST NAME</strong></td>
                            <td width="20%"><input type="text" class="form-control form-control-sm" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>"></td>
                            <td colspan="2" rowspan="3" class="align-middle bg-light-blue" width="30%" style="padding: 0.3rem;">
                                <strong class="d-block p-1 bg-info text-white rounded" style="font-size: 0.9rem; padding-left: 0.5rem;">SERVICES</strong>
                                <div class="mt-1">
                                    <table class="table table-sm mb-0" style="font-size: 0.8rem;">
                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="visa-flight" name="services[]" value="visa_flight" <?php echo in_array('visa_flight', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="visa-flight">VISA / FLIGHT BOOKING</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="accommodation" name="services[]" value="accommodation" <?php echo in_array('accommodation', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="accommodation">ACCOMMODATION</label>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="cruise-hire" name="services[]" value="cruise_hire" <?php echo in_array('cruise_hire', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="cruise-hire">CRUISE HIRE</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="transportation" name="services[]" value="transportation" <?php echo in_array('transportation', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="transportation">TRANSPORTATION</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="extras" name="services[]" value="extras" <?php echo in_array('extras', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="extras">EXTRAS/MISCELLANOUS</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-0 border-0">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input service-checkbox" id="travel-insurance" name="services[]" value="travel_insurance" <?php echo in_array('travel_insurance', $services) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="travel-insurance">TRAVEL INSURANCE</label>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td class="bg-light" width="15%" style="font-size: 0.8rem;"><strong>ENQUIRY NUMBER</strong></td>
                            <td class="font-weight-bold" width="20%"><?php echo htmlspecialchars($enquiry['enquiry_number']); ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>FILE NUMBER</strong></td>
                            <td class="font-weight-bold" style="font-size: 0.8rem;"><?php echo htmlspecialchars($enquiry['enquiry_number'] ?? 'Auto'); ?></td>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>ENQUIRY DATE</strong></td>
                            <td class="font-weight-bold" style="font-size: 0.8rem;"><?php echo $enquiry['enquiry_date'] ? date('d-m-Y', strtotime($enquiry['enquiry_date'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>GUEST ADDRESS</strong></td>
                            <td><input type="text" class="form-control form-control-sm" name="guest_address" placeholder="Not Mandatory"></td>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>Quotation/Booking Date</strong></td>
                            <td class="font-weight-bold" style="font-size: 0.8rem;"><?php echo $enquiry['booking_date'] ? date('d-m-Y', strtotime($enquiry['booking_date'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>EMAIL ID</strong></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($enquiry['email'] ?? 'N/A'); ?></td>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>SOURCE / AGENT</strong></td>
                            <td><input type="text" class="form-control form-control-sm" name="source_agent" value="<?php echo htmlspecialchars($enquiry['source_name'] ?? ''); ?>" placeholder="auto fetch +edit"></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>NATIONALITY</strong></td>
                            <td>
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
                                    <option value="BV">Bouvet Island</option>
                                    <option value="BR">Brazil</option>
                                    <option value="IO">British Indian Ocean Territory</option>
                                    <option value="BN">Brunei Darussalam</option>
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
                                    <option value="CX">Christmas Island</option>
                                    <option value="CC">Cocos (Keeling) Islands</option>
                                    <option value="CO">Colombia</option>
                                    <option value="KM">Comoros</option>
                                    <option value="CG">Congo</option>
                                    <option value="CD">Congo, Democratic Republic</option>
                                    <option value="CK">Cook Islands</option>
                                    <option value="CR">Costa Rica</option>
                                    <option value="CI">Cote D'Ivoire</option>
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
                                    <option value="FK">Falkland Islands</option>
                                    <option value="FO">Faroe Islands</option>
                                    <option value="FJ">Fiji</option>
                                    <option value="FI">Finland</option>
                                    <option value="FR">France</option>
                                    <option value="GF">French Guiana</option>
                                    <option value="PF">French Polynesia</option>
                                    <option value="TF">French Southern Territories</option>
                                    <option value="GA">Gabon</option>
                                    <option value="GM">Gambia</option>
                                    <option value="GE">Georgia</option>
                                    <option value="DE">Germany</option>
                                    <option value="GH">Ghana</option>
                                    <option value="GI">Gibraltar</option>
                                    <option value="GR">Greece</option>
                                    <option value="GL">Greenland</option>
                                    <option value="GD">Grenada</option>
                                    <option value="GP">Guadeloupe</option>
                                    <option value="GU">Guam</option>
                                    <option value="GT">Guatemala</option>
                                    <option value="GN">Guinea</option>
                                    <option value="GW">Guinea-Bissau</option>
                                    <option value="GY">Guyana</option>
                                    <option value="HT">Haiti</option>
                                    <option value="HM">Heard and Mcdonald Islands</option>
                                    <option value="VA">Holy See (Vatican City)</option>
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
                                    <option value="KP">Korea, North</option>
                                    <option value="KR">Korea, South</option>
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
                                    <option value="MO">Macau</option>
                                    <option value="MK">Macedonia</option>
                                    <option value="MG">Madagascar</option>
                                    <option value="MW">Malawi</option>
                                    <option value="MY">Malaysia</option>
                                    <option value="MV">Maldives</option>
                                    <option value="ML">Mali</option>
                                    <option value="MT">Malta</option>
                                    <option value="MH">Marshall Islands</option>
                                    <option value="MQ">Martinique</option>
                                    <option value="MR">Mauritania</option>
                                    <option value="MU">Mauritius</option>
                                    <option value="YT">Mayotte</option>
                                    <option value="MX">Mexico</option>
                                    <option value="FM">Micronesia</option>
                                    <option value="MD">Moldova</option>
                                    <option value="MC">Monaco</option>
                                    <option value="MN">Mongolia</option>
                                    <option value="MS">Montserrat</option>
                                    <option value="MA">Morocco</option>
                                    <option value="MZ">Mozambique</option>
                                    <option value="MM">Myanmar</option>
                                    <option value="NA">Namibia</option>
                                    <option value="NR">Nauru</option>
                                    <option value="NP">Nepal</option>
                                    <option value="NL">Netherlands</option>
                                    <option value="AN">Netherlands Antilles</option>
                                    <option value="NC">New Caledonia</option>
                                    <option value="NZ">New Zealand</option>
                                    <option value="NI">Nicaragua</option>
                                    <option value="NE">Niger</option>
                                    <option value="NG">Nigeria</option>
                                    <option value="NU">Niue</option>
                                    <option value="NF">Norfolk Island</option>
                                    <option value="MP">Northern Mariana Islands</option>
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
                                    <option value="PN">Pitcairn</option>
                                    <option value="PL">Poland</option>
                                    <option value="PT">Portugal</option>
                                    <option value="PR">Puerto Rico</option>
                                    <option value="QA">Qatar</option>
                                    <option value="RE">Reunion</option>
                                    <option value="RO">Romania</option>
                                    <option value="RU">Russian Federation</option>
                                    <option value="RW">Rwanda</option>
                                    <option value="SH">Saint Helena</option>
                                    <option value="KN">Saint Kitts and Nevis</option>
                                    <option value="LC">Saint Lucia</option>
                                    <option value="PM">Saint Pierre and Miquelon</option>
                                    <option value="VC">Saint Vincent and Grenadines</option>
                                    <option value="WS">Samoa</option>
                                    <option value="SM">San Marino</option>
                                    <option value="ST">Sao Tome and Principe</option>
                                    <option value="SA">Saudi Arabia</option>
                                    <option value="SN">Senegal</option>
                                    <option value="SC">Seychelles</option>
                                    <option value="SL">Sierra Leone</option>
                                    <option value="SG">Singapore</option>
                                    <option value="SK">Slovakia</option>
                                    <option value="SI">Slovenia</option>
                                    <option value="SB">Solomon Islands</option>
                                    <option value="SO">Somalia</option>
                                    <option value="ZA">South Africa</option>
                                    <option value="GS">South Georgia</option>
                                    <option value="ES">Spain</option>
                                    <option value="LK">Sri Lanka</option>
                                    <option value="SD">Sudan</option>
                                    <option value="SR">Suriname</option>
                                    <option value="SJ">Svalbard and Jan Mayen</option>
                                    <option value="SZ">Swaziland</option>
                                    <option value="SE">Sweden</option>
                                    <option value="CH">Switzerland</option>
                                    <option value="SY">Syrian Arab Republic</option>
                                    <option value="TW">Taiwan</option>
                                    <option value="TJ">Tajikistan</option>
                                    <option value="TZ">Tanzania</option>
                                    <option value="TH">Thailand</option>
                                    <option value="TL">Timor-Leste</option>
                                    <option value="TG">Togo</option>
                                    <option value="TK">Tokelau</option>
                                    <option value="TO">Tonga</option>
                                    <option value="TT">Trinidad and Tobago</option>
                                    <option value="TN">Tunisia</option>
                                    <option value="TR">Turkey</option>
                                    <option value="TM">Turkmenistan</option>
                                    <option value="TC">Turks and Caicos Islands</option>
                                    <option value="TV">Tuvalu</option>
                                    <option value="UG">Uganda</option>
                                    <option value="UA">Ukraine</option>
                                    <option value="AE">United Arab Emirates</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="US">United States</option>
                                    <option value="UM">United States Minor Outlying Islands</option>
                                    <option value="UY">Uruguay</option>
                                    <option value="UZ">Uzbekistan</option>
                                    <option value="VU">Vanuatu</option>
                                    <option value="VE">Venezuela</option>
                                    <option value="VN">Vietnam</option>
                                    <option value="VG">Virgin Islands, British</option>
                                    <option value="VI">Virgin Islands, U.S.</option>
                                    <option value="WF">Wallis and Futuna</option>
                                    <option value="EH">Western Sahara</option>
                                    <option value="YE">Yemen</option>
                                    <option value="ZM">Zambia</option>
                                    <option value="ZW">Zimbabwe</option>
                                </select>
                            </td>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>CURRENCY</strong></td>
                            <td>
                                <select class="form-control form-control-sm" name="currency" id="currency-select">
                                    <option value="USD" data-symbol="USD" <?php echo (isset($currency) && $currency == 'USD') ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" data-symbol="EUR" <?php echo (isset($currency) && $currency == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                                    <option value="GBP" data-symbol="GBP" <?php echo (isset($currency) && $currency == 'GBP') ? 'selected' : ''; ?>>GBP</option>
                                    <option value="INR" data-symbol="INR" <?php echo (isset($currency) && $currency == 'INR') ? 'selected' : ''; ?>>INR</option>
                                    <option value="BHD" data-symbol="BHD" <?php echo (isset($currency) && $currency == 'BHD') ? 'selected' : ''; ?>>BHD</option>
                                    <option value="KWD" data-symbol="KWD" <?php echo (isset($currency) && $currency == 'KWD') ? 'selected' : ''; ?>>KWD</option>
                                    <option value="OMR" data-symbol="OMR" <?php echo (isset($currency) && $currency == 'OMR') ? 'selected' : ''; ?>>OMR</option>
                                    <option value="QAR" data-symbol="QAR" <?php echo (isset($currency) && $currency == 'QAR') ? 'selected' : ''; ?>>QAR</option>
                                    <option value="SAR" data-symbol="SAR" <?php echo (isset($currency) && $currency == 'SAR') ? 'selected' : ''; ?>>SAR</option>
                                    <option value="AED" data-symbol="AED" <?php echo (isset($currency) && $currency == 'AED') ? 'selected' : ''; ?>>AED</option>
                                    <option value="GCC" data-symbol="GCC" <?php echo (isset($currency) && $currency == 'GCC') ? 'selected' : ''; ?>>GCC</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>TOUR PACKAGE</strong></td>
                            <td>
                                <select class="form-control form-control-sm" name="tour_package" id="tour-package-select">
                                    <option value="">Select Package</option>
                                </select>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>TRAVEL DESTINATIONS</strong></td>
                            <td><?php echo htmlspecialchars($enquiry['destination_name'] ?? ''); ?></td>
                            <td colspan="2" rowspan="1" class="bg-light" style="font-size: 0.8rem;"><strong>SECTOR /DEPARTMENT</strong></td>
                            <td colspan="2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($enquiry['department_name'] ?? 'Auto'); ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>CONTACT NUMBER</strong></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($enquiry['phone'] ?? 'N/A'); ?></td>
                            <td colspan="2" rowspan="2" class="align-middle bg-light-blue" style="padding: 0.3rem;">
                                <strong class="d-block p-1 bg-info text-white rounded" style="font-size: 0.9rem; padding-left: 0.5rem;">NUMBER OF PAX</strong>
                                <?php 
                                $adults = intval($enquiry['adults_count'] ?? 0);
                                $children = intval($enquiry['children_count'] ?? 0);
                                $infants = intval($enquiry['infants_count'] ?? 0);
                                $total_pax = $adults + $children + $infants;
                                ?>
                                <table class="table table-sm mb-0" style="font-size: 0.8rem;">
                                    <tr>
                                        <td class="bg-light py-1"><strong>ADULTS</strong></td>
                                        <td class="py-1"><?php echo $adults; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light py-1"><strong>CHILDREN</strong></td>
                                        <td class="py-1"><?php echo $children; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light py-1"><strong>CHILDREN AGES</strong></td>
                                        <td class="py-1"><input type="text" class="form-control form-control-sm" name="children_ages" placeholder="Custom" style="height: 25px;"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light py-1"><strong>INFANT</strong></td>
                                        <td class="py-1"><?php echo $infants; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light py-1"><strong>TOTAL PAX</strong></td>
                                        <td class="font-weight-bold text-info py-1"><?php echo $total_pax; ?></td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td class="bg-light" style="font-size: 0.8rem;"><strong>WHATSAPP NUMBER</strong></td>
                            <td><input type="text" class="form-control form-control-sm" name="whatsapp_number"></td>
                            <td colspan="2"></td>
                            <td colspan="2"></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="section-divider"></div>
            
            <!-- Services are now included in the header table above -->
            
            <!-- Service Details Sections -->
            <div id="visa-flight-section" class="service-section mb-4" style="display: none;">
                <h5 class="bg-light p-2 text-center">VISA / FLIGHT BOOKING</h5>
                <div class="table-responsive receipt-table">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>TRAVEL PERIOD</th>
                                <th>DATE</th>
                                <th>CITY</th>
                                <th>FLIGHT</th>
                                <th>NIGHTS/DAYS</th>
                                <th>Flight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ARRIVAL</td>
                                <td>
                                    <input type="date" class="form-control" name="travel_details[arrival_date]" value="<?php echo $enquiry['travel_start_date'] ?? ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[arrival_city]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[arrival_flight]" value="">
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[arrival_nights_days]">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[arrival_connection]">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection flight</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info add-connecting-flight" data-type="arrival"><i class="fa fa-plus"></i></button>
                                </td>
                            </tr>
                            <tr id="arrival-connecting-flight" style="display: none;">
                                <td>ARRIVAL (Connecting)</td>
                                <td>
                                    <input type="date" class="form-control" name="travel_details[arrival_connecting_date]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[arrival_connecting_city]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[arrival_connecting_flight]" value="">
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[arrival_connecting_nights_days]">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[arrival_connecting_type]">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection flight</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-connecting-flight" data-type="arrival"><i class="fa fa-minus"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>DEPARTURE</td>
                                <td>
                                    <input type="date" class="form-control" name="travel_details[departure_date]" value="<?php echo $enquiry['travel_end_date'] ?? ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[departure_city]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[departure_flight]" value="">
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[departure_nights_days]">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[departure_connection]">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection flight</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info add-connecting-flight" data-type="departure"><i class="fa fa-plus"></i></button>
                                </td>
                            </tr>
                            <tr id="departure-connecting-flight" style="display: none;">
                                <td>DEPARTURE (Connecting)</td>
                                <td>
                                    <input type="date" class="form-control" name="travel_details[departure_connecting_date]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[departure_connecting_city]" value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="travel_details[departure_connecting_flight]" value="">
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[departure_connecting_nights_days]">
                                        <option value="">Select</option>
                                        <option value="Day">Day Flight</option>
                                        <option value="Night">Night Flight</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="travel_details[departure_connecting_type]">
                                        <option value="">Select</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Connection">Connection flight</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-connecting-flight" data-type="departure"><i class="fa fa-minus"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h6 class="bg-light p-2 mt-3 text-center">FLIGHT TICKET</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. No</th>
                                <th>SECTOR</th>
                                <th>SUPPLIER</th>
                                <th colspan="2">DATE of TRAVELS</th>
                                <th>NO OF PASSENGER</th>
                                <th>RATE / PERSON</th>
                                <th>ROE</th>
                                <th>TOTAL</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th>ONWARD</th>
                                <th>RETURN</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[0][sector]" value="<?php echo isset($visa_flight_ticket[0]['sector']) ? htmlspecialchars($visa_flight_ticket[0]['sector']) : ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[0][supplier]" value="<?php echo isset($visa_flight_ticket[0]['supplier']) ? htmlspecialchars($visa_flight_ticket[0]['supplier']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="visa_flight_ticket[0][onward]" value="<?php echo isset($visa_flight_ticket[0]['onward']) ? htmlspecialchars($visa_flight_ticket[0]['onward']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="visa_flight_ticket[0][return]" value="<?php echo isset($visa_flight_ticket[0]['return']) ? htmlspecialchars($visa_flight_ticket[0]['return']) : ''; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm passenger-count" name="visa_flight_ticket[0][passenger_count]" data-row="0" value="<?php echo isset($visa_flight_ticket[0]['passenger_count']) ? intval($visa_flight_ticket[0]['passenger_count']) : 0; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm rate-per-person" name="visa_flight_ticket[0][rate_per_person]" data-row="0" value="<?php echo isset($visa_flight_ticket[0]['rate_per_person']) ? floatval($visa_flight_ticket[0]['rate_per_person']) : 0; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[0][roe]" value="<?php echo isset($visa_flight_ticket[0]['roe']) ? htmlspecialchars($visa_flight_ticket[0]['roe']) : '30'; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm subtotal" name="visa_flight_ticket[0][total]" data-row="0" readonly></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[1][sector]" value="<?php echo isset($visa_flight_ticket[1]['sector']) ? htmlspecialchars($visa_flight_ticket[1]['sector']) : ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[1][supplier]" value="<?php echo isset($visa_flight_ticket[1]['supplier']) ? htmlspecialchars($visa_flight_ticket[1]['supplier']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="visa_flight_ticket[1][onward]" value="<?php echo isset($visa_flight_ticket[1]['onward']) ? htmlspecialchars($visa_flight_ticket[1]['onward']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="visa_flight_ticket[1][return]" value="<?php echo isset($visa_flight_ticket[1]['return']) ? htmlspecialchars($visa_flight_ticket[1]['return']) : ''; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm passenger-count" name="visa_flight_ticket[1][passenger_count]" data-row="1" value="<?php echo isset($visa_flight_ticket[1]['passenger_count']) ? intval($visa_flight_ticket[1]['passenger_count']) : 0; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm rate-per-person" name="visa_flight_ticket[1][rate_per_person]" data-row="1" value="<?php echo isset($visa_flight_ticket[1]['rate_per_person']) ? floatval($visa_flight_ticket[1]['rate_per_person']) : 0; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="visa_flight_ticket[1][roe]" value="<?php echo isset($visa_flight_ticket[1]['roe']) ? htmlspecialchars($visa_flight_ticket[1]['roe']) : '30'; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm subtotal" name="visa_flight_ticket[1][total]" data-row="1" readonly></td>
                            </tr>
                            <tr>
                                <td colspan="7"></td>
                                <td class="bg-light"><strong>TOTAL INR</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="visa_flight_total" readonly></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="bg-light"><strong>OTHER EXPENSES</strong></td>
                                <td colspan="5"><input type="text" class="form-control form-control-sm" name="visa_flight_other_expenses"></td>
                                <td class="bg-light"><strong>Total</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="visa_flight_grand_total" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="transportation-section" class="service-section mb-4" style="display: none;">
                <h5 class="bg-light p-2 text-center">INTERNAL TRANSPORTATION</h5>
                <div class="table-responsive receipt-table">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>S. NO</th>
                                <th>SUPPLIER</th>
                                <th>CAR TYPE</th>
                                <th colspan="2">RENT TYPE</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td rowspan="6">1</td>
                                <td rowspan="6"><input type="text" class="form-control form-control-sm" name="transportation[0][supplier]" value="<?php echo isset($transportation[0]['supplier']) ? htmlspecialchars($transportation[0]['supplier']) : ''; ?>"></td>
                                <td rowspan="6"><input type="text" class="form-control form-control-sm" name="transportation[0][car_type]" value="<?php echo isset($transportation[0]['car_type']) ? htmlspecialchars($transportation[0]['car_type']) : ''; ?>"></td>
                                <td>DAILY RENT</td>
                                <td><input type="number" class="form-control form-control-sm trans-daily-rent" name="transportation[0][daily_rent]" data-row="0" value="<?php echo isset($transportation[0]['daily_rent']) ? floatval($transportation[0]['daily_rent']) : 0; ?>"></td>
                                <td rowspan="6"><input type="text" class="form-control form-control-sm trans-total" name="transportation[0][total]" data-row="0" readonly></td>
                            </tr>
                            <tr>
                                <td>NUMBER OF DAYS</td>
                                <td><input type="number" class="form-control form-control-sm trans-days" name="transportation[0][days]" data-row="0" value="<?php echo isset($transportation[0]['days']) ? intval($transportation[0]['days']) : 2; ?>"></td>
                            </tr>
                            <tr>
                                <td>KM</td>
                                <td><input type="number" class="form-control form-control-sm trans-km" name="transportation[0][km]" data-row="0" value="<?php echo isset($transportation[0]['km']) ? floatval($transportation[0]['km']) : 0; ?>" placeholder="KM"></td>
                            </tr>
                            <tr>
                                <td>EXTRA KM</td>
                                <td><input type="number" class="form-control form-control-sm trans-extra-km" name="transportation[0][extra_km]" data-row="0" value="<?php echo isset($transportation[0]['extra_km']) ? floatval($transportation[0]['extra_km']) : 0; ?>" placeholder="Extra KM"></td>
                            </tr>
                            <tr>
                                <td>PRICE/KM</td>
                                <td><input type="number" class="form-control form-control-sm trans-price-per-km" name="transportation[0][price_per_km]" data-row="0" value="<?php echo isset($transportation[0]['price_per_km']) ? floatval($transportation[0]['price_per_km']) : 0; ?>" placeholder="Price per KM"></td>
                            </tr>
                            <tr>
                                <td>TOLL/ PARKING</td>
                                <td><input type="number" class="form-control form-control-sm trans-toll" name="transportation[0][toll]" data-row="0" value="<?php echo isset($transportation[0]['toll']) ? floatval($transportation[0]['toll']) : 0; ?>"></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td class="bg-light"><strong>TOTAL</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="transportation_total" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="cruise-hire-section" class="service-section mb-4" style="display: none;">
                <h5 class="bg-light p-2 text-center">CRUISE HIRE</h5>
                <div class="table-responsive receipt-table">
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
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[0][supplier]" value="<?php echo isset($cruise_hire[0]['supplier']) ? htmlspecialchars($cruise_hire[0]['supplier']) : ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[0][boat_type]" value="<?php echo isset($cruise_hire[0]['boat_type']) ? htmlspecialchars($cruise_hire[0]['boat_type']) : ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[0][cruise_type]" value="<?php echo isset($cruise_hire[0]['cruise_type']) ? htmlspecialchars($cruise_hire[0]['cruise_type']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="cruise_hire[0][check_in]" value="<?php echo isset($cruise_hire[0]['check_in']) ? htmlspecialchars($cruise_hire[0]['check_in']) : ''; ?>"></td>
                                <td><input type="date" class="form-control form-control-sm" name="cruise_hire[0][check_out]" value="<?php echo isset($cruise_hire[0]['check_out']) ? htmlspecialchars($cruise_hire[0]['check_out']) : ''; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise_hire[0][rate]" data-row="0" value="<?php echo isset($cruise_hire[0]['rate']) ? floatval($cruise_hire[0]['rate']) : 0; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise_hire[0][extra]" data-row="0" value="<?php echo isset($cruise_hire[0]['extra']) ? floatval($cruise_hire[0]['extra']) : 0; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise_hire[0][total]" data-row="0" readonly></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[1][supplier]"></td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[1][boat_type]"></td>
                                <td><input type="text" class="form-control form-control-sm" name="cruise_hire[1][cruise_type]"></td>
                                <td><input type="date" class="form-control form-control-sm" name="cruise_hire[1][check_in]"></td>
                                <td><input type="date" class="form-control form-control-sm" name="cruise_hire[1][check_out]"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-rate" name="cruise_hire[1][rate]" data-row="1" value="0"></td>
                                <td><input type="number" class="form-control form-control-sm cruise-extra" name="cruise_hire[1][extra]" data-row="1" value="0"></td>
                                <td><input type="text" class="form-control form-control-sm cruise-total" name="cruise_hire[1][total]" data-row="1" readonly></td>
                            </tr>
                            <tr>
                                <td colspan="7"></td>
                                <td class="bg-light"><strong>TOTAL</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="cruise_hire_total" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="extras-section" class="service-section mb-4" style="display: none;">
                <h5 class="bg-light p-2 text-center">EXTRAS/MISCELLANOUS</h5>
                <div class="table-responsive receipt-table">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>SUPPLIER</th>
                                <th>TYPE OF SERVICE</th>
                                <th>AMOUNT</th>
                                <th>EXTRAS</th>
                                <th>TOTAL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="extras-tbody">
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" name="extras[0][supplier]" value="<?php echo isset($extras[0]['supplier']) ? htmlspecialchars($extras[0]['supplier']) : ''; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="extras[0][service_type]" value="<?php echo isset($extras[0]['service_type']) ? htmlspecialchars($extras[0]['service_type']) : ''; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[0][amount]" data-row="0" value="<?php echo isset($extras[0]['amount']) ? floatval($extras[0]['amount']) : 0; ?>"></td>
                                <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[0][extras]" data-row="0" value="<?php echo isset($extras[0]['extras']) ? floatval($extras[0]['extras']) : 0; ?>"></td>
                                <td><input type="text" class="form-control form-control-sm extras-total" name="extras[0][total]" data-row="0" readonly></td>
                                <td><button type="button" class="btn btn-sm btn-info add-extras-row"><i class="fa fa-plus"></i></button></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td class="bg-light"><strong>TOTAL</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><input type="text" class="form-control form-control-sm" id="extras_total" readonly></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="accommodation-section" class="service-section mb-4" style="display: none;">
                <h5 class="bg-light p-2 text-center">ACCOMMODATION</h5>
                <div class="table-responsive receipt-table" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>DESTINATION</th>
                                <th>HOTEL</th>
                                <th>CHECK IN</th>
                                <th>CHECK OUT</th>
                                <th>ROOM TYPE</th>
                                <th colspan="2">ROOMS</th>
                                <th colspan="2">EXTRA BED ADULT</th>
                                <th colspan="2">EXTRA BED CHILD</th>
                                <th colspan="2">CHILD NO BED</th>
                                <th>Nights</th>
                                <th>Meal Plan</th>
                                <th>Total</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th>NO</th>
                                <th>RATE</th>
                                <th>NO</th>
                                <th>RATE/bed</th>
                                <th>NO</th>
                                <th>RATE</th>
                                <th>NO</th>
                                <th>RATE</th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][destination]">
                                        <option value="">Select</option>
                                        <option value="Dubai" <?php echo (isset($accommodation[0]['destination']) && $accommodation[0]['destination'] == 'Dubai') ? 'selected' : ''; ?>>Dubai</option>
                                        <option value="Abu Dhabi" <?php echo (isset($accommodation[0]['destination']) && $accommodation[0]['destination'] == 'Abu Dhabi') ? 'selected' : ''; ?>>Abu Dhabi</option>
                                        <option value="Singapore" <?php echo (isset($accommodation[0]['destination']) && $accommodation[0]['destination'] == 'Singapore') ? 'selected' : ''; ?>>Singapore</option>
                                        <option value="Bangkok" <?php echo (isset($accommodation[0]['destination']) && $accommodation[0]['destination'] == 'Bangkok') ? 'selected' : ''; ?>>Bangkok</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][hotel]">
                                        <option value="">Select</option>
                                        <option value="Hilton" <?php echo (isset($accommodation[0]['hotel']) && $accommodation[0]['hotel'] == 'Hilton') ? 'selected' : ''; ?>>Hilton</option>
                                        <option value="Marriott" <?php echo (isset($accommodation[0]['hotel']) && $accommodation[0]['hotel'] == 'Marriott') ? 'selected' : ''; ?>>Marriott</option>
                                        <option value="Hyatt" <?php echo (isset($accommodation[0]['hotel']) && $accommodation[0]['hotel'] == 'Hyatt') ? 'selected' : ''; ?>>Hyatt</option>
                                        <option value="Sheraton" <?php echo (isset($accommodation[0]['hotel']) && $accommodation[0]['hotel'] == 'Sheraton') ? 'selected' : ''; ?>>Sheraton</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" class="form-control form-control-sm" name="accommodation[0][check_in]" value="<?php echo isset($accommodation[0]['check_in']) ? htmlspecialchars($accommodation[0]['check_in']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="date" class="form-control form-control-sm" name="accommodation[0][check_out]" value="<?php echo isset($accommodation[0]['check_out']) ? htmlspecialchars($accommodation[0]['check_out']) : ''; ?>">
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][room_type]">
                                        <option value="">Select</option>
                                        <option value="Standard" <?php echo (isset($accommodation[0]['room_type']) && $accommodation[0]['room_type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                        <option value="Deluxe" <?php echo (isset($accommodation[0]['room_type']) && $accommodation[0]['room_type'] == 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                        <option value="Suite" <?php echo (isset($accommodation[0]['room_type']) && $accommodation[0]['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[0][rooms_no]" data-row="0" value="<?php echo isset($accommodation[0]['rooms_no']) ? intval($accommodation[0]['rooms_no']) : 0; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[0][rooms_rate]" data-row="0" value="<?php echo isset($accommodation[0]['rooms_rate']) ? floatval($accommodation[0]['rooms_rate']) : 0; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[0][extra_adult_no]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[0][extra_adult_rate]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[0][extra_child_no]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[0][extra_child_rate]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[0][child_no_bed_no]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[0][child_no_bed_rate]" data-row="0" value="0">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm accom-nights" name="accommodation[0][nights]" data-row="0" value="0">
                                </td>
                                <td>
                                    <select class="form-control form-control-sm" name="accommodation[0][meal_plan]">
                                        <option value="">Select</option>
                                        <option value="BB" <?php echo (isset($accommodation[0]['meal_plan']) && $accommodation[0]['meal_plan'] == 'BB') ? 'selected' : ''; ?>>BB</option>
                                        <option value="HB" <?php echo (isset($accommodation[0]['meal_plan']) && $accommodation[0]['meal_plan'] == 'HB') ? 'selected' : ''; ?>>HB</option>
                                        <option value="FB" <?php echo (isset($accommodation[0]['meal_plan']) && $accommodation[0]['meal_plan'] == 'FB') ? 'selected' : ''; ?>>FB</option>
                                        <option value="AI" <?php echo (isset($accommodation[0]['meal_plan']) && $accommodation[0]['meal_plan'] == 'AI') ? 'selected' : ''; ?>>AI</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm accom-total" name="accommodation[0][total]" data-row="0" readonly>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="14"></td>
                                <td class="bg-light"><strong>TOTAL</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="accommodation_total" readonly></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary mt-2" id="add-accommodation-row">Add Row</button>
                </div>
            </div>
            
            <!-- Add other service sections as needed -->
            
            <!-- Payment Details -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5 class="bg-light p-2 text-center">PAYMENT DETAILS</h5>
                    <div class="table-responsive receipt-table">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>DATE</th>
                                    <th>BANK</th>
                                    <th>AMOUNT</th>
                                    <th>Receipt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payment-tbody">
                                <tr>
                                    <td><input type="date" class="form-control form-control-sm payment-date" name="payment[0][date]" value="<?php echo isset($payment_data[0]['date']) ? htmlspecialchars($payment_data[0]['date']) : ''; ?>"></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="payment[0][bank]">
                                            <option value="">Select Bank</option>
                                            <option value="HDFC BANK" <?php echo (isset($payment_data[0]['bank']) && $payment_data[0]['bank'] == 'HDFC BANK') ? 'selected' : ''; ?>>HDFC BANK</option>
                                            <option value="ICICI BANK" <?php echo (isset($payment_data[0]['bank']) && $payment_data[0]['bank'] == 'ICICI BANK') ? 'selected' : ''; ?>>ICICI BANK</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm payment-amount" name="payment[0][amount]" data-row="0" value="<?php echo isset($payment_data[0]['amount']) ? floatval($payment_data[0]['amount']) : 0; ?>"></td>
                                    <td><input type="file" class="form-control form-control-sm" name="payment_receipt_0" accept="image/*"></td>
                                    <td><button type="button" class="btn btn-sm btn-info add-payment-row"><i class="fa fa-plus"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="bg-light"><strong>TOTAL RECEIVED</strong></td>
                                    <td><input type="text" class="form-control form-control-sm" id="payment_total" readonly></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="bg-light"><strong>BALANCE AMOUNT TO BE COLLECTED</strong></td>
                                    <td><input type="text" class="form-control form-control-sm" id="payment_balance" readonly></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary and Profit Calculation -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5 class="bg-light p-2 text-center">SUMMARY</h5>
                    <div class="table-responsive receipt-table">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <td class="bg-light" width="25%"><strong>TOTAL EXPENSE</strong></td>
                                    <td width="25%"><input type="text" class="form-control form-control-sm" id="total_expense" name="total_expense" readonly></td>
                                    <td class="bg-light" width="25%"><strong>PACKAGE COST</strong></td>
                                    <td width="25%"><input type="number" class="form-control form-control-sm" id="package_cost" name="package_cost" value="<?php echo $package_cost; ?>"></td>
                                </tr>
                                <tr>
                                    <td class="bg-light"><strong>MARK UP (PROFIT) <span id="profit_percentage_label">0</span>%</strong></td>
                                    <td><input type="text" class="form-control form-control-sm" id="markup_amount" name="markup_amount" readonly></td>
                                    <td class="bg-light"><strong>TAX</strong></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select class="form-control form-control-sm" id="tax_percentage" name="tax_percentage">
                                                <option value="5" <?php echo ($tax_percentage == 5) ? 'selected' : ''; ?>>5%</option>
                                                <option value="18" <?php echo ($tax_percentage == 18) ? 'selected' : ''; ?>>18%</option>
                                                <option value="1.05" <?php echo ($tax_percentage == 1.05) ? 'selected' : ''; ?>>1.05%</option>
                                                <option value="1.18" <?php echo ($tax_percentage == 1.18) ? 'selected' : ''; ?>>1.18%</option>
                                            </select>
                                            <input type="text" class="form-control form-control-sm" id="tax_amount" name="tax_amount" readonly>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bg-light"><strong>Amount in</strong></td>
                                    <td><input type="text" class="form-control form-control-sm" id="currency_display" readonly></td>
                                    <td class="bg-light"><strong>CURRENCY CONVERSION RATE</strong></td>
                                    <td><input type="number" class="form-control form-control-sm" id="currency_rate" name="currency_rate" value="<?php echo $currency_rate; ?>"></td>
                                </tr>
                                <tr>
                                    <td class="bg-light"><strong>Net Cost</strong></td>
                                    <td colspan="3"><input type="text" class="form-control form-control-sm" id="final_amount" name="final_amount" readonly></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-info btn-sm">Save Cost Sheet</button>
                    <a href="view_leads.php" class="btn btn-secondary btn-sm">Back to Leads</a>
                    <!-- <button type="button" class="btn btn-success btn-sm" id="export-excel">Export to Excel</button> -->
                    <button type="button" class="btn btn-primary btn-sm" id="print-cost-sheet">Print</button>
                </div>
            </div>
            
            <div class="receipt-footer mt-3">
                <p class="mb-0">Thank you for your business!</p>
                <p class="mb-0">For any queries, please contact us at: info@example.com | +1234567890</p>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle service sections based on checkbox selection
        const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
        serviceCheckboxes.forEach(checkbox => {
            // Initial state
            toggleServiceSection(checkbox);
            
            // On change
            checkbox.addEventListener('change', function() {
                toggleServiceSection(this);
            });
        });
        
        function toggleServiceSection(checkbox) {
            const sectionId = checkbox.id + '-section';
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = checkbox.checked ? 'block' : 'none';
            }
        }
        
        // Add row to accommodation table
        let accommodationRowCount = 1;
        document.getElementById('add-accommodation-row').addEventListener('click', function() {
            const tbody = document.querySelector('#accommodation-section table tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][destination]">
                        <option value="">Select</option>
                        <option value="Dubai">Dubai</option>
                        <option value="Abu Dhabi">Abu Dhabi</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Bangkok">Bangkok</option>
                    </select>
                </td>
                <td>
                    <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][hotel]">
                        <option value="">Select</option>
                        <option value="Hilton">Hilton</option>
                        <option value="Marriott">Marriott</option>
                        <option value="Hyatt">Hyatt</option>
                        <option value="Sheraton">Sheraton</option>
                    </select>
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_in]">
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][check_out]">
                </td>
                <td>
                    <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][room_type]">
                        <option value="">Select</option>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-rooms-no" name="accommodation[${accommodationRowCount}][rooms_no]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-rooms-rate" name="accommodation[${accommodationRowCount}][rooms_rate]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-extra-adult-no" name="accommodation[${accommodationRowCount}][extra_adult_no]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-extra-adult-rate" name="accommodation[${accommodationRowCount}][extra_adult_rate]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-extra-child-no" name="accommodation[${accommodationRowCount}][extra_child_no]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-extra-child-rate" name="accommodation[${accommodationRowCount}][extra_child_rate]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-child-no-bed-no" name="accommodation[${accommodationRowCount}][child_no_bed_no]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-child-no-bed-rate" name="accommodation[${accommodationRowCount}][child_no_bed_rate]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm accom-nights" name="accommodation[${accommodationRowCount}][nights]" data-row="${accommodationRowCount}" value="0">
                </td>
                <td>
                    <select class="form-control form-control-sm" name="accommodation[${accommodationRowCount}][meal_plan]">
                        <option value="">Select</option>
                        <option value="BB">BB</option>
                        <option value="HB">HB</option>
                        <option value="FB">FB</option>
                        <option value="AI">AI</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm accom-total" name="accommodation[${accommodationRowCount}][total]" data-row="${accommodationRowCount}" readonly>
                </td>
            `;
            tbody.appendChild(newRow);
            
            // Add event listeners to the new row
            const newInputs = newRow.querySelectorAll('.accom-rooms-no, .accom-rooms-rate, .accom-extra-adult-no, .accom-extra-adult-rate, .accom-extra-child-no, .accom-extra-child-rate, .accom-child-no-bed-no, .accom-child-no-bed-rate, .accom-nights');
            newInputs.forEach(input => {
                input.addEventListener('input', function() {
                    calculateAccommodationTotal(this.getAttribute('data-row'));
                });
            });
            
            calculateAccommodationTotal(accommodationRowCount);
            accommodationRowCount++;
        });
        
        // Export to Excel functionality
        document.getElementById('export-excel').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'export_cost_sheet.php?id=<?php echo $enquiry_id; ?>';
        });
        
        // Add connecting flight functionality
        document.querySelectorAll('.add-connecting-flight').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                document.getElementById(type + '-connecting-flight').style.display = 'table-row';
                this.style.display = 'none';
            });
        });
        
        // Remove connecting flight functionality
        document.querySelectorAll('.remove-connecting-flight').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                document.getElementById(type + '-connecting-flight').style.display = 'none';
                document.querySelector(`.add-connecting-flight[data-type="${type}"]`).style.display = 'inline-block';
            });
        });
        
        // Calculate subtotals for VISA / FLIGHT TICKET
        function calculateSubtotal(row) {
            const symbol = getCurrencySymbol();
            const passengerCount = parseFloat(document.querySelector(`.passenger-count[data-row="${row}"]`).value) || 0;
            const ratePerPerson = parseFloat(document.querySelector(`.rate-per-person[data-row="${row}"]`).value) || 0;
            const subtotal = passengerCount * ratePerPerson;
            document.querySelector(`.subtotal[data-row="${row}"]`).value = `${symbol} ${subtotal.toFixed(2)}`;
            calculateTotal();
        }
        
        function calculateTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.subtotal').forEach(input => {
                const value = input.value.replace(/[^0-9.-]+/g, '');
                total += parseFloat(value) || 0;
            });
            document.getElementById('visa_flight_total').value = `${symbol} ${total.toFixed(2)}`;
            document.getElementById('visa_flight_grand_total').value = `${symbol} ${total.toFixed(2)}`;
            calculateSummary();
        }
        
        // Add event listeners for calculation
        document.querySelectorAll('.passenger-count, .rate-per-person').forEach(input => {
            input.addEventListener('input', function() {
                calculateSubtotal(this.getAttribute('data-row'));
            });
        });
        
        // Calculate accommodation totals
        function calculateAccommodationTotal(row) {
            const symbol = getCurrencySymbol();
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
            
            document.querySelector(`.accom-total[data-row="${row}"]`).value = `${symbol} ${rowTotal.toFixed(2)}`;
            calculateAccommodationGrandTotal();
        }
        
        function calculateAccommodationGrandTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.accom-total').forEach(input => {
                const value = input.value.replace(/[^0-9.-]+/g, '');
                total += parseFloat(value) || 0;
            });
            document.getElementById('accommodation_total').value = `${symbol} ${total.toFixed(2)}`;
            calculateSummary();
        }
        
        // Add event listeners for accommodation calculation
        document.querySelectorAll('.accom-rooms-no, .accom-rooms-rate, .accom-extra-adult-no, .accom-extra-adult-rate, .accom-extra-child-no, .accom-extra-child-rate, .accom-child-no-bed-no, .accom-child-no-bed-rate, .accom-nights').forEach(input => {
            input.addEventListener('input', function() {
                calculateAccommodationTotal(this.getAttribute('data-row'));
            });
        });
        
        // Initialize calculations
        document.querySelectorAll('.accom-total').forEach(input => {
            calculateAccommodationTotal(input.getAttribute('data-row'));
        });
        
        // Calculate cruise hire totals
        function calculateCruiseTotal(row) {
            const symbol = getCurrencySymbol();
            const rate = parseFloat(document.querySelector(`.cruise-rate[data-row="${row}"]`).value) || 0;
            const extra = parseFloat(document.querySelector(`.cruise-extra[data-row="${row}"]`).value) || 0;
            const total = rate + extra;
            
            document.querySelector(`.cruise-total[data-row="${row}"]`).value = `${symbol} ${total.toFixed(2)}`;
            calculateCruiseGrandTotal();
        }
        
        function calculateCruiseGrandTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.cruise-total').forEach(input => {
                const value = input.value.replace(/[^0-9.-]+/g, '');
                total += parseFloat(value) || 0;
            });
            document.getElementById('cruise_hire_total').value = `${symbol} ${total.toFixed(2)}`;
            calculateSummary();
        }
        
        // Add event listeners for cruise hire calculation
        document.querySelectorAll('.cruise-rate, .cruise-extra').forEach(input => {
            input.addEventListener('input', function() {
                calculateCruiseTotal(this.getAttribute('data-row'));
            });
        });
        
        // Initialize cruise calculations
        document.querySelectorAll('.cruise-total').forEach(input => {
            calculateCruiseTotal(input.getAttribute('data-row'));
        });
        
        // Calculate transportation totals
        function calculateTransportationTotal(row) {
            const symbol = getCurrencySymbol();
            const dailyRent = parseFloat(document.querySelector(`.trans-daily-rent[data-row="${row}"]`).value) || 0;
            const days = parseFloat(document.querySelector(`.trans-days[data-row="${row}"]`).value) || 0;
            const km = parseFloat(document.querySelector(`.trans-km[data-row="${row}"]`).value) || 0;
            const extraKm = parseFloat(document.querySelector(`.trans-extra-km[data-row="${row}"]`).value) || 0;
            const pricePerKm = parseFloat(document.querySelector(`.trans-price-per-km[data-row="${row}"]`).value) || 0;
            const toll = parseFloat(document.querySelector(`.trans-toll[data-row="${row}"]`).value) || 0;
            
            const total = (dailyRent * days) + ((km + extraKm) * pricePerKm) + toll;
            
            document.querySelector(`.trans-total[data-row="${row}"]`).value = `${symbol} ${total.toFixed(2)}`;
            calculateTransportationGrandTotal();
        }
        
        function calculateTransportationGrandTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.trans-total').forEach(input => {
                const value = input.value.replace(/[^0-9.-]+/g, '');
                total += parseFloat(value) || 0;
            });
            document.getElementById('transportation_total').value = `${symbol} ${total.toFixed(2)}`;
            calculateSummary();
        }
        
        // Add event listeners for transportation calculation
        document.querySelectorAll('.trans-daily-rent, .trans-days, .trans-km, .trans-extra-km, .trans-price-per-km, .trans-toll').forEach(input => {
            input.addEventListener('input', function() {
                calculateTransportationTotal(this.getAttribute('data-row'));
            });
        });
        
        // Initialize transportation calculations
        document.querySelectorAll('.trans-total').forEach(input => {
            calculateTransportationTotal(input.getAttribute('data-row'));
        });
        
        // Calculate extras totals
        function calculateExtrasTotal(row) {
            const symbol = getCurrencySymbol();
            const amount = parseFloat(document.querySelector(`.extras-amount[data-row="${row}"]`).value) || 0;
            const extras = parseFloat(document.querySelector(`.extras-extra[data-row="${row}"]`).value) || 0;
            const total = amount + extras;
            
            document.querySelector(`.extras-total[data-row="${row}"]`).value = `${symbol} ${total.toFixed(2)}`;
            calculateExtrasGrandTotal();
        }
        
        function calculateExtrasGrandTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.extras-total').forEach(input => {
                const value = input.value.replace(/[^0-9.-]+/g, '');
                total += parseFloat(value) || 0;
            });
            document.getElementById('extras_total').value = `${symbol} ${total.toFixed(2)}`;
            calculateSummary();
        }
        
        // Add event listeners for extras calculation
        document.querySelectorAll('.extras-amount, .extras-extra').forEach(input => {
            input.addEventListener('input', function() {
                calculateExtrasTotal(this.getAttribute('data-row'));
            });
        });
        
        // Initialize extras calculations
        document.querySelectorAll('.extras-total').forEach(input => {
            calculateExtrasTotal(input.getAttribute('data-row'));
        });
        
        // Calculate payment totals
        function calculatePaymentTotal() {
            const symbol = getCurrencySymbol();
            let total = 0;
            document.querySelectorAll('.payment-amount').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('payment_total').value = `${symbol} ${total.toFixed(2)}`;
            
            // Calculate balance
            const finalAmountStr = document.getElementById('final_amount').value || '0';
            const finalAmount = parseFloat(finalAmountStr.replace(/[^0-9.-]+/g, '')) || 0;
            const balance = finalAmount - total;
            document.getElementById('payment_balance').value = `${symbol} ${balance.toFixed(2)}`;
        }
        
        // Add row to payment table
        let paymentRowCount = 1;
        document.querySelectorAll('.add-payment-row').forEach(button => {
            button.addEventListener('click', function() {
                const tbody = document.getElementById('payment-tbody');
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="date" class="form-control form-control-sm payment-date" name="payment[${paymentRowCount}][date]"></td>
                    <td>
                        <select class="form-control form-control-sm" name="payment[${paymentRowCount}][bank]">
                            <option value="">Select Bank</option>
                            <option value="HDFC BANK">HDFC BANK</option>
                            <option value="ICICI BANK">ICICI BANK</option>
                        </select>
                    </td>
                    <td><input type="number" class="form-control form-control-sm payment-amount" name="payment[${paymentRowCount}][amount]" data-row="${paymentRowCount}" value="0"></td>
                    <td><input type="file" class="form-control form-control-sm" name="payment_receipt_${paymentRowCount}" accept="image/*"></td>
                    <td><button type="button" class="btn btn-sm btn-info add-payment-row"><i class="fa fa-plus"></i></button></td>
                `;
                
                tbody.appendChild(newRow);
                
                // Add event listeners to the new row
                const newAmountInput = newRow.querySelector('.payment-amount');
                newAmountInput.addEventListener('input', calculatePaymentTotal);
                
                // Add event listener to the new add button
                newRow.querySelector('.add-payment-row').addEventListener('click', function() {
                    const nextRowCount = paymentRowCount + 1;
                    const nextRow = document.createElement('tr');
                    nextRow.innerHTML = `
                        <td><input type="date" class="form-control form-control-sm payment-date" name="payment[${nextRowCount}][date]"></td>
                        <td>
                            <select class="form-control form-control-sm" name="payment[${nextRowCount}][bank]">
                                <option value="">Select Bank</option>
                                <option value="HDFC BANK">HDFC BANK</option>
                                <option value="ICICI BANK">ICICI BANK</option>
                            </select>
                        </td>
                        <td><input type="number" class="form-control form-control-sm payment-amount" name="payment[${nextRowCount}][amount]" data-row="${nextRowCount}" value="0"></td>
                        <td><input type="file" class="form-control form-control-sm" name="payment_receipt_${nextRowCount}" accept="image/*"></td>
                        <td><button type="button" class="btn btn-sm btn-info add-payment-row"><i class="fa fa-plus"></i></button></td>
                    `;
                    
                    tbody.appendChild(nextRow);
                    
                    const nextAmountInput = nextRow.querySelector('.payment-amount');
                    nextAmountInput.addEventListener('input', calculatePaymentTotal);
                    
                    nextRow.querySelector('.add-payment-row').addEventListener('click', arguments.callee);
                    
                    calculatePaymentTotal();
                    paymentRowCount = nextRowCount + 1;
                });
                
                calculatePaymentTotal();
                paymentRowCount++;
            });
        });
        
        // Add event listeners for payment calculation
        document.querySelectorAll('.payment-amount').forEach(input => {
            input.addEventListener('input', calculatePaymentTotal);
        });
        
        // Initialize currency symbols and summary calculations
        updateCurrencySymbols();
        calculateSummary();
        calculatePaymentTotal();
        
        // Print functionality
        document.getElementById('print-cost-sheet').addEventListener('click', function() {
            window.print();
        });
        
        // Get currency symbol
        function getCurrencySymbol() {
            const currencySelect = document.getElementById('currency-select');
            const selectedOption = currencySelect.options[currencySelect.selectedIndex];
            return selectedOption.getAttribute('data-symbol') || '';
        }
        
        // Update all currency displays
        function updateCurrencySymbols() {
            const symbol = getCurrencySymbol();
            
            // Update all numeric display fields with the currency symbol
            document.querySelectorAll('.subtotal, .cruise-total, .trans-total, .extras-total, .accom-total, #visa_flight_total, #visa_flight_grand_total, #transportation_total, #cruise_hire_total, #extras_total, #accommodation_total, #total_expense, #tax_amount, #final_amount').forEach(input => {
                if (input.value) {
                    const numericValue = parseFloat(input.value) || 0;
                    input.value = `${symbol} ${numericValue.toFixed(2)}`;
                }
            });
        }
        
        // Calculate summary and profit
        function calculateSummary() {
            const symbol = getCurrencySymbol();
            
            // Get all section totals (strip currency symbols for calculation)
            const visaFlightTotalStr = document.getElementById('visa_flight_total')?.value || '0';
            const transportationTotalStr = document.getElementById('transportation_total')?.value || '0';
            const cruiseHireTotalStr = document.getElementById('cruise_hire_total')?.value || '0';
            const extrasTotalStr = document.getElementById('extras_total')?.value || '0';
            const accommodationTotalStr = document.getElementById('accommodation_total')?.value || '0';
            
            // Extract numeric values
            const visaFlightTotal = parseFloat(visaFlightTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
            const transportationTotal = parseFloat(transportationTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
            const cruiseHireTotal = parseFloat(cruiseHireTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
            const extrasTotal = parseFloat(extrasTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
            const accommodationTotal = parseFloat(accommodationTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
            
            // Calculate total expense
            const totalExpense = visaFlightTotal + transportationTotal + cruiseHireTotal + extrasTotal + accommodationTotal;
            document.getElementById('total_expense').value = `${symbol} ${totalExpense.toFixed(2)}`;
            
            // Calculate markup percentage and amount
            const packageCostStr = document.getElementById('package_cost').value || '0';
            const packageCost = parseFloat(packageCostStr.replace(/[^0-9.-]+/g, '')) || 0;
            let markupPercentage = 0;
            let markupAmount = 0;
            if (totalExpense > 0 && packageCost > 0) {
                markupPercentage = ((packageCost - totalExpense) / totalExpense) * 100;
                markupAmount = packageCost - totalExpense;
            }
            document.getElementById('profit_percentage_label').textContent = markupPercentage.toFixed(2);
            document.getElementById('markup_amount').value = `${symbol} ${markupAmount.toFixed(2)}`;
            
            // Calculate tax
            const taxPercentage = parseFloat(document.getElementById('tax_percentage').value || 0);
            const taxAmount = (packageCost * taxPercentage) / 100;
            document.getElementById('tax_amount').value = `${symbol} ${taxAmount.toFixed(2)}`;
            
            // Get currency and conversion rate
            const currencySelect = document.getElementById('currency-select');
            const selectedCurrency = currencySelect.options[currencySelect.selectedIndex].value;
            document.getElementById('currency_display').value = selectedCurrency;
            
            const conversionRate = parseFloat(document.getElementById('currency_rate').value || 1);
            
            // Calculate final amount
            const finalAmount = (packageCost + taxAmount) * conversionRate;
            document.getElementById('final_amount').value = `${symbol} ${finalAmount.toFixed(2)}`;
        }
        
        // Add event listeners for summary calculation
        document.getElementById('package_cost').addEventListener('input', calculateSummary);
        document.getElementById('tax_percentage').addEventListener('change', calculateSummary);
        document.getElementById('currency_rate').addEventListener('input', calculateSummary);
        document.getElementById('currency-select').addEventListener('change', function() {
            updateCurrencySymbols();
            calculateSummary();
            calculatePaymentTotal();
        });
        
        // Add row to extras table
        let extrasRowCount = 1;
        document.querySelectorAll('.add-extras-row').forEach(button => {
            button.addEventListener('click', function() {
                const tbody = document.getElementById('extras-tbody');
                const totalRow = tbody.rows[tbody.rows.length - 2]; // Get the total row
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" class="form-control form-control-sm" name="extras[${extrasRowCount}][supplier]"></td>
                    <td><input type="text" class="form-control form-control-sm" name="extras[${extrasRowCount}][service_type]"></td>
                    <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[${extrasRowCount}][amount]" data-row="${extrasRowCount}" value="0"></td>
                    <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[${extrasRowCount}][extras]" data-row="${extrasRowCount}" value="0"></td>
                    <td><input type="text" class="form-control form-control-sm extras-total" name="extras[${extrasRowCount}][total]" data-row="${extrasRowCount}" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-info add-extras-row"><i class="fa fa-plus"></i></button></td>
                `;
                
                // Insert before the total row
                tbody.insertBefore(newRow, totalRow);
                
                // Add event listeners to the new row
                const newInputs = newRow.querySelectorAll('.extras-amount, .extras-extra');
                newInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        calculateExtrasTotal(this.getAttribute('data-row'));
                    });
                });
                
                // Add event listener to the new add button
                newRow.querySelector('.add-extras-row').addEventListener('click', function() {
                    const tbody = document.getElementById('extras-tbody');
                    const totalRow = tbody.rows[tbody.rows.length - 2];
                    const nextRowCount = extrasRowCount + 1;
                    const nextRow = document.createElement('tr');
                    nextRow.innerHTML = `
                        <td><input type="text" class="form-control form-control-sm" name="extras[${nextRowCount}][supplier]"></td>
                        <td><input type="text" class="form-control form-control-sm" name="extras[${nextRowCount}][service_type]"></td>
                        <td><input type="number" class="form-control form-control-sm extras-amount" name="extras[${nextRowCount}][amount]" data-row="${nextRowCount}" value="0"></td>
                        <td><input type="number" class="form-control form-control-sm extras-extra" name="extras[${nextRowCount}][extras]" data-row="${nextRowCount}" value="0"></td>
                        <td><input type="text" class="form-control form-control-sm extras-total" name="extras[${nextRowCount}][total]" data-row="${nextRowCount}" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-info add-extras-row"><i class="fa fa-plus"></i></button></td>
                    `;
                    
                    tbody.insertBefore(nextRow, totalRow);
                    
                    const nextInputs = nextRow.querySelectorAll('.extras-amount, .extras-extra');
                    nextInputs.forEach(input => {
                        input.addEventListener('input', function() {
                            calculateExtrasTotal(this.getAttribute('data-row'));
                        });
                    });
                    
                    nextRow.querySelector('.add-extras-row').addEventListener('click', arguments.callee);
                    
                    calculateExtrasTotal(nextRowCount);
                    extrasRowCount = nextRowCount + 1;
                });
                
                calculateExtrasTotal(extrasRowCount);
                extrasRowCount++;
            });
        });
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>