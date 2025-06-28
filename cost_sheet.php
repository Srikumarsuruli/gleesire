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

<style>
    /* Modern Professional Cost Sheet Design */
    .costsheet-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .costsheet-section .card-box {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .costsheet-section .receipt-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .costsheet-section .receipt-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
        animation: float 20s infinite linear;
    }
    
    @keyframes float {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
    
    .costsheet-section .receipt-header h4 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1;
    }
    
    .costsheet-section .receipt-header p {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }
    
    .costsheet-section .table {
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        border: none;
        margin-bottom: 2rem;
    }
    
    .costsheet-section .table th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 1rem 0.8rem;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .costsheet-section .table td {
        padding: 0.8rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    
    .costsheet-section .table tbody tr:hover {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        transform: translateY(-1px);
        transition: all 0.3s ease;
    }
    
    .costsheet-section .form-control, .costsheet-section .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: #fff;
    }
    
    .costsheet-section .form-control:focus, .costsheet-section .form-select:focus {
        border-color: #4facfe;
        box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
        transform: translateY(-2px);
    }
    
    .costsheet-section .btn {
        border-radius: 10px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
    }
    
    .costsheet-section .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
    }
    
    .costsheet-section .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
    }
    
    .costsheet-section .btn-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .costsheet-section .btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
    }
    
    .costsheet-section .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        font-weight: 600;
        color: #495057 !important;
    }
    
    .costsheet-section .bg-light-blue {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
        border-radius: 15px;
        padding: 1.5rem !important;
    }
    
    .costsheet-section .service-section {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border: 2px solid #e3f2fd;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .costsheet-section .service-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    
    .costsheet-section .service-section h5, .costsheet-section .service-section h6 {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border-radius: 15px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
    }
    
    .costsheet-section .custom-control-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .costsheet-section .custom-control-input:checked ~ .custom-control-label {
        color: #4facfe;
        font-weight: 600;
    }
    
    .costsheet-section .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border-color: #4facfe;
    }
    
    .costsheet-section .alert {
        border-radius: 15px;
        border: none;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        font-weight: 500;
    }
    
    .costsheet-section .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
    }
    
    .costsheet-section .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
    }
    
    .costsheet-section .receipt-footer {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-top: 2rem;
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }
    
    .costsheet-section .section-divider {
        border: none;
        height: 2px;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        margin: 2rem 0;
        border-radius: 2px;
    }
    
    .costsheet-section .table-responsive {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    .costsheet-section .form-control[readonly] {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        border-color: #dee2e6;
    }
    
    /* Animation for form elements */
    .costsheet-section .form-control, .costsheet-section .btn, .costsheet-section .table {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .costsheet-section .receipt-header h4 {
            font-size: 2rem;
        }
        
        .costsheet-section .card-box {
            margin: 1rem;
            border-radius: 15px;
        }
        
        .costsheet-section .table th, .costsheet-section .table td {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
    }
    
    /* Services Card Styles */
    .costsheet-section .services-card {
        padding: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .costsheet-section .services-title {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 0.8rem 1rem;
        border-radius: 10px;
        text-align: center;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    }
    
    .costsheet-section .services-grid {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }
    
    .costsheet-section .service-item {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        padding: 0.6rem 0.8rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(79, 172, 254, 0.2);
    }
    
    .costsheet-section .service-item:hover {
        background: rgba(79, 172, 254, 0.1);
        transform: translateX(5px);
        border-color: #4facfe;
    }
    
    .costsheet-section .service-item .custom-control-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: #495057;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    
    .costsheet-section .service-item .custom-control-label i {
        color: #4facfe;
        width: 16px;
        text-align: center;
    }
    
    .costsheet-section .service-item .custom-control-input:checked ~ .custom-control-label {
        color: #4facfe;
        font-weight: 600;
    }
    
    .costsheet-section .service-item .custom-control-input:checked ~ .custom-control-label i {
        color: #00f2fe;
    }
    
    /* Customer Information Table Styles */
    .costsheet-section .customer-info-table {
        margin-bottom: 2rem;
    }
    
    .costsheet-section .customer-info-table .info-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        width: 20%;
        padding: 1rem 0.8rem;
    }
    
    .costsheet-section .customer-info-table .info-label i {
        color: #4facfe;
        width: 16px;
        text-align: center;
    }
    
    .costsheet-section .customer-info-table .info-value {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
        padding: 1rem 0.8rem;
        background: rgba(79, 172, 254, 0.05);
    }
    
    /* Print styles */
    @media print {
        .costsheet-section {
            background: white;
            padding: 0;
        }
        
        .costsheet-section .card-box {
            box-shadow: none;
            border-radius: 0;
        }
        
        .costsheet-section .receipt-header {
            background: #f8f9fa !important;
            color: #333 !important;
        }
        
        .costsheet-section .btn, .no-print {
            display: none !important;
        }
        
        .costsheet-section .services-card {
            background: #f8f9fa !important;
        }
        
        .costsheet-section .services-title {
            background: #dee2e6 !important;
            color: #333 !important;
        }
    }
</style>
<div class="costsheet-section">
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

                    <table class="table table-bordered customer-info-table">
                        <tr>
                            <td class="bg-light info-label"><i class="fas fa-user mr-2"></i><strong>GUEST NAME</strong></td>
                            <td class="info-value"><?php echo htmlspecialchars($enquiry['customer_name']); ?></td>
                            <td colspan="2" rowspan="3" class="align-middle bg-light-blue" width="30%">
                                <div class="services-card">
                                    <h6 class="services-title mb-3"><i class="fas fa-concierge-bell mr-2"></i>SERVICES</h6>
                                    <div class="services-grid">
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="visa-flight" name="services[]" value="visa_flight" <?php echo in_array('visa_flight', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="visa-flight">
                                                    <i class="fas fa-plane mr-2"></i>VISA / FLIGHT BOOKING
                                                </label>
                                            </div>
                                        </div>
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="accommodation" name="services[]" value="accommodation" <?php echo in_array('accommodation', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="accommodation">
                                                    <i class="fas fa-bed mr-2"></i>ACCOMMODATION
                                                </label>
                                            </div>
                                        </div>
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="cruise-hire" name="services[]" value="cruise_hire" <?php echo in_array('cruise_hire', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="cruise-hire">
                                                    <i class="fas fa-ship mr-2"></i>CRUISE HIRE
                                                </label>
                                            </div>
                                        </div>
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="transportation" name="services[]" value="transportation" <?php echo in_array('transportation', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="transportation">
                                                    <i class="fas fa-car mr-2"></i>TRANSPORTATION
                                                </label>
                                            </div>
                                        </div>
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="extras" name="services[]" value="extras" <?php echo in_array('extras', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="extras">
                                                    <i class="fas fa-plus-circle mr-2"></i>EXTRAS/MISCELLANEOUS
                                                </label>
                                            </div>
                                        </div>
                                        <div class="service-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input service-checkbox" id="travel-insurance" name="services[]" value="travel_insurance" <?php echo in_array('travel_insurance', $services) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="travel-insurance">
                                                    <i class="fas fa-shield-alt mr-2"></i>TRAVEL INSURANCE
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="bg-light info-label"><i class="fas fa-hashtag mr-2"></i><strong>ENQUIRY NUMBER</strong></td>
                            <td class="info-value"><?php echo htmlspecialchars($enquiry['enquiry_number']); ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light info-label"><i class="fas fa-file-alt mr-2"></i><strong>FILE NUMBER</strong></td>
                            <td class="info-value"><?php echo htmlspecialchars($enquiry['enquiry_number'] ?? 'Auto'); ?></td>
                            <td class="bg-light info-label"><i class="fas fa-calendar mr-2"></i><strong>ENQUIRY DATE</strong></td>
                            <td class="info-value"><?php echo $enquiry['enquiry_date'] ? date('d-m-Y', strtotime($enquiry['enquiry_date'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light info-label"><i class="fas fa-map-marker-alt mr-2"></i><strong>GUEST ADDRESS</strong></td>
                            <td><input type="text" class="form-control" name="guest_address" placeholder="Enter guest address (optional)"></td>
                            <td class="bg-light info-label"><i class="fas fa-calendar-check mr-2"></i><strong>BOOKING DATE</strong></td>
                            <td class="info-value"><?php echo $enquiry['booking_date'] ? date('d-m-Y', strtotime($enquiry['booking_date'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light info-label"><i class="fas fa-envelope mr-2"></i><strong>EMAIL ID</strong></td>
                            <td class="info-value"><?php echo htmlspecialchars($enquiry['email'] ?? 'N/A'); ?></td>
                            <td class="bg-light info-label"><i class="fas fa-user-tie mr-2"></i><strong>SOURCE / AGENT</strong></td>
                            <td><input type="text" class="form-control" name="source_agent" value="<?php echo htmlspecialchars($enquiry['source_name'] ?? ''); ?>" placeholder="Enter source or agent name"></td>
                        </tr>
                        <tr>
                            <td class="bg-light info-label"><i class="fas fa-flag mr-2"></i><strong>NATIONALITY</strong></td>
                            <td>
                                <select class="form-control" name="nationality">
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
   