<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Get cost sheet ID from URL
$cost_sheet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($cost_sheet_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid cost sheet ID.</div>";
    require_once "includes/footer.php";
    exit;
}

// Create cost_sheets table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS cost_sheets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    enquiry_number VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    total_expense DECIMAL(15,2) NOT NULL DEFAULT 0,
    package_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    markup_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_percentage DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    services_data LONGTEXT,
    payment_data LONGTEXT,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_table_sql)) {
    echo "<div class='alert alert-danger'>Error creating table: " . mysqli_error($conn) . "</div>";
}

// Get cost sheet details
$sql = "SELECT cs.*, e.customer_name, e.phone, e.email, u.full_name as created_by_name 
        FROM cost_sheets cs 
        LEFT JOIN enquiries e ON cs.enquiry_id = e.id 
        LEFT JOIN users u ON cs.created_by = u.id 
        WHERE cs.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $cost_sheet_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Cost sheet not found.</div>";
    require_once "includes/footer.php";
    exit;
}

$cost_sheet = mysqli_fetch_assoc($result);
$services_data = json_decode($cost_sheet['services_data'], true);
$payment_data = json_decode($cost_sheet['payment_data'], true);
?>

<div class="card-box mb-30">
    <div class="receipt-header">
        <h4 class="text-info font-weight-bold">COST SHEET</h4>
        <p class="mb-0">Reference: <?php echo htmlspecialchars($cost_sheet['enquiry_number']); ?></p>
        <p class="mb-0">Date: <?php echo date('d-m-Y', strtotime($cost_sheet['created_at'])); ?></p>
    </div>
    <div class="pb-10 pd-10">
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
                    .text-right {
                        text-align: right !important;
                    }
                    .text-left {
                        text-align: left !important;
                    }
                    .service-section {
                        margin-bottom: 0.5rem !important;
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
                </style>
                <table class="table table-bordered table-sm" style="table-layout: fixed; width: 100%;">
                    <tr>
                        <td class="bg-light" width="15%" style="font-size: 0.8rem;"><strong>GUEST NAME</strong></td>
                        <td class="font-weight-bold" width="20%"><?php echo htmlspecialchars($cost_sheet['customer_name']); ?></td>
                        <td colspan="2" rowspan="1" class="bg-light" width="30%" style="font-size: 0.8rem;"><strong>SERVICES</strong></td>
                        <td class="bg-light" width="15%" style="font-size: 0.8rem;"><strong>ENQUIRY NUMBER</strong></td>
                        <td class="font-weight-bold" width="20%"><?php echo htmlspecialchars($cost_sheet['enquiry_number']); ?></td>
                    </tr>
                    <tr>
                        <td class="bg-light" style="font-size: 0.8rem;"><strong>EMAIL ID</strong></td>
                        <td class="font-weight-bold"><?php echo htmlspecialchars($cost_sheet['email'] ?? 'N/A'); ?></td>
                        <td colspan="2" rowspan="1">
                            <?php if(!empty($services_data['services'])): ?>
                                <?php foreach($services_data['services'] as $service): ?>
                                    <div class="mb-1"><?php echo strtoupper(str_replace('_', ' ', $service)); ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td class="bg-light" style="font-size: 0.8rem;"><strong>CURRENCY</strong></td>
                        <td class="font-weight-bold"><?php echo htmlspecialchars($cost_sheet['currency']); ?></td>
                    </tr>
                    <tr>
                        <td class="bg-light" style="font-size: 0.8rem;"><strong>CONTACT NUMBER</strong></td>
                        <td class="font-weight-bold"><?php echo htmlspecialchars($cost_sheet['phone'] ?? 'N/A'); ?></td>
                        <td colspan="2" rowspan="1" class="bg-light" style="font-size: 0.8rem;"><strong>CREATED BY</strong></td>
                        <td colspan="2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($cost_sheet['created_by_name'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="section-divider"></div>
        
        <!-- Summary Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="bg-light p-2 text-center">SUMMARY</h5>
                <div class="table-responsive receipt-table">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td class="bg-light" width="25%"><strong>TOTAL EXPENSE</strong></td>
                                <td width="25%"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['total_expense'], 2); ?></td>
                                <td class="bg-light" width="25%"><strong>PACKAGE COST</strong></td>
                                <td width="25%"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['package_cost'], 2); ?></td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>MARK UP (PROFIT) %</strong></td>
                                <td><?php echo number_format($cost_sheet['markup_percentage'], 2) . '%'; ?></td>
                                <td class="bg-light"><strong>TAX</strong></td>
                                <td><?php echo number_format($cost_sheet['tax_percentage'], 2) . '% (' . htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['tax_amount'], 2) . ')'; ?></td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>FINAL AMOUNT</strong></td>
                                <td colspan="3"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['final_amount'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Payment Details Section -->
        <?php if(!empty($payment_data)): ?>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_received = 0;
                            foreach($payment_data as $payment): 
                                $total_received += floatval($payment['amount'] ?? 0);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['bank'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format(floatval($payment['amount'] ?? 0), 2); ?></td>
                                    <td>
                                        <?php if(!empty($payment['receipt'])): ?>
                                            <a href="<?php echo htmlspecialchars($payment['receipt']); ?>" target="_blank">View Receipt</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2" class="bg-light"><strong>TOTAL RECEIVED</strong></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($total_received, 2); ?></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="bg-light"><strong>BALANCE AMOUNT TO BE COLLECTED</strong></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['final_amount'] - $total_received, 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-2">
            <div class="col-md-12 text-center">
                <a href="view_cost_sheets.php" class="btn btn-secondary btn-sm">Back to Cost Sheets</a>
                <a href="cost_sheet.php?id=<?php echo $cost_sheet['enquiry_id']; ?>" class="btn btn-info btn-sm">Edit</a>
                <a href="generate_pdf.php?id=<?php echo $cost_sheet_id; ?>" class="btn btn-success btn-sm">Download PDF</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
            </div>
        </div>
        
        <div class="receipt-footer mt-3">
            <p class="mb-0">Thank you for your business!</p>
            <p class="mb-0">For any queries, please contact us at: info@example.com | +1234567890</p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>