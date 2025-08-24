<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_payment_receipts') && $_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Check if payments table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
$payments_table_exists = (mysqli_num_rows($table_exists) > 0);

// Check if viewing a specific cost sheet
$view_cost_sheet = null;
$payment_details = null;
$cost_sheet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($payments_table_exists) {
    if($cost_sheet_id > 0) {
        // Get the specific cost sheet
        $view_sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email,
                    SUBSTRING_INDEX(tc.cost_sheet_number, '-S', 1) as base_number,
                    SUBSTRING_INDEX(tc.cost_sheet_number, '-S', -1) as version_number
                    FROM tour_costings tc 
                    LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
                    WHERE tc.id = ?";
        $view_stmt = mysqli_prepare($conn, $view_sql);
        mysqli_stmt_bind_param($view_stmt, "i", $cost_sheet_id);
        mysqli_stmt_execute($view_stmt);
        $view_result = mysqli_stmt_get_result($view_stmt);
        
        if($view_row = mysqli_fetch_assoc($view_result)) {
            $view_cost_sheet = $view_row;
            
            // Get payment details for this cost sheet
            $payment_sql = "SELECT * FROM payments WHERE cost_file_id = ? ORDER BY payment_date DESC";
            $payment_stmt = mysqli_prepare($conn, $payment_sql);
            mysqli_stmt_bind_param($payment_stmt, "i", $cost_sheet_id);
            mysqli_stmt_execute($payment_stmt);
            $payment_result = mysqli_stmt_get_result($payment_stmt);
            
            if(mysqli_num_rows($payment_result) > 0) {
                $payment_details = mysqli_fetch_assoc($payment_result);
            } else {
                // Try to get payment data from the cost sheet's payment_data JSON
                $payment_data = json_decode($view_cost_sheet['payment_data'] ?? '{}', true);
                if(!empty($payment_data)) {
                    // Debug payment data
                    error_log("Payment data for cost sheet {$view_cost_sheet['cost_sheet_number']}: " . print_r($payment_data, true));
                    
                    $payment_details = [
                        'payment_date' => $payment_data['date'] ?? '',
                        'payment_bank' => $payment_data['bank'] ?? '',
                        'payment_amount' => $payment_data['amount'] ?? 0,
                        'payment_receipt' => $payment_data['receipt'] ?? null,
                        'total_received' => $payment_data['total_received'] ?? $payment_data['amount'] ?? 0,
                        'balance_amount' => $payment_data['balance_amount'] ?? 0
                    ];
                    
                    // If payment data exists but some fields are empty, try to calculate them
                    if (empty($payment_details['total_received']) && !empty($payment_details['payment_amount'])) {
                        $payment_details['total_received'] = $payment_details['payment_amount'];
                    }
                    
                    if (empty($payment_details['balance_amount'])) {
                        $payment_details['balance_amount'] = ($view_cost_sheet['package_cost'] ?? 0) - $payment_details['total_received'];
                    }
                }
            }
        }
    }
    
    // Get all payment receipts for the list view - combine both sources
    $all_payments = [];
    
    // First, get payments from the payments table
    $sql = "SELECT p.*, tc.cost_sheet_number, tc.package_cost, e.customer_name, e.mobile_number, e.email,
            SUBSTRING_INDEX(tc.cost_sheet_number, '-S', 1) as base_number,
            SUBSTRING_INDEX(tc.cost_sheet_number, '-S', -1) as version_number,
            'payments_table' as source
            FROM payments p
            LEFT JOIN tour_costings tc ON p.cost_file_id = tc.id
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id
            ORDER BY base_number DESC, version_number DESC, p.payment_date DESC";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $all_payments[] = $row;
        }
    }
    
    // Then, get payments from tour_costings payment_data
    $sql = "SELECT tc.id as cost_file_id, tc.cost_sheet_number, tc.package_cost, tc.payment_data, 
            e.customer_name, e.mobile_number, e.email,
            SUBSTRING_INDEX(tc.cost_sheet_number, '-S', 1) as base_number,
            SUBSTRING_INDEX(tc.cost_sheet_number, '-S', -1) as version_number,
            'json_data' as source
            FROM tour_costings tc
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id
            WHERE tc.payment_data IS NOT NULL AND tc.payment_data != '{}'
            ORDER BY base_number DESC, version_number DESC";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $all_payments[] = $row;
        }
    }
    
    // Set the result for the template
    $result = !empty($all_payments);
}
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4"><?php echo $view_cost_sheet ? 'Payment Details' : 'Payment Receipts'; ?></h4>
        </div>
    </div>
    
    <?php if(!$view_cost_sheet): ?>
    <!-- Search Form -->
    <div class="mb-20">
        <form action="" method="GET" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" class="form-control" name="search_name" placeholder="Search by customer name" value="<?php echo htmlspecialchars($_GET['search_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" class="form-control" name="search_number" placeholder="Search by cost sheet number" value="<?php echo htmlspecialchars($_GET['search_number'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                <?php if(!empty($_GET['search_name']) || !empty($_GET['search_number'])): ?>
                    <a href="view_payment_receipts.php" class="btn btn-secondary"><i class="fa fa-times"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if(!$payments_table_exists): ?>
        <div class="alert alert-warning">
            <h5><i class="icon fa fa-exclamation-triangle"></i> Payments Table Not Found</h5>
            <p>The payments table does not exist in the database. Please run the <a href="create_payments_table.php">database setup script</a> to create it.</p>
        </div>
    <?php elseif($view_cost_sheet): ?>
        <!-- Display detailed view of a specific cost sheet's payment details -->
        <div class="cost-sheet-details">
            <div class="cost-sheet-header">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Payment Details for Cost Sheet: <?php echo htmlspecialchars($view_cost_sheet['cost_sheet_number']); ?></h5>
                        <p class="mb-0"><?php echo htmlspecialchars($view_cost_sheet['guest_name'] ?? $view_cost_sheet['customer_name']); ?> | Created: <?php echo date('d-m-Y H:i', strtotime($view_cost_sheet['created_at'])); ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="view_payment_receipts.php" class="btn btn-sm btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
                        <a href="view_cost_sheets.php?action=view&id=<?php echo $view_cost_sheet['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-file"></i> View Cost Sheet</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="cost-sheet-section">
                        <h6><i class="fa fa-credit-card"></i> Payment Information</h6>
                        <?php if($payment_details): ?>
                            <div class="info-row">
                                <div class="info-label">Date:</div>
                                <div class="info-value"><?php echo !empty($payment_details['payment_date']) ? date('d-m-Y', strtotime($payment_details['payment_date'])) : 'N/A'; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Bank:</div>
                                <div class="info-value"><?php echo htmlspecialchars($payment_details['payment_bank'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Amount:</div>
                                <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['currency'] ?? 'USD'); ?> <?php echo number_format($payment_details['payment_amount'] ?? 0, 2); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Receipt:</div>
                                <div class="info-value">
                                    <?php if(!empty($payment_details['payment_receipt'])): ?>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openReceiptModal('view_receipt.php?path=<?php echo urlencode($payment_details['payment_receipt']); ?>&id=<?php echo $cost_sheet_id; ?>')">
                                            <i class="fa fa-eye"></i> View Receipt
                                        </button>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Receipt Uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Total Received:</div>
                                <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['currency'] ?? 'USD'); ?> <?php echo number_format($payment_details['total_received'] ?? $payment_details['payment_amount'] ?? 0, 2); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Balance Amount:</div>
                                <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['currency'] ?? 'USD'); ?> <?php 
                                    $balance = ($view_cost_sheet['package_cost'] ?? 0) - ($payment_details['total_received'] ?? $payment_details['payment_amount'] ?? 0);
                                    echo number_format($balance, 2); 
                                ?></div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No payment details found for this cost sheet.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="cost-sheet-section">
                        <h6><i class="fa fa-calculator"></i> Cost Summary</h6>
                        <table class="cost-summary-table">
                            <tr>
                                <td>Total Expense:</td>
                                <td><?php echo htmlspecialchars($view_cost_sheet['currency']) . ' ' . number_format($view_cost_sheet['total_expense'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Mark Up (<?php echo number_format($view_cost_sheet['markup_percentage'], 2); ?>%):</td>
                                <td><?php echo htmlspecialchars($view_cost_sheet['currency']) . ' ' . number_format($view_cost_sheet['markup_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Service Tax (<?php echo number_format($view_cost_sheet['tax_percentage'], 2); ?>%):</td>
                                <td><?php echo htmlspecialchars($view_cost_sheet['currency']) . ' ' . number_format($view_cost_sheet['tax_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Package Cost:</td>
                                <td><?php echo htmlspecialchars($view_cost_sheet['currency']) . ' ' . number_format($view_cost_sheet['package_cost'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Display list of all payment receipts -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Cost Sheet #</th>
                        <th>Customer Name</th>
                        <th>Payment Date</th>
                        <th>Bank</th>
                        <th>Amount</th>
                        <th>Package Cost</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && !empty($all_payments)): ?>
                        <?php foreach($all_payments as $row): 
                            // Extract payment data from JSON if it exists
                            $payment_data = [];
                            if ($row['source'] == 'json_data' && isset($row['payment_data'])) {
                                $payment_data = json_decode($row['payment_data'], true) ?: [];
                                $payment_date = $payment_data['date'] ?? '';
                                $payment_bank = $payment_data['bank'] ?? '';
                                $payment_amount = $payment_data['amount'] ?? 0;
                                $payment_receipt = $payment_data['receipt'] ?? null;
                            } else {
                                $payment_date = $row['payment_date'] ?? '';
                                $payment_bank = $row['payment_bank'] ?? '';
                                $payment_amount = $row['payment_amount'] ?? 0;
                                $payment_receipt = $row['payment_receipt'] ?? null;
                            }
                            
                            // Skip if no payment data
                            if (empty($payment_date) || empty($payment_bank) || empty($payment_amount)) {
                                continue;
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['cost_sheet_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo !empty($payment_date) ? date('d-m-Y', strtotime($payment_date)) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($payment_bank); ?></td>
                                <td><?php echo number_format($payment_amount, 2); ?></td>
                                <td><?php echo number_format($row['package_cost'], 2); ?></td>
                                <td><?php echo number_format($row['package_cost'] - $payment_amount, 2); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-link font-18 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                            <i class="dw dw-more"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                            <a class="dropdown-item" href="view_payment_receipts.php?id=<?php echo $row['cost_file_id']; ?>"><i class="dw dw-eye"></i> View Details</a>
                                            <?php if(!empty($payment_receipt)): ?>
                                                <a class="dropdown-item" href="#" onclick="openReceiptModal('view_receipt.php?path=<?php echo urlencode($payment_receipt); ?>&id=<?php echo $row['cost_file_id']; ?>')"><i class="dw dw-file"></i> View Receipt</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No payment receipts found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Payment Receipt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="receiptFrame" src="" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openReceiptModal(url) {
    document.getElementById('receiptFrame').src = url;
    $('#receiptModal').modal('show');
}

// Clear iframe when modal is closed
$('#receiptModal').on('hidden.bs.modal', function () {
    document.getElementById('receiptFrame').src = '';
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>