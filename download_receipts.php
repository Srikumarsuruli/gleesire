<?php
require_once "config/database.php";

if(!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. Cost file ID is required.");
}

$cost_file_id = intval($_GET['id']);

// Get payment details
$payment_sql = "SELECT * FROM payments WHERE cost_file_id = ? ORDER BY payment_date ASC";
$payment_stmt = mysqli_prepare($conn, $payment_sql);
mysqli_stmt_bind_param($payment_stmt, "i", $cost_file_id);
mysqli_stmt_execute($payment_stmt);
$payment_result = mysqli_stmt_get_result($payment_stmt);

$payments = [];
while($payment = mysqli_fetch_assoc($payment_result)) {
    $payments[] = $payment;
}

if(empty($payments)) {
    die("No payment records found for this cost file.");
}

// Get cost file details for header info
$cost_sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email 
            FROM tour_costings tc 
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
            WHERE tc.id = ?";
$cost_stmt = mysqli_prepare($conn, $cost_sql);
mysqli_stmt_bind_param($cost_stmt, "i", $cost_file_id);
mysqli_stmt_execute($cost_stmt);
$cost_result = mysqli_stmt_get_result($cost_stmt);
$cost_sheet = mysqli_fetch_assoc($cost_result);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipts - <?php echo htmlspecialchars($cost_sheet['cost_sheet_number'] ?? 'N/A'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .receipt { page-break-after: always; margin-bottom: 40px; border: 1px solid #ddd; padding: 20px; }
        .receipt:last-child { page-break-after: auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .receipt-title { font-size: 18px; margin: 10px 0; color: #e74c3c; }
        .receipt-number { font-size: 14px; color: #666; }
        .customer-info { margin: 20px 0; }
        .info-table { width: 100%; margin: 15px 0; }
        .info-table td { padding: 8px 0; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 150px; }
        .payment-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .amount-box { text-align: center; background: #e8f5e8; border: 2px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
        .receipt-image { text-align: center; margin: 20px 0; }
        .receipt-image img { max-width: 300px; max-height: 400px; border: 1px solid #ddd; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        @media print {
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
            .receipt { border: none; margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <?php foreach($payments as $index => $payment): ?>
    <div class="receipt">
        <div class="header">
            <div class="company-name">GLEESIRE TRAVELS</div>
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div class="receipt-number">Receipt #: <?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>

        <div class="customer-info">
            <table class="info-table">
                <tr>
                    <td class="label">Customer Name:</td>
                    <td><?php echo htmlspecialchars($cost_sheet['guest_name'] ?? $cost_sheet['customer_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Mobile Number:</td>
                    <td><?php echo htmlspecialchars($cost_sheet['mobile_number'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td><?php echo htmlspecialchars($cost_sheet['email'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Cost Sheet No:</td>
                    <td><?php echo htmlspecialchars($cost_sheet['cost_sheet_number'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>

        <div class="payment-details">
            <table class="info-table">
                <tr>
                    <td class="label">Payment Date:</td>
                    <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Payment Bank:</td>
                    <td><?php echo htmlspecialchars($payment['payment_bank'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Payment Method:</td>
                    <td>Bank Transfer</td>
                </tr>
                <!-- <tr>
                    <td class="label">Transaction ID:</td>
                    <td><?php echo 'TXN' . str_pad($payment['id'], 8, '0', STR_PAD_LEFT); ?></td>
                </tr> -->
            </table>
        </div>

        <div class="amount-box">
            <div style="font-size: 16px; margin-bottom: 10px;">Amount Received</div>
            <div class="amount"><?php echo htmlspecialchars($cost_sheet['currency'] ?? 'USD') . ' ' . number_format($payment['payment_amount'], 2); ?></div>
        </div>

        <?php if(!empty($payment['payment_receipt']) && file_exists($payment['payment_receipt'])): ?>
        <div class="receipt-image">
            <div style="font-weight: bold; margin-bottom: 10px;">Payment Receipt Image:</div>
            <img src="<?php echo htmlspecialchars($payment['payment_receipt']); ?>" alt="Payment Receipt">
        </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
            <p>For any queries, please contact us at info@gleesiretravels.com</p>
        </div>
    </div>
    <?php endforeach; ?>

</body>
</html>