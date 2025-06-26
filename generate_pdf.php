<?php
// Include database connection
require_once "config/database.php";
require_once "includes/header.php";

// Get cost sheet ID from URL
$cost_sheet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($cost_sheet_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid cost sheet ID.</div>";
    echo "<a href='view_cost_sheets.php' class='btn btn-primary'>Back to Cost Sheets</a>";
    exit;
}

// Get cost sheet details
$sql = "SELECT cs.*, u.full_name as created_by_name 
        FROM cost_sheets cs 
        LEFT JOIN users u ON cs.created_by = u.id 
        WHERE cs.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $cost_sheet_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Cost sheet not found.</div>";
    echo "<a href='view_cost_sheets.php' class='btn btn-primary'>Back to Cost Sheets</a>";
    exit;
}

$cost_sheet = mysqli_fetch_assoc($result);
$services_data = json_decode($cost_sheet['services_data'], true);
$payment_data = json_decode($cost_sheet['payment_data'], true);

// Generate HTML version instead of PDF
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost Sheet - <?php echo htmlspecialchars($cost_sheet['enquiry_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #0066cc;
        }
        .header p {
            font-size: 14px;
            margin: 5px 0;
        }
        .section-title {
            background-color: #f2f2f2;
            padding: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
            margin: 20px 0 10px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .print-button, .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>COST SHEET</h1>
            <p>Cost Sheet No: <?php echo htmlspecialchars($cost_sheet['cost_sheet_number'] ?? 'N/A'); ?></p>
            <p>Reference: <?php echo htmlspecialchars($cost_sheet['enquiry_number']); ?></p>
            <p>Date: <?php echo date('d-m-Y', strtotime($cost_sheet['created_at'])); ?></p>
        </div>
        
        <table>
            <tr>
                <th width="25%">GUEST NAME</th>
                <td width="25%"><?php echo htmlspecialchars($cost_sheet['customer_name']); ?></td>
                <th width="25%">ENQUIRY NUMBER</th>
                <td width="25%"><?php echo htmlspecialchars($cost_sheet['enquiry_number']); ?></td>
            </tr>
            <tr>
                <th>CURRENCY</th>
                <td><?php echo htmlspecialchars($cost_sheet['currency']); ?></td>
                <th>CREATED BY</th>
                <td><?php echo htmlspecialchars($cost_sheet['created_by_name'] ?? 'N/A'); ?></td>
            </tr>
        </table>
        
        <div class="section-title">SUMMARY</div>
        <table>
            <tr>
                <th width="25%">TOTAL EXPENSE</th>
                <td width="25%"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['total_expense'], 2); ?></td>
                <th width="25%">PACKAGE COST</th>
                <td width="25%"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['package_cost'], 2); ?></td>
            </tr>
            <tr>
                <th>MARK UP (PROFIT) %</th>
                <td><?php echo number_format($cost_sheet['markup_percentage'], 2) . '%'; ?></td>
                <th>TAX</th>
                <td><?php echo number_format($cost_sheet['tax_percentage'], 2) . '% (' . htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['tax_amount'], 2) . ')'; ?></td>
            </tr>
            <tr>
                <th>FINAL AMOUNT</th>
                <td colspan="3"><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['final_amount'], 2); ?></td>
            </tr>
        </table>
        
        <?php if(!empty($payment_data)): ?>
        <div class="section-title">PAYMENT DETAILS</div>
        <table>
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>BANK</th>
                    <th>AMOUNT</th>
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
                </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="2">TOTAL RECEIVED</th>
                    <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($total_received, 2); ?></td>
                </tr>
                <tr>
                    <th colspan="2">BALANCE AMOUNT TO BE COLLECTED</th>
                    <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['final_amount'] - $total_received, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>For any queries, please contact us at: info@example.com | +1234567890</p>
        </div>
        
        <div class="print-button">
            <button onclick="window.print()">Print this page</button>
            <a href="view_cost_sheets.php" class="no-print">Back to Cost Sheets</a>
        </div>
    </div>
</body>
</html>
<?php
// No need to include footer since we're generating a complete HTML page
?>