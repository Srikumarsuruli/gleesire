<?php
// This is a simple PDF template file for debugging purposes
// It will be included from view_cost_sheets.php when the regular template fails

// Get cost sheet data from the parent scope
$cost_sheet_number = $cost_sheet['cost_sheet_number'] ?? 'N/A';
$guest_name = $cost_sheet['guest_name'] ?? $cost_sheet['customer_name'] ?? 'N/A';
$currency = $cost_sheet['currency'] ?? 'USD';
$total_expense = $cost_sheet['total_expense'] ?? '0.00';
$markup_percentage = $cost_sheet['markup_percentage'] ?? '0.00';
$markup_amount = $cost_sheet['markup_amount'] ?? '0.00';
$tax_percentage = $cost_sheet['tax_percentage'] ?? '0.00';
$tax_amount = $cost_sheet['tax_amount'] ?? '0.00';
$package_cost = $cost_sheet['package_cost'] ?? '0.00';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Cost Sheet - <?php echo htmlspecialchars($cost_sheet_number); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .cost-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .cost-table tr:last-child td {
            border-bottom: none;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
    
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div style="text-align: left;">
                <img src="assets/deskapp/vendors/images/custom-logo.svg" style="max-height: 60px;">
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold; font-size: 18px;">Simple Cost Sheet</div>
                <div style="font-size: 16px; color: #666;"><?php echo htmlspecialchars($cost_sheet_number); ?></div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Customer Information</div>
        <div class="info-row">
            <span class="info-label">Guest Name:</span>
            <span><?php echo htmlspecialchars($guest_name); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Mobile:</span>
            <span><?php echo htmlspecialchars($cost_sheet['mobile_number'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span><?php echo htmlspecialchars($cost_sheet['email'] ?? 'N/A'); ?></span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Cost Summary</div>
        <table class="cost-table">
            <tr>
                <td>Total Expense:</td>
                <td><?php echo htmlspecialchars($currency) . ' ' . number_format($total_expense, 2); ?></td>
            </tr>
            <tr>
                <td>Mark Up (<?php echo number_format($markup_percentage, 2); ?>%):</td>
                <td><?php echo htmlspecialchars($currency) . ' ' . number_format($markup_amount, 2); ?></td>
            </tr>
            <tr>
                <td>Service Tax (<?php echo number_format($tax_percentage, 2); ?>%):</td>
                <td><?php echo htmlspecialchars($currency) . ' ' . number_format($tax_amount, 2); ?></td>
            </tr>
            <tr>
                <td>Package Cost:</td>
                <td><?php echo htmlspecialchars($currency) . ' ' . number_format($package_cost, 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
        <p>This is a computer-generated document. No signature is required.</p>
        <p><strong>Note:</strong> This is a simplified template for debugging purposes.</p>
    </div>
</body>
</html>