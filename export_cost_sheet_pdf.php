<?php
// Include necessary files
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. Cost sheet ID is required.");
}

$cost_file_id = intval($_GET['id']);

// Get the specific cost sheet
$view_sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email 
            FROM tour_costings tc 
            LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
            WHERE tc.id = ?";
$view_stmt = mysqli_prepare($conn, $view_sql);
mysqli_stmt_bind_param($view_stmt, "i", $cost_file_id);
mysqli_stmt_execute($view_stmt);
$view_result = mysqli_stmt_get_result($view_stmt);

if($view_row = mysqli_fetch_assoc($view_result)) {
    $cost_sheet = $view_row;
} else {
    die("Cost sheet not found.");
}

// Set the content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Generate a printable HTML page that can be saved as PDF using browser's print function
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost Sheet - <?php echo htmlspecialchars($cost_sheet['cost_sheet_number']); ?></title>
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
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cost-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .cost-table tr:last-child td {
            border-bottom: none;
            font-weight: bold;
        }
        .cost-table td:last-child {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
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
        <table width="100%" style="margin-bottom: 10px;">
            <tr>
                <td width="50%" style="text-align: left;">
                    <img src="assets/deskapp/vendors/images/custom-logo.svg" style="max-height: 60px;">
                </td>
                <td width="50%" style="text-align: right;">
                    <div style="font-weight: bold; font-size: 18px;">Cost Sheet</div>
                    <div style="font-size: 16px; color: #666;"><?php echo htmlspecialchars($cost_sheet['cost_sheet_number']); ?></div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">Customer Information</div>
        <div class="info-row">
            <div class="info-label">Guest Name:</div>
            <div><?php echo htmlspecialchars($cost_sheet['guest_name'] ?? $cost_sheet['customer_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Mobile:</div>
            <div><?php echo htmlspecialchars($cost_sheet['mobile_number'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div><?php echo htmlspecialchars($cost_sheet['email'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Address:</div>
            <div><?php echo htmlspecialchars($cost_sheet['guest_address'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">WhatsApp:</div>
            <div><?php echo htmlspecialchars($cost_sheet['whatsapp_number'] ?? 'N/A'); ?></div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Travel Information</div>
        <div class="info-row">
            <div class="info-label">Tour Package:</div>
            <div><?php echo htmlspecialchars($cost_sheet['tour_package'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Currency:</div>
            <div><?php echo htmlspecialchars($cost_sheet['currency'] ?? 'USD'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Nationality:</div>
            <div><?php echo htmlspecialchars($cost_sheet['nationality'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Adults:</div>
            <div><?php echo htmlspecialchars($cost_sheet['adults_count'] ?? '0'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Children:</div>
            <div><?php echo htmlspecialchars($cost_sheet['children_count'] ?? '0'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Infants:</div>
            <div><?php echo htmlspecialchars($cost_sheet['infants_count'] ?? '0'); ?></div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Selected Services</div>
        <?php 
        $selected_services = json_decode($cost_sheet['selected_services'] ?? '[]', true);
        if(!empty($selected_services)): 
            $services_text = '';
            foreach($selected_services as $service) {
                $services_text .= strtoupper(str_replace('_', ' ', $service)) . ', ';
            }
            $services_text = rtrim($services_text, ', ');
            echo htmlspecialchars($services_text);
        else: 
            echo 'No services selected';
        endif; 
        ?>
    </div>
    
    <div class="section">
        <div class="section-title">Cost Summary</div>
        <table class="cost-table">
            <tr>
                <td>Total Expense:</td>
                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['total_expense'], 2); ?></td>
            </tr>
            <tr>
                <td>Mark Up (<?php echo number_format($cost_sheet['markup_percentage'], 2); ?>%):</td>
                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['markup_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>Service Tax (<?php echo number_format($cost_sheet['tax_percentage'], 2); ?>%):</td>
                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['tax_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>Package Cost:</td>
                <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['package_cost'], 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
?>