<?php
// Include error handler for debugging
require_once "includes/error_handler.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once "config/database.php";

// Include common functions
require_once "includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

$enquiry_id = isset($_GET['enquiry_id']) ? intval($_GET['enquiry_id']) : 0;

// Handle export actions
if(isset($_GET['action']) && ($_GET['action'] == 'export_pdf' || $_GET['action'] == 'export_excel') && isset($_GET['id'])) {
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
        
        // Debug the cost sheet data
        $debug_info = "<!-- Cost Sheet Data Debug:\n";
        $debug_info .= "ID: {$cost_sheet['id']}\n";
        $debug_info .= "Number: {$cost_sheet['cost_sheet_number']}\n";
        $debug_info .= "Selected Services: {$cost_sheet['selected_services']}\n";
        $debug_info .= "Adults Count: {$cost_sheet['adults_count']}\n";
        $debug_info .= "Children Count: {$cost_sheet['children_count']}\n";
        $debug_info .= "Infants Count: {$cost_sheet['infants_count']}\n";
        
        // List all available columns
        $debug_info .= "\nAll available columns:\n";
        foreach($cost_sheet as $key => $value) {
            if(is_string($value) || is_numeric($value)) {
                $debug_info .= "$key: $value\n";
            } else {
                $debug_info .= "$key: (complex type)\n";
            }
        }
        $debug_info .= "-->\n";
        
        if($_GET['action'] == 'export_pdf') {
            // Output PDF (HTML that can be printed)
            header('Content-Type: text/html; charset=utf-8');
            
            // Check if we should use the simple template for debugging
            if (isset($_GET['template']) && $_GET['template'] == 'simple') {
                // Enable error reporting for debugging
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                error_reporting(E_ALL);
                
                // Check if the file exists before including
                if (file_exists("simple_pdf_template.php")) {
                    include "simple_pdf_template.php";
                } else {
                    echo "<h1>Error: Simple PDF Template Not Found</h1>";
                    echo "<p>The simple_pdf_template.php file is missing.</p>";
                    echo "<p><a href='debug.php'>Run Diagnostics</a></p>";
                }
            } else {
                // Use the regular PDF template
                try {
                    // Enable error reporting for debugging
                    ini_set('display_errors', 1);
                    ini_set('display_startup_errors', 1);
                    error_reporting(E_ALL);
                    
                    // Include the data helper to fetch additional fields
                    if (file_exists("pdf_data_helper.php")) {
                        include "pdf_data_helper.php";
                    }
                    
                    // Check if the file exists before including
                    if (file_exists("pdf_template.php")) {
                        include "pdf_template.php";
                    } else {
                        throw new Exception("pdf_template.php file not found");
                    }
                } catch (Exception $e) {
                    echo "<h1>Error rendering PDF</h1>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><a href='?action=export_pdf&id=" . $cost_file_id . "&template=simple'>Try with simple template</a></p>";
                    echo "<p><a href='debug.php'>Run Diagnostics</a></p>";
                }
            }
            exit;
            // The code below is never reached due to the exit statement above
            ?>
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
                        width: 200px;
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
                    .service-section {
                        margin-bottom: 15px;
                    }
                    .service-title {
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .service-details {
                        margin-left: 20px;
                        margin-bottom: 10px;
                    }
                    .payment-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 15px;
                    }
                    .payment-table th, .payment-table td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    .payment-table th {
                        background-color: #f2f2f2;
                    }
                    .summary-box {
                        border: 1px solid #ddd;
                        padding: 10px;
                        margin-top: 15px;
                        background-color: #f9f9f9;
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
                    <h1>Gleesire - Cost Sheet</h1>
                    <h2><?php echo htmlspecialchars($cost_sheet['cost_sheet_number']); ?></h2>
                </div>
                
                <!-- Debug information (only visible in source) -->
                <!-- 
                Cost Sheet ID: <?php echo $cost_sheet['id']; ?>
                Selected Services: <?php echo htmlspecialchars($cost_sheet['selected_services'] ?? 'None'); ?>
                Parsed Services: <?php echo htmlspecialchars(print_r($selected_services, true)); ?>
                Normalized Services: <?php echo htmlspecialchars(print_r($normalized_services, true)); ?>
                -->
                
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
                </div>
                
                <div class="section">
                    <div class="section-title">Number of PAX</div>
                    <!-- Debug PAX data: <?php echo "adults: {$cost_sheet['adults_count']}, children: {$cost_sheet['children_count']}, infants: {$cost_sheet['infants_count']}"; ?> -->
                    <?php
                    // Try different possible column names for PAX counts
                    $adults_count = $cost_sheet['adults_count'] ?? $cost_sheet['adult_count'] ?? $cost_sheet['adults'] ?? $cost_sheet['adult'] ?? '0';
                    $children_count = $cost_sheet['children_count'] ?? $cost_sheet['child_count'] ?? $cost_sheet['children'] ?? $cost_sheet['child'] ?? '0';
                    $infants_count = $cost_sheet['infants_count'] ?? $cost_sheet['infant_count'] ?? $cost_sheet['infants'] ?? $cost_sheet['infant'] ?? '0';
                    ?>
                    <div class="info-row">
                        <div class="info-label">Adults:</div>
                        <div><?php echo htmlspecialchars($adults_count); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Children:</div>
                        <div><?php echo htmlspecialchars($children_count); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Infants:</div>
                        <div><?php echo htmlspecialchars($infants_count); ?></div>
                    </div>
                </div>
                
                <?php 
                // Debug the selected services
                $selected_services = json_decode($cost_sheet['selected_services'] ?? '[]', true);
                // Make sure we're properly parsing the JSON
                if($cost_sheet['selected_services'] && $selected_services === null) {
                    echo "<!-- JSON Error: " . json_last_error_msg() . " -->";
                    $selected_services = array(); // Fallback to empty array
                }
                
                // Normalize service names for more flexible matching
                $normalized_services = array();
                foreach($selected_services as $service) {
                    $normalized_services[] = strtolower(trim($service));
                }
                
                // Helper function to check if a service is selected
                function hasService($service, $normalized_services) {
                    if (empty($normalized_services) || !is_array($normalized_services)) {
                        return false;
                    }
                    $service = strtolower(trim($service));
                    foreach($normalized_services as $s) {
                        if(is_string($s) && (strpos($s, $service) !== false || strpos($service, $s) !== false)) {
                            return true;
                        }
                    }
                    return false;
                }
                
                if(!empty($selected_services)): 
                ?>
                <div class="section">
                    <div class="section-title">Selected Services</div>
                    <!-- Debug: <?php echo htmlspecialchars(print_r($selected_services, true)); ?> -->
                    
                    <?php if(hasService('visa', $normalized_services) || hasService('flight', $normalized_services)): ?>
                    <div class="service-section">
                        <div class="service-title">VISA / FLIGHT BOOKING</div>
                        <div class="service-details">
                            <?php 
                            // Try different possible column names for visa/flight details
                            $visa_flight_details_json = $cost_sheet['visa_flight_details'] ?? $cost_sheet['visa_flight_detail'] ?? $cost_sheet['visa_details'] ?? $cost_sheet['flight_details'] ?? '{}';
                            $visa_flight_details = json_decode($visa_flight_details_json, true);
                            echo "<!-- Visa/Flight details JSON: " . htmlspecialchars($visa_flight_details_json) . " -->";
                            if(!empty($visa_flight_details)): 
                                foreach($visa_flight_details as $key => $value):
                                    if(!empty($value)):
                            ?>
                            <div class="info-row">
                                <div class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                                echo "<p>No details available</p>";
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(hasService('accommodation', $normalized_services) || hasService('hotel', $normalized_services)): ?>
                    <div class="service-section">
                        <div class="service-title">ACCOMMODATION</div>
                        <div class="service-details">
                            <?php 
                            // Try different possible column names for accommodation details
                            $accommodation_details_json = $cost_sheet['accommodation_details'] ?? $cost_sheet['accommodation_detail'] ?? $cost_sheet['accommodation'] ?? $cost_sheet['hotel_details'] ?? '{}';
                            $accommodation_details = json_decode($accommodation_details_json, true);
                            echo "<!-- Accommodation details JSON: " . htmlspecialchars($accommodation_details_json) . " -->";
                            if(!empty($accommodation_details)): 
                                foreach($accommodation_details as $key => $value):
                                    if(!empty($value)):
                            ?>
                            <div class="info-row">
                                <div class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                                echo "<p>No details available</p>";
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(hasService('transportation', $normalized_services) || hasService('transport', $normalized_services)): ?>
                    <div class="service-section">
                        <div class="service-title">INTERNAL TRANSPORTATION</div>
                        <div class="service-details">
                            <?php 
                            // Try different possible column names for transportation details
                            $transportation_details_json = $cost_sheet['transportation_details'] ?? $cost_sheet['transportation_detail'] ?? $cost_sheet['transportation'] ?? $cost_sheet['transport_details'] ?? '{}';
                            $transportation_details = json_decode($transportation_details_json, true);
                            echo "<!-- Transportation details JSON: " . htmlspecialchars($transportation_details_json) . " -->";
                            if(!empty($transportation_details)): 
                                foreach($transportation_details as $key => $value):
                                    if(!empty($value)):
                            ?>
                            <div class="info-row">
                                <div class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                                echo "<p>No details available</p>";
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Debug cruise service
                    $has_cruise = hasService('cruise', $normalized_services);
                    echo "<!-- Has cruise: " . ($has_cruise ? 'Yes' : 'No') . " -->";
                    if($has_cruise): 
                    ?>
                    <div class="service-section">
                        <div class="service-title">CRUISE HIRE</div>
                        <div class="service-details">
                        <!-- Debug cruise details: <?php echo htmlspecialchars(print_r($cost_sheet['cruise_details'] ?? '{}', true)); ?> -->
                            <?php 
                            // Try different possible column names for cruise details
                            $cruise_details_json = $cost_sheet['cruise_details'] ?? $cost_sheet['cruise_detail'] ?? $cost_sheet['cruise'] ?? '{}';
                            $cruise_details = json_decode($cruise_details_json, true);
                            echo "<!-- Cruise details JSON: " . htmlspecialchars($cruise_details_json) . " -->";
                            if(!empty($cruise_details)): 
                                foreach($cruise_details as $key => $value):
                                    if(!empty($value)):
                            ?>
                            <div class="info-row">
                                <div class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                                echo "<p>No details available</p>";
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Debug extras service
                    $has_extras = hasService('extras', $normalized_services) || hasService('miscellaneous', $normalized_services);
                    echo "<!-- Has extras: " . ($has_extras ? 'Yes' : 'No') . " -->";
                    if($has_extras): 
                    ?>
                    <div class="service-section">
                        <div class="service-title">EXTRAS/MISCELLANEOUS</div>
                        <div class="service-details">
                        <!-- Debug extras details: <?php echo htmlspecialchars(print_r($cost_sheet['extras_details'] ?? '{}', true)); ?> -->
                            <?php 
                            // Try different possible column names for extras details
                            $extras_details_json = $cost_sheet['extras_details'] ?? $cost_sheet['extras_detail'] ?? $cost_sheet['extras'] ?? $cost_sheet['miscellaneous_details'] ?? $cost_sheet['miscellaneous'] ?? '{}';
                            $extras_details = json_decode($extras_details_json, true);
                            echo "<!-- Extras details JSON: " . htmlspecialchars($extras_details_json) . " -->";
                            if(!empty($extras_details)): 
                                foreach($extras_details as $key => $value):
                                    if(!empty($value)):
                            ?>
                            <div class="info-row">
                                <div class="info-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                                echo "<p>No details available</p>";
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="section">
                    <div class="section-title">Payment Details</div>
                    <?php
                    // Always show payment details section
                    $total_paid = 0;
                    
                    // Check if payment_receipts table exists
                    $table_exists = false;
                    try {
                        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'payment_receipts'");
                        $table_exists = $table_check && mysqli_num_rows($table_check) > 0;
                    } catch (Exception $e) {
                        // Table doesn't exist
                        echo "<!-- Payment table error: " . htmlspecialchars($e->getMessage()) . " -->";
                    }
                    
                    if($table_exists) {
                        try {
                            // Get payment details for this cost sheet
                            $payment_sql = "SELECT * FROM payment_receipts WHERE cost_sheet_id = ? ORDER BY payment_date ASC";
                            $payment_stmt = mysqli_prepare($conn, $payment_sql);
                            mysqli_stmt_bind_param($payment_stmt, "i", $cost_sheet['id']);
                            mysqli_stmt_execute($payment_stmt);
                            $payment_result = mysqli_stmt_get_result($payment_stmt);
                            
                            if(mysqli_num_rows($payment_result) > 0):
                            ?>
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    while($payment = mysqli_fetch_assoc($payment_result)): 
                                        $total_paid += $payment['amount'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['status']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <p>No payment records found.</p>
                            <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                            <p>Error retrieving payment details.</p>
                            <!-- Payment query error: <?php echo htmlspecialchars($e->getMessage()); ?> -->
                        <?php }
                    } else { ?>
                        <p>Payment system not configured yet.</p>
                    <?php } ?>
                </div>
                
                <div class="section">
                    <div class="section-title">Summary</div>
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
                        <tr>
                            <td>Confirmed:</td>
                            <td><?php echo number_format($cost_sheet['confirmed'], 0) == 1 ? 'Yes': 'No'; ?></td>
                        </tr>
                    </table>
                    
                    <div class="summary-box">
                        <?php
                        // Calculate balance
                        $balance = $cost_sheet['package_cost'] - ($total_paid ?? 0);
                        ?>
                        <div class="info-row">
                            <div class="info-label">Total Package Cost:</div>
                            <div><strong><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($cost_sheet['package_cost'], 2); ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Total Paid:</div>
                            <div><strong><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($total_paid, 2); ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Balance:</div>
                            <div><strong><?php echo htmlspecialchars($cost_sheet['currency']) . ' ' . number_format($balance, 2); ?></strong></div>
                        </div>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
                    <p>This is a computer-generated document. No signature is required.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else if($_GET['action'] == 'export_excel') {
            // Output CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Cost_Sheet_' . $cost_sheet['cost_sheet_number'] . '.csv');
            
            // Create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel to properly display UTF-8 characters
            fputs($output, "\xEF\xBB\xBF");
            
            // Add header rows
            fputcsv($output, ['Gleesire - Cost Sheet']);
            fputcsv($output, ['Cost Sheet: ' . $cost_sheet['cost_sheet_number']]);
            fputcsv($output, ['Generated on: ' . date('d-m-Y H:i')]);
            fputcsv($output, []); // Empty row for spacing
            
            // Customer Information
            fputcsv($output, ['Customer Information']);
            fputcsv($output, ['Guest Name:', $cost_sheet['guest_name'] ?? $cost_sheet['customer_name']]);
            fputcsv($output, ['Mobile:', $cost_sheet['mobile_number'] ?? 'N/A']);
            fputcsv($output, ['Email:', $cost_sheet['email'] ?? 'N/A']);
            fputcsv($output, ['Address:', $cost_sheet['guest_address'] ?? 'N/A']);
            fputcsv($output, ['WhatsApp:', $cost_sheet['whatsapp_number'] ?? 'N/A']);
            fputcsv($output, []); // Empty row for spacing
            
            // Travel Information
            fputcsv($output, ['Travel Information']);
            fputcsv($output, ['Tour Package:', $cost_sheet['tour_package'] ?? 'N/A']);
            fputcsv($output, ['Currency:', $cost_sheet['currency'] ?? 'USD']);
            fputcsv($output, ['Nationality:', $cost_sheet['nationality'] ?? 'N/A']);
            fputcsv($output, []); // Empty row for spacing
            
            // Number of PAX
            // Try different possible column names for PAX counts
            $adults_count = $cost_sheet['adults_count'] ?? $cost_sheet['adult_count'] ?? $cost_sheet['adults'] ?? $cost_sheet['adult'] ?? '0';
            $children_count = $cost_sheet['children_count'] ?? $cost_sheet['child_count'] ?? $cost_sheet['children'] ?? $cost_sheet['child'] ?? '0';
            $infants_count = $cost_sheet['infants_count'] ?? $cost_sheet['infant_count'] ?? $cost_sheet['infants'] ?? $cost_sheet['infant'] ?? '0';
            
            fputcsv($output, ['Number of PAX']);
            fputcsv($output, ['Adults:', $adults_count]);
            fputcsv($output, ['Children:', $children_count]);
            fputcsv($output, ['Infants:', $infants_count]);
            fputcsv($output, []); // Empty row for spacing
            
            // Selected Services
            $selected_services = json_decode($cost_sheet['selected_services'] ?? '[]', true);
            if($cost_sheet['selected_services'] && $selected_services === null) {
                // JSON error fallback
                $selected_services = array();
            }
            
            // Normalize service names for more flexible matching
            $normalized_services = array();
            foreach($selected_services as $service) {
                $normalized_services[] = strtolower(trim($service));
            }
            
            // Helper function to check if a service is selected
            function hasServiceExcel($service, $normalized_services) {
                if (empty($normalized_services) || !is_array($normalized_services)) {
                    return false;
                }
                $service = strtolower(trim($service));
                foreach($normalized_services as $s) {
                    if(is_string($s) && (strpos($s, $service) !== false || strpos($service, $s) !== false)) {
                        return true;
                    }
                }
                return false;
            }
            
            if(!empty($selected_services)) {
                fputcsv($output, ['Selected Services']);
                
                // VISA / FLIGHT BOOKING
                if(hasServiceExcel('visa', $normalized_services) || hasServiceExcel('flight', $normalized_services)) {
                    fputcsv($output, ['VISA / FLIGHT BOOKING']);
                    // Try different possible column names for visa/flight details
                    $visa_flight_details_json = $cost_sheet['visa_flight_details'] ?? $cost_sheet['visa_flight_detail'] ?? $cost_sheet['visa_details'] ?? $cost_sheet['flight_details'] ?? '{}';
                    $visa_flight_details = json_decode($visa_flight_details_json, true);
                    if(!empty($visa_flight_details)) {
                        foreach($visa_flight_details as $key => $value) {
                            if(!empty($value)) {
                                fputcsv($output, [ucwords(str_replace('_', ' ', $key)) . ':', $value]);
                            }
                        }
                    } else {
                        fputcsv($output, ['No details available']);
                    }
                    fputcsv($output, []); // Empty row for spacing
                }
                
                // ACCOMMODATION
                if(hasServiceExcel('accommodation', $normalized_services) || hasServiceExcel('hotel', $normalized_services)) {
                    fputcsv($output, ['ACCOMMODATION']);
                    // Try different possible column names for accommodation details
                    $accommodation_details_json = $cost_sheet['accommodation_details'] ?? $cost_sheet['accommodation_detail'] ?? $cost_sheet['accommodation'] ?? $cost_sheet['hotel_details'] ?? '{}';
                    $accommodation_details = json_decode($accommodation_details_json, true);
                    if(!empty($accommodation_details)) {
                        foreach($accommodation_details as $key => $value) {
                            if(!empty($value)) {
                                fputcsv($output, [ucwords(str_replace('_', ' ', $key)) . ':', $value]);
                            }
                        }
                    } else {
                        fputcsv($output, ['No details available']);
                    }
                    fputcsv($output, []); // Empty row for spacing
                }
                
                // INTERNAL TRANSPORTATION
                if(hasServiceExcel('transportation', $normalized_services) || hasServiceExcel('transport', $normalized_services)) {
                    fputcsv($output, ['INTERNAL TRANSPORTATION']);
                    // Try different possible column names for transportation details
                    $transportation_details_json = $cost_sheet['transportation_details'] ?? $cost_sheet['transportation_detail'] ?? $cost_sheet['transportation'] ?? $cost_sheet['transport_details'] ?? '{}';
                    $transportation_details = json_decode($transportation_details_json, true);
                    if(!empty($transportation_details)) {
                        foreach($transportation_details as $key => $value) {
                            if(!empty($value)) {
                                fputcsv($output, [ucwords(str_replace('_', ' ', $key)) . ':', $value]);
                            }
                        }
                    } else {
                        fputcsv($output, ['No details available']);
                    }
                    fputcsv($output, []); // Empty row for spacing
                }
                
                // CRUISE HIRE
                if(hasServiceExcel('cruise', $normalized_services)) {
                    fputcsv($output, ['CRUISE HIRE']);
                    // Try different possible column names for cruise details
                    $cruise_details_json = $cost_sheet['cruise_details'] ?? $cost_sheet['cruise_detail'] ?? $cost_sheet['cruise'] ?? '{}';
                    $cruise_details = json_decode($cruise_details_json, true);
                    if(!empty($cruise_details)) {
                        foreach($cruise_details as $key => $value) {
                            if(!empty($value)) {
                                fputcsv($output, [ucwords(str_replace('_', ' ', $key)) . ':', $value]);
                            }
                        }
                    } else {
                        fputcsv($output, ['No details available']);
                    }
                    fputcsv($output, []); // Empty row for spacing
                }
                
                // EXTRAS/MISCELLANEOUS
                if(hasServiceExcel('extras', $normalized_services) || hasServiceExcel('miscellaneous', $normalized_services)) {
                    fputcsv($output, ['EXTRAS/MISCELLANEOUS']);
                    // Try different possible column names for extras details
                    $extras_details_json = $cost_sheet['extras_details'] ?? $cost_sheet['extras_detail'] ?? $cost_sheet['extras'] ?? $cost_sheet['miscellaneous_details'] ?? $cost_sheet['miscellaneous'] ?? '{}';
                    $extras_details = json_decode($extras_details_json, true);
                    if(!empty($extras_details)) {
                        foreach($extras_details as $key => $value) {
                            if(!empty($value)) {
                                fputcsv($output, [ucwords(str_replace('_', ' ', $key)) . ':', $value]);
                            }
                        }
                    } else {
                        fputcsv($output, ['No details available']);
                    }
                    fputcsv($output, []); // Empty row for spacing
                }
            }
            
            // Payment Details
            fputcsv($output, ['Payment Details']);
            $total_paid = 0;
            
            // Check if payment_receipts table exists
            $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payment_receipts'");
            
            if(mysqli_num_rows($table_exists) > 0) {
                $payment_sql = "SELECT * FROM payment_receipts WHERE cost_sheet_id = ? ORDER BY payment_date ASC";
                $payment_stmt = mysqli_prepare($conn, $payment_sql);
                mysqli_stmt_bind_param($payment_stmt, "i", $cost_sheet['id']);
                mysqli_stmt_execute($payment_stmt);
                $payment_result = mysqli_stmt_get_result($payment_stmt);
                
                if(mysqli_num_rows($payment_result) > 0) {
                    fputcsv($output, ['Receipt #', 'Date', 'Amount', 'Payment Method', 'Status']);
                    while($payment = mysqli_fetch_assoc($payment_result)) {
                        $total_paid += $payment['amount'];
                        fputcsv($output, [
                            $payment['receipt_number'],
                            date('d-m-Y', strtotime($payment['payment_date'])),
                            $cost_sheet['currency'] . ' ' . number_format($payment['amount'], 2),
                            $payment['payment_method'],
                            $payment['status']
                        ]);
                    }
                } else {
                    fputcsv($output, ['No payment records found.']);
                }
            } else {
                fputcsv($output, ['Payment system not configured yet.']);
            }
            fputcsv($output, []); // Empty row for spacing
            
            fputcsv($output, ['Confirmed:', number_format($cost_sheet['confirmed'], 0) == 1 ? 'Yes': 'No']);
            // Summary
            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Expense:', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['total_expense'], 2)]);
            fputcsv($output, ['Mark Up (' . number_format($cost_sheet['markup_percentage'], 2) . '%):', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['markup_amount'], 2)]);
            fputcsv($output, ['Service Tax (' . number_format($cost_sheet['tax_percentage'], 2) . '%):', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['tax_amount'], 2)]);
            fputcsv($output, ['Package Cost:', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['package_cost'], 2)]);
            
            fputcsv($output, []); // Empty row for spacing
            
            // Balance
            $balance = $cost_sheet['package_cost'] - $total_paid;
            fputcsv($output, ['Total Package Cost:', $cost_sheet['currency'] . ' ' . number_format($cost_sheet['package_cost'], 2)]);
            fputcsv($output, ['Total Paid:', $cost_sheet['currency'] . ' ' . number_format($total_paid, 2)]);
            fputcsv($output, ['Balance:', $cost_sheet['currency'] . ' ' . number_format($balance, 2)]);
            fputcsv($output, []); // Empty row for spacing
            
            // Footer
            fputcsv($output, ['This is a computer-generated document. No signature is required.']);
            
            // Close the file pointer
            fclose($output);
            exit;
        }
    } else {
        die("Cost sheet not found.");
    }
}

// Include header for non-export requests
if (!isset($_GET['action']) || ($_GET['action'] != 'export_pdf' && $_GET['action'] != 'export_excel')) {
    require_once "includes/header.php";
}

// Handle delete action
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isAdmin()) {
    $cost_file_id = intval($_GET['id']);
    
    // Delete cost file
    $delete_sql = "DELETE FROM tour_costings WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $cost_file_id);
    
    if(mysqli_stmt_execute($delete_stmt)) {
        $success_message = "Cost file deleted successfully!";
    } else {
        $error_message = "Error deleting cost file: " . mysqli_error($conn);
    }
}

// Handle view action for a specific cost sheet
$view_cost_sheet = null;
if(isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
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
        $view_cost_sheet = $view_row;
    }
}

// Search functionality
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$search_number = isset($_GET['search_number']) ? trim($_GET['search_number']) : '';

// Get all cost files grouped by base number with search filters
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email,
        SUBSTRING_INDEX(tc.cost_sheet_number, '-S', 1) as base_number,
        u.full_name as file_manager_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN users u ON cl.file_manager_id = u.id";

if ($enquiry_id !=0){
    $sql .= " WHERE tc.enquiry_id = $enquiry_id";
}

// Add search conditions if provided
if (!empty($search_name)) {
    $search_name = '%' . mysqli_real_escape_string($conn, $search_name) . '%';
    $sql .= " AND (tc.guest_name LIKE '$search_name' OR e.customer_name LIKE '$search_name')";
}

if (!empty($search_number)) {
    $search_number = '%' . mysqli_real_escape_string($conn, $search_number) . '%';
    $sql .= " AND tc.cost_sheet_number LIKE '$search_number'";
}

$sql .= " ORDER BY base_number DESC, tc.cost_sheet_number DESC";
$result = mysqli_query($conn, $sql);

// Group results by base number
$grouped_costs = [];
while($row = mysqli_fetch_assoc($result)) {
    $base = $row['base_number'];
    if(!isset($grouped_costs[$base])) {
        $grouped_costs[$base] = [];
    }
    $grouped_costs[$base][] = $row;
}


?>

<style>

.table-responsive{
    padding-bottom: 100px;
}
.dropdown-menu{
    right: 20px;
    left: unset;
}

.version-list {
    margin-bottom: 15px;
}

.version-list .version-item {
    display: block;
    padding: 8px 15px;
    margin-bottom: 5px;
    background-color: #f8f9fa;
    border-left: 3px solid #4facfe;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.version-list .version-item:hover {
    background-color: #e9ecef;
    border-left-color: #007bff;
    transform: translateX(5px);
}

.version-list .version-item.active {
    background-color: #e3f2fd;
    border-left-color: #007bff;
    font-weight: bold;
}

.cost-sheet-details {
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.cost-sheet-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.cost-sheet-header h5 {
    margin-bottom: 5px;
}

.cost-sheet-section {
    margin-bottom: 20px;
}

.cost-sheet-section h6 {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.info-row {
    display: flex;
    margin-bottom: 8px;
}

.info-label {
    font-weight: 600;
    width: 150px;
    color: #6c757d;
}

.info-value {
    flex: 1;
}

.service-badge {
    display: inline-block;
    padding: 4px 8px;
    margin-right: 5px;
    margin-bottom: 5px;
    border-radius: 4px;
    font-size: 0.8rem;
    background-color: #e9ecef;
}

.cost-summary-table {
    width: 100%;
    margin-bottom: 20px;
}

.cost-summary-table td {
    padding: 8px;
    border-bottom: 1px solid #e9ecef;
}

.cost-summary-table td:first-child {
    font-weight: 600;
    width: 200px;
}

.cost-summary-table td:last-child {
    text-align: right;
    font-weight: 500;
}

.cost-summary-table tr:last-child td {
    border-bottom: none;
    font-weight: 700;
    font-size: 1.1rem;
}

.accordion-toggle-all {
    margin-bottom: 15px;
    text-align: right;
}
</style>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Cost Files</h4>
        </div>
        <div class="pull-right">
            <div class="accordion-toggle-all">
                <button class="btn btn-sm btn-outline-primary" id="expandAll">Expand All</button>
                <button class="btn btn-sm btn-outline-secondary" id="collapseAll">Collapse All</button>
            </div>
        </div>
    </div>
    
    <!-- Search Form -->
    <div class="mb-20">
        <form action="" method="GET" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" class="form-control" name="search_name" placeholder="Search by name" value="<?php echo htmlspecialchars($search_name ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" class="form-control" name="search_number" placeholder="Search by cost sheet number" value="<?php echo htmlspecialchars($search_number ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                <?php if(!empty($search_name) || !empty($search_number)): ?>
                    <a href="view_cost_sheets.php" class="btn btn-secondary"><i class="fa fa-times"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if($view_cost_sheet): ?>
        <!-- Display detailed view of a specific cost sheet -->
        <div class="cost-sheet-details">
            <div class="cost-sheet-header">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Cost Sheet: <?php echo htmlspecialchars($view_cost_sheet['cost_sheet_number']); ?></h5>
                        <p class="mb-0"><?php echo htmlspecialchars($view_cost_sheet['guest_name'] ?? $view_cost_sheet['customer_name']); ?> | Created: <?php echo date('d-m-Y H:i', strtotime($view_cost_sheet['created_at'])); ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="view_cost_sheets.php" class="btn btn-sm btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
                        <?php if($has_sheet_confirmed == 0): ?>
                            <a href="edit_cost_file.php?id=<?php echo $view_cost_sheet['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i> Edit Cost sheet</a>
                        <?php endif; ?>                        <!-- <a href="view_payment_receipts.php?id=<?php echo $view_cost_sheet['id']; ?>" class="btn btn-sm btn-info"><i class="fa fa-credit-card"></i> Payment Details</a> -->
                        <a href="view_cost_sheets.php?action=export_pdf&id=<?php echo $view_cost_sheet['id']; ?>" class="btn btn-sm btn-danger" target="_blank"><i class="fa fa-file-pdf-o"></i> PDF</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="cost-sheet-section">
                        <h6><i class="fa fa-user"></i> Customer Information</h6>
                        <div class="info-row">
                            <div class="info-label">Guest Name:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['guest_name'] ?? $view_cost_sheet['customer_name']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mobile:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['mobile_number'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Address:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['guest_address'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">WhatsApp:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['whatsapp_number'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="cost-sheet-section">
                        <h6><i class="fa fa-plane"></i> Travel Information</h6>
                        <div class="info-row">
                            <div class="info-label">Tour Package:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['tour_package'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Currency:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['currency'] ?? 'USD'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nationality:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['nationality'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Adults:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['adults_count'] ?? '0'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Children:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['children_count'] ?? '0'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Infants:</div>
                            <div class="info-value"><?php echo htmlspecialchars($view_cost_sheet['infants_count'] ?? '0'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="cost-sheet-section">
                <h6><i class="fa fa-cogs"></i> Selected Services</h6>
                <?php 
                $selected_services = json_decode($view_cost_sheet['selected_services'] ?? '[]', true);
                if(!empty($selected_services)): 
                ?>
                    <?php foreach($selected_services as $service): ?>
                        <span class="service-badge"><?php echo strtoupper(str_replace('_', ' ', $service)); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No services selected</p>
                <?php endif; ?>
            </div>
            
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
                    <tr>
                        <td>Confirmed:</td>
                        <td><?php echo number_format($view_cost_sheet['confirmed'], 0) == 1 ? 'Yes': 'No'; ?></td>
                    </tr>
                </table>
            </div>
            
            <?php 
            // Get all versions of this cost sheet
            $base_number = preg_replace('/-S\d+$/', '', $view_cost_sheet['cost_sheet_number']);
            $versions_sql = "SELECT id, cost_sheet_number, created_at 
                            FROM tour_costings 
                            WHERE cost_sheet_number LIKE ? 
                            ORDER BY cost_sheet_number DESC";
            $versions_stmt = mysqli_prepare($conn, $versions_sql);
            $search_pattern = $base_number . '-%';
            mysqli_stmt_bind_param($versions_stmt, "s", $search_pattern);
            mysqli_stmt_execute($versions_stmt);
            $versions_result = mysqli_stmt_get_result($versions_stmt);
            
            if(mysqli_num_rows($versions_result) > 1): // Only show if there are multiple versions
            ?>
            <div class="cost-sheet-section">
                <h6><i class="fa fa-history"></i> Version History</h6>
                <div class="version-list">
                    <?php while($version = mysqli_fetch_assoc($versions_result)): ?>
                        <a href="view_cost_sheets.php?action=view&id=<?php echo $version['id']; ?>" 
                           class="version-item <?php echo ($version['id'] == $view_cost_sheet['id']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($version['cost_sheet_number']); ?> 
                            <small class="text-muted">(<?php echo date('d-m-Y H:i', strtotime($version['created_at'])); ?>)</small>
                            <?php if($version['id'] == $view_cost_sheet['id']): ?>
                                <span class="badge badge-primary float-right">Current</span>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Display list of all cost sheets grouped by base number -->
        <div class="accordion" id="costSheetsAccordion">
            <?php if(!empty($grouped_costs)): ?>
                <?php $accordion_index = 0; ?>
                <?php foreach($grouped_costs as $base_number => $versions): ?>
                    <?php $latest_version = $versions[0];
                        $has_sheet_confirmed = 0;
                        foreach($versions as $version) {
                            if($version['confirmed'] == 1) {
                                $has_sheet_confirmed = 1;
                                break;
                            }
                        } 
                        
                    
                    ?>
                    <div class="card">
                        <div class="card-header" id="heading<?php echo $accordion_index; ?>">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?php echo $accordion_index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $accordion_index; ?>">
                                    <strong><?php echo htmlspecialchars($base_number); ?></strong> - <?php echo htmlspecialchars($latest_version['guest_name'] ?? $latest_version['customer_name']); ?> 
                                    <?php if(!empty($latest_version['file_manager_name'])): ?>
                                        <small class="text-muted">(File Manager: <?php echo htmlspecialchars($latest_version['file_manager_name']); ?>)</small>
                                    <?php endif; ?>
                                    <span class="badge badge-primary"><?php echo count($versions); ?> version(s)</span>
                                </button>
                            </h2>
                        </div>
                        <div id="collapse<?php echo $accordion_index; ?>" class="collapse" aria-labelledby="heading<?php echo $accordion_index; ?>" data-parent="#costSheetsAccordion">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Version</th>
                                                <th>Guest Name</th>
                                                <th>Package Cost</th>
                                                <th>Confirmed</th>
                                                <th>Created At</th>
                                                <th>Download</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($versions as $version): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($version['cost_sheet_number']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($version['guest_name'] ?? $version['customer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($version['currency']) . ' ' . number_format($version['package_cost'], 2); ?></td>
                                                    <td><?php echo number_format($version['confirmed'], 0) == 1 ? 'Yes': 'No'; ?></td>
                                                    <td><?php echo date('d-m-Y H:i', strtotime($version['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_cost_sheets.php?action=export_pdf&id=<?php echo $version['id']; ?>" class="btn btn-sm btn-danger" target="_blank" title="Download PDF"><i class="fa fa-file-pdf-o"></i> PDF</a>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <a class="btn btn-link font-18 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                                <i class="dw dw-more"></i>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                                <!-- <a class="dropdown-item" href="view_cost_sheets.php?action=view&id=<?php echo $version['id']; ?>"><i class="dw dw-eye"></i> View</a> -->
                                                                <a class="dropdown-item" href="view_cost_sheets.php?action=export_pdf&id=<?php echo $version['id']; ?>" target="_blank"><i class="dw dw-eye"></i>View Cost Sheet</a>
                                                                <?php if($has_sheet_confirmed == 0): ?>
                                                                    <a class="dropdown-item" href="edit_cost_file.php?id=<?php echo $version['id']; ?>"><i class="dw dw-edit2"></i> Edit Cost Sheet</a>
                                                                <?php endif; ?>  
                                                                
                                                                
                                                                <!-- <a class="dropdown-item" href="view_payment_receipts.php?id=<?php echo $version['id']; ?>"><i class="dw dw-credit-card"></i> Payment Details</a> -->
                                                                <?php if(isAdmin()): ?>
                                                                    <a class="dropdown-item" href="view_cost_sheets.php?action=delete&id=<?php echo $version['id']; ?>" onclick="return confirm('Are you sure you want to delete this version?');"><i class="dw dw-delete-3"></i> Delete</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $accordion_index++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No cost files found.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Expand/collapse all accordions
document.addEventListener('DOMContentLoaded', function() {
    const expandAllBtn = document.getElementById('expandAll');
    const collapseAllBtn = document.getElementById('collapseAll');
    
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.collapse').forEach(item => {
                item.classList.add('show');
            });
        });
    }
    
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.collapse').forEach(item => {
                item.classList.remove('show');
            });
        });
    }
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>