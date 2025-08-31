<?php
// Include header
// require_once "includes/header.php";
require_once "config/database.php";

// Check if user has privilege to access this page
// if(!hasPrivilege('view_leads')) {
//     header("location: index.php");
//     exit;
// }

// Get cost file ID
$cost_file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($cost_file_id == 0) {
    echo "<div class='alert alert-danger'>Invalid cost file ID.</div>";
    require_once "includes/footer.php";
    exit;
}

// Get cost file data with all related information
$sql = "SELECT tc.*, e.customer_name, e.mobile_number, e.email, e.lead_number as enquiry_number, 
        cl.enquiry_number as lead_number, e.referral_code, e.created_at as enquiry_date, 
        cl.created_at as lead_date, s.name as source_name, dest.name as destination_name, 
        fm.full_name as file_manager_name, cl.night_day as night_day, 
        cl.travel_start_date as travel_start_date, cl.travel_end_date as travel_end_date, 
        dp.name as department_name
        FROM tour_costings tc 
        LEFT JOIN enquiries e ON tc.enquiry_id = e.id 
        LEFT JOIN sources s ON e.source_id = s.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN destinations dest ON cl.destination_id = dest.id
        LEFT JOIN users fm ON cl.file_manager_id = fm.id
        LEFT JOIN departments dp ON e.department_id = dp.id
        WHERE tc.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $cost_file_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Cost file not found.</div>";
    require_once "includes/footer.php";
    exit;
}

$cost_data = mysqli_fetch_assoc($result);

// Decode JSON data
$selected_services = json_decode($cost_data['selected_services'] ?? '[]', true);
$visa_data = json_decode($cost_data['visa_data'] ?? '[]', true);
$accommodation_data = json_decode($cost_data['accommodation_data'] ?? '[]', true);
$transportation_data = json_decode($cost_data['transportation_data'] ?? '[]', true);
$cruise_data = json_decode($cost_data['cruise_data'] ?? '[]', true);
$extras_data = json_decode($cost_data['extras_data'] ?? '[]', true);
$agent_package_data = json_decode($cost_data['agent_package_data'] ?? '[]', true);
$medical_tourism_data = json_decode($cost_data['medical_tourism_data'] ?? '[]', true);
$payment_data = json_decode($cost_data['payment_data'] ?? '{}', true);

// Generate invoice number if not exists
$invoice_number = 'INV-' . $cost_data['cost_sheet_number'];

// Company details (you can move these to a config file)
$company_name = "Gleesire Travel & Tourism";
$company_address = "123 Business Street, City, State 12345";
$company_phone = "+1 (555) 123-4567";
$company_email = "info@gleesire.com";
$company_website = "www.gleesire.com";
$company_logo = "assets/deskapp/vendors/images/custom-logo.svg";

?>


<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
        <title>Invoice - <?php echo htmlspecialchars($invoice_number); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
            .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
            .company-info { flex: 1; }
            .company-logo { max-height: 100px; }
            .company-name { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 5px; }
            .company-details { font-size: 11px; color: #666; }
            .invoice-info { text-align: right; }
            .invoice-title { font-size: 28px; font-weight: bold; color: #007bff; margin-bottom: 10px; }
            .invoice-number { font-size: 16px; font-weight: bold; }
            .invoice-date { font-size: 12px; color: #666; }
            .billing-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .bill-to, .ship-to { flex: 1; margin-right: 20px; }
            .section-title { font-size: 14px; font-weight: bold; color: #007bff; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
            .customer-info { font-size: 12px; }
            .services-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .services-table th, .services-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .services-table th { background-color: #f8f9fa; font-weight: bold; color: #007bff; }
            .services-table .text-right { text-align: right; }
            .services-table .text-center { text-align: center; }
            .summary-table { width: 100%; max-width: 400px; margin-left: auto; margin-bottom: 20px; }
            .summary-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .summary-table .total-row { font-weight: bold; background-color: #f8f9fa; border-top: 2px solid #007bff; }
            .payment-info { margin-bottom: 20px; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #666; }
            .service-section { margin-bottom: 25px; }
            .service-header { background-color: #007bff; color: white; padding: 8px; font-weight: bold; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
                .invoice-container { padding: 10px; }
            }
        </style>
</head>
<body>
        <div class="invoice-container">
            <!-- Header -->
            <div class="header">
                <div class="company-info">
                    <img src="<?php echo $company_logo; ?>" alt="Company Logo" class="company-logo">
                    <div class="company-name"><?php echo $company_name; ?></div>
                    <div class="company-details">
                        <?php echo $company_address; ?><br>
                        Phone: <?php echo $company_phone; ?><br>
                        Email: <?php echo $company_email; ?><br>
                        Website: <?php echo $company_website; ?>
                    </div>
                </div>
                <div class="invoice-info">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number"><?php echo htmlspecialchars($invoice_number); ?></div>
                    <div class="invoice-date">Date: <?php echo date('d-m-Y'); ?></div>
                    <?php if($cost_data['booking_number']): ?>
                    <div class="invoice-date">Booking: <?php echo htmlspecialchars($cost_data['booking_number']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Billing Information -->
            <div class="billing-section">
                <div class="bill-to">
                    <div class="section-title">Bill To:</div>
                    <div class="customer-info">
                        <strong><?php echo htmlspecialchars($cost_data['guest_name'] ?? $cost_data['customer_name']); ?></strong><br>
                        <?php if($cost_data['guest_address']): ?>
                        <?php echo htmlspecialchars($cost_data['guest_address']); ?><br>
                        <?php endif; ?>
                        Phone: <?php echo htmlspecialchars($cost_data['mobile_number']); ?><br>
                        <?php if($cost_data['whatsapp_number']): ?>
                        WhatsApp: <?php echo htmlspecialchars($cost_data['whatsapp_number']); ?><br>
                        <?php endif; ?>
                        <?php if($cost_data['email']): ?>
                        Email: <?php echo htmlspecialchars($cost_data['email']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ship-to">
                    <div class="section-title">Travel Details:</div>
                    <div class="customer-info">
                        <strong>Package:</strong> <?php echo htmlspecialchars($cost_data['tour_package'] ?? 'Custom Package'); ?><br>
                        <strong>Destination:</strong> <?php echo htmlspecialchars($cost_data['destination_name'] ?? 'N/A'); ?><br>
                        <strong>Duration:</strong> <?php echo htmlspecialchars($cost_data['night_day'] ?? 'N/A'); ?><br>
                        <strong>Travel Period:</strong><br>
                        <?php echo date('d-m-Y', strtotime($cost_data['travel_start_date'])); ?> to 
                        <?php echo date('d-m-Y', strtotime($cost_data['travel_end_date'])); ?><br>
                        <strong>Passengers:</strong> 
                        Adults: <?php echo $cost_data['adults_count'] ?? 0; ?>, 
                        Children: <?php echo $cost_data['children_count'] ?? 0; ?>, 
                        Infants: <?php echo $cost_data['infants_count'] ?? 0; ?>
                    </div>
                </div>
            </div>

            <!-- Services Details -->
            <?php if(in_array('visa_flight', $selected_services) && !empty($visa_data)): ?>
            <div class="service-section">
                <div class="service-header">VISA / FLIGHT BOOKING</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Sector</th>
                            <th>Supplier</th>
                            <th>Travel Date</th>
                            <th class="text-center">Passengers</th>
                            <th class="text-right">Rate/Person</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $visa_total = 0;
                        foreach($visa_data as $visa): 
                            $row_total = ($visa['passengers'] ?? 0) * ($visa['rate_per_person'] ?? 0) * ($visa['roe'] ?? 1);
                            $visa_total += $row_total;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucfirst($visa['sector'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($visa['supplier'] ?? ''); ?></td>
                            <td><?php echo $visa['travel_date'] ? date('d-m-Y', strtotime($visa['travel_date'])) : ''; ?></td>
                            <td class="text-center"><?php echo $visa['passengers'] ?? 0; ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($visa['rate_per_person'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($row_total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($visa_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('accommodation', $selected_services) && !empty($accommodation_data)): ?>
            <div class="service-section">
                <div class="service-header">ACCOMMODATION</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Hotel</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Room Type</th>
                            <th class="text-center">Rooms</th>
                            <th class="text-center">Nights</th>
                            <th class="text-right">Rate/Night</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $accommodation_total = 0;
                        foreach($accommodation_data as $accom): 
                            $accommodation_total += $accom['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($accom['hotel'] ?? ''); ?></td>
                            <td><?php echo $accom['check_in'] ? date('d-m-Y', strtotime($accom['check_in'])) : ''; ?></td>
                            <td><?php echo $accom['check_out'] ? date('d-m-Y', strtotime($accom['check_out'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($accom['room_type'] ?? ''); ?></td>
                            <td class="text-center"><?php echo $accom['rooms_no'] ?? 0; ?></td>
                            <td class="text-center"><?php echo $accom['nights'] ?? 0; ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($accom['rooms_rate'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($accom['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="7" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($accommodation_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('transportation', $selected_services) && !empty($transportation_data)): ?>
            <div class="service-section">
                <div class="service-header">TRANSPORTATION</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Vehicle Type</th>
                            <th class="text-center">Days</th>
                            <th class="text-right">Daily Rate</th>
                            <th class="text-right">Extra Charges</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $transportation_total = 0;
                        foreach($transportation_data as $trans): 
                            $transportation_total += $trans['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trans['supplier'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($trans['car_type'] ?? ''); ?></td>
                            <td class="text-center"><?php echo $trans['days'] ?? 0; ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($trans['daily_rent'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format(($trans['extra_km'] ?? 0) * ($trans['price_per_km'] ?? 0) + ($trans['toll'] ?? 0), 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($trans['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($transportation_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('cruise_hire', $selected_services) && !empty($cruise_data)): ?>
            <div class="service-section">
                <div class="service-header">CRUISE HIRE</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Boat Type</th>
                            <th>Cruise Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th class="text-right">Rate</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cruise_total = 0;
                        foreach($cruise_data as $cruise): 
                            $cruise_total += $cruise['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cruise['supplier'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cruise['boat_type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cruise['cruise_type'] ?? ''); ?></td>
                            <td><?php echo $cruise['check_in'] ? date('d-m-Y H:i', strtotime($cruise['check_in'])) : ''; ?></td>
                            <td><?php echo $cruise['check_out'] ? date('d-m-Y H:i', strtotime($cruise['check_out'])) : ''; ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($cruise['rate'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($cruise['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="6" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($cruise_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('agent_package', $selected_services) && !empty($agent_package_data)): ?>
            <div class="service-section">
                <div class="service-header">AGENT PACKAGE SERVICE</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Destination</th>
                            <th>Agent/Supplier</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Adults</th>
                            <th class="text-center">Children</th>
                            <th class="text-center">Infants</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $agent_package_total = 0;
                        foreach($agent_package_data as $package): 
                            $agent_package_total += $package['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($package['destination'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($package['agent_supplier'] ?? ''); ?></td>
                            <td><?php echo $package['start_date'] ? date('d-m-Y', strtotime($package['start_date'])) : ''; ?></td>
                            <td><?php echo $package['end_date'] ? date('d-m-Y', strtotime($package['end_date'])) : ''; ?></td>
                            <td class="text-center"><?php echo $package['adult_count'] ?? 0; ?></td>
                            <td class="text-center"><?php echo $package['child_count'] ?? 0; ?></td>
                            <td class="text-center"><?php echo $package['infant_count'] ?? 0; ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($package['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="7" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($agent_package_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('medical_tourism', $selected_services) && !empty($medical_tourism_data)): ?>
            <div class="service-section">
                <div class="service-header">MEDICAL TOURISM</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Place</th>
                            <th>Hospital</th>
                            <th>Treatment Date</th>
                            <th>Treatment Type</th>
                            <th class="text-right">Net Amount</th>
                            <th class="text-right">GST (18%)</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $medical_total = 0;
                        foreach($medical_tourism_data as $medical): 
                            $medical_total += $medical['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medical['place'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($medical['hospital'] ?? ''); ?></td>
                            <td><?php echo $medical['treatment_date'] ? date('d-m-Y', strtotime($medical['treatment_date'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($medical['treatment_type'] ?? ''); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format(($medical['net'] ?? 0) + ($medical['tds'] ?? 0) + ($medical['other_expenses'] ?? 0), 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format((($medical['net'] ?? 0) + ($medical['tds'] ?? 0) + ($medical['other_expenses'] ?? 0)) * 0.18, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($medical['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="6" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($medical_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(in_array('extras', $selected_services) && !empty($extras_data)): ?>
            <div class="service-section">
                <div class="service-header">EXTRAS / MISCELLANEOUS</div>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Service Type</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Extra Charges</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $extras_total = 0;
                        foreach($extras_data as $extra): 
                            $extras_total += $extra['total'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($extra['supplier'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($extra['service_type'] ?? ''); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($extra['amount'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($extra['extras'] ?? 0, 2); ?></td>
                            <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($extra['total'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($extras_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Summary -->
            <table class="summary-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($cost_data['total_expense'], 2); ?></td>
                </tr>
                <tr>
                    <td>Markup (<?php echo number_format($cost_data['markup_percentage'], 2); ?>%):</td>
                    <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($cost_data['markup_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Tax (<?php echo number_format($cost_data['tax_percentage'], 2); ?>%):</td>
                    <td class="text-right"><?php echo $cost_data['currency'] . ' ' . number_format($cost_data['tax_amount'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total Amount:</strong></td>
                    <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($cost_data['package_cost'], 2); ?></strong></td>
                </tr>
                <?php if($cost_data['currency'] != 'USD' && $cost_data['currency_rate'] > 0): ?>
                <tr>
                    <td>Amount in <?php echo $cost_data['currency']; ?> (Rate: <?php echo $cost_data['currency_rate']; ?>):</td>
                    <td class="text-right"><strong><?php echo $cost_data['currency'] . ' ' . number_format($cost_data['converted_amount'], 2); ?></strong></td>
                </tr>
                <?php endif; ?>
            </table>

            <!-- Payment Information -->
            <?php if(!empty($payment_data['amount']) && $payment_data['amount'] > 0): ?>
            <div class="payment-info">
                <div class="section-title">Payment Information</div>
                <table class="services-table">
                    <tr>
                        <td><strong>Payment Date:</strong></td>
                        <td><?php echo $payment_data['date'] ? date('d-m-Y', strtotime($payment_data['date'])) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Payment Method:</strong></td>
                        <td><?php echo htmlspecialchars($payment_data['bank'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Amount Paid:</strong></td>
                        <td><?php echo $cost_data['currency'] . ' ' . number_format($payment_data['amount'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Balance Due:</strong></td>
                        <td><?php echo $cost_data['currency'] . ' ' . number_format(($cost_data['package_cost'] - ($payment_data['amount'] ?? 0)), 2); ?></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer">
                <p><strong>Terms & Conditions:</strong></p>
                <p>1. Payment is due within 30 days of invoice date.</p>
                <p>2. All bookings are subject to availability and confirmation.</p>
                <p>3. Cancellation charges apply as per company policy.</p>
                <p>4. Travel insurance is recommended for all travelers.</p>
                <br>
                <p>Thank you for choosing <?php echo $company_name; ?>!</p>
                <p>Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
            </div>
        </div>

        <script>
            window.onload = function() {
                window.print();
            }
        </script>
</body>
</html>



<?php require_once "includes/footer.php"; ?>