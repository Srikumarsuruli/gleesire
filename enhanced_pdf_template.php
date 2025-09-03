<?php
// Enhanced PDF template with complete missing information
// Calculate total PAX
$total_pax = ($cost_sheet['adults_count'] ?? 0) + ($cost_sheet['children_count'] ?? 0) + ($cost_sheet['infants_count'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost Sheet - <?php echo htmlspecialchars($cost_sheet['cost_sheet_number']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
            background-color: #ffffff;
            font-size: 10px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .logo {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            max-width: 150px;
            max-height: 80px;
            border-radius: 8px;
            background: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
        }
        .info-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 6px;
            align-items: flex-start;
        }
        .info-label {
            font-weight: 600;
            width: 100px;
            color: #666;
            font-size: 9px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            font-size: 9px;
            word-break: break-word;
        }
        .services-section {
            padding: 20px;
        }
        .service-card {
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .service-header {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 11px;
        }
        .service-content {
            padding: 10px;
        }
        .service-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .service-table th {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 6px 4px;
            text-align: left;
            font-weight: 600;
            color: #495057;
        }
        .service-table td {
            border: 1px solid #dee2e6;
            padding: 6px 4px;
            vertical-align: top;
        }
        .service-table tfoot td {
            background: #e9ecef;
            font-weight: bold;
            color: #007bff;
        }
        .summary-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
        }
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cost-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .cost-table td:first-child {
            font-weight: 600;
            color: #666;
        }
        .cost-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .cost-table tr:last-child td {
            border-bottom: 2px solid #007bff;
            font-weight: bold;
            color: #007bff;
            font-size: 11px;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .payment-table th {
            background: #007bff;
            color: white;
            border: 1px solid #0056b3;
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
        }
        .payment-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
        }
        .balance-highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
        }
        .balance-highlight.pending {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }
        .footer {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .company-info {
            font-weight: bold;
            font-size: 12px;
        }
        .generation-info {
            font-size: 9px;
            opacity: 0.8;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .container { box-shadow: none; }
            .no-print { display: none; }
            @page { size: A4; margin: 0.5cm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="assets/deskapp/vendors/images/custom-logo.svg" alt="Gleesire Logo" class="logo">
            <h1>GLEESIRE TRAVEL & TOURISM</h1>
            <h2>Cost Sheet: <?php echo htmlspecialchars($cost_sheet['cost_sheet_number']); ?></h2>
        </div>

        <!-- Customer & Travel Information -->
        <div class="info-grid">
            <div class="info-card">
                <h3>👤 Customer Information</h3>
                <div class="info-row">
                    <div class="info-label">Guest Name:</div>
                    <div class="info-value"><strong><?php echo htmlspecialchars($cost_sheet['guest_name'] ?? $cost_sheet['customer_name']); ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Lead Number:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['lead_number'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Lead Date:</div>
                    <div class="info-value"><?php echo isset($cost_sheet['lead_date']) ? date('d-m-Y', strtotime($cost_sheet['lead_date'])) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Mobile:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['mobile_number'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['email'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['guest_address'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">WhatsApp:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['whatsapp_number'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="info-card">
                <h3>✈️ Travel Information</h3>
                <div class="info-row">
                    <div class="info-label">Ref Code:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['referral_code'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Source / Agent:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['source_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Travel Destination:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['travel_destination'] ?? $cost_sheet['tour_package'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Package:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['tour_package'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Currency:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['currency'] ?? 'USD'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nationality:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['nationality'] ?? 'N/A'); ?></div>
                </div>
                <!-- <div class="info-row">
                    <span class="info-label">Ref Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cost_sheet['referral_code'] ?? 'N/A'); ?></span>
                </div> -->
                <div class="info-row">
                    <span class="info-label">Source / Agent:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cost_sheet['source_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Travel Destination:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cost_sheet['destination_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lead Department:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cost_sheet['department_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Night/Day:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cost_sheet['night_day'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Travel Period:</span>
                    <span class="info-value"><?php echo date('d-m-Y', strtotime($cost_sheet['travel_start_date'])) ?? 'N/A'; ?> To <?php echo date('d-m-Y', strtotime($cost_sheet['travel_end_date'])) ?? 'N/A'; ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3>👥 PAX Details</h3>
                <div class="info-row">
                    <div class="info-label">Adults:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['adults_count'] ?? '0'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Children:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['children_count'] ?? '0'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Infants:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['infants_count'] ?? '0'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total PAX:</div>
                    <div class="info-value"><strong><?php echo $total_pax; ?></strong></div>
                </div>
                <?php if (!empty($cost_sheet['children_age_details'])): ?>
                <div class="info-row">
                    <div class="info-label">Children Ages:</div>
                    <div class="info-value"><?php echo htmlspecialchars($cost_sheet['children_age_details']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span style="background: <?php echo ($cost_sheet['confirmed'] ?? 0) ? '#28a745' : '#ffc107'; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 8px;">
                            <?php echo ($cost_sheet['confirmed'] ?? 0) ? 'CONFIRMED' : 'PENDING'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="services-section">
            <?php
            // Decode service data
            $selected_services = json_decode($cost_sheet['selected_services'] ?? '[]', true);
            $visa_data = json_decode($cost_sheet['visa_data'] ?? '[]', true);
            $accommodation_data = json_decode($cost_sheet['accommodation_data'] ?? '[]', true);
            $transportation_data = json_decode($cost_sheet['transportation_data'] ?? '[]', true);
            $cruise_data = json_decode($cost_sheet['cruise_data'] ?? '[]', true);
            $extras_data = json_decode($cost_sheet['extras_data'] ?? '[]', true);
            $agent_package_data = json_decode($cost_sheet['agent_package_data'] ?? '[]', true);
            $medical_tourism_data = json_decode($cost_sheet['medical_tourism_data'] ?? '[]', true);
            ?>

            <?php if(!empty($visa_data) && is_array($visa_data)): ?>
            <div class="service-card">
                <div class="service-header">✈️ VISA / FLIGHT BOOKING</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Sector</th>
                                <th>Supplier</th>
                                <th>Travel Date</th>
                                <th>Passengers</th>
                                <th>Rate/Person</th>
                                <th>ROE</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($visa_data as $visa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visa['sector'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($visa['supplier'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($visa['travel_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($visa['passengers'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($visa['rate_per_person'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($visa['roe'] ?? '1'); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($visa['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align: right;"><strong>TOTAL VISA/FLIGHT COST:</strong></td>
                                <td><strong><?php 
                                    $visa_total = 0;
                                    foreach($visa_data as $visa) $visa_total += floatval($visa['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($visa_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                 
                <?php if(!empty($cost_sheet['arrival_flight'])): ?>
                <div class="service-header">✈️  Travel Details</div>
                <div class="service-content">
                   
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Travel Period</th>
                                <th>Date</th>
                                <th>City</th>
                                <th>Flight</th>
                                <th>Nights/Days</th>
                                <th>Flight Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>ARRIVAL</strong></td>
                                <td> <?php echo $cost_sheet['arrival_date'] ? date('d-m-Y H:i', strtotime($cost_sheet['arrival_date'])) : 'N/A'; ?></td>
                                <td><?php echo $cost_sheet['arrival_flight'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['arrival_nights_days'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['arrival_connection'] ?? ''; ?></td>
                            </tr>
                            <tr id="arrival-connecting" style="display: <?php echo isset($cost_sheet['arrival_connecting_date']) ? 'table-row' : 'none'; ?>">
                                <td><strong>ARRIVAL (Connecting)</strong></td>
                                <td> <?php echo $cost_sheet['arrival_connecting_date'] ? date('d-m-Y H:i', strtotime($cost_sheet['arrival_connecting_date'])) : 'N/A'; ?></td>
                                <td><?php echo $cost_sheet['arrival_connecting_city'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['arrival_connecting_flight'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['arrival_connecting_nights_days'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['arrival_connecting_type'] ?? ''; ?></td>
                            </tr>
                            <tr>
                                <td><strong>DEPARTURE</strong></td>
                                <td> <?php echo $cost_sheet['departure_date'] ? date('d-m-Y H:i', strtotime($cost_sheet['departure_date'])) : 'N/A'; ?></td>
                                <td><?php echo $cost_sheet['departure_city'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_flight'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_nights_days'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_connection'] ?? ''; ?></td>
                            </tr>
                            <tr id="departure-connecting" style="display: <?php echo !empty($cost_sheet['departure_connecting_date']) ? 'table-row' : 'none'; ?>">
                                <td><strong>DEPARTURE (Connecting)</strong></td>
                                <td> <?php echo $cost_sheet['departure_connecting_date'] ? date('d-m-Y H:i', strtotime($cost_sheet['departure_connecting_date'])) : 'N/A'; ?></td>
                                <td><?php echo $cost_sheet['departure_connecting_city'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_connecting_flight'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_connecting_nights_days'] ?? ''; ?></td>
                                <td><?php echo $cost_sheet['departure_connecting_type'] ?? ''; ?></td>
                            </tr>                            
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if(!empty($accommodation_data) && is_array($accommodation_data) && !empty($accommodation_data[0]['destination'])): ?>
            <div class="service-card">
                <div class="service-header">🏨 ACCOMMODATION</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Hotel</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Room Type</th>
                                <th>Rooms</th>
                                <th>Rate</th>
                                <th>Extra Adult</th>
                                <th>Extra Child</th>
                                <th>Child No Bed</th>
                                <th>Nights</th>
                                <th>Meal Plan</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($accommodation_data as $accom): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($accom['destination'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($accom['hotel'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($accom['check_in'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($accom['check_out'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($accom['room_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($accom['rooms_no'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($accom['rooms_rate'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars(($accom['extra_adult_no'] ?? '0') . ' @ ' . $cost_sheet['currency'] . ($accom['extra_adult_rate'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars(($accom['extra_child_no'] ?? '0') . ' @ ' . $cost_sheet['currency'] . ($accom['extra_child_rate'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars(($accom['child_no_bed_no'] ?? '0') . ' @ ' . $cost_sheet['currency'] . ($accom['child_no_bed_rate'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($accom['nights'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($accom['meal_plan'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($accom['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="12" style="text-align: right;"><strong>TOTAL ACCOMMODATION COST:</strong></td>
                                <td><strong><?php 
                                    $accom_total = 0;
                                    foreach($accommodation_data as $accom) $accom_total += floatval($accom['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($accom_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($transportation_data) && is_array($transportation_data) && !empty($transportation_data[0]['supplier'])): ?>
            <div class="service-card">
                <div class="service-header">🚗 TRANSPORTATION</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Car Type</th>
                                <th>Daily Rent</th>
                                <th>Days</th>
                                <th>KM</th>
                                <th>Extra KM</th>
                                <th>Price/KM</th>
                                <th>Toll</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transportation_data as $trans): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trans['supplier'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($trans['car_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($trans['daily_rent'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($trans['days'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($trans['km'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($trans['extra_km'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($trans['price_per_km'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($trans['toll'] ?? '0')); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($trans['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" style="text-align: right;"><strong>TOTAL TRANSPORTATION COST:</strong></td>
                                <td><strong><?php 
                                    $trans_total = 0;
                                    foreach($transportation_data as $trans) $trans_total += floatval($trans['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($trans_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($cruise_data) && is_array($cruise_data) && !empty($cruise_data[0]['supplier'])): ?>
            <div class="service-card">
                <div class="service-header">🚢 CRUISE HIRE</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Boat Type</th>
                                <th>Cruise Type</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Rate</th>
                                <th>Extra</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cruise_data as $cruise): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cruise['supplier'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cruise['boat_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cruise['cruise_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cruise['check_in'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cruise['check_out'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($cruise['rate'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($cruise['extra'] ?? '0')); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($cruise['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" style="text-align: right;"><strong>TOTAL CRUISE COST:</strong></td>
                                <td><strong><?php 
                                    $cruise_total = 0;
                                    foreach($cruise_data as $cruise) $cruise_total += floatval($cruise['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($cruise_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($agent_package_data) && is_array($agent_package_data) && !empty($agent_package_data[0]['destination'])): ?>
            <div class="service-card">
                <div class="service-header">💼 AGENT PACKAGE SERVICE</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Agent/Supplier</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Adults</th>
                                <th>Adult Price</th>
                                <th>Children</th>
                                <th>Child Price</th>
                                <th>Infants</th>
                                <th>Infant Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($agent_package_data as $package): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($package['destination'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($package['agent_supplier'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($package['start_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($package['end_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($package['adult_count'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($package['adult_price'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($package['child_count'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($package['child_price'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($package['infant_count'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($package['infant_price'] ?? '0')); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($package['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="10" style="text-align: right;"><strong>TOTAL AGENT PACKAGE COST:</strong></td>
                                <td><strong><?php 
                                    $package_total = 0;
                                    foreach($agent_package_data as $package) $package_total += floatval($package['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($package_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($medical_tourism_data) && is_array($medical_tourism_data) && !empty($medical_tourism_data[0]['place'])): ?>
            <div class="service-card">
                <div class="service-header">🏥 MEDICAL TOURISM</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Place</th>
                                <th>Treatment Date</th>
                                <th>Hospital</th>
                                <th>Treatment Type</th>
                                <th>OP/IP</th>
                                <th>Net</th>
                                <th>TDS</th>
                                <th>Other Expenses</th>
                                <th>GST</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($medical_tourism_data as $medical): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medical['place'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($medical['treatment_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($medical['hospital'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($medical['treatment_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($medical['op_ip'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($medical['net'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($medical['tds'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($medical['other_expenses'] ?? '0')); ?></td>
                                <td>18%</td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($medical['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" style="text-align: right;"><strong>TOTAL MEDICAL TOURISM COST:</strong></td>
                                <td><strong><?php 
                                    $medical_total = 0;
                                    foreach($medical_tourism_data as $medical) $medical_total += floatval($medical['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($medical_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($extras_data) && is_array($extras_data) && !empty($extras_data[0]['supplier'])): ?>
            <div class="service-card">
                <div class="service-header">➕ EXTRAS/MISCELLANEOUS</div>
                <div class="service-content">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Service Type</th>
                                <th>Amount</th>
                                <th>Extras</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($extras_data as $extra): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($extra['supplier'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($extra['service_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($extra['amount'] ?? '0')); ?></td>
                                <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($extra['extras'] ?? '0')); ?></td>
                                <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . ($extra['total'] ?? '0')); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right;"><strong>TOTAL EXTRAS COST:</strong></td>
                                <td><strong><?php 
                                    $extras_total = 0;
                                    foreach($extras_data as $extra) $extras_total += floatval($extra['total'] ?? 0);
                                    echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($extras_total, 2));
                                ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-card">
                <h3>💰 Cost Summary</h3>
                <table class="cost-table">
                    <tr>
                        <td>Total Expense:</td>
                        <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($cost_sheet['total_expense'], 2)); ?></td>
                    </tr>
                    <tr>
                        <td>Mark Up (<?php echo number_format($cost_sheet['markup_percentage'], 2); ?>%):</td>
                        <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($cost_sheet['markup_amount'], 2)); ?></td>
                    </tr>
                    <tr>
                        <td>Service Tax (<?php echo number_format($cost_sheet['tax_percentage'], 2); ?>%):</td>
                        <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($cost_sheet['tax_amount'], 2)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Package Cost:</strong></td>
                        <td><strong><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($cost_sheet['package_cost'], 2)); ?></strong></td>
                    </tr>
                </table>
            </div>

            <div class="summary-card">
                <h3>💳 Payment Summary</h3>
                <?php
                // Calculate payment summary
                $total_paid = 0;
                $payment_history = [];
                
                // Check if payments table exists and get payment data
                $check_payments_table = "SHOW TABLES LIKE 'payments'";
                $payments_table_exists = mysqli_query($conn, $check_payments_table);
                if(mysqli_num_rows($payments_table_exists) > 0) {
                    $payments_sql = "SELECT * FROM payments WHERE cost_file_id = ? ORDER BY payment_date DESC";
                    $payments_stmt = mysqli_prepare($conn, $payments_sql);
                    mysqli_stmt_bind_param($payments_stmt, "i", $cost_sheet['id']);
                    mysqli_stmt_execute($payments_stmt);
                    $payments_result = mysqli_stmt_get_result($payments_stmt);
                    while($payment = mysqli_fetch_assoc($payments_result)) {
                        $payment_history[] = $payment;
                        $total_paid += $payment['payment_amount'];
                    }
                    mysqli_stmt_close($payments_stmt);
                }
                
                $balance = $cost_sheet['package_cost'] - $total_paid;
                ?>
                
                <?php if(!empty($payment_history)): ?>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bank</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_history as $payment): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_bank']); ?></td>
                            <td><?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($payment['payment_amount'], 2)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #666; font-style: italic;">No payment records found</p>
                <?php endif; ?>
                
                <div class="balance-highlight <?php echo $balance > 0 ? 'pending' : ''; ?>">
                    <div style="font-size: 11px; margin-bottom: 5px;">BALANCE AMOUNT</div>
                    <div style="font-size: 16px; font-weight: bold;">
                        <?php echo htmlspecialchars($cost_sheet['currency'] . ' ' . number_format($balance, 2)); ?>
                    </div>
                    <div style="font-size: 9px; margin-top: 5px;">
                        <?php echo $balance <= 0 ? '✅ FULLY PAID' : '⚠️ PAYMENT PENDING'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="company-info">
                    <div>GLEESIRE TRAVEL & TOURISM</div>
                    <div style="font-size: 10px; margin-top: 5px;">Thank you for choosing us for your travel needs!</div>
                </div>
                <div class="generation-info">
                    <div>Generated on: <?php echo date('d-m-Y H:i:s'); ?></div>
                    <div>This is a computer-generated document</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>