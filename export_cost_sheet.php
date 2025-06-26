<?php
// Include database connection
require_once "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get enquiry ID from URL
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($enquiry_id <= 0) {
    die("Invalid enquiry ID.");
}

// Get enquiry details
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.adults_count, 
        cl.children_count, cl.infants_count,
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
    die("Enquiry not found.");
}

$enquiry = mysqli_fetch_assoc($result);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Cost_Sheet_' . $enquiry['enquiry_number'] . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000000;
            padding: 5px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="6" class="header">COST SHEET</td>
        </tr>
        <tr>
            <td colspan="2">Customer Name: <?php echo $enquiry['customer_name']; ?></td>
            <td colspan="2">Enquiry Number: <?php echo $enquiry['enquiry_number']; ?></td>
            <td colspan="2">File Manager: <?php echo $enquiry['file_manager_name'] ?? 'N/A'; ?></td>
        </tr>
        <tr>
            <td colspan="2">Destination: <?php echo $enquiry['destination_name'] ?? 'N/A'; ?></td>
            <td colspan="2">Travel Period: 
                <?php 
                if($enquiry['travel_start_date'] && $enquiry['travel_end_date']) {
                    echo date('d-m-Y', strtotime($enquiry['travel_start_date'])) . ' to ' . date('d-m-Y', strtotime($enquiry['travel_end_date']));
                } else {
                    echo 'N/A';
                }
                ?>
            </td>
            <td colspan="2">Nights/Days: N/A</td>
        </tr>
        <tr>
            <td colspan="2">Adults: <?php echo $enquiry['adults_count'] ?? '0'; ?></td>
            <td colspan="2">Children: <?php echo $enquiry['children_count'] ?? '0'; ?></td>
            <td colspan="2">Infants: <?php echo $enquiry['infants_count'] ?? '0'; ?></td>
        </tr>
        
        <!-- SERVICES -->
        <tr>
            <td colspan="6" class="header">SERVICES</td>
        </tr>
        
        <!-- VISA / FLIGHT BOOKING -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">VISA / FLIGHT BOOKING</td>
        </tr>
        <tr>
            <th>TRAVEL PERIOD</th>
            <th>DATE</th>
            <th>CITY</th>
            <th>FLIGHT</th>
            <th>NIGHTS/DAYS</th>
            <th>Flight</th>
        </tr>
        <tr>
            <td>ARRIVAL</td>
            <td><?php echo $enquiry['travel_start_date'] ? date('d-m-Y', strtotime($enquiry['travel_start_date'])) : ''; ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>DEPARTURE</td>
            <td><?php echo $enquiry['travel_end_date'] ? date('d-m-Y', strtotime($enquiry['travel_end_date'])) : ''; ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        
        <!-- ACCOMMODATION -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">ACCOMMODATION</td>
        </tr>
        <tr>
            <th>CITY</th>
            <th>HOTEL NAME</th>
            <th>ROOM TYPE</th>
            <th>MEAL PLAN</th>
            <th>CHECK-IN</th>
            <th>CHECK-OUT</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        
        <!-- Add other service sections as needed -->
        
        <!-- TOUR PACKAGE -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">TOUR PACKAGE</td>
        </tr>
        
        <!-- CRUISE HIRE -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">CRUISE HIRE</td>
        </tr>
        
        <!-- TRANSPORTATION -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">TRANSPORTATION</td>
        </tr>
        
        <!-- EXTRAS/MISCELLANEOUS -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">EXTRAS/MISCELLANEOUS</td>
        </tr>
        
        <!-- TRAVEL INSURANCE -->
        <tr>
            <td colspan="6" style="background-color: #D9E1F2;">TRAVEL INSURANCE</td>
        </tr>
        
        <!-- COST SUMMARY -->
        <tr>
            <td colspan="6" class="header">COST SUMMARY</td>
        </tr>
        <tr>
            <th>DESCRIPTION</th>
            <th>COST PER PERSON</th>
            <th>NO. OF PERSONS</th>
            <th>TOTAL COST</th>
            <th colspan="2">REMARKS</th>
        </tr>
        <tr>
            <td>VISA / FLIGHT BOOKING</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>ACCOMMODATION</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>TOUR PACKAGE</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>CRUISE HIRE</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>TRANSPORTATION</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>EXTRAS/MISCELLANEOUS</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>TRAVEL INSURANCE</td>
            <td></td>
            <td><?php echo $enquiry['adults_count'] + $enquiry['children_count']; ?></td>
            <td></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL COST</td>
            <td></td>
            <td colspan="2"></td>
        </tr>
    </table>
</body>
</html>