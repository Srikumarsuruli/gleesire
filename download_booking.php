<?php
// Include database connection
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('booking_confirmed')) {
    header("location: index.php");
    exit;
}

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: booking_confirmed.php");
    exit;
}

$booking_id = $_GET['id'];

// Get booking details
$booking_sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
                s.name as source_name, ac.name as campaign_name,
                cl.*, dest.name as destination_name, fm.full_name as file_manager_name 
                FROM enquiries e 
                JOIN users u ON e.attended_by = u.id 
                JOIN departments d ON e.department_id = d.id 
                JOIN sources s ON e.source_id = s.id 
                LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
                JOIN converted_leads cl ON e.id = cl.enquiry_id
                LEFT JOIN destinations dest ON cl.destination_id = dest.id
                LEFT JOIN users fm ON cl.file_manager_id = fm.id
                WHERE e.id = ?";
$booking_stmt = mysqli_prepare($conn, $booking_sql);
mysqli_stmt_bind_param($booking_stmt, "i", $booking_id);
mysqli_stmt_execute($booking_stmt);
$booking_result = mysqli_stmt_get_result($booking_stmt);

if(mysqli_num_rows($booking_result) == 0) {
    header("location: booking_confirmed.php");
    exit;
}

$booking = mysqli_fetch_assoc($booking_result);

// Include TCPDF library
require_once('assets/tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Lead Management System');
$pdf->SetAuthor('Lead Management System');
$pdf->SetTitle('Booking Details - ' . $booking['enquiry_number']);
$pdf->SetSubject('Booking Details');

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Title
$pdf->Cell(0, 10, 'Booking Details', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'File Number: ' . $booking['enquiry_number'], 0, 1, 'C');
$pdf->Ln(5);

// Create content
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Customer Information', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Customer details table
$html = '<table cellspacing="0" cellpadding="5" border="0">
    <tr>
        <td width="30%"><strong>Customer Name:</strong></td>
        <td width="70%">' . $booking['customer_name'] . '</td>
    </tr>
    <tr>
        <td><strong>Mobile Number:</strong></td>
        <td>' . $booking['mobile_number'] . '</td>
    </tr>
    <tr>
        <td><strong>Email:</strong></td>
        <td>' . ($booking['email'] ? $booking['email'] : '-') . '</td>
    </tr>
    <tr>
        <td><strong>Customer Location:</strong></td>
        <td>' . ($booking['customer_location'] ? $booking['customer_location'] : '-') . '</td>
    </tr>
    <tr>
        <td><strong>Secondary Contact:</strong></td>
        <td>' . ($booking['secondary_contact'] ? $booking['secondary_contact'] : '-') . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

// Booking details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Booking Details', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Format travel period
$travel_period = '-';
if($booking['travel_start_date'] && $booking['travel_end_date']) {
    $travel_period = date('d-m-Y', strtotime($booking['travel_start_date'])) . ' to ' . date('d-m-Y', strtotime($booking['travel_end_date']));
}

// Format travelers
$travelers = array();
if($booking['adults_count'] > 0) $travelers[] = $booking['adults_count'] . ' Adults';
if($booking['children_count'] > 0) $travelers[] = $booking['children_count'] . ' Children';
if($booking['infants_count'] > 0) $travelers[] = $booking['infants_count'] . ' Infants';
$travelers_text = !empty($travelers) ? implode(', ', $travelers) : '-';

$html = '<table cellspacing="0" cellpadding="5" border="0">
    <tr>
        <td width="30%"><strong>Booking Date:</strong></td>
        <td width="70%">' . date('d-m-Y', strtotime($booking['created_at'])) . '</td>
    </tr>
    <tr>
        <td><strong>Lead Number:</strong></td>
        <td>' . $booking['lead_number'] . '</td>
    </tr>
    <tr>
        <td><strong>Destination:</strong></td>
        <td>' . ($booking['destination_name'] ? $booking['destination_name'] : '-') . '</td>
    </tr>
    <tr>
        <td><strong>Travel Month:</strong></td>
        <td>' . ($booking['travel_month'] ? date('F Y', strtotime($booking['travel_month'])) : '-') . '</td>
    </tr>
    <tr>
        <td><strong>Travel Period:</strong></td>
        <td>' . $travel_period . '</td>
    </tr>
    <tr>
        <td><strong>Travelers:</strong></td>
        <td>' . $travelers_text . '</td>
    </tr>
    <tr>
        <td><strong>Customer Available Timing:</strong></td>
        <td>' . ($booking['customer_available_timing'] ? $booking['customer_available_timing'] : '-') . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

// Source information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Source Information', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$html = '<table cellspacing="0" cellpadding="5" border="0">
    <tr>
        <td width="30%"><strong>Department:</strong></td>
        <td width="70%">' . $booking['department_name'] . '</td>
    </tr>
    <tr>
        <td><strong>Source:</strong></td>
        <td>' . $booking['source_name'] . '</td>
    </tr>
    <tr>
        <td><strong>Ad Campaign:</strong></td>
        <td>' . ($booking['campaign_name'] ? $booking['campaign_name'] : '-') . '</td>
    </tr>
    <tr>
        <td><strong>Attended By:</strong></td>
        <td>' . $booking['attended_by_name'] . '</td>
    </tr>
    <tr>
        <td><strong>File Manager:</strong></td>
        <td>' . ($booking['file_manager_name'] ? $booking['file_manager_name'] : '-') . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

// Additional details
if(!empty($booking['other_details'])) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Additional Details', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, $booking['other_details'], 0, 'L');
}

// Output the PDF
$pdf->Output('booking_' . $booking['enquiry_number'] . '.pdf', 'D');
exit;
?>