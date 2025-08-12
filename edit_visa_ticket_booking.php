<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$booking_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: visa_ticket_booking.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM visa_ticket_booking WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $booking_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: visa_ticket_booking.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cost_sheet_number = trim($_POST['cost_sheet_number']);
    $booking_date = $_POST['booking_date'];
    $checkin_date = $_POST['checkin_date'];
    $checkout_date = $_POST['checkout_date'];
    $destination = trim($_POST['destination']);
    $agent_type = $_POST['agent_type'];
    $supplier = trim($_POST['supplier']);
    $supplier_name = trim($_POST['supplier_name']);
    $contact_number = trim($_POST['contact_number']);
    $availability_status = $_POST['availability_status'];
    $booking_status = $_POST['booking_status'];
    
    if (empty($destination) || empty($supplier) || empty($supplier_name) || empty($contact_number)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE visa_ticket_booking SET cost_sheet_number = ?, booking_date = ?, checkin_date = ?, checkout_date = ?, destination = ?, agent_type = ?, supplier = ?, supplier_name = ?, contact_number = ?, availability_status = ?, booking_status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssssi", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $agent_type, $supplier, $supplier_name, $contact_number, $availability_status, $booking_status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Visa & ticket booking updated successfully!";
                // Refresh data
                $booking_detail['cost_sheet_number'] = $cost_sheet_number;
                $booking_detail['booking_date'] = $booking_date;
                $booking_detail['checkin_date'] = $checkin_date;
                $booking_detail['checkout_date'] = $checkout_date;
                $booking_detail['destination'] = $destination;
                $booking_detail['agent_type'] = $agent_type;
                $booking_detail['supplier'] = $supplier;
                $booking_detail['supplier_name'] = $supplier_name;
                $booking_detail['contact_number'] = $contact_number;
                $booking_detail['availability_status'] = $availability_status;
                $booking_detail['booking_status'] = $booking_status;
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Edit Visa & Air Ticket Booking</h4>
    </div>
    <div class="pd-20">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Cost Sheet Number</label>
                        <input type="text" name="cost_sheet_number" class="form-control" value="<?php echo htmlspecialchars($booking_detail['cost_sheet_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" value="<?php echo $booking_detail['booking_date'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Checkin Date</label>
                        <input type="date" name="checkin_date" class="form-control" value="<?php echo $booking_detail['checkin_date'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Check Out Date</label>
                        <input type="date" name="checkout_date" class="form-control" value="<?php echo $booking_detail['checkout_date'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($booking_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Agent Type</label>
                        <select name="agent_type" class="form-control">
                            <option value="Domestic" <?php echo (($booking_detail['agent_type'] ?? 'Domestic') == 'Domestic') ? 'selected' : ''; ?>>Domestic</option>
                            <option value="Outbound" <?php echo (($booking_detail['agent_type'] ?? 'Domestic') == 'Outbound') ? 'selected' : ''; ?>>Outbound</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($booking_detail['supplier']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name of the Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" value="<?php echo htmlspecialchars($booking_detail['supplier_name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($booking_detail['contact_number']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status" class="form-control">
                            <option value="Available" <?php echo (($booking_detail['availability_status'] ?? 'Available') == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (($booking_detail['availability_status'] ?? 'Available') == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Booking Status</label>
                        <select name="booking_status" class="form-control">
                            <option value="Booking Confirmed" <?php echo (($booking_detail['booking_status'] ?? 'Booking Confirmed') == 'Booking Confirmed') ? 'selected' : ''; ?>>Booking Confirmed</option>
                            <option value="Amendment" <?php echo (($booking_detail['booking_status'] ?? 'Booking Confirmed') == 'Amendment') ? 'selected' : ''; ?>>Amendment</option>
                            <option value="Cancelation" <?php echo (($booking_detail['booking_status'] ?? 'Booking Confirmed') == 'Cancelation') ? 'selected' : ''; ?>>Cancelation</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Visa & Ticket Booking</button>
                <a href="visa_ticket_booking.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>