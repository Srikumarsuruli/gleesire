<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';

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
        $sql = "INSERT INTO visa_ticket_booking (cost_sheet_number, booking_date, checkin_date, checkout_date, destination, agent_type, supplier, supplier_name, contact_number, availability_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssss", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $agent_type, $supplier, $supplier_name, $contact_number, $availability_status, $booking_status);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Visa & ticket booking added successfully!";
                // Clear form data
                $cost_sheet_number = $booking_date = $checkin_date = $checkout_date = $destination = $supplier = $supplier_name = $contact_number = '';
                $agent_type = 'Domestic';
                $availability_status = 'Available';
                $booking_status = 'Booking Confirmed';
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
        <h4 class="text-blue h4">Add Visa & Air Ticket Booking</h4>
        <p class="mb-0">Fill in the details below to add a new visa & ticket booking</p>
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
                        <input type="text" name="cost_sheet_number" class="form-control" value="<?php echo isset($cost_sheet_number) ? htmlspecialchars($cost_sheet_number) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" value="<?php echo isset($booking_date) ? $booking_date : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Checkin Date</label>
                        <input type="date" name="checkin_date" class="form-control" value="<?php echo isset($checkin_date) ? $checkin_date : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Check Out Date</label>
                        <input type="date" name="checkout_date" class="form-control" value="<?php echo isset($checkout_date) ? $checkout_date : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo isset($destination) ? htmlspecialchars($destination) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Agent Type</label>
                        <select name="agent_type" class="form-control">
                            <option value="Domestic" <?php echo (isset($agent_type) && $agent_type == 'Domestic') ? 'selected' : ''; ?>>Domestic</option>
                            <option value="Outbound" <?php echo (isset($agent_type) && $agent_type == 'Outbound') ? 'selected' : ''; ?>>Outbound</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo isset($supplier) ? htmlspecialchars($supplier) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name of the Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" value="<?php echo isset($supplier_name) ? htmlspecialchars($supplier_name) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status" class="form-control">
                            <option value="Available" <?php echo (isset($availability_status) && $availability_status == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (isset($availability_status) && $availability_status == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Booking Status</label>
                        <select name="booking_status" class="form-control">
                            <option value="Booking Confirmed" <?php echo (isset($booking_status) && $booking_status == 'Booking Confirmed') ? 'selected' : ''; ?>>Booking Confirmed</option>
                            <option value="Amendment" <?php echo (isset($booking_status) && $booking_status == 'Amendment') ? 'selected' : ''; ?>>Amendment</option>
                            <option value="Cancelation" <?php echo (isset($booking_status) && $booking_status == 'Cancelation') ? 'selected' : ''; ?>>Cancelation</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Visa & Ticket Booking</button>
                <a href="visa_ticket_booking.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>