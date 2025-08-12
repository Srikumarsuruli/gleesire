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
    $cruise_details = trim($_POST['cruise_details']);
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $adult_price = floatval($_POST['adult_price']);
    $kids_price = floatval($_POST['kids_price']);
    $kids_price_available = $_POST['kids_price_available'];
    $cancelation_availability = $_POST['cancelation_availability'];
    $booking_status = $_POST['booking_status'];
    
    if (empty($destination) || empty($cruise_details) || empty($name) || empty($contact_number) || empty($department)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO hotel_cruise_details (cost_sheet_number, booking_date, checkin_date, checkout_date, destination, cruise_details, name, contact_number, department, adult_price, kids_price, kids_price_available, cancelation_availability, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssddsss", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $cruise_details, $name, $contact_number, $department, $adult_price, $kids_price, $kids_price_available, $cancelation_availability, $booking_status);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Hotel cruise detail added successfully!";
                // Clear form data
                $cost_sheet_number = $booking_date = $checkin_date = $checkout_date = $destination = $cruise_details = $name = $contact_number = $department = '';
                $adult_price = $kids_price = 0;
                $kids_price_available = $cancelation_availability = 'Available';
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
        <h4 class="text-blue h4">Add Hotel/Resort Cruise Detail</h4>
        <p class="mb-0">Fill in the details below to add a new cruise booking</p>
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
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Cruise Details <span class="text-danger">*</span></label>
                        <input type="text" name="cruise_details" class="form-control" value="<?php echo isset($cruise_details) ? htmlspecialchars($cruise_details) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo isset($department) ? htmlspecialchars($department) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adult Price (₹)</label>
                        <input type="number" step="0.01" name="adult_price" class="form-control" value="<?php echo isset($adult_price) ? $adult_price : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price (₹)</label>
                        <input type="number" step="0.01" name="kids_price" class="form-control" value="<?php echo isset($kids_price) ? $kids_price : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price Available</label>
                        <select name="kids_price_available" class="form-control">
                            <option value="Available" <?php echo (isset($kids_price_available) && $kids_price_available == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (isset($kids_price_available) && $kids_price_available == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Cancelation Availability Status</label>
                        <select name="cancelation_availability" class="form-control">
                            <option value="Available" <?php echo (isset($cancelation_availability) && $cancelation_availability == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (isset($cancelation_availability) && $cancelation_availability == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
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
                <button type="submit" class="btn btn-primary">Add Cruise Detail</button>
                <a href="hotel_cruise_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>