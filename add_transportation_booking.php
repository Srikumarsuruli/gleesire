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
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $mobile = trim($_POST['mobile']);
    $vehicle = trim($_POST['vehicle']);
    $daily_rent = floatval($_POST['daily_rent']);
    $rate_per_km = floatval($_POST['rate_per_km']);
    $availability_status = $_POST['availability_status'];
    $booking_status = $_POST['booking_status'];
    
    if (empty($destination) || empty($company_name) || empty($contact_person) || empty($mobile) || empty($vehicle)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO transportation_booking (cost_sheet_number, booking_date, checkin_date, checkout_date, destination, company_name, contact_person, mobile, vehicle, daily_rent, rate_per_km, availability_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssddss", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $company_name, $contact_person, $mobile, $vehicle, $daily_rent, $rate_per_km, $availability_status, $booking_status);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Transportation booking added successfully!";
                // Clear form data
                $cost_sheet_number = $booking_date = $checkin_date = $checkout_date = $destination = $company_name = $contact_person = $mobile = $vehicle = '';
                $daily_rent = $rate_per_km = 0;
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
        <h4 class="text-blue h4">Add Transportation Booking</h4>
        <p class="mb-0">Fill in the details below to add a new transportation booking</p>
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
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo isset($company_name) ? htmlspecialchars($company_name) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo isset($contact_person) ? htmlspecialchars($contact_person) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo isset($mobile) ? htmlspecialchars($mobile) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle" class="form-control" value="<?php echo isset($vehicle) ? htmlspecialchars($vehicle) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Daily Rent (₹)</label>
                        <input type="number" step="0.01" name="daily_rent" class="form-control" value="<?php echo isset($daily_rent) ? $daily_rent : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Rate per KM (₹)</label>
                        <input type="number" step="0.01" name="rate_per_km" class="form-control" value="<?php echo isset($rate_per_km) ? $rate_per_km : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status" class="form-control">
                            <option value="Available" <?php echo (isset($availability_status) && $availability_status == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (isset($availability_status) && $availability_status == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
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
                <button type="submit" class="btn btn-primary">Add Transportation Booking</button>
                <a href="transportation_booking.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>