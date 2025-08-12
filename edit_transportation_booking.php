<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$booking_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: transportation_booking.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM transportation_booking WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $booking_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: transportation_booking.php");
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
        $sql = "UPDATE transportation_booking SET cost_sheet_number = ?, booking_date = ?, checkin_date = ?, checkout_date = ?, destination = ?, company_name = ?, contact_person = ?, mobile = ?, vehicle = ?, daily_rent = ?, rate_per_km = ?, availability_status = ?, booking_status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssddssi", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $company_name, $contact_person, $mobile, $vehicle, $daily_rent, $rate_per_km, $availability_status, $booking_status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Transportation booking updated successfully!";
                // Refresh data
                $booking_detail['cost_sheet_number'] = $cost_sheet_number;
                $booking_detail['booking_date'] = $booking_date;
                $booking_detail['checkin_date'] = $checkin_date;
                $booking_detail['checkout_date'] = $checkout_date;
                $booking_detail['destination'] = $destination;
                $booking_detail['company_name'] = $company_name;
                $booking_detail['contact_person'] = $contact_person;
                $booking_detail['mobile'] = $mobile;
                $booking_detail['vehicle'] = $vehicle;
                $booking_detail['daily_rent'] = $daily_rent;
                $booking_detail['rate_per_km'] = $rate_per_km;
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
        <h4 class="text-blue h4">Edit Transportation Booking</h4>
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
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($booking_detail['company_name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($booking_detail['contact_person']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($booking_detail['mobile']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle" class="form-control" value="<?php echo htmlspecialchars($booking_detail['vehicle']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Daily Rent (₹)</label>
                        <input type="number" step="0.01" name="daily_rent" class="form-control" value="<?php echo $booking_detail['daily_rent']; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Rate per KM (₹)</label>
                        <input type="number" step="0.01" name="rate_per_km" class="form-control" value="<?php echo $booking_detail['rate_per_km']; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status" class="form-control">
                            <option value="Available" <?php echo (($booking_detail['availability_status'] ?? 'Available') == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (($booking_detail['availability_status'] ?? 'Available') == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
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
                <button type="submit" class="btn btn-primary">Update Transportation Booking</button>
                <a href="transportation_booking.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>