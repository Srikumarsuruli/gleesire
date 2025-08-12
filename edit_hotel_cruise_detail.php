<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$cruise_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: hotel_cruise_details.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM hotel_cruise_details WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $cruise_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: hotel_cruise_details.php");
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
        $sql = "UPDATE hotel_cruise_details SET cost_sheet_number = ?, booking_date = ?, checkin_date = ?, checkout_date = ?, destination = ?, cruise_details = ?, name = ?, contact_number = ?, department = ?, adult_price = ?, kids_price = ?, kids_price_available = ?, cancelation_availability = ?, booking_status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssddsssi", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $cruise_details, $name, $contact_number, $department, $adult_price, $kids_price, $kids_price_available, $cancelation_availability, $booking_status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Hotel cruise detail updated successfully!";
                // Refresh data
                $cruise_detail['cost_sheet_number'] = $cost_sheet_number;
                $cruise_detail['booking_date'] = $booking_date;
                $cruise_detail['checkin_date'] = $checkin_date;
                $cruise_detail['checkout_date'] = $checkout_date;
                $cruise_detail['destination'] = $destination;
                $cruise_detail['cruise_details'] = $cruise_details;
                $cruise_detail['name'] = $name;
                $cruise_detail['contact_number'] = $contact_number;
                $cruise_detail['department'] = $department;
                $cruise_detail['adult_price'] = $adult_price;
                $cruise_detail['kids_price'] = $kids_price;
                $cruise_detail['kids_price_available'] = $kids_price_available;
                $cruise_detail['cancelation_availability'] = $cancelation_availability;
                $cruise_detail['booking_status'] = $booking_status;
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
        <h4 class="text-blue h4">Edit Hotel/Resort Cruise Detail</h4>
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
                        <input type="text" name="cost_sheet_number" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['cost_sheet_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" value="<?php echo $cruise_detail['booking_date'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Checkin Date</label>
                        <input type="date" name="checkin_date" class="form-control" value="<?php echo $cruise_detail['checkin_date'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Check Out Date</label>
                        <input type="date" name="checkout_date" class="form-control" value="<?php echo $cruise_detail['checkout_date'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Cruise Details <span class="text-danger">*</span></label>
                        <input type="text" name="cruise_details" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['cruise_details']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['contact_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($cruise_detail['department']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adult Price (₹)</label>
                        <input type="number" step="0.01" name="adult_price" class="form-control" value="<?php echo $cruise_detail['adult_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price (₹)</label>
                        <input type="number" step="0.01" name="kids_price" class="form-control" value="<?php echo $cruise_detail['kids_price']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price Available</label>
                        <select name="kids_price_available" class="form-control">
                            <option value="Available" <?php echo (($cruise_detail['kids_price_available'] ?? 'Available') == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (($cruise_detail['kids_price_available'] ?? 'Available') == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Cancelation Availability Status</label>
                        <select name="cancelation_availability" class="form-control">
                            <option value="Available" <?php echo (($cruise_detail['cancelation_availability'] ?? 'Available') == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo (($cruise_detail['cancelation_availability'] ?? 'Available') == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Booking Status</label>
                        <select name="booking_status" class="form-control">
                            <option value="Booking Confirmed" <?php echo (($cruise_detail['booking_status'] ?? 'Booking Confirmed') == 'Booking Confirmed') ? 'selected' : ''; ?>>Booking Confirmed</option>
                            <option value="Amendment" <?php echo (($cruise_detail['booking_status'] ?? 'Booking Confirmed') == 'Amendment') ? 'selected' : ''; ?>>Amendment</option>
                            <option value="Cancelation" <?php echo (($cruise_detail['booking_status'] ?? 'Booking Confirmed') == 'Cancelation') ? 'selected' : ''; ?>>Cancelation</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Cruise Detail</button>
                <a href="hotel_cruise_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>