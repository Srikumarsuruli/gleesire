<?php
require_once "includes/header.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cost_sheet_number = isset($_POST["cost_sheet_number"]) ? trim($_POST["cost_sheet_number"]) : '';
    $booking_date = !empty($_POST["booking_date"]) ? $_POST["booking_date"] : null;
    $checkin_date = !empty($_POST["checkin_date"]) ? $_POST["checkin_date"] : null;
    $checkout_date = !empty($_POST["checkout_date"]) ? $_POST["checkout_date"] : null;
    $destination = isset($_POST["destination"]) ? trim($_POST["destination"]) : '';
    $hotel_name = isset($_POST["hotel_name"]) ? trim($_POST["hotel_name"]) : '';
    $room_category = isset($_POST["room_category"]) ? trim($_POST["room_category"]) : '';
    $cp = isset($_POST["cp"]) && $_POST["cp"] !== '' ? (float)$_POST["cp"] : 0.00;
    $map_price = isset($_POST["map_price"]) && $_POST["map_price"] !== '' ? (float)$_POST["map_price"] : 0.00;
    $eb_adult_cp = isset($_POST["eb_adult_cp"]) && $_POST["eb_adult_cp"] !== '' ? (float)$_POST["eb_adult_cp"] : 0.00;
    $eb_adult_map = isset($_POST["eb_adult_map"]) && $_POST["eb_adult_map"] !== '' ? (float)$_POST["eb_adult_map"] : 0.00;
    $child_with_bed_cp = isset($_POST["child_with_bed_cp"]) && $_POST["child_with_bed_cp"] !== '' ? (float)$_POST["child_with_bed_cp"] : 0.00;
    $child_with_bed_map = isset($_POST["child_with_bed_map"]) && $_POST["child_with_bed_map"] !== '' ? (float)$_POST["child_with_bed_map"] : 0.00;
    $child_without_bed_cp = isset($_POST["child_without_bed_cp"]) && $_POST["child_without_bed_cp"] !== '' ? (float)$_POST["child_without_bed_cp"] : 0.00;
    $child_without_bed_map = isset($_POST["child_without_bed_map"]) && $_POST["child_without_bed_map"] !== '' ? (float)$_POST["child_without_bed_map"] : 0.00;
    $xmas_newyear_charges = isset($_POST["xmas_newyear_charges"]) && $_POST["xmas_newyear_charges"] !== '' ? (float)$_POST["xmas_newyear_charges"] : 0.00;
    $meal_type = isset($_POST["meal_type"]) ? trim($_POST["meal_type"]) : '';
    $adult_meal_charges = isset($_POST["adult_meal_charges"]) && $_POST["adult_meal_charges"] !== '' ? (float)$_POST["adult_meal_charges"] : 0.00;
    $kids_meal_charges = isset($_POST["kids_meal_charges"]) && $_POST["kids_meal_charges"] !== '' ? (float)$_POST["kids_meal_charges"] : 0.00;
    $availability_status = isset($_POST["availability_status"]) ? $_POST["availability_status"] : 'Available';
    $booking_status = isset($_POST["booking_status"]) ? $_POST["booking_status"] : 'Booking Confirmed';
    $status = isset($_POST["status"]) ? $_POST["status"] : 'Active';

    $sql = "INSERT INTO hotel_resorts (cost_sheet_number, booking_date, checkin_date, checkout_date, destination, hotel_name, room_category, cp, map_price, meal_type, availability_status, booking_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssssssddssss", $cost_sheet_number, $booking_date, $checkin_date, $checkout_date, $destination, $hotel_name, $room_category, $cp, $map_price, $meal_type, $availability_status, $booking_status, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Hotel/Resort added successfully'); window.location.href='hotel_resorts.php';</script>";
        } else {
            $message = "Error: " . mysqli_stmt_error($stmt) . " - " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Add Hotel/Resort</h4>
    </div>
    <div class="pd-20">
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Cost Sheet Number</label>
                        <input type="text" class="form-control" name="cost_sheet_number" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Booking Date</label>
                        <input type="date" class="form-control" name="booking_date">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <input type="date" class="form-control" name="checkin_date">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <input type="date" class="form-control" name="checkout_date">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Destination</label>
                        <input type="text" class="form-control" name="destination" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Hotel Name</label>
                        <input type="text" class="form-control" name="hotel_name" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Room Category</label>
                        <input type="text" class="form-control" name="room_category">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Meal Type</label>
                        <input type="text" class="form-control" name="meal_type">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>CP</label>
                        <input type="number" step="0.01" class="form-control" name="cp">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>MAP</label>
                        <input type="number" step="0.01" class="form-control" name="map_price">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>EB Adult CP</label>
                        <input type="number" step="0.01" class="form-control" name="eb_adult_cp">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>EB Adult MAP</label>
                        <input type="number" step="0.01" class="form-control" name="eb_adult_map">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Child With Bed CP</label>
                        <input type="number" step="0.01" class="form-control" name="child_with_bed_cp">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Child With Bed MAP</label>
                        <input type="number" step="0.01" class="form-control" name="child_with_bed_map">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Child Without Bed CP</label>
                        <input type="number" step="0.01" class="form-control" name="child_without_bed_cp">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Child Without Bed MAP</label>
                        <input type="number" step="0.01" class="form-control" name="child_without_bed_map">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>XMAS/New Year Supplement Charges</label>
                        <input type="number" step="0.01" class="form-control" name="xmas_newyear_charges">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Adult Meal Charges</label>
                        <input type="number" step="0.01" class="form-control" name="adult_meal_charges">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Kids Meal Charges</label>
                        <input type="number" step="0.01" class="form-control" name="kids_meal_charges">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select class="custom-select" name="availability_status" required>
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Booking Status</label>
                        <select class="custom-select" name="booking_status" required>
                            <option value="Booking Confirmed">Booking Confirmed</option>
                            <option value="Amendment">Amendment</option>
                            <option value="Cancelation">Cancelation</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="custom-select" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Hotel/Resort</button>
                <a href="hotel_resorts.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>