<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $hotel_name = trim($_POST['hotel_name']);
    $room_category = trim($_POST['room_category']);
    $cp = floatval($_POST['cp']);
    $map_rate = floatval($_POST['map_rate']);
    $eb_adult_cp = floatval($_POST['eb_adult_cp']);
    $eb_adult_map = floatval($_POST['eb_adult_map']);
    $child_with_bed_cp = floatval($_POST['child_with_bed_cp']);
    $child_with_bed_map = floatval($_POST['child_with_bed_map']);
    $child_without_bed_cp = floatval($_POST['child_without_bed_cp']);
    $child_without_bed_map = floatval($_POST['child_without_bed_map']);
    $xmas_newyear_charges = floatval($_POST['xmas_newyear_charges']);
    $meal_type = trim($_POST['meal_type']);
    $meal_charges = floatval($_POST['meal_charges']);
    $child_meal_price = floatval($_POST['child_meal_price']);
    $validity_from = $_POST['validity_from'];
    $validity_to = $_POST['validity_to'];
    $remark = trim($_POST['remark']);
    
    if (empty($destination) || empty($hotel_name) || empty($room_category) || empty($validity_from) || empty($validity_to)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!DateTime::createFromFormat('Y-m-d', $validity_from) || !DateTime::createFromFormat('Y-m-d', $validity_to)) {
        $error_message = "Please enter valid dates in YYYY-MM-DD format.";
    } else {
        // Add child_meal_price column if it doesn't exist
        $add_column_sql = "ALTER TABLE accommodation_details ADD COLUMN child_meal_price DECIMAL(10,2) DEFAULT 0.00";
        @mysqli_query($conn, $add_column_sql);
        
        $sql = "INSERT INTO accommodation_details (destination, hotel_name, room_category, cp, map_rate, eb_adult_cp, eb_adult_map, child_with_bed_cp, child_with_bed_map, child_without_bed_cp, child_without_bed_map, xmas_newyear_charges, meal_type, meal_charges, child_meal_price, validity_from, validity_to, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssdddddddddsddsss", $destination, $hotel_name, $room_category, $cp, $map_rate, $eb_adult_cp, $eb_adult_map, $child_with_bed_cp, $child_with_bed_map, $child_without_bed_cp, $child_without_bed_map, $xmas_newyear_charges, $meal_type, $meal_charges, $child_meal_price, $validity_from, $validity_to, $remark);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Accommodation detail added successfully!";
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
        <h4 class="text-blue h4">Add Accommodation Detail</h4>
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
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Hotel Name <span class="text-danger">*</span></label>
                        <input type="text" name="hotel_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Room Category <span class="text-danger">*</span></label>
                        <input type="text" name="room_category" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>CP (₹)</label>
                        <input type="number" step="0.01" name="cp" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>MAP (₹)</label>
                        <input type="number" step="0.01" name="map_rate" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>EB Adult CP (₹)</label>
                        <input type="number" step="0.01" name="eb_adult_cp" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>EB Adult MAP (₹)</label>
                        <input type="number" step="0.01" name="eb_adult_map" class="form-control" value="0">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Child With Bed CP (₹)</label>
                        <input type="number" step="0.01" name="child_with_bed_cp" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Child With Bed MAP (₹)</label>
                        <input type="number" step="0.01" name="child_with_bed_map" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Child Without Bed CP (₹)</label>
                        <input type="number" step="0.01" name="child_without_bed_cp" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Child Without Bed MAP (₹)</label>
                        <input type="number" step="0.01" name="child_without_bed_map" class="form-control" value="0">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Xmas/NewYear Charges (₹)</label>
                        <input type="number" step="0.01" name="xmas_newyear_charges" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Meal Type</label>
                        <input type="text" name="meal_type" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Adult Meal Charges (₹)</label>
                        <input type="number" step="0.01" name="meal_charges" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Child Meal Price (₹)</label>
                        <input type="number" step="0.01" name="child_meal_price" class="form-control" value="0">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Validity From <span class="text-danger">*</span></label>
                        <input type="date" name="validity_from" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Validity To <span class="text-danger">*</span></label>
                        <input type="date" name="validity_to" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remark</label>
                        <textarea name="remark" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Accommodation Detail</button>
                <a href="accommodation_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>