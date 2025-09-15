<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update table structure
    $alter_queries = [
        "ALTER TABLE accommodation_details ADD COLUMN star_category VARCHAR(10) DEFAULT '3 Star'",
        "ALTER TABLE accommodation_details ADD COLUMN season_type VARCHAR(20) DEFAULT 'WINTER'",
        "ALTER TABLE accommodation_details ADD COLUMN room_price_adult DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE accommodation_details ADD COLUMN room_price_child_with_bed DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE accommodation_details ADD COLUMN room_price_child_without_bed DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE accommodation_details ADD COLUMN meal_charge_adult DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE accommodation_details ADD COLUMN meal_charge_child DECIMAL(10,2) DEFAULT 0.00"
    ];
    
    foreach($alter_queries as $query) {
        @mysqli_query($conn, $query);
    }
    
    $star_category = trim($_POST['star_category']);
    $destination = trim($_POST['destination']);
    $hotel_name = trim($_POST['hotel_name']);
    $room_category = trim($_POST['room_category']);
    $meal_type = trim($_POST['meal_type']);
    $room_price_adult = floatval($_POST['room_price_adult']);
    $room_price_child_with_bed = floatval($_POST['room_price_child_with_bed']);
    $room_price_child_without_bed = floatval($_POST['room_price_child_without_bed']);
    $meal_charge_adult = floatval($_POST['meal_charge_adult']);
    $meal_charge_child = floatval($_POST['meal_charge_child']);
    $season_type = trim($_POST['season_type']);
    $validity_from = $_POST['validity_from'];
    $validity_to = $_POST['validity_to'];
    
    if (empty($destination) || empty($hotel_name) || empty($room_category) || empty($validity_from) || empty($validity_to)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO accommodation_details (star_category, destination, hotel_name, room_category, meal_type, room_price_adult, room_price_child_with_bed, room_price_child_without_bed, meal_charge_adult, meal_charge_child, season_type, validity_from, validity_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssdddddsss", $star_category, $destination, $hotel_name, $room_category, $meal_type, $room_price_adult, $room_price_child_with_bed, $room_price_child_without_bed, $meal_charge_adult, $meal_charge_child, $season_type, $validity_from, $validity_to);
            
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Star Category <span class="text-danger">*</span></label>
                        <select name="star_category" class="form-control" required>
                            <option value="3 Star">3 Star</option>
                            <option value="4 Star">4 Star</option>
                            <option value="5 Star">5 Star</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Hotel Name <span class="text-danger">*</span></label>
                        <input type="text" name="hotel_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room Category <span class="text-danger">*</span></label>
                        <input type="text" name="room_category" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Type</label>
                        <select name="meal_type" class="form-control">
                            <option value="EPAI">EPAI</option>
                            <option value="CPAI">CPAI</option>
                            <option value="MAPAI">MAPAI</option>
                            <option value="APAI">APAI</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Season Type</label>
                        <select name="season_type" class="form-control">
                            <option value="DIWALI">DIWALI</option>
                            <option value="POOJA">POOJA</option>
                            <option value="CHRISTMAS">CHRISTMAS</option>
                            <option value="NEW YEAR">NEW YEAR</option>
                            <option value="SUMMER">SUMMER</option>
                            <option value="WINTER">WINTER</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room Price - Adult (₹)</label>
                        <input type="number" step="0.01" name="room_price_adult" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Child with Bed (₹)</label>
                        <input type="number" step="0.01" name="room_price_child_with_bed" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Child without Bed (₹)</label>
                        <input type="number" step="0.01" name="room_price_child_without_bed" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Charge - Adult (₹)</label>
                        <input type="number" step="0.01" name="meal_charge_adult" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Charge - Child (₹)</label>
                        <input type="number" step="0.01" name="meal_charge_child" class="form-control" value="0">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Valid From <span class="text-danger">*</span></label>
                        <input type="date" name="validity_from" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Valid To <span class="text-danger">*</span></label>
                        <input type="date" name="validity_to" class="form-control" required>
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