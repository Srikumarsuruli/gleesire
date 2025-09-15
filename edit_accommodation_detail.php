<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$accommodation_detail = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: accommodation_details.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM accommodation_details WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $accommodation_detail = mysqli_fetch_assoc($result);
        } else {
            header("Location: accommodation_details.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $sql = "UPDATE accommodation_details SET star_category = ?, destination = ?, hotel_name = ?, room_category = ?, meal_type = ?, room_price_adult = ?, room_price_child_with_bed = ?, room_price_child_without_bed = ?, meal_charge_adult = ?, meal_charge_child = ?, season_type = ?, validity_from = ?, validity_to = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssdddddsssi", $star_category, $destination, $hotel_name, $room_category, $meal_type, $room_price_adult, $room_price_child_with_bed, $room_price_child_without_bed, $meal_charge_adult, $meal_charge_child, $season_type, $validity_from, $validity_to, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Accommodation detail updated successfully!";
                // Refresh data
                $accommodation_detail['star_category'] = $star_category;
                $accommodation_detail['destination'] = $destination;
                $accommodation_detail['hotel_name'] = $hotel_name;
                $accommodation_detail['room_category'] = $room_category;
                $accommodation_detail['meal_type'] = $meal_type;
                $accommodation_detail['room_price_adult'] = $room_price_adult;
                $accommodation_detail['room_price_child_with_bed'] = $room_price_child_with_bed;
                $accommodation_detail['room_price_child_without_bed'] = $room_price_child_without_bed;
                $accommodation_detail['meal_charge_adult'] = $meal_charge_adult;
                $accommodation_detail['meal_charge_child'] = $meal_charge_child;
                $accommodation_detail['season_type'] = $season_type;
                $accommodation_detail['validity_from'] = $validity_from;
                $accommodation_detail['validity_to'] = $validity_to;
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
        <h4 class="text-blue h4">Edit Accommodation Detail</h4>
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
                            <option value="3 Star" <?php echo ($accommodation_detail['star_category'] ?? '3 Star') == '3 Star' ? 'selected' : ''; ?>>3 Star</option>
                            <option value="4 Star" <?php echo ($accommodation_detail['star_category'] ?? '') == '4 Star' ? 'selected' : ''; ?>>4 Star</option>
                            <option value="5 Star" <?php echo ($accommodation_detail['star_category'] ?? '') == '5 Star' ? 'selected' : ''; ?>>5 Star</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($accommodation_detail['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Hotel Name <span class="text-danger">*</span></label>
                        <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($accommodation_detail['hotel_name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room Category <span class="text-danger">*</span></label>
                        <input type="text" name="room_category" class="form-control" value="<?php echo htmlspecialchars($accommodation_detail['room_category']); ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Type</label>
                        <select name="meal_type" class="form-control">
                            <option value="EPAI" <?php echo ($accommodation_detail['meal_type'] ?? 'EPAI') == 'EPAI' ? 'selected' : ''; ?>>EPAI</option>
                            <option value="CPAI" <?php echo ($accommodation_detail['meal_type'] ?? '') == 'CPAI' ? 'selected' : ''; ?>>CPAI</option>
                            <option value="MAPAI" <?php echo ($accommodation_detail['meal_type'] ?? '') == 'MAPAI' ? 'selected' : ''; ?>>MAPAI</option>
                            <option value="APAI" <?php echo ($accommodation_detail['meal_type'] ?? '') == 'APAI' ? 'selected' : ''; ?>>APAI</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Season Type</label>
                        <select name="season_type" class="form-control">
                            <option value="DIWALI" <?php echo ($accommodation_detail['season_type'] ?? '') == 'DIWALI' ? 'selected' : ''; ?>>DIWALI</option>
                            <option value="POOJA" <?php echo ($accommodation_detail['season_type'] ?? '') == 'POOJA' ? 'selected' : ''; ?>>POOJA</option>
                            <option value="CHRISTMAS" <?php echo ($accommodation_detail['season_type'] ?? '') == 'CHRISTMAS' ? 'selected' : ''; ?>>CHRISTMAS</option>
                            <option value="NEW YEAR" <?php echo ($accommodation_detail['season_type'] ?? '') == 'NEW YEAR' ? 'selected' : ''; ?>>NEW YEAR</option>
                            <option value="SUMMER" <?php echo ($accommodation_detail['season_type'] ?? '') == 'SUMMER' ? 'selected' : ''; ?>>SUMMER</option>
                            <option value="WINTER" <?php echo ($accommodation_detail['season_type'] ?? 'WINTER') == 'WINTER' ? 'selected' : ''; ?>>WINTER</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Room Price - Adult (₹)</label>
                        <input type="number" step="0.01" name="room_price_adult" class="form-control" value="<?php echo $accommodation_detail['room_price_adult'] ?? 0; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Child with Bed (₹)</label>
                        <input type="number" step="0.01" name="room_price_child_with_bed" class="form-control" value="<?php echo $accommodation_detail['room_price_child_with_bed'] ?? 0; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Child without Bed (₹)</label>
                        <input type="number" step="0.01" name="room_price_child_without_bed" class="form-control" value="<?php echo $accommodation_detail['room_price_child_without_bed'] ?? 0; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Charge - Adult (₹)</label>
                        <input type="number" step="0.01" name="meal_charge_adult" class="form-control" value="<?php echo $accommodation_detail['meal_charge_adult'] ?? 0; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Meal Charge - Child (₹)</label>
                        <input type="number" step="0.01" name="meal_charge_child" class="form-control" value="<?php echo $accommodation_detail['meal_charge_child'] ?? 0; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Valid From <span class="text-danger">*</span></label>
                        <input type="date" name="validity_from" class="form-control" value="<?php echo $accommodation_detail['validity_from']; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Valid To <span class="text-danger">*</span></label>
                        <input type="date" name="validity_to" class="form-control" value="<?php echo $accommodation_detail['validity_to']; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Accommodation Detail</button>
                <a href="accommodation_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>